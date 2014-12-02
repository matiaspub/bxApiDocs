<?php
namespace Bitrix\Catalog;

use Bitrix\Main\Entity\DataManager as DataManager;
use Bitrix\Main\Application as Application;
use Bitrix\Main\Entity\Query as Query;
use Bitrix\Main\Config\Option as Option;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CatalogViewedProductTable extends DataManager
{
	/**
	 * @override
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_catalog_viewed_product';
	}

	/**
	 * @override
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),

			'FUSER_ID' => array(
				'data_type' => 'integer',
			),

			'DATE_VISIT' => array(
				'data_type' => 'datetime',
			),
			'PRODUCT_ID' => array(
				'data_type' => 'integer'
			),
			'SITE_ID' => array(
				'data_type' => 'string'
			),
			'VIEW_COUNT' => array(
				'data_type' => 'integer'
			),
			'ELEMENT' => array(
				'data_type' => '\Bitrix\Iblock\ElementTable',
				'reference' => array('=this.PRODUCT_ID' => 'ref.ID'),
				'join_type' => 'INNER'
			),
			'PRODUCT' => array(
				'data_type' => '\Bitrix\Sale\ProductTable',
				'reference' => array('=this.PRODUCT_ID' => 'ref.ID')
			),
			'FUSER' => array(
				'data_type' => '\Bitrix\Sale\FuserTable',
				'reference' => array('=this.FUSER_ID' => 'ref.ID')
			)
		);
	}

	/**
	 * Common function, used to update/insert any product.
	 *
	 * @param int $productId   Id of product.
	 * @param int $fuserId   User basket id.
	 * @param string $siteId      Site id.
	 *
	 * @return int Id of row.
	 */
	public static function refresh($productId, $fuserId, $siteId = SITE_ID)
	{
		$connection = Application::getConnection();

		$productId = (int)$productId;
		if ($productId <= 0)
		{
			return -1;
		}
		$iblockID = (int)\CIBlockElement::getIBlockByID($productId);
		if ($iblockID <= 0)
		{
			return -1;
		}
		$productInfo = \CCatalogSKU::getProductInfo($productId, $iblockID);

		$fuserId = (int)$fuserId;
		if($fuserId <= 0)
		{
			return -1;
		}

		if(!is_string($siteId) || strlen($siteId) <=0 )
		{
			return -1;
		}
		$filter = array("FUSER_ID" => $fuserId, "SITE_ID" => $siteId);

		// Concrete SKU ID
		if (!empty($productInfo))
		{
			$filter['PRODUCT_ID'] = array();
			$siblings = array();

			// Delete parent product id (for capability)
			$connection->query("DELETE FROM b_catalog_viewed_product WHERE PRODUCT_ID = {$productInfo['ID']} AND FUSER_ID = {$fuserId} AND SITE_ID = '{$siteId}'");

			$skuInfo = \CCatalogSKU::getInfoByOfferIBlock($iblockID);
			$skus = \CIBlockElement::getList(
				array(),
				array('IBLOCK_ID' => $iblockID, 'PROPERTY_'.$skuInfo['SKU_PROPERTY_ID'] => $productInfo['ID']),
				false,
				false,
				array('ID', 'IBLOCK_ID')
			);
			while ($oneSku = $skus->fetch())
			{
				$siblings[] = $oneSku['ID'];
			}

			$filter["PRODUCT_ID"] = $siblings;
		}
		else
		{
			$filter["PRODUCT_ID"] = $productId;
		}

		$iterator = static::getList(array(
			"filter" => $filter,
			"select" => array("ID", "FUSER_ID", "DATE_VISIT", "PRODUCT_ID", "SITE_ID", "VIEW_COUNT")
		));

		if ($row = $iterator->fetch())
		{
			static::update($row["ID"], array("PRODUCT_ID" => $productId, "DATE_VISIT" => new \Bitrix\Main\Type\DateTime(), 'VIEW_COUNT' => $row['VIEW_COUNT'] + 1));
			return $row['ID'];
		}
		else
		{
			$result = static::add(array(
				"FUSER_ID" => $fuserId,
				"DATE_VISIT" => new \Bitrix\Main\Type\DateTime(),
				"PRODUCT_ID" => $productId,
				"SITE_ID" => $siteId,
				"VIEW_COUNT" => 1
			));
			return $result->getId();
		}
	}


	/**
	 * Returns ids map: SKU_PRODUCT_ID => PRODUCT_ID
	 *
	 * @param array $originalIds Input products ids.
	 * @return integer[]
	 */
	public static function getProductsMap(array $originalIds = array())
	{
		if(!is_array($originalIds) || !count($originalIds))
			return array();

		$newIds = array();
		$catalogIterator = \CCatalog::getList(array("IBLOCK_ID" => "ASC"), array("!SKU_PROPERTY_ID" => 0), false, false, array("IBLOCK_ID", "SKU_PROPERTY_ID"));
		while($catalog = $catalogIterator->fetch())
		{
			$elementIterator = \CIBlockElement::getList(
				array(),
				array("ID" => $originalIds, "IBLOCK_ID" => $catalog['IBLOCK_ID']),
				false,
				false,
				array("ID", "IBLOCK_ID", "PROPERTY_" . $catalog['SKU_PROPERTY_ID'])
			);

			while ($item = $elementIterator->fetch())
			{
				$propertyName = "PROPERTY_" . $catalog['SKU_PROPERTY_ID'] . "_VALUE";
				$parentId = $item[$propertyName];
				if (!empty($parentId))
				{
					$newIds[$item['ID']] = $parentId;
				}
				else
				{
					$newIds[$item['ID']] = $item['ID'];
				}
			}
		}

		// Push missing
		foreach ($originalIds as $id)
		{
			if (!isset($newIds[$id]))
			{
				$newIds[$id] = $id;
			}
		}

		// Resort map
		$tmpMap = array();
		foreach ($originalIds as $id)
		{
			$tmpMap[$id . ""] = $newIds[$id];
		}

		return $tmpMap;
	}

	/**
	 * Clear table b_catalog_viewed_product
	 *
	 * @param int $liveTime Live time.
	 * @return void
	 */
	public static function clear($liveTime = 10)
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$liveTime = (int)$liveTime;
		$liveTo = $helper->addSecondsToDateTime($liveTime * 24 * 3600, "DATE_VISIT");
		$now = $helper->getCurrentDateTimeFunction();

		$deleteSql = "DELETE FROM b_catalog_viewed_product WHERE $now > $liveTo";
		$connection->query($deleteSql);
	}

	/**
	 * For agent
	 *
	 * @return string
	 */
	public static function clearAgent()
	{
		self::clear(Option::get('catalog', 'viewed_time'));
		return '\Bitrix\Catalog\CatalogViewedProductTable::clearAgent(0);';
	}
}

?>