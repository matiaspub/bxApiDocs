<?php
namespace Bitrix\Catalog;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CatalogViewedProductTable extends Main\Entity\DataManager
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
			'ELEMENT_ID' => array(
				'data_type' => 'integer'
			),
			'SITE_ID' => array(
				'data_type' => 'string'
			),
			'VIEW_COUNT' => array(
				'data_type' => 'integer'
			),
			'RECOMMENDATION' => array(
				'data_type' => 'string'
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
	 * @param int $productId			Id of product.
	 * @param int $fuserId				User basket id.
	 * @param string $siteId			Site id.
	 * @param int $elementId			Parent id.
	 * @param string $recommendationId	Bigdata recommendation id.
	 *
	 * @return int
	 */
	public static function refresh($productId, $fuserId, $siteId = SITE_ID, $elementId = 0, $recommendationId = '')
	{
		$productId = (int)$productId;
		$fuserId = (int)$fuserId;
		$siteId = (string)$siteId;
		$elementId = (int)$elementId;
		$recommendationId = (string)$recommendationId;
		if ($productId <= 0 || $fuserId <= 0 || $siteId == '')
			return -1;

		if (Main\Loader::includeModule('statistic') && isset($_SESSION['SESS_SEARCHER_ID']) && (int)$_SESSION['SESS_SEARCHER_ID'] > 0)
			return -1;

		$filter = array('=FUSER_ID' => $fuserId, '=SITE_ID' => $siteId);

		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$sqlSiteId = $helper->forSql($siteId);

		if ($elementId > 0)
		{
			$filter["=ELEMENT_ID"] = $elementId;

			// Delete parent product id (for capability)
			if ($elementId != $productId)
				$connection->query(
					"delete from b_catalog_viewed_product where PRODUCT_ID = ".$elementId." and FUSER_ID = ".$fuserId." and SITE_ID = '".$sqlSiteId."'"
				);
		}
		else
		{
			$productInfo = \CCatalogSKU::getProductInfo($productId);
			// Real SKU ID
			if (!empty($productInfo))
			{
				$elementId = $productInfo['ID'];
				$siblings = array();
				// Delete parent product id (for capability)
				$connection->query("delete from b_catalog_viewed_product
									where PRODUCT_ID = ".$productInfo['ID']." and FUSER_ID = ".$fuserId." and SITE_ID = '" .$sqlSiteId. "'"
				);

				$skus = \CIBlockElement::getList(
					array(),
					array('IBLOCK_ID' => $productInfo['OFFER_IBLOCK_ID'], '=PROPERTY_'.$productInfo['SKU_PROPERTY_ID'] => $productInfo['ID']),
					false,
					false,
					array('ID', 'IBLOCK_ID')
				);
				while ($oneSku = $skus->fetch())
					$siblings[] = $oneSku['ID'];
				unset($oneSku, $skus);

				$filter['@PRODUCT_ID'] = $siblings;
			}
			else
			{
				$elementId = $productId;
				$filter['=PRODUCT_ID'] = $productId;
			}
		}

		// recommendation
		if (!empty($elementId))
		{
			global $APPLICATION;

			$recommendationCookie = $APPLICATION->get_cookie(Main\Analytics\Catalog::getCookieLogName());
			if (!empty($recommendationCookie))
			{
				$recommendations = Main\Analytics\Catalog::decodeProductLog($recommendationCookie);
				if (is_array($recommendations) && isset($recommendations[$elementId]))
					$recommendationId = $recommendations[$elementId][0];
			}
		}

		$iterator = static::getList(array(
			'select' => array('ID', 'FUSER_ID', 'DATE_VISIT', 'PRODUCT_ID', 'SITE_ID', 'VIEW_COUNT'),
			'filter' => $filter
		));

		if ($row = $iterator->fetch())
		{
			$update = array(
				"PRODUCT_ID" => $productId,
				"DATE_VISIT" => new Main\Type\DateTime,
				'VIEW_COUNT' => $row['VIEW_COUNT'] + 1,
				"ELEMENT_ID" => $elementId
			);
			if (!empty($recommendationId))
				$update["RECOMMENDATION"] = $recommendationId;

			$result = static::update($row['ID'], $update);
			return ($result->isSuccess(true) ? $row['ID'] : -1);
		}
		else
		{
			$result = static::add(array(
				"FUSER_ID" => $fuserId,
				"DATE_VISIT" => new Main\Type\DateTime(),
				"PRODUCT_ID" => $productId,
				"ELEMENT_ID" => $elementId,
				"SITE_ID" => $siteId,
				"VIEW_COUNT" => 1,
				"RECOMMENDATION" => $recommendationId
			));
			return ($result->isSuccess(true) ? $result->getId() : -1);
		}
	}

	/**
	 * Returns ids map: SKU_PRODUCT_ID => PRODUCT_ID.
	 *
	 * @param array $originalIds			Input products ids.
	 * @return array
	 */
	public static function getProductsMap(array $originalIds = array())
	{
		if (empty($originalIds) && !is_array($originalIds))
			return array();

		$result = array();
		$productList = \CCatalogSKU::getProductList($originalIds);
		if ($productList === false)
			$productList = array();
		foreach ($originalIds as &$oneId)
			$result[$oneId] = (isset($productList[$oneId]) ? $productList[$oneId]['ID'] : $oneId);
		unset($oneId, $productList);
		return $result;
	}

	/**
	 * Returns product map: array('PRODUCT_ID' => 'ELEMENT_ID').
	 *
	 * @param int $iblockId					Iblock Id.
	 * @param int $sectionId				Section Id.
	 * @param int $fuserId					Sale user Id.
	 * @param int $excludeProductId				Exclude item Id.
	 * @param int $limit					Max count.
	 * @param int $depth					Depth level.
	 * @param string|null $siteId			Site identifier.
	 * @return array
	 */
	public static function getProductSkuMap($iblockId, $sectionId, $fuserId, $excludeProductId, $limit, $depth = 0, $siteId = null)
	{
		$map = array();

		$iblockId = (int)$iblockId;
		$sectionId = (int)$sectionId;
		$fuserId = (int)$fuserId;
		$excludeProductId = (int)$excludeProductId;
		$limit = (int)$limit;
		$depth = (int)$depth;
		if ($depth <= 0 || $fuserId <= 0)
			return $map;

		if (empty($siteId))
		{
			$context = Application::getInstance()->getContext();
			$siteId = $context->getSite();
		}
		if (empty($siteId))
			return $map;

		$con = Application::getConnection();
		$sqlHelper = $con->getSqlHelper();

		$subSections = array();

		if ($depth > 0)
		{
			$strSql = "SELECT BSprS.ID AS ID
						FROM b_iblock_section BSprS
							INNER JOIN b_iblock_section BS1
							ON (
								BSprS.IBLOCK_ID = BS1.IBLOCK_ID
								AND BSprS.LEFT_MARGIN <= BS1.LEFT_MARGIN
								AND BSprS.RIGHT_MARGIN >= BS1.RIGHT_MARGIN
							)
						WHERE BS1.ID = ".$sectionId." AND BSprS.DEPTH_LEVEL = " .$depth;

			$result = $con->query($strSql, null);
			while ($item = $result->fetch())
				$subSections[] = $item['ID'];
		}
		if (!empty($subSections))
			$subSections[] = 0;

		$strSql = "SELECT CVP.PRODUCT_ID AS PRODUCT_ID, CVP.ELEMENT_ID AS ELEMENT_ID
					FROM b_catalog_viewed_product CVP
						INNER JOIN b_iblock_element BE ON BE.ID = CVP.ELEMENT_ID
						INNER JOIN (
							SELECT DISTINCT BSE.IBLOCK_ELEMENT_ID
							FROM b_iblock_section_element BSE
								INNER JOIN b_iblock_section BSubS ON BSE.IBLOCK_SECTION_ID = BSubS.ID
								INNER JOIN b_iblock_section BS ON (BSubS.IBLOCK_ID = BS.IBLOCK_ID
									AND BSubS.LEFT_MARGIN >= BS.LEFT_MARGIN
									AND BSubS.RIGHT_MARGIN <= BS.RIGHT_MARGIN)
							WHERE ".( !empty($subSections) ? "BS.ID IN (".implode(', ', $subSections).") OR " : "").
							"BS.ID = ".$sectionId."
						) BES ON BES.IBLOCK_ELEMENT_ID = BE.ID

					WHERE CVP.FUSER_ID = ".$fuserId."
						AND CVP.SITE_ID = '".$sqlHelper->forSql($siteId)."'
						AND BE.ID <> ".$excludeProductId."
						AND BE.IBLOCK_ID = ".$iblockId."
						AND (BE.WF_STATUS_ID = 1 AND BE.WF_PARENT_ELEMENT_ID IS NULL)
					ORDER BY CVP.DATE_VISIT DESC";

		$result = $con->query($strSql, null, 0, $limit);

		while($item = $result->fetch())
			$map[$item['PRODUCT_ID']] = $item['ELEMENT_ID'];

		return $map;
	}

	/**
	 * Clear table b_catalog_viewed_product.
	 *
	 * @param int $liveTime			Live time.
	 * @return void
	 */
	public static function clear($liveTime = 10)
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$liveTime = (int)$liveTime;
		$liveTo = $helper->addSecondsToDateTime($liveTime * 24 * 3600, "DATE_VISIT");
		$now = $helper->getCurrentDateTimeFunction();

		$deleteSql = "delete from b_catalog_viewed_product where ".$now." > ".$liveTo;
		$connection->query($deleteSql);
	}

	/**
	 * For agent.
	 *
	 * @return string
	 */
	public static function clearAgent()
	{
		self::clear((int)Option::get('catalog', 'viewed_time'));
		return '\Bitrix\Catalog\CatalogViewedProductTable::clearAgent();';
	}
}