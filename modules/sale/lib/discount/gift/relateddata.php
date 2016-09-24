<?php
namespace Bitrix\Sale\Discount\Gift;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\DB\MssqlConnection;
use Bitrix\Main\DB\MysqlCommonConnection;
use Bitrix\Main\DB\OracleConnection;
use Bitrix\Main\Entity\DataManager;

/**
 * @internals
 *
 * Class RelatedDataTable.
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DISCOUNT_ID int mandatory
 * <li> ELEMENT_ID int optional
 * <li> SECTION_ID int optional
 * <li> MAIN_PRODUCT_SECTION_ID int optional
 * </ul>
 *
 * @package Bitrix\Sale\Discount\Gift
 **/

final class RelatedDataTable extends DataManager
{
	const MAX_LENGTH_BATCH_MYSQL_QUERY = 2048;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sale_gift_related_data';
	}

	/**
	 * Returns entity map definition.
	 *
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
			'DISCOUNT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'DISCOUNT' => array(
				'data_type' => '\Bitrix\Sale\Internals\DiscountTable',
				'reference' => array(
					'=this.DISCOUNT_ID' => 'ref.ID'
				),
				'join_type' => 'INNER',
			),
			'DISCOUNT_GROUP' => array(
				'data_type' => '\Bitrix\Sale\Internals\DiscountGroupTable',
				'reference' => array(
					'=this.DISCOUNT_ID' => 'ref.DISCOUNT_ID'
				),
				'join_type' => 'INNER',
			),
			'ELEMENT_ID' => array(
				'data_type' => 'integer',
			),
			'SECTION_ID' => array(
				'data_type' => 'integer',
			),
			'MAIN_PRODUCT_SECTION_ID' => array(
				'data_type' => 'integer',
			),
		);
	}

	/**
	 * Deletes rows by discounts.
	 *
	 * @param array $discountIds List of discount ids.
	 * @return void
	 */
	public static function deleteByDiscounts(array $discountIds)
	{
		if(empty($discountIds))
		{
			return;
		}
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$connection->queryExecute(
			'delete from '.$helper->quote(self::getTableName()).
			' where ' . $helper->quote('DISCOUNT_ID') . ' in ('.implode(',', array_map('intval', $discountIds)).')'
		);
	}

	/**
	 * Deletes rows by discount id.
	 *
	 * @param int $discountId Id of discount.
	 * @return void
	 */
	public static function deleteByDiscount($discountId)
	{
		$discountId = (int)$discountId;
		if($discountId <= 0)
		{
			return;
		}
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$connection->queryExecute('delete from ' . $helper->quote(self::getTableName()) . ' where ' . $helper->quote('DISCOUNT_ID') . ' = ' . $discountId);
	}

	/**
	 * Fills table of related data by discount.
	 *
	 * @param array $discount Discount.
	 * @return void
	 */
	public static function fillByDiscount(array $discount)
	{
		list($elementIds, $sectionIds) = static::getGiftsData($discount);
		list($productElementIds, $productSectionIds) = static::getProductsData($discount);

		//we works only with one section in condition.
		$mainProductSectionId = reset($productSectionIds);
		if(!is_int($mainProductSectionId))
		{
			$mainProductSectionId = null;
		}

		$items = array();
		foreach($elementIds as $elementId)
		{
			$items[] = array(
				'DISCOUNT_ID' => $discount['ID'],
				'ELEMENT_ID' => $elementId,
				'SECTION_ID' => null,
				'MAIN_PRODUCT_SECTION_ID' => $mainProductSectionId,
			);
		}

		foreach($sectionIds as $sectionId)
		{
			$items[] = array(
				'DISCOUNT_ID' => $discount['ID'],
				'ELEMENT_ID' => null,
				'SECTION_ID' => $sectionId,
				'MAIN_PRODUCT_SECTION_ID' => $mainProductSectionId,
			);
		}

		static::insertBatch($items);
	}

	/**
	 * Returns gift data which contains list of section id, element id. It's gifts for the discount.
	 *
	 * @param array $discount The discount.
	 * @return array
	 */
	public static function getGiftsData(array $discount)
	{
		$sectionIds = $elementIds = array();

		if (
			(empty($discount['ACTIONS_LIST']) || !is_array($discount['ACTIONS_LIST']))
			&& checkSerializedData($discount['ACTIONS']))
		{
			$discount['ACTIONS_LIST'] = unserialize($discount['ACTIONS']);
		}

		if(!isset($discount['ACTIONS_LIST']['CHILDREN']) && is_array($discount['ACTIONS_LIST']['CHILDREN']))
		{
			return array($elementIds, $sectionIds);
		}

		foreach($discount['ACTIONS_LIST']['CHILDREN'] as $child)
		{
			if(!isset($child['CLASS_ID']) || !isset($child['DATA']) || $child['CLASS_ID'] !== \CSaleActionGiftCtrlGroup::getControlID())
			{
				continue;
			}
			foreach($child['CHILDREN'] as $gifterChild)
			{
				switch($gifterChild['CLASS_ID'])
				{
					case 'GifterCondIBElement':
						$elementIds = array_merge($elementIds, (array)$gifterChild['DATA']['Value']);
						break;
					case 'GifterCondIBSection':
						$sectionIds = array_merge($sectionIds, (array)$gifterChild['DATA']['Value']);
						break;
				}
			}
			unset($gifterChild);
		}
		unset($child);

		return array($elementIds, $sectionIds);
	}

	/**
	 * Returns main product data which contains list of section id, element id. It's main products for the discount.
	 *
	 * @param array $discount The discount.
	 * @return array
	 */
	public static function getProductsData(array $discount)
	{
		$sectionIds = $elementIds = array();

		if (
			(empty($discount['CONDITIONS_LIST']) || !is_array($discount['CONDITIONS_LIST']))
			&& checkSerializedData($discount['CONDITIONS']))
		{
			$discount['CONDITIONS_LIST'] = unserialize($discount['CONDITIONS']);
		}

		if(!isset($discount['CONDITIONS_LIST']['CLASS_ID']) || $discount['CONDITIONS_LIST']['CLASS_ID'] !== 'CondGroup')
		{
			return array($elementIds, $sectionIds);
		}
		if(empty($discount['CONDITIONS_LIST']['CHILDREN']))
		{
			return array($elementIds, $sectionIds);
		}
		if(count($discount['CONDITIONS_LIST']['CHILDREN']) > 1)
		{
			return array($elementIds, $sectionIds);
		}
		$child = reset($discount['CONDITIONS_LIST']['CHILDREN']);

		if($child['CLASS_ID'] !== 'CondBsktProductGroup')
		{
			return array($elementIds, $sectionIds);
		}

		if(empty($child['CHILDREN']))
		{
			return array($elementIds, $sectionIds);
		}
		if(count($child['CHILDREN']) > 1)
		{
			return array($elementIds, $sectionIds);
		}
		$condition = reset($child['CHILDREN']);

		if(!isset($condition['DATA']['logic']) || $condition['DATA']['logic'] !== 'Equal')
		{
			return array($elementIds, $sectionIds);
		}

		switch($condition['CLASS_ID'])
		{
			case 'CondIBElement':
				$elementIds = (array)$condition['DATA']['value'];
				break;
			case 'CondIBSection':
				$sectionIds = (array)$condition['DATA']['value'];
				break;
		}

		return array($elementIds, $sectionIds);
	}

	/**
	 * Adds rows to table.
	 * @param array $items Items.
	 * @internal
	 */
	private static function insertBatch(array $items)
	{
		$tableName = static::getTableName();
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$query = $prefix = '';
		if($connection instanceof MysqlCommonConnection)
		{
			foreach ($items as $item)
			{
				list($prefix, $values) = $sqlHelper->prepareInsert($tableName, $item);

				$query .= ($query? ', ' : ' ') . '(' . $values . ')';
				if(strlen($query) > self::MAX_LENGTH_BATCH_MYSQL_QUERY)
				{
					$connection->queryExecute("INSERT INTO {$tableName} ({$prefix}) VALUES {$query}");
					$query = '';
				}
			}
			unset($item);

			if($query && $prefix)
			{
				$connection->queryExecute("INSERT INTO {$tableName} ({$prefix}) VALUES {$query}");
			}
		}
		elseif($connection instanceof MssqlConnection)
		{
			$valueData = array();
			foreach ($items as $item)
			{
				list($prefix, $values) = $sqlHelper->prepareInsert($tableName, $item);
				$valueData[] = "SELECT {$values}";
			}
			unset($item);

			$valuesSql = implode(' UNION ALL ', $valueData);
			if($valuesSql && $prefix)
			{
				$connection->queryExecute("INSERT INTO {$tableName} ({$prefix}) $valuesSql");
			}
		}
		elseif($connection instanceof OracleConnection)
		{
			$valueData = array();
			foreach ($items as $item)
			{
				list($prefix, $values) = $sqlHelper->prepareInsert($tableName, $item);
				$valueData[] = "SELECT {$values} FROM dual";
			}
			unset($item);

			$valuesSql = implode(' UNION ALL ', $valueData);
			if($valuesSql && $prefix)
			{
				$connection->queryExecute("INSERT INTO {$tableName} ({$prefix}) $valuesSql");
			}
		}
	}
}