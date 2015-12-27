<?php
namespace Bitrix\Catalog;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class MeasureRatioTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> PRODUCT_ID int mandatory
 * <li> RATIO double mandatory default 1
 * </ul>
 *
 * @package Bitrix\Catalog
 **/

class MeasureRatioTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_catalog_measure_ratio';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('MEASURE_RATIO_ENTITY_ID_FIELD')
			)),
			'PRODUCT_ID' => new Main\Entity\IntegerField('PRODUCT_ID', array(
				'required' => true,
				'title' => Loc::getMessage('MEASURE_RATIO_ENTITY_PRODUCT_ID_FIELD')
			)),
			'RATIO' => new Main\Entity\FloatField('RATIO', array(
				'required' => true,
				'title' => Loc::getMessage('MEASURE_RATIO_ENTITY_RATIO_FIELD')
			))
		);
	}

	/**
	 * Return ratio for product list.
	 *
	 * @param array|int $product			Product id list.
	 * @return array|bool
	 * @throws Main\ArgumentException
	 */
	public static function getCurrentRatio($product)
	{
		if (!is_array($product))
			$product = array($product);
		Main\Type\Collection::normalizeArrayValuesByInt($product, true);
		if (empty($product))
			return false;

		$result = array_fill_keys($product, 1);
		$ratioRows = array_chunk($product, 500);
		foreach ($ratioRows as &$row)
		{
			$ratioIterator = self::getList(array(
				'select' => array('PRODUCT_ID', 'RATIO'),
				'filter' => array('@PRODUCT_ID' => $row)
			));
			while ($ratio = $ratioIterator->fetch())
			{
				$ratio['PRODUCT_ID'] = (int)$ratio['PRODUCT_ID'];
				$ratioInt = (int)$ratio['RATIO'];
				$ratioFloat = (float)$ratio['RATIO'];
				$ratioResult  = ($ratioFloat > $ratioInt ? $ratioFloat : $ratioInt);
				if (abs($ratioResult) < CATALOG_VALUE_EPSILON || $ratioResult < 0)
					continue;
				$result[$ratio['PRODUCT_ID']] = $ratioResult;
			}
			unset($module, $moduleIterator);
		}
		unset($row, $ratioRows);
		return $result;
	}
}