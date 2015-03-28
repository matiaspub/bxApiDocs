<?php
namespace Bitrix\Crm;
use Bitrix\Main;
class Measure
{
	private static $DEFAULT_MEASURE = null;
	private static $IS_DEFAULT_MEASURE_LOADED = false;

	public static function getProductMeasures($productID)
	{
		if (!Main\Loader::includeModule('catalog'))
		{
			throw new Main\SystemException("Could not load 'catalog' module.");
		}

		$productIDs = is_array($productID) ? $productID : array($productID);

		$measure2product = array();
		if (!empty($productIDs))
		{
			$productEntity = new \CCatalogProduct();
			$dbProductResult = $productEntity->GetList(array(), array('@ID' => $productIDs), false, false, array('ID', 'MEASURE'));
			if(is_object($dbProductResult))
			{
				while($productFields = $dbProductResult->Fetch())
				{
					$measureID = isset($productFields['MEASURE'])  ? intval($productFields['MEASURE']) : 0;
					if($measureID <= 0)
					{
						continue;
					}

					if(isset($measure2product[$measureID]))
					{
						$measure2product[$measureID] = array();
					}

					$measure2product[$measureID][] =  intval($productFields['ID']);
				}
			}
		}
		$result = array();

		if(!empty($measure2product))
		{
			$dbMeasureResult = \CCatalogMeasure::getList(
				array(),
				array('@ID' => array_keys($measure2product)),
				false,
				false,
				array('ID', 'CODE', 'SYMBOL_RUS', 'SYMBOL_INTL', 'IS_DEFAULT')
			);

			if(is_object($dbMeasureResult))
			{
				while($measureFields = $dbMeasureResult->Fetch())
				{
					$measureID = intval($measureFields['ID']);
					$measureInfo = array(
						'ID' => $measureID,
						'CODE' => intval($measureFields['CODE']),
						'IS_DEFAULT' => isset($measureFields['IS_DEFAULT']) && $measureFields['IS_DEFAULT'] === 'Y',
						'SYMBOL' => isset($measureFields['SYMBOL_RUS'])
							? $measureFields['SYMBOL_RUS'] : $measureFields['SYMBOL_INTL']
					);

					foreach($measure2product[$measureID] as $productID)
					{
						$result[$productID] = array($measureInfo);
					}
				}
			}
		}

		return $result;
	}
	public static function getDefaultMeasure()
	{
		if(self::$IS_DEFAULT_MEASURE_LOADED)
		{
			return self::$DEFAULT_MEASURE;
		}

		if (!Main\Loader::includeModule('catalog'))
		{
			throw new Main\SystemException("Could not load 'catalog' module.");
		}

		self::$IS_DEFAULT_MEASURE_LOADED = true;

		$measureFields = \CCatalogMeasure::getDefaultMeasure(true, false);
		if(!is_array($measureFields))
		{
			return null;
		}

		return (
			self::$DEFAULT_MEASURE = array(
				'ID' => intval($measureFields['ID']),
				'CODE' => intval($measureFields['CODE']),
				'IS_DEFAULT' => isset($measureFields['IS_DEFAULT']) && $measureFields['IS_DEFAULT'] === 'Y',
				'SYMBOL' => isset($measureFields['SYMBOL_RUS'])
					? $measureFields['SYMBOL_RUS'] : $measureFields['SYMBOL_INTL']
			)
		);
	}
	public static function getMeasures($top = 0)
	{
		if (!Main\Loader::includeModule('catalog'))
		{
			throw new Main\SystemException("Could not load 'catalog' module.");
		}

		$top = intval($top);
		$dbMeasureResult = \CCatalogMeasure::getList(
			array('CODE' => 'ASC'),
			array(),
			false,
			($top > 0 ? array('nTopCount' => $top) : false),
			array('ID', 'CODE', 'SYMBOL_RUS', 'SYMBOL_INTL', 'IS_DEFAULT')
		);

		if(!is_object($dbMeasureResult))
		{
			return array();
		}

		$result = array();
		while($measureFields = $dbMeasureResult->Fetch())
		{
			$result[] = array(
				'ID' => intval($measureFields['ID']),
				'CODE' => intval($measureFields['CODE']),
				'IS_DEFAULT' => isset($measureFields['IS_DEFAULT']) && $measureFields['IS_DEFAULT'] === 'Y',
				'SYMBOL' => isset($measureFields['SYMBOL_RUS'])
					? $measureFields['SYMBOL_RUS'] : $measureFields['SYMBOL_INTL']
			);
		}
		return $result;
	}
	public static function getMeasureByCode($code)
	{
		if (!Main\Loader::includeModule('catalog'))
		{
			throw new Main\SystemException("Could not load 'catalog' module.");
		}

		$dbMeasureResult = \CCatalogMeasure::getList(
			array(),
			array('=CODE' => $code),
			false,
			false,
			array('ID', 'CODE', 'SYMBOL_RUS', 'SYMBOL_INTL', 'IS_DEFAULT')
		);

		$measureFields = is_object($dbMeasureResult) ? $dbMeasureResult->Fetch() : null;
		if(!is_array($measureFields))
		{
			return null;
		}

		return array(
			'ID' => intval($measureFields['ID']),
			'CODE' => intval($measureFields['CODE']),
			'IS_DEFAULT' => isset($measureFields['IS_DEFAULT']) && $measureFields['IS_DEFAULT'] === 'Y',
			'SYMBOL' => isset($measureFields['SYMBOL_RUS'])
				? $measureFields['SYMBOL_RUS'] : $measureFields['SYMBOL_INTL']
		);
	}
}