<?php
namespace Bitrix\Catalog;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class ProductTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> QUANTITY double mandatory
 * <li> QUANTITY_TRACE bool optional default 'N'
 * <li> WEIGHT double mandatory
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> PRICE_TYPE string(1) mandatory default 'S'
 * <li> RECUR_SCHEME_LENGTH int optional
 * <li> RECUR_SCHEME_TYPE string(1) mandatory default 'D'
 * <li> TRIAL_PRICE_ID int optional
 * <li> WITHOUT_ORDER bool optional default 'N'
 * <li> SELECT_BEST_PRICE bool optional default 'Y'
 * <li> VAT_ID int optional
 * <li> VAT_INCLUDED bool optional default 'Y'
 * <li> CAN_BUY_ZERO bool optional default 'N'
 * <li> NEGATIVE_AMOUNT_TRACE string(1) mandatory default 'D'
 * <li> TMP_ID string(40) optional
 * <li> PURCHASING_PRICE double optional
 * <li> PURCHASING_CURRENCY string(3) optional
 * <li> BARCODE_MULTI bool optional default 'N'
 * <li> QUANTITY_RESERVED double optional
 * <li> SUBSCRIBE string(1) optional
 * <li> WIDTH double optional
 * <li> LENGTH double optional
 * <li> HEIGHT double optional
 * <li> MEASURE int optional
 * <li> TYPE int optional
 * <li> IBLOCK_ELEMENT reference to {@link \Bitrix\Iblock\ElementTable}
 * </ul>
 *
 * @package Bitrix\Catalog
 **/

class ProductTable extends Main\Entity\DataManager
{
	const STATUS_YES = 'Y';
	const STATUS_NO = 'N';
	const STATUS_DEFAULT = 'D';

	protected static $defaultProductSettings = array();

	protected static $existProductCache = array();

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_catalog_product';
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
				'title' => Loc::getMessage('PRODUCT_ENTITY_ID_FIELD')
			)),
			'QUANTITY' => new Main\Entity\FloatField('QUANTITY', array(
				'default_value' => 0,
				'title' => Loc::getMessage('PRODUCT_ENTITY_QUANTITY_FIELD')
			)),
			'QUANTITY_TRACE' => new Main\Entity\EnumField('QUANTITY_TRACE', array(
				'values' => array(self::STATUS_DEFAULT, self::STATUS_NO, self::STATUS_YES),
				'default_value' => self::STATUS_DEFAULT,
				'fetch_data_modification' => array(__CLASS__, 'modifyQuantityTrace'),
				'title' => Loc::getMessage('PRODUCT_ENTITY_QUANTITY_TRACE_FIELD')
			)),
			'QUANTITY_TRACE_ORIG' => new Main\Entity\ExpressionField(
				'QUANTITY_TRACE_ORIG',
				'%s',
				'QUANTITY_TRACE',
				array(
					'data_type' => 'string',
					'title' => Loc::getMessage('PRODUCT_ENTITY_QUANTITY_TRACE_ORIG_FIELD')
				)
			),
			'WEIGHT' => new Main\Entity\FloatField('WEIGHT', array(
				'default_value' => 0,
				'title' => Loc::getMessage('PRODUCT_ENTITY_WEIGHT_FIELD')
			)),
			'TIMESTAMP_X' => new Main\Entity\DatetimeField('TIMESTAMP_X', array(
				'default_value' => new Main\Type\DateTime(),
				'title' => Loc::getMessage('PRODUCT_ENTITY_TIMESTAMP_X_FIELD')
			)),
			'PRICE_TYPE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validatePriceType'),
				'title' => Loc::getMessage('PRODUCT_ENTITY_PRICE_TYPE_FIELD'),
			),
			'RECUR_SCHEME_LENGTH' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('PRODUCT_ENTITY_RECUR_SCHEME_LENGTH_FIELD'),
			),
			'RECUR_SCHEME_TYPE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateRecurSchemeType'),
				'title' => Loc::getMessage('PRODUCT_ENTITY_RECUR_SCHEME_TYPE_FIELD'),
			),
			'TRIAL_PRICE_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('PRODUCT_ENTITY_TRIAL_PRICE_ID_FIELD'),
			),
			'WITHOUT_ORDER' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('PRODUCT_ENTITY_WITHOUT_ORDER_FIELD'),
			),
			'SELECT_BEST_PRICE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('PRODUCT_ENTITY_SELECT_BEST_PRICE_FIELD'),
			),
			'VAT_ID' => new Main\Entity\IntegerField('VAT_ID', array(
				'default_value' => 0,
				'title' => Loc::getMessage('PRODUCT_ENTITY_VAT_ID_FIELD')
			)),
			'VAT_INCLUDED' => new Main\Entity\BooleanField('VAT_INCLUDED', array(
				'values' => array('N', 'Y'),
				'default_value' => 'N',
				'title' => Loc::getMessage('PRODUCT_ENTITY_VAT_INCLUDED_FIELD')
			)),
			'CAN_BUY_ZERO' => new Main\Entity\EnumField('CAN_BUY_ZERO', array(
				'values' => array(self::STATUS_DEFAULT, self::STATUS_NO, self::STATUS_YES),
				'default_value' => self::STATUS_DEFAULT,
				'fetch_data_modification' => array(__CLASS__, 'modifyCanBuyZero'),
				'title' => Loc::getMessage('PRODUCT_ENTITY_CAN_BUY_ZERO_FIELD')
			)),
			'CAN_BUY_ZERO_ORIG' => new Main\Entity\ExpressionField(
				'CAN_BUY_ZERO_ORIG',
				'%s',
				'CAN_BUY_ZERO',
				array(
					'data_type' => 'string',
					'title' => Loc::getMessage('PRODUCT_ENTITY_CAN_BUY_ZERO_ORIG_FIELD')
				)
			),
			'NEGATIVE_AMOUNT_TRACE' => new Main\Entity\EnumField('NEGATIVE_AMOUNT_TRACE', array(
				'values' => array(self::STATUS_DEFAULT, self::STATUS_NO, self::STATUS_YES),
				'default_value' => self::STATUS_DEFAULT,
				'fetch_data_modification' => array(__CLASS__, 'modifyNegativeAmountTrace'),
				'title' => Loc::getMessage('PRODUCT_ENTITY_NEGATIVE_AMOUNT_TRACE_FIELD')
			)),
			'NEGATIVE_AMOUNT_TRACE_ORIG' => new Main\Entity\ExpressionField(
				'NEGATIVE_AMOUNT_TRACE_ORIG',
				'%s',
				'NEGATIVE_AMOUNT_TRACE',
				array(
					'data_type' => 'string',
					'title' => Loc::getMessage('PRODUCT_ENTITY_NEGATIVE_AMOUNT_TRACE_ORIG_FIELD')
				)
			),
			'TMP_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateTmpId'),
				'title' => Loc::getMessage('PRODUCT_ENTITY_TMP_ID_FIELD'),
			),
			'PURCHASING_PRICE' => array(
				'data_type' => 'float',
				'title' => Loc::getMessage('PRODUCT_ENTITY_PURCHASING_PRICE_FIELD'),
			),
			'PURCHASING_CURRENCY' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validatePurchasingCurrency'),
				'title' => Loc::getMessage('PRODUCT_ENTITY_PURCHASING_CURRENCY_FIELD'),
			),
			'BARCODE_MULTI' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('PRODUCT_ENTITY_BARCODE_MULTI_FIELD'),
			),
			'QUANTITY_RESERVED' => array(
				'data_type' => 'float',
				'title' => Loc::getMessage('PRODUCT_ENTITY_QUANTITY_RESERVED_FIELD'),
			),
			'SUBSCRIBE' => new Main\Entity\EnumField('SUBSCRIBE', array(
				'values' => array(self::STATUS_DEFAULT, self::STATUS_NO, self::STATUS_YES),
				'default_value' => self::STATUS_DEFAULT,
				'fetch_data_modification' => array(__CLASS__, 'modifySubscribe'),
				'title' => Loc::getMessage('PRODUCT_ENTITY_SUBSCRIBE_FIELD'),
			)),
			'SUBSCRIBE_ORIG' => new Main\Entity\ExpressionField(
				'SUBSCRIBE_ORIG',
				'%s',
				'SUBSCRIBE',
				array(
					'data_type' => 'string',
					'title' => Loc::getMessage('PRODUCT_ENTITY_SUBSCRIBE_ORIG_FIELD')
				)
			),
			'WIDTH' => array(
				'data_type' => 'float',
				'title' => Loc::getMessage('PRODUCT_ENTITY_WIDTH_FIELD'),
			),
			'LENGTH' => array(
				'data_type' => 'float',
				'title' => Loc::getMessage('PRODUCT_ENTITY_LENGTH_FIELD'),
			),
			'HEIGHT' => array(
				'data_type' => 'float',
				'title' => Loc::getMessage('PRODUCT_ENTITY_HEIGHT_FIELD'),
			),
			'MEASURE' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('PRODUCT_ENTITY_MEASURE_FIELD'),
			),
			'TYPE' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('PRODUCT_ENTITY_TYPE_FIELD'),
			),
			'IBLOCK_ELEMENT' => new Main\Entity\ReferenceField(
				'IBLOCK_ELEMENT',
				'Bitrix\Iblock\Element',
				array('=this.ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			)
		);
	}

	/**
	 * Returns validators for PRICE_TYPE field.
	 *
	 * @return array
	 */
	public static function validatePriceType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}

	/**
	 * Returns validators for RECUR_SCHEME_TYPE field.
	 *
	 * @return array
	 */
	public static function validateRecurSchemeType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}

	/**
	 * Returns validators for TMP_ID field.
	 *
	 * @return array
	 */
	public static function validateTmpId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 40),
		);
	}
	/**
	 * Returns validators for PURCHASING_CURRENCY field.
	 *
	 * @return array
	 */
	public static function validatePurchasingCurrency()
	{
		return array(
			new Main\Entity\Validator\Length(null, 3),
		);
	}

	/**
	 * Returns fetch modificators for QUANTITY_TRACE field.
	 *
	 * @return array
	 */
	public static function modifyQuantityTrace()
	{
		return array(
			array(__CLASS__, 'prepareQuantityTrace')
		);
	}

	/**
	 * Returns fetch modificators for CAN_BUY_ZERO field.
	 *
	 * @return array
	 */
	public static function modifyCanBuyZero()
	{
		return array(
			array(__CLASS__, 'prepareCanBuyZero')
		);
	}

	/**
	 * Returns fetch modificators for NEGATIVE_AMOUNT_TRACE field.
	 *
	 * @return array
	 */
	public static function modifyNegativeAmountTrace()
	{
		return array(
			array(__CLASS__, 'prepareNegativeAmountTrace')
		);
	}

	/**
	 * Returns fetch modificators for SUBSCRIBE field.
	 *
	 * @return array
	 */
	public static function modifySubscribe()
	{
		return array(
			array(__CLASS__, 'prepareSubscribe')
		);
	}

	/**
	 * Convert default QUANTITY_TRACE into real from module settings.
	 *
	 * @param string $value			QUANTITY_TRACE original value.
	 * @return string
	 */
	public static function prepareQuantityTrace($value)
	{
		if ($value == self::STATUS_DEFAULT)
		{
			if (empty(self::$defaultProductSettings))
				self::loadDefaultProductSettings();
			return self::$defaultProductSettings['QUANTITY_TRACE'];
		}
		return $value;
	}

	/**
	 * Convert default CAN_BUY_ZERO into real from module settings.
	 *
	 * @param string $value			CAN_BUY_ZERO original value.
	 * @return string
	 */
	public static function prepareCanBuyZero($value)
	{
		if ($value == self::STATUS_DEFAULT)
		{
			if (empty(self::$defaultProductSettings))
				self::loadDefaultProductSettings();
			return self::$defaultProductSettings['CAN_BUY_ZERO'];
		}
		return $value;
	}

	/**
	 * Convert default NEGATIVE_AMOUNT_TRACE into real from module settings.
	 *
	 * @param string $value			NEGATIVE_AMOUNT_TRACE original value.
	 * @return string
	 */
	public static function prepareNegativeAmountTrace($value)
	{
		if ($value == self::STATUS_DEFAULT)
		{
			if (empty(self::$defaultProductSettings))
				self::loadDefaultProductSettings();
			return self::$defaultProductSettings['NEGATIVE_AMOUNT_TRACE'];
		}
		return $value;
	}

	/**
	 * Convert default SUBSCRIBE into real from module settings.
	 *
	 * @param string $value			SUBSCRIBE original value.
	 * @return string
	 */
	public static function prepareSubscribe($value)
	{
		if ($value == self::STATUS_DEFAULT)
		{
			if (empty(self::$defaultProductSettings))
				self::loadDefaultProductSettings();
			return self::$defaultProductSettings['SUBSCRIBE'];
		}
		return $value;
	}

	/**
	 * Return is exist product.
	 *
	 * @param int $product				Product id.
	 * @return bool
	 * @throws Main\ArgumentException
	 */
	public static function isExistProduct($product)
	{
		$product = (int)$product;
		if ($product <= 0)
			return false;
		if (!isset(self::$existProductCache[$product]))
		{
			self::$existProductCache[$product] = false;
			$existProduct = self::getList(array(
				'select' => array('ID'),
				'filter' => array('=ID' => $product)
			))->fetch();
			if (!empty($existProduct))
				self::$existProductCache[$product] = true;
			unset($existProduct);
		}
		return self::$existProductCache[$product];
	}

	/**
	 * Clear product cache.
	 *
	 * @param int $product			Product id or zero (clear all cache).
	 * @return void
	 */
	public static function clearProductCache($product = 0)
	{
		$product = (int)$product;
		if ($product > 0)
		{
			if (isset(self::$existProductCache[$product]))
				unset(self::$existProductCache[$product]);
		}
		else
		{
			self::$existProductCache = array();
		}
	}

	/**
	 * Returns ratio and measure for products.
	 *
	 * @param array|int $product				Product ids.
	 * @return array|bool
	 * @throws Main\ArgumentException
	 */
	public static function getCurrentRatioWithMeasure($product)
	{
		if (!is_array($product))
			$product = array($product);
		Main\Type\Collection::normalizeArrayValuesByInt($product, true);
		if (empty($product))
			return false;

		$result = array();

		$defaultMeasure = \CCatalogMeasure::getDefaultMeasure(true, true);
		$defaultRow = array(
			'RATIO' => 1,
			'MEASURE' => (!empty($defaultMeasure) ? $defaultMeasure : array())
		);
		$existProduct = array();
		$measureMap = array();

		$productRows = array_chunk($product, 500);
		foreach ($productRows as &$row)
		{
			$productIterator = self::getList(array(
				'select' => array('ID', 'MEASURE'),
				'filter' => array('@ID' => $row),
				'order' => array('ID' => 'ASC')
			));
			while ($item = $productIterator->fetch())
			{
				$item['ID'] = (int)$item['ID'];
				$item['MEASURE'] = (int)$item['MEASURE'];
				self::$existProductCache[$item['ID']] = true;
				$existProduct[] = $item['ID'];
				$result[$item['ID']] = $defaultRow;
				if ($item['MEASURE'] > 0)
				{
					if (!isset($measureMap[$item['MEASURE']]))
						$measureMap[$item['MEASURE']] = array();
					$measureMap[$item['MEASURE']][] = &$result[$item['ID']];
				}
			}
			unset($item, $productIterator);
		}
		unset($row, $productRows);
		unset($defaultRow, $defaultMeasure);
		if (empty($existProduct))
			return false;

		$ratioResult = MeasureRatioTable::getCurrentRatio($existProduct);
		if (!empty($ratioResult))
		{
			foreach ($ratioResult as $ratioProduct => $ratio)
				$result[$ratioProduct]['RATIO'] = $ratio;
			unset($ratio, $ratioProduct);
		}
		unset($ratioResult);
		unset($existProduct);

		if (!empty($measureMap))
		{
			$measureIterator = \CCatalogMeasure::getList(
				array(),
				array('@ID' => array_keys($measureMap)),
				false,
				false,
				array()
			);
			while ($measure = $measureIterator->GetNext())
			{
				$measure['ID'] = (int)$measure['ID'];
				if (empty($measureMap[$measure['ID']]))
					continue;

				foreach ($measureMap[$measure['ID']] as &$product)
					$product['MEASURE'] = $measure;
				unset($product);
			}
			unset($measure, $measureIterator);
		}
		unset($measureMap);

		return $result;
	}

	/**
	 * Load default product settings from module options.
	 *
	 * @return void
	 */
	protected static function loadDefaultProductSettings()
	{
		self::$defaultProductSettings = array(
			'QUANTITY_TRACE' => ((string)Main\Config\Option::get('catalog', 'default_quantity_trace') == 'Y' ? 'Y' : 'N'),
			'CAN_BUY_ZERO' => ((string)Main\Config\Option::get('catalog', 'default_can_buy_zero') == 'Y' ? 'Y' : 'N'),
			'NEGATIVE_AMOUNT_TRACE' => ((string)Main\Config\Option::get('catalog', 'allow_negative_amount') == 'Y' ? 'Y' : 'N'),
			'SUBSCRIBE' => ((string)Main\Config\Option::get('catalog', 'default_subscribe') == 'N' ? 'N' : 'Y')
		);
	}
}