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
 * <li> AVAILABLE string(1) optional
 * <li> BUNDLE string(1) optional
 * <li> IBLOCK_ELEMENT reference to {@link \Bitrix\Iblock\ElementTable}
 * <li> TRIAL_IBLOCK_ELEMENT reference to {@link \Bitrix\Iblock\ElementTable}
 * <li> TRIAL_PRODUCT reference to {@link \Bitrix\Catalog\ProductTable}
 * </ul>
 *
 * @package Bitrix\Catalog
 **/

class ProductTable extends Main\Entity\DataManager
{
	const STATUS_YES = 'Y';
	const STATUS_NO = 'N';
	const STATUS_DEFAULT = 'D';

	const TYPE_PRODUCT = 1;
	const TYPE_SET = 2;
	const TYPE_SKU = 3;
	const TYPE_OFFER = 4;
	const TYPE_FREE_OFFER = 5;
	const TYPE_EMPTY_SKU = 6;

	const PAYMENT_TYPE_SINGLE = 'S';
	const PAYMENT_TYPE_REGULAR = 'R';
	const PAYMENT_TYPE_TRIAL = 'T';

	const PAYMENT_PERIOD_HOUR = 'H';
	const PAYMENT_PERIOD_DAY = 'D';
	const PAYMENT_PERIOD_WEEK = 'W';
	const PAYMENT_PERIOD_MONTH = 'M';
	const PAYMENT_PERIOD_QUART = 'Q';
	const PAYMENT_PERIOD_SEMIYEAR = 'S';
	const PAYMENT_PERIOD_YEAR = 'Y';
	const PAYMENT_PERIOD_DOUBLE_YEAR = 'T';

	protected static $defaultProductSettings = array();

	protected static $existProductCache = array();

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает название таблицы товаров торговых каталогов. Статический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/producttable/gettablename.php
	* @author Bitrix
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
	
	/**
	* <p>Метод возвращает список полей для таблицы товаров торговых каталогов. Статический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/producttable/getmap.php
	* @author Bitrix
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
					'data_type' => 'string'
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
			'PRICE_TYPE' => new Main\Entity\EnumField('PRICE_TYPE', array(
				'values' => array(self::PAYMENT_TYPE_SINGLE, self::PAYMENT_TYPE_REGULAR, self::PAYMENT_TYPE_TRIAL),
				'default_value' => self::PAYMENT_TYPE_SINGLE,
				'title' => Loc::getMessage('PRODUCT_ENTITY_PRICE_TYPE_FIELD')
			)),
			'RECUR_SCHEME_LENGTH' => new Main\Entity\IntegerField('RECUR_SCHEME_LENGTH', array(
				'default_value' => 0,
				'title' => Loc::getMessage('PRODUCT_ENTITY_RECUR_SCHEME_LENGTH_FIELD')
			)),
			'RECUR_SCHEME_TYPE' => new Main\Entity\EnumField('RECUR_SCHEME_TYPE', array(
				'values' => array(
					self::PAYMENT_PERIOD_HOUR,
					self::PAYMENT_PERIOD_DAY,
					self::PAYMENT_PERIOD_WEEK,
					self::PAYMENT_PERIOD_MONTH,
					self::PAYMENT_PERIOD_QUART,
					self::PAYMENT_PERIOD_SEMIYEAR,
					self::PAYMENT_PERIOD_YEAR,
					self::PAYMENT_PERIOD_DOUBLE_YEAR
				),
				'default_value' => self::PAYMENT_PERIOD_DAY,
				'title' => Loc::getMessage('PRODUCT_ENTITY_RECUR_SCHEME_TYPE_FIELD')
			)),
			'TRIAL_PRICE_ID' => new Main\Entity\IntegerField('TRIAL_PRICE_ID', array(
				'title' => Loc::getMessage('PRODUCT_ENTITY_TRIAL_PRICE_ID_FIELD')
			)),
			'WITHOUT_ORDER' => new Main\Entity\BooleanField('WITHOUT_ORDER', array(
				'values' => array(self::STATUS_NO, self::STATUS_YES),
				'default_value' => self::STATUS_NO,
				'title' => Loc::getMessage('PRODUCT_ENTITY_WITHOUT_ORDER_FIELD'),
			)),
			'SELECT_BEST_PRICE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'VAT_ID' => new Main\Entity\IntegerField('VAT_ID', array(
				'default_value' => 0,
				'title' => Loc::getMessage('PRODUCT_ENTITY_VAT_ID_FIELD')
			)),
			'VAT_INCLUDED' => new Main\Entity\BooleanField('VAT_INCLUDED', array(
				'values' => array(self::STATUS_NO, self::STATUS_YES),
				'default_value' => self::STATUS_NO,
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
					'data_type' => 'string'
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
					'data_type' => 'string'
				)
			),
			'TMP_ID' => New Main\Entity\StringField('TMP_ID' ,array(
				'validation' => array(__CLASS__, 'validateTmpId'),
				'title' => Loc::getMessage('PRODUCT_ENTITY_TMP_ID_FIELD')
			)),
			'PURCHASING_PRICE' => new Main\Entity\FloatField('PURCHASING_PRICE', array(
				'title' => Loc::getMessage('PRODUCT_ENTITY_PURCHASING_PRICE_FIELD')
			)),
			'PURCHASING_CURRENCY' => new Main\Entity\StringField('PURCHASING_CURRENCY', array(
				'validation' => array(__CLASS__, 'validatePurchasingCurrency'),
				'title' => Loc::getMessage('PRODUCT_ENTITY_PURCHASING_CURRENCY_FIELD')
			)),
			'BARCODE_MULTI' => new Main\Entity\BooleanField('BARCODE_MULTI', array(
				'values' => array(self::STATUS_NO, self::STATUS_YES),
				'default_value' => self::STATUS_NO,
				'title' => Loc::getMessage('PRODUCT_ENTITY_BARCODE_MULTI_FIELD')
			)),
			'QUANTITY_RESERVED' => new Main\Entity\FloatField('QUANTITY_RESERVED', array(
				'title' => Loc::getMessage('PRODUCT_ENTITY_QUANTITY_RESERVED_FIELD')
			)),
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
					'data_type' => 'string'
				)
			),
			'WIDTH' => new Main\Entity\FloatField('WIDTH', array(
				'title' => Loc::getMessage('PRODUCT_ENTITY_WIDTH_FIELD')
			)),
			'LENGTH' => new Main\Entity\FloatField('LENGTH', array(
				'title' => Loc::getMessage('PRODUCT_ENTITY_LENGTH_FIELD')
			)),
			'HEIGHT' => new Main\Entity\FloatField('HEIGHT', array(
				'title' => Loc::getMessage('PRODUCT_ENTITY_HEIGHT_FIELD')
			)),
			'MEASURE' => new Main\Entity\IntegerField('MEASURE', array(
				'title' => Loc::getMessage('PRODUCT_ENTITY_MEASURE_FIELD')
			)),
			'TYPE' => new Main\Entity\EnumField('TYPE', array(
				'values' => array(self::TYPE_PRODUCT, self::TYPE_SET, self::TYPE_SKU, self::TYPE_OFFER, self::TYPE_FREE_OFFER, self::TYPE_EMPTY_SKU),
				'default_value' => self::TYPE_PRODUCT,
				'title' => Loc::getMessage('PRODUCT_ENTITY_TYPE_FIELD')
			)),
			'AVAILABLE' => new Main\Entity\BooleanField('AVAILABLE', array(
				'values' => array(self::STATUS_NO, self::STATUS_YES),
				'title' => Loc::getMessage('PRODUCT_ENTITY_AVAILABLE_FIELD')
			)),
			'BUNDLE' => new Main\Entity\BooleanField('BUNDLE', array(
				'values' => array(self::STATUS_NO, self::STATUS_YES),
				'title' => Loc::getMessage('PRODUCT_ENTITY_BUNDLE_FIELD')
			)),
			'IBLOCK_ELEMENT' => new Main\Entity\ReferenceField(
				'IBLOCK_ELEMENT',
				'Bitrix\Iblock\Element',
				array('=this.ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
			'TRIAL_IBLOCK_ELEMENT' => new Main\Entity\ReferenceField(
				'TRIAL_IBLOCK_ELEMENT',
				'Bitrix\Iblock\Element',
				array('=this.TRIAL_PRICE_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
			'TRIAL_PRODUCT' => new Main\Entity\ReferenceField(
				'TRIAL_PRODUCT',
				'Bitrix\Catalog\Product',
				array('=this.TRIAL_PRICE_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			)
		);
	}

	/**
	 * Returns validators for PRICE_TYPE field.
	 *
	 * @deprecated deprecated since catalog 16.5.0 - no longer needed.
	 * @internal
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>PRICE_TYPE</code> (тип цены). Статический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/producttable/validatepricetype.php
	* @author Bitrix
	* @deprecated deprecated since catalog 16.5.0 - no longer needed.
	*/
	public static function validatePriceType()
	{
		return array();
	}

	/**
	 * Returns validators for RECUR_SCHEME_TYPE field.
	 *
	 * @deprecated deprecated since catalog 16.5.0 - no longer needed.
	 * @internal
	 * @return array
	 */
	public static function validateRecurSchemeType()
	{
		return array();
	}

	/**
	 * Returns validators for TMP_ID field.
	 *
	 * @internal
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>TMP_ID</code> (временный символьный идентификатор, используемый для служебных целей). Статический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/producttable/validatetmpid.php
	* @author Bitrix
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
	 * @internal
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>PURCHASING_CURRENCY</code> (валюта закупочной цены). Статический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/producttable/validatepurchasingcurrency.php
	* @author Bitrix
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
	 * @internal
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
	 * @internal
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
	 * @internal
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
	 * @internal
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
	 * @internal
	 * @param string $value			QUANTITY_TRACE original value.
	 * @return string
	 */
	
	/**
	* <p>Метод получает значение поля <code>QUANTITY_TRACE</code> (включить количественный учет) и, если оно равно <code>D</code>, то вместо текущего значения возвращает настройку поля <code>QUANTITY_TRACE</code> из модуля. Является служебным статическим методом.</p>
	*
	*
	* @param string $value  Значение поля <code>QUANTITY_TRACE</code>.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/producttable/preparequantitytrace.php
	* @author Bitrix
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
	 * @internal
	 * @param string $value			CAN_BUY_ZERO original value.
	 * @return string
	 */
	
	/**
	* <p>Метод получает значение поля <code>CAN_BUY_ZERO</code> (разрешение покупки при отсутствии) и, если оно равно <code>D</code>, то вместо текущего значения возвращает настройку поля <code>CAN_BUY_ZERO</code> из модуля. Является служебным статическим методом.</p>
	*
	*
	* @param string $value  Значение поля <code>CAN_BUY_ZERO</code>.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/producttable/preparecanbuyzero.php
	* @author Bitrix
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
	 * @internal
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
	 * @internal
	 * @param string $value			SUBSCRIBE original value.
	 * @return string
	 */
	
	/**
	* <p>Метод получает значение поля <code>SUBSCRIBE</code> (разрешение подписки) и, если оно равно <code>D</code>, то вместо текущего значения возвращает настройку поля <code>SUBSCRIBE</code> из модуля. Является служебным статическим методом.</p>
	*
	*
	* @param string $value  Значение поля <code>SUBSCRIBE</code>.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/producttable/preparesubscribe.php
	* @author Bitrix
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
	
	/**
	* <p>Метод проверяет наличие информации о товаре с кодом <code>$product</code>. Метод статический.</p>
	*
	*
	* @param integer $product  Идентификатор товара.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/producttable/isexistproduct.php
	* @author Bitrix
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
	
	/**
	* <p>Метод сбрасывает внутренний кеш на хите (в рамках самого класса). Метод статический.</p>
	*
	*
	* @param integer $product  Идентификатор товара или ноль, если следует сбросить кеш всех
	* товаров.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/producttable/clearproductcache.php
	* @author Bitrix
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
	
	/**
	* <p>Метод возвращает коэффициент и код единиц измерения для товаров. Метод статический.</p>
	*
	*
	* @param array $array  Идентификаторы товаров.
	*
	* @param integer $product  
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/producttable/getcurrentratiowithmeasure.php
	* @author Bitrix
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
			while ($measure = $measureIterator->getNext())
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
	 * Calculate available for product.
	 *
	 * @param array $fields					Product data.
	 * @return string
	 * @throws Main\ArgumentNullException
	 */
	
	/**
	* <p>Метод возвращает флаг Y/N доступности товара к покупке для переданного массива товара. Метод статический.</p>
	*
	*
	* @param array $fields  Массив полей товара. В массиве обязательно должны быть заданы
	* следующие ключи: <code>QUANTITY</code>, <code>QUANTITY_TRACE</code> и <code>CAN_BUY_ZERO</code>.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/producttable/calculateavailable.php
	* @author Bitrix
	*/
	public static function calculateAvailable($fields)
	{
		if (empty($fields) || !is_array($fields))
			return self::STATUS_NO;

		if (isset($fields['QUANTITY']) && isset($fields['QUANTITY_TRACE']) && isset($fields['CAN_BUY_ZERO']))
		{
			if (empty(self::$defaultProductSettings))
				self::loadDefaultProductSettings();
			if ($fields['QUANTITY_TRACE'] == self::STATUS_DEFAULT)
				$fields['QUANTITY_TRACE'] = self::$defaultProductSettings['QUANTITY_TRACE'];
			if ($fields['CAN_BUY_ZERO'] == self::STATUS_DEFAULT)
				$fields['CAN_BUY_ZERO'] = self::$defaultProductSettings['CAN_BUY_ZERO'];
			return (
				(
					(float)$fields['QUANTITY'] <= 0
					&& $fields['QUANTITY_TRACE'] == self::STATUS_YES
					&& $fields['CAN_BUY_ZERO'] == self::STATUS_NO
				)
				? self::STATUS_NO
				: self::STATUS_YES
			);
		}

		return self::STATUS_NO;
	}

	/**
	 * Load default product settings from module options.
	 *
	 * @internal
	 * @return void
	 */
	
	/**
	* <p>Метод выбирает значения параметров товаров по умолчанию из настроек модуля. Статический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/producttable/loaddefaultproductsettings.php
	* @author Bitrix
	*/
	public static function loadDefaultProductSettings()
	{
		self::$defaultProductSettings = array(
			'QUANTITY_TRACE' => ((string)Main\Config\Option::get('catalog', 'default_quantity_trace') == 'Y' ? 'Y' : 'N'),
			'CAN_BUY_ZERO' => ((string)Main\Config\Option::get('catalog', 'default_can_buy_zero') == 'Y' ? 'Y' : 'N'),
			'NEGATIVE_AMOUNT_TRACE' => ((string)Main\Config\Option::get('catalog', 'allow_negative_amount') == 'Y' ? 'Y' : 'N'),
			'SUBSCRIBE' => ((string)Main\Config\Option::get('catalog', 'default_subscribe') == 'N' ? 'N' : 'Y')
		);
	}

	/**
	 * Return product type list.
	 *
	 * @param bool $descr			With description.
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает список типов товаров. Метод статический.</p>
	*
	*
	* @param boolean $withDescr = false Если параметр принимает значение <i>true</i>, то список будет
	* возвращен с описанием.
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/producttable/getproducttypes.php
	* @author Bitrix
	*/
	public static function getProductTypes($descr = false)
	{
		if ($descr)
		{
			return array(
				self::TYPE_PRODUCT => Loc::getMessage('PRODUCT_ENTITY_TYPE_PRODUCT'),
				self::TYPE_SET => Loc::getMessage('PRODUCT_ENTITY_TYPE_SET'),
				self::TYPE_SKU => Loc::getMessage('PRODUCT_ENTITY_TYPE_SKU'),
				self::TYPE_OFFER => Loc::getMessage('PRODUCT_ENTITY_TYPE_OFFER'),
				self::TYPE_FREE_OFFER => Loc::getMessage('PRODUCT_ENTITY_TYPE_FREE_OFFER')
			);
		}
		return array(
			self::TYPE_PRODUCT,
			self::TYPE_SET,
			self::TYPE_SKU,
			self::TYPE_OFFER,
			self::TYPE_FREE_OFFER
		);
	}

	/**
	 * Return payment type list.
	 *
	 * @param bool $descr			With description.
	 * @return array
	 */
	public static function getPaymentTypes($descr = false)
	{
		if ($descr)
		{
			return array(
				self::PAYMENT_TYPE_SINGLE => Loc::getMessage('PRODUCT_ENTITY_PAYMENT_TYPE_SINGLE'),
				self::PAYMENT_TYPE_REGULAR => Loc::getMessage('PRODUCT_ENTITY_PAYMENT_TYPE_REGULAR'),
				self::PAYMENT_TYPE_TRIAL => Loc::getMessage('PRODUCT_ENTITY_PAYMENT_TYPE_TRIAL')
			);
		}
		return array(
			self::PAYMENT_TYPE_SINGLE,
			self::PAYMENT_TYPE_REGULAR,
			self::PAYMENT_TYPE_TRIAL
		);
	}

	/**
	 * Return payment period list.
	 *
	 * @param bool $descr			With description.
	 * @return array
	 */
	public static function getPaymentPeriods($descr = false)
	{
		if ($descr)
		{
			return array(
				self::PAYMENT_PERIOD_HOUR => Loc::getMessage('PRODUCT_ENTITY_PAYMENT_PERIOD_HOUR'),
				self::PAYMENT_PERIOD_DAY => Loc::getMessage('PRODUCT_ENTITY_PAYMENT_PERIOD_DAY'),
				self::PAYMENT_PERIOD_WEEK => Loc::getMessage('PRODUCT_ENTITY_PAYMENT_PERIOD_WEEK'),
				self::PAYMENT_PERIOD_MONTH => Loc::getMessage('PRODUCT_ENTITY_PAYMENT_PERIOD_MONTH'),
				self::PAYMENT_PERIOD_QUART => Loc::getMessage('PRODUCT_ENTITY_PAYMENT_PERIOD_QUART'),
				self::PAYMENT_PERIOD_SEMIYEAR => Loc::getMessage('PRODUCT_ENTITY_PAYMENT_PERIOD_SEMIYEAR'),
				self::PAYMENT_PERIOD_YEAR => Loc::getMessage('PRODUCT_ENTITY_PAYMENT_PERIOD_YEAR')
			);
		}
		return array(
			self::PAYMENT_PERIOD_HOUR,
			self::PAYMENT_PERIOD_DAY,
			self::PAYMENT_PERIOD_WEEK,
			self::PAYMENT_PERIOD_MONTH,
			self::PAYMENT_PERIOD_QUART,
			self::PAYMENT_PERIOD_SEMIYEAR,
			self::PAYMENT_PERIOD_YEAR
		);
	}

	/**
	 * Return default alailable settings.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает доступные по умолчанию настройки. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/producttable/getdefaultavailablesettings.php
	* @author Bitrix
	*/
	public static function getDefaultAvailableSettings()
	{
		return array(
			'AVAILABLE' => self::STATUS_NO,
			'QUANTITY' => 0,
			'QUANTITY_TRACE' => self::STATUS_YES,
			'CAN_BUY_ZERO' => self::STATUS_NO
		);
	}
}