<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class DeliveryServiceTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> NAME string(255) mandatory
 * <li> LID string(2) mandatory
 * <li> PERIOD_FROM int optional
 * <li> PERIOD_TO int optional
 * <li> PERIOD_TYPE string(1) optional
 * <li> WEIGHT_FROM int optional
 * <li> WEIGHT_TO int optional
 * <li> ORDER_PRICE_FROM unknown optional
 * <li> ORDER_PRICE_TO unknown optional
 * <li> ORDER_CURRENCY string(3) optional
 * <li> ACTIVE bool optional default 'Y'
 * <li> PRICE unknown mandatory
 * <li> CURRENCY string(3) mandatory
 * <li> SORT int optional default 100
 * <li> DESCRIPTION string optional
 * <li> LOGOTIP int optional
 * <li> STORE string optional
 * </ul>
 *
 * @package Bitrix\Sale
 **/

class DeliveryServiceTable extends Main\Entity\DataManager
{
	/**
	 * Returns path to the file which contains definition of the class.
	 *
	 * @return string
	 */
	public static function getFilePath()
	{
		return __FILE__;
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sale_delivery_srv';
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
				'title' => Loc::getMessage('DELIVERY_ENTITY_ID_FIELD'),
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('DELIVERY_ENTITY_NAME_FIELD'),
			),
//			'LID' => array(
//				'data_type' => 'string',
//				'required' => true,
//				'validation' => array(__CLASS__, 'validateLid'),
//				'title' => Loc::getMessage('DELIVERY_ENTITY_LID_FIELD'),
//			),
//			'PERIOD_FROM' => array(
//				'data_type' => 'integer',
//				'title' => Loc::getMessage('DELIVERY_ENTITY_PERIOD_FROM_FIELD'),
//			),
//			'PERIOD_TO' => array(
//				'data_type' => 'integer',
//				'title' => Loc::getMessage('DELIVERY_ENTITY_PERIOD_TO_FIELD'),
//			),
//			'PERIOD_TYPE' => array(
//				'data_type' => 'string',
//				'validation' => array(__CLASS__, 'validatePeriodType'),
//				'title' => Loc::getMessage('DELIVERY_ENTITY_PERIOD_TYPE_FIELD'),
//			),
//			'WEIGHT_FROM' => array(
//				'data_type' => 'integer',
//				'title' => Loc::getMessage('DELIVERY_ENTITY_WEIGHT_FROM_FIELD'),
//			),
//			'WEIGHT_TO' => array(
//				'data_type' => 'integer',
//				'title' => Loc::getMessage('DELIVERY_ENTITY_WEIGHT_TO_FIELD'),
//			),
//			'ORDER_PRICE_FROM' => array(
//				'data_type' => 'float',
//				'title' => Loc::getMessage('DELIVERY_ENTITY_ORDER_PRICE_FROM_FIELD'),
//			),
//			'ORDER_PRICE_TO' => array(
//				'data_type' => 'float',
//				'title' => Loc::getMessage('DELIVERY_ENTITY_ORDER_PRICE_TO_FIELD'),
//			),
//			'ORDER_CURRENCY' => array(
//				'data_type' => 'string',
//				'validation' => array(__CLASS__, 'validateOrderCurrency'),
//				'title' => Loc::getMessage('DELIVERY_ENTITY_ORDER_CURRENCY_FIELD'),
//			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('DELIVERY_ENTITY_ACTIVE_FIELD'),
			),
//			'PRICE' => array(
//				'data_type' => 'float',
//				'required' => true,
//				'title' => Loc::getMessage('DELIVERY_ENTITY_PRICE_FIELD'),
//			),
			'CURRENCY' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateCurrency'),
				'title' => Loc::getMessage('DELIVERY_ENTITY_CURRENCY_FIELD'),
			),
			'SORT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('DELIVERY_ENTITY_SORT_FIELD'),
			),
			'DESCRIPTION' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('DELIVERY_ENTITY_DESCRIPTION_FIELD'),
			),
			'LOGOTIP' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('DELIVERY_ENTITY_LOGOTIP_FIELD'),
			),
//			'STORE' => array(
//				'data_type' => 'text',
//				'title' => Loc::getMessage('DELIVERY_ENTITY_STORE_FIELD'),
//			),
		);
	}
	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for LID field.
	 *
	 * @return array
	 */
	public static function validateLid()
	{
		return array(
			new Entity\Validator\Length(null, 2),
		);
	}
	/**
	 * Returns validators for PERIOD_TYPE field.
	 *
	 * @return array
	 */
	public static function validatePeriodType()
	{
		return array(
			new Entity\Validator\Length(null, 1),
		);
	}
	/**
	 * Returns validators for ORDER_CURRENCY field.
	 *
	 * @return array
	 */
	public static function validateOrderCurrency()
	{
		return array(
			new Entity\Validator\Length(null, 3),
		);
	}
	/**
	 * Returns validators for CURRENCY field.
	 *
	 * @return array
	 */
	public static function validateCurrency()
	{
		return array(
			new Entity\Validator\Length(null, 3),
		);
	}
}