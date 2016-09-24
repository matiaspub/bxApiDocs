<?php
namespace Bitrix\Sale\TradingPlatform;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class OrderTable
 * Links external order id with internal order id.
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ORDER_ID int mandatory
 * <li> TRADING_PLATFORM_ID int mandatory
 * <li> EXTERNAL_ORDER_ID string(100) mandatory
 * <li> EXTERNAL_ORDER \Bitrix\Sale\TradingPlatform optional
 * </ul>
 *
 * @package Bitrix\Sale\TradingPlatform
 **/

class OrderTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_tp_order';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_ORDER_ENTITY_ID_FIELD'),
			),
			'ORDER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_ORDER_ENTITY_ORDER_ID_FIELD'),
			),
			'ORDER' => array(
				'data_type' => '\Bitrix\Sale\Internals\OrderTable',
				'reference' => array('=this.ORDER_ID' => 'ref.ID'),
				'title' => Loc::getMessage('TRADING_PLATFORM_ORDER_ENTITY_ORDER_FIELD')
			),
			'EXTERNAL_ORDER_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateExternalOrderId'),
				'title' => Loc::getMessage('TRADING_PLATFORM_ORDER_ENTITY_EXTERNAL_ORDER_ID_FIELD'),
			),
			'PARAMS' => array(
				'data_type' => 'string',
				'required' => false,
				'serialized' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_ORDER_ENTITY_EXTERNAL_ORDER_LINES_FIELD'),
			),
			'TRADING_PLATFORM_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_ORDER_ENTITY_TRADING_PLATFORM_ID_FIELD'),
			),
			'TRADING_PLATFORM' => array(
				'data_type' => '\Bitrix\Sale\TradingPlatform',
				'reference' => array('=this.TRADING_PLATFORM_ID' => 'ref.ID'),
				'title' => Loc::getMessage('TRADING_PLATFORM_ORDER_ENTITY_TRADING_PLATFORM_FIELD'),
			));
	}
	public static function validateExternalOrderId()
	{
		return array(
			new Entity\Validator\Length(null, 100),
		);
	}
}