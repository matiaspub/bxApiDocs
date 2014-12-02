<?php
namespace Bitrix\Sale\Delivery;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class OrderDeliveryTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ORDER_ID int mandatory
 * <li> DELIVERY_LOCATION string(50) optional
 * <li> DATE_REQUEST datetime optional
 * <li> PARAMS string optional
 * </ul>
 *
 * @package Bitrix\Sale\Delivery
 **/

class OrderDeliveryTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_sale_order_delivery';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ORDERDELIVERY_ENTITY_ID_FIELD'),
			),
			'ORDER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('ORDERDELIVERY_ENTITY_ORDER_ID_FIELD'),
			),
			'DELIVERY_LOCATION' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateLocation'),
				'title' => Loc::getMessage('ORDERDELIVERY_ENTITY_HID_FIELD'),
			),
			'DATE_REQUEST' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('ORDERDELIVERY_ENTITY_DATE_REQUEST_FIELD'),
			),
			'PARAMS' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('ORDERDELIVERY_ENTITY_PARAMS_FIELD'),
			),
		);
	}
	public static function validateLocation()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
}