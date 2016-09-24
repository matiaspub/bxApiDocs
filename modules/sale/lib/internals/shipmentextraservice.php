<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class ShipmentExtraServiceTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> SHIPMENT_ID int mandatory
 * <li> EXTRA_SERVICE_ID int mandatory
 * <li> VALUE string(255) optional
 * </ul>
 *
 * @package Bitrix\Sale\Internals
 **/

class ShipmentExtraServiceTable extends Main\Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_order_delivery_es';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ORDER_DELIVERY_EXTRA_SERVICES_ENTITY_ID_FIELD'),
			),
			'SHIPMENT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('ORDER_DELIVERY_EXTRA_SERVICES_ENTITY_SHIPMENT_ID_FIELD'),
			),
			'EXTRA_SERVICE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('ORDER_DELIVERY_EXTRA_SERVICES_ENTITY_EXTRA_SERVICE_ID_FIELD'),
			),
			'VALUE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateValue'),
				'title' => Loc::getMessage('ORDER_DELIVERY_EXTRA_SERVICES_ENTITY_VALUE_FIELD'),
			),
		);
	}
	public static function validateValue()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	public static function deleteByShipmentId($shipmentId)
	{
		if(intval($shipmentId) > 0)
		{
			$con = \Bitrix\Main\Application::getConnection();
			$sqlHelper = $con->getSqlHelper();
			$strSql = "DELETE FROM ".self::getTableName()." WHERE SHIPMENT_ID=".$sqlHelper->forSql($shipmentId);
			$con->queryExecute($strSql);
		}
	}
}