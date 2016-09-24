<?php
namespace Bitrix\Sale\Delivery\ExtraServices;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class Table
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CODE string(50) optional
 * <li> NAME string(255) mandatory
 * <li> DESCRIPTION string(255) optional
 * <li> CLASS_NAME string(255) mandatory
 * <li> PARAMS string optional
 * <li> RIGHTS string(3) mandatory
 * <li> DELIVERY_ID int mandatory
 * <li> INIT_VALUE string(255) optional
 * <li> ACTIVE string(1) mandatory
 * <li> SORT int optional default 100
 * </ul>
 *
 * @package Bitrix\Sale\Delivery\ExtraServices
 **/

class Table extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_delivery_es';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('DELIVERY_EXTRA_SERVICES_ENTITY_ID_FIELD'),
			),
			'CODE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCode'),
				'title' => Loc::getMessage('DELIVERY_EXTRA_SERVICES_ENTITY_CODE_FIELD'),
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('DELIVERY_EXTRA_SERVICES_ENTITY_NAME_FIELD'),
			),
			'DESCRIPTION' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateDescription'),
				'title' => Loc::getMessage('DELIVERY_EXTRA_SERVICES_ENTITY_DESCRIPTION_FIELD'),
			),
			'CLASS_NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateClassName'),
				'title' => Loc::getMessage('DELIVERY_EXTRA_SERVICES_ENTITY_CLASS_NAME_FIELD'),
			),
			'PARAMS' => array(
				'data_type' => 'text',
				'serialized' => true,
				'title' => Loc::getMessage('DELIVERY_EXTRA_SERVICES_ENTITY_PARAMS_FIELD'),
			),
			'RIGHTS' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateRights'),
				'title' => Loc::getMessage('DELIVERY_EXTRA_SERVICES_ENTITY_RIGHTS_FIELD'),
			),
			'DELIVERY_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('DELIVERY_EXTRA_SERVICES_ENTITY_DELIVERY_ID_FIELD'),
			),
			'INIT_VALUE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateInitial'),
				'title' => Loc::getMessage('DELIVERY_EXTRA_SERVICES_ENTITY_INITIAL_FIELD'),
			),
			'ACTIVE' => array(
				'data_type' => 'string',
				'default_value'=> 'Y',
				'validation' => array(__CLASS__, 'validateActive'),
				'title' => Loc::getMessage('DELIVERY_EXTRA_SERVICES_ENTITY_ACTIVE_FIELD'),
			),
			'SORT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('DELIVERY_EXTRA_SERVICES_ENTITY_SORT_FIELD'),
			),
			'DELIVERY_SERVICE' => array(
				'data_type' => '\Bitrix\Sale\Delivery\Services\Table',
				'reference' => array('=this.DELIVERY_ID' => 'ref.ID'),
			)
		);
	}
	public static function validateCode()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
	public static function validateName()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateDescription()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateClassName()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateRights()
	{
		return array(
			new Entity\Validator\Length(null, 3),
		);
	}
	public static function validateInitial()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateActive()
	{
		return array(
			new Entity\Validator\Length(null, 1),
		);
	}

	public static function onBeforeDelete(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$primary = $event->getParameter("primary");

		if(intval($primary['ID']) > 0)
		{
			$dbRes = \Bitrix\Sale\Internals\ShipmentExtraServiceTable::getList(array(
				'filter' => array(
					'=EXTRA_SERVICE_ID' => $primary['ID']
				)
			));

			if($row = $dbRes->fetch())
				$result->addError(new Entity\EntityError(
					str_replace('#ID#', $primary['ID'], Loc::getMessage('DELIVERY_EXTRA_SERVICES_ENTITY_ERROR_DELETE'))
				));
		}

		return $result;
	}
}