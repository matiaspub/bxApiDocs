<?php

namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class Table
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DELIVERY_ID int mandatory
 * <li> SORT int sorting
 * <li> CLASS_NAME string(255) mandatory
 * <li> PARAMS string optional
 * </ul>
 *
 * @package Bitrix\Sale\Delivery\Restrictions
 **/

class ServiceRestrictionTable extends Main\Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_service_rstr';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('DELIVERY_RESTRICTION_ENTITY_ID_FIELD'),
			),
			'SERVICE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('DELIVERY_RESTRICTION_ENTITY_DELIVERY_ID_FIELD'),
			),
			'SERVICE_TYPE' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('DELIVERY_RESTRICTION_ENTITY_SERVICE_TYPE_FIELD'),
			),
			'SORT' => array(
				'data_type' => 'integer',
				'default' => 100,
				'title' => Loc::getMessage('DELIVERY_RESTRICTION_ENTITY_SORT_FIELD'),
			),
			'CLASS_NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateClassName'),
				'title' => Loc::getMessage('DELIVERY_RESTRICTION_ENTITY_CLASS_NAME_FIELD'),
			),
			'PARAMS' => array(
				'data_type' => 'text',
				'serialized' => true,
				'title' => Loc::getMessage('DELIVERY_RESTRICTION_ENTITY_PARAMS_FIELD'),
			),
		);
	}
	public static function validateClassName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
}