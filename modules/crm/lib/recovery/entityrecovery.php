<?php
namespace Bitrix\Crm\Recovery;

use Bitrix\Main;
use Bitrix\Main\Entity;
//use Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

class EntityRecoveryTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_entity_recovery';
	}
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'REGISTRATION_TIME' => array(
				'data_type' => 'datetime',
				'required' => true
			),
			'ENTITY_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'ENTITY_TYPE_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'CONTEXT_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'RESPONSIBLE_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'TITLE' => array(
				'data_type' => 'string',
				'required' => true
			),
			'IS_COMPRESSED' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'DATA' => array(
				'data_type' => 'string',
				'required' => true
			)
		);
	}
}