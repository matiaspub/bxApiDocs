<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class EventRelationsTable extends Entity\DataManager
{
	public static function getMap()
	{
		return array(
			'EVENT_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'EVENT_BY' => array(
				'data_type' => 'Event',
				'reference' => array('=this.EVENT_ID' => 'ref.ID')
			),
			'ENTITY_TYPE' => array(
				'data_type' => 'string'
			),
			'ENTITY_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'ASSIGNED_BY_ID' => array(
				'data_type' => 'integer'
			),
			'ASSIGNED_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.ASSIGNED_BY_ID' => 'ref.ID')
			)
		);
	}
}

class EventTable extends Entity\DataManager
{
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'EVENT_ID' => array(
				'data_type' => 'string'
			),
			'EVENT_BY' => array(
				'data_type' => 'Status',
				'reference' => array('EVENT_ID', 'STATUS_ID', 'ENTITY_ID')
			),
			'EVENT_NAME' => array(
				'data_type' => 'string'
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime'
			),
			'CREATED_BY_ID' => array(
				'data_type' => 'integer'
			),
			'CREATED_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('CREATED_BY_ID', 'ID')
			)
		);
	}
}
