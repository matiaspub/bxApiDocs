<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ActivityTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_act';
	}

	public static function getMap()
	{
		global $DB, $DBType;

		$datetimeNull = (ToUpper($DBType) === 'MYSQL') ? 'CAST(NULL AS DATETIME)' : 'NULL';

		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'TYPE_ID' => array(
				'data_type' => 'integer'
			),
			'DIRECTION' => array(
				'data_type' => 'integer'
			),
			'IS_MEETING' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = '.\CCrmActivityType::Meeting.' THEN 1 ELSE 0 END',
					'TYPE_ID'
				),
				'values' => array(0, 1)
			),
			'IS_CALL' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = '.\CCrmActivityType::Call.' THEN 1 ELSE 0 END',
					'TYPE_ID'
				),
				'values' => array(0, 1)
			),
			'IS_CALL_IN' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = '.\CCrmActivityType::Call.
					' AND %s = '.\CCrmActivityDirection::Incoming.' THEN 1 ELSE 0 END',
					'TYPE_ID', 'DIRECTION'
				),
				'values' => array(0, 1)
			),
			'IS_CALL_OUT' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = '.\CCrmActivityType::Call.
					' AND %s = '.\CCrmActivityDirection::Outgoing.' THEN 1 ELSE 0 END',
					'TYPE_ID', 'DIRECTION'
				),
				'values' => array(0, 1)
			),
			'IS_TASK' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = '.\CCrmActivityType::Task.' THEN 1 ELSE 0 END',
					'TYPE_ID'
				),
				'values' => array(0, 1)
			),
			'IS_EMAIL' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = '.\CCrmActivityType::Email.' THEN 1 ELSE 0 END',
					'TYPE_ID'
				),
				'values' => array(0, 1)
			),
			'IS_EMAIL_IN' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = '.\CCrmActivityType::Email.
					' AND %s = '.\CCrmActivityDirection::Incoming.' THEN 1 ELSE 0 END',
					'TYPE_ID', 'DIRECTION'
				),
				'values' => array(0, 1)
			),
			'IS_EMAIL_OUT' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = '.\CCrmActivityType::Email.
					' AND %s = '.\CCrmActivityDirection::Outgoing.' THEN 1 ELSE 0 END',
					'TYPE_ID', 'DIRECTION'
				),
				'values' => array(0, 1)
			),
			'OWNER_ID' => array(
				'data_type' => 'integer'
			),
			'OWNER_TYPE_ID' => array(
				'data_type' => 'integer'
			),
			'ASSOCIATED_ENTITY_ID' => array(
				'data_type' => 'integer'
			),
			'SUBJECT' => array(
				'data_type' => 'string'
			),
			'COMPLETED' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'RESPONSIBLE_ID' => array(
				'data_type' => 'integer'
			),
			'ASSIGNED_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.RESPONSIBLE_ID' => 'ref.ID')
			),
			'PRIORITY' => array(
				'data_type' => 'integer'
			),
			'NOTIFY_TYPE' => array(
				'data_type' => 'integer'
			),
			'NOTIFY_VALUE' => array(
				'data_type' => 'integer'
			),
			'LOCATION' => array(
				'data_type' => 'string'
			),
			'CREATED' => array(
				'data_type' => 'datetime'
			),
			'DATE_CREATED_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'CREATED'
				)
			),
			'LAST_UPDATED' => array(
				'data_type' => 'datetime'
			),
			'LAST_UPDATED_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'LAST_UPDATED'
				)
			),
			'DATE_FINISHED_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					'CASE WHEN %s = \'Y\' THEN '.$DB->datetimeToDateFunction('%s').' ELSE '.$datetimeNull.' END', 'COMPLETED', 'LAST_UPDATED'
				)
			),
			'START_TIME' => array(
				'data_type' => 'datetime'
			),
			'START_TIME_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'START_TIME'
				)
			),
			'END_TIME' => array(
				'data_type' => 'datetime'
			),
			'END_TIME_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'END_TIME'
				)
			),
			'PARENT_ID' => array(
				'data_type' => 'integer'
			),
			'URN' => array(
				'data_type' => 'string'
			),
			'ORIGIN_ID' => array(
				'data_type' => 'string'
			),
			'AUTHOR_ID' => array(
				'data_type' => 'integer'
			),
			'AUTHOR_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.AUTHOR_ID' => 'ref.ID')
			),
			'EDITOR_ID' => array(
				'data_type' => 'integer'
			),
			'EDITOR_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.EDITOR_ID' => 'ref.ID')
			)
		);
	}
}
