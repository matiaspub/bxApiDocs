<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

if (!\CModule::IncludeModule('report'))
	return;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class LeadTable extends Entity\DataManager
{
	private static $STATUS_INIT = false;
	private static $WORK_STATUSES = array();
	private static $REJECT_STATUSES = array();

	public static function getUFId()
	{
		return 'CRM_LEAD';
	}

	public static function getMap()
	{
		global $DB, $DBType;

		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'TITLE' => array(
				'data_type' => 'string'
			),
			'STATUS_ID' => array(
				'data_type' => 'string'
			),
			'STATUS_BY' => array(
				'data_type' => 'Status',
				'reference' => array(
					'=this.STATUS_ID' => 'ref.STATUS_ID',
					'=ref.ENTITY_ID' => array('?', 'STATUS')
				)
			),
			'STATUS_DESCRIPTION' => array(
				'data_type' => 'string'
			),
			'IS_CONVERT' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = \'CONVERTED\' THEN 1 ELSE 0 END',
					'STATUS_ID'
				),
				'values' => array(0, 1)
			),
			'PRODUCT_ID' => array(
				'data_type' => 'string'
			),
			'OPPORTUNITY' => array(
				'data_type' => 'integer'
			),
			'CURRENCY_ID' => array(
				'data_type' => 'string'
			),
			'COMMENTS' => array(
				'data_type' => 'string'
			),
			'NAME' => array(
				'data_type' => 'string'
			),
			'LAST_NAME' => array(
				'data_type' => 'string'
			),
			'SECOND_NAME' => array(
				'data_type' => 'string'
			),
			'SHORT_NAME' => array(
				'data_type' => 'string',
				'expression' => array(
					$DB->concat("%s","' '", "UPPER(".$DB->substr("%s", 1, 1).")", "'.'"),
					'LAST_NAME', 'NAME'
				)
			),
			'LOGIN' => array(
				'data_type' => 'string',
				'expression' => array('NULL')
			),
			'COMPANY_TITLE' => array(
				'data_type' => 'string'
			),
			'POST' => array(
				'data_type' => 'string'
			),
			'ADDRESS' => array(
				'data_type' => 'string'
			),
			'SOURCE_ID' => array(
				'data_type' => 'string'
			),
			'SOURCE_BY' => array(
				'data_type' => 'Status',
				'reference' => array(
					'=this.SOURCE_ID' => 'ref.STATUS_ID',
					'=ref.ENTITY_ID' => array('?', 'SOURCE')
				)
			),
			'SOURCE_DESCRIPTION' => array(
				'data_type' => 'string'
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime'
			),
			'DATE_CREATE_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DATE_CREATE'
				)
			),
			'DATE_MODIFY' => array(
				'data_type' => 'datetime'
			),
			'DATE_MODIFY_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DATE_MODIFY'
				)
			),
			'DATE_CLOSED' => array(
				'data_type' => 'datetime'
			),
			'ASSIGNED_BY_ID' => array(
				'data_type' => 'integer'
			),
			'ASSIGNED_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.ASSIGNED_BY_ID' => 'ref.ID')
			),
			'CREATED_BY_ID' => array(
				'data_type' => 'integer'
			),
			'CREATED_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.CREATED_BY_ID' => 'ref.ID')
			),
			'MODIFY_BY_ID' => array(
				'data_type' => 'integer'
			),
			'MODIFY_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.MODIFY_BY_ID' => 'ref.ID')
			),
			'EVENT_RELATION' => array(
				'data_type' => 'EventRelations',
				'reference' => array('=this.ID' => 'ref.ENTITY_ID')
			),
			'PHONE_MOBILE' => array(
				'data_type' => 'string',
				'expression' => array(
					(ToLower($DBType) === 'oracle') ?
					'(SELECT FM.VALUE '.
						'FROM (SELECT ID, ENTITY_ID, ELEMENT_ID, TYPE_ID, VALUE_TYPE, VALUE '.
						'FROM b_crm_field_multi ORDER BY ID) FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'PHONE\' '.
						'AND FM.VALUE_TYPE = \'MOBILE\' '.
						'AND ROWNUM <= 1)' :
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'PHONE\' '.
						'AND FM.VALUE_TYPE = \'MOBILE\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'PHONE_WORK' => array(
				'data_type' => 'string',
				'expression' => array(
					(ToLower($DBType) === 'oracle') ?
					'(SELECT FM.VALUE '.
					'FROM (SELECT ID, ENTITY_ID, ELEMENT_ID, TYPE_ID, VALUE_TYPE, VALUE '.
					'FROM b_crm_field_multi ORDER BY ID) FM '.
					'WHERE FM.ENTITY_ID = \'LEAD\' '.
					'AND FM.ELEMENT_ID = %s '.
					'AND FM.TYPE_ID = \'PHONE\' '.
					'AND FM.VALUE_TYPE = \'WORK\' '.
					'AND ROWNUM <= 1)' :
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'PHONE\' '.
						'AND FM.VALUE_TYPE = \'WORK\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'EMAIL_HOME' => array(
				'data_type' => 'string',
				'expression' => array(
					(ToLower($DBType) === 'oracle') ?
					'(SELECT FM.VALUE '.
					'FROM (SELECT ID, ENTITY_ID, ELEMENT_ID, TYPE_ID, VALUE_TYPE, VALUE '.
					'FROM b_crm_field_multi ORDER BY ID) FM '.
					'WHERE FM.ENTITY_ID = \'LEAD\' '.
					'AND FM.ELEMENT_ID = %s '.
					'AND FM.TYPE_ID = \'EMAIL\' '.
					'AND FM.VALUE_TYPE = \'HOME\' '.
					'AND ROWNUM <= 1)' :
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'EMAIL\' '.
						'AND FM.VALUE_TYPE = \'HOME\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'EMAIL_WORK' => array(
				'data_type' => 'string',
				'expression' => array(
					(ToLower($DBType) === 'oracle') ?
					'(SELECT FM.VALUE '.
					'FROM (SELECT ID, ENTITY_ID, ELEMENT_ID, TYPE_ID, VALUE_TYPE, VALUE '.
					'FROM b_crm_field_multi ORDER BY ID) FM '.
					'WHERE FM.ENTITY_ID = \'LEAD\' '.
					'AND FM.ELEMENT_ID = %s '.
					'AND FM.TYPE_ID = \'EMAIL\' '.
					'AND FM.VALUE_TYPE = \'WORK\' '.
					'AND ROWNUM <= 1)' :
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'EMAIL\' '.
						'AND FM.VALUE_TYPE = \'WORK\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'SKYPE' => array(
				'data_type' => 'string',
				'expression' => array(
					(ToLower($DBType) === 'oracle') ?
					'(SELECT FM.VALUE '.
					'FROM (SELECT ID, ENTITY_ID, ELEMENT_ID, TYPE_ID, VALUE_TYPE, VALUE '.
					'FROM b_crm_field_multi ORDER BY ID) FM '.
					'WHERE FM.ENTITY_ID = \'LEAD\' '.
					'AND FM.ELEMENT_ID = %s '.
					'AND FM.TYPE_ID = \'IM\' '.
					'AND FM.VALUE_TYPE = \'SKYPE\' '.
					'AND ROWNUM <= 1)' :
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'IM\' '.
						'AND FM.VALUE_TYPE = \'SKYPE\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			),
			'ICQ' => array(
				'data_type' => 'string',
				'expression' => array(
					(ToLower($DBType) === 'oracle') ?
					'(SELECT FM.VALUE '.
					'FROM (SELECT ID, ENTITY_ID, ELEMENT_ID, TYPE_ID, VALUE_TYPE, VALUE '.
					'FROM b_crm_field_multi ORDER BY ID) FM '.
					'WHERE FM.ENTITY_ID = \'LEAD\' '.
					'AND FM.ELEMENT_ID = %s '.
					'AND FM.TYPE_ID = \'IM\' '.
					'AND FM.VALUE_TYPE = \'ICQ\' '.
					'AND ROWNUM <= 1)' :
					'('.$DB->TopSql(
						'SELECT FM.VALUE '.
						'FROM b_crm_field_multi FM '.
						'WHERE FM.ENTITY_ID = \'LEAD\' '.
						'AND FM.ELEMENT_ID = %s '.
						'AND FM.TYPE_ID = \'IM\' '.
						'AND FM.VALUE_TYPE = \'ICQ\' '.
						'ORDER BY FM.ID', 1
					).')',
					'ID'
				)
			)
		);
	}

	private static function ensureStatusesLoaded()
	{
		if(self::$STATUS_INIT)
		{
			return;
		}

		global $DB;

		$convertStatus = null;
		$arStatuses = array();
		$rsStatuses = $DB->Query('SELECT STATUS_ID, SORT FROM b_crm_status WHERE ENTITY_ID = \'STATUS\'');
		while($arStatus = $rsStatuses->Fetch())
		{
			if(!$convertStatus && strval($arStatus['STATUS_ID']) === 'CONVERTED')
			{
				$convertStatus = $arStatus;
				continue;
			}

			$arStatuses[$arStatus['STATUS_ID']] = $arStatus;
		}

		self::$WORK_STATUSES = array();
		self::$REJECT_STATUSES = array();

		if($convertStatus)
		{
			$convertStatusSort = intval($convertStatus['SORT']);
			foreach($arStatuses as $statusID => $arStatus)
			{
				$sort = intval($arStatus['SORT']);
				if($sort < $convertStatusSort)
				{
					self::$WORK_STATUSES[] = '\''.$DB->ForSql($statusID).'\'';
				}
				elseif($sort > $convertStatusSort)
				{
					self::$REJECT_STATUSES[] = '\''.$DB->ForSql($statusID).'\'';
				}
			}
		}

		self::$STATUS_INIT = true;
	}

	public static function processQueryOptions(&$options)
	{
		$stub = '_BX_STATUS_STUB_';
		self::ensureStatusesLoaded();
		$options['WORK_STATUS_IDS'] = '('.(!empty(self::$WORK_STATUSES) ? implode(',', self::$WORK_STATUSES) : "'$stub'").')';
		$options['REJECT_STATUS_IDS'] = '('.(!empty(self::$REJECT_STATUSES) ? implode(',', self::$REJECT_STATUSES) : "'$stub'").')';
	}
}
