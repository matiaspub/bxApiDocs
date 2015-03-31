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

class CompanyTable extends Entity\DataManager
{
	public static function getUFId()
	{
		return 'CRM_COMPANY';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'TITLE' => array(
				'data_type' => 'string'
			),
			'COMPANY_TYPE' => array(
				'data_type' => 'string'
			),
			'COMPANY_TYPE_BY' => array(
				'data_type' => 'Status',
				'reference' => array(
					'=this.COMPANY_TYPE' => 'ref.STATUS_ID',
					'=ref.ENTITY_ID' => array('?', 'COMPANY_TYPE')
				)
			),
			'INDUSTRY' => array(
				'data_type' => 'string'
			),
			'INDUSTRY_BY' => array(
				'data_type' => 'Status',
				'reference' => array(
					'=this.INDUSTRY' => 'ref.STATUS_ID',
					'=ref.ENTITY_ID' => array('?', 'INDUSTRY')
				)
			),
			'EMPLOYEES' => array(
				'data_type' => 'string'
			),
			'EMPLOYEES_BY' => array(
				'data_type' => 'Status',
				'reference' => array(
					'=this.EMPLOYEES' => 'ref.STATUS_ID',
					'=ref.ENTITY_ID' => array('?', 'EMPLOYEES')
				)
			),
			'REVENUE' => array(
				'data_type' => 'string'
			),
//			'REVENUE_BY' => array( // FOR COMPATIBILITY ONLY
//				'data_type' => 'CrmStatus',
//				'reference' => array('=this.REVENUE' => 'ref.STATUS_ID')
//			),
			'CURRENCY_ID' => array(
				'data_type' => 'string'
			),
//			'CURRENCY_BY' => array(
//				'data_type' => 'CrmStatus',
//				'reference' => array('CURRENCY_ID', 'STATUS_ID')
//			),
			'COMMENTS' => array(
				'data_type' => 'string'
			),
			'ADDRESS' => array(
				'data_type' => 'string'
			),
			'ADDRESS_LEGAL' => array(
				'data_type' => 'string'
			),
			'BANKING_DETAILS' => array(
				'data_type' => 'string'
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime'
			),
			'DATE_MODIFY' => array(
				'data_type' => 'datetime'
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
			)
		);
	}
}
