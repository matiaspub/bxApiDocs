<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2013-2013 Bitrix
 */
namespace Bitrix\Crm;

if (!\CModule::IncludeModule('report'))
	return;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class InvoiceUtsTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_uts_order';
	}

	public static function getMap()
	{
		global $DB;

		return array(
			'VALUE_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'UF_DEAL_ID' => array(
				'data_type' => 'integer'
			),
			'UF_COMPANY_ID' => array(
				'data_type' => 'integer'
			),
			'UF_CONTACT_ID' => array(
				'data_type' => 'integer'
			),
			'DEAL_BY' => array(
				'data_type' => 'Deal',
				'reference' => array('=this.UF_DEAL_ID' => 'ref.ID')
			),
			'CONTACT_BY' => array(
				'data_type' => 'Contact',
				'reference' => array('=this.UF_CONTACT_ID' => 'ref.ID')
			),
			'COMPANY_BY' => array(
				'data_type' => 'Company',
				'reference' => array('=this.UF_COMPANY_ID' => 'ref.ID')
			)
		);
	}
}
