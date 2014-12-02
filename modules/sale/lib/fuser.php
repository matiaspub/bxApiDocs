<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class FuserTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_sale_fuser';
	}

	public static function getMap()
	{
		global $DB;

		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'DATE_INSERT' => array(
				'data_type' => 'datetime'
			),
			'DATE_INS' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->DatetimeToDateFunction('%s'), 'DATE_INSERT'
				)
			),
			'DATE_UPDATE' => array(
				'data_type' => 'datetime'
			),
			'DATE_UPD' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->DatetimeToDateFunction('%s'), 'DATE_UPDATE'
				)
			),
			'USER_ID' => array(
				'data_type' => 'integer'
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.USER_ID' => 'ref.ID')
			)
		);
	}
}
