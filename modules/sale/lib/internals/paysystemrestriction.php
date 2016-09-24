<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale\Internals;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class PaySystemRestrictionTable extends \Bitrix\Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_sale_pay_system_rstr';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'PAY_SYSTEM_ID' => array(
				'data_type' => 'integer'
			),
			'SORT' => array(
				'data_type' => 'integer'
			),
			'CLASS_NAME' => array(
				'data_type' => 'string'
			),
			'PARAMS' => array(
				'data_type' => 'string'
			)
		);
	}
}
