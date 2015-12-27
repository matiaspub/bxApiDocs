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

class PaySystemActionTable extends \Bitrix\Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_sale_pay_system_action';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'PAY_SYSTEM_ID' => array(
				'data_type' => 'integer'
			),
			'PERSON_TYPE_ID' => array(
				'data_type' => 'integer'
			),
			'NAME' => array(
				'data_type' => 'string'
			),
			'ACTION_FILE' => array(
				'data_type' => 'string'
			),
			'RESULT_FILE' => array(
				'data_type' => 'string'
			),
			'NEW_WINDOW' => array(
				'data_type' => 'boolean'
			),
			'PARAMS' => array(
				'data_type' => 'string'
			),
			'TARIF' => array(
				'data_type' => 'string'
			),
			'HAVE_PAYMENT' => array(
				'data_type' => 'boolean'
			),
			'HAVE_ACTION' => array(
				'data_type' => 'boolean'
			),
			'HAVE_RESULT' => array(
				'data_type' => 'boolean'
			),
			'HAVE_PREPAY' => array(
				'data_type' => 'boolean'
			),
			'HAVE_RESULT_RECEIVE' => array(
				'data_type' => 'boolean'
			),
			'ENCODING' => array(
				'data_type' => 'string'
			),
			'LOGOTIP' => array(
				'data_type' => 'integer'
			)
		);
	}
}
