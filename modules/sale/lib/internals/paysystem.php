<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class PaySystemTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> LID string(2) optional
 * <li> CURRENCY string(3) optional
 * <li> NAME string(255) mandatory
 * <li> ACTIVE bool optional default 'Y'
 * <li> SORT int optional default 100
 * <li> DESCRIPTION string(2000) optional
 * </ul>
 **/
class PaySystemTable extends Main\Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_pay_system';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('PAY_SYSTEM_ENTITY_ID_FIELD'),
			),
			'LID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateLid'),
				'title' => Loc::getMessage('PAY_SYSTEM_ENTITY_LID_FIELD'),
			),
			'CURRENCY' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCurrency'),
				'title' => Loc::getMessage('PAY_SYSTEM_ENTITY_CURRENCY_FIELD'),
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('PAY_SYSTEM_ENTITY_NAME_FIELD'),
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('PAY_SYSTEM_ENTITY_ACTIVE_FIELD'),
			),
			'SORT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('PAY_SYSTEM_ENTITY_SORT_FIELD'),
			),
			'DESCRIPTION' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateDescription'),
				'title' => Loc::getMessage('PAY_SYSTEM_ENTITY_DESCRIPTION_FIELD'),
			),
			'ALLOW_EDIT_PAYMENT' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('PAY_SYSTEM_ENTITY_ALLOW_EDIT_PAYMENT_FIELD')
			)
		);
	}
	public static function validateLid()
	{
		return array(
			new Main\Entity\Validator\Length(null, 2),
		);
	}
	public static function validateCurrency()
	{
		return array(
			new Main\Entity\Validator\Length(null, 3),
		);
	}
	public static function validateName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	public static function validateDescription()
	{
		return array(
			new Main\Entity\Validator\Length(null, 2000),
		);
	}
}