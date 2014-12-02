<?php
namespace Bitrix\Main;

use Bitrix\Main\Entity;

/**
 * Class UserFieldConfirmTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int mandatory
 * <li> DATE_CHANGE datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> FIELD string(255) mandatory
 * <li> FIELD_VALUE string(255) mandatory
 * <li> CONFIRM_CODE string(32) mandatory
 * </ul>
 **/

class UserFieldConfirmTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_user_field_confirm';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'DATE_CHANGE' => array(
				'data_type' => 'datetime',
			),
			'FIELD' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'FIELD_VALUE' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'CONFIRM_CODE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateConfirmCode'),
			),
		);
	}

	public static function validateConfirmCode()
	{
		return array(
			new Entity\Validator\Length(null, 32),
		);
	}
}
