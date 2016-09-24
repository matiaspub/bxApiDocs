<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Sale\Internals;

use	Bitrix\Main\Entity\DataManager,
	Bitrix\Main\Entity\Validator;

class UserPropsValueTable extends DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_user_props_value';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'primary' => true,
				'autocomplete' => true,
				'data_type' => 'integer',
				'format' => '/^[0-9]{1,11}$/',
			),
			'USER_PROPS_ID' => array(
				'data_type' => 'integer',
				'format' => '/^[0-9]{1,11}$/',
			),
			'ORDER_PROPS_ID' => array(
				'data_type' => 'integer',
				'format' => '/^[0-9]{1,11}$/',
			),
			'NAME' => array(
				'required' => true,
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'getNameValidators'),
			),
			'VALUE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'getValueValidators'),
			),

			'PROPERTY' => array(
				'data_type' => 'Bitrix\Sale\Internals\OrderPropsTable',
				'reference' => array('=this.ORDER_PROPS_ID' => 'ref.ID'),
				'join_type' => 'LEFT',
			),
		);
	}

	public static function getNameValidators()
	{
		return array(
			new Validator\Length(1, 255),
		);
	}

	public static function getValueValidators()
	{
		return array(
			new Validator\Length(null, 255),
		);
	}
}
