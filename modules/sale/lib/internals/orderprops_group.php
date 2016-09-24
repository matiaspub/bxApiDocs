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

class OrderPropsGroupTable extends DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_order_props_group';
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
			'PERSON_TYPE_ID' => array(
				'required' => true,
				'data_type' => 'integer',
				'format' => '/^[0-9]{1,11}$/',
			),
			'NAME' => array(
				'required' => true,
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'getNameValidators'),
			),
			'SORT' => array(
				'data_type' => 'integer',
				'format' => '/^[0-9]{1,11}$/',
			),
		);
	}

	public static function getNameValidators()
	{
		return array(
			new Validator\Length(1, 255),
		);
	}
}
