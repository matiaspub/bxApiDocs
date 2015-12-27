<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\Entity;

class GroupTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_group';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime'
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'C_SORT' => array(
				'data_type' => 'integer'
			),
			'ANONYMOUS' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'NAME' => array(
				'data_type' => 'string'
			),
			'DESCRIPTION' => array(
				'data_type' => 'string'
			),
			'STRING_ID' => array(
				'data_type' => 'string'
			),
		);
	}
}
