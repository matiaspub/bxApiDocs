<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main\Localization;

use Bitrix\Main\Entity;

class CultureTable extends Entity\DataManager
{
	const LEFT_TO_RIGHT = 'Y';
	const RIGHT_TO_LEFT = 'N';

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_culture';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'CODE' => array(
				'data_type' => 'string',
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'FORMAT_DATE' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'FORMAT_DATETIME' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'FORMAT_NAME' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'WEEK_START' => array(
				'data_type' => 'integer',
			),
			'CHARSET' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'DIRECTION' => array(
				'data_type' => 'boolean',
				'values' => array(self::RIGHT_TO_LEFT, self::LEFT_TO_RIGHT),
			),
		);
	}
}
