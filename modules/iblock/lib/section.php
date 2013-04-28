<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Iblock;

use Bitrix\Main\Entity;

class SectionTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_iblock_section';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'NAME' => array(
				'data_type' => 'string'
			)
		);
	}
}
