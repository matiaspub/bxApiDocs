<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Iblock;

use Bitrix\Main\Entity;

class ElementTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_iblock_element';
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
			),
			'IBLOCK_ID' => array(
				'data_type' => 'integer'
			),
			'IBLOCK' => array(
				'data_type' => 'Iblock',
				'reference' => array('=this.IBLOCK_ID' => 'ref.ID')
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			)
		);
	}
}
