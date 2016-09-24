<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 *
 * @ignore
 * @see \Bitrix\Catalog\SectionTable
 */
namespace Bitrix\Sale\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class SectionTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_iblock_section';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'NAME' => array(
				'data_type' => 'string'
			)
		);

		return $fieldsMap;
	}
}