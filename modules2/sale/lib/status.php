<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Main\Entity;

class StatusTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_status_lang';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'STATUS_ID' => array(
				'data_type' => 'string',
				'primary' => true
			),
			// field for filter operation on entity
			'ID' => array(
				'data_type' => 'string',
				'expression' => array(
					'%s', 'STATUS_ID'
				)
			),
			'LID' => array(
				'data_type' => 'string'
			),
			'NAME' => array(
				'data_type' => 'string'
			),
			'DESCRIPTION' => array(
				'data_type' => 'string'
			)
		);

		return $fieldsMap;
	}
}
