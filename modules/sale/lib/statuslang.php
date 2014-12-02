<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class StatusLangTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_sale_status_lang';
	}

	public static function getMap()
	{
		return array(
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
	}
}
