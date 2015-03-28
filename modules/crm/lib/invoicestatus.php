<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2013-2013 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class InvoiceStatusTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_sale_status';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'string',
				'primary' => true
			),
			'SORT' => array(
				'data_type' => 'integer'
			)
		);
	}
}
