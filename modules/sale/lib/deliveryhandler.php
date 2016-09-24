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

class DeliveryHandlerTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_sale_delivery_handler';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'HID' => array(
				'data_type' => 'string'
			),
			'NAME' => array(
				'data_type' => 'string'
			),
			'LID' => array(
				'data_type' => 'string'
			)
		);
	}
}
