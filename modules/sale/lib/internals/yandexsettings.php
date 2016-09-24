<?php
namespace Bitrix\Sale\Internals;

use	Bitrix\Main\Entity\DataManager,
	Bitrix\Main\Entity\Validator;

class YandexSettingsTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_sale_yandex_settings';
	}

	public static function getMap()
	{
		return array(
			'SHOP_ID' => array(
				'required' => true,
				'primary' => true,
				'data_type' => 'integer',
			),
			'CSR' => array(
				'data_type' => 'text',
			),
			'SIGN' => array(
				'data_type' => 'text',
			),
			'CERT' => array(
				'data_type' => 'text',
			),
			'PKEY' => array(
				'data_type' => 'text',
			),
			'PUB_KEY' => array(
				'data_type' => 'text',
			)
		);
	}
}
