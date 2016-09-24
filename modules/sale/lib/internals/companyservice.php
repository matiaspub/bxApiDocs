<?php

namespace Bitrix\Sale\Internals;

use Bitrix\Main;


class CompanyServiceTable extends Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_sale_company2service';
	}

	public static function getMap()
	{
		return array(
			'COMPANY_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'SERVICE_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'SERVICE_TYPE' => array(
				'data_type' => 'integer',
				'primary' => true
			),
		);
	}
}