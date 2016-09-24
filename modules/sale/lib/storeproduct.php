<?php

namespace Bitrix\Sale;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class StoreProductTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_catalog_store_product';
	}

	public static function getMap()
	{
		global $DB;
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'PRODUCT_ID' => array(
				'data_type' => 'integer'
			),
			'SALE_PRODUCT' => array(
				'data_type' => 'Product',
				'reference' => array('=this.PRODUCT_ID' => 'ref.ID')
			),
			'AMOUNT' => array(
				'data_type' => 'float'
			),
			'STORE_ID' => array(
				'data_type' => 'integer',
			),
			'STORE' => array(
				'data_type' => 'Bitrix\Catalog\Store',
				'reference' => array('=this.STORE_ID' => 'ref.ID')
			)
		);

		return $fieldsMap;
	}
}
