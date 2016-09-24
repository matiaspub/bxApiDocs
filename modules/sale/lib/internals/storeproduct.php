<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 *
 * @ignore
 * @see \Bitrix\Catalog\StoreProductTable
 */
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class StoreProductTable extends Main\Entity\DataManager
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
