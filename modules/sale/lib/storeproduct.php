<?php

namespace Bitrix\Sale;

use Bitrix\Main\Entity;

class StoreProductTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

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
			),
			'ARRIVED_PRODUCTS_IN_PERIOD_BY_STORE' => array(
				'data_type' => 'float',
				'expression' => array(
					'(SELECT  SUM(b_catalog_docs_element.AMOUNT)
						FROM b_catalog_store_docs
						INNER JOIN b_catalog_docs_element on b_catalog_store_docs.ID = b_catalog_docs_element.DOC_ID
						WHERE b_catalog_store_docs.DOC_TYPE in (\'A\', \'M\', \'R\')
							AND b_catalog_store_docs.STATUS = \'Y\'
							AND b_catalog_store_docs.DATE_DOCUMENT %%RT_TIME_INTERVAL%%
							AND b_catalog_docs_element.STORE_TO = %s
							AND b_catalog_docs_element.ELEMENT_ID = %s)', 'STORE_ID', 'PRODUCT_ID'
				)
			),
			'EXPENSE_PRODUCTS_IN_PERIOD_BY_STORE' => array(
				'data_type' => 'integer',
				'expression' => array(
					$DB->isNull('(SELECT  SUM(b_sale_store_barcode.QUANTITY)
						FROM b_sale_store_barcode
							INNER JOIN b_sale_basket ON b_sale_store_barcode.BASKET_ID = b_sale_basket.ID
							INNER JOIN b_sale_order ON b_sale_basket.ORDER_ID = b_sale_order.ID
						WHERE b_sale_store_barcode.STORE_ID = %s
							AND b_sale_basket.PRODUCT_ID = %s
							AND b_sale_order.PAYED = \'Y\'
							AND b_sale_order.DEDUCTED = \'Y\'
							AND b_sale_order.DATE_INSERT %%RT_TIME_INTERVAL%%
							AND b_sale_basket.LID %%RT_SITE_FILTER%%)', 0).'+'.
					$DB->isNull('(SELECT  SUM(b_catalog_docs_element.AMOUNT)
						FROM b_catalog_store_docs
							INNER JOIN b_catalog_docs_element on b_catalog_store_docs.ID = b_catalog_docs_element.DOC_ID
						WHERE b_catalog_store_docs.DOC_TYPE in (\'M\', \'D\')
							AND b_catalog_store_docs.STATUS = \'Y\'
							AND b_catalog_store_docs.DATE_DOCUMENT %%RT_TIME_INTERVAL%%
							AND b_catalog_docs_element.STORE_FROM = %s
							AND b_catalog_docs_element.ELEMENT_ID = %s)', 0),
					'STORE_ID', 'PRODUCT_ID', 'STORE_ID', 'PRODUCT_ID'
				)
			)
		);

		return $fieldsMap;
	}
}
