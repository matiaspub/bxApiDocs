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

class BasketTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_sale_basket';
	}

	public static function getMap()
	{
		global $DB, $DBType;

		if (function_exists('___dbCastIntToChar') !== true)
		{
			eval(
				'function ___dbCastIntToChar($dbtype, $param)'.
				'{'.
				'   $result = $param;'.
				'   if (ToLower($dbtype) === "mssql")'.
				'   {'.
				'       $result = "CAST(".$param." AS VARCHAR)";'.
				'   }'.
				'   return $result;'.
				'}'
			);
		}

		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'FUSER_ID' => array(
				'data_type' => 'integer'
			),
			'FUSER' => array(
				'data_type' => 'Fuser',
				'reference' => array(
					'=this.FUSER_ID' => 'ref.ID'
				)
			),
			'DATE_INSERT' => array(
				'data_type' => 'datetime'
			),
			'DATE_INS' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DATE_INSERT'
				)
			),
			'DATE_UPDATE' => array(
				'data_type' => 'datetime'
			),
			'DATE_UPD' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DATE_UPDATE'
				)
			),
			'PRODUCT_ID' => array(
				'data_type' => 'integer'
			),
			'PRODUCT' => array(
				'data_type' => 'Product',
				'reference' => array(
					'=this.PRODUCT_ID' => 'ref.ID'
				)
			),
			'NAME' => array(
				'data_type' => 'string'
			),
			'NAME_WITH_IDENT' => array(
				'data_type' => 'string',
				'expression' => array(
					$DB->concat('%s', '\' [\'', ___dbCastIntToChar($DBType, '%s'), '\']\''), 'NAME', 'PRODUCT_ID'
				)
			),
			'ORDER_ID' => array(
				'data_type' => 'integer'
			),
			'ORDER' => array(
				'data_type' => 'Order',
				'reference' => array(
					'=this.ORDER_ID' => 'ref.ID'
				)
			),
			'PRICE' => array(
				'data_type' => 'float'
			),
			'DISCOUNT_PRICE' => array(
				'data_type' => 'float'
			),
			'DISCOUNT_NAME' => array(
				'data_type' => 'string'
			),
			'DISCOUNT_VALUE' => array(
				'data_type' => 'string'
			),
			'VAT_RATE' => array(
				'data_type' => 'float'
			),
			'VAT_RATE_PRC' => array(
				'data_type' => 'float',
				'expression' => array(
					'100 * %s', 'VAT_RATE'
				)
			),
			'QUANTITY' => array(
				'data_type' => 'float'
			),
			'NOTES' => array(
				'data_type' => 'string'
			),
			'LID' => array(
				'data_type' => 'string'
			),
			'DELAY' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),
			'SUMMARY_PRICE' => array(
				'data_type' => 'float',
				'expression' => array(
					'(%s * %s)', 'QUANTITY', 'PRICE'
				)
			),
			'SUBSCRIBE' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),
			'N_SUBSCRIBE' => array(
				'data_type' => 'integer',
				'expression' => array(
					'CASE WHEN %s = \'Y\' THEN 1 ELSE 0 END', 'SUBSCRIBE'
				)
			),
			'SUMMARY_PURCHASING_PRICE' => array(
				'data_type' => 'float',
				'expression' => array(
					'(%s) * %s', 'PRODUCT.PURCHASING_PRICE_IN_SITE_CURRENCY', 'QUANTITY'
				)
			),
			'GROSS_PROFIT' => array(
				'data_type' => 'float',
				'expression' => array(
					'(%s) - (%s)', 'SUMMARY_PRICE', 'SUMMARY_PURCHASING_PRICE'
				)
			),
			'PROFITABILITY' => array(
				'data_type' => 'float',
				'expression' => array(
					'CASE WHEN %s is NULL OR %s=0 THEN NULL ELSE (%s) * 100 / (%s) END',
					'SUMMARY_PURCHASING_PRICE', 'SUMMARY_PURCHASING_PRICE', 'GROSS_PROFIT', 'SUMMARY_PURCHASING_PRICE'
				)
			)
		);
	}
}
