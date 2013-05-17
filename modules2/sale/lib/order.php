<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Main\Entity;

class OrderTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_order';
	}

	public static function getMap()
	{
		global $DB;

		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
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
			'DATE_UPDATE_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DATE_UPDATE'
				)
			),
			'PRODUCTS_QUANT' => array(
				'data_type' => 'float',
				'expression' => array(
					'(SELECT  SUM(b_sale_basket.QUANTITY)
						FROM b_sale_basket
						WHERE b_sale_basket.ORDER_ID = %s)', 'ID'
				)
			),
			'TAX_VALUE' => array(
				'data_type' => 'float'
			),
			'PRICE_DELIVERY' => array(
				'data_type' => 'float'
			),
			'ALLOW_DELIVERY' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),
			'EMP_ALLOW_DELIVERY_ID' => array(
				'data_type' => 'integer'
			),
			'EMP_ALLOW_DELIVERY_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array(
					'=this.EMP_ALLOW_DELIVERY_ID' => 'ref.ID'
				)
			),
			'DATE_ALLOW_DELIVERY' => array(
				'data_type' => 'datetime'
			),
			'DATE_ALLOW_DELIVERY_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DATE_ALLOW_DELIVERY'
				)
			),
			'DELIVERY_ID' => array(
				'data_type' => 'string'
			),
			'DISCOUNT_VALUE' => array(
				'data_type' => 'float'
			),
			'DISCOUNT_ALL' => array(
				'data_type' => 'float',
				'expression' => array(
					'%s + (SELECT  SUM(b_sale_basket.DISCOUNT_PRICE)
						FROM b_sale_basket
						WHERE b_sale_basket.ORDER_ID = %s)', 'DISCOUNT_VALUE', 'ID'
				)
			),
			'PRICE' => array(
				'data_type' => 'float'
			),
			'STATUS_ID' => array(
				'data_type' => 'string'
			),
			'STATUS' => array(
				'data_type' => 'Status',
				'reference' => array(
					'=this.STATUS_ID' => 'ref.STATUS_ID',
					'=ref.LID' => array('?', LANGUAGE_ID)
				)
			),
			'EMP_STATUS_ID' => array(
				'data_type' => 'integer'
			),
			'EMP_STATUS_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array(
					'=this.EMP_STATUS_ID' => 'ref.ID'
				)
			),
			'DATE_STATUS' => array(
				'data_type' => 'datetime'
			),
			'DATE_STATUS_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DATE_STATUS'
				)
			),
			'PAYED' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),
			'EMP_PAYED_ID' => array(
				'data_type' => 'integer'
			),
			'EMP_PAYED_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array(
					'=this.EMP_PAYED_ID' => 'ref.ID'
				)
			),
			'PAY_SYSTEM_ID' => array(
				'data_type' => 'integer'
			),
			'PAY_SYSTEM' => array(
				'data_type' => 'PaySystem',
				'reference' => array(
					'=this.PAY_SYSTEM_ID' => 'ref.ID'
				)
			),
			'CANCELED' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),
			'EMP_CANCELED_ID' => array(
				'data_type' => 'integer'
			),
			'EMP_CANCELED_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array(
					'=this.EMP_CANCELED_ID' => 'ref.ID'
				)
			),
			'DATE_CANCELED' => array(
				'data_type' => 'datetime'
			),
			'DATE_CANCELED_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DATE_CANCELED'
				)
			),
			'REASON_CANCELED' => array(
				'data_type' => 'string'
			),
			'SUM_PAID' => array(
				'data_type' => 'float'
			),
			'SUM_PAID_FORREP' => array(
				'data_type' => 'float',
				'expression' => array(
					'CASE WHEN %s = \'Y\' THEN %s ELSE %s END', 'PAYED', 'PRICE', 'SUM_PAID'
				)
			),
			'PAY_VOUCHER_NUM' => array(
				'data_type' => 'string'
			),
			'PAY_VOUCHER_DATE' => array(
				'data_type' => 'datetime'
			),
			'PAY_VOUCHER_DATE_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'PAY_VOUCHER_DATE'
				)
			),
			'DELIVERY_DOC_NUM' => array(
				'data_type' => 'string'
			),
			'DELIVERY_DOC_DATE' => array(
				'data_type' => 'datetime'
			),
			'DELIVERY_DOC_DATE_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DELIVERY_DOC_DATE'
				)
			),
			'LID' => array(
				'data_type' => 'string'
			),
			'PERSON_TYPE_ID' => array(
				'data_type' => 'string'
			),
			'USER_ID' => array(
				'data_type' => 'integer'
			),
			'BUYER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array(
					'=this.USER_ID' => 'ref.ID'
				)
			),
			'DEDUCTED' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			)
		);
	}
}
