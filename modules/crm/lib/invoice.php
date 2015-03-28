<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2013-2013 Bitrix
 */
namespace Bitrix\Crm;

if (!\CModule::IncludeModule('report'))
	return;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class InvoiceTable extends Entity\DataManager
{
	private static $STATUS_INIT = false;
	private static $WORK_STATUSES = array();
	private static $CANCEL_STATUSES = array();

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
			'PRICE' => array(
				'data_type' => 'float'
			),
			'PRICE_PAYED' => array(
				'data_type' => 'float',
				'expression' => array(
					'CASE WHEN %s = \'P\' THEN %s ELSE 0 END',
					'STATUS_ID', 'PRICE'
				),
				'values' => array(0, 1)
			),
			'STATUS_ID' => array(
				'data_type' => 'string'
			),
			'STATUS_BY' => array(
				'data_type' => 'InvoiceStatus',
				'reference' => array(
					'=this.STATUS_ID' => 'ref.ID'
				)
			),
			'PAY_SYSTEM_ID' => array(
				'data_type' => 'integer'
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
			'LID' => array(
				'data_type' => 'string'
			),
			'PERSON_TYPE_ID' => array(
				'data_type' => 'string'
			),
			'IS_PAYED' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = \'P\' THEN 1 ELSE 0 END',
					'STATUS_ID'
				),
				'values' => array(0, 1)
			),
			'INVOICE_UTS' => array(
				'data_type' => 'InvoiceUts',
				'reference' => array(
					'=this.ID' => 'ref.VALUE_ID'
				)
			),
			'ACCOUNT_NUMBER' => array(
				'data_type' => 'string'
			),
			'ORDER_TOPIC' => array(
				'data_type' => 'string'
			),
			'DATE_BILL' => array(
				'data_type' => 'datetime'
			),
			'DATE_BILL_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DATE_BILL'
				)
			),
			'DATE_PAY_BEFORE' => array(
				'data_type' => 'datetime'
			),
			'DATE_PAY_BEFORE_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DATE_PAY_BEFORE'
				)
			),
			'DATE_MARKED' => array(
				'data_type' => 'datetime'
			),
			'DATE_MARKED_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DATE_MARKED'
				)
			),
			'REASON_MARKED' => array(
				'data_type' => 'string'
			),
			'RESPONSIBLE_ID' => array(
				'data_type' => 'integer'
			),
			'ASSIGNED_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.RESPONSIBLE_ID' => 'ref.ID')
			),
			'CURRENCY' => array(
				'data_type' => 'string'
			),
			'DATE_BEGIN_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction($DB->IsNull('%s', '%s')),
					'DATE_BILL', 'DATE_INSERT'
				)
			)
		);
	}

	private static function ensureStatusesLoaded()
	{
		if(self::$STATUS_INIT)
		{
			return;
		}

		global $DB;

		$paidStatus = null;
		$arStatuses = array();
		$arStatuses = \CCrmInvoice::GetStatusList();
		foreach ($arStatuses as $statusID => $arStatus)
		{
			if(!$paidStatus && strval($statusID) === 'P')
			{
				$paidStatus = $arStatus;
				continue;
			}
		}

		self::$WORK_STATUSES = array();
		self::$CANCEL_STATUSES = array();

		if($paidStatus)
		{
			$paidStatusSort = intval($paidStatus['SORT']);
			foreach($arStatuses as $statusID => $arStatus)
			{
				$sort = intval($arStatus['SORT']);
				if($sort < $paidStatusSort)
				{
					self::$WORK_STATUSES[] = '\''.$DB->ForSql($statusID).'\'';
				}
				elseif($sort > $paidStatusSort)
				{
					self::$CANCEL_STATUSES[] = '\''.$DB->ForSql($statusID).'\'';
				}
			}
		}

		self::$STATUS_INIT = true;
	}

	public static function processQueryOptions(&$options)
	{
		$stub = '_BX_STATUS_STUB_';
		self::ensureStatusesLoaded();
		$options['WORK_STATUS_IDS'] = '('.(!empty(self::$WORK_STATUSES) ? implode(',', self::$WORK_STATUSES) : "'$stub'").')';
		$options['CANCEL_STATUS_IDS'] = '('.(!empty(self::$CANCEL_STATUSES) ? implode(',', self::$CANCEL_STATUSES) : "'$stub'").')';
	}
}
