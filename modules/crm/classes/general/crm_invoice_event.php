<?php

if (!CModule::IncludeModule('sale'))
	return;

IncludeModuleLangFile(__FILE__);

class CCrmInvoiceEvent extends CSaleOrderChange
{
	protected static $eventTypes = array();

	public static function getTypes()
	{
		if (empty(self::$eventTypes))
		{
			self::$eventTypes = array(
				'ORDER_COMMENTED' => GetMessage('CRM_INVOICE_EVENT_NAME_COMMENTED'),
				'ORDER_STATUS_CHANGED' => GetMessage('CRM_INVOICE_EVENT_NAME_STATUS_CHANGED'),
				'ORDER_PAYMENT_SYSTEM_CHANGED' => GetMessage('CRM_INVOICE_EVENT_NAME_PAYMENT_SYSTEM_CHANGED'),
				'ORDER_PAYMENT_VOUCHER_CHANGED' => GetMessage('CRM_INVOICE_EVENT_NAME_PAYMENT_VOUCHER_CHANGED'),
				'ORDER_PERSON_TYPE_CHANGED' => GetMessage('CRM_INVOICE_EVENT_NAME_PERSON_TYPE_CHANGED'),
				'ORDER_USER_DESCRIPTION_CHANGED' => GetMessage('CRM_INVOICE_EVENT_NAME_USER_DESCRIPTION_CHANGED'),
				'ORDER_PRICE_CHANGED' => GetMessage('CRM_INVOICE_EVENT_NAME_PRICE_CHANGED'),
				'ORDER_ADDED' => GetMessage('CRM_INVOICE_EVENT_NAME_ADDED'),
				'BASKET_ADDED' => GetMessage('CRM_INVOICE_EVENT_NAME_PRODUCT_ADDED'),
				'BASKET_REMOVED' => GetMessage('CRM_INVOICE_EVENT_NAME_PRODUCT_REMOVED'),
				'BASKET_QUANTITY_CHANGED' => GetMessage('CRM_INVOICE_EVENT_NAME_PRODUCT_QUANTITY_CHANGED'),
				'BASKET_PRICE_CHANGED' => GetMessage('CRM_INVOICE_EVENT_NAME_PRODUCT_PRICE_CHANGED')
			);
		}

		return self::$eventTypes;
	}

	public static function getName($typeCode)
	{
		if (empty(self::$eventTypes))
			self::getTypes();

		return self::$eventTypes[$typeCode];
	}

	public function GetRecordDescription($type, $data)
	{
		foreach (CCrmInvoiceEventFormat::$arOperationTypes as $typeCode => $arInfo)
		{
			if ($type == $typeCode)
			{
				if (isset($arInfo["FUNCTION"]) && is_callable(array("CCrmInvoiceEventFormat", $arInfo["FUNCTION"])))
				{
					$arResult = call_user_func_array(array("CCrmInvoiceEventFormat", $arInfo["FUNCTION"]), array(unserialize($data)));
					$arResult["NAME"] = self::getName($type);
					return $arResult;
				}
			}
		}

		return false;
	}
}

class CCrmInvoiceEventFormat extends CSaleOrderChangeFormat
{
	public static $arOperationTypes = array(
		'ORDER_COMMENTED' => array(
			'TRIGGER_FIELDS' => array('COMMENTS'),
			'FUNCTION' => 'FormatInvoiceCommented',
			'DATA_FIELDS' => array('COMMENTS')
		),
		'ORDER_STATUS_CHANGED' => array(
			'TRIGGER_FIELDS' => array('STATUS_ID'),
			'FUNCTION' => 'FormatInvoiceStatusChanged',
			'DATA_FIELDS' => array('STATUS_ID')
		),
		'ORDER_PAYMENT_SYSTEM_CHANGED' => array(
			'TRIGGER_FIELDS' => array('PAY_SYSTEM_ID'),
			'FUNCTION' => 'FormatInvoicePaymentSystemChanged',
			'DATA_FIELDS' => array('PAY_SYSTEM_ID')
		),
		'ORDER_PAYMENT_VOUCHER_CHANGED' => array(
			'TRIGGER_FIELDS' => array('PAY_VOUCHER_NUM'),
			'FUNCTION' => 'FormatOrderPaymentVoucherChanged',
			'DATA_FIELDS' => array('PAY_VOUCHER_NUM', 'PAY_VOUCHER_DATE')
		),
		'ORDER_PERSON_TYPE_CHANGED' => array(
			'TRIGGER_FIELDS' => array('PERSON_TYPE_ID'),
			'FUNCTION' => 'FormatInvoicePersonTypeChanged',
			'DATA_FIELDS' => array('PERSON_TYPE_ID')
		),
		'ORDER_USER_DESCRIPTION_CHANGED' => array(
			'TRIGGER_FIELDS' => array('USER_DESCRIPTION'),
			'FUNCTION' => 'FormatInvoiceUserDescriptionChanged',
			'DATA_FIELDS' => array('USER_DESCRIPTION')
		),
		'ORDER_PRICE_CHANGED' => array(
			'TRIGGER_FIELDS' => array('PRICE'),
			'FUNCTION' => 'FormatInvoicePriceChanged',
			'DATA_FIELDS' => array('PRICE', 'CURRENCY')
		),
		'ORDER_ADDED' => array(
			'TRIGGER_FIELDS' => array(),
			'FUNCTION' => 'FormatOrderAdded',
			'DATA_FIELDS' => array()
		),

		'BASKET_ADDED' => array(
			'ENTITY' => 'BASKET',
			'TRIGGER_FIELDS' => array(),
			'FUNCTION' => 'FormatBasketAdded',
			'DATA_FIELDS' => array('PRODUCT_ID', 'NAME', 'QUANTITY')
		),
		'BASKET_REMOVED' => array(
			'ENTITY' => 'BASKET',
			'TRIGGER_FIELDS' => array(),
			'FUNCTION' => 'FormatBasketRemoved',
			'DATA_FIELDS' => array('PRODUCT_ID', 'NAME')
		),
		'BASKET_QUANTITY_CHANGED' => array(
			'ENTITY' => 'BASKET',
			'TRIGGER_FIELDS' => array('QUANTITY'),
			'FUNCTION' => 'FormatBasketQuantityChanged',
			'DATA_FIELDS' => array('PRODUCT_ID', 'NAME', 'QUANTITY')
		),
		'BASKET_PRICE_CHANGED' => array(
			'ENTITY' => 'BASKET',
			'TRIGGER_FIELDS' => array('PRICE'),
			'FUNCTION' => 'FormatBasketPriceChanged',
			'DATA_FIELDS' => array('PRODUCT_ID', 'NAME', 'PRICE', 'CURRENCY')
		)
	);

	public static function FormatInvoiceCommented($arData)
	{

		$info = GetMessage("CRM_INVOICE_EVENT_INFO_COMMENTED");
		foreach ($arData as $param => $value)
			$info = str_replace("#".$param."#", $value, $info);

		return array(
			"INFO" => $info
		);
	}

	public static function FormatInvoiceStatusChanged($arData)
	{
		$info = GetMessage("CRM_INVOICE_EVENT_INFO_STATUS_CHANGED");
		foreach ($arData as $param => $value)
		{
			if ($param == "STATUS_ID")
			{
				$res = CCrmStatusInvoice::getByID($value);
				$value = "\"".$res["NAME"]."\"";
			}

			$info = str_replace("#".$param."#", $value, $info);
		}

		return array(
			"INFO" => $info
		);
	}

	public static function FormatInvoicePaymentSystemChanged($arData)
	{
		$info = GetMessage("CRM_INVOICE_EVENT_INFO_PAYMENT_SYSTEM_CHANGED");
		foreach ($arData as $param => $value)
		{
			if ($param == "PAY_SYSTEM_ID")
			{
				$res = CSalePaySystem::GetByID($value);
				$value = "\"".$res["NAME"]."\"";
			}

			$info = str_replace("#".$param."#", $value, $info);
		}

		return array(
			"INFO" => $info
		);
	}

	// FormatOrderPaymentVoucherChanged - used from parent

	public static function FormatInvoicePersonTypeChanged($arData)
	{
		$info = GetMessage("CRM_INVOICE_EVENT_INFO_PERSON_TYPE_CHANGED");
		foreach ($arData as $param => $value)
		{
			if ($param == "PERSON_TYPE_ID")
			{
				$res = CSalePersonType::GetByID($value);
				$value = "\"".$res["NAME"]."\"";
				if ($res["NAME"] === 'CRM_CONTACT')
					$value = '"'.GetMessage('CRM_PERSON_TYPE_CONTACT').'"';
				else if ($res["NAME"] === 'CRM_COMPANY')
					$value = '"'.GetMessage('CRM_PERSON_TYPE_COMPANY').'"';
			}

			$info = str_replace("#".$param."#", $value, $info);
		}

		return array(
			"INFO" => $info
		);
	}

	public static function FormatInvoiceUserDescriptionChanged($arData)
	{
		$info = GetMessage("CRM_INVOICE_EVENT_INFO_USER_DESCRIPTION_CHANGED");

		foreach ($arData as $param => $value)
			$info = str_replace("#".$param."#", $value, $info);

		return array(
			"INFO" => $info
		);
	}

	public static function FormatInvoicePriceChanged($arData)
	{
		$info = GetMessage(
			"CRM_INVOICE_EVENT_INFO_PRICE_CHANGED",
			array("#AMOUNT#" => CurrencyFormat($arData["PRICE"], $arData["CURRENCY"]))
		);

		return array(
			"INFO" => $info
		);
	}

	// FormatOrderAdded - used from parent

	// FormatBasketAdded - used from parent
	// FormatBasketRemoved - used from parent
	// FormatBasketQuantityChanged - used from parent
}