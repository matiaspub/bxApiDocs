<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;

Loc::loadMessages(__FILE__);

/**
 * Class CashHandler
 */
class CashHandler extends PaySystem\BaseServiceHandler
{
	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return PaySystem\ServiceResult
	 */
	static public function initiatePay(Payment $payment, Request $request = null)
	{
		return new PaySystem\ServiceResult();
	}

	/**
	 * @return array
	 */
	static public function getCurrencyList()
	{
		return array();
	}

}