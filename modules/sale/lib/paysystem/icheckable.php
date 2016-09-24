<?php

namespace Bitrix\Sale\PaySystem;

use Bitrix\Sale\Payment;

interface ICheckable
{
	/**
	 * @param Payment $payment
	 * @return ServiceResult
	 */
	static public function check(Payment $payment);
}
