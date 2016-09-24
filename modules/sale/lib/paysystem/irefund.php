<?php

namespace Bitrix\Sale\PaySystem;

use Bitrix\Sale\Payment;

interface IRefund
{
	static public function refund(Payment $payment, $refundableSum);
}
