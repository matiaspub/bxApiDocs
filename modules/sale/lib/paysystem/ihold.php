<?php

namespace Bitrix\Sale\PaySystem;

use Bitrix\Sale\Payment;

interface IHold
{
	static public function cancel(Payment $payment);

	static public function confirm(Payment $payment);
}
