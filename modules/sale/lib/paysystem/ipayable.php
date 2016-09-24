<?php

namespace Bitrix\Sale\PaySystem;

use Bitrix\Sale\Payment;

interface IPayable
{
	static public function getPrice(Payment $payment);
}
