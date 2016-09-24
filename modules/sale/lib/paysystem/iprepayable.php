<?php

namespace Bitrix\Sale\PaySystem;

use Bitrix\Main\Request;
use Bitrix\Sale\Payment;

interface IPrePayable
{
	static public function initPrePayment(Payment $payment = null, Request $request);

	public function getProps();

	static public function payOrder($orderData = array());

	static public function setOrderConfig($orderData = array());

	static public function basketButtonAction($orderData);
}
