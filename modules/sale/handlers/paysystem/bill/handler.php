<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Request;
use Bitrix\Sale;
use Bitrix\Sale\PaySystem;

class BillHandler extends PaySystem\BaseServiceHandler
{
	/**
	 * @param Sale\Payment $payment
	 * @param Request|null $request
	 * @return PaySystem\ServiceResult
	 */
	public function initiatePay(Sale\Payment $payment, Request $request = null)
	{
		/** @var \Bitrix\Sale\PaymentCollection $paymentCollection */
		$paymentCollection = $payment->getCollection();

		/** @var \Bitrix\Sale\Order $order */
		$order = $paymentCollection->getOrder();

		$sumPaid = $paymentCollection->getPaidSum();
		$template = 'template';

//		if ($sumPaid + $payment->getSum() < $order->getPrice())
//			$template .= '_prepay';

		if (array_key_exists('pdf', $_REQUEST))
			$template .= '_pdf';

		$accountNumber = (IsModuleInstalled('intranet')) ? $order->getField('ACCOUNT_NUMBER') : $payment->getField('ACCOUNT_NUMBER');
		$this->setExtraParams(array('ACCOUNT_NUMBER' => $accountNumber));

		return $this->showTemplate($payment, $template);
	}

	/**
	 * @return array
	 */
	static public function getCurrencyList()
	{
		return array('RUB');
	}

	/**
	 * @return bool
	 */
	static public function isAffordPdf()
	{
		return true;
	}


}