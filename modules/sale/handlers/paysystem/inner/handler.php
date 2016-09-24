<?php

namespace Sale\Handlers\PaySystem;


use Bitrix\Main\Request;
use Bitrix\Sale\Order;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PriceMaths;
use Bitrix\Sale\Result;
use Bitrix\Sale\Internals\UserBudgetPool;
use Bitrix\Main\Entity\EntityError;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\ResultError;

Loc::loadMessages(__FILE__);

class InnerHandler extends PaySystem\BaseServiceHandler implements PaySystem\IRefund
{
	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return PaySystem\ServiceResult
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	static public function initiatePay(Payment $payment, Request $request = null)
	{
		$result = new PaySystem\ServiceResult();

		/** @var \Bitrix\Sale\PaymentCollection $paymentCollection */
		$paymentCollection = $payment->getCollection();

		if ($paymentCollection)
		{
			/** @var \Bitrix\Sale\Order $order */
			$order = $paymentCollection->getOrder();
			if ($order)
			{
				$res = $payment->setPaid('Y');
				if ($res->isSuccess())
				{
					$res = $order->save();
					if ($res)
						$result->addErrors($res->getErrors());
				}
				else
				{
					$result->addErrors($res->getErrors());
				}
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	static public function getCurrencyList()
	{
		return array();
	}

	/**
	 * @param Payment $payment
	 * @param int $refundableSum
	 * @return PaySystem\ServiceResult
	 */
	public function refund(Payment $payment, $refundableSum)
	{
		$result = new PaySystem\ServiceResult();

		/** @var \Bitrix\Sale\PaymentCollection $paymentCollection */
		$paymentCollection = $payment->getCollection();

		/** @var \Bitrix\Sale\Order $order */
		$order = $paymentCollection->getOrder();

		if ($this->isUserBudgetLock($order))
		{
			$result->addError(new EntityError(Loc::getMessage('ORDER_PSH_INNER_ERROR_USER_BUDGET_LOCK')));
			return $result;
		}

		UserBudgetPool::addPoolItem($order, $refundableSum, UserBudgetPool::BUDGET_TYPE_ORDER_UNPAY, $payment);

		return $result;
	}

	/**
	 * @param Payment $payment
	 * @return PaySystem\ServiceResult
	 */
	public function creditNoDemand(Payment $payment)
	{
		$result = new PaySystem\ServiceResult();

		/** @var \Bitrix\Sale\PaymentCollection $collection */
		$collection = $payment->getCollection();

		/** @var \Bitrix\Sale\Order $order */
		$order = $collection->getOrder();

		if ($this->isUserBudgetLock($order))
		{
			$result->addError(new EntityError(Loc::getMessage('ORDER_PSH_INNER_ERROR_USER_BUDGET_LOCK')));
			return $result;
		}

		$paymentSum = PriceMaths::roundByFormatCurrency($payment->getSum(), $order->getCurrency());
		$userBudget = PriceMaths::roundByFormatCurrency(UserBudgetPool::getUserBudgetByOrder($order), $order->getCurrency());

		if($userBudget >= $paymentSum)
			UserBudgetPool::addPoolItem($order, ( $paymentSum * -1 ), UserBudgetPool::BUDGET_TYPE_ORDER_PAY, $payment);
		else
			$result->addError(new EntityError(Loc::getMessage('ORDER_PSH_INNER_ERROR_INSUFFICIENT_MONEY')));

		return $result;
	}

	/**
	 * @param Payment $payment
	 * @return PaySystem\ServiceResult
	 */
	public function debitNoDemand(Payment $payment)
	{
		return $this->refund($payment, $payment->getSum());
	}

	/**
	 * @param Order $order
	 * @return bool
	 */
	private function isUserBudgetLock(Order $order)
	{
		if ($userAccount = \CSaleUserAccount::GetByUserId($order->getUserId(), $order->getCurrency()))
			 return $userAccount['LOCKED'] == 'Y';

		return false;
	}
}