<?php

namespace Bitrix\Sale\Internals;

use Bitrix\Sale\Internals\PaySystemActionTable;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Entity\EntityError;
use Bitrix\Main\Entity\Result;
use Bitrix\Sale\Provider;
use Bitrix\Sale\Payment;
use Bitrix\Sale\Order;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class PaySystemInner
{
	const OPERATION_DEBIT = 0; //from account
	const OPERATION_CREDIT = 1; //to account
	const OPERATION_RETURN = 2; //to account (return)

	const ACTION_FILE_TEXT = 'INNER_BUDGET';
	const CACHE_ID = "BITRIX_SALE_INNER_BUDGET_ID";
	const TTL = 31536000;

	/**
	 * @param bool $useCache
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getId($useCache = true)
	{
		$id = 0;
		$cacheManager = \Bitrix\Main\Application::getInstance()->getManagedCache();
		$ttl = $useCache ? self::TTL : 0;

		if($cacheManager->read($ttl, self::CACHE_ID))
			$id = $cacheManager->get(self::CACHE_ID);

		if(intval($id) <= 0)
		{
			$dbRes = PaySystemActionTable::getList(array(
				'filter' => array('ACTION_FILE' => self::ACTION_FILE_TEXT),
				'select' => array('PAY_SYSTEM_ID')
				)
			);

			if($res = $dbRes->fetch())
			{
				$id = $res['PAY_SYSTEM_ID'];
				$cacheManager->set(self::CACHE_ID, $id);
			}
		}

		return intval($id);
	}

	/**
	 * @param Order $order
	 * @param Payment $payment
	 * @param $operation
	 * @return Result
	 * @throws \Exception
	 */
	public static function createOperation(Order &$order, Payment &$payment, $operation)
	{
		$result = new Result();
		$paymentSum = $payment->getSum();

		if($operation == self::OPERATION_DEBIT)
		{
			$userBudget = UserBudgetPool::getUserBudgetByOrder($order);

			if($userBudget >= $paymentSum)
			{
				UserBudgetPool::addPoolItem($order, ( $paymentSum * -1 ), UserBudgetPool::BUDGET_TYPE_ORDER_PAY, $payment);
//				$payment->setField('PAID', 'Y');
			}
			else
			{
				$result->addError(new EntityError(Loc::getMessage('ORDER_PS_INNER_ERROR_INSUFFICIENT_MONEY')));
			}
		}
		elseif($operation == self::OPERATION_CREDIT)
		{
			UserBudgetPool::addPoolItem($order, ( $paymentSum ), UserBudgetPool::BUDGET_TYPE_ORDER_UNPAY, $payment);

//			$payment->setField('PAID', 'N');
		}
		elseif($operation == self::OPERATION_RETURN)
		{
			$sumPaid = $order->getSumPaid();
			$sumTrans = UserBudgetPool::getUserBudgetTransForOrder($order);
			$finalSumPaid = $paymentSum + $sumTrans;

			if ($finalSumPaid > 0)
			{
				$paymentSum = $paymentSum - $finalSumPaid;
			}

//			Internals\UserBudgetPool::addPoolItem($order->getUserId(), ( $paymentSum ), UserBudgetPool::BUDGET_TYPE_CANCEL_RETURN, $order, $payment);
//			$payment->setField('PAID', 'N');
			$payment->setField('IS_RETURN', 'Y');
		}
		else
		{
			throw new InvalidOperationException('Wrong operation type!');
		}

		return $result;
	}

	/**
	 * @return int
	 */
	public static function add()
	{
		$id = self::getId(false);

		if($id > 0)
			return $id;

		$result = 0;

		$res =  PaySystemTable::add(array(
			'NAME' => Loc::getMessage('ORDER_PS_INNER_NAME'),
			'DESCRIPTION' => Loc::getMessage('ORDER_PS_INNER_DESCRIPTION'),
			'SORT' => 10,
			'LID' => '',
			'CURRENCY' => ''
		));

		if($res->isSuccess())
		{
			$cacheManager = \Bitrix\Main\Application::getInstance()->getManagedCache();
			$cacheManager->set(self::CACHE_ID, $res->getId());

			$res = PaySystemActionTable::add(array(
				'PAY_SYSTEM_ID' => $res->getId(),
				'PERSON_TYPE_ID' => 0,
				'NAME' => Loc::getMessage('ORDER_PS_INNER_NAME'),
				'ACTION_FILE' => self::ACTION_FILE_TEXT
				)
			);

			if($res->isSuccess())
				$result =  $res->getId();
		}

		return $result;
	}
}