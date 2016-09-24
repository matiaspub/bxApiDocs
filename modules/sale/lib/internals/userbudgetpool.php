<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;

class UserBudgetPool
{
	protected static $userBudgetPool = array();

	protected $items = array();

	const BUDGET_TYPE_ORDER_CANCEL_PART = 'ORDER_CANCEL_PART'; //
	const BUDGET_TYPE_ORDER_UNPAY = 'ORDER_UNPAY'; //
	const BUDGET_TYPE_ORDER_PART_RETURN = 'ORDER_PART_RETURN'; //
	const BUDGET_TYPE_OUT_CHARGE_OFF = 'OUT_CHARGE_OFF'; //
	const BUDGET_TYPE_EXCESS_SUM_PAID = 'EXCESS_SUM_PAID'; //
	const BUDGET_TYPE_MANUAL = 'MANUAL'; //
	const BUDGET_TYPE_ORDER_PAY = 'ORDER_PAY'; //
	const BUDGET_TYPE_ORDER_PAY_PART = 'ORDER_PAY_PART'; //

	static public function __construct()
	{
	}

	/**
	 * @param $sum
	 * @param $type
	 * @param Sale\Order $order
	 * @param Sale\Payment $payment
	 */
	public function add($sum, $type, Sale\Order $order, Sale\Payment $payment = null)
	{
		$fields = array(
			"SUM" => $sum,
			"CURRENCY" => $order->getCurrency(),
			"TYPE" => $type,
			"ORDER" => $order,
		);

		if ($payment !== null)
			$fields['PAYMENT'] = $payment;

		$this->items[] = $fields;

	}

	/**
	 * @return array
	 */
	public function get()
	{
		if (isset($this->items))
			return $this->items;

		return false;
	}

	/**
	 * @param $index
	 * @return bool
	 */
	public function delete($index)
	{
		if (isset($this->items) && isset($this->items[$index]))
		{
			unset($this->items[$index]);
			return true;
		}

		return false;
	}

	/**
	 * @param $key
	 * @return UserBudgetPool
	 */
	public static function getUserBudgetPool($key)
	{
		if (!isset(static::$userBudgetPool[$key]))
			static::$userBudgetPool[$key] = new static();

		return static::$userBudgetPool[$key];
	}

	/**
	 * @param Sale\Order $order
	 * @param $value
	 * @param $type
	 * @param Sale\Payment $payment
	 */
	public static function addPoolItem(Sale\Order $order, $value, $type, Sale\Payment $payment = null)
	{
		if (floatval($value) == 0)
			return;

		$key = $order->getUserId();
		$pool = static::getUserBudgetPool($key);
		$pool->add($value, $type, $order, $payment);
	}

	/**
	 * @param $userId
	 * @return Sale\Result
	 */
	public static function onUserBudgetSave($userId)
	{
		$result = new Sale\Result();

		$pool = static::getUserBudgetPool($userId);
		foreach ($pool->get() as $key => $budgetDat)
		{

			$orderId = null;
			$paymentId = null;

			if (isset($budgetDat['ORDER'])
				&& ($budgetDat['ORDER'] instanceof Sale\OrderBase))
			{
				$orderId = $budgetDat['ORDER']->getId();
			}

			if (isset($budgetDat['PAYMENT'])
				&& ($budgetDat['PAYMENT'] instanceof Sale\Payment))
			{
				$paymentId = $budgetDat['PAYMENT']->getId();
			}

//			if ($budgetDat['TYPE'] == Internals\UserBudgetPool::BUDGET_TYPE_ORDER_PAY_PART
//				|| $budgetDat['TYPE'] == Internals\UserBudgetPool::BUDGET_TYPE_ORDER_PAY)
//			{
//				if (!\CSaleUserAccount::Pay($userId, ($budgetDat['SUM'] * -1), $budgetDat['CURRENCY'], $orderId, false, $paymentId))
//				{
//					$result->addError( new ResultError(Loc::getMessage("SALE_PROVIDER_USER_BUDGET_".$budgetDat['TYPE']."_ERROR"), "SALE_PROVIDER_USER_BUDGET_".$budgetDat['TYPE']."_ERROR") );
//				}
//			}
//			else
//			{
			if (!\CSaleUserAccount::UpdateAccount($userId, $budgetDat['SUM'], $budgetDat['CURRENCY'], $budgetDat['TYPE'], $orderId, '', $paymentId))
			{
				$result->addError( new Sale\ResultError(Loc::getMessage("SALE_PROVIDER_USER_BUDGET_".$budgetDat['TYPE']."_ERROR"), "SALE_PROVIDER_USER_BUDGET_".$budgetDat['TYPE']."_ERROR") );
			}
//			}

			$pool->delete($key);
		}

		return $result;
	}

	/**
	 * @param Sale\Order $order
	 * @return int
	 */
	public static function getUserBudgetTransForOrder(Sale\Order $order)
	{
		$ignoreTypes = array(
			static::BUDGET_TYPE_ORDER_PAY
		);
		$sumTrans = 0;

		if ($order->getId() > 0)
		{
			$resTrans = \CSaleUserTransact::GetList(
				array("TRANSACT_DATE" => "DESC"),
				array(
					"ORDER_ID" => $order->getId(),
				),
				false,
				false,
				array("AMOUNT", "CURRENCY", "DEBIT")
			);
			while ($transactDat = $resTrans->Fetch())
			{
				if ($transactDat['DEBIT'] == "Y")
				{
					$sumTrans += $transactDat['AMOUNT'];
				}
				else
				{
					$sumTrans -= $transactDat['AMOUNT'];
				}
			}
		}

		if ($userBudgetPool = static::getUserBudgetPool($order->getUserId()))
		{
			foreach ($userBudgetPool->get() as $userBudgetDat)
			{
				if (in_array($userBudgetDat['TYPE'], $ignoreTypes))
					continue;

				$sumTrans += $userBudgetDat['SUM'];
			}
		}

		return $sumTrans;
	}

	/**
	 * @param Sale\Order $order
	 * @return int
	 */
	public static function getUserBudgetByOrder(Sale\Order $order)
	{
		$budget = static::getUserBudget($order->getUserId(), $order->getCurrency());
		if ($userBudgetPool = static::getUserBudgetPool($order->getUserId()))
		{
			foreach ($userBudgetPool->get() as $userBudgetDat)
			{
				$budget += $userBudgetDat['SUM'];
			}
		}

		return $budget;
	}

	/**
	 * @param $userId
	 * @param $currency
	 * @return float|null
	 */
	public static function getUserBudget($userId, $currency)
	{
		$budget = null;
		if ($userAccount = \CSaleUserAccount::GetByUserId($userId, $currency))
		{
			if ($userAccount['LOCKED'] != 'Y')
				$budget = floatval($userAccount['CURRENT_BUDGET']);
		}

		return $budget;
	}
}