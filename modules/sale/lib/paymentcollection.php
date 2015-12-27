<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Main;
use Bitrix\Sale\Internals;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class PaymentCollection
	extends Internals\EntityCollection
{
	/** @var OrderBase */
	protected $order;

	/**
	 * @return Order
	 */
	protected function getEntityParent()
	{
		return $this->getOrder();
	}

	public function createItem(PaySystemService $paySystemService = null)
	{
		$payment = Payment::create($this, $paySystemService);
		$this->addItem($payment);

		return $payment;
	}

	/**
	 * @param Payment $payment
	 * @return bool|void
	 */
	public function addItem(Payment $payment)
	{
		/** @var Payment $payment */
		$payment = parent::addItem($payment);

		$order = $this->getOrder();
		return $order->onPaymentCollectionModify(EventActions::ADD, $payment);
	}

	/**
	 * @internal
	 *
	 * @param $index
	 * @return bool
	 */
	public function deleteItem($index)
	{
		$oldItem = parent::deleteItem($index);

		/** @var Order $order */
		$order = $this->getOrder();
		return $order->onPaymentCollectionModify(EventActions::DELETE, $oldItem);
	}

	public function onItemModify(Payment $item, $name = null, $oldValue = null, $value = null)
	{
		/** @var Order $order */
		$order = $this->getOrder();
		return $order->onPaymentCollectionModify(EventActions::UPDATE, $item, $name, $oldValue, $value);
	}

	/**
	 * @return bool
	 */
	public function isPaid()
	{
		if (!empty($this->collection) && is_array($this->collection))
		{
			/** @var Payment $payment */
			foreach ($this->collection as $payment)
			{
				if (!$payment->isPaid())
					return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * @param $name
	 * @param $oldValue
	 * @param $value
	 * @return Result
	 */
	public function onOrderModify($name, $oldValue, $value)
	{
		$result = new Result();

		switch($name)
		{
			case "CANCELED":

				if ($value == "Y")
				{
					$isPaid = false;

					/** @var Payment $payment */
					foreach ($this->collection as $payment)
					{
						if ($payment->isPaid())
						{
							$isPaid = true;
							break;
						}
					}

					if ($isPaid)
					{
						$result->addError(new ResultError(Loc::getMessage('SALE_ORDER_CANCEL_PAYMENT_EXIST_ACTIVE'), 'SALE_ORDER_CANCEL_PAYMENT_EXIST_ACTIVE'));
					}
				}

			break;
		}

		return $result;
	}

	/**
	 * @param OrderBase $order
	 */
	public function setOrder(OrderBase $order)
	{
		$this->order = $order;
	}

	/**
	 * @return Order
	 */
	public function getOrder()
	{
		return $this->order;
	}

	/**
	 * @param OrderBase $order
	 * @return PaymentCollection
	 */
	public static function load(OrderBase $order)
	{
		/** @var PaymentCollection $paymentCollection */
		$paymentCollection = new static();
		$paymentCollection->setOrder($order);

		if ($order->getId() > 0)
		{
			$paymentList = Payment::loadForOrder($order->getId());
			/** @var Payment $payment */
			foreach ($paymentList as $payment)
			{
				$payment->setCollection($paymentCollection);
				$paymentCollection->addItem($payment);
			}
		}

		return $paymentCollection;
	}


	/**
	 * @return float
	 */
	public function getPaidSum()
	{
		$sum = 0;
		if (!empty($this->collection) && is_array($this->collection))
		{
			/** @var Payment $payment */
			foreach ($this->collection as $payment)
			{
				if ($payment->getField('PAID') == "Y")
				{
					$sum += $payment->getSum();
				}
			}
		}

		return $sum;
	}

	/**
	 * @return float
	 */
	public function getSum()
	{
		$sum = 0;
		if (!empty($this->collection) && is_array($this->collection))
		{
			/** @var Payment $payment */
			foreach ($this->collection as $payment)
			{
				$sum += $payment->getSum();
			}
		}

		return $sum;
	}

	public function hasPaidPayment()
	{
		if (!empty($this->collection) && is_array($this->collection))
		{
			/** @var Payment $payment */
			foreach ($this->collection as $payment)
			{
				if ($payment->getField('PAID') == "Y")
					return true;
			}
		}

		return false;
	}

	public function dump($i)
	{
		$s = '';
		/** @var Payment $item */
		foreach ($this->collection as $item)
		{
			$s .= $item->dump($i);
		}
		return $s;
	}

	/**
	 * @return Entity\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectNotFoundException
	 * @throws \Exception
	 */
	public function save()
	{
		$result = new Entity\Result();

		$itemsFromDb = array();
		if ($this->getOrder()->getId() > 0)
		{
			$itemsFromDbList = Internals\PaymentTable::getList(
				array(
					"filter" => array("ORDER_ID" => $this->getOrder()->getId()),
					"select" => array("ID")
				)
			);
			while ($itemsFromDbItem = $itemsFromDbList->fetch())
				$itemsFromDb[$itemsFromDbItem["ID"]] = true;
		}

		/** @var Payment $payment */
		foreach ($this->collection as $payment)
		{
			if ($payment->isInner() && $payment->getSum() == 0 && $payment->getId() == 0)
				$payment->delete();
		}

		/** @var Payment $payment */
		foreach ($this->collection as $payment)
		{
			$r = $payment->save();
			if (!$r->isSuccess())
				$result->addErrors($r->getErrors());

			if (isset($itemsFromDb[$payment->getId()]))
				unset($itemsFromDb[$payment->getId()]);
		}

		foreach ($itemsFromDb as $k => $v)
		{
			Internals\PaymentTable::delete($k);
			/** @var Order $order */
			if (!$order = $this->getOrder())
			{
				throw new Main\ObjectNotFoundException('Entity "Order" not found');
			}

			if ($order->getId() > 0)
			{
				OrderHistory::addAction('PAYMENT', $order->getId(), 'PAYMENT_REMOVE', $k);
			}

		}

		return $result;
	}

	/**
	 * @return Payment|bool
	 * @throws Main\ObjectNotFoundException
	 */
	public function getInnerPayment()
	{
		/** @var Order $order */
		if (!$order = $this->getOrder())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}

		if ($paySystemId = Internals\PaySystemInner::getId())
		{
			/** @var Payment $payment */
			foreach ($this->collection as $payment)
			{
				if ($payment->getPaymentSystemId() == $paySystemId)
					return $payment;
			}

			/** @var PaySystemService $paySystemService */
			if ($paySystemService = PaySystemService::load($paySystemId))
			{
				$payment = $this->createItem($paySystemService);

				return $payment;
			}

		}

		return false;
	}

	/**
	 * @return int
	 */
	public static function getInnerPaySystemId()
	{
		return Internals\PaySystemInner::getId();
	}

	/**
	 * @return bool
	 */
	public function isExistsInnerPayment()
	{
		if ($paySystemId = Internals\PaySystemInner::getId())
		{
			/** @var Payment $payment */
			foreach ($this->collection as $payment)
			{
				if ($payment->getPaymentSystemId() == $paySystemId)
					return true;
			}
		}

		return false;
	}

}
