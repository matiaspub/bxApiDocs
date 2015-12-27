<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals;

Loc::loadMessages(__FILE__);

abstract class BasketBase
	extends Internals\EntityCollection
{
	/** @var string */
	protected $siteId = null;

	/** @var int */
	protected $fUserId = null;

	/** @var int */
	protected $orderId = null;

	/** @var Order */
	protected $order = null;

	const TYPE_SET = 1;

	/**
	 * @internal
	 *
	 * @param $index
	 * @return mixed|void
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectNotFoundException
	 */
	public function deleteItem($index)
	{
		$oldItem = parent::deleteItem($index);

		/** @var Order $order */
		if ($order = $this->getOrder())
		{
			$order->onBasketModify(EventActions::DELETE, $oldItem);
		}
	}

	/**
	 * @param OrderBase $order
	 * @return static
	 */
	public static function loadItemsForOrder(OrderBase $order)
	{
		$basket = new static();
		$basket->setOrder($order);
		return $basket->loadFromDB(array(
			"ORDER_ID" => $order->getId()
		));
	}

	/**
	 * @param array $filter
	 * @throws \Exception
	 * @return Basket
	 */
	abstract protected function loadFromDb(array $filter);

	/**
	 * @return Internals\EntityCollection
	 */
	public function getBasketItems()
	{
		return $this->collection;
	}

	/**
	 * @param OrderBase $order
	 */
	public function setOrder(OrderBase $order)
	{
		$this->order = $order;

		$this->orderId = $order->getId();
	}

	/**
	 * @return Order
	 */
	public function getOrder()
	{
		return $this->order;
	}

	/**
	 * @return int
	 */
	public function getPrice()
	{
		$orderPrice = 0;

		/** @var BasketItem $basketItem */
		foreach ($this->collection as $basketItem)
		{
			if (!$basketItem->isBundleChild())
				$orderPrice += $basketItem->getFinalPrice();
		}

		$orderPrice = roundEx($orderPrice, SALE_VALUE_PRECISION);

		return $orderPrice;
	}

	/**
	 * @return float|int
	 */
	public function getVatSum()
	{
		$vatSum = 0;

		/** @var BasketItem $basketItem */
		foreach ($this->collection as $basketItem)
		{
			if (!$basketItem->isBundleChild())
			{
				// BasketItem that is removed is not involved
				if ($basketItem->getQuantity() == 0)
					continue;

				$vatSum += $basketItem->getVat();
			}
		}

		return $vatSum;
	}

	/**
	 * @return float|int
	 */
	public function getVatRate()
	{
		$vatRate = 0;
		/** @var BasketItem $basketItem */
		foreach ($this->collection as $basketItem)
		{
			// BasketItem that is removed is not involved
			if ($basketItem->getQuantity() == 0)
				continue;

			if ($basketItem->getVatRate() > 0)
			{
				if ($basketItem->getVatRate() > $vatRate)
				{
					$vatRate = $basketItem->getVatRate();
				}
			}
		}

		return $vatRate;
	}

	/**
	 * @return int
	 */
	public function getWeight()
	{
		$orderWeight = 0;
		foreach ($this->collection as $basketItem)
		{
			$orderWeight += $basketItem->getWeight() * $basketItem->getQuantity();
		}

		return $orderWeight;
	}



	/**
	 * @param $itemCode
	 * @return BasketItem
	 */
	public function getItemByBasketCode($itemCode)
	{
		/** @var BasketItem $basketItem */
		foreach ($this->collection as $basketItem)
		{
			$basketItemCode = $basketItem->getBasketCode();
			if ($itemCode == $basketItemCode)
				return $basketItem;

			if ($basketItem->isBundleParent())
			{
				$bundleCollection = $basketItem->getBundleCollection();

				/** @var BasketItem $bundleBasketItem */
				foreach ($bundleCollection as $bundleBasketItem)
				{
					$bundleBasketItemCode = $bundleBasketItem->getBasketCode();
					if ($itemCode == $bundleBasketItemCode)
						return $bundleBasketItem;
				}
			}
		}

		return null;
	}


	/**
	 * @return bool
	 */
	abstract public function save();

	/**
	 * @return int
	 */
	public function getOrderId()
	{
		return $this->orderId;
	}

	/**
	 * @param $fuserId
	 */
	protected function setFUserId($fuserId)
	{
		$this->fUserId = intval($fuserId) > 0?intval($fuserId) : null;
	}

	/**
	 * @param $siteId
	 */
	protected function setSiteId($siteId)
	{
		$this->siteId = $siteId;
	}

	/**
	 * @param bool $skipCreate
	 * @return int|void
	 */
	public function getFUserId($skipCreate = false)
	{
		if ($this->fUserId === null)
		{
			$this->fUserId = Fuser::getId($skipCreate);
		}
		return $this->fUserId;
	}


	/**
	 * @return string
	 */
	public function getSiteId()
	{
		return $this->siteId;
	}

	/**
	 * @return array
	 */
	public function getQuantityList()
	{
		$quantityList = array();

		/**
		 * @var  $basketKey
		 * @var BasketItem $basketItem
		 */
		foreach ($this->collection as $basketKey => $basketItem)
		{
			$quantityList[$basketItem->getBasketCode()] = $basketItem->getQuantity();
		}

		return $quantityList;
	}

	/**
	 * @param int $days
	 *
	 * @return bool
	 */
	public static function deleteOld($days)
	{
		return true;
	}

}
