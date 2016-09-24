<?php
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
	 * @internal
	 *
	 * Load the contents of the basket to order
	 *
	 * @param OrderBase $order - object of the order
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
	 * Getting the contents of the basket
	 *
	 * @return Internals\EntityCollection
	 */
	
	/**
	* <p>Метод возвращает содержимое корзины. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Sale\Internals\EntityCollection 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/basketbase/getbasketitems.php
	* @author Bitrix
	*/
	public function getBasketItems()
	{
		return $this->collection;
	}

	/**
	 * Attach to the essence of the object of the order basket
	 *
	 * @param OrderBase $order - object of the order
	 */
	
	/**
	* <p>Метод ассоциирует корзину с заказом. Нестатический метод.</p>
	*
	*
	* @param mixed $Bitrix  Объект заказа.
	*
	* @param Bitri $Sale  
	*
	* @param OrderBase $order  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/basketbase/setorder.php
	* @author Bitrix
	*/
	public function setOrder(OrderBase $order)
	{
		$this->order = $order;

		$this->orderId = $order->getId();
	}

	/**
	 * Getting the object of the order
	 *
	 * @return Order
	 */
	
	/**
	* <p>Метод возвращает объект класса <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/sale/order/index.php">\Bitrix\Sale\Order</a>. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Sale\Order 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/basketbase/getorder.php
	* @author Bitrix
	*/
	public function getOrder()
	{
		return $this->order;
	}

	/**
	 * Getting basket price with discounts and taxes
	 *
	 * @return float
	 */
	
	/**
	* <p>Метод возвращает цену корзины с учетом скидок и налогов. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return float 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/basketbase/getprice.php
	* @author Bitrix
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

		return $orderPrice;
	}

	/**
	 * Getting basket price without discounts
	 *
	 * @return float
	 */
	
	/**
	* <p>Метод возвращает цену корзины без учета скидок. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return float 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/basketbase/getbaseprice.php
	* @author Bitrix
	*/
	public function getBasePrice()
	{
		$orderPrice = 0;

		/** @var BasketItem $basketItem */
		foreach ($this->collection as $basketItem)
		{
			if (!$basketItem->isBundleChild())
				$orderPrice += PriceMaths::roundPrecision($basketItem->getBasePrice() * $basketItem->getQuantity());
		}

		$orderPrice = PriceMaths::roundPrecision($orderPrice);

		return $orderPrice;
	}

	/**
	 * Getting the value of the tax basket
	 *
	 * @return float
	 */
	
	/**
	* <p>Метод возвращает величину налога в корзине. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return float 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/basketbase/getvatsum.php
	* @author Bitrix
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
	 * Getting the value of the tax rate basket
	 *
	 * @return float
	 */
	
	/**
	* <p>Метод возвращает значение налоговой ставки в корзине. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return float 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/basketbase/getvatrate.php
	* @author Bitrix
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
	 * Getting the weight basket
	 *
	 * @return int
	 */
	
	/**
	* <p>Метод возвращает вес корзины. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/basketbase/getweight.php
	* @author Bitrix
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
	 * Get the code element basket
	 *
	 * @param $itemCode - code element basket
	 * @return BasketItem
	 */
	
	/**
	* <p>Метод возвращает элемент корзины по коду. Нестатический метод.</p>
	*
	*
	* @param mixed $itemCode  Код элемента корзины.
	*
	* @return \Bitrix\Sale\BasketItem 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/basketbase/getitembybasketcode.php
	* @author Bitrix
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
	 * Save basket
	 *
	 * @return bool
	 */
	
	/**
	* <p>Метод сохраняет корзину. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/basketbase/save.php
	* @author Bitrix
	*/
	abstract public function save();

	/**
	 * Getting order ID
	 *
	 * @return int
	 */
	
	/**
	* <p>Метод возвращает идентификатор заказа. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/basketbase/getorderid.php
	* @author Bitrix
	*/
	public function getOrderId()
	{
		return $this->orderId;
	}

	/**
	 * Setting Customer ID to basket
	 *
	 * @param $fUserId - customer ID
	 */
	
	/**
	* <p>Метод устанавливает идентификатор владельца корзины. Нестатический метод.</p>
	*
	*
	* @param mixed $fUserId  Идентификатор клиента магазина.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/basketbase/setfuserid.php
	* @author Bitrix
	*/
	public function setFUserId($fUserId)
	{
		$this->fUserId = intval($fUserId) > 0?intval($fUserId) : null;
	}

	/**
	 * Setting site ID to basket
	 *
	 * @param $siteId - site ID
	 */
	protected function setSiteId($siteId)
	{
		$this->siteId = $siteId;
	}

	/**
	 * Getting Customer ID
	 *
	 * @param bool $skipCreate - Creating a buyer if it is not found
	 * @return int|void
	 */
	
	/**
	* <p>Метод возвращает идентификатор владельца корзины. Нестатический метод.</p>
	*
	*
	* @param boolean $skipCreate = false Если принимает значение <i>false</i>, то будет создан покупатель (если
	* он не найден).
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/basketbase/getfuserid.php
	* @author Bitrix
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
	 * Getting Site ID
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает идентификатор сайта. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/basketbase/getsiteid.php
	* @author Bitrix
	*/
	public function getSiteId()
	{
		return $this->siteId;
	}

	/**
	 * Getting a list of a count of elements in the basket
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает список с количеством каждой позиции в корзине. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/basketbase/getquantitylist.php
	* @author Bitrix
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
	 * Removing the old records in the basket
	 *
	 * @param int $days - number of days, how many is considered obsolete basket
	 *
	 * @return bool
	 */
	
	/**
	* <p>Метод удаляет старые записи из корзины. Метод статический.</p>
	*
	*
	* @param integer $days  Количество дней, через которое корзина считается устаревшей.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/basketbase/deleteold.php
	* @author Bitrix
	*/
	public static function deleteOld($days)
	{
		return true;
	}

	/**
	 * @internal
	 * @param \SplObjectStorage $cloneEntity
	 *
	 * @return Basket
	 */
	public function createClone(\SplObjectStorage $cloneEntity = null)
	{
		if ($cloneEntity === null)
		{
			$cloneEntity = new \SplObjectStorage();
		}
		else
		{
			if ($this->isClone() && $cloneEntity->contains($this))
			{
				return $cloneEntity[$this];
			}
		}
		
		$basketClone = clone $this;
		$basketClone->isClone = true;

		if ($this->order)
		{
			if ($cloneEntity->contains($this->order))
			{
				$basketClone->order = $cloneEntity[$this->order];
			}
		}

		if (!$cloneEntity->contains($this))
		{
			$cloneEntity[$this] = $basketClone;
		}

		/**
		 * @var int key
		 * @var BasketItem $basketItem
		 */
		foreach ($basketClone->collection as $key => $basketItem)
		{
			if (!$cloneEntity->contains($basketItem))
			{
				$cloneEntity[$basketItem] = $basketItem->createClone($cloneEntity);
			}

			$basketClone->collection[$key] = $cloneEntity[$basketItem];
		}


		return $basketClone;
	}

}
