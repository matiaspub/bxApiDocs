<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals;

Loc::loadMessages(__FILE__);

class ShipmentItemCollection
	extends Internals\EntityCollection
{
	/** @var Shipment */
	protected $shipment;

	/**
	 * @return Shipment
	 */
	protected function getEntityParent()
	{
		return $this->getShipment();
	}

	/**
	 * @param Basket $basket
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 */
	public function resetCollection(Basket $basket)
	{
		if ($this->getShipment()->isShipped())
			throw new Main\NotSupportedException();

		if (!empty($this->collection))
		{
			/** @var ShipmentItem $shipmentItem */
			foreach ($this->collection as $shipmentItem)
			{
				$shipmentItem->setFieldNoDemand('QUANTITY', 0);
				$shipmentItem->delete();
			}
		}

		/** @var BasketItem $basketItem */
		foreach ($basket as $basketItem)
		{
			$shipmentItem = ShipmentItem::create($this, $basketItem);
			$this->addItem($shipmentItem);

			$shipmentItem->setFieldNoDemand("QUANTITY", $basketItem->getQuantity());

			if ($basketItem->isBundleParent())
			{
				$this->addBundleToCollection($basketItem);
			}

		}
	}

	/**
	 * @param BasketItem $basketItem
	 * @return ShipmentItem|null|static
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public function createItem(BasketItem $basketItem)
	{
		if ($this->getShipment()->isShipped())
			return null;

		$shipmentItem = $this->getItemByBasketCode($basketItem->getBasketCode());
		if ($shipmentItem !== null )
			return $shipmentItem;

		$shipmentItem = ShipmentItem::create($this, $basketItem);
		$this->addItem($shipmentItem);

		$shipment = $this->getShipment();

		if ($basketItem->isBundleParent() && !$shipment->isSystem())
		{
			$this->addBundleToCollection($basketItem);
		}

		return $shipmentItem;
	}

	/**
	 * @param BasketItem $basketItem
	 * @throws Main\ArgumentOutOfRangeException
	 */
	private function addBundleToCollection(BasketItem $basketItem)
	{
		/** @var Basket $bundleCollection */
		$bundleCollection = $basketItem->getBundleCollection();

		if ($bundleCollection->getOrder() === null)
		{
			/** @var Basket $basketCollection */
			if ($basketCollection = $basketItem->getCollection())
			{
				if ($order = $basketCollection->getOrder())
				{
					$bundleCollection->setOrder($order);
				}
			}
		}

		/** @var Shipment $shipment */
		$shipment = $this->getShipment();

		$bundleBaseQuantity = $basketItem->getBundleBaseQuantity();

		/** @var BasketItem $bundleBasketItem */
		foreach ($bundleCollection as $bundleBasketItem)
		{

			if ($this->isExistsBasketItem($bundleBasketItem))
			{
				continue;
			}

			$bundleProductId = $bundleBasketItem->getProductId();

			if (!isset($bundleBaseQuantity[$bundleProductId]))
				throw new Main\ArgumentOutOfRangeException("bundle product id");

			$quantity = $bundleBaseQuantity[$bundleProductId] * $basketItem->getQuantity();

			if ($quantity == 0)
				continue;

			$shipmentItemBundle = ShipmentItem::create($this, $bundleBasketItem);
			$this->addItem($shipmentItemBundle);



			if ($shipment->isSystem())
			{
				$shipmentItemBundle->setFieldNoDemand('QUANTITY', $quantity);
			}
			else
			{
				$shipmentItemBundle->setQuantity($quantity);
			}
		}
	}

	/**
	 * @param ShipmentItem $shipmentItem
	 * @return Internals\CollectableEntity|void
	 * @throws Main\NotSupportedException
	 */
	protected function addItem(ShipmentItem $shipmentItem)
	{
		parent::addItem($shipmentItem);

		/** @var Shipment $shipment */
		$shipment = $this->getShipment();
		$shipment->onShipmentItemCollectionModify(EventActions::ADD, $shipmentItem);

//		$shipment->setFieldNoDemand('PRICE_DELIVERY', $this->getPrice());
	}

	/**
	 * @internal
	 *
	 * @param $index
	 * @return mixed|void
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 */
	public function deleteItem($index)
	{
		$oldShipmentItem = parent::deleteItem($index);

		$shipment = $this->getShipment();
		$shipment->onShipmentItemCollectionModify(EventActions::DELETE, $oldShipmentItem);
	}

	/**
	 * @param ShipmentItem $item
	 * @param null $name
	 * @param null $oldValue
	 * @param null $value
	 * @return bool
	 */
	public function onItemModify(ShipmentItem $item, $name = null, $oldValue = null, $value = null)
	{
		$shipment = $this->getShipment();
		return $shipment->onShipmentItemCollectionModify(EventActions::UPDATE, $item, $name, $oldValue, $value);
	}

	/**
	 * @param $itemCode
	 * @return ShipmentItem|null
	 */
	public function getItemByBasketCode($itemCode)
	{
		foreach ($this->collection as $shippedItem)
		{
			/** @var ShipmentItem $shippedItem */
			$shippedItemCode = $shippedItem->getBasketCode();
			if ($itemCode === $shippedItemCode)
				return $shippedItem;
		}

		return null;
	}

	/**
	 * @return float|int
	 */
	public function getPrice()
	{
		$price = 0;
		/** @var ShipmentItem $shipmentItem */
		foreach ($this->collection as $shipmentItem)
		{
			/** @var BasketItem $basketItem */
			if ($basketItem = $shipmentItem->getBasketItem())
			{
				$price += $basketItem->getPrice() * $shipmentItem->getQuantity();
			}
		}

		return $price;
	}

	/**
	 * @return Shipment
	 */
	public function getShipment()
	{
		return $this->shipment;
	}

	public function dump($i)
	{
		$s = '';
		/** @var ShipmentItem $item */
		foreach ($this->collection as $item)
		{
			$s .= $item->dump($i);
		}
		return $s;
	}

	/**
	 * @return Main\Entity\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ObjectNotFoundException
	 */
	public function save()
	{
		$result = new Main\Entity\Result();

		$itemsFromDb = array();
		if ($this->getShipment()->getId() > 0)
		{
			$itemsFromDbList = Internals\ShipmentItemTable::getList(
				array(
					"filter" => array("ORDER_DELIVERY_ID" => $this->getShipment()->getId()),
					"select" => array("ID", 'BASKET_ID')
				)
			);
			while ($itemsFromDbItem = $itemsFromDbList->fetch())
				$itemsFromDb[$itemsFromDbItem["ID"]] = $itemsFromDbItem;
		}


		/** @var ShipmentItem $shipmentItem */
		foreach ($this->collection as $shipmentItem)
		{
			/** @var BasketItem $basketItem */
			if (!$basketItem = $shipmentItem->getBasketItem())
			{
				throw new Main\ObjectNotFoundException('Entity "BasketItem" not found');
			}

			if ($basketItem->isBundleParent())
			{
				$this->addBundleToCollection($basketItem);
			}
		}

		/** @var Shipment $shipment */
		if (!$shipment = $this->getShipment())
		{
			throw new Main\ObjectNotFoundException('Entity "Shipment" not found');
		}

		/** @var ShipmentItem $shipmentItem */
		foreach ($this->collection as $shipmentItem)
		{
			if ($shipment->isSystem() && $shipmentItem->getQuantity() == 0)
				continue;

			$r = $shipmentItem->save();
			if (!$r->isSuccess())
				$result->addErrors($r->getErrors());

			if (isset($itemsFromDb[$shipmentItem->getId()]))
				unset($itemsFromDb[$shipmentItem->getId()]);

		}

		/** @var ShipmentCollection $shipmentCollection */
		if (!$shipmentCollection = $shipment->getCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
		}


		/** @var Order $order */
		if(!$order = $shipmentCollection->getOrder())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}

		/** @var Basket $basket */
		if (!$basket = $order->getBasket())
		{
			throw new Main\ObjectNotFoundException('Entity "Basket" not found');
		}

		foreach ($itemsFromDb as $k => $v)
		{
			Internals\ShipmentItemTable::deleteWithItems($k);

			/** @var BasketItem $basketItem */
			if ($basketItem = $basket->getItemById($k))
			{

				OrderHistory::addAction(
					'SHIPMENT',
					$order->getId(),
					'SHIPMENT_ITEM_BASKET_REMOVED',
					$shipment->getId(),
					null,
					array(
						'NAME' => $basketItem->getField('NAME'),
						'QUANTITY' => $basketItem->getQuantity(),
						'PRODUCT_ID' => $basketItem->getProductId(),
					)
				);
			}
		}

		return $result;
	}

	/**
	 * @param Shipment $shipment
	 * @return ShipmentItemCollection
	 */
	public static function load(Shipment $shipment)
	{
		/** @var ShipmentItemCollection $shipmentItemCollection */
		$shipmentItemCollection = new static();
		$shipmentItemCollection->shipment = $shipment;

		if ($shipment->getId() > 0)
		{
			$shipmentItemList = ShipmentItem::loadForShipment($shipment->getId());

			/** @var ShipmentItem $shipmentItem */
			foreach ($shipmentItemList as $shipmentItem)
			{
				$shipmentItem->setCollection($shipmentItemCollection);
				$shipmentItemCollection->addItem($shipmentItem);
			}
		}

		return $shipmentItemCollection;
	}

	/**
	 * @param array $filter
	 * @return \Bitrix\Main\DB\Result
	 */
	public static function getList(array $filter = array())
	{
		return Internals\ShipmentItemTable::getList($filter);
	}


	/**
	 * @param $action
	 * @param BasketItem $basketItem
	 * @param null $name
	 * @param null $oldValue
	 * @param null $value
	 * @return bool
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotImplementedException
	 * @throws \Exception
	 */
	public function onBasketModify($action, BasketItem $basketItem, $name = null, $oldValue = null, $value = null)
	{
		throw new Main\NotImplementedException();

		$foundItem = false;
		/** @var ShipmentItem $shipmentItem */
		foreach ($this->collection as $shipmentItemIndex => $shipmentItem)
		{
			$code = $shipmentItem->getBasketCode();
			if ($code === $basketItem->getBasketCode())
			{
				$shipmentItem->onBasketModify($action, $basketItem, $name, $oldValue, $value);

				if ($action == "ADD")
				{
					$foundItem = true;
					break;
				}
				elseif ($action === "DELETE")
				{
					unset($this->collection[$shipmentItemIndex]);
				}

				return true;
			}
		}

		if (!$foundItem && $action == "ADD" || ($action == "UPDATE" && $value > $oldValue))
		{
			$shipmentFields = array(
				'ORDER_DELIVERY' => $this->shipment,
				'BASKET' => $basketItem,
				'QUANTITY' => ($basketItem->getQuantity()),
				'RESERVED_QUANTITY' => 0
			);

			if ($action == "UPDATE")
			{
				$shipmentItem->initFields($shipmentFields);
				$shipmentFields['QUANTITY'] = $value - $oldValue;
			}

			$shipmentItem = $this->createItem($basketItem);
			$shipmentItem->initFields($shipmentFields);
			return true;
		}

		if ($action == "UPDATE" && $value < $oldValue)
		{
			throw new Main\SystemException("no quantity");
		}

		return false;
	}

	/**
	 * @param BasketItem $basketItem
	 * @return bool
	 * @throws Main\ObjectNotFoundException
	 */
	protected function isExistsBasketItem(BasketItem $basketItem)
	{
		/** @var ShipmentItem $shipmentItem */
		foreach ($this->collection as $shipmentItem)
		{
			if ($shipmentItem->getBasketCode() == $basketItem->getBasketCode())
				return true;
		}

		return false;
	}
	/**
	 * @param array $shipmentItems
	 */
	/*
	protected function updateAttributesForShipmentItem(array $shipmentItems)
	{
		foreach($this->collection as $oldShipmentItemId => $oldShipmentItem)
		{
			$attributes = $oldShipmentItem->getAttributes();
			$foundItem = false;
			foreach ($shipmentItems as $shipmentItem)
			{
				if ($shipmentItem['ID'] == $oldShipmentItem->getId())
				{

					foreach($shipmentItem as $name => $value)
					{
						if (isset($attributes[$name]) && $value != $attributes[$name])
						{
							$oldShipmentItem->setAttribute($name, $value);
						}
					}

					$foundItem = true;
				}
			}

			if (!$foundItem)
			{
				unset($this->collection[$oldShipmentItemId]);
			}
		}

	}
	*/

}