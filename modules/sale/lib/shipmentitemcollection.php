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
	 *
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectNotFoundException
	 * @throws \ErrorException
	 */
	public function resetCollection(Basket $basket)
	{
		if ($this->getShipment()->isShipped())
			throw new Main\NotSupportedException();

		/** @var Shipment $shipment */
		if (!$shipment = $this->getShipment())
		{
			throw new Main\ObjectNotFoundException('Entity "Shipment" not found');
		}

		/** @var ShipmentCollection $shipmentCollection */
		if (!$shipmentCollection = $shipment->getCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
		}

		if (!empty($this->collection))
		{
			/** @var ShipmentItem $shipmentItem */
			foreach ($this->collection as $shipmentItem)
			{
				$shipmentItem->setFieldNoDemand('QUANTITY', 0);
				$shipmentItem->delete();
			}
		}

		$quantityList = array();

		/** @var BasketItem $basketItem */
		foreach ($basket as $basketItem)
		{
			$quantityList[$basketItem->getBasketCode()] = $shipmentCollection->getBasketItemQuantity($basketItem);
		}


		/** @var BasketItem $basketItem */
		foreach ($basket as $basketItem)
		{
			$shipmentItem = ShipmentItem::create($this, $basketItem);
			$this->addItem($shipmentItem);

			$basketItemQuantity = 0;

			if (array_key_exists($basketItem->getBasketCode(), $quantityList))
			{
				$basketItemQuantity = $quantityList[$basketItem->getBasketCode()];
			}

			$quantity = $basketItem->getQuantity() - $basketItemQuantity;

			$shipmentItem->setFieldNoDemand("QUANTITY", $quantity);

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

		$shipmentItem->setCollection($this);
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
	 *
	 * @return Result
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\ObjectNotFoundException
	 */
	private function addBundleToCollection(BasketItem $basketItem)
	{
		$result = new Result();

		/** @var Basket $bundleCollection */
		if (!$bundleCollection = $basketItem->getBundleCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "BundleCollection" not found');
		}

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

		/** @var ShipmentCollection $shipmentCollection */
		if (!$shipmentCollection = $shipment->getCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
		}

		/** @var Shipment $systemShipment */
		if (!$systemShipment = $shipmentCollection->getSystemShipment())
		{
			throw new Main\ObjectNotFoundException('Entity "System Shipment" not found');
		}

		/** @var ShipmentItemCollection $systemShipmentItemCollection */
		if (!$systemShipmentItemCollection = $systemShipment->getShipmentItemCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentItemCollection" not found');
		}

		$baseQuantity = $basketItem->getQuantity();

		/** @var ShipmentItem $systemShipmentItem */
		if ($systemShipmentItem = $systemShipmentItemCollection->getItemByBasketCode($basketItem->getBasketCode()))
		{
			$baseQuantity = $systemShipmentItem->getQuantity();
		}

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

			$quantity = $bundleBaseQuantity[$bundleProductId] * $baseQuantity;

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
				$r = $shipmentItemBundle->setQuantity($quantity);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}
		}

		return $result;
	}

	/**
	 * @param Internals\CollectableEntity $shipmentItem
	 * @return Internals\CollectableEntity|void
	 * @throws Main\NotSupportedException
	 */
	protected function addItem(Internals\CollectableEntity $shipmentItem)
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
	 * @param Internals\CollectableEntity $item
	 * @param null $name
	 * @param null $oldValue
	 * @param null $value
	 * @return bool
	 */
	public function onItemModify(Internals\CollectableEntity $item, $name = null, $oldValue = null, $value = null)
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
	 * @param $itemId
	 * @return ShipmentItem|null
	 */
	public function getItemByBasketId($itemId)
	{
		foreach ($this->collection as $shippedItem)
		{
			/** @var ShipmentItem $shippedItem */
			$shippedItemId = $shippedItem->getBasketId();
			if ($itemId === $shippedItemId)
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
				if ($basketItem->isBundleChild())
					continue;
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

		/** @var Shipment $shipment */
		if (!$shipment = $this->getShipment())
		{
			throw new Main\ObjectNotFoundException('Entity "Shipment" not found');
		}

		/** @var ShipmentCollection $shipmentCollection */
		if (!$shipmentCollection = $shipment->getCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
		}

		/** @var Order $order */
		if (!$order = $shipmentCollection->getOrder())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}

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

		$changeMeaningfulFields = array(
			"QUANTITY",
			"RESERVED_QUANTITY",
		);

		/** @var ShipmentItem $shipmentItem */
		foreach ($this->collection as $shipmentItem)
		{
			$isNew = (bool)($shipmentItem->getId() <= 0);
			$isChanged = $shipmentItem->isChanged();

			if ($order->getId() > 0 && $isChanged)
			{
				/** @var BasketItem $basketItem */
				if (!$basketItem = $shipmentItem->getBasketItem())
				{
					throw new Main\ObjectNotFoundException('Entity "BasketItem" not found');
				}

				$logFields = array(
					"BASKET_ID" => $basketItem->getId(),
					"BASKET_ITEM_NAME" => $basketItem->getField("NAME"),
					"BASKET_ITEM_PRODUCT_ID" => $basketItem->getField("PRODUCT_ID"),
					"ORDER_DELIVERY_ID" => $shipmentItem->getField("ORDER_DELIVERY_ID"),
				);

				$fields = $shipmentItem->getFields();
				$originalValues = $fields->getOriginalValues();

				foreach($originalValues as $originalFieldName => $originalFieldValue)
				{
					if (in_array($originalFieldName, $changeMeaningfulFields) && $shipmentItem->getField($originalFieldName) != $originalFieldValue)
					{
						$logFields[$originalFieldName] = $shipmentItem->getField($originalFieldName);
						if (!$isNew)
							$logFields['OLD_'.$originalFieldName] = $originalFieldValue;
					}
				}
			}


			if ($shipment->isSystem() && $shipmentItem->getQuantity() == 0)
				continue;

			$r = $shipmentItem->save();
			if ($r->isSuccess())
			{
				if ($order->getId() > 0 && $isChanged)
				{
					OrderHistory::addLog('SHIPMENT_ITEM', $order->getId(), $isNew ? 'SHIPMENT_ITEM_ADD' : 'SHIPMENT_ITEM_UPDATE', $shipmentItem->getId(), $shipmentItem, $logFields , OrderHistory::SALE_ORDER_HISTORY_LOG_LEVEL_1);
				}
			}
			else
			{
				$result->addErrors($r->getErrors());
			}

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

		$itemEventName = ShipmentItem::getEntityEventName();

		foreach ($itemsFromDb as $k => $v)
		{
			/** @var Main\Event $event */
			$event = new Main\Event('sale', "OnBefore".$itemEventName."Deleted", array(
					'VALUES' => $v,
			));
			$event->send();

			Internals\ShipmentItemTable::deleteWithItems($k);

			/** @var Main\Event $event */
			$event = new Main\Event('sale', "On".$itemEventName."Deleted", array(
					'VALUES' => $v,
			));
			$event->send();

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

		if ($order->getId() > 0)
		{
			OrderHistory::collectEntityFields('SHIPMENT_ITEM', $order->getId());
		}

		return $result;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return ShipmentItemCollection
	 * @throws Main\ArgumentNullException
	 * @throws Main\ObjectNotFoundException
	 */
	public static function load(Shipment $shipment)
	{
		/** @var ShipmentItemCollection $shipmentItemCollection */
		$shipmentItemCollection = new static();
		$shipmentItemCollection->shipment = $shipment;

		if ($shipment->getId() > 0)
		{

			/** @var ShipmentCollection $shipmentCollection */
			if (!$shipmentCollection = $shipment->getCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
			}

			/** @var Order $order */
			if (!$order = $shipmentCollection->getOrder())
			{
				throw new Main\ObjectNotFoundException('Entity "Order" not found');
			}

			$shipmentItemList = ShipmentItem::loadForShipment($shipment->getId());

			/** @var ShipmentItem $shipmentItem */
			foreach ($shipmentItemList as $shipmentItem)
			{
				$shipmentItem->setCollection($shipmentItemCollection);
				$shipmentItemCollection->addItem($shipmentItem);
				
				if (!$basketItem = $shipmentItem->getBasketItem())
				{
					if (!$shipment->isMarked())
					{
						$shipment->setField('MARKED', 'Y');
						$oldErrorText = $shipment->getField('REASON_MARKED');
						$oldErrorText .= (strval($oldErrorText) != '' ? "\n" : ""). Loc::getMessage("SALE_SHIPMENT_ITEM_COLLECTION_BASKET_ITEM_NOT_FOUND", array(
								'#BASKET_ITEM_ID#' => $shipmentItem->getBasketId(),
								'#SHIPMENT_ID#' => $shipment->getId(),
								'#SHIPMENT_ITEM_ID#' => $shipmentItem->getId(),
							));
						
						$shipment->setField('REASON_MARKED', $oldErrorText);
					}
				}
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
	 * @param BasketItem $basketItem
	 *
	 * @return Result
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ObjectNotFoundException
	 * @throws \ErrorException
	 */
	public function deleteByBasketItem(BasketItem $basketItem)
	{
		$result = new Result();
		$systemShipmentItem = null;

		/** @var Shipment $shipment */
		if (!$shipment = $this->getShipment())
		{
			throw new Main\ObjectNotFoundException('Entity "Shipment" not found');
		}


		/** @var ShipmentItem $shipmentItem */
		foreach ($this->collection as $shipmentItem)
		{
			if ($shipmentItem->getBasketCode() == $basketItem->getBasketCode())
			{
				if ($shipment->isSystem())
				{
					$systemShipmentItem = $shipmentItem;
					continue;
				}

				$r = $shipmentItem->delete();
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}
		}

		if ($systemShipmentItem !== null)
		{
			if ($systemShipmentItem->getReservedQuantity() > 0)
			{
				/** @var Result $r */
				$r = $systemShipmentItem->tryUnreserve();
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}

			if ($result->isSuccess())
			{
				$systemShipmentItem->setFieldNoDemand('QUANTITY', 0);
				$r = $systemShipmentItem->delete();
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function isEmpty()
	{
		if (count($this->collection) == 0)
			return true;

		/** @var ShipmentItem $item */
		foreach ($this->collection as $item)
		{
			if ($item->getQuantity() > 0)
				return false;
		}

		return true;
	}

	/**
	 * @param BasketItem $basketItem
	 *
	 * @return float|int
	 * @throws Main\ObjectNotFoundException
	 */
	public function getBasketItemQuantity(BasketItem $basketItem)
	{
		$allQuantity = 0;
		/** @var ShipmentItem $shipmentItem */
		foreach ($this->collection as $shipmentItem)
		{
			if ($shipmentItem->getBasketCode() == $basketItem->getBasketCode())
			{
				$allQuantity += $shipmentItem->getQuantity();
			}
		}

		return $allQuantity;
	}

	/**
	 * @param BasketItem $basketItem
	 *
	 * @return bool
	 * @throws Main\ObjectNotFoundException
	 */
	public function isExistBasketItem(BasketItem $basketItem)
	{
		/** @var ShipmentItem $shipmentItem */
		foreach ($this->collection as $shipmentItem)
		{
			if ($shipmentItem->getBasketCode() == $basketItem->getBasketCode())
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @internal
	 * @param \SplObjectStorage $cloneEntity
	 *
	 * @return ShipmentItemCollection
	 */
	public function createClone(\SplObjectStorage $cloneEntity)
	{
		if ($this->isClone() && $cloneEntity->contains($this))
		{
			return $cloneEntity[$this];
		}

		$shipmentItemCollectionClone = clone $this;
		$shipmentItemCollectionClone->isClone = true;

		if (!$cloneEntity->contains($this))
		{
			$cloneEntity[$this] = $shipmentItemCollectionClone;
		}

		/** @var Shipment $shipment */
		if ($shipment = $this->shipment)
		{
			if (!$cloneEntity->contains($shipment))
			{
				$cloneEntity[$shipment] = $shipment->createClone($cloneEntity);
			}

			if ($cloneEntity->contains($shipment))
			{
				$shipmentItemCollectionClone->shipment = $cloneEntity[$shipment];
			}

		}


		/**
		 * @var int key
		 * @var ShipmentItem $shipmentItem
		 */
		foreach ($shipmentItemCollectionClone->collection as $key => $shipmentItem)
		{
			if (!$cloneEntity->contains($shipmentItem))
			{
				$cloneEntity[$shipmentItem] = $shipmentItem->createClone($cloneEntity);
			}

			$shipmentItemCollectionClone->collection[$key] = $cloneEntity[$shipmentItem];
		}

		return $shipmentItemCollectionClone;
	}

}