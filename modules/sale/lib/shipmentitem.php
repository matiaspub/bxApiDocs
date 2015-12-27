<?php
namespace Bitrix\Sale;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Sale\Internals;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ShipmentItem
	extends Internals\CollectableEntity
{
	/** @var BasketItem */
	private $basketItem;

	private $shipmentItemStoreCollection = array();

	private static $errors = array();

	/**
	 * @return array
	 */
	public static function getAvailableFields()
	{
		return array("QUANTITY", "RESERVED_QUANTITY");
	}

	/**
	 * @return array
	 */
	public static function getMeaningfulFields()
	{
		return array('QUANTITY');
	}

	/**
	 * @return array
	 */
	public static function getAllFields()
	{
		static $fields = null;
		if ($fields == null)
			$fields = array_keys(Internals\ShipmentItemTable::getMap());
		return $fields;
	}

	/**
	 * Internal method, use ShipmentItemCollection::createShipmentItem()
	 *
	 * @internal
	 * @see ShipmentItemCollection::createShipmentItem()
	 *
	 * @param ShipmentItemCollection $collection
	 * @param BasketItem $basketItem
	 * @return static
	 */
	public static function create(ShipmentItemCollection $collection, BasketItem $basketItem = null)
	{
		$fields = array();
		if ($basketItem !== null && $basketItem->getId() > 0)
		{
			$fields["BASKET_ID"] = $basketItem->getId();
		}

		$shipmentItem = new static();
		$shipmentItem->setFieldsNoDemand($fields);
		$shipmentItem->setCollection($collection);

		if ($basketItem !== null)
		{
			$shipmentItem->basketItem = $basketItem;
		}

		return $shipmentItem;
	}

	/**
	 * Deletes shipment item
	 *
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 * @throws \Exception
	 */
	public function delete()
	{
		$result = new Result();
		/** @var ShipmentItemCollection $shipmentItemCollection */
		if (!$shipmentItemCollection = $this->getCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentItemCollection" not found');
		}

		/** @var Shipment $shipment */
		if (!$shipment = $shipmentItemCollection->getShipment())
		{
			throw new Main\ObjectNotFoundException('Entity "Shipment" not found');
		}

		if (!$shipment->isSystem())
		{
			if ($shipment->isShipped())
			{
				/** @var BasketItem $basketItem */
				if (!$basketItem = $this->getBasketItem())
				{
					throw new Main\ObjectNotFoundException('Entity "BasketItem" not found');
				}

				$result->addError(new ResultError(Loc::getMessage(
					'SALE_SHIPMENT_ITEM_SHIPMENT_ALREADY_SHIPPED_CANNOT_DELETE',
					array(
						'#PRODUCT_NAME#' => $basketItem->getField('NAME')
					)), 'SALE_SHIPMENT_ITEM_SHIPMENT_ALREADY_SHIPPED_CANNOT_DELETE'));

				return $result;
			}

			$r = $this->setField("QUANTITY", 0);

			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
				return $result;
			}
		}
		elseif ($this->getQuantity() > 0)
		{
			throw new \ErrorException('System shipment not empty');
		}

		return parent::delete();
	}

	/**
	 * Sets new value to specified field of shipment item
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return Result
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 * @throws \Exception
	 */
	public function setField($name, $value)
	{
		/** @var ShipmentItemCollection $collection */
		$collection = $this->getCollection();
		$shipment = $collection->getShipment();
		if ($shipment->isSystem() && ($name != 'RESERVED_QUANTITY'))
			throw new Main\NotSupportedException();

		return parent::setField($name, $value);
	}

	/**
	 * @param $quantity
	 * @return bool
	 * @throws Main\NotSupportedException
	 */
//	public function reserve($quantity)
//	{
//		$quantity = floatval($quantity);
//
//		$reservedQuantity = Provider::tryReserveShipmentItem($this);
//		$newQuantity = $this->getReservedQuantity() + $reservedQuantity;
//
//		//$this->setField("RESERVED_QUANTITY", $newQuantity);
//
//		return ($this->getQuantity() === $this->getReservedQuantity());
//	}

	/**
	 * @return float
	 */
	public function getQuantity()
	{
		return $this->getField('QUANTITY');
	}

	protected function onFieldModify($name, $oldValue, $value)
	{
		$result = new Result();

		/** @var ShipmentItemCollection $shipmentItemCollection */
		if (!$shipmentItemCollection = $this->getCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentItemCollection" not found');
		}

		/** @var Shipment $shipment */
		if (!$shipment = $shipmentItemCollection->getShipment())
		{
			throw new Main\ObjectNotFoundException('Entity "Shipment" not found');
		}

		if ($shipment->isShipped())
		{
			$result = new Result();
			$result->addError(new ResultError(Loc::getMessage('SALE_SHIPMENT_ITEM_SHIPMENT_ALREADY_SHIPPED_CANNOT_EDIT'), 'SALE_SHIPMENT_ITEM_SHIPMENT_ALREADY_SHIPPED_CANNOT_EDIT'));
			return $result;
		}

		if ($name == "QUANTITY")
		{

			/** @var BasketItem $basketItem */
			if (!$basketItem = $this->getBasketItem())
			{
				throw new Main\ObjectNotFoundException('Entity "BasketItem" not found');
			}


			$deltaQuantity = $value - $oldValue;

			if ($deltaQuantity > 0)
			{

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

				$systemBasketItemQuantity = $systemShipment->getBasketItemQuantity($basketItem);
				if ($systemBasketItemQuantity < abs($deltaQuantity))
				{
					$errorBasketCode = $basketItem->getBasketCode();

					if ($basketItem->isBundleChild())
					{
						/** @var BasketItem $parentBasketItem */
						if (!($parentBasketItem = $basketItem->getParentBasketItem()))
						{
							throw new Main\ObjectNotFoundException('Entity "Parent Basket Item" not found');
						}

						$errorBasketCode = $parentBasketItem->getBasketCode();
					}

					if (isset(static::$errors[$errorBasketCode][$basketItem->getField('ORDER_DELIVERY_BASKET_ID')]['STORE_QUANTITY_LARGER_ALLOWED']))
					{
						static::$errors[$errorBasketCode][$basketItem->getField('ORDER_DELIVERY_BASKET_ID')]['STORE_QUANTITY_LARGER_ALLOWED'] += $basketItem->getQuantity();
					}
					else
					{
						$result->addError(new ResultError(
												Loc::getMessage('SALE_SHIPMENT_ITEM_LESS_AVAILABLE_QUANTITY', array(
													'#PRODUCT_NAME#' => $basketItem->getField('NAME'),
												)),
												'SALE_SHIPMENT_ITEM_LESS_AVAILABLE_QUANTITY')
						);

						static::$errors[$errorBasketCode][$basketItem->getField('ORDER_DELIVERY_BASKET_ID')]['STORE_QUANTITY_LARGER_ALLOWED'] = $basketItem->getQuantity();
					}

//
//					$result->addError(new ResultError(Loc::getMessage('SALE_SHIPMENT_ITEM_LESS_AVAILABLE_QUANTITY', array(
//						'#PRODUCT_NAME#' => $basketItem->getField('NAME')
//					)), 'SALE_SHIPMENT_ITEM_LESS_AVAILABLE_QUANTITY'));
					return $result;
				}
			}

			if ($basketItem->isBundleParent())
			{
				$r = parent::onFieldModify($name, $oldValue, $value);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}

				if ($bundleCollection = $basketItem->getBundleCollection())
				{
					$bundleBaseQuantity = $basketItem->getBundleBaseQuantity();
					/** @var BasketItem $bundleBasketItem */
					foreach ($bundleCollection as $bundleBasketItem)
					{
						/** @var ShipmentItem $shipmentItem */
						if ($shipmentItem = $shipmentItemCollection->getItemByBasketCode($bundleBasketItem->getBasketCode()))
						{
							$bundleProductId = $bundleBasketItem->getProductId();

							if (!isset($bundleBaseQuantity[$bundleProductId]))
								throw new Main\ArgumentOutOfRangeException("bundle product id");

							$quantity = $bundleBaseQuantity[$bundleProductId] * $value;

							$r = $shipmentItem->setQuantity($quantity);

							if (!$r->isSuccess())
							{
								$result->addErrors($r->getErrors());
							}
						}
					}
				}

				if (!$this->isMathActionOnly())
				{
					$r = $this->calculateDelivery();
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
					}
				}
				return $result;
			}

			if (!$basketItem->isBundleChild())
			{
				if (!$this->isMathActionOnly())
				{
					$r = $this->calculateDelivery();
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
					}
				}
			}

			/** @var ShipmentItemStoreCollection $shipmentItemStoreCollection */
			if (!$shipmentItemStoreCollection = $this->getShipmentItemStoreCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentItemStoreCollection" not found');
			}

			if ($value == 0)
			{

				/** @var ShipmentItemStore $shipmentItemStore */
				foreach ($shipmentItemStoreCollection as $shipmentItemStore)
				{
					$shipmentItemStore->delete();
				}
			}
			else
			{
				// check barcodes
				/** @var ShipmentItemStoreCollection $shipmentItemStoreCollection */
				if ($this->getShipmentItemStoreCollection())
				{
					$barcodeQuantity = $shipmentItemStoreCollection->getQuantityByBasketCode($basketItem->getBasketCode());
					if ($barcodeQuantity > $value)
					{
						$result->addError(new ResultError(Loc::getMessage('SALE_SHIPMENT_ITEM_BARCODE_MORE_ITEM_QUANTITY'), 'BARCODE_MORE_ITEM_QUANTITY'));
						return $result;
					}
				}
			}
		}

		return parent::onFieldModify($name, $oldValue, $value);
	}


	/**
	 * @param float $quantity
	 * @return Result
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\NotSupportedException
	 * @throws \Exception
	 */
	public function setQuantity($quantity)
	{
		if (!is_numeric($quantity))
			throw new Main\ArgumentTypeException("quantity");

		return $this->setField('QUANTITY', floatval($quantity));
	}

	/**
	 * @return float
	 */
	public function getReservedQuantity()
	{
		return floatval($this->getField('RESERVED_QUANTITY'));
	}

	/**
	 * @return int
	 */
	public function getBasketId()
	{
		return $this->getField('BASKET_ID');
	}

	/**
	 * @return int
	 * @throws Main\SystemException
	 */
	public function getBasketCode()
	{
		if ($basketItem = $this->getBasketItem())
		{
			return $basketItem->getBasketCode();
		}

		throw new Main\ObjectNotFoundException('Entity basketItem not found');
	}

	public function dump($i)
	{
		return str_repeat(' ', $i)."Item: Id=".$this->getId().", BasketId=".$this->getBasketId().", BasketCode=".$this->getBasketCode().", Quantity=".$this->getQuantity().", ReservedQuantity=".$this->getReservedQuantity()."\n";
	}

	/**
	 * @return Entity\AddResult|Entity\UpdateResult
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ObjectNotFoundException
	 * @throws \Exception
	 */
	public function save()
	{
		$id = $this->getId();
		$fields = $this->fields->getValues();


		/** @var ShipmentItemCollection $shipmentItemCollection */
		if (!$shipmentItemCollection = $this->getCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentItemCollection" not found');
		}

		/** @var Shipment $shipment */
		if (!$shipment = $shipmentItemCollection->getShipment())
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

		if ($id > 0)
		{
			$fields = $this->fields->getChangedValues();

			if (!empty($fields) && is_array($fields))
			{
				/** @var ShipmentItemCollection $shipmentItemCollection */
				if (!$shipmentItemCollection = $this->getCollection())
				{
					throw new Main\ObjectNotFoundException('Entity "ShipmentItemCollection" not found');
				}

				/** @var Shipment $shipment */
				if (!$shipment = $shipmentItemCollection->getShipment())
				{
					throw new Main\ObjectNotFoundException('Entity "Shipment" not found');
				}

				if (!$shipment->isSystem())
				{
					if (isset($fields["QUANTITY"]) && (floatval($fields["QUANTITY"]) == 0))
						return new Entity\UpdateResult();
				}


				//$fields['DATE_UPDATE'] = new Main\Type\DateTime();
				$r = Internals\ShipmentItemTable::update($id, $fields);
				if (!$r->isSuccess())
					return $r;
			}

			$result = new Entity\UpdateResult();

			if ($order && $order->getId() > 0)
				OrderHistory::collectEntityFields('SHIPMENT_ITEM_STORE', $order->getId(), $id);
		}
		else
		{
			$fields['ORDER_DELIVERY_ID'] = $this->getParentShipmentId();
			$fields['DATE_INSERT'] = new Main\Type\DateTime();
			$fields["BASKET_ID"] = $this->basketItem->getId();


			if (!isset($fields["QUANTITY"]) || (floatval($fields["QUANTITY"]) == 0))
				return new Entity\UpdateResult();

			if (!isset($fields['RESERVED_QUANTITY']))
			{
				$fields['RESERVED_QUANTITY'] = $this->getReservedQuantity() === null ? 0 : $this->getReservedQuantity();
			}

			$r = Internals\ShipmentItemTable::add($fields);
			if (!$r->isSuccess())
			{
				return $r;
			}

			$id = $r->getId();
			$this->setFieldNoDemand('ID', $id);

			$result = new Entity\AddResult();

			OrderHistory::addAction(
				'SHIPMENT',
				$order->getId(),
				'SHIPMENT_ITEM_BASKET_ADDED',
				$shipment->getId(),
				$this->basketItem,
				array(
					'QUANTITY' => $this->getQuantity(),
				)
			);
		}

		if ($eventName = static::getEntityEventName())
		{
			$oldEntityValues = $this->fields->getOriginalValues();

			if (!empty($oldEntityValues))
			{
				/** @var Main\Event $event */
				$event = new Main\Event('sale', 'On'.$eventName.'EntitySaved', array(
					'ENTITY' => $this,
					'VALUES' => $oldEntityValues,
				));
				$event->send();
			}
		}

		$shipmentItemStoreCollection = $this->getShipmentItemStoreCollection();
		$r = $shipmentItemStoreCollection->save();
		if (!$r->isSuccess())
			$result->addErrors($r->getErrors());

		if ($result->isSuccess())
		{
			OrderHistory::collectEntityFields('SHIPMENT_ITEM', $order->getId(), $id);
		}

		$this->fields->clearChanged();

		return $result;
	}

	private function getParentShipmentId()
	{
		/** @var ShipmentItemCollection $collection */
		$collection = $this->getCollection();
		$shipment = $collection->getShipment();
		return $shipment->getId();
	}

	/**
	 * @param $id
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 */
	public static function loadForShipment($id)
	{
		if (intval($id) <= 0)
			throw new Main\ArgumentNullException("id");

		$items = array();

		$itemDataList = Internals\ShipmentItemTable::getList(
			array(
				'filter' => array('ORDER_DELIVERY_ID' => $id),
				'order' => array('DATE_INSERT' => 'ASC', 'ID' => 'ASC')
			)
		);
		while ($itemData = $itemDataList->fetch())
			$items[] = new static($itemData);

		return $items;
	}

	/**
	 * @return bool
	 */
	protected function loadBasketItem()
	{
		/** @var ShipmentItemCollection $collection */
		$collection = $this->getCollection();
		$shipment = $collection->getShipment();

		/** @var ShipmentCollection $shipmentCollection */
		$shipmentCollection = $shipment->getCollection();
		$order = $shipmentCollection->getOrder();

		$basketCollection = $order->getBasket();

		return $basketCollection->getItemById($this->getBasketId());
	}

	/**
	 * @return BasketItem
	 */
	public function getBasketItem()
	{
		if ($this->basketItem == null)
		{
			$this->basketItem = $this->loadBasketItem();
		}

		return $this->basketItem;
	}

	/**
	 * @return ShipmentItemStoreCollection
	 */
	public function getShipmentItemStoreCollection()
	{
		if (empty($this->shipmentItemStoreCollection))
		{
			$this->shipmentItemStoreCollection = ShipmentItemStoreCollection::load($this);
		}
		return $this->shipmentItemStoreCollection;
	}

	/**
	 * @param $action
	 * @param BasketItem $basketItem
	 * @param null $name
	 * @param null $oldValue
	 * @param null $value
	 */
	public function onBasketModify($action, BasketItem $basketItem, $name = null, $oldValue = null, $value = null)
	{
		switch($action)
		{
			case "UPDATE":

				if ($name == "QUANTITY")
				{

					/** @var ShipmentItemCollection $collection */
					$collection = $this->getCollection();
					$shipment = $collection->getShipment();

					if ($shipment->isShipped() != "Y")
						return true;

					if ($basketItem->getBasketCode() != $this->getBasketCode())
						return true;

					$quantity = ($value - $oldValue);

					if ($quantity != 0)
						$result = Provider::tryReserveShipmentItem($this);

					if (!empty($result) && is_array($result))
					{
						$this->setField('RESERVED_QUANTITY', $result['QUANTITY']);

						if ($quantity > 0)
						{
							if ($this->getQuantity() != $this->getReservedQuantity())
							{
								/** @var ShipmentItemCollection $shipmentItemCollection */
								$shipmentItemCollection = $this->getCollection();

								/** @var Shipment $shipment */
								$shipment = $shipmentItemCollection->getShipment();

								$shipment->setMark();
							}
						}

					}
				}
				//change quantity
				
				break;
			case "DELETE":
				// unreserve
				break;
		}
	}

	/**
	 * @return Result
	 * @throws Main\NotSupportedException
	 */
	protected function calculateDelivery()
	{
		$result = new Result();

		/** @var ShipmentItemCollection $collection */
		$collection = $this->getCollection();
		/** @var Shipment $shipment */
		$shipment = $collection->getShipment();

		/** @var ShipmentCollection $shipmentCollection */
		$shipmentCollection = $shipment->getCollection();

		/** @var Order $order */
		$order = $shipmentCollection->getOrder();

		if ($order->getId() > 0)
		{
			return $result;
		}

		$deliveryCalculate = $shipment->calculateDelivery();
		if (!$deliveryCalculate->isSuccess())
		{
			$result->addErrors($deliveryCalculate->getErrors());
		}

		if ($deliveryCalculate->getPrice() > 0)
		{
			$shipment->setField('BASE_PRICE_DELIVERY', $deliveryCalculate->getPrice());
		}

		return $result;
	}


	/**
	 * @param string $name
	 * @param null|string $oldValue
	 * @param null|string $value
	 * @throws Main\ObjectNotFoundException
	 */
	protected function addChangesToHistory($name, $oldValue = null, $value = null)
	{
		if ($this->getId() > 0)
		{
			/** @var ShipmentItemCollection $shipmentItemCollection */
			if (!$shipmentItemCollection = $this->getCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentItemCollection" not found');
			}

			/** @var Shipment $shipment */
			if (!$shipment = $shipmentItemCollection->getShipment())
			{
				throw new Main\ObjectNotFoundException('Entity "Shipment" not found');
			}

			if ($shipment->isSystem())
				return;

			/** @var ShipmentCollection $shipmentCollection */
			if (!$shipmentCollection = $shipment->getCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
			}

			$historyFields = array();

			/** @var BasketItem $basketItem */
			if ($basketItem = $this->getBasketItem())
			{
				$historyFields = array(
					'NAME' => $basketItem->getField('NAME'),
					'PRODUCT_ID' => $basketItem->getField('PRODUCT_ID'),
				);
			}

			/** @var Order $order */
			if (($order = $shipmentCollection->getOrder()) && $order->getId() > 0)
			{
				OrderHistory::addField(
					'SHIPMENT_ITEM',
					$order->getId(),
					$name,
					$oldValue,
					$value,
					$this->getId(),
					$this,
					$historyFields
				);
			}


		}
	}


	/**
	 * @return bool
	 */
	public function isChanged()
	{
		if (parent::isChanged())
		{
			return true;
		}

		/** @var ShipmentItemStoreCollection $shipmentItemCollection */
		if ($shipmentItemStoreCollection = $this->getShipmentItemStoreCollection())
		{
			if ($shipmentItemStoreCollection->isChanged())
			{
				return true;
			}
		}

		return false;
	}


//	/**
//	 * @return Entity\DeleteResult
//	 */
//	public function deleteShipmentItem()
//	{
//		$result = Internals\ShipmentItemTable::delete($this->getId());
//		if (!$result->isSuccess())
//		{
//
//		}
//
//		return $result;
//	}

//	/**
//	 * @return Entity\UpdateResult|mixed
//	 */
//	public function updateShipmentItem()
//	{
//		$attributes = $this->getFieldValues();
//
//		$changedAttributes = $this->fields->getChangedKeys();
//		if (empty($changedAttributes))
//		{
//			return new Entity\UpdateResult();
//		}
//
//		foreach ($attributes as $attributeKey => $attributeValue)
//		{
//			if (!in_array($attributeKey, $changedAttributes))
//			{
//				unset($attributes[$attributeKey]);
//			}
//		}
//
//		$result = Internals\ShipmentItemTable::update($this->getId(), $attributes);
//		if (!$result->isSuccess())
//		{
//
//		}
//
//		return $result;
//	}


}