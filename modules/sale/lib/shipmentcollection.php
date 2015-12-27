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
use Bitrix\Sale\Delivery;
use Bitrix\Sale\Internals;

Loc::loadMessages(__FILE__);

class ShipmentCollection
	extends Internals\EntityCollection
{
	/** @var OrderBase */
	protected $order;

	/** @var array */
	private $errors = array();

	/**
	 * @return Order
	 */
	protected function getEntityParent()
	{
		return $this->getOrder();
	}

	/**
	 *
	 * Deletes all shipments and creates system shipment containing the whole basket
	 *
	 * @internal
	 *
	 * @return Result
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectNotFoundException
	 */
	public function resetCollection()
	{
		/** @var Order $order */
		if (!$order = $this->getOrder())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}

		/** @var Basket $basket */
		if (!$basket = $order->getBasket())
		{
			throw new Main\ObjectNotFoundException('Entity "Basket" not found');
		}

		$result = new Result();

		$deliveryInfo = array();

		if (count($this->collection) > 0)
		{
			/** @var Shipment $shipment */
			foreach ($this->collection as $shipment)
			{
				if (empty($deliveryInfo))
				{
					if ($shipment->isSystem() && $shipment->getDeliveryId() > 0)
					{
						foreach (static::getClonedFields() as $field)
						{
							if (strval(trim($shipment->getField($field))) != '')
								$deliveryInfo[$field] = trim($shipment->getField($field));
						}
					}
				}
				$shipment->delete();
			}
		}

		$systemShipment = $this->getSystemShipment();
		$systemShipmentItemCollection = $systemShipment->getShipmentItemCollection();

		$systemShipmentItemCollection->resetCollection($basket);

		if (!empty($deliveryInfo))
		{
			$systemShipment->setFieldsNoDemand($deliveryInfo);
		}

		if (Configuration::getProductReservationCondition() == Configuration::RESERVE_ON_CREATE)
		{
			/** @var Result $r */
			$r = $this->tryReserve();
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		return $result;
	}

	/**
	 * Creates new shipment
	 *
	 * @param Delivery\Services\Base $delivery
	 * @return Shipment
	 */
	public function createItem(Delivery\Services\Base $delivery = null)
	{
		$shipment = Shipment::create($this, $delivery);
		$this->addItem($shipment);

		return $shipment;
	}

	/**
	 * @param Shipment $shipment
	 * @return Internals\CollectableEntity|void
	 */
	protected function addItem(Shipment $shipment)
	{
		/** @var Shipment $shipment */
		$shipment = parent::addItem($shipment);

		$order = $this->getOrder();
		$order->onShipmentCollectionModify(EventActions::ADD, $shipment);

		return $shipment;
	}

	/**
	 * @internal
	 *
	 * @param $index
	 * @return mixed|void
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public function deleteItem($index)
	{
		$result = new Result();
		/** @var Shipment $oldItem */
		$oldItem = parent::deleteItem($index);

		/** @var Shipment $systemShipment */
		if ($oldItem->getId() > 0 && !$oldItem->isSystem() && ($systemShipment = $this->getSystemShipment()) && $systemShipment->getId() == 0)
		{
			$r = $this->cloneShipment($oldItem, $systemShipment);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		$order = $this->getOrder();
		$order->onShipmentCollectionModify(EventActions::DELETE, $oldItem);
	}

	/**
	 * @param Shipment $item
	 * @param null $name
	 * @param null $oldValue
	 * @param null $value
	 * @return Result
	 */
	public function onItemModify(Shipment $item, $name = null, $oldValue = null, $value = null)
	{
		/** @var Order $order */
		$order = $this->getOrder();
		return $order->onShipmentCollectionModify(EventActions::UPDATE, $item, $name, $oldValue, $value);
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
	 * @return ShipmentCollection
	 * @throws Main\ArgumentNullException
	 */
	public static function load(OrderBase $order)
	{
		/** @var ShipmentCollection $shipmentCollection */
		$shipmentCollection = new static();
		$shipmentCollection->setOrder($order);

		if ($order->getId() > 0)
		{
			$shipmentList = Shipment::loadForOrder($order->getId());
			/** @var Shipment $shipment */
			foreach ($shipmentList as $shipment)
			{
				$shipment->setCollection($shipmentCollection);
				$shipmentCollection->addItem($shipment);
			}
		}

		return $shipmentCollection;
	}



//	/**
//	 * @param $id
//	 * @param array $data
//	 */
//	/*
//	public function updateShipment($id, array $data)
//	{
//		if (isset($this->collection[$id]))
//		{
//			$shipment = $this->collection[$id];
//
//			$shipment->setAttributes($data);
//
//			if (array_key_exists('BASKET', $data) && !empty($data['BASKET']) && is_array($data['BASKET']))
//			{
//				$shipmentItemCollection = $shipment->getShippedItemCollection();
//				$shipmentItemCollection->updateAttributesForShipmentItem($data['BASKET']);
//			}
//
//		}
//	}
//	*/

//	/**
//	 * @param array $request
//	 */
//	public function markShipmentItemsChanged(array $request)
//	{
//
//		if (array_key_exists('SHIPMENT', $request) && !empty($request['SHIPMENT']) && is_array($request['SHIPMENT']))
//		{
//			$shipmentItemList = array();
//			foreach ($request['SHIPMENT'] as $shipmentItemDatId => $shipmentItemDat)
//			{
//				if (substr($shipmentItemDatId, 0, 1) == "n")
//				{
//					continue;
//				}
//
//				$shipmentItemList[$shipmentItemDatId] = $shipmentItemDat;
//			}
//
//			//$shipmentItems = $this->getShipmentCollection();
//		}
//
//		/** @var Order $order */
//		$order = $this->getOrder();
//		//loadForOrder
//
//		/** @var Shipment $shipment */
//		foreach (static::loadForOrder($order) as $shipment)
//		{
//			foreach ($shipment->getShipmentItemCollection() as $shipmentItem)
//			{
//
//				if (
//					isset($shipmentItemList[$shipmentItem->getId()])
//					&& $shipmentItem->getQuantity() == $shipmentItemList[$shipmentItem->getId()]['QUANTITY']
//				)
//				{
//					$shipmentItem->markChangedQuantity();
//				}
//			}
//		}
//	}


//	/**
//	 * @param $itemId
//	 * @param Shipment $shipment
//	 * @return int
//	 */
//	protected function getQuantityFromShipmentItem($itemId, Shipment $shipment)
//	{
//		$quantity = 0;
//		foreach ($shipment->getShipmentItemCollection() as $shipmentItem)
//		{
//			if ($shipmentItem->getBasketId() == $itemId)
//			{
//				$quantity += $shipmentItem->getQuantity();
//			}
//		}
//
//		return $quantity;
//	}

	/**
	 * @return Shipment
	 */
	public function getSystemShipment()
	{
		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			if ($shipment->isSystem())
				return $shipment;
		}

		$shipment = Shipment::createSystem($this);
		$this->addItem($shipment);

		return $shipment;
	}

	/**
	 * @return bool
	 */
	public function isExistsSystemShipment()
	{
		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			if ($shipment->isSystem())
				return true;
		}

		return false;
	}

	/**
	 * @return Entity\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ObjectNotFoundException
	 */
	public function save()
	{
		$result = new Entity\Result();

		$itemsFromDb = array();
		if ($this->getOrder()->getId() > 0)
		{
			$itemsFromDbList = Internals\ShipmentTable::getList(
				array(
					"filter" => array("ORDER_ID" => $this->getOrder()->getId()),
					"select" => array("ID" , "DELIVERY_NAME")
				)
			);
			while ($itemsFromDbItem = $itemsFromDbList->fetch())
				$itemsFromDb[$itemsFromDbItem["ID"]] = $itemsFromDbItem;
		}

		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			if ($shipment->isSystem())
				continue;

			if (($systemShipment = $this->getSystemShipment()) && $systemShipment->getId() == 0)
			{
				/** @var Result $r */
				$r = $this->cloneShipment($shipment, $systemShipment);
				if ($r->isSuccess())
				{
					break;
				}
				else
				{
					$result->addErrors($r->getErrors());
				}
			}
		}

		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			$r = $shipment->save();
			if (!$r->isSuccess())
				$result->addErrors($r->getErrors());

			if (isset($itemsFromDb[$shipment->getId()]))
				unset($itemsFromDb[$shipment->getId()]);
		}

		foreach ($itemsFromDb as $k => $v)
		{
			Internals\ShipmentTable::deleteWithItems($k);

			/** @var Order $order */
			if (!$order = $this->getOrder())
			{
				throw new Main\ObjectNotFoundException('Entity "Order" not found');
			}

			if ($order->getId() > 0)
			{
				OrderHistory::addAction(
					'SHIPMENT',
					$order->getId(),
					'SHIPMENT_REMOVED',
					$k,
					null,
					array(
						'ID' => $k,
						'DELIVERY_NAME' => $v['DELIVERY_NAME'],
					)
				);
			}

		}

		if ($this->getOrder()->getId() > 0)
		{
			OrderHistory::collectEntityFields('SHIPMENT', $this->getOrder()->getId());
		}

		return $result;
	}

	public function dump($i)
	{
		$s = '';
		/** @var Shipment $item */
		foreach ($this->collection as $item)
		{
			$s .= $item->dump($i);
		}
		return $s;
	}

	/**
	 * @param OrderBase $order
	 */
	public function setOrder(OrderBase $order)
	{
		$this->order = $order;
	}

	/**
	 * @internal
	 * @param Shipment $parentShipment
	 * @param Shipment $childShipment
	 * @return Result
	 * @throws Main\ArgumentOutOfRangeException
	 */
	static public function cloneShipment(Shipment $parentShipment, Shipment $childShipment)
	{
		foreach (static::getClonedFields() as $fieldName)
		{
			/** @var Result $r */
			$childShipment->setFieldNoDemand($fieldName, $parentShipment->getField($fieldName));
		}

		$childShipment->setExtraServices($parentShipment->getExtraServices());
		$childShipment->setStoreId($parentShipment->getStoreId());
		return new Result();
	}

	/**
	 * @return array
	 */
	protected static function getClonedFields()
	{
		return array(
			'DELIVERY_LOCATION',
			'PARAMS',
			'DELIVERY_ID',
			'DELIVERY_NAME',
		);
	}


	/**
	 *
	 */
//	protected function rebuildShipment()
//	{
//		$order = $this->getOrder();
//		$basketCollection = $order->getBasket();
//		$quantityList = array();
//
//		foreach ($basketCollection as $basket)
//		{
//			foreach ($this->collection as $shipment)
//			{
//				$countItem = static::getQuantityFromShipmentItem($basket->getId(), $shipment);
//				if ($countItem > 0)
//				{
//					$quantityList[$basket->getId()] += $countItem;
//				}
//
//			}
//		}
//
//		if (!empty($quantityList) && is_array($quantityList))
//		{
//			$systemShipment = false;
//
//			foreach ($basketCollection as $basket)
//			{
//				if (
//					(
//						isset($quantityList[$basket->getId()])
//						&& $basket->getQuantity() > $quantityList[$basket->getId()]
//					)
//
//					|| !isset($quantityList[$basket->getId()])
//				)
//				{
//
//					// check system shipment
//					if (!$systemShipment)
//					{
//						if (!$systemShipment = $this->getSystemShipment())
//						{
//							$r = $this->createSystemShipment();
//							if ($r->isSuccess())
//							{
//								$systemShipment = $this->getSystemShipment();
//							}
//							else
//							{
////							    $result->addErrors($r->getErrors());
//							}
//						}
//					}
//
//					$foundItem = false;
//					foreach ($systemShipment->getShipmentItemCollection() as $systemShipmentItem)
//					{
//						if ($systemShipmentItem->getBasketId() == $basket->getId())
//						{
//							$quantity = $basket->getQuantity() - $quantityList[$basket->getId()];
//							$basket->setQuantity($quantity);
//							$foundItem = true;
//							break;
//						}
////						$quantity = $basket->getQuantity() - $quantityList[$basket->getId()];
//					}
//
//					if (!$foundItem)
//					{
//
//						$fieldsItem = array(
//							'ORDER_DELIVERY_ID' => $systemShipment->getId(),
//							'BASKET_ID' => $basket->getId(),
//							'QUANTITY' => ($basket->getQuantity() - $quantityList[$basket->getId()]),
//							'RESERVED_QUANTITY' => 0
//						);
//
//						$resultItem = ShipmentItem::add($fieldsItem);
//						if ($resultItem->isSuccess())
//						{
//							$data = $resultItem->getData();
//							$data['ID'] = $resultItem->getId();
//
////							$shipmentItem = new ShipmentItem;
////							$shipmentItem->setFields($data);
////							$shipmentItem->setOrder($order);
////
////							$shippedItemCollection = $systemShipment->getShipmentItemCollection();
////							$shippedItemCollection->addShipmentItem($shipmentItem);
//						}
//					}
//
//				}
//			}
//		}
//	}


	/**
	 * @param array $request
	 */
//	public function processingShipment(array $request)
//	{
//		$order = $this->getOrder();
//
//		foreach ($request as $shipmentData)
//		{
//			if (array_key_exists('ID', $shipmentData) && intval($shipmentData['ID']) > 0)
//			{
//				$this->updateShipment($shipmentData['ID'], $shipmentData);
//			}
//			else
//			{
//				$deliveryService = DeliveryService::loadById($shipmentData['DELIVERY_ID']);
//				if ($deliveryService->applicableForOrder($order, $shipmentData['BASKET']))
//				{
//					$shipment = Shipment::create($deliveryService, $shipmentData);
//					$shipment->setOrder($order);
//					$this->addShipment($shipment);
//				}
//			}
//		}
//
//		$this->rebuildShipment();
//
//	}

	/**
	 * @return \Bitrix\Main\Entity\AddResult
	 */
//	public function createSystemShipment()
//	{
//		$order = $this->getOrder();
//		$shipment = Shipment::createForOrder($order);
//		$shipment->markSystem();
//
//		/** @var \Bitrix\Main\Entity\AddResult $result */
//		if ($result->isSuccess())
//		{
//			$data = $result->getData();
//			$data['ID'] = $result->getId();
//
//			$shipment = new Shipment;
//			$shipment->setAttributes($data);
//			$shipment->setShipmentCollection($this->shipmentCollection);
//			$shipment->setOrder($this);
//
//			$this->addShipment($shipment);
//		}
//
//		return $result;
//	}



	/**
	 * @return bool
	 */
	public function isShipped()
	{
		if (!empty($this->collection) && is_array($this->collection))
		{
			/** @var Shipment $shipment */
			foreach ($this->collection as $shipment)
			{
				if ($shipment->isSystem())
					continue;

				if (!$shipment->isShipped())
					return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function isMarked()
	{
		if (!empty($this->collection) && is_array($this->collection))
		{
			/** @var Shipment $shipment */
			foreach ($this->collection as $shipment)
			{
				if ($shipment->isSystem())
					continue;

				if ($shipment->isMarked())
					return true;
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function isReserved()
	{
		if (!empty($this->collection) && is_array($this->collection))
		{
			/** @var Shipment $shipment */
			foreach ($this->collection as $shipment)
			{
				if ($shipment->isSystem())
				{
					if (count($this->collection) == 1)
						return $shipment->isReserved();

					continue;
				}

				if (!$shipment->isReserved())
					return false;
			}

			return true;
		}

		return false;
	}



	/**
	 * @return bool
	 */
	public function isAllowDelivery()
	{
		if (!empty($this->collection) && is_array($this->collection))
		{
			/** @var Shipment $shipment */
			foreach ($this->collection as $shipment)
			{
				if ($shipment->isSystem())
					continue;

				if (!$shipment->isAllowDelivery())
					return false;
			}

			return true;
		}

		return false;
	}



//	/**
//	 * @return float
//	 */
//	public function getPrice()
//	{
//		$price = 0;
//		/** @var Shipment $shipment */
//		foreach ($this->collection as $shipment)
//		{
//			$price += $shipment->getPriceDelivery();
//		}
//
//		return $price;
//	}


//	/**
//	 * @param $action
//	 * @param Payment $payment
//	 * @param null $name
//	 * @param null $oldValue
//	 * @param null $value
//	 */
//	public function onPaymentCollectionModify($action, Payment $payment, $name = null, $oldValue = null, $value = null)
//	{
//		if ($name == "PAYED" && $oldValue != $value && $value == "Y")
//		{
//
//		}
//	}

	public function allowDelivery()
	{
		$result = new Result();
		/** @var Shipment $shipment */
		foreach($this->collection as $shipment)
		{
			if ($shipment->isSystem())
				continue;

			$r = $shipment->allowDelivery();
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}
		return $result;
	}

	public function disallowDelivery()
	{
		$result = new Result();
		/** @var Shipment $shipment */
		foreach($this->collection as $shipment)
		{
			if ($shipment->isSystem())
				continue;

			$r = $shipment->disallowDelivery();
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	public function tryReserve()
	{
		$result = new Result();
		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			if ($shipment->isReserved() || $shipment->isSystem() || $shipment->isShipped())
				continue;

			$r = $shipment->tryReserve();
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	public function tryUnreserve()
	{
		$result = new Result();
		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			if (!$shipment->isReserved() || $shipment->isShipped())
				continue;

			$r = $shipment->tryUnreserve();
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		return $result;
	}

//	/**
//	 *
//	 */
//	public function reserve()
//	{
//		/** @var Shipment $shipment */
//		foreach ($this->collection as $shipment)
//		{
//			if ($shipment->isReserved())
//				continue;
//
//			$shipment->reserve();
//		}
//	}
//
//	/**
//	 * @return array
//	 */
//	public function unreserve()
//	{
//		/** @var Shipment $shipment */
//		foreach ($this->collection as $shipment)
//		{
//			if (!$shipment->isReserved())
//				continue;
//
//			$shipment->unreserve();
//		}
//	}

	/**
	 * @param $action
	 * @param BasketItem $basketItem
	 * @param null $name
	 * @param null $oldValue
	 * @param null $value
	 * @return Result
	 * @throws Main\NotImplementedException
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectNotFoundException
	 */
	public function onBasketModify($action, BasketItem $basketItem, $name = null, $oldValue = null, $value = null)
	{
		if ($action != EventActions::UPDATE)
			throw new Main\NotImplementedException();

		/** @var Shipment $systemShipment */
		if (!$systemShipment = $this->getSystemShipment())
		{
			throw new Main\ObjectNotFoundException('Entity "Shipment" not found');
		}

		if ($name == 'QUANTITY')
		{
			$deltaQuantity = $value - $oldValue;

			if ($value == 0)
			{
				/** @var Shipment $shipment */
				foreach ($this->collection as $shipment)
				{

					/** @var ShipmentItemCollection $shipmentItemCollection */
					if (!$shipmentItemCollection = $shipment->getShipmentItemCollection())
					{
						throw new Main\ObjectNotFoundException('Entity "ShipmentItemCollection" not found');
					}

					/** @var ShipmentItem $shipmentItem */
					foreach ($shipmentItemCollection as $shipmentItem)
					{
						if ($shipmentItem->getBasketCode() == $basketItem->getBasketCode())
						{
							if ($shipment->isSystem())
							{
								$shipmentItem->setFieldNoDemand('QUANTITY', 0);
							}

							$shipmentItem->delete();
						}
					}
				}

			}
			elseif ($deltaQuantity < 0)
			{
				$basketItemQuantity = $this->getBasketItemQuantity($basketItem);
				if ($basketItemQuantity > $value)
				{
					$result = new Result();
					if (!$basketItem->isBundleChild() && !isset($this->errors[$basketItem->getBasketCode()]['SALE_ORDER_SYSTEM_SHIPMENT_LESS_QUANTITY']))
					{
						$result->addError(new ResultError(
											Loc::getMessage('SALE_ORDER_SYSTEM_SHIPMENT_LESS_QUANTITY',
															array(
																	'#PRODUCT_NAME#' => $basketItem->getField("NAME"),
																	'#QUANTITY#' => ($basketItemQuantity - $value)
															)
												),
											'SALE_ORDER_SYSTEM_SHIPMENT_LESS_QUANTITY'));

						$this->errors[$basketItem->getBasketCode()]['SALE_ORDER_SYSTEM_SHIPMENT_LESS_QUANTITY'] = $basketItemQuantity - $value;
					}



					return $result;
				}
			}

		}



		return $systemShipment->onBasketModify($action, $basketItem, $name, $oldValue, $value);
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

					$isShipped = false;
					/** @var Shipment $shipment */
					foreach ($this->collection as $shipment)
					{
						if ($shipment->isShipped())
						{
							$isShipped = true;
							break;
						}
					}

					if ($isShipped)
					{
						$result->addError(new ResultError(Loc::getMessage('SALE_ORDER_CANCEL_SHIPMENT_EXIST_SHIPPED'), 'SALE_ORDER_CANCEL_SHIPMENT_EXIST_SHIPPED'));
						return $result;
					}

					$this->tryUnreserve();
				}
				else
				{
					/** @var Shipment $shipment */
					foreach ($this->collection as $shipment)
					{
						if ($shipment->needReservation())
						{
							/** @var Result $r */
							$r = $shipment->tryReserve();
							if (!$r->isSuccess())
							{
								$shipment->setField('MARKED', 'Y');

								if (is_array($r->getErrorMessages()))
								{
									$oldErrorText = $shipment->getField('REASON_MARKED');
									foreach($r->getErrorMessages() as $error)
									{
										$oldErrorText .= (strval($oldErrorText) != '' ? "\n" : ""). $error;
									}

									$shipment->setField('REASON_MARKED', $oldErrorText);
								}

								$result->addErrors($r->getErrors());
							}
						}
					}

				}
			break;

			case "MARKED":
				if ($value == "N")
				{
					/** @var Shipment $shipment */
					foreach ($this->collection as $shipment)
					{
						if ($shipment->isSystem())
							continue;

						$shipment->setField('MARKED', $value);
					}
				}
			break;
		}

		return $result;
	}

	/**
	 * @return Result
	 * @throws Main\NotSupportedException
	 */
	public function refreshData()
	{
		$result = new Result();

		$this->resetData();

		$r = $this->calculateDelivery();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		return $result;
	}


	/**
	 * @return Result
	 */
	public function calculateDelivery()
	{
		/** @var Result $result */
		$result = new Result();

		$shipmentListResult = array();

		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			if ($shipment->isSystem() || $shipment->getDeliveryId() == 0)
				continue;

			if ($shipment->isCustomPrice())
			{
//				$shipmentListResult[] = array(
//					'SHIPMENT_ITEM' => $shipment,
//					'PRICE' => $shipment->getPrice(),
//					'AVAILABLE' => true,
//				);
//				continue;

				$priceDelivery = $shipment->getPrice();
				$shipment->setField('BASE_PRICE_DELIVERY', $priceDelivery);
			}
			else
			{

				$deliveryCalculate = $shipment->calculateDelivery();
				if (!$deliveryCalculate->isSuccess())
				{
					$result->addErrors($deliveryCalculate->getErrors());
					continue;
				}

				$deliveryCalculateData = $deliveryCalculate->getData();

				if (!isset($deliveryCalculateData['AVAILABLE']))
				{
					$result->addError(new ResultError(Loc::getMessage('SALE_ORDER_DELIVERY_SERVICE_NOT_AVAILABLE'), 'DELIVERY_SERVICE_NOT_AVAILABLE'));
					continue;
				}

				if ($deliveryCalculate->getPrice() < 0)
				{
					$result->addError(new ResultError(Loc::getMessage('SALE_ORDER_SHIPMENT_WRONG_DELIVERY_PRICE'), 'WRONG_DELIVERY_PRICE'));
					continue;
				}

				$shipment->setField('BASE_PRICE_DELIVERY', $deliveryCalculate->getPrice());

	//			$shipmentListResult[] = array(
	//				'SHIPMENT_ITEM' => $shipment,
	//				'PRICE' => $deliveryCalculate->getPrice(),
	//				'AVAILABLE' => $deliveryCalculateData['AVAILABLE'],
	//			);

			}

		}

		// event OnSaleCalculateOrderDelivery
		return $result;
	}


	/**
	 *
	 */
	public function resetData()
	{
		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			if ($shipment->isSystem())
				continue;

			$shipment->resetData();
		}
	}

	/**
	 * @param BasketItem $basketItem
	 * @return float|int
	 * @throws Main\ObjectNotFoundException
	 */
	public function getBasketItemQuantity(BasketItem $basketItem)
	{
		$allQuantity = 0;
		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			if ($shipment->isSystem())
				continue;

			/** @var ShipmentItemCollection $shipmentItemCollection */
			if (!$shipmentItemCollection = $shipment->getShipmentItemCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentItemCollection" not found');
			}

			/** @var ShipmentItem $shipmentItem */
			foreach ($shipmentItemCollection as $shipmentItem)
			{
				if ($shipmentItem->getBasketCode() == $basketItem->getBasketCode())
				{
					$allQuantity += $shipmentItem->getQuantity();
				}
			}
		}

		return $allQuantity;
	}

	/**
	 * @return float
	 */
	public function getBasePriceDelivery()
	{
		$sum = 0;
		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			if ($shipment->isSystem())
				continue;

			$sum += $shipment->getField('BASE_PRICE_DELIVERY');
		}


		return $sum;
	}

	/**
	 * @return float
	 */
	public function getPriceDelivery()
	{
		$sum = 0;
		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			if ($shipment->isSystem())
				continue;

			$sum += $shipment->getPrice();
		}


		return $sum;
	}

	/**
	 * @param $itemCode
	 * @return Shipment|null
	 */
	public function getItemByShipmentCode($itemCode)
	{
		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			$shipmentCode = $shipment->getShipmentCode();
			if ($itemCode == $shipmentCode)
				return $shipment;

		}

		return null;
	}

	/**
	 * @return int
	 */
	public function getWeight()
	{
		$weight = 0;
		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			if ($shipment->isSystem())
				continue;

			$weight += $shipment->getWeight();

		}

		return $weight;
	}


}
