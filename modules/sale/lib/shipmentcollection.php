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
	 * Getting the parent entity
	 * @return Order - order entity
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

		/** @var Shipment $systemShipment */
		if (!$systemShipment = $this->getSystemShipment())
		{
			throw new Main\ObjectNotFoundException('Entity "Shipment" not found');
		}

		/** @var ShipmentItemCollection $systemShipmentItemCollection */
		if (!$systemShipmentItemCollection = $systemShipment->getShipmentItemCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentItemCollection" not found');
		}

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
	
	/**
	* <p>Метод создает новую отгрузку. Метод нестатический.</p>
	*
	*
	* @param mixed $Bitrix  Сущность службы доставки.
	*
	* @param Bitri $Sale  
	*
	* @param Sal $Delivery  
	*
	* @param Deliver $Services  
	*
	* @param Base $delivery = null 
	*
	* @return \Bitrix\Sale\Shipment 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/shipmentcollection/createitem.php
	* @author Bitrix
	*/
	public function createItem(Delivery\Services\Base $delivery = null)
	{
		$shipment = Shipment::create($this, $delivery);
		$this->addItem($shipment);

		return $shipment;
	}

	/**
	 * Adding shipping to the collection
	 *
	 * @param Internals\CollectableEntity $shipment
	 * @return Internals\CollectableEntity|void
	 */
	protected function addItem(Internals\CollectableEntity $shipment)
	{
		/** @var Shipment $shipment */
		$shipment = parent::addItem($shipment);

		/** @var Order $order */
		if (!$order = $this->getOrder())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}

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
	 * Processing changes the essence of the shipment fields
	 *
	 * @param Internals\CollectableEntity $item
	 * @param null $name
	 * @param null $oldValue
	 * @param null $value
	 * @return Result
	 */
	
	/**
	* <p>Обработчик событий, вызываемый при изменении значений полей отгрузки. Метод нестатический.</p>
	*
	*
	* @param mixed $Bitrix  Объект отгрузки.
	*
	* @param Bitri $Sale  Имя параметра.
	*
	* @param Shipment $item  Старое значение.
	*
	* @param null $name = null Новое значение.
	*
	* @param null $oldValue = null 
	*
	* @param null $value = null 
	*
	* @return \Bitrix\Sale\Result 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/shipmentcollection/onitemmodify.php
	* @author Bitrix
	*/
	public function onItemModify(Internals\CollectableEntity $item, $name = null, $oldValue = null, $value = null)
	{
		/** @var Order $order */
		$order = $this->getOrder();
		return $order->onShipmentCollectionModify(EventActions::UPDATE, $item, $name, $oldValue, $value);
	}

	/**
	 * Getting entity of the order
	 *
	 * @return Order
	 */
	
	/**
	* <p>Метод получает сущность заказа. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Sale\Order 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/shipmentcollection/getorder.php
	* @author Bitrix
	*/
	public function getOrder()
	{
		return $this->order;
	}

	/**
	 * Loaded from the database collection shipments Order
	 *
	 * @param OrderBase $order
	 * @return ShipmentCollection
	 * @throws Main\ArgumentNullException
	 */
	
	/**
	* <p>Получает из базы данные по отгрузке заказа и помещает в коллекцию. Метод статический.</p>
	*
	*
	* @param mixed $Bitrix  Объект заказа.
	*
	* @param Bitri $Sale  
	*
	* @param OrderBase $order  
	*
	* @return \Bitrix\Sale\ShipmentCollection 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/shipmentcollection/load.php
	* @author Bitrix
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


	/**
	 * Getting the system shipment
	 *
	 * @return Shipment
	 */
	
	/**
	* <p>Метод получает системную отгрузку. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Sale\Shipment 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/shipmentcollection/getsystemshipment.php
	* @author Bitrix
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
	 * Check whether there is a system collection of shipping
	 *
	 * @return bool
	 */
	
	/**
	* <p>Проверяет, существует ли системная отгрузка в коллекции. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/shipmentcollection/isexistssystemshipment.php
	* @author Bitrix
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
	 * Saving data collection
	 *
	 * @return Entity\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ObjectNotFoundException
	 */
	
	/**
	* <p>Сохраняет данные коллекции. Метод нестатический.</p> <p>Без параметров</p>
	*
	*
	* @return \Bitrix\Main\Entity\Result 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/shipmentcollection/save.php
	* @author Bitrix
	*/
	public function save()
	{
		$result = new Entity\Result();

		/** @var Order $order */
		if (!$order = $this->getOrder())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}

		$itemsFromDb = array();
		if ($order->getId() > 0)
		{
			$itemsFromDbList = Internals\ShipmentTable::getList(
				array(
					"filter" => array("ORDER_ID" => $order->getId()),
					"select" => array("ID" , "DELIVERY_NAME", "DELIVERY_ID")
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

		$changeMeaningfulFields = array(
			"DELIVERY_LOCATION",
			"PRICE_DELIVERY",
			"CUSTOM_PRICE_DELIVERY",
			"ALLOW_DELIVERY",
			"DEDUCTED",
			"RESERVED",
			"DELIVERY_NAME",
			"DELIVERY_ID",
			"CANCELED",
			"MARKED",
			"SYSTEM",
			"COMPANY_ID",
			"DISCOUNT_PRICE",
			"BASE_PRICE_DELIVERY",
			"EXTERNAL_DELIVERY",
		);

		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			$isNew = (bool)($shipment->getId() <= 0);
			$isChanged = $shipment->isChanged();

			if ($order->getId() > 0 && $isChanged)
			{
				$logFields = array();


				$fields = $shipment->getFields();
				$originalValues = $fields->getOriginalValues();

				foreach($originalValues as $originalFieldName => $originalFieldValue)
				{
					if (in_array($originalFieldName, $changeMeaningfulFields) && $shipment->getField($originalFieldName) != $originalFieldValue)
					{
						$logFields[$originalFieldName] = $shipment->getField($originalFieldName);
						if (!$isNew)
							$logFields['OLD_'.$originalFieldName] = $originalFieldValue;
					}
				}

			}

			$r = $shipment->save();
			if ($r->isSuccess())
			{
				if ($order->getId() > 0)
				{
					if ($isChanged)
					{
						OrderHistory::addLog('SHIPMENT', $order->getId(), $isNew ? 'SHIPMENT_ADD' : 'SHIPMENT_UPDATE', $shipment->getId(), $shipment, $logFields , OrderHistory::SALE_ORDER_HISTORY_LOG_LEVEL_1);
						
						OrderHistory::addAction(
							'SHIPMENT',
							$order->getId(),
							"SHIPMENT_SAVED",
							$shipment->getId(),
							$shipment
						);
					}
				}

			}
			else
			{
				$result->addErrors($r->getErrors());
			}

			if (isset($itemsFromDb[$shipment->getId()]))
				unset($itemsFromDb[$shipment->getId()]);
		}

		$itemEventName = Shipment::getEntityEventName();

		foreach ($itemsFromDb as $k => $v)
		{
			/** @var Main\Event $event */
			$event = new Main\Event('sale', "OnBefore".$itemEventName."Deleted", array(
					'VALUES' => $v,
			));
			$event->send();

			Internals\ShipmentTable::deleteWithItems($k);

			/** @var Main\Event $event */
			$event = new Main\Event('sale', "On".$itemEventName."Deleted", array(
					'VALUES' => $v,
			));
			$event->send();

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
						'DELIVERY_ID' => $v['DELIVERY_ID'],
					)
				);
			}

		}

		if ($order->getId() > 0)
		{
			OrderHistory::collectEntityFields('SHIPMENT', $order->getId());
		}

		return $result;
	}

	/**
	 * The attachment order to the collection
	 *
	 * @param OrderBase $order
	 */
	
	/**
	* <p>Связывает заказ с коллекцией отгрузок. Метод нестатический.</p>
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/shipmentcollection/setorder.php
	* @author Bitrix
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
	 * Fields that are cloned into the system from a conventional shipping
	 *
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
	 * Is the entire collection shipped
	 *
	 * @return bool
	 */
	
	/**
	* <p>Проверяет, отгружены ли все отгрузки коллекции. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/shipmentcollection/isshipped.php
	* @author Bitrix
	*/
	public function isShipped()
	{
		$emptyShipment = true;
		if (!empty($this->collection) && is_array($this->collection))
		{
			/** @var Shipment $shipment */
			foreach ($this->collection as $shipment)
			{
				if ($shipment->isSystem())
					continue;

				if (!$shipment->isShipped() && !$shipment->isEmpty())
					return false;

				if (!$shipment->isEmpty())
					$emptyShipment = false;
			}

			if ($this->isExistsSystemShipment() && $this->isEmptySystemShipment())
				return true;

			if ($emptyShipment)
				return false;
		}

		return false;
	}

	/**
	 * Is the entire collection shipped
	 *
	 * @return bool
	 */
	public function hasShipped()
	{
		$emptyShipment = true;
		if (!empty($this->collection) && is_array($this->collection))
		{
			/** @var Shipment $shipment */
			foreach ($this->collection as $shipment)
			{
				if ($shipment->isSystem())
					continue;

				if ($shipment->isShipped() && !$shipment->isEmpty())
					return true;

				if (!$shipment->isEmpty())
					$emptyShipment = false;
			}

			if ($this->isExistsSystemShipment() && $this->isEmptySystemShipment())
				return true;

			if ($emptyShipment)
				return false;
		}

		return false;
	}

	/**
	 * Is the entire collection of marked
	 *
	 * @return bool
	 */
	
	/**
	* <p>Проверяет, есть ли в коллекции помеченная отгрузка. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/shipmentcollection/ismarked.php
	* @author Bitrix
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
	 * Is the entire collection reserved
	 *
	 * @return bool
	 */
	
	/**
	* <p>Метод проверяет, зарезервированы ли все отгрузки коллекции. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/shipmentcollection/isreserved.php
	* @author Bitrix
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
	 * Is the entire collection allowed for shipment
	 *
	 * @return bool
	 */
	
	/**
	* <p>Метод проверяет, разрешена ли отгрузка для всей коллекции. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/shipmentcollection/isallowdelivery.php
	* @author Bitrix
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

	/**
	 * @return bool
	 */
	public function hasAllowDelivery()
	{
		if (!empty($this->collection) && is_array($this->collection))
		{
			/** @var Shipment $shipment */
			foreach ($this->collection as $shipment)
			{
				if ($shipment->isSystem())
					continue;
				
				if ($shipment->isAllowDelivery())
					return true;
			}
		}

		return false;
	}

	/**
	 * Is the a system shipped empty
	 *
	 * @return bool
	 */
	
	/**
	* <p>Проверяет, пуста ли системная отгрузка. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/shipmentcollection/isemptysystemshipment.php
	* @author Bitrix
	*/
	public function isEmptySystemShipment()
	{
		/** @var Shipment $item */
		foreach ($this->collection as $item)
		{
			if ($item->isSystem())
			{
				return $item->isEmpty();
			}
		}

		return true;
	}

	/**
	 * Resolution fact shipment to shipment collection
	 *
	 * @return Result
	 */
	
	/**
	* <p>Метод выставляет разрешение на отгрузку всем частичным отгрузкам коллекции. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Sale\Result 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/shipmentcollection/allowdelivery.php
	* @author Bitrix
	*/
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

	/**
	 * Prohibition upon shipment to shipment collection
	 * @return Result
	 */
	
	/**
	* <p>Метод выставляет запрет на отгрузку всем частичным отгрузкам коллекции. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Sale\Result 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/shipmentcollection/disallowdelivery.php
	* @author Bitrix
	*/
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
	 * Trying to reserve the contents of the shipment collection
	 *
	 * @return Result
	 */
	
	/**
	* <p>Пробует, можно ли зарезервировать товар из коллекции отгрузок. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Sale\Result 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/shipmentcollection/tryreserve.php
	* @author Bitrix
	*/
	public function tryReserve()
	{
		$result = new Result();
		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			if ($shipment->isReserved() || $shipment->isShipped())
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
	 * Trying to reserve the contents of the shipment collection
	 * @return Result
	 */
	
	/**
	* <p>Метод пытается разрезервировать товар из коллекции отгрузок. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Sale\Result 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/shipmentcollection/tryunreserve.php
	* @author Bitrix
	*/
	public function tryUnreserve()
	{
		$result = new Result();
		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			if ($shipment->isShipped())
				continue;

			$r = $shipment->tryUnreserve();
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		return $result;
	}


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

		$result = new Result();

		$currentShipment = null;
		$allowQuantityChange = false;

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

					$r = $shipmentItemCollection->deleteByBasketItem($basketItem);
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
					}
				}

			}
			elseif ($deltaQuantity != 0)
			{
				if (count($this->collection) == 1 || (count($this->collection) == 2) && $this->isExistsSystemShipment())
				{
					/** @var Shipment $shipment */
					foreach ($this->collection as $shipment)
					{
						if ($shipment->isSystem())
						{
							if ($shipment->isExistBasketItem($basketItem))
							{
								$allowQuantityChange = false;
								$currentShipment = null;
								break;
							}
						}
						elseif ($shipment->isExistBasketItem($basketItem))
						{
							$allowQuantityChange = true;
							$currentShipment = $shipment;
						}
						elseif ($basketItem->getId() == 0)
						{
							$allowQuantityChange = true;
							$currentShipment = $shipment;
							break;
						}
					}
				}

				if ($allowQuantityChange && $currentShipment)
				{
					$allowQuantityChange = (bool)(!$currentShipment->isAllowDelivery() && !$currentShipment->isCanceled() && !$currentShipment->isShipped());

					if ($allowQuantityChange)
					{
						/** @var DeliveryService $deliveryService */
						if ($deliveryService = $currentShipment->getDelivery())
						{
							$allowQuantityChange = $deliveryService->isAllowEditShipment();
						}
					}
				}

				if (!$allowQuantityChange && $deltaQuantity < 0)
				{
					$basketItemQuantity = $this->getBasketItemQuantity($basketItem);
					if ($basketItemQuantity > $value)
					{
						if (!$basketItem->isBundleChild() && !isset($this->errors[$basketItem->getBasketCode()]['SALE_ORDER_SYSTEM_SHIPMENT_LESS_QUANTITY']))
						{
							$result->addError(new ResultError(
												Loc::getMessage('SALE_ORDER_SYSTEM_SHIPMENT_LESS_QUANTITY',
																array(
																		'#PRODUCT_NAME#' => $basketItem->getField("NAME"),
																		'#BASKET_ITEM_QUANTITY#' => ($basketItemQuantity),
																		'#BASKET_ITEM_MEASURE#' => $basketItem->getField("MEASURE_NAME"),
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

		}

		if(!$result->isSuccess())
			return $result;

		$r = $systemShipment->onBasketModify($action, $basketItem, $name, $oldValue, $value);
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
			return $result;
		}

		if ($name == 'QUANTITY')
		{
			if ($allowQuantityChange)
			{
				if ($currentShipment)
				{
					/** @var ShipmentItemCollection $shipmentItemCollection */
					if (!$shipmentItemCollection = $currentShipment->getShipmentItemCollection())
					{
						throw new Main\ObjectNotFoundException('Entity "ShipmentItemCollection" not found');
					}

					if ($shipmentItem = $shipmentItemCollection->getItemByBasketCode($basketItem->getBasketCode()))
					{
						$r = $shipmentItem->setField(
								"QUANTITY",
								$shipmentItem->getField("QUANTITY") + $deltaQuantity
						);

						if ($r->isSuccess())
						{
							if ($deltaQuantity < 0)
							{
								$r = $systemShipment->onBasketModify($action, $basketItem, $name, $oldValue, $value);
								if (!$r->isSuccess())
								{
									$result->addErrors($r->getErrors());
									return $result;
								}
							}

							/** @var Delivery\CalculationResult $deliveryCalculate */
							$deliveryCalculate = $currentShipment->calculateDelivery();
							if (!$deliveryCalculate->isSuccess())
							{
								$result->addErrors($deliveryCalculate->getErrors());
							}

							if ($deliveryCalculate->getPrice() > 0)
							{
								$currentShipment->setField('BASE_PRICE_DELIVERY', $deliveryCalculate->getPrice());
							}
						}
						else
						{
							$result->addErrors($r->getErrors());
						}
					}
					else
					{
						if ($shipmentItem = $shipmentItemCollection->createItem($basketItem))
						{
							$shipmentItem->setField("QUANTITY", $basketItem->getQuantity());
						}
					}
				}
			}
		}

		return $result;
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

		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			if ($shipment->isSystem() || $shipment->getDeliveryId() == 0)
				continue;

			if ($shipment->isCustomPrice())
			{
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

				if ($deliveryCalculate->getPrice() < 0)
				{
					$result->addError(new ResultError(Loc::getMessage('SALE_ORDER_SHIPMENT_WRONG_DELIVERY_PRICE'), 'WRONG_DELIVERY_PRICE'));
					continue;
				}

				$shipment->setField('BASE_PRICE_DELIVERY', $deliveryCalculate->getPrice());

			}

		}

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

			$allQuantity += $shipment->getBasketItemQuantity($basketItem);
		}

		return $allQuantity;
	}

	/**
	 * @param BasketItem $basketItem
	 * @param bool|false $includeSystemShipment
	 *
	 * @return bool
	 * @throws Main\ObjectNotFoundException
	 */
	public function isExistBasketItem(BasketItem $basketItem, $includeSystemShipment = false)
	{
		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			if (!$includeSystemShipment && $shipment->isSystem())
				continue;

			return $shipment->isExistBasketItem($basketItem);
		}

		return false;
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

	/**
	 * @return Result
	 */
	public function verify()
	{
		$result = new Result();

		/** @var Shipment $shipment */
		foreach ($this->collection as $shipment)
		{
			$r = $shipment->verify();
			if (!$r->isSuccess())
			{
				if ($shipment->isSystem())
				{
					$result->addNotices($r->getErrors());
				}
				else
				{
					$result->addErrors($r->getErrors());
				}
			}
		}
		return $result;
	}


	/**
	 * @internal
	 * @param \SplObjectStorage $cloneEntity
	 *
	 * @return ShipmentCollection
	 */
	public function createClone(\SplObjectStorage $cloneEntity)
	{
		if ($this->isClone() && $cloneEntity->contains($this))
		{
			return $cloneEntity[$this];
		}
		
		$shipmentCollectionClone = clone $this;
		$shipmentCollectionClone->isClone = true;

		if ($this->order)
		{
			if ($cloneEntity->contains($this->order))
			{
				$shipmentCollectionClone->order = $cloneEntity[$this->order];
			}
		}

		if (!$cloneEntity->contains($this))
		{
			$cloneEntity[$this] = $shipmentCollectionClone;
		}

		/**
		 * @var int key
		 * @var Shipment $shipment
		 */
		foreach ($shipmentCollectionClone->collection as $key => $shipment)
		{
			if (!$cloneEntity->contains($shipment))
			{
				$cloneEntity[$shipment] = $shipment->createClone($cloneEntity);
			}

			$shipmentCollectionClone->collection[$key] = $cloneEntity[$shipment];
		}

		return $shipmentCollectionClone;
	}

}
