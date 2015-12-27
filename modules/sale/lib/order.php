<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Main\Config;
use Bitrix\Main\Entity;
use Bitrix\Main;
use Bitrix\Main\Type;
use Bitrix\Sale\Compatible\BasketCompatibility;
use Bitrix\Sale\Compatible\EventCompatibility;
use Bitrix\Sale\Internals;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Order
	extends OrderBase implements \IShipmentOrder, \IPaymentOrder
{

	private $isNew = null;
	/** @var Discount $discount */
	protected $discount = null;

	const SALE_ORDER_LOCK_STATUS_RED = 'red';
	const SALE_ORDER_LOCK_STATUS_GREEN = 'green';
	const SALE_ORDER_LOCK_STATUS_YELLOW = 'yellow';


	protected $isStartField = null;
	protected $isMeaningfulField = false;
	protected $isOnlyMathAction = null;

	/**
	 * Modify shipment collection.
	 *
	 * @param string $action				Action code.
	 * @param Shipment $shipment			Shipment.
	 * @param null|string $name					Field name.
	 * @param null|string|int|float $oldValue				Old value.
	 * @param null|string|int|float $value					New value.
	 * @return bool
	 *
	 * @throws Main\NotImplementedException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\NotSupportedException
	 * @throws \Exception
	 */
	public function onShipmentCollectionModify($action, Shipment $shipment, $name = null, $oldValue = null, $value = null)
	{
		global $USER;

		$result = new Result();

		if ($action == EventActions::DELETE)
		{
			if ($this->getField('DELIVERY_ID') == $shipment->getDeliveryId())
			{
				/** @var ShipmentCollection $shipmentCollection */
				if (!$shipmentCollection = $shipment->getCollection())
				{
					throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
				}

				$foundShipment = false;

				/** @var Shipment $entityShipment */
				foreach ($shipmentCollection as $entityShipment)
				{
					if ($entityShipment->isSystem())
						continue;

					if (intval($entityShipment->getField('DELIVERY_ID')) > 0)
					{
						$foundShipment = true;
						$this->setFieldNoDemand('DELIVERY_ID', $entityShipment->getField('DELIVERY_ID'));
						break;
					}
				}

				if (!$foundShipment && !$shipment->isSystem())
				{
					/** @var Shipment $systemShipment */
					if (($systemShipment = $shipmentCollection->getSystemShipment()) && intval($systemShipment->getField('DELIVERY_ID')) > 0)
					{
						$this->setFieldNoDemand('DELIVERY_ID', $systemShipment->getField('DELIVERY_ID'));
					}
				}
			}
		}

		if ($action != EventActions::UPDATE)
			return $result;



		// PRICE_DELIVERY, ALLOW_DELIVERY, DEDUCTED, MARKED
		// CANCELED, DELIVERY_ID
		if ($name == "ALLOW_DELIVERY")
		{
			if ($this->isCanceled())
			{
				$result->addError(new ResultError(Loc::getMessage('SALE_ORDER_ALLOW_DELIVERY_ORDER_CANCELED'), 'SALE_ORDER_ALLOW_DELIVERY_ORDER_CANCELED'));
				return $result;
			}

			$r = $shipment->deliver();
			if ($r->isSuccess())
			{
				$event = new Main\Event('sale', EventActions::EVENT_ON_SHIPMENT_DELIVER, array(
					'ENTITY' =>$shipment
				));
				$event->send();
			}
			else
			{
				$result->addErrors($r->getErrors());
			}

			if (Configuration::getProductReservationCondition() == Configuration::RESERVE_ON_ALLOW_DELIVERY)
			{
				if ($value == "Y")
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
				else
				{
					if (!$shipment->isShipped())
					{
						/** @var Result $r */
						$r = $shipment->tryUnreserve();
						if (!$r->isSuccess())
						{
							$result->addErrors($r->getErrors());
						}
					}
				}
			}

			if ($oldValue == "N")
			{
				$orderStatus = Config\Option::get('sale', 'status_on_allow_delivery', '');

				if (strval($orderStatus) != '')
				{
					if ($USER && $USER->isAuthorized())
					{
						$statusesList = OrderStatus::getAllowedUserStatuses($USER->getID(), $this->getField('STATUS_ID'));
					}
					else
					{
						$statusesList = OrderStatus::getAllStatuses();
					}

					if($this->getField('STATUS_ID') != $orderStatus && array_key_exists($orderStatus, $statusesList))
					{
						/** @var Result $r */
						$r = $this->setField('STATUS_ID', $orderStatus);
						if (!$r->isSuccess())
						{
							$result->addErrors($r->getErrors());
						}
					}
				}

			}

			if (Configuration::needShipOnAllowDelivery() && $value == "Y")
			{
				$shipment->setField("DEDUCTED", "Y");
			}

			/** @var ShipmentCollection $shipmentCollection */
			if (!$shipmentCollection = $this->getShipmentCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
			}

			if ($shipmentCollection->isAllowDelivery() && $this->getField('ALLOW_DELIVERY') == 'N')
				$this->setFieldNoDemand('DATE_ALLOW_DELIVERY', new Type\DateTime());

			$this->setFieldNoDemand('ALLOW_DELIVERY', $shipmentCollection->isAllowDelivery() ? "Y" : "N");
		}
		elseif ($name == "DEDUCTED")
		{
			if ($this->isCanceled())
			{
				$result->addError(new ResultError(Loc::getMessage('SALE_ORDER_SHIPMENT_ORDER_CANCELED'), 'SALE_ORDER_SHIPMENT_ORDER_CANCELED'));
				return $result;
			}

			if (Configuration::getProductReservationCondition() == Configuration::RESERVE_ON_SHIP)
			{
				if ($value == "Y")
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
				else
				{
					$shipment->tryUnreserve();
				}
			}

			if ($value == "Y")
			{
				/** @var Result $r */
				$r = $shipment->tryShip();
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
					return $result;
				}

			}
			elseif ($oldValue == 'Y')
			{
				/** @var Result $r */
				$r = $shipment->tryUnship();
				if (!$r->isSuccess())
				{
					/** @var Result $resultShipment */
					$resultShipment = $shipment->setField('MARKED', 'Y');
					if (!$resultShipment->isSuccess())
					{
						$result->addErrors($r->getErrors());
					}

					if (is_array($r->getErrorMessages()))
					{
						$oldErrorText = $shipment->getField('REASON_MARKED');
						foreach($r->getErrorMessages() as $error)
						{
							$oldErrorText .= (strval($oldErrorText) != '' ? "\n" : ""). $error;
						}

						/** @var Result $resultShipment */
						$resultShipment = $shipment->setField('REASON_MARKED', $oldErrorText);
						if (!$resultShipment->isSuccess())
						{
							$result->addErrors($r->getErrors());
						}
					}
					$result->addErrors($r->getErrors());
					return $result;
				}
			}

			/** @var ShipmentCollection $shipmentCollection */
			$shipmentCollection = $shipment->getCollection();
			$this->setFieldNoDemand('DEDUCTED', $shipmentCollection->isShipped() ? "Y" : "N");

			if ($shipmentCollection->isShipped())
			{
				if (strval($shipment->getField('DATE_DEDUCTED')) != '')
				{
					$this->setFieldNoDemand('DATE_DEDUCTED', $shipment->getField('DATE_DEDUCTED'));
				}
				if (strval($shipment->getField('EMP_DEDUCTED_ID')) != '')
				{
					$this->setFieldNoDemand('EMP_DEDUCTED_ID', $shipment->getField('EMP_DEDUCTED_ID'));
				}
			}
		}
		elseif ($name == "MARKED")
		{
			if ($value == "Y")
			{
				/** @var Result $r */
				$r = $this->setField('MARKED', 'Y');
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}
		}
		elseif ($name == "REASON_MARKED")
		{
			if (!empty($value))
			{
				$orderReasonMarked = $this->getField('REASON_MARKED');
				if (is_array($value))
				{
					$newOrderReasonMarked = '';
					foreach ($value as $err)
					{
						$newOrderReasonMarked .= (strval($newOrderReasonMarked) != '' ? "\n" : "") . $err;
					}
				}
				else
				{
					$newOrderReasonMarked = $value;
				}

				/** @var Result $r */
				$r = $this->setField('REASON_MARKED', $orderReasonMarked. (strval($orderReasonMarked) != '' ? "\n" : ""). $newOrderReasonMarked);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}
		}
		elseif ($name == "BASE_PRICE_DELIVERY")
		{
			if ($this->isCanceled())
			{
				$result->addError(new ResultError(Loc::getMessage('SALE_ORDER_PRICE_DELIVERY_ORDER_CANCELED'), 'SALE_ORDER_PRICE_DELIVERY_ORDER_CANCELED'));
				return $result;
			}

			/** @var ShipmentCollection $shipmentCollection */
			if (!$shipmentCollection = $shipment->getCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
			}

			$discount = $this->getDiscount();
			$discount->setCalculateShipments($shipment);

			$r = $shipment->setField('PRICE_DELIVERY', $value);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}
		elseif ($name == "PRICE_DELIVERY")
		{
			if ($this->isCanceled())
			{
				$result->addError(new ResultError(Loc::getMessage('SALE_ORDER_PRICE_DELIVERY_ORDER_CANCELED'), 'SALE_ORDER_PRICE_DELIVERY_ORDER_CANCELED'));
				return $result;
			}

			/** @var ShipmentCollection $shipmentCollection */
			if (!$shipmentCollection = $shipment->getCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
			}

			$this->setFieldNoDemand(
				"PRICE_DELIVERY",
				$this->getField("PRICE_DELIVERY") - $oldValue + $value
			);

			/** @var Result $r */
			$r = $this->setField(
				"PRICE",
				$this->getField("PRICE") - $oldValue + $value
			);

			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}

		}
		elseif ($name == "DELIVERY_ID")
		{
			if ($shipment->isSystem() || intval($shipment->getField('DELIVERY_ID')) <= 0 )
			{
				return $result;
			}

			$this->setFieldNoDemand('DELIVERY_ID', $shipment->getField('DELIVERY_ID'));
		}

		return $result;
	}

	/**
	 * Fill basket.
	 *
	 * @param Basket $basket			Basket.
	 * @return Result
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectNotFoundException
	 */
	public function setBasket(Basket $basket)
	{
		$result = new Result();

		$isStartField = $this->isStartField();

		$r = parent::setBasket($basket);
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
			return $result;
		}

		$shipmentCollection = $this->getShipmentCollection();
		/** @var Result $r */
		$r = $shipmentCollection->resetCollection();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
			return $result;
		}

		if (!$this->isMathActionOnly())
		{
			/** @var Result $r */
			$r = $this->refreshData();
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}


		if ($isStartField)
		{
			$hasMeaningfulFields = $this->hasMeaningfulField();

			/** @var Result $r */
			$r = $this->doFinalAction($hasMeaningfulFields);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}


		return $result;
	}


	/**
	 * @param $id
	 * @return array
	 * @throws Main\ArgumentException
	 */
	static protected function loadFromDb($id)
	{
		if ($orderDat = Internals\OrderTable::getList(array(
				'filter' => array( 'ID' => $id ),
				'select' => array('*'),
		))->fetch())
		{
			return $orderDat;
		}

		return false;
	}


	/**
	 * @return mixed|static
	 */
	protected function loadBasket()
	{
		if (intval($this->getId()) > 0)
		{
			return Basket::loadItemsForOrder($this);
		}
		else
		{
			return false;
		}
	}

	/**
	 * @return ShipmentCollection
	 */
	public function getShipmentCollection()
	{
		if(empty($this->shipmentCollection))
		{
			$this->shipmentCollection = $this->loadShipmentCollection();
		}

		return $this->shipmentCollection;
	}


	/**
	 * @return PaymentCollection
	 */
	public function getPaymentCollection()
	{
		if(empty($this->paymentCollection))
		{
			$this->paymentCollection = $this->loadPaymentCollection();
		}
		return $this->paymentCollection;
	}

	/**
	 * @return ShipmentCollection|static
	 */
	static public function loadShipmentCollection()
	{
		return ShipmentCollection::load($this);
	}

	/**
	 * @return PaymentCollection
	 */
	static public function loadPaymentCollection()
	{
		return PaymentCollection::load($this);
	}

	/**
	 * @return PropertyValueCollection
	 */
	static public function loadPropertyCollection()
	{
		return PropertyValueCollection::load($this);
	}

	/**
	 * @return array
	 */
	public function getDeliverySystemId()
	{
		$result = array();
		$shipmentCollection = $this->getShipmentCollection();

		/** @var Shipment $shipment */
		foreach ($shipmentCollection as $shipment)
		{
			$result[] = $shipment->getDeliveryId();
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getPaymentSystemId()
	{
		$result = array();
		$paymentCollection = $this->getPaymentCollection();

		/** @var Payment $payment */
		foreach ($paymentCollection as $payment)
		{
			$result[] = $payment->getPaymentSystemId();
		}

		return $result;
	}


	/**
	 * @return Entity\AddResult|Entity\UpdateResult|Result|mixed
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public function save()
	{
		global $USER;

		$result = new Result();

		$id = $this->getId();
		$this->isNew = ($id == 0);

		/** @var array $oldEntityValues */
		$oldEntityValues = $this->fields->getOriginalValues();

		/** @var Main\Entity\Event $event */
		$event = new Main\Event('sale', EventActions::EVENT_ON_ORDER_BEFORE_SAVED, array(
			'ENTITY' => $this,
			'VALUES' => $oldEntityValues
		));
		$event->send();

		if ($event->getResults())
		{
			$result = new Result();
			/** @var Main\EventResult $eventResult */
			foreach($event->getResults() as $eventResult)
			{
				if($eventResult->getType() == Main\EventResult::ERROR)
				{
					$errorMsg = new ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_ON_BEFORE_ORDER_SAVED_ERROR'), 'SALE_EVENT_ON_BEFORE_ORDER_SAVED_ERROR');
					if ($eventResultData = $eventResult->getParameters())
					{
						if (isset($eventResultData['ERROR']) && $eventResultData['ERROR'] instanceof ResultError)
						{
							$errorMsg = $eventResultData['ERROR'];
						}
					}

					$result->addError($errorMsg);
				}
			}

			if (!$result->isSuccess())
			{
				return $result;
			}
		}


		$r = Provider::onOrderSave($this);
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
			return $result;
		}


		$fields = $this->fields->getValues();

		if ($id > 0)
		{
			$fields = $this->fields->getChangedValues();

			if ($this->isChanged())
			{
				$fields['DATE_UPDATE'] = new Type\DateTime();
				$this->setFieldNoDemand('DATE_UPDATE', $fields['DATE_UPDATE']);

				$fields['VERSION'] = intval($this->getField('VERSION')) + 1;
				$this->setFieldNoDemand('VERSION', $fields['VERSION']);

				$fields['UPDATED_1C'] = 'N';
				$this->setFieldNoDemand('UPDATED_1C', $fields['UPDATED_1C']);
			}

			if (!empty($fields) && is_array($fields))
			{
				$result = Internals\OrderTable::update($id, $fields);
				if (!$result->isSuccess())
					return $result;

			}
			else
			{
				$result = new Entity\UpdateResult();
			}
		}
		else
		{
			$fields['DATE_UPDATE'] = $fields['DATE_INSERT'] = new Type\DateTime();
			$this->setFieldNoDemand('DATE_INSERT', $fields['DATE_INSERT']);
			$this->setFieldNoDemand('DATE_UPDATE', $fields['DATE_UPDATE']);

			if ($USER->isAuthorized())
			{
				$fields['CREATED_BY'] = $USER->getID();
				$this->setFieldNoDemand('CREATED_BY', $fields['CREATED_BY']);
			}

			if (!isset($fields['STATUS_ID']) || strval($fields['STATUS_ID']) == '')
			{
				$orderStatus = OrderStatus::getInitialStatus();
				if (!empty($orderStatus) && !is_array($orderStatus))
				{
					$fields['STATUS_ID'] = $orderStatus;
					$this->setFieldNoDemand('STATUS_ID', $fields['STATUS_ID']);
				}
			}

			if (isset($fields['STATUS_ID']) && strval($fields['STATUS_ID']) != '')
			{
				if (!isset($fields['DATE_STATUS']) || strval($fields['DATE_STATUS']) == '')
				{
					$fields['DATE_STATUS'] = new Type\DateTime();
					$this->setFieldNoDemand('DATE_STATUS', $fields['DATE_STATUS']);
				}


				if ((!isset($fields['EMP_STATUS_ID']) || (int)$fields['EMP_STATUS_ID'] <= 0) && $USER->isAuthorized())
				{
					$fields['EMP_STATUS_ID'] = $USER->getID();
					$this->setFieldNoDemand('EMP_STATUS_ID', $fields['EMP_STATUS_ID']);
				}
			}


			$result = Internals\OrderTable::add($fields);
			if (!$result->isSuccess())
				return $result;

			$id = $result->getId();
			$this->setFieldNoDemand('ID', $id);

			/** @var Result $r */
			$r = static::setAccountNumber($id);
			if ($r->isSuccess())
			{
				if ($accountData = $r->getData())
				{
					if (array_key_exists('ACCOUNT_NUMBER', $accountData))
					{
						$this->setField('ACCOUNT_NUMBER', $accountData['ACCOUNT_NUMBER']);
					}
				}
			}
			OrderHistory::addAction('ORDER', $id, 'ORDER_ADDED', $id, $this);
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

		OrderHistory::collectEntityFields('ORDER', $id, $id);

		$this->fields->clearChanged();

		/** @var Basket $basket */
		$basket = $this->getBasket();

		/** @var Result $r */
		$r = $basket->save();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		/** @var PaymentCollection $paymentCollection */
		$paymentCollection = $this->getPaymentCollection();

		/** @var Result $r */
		$r = $paymentCollection->save();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}


		// user budget

		Internals\UserBudgetPool::onUserBudgetSave($this->getUserId());

		/** @var ShipmentCollection $shipmentCollection */
		$shipmentCollection = $this->getShipmentCollection();

		/** @var Result $r */
		$r = $shipmentCollection->save();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		/** @var Tax $tax */
		$tax = $this->getTax();

		/** @var Result $r */
		$r = $tax->save();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}


		/** @var PropertyValueCollection $propertyCollection */
		$propertyCollection = $this->getPropertyCollection();

		/** @var Result $r */
		$r = $propertyCollection->save();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		/** @var Discount $discount */
		$discount = $this->getDiscount();

		/** @var Result $r */
		$r = $discount->save();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		/** @var array $oldEntityValues */
		$oldEntityValues = $this->fields->getOriginalValues();

		$event = new Main\Event('sale', EventActions::EVENT_ON_ORDER_SAVED, array(
			'ENTITY' => $this,
			'IS_NEW' => $this->isNew,
			'VALUES' => $oldEntityValues,
		));
		$event->send();


		if (($eventList = Internals\EventsPool::getEvents($this)) && !empty($eventList) && is_array($eventList))
		{
			foreach ($eventList as $eventName => $eventData)
			{
				$event = new Main\Event('sale', $eventName, $eventData);
				$event->send();
			}

			Internals\EventsPool::resetEvents($this);
		}

		$this->isNew = false;

		return $result;
	}

	/**
	 * Delete order.
	 *
	 * @param int $id				Order id.
	 * @return Result
	 * @throws Main\ArgumentNullException
	 */
	public static function delete($id)
	{
		$result = new Result();

		if (!$order = Order::load($id))
		{
			$result->addError(new ResultError(Loc::getMessage('SALE_ORDER_ENTITY_NOT_FOUND'), 'SALE_ORDER_ENTITY_NOT_FOUND'));
			return $result;
		}

		/** @var Result $r */
		$r = $order->setField('CANCELED', 'Y');
		if (!$r->isSuccess())
		{
			return $r;
		}

		/** @var Basket $basketCollection */
		if ($basketCollection = $order->getBasket())
		{
			/** @var BasketItem $basketItem */
			foreach ($basketCollection as $basketItem)
			{
				$basketItem->delete();
			}
		}

		/** @var ShipmentCollection $shipmentCollection */
		if ($shipmentCollection = $order->getShipmentCollection())
		{
			/** @var Shipment $shipment */
			foreach ($shipmentCollection as $shipment)
			{
				$shipment->delete();
			}
		}


		/** @var PaymentCollection $paymentCollection */
		if ($paymentCollection = $order->getPaymentCollection())
		{
			/** @var Payment $payment */
			foreach ($paymentCollection as $payment)
			{
				$payment->delete();
			}
		}

		/** @var PropertyValueCollection $propertyCollection */
		if ($propertyCollection = $order->getPropertyCollection())
		{
			/** @var PropertyValue $property */
			foreach ($propertyCollection as $property)
			{
				$property->delete();
			}
		}

		$event = new Main\Event('sale', EventActions::EVENT_ON_BEFORE_ORDER_DELETE, array(
			'ENTITY' => $order
		));
		$event->send();

		foreach ($event->getResults() as $eventResult)
		{
			$return = null;
			if ($eventResult->getType() == Main\EventResult::ERROR)
			{
				continue;
			}

			if ($eventResult->getType() == Main\EventResult::SUCCESS)
			{
				$return = $eventResult->getParameters('return');

				if ($return !== null)
				{
					return $return;
				}
			}
		}

		/** @var Result $r */
		$r = $order->save();
		if ($r->isSuccess())
		{
			Internals\OrderTable::delete($id);
		}
		else
		{
			$result->addErrors($r->getErrors());
		}

		$event = new Main\Event('sale', EventActions::EVENT_ON_ORDER_DELETED, array(
			'ENTITY' => $order,
			'VALUE' => ((bool) $r->isSuccess())
		));
		$event->send();

		return $result;
	}

	/**
	 * @return bool
	 */
	public function isPaid()
	{
		return $this->getField('PAYED') == "Y" ? true : false;
	}

	/**
	 * @return bool
	 */
	public function isShipped()
	{
		$shipmentCollection = $this->getShipmentCollection();
		return $shipmentCollection->isShipped();
	}

	/**
	 * @return bool
	 */
	public function isAllowDelivery()
	{
		return $this->getField('ALLOW_DELIVERY') == "Y"? true: false;
	}

	/**
	 * @return bool
	 */
	public function isCanceled()
	{
		return $this->getField('CANCELED') == "Y"? true: false;
	}

	/**
	 * Modify payment collection.
	 *
	 * @param string $action			Action.
	 * @param Payment $payment			Payment.
	 * @param null|string $name				Field name.
	 * @param null|string|int|float $oldValue		Old value.
	 * @param null|string|int|float $value			New value.
	 * @return Result
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotImplementedException
	 * @throws Main\ObjectNotFoundException
	 */
	public function onPaymentCollectionModify($action, Payment $payment, $name = null, $oldValue = null, $value = null)
	{
		$result = new Result();

		if ($action != EventActions::UPDATE)
			return $result;

		if (($name == "CURRENCY") && ($value != $this->getField("CURRENCY")))
			throw new Main\NotImplementedException();

		if ($name == "SUM" || $name == "PAID")
		{

			if ($this->isCanceled())
			{
				$result->addError(new ResultError(Loc::getMessage('SALE_ORDER_PAID_ORDER_CANCELED'), 'SALE_ORDER_PAID_ORDER_CANCELED'));
				return $result;
			}

			if (($name == "SUM") && !$payment->isPaid())
			{
				if ($value == 0 && $payment->isInner())
					$payment->delete();

				return $result;
			}


			$r = $this->syncOrderAndPayments($payment);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}

		}
		elseif ($name == "IS_RETURN")
		{
			if ($this->isCanceled())
			{
				$result->addError(new ResultError(Loc::getMessage('SALE_ORDER_RETURN_ORDER_CANCELED'), 'SALE_ORDER_RETURN_ORDER_CANCELED'));
				return $result;
			}

			if ($value == "Y")
			{
				if (!$payment->isPaid())
				{
					$result->addError( new ResultError(Loc::getMessage('SALE_ORDER_PAYMENT_RETURN_NOT_PAID'), 'SALE_ORDER_PAYMENT_RETURN_NOT_PAID'));
					return $result;
				}

				$oldPaid = $this->isPaid()? "Y" : "N";

				/** @var PaymentCollection $paymentCollection */
				if (!$paymentCollection = $this->getPaymentCollection())
				{
					throw new Main\ObjectNotFoundException('Entity "PaymentCollection" not found');
				}

				$creditSum = 0;
				$sumPaid = $paymentCollection->getPaidSum();
				$maxPaid = $sumPaid - $this->getPrice();

				if ($maxPaid < 0)
				{
					$maxPaid = 0;
				}

				if ($payment->getSum() > $maxPaid)
				{
					$creditSum = ($payment->getSum() - $maxPaid);
				}

				$payment->setFieldNoDemand('PAID', 'N');
				if ($creditSum > 0)
				{
					Internals\UserBudgetPool::addPoolItem($this, $creditSum, Internals\UserBudgetPool::BUDGET_TYPE_ORDER_PART_RETURN, $payment);
				}

				$finalSumPaid = $this->getSumPaid() - $creditSum;
				if ($finalSumPaid != $this->getSumPaid())
				{
					$this->setFieldNoDemand('SUM_PAID', $finalSumPaid);
					$this->setFieldNoDemand('PAYED', ($this->getPrice() <= $finalSumPaid) ? "Y" : "N");
				}

				/** @var Result $r */
				$r = $this->onAfterSyncPaid($oldPaid);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}

			}
			else
			{

				if ($payment->isPaid())
				{
					$result->addError( new ResultError(Loc::getMessage('SALE_ORDER_PAYMENT_RETURN_PAID'), 'SALE_ORDER_PAYMENT_RETURN_PAID'));
					return $result;
				}

				$userBudget = Internals\UserBudgetPool::getUserBudgetByOrder($this);
				if ($userBudget < $payment->getSum())
				{
					$result->addError( new ResultError( Loc::getMessage('SALE_ORDER_PAYMENT_NOT_ENOUGH_USER_BUDGET'), "SALE_ORDER_PAYMENT_NOT_ENOUGH_USER_BUDGET") );
					return $result;
				}

				Internals\UserBudgetPool::addPoolItem($this, ($payment->getSum() * -1), Internals\UserBudgetPool::BUDGET_TYPE_ORDER_PAY, $payment);

				$r = $payment->setField('PAID', "Y");
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
					return $result;
				}

			}

		}

		return $result;
	}

	/**
	 * Modify order field.
	 *
	 * @param string $name				Field name.
	 * @param mixed|string|int|float $oldValue			Old value.
	 * @param mixed|string|int|float $value				New value.
	 * @return Entity\Result|Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotImplementedException
	 * @throws Main\ObjectNotFoundException
	 */
	protected function onFieldModify($name, $oldValue, $value)
	{
		global $USER;

		if ($name == "PRICE")
		{
			/** @var Result $r */
			$r = $this->refreshVat();
			if (!$r->isSuccess())
			{
				return $r;
			}


			/** @var ShipmentCollection $shipmentCollection */
			if (!$shipmentCollection = $this->getShipmentCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
			}

			$result = $shipmentCollection->onOrderModify($name, $oldValue, $value);
			if (!$result->isSuccess())
				return $result;

			/** @var PaymentCollection $paymentCollection */
			if (!$paymentCollection = $this->getPaymentCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "PaymentCollection" not found');
			}

			$result = $paymentCollection->onOrderModify($name, $oldValue, $value);
			if (!$result->isSuccess())
				return $result;


			return $result;
		}
		elseif ($name == "CURRENCY")
		{
			throw new Main\NotImplementedException('field CURRENCY');
		}
		elseif ($name == "PERSON_TYPE_ID")
		{
			// may be need activate properties
			//throw new Main\NotImplementedException();
		}
		elseif ($name == "CANCELED")
		{
			$event = new Main\Event('sale', EventActions::EVENT_ON_BEFORE_ORDER_CANCELED, array(
				'ENTITY' => $this
			));
			$event->send();

			/** @var PaymentCollection $paymentCollection */
			if (!$paymentCollection = $this->getPaymentCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "PaymentCollection" not found');
			}

			$result = $paymentCollection->onOrderModify($name, $oldValue, $value);
			if (!$result->isSuccess())
				return $result;

			/** @var ShipmentCollection $shipmentCollection */
			if (!$shipmentCollection = $this->getShipmentCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
			}

			$result = $shipmentCollection->onOrderModify($name, $oldValue, $value);
			if (!$result->isSuccess())
				return $result;

			$this->setField('DATE_CANCELED', new Type\DateTime());

			if ($USER->isAuthorized())
				$this->setField('EMP_CANCELED_ID', $USER->getID());

			Internals\EventsPool::addEvent($this, EventActions::EVENT_ON_ORDER_CANCELED, array(
				'ENTITY' => $this,
			));

			Internals\EventsPool::addEvent($this, EventActions::EVENT_ON_ORDER_CANCELED_SEND_MAIL, array(
				'ENTITY' => $this,
			));
		}
		elseif ($name == "USER_ID")
		{
			throw new Main\NotImplementedException('field USER_ID');
		}
		elseif($name == "MARKED")
		{
			if ($oldValue != "Y")
			{
				$this->setField('DATE_MARKED', new Type\DateTime());

				if ($USER->isAuthorized())
					$this->setField('EMP_MARKED_ID', $USER->getID());
			}
			elseif ($value == "N")
			{
				$this->setField('REASON_MARKED', '');
			}

			/** @var ShipmentCollection $shipmentCollection */
			if (!$shipmentCollection = $this->getShipmentCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
			}

			$result = $shipmentCollection->onOrderModify($name, $oldValue, $value);
			if (!$result->isSuccess())
				return $result;
		}
		elseif ($name == "STATUS_ID")
		{

			$event = new Main\Event('sale', EventActions::EVENT_ON_BEFORE_ORDER_STATUS_CHANGE, array(
				'ENTITY' => $this,
				'VALUE' => $value,
				'OLD_VALUE' => $oldValue,
			));
			$event->send();

			$this->setField('DATE_STATUS', new Type\DateTime());

			if ($USER && $USER->isAuthorized())
				$this->setField('EMP_STATUS_ID', $USER->GetID());

			Internals\EventsPool::addEvent($this, EventActions::EVENT_ON_ORDER_STATUS_CHANGE, array(
				'ENTITY' => $this,
				'VALUE' => $value,
				'OLD_VALUE' => $oldValue,
			));

			Internals\EventsPool::addEvent($this, EventActions::EVENT_ON_ORDER_STATUS_CHANGE_SEND_MAIL, array(
				'ENTITY' => $this,
				'VALUE' => $value,
				'OLD_VALUE' => $oldValue,
			));
		}
		return new Result();
	}

	/**
	 * Modify basket.
	 *
	 * @param string $action				Action.
	 * @param BasketItem $basketItem		Basket item.
	 * @param null|string $name				Field name.
	 * @param null|string|int|float $oldValue		Old value.
	 * @param null|string|int|float $value			New value.
	 * @return Result
	 * @throws Main\NotImplementedException
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectNotFoundException
	 */
	public function onBasketModify($action, BasketItem $basketItem, $name = null, $oldValue = null, $value = null)
	{
		if ($action != EventActions::UPDATE)
			return new Result();

		if ($name == "QUANTITY")
		{
			if ($value < 0)
			{
				$result = new Result();
				$result->addError( new ResultError(Loc::getMessage('SALE_ORDER_BASKET_WRONG_QUANTITY',
									array(
										'#PRODUCT_NAME#' => $basketItem->getField('NAME')
									)
				), 'SALE_ORDER_BASKET_WRONG_QUANTITY') );

				return $result;
			}

			/** @var ShipmentCollection $shipmentCollection */
			if (!$shipmentCollection = $this->getShipmentCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
			}
			$result = $shipmentCollection->onBasketModify($action, $basketItem, $name, $oldValue, $value);
			if (!$result->isSuccess())
				return $result;

			if ($value == 0)
			{

				/** @var Result $r */
				$r = $this->refreshVat();
				if (!$r->isSuccess())
				{
					return $r;
				}

				if ($tax = $this->getTax())
				{
					$tax->refreshData();
				}
			}

			if ($basketItem->isBundleChild())
				return $result;

			/** @var Result $result */
			$result = $this->setField(
				"PRICE",
				$this->getBasket()->getPrice() + $this->getShipmentCollection()->getPriceDelivery()
			);

			if ($this->getId() == 0 && !$this->isMathActionOnly())
			{
				$shipmentCollection->refreshData();
			}

			return $result;
		}
		elseif ($name == "PRICE")
		{
			/** @var Result $result */
			$result = $this->setField(
				"PRICE",
				$this->getBasket()->getPrice() + $this->getShipmentCollection()->getPriceDelivery()
			);

			/** @var ShipmentCollection $shipmentCollection */
			if (!$shipmentCollection = $this->getShipmentCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
			}

			if ($this->getId() == 0 && !$this->isMathActionOnly())
			{
				$shipmentCollection->refreshData();
			}
			return $result;
		}
		elseif ($name == "CURRENCY")
		{
			if ($value != $this->getField("CURRENCY"))
				throw new Main\NotSupportedException("CURRENCY");
		}
		elseif ($name == "DIMENSIONS")
		{
			/** @var ShipmentCollection $shipmentCollection */
			if (!$shipmentCollection = $this->getShipmentCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
			}
			return $shipmentCollection->onBasketModify($action, $basketItem, $name, $oldValue, $value);
		}

		return new Result();
	}

	/**
	 * Modify property value collection.
	 *
	 * @param string $action				Action.
	 * @param PropertyValue $property		Property.
	 * @param null|string $name				Field name.
	 * @param null|string|int|float $oldValue		Old value.
	 * @param null|string|int|float $value			New value.
	 * @return bool
	 */
	static public function onPropertyValueCollectionModify($action, PropertyValue $property, $name = null, $oldValue = null, $value = null)
	{
		return new Result();
	}

	/**
	 * Sync.
	 *
	 * @param Payment $payment			Payment.
	 * @return Result
	 * @throws Main\ObjectNotFoundException
	 */
	protected function syncOrderAndPayments(Payment $payment = null)
	{
		global $USER;

		$result = new Result();

		$oldPaid = $this->getField('PAYED');
		$paymentCollection = $this->getPaymentCollection();
		$sumPaid = $paymentCollection->getPaidSum();

		if ( $this->getId() > 0)
		{

			if ($payment)
			{
				$finalSumPaid = $sumPaid;

				if ($payment->isPaid())
				{
					if ($sumPaid > $this->getPrice())
					{
						$finalSumPaid = $this->getSumPaid() + $payment->getSum();
					}
				}
				else
				{

					$r = $this->syncOrderPaymentPaid($payment);
					if ($r->isSuccess())
					{
						$paidResult = $r->getData();
						if (isset($paidResult['SUM_PAID']))
						{
							$finalSumPaid = $paidResult['SUM_PAID'];
						}
					}

				}
			}
			else
			{
				$finalSumPaid = $this->getSumPaid();

				$r = $this->syncOrderPaid();
				if ($r->isSuccess())
				{
					$paidResult = $r->getData();
					if (isset($paidResult['SUM_PAID']))
					{
						$finalSumPaid = $paidResult['SUM_PAID'];
					}
				}
			}

		}
		else
		{
			$finalSumPaid = $sumPaid;
		}

		$paid = ($finalSumPaid > 0 && $this->getPrice() <= $finalSumPaid);

		$this->setFieldNoDemand('PAYED', $paid ? "Y" : "N");

		if ($paid && $oldPaid == "N")
		{
			$this->setFieldNoDemand('DATE_PAYED', new Type\DateTime());

			if ($USER->isAuthorized())
				$this->setFieldNoDemand('EMP_PAYED_ID', $USER->getID());

			if ($paymentCollection->isPaid() && $payment !== null)
			{
				if (strval($payment->getField('PAY_VOUCHER_NUM')) != '')
				{
					$this->setFieldNoDemand('PAY_VOUCHER_NUM', $payment->getField('PAY_VOUCHER_NUM'));
				}

				if (strval($payment->getField('PAY_VOUCHER_DATE')) != '')
				{
					$this->setFieldNoDemand('PAY_VOUCHER_DATE', $payment->getField('PAY_VOUCHER_DATE'));
				}
			}
		}

		if ($finalSumPaid > 0 && $finalSumPaid > $this->getPrice())
		{
			if (($payment && $payment->isPaid()) || !$payment)
			{
				Internals\UserBudgetPool::addPoolItem($this, $finalSumPaid - $this->getPrice(), Internals\UserBudgetPool::BUDGET_TYPE_EXCESS_SUM_PAID, $payment);
			}

			$finalSumPaid = $this->getPrice();
		}

		$this->setFieldNoDemand('SUM_PAID', $finalSumPaid);

		$r = $this->onAfterSyncPaid($oldPaid);

		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * @param Payment $payment
	 * @return Result
	 */
	protected function syncOrderPaymentPaid(Payment $payment)
	{
		$result = new Result();

		if ($payment->isPaid())
			return $result;

		$paymentCollection = $this->getPaymentCollection();
		$sumPaid = $paymentCollection->getPaidSum();

		$userBudget = Internals\UserBudgetPool::getUserBudgetByOrder($this);

		$debitSum = $payment->getSum();
		$maxPaid = ($payment->getSum() + $sumPaid) - $this->getPrice();
		if ($maxPaid >= $payment->getSum())
		{
			$finalSumPaid = $this->getSumPaid();
		}
		else
		{
			$debitSum = $maxPaid;
			$finalSumPaid = $this->getSumPaid() - $payment->getSum() + ($maxPaid > 0 ? $maxPaid : 0);
		}


		if ($maxPaid > 0)
		{
			if ($debitSum > $userBudget)
			{
				$result->addError( new ResultError('SALE_ORDER_PAYMENT_NOT_ENOUGH_USER_BUDGET', 'SALE_ORDER_PAYMENT_NOT_ENOUGH_USER_BUDGET_SYNCPAID') );
				return $result;
			}

			Internals\UserBudgetPool::addPoolItem($this, ($debitSum * -1), Internals\UserBudgetPool::BUDGET_TYPE_ORDER_CANCEL_PART, $payment);
		}

		$result->setData(array('SUM_PAID' => $finalSumPaid));

		return $result;
	}

	/**
	 * @return Result
	 */
	protected function syncOrderPaid()
	{
		$result = new Result();

		if ($this->getSumPaid() == $this->getPrice())
			return $result;

		$debitSum = $this->getPrice() - $this->getSumPaid();

		$paymentCollection = $this->getPaymentCollection();
		$sumPaid = $paymentCollection->getPaidSum();
		$userBudget = Internals\UserBudgetPool::getUserBudgetByOrder($this);

		$bePaid = $sumPaid - $this->getSumPaid();

		if ($bePaid > 0)
		{
			if ($debitSum > $bePaid)
			{
				$debitSum = $bePaid;
			}

			if ($debitSum >= $userBudget)
			{
				$debitSum = $userBudget;
			}

			if ($userBudget >= $debitSum && $debitSum > 0)
			{
				Internals\UserBudgetPool::addPoolItem($this, ($debitSum * -1), Internals\UserBudgetPool::BUDGET_TYPE_ORDER_PAY);

				$finalSumPaid = $this->getSumPaid() + $debitSum;
				$result->setData(array(
					'SUM_PAID' => $finalSumPaid
				));
			}
		}


		return $result;
	}


	/**
	 * @param null $oldPaid
	 * @return Result
	 * @throws Main\ObjectNotFoundException
	 */
	protected function onAfterSyncPaid($oldPaid = null)
	{
		global $USER;
		$result = new Result();
		/** @var PaymentCollection $paymentCollection */
		if (!$paymentCollection = $this->getPaymentCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "PaymentCollection" not found');
		}

		/** @var ShipmentCollection $shipmentCollection */
		if (!$shipmentCollection = $this->getShipmentCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
		}

		$oldPaidBool = null;

		if ($oldPaid !== null)
			$oldPaidBool = ($oldPaid == "Y");

		if ($oldPaid == "N" && $this->isPaid() )
		{

			$orderStatus = Config\Option::get('sale', 'status_on_paid', '');

			if (strval($orderStatus) != '')
			{
				if ($USER && $USER->isAuthorized())
				{
					$statusesList = OrderStatus::getAllowedUserStatuses($USER->getID(), $this->getField('STATUS_ID'));
					$statusesList = array_keys($statusesList);
				}
				else
				{
					$statusesList = OrderStatus::getAllStatuses();
				}

				if($this->getField('STATUS_ID') != $orderStatus && in_array($orderStatus, $statusesList))
				{
					$this->setField('STATUS_ID', $orderStatus);
				}
			}

		}

		if ($oldPaid !== null && $this->isPaid() != $oldPaidBool)
		{
			Internals\EventsPool::addEvent($this, EventActions::EVENT_ON_ORDER_PAID, array(
				'ENTITY' => $this,
			));

			Internals\EventsPool::addEvent($this, EventActions::EVENT_ON_ORDER_PAID_SEND_MAIL, array(
				'ENTITY' => $this,
			));
		}

		if (Configuration::getProductReservationCondition() == Configuration::RESERVE_ON_PAY)
		{
			if ($paymentCollection->hasPaidPayment())
			{
				$r = $shipmentCollection->tryReserve();
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}
			else
			{
				$r = $shipmentCollection->tryUnreserve();
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}
		}
		elseif (Configuration::getProductReservationCondition() == Configuration::RESERVE_ON_FULL_PAY)
		{
			if ($oldPaid == "N" && $this->isPaid())
			{
				$r = $shipmentCollection->tryReserve();
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}
			elseif ($oldPaid == "Y" && !$this->isPaid())
			{
				$r = $shipmentCollection->tryUnreserve();
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}
		}

		if (Configuration::needAllowDeliveryOnPay())
		{
			if ($oldPaid == "N" && $this->isPaid())
			{
				$r = $shipmentCollection->allowDelivery();
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}
			elseif ($oldPaid == "Y" && !$this->isPaid())
			{
				$r = $shipmentCollection->disallowDelivery();
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}
		}

		return $result;
	}

	/**
	 * Reset the value of the order and delivery
	 * @internal
	 * @param array $select - the list of fields which need to be reset
	 */
	public function resetData($select = array('PRICE'))
	{
		if (in_array('PRICE', $select))
		{
			$this->setFieldNoDemand('PRICE', 0);
		}

		if (in_array('PRICE_DELIVERY', $select))
		{
			$this->setFieldNoDemand('PRICE_DELIVERY', 0);
		}
	}

	/**
	 * Reset the value of taxes
	 *
	 * @internal
	 */
	public function resetTax()
	{
		$this->setFieldNoDemand('TAX_PRICE', 0);
		$this->setFieldNoDemand('TAX_VALUE', 0);
	}

	/**
	 * Full refresh order data.
	 *
	 * @param array $select				Fields list.
	 * @return Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ObjectNotFoundException
	 */
	public function refreshData($select = array())
	{
		$result = new Result();

		$isStartField = $this->isStartField();

		$this->calculateType = ($this->getId() > 0 ? static::SALE_ORDER_CALC_TYPE_REFRESH : static::SALE_ORDER_CALC_TYPE_NEW);
		$this->resetData($select);

		/** @var Basket $basket */
		$basket = $this->getBasket();
		if (!$basket)
		{
			return $result;
		}

		/** @var Result $r */
		$r = $this->setField('PRICE', $basket->getPrice());
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
			return $result;
		}

		if ($this instanceof \IShipmentOrder)
		{
			/** @var ShipmentCollection $shipmentCollection */
			if (!$shipmentCollection = $this->getShipmentCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
			}

			$r = $shipmentCollection->refreshData();
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
				return $result;
			}
		}

		/** @var Tax $tax */
		if ($tax = $this->getTax())
		{
			$tax->resetTaxList();
		}

		if ($isStartField)
		{
			$hasMeaningfulFields = $this->hasMeaningfulField();

			/** @var Result $r */
			$r = $this->doFinalAction($hasMeaningfulFields);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		return $result;
	}

	/**
	 * Get the entity of taxes
	 *
	 * @return Tax
	 */
	public function getTax()
	{
		if ($this->tax === null)
		{
			$this->tax = $this->loadTax();
		}
		return $this->tax;
	}

	/**
	 *
	 * @return Result
	 * @throws Main\ObjectNotFoundException
	 */
	protected function syncOrderTax()
	{
		$result = new Result();

		/** @var Tax $tax */
		if (!$tax = $this->getTax())
		{
			throw new Main\ObjectNotFoundException('Entity "Tax" not found');
		}

		$this->resetTax();
		/** @var Result $r */
		$r = $tax->calculate();
		if ($r->isSuccess())
		{
			$taxResult = $r->getData();
			if (isset($taxResult['TAX_PRICE']) && floatval($taxResult['TAX_PRICE']) > 0)
			{
				/** @var Result $r */
				$r = $this->setField('TAX_PRICE', $taxResult['TAX_PRICE']);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}

			if (isset($taxResult['VAT_SUM']) && floatval($taxResult['VAT_SUM']) > 0)
			{
				/** @var Result $r */
				$r = $this->setField('VAT_SUM', $taxResult['VAT_SUM']);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}

			if (isset($taxResult['VAT_DELIVERY']) && floatval($taxResult['VAT_DELIVERY']) > 0)
			{
				/** @var Result $r */
				$r = $this->setField('VAT_DELIVERY', $taxResult['VAT_DELIVERY']);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}

			/** @var Result $r */
			$r = $this->setField('TAX_VALUE', $this->isUsedVat()? $this->getVatSum() : $this->getField('TAX_PRICE'));
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}

		}
		else
		{
			$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * @return Tax|static
	 */
	protected function loadTax()
	{
		return Tax::load($this);
	}


	/**
	 * @return Discount
	 */
	public function getDiscount()
	{
		if ($this->discount === null)
		{
			$this->discount = $this->loadDiscount();
		}

		return $this->discount;
	}

	/**
	 * @return mixed
	 */
	protected function loadDiscount()
	{
		return Discount::load($this);
	}

	/**
	 * Set account number.
	 *
	 * @param int $id			Account id.
	 * @return bool
	 */
	public static function setAccountNumber($id)
	{
		return \CSaleOrder::setAccountNumberById($id);
	}

	/**
	 * apply discount.
	 * @internal
	 * @param array $data			Order data.
	 * @return Result
	 */
	public function applyDiscount(array $data)
	{
		if (!empty($data['BASKET_ITEMS']) && is_array($data['BASKET_ITEMS']))
		{
			/** @var Basket $basket */
			$basket = $this->getBasket();

			foreach ($data['BASKET_ITEMS'] as $basketCode => $basketItemData)
			{
				/** @var BasketItem $basketItem */
				if ($basketItem = $basket->getItemByBasketCode($basketCode))
				{
					if (isset($basketItemData['DISCOUNT_PRICE']) && floatval($basketItemData['DISCOUNT_PRICE']) >= 0
						&& $basketItem->getDiscountPrice() != floatval($basketItemData['DISCOUNT_PRICE']))
					{
						$basketItemData['DISCOUNT_PRICE'] = roundEx(floatval($basketItemData['DISCOUNT_PRICE']), SALE_VALUE_PRECISION);
						$basketItem->setField('DISCOUNT_PRICE', $basketItemData['DISCOUNT_PRICE']);

						if (!$basketItem->isCustomPrice())
						{
							$basketItem->setField('PRICE', $basketItem->getBasePrice() - $basketItemData['DISCOUNT_PRICE']);
						}
					}
				}
			}
		}

		if (isset($data['SHIPMENT']) && intval($data['SHIPMENT']) > 0
			&& isset($data['PRICE_DELIVERY']) && floatval($data['PRICE_DELIVERY']) >= 0)
		{
			/** @var ShipmentCollection $shipmentCollection */
			if ($shipmentCollection = $this->getShipmentCollection())
			{
				/** @var Shipment $shipment */
				if ($shipment = $shipmentCollection->getItemByShipmentCode($data['SHIPMENT']))
				{
					if (floatval($data['PRICE_DELIVERY']) > 0)
					{
						$data['PRICE_DELIVERY'] = roundEx(floatval($data['PRICE_DELIVERY']), SALE_VALUE_PRECISION);
						$shipment->setField('PRICE_DELIVERY', $data['PRICE_DELIVERY']);
					}


				}
			}
		}


		if (isset($data['DISCOUNT_PRICE']) && floatval($data['DISCOUNT_PRICE']) >= 0)
		{
			$data['DISCOUNT_PRICE'] = roundEx(floatval($data['DISCOUNT_PRICE']), SALE_VALUE_PRECISION);
			$this->setField('DISCOUNT_PRICE', $data['DISCOUNT_PRICE']);
		}

		return new Result();
	}

	/**
	 * Save field modify to history.
	 *
	 * @param string $name				Field name.
	 * @param null|string $oldValue		Old value.
	 * @param null|string $value		New value.
	 */
	protected function addChangesToHistory($name, $oldValue = null, $value = null)
	{
		if ($this->getId() > 0)
		{
			$historyFields = array();
			if ($name == "PRICE")
			{
				$historyFields = array(
					'OLD_PRICE' => $oldValue,
					'CURRENCY' => $this->getCurrency()
				);
			}

			OrderHistory::addField(
				'ORDER',
				$this->getId(),
				$name,
				$oldValue,
				$value,
				$this->getId(),
				$this,
				$historyFields
			);
		}
	}

	/**
	 * Lock order.
	 *
	 * @param int $id			Order id.
	 * @return Entity\UpdateResult|Result
	 * @throws \Exception
	 */
	public static function lock($id)
	{
		global $USER;

		$result = new Result();
		$id = (int)$id;
		if ($id <= 0)
		{
			$result->addError( new ResultError(Loc::getMessage('SALE_ORDER_WRONG_ID'), 'SALE_ORDER_WRONG_ID') );
			return $result;
		}

		return Internals\OrderTable::update($id, array(
			'DATE_LOCK' => new Main\Type\DateTime(),
			'LOCKED_BY' => $USER->GetID()
		));
	}

	/**
	 * Unlock order.
	 *
	 * @param int $id			Order id.
	 * @return Entity\UpdateResult|Result
	 * @throws Main\ArgumentNullException
	 * @throws \Exception
	 */
	public static function unlock($id)
	{
		global $USER;

		$result = new Result();
		$id = (int)$id;
		if ($id <= 0)
		{
			$result->addError( new ResultError(Loc::getMessage('SALE_ORDER_WRONG_ID'), 'SALE_ORDER_WRONG_ID') );
			return $result;
		}

		if(!$order = Order::load($id))
		{
			$result->addError( new ResultError(Loc::getMessage('SALE_ORDER_ENTITY_NOT_FOUND'), 'SALE_ORDER_ENTITY_NOT_FOUND') );
			return $result;
		}

		$userRights = \CMain::getUserRight("sale", $USER->getUserGroupArray(), "Y", "Y");

		if (($userRights >= "W") || ($order->getField("LOCKED_BY") == $USER->getID()))
		{
			return Internals\OrderTable::update($id, array(
				'DATE_LOCK' => null,
				'LOCKED_BY' => null
			));
		}

		return $result;
	}

	/**
	 * Return is order locked.
	 *
	 * @param int $id			Order id.
	 * @return bool
	 */
	public static function isLocked($id)
	{
		/** @var Result $r */
		$r = static::getLockedStatus($id);
		if ($r->isSuccess())
		{
			$lockResultData = $r->getData();

			if (array_key_exists('LOCK_STATUS', $lockResultData)
				&& $lockResultData['LOCK_STATUS'] == static::SALE_ORDER_LOCK_STATUS_RED)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Return order locked status.
	 *
	 * @param int $id		Order id.
	 * @return Result
	 * @throws Main\ArgumentException
	 */
	public static function getLockedStatus($id)
	{
		$result = new Result();

		$res = Internals\OrderTable::getList(array(
				'filter' => array('=ID' => $id),
				'select' => array(
					'LOCKED_BY',
					'LOCK_STATUS',
					'DATE_LOCK'
				)
		));

		if ($data = $res->fetch())
		{
			$result->addData(array(
				'LOCKED_BY' => $data['LOCKED_BY'],
				'LOCK_STATUS' => $data['LOCK_STATUS'],
				'DATE_LOCK' => $data['DATE_LOCK'],
			));
		}

		return $result;
	}

	/**
	 * @return null|string
	 */
	public function getTaxLocation()
	{
		if (strval(($this->getField('TAX_LOCATION')) == ""))
		{
			/** @var PropertyValueCollection $propertyCollection */
			$propertyCollection = $this->getPropertyCollection();

			if ($property = $propertyCollection->getTaxLocation())
			{
				$this->setField('TAX_LOCATION', $property->getValue());
			}

		}

		return $this->getField('TAX_LOCATION');
	}

	/**
	 * @param bool $isMeaningfulField
	 * @return bool
	 */
	public function isStartField($isMeaningfulField = false)
	{
		if ($this->isStartField === null)
		{
			$this->isStartField = true;
		}
		else
			$this->isStartField = false;

		if ($isMeaningfulField === true)
		{
			$this->isMeaningfulField = true;
		}

		return $this->isStartField;
	}

	/**
	 *
	 */
	public function clearStartField()
	{
		$this->isStartField = null;
		$this->isMeaningfulField = false;
	}

	/**
	 * @return bool
	 */
	public function hasMeaningfulField()
	{
		return $this->isMeaningfulField;
	}

	/**
	 * @param bool $hasMeaningfulField
	 * @return Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ObjectNotFoundException
	 */
	public function doFinalAction($hasMeaningfulField = false)
	{
		$result = new Result();

		if (!$hasMeaningfulField)
		{
			$this->clearStartField();
			return $result;
		}
		
		if ($basket = $this->getBasket())
		{
			$this->setMathActionOnly(true);

			if ($eventName = static::getEntityEventName())
			{
				$event = new Main\Event('sale', 'OnBefore'.$eventName.'FinalAction', array(
					'ENTITY' => $this,
					'HAS_MEANINGFUL_FIELD' => $hasMeaningfulField,
					'BASKET' => $basket,
				));
				$event->send();

				if ($event->getResults())
				{
					/** @var Main\EventResult $eventResult */
					foreach($event->getResults() as $eventResult)
					{
						if($eventResult->getType() == Main\EventResult::ERROR)
						{
							$errorMsg = new ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_ON_BEFORE_'.strtoupper($eventName).'_FINAL_ACTION_ERROR'), 'SALE_EVENT_ON_BEFORE_'.strtoupper($eventName).'_FINAL_ACTION_ERROR');
							if ($eventResultData = $eventResult->getParameters())
							{
								if (isset($eventResultData['ERROR']) && $eventResultData['ERROR'] instanceof ResultError)
								{
									$errorMsg = $eventResultData['ERROR'];
								}
							}

							$result->addError($errorMsg);
						}
					}
				}

				if (!$result->isSuccess())
				{
					return $result;
				}
			}


			
			// discount
			$discount = $this->getDiscount();
			$r = $discount->calculate();
			if (!$r->isSuccess())
			{
//				$this->clearStartField();
//				$result->addErrors($r->getErrors());
//				return $result;
			}

			if ($r->isSuccess() && ($discountData = $r->getData()) && !empty($discountData) && is_array($discountData))
			{
				/** @var Result $r */
				$r = $this->applyDiscount($discountData);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
					return $result;
				}
			}


			if (!$this->isExternal())
			{
				/** @var Tax $tax */
				$tax = $this->getTax();
				/** @var Result $r */
				$r = $tax->calculate();
				if (!$result->isSuccess())
					return $r;

				$taxChanged = false;
				$taxResult = $r->getData();
				if (isset($taxResult['TAX_PRICE']) && floatval($taxResult['TAX_PRICE']) >= 0)
				{
					if (!$this->isUsedVat())
					{
						$taxChanged = true;
						$this->setField('TAX_PRICE', $taxResult['TAX_PRICE']);

						$this->setFieldNoDemand(
							"PRICE",
							$this->getBasket()->getPrice() + $this->getShipmentCollection()->getPriceDelivery() + $taxResult['TAX_PRICE']
						);
					}

				}

				if ($taxChanged || $this->isUsedVat())
				{
					$taxValue = $this->isUsedVat()? $this->getVatSum() : $this->getField('TAX_PRICE');
					if (floatval($taxValue) != floatval($this->getField('TAX_VALUE')))
						$this->setField('TAX_VALUE', floatval($taxValue));
				}
			}




		}

		//

		$this->setMathActionOnly(false);

		//
		/** @var Result $r */
		$r = $this->syncOrderAndPayments();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		$this->clearStartField();

		if ($eventName = static::getEntityEventName())
		{
			$event = new Main\Event('sale', 'OnAfter'.$eventName.'FinalAction', array(
				'ENTITY' => $this,
			));
			$event->send();
		}

		return $result;
	}

	/**
	 * @internal
	 * @param bool $value
	 * @return bool
	 */
	public function setMathActionOnly($value = false)
	{
		$this->isOnlyMathAction = $value;
	}

	/**
	 * @return bool
	 */
	public function isMathActionOnly()
	{
		return $this->isOnlyMathAction;
	}

	/**
	 * @param string $event
	 * @return array
	 */
	public static function getEventListUsed($event)
	{
		return GetModuleEvents("sale", $event, true);
	}

	/**
	 * @internal
	 * @return null|bool
	 */
	public function isNew()
	{
		return $this->isNew;
	}

	/**
	 * @return bool
	 */
	public function isChanged()
	{
		if (parent::isChanged())
			return true;

		/** @var PropertyValueCollection $propertyCollection */
		if ($propertyCollection = $this->getPropertyCollection())
		{
			if ($propertyCollection->isChanged())
			{
				return true;
			}
		}

		/** @var Basket $basket */
		if ($basket = $this->getBasket())
		{
			if ($basket->isChanged())
			{
				return true;
			}

			/** @var PaymentCollection $paymentCollection */
			if ($paymentCollection = $this->getPaymentCollection())
			{
				if ($paymentCollection->isChanged())
				{
					return true;
				}
			}

			/** @var ShipmentCollection $shipmentCollection */
			if ($shipmentCollection = $this->getShipmentCollection())
			{
				if ($shipmentCollection->isChanged())
				{
					return true;
				}
			}

		}

		return false;
	}


}