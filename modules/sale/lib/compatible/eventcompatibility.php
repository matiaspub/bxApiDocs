<?php

namespace Bitrix\Sale\Compatible;

use Bitrix\Main;
use Bitrix\Sale;

Main\Localization\Loc::loadMessages(__FILE__);

class EventCompatibility extends Sale\Compatible\Internals\EntityCompatibility
{
	// Events old kernel, which will be called in a new kernel
	const EVENT_COMPATIBILITY_ON_ORDER_PAID = "OnSalePayOrder";
	const EVENT_COMPATIBILITY_ON_BEFORE_ORDER_DELETE = "OnBeforeOrderDelete";
	const EVENT_COMPATIBILITY_ON_ORDER_DELETED = "OnOrderDelete";
	const EVENT_COMPATIBILITY_ON_SHIPMENT_DELIVER = "OnSaleDeliveryOrder";

	const EVENT_COMPATIBILITY_ON_ORDER_UPDATE = "OnOrderUpdate";

	const EVENT_COMPATIBILITY_ON_BEFORE_ORDER_ADD = "OnBeforeOrderAdd";
	const EVENT_COMPATIBILITY_ON_BEFORE_ORDER_UPDATE = "OnBeforeOrderUpdate";

	const EVENT_COMPATIBILITY_ON_ORDER_SAVE = "OnOrderSave";
	const EVENT_COMPATIBILITY_ON_ORDER_ADD = "OnOrderAdd";

	const EVENT_COMPATIBILITY_ON_BEFORE_BASKET_ITEM_ADD = "OnBeforeBasketAdd";
	const EVENT_COMPATIBILITY_ON_BEFORE_BASKET_ITEM_UPDATE = "OnBeforeBasketUpdate";

	const EVENT_COMPATIBILITY_ON_BASKET_ITEM_ADD = "OnBasketAdd";
	const EVENT_COMPATIBILITY_ON_BASKET_ITEM_UPDATE = "OnBasketUpdate";

	const EVENT_COMPATIBILITY_ON_BEFORE_ORDER_CANCELED = "OnSaleBeforeCancelOrder";
	const EVENT_COMPATIBILITY_ON_ORDER_CANCELED = "OnSaleCancelOrder";

	const EVENT_COMPATIBILITY_ON_TRACKING_NUMBER_CHANGE = "OnTrackingNumberChange";

	const EVENT_COMPATIBILITY_ON_BEFORE_ORDER_STATUS_CHANGE = "OnSaleBeforeStatusOrder";
	const EVENT_COMPATIBILITY_ON_ORDER_STATUS_CHANGE = "OnSaleStatusOrder";

	const EVENT_COMPATIBILITY_ORDER_STATUS_SEND_EMAIL = "OnOrderStatusSendEmail";
	const EVENT_COMPATIBILITY_ORDER_STATUS_EMAIL = "OnSaleStatusEMail";
	const EVENT_COMPATIBILITY_MOBILE_PUSH_ORDER_STATUS_CHANGE = "ORDER_STATUS_CHANGED";

	const EVENT_COMPATIBILITY_ON_ORDER_NEW_SEND_EMAIL = "OnOrderNewSendEmail";
	const EVENT_COMPATIBILITY_ORDER_NEW_SEND_EMAIL_EVENT_NAME = "SALE_NEW_ORDER";

	const EVENT_COMPATIBILITY_ON_ORDER_DELIVER_SEND_EMAIL = "OnOrderDeliverSendEmail";
	const EVENT_COMPATIBILITY_ORDER_DELIVER_SEND_EMAIL_EVENT_NAME = "SALE_ORDER_DELIVERY";

	const EVENT_COMPATIBILITY_ON_ORDER_PAID_SEND_EMAIL = "OnOrderPaySendEmail";

	const EVENT_COMPATIBILITY_ON_ORDER_CANCEL_SEND_EMAIL = "OnOrderCancelSendEmail";
	const EVENT_COMPATIBILITY_ORDER_CANCEL_SEND_EMAIL_EVENT_NAME = "SALE_ORDER_CANCEL";

	const EVENT_COMPATIBILITY_ON_BEFORE_BASKET_DELETE = "OnBeforeBasketDelete";
	const EVENT_COMPATIBILITY_ON_BASKET_DELETED = "OnBasketDelete";

//	const EVENT_COMPATIBILITY_ON_BASKET_ = "OnSaleCancelOrder";


	protected static $disableMailSend = false;

	protected static $disableEvent = false;


	/**
	 * @param string $event
	 * @return bool
	 */
	public static function getEventListUsed($event)
	{
		return GetModuleEvents("sale", $event, true);
	}

	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 */
	public static function onSalePayOrder(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\Order $order */
		$order = $parameters['ENTITY'];
		if (!$order instanceof Sale\Order)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_ORDER'), 'SALE_EVENT_COMPATIBILITY_ORDER_PAY_WRONG_ORDER'),
				'sale'
			);
		}

		$id = $order->getId();
		$value = $order->getField('PAYED');

		static::setDisableEvent(true);
		foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_ORDER_PAID, true) as $oldEvent)
		{
			ExecuteModuleEventEx($oldEvent, array($id, $value));
		}
		static::setDisableEvent(false);

		if ($value == "Y")
		{
			if (Main\Loader::includeModule("statistic"))
			{
				\CStatEvent::AddByEvents("eStore", "order_paid", $id, "", $order->getField("STAT_GID"), $order->getPrice(), $order->getCurrency());
			}
		}
		else
		{
			if (Main\Loader::includeModule("statistic"))
			{
				\CStatEvent::AddByEvents("eStore", "order_chargeback", $id, "", $order->getField("STAT_GID"), $order->getPrice(), $order->getCurrency(), "Y");
			}
		}

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 */
	public static function onSaleOrderPaidSendMail(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\Order $order */
		$order = $parameters['ENTITY'];
		if (!$order instanceof Sale\Order)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_ORDER'), 'SALE_EVENT_COMPATIBILITY_ORDER_PAY_SEND_EMAIL_WRONG_ORDER'),
				'sale'
			);
		}

		$value = $order->getField('PAYED');

		if ($value == "Y")
		{
			Sale\Notify::sendOrderPaid($order);
		}
		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 */
	public static function onSaleOrderCancelSendEmail(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		if (static::$disableMailSend === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\Order $order */
		$order = $parameters['ENTITY'];
		if (!$order instanceof Sale\Order)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_ORDER'), 'SALE_EVENT_COMPATIBILITY_ORDER_CANCEL_SEND_EMAIL_WRONG_ORDER'),
				'sale'
			);
		}

		$id = $order->getId();
		$value = $order->getField('CANCELED');

		if ($value == "Y")
		{
			Sale\Notify::sendOrderCancel($order);

			if (Main\Loader::includeModule("statistic"))
			{
				\CStatEvent::AddByEvents("eStore", "order_cancel", $id, "", $order->getField("STAT_GID"));
			}

			$GLOBALS['SALE_ORDER_CANCEL_SEND'][$id] = true;

		}
		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}


	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 */
	public static function onOrderNewSendEmail(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		if (static::$disableMailSend === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\Order $order */
		$order = $parameters['ENTITY'];
		$isNew = $parameters['IS_NEW'];

		if (!$order instanceof Sale\Order)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_ORDER'), 'SALE_EVENT_COMPATIBILITY_ORDER_NEW_SEND_EMAIL_WRONG_ORDER'),
				'sale'
			);
		}

		$id = $order->getId();

		if (!$isNew)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		Sale\Notify::sendOrderNew($order);

		$GLOBALS['SALE_NEW_ORDER_SEND'][$id] = true;

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}



	/**
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function onBeforeOrderDelete(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\Order $order */
		$order = $parameters['ENTITY'];
		if (!$order instanceof Sale\Order)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_ORDER'), 'SALE_EVENT_COMPATIBILITY_BEFORE_ORDER_DELETE_WRONG_ORDER'),
				'sale'
			);
		}

		$id = $order->getId();

		static::setDisableEvent(true);
		foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_BEFORE_ORDER_DELETE, true) as $oldEvent)
		{
			if (ExecuteModuleEventEx($oldEvent, array($id)) === false)
			{
				return new Main\EventResult(
					Main\EventResult::SUCCESS,
					false,
					'sale');
			}
		}
		static::setDisableEvent(false);

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}


	/**
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function onOrderDelete(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\Order $order */
		$order = $parameters['ENTITY'];
		if (!$order instanceof Sale\Order)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_ORDER'), 'SALE_EVENT_COMPATIBILITY_ORDER_DELETE_WRONG_ORDER'),
				'sale'
			);
		}

		$deleted = $parameters['VALUE'];
		$id = $order->getId();

		static::setDisableEvent(true);
		foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_ORDER_DELETED, true) as $oldEvent)
		{
			ExecuteModuleEventEx($oldEvent, array($id, $deleted));
		}
		static::setDisableEvent(false);

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 * @throws Main\ObjectNotFoundException
	 */
	public static function onSaleDeliveryOrder(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\Shipment $shipment */
		$shipment = $parameters['ENTITY'];
		if (!$shipment instanceof Sale\Shipment)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_SHIPMENT'), 'SALE_EVENT_COMPATIBILITY_DELIVER_ORDER_WRONG_SHIPMENT'),
				'sale'
			);
		}

		/** @var Sale\ShipmentCollection $shipmentCollection */
		if (!$shipmentCollection = $shipment->getCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
		}

		/** @var Sale\Order $order */
		if (!$order = $shipmentCollection->getOrder())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}

		$id = $order->getId();

		static::setDisableEvent(true);
		foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_SHIPMENT_DELIVER, true) as $oldEvent)
		{
			ExecuteModuleEventEx($oldEvent, array($id, $shipment->getField('ALLOW_DELIVERY')));
		}
		static::setDisableEvent(false);

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}


	/**
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function onOrderSave(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\Order $order */
		$order = $parameters['ENTITY'];
		$oldValues = $parameters['VALUES'];
		if (!$order instanceof Sale\Order)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_ORDER'), 'SALE_EVENT_COMPATIBILITY_ORDER_SAVE_WRONG_ORDER'),
				'sale'
			);
		}

		$isNew = $order->isNew();
		$id = $order->getId();

		$fields = null;
		$orderFields = array();

		/** @var Sale\Result $resultOrderFields */
		$resultOrderFields = OrderCompatibility::getOrderFields($order);
		if ($resultOrderFields->isSuccess())
		{
			if ($orderFieldsResultData = $resultOrderFields->getData())
			{
				if (!empty($orderFieldsResultData['FIELDS']) && is_array($orderFieldsResultData['FIELDS']))
				{
					$fields = $orderFieldsResultData['FIELDS'];
				}
				if (!empty($orderFieldsResultData['ORDER_FIELDS']) && is_array($orderFieldsResultData['ORDER_FIELDS']))
				{
					$orderFields = $orderFieldsResultData['ORDER_FIELDS'];
				}
			}
		}

		static::setDisableEvent(true);
		if ($isNew)
		{
			foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_ORDER_ADD, true) as $oldEvent)
			{
				ExecuteModuleEventEx($oldEvent, array($id, $orderFields));
			}
		}
		else
		{
			foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_ORDER_UPDATE, true) as $oldEvent)
			{
				ExecuteModuleEventEx($oldEvent, array($id, $orderFields));
			}
		}
		static::setDisableEvent(false);

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function onOrderSaved(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\Order $order */
		$order = $parameters['ENTITY'];
		$oldValues = $parameters['VALUES'];
		if (!$order instanceof Sale\Order)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_ORDER'), 'SALE_EVENT_COMPATIBILITY_ORDER_SAVED_WRONG_ORDER'),
				'sale'
			);
		}

		$isNew = $parameters['IS_NEW'];
		$id = $order->getId();

		$fields = null;
		$orderFields = null;

		/** @var Sale\Result $resultOrderFields */
		$resultOrderFields = OrderCompatibility::getOrderFields($order);
		if ($resultOrderFields->isSuccess())
		{
			if ($orderFieldsResultData = $resultOrderFields->getData())
			{
				if (!empty($orderFieldsResultData['FIELDS']) && is_array($orderFieldsResultData['FIELDS']))
				{
					$fields = $orderFieldsResultData['FIELDS'];
				}
				if (!empty($orderFieldsResultData['ORDER_FIELDS']) && is_array($orderFieldsResultData['ORDER_FIELDS']))
				{
					$orderFields = $orderFieldsResultData['ORDER_FIELDS'];
				}
			}
		}

		static::setDisableEvent(true);
		foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_ORDER_SAVE, true) as $oldEvent)
		{
			ExecuteModuleEventEx($oldEvent, array($id, $fields, $orderFields, $isNew));
		}
		static::setDisableEvent(false);

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function onOrderAdd(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\Order $order */
		$order = $parameters['ENTITY'];
		$isNew = $parameters['IS_NEW'];
		if (!$isNew)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		if (!$order instanceof Sale\Order)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_ORDER'), 'SALE_EVENT_COMPATIBILITY_ORDER_ADD_WRONG_ORDER'),
				'sale'
			);
		}

		$id = $order->getId();

		$fields = null;
		$orderFields = null;

		/** @var Sale\Result $resultOrderFields */
		$resultOrderFields = OrderCompatibility::getOrderFields($order);
		if ($resultOrderFields->isSuccess())
		{
			if ($orderFieldsResultData = $resultOrderFields->getData())
			{
				if (!empty($orderFieldsResultData['ORDER_FIELDS']) && is_array($orderFieldsResultData['ORDER_FIELDS']))
				{
					$orderFields = $orderFieldsResultData['ORDER_FIELDS'];
				}
			}
		}

		static::setDisableEvent(true);
		foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_ORDER_ADD, true) as $oldEvent)
		{
			ExecuteModuleEventEx($oldEvent, array($id, $orderFields));
		}
		static::setDisableEvent(false);

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}
	/**
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function onOrderBeforeSaved(Main\Event $event)
	{
		global $APPLICATION;

		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}


		$parameters = $event->getParameters();

		/** @var Sale\Order $order */
		$order = $parameters['ENTITY'];

		if (!$order instanceof Sale\Order)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_ORDER'), 'SALE_EVENT_COMPATIBILITY_ORDER_BEFORE_SAVED_WRONG_ORDER'),
				'sale'
			);
		}

		$id = $order->getId();

		$fields = null;
		$orderFields = null;

		/** @var Sale\Result $resultOrderFields */
		$resultOrderFields = OrderCompatibility::getOrderFields($order);
		if ($resultOrderFields->isSuccess())
		{
			if ($orderFieldsResultData = $resultOrderFields->getData())
			{
				if (!empty($orderFieldsResultData['ORDER_FIELDS']) && is_array($orderFieldsResultData['ORDER_FIELDS']))
				{
					$orderFields = $orderFieldsResultData['ORDER_FIELDS'];
				}
			}
		}

		$currentOrderFields = $orderFields;

		if ($order->isNew())
		{
			static::setDisableEvent(true);
			foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_BEFORE_ORDER_ADD, true) as $oldEvent)
			{
				if (ExecuteModuleEventEx($oldEvent, array(&$orderFields)) === false)
				{
					if ($ex = $APPLICATION->GetException())
					{
						return new Main\EventResult(
							Main\EventResult::ERROR,
							new Sale\ResultError($ex->GetString(), $ex->GetID()),
							'sale'
						);
					}

				}
			}

			static::setDisableEvent(false);

			$allowFields = OrderCompatibility::getAvailableFields();

			foreach ($orderFields as $orderFieldName => $orderFieldValue)
			{
				if (in_array($orderFieldName, $allowFields)
					&& (array_key_exists($orderFieldName, $currentOrderFields) && $orderFieldValue != $currentOrderFields[$orderFieldName]))
				{
					/** @var Sale\Result $r */
					$order->setFieldNoDemand($orderFieldName, $orderFieldValue);
				}
			}
		}
		else
		{
			static::setDisableEvent(true);
			foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_BEFORE_ORDER_UPDATE, true) as $oldEvent)
			{
				if (ExecuteModuleEventEx($oldEvent, array($id, $orderFields)) === false)
				{
					if ($ex = $APPLICATION->GetException())
					{

						return new Main\EventResult(
							Main\EventResult::ERROR,
							new Sale\ResultError($ex->GetString(), $ex->GetID()),
							'sale'
						);
					}
				}
			}
			static::setDisableEvent(false);
		}

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}


	/**
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function onSaleBeforeCancelOrder(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\Order $order */
		$order = $parameters['ENTITY'];
		if (!$order instanceof Sale\Order)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_ORDER'), 'SALE_EVENT_COMPATIBILITY_BEFORE_ORDER_DELETE_WRONG_ORDER'),
				'sale'
			);
		}

		$id = $order->getId();
		$value = $order->getField('CANCELED');;

		static::setDisableEvent(true);
		foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_BEFORE_ORDER_CANCELED, true) as $oldEvent)
		{
			if (ExecuteModuleEventEx($oldEvent, array($id, $value)) === false)
			{
				return new Main\EventResult(
					Main\EventResult::SUCCESS,
					false,
					'sale');
			}
		}
		static::setDisableEvent(false);

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}


	/**
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function onSaleCancelOrder(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\Order $order */
		$order = $parameters['ENTITY'];
		if (!$order instanceof Sale\Order)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_ORDER'), 'SALE_EVENT_COMPATIBILITY_ORDER_DELETE_WRONG_ORDER'),
				'sale'
			);
		}

		$canceled = $order->getField('CANCELED');
		$id = $order->getId();
		$description = $order->getField('REASON_CANCELED');

		static::setDisableEvent(true);
		foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_ORDER_CANCELED, true) as $oldEvent)
		{
			ExecuteModuleEventEx($oldEvent, array($id, $canceled, $description));
			$order->setField('REASON_CANCELED', $description);
		}
		static::setDisableEvent(false);

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 * @throws Main\ObjectNotFoundException
	 */
	public static function onBasketItemBeforeChange(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\BasketItem $basketItem */
		$basketItem = $parameters['ENTITY'];
		$isNew = $parameters['IS_NEW'];
		$oldValues = $parameters['VALUES'];

		if (!$basketItem instanceof Sale\BasketItem)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_BASKET'), 'SALE_EVENT_COMPATIBILITY_BASKET_ITEM_BEFORE_CHANGE_WRONG_BASKET'),
				'sale'
			);
		}

		$currentBasketFields = BasketCompatibility::convertBasketItemToArray($basketItem);

		$basketFields = array();

		if ($isNew)
		{
			$basketFields = $currentBasketFields;
		}
		else
		{
			if (!empty($oldValues) && is_array($oldValues))
			{
				foreach ($oldValues as $oldValueKey => $oldValueData)
				{
					if (array_key_exists($oldValueKey, $currentBasketFields))
					{
						$basketFields[$oldValueKey] = $currentBasketFields[$oldValueKey];
					}
				}
			}
		}

		if (array_key_exists('QUANTITY', $oldValues) && ($currentBasketFields['QUANTITY'] - $oldValues['QUANTITY']) > 0)
		{
			if (empty($basketFields['ID']) && !empty($currentBasketFields['ID']))
			{
				$basketFields['ID'] = $currentBasketFields['ID'];
			}

			$basketFields['QUANTITY'] = $currentBasketFields['QUANTITY'] - $oldValues['QUANTITY'];

			static::setDisableEvent(true);
			foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_BEFORE_BASKET_ITEM_ADD, true) as $oldEvent)
			{
				ExecuteModuleEventEx($oldEvent, array(&$basketFields));
			}
			static::setDisableEvent(false);

			$basketFields['QUANTITY'] = $oldValues['QUANTITY'] + $basketFields['QUANTITY'];
		}

		if (empty($basketFields) && !empty($oldValues) && is_array($oldValues))
		{
			foreach ($oldValues as $oldValueKey => $oldValueData)
			{
				if (array_key_exists($oldValueKey, $currentBasketFields))
				{
					$basketFields[$oldValueKey] = $currentBasketFields[$oldValueKey];
				}
			}
		}

		if (!$isNew)
		{
			static::setDisableEvent(true);
			foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_BEFORE_BASKET_ITEM_UPDATE, true) as $oldEvent)
			{
				ExecuteModuleEventEx($oldEvent, array($basketItem->getId(), &$basketFields));
			}
			static::setDisableEvent(false);
		}


		foreach ($currentBasketFields as $key => $value)
		{
			if (isset($basketFields[$key]) && !is_array($value) && $basketFields[$key] != $value)
			{
				$basketItem->setFieldNoDemand($key, $basketFields[$key]);
			}
		}

		if (!empty($basketFields['PROPS']) && is_array($basketFields['PROPS']))
		{
			$propIndexList = array();
			/** @var Sale\BasketPropertiesCollection $basketPropertyCollection */
			if (!$basketPropertyCollection = $basketItem->getPropertyCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "BasketPropertiesCollection" not found');
			}

			$propDiffByName = false;

			if (!empty($currentBasketFields['PROPS']) && is_array($currentBasketFields['PROPS']))
			{
				foreach ($currentBasketFields['PROPS'] as $propData)
				{
					$propCode = $propData["CODE"];
					if (empty($propData["CODE"]))
					{
						$propCode = $propData["NAME"];
						$propDiffByName = true;
					}

					$propIndexList[$propCode] = $propData;
				}
			}

			foreach ($basketFields['PROPS'] as $propData)
			{
				$propCode = $propData["CODE"];
				if (empty($propData["CODE"]) || $propDiffByName)
					$propCode = $propData["NAME"];

				if (isset($propIndexList[$propCode]))
				{
					$propOldData = $propIndexList[$propCode];
					if ($propData['SORT'] != $propOldData['SORT'] || $propData['VALUE'] != $propOldData['VALUE'])
					{
						/** @var Sale\BasketPropertyItem $basketPropertyItem */
						if ($basketPropertyItem = $basketPropertyCollection->getPropertyItemByValue($propIndexList[$propCode]))
						{
							$basketPropertyItem->setFieldsNoDemand($propData);
						}
					}
				}
				else
				{
					if ($basketPropertyItem = $basketPropertyCollection->createItem())
					{
						$basketPropertyItem->setFieldsNoDemand($propData);
					}
				}

			}
		}



		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 */
	public static function onBasketItemChange(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\BasketItem $basketItem */
		$basketItem = $parameters['ENTITY'];
		$isNew = $parameters['IS_NEW'];
		$oldValues = $parameters['VALUES'];
		if (!$basketItem instanceof Sale\BasketItem)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_BASKET'), 'SALE_EVENT_COMPATIBILITY_BASKET_ITEM_CHANGE_WRONG_BASKET'),
				'sale'
			);
		}

		$basketFields = BasketCompatibility::convertBasketItemToArray($basketItem);

		static::setDisableEvent(true);
		if (!$isNew)
		{
			foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_BASKET_ITEM_UPDATE, true) as $oldEvent)
			{
				ExecuteModuleEventEx($oldEvent, array($basketItem->getId(), $basketFields));
			}
		}

		if (array_key_exists('QUANTITY', $oldValues) && ($basketFields['QUANTITY'] - $oldValues['QUANTITY']) > 0)
		{
			foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_BASKET_ITEM_ADD, true) as $oldEvent)
			{
				ExecuteModuleEventEx($oldEvent, Array($basketItem->getId(), $basketFields));
			}
		}

		static::setDisableEvent(false);

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 * @throws Main\ObjectNotFoundException
	 */
	public static function onShipmentTrackingNumberChange(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\Shipment $basketItem */
		$shipment = $parameters['ENTITY'];
		$oldValues = $parameters['VALUES'];
		if (!$shipment instanceof Sale\Shipment)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_SHIPMENT'), 'SALE_EVENT_COMPATIBILITY_SHIPMENT_TRACKING_NUMBER_CHANGE_WRONG_SHIPMENT'),
				'sale'
			);
		}

		/** @var Sale\ShipmentCollection $shipmentCollection */
		if (!$shipmentCollection = $shipment->getCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
		}

		/** @var Sale\Order $order */
		if (!$order = $shipmentCollection->getOrder())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}

		static::setDisableEvent(true);
		foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_TRACKING_NUMBER_CHANGE, true) as $oldEvent)
		{
			ExecuteModuleEventEx($oldEvent, Array($order->getId(), $shipment->getField('TRACKING_NUMBER')));
		}
		static::setDisableEvent(false);

		if (array_key_exists('TRACKING_NUMBER', $oldValues) && strval($shipment->getField('TRACKING_NUMBER')) != ''
			&& $oldValues["TRACKING_NUMBER"] != $shipment->getField('TRACKING_NUMBER'))
		{
			Sale\Notify::sendShipmentTrackingNumberChange($shipment);
		}

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 * @throws Main\ObjectNotFoundException
	 */
	public static function onShipmentAllowDelivery(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\Shipment $shipment */
		$shipment = $parameters['ENTITY'];
		$oldValues = $parameters['VALUES'];
		if (!$shipment instanceof Sale\Shipment)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_SHIPMENT'), 'SALE_EVENT_COMPATIBILITY_SHIPMENT_ALLOW_DELIVERY_WRONG_SHIPMENT'),
				'sale'
			);
		}

		/** @var Sale\ShipmentCollection $shipmentCollection */
		if (!$shipmentCollection = $shipment->getCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
		}

		/** @var Sale\Order $order */
		if (!$order = $shipmentCollection->getOrder())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}

		if ($shipmentCollection->isAllowDelivery() && array_key_exists('ALLOW_DELIVERY', $oldValues) && strval($shipment->getField('ALLOW_DELIVERY')) != ''
			&& $oldValues["ALLOW_DELIVERY"] != $shipment->getField('ALLOW_DELIVERY'))
		{
			Sale\Notify::sendShipmentAllowDelivery($shipment);
		}

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 * @throws Main\ObjectNotFoundException
	 */
	public static function onSaleStatusOrderChange(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\Order $basketItem */
		$order = $parameters['ENTITY'];
		$value = $parameters['VALUE'];
		$oldValue = $parameters['OLD_VALUE'];
		if (!$order instanceof Sale\Order)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_ORDER'), 'SALE_EVENT_COMPATIBILITY_ORDER_STATUS_CHANGE_WRONG_ORDER'),
				'sale'
			);
		}

		static::setDisableEvent(true);
		foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_ORDER_STATUS_CHANGE, true) as $oldEvent)
		{
			ExecuteModuleEventEx($oldEvent, array($order->getId(), $value));
		}
		static::setDisableEvent(false);

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 * @throws Main\ObjectNotFoundException
	 */
	public static function onSaleOrderStatusChangeSendEmail(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\Order $basketItem */
		$order = $parameters['ENTITY'];
		$value = $parameters['VALUE'];
		$oldValue = $parameters['OLD_VALUE'];
		if (!$order instanceof Sale\Order)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_ORDER'), 'SALE_EVENT_COMPATIBILITY_ORDER_STATUS_CHANGE_SEND_EMAIL_WRONG_ORDER'),
				'sale'
			);
		}

		Sale\Notify::sendOrderStatusChange($order);

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param $id
	 * @param $eventName
	 * @param $fields
	 *
	 * @return bool
	 */
	public static function onCallOrderNewSendEmail($id, $eventName, $fields)
	{
		if (static::$disableMailSend === true)
		{
			return true;
		}

		if (!empty($GLOBALS['SALE_NEW_ORDER_SEND'][$id]))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param $id
	 * @param $eventName
	 * @param $fields
	 *
	 * @return bool
	 */
	public static function onCallOrderCancelSendEmail($id, $eventName, $fields)
	{
		if (static::$disableMailSend === true)
		{
			return true;
		}

		if (!empty($GLOBALS['SALE_ORDER_CANCEL_SEND'][$id]))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 * @throws Main\ObjectNotFoundException
	 */
	public static function onSaleBeforeStatusOrderChange(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\Order $basketItem */
		$order = $parameters['ENTITY'];
		$value = $parameters['VALUE'];
		$oldValue = $parameters['OLD_VALUE'];
		if (!$order instanceof Sale\Order)
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_ORDER'), 'SALE_EVENT_COMPATIBILITY_BEFORE_ORDER_STATUS_CHANGE_WRONG_ORDER'),
				'sale'
			);
		}

		static::setDisableEvent(true);
		foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_BEFORE_ORDER_STATUS_CHANGE, true) as $oldEvent)
		{
			ExecuteModuleEventEx($oldEvent, array($order->getId(), $value));
		}
		static::setDisableEvent(false);

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}


	/**
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function onBeforeBasketDelete(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\BasketItem $basketItem */
		$basketItem = $parameters['ENTITY'];
		if (!$basketItem instanceof Sale\BasketItem)
		{
			return new Main\EventResult(
					Main\EventResult::ERROR,
					new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_BASKET_ITEM'), 'SALE_EVENT_COMPATIBILITY_BEFORE_BASKET_ITEM_DELETE_WRONG_BASKET_ITEM'),
					'sale'
			);
		}

		$id = $basketItem->getId();

		static::setDisableEvent(true);
		foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_BEFORE_BASKET_DELETE, true) as $oldEvent)
		{
			if (ExecuteModuleEventEx($oldEvent, array($id)) === false)
			{
				return new Main\EventResult(
						Main\EventResult::SUCCESS,
						false,
						'sale');
			}
		}
		static::setDisableEvent(false);

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}


	/**
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function onBasketDelete(Main\Event $event)
	{
		if (static::$disableEvent === true)
		{
			return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
		}

		$parameters = $event->getParameters();

		/** @var Sale\BasketItem $basketItem */
		$values = $parameters['VALUES'];
		if (empty($values) || !is_array($values))
		{
			return new Main\EventResult(
					Main\EventResult::ERROR,
					new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_VALUES'), 'SALE_EVENT_COMPATIBILITY_BASKET_ITEM_DELETE_WRONG_VALUES'),
					'sale'
			);
		}

		if (empty($values['ID']))
		{
			return new Main\EventResult(
					Main\EventResult::ERROR,
					new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_COMPATIBILITY_WRONG_ID'), 'SALE_EVENT_COMPATIBILITY_BASKET_ITEM_DELETE_WRONG_ID'),
					'sale'
			);
		}

		$id = $values['ID'];

		static::setDisableEvent(true);
		foreach(GetModuleEvents("sale", static::EVENT_COMPATIBILITY_ON_BASKET_DELETED, true) as $oldEvent)
		{
			ExecuteModuleEventEx($oldEvent, array($id));
		}
		static::setDisableEvent(false);

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param $value
	 */
	public static function setDisableMailSend($value)
	{
		static::$disableMailSend = ($value === true || $value == "Y");
	}

	/**
	 * @param $value
	 */
	protected static function setDisableEvent($value)
	{
		static::$disableEvent = ($value === true);
	}

	/**
	 * @return bool
	 */
	protected static function isDisableEvent()
	{
		return static::$disableEvent;
	}

	/**
	 *
	 */
	public static function registerEvents()
	{
		$eventManager = Main\EventManager::getInstance();

		$eventManager->registerEventHandler('sale', 'OnSaleOrderPaid', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSalePayOrder');

		$eventManager->registerEventHandler('sale', 'OnSaleOrderBeforeSaved', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onOrderBeforeSaved');

		$eventManager->registerEventHandler('sale', 'OnSaleBeforeOrderDelete', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onBeforeOrderDelete');

		$eventManager->registerEventHandler('sale', 'OnSaleOrderDeleted', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onOrderDelete');

		$eventManager->registerEventHandler('sale', 'OnSaleShipmentDelivery', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSaleDeliveryOrder');

		$eventManager->registerEventHandler('sale', 'OnSaleBeforeOrderCanceled', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSaleBeforeCancelOrder');

		$eventManager->registerEventHandler('sale', 'OnSaleOrderCanceled', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSaleCancelOrder');

		$eventManager->registerEventHandler('sale', 'OnSaleOrderPaidSendMail', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSaleOrderPaidSendMail');

		$eventManager->registerEventHandler('sale', 'OnSaleOrderCancelSendEmail', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSaleOrderCancelSendEmail');

		$eventManager->registerEventHandler('sale', 'OnSaleOrderEntitySaved', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onOrderSave');

		$eventManager->registerEventHandler('sale', 'OnSaleOrderSaved', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onOrderSaved');

		$eventManager->registerEventHandler('sale', 'OnSaleBasketItemBeforeSaved', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onBasketItemBeforeChange');

		$eventManager->registerEventHandler('sale', 'OnSaleBasketItemEntitySaved', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onBasketItemChange');

		$eventManager->registerEventHandler('sale', 'OnShipmentTrackingNumberChange', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onShipmentTrackingNumberChange');

		$eventManager->registerEventHandler('sale', 'OnSaleBeforeStatusOrderChange', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSaleBeforeStatusOrderChange');

		$eventManager->registerEventHandler('sale', 'OnSaleStatusOrderChange', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSaleStatusOrderChange');

		$eventManager->registerEventHandler('sale', 'OnSaleOrderStatusChangeSendEmail', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSaleOrderStatusChangeSendEmail');

		$eventManager->registerEventHandler('sale', 'OnSaleOrderSaved', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onOrderNewSendEmail');

		RegisterModuleDependences("sale", "OnOrderNewSendEmail", "sale", "\\Bitrix\\Sale\\Compatible\\EventCompatibility", "onCallOrderNewSendEmail");

		$eventManager->registerEventHandler('sale', 'OnBeforeSaleBasketItemEntityDeleted', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'OnBeforeBasketDelete');

		$eventManager->registerEventHandler('sale', 'OnSaleBasketItemDeleted', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'OnBasketDelete');

		$eventManager->registerEventHandler('sale', 'OnShipmentAllowDelivery', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onShipmentAllowDelivery');

		RegisterModuleDependences("sale", "OnOrderCancelSendEmail", "sale", "\\Bitrix\\Sale\\Compatible\\EventCompatibility", "onCallOrderCancelSendEmail");

		$eventManager->registerEventHandler('sale', 'OnSaleOrderSaved', 'sale', '\Bitrix\Sale\Product2ProductTable', 'onSaleOrderAddEvent');
		
		$eventManager->registerEventHandler('sale', 'OnSaleStatusOrderChange', 'sale', '\Bitrix\Sale\Product2ProductTable', 'onSaleStatusOrderHandlerEvent');
		
		$eventManager->registerEventHandler('sale', 'OnShipmentAllowDelivery', 'sale', '\Bitrix\Sale\Product2ProductTable', 'onSaleDeliveryOrderHandlerEvent');
		
		$eventManager->registerEventHandler('sale', 'OnShipmentDeducted', 'sale', '\Bitrix\Sale\Product2ProductTable', 'onSaleDeductOrderHandlerEvent');
		
		$eventManager->registerEventHandler('sale', 'OnSaleOrderCanceled', 'sale', '\Bitrix\Sale\Product2ProductTable', 'onSaleCancelOrderHandlerEvent');
		
		$eventManager->registerEventHandler('sale', 'OnSaleOrderPaid', 'sale', '\Bitrix\Sale\Product2ProductTable', 'onSalePayOrderHandlerEvent');
		
		UnRegisterModuleDependences("sale", "OnBasketOrder", "sale", "\\Bitrix\\Sale\\Product2ProductTable", "onSaleOrderAdd", 100);
		UnRegisterModuleDependences("sale", "OnSaleStatusOrder", "sale", "\\Bitrix\\Sale\\Product2ProductTable", "onSaleStatusOrderHandler", 100);
		UnRegisterModuleDependences("sale", "OnSaleDeliveryOrder", "sale", "\\Bitrix\\Sale\\Product2ProductTable", "onSaleDeliveryOrderHandler", 100);
		UnRegisterModuleDependences("sale", "OnSaleDeductOrder", "sale", "\\Bitrix\\Sale\\Product2ProductTable", "onSaleDeductOrderHandler", 100);
		UnRegisterModuleDependences("sale", "OnSaleCancelOrder", "sale", "\\Bitrix\\Sale\\Product2ProductTable", "onSaleCancelOrderHandler", 100);
		UnRegisterModuleDependences("sale", "OnSalePayOrder", "sale", "\\Bitrix\\Sale\\Product2ProductTable", "onSalePayOrderHandler", 100);

	}

	/**
	 *
	 */
	public static function unRegisterEvents()
	{
		$eventManager = Main\EventManager::getInstance();

		$eventManager->unRegisterEventHandler('sale', 'OnSaleOrderPaid', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSalePayOrder');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleOrderBeforeSaved', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onOrderBeforeSaved');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleBeforeOrderDelete', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onBeforeOrderDelete');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleOrderDeleted', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onOrderDelete');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleShipmentDelivery', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSaleDeliveryOrder');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleBeforeOrderCanceled', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSaleBeforeCancelOrder');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleOrderCanceled', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSaleCancelOrder');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleOrderPaidSendMail', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSaleOrderPaidSendMail');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleOrderCancelSendEmail', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSaleOrderCancelSendEmail');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleOrderEntitySaved', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onOrderSave');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleOrderSaved', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onOrderSaved');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleBasketItemBeforeSaved', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onBasketItemBeforeChange');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleBasketItemEntitySaved', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onBasketItemChange');

		$eventManager->unRegisterEventHandler('sale', 'OnShipmentTrackingNumberChange', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onShipmentTrackingNumberChange');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleBeforeStatusOrderChange', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSaleBeforeStatusOrderChange');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleStatusOrderChange', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSaleStatusOrderChange');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleOrderStatusChangeSendEmail', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onSaleOrderStatusChangeSendEmail');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleOrderSaved', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onOrderNewSendEmail');

		UnRegisterModuleDependences("sale", "OnOrderNewSendEmail", "sale", "\\Bitrix\\Sale\\Compatible\\EventCompatibility", "onCallOrderNewSendEmail");

		$eventManager->unRegisterEventHandler('sale', 'OnBeforeSaleBasketItemEntityDeleted', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'OnBeforeBasketDelete');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleBasketItemDeleted', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'OnBasketDelete');

		$eventManager->unRegisterEventHandler('sale', 'OnShipmentAllowDelivery', 'sale', '\Bitrix\Sale\Compatible\EventCompatibility', 'onShipmentAllowDelivery');

		UnRegisterModuleDependences("sale", "OnOrderCancelSendEmail", "sale", "\\Bitrix\\Sale\\Compatible\\EventCompatibility", "onCallOrderCancelSendEmail");

		$eventManager->unRegisterEventHandler('sale', 'OnSaleOrderSaved', 'sale', '\Bitrix\Sale\Product2ProductTable', 'onSaleOrderAddEvent');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleStatusOrderChange', 'sale', '\Bitrix\Sale\Product2ProductTable', 'onSaleStatusOrderHandlerEvent');

		$eventManager->unRegisterEventHandler('sale', 'OnShipmentAllowDelivery', 'sale', '\Bitrix\Sale\Product2ProductTable', 'onSaleDeliveryOrderHandlerEvent');

		$eventManager->unRegisterEventHandler('sale', 'OnShipmentDeducted', 'sale', '\Bitrix\Sale\Product2ProductTable', 'onSaleDeductOrderHandlerEvent');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleOrderCanceled', 'sale', '\Bitrix\Sale\Product2ProductTable', 'onSaleCancelOrderHandlerEvent');

		$eventManager->unRegisterEventHandler('sale', 'OnSaleOrderPaid', 'sale', '\Bitrix\Sale\Product2ProductTable', 'onSalePayOrderHandlerEvent');
		
		RegisterModuleDependences("sale", "OnBasketOrder", "sale", "\\Bitrix\\Sale\\Product2ProductTable", "onSaleOrderAdd", 100);
		RegisterModuleDependences("sale", "OnSaleStatusOrder", "sale", "\\Bitrix\\Sale\\Product2ProductTable", "onSaleStatusOrderHandler", 100);
		RegisterModuleDependences("sale", "OnSaleDeliveryOrder", "sale", "\\Bitrix\\Sale\\Product2ProductTable", "onSaleDeliveryOrderHandler", 100);
		RegisterModuleDependences("sale", "OnSaleDeductOrder", "sale", "\\Bitrix\\Sale\\Product2ProductTable", "onSaleDeductOrderHandler", 100);
		RegisterModuleDependences("sale", "OnSaleCancelOrder", "sale", "\\Bitrix\\Sale\\Product2ProductTable", "onSaleCancelOrderHandler", 100);
		RegisterModuleDependences("sale", "OnSalePayOrder", "sale", "\\Bitrix\\Sale\\Product2ProductTable", "onSalePayOrderHandler", 100);

	}

}
