<?php
namespace Bitrix\Sale;

class EventActions
{
	const ADD = "ADD";
	const UPDATE = "UPDATE";
	const DELETE = "DELETE";

	// Events new kernel
	const EVENT_ON_ORDER_PAID = "OnSaleOrderPaid";
	const EVENT_ON_PAYMENT_PAID = "OnSalePaymentPaid";
	const EVENT_ON_BEFORE_ORDER_DELETE = "OnSaleBeforeOrderDelete";
	const EVENT_ON_ORDER_DELETED = "OnSaleOrderDeleted";
	const EVENT_ON_ORDER_BEFORE_SAVED = "OnSaleOrderBeforeSaved";
	const EVENT_ON_ORDER_SAVED = "OnSaleOrderSaved";
	const EVENT_ON_SHIPMENT_DELIVER = "OnSaleShipmentDelivery";

	const EVENT_ON_BEFORE_ORDER_CANCELED = "OnSaleBeforeOrderCanceled";
	const EVENT_ON_ORDER_CANCELED = "OnSaleOrderCanceled";

	const EVENT_ON_ORDER_PAID_SEND_MAIL = "OnSaleOrderPaidSendMail";
	const EVENT_ON_ORDER_CANCELED_SEND_MAIL = "OnSaleOrderCancelSendEmail";

	const EVENT_ON_BASKET_BEFORE_SAVED = "OnSaleBasketBeforeSaved";
	const EVENT_ON_BASKET_ITEM_BEFORE_SAVED = "OnSaleBasketItemBeforeSaved";
	const EVENT_ON_BASKET_ITEM_SAVED = "OnSaleBasketItemSaved";
	const EVENT_ON_BASKET_SAVED = "OnSaleBasketSaved";

	const EVENT_ON_SHIPMENT_TRACKING_NUMBER_CHANGE = "OnShipmentTrackingNumberChange";
	const EVENT_ON_SHIPMENT_ALLOW_DELIVERY = "OnShipmentAllowDelivery";
	const EVENT_ON_SHIPMENT_DEDUCTED = "OnShipmentDeducted";

	const EVENT_ON_BEFORE_ORDER_STATUS_CHANGE = "OnSaleBeforeStatusOrderChange";
	const EVENT_ON_ORDER_STATUS_CHANGE = "OnSaleStatusOrderChange";
	const EVENT_ON_ORDER_STATUS_CHANGE_SEND_MAIL = "OnSaleOrderStatusChangeSendEmail";

	const EVENT_ON_BEFORE_SHIPMENT_STATUS_CHANGE = "OnSaleBeforeStatusShipmentChange";
	const EVENT_ON_SHIPMENT_STATUS_CHANGE = "OnSaleStatusShipmentChange";
	const EVENT_ON_SHIPMENT_STATUS_CHANGE_SEND_MAIL = "OnSaleShipmentStatusChangeSendEmail";
	
	const EVENT_ON_ADMIN_ORDER_LIST = "OnSaleAdminOrderList";

	const EVENT_ON_BASKET_ITEM_REFRESH_DATA = "OnSaleBasketItemRefreshData";

	const ENTITY_ORDER = '\Bitrix\Sale\Order';
	const ENTITY_SHIPMENT = '\Bitrix\Sale\Shipment';

	/**
	 * @return array
	 */
	public static function getEventNotifyMap()
	{
		return array(
			static::EVENT_ON_ORDER_SAVED => array(
				"ENTITY" => static::ENTITY_ORDER,
				"METHOD" => array('\Bitrix\Sale\Notify', "sendOrderNew"),
			),
			static::EVENT_ON_ORDER_CANCELED => array(
				"ENTITY" => static::ENTITY_ORDER,
				"METHOD" => array('\Bitrix\Sale\Notify', "sendOrderCancel"),
			),
			static::EVENT_ON_ORDER_PAID => array(
				"ENTITY" => static::ENTITY_ORDER,
				"METHOD" => array('\Bitrix\Sale\Notify', "sendOrderPaid"),
			),

			static::EVENT_ON_ORDER_STATUS_CHANGE => array(
				"ENTITY" => static::ENTITY_ORDER,
				"METHOD" => array('\Bitrix\Sale\Notify', "sendOrderStatusChange"),
			),
			static::EVENT_ON_SHIPMENT_TRACKING_NUMBER_CHANGE => array(
				"ENTITY" => static::ENTITY_SHIPMENT,
				"METHOD" => array('\Bitrix\Sale\Notify', "sendShipmentTrackingNumberChange"),
			),
			static::EVENT_ON_SHIPMENT_ALLOW_DELIVERY => array(
				"ENTITY" => static::ENTITY_SHIPMENT,
				"METHOD" => array('\Bitrix\Sale\Notify', "sendShipmentAllowDelivery"),
			),
			static::EVENT_ON_SHIPMENT_STATUS_CHANGE => array(
				"ENTITY" => static::ENTITY_SHIPMENT,
				"METHOD" => array('\Bitrix\Sale\Notify', "sendShipmentStatusChange"),
			),

		);
	}

}
