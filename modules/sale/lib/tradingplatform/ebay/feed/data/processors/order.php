<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Processors;

use Bitrix\Main\Localization\Loc;
use \Bitrix\Main\SystemException;
use \Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\Internals\SiteCurrencyTable;
use Bitrix\Sale\Provider;
use \Bitrix\Sale\TradingPlatform\Logger;
use \Bitrix\Sale\TradingPlatform\Ebay\Ebay;
use \Bitrix\Sale\TradingPlatform\OrderTable;
use Bitrix\Sale\TradingPlatform\Xml2Array;

Loc::loadMessages(__FILE__);


class Order extends DataProcessor
{
	protected $siteId;

	public function __construct($params)
	{
		if(!isset($params["SITE_ID"]) || strlen($params["SITE_ID"]) <= 0)
			throw new ArgumentNullException("SITE_ID");

		$this->siteId = $params["SITE_ID"];
	}

	public function process($data)
	{
		if(!is_array($data))
			throw new SystemException("Data must be an array! ".__METHOD__);

		foreach($data as $order)
		{
			try
			{
				if(intval($this->processOrder($order)) > 0)
				{
					Ebay::log(
						Logger::LOG_LEVEL_INFO,
						"EBAY_DATA_PROCESSOR_ORDER_PROCESSED",
						$order["OrderID"],
						Loc::getMessage(
							"SALE_TP_EBAY_FDPO_ORDER_PROCESSED",
							array("#ORDER_ID" => $order["OrderID"])
						),
						$this->siteId
					);
				}
				else
				{
					Ebay::log(
						Logger::LOG_LEVEL_ERROR,
						"EBAY_DATA_PROCESSOR_ORDER_ERROR",
						$order["OrderID"],
						Loc::getMessage(
							"SALE_TP_EBAY_FDPO_ORDER_ERROR",
							array("#ORDER_ID" => $order["OrderID"])
						),
						$this->siteId
					);
				}
			}
			catch(SystemException $e)
			{
				Ebay::log(
					Logger::LOG_LEVEL_ERROR,
					"EBAY_DATA_PROCESSOR_ORDER_ERROR",
					$order["OrderID"],
					Loc::getMessage(
						"SALE_TP_EBAY_FDPO_ORDER_ERROR",
						array("#ORDER_ID" => $order["OrderID"])
					).".".$e->getMessage(),
					$this->siteId
				);
			}
		}

		\Bitrix\Sale\TradingPlatform\Ebay\Agent::add('ORDER_ACK', $this->siteId, 1, true);
		return true;
	}

	protected function getSku($ebaySku)
	{
		$result = "";
		$sku = explode("_", $ebaySku);

		if(isset($sku[1]) && strlen($sku[1]) > 0)
			$result = $sku[1];

		return $result;
	}

	protected function getSkuVariation($ebaySku)
	{
		$result = "";

		$sku = explode("_", $ebaySku);

		if(isset($sku[2]) && strlen($sku[2]) > 0)
			$result = $sku[2];

		return $result;
	}

	protected function normalizeTransactionsArray($transactArray)
	{
		foreach($transactArray["Transaction"] as $key => $transaction)
		{
			if(intval($key) !== $key)
				$transactArray["Transaction"] = array($transactArray["Transaction"]);

			break;
		}

		return $transactArray["Transaction"];
	}

	public function processOrder($orderEbay)
	{
		Ebay::log(Logger::LOG_LEVEL_DEBUG, "EBAY_DATA_PROCESSOR_ORDER_PROCESSING", $orderEbay["ExtendedOrderID"], print_r($orderEbay,true), $this->siteId);

		/*
		 * only in this case order is completely ready for shipping
		 */

		if($orderEbay["OrderStatus"]!= "Completed"
			|| !isset($orderEbay["CheckoutStatus"]["eBayPaymentStatus"])
			|| $orderEbay["CheckoutStatus"]["eBayPaymentStatus"] != "NoPaymentFailure"
//			|| empty($orderEbay["PaymentClearedTime"])
		)
		{
			Ebay::log(
				Logger::LOG_LEVEL_INFO,
				"EBAY_DATA_PROCESSOR_ORDER_SKIPPED",
				$orderEbay["ExtendedOrderID"],
				Loc::getMessage(
					"SALE_TP_EBAY_FDPO_ORDER_SKIPPED",
					array("#ORDER_ID#" => $orderEbay["ExtendedOrderID"])
				),
				$this->siteId
			);
			return array();
		}

		$ebay = \Bitrix\Sale\TradingPlatform\Ebay\Ebay::getInstance();
		$settings = $ebay->getSettings();

		if(!isset($settings[$this->siteId]["ORDER_PROPS"]) || !is_array($settings[$this->siteId]["ORDER_PROPS"]))
			throw new SystemException("Can't get order props map");

		$propsMap = $settings[$this->siteId]["ORDER_PROPS"];

		/*
		if(strtolower(SITE_CHARSET) != 'utf-8')
			$orderEbay = \Bitrix\Main\Text\Encoding::convertEncodingArray($orderEbay, 'UTF-8', SITE_CHARSET);
		*/

		$dbRes = OrderTable::getList(array(
			"filter" => array(
				"TRADING_PLATFORM_ID" => $ebay->getId(),
				"EXTERNAL_ORDER_ID" => $orderEbay["ExtendedOrderID"]
			)
		));

		if($orderCorrespondence = $dbRes->fetch())
		{
			Ebay::log(
				Logger::LOG_LEVEL_INFO,
				"EBAY_DATA_PROCESSOR_ORDER_ALREADY_EXIST",
				$orderEbay["ExtendedOrderID"],
				Loc::getMessage(
					"SALE_TP_EBAY_FDPO_ORDER_SKIPPED_EXIST",
					array("#ORDER_ID#" => $orderEbay["ExtendedOrderID"])
				),
				$this->siteId
			);

			return array();
		}

		/** @var \Bitrix\Sale\Order $order */
		$order = \Bitrix\Sale\Order::create($this->siteId);
		$order->setPersonTypeId($settings[$this->siteId]["PERSON_TYPE"]);
		$propsCollection = $order->getPropertyCollection();

		/** @var \Bitrix\Sale\PropertyValueCollection $propCollection */
		if(intval($propsMap["FIO"]) > 0)
		{
			$prop = $propsCollection->getItemByOrderPropertyId($propsMap["FIO"]);
			$prop->setValue($orderEbay["ShippingAddress"]["Name"]);
		}

		if(intval($propsMap["CITY"]) > 0)
		{
			$prop = $propsCollection->getItemByOrderPropertyId($propsMap["CITY"]);
			$prop->setValue($orderEbay["ShippingAddress"]["CityName"]);
		}

		if(intval($propsMap["PHONE"]) > 0)
		{
			$prop = $propsCollection->getItemByOrderPropertyId($propsMap["PHONE"]);
			$prop->setValue($orderEbay["ShippingAddress"]["Phone"]);
		}

		if(intval($propsMap["ZIP"]) > 0)
		{
			$prop = $propsCollection->getItemByOrderPropertyId($propsMap["ZIP"]);
			$prop->setValue($orderEbay["ShippingAddress"]["PostalCode"]);
		}

		if(intval($propsMap["ADDRESS"]) > 0)
		{
			$prop = $propsCollection->getItemByOrderPropertyId($propsMap["ADDRESS"]);
			$prop->setValue(
				$orderEbay["ShippingAddress"]["CountryName"]." ".
				$orderEbay["ShippingAddress"]["CityName"]." ".
				$orderEbay["ShippingAddress"]["Street1"]." ".
				(!empty($orderEbay["ShippingAddress"]["Street2"]) ? $orderEbay["ShippingAddress"]["Street2"]." " : "")
			);
		}

		$basket = null;
		$bitrixOrderId = 0;
		$userId = 0;
		$orderLineItemsIds = array();
		$transactionsArray = $this->normalizeTransactionsArray($orderEbay["TransactionArray"]);

		foreach($transactionsArray as $transaction)
		{
			//if we have more than one transaction let's create user from the first
			if($userId <= 0)
			{
				if(intval($propsMap["EMAIL"]) > 0 && !empty($transaction["Buyer"]["Email"]))
				{
					$prop = $propsCollection->getItemByOrderPropertyId($propsMap["EMAIL"]);
					$prop->setValue($transaction["Buyer"]["Email"]);

					$userId = $this->createUser(
						$transaction["Buyer"]["Email"],
						array(
							"NAME" => $transaction["Buyer"]["UserFirstName"],
							"LAST_NAME" => $transaction["Buyer"]["UserLastName"]
						)
					);
				}

				if($userId <= 0)
					$userId = \CSaleUser::GetAnonymousUserID();
			}

			if(intval($userId > 0))
				$order->setFieldNoDemand("USER_ID",	$userId);

			$fUserId = null;

			if ($order->getUserId() > 0)
				$fUserId = Fuser::getIdByUserId($order->getUserId());

			/** @var \Bitrix\Sale\Basket $basket */
			if(!$basket)
			{
				$basket = \Bitrix\Sale\Basket::create($this->siteId);
				$basket->setFUserId($fUserId);
			}

			$items = array();
			$isVariation = false;

			if(!empty($transaction["Item"]))
			{
				$items = Xml2Array::normalize($transaction["Item"]);
			}
			elseif(!empty($transaction["Variation"]))
			{
				$items = Xml2Array::normalize($transaction["Variation"]);
				$isVariation = true;
			}

			if(empty($items))
			{
				Ebay::log(Logger::LOG_LEVEL_INFO, "EBAY_DATA_PROCESSOR_ORDER_PROCESSING_TRANSACTION_ITEM_NOT_FOUND", $transaction["OrderLineItemID"], print_r($transaction,true), $this->siteId);
				continue;
			}

			foreach($items as $transactionItem)
			{
				$ebaySku = $isVariation ? $this->getSkuVariation($transactionItem["SKU"]) : $this->getSku($transactionItem["SKU"]);

				if(strlen($ebaySku) <=0)
				{
					Ebay::log(Logger::LOG_LEVEL_INFO, "EBAY_DATA_PROCESSOR_ORDER_PROCESSING_TRANSACTION_ITEM_SKU_NOT_FOUND", $transaction["OrderLineItemID"], print_r($transaction,true), $this->siteId);
					continue;
				}

				$item = $basket->createItem('catalog',	$ebaySku);
				$item->setField("PRODUCT_PROVIDER_CLASS", "CCatalogProductProvider");

				$itemData = array(
					"CUSTOM_PRICE" => "Y",
					"PRICE" => floatval($transaction["TransactionPrice"]),
					"QUANTITY" => floatval($transaction["QuantityPurchased"]),
					"NAME" => !empty($transactionItem["VariationTitle"]) ? $transactionItem["VariationTitle"] : $transactionItem["Title"],
					"CURRENCY" => SiteCurrencyTable::getSiteCurrency($this->siteId)
				);

				$data = Provider::getProductData($basket);

				if(!empty($data[$item->getBasketCode()]))
				{
					$itemData = array_merge($data[$item->getBasketCode()], $itemData);
				}
				else
				{
					$item->delete();
					$item = $basket->createItem('',	$ebaySku);
				}

				$res = $item->setFields($itemData);

				if($res->isSuccess())
				{
					$orderLineItemsIds[] = $transaction["OrderLineItemID"];
				}
				else
				{
					foreach($res->getErrors() as $error)
						Ebay::log(Logger::LOG_LEVEL_ERROR, "EBAY_DATA_PROCESSOR_ORDER_TRANSACTION_ITEM_CREATE_ERROR", $transaction["OrderLineItemID"], $error->getMessage(), $this->siteId);
				}
			}
		}

		$res = $order->setBasket($basket);

		if(!$res->isSuccess())
			foreach($res->getErrors() as $error)
				Ebay::log(Logger::LOG_LEVEL_ERROR, "EBAY_DATA_PROCESSOR_ORDER_CREATE_ERROR_SET_BASKET", $orderEbay["ExtendedOrderID"], $error->getMessage(), $this->siteId);

		//payments
		if(intval($settings[$this->siteId]["MAPS"]["PAYMENT"]["PayPal"]) > 0)
		{
			$payments = $order->getPaymentCollection();

			/** @var \Bitrix\Sale\Payment $payment */
			if($payments->count() > 0)
			{
				foreach ($payments as $payment)
				{
					if($payment->isPaid())
						$payment->setPaid("N");

					$payment->delete();
				}
			}

			$payment = $payments->createItem();
			$payment->setField('PAY_SYSTEM_ID', $settings[$this->siteId]["MAPS"]["PAYMENT"]["PayPal"]);
			$payment->setField('PAY_SYSTEM_NAME', "PayPal via Ebay");

			if($orderEbay["CheckoutStatus"]["eBayPaymentStatus"] == "NoPaymentFailure"
				&& $orderEbay["MonetaryDetails"]["Payments"]["Payment"]["PaymentStatus"]
				&& $orderEbay["MonetaryDetails"]["Payments"]["Payment"]["PaymentAmount"] == $orderEbay["Total"]
			)
			{
				$payment->setField("SUM", $orderEbay["AmountPaid"]);
				$payment->setPaid("Y");
			}
		}

		//shipment
		if(intval($settings[$this->siteId]["MAPS"]["SHIPMENT"][$orderEbay["ShippingServiceSelected"]["ShippingService"]]) > 0)
		{
			$shipments = $order->getShipmentCollection();

			/** @var \Bitrix\Sale\Shipment $shipment */
			if($shipments->count() > 0)
				foreach ($shipments as $shipment)
					if(!$shipment->isSystem())
						$shipment->delete();

			$shipment = $shipments->createItem();
			$shipment->setField('DELIVERY_ID', $settings[$this->siteId]["MAPS"]["SHIPMENT"][$orderEbay["ShippingServiceSelected"]["ShippingService"]]);
			$shipment->setField('CUSTOM_PRICE_DELIVERY', "Y");
			$shipment->setField('BASE_PRICE_DELIVERY', $orderEbay["ShippingServiceSelected"]["ShippingServiceCost"]);
			$basket = $order->getBasket();

			if($basket)
			{
				$shipmentItemCollection = $shipment->getShipmentItemCollection();
				$basketItems = $basket->getBasketItems();

				foreach ($basketItems as $basketItem)
				{
					$shipmentItem = $shipmentItemCollection->createItem($basketItem);
					$shipmentItem->setQuantity($basketItem->getField('QUANTITY'));
				}
			}
			// todo: delivery price changed. Probably bug.
			$shipment->setField('BASE_PRICE_DELIVERY', $orderEbay["ShippingServiceSelected"]["ShippingServiceCost"]);
		}
		else
		{
			Ebay::log(
				Logger::LOG_LEVEL_ERROR,
				"EBAY_DATA_PROCESSOR_ORDER_SHIPPING_ERROR",
				$orderEbay["ExtendedOrderID"],
				Loc::getMessage(
					"SALE_TP_EBAY_FDPO_NOT_MAPPED_SHIPPING",
					array(
						"#ORDER_ID#" => $orderEbay["ExtendedOrderID"],
						"#EBAY_SHIPPING#" => $orderEbay["ShippingServiceSelected"]["ShippingService"]
					)
				),
				$this->siteId
			);

			return 0;
		}

		// order status
		if(strlen($settings[$this->siteId]["STATUS_MAP"][$orderEbay["OrderStatus"]]) > 0)
		{
			switch($settings[$this->siteId]["STATUS_MAP"][$orderEbay["OrderStatus"]])
			{
				/* flags */
				case "CANCELED":

					if(!$order->setField("CANCELED", "Y"))
					{
						Ebay::log(
							Logger::LOG_LEVEL_ERROR,
							"EBAY_DATA_PROCESSOR_ORDER_CANCELING_ERROR",
							$orderEbay["ExtendedOrderID"],
							Loc::getMessage(
								"SALE_TP_EBAY_FDPO_ORDER_CANCEL_ERROR",
								array(
									"#ORDER_ID#" => $orderEbay["ExtendedOrderID"]
								)
							),
							$this->siteId
						);
					}

					break;

				case "PAYED":
					$payments = $order->getPaymentCollection();

					foreach ($payments as $payment)
						$payment->setPaid("Y");

					break;

				case "ALLOW_DELIVERY":
					// we suggest that only one shipment exists
					$shipments = $order->getShipmentCollection();

					foreach ($shipments as $shipment)
					{
						if(!$shipment->isSystem())
						{
							if(!$shipment->allowDelivery())
							{
								Ebay::log(
									Logger::LOG_LEVEL_ERROR,
									"EBAY_DATA_PROCESSOR_ORDER_ALLOW_DELIVERY_ERROR",
									$orderEbay["ExtendedOrderID"],
									Loc::getMessage(
										"SALE_TP_EBAY_FDPO_ORDER_ALLOW_DELIVERY_ERROR",
										array(
											"#ORDER_ID#" => $orderEbay["ExtendedOrderID"]
										)
									),
									$this->siteId
								);
							}
						}
					}

					break;

				case "DEDUCTED":
					$shipments = $order->getShipmentCollection();

					foreach ($shipments as $shipment)
					{
						if(!$shipment->isSystem())
						{
							if(!$shipment->setField('DEDUCTED', 'Y'))
							{
								Ebay::log(
									Logger::LOG_LEVEL_ERROR,
									"EBAY_DATA_PROCESSOR_ORDER_DEDUCTIOING_ERROR",
									$orderEbay["ExtendedOrderID"],
									Loc::getMessage(
										"SALE_TP_EBAY_FDPO_ORDER_DEDUCT_ERROR",
										array("#ORDER_ID#" => $orderEbay["ExtendedOrderID"])
									),
									$this->siteId
								);
							}
						}
					}

					break;

				/* statuses */
				default:
					$res = $order->setField("STATUS_ID", $settings[$this->siteId]["STATUS_MAP"][$orderEbay["OrderStatus"]]);

					/** @var \Bitrix\Sale\Result $res */
					if(!$res->isSuccess())
					{
						Ebay::log(
							Logger::LOG_LEVEL_ERROR,
							"EBAY_DATA_PROCESSOR_ORDER_CHANGE_STATUS_ERROR",
							$orderEbay["OrderLineItemID"],
							Loc::getMessage(
								"SALE_TP_EBAY_FDPO_ORDER_SET_STATUS_ERROR",
								array(
									"#ORDER_ID#" => $orderEbay["ExtendedOrderID"],
									"#STATUS#" => $orderEbay["OrderStatus"]
									)
							),
							$this->siteId
						);
					}
			}
		}

		$order->setField("PRICE", $orderEbay["Total"]);
		$order->setField("XML_ID", Ebay::TRADING_PLATFORM_CODE."_".$orderEbay["ExtendedOrderID"]);
		$res = $order->save();

		if(!$res->isSuccess())
		{
			foreach($res->getErrors() as $error)
				Ebay::log(Logger::LOG_LEVEL_ERROR, "EBAY_DATA_PROCESSOR_ORDER_SAVE_ERROR", $orderEbay["ExtendedOrderID"], print_r($error->getMessage(),true), $this->siteId);
		}
		else
		{
			$bitrixOrderId = $order->getId();

			Ebay::log(
				Logger::LOG_LEVEL_INFO,
				"EBAY_DATA_PROCESSOR_ORDER_CREATED",
				$bitrixOrderId,
				Loc::getMessage(
					"SALE_TP_EBAY_FDPO_ORDER_SAVED",
					array("#ORDER_ID#" => $bitrixOrderId)
				),
				$this->siteId
			);

			\CSaleMobileOrderPush::send("ORDER_CREATED", array("ORDER_ID" => $bitrixOrderId));

			$res = OrderTable::add(array(
				"ORDER_ID" => $bitrixOrderId,
				"TRADING_PLATFORM_ID" => $ebay->getId(),
				"EXTERNAL_ORDER_ID" => $orderEbay["ExtendedOrderID"],
				"PARAMS" => array(
					"ORDER_LINES" => $orderLineItemsIds,
					"ORDER_ID" => $orderEbay["OrderID"]
				)
			));

			if(!$res->isSuccess())
			{
				foreach($res->getErrors() as $error)
					Ebay::log(Logger::LOG_LEVEL_ERROR, "EBAY_DATA_PROCESSOR_ORDER_DELIVERY_SAVE_ERROR", $orderEbay["ExtendedOrderID"], $error->getMessage(), $this->siteId);
			}
		}

		// send confirmation
		if($bitrixOrderId > 0 && !empty($orderLineItemsIds))
		{
			$ebayFeed = \Bitrix\Sale\TradingPlatform\Ebay\Feed\Manager::createFeed("ORDER_ACK", $this->siteId);
			$sourceData = array();

			foreach($orderLineItemsIds as $id)
				$sourceData[] = array("ORDER_ID" => $orderEbay["OrderID"], "ORDER_LINE_ITEM_ID" => $id);

			$ebayFeed->setSourceData(array($sourceData));
			$ebayFeed->processData();
		}
		return $bitrixOrderId;
	}

	protected function createUser($email, $name)
	{
		$errors = array();

		$userId = \CSaleUser::DoAutoRegisterUser(
			$email,
			$name,
			$this->siteId,
			$errors);

		if (!empty($errors))
		{
			$errorMessage = "";

			foreach($errors as $val)
				$errorMessage .= $val["TEXT"];

			throw new SystemException($errorMessage);
		}

		return $userId;
	}
}
