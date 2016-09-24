<?php
namespace Bitrix\Sale\Internals;

use \Bitrix\Main;
use \Bitrix\Main\Config;
use \Bitrix\Sale;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Product2ProductTable extends Main\Entity\DataManager
{
	public static function getTableName()
	{
		return "b_sale_product2product";
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'PRODUCT_ID' => array(
				'data_type' => 'integer'
			),
			'PARENT_PRODUCT_ID' => array(
				'data_type' => 'integer'
			),
			'CNT' => array(
				'data_type' => 'integer'
			)
		);
	}

	/**
	 *
	 * Remove old products from b_sale_product2product table.
	 * Used in agents.
	 *
	 * @param int $liveTime in days
	 *
	 * @return string
	 */
	public static function deleteOldProducts($liveTime = 10)
	{
		$connection = Main\Application::getConnection();
		$type = $connection->getType();
		$helper = $connection->getSqlHelper();
		$liveTo = $helper->addSecondsToDateTime($liveTime * 24 * 3600, "o.DATE_INSERT");
		$now = $helper->getCurrentDateTimeFunction();

		// Update existing
		if ($type == "mysql")
		{
			$sqlUpdate = "UPDATE b_sale_product2product p2p, b_sale_basket b, b_sale_basket b1, b_sale_order o, b_sale_order_processing op
				SET  p2p.CNT = p2p.CNT - 1
				WHERE b.ORDER_ID = b1.ORDER_ID AND
					b.ID <> b1.ID AND
					$now > $liveTo AND
					o.ID = b.ORDER_ID AND
					o.ID = op.ORDER_ID AND
					op.PRODUCTS_REMOVED = 'N' AND
					p2p.PRODUCT_ID = b.PRODUCT_ID AND
					p2p.PARENT_PRODUCT_ID = b1.PRODUCT_ID";
		}
		elseif ($type == "mssql")
		{
			$sqlUpdate = "UPDATE b_sale_product2product
				SET  CNT = CNT - 1
				FROM b_sale_product2product p2p, b_sale_basket b, b_sale_basket b1, b_sale_order o, b_sale_order_processing op
				WHERE b.ORDER_ID = b1.ORDER_ID AND
					b.ID <> b1.ID AND
					$now > $liveTo AND
					o.ID = b.ORDER_ID AND
					o.ID = op.ORDER_ID AND
					op.PRODUCTS_REMOVED = 'N' AND
					p2p.PRODUCT_ID = b.PRODUCT_ID AND
					p2p.PARENT_PRODUCT_ID = b1.PRODUCT_ID";
		}
		else // Oracle
		{
			$sqlUpdate = "UPDATE b_sale_product2product
				SET CNT = CNT - 1
				WHERE ID IN (
					SELECT p2p.ID FROM b_sale_product2product p2p, b_sale_basket b, b_sale_basket b1, b_sale_order o, b_sale_order_processing op
					WHERE b.ORDER_ID = b1.ORDER_ID AND
						b.ID <> b1.ID AND
						$now > $liveTo AND
						o.ID = b.ORDER_ID AND
						o.ID = op.ORDER_ID AND
						op.PRODUCTS_REMOVED = 'N' AND
						p2p.PRODUCT_ID = b.PRODUCT_ID AND
						p2p.PARENT_PRODUCT_ID = b1.PRODUCT_ID
					)";
		}

		$connection->query($sqlUpdate);

		// Update Status
		$updateRemStatusSql = "UPDATE b_sale_order_processing SET PRODUCTS_REMOVED = 'Y'";
		$connection->query($updateRemStatusSql);

		// Delete
		$deleteSql = "DELETE FROM b_sale_product2product WHERE CNT <= 0";
		$connection->query($deleteSql);

		return "\\Bitrix\\Sale\\Product2ProductTable::deleteOldProducts(".intval($liveTime).");";
	}

	/**
	 * Add products from order or updates existing.
	 *
	 * @param $orderId
	 *
	 * @return void
	 */
	public static function addProductsFromOrder($orderId = 0)
	{
		$orderId = (int)$orderId;

		if (Sale\OrderProcessingTable::hasAddedProducts($orderId))
			return;

		$connection = Main\Application::getConnection();
		$type = $connection->getType();

		// Update existing
		if ($type == "mysql")
		{
			$sqlUpdate = "UPDATE b_sale_product2product p2p, b_sale_basket b, b_sale_basket b1
				SET  p2p.CNT = p2p.CNT + 1
				WHERE b.ORDER_ID = b1.ORDER_ID AND
					b.ID <> b1.ID AND
					b.ORDER_ID = $orderId AND
					p2p.PRODUCT_ID = b.PRODUCT_ID AND
					p2p.PARENT_PRODUCT_ID = b1.PRODUCT_ID";
		}
		elseif ($type == "mssql")
		{
			$sqlUpdate = "UPDATE b_sale_product2product
				SET CNT = CNT + 1
				FROM b_sale_product2product p2p, b_sale_basket b, b_sale_basket b1
				WHERE b.ORDER_ID = b1.ORDER_ID AND
					b.ID <> b1.ID AND
					b.ORDER_ID = $orderId AND
					p2p.PRODUCT_ID = b.PRODUCT_ID AND
					p2p.PARENT_PRODUCT_ID = b1.PRODUCT_ID";
		}
		else // Oracle
		{
			$sqlUpdate = "UPDATE b_sale_product2product
				SET CNT = CNT + 1
				WHERE ID IN (
					SELECT p2p.ID FROM b_sale_product2product p2p, b_sale_basket b, b_sale_basket b1
					WHERE b.ORDER_ID = b1.ORDER_ID AND
						b.ID <> b1.ID AND
						b.ORDER_ID = $orderId AND
						p2p.PRODUCT_ID = b.PRODUCT_ID AND
						p2p.PARENT_PRODUCT_ID = b1.PRODUCT_ID
					)";
		}

		$connection->query($sqlUpdate);

		// Insert new
		$sqlInsert = "INSERT INTO b_sale_product2product (PRODUCT_ID, PARENT_PRODUCT_ID, CNT)
			SELECT b.PRODUCT_ID, b1.PRODUCT_ID, 1
			FROM b_sale_basket b, b_sale_basket b1
			WHERE b.ORDER_ID = b1.ORDER_ID AND
				b.ORDER_ID = $orderId AND
				b.ID <> b1.ID AND
				NOT EXISTS (SELECT 1 FROM b_sale_product2product d WHERE d.PRODUCT_ID = b.PRODUCT_ID AND d.PARENT_PRODUCT_ID = b1.PRODUCT_ID)";

		$connection->query($sqlInsert);

		Sale\OrderProcessingTable::markProductsAdded($orderId);

		if (defined("BX_COMP_MANAGED_CACHE"))
		{
			$app = Main\Application::getInstance();
			$app->getTaggedCache()->clearByTag('sale_product_buy');
		}
	}

	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 */
	public static function onSaleOrderAddEvent(Main\Event $event)
	{
		$order = $event->getParameter('ENTITY');
		$isNew = $event->getParameter('IS_NEW');
		if ((!$order instanceof Sale\Order))
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_PRODUCT2PRODUCT_WRONG_ORDER'), 'SALE_EVENT_PRODUCT2PRODUCT_ON_SALE_ORDER_ADD_WRONG_ORDER'),
				'sale'
			);
		}

		$basket = $order->getBasket();

		if ($isNew && ($basket && count($basket) > 0))
		{
			static::onSaleOrderAdd($order->getId());
		}

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 */
	public static function onSaleStatusOrderHandlerEvent(Main\Event $event)
	{
		$order = $event->getParameter('ENTITY');
		$value = $event->getParameter('VALUE');
		if ((!$order instanceof Sale\Order))
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_PRODUCT2PRODUCT_WRONG_ORDER'), 'SALE_EVENT_PRODUCT2PRODUCT_ON_SALE_ORDER_STATUS_WRONG_ORDER'),
				'sale'
			);
		}

		static::onSaleStatusOrderHandler($order->getId(), $value);

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 */
	public static function onSaleDeliveryOrderHandlerEvent(Main\Event $event)
	{
		$shipment = $event->getParameter('ENTITY');
		if ((!$shipment instanceof Sale\Shipment))
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_PRODUCT2PRODUCT_WRONG_SHIPMENT'), 'SALE_EVENT_PRODUCT2PRODUCT_ON_SALE_DELIVERY_ORDER_WRONG_SHIPMENT'),
				'sale'
			);
		}

		if (!$shipmentCollection = $shipment->getCollection())
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_PRODUCT2PRODUCT_WRONG_SHIPMENTCOLLECTION'), 'SALE_EVENT_PRODUCT2PRODUCT_ON_SALE_DELIVERY_ORDER_WRONG_SHIPMENTCOLLECTION'),
				'sale'
			);

		}

		if (!$order = $shipmentCollection->getOrder())
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_PRODUCT2PRODUCT_WRONG_ORDER'), 'SALE_EVENT_PRODUCT2PRODUCT_ON_SALE_DELIVERY_ORDER_WRONG_ORDER'),
				'sale'
			);

		}

		static::onSaleDeliveryOrderHandler($order->getId(), $order->isAllowDelivery() ? 'Y' : 'N');

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 */
	public static function onSaleDeductOrderHandlerEvent(Main\Event $event)
	{
		$shipment = $event->getParameter('ENTITY');
		if ((!$shipment instanceof Sale\Shipment))
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_PRODUCT2PRODUCT_WRONG_SHIPMENT'), 'SALE_EVENT_PRODUCT2PRODUCT_ON_SALE_DEDUCT_ORDER_WRONG_SHIPMENT'),
				'sale'
			);
		}

		if (!$shipmentCollection = $shipment->getCollection())
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_PRODUCT2PRODUCT_WRONG_SHIPMENTCOLLECTION'), 'SALE_EVENT_PRODUCT2PRODUCT_ON_SALE_DEDUCT_ORDER_WRONG_SHIPMENTCOLLECTION'),
				'sale'
			);

		}

		if (!$order = $shipmentCollection->getOrder())
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_PRODUCT2PRODUCT_WRONG_ORDER'), 'SALE_EVENT_PRODUCT2PRODUCT_ON_SALE_DEDUCT_ORDER_WRONG_ORDER'),
				'sale'
			);

		}


		static::onSaleDeductOrderHandler($order->getId(), $order->isShipped() ? 'Y' : 'N');

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 */
	public static function onSaleCancelOrderHandlerEvent(Main\Event $event)
	{
		$order = $event->getParameter('ENTITY');
		if ((!$order instanceof Sale\Order))
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_PRODUCT2PRODUCT_WRONG_ORDER'), 'SALE_EVENT_PRODUCT2PRODUCT_ON_SALE_CANCELED_ORDER_WRONG_ORDER'),
				'sale'
			);
		}

		static::onSaleCancelOrderHandler($order->getId(), $order->isCanceled() ? 'Y' : 'N');

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * @param Main\Event $event
	 *
	 * @return Main\EventResult
	 */
	public static function onSalePayOrderHandlerEvent(Main\Event $event)
	{
		$order = $event->getParameter('ENTITY');
		if ((!$order instanceof Sale\Order))
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_PRODUCT2PRODUCT_WRONG_ORDER'), 'SALE_EVENT_PRODUCT2PRODUCT_ON_SALE_PAID_ORDER_WRONG_ORDER'),
				'sale'
			);
		}

		static::onSaleCancelOrderHandler($order->getId(), $order->isPaid() ? 'Y' : 'N');

		return new Main\EventResult( Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * Executes when order status added.
	 *
	 * @param $orderId
	 * @return void
	 */
	public static function onSaleOrderAdd($orderId)
	{
		$allowIds = Config\Option::get("sale", "p2p_status_list", "");
		if ($allowIds != '')
			$allowIds = unserialize($allowIds);
		else
			$allowIds = array();

		if (!empty($allowIds) && is_array($allowIds) && in_array("N", $allowIds))
		{
			static::addProductsFromOrder($orderId);
		}
	}

	/**
	 * Executes when order status has changed.
	 *
	 * @param $orderId
	 * @param $status
	 * @return void
	 */
	public static function onSaleStatusOrderHandler($orderId, $status)
	{
		$allowIds = Config\Option::get("sale", "p2p_status_list", "");
		if ($allowIds != '')
			$allowIds = unserialize($allowIds);
		else
			$allowIds = array();

		if (!empty($allowIds) && is_array($allowIds) && in_array($status, $allowIds))
		{
			static::addProductsFromOrder($orderId);
		}
	}

	/**
	 * Executes when order status Delivered.
	 *
	 * @param $orderId
	 * @param $status
	 * @return void
	 */
	public static function onSaleDeliveryOrderHandler($orderId, $status)
	{
		if ($status == 'Y')
		{
			$allowIds = Config\Option::get("sale", "p2p_status_list", "");
			if ($allowIds != '')
				$allowIds = unserialize($allowIds);
			else
				$allowIds = array();

			if (!empty($allowIds) && is_array($allowIds) && in_array("F_DELIVERY", $allowIds))
			{
				static::addProductsFromOrder($orderId);
			}
		}
	}

	/**
	 * Executes when order status has deducted.
	 *
	 * @param $orderId
	 * @param $status
	 * @return void
	 */
	public static function onSaleDeductOrderHandler($orderId, $status)
	{
		if ($status == 'Y')
		{
			$allowIds = Config\Option::get("sale", "p2p_status_list", "");
			if ($allowIds != '')
				$allowIds = unserialize($allowIds);
			else
				$allowIds = array();

			if (!empty($allowIds) && is_array($allowIds) && in_array("F_OUT", $allowIds))
			{
				static::addProductsFromOrder($orderId);
			}
		}
	}

	/**
	 * Executes when order status has canceled.
	 *
	 * @param $orderId
	 * @param $status
	 * @return void
	 */
	public static function onSaleCancelOrderHandler($orderId, $status)
	{
		if ($status == 'Y')
		{
			$allowIds = Config\Option::get("sale", "p2p_status_list", "");
			if ($allowIds != '')
				$allowIds = unserialize($allowIds);
			else
				$allowIds = array();

			if (!empty($allowIds) && is_array($allowIds) && in_array("F_CANCELED", $allowIds))
				static::addProductsFromOrder($orderId);
		}
	}

	/**
	 * Executes when order status has canceled.
	 *
	 * @param $orderId
	 * @param $status
	 * @return void
	 */
	public static function onSalePayOrderHandler($orderId, $status)
	{
		if ($status == 'Y')
		{
			$allowIds = Config\Option::get("sale", "p2p_status_list", "");
			if ($allowIds != '')
				$allowIds = unserialize($allowIds);
			else
				$allowIds = array();

			if (!empty($allowIds) && is_array($allowIds) && in_array("F_PAY", $allowIds))
				static::addProductsFromOrder($orderId);
		}
	}
}