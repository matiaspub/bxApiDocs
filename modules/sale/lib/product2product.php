<?php
namespace Bitrix\Sale;

use \Bitrix\Main\Entity\DataManager as DataManager;
use \Bitrix\Sale\OrderProcessingTable as OrderProcessing;
use \Bitrix\Main\Application as Application;
use \Bitrix\Main\Config\Option as Option;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Product2ProductTable extends DataManager
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
	 * Remove old products from b_sale_product2product table.
	 * Used in agents.
	 *
	 * @param int $liveTime in days
	 */
	public static function deleteOldProducts($liveTime = 10)
	{
		$connection = Application::getConnection();
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

		if (OrderProcessing::hasAddedProducts($orderId))
			return;

		$connection = Application::getConnection();
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

		OrderProcessing::markProductsAdded($orderId);

		if (defined("BX_COMP_MANAGED_CACHE"))
		{
			$app = Application::getInstance();
			$app->getTaggedCache()->clearByTag('sale_product_buy');
		}
	}

	/**
	 * Executes when order status added.
	 *
	 * @param $orderId
	 * @return void
	 */
	public static function onSaleOrderAdd($orderId)
	{
		$allowIds = Option::get("sale", "p2p_status_list", "");
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
		$allowIds = Option::get("sale", "p2p_status_list", "");
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
			$allowIds = Option::get("sale", "p2p_status_list", "");
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
			$allowIds = Option::get("sale", "p2p_status_list", "");
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
			$allowIds = Option::get("sale", "p2p_status_list", "");
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
			$allowIds = Option::get("sale", "p2p_status_list", "");
			if ($allowIds != '')
				$allowIds = unserialize($allowIds);
			else
				$allowIds = array();

			if (!empty($allowIds) && is_array($allowIds) && in_array("F_PAY", $allowIds))
				static::addProductsFromOrder($orderId);
		}
	}
}