<?php
namespace Bitrix\Sale\Internals;
use \Bitrix\Main\Entity\DataManager as DataManager;
use \Bitrix\Main\Type\DateTime as DateTime;
use \Bitrix\Main\Application as Application;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


class OrderProcessingTable extends DataManager
{
	protected $orderProcessedCache = array();

	public static function getTableName()
	{
		return "b_sale_order_processing";
	}

	public static function getMap()
	{
		return array(
			'ORDER_ID' => array(
				'primary' => true,
				'data_type' => 'integer',
			),
			'PRODUCTS_ADDED' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),
			'PRODUCTS_REMOVED' =>array (
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),
			'ORDER' => array(
				'data_type' => "Bitrix\\Ssale\\OrderTable",
				'reference' => array('=this.ORDER_ID' => 'ref.ID')
			)
		);
	}

	/**
	 * Wether order was processed
	 *
	 * @param int $orderId
	 *
	 * @return bool
	 */
	public static function hasAddedProducts($orderId = 0)
	{
		$orderId = (int)$orderId;
		$iterator = static::getList(array(
			"filter" => array("ORDER_ID" => $orderId)
		));

		$row = $iterator->fetch();
		return $row &&  $row['PRODUCTS_ADDED'] == "Y";
	}

	/**
	 * Wether order was processed
	 *
	 * @param int $orderId
	 *
	 * @return bool|null
	 */
	public static function hasRemovedProducts($orderId = 0)
	{
		$orderId = (int)$orderId;
		$iterator = static::getList(array(
			"filter" => array("ORDER_ID" => $orderId)
		));

		$row = $iterator->fetch();
		return $row &&  $row['PRODUCTS_REMOVED'] == "Y";
	}

	/**
	 * Mark order as processed
	 *
	 * @param int $orderId
	 */
	public static function markProductsAdded($orderId = 0)
	{
		$orderId = (int)$orderId;
		$iterator = static::getList(array(
			"filter" => array("ORDER_ID" => $orderId)
		));
		if($row = $iterator->fetch())
		{
			static::update($orderId, array("PRODUCTS_ADDED" => 'Y'));
		}
		else
		{
			static::add(array("ORDER_ID" => $orderId, "PRODUCTS_ADDED" => 'Y'));
		}
	}

	/**
	 * Mark order as processed
	 *
	 * @param int $orderId
	 */
	public static function markProductsRemoved($orderId = 0)
	{
		$orderId = (int)$orderId;
		$iterator = static::getList(array(
			"filter" => array("ORDER_ID" => $orderId)
		));
		if($row = $iterator->fetch())
		{
			static::update($orderId, array("PRODUCTS_REMOVED" => 'Y'));
		}
		else
		{
			static::add(array("ORDER_ID" => $orderId, "PRODUCTS_REMOVED" => 'Y'));
		}
	}

	/**
	 * Clear table
	 *
	 * @param int $orderId
	 */
	public static function clear()
	{
		$connection = Application::getConnection();
		$sql = "DELETE FROM " . static::getTableName() . "
				WHERE ID NOT IN (SELECT ID FROM b_sale_order)";
		$connection->query($sql);
	}
}

?>