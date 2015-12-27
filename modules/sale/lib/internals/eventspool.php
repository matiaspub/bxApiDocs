<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Sale\Order;

class EventsPool
{
	protected static $events = array();


	public static function getEvents(Order $order)
	{
		if (isset(static::$events[$order->getInternalId()]))
		{
			return static::$events[$order->getInternalId()];
		}

		return null;
	}

	/**
	 * @param Order $order
	 * @param $type
	 * @param $event
	 */
	public static function addEvent(Order $order, $type, $event)
	{
		static::$events[$order->getInternalId()][$type] = $event;
	}

	/**
	 * @param Order $order
	 */
	public static function resetEvents(Order $order = null)
	{
		if ($order !== null)
		{
			unset(static::$events[$order->getInternalId()]);
		}
		else
		{
			static::$events = array();
		}
	}
}
