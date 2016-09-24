<?php
namespace Bitrix\Sale\Internals;

class EventsPool
{
	protected static $events = array();


	public static function getEvents($code)
	{
		if (isset(static::$events[$code]))
		{
			return static::$events[$code];
		}

		return null;
	}

	/**
	 * @param $code
	 * @param $type
	 * @param $event
	 */
	public static function addEvent($code, $type, $event)
	{
		static::$events[$code][$type] = $event;
	}

	/**
	 * @param $code
	 */
	public static function resetEvents($code = null)
	{
		if ($code !== null)
		{
			unset(static::$events[$code]);
		}
		else
		{
			static::$events = array();
		}
	}
}
