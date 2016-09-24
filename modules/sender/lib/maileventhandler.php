<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Sender;

use Bitrix\Main\EventManager;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class MailEventHandler
{
	private static $list = array();

	/**
	 * @param \Bitrix\Main\Event $event
	 * @return mixed
	 */
	public static function handleEvent(Event $event)
	{
		$eventData = $event->getParameters();
		$eventData = $eventData[0];

		$eventName = $eventData['EVENT_NAME'];
		$fields = is_array($eventData['C_FIELDS']) ? $eventData['C_FIELDS'] : array();

		if(static::isPreventable($eventName, $fields))
		{
			// error
			$result = new EventResult(EventResult::ERROR);
		}
		else
		{
			// success
			$result = new EventResult(EventResult::SUCCESS);
		}

		return $result;
	}

	public static function prevent($eventName, array $filter)
	{
		if(empty(static::$list[$eventName]))
		{
			EventManager::getInstance()->addEventHandler('main', 'OnBeforeMailEventAdd', array(__CLASS__, 'handleEvent'), false, 1);
		}

		static::$list[$eventName][] = $filter;
	}

	public static function isPreventable($eventName, array $fields)
	{
		if(empty(static::$list[$eventName]))
			return false;

		$prevent = false;

		// check each filter
		foreach(static::$list[$eventName] as $filter)
		{
			foreach($filter as $key => $value)
			{
				$prevent = true;
				if(!isset($fields[$key]) || $fields[$key] != $value)
				{
					$prevent = false;
					break;
				}
			}

			if($prevent)
				break;
		}

		return $prevent;
	}
}