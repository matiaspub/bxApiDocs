<?php

namespace Bitrix\Sale\PaySystem\ExtraService;

use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\SystemException;

class BaseManager
{
	protected static $isInit = false;

	/**
	 * @return bool
	 * @throws SystemException
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected static function initClassesList()
	{
		if (static::$isInit === true)
			return;

		$result = static::getBuildInExtraServices();
		Loader::registerAutoLoadClasses('sale', $result);

		$event = new Event('sale', static::getEventName());
		$event->send();

		foreach ($event->getParameters() as $class)
		{
			Loader::registerAutoLoadClasses(null, $class);
		}

		static::$isInit = true;
	}

	/**
	 * @param $entityId
	 * @throws NotImplementedException
	 * @return array
	 */
	public static function getExtraServiceByEntity($entityId)
	{
		throw new NotImplementedException();
	}

	/**
	 * @param $serviceId
	 * @throws NotImplementedException
	 * @return array
	 */
	public static function getExtraServiceBySystem($serviceId)
	{
		throw new NotImplementedException();
	}

	/**
	 * @throws NotImplementedException
	 * @return array;
	 */
	protected static function getBuildInExtraServices()
	{
		throw new NotImplementedException();
	}

	/**
	 * @throws NotImplementedException
	 * @return string
	 */
	protected static function getEventName()
	{
		throw new NotImplementedException();
	}

	/**
	 * @param array $params
	 * @param int $entityId
	 * @throws NotImplementedException
	 */
	public static function save(array $params, $entityId)
	{
		throw new NotImplementedException();
	}
}