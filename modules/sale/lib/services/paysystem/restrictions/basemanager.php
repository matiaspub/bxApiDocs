<?php

namespace Bitrix\Sale\PaySystem\Restrictions;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\NotImplementedException;
use Bitrix\Sale\Internals\CollectableEntity;

class BaseManager
{
	protected static $isInit = false;

	/**
	 * @param CollectableEntity $entity
	 * @param $serviceId
	 * @return bool
	 * @throws NotImplementedException
	 */
	public static function checkAptitudeService(CollectableEntity $entity, $serviceId)
	{
		$restrictions = static::getRestrictionsForService($serviceId);
		foreach ($restrictions as $restriction)
		{
			if (!static::checkConcreteRestriction($entity, $restriction))
				return false;
		}

		return true;
	}

	/**
	 * @param CollectableEntity $entity
	 * @param $restriction
	 * @return mixed
	 */
	public static function checkConcreteRestriction(CollectableEntity $entity, $restriction)
	{
		static::initRestrictions();

		$restriction['PARAMS'] = unserialize($restriction['PARAMS']);

		return $restriction['CLASS_NAME']::checkAptitude($entity, $restriction);
	}

	/**
	 * @param $serviceId
	 * @throws NotImplementedException
	 * @return array()
	 */
	public static function getRestrictionsForService($serviceId)
	{
		throw new NotImplementedException();
	}

	/**
	 * @throws NotImplementedException
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected static function initRestrictions()
	{
		if (static::$isInit === true)
			return;

		$buildInRestriction = static::getBuildInRestrictions();
		Loader::registerAutoLoadClasses('sale', $buildInRestriction);

		$event = new Event('sale', static::getEventName());
		$event->send();

		foreach ($event->getResults() as $eventResult)
        {
            if ($eventResult->getType() != EventResult::ERROR)
            {
                foreach ($eventResult->getParameters() as $class)
                    Loader::registerAutoLoadClasses(null, $class);
            }
        }

		static::$isInit = true;
	}

	/**
	 * @throws NotImplementedException
	 * @return array
	 */
	public static function getBuildInRestrictions()
	{
		throw new NotImplementedException();
	}

	/**
	 * @throws NotImplementedException
	 * @return string
	 */
	public static function getEventName()
	{
		throw new NotImplementedException();
	}
}