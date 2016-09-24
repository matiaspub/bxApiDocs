<?
namespace Bitrix\Sale\Services\Base;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Sale\Internals\ServiceRestrictionTable;
use Bitrix\Sale\Internals\CollectableEntity;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\SystemException;
use Bitrix\Main\EventResult;

class RestrictionManager
{
	protected static $classNames;
	protected static $cachedFields = array();

	const MODE_CLIENT = 1;
	const MODE_MANAGER = 2;

	const SEVERITY_NONE = 0;
	const SEVERITY_SOFT = 1;
	const SEVERITY_STRICT = 2;

	const SERVICE_TYPE_SHIPMENT = 0;
	const SERVICE_TYPE_PAYMENT = 1;
	const SERVICE_TYPE_COMPANY = 2;

	protected static function init()
	{
		if(static::$classNames != null)
			return;

		$classes = static::getBuildInRestrictions();

		Loader::registerAutoLoadClasses('sale', $classes);

		$event = new Event('sale', static::getEventName());
		$event->send();
		$resultList = $event->getResults();

		if (is_array($resultList) && !empty($resultList))
		{
			$customClasses = array();

			foreach ($resultList as $eventResult)
			{
				/** @var  EventResult $eventResult*/
				if ($eventResult->getType() != EventResult::SUCCESS)
					throw new SystemException("Can't add custom restriction class successfully");

				$params = $eventResult->getParameters();

				if(!empty($params) && is_array($params))
					$customClasses = array_merge($customClasses, $params);
			}

			if(!empty($customClasses))
			{
				Loader::registerAutoLoadClasses(null, $customClasses);
				$classes = array_merge($customClasses, $classes);
			}
		}

		static::$classNames = array_keys($classes);
	}

	/**
	 * @return string
	 * @throws NotImplementedException
	 */
	public static function getEventName()
	{
		throw new NotImplementedException;
	}

	/**
	 * @return array
	 * @throws SystemException
	 */
	public static function getClassesList()
	{
		if (static::$classNames === null)
			self::init();

		return static::$classNames;
	}

	/**
	 * @param $serviceId
	 * @param CollectableEntity $entity
	 * @param int $mode
	 * @return int
	 * @throws SystemException
	 */
	public static function checkService($serviceId, CollectableEntity $entity, $mode = self::MODE_CLIENT)
	{
		if(intval($serviceId) <= 0)
			return self::SEVERITY_NONE;

		self::init();
		$result = self::SEVERITY_NONE;
		$restrictions = static::getRestrictionsList($serviceId);

		foreach($restrictions as $rstrParams)
		{
			if(!$rstrParams['PARAMS'])
				$rstrParams['PARAMS'] = array();

			$res = $rstrParams['CLASS_NAME']::checkByEntity($entity, $rstrParams['PARAMS'], $mode, $serviceId);

			if($res == self::SEVERITY_STRICT)
				return $res;

			if($res == self::SEVERITY_SOFT && $result != self::SEVERITY_SOFT)
				$result = self::SEVERITY_SOFT;
		}

		return $result;
	}

	/**
	 * @return int
	 * @throws NotImplementedException
	 */
	protected static function getServiceType()
	{
		throw new NotImplementedException;
	}

	/**
	 * @param $serviceId
	 * @return array
	 */
	public static function getRestrictionsList($serviceId)
	{
		if ((int)$serviceId <= 0)
			return array();

		$serviceType = static::getServiceType();

		if (!isset(static::$cachedFields[$serviceType]))
		{
			$result = array();
			$dbRes = ServiceRestrictionTable::getList(array(
				'filter' => array(
					'=SERVICE_TYPE' => $serviceType
				),
				'order' => array('SORT' => 'ASC')
			));

			while($restriction = $dbRes->fetch())
			{
				if (!isset($result[$restriction['SERVICE_ID']]))
					$result[$restriction['SERVICE_ID']] = array();

				$result[$restriction['SERVICE_ID']][$restriction["ID"]] = $restriction;
			}

			static::$cachedFields[$serviceType] = $result;
		}

		if (!isset(static::$cachedFields[$serviceType][$serviceId]))
			return array();

		return static::$cachedFields[$serviceType][$serviceId];
	}

	/**
	 * @param $id
	 * @return array Sites from restrictions.
	 */
	public static function getSitesByServiceId($id)
	{
		if($id <= 0)
			return array();

		$result = array();

		foreach(static::getRestrictionsList($id) as $fields)
		{
			if($fields['CLASS_NAME'] == '\Bitrix\Sale\Delivery\Restrictions\BySite')
			{
				if(!empty($fields["PARAMS"]["SITE_ID"]))
				{
					if(is_array($fields["PARAMS"]["SITE_ID"]))
						$result = $fields["PARAMS"]["SITE_ID"];
					else
						$result = array($fields["PARAMS"]["SITE_ID"]);
				}

				break;
			}
		}

		return $result;
	}

	/**
	 * @param array $servicesIds
	 * @throws NotImplementedException
	 * @throws \Bitrix\Main\ArgumentException
	 * @internal
	 */
	public static function prepareData(array $servicesIds, array $fields = array())
	{
		if(empty($servicesIds))
			return;

		$serviceType = static::getServiceType();
		$cachedServices = is_array(static::$cachedFields[$serviceType]) ? array_keys(static::$cachedFields[$serviceType]) : array();
		$ids = array_diff($servicesIds, $cachedServices);
		$idsForDb = array_diff($ids, array_keys($fields));

		if(!empty($idsForDb))
		{
			$dbRes = ServiceRestrictionTable::getList(array(
				'filter' => array(
					'=SERVICE_ID' => $idsForDb,
					'=SERVICE_TYPE' => $serviceType
				),
				'order' => array('SORT' =>'ASC')
			));

			while($restriction = $dbRes->fetch())
				self::setCache($restriction["SERVICE_ID"], $serviceType, $restriction);
		}

		foreach($fields as $serviceId => $serviceRestrictions)
		{
			if(is_array($serviceRestrictions))
			{
				foreach($serviceRestrictions as $restrId => $restrFields)
					self::setCache($serviceId, $serviceType, $restrFields);
			}
		}

		foreach($ids as $serviceId)
			self::setCache($serviceId, $serviceType);

		/** @var \Bitrix\Sale\Services\Base\Restriction  $className */
		foreach(static::getClassesList() as $className)
			$className::prepareData($ids);
	}

	/**
	 * @param int $serviceId
	 * @param int $serviceType
	 * @param array $fields
	 * @throws ArgumentNullException
	 */
	protected static function setCache($serviceId, $serviceType, array $fields = array())
	{
		if(intval($serviceId) <= 0)
			throw new  ArgumentNullException('serviceId');

		if(!isset(static::$cachedFields[$serviceType]))
			static::$cachedFields[$serviceType] = array();

		if(!isset(static::$cachedFields[$serviceType][$serviceId]))
			static::$cachedFields[$serviceType][$serviceId] = array();

		if(!empty($fields))
			static::$cachedFields[$serviceType][$serviceId][$fields["ID"]] = $fields;
	}

	/**
	 * @param int $serviceId
	 * @param int $serviceType
	 * @return array
	 * @throws ArgumentNullException
	 */
	protected static function getCache($serviceId, $serviceType)
	{
		$result = array();

		if(intval($serviceId) > 0)
		{
			if(isset(static::$cachedFields[$serviceType][$serviceId]))
				$result = static::$cachedFields[$serviceType][$serviceId];
		}
		else
		{
			if(isset(static::$cachedFields[$serviceType]))
				$result = static::$cachedFields[$serviceType];
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws NotImplementedException
	 */
	public static function getBuildInRestrictions()
	{
		throw new NotImplementedException;
	}

	/**
	 * @param array $params
	 * @return \Bitrix\Main\DB\Result
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getList(array $params)
	{
		if (!$params['filter'])
			$params['filter'] = array();

		$params['filter']['SERVICE_TYPE'] = static::getServiceType();

		return ServiceRestrictionTable::getList($params);
	}

	/**
	 * @param $id
	 * @return \Bitrix\Main\DB\Result
	 */
	public static function getById($id)
	{
		return ServiceRestrictionTable::getById($id);
	}
}