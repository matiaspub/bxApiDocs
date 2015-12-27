<?php
namespace Bitrix\Sale\Delivery\Services;

use Bitrix\Sale\Delivery\ExtraServices;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\Delivery\Restrictions;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Shipment;

/**
 * Class Manager
 * Fabric for Services
 * @package Bitrix\Sale\Delivery
 */
class Manager
{
	protected static $handlerClassNames = null;
	protected static $restrictionClassNames = null;
	protected static $cachedServicesFields = array();
	protected static $cachedRestrictionsFields = array();

	const SKIP_PROFILE_PARENT_CHECK = 0;
	const SKIP_CHILDREN_PARENT_CHECK = 1;
	const SKIP_RESTRICTIONS_CHECK = 2;
	const SKIP_RESTRICTIONS_CLASSES = 3;

	/**
	 * @param $className
	 * @return Restrictions\Base
	 * @throws SystemException
	 */
	public static function getRestrictionObject($className)
	{
		if(!class_exists($className))
			throw new SystemException("Can't find class: ".$className);

		static $cache = array();

		if(isset($cache[$className]))
			return $cache[$className];

		$restriction = new $className;

		if(!($restriction instanceof Restrictions\Base))
			throw new SystemException('Object must be the instance of Bitrix\Sale\Delivery\Restrictions\Base');

		$cache[$className] = $restriction;
		return  $restriction;
	}

	/**
	 * @param int $deliveryId
	 * @param Shipment $shipment
	 * @param array $restrictionsClassesToSkip
	 * @return bool
	 * @throws SystemException
	 */
	public static function checkServiceRestrictions($deliveryId, Shipment $shipment, $restrictionsClassesToSkip = array())
	{
		$result = true;
		self::initRestrictionClassNames();
		$restrictions = self::getRestrictionsByDeliveryId($deliveryId);

		foreach($restrictions as $rstrParams)
		{
			if(in_array($rstrParams['CLASS_NAME'], $restrictionsClassesToSkip))
				continue;

			$restriction = static::getRestrictionObject($rstrParams['CLASS_NAME']);

			if(!$rstrParams['PARAMS'])
				$rstrParams['PARAMS'] = array();

			if(!($restriction->checkByShipment($shipment, $rstrParams['PARAMS'], $deliveryId)))
			{
				$result = false;
				break;
			}
		}

		return $result;
	}

	/**
	 * @param $deliveryId
	 * @param Shipment | mixed $checkParams
	 * @param $restrictionClassName
	 * @return bool
	 * @throws SystemException
	 */
	public static function checkServiceRestriction($deliveryId, $checkParams, $restrictionClassName)
	{
		if($deliveryId <= 0)
			throw new ArgumentNullException("deliveryId");

		$result = true;
		self::initRestrictionClassNames();
		$restrictions = self::getRestrictionsByDeliveryId($deliveryId);
		$restriction = static::getRestrictionObject($restrictionClassName);

		$itemId = 0;
		foreach($restrictions as $id => $restr)
		{
			if($restr["CLASS_NAME"] != $restrictionClassName)
				continue;

			$itemId = $id;
			break;
		}

		if($itemId == 0)
			return true;

		if(isset($restrictions[$itemId]['PARAMS']) && is_array($restrictions[$itemId]['PARAMS']))
			$params = $restrictions[$itemId]['PARAMS'];
		else
			$params = array();

		if($checkParams instanceof Shipment)
		{
			if(!($restriction->checkByShipment($checkParams, $params, $deliveryId)))
				$result = false;
		}
		else
		{
			if(!($restriction->check($checkParams, $params, $deliveryId)))
				$result = false;
		}

		return $result;
	}

	/**
	 * @param $id
	 * @return array
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected static function getParams($id)
	{
		$id = intval($id);

		if($id <= 0)
			throw new SystemException("id");

		if(isset(self::$cachedServicesFields[$id]))
			return self::$cachedServicesFields[$id];

		$result = array();

		$resSrvParams = Table::getList(array(
			'order' => array('SORT' =>'ASC', 'NAME' => 'ASC'),
			'filter' => array("ID" =>  $id)
		));

		if($srvParams = $resSrvParams->fetch())
			$result = self::$cachedServicesFields[$srvParams["ID"]] = $srvParams;

		return $result;
	}

	/**
	 * Returns array of delivery services objects
	 * wich are compatible with order params
	 * @param Shipment $shipment.
	 * @return Base[]
	 */
	public static function getServicesForShipment(Shipment $shipment)
	{
		$result = array();
		$services = self::getActive();

		foreach($services as $srvParams)
		{
			if(is_callable($srvParams["CLASS_NAME"]."::canHasProfiles") && $srvParams["CLASS_NAME"]::canHasProfiles())
				continue;

			if(is_callable($srvParams["CLASS_NAME"]."::canHasChildren") && $srvParams["CLASS_NAME"]::canHasChildren())
				continue;

			if(!static::checkServiceRestrictions($srvParams["ID"], $shipment))
				continue;

			$service = self::createServiceObject($srvParams);

			if(!$service)
				continue;

			if(!$service->isCompatible($shipment))
				continue;

			if($shipment->getCurrency() != $service->getCurrency())
			{
				$service->getExtraServices()->setOperationCurrency(
					$shipment->getCurrency()
				);
			}

			$result[$srvParams["ID"]] = $service;
		}

		return $result;
	}

	public static function isExistService($deliveryServiceId)
	{
		if(intval($deliveryServiceId) <= 0)
			return false;

		$srv = self::getParams($deliveryServiceId);
		return !empty($srv);
	}

	/**
	 * prepares restriction data
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getActive()
	{
		static $isDataPrepared = false;
		static $activeIdxs = array();

		if($isDataPrepared)
			return array_intersect_key(self::$cachedServicesFields, $activeIdxs);

		$result = array();

		$dbRes = Table::getList(array(
			'order' => array('SORT' =>'ASC', 'NAME' => 'ASC'),
			'filter' => array(
				"ACTIVE" =>  "Y"
			)
		));

		$canHasProfiles = array();
		$profilesParentsIds = array();

		while($service = $dbRes->fetch())
		{
			if (is_callable($service['CLASS_NAME'].'::canHasProfiles') && $service['CLASS_NAME']::canHasProfiles())
				$canHasProfiles[$service["ID"]] = true;

			if($service["PARENT_ID"] != 0)
				$profilesParentsIds[$service["PARENT_ID"]] = true;

			$result[$service["ID"]] = $service;
			$activeIdxs[] = $service["ID"];
		}

		foreach(array_diff_key($canHasProfiles, $profilesParentsIds) as $id => $tmp)
			unset($result[$id]);

		self::$cachedServicesFields = $result;
		self::prepareRestrictionsData();
		return $result;
	}

	// let's prepare data for restrictions
	protected static function prepareRestrictionsData()
	{
		$ids = array_keys(
			array_diff_key(self::$cachedServicesFields, self::$cachedRestrictionsFields)
		);

		$dbRstrRes = \Bitrix\Sale\Delivery\Restrictions\Table::getList(array(
			'filter' => array(
				'=DELIVERY_ID' => $ids
			),
			'order' => array('SORT' =>'ASC')
		));

		while($restriction = $dbRstrRes->fetch())
		{
			if(!isset(self::$cachedRestrictionsFields[$restriction["DELIVERY_ID"]]))
				self::$cachedRestrictionsFields[$restriction["DELIVERY_ID"]] = array();

			self::$cachedRestrictionsFields[$restriction["DELIVERY_ID"]][$restriction["ID"]] = $restriction;
		}

		foreach($ids as $deliveryId)
		{
			if(!isset(self::$cachedRestrictionsFields[$deliveryId]))
			{
				self::$cachedRestrictionsFields[$deliveryId] = array();
			}
		}

		foreach(self::getRestrictionClassNames() as $className)
		{
			$restriction = static::getRestrictionObject($className);

			if(is_callable(array($restriction, "prepareData")))
				$restriction->prepareData(array_keys(self::$cachedServicesFields));
		}
	}

	/**
	 * @param Shipment $shipment
	 * @param array $skipChecks self::SKIP_CHILDREN_PARENT_CHECK || self::SKIP_PROFILE_PARENT_CHECK || self::SKIP_RESTRICTIONS_CHECK || self::SKIP_RESTRICTIONS_CLASSES
	 * @param bool $getAll
	 * @return array
	 */
	public static function getServicesBriefsForShipment(Shipment $shipment = null, array $skipChecks = array(), $getAll = false)
	{
		$result = array();

		if(empty($skipChecks))
		{
			$skipChecks = array(
				self::SKIP_CHILDREN_PARENT_CHECK,
				self::SKIP_PROFILE_PARENT_CHECK,
				self::SKIP_RESTRICTIONS_CHECK
			);
		}

		$restrictionsClassesToSkip = array();

		if(isset($skipChecks[self::SKIP_RESTRICTIONS_CLASSES]) && is_array($skipChecks[self::SKIP_RESTRICTIONS_CLASSES]))
			$restrictionsClassesToSkip = $skipChecks[self::SKIP_RESTRICTIONS_CLASSES];

		$services = self::getActive();

		foreach($services as $srvParams)
		{
			$srvParams["RESTRICTED"] = false;

			if(!in_array(self::SKIP_PROFILE_PARENT_CHECK, $skipChecks))
				if(is_callable($srvParams["CLASS_NAME"]."::canHasProfiles") && $srvParams["CLASS_NAME"]::canHasProfiles())
					continue;

			if(!in_array(self::SKIP_CHILDREN_PARENT_CHECK, $skipChecks))
				if(is_callable($srvParams["CLASS_NAME"]."::canHasChildren") && $srvParams["CLASS_NAME"]::canHasChildren())
					continue;

			if(!in_array(self::SKIP_RESTRICTIONS_CHECK, $skipChecks))
			{
				if($shipment!= null && !static::checkServiceRestrictions($srvParams["ID"], $shipment, $restrictionsClassesToSkip))
				{
					if($getAll)
						$srvParams["RESTRICTED"] = true;
					else
						continue;
				}
			}

			$result[$srvParams["ID"]] = $srvParams;
		}

		return $result;
	}

	/**
	 * @param array $srvParams Base params.
	 * @return Base
	 * @throws ArgumentNullException
	 * @throws SystemException
	 */
	public static function createServiceObject(array $srvParams)
	{
		self::initHandlerClassNames();

		if(!isset($srvParams["PARENT_ID"]))
			$srvParams["PARENT_ID"] = 0;

		if(!class_exists($srvParams['CLASS_NAME']))
			throw new SystemException("Can't create delivery object. Class \"".$srvParams['CLASS_NAME']."\" does not exist.");

		$service = new $srvParams['CLASS_NAME']($srvParams);

		if(!($service instanceof Base))
			throw new SystemException("Can't create delivery object. Class ".$srvParams['CLASS_NAME'].' is not the instance of Bitrix\Sale\DeliveryService');

		return $service;
	}

	/**
	 * @param $deliveryId
	 * @return Base
	 * @throws ArgumentNullException
	 * @throws SystemException
	 */
	public static function getService($deliveryId)
	{
		if(intval($deliveryId) <= 0 )
			throw new ArgumentNullException("deliveryId");

		$srvParams = self::getParams($deliveryId);

		if(empty($srvParams))
			return null;

		return self::createServiceObject($srvParams);
	}

	public static function getServiceByCode($serviceCode)
	{
		if(strlen($serviceCode) <= 0 )
			throw new ArgumentNullException("serviceCode");

		$resSrvParams = Table::getList(array(
			'filter' => array('=CODE' => $serviceCode)
		));

		if(!($srvParams = $resSrvParams->fetch()))
			throw new SystemException("Can't get delivery service data with code: \"".$serviceCode."\"");

		return self::createServiceObject($srvParams);
	}

	protected static function initHandlerClassNames()
	{
		if(self::$handlerClassNames !== null)
			return true;

		$result = array(
			'\Bitrix\Sale\Delivery\Services\Automatic' => 'lib/delivery/services/automatic.php',
			'\Bitrix\Sale\Delivery\Services\AutomaticProfile' => 'lib/delivery/services/automatic_profile.php',
			'\Bitrix\Sale\Delivery\Services\Configurable' => 'lib/delivery/services/configurable.php',
			'\Bitrix\Sale\Delivery\Services\Group' => 'lib/delivery/services/group.php'
		);

		foreach(GetModuleEvents("sale", "onSaleDeliveryHandlersClassNamesBuildList", true) as $handler)
		{
			$classes = ExecuteModuleEventEx($handler);

			if(!is_array($classes))
				throw new SystemException('Event services onSaleDeliveryHandlersClassNamesBuildList must return an array!)');

			if(!empty($classes))
				$result = array_merge($result, $classes);
		}

		self::$handlerClassNames = array_keys($result);
		\Bitrix\Main\Loader::registerAutoLoadClasses('sale', $result);

		return true;
	}

	public static function getHandlersClassNames()
	{
		if(self::$handlerClassNames === null)
			self::initHandlerClassNames();

		return self::$handlerClassNames;
	}

	protected static function initRestrictionClassNames()
	{
		if(self::$restrictionClassNames !== null)
			return true;

		$result = array(
			'\Bitrix\Sale\Delivery\Restrictions\BySite' => 'lib/delivery/restrictions/bysite.php',
			'\Bitrix\Sale\Delivery\Restrictions\ByPrice' => 'lib/delivery/restrictions/byprice.php',
			'\Bitrix\Sale\Delivery\Restrictions\ByWeight' => 'lib/delivery/restrictions/byweight.php',
			'\Bitrix\Sale\Delivery\Restrictions\ByMaxSize' => 'lib/delivery/restrictions/bymaxsize.php',
			'\Bitrix\Sale\Delivery\Restrictions\ByLocation' => 'lib/delivery/restrictions/bylocation.php',
			'\Bitrix\Sale\Delivery\Restrictions\ByPaySystem' => 'lib/delivery/restrictions/bypaysystem.php',
			'\Bitrix\Sale\Delivery\Restrictions\ByDimensions' => 'lib/delivery/restrictions/bydimensions.php',
			'\Bitrix\Sale\Delivery\Restrictions\ByPublicMode' => 'lib/delivery/restrictions/bypublicmode.php'
		);

		foreach(GetModuleEvents("sale", "onSaleDeliveryRestrictionsClassNamesBuildList", true) as $handler)
		{
			$classes = ExecuteModuleEventEx($handler);

			if(!is_array($classes))
				throw new SystemException('Event services onSaleDeliveryRestrictionsClassNamesBuildList must return an array!)');

			if(!empty($classes))
				$result = array_merge($result, $classes);
		}

		\Bitrix\Main\Loader::registerAutoLoadClasses('sale', $result);
		self::$restrictionClassNames = array_keys($result);
		return true;
	}

	public static function getRestrictionClassNames()
	{
		if(self::$restrictionClassNames === null)
			self::initRestrictionClassNames();

		return self::$restrictionClassNames;
	}

	public static function calculate(Shipment $shipment)
	{
		$delivery = self::getService($shipment->getDeliveryId());
		return $delivery->calculate($shipment);
	}

	public static function getGroupId($name)
	{
		$result = 0;

		$res = Table::getList( array(
			'select' => array("ID"),
			'filter' => array(
				"=NAME" => $name,
				"=CLASS_NAME" => '\Bitrix\Sale\Delivery\Services\Group'
			)
		));

		if($group = $res->fetch())
		{
			$result = $group["ID"];
		}
		else
		{
			$res = Table::add(array(
				"NAME" => $name,
				"CLASS_NAME" => '\Bitrix\Sale\Delivery\Services\Group',
				"ACTIVE" => "Y"
			));

			if($res->isSuccess())
				$result = $res->getId();
			else
				throw new SystemException(implode("<br>\n",$res->getErrorMessages()));
		}

		return $result;
	}

	public static function getByParentId($parentId)
	{
		static $hitCache = array();

		if(isset($hitCache[$parentId]))
			return $hitCache[$parentId];

		$result = array();

		$dbRes = \Bitrix\Sale\Delivery\Services\Table::getList(array(
			'filter' => array(
				"PARENT_ID" => $parentId
			)
		));

		while($child = $dbRes->fetch())
			$result[$child["ID"]] = $child;

		$hitCache[$parentId] = $result;
		return $result;
	}

	public static function getLocationConnectorEntityName()
	{
		return	'Bitrix\Sale\Delivery\DeliveryLocation';
	}

	public static function getRestrictionsByDeliveryId($deliveryId)
	{
		if(isset(self::$cachedRestrictionsFields[$deliveryId]))
			return self::$cachedRestrictionsFields[$deliveryId];

		$result = array();

		$dbRstrRes = \Bitrix\Sale\Delivery\Restrictions\Table::getList(array(
			'filter' => array(
				'=DELIVERY_ID' => $deliveryId,
			),
			'order' => array('SORT' =>'ASC')
		));

		while($restriction = $dbRstrRes->fetch())
			$result[$restriction["ID"]] = $restriction;

		self::$cachedRestrictionsFields[$deliveryId] = $result;
		return $result;
	}

	public static function saveRestriction($deliveryId, $className, $params = false)
	{
		$res = Restrictions\Table::getList(array(
			'filter' => array(
				"=DELIVERY_ID" => $deliveryId,
				"=CLASS_NAME" => $className
			),
			'select' => array("ID")
		));

		if($restriction = $res->fetch())
		{
			if($params !== false)
			{
				$res = Restrictions\Table::update(
					$restriction["ID"],
					array(
						"DELIVERY_ID" => $deliveryId,
						"CLASS_NAME" => $className,
						"PARAMS" => $params
				));
			}
		}
		else
		{
			$res = Restrictions\Table::add(array(
				"DELIVERY_ID" => $deliveryId,
				"CLASS_NAME" => $className,
				"PARAMS" => is_array($params) ? $params : array()
			));
		}

		return $res;
	}

	/**
	 * @param $serviceId
	 * @param array $fields
	 * @return bool
	 */
	public static function onAfterAdd($serviceId, array $fields = array())
	{
		$result = true;

		if(!empty($fields["CLASS_NAME"]) && is_callable($fields["CLASS_NAME"]."::onAfterAdd"))
			$result = $fields["CLASS_NAME"]::onAfterAdd($serviceId, $fields);

		return $result;
	}
}