<?php

namespace Bitrix\Sale\Delivery\ExtraServices;

use Bitrix\Main\Event;
use Bitrix\Sale\Result;
use Bitrix\Main\EventResult;
use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\Services;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\Internals\ShipmentExtraServiceTable;
use Bitrix\Sale\Shipment;

Loc::loadMessages(__FILE__);

class Manager
{
	/** @var Base[] */
	protected $items = array();
	protected static $classes = null;
	protected static $cachedFields = array();

	const RIGHTS_ADMIN_IDX = 0;
	const RIGHTS_MANAGER_IDX = 1;
	const RIGHTS_CLIENT_IDX = 2;

	const STORE_PICKUP_CODE = 'BITRIX_STORE_PICKUP';
	const STORE_PICKUP_CLASS = '\Bitrix\Sale\Delivery\ExtraServices\Store';

	/** @var bool  */
	protected $isClone = false;

	/**
	 * Manager constructor.
	 * @param array $initParam
	 * @param string $currency
	 * @param array $values
	 * @param array $additionalParams
	 */
	public function __construct($initParam, $currency = "", $values = array(), array $additionalParams = array())
	{
		if(!is_array($initParam)) // deliveryId
		{
			if(intval($initParam) <= 0) //new delivery service
				return;

			$itemsParams = self::getExtraServicesList($initParam);

		}
		else // params array.
		{
			$itemsParams = $initParam;
		}

		if(empty($itemsParams))
			return;

		if(!empty($this->items))
			sortByColumn($itemsParams, array("SORT" => SORT_ASC, "NAME" => SORT_ASC), '', null, true);

		foreach($itemsParams as $params)
		{
			if($currency === "" && !empty($params["CURRENCY"]))
				$currency = $params["CURRENCY"];

			$this->addItem($params, $currency, isset($values[$params["ID"]]) ? $values[$params["ID"]] : null, $additionalParams);
		}
	}

	/**
	 * @return array Classes list
	 */
	public static function getClassesList()
	{
		if(static::$classes === null)
			self::initClassesList();

		return static::$classes;
	}

	/**
	 * @return bool|null
	 * @throws SystemException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function initClassesList()
	{
		if(static::$classes !== null)
			return true;

		$classes = array(
			'\Bitrix\Sale\Delivery\ExtraServices\Enum' => 'lib/delivery/extra_services/enum.php',
			'\Bitrix\Sale\Delivery\ExtraServices\Store' => 'lib/delivery/extra_services/store.php',
			'\Bitrix\Sale\Delivery\ExtraServices\String' => 'lib/delivery/extra_services/string.php',
			'\Bitrix\Sale\Delivery\ExtraServices\Quantity' => 'lib/delivery/extra_services/quantity.php',
			'\Bitrix\Sale\Delivery\ExtraServices\Checkbox' => 'lib/delivery/extra_services/checkbox.php'
		);

		\Bitrix\Main\Loader::registerAutoLoadClasses('sale', $classes);
		Services\Manager::getHandlersList();
		unset($classes['\Bitrix\Sale\Delivery\ExtraServices\Store']);
		$event = new Event('sale', 'onSaleDeliveryExtraServicesClassNamesBuildList');
		$event->send();
		$resultList = $event->getResults();

		if (is_array($resultList) && !empty($resultList))
		{
			$customClasses = array();

			foreach ($resultList as $eventResult)
			{
				/** @var  EventResult $eventResult*/
				if ($eventResult->getType() != EventResult::SUCCESS)
					continue;

				$params = $eventResult->getParameters();

				if(!empty($params) && is_array($params))
					$customClasses = array_merge($customClasses, $params);
			}

			if(!empty($customClasses))
			{
				\Bitrix\Main\Loader::registerAutoLoadClasses(null, $customClasses);
				$classes = array_merge($customClasses, $classes);
			}
		}

		static::$classes = array_merge(array_keys($classes));

		return static::$classes;
	}

	/**
	 * @return Base[]
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * @return Base
	 */
	public function getItem($id)
	{
		return (isset($this->items[$id]) ? $this->items[$id] : null);
	}

	/**
	 * @param $code
	 * @return Base|null
	 */
	public function getItemByCode($code)
	{
		foreach($this->items as $item)
			if($item->getCode() == $code)
				return $item;

		return null;
	}

	/**
	 * @param Shipment|null $shipment
	 * @return float
	 */
	public function getTotalCostShipment(Shipment $shipment = null)
	{
		$result = 0;

		foreach($this->items as $itemId => $item)
			$result += $item->getCostShipment($shipment);

		return $result;
	}

	/**
	 * Prepares fields for saving
	 * @param $params
	 * @return mixed
	 */
	public static function prepareParamsToSave($params)
	{
		if(isset($params["RIGHTS"]))
		{
			$params["RIGHTS"] =
				(isset($params["RIGHTS"][self::RIGHTS_ADMIN_IDX]) ? $params["RIGHTS"][self::RIGHTS_ADMIN_IDX] : "Y").
				(isset($params["RIGHTS"][self::RIGHTS_MANAGER_IDX]) ? $params["RIGHTS"][self::RIGHTS_MANAGER_IDX] : "Y").
				(isset($params["RIGHTS"][self::RIGHTS_CLIENT_IDX]) ? $params["RIGHTS"][self::RIGHTS_CLIENT_IDX] : "Y");
		}

		if(!isset($params["CLASS_NAME"]) || strlen($params["CLASS_NAME"]) <= 0 || !class_exists($params["CLASS_NAME"]))
			return $params;

		if(!isset($params["ACTIVE"]))
			$params["ACTIVE"] = "Y";

		if(isset($params["CLASS_NAME_DISABLED"]))
			unset($params["CLASS_NAME_DISABLED"]);

		if(is_callable($params["CLASS_NAME"]."::prepareParamsToSave"))
			$params = $params["CLASS_NAME"]::prepareParamsToSave($params);

		return $params;
	}

	/**
	 * @param string $className
	 * @param string $name
	 * @param array $params
	 * @return string Html for extra service administration
	 * @throws ArgumentNullException
	 * @throws SystemException
	 */
	public static function getAdminParamsControl($className, $name, array $params)
	{
		if(strlen($className) <= 0)
			throw new ArgumentNullException("className");

		if(!is_callable($className.'::getAdminParamsControl'))
			throw new SystemException('"'.$className.'::getAdminParamsControl" does not exist!');

		return $className::getAdminParamsControl($name, $params);
	}

	/**
	 * @param array $params
	 * @param string $currency
	 * @param mixed $value
	 * @param array $additionalParams
	 * @return bool
	 * @throws ArgumentNullException
	 * @throws SystemException
	 */
	public function addItem($params, $currency, $value = null, array $additionalParams = array())
	{
		if(strlen($params["CLASS_NAME"]) <= 0 )
			return false;

		if(!isset($params["CLASS_NAME"]))
			throw new ArgumentNullException("params[\"CLASS_NAME\"]");

		if(!class_exists($params["CLASS_NAME"]))
			return false;

		$item = new $params["CLASS_NAME"]($params["ID"], $params, $currency, $value, $additionalParams);

		if(!($item instanceof Base))
			throw new SystemException("Class ".$params["CLASS_NAME"].' must extends \Bitrix\Sale\Delivery\ExtraServices\Base');

		$this->items[$params["ID"]] =  $item;

		return $params["ID"];
	}

	/**
	 * @param array $values
	 */
	public function setValues(array $values = array())
	{
		foreach($values as $eSrvId => $value)
		{
			$item = $this->getItem($eSrvId);

			if($item)
				$item->setValue($value);
		}
	}

	/**
	 * @param string $currency
	 */

	public function setOperationCurrency($currency)
	{
		foreach($this->items as $itemId => $item)
			$item->setOperatingCurrency($currency);
	}

	/**
	 * @param int $shipmentId
	 * @param int $deliveryId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getValuesForShipment($shipmentId, $deliveryId)
	{
		$result = array();

		if(intval($shipmentId) > 0 && intval($deliveryId) > 0)
		{
			$dbRes = ShipmentExtraServiceTable::getList(array(
				'filter' => array(
					'=SHIPMENT_ID' => $shipmentId,
					'!=ID' => self::getStoresValueId($deliveryId)
				)
			));

			while($row = $dbRes->fetch())
				$result[$row["EXTRA_SERVICE_ID"]] = $row["VALUE"];
		}

		return $result;
	}

	/**
	 * @param int $shipmentId
	 * @param array $extraServices
	 * @return Result
	 * @throws ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 */
	public static function saveValuesForShipment($shipmentId, $extraServices)
	{
		$result = new Result();

		if(intval($shipmentId) <= 0)
			throw new ArgumentNullException("shipmentId");

		$exist = array();

		$dbRes = ShipmentExtraServiceTable::getList(array(
			'filter' => array(
				'=SHIPMENT_ID' => $shipmentId
			)
		));

		while($row = $dbRes->fetch())
			$exist[$row["EXTRA_SERVICE_ID"]] = $row["ID"];

		if(is_array($extraServices))
		{
			foreach($extraServices as $extraServiceId => $value)
			{
				if(array_key_exists($extraServiceId, $exist))
				{
					$res = ShipmentExtraServiceTable::update($exist[$extraServiceId], array("VALUE" => $value));
				}
				else
				{
					$res = ShipmentExtraServiceTable::add(array(
						"EXTRA_SERVICE_ID" => $extraServiceId,
						"SHIPMENT_ID" => $shipmentId,
						"VALUE" => $value
					));
				}

				if($res->isSuccess())
					unset($exist[$extraServiceId]);
				else
					foreach($res->getErrors() as $error)
						$result->addError($error);

			}
		}

		foreach($exist as $extraServiceId => $value)
		{
			$res = ShipmentExtraServiceTable::delete($extraServiceId);

			if(!$res->isSuccess())
				foreach($res->getErrors() as $error)
					$result->addError($error);
		}

		return $result;
	}

	/**
	 * @param int $shipmentId
	 * @param int $deliveryId
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getStoreIdForShipment($shipmentId, $deliveryId)
	{
		$result = 0;

		if(intval($shipmentId) > 0 && intval($deliveryId) > 0)
		{
			$storeFields = self::getStoresFields($deliveryId);

			if(!empty($storeFields))
			{
				$dbRes = ShipmentExtraServiceTable::getList(array(
					'filter' => array(
						'=SHIPMENT_ID' => $shipmentId,
						'=EXTRA_SERVICE_ID' => $storeFields['ID']
					)
				));

				if($row = $dbRes->fetch())
					$result = $row["VALUE"];
			}
		}

		return $result;
	}

	/**
	 * @param int $shipmentId
	 * @param int $deliveryId
	 * @param int $storeId
	 * @return Result
	 * @throws ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 */
	public static function saveStoreIdForShipment($shipmentId, $deliveryId, $storeId)
	{
		if(intval($shipmentId) <= 0)
			throw new ArgumentNullException("shipmentId");

		$result = new Result();

		if(intval($deliveryId) <= 0)
			return $result;

		$storeFields = self::getStoresFields($deliveryId, false);

		if(isset($storeFields['ID']))
		{
			$dbRes = ShipmentExtraServiceTable::getList(array(
				'filter' => array(
					'=SHIPMENT_ID' => $shipmentId,
					'=EXTRA_SERVICE_ID' => $storeFields['ID']
				)
			));

			$storeRowId = 0;

			if($row = $dbRes->fetch())
				$storeRowId = $row["ID"];

			if($storeRowId > 0)
			{
				$res = ShipmentExtraServiceTable::update($storeRowId, array("VALUE" => $storeId));
			}
			else
			{
				$res = ShipmentExtraServiceTable::add(array(
					"EXTRA_SERVICE_ID" => $storeFields['ID'],
					"SHIPMENT_ID" => $shipmentId,
					"VALUE" => $storeId
				));
			}

			if(!$res->isSuccess())
				foreach($res->getErrors() as $error)
					$result->addError($error);
		}

		return $result;
	}

	/**
	 * @param int $deliveryId
	 * @return int
	 */
	protected static function getStoresValueId($deliveryId)
	{
		$fields = self::getStoresFields($deliveryId);

		if(isset($fields["ID"]))
			$result = $fields["ID"];
		else
			$result = 0;

		return $result;
	}

	/**
	 * @param int $deliveryId
	 * @param bool $onlyActive
	 * @return array
	 */
	public static function getStoresFields($deliveryId, $onlyActive = true)
	{
		if(intval($deliveryId) <= 0)
			return array();

		$result = self::getExtraServicesList($deliveryId, true);

		if($onlyActive && $result['ACTIVE'] != 'Y')
			return array();

		return $result;
	}

	/**
	 * @param int $deliveryId
	 * @return array
	 */
	public static function getStoresList($deliveryId)
	{
		$stores = self::getStoresFields($deliveryId);
		return isset($stores["PARAMS"]["STORES"]) ? $stores["PARAMS"]["STORES"] : array();
	}

	/**
	 * @param int $deliveryId
	 * @return Result
	 * @throws \Exception
	 */
	public static function deleteStores($deliveryId)
	{
		$storesFields = self::getStoresFields($deliveryId, false);

		if(empty($storesFields['ID']))
			return new Result();

		$result = Table::delete($storesFields['ID']);

		if($result->isSuccess())
			unset(static::$cachedFields[$deliveryId][$storesFields['ID']]);

		return $result;
	}

	/**
	 * @param int $deliveryId
	 * @return Result
	 * @throws \Exception
	 */
	public static function setStoresUnActive($deliveryId)
	{
		if(intval($deliveryId) <= 0)
			return new Result();

		$storesFields = self::getStoresFields($deliveryId);

		if(empty($storesFields['ID']))
			return new Result();

		$result = Table::update(
			$storesFields['ID'],
			array(
				"ACTIVE" => "N"
			)
		);

		if($result->isSuccess())
			static::$cachedFields[$deliveryId][$storesFields['ID']]["ACTIVE"] = "N";

		return $result;
	}

	/**
	 * @param int $deliveryId
	 * @param array $storesList
	 * @return Result
	 * @throws \Exception
	 */
	public static function saveStores($deliveryId, array $storesList)
	{
		$result = new Result();
		$storesFields = self::getStoresFields($deliveryId, false);

		if(!empty($storesFields['ID']))
		{
			$res = Table::update(
				$storesFields["ID"],
				array(
					"ACTIVE" => "Y",
					"PARAMS" => array(
						"STORES" => $storesList
					)
				)
			);
		}
		else
		{
			$res = Table::add(
				array(
					"CODE" => self::STORE_PICKUP_CODE,
					"NAME" => Loc::getMessage("DELIVERY_SERVICE_MANAGER_ES_NAME"),
					"DESCRIPTION" => Loc::getMessage("DELIVERY_SERVICE_MANAGER_ES_DESCRIPTION"),
					"CLASS_NAME" => self::STORE_PICKUP_CLASS,
					"DELIVERY_ID" => $deliveryId,
					"RIGHTS" => "YYY",
					"ACTIVE" => "Y",
					"PARAMS" => array(
						"STORES" => $storesList
					)
				)
			);
		}

		if(!$res->isSuccess())
			$result->addErrors($res->getErrors());

		return $result;
	}

	/**
	 * @param int $deliveryId
	 * @return array
	 * @throws SystemException
	 */
	public static function getExtraServicesList($deliveryId, $stores = false)
	{
		if(intval($deliveryId) <= 0)
			return array();

		if(!isset(static::$cachedFields[$deliveryId]))
		{
			$srv = Services\Manager::getById($deliveryId);

			if(!empty($srv['PARENT_ID']))
			{
				self::prepareData(array($deliveryId, $srv['PARENT_ID']));
				static::$cachedFields[$deliveryId] = static::$cachedFields[$deliveryId] + static::$cachedFields[$srv['PARENT_ID']];
			}
			else
			{
				self::prepareData(array($deliveryId));
			}
		}

		$result = array();

		foreach(static::$cachedFields[$deliveryId] as $id => $es)
		{
			if($es['CLASS_NAME'] == self::STORE_PICKUP_CLASS)
			{
				if($stores)
					return $es;

				continue;
			}

			if(!$stores)
				$result[$id] = $es;
		}

		return $result;
	}

	/**
	 * @param array $servicesIds
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @internal
	 */
	public static function prepareData(array $servicesIds)
	{
		if(empty($servicesIds))
			return;

		foreach($servicesIds as $id)
		{
			$srv = Services\Manager::getById($id);

			if(!empty($srv['PARENT_ID']) && !in_array($id, $servicesIds))
				$servicesIds[] = $id;
		}

		$ids = array_diff($servicesIds, array_keys(static::$cachedFields));

		$dbRes = Table::getList(array(
			'filter' => array(
				'=DELIVERY_ID' => $ids,
				array(
					"LOGIC" => "OR",
					"=ACTIVE" => "Y",
					"=CLASS_NAME" => self::STORE_PICKUP_CLASS
				)
			),
			"order" => array(
				"SORT" =>"ASC",
				"NAME" => "ASC"
			),
			"select" => array("*", "CURRENCY" => "DELIVERY_SERVICE.CURRENCY")
		));

		while($es = $dbRes->fetch())
		{
			if(!isset(static::$cachedFields[$es['DELIVERY_ID']]))
				static::$cachedFields[$es['DELIVERY_ID']] = array();

			static::$cachedFields[$es['DELIVERY_ID']][$es["ID"]] = $es;
		}

		foreach($ids as $deliveryId)
			if(!isset(static::$cachedFields[$deliveryId]))
				static::$cachedFields[$deliveryId] = array();
	}

	/**
	 * @internal
	 * @param \SplObjectStorage $cloneEntity
	 *
	 * @return Manager
	 */
	public function createClone(\SplObjectStorage $cloneEntity)
	{
		if ($this->isClone() && $cloneEntity->contains($this))
		{
			return $cloneEntity[$this];
		}

		$extraServiceClone = clone $this;
		$extraServiceClone->isClone = true;

		if (!$cloneEntity->contains($this))
		{
			$cloneEntity[$this] = $extraServiceClone;
		}

		return $extraServiceClone;
	}

	/**
	 * @return bool
	 */
	public function isClone()
	{
		return $this->isClone;
	}

	/**
	 * @return float total cost
	 * @deprecated
	 * @use \Bitrix\Sale\Delivery\ExtraServices\Manager::getTotalCostShipment()
	 */
	public function getTotalCost()
	{
		$result = 0;

		foreach($this->items as $itemId => $item)
			$result += $item->getCost();

		return $result;
	}
}

\Bitrix\Sale\Delivery\ExtraServices\Manager::initClassesList();