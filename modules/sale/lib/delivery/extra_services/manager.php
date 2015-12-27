<?php

namespace Bitrix\Sale\Delivery\ExtraServices;

use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\Internals\ShipmentExtraServiceTable;
use Bitrix\Sale\Result;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Manager
{
	/** @var Base[] */
	protected $items = array();
	protected static $classes = null;

	const RIGHTS_ADMIN_IDX = 0;
	const RIGHTS_MANAGER_IDX = 1;
	const RIGHTS_CLIENT_IDX = 2;

	const STORE_PICKUP_CODE = 'BITRIX_STORE_PICKUP';
	const STORE_PICKUP_CLASS = '\Bitrix\Sale\Delivery\ExtraServices\Store';

	public function __construct($initParam, $currency = "", $values = array(), array $additionalParams = array())
	{
		$itemsParams = array();

		if(!is_array($initParam)) // deliveryId
		{
			if(intval($initParam) <= 0) //new delivery service
				return;

			$itemsParams = self::getByDeliveryId($initParam);

		}
		else // params array.
		{
			$itemsParams = $initParam;
		}

		if(empty($itemsParams))
			return;

		foreach($itemsParams as $params)
		{
			if($currency === "" && !empty($params["CURRENCY"]))
				$currency = $params["CURRENCY"];

			$this->addItem($params, $currency, isset($values[$params["ID"]]) ? $values[$params["ID"]] : null, $additionalParams);
		}
	}

	public static function getClassesList()
	{
		return static::$classes;
	}

	public static function initClassesList()
	{
		if(static::$classes !== null)
			return true;

		$classes = array(
			'\Bitrix\Sale\Delivery\ExtraServices\Enum' => 'lib/delivery/extra_services/enum.php',
			'\Bitrix\Sale\Delivery\ExtraServices\Store' => 'lib/delivery/extra_services/store.php',
			'\Bitrix\Sale\Delivery\ExtraServices\String' => 'lib/delivery/extra_services/string.php',
			'\Bitrix\Sale\Delivery\ExtraServices\Checkbox' => 'lib/delivery/extra_services/checkbox.php'
		);

		\Bitrix\Main\Loader::registerAutoLoadClasses('sale', $classes);

		unset($classes['\Bitrix\Sale\Delivery\ExtraServices\Store']);

		static::$classes = array_keys($classes);

		foreach(GetModuleEvents("sale", "onSaleDeliveryExtraServicesClassesCustom", true) as $arHandler)
		{
			$classes = ExecuteModuleEventEx($arHandler);

			if(!is_array($classes))
				throw new SystemException('Handler of onSaleDeliveryExtraServicesClassesCustom must return Bitrix\Sale\Delivery\ExtraServices\Base[]');

			foreach($classes as $class)
			{
				if(!class_exists($class))
					throw new SystemException('onSaleDeliveryExtraServicesClassesCustom class doesn\'t exist: "'.$class.'"');

				if(in_array($class, static::$classes))
					throw new SystemException('onSaleDeliveryExtraServicesClassesCustom class with such name alredy exists: "'.$class.'"');

				static::$classes[] = $class;
			}
		}

		return true;
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

	public function getTotalCost()
	{
		$result = 0;

		foreach($this->items as $itemId => $item)
			$result += $item->getCost();

		return $result;
	}

	public static function prepareParamsToSave($params)
	{
		if(isset($params["RIGHTS"]))
		{
			$params["RIGHTS"] =
				(isset($params["RIGHTS"][self::RIGHTS_ADMIN_IDX]) ? $params["RIGHTS"][self::RIGHTS_ADMIN_IDX] : "Y").
				(isset($params["RIGHTS"][self::RIGHTS_MANAGER_IDX]) ? $params["RIGHTS"][self::RIGHTS_MANAGER_IDX] : "N").
				(isset($params["RIGHTS"][self::RIGHTS_CLIENT_IDX]) ? $params["RIGHTS"][self::RIGHTS_CLIENT_IDX] : "N");
		}

		if(!isset($params["CLASS_NAME"]) || strlen($params["CLASS_NAME"]) <= 0 || !class_exists($params["CLASS_NAME"]))
			return $params;

		if(!isset($params["ACTIVE"]))
			$params["ACTIVE"] = "N";

		if(isset($params["CLASS_NAME_DISABLED"]))
			unset($params["CLASS_NAME_DISABLED"]);

		if(is_callable($params["CLASS_NAME"]."::prepareParamsToSave"))
			$params = $params["CLASS_NAME"]::prepareParamsToSave($params);

		return $params;
	}

	public static function getAdminParamsControl($className, $name, array $params)
	{
		if(strlen($className) <= 0)
			throw new ArgumentNullException("className");

		if(!is_callable($className.'::getAdminParamsControl'))
			throw new SystemException('"'.$className.'::getAdminParamsControl" does not exist!');

		return $className::getAdminParamsControl($name, $params);
	}

	protected function addItem($params, $currency, $value = null, array $additionalParams = array())
	{
		if(strlen($params["CLASS_NAME"]) <= 0 )
			return false;

		if(!isset($params["CLASS_NAME"]))
			throw new ArgumentNullException("params[\"CLASS_NAME\"]");

		if(!class_exists($params["CLASS_NAME"]))
			throw new SystemException("Class \"".$params["CLASS_NAME"]."\" doesn't exist");

		$item = new $params["CLASS_NAME"]($params["ID"], $params, $currency, $value, $additionalParams);

		if(!($item instanceof Base))
			throw new SystemException("Class ".$params["CLASS_NAME"].' must extends \Bitrix\Sale\Delivery\ExtraServices\Base');

		$this->items[$params["ID"]] =  $item;

		return $params["ID"];
	}

	public function setValues(array $values = array())
	{
		foreach($values as $eSrvId => $value)
		{
			$item = $this->getItem($eSrvId);

			if($item)
				$item->setValue($value);
		}
	}

	public function setOperationCurrency($currency)
	{
		foreach($this->items as $itemId => $item)
			$item->setOperatingCurrency($currency);
	}

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

	public static function saveStoreIdForShipment($shipmentId, $deliveryId, $storeId)
	{
		if(intval($shipmentId) <= 0)
			throw new ArgumentNullException("shipmentId");

		$result = new Result();

		if(intval($deliveryId) <= 0)
			return $result;

		$storeFields = self::getStoresFields($deliveryId);

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

	protected static function getStoresValueId($deliveryId)
	{
		$fields = self::getStoresFields($deliveryId);

		if(isset($fields["ID"]))
			$result = $fields["ID"];
		else
			$result = 0;

		return $result;
	}

	public static function getStoresFields($deliveryId)
	{
		static $cache = array();

		if(isset($cache[$deliveryId]))
		{
			$result = $cache[$deliveryId];
		}
		else
		{
			$result = array();

			$res = Table::getList(array(
				'filter' => array(
					"=DELIVERY_ID" => $deliveryId,
					"=CLASS_NAME" => self::STORE_PICKUP_CLASS,
					"=CODE" => self::STORE_PICKUP_CODE
				)
			));

			if($stores = $res->fetch())
				$result = $stores;

			$cache[$deliveryId] = $result;
		}

		return $result;
	}

	public static function getStoresList($deliveryId)
	{
		$stores = self::getStoresFields($deliveryId);
		return isset($stores["PARAMS"]["STORES"]) ? $stores["PARAMS"]["STORES"] : array();
	}

	public static function saveStores($deliveryId, array $storesList)
	{
		$result = new Result();
		$storesFields = self::getStoresFields($deliveryId);

		if(!empty($storesFields))
		{
			$res = Table::update(
				$storesFields["ID"],
				array(
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
					"PARAMS" => array(
						"STORES" => $storesList
					)
				)
			);
		}

		if(!$res->isSuccess())
			foreach($res->getErrors() as $error)
				$result->addError($error);

		return $result;
	}

	protected static function getByDeliveryId($deliveryId)
	{
		static $hitCache = array();

		if(isset($hitCache[$deliveryId]))
			return $hitCache[$deliveryId];

		$result = array();

		$dbRes = Table::getList(array(
			"order" => array(
				"SORT" =>"ASC",
				"NAME" => "ASC"
			),
			"filter" => array(
				"=DELIVERY_ID" => $deliveryId,
				"=ACTIVE" => "Y",
				"=CLASS_NAME" => self::getClassesList()
			),
			"select" => array("*", "CURRENCY" => "DELIVERY_SERVICE.CURRENCY")
		));

		while($row = $dbRes->fetch())
			$result[$row["ID"]] = $row;

		$hitCache[$deliveryId] = $result;
		return $result;
	}
}

\Bitrix\Sale\Delivery\ExtraServices\Manager::initClassesList();