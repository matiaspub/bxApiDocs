<?php

namespace Bitrix\Sale\Delivery\ExtraServices;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\Internals\Input;
use Bitrix\Sale\Shipment;

abstract class Base
{
	protected $id;
	protected $code;
	protected $name = "";
	protected $description = "";
	protected $className = __CLASS__;
	protected $params = array();
	protected $rights = array (
		Manager::RIGHTS_ADMIN_IDX => "N",
		Manager::RIGHTS_MANAGER_IDX => "N",
		Manager::RIGHTS_CLIENT_IDX => "N"
	);
	protected $deliveryId = 0;
	protected $initial = "";
	protected $active = false;
	protected $sort = 100;
	protected $value = null;
	protected $currency = "";
	protected $operatingCurrency = "";

	abstract public function getClassTitle();

	public function __construct($id, array $initParams, $currency, $value = null, array $additionalParams = array())
	{
		if(strlen($id) <= 0)
			throw new ArgumentNullException('id');

		$this->id = $id;
		$this->code = $initParams["CODE"];
		$this->name = $initParams["NAME"];
		$this->description = $initParams["DESCRIPTION"];
		$this->className = $initParams["CLASS_NAME"];
		$this->params = $initParams["PARAMS"];
		$this->rights = $initParams["RIGHTS"];
		$this->deliveryId = $initParams["DELIVERY_ID"];
		$this->initial = isset($initParams["INIT_VALUE"]) ? $initParams["INIT_VALUE"] : null;
		$this->active = $initParams["ACTIVE"];
		$this->sort = $initParams["SORT"];

		$this->currency = $this->operatingCurrency = $currency;

		if($value !== null)
			$this->setValue($value);
		elseif($this->initial !== null)
			$this->setValue($this->initial);
	}

	public function setValue($value)
	{
		$this->value = $value;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function getEditControl($prefix = "", $value = false)
	{
		if(strlen($prefix) > 0)
			$name = $prefix;
		else
			$name = $this->id;

		if(!$value)
			$value = $this->value;

		return Input\Manager::getEditHtml($name, $this->params, $value);
	}

	public function getViewControl()
	{
		return Input\Manager::getViewHtml($this->params, $this->value);
	}

	/**
	 * @return float
	 * @deprecated
	 * use \Bitrix\Sale\Delivery\ExtraServices\Base::getPriceShipment()
	 */

	public function getPrice()
	{
		$result = false;

		if(isset($this->params["PRICE"]))
			$result = $this->convertToOperatingCurrency($this->params["PRICE"]);

		return $result;
	}

	protected function convertToOtherCurrency($value, $currency)
	{
		$result = floatval($value);

		if($result <= 0)
			return $value;

		if(strlen($this->currency) <= 0 || strlen($currency) <= 0)
			return $value;

		if($this->currency == $currency)
			return $value;

		static $rates = null;

		if($rates === null)
		{
			if(\Bitrix\Main\Loader::includeModule('currency'))
				$rates = new \CCurrencyRates;
			else
				$rates = false;
		}

		if($rates)
			$result = $rates->convertCurrency($result,  $this->currency, $currency);
		else
			$result = $value;

		return $result;
	}

	protected function convertToOperatingCurrency($value)
	{
		return $this->convertToOtherCurrency($value, $this->operatingCurrency);
	}

	public static function prepareParamsToSave(array $params)
	{
		return $params;
	}

	public function canUserEditValue()
	{
		return $this->rights[Manager::RIGHTS_CLIENT_IDX] == "Y";
	}

	public function canManagerEditValue()
	{
		return $this->rights[Manager::RIGHTS_MANAGER_IDX] == "Y";
	}

	public function getAdminDefaultControl($prefix = "", $value = false)
	{
		return $this->getEditControl($prefix, $value);
	}

	public static function getAdminParamsControl($name, array $params, $currency = "")
	{
		return false;
	}

	public function isStore()
	{
		return $this->className == '\Bitrix\Sale\Delivery\ExtraServices\Store';
	}

	public function getParams()
	{
		return $this->params;
	}

	public static function isInner()
	{
		return false;
	}

	public function setOperatingCurrency($currency)
	{
		$this->operatingCurrency = $currency;
	}

	public function getOperatingCurrency()
	{
		return $this->operatingCurrency;
	}

	public function getCode()
	{
		return $this->code;
	}


	public function getCostShipment(Shipment $shipment = null)
	{
		return $this->getCost();
	}

	/**
	 * @return float
	 * @deprecated
	 * use \Bitrix\Sale\Delivery\ExtraServices\Base::getCostShipment()
	 */
	static public function getCost()
	{
		return 0;
	}

	public static function isEmbeddedOnly()
	{
		return false;
	}

	public function getPriceShipment(Shipment $shipment = null)
	{
		return $this->getPrice();
	}
}
