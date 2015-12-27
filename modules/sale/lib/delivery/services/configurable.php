<?php

namespace Bitrix\Sale\Delivery\Services;

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Currency;

Loc::loadMessages(__FILE__);

/**
 * Class Configurable
 * Simple class for delivery service.
 * Old configurable type converted to this type.
 * @package Bitrix\Sale\Delivery\Services
 */
class Configurable extends Base
{
	public $countPriceImmediately = true;

	/**
	 * @param array $initParams Initial data params from table record.
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function __construct(array $initParams)
	{
		parent::__construct($initParams);

		if(!isset($this->config["MAIN"]["PRICE"]))
			$this->config["MAIN"]["PRICE"] = "0";

		if(!isset($initParams["CURRENCY"]))
			$initParams["CURRENCY"] = "RUB";

		if(!isset($this->config["MAIN"]["PERIOD"]) || !is_array($this->config["MAIN"]["PERIOD"]))
		{
			$this->config["MAIN"]["PERIOD"] = array();
			$this->config["MAIN"]["PERIOD"]["FROM"] = "0";
			$this->config["MAIN"]["PERIOD"]["TO"] = "0";
			$this->config["MAIN"]["PERIOD"]["TYPE"] = "D";
		}
	}

	/**
	 * @return string Class title.
	 */
	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLVR_HANDL_NAME");
	}

	/**
	 * @return string Class, service description.
	 */
	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLVR_HANDL_DESCRIPTION");
	}

	/**
	 * @return string Period text.
	 */
	protected function getPeriodText()
	{
		$result = "";

		if (IntVal($this->config["MAIN"]["PERIOD"]["FROM"]) > 0 || IntVal($this->config["MAIN"]["PERIOD"]["TO"]) > 0)
		{
			$result = "";

			if (IntVal($this->config["MAIN"]["PERIOD"]["FROM"]) > 0)
				$result .= " ".Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_FROM")." ".IntVal($this->config["MAIN"]["PERIOD"]["FROM"]);

			if (IntVal($this->config["MAIN"]["PERIOD"]["TO"]) > 0)
				$result .= " ".Loc::getMessage("SOA_TO")." ".IntVal($this->config["MAIN"]["PERIOD"]["TO"]);

			if ($this->config["MAIN"]["PERIOD"]["TYPE"] == "H")
				$result .= " ".Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_HOUR")." ";
			elseif ($this->config["MAIN"]["PERIOD"]["TYPE"] == "M")
				$result .= " ".Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_MONTH")." ";
			else
				$result .= " ".Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_DAY")." ";
		}

		return $result;
	}

	protected function calculateConcrete(\Bitrix\Sale\Shipment $shipment = null)
	{
		$result = new CalculationResult;
		$price = $this->config["MAIN"]["PRICE"];

		if($shipment && \Bitrix\Main\Loader::includeModule('currency'))
		{
			$rates = new \CCurrencyRates;
			$currency = $this->currency;
			$shipmentCurrency = $shipment->getCollection()->getOrder()->getCurrency();
			$price = $rates->convertCurrency( $price,  $currency, $shipmentCurrency);
		}

		$result->setDeliveryPrice(
			roundEx(
				$price,
				SALE_VALUE_PRECISION
			)
		);

		$result->setPeriodDescription($this->getPeriodText());
		return $result;
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	protected function getConfigStructure()
	{
		$currency = $this->currency;

		if(Loader::includeModule('currency'))
		{
			$currencyList = Currency\CurrencyManager::getCurrencyList();
			if (isset($currencyList[$this->currency]))
				$currency = $currencyList[$this->currency];
			unset($currencyList);
		}

		return array(

			"MAIN" => array(
				"TITLE" => Loc::getMessage("SALE_DLVR_HANDL_CONF_TITLE"),
				"DESCRIPTION" => Loc::getMessage("SALE_DLVR_HANDL_CONF_DESCRIPTION"),
				"ITEMS" => array(

					"CURRENCY" => array(
						"TYPE" => "DELIVERY_READ_ONLY",
						"NAME" => Loc::getMessage("SALE_DLVR_HANDL_CONF_CURRENCY"),
						"VALUE" => $this->currency,
						"VALUE_VIEW" => $currency
					),

					"PRICE" => array(
						"TYPE" => "NUMBER",
						"MIN" => 0,
						"NAME" => Loc::getMessage("SALE_DLVR_HANDL_CONF_PRICE")
					),

					"PERIOD" => array(
						"TYPE" => "DELIVERY_PERIOD",
						"NAME" => Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_DLV"),
						"ITEMS" => array(
							"FROM" => array(
								"TYPE" => "NUMBER",
								"MIN" => 0,
								"NAME" => "" //Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_FROM"),
							),
							"TO" => array(
								"TYPE" => "NUMBER",
								"MIN" => 0,
								"NAME" => "&nbsp;-&nbsp;" //Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_TO"),
							),
							"TYPE" => array(
								"TYPE" => "ENUM",
								"OPTIONS" => array(
									"H" => Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_HOUR"),
									"D" => Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_DAY"),
									"M" => Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_MONTH")
								)
							)
						)
					)
				)
			)
		);
	}

	public static function getAdminFieldsList()
	{
		$result = parent::getAdminFieldsList();
		$result["STORES"] = true;
		return $result;
	}

	static public function prepareFieldsForSaving(array $fields)
	{
		if((!isset($fields["CODE"]) || intval($fields["CODE"]) < 0) && isset($fields["ID"]) && intval($fields["ID"]) > 0)
			$fields["CODE"] = $fields["ID"];

		return parent::prepareFieldsForSaving($fields);
	}

	public static function whetherAdminExtraServicesShow()
	{
		return true;
	}

	public static function onAfterAdd($serviceId, array $fields = array())
	{
		if($serviceId <= 0)
			return false;

		$res = \Bitrix\Sale\Delivery\Services\Table::update($serviceId, array('CODE' => $serviceId));
		return $res->isSuccess();
	}
}