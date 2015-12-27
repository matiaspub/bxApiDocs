<?php
namespace Bitrix\Sale\Delivery\Restrictions;

use Bitrix\Main\SystemException;
use Bitrix\Sale\Delivery\Restrictions\Base;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class ByWeight
 * Restricts delivery by weight
 * @package Bitrix\Sale\Delivery\Restrictions
 */
class ByPrice extends Base
{
	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLVR_RSTR_BY_PRICE_NAME");
	}

	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLVR_RSTR_BY_PRICE_DESCRIPT");
	}

	static public function check($price, array $restrictionParams, $deliveryId = 0)
	{
		$price = floatval($price);

		if(floatval($restrictionParams["MIN_PRICE"]) > 0  && $price < floatval($restrictionParams["MIN_PRICE"]))
			$result = false;
		elseif(floatval($restrictionParams["MAX_PRICE"]) > 0 && $price > floatval($restrictionParams["MAX_PRICE"]))
			$result = false;
		else
			$result = true;

		return $result;
	}

	public function checkByShipment(\Bitrix\Sale\Shipment $shipment, array $restrictionParams, $deliveryId = 0)
	{
		if(empty($restrictionParams))
			return true;

		$result = true;

		if(!$itemCollection = $shipment->getShipmentItemCollection())
			throw new SystemException("Cant get ShipmentItemCollection");

		$shipmentPrice = $itemCollection->getPrice();

		if (\Bitrix\Main\Loader::includeModule('currency'))
		{
			$shipmentPrice = \CCurrencyRates::convertCurrency(
				$shipmentPrice,
				$shipment->getCurrency(),
				$restrictionParams["CURRENCY"]
			);
		}

		if($shipmentPrice >= 0)
			$result = $this->check($shipmentPrice, $restrictionParams, $deliveryId);

		return $result;
	}

	public static function getParamsStructure()
	{
		return array(
			"MIN_PRICE" => array(
				"TYPE" => "NUMBER",
				"DEFAULT" => "0",
				'MIN' => 0,
				"LABEL" => Loc::getMessage("SALE_DLVR_RSTR_BY_PRICE_MIN_PRICE")
			),

			"MAX_PRICE" => array(
				"TYPE" => "NUMBER",
				"DEFAULT" => "0",
				'MIN' => 0,
				"LABEL" => Loc::getMessage("SALE_DLVR_RSTR_BY_PRICE_MAX_PRICE")
			),

			"CURRENCY" => array(
				"TYPE" => "ENUM",
				"DEFAULT" => "RUB",
				"LABEL" => Loc::getMessage("SALE_DLVR_RSTR_BY_PRICE_CURRECY"),
				"OPTIONS" => \Bitrix\Sale\Delivery\Helper::getCurrenciesList()
			)
		);
	}
}