<?php

namespace Sale\Handlers\Delivery\Additional\ExtraServices;

use Bitrix\Sale\Shipment;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\ExtraServices\Base;

Loc::loadMessages(__FILE__);

class Insurance extends Base
{
	public function __construct($id, array $structure, $currency, $value = null, array $additionalParams = array())
	{
		$structure["PARAMS"]["ONCHANGE"] = $this->createJSOnchange($id);
		parent::__construct($id, $structure, $currency, $value, $additionalParams);
		$this->params["TYPE"] = "Y/N";
	}

	static public function getClassTitle()
	{
		return Loc::getMessage('SALE_DLVRS_ADD_ESI_TITLE');
	}

	public function getCostShipment(Shipment $shipment = null)
	{
		if($this->value != "Y")
			return 0;

		return $this->getPriceShipment($shipment);
	}

	protected function getShipmentProductsPrice(Shipment $shipment = null)
	{
		$result = 0;

		/** @var \Bitrix\Sale\ShipmentItem $shipmentItem */
		foreach($shipment->getShipmentItemCollection() as $shipmentItem)
		{
			$basketItem = $shipmentItem->getBasketItem();

			if($basketItem)
				$result += $basketItem->getPrice();
		}

		return $result;
	}

	public static function getAdminParamsName()
	{
		return Loc::getMessage('SALE_DLVRS_ADD_ESI_PARAMS_NAME');
	}

	public static function getAdminParamsControl($name, array $params, $currency = "")
	{
		return \Bitrix\Sale\Internals\Input\Manager::getEditHtml(
			$name."[PARAMS][FEE]",
			array("TYPE" => "NUMBER"),
			$params["PARAMS"]["FEE"]
		)." %";
	}

	protected function createJSOnchange($id)
	{
		return "BX.onCustomEvent('onDeliveryExtraServiceValueChange', [{'id' : '".$id."', 'value': this.checked, 'price': '0'}]);";
	}

	public static function isEmbeddedOnly()
	{
		return true;
	}

	public function getPriceShipment(Shipment $shipment = null)
	{
		if(!isset($this->params["FEE"]))
			return 0;

		if(!$shipment)
			return 0;

		$shipmentPrice = $this->getShipmentProductsPrice($shipment);
		//$shipmentPrice = $sipment->getPrice();

		return $this->convertToOperatingCurrency(
				$shipmentPrice * floatval($this->params["FEE"]) / 100
		);
	}
}
