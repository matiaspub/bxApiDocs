<?php

namespace Bitrix\Sale\Delivery\ExtraServices;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Quantity extends Base
{
	public function __construct($id, array $structure, $currency, $value = null, array $additionalParams = array())
	{
		if(isset($structure["PARAMS"]["PRICE"]))
			$structure["PARAMS"]["ONCHANGE"] = $this->createJSOnchange($id, $structure["PARAMS"]["PRICE"]);

		parent::__construct($id, $structure, $currency, $value);
		$this->params["TYPE"] = "STRING";
	}

	static public function getClassTitle()
	{
		return Loc::getMessage("DELIVERY_EXTRA_SERVICE_QUANTITY_TITLE");
	}

	public function setValue($value)
	{
		$this->value = intval($value) >= 0 ? intval($value) : 0;
	}

	public function getCost()
	{
		return floatval($this->getPrice())*floatval($this->value);
	}

	public static function getAdminParamsName()
	{
		return Loc::getMessage("DELIVERY_EXTRA_SERVICE_QUANTITY_PRICE");
	}

	public static function getAdminParamsControl($name, array $params = array(), $currency = "")
	{
		if(!empty($params["PARAMS"]["PRICE"]))
			$price = roundEx(floatval($params["PARAMS"]["PRICE"]), SALE_VALUE_PRECISION);
		else
			$price = 0;

		return '<input type="text" name="'.$name.'[PARAMS][PRICE]" value="'.$price.'">'.(strlen($currency) > 0 ? " (".htmlspecialcharsbx($currency).")" : "");
	}

	public function setOperatingCurrency($currency)
	{
		$this->params["ONCHANGE"] = $this->createJSOnchange($this->id, $this->getPrice());
		parent::setOperatingCurrency($currency);
	}

	protected function createJSOnchange($id, $price)
	{
		$price = roundEx(floatval($price), SALE_VALUE_PRECISION);
		return "BX.onCustomEvent('onDeliveryExtraServiceValueChange', [{'id' : '".$id."', 'value': this.value, 'price': this.value*parseFloat('".$price."')}]);";
	}
}