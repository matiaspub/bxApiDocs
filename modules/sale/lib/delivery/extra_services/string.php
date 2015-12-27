<?php

namespace Bitrix\Sale\Delivery\ExtraServices;

use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class String extends Base
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
		return Loc::getMessage("DELIVERY_EXTRA_SERVICE_STRING_TITLE");
	}

	public function setValue($value)
	{
		$this->value = intval($value);
	}

	public function getCost()
	{
		return floatval($this->getPrice())*floatval($this->value);
	}

	public static function getAdminParamsName()
	{
		return Loc::getMessage("DELIVERY_EXTRA_SERVICE_STRING_PRICE");
	}

	public static function getAdminParamsControl($name, array $params = array(), $currency = "")
	{
		return '<input type="text" name="'.$name.'[PARAMS][PRICE]" value="'.$params["PARAMS"]["PRICE"].'">'.(strlen($currency) > 0 ? " (".$currency.")" : "");
	}

	public function setOperatingCurrency($currency)
	{
		$this->params["ONCHANGE"] = $this->createJSOnchange($this->id, $this->getPrice());
		parent::setOperatingCurrency($currency);
	}

	protected function createJSOnchange($id, $price)
	{
		return 'BX.onCustomEvent("onDeliveryExtraServiceValueChange", [{"id" : "'.$id.'", "value": this.value, "price": this.value*parseFloat("'.$price.'")}]);';
	}
}

