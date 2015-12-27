<?php

namespace Bitrix\Sale\Delivery\ExtraServices;

use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Checkbox extends Base
{
	public function __construct($id, array $structure, $currency, $value = null, array $additionalParams = array())
	{
		$structure["PARAMS"]["ONCHANGE"] = $this->createJSOnchange($id, $structure["PARAMS"]["PRICE"]);
		parent::__construct($id, $structure, $currency, $value, $additionalParams);
		$this->params["TYPE"] = "Y/N";
	}

	static public function getClassTitle()
	{
		return Loc::getMessage("DELIVERY_EXTRA_SERVICE_CHECKBOX_TITLE");
	}

	public function getCost()
	{
		if($this->value == "Y")
			$result = $this->getPrice();
		else
			$result = 0;

		return $result;
	}

	public static function getAdminParamsName()
	{
		return Loc::getMessage("DELIVERY_EXTRA_SERVICE_CHECKBOX_PRICE");
	}

	public static function getAdminParamsControl($name, array $params, $currency = "")
	{
		return \Bitrix\Sale\Internals\Input\Manager::getEditHtml(
			$name."[PARAMS][PRICE]",
			array(
				"TYPE" => "NUMBER"
			),
			$params["PARAMS"]["PRICE"]
		).(strlen($currency) > 0 ? " (".$currency.")" : "");
	}

	public function setOperatingCurrency($currency)
	{
		$this->params["ONCHANGE"] = $this->createJSOnchange($this->id, $this->getPrice());
		parent::setOperatingCurrency($currency);
	}

	protected function createJSOnchange($id, $price)
	{
		return 'BX.onCustomEvent("onDeliveryExtraServiceValueChange", [{"id" : "'.$id.'", "value": this.checked, "price": this.checked ? "'.$price.'" : "0"}]);';
	}

}
