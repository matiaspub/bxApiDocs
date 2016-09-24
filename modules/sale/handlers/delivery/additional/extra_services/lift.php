<?php

namespace Sale\Handlers\Delivery\Additional\ExtraServices;

use Bitrix\Sale\Shipment;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\ExtraServices\Base;

Loc::loadMessages(__FILE__);

class Lift extends Base
{
	public function __construct($id, array $structure, $currency, $value = null, array $additionalParams = array())
	{
		$structure["PARAMS"]["ONCHANGE"] = $this->createJSOnchange($id);
		parent::__construct($id, $structure, $currency, $value);
		$this->params["TYPE"] = "STRING";
	}

	static public function getClassTitle()
	{
		return Loc::getMessage('SALE_DLVRS_ADD_ESL_TITLE');
	}

	public function setValue($value)
	{
		$this->value = intval($value) >= 0 ? intval($value) : 0;
	}

	public function getCostShipment(Shipment $shipment = null)
	{
		if(!$shipment)
			return 0;

		if(!isset($this->params['PRICES']) || !is_array($this->params['PRICES']))
			return 0;

		$weight = $shipment->getWeight()/1000;

		foreach($this->params['PRICES'] as $k => $v)
		{
			if(empty($v['W1']) && empty($v['W2']) && empty($v['P']))
				continue;

			if($weight >= floatval($v['W1']) && (floatval($v['W2']) <= 0 || $weight <= floatval($v['W2'])))
				return floatval($v['P'])*floatval($this->value);
		}

		return 0;
	}

	public static function getAdminParamsName()
	{
		return Loc::getMessage('SALE_DLVRS_ADD_ESL_PARAMS_NAME');
	}

	public static function getAdminParamsControl($name, array $params = array(), $currency = "")
	{
		$result = '';

		if(isset($params["PARAMS"]["PRICES"]) && is_array($params["PARAMS"]["PRICES"]))
			$count = count($params["PARAMS"]["PRICES"]);
		else
			$count = 0;

		for($i = 0; $i < $count+5; $i++)
		{
			$w1 = isset($params["PARAMS"]["PRICES"][$i]["W1"]) ? $params["PARAMS"]["PRICES"][$i]["W1"] : '';
			$w2 = isset($params["PARAMS"]["PRICES"][$i]["W2"]) ? $params["PARAMS"]["PRICES"][$i]["W2"] : '';
			$price = isset($params["PARAMS"]["PRICES"][$i]["P"]) ? roundEx($params["PARAMS"]["PRICES"][$i]["P"], SALE_VALUE_PRECISION) : '';

			$result .= Loc::getMessage('SALE_DLVRS_ADD_ESL_WEIGHT_FROM').
				'&nbsp;<input type="text" size="5" name="'.$name.'[PARAMS][PRICES]['.$i.'][W1]" value="'.$w1.'">&nbsp;-&nbsp;'.
				'<input type="text" size="5" name="'.$name.'[PARAMS][PRICES]['.$i.'][W2]" value="'.$w2.'">&nbsp;'.
				Loc::getMessage('SALE_DLVRS_ADD_ESL_KG').
				'&nbsp;-&nbsp;'.
				'<input type="text" size="5" name="'.$name.'[PARAMS][PRICES]['.$i.'][P]" value="'.$price.'">'.
				(strlen($currency) > 0 ? " (".htmlspecialcharsbx($currency).")" : "").'<br>';
		}

		return $result;
	}

	protected function createJSOnchange($id)
	{
		return "BX.onCustomEvent('onDeliveryExtraServiceValueChange', [{'id' : '".$id."', 'value': this.value, 'price': '0'}]);";
	}

	public static function prepareParamsToSave($params)
	{
		if(!isset($params["PARAMS"]["PRICES"]) || !is_array($params["PARAMS"]["PRICES"]))
			return $params;

		foreach($params["PARAMS"]["PRICES"] as $k => $v)
			if(empty($v['W1']) && empty($v['W2']) && empty($v['P']))
				unset($params["PARAMS"]["PRICES"][$k]);

		return $params;
	}

	public static function isEmbeddedOnly()
	{
		return true;
	}
}