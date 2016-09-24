<?php

namespace Bitrix\Sale\Delivery\Inputs;
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/lib/internals/input.php");

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Sale\Delivery\DeliveryLocationTable;
use	Bitrix\Sale\Internals\Input;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Period extends Input\Base
{
	public static function getViewHtmlSingle(array $input, $values)
	{
		if(!is_array($values))
			throw new ArgumentTypeException('values', 'array');

		self::checkArgs($input, $values);

		return $input["ITEMS"]["FROM"]["NAME"].": ".Input\Manager::getViewHtml($input["ITEMS"]["FROM"], $values["FROM"]).
			$input["ITEMS"]["TO"]["NAME"].": ".Input\Manager::getViewHtml($input["ITEMS"]["TO"], $values["TO"]).
			" ".Input\Manager::getViewHtml($input["ITEMS"]["TYPE"], $values["TYPE"]);
	}

	public static function getEditHtmlSingle($name, array $input, $values)
	{
		if(!isset($input["ITEMS"]))
			$input["ITEMS"] = array(
		"FROM" => array(
			"TYPE" => "STRING",
			"NAME" => ""
		),
		"TO" => array(
			"TYPE" => "STRING",
			"NAME" => "&nbsp;-&nbsp;"
		),
		"TYPE" => array(
			"TYPE" => "ENUM",
			"OPTIONS" => array(
				"H" => "HOURS", //Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_HOUR"),
				"D" => "DAYS", //Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_DAY"),
				"M" => "MONTHS" ////Loc::getMessage("SALE_DLVR_HANDL_CONF_PERIOD_MONTH")
			)
		)
	);

		return $input["ITEMS"]["FROM"]["NAME"].Input\Manager::getEditHtml($name."[FROM]", $input["ITEMS"]["FROM"], $values["FROM"]).
			$input["ITEMS"]["TO"]["NAME"].Input\Manager::getEditHtml($name."[TO]", $input["ITEMS"]["TO"], $values["TO"]).
			" ".Input\Manager::getEditHtml($name."[TYPE]", $input["ITEMS"]["TYPE"], $values["TYPE"]);
	}

	public static function getError(array $input, $values)
	{
		if(!is_array($values))
			throw new ArgumentTypeException('values', 'array');

		return self::getErrorSingle($input, $values);
	}

	public static function getErrorSingle(array $input, $values)
	{
		if(!is_array($values))
			throw new ArgumentTypeException('values', 'array');

		self::checkArgs($input, $values);

		$errors = array();

		if ($error = Input\Manager::getError($input["ITEMS"]["FROM"], $values["FROM"]))
			$errors = $error;

		if ($error = Input\Manager::getError($input["ITEMS"]["TO"], $values["TO"]))
			$errors = array_merge($errors, $error);

		if ($error = Input\Manager::getError($input["ITEMS"]["TYPE"], $values["TYPE"]))
			$errors = array_merge($errors, $error);

		return $errors;
	}

	public static function getValueSingle(array $input, $userValue)
	{
		return $userValue;
	}

	public static function getSettings(array $input, $reload)
	{
		return array();
	}

	protected static function checkArgs(array $input, array $values)
	{
		if(!isset($input["ITEMS"]["FROM"]) || !isset($input["ITEMS"]["TO"]) || !isset($input["ITEMS"]["TYPE"]))
			throw new ArgumentException("Wrong argument structure!", "input");

		if(!isset($values["FROM"]) || !isset($values["TO"]) || !isset($values["TYPE"]))
			throw new \Bitrix\Main\ArgumentException("Wrong argument structure!", "values");

		return true;
	}
}

Input\Manager::register('DELIVERY_PERIOD', array(
	'CLASS' => __NAMESPACE__.'\\Period',
	'NAME' => Loc::getMessage('INPUT_DELIVERY_PERIOD')
));

class ReadOnly extends Input\Base
{
	public static function getViewHtmlSingle(array $input, $value)
	{
		$result = '<span';

		if(!empty($input['ID']))
			$result .= ' id="'.$input['ID'].'_view"';

		$result .= '>';
		$result .= isset($input["VALUE_VIEW"]) ? $input["VALUE_VIEW"] : $value;
		$result .= '</span>';
		return $result;
	}

	public static function getEditHtmlSingle($name, array $input, $value)
	{
		$value = str_replace('"', "'", $value);
		$res = self::getViewHtml($input, $value).'<input type="hidden" value="'.htmlspecialcharsbx($value).'" name="'.htmlspecialcharsbx($name).'"';

		if(!empty($input['ID']))
			$res .= ' id="'.$input['ID'].'"';

		$res .= '>';
		return $res;
	}


	public static function getError(array $input, $values)
	{
		return self::getErrorSingle($input, $values);
	}

	public static function getErrorSingle(array $input, $values)
	{
		return array();
	}

	public static function getValueSingle(array $input, $userValue)
	{
		return $userValue;
	}

	public static function getSettings(array $input, $reload)
	{
		return array();
	}
}

Input\Manager::register('DELIVERY_READ_ONLY', array(
	'CLASS' => __NAMESPACE__.'\\ReadOnly',
	'NAME' => Loc::getMessage('INPUT_DELIVERY_READ_ONLY')
));

class MultiControlString extends Input\Base
{
	protected $items = array();
	protected $myParams = array();
	protected $myKey = array();

	public function addItem($key, array $control)
	{
		$this->items[$key] = $control;
	}

	public function setParams($key, array $params)
	{
		$this->myParams = $params;
		$this->setKey($key);
	}

	public function getParams()
	{
		$result = $this->myParams;
		$result["ITEMS"] = $this->items;
		return $result;
	}

	public function setKey($key)
	{
		$this->myKey = $key;
	}

	public function getKey()
	{
		return $this->myKey;
	}

	public function isClean()
	{
		return empty($this->myParams);
	}

	public function clean()
	{
		$this->myParams = $this->items = $this->myKey = array();
	}

	public static function getViewHtmlSingle(array $input, $values)
	{
		$result = "";

		foreach($input["ITEMS"] as $key => $item)
			$result .=
				isset($item["NAME"]) ? $item["NAME"] : "".
				Input\Manager::getViewHtml($item, isset($values[$key]) ? $values[$key] : null).
				" ";

		return $result;
	}

	public static function getEditHtmlSingle($name, array $input, $values)
	{
		$result = "";

		foreach($input["ITEMS"] as $key => $item)
			$result .=
				isset($item["NAME"]) ? $item["NAME"] : "".
				Input\Manager::getEditHtml($name."[".$key."]", $item, isset($values[$key]) ? $values[$key] : null).
				" ";

		return $result;
	}

	public static function getErrorSingle(array $input, $values)
	{
		if(!is_array($values))
			throw new ArgumentTypeException('values', 'array');

		$errors = array();

		foreach($input["ITEMS"] as $key => $item)
			if ($error = Input\Manager::getError($item, isset($values[$key]) ? $values[$key] : null))
				$errors[$key] = $error;

		return $errors;
	}

	public static function getValueSingle(array $input, $userValue)
	{
		return $userValue;
	}

	public static function getSettings(array $input, $reload)
	{
		return array();
	}
}

Input\Manager::register('DELIVERY_MULTI_CONTROL_STRING', array(
	'CLASS' => __NAMESPACE__.'\\MultiControlString',
	'NAME' => Loc::getMessage('INPUT_DELIVERY_MULTI_CONTROL_STRING')
));

class LocationMulti extends Input\Base
{
	public static function getViewHtml(array $input, $value = null)
	{
		$result = "";

		$res = \Bitrix\Sale\Delivery\DeliveryLocationTable::getConnectedLocations(
			$input["DELIVERY_ID"],
			array(
				'select' => array('LNAME' => 'NAME.NAME'),
				'filter' => array('NAME.LANGUAGE_ID' => LANGUAGE_ID)
			)
		);

		while($loc = $res->fetch())
			$result .= htmlspecialcharsbx($loc["LNAME"])."<br>\n";

		$res = DeliveryLocationTable::getConnectedGroups(
			$input["DELIVERY_ID"],
			array(
				'select' => array('LNAME' => 'NAME.NAME'),
				'filter' => array('NAME.LANGUAGE_ID' => LANGUAGE_ID)
			)
		);

		while($loc = $res->fetch())
			$result .= htmlspecialcharsbx($loc["LNAME"])."<br>\n";

		return $result;
	}

	public static function getEditHtml($name, array $input, $values = null)
	{
		global $APPLICATION;

		ob_start();

		$APPLICATION->IncludeComponent(
			"bitrix:sale.location.selector.system",
			"",
			array(
				"ENTITY_PRIMARY" => $input["DELIVERY_ID"],
				"LINK_ENTITY_NAME" => \Bitrix\Sale\Delivery\Services\Manager::getLocationConnectorEntityName(),
				"INPUT_NAME" => $name
			),
			false
		);

		$result = ob_get_contents();
		$result = '
			<script>

				var bxInputdeliveryLocMultiStep3 = function()
				{
					BX.loadScript("/bitrix/components/bitrix/sale.location.selector.system/templates/.default/script.js", function(){
						BX.onCustomEvent("deliveryGetRestrictionHtmlScriptsReady");
					});
				};

				var bxInputdeliveryLocMultiStep2Count = 0;

				var bxInputdeliveryLocMultiStep2CB = function(){

					bxInputdeliveryLocMultiStep2Count++;

					if(bxInputdeliveryLocMultiStep2Count >= 3)
						bxInputdeliveryLocMultiStep3();
				};

				var bxInputdeliveryLocMultiStep2 = function()
				{
					BX.loadScript("/bitrix/js/sale/core_ui_etc.js", bxInputdeliveryLocMultiStep2CB);
					BX.loadScript("/bitrix/js/sale/core_ui_autocomplete.js", bxInputdeliveryLocMultiStep2CB);
					BX.loadScript("/bitrix/js/sale/core_ui_itemtree.js", bxInputdeliveryLocMultiStep2CB);
				};

				BX.loadScript("/bitrix/js/sale/core_ui_widget.js", bxInputdeliveryLocMultiStep2);

				//at first we must load some scripts in the right order
				window["deliveryGetRestrictionHtmlScriptsLoadingStarted"] = true;

			</script>

			<link rel="stylesheet" type="text/css" href="/bitrix/panel/main/adminstyles_fixed.css">
			<link rel="stylesheet" type="text/css" href="/bitrix/panel/main/admin.css">
			<link rel="stylesheet" type="text/css" href="/bitrix/panel/main/admin-public.css">
			<link rel="stylesheet" type="text/css" href="/bitrix/components/bitrix/sale.location.selector.system/templates/.default/style.css">
		'.
		$result;
		ob_end_clean();
		return $result;
	}

	public static function getError(array $input, $values)
	{
		return array();
	}


	public static function getValueSingle(array $input, $userValue)
	{
		return $userValue;
	}

	public static function getSettings(array $input, $reload)
	{
		return array();
	}
}

Input\Manager::register('LOCATION_MULTI', array(
	'CLASS' => __NAMESPACE__.'\\LocationMulti',
	'NAME' => Loc::getMessage('INPUT_DELIVERY_LOCATION_MULTI')
));

class ProductCategories extends Input\Base
{
	public static function getViewHtml(array $input, $values = null)
	{
		if(!is_array($values))
			return '';

		$result = '<br><br>';
		$catList = self::getCategoriesList($values);

		foreach($catList as $catName)
			$result .= '<div> - '.$catName.'</div>';

		return $result;
	}

	public static function getEditHtml($name, array $input, $values = null)
	{
		if(!is_array($values))
			$values = array();

		$result = '<br><a style="color:#113c7d;text-decoration:none;border-bottom:1px dashed #113c7d;font-weight: bold;" href="javascript:void(0);" id="'.$input["ID"].'" onclick="window.open(\''.$input["URL"].'\',\'choose category\',\'width=850,height=600\');">'.Loc::getMessage('SALE_DELIVERY_INP_ADD').'</a><br><br>'.
			'<script type="text/javascript">'.$input["SCRIPT"].'</script>'.
			'<script type="text/javascript">BX.message({SALE_DELIVERY_INP_DELETE: "'.Loc::getMessage("SALE_DELIVERY_INP_DELETE").'"});</script>';

		$catList = self::getCategoriesList($values);
		$existCatHtml = '<table id="sale-admin-delivery-restriction-cat-content" width="100%">';

		foreach($catList as $catId => $catName)
			$existCatHtml .= '
				<tr class="adm-s-delivery-restriction-delcat" id="sale-admin-delivery-restriction-cat-'.$catId.'">
					<td>
						<span> - '.$catName.'</span>
						<input type="hidden" name="RESTRICTION[CATEGORIES][]" value="'.$catId.'">
					</td>
					<td align="right">
						&nbsp;<a class="adm-s-bus-morelinkqhsw" href="javascript:void(0);" onclick="BX.Sale.Delivery.deleteRestrictionProductSection(\''.$catId.'\');">'.Loc::getMessage('SALE_DELIVERY_INP_DELETE').'</a>
					</td>
				</tr>';

		$existCatHtml .= '</table>';

		return $existCatHtml.$result;
	}

	protected static function getCategoriesList($ids)
	{
		if(!\Bitrix\Main\Loader::includeModule('iblock'))
			return array();

		$result = array();

		$res = \Bitrix\Iblock\SectionTable::getList(array(
			'filter' => array(
				'ID' => $ids
			),
			'select' => array('ID', 'NAME')
		));

		while($section = $res->fetch())
			$result[$section['ID']]  = $section['NAME'];

		return $result;
	}

	public static function getValueSingle(array $input, $userValue)
	{
		return $userValue;
	}


	public static function getError(array $input, $values)
	{
		return self::getErrorSingle($input, $values);
	}

	public static function getErrorSingle(array $input, $values)
	{
		return array();
	}

	public static function getSettings(array $input, $reload)
	{
		return array();
	}
}

Input\Manager::register('DELIVERY_PRODUCT_CATEGORIES', array(
	'CLASS' => __NAMESPACE__.'\\ProductCategories',
	'NAME' => Loc::getMessage('INPUT_DELIVERY_PRODUCT_CATEGORIES')
));
