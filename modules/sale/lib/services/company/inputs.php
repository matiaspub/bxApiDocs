<?php

namespace Bitrix\Sale\Company\Inputs;
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/lib/internals/input.php");

use Bitrix\Sale\Internals\CompanyLocationTable;
use	Bitrix\Sale\Internals\Input;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Services\Company;

Loc::loadMessages(__FILE__);

class LocationMulti extends Input\Base
{
	public static function getViewHtml(array $input, $value = null)
	{
		$result = "";

		$res = CompanyLocationTable::getConnectedLocations(
			$input["COMPANY_ID"],
			array(
				'select' => array('LNAME' => 'NAME.NAME'),
				'filter' => array('NAME.LANGUAGE_ID' => LANGUAGE_ID)
			)
		);

		while($loc = $res->fetch())
			$result .= htmlspecialcharsbx($loc["LNAME"])."<br>\n";

		$res = CompanyLocationTable::getConnectedGroups(
			$input["COMPANY_ID"],
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
				"ENTITY_PRIMARY" => $input["COMPANY_ID"],
				"LINK_ENTITY_NAME" => Company\Manager::getLocationConnectorEntityName(),
				"INPUT_NAME" => $name
			),
			false
		);

		$result = ob_get_contents();
		$result = '
			<script>

				var bxInputcompanyLocMultiStep3 = function()
				{
					BX.loadScript("/bitrix/components/bitrix/sale.location.selector.system/templates/.default/script.js", function(){
						BX.onCustomEvent("companyGetRuleHtmlScriptsReady");
					});
				};

				var bxInputcompanyLocMultiStep2Count = 0;

				var bxInputcompanyLocMultiStep2CB = function(){

					bxInputcompanyLocMultiStep2Count++;

					if(bxInputcompanyLocMultiStep2Count >= 3)
						bxInputcompanyLocMultiStep3();
				};

				var bxInputcompanyLocMultiStep2 = function()
				{
					BX.loadScript("/bitrix/js/sale/core_ui_etc.js", bxInputcompanyLocMultiStep2CB);
					BX.loadScript("/bitrix/js/sale/core_ui_autocomplete.js", bxInputcompanyLocMultiStep2CB);
					BX.loadScript("/bitrix/js/sale/core_ui_itemtree.js", bxInputcompanyLocMultiStep2CB);
				};

				BX.loadScript("/bitrix/js/sale/core_ui_widget.js", bxInputcompanyLocMultiStep2);

				//at first we must load some scripts in the right order
				window["companyGetRuleHtmlScriptsLoadingStarted"] = true;

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

Input\Manager::register('COMPANY_LOCATION_MULTI', array(
	'CLASS' => __NAMESPACE__.'\\LocationMulti',
	'NAME' => Loc::getMessage('INPUT_company_LOCATION_MULTI')
));
