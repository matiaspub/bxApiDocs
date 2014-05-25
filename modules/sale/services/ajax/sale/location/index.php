<?php
// define("NO_AGENT_CHECK", true);
// define("NO_AGENT_STATISTIC", true);
// define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

$arResult = array();

if (!\Bitrix\Main\Loader::includeModule('sale'))
	$arResult["ERROR"] = Loc::getMessage("SALE_SRV_LOCATION_CANT_INCLUDE_MODULE");

if(!isset($arResult["ERROR"]) && check_bitrix_sessid())
{
	$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']): '';
	$lang = isset($_REQUEST['lang']) ? $_REQUEST['lang']: LANGUAGE_ID;
	$countryIds = isset($_REQUEST['countryIds']) ? $_REQUEST['countryIds']: array();

	switch ($action)
	{
		case "getRegionList":

			$arResult["DATA"] = \Bitrix\Sale\SalesZone::getRegions($countryIds, $lang);

			break;

		case "getCityList":

			$regionIds = isset($_REQUEST['regionIds']) && is_array($_REQUEST['regionIds'])? $_REQUEST['regionIds']: array();

			$arResult["DATA"] = \Bitrix\Sale\SalesZone::getCities($countryIds, $regionIds, $lang);
			break;
	}
}
else
{
	if(!isset($arResult["ERROR"]))
		$arResult["ERROR"] = Loc::getMessage("SALE_SRV_LOCATION_ACCESS_DENIED");
}

if(isset($arResult["ERROR"]))
	$arResult["RESULT"] = "ERROR";
else
	$arResult["RESULT"] = "OK";

/** @global CMain $APPLICATION */
if(strtolower(SITE_CHARSET) != 'utf-8')
	$arResult = $APPLICATION -> ConvertCharsetArray($arResult, SITE_CHARSET, 'utf-8');

header('Content-Type: application/json');
echo json_encode($arResult);

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
