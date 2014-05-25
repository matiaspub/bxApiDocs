<?
/**
 * @global CMain $APPLICATION
 */
// define("STOP_STATISTICS", true);
// define("PUBLIC_AJAX_MODE", true);

if(isset($_REQUEST["site"]) && is_string($_REQUEST["site"]))
{

	$site_id = trim($_REQUEST["site"]);
	$site_id = substr(preg_replace("/[^a-z0-9_]/i", "", $site_id), 0, 2);
	// define("SITE_ID", $site_id);
}

require_once(dirname(__FILE__)."/../include/prolog_before.php");

$arParams = array();
$arParams["PATH_TO_USER"] = COption::GetOptionString("main", "TOOLTIP_PATH_TO_USER", "", SITE_ID);
$arParams["PATH_TO_MESSAGES_CHAT"] = COption::GetOptionString("main", "TOOLTIP_PATH_TO_MESSAGES_CHAT", "", SITE_ID);
$arParams["DATE_TIME_FORMAT"] = COption::GetOptionString("main", "TOOLTIP_DATE_TIME_FORMAT", "", SITE_ID);
$arParams["NAME_TEMPLATE"] = COption::GetOptionString("main", "TOOLTIP_DATE_TIME_FORMAT", "", SITE_ID);
$arParams["SHOW_YEAR"] = COption::GetOptionString("main", "TOOLTIP_SHOW_YEAR", "", SITE_ID);
$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat(false);
$arParams["SHOW_LOGIN"] = COption::GetOptionString("main", "TOOLTIP_SHOW_LOGIN", "", SITE_ID);
$arParams["PATH_TO_CONPANY_DEPARTMENT"] = COption::GetOptionString("main", "TOOLTIP_PATH_TO_CONPANY_DEPARTMENT", "", SITE_ID);
$arParams["PATH_TO_VIDEO_CALL"] = COption::GetOptionString("main", "TOOLTIP_PATH_TO_VIDEO_CALL", "", SITE_ID);

$APPLICATION->IncludeComponent("bitrix:main.user.link",
	'',
	array(
		"AJAX_ONLY" => "Y",
		"PATH_TO_SONET_USER_PROFILE" => $arParams["PATH_TO_USER"],
		"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["PATH_TO_MESSAGES_CHAT"],
		"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
		"SHOW_YEAR" => $arParams["SHOW_YEAR"],
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
		"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
		"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
		"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
	),
	false,
	array("HIDE_ICONS" => "Y")
);

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
