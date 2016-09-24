<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 */

// define("START_EXEC_EPILOG_AFTER_1", microtime());
$GLOBALS["BX_STATE"] = "EA";

global $USER, $APPLICATION, $DB;

foreach(GetModuleEvents("main", "OnEpilog", true) as $arEvent)
	ExecuteModuleEventEx($arEvent);

$r = $APPLICATION->EndBufferContentMan();
$main_exec_time = round((getmicrotime()-START_EXEC_TIME), 4);

//it's possible to have no response on update
$response = \Bitrix\Main\Context::getCurrent()->getResponse();
if($response)
{
	$response->flush($r);
}
else
{
	echo $r;
}

$arAllEvents = GetModuleEvents("main", "OnAfterEpilog", true);

// define("START_EXEC_EVENTS_1", microtime());
$GLOBALS["BX_STATE"] = "EV";
CMain::EpilogActions();
// define("START_EXEC_EVENTS_2", microtime());
$GLOBALS["BX_STATE"] = "EA";

foreach($arAllEvents as $arEvent)
	ExecuteModuleEventEx($arEvent);

if(!IsModuleInstalled("compression") && !defined("ADMIN_AJAX_MODE") && ($_REQUEST["mode"] != 'excel'))
{
	$canEditPHP = $USER->CanDoOperation('edit_php');
	$bShowTime = ($_SESSION["SESS_SHOW_TIME_EXEC"] == 'Y');
	$bShowStat = ($DB->ShowSqlStat && $canEditPHP);
	$bShowCacheStat = (\Bitrix\Main\Data\Cache::getShowCacheStat() && ($canEditPHP || $_SESSION["SHOW_CACHE_STAT"]=="Y"));

	if($bShowTime || $bShowStat || $bShowCacheStat)
	{
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/interface/debug_info.php");
	}
}

$DB->Disconnect();

CMain::ForkActions();
