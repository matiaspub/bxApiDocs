<?
// define("START_EXEC_EPILOG_AFTER_1", microtime());
$GLOBALS["BX_STATE"] = "EA";

if(!isset($USER)) {global $USER;}
if(!isset($APPLICATION)) {global $APPLICATION;}
if(!isset($DB)) {global $DB;}

foreach(GetModuleEvents("main", "OnEpilog", true) as $arEvent)
	ExecuteModuleEventEx($arEvent);

if(isset($_GET["show_lang_files"]) || isset($_SESSION["SHOW_LANG_FILES"]))
	include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/interface/lang_files.php");

$canEditPHP = $USER->CanDoOperation('edit_php');
if($canEditPHP)
	$_SESSION["SHOW_SQL_STAT"] = ($DB->ShowSqlStat? "Y": "N");

$bShowTime = isset($_SESSION["SESS_SHOW_TIME_EXEC"]) && ($_SESSION["SESS_SHOW_TIME_EXEC"] == 'Y');
$bShowStat = ($DB->ShowSqlStat && ($canEditPHP || $_SESSION["SHOW_SQL_STAT"]=="Y"));
$bShowCacheStat = (\Bitrix\Main\Data\Cache::getShowCacheStat() && ($canEditPHP || $_SESSION["SHOW_CACHE_STAT"]=="Y"));

if(($bShowStat || $bShowCacheStat) && !$USER->IsAuthorized())
{
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/init_admin.php");
	$GLOBALS["APPLICATION"]->AddHeadString($GLOBALS["adminPage"]->ShowScript());
	$GLOBALS["APPLICATION"]->AddHeadString('<script type="text/javascript" src="/bitrix/js/main/public_tools.js"></script>');
	$GLOBALS["APPLICATION"]->AddHeadString('<link rel="stylesheet" type="text/css" href="/bitrix/themes/.default/pubstyles.css" />');
}

if ($bShowStat || $bShowTime || $bShowCacheStat)
{
	CUtil::InitJSCore(array('window', 'admin'));
}

$r = $APPLICATION->EndBufferContentMan();
$main_exec_time = round(microtime(true) - START_EXEC_TIME, 4);

\Bitrix\Main\Context::getCurrent()->getResponse()->flush($r);

$arAllEvents = GetModuleEvents("main", "OnAfterEpilog", true);

// define("START_EXEC_EVENTS_1", microtime());
$GLOBALS["BX_STATE"] = "EV";
CMain::EpilogActions();
// define("START_EXEC_EVENTS_2", microtime());
$GLOBALS["BX_STATE"] = "EA";

foreach($arAllEvents as $arEvent)
	ExecuteModuleEventEx($arEvent);

if(!IsModuleInstalled("compression") && !defined('PUBLIC_AJAX_MODE') && ($_REQUEST["mode"] != 'excel'))
{
	if($bShowTime || $bShowStat || $bShowCacheStat)
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/interface/debug_info.php");
}

$DB->Disconnect();

CMain::ForkActions();
