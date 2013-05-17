<?
// define("START_EXEC_EPILOG_AFTER_1", microtime());
$GLOBALS["BX_STATE"] = "EA";

if(!isset($USER))		{global $USER;}
if(!isset($APPLICATION)){global $APPLICATION;}
if(!isset($DB))			{global $DB;}

foreach(GetModuleEvents("main", "OnEpilog", true) as $arEvent)
	ExecuteModuleEventEx($arEvent);

$r = $APPLICATION->EndBufferContentMan();
$main_exec_time = round((getmicrotime()-START_EXEC_TIME), 4);
echo $r;

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
	$bShowTime = ($_SESSION["SESS_SHOW_TIME_EXEC"] == 'Y');
	$bShowStat = ($GLOBALS["DB"]->ShowSqlStat && $GLOBALS["USER"]->CanDoOperation('edit_php'));
	if($bShowTime || $bShowStat)
	{
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/interface/debug_info.php");
	}
}

$DB->Disconnect();

CMain::ForkActions();
?>