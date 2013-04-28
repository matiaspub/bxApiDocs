<?
if (!defined('BX_PUBLIC_MODE') || BX_PUBLIC_MODE != 1)
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/epilog_auth_admin.php");
else
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/epilog_auth_admin.php");


// define("START_EXEC_EPILOG_AFTER_1", microtime());
$GLOBALS["BX_STATE"] = "EA";

if(!isset($USER))		{global $USER;}
if(!isset($APPLICATION)){global $APPLICATION;}
if(!isset($DB))			{global $DB;}

$db_events = GetModuleEvents("main", "OnEpilog");
while($arEvent = $db_events->Fetch())
	ExecuteModuleEventEx($arEvent);

$r = $APPLICATION->EndBufferContentMan();
$main_exec_time = round((getmicrotime()-START_EXEC_TIME), 4);
echo $r;

$arAllEvents = Array();
$db_events = GetModuleEvents("main", "OnAfterEpilog");
while($arEvent = $db_events->Fetch())
	$arAllEvents[] = $arEvent;

// define("START_EXEC_EVENTS_1", microtime());
$GLOBALS["BX_STATE"] = "EV";
CMain::EpilogActions();
// define("START_EXEC_EVENTS_2", microtime());
$GLOBALS["BX_STATE"] = "EA";

for($i=0; $i<count($arAllEvents); $i++)
	ExecuteModuleEventEx($arAllEvents[$i]);

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