<?
/**********************************************************************/
/**    DO NOT MODIFY THIS FILE                                       **/
/**    MODIFICATION OF THIS FILE WILL ENTAIL SITE FAILURE            **/
/**********************************************************************/
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
// define("HELP_FILE", "marketplace/sysupdate.php");

if (!function_exists('htmlspecialcharsbx'))
{
	function htmlspecialcharsbx($string, $flags=ENT_COMPAT)
	{
		//shitty function for php 5.4 where default encoding is UTF-8
		return htmlspecialchars($string, $flags, (defined("BX_UTF")? "UTF-8" : "ISO-8859-1"));
	}
}

if(!$USER->CanDoOperation('view_other_settings') && !$USER->CanDoOperation('install_updates'))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

IncludeModuleLangFile(__FILE__);

$sTableID = "tbl_update_log";
$oSort = new CAdminSorting($sTableID, "date", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);

$lAdmin->AddHeaders(array(
	array("id"=>"DESCRIPTION",	"content"=>GetMessage("SUP_HIST_DESCR"), "sort"=>"description", "default"=>true),
	array("id"=>"DATE",	"content"=>GetMessage("SUP_HIST_DATE"), "sort"=>"date", "default"=>true),
	array("id"=>"SUCCESS",	"content"=>GetMessage("SUP_HIST_STATUS"), "sort"=>"success", "default"=>true),
));

$arLogRecs = array();
if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/updater.log")
	&& is_file($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/updater.log")
	&& is_readable($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/updater.log"))
{
	$logf = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/updater.log", "r");
	while (!feof($logf))
	{
		$buffer = fgets($logf, 8192);
		$rec = false;
		if (substr($buffer, strlen("0000-00-00 00:00:00 "), strlen("- UPD_SUCCESS -"))=="- UPD_SUCCESS -")
		{
			$rec = array(
				"S",
				substr($buffer, 0, strlen("0000-00-00 00:00:00")),
				substr($buffer, strlen("0000-00-00 00:00:00 - UPD_SUCCESS - "))
			);
		}
		elseif (substr($buffer, strlen("0000-00-00 00:00:00 "), strlen("- UPD_ERROR -"))=="- UPD_ERROR -")
		{
			$rec = array(
				"E",
				substr($buffer, 0, strlen("0000-00-00 00:00:00")),
				substr($buffer, strlen("0000-00-00 00:00:00 - UPD_ERROR - "))
			);
		}
		elseif (substr($buffer, strlen("0000-00-00 00:00:00 "), strlen("- UPD_NOTE -"))=="- UPD_NOTE -")
		{
			$rec = array(
				"N",
				substr($buffer, 0, strlen("0000-00-00 00:00:00")),
				substr($buffer, strlen("0000-00-00 00:00:00 - UPD_NOTE - "))
			);
		}
		if($rec)
		{
			$rec[3] = "";
			$pos1 = strpos($rec[2], "<br>");
			if($pos1 !== false)
			{
				$rec[3] = trim(substr($rec[2], $pos1 + 4));
				$rec[3] = str_replace('\"', '&quot;', $rec[3]);
				$rec[2] = substr($rec[2], 0, $pos1);
			}
			$arLogRecs[] = $rec;
		}
	}
	fclose($logf);

	$by = strtoupper($by);
	if($by == "SUCCESS")
		$sort = 0;
	elseif($by == "DESCRIPTION")
		$sort = 2;
	else
		$sort = 1;
	if(strtoupper($order) == "ASC")
		$ord = 1;
	else
		$ord = -1;
	usort($arLogRecs, create_function('$a, $b', 'return strcmp($a['.$sort.'], $b['.$sort.'])*('.$ord.');'));
}

$rsData = new CAdminResult(null, $sTableID);
$rsData->InitFromArray($arLogRecs);
$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("update_log_nav")));

$n = 0;
while($rec = $rsData->Fetch())
{
	$row = &$lAdmin->AddRow(0, null);

	$aDate = explode(" ", htmlspecialcharsbx($rec[1]));
	$row->AddField("DATE", '<span style="white-space:nowrap">'.$aDate[0].'</span> '.$aDate[1]);

	$row->AddField("DESCRIPTION", ($rec[3]<>""? '<a href="javascript:void(0)" onClick="jsUtils.ToggleDiv(\'descr_'.$n.'\')" title="'.GetMessage("HINT_WIND_EXEC_ALT").'">'.htmlspecialcharsbx($rec[2]).'</a>' : htmlspecialcharsbx($rec[2])).'<div id="descr_'.$n.'" style="display:none;">'.$rec[3].'</div>');

	$s = "";
	if($rec[0]=="S")
		$s = '<div class="lamp-green" style="float:left"></div>'.GetMessage("SUP_HIST_SUCCESS");
	elseif($rec[0]=="E")
		$s = '<div class="lamp-red" style="float:left"></div>'.GetMessage("SUP_HIST_ERROR");
	elseif($rec[0]=="N")
		$s = '<div class="lamp-yellow" style="float:left"></div>'.GetMessage("SUP_HIST_NOTES");
	$row->AddField("SUCCESS", $s);
	
	$n++;
}

$aMenu = array(
	array(
		"TEXT"=>GetMessage("update_log_index"),
		"TITLE"=>GetMessage("update_log_index_title"),
		"LINK"=>"update_system.php?lang=".LANGUAGE_ID,
		"ICON"=>"btn_update",
	),
);
$lAdmin->AddAdminContextMenu($aMenu);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("update_log_title"));
$APPLICATION->SetAdditionalCSS("/bitrix/themes/".ADMIN_THEME_ID."/sysupdate.css");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>

<?$lAdmin->DisplayList();?>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
