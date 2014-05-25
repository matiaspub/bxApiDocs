<?
##############################################
# Bitrix Site Manager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################

// define("NO_KEEP_STATISTIC", true);
// define("NO_AGENT_STATISTIC", true);
// define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if($USER->IsAuthorized() && check_bitrix_sessid())
{
	if($_GET["action"] == "delete" && $_GET["c"] <> "" && $_GET["n"] <> "")
		CUserOptions::DeleteOption($_GET["c"], $_GET["n"], ($_GET["common"]=="Y" && $GLOBALS["USER"]->CanDoOperation('edit_other_settings')));
	if(is_array($_REQUEST["p"]))
	{
		$arOptions = $_REQUEST["p"];
		CUtil::decodeURIComponent($arOptions);
		CUserOptions::SetOptionsFromArray($arOptions);
	}
}
echo "OK";
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_after.php");
?>
