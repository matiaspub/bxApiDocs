<?
// define("NO_KEEP_STATISTIC", true);
// define("NO_AGENT_STATISTIC", true);
// define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if($USER->IsAdmin() && isset($_POST["ID"]) && check_bitrix_sessid())
{
	CAdminNotify::Delete(intval($_POST["ID"]));
}
echo "OK";
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_after.php");
?>
