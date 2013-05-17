<?
// define("NO_KEEP_STATISTIC", true);
// define("NO_AGENT_STATISTIC", true);
// define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");

$adminMenu->AddOpenedSections($_REQUEST["admin_mnu_menu_id"]);
$adminMenu->Init(array($_REQUEST["admin_mnu_module_id"]));
$adminMenu->ShowSubmenu($_REQUEST["admin_mnu_menu_id"]);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
?>
