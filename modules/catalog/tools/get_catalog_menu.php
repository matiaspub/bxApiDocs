<?
// define("NO_KEEP_STATISTIC", true);
// define("NO_AGENT_STATISTIC", true);
// define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
if (CModule::IncludeModule('catalog'))
{
	$adminMenu = new CCatalogMenu();
	$adminMenu->AddOpenedSections($_REQUEST["admin_mnu_menu_id"]);
	$adminMenu->Init(array($_REQUEST["admin_mnu_module_id"]));
	$adminMenu->ShowSubmenu($_REQUEST["admin_mnu_menu_id"], $_REQUEST["admin_mnu_url_back"]);
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");