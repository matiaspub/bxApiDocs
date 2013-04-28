<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// define("START_EXEC_PROLOG_AFTER_1", microtime());
$GLOBALS["BX_STATE"] = "PA";

if(!defined("BX_ROOT"))
	// define("BX_ROOT", "/bitrix");

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/init_admin.php");

if (!defined('BX_PUBLIC_MODE') || BX_PUBLIC_MODE != 1)
{
	if (!defined('BX_AUTH_FORM') || !BX_AUTH_FORM)
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/prolog_main_admin.php");
	else
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/prolog_auth_admin.php");
}
else
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/prolog_jspopup_admin.php");

// define("START_EXEC_PROLOG_AFTER_2", microtime());
$GLOBALS["BX_STATE"] = "WA";
?>