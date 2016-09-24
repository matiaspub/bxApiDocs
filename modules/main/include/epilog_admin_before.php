<?
/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @global CUserTypeManager $USER_FIELD_MANAGER
 * @global CCacheManager $CACHE_MANAGER
 */

// define("START_EXEC_EPILOG_BEFORE_1", microtime());
$GLOBALS["BX_STATE"] = "EB";

if($USER->IsAuthorized() && (!defined("BX_AUTH_FORM") || !BX_AUTH_FORM))
{
	$hkInstance = CHotKeys::getInstance();

	$Execs=$hkInstance->GetCodeByClassName("Global");
	echo $hkInstance->PrintJSExecs($Execs);
	echo $hkInstance->SetTitle("Global");

	$Execs=$hkInstance->GetCodeByUrl($_SERVER["REQUEST_URI"]);

	echo $hkInstance->PrintJSExecs($Execs);
	echo $hkInstance->PrintPhpToJSVars();

	echo CAdminInformer::PrintHtml();

	if(
		$USER->IsAuthorized()
		&& (!defined('BX_PUBLIC_MODE') || BX_PUBLIC_MODE != 1)
		&& (!isset($_SESSION["SS_B24NET_STATE"]) || $_SESSION["SS_B24NET_STATE"] !== $USER->GetID())
		&& \Bitrix\Main\ModuleManager::isModuleInstalled("socialservices")
		&& \Bitrix\Main\Config\Option::get("socialservices", "bitrix24net_id", "") != ""
	)
	{
		if(
			\Bitrix\Main\Loader::includeModule("socialservices")
			&& class_exists("Bitrix\\Socialservices\\Network")
			&& method_exists("Bitrix\\Socialservices\\Network", "displayAdminPopup")
		)
		{
			\Bitrix\Socialservices\Network::displayAdminPopup(array(
				"SHOW" => true,
			));
		}
	}
}

if (!defined('BX_PUBLIC_MODE') || BX_PUBLIC_MODE != 1)
{
	if (!defined("BX_AUTH_FORM") || !BX_AUTH_FORM)
		require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/epilog_main_admin.php");
	else
		require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/epilog_auth_admin.php");
}
else
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/epilog_jspopup_admin.php");

?>
