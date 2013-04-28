<?
// define("START_EXEC_PROLOG_AFTER_1", microtime());
$GLOBALS["BX_STATE"] = "PA";

if(!headers_sent())
	header("Content-type: text/html; charset=".LANG_CHARSET);

if(defined("DEMO") && DEMO=="Y")
{
	if(OLDSITEEXPIREDATE != SITEEXPIREDATE)
		die(GetMessage("expire_mess2"));

	//wizard customization file
	$bxProductConfig = array();
	if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/.config.php"))
		include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/.config.php");

	$delta = $SiteExpireDate-time();
	$daysToExpire = ($delta < 0? 0 : ceil($delta/86400));
	$bSaas = (COption::GetOptionString('main', '~SAAS_MODE', "N") == "Y");

	if(isset($bxProductConfig["saas"]))
	{
		if($bSaas)
		{
			if($daysToExpire > 0)
			{
				if($daysToExpire <= $bxProductConfig["saas"]["days_before_warning"])
				{
					$sWarn = $bxProductConfig["saas"]["public_warning"];
					$sWarn = str_replace("#RENT_DATE#", COption::GetOptionString('main', '~support_finish_date'), $sWarn);
					$sWarn = str_replace("#DAYS#", $daysToExpire, $sWarn);
					echo $sWarn;
				}
			}
			else
			{
				echo str_replace("#RENT_DATE#", COption::GetOptionString('main', '~support_finish_date'), $bxProductConfig["saas"]["public_warning_expired"]);
			}
		}
		else
		{
			if($daysToExpire == 0)
				echo $bxProductConfig["saas"]["public_trial_expired"];
		}
	}
	elseif($daysToExpire == 0)
	{
		echo GetMessage("expire_mess1");
	}
}

if(COption::GetOptionString("main", "site_stopped", "N")=="Y" && !$GLOBALS["USER"]->CanDoOperation('edit_other_settings'))
{
	if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/".LANG."/site_closed.php"))
		include($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/".LANG."/site_closed.php");
	elseif(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/include/site_closed.php"))
		include($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/include/site_closed.php");
	else
		include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/site_closed.php");
	die();
}

$sPreviewFile = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/tmp/templates/__bx_preview/header.php";
if($_GET['bx_template_preview_mode'] == 'Y' && $USER->CanDoOperation('edit_other_settings') && file_exists($sPreviewFile))
	include_once($sPreviewFile);
else
{
	include_once($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".SITE_TEMPLATE_ID."/header.php");

	if($GLOBALS['APPLICATION']->IsCSSOptimized())
	{
		$arCSS = $APPLICATION->GetCSSArray();
		$arCSSKeys = array_keys($arCSS);
		$cntCSSKeys = count($arCSS);
		$APPLICATION->SetHeaderLastCss($arCSSKeys[$cntCSSKeys-1]);
		unset($arCSS, $arCSSKeys);
	}

	if($GLOBALS['APPLICATION']->IsJSOptimized())
	{
		$arScripts = array_unique($APPLICATION->arHeadScripts);
		$arJsKeys = array_keys($arScripts);
		$cntJsKeys = count($arScripts);
		$APPLICATION->SetHeaderLastJs($arJsKeys[$cntJsKeys-1]);
		unset($arScripts, $arJsKeys);
	}
}

/* Draw edit menu for whole content */
global $BX_GLOBAL_AREA_EDIT_ICON;
$BX_GLOBAL_AREA_EDIT_ICON = false;
if($GLOBALS['APPLICATION']->GetShowIncludeAreas())
{
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/init_admin.php");

	$aUserOpt = CUserOptions::GetOption("global", "settings", array());
	if ($aUserOpt["page_edit_control_enable"] != "N")
	{
		$documentRoot = CSite::GetSiteDocRoot(SITE_ID);
		if(isset($_SERVER["REAL_FILE_PATH"]) && $_SERVER["REAL_FILE_PATH"] != "")
			$currentFilePath = $_SERVER["REAL_FILE_PATH"];
		else
			$currentFilePath = $GLOBALS['APPLICATION']->GetCurPage(true);

		$bCanEdit = true;

		if(!is_file($documentRoot.$currentFilePath) || !$GLOBALS["USER"]->CanDoFileOperation("fm_edit_existent_file", array(SITE_ID, $currentFilePath)))
			$bCanEdit = false;

		//need fm_lpa for every .php file, even with no php code inside
		if($bCanEdit && !$GLOBALS["USER"]->CanDoOperation('edit_php') && in_array(GetFileExtension($currentFilePath), GetScriptFileExt()) && !$GLOBALS["USER"]->CanDoFileOperation('fm_lpa', array(SITE_ID, $currentFilePath)))
			$bCanEdit = false;

		if($bCanEdit && IsModuleInstalled("fileman") && !($GLOBALS["USER"]->CanDoOperation("fileman_admin_files") && $GLOBALS["USER"]->CanDoOperation("fileman_edit_existent_files")))
			$bCanEdit = false;

		if($bCanEdit)
		{
			echo $GLOBALS['APPLICATION']->IncludeStringBefore();
			$BX_GLOBAL_AREA_EDIT_ICON = true;
		}
	}
}
// define("START_EXEC_PROLOG_AFTER_2", microtime());
$GLOBALS["BX_STATE"] = "WA";
?>