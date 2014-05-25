#!#PHP_PATH# -q
<?php
$_SERVER["DOCUMENT_ROOT"] = "#DOCUMENT_ROOT#";

// define("NO_KEEP_STATISTIC", true);
// define("NOT_CHECK_PERMISSIONS",true);
// define("BX_CAT_CRON", true);
// define('NO_AGENT_CHECK', true);
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

$profile_id = $argv[1];

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

set_time_limit(0);

if (!defined("CATALOG_LOAD_NO_STEP"))
	// define("CATALOG_LOAD_NO_STEP", true);

if (CModule::IncludeModule("catalog"))
{
	$profile_id = intval($profile_id);
	if ($profile_id<=0) die();

	$ar_profile = CCatalogImport::GetByID($profile_id);
	if (!$ar_profile) die();

	$strFile = CATALOG_PATH2IMPORTS.$ar_profile["FILE_NAME"]."_run.php";
	if (!file_exists($_SERVER["DOCUMENT_ROOT"].$strFile))
	{
		$strFile = CATALOG_PATH2IMPORTS_DEF.$ar_profile["FILE_NAME"]."_run.php";
		if (!file_exists($_SERVER["DOCUMENT_ROOT"].$strFile))
		{
			die();
		}
	}

	$bFirstLoadStep = true;

	$arSetupVars = array();
	$intSetupVarsCount = 0;
	if ('Y' != $ar_profile["DEFAULT_PROFILE"])
	{
		parse_str($ar_profile["SETUP_VARS"], $arSetupVars);
		if (!empty($arSetupVars) && is_array($arSetupVars))
		{
			$intSetupVarsCount = extract($arSetupVars, EXTR_SKIP);
		}
	}

	CCatalogDiscountSave::Disable();
	include($_SERVER["DOCUMENT_ROOT"].$strFile);
	CCatalogDiscountSave::Enable();

	CCatalogImport::Update($profile_id, array(
		"=LAST_USE" => $DB->GetNowFunction()
		)
	);
}
?>