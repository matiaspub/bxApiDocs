<?php
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../../../..");

// define("NO_KEEP_STATISTIC", true);
// define("NOT_CHECK_PERMISSIONS",true);
@set_time_limit (0);
@ignore_user_abort(true);
//// define("LANG","en");

// define("BX_CRONTAB", true);

$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(CModule::IncludeModule("controller"))
{
	CControllerTask::ProcessAllTask();
}
//echo 'OK';
?>