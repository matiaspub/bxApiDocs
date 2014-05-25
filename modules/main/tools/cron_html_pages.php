<?php
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../../../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

// define("NO_KEEP_STATISTIC", true);
// define("NOT_CHECK_PERMISSIONS",true);
// define("BX_CRONTAB", true);
// define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

@set_time_limit(0);
@ignore_user_abort(true);

$hours = is_array($argv) && count($argv) > 1 && intval($argv[1]) > 0 ? intval($argv[1]) : 24;
$validTime = time() - $hours * 60 * 60;
$bytes = CHTMLPagesCache::deleteRecursive("/", $validTime);
CHTMLPagesCache::updateQuota(-$bytes);