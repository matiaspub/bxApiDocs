<?
// define("NO_KEEP_STATISTIC", true);
// define("NOT_CHECK_FILE_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

header("Content-Type: application/x-javascript; charset=".LANG_CHARSET);
echo CUtil::PhpToJSObject(CJSCore::GetCoreMessages());
die();