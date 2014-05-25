<?
// define('BX_SECURITY_SHOW_MESSAGE', 1);
// define("NO_KEEP_STATISTIC", true);
// define("NOT_CHECK_FILE_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
/**
 * @var CMain $APPLICATION
 */

if($_REQUEST["manifest_id"])
{
	$appCache = \Bitrix\Main\Data\AppCacheManifest::getInstance();
	$data = $appCache->readManifestCache($_REQUEST["manifest_id"]);
	if($data && $data["TEXT"])
	{
		$APPLICATION->RestartBuffer();
		header('Content-Type: text/cache-manifest');
		echo $data["TEXT"];
		die();
	}
}

header("HTTP/1.0 404 Not Found");
die();
?>