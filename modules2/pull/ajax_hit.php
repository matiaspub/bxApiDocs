<?
if($_SERVER["REQUEST_METHOD"] == "POST" && array_key_exists("PULL_AJAX_CALL", $_REQUEST) && $_REQUEST["PULL_AJAX_CALL"] === "Y")
{
	$arResult = array();
	global $USER, $APPLICATION, $DB;
	include($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/pull.request/ajax.php");
	die();
}
else if (!defined('BX_SKIP_PULL_INIT') && !(isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
		&& intval($GLOBALS['USER']->GetID()) > 0 && CModule::IncludeModule('pull') && CPullOptions::CheckNeedRun())
{
	// define("BX_SKIP_PULL_INIT", true);
	CJSCore::Init(array('pull'));

	global $APPLICATION;
	$jsMsg = '<script type="text/javascript">BX.PULL.start('.(defined('BX_PULL_SKIP_LS')? "{LOCAL_STORAGE: 'N'}": '').');</script>';
	if($GLOBALS['APPLICATION']->IsJSOptimized())
		$APPLICATION->AddAdditionalJS($jsMsg);
	else
		$APPLICATION->AddHeadString($jsMsg);
}
?>
