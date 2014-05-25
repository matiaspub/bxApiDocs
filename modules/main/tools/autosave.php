<?
// define("STOP_STATISTICS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if(!$USER->IsAuthorized() || !check_bitrix_sessid())
	die();

CUtil::JSPostUnescape();

$arFormData = $_REQUEST['form_data'];

$AUTOSAVE = new CAutoSave();

if ($AUTOSAVE->Set($arFormData))
	echo time() + CTimeZone::GetOffset();
else
	echo 'FAILED';

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>