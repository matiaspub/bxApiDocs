<?
// define("STOP_STATISTICS", true);

if (($_GET["admin_section"]=="Y") && (!defined("ADMIN_SECTION")))
	// define("ADMIN_SECTION", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/include.php");

$MAIN_RIGHT = $APPLICATION->GetGroupRight("main");
if($MAIN_RIGHT<"R") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
if (CModule::IncludeModule("forum"))
{
	$arTopic = CForumTopic::GetByID($ID);
}
if ($arTopic)
	$res = "[<a class='tablebodylink' href='/bitrix/admin/forum_topics.php?lang=".LANG."'>".intVal($arTopic["ID"])."</a>] (".htmlspecialcharsbx($arTopic["TITLE"]).") ";
else
	$res = "&nbsp;".GetMessage("MAIN_NOT_FOUND");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
?><script language="JavaScript">
<!--
window.parent.document.getElementById("div_<?=CUtil::JSEscape($strName)?>").innerHTML='<?=CUtil::JSEscape($res)?>';
//-->
</script><?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");
?>
