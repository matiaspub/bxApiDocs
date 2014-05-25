<?
// define("ADMIN_SECTION", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header("Content-Type: text/xml");
CModule::IncludeModule("iblock");

if (IntVal($ID)>0)
{
	$ID = IntVal($ID);
}
else
{
	$ID = Trim($ID);
}
$LANG = Trim($_REQUEST["LANG"]);
$TYPE = Trim($TYPE);
$LIMIT = IntVal($LIMIT);

CIBlockRSS::GetRSS($ID, $LANG, $TYPE, $LIMIT, false, false);
?>