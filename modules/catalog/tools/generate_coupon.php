<?
// define("STOP_STATISTICS", true);
// define("BX_SECURITY_SHOW_MESSAGE", true);
// define('NO_AGENT_CHECK', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

global $USER;

$arResult = array(
	'STATUS' => 'OK',
	'MESSAGE' => '',
	'RESULT' => '',
);
$boolFlag = true;

IncludeModuleLangFile(__FILE__);
if ($boolFlag)
{
	if (!isset($USER) || !(($USER instanceof CUser) && ('CUser' == get_class($USER))))
	{
		$arResult['STATUS'] = 'ERROR';
		$arResult['MESSAGE'] = GetMessage('BT_CAT_TOOLS_GEN_CPN_ERR_USER');
		$boolFlag = false;
	}
	elseif (!$USER->IsAuthorized())
	{
		$arResult['STATUS'] = 'ERROR';
		$arResult['MESSAGE'] = GetMessage('BT_CAT_TOOLS_GEN_CPN_ERR_AUTH');
		$boolFlag = false;
	}
}

if ($boolFlag)
{
	if (!check_bitrix_sessid())
	{
		$arResult['STATUS'] = 'ERROR';
		$arResult['MESSAGE'] = GetMessage('BT_CAT_TOOLS_GEN_CPN_ERR_SESSION');
		$boolFlag = false;
	}
}
if ($boolFlag)
{
	if (!$USER->CanDoOperation('catalog_discount'))
	{
		$arResult['STATUS'] = 'ERROR';
		$arResult['MESSAGE'] = GetMessage('BT_CAT_TOOLS_GEN_CPN_ERR_RIGHTS');
		$boolFlag = false;
	}
}

if ($boolFlag)
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/include.php");

	do
	{
		$strCoupon = substr(CatalogGenerateCoupon(), 0, 32);
		$boolCheck = !CCatalogDiscountCoupon::IsExistCoupon($strCoupon);
	}
	while (!$boolCheck);

	$arResult['RESULT'] = $strCoupon;
}

echo CUtil::PhpToJSObject($arResult);
?>