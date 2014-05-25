<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?
// РЎРєРѕРїРёСЂСѓР№С‚Рµ СЌС‚РѕС‚ С„Р°Р№Р» РІ РїР°РїРєСѓ /bitrix/php_interface/include/sale_payment/ Рё
// Р·Р°РґР°Р№С‚Рµ РїСѓС‚СЊ Рє РЅРµРјСѓ РІ РЅР°СЃС‚СЂРѕР№РєР°С… РїР»Р°С‚РµР¶РЅРѕР№ СЃРёСЃС‚РµРјС‹
// Р’С‹ РјРѕР¶РµС‚Рµ РёР·РјРµРЅРёС‚СЊ СЌС‚РѕС‚ С„Р°Р№Р» РїРѕ СЃРІРѕРµРјСѓ СѓСЃРјРѕС‚СЂРµРЅРёСЋ

// define("NO_KEEP_STATISTIC", true);
// define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
if (CModule::IncludeModule("sale"))
{
	if ($_SERVER["REQUEST_METHOD"] == "POST")
	{
		$bCorrectPayment = True;

		$SERVER_NAME_tmp = "";
		if (defined("SITE_SERVER_NAME"))
			$SERVER_NAME_tmp = SITE_SERVER_NAME;
		if (strlen($SERVER_NAME_tmp)<=0)
			$SERVER_NAME_tmp = COption::GetOptionString("main", "server_name", "");

		if (!($arOrder = CSaleOrder::GetByID(IntVal($_POST["LMI_PAYMENT_NO"]))))
			$bCorrectPayment = False;

		if ($bCorrectPayment)
			CSalePaySystemAction::InitParamArrays($arOrder, $arOrder["ID"]);

		$CNST_SECRET_KEY = CSalePaySystemAction::GetParamValue("CNST_SECRET_KEY");
		$CNST_PAYEE_PURSE = CSalePaySystemAction::GetParamValue("ACC_NUMBER");

		$strCheck = md5($_POST["pci_wmtid"].$_POST["WMID"].md5(ToUpper("http://".$SERVER_NAME_tmp.(CSalePaySystemAction::GetParamValue("PATH_TO_RESULT"))."?ORDER_ID=".$_REQUEST["ORDER_ID"].$CNST_PAYEE_PURSE.round($arOrder["PRICE"], 2)."Order_".$ORDER_ID."")).$_POST["pci_pursesrc"].$_POST["pci_pursedest"].$_POST["pci_amount"].$_POST["pci_desc"].$_POST["pci_datecrt"].$_POST["pci_mode"].md5($CNST_SECRET_KEY));
		if ($_POST["pci_marker"] != $strCheck)
			$bCorrectPayment = False;

		if ($bCorrectPayment)
		{
			$strPS_STATUS_DESCRIPTION = "";
			if (strlen($_POST["pci_mode"]) > 0)
				$strPS_STATUS_DESCRIPTION .= "С‚РµСЃС‚РѕРІС‹Р№ СЂРµР¶РёРј, СЂРµР°Р»СЊРЅРѕ РґРµРЅСЊРіРё РЅРµ РїРµСЂРµРІРѕРґРёР»РёСЃСЊ; ";
			$strPS_STATUS_DESCRIPTION .= "РєРѕС€РµР»РµРє РїСЂРѕРґР°РІС†Р° - ".$_POST["pci_pursedest"]."; ";
			$strPS_STATUS_DESCRIPTION .= "РЅРѕРјРµСЂ РѕРїРµСЂР°С†РёРё - ".$_POST["pci_wmtid"]."; ";
			$strPS_STATUS_DESCRIPTION .= "РґР°С‚Р° РїР»Р°С‚РµР¶Р° - ".$_POST["pci_datecrt"]."";

			$strPS_STATUS_MESSAGE = "";
			$strPS_STATUS_MESSAGE .= "РєРѕС€РµР»РµРє РїРѕРєСѓРїР°С‚РµР»СЏ - ".$_POST["pci_pursesrc"]."; ";
			$strPS_STATUS_MESSAGE .= "WMId РїРѕРєСѓРїР°С‚РµР»СЏ - ".$_POST["WMID"]."; ";
			$strPS_STATUS_MESSAGE .= "".$_POST["pci_desc"]."";

			$arFields = array(
					"PS_STATUS" => "Y",
					"PS_STATUS_CODE" => "-",
					"PS_STATUS_DESCRIPTION" => $strPS_STATUS_DESCRIPTION,
					"PS_STATUS_MESSAGE" => $strPS_STATUS_MESSAGE,
					"PS_SUM" => $_POST["pci_amount"],
					"PS_CURRENCY" => $arOrder["CURRENCY"],
					"PS_RESPONSE_DATE" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
					"USER_ID" => $arOrder["USER_ID"]
				);

			// You can comment this code if you want PAYED flag not to be set automatically
			if ($arOrder["PRICE"] == $_POST["pci_amount"] 
				&& $CNST_PAYEE_PURSE == $_POST["pci_pursedest"])
			{
				CSaleOrder::PayOrder($arOrder["ID"], "Y");
			}

			CSaleOrder::Update($arOrder["ID"], $arFields);
		}
	}
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>