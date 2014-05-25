<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?
////////////////////////////////////////////////////////////////
//              РњРѕРґСѓР»СЊ Z-PAYMENT РґР»СЏ 1C-Bitrix                //
////////////////////////////////////////////////////////////////
//      Z-PAYMENT, СЃРёСЃС‚РµРјР° РїСЂРёРµРјР° Рё РѕР±СЂР°Р±РѕС‚РєРё РїР»Р°С‚РµР¶РµР№        //
//      All rights reserved В© 2002-2007, TRANSACTOR LLC       //
////////////////////////////////////////////////////////////////

// define("NO_KEEP_STATISTIC", true);
// define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$lmi_prerequest = $_REQUEST['LMI_PREREQUEST'];
$lmi_payee_purse = $_REQUEST['LMI_PAYEE_PURSE'];
$lmi_payment_amount = $_REQUEST['LMI_PAYMENT_AMOUNT'];
$lmi_payer_purse = $_REQUEST['LMI_PAYER_PURSE'];
$lmi_payer_wm = $_REQUEST['LMI_PAYER_WM'];
$lmi_payment_no = $_REQUEST['LMI_PAYMENT_NO'];
$lmi_mode = $_REQUEST['LMI_MODE'];
$id_pay = $_REQUEST['ID_PAY'];
$client_mail = $_REQUEST['CLIENT_MAIL'];
$custom = $_REQUEST['custom'];
$lmi_sys_trans_no = $_REQUEST['LMI_SYS_TRANS_NO'];
$lmi_sys_invs_no = $_REQUEST['LMI_SYS_INVS_NO'];
$lmi_sys_trans_date = $_REQUEST['LMI_SYS_TRANS_DATE'];
$lmi_hash = $_REQUEST['LMI_HASH'];
$lmi_secret_key = $_REQUEST['LMI_SECRET_KEY'];


if (CModule::IncludeModule("sale"))
{
	$bCorrectPayment = True;

	$err=0;
	$err_text= '';

	if ($arOrder = CSaleOrder::GetByID(IntVal($lmi_payment_no)))	
	{
		$bCorrectPayment = False;
		$err=1;
		$err_text= 'ERR: РќР•Рў РўРђРљРћР“Рћ Р—РђРљРђР—Рђ';
	}

	if ($bCorrectPayment) 
		CSalePaySystemAction::InitParamArrays($arOrder, $arOrder["ID"]);

	$IdM = CSalePaySystemAction::GetParamValue("ZP_SHOP_ID");
	$sk  = CSalePaySystemAction::GetParamValue("ZP_MERCHANT_KEY");
	$CruR  = CSalePaySystemAction::GetParamValue("ZP_CODE_RUR");

	// РџСЂРѕРІРµСЂСЏРµРј, РЅРµ РїСЂРѕРёР·РѕС€Р»Р° Р»Рё РїРѕРґРјРµРЅР° СЃСѓРјРјС‹.
	$order_amount =CCurrencyRates::ConvertCurrency($arOrder["PRICE"], $arOrder["CURRENCY"] , $CruR);

	if ($order_amount != $lmi_payment_amount)
	{
		$err=2;
		$err_text='ERR: РќР•Р’Р•Р РќРђРЇ РЎРЈРњРњРђ : '.$lmi_payment_amount;
	}  

	//РїСЂРѕРІРµСЂСЏРµРј ID РјР°РіР°Р·РёРЅР°
	if($lmi_payee_purse != $IdM) 
	{
		$err=3;
		$err_text='ERR: РќР•Р’Р•Р Р•Рќ ID РњРђР“РђР—Р?РќРђ : '.$lmi_payee_purse;
	}


	if($lmi_prerequest == 1) //С„РѕСЂРјР° РїСЂРµРґРІР°СЂРёС‚РµР»СЊРЅРѕРіРѕ Р·Р°РїСЂРѕСЃР°
	{ 
		if ($err != 0) 
			echo $err_text; 
		else 
			echo 'YES';
	}
	else 
	{


		$common_string = $lmi_payee_purse.$lmi_payment_amount.$lmi_payment_no.$lmi_mode.$lmi_sys_invs_no.$lmi_sys_trans_no.$lmi_sys_trans_date.$sk.$lmi_payer_purse.$lmi_payer_wm;
				$hash =ToUpper(md5($common_string));

	if ($err==0)	{
						if ($hash == $lmi_hash) 
										{
									$strPS_STATUS_DESCRIPTION = "";
										$strPS_STATUS_DESCRIPTION .= "Р?РґРµРЅС‚РёС„РёРєР°С‚РѕСЂ РјР°РіР°Р·РёРЅР° - ".$lmi_payee_purse."; ";
									$strPS_STATUS_DESCRIPTION .= "Р’РЅСѓС‚СЂРµРЅРЅРёР№ РЅРѕРјРµСЂ РїР»Р°С‚РµР¶Р°  РІ СЃРёСЃС‚РµРјРµ Z-PAYMENT - ".$lmi_sys_invs_no."; ";
									$strPS_STATUS_DESCRIPTION .= "Р’РЅСѓС‚СЂРµРЅРЅРёР№ РЅРѕРјРµСЂ СЃС‡РµС‚Р° РІ СЃРёСЃС‚РµРјРµ Z-PAYMENT - ".$lmi_sys_trans_no."; ";
									$strPS_STATUS_DESCRIPTION .= "РґР°С‚Р° РїР»Р°С‚РµР¶Р° - ".$lmi_sys_trans_date."";

									$strPS_STATUS_MESSAGE = "";
									$strPS_STATUS_MESSAGE .= "РєРѕС€РµР»РµРє РїРѕРєСѓРїР°С‚РµР»СЏ РёР»Рё РµРіРѕ e-mail  - ".$lmi_payer_purse."; ";

										$arFields = array(
													"PS_STATUS" => "Y",
													"PS_STATUS_CODE" => "-",
												"PS_STATUS_DESCRIPTION" => $strPS_STATUS_DESCRIPTION,
												"PS_STATUS_MESSAGE" => $strPS_STATUS_MESSAGE,
													"PS_SUM" => $lmi_payment_amount,
												"PS_CURRENCY" => $arOrder["CURRENCY"],
												"PS_RESPONSE_DATE" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
													"USER_ID" => $arOrder["USER_ID"]
													);

			// You can comment this code if you want PAYED flag not to be set automatically
			
				CSaleOrder::PayOrder($arOrder["ID"], "Y");
				CSaleOrder::Update($arOrder["ID"], $arFields);
												}
					}
	}
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>