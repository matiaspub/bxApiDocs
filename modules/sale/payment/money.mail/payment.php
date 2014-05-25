<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?
include(GetLangFileName(dirname(__FILE__)."/", "/money_mail.php"));
function PrepareParams(&$item)
{
	$item = rawurlencode($item);
}

$message = "";
$invoice_number="";
$arParams = Array();
$ORDER_ID =(strlen(CSalePaySystemAction::GetParamValue("ORDER_ID")) > 0) ? CSalePaySystemAction::GetParamValue("ORDER_ID") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ID"];
$ORDER = CSaleOrder::GetByID($ORDER_ID);

if ($ORDER['PAY_VOUCHER_NUM']) 
	$invoice_number = $ORDER['PAY_VOUCHER_NUM'];
else
{
	$SITE_NAME = COption::GetOptionString("main", "server_name", "");
	$dateInsert = (strlen(CSalePaySystemAction::GetParamValue("DATE_INSERT")) > 0) ? CSalePaySystemAction::GetParamValue("DATE_INSERT") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DATE_INSERT"];
	$arParams['issuer_id'] = base64_encode($ORDER_ID);
	$arParams['access_key'] = (strlen(CSalePaySystemAction::GetParamValue("KEY")) > 0) ? CSalePaySystemAction::GetParamValue("KEY") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["KEY"];
	$arParams['shouldPay'] = (strlen(CSalePaySystemAction::GetParamValue("SHOULD_PAY")) > 0) ? CSalePaySystemAction::GetParamValue("SHOULD_PAY") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"];
	$arParams['buyer_email'] = (strlen(CSalePaySystemAction::GetParamValue("BUYER_EMAIL")) > 0) ? CSalePaySystemAction::GetParamValue("BUYER_EMAIL") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"];
	//$arParams['currency'] = (strlen(CSalePaySystemAction::GetParamValue("CURRENCY")) > 0) ? CSalePaySystemAction::GetParamValue("CURRENCY") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"];
	if (strlen(CSalePaySystemAction::GetParamValue("TEST_MODE")))
		$arParams['currency'] = CSalePaySystemAction::GetParamValue("TEST_MODE");
	else
		$arParams['currency'] = "RUR";	

	$arParams['buyer_ip'] = $_SERVER['REMOTE_ADDR'];
    $arParams['description'] = base64_encode((ToUpper(SITE_CHARSET) != ToUpper('windows-1251')) ? $APPLICATION->ConvertCharset(GetMessage("MM_DESC",Array('#ORDER_ID#' => $ORDER_ID, '#DATE#' => $dateInsert, '#SITE_NAME#' => $SITE_NAME)), SITE_CHARSET, 'windows-1251') : GetMessage("MM_DESC", Array('#ORDER_ID#' => $ORDER_ID, '#DATE#' => $dateInsert, '#SITE_NAME#' => $SITE_NAME)));
	array_walk($arParams, 'PrepareParams');

	$sHost = "merchant.money.mail.ru";
	$sUrl = "/api/invoice/make";
	$sVars ="key=".$arParams['access_key']."&buyer_email=".$arParams['buyer_email']."&sum=".(str_replace(",", ".", $arParams['shouldPay']))."&currency=".$arParams['currency']."&description=".$arParams['description']."&buyer_ip=".$arParams['buyer_ip']."&issuer_id=".$arParams['issuer_id'];
	$invoice_number = QueryGetData($sHost, 443, $sUrl, $sVars, $errno, $errstr, "GET", "ssl://");
	if (is_numeric($invoice_number)) 
		CSaleOrder::Update($ORDER_ID, Array('PAY_VOUCHER_NUM' => $invoice_number, 'PAY_VOUCHER_DATE' => $dateInsert));	
}

if (is_numeric($invoice_number))
{
    $message.=GetMessage('MM_INVOICE_NUM', Array('#INVOICE_NUM#' => $invoice_number));
	
	$sHost = "merchant.money.mail.ru";
	$sUrl = "/api/invoice/item/";
	$access_key = rawurlencode((strlen(CSalePaySystemAction::GetParamValue("KEY")) > 0) ? CSalePaySystemAction::GetParamValue("KEY") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["KEY"]);
	$sVars = "key=".$access_key."&invoice_number=".$invoice_number;
	sleep(1);
	$CheckStatus = QueryGetData($sHost, 443, $sUrl, $sVars, $errno, $errstr, "GET", "ssl://");
		
	if ($CheckStatus)
	{			
		parse_str(str_replace(Array("\r\n","\n","\r"), "&", 'success='.$CheckStatus), $Data);
		$default_url = 'https://money.mail.ru';
		if ($Data['success'] == 'OK')
		{
			switch($Data['status'])
			{
				case 'NEW':
					$message .= GetMessage('MM_PAY_NEW_INVOICE', Array('#URL#' => $default_url));
				break;
				case 'DELIVERED': 
					$message .= GetMessage('MM_PAY_DELIVERED_INVOICE', Array('#URL#' => $Data['url_pay']));
				break;
				case 'PAID':
					$message.=GetMessage('MM_ALLREADY_PAID');
				break;
				case 'REJECTED':
					$message.=GetMessage('MM_REJECT_PAY');
				break;
			}
		} 
									   
								
	}
			
}
if (strlen($message) <= 0)
	echo GetMessage('MM_ERROR_TRY_LATER');
else 
	echo $message;
?>