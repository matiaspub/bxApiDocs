<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?
include(GetLangFileName(dirname(__FILE__)."/", "/payment.php"));
if(!function_exists("bx_hmac"))
{
	function bx_hmac($algo, $data, $key, $raw_output = false) 
	{ 
		$algo = strtolower($algo); 
		$pack = "H".strlen($algo("test")); 
		$size = 64; 
		$opad = str_repeat(chr(0x5C), $size); 
		$ipad = str_repeat(chr(0x36), $size); 

		if (strlen($key) > $size) { 
			$key = str_pad(pack($pack, $algo($key)), $size, chr(0x00)); 
		} else { 
			$key = str_pad($key, $size, chr(0x00)); 
		} 

		$lenKey = strlen($key) - 1;
		for ($i = 0; $i < $lenKey; $i++) { 
			$opad[$i] = $opad[$i] ^ $key[$i]; 
			$ipad[$i] = $ipad[$i] ^ $key[$i]; 
		} 

		$output = $algo($opad.pack($pack, $algo($ipad.$data))); 
		return ($raw_output) ? pack($pack, $output) : $output; 
	} 
}

$p_terminal = $_POST["TERMINAL"];
$p_trtype = $_POST["TRTYPE"];
$p_order = $_POST["ORDER"];
$p_amount = $_POST["AMOUNT"];
$p_currency = $_POST["CURRENCY"];
$p_action = $_POST["ACTION"];
$p_rc = $_POST["RC"];
$p_approval = $_POST["APPROVAL"];
$p_rrn = $_POST["RRN"];
$p_int_ref = $_POST["INT_REF"];
$p_tm = $_POST["TIMESTAMP"];
$p_cardbin = $_POST["CARDBIN"];
$p_nonce = $_POST["NONCE"];
$p_sign = $_POST["P_SIGN"];
$p_extcode = $_POST["EXTCODE"];

$bError = true;

if($arOrder = CSaleOrder::GetByID(IntVal($p_order)))
{
	CSalePaySystemAction::InitParamArrays($arOrder, $arOrder["ID"]);
	
	$amount = CSalePaySystemAction::GetParamValue("SHOULD_PAY"); 
	$amount = number_format($amount, 2, ".", "");
	$currency = CSalePaySystemAction::GetParamValue("CURRENCY"); 
	if(strlen($currency) <= 0)
		$currency = "UAH";

	$order = CSalePaySystemAction::GetParamValue("ORDER_ID"); 
	if(strlen($order) < 6)
	{
		$n = 6-strlen($order);
		for($i = 0; $i < $n; $i++)
			$order = "0".$order;
	}

	$desc = trim(CSalePaySystemAction::GetParamValue("ORDER_DESC").CSalePaySystemAction::GetParamValue("ORDER_ID")); 
	$merchant = CSalePaySystemAction::GetParamValue("MERCHANT");
	$terminal = CSalePaySystemAction::GetParamValue("TERMINAL");
	$email = CSalePaySystemAction::GetParamValue("EMAIL"); 
	$mac = CSalePaySystemAction::GetParamValue("MAC");
	$PAY_OK = str_replace("#ID#", $arOrder["ID"], CSalePaySystemAction::GetParamValue("PAY_OK"));
	$PAY_ERROR = str_replace("#ID#", $arOrder["ID"], CSalePaySystemAction::GetParamValue("PAY_ERROR"));
	$ALLOW_DELIVERY = CSalePaySystemAction::GetParamValue("ALLOW_DELIVERY");

	if(strlen(CSalePaySystemAction::GetParamValue("IS_TEST")) > 0)
		$server_url = "/cgi-bin/cgi_test";
	else
		$server_url = "/cgi-bin/cgi_link";

	$key = pack("H*", $mac);   
	
	$sign = ToUpper(bx_hmac("sha1", 
		(strlen($p_rrn) > 0 ? strlen($p_rrn).$p_rrn : "-").
		(strlen($p_int_ref) > 0 ? strlen($p_int_ref).$p_int_ref : "-").
		(strlen($p_terminal) > 0 ? strlen($p_terminal).$p_terminal : "-").
		(strlen($p_trtype) > 0 ? strlen($p_trtype).$p_trtype : "-").
		(strlen($p_order) > 0 ? strlen($p_order).$p_order : "-").
		(strlen($p_amount) > 0 ? strlen($p_amount).$p_amount : "-").
		(strlen($p_currency) > 0 ? strlen($p_currency).$p_currency : "-").
		(strlen($p_action) > 0 ? strlen($p_action).$p_action : "-").
		(strlen($p_rc) > 0 ? strlen($p_rc).$p_rc : "-").
		(strlen($p_approval) > 0 ? strlen($p_approval).$p_approval : "-").
		(strlen($p_tm) > 0 ? strlen($p_tm).$p_tm : "-").
		(strlen($p_nonce) > 0 ? strlen($p_nonce).$p_nonce : "-")
		, 
		$key
		));

	$strPS_STATUS_DESCRIPTION = "";
	$strPS_STATUS_DESCRIPTION .= "ACTION: ".$p_action."; ";
	$strPS_STATUS_DESCRIPTION .= "RC: ".$p_rc."; ";
	$strPS_STATUS_DESCRIPTION .= "APPROVAL: ".$p_approval."; ";
	$strPS_STATUS_DESCRIPTION .= "RRN: ".$p_rrn."; ";
	$strPS_STATUS_DESCRIPTION .= "INT_REF: ".$p_int_ref."; ";

	$arFields = array(
			"PS_STATUS" => "N",
			"PS_STATUS_CODE" => $p_action,
			"PS_STATUS_DESCRIPTION" => $strPS_STATUS_DESCRIPTION,
			"PS_STATUS_MESSAGE" => "",
			"PS_SUM" => $p_amount,
			"PS_CURRENCY" => $p_currency,
			"PS_RESPONSE_DATE" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
		);
	if(strlen($p_extcode) > 0 && $p_extcode != "NONE")
		$arFields["PS_STATUS_MESSAGE"] .= GetMessage("EXTCODE_".$p_extcode).". ";

	if($sign == $p_sign)
	{
		if($p_action == "0" && $p_rc = "00")
		{
			if(DoubleVal($p_amount) == DoubleVal($arOrder["PRICE"]) && $p_currency == $currency)
			{
				echo $PAY_OK;
				$bError = false;
				$arFields["PS_STATUS"] = "Y";
				
				if($arOrder["PAYED"] != "Y")
					CSaleOrder::PayOrder($arOrder["ID"], "Y", true, true);
				if($arOrder["ALLOW_DELIVERY"] != "Y" && $ALLOW_DELIVERY == "Y")
					CSaleOrder::DeliverOrder($arOrder["ID"], "Y");
				
				$trtype = 21;
				$time = gmdate("YmdHis", time());
				$var = unpack("H*r", ToUpper(substr(md5(uniqid(30)), 0, 8))); 
				$nonce = $var[r];
				
				$signew = bx_hmac("sha1", 
						strlen($order).$order.
						strlen($amount).$amount.
						strlen($currency).$currency.
						strlen($p_rrn).$p_rrn.
						strlen($p_int_ref).$p_int_ref.
						strlen($trtype).$trtype.
						strlen($terminal).$terminal.
						strlen($time).$time.
						strlen($nonce).$nonce
						, 
						$key
					);
				
				$res = "";
				$res .= "TRTYPE=".$trtype;
				$res .= "&ORDER=".$order;
				$res .= "&AMOUNT=".$amount;
				$res .= "&CURRENCY=".$currency;
				$res .= "&RRN=".$p_rrn;
				$res .= "&INT_REF=".$p_int_ref;
				$res .= "&TERMINAL=".$terminal;
				$res .= "&TIMESTAMP=".$time;
				$res .= "&NONCE=".$nonce;
				$res .= "&EMAIL=".$email;
				$res .= "&LANG=";
				$res .= "&P_SIGN=".$signew;

				$header = "POST ".$server_url." HTTP/1.0\r\n";
				$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
				$header .= "Content-Length: " . strlen($res) . "\r\n\r\n";
				
				$fp = fsockopen("ssl://3ds.eximb.com", 443, $errno, $errstr, 60);
				if($fp)
					fputs ($fp, $header.$res);
				fclose ($fp);
			}
			else
				$arFields["PS_STATUS_MESSAGE"] .= GetMessage("ERROR_SUM").". ";
		}
	}
	else
		$arFields["PS_STATUS_MESSAGE"] .= GetMessage("ERROR_CHECKSUM")."";
		
	if($bError)
		echo $PAY_ERROR;
		
	CSaleOrder::Update($arOrder["ID"], $arFields);
}
?>