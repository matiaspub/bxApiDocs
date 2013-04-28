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
$m_name = CSalePaySystemAction::GetParamValue("MERCH_NAME"); 
$m_url = CSalePaySystemAction::GetParamValue("MERCH_URL"); 
$merchant = CSalePaySystemAction::GetParamValue("MERCHANT");
$terminal = CSalePaySystemAction::GetParamValue("TERMINAL");
$email = CSalePaySystemAction::GetParamValue("EMAIL"); 
$backref = htmlspecialcharsbx(CSalePaySystemAction::GetParamValue("SHOP_RESULT")); 
$mac = CSalePaySystemAction::GetParamValue("MAC");

if(strlen(CSalePaySystemAction::GetParamValue("IS_TEST")) > 0)
	$server_url = "https://3ds.eximb.com:443/cgi-bin/cgi_test";
else
	$server_url = "https://3ds.eximb.com/cgi-bin/cgi_link";

$trtype = 0;  
$country = ""; 
$merch_gmt = ""; 
$time = ""; 

$var = unpack("H*r", ToUpper(substr(md5(uniqid(30)), 0, 8))); 
$nonce = $var[r];

$key = pack("H*", $mac);   
$time = gmdate("YmdHis", time());

$sign = bx_hmac("sha1", 
		(strlen($amount) > 0 ? strlen($amount).$amount : "-").
		(strlen($currency) > 0 ? strlen($currency).$currency : "-").
		(strlen($order) > 0 ? strlen($order).$order : "-").
		(strlen($desc) > 0 ? strlen($desc).$desc : "-").
		(strlen($m_name) > 0 ? strlen($m_name).$m_name : "-").
		(strlen($m_url) > 0 ? strlen($m_url).$m_url : "-").
		(strlen($merchant) > 0 ? strlen($merchant).$merchant : "-").
		(strlen($terminal) > 0 ? strlen($terminal).$terminal : "-").
		(strlen($email) > 0 ? strlen($email).$email : "-").
		(strlen($trtype) > 0 ? strlen($trtype).$trtype : "-").
		"--".
		(strlen($time) > 0 ? strlen($time).$time : "-").
		(strlen($nonce) > 0 ? strlen($nonce).$nonce : "-").
		(strlen($backref) > 0 ? strlen($backref).$backref : "-")
		, 
		$key
	);
?>

<form name="cardform" action="<?=$server_url?>" method="post">
<input type="hidden" name="TRTYPE" VALUE="<?=$trtype?>">
<input type="hidden" name="AMOUNT" value="<?=$amount?>"> 
<input type="hidden" name="CURRENCY" value="<?=$currency?>"> 
<input type="hidden" name="ORDER" value="<?=$order?>">  
<input type="hidden" name="DESC" value="<?=$desc?>"> 
<input type="hidden" name="MERCH_NAME" value="<?=$m_name?>"> 
<input type="hidden" name="MERCH_URL" value="<?=$m_url?>"> 
<input type="hidden" name="MERCHANT" value="<?=$merchant?>"> 
<input type="hidden" name="TERMINAL" value="<?=$terminal?>"> 
<input type="hidden" name="EMAIL" value="<?=$email?>"> 
<input type="hidden" name="LANG" value=""> 
<input type="hidden" name="BACKREF" value="<?=$backref?>"> 
<input type="hidden" name="NONCE" value="<?=$nonce?>">
<input type="hidden" name="P_SIGN" value="<?=$sign?>">
<input type="hidden" name="TIMESTAMP" value="<?=$time?>">
<input type="submit" value="<?=GetMessage("PAY_BUTTON")?>" name="send_button">
</form>