<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?
function liqpay_parseTag($rs, $tag)
{
	$rs = str_replace("\n", "", str_replace("\r", "", $rs));
	$tags = '<'.$tag.'>';
	$tage = '</'.$tag;
	$start = strpos($rs, $tags)+strlen($tags);
	$end = strpos($rs, $tage);

	return substr($rs, $start, ($end-$start));
}

if ($_POST['signature']=="" || $_POST['operation_xml']=="")
	die();

$insig = $_POST['signature'];
$resp = base64_decode($_POST['operation_xml']);

$order_id = str_replace("ORDER_", "", liqpay_parseTag($resp, "order_id"));
$status = liqpay_parseTag($resp, "status");
$response_description = liqpay_parseTag($resp, "response_description");
$transaction_id = liqpay_parseTag($resp, "transaction_id");
$pay_details = liqpay_parseTag($resp, "pay_details");
$pay_way = liqpay_parseTag($resp, "pay_way");
$amount = liqpay_parseTag($resp, "amount");
$currency = liqpay_parseTag($resp, "currency");
$sender_phone = liqpay_parseTag($resp, "sender_phone");

if ($order_id <= 0)
	die();
if (!($arOrder = CSaleOrder::GetByID($order_id)))
	die();
if ($arOrder["PAYED"] == "Y")
	die();

CSalePaySystemAction::InitParamArrays($arOrder, $arOrder["ID"]);
$merchant_id = CSalePaySystemAction::GetParamValue("MERCHANT_ID");
$signature = CSalePaySystemAction::GetParamValue("SIGN");

$gensig = base64_encode(sha1($signature.$resp.$signature,1));

if ($insig == $gensig)
{
	if ($status == "success")
	{
		$sDescription = '';
		$sStatusMessage = '';

		$sDescription .= 'sender phone: '.$sender_phone.'; ';
		$sDescription .= 'amount: '.$amount.'; ';
		$sDescription .= 'currency: '.$currency.'; ';

		$sStatusMessage .= 'status: '.$status.'; ';
		$sStatusMessage .= 'transaction_id: '.$transaction_id.'; ';
		$sStatusMessage .= 'pay_way: '.$pay_way.'; ';
		$sStatusMessage .= 'order_id: '.$order_id.'; ';

		$arFields = array(
			"PS_STATUS" => "Y",
			"PS_STATUS_CODE" => $status,
			"PS_STATUS_DESCRIPTION" => $sDescription,
			"PS_STATUS_MESSAGE" => $sStatusMessage,
			"PS_SUM" => $amount,
			"PS_CURRENCY" => $currency,
			"PS_RESPONSE_DATE" => date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
			);

		CSaleOrder::PayOrder($arOrder["ID"], "Y");
		CSaleOrder::Update($arOrder["ID"], $arFields);
	}
	elseif ($status == "wait_secure")
	{
		//
	}
}
?>