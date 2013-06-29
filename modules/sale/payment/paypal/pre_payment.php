<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?
include(GetLangFileName(dirname(__FILE__)."/", "/payment.php"));

class CSalePaySystemPrePayment
{
	var $username = "";
	var $pwd = "";
	var $signature = "";
	var $currency = "";
	var $serverName = "";
	var $testMode = true;
	var $domain = "";
	var $token = "";
	var $payerId = "";
	var $encoding = "";
	var $version = "";

	public function init()
	{
		$this->username = CSalePaySystemAction::GetParamValue("USER");
		$this->pwd = CSalePaySystemAction::GetParamValue("PWD");
		$this->signature = CSalePaySystemAction::GetParamValue("SIGNATURE");
		$this->currency = CSalePaySystemAction::GetParamValue("CURRENCY");
		$this->testMode = (CSalePaySystemAction::GetParamValue("TEST") == "Y");
		if($this->testMode)
			$this->domain = "sandbox.";
		if(strlen($_REQUEST["token"]) > 0)
			$this->token = $_REQUEST["token"];
		if(strlen($_REQUEST["PayerID"]) > 0)
			$this->payerId = $_REQUEST["PayerID"];
		$this->version = "98.0";

		$dbSite = CSite::GetByID(SITE_ID);
		$arSite = $dbSite->Fetch();
		$this->serverName = $arSite["SERVER_NAME"];
		if (strLen($this->serverName) <=0)
		{
			if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME)>0)
				$this->serverName = SITE_SERVER_NAME;
			else
				$this->serverName = COption::GetOptionString("main", "server_name", "www.bitrixsoft.com");
		}
		
		$this->serverName = (CMain::IsHTTPS() ? "https" : "http")."://".$this->serverName;

		if(strlen($this->username) <= 0 || strlen($this->username) <= 0 || strlen($this->username) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException("CSalePaySystempaypal: init error", "CSalePaySystempaypal_init_error");
			return false;
		}
		return true;
	}
	
	public static function BasketButtonShow()
	{
		return "
		<form action=\"".POST_FORM_ACTION_URI."\" method=\"post\" name=\"paypal\">
			<input type=\"hidden\" name=\"paypal\" value=\"Y\">
			<input type=\"image\" name=\"paypalbutton\" value=\"".GetMessage("PPL_BUTTON")."\" src=\"https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif\">
		</form>";
	}

	public function BasketButtonAction($orderData = array())
	{
		global $APPLICATION;
		if($_POST["paypal"] == "Y")
		{
			$url = "https://api-3t.".$this->domain."paypal.com/nvp";

			$arFields = array(
					"METHOD" => "SetExpressCheckout",
					"VERSION" => "98.0",
					"USER" => $this->username,
					"PWD" => $this->pwd,
					"SIGNATURE" => $this->signature,
					"AMT" => $orderData["AMOUNT"],
					"CURRENCYCODE" => $this->currency,
					"RETURNURL" => $this->serverName.$orderData["PATH_TO_ORDER"],
					"CANCELURL" => $this->serverName.$APPLICATION->GetCurPageParam("paypal=Y&paypal_error=Y", array("paypal", "paypal_error")),
					"PAYMENTACTION" => "Authorization",
				);

			$arFields["RETURNURL"] .= ((strpos($arFields["RETURNURL"], "?") === false) ? "?" : "&")."paypal=Y";

			$ht = new CHTTP();
			if($res = $ht->Post($url, $arFields))
			{
				$result = $this->parseResult($res);

				if(strlen($result["TOKEN"]) > 0)
				{
					$url = "https://www.".$this->domain."paypal.com/webscr?cmd=_express-checkout&token=".$result["TOKEN"];
					if($orderData["ORDER_REQUEST"] == "Y")
						return $url;
					LocalRedirect($url);
				}
			}
			else
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("PPL_ERROR"), "CSalePaySystemPrePayment_action_error");
				return false;
			}
		}

		return true;
	}

	public function getHiddenInputs()
	{
		return "
			<input type=\"hidden\" name=\"paypal\" value=\"Y\">
			<input type=\"hidden\" name=\"token\" value=\"".htmlspecialcharsbx($this->token)."\">
			<input type=\"hidden\" name=\"PayerID\" value=\"".htmlspecialcharsbx($this->payerId)."\">
		";
	}

	public function isAction()
	{
		if($_REQUEST["paypal"] == "Y" && strlen($this->token) > 0)
			return true;
		return false;
	}

	public function parseResult($data)
	{
		global $APPLICATION;

		$keyarray = array();
		$res1= explode("&", $data);
		foreach($res1 as $res2)
		{
			list($key,$val) = explode("=", $res2);
			$keyarray[urldecode($key)] = urldecode($val);
			if(strlen($this->encoding) > 0)
				$keyarray[urldecode($key)] = $APPLICATION->ConvertCharset($keyarray[urldecode($key)], $this->encoding, SITE_CHARSET);
		}
		return $keyarray;

	}

	public function getProps()
	{
		if(strlen($this->token) > 0)
		{
			$url = "https://api-3t.".$this->domain."paypal.com/nvp";
			$arFields = array(
					"METHOD" => "GetExpressCheckoutDetails",
					"VERSION" => $this->version,
					"USER" => $this->username,
					"PWD" => $this->pwd,
					"SIGNATURE" => $this->signature,
					"TOKEN" => $this->token,				
				);

			$ht = new CHTTP();
			if($res = $ht->Post($url, $arFields))
			{
				$result = $this->parseResult($res);

				if($result["ACK"] == "Success")
				{
					$arResult = array(
						"FIO" => $result["FIRSTNAME"]." ".$result["LASTNAME"],
						"EMAIL" => $result["EMAIL"],
						"ZIP" => $result["SHIPTOZIP"],
						"ADDRESS" => $result["SHIPTOSTREET"]." ".$result["SHIPTOSTREET2"],
						"COUNTRY" => $result["SHIPTOCOUNTRYNAME"],
						"STATE" => $result["SHIPTOSTATE"],
						"CITY" => $result["SHIPTOCITY"],
						"LOCATION" => $result["SHIPTOCITY"],
						);
					return $arResult;
				}
			}
		}
	}

	public function payOrder()
	{
		if(strlen($this->token) > 0)
		{
			$url = "https://api-3t.".$this->domain."paypal.com/nvp";
			$arFields = array(
					"METHOD" => "GetExpressCheckoutDetails",
					"VERSION" => $this->version,
					"USER" => $this->username,
					"PWD" => $this->pwd,
					"SIGNATURE" => $this->signature,
					"TOKEN" => $this->token,				
				);

			$ht = new CHTTP();
			if($res = $ht->Post($url, $arFields))
			{
				$result = $this->parseResult($res);

				if($result["ACK"] == "Success" && in_array($result["CHECKOUTSTATUS"], array("PaymentActionNotInitiated")))
				{
					$arFields["METHOD"] = "DoExpressCheckoutPayment";
					$arFields["PAYERID"] = $this->payerId;
					$arFields["PAYMENTACTION"] = "Sale";
					$arFields["AMT"] = $this->orderAmount;
					$arFields["CURRENCYCODE"] = $this->currency;
					$arFields["DESC"] = "Order #".$this->orderId;

					if($res2 = $ht->Post($url, $arFields))
					{
						$result2 = $this->parseResult($res2);

						if($result2["ACK"] == "Success" && $result2["PAYMENTSTATUS"] == "Completed")
						{
							CSaleOrder::PayOrder($this->orderId, "Y");
							$strPS_STATUS_MESSAGE = "";
							$strPS_STATUS_MESSAGE .= "Name: ".$result["FIRSTNAME"]." ".$result["LASTNAME"]."; ";
							$strPS_STATUS_MESSAGE .= "Email: ".$result["EMAIL"]."; ";
							
							$strPS_STATUS_DESCRIPTION = "";
							$strPS_STATUS_DESCRIPTION .= "Payment status: ".$result2["PAYMENTSTATUS"]."; ";
							$strPS_STATUS_DESCRIPTION .= "Payment sate: ".$result2["ORDERTIME"]."; ";

							$arOrderFields = array(
									"PS_STATUS" => "Y",
									"PS_STATUS_CODE" => "-",
									"PS_STATUS_DESCRIPTION" => $strPS_STATUS_DESCRIPTION,
									"PS_STATUS_MESSAGE" => $strPS_STATUS_MESSAGE,
									"PS_SUM" => $result2["AMT"],
									"PS_CURRENCY" => $result2["CURRENCYCODE"],
									"PS_RESPONSE_DATE" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
									"PAY_VOUCHER_NUM" => $result2["TRANSACTIONID"],
									"PAY_VOUCHER_DATE" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
								);
						}
						else
						{
							$strPS_STATUS_MESSAGE = "";
							$strPS_STATUS_MESSAGE .= "Name: ".$result["FIRSTNAME"]." ".$result["LASTNAME"]."; ";
							$strPS_STATUS_MESSAGE .= "Email: ".$result["EMAIL"]."; ";
							
							$strPS_STATUS_DESCRIPTION = "";
							$strPS_STATUS_DESCRIPTION .= "Payment status: ".$result2["PAYMENTSTATUS"]."; ";
							$strPS_STATUS_DESCRIPTION .= "Pending reason: ".$result2["PENDINGREASON"]."; ";
							$strPS_STATUS_DESCRIPTION .= "Payment sate: ".$result2["ORDERTIME"]."; ";

							$arOrderFields = array(
									"PS_STATUS" => "N",
									"PS_STATUS_CODE" => $result2["PAYMENTSTATUS"],
									"PS_STATUS_DESCRIPTION" => $strPS_STATUS_DESCRIPTION,
									"PS_STATUS_MESSAGE" => $strPS_STATUS_MESSAGE,
									"PS_SUM" => $result2["AMT"],
									"PS_CURRENCY" => $result2["CURRENCYCODE"],
									"PS_RESPONSE_DATE" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
									"PAY_VOUCHER_NUM" => $result2["TRANSACTIONID"],
									"PAY_VOUCHER_DATE" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
								);
						}
						CSaleOrder::Update($this->orderId, $arOrderFields);
					}
				}
			}
		}
	}
}
?>