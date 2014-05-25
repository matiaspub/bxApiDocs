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
	var $notifyUrl = "";
	var $taxAmount = "";
	var $deliveryAmount = "";

	public function init()
	{
		$this->username = CSalePaySystemAction::GetParamValue("USER");
		$this->pwd = CSalePaySystemAction::GetParamValue("PWD");
		$this->signature = CSalePaySystemAction::GetParamValue("SIGNATURE");
		$this->currency = CSalePaySystemAction::GetParamValue("CURRENCY");
		$this->testMode = (CSalePaySystemAction::GetParamValue("TEST") == "Y");
		$this->notifyUrl = CSalePaySystemAction::GetParamValue("NOTIFY_URL");

		if(strlen($this->currency) <= 0)
			$this->currency =CSaleLang::GetLangCurrency(SITE_ID);

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
		if(LANGUAGE_ID == "ru")
			$imgSrc = "//www.1c-bitrix.ru/download/sale/paypal.jpg";
		elseif(LANGUAGE_ID == "de")
			$imgSrc = "//www.paypal.com/de_DE/i/btn/btn_xpressCheckout.gif";
		else
			$imgSrc = "//www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif";
		return "
		<form action=\"".POST_FORM_ACTION_URI."\" method=\"post\" name=\"paypal\">
			<input type=\"hidden\" name=\"paypal\" value=\"Y\">
			<input style=\"padding-top:7px;\" type=\"image\" name=\"paypalbutton\" value=\"".GetMessage("PPL_BUTTON")."\" src=\"".$imgSrc."\">
		</form>";
	}

	public function BasketButtonAction($orderData = array())
	{
		global $APPLICATION;

		if(strlen($_POST["paypalbutton"]) > 0 || $_POST["paypal"] == "Y")
		{
			$url = "https://api-3t.".$this->domain."paypal.com/nvp";

			$arFields = array(
					"METHOD" => "SetExpressCheckout",
					"VERSION" => "98.0",
					"USER" => $this->username,
					"PWD" => $this->pwd,
					"SIGNATURE" => $this->signature,
					"PAYMENTREQUEST_0_AMT" => number_format($orderData["AMOUNT"], 2, ".", ""),
					"PAYMENTREQUEST_0_CURRENCYCODE" => $this->currency,
					"RETURNURL" => $this->serverName.$orderData["PATH_TO_ORDER"],
					"CANCELURL" => $this->serverName.$APPLICATION->GetCurPageParam("paypal=Y&paypal_error=Y", array("paypal", "paypal_error")),
					"PAYMENTREQUEST_0_PAYMENTACTION" => "Authorization",
					"PAYMENTREQUEST_0_DESC" => "Order payment for ".$this->serverName,
					"LOCALECODE" => ToUpper(LANGUAGE_ID),
					"buttonsource" => "Bitrix_Cart",
				);

			if(!empty($orderData["BASKET_ITEMS"]))
			{
				$arFields["PAYMENTREQUEST_0_ITEMAMT"] = number_format($orderData["AMOUNT"], 2, ".", "");
				foreach($orderData["BASKET_ITEMS"] as $k => $val)	
				{
					$arFields["L_PAYMENTREQUEST_0_NAME".$k] = $APPLICATION->ConvertCharset($val["NAME"], SITE_CHARSET, "utf-8");
					$arFields["L_PAYMENTREQUEST_0_AMT".$k] = number_format($val["PRICE"], 2, ".", "");
					$arFields["L_PAYMENTREQUEST_0_QTY".$k] = $val["QUANTITY"];
				}
			}

			$arFields["RETURNURL"] .= ((strpos($arFields["RETURNURL"], "?") === false) ? "?" : "&")."paypal=Y";

			$ht = new \Bitrix\Main\Web\HttpClient(array("version" => "1.1"));
			if($res = $ht->post($url, $arFields))
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
		$result = "
			<input type=\"hidden\" name=\"paypal\" value=\"Y\">
			<input type=\"hidden\" name=\"token\" value=\"".htmlspecialcharsbx($this->token)."\">
			<input type=\"hidden\" name=\"PayerID\" value=\"".htmlspecialcharsbx($this->payerId)."\">
		";

		if(strlen($this->token) > 0)
			$result .= "<span style='color: green'>".GetMessage("PPL_PREAUTH_TEXT")."<br /><br /></span>";
		return $result;
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
					"buttonsource" => "Bitrix_Cart",
				);

			$ht = new \Bitrix\Main\Web\HttpClient(array("version" => "1.1"));
			if($res = $ht->post($url, $arFields))
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
						"PP_SOURCE" => $result,
						);
					return $arResult;
				}
			}
		}
	}

	public function payOrder($orderData = array())
	{
		if(strlen($this->token) > 0)
		{
			global $APPLICATION;
			$url = "https://api-3t.".$this->domain."paypal.com/nvp";
			$arFields = array(
					"METHOD" => "GetExpressCheckoutDetails",
					"VERSION" => $this->version,
					"USER" => $this->username,
					"PWD" => $this->pwd,
					"SIGNATURE" => $this->signature,
					"TOKEN" => $this->token,
					"buttonsource" => "Bitrix_Cart",
				);

			$ht = new \Bitrix\Main\Web\HttpClient(array("version" => "1.1"));
			if($res = $ht->post($url, $arFields))
			{
				$result = $this->parseResult($res);
				if($result["ACK"] == "Success" && in_array($result["CHECKOUTSTATUS"], array("PaymentActionNotInitiated")))
				{
					$arFields["METHOD"] = "DoExpressCheckoutPayment";
					$arFields["PAYERID"] = $this->payerId;
					$arFields["PAYMENTACTION"] = "Sale";
					$arFields["PAYMENTREQUEST_0_AMT"] = number_format($this->orderAmount, 2, ".", "");
					$arFields["PAYMENTREQUEST_0_CURRENCYCODE"] = $this->currency;
					$arFields["PAYMENTREQUEST_0_DESC"] = "Order #".$this->orderId;
					$arFields["PAYMENTREQUEST_0_NOTETEX"] = "Order #".$this->orderId;
					$arFields["PAYMENTREQUEST_0_INVNUM"] = $this->orderId;

					if(DoubleVal($this->deliveryAmount) > 0)
					{
						$arFields["PAYMENTREQUEST_0_SHIPPINGAMT"] = number_format($this->deliveryAmount, 2, ".", "");
					}
					$orderProps = $this->getProps();

					if(!empty($orderProps))
					{
						$arFields["PAYMENTREQUEST_0_SHIPTONAME"] = $APPLICATION->ConvertCharset($orderProps["PP_SOURCE"]["PAYMENTREQUEST_0_SHIPTONAME"], SITE_CHARSET, "utf-8");
						$arFields["PAYMENTREQUEST_0_SHIPTOSTREET"] = $APPLICATION->ConvertCharset($orderProps["PP_SOURCE"]["PAYMENTREQUEST_0_SHIPTOSTREET"], SITE_CHARSET, "utf-8");
						$arFields["PAYMENTREQUEST_0_SHIPTOSTREET2"] = $APPLICATION->ConvertCharset($orderProps["PP_SOURCE"]["PAYMENTREQUEST_0_SHIPTOSTREET2"], SITE_CHARSET, "utf-8");
						$arFields["PAYMENTREQUEST_0_SHIPTOCITY"] = $APPLICATION->ConvertCharset($orderProps["PP_SOURCE"]["PAYMENTREQUEST_0_SHIPTOCITY"], SITE_CHARSET, "utf-8");
						$arFields["PAYMENTREQUEST_0_SHIPTOSTATE"] = $APPLICATION->ConvertCharset($orderProps["PP_SOURCE"]["PAYMENTREQUEST_0_SHIPTOSTATE"], SITE_CHARSET, "utf-8");
						$arFields["PAYMENTREQUEST_0_SHIPTOZIP"] = $orderProps["PP_SOURCE"]["PAYMENTREQUEST_0_SHIPTOZIP"];
						$arFields["PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE"] = $APPLICATION->ConvertCharset($orderProps["PP_SOURCE"]["PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE"], SITE_CHARSET, "utf-8");
					}

					if(!empty($orderData["BASKET_ITEMS"]))
					{
						$arFields["PAYMENTREQUEST_0_ITEMAMT"] = number_format($this->orderAmount-$this->deliveryAmount, 2, ".", "");
						foreach($orderData["BASKET_ITEMS"] as $k => $val)	
						{
							$arFields["L_PAYMENTREQUEST_0_NAME".$k] = $APPLICATION->ConvertCharset($val["NAME"], SITE_CHARSET, "utf-8");
							$arFields["L_PAYMENTREQUEST_0_AMT".$k] = number_format($val["PRICE"], 2, ".", "");
							$arFields["L_PAYMENTREQUEST_0_QTY".$k] = $val["QUANTITY"];
							$arFields["L_PAYMENTREQUEST_0_NUMBER".$k] = $val["PRODUCT_ID"];
						}
					}

					if(strlen($this->notifyUrl) > 0)
						$arFields["PAYMENTREQUEST_0_NOTIFYURL"] = $this->notifyUrl;

					if($res2 = $ht->Post($url, $arFields))
					{
						$result2 = $this->parseResult($res2);

						if($result2["ACK"] == "Success" && in_array($result2["PAYMENTINFO_0_PAYMENTSTATUS"], array("Completed")))
						{
							CSaleOrder::PayOrder($this->orderId, "Y");
							$strPS_STATUS_MESSAGE = "";
							$strPS_STATUS_MESSAGE .= "Name: ".$result["FIRSTNAME"]." ".$result["LASTNAME"]."; ";
							$strPS_STATUS_MESSAGE .= "Email: ".$result["EMAIL"]."; ";
							
							$strPS_STATUS_DESCRIPTION = "";
							$strPS_STATUS_DESCRIPTION .= "Payment status: ".$result2["PAYMENTINFO_0_PAYMENTSTATUS"]."; ";
							$strPS_STATUS_DESCRIPTION .= "Payment sate: ".$result2["PAYMENTINFO_0_ORDERTIME"]."; ";

							$arOrderFields = array(
									"PS_STATUS" => "Y",
									"PS_STATUS_CODE" => "-",
									"PS_STATUS_DESCRIPTION" => $strPS_STATUS_DESCRIPTION,
									"PS_STATUS_MESSAGE" => $strPS_STATUS_MESSAGE,
									"PS_SUM" => $result2["PAYMENTINFO_0_AMT"],
									"PS_CURRENCY" => $result2["PAYMENTINFO_0_CURRENCYCODE"],
									"PS_RESPONSE_DATE" => ConvertTimeStamp(false, "FULL"),
									"PAY_VOUCHER_NUM" => $result2["PAYMENTINFO_0_TRANSACTIONID"],
									"PAY_VOUCHER_DATE" => ConvertTimeStamp(false, "FULL"),
								);
						}
						else
						{
							$strPS_STATUS_MESSAGE = "";
							$strPS_STATUS_MESSAGE .= "Name: ".$result["FIRSTNAME"]." ".$result["LASTNAME"]."; ";
							$strPS_STATUS_MESSAGE .= "Email: ".$result["EMAIL"]."; ";
							
							$strPS_STATUS_DESCRIPTION = "";
							$strPS_STATUS_DESCRIPTION .= "Payment status: ".$result2["PAYMENTINFO_0_PAYMENTSTATUS"]."; ";
							$strPS_STATUS_DESCRIPTION .= "Pending reason: ".$result2["PAYMENTINFO_0_PENDINGREASON"]."; ";
							$strPS_STATUS_DESCRIPTION .= "Payment sate: ".$result2["PAYMENTINFO_0_ORDERTIME"]."; ";

							$arOrderFields = array(
									"PS_STATUS" => "N",
									"PS_STATUS_CODE" => $result2["PAYMENTINFO_0_PAYMENTSTATUS"],
									"PS_STATUS_DESCRIPTION" => $strPS_STATUS_DESCRIPTION,
									"PS_STATUS_MESSAGE" => $strPS_STATUS_MESSAGE,
									"PS_SUM" => $result2["PAYMENTINFO_0_AMT"],
									"PS_CURRENCY" => $result2["PAYMENTINFO_0_CURRENCYCODE"],
									"PS_RESPONSE_DATE" => ConvertTimeStamp(false, "FULL"),
									"PAY_VOUCHER_NUM" => $result2["PAYMENTINFO_0_TRANSACTIONID"],
									"PAY_VOUCHER_DATE" => ConvertTimeStamp(false, "FULL"),
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