<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Request;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Type\Date;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Sale\PriceMaths;

Loc::loadMessages(__FILE__);

class PayPalHandler extends PaySystem\ServiceHandler implements PaySystem\IPrePayable
{
	private $prePaymentSetting = array();

	/**
	 * @return array
	 */
	static public function getIndicativeFields()
	{
		return array('custom', 'mc_gross', 'mc_currency');
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return PaySystem\ServiceResult
	 */
	public function processRequest(Payment $payment, Request $request)
	{
		/** @var PaySystem\ServiceResult $serviceResult */
		$serviceResult = new PaySystem\ServiceResult();

		$instance = Application::getInstance();
		$context = $instance->getContext();
		$server = $context->getServer();

		$req = '';
		if ($request->get('tx'))
		{
			$req = $this->getPdtRequest($payment, $request);
		}
		elseif ($request->get('txn_id') && $server->getRequestMethod() == "POST")
		{
			$req = $this->getIpnRequest($request);
		}

		if ($req !== '')
		{
			$domain = '';
			if ($this->isTestMode($payment))
				$domain = "sandbox.";
			$host = "www.".$domain."paypal.com";

			$header = "POST /cgi-bin/webscr HTTP/1.1\r\n";
			$header .= "Host: ".$host."\r\n";
			$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$header .= "Content-Length: ".strlen($req)."\r\n";
			$header .= "User-Agent: 1C-Bitrix\r\n";
			$header .= "Connection: Close\r\n\r\n";

			if ($this->getBusinessValue($payment, "SSL_ENABLE") == "Y")
				$fp = fsockopen("ssl://".$host, 443, $errNo, $errStr, 30);
			else
				$fp = fsockopen($host, 80, $errNo, $errStr, 30);

			if ($fp)
			{
				fputs ($fp, $header.$req);
				$response = '';
				$headerDone = false;
				while (!feof($fp))
				{
					$line = fgets ($fp, 1024);
					if (strcmp($line, "\r\n") == 0)
						$headerDone = true;
					elseif ($headerDone)
						$response .= $line;
				}

				// parse the data
				$lines = explode('\n', $response);

				if (strcmp($lines[0], "SUCCESS") == 0)
				{
					return $this->processSuccessAction($payment, $request, $lines);
				}
				elseif (strpos($response, "VERIFIED") !== false)
				{
					return $this->processVerifiedAction($payment, $request);
				}
				else
				{
					$serviceResult->setData(array('MESSAGE' => Loc::getMessage("SALE_HPS_PAYPAL_I1")));
				}
			}
			else
			{
				$serviceResult->setData(
					array('MESSAGE' => Loc::getMessage("SALE_HPS_PAYPAL_I3").'<br /><br />'.Loc::getMessage("SALE_HPS_PAYPAL_I4"))
				);
			}
		}

		return $serviceResult;
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @param $lines
	 * @return PaySystem\ServiceResult
	 */
	protected function processSuccessAction(Payment $payment, Request $request, $lines)
	{
		$serviceResult = new PaySystem\ServiceResult();

		$keys = array();

		for ($i=1, $cnt = count($lines); $i < $cnt; $i++)
		{
			list($key, $val) = explode('=', $lines[$i]);
			$keys[urldecode($key)] = urldecode($val);
		}

		$psStatusMessage = 'Name: '.$keys['first_name'].' '.$keys['last_name'].'; ';
		$psStatusMessage .= 'Email: '.$keys['payer_email'].'; ';
		$psStatusMessage .= 'Item: '.$keys['item_name'].'; ';
		$psStatusMessage .= 'Amount: '.$keys['mc_gross'].'; ';

		$psStatusDescription = 'Payment status - '.$keys['payment_status'].'; ';
		$psStatusDescription .= 'Payment sate - '.$keys['payment_date'].'; ';

		$fields = array(
			"PS_STATUS" => "Y",
			"PS_STATUS_CODE" => "-",
			"PS_STATUS_DESCRIPTION" => $psStatusDescription,
			"PS_STATUS_MESSAGE" => $psStatusMessage,
			"PS_SUM" => $keys["mc_gross"],
			"PS_CURRENCY" => $keys["mc_currency"],
			"PS_RESPONSE_DATE" => new DateTime(),
			"PAY_VOUCHER_NUM" => $request->get('tx'),
			"PAY_VOUCHER_DATE" => new Date()
		);

		$serviceResult->setPsData($fields);

		$paymentSum = PriceMaths::roundByFormatCurrency($this->getBusinessValue($payment, 'PAYMENT_SHOULD_PAY'), $payment->getField('CURRENCY'));

		$payPalSum = (float)$keys["mc_gross"];
		if ($keys["tax"])
			$payPalSum -= (float)$keys["tax"];
		$payPalSum = PriceMaths::roundByFormatCurrency($payPalSum, $payment->getField('CURRENCY'));

		if ($paymentSum == $payPalSum
			&& ToLower($keys["receiver_email"]) == ToLower($this->getBusinessValue($payment, "PAYPAL_BUSINESS"))
			&& $keys["payment_status"] == "Completed"
		)
		{
			$serviceResult->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
		}

		$response = '<p><h3>'.Loc::getMessage('SALE_HPS_PAYPAL_T1').'</h3></p>';
		$response .= '<b>'.Loc::getMessage('SALE_HPS_PAYPAL_T2').'</b><br>\n';
		$response .= '<li>'.Loc::getMessage('SALE_HPS_PAYPAL_T3').': '.$keys['first_name'].' '.$keys['last_name'].'</li>\n';
		$response .= '<li>'.Loc::getMessage('SALE_HPS_PAYPAL_T4').': '.$keys['item_name'].'</li>\n';
		$response .= '<li>'.Loc::getMessage('SALE_HPS_PAYPAL_T5').': '.$keys['mc_gross'].'</li>\n';

		$serviceResult->setData(array('MESSAGE' => $response));

		return $serviceResult;
	}

	/**
	 * @param Request $request
	 * @param Payment $payment
	 * @return PaySystem\ServiceResult
	 */
	protected function processVerifiedAction(Payment $payment, Request $request)
	{
		$serviceResult = new PaySystem\ServiceResult();

		$psStatusMessage = Loc::getMessage("SALE_HPS_PAYPAL_T3").": ".$request->get("first_name")." ".$request->get("last_name")."; ";
		$psStatusMessage .= "Email: ".$request->get("payer_email")."; ";
		$psStatusMessage .= Loc::getMessage("SALE_HPS_PAYPAL_T4").": ".$_POST["item_name"]."; ";
		$psStatusMessage .= Loc::getMessage("SALE_HPS_PAYPAL_T5").": ".$_POST["mc_gross"]."; ";

		$psStatusDescription = "Payment status - ".$request->get("payment_status")."; ";
		$psStatusDescription .= "Payment sate - ".$request->get("payment_date")."; ";

		$fields = array(
			"PS_STATUS" => "Y",
			"PS_STATUS_CODE" => "-",
			"PS_STATUS_DESCRIPTION" => $psStatusDescription,
			"PS_STATUS_MESSAGE" => $psStatusMessage,
			"PS_SUM" => $request->get("mc_gross"),
			"PS_CURRENCY" => $request->get("mc_currency"),
			"PS_RESPONSE_DATE" => new DateTime(),
			"PAY_VOUCHER_NUM" => $request->get('txn_id'),
			"PAY_VOUCHER_DATE" => new Date()
		);

		$serviceResult->setPsData($fields);

		$paymentSum = PriceMaths::roundByFormatCurrency($this->getBusinessValue($payment, 'PAYMENT_SHOULD_PAY'), $payment->getField('CURRENCY'));

		$payPalSum = (float)$request->get("mc_gross");
		if ($request->get('tax'))
			$payPalSum -= (float)$request->get('tax');
		$payPalSum = PriceMaths::roundByFormatCurrency($payPalSum, $payment->getField('CURRENCY'));

		if ($paymentSum == $payPalSum
			&& ToLower($request->get("receiver_email")) == ToLower($this->getBusinessValue($payment, "PAYPAL_BUSINESS"))
			&& $request->get("payment_status") == "Completed"
			&& $payment->getField("PAY_VOUCHER_NUM") != $request->get('txn_id')
			)
		{
			$serviceResult->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
		}

		return $serviceResult;
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return string
	 */
	protected function getPdtRequest(Payment $payment, Request $request)
	{
		$req = '';
		if ($request->get('tx'))
		{
			$req = 'cmd=_notify-synch';
			$req .= "&tx=".$request->get('tx')."&at=".$this->getBusinessValue($payment, "IDENTITY_TOKEN");
		}

		return $req;
	}

	/**
	 * @param Request $request
	 * @return string
	 */
	protected function getIpnRequest(Request $request)
	{
		$req = 'cmd=_notify-validate';

		foreach ($_POST as $key => $value)
			$req .= '&'.$key.'='.urlencode(stripslashes($value));

		return $req;
	}

	/**
	 * @return array
	 */
	protected function getUrlList()
	{
		return array(
			'pay' => array(
				self::TEST_URL => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
				self::ACTIVE_URL => 'https://www.paypal.com/cgi-bin/webscr'
			)
		);
	}

	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return PaySystem\ServiceResult
	 */
	public function initiatePay(Payment $payment, Request $request = null)
	{
		$this->setExtraParams(array('URL' => $this->getUrl($payment, 'pay')));

		return $this->showTemplate($payment, 'template');
	}

	/**
	 * @return array
	 */
	static public function getCurrencyList()
	{
		return array('RUB');
	}

	/**
	 * @param Request $request
	 * @return mixed
	 */
	static public function getPaymentIdFromRequest(Request $request)
	{
		return $request->get('custom');
	}

	/**
	 * @param Payment $payment
	 * @return bool
	 */
	protected function isTestMode(Payment $payment = null)
	{
		return $this->getBusinessValue($payment, 'PS_IS_TEST') == 'Y';
	}

	/**
	 * @param PaySystem\ServiceResult $result
	 * @param Request $request
	 * @return mixed
	 */
	static public function sendResponse(PaySystem\ServiceResult $result, Request $request)
	{
		$data = $result->getData();

		if (isset($data['MESSAGE']))
			echo $data['MESSAGE'];
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return bool
	 */
	public function initPrePayment(Payment $payment = null, Request $request)
	{
		$this->prePaymentSetting = array(
			'USER' => $this->getBusinessValue($payment, 'PAYPAL_USER'),
			'PWD' => $this->getBusinessValue($payment, 'PAYPAL_PWD'),
			'SIGNATURE' => $this->getBusinessValue($payment, 'PAYPAL_SIGNATURE'),
			'CURRENCY' => $this->getBusinessValue($payment, 'PAYMENT_CURRENCY'),
			'TEST' => $this->isTestMode($payment),
			'NOTIFY_URL' => $this->getBusinessValue($payment, 'PAYPAL_NOTIFY_URL'),
			'ENCODING' => $this->service->getField('ENCODING')
		);

		if (!$this->prePaymentSetting['CURRENCY'])
			$this->prePaymentSetting['CURRENCY'] = \CSaleLang::GetLangCurrency(SITE_ID);
		if ($this->prePaymentSetting['TEST'])
			$this->prePaymentSetting['DOMAIN'] = "sandbox.";
		if ($request->get("token"))
			$this->prePaymentSetting['TOKEN'] = $request->get("token");
		if ($request->get("PayerID"))
			$this->prePaymentSetting['PayerID'] = $request->get("PayerID");

		$this->prePaymentSetting['VERSION'] = "98.0";

		$dbSite = \CSite::GetByID(SITE_ID);
		$arSite = $dbSite->Fetch();

		$this->prePaymentSetting['SERVER_NAME'] = $arSite["SERVER_NAME"];
		if ($this->prePaymentSetting['SERVER_NAME'])
		{
			if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0)
				$this->prePaymentSetting['SERVER_NAME'] = SITE_SERVER_NAME;
			else
				$this->prePaymentSetting['SERVER_NAME'] = \COption::GetOptionString("main", "server_name", "www.bitrixsoft.com");
		}

		$this->prePaymentSetting['SERVER_NAME'] = (\CMain::IsHTTPS() ? "https" : "http")."://".$this->prePaymentSetting['SERVER_NAME'];

		if(!$this->prePaymentSetting['USER'])
		{
			$GLOBALS["APPLICATION"]->ThrowException("CSalePaySystempaypal: init error", "CSalePaySystempaypal_init_error");
			return false;
		}

		return true;
	}

	/**
	 * @param $data
	 * @return array
	 */
	protected function parsePrePaymentResult($data)
	{
		global $APPLICATION;

		$keyArray = array();
		$res1 = explode("&", $data);
		foreach ($res1 as $res2)
		{
			list($key, $val) = explode("=", $res2);
			$keyArray[urldecode($key)] = urldecode($val);
			if ($this->prePaymentSetting['ENCODING'])
				$keyArray[urldecode($key)] = $APPLICATION->ConvertCharset($keyArray[urldecode($key)], $this->prePaymentSetting['ENCODING'], SITE_CHARSET);
		}

		return $keyArray;
	}

	/**
	 * @return array
	 */
	public function getProps()
	{
		$data = array();

		if ($this->prePaymentSetting['TOKEN'])
		{
			$url = "https://api-3t.".$this->prePaymentSetting['DOMAIN']."paypal.com/nvp";
			$arFields = array(
				"METHOD" => "GetExpressCheckoutDetails",
				"VERSION" => $this->prePaymentSetting['VERSION'],
				"USER" => $this->prePaymentSetting['USER'],
				"PWD" => $this->prePaymentSetting['PWD'],
				"SIGNATURE" => $this->prePaymentSetting['SIGNATURE'],
				"TOKEN" => $this->prePaymentSetting['TOKEN'],
				"buttonsource" => "Bitrix_Cart"
			);

			$ht = new HttpClient(array("version" => "1.1"));
			if ($res = $ht->post($url, $arFields))
			{
				$result = $this->parsePrePaymentResult($res);
				if ($result["ACK"] == "Success")
				{
					$data = array(
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

					return $data;
				}
			}
		}

		return $data;
	}

	/**
	 * @param array $orderData
	 */
	public function payOrder($orderData = array())
	{
		$serviceResult = new PaySystem\ServiceResult();

		if($this->prePaymentSetting['TOKEN'])
		{
			global $APPLICATION;
			$url = "https://api-3t.".$this->prePaymentSetting['DOMAIN']."paypal.com/nvp";
			$arFields = array(
					"METHOD" => "GetExpressCheckoutDetails",
					"VERSION" => $this->prePaymentSetting['VERSION'],
					"USER" => $this->prePaymentSetting['USER'],
					"PWD" => $this->prePaymentSetting['PWD'],
					"SIGNATURE" => $this->prePaymentSetting['SIGNATURE'],
					"TOKEN" => $this->prePaymentSetting['TOKEN'],
					"buttonsource" => "Bitrix_Cart",
				);

			$ht = new \Bitrix\Main\Web\HttpClient(array("version" => "1.1"));
			if($res = $ht->post($url, $arFields))
			{
				$result = $this->parsePrePaymentResult($res);
				if($result["ACK"] == "Success" && in_array($result["CHECKOUTSTATUS"], array("PaymentActionNotInitiated")))
				{
					$arFields["METHOD"] = "DoExpressCheckoutPayment";
					$arFields["PAYERID"] = $this->prePaymentSetting['payerId'];
					$arFields["PAYMENTACTION"] = "Sale";
					$arFields["PAYMENTREQUEST_0_AMT"] = number_format($this->prePaymentSetting['ORDER_PRICE'], 2, ".", "");
					$arFields["PAYMENTREQUEST_0_CURRENCYCODE"] = $this->prePaymentSetting['CURRENCY'];
					$arFields["PAYMENTREQUEST_0_DESC"] = "Order #".$this->prePaymentSetting['ORDER_ID'];
					$arFields["PAYMENTREQUEST_0_NOTETEX"] = "Order #".$this->prePaymentSetting['ORDER_ID'];
					$arFields["PAYMENTREQUEST_0_INVNUM"] = $this->prePaymentSetting['ORDER_ID'];

					if(DoubleVal($this->prePaymentSetting['DELIVERY_PRICE']) > 0)
					{
						$arFields["PAYMENTREQUEST_0_SHIPPINGAMT"] = number_format($this->prePaymentSetting['DELIVERY_PRICE'], 2, ".", "");
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
						$arFields["PAYMENTREQUEST_0_ITEMAMT"] = number_format($this->prePaymentSetting['ORDER_PRICE']-$this->prePaymentSetting['DELIVERY_PRICE'], 2, ".", "");
						foreach($orderData["BASKET_ITEMS"] as $i => $val)
						{
							$arFields["L_PAYMENTREQUEST_0_NAME".$i] = $APPLICATION->ConvertCharset($val["NAME"], SITE_CHARSET, "utf-8");
							$arFields["L_PAYMENTREQUEST_0_AMT".$i] = number_format($val["PRICE"], 2, ".", "");
							$arFields["L_PAYMENTREQUEST_0_QTY".$i] = $val["QUANTITY"];
							$arFields["L_PAYMENTREQUEST_0_NUMBER".$i] = $val["PRODUCT_ID"];
						}
					}

					if(strlen($this->prePaymentSetting['DELIVERY_PRICE']) > 0)
						$arFields["PAYMENTREQUEST_0_NOTIFYURL"] = $this->prePaymentSetting['NOTIFY_URL'];

					if($postResult = $ht->Post($url, $arFields))
					{
						$parseResult = $this->parsePrePaymentResult($postResult);

						if($parseResult["ACK"] == "Success" && in_array($parseResult["PAYMENTINFO_0_PAYMENTSTATUS"], array("Completed")))
						{
							$psStatusMessage = "Name: ".$result["FIRSTNAME"]." ".$result["LASTNAME"]."; ";
							$psStatusMessage .= "Email: ".$result["EMAIL"]."; ";

							$psStatusDescription = "Payment status: ".$parseResult["PAYMENTINFO_0_PAYMENTSTATUS"]."; ";
							$psStatusDescription .= "Payment sate: ".$parseResult["PAYMENTINFO_0_ORDERTIME"]."; ";

							$fields = array(
								"PS_STATUS" => "Y",
								"PS_STATUS_CODE" => "-",
								"PS_STATUS_DESCRIPTION" => $psStatusDescription,
								"PS_STATUS_MESSAGE" => $psStatusMessage,
								"PS_SUM" => $parseResult["PAYMENTINFO_0_AMT"],
								"PS_CURRENCY" => $parseResult["PAYMENTINFO_0_CURRENCYCODE"],
								"PS_RESPONSE_DATE" => ConvertTimeStamp(false, "FULL"),
								"PAY_VOUCHER_NUM" => $parseResult["PAYMENTINFO_0_TRANSACTIONID"],
								"PAY_VOUCHER_DATE" => ConvertTimeStamp(false, "FULL"),
							);
						}
						else
						{
							$psStatusMessage = "Name: ".$result["FIRSTNAME"]." ".$result["LASTNAME"]."; ";
							$psStatusMessage .= "Email: ".$result["EMAIL"]."; ";

							$psStatusDescription = "Payment status: ".$parseResult["PAYMENTINFO_0_PAYMENTSTATUS"]."; ";
							$psStatusDescription .= "Pending reason: ".$parseResult["PAYMENTINFO_0_PENDINGREASON"]."; ";
							$psStatusDescription .= "Payment sate: ".$parseResult["PAYMENTINFO_0_ORDERTIME"]."; ";

							$fields = array(
								"PS_STATUS" => "N",
								"PS_STATUS_CODE" => $parseResult["PAYMENTINFO_0_PAYMENTSTATUS"],
								"PS_STATUS_DESCRIPTION" => $psStatusDescription,
								"PS_STATUS_MESSAGE" => $psStatusMessage,
								"PS_SUM" => $parseResult["PAYMENTINFO_0_AMT"],
								"PS_CURRENCY" => $parseResult["PAYMENTINFO_0_CURRENCYCODE"],
								"PS_RESPONSE_DATE" => ConvertTimeStamp(false, "FULL"),
								"PAY_VOUCHER_NUM" => $parseResult["PAYMENTINFO_0_TRANSACTIONID"],
								"PAY_VOUCHER_DATE" => ConvertTimeStamp(false, "FULL"),
							);
						}

						$serviceResult->setPsData($fields);
					}
				}
			}
		}
	}

	/**
	 * @param array $orderData
	 * @return bool|string
	 */
	public function BasketButtonAction($orderData = array())
	{
		global $APPLICATION;
		if (array_key_exists('paypalbutton_x', $_POST) && array_key_exists('paypalbutton_y', $_POST))
		{
			$url = "https://api-3t.".$this->prePaymentSetting['DOMAIN']."paypal.com/nvp";

			$arFields = array(
					"METHOD" => "SetExpressCheckout",
					"VERSION" => "98.0",
					"USER" => $this->prePaymentSetting['USER'],
					"PWD" => $this->prePaymentSetting['PWD'],
					"SIGNATURE" => $this->prePaymentSetting['SIGNATURE'],
					"PAYMENTREQUEST_0_AMT" => number_format($orderData["AMOUNT"], 2, ".", ""),
					"PAYMENTREQUEST_0_CURRENCYCODE" => $this->prePaymentSetting['CURRENCY'],
					"RETURNURL" => $this->prePaymentSetting['SERVER_NAME'].$orderData["PATH_TO_ORDER"],
					"CANCELURL" => $this->prePaymentSetting['SERVER_NAME'].$APPLICATION->GetCurPageParam("paypal=Y&paypal_error=Y", array("paypal", "paypal_error")),
					"PAYMENTREQUEST_0_PAYMENTACTION" => "Authorization",
					"PAYMENTREQUEST_0_DESC" => "Order payment for ".$this->prePaymentSetting['SERVER_NAME'],
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
				$result = $this->parsePrePaymentResult($res);

				if($result["TOKEN"] != '')
				{
					$url = "https://www.".$this->prePaymentSetting['DOMAIN']."paypal.com/webscr?cmd=_express-checkout&token=".$result["TOKEN"];
					if($orderData["ORDER_REQUEST"] == "Y")
						return $url;
					LocalRedirect($url);
				}
				else
				{
					$GLOBALS["APPLICATION"]->ThrowException($result['L_SHORTMESSAGE0'].' : '.$result['L_LONGMESSAGE0'], "CSalePaySystemPrePayment_action_error");
					return false;
				}
			}
			else
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SALE_HPS_PAYPAL_ERROR"), "CSalePaySystemPrePayment_action_error");
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array $orderData
	 */
	public function setOrderConfig($orderData = array())
	{
		if ($orderData)
			$this->prePaymentSetting = array_merge($this->prePaymentSetting, $orderData);
	}
}