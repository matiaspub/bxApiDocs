<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Entity\EntityError;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\Error;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PriceMaths;

Loc::loadMessages(__FILE__);

class AssistHandler extends PaySystem\ServiceHandler implements PaySystem\IRefund, PaySystem\ICheckable
{
	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return PaySystem\ServiceResult
	 */
	public function initiatePay(Payment $payment, Request $request = null)
	{
		$extraParams = array(
			'URL' => $this->getUrl($payment, 'pay')
		);
		$this->setExtraParams($extraParams);

		return $this->showTemplate($payment, "template");
	}

	/**
	 * @return array
	 */
	public static function getIndicativeFields()
	{
		return array('ordernumber', 'billnumber', 'orderamount', 'amount', 'meantypename', 'meantype_id', 'approvalcode', 'operationtype');
	}

	/**
	 * @param Payment $payment
	 * @param int $refundableSum
	 * @return PaySystem\ServiceResult
	 */
	public function refund(Payment $payment, $refundableSum)
	{
		$result = new PaySystem\ServiceResult();

		$params = $this->getParamsBusValue($payment);
		$refundUrl = $this->getUrl($payment, 'return');

		$data = array(
			'Billnumber' => $payment->getField('PS_INVOICE_ID'),
			'Merchant_ID' => $params['ASSIST_SHOP_IDP'],
			'Login' => $params['ASSIST_SHOP_LOGIN'],
			'Password' => $params['ASSIST_SHOP_PASSWORD'],
			'Amount' => $refundableSum,
			'Currency' => $params['PAYMENT_CURRENCY'],
			'Format' => 3
		);

		$clientHttp = new HttpClient();
		$response = $clientHttp->post($refundUrl, $data);

		if ($response)
		{
			$xml = new \CDataXML();
			$xml->LoadString($response);
			$data = $xml->GetArray();
			if ($data && $data['result']['@']['firstcode'] == '0' && $data['result']['@']['secondcode'] == '0')
			{
				$result->setOperationType(PaySystem\ServiceResult::MONEY_LEAVING);
			}
			else
			{
				PaySystem\ErrorLog::add(array(
					'ACTION' => 'return',
					'MESSAGE' => 'assist error refund: firstcode='.$data['result']['@']['firstcode'].' secondcode='.$data['result']['@']['secondcode']
				));
				$result->addError(new EntityError(Loc::getMessage('SALE_PS_MESSAGE_ERROR_CONNECT_PAY_SYS')));
			}
		}
		else
		{
			$message = 'Incorrect server response';

			$result->addError(new Error($message));
			PaySystem\ErrorLog::add(array(
				'ACTION' => 'return',
				'MESSAGE' => $message
			));
		}

		return $result;
	}

	/**
	 * @param Payment $payment
	 * @param $request
	 * @return bool
	 */
	private function isCorrectHash(Payment $payment, Request $request)
	{
		$hash = md5(
			ToUpper(
				md5($this->getBusinessValue($payment, 'ASSIST_SHOP_SECRET_WORLD')).md5(
					$this->getBusinessValue($payment, 'ASSIST_SHOP_IDP').$request->get('ordernumber').$request->get('amount').$this->getBusinessValue($payment, 'PAYMENT_CURRENCY').$request->get('orderstate')
				)
			)
		);

		return (ToUpper($hash) == ToUpper($request->get('checkvalue')));
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return bool
	 */
	private function isCorrectSum(Payment $payment, Request $request)
	{
		$sum = $request->get('orderamount');
		$paymentSum = $this->getBusinessValue($payment, 'PAYMENT_SHOULD_PAY');

		return PriceMaths::roundByFormatCurrency($paymentSum, $payment->getField('CURRENCY')) == PriceMaths::roundByFormatCurrency($sum, $payment->getField('CURRENCY'));
	}

	/**
	 * @param PaySystem\ServiceResult $result
	 * @param Request $request
	 * @return mixed
	 */
	static public function sendResponse(PaySystem\ServiceResult $result, Request $request)
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();

		header('Content-Type: text/xml');
		header('Pragma: no-cache');
		$text = '<?xml version=\'1.0\' encoding=\'UTF-8\'?>\n';

		if ($result->isResultApplied())
		{
			$text .= '<pushpaymentresult firstcode=\'0\' secondcode=\'0\'>';
			$text .= '<order>';
			$text .= '<billnumber>'.$request->get('billnumber').'</billnumber>';
			$text .= '<packetdate>'.$request->get('packetdate').'</packetdate>';
			$text .= '</order>';
		}
		else
		{
			$text .= '<pushpaymentresult firstcode=\'9\' secondcode=\'7\'>';
		}

		$text .= '</pushpaymentresult>';

		echo $text;
		die();
	}

	/**
	 * @param Request $request
	 * @return array
	 */
	static public function getPaymentIdFromRequest(Request $request)
	{
		return $request->get('ordernumber');
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return PaySystem\ServiceResult
	 */
	public function processRequest(Payment $payment, Request $request)
	{
		$result = new PaySystem\ServiceResult();

		if ($this->isCorrectHash($payment, $request))
		{
			$status = str_replace(' ', '', $request->get('orderstate'));
			$psStatus = ($status == "Approved") ? "Y" : "N";

			$result->setPSData(
				array(
					"PS_STATUS" => $psStatus,
					"PS_STATUS_CODE" => substr($status, 0, 5),
					"PS_STATUS_DESCRIPTION" => Loc::getMessage('SALE_PS_DESCRIPTION_'.ToUpper($status)),
					"PS_STATUS_MESSAGE" => Loc::getMessage('SALE_PS_MESSAGE_'.ToUpper($status)),
					"PS_SUM" => $request->get('orderamount'),
					"PS_CURRENCY" => $request->get('ordercurrency'),
					"PS_INVOICE_ID" => $request->get('billnumber'),
					"PS_RESPONSE_DATE" => new DateTime()
				)
			);

			if ($this->isCorrectSum($payment, $request) &&
				$this->getBusinessValue($payment, 'PS_CHANGE_STATUS_PAY') == 'Y' &&
				$psStatus == 'Y' &&
				!$payment->isPaid()
			)
			{
				$result->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
			}
			else
			{
				$result->addError(new Error('Incorrect sum or payment flag'));
			}
		}
		else
		{
			$result->addError(new Error('Incorrect hash sum'));
		}

		if (!$result->isSuccess())
		{
			PaySystem\ErrorLog::add(array(
				'ACTION' => $request->get('orderstate'),
				'MESSAGE' => join('\n', $result->getErrorMessages())
			));
		}

		return $result;
	}

	/**
	 * @param Payment $payment
	 * @return bool
	 */
	protected function isTestMode(Payment $payment = null)
	{
		return ($this->getBusinessValue($payment, 'PS_IS_TEST') == 'Y');
	}

	/**
	 * @return array
	 */
	protected function getUrlList()
	{
		return array(
			'confirm' => array(
				self::ACTIVE_URL=> 'https://test.paysecure.ru/charge/charge.cfm.'
			),
			'return' => array(
				self::ACTIVE_URL=> 'https://secure.assist.ru/rvr/rvr.cfm',
				self::TEST_URL => 'https://test.paysecure.ru/cancel/cancel.cfm'
			),
			'pay' => array(
				self::ACTIVE_URL=> 'https://payments.paysecure.ru/pay/order.cfm',
				self::TEST_URL => 'https://test.paysecure.ru/pay/order.cfm'
			),
			'check' => array(
				self::ACTIVE_URL=> 'https://payments.paysecure.ru/orderstate/orderstate.cfm',
				self::TEST_URL=> 'https://test.paysecure.ru/orderstate/orderstate.cfm'
			)
		);
	}

	/**
	 * @return array
	 */
	static public function getCurrencyList()
	{
		return array('RUB', 'USD', 'EUR');
	}

	/**
	 * @param Payment $payment
	 * @return bool
	 */
	public function check(Payment $payment)
	{
		$serviceResult = new PaySystem\ServiceResult();

		$dtm = AddToTimeStamp(array("MM" => -1), false);

		$postData = array(
			'Ordernumber' => $this->getBusinessValue($payment, 'PAYMENT_ID'),
			'Merchant_ID' => $this->getBusinessValue($payment, 'ASSIST_SHOP_IDP'),
			'login' => $this->getBusinessValue($payment, 'ASSIST_SHOP_LOGIN'),
			'password' => $this->getBusinessValue($payment, 'ASSIST_SHOP_PASSWORD'),
			'FORMAT' => 3,
			'StartYear' => date('Y', $dtm),
			'StartMonth' => date('n', $dtm),
			'StartYDay' => date('j', $dtm)
		);

		$httpClient = new HttpClient();
		$queryRes = $httpClient->query('POST', $this->getUrl($payment, 'check'), $postData);

		if ($queryRes)
		{
			$httpResult = $httpClient->getResult();

			$objXML = new \CDataXML();
			$objXML->LoadString($httpResult);
			$data = $objXML->GetArray();

			if ($data && $data['result']['@']['firstcode'] == '0')
			{
				$orderData = $data['result']['#']['order'][0]['#'];
				if ((int)$orderData['ordernumber'][0]['#'] == $this->getBusinessValue($payment, 'PAYMENT_ID'))
				{
					$check = ToUpper(md5(ToUpper(md5($this->getBusinessValue($payment, 'ASSIST_SHOP_SECRET_WORLD')).md5($this->getBusinessValue($payment, 'ASSIST_SHOP_IDP').$orderData['ordernumber'][0]['#'].$orderData['orderamount'][0]['#'].$orderData['ordercurrency'][0]['#'].$orderData['orderstate'][0]['#']))));

					if (ToUpper($orderData['checkvalue'][0]['#']) == $check)
					{
						$status = str_replace(' ', '', $orderData['orderstate'][0]['#']);

						$psData = array(
							'PS_STATUS' => ($orderData['orderstate'][0]['#'] == 'Approved' ? 'Y' : 'N'),
							'PS_STATUS_CODE' => substr($orderData['orderstate'][0]['#'], 0, 5),
							'PS_STATUS_DESCRIPTION' => Loc::getMessage('SALE_PS_DESCRIPTION_'.ToUpper($status)),
							'PS_STATUS_MESSAGE' => Loc::getMessage('SALE_PS_MESSAGE_'.ToUpper($status)),
							'PS_SUM' => DoubleVal($orderData['orderamount'][0]['#']),
							'PS_CURRENCY' => $orderData['ordercurrency'][0]['#'],
							'PS_RESPONSE_DATE' => new DateTime(),
						);
						$serviceResult->setPsData($psData);

						if (
							!$payment->isPaid() &&
							$this->getBusinessValue($payment, 'PS_CHANGE_STATUS_PAY') == 'Y' &&
							$psData["PS_STATUS"] == "Y" &&
							$payment->getSum() == floatval($psData["PS_SUM"])
						)
						{
							$serviceResult->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
						}
					}
				}
			}
			else
			{
				$serviceResult->addError(new EntityError(Loc::getMessage('SALE_PS_MESSAGE_ERROR_CONNECT_PAY_SYS')));
			}
		}

		return $serviceResult;
	}
}