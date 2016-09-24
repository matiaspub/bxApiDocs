<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\PaySystem\ServiceResult;
use Bitrix\Sale\PriceMaths;

Loc::loadMessages(__FILE__);

class WebMoneyHandler extends PaySystem\ServiceHandler
{
	/**
	 * @return array
	 */
	static public function getIndicativeFields()
	{
		return array('BX_HANDLER' => 'WEBMONEY');
	}

	/**
	 * @param Request $request
	 * @param $paySystemId
	 * @return bool
	 */
	static protected function isMyResponseExtended(Request $request, $paySystemId)
	{
		$id = $request->get('BX_PAYSYSTEM_CODE');
		return $id == $paySystemId;
	}

	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return PaySystem\ServiceResult
	 */
	public function initiatePay(Payment $payment, Request $request = null)
	{
		$extraParams = array(
			'URL' => $this->getUrl($payment, 'pay'),
			'ENCODING' => $this->service->getField('ENCODING'),
			'BX_PAYSYSTEM_CODE' => $payment->getPaymentSystemId()
		);

		$this->setExtraParams($extraParams);

		return $this->showTemplate($payment, 'template');
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

		if ((int)$request->get('LMI_PREREQUEST') == 1)
		{
			if (
				!$this->checkSum($payment, $request) ||
				$request->get('LMI_PAYEE_PURSE') != $this->getBusinessValue($payment, 'WEBMONEY_SHOP_ACCT')
			)
			{
				$serviceResult->addError(new Error('Incorrect sum or WEBMONEY_SHOP_ACCT'));
			}
		}
		else
		{
			if ($this->checkHash($payment, $request))
			{
				$psDescription = '';

				if ($request->get("LMI_MODE") != 0)
					$psDescription .= Loc::getMessage('SALE_HPS_WEBMONEY_TEST');

				$psDescription .= Loc::getMessage('SALE_HPS_WEBMONEY_PAYEE_PURSE', array('#PAYEE_PURSE#' => $request->get("LMI_PAYEE_PURSE")))."; ";
				$psDescription .= Loc::getMessage('SALE_HPS_WEBMONEY_INVS_NO', array('#INVS_NO#' => $request->get("LMI_SYS_INVS_NO")))."; ";
				$psDescription .= Loc::getMessage('SALE_HPS_WEBMONEY_TRANS_NO', array('#TRANS_NO#' => $request->get("LMI_SYS_TRANS_NO")))."; ";
				$psDescription .= Loc::getMessage('SALE_HPS_WEBMONEY_TRANS_DATE', array('#TRANS_DATE#' => $request->get("LMI_SYS_TRANS_DATE")))."; ";

				$psMessage = "";
				if ($request->get("LMI_PAYER_PURSE") !== null)
					$psMessage .= Loc::getMessage('SALE_HPS_WEBMONEY_PAYER_PURSE', array('#PAYER_PURSE#' => $request->get("LMI_PAYER_PURSE")))."; ";

				if ($request->get("LMI_PAYER_WM") !== null)
					$psMessage .= Loc::getMessage('SALE_HPS_WEBMONEY_PAYER_WM', array('#PAYER_WM#' => $request->get("LMI_PAYER_WM")))."; ";

				if ($request->get("LMI_PAYMER_NUMBER") !== null)
					$psMessage .= Loc::getMessage('SALE_HPS_WEBMONEY_PAYMER_NUMBER', array('#PAYMER_NUMBER#' => $request->get("LMI_PAYMER_NUMBER")))."; ";

				if ($request->get("LMI_PAYMER_EMAIL") !== null)
					$psMessage .= Loc::getMessage('SALE_HPS_WEBMONEY_PAYMER_EMAIL', array('#PAYMER_EMAIL#' => $request->get("LMI_PAYMER_EMAIL")))."; ";

				if ($request->get("LMI_TELEPAT_PHONENUMBER") !== null)
					$psMessage .= Loc::getMessage('SALE_HPS_WEBMONEY_TELEPAT_PHONENUMBER', array('#TELEPAT_PHONENUMBER#' => $request->get("LMI_TELEPAT_PHONENUMBER")))."; ";

				if ($request->get("LMI_TELEPAT_ORDERID") !== null)
					$psMessage .= Loc::getMessage('SALE_HPS_WEBMONEY_TELEPAT_ORDERID', array('#TELEPAT_ORDERID#' => $request->get("LMI_TELEPAT_ORDERID")));

				$psFields = array(
					"PS_STATUS" => "Y",
					"PS_STATUS_CODE" => "-",
					"PS_STATUS_DESCRIPTION" => $psDescription,
					"PS_STATUS_MESSAGE" => $psMessage,
					"PS_SUM" => $request->get('LMI_PAYMENT_AMOUNT'),
					"PS_CURRENCY" => $payment->getField('CURRENCY'),
					"PS_RESPONSE_DATE" => new DateTime()
				);

				if ($this->checkSum($payment, $request)
					&& $this->getBusinessValue($payment, 'WEBMONEY_SHOP_ACCT') == $request->get("LMI_PAYEE_PURSE")
					&& !$payment->isPaid()
					&& $this->getBusinessValue($payment, 'PS_CHANGE_STATUS_PAY') == "Y"
					)
				{
					$serviceResult->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
					$serviceResult->setPsData($psFields);
				}
				else
				{
					$serviceResult->addError(new Error('Incorrect payment sum or payment flag'));
				}
			}
			else
			{
				$serviceResult->addError(new Error('Incorrect payment hash'));
			}
		}

		return $serviceResult;
	}

	/**
	 * @param Request $request
	 * @return array
	 */
	static public function getPaymentIdFromRequest(Request $request)
	{
		return $request->get('LMI_PAYMENT_NO');
	}

	/**
	 * @param Payment $payment
	 * @return mixed
	 */
	protected function isTestMode(Payment $payment = null)
	{
		return $this->getBusinessValue($payment, 'PS_IS_TEST');
	}

	/**
	 * @return array
	 */
	protected function getUrlList()
	{
		return array(
			'pay' => array(
				self::ACTIVE_URL => 'https://merchant.webmoney.ru/lmi/payment.asp'
			)
		);
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return bool
	 */
	protected function checkSum(Payment $payment, Request $request)
	{
		$paymentShouldPay = PriceMaths::roundByFormatCurrency($this->getBusinessValue($payment, 'PAYMENT_SHOULD_PAY'), $payment->getField('CURRENCY'));
		$lmiPaymentAmount = PriceMaths::roundByFormatCurrency($request->get('LMI_PAYMENT_AMOUNT'), $payment->getField('CURRENCY'));

		return $paymentShouldPay == $lmiPaymentAmount;
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return bool
	 */
	protected function checkHash(Payment $payment, Request $request)
	{
		$algorithm = $this->getBusinessValue($payment, 'WEBMONEY_HASH_ALGO');

		$string = $request->get("LMI_PAYEE_PURSE").$request->get("LMI_PAYMENT_AMOUNT").$request->get("LMI_PAYMENT_NO").$request->get("LMI_MODE").$request->get("LMI_SYS_INVS_NO").$request->get("LMI_SYS_TRANS_NO").$request->get("LMI_SYS_TRANS_DATE").$this->getBusinessValue($payment, 'WEBMONEY_CNST_SECRET_KEY').$request->get("LMI_PAYER_PURSE").$request->get("LMI_PAYER_WM");

		$hash = hash($algorithm, $string);

		return ToUpper($hash) == ToUpper($request->get('LMI_HASH'));
	}

	/**
	 * @return array
	 */
	static public function getCurrencyList()
	{
		return array('RUB', 'USD', 'EUR', 'UAH');
	}

	/**
	 * @param ServiceResult $result
	 * @param Request $request
	 * @return mixed
	 */
	static public function sendResponse(ServiceResult $result, Request $request)
	{
		global $APPLICATION;

		if ($result->isSuccess() && (int)$request->get('LMI_PREREQUEST') == 1)
		{
			$APPLICATION->RestartBuffer();

			echo 'YES';
			die();
		}
	}

}