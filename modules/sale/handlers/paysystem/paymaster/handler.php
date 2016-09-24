<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Error;
use Bitrix\Main\Request;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::registerAutoLoadClasses('sale', array(PaySystem\Manager::getClassNameFromPath('WebMoney') => 'handlers/paysystem/webmoney/handler.php'));

Loc::loadMessages(__FILE__);

class PayMasterHandler extends WebMoneyHandler
{
	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return PaySystem\ServiceResult
	 */
	public function initiatePay(Payment $payment, Request $request = null)
	{
		$extraParams = array(
			'PS_MODE' => $this->service->getField('PS_MODE'),
			'URL' => $this->getUrl($payment, 'pay'),
			'BX_PAYSYSTEM_CODE' => $payment->getPaymentSystemId()
		);
		$this->setExtraParams($extraParams);

		return $this->showTemplate($payment, 'template');
	}

	/**
	 * @return array
	 */
	static public function getIndicativeFields()
	{
		return array('BX_HANDLER' => 'PAYMASTER');
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

		if ((int)$request->get('LMI_PREREQUEST') == 1 || (int)$request->get('LMI_PREREQUEST') == 2)
		{
			if (
				!$this->checkSum($payment, $request) ||
				$request->get('LMI_CURRENCY') != $this->getBusinessValue($payment, 'PAYMENT_CURRENCY') ||
				$request->get('LMI_MERCHANT_ID') != $this->getBusinessValue($payment, 'PAYMASTER_SHOP_ACCT')
			)
			{
				$serviceResult->addError(new Error(Loc::getMessage('SALE_HPS_PAYMASTER_ERROR_PARAMS_VALUE')));
				return $serviceResult;
			}

			$serviceResult->setData(array('CODE' => 'YES'));
		}
		else
		{
			if ($this->checkHash($payment, $request))
			{
				$psDescription = '';

				if ($request->get("LMI_SIM_MODE") != 0)
					$psDescription .= Loc::getMessage('SALE_HPS_PAYMASTER_SIM_MODE_TEST');

				$psDescription .= str_replace('#MERCHANT_ID#', $request->get("LMI_MERCHANT_ID"), Loc::getMessage('SALE_HPS_PAYMASTER_DESC_MERCHANT_ID'));
				$psDescription .= str_replace('#SYS_INVS_NO#', $request->get("LMI_SYS_INVS_NO"), Loc::getMessage('SALE_HPS_PAYMASTER_DESC_SYS_INVS_NO'));
				$psDescription .= str_replace('#SYS_TRANS_NO#', $request->get("LMI_SYS_TRANS_NO"), Loc::getMessage('SALE_HPS_PAYMASTER_DESC_SYS_TRANS_NO'));
				$psDescription .= str_replace('#SYS_TRANS_DATE#', $request->get("LMI_SYS_TRANS_DATE"), Loc::getMessage('SALE_HPS_PAYMASTER_DESC_SYS_TRANS_DATE'));
				$psDescription .= str_replace('#PAY_SYSTEM#', $request->get("LMI_PAY_SYSTEM"), Loc::getMessage('SALE_HPS_PAYMASTER_DESC_PAY_SYSTEM'));

				$psMessage = '';
				if ($request->get("LMI_PAYER_PURSE") !== null)
					$psMessage .= str_replace('#PAYER_PURSE#', $request->get("LMI_PAYER_PURSE"), Loc::getMessage('SALE_HPS_PAYMASTER_DESC_PAYER_PURSE'));

				if ($request->get("LMI_PAYER_WM") !== null)
					$psMessage .= str_replace('#PAYER_WM#', $request->get("LMI_PAYER_WM"), Loc::getMessage('SALE_HPS_PAYMASTER_DESC_PAYER_WM'));

				if ($request->get("LMI_PAYMER_NUMBER") !== null)
					$psMessage .= str_replace('#PAYMER_NUMBER#', $request->get("LMI_PAYER_NUMBER"), Loc::getMessage('SALE_HPS_PAYMASTER_DESC_PAYER_NUMBER'));

				if ($request->get("LMI_PAYMER_EMAIL") !== null)
					$psMessage .= str_replace('#PAYMER_EMAIL#', $request->get("LMI_PAYER_EMAIL"), Loc::getMessage('SALE_HPS_PAYMASTER_DESC_PAYER_EMAIL'));

				if ($request->get("LMI_TELEPAT_PHONENUMBER") !== null)
					$psMessage .= str_replace('#TELEPAT_PHONENUMBER#', $request->get("LMI_TELEPAT_PHONENUMBER"), Loc::getMessage('SALE_HPS_PAYMASTER_DESC_TELEPAT_PHONENUMBER'));

				if ($request->get("LMI_TELEPAT_ORDERID") !== null)
					$psMessage .= str_replace('#TELEPAT_ORDERID#', $request->get("LMI_TELEPAT_ORDERID"), Loc::getMessage('SALE_HPS_PAYMASTER_DESC_TELEPAT_ORDERID'));

				$psFields = array(
					"PS_STATUS" => "Y",
					"PS_STATUS_CODE" => "-",
					"PS_STATUS_DESCRIPTION" => $psDescription,
					"PS_STATUS_MESSAGE" => $psMessage,
					"PS_SUM" => $request->get("LMI_PAYMENT_AMOUNT"),
					"PS_CURRENCY" => $this->getBusinessValue($payment, 'PAYMENT_CURRENCY'),
					"PS_RESPONSE_DATE" => new DateTime()
				);

				if ($this->checkSum($payment, $request)
					&& $this->getBusinessValue($payment, 'PAYMENT_CURRENCY') == $request->get("LMI_CURRENCY")
					&& $this->getBusinessValue($payment, 'PAYMASTER_SHOP_ACCT') == $request->get("LMI_MERCHANT_ID")
					&& !$payment->isPaid()
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

		if (!$serviceResult->isSuccess())
		{
			PaySystem\ErrorLog::add(array(
				'ACTION' => 'processRequest',
				'MESSAGE' => join(' ', $serviceResult->getErrorMessages())
			));
		}

		return $serviceResult;
	}


	/**
	 * @return array
	 */
	protected function getUrlList()
	{
		return array(
			'pay' => array(
				self::ACTIVE_URL => 'https://paymaster.ru/Payment/Init'
			)
		);
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return bool
	 */
	protected function checkHash(Payment $payment, Request $request)
	{
		$algorithm = $this->getBusinessValue($payment, 'PAYMASTER_HASH_ALGO');

		$string = $request->get("LMI_MERCHANT_ID").";".$request->get("LMI_PAYMENT_NO").";".$request->get("LMI_SYS_PAYMENT_ID").";".$request->get("LMI_SYS_PAYMENT_DATE").";".$request->get("LMI_PAYMENT_AMOUNT").";".$request->get("LMI_CURRENCY").";".$request->get("LMI_PAID_AMOUNT").";".$request->get("LMI_PAID_CURRENCY").";".$request->get("LMI_PAYMENT_SYSTEM").";".$request->get("LMI_SIM_MODE").";".$this->getBusinessValue($payment, 'PAYMASTER_CNST_SECRET_KEY');

		$hash = base64_encode(hash($algorithm, $string, true));

		return ToUpper($hash) == ToUpper($request->get('LMI_HASH'));
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

		$data = $result->getData();
		if (array_key_exists('CODE', $data))
		{
			echo $data['CODE'];
			die();
		}
	}
}