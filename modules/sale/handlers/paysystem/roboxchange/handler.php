<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Error;
use Bitrix\Main\Request;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PriceMaths;

Loc::loadMessages(__FILE__);

class RoboxchangeHandler extends PaySystem\ServiceHandler
{
	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return PaySystem\ServiceResult
	 */
	public function initiatePay(Payment $payment, Request $request = null)
	{
		$test = '';
		if ($this->isTestMode($payment))
			$test = '_TEST';

		$signatureValue = md5(
			$this->getBusinessValue($payment, 'ROBOXCHANGE_SHOPLOGIN').":".
			$this->getBusinessValue($payment, 'PAYMENT_SHOULD_PAY').":".
			$this->getBusinessValue($payment, 'PAYMENT_ID').":".
			$this->getBusinessValue($payment, 'ROBOXCHANGE_SHOPPASSWORD'.$test).':'.
			'SHP_BX_PAYSYSTEM_CODE='.$payment->getPaymentSystemId().':'.
			'SHP_HANDLER=ROBOXCHANGE'
		);

		$params = array(
			'URL' => $this->getUrl($payment, 'pay'),
			'PS_MODE' => $this->service->getField('PS_MODE'),
			'SIGNATURE_VALUE' => $signatureValue,
			'BX_PAYSYSTEM_CODE' => $payment->getPaymentSystemId(),
		);
		$this->setExtraParams($params);

		return $this->showTemplate($payment, "template");
	}

	/**
	 * @return array
	 */
	public static function getIndicativeFields()
	{
		return array('SHP_HANDLER' => 'ROBOXCHANGE');
	}

	/**
	 * @param Request $request
	 * @param $paySystemId
	 * @return bool
	 */
	static protected function isMyResponseExtended(Request $request, $paySystemId)
	{
		$id = $request->get('SHP_BX_PAYSYSTEM_CODE');
		return $id == $paySystemId;
	}

	/**
	 * @param Payment $payment
	 * @param $request
	 * @return bool
	 */
	private function isCorrectHash(Payment $payment, Request $request)
	{
		$test = '';
		if ($this->isTestMode($payment))
			$test = '_TEST';

		$hash = md5($request->get('OutSum').":".$request->get('InvId').":".$this->getBusinessValue($payment, 'ROBOXCHANGE_SHOPPASSWORD2'.$test).':SHP_BX_PAYSYSTEM_CODE='.$payment->getPaymentSystemId().':SHP_HANDLER=ROBOXCHANGE');

		return ToUpper($hash) == ToUpper($request->get('SignatureValue'));
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return bool
	 */
	private function isCorrectSum(Payment $payment, Request $request)
	{
		$sum = PriceMaths::roundByFormatCurrency($request->get('OutSum'), $payment->getField('CURRENCY'));
		$paymentSum = PriceMaths::roundByFormatCurrency($this->getBusinessValue($payment, 'PAYMENT_SHOULD_PAY'), $payment->getField('CURRENCY'));

		return $paymentSum == $sum;
	}

	/**
	 * @param Request $request
	 * @return mixed
	 */
	static public function getPaymentIdFromRequest(Request $request)
	{
		return $request->get('InvId');
	}

	/**
	 * @return mixed
	 */
	protected function getUrlList()
	{
		return array(
			'pay' => array(
				self::ACTIVE_URL => 'https://merchant.roboxchange.com/Index.aspx'
			)
		);
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
			return $this->processNoticeAction($payment, $request);
		}
		else
		{
			PaySystem\ErrorLog::add(array(
				'ACTION' => 'processRequest',
				'MESSAGE' => 'Incorrect hash'
			));
			$result->addError(new Error('Incorrect hash'));
		}

		return $result;
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return PaySystem\ServiceResult
	 */
	private function processNoticeAction(Payment $payment, Request $request)
	{
		$result = new PaySystem\ServiceResult();

		$psStatusDescription = Loc::getMessage('SALE_HPS_ROBOXCHANGE_RES_NUMBER').": ".$request->get('InvId');
		$psStatusDescription .= "; ".Loc::getMessage('SALE_HPS_ROBOXCHANGE_RES_DATEPAY').": ".date("d.m.Y H:i:s");

		if ($request->get("IncCurrLabel") !== null)
			$psStatusDescription .= "; ".Loc::getMessage('SALE_HPS_ROBOXCHANGE_RES_PAY_TYPE').": ".$request->get("IncCurrLabel");

		$fields = array(
			"PS_STATUS" => "Y",
			"PS_STATUS_CODE" => "-",
			"PS_STATUS_DESCRIPTION" => $psStatusDescription,
			"PS_STATUS_MESSAGE" => Loc::getMessage('SALE_HPS_ROBOXCHANGE_RES_PAYED'),
			"PS_SUM" => $request->get('OutSum'),
			"PS_CURRENCY" => $this->getBusinessValue($payment, "PAYMENT_CURRENCY"),
			"PS_RESPONSE_DATE" => new DateTime(),
		);

		$result->setPsData($fields);

		if ($this->isCorrectSum($payment, $request))
		{
			$result->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
		}
		else
		{
			PaySystem\ErrorLog::add(array(
				'ACTION' => 'processNoticeAction',
				'MESSAGE' => 'Incorrect sum'
			));
			$result->addError(new Error('Incorrect sum'));
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
	static public function getCurrencyList()
	{
		return array('RUB');
	}

	/**
	 * @param PaySystem\ServiceResult $result
	 * @param Request $request
	 * @return mixed
	 */
	static public function sendResponse(PaySystem\ServiceResult $result, Request $request)
	{
		global $APPLICATION;
		if ($result->isResultApplied())
		{
			$APPLICATION->RestartBuffer();
			echo 'OK'.$request->get('InvId');
		}
	}

	/**
	 * @return array
	 */
	public static function getHandlerModeList()
	{
		return array(
			'' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_NO_CHOOSE'),
			'WMR' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_WMRM_EMONEY'),
			'AlfaBank' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_ALFABANKOCEANR_BANK'),
			'BankCard' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_OCEANBANKOCEANR_BANK'),
			'PhoneMegafon' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_MEGAFONR_MOBILE'),
			'PhoneMTS' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_MTSR_MOBILE'),
			'StoreEuroset' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_RAPIDAOCEANEUROSETR_OTHER'),
			'PhoneTele2' => 'Tele2',
			'PhoneBeeline' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_MMixplatBeelineRIBR'),
			'BankRSB' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_RussianStandardBankRIBR'),
			'BankTrust' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_BSSNationalBankTRUSTR'),
			'BankTatfondbank' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_BSSTatfondbankR'),
			'BankPSB' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_PSKBR'),
			'HandyBank' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_HandyBankMerchantOceanR'),
			'HandyBankBO' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_HandyBankBO'),
			'StoreSvyaznoy' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_RapidaRIBSvyaznoyR'),
			'HandyBankFB' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_HandyBankFB'),
			'HandyBankFU' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_HandyBankFU'),
			'HandyBankKB' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_HandyBankKB'),
			'HandyBankKSB' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_HandyBankKSB'),
			'HandyBankLOB' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_HandyBankLOB'),
			'HandyBankNSB' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_HandyBankNSB'),
			'HandyBankTB' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_HandyBankTB'),
			'HandyBankVIB' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_HandyBankVIB'),
			'BankMTEB' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_BSSMezhtopenergobankR'),
			'BankMIN' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_MINBankR'),
			'BankFBID' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_BSSFederalBankForInnovationAndDevelopmentR'),
			'BankInteza' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_BSSIntezaR'),
			'BankGorod' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_BSSBankGorodR'),
			'BankAVB' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_BSSAvtovazbankR'),
			'KUBank' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_KUBankR'),
			'MobileRobokassa' => Loc::getMessage('SALE_HPS_ROBOXCHANGE_BANKOCEAN3CHECKR'),
		);
	}
}