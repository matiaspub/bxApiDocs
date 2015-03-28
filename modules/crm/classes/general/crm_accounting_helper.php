<?php
class CCrmAccountingHelper
{
	public static function PrepareAccountingData($arFields)
	{
		$accountCurrencyID = CCrmCurrency::GetAccountCurrencyID();
		if(!isset($accountCurrencyID[0]))
		{
			return false;
		}

		$currencyID = isset($arFields['CURRENCY_ID']) ? strval($arFields['CURRENCY_ID']) : '';
		if(!CCrmCurrency::GetByID($currencyID))
		{
			// Currency is invalid or not assigned
			return false;
		}

		if($currencyID === $accountCurrencyID)
		{
			// Avoid conversion to float since possible data lost
			return array(
				'ACCOUNT_CURRENCY_ID' => $accountCurrencyID,
				'ACCOUNT_SUM' => isset($arFields['SUM']) ? $arFields['SUM'] : 0.0
			);
		}

		$account = CCrmCurrency::ConvertMoney(
			isset($arFields['SUM']) ? doubleval($arFields['SUM']) : 0.0,
				$currencyID,
				$accountCurrencyID,
				isset($arFields['EXCH_RATE']) ? doubleval($arFields['EXCH_RATE']) : -1
			);

		return array(
			'ACCOUNT_CURRENCY_ID' => $accountCurrencyID,
			'ACCOUNT_SUM' => $account
		);
	}
}
