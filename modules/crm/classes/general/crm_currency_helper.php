<?php
if (!CModule::IncludeModule('currency'))
{
	return false;
}

class CCrmCurrencyHelper
{
	public static function PrepareListItems()
	{
		if (!CModule::IncludeModule('currency'))
		{
			return array();
		}

		$ary = array();
		$dbCurrencies = CCurrency::GetList(($by='sort'), ($order='asc'));
		while ($arCurrency = $dbCurrencies->Fetch())
		{
			$ary[$arCurrency['CURRENCY']] = $arCurrency['FULL_NAME'];
		}

		return $ary;
	}
}
