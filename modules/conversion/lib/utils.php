<?php

namespace Bitrix\Conversion;

use Bitrix\Main\Loader;
use Bitrix\Currency\CurrencyLangTable;

final class Utils
{
	static public function convertToBaseCurrency($value, $currency)
	{
		static $module, $baseCurrency;

		if (! $module)
		{
			$module = Loader::includeModule('currency');
			$baseCurrency = Config::getBaseCurrency();
		}

		if ($module && $currency != $baseCurrency)
		{
			$value = \CCurrencyRates::ConvertCurrency($value, $currency, $baseCurrency);
		}

		return $value;
	}

	static public function formatToBaseCurrency($value, $format = null)
	{
		static $module, $baseCurrency;

		if (! $module)
		{
			$module = Loader::includeModule('currency');
			$baseCurrency = Config::getBaseCurrency();
		}

		if ($module)
		{
			$value = \CCurrencyLang::CurrencyFormat($value, $baseCurrency);
		}

		return $value;
	}

	/** @deprecated */
	static public function getBaseCurrencyUnit() // TODO remove from sale
	{
		static $unit;

		if (! $unit)
		{
			$unit = Config::getBaseCurrency();

			if (LANGUAGE_ID == 'ru' && Loader::includeModule('currency'))
			{
				switch ($unit)
				{
					case 'RUB':
					case 'BYR':
					case 'UAH':

						if ($row = CurrencyLangTable::getByPrimary(array('CURRENCY' => $unit, 'LID' => LANGUAGE_ID))->fetch())
						{
							$unit = trim(str_replace('#', '', $row['FORMAT_STRING']));
						}

						break;
				}
			}
		}

		return $unit;
	}
}