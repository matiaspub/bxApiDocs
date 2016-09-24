<?php

namespace Bitrix\Sale;

use Bitrix\Main;

class PriceMaths
{
	/**
	 * @param $value
	 *
	 * @return float
	 * @throws Main\ArgumentNullException
	 */
	public static function roundPrecision($value)
	{
		$valuePrecision = Main\Config\Option::get('sale', 'value_precision', 2);
		if (intval($valuePrecision) <= 0)
		{
			$valuePrecision = 2;
		}

		return roundEx($value, $valuePrecision);
	}

	/**
	 * @param $price
	 * @param $currency
	 * 
	 * @return float
	 */
	public static function roundByFormatCurrency($price, $currency)
	{
		return floatval(SaleFormatCurrency($price, $currency, false, true));
	}
}