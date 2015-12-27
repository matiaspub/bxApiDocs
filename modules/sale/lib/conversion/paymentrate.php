<?php

namespace Bitrix\Sale\Conversion;

use Bitrix\Main\Localization\Loc;

class PaymentRate extends Rate
{
	static public function getTitle()
	{
		return Loc::getMessage('SALE_CONVERSION_RATE_PAYMENT_TITLE');
	}

	static public function getCounters()
	{
		return array(
			'sale_payment_sum_add',
			'sale_payment_add_day',
			'conversion_visit_day',
		);
	}

	static public function newFromCounters(array $counters)
	{
		// TODO is_numeric
		$sum      = $counters['sale_payment_sum_add'] ?: 0;
		$quantity = $counters['sale_payment_add_day'] ?: 0;
		$traffic  = $counters['conversion_visit_day'] ?: 0;

		$rate = new static;
		$rate->sum      = $sum;
		$rate->quantity = $quantity;
		$rate->traffic  = $traffic;
		$rate->rate     = $traffic ? $quantity / $traffic : 0;

		return $rate;
	}
}
