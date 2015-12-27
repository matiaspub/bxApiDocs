<?php

namespace Bitrix\Sale\Conversion;

use Bitrix\Main\Localization\Loc;

class CartRate extends Rate
{
	static public function getTitle()
	{
		return Loc::getMessage('SALE_CONVERSION_RATE_CART_TITLE');
	}

	static public function getCounters()
	{
		return array(
			'sale_cart_sum_add',
			'sale_cart_add_day',
			'conversion_visit_day',
		);
	}

	static public function newFromCounters(array $counters)
	{
		$sum      = $counters['sale_cart_sum_add'] ?: 0;
		$quantity = $counters['sale_cart_add_day'] ?: 0;
		$traffic  = $counters['conversion_visit_day'] ?: 0;

		$rate = new static;
		$rate->sum      = $sum;
		$rate->quantity = $quantity;
		$rate->traffic  = $traffic;
		$rate->rate     = $traffic ? $quantity / $traffic : 0;

		return $rate;
	}
}
