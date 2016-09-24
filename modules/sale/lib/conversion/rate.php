<?php

namespace Bitrix\Sale\Conversion;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

abstract class Rate extends \Bitrix\Conversion\Rate
{
	protected $sum, $traffic, $quantity, $rate;

	public function getRate()
	{
		return $this->rate;
	}

	public function getSum()
	{
		return $this->sum;
	}

	public function getQuantity()
	{
		return $this->quantity;
	}

	public function getTraffic()
	{
		return $this->traffic;
	}
}
