<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Converters;

use Bitrix\Sale\TradingPlatform\Xml2Array;

class Order extends DataConverter
{
	static public function convert($data)
	{
		$result = Xml2Array::convert($data, false);
		return Xml2Array::normalize($result["Order"]);
	}
}