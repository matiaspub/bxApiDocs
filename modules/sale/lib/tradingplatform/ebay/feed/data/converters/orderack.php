<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Converters;

use \Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\SystemException;

class OrderAck extends DataConverter
{
	static public function convert($data)
	{
		if(!is_array($data))
			throw new ArgumentTypeException("data", "array");

		$result = "";

		foreach($data as $item)
		{
			if(empty($item["ORDER_ID"]) || empty($item["ORDER_LINE_ITEM_ID"]))
				throw new SystemException("Wrong structure of ack data item");

			$result .=
				"\t<OrderAck>\n".
				"\t\t<OrderID>".$item["ORDER_ID"]."</OrderID>\n".
				"\t\t<OrderLineItemID>".$item["ORDER_LINE_ITEM_ID"]."</OrderLineItemID>\n".
				"\t</OrderAck>\n";
		}

		return $result;
	}
}