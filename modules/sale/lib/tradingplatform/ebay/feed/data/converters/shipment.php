<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Converters;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\SystemException;

class Shipment extends DataConverter
{
	//todo: multiply track numbers
	static public function convert($data)
	{
		$result = "";

		if(!is_array($data))
			throw new ArgumentTypeException("data", "array");

		foreach($data as $item)
		{
			if(!isset($item["ORDER_ID"])
				|| !isset($item["ORDER_LINE_ITEM_ID"])
				|| !isset($item["DELIVERY_NAME"])
				|| !isset($item["TRACKING_NUMBER"])
			)
			{
				throw new SystemException("Wrong structure of item in Shipment::convert()");
			}

			$result .= "\t<Shipment>\n".
				"\t\t<OrderID>".$item["ORDER_ID"]."</OrderID>\n".
				"\t\t<OrderLineItemID>".$item["ORDER_LINE_ITEM_ID"]."</OrderLineItemID>\n".
				//"\t\t<ShippedTime>".$data["SHIPPED_TIME"]."</ShippedTime>\n".
				 "\t\t<ShipmentTracking>\n".
				"\t\t\t<ShippingCarrier>".$item["DELIVERY_NAME"]."</ShippingCarrier>\n".
				"\t\t\t<TrackingNumber>".$item["TRACKING_NUMBER"]."</TrackingNumber>\n".
				"\t\t</ShipmentTracking>\n".
				"\t</Shipment>\n";
		}

		return $result;
	}
}