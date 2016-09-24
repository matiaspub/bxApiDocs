<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Converters;

use \Bitrix\Main\SystemException;

class Inventory extends DataConverter
{
	public function convert($data)
	{
		$result = "";

		if(isset($data["OFFERS"]) && is_array($data["OFFERS"]) && !empty($data["OFFERS"]))
		{
			foreach($data["OFFERS"] as $offer)
				$result .= $this->getItemData($offer, $data["IBLOCK_ID"]."_".$data["ID"]."_");
		}
		else
		{
			$result .= $this->getItemData($data, $data["IBLOCK_ID"]."_");
		}

		return $result;
	}

	protected function getItemData($data, $skuPrefix = "")
	{
		if(!isset($data["PRICES"]["MIN"]) || $data["PRICES"]["MIN"] <= 0)
			throw new SystemException("Can't find the price for product id: ".$data["ID"]." ! ".__METHOD__);

		if(!isset($data["QUANTITY"]))
			throw new SystemException("Can't find the quantity for product id: ".$data["ID"]." ! ".__METHOD__);

		$result = "\t<Inventory>\n";
		$result .= "\t\t<SKU>".$skuPrefix.$data["ID"]."</SKU>\n";
		$result .= "\t\t<Price>".$data["PRICES"]["MIN"]."</Price>\n";
		$result .= "\t\t<Quantity>".($data["QUANTITY"] ? $data["QUANTITY"] : 1)."</Quantity>\n";
		$result .= "\t</Inventory>\n";

		return $result;
	}
} 