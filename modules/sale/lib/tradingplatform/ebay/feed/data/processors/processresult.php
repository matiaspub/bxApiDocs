<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Processors;

use Bitrix\Main\ArgumentNullException;

class ProcessResult extends DataProcessor
{
	static public function process($data)
	{
		if(!isset($data["RESULT_ID"]))
			throw new ArgumentNullException("data[\"RESULT_ID\"]");

		$id = $data["RESULT_ID"];
		unset($data["RESULT_ID"]);

		if(isset($data["PROCESSING_REQUEST_ID"]) || isset($data["PROCESSING_RESULT"]))
			$fields = $data;
		else
			$fields = array(
				"PROCESSING_REQUEST_ID" => "-",
				"PROCESSING_RESULT" => "-"
			);

		return \Bitrix\Sale\TradingPlatform\Ebay\Feed\ResultsTable::update($id, $fields);
	}
}