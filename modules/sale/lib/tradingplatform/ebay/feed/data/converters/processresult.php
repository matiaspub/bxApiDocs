<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Converters;

use Bitrix\Main\ArgumentNullException;

class ProcessResult extends DataConverter
{
	static public function convert($data)
	{
		if(!isset($data["RESULT_ID"]))
			throw new ArgumentNullException("data[\"RESULT_ID\"]");

		if(!isset($data["CONTENT"]))
			throw new ArgumentNullException("data[\"CONTENT\"]");

		$result["RESULT_ID"] = $data["RESULT_ID"];

		if(strlen($data["CONTENT"]) >= 0)
		{
			$strings = explode("\n", $data["CONTENT"]);
			$fields = array();

			if(is_array($strings))
			{
				foreach($strings as $string)
				{
					$info = json_decode($string, true);

					if(strpos($info["message"], "Processing Request #") !== false)
						$fields["PROCESSING_REQUEST_ID"] = substr($info["message"], 20);
					elseif(strpos($info["message"], "Processing Complete") !== false)
						$fields["PROCESSING_RESULT"] = "Complete";
				}

				if(!empty($fields))
					$result = array_merge($result, $fields);
			}
		}

		return $result;
	}
}