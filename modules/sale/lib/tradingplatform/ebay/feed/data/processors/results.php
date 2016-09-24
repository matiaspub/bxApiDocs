<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Processors;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\TradingPlatform\Logger;
use Bitrix\Sale\TradingPlatform\Ebay\Ebay;

class Results extends DataProcessor
{
	protected $siteId;

	public function __construct($params)
	{
		if(!isset($params["SITE_ID"]) || strlen($params["SITE_ID"]) <= 0)
			throw new ArgumentNullException("SITE_ID");

		$this->siteId = $params["SITE_ID"];
	}

	public function process($data)
	{
		if(!isset($data["RESULT_ID"]))
			throw new ArgumentNullException("data[\"RESULT_ID\"]");

		if(isset($data["XML"]))
		{
			$fields["RESULTS"] = $data["XML"];
			\Bitrix\Sale\TradingPlatform\Ebay\Feed\ResultsTable::update($data["RESULT_ID"], $fields);
		}

		$message = "";

		if(isset($data["ARRAY"]["RequestDetails"]["Errors"]["Error"]))
			$message .= $this->getErrorsString($data["ARRAY"]["RequestDetails"]["Errors"]["Error"]);

		if(isset($data["ARRAY"]["RequestDetails"]["Warnings"]["Warning"]))
			$message .= $this->getWarningsString($data["ARRAY"]["RequestDetails"]["Warnings"]["Warning"]);

		if(isset($data["ARRAY"]["ProductResult"]))
			$message .= $this->getProductsString($data["ARRAY"]["ProductResult"]);

		if(strlen($message) > 0)
		{
			$message = "RequestId: ".$data["ARRAY"]["RequestDetails"]["RequestID"]."\n".
			"StartTime: ".$data["ARRAY"]["RequestDetails"]["StartTime"]."\n".
			"EndTime: ".$data["ARRAY"]["RequestDetails"]["EndTime"]."\n\n".
			$message;

			Ebay::log(
					Logger::LOG_LEVEL_ERROR,
					"EBAY_FEED_RESULTS_ERROR",
					$data["ARRAY"]["RequestDetails"]["RequestID"],
					$message,
					$this->siteId);
		}

		return true;
	}

	protected function getProductsString($products)
	{
		if(!is_array($products) || empty($products))
			return "";

		reset($products);

		if(key($products) !== 0)
			$products = array( 0 => $products);

		$result = "";

		foreach($products as $product)
			$result .= $this->getProductInfo($product);

		return $result;
	}

	protected function getProductInfo($product)
	{
		if(!is_array($product) || empty($product))
			return "";

		$result = "";

		if(isset($product["Errors"]["Error"]) || isset($product["Warnings"]["Warning"]))
		{
			if(isset($product["ProductID"]))
				$result .= "\nProductID: ".$product["ProductID"]."\n";

			if(isset($product["Result"]))
				$result .= "Result: ".$product["Result"]."\n";

			if(isset($product["Action"]))
				$result .= "Action: ".$product["Action"]."\n";
		}

		if(isset($product["Errors"]["Error"]))
			$result .= $this->getErrorsString($product["Errors"]["Error"]);

		if(isset($product["Warnings"]["Warning"]))
			$result .= $this->getWarningsString($product["Warnings"]["Warning"]);

		return $result;
	}

	protected function getErrorsString($errors)
	{
		if(!is_array($errors) || empty($errors))
			return "";

		reset($errors);

		if(key($errors) !== 0)
			$errors = array( 0 => $errors);

		$result = "";

		foreach($errors as $error)
			$result .= "Error: ".$error["Message"]." (error code:  ".$error["Code"].").\n";

		return $result;
	}

	protected function getWarningsString($warnings)
	{
		if(!is_array($warnings) || empty($warnings))
			return "";

		reset($warnings);

		if(key($warnings) !== 0)
			$warnings = array( 0 => $warnings);

		$result = "";

		foreach($warnings as $warning)
			$result .= "Warning: ".$warning["Message"]." (warning code:  ".$warning["Code"].").\n";

		return $result;
	}
}