<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Api;

use Bitrix\Main\Text\Encoding;
use Bitrix\Sale\TradingPlatform\Xml2Array;
use Bitrix\Sale\TradingPlatform\Ebay\CategoryTable;
use Bitrix\Sale\TradingPlatform\Ebay\CategoryVariationTable;

class Categories extends Entity
{
	protected  function getItems(array $params = array())
	{
		$data = '<?xml version="1.0" encoding="utf-8"?>
			<GetCategoriesRequest xmlns="urn:ebay:apis:eBLBaseComponents">
			<RequesterCredentials>
			<eBayAuthToken>'.$this->authToken.'</eBayAuthToken>
			</RequesterCredentials>
			<CategorySiteID>'.$this->ebaySiteId.'</CategorySiteID>
			<WarningLevel>'.$this->warningLevel.'</WarningLevel>'."\n";

		$data .= $this->array2Tags($params);
		$data .= '</GetCategoriesRequest>?';

		$categoriesXml = $this->apiCaller->sendRequest("GetCategories", $data);

		if(strtolower(SITE_CHARSET) != 'utf-8')
			$categoriesXml = Encoding::convertEncoding($categoriesXml, 'UTF-8', SITE_CHARSET);

		$result = Xml2Array::convert($categoriesXml);
		return $result;
	}

	protected function getTopItems()
	{
		return $this->getItems(array(
			"LevelLimit" => 1,
			"DetailLevel" => "ReturnAll"
		));
	}

	public function refreshTableData()
	{
		$refreshedCount = 0;
		$catInfo = $this->getItems(array("DetailLevel" => "ReturnAll"));
		$existCategoriesList = array();

		$res = CategoryTable::getList(array(
			"select" => array("ID", "CATEGORY_ID")
		));

		while($category = $res->fetch())
			$existCategoriesList[$category["CATEGORY_ID"]] = $category["ID"];

		if(isset($catInfo["CategoryArray"]["Category"]))
		{
			$categories = Xml2Array::normalize($catInfo["CategoryArray"]["Category"]);

			foreach($categories as $category)
			{
				$fields = array(
					"CATEGORY_ID" => $category["CategoryID"],
					"LEVEL" => $category["CategoryLevel"],
					"NAME" => $category["CategoryName"],
					"PARENT_ID" => $category["CategoryParentID"]
				);

				if(array_key_exists($category["CategoryID"], $existCategoriesList))
					$result = CategoryTable::update($existCategoriesList[$category["CategoryID"]], $fields);
				else
					$result = CategoryTable::add($fields);

				if($result > 0)
					$refreshedCount++;
			}
		}

		return $refreshedCount;
	}

	public function getItemSpecifics(array $params)
	{
		$data = '<?xml version="1.0" encoding="utf-8"?>
			<GetCategorySpecificsRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
		$data.= $this->array2Tags($params);
		$data.=	'<RequesterCredentials>
					<eBayAuthToken>'.$this->authToken.'</eBayAuthToken>
				</RequesterCredentials>
				<WarningLevel>'.$this->warningLevel.'</WarningLevel>
			</GetCategorySpecificsRequest>?';

		return $this->apiCaller->sendRequest("GetCategorySpecifics", $data);
	}

	protected function getMappedCategories()
	{
		$ebay = \Bitrix\Sale\TradingPlatform\Ebay\Ebay::getInstance();
		$settings = $ebay->getSettings();
		$iblocksIds = array();
		$result = array();

		foreach($settings[$this->siteId]["IBLOCK_ID"] as $iblockId)
			$iblocksIds[] = \Bitrix\Sale\TradingPlatform\Ebay\MapHelper::getCategoryEntityId($iblockId);

		$catMapRes = \Bitrix\Sale\TradingPlatform\MapTable::getList(array(
			"filter" => array(
				"ENTITY_ID" => $iblocksIds
			)
		));

		while($arMapRes = $catMapRes->fetch())
			$result = $arMapRes["VALUE_EXTERNAL"];

		return $result;
	}

	public function refreshVariationsTableData(array $ebayCategoriesIds = array())
	{
		$refreshedCount = 0;

		$specXml = $this->getItemSpecifics(array(
			"CategoryID" => empty($ebayCategoriesIds) ? $this->getMappedCategories() : $ebayCategoriesIds
		));

		$specifics = new \SimpleXMLElement($specXml, LIBXML_NOCDATA);

		foreach($specifics->Recommendations as $categoryRecommendation)
		{
			foreach($categoryRecommendation->NameRecommendation as $nameRecommendation)
			{
				$fields = array(
					"CATEGORY_ID" => $categoryRecommendation->CategoryID->__toString(),
					"NAME" => $nameRecommendation->Name->__toString()
				);

				if(isset($nameRecommendation->ValidationRules))
				{

					if($nameRecommendation->ValidationRules->MinValues)
						$fields["MIN_VALUES"] = $nameRecommendation->ValidationRules->MinValues->__toString();
					else
						$fields["MIN_VALUES"] = 0;

					if($nameRecommendation->ValidationRules->MinValues)
						$fields["MAX_VALUES"] = $nameRecommendation->ValidationRules->MaxValues->__toString();
					else
						$fields["MAX_VALUES"] = 0;

					$fields["REQUIRED"] = intval($fields["MIN_VALUES"]) > 0 ? "Y" : "N";
					$fields["SELECTION_MODE"] = $nameRecommendation->ValidationRules->SelectionMode->__toString();
					$fields["ALLOWED_AS_VARIATION"] = $nameRecommendation->ValidationRules->VariationSpecifics->__toString() == "Enabled" ? "Y" : "N";
					$fields["HELP_URL"] = $nameRecommendation->ValidationRules->HelpURL->__toString();
				}

				if(isset($nameRecommendation->ValueRecommendation))
				{
					$values = array();

					foreach($nameRecommendation->ValueRecommendation as $valueRecommendation)
						$values[] = $valueRecommendation->Value->__toString();

					$fields["VALUE"] = $values;
				}

				if(strtolower(SITE_CHARSET) != 'utf-8')
					$fields = \Bitrix\Main\Text\Encoding::convertEncodingArray($fields, 'UTF-8', SITE_CHARSET);

				$res = CategoryVariationTable::getList(array(
					"filter" => array(
						"CATEGORY_ID" => $fields["CATEGORY_ID"],
						"NAME" =>  $fields["NAME"]
					),
					"select" => array("ID")
				));

				if($savedVar = $res->fetch())
					$result = CategoryVariationTable::update($savedVar["ID"], $fields);
				else
					$result = CategoryVariationTable::add($fields);

				if($result > 0)
					$refreshedCount++;
			}
		}

		return $refreshedCount;
	}
}