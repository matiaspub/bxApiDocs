<?php
namespace Bitrix\Catalog\Ebay;

use Bitrix\Main\SystemException,
	Bitrix\Currency;

class ExportOffer implements \Iterator
{
	/*Constructor input vars*/
	protected $iBlockId;
	protected $xmlData;

	/*Counted by constructor vars*/
	protected $bAllSections;
	protected $arSections = array();
	protected $arIblock;
	protected $intMaxSectionID = 0;
	protected $arSectionIDs = array();

	/*Iterator vars*/
	protected $currentKey = 0;
	protected $currentRecord = array();

	/*other vars*/
	protected $cnt = 0;
	/** @var null|\CIBlockResult $dbItems */
	protected $dbItems = null;
	protected $catalogType;

	public function __construct($catalogType, $params)
	{
		if(!isset($params["IBLOCK_ID"]) || intval($params["IBLOCK_ID"]) <= 0)
			throw new SystemException("Incorrect iBlock ID  (".__CLASS__."::".__METHOD__.")");

		$this->catalogType = $catalogType;

		$this->iBlockId = $params["IBLOCK_ID"];
		$this->xmlData = $params["XML_DATA"];

		$this->arIblock = $this->getIblockProps($params["SETUP_SERVER_NAME"]);
		$this->arSections = $this->getSections($params["PRODUCT_GROUPS"]);

		$this->bAllSections = in_array(0, $this->arSections) ? true : false;
		$availGroups = $this->getAvailGroups();
		$this->intMaxSectionID = $this->getMaxSectionId($availGroups);
		$this->arSectionIDs = $this->getSectionIDs($availGroups);
	}

	/*Iterator methods*/
	public function current()
	{
		return $this->currentRecord;
	}

	public function key()
	{
		return $this->currentKey;
	}

	public function next()
	{
		$this->currentKey++;
		$this->currentRecord = $this->nextItem();
		$this->checkDiscountCache();
	}

	public function rewind()
	{
		$this->currentKey = 0;
		$this->dbItems = $this->createDbResObject();
		$this->currentRecord = $this->nextItem();
		$this->cnt = 100;
		$this->checkDiscountCache();
	}

	public function valid ()
	{
		return is_array($this->currentRecord);
	}

	protected function createDbResObject()
	{
		$arSelect = array("ID", "LID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "NAME", "PREVIEW_PICTURE", "PREVIEW_TEXT",
			"PREVIEW_TEXT_TYPE", "DETAIL_PICTURE", "LANG_DIR", "DETAIL_PAGE_URL", "DETAIL_TEXT");

		$filter = array("IBLOCK_ID" => $this->iBlockId);

		if (!$this->bAllSections && !empty($this->arSectionIDs))
		{
			$filter["INCLUDE_SUBSECTIONS"] = "Y";
			$filter["SECTION_ID"] = $this->arSectionIDs;
		}

		$filter["ACTIVE"] = "Y";
		$filter["ACTIVE_DATE"] = "Y";

		return \CIBlockElement::GetList(array(), $filter, false, false, $arSelect);
	}

	protected function getMaxSectionId(array $arAvailGroups)
	{
		$result = 0;

		foreach($arAvailGroups as $group)
			if ($result < $group["ID"])
				$result = $group["ID"];

		$result += 100000000;

		return $result;
	}

	protected function getAvailGroups()
	{
		$arAvailGroups = array();

		if (!$this->bAllSections)
		{
			for ($i = 0, $intSectionsCount = count($this->arSections); $i < $intSectionsCount; $i++)
			{
				$db_res = \CIBlockSection::GetNavChain($this->iBlockId, $this->arSections[$i]);
				$curLEFT_MARGIN = 0;
				$curRIGHT_MARGIN = 0;
				while ($ar_res = $db_res->Fetch())
				{
					$curLEFT_MARGIN = (int)$ar_res["LEFT_MARGIN"];
					$curRIGHT_MARGIN = (int)$ar_res["RIGHT_MARGIN"];
					$arAvailGroups[$ar_res["ID"]] = array(
						"ID" => (int)$ar_res["ID"],
						"IBLOCK_SECTION_ID" => (int)$ar_res["IBLOCK_SECTION_ID"],
						"NAME" => $ar_res["NAME"]
					);
				}

				$filter = array("IBLOCK_ID"=>$this->iBlockId, ">LEFT_MARGIN"=>$curLEFT_MARGIN, "<RIGHT_MARGIN"=>$curRIGHT_MARGIN, "ACTIVE"=>"Y", "IBLOCK_ACTIVE"=>"Y", "GLOBAL_ACTIVE"=>"Y");
				$db_res = \CIBlockSection::GetList(array("left_margin"=>"asc"), $filter);
				while ($ar_res = $db_res->Fetch())
				{
					$arAvailGroups[$ar_res["ID"]] = array(
						"ID" => (int)$ar_res["ID"],
						"IBLOCK_SECTION_ID" => (int)$ar_res["IBLOCK_SECTION_ID"],
						"NAME" => $ar_res["NAME"]
					);
				}
			}
		}
		else
		{
			$filter = array("IBLOCK_ID"=>$this->iBlockId, "ACTIVE"=>"Y", "IBLOCK_ACTIVE"=>"Y", "GLOBAL_ACTIVE"=>"Y");
			$db_res = \CIBlockSection::GetList(array("left_margin"=>"asc"), $filter);
			while ($ar_res = $db_res->Fetch())
			{
				$arAvailGroups[$ar_res["ID"]] = array(
					"ID" => (int)$ar_res["ID"],
					"IBLOCK_SECTION_ID" => (int)$ar_res["IBLOCK_SECTION_ID"],
					"NAME" => $ar_res["NAME"]
				);
			}
		}

		return $arAvailGroups;
	}

	protected function getSections($selectedGroups)
	{
		$arSections = array();

		if (is_array($selectedGroups))
		{
			foreach ($selectedGroups as $value)
			{
				$arSections[] = (int)$value;

				if ($value == 0)
					break;
			}
		}

		return $arSections;
	}

	protected function getIblockProps($serverName)
	{
		$dbIblock = \CIBlock::GetByID($this->iBlockId);
		$arIblock = $dbIblock->Fetch();

		if($arIblock)
		{
			if (strlen($serverName) <= 0)
			{
				if (strlen($arIblock['SERVER_NAME']) <= 0)
				{
					$b = "sort";
					$o = "asc";
					$rsSite = \CSite::GetList($b, $o, array("LID" => $arIblock["LID"]));
					if($arSite = $rsSite->Fetch())
						$arIblock["SERVER_NAME"] = $arSite["SERVER_NAME"];
					if(strlen($arIblock["SERVER_NAME"])<=0 && defined("SITE_SERVER_NAME"))
						$arIblock["SERVER_NAME"] = SITE_SERVER_NAME;
					if(strlen($arIblock["SERVER_NAME"])<=0)
						$arIblock["SERVER_NAME"] = \COption::GetOptionString("main", "server_name", "");
				}
			}
			else
			{
				$arIblock['SERVER_NAME'] = $serverName;
			}

			$arIblock['PROPERTY'] = array();

			$rsProps = \CIBlockProperty::GetList(
				array('SORT' => 'ASC', 'NAME' => 'ASC'),
				array('IBLOCK_ID' => $this->iBlockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N')
			);
			while ($arProp = $rsProps->Fetch())
			{
				$arProp['ID'] = (int)$arProp['ID'];
				$arProp['USER_TYPE'] = (string)$arProp['USER_TYPE'];
				$arProp['CODE'] = (string)$arProp['CODE'];
				$arIblock['PROPERTY'][$arProp['ID']] = $arProp;
			}
		}

		return $arIblock;
	}

	protected function getQuantity($productId)
	{
		$result = 0;

		$rsProducts = \CCatalogProduct::GetList(
			array(),
			array('ID' => $productId),
			false,
			false,
			array('ID', 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO')
		);

		if ($arProduct = $rsProducts->Fetch())
		{
			$arProduct['QUANTITY'] = doubleval($arProduct['QUANTITY']);

			if (0 >= $arProduct['QUANTITY'] && ('Y' != $arProduct['QUANTITY_TRACE'] || 'N' != $arProduct['CAN_BUY_ZERO']))
				$result = 1;
			else
				$result = $arProduct['QUANTITY'];
		}

		return $result;
	}

	/**
	 * Return ruble currency code.
	 *
	 * @return string
	 */
	public static function getRub()
	{
		$currencyList = Currency\CurrencyManager::getCurrencyList();
		return (isset($currencyList['RUR']) ? 'RUR' : 'RUB');
	}

	protected function getPrices($productId, $siteId)
	{
		$minPrice = 0;
		$minPriceRUR = 0;
		$minPriceGroup = 0;
		$minPriceCurrency = "";

		$baseCurrency = Currency\CurrencyManager::getBaseCurrency();
		$RUR = $this->getRub();

		if ($this->xmlData['PRICE'] > 0)
		{
			$rsPrices = \CPrice::GetListEx(array(),array(
					'PRODUCT_ID' => $productId,
					'CATALOG_GROUP_ID' => $this->xmlData['PRICE'],
					'CAN_BUY' => 'Y',
					'GROUP_GROUP_ID' => array(2),
					'+<=QUANTITY_FROM' => 1,
					'+>=QUANTITY_TO' => 1,
				)
			);

			if ($arPrice = $rsPrices->Fetch())
			{
				if ($arOptimalPrice = \CCatalogProduct::GetOptimalPrice(
					$productId,
					1,
					array(2), // anonymous
					'N',
					array($arPrice),
					$siteId
				))
				{
					$minPrice = $arOptimalPrice['RESULT_PRICE']['DISCOUNT_PRICE'];
					$minPriceCurrency = $baseCurrency;
					$minPriceRUR = \CCurrencyRates::ConvertCurrency($minPrice, $baseCurrency, $RUR);
					$minPriceGroup = $arOptimalPrice['PRICE']['CATALOG_GROUP_ID'];
				}
			}
		}
		else
		{
			if ($arPrice = \CCatalogProduct::GetOptimalPrice(
				$productId,
				1,
				array(2), // anonymous
				'N',
				array(),
				$siteId
			))
			{
				$minPrice = $arPrice['RESULT_PRICE']['DISCOUNT_PRICE'];
				$minPriceCurrency = $baseCurrency;
				$minPriceRUR = \CCurrencyRates::ConvertCurrency($minPrice, $baseCurrency, $RUR);
				$minPriceGroup = $arPrice['PRICE']['CATALOG_GROUP_ID'];
			}
		}

		$result = array(
			"MIN" => $minPrice,
			"MIN_RUB" => $minPriceRUR,
			"MIN_GROUP" => $minPriceGroup,
			"MIN_CURRENCY" => $minPriceCurrency
		);

		return $result;
	}

	protected function getDetailPageUrl($detailPageUrl)
	{
		if (strlen($detailPageUrl) <= 0)
			$detailPageUrl = '/';
		else
			$detailPageUrl = str_replace(' ', '%20', $detailPageUrl);

		$result = "http://".$this->arIblock['SERVER_NAME'].htmlspecialcharsbx($detailPageUrl);

		return $result;
	}

	protected function getPictureUrl($pictNo)
	{
		$strFile = "";

		if ($file = \CFile::GetFileArray($pictNo))
		{
			if(substr($file["SRC"], 0, 1) == "/")
				$strFile = "http://".$this->arIblock['SERVER_NAME'].implode("/", array_map("rawurlencode", explode("/", $file["SRC"])));
			elseif(preg_match("/^(http|https):\\/\\/(.*?)\\/(.*)\$/", $file["SRC"], $match))
				$strFile = "http://".$match[2].'/'.implode("/", array_map("rawurlencode", explode("/", $match[3])));
			else
				$strFile = $file["SRC"];
		}

		return $strFile;
	}

	protected function getParams($product, $arIblock)
	{
		if (isset($arIblock['PROPERTY']))
			$arProperties = $arIblock['PROPERTY'];
		else
			$arProperties = array();

		$arUserTypeFormat = array();

		foreach($arProperties as $key => $arProperty)
		{
			$arUserTypeFormat[$arProperty["ID"]] = false;
			if (strlen($arProperty["USER_TYPE"]))
			{
				$arUserType = \CIBlockProperty::GetUserType($arProperty["USER_TYPE"]);
				if (array_key_exists("GetPublicViewHTML", $arUserType))
				{
					$arUserTypeFormat[$arProperty["ID"]] = $arUserType["GetPublicViewHTML"];
					$arProperties[$key]['PROPERTY_TYPE'] = 'USER_TYPE';
				}
			}
		}

		$result = array();


		if (is_array($this->xmlData) && is_array($this->xmlData['XML_DATA']) && is_array($this->xmlData['XML_DATA']['PARAMS']))
		{
			foreach ($this->xmlData['XML_DATA']['PARAMS'] as $key => $propId)
			{
				if ($propId)
					$result[] = $this->getValue($product, 'PARAM_'.$key, $propId, $arProperties, $arUserTypeFormat);
			}
		}

		return $result;
	}

	protected function getValue($arOffer, $param, $PROPERTY, $arProperties, $arUserTypeFormat)
	{
		$result = array();

		$bParam = (strncmp($param, 'PARAM_', 6) == 0);

		if (isset($arProperties[$PROPERTY]) && !empty($arProperties[$PROPERTY]))
		{
			$PROPERTY_CODE = $arProperties[$PROPERTY]['CODE'];
			$arProperty = (
			isset($arOffer['PROPERTIES'][$PROPERTY_CODE])
				? $arOffer['PROPERTIES'][$PROPERTY_CODE]
				: $arOffer['PROPERTIES'][$PROPERTY]
			);

			$value = array();

			if(!is_array($arProperty["VALUE"]))
				$arProperty["VALUE"] = array($arProperty["VALUE"]);

			switch ($arProperties[$PROPERTY]['PROPERTY_TYPE'])
			{
				case 'USER_TYPE':
					foreach($arProperty["VALUE"] as $oneValue)
					{
						$value[] = call_user_func_array($arUserTypeFormat[$PROPERTY],
							array(
								$arProperty,
								array("VALUE" => $oneValue),
								array('MODE' => 'SIMPLE_TEXT'),
							));
					}
					break;

				case 'E':
					$arCheckValue = array();

					foreach ($arProperty['VALUE'] as &$intValue)
					{
						$intValue = (int)$intValue;
						if (0 < $intValue)
							$arCheckValue[] = $intValue;
					}

					if (isset($intValue))
						unset($intValue);

					if (!empty($arCheckValue))
					{
						$dbRes = \CIBlockElement::GetList(array(), array('IBLOCK_ID' => $arProperties[$PROPERTY]['LINK_IBLOCK_ID'], 'ID' => $arCheckValue), false, false, array('NAME'));
						while ($arRes = $dbRes->Fetch())
						{
							$value[]= $arRes['NAME'];
						}
					}
					break;

				case 'G':
					$arCheckValue = array();

					foreach ($arProperty['VALUE'] as &$intValue)
					{
						$intValue = (int)$intValue;
						if (0 < $intValue)
							$arCheckValue[] = $intValue;
					}

					if (isset($intValue))
						unset($intValue);

					if (!empty($arCheckValue))
					{
						$dbRes = \CIBlockSection::GetList(array(), array('IBLOCK_ID' => $arProperty['LINK_IBLOCK_ID'], 'ID' => $arCheckValue), false, array('NAME'));
						while ($arRes = $dbRes->Fetch())
						{
							$value[] = $arRes['NAME'];
						}
					}
					break;

				case 'L':
					$value .= $arProperty['VALUE'];
					break;

				case 'F':
					foreach ($arProperty['VALUE'] as &$intValue)
					{
						$intValue = (int)$intValue;
						if ($intValue > 0)
						{
							if ($ar_file = \CFile::GetFileArray($intValue))
							{
								if(substr($ar_file["SRC"], 0, 1) == "/")
									$strFile = "http://".$this->arIblock["SERVER_NAME"].implode("/", array_map("rawurlencode", explode("/", $ar_file["SRC"])));
								elseif(preg_match("/^(http|https):\\/\\/(.*?)\\/(.*)\$/", $ar_file["SRC"], $match))
									$strFile = "http://".$match[2].'/'.implode("/", array_map("rawurlencode", explode("/", $match[3])));
								else
									$strFile = $ar_file["SRC"];
								$value[] = $strFile;
							}
						}
					}

					if (isset($intValue))
						unset($intValue);
					break;

				default:
					$value = $arProperty['VALUE'];
			}

			if(is_array($value) && count($value) == 1)
				$value = implode("",$value);

			if ($bParam)
			{
				$result[$param] = array(
					"NAME" => $arProperties[$PROPERTY]['NAME'],
					"VALUES" => $value
				);
			}
			else
			{
				$result[$param] = $value;
			}
		}

		return $result;
	}

	protected function getCategories($productId)
	{
		$boolCurrentSections = false;
		$result = array();

		$dbElementGroups = \CIBlockElement::GetElementGroups($productId, false, array('ID', 'ADDITIONAL_PROPERTY_ID'));

		while ($arElementGroup = $dbElementGroups->Fetch())
		{
			if (0 < (int)$arElementGroup['ADDITIONAL_PROPERTY_ID'])
				continue;

			$boolCurrentSections = true;

			if (in_array((int)$arElementGroup["ID"], $this->arSectionIDs))
				$result[] = $arElementGroup["ID"];
		}

		if (!$boolCurrentSections)
			$result[] = $this->intMaxSectionID;

		return $result;
	}

	protected function getSectionIDs(array $availGroups)
	{
		if (!empty($availGroups))
			$arSectionIDs = array_keys($availGroups);
		else
			$arSectionIDs = array();

		return $arSectionIDs;
	}

	protected function checkDiscountCache()
	{
		$this->cnt++;

		if (100 <= $this->cnt)
		{
			$this->cnt = 0;
			\CCatalogDiscount::ClearDiscountCache(array(
				'PRODUCT' => true,
				'SECTIONS' => true,
				'PROPERTIES' => true
			));
		}
	}

	protected function nextItem()
	{
		if(!$obElement = $this->dbItems->GetNextElement())
			return false;

		$arItem = $obElement->GetFields();
		$arItem["QUANTITY"] = $this->getQuantity($arItem["ID"]);
		$arItem["PRICES"] = $this->getPrices($arItem["ID"], $this->arIblock['LID']);
		$arItem["CATEGORIES"] = $this->getCategories($arItem["ID"]);
		$arItem["DETAIL_PICTURE_URL"] = $this->getPictureUrl((int)$arItem["DETAIL_PICTURE"]);
		$arItem["PREVIEW_PICTURE_URL"] = $this->getPictureUrl((int)$arItem["PREVIEW_PICTURE"]);
		$arItem["PARAMS"] = $this->getParams($arItem, $this->arIblock);
		$arItem["DETAIL_PAGE_URL"] = $this->getDetailPageUrl($arItem["~DETAIL_PAGE_URL"]);

		return $arItem;
	}
}