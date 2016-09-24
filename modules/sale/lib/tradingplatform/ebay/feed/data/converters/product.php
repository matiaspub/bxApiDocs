<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Converters;

use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\TradingPlatform\Ebay\CategoryVariationTable;

class Product extends DataConverter
{
	protected $ebayCategories;
	protected $attributesList;
	protected $attributesItem;
	protected $variationsVector;
	protected $bitrixCategories;
	protected $siteId;

	public function __construct($params)
	{
		if(!isset($params["SITE_ID"]) || strlen($params["SITE_ID"]) <= 0)
			throw new ArgumentNullException("SITE_ID");

		$this->siteId = $params["SITE_ID"];
	}

	public function convert($data)
	{
		$this->ebayCategories = $this->bitrixToEbayCategories($data["IBLOCK_ID"], $data["CATEGORIES"]);
		$this->attributesList = $this->getAttributesList($data["IBLOCK_ID"], $this->ebayCategories);
		$this->attributesItem = $this->getAttributesItem($this->attributesList, $data);
		$this->bitrixCategories = $data["CATEGORIES"];

		if(isset($data["OFFERS"]) && is_array($data["OFFERS"]) && !empty($data["OFFERS"]))
		{
			$result = $this->getItemDataOffers($data);

			foreach($data["OFFERS"] as $offer)
				$result .= $this->getItemDataOffersOffer($offer, $data["IBLOCK_ID"]."_".$data["ID"]);
		}
		else
		{
			$result = $this->getItemData($data);
		}

		return $result;
	}

	protected function getItemDataOffersOffer($data, $parentSKU)
	{
		$attributies  = $this->getAttributesItem($this->variationsVector, $data);

		$result = "\t<Listing>\n";
		$result .= "\t\t<Product>\n";
		$result .= "\t\t\t<SKU VariantOf=\"".$parentSKU."\">".$parentSKU."_".$data["ID"]."</SKU>\n";
		$result .= "\t\t\t<ProductInformation>\n";

		if(!empty($attributies))
		{
			$result .= "\t\t\t<Attributes>\n";

			foreach($attributies as $attrName => $attrValue)
				$result .= "\t\t\t\t<Attribute Name=\"".$attrName."\">".htmlspecialcharsbx($attrValue)."</Attribute>\n";

			$result .= "\t\t\t</Attributes>\n";
		}

		if(strlen($data["DETAIL_PICTURE_URL"]) > 0 || strlen($data["PREVIEW_PICTURE_URL"]) > 0)
		{
			$result .= "\t\t\t<PictureUrls>\n";
			$result .= "\t\t\t\t<PictureUrl>".(strlen($data["DETAIL_PICTURE_URL"]) > 0 ? $data["DETAIL_PICTURE_URL"] : $data["PREVIEW_PICTURE_URL"] )."</PictureUrl>\n";
			$result .= "\t\t\t</PictureUrls>\n";
		}

		$result .= "\t\t\t</ProductInformation>\n";
		$result .= "\t\t</Product>\n";
		$result .= "\t</Listing>\n";
		return $result;
	}

	protected function getItemDataOffers($data)
	{
		$this->variationsVector = array_diff_key($this->attributesList, $this->attributesItem);

		$result = "\t<Listing>\n";
		$result .= "\t\t<ProductVariationGroup>\n";
		$result .= "\t\t\t<Country>RU</Country>\n";
		$result .= "\t\t\t<GroupId>".$data["IBLOCK_ID"]."_".$data["ID"]."</GroupId>\n";

		if(is_array($this->variationsVector) && !empty($this->variationsVector))
		{
			$result .= "\t\t\t<VariationVector>\n";

			foreach($this->variationsVector as $ebayAttributeName => $bitrixPropId)
				$result .= "\t\t\t\t<Name>".$ebayAttributeName."</Name>\n";

			$result .= "\t\t\t</VariationVector>\n";
		}

		$result .= "\t\t\t<Categories>\n";

		foreach($this->ebayCategories as $category)
			$result .= "\t\t\t\t<Category Type=\"eBayLeafCategory\">".$category."</Category>\n";

		$result .= "\t\t\t</Categories>\n";
		$result .= "\t\t\t<SharedProductInformation>\n";
		$result .= "\t\t\t<Title>".$data["NAME"]."</Title>\n";
		$result .= "\t\t\t<Description>\n";
		$result .= "\t\t\t\t<ProductDescription>\n";
		$result .= "<![CDATA[\n";
		$result .= strlen($data["~PREVIEW_TEXT"]) > 0 ? $data["~PREVIEW_TEXT"] : $data["~DETAIL_TEXT"]."\n";
		$result .= "]]>\n";
		$result .= "</ProductDescription>\n";
		$result .= "\t\t\t</Description>\n";

		if(!empty($this->attributesItem))
		{
			$result .= "\t\t\t<Attributes>\n";

			foreach($this->attributesItem as $attrName => $attrValue)
				$result .= "\t\t\t\t<Attribute Name=\"".$attrName."\">".htmlspecialcharsbx($attrValue)."</Attribute>\n";

			$result .= "\t\t\t</Attributes>\n";
		}

		$result .= "\t\t\t<PictureUrls>\n";
		$result .= "\t\t\t\t<PictureUrl>".(strlen($data["DETAIL_PICTURE_URL"]) > 0 ? $data["DETAIL_PICTURE_URL"] : $data["PREVIEW_PICTURE_URL"] )."</PictureUrl>\n";
		$result .= "\t\t\t</PictureUrls>\n";
		$result .= "<ConditionInfo>
						<Condition>NEW</Condition>
				</ConditionInfo>";
		$result .= "\t\t\t</SharedProductInformation>\n";
		reset($this->ebayCategories);
		$result .= $this->getListingDetails($data["IBLOCK_ID"], current($this->ebayCategories));
		$result .= "\t\t</ProductVariationGroup>\n";
		$result .= "\t</Listing>\n";
		return $result;
	}

	protected function getEbayCategoryAttrName($ebeyAttributeId)
	{
		$res = CategoryVariationTable::getById($ebeyAttributeId);

		if($category = $res->fetch())
			$result = $category["NAME"];
		else
			$result = "";

		return $result;
	}

	protected function getAttributesItem($attributesList, $data)
	{
		if(!is_array($data["PROPERTIES"]))
			return array();

		$result = array();

		foreach($attributesList as $ebayCategoryAttrId => $bitrixAttr)
		{
			$value = $this->getBitrixItemPropValue($bitrixAttr, $data["PROPERTIES"]);
			$name = $this->getEbayCategoryAttrName($ebayCategoryAttrId);

			if($value !== false)
				$result[$name] = $value;
		}

		return $result;
	}

	protected function getItemData($data)
	{
		$result = "\t<Listing>\n";
		$result .= "\t\t<Product>\n";
		$result .= "\t\t\t<SKU>".$data["IBLOCK_ID"]."_".$data["ID"]."</SKU>\n";
		$result .= "\t\t\t<ProductInformation>\n";
		$result .= "\t\t\t\t<Country>RU</Country>\n";
		$result .= "\t\t\t\t<Title>".$data["NAME"]."</Title>\n";
		$result .= "\t\t\t\t<Description>\n";
		$result .= "\t\t\t\t\t<ProductDescription>\n";
		$result .= "<![CDATA[\n";
		$result .= strlen($data["~PREVIEW_TEXT"]) > 0 ? $data["~PREVIEW_TEXT"] : $data["~DETAIL_TEXT"]."\n";
		$result .= "]]>\n";
		$result .= "</ProductDescription>\n";
		$result .= "\t\t\t\t</Description>\n";
		$result .= "<ConditionInfo>
						<Condition>NEW</Condition>
				</ConditionInfo>";

		if(!empty($this->attributesItem))
		{
			$result .= "\t\t\t\t<Attributes>\n";

			foreach($this->attributesItem as $attrName => $attrValue)
				$result .= "\t\t\t\t\t<Attribute Name=\"".$attrName."\">".$attrValue."</Attribute>\n";

			$result .= "\t\t\t\t</Attributes>\n";
		}

		$result .= "\t\t\t\t<PictureUrls>\n";
		$result .= "\t\t\t\t\t<PictureUrl>".(strlen($data["DETAIL_PICTURE_URL"]) > 0 ? $data["DETAIL_PICTURE_URL"] : $data["PREVIEW_PICTURE_URL"] )."</PictureUrl>\n";
		$result .= "\t\t\t\t</PictureUrls>\n";
		$result .= "\t\t\t\t<Categories>\n";

		foreach($this->ebayCategories as $category)
			$result .= "\t\t\t\t\t<Category Type=\"eBayLeafCategory\">".$category."</Category>\n";

		$result .= "\t\t\t\t</Categories>\n";
		$result .= "\t\t\t</ProductInformation>\n";
		$result .= "\t\t</Product>\n";
		reset($this->ebayCategories);
		$result .= $this->getListingDetails($data["IBLOCK_ID"], current($this->ebayCategories));
		$result .= "\t</Listing>\n";
		return $result;
	}

	protected function getListingDetails($iBlockId, $ebayCategory)
	{
		$policy = $this->getPolicyForCategory($iBlockId, $ebayCategory);
		$result = "\t\t<ListingDetails>\n";

		if(!empty($policy["RETURN"]))
			$result .= "\t\t\t<ReturnPolicy>".$policy["RETURN"]."</ReturnPolicy>\n";

		if(!empty($policy["SHIPPING"]))
			$result .= "\t\t\t<ShippingPolicy>".$policy["SHIPPING"]."</ShippingPolicy>\n";

		if(!empty($policy["PAYMENT"]))
			$result .= "\t\t\t<PaymentPolicy>".$policy["PAYMENT"]."</PaymentPolicy>\n";

		$result .= "\t\t</ListingDetails>\n";
		return $result;
	}

	protected function getBitrixItemPropValue($propId, array $props)
	{
		$result = false;

		foreach($props as $property)
		{
			if($property["ID"] == $propId)
			{
				$result = $property["~VALUE"];
				break;
			}
		}

		return $result;
	}

	protected function getAttributesList($iblockId, array $ebayCategories)
	{
		$result = array();

		foreach($ebayCategories as $category)
		{
			$mapEntityId = \Bitrix\Sale\TradingPlatform\Ebay\MapHelper::getCategoryVariationEntityId($iblockId, $category);

			$catMapVarRes = \Bitrix\Sale\TradingPlatform\MapTable::getList(array(
				"filter" => array(
					"ENTITY_ID" => $mapEntityId
				)
			));

			while($arMapRes = $catMapVarRes->fetch())
				$result[$arMapRes["VALUE_EXTERNAL"]] = $arMapRes["VALUE_INTERNAL"];


		}

		return $result;
	}

	/* note:  limitation for Russia - product can be just in one category */
	protected function bitrixToEbayCategories($iblockId, array $bitrixCategories)
	{
		$categories = $this->getEbayCategoriesParams($iblockId, $bitrixCategories);
		$result = array();

		foreach($categories as $category)
			$result[] = $category["VALUE_EXTERNAL"];

		return $result;
	}

	protected static function getEbayCategoriesParams($iblockId, array $bitrixCategories = array())
	{
		static $entitiesIds = array();

		if(empty($entitiesIds[$iblockId]))
		{
			$res = \Bitrix\Sale\TradingPlatform\Ebay\MapHelper::getCategoryEntityId($iblockId);

			if(!$res)
				return array();

			$entitiesIds[$iblockId] = $res;
		}

		static $params  = array();

		if(!isset($params[$iblockId]))
		{
			$params[$iblockId] = array();

			$catRes = \Bitrix\Sale\TradingPlatform\MapTable::getList(array(
				'filter' => array(
					'=ENTITY_ID' => $entitiesIds[$iblockId],
				),
			));

			while($category = $catRes->fetch())
				$params[$iblockId][$category["VALUE_INTERNAL"]] = $category;
		}

		$result = array();

		if(!empty($bitrixCategories))
		{
			foreach($bitrixCategories as $catId)
				if(isset($params[$iblockId][$catId]) && is_array($params[$iblockId][$catId]))
					$result[] = $params[$iblockId][$catId];
		}
		else
		{
			$result = $params[$iblockId];
		}

		return $result;
	}

	protected function getPolicyForCategory($iblockId, $ebayCategory)
	{
		static $result = array();

		if(!isset($result[$ebayCategory]))
		{
			$ebay = \Bitrix\Sale\TradingPlatform\Ebay\Ebay::getInstance();
			$settings = $ebay->getSettings();
			$siteSettings = $settings[$this->siteId];
			$policyReturnId = "";
			$policyPaymentId = "";
			$policyShippingId = "";
			$result[$ebayCategory] = array();

			foreach($this->getEbayCategoriesParams($iblockId) as $categoryParams)
			{
				if($categoryParams["VALUE_EXTERNAL"] != $ebayCategory)
					continue;

				if(!empty($categoryParams["PARAMS"]["POLICY"]))
				{
					if(!empty($categoryParams["PARAMS"]["POLICY"]["RETURN"]))
						$policyReturnId = $categoryParams["PARAMS"]["POLICY"]["RETURN"];

					if(!empty($categoryParams["PARAMS"]["POLICY"]["SHIPPING"]))
						$policyShippingId = $categoryParams["PARAMS"]["POLICY"]["SHIPPING"];

					if(!empty($categoryParams["PARAMS"]["POLICY"]["PAYMENT"]))
						$policyPaymentId = $categoryParams["PARAMS"]["POLICY"]["PAYMENT"];
				}

				if(strlen($policyReturnId) <= 0 && !empty($siteSettings["POLICY"]["RETURN"]["DEFAULT"]))
					$policyReturnId = $siteSettings["POLICY"]["RETURN"]["DEFAULT"];

				if(strlen($policyShippingId) <= 0 && !empty($siteSettings["POLICY"]["SHIPPING"]["DEFAULT"]))
					$policyShippingId = $siteSettings["POLICY"]["SHIPPING"]["DEFAULT"];

				if(strlen($policyPaymentId) <= 0 && !empty($siteSettings["POLICY"]["PAYMENT"]["DEFAULT"]))
					$policyPaymentId = $siteSettings["POLICY"]["PAYMENT"]["DEFAULT"];

				if($policyReturnId != "" && !empty($siteSettings["POLICY"]["RETURN"]["LIST"][$policyReturnId]))
					$result[$ebayCategory]["RETURN"] = $siteSettings["POLICY"]["RETURN"]["LIST"][$policyReturnId];

				if($policyShippingId != "" && !empty($siteSettings["POLICY"]["SHIPPING"]["LIST"][$policyShippingId]))
					$result[$ebayCategory]["SHIPPING"] = $siteSettings["POLICY"]["SHIPPING"]["LIST"][$policyShippingId];

				if($policyPaymentId != "" && !empty($siteSettings["POLICY"]["PAYMENT"]["LIST"][$policyPaymentId]))
					$result[$ebayCategory]["PAYMENT"] = $siteSettings["POLICY"]["PAYMENT"]["LIST"][$policyPaymentId];

				break;
			}
		}

		return $result[$ebayCategory];
	}
} 