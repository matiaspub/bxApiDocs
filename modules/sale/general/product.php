<?
use Bitrix\Iblock;
use Bitrix\Main;
IncludeModuleLangFile(__FILE__);

class CALLSaleProduct
{
	public static $arProductIblockInfoCache = array();

	static function GetProductSkuProps($ID, $IBLOCK_ID = '', $getExt = false)
	{
		$getExt = ($getExt === true);
		$arSkuProps = array();
		$ID = (int)$ID;
		$IBLOCK_ID = (int)$IBLOCK_ID;
		if ($ID > 0 && \Bitrix\Main\Loader::includeModule('catalog'))
		{
			$filter = array('ID' => $ID, 'ACTIVE' => 'Y');
			if ($IBLOCK_ID > 0)
				$filter['IBLOCK_ID'] = $IBLOCK_ID;
			$res = CIBlockElement::GetList(
				array(),
				$filter,
				false,
				false,
				array("ID", "IBLOCK_ID")
			);
			$arElement = $res->Fetch();
			if ($arElement)
			{
				$arElement['ID'] = (int)$arElement['ID'];
				$arParent = CCatalogSku::GetProductInfo($ID, $arElement["IBLOCK_ID"]);
				if ($arParent)
				{
					$arElement['PROPERTIES'] = array();
					$arElementLink = array(
						$arElement['ID'] => &$arElement
					);
					$arFilter = array('ID' => $arElement['ID'], 'IBLOCK_ID' => $arElement["IBLOCK_ID"]);
					CIBlockElement::GetPropertyValuesArray($arElementLink, $arElement["IBLOCK_ID"], $arFilter);
					if (!empty($arElement['PROPERTIES']))
					{
						foreach ($arElement['PROPERTIES'] as &$prop)
						{
							if ($prop['XML_ID'] == 'CML2_LINK' || $prop['PROPERTY_TYPE'] == 'F')
							{
								continue;
							}
							$boolArr = is_array($prop["VALUE"]);
							if(
								($boolArr && !empty($prop["VALUE"]))
								|| (!$boolArr && strlen($prop["VALUE"]) > 0)
							)
							{
								$displayProperty = CIBlockFormatProperties::GetDisplayValue($arElement, $prop, '');
								$mxValues = '';
								if ('E' == $prop['PROPERTY_TYPE'])
								{
									if (!empty($displayProperty['LINK_ELEMENT_VALUE']))
									{
										$mxValues = array();
										foreach ($displayProperty['LINK_ELEMENT_VALUE'] as $arTempo)
											$mxValues[] = $arTempo['NAME'].' ['.$arTempo['ID'].']';
									}
								}
								elseif ('G' == $prop['PROPERTY_TYPE'])
								{
									if (!empty($displayProperty['LINK_SECTION_VALUE']))
									{
										$mxValues = array();
										foreach ($displayProperty['LINK_SECTION_VALUE'] as $arTempo)
											$mxValues[] = $arTempo['NAME'].' ['.$arTempo['ID'].']';
									}
								}
								if (empty($mxValues))
								{
									$mxValues = $displayProperty["DISPLAY_VALUE"];
								}
								if ($getExt)
								{
									$arSkuProps[$prop["ID"]] = array(
										'ID' => $prop["ID"],
										'CODE' => $prop['CODE'],
										'NAME' => $prop["NAME"],
										'VALUE' => strip_tags(is_array($mxValues) ? implode("/ ", $mxValues) : $mxValues)
									);
								}
								else
								{
									$arSkuProps[$prop["NAME"]] = strip_tags(is_array($mxValues) ? implode("/ ", $mxValues) : $mxValues);
								}
							}
						}
						unset($prop);
					}
				}
			}
		}
		return $arSkuProps;
	}


	/**
	 * get sku for product.
	 *
	 * @param integer $USER_ID				User.
	 * @param string  $LID					Site.
	 * @param integer $PRODUCT_ID			Product id.
	 * @param string  $PRODUCT_NAME			Product name.
	 * @param string CURRENCY				Currency.
	 * @param array $arProduct				Iblock list.
	 * @return array|false
	 */
	public static function GetProductSku($USER_ID, $LID, $PRODUCT_ID, $PRODUCT_NAME = '', $CURRENCY = '', $arProduct = array())
	{
		$USER_ID = (int)$USER_ID;

		$PRODUCT_ID = (int)$PRODUCT_ID;
		if ($PRODUCT_ID <= 0)
			return false;

		$LID = trim($LID);
		if ($LID == '')
			return false;

		$PRODUCT_NAME = trim($PRODUCT_NAME);
		$arResult = array();
		$arOffers = array();

		static $arCacheGroups = array();

		if (!is_set($arCacheGroups[$USER_ID]))
			$arCacheGroups[$USER_ID] = CUser::GetUserGroup($USER_ID);
		$arGroups = $arCacheGroups[$USER_ID];

		if (empty($arProduct))
			$arProduct = CSaleProduct::GetProductListIblockInfo(array($PRODUCT_ID));

		static $arOffersIblock = array();
		if (!is_set($arOffersIblock[$arProduct["IBLOCK_ID"]]))
		{
			$mxResult = CCatalogSKU::GetInfoByProductIBlock($arProduct["IBLOCK_ID"]);
			if (is_array($mxResult))
				$arOffersIblock[$arProduct["IBLOCK_ID"]] = $mxResult["IBLOCK_ID"];
		}

		if ($arOffersIblock[$arProduct["IBLOCK_ID"]] > 0)
		{

			static $arCacheOfferProperties = array();
			if (!is_set($arCacheOfferProperties[$arOffersIblock[$arProduct["IBLOCK_ID"]]]))
			{
				$dbOfferProperties = CIBlock::GetProperties($arOffersIblock[$arProduct["IBLOCK_ID"]], array(), array("!XML_ID" => "CML2_LINK"));
				while($arOfferProperties = $dbOfferProperties->Fetch())
					$arCacheOfferProperties[$arOffersIblock[$arProduct["IBLOCK_ID"]]][] = $arOfferProperties;
			}
			$arOfferProperties = $arCacheOfferProperties[$arOffersIblock[$arProduct["IBLOCK_ID"]]];


			$arIblockOfferProps = array();
			$arIblockOfferPropsFilter = array();
			if (is_array($arOfferProperties))
			{
				foreach ($arOfferProperties as $val)
				{
					$arIblockOfferProps[] = array("CODE" => $val["CODE"], "NAME" => $val["NAME"]);
					$arIblockOfferPropsFilter[] = $val["CODE"];
				}
			}

			$arOffers = CIBlockPriceTools::GetOffersArray(
						$arProduct["IBLOCK_ID"],
						$PRODUCT_ID,
						array("ID" => "DESC"),
						array("NAME"),
						$arIblockOfferPropsFilter,
						0,
						array(),
						1,
						array(),
						$USER_ID,
						$LID
			);
			$arSku = array();
			$minItemPrice = 0;
			$minItemPriceFormat = "";

			$arSkuId = array();
			$arImgSku = array();
			foreach($arOffers as $arOffer)
				$arSkuId[] = $arOffer['ID'];

			if (!empty($arSkuId))
			{
				$res = CIBlockElement::GetList(array(), array("ID" => $arSkuId), false, false, array("ID", "IBLOCK_ID", "NAME", "PREVIEW_PICTURE", "DETAIL_PICTURE", "DETAIL_PAGE_URL"));
				while($arOfferImg = $res->GetNext())
					$arImgSku[$arOfferImg["ID"]] = $arOfferImg;
			}

			foreach($arOffers as $arOffer)
			{
				$arPrice = CCatalogProduct::GetOptimalPrice($arOffer['ID'], 1, $arGroups, "N", array(), $LID);
				if (empty($arPrice))
				{
					break;
				}
				elseif (strlen($CURRENCY) > 0)
				{
					$arPrice["PRICE"]["PRICE"] = CCurrencyRates::ConvertCurrency($arPrice["PRICE"]["PRICE"], $arPrice["PRICE"]["CURRENCY"], $CURRENCY);
					if ($arPrice["DISCOUNT_PRICE"] > 0)
						$arPrice["DISCOUNT_PRICE"] = CCurrencyRates::ConvertCurrency($arPrice["DISCOUNT_PRICE"], $arPrice["PRICE"]["CURRENCY"], $CURRENCY);

					$arPrice["PRICE"]["CURRENCY"] = $CURRENCY;
				}
				$arSkuTmp = array();

				$arOffer["CAN_BUY"] = "N";
				$arCatalogProduct = CCatalogProduct::GetByID($arOffer['ID']);
				if (!empty($arCatalogProduct))
				{
					if ($arCatalogProduct["CAN_BUY_ZERO"]!="Y" && ($arCatalogProduct["QUANTITY_TRACE"]=="Y" && doubleval($arCatalogProduct["QUANTITY"])<=0))
						$arOffer["CAN_BUY"] = "N";
					else
						$arOffer["CAN_BUY"] = "Y";
				}

				$arSkuTmp["ImageUrl"] = '';
				if ($arOffer["CAN_BUY"] == "Y")
				{
					$productImg = "";
					if (isset($arImgSku[$arOffer['ID']]) && !empty($arImgSku[$arOffer['ID']]))
					{
						if ('' == $PRODUCT_NAME)
							$PRODUCT_NAME = $arImgSku[$arOffer['ID']]["~NAME"];

						if($arImgSku[$arOffer['ID']]["PREVIEW_PICTURE"] != "")
							$productImg = $arImgSku[$arOffer['ID']]["PREVIEW_PICTURE"];
						elseif($arImgSku[$arOffer['ID']]["DETAIL_PICTURE"] != "")
							$productImg = $arImgSku[$arOffer['ID']]["DETAIL_PICTURE"];

						if ($productImg == "")
						{
							if($arProduct["PREVIEW_PICTURE"] != "")
								$productImg = $arProduct["PREVIEW_PICTURE"];
							elseif($arProduct["DETAIL_PICTURE"] != "")
								$productImg = $arProduct["DETAIL_PICTURE"];
						}

						if ($productImg != "")
						{
							$arFile = CFile::GetFileArray($productImg);
							$productImg = CFile::ResizeImageGet($arFile, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_PROPORTIONAL, false, false);
							$arSkuTmp["ImageUrl"] = $productImg["src"];
						}
					}
				}

				if (($minItemPrice === 0) || ($arPrice["DISCOUNT_PRICE"] < $minItemPrice))
				{
					$minItemPrice = $arPrice["DISCOUNT_PRICE"];
					$minItemPriceFormat = SaleFormatCurrency($arPrice["DISCOUNT_PRICE"], $arPrice["PRICE"]["CURRENCY"]);
				}

				foreach($arIblockOfferProps as $arCode)
				{
					if (array_key_exists($arCode["CODE"], $arOffer["PROPERTIES"]))
					{
						if (is_array($arOffer["PROPERTIES"][$arCode["CODE"]]["VALUE"]))
							$arSkuTmp[] = implode("/", $arOffer["PROPERTIES"][$arCode["CODE"]]["VALUE"]);
						else
							$arSkuTmp[] = $arOffer["PROPERTIES"][$arCode["CODE"]]["VALUE"];
					}
				}

				if (!empty($arCatalogProduct))
				{
					$arSkuTmp["BALANCE"] = $arCatalogProduct["QUANTITY"];
					$arSkuTmp["WEIGHT"] = $arCatalogProduct["WEIGHT"];
					$arSkuTmp["BARCODE_MULTI"] = $arCatalogProduct["BARCODE_MULTI"];
				}
				else
				{
					$arSkuTmp["BALANCE"] = 0;
					$arSkuTmp["WEIGHT"] = 0;
					$arSkuTmp["BARCODE_MULTI"] = 'N';
				}
				$urlEdit = CIBlock::GetAdminElementEditLink(
					$arOffer["IBLOCK_ID"],
					$arOffer['ID'],
					array(
						'find_section_section' => 0,
						'WF' => 'Y',
					)
				);
				$discountPercent = 0;
				$arSkuTmp["USER_ID"] = $USER_ID;
				$arSkuTmp["ID"] = $arOffer["ID"];
				$arSkuTmp["NAME"] = CUtil::JSEscape($arOffer["NAME"]);
				$arSkuTmp["PRODUCT_NAME"] = CUtil::JSEscape($PRODUCT_NAME);
				$arSkuTmp["PRODUCT_ID"] = $PRODUCT_ID;
				$arSkuTmp["LID"] = CUtil::JSEscape($LID);
				$arSkuTmp["MIN_PRICE"] = $minItemPriceFormat;
				$arSkuTmp["URL_EDIT"] = $urlEdit;
				$arSkuTmp["DISCOUNT_PRICE"] = '';
				$arSkuTmp["DISCOUNT_PRICE_FORMATED"] = '';
				$arSkuTmp["PRICE"] = $arPrice["PRICE"]["PRICE"];
				$arSkuTmp["PRICE_FORMATED"] = CCurrencyLang::CurrencyFormat($arPrice["PRICE"]["PRICE"], $arPrice["PRICE"]["CURRENCY"], false);

				$arPriceType = GetCatalogGroup($arPrice["PRICE"]["CATALOG_GROUP_ID"]);
				$arSkuTmp["PRICE_TYPE"] = $arPriceType["NAME_LANG"];
				$arSkuTmp["VAT_RATE"] = $arPrice["PRICE"]["VAT_RATE"];

				if (count($arPrice["DISCOUNT"]) > 0)
				{
					$discountPercent = IntVal($arPrice["DISCOUNT"]["VALUE"]);

					$arSkuTmp["DISCOUNT_PRICE"] = $arPrice["DISCOUNT_PRICE"];
					$arSkuTmp["DISCOUNT_PRICE_FORMATED"] = CCurrencyLang::CurrencyFormat($arPrice["DISCOUNT_PRICE"], $arPrice["PRICE"]["CURRENCY"], false);
				}

				$arCurFormat = CCurrencyLang::GetCurrencyFormat($arPrice["PRICE"]["CURRENCY"]);
				$arSkuTmp["VALUTA_FORMAT"] = str_replace("#", '', $arCurFormat["FORMAT_STRING"]);
				$arSkuTmp["DISCOUNT_PERCENT"] = $discountPercent;
				$arSkuTmp["CURRENCY"] = $arPrice["PRICE"]["CURRENCY"];
				$arSkuTmp["CAN_BUY"] = $arOffer["CAN_BUY"];

				$arSku[] = $arSkuTmp;
			}
			if ((!is_array($arIblockOfferProps) || empty($arIblockOfferProps)) && is_array($arSku) && !empty($arSku))
			{
				$arIblockOfferProps[0] = array("CODE" => "TITLE", "NAME" => GetMessage("SKU_TITLE"));
				foreach ($arSku as $key => $val)
					$arSku[$key][0] = $val["NAME"];
			}

			$arResult["SKU_ELEMENTS"] = $arSku;
			$arResult["SKU_PROPERTIES"] = $arIblockOfferProps;
			$arResult["OFFERS_IBLOCK_ID"] = $arOffersIblock[$arProduct["IBLOCK_ID"]];
		}//if OFFERS_IBLOCK_ID > 0

		return $arResult;
	}

	/**
	 * Returns product iblock data by array of PRODUCT_ID
	 * Uses self::$arProductIblockInfoCache for result caching
	 * Requires array of IDs for better performance when working with multiple items
	 *
	 * @param array $arProductId array of integer PRODUCT_ID
	 * @return array
	 */
	public static function GetProductListIblockInfo($arProductId)
	{
		if (!CModule::IncludeModule('iblock'))
			return false;
		$arNewProductId = array();
		$arResult = array();

		if (!is_array($arProductId))
			$arProductId = array($arProductId);

		foreach ($arProductId as $productId)
		{
			$productId = (int)$productId;
			if ($productId <= 0)
				return false;

			if (!isset(self::$arProductIblockInfoCache[$productId]))
				$arNewProductId[$productId] = $productId;
		}

		if (!empty($arNewProductId))
		{
			$dbProduct = CIBlockElement::GetList(
				array(),
				array("ID" => $arNewProductId),
				false,
				false,
				array('ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE')
			);
			while ($arProduct = $dbProduct->Fetch())
				self::$arProductIblockInfoCache[$arProduct["ID"]] = $arProduct;
		}

		foreach ($arProductId as $productId)
		{
			if (isset(self::$arProductIblockInfoCache[$productId]))
				$arResult[$productId] = self::$arProductIblockInfoCache[$productId];
		}

		return $arResult;
	}

	public static function RefreshProductList()
	{
		global $DB;
		$strSql = "truncate table b_sale_product2product";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$strSql = "INSERT INTO b_sale_product2product (PRODUCT_ID, PARENT_PRODUCT_ID, CNT)
			select b.PRODUCT_ID as PRODUCT_ID, b1.PRODUCT_ID as PARENT_PRODUCT_ID, COUNT(b1.PRODUCT_ID) as CNT
			from b_sale_basket b
			left join b_sale_basket b1 on (b.ORDER_ID = b1.ORDER_ID)
			inner join b_sale_order o on (o.ID = b.ORDER_ID)
			where
				o.ALLOW_DELIVERY = 'Y'
				AND b.ID <> b1.ID
			GROUP BY b.PRODUCT_ID, b1.PRODUCT_ID";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return "CSaleProduct::RefreshProductList();";
	}

	/**
	 * Returns list of recommended products for specific product.
	 *
	 * @param int $USER_ID							User id.
	 * @param string $LID							Site id.
	 * @param array $arFilterRecomendet				Recomendation filter.
	 * @param string $recomMore						Get more.
	 * @param int $cntProductDefault				Max count.
	 * @return array
	 */
	public static function GetRecommendetProduct($USER_ID, $LID, $arFilterRecomendet = array(), $recomMore = 'N', $cntProductDefault = 2)
	{
		$arRecomendetResult = array();

		if (CModule::IncludeModule('catalog') && !empty($arFilterRecomendet))
		{
			$arRecomendet = array();
			if (!is_array($arFilterRecomendet))
				$arFilterRecomendet = array($arFilterRecomendet);
			Main\Type\Collection::normalizeArrayValuesByInt($arFilterRecomendet);
			if (empty($arFilterRecomendet))
				return $arRecomendetResult;

			$iblockRecommended = array();
			$productIterator = Iblock\ElementTable::getList(array(
				'select' => array('ID', 'IBLOCK_ID'),
				'filter' => array('@ID' => $arFilterRecomendet, '=ACTIVE' => 'Y')
			));
			while ($product = $productIterator->fetch())
			{
				$product['ID'] = (int)$product['ID'];
				$product['IBLOCK_ID'] = (int)$product['IBLOCK_ID'];
				if (!isset($iblockRecommended[$product['IBLOCK_ID']]))
					$iblockRecommended[$product['IBLOCK_ID']] = array();
				$iblockRecommended[$product['IBLOCK_ID']][] = $product['ID'];
			}
			unset($product, $productIterator);
			if (empty($iblockRecommended))
				return $arRecomendetResult;

			$propertyList = array();
			$propertyIterator = Iblock\PropertyTable::getList(array(
				'select' => array('ID', 'IBLOCK_ID'),
				'filter' => array('@IBLOCK_ID' => array_keys($iblockRecommended), '=CODE' => 'RECOMMEND', '=PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_ELEMENT)
			));
			while ($property = $propertyIterator->fetch())
			{
				$property['ID'] = (int)$property['ID'];
				$property['IBLOCK_ID'] = (int)$property['IBLOCK_ID'];
				$propertyList[$property['IBLOCK_ID']] = $property['ID'];
			}
			unset($property, $propertyIterator);
			if (empty($propertyList))
				return $arRecomendetResult;

			foreach ($propertyList as $iblockID => $propertyID)
			{
				$propertyValue = 'PROPERTY_'.$propertyID;
				$filter = array('ID' => $iblockRecommended[$iblockID], 'IBLOCK_ID' => $iblockID);
				$select = array('ID', 'IBLOCK_ID', $propertyValue);
				$propertyValue .= '_VALUE';
				$elementIterator = CIBlockElement::GetList(array(), $filter, false, false, $select);
				while ($element = $elementIterator->Fetch())
				{
					if (empty($element[$propertyValue]))
						continue;
					if (is_array($element[$propertyValue]))
					{
						foreach ($element[$propertyValue] as &$recId)
						{
							$recId = (int)$recId;
							if ($recId > 0)
								$arRecomendet[$recId] = true;
						}
						unset($recId);
					}
					else
					{
						$recId = (int)$element[$propertyValue];
						if ($recId > 0)
							$arRecomendet[$recId] = true;
					}
				}
			}
			unset($element, $elementIterator, $select, $filter, $propertyValue, $propertyID, $iblockID, $propertyList);

			if (!empty($arRecomendet))
			{
				$arRecomendet = array_keys($arRecomendet);
				$arBuyerGroups = CUser::GetUserGroup($USER_ID);

				$arFilter = array("ID" => $arRecomendet, "ACTIVE"=>"Y");
				$rsElement = CIBlockElement::GetList(
					array(),
					$arFilter,
					false,
					false,
					array("NAME", "ID", "LID", 'IBLOCK_ID', 'IBLOCK_SECTION_ID', "DETAIL_PICTURE", "PREVIEW_PICTURE", "DETAIL_PAGE_URL")
				);

				$currentVatMode = CCatalogProduct::getPriceVatIncludeMode();
				$currentUseDiscount = CCatalogProduct::getUseDiscount();
				CCatalogProduct::setUseDiscount(true);
				CCatalogProduct::setPriceVatIncludeMode(true);
				CCatalogProduct::setUsedCurrency(CSaleLang::GetLangCurrency($LID));
				$i = 0;
				while ($arElement = $rsElement->GetNext())
				{
					if (in_array($arElement["ID"], $arFilterRecomendet))
						continue;
					if (($recomMore == "N" && $i < $cntProductDefault) || $recomMore == "Y")
					{
						$arElement["MODULE"] = "catalog";
						$arElement["PRODUCT_PROVIDER_CLASS"] = "CCatalogProductProvider";
						$arElement["PRODUCT_ID"] = $arElement["ID"];

						$arPrice = CCatalogProduct::GetOptimalPrice($arElement["ID"], 1, $arBuyerGroups, "N", array(), $LID, array());

						$currentPrice = $arPrice['RESULT_PRICE']['DISCOUNT_PRICE'];
						$arElement["PRICE"] = $currentPrice;
						$arElement["CURRENCY"] = $arPrice["RESULT_PRICE"]["CURRENCY"];
						$arElement["DISCOUNT_PRICE"] = $arPrice['RESULT_PRICE']['DISCOUNT'];

						if ($arElement["IBLOCK_ID"] > 0 && $arElement["IBLOCK_SECTION_ID"] > 0)
						{
							$arElement["EDIT_PAGE_URL"] = CIBlock::GetAdminElementEditLink($arElement["IBLOCK_ID"], $arElement["PRODUCT_ID"], array(
								"find_section_section" => $arElement["IBLOCK_SECTION_ID"],
								'WF' => 'Y',
							));
						}

						$arRecomendetResult[] = $arElement;
						$i++;
					}
				}
				CCatalogProduct::clearUsedCurrency();
				CCatalogProduct::setPriceVatIncludeMode($currentVatMode);
				CCatalogProduct::setUseDiscount($currentUseDiscount);
				unset($currentUseDiscount, $currentVatMode);
			}
		}
		return $arRecomendetResult;
	}
}

class CAllSaleViewedProduct
{
	/**
	* Updates viewed product info for user
	*
	* @param int $ID - code field for update
	* @param array $arFields - parameters for update
	* @return true false
	*/
	static public function Update($ID, $arFields)
	{
		global $DB;

		foreach(GetModuleEvents("sale", "OnBeforeViewedUpdate", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array($ID, &$arFields))===false)
				return false;

		if (isset($arFields["ID"]))
			unset($arFields["ID"]);

		$strUpdateSql = "";
		if (!empty($arFields["~DATE_VISIT"]))
		{
			$strUpdateSql .= ", DATE_VISIT = ".$DB->ForSql($arFields["~DATE_VISIT"])." ";
			unset($arFields["DATE_VISIT"]);
			unset($arFields["~DATE_VISIT"]);
		}
		else
		{
			$strUpdateSql .= ", DATE_VISIT = ".$DB->GetNowFunction()." ";
		}

		$ID = IntVal($ID);
		$strUpdate = $DB->PrepareUpdate("b_sale_viewed_product", $arFields);

		$strSql = "UPDATE b_sale_viewed_product SET ".
						" ".$strUpdate.$strUpdateSql.
						" WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		foreach(GetModuleEvents("sale", "OnViewedUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		return $ID;
	}

	/**
	* The agent function delete old viewed
	*
	* @param
	* @return true false
	*/
	static public function ClearViewed()
	{
		CSaleViewedProduct::_ClearViewed();

		return "CSaleViewedProduct::ClearViewed();";
	}
}