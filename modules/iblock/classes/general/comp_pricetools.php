<?
class CIBlockPriceTools
{
	
	/**
	 * 
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpricetools/getcatalogprices.php
	 * @author Bitrix
	 */
	public static function GetCatalogPrices($IBLOCK_ID, $arPriceCode)
	{
		global $USER;
		$arCatalogPrices = array();
		if(CModule::IncludeModule("catalog"))
		{
			$arCatalogGroupCodesFilter = array();
			foreach($arPriceCode as $value)
			{
				$t_value = trim($value);
				if('' != $t_value)
					$arCatalogGroupCodesFilter[$value] = true;
			}
			$arCatalogGroupsFilter = array();
			$arCatalogGroups = CCatalogGroup::GetListArray();
			foreach($arCatalogGroups as $key => $value)
			{
				if(array_key_exists($value["NAME"], $arCatalogGroupCodesFilter))
				{
					$arCatalogGroupsFilter[] = $key;
					$arCatalogPrices[$value["NAME"]] = array(
						"ID" => htmlspecialcharsbx($value["ID"]),
						"TITLE" => htmlspecialcharsbx($value["NAME_LANG"]),
						"SELECT" => "CATALOG_GROUP_".$value["ID"],
					);
				}
			}
			$arPriceGroups = CCatalogGroup::GetGroupsPerms($USER->GetUserGroupArray(), $arCatalogGroupsFilter);
			foreach($arCatalogPrices as $name=>$value)
			{
				$arCatalogPrices[$name]["CAN_VIEW"]=in_array($value["ID"], $arPriceGroups["view"]);
				$arCatalogPrices[$name]["CAN_BUY"]=in_array($value["ID"], $arPriceGroups["buy"]);
			}
		}
		else
		{
			$arPriceGroups = array(
				"view" => array(),
			);
			$rsProperties = CIBlockProperty::GetList(array(), array(
				"IBLOCK_ID"=>$IBLOCK_ID,
				"CHECK_PERMISSIONS"=>"N",
				"PROPERTY_TYPE"=>"N",
			));
			while($arProperty = $rsProperties->Fetch())
			{
				if($arProperty["MULTIPLE"]=="N" && in_array($arProperty["CODE"], $arPriceCode))
				{
					$arPriceGroups["view"][]=htmlspecialcharsbx("PROPERTY_".$arProperty["CODE"]);
					$arCatalogPrices[$arProperty["CODE"]] = array(
						"ID"=>$arProperty["ID"],
						"TITLE"=>htmlspecialcharsbx($arProperty["NAME"]),
						"SELECT" => "PROPERTY_".$arProperty["ID"],
						"CAN_VIEW"=>true,
						"CAN_BUY"=>false,
					);
				}
			}
		}
		return $arCatalogPrices;
	}

	public static function GetAllowCatalogPrices($arPriceTypes)
	{
		$arResult = array();
		if (!empty($arPriceTypes) && is_array($arPriceTypes))
		{
			foreach ($arPriceTypes as &$arOnePriceType)
			{
				if ($arOnePriceType['CAN_VIEW'] || $arOnePriceType['CAN_BUY'])
					$arResult[] = intval($arOnePriceType['ID']);
			}
			if (isset($arOnePriceType))
				unset($arOnePriceType);
		}
		return $arResult;
	}

	public static function SetCatalogDiscountCache($arCatalogGroups, $arUserGroups)
	{
		global $DB;
		if (CModule::IncludeModule('catalog'))
		{
			if (!is_array($arCatalogGroups))
				return false;
			if (!is_array($arUserGroups))
				return false;
			CatalogClearArray($arCatalogGroups);
			if (empty($arCatalogGroups))
				return false;
			CatalogClearArray($arUserGroups);
			if (empty($arUserGroups))
				return false;

			$arRestFilter = array(
				'PRICE_TYPES' => $arCatalogGroups,
				'USER_GROUPS' => $arUserGroups,
			);
			$arRest = CCatalogDiscount::GetRestrictions($arRestFilter, false, false);
			$arDiscountFilter = array();
			$arDiscountResult = array();
			if (empty($arRest) || (array_key_exists('DISCOUNTS', $arRest) && empty($arRest['DISCOUNTS'])))
			{
				foreach ($arCatalogGroups as &$intOneGroupID)
				{
					$strCacheKey = CCatalogDiscount::GetDiscountFilterCacheKey(array($intOneGroupID), $arUserGroups, false);
					$arDiscountFilter[$strCacheKey] = array();
				}
				if (isset($intOneGroupID))
					unset($intOneGroupID);
			}
			else
			{
				$arSelect = array(
					"ID", "TYPE", "SITE_ID", "ACTIVE", "ACTIVE_FROM", "ACTIVE_TO",
					"RENEWAL", "NAME", "SORT", "MAX_DISCOUNT", "VALUE_TYPE", "VALUE", "CURRENCY",
					"PRIORITY", "LAST_DISCOUNT",
					"COUPON", "COUPON_ONE_TIME", "COUPON_ACTIVE", 'UNPACK'
				);
				$strDate = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")));
				$arFilter = array(
					"ID" => $arRest['DISCOUNTS'],
					"SITE_ID" => SITE_ID,
					"TYPE" => DISCOUNT_TYPE_STANDART,
					"ACTIVE" => "Y",
					"RENEWAL" => 'N',
					"+<=ACTIVE_FROM" => $strDate,
					"+>=ACTIVE_TO" => $strDate,
					'+COUPON' => array(),
				);

				$arResultDiscountList = array();

				$rsPriceDiscounts = CCatalogDiscount::GetList(
					array(),
					$arFilter,
					false,
					false,
					$arSelect
				);

				while ($arPriceDiscount = $rsPriceDiscounts->Fetch())
				{
					$arPriceDiscount['ID'] = intval($arPriceDiscount['ID']);
					$arResultDiscountList[$arPriceDiscount['ID']] = $arPriceDiscount;
				}

				foreach ($arCatalogGroups as &$intOneGroupID)
				{
					$strCacheKey = CCatalogDiscount::GetDiscountFilterCacheKey(array($intOneGroupID), $arUserGroups, false);
					$arDiscountDetailList = array();
					$arDiscountList = array();
					foreach ($arRest['RESTRICTIONS'] as $intDiscountID => $arDiscountRest)
					{
						if (empty($arDiscountRest['PRICE_TYPE']) || array_key_exists($intOneGroupID, $arDiscountRest['PRICE_TYPE']))
						{
							$arDiscountList[] = $intDiscountID;
							if (array_key_exists($intDiscountID, $arResultDiscountList))
								$arDiscountDetailList[] = $arResultDiscountList[$intDiscountID];
						}
					}
					sort($arDiscountList);
					$arDiscountFilter[$strCacheKey] = $arDiscountList;
					$strResultCacheKey = CCatalogDiscount::GetDiscountResultCacheKey($arDiscountList, SITE_ID, 'N');
					$arDiscountResult[$strResultCacheKey] = $arDiscountDetailList;
				}
				if (isset($intOneGroupID))
					unset($intOneGroupID);
			}
			$boolFlag = CCatalogDiscount::SetAllDiscountFilterCache($arDiscountFilter, false);
			$boolFlagExt = CCatalogDiscount::SetAllDiscountResultCache($arDiscountResult);
			return $boolFlag && $boolFlagExt;
		}
		return true;
	}

	
	/**
	 * <p>Метод возвращает цену с учётом скидок (без купонов), в том числе и сконвертированную в нужную валюту.</p>
	 *
	 *
	 *
	 *
	 * @param $IBLOCK_I $D  
	 *
	 *
	 *
	 * @param $arCatalogPrice $s  
	 *
	 *
	 *
	 * @param $arIte $m  
	 *
	 *
	 *
	 * @param $bVATInclud $e = true 
	 *
	 *
	 *
	 * @param $arCurrencyParam $s = array() В данном параметре необходимо передать код валюты, в которую
	 * необходимо произвести конвертацию: <pre class="syntax"> $arCurrencyParams =
	 * array('CURRENCY_ID' =&gt; 'USD'); // конвертировать в доллары </pre>
	 *
	 *
	 *
	 * @return function <p>Массив цен. Возвращает данные по ценам определённых типов,
	 * определённых товаров, определённого инфоблока, с учётом/без
	 * учёта НДС.</p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * Ценовое предложение без конвертации:
	 * array( 
	 *    "VALUE_NOVAT" =&gt; ,
	 *    "PRINT_VALUE_NOVAT" =&gt; ,
	 * 
	 *    "VALUE_VAT" =&gt; ,
	 *    "PRINT_VALUE_VAT" =&gt; ,
	 * 
	 *    "VATRATE_VALUE" =&gt; ,
	 *    "PRINT_VATRATE_VALUE" =&gt; ,
	 * 
	 *    "DISCOUNT_VALUE_NOVAT" =&gt; ,
	 *    "PRINT_DISCOUNT_VALUE_NOVAT" =&gt; ,
	 * 
	 *    "DISCOUNT_VALUE_VAT" =&gt; ,
	 *    "PRINT_DISCOUNT_VALUE_VAT" =&gt; ,
	 * 
	 *    'DISCOUNT_VATRATE_VALUE' =&gt; ,
	 *    'PRINT_DISCOUNT_VATRATE_VALUE' =&gt; ,
	 * 
	 *    "CURRENCY" =&gt; 'код валюты', 
	 *    'ID' =&gt; 'ID ценового предложения', 
	 *    'CAN_ACCESS' =&gt; 'возможность просмотра - Y/N', 
	 *    'CAN_BUY' =&gt; 'возможность купить - Y/N', 
	 *    'VALUE' =&gt; 'цена', 
	 *    'PRINT_VALUE' =&gt; 'отформатированная цена для вывода', 
	 *    'DISCOUNT_VALUE' =&gt; 'цена со скидкой',
	 *    'PRINT_DISCOUNT_VALUE' =&gt; 'отформатированная цена со скидкой'
	 * )
	 * Сконвертированное ценовое предложение:
	 * array(  
	 *    'ORIG_VALUE_NOVAT' =&gt; ,
	 *    "VALUE_NOVAT" =&gt; ,
	 *    "PRINT_VALUE_NOVAT" =&gt; ,
	 * 
	 *    'ORIG_VALUE_VAT' =&gt; ,
	 *    "VALUE_VAT" =&gt; ,
	 *    "PRINT_VALUE_VAT" =&gt; ,
	 * 
	 *    'ORIG_VATRATE_VALUE' =&gt; ,
	 *    "VATRATE_VALUE" =&gt; ,
	 *    "PRINT_VATRATE_VALUE" =&gt; ,
	 * 
	 *    'ORIG_DISCOUNT_VALUE_NOVAT' =&gt; ,
	 *    "DISCOUNT_VALUE_NOVAT" =&gt; ,
	 *    "PRINT_DISCOUNT_VALUE_NOVAT" =&gt; ,
	 * 
	 *    "ORIG_DISCOUNT_VALUE_VAT" =&gt; ,
	 *    "DISCOUNT_VALUE_VAT" =&gt; ,
	 *    "PRINT_DISCOUNT_VALUE_VAT" =&gt; ,
	 * 
	 *    'ORIG_DISCOUNT_VATRATE_VALUE' =&gt; ,
	 *    'DISCOUNT_VATRATE_VALUE' =&gt; ,
	 *    'PRINT_DISCOUNT_VATRATE_VALUE' =&gt; ,
	 * 
	 *    'ORIG_CURRENCY' =&gt; 'код исходной валюты',
	 *    "CURRENCY" =&gt; 'код валюты, в которую конвертим',
	 *  
	 *    'ID' =&gt; 'ID ценового предложения', 
	 *    'CAN_ACCESS' =&gt; 'возможность просмотра - Y/N', 
	 *    'CAN_BUY' =&gt; 'возможность покупки - Y/N', 
	 *    'VALUE' =&gt; 'цена', 
	 *    'PRINT_VALUE' =&gt; 'отформатированная цена для вывода', 
	 *    'DISCOUNT_VALUE' =&gt; 'цена со скидкой',
	 *    'PRINT_DISCOUNT_VALUE' =&gt; 'отформатированная цена со скидкой'
	 * )
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="../../../main/reference/cdbresult/index.php.html">CDBResult</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpricetools/getitemprices.php
	 * @author Bitrix
	 */
	public static function GetItemPrices($IBLOCK_ID, $arCatalogPrices, $arItem, $bVATInclude = true, $arCurrencyParams = array(), $USER_ID = 0, $LID = SITE_ID)
	{
		static $arCurUserGroups = array();

		global $USER;
		$arPrices = array();
		if(CModule::IncludeModule("catalog"))
		{
			$USER_ID = intval($USER_ID);
			$intUserID = $USER_ID;
			if (0 >= $intUserID)
				$intUserID = $USER->GetID();
			if (!array_key_exists($intUserID, $arCurUserGroups))
			{
				$arUserGroups = (0 < $USER_ID ? CUser::GetUserGroup($USER_ID) : $USER->GetUserGroupArray());
				CatalogClearArray($arUserGroups);
				$arCurUserGroups[$intUserID] = $arUserGroups;
			}
			else
			{
				$arUserGroups = $arCurUserGroups[$intUserID];
			}

			$boolConvert = false;
			$strCurrencyID = '';
			if (!empty($arCurrencyParams) && is_array($arCurrencyParams))
			{
				if (array_key_exists('CURRENCY_ID', $arCurrencyParams) && !empty($arCurrencyParams['CURRENCY_ID']))
				{
					$boolConvert = true;
					$strCurrencyID = $arCurrencyParams['CURRENCY_ID'];
				}
			}

			CCatalogDiscountSave::Disable();
			foreach($arCatalogPrices as $key => $value)
			{
				if($value["CAN_VIEW"] && strlen($arItem["CATALOG_PRICE_".$value["ID"]]) > 0)
				{
					// get final price with VAT included.
					if ($arItem['CATALOG_VAT_INCLUDED'] != 'Y')
					{
						$arItem['CATALOG_PRICE_'.$value['ID']] *= (1 + $arItem['CATALOG_VAT'] * 0.01);
					}
					// so discounts will include VAT
					$arDiscounts = CCatalogDiscount::GetDiscount(
						$arItem["ID"],
						$arItem["IBLOCK_ID"],
						array($value["ID"]),
						$arUserGroups,
						"N",
						$LID,
						array()
					);
					$discountPrice = CCatalogProduct::CountPriceWithDiscount(
						$arItem["CATALOG_PRICE_".$value["ID"]],
						$arItem["CATALOG_CURRENCY_".$value["ID"]],
						$arDiscounts
					);
					// get clear prices WO VAT
					$arItem['CATALOG_PRICE_'.$value['ID']] /= (1 + $arItem['CATALOG_VAT'] * 0.01);
					$discountPrice /= (1 + $arItem['CATALOG_VAT'] * 0.01);

					$vat_value_discount = $discountPrice * $arItem['CATALOG_VAT'] * 0.01;
					$vat_discountPrice = $discountPrice + $vat_value_discount;

					$vat_value = $arItem['CATALOG_PRICE_'.$value['ID']] * $arItem['CATALOG_VAT'] * 0.01;
					$vat_price = $arItem["CATALOG_PRICE_".$value["ID"]] + $vat_value;

					if ($boolConvert && $strCurrencyID != $arItem["CATALOG_CURRENCY_".$value["ID"]])
					{
						$strOrigCurrencyID = $arItem["CATALOG_CURRENCY_".$value["ID"]];
						$dblOrigNoVat = $arItem["CATALOG_PRICE_".$value["ID"]];
						$dblNoVat = CCurrencyRates::ConvertCurrency($dblOrigNoVat, $strOrigCurrencyID, $strCurrencyID);
						$dblVatPrice = CCurrencyRates::ConvertCurrency($vat_price, $strOrigCurrencyID, $strCurrencyID);
						$dblVatValue = CCurrencyRates::ConvertCurrency($vat_value, $strOrigCurrencyID, $strCurrencyID);
						$dblDiscountValueNoVat = CCurrencyRates::ConvertCurrency($discountPrice, $strOrigCurrencyID, $strCurrencyID);
						$dblVatDiscountPrice = CCurrencyRates::ConvertCurrency($vat_discountPrice, $strOrigCurrencyID, $strCurrencyID);
						$dblDiscountValueVat = CCurrencyRates::ConvertCurrency($vat_value_discount, $strOrigCurrencyID, $strCurrencyID);

						$arPrices[$key] = array(
							'ORIG_VALUE_NOVAT' => $dblOrigNoVat,
							"VALUE_NOVAT" => $dblNoVat,
							"PRINT_VALUE_NOVAT" => FormatCurrency($dblNoVat, $strCurrencyID),

							'ORIG_VALUE_VAT' => $vat_price,
							"VALUE_VAT" => $dblVatPrice,
							"PRINT_VALUE_VAT" => FormatCurrency($dblVatPrice, $strCurrencyID),

							'ORIG_VATRATE_VALUE' => $vat_value,
							"VATRATE_VALUE" => $dblVatValue,
							"PRINT_VATRATE_VALUE" => FormatCurrency($dblVatValue, $strCurrencyID),

							'ORIG_DISCOUNT_VALUE_NOVAT' => $discountPrice,
							"DISCOUNT_VALUE_NOVAT" => $dblDiscountValueNoVat,
							"PRINT_DISCOUNT_VALUE_NOVAT" => FormatCurrency($dblDiscountValueNoVat, $strCurrencyID),

							"ORIG_DISCOUNT_VALUE_VAT" => $vat_discountPrice,
							"DISCOUNT_VALUE_VAT" => $dblVatDiscountPrice,
							"PRINT_DISCOUNT_VALUE_VAT" => FormatCurrency($dblVatDiscountPrice, $strCurrencyID),

							'ORIG_DISCOUNT_VATRATE_VALUE' => $vat_value_discount,
							'DISCOUNT_VATRATE_VALUE' => $dblDiscountValueVat,
							'PRINT_DISCOUNT_VATRATE_VALUE' => FormatCurrency($dblDiscountValueVat, $strCurrencyID),

							'ORIG_CURRENCY' => $strOrigCurrencyID,
							"CURRENCY" => $strCurrencyID,
						);
					}
					else
					{
						$arPrices[$key] = array(
							"VALUE_NOVAT" => $arItem["CATALOG_PRICE_".$value["ID"]],
							"PRINT_VALUE_NOVAT" => FormatCurrency($arItem["CATALOG_PRICE_".$value["ID"]],$arItem["CATALOG_CURRENCY_".$value["ID"]]),

							"VALUE_VAT" => $vat_price,
							"PRINT_VALUE_VAT" => FormatCurrency($vat_price, $arItem["CATALOG_CURRENCY_".$value["ID"]]),

							"VATRATE_VALUE" => $vat_value,
							"PRINT_VATRATE_VALUE" => FormatCurrency($vat_value, $arItem["CATALOG_CURRENCY_".$value["ID"]]),

							"DISCOUNT_VALUE_NOVAT" => $discountPrice,
							"PRINT_DISCOUNT_VALUE_NOVAT" => FormatCurrency($discountPrice, $arItem["CATALOG_CURRENCY_".$value["ID"]]),

							"DISCOUNT_VALUE_VAT" => $vat_discountPrice,
							"PRINT_DISCOUNT_VALUE_VAT" => FormatCurrency($vat_discountPrice, $arItem["CATALOG_CURRENCY_".$value["ID"]]),

							'DISCOUNT_VATRATE_VALUE' => $vat_value_discount,
							'PRINT_DISCOUNT_VATRATE_VALUE' => FormatCurrency($vat_value_discount, $arItem["CATALOG_CURRENCY_".$value["ID"]]),

							"CURRENCY" => $arItem["CATALOG_CURRENCY_".$value["ID"]],
						);
					}
					$arPrices[$key]["ID"] = $arItem["CATALOG_PRICE_ID_".$value["ID"]];
					$arPrices[$key]["CAN_ACCESS"] = $arItem["CATALOG_CAN_ACCESS_".$value["ID"]];
					$arPrices[$key]["CAN_BUY"] = $arItem["CATALOG_CAN_BUY_".$value["ID"]];

					if ($bVATInclude)
					{
						$arPrices[$key]['VALUE'] = $arPrices[$key]['VALUE_VAT'];
						$arPrices[$key]['PRINT_VALUE'] = $arPrices[$key]['PRINT_VALUE_VAT'];
						$arPrices[$key]['DISCOUNT_VALUE'] = $arPrices[$key]['DISCOUNT_VALUE_VAT'];
						$arPrices[$key]['PRINT_DISCOUNT_VALUE'] = $arPrices[$key]['PRINT_DISCOUNT_VALUE_VAT'];
					}
					else
					{
						$arPrices[$key]['VALUE'] = $arPrices[$key]['VALUE_NOVAT'];
						$arPrices[$key]['PRINT_VALUE'] = $arPrices[$key]['PRINT_VALUE_NOVAT'];
						$arPrices[$key]['DISCOUNT_VALUE'] = $arPrices[$key]['DISCOUNT_VALUE_NOVAT'];
						$arPrices[$key]['PRINT_DISCOUNT_VALUE'] = $arPrices[$key]['PRINT_DISCOUNT_VALUE_NOVAT'];
					}
				}
			}
			CCatalogDiscountSave::Enable();
		}
		else
		{
			foreach($arCatalogPrices as $key => $value)
			{
				if($value["CAN_VIEW"])
				{
					$arPrices[$key] = array(
						"ID" => $arItem["PROPERTY_".$value["ID"]."_VALUE_ID"],
						"VALUE" => round(doubleval($arItem["PROPERTY_".$value["ID"]."_VALUE"]),2),
						"PRINT_VALUE" => round(doubleval($arItem["PROPERTY_".$value["ID"]."_VALUE"]),2)." ".$arItem["PROPERTY_".$value["ID"]."_DESCRIPTION"],
						"DISCOUNT_VALUE" => round(doubleval($arItem["PROPERTY_".$value["ID"]."_VALUE"]),2),
						"PRINT_DISCOUNT_VALUE" => round(doubleval($arItem["PROPERTY_".$value["ID"]."_VALUE"]),2)." ".$arItem["PROPERTY_".$value["ID"]."_DESCRIPTION"],
						"CURRENCY" => $arItem["PROPERTY_".$value["ID"]."_DESCRIPTION"],
						"CAN_ACCESS" => true,
						"CAN_BUY" => false,
					);
				}
			}
		}
		return $arPrices;
	}

	
	/**
	 * 
	 *
	 *
	 *
	 *
	 * @return mixed <p></p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p></p><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpricetools/canbuy.php
	 * @author Bitrix
	 */
	public static function CanBuy($IBLOCK_ID, $arCatalogPrices, $arItem)
	{
		if(is_array($arItem["PRICE_MATRIX"]))
		{
			return $arItem["PRICE_MATRIX"]["AVAILABLE"] == "Y";
		}
		else
		{
			foreach($arCatalogPrices as $arPrice)
			{
				if($arPrice["CAN_BUY"] && strlen($arItem["CATALOG_PRICE_".$arPrice["ID"]]) > 0)
				{
					if( $arItem["CATALOG_CAN_BUY_ZERO"] == "Y" || (
						($arItem["CATALOG_QUANTITY_TRACE"] != "Y")
						|| (doubleval($arItem["CATALOG_QUANTITY"]) > 0))
					)
					{
						return true;
					}
				}
			}
		}
		return false;
	}

	public static function GetProductProperties($IBLOCK_ID, $ELEMENT_ID, $arPropertiesList, $arPropertiesValues)
	{
		$arResult = array();
		foreach($arPropertiesList as $pid)
		{
			$prop = $arPropertiesValues[$pid];
			$arResult[$pid] = array("VALUES" => array(), "SELECTED" => false);
			$product_props = &$arResult[$pid];

			if($prop["MULTIPLE"] == "Y" && is_array($prop["VALUE"]))
			{
				switch($prop["PROPERTY_TYPE"])
				{
				case "S":
				case "N":
					foreach($prop["VALUE"] as $value)
					{
						if(!is_array($value) && strlen($value))
						{
							if($product_props["SELECTED"] === false)
								$product_props["SELECTED"] = $value;
							$product_props["VALUES"][$value] = $value;
						}
					}
					break;
				case "G":
					$ar = array();
					foreach($prop["VALUE"] as $value)
					{
						$value = intval($value);
						if($value > 0)
							$ar[] = $value;
					}
					$rsSections = CIBlockSection::GetList(array("LEFT_MARGIN"=>"ASC"), array("=ID"=>$ar));
					while($arSection = $rsSections->GetNext())
					{
						if($product_props["SELECTED"] === false)
							$product_props["SELECTED"] = $arSection["ID"];
						$product_props["VALUES"][$arSection["ID"]] = $arSection["NAME"];
					}
					break;
				case "E":
					$ar = array();
					foreach($prop["VALUE"] as $value)
					{
						$value = intval($value);
						if($value > 0)
							$ar[] = $value;
					}
					$rsElements = CIBlockElement::GetList(array("ID"=>"ASC"), array("=ID"=>$ar), false, false, array("ID", "NAME"));
					while($arElement = $rsElements->GetNext())
					{
						if($product_props["SELECTED"] === false)
							$product_props["SELECTED"] = $arElement["ID"];
						$product_props["VALUES"][$arElement["ID"]] = $arElement["NAME"];
					}
					break;
				case "L":
					foreach($prop["VALUE"] as $i => $value)
					{
						if($product_props["SELECTED"] === false)
							$product_props["SELECTED"] = $prop["VALUE_ENUM_ID"][$i];
						$product_props["VALUES"][$prop["VALUE_ENUM_ID"][$i]] = $value;
					}
					break;
				}
			}
			elseif($prop["MULTIPLE"] == "N")
			{
				switch($prop["PROPERTY_TYPE"])
				{
				case "L":
					$rsEnum = CIBlockPropertyEnum::GetList(array("SORT"=>"ASC", "VALUE"=>"ASC"), array("IBLOCK_ID"=>$IBLOCK_ID, "CODE"=>$pid));
					while($arEnum = $rsEnum->GetNext())
					{
						$product_props["VALUES"][$arEnum["ID"]] = $arEnum["VALUE"];
						if($arEnum["DEF"] == "Y")
							$product_props["SELECTED"] = $arEnum["ID"];
					}
					break;
				case "E":
					if($prop["LINK_IBLOCK_ID"] > 0)
					{
						$rsElements = CIBlockElement::GetList(
							array("NAME"=>"ASC", "SORT"=>"ASC"),
							array("IBLOCK_ID"=>$prop["LINK_IBLOCK_ID"], "ACTIVE"=>"Y"),
							false, false,
							array("ID", "NAME")
						);
						while($arElement = $rsElements->GetNext())
						{
							if($product_props["SELECTED"] === false)
								$product_props["SELECTED"] = $arElement["ID"];
							$product_props["VALUES"][$arElement["ID"]] = $arElement["NAME"];
						}
					}
					break;
				}
			}
		}
		return $arResult;
	}

	/*
	Checks arPropertiesValues against DB values
	returns array on success
	or number on fail (may be used for debug)
	*/
	public static function CheckProductProperties($IBLOCK_ID, $ELEMENT_ID, $arPropertiesList, $arPropertiesValues)
	{
		$SORT=1;
		$arResult = array();
		$param_props = array_flip($arPropertiesList);
		$rsProps = CIBlockElement::GetProperty($IBLOCK_ID, $ELEMENT_ID);
		while($arProp = $rsProps->Fetch())
		{
			if(in_array($arProp["CODE"], $arPropertiesList))
				$pid = $arProp["CODE"];
			elseif(in_array($arProp["ID"], $arPropertiesList))
				$pid = $arProp["CODE"];
			else
				continue; //Skip next. It's not an product property

			//Check if already handled
			if(!array_key_exists($pid, $param_props))
				continue;

			if(!strlen($arPropertiesValues[$pid])) //Property value MUST be there
			{
				return 1;
			}
			elseif($arProp["MULTIPLE"] == "Y")
			{
				switch($arProp["PROPERTY_TYPE"])
				{
				case "S":
				case "N":
					if($arProp["VALUE"] == $arPropertiesValues[$pid])
					{
						$arResult[] = array(
							"NAME" => $arProp["NAME"],
							"CODE" => $pid,
							"VALUE" => $arProp["VALUE"],
							"SORT" => $SORT++,
						);
						unset($param_props[$pid]);//mark as found
					}
					break;
				case "G":
					if($arProp["VALUE"] == $arPropertiesValues[$pid])
					{
						$rsSection = CIBlockSection::GetList(array(), array("=ID"=>$arProp["VALUE"]));
						if($arSection = $rsSection->Fetch())
						{
							$arResult[] = array(
								"NAME" => $arProp["NAME"],
								"CODE" => $pid,
								"VALUE" => $arSection["NAME"],
								"SORT" => $SORT++,
							);
							unset($param_props[$pid]);//mark as found
						}
					}
					break;
				case "E":
					if($arProp["VALUE"] == $arPropertiesValues[$pid])
					{
						$rsElement = CIBlockElement::GetList(array(), array("=ID"=>$arProp["VALUE"]), false, false, array("ID", "NAME"));
						if($arElement = $rsElement->Fetch())
						{
							$arResult[] = array(
								"NAME" => $arProp["NAME"],
								"CODE" => $pid,
								"VALUE" => $arElement["NAME"],
								"SORT" => $SORT++,
							);
							unset($param_props[$pid]);//mark as found
						}
					}
					break;
				case "L":
					if($arProp["VALUE"] == $arPropertiesValues[$pid])
					{
						$rsEnum = CIBlockPropertyEnum::GetList(array(), array("IBLOCK_ID"=>$IBLOCK_ID, "PROPERTY_ID" => $pid, "ID" => $arPropertiesValues[$pid]));
						if($arEnum = $rsEnum->Fetch())
						{
							$arResult[] = array(
								"NAME" => $arProp["NAME"],
								"CODE" => $pid,
								"VALUE" => $arEnum["VALUE"],
								"SORT" => $SORT++,
							);
							unset($param_props[$pid]);//mark as found
						}
					}
					break;
				default:
					return 2;
				}
			}
			else
			{
				switch($arProp["PROPERTY_TYPE"])
				{
				case "L":
					$rsEnum = CIBlockPropertyEnum::GetList(array(), array("IBLOCK_ID"=>$IBLOCK_ID, "PROPERTY_ID" => $pid, "ID" => $arPropertiesValues[$pid]));
					if($arEnum = $rsEnum->Fetch())
					{
						$arResult[] = array(
							"NAME" => $arProp["NAME"],
							"CODE" => $pid,
							"VALUE" => $arEnum["VALUE"],
							"SORT" => $SORT++,
						);
						unset($param_props[$pid]);//mark as found
					}
					break;
				case "E":
					if($arProp["LINK_IBLOCK_ID"] > 0)
					{
						$rsElement = CIBlockElement::GetList(array(), array("IBLOCK_ID"=>$arProp["LINK_IBLOCK_ID"], "ACTIVE" => "Y", "=ID" => $arPropertiesValues[$pid]), false, false, array("ID", "NAME"));
						if($arElement = $rsElement->Fetch())
						{
							$arResult[] = array(
								"NAME" => $arProp["NAME"],
								"CODE" => $pid,
								"VALUE" => $arElement["NAME"],
								"SORT" => $SORT++,
							);
							unset($param_props[$pid]);//mark as found
						}
					}
					break;
				default:
					return 3;
				}
			}
		}

		if(count($param_props))
			return 4;

		return $arResult;
	}

	public static function GetOffersIBlock($IBLOCK_ID)
	{
		$arResult = false;
		$IBLOCK_ID = intval($IBLOCK_ID);
		if (0 < $IBLOCK_ID)
		{
			if (CModule::IncludeModule("catalog"))
			{
				$arCatalog = CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);
				if (!empty($arCatalog) && is_array($arCatalog))
				{
					$arResult = array(
						'OFFERS_IBLOCK_ID' => $arCatalog['IBLOCK_ID'],
						'OFFERS_PROPERTY_ID' => $arCatalog['SKU_PROPERTY_ID'],
					);
				}
			}
		}
		return $arResult;
	}

	public static function GetOfferProperties($OFFER_ID, $IBLOCK_ID, $arPropertiesList)
	{
		$arResult = array();

		$arOffersIBlock = CIBlockPriceTools::GetOffersIBlock($IBLOCK_ID);
		if(!$arOffersIBlock)
			return $arResult;

		$SORT = 1;
		$rsProps = CIBlockElement::GetProperty(
			$arOffersIBlock["OFFERS_IBLOCK_ID"]
			,$OFFER_ID
			,array("sort"=>"asc", "enum_sort" => "asc", "value_id"=>"asc")
			,array("EMPTY"=>"N")
		);

		while($arProp = $rsProps->Fetch())
		{
			if(in_array($arProp["CODE"], $arPropertiesList))
				$pid = $arProp["CODE"];
			elseif(in_array($arProp["ID"], $arPropertiesList))
				$pid = $arProp["CODE"];
			else
				continue; //Skip next. It's not an product property

			switch($arProp["PROPERTY_TYPE"])
			{
			case "S":
			case "N":
				$arResult[] = array(
					"NAME" => $arProp["NAME"],
					"CODE" => $pid,
					"VALUE" => $arProp["VALUE"],
					"SORT" => $SORT++,
				);
				break;
			case "G":
				$rsSection = CIBlockSection::GetList(array(), array("=ID"=>$arProp["VALUE"]), false, array("NAME"));
				if($arSection = $rsSection->Fetch())
				{
					$arResult[] = array(
						"NAME" => $arProp["NAME"],
						"CODE" => $pid,
						"VALUE" => $arSection["NAME"],
						"SORT" => $SORT++,
					);
				}
				break;
			case "E":
				$rsElement = CIBlockElement::GetList(array(), array("=ID"=>$arProp["VALUE"]), false, false, array("ID", "NAME"));
				if($arElement = $rsElement->Fetch())
				{
					$arResult[] = array(
						"NAME" => $arProp["NAME"],
						"CODE" => $pid,
						"VALUE" => $arElement["NAME"],
						"SORT" => $SORT++,
					);
				}
				break;
			case "L":
				$arResult[] = array(
					"NAME" => $arProp["NAME"],
					"CODE" => $pid,
					"VALUE" => $arProp["VALUE_ENUM"],
					"SORT" => $SORT++,
				);
				break;
			}
		}

		return $arResult;
	}

	
	/**
	 * <p>Метод конвертирует цену в нужную валюту.</p>
	 *
	 *
	 *
	 *
	 * @param $IBLOCK_I $D  
	 *
	 *
	 *
	 * @param $arElementI $D  
	 *
	 *
	 *
	 * @param $arOrde $r  
	 *
	 *
	 *
	 * @param $arSelectField $s  
	 *
	 *
	 *
	 * @param $arSelectPropertie $s  
	 *
	 *
	 *
	 * @param $limi $t  
	 *
	 *
	 *
	 * @param $arPrice $s  
	 *
	 *
	 *
	 * @param $vat_includ $e  
	 *
	 *
	 *
	 * @param $arCurrencyParam $s = array() В данном параметре необходимо передать код валюты, в которую
	 * необходимо произвести конвертацию: <pre class="syntax"> $arCurrencyParams =
	 * array('CURRENCY_ID' =&gt; 'USD'); // конвертировать в доллары </pre>
	 *
	 *
	 *
	 * @return function <p>Массив цен.</p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * Ценовое предложение без конвертации:
	 * array( 
	 *    "VALUE_NOVAT" =&gt; ,
	 *    "PRINT_VALUE_NOVAT" =&gt; ,
	 * 
	 *    "VALUE_VAT" =&gt; ,
	 *    "PRINT_VALUE_VAT" =&gt; ,
	 * 
	 *    "VATRATE_VALUE" =&gt; ,
	 *    "PRINT_VATRATE_VALUE" =&gt; ,
	 * 
	 *    "DISCOUNT_VALUE_NOVAT" =&gt; ,
	 *    "PRINT_DISCOUNT_VALUE_NOVAT" =&gt; ,
	 * 
	 *    "DISCOUNT_VALUE_VAT" =&gt; ,
	 *    "PRINT_DISCOUNT_VALUE_VAT" =&gt; ,
	 * 
	 *    'DISCOUNT_VATRATE_VALUE' =&gt; ,
	 *    'PRINT_DISCOUNT_VATRATE_VALUE' =&gt; ,
	 * 
	 *    "CURRENCY" =&gt; 'код валюты', 
	 *    'ID' =&gt; 'ID ценового предложения', 
	 *    'CAN_ACCESS' =&gt; 'возможность просмотра - Y/N', 
	 *    'CAN_BUY' =&gt; 'возможность купить - Y/N', 
	 *    'VALUE' =&gt; 'цена', 
	 *    'PRINT_VALUE' =&gt; 'отформатированная цена для вывода', 
	 *    'DISCOUNT_VALUE' =&gt; 'цена со скидкой',
	 *    'PRINT_DISCOUNT_VALUE' =&gt; 'отформатированная цена со скидкой'
	 * )
	 * Сконвертированное ценовое предложение:
	 * array(  
	 *    'ORIG_VALUE_NOVAT' =&gt; ,
	 *    "VALUE_NOVAT" =&gt; ,
	 *    "PRINT_VALUE_NOVAT" =&gt; ,
	 * 
	 *    'ORIG_VALUE_VAT' =&gt; ,
	 *    "VALUE_VAT" =&gt; ,
	 *    "PRINT_VALUE_VAT" =&gt; ,
	 * 
	 *    'ORIG_VATRATE_VALUE' =&gt; ,
	 *    "VATRATE_VALUE" =&gt; ,
	 *    "PRINT_VATRATE_VALUE" =&gt; ,
	 * 
	 *    'ORIG_DISCOUNT_VALUE_NOVAT' =&gt; ,
	 *    "DISCOUNT_VALUE_NOVAT" =&gt; ,
	 *    "PRINT_DISCOUNT_VALUE_NOVAT" =&gt; ,
	 * 
	 *    "ORIG_DISCOUNT_VALUE_VAT" =&gt; ,
	 *    "DISCOUNT_VALUE_VAT" =&gt; ,
	 *    "PRINT_DISCOUNT_VALUE_VAT" =&gt; ,
	 * 
	 *    'ORIG_DISCOUNT_VATRATE_VALUE' =&gt; ,
	 *    'DISCOUNT_VATRATE_VALUE' =&gt; ,
	 *    'PRINT_DISCOUNT_VATRATE_VALUE' =&gt; ,
	 * 
	 *    'ORIG_CURRENCY' =&gt; 'код исходной валюты',
	 *    "CURRENCY" =&gt; 'код валюты, в которую конвертим',
	 *  
	 *    'ID' =&gt; 'ID ценового предложения', 
	 *    'CAN_ACCESS' =&gt; 'возможность просмотра - Y/N', 
	 *    'CAN_BUY' =&gt; 'возможность покупки - Y/N', 
	 *    'VALUE' =&gt; 'цена', 
	 *    'PRINT_VALUE' =&gt; 'отформатированная цена для вывода', 
	 *    'DISCOUNT_VALUE' =&gt; 'цена со скидкой',
	 *    'PRINT_DISCOUNT_VALUE' =&gt; 'отформатированная цена со скидкой'
	 * )
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="../../../main/reference/cdbresult/index.php.html">CDBResult</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpricetools/getoffersarray.php
	 * @author Bitrix
	 */
	public static function GetOffersArray($arFilter, $arElementID, $arOrder, $arSelectFields, $arSelectProperties, $limit, $arPrices, $vat_include, $arCurrencyParams = array(), $USER_ID = 0, $LID = SITE_ID)
	{
		$arResult = array();

		$boolHideNotAvailable = false;
		$IBLOCK_ID = 0;
		if (!empty($arFilter) && is_array($arFilter))
		{
			if (array_key_exists('IBLOCK_ID', $arFilter))
				$IBLOCK_ID = $arFilter['IBLOCK_ID'];
			if (array_key_exists('HIDE_NOT_AVAILABLE', $arFilter))
				$boolHideNotAvailable = 'Y' === $arFilter['HIDE_NOT_AVAILABLE'];
		}
		else
		{
			$IBLOCK_ID = $arFilter;
		}

		$arOffersIBlock = CIBlockPriceTools::GetOffersIBlock($IBLOCK_ID);
		if($arOffersIBlock)
		{
			$limit = intval($limit);
			if (0 > $limit)
				$limit = 0;

			if(!array_key_exists("ID", $arOrder))
				$arOrder["ID"] = "DESC";

			$arFilter = array(
				"IBLOCK_ID" => $arOffersIBlock["OFFERS_IBLOCK_ID"],
				"PROPERTY_".$arOffersIBlock["OFFERS_PROPERTY_ID"] => $arElementID,
				"ACTIVE" => "Y",
				"ACTIVE_DATE" => "Y",
			);
			if ($boolHideNotAvailable)
				$arFilter['CATALOG_AVAILABLE'] = 'Y';

			$arSelect = array(
				"ID" => 1,
				"IBLOCK_ID" => 1,
				"PROPERTY_".$arOffersIBlock["OFFERS_PROPERTY_ID"] => 1,
			);
			//if(!$arParams["USE_PRICE_COUNT"])
			{
				foreach($arPrices as $value)
				{
					$arSelect[$value["SELECT"]] = 1;
					//$arrFilter["CATALOG_SHOP_QUANTITY_".$value["ID"]] = $arParams["SHOW_PRICE_COUNT"];
				}
			}

			foreach($arSelectFields as $code)
				$arSelect[$code] = 1; //mark to select

			$arOffersPerElement = array();
			$rsOffers = CIBlockElement::GetList($arOrder, $arFilter, false, false, array_keys($arSelect));
			while($obOffer = $rsOffers->GetNextElement())
			{
				$arOffer = $obOffer->GetFields();
				$element_id = $arOffer["PROPERTY_".$arOffersIBlock["OFFERS_PROPERTY_ID"]."_VALUE"];
				//No more than limit offers per element
				if($limit > 0)
				{
					$arOffersPerElement[$element_id]++;
					if($arOffersPerElement[$element_id] > $limit)
						continue;
				}

				if($element_id > 0)
				{
					$arOffer["LINK_ELEMENT_ID"] = $element_id;
					$arOffer["DISPLAY_PROPERTIES"] = array();
					if(!empty($arSelectProperties))
					{
						$arOffer["PROPERTIES"] = $obOffer->GetProperties();
						foreach($arSelectProperties as $pid)
						{
							$prop = &$arOffer["PROPERTIES"][$pid];
							if((is_array($prop["VALUE"]) && count($prop["VALUE"])>0) ||
							(!is_array($prop["VALUE"]) && strlen($prop["VALUE"])>0))
							{
								$arOffer["DISPLAY_PROPERTIES"][$pid] = CIBlockFormatProperties::GetDisplayValue($arOffer, $prop, "catalog_out");
							}
						}
					}
					$arOffer["PRICES"] = CIBlockPriceTools::GetItemPrices($arOffersIBlock["OFFERS_IBLOCK_ID"], $arPrices, $arOffer, $vat_include, $arCurrencyParams, $USER_ID, $LID);
					$arOffer["CAN_BUY"] = CIBlockPriceTools::CanBuy($arOffersIBlock["OFFERS_IBLOCK_ID"], $arPrices, $arOffer);
				}
				$arResult[] = $arOffer;
			}
		}

		return $arResult;
	}
}
?>