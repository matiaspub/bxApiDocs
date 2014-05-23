<?
IncludeModuleLangFile(__FILE__);

class CIBlockPriceTools
{
	protected static $catalogIncluded = null;
	protected static $highLoadInclude = null;

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
		if (self::$catalogIncluded === null)
			self::$catalogIncluded = \Bitrix\Main\Loader::includeModule('catalog');
		if (self::$catalogIncluded)
		{
			$arCatalogGroupCodesFilter = array();
			foreach($arPriceCode as $value)
			{
				$t_value = trim($value);
				if ('' != $t_value)
					$arCatalogGroupCodesFilter[$value] = true;
			}
			$arCatalogGroupsFilter = array();
			$arCatalogGroups = CCatalogGroup::GetListArray();
			foreach ($arCatalogGroups as $key => $value)
			{
				if (isset($arCatalogGroupCodesFilter[$value['NAME']]))
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

		if (self::$catalogIncluded === null)
			self::$catalogIncluded = \Bitrix\Main\Loader::includeModule('catalog');
		if (self::$catalogIncluded)
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
							if (isset($arResultDiscountList[$intDiscountID]))
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
		return false;
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
		$arPrices = array();

		if (empty($arCatalogPrices) || !is_array($arCatalogPrices))
		{
			return $arPrices;
		}

		global $USER;
		static $arCurUserGroups = array();
		static $strBaseCurrency = '';

		if (self::$catalogIncluded === null)
			self::$catalogIncluded = \Bitrix\Main\Loader::includeModule('catalog');
		if (self::$catalogIncluded)
		{
			$USER_ID = intval($USER_ID);
			$intUserID = $USER_ID;
			if (0 >= $intUserID)
				$intUserID = $USER->GetID();
			if (!isset($arCurUserGroups[$intUserID]))
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
			if (isset($arCurrencyParams['CURRENCY_ID']) && !empty($arCurrencyParams['CURRENCY_ID']))
			{
				$boolConvert = true;
				$strCurrencyID = $arCurrencyParams['CURRENCY_ID'];
			}
			if (!$boolConvert && '' == $strBaseCurrency)
				$strBaseCurrency = CCurrency::GetBaseCurrency();

			$strMinCode = '';
			$boolStartMin = true;
			$dblMinPrice = 0;
			$strMinCurrency = ($boolConvert ? $strCurrencyID : $strBaseCurrency);
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
							"PRINT_VALUE_NOVAT" => CCurrencyLang::CurrencyFormat($dblNoVat, $strCurrencyID, true),

							'ORIG_VALUE_VAT' => $vat_price,
							"VALUE_VAT" => $dblVatPrice,
							"PRINT_VALUE_VAT" => CCurrencyLang::CurrencyFormat($dblVatPrice, $strCurrencyID, true),

							'ORIG_VATRATE_VALUE' => $vat_value,
							"VATRATE_VALUE" => $dblVatValue,
							"PRINT_VATRATE_VALUE" => CCurrencyLang::CurrencyFormat($dblVatValue, $strCurrencyID, true),

							'ORIG_DISCOUNT_VALUE_NOVAT' => $discountPrice,
							"DISCOUNT_VALUE_NOVAT" => $dblDiscountValueNoVat,
							"PRINT_DISCOUNT_VALUE_NOVAT" => CCurrencyLang::CurrencyFormat($dblDiscountValueNoVat, $strCurrencyID, true),

							"ORIG_DISCOUNT_VALUE_VAT" => $vat_discountPrice,
							"DISCOUNT_VALUE_VAT" => $dblVatDiscountPrice,
							"PRINT_DISCOUNT_VALUE_VAT" => CCurrencyLang::CurrencyFormat($dblVatDiscountPrice, $strCurrencyID, true),

							'ORIG_DISCOUNT_VATRATE_VALUE' => $vat_value_discount,
							'DISCOUNT_VATRATE_VALUE' => $dblDiscountValueVat,
							'PRINT_DISCOUNT_VATRATE_VALUE' => CCurrencyLang::CurrencyFormat($dblDiscountValueVat, $strCurrencyID, true),

							'ORIG_CURRENCY' => $strOrigCurrencyID,
							"CURRENCY" => $strCurrencyID,
						);
					}
					else
					{
						$strPriceCurrency = $arItem["CATALOG_CURRENCY_".$value["ID"]];
						$arPrices[$key] = array(
							"VALUE_NOVAT" => $arItem["CATALOG_PRICE_".$value["ID"]],
							"PRINT_VALUE_NOVAT" => CCurrencyLang::CurrencyFormat($arItem["CATALOG_PRICE_".$value["ID"]], $strPriceCurrency, true),

							"VALUE_VAT" => $vat_price,
							"PRINT_VALUE_VAT" => CCurrencyLang::CurrencyFormat($vat_price, $strPriceCurrency, true),

							"VATRATE_VALUE" => $vat_value,
							"PRINT_VATRATE_VALUE" => CCurrencyLang::CurrencyFormat($vat_value, $strPriceCurrency, true),

							"DISCOUNT_VALUE_NOVAT" => $discountPrice,
							"PRINT_DISCOUNT_VALUE_NOVAT" => CCurrencyLang::CurrencyFormat($discountPrice, $strPriceCurrency, true),

							"DISCOUNT_VALUE_VAT" => $vat_discountPrice,
							"PRINT_DISCOUNT_VALUE_VAT" => CCurrencyLang::CurrencyFormat($vat_discountPrice, $strPriceCurrency, true),

							'DISCOUNT_VATRATE_VALUE' => $vat_value_discount,
							'PRINT_DISCOUNT_VATRATE_VALUE' => CCurrencyLang::CurrencyFormat($vat_value_discount, $strPriceCurrency, true),

							"CURRENCY" => $arItem["CATALOG_CURRENCY_".$value["ID"]],
						);
					}
					$arPrices[$key]["ID"] = $arItem["CATALOG_PRICE_ID_".$value["ID"]];
					$arPrices[$key]["CAN_ACCESS"] = $arItem["CATALOG_CAN_ACCESS_".$value["ID"]];
					$arPrices[$key]["CAN_BUY"] = $arItem["CATALOG_CAN_BUY_".$value["ID"]];
					$arPrices[$key]['MIN_PRICE'] = 'N';

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

					if (roundEx($arPrices[$key]['VALUE'], 2) == roundEx($arPrices[$key]['DISCOUNT_VALUE'], 2))
					{
						$arPrices[$key]['DISCOUNT_DIFF'] = 0;
						$arPrices[$key]['DISCOUNT_DIFF_PERCENT'] = 0;
						$arPrices[$key]['PRINT_DISCOUNT_DIFF'] = CCurrencyLang::CurrencyFormat(0, $arPrices[$key]['CURRENCY'], true);
					}
					else
					{
						$arPrices[$key]['DISCOUNT_DIFF'] = $arPrices[$key]['VALUE'] - $arPrices[$key]['DISCOUNT_VALUE'];
						$arPrices[$key]['DISCOUNT_DIFF_PERCENT'] = roundEx(100*$arPrices[$key]['DISCOUNT_DIFF']/$arPrices[$key]['VALUE'], 0);
						$arPrices[$key]['PRINT_DISCOUNT_DIFF'] = CCurrencyLang::CurrencyFormat($arPrices[$key]['DISCOUNT_DIFF'], $arPrices[$key]['CURRENCY'], true);
					}

					if ($value["CAN_VIEW"])
					{
						if ($boolStartMin)
						{
							$dblMinPrice = ($boolConvert || ($arPrices[$key]['CURRENCY'] == $strMinCurrency)
								? $arPrices[$key]['DISCOUNT_VALUE']
								: CCurrencyRates::ConvertCurrency($arPrices[$key]['DISCOUNT_VALUE'], $arPrices[$key]['CURRENCY'], $strMinCurrency)
							);
							$strMinCode = $key;
							$boolStartMin = false;
						}
						else
						{
							$dblComparePrice = ($boolConvert || ($arPrices[$key]['CURRENCY'] == $strMinCurrency)
								? $arPrices[$key]['DISCOUNT_VALUE']
								: CCurrencyRates::ConvertCurrency($arPrices[$key]['DISCOUNT_VALUE'], $arPrices[$key]['CURRENCY'], $strMinCurrency)
							);
							if ($dblMinPrice > $dblComparePrice)
							{
								$dblMinPrice = $dblComparePrice;
								$strMinCode = $key;
							}
						}
					}
				}
			}
			if ('' != $strMinCode)
				$arPrices[$strMinCode]['MIN_PRICE'] = 'Y';
			CCatalogDiscountSave::Enable();
		}
		else
		{
			$strMinCode = '';
			$boolStartMin = true;
			$dblMinPrice = 0;
			foreach($arCatalogPrices as $key => $value)
			{
				if($value["CAN_VIEW"])
				{
					$dblValue = round(doubleval($arItem["PROPERTY_".$value["ID"]."_VALUE"]), 2);
					if ($boolStartMin)
					{
						$dblMinPrice = $dblValue;
						$strMinCode = $key;
						$boolStartMin = false;
					}
					else
					{
						if ($dblMinPrice > $dblValue)
						{
							$dblMinPrice = $dblValue;
							$strMinCode = $key;
						}
					}
					$arPrices[$key] = array(
						"ID" => $arItem["PROPERTY_".$value["ID"]."_VALUE_ID"],
						"VALUE" => $dblValue,
						"PRINT_VALUE" => $dblValue." ".$arItem["PROPERTY_".$value["ID"]."_DESCRIPTION"],
						"DISCOUNT_VALUE" => $dblValue,
						"PRINT_DISCOUNT_VALUE" => $dblValue." ".$arItem["PROPERTY_".$value["ID"]."_DESCRIPTION"],
						"CURRENCY" => $arItem["PROPERTY_".$value["ID"]."_DESCRIPTION"],
						"CAN_ACCESS" => true,
						"CAN_BUY" => false,
						'DISCOUNT_DIFF_PERCENT' => 0,
						'DISCOUNT_DIFF' => 0,
						'PRINT_DISCOUNT_DIFF' => '0 '.$arItem["PROPERTY_".$value["ID"]."_DESCRIPTION"],
						"MIN_PRICE" => "N"
					);
				}
			}
			if ('' != $strMinCode)
				$arPrices[$strMinCode]['MIN_PRICE'] = 'Y';
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
		if (isset($arItem['CATALOG_AVAILABLE']) && 'N' == $arItem['CATALOG_AVAILABLE'])
			return false;

		if(isset($arItem["PRICE_MATRIX"]) && !empty($arItem["PRICE_MATRIX"]) && is_array($arItem["PRICE_MATRIX"]))
		{
			return $arItem["PRICE_MATRIX"]["AVAILABLE"] == "Y";
		}
		else
		{
			if (empty($arCatalogPrices) || !is_array($arCatalogPrices))
			{
				return false;
			}

			foreach($arCatalogPrices as $arPrice)
			{
				if($arPrice["CAN_BUY"] && isset($arItem["CATALOG_PRICE_".$arPrice["ID"]]) && $arItem["CATALOG_PRICE_".$arPrice["ID"]] !== null)
				{
					return true;
				}
			}
		}
		return false;
	}

	public static function GetProductProperties($IBLOCK_ID, $ELEMENT_ID, $arPropertiesList, $arPropertiesValues)
	{
		static $cache = array();
		static $userTypeList = array();
		$propertyTypeSupport = array(
			'Y' => array(
				'N' => true,
				'S' => true,
				'L' => true,
				'G' => true,
				'E' => true
			),
			'N' => array(
				'L' => true,
				'E' => true
			)
		);

		$result = array();
		foreach ($arPropertiesList as $pid)
		{
			$prop = $arPropertiesValues[$pid];
			$prop['ID'] = intval($prop['ID']);
			if (!isset($propertyTypeSupport[$prop['MULTIPLE']][$prop['PROPERTY_TYPE']]))
			{
				continue;
			}
			$emptyValues = true;
			$productProp = array('VALUES' => array(), 'SELECTED' => false, 'SET' => false);

			$userTypeProp = false;
			$userType = null;
			if (isset($prop['USER_TYPE']) && !empty($prop['USER_TYPE']))
			{
				if (!isset($userTypeList[$prop['USER_TYPE']]))
				{
					$userTypeDescr = CIBlockProperty::GetUserType($prop['USER_TYPE']);
					if (isset($userTypeDescr['GetPublicViewHTML']))
					{
						$userTypeList[$prop['USER_TYPE']] = $userTypeDescr['GetPublicViewHTML'];
					}
				}
				if (isset($userTypeList[$prop['USER_TYPE']]))
				{
					$userTypeProp = true;
					$userType = $userTypeList[$prop['USER_TYPE']];
				}
			}

			if ($prop["MULTIPLE"] == "Y" && !empty($prop["VALUE"]) && is_array($prop["VALUE"]))
			{
				if ($userTypeProp)
				{
					$countValues = 0;
					foreach($prop["VALUE"] as $value)
					{
						if (!is_scalar($value))
							continue;
						$value = (string)$value;
						$displayValue = (string)call_user_func_array($userType,
							array(
								$prop,
								array('VALUE' => $value),
								array(array('MODE' => 'SIMPLE_TEXT'))
							));
						if ('' !== $displayValue)
						{
							if ($productProp["SELECTED"] === false)
								$productProp["SELECTED"] = $value;
							$productProp["VALUES"][$value] = htmlspecialcharsbx($displayValue);
							$emptyValues = false;
							$countValues++;
						}
					}
					$productProp['SET'] = ($countValues === 1);
				}
				else
				{
					switch($prop["PROPERTY_TYPE"])
					{
					case "S":
					case "N":
						$countValues = 0;
						foreach($prop["VALUE"] as $value)
						{
							if (!is_scalar($value))
								continue;
							$value = (string)$value;
							if($value !== '')
							{
								if($productProp["SELECTED"] === false)
									$productProp["SELECTED"] = $value;
								$productProp["VALUES"][$value] = $value;
								$emptyValues = false;
								$countValues++;
							}
							$productProp['SET'] = ($countValues === 1);
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
						if (!empty($ar))
						{
							$countValues = 0;
							$rsSections = CIBlockSection::GetList(
								array("LEFT_MARGIN"=>"ASC"),
								array("=ID" => $ar),
								false,
								array('ID', 'NAME')
							);
							while ($arSection = $rsSections->GetNext())
							{
								$arSection["ID"] = intval($arSection["ID"]);
								if ($productProp["SELECTED"] === false)
									$productProp["SELECTED"] = $arSection["ID"];
								$productProp["VALUES"][$arSection["ID"]] = $arSection["NAME"];
								$emptyValues = false;
								$countValues++;
							}
							$productProp['SET'] = ($countValues === 1);
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
						if (!empty($ar))
						{
							$countValues = 0;
							$rsElements = CIBlockElement::GetList(
								array("ID" => "ASC"),
								array("=ID" => $ar),
								false,
								false,
								array("ID", "NAME")
							);
							while($arElement = $rsElements->GetNext())
							{
								$arElement['ID'] = intval($arElement['ID']);
								if($productProp["SELECTED"] === false)
									$productProp["SELECTED"] = $arElement["ID"];
								$productProp["VALUES"][$arElement["ID"]] = $arElement["NAME"];
								$emptyValues = false;
								$countValues++;
							}
							$productProp['SET'] = ($countValues === 1);
						}
						break;
					case "L":
						$countValues = 0;
						foreach($prop["VALUE"] as $i => $value)
						{
							$prop["VALUE_ENUM_ID"][$i] = intval($prop["VALUE_ENUM_ID"][$i]);
							if($productProp["SELECTED"] === false)
								$productProp["SELECTED"] = $prop["VALUE_ENUM_ID"][$i];
							$productProp["VALUES"][$prop["VALUE_ENUM_ID"][$i]] = $value;
							$emptyValues = false;
							$countValues++;
						}
						$productProp['SET'] = ($countValues === 1);
						break;
					}
				}
			}
			elseif($prop["MULTIPLE"] == "N")
			{
				switch($prop["PROPERTY_TYPE"])
				{
				case "L":
					if (0 == intval($prop["VALUE_ENUM_ID"]))
					{
						if (isset($cache[$prop['ID']]))
						{
							$productProp = $cache[$prop['ID']];
							$emptyValues = false;
						}
						else
						{
							$rsEnum = CIBlockPropertyEnum::GetList(
								array("SORT"=>"ASC", "VALUE"=>"ASC"),
								array("IBLOCK_ID"=>$IBLOCK_ID, "PROPERTY_ID" => $prop['ID'])
							);
							while ($arEnum = $rsEnum->GetNext())
							{
								$arEnum["ID"] = intval($arEnum["ID"]);
								$productProp["VALUES"][$arEnum["ID"]] = $arEnum["VALUE"];
								if ($arEnum["DEF"] == "Y")
									$productProp["SELECTED"] = $arEnum["ID"];
								$emptyValues = false;
							}
							if (!$emptyValues)
							{
								$cache[$prop['ID']] = $productProp;
							}
						}
					}
					else
					{
						$prop['VALUE_ENUM_ID'] = intval($prop['VALUE_ENUM_ID']);
						$productProp['VALUES'][$prop['VALUE_ENUM_ID']] = $prop['VALUE'];
						$productProp['SELECTED'] = $prop['VALUE_ENUM_ID'];
						$productProp['SET'] = true;
						$emptyValues = false;
					}
					break;
				case "E":
					if (0 == intval($prop['VALUE']))
					{
						if (isset($cache[$prop['ID']]))
						{
							$productProp = $cache[$prop['ID']];
							$emptyValues = false;
						}
						else
						{
							if($prop["LINK_IBLOCK_ID"] > 0)
							{
								$rsElements = CIBlockElement::GetList(
									array("NAME"=>"ASC", "SORT"=>"ASC"),
									array("IBLOCK_ID"=>$prop["LINK_IBLOCK_ID"], "ACTIVE"=>"Y"),
									false, false,
									array("ID", "NAME")
								);
								while ($arElement = $rsElements->GetNext())
								{
									$arElement['ID'] = intval($arElement['ID']);
									if($productProp["SELECTED"] === false)
										$productProp["SELECTED"] = $arElement["ID"];
									$productProp["VALUES"][$arElement["ID"]] = $arElement["NAME"];
									$emptyValues = false;
								}
								if (!$emptyValues)
								{
									$cache[$prop['ID']] = $productProp;
								}
							}
						}
					}
					else
					{
						$rsElements = CIBlockElement::GetList(
							array(),
							array('ID' => $prop["VALUE"], 'ACTIVE' => 'Y'),
							false,
							false,
							array('ID', 'NAME')
						);
						if ($arElement = $rsElements->GetNext())
						{
							$arElement['ID'] = intval($arElement['ID']);
							$productProp['VALUES'][$arElement['ID']] = $arElement['NAME'];
							$productProp['SELECTED'] = $arElement['ID'];
							$productProp['SET'] = true;
							$emptyValues = false;
						}
					}
					break;
				}
			}

			if (!$emptyValues)
			{
				$result[$pid] = $productProp;
			}
		}

		return $result;
	}

	public static function getFillProductProperties($productProps)
	{
		$result = array();
		if (!empty($productProps) && is_array($productProps))
		{
			foreach ($productProps as $propID => $propInfo)
			{
				if (isset($propInfo['SET']) && $propInfo['SET'])
				{
					$result[$propID] = array(
						'ID' => $propInfo['SELECTED'],
						'VALUE' => $propInfo['VALUES'][$propInfo['SELECTED']]
					);
				}
			}
		}
		return $result;
	}

	/*
	Checks arPropertiesValues against DB values
	returns array on success
	or number on fail (may be used for debug)
	*/
	public static function CheckProductProperties($iblockID, $elementID, $propertiesList, $propertiesValues, $enablePartialList = false)
	{
		$propertyTypeSupport = array(
			'Y' => array(
				'N' => true,
				'S' => true,
				'L' => true,
				'G' => true,
				'E' => true
			),
			'N' => array(
				'L' => true,
				'E' => true
			)
		);
		$iblockID = intval($iblockID);
		$elementID = intval($elementID);
		if (0 >= $iblockID || 0 >= $elementID)
			return 6;
		$enablePartialList = (true === $enablePartialList);
		$sortIndex = 1;
		$result = array();
		if (!is_array($propertiesList))
			$propertiesList = array();
		if (empty($propertiesList))
			return $result;
		$checkProps = array_fill_keys($propertiesList, true);
		$propCodes = $checkProps;
		$existProps =  array();
		$rsProps = CIBlockElement::GetProperty($iblockID, $elementID, 'sort', 'asc', array());
		while ($oneProp = $rsProps->Fetch())
		{
			if (!isset($propCodes[$oneProp['CODE']]) && !isset($propCodes[$oneProp['ID']]))
				continue;
			$propID = (isset($propCodes[$oneProp['CODE']]) ? $oneProp['CODE'] : $oneProp['ID']);
			if (!isset($checkProps[$propID]))
				continue;

			if (!isset($propertyTypeSupport[$oneProp['MULTIPLE']][$oneProp['PROPERTY_TYPE']]))
			{
				return ($oneProp['MULTIPLE'] == 'Y' ? 2 : 3);
			}

			if (null !== $oneProp['VALUE'])
			{
				$existProps[$propID] = true;
			}

			if (!isset($propertiesValues[$propID]))
			{
				if ($enablePartialList)
				{
					continue;
				}
				return 1;
			}

			if (!is_scalar($propertiesValues[$propID]))
					return 5;

			$propertiesValues[$propID] = (string)$propertiesValues[$propID];
			$existValue = ('' != $propertiesValues[$propID]);
			if (!$existValue)
				return 1;

			$userTypeProp = false;
			$userType = null;
			if (isset($oneProp['USER_TYPE']) && !empty($oneProp['USER_TYPE']))
			{
				$userTypeDescr = CIBlockProperty::GetUserType($oneProp['USER_TYPE']);
				if (isset($userTypeDescr['GetPublicViewHTML']))
				{
					$userTypeProp = true;
					$userType = $userTypeDescr['GetPublicViewHTML'];
				}
			}

			if ($oneProp["MULTIPLE"] == "Y")
			{
				if ($userTypeProp)
				{
					if ($oneProp["VALUE"] == $propertiesValues[$propID])
					{
						$displayValue = (string)call_user_func_array($userType,
							array(
								$oneProp,
								array('VALUE' => $oneProp['VALUE']),
								array('MODE' => 'SIMPLE_TEXT')
							));
						$result[] = array(
							"NAME" => $oneProp["NAME"],
							"CODE" => $propID,
							"VALUE" => $displayValue,
							"SORT" => $sortIndex++,
						);
						unset($checkProps[$propID]);//mark as found
					}
				}
				else
				{
					switch($oneProp["PROPERTY_TYPE"])
					{
					case "S":
					case "N":
						if ($oneProp["VALUE"] == $propertiesValues[$propID])
						{
							$result[] = array(
								"NAME" => $oneProp["NAME"],
								"CODE" => $propID,
								"VALUE" => $oneProp["VALUE"],
								"SORT" => $sortIndex++,
							);
							unset($checkProps[$propID]);//mark as found
						}
						break;
					case "G":
						if ($oneProp["VALUE"] == $propertiesValues[$propID])
						{
							$rsSection = CIBlockSection::GetList(
								array(),
								array("=ID" => $oneProp["VALUE"]),
								false,
								array('ID', 'NAME')
							);
							if($arSection = $rsSection->Fetch())
							{
								$result[] = array(
									"NAME" => $oneProp["NAME"],
									"CODE" => $propID,
									"VALUE" => $arSection["NAME"],
									"SORT" => $sortIndex++,
								);
								unset($checkProps[$propID]);//mark as found
							}
						}
						break;
					case "E":
						if ($oneProp["VALUE"] == $propertiesValues[$propID])
						{
							$rsElement = CIBlockElement::GetList(
								array(),
								array("=ID" => $oneProp["VALUE"]),
								false,
								false,
								array("ID", "NAME")
							);
							if ($arElement = $rsElement->Fetch())
							{
								$result[] = array(
									"NAME" => $oneProp["NAME"],
									"CODE" => $propID,
									"VALUE" => $arElement["NAME"],
									"SORT" => $sortIndex++,
								);
								unset($checkProps[$propID]);//mark as found
							}
						}
						break;
					case "L":
						if ($oneProp["VALUE"] == $propertiesValues[$propID])
						{
							$rsEnum = CIBlockPropertyEnum::GetList(
								array(),
								array( "ID" => $propertiesValues[$propID], "IBLOCK_ID" => $iblockID, "PROPERTY_ID" => $oneProp['ID'])
							);
							if ($arEnum = $rsEnum->Fetch())
							{
								$result[] = array(
									"NAME" => $oneProp["NAME"],
									"CODE" => $propID,
									"VALUE" => $arEnum["VALUE"],
									"SORT" => $sortIndex++,
								);
								unset($checkProps[$propID]);//mark as found
							}
						}
						break;
					}
				}
			}
			else
			{
				switch ($oneProp["PROPERTY_TYPE"])
				{
				case "L":
					if (0 < intval($propertiesValues[$propID]))
					{
						$rsEnum = CIBlockPropertyEnum::GetList(
							array(),
							array("ID" => $propertiesValues[$propID], "IBLOCK_ID" => $iblockID, "PROPERTY_ID" => $oneProp['ID'])
						);
						if ($arEnum = $rsEnum->Fetch())
						{
							$result[] = array(
								"NAME" => $oneProp["NAME"],
								"CODE" => $propID,
								"VALUE" => $arEnum["VALUE"],
								"SORT" => $sortIndex++,
							);
							unset($checkProps[$propID]);//mark as found
						}
					}
					break;
				case "E":
					if (0 < intval($propertiesValues[$propID]))
					{
						$rsElement = CIBlockElement::GetList(
							array(),
							array("=ID" => $propertiesValues[$propID]),
							false,
							false,
							array("ID", "NAME")
						);
						if ($arElement = $rsElement->Fetch())
						{
							$result[] = array(
								"NAME" => $oneProp["NAME"],
								"CODE" => $propID,
								"VALUE" => $arElement["NAME"],
								"SORT" => $sortIndex++,
							);
							unset($checkProps[$propID]);//mark as found
						}
					}
					break;
				}
			}
		}

		if ($enablePartialList && !empty($checkProps))
		{
			$nonExistProps = array_keys($checkProps);
			foreach ($nonExistProps as &$oneCode)
			{
				if (!isset($existProps[$oneCode]))
					unset($checkProps[$oneCode]);
			}
			unset($oneCode);
		}

		if(!empty($checkProps))
			return 4;

		return $result;
	}

	public static function GetOffersIBlock($IBLOCK_ID)
	{
		$arResult = false;
		$IBLOCK_ID = intval($IBLOCK_ID);
		if (0 < $IBLOCK_ID)
		{
			if (self::$catalogIncluded === null)
				self::$catalogIncluded = \Bitrix\Main\Loader::includeModule('catalog');
			if (self::$catalogIncluded)
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

	public static function GetOfferProperties($offerID, $iblockID, $propertiesList, $skuTreeProps = '')
	{
		$iblockInfo = false;
		$result = array();

		$iblockID = intval($iblockID);
		$offerID = intval($offerID);
		if (0 >= $iblockID || 0 >= $offerID)
			return $result;

		$skuPropsList = array();
		if (!empty($skuTreeProps))
		{
			if (is_array($skuTreeProps))
			{
				$skuPropsList = $skuTreeProps;
			}
			else
			{
				$skuTreeProps = base64_decode((string)$skuTreeProps);
				if (false !== $skuTreeProps && CheckSerializedData($skuTreeProps))
				{
					$skuPropsList = unserialize($skuTreeProps);
					if (!is_array($skuPropsList))
					{
						$skuPropsList = array();
					}
				}
			}
		}

		if (!is_array($propertiesList))
		{
			$propertiesList = array();
		}
		if (!empty($skuPropsList))
		{
			$propertiesList = array_unique(array_merge($propertiesList, $skuPropsList));
		}
		if (empty($propertiesList))
			return $result;
		$propCodes = array_fill_keys($propertiesList, true);

		if (self::$catalogIncluded === null)
			self::$catalogIncluded = \Bitrix\Main\Loader::includeModule('catalog');
		if (self::$catalogIncluded)
		{
			$iblockInfo = CCatalogSKU::GetInfoByProductIBlock($iblockID);
		}
		if (empty($iblockInfo))
			return $result;

		$sortIndex = 1;
		$rsProps = CIBlockElement::GetProperty(
			$iblockInfo['IBLOCK_ID'],
			$offerID,
			array("sort"=>"asc", "enum_sort" => "asc", "value_id"=>"asc"),
			array("EMPTY"=>"N")
		);

		while ($oneProp = $rsProps->Fetch())
		{
			if (!isset($propCodes[$oneProp['CODE']]) && !isset($propCodes[$oneProp['ID']]))
				continue;
			$propID = (isset($propCodes[$oneProp['CODE']]) ? $oneProp['CODE'] : $oneProp['ID']);

			$userTypeProp = false;
			$userType = null;
			if (isset($oneProp['USER_TYPE']) && !empty($oneProp['USER_TYPE']))
			{
				$userTypeDescr = CIBlockProperty::GetUserType($oneProp['USER_TYPE']);
				if (isset($userTypeDescr['GetPublicViewHTML']))
				{
					$userTypeProp = true;
					$userType = $userTypeDescr['GetPublicViewHTML'];
				}
			}

			if ($userTypeProp)
			{
				$displayValue = (string)call_user_func_array($userType,
					array(
						$oneProp,
						array('VALUE' => $oneProp['VALUE']),
						array('MODE' => 'SIMPLE_TEXT')
					));
				$result[] = array(
					"NAME" => $oneProp["NAME"],
					"CODE" => $propID,
					"VALUE" => $displayValue,
					"SORT" => $sortIndex++,
				);
			}
			else
			{
				switch ($oneProp["PROPERTY_TYPE"])
				{
				case "S":
				case "N":
					$result[] = array(
						"NAME" => $oneProp["NAME"],
						"CODE" => $propID,
						"VALUE" => $oneProp["VALUE"],
						"SORT" => $sortIndex++,
					);
					break;
				case "G":
					$rsSection = CIBlockSection::GetList(
						array(),
						array("=ID"=>$oneProp["VALUE"]),
						false,
						array('ID', 'NAME')
					);
					if ($arSection = $rsSection->Fetch())
					{
						$result[] = array(
							"NAME" => $oneProp["NAME"],
							"CODE" => $propID,
							"VALUE" => $arSection["NAME"],
							"SORT" => $sortIndex++,
						);
					}
					break;
				case "E":
					$rsElement = CIBlockElement::GetList(
						array(),
						array("=ID"=>$oneProp["VALUE"]),
						false,
						false,
						array("ID", "NAME")
					);
					if ($arElement = $rsElement->Fetch())
					{
						$result[] = array(
							"NAME" => $oneProp["NAME"],
							"CODE" => $propID,
							"VALUE" => $arElement["NAME"],
							"SORT" => $sortIndex++,
						);
					}
					break;
				case "L":
					$result[] = array(
						"NAME" => $oneProp["NAME"],
						"CODE" => $propID,
						"VALUE" => $oneProp["VALUE_ENUM"],
						"SORT" => $sortIndex++,
					);
					break;
				}
			}
		}
		return $result;
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

		$boolCheckPermissions = false;
		$boolHideNotAvailable = false;
		$IBLOCK_ID = 0;
		if (!empty($arFilter) && is_array($arFilter))
		{
			if (isset($arFilter['IBLOCK_ID']))
				$IBLOCK_ID = $arFilter['IBLOCK_ID'];
			if (isset($arFilter['HIDE_NOT_AVAILABLE']))
				$boolHideNotAvailable = 'Y' === $arFilter['HIDE_NOT_AVAILABLE'];
			if (isset($arFilter['CHECK_PERMISSIONS']))
				$boolCheckPermissions = 'Y' === $arFilter['CHECK_PERMISSIONS'];
		}
		else
		{
			$IBLOCK_ID = $arFilter;
		}

		$arOffersIBlock = CIBlockPriceTools::GetOffersIBlock($IBLOCK_ID);
		if($arOffersIBlock)
		{
			$arDefaultMeasure = CCatalogMeasure::getDefaultMeasure(true, true);

			$limit = intval($limit);
			if (0 > $limit)
				$limit = 0;

			if(!isset($arOrder["ID"]))
				$arOrder["ID"] = "DESC";

			$intOfferIBlockID = $arOffersIBlock["OFFERS_IBLOCK_ID"];

			$arFilter = array(
				"IBLOCK_ID" => $intOfferIBlockID,
				"PROPERTY_".$arOffersIBlock["OFFERS_PROPERTY_ID"] => $arElementID,
				"ACTIVE" => "Y",
				"ACTIVE_DATE" => "Y",
			);
			if ($boolHideNotAvailable)
				$arFilter['CATALOG_AVAILABLE'] = 'Y';
			if ($boolCheckPermissions)
			{
				$arFilter['CHECK_PERMISSIONS'] = "Y";
				$arFilter['MIN_PERMISSION'] = "R";
			}

			$arSelect = array(
				"ID" => 1,
				"IBLOCK_ID" => 1,
				"PROPERTY_".$arOffersIBlock["OFFERS_PROPERTY_ID"] => 1,
				"CATALOG_QUANTITY" => 1
			);
			//if(!$arParams["USE_PRICE_COUNT"])
			{
				foreach($arPrices as $value)
				{
					if (!$value['CAN_VIEW'] && !$value['CAN_BUY'])
						continue;
					$arSelect[$value["SELECT"]] = 1;
				}
			}

			foreach($arSelectFields as $code)
				$arSelect[$code] = 1; //mark to select
			if (!isset($arSelect['PREVIEW_PICTURE']))
				$arSelect['PREVIEW_PICTURE'] = 1;
			if (!isset($arSelect['DETAIL_PICTURE']))
				$arSelect['DETAIL_PICTURE'] = 1;

			$arOfferIDs = array();
			$arMeasureMap = array();
			$intKey = 0;
			$arOffersPerElement = array();
			$arOffersLink = array();
			$rsOffers = CIBlockElement::GetList($arOrder, $arFilter, false, false, array_keys($arSelect));
			while($arOffer = $rsOffers->GetNext())
			{
				$arOffer['ID'] = intval($arOffer['ID']);
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
					$arOffer["LINK_ELEMENT_ID"] = intval($element_id);
					$arOffer["PROPERTIES"] = array();
					$arOffer["DISPLAY_PROPERTIES"] = array();

					$arOffer['CHECK_QUANTITY'] = ('Y' == $arOffer['CATALOG_QUANTITY_TRACE'] && 'N' == $arOffer['CATALOG_CAN_BUY_ZERO']);
					$arOffer['CATALOG_TYPE'] = CCatalogProduct::TYPE_OFFER;
					$arOffer['CATALOG_MEASURE_NAME'] = $arDefaultMeasure['SYMBOL_RUS'];
					$arOffer['~CATALOG_MEASURE_NAME'] = $arDefaultMeasure['SYMBOL_RUS'];
					$arOffer["CATALOG_MEASURE_RATIO"] = 1;
					if (!isset($arOffer['CATALOG_MEASURE']))
						$arOffer['CATALOG_MEASURE'] = 0;
					$arOffer['CATALOG_MEASURE'] = intval($arOffer['CATALOG_MEASURE']);
					if (0 > $arOffer['CATALOG_MEASURE'])
						$arOffer['CATALOG_MEASURE'] = 0;
					if (0 < $arOffer['CATALOG_MEASURE'])
					{
						if (!isset($arMeasureMap[$arOffer['CATALOG_MEASURE']]))
							$arMeasureMap[$arOffer['CATALOG_MEASURE']] = array();
						$arMeasureMap[$arOffer['CATALOG_MEASURE']][] = $intKey;
					}

					$arOfferIDs[] = $arOffer['ID'];
					$arResult[$intKey] = $arOffer;
					$arOffersLink[$arOffer['ID']] = &$arResult[$intKey];
					$intKey++;
				}
			}
			if (!empty($arOfferIDs))
			{
				$rsRatios = CCatalogMeasureRatio::getList(
					array(),
					array('PRODUCT_ID' => $arOfferIDs),
					false,
					false,
					array('PRODUCT_ID', 'RATIO')
				);
				while ($arRatio = $rsRatios->Fetch())
				{
					$arRatio['PRODUCT_ID'] = intval($arRatio['PRODUCT_ID']);
					if (isset($arOffersLink[$arRatio['PRODUCT_ID']]))
					{
						$intRatio = intval($arRatio['RATIO']);
						$dblRatio = doubleval($arRatio['RATIO']);
						$mxRatio = ($dblRatio > $intRatio ? $dblRatio : $intRatio);
						if (CATALOG_VALUE_EPSILON > abs($mxRatio))
							$mxRatio = 1;
						elseif (0 > $mxRatio)
							$mxRatio = 1;
						$arOffersLink[$arRatio['PRODUCT_ID']]['CATALOG_MEASURE_RATIO'] = $mxRatio;
					}
				}

				if (!empty($arSelectProperties))
				{
					CIBlockElement::GetPropertyValuesArray($arOffersLink, $intOfferIBlockID, $arFilter);
					foreach ($arResult as &$arOffer)
					{
						CCatalogDiscount::SetProductPropertiesCache($arOffer['ID'], $arOffer["PROPERTIES"]);
						foreach ($arSelectProperties as $pid)
						{
							if (!isset($arOffer["PROPERTIES"][$pid]))
								continue;
							$prop = &$arOffer["PROPERTIES"][$pid];
							$boolArr = is_array($prop["VALUE"]);
							if(
								($boolArr && !empty($prop["VALUE"])) ||
								(!$boolArr && strlen($prop["VALUE"])>0))
							{
								$arOffer["DISPLAY_PROPERTIES"][$pid] = CIBlockFormatProperties::GetDisplayValue($arOffer, $prop, "catalog_out");
							}
						}
						if (isset($arOffer))
							unset($arOffer);
					}
				}

				CCatalogDiscount::SetProductSectionsCache($arOfferIDs);
				CCatalogDiscount::SetDiscountProductCache($arOfferIDs, array('IBLOCK_ID' => $intOfferIBlockID, 'GET_BY_ID' => 'Y'));
				foreach ($arResult as &$arOffer)
				{
					$arOffer['CATALOG_QUANTITY'] = (
						0 < $arOffer['CATALOG_QUANTITY'] && is_float($arOffer['CATALOG_MEASURE_RATIO'])
						? floatval($arOffer['CATALOG_QUANTITY'])
						: intval($arOffer['CATALOG_QUANTITY'])
					);
					$arOffer['MIN_PRICE'] = false;
					$arOffer["PRICES"] = CIBlockPriceTools::GetItemPrices($arOffersIBlock["OFFERS_IBLOCK_ID"], $arPrices, $arOffer, $vat_include, $arCurrencyParams, $USER_ID, $LID);
					if (!empty($arOffer["PRICES"]))
					{
						foreach ($arOffer['PRICES'] as &$arOnePrice)
						{
							if ('Y' == $arOnePrice['MIN_PRICE'])
							{
								$arOffer['MIN_PRICE'] = $arOnePrice;
								break;
							}
						}
						unset($arOnePrice);
					}
					$arOffer["CAN_BUY"] = CIBlockPriceTools::CanBuy($arOffersIBlock["OFFERS_IBLOCK_ID"], $arPrices, $arOffer);
				}
				if (isset($arOffer))
					unset($arOffer);
			}
			if (!empty($arMeasureMap))
			{
				$rsMeasures = CCatalogMeasure::getList(
					array(),
					array('@ID' => array_keys($arMeasureMap)),
					false,
					false,
					array('ID', 'SYMBOL_RUS')
				);
				while ($arMeasure = $rsMeasures->GetNext())
				{
					$arMeasure['ID'] = intval($arMeasure['ID']);
					if (isset($arMeasureMap[$arMeasure['ID']]) && !empty($arMeasureMap[$arMeasure['ID']]))
					{
						foreach ($arMeasureMap[$arMeasure['ID']] as &$intOneKey)
						{
							$arResult[$intOneKey]['CATALOG_MEASURE_NAME'] = $arMeasure['SYMBOL_RUS'];
							$arResult[$intOneKey]['~CATALOG_MEASURE_NAME'] = $arMeasure['~SYMBOL_RUS'];
						}
						unset($intOneKey);
					}
				}
			}
		}

		return $arResult;
	}

	public static function GetDefaultMeasure()
	{
		if (self::$catalogIncluded === null)
			self::$catalogIncluded = \Bitrix\Main\Loader::includeModule('catalog');
		return (self::$catalogIncluded ? array() : CCatalogMeasure::getDefaultMeasure(true, true));
	}

	public static function setRatioMinPrice(&$item, $replaceMinPrice = false)
	{
		if (isset($item['MIN_PRICE']) && !empty($item['MIN_PRICE']) && isset($item['CATALOG_MEASURE_RATIO']))
		{
			if (1 === $item['CATALOG_MEASURE_RATIO'])
			{
				$item['RATIO_PRICE'] = array(
					'VALUE' => $item['MIN_PRICE']['VALUE'],
					'DISCOUNT_VALUE' => $item['MIN_PRICE']['DISCOUNT_VALUE'],
					'PRINT_VALUE' => $item['MIN_PRICE']['PRINT_VALUE'],
					'PRINT_DISCOUNT_VALUE' => $item['MIN_PRICE']['PRINT_DISCOUNT_VALUE'],
					'DISCOUNT_DIFF' => $item['MIN_PRICE']['DISCOUNT_DIFF'],
					'PRINT_DISCOUNT_DIFF' => $item['MIN_PRICE']['PRINT_DISCOUNT_DIFF'],
					'DISCOUNT_DIFF_PERCENT' => $item['MIN_PRICE']['DISCOUNT_DIFF_PERCENT'],
					'CURRENCY' => $item['MIN_PRICE']['CURRENCY']
				);
			}
			else
			{
				$item['RATIO_PRICE'] = array(
					'VALUE' => $item['MIN_PRICE']['VALUE']*$item['CATALOG_MEASURE_RATIO'],
					'DISCOUNT_VALUE' => $item['MIN_PRICE']['DISCOUNT_VALUE']*$item['CATALOG_MEASURE_RATIO'],
					'CURRENCY' => $item['MIN_PRICE']['CURRENCY']
				);
				$item['RATIO_PRICE']['PRINT_VALUE'] = CCurrencyLang::CurrencyFormat(
					$item['RATIO_PRICE']['VALUE'],
					$item['RATIO_PRICE']['CURRENCY'],
					true
				);
				$item['RATIO_PRICE']['PRINT_DISCOUNT_VALUE'] = CCurrencyLang::CurrencyFormat(
					$item['RATIO_PRICE']['DISCOUNT_VALUE'],
					$item['RATIO_PRICE']['CURRENCY'],
					true
				);
				if ($item['MIN_PRICE']['VALUE'] == $item['MIN_PRICE']['DISCOUNT_VALUE'])
				{
					$item['RATIO_PRICE']['DISCOUNT_DIFF'] = 0;
					$item['RATIO_PRICE']['DISCOUNT_DIFF_PERCENT'] = 0;
					$item['RATIO_PRICE']['PRINT_DISCOUNT_DIFF'] = CCurrencyLang::CurrencyFormat(0, $item['RATIO_PRICE']['CURRENCY'], true);
				}
				else
				{
					$item['RATIO_PRICE']['DISCOUNT_DIFF'] = $item['RATIO_PRICE']['VALUE'] - $item['RATIO_PRICE']['DISCOUNT_VALUE'];
					$item['RATIO_PRICE']['DISCOUNT_DIFF_PERCENT'] = 100*$item['RATIO_PRICE']['DISCOUNT_DIFF']/$item['RATIO_PRICE']['VALUE'];
					$item['RATIO_PRICE']['PRINT_DISCOUNT_DIFF'] = CCurrencyLang::CurrencyFormat(
						$item['RATIO_PRICE']['DISCOUNT_DIFF'],
						$item['RATIO_PRICE']['CURRENCY'],
						true
					);
				}
			}
			if ($replaceMinPrice)
			{
				$item['MIN_PRICE'] = $item['RATIO_PRICE'];
				unset($item['RATIO_PRICE']);
			}
		}
	}

	public static function checkPropDirectory(&$property, $getPropInfo = false)
	{
		if (empty($property))
			return false;
		if (!is_array($property))
			return false;
		if (!isset($property['USER_TYPE_SETTINGS']['TABLE_NAME']) || empty($property['USER_TYPE_SETTINGS']['TABLE_NAME']))
			return false;
		if (null === self::$highLoadInclude)
			self::$highLoadInclude = \Bitrix\Main\Loader::includeModule('highloadblock');
		if (!self::$highLoadInclude)
			return false;

		$highBlock = \Bitrix\Highloadblock\HighloadBlockTable::getList(array("filter" => array('TABLE_NAME' => $property['USER_TYPE_SETTINGS']['TABLE_NAME'])))->fetch();
		if (!isset($highBlock['ID']))
			return false;

		$entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($highBlock);
		$entityDataClass = $entity->getDataClass();
		$fieldsList = $entityDataClass::getMap();
		if (empty($fieldsList))
			return false;
		$requireFields = array(
			'ID',
			'UF_XML_ID',
			'UF_NAME',
		);
		foreach ($requireFields as &$fieldCode)
		{
			if (!isset($fieldsList[$fieldCode]) || empty($fieldsList[$fieldCode]))
				return;
		}
		unset($fieldCode);
		if ($getPropInfo)
		{
			$property['USER_TYPE_SETTINGS']['FIELDS_MAP'] = $fieldsList;
			$propInfo['USER_TYPE_SETTINGS']['ENTITY'] = $entity;
		}
		return true;
	}

	public static function getTreeProperties($skuInfo, $propertiesCodes, $defaultFields = array())
	{
		$requireFields = array(
			'ID',
			'UF_XML_ID',
			'UF_NAME',
		);

		$result = array();
		if (empty($skuInfo))
			return $result;
		if (!is_array($skuInfo))
		{
			$skuInfo = intval($skuInfo);
			if ($skuInfo <= 0)
				return $result;
			if (self::$catalogIncluded === null)
				self::$catalogIncluded = \Bitrix\Main\Loader::includeModule('catalog');
			if (!self::$catalogIncluded)
				return $result;
			$skuInfo = CCatalogSKU::GetInfoByProductIBlock($skuInfo);
			if (empty($skuInfo) || !is_array($skuInfo))
				return $result;
		}
		if (empty($propertiesCodes) || !is_array($propertiesCodes))
			return $result;

		$showMode = '';

		$props = CIBlockProperty::GetList(
			array('SORT' => 'ASC', 'ID' => 'ASC'),
			array('IBLOCK_ID' => $skuInfo['IBLOCK_ID'], 'ACTIVE' => 'Y', 'MULTIPLE' => 'N')
		);
		while ($propInfo = $props->Fetch())
		{
			$propInfo['ID'] = intval($propInfo['ID']);
			if ($propInfo['ID'] == $skuInfo['SKU_PROPERTY_ID'])
				continue;
			if ('' == $propInfo['CODE'])
				$propInfo['CODE'] = $propInfo['ID'];
			if (!in_array($propInfo['CODE'], $propertiesCodes))
				continue;
			$propInfo['USER_TYPE'] = (string)$propInfo['USER_TYPE'];
			if ('L' != $propInfo['PROPERTY_TYPE'] && 'E' != $propInfo['PROPERTY_TYPE'] && !('S' == $propInfo['PROPERTY_TYPE'] && 'directory' == $propInfo['USER_TYPE']))
				continue;
			if ('S' == $propInfo['PROPERTY_TYPE'] && 'directory' == $propInfo['USER_TYPE'])
			{
				if (!isset($propInfo['USER_TYPE_SETTINGS']['TABLE_NAME']) || empty($propInfo['USER_TYPE_SETTINGS']['TABLE_NAME']))
					continue;
				if (null === self::$highLoadInclude)
					self::$highLoadInclude = \Bitrix\Main\Loader::includeModule('highloadblock');
				if (!self::$highLoadInclude)
					continue;

				$highBlock = \Bitrix\Highloadblock\HighloadBlockTable::getList(array("filter" => array('TABLE_NAME' => $propInfo['USER_TYPE_SETTINGS']['TABLE_NAME'])))->fetch();
				if (!isset($highBlock['ID']))
					continue;

				$entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($highBlock);
				$entityDataClass = $entity->getDataClass();
				$fieldsList = $entityDataClass::getMap();
				if (empty($fieldsList))
					continue;

				$flag = true;
				foreach ($requireFields as &$fieldCode)
				{
					if (!isset($fieldsList[$fieldCode]) || empty($fieldsList[$fieldCode]))
					{
						$flag = false;
						break;
					}
				}
				unset($fieldCode);
				if (!$flag)
					continue;
				$propInfo['USER_TYPE_SETTINGS']['FIELDS_MAP'] = $fieldsList;
				$propInfo['USER_TYPE_SETTINGS']['ENTITY'] = $entity;
			}
			switch ($propInfo['PROPERTY_TYPE'])
			{
				case 'E':
					$showMode = 'PICT';
					break;
				case 'L':
					$showMode = 'TEXT';
					break;
				case 'S':
					$showMode = (isset($fieldsList['UF_FILE']) ? 'PICT' : 'TEXT');
					break;
			}
			$treeProp = array(
				'ID' => intval($propInfo['ID']),
				'CODE' => $propInfo['CODE'],
				'NAME' => $propInfo['NAME'],
				'SORT' => intval($propInfo['SORT']),
				'PROPERTY_TYPE' => $propInfo['PROPERTY_TYPE'],
				'USER_TYPE' => $propInfo['USER_TYPE'],
				'LINK_IBLOCK_ID' => $propInfo['LINK_IBLOCK_ID'],
				'USER_TYPE_SETTINGS' => $propInfo['USER_TYPE_SETTINGS'],
				'VALUES' => array(),
				'SHOW_MODE' => $showMode,
				'DEFAULT_VALUES' => array(
					'PICT' => false,
					'NAME' => '-'
				)
			);
			if ('PICT' == $showMode)
			{
				if (isset($defaultFields['PICT']))
				{
					$treeProp['DEFAULT_VALUES']['PICT'] = $defaultFields['PICT'];
				}
			}
			if (isset($defaultFields['NAME']))
			{
				$treeProp['DEFAULT_VALUES']['NAME'] = $defaultFields['NAME'];
			}
			$result[$treeProp['CODE']] = $treeProp;
		}
		return $result;
	}

	public static function getTreePropertyValues(&$propList, &$propNeedValues)
	{
		$result = array();
		if (!empty($propList) && is_array($propList))
		{
			foreach ($propList as $oneProperty)
			{
				$values = array();
				$valuesExist = false;
				$pictMode = ('PICT' == $oneProperty['SHOW_MODE']);
				$needValuesExist = isset($propNeedValues[$oneProperty['ID']]) && !empty($propNeedValues[$oneProperty['ID']]);
				$filterValuesExist = ($needValuesExist && count($propNeedValues[$oneProperty['ID']]) <= 100);
				if ('L' == $oneProperty['PROPERTY_TYPE'])
				{
					$propEnums = CIBlockProperty::GetPropertyEnum(
						$oneProperty['ID'],
						array('SORT' => 'ASC', 'VALUE' => 'ASC')
					);
					while ($oneEnum = $propEnums->Fetch())
					{
						$oneEnum['ID'] = intval($oneEnum['ID']);
						if ($needValuesExist && !isset($propNeedValues[$oneProperty['ID']][$oneEnum['ID']]))
							continue;
						$values[$oneEnum['ID']] = array(
							'ID' => $oneEnum['ID'],
							'NAME' => $oneEnum['VALUE'],
							'SORT' => intval($oneEnum['SORT']),
							'PICT' => false
						);
						$valuesExist = true;
					}
					$values[0] = array(
						'ID' => 0,
						'SORT' => PHP_INT_MAX,
						'NA' => true,
						'NAME' => $oneProperty['DEFAULT_VALUES']['NAME'],
						'PICT' => $oneProperty['DEFAULT_VALUES']['PICT']
					);
				}
				elseif ('E' == $oneProperty['PROPERTY_TYPE'])
				{
					$selectFields = array('ID', 'NAME');
					if ($pictMode)
						$selectFields[] = 'PREVIEW_PICTURE';
					$filterValues = (
						$filterValuesExist
						? array('ID' => $propNeedValues[$oneProperty['ID']], 'IBLOCK_ID' => $oneProperty['LINK_IBLOCK_ID'], 'ACTIVE' => 'Y')
						: array('IBLOCK_ID' => $oneProperty['LINK_IBLOCK_ID'], 'ACTIVE' => 'Y')
					);
					$propEnums = CIBlockElement::GetList(
						array('SORT' => 'ASC', 'NAME' => 'ASC'),
						$filterValues,
						false,
						false,
						$selectFields
					);
					while ($oneEnum = $propEnums->Fetch())
					{
						if ($needValuesExist && !$filterValuesExist)
						{
							if (!isset($propNeedValues[$oneProperty['ID']][$oneEnum['ID']]))
								continue;
						}
						if ($pictMode)
						{
							$oneEnum['PICT'] = false;
							if (!empty($oneEnum['PREVIEW_PICTURE']))
							{
								$previewPict = CFile::GetFileArray($oneEnum['PREVIEW_PICTURE']);
								if (!empty($previewPict))
								{
									$oneEnum['PICT'] = array(
										'SRC' => $previewPict['SRC'],
										'WIDTH' => intval($previewPict['WIDTH']),
										'HEIGHT' => intval($previewPict['HEIGHT'])
									);
								}
							}
							if (empty($oneEnum['PICT']))
							{
								$oneEnum['PICT'] = $oneProperty['DEFAULT_VALUES']['PICT'];
							}
						}
						$oneEnum['ID'] = intval($oneEnum['ID']);
						$values[$oneEnum['ID']] = array(
							'ID' => $oneEnum['ID'],
							'NAME' => $oneEnum['NAME'],
							'SORT' => intval($oneEnum['SORT']),
							'PICT' => ($pictMode ? $oneEnum['PICT'] : false)
						);
						$valuesExist = true;
					}
					$values[0] = array(
						'ID' => 0,
						'SORT' => PHP_INT_MAX,
						'NA' => true,
						'NAME' => $oneProperty['DEFAULT_VALUES']['NAME'],
						'PICT' => ($pictMode ? $oneProperty['DEFAULT_VALUES']['PICT'] : false)
					);
				}
				else
				{
					if (null === self::$highLoadInclude)
						self::$highLoadInclude = \Bitrix\Main\Loader::includeModule('highloadblock');
					if (!self::$highLoadInclude)
						continue;
					$xmlMap = array();
					$sortExist = isset($oneProperty['USER_TYPE_SETTINGS']['FIELDS_MAP']['UF_SORT']);

					$directorySelect = array('ID', 'UF_NAME', 'UF_XML_ID');
					$directoryOrder = array();
					if ($pictMode)
					{
						$directorySelect[] = 'UF_FILE';
					}
					if ($sortExist)
					{
						$directorySelect[] = 'UF_SORT';
						$directoryOrder['UF_SORT'] = 'ASC';
					}
					$directoryOrder['UF_NAME'] = 'ASC';
					$sortValue = 100;

					$entityDataClass = $oneProperty['USER_TYPE_SETTINGS']['ENTITY']->getDataClass();
					$entityGetList = array(
						'select' => $directorySelect,
						'order' => $directoryOrder
					);
					if ($filterValuesExist)
						$entityGetList['filter'] = array('=UF_XML_ID' => $propNeedValues[$oneProperty['ID']]);
					$propEnums = $entityDataClass::getList($entityGetList);
					while ($oneEnum = $propEnums->fetch())
					{
						$oneEnum['ID'] = intval($oneEnum['ID']);
						$oneEnum['UF_SORT'] = ($sortExist ? intval($oneEnum['UF_SORT']) : $sortValue);
						$sortValue += 100;

						if ($pictMode)
						{
							if (!empty($oneEnum['UF_FILE']))
							{
								$arFile = CFile::GetFileArray($oneEnum['UF_FILE']);
								if (!empty($arFile))
								{
									$oneEnum['PICT'] = array(
										'SRC' => $arFile['SRC'],
										'WIDTH' => intval($arFile['WIDTH']),
										'HEIGHT' => intval($arFile['HEIGHT'])
									);
								}
							}
							if (empty($oneEnum['PICT']))
								$oneEnum['PICT'] = $oneProperty['DEFAULT_VALUES']['PICT'];
						}
						$values[$oneEnum['ID']] = array(
							'ID' => $oneEnum['ID'],
							'NAME' => $oneEnum['UF_NAME'],
							'SORT' => intval($oneEnum['UF_SORT']),
							'XML_ID' => $oneEnum['UF_XML_ID'],
							'PICT' => ($pictMode ? $oneEnum['PICT'] : false)
						);
						$valuesExist = true;
						$xmlMap[$oneEnum['UF_XML_ID']] = $oneEnum['ID'];
					}
					$values[0] = array(
						'ID' => 0,
						'SORT' => PHP_INT_MAX,
						'NA' => true,
						'NAME' => $oneProperty['DEFAULT_VALUES']['NAME'],
						'XML_ID' => '',
						'PICT' => ($pictMode ? $oneProperty['DEFAULT_VALUES']['PICT'] : false)
					);
					if ($valuesExist)
						$oneProperty['XML_MAP'] = $xmlMap;
				}
				if (!$valuesExist)
					continue;
				$oneProperty['VALUES'] = $values;
				$oneProperty['VALUES_COUNT'] = count($values);

				$result[$oneProperty['CODE']] = $oneProperty;
			}
		}
		$propList = $result;
		unset($arFilterProp);
	}

	public static function getMinPriceFromOffers(&$offers, $currency)
	{
		$result = false;
		$minPrice = 0;
		if (!empty($offers) && is_array($offers))
		{
			$doubles = array();
			foreach ($offers as $oneOffer)
			{
				$oneOffer['ID'] = intval($oneOffer['ID']);
				if (isset($doubles[$oneOffer['ID']]))
					continue;
				if (!$oneOffer['CAN_BUY'])
					continue;

				CIBlockPriceTools::setRatioMinPrice($oneOffer, true);

				$oneOffer['MIN_PRICE']['CATALOG_MEASURE_RATIO'] = $oneOffer['CATALOG_MEASURE_RATIO'];
				$oneOffer['MIN_PRICE']['CATALOG_MEASURE'] = $oneOffer['CATALOG_MEASURE'];
				$oneOffer['MIN_PRICE']['CATALOG_MEASURE_NAME'] = $oneOffer['CATALOG_MEASURE_NAME'];
				$oneOffer['MIN_PRICE']['~CATALOG_MEASURE_NAME'] = $oneOffer['~CATALOG_MEASURE_NAME'];

				if (empty($result))
				{
					$minPrice = ($oneOffer['MIN_PRICE']['CURRENCY'] == $currency
						? $oneOffer['MIN_PRICE']['DISCOUNT_VALUE']
						: CCurrencyRates::ConvertCurrency($oneOffer['MIN_PRICE']['DISCOUNT_VALUE'], $oneOffer['MIN_PRICE']['CURRENCY'], $currency)
					);
					$result = $oneOffer['MIN_PRICE'];
				}
				else
				{
					$comparePrice = ($oneOffer['MIN_PRICE']['CURRENCY'] == $currency
						? $oneOffer['MIN_PRICE']['DISCOUNT_VALUE']
						: CCurrencyRates::ConvertCurrency($oneOffer['MIN_PRICE']['DISCOUNT_VALUE'], $oneOffer['MIN_PRICE']['CURRENCY'], $currency)
					);
					if ($minPrice > $comparePrice)
					{
						$minPrice = $comparePrice;
						$result = $oneOffer['MIN_PRICE'];
					}
				}
				$doubles[$oneOffer['ID']] = true;
			}
		}
		return $result;
	}

	public static function getDoublePicturesForItem(&$item, $propertyCode)
	{
		$result = array(
			'PICT' => false,
			'SECOND_PICT' => false
		);

		if (!empty($item) && is_array($item))
		{
			if (!empty($item['PREVIEW_PICTURE']))
			{
				if (!is_array($item['PREVIEW_PICTURE']))
					$item['PREVIEW_PICTURE'] = CFile::GetFileArray($item['PREVIEW_PICTURE']);
				if (isset($item['PREVIEW_PICTURE']['ID']))
				{
					$result['PICT'] = array(
						'ID' => intval($item['PREVIEW_PICTURE']['ID']),
						'SRC' => $item['PREVIEW_PICTURE']['SRC'],
						'WIDTH' => intval($item['PREVIEW_PICTURE']['WIDTH']),
						'HEIGHT' => intval($item['PREVIEW_PICTURE']['HEIGHT'])
					);
				}
			}
			if (!empty($item['DETAIL_PICTURE']))
			{
				$keyPict = (empty($result['PICT']) ? 'PICT' : 'SECOND_PICT');
				if (!is_array($item['DETAIL_PICTURE']))
					$item['DETAIL_PICTURE'] = CFile::GetFileArray($item['DETAIL_PICTURE']);
				if (isset($item['DETAIL_PICTURE']['ID']))
				{
					$result[$keyPict] = array(
						'ID' => intval($item['DETAIL_PICTURE']['ID']),
						'SRC' => $item['DETAIL_PICTURE']['SRC'],
						'WIDTH' => intval($item['DETAIL_PICTURE']['WIDTH']),
						'HEIGHT' => intval($item['DETAIL_PICTURE']['HEIGHT'])
					);
				}
			}
			if (empty($result['SECOND_PICT']))
			{
				if (
					'' != $propertyCode &&
					isset($item['PROPERTIES'][$propertyCode]) &&
					'F' == $item['PROPERTIES'][$propertyCode]['PROPERTY_TYPE']
				)
				{
					if (
						isset($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']) &&
						!empty($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE'])
					)
					{
						$fileValues = (
						isset($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']['ID']) ?
							array(0 => $item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']) :
							$item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']
						);
						foreach ($fileValues as &$oneFileValue)
						{
							$keyPict = (empty($result['PICT']) ? 'PICT' : 'SECOND_PICT');
							$result[$keyPict] = array(
								'ID' => intval($oneFileValue['ID']),
								'SRC' => $oneFileValue['SRC'],
								'WIDTH' => intval($oneFileValue['WIDTH']),
								'HEIGHT' => intval($oneFileValue['HEIGHT'])
							);
							if ('SECOND_PICT' == $keyPict)
								break;
						}
						if (isset($oneFileValue))
							unset($oneFileValue);
					}
					else
					{
						$propValues = $item['PROPERTIES'][$propertyCode]['VALUE'];
						if (!is_array($propValues))
							$propValues = array($propValues);
						foreach ($propValues as &$oneValue)
						{
							$oneFileValue = CFile::GetFileArray($oneValue);
							if (isset($oneFileValue['ID']))
							{
								$keyPict = (empty($result['PICT']) ? 'PICT' : 'SECOND_PICT');
								$result[$keyPict] = array(
									'ID' => intval($oneFileValue['ID']),
									'SRC' => $oneFileValue['SRC'],
									'WIDTH' => intval($oneFileValue['WIDTH']),
									'HEIGHT' => intval($oneFileValue['HEIGHT'])
								);
								if ('SECOND_PICT' == $keyPict)
									break;
							}
						}
						if (isset($oneValue))
							unset($oneValue);
					}
				}
			}
		}
		return $result;
	}

	public static function getSliderForItem(&$item, $propertyCode, $addDetailToSlider)
	{
		$result = array();

		if (!empty($item) && is_array($item))
		{
			if (
				'' != $propertyCode &&
				isset($item['PROPERTIES'][$propertyCode]) &&
				'F' == $item['PROPERTIES'][$propertyCode]['PROPERTY_TYPE']
			)
			{
				if ('MORE_PHOTO' == $propertyCode && isset($item['MORE_PHOTO']) && !empty($item['MORE_PHOTO']))
				{
					foreach ($item['MORE_PHOTO'] as &$onePhoto)
					{
						$result[] = array(
							'ID' => intval($onePhoto['ID']),
							'SRC' => $onePhoto['SRC'],
							'WIDTH' => intval($onePhoto['WIDTH']),
							'HEIGHT' => intval($onePhoto['HEIGHT'])
						);
					}
					unset($onePhoto);
				}
				else
				{
					if (
						isset($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']) &&
						!empty($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE'])
					)
					{
						$fileValues = (
						isset($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']['ID']) ?
							array(0 => $item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']) :
							$item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']
						);
						foreach ($fileValues as &$oneFileValue)
						{
							$result[] = array(
								'ID' => intval($oneFileValue['ID']),
								'SRC' => $oneFileValue['SRC'],
								'WIDTH' => intval($oneFileValue['WIDTH']),
								'HEIGHT' => intval($oneFileValue['HEIGHT'])
							);
						}
						if (isset($oneFileValue))
							unset($oneFileValue);
					}
					else
					{
						$propValues = $item['PROPERTIES'][$propertyCode]['VALUE'];
						if (!is_array($propValues))
							$propValues = array($propValues);

						foreach ($propValues as &$oneValue)
						{
							$oneFileValue = CFile::GetFileArray($oneValue);
							if (isset($oneFileValue['ID']))
							{
								$result[] = array(
									'ID' => intval($oneFileValue['ID']),
									'SRC' => $oneFileValue['SRC'],
									'WIDTH' => intval($oneFileValue['WIDTH']),
									'HEIGHT' => intval($oneFileValue['HEIGHT'])
								);
							}
						}
						if (isset($oneValue))
							unset($oneValue);
					}
				}
			}
			if ($addDetailToSlider || empty($result))
			{
				if (!empty($item['DETAIL_PICTURE']))
				{
					if (!is_array($item['DETAIL_PICTURE']))
						$item['DETAIL_PICTURE'] = CFile::GetFileArray($item['DETAIL_PICTURE']);
					if (isset($item['DETAIL_PICTURE']['ID']))
					{
						array_unshift(
							$result,
							array(
								'ID' => intval($item['DETAIL_PICTURE']['ID']),
								'SRC' => $item['DETAIL_PICTURE']['SRC'],
								'WIDTH' => intval($item['DETAIL_PICTURE']['WIDTH']),
								'HEIGHT' => intval($item['DETAIL_PICTURE']['HEIGHT'])
							)
						);
					}
				}
			}
		}
		return $result;
	}

	public static function getLabel(&$item, $propertyCode)
	{
		if (!empty($item) && is_array($item))
		{
			$item['LABEL'] = false;
			$item['LABEL_VALUE'] = '';
			$propertyCode = (string)$propertyCode;
			if ('' !== $propertyCode && isset($item['PROPERTIES'][$propertyCode]))
			{
				$prop = $item['PROPERTIES'][$propertyCode];
				if (!empty($prop['VALUE']))
				{
					if ('N' == $prop['MULTIPLE'] && 'L' == $prop['PROPERTY_TYPE'] && 'C' == $prop['LIST_TYPE'])
					{
						$item['LABEL_VALUE'] = $prop['NAME'];
					}
					else
					{
						$item['LABEL_VALUE'] = (is_array($prop['VALUE'])
							? implode(' / ', $prop['VALUE'])
							: $prop['VALUE']
						);
					}
					$item['LABEL'] = true;

					if (isset($item['DISPLAY_PROPERTIES'][$propertyCode]))
						unset($item['DISPLAY_PROPERTIES'][$propertyCode]);
				}
				unset($prop);
			}
		}
	}

	public static function clearProperties(&$properties, $clearCodes)
	{
		if (!empty($properties) && is_array($properties))
		{
			if (!empty($clearCodes))
			{
				if (!is_array($clearCodes))
				{
					$clearCodes = array($clearCodes);
				}
				foreach ($clearCodes as &$oneCode)
				{
					if (isset($properties[$oneCode]))
					{
						unset($properties[$oneCode]);
					}
				}
				unset($oneCode);
			}
			return !empty($properties);
		}
		return false;
	}
}
?>