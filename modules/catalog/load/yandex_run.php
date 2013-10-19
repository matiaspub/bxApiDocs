<?
//<title>Yandex</title>

__IncludeLang(GetLangFileName($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/lang/", "/export_yandex.php"));
set_time_limit(0);

global $USER, $APPLICATION;
$bTmpUserCreated = false;
if (!isset($USER) || !(($USER instanceof CUser) && ('CUser' == get_class($USER))))
{
	$bTmpUserCreated = true;
	if (isset($USER))
	{
		$USER_TMP = $USER;
		unset($USER);
	}

	$USER = new CUser();
}

CCatalogDiscountSave::Disable();

$arYandexFields = array('vendor', 'vendorCode', 'model', 'author', 'name', 'publisher', 'series', 'year', 'ISBN', 'volume', 'part', 'language', 'binding', 'page_extent', 'table_of_contents', 'performed_by', 'performance_type', 'storage', 'format', 'recording_length', 'artist', 'title', 'year', 'media', 'starring', 'director', 'originalName', 'country', 'aliases', 'description', 'sales_notes', 'promo', 'provider', 'tarifplan', 'xCategory', 'additional', 'worldRegion', 'region', 'days', 'dataTour', 'hotel_stars', 'room', 'meal', 'included', 'transport', 'price_min', 'price_max', 'options', 'manufacturer_warranty', 'country_of_origin', 'downloadable', 'param', 'place', 'hall', 'hall_part', 'is_premiere', 'is_kids', 'date',);

if (!function_exists("yandex_replace_special"))
{
	function yandex_replace_special($arg)
	{
		if (in_array($arg[0], array("&quot;", "&amp;", "&lt;", "&gt;")))
			return $arg[0];
		else
			return " ";
	}
}

if (!function_exists("yandex_text2xml"))
{
	function yandex_text2xml($text, $bHSC = false, $bDblQuote = false)
	{
		global $APPLICATION;

		$bHSC = (true == $bHSC ? true : false);
		$bDblQuote = (true == $bDblQuote ? true: false);

		if ($bHSC)
		{
			$text = htmlspecialcharsbx($text);
			if ($bDblQuote)
				$text = str_replace('&quot;', '"', $text);
		}
		$text = preg_replace("/[\x1-\x8\xB-\xC\xE-\x1F]/", "", $text);
		$text = str_replace("'", "&apos;", $text);
		$text = $APPLICATION->ConvertCharset($text, LANG_CHARSET, 'windows-1251');
		return $text;
	}
}

if (!function_exists('yandex_get_value'))
{
	function yandex_get_value($arOffer, $param, $PROPERTY)
	{
		global $IBLOCK_ID;
		static $arProperties = null, $arUserTypes = null;

		if (!is_array($arProperties))
		{
			$dbRes = CIBlockProperty::GetList(
				array('ID' => 'ASC'),
				array('IBLOCK_ID' => $IBLOCK_ID, 'CHECK_PERMISSIONS' => 'N')
			);

			while ($arRes = $dbRes->Fetch())
			{
				$arProperties[$arRes['ID']] = $arRes;
			}

			if (!empty($arOffer['IBLOCK_ID']) && $arOffer['IBLOCK_ID'] != $IBLOCK_ID)
			{
				$dbRes = CIBlockProperty::GetList(
					array('ID' => 'ASC'),
					array('IBLOCK_ID' => $arOffer['IBLOCK_ID'], 'CHECK_PERMISSIONS' => 'N')
				);

				while ($arRes = $dbRes->Fetch())
				{
					$arProperties[$arRes['ID']] = $arRes;
				}
			}
		}

		$strProperty = '';
		$bParam = substr($param, 0, 6) == 'PARAM_';
		if (is_array($arProperties[$PROPERTY]))
		{
			$PROPERTY_CODE = $arProperties[$PROPERTY]['CODE'];

			$arProperty = $arOffer['PROPERTIES'][$PROPERTY_CODE] ? $arOffer['PROPERTIES'][$PROPERTY_CODE] : $arOffer['PROPERTIES'][$PROPERTY];

			$value = '';
			$description = '';
			switch ($arProperties[$PROPERTY]['PROPERTY_TYPE'])
			{
				case 'E':
					if (!empty($arProperty['VALUE']))
					{
						$arCheckValue = array();
						if (!is_array($arProperty['VALUE']))
						{
							$arProperty['VALUE'] = intval($arProperty['VALUE']);
							if (0 < $arProperty['VALUE'])
								$arCheckValue[] = $arProperty['VALUE'];
						}
						else
						{
							foreach ($arProperty['VALUE'] as &$intValue)
							{
								$intValue = intval($intValue);
								if (0 < $intValue)
									$arCheckValue[] = $intValue;
							}
							if (isset($intValue))
								unset($intValue);
						}
						if (!empty($arCheckValue))
						{
							$dbRes = CIBlockElement::GetList(array(), array('IBLOCK_ID' => $arProperties[$PROPERTY]['LINK_IBLOCK_ID'], 'ID' => $arCheckValue), false, false, array('NAME'));
							while ($arRes = $dbRes->Fetch())
							{
								$value .= ($value ? ', ' : '').$arRes['NAME'];
							}
						}
					}
					break;
				case 'G':
					if (!empty($arProperty['VALUE']))
					{
						$arCheckValue = array();
						if (!is_array($arProperty['VALUE']))
						{
							$arProperty['VALUE'] = intval($arProperty['VALUE']);
							if (0 < $arProperty['VALUE'])
								$arCheckValue[] = $arProperty['VALUE'];
						}
						else
						{
							foreach ($arProperty['VALUE'] as &$intValue)
							{
								$intValue = intval($intValue);
								if (0 < $intValue)
									$arCheckValue[] = $intValue;
							}
							if (isset($intValue))
								unset($intValue);
						}
						if (!empty($arCheckValue))
						{
							$dbRes = CIBlockSection::GetList(array(), array('IBLOCK_ID' => $arProperty['LINK_IBLOCK_ID'], 'ID' => $arCheckValue), false, array('NAME'));
							while ($arRes = $dbRes->Fetch())
							{
								$value .= ($value ? ', ' : '').$arRes['NAME'];
							}
						}
					}
					break;
				case 'L':
					if ($arProperty['VALUE'])
					{
						if (is_array($arProperty['VALUE']))
							$value .= implode(', ', $arProperty['VALUE']);
						else
							$value .= $arProperty['VALUE'];
					}
					break;
				default:
					if ($bParam && $arProperty['WITH_DESCRIPTION'] == 'Y')
					{
						$description = $arProperty['DESCRIPTION'];
						$value = $arProperty['VALUE'];
					}
					else
					{
						$value = is_array($arProperty['VALUE']) ? implode(', ', $arProperty['VALUE']) : $arProperty['VALUE'];
					}
			}

			// !!!! check multiple properties and properties like CML2_ATTRIBUTES

			if ($bParam)
			{
				if (is_array($description))
				{
					foreach ($value as $key => $val)
					{
						$strProperty .= $strProperty ? "\n" : "";
						$strProperty .= '<param name="'.yandex_text2xml($description[$key], true).'">'.yandex_text2xml($val, true).'</param>';
					}
				}
				else
				{
					$strProperty .= '<param name="'.yandex_text2xml($arProperties[$PROPERTY]['NAME'], true).'">'.yandex_text2xml($value, true).'</param>';
				}
			}
			else
			{
				$param_h = yandex_text2xml($param, true);
				$strProperty .= '<'.$param_h.'>'.yandex_text2xml($value, true).'</'.$param_h.'>';
			}
		}

		return $strProperty;
		//if (is_callable(array($arParams["arUserField"]["USER_TYPE"]['CLASS_NAME'], 'getlist')))
	}
}

$arRunErrors = array();

if ($XML_DATA && CheckSerializedData($XML_DATA))
{
	$XML_DATA = unserialize(stripslashes($XML_DATA));
	if (!is_array($XML_DATA)) $XML_DATA = array();
}

$IBLOCK_ID = intval($IBLOCK_ID);
$db_iblock = CIBlock::GetByID($IBLOCK_ID);
if (!($ar_iblock = $db_iblock->Fetch()))
{
	$arRunErrors[] = str_replace('#ID#', $IBLOCK_ID, GetMessage('YANDEX_ERR_NO_IBLOCK_FOUND_EXT'));
}
/*elseif (!CIBlockRights::UserHasRightTo($IBLOCK_ID, $IBLOCK_ID, 'iblock_admin_display'))
{
	$arRunErrors[] = str_replace('#IBLOCK_ID#',$IBLOCK_ID,GetMessage('CET_ERROR_IBLOCK_PERM'));
} */
else
{
	$SETUP_SERVER_NAME = trim($SETUP_SERVER_NAME);

	if (strlen($SETUP_SERVER_NAME) <= 0)
	{
		if (strlen($ar_iblock['SERVER_NAME']) <= 0)
		{
			$rsSite = CSite::GetList(($b="sort"), ($o="asc"), array("LID" => $ar_iblock["LID"]));
			if($arSite = $rsSite->Fetch())
				$ar_iblock["SERVER_NAME"] = $arSite["SERVER_NAME"];
			if(strlen($ar_iblock["SERVER_NAME"])<=0 && defined("SITE_SERVER_NAME"))
				$ar_iblock["SERVER_NAME"] = SITE_SERVER_NAME;
			if(strlen($ar_iblock["SERVER_NAME"])<=0)
				$ar_iblock["SERVER_NAME"] = COption::GetOptionString("main", "server_name", "");
		}
	}
	else
	{
		$ar_iblock['SERVER_NAME'] = $SETUP_SERVER_NAME;
	}
	$ar_iblock['PROPERTY'] = array();
	$rsProps = CIBlockProperty::GetList(array(),array('IBLOCK_ID' => $IBLOCK_ID, 'ACTIVE' => 'Y'));
	while ($arProp = $rsProps->Fetch())
	{
		$ar_iblock['PROPERTY'][$arProp['ID']] = $arProp;
	}
}

$boolOffers = false;
$arOffers = false;
$arOfferIBlock = false;
$intOfferIBlockID = 0;
$arSelectOfferProps = array();
$arSelectedPropTypes = array('S','N','L','E','G');
$arOffersSelectKeys = array(
	YANDEX_SKU_EXPORT_ALL,
	YANDEX_SKU_EXPORT_MIN_PRICE,
	YANDEX_SKU_EXPORT_PROP,
);
$arCondSelectProp = array(
	'ZERO',
	'NONZERO',
	'EQUAL',
	'NONEQUAL',
);
$arPropertyMap = array();
$arSKUExport = array();

$arCatalog = CCatalog::GetByIDExt($IBLOCK_ID);
if (empty($arCatalog))
{
	$arRunErrors[] = str_replace('#ID#', $IBLOCK_ID, GetMessage('YANDEX_ERR_NO_IBLOCK_IS_CATALOG'));
}
else
{
	$arOffers = CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);
	if (!empty($arOffers['IBLOCK_ID']))
	{
		$intOfferIBlockID = $arOffers['IBLOCK_ID'];
		$rsOfferIBlocks = CIBlock::GetByID($intOfferIBlockID);
		if (($arOfferIBlock = $rsOfferIBlocks->Fetch()))
		{
			$boolOffers = true;
			$rsProps = CIBlockProperty::GetList(array('SORT' => 'ASC'),array('IBLOCK_ID' => $intOfferIBlockID,'ACTIVE' => 'Y'));
			while ($arProp = $rsProps->Fetch())
			{
				if ($arOffers['SKU_PROPERTY_ID'] != $arProp['ID'])
				{
					$ar_iblock['OFFERS_PROPERTY'][$arProp['ID']] = $arProp;
					if (in_array($arProp['PROPERTY_TYPE'],$arSelectedPropTypes))
						$arSelectOfferProps[] = $arProp['ID'];
					if (strlen($arProp['CODE']) > 0)
					{
						foreach ($ar_iblock['PROPERTY'] as &$arMainProp)
						{
							if ($arMainProp['CODE'] == $arProp['CODE'])
							{
								$arPropertyMap[$arProp['ID']] = $arMainProp['CODE'];
								break;
							}
						}
						if (isset($arMainProp))
							unset($arMainProp);
					}
				}
			}
			$arOfferIBlock['LID'] = $ar_iblock['LID'];
		}
		else
		{
			$arRunErrors[] = GetMessage('YANDEX_ERR_BAD_OFFERS_IBLOCK_ID');
		}
	}
	if (true == $boolOffers)
	{
		if (empty($XML_DATA['SKU_EXPORT']))
		{
			$arRunErrors[] = GetMessage('YANDEX_ERR_SKU_SETTINGS_ABSENT');
		}
		else
		{
			$arSKUExport = $XML_DATA['SKU_EXPORT'];;
			if (empty($arSKUExport['SKU_EXPORT_COND']) || !in_array($arSKUExport['SKU_EXPORT_COND'],$arOffersSelectKeys))
			{
				$arRunErrors[] = GetMessage('YANDEX_SKU_EXPORT_ERR_CONDITION_ABSENT');
			}
			if (YANDEX_SKU_EXPORT_PROP == $arSKUExport['SKU_EXPORT_COND'])
			{
				if (empty($arSKUExport['SKU_PROP_COND']) || !is_array($arSKUExport['SKU_PROP_COND']))
				{
					$arRunErrors[] = GetMessage('YANDEX_SKU_EXPORT_ERR_PROPERTY_ABSENT');
				}
				else
				{
					if (empty($arSKUExport['SKU_PROP_COND']['PROP_ID']) || !in_array($arSKUExport['SKU_PROP_COND']['PROP_ID'],$arSelectOfferProps))
					{
						$arRunErrors[] = GetMessage('YANDEX_SKU_EXPORT_ERR_PROPERTY_ABSENT');
					}
					if (empty($arSKUExport['SKU_PROP_COND']['COND']) || !in_array($arSKUExport['SKU_PROP_COND']['COND'],$arCondSelectProp))
					{
						$arRunErrors[] = GetMessage('YANDEX_SKU_EXPORT_ERR_PROPERTY_COND_ABSENT');
					}
					else
					{
						if ($arSKUExport['SKU_PROP_COND']['COND'] == 'EQUAL' || $arSKUExport['SKU_PROP_COND']['COND'] == 'NONEQUAL')
						{
							if (empty($arSKUExport['SKU_PROP_COND']['VALUES']))
							{
								$arRunErrors[] = GetMessage('YANDEX_SKU_EXPORT_ERR_PROPERTY_VALUES_ABSENT');
							}
						}
					}
				}
			}
		}
	}
}

if (empty($arRunErrors))
{
	$bAllSections = False;
	$arSections = array();
	if (is_array($V))
	{
		foreach ($V as $key => $value)
		{
			if (trim($value)=="0")
			{
				$bAllSections = True;
				break;
			}

			if (intval($value)>0)
			{
				$arSections[] = intval($value);
			}
		}
	}

	if (!$bAllSections && count($arSections)<=0)
	{
		$arRunErrors[] = GetMessage('YANDEX_ERR_NO_SECTION_LIST');
	}
}

if (!empty($XML_DATA['PRICE']))
{
	if (intval($XML_DATA['PRICE']) > 0)
	{
		$rsCatalogGroups = CCatalogGroup::GetGroupsList(array('CATALOG_GROUP_ID' => $XML_DATA['PRICE'],'GROUP_ID' => 2));
		if (!($arCatalogGroup = $rsCatalogGroups->Fetch()))
		{
			$arRunErrors[] = GetMessage('YANDEX_ERR_BAD_PRICE_TYPE');
		}
	}
	else
	{
		$arRunErrors[] = GetMessage('YANDEX_ERR_BAD_PRICE_TYPE');
	}
}

if (strlen($SETUP_FILE_NAME) <= 0)
{
	$arRunErrors[] = GetMessage("CATI_NO_SAVE_FILE");
}
elseif (preg_match(BX_CATALOG_FILENAME_REG,$SETUP_FILE_NAME))
{
	$arRunErrors[] = GetMessage("CES_ERROR_BAD_EXPORT_FILENAME");
}
else
{
	$SETUP_FILE_NAME = Rel2Abs("/", $SETUP_FILE_NAME);
}
if (empty($arRunErrors))
{
/*	if ($GLOBALS["APPLICATION"]->GetFileAccessPermission($SETUP_FILE_NAME) < "W")
	{
		$arRunErrors[] = str_replace('#FILE#', $SETUP_FILE_NAME,GetMessage('YANDEX_ERR_FILE_ACCESS_DENIED'));
	} */
}

if (empty($arRunErrors))
{
	CheckDirPath($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME);

	if (!$fp = @fopen($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME, "wb"))
	{
		$arRunErrors[] = str_replace('#FILE#', $_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME, GetMessage('YANDEX_ERR_FILE_OPEN_WRITING'));
	}
	else
	{

		if (!@fwrite($fp, '<?if (!isset($_GET["referer1"]) || strlen($_GET["referer1"])<=0) $_GET["referer1"] = "yandext";?>'))
		{
			$arRunErrors[] = str_replace('#FILE#', $_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME, GetMessage('YANDEX_ERR_SETUP_FILE_WRITE'));
			@fclose($fp);
		}
		else
		{
			fwrite($fp, '<? $strReferer1 = htmlspecialchars($_GET["referer1"]); ?>');
			fwrite($fp, '<?if (!isset($_GET["referer2"]) || strlen($_GET["referer2"]) <= 0) $_GET["referer2"] = "";?>');
			fwrite($fp, '<? $strReferer2 = htmlspecialchars($_GET["referer2"]); ?>');
		}
	}
}

if (empty($arRunErrors))
{
	@fwrite($fp, '<? header("Content-Type: text/xml; charset=windows-1251");?>');
	@fwrite($fp, '<? echo "<"."?xml version=\"1.0\" encoding=\"windows-1251\"?".">"?>');
	@fwrite($fp, "\n<!DOCTYPE yml_catalog SYSTEM \"shops.dtd\">\n");
	@fwrite($fp, "<yml_catalog date=\"".Date("Y-m-d H:i")."\">\n");
	@fwrite($fp, "<shop>\n");

	@fwrite($fp, "<name>".$APPLICATION->ConvertCharset(htmlspecialcharsbx(COption::GetOptionString("main", "site_name", "")), LANG_CHARSET, 'windows-1251')."</name>\n");

	@fwrite($fp, "<company>".$APPLICATION->ConvertCharset(htmlspecialcharsbx(COption::GetOptionString("main", "site_name", "")), LANG_CHARSET, 'windows-1251')."</company>\n");
	@fwrite($fp, "<url>http://".htmlspecialcharsbx($ar_iblock['SERVER_NAME'])."</url>\n");

	$strTmp = "<currencies>\n";

	if ($arCurrency = CCurrency::GetByID('RUR'))
		$RUR = 'RUR';
	else
		$RUR = 'RUB';

	$arCurrencyAllowed = array($RUR, 'USD', 'EUR', 'UAH', 'BYR', 'KZT');

	$BASE_CURRENCY = CCurrency::GetBaseCurrency();
	if (is_array($XML_DATA['CURRENCY']))
	{
		foreach ($XML_DATA['CURRENCY'] as $CURRENCY => $arCurData)
		{
			if (in_array($CURRENCY, $arCurrencyAllowed))
			{
				$strTmp.= "<currency id=\"".$CURRENCY."\""
				." rate=\"".($arCurData['rate'] == 'SITE' ? CCurrencyRates::ConvertCurrency(1, $CURRENCY, $RUR) : $arCurData['rate'])."\""
				.($arCurData['plus'] > 0 ? ' plus="'.intval($arCurData['plus']).'"' : '')
				." />\n";
			}
		}
	}
	else
	{
		$db_acc = CCurrency::GetList(($by="sort"), ($order="asc"));
		while ($arAcc = $db_acc->Fetch())
		{
			if (in_array($arAcc['CURRENCY'], $arCurrencyAllowed))
				$strTmp.= "<currency id=\"".$arAcc["CURRENCY"]."\" rate=\"".(CCurrencyRates::ConvertCurrency(1, $arAcc["CURRENCY"], $RUR))."\"/>\n";
		}
	}
	$strTmp.= "</currencies>\n";

	@fwrite($fp, $strTmp);

	//*****************************************//


	//*****************************************//
	$intMaxSectionID = 0;

	$strTmpCat = "";
	$strTmpOff = "";

	$arAvailGroups = array();
	if (!$bAllSections)
	{
		for ($i = 0, $intSectionsCount = count($arSections); $i < $intSectionsCount; $i++)
		{
			$filter_tmp = $filter;
			$db_res = CIBlockSection::GetNavChain($IBLOCK_ID, $arSections[$i]);
			$curLEFT_MARGIN = 0;
			$curRIGHT_MARGIN = 0;
			while ($ar_res = $db_res->Fetch())
			{
				$curLEFT_MARGIN = intval($ar_res["LEFT_MARGIN"]);
				$curRIGHT_MARGIN = intval($ar_res["RIGHT_MARGIN"]);
				$arAvailGroups[$ar_res["ID"]] = array(
					"ID" => intval($ar_res["ID"]),
					"IBLOCK_SECTION_ID" => intval($ar_res["IBLOCK_SECTION_ID"]),
					"NAME" => $ar_res["NAME"]
					);
				if ($intMaxSectionID < $ar_res["ID"])
					$intMaxSectionID = $ar_res["ID"];
			}

			$filter = Array("IBLOCK_ID"=>$IBLOCK_ID, ">LEFT_MARGIN"=>$curLEFT_MARGIN, "<RIGHT_MARGIN"=>$curRIGHT_MARGIN, "ACTIVE"=>"Y", "IBLOCK_ACTIVE"=>"Y", "GLOBAL_ACTIVE"=>"Y");
			$db_res = CIBlockSection::GetList(array("left_margin"=>"asc"), $filter);
			while ($ar_res = $db_res->Fetch())
			{
				$arAvailGroups[$ar_res["ID"]] = array(
					"ID" => intval($ar_res["ID"]),
					"IBLOCK_SECTION_ID" => intval($ar_res["IBLOCK_SECTION_ID"]),
					"NAME" => $ar_res["NAME"]
					);
				if ($intMaxSectionID < $ar_res["ID"])
					$intMaxSectionID = $ar_res["ID"];
			}
		}
	}
	else
	{
		$filter = Array("IBLOCK_ID"=>$IBLOCK_ID, "ACTIVE"=>"Y", "IBLOCK_ACTIVE"=>"Y", "GLOBAL_ACTIVE"=>"Y");
		$db_res = CIBlockSection::GetList(array("left_margin"=>"asc"), $filter);
		while ($ar_res = $db_res->Fetch())
		{
			$arAvailGroups[$ar_res["ID"]] = array(
				"ID" => intval($ar_res["ID"]),
				"IBLOCK_SECTION_ID" => intval($ar_res["IBLOCK_SECTION_ID"]),
				"NAME" => $ar_res["NAME"]
				);
			if ($intMaxSectionID < $ar_res["ID"])
					$intMaxSectionID = $ar_res["ID"];
		}
	}

	$arSectionIDs = array();
	foreach ($arAvailGroups as &$value)
	{
		$strTmpCat.= "<category id=\"".$value["ID"]."\"".(intval($value["IBLOCK_SECTION_ID"])>0?" parentId=\"".$value["IBLOCK_SECTION_ID"]."\"":"").">".yandex_text2xml($value["NAME"], true)."</category>\n";
	}
	if (isset($value))
		unset($value);

	if (!empty($arAvailGroups))
		$arSectionIDs = array_keys($arAvailGroups);

	$intMaxSectionID += 100000000;

	//*****************************************//
	$boolNeedRootSection = false;

	if ('D' == $arCatalog['CATALOG_TYPE'] || 'O' == $arCatalog['CATALOG_TYPE'])
	{
		$arSelect = array("ID", "LID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "ACTIVE", "ACTIVE_FROM", "ACTIVE_TO", "NAME", "PREVIEW_PICTURE", "PREVIEW_TEXT", "PREVIEW_TEXT_TYPE", "DETAIL_PICTURE", "LANG_DIR", "DETAIL_PAGE_URL");

		$filter = Array("IBLOCK_ID"=>$IBLOCK_ID, "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");
		if (!$bAllSections)
		{
			$filter["INCLUDE_SUBSECTIONS"] = "Y";
			$filter["SECTION_ID"] = $arSectionIDs;
		}
		$res = CIBlockElement::GetList(array(), $filter, false, false, $arSelect);

		$total_sum = 0;
		$is_exists = false;
		$cnt = 0;

		while ($obElement = $res->GetNextElement())
		{
			$arAcc = $obElement->GetFields();
			if (is_array($XML_DATA['XML_DATA']))
			{
				$arAcc["PROPERTIES"] = $obElement->GetProperties();
			}
			$arAcc['CATALOG_QUANTITY'] = '';
			$arAcc['CATALOG_QUANTITY_TRACE'] = 'N';
			$arProduct = CCatalogProduct::GetByID($arAcc['ID']);
			if (!empty($arProduct))
			{
				$arAcc['CATALOG_QUANTITY'] = $arProduct['QUANTITY'];
				$arAcc['CATALOG_QUANTITY_TRACE'] = $arProduct['QUANTITY_TRACE'];
			}
			$str_QUANTITY = DoubleVal($arAcc["CATALOG_QUANTITY"]);
			$str_QUANTITY_TRACE = $arAcc["CATALOG_QUANTITY_TRACE"];
			if (($str_QUANTITY <= 0) && ($str_QUANTITY_TRACE == "Y"))
				$str_AVAILABLE = ' available="false"';
			else
				$str_AVAILABLE = ' available="true"';

			$minPrice = 0;
			$minPriceRUR = 0;
			$minPriceGroup = 0;
			$minPriceCurrency = "";

			if ($XML_DATA['PRICE'] > 0)
			{
				$rsPrices = CPrice::GetListEx(array(),array(
					'PRODUCT_ID' => $arAcc['ID'],
					'CATALOG_GROUP_ID' => $XML_DATA['PRICE'],
					'CAN_BUY' => 'Y',
					'GROUP_GROUP_ID' => array(2),
					'+<=QUANTITY_FROM' => 1,
					'+>=QUANTITY_TO' => 1,
					)
				);
				if ($arPrice = $rsPrices->Fetch())
				{
/*					$dbVAT = CCatalogProduct::GetVATInfo($arAcc['ID']);
					if ($arVat = $dbVAT->Fetch())
					{
						$arVat['RATE'] = floatval($arVat['RATE'] * 0.01);
					}
					else
					{
						$arVat = array('RATE' => 0, 'VAT_INCLUDED' => 'N');
					}
					$arPrice['VAT_RATE'] = $arVat['RATE'];
					$arPrice['VAT_INCLUDED'] = $arVat['VAT_INCLUDED'];
					if ($arPrice['VAT_INCLUDED'] == 'N')
					{
						$arPrice['PRICE'] *= (1 + $arPrice['VAT_RATE']);
						$arPrice['VAT_INCLUDED'] = 'Y';
					}
					$arPrice['PRICE'] = roundEx($arPrice['PRICE'], CATALOG_VALUE_PRECISION);
					$arDiscounts = CCatalogDiscount::GetDiscount(
						$arAcc['ID'],
						$IBLOCK_ID,
						array($XML_DATA['PRICE']),
						array(2),
						'N',
						$ar_iblock['LID'],
						false
					);
					$minPrice = CCatalogProduct::CountPriceWithDiscount($arPrice['PRICE'], $arPrice["CURRENCY"], $arDiscounts);
					$minPriceGroup = $arPrice['CATALOG_GROUP_ID'];
					$minPriceCurrency = $arPrice["CURRENCY"];
					$minPriceRUR = CCurrencyRates::ConvertCurrency($arPrice['PRICE'], $arPrice["CURRENCY"], $RUR);
					*/
					if ($arOptimalPrice = CCatalogProduct::GetOptimalPrice(
						$arAcc['ID'],
						1,
						array(2), // anonymous
						'N',
						array($arPrice),
						$ar_iblock['LID']
					))
					{
						$minPrice = $arOptimalPrice['DISCOUNT_PRICE'];
						$minPriceCurrency = $BASE_CURRENCY;
						$minPriceRUR = CCurrencyRates::ConvertCurrency($minPrice, $BASE_CURRENCY, $RUR);
						$minPriceGroup = $arOptimalPrice['PRICE']['CATALOG_GROUP_ID'];
					}
				}
			}
			else
			{
				if ($arPrice = CCatalogProduct::GetOptimalPrice(
					$arAcc['ID'],
					1,
					array(2), // anonymous
					'N',
					array(),
					$ar_iblock['LID']
				))
				{
					$minPrice = $arPrice['DISCOUNT_PRICE'];
					$minPriceCurrency = $BASE_CURRENCY;
					$minPriceRUR = CCurrencyRates::ConvertCurrency($minPrice, $BASE_CURRENCY, $RUR);
					$minPriceGroup = $arPrice['PRICE']['CATALOG_GROUP_ID'];
				}
			}

			if ($minPrice <= 0) continue;

			$boolCurrentSections = false;
			$bNoActiveGroup = True;
			$strTmpOff_tmp = "";
			$db_res1 = CIBlockElement::GetElementGroups($arAcc["ID"], false, array('ID', 'ADDITIONAL_PROPERTY_ID'));
			while ($ar_res1 = $db_res1->Fetch())
			{
				if (0 < intval($ar_res1['ADDITIONAL_PROPERTY_ID']))
					continue;
				$boolCurrentSections = true;
				if (in_array(intval($ar_res1["ID"]), $arSectionIDs))
				{
					$strTmpOff_tmp.= "<categoryId>".$ar_res1["ID"]."</categoryId>\n";
					$bNoActiveGroup = False;

				}
			}
			if (false == $boolCurrentSections)
			{
				$boolNeedRootSection = true;
				$strTmpOff_tmp.= "<categoryId>".$intMaxSectionID."</categoryId>\n";
			}
			else
			{
				if ($bNoActiveGroup)
					continue;
			}

			if (strlen($arAcc['DETAIL_PAGE_URL']) <= 0)
				$arAcc['DETAIL_PAGE_URL'] = '/';
			else
				$arAcc['DETAIL_PAGE_URL'] = str_replace(' ', '%20', $arAcc['DETAIL_PAGE_URL']);

			if (is_array($XML_DATA) && $XML_DATA['TYPE'] && $XML_DATA['TYPE'] != 'none')
				$str_TYPE = ' type="'.htmlspecialcharsbx($XML_DATA['TYPE']).'"';
			else
				$str_TYPE = '';

			$strTmpOff.= "<offer id=\"".$arAcc["ID"]."\"".$str_TYPE.$str_AVAILABLE.">\n";
			$strTmpOff.= "<url>http://".$ar_iblock['SERVER_NAME'].htmlspecialcharsbx($arAcc["~DETAIL_PAGE_URL"]).(strstr($arAcc['DETAIL_PAGE_URL'], '?') === false ? '?' : '&amp;')."r1=<?echo \$strReferer1; ?>&amp;r2=<?echo \$strReferer2; ?></url>\n";

			$strTmpOff.= "<price>".$minPrice."</price>\n";
			$strTmpOff.= "<currencyId>".$minPriceCurrency."</currencyId>\n";

			$strTmpOff.= $strTmpOff_tmp;

			if (intval($arAcc["DETAIL_PICTURE"])>0 || intval($arAcc["PREVIEW_PICTURE"])>0)
			{
				$pictNo = intval($arAcc["DETAIL_PICTURE"]);
				if ($pictNo<=0) $pictNo = intval($arAcc["PREVIEW_PICTURE"]);

				if ($ar_file = CFile::GetFileArray($pictNo))
				{
					if(substr($ar_file["SRC"], 0, 1) == "/")
						$strFile = "http://".$ar_iblock['SERVER_NAME'].implode("/", array_map("rawurlencode", explode("/", $ar_file["SRC"])));
					elseif(preg_match("/^(http|https):\\/\\/(.*?)\\/(.*)\$/", $ar_file["SRC"], $match))
						$strFile = "http://".$match[2].'/'.implode("/", array_map("rawurlencode", explode("/", $match[3])));
					else
						$strFile = $ar_file["SRC"];
					$strTmpOff.="<picture>".$strFile."</picture>\n";
				}
			}

			$y = 0;
			foreach ($arYandexFields as $key)
			{
				switch ($key)
				{
				case 'name':
					if (is_array($XML_DATA) && ($XML_DATA['TYPE'] == 'vendor.model' || $XML_DATA['TYPE'] == 'artist.title'))
						continue;

					$strTmpOff .= "<name>".yandex_text2xml($arAcc["NAME"], true)."</name>\n";
					break;
				case 'description':
					$strTmpOff .=
						"<description>".
						yandex_text2xml(TruncateText(
							($arAcc["PREVIEW_TEXT_TYPE"]=="html"?
							strip_tags(preg_replace_callback("'&[^;]*;'", "yandex_replace_special", $arAcc["~PREVIEW_TEXT"])) : preg_replace_callback("'&[^;]*;'", "yandex_replace_special", $arAcc["~PREVIEW_TEXT"])),
							255), true).
						"</description>\n";
					break;
				case 'param':
					if (is_array($XML_DATA) && is_array($XML_DATA['XML_DATA']) && is_array($XML_DATA['XML_DATA']['PARAMS']))
					{
						foreach ($XML_DATA['XML_DATA']['PARAMS'] as $key => $prop_id)
						{
							$strParamValue = '';
							if ($prop_id)
							{
								$strParamValue = yandex_get_value($arAcc, 'PARAM_'.$key, $prop_id);
							}
							if ('' != $strParamValue)
								$strTmpOff .= $strParamValue."\n";
						}
					}
					break;
				case 'model':
				case 'title':
					if (!is_array($XML_DATA) || !is_array($XML_DATA['XML_DATA']) || !$XML_DATA['XML_DATA'][$key])
					{
						if (
							$key == 'model' && $XML_DATA['TYPE'] == 'vendor.model'
							||
							$key == 'title' && $XML_DATA['TYPE'] == 'artist.title'
						)

						$strTmpOff.= "<".$key.">".yandex_text2xml($arAcc["NAME"], true)."</".$key.">\n";
					}
					else
					{
						$strValue = '';
						$strValue = yandex_get_value($arAcc, $key, $XML_DATA['XML_DATA'][$key]);
						if ('' != $strValue)
							$strTmpOff .= $strValue."\n";
					}
					break;
				case 'year':
					$y++;
					if ($XML_DATA['TYPE'] == 'artist.title')
					{
						if ($y == 1) continue;
					}
					else
					{
						if ($y > 1) continue;
					}

					// no break here

				default:
					if (is_array($XML_DATA) && is_array($XML_DATA['XML_DATA']) && $XML_DATA['XML_DATA'][$key])
					{
						$strValue = '';
						$strValue = yandex_get_value($arAcc, $key, $XML_DATA['XML_DATA'][$key]);
						if ('' != $strValue)
							$strTmpOff .= $strValue."\n";
					}
				}
			}

			$strTmpOff.= "</offer>\n";
		}
	}
	elseif ('P' == $arCatalog['CATALOG_TYPE'] || 'X' == $arCatalog['CATALOG_TYPE'])
	{
		$arOfferSelect = array("ID", "LID", "IBLOCK_ID", "ACTIVE", "NAME", "PREVIEW_PICTURE", "PREVIEW_TEXT", "PREVIEW_TEXT_TYPE", "DETAIL_PICTURE", "DETAIL_PAGE_URL");
		$arOfferFilter = array('IBLOCK_ID' => $intOfferIBlockID, 'PROPERTY_'.$arOffers['SKU_PROPERTY_ID'] => 0, "ACTIVE" => "Y", "ACTIVE_DATE" => "Y");
		if (YANDEX_SKU_EXPORT_PROP == $arSKUExport['SKU_EXPORT_COND'])
		{
			$strExportKey = '';
			$mxValues = false;
			if ($arSKUExport['SKU_PROP_COND']['COND'] == 'NONZERO' || $arSKUExport['SKU_PROP_COND']['COND'] == 'NONEQUAL')
				$strExportKey = '!';
			$strExportKey .= 'PROPERTY_'.$arSKUExport['SKU_PROP_COND']['PROP_ID'];
			if ($arSKUExport['SKU_PROP_COND']['COND'] == 'EQUAL' || $arSKUExport['SKU_PROP_COND']['COND'] == 'NONEQUAL')
				$mxValues = $arSKUExport['SKU_PROP_COND']['VALUES'];
			$arOfferFilter[$strExportKey] = $mxValues;
		}

		$arSelect = array("ID", "LID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "ACTIVE", "ACTIVE_FROM", "ACTIVE_TO", "NAME", "PREVIEW_PICTURE", "PREVIEW_TEXT", "PREVIEW_TEXT_TYPE", "DETAIL_PICTURE", "DETAIL_PAGE_URL");
		$arFilter = Array("IBLOCK_ID"=>$IBLOCK_ID, "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");
		if (!$bAllSections)
		{
			$arFilter["INCLUDE_SUBSECTIONS"] = "Y";
			$arFilter["SECTION_ID"] = $arSectionIDs;
		}

		$strOfferTemplateURL = '';
		if (!empty($arSKUExport['SKU_URL_TEMPLATE_TYPE']))
		{
			switch($arSKUExport['SKU_URL_TEMPLATE_TYPE'])
			{
				case YANDEX_SKU_TEMPLATE_PRODUCT:
					$strOfferTemplateURL = '#PRODUCT_URL#';
					break;
				case YANDEX_SKU_TEMPLATE_CUSTOM:
					if (!empty($arSKUExport['SKU_URL_TEMPLATE']))
					$strOfferTemplateURL = $arSKUExport['SKU_URL_TEMPLATE'];
					break;
				case YANDEX_SKU_TEMPLATE_OFFERS:
				default:
					$strOfferTemplateURL = '';
					break;
			}
		}

		$rsItems = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
		while ($obItem = $rsItems->GetNextElement())
		{
			$arItem = $obItem->GetFields();
			$arItem['PROPERTIES'] = $obItem->GetProperties();
			if (!empty($arItem['PROPERTIES']))
			{
				$arCross = array();
				foreach ($arItem['PROPERTIES'] as &$arProp)
				{
					$arCross[$arProp['ID']] = $arProp;
				}
				if (isset($arProp))
					unset($arProp);
				$arItem['PROPERTIES'] = $arCross;
			}
			$boolItemExport = false;
			$boolItemOffers = false;
			$arItem['OFFERS'] = array();

			$boolCurrentSections = false;
			$boolNoActiveSections = true;
			$strSections = '';
			$rsSections = CIBlockElement::GetElementGroups($arItem["ID"], false, array('ID', 'ADDITIONAL_PROPERTY_ID'));
			while ($arSection = $rsSections->Fetch())
			{
				if (0 < intval($arSection['ADDITIONAL_PROPERTY_ID']))
					continue;
				$arSection["ID"] = intval($arSection["ID"]);
				$boolCurrentSections = true;
				if (in_array($arSection["ID"], $arSectionIDs))
				{
					$strSections .= "<categoryId>".$arSection["ID"]."</categoryId>\n";
					$boolNoActiveSections = false;
				}
			}
			if (false == $boolCurrentSections)
			{
				$boolNeedRootSection = true;
				$strSections .= "<categoryId>".$intMaxSectionID."</categoryId>\n";
			}
			else
			{
				if ($boolNoActiveSections)
					continue;
			}

			$arItem['YANDEX_CATEGORY'] = $strSections;

			$strFile = '';
			if (intval($arItem["DETAIL_PICTURE"])>0 || intval($arItem["PREVIEW_PICTURE"])>0)
			{
				$pictNo = intval($arItem["DETAIL_PICTURE"]);
				if ($pictNo <= 0)
					$pictNo = intval($arItem["PREVIEW_PICTURE"]);

				if ($ar_file = CFile::GetFileArray($pictNo))
				{
					if(substr($ar_file["SRC"], 0, 1) == "/")
						$strFile = "http://".$ar_iblock['SERVER_NAME'].implode("/", array_map("rawurlencode", explode("/", $ar_file["SRC"])));
					elseif(preg_match("/^(http|https):\\/\\/(.*?)\\/(.*)\$/", $ar_file["SRC"], $match))
						$strFile = "http://".$match[2].'/'.implode("/", array_map("rawurlencode", explode("/", $match[3])));
					else
						$strFile = $ar_file["SRC"];
				}
			}
			$arItem['YANDEX_PICT'] = $strFile;

			$arItem['YANDEX_DESCR'] = yandex_text2xml(TruncateText(
							($arItem["PREVIEW_TEXT_TYPE"]=="html"?
							strip_tags(preg_replace_callback("'&[^;]*;'", "yandex_replace_special", $arItem["~PREVIEW_TEXT"])) : preg_replace_callback("'&[^;]*;'", "yandex_replace_special", $arItem["~PREVIEW_TEXT"])),
							255), true);

			$arOfferFilter['PROPERTY_'.$arOffers['SKU_PROPERTY_ID']] = $arItem['ID'];
			$rsOfferItems = CIBlockElement::GetList(array(),$arOfferFilter,false,false,$arOfferSelect);

			if (!empty($strOfferTemplateURL))
				$rsOfferItems->SetUrlTemplates($strOfferTemplateURL);
			if (YANDEX_SKU_EXPORT_MIN_PRICE == $arSKUExport['SKU_EXPORT_COND'])
			{
				$arCurrentOffer = false;
				$arCurrentPrice = false;
				$dblAllMinPrice = 0;
				$boolFirst = true;

				while ($obOfferItem = $rsOfferItems->GetNextElement())
				{
					$arOfferItem = $obOfferItem->GetFields();
					$minPrice = -1;
					if ($XML_DATA['PRICE'] > 0)
					{
						$rsPrices = CPrice::GetListEx(array(),array(
							'PRODUCT_ID' => $arOfferItem['ID'],
							'CATALOG_GROUP_ID' => $XML_DATA['PRICE'],
							'CAN_BUY' => 'Y',
							'GROUP_GROUP_ID' => array(2),
							'+<=QUANTITY_FROM' => 1,
							'+>=QUANTITY_TO' => 1,
							)
						);
						if ($arPrice = $rsPrices->Fetch())
						{
/*							$dbVAT = CCatalogProduct::GetVATInfo($arOfferItem['ID']);
							if ($arVat = $dbVAT->Fetch())
							{
								$arVat['RATE'] = floatval($arVat['RATE'] * 0.01);
							}
							else
							{
								$arVat = array('RATE' => 0, 'VAT_INCLUDED' => 'N');
							}
							$arPrice['VAT_RATE'] = $arVat['RATE'];
							$arPrice['VAT_INCLUDED'] = $arVat['VAT_INCLUDED'];
							if ($arPrice['VAT_INCLUDED'] == 'N')
							{
								$arPrice['PRICE'] *= (1 + $arPrice['VAT_RATE']);
								$arPrice['VAT_INCLUDED'] = 'Y';
							}
							$arPrice['PRICE'] = roundEx($arPrice['PRICE'], CATALOG_VALUE_PRECISION);
							$arDiscounts = CCatalogDiscount::GetDiscount(
								$arOfferItem['ID'],
								$intOfferIBlockID,
								array($XML_DATA['PRICE']),
								array(2),
								'N',
								$arOfferIBlock['LID'],
								false
							);
							$minPrice = CCatalogProduct::CountPriceWithDiscount($arPrice['PRICE'], $arPrice["CURRENCY"], $arDiscounts);
							$minPriceGroup = $arPrice['CATALOG_GROUP_ID'];
							$minPriceCurrency = $arPrice["CURRENCY"];
							$minPriceRUR = CCurrencyRates::ConvertCurrency($arPrice['PRICE'], $arPrice["CURRENCY"], $RUR); */
							if ($arOptimalPrice = CCatalogProduct::GetOptimalPrice(
								$arOfferItem['ID'],
								1,
								array(2),
								'N',
								array($arPrice),
								$arOfferIBlock['LID']
							))
							{
								$minPrice = $arOptimalPrice['DISCOUNT_PRICE'];
								$minPriceCurrency = $BASE_CURRENCY;
								$minPriceRUR = CCurrencyRates::ConvertCurrency($minPrice, $BASE_CURRENCY, $RUR);
								$minPriceGroup = $arOptimalPrice['PRICE']['CATALOG_GROUP_ID'];
							}
						}
					}
					else
					{
						if ($arPrice = CCatalogProduct::GetOptimalPrice(
							$arOfferItem['ID'],
							1,
							array(2), // anonymous
							'N',
							array(),
							$arOfferIBlock['LID']
						))
						{
							$minPrice = $arPrice['DISCOUNT_PRICE'];
							$minPriceCurrency = $BASE_CURRENCY;
							$minPriceRUR = CCurrencyRates::ConvertCurrency($minPrice, $BASE_CURRENCY, $RUR);
							$minPriceGroup = $arPrice['PRICE']['CATALOG_GROUP_ID'];
						}
					}
					if ($minPrice <= 0)
						continue;
					if ($boolFirst)
					{
						$dblAllMinPrice = $minPriceRUR;
						$arOfferItem['PROPERTIES'] = $obOfferItem->GetProperties();
						$arCross = array();
						foreach ($arOfferItem['PROPERTIES'] as $arProp)
						{
							$arCross[$arProp['ID']] = $arProp;
						}
						if (!empty($arItem['PROPERTIES']))
						{
							$arOfferItem['PROPERTIES'] = $arCross + $arItem['PROPERTIES'];
						}
						else
						{
							$arOfferItem['PROPERTIES'] = $arCross;
						}

						$arCurrentOffer = $arOfferItem;
						$arCurrentPrice = array(
							'MIN_PRICE' => $minPrice,
							'MIN_PRICE_CURRENCY' => $minPriceCurrency,
							'MIN_PRICE_RUR' => $minPriceRUR,
							'MIN_PRICE_GROUP' => $minPriceGroup,
						);
						$boolFirst = false;
					}
					else
					{
						if ($dblAllMinPrice > $minPriceRUR)
						{
							$dblAllMinPrice > $minPriceRUR;
							$arOfferItem['PROPERTIES'] = $obOfferItem->GetProperties();
							$arCross = array();
							foreach ($arOfferItem['PROPERTIES'] as $arProp)
							{
								$arCross[$arProp['ID']] = $arProp;
							}
							if (!empty($arItem['PROPERTIES']))
							{
								$arOfferItem['PROPERTIES'] = $arCross + $arItem['PROPERTIES'];
							}
							else
							{
								$arOfferItem['PROPERTIES'] = $arCross;
							}

							$arCurrentOffer = $arOfferItem;
							$arCurrentPrice = array(
								'MIN_PRICE' => $minPrice,
								'MIN_PRICE_CURRENCY' => $minPriceCurrency,
								'MIN_PRICE_RUR' => $minPriceRUR,
								'MIN_PRICE_GROUP' => $minPriceGroup,
							);
						}
					}
				}
				if (!empty($arCurrentOffer) && !empty($arCurrentPrice))
				{
					$arOfferItem = $arCurrentOffer;
					$minPrice = $arCurrentPrice['MIN_PRICE'];
					$minPriceCurrency = $arCurrentPrice['MIN_PRICE_CURRENCY'];
					$minPriceRUR = $arCurrentPrice['MIN_PRICE_RUR'];
					$minPriceGroup = $arCurrentPrice['MIN_PRICE_GROUP'];

					$arOfferItem['CATALOG_QUANTITY'] = '';
					$arOfferItem['CATALOG_QUANTITY_TRACE'] = 'N';
					$arProduct = CCatalogProduct::GetByID($arOfferItem['ID']);
					if (!empty($arProduct))
					{
						$arOfferItem['CATALOG_QUANTITY'] = $arProduct['QUANTITY'];
						$arOfferItem['CATALOG_QUANTITY_TRACE'] = $arProduct['QUANTITY_TRACE'];
					}
					$arOfferItem['YANDEX_AVAILABLE'] = 'true';
					$str_QUANTITY = DoubleVal($arOfferItem["CATALOG_QUANTITY"]);
					$str_QUANTITY_TRACE = $arOfferItem["CATALOG_QUANTITY_TRACE"];
					if (($str_QUANTITY <= 0) && ($str_QUANTITY_TRACE == "Y"))
						$arOfferItem['YANDEX_AVAILABLE'] = 'false';

					if (strlen($arOfferItem['DETAIL_PAGE_URL']) <= 0)
						$arOfferItem['DETAIL_PAGE_URL'] = '/';
					else
						$arOfferItem['DETAIL_PAGE_URL'] = str_replace(' ', '%20', $arOfferItem['DETAIL_PAGE_URL']);

					if (is_array($XML_DATA) && $XML_DATA['TYPE'] && $XML_DATA['TYPE'] != 'none')
						$str_TYPE = ' type="'.htmlspecialcharsbx($XML_DATA['TYPE']).'"';
					else
						$str_TYPE = '';

					$arOfferItem['YANDEX_TYPE'] = $str_TYPE;

					$strOfferYandex = '';
					$strOfferYandex .= "<offer id=\"".$arOfferItem["ID"]."\"".$str_TYPE." available=\"".$arOfferItem['YANDEX_AVAILABLE']."\">\n";
					$strOfferYandex .= "<url>http://".$ar_iblock['SERVER_NAME'].htmlspecialcharsbx($arOfferItem["~DETAIL_PAGE_URL"]).(strstr($arOfferItem['DETAIL_PAGE_URL'], '?') === false ? '?' : '&amp;')."r1=<?echo \$strReferer1; ?>&amp;r2=<?echo \$strReferer2; ?></url>\n";

					$strOfferYandex .= "<price>".$minPrice."</price>\n";
					$strOfferYandex .= "<currencyId>".$minPriceCurrency."</currencyId>\n";

					$strOfferYandex .= $arItem['YANDEX_CATEGORY'];

					$strFile = '';
					if (intval($arOfferItem["DETAIL_PICTURE"])>0 || intval($arOfferItem["PREVIEW_PICTURE"])>0)
					{
						$pictNo = intval($arOfferItem["DETAIL_PICTURE"]);
						if ($pictNo<=0)
							$pictNo = intval($arOfferItem["PREVIEW_PICTURE"]);

						if ($ar_file = CFile::GetFileArray($pictNo))
						{
							if(substr($ar_file["SRC"], 0, 1) == "/")
								$strFile = "http://".$ar_iblock['SERVER_NAME'].implode("/", array_map("rawurlencode", explode("/", $ar_file["SRC"])));
							elseif(preg_match("/^(http|https):\\/\\/(.*?)\\/(.*)\$/", $ar_file["SRC"], $match))
								$strFile = "http://".$match[2].'/'.implode("/", array_map("rawurlencode", explode("/", $match[3])));
							else
								$strFile = $ar_file["SRC"];
						}
					}
					if (!empty($strFile) || !empty($arItem['YANDEX_PICT']))
					{
						$strOfferYandex .= "<picture>".(!empty($strFile) ? $strFile : $arItem['YANDEX_PICT'])."</picture>\n";
					}

					$y = 0;
					foreach ($arYandexFields as $key)
					{
						switch ($key)
						{
						case 'name':
							if (is_array($XML_DATA) && ($XML_DATA['TYPE'] == 'vendor.model' || $XML_DATA['TYPE'] == 'artist.title'))
								continue;

							$strOfferYandex .= "<name>".yandex_text2xml($arOfferItem["NAME"], true)."</name>\n";
							break;
						case 'description':
							$strOfferYandex .= "<description>";
							if (strlen($arOfferItem['~PREVIEW_TEXT']) <= 0)
							{
								$strOfferYandex .= $arItem['YANDEX_DESCR'];
							}
							else
							{
								$strOfferYandex .= yandex_text2xml(TruncateText(
									($arOfferItem["PREVIEW_TEXT_TYPE"]=="html"?
										strip_tags(preg_replace_callback("'&[^;]*;'", "yandex_replace_special", $arOfferItem["~PREVIEW_TEXT"])) : $arOfferItem["~PREVIEW_TEXT"]),
										255),
									true);
							}
							$strOfferYandex .= "</description>\n";
							break;
						case 'param':
							if (is_array($XML_DATA) && is_array($XML_DATA['XML_DATA']) && is_array($XML_DATA['XML_DATA']['PARAMS']))
							{
								foreach ($XML_DATA['XML_DATA']['PARAMS'] as $key => $prop_id)
								{
									$strParamValue = '';
									if ($prop_id)
									{
										$strParamValue = yandex_get_value($arOfferItem, 'PARAM_'.$key, $prop_id);
									}
									if ('' != $strParamValue)
										$strOfferYandex .= $strParamValue."\n";
								}
							}
							break;
						case 'model':
						case 'title':
							if (!is_array($XML_DATA) || !is_array($XML_DATA['XML_DATA']) || !$XML_DATA['XML_DATA'][$key])
							{
								if (
									$key == 'model' && $XML_DATA['TYPE'] == 'vendor.model'
									||
									$key == 'title' && $XML_DATA['TYPE'] == 'artist.title'
								)
								$strOfferYandex .= "<".$key.">".yandex_text2xml($arOfferItem["NAME"], true)."</".$key.">\n";
							}
							else
							{
								$strValue = '';
								$strValue = yandex_get_value($arOfferItem, $key, $XML_DATA['XML_DATA'][$key]);
								if ('' != $strValue)
									$strOfferYandex .= $strValue."\n";
							}
							break;
						case 'year':
							$y++;
							if ($XML_DATA['TYPE'] == 'artist.title')
							{
								if ($y == 1) continue;
							}
							else
							{
								if ($y > 1) continue;
							}
					// no break here
						default:
							if (is_array($XML_DATA) && is_array($XML_DATA['XML_DATA']) && $XML_DATA['XML_DATA'][$key])
							{
								$strValue = '';
								$strValue = yandex_get_value($arOfferItem, $key, $XML_DATA['XML_DATA'][$key]);
								if ('' != $strValue)
									$strOfferYandex .= $strValue."\n";
							}
						}
					}

					$strOfferYandex .= "</offer>\n";
					$arItem['OFFERS'][] = $strOfferYandex;
					$boolItemOffers = true;
					$boolItemExport = true;
				}
			}
			else
			{
				while ($obOfferItem = $rsOfferItems->GetNextElement())
				{
					$arOfferItem = $obOfferItem->GetFields();
					$arOfferItem['PROPERTIES'] = $obOfferItem->GetProperties();
					$arCross = array();
					foreach ($arOfferItem['PROPERTIES'] as $arProp)
					{
						$arCross[$arProp['ID']] = $arProp;
					}
					if (!empty($arItem['PROPERTIES']))
					{
						$arOfferItem['PROPERTIES'] = $arCross + $arItem['PROPERTIES'];
					}
					else
					{
						$arOfferItem['PROPERTIES'] = $arCross;
					}

					$arOfferItem['CATALOG_QUANTITY'] = '';
					$arOfferItem['CATALOG_QUANTITY_TRACE'] = 'N';
					$arProduct = CCatalogProduct::GetByID($arOfferItem['ID']);
					if (!empty($arProduct))
					{
						$arOfferItem['CATALOG_QUANTITY'] = $arProduct['QUANTITY'];
						$arOfferItem['CATALOG_QUANTITY_TRACE'] = $arProduct['QUANTITY_TRACE'];
					}
					$arOfferItem['YANDEX_AVAILABLE'] = 'true';
					$str_QUANTITY = DoubleVal($arOfferItem["CATALOG_QUANTITY"]);
					$str_QUANTITY_TRACE = $arOfferItem["CATALOG_QUANTITY_TRACE"];
					if (($str_QUANTITY <= 0) && ($str_QUANTITY_TRACE == "Y"))
						$arOfferItem['YANDEX_AVAILABLE'] = 'false';

					$minPrice = -1;
					if ($XML_DATA['PRICE'] > 0)
					{
						$rsPrices = CPrice::GetListEx(array(),array(
							'PRODUCT_ID' => $arOfferItem['ID'],
							'CATALOG_GROUP_ID' => $XML_DATA['PRICE'],
							'CAN_BUY' => 'Y',
							'GROUP_GROUP_ID' => array(2),
							'+<=QUANTITY_FROM' => 1,
							'+>=QUANTITY_TO' => 1,
							)
						);
						if ($arPrice = $rsPrices->Fetch())
						{
/*							$dbVAT = CCatalogProduct::GetVATInfo($arOfferItem['ID']);
							if ($arVat = $dbVAT->Fetch())
							{
								$arVat['RATE'] = floatval($arVat['RATE'] * 0.01);
							}
							else
							{
								$arVat = array('RATE' => 0, 'VAT_INCLUDED' => 'N');
							}
							$arPrice['VAT_RATE'] = $arVat['RATE'];
							$arPrice['VAT_INCLUDED'] = $arVat['VAT_INCLUDED'];
							if ($arPrice['VAT_INCLUDED'] == 'N')
							{
								$arPrice['PRICE'] *= (1 + $arPrice['VAT_RATE']);
								$arPrice['VAT_INCLUDED'] = 'Y';
							}
							$arPrice['PRICE'] = roundEx($arPrice['PRICE'], CATALOG_VALUE_PRECISION);
							$arDiscounts = CCatalogDiscount::GetDiscount(
								$arOfferItem['ID'],
								$intOfferIBlockID,
								array($XML_DATA['PRICE']),
								array(2),
								'N',
								$arOfferIBlock['LID'],
								false
							);
							$minPrice = CCatalogProduct::CountPriceWithDiscount($arPrice['PRICE'], $arPrice["CURRENCY"], $arDiscounts);
							$minPriceGroup = $arPrice['CATALOG_GROUP_ID'];
							$minPriceCurrency = $arPrice["CURRENCY"];
							$minPriceRUR = CCurrencyRates::ConvertCurrency($arPrice['PRICE'], $arPrice["CURRENCY"], $RUR); */
							if ($arOptimalPrice = CCatalogProduct::GetOptimalPrice(
								$arOfferItem['ID'],
								1,
								array(2),
								'N',
								array($arPrice),
								$arOfferIBlock['LID']
							))
							{
								$minPrice = $arOptimalPrice['DISCOUNT_PRICE'];
								$minPriceCurrency = $BASE_CURRENCY;
								$minPriceRUR = CCurrencyRates::ConvertCurrency($minPrice, $BASE_CURRENCY, $RUR);
								$minPriceGroup = $arOptimalPrice['PRICE']['CATALOG_GROUP_ID'];
							}

						}
					}
					else
					{
						if ($arPrice = CCatalogProduct::GetOptimalPrice(
							$arOfferItem['ID'],
							1,
							array(2), // anonymous
							'N',
							array(),
							$arOfferIBlock['LID']
						))
						{
							$minPrice = $arPrice['DISCOUNT_PRICE'];
							$minPriceCurrency = $BASE_CURRENCY;
							$minPriceRUR = CCurrencyRates::ConvertCurrency($minPrice, $BASE_CURRENCY, $RUR);
							$minPriceGroup = $arPrice['PRICE']['CATALOG_GROUP_ID'];
						}
					}
					if ($minPrice <= 0)
						continue;

					if (strlen($arOfferItem['DETAIL_PAGE_URL']) <= 0)
						$arOfferItem['DETAIL_PAGE_URL'] = '/';
					else
						$arOfferItem['DETAIL_PAGE_URL'] = str_replace(' ', '%20', $arOfferItem['DETAIL_PAGE_URL']);

					if (is_array($XML_DATA) && $XML_DATA['TYPE'] && $XML_DATA['TYPE'] != 'none')
						$str_TYPE = ' type="'.htmlspecialcharsbx($XML_DATA['TYPE']).'"';
					else
						$str_TYPE = '';

					$arOfferItem['YANDEX_TYPE'] = $str_TYPE;

					$strOfferYandex = '';
					$strOfferYandex .= "<offer id=\"".$arOfferItem["ID"]."\"".$str_TYPE." available=\"".$arOfferItem['YANDEX_AVAILABLE']."\">\n";
					$strOfferYandex .= "<url>http://".$ar_iblock['SERVER_NAME'].htmlspecialcharsbx($arOfferItem["~DETAIL_PAGE_URL"]).(strstr($arOfferItem['DETAIL_PAGE_URL'], '?') === false ? '?' : '&amp;')."r1=<?echo \$strReferer1; ?>&amp;r2=<?echo \$strReferer2; ?></url>\n";

					$strOfferYandex .= "<price>".$minPrice."</price>\n";
					$strOfferYandex .= "<currencyId>".$minPriceCurrency."</currencyId>\n";

					$strOfferYandex .= $arItem['YANDEX_CATEGORY'];

					$strFile = '';
					if (intval($arOfferItem["DETAIL_PICTURE"])>0 || intval($arOfferItem["PREVIEW_PICTURE"])>0)
					{
						$pictNo = intval($arOfferItem["DETAIL_PICTURE"]);
						if ($pictNo<=0)
							$pictNo = intval($arOfferItem["PREVIEW_PICTURE"]);

						if ($ar_file = CFile::GetFileArray($pictNo))
						{
							if(substr($ar_file["SRC"], 0, 1) == "/")
								$strFile = "http://".$ar_iblock['SERVER_NAME'].implode("/", array_map("rawurlencode", explode("/", $ar_file["SRC"])));
							elseif(preg_match("/^(http|https):\\/\\/(.*?)\\/(.*)\$/", $ar_file["SRC"], $match))
								$strFile = "http://".$match[2].'/'.implode("/", array_map("rawurlencode", explode("/", $match[3])));
							else
								$strFile = $ar_file["SRC"];
						}
					}
					if (!empty($strFile) || !empty($arItem['YANDEX_PICT']))
					{
						$strOfferYandex .= "<picture>".(!empty($strFile) ? $strFile : $arItem['YANDEX_PICT'])."</picture>\n";
					}

					$y = 0;
					foreach ($arYandexFields as $key)
					{
						switch ($key)
						{
						case 'name':
							if (is_array($XML_DATA) && ($XML_DATA['TYPE'] == 'vendor.model' || $XML_DATA['TYPE'] == 'artist.title'))
								continue;

							$strOfferYandex .= "<name>".yandex_text2xml($arOfferItem["NAME"], true)."</name>\n";
							break;
						case 'description':
							$strOfferYandex .= "<description>";
							if (strlen($arOfferItem['~PREVIEW_TEXT']) <= 0)
							{
								$strOfferYandex .= $arItem['YANDEX_DESCR'];
							}
							else
							{
								$strOfferYandex .= yandex_text2xml(TruncateText(
									($arOfferItem["PREVIEW_TEXT_TYPE"]=="html"?
										strip_tags(preg_replace_callback("'&[^;]*;'", "yandex_replace_special", $arOfferItem["~PREVIEW_TEXT"])) : preg_replace_callback("'&[^;]*;'", "yandex_replace_special", $arOfferItem["~PREVIEW_TEXT"])),
										255),
									true);
							}
							$strOfferYandex .= "</description>\n";
							break;
						case 'param':
							if (is_array($XML_DATA) && is_array($XML_DATA['XML_DATA']) && is_array($XML_DATA['XML_DATA']['PARAMS']))
							{
								foreach ($XML_DATA['XML_DATA']['PARAMS'] as $key => $prop_id)
								{
									$strParamValue = '';
									if ($prop_id)
									{
										$strParamValue = yandex_get_value($arOfferItem, 'PARAM_'.$key, $prop_id);
									}
									if ('' != $strParamValue)
										$strOfferYandex .= $strParamValue."\n";
								}
							}
							break;
						case 'model':
						case 'title':
							if (!is_array($XML_DATA) || !is_array($XML_DATA['XML_DATA']) || !$XML_DATA['XML_DATA'][$key])
							{
								if (
									$key == 'model' && $XML_DATA['TYPE'] == 'vendor.model'
									||
									$key == 'title' && $XML_DATA['TYPE'] == 'artist.title'
								)
								$strOfferYandex .= "<".$key.">".yandex_text2xml($arOfferItem["NAME"], true)."</".$key.">\n";
							}
							else
							{
								$strValue = '';
								$strValue = yandex_get_value($arOfferItem, $key, $XML_DATA['XML_DATA'][$key]);
								if ('' != $strValue)
									$strOfferYandex .= $strValue."\n";
							}
							break;
						case 'year':
							$y++;
							if ($XML_DATA['TYPE'] == 'artist.title')
							{
								if ($y == 1) continue;
							}
							else
							{
								if ($y > 1) continue;
							}
					// no break here
						default:
							if (is_array($XML_DATA) && is_array($XML_DATA['XML_DATA']) && $XML_DATA['XML_DATA'][$key])
							{
								$strValue = '';
								$strValue = yandex_get_value($arOfferItem, $key, $XML_DATA['XML_DATA'][$key]);
								if ('' != $strValue)
									$strOfferYandex .= $strValue."\n";
							}
						}
					}

					$strOfferYandex .= "</offer>\n";
					$arItem['OFFERS'][] = $strOfferYandex;
					$boolItemOffers = true;
					$boolItemExport = true;
				}
			}
			if ('X' == $arCatalog['CATALOG_TYPE'] && !$boolItemOffers)
			{
				$arItem['CATALOG_QUANTITY'] = '';
				$arItem['CATALOG_QUANTITY_TRACE'] = 'N';
				$arProduct = CCatalogProduct::GetByID($arItem['ID']);
				if (!empty($arProduct))
				{
					$arItem['CATALOG_QUANTITY'] = $arProduct['QUANTITY'];
					$arItem['CATALOG_QUANTITY_TRACE'] = $arProduct['QUANTITY_TRACE'];
				}
				$str_QUANTITY = DoubleVal($arItem["CATALOG_QUANTITY"]);
				$str_QUANTITY_TRACE = $arItem["CATALOG_QUANTITY_TRACE"];
				if (($str_QUANTITY <= 0) && ($str_QUANTITY_TRACE == "Y"))
					$str_AVAILABLE = ' available="false"';
				else
					$str_AVAILABLE = ' available="true"';

				$minPrice = 0;
				$minPriceRUR = 0;
				$minPriceGroup = 0;
				$minPriceCurrency = "";

				if ($XML_DATA['PRICE'] > 0)
				{
					$rsPrices = CPrice::GetListEx(array(),array(
						'PRODUCT_ID' => $arItem['ID'],
						'CATALOG_GROUP_ID' => $XML_DATA['PRICE'],
						'CAN_BUY' => 'Y',
						'GROUP_GROUP_ID' => array(2),
						'+<=QUANTITY_FROM' => 1,
						'+>=QUANTITY_TO' => 1,
						)
					);
					if ($arPrice = $rsPrices->Fetch())
					{
/*						$dbVAT = CCatalogProduct::GetVATInfo($arItem['ID']);
						if ($arVat = $dbVAT->Fetch())
						{
							$arVat['RATE'] = floatval($arVat['RATE'] * 0.01);
						}
						else
						{
							$arVat = array('RATE' => 0, 'VAT_INCLUDED' => 'N');
						}
						$arPrice['VAT_RATE'] = $arVat['RATE'];
						$arPrice['VAT_INCLUDED'] = $arVat['VAT_INCLUDED'];
						if ($arPrice['VAT_INCLUDED'] == 'N')
						{
							$arPrice['PRICE'] *= (1 + $arPrice['VAT_RATE']);
							$arPrice['VAT_INCLUDED'] = 'Y';
						}
						$arPrice['PRICE'] = roundEx($arPrice['PRICE'], CATALOG_VALUE_PRECISION);
						$arDiscounts = CCatalogDiscount::GetDiscount(
							$arItem['ID'],
							$IBLOCK_ID,
							array($XML_DATA['PRICE']),
							array(2),
							'N',
							$ar_iblock['LID'],
							false
						);
						$minPrice = CCatalogProduct::CountPriceWithDiscount($arPrice['PRICE'], $arPrice["CURRENCY"], $arDiscounts);
						$minPriceGroup = $arPrice['CATALOG_GROUP_ID'];
						$minPriceCurrency = $arPrice["CURRENCY"];
						$minPriceRUR = CCurrencyRates::ConvertCurrency($arPrice['PRICE'], $arPrice["CURRENCY"], $RUR); */
						if ($arOptimalPrice = CCatalogProduct::GetOptimalPrice(
							$arItem['ID'],
							1,
							array(2),
							'N',
							array($arPrice),
							$ar_iblock['LID']
						))
						{
							$minPrice = $arOptimalPrice['DISCOUNT_PRICE'];
							$minPriceCurrency = $BASE_CURRENCY;
							$minPriceRUR = CCurrencyRates::ConvertCurrency($minPrice, $BASE_CURRENCY, $RUR);
							$minPriceGroup = $arOptimalPrice['PRICE']['CATALOG_GROUP_ID'];
						}
					}
				}
				else
				{
					if ($arPrice = CCatalogProduct::GetOptimalPrice(
						$arItem['ID'],
						1,
						array(2), // anonymous
						'N',
						array(),
						$ar_iblock['LID']
					))
					{
						$minPrice = $arPrice['DISCOUNT_PRICE'];
						$minPriceCurrency = $BASE_CURRENCY;
						$minPriceRUR = CCurrencyRates::ConvertCurrency($minPrice, $BASE_CURRENCY, $RUR);
						$minPriceGroup = $arPrice['PRICE']['CATALOG_GROUP_ID'];
					}
				}

				if ($minPrice <= 0) continue;

				if ('' == $arItem['DETAIL_PAGE_URL'])
				{
					$arItem['DETAIL_PAGE_URL'] = '/';
				}
				else
				{
					$arItem['DETAIL_PAGE_URL'] = str_replace(' ', '%20', $arItem['DETAIL_PAGE_URL']);
				}
				if ('' == $arItem['~DETAIL_PAGE_URL'])
				{
					$arItem['~DETAIL_PAGE_URL'] = '/';
				}
				else
				{
					$arItem['~DETAIL_PAGE_URL'] = str_replace(' ', '%20', $arItem['~DETAIL_PAGE_URL']);
				}

				if (is_array($XML_DATA) && $XML_DATA['TYPE'] && $XML_DATA['TYPE'] != 'none')
					$str_TYPE = ' type="'.htmlspecialcharsbx($XML_DATA['TYPE']).'"';
				else
					$str_TYPE = '';

				$strOfferYandex = '';
				$strOfferYandex.= "<offer id=\"".$arItem["ID"]."\"".$str_TYPE.$str_AVAILABLE.">\n";
				$strOfferYandex.= "<url>http://".$ar_iblock['SERVER_NAME'].htmlspecialcharsbx($arItem["~DETAIL_PAGE_URL"]).(strstr($arItem['DETAIL_PAGE_URL'], '?') === false ? '?' : '&amp;')."r1=<?echo \$strReferer1; ?>&amp;r2=<?echo \$strReferer2; ?></url>\n";

				$strOfferYandex.= "<price>".$minPrice."</price>\n";
				$strOfferYandex.= "<currencyId>".$minPriceCurrency."</currencyId>\n";

				$strOfferYandex.= $arItem['YANDEX_CATEGORY'];

				if (!empty($arItem['YANDEX_PICT']))
				{
					$strOfferYandex .= "<picture>".$arItem['YANDEX_PICT']."</picture>\n";
				}

				$y = 0;
				foreach ($arYandexFields as $key)
				{
					$strValue = '';
					switch ($key)
					{
					case 'name':
						if (is_array($XML_DATA) && ($XML_DATA['TYPE'] == 'vendor.model' || $XML_DATA['TYPE'] == 'artist.title'))
							continue;

						$strValue = "<name>".yandex_text2xml($arItem["NAME"], true)."</name>\n";
						break;
					case 'description':
						$strValue =
							"<description>".
							yandex_text2xml(TruncateText(
								($arItem["PREVIEW_TEXT_TYPE"]=="html"?
								strip_tags(preg_replace_callback("'&[^;]*;'", "yandex_replace_special", $arItem["~PREVIEW_TEXT"])) : preg_replace_callback("'&[^;]*;'", "yandex_replace_special", $arItem["~PREVIEW_TEXT"])),
								255), true).
							"</description>\n";
						break;
					case 'param':
						if (is_array($XML_DATA) && is_array($XML_DATA['XML_DATA']) && is_array($XML_DATA['XML_DATA']['PARAMS']))
						{
							foreach ($XML_DATA['XML_DATA']['PARAMS'] as $key => $prop_id)
							{
								$strParamValue = '';
								if ($prop_id)
								{
									$strParamValue = yandex_get_value($arItem, 'PARAM_'.$key, $prop_id);
								}
								if ('' != $strParamValue)
									$strValue .= $strParamValue."\n";
							}
						}
						break;
					case 'model':
					case 'title':
						if (!is_array($XML_DATA) || !is_array($XML_DATA['XML_DATA']) || !$XML_DATA['XML_DATA'][$key])
						{
							if (
								$key == 'model' && $XML_DATA['TYPE'] == 'vendor.model'
								||
								$key == 'title' && $XML_DATA['TYPE'] == 'artist.title'
							)

							$strValue = "<".$key.">".yandex_text2xml($arItem["NAME"], true)."</".$key.">\n";
						}
						else
						{
							$strValue = yandex_get_value($arItem, $key, $XML_DATA['XML_DATA'][$key]);
							if ('' != $strValue)
								$strValue .= "\n";
						}
						break;
					case 'year':
						$y++;
						if ($XML_DATA['TYPE'] == 'artist.title')
						{
							if ($y == 1) continue;
						}
						else
						{
							if ($y > 1) continue;
						}

					// no break here

					default:
						if (is_array($XML_DATA) && is_array($XML_DATA['XML_DATA']) && $XML_DATA['XML_DATA'][$key])
						{
							$strValue = yandex_get_value($arItem, $key, $XML_DATA['XML_DATA'][$key]);
							if ('' != $strValue)
								$strValue .= "\n";
						}
					}
					if ('' != $strValue)
						$strOfferYandex .= $strValue;
				}

				$strOfferYandex .= "</offer>\n";

				if ('' != $strOfferYandex)
				{
					$arItem['OFFERS'][] = $strOfferYandex;
					$boolItemOffers = true;
					$boolItemExport = true;
				}
			}
			if (!$boolItemExport)
				continue;
			foreach ($arItem['OFFERS'] as $strOfferItem)
			{
				$strTmpOff .= $strOfferItem;
			}
		}
	}

	@fwrite($fp, "<categories>\n");
	if (true == $boolNeedRootSection)
	{
		$strTmpCat .= "<category id=\"".$intMaxSectionID."\">".yandex_text2xml(GetMessage('YANDEX_ROOT_DIRECTORY'), true)."</category>\n";
	}
	@fwrite($fp, $strTmpCat);
	@fwrite($fp, "</categories>\n");

	@fwrite($fp, "<offers>\n");
	@fwrite($fp, $strTmpOff);
	@fwrite($fp, "</offers>\n");

	@fwrite($fp, "</shop>\n");
	@fwrite($fp, "</yml_catalog>\n");

	@fclose($fp);
}

CCatalogDiscountSave::Enable();

if (!empty($arRunErrors))
	$strExportErrorMessage = implode('<br />',$arRunErrors);

if ($bTmpUserCreated)
{
	unset($USER);
	if (isset($USER_TMP))
	{
		$USER = $USER_TMP;
		unset($USER_TMP);
	}
}
?>