<?
use Bitrix\Currency;

global $APPLICATION;
set_time_limit(0);

global $USER;
$bTmpUserCreated = false;
if (!CCatalog::IsUserExists())
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
CCatalogDiscountCoupon::ClearCoupon();
if ($USER->IsAuthorized())
{
	CCatalogDiscountCoupon::ClearCouponsByManage($USER->GetID());
}

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
		$text = preg_replace('/[\x01-\x08\x0B-\x0C\x0E-\x1F]/', "", $text);
		$text = str_replace("'", "&apos;", $text);
		$text = $APPLICATION->ConvertCharset($text, LANG_CHARSET, 'windows-1251');
		return $text;
	}
}

$strAll = '<?if (!isset($_GET["referer1"]) || strlen($_GET["referer1"])<=0) $_GET["referer1"] = "yandext"?>';
$strAll .= '<? $strReferer1 = htmlspecialchars($_GET["referer1"]); ?>';
$strAll .= '<?if (!isset($_GET["referer2"]) || strlen($_GET["referer2"])<=0) $_GET["referer2"] = "";?>';
$strAll .= '<? $strReferer2 = htmlspecialchars($_GET["referer2"]); ?>';
$strAll .= '<? header("Content-Type: text/xml; charset=windows-1251");?>';
$strAll.= '<?echo "<?xml version=\"1.0\" encoding=\"windows-1251\"?>"?>';
$strAll.= "\n<!DOCTYPE yml_catalog SYSTEM \"shops.dtd\">\n";
$strAll.= "<yml_catalog date=\"".Date("Y-m-d H:i")."\">\n";
$strAll.= "<shop>\n";
$strAll.= "<name>".$APPLICATION->ConvertCharset(htmlspecialcharsbx(COption::GetOptionString("main", "site_name", "")), LANG_CHARSET, 'windows-1251')."</name>\n";
$strAll.= "<company>".$APPLICATION->ConvertCharset(htmlspecialcharsbx(COption::GetOptionString("main", "site_name", "")), LANG_CHARSET, 'windows-1251')."</company>\n";
$strAll.= "<url>http://".htmlspecialcharsbx(COption::GetOptionString("main", "server_name", ""))."</url>\n";
$strAll.= "<platform>1C-Bitrix</platform>\n";

//*****************************************//

$BASE_CURRENCY = Currency\CurrencyManager::getBaseCurrency();
$RUR = 'RUB';
$currencyIterator = Currency\CurrencyTable::getList(array(
	'select' => array('CURRENCY'),
	'filter' => array('=CURRENCY' => 'RUR')
));
if ($currency = $currencyIterator->fetch())
	$RUR = 'RUR';
unset($currency, $currencyIterator);
$arCurrencyAllowed = array('RUR', 'RUB', 'USD', 'EUR', 'UAH', 'BYR', 'KZT');
$strTmp = "<currencies>\n";
$currencyIterator = Currency\CurrencyTable::getList(array(
	'select' => array('CURRENCY'),
	'filter' => array('@CURRENCY' => $arCurrencyAllowed),
	'order' => array('SORT' => 'ASC')
));
while ($currency = $currencyIterator->fetch())
	$strTmp.= '<currency id="'.$currency['CURRENCY'].'" rate="'.(CCurrencyRates::ConvertCurrency(1, $currency['CURRENCY'], $RUR)).'" />'."\n";
unset($currency, $currencyIterator);
$strTmp.= "</currencies>\n";

$strAll.= $strTmp;
unset($strTmp);

//*****************************************//

$arSelect = array("ID", "LID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "ACTIVE", "NAME", "PREVIEW_PICTURE", "PREVIEW_TEXT", "PREVIEW_TEXT_TYPE", "DETAIL_PICTURE", "LANG_DIR", "DETAIL_PAGE_URL");

$strTmpCat = "";
$strTmpOff = "";

$arSiteServers = array();

$db_catalog_list = CCatalog::GetList(array(), array("YANDEX_EXPORT" => "Y", "PRODUCT_IBLOCK_ID" => 0), false, false, array('IBLOCK_ID'));
while ($arCatalog_list = $db_catalog_list->Fetch())
{
	$arCatalog_list['IBLOCK_ID'] = intval($arCatalog_list['IBLOCK_ID']);
	$arIBlock = CIBlock::GetArrayByID($arCatalog_list['IBLOCK_ID']);
	if (empty($arIBlock) || !is_array($arIBlock))
		continue;
	if ('Y' != $arIBlock['ACTIVE'])
		continue;
	$boolRights = false;
	if ('E' != $arIBlock['RIGHTS_MODE'])
	{
		$arRights = CIBlock::GetGroupPermissions($arCatalog_list['IBLOCK_ID']);
		if (!empty($arRights) && isset($arRights[2]) && 'R' <= $arRights[2])
			$boolRights = true;
	}
	else
	{
		$obRights = new CIBlockRights($arCatalog_list['IBLOCK_ID']);
		$arRights = $obRights->GetGroups(array('section_read', 'element_read'));
		if (!empty($arRights) && in_array('G2',$arRights))
			$boolRights = true;
	}
	if (!$boolRights)
		continue;

	$filter = array("IBLOCK_ID"=>$arCatalog_list["IBLOCK_ID"], "ACTIVE"=>"Y", "GLOBAL_ACTIVE"=>"Y");
	$db_acc = CIBlockSection::GetList(array("left_margin"=>"asc"), $filter);

	$arAvailGroups = array();
	while ($arAcc = $db_acc->Fetch())
	{
		$strTmpCat.= "<category id=\"".$arAcc["ID"]."\"".(IntVal($arAcc["IBLOCK_SECTION_ID"])>0?" parentId=\"".$arAcc["IBLOCK_SECTION_ID"]."\"":"").">".yandex_text2xml($arAcc["NAME"], true)."</category>\n";
		$arAvailGroups[] = IntVal($arAcc["ID"]);
	}

	//*****************************************//

	$filter = array("IBLOCK_ID"=>$arCatalog_list["IBLOCK_ID"], "ACTIVE"=>"Y", "ACTIVE_DATE"=>"Y");
	$db_acc = CIBlockElement::GetList(array(), $filter, false, false, $arSelect);

	$total_sum=0;
	$is_exists=false;
	$cnt=0;

	while ($arAcc = $db_acc->GetNext())
	{
		$cnt++;
		if (!array_key_exists($arAcc['LID'], $arSiteServers))
		{
			$b="sort";
			$o="asc";
			$rsSite = CSite::GetList($b, $o, array("LID" => $arAcc["LID"]));
			if($arSite = $rsSite->Fetch())
				$arAcc["SERVER_NAME"] = $arSite["SERVER_NAME"];
			if(strlen($arAcc["SERVER_NAME"])<=0 && defined("SITE_SERVER_NAME"))
				$arAcc["SERVER_NAME"] = SITE_SERVER_NAME;
			if(strlen($arAcc["SERVER_NAME"])<=0)
				$arAcc["SERVER_NAME"] = COption::GetOptionString("main", "server_name", "");

			$arSiteServers[$arAcc['LID']] = $arAcc['SERVER_NAME'];
		}
		else
		{
			$arAcc['SERVER_NAME'] = $arSiteServers[$arAcc['LID']];
		}
		$arAcc['CATALOG_QUANTITY'] = '';
		$arAcc['CATALOG_QUANTITY_TRACE'] = 'N';
		$rsProducts = CCatalogProduct::GetList(
			array(),
			array('ID' => $arAcc['ID']),
			false,
			false,
			array('ID', 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO')
		);
		if ($arProduct = $rsProducts->Fetch())
		{
			$arAcc['CATALOG_QUANTITY'] = $arProduct['QUANTITY'];
			$arAcc['CATALOG_QUANTITY_TRACE'] = $arProduct['QUANTITY_TRACE'];
		}

		$str_QUANTITY = doubleval($arAcc["CATALOG_QUANTITY"]);
		$str_QUANTITY_TRACE = $arAcc["CATALOG_QUANTITY_TRACE"];
		if (($str_QUANTITY <= 0) && ($str_QUANTITY_TRACE == "Y"))
			$str_AVAILABLE = ' available="false"';
		else
			$str_AVAILABLE = ' available="true"';


		$minPrice = 0;
		$minPriceRUR = 0;
		$minPriceGroup = 0;
		$minPriceCurrency = "";

		if ($arPrice = CCatalogProduct::GetOptimalPrice(
			$arAcc['ID'],
			1,
			array(2), // anonymous
			'N',
			array(),
			$arIBlock['LID'],
			array()
		))
		{
			$minPrice = $arPrice['DISCOUNT_PRICE'];
			$minPriceCurrency = $BASE_CURRENCY;
			if ($BASE_CURRENCY != $RUR)
			{
				$minPriceRUR = CCurrencyRates::ConvertCurrency($minPrice, $BASE_CURRENCY, $RUR);
			}
			else
			{
				$minPriceRUR = $minPrice;
			}
			$minPriceGroup = $arPrice['PRICE']['CATALOG_GROUP_ID'];
		}

		if ($minPrice <= 0) continue;

		$bNoActiveGroup = True;
		$strTmpOff_tmp = "";
		$db_res1 = CIBlockElement::GetElementGroups($arAcc["ID"], false, array('ID', 'ADDITIONAL_PROPERTY_ID'));
		while ($ar_res1 = $db_res1->Fetch())
		{
			if (0 < intval($ar_res1['ADDITIONAL_PROPERTY_ID']))
				continue;
			$strTmpOff_tmp.= "<categoryId>".$ar_res1["ID"]."</categoryId>\n";
			if ($bNoActiveGroup && in_array(intval($ar_res1["ID"]), $arAvailGroups))
			{
				$bNoActiveGroup = False;
			}
		}
		if ($bNoActiveGroup) continue;

		if ('' == $arAcc['DETAIL_PAGE_URL'])
		{
			$arAcc['DETAIL_PAGE_URL'] = '/';
		}
		else
		{
			$arAcc['DETAIL_PAGE_URL'] = str_replace(' ', '%20', $arAcc['DETAIL_PAGE_URL']);
		}
		if ('' == $arAcc['~DETAIL_PAGE_URL'])
		{
			$arAcc['~DETAIL_PAGE_URL'] = '/';
		}
		else
		{
			$arAcc['~DETAIL_PAGE_URL'] = str_replace(' ', '%20', $arAcc['~DETAIL_PAGE_URL']);
		}

		$strTmpOff.= "<offer id=\"".$arAcc["ID"]."\"".$str_AVAILABLE.">\n";
		$strTmpOff.= "<url>http://".$arAcc['SERVER_NAME'].htmlspecialcharsbx($arAcc["~DETAIL_PAGE_URL"]).(strstr($arAcc['DETAIL_PAGE_URL'], '?') === false ? '?' : '&amp;')."r1=<?echo \$strReferer1; ?>&amp;r2=<?echo \$strReferer2; ?></url>\n";

		$strTmpOff.= "<price>".$minPrice."</price>\n";
		$strTmpOff.= "<currencyId>".$minPriceCurrency."</currencyId>\n";

		$strTmpOff.= $strTmpOff_tmp;

		if (IntVal($arAcc["DETAIL_PICTURE"])>0 || IntVal($arAcc["PREVIEW_PICTURE"])>0)
		{
			$pictNo = IntVal($arAcc["DETAIL_PICTURE"]);
			if ($pictNo<=0) $pictNo = IntVal($arAcc["PREVIEW_PICTURE"]);

			$arPictInfo = CFile::GetFileArray($pictNo);
			if (is_array($arPictInfo))
			{
				if(substr($arPictInfo["SRC"], 0, 1) == "/")
					$strFile = "http://".$arAcc['SERVER_NAME'].CHTTP::urnEncode($arPictInfo["SRC"], 'utf-8');
				else
					$strFile = $arPictInfo["SRC"];
				$strTmpOff.="<picture>".$strFile."</picture>\n";
			}
		}

		$strTmpOff.= "<name>".yandex_text2xml($arAcc["~NAME"], true)."</name>\n";
		$strTmpOff.=
			"<description>".
			yandex_text2xml(TruncateText(
				($arAcc["PREVIEW_TEXT_TYPE"]=="html"?
				strip_tags(preg_replace_callback("'&[^;]*;'", "yandex_replace_special", $arAcc["~PREVIEW_TEXT"])) : preg_replace_callback("'&[^;]*;'", "yandex_replace_special", $arAcc["~PREVIEW_TEXT"])),
				255), true).
			"</description>\n";
		$strTmpOff.= "</offer>\n";
		if (100 <= $cnt)
		{
			$cnt = 0;
			CCatalogDiscount::ClearDiscountCache(array(
				'PRODUCT' => true,
				'SECTIONS' => true,
				'PROPERTIES' => true
			));
		}
	}
}

$strAll.= "<categories>\n";
$strAll.= $strTmpCat;
$strAll.= "</categories>\n";

$strAll.= "<offers>\n";
$strAll.= $strTmpOff;
$strAll.= "</offers>\n";

$strAll.= "</shop>\n";
$strAll.= "</yml_catalog>\n";

$boolError = true;
$strExportPath = COption::GetOptionString("catalog", "export_default_path", CATALOG_DEFAULT_EXPORT_PATH);
$strYandexPath = Rel2Abs('/',str_replace('//','/',$strExportPath."/yandex.php"));
if (!empty($strYandexPath))
{
	CheckDirPath($_SERVER["DOCUMENT_ROOT"].$strExportPath);

	if ($fp = @fopen($_SERVER["DOCUMENT_ROOT"].$strYandexPath, 'wb'))
	{
		fwrite($fp, $strAll);
		fclose($fp);
		$boolError = false;
	}
}

if ($boolError)
{
	CEventLog::Log('WARNING','CAT_YAND_AGENT','catalog','YandexAgent',$strYandexPath);
}

CCatalogDiscountSave::Enable();

if ($bTmpUserCreated)
{
	unset($USER);
	if (isset($USER_TMP))
	{
		$USER = $USER_TMP;
		unset($USER_TMP);
	}
}