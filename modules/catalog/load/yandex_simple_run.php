<?
//<title>Yandex simple</title>
/** @global CUser $USER */
/** @global CMain $APPLICATION */
use Bitrix\Main,
	Bitrix\Currency,
	Bitrix\Iblock;

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/catalog/export_setup_templ.php');
set_time_limit(0);

global $USER, $APPLICATION;
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

function yandex_replace_special($arg)
{
	if (in_array($arg[0], array("&quot;", "&amp;", "&lt;", "&gt;")))
		return $arg[0];
	else
		return " ";
}

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

$strExportErrorMessage = '';

$usedProtocol = (isset($USE_HTTPS) && $USE_HTTPS == 'Y' ? 'https://' : 'http://');

$SETUP_SERVER_NAME = trim($SETUP_SERVER_NAME);
if (strlen($SETUP_FILE_NAME)<=0)
{
	$strExportErrorMessage .= GetMessage("CET_ERROR_NO_FILENAME")."<br>";
}
elseif (preg_match(BX_CATALOG_FILENAME_REG,$SETUP_FILE_NAME))
{
	$strExportErrorMessage .= GetMessage("CES_ERROR_BAD_EXPORT_FILENAME")."<br>";
}

if ($strExportErrorMessage == '')
{
	$SETUP_FILE_NAME = Rel2Abs("/", $SETUP_FILE_NAME);

/*	if ($APPLICATION->GetFileAccessPermission($SETUP_FILE_NAME) < "W")
	{
		$strExportErrorMessage .= str_replace("#FILE#", $SETUP_FILE_NAME, GetMessage('CET_YAND_RUN_ERR_SETUP_FILE_ACCESS_DENIED'))."\n";
	} */
}

if ($strExportErrorMessage == '')
{
	if (!$fp = @fopen($_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME, "wb"))
	{
		$strExportErrorMessage .= str_replace('#FILE#',$_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME, GetMessage('CET_YAND_RUN_ERR_SETUP_FILE_OPEN_WRITING'))."\n";
	}
	else
	{
		if (!@fwrite($fp, '<?if (!isset($_GET["referer1"]) || strlen($_GET["referer1"])<=0) $_GET["referer1"] = "yandext"?>'))
		{
			$strExportErrorMessage .= str_replace('#FILE#',$_SERVER["DOCUMENT_ROOT"].$SETUP_FILE_NAME, GetMessage('CET_YAND_RUN_ERR_SETUP_FILE_WRITE'))."\n";
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

if ($strExportErrorMessage == '')
{
	fwrite($fp, '<? header("Content-Type: text/xml; charset=windows-1251");?>');
	fwrite($fp, '<? echo "<"."?xml version=\"1.0\" encoding=\"windows-1251\"?".">"?>');
	fwrite($fp, "\n<!DOCTYPE yml_catalog SYSTEM \"shops.dtd\">\n");
	fwrite($fp, "<yml_catalog date=\"".Date("Y-m-d H:i")."\">\n");
	fwrite($fp, "<shop>\n");
	fwrite($fp, "<name>".$APPLICATION->ConvertCharset(htmlspecialcharsbx(COption::GetOptionString("main", "site_name", "")), LANG_CHARSET, 'windows-1251')."</name>\n");
	fwrite($fp, "<company>".$APPLICATION->ConvertCharset(htmlspecialcharsbx(COption::GetOptionString("main", "site_name", "")), LANG_CHARSET, 'windows-1251')."</company>\n");
	fwrite($fp, "<url>".$usedProtocol.htmlspecialcharsbx(strlen($SETUP_SERVER_NAME) > 0 ? $SETUP_SERVER_NAME : COption::GetOptionString("main", "server_name", ""))."</url>\n");
	fwrite($fp, "<platform>1C-Bitrix</platform>\n");

	$RUR = 'RUB';
	$currencyIterator = Currency\CurrencyTable::getList(array(
		'select' => array('CURRENCY'),
		'filter' => array('=CURRENCY' => 'RUR')
	));
	if ($currency = $currencyIterator->fetch())
		$RUR = 'RUR';
	unset($currency, $currencyIterator);

	$allowedCurrency = array('RUR', 'RUB', 'USD', 'EUR', 'UAH', 'BYR', 'KZT');
	$strTmp = "<currencies>\n";
	$currencyIterator = Currency\CurrencyTable::getList(array(
		'select' => array('CURRENCY'),
		'filter' => array('@CURRENCY' => $allowedCurrency),
		'order' => array('SORT' => 'ASC', 'CURRENCY' => 'ASC')
	));
	while ($currency = $currencyIterator->fetch())
		$strTmp .= '<currency id="'.$currency['CURRENCY'].'" rate="'.(CCurrencyRates::ConvertCurrency(1, $currency['CURRENCY'], $RUR)).'" />'."\n";
	unset($currency, $currencyIterator, $allowedCurrency);

	$strTmp .= "</currencies>\n";

	fwrite($fp, $strTmp);
	unset($strTmp);

	//*****************************************//

	$arSelect = array("ID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "NAME", "PREVIEW_PICTURE", "PREVIEW_TEXT", "PREVIEW_TEXT_TYPE", "DETAIL_PICTURE", "DETAIL_PAGE_URL", 'CATALOG_AVAILABLE');
	$db_res = CCatalogGroup::GetGroupsList(array("GROUP_ID" => 2));
	$arPTypes = array();
	while ($ar_res = $db_res->Fetch())
	{
		if (!in_array($ar_res["CATALOG_GROUP_ID"], $arPTypes))
		{
			$arPTypes[] = $ar_res["CATALOG_GROUP_ID"];
			$arSelect[] = "CATALOG_GROUP_".$ar_res["CATALOG_GROUP_ID"];
		}
	}

	$strTmpCat = "";
	$strTmpOff = "";

	if (is_array($YANDEX_EXPORT))
	{
		$arSiteServers = array();

		$intMaxSectionID = 0;
		$sectionIterator = Iblock\SectionTable::getList(array(
			'select' => array(
				new Main\Entity\ExpressionField('MAX_ID', 'MAX(%s)', array('ID'))
			)
		));
		if ($section = $sectionIterator->fetch())
			$intMaxSectionID = (int)$section['MAX_ID'];
		unset($section, $sectionIterator);
		$intMaxSectionID += 100000000;
		$maxSections = array();

		foreach ($YANDEX_EXPORT as $ykey => $yvalue)
		{
			$boolNeedRootSection = false;

			$yvalue = (int)$yvalue;
			if ($yvalue <= 0)
				continue;

			$filter = array("IBLOCK_ID" => $yvalue, "ACTIVE" => "Y", "IBLOCK_ACTIVE" => "Y", "GLOBAL_ACTIVE" => "Y");
			$db_acc = CIBlockSection::GetList(array("LEFT_MARGIN" => "ASC"), $filter, false, array('ID', 'IBLOCK_SECTION_ID', 'NAME'));

			$arAvailGroups = array();
			while ($arAcc = $db_acc->Fetch())
			{
				$arAcc['ID'] = (int)$arAcc['ID'];
				$arAcc['IBLOCK_SECTION_ID'] = (int)$arAcc['IBLOCK_SECTION_ID'];
				$strTmpCat.= '<category id="'.$arAcc['ID'].'"'.($arAcc['IBLOCK_SECTION_ID'] > 0 ? ' parentId="'.$arAcc['IBLOCK_SECTION_ID'].'"' : '').'>'.yandex_text2xml($arAcc['NAME'], true).'</category>'."\n";
				$arAvailGroups[] = $arAcc['ID'];
			}

			//*****************************************//

			$filter = array("IBLOCK_ID" => $yvalue, "ACTIVE_DATE" => "Y", "ACTIVE" => "Y");
			$res = CIBlockElement::GetList(array('ID' => 'ASC'), $filter, false, false, $arSelect);

			$total_sum=0;
			$is_exists=false;
			$cnt=0;

			while ($arAcc = $res->GetNext())
			{
				$cnt++;
				if (strlen($SETUP_SERVER_NAME) <= 0)
				{
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
				}
				else
				{
					$arAcc['SERVER_NAME'] = $SETUP_SERVER_NAME;
				}

				$str_AVAILABLE = ($arAcc['CATALOG_AVAILABLE'] == 'Y' ? ' available="true"' : ' available="false"');

				$minPrice = 0;
				$minPriceRUR = 0;
				$minPriceGroup = 0;
				$minPriceCurrency = "";
				for ($i = 0, $intCount = count($arPTypes); $i < $intCount; $i++)
				{
					if (strlen($arAcc["CATALOG_CURRENCY_".$arPTypes[$i]])<=0) continue;

					$tmpPrice = CCurrencyRates::ConvertCurrency($arAcc["CATALOG_PRICE_".$arPTypes[$i]], $arAcc["CATALOG_CURRENCY_".$arPTypes[$i]], $RUR);
					if ($minPriceRUR<=0 || $minPriceRUR>$tmpPrice)
					{
						$minPriceRUR = $tmpPrice;
						$minPrice = $arAcc["CATALOG_PRICE_".$arPTypes[$i]];
						$minPriceGroup = $arPTypes[$i];
						$minPriceCurrency = $arAcc["CATALOG_CURRENCY_".$arPTypes[$i]];
						if ($minPriceCurrency!="USD" && $minPriceCurrency!=$RUR)
						{
							$minPriceCurrency = $RUR;
							$minPrice = $tmpPrice;
						}
					}
				}

				if ($minPrice <= 0) continue;

				$currentSection = false;
				$bNoActiveGroup = true;
				$strTmpOff_tmp = "";
				$db_res1 = CIBlockElement::GetElementGroups($arAcc["ID"], false, array('ID', 'ADDITIONAL_PROPERTY_ID'));
				while ($ar_res1 = $db_res1->Fetch())
				{
					$ar_res1['ID'] = (int)$ar_res1['ID'];
					$ar_res1['ADDITIONAL_PROPERTY_ID'] = (int)$ar_res1['ADDITIONAL_PROPERTY_ID'];
					if ($ar_res1['ADDITIONAL_PROPERTY_ID'] > 0)
						continue;
					$currentSection = true;
					if (in_array($ar_res1["ID"], $arAvailGroups))
					{
						$strTmpOff_tmp.= "<categoryId>".$ar_res1["ID"]."</categoryId>\n";
						$bNoActiveGroup = false;
					}
				}

				if (!$currentSection)
				{
					$boolNeedRootSection = true;
					if (!isset($maxSections[$yvalue]))
						$maxSections[$yvalue] = $intMaxSectionID + $yvalue;
					$strTmpOff_tmp.= '<categoryId>'.$maxSections[$yvalue]."</categoryId>\n";
				}
				else
				{
					if ($bNoActiveGroup)
						continue;
				}

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
				$strTmpOff.= "<url>".$usedProtocol.$arAcc['SERVER_NAME'].htmlspecialcharsbx($arAcc["~DETAIL_PAGE_URL"]).(strstr($arAcc['DETAIL_PAGE_URL'], '?') === false ? '?' : '&amp;')."r1=<?echo \$strReferer1; ?>&amp;r2=<?echo \$strReferer2; ?></url>\n";

				$strTmpOff.= "<price>".$minPrice."</price>\n";
				$strTmpOff.= "<currencyId>".$minPriceCurrency."</currencyId>\n";

				$strTmpOff.= $strTmpOff_tmp;

				if (intval($arAcc["DETAIL_PICTURE"])>0 || intval($arAcc["PREVIEW_PICTURE"])>0)
				{
					$pictNo = intval($arAcc["DETAIL_PICTURE"]);
					if ($pictNo<=0) $pictNo = intval($arAcc["PREVIEW_PICTURE"]);

					$arPictInfo = CFile::GetFileArray($pictNo);
					if (is_array($arPictInfo))
					{
						if(substr($arPictInfo["SRC"], 0, 1) == "/")
							$strFile = $usedProtocol.$arAcc['SERVER_NAME'].CHTTP::urnEncode($arPictInfo["SRC"], 'utf-8');
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

			if ($boolNeedRootSection)
			{
				$iblockName = CIBlock::GetArrayByID($yvalue, 'NAME');
				$strTmpCat .= '<category id="'.$maxSections[$yvalue].'">'.yandex_text2xml(GetMessage('YANDEX_ROOT_DIRECTORY_EXT', array('#NAME#' => $iblockName)), true)."</category>\n";
				unset($iblockName);
			}
		}
	}

	fwrite($fp, "<categories>\n");
	fwrite($fp, $strTmpCat);
	fwrite($fp, "</categories>\n");

	fwrite($fp, "<offers>\n");
	fwrite($fp, $strTmpOff);
	fwrite($fp, "</offers>\n");

	fwrite($fp, "</shop>\n");
	fwrite($fp, "</yml_catalog>\n");

	fclose($fp);
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