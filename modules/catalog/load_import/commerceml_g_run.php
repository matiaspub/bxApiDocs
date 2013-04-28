<?
//<title>CommerceML MySql Fast - BETA VERS</title>
set_time_limit(0);

// define("CML_DEBUG", False);
// define("CML_MEMORY_DEBUG", False);
// define("CML_DEBUG_FILE_NAME", "/__cml_time_mark.dat");

// define("CML_GROUP_OPERATION_CNT", 100);
// define("CML_CLEAR_TEMP_TABLES", False);
// define("CML_DELETE_COMMENTS", False);

// define("CML_KEEP_EXISTING_PROPERTIES", True);
// define("CML_KEEP_EXISTING_DATA", False);
// define("CML_ACTIVATE_FILE_DATA", True);

//// define("CML_USE_SYSTEM_DELETE", False);

__IncludeLang(GetLangFileName($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/lang/", "/import_setup_templ.php"));
$startImportExecTime = getmicrotime();

global $USER, $DB, $APPLICATION;
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

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/include/1c_mutator.php"))
	include($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/include/1c_mutator.php");

global $strImportErrorMessage, $strImportOKMessage;
$strImportErrorMessage = "";
$strImportOKMessage = "";

/************************ FUNCTIONS *******************************/
if (!function_exists("file_get_contents"))
{
	function file_get_contents($filename)
	{
		$fd = fopen("$filename", "rb");
		$content = fread($fd, filesize($filename));
		fclose($fd);
		return $content;
	}
}

function cmlStartElement($parser, $name, $attrs)
{
	global $DB;
	global $USER;
	global $currentCatalog, $currentProduct, $currentProperty, $currentOffersList, $currentOffer;
	global $arIBlockCache, $iBlockIDString, $arCMLCurrencies;
	global $APPLICATION, $nameUTF, $IBLOCK_TYPE_ID, $strImportErrorMessage;
	global $oIBlock, $cmlLoadCnts;

	global $USE_TRANSLIT, $ADD_TRANSLIT;
	global $boolIBlockTranslit, $boolTranslitElement, $boolTranslitSection, $arTranslitElement, $arTranslitSection;

	static $SITE_ID = false;

	if (false === $SITE_ID)
	{
		$SITE_ID = 'ru';
		$dbSite = CSite::GetByID($SITE_ID);
		if (!$dbSite->Fetch())
		{
			$dbSite = CSite::GetList(($by = 'sort'), ($order = 'asc'));
			$arSite = $dbSite->Fetch();
			$SITE_ID = $arSite['ID'];
		}
	}

	switch ($name)
	{
		case $nameUTF["Catalog"]:
			$currentCatalog = array();

			if (in_array($nameUTF["ID"], array_keys($attrs)))
				$currentCatalog["ID"] = $attrs[$nameUTF["ID"]];
			if (in_array($nameUTF["Name"], array_keys($attrs)))
				$currentCatalog["Name"] = $attrs[$nameUTF["Name"]];
			if (in_array($nameUTF["Description"], array_keys($attrs)))
				$currentCatalog["Description"] = $attrs[$nameUTF["Description"]];

			$dbIBlockList = CIBlock::GetList(
					array(),
					array("=TYPE" => $IBLOCK_TYPE_ID, "=XML_ID" => $currentCatalog["ID"], 'MIN_PERMISSION' => 'W')
				);
			if ($arIBlock = $dbIBlockList->Fetch())
			{
				$bUpdate = True;
				$currentCatalog["BID"] = $arIBlock["ID"];
				$res = $oIBlock->Update(
						$currentCatalog["BID"],
						array(
								"NAME" => $currentCatalog["Name"],
								"DESCRIPTION" => $currentCatalog["Description"]
							)
					);
			}
			elseif ($USER->IsAdmin())
			{
				$bUpdate = False;
				$arFields = Array(
						"ACTIVE" => "Y",
						"NAME" => $currentCatalog["Name"],
						"XML_ID" => $currentCatalog["ID"],
						"IBLOCK_TYPE_ID" => $IBLOCK_TYPE_ID,
						"LID" => $SITE_ID,
						"WORKFLOW" => "N",
					);
				if ('Y' == $USE_TRANSLIT && 'Y' == $ADD_TRANSLIT)
				{
					$arFields['FIELDS'] = array(
						'CODE' => array(
							'DEFAULT_VALUE' => array(
								'TRANSLITERATION' => 'Y',
							),
						),
						'SECTION_CODE' => array(
							'DEFAULT_VALUE' => array(
								'TRANSLITERATION' => 'Y',
							),
						)
					);
				}
				$currentCatalog["BID"] = $oIBlock->Add($arFields);
				$res = ($currentCatalog["BID"] > 0);
			}
			else
				$res = false;

			$cmlLoadCnts["CATALOG"]++;

			if (!$res)
			{
				$strImportErrorMessage .= str_replace("#ERROR#", $oIBlock->LAST_ERROR, str_replace("#NAME#", "[".$currentCatalog["BID"]."] \"".$currentCatalog["Name"]."\" (".$currentCatalog["ID"].")", str_replace("#ACT#", ($bUpdate ? GetMessage("CML_R_EDIT") : GetMessage("CML_R_ADD")), GetMessage("CML_R_IBLOCK")))).".<br>";
				$currentCatalog = false;
			}
			else
			{
				$boolIBlockTranslit = $USE_TRANSLIT;
				$boolTranslitElement = false;
				$boolTranslitSection = false;
				$arTranslitElement = array();
				$arTranslitSection = array();

				if ('Y' == $boolIBlockTranslit)
				{
					$boolOutTranslit = false;
					$arIBlock = CIBlock::GetArrayByID($currentCatalog["BID"]);
					if (isset($arIBlock['FIELDS']['CODE']['DEFAULT_VALUE']))
					{
						if ('Y' == $arIBlock['FIELDS']['CODE']['DEFAULT_VALUE']['TRANSLITERATION']
							&& 'Y' == $arIBlock['FIELDS']['CODE']['DEFAULT_VALUE']['USE_GOOGLE'])
						{
							$boolOutTranslit = true;
						}
					}
					if (isset($arIBlock['FIELDS']['SECTION_CODE']['DEFAULT_VALUE']))
					{
						if ('Y' == $arIBlock['FIELDS']['SECTION_CODE']['DEFAULT_VALUE']['TRANSLITERATION']
							&& 'Y' == $arIBlock['FIELDS']['SECTION_CODE']['DEFAULT_VALUE']['USE_GOOGLE'])
						{
							$boolOutTranslit = true;
						}
					}
					if ($boolOutTranslit)
					{
						$boolIBlockTranslit = 'N';
						$strImportErrorMessage .= str_replace("#ERROR#", GetMessage('CATI_USE_CODE_TRANSLIT_OUT'), str_replace("#NAME#", "[".$currentCatalog["BID"]."] \"".$currentCatalog["Name"]."\" (".$currentCatalog["ID"].")", str_replace("#ACT#", ($bUpdate ? GetMessage("CML_R_EDIT") : GetMessage("CML_R_ADD")), GetMessage("CML_R_IBLOCK")))).".<br>";
						$currentCatalog = false;
						break;
					}

					if ('Y' == $boolIBlockTranslit)
					{
						if (isset($arIBlock['FIELDS']['CODE']['DEFAULT_VALUE']))
						{
							$arTransSettings = $arIBlock['FIELDS']['CODE']['DEFAULT_VALUE'];
							$boolTranslitElement = ('Y' == $arTransSettings['TRANSLITERATION'] ? true : false);
							$arTranslitElement = array(
								"max_len" => $arTransSettings['TRANS_LEN'],
								"change_case" => $arTransSettings['TRANS_CASE'],
								"replace_space" => $arTransSettings['TRANS_SPACE'],
								"replace_other" => $arTransSettings['TRANS_OTHER'],
								"delete_repeat_replace" => ('Y' == $arTransSettings['TRANS_EAT'] ? true : false),
								"use_google" => ('Y' == $arTransSettings['USE_GOOGLE'] ? true : false),
							);
						}
						if (isset($arIBlock['FIELDS']['SECTION_CODE']['DEFAULT_VALUE']))
						{
							$arTransSettings = $arIBlock['FIELDS']['SECTION_CODE']['DEFAULT_VALUE'];
							$boolTranslitSection = ('Y' == $arTransSettings['TRANSLITERATION'] ? true : false);
							$arTranslitSection = array(
								"max_len" => $arTransSettings['TRANS_LEN'],
								"change_case" => $arTransSettings['TRANS_CASE'],
								"replace_space" => $arTransSettings['TRANS_SPACE'],
								"replace_other" => $arTransSettings['TRANS_OTHER'],
								"delete_repeat_replace" => ('Y' == $arTransSettings['TRANS_EAT'] ? true : false),
								"use_google" => ('Y' == $arTransSettings['USE_GOOGLE'] ? true : false),
							);
						}
					}
				}

				$arIBlockCache[$currentCatalog["ID"]] = IntVal($currentCatalog["BID"]);
				$iBlockIDString .= ",".IntVal($currentCatalog["BID"]);
				if (!CCatalog::GetByID($currentCatalog["BID"]))
					CCatalog::Add(Array("IBLOCK_ID" => $currentCatalog["BID"]));

				if (function_exists("catalog_1c_mutator_catalogT"))
					catalog_1c_mutator_catalogT($currentCatalog["BID"], $bUpdate, $attrs);
			}

			break;

		case $nameUTF["Property"]:
			if ($currentCatalog)
			{
				$currentProperty = array();

				$currentProperty["ID"] = $attrs[$nameUTF["ID"]];
				$currentProperty["DataType"] = $attrs[$nameUTF["DataType"]];
				$currentProperty["Multiple"] = (($attrs[$nameUTF["Multiple"]] == "1" || $attrs[$nameUTF["Multiple"]] == "Y") ? "Y" : "N");
				$currentProperty["Name"] = $attrs[$nameUTF["Name"]];
				$currentProperty["DefaultValue"] = $attrs[$nameUTF["DefaultValue"]];

				if ($currentProperty["DataType"] == "enumeration")
					$currentProperty["DataType"] = "L";
				else
					$currentProperty["DataType"] = "S";

				$strSql =
					"INSERT INTO b_catalog_cml_property (XML_ID, CATALOG_ID, DATA_TYPE, MULTIPLE, NAME, DEFAULT_VALUE) ".
					"VALUES ('".$currentProperty["ID"]."', ".$currentCatalog["BID"].", '".$currentProperty["DataType"]."', '".$currentProperty["Multiple"]."', '".$DB->ForSql($currentProperty["Name"])."', '".$DB->ForSql($currentProperty["DefaultValue"])."')";

				$DB->Query($strSql);

				$cmlLoadCnts["PROPERTY"]++;
			}
			break;

		case $nameUTF["PropertyVariant"]:
			if ($currentProperty)
			{
				$currentPropertyEnum = array();

				$currentPropertyEnum["ID"] = $attrs[$nameUTF["ID"]];
				$currentPropertyEnum["Name"] = $attrs[$nameUTF["Name"]];
				$currentPropertyEnum["Default"] = (($currentProperty["DefaultValue"] == $currentPropertyEnum["ID"]) ? "Y" : "N");

				$strSql =
					"INSERT INTO b_catalog_cml_property_var (XML_ID, CATALOG_ID, PROPERTY_XML_ID, NAME, DEFAULT_VALUE) ".
					"VALUES ('".$currentPropertyEnum["ID"]."', ".$currentCatalog["BID"].", '".$currentProperty["ID"]."', '".$DB->ForSql($currentPropertyEnum["Name"])."', '".$currentPropertyEnum["Default"]."')";

				$DB->Query($strSql);
			}
			break;

		case $nameUTF["Category"]:
			if ($currentCatalog)
			{
				$currentCategory = array();

				if (in_array($nameUTF["ID"], array_keys($attrs)))
					$currentCategory["ID"] = $attrs[$nameUTF["ID"]];
				if (in_array($nameUTF["Name"], array_keys($attrs)))
					$currentCategory["Name"] = $attrs[$nameUTF["Name"]];
				if (in_array($nameUTF["ParentCategory"], array_keys($attrs)))
					$currentCategory["ParentCategory"] = $attrs[$nameUTF["ParentCategory"]];
				$currentCategory["Code"] = false;
				if (true === $boolTranslitSection)
					$currentCategory["Code"] = CUtil::translit($currentCategory["Name"], 'ru', $arTranslitSection);

				$strSql =
					"INSERT INTO b_catalog_cml_section (XML_ID, CATALOG_ID, PARENT_XML_ID, NAME, CODE) ".
					"VALUES ('".$currentCategory["ID"]."', ".$currentCatalog["BID"].", '".$currentCategory["ParentCategory"]."', '".$DB->ForSql($currentCategory["Name"])."', '".(false === $currentCategory["Code"] ? '' : $DB->ForSql($currentCategory["Code"]))."')";

				$DB->Query($strSql);

				$cmlLoadCnts["SECTION"]++;
			}
			break;

		case $nameUTF["Product"]:
			if ($currentCatalog)
			{
				$currentProduct = array();
				$currentProduct["ID"] = $attrs[$nameUTF["ID"]];
				$currentProduct["Name"] = $attrs[$nameUTF["Name"]];
				$currentProduct["ParentCategory"] = $attrs[$nameUTF["ParentCategory"]];
				$currentProduct["Code"] = false;
				if (true === $boolTranslitElement)
					$currentProduct["Code"] = CUtil::translit($currentProduct["Name"], 'ru', $arTranslitElement);

				$strSql =
					"INSERT INTO b_catalog_cml_product (XML_ID, CATALOG_ID, NAME, MODIFIED_BY, PARENT_CATEGORY, CODE) ".
					"VALUES ('".$currentProduct["ID"]."', ".$currentCatalog["BID"].", '".$DB->ForSql($currentProduct["Name"])."', ".((IntVal($USER->GetID()) > 0) ? IntVal($USER->GetID()) : 1).", '".$currentProduct["ParentCategory"]."', '".(false === $currentProduct["Code"] ? '' : $DB->ForSql($currentProduct["Code"]))."')";

				$DB->Query($strSql);

				if (function_exists("catalog_1c_mutator_productT"))
					catalog_1c_mutator_productT($attrs);

				$cmlLoadCnts["PRODUCT"]++;

				if (strlen($currentProduct["ParentCategory"]) > 0)
				{
					$strSql =
						"INSERT INTO b_catalog_cml_product_cat (CATALOG_ID, PRODUCT_XML_ID, CATEGORY_XML_ID) ".
						"VALUES (".$currentCatalog["BID"].", '".$currentProduct["ID"]."',  '".$currentProduct["ParentCategory"]."')";

					$DB->Query($strSql);
				}
			}
			break;

		case $nameUTF["CategoryReference"]:
			if ($currentProduct)
			{
				$strSql =
					"INSERT INTO b_catalog_cml_product_cat (CATALOG_ID, PRODUCT_XML_ID, CATEGORY_XML_ID) ".
					"VALUES (".$currentCatalog["BID"].", '".$currentProduct["ID"]."',  '".$attrs[$nameUTF["IdInCatalog"]]."')";

				$DB->Query($strSql);
			}
			break;

		case $nameUTF["PropertyValue"]:
			if ($currentProduct)
			{
				$propertyID = $attrs[$nameUTF["PropertyId"]];
				$propertyValue = $attrs[$nameUTF["Value"]];

				$strSql =
					"INSERT INTO b_catalog_cml_product_prop (CATALOG_ID, PRODUCT_XML_ID, PROPERTY_XML_ID, PROPERTY_VALUE, PROPERTY_VALUE_TEXT) ".
					"VALUES (".$currentCatalog["BID"].", '".$currentProduct["ID"]."',  '".$propertyID."', '".$DB->ForSql($propertyValue, 255)."', '".$DB->ForSql($propertyValue)."')";

				$DB->Query($strSql);
			}
			elseif ($currentOffersList && !$currentOffer)
			{
				$priceType = $attrs[$nameUTF["Value"]];
				$currentOffersList["PRICE_TYPE"] = $priceType;

				$strSql =
					"INSERT INTO b_catalog_cml_oflist_prop (OFFER_LIST_XML_ID, PROPERTY_VALUE) ".
					"VALUES (".$currentOffersList["ID"].", '".$DB->ForSql($priceType, 255)."')";

				$DB->Query($strSql);
			}
			break;

		case $nameUTF["OffersList"]:
			$currentOffersList = array();
			$currentOffersList["CatalogID"] = $attrs[$nameUTF["CatalogID"]];
			$currentOffersList["Currency"] = $arCMLCurrencies[$attrs[$nameUTF["Currency"]]];
			if (strlen($currentOffersList["Currency"]) <= 0)
				$currentOffersList["Currency"] = "USD";

			if (!array_key_exists($currentOffersList["CatalogID"], $arIBlockCache))
			{
				$dbIBlockList = CIBlock::GetList(array(), array("XML_ID" => $currentOffersList["CatalogID"]));
				if ($arIBlock = $dbIBlockList->Fetch())
					$arIBlockCache[$currentOffersList["CatalogID"]] = IntVal($arIBlock["ID"]);
			}

			$strSql =
				"INSERT INTO b_catalog_cml_oflist(CATALOG_ID) ".
				"VALUES (".$arIBlockCache[$currentOffersList["CatalogID"]].")";

			$DB->Query($strSql);

			$currentOffersList["ID"] = IntVal($DB->LastID());

			break;

		case $nameUTF["Offer"]:
			if ($currentOffersList)
			{
				$currentOffer = array();
				$currentOffer["ProductId"] = $attrs[$nameUTF["ProductId"]];
				$currentOffer["Price"] = DoubleVal(str_replace(",", ".", $attrs[$nameUTF["Price"]]));
				$currentOffer["Amount"] = IntVal($attrs[$nameUTF["Amount"]]);
				$currentOffer["Currency"] = $arCMLCurrencies[$attrs[$nameUTF["Currency"]]];
				if (strlen($currentOffer["Currency"]) <= 0)
					$currentOffer["Currency"] = $currentOffersList["Currency"];

				$strSql =
					"INSERT INTO b_catalog_cml_offer (OFFER_LIST_XML_ID, PRODUCT_XML_ID, PRICE, AMOUNT, CURRENCY) ".
					"VALUES ('".$currentOffersList["ID"]."', '".$currentOffer["ProductId"]."', ".$currentOffer["Price"].", ".$currentOffer["Amount"].", '".$currentOffer["Currency"]."')";

				$DB->Query($strSql);

				$cmlLoadCnts["OFFER"]++;
			}

			break;

		default:
			break;
	}
}

function cmlEndElement($parser, $name)
{
	global $DB;
	global $currentCatalog, $currentProduct, $currentProperty, $currentOffersList, $currentOffer;
	global $arIBlockCache;
	global $APPLICATION, $nameUTF, $tmpid;

	switch ($name)
	{
		case $nameUTF["Catalog"]:
			$currentCatalog = false;
			break;

		case $nameUTF["Product"]:
			$currentProduct = false;
			break;

		case $nameUTF["Property"]:
			$currentProperty = false;
			break;

		case $nameUTF["OffersList"]:
			if (!array_key_exists("PRICE_TYPE", $currentOffersList) || strlen($currentOffersList["PRICE_TYPE"]) <= 0)
			{
				$strSql = "SELECT NAME FROM b_catalog_group WHERE BASE = 'Y'";
				$dbRes = $DB->Query($strSql);
				if ($arRes = $dbRes->Fetch())
				{
					$priceType = $arRes["NAME"];
					$currentOffersList["PRICE_TYPE"] = $priceType;

					$strSql =
						"INSERT INTO b_catalog_cml_oflist_prop (OFFER_LIST_XML_ID, PROPERTY_VALUE) ".
						"VALUES (".$currentOffersList["ID"].", '".$DB->ForSql($priceType, 255)."')";
					$DB->Query($strSql);
				}
			}
			else
			{
				$strSql = "SELECT count(*) as CNT FROM b_catalog_group WHERE NAME = '".$DB->ForSql($currentOffersList["PRICE_TYPE"])."'";
				$dbRes = $DB->Query($strSql);
				if ($arRes = $dbRes->Fetch())
				{
					if (IntVal($arRes["CNT"]) <= 0)
					{
						CCatalogGroup::Add(
								array(
									"NAME" => $currentOffersList["PRICE_TYPE"],
									"USER_LANG" => array("ru" => $currentOffersList["PRICE_TYPE"])
								)
							);
					}
				}
			}
			$currentOffersList = false;
			break;

		case $nameUTF["Offer"]:
			$currentOffer = false;
			break;
	}
}

function __SetTimeMark($text, $startStop = "")
{
	global $bCmlDebug;
	global $cmlTimeMarkTo, $cmlTimeMarkFrom, $cmlTimeMarkGlobalFrom;
	global $cmlMemoryMarkTo, $cmlMemoryMarkFrom, $cmlMemoryMarkGlobalFrom;

	//echo " ";
	//flush();

	if (!$bCmlDebug)
		return;

	if (StrToUpper($startStop) == "START")
	{
		$hFile = fopen($_SERVER["DOCUMENT_ROOT"].CML_DEBUG_FILE_NAME, "w");
		fwrite($hFile, date("H:i:s")." - ".__getMemoryUsage()." - ".$text."\n");
		fclose($hFile);

		$cmlMemoryMarkGlobalFrom = __getMemoryUsage();
		$cmlMemoryMarkFrom = __getMemoryUsage();
		$cmlTimeMarkGlobalFrom = __getMicroTime();
		$cmlTimeMarkFrom = __getMicroTime();
	}
	elseif (StrToUpper($startStop) == "STOP")
	{
		$cmlTimeMarkTo = __getMicroTime();
		$cmlMemoryMarkTo = __getMemoryUsage();

		$hFile = fopen($_SERVER["DOCUMENT_ROOT"].CML_DEBUG_FILE_NAME, "a");
		fwrite($hFile, date("H:i:s")." - ".Round($cmlTimeMarkTo - $cmlTimeMarkFrom, 3)." s - ".($cmlMemoryMarkTo - $cmlMemoryMarkFrom)." - ".$text."\n");
		fwrite($hFile, date("H:i:s")." - ".Round($cmlTimeMarkTo - $cmlTimeMarkGlobalFrom, 3)." s - ".($cmlMemoryMarkTo - $cmlMemoryMarkGlobalFrom)."\n");
		fclose($hFile);
	}
	else
	{
		$cmlTimeMarkTo = __getMicroTime();
		$cmlMemoryMarkTo = __getMemoryUsage();

		$hFile = fopen($_SERVER["DOCUMENT_ROOT"].CML_DEBUG_FILE_NAME, "a");
		fwrite($hFile, date("H:i:s")." - ".Round($cmlTimeMarkTo - $cmlTimeMarkFrom, 3)." s - ".($cmlMemoryMarkTo - $cmlMemoryMarkFrom)." - ".($cmlMemoryMarkTo - $cmlMemoryMarkGlobalFrom)." - ".$text."\n");
		fclose($hFile);

		$cmlMemoryMarkFrom = __getMemoryUsage();
		$cmlTimeMarkFrom = __getMicroTime();
	}
}

function __getMicroTime()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

function __getMemoryUsage()
{
	global $bCmlMemoryDebug;

	if (!$bCmlMemoryDebug)
		return 0;

	if (function_exists('memory_get_usage'))
	{
		return memory_get_usage();
	}

	return 0;
}

function cmlCreateTempTables()
{
	global $DB;

	$arTables = array("b_catalog_cml_tmp", "b_catalog_cml_property", "b_catalog_cml_property_var", "b_catalog_cml_section", "b_catalog_cml_product", "b_catalog_cml_product_cat", "b_catalog_cml_product_prop", "b_catalog_cml_oflist", "b_catalog_cml_oflist_prop", "b_catalog_cml_offer");

	$dbRes = $DB->Query("SHOW TABLES");
	while ($arRes = $dbRes->Fetch())
	{
		$arKeys = array_keys($arRes);
		$tableName = $arRes[$arKeys[0]];

		if (in_array($tableName, $arTables))
		{
			foreach ($arTables as $key => $value)
			{
				if ($value == $tableName)
				{
					unset($arTables[$key]);
					break;
				}
			}
		}
	}

	foreach ($arTables as $key => $value)
	{
		if ($value == "b_catalog_cml_property")
		{
			$DB->Query("create table b_catalog_cml_property (
				XML_ID varchar(100) not null,
				CATALOG_ID int(11) not null,
				DATA_TYPE char(1) not null,
				MULTIPLE char(1) not null,
				NAME varchar(255) not null,
				DEFAULT_VALUE varchar(255),
				primary key (CATALOG_ID, XML_ID))");
		}
		elseif ($value == "b_catalog_cml_property_var")
		{
			$DB->Query("create table b_catalog_cml_property_var (
				XML_ID varchar(100) not null,
				CATALOG_ID int(11) not null,
				PROPERTY_XML_ID varchar(100) not null,
				NAME varchar(255) not null,
				DEFAULT_VALUE char(1),
				primary key (CATALOG_ID, PROPERTY_XML_ID, XML_ID))");
		}
		elseif ($value == "b_catalog_cml_section")
		{
			$DB->Query("create table b_catalog_cml_section (
				XML_ID varchar(100) not null,
				CATALOG_ID int(11) not null,
				PARENT_XML_ID varchar(100) not null,
				NAME varchar(255) not null,
				CODE varchar(255),
				primary key (CATALOG_ID, XML_ID))");
		}
		elseif ($value == "b_catalog_cml_product")
		{
			$DB->Query("create table b_catalog_cml_product (
				XML_ID varchar(100) not null,
				CATALOG_ID int(11) not null,
				NAME varchar(255) not null,
				MODIFIED_BY int(11) not null,
				PARENT_CATEGORY varchar(100) not null,
				CODE varchar(255),
				primary key (CATALOG_ID, XML_ID))");
		}
		elseif ($value == "b_catalog_cml_product_cat")
		{
			$DB->Query("create table b_catalog_cml_product_cat (
				CATALOG_ID int(11) not null,
				PRODUCT_XML_ID varchar(100) not null,
				CATEGORY_XML_ID varchar(100) not null,
				primary key (CATALOG_ID, PRODUCT_XML_ID, CATEGORY_XML_ID))");
		}
		elseif ($value == "b_catalog_cml_product_prop")
		{
			$DB->Query("create table b_catalog_cml_product_prop (
				CATALOG_ID int(11) not null,
				PRODUCT_XML_ID varchar(100) not null,
				PROPERTY_XML_ID varchar(100) not null,
				PROPERTY_VALUE varchar(255) not null,
				PROPERTY_VALUE_TEXT text,
				index IXS_CAT_CML_P2P (CATALOG_ID, PROPERTY_XML_ID))");
		}
		elseif ($value == "b_catalog_cml_oflist")
		{
			$DB->Query("create table b_catalog_cml_oflist (
				ID int(11) not null auto_increment,
				CATALOG_ID int(11) not null,
				primary key (ID),
				index IXS_CAT_CML_OL (CATALOG_ID))");
		}
		elseif ($value == "b_catalog_cml_oflist_prop")
		{
			$DB->Query("create table b_catalog_cml_oflist_prop (
				OFFER_LIST_XML_ID int(11) not null,
				PROPERTY_VALUE varchar(255) not null,
				primary key (OFFER_LIST_XML_ID))");
		}
		elseif ($value == "b_catalog_cml_offer")
		{
			$DB->Query("create table b_catalog_cml_offer (
				OFFER_LIST_XML_ID int(11) not null,
				PRODUCT_XML_ID varchar(100) not null,
				PRICE decimal(18,4) not null,
				AMOUNT int(11) not null,
				CURRENCY char(3) not null,
				primary key (OFFER_LIST_XML_ID, PRODUCT_XML_ID))");
		}
		elseif ($value == "b_catalog_cml_tmp")
		{
			$DB->Query("create table b_catalog_cml_tmp (
				ID int(11) not null default '0',
				XML_ID varchar(100) not null,
				CATALOG_ID int(11) not null,
				VALUE_ID int(11),
				primary key (CATALOG_ID, XML_ID),
				index IXS_CAT_CML_TMP (ID))");
		}
	}
}
/************************ END FUNCTIONS *******************************/



if (StrToUpper($DB->type) != "MYSQL")
	$strImportErrorMessage .= GetMessage("CML_R_MYSQL_ONLY").". ";

if (strlen($strImportErrorMessage) <= 0)
{
	$DATA_FILE_NAME = "";

	if (isset($_FILES["FILE_1C"]) && is_uploaded_file($_FILES["FILE_1C"]["tmp_name"]))
		$DATA_FILE_NAME = $_FILES["FILE_1C"]["tmp_name"];

	if (strlen($DATA_FILE_NAME) <= 0)
	{
		if (strlen($URL_FILE_1C) > 0)
		{
			$URL_FILE_1C = Rel2Abs("/", $URL_FILE_1C);
			if (file_exists($_SERVER["DOCUMENT_ROOT"].$URL_FILE_1C) && is_file($_SERVER["DOCUMENT_ROOT"].$URL_FILE_1C))
				$DATA_FILE_NAME = $_SERVER["DOCUMENT_ROOT"].$URL_FILE_1C;
		}
	}

	if (strlen($DATA_FILE_NAME) <= 0)
		$strImportErrorMessage .= GetMessage("CICML_NO_LOAD_FILE")."<br>";

	global $IBLOCK_TYPE_ID;
	$IBLOCK_TYPE_ID = trim(strval($IBLOCK_TYPE_ID));
	if (0 < strlen($IBLOCK_TYPE_ID))
	{
		$rsIBlockTypes = CIBlockType::GetByID($IBLOCK_TYPE_ID);
		if (!($arIBlockType = $rsIBlockTypes->Fetch()))
		{
			$IBLOCK_TYPE_ID = '';
		}
	}
	if (strlen($IBLOCK_TYPE_ID) <= 0)
	{
		$IBLOCK_TYPE_ID = COption::GetOptionString("catalog", "default_catalog_1c", "");
	}
	if (strlen($IBLOCK_TYPE_ID) <= 0)
	{
		ClearVars('f_');
		$iblocks = CIBlockType::GetList(array('SORT' => 'ASC'));
		if ($iblocks->ExtractFields("f_"))
			$IBLOCK_TYPE_ID = $f_ID;
	}
	if (strlen($IBLOCK_TYPE_ID) <= 0)
		$strImportErrorMessage .= GetMessage("CICML_NO_IBLOCK")."<br>";

	if ($keepExistingProperties != "Y" && $keepExistingProperties != "N")
		$keepExistingProperties = COption::GetOptionString("catalog", "keep_existing_properties", (CML_KEEP_EXISTING_PROPERTIES ? "Y" : "N"));
	$bKeepExistingProperties = (($keepExistingProperties == "Y") ? True : False);

	if ($keepExistingData != "Y" && $keepExistingData != "N")
		$keepExistingData = COption::GetOptionString("catalog", "keep_existing_data", (CML_KEEP_EXISTING_DATA ? "Y" : "N"));
//	$bKeepExistingData = (($keepExistingData == "Y") ? True : False);

	if ($activateFileData != "Y" && $activateFileData != "N")
		$activateFileData = COption::GetOptionString("catalog", "activate_file_data", (CML_ACTIVATE_FILE_DATA ? "Y" : "N"));
	$bActivateFileData = (($activateFileData == "Y") ? True : False);

	if ($deleteComments != "Y" && $deleteComments != "N")
		$deleteComments = (CML_DELETE_COMMENTS ? "Y" : "N");
	$bDeleteComments = (($deleteComments == "Y") ? True : False);

	global $bCmlDebug;
	if ($cmlDebug != "Y" && $cmlDebug != "N")
		$cmlDebug = (CML_DEBUG ? "Y" : "N");
	$bCmlDebug = (($cmlDebug == "Y") ? True : False);

	global $bCmlMemoryDebug;
	if ($cmlMemoryDebug != "Y" && $cmlMemoryDebug != "N")
		$cmlMemoryDebug = (CML_MEMORY_DEBUG ? "Y" : "N");
	$bCmlMemoryDebug = (($cmlMemoryDebug == "Y") ? True : False);

	global $arCMLCurrencies;
	$arCMLCurrencies = array();
	include(dirname(__FILE__).'/ru/commerceml_g_run_cur.php');
	if (!isset($arCMLCurrencies) || !is_array($arCMLCurrencies) || empty($arCMLCurrencies))
		$strImportErrorMessage .= GetMessage('CAT_ADM_CML1_IMP_ERR_CMLCUR').'<br>';

	global $nameUTF;
	$nameUTF = array();
	include(dirname(__FILE__).'/ru/commerceml_g_run_name.php');
	if (!isset($nameUTF) || !is_array($nameUTF) || empty($nameUTF))
		$strImportErrorMessage .= GetMessage('CAT_ADM_CML1_IMP_ERR_NAMEUTF').'<br>';

	global $currentCatalog, $currentProduct, $currentProperty, $currentOffersList, $currentOffer;
	$currentCatalog = false;
	$currentProduct = false;
	$currentProperty = false;
	$currentOffersList = false;
	$currentOffer = false;

	global $arIBlockCache, $iBlockIDString;
	$arIBlockCache = array();
	$iBlockIDString = "0";

	global $cmlLoadCnts;
	$cmlLoadCnts = array();
	$cmlLoadCnts["CATALOG"] = 0;
	$cmlLoadCnts["PROPERTY"] = 0;
	$cmlLoadCnts["SECTION"] = 0;
	$cmlLoadCnts["PRODUCT"] = 0;
	$cmlLoadCnts["OFFER"] = 0;

	global $USE_TRANSLIT, $ADD_TRANSLIT;
	$USE_TRANSLIT = (isset($USE_TRANSLIT) && 'Y' == $USE_TRANSLIT ? 'Y' : 'N');
	$ADD_TRANSLIT = (isset($ADD_TRANSLIT) && 'Y' == $ADD_TRANSLIT ? 'Y' : 'N');

	$boolIBlockTranslit = $USE_TRANSLIT;
	$boolTranslitElement = false;
	$boolTranslitSection = false;
	$arTranslitElement = array();
	$arTranslitSection = array();

	__SetTimeMark("Start", "START");

	cmlCreateTempTables();

	__SetTimeMark("Create temp tables");

	$DB->Query("TRUNCATE TABLE b_catalog_cml_property");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_property_var");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_section");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_product");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_product_cat");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_product_prop");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_oflist");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_oflist_prop");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_offer");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_tmp");

	__SetTimeMark("Clear temp tables");

	global $oIBlock;
	$oIBlock = new CIBlock();

	global $tmpid;
	$tmpid = md5(uniqid(""));

	$xmlData = file_get_contents($DATA_FILE_NAME);

	__SetTimeMark("Get contents");
	if ($pe = strpos($xmlData, ">"))
	{
		$headerString = substr($xmlData, 0, $pe);
		if(preg_match('#encoding[\s]*=[\s]*"(.*?)"#i', $headerString, $arMatch))
		{
			$xmlData = $APPLICATION->ConvertCharset($xmlData, $arMatch[1], LANG_CHARSET);
		}
	}

	__SetTimeMark("Convert");

	if ($bDeleteComments)
	{
		$xmlData = preg_replace("#<\!--.*?-->#s", "", $xmlData);
		__SetTimeMark("Delete comments");
	}

	$search = array(
			"'&(quot|#34);'i",
			"'&(amp|#38);'i",
			"'&(lt|#60);'i",
			"'&(gt|#62);'i",
			"'&#(\d+);'e"
		);

	$replace = array(
			"\"",
			"&",
			"<",
			">",
			"chr(\\1)"
		);

	$pb = strpos($xmlData, "<");
	while ($pb !== false)
	{
		$pe = strpos($xmlData, ">", $pb);
		if($pe === false)
			break;

		$tag_cont = substr($xmlData, $pb+1, $pe-$pb-1);
		$pb = strpos($xmlData, "<", $pe);

		$check_str = substr($tag_cont, 0, 1);
		if($check_str=="?")
			continue;
		elseif($check_str=="!")
			continue;
		elseif($check_str=="/")
			cmlEndElement(false, substr($tag_cont, 1));
		else
		{
			$p = 0;
			$ltag_cont = strlen($tag_cont);
			while(($p < $ltag_cont) && (strpos(" \t\n\r", substr($tag_cont, $p, 1))===false))
				$p++;
			$name = substr($tag_cont, 0, $p);
			$at = substr($tag_cont, $p);
			if (strpos($at, "&")!==false)
				$bAmp = true;
			else
				$bAmp = false;

			preg_match_all("/(\\S+)\\s*=\\s*[\"](.*?)[\"]/s".BX_UTF_PCRE_MODIFIER, $at, $attrs_tmp);
			$attrs = Array();
			for ($i=0; $i<count($attrs_tmp[1]); $i++)
				$attrs[$attrs_tmp[1][$i]] = ($bAmp ? preg_replace($search, $replace, $attrs_tmp[2][$i]) : $attrs_tmp[2][$i]);
			cmlStartElement(false, $name, $attrs);
			if(substr($tag_cont, -1) === "/")
				cmlEndElement(false, $name);
		}
	}

	$xmlData = "";
	__SetTimeMark("Parse");


	// If there are catalogs in CommerceML data file
	if ($iBlockIDString != "0")
	{
		/***************************** PROPERTIES *******************************************/
		// Collect properties temp table
		$strSql =
			"INSERT INTO b_catalog_cml_tmp(ID, XML_ID, CATALOG_ID) ".
			"SELECT IF(P.ID IS NULL, 0, P.ID), X.XML_ID, X.CATALOG_ID ".
			"FROM b_catalog_cml_property X ".
			"	LEFT JOIN b_iblock_property P ON (P.XML_ID = X.XML_ID AND P.IBLOCK_ID = X.CATALOG_ID) ";
		$DB->Query($strSql);

		// Add new properties
		$strSql =
			"INSERT INTO b_iblock_property(IBLOCK_ID, NAME, ACTIVE, SORT, DEFAULT_VALUE, PROPERTY_TYPE, MULTIPLE, XML_ID, TMP_ID) ".
			"SELECT T.CATALOG_ID, X.NAME, 'Y', 500, X.DEFAULT_VALUE, X.DATA_TYPE, X.MULTIPLE, X.XML_ID, '".$DB->ForSql($tmpid)."' ".
			"FROM b_catalog_cml_tmp T ".
			"	INNER JOIN b_catalog_cml_property X ON (T.CATALOG_ID = X.CATALOG_ID AND T.XML_ID = X.XML_ID) ".
			"WHERE T.ID = 0 ";
		$DB->Query($strSql);

		// Update properties
		$strSql =
			"SELECT P.ID, X.NAME, X.DATA_TYPE, X.MULTIPLE, X.DEFAULT_VALUE, X.XML_ID ".
			"FROM b_catalog_cml_tmp T ".
			"	INNER JOIN b_catalog_cml_property X ON (T.CATALOG_ID = X.CATALOG_ID AND T.XML_ID = X.XML_ID) ".
			"	INNER JOIN b_iblock_property P ON (T.CATALOG_ID = P.IBLOCK_ID AND T.XML_ID = P.XML_ID) ".
			"WHERE T.ID > 0 ";
		$dbRes = $DB->Query($strSql);
		while ($arRes = $dbRes->Fetch())
		{
			$strSql =
				"UPDATE b_iblock_property SET ".
				"	NAME = '".$DB->ForSql($arRes["NAME"])."', ".
				"	PROPERTY_TYPE = '".$arRes["DATA_TYPE"]."', ".
				"	MULTIPLE = '".$arRes["MULTIPLE"]."', ".
				"	DEFAULT_VALUE = '".$DB->ForSql($arRes["DEFAULT_VALUE"])."', ".
				"	TMP_ID = '".$DB->ForSql($tmpid)."' ".
				"WHERE ID = ".$arRes["ID"]." ";
			$DB->Query($strSql);
		}

		// Clear temp table
		$DB->Query("TRUNCATE TABLE b_catalog_cml_tmp");

		__SetTimeMark("Process properties");

		/****************** Property variants *************************************/
		// Collect property variants temp table
		$strSql =
			"INSERT INTO b_catalog_cml_tmp(ID, XML_ID, CATALOG_ID, VALUE_ID) ".
			"SELECT IF(PV.ID IS NULL, 0, PV.ID), X.XML_ID, X.CATALOG_ID, P.ID ".
			"FROM b_catalog_cml_property_var X ".
			"	LEFT JOIN b_iblock_property P ON (P.XML_ID = X.PROPERTY_XML_ID AND P.IBLOCK_ID = X.CATALOG_ID) ".
			"	LEFT JOIN b_iblock_property_enum PV ON (PV.XML_ID = X.XML_ID AND PV.PROPERTY_ID = P.ID) ";
		$DB->Query($strSql);

		// Add new property variants
		$strSql =
			"INSERT INTO b_iblock_property_enum(PROPERTY_ID, VALUE, DEF, SORT, XML_ID, TMP_ID) ".
			"SELECT T.VALUE_ID, XV.NAME, XV.DEFAULT_VALUE, 500, XV.XML_ID, '".$DB->ForSql($tmpid)."' ".
			"FROM b_catalog_cml_tmp T ".
			"	INNER JOIN b_catalog_cml_property_var XV ON (T.XML_ID = XV.XML_ID AND T.CATALOG_ID = XV.CATALOG_ID) ".
			"WHERE T.ID = 0 ";
		$DB->Query($strSql);

		// Update property variants
		$strSql =
			"SELECT PV.ID, XV.NAME, XV.DEFAULT_VALUE ".
			"FROM b_catalog_cml_tmp T ".
			"	INNER JOIN b_catalog_cml_property_var XV ON (T.XML_ID = XV.XML_ID AND T.CATALOG_ID = XV.CATALOG_ID) ".
			"	INNER JOIN b_iblock_property_enum PV ON (PV.XML_ID = T.XML_ID AND PV.PROPERTY_ID = T.VALUE_ID) ".
			"WHERE T.ID > 0 ";
		$dbRes = $DB->Query($strSql);
		while ($arRes = $dbRes->Fetch())
		{
			$strSql =
				"UPDATE b_iblock_property_enum SET ".
				"	VALUE = '".$DB->ForSql($arRes["NAME"])."', ".
				"	DEF = '".$arRes["DEFAULT_VALUE"]."', ".
				"	TMP_ID = '".$DB->ForSql($tmpid)."' ".
				"WHERE ID = ".$arRes["ID"]." ";
			$DB->Query($strSql);
		}

		// Clear temp table
		$DB->Query("TRUNCATE TABLE b_catalog_cml_tmp");

		if (function_exists("catalog_1c_mutator_property"))
			catalog_1c_mutator_property($iBlockIDString);

		__SetTimeMark("Process properties variants");

		/***************************** Sections ****************************************/
		// Collect sections temp table
		$strSql =
			"INSERT INTO b_catalog_cml_tmp(ID, XML_ID, CATALOG_ID) ".
			"SELECT IF(S.ID IS NULL, 0, S.ID), X.XML_ID, X.CATALOG_ID ".
			"FROM b_catalog_cml_section X ".
			"	LEFT JOIN b_iblock_section S ON (S.XML_ID = X.XML_ID AND X.CATALOG_ID = S.IBLOCK_ID) ";
		$DB->Query($strSql);

		// Delete old sections
		if ($keepExistingData == "N")
		{
			$num = 0;
			$sectionIDs = "0";
			$arPropsCache = array();

			$strSql =
				"SELECT S.ID, S.IBLOCK_ID ".
				"FROM b_iblock_section S1 ".
				"	INNER JOIN b_iblock_section S ON (S1.IBLOCK_ID = S.IBLOCK_ID AND S.LEFT_MARGIN >= S1.LEFT_MARGIN AND S.RIGHT_MARGIN <= S1.RIGHT_MARGIN) ".
				"	LEFT JOIN b_catalog_cml_tmp T ON (S1.ID = T.ID) ".
				"WHERE S1.IBLOCK_ID IN (".$iBlockIDString.") ".
				"	AND T.ID IS NULL ";
			$dbRes = $DB->Query($strSql);
			while ($arRes = $dbRes->Fetch())
			{
				$num++;
				$sectionIDs .= ",".$arRes["ID"];

				if (!array_key_exists($arRes["IBLOCK_ID"], $arPropsCache))
				{
					$arPropsCache[$arRes["IBLOCK_ID"]] = "0";
					$strSql =
						"SELECT ID ".
						"FROM b_iblock_property ".
						"WHERE LINK_IBLOCK_ID = ".$arRes["IBLOCK_ID"]." ".
						"	AND PROPERTY_TYPE = 'G' ";
					$dbRes1 = $DB->Query($strSql);
					while ($arRes1 = $dbRes1->Fetch())
					{
						$arPropsCache[$arRes["IBLOCK_ID"]] .= ",".$arRes1["ID"];
					}
				}

				if ($num > CML_GROUP_OPERATION_CNT)
				{
					$strSql =
						"DELETE FROM b_catalog_discount2section ".
						"WHERE SECTION_ID IN (".$sectionIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"UPDATE b_iblock_element SET ".
						"	IBLOCK_SECTION_ID = NULL ".
						"WHERE IBLOCK_SECTION_ID IN (".$sectionIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"DELETE FROM b_iblock_section_element ".
						"WHERE IBLOCK_SECTION_ID IN (".$sectionIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"UPDATE b_iblock_section SET ".
						"	IBLOCK_SECTION_ID = NULL ".
						"WHERE IBLOCK_SECTION_ID IN (".$sectionIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"DELETE FROM b_iblock_section ".
						"WHERE ID IN (".$sectionIDs.") ";
					$DB->Query($strSql);

					$num = 0;
					$sectionIDs = "0";
				}

				if ($arPropsCache[$arRes["IBLOCK_ID"]] != "0")
				{
					$strSql =
						"DELETE FROM b_iblock_element_property ".
						"WHERE VALUE_NUM  = ".$arRes["ID"]." ".
						"	AND IBLOCK_PROPERTY_ID IN (".$arPropsCache[$arRes["IBLOCK_ID"]].") ";
					$DB->Query($strSql);
				}
			}

			if ($num > 0)
			{
				$strSql =
					"DELETE FROM b_catalog_discount2section ".
					"WHERE SECTION_ID IN (".$sectionIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"UPDATE b_iblock_element SET ".
					"	IBLOCK_SECTION_ID = NULL ".
					"WHERE IBLOCK_SECTION_ID IN (".$sectionIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_iblock_section_element ".
					"WHERE IBLOCK_SECTION_ID IN (".$sectionIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"UPDATE b_iblock_section SET ".
					"	IBLOCK_SECTION_ID = NULL ".
					"WHERE IBLOCK_SECTION_ID IN (".$sectionIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_iblock_section ".
					"WHERE ID IN (".$sectionIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_iblock_element_property ".
					"WHERE VALUE_NUM IN (".$sectionIDs.") ";
				$DB->Query($strSql);
			}
		}
		else
		{

		}

		// Update sections
		$strSql =
			"SELECT T.ID, X.NAME, X.PARENT_XML_ID, X.CODE ".
			"FROM b_catalog_cml_tmp T ".
			"	INNER JOIN b_catalog_cml_section X ON (T.XML_ID = X.XML_ID AND T.CATALOG_ID = X.CATALOG_ID) ".
			"WHERE T.ID > 0 ";
		$dbRes = $DB->Query($strSql);
		while ($arRes = $dbRes->Fetch())
		{
			$strSql =
				"UPDATE b_iblock_section SET ".
				"	NAME = '".$DB->ForSql($arRes["NAME"])."', ".
				"	TMP_ID = '".$DB->ForSql($tmpid)."' ".
				(true === $boolTranslitSection ? " ,	CODE = '".$DB->ForSql($arRes["CODE"])."' ": '').
				($bActivateFileData ? ",	ACTIVE = 'Y' " : "").
				"WHERE ID = ".$arRes["ID"]." ";
			$DB->Query($strSql);
		}

		// Add new sections
		$strSql =
			"INSERT INTO b_iblock_section(IBLOCK_ID, ACTIVE, GLOBAL_ACTIVE, SORT, NAME, XML_ID, TMP_ID".(true === $boolTranslitSection ? ', CODE' : '').") ".
			"SELECT T.CATALOG_ID, 'Y', 'Y', 500, X.NAME, X.XML_ID, '".$DB->ForSql($tmpid)."'".(true === $boolTranslitSection ? ', X.CODE ' : '').' '.
			"FROM b_catalog_cml_tmp T ".
			"	INNER JOIN b_catalog_cml_section X ON (T.XML_ID = X.XML_ID AND T.CATALOG_ID = X.CATALOG_ID) ".
			"WHERE T.ID = 0 ";
		$DB->Query($strSql);

		// Update section parent links
		$strSql =
			"SELECT S.ID, S1.ID as VALUE_ID ".
			"FROM b_catalog_cml_section X ".
			"	INNER JOIN b_iblock_section S ON (S.XML_ID = X.XML_ID AND X.CATALOG_ID = S.IBLOCK_ID) ".
			"	LEFT JOIN b_iblock_section S1 ON (S1.XML_ID IS NOT NULL AND S1.XML_ID = X.PARENT_XML_ID AND X.CATALOG_ID = S1.IBLOCK_ID) ";
		$dbRes = $DB->Query($strSql);
		while ($arRes = $dbRes->Fetch())
		{
			$strSql =
				"UPDATE b_iblock_section SET ".
				"	IBLOCK_SECTION_ID = ".((IntVal($arRes["VALUE_ID"]) > 0) ? IntVal($arRes["VALUE_ID"]) : "NULL" )." ".
				"WHERE ID = ".$arRes["ID"]." ";
			$DB->Query($strSql);
		}

		// ReSort sections
		function ReSort($iblockID, $id = 0, $cnt = 0, $depth = 0, $active = "Y")
		{
			global $DB;
			$iblockID = IntVal($iblockID);

			if ($id > 0)
				$DB->Query(
					"UPDATE b_iblock_section SET ".
					"	TIMESTAMP_X = ".(($DB->type=="ORACLE") ? "NULL" : "TIMESTAMP_X").", ".
					"	RIGHT_MARGIN = ".IntVal($cnt).", ".
					"	LEFT_MARGIN = ".IntVal($cnt)." ".
					"WHERE ID=".IntVal($id));

			$strSql =
				"SELECT BS.ID, BS.ACTIVE ".
				"FROM b_iblock_section BS ".
				"WHERE BS.IBLOCK_ID = ".$iblockID." ".
				"	AND ".(($id > 0) ? "BS.IBLOCK_SECTION_ID = ".IntVal($id) : "BS.IBLOCK_SECTION_ID IS NULL")." ".
				"ORDER BY BS.SORT, BS.NAME ";

			$cnt++;
			$res = $DB->Query($strSql);
			while ($arr = $res->Fetch())
				$cnt = ReSort($iblockID, $arr["ID"], $cnt, $depth + 1, (($active=="Y" && $arr["ACTIVE"]=="Y") ? "Y" : "N"));

			if ($id == 0)
				return true;

			$DB->Query(
				"UPDATE b_iblock_section SET ".
				"	TIMESTAMP_X = ".($DB->type=="ORACLE"?"NULL":"TIMESTAMP_X").", ".
				"	RIGHT_MARGIN = ".IntVal($cnt).", ".
				"	DEPTH_LEVEL = ".IntVal($depth).", ".
				"	GLOBAL_ACTIVE = '".$active."' ".
				"WHERE ID=".IntVal($id));
			return $cnt + 1;
		}

		$arIBlockIDString = explode(",", $iBlockIDString);
		if (count($arIBlockIDString) > 0)
		{
			for ($i = 0; $i < count($arIBlockIDString); $i++)
			{
				if (IntVal($arIBlockIDString[$i]) == 0)
					continue;

				ReSort($arIBlockIDString[$i]);
			}
		}

		// Clear temp table
		$DB->Query("TRUNCATE TABLE b_catalog_cml_tmp");

		if (function_exists("catalog_1c_mutator_section"))
			catalog_1c_mutator_section($iBlockIDString);

		__SetTimeMark("Process sections");

		/***************************** Products ****************************************/
		// Collect products temp table
		$strSql =
			"INSERT INTO b_catalog_cml_tmp(ID, XML_ID, CATALOG_ID) ".
			"SELECT IF(P.ID IS NULL, 0, P.ID), X.XML_ID, X.CATALOG_ID ".
			"FROM b_catalog_cml_product X ".
			"	LEFT JOIN b_iblock_element P USE INDEX (ix_iblock_element_4) ON (P.XML_ID = X.XML_ID AND X.CATALOG_ID = P.IBLOCK_ID AND P.WF_PARENT_ELEMENT_ID IS NULL) ";
		$DB->Query($strSql);

		$DB->Query("REPAIR TABLE b_catalog_cml_tmp QUICK", True);

		__SetTimeMark("Process products - temp table");

		// Delete old products
		if ($keepExistingData == "N")
		{
			$num = 0;
			$productIDs = "0";

			$strSql =
				"SELECT P.ID, P.IBLOCK_ID ".
				"FROM b_iblock_element P ".
				"	LEFT JOIN b_catalog_cml_tmp T ON (P.ID = T.ID) ".
				"WHERE P.IBLOCK_ID IN (".$iBlockIDString.") ".
				"	AND T.ID IS NULL ".
				"	AND P.WF_PARENT_ELEMENT_ID IS NULL";
			$dbRes = $DB->Query($strSql);
			while ($arRes = $dbRes->Fetch())
			{
				$num++;
				$productIDs .= ",".$arRes["ID"];

				if ($num > CML_GROUP_OPERATION_CNT)
				{
					$strSql =
						"DELETE FROM b_catalog_discount2product ".
						"WHERE PRODUCT_ID IN (".$productIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"DELETE FROM b_catalog_price ".
						"WHERE PRODUCT_ID IN (".$productIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"DELETE FROM b_catalog_product2group ".
						"WHERE PRODUCT_ID IN (".$productIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"DELETE FROM b_catalog_product ".
						"WHERE ID IN (".$productIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"DELETE FROM b_iblock_element_property ".
						"WHERE IBLOCK_ELEMENT_ID IN (".$productIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"DELETE FROM b_iblock_section_element ".
						"WHERE IBLOCK_ELEMENT_ID IN (".$productIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"DELETE FROM b_workflow_move ".
						"WHERE IBLOCK_ELEMENT_ID IN (".$productIDs.") ";
					$DB->Query($strSql, True);

					$strSql =
						"DELETE FROM b_iblock_element ".
						"WHERE ID IN (".$productIDs.") ";
					$DB->Query($strSql);

					$num = 0;
					$productIDs = "0";
				}
			}

			if ($num > 0)
			{
				$strSql =
					"DELETE FROM b_catalog_discount2product ".
					"WHERE PRODUCT_ID IN (".$productIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_catalog_price ".
					"WHERE PRODUCT_ID IN (".$productIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_catalog_product2group ".
					"WHERE PRODUCT_ID IN (".$productIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_catalog_product ".
					"WHERE ID IN (".$productIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_iblock_element_property ".
					"WHERE IBLOCK_ELEMENT_ID IN (".$productIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_iblock_section_element ".
					"WHERE IBLOCK_ELEMENT_ID IN (".$productIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_workflow_move ".
					"WHERE IBLOCK_ELEMENT_ID IN (".$productIDs.") ";
				$DB->Query($strSql, True);

				$strSql =
					"DELETE FROM b_iblock_element ".
					"WHERE ID IN (".$productIDs.") ";
				$DB->Query($strSql);
			}

			// From workflow
			$num = 0;
			$productIDs = "0";

			$strSql =
				"SELECT P.ID, P.IBLOCK_ID ".
				"FROM b_iblock_element P ".
				"	LEFT JOIN b_catalog_cml_tmp T ON (P.WF_PARENT_ELEMENT_ID = T.ID) ".
				"WHERE P.IBLOCK_ID IN (".$iBlockIDString.") ".
				"	AND P.WF_PARENT_ELEMENT_ID IS NOT NULL ".
				"	AND T.ID IS NULL ";
			$dbRes = $DB->Query($strSql);
			while ($arRes = $dbRes->Fetch())
			{
				$num++;
				$productIDs .= ",".$arRes["ID"];

				if ($num > CML_GROUP_OPERATION_CNT)
				{
					$strSql =
						"DELETE FROM b_catalog_discount2product ".
						"WHERE PRODUCT_ID IN (".$productIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"DELETE FROM b_catalog_price ".
						"WHERE PRODUCT_ID IN (".$productIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"DELETE FROM b_catalog_product2group ".
						"WHERE PRODUCT_ID IN (".$productIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"DELETE FROM b_catalog_product ".
						"WHERE ID IN (".$productIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"DELETE FROM b_iblock_element_property ".
						"WHERE IBLOCK_ELEMENT_ID IN (".$productIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"DELETE FROM b_iblock_section_element ".
						"WHERE IBLOCK_ELEMENT_ID IN (".$productIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"DELETE FROM b_workflow_move ".
						"WHERE IBLOCK_ELEMENT_ID IN (".$productIDs.") ";
					$DB->Query($strSql, True);

					$strSql =
						"DELETE FROM b_iblock_element ".
						"WHERE ID IN (".$productIDs.") ";
					$DB->Query($strSql);

					$num = 0;
					$productIDs = "0";
				}
			}

			if ($num > 0)
			{
				$strSql =
					"DELETE FROM b_catalog_discount2product ".
					"WHERE PRODUCT_ID IN (".$productIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_catalog_price ".
					"WHERE PRODUCT_ID IN (".$productIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_catalog_product2group ".
					"WHERE PRODUCT_ID IN (".$productIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_catalog_product ".
					"WHERE ID IN (".$productIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_iblock_element_property ".
					"WHERE IBLOCK_ELEMENT_ID IN (".$productIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_iblock_section_element ".
					"WHERE IBLOCK_ELEMENT_ID IN (".$productIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_workflow_move ".
					"WHERE IBLOCK_ELEMENT_ID IN (".$productIDs.") ";
				$DB->Query($strSql, True);

				$strSql =
					"DELETE FROM b_iblock_element ".
					"WHERE ID IN (".$productIDs.") ";
				$DB->Query($strSql);
			}
		}

		__SetTimeMark("Process products - delete old");

		// Update products
		$strSql =
			"SELECT T.ID, X.NAME, X.MODIFIED_BY, X.CODE, S.ID as PARENT_CATEGORY, MIN(S1.ID) as CNT ".
			"FROM b_catalog_cml_tmp T ".
			"	INNER JOIN b_catalog_cml_product X ON (T.XML_ID = X.XML_ID AND T.CATALOG_ID = X.CATALOG_ID) ".
			"	LEFT JOIN b_iblock_section S ON (S.XML_ID = X.PARENT_CATEGORY AND X.CATALOG_ID = S.IBLOCK_ID) ".
			"	LEFT JOIN b_catalog_cml_product_cat XC ON (T.CATALOG_ID = XC.CATALOG_ID AND T.XML_ID = XC.PRODUCT_XML_ID) ".
			"	LEFT JOIN b_iblock_section S1 ON (S1.XML_ID = XC.CATEGORY_XML_ID AND XC.CATALOG_ID = S1.IBLOCK_ID) ".
			"WHERE T.ID > 0 ".
			"GROUP BY T.ID, X.NAME, X.MODIFIED_BY, X.CODE, S.ID";
		$dbRes = $DB->Query($strSql);
		while ($arRes = $dbRes->Fetch())
		{
			$strSql =
				"UPDATE b_iblock_element SET ".
				"	MODIFIED_BY = '".$arRes["MODIFIED_BY"]."', ".
				"	NAME = '".$DB->ForSql($arRes["NAME"])."', ".
				"	IBLOCK_SECTION_ID = ".((IntVal($arRes["PARENT_CATEGORY"]) > 0) ? IntVal($arRes["PARENT_CATEGORY"]) : "NULL" ).", ".
				"	IN_SECTIONS = '".((IntVal($arRes["CNT"]) > 0) ? "Y" : "N" )."', ".
				"	TIMESTAMP_X = NOW(), ".
				"	TMP_ID = '".$DB->ForSql($tmpid)."' ".
				(true == $boolTranslitElement ? ",	CODE = '".$DB->ForSql($arRes['CODE'])."'" : '').
				($bActivateFileData ? ",	ACTIVE = 'Y' " : "").
				"WHERE ID = ".$arRes["ID"]." ";
			$DB->Query($strSql);
		}

		__SetTimeMark("Process products - update");

		// Add new products
		$strSql =
			"INSERT INTO b_iblock_element(IBLOCK_ID, ACTIVE, MODIFIED_BY, CREATED_BY, SORT, NAME, XML_ID, IBLOCK_SECTION_ID, IN_SECTIONS, TMP_ID, TIMESTAMP_X".(true === $boolTranslitElement ? ', CODE' : '').") ".
			"SELECT T.CATALOG_ID, 'Y', X.MODIFIED_BY, X.MODIFIED_BY, 500, X.NAME, X.XML_ID, S.ID, IF(MIN(S1.ID), 'Y', 'N'), '".$DB->ForSql($tmpid)."', NOW()".(true === $boolTranslitElement ? ', X.CODE': '').' '.
			"FROM b_catalog_cml_tmp T ".
			"	INNER JOIN b_catalog_cml_product X ON (T.XML_ID = X.XML_ID AND T.CATALOG_ID = X.CATALOG_ID) ".
			"	LEFT JOIN b_iblock_section S ON (S.XML_ID = X.PARENT_CATEGORY AND X.CATALOG_ID = S.IBLOCK_ID) ".
			"	LEFT JOIN b_catalog_cml_product_cat XC ON (T.CATALOG_ID = XC.CATALOG_ID AND T.XML_ID = XC.PRODUCT_XML_ID) ".
			"	LEFT JOIN b_iblock_section S1 ON (S1.XML_ID = XC.CATEGORY_XML_ID AND XC.CATALOG_ID = S1.IBLOCK_ID) ".
			"WHERE T.ID = 0 ".
			"GROUP BY T.CATALOG_ID, X.MODIFIED_BY, X.NAME, X.XML_ID, X.CODE, S.ID";
		$DB->Query($strSql);

		__SetTimeMark("Process products - insert");

		/************************ Products 2 sections ***********************************/

		// Delete parent sections links, delete import offer lists, delete import properties
		$catalogGroupsString = "0";
		$strSql =
			"SELECT CG.ID ".
			"FROM b_catalog_cml_oflist X ".
			"	INNER JOIN b_catalog_cml_oflist_prop XP ON (XP.OFFER_LIST_XML_ID = X.ID) ".
			"	INNER JOIN b_catalog_group CG ON (CG.NAME = XP.PROPERTY_VALUE) ";
		$dbRes = $DB->Query($strSql);
		while ($arRes = $dbRes->Fetch())
			$catalogGroupsString .= ",".$arRes["ID"];

		$catalogPropertiesString = "0";
		$strSql =
			"SELECT P.ID ".
			"FROM b_catalog_cml_property XP ".
			"	INNER JOIN b_iblock_property P ON (P.XML_ID = XP.XML_ID AND XP.CATALOG_ID = P.IBLOCK_ID) ";
		$dbRes = $DB->Query($strSql);
		while ($arRes = $dbRes->Fetch())
			$catalogPropertiesString .= ",".$arRes["ID"];


		$num = 0;
		$productIDs = "0";

		$strSql =
			"SELECT P.ID ".
			"FROM b_catalog_cml_product X ".
			"	INNER JOIN b_iblock_element P ON (P.XML_ID = X.XML_ID AND X.CATALOG_ID = P.IBLOCK_ID AND P.WF_PARENT_ELEMENT_ID IS NULL) ";
		$dbRes = $DB->Query($strSql);
		while ($arRes = $dbRes->Fetch())
		{
			$num++;
			$productIDs .= ",".$arRes["ID"];

			if ($num > CML_GROUP_OPERATION_CNT)
			{
				$strSql =
					"DELETE FROM b_iblock_section_element ".
					"WHERE IBLOCK_ELEMENT_ID IN (".$productIDs.") ".
					"	AND ADDITIONAL_PROPERTY_ID IS NULL";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_catalog_price ".
					"WHERE PRODUCT_ID IN (".$productIDs.") ".
					"	AND CATALOG_GROUP_ID IN (".$catalogGroupsString.")";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_iblock_element_property ".
					"WHERE IBLOCK_PROPERTY_ID IN (".$catalogPropertiesString.") ".
					"	AND IBLOCK_ELEMENT_ID IN (".$productIDs.")";
				$DB->Query($strSql);

				$num = 0;
				$productIDs = "0";
			}
		}

		if ($num > 0)
		{
			$strSql =
				"DELETE FROM b_iblock_section_element ".
				"WHERE IBLOCK_ELEMENT_ID IN (".$productIDs.") ".
				"	AND ADDITIONAL_PROPERTY_ID IS NULL";
			$DB->Query($strSql);

			$strSql =
				"DELETE FROM b_catalog_price ".
				"WHERE PRODUCT_ID IN (".$productIDs.") ".
				"	AND CATALOG_GROUP_ID IN (".$catalogGroupsString.")";
			$DB->Query($strSql);

			$strSql =
				"DELETE FROM b_iblock_element_property ".
				"WHERE IBLOCK_PROPERTY_ID IN (".$catalogPropertiesString.") ".
				"	AND IBLOCK_ELEMENT_ID IN (".$productIDs.")";
			$DB->Query($strSql);
		}

		__SetTimeMark("Process products groups - delete old");

		// Add product parent links
		$strSql =
			"INSERT INTO b_iblock_section_element(IBLOCK_SECTION_ID, IBLOCK_ELEMENT_ID) ".
			"SELECT S.ID, P.ID ".
			"FROM b_catalog_cml_product X ".
			"	INNER JOIN b_iblock_element P ON (P.XML_ID = X.XML_ID AND X.CATALOG_ID = P.IBLOCK_ID AND P.WF_PARENT_ELEMENT_ID IS NULL) ".
			"	INNER JOIN b_catalog_cml_product_cat XC ON (X.CATALOG_ID = XC.CATALOG_ID AND X.XML_ID = XC.PRODUCT_XML_ID) ".
			"	INNER JOIN b_iblock_section S ON (S.XML_ID = XC.CATEGORY_XML_ID AND XC.CATALOG_ID = S.IBLOCK_ID) ";
		$DB->Query($strSql);

		__SetTimeMark("Process products groups");

		/************************ Product properties values ***********************************/

		// Add product property values
		$strSql =
			"INSERT INTO b_iblock_element_property(IBLOCK_PROPERTY_ID, IBLOCK_ELEMENT_ID, VALUE, VALUE_NUM) ".
			"SELECT STRAIGHT_JOIN P.ID, E.ID, XV.PROPERTY_VALUE_TEXT, XV.PROPERTY_VALUE ".
			"FROM b_catalog_cml_product_prop XV ".
			"	INNER JOIN b_iblock_property P ON (XV.CATALOG_ID = P.IBLOCK_ID AND XV.PROPERTY_XML_ID = P.XML_ID) ".
			"	INNER JOIN b_catalog_cml_property XP ON (XP.CATALOG_ID = XV.CATALOG_ID AND P.XML_ID = XP.XML_ID) ".
			"	INNER JOIN b_iblock_element E USE INDEX (ix_iblock_element_4) ON (XP.CATALOG_ID = E.IBLOCK_ID AND E.XML_ID = XV.PRODUCT_XML_ID AND E.WF_PARENT_ELEMENT_ID IS NULL) ".
			"WHERE P.PROPERTY_TYPE <> 'L' ";
		$DB->Query($strSql);

		__SetTimeMark("Process products properties - insert");

		$strSql =
			"INSERT INTO b_iblock_element_property(IBLOCK_PROPERTY_ID, IBLOCK_ELEMENT_ID, VALUE, value_enum) ".
			"SELECT STRAIGHT_JOIN P.ID, E.ID, PE.ID, PE.ID ".
			"FROM b_catalog_cml_product_prop XV ".
			"	INNER JOIN b_iblock_property P ON (XV.CATALOG_ID = P.IBLOCK_ID AND XV.PROPERTY_XML_ID = P.XML_ID) ".
			"	INNER JOIN b_catalog_cml_property XP ON (XP.CATALOG_ID = XV.CATALOG_ID AND XP.XML_ID = P.XML_ID) ".
			"	INNER JOIN b_iblock_element E USE INDEX (ix_iblock_element_4) ON (XP.CATALOG_ID = E.IBLOCK_ID AND E.XML_ID = XV.PRODUCT_XML_ID AND E.WF_PARENT_ELEMENT_ID IS NULL) ".
			"	INNER JOIN b_iblock_property_enum PE ON (PE.PROPERTY_ID = P.ID AND XV.PROPERTY_VALUE = PE.XML_ID) ".
			"WHERE P.PROPERTY_TYPE = 'L' ";
		$DB->Query($strSql);

		__SetTimeMark("Process products properties - insert enum");

		// Clear temp table
		$DB->Query("TRUNCATE TABLE b_catalog_cml_tmp");

		if (function_exists("catalog_1c_mutator_product"))
			catalog_1c_mutator_product($iBlockIDString);

		__SetTimeMark("Process products properties");

		/***************************** Offers ****************************************/

		// Collect offers temp table
		$strSql =
			"INSERT INTO b_catalog_cml_tmp(ID, XML_ID, CATALOG_ID, VALUE_ID) ".
			"SELECT IF(CP.ID IS NULL, 0, CP.ID), X.XML_ID, X.CATALOG_ID, P.ID ".
			"FROM b_catalog_cml_product X ".
			"	INNER JOIN b_iblock_element P USE INDEX (ix_iblock_element_4) ON (P.XML_ID = X.XML_ID AND X.CATALOG_ID = P.IBLOCK_ID AND P.WF_PARENT_ELEMENT_ID IS NULL) ".
			"	LEFT JOIN b_catalog_product CP ON (P.ID = CP.ID) ";
		$DB->Query($strSql);

		$DB->Query("REPAIR TABLE b_catalog_cml_tmp QUICK", True);

		__SetTimeMark("Process offers - temp table");

		// Update catalog products
		$strSql =
			"SELECT T.ID, MIN(X.AMOUNT) as CNT ".
			"FROM b_catalog_cml_tmp T ".
			"	INNER JOIN b_catalog_cml_oflist XL ON (XL.CATALOG_ID = T.CATALOG_ID) ".
			"	LEFT JOIN b_catalog_cml_offer X ON (X.PRODUCT_XML_ID = T.XML_ID AND X.OFFER_LIST_XML_ID = XL.ID) ".
			"WHERE T.ID > 0 ".
			"GROUP BY T.ID";
		$dbRes = $DB->Query($strSql);
		while ($arRes = $dbRes->Fetch())
		{
			$strSql =
				"UPDATE b_catalog_product SET ".
				"	QUANTITY = ".IntVal($arRes["CNT"])." ".
				"WHERE ID = ".$arRes["ID"]." ";
			$DB->Query($strSql);
		}

		__SetTimeMark("Process offers - update products");

		// Add new catalog products
		$strSql =
			"INSERT INTO b_catalog_product(ID, QUANTITY) ".
			"SELECT T.VALUE_ID, MIN(X.AMOUNT) ".
			"FROM b_catalog_cml_tmp T ".
			"	INNER JOIN b_catalog_cml_oflist XL ON (XL.CATALOG_ID = T.CATALOG_ID) ".
			"	LEFT JOIN b_catalog_cml_offer X ON (X.PRODUCT_XML_ID = T.XML_ID AND X.OFFER_LIST_XML_ID = XL.ID) ".
			"WHERE T.ID = 0 ".
			"GROUP BY T.VALUE_ID";
		$DB->Query($strSql);

		__SetTimeMark("Process offers - insert products");

		// Add offers
		$strSql =
			"INSERT INTO b_catalog_price(PRODUCT_ID, CATALOG_GROUP_ID, PRICE, CURRENCY)".
			"SELECT P.ID, CG.ID, XO.PRICE, XO.CURRENCY ".
			"FROM b_catalog_cml_product X ".
			"	INNER JOIN b_iblock_element P ON (P.XML_ID = X.XML_ID AND X.CATALOG_ID = P.IBLOCK_ID AND P.WF_PARENT_ELEMENT_ID IS NULL) ".
			"	INNER JOIN b_catalog_cml_oflist XL ON (XL.CATALOG_ID = P.IBLOCK_ID) ".
			"	INNER JOIN b_catalog_cml_oflist_prop XLP ON (XLP.OFFER_LIST_XML_ID = XL.ID) ".
			"	INNER JOIN b_catalog_group CG ON (CG.NAME = XLP.PROPERTY_VALUE) ".
			"	INNER JOIN b_catalog_cml_offer XO ON (XO.OFFER_LIST_XML_ID = XL.ID AND XO.PRODUCT_XML_ID = X.XML_ID) ";
		$DB->Query($strSql);

		if (function_exists("catalog_1c_mutator_offer"))
			catalog_1c_mutator_offer($iBlockIDString, $catalogGroupsString);

		__SetTimeMark("Process offers");

		/*************************** Delete old properties *********************************/

		if (!$bKeepExistingProperties && ($keepExistingData == "N"))
		{
			$num = 0;
			$propertyIDs = "0";

			$strSql =
				"SELECT P.ID, P.IBLOCK_ID ".
				"FROM b_iblock_property P ".
				"	LEFT JOIN b_catalog_cml_property X ON (P.IBLOCK_ID = X.CATALOG_ID AND P.XML_ID = X.XML_ID) ".
				"WHERE P.IBLOCK_ID IN (".$iBlockIDString.") ".
				"	AND X.XML_ID IS NULL ";
			$dbRes = $DB->Query($strSql);
			while ($arRes = $dbRes->Fetch())
			{
				$num++;
				$propertyIDs .= ",".$arRes["ID"];

				if ($num > CML_GROUP_OPERATION_CNT)
				{
					$strSql =
						"DELETE FROM b_iblock_element_property ".
						"WHERE IBLOCK_PROPERTY_ID IN (".$propertyIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"DELETE FROM b_iblock_property_enum ".
						"WHERE PROPERTY_ID IN (".$propertyIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"DELETE FROM b_iblock_section_element ".
						"WHERE ADDITIONAL_PROPERTY_ID IN (".$propertyIDs.") ";
					$DB->Query($strSql);

					$strSql =
						"DELETE FROM b_iblock_property ".
						"WHERE ID IN (".$propertyIDs.") ";
					$DB->Query($strSql);

					$num = 0;
					$productIDs = "0";
				}
			}

			if ($num > 0)
			{
				$strSql =
					"DELETE FROM b_iblock_element_property ".
					"WHERE IBLOCK_PROPERTY_ID IN (".$propertyIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_iblock_property_enum ".
					"WHERE PROPERTY_ID IN (".$propertyIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_iblock_section_element ".
					"WHERE ADDITIONAL_PROPERTY_ID IN (".$propertyIDs.") ";
				$DB->Query($strSql);

				$strSql =
					"DELETE FROM b_iblock_property ".
					"WHERE ID IN (".$propertyIDs.") ";
				$DB->Query($strSql);
			}
		}

		__SetTimeMark("Process old properties");
	}
	else
	{
		// Collect offers temp table
		$strSql =
			"INSERT INTO b_catalog_cml_tmp(ID, XML_ID, CATALOG_ID, VALUE_ID) ".
			"SELECT DISTINCT STRAIGHT_JOIN SQL_BIG_RESULT IF(CP.ID IS NULL, 0, CP.ID), P.XML_ID, P.IBLOCK_ID, P.ID ".
			"FROM b_catalog_cml_offer X ".
			"	INNER JOIN b_catalog_cml_oflist XL ON (X.OFFER_LIST_XML_ID = XL.ID) ".
			"	INNER JOIN b_iblock_element P USE INDEX (ix_iblock_element_4) ON (P.XML_ID = X.PRODUCT_XML_ID AND XL.CATALOG_ID = P.IBLOCK_ID AND P.WF_PARENT_ELEMENT_ID IS NULL) ".
			"	LEFT JOIN b_catalog_product CP ON (P.ID = CP.ID) ";
		$DB->Query($strSql);

		$DB->Query("REPAIR TABLE b_catalog_cml_tmp QUICK", True);

		__SetTimeMark("Process offers - temp table");

		// Update catalog products
		$strSql =
			"SELECT T.ID, MIN(X.AMOUNT) as CNT ".
			"FROM b_catalog_cml_tmp T ".
			"	INNER JOIN b_catalog_cml_oflist XL ON (XL.CATALOG_ID = T.CATALOG_ID) ".
			"	LEFT JOIN b_catalog_cml_offer X ON (X.PRODUCT_XML_ID = T.XML_ID AND X.OFFER_LIST_XML_ID = XL.ID) ".
			"WHERE T.ID > 0 ".
			"GROUP BY T.ID";
		$dbRes = $DB->Query($strSql);
		while ($arRes = $dbRes->Fetch())
		{
			$strSql =
				"UPDATE b_catalog_product SET ".
				"	QUANTITY = ".IntVal($arRes["CNT"])." ".
				"WHERE ID = ".$arRes["ID"]." ";
			$DB->Query($strSql);
		}

		__SetTimeMark("Process offers - update products");

		// Add new catalog products
		$strSql =
			"INSERT INTO b_catalog_product(ID, QUANTITY) ".
			"SELECT T.VALUE_ID, MIN(X.AMOUNT) ".
			"FROM b_catalog_cml_tmp T ".
			"	INNER JOIN b_catalog_cml_oflist XL ON (XL.CATALOG_ID = T.CATALOG_ID) ".
			"	LEFT JOIN b_catalog_cml_offer X ON (X.PRODUCT_XML_ID = T.XML_ID AND X.OFFER_LIST_XML_ID = XL.ID) ".
			"WHERE T.ID = 0 ".
			"GROUP BY T.VALUE_ID";
		$DB->Query($strSql);

		__SetTimeMark("Process offers - insert products");

		// Delete import offer lists
		$catalogGroupsString = "0";
		$strSql =
			"SELECT CG.ID ".
			"FROM b_catalog_cml_oflist X ".
			"	INNER JOIN b_catalog_cml_oflist_prop XP ON (XP.OFFER_LIST_XML_ID = X.ID) ".
			"	INNER JOIN b_catalog_group CG ON (CG.NAME = XP.PROPERTY_VALUE) ";
		$dbRes = $DB->Query($strSql);
		while ($arRes = $dbRes->Fetch())
			$catalogGroupsString .= ",".$arRes["ID"];

		$num = 0;
		$productIDs = "0";

		$strSql =
			"SELECT T.VALUE_ID ".
			"FROM b_catalog_cml_tmp T ";
		$dbRes = $DB->Query($strSql);
		while ($arRes = $dbRes->Fetch())
		{
			$num++;
			$productIDs .= ",".$arRes["VALUE_ID"];

			if ($num > CML_GROUP_OPERATION_CNT)
			{
				$strSql =
					"DELETE FROM b_catalog_price ".
					"WHERE PRODUCT_ID IN (".$productIDs.") ".
					"	AND CATALOG_GROUP_ID IN (".$catalogGroupsString.")";
				$DB->Query($strSql);

				$num = 0;
				$productIDs = "0";
			}
		}

		if ($num > 0)
		{
			$strSql =
				"DELETE FROM b_catalog_price ".
				"WHERE PRODUCT_ID IN (".$productIDs.") ".
				"	AND CATALOG_GROUP_ID IN (".$catalogGroupsString.")";
			$DB->Query($strSql);
		}

		__SetTimeMark("Process offers - delete offers");

		// Add offers
		$strSql =
			"INSERT INTO b_catalog_price(PRODUCT_ID, CATALOG_GROUP_ID, PRICE, CURRENCY)".
			"SELECT STRAIGHT_JOIN P.ID, CG.ID, XO.PRICE, XO.CURRENCY ".
			"FROM b_catalog_cml_offer XO ".
			"	INNER JOIN b_catalog_cml_oflist XL ON (XL.ID = XO.OFFER_LIST_XML_ID) ".
			"	INNER JOIN b_iblock_element P USE INDEX (ix_iblock_element_4) ON (P.XML_ID = XO.PRODUCT_XML_ID AND XL.CATALOG_ID = P.IBLOCK_ID AND P.WF_PARENT_ELEMENT_ID IS NULL) ".
			"	INNER JOIN b_catalog_cml_oflist_prop XLP ON (XLP.OFFER_LIST_XML_ID = XL.ID) ".
			"	INNER JOIN b_catalog_group CG ON (CG.NAME = XLP.PROPERTY_VALUE) ";
		$DB->Query($strSql);

		if (function_exists("catalog_1c_mutator_offer"))
			catalog_1c_mutator_offer($iBlockIDString, $catalogGroupsString);

		__SetTimeMark("Process offers");
	}

	/***************************** Final clearing ****************************************/

	$DB->Query("TRUNCATE TABLE b_catalog_cml_property");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_property_var");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_section");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_product");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_product_cat");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_product_prop");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_oflist");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_oflist_prop");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_offer");
	$DB->Query("TRUNCATE TABLE b_catalog_cml_tmp");

	if (function_exists("catalog_1c_mutator_final"))
		catalog_1c_mutator_final($iBlockIDString);

	__SetTimeMark("Clear temp tables", "STOP");
}

if (strlen($strImportErrorMessage) <= 0)
{
	$totalExecutionTime = RoundEx(getmicrotime() - $startImportExecTime, 1);
	$totalExecutionTimeM = RoundEx($totalExecutionTime / 60, 1);
	$strImportOKMessage .= str_replace("#MIN#", (($totalExecutionTimeM > 1) ? " (".$totalExecutionTimeM." ".GetMessage("CML_R_MIN").")" : ""), str_replace("#TIME#", $totalExecutionTime, GetMessage("CML_R_TIME")))."<br>";
	$strImportOKMessage .= str_replace("#NUM#", $cmlLoadCnts["CATALOG"], GetMessage("CML_R_NCATA"))."<br> ";
	$strImportOKMessage .= str_replace("#NUM#", $cmlLoadCnts["PROPERTY"], GetMessage("CML_R_NPROP"))."<br> ";
	$strImportOKMessage .= str_replace("#NUM#", $cmlLoadCnts["SECTION"], GetMessage("CML_R_NGRP"))."<br> ";
	$strImportOKMessage .= str_replace("#NUM#", $cmlLoadCnts["PRODUCT"], GetMessage("CML_R_NPRD"))."<br> ";
	$strImportOKMessage .= str_replace("#NUM#", $cmlLoadCnts["OFFER"], GetMessage("CML_R_NOFF"))."<br> ";
}

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