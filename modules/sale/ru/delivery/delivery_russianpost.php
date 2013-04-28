<?
/*********************************************************************************
Delivery handler for Russian Post Service (http://www.russianpost.ru/)
It uses on-line calculator. Delivery only from Moscow.
Files:
- russianpost/country.php - list of russianpost country ids
*********************************************************************************/

CModule::IncludeModule("sale");

IncludeModuleLangFile('/bitrix/modules/sale/delivery/delivery_russianpost.php');

// define('DELIVERY_RUSSIANPOST_WRITE_LOG', 0); // flag 'write to log'. use CDeliveryRUSSIANPOST::__WriteToLog() for logging.
// define('DELIVERY_RUSSIANPOST_CACHE_LIFETIME', 2592000); // cache lifetime - 30 days (60*60*24*30)

// define('DELIVERY_RUSSIANPOST_CATEGORY_DEFAULT', 23); // default delivery type

// define('DELIVERY_RUSSIANPOST_PRICE_TARIFF', 0.03); // price koefficient - 3%
// define('DELIVERY_RUSSIANPOST_PRICE_TARIFF_1', 0.04); // price koefficient - 4%

// define('DELIVERY_RUSSIANPOST_SERVER_POST_CATEGORY', 'viewPost');
// define('DELIVERY_RUSSIANPOST_SERVER_POST_CATEGORY_NAME', 'viewPostName');
// define('DELIVERY_RUSSIANPOST_SERVER_POST_PROFILE', 'typePost');
// define('DELIVERY_RUSSIANPOST_SERVER_POST_PROFILE_NAME', 'typePostName');
// define('DELIVERY_RUSSIANPOST_SERVER_POST_ZIP', 'postOfficeId');
// define('DELIVERY_RUSSIANPOST_SERVER_POST_WEIGHT', 'weight');
// define('DELIVERY_RUSSIANPOST_SERVER_POST_PRICE', 'value1');

// define('DELIVERY_RUSSIANPOST_SERVER_POST_COUNTRY', 'countryCode');
// define('DELIVERY_RUSSIANPOST_SERVER_POST_COUNTRY_NAME', 'countryCodeName');

// define('DELIVERY_RUSSIANPOST_SERVER', 'www.russianpost.ru');
// define('DELIVERY_RUSSIANPOST_SERVER_PORT', 80);
// define('DELIVERY_RUSSIANPOST_SERVER_PAGE', '/autotarif/Autotarif.aspx');
// define('DELIVERY_RUSSIANPOST_SERVER_METHOD', 'GET');
// define('DELIVERY_RUSSIANPOST_SERVER_METHOD_CAPTHA', 'POST');

// define('DELIVERY_RUSSIANPOST_VALUE_CHECK_STRING', '<span id="TarifValue">');
define(
	'DELIVERY_RUSSIANPOST_VALUE_CHECK_REGEXP_RUS',
	'/<sup>\*<\/sup><\/td><td align="Right">*([0-9,]+)<\/td>/i'
);
define(
	'DELIVERY_RUSSIANPOST_VALUE_CHECK_REGEXP',
	'/<span id="TarifValue">*([0-9,]+)<\/span>/i'
);
// define('DELIVERY_RUSSIANPOST_VALUE_CAPTHA_STRING', '<input id="key"');
define(
	'DELIVERY_RUSSIANPOST_CAPTHA_REGEXP',
	'/<input id="key" name="key" value="*([0-9,]+)"\/>/i'
);

class CDeliveryRUSSIANPOST
{
	public static function Init()
	{
		if ($arCurrency = CCurrency::GetByID('RUR'))
		{
			$base_currency = 'RUR';
		}
		else
		{
			$base_currency = 'RUB';
		}

		return array(
			/* Basic description */
			"SID" => "russianpost",
			"NAME" => GetMessage('SALE_DH_RUSSIANPOST_NAME'),
			"DESCRIPTION" => GetMessage('SALE_DH_RUSSIANPOST_DESCRIPTION'),
			"DESCRIPTION_INNER" => GetMessage('SALE_DH_RUSSIANPOST_DESCRIPTION_INNER').GetMessage('SALE_DH_RUSSIANPOST_DESCRIPTION_INNER2'),
			"BASE_CURRENCY" => $base_currency,

			"HANDLER" => __FILE__,

			/* Handler methods */
			"DBGETSETTINGS" => array("CDeliveryRUSSIANPOST", "GetSettings"),
			"DBSETSETTINGS" => array("CDeliveryRUSSIANPOST", "SetSettings"),
			"GETCONFIG" => array("CDeliveryRUSSIANPOST", "GetConfig"),

			"COMPABILITY" => array("CDeliveryRUSSIANPOST", "Compability"),
			"CALCULATOR" => array("CDeliveryRUSSIANPOST", "Calculate"),

			/* List of delivery profiles */
			"PROFILES" => array(
				"ground" => array(
					"TITLE" => GetMessage("SALE_DH_RUSSIANPOST_GROUND_TITLE"),
					"DESCRIPTION" => '', //GetMessage("SALE_DH_RUSSIANPOST_GROUND_DESCRIPTION"),

					"RESTRICTIONS_WEIGHT" => array(0),
					"RESTRICTIONS_SUM" => array(0),
				),

				"avia" => array(
					"TITLE" => GetMessage("SALE_DH_RUSSIANPOST_AVIA_TITLE"),
					"DESCRIPTION" => '', //GetMessage("SALE_DH_RUSSIANPOST_AVIA_DESCRIPTION"),

					"RESTRICTIONS_WEIGHT" => array(0),
					"RESTRICTIONS_SUM" => array(0),
				),
			)
		);
	}

	public static function GetConfig()
	{
		$arConfig = array(
			"CONFIG_GROUPS" => array(
				"all" => GetMessage('SALE_DH_RUSSIANPOST_CONFIG_TITLE'),
			),

			"CONFIG" => array(
				"category" => array(
					"TYPE" => "DROPDOWN",
					"DEFAULT" => DELIVERY_RUSSIANPOST_CATEGORY_DEFAULT,
					"TITLE" => GetMessage('SALE_DH_RUSSIANPOST_CONFIG_CATEGORY'),
					"GROUP" => "all",
					"VALUES" => array(),
				),
			),
		);

		//$arList = array(42, 43, 44, 23, 52, 12, 13, 30, 41, 50, 33, 26, 53, 36, 16, 51, 54);
		$arList = array(23, 12, 13, 26, 36, 16);

		for ($i = 0, $cnt = count($arList); $i < $cnt; $i++)
		{
			$arConfig["CONFIG"]["category"]["VALUES"][$arList[$i]] = GetMessage('SALE_DH_RUSSIANPOST_CONFIG_CATEGORY_'.$arList[$i]);
		}

		return $arConfig;
	}

	public static function GetSettings($strSettings)
	{
		return array(
			"category" => intval($strSettings)
		);
	}

	public static function SetSettings($arSettings)
	{
		return intval($arSettings["category"]);
	}

	function __GetLocation($location, $bGetZIP = false)
	{
		$arLocation = CSaleLocation::GetByID($location);

		$arLocation["IS_RUSSIAN"] = CDeliveryRUSSIANPOST::__IsRussian($arLocation) ? "Y" : "N";
		if ($bGetZIP)
		{
			$arLocation["ZIP"] = array();

			if ($arLocation["IS_RUSSIAN"] == "Y")
			{
				$rsZIPList = CSaleLocation::GetLocationZIP($location);
				while ($arZIP = $rsZIPList->Fetch())
				{
					$arLocation["ZIP"][] = $arZIP["ZIP"];
				}
			}
		}

		return $arLocation;
	}

	function __GetCountry($arLocation)
	{
		static $arRUSSIANPOSTCountryList;

		if (!is_array($arRUSSIANPOSTCountryList))
		{
			require("russianpost/country.php");
		}

		foreach ($arRUSSIANPOSTCountryList as $country_id => $country_name)
		{
			if (
				ToUpper($arLocation["COUNTRY_NAME_ORIG"]) == $country_name
				|| ToUpper($arLocation["COUNTRY_SHORT_NAME"]) == $country_name
				|| ToUpper($arLocation["COUNTRY_NAME_LANG"]) == $country_name
				|| ToUpper($arLocation["COUNTRY_NAME"]) == $country_name
			)
			{
				return array(
					"ID" => $country_id,
					"NAME" => $country_name,
				);
			}
		}
	}

	public static function Calculate($profile, $arConfig, $arOrder, $STEP, $TEMP = false)
	{
		if ($STEP >= 3)
			return array(
				"RESULT" => "ERROR",
				"TEXT" => GetMessage('SALE_DH_RUSSIANPOST_ERROR_CONNECT'),
			);

		if ($arOrder["WEIGHT"] <= 0) $arOrder["WEIGHT"] = 1;

		$arLocationFrom = CDeliveryRUSSIANPOST::__GetLocation($arOrder["LOCATION_FROM"]);

		if ($arOrder['LOCATION_ZIP'])
		{
			$arLocationTo = CDeliveryRUSSIANPOST::__GetLocation($arOrder["LOCATION_TO"]);
			$arLocationTo['ZIP'] = array(0 => $arOrder['LOCATION_ZIP']);
		}
		else
		{
			$arLocationTo = CDeliveryRUSSIANPOST::__GetLocation($arOrder["LOCATION_TO"], true);
		}

		$zip = COption::GetOptionString('sale', 'location_zip');
		if (strlen($zip) > 0)
			$arLocationFrom["ZIP"] = array(0 => $zip);

		if ($arLocationTo["IS_RUSSIAN"] == 'Y' && count($arLocationTo["ZIP"]) <= 0)
		{
			return array(
				"RESULT" => "ERROR",
				"TEXT" => GetMessage('SALE_DH_RUSSIANPOST_ERROR_NOZIP'),
			);
		}

		$cache_id = "sale|8.0.3|russianpost|".$profile."|".$arConfig["category"]["VALUE"]."|".$arOrder["LOCATION_FROM"]."|".($arLocationTo["IS_RUSSIAN"] == 'Y' ? $arLocationTo["ZIP"][0] : $arOrder["LOCATION_TO"]);

		if (in_array($arConfig["category"]["VALUE"], array(23, 12, 13, 26, 16)))
			$cache_id .= "|".ceil(CSaleMeasure::Convert($arOrder["WEIGHT"], "G", "KG")/20);
		else
			$cache_id .= "|".ceil(CSaleMeasure::Convert($arOrder["WEIGHT"], "G", "KG")/500);

		$obCache = new CPHPCache();
		if ($obCache->InitCache(DELIVERY_RUSSIANPOST_CACHE_LIFETIME, $cache_id, "/"))
		{
			$vars = $obCache->GetVars();
			$result = $vars["RESULT"];

			// only these delivery types have insurance tax of 3% or 4% from price
			if (in_array($arConfig["category"]["VALUE"], array(26, 16)))
				$result += $arOrder["PRICE"] * DELIVERY_RUSSIANPOST_PRICE_TARIFF;
			elseif ($arConfig["category"]["VALUE"] == 36)
				$result += $arOrder["PRICE"] * DELIVERY_RUSSIANPOST_PRICE_TARIFF_1;

			return array(
				"RESULT" => "OK",
				"VALUE" => $result
			);
		}

		$arQuery = array();

		$arProfile = array("ground" => 1, "avia" => 2);

		if ($arLocationTo["IS_RUSSIAN"] == "Y")
		{
			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_CATEGORY."=".urlencode($arConfig["category"]["VALUE"]);
			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_CATEGORY_NAME."=".urlencode(GetMessage("SALE_DH_RUSSIANPOST_CONFIG_CATEGORY_".$arConfig["category"]["VALUE"]));

			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_PROFILE."=".urlencode($arProfile[$profile]);
			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_PROFILE_NAME.'='.urlencode(GetMessage("SALE_DH_RUSSIANPOST_".ToUpper($profile)));
			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_COUNTRY."=643";
			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_COUNTRY_NAME.'='.urlencode($GLOBALS['APPLICATION']->ConvertCharset('Российская Федерация', LANG_CHARSET, 'utf-8'));

			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_WEIGHT."=".urlencode($arOrder["WEIGHT"]);

			// price does not affect on half of delivery types. others have 3% or 4% insurance tax which is ignored here for caching and used later.
			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_PRICE."=0";
			// if (!in_array($arConfig["category"]["VALUE"], array(26, 36, 16)))
			// {
				// $arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_PRICE."=".urlencode(round($arOrder["PRICE"]));
			// }
			// else
			// {
				// $arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_PRICE."=0";
			// }
			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_ZIP."=".urlencode($arLocationTo["ZIP"][0]);
		}
		else
		{
			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_CATEGORY."=".urlencode($arConfig["category"]["VALUE"]);
			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_CATEGORY_NAME."=".urlencode(GetMessage("SALE_DH_RUSSIANPOST_CONFIG_CATEGORY_".$arConfig["category"]["VALUE"]));
			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_PROFILE."=".urlencode($arProfile[$profile]);
			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_PROFILE_NAME.'='.urlencode(GetMessage("SALE_DH_RUSSIANPOST_".ToUpper($profile)));
			$arCountry = CDeliveryRUSSIANPOST::__GetCountry($arLocationTo);

			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_COUNTRY."=".urlencode($arCountry["ID"]);
			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_COUNTRY_NAME."=".urlencode($GLOBALS['APPLICATION']->ConvertCharset($arCountry["NAME"], LANG_CHARSET, 'utf-8'));

			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_WEIGHT."=".urlencode($arOrder["WEIGHT"]);
			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_PRICE."=0";
			$arQuery[] = DELIVERY_RUSSIANPOST_SERVER_POST_ZIP."=0";
		}

		$data = QueryGetData(
			DELIVERY_RUSSIANPOST_SERVER,
			DELIVERY_RUSSIANPOST_SERVER_PORT,
			DELIVERY_RUSSIANPOST_SERVER_PAGE,
			implode("&", $arQuery),
			$error_number = 0,
			$error_text = "",
			DELIVERY_RUSSIANPOST_SERVER_METHOD
		);

		$data = $GLOBALS['APPLICATION']->ConvertCharset($data, 'utf-8', LANG_CHARSET);

		CDeliveryRUSSIANPOST::__Write2Log($error_number.": ".$error_text);
		CDeliveryRUSSIANPOST::__Write2Log($data);

		if (strlen($data) <= 0)
		{
			return array(
				"RESULT" => "ERROR",
				"TEXT" => GetMessage('SALE_DH_RUSSIANPOST_ERROR_CONNECT'),
			);
		}

		if (strstr($data, DELIVERY_RUSSIANPOST_VALUE_CAPTHA_STRING))
		{
			$cResult = preg_match(
					DELIVERY_RUSSIANPOST_CAPTHA_REGEXP,
					$data,
					$matches
			);

			$arCode = array();
			$arCode["key"] = IntVal($matches[1]);

			$data = QueryGetData(
				DELIVERY_RUSSIANPOST_SERVER,
				DELIVERY_RUSSIANPOST_SERVER_PORT,
				DELIVERY_RUSSIANPOST_SERVER_PAGE,
				implode("&", $arCode),
				$error_number = 0,
				$error_text = "",
				DELIVERY_RUSSIANPOST_SERVER_METHOD_CAPTHA
			);
		}

		if (strstr($data, DELIVERY_RUSSIANPOST_VALUE_CHECK_STRING))
		{
			$bResult = preg_match(
				DELIVERY_RUSSIANPOST_VALUE_CHECK_REGEXP_RUS,
				$data,
				$matches
			);

			// both regexps must be checked! it's not only for russian and non-russian
			if (/*$arLocationTo["IS_RUSSIAN"] == "Y" && */!$bResult)
			{
				$bResult = preg_match(
					DELIVERY_RUSSIANPOST_VALUE_CHECK_REGEXP,
					$data,
					$matches
				);
			}

			if ($bResult)
			{
				$obCache->StartDataCache();

				$result = $matches[1];
				$result = str_replace(array(" ", ","), array("", "."), $result);
				$result = doubleval($result);

				$obCache->EndDataCache(
					array(
						"RESULT" => $result
					)
				);

				// only these delivery types have insurance tax of 3% or 4% from price
				if (in_array($arConfig["category"]["VALUE"], array(36, 16)))
					$result += $arOrder["PRICE"] * DELIVERY_RUSSIANPOST_PRICE_TARIFF;
				elseif ($arConfig["category"]["VALUE"] == 26)
					$result += $arOrder["PRICE"] * DELIVERY_RUSSIANPOST_PRICE_TARIFF_1;

				return array(
					"RESULT" => "OK",
					"VALUE" => $result,
				);
			}
			else
			{
				return array(
					"RESULT" => "ERROR",
					"TEXT" => GetMessage('SALE_DH_RUSSIANPOST_ERROR_RESPONSE'),
				);
			}
		}
		else
			return array(
				"RESULT" => "ERROR",
				"TEXT" => GetMessage('SALE_DH_RUSSIANPOST_ERROR_RESPONSE'),
			);
	}

	public static function Compability($arOrder, $arConfig)
	{
		$arLocationFrom = CSaleLocation::GetByID($arOrder["LOCATION_FROM"]);

		if (
			ToUpper($arLocationFrom["CITY_NAME_ORIG"]) == "МОСКВА"
			|| ToUpper($arLocationFrom["CITY_SHORT_NAME"]) == "МОСКВА"
			|| ToUpper($arLocationFrom["CITY_NAME_LANG"]) == "МОСКВА"
			|| ToUpper($arLocationFrom["CITY_NAME_ORIG"]) == "MOSCOW"
			|| ToUpper($arLocationFrom["CITY_SHORT_NAME"]) == "MOSCOW"
			|| ToUpper($arLocationFrom["CITY_NAME_LANG"]) == "MOSCOW"
		)
		{
			$arLocationTo = CSaleLocation::GetByID($arOrder["LOCATION_TO"]);

			if (!CDeliveryRUSSIANPOST::__IsRussian($arLocationTo) && $arConfig['category']['VALUE'] == 26)
				return array();

			if (isset($arConfig["category"]["VALUE"]) && $arConfig["category"]["VALUE"] == 26 )
				return array("ground");
			else
				return array("ground", "avia");

		}
		else
		{
			return array();
		}
	}

	function __IsRussian($arLocation)
	{
		return
			(ToUpper($arLocation["COUNTRY_NAME_ORIG"]) == "РОССИЯ"
			|| ToUpper($arLocation["COUNTRY_SHORT_NAME"]) == "РОССИЯ"
			|| ToUpper($arLocation["COUNTRY_NAME_LANG"]) == "РОССИЯ"
			|| ToUpper($arLocation["COUNTRY_NAME_ORIG"]) == "RUSSIA"
			|| ToUpper($arLocation["COUNTRY_SHORT_NAME"]) == "RUSSIA"
			|| ToUpper($arLocation["COUNTRY_NAME_LANG"]) == "RUSSIA"
			|| ToUpper($arLocation["COUNTRY_NAME_ORIG"]) == "РОССИЙСКАЯ ФЕДЕРАЦИЯ"
			|| ToUpper($arLocation["COUNTRY_SHORT_NAME"]) == "РОССИЙСКАЯ ФЕДЕРАЦИЯ"
			|| ToUpper($arLocation["COUNTRY_NAME_LANG"]) == "РОССИЙСКАЯ ФЕДЕРАЦИЯ"
			|| ToUpper($arLocation["COUNTRY_NAME_ORIG"]) == "RUSSIAN FEDERATION"
			|| ToUpper($arLocation["COUNTRY_SHORT_NAME"]) == "RUSSIAN FEDERATION"
			|| ToUpper($arLocation["COUNTRY_NAME_LANG"]) == "RUSSIAN FEDERATION");
	}

	function __Write2Log($data)
	{
		if (defined('DELIVERY_RUSSIANPOST_WRITE_LOG') && DELIVERY_RUSSIANPOST_WRITE_LOG === 1)
		{
			$fp = fopen(dirname(__FILE__)."/russianpost.log", "a");
			fwrite($fp, "\r\n==========================================\r\n");
			fwrite($fp, $data);
			fclose($fp);
		}
	}
}

AddEventHandler("sale", "onSaleDeliveryHandlersBuildList", array('CDeliveryRUSSIANPOST', 'Init'));
?>