<?
/**********************************************************************
Delivery handler for CPCR delivery service (http://www.cpcr.ru/)
It uses on-line calculator. Calculation only to Russia.
Files:
cpcr/cities.php - cache of cpcr ids for cities
cpcr/locations.php - list of cpcr ids for countries.
**********************************************************************/

CModule::IncludeModule("sale");

IncludeModuleLangFile('/bitrix/modules/sale/delivery/delivery_cpcr.php');

// define('DELIVERY_CPCR_WRITE_LOG', 0); // flag 'write to log'. use CDeliveryCPCR::__WriteToLog() for logging.
// define('DELIVERY_CPCR_CACHE_LIFETIME', 2592000); // cache lifetime - 30 days (60*60*24*30)

// define('DELIVERY_CPCR_CATEGORY_DEFAULT', 8); // default category for delivered goods

// define('DELIVERY_CPCR_PRICE_TARIFF', 0.0025); // price koefficient - 0.25%

// define('DELIVERY_CPCR_COUNTRY_DEFAULT', '209|0'); // default country - Russia
// define('DELIVERY_CPCR_CITY_DEFAULT', '992|0'); // default city - Moscow

//define('DELIVERY_CPCR_SERVER', 'old.cpcr.ru'); // server name to send data
// define('DELIVERY_CPCR_SERVER', 'spsr.ru'); // server name to send data

// define('DELIVERY_CPCR_SERVER_PORT', 80); // server port
//define('DELIVERY_CPCR_SERVER_PAGE', '/components/tarifcalc2/tarifcalc.php?JsHttpRequest='); // server page url
// define('DELIVERY_CPCR_SERVER_PAGE', '/cgi-bin/post09.pl?TARIFFCOMPUTE_2'); // server page url

//define('DELIVERY_CPCR_SERVER_METHOD', 'POST'); // data send method
// define('DELIVERY_CPCR_SERVER_METHOD', 'GET'); // data send method

//define('DELIVERY_CPCR_SERVER_POST_FROM_REGION', 'from_select_region'); // query variable name for "from" region id
// define('DELIVERY_CPCR_SERVER_POST_FROM_REGION', 'FromRegion'); // query variable name for "from" region id

//define('DELIVERY_CPCR_SERVER_POST_FROM_COUNTRY', 'from_select_country'); // query variable name for "from" region id
// define('DELIVERY_CPCR_SERVER_POST_FROM_COUNTRY', 'FromCountry'); // query variable name for "from" region id

//define('DELIVERY_CPCR_SERVER_POST_FROM_CITY_NAME', 'from_Cities_name'); // query variable name for "from" city name
// define('DELIVERY_CPCR_SERVER_POST_FROM_CITY_NAME', 'FromCity'); // query variable name for "from" city name

//define('DELIVERY_CPCR_SERVER_POST_FROM_CITY', 'from_Cities_Id'); // query variable name for "from" city id
// define('DELIVERY_CPCR_SERVER_POST_FROM_CITY', 'FromCity'); // query variable name for "from" city id

// define('DELIVERY_CPCR_SERVER_POST_WEIGHT', 'Weight'); // query variable name for order weight
// define('DELIVERY_CPCR_SERVER_POST_CATEGORY', 'Nature'); // query variable name for order goods category
// define('DELIVERY_CPCR_SERVER_POST_PRICE', 'Amount'); // query variable name for order price


//define('DELIVERY_CPCR_SERVER_POST_TO_COUNTRY', 'to_select_country'); // query variable name for "to" country id
// define('DELIVERY_CPCR_SERVER_POST_TO_COUNTRY', 'Country'); // query variable name for "to" country id

//define('DELIVERY_CPCR_SERVER_POST_TO_REGION', 'to_select_region'); // query variable name for "to" region id
// define('DELIVERY_CPCR_SERVER_POST_TO_REGION', 'ToRegion'); // query variable name for "to" region id

//define('DELIVERY_CPCR_SERVER_POST_TO_CITY_NAME', 'to_Cities_name'); // query variable name for "to" city name
// define('DELIVERY_CPCR_SERVER_POST_TO_CITY_NAME', 'to_Cities_name'); // query variable name for "to" city name

//define('DELIVERY_CPCR_SERVER_POST_TO_CITY', 'to_Cities_Id'); // query variable name for "to" city id
// define('DELIVERY_CPCR_SERVER_POST_TO_CITY', 'ToCity'); // query variable name for "to" city id


// define('DELIVERY_CPCR_SERVER_POST_ADDITIONAL', 'Amount=0&AmountCheck=1&SMS=0&InHands=0&BeforeSignal=0&DuesOrder=0&PlatType=0&GabarythSum=60&GabarythB=0'); // additional POST data

// define('DELIVERY_CPCR_VALUE_CHECK_STRING', '"Total"'); // first check string - to determine whether delivery price is in response
//define(
//	'DELIVERY_CPCR_VALUE_CHECK_REGEXP',
//	'/"(result[2]{0,1})": \[([^\]]*)\]/i'
//); // second check string - regexp to parse final price from response

class CDeliveryCPCR
{
	public static function Init()
	{
		// fix a possible currency bug
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
			"SID" => "cpcr", // unique string identifier
			"NAME" => GetMessage('SALE_DH_CPCR_NAME'), // handler public title
			"DESCRIPTION" => GetMessage('SALE_DH_CPCR_DESCRIPTION'), // handler public dedcription
			"DESCRIPTION_INNER" => GetMessage('SALE_DH_CPCR_DESCRIPTION_INNER'), // handler private description for admin panel
			"BASE_CURRENCY" => $base_currency, // handler base currency

			"HANDLER" => __FILE__, // handler path - don't change it if you do not surely know what you are doin

			"COMPABILITY" => array("CDeliveryCPCR", "Compability"), // callback method to check whether handler is compatible with current order
			"CALCULATOR" => array("CDeliveryCPCR", "Calculate"), // callback method to calculate delivery price

			/* List of delivery profiles */
			"PROFILES" => array(
				"simple" => array(
					"TITLE" => GetMessage("SALE_DH_CPCR_SIMPLE_TITLE"),
					"DESCRIPTION" => GetMessage("SALE_DH_CPCR_SIMPLE_DESCRIPTION"),

					"RESTRICTIONS_WEIGHT" => array(0, 31500),
					"RESTRICTIONS_SUM" => array(0, 500000),
				),
				"simple13" => array(
					"TITLE" => GetMessage("SALE_DH_CPCR_SIMPLE13_TITLE"),
					"DESCRIPTION" => GetMessage("SALE_DH_CPCR_SIMPLE_DESCRIPTION"),

					"RESTRICTIONS_WEIGHT" => array(0, 31500),
					"RESTRICTIONS_SUM" => array(0, 500000),
				),
				"simple18" => array(
					"TITLE" => GetMessage("SALE_DH_CPCR_SIMPLE18_TITLE"),
					"DESCRIPTION" => GetMessage("SALE_DH_CPCR_SIMPLE_DESCRIPTION"),

					"RESTRICTIONS_WEIGHT" => array(0, 31500),
					"RESTRICTIONS_SUM" => array(0, 500000),
				),
				"econom" => array(
					"TITLE" => GetMessage("SALE_DH_CPCR_ECONOM_TITLE"),
					"DESCRIPTION" => GetMessage("SALE_DH_CPCR_ECONOM_DESCRIPTION"),

					"RESTRICTIONS_WEIGHT" => array(0, 68000),
					"RESTRICTIONS_SUM" => array(0, 500000),
				),

				"bizon" => array(
					"TITLE" => GetMessage("SALE_DH_CPCR_BIZON_TITLE"),
					"DESCRIPTION" => GetMessage("SALE_DH_CPCR_BIZON_DESCRIPTION"),

					"RESTRICTIONS_WEIGHT" => array(0, 68000),
					"RESTRICTIONS_SUM" => array(0, 500000),
				),
				"colibri" => array(
					"TITLE" => GetMessage("SALE_DH_CPCR_COLIBRI_TITLE"),
					"DESCRIPTION" => GetMessage("SALE_DH_CPCR_COLIBRI_DESCRIPTION"),

					"RESTRICTIONS_WEIGHT" => array(0, 68000),
					"RESTRICTIONS_SUM" => array(0, 500000),
				),
				"pelican" => array(
					"TITLE" => GetMessage("SALE_DH_CPCR_PELICAN_TITLE"),
					"DESCRIPTION" => GetMessage("SALE_DH_CPCR_PELICAN_DESCRIPTION"),

					"RESTRICTIONS_WEIGHT" => array(0, 68000),
					"RESTRICTIONS_SUM" => array(0, 500000),
				),
				"fraxt" => array(
					"TITLE" => GetMessage("SALE_DH_CPCR_FRAXT_TITLE"),
					"DESCRIPTION" => GetMessage("SALE_DH_CPCR_FRAXT_DESCRIPTION"),

					"RESTRICTIONS_WEIGHT" => array(0, 68000),
					"RESTRICTIONS_SUM" => array(0, 500000),
				)
			)
		);
	}

	public static function GetConfig()
	{
		return array();

		$arConfig = array(
			"CONFIG_GROUPS" => array(
				"all" => GetMessage('SALE_DH_CPCR_CONFIG_TITLE'),
			),

			"CONFIG" => array(
				"category" => array(
					"TYPE" => "DROPDOWN",
					"DEFAULT" => DELIVERY_CPCR_CATEGORY_DEFAULT,
					"TITLE" => GetMessage('SALE_DH_CPCR_CONFIG_CATEGORY'),
					"GROUP" => "all",
					"VALUES" => array(),
				),
			),
		);

		for ($i = 1; $i < 9; $i++)
		{
			$arConfig["CONFIG"]["category"]["VALUES"][$i] = GetMessage('SALE_DH_CPCR_CONFIG_CATEGORY_'.$i);
		}

		return $arConfig;
	}

	public static function GetSettings($strSettings)
	{
		return array();
		return array(
			"category" => intval($strSettings)
		);
	}

	public static function SetSettings($arSettings)
	{
		return array();
		$category = intval($arSettings["category"]);
		if ($category <= 0 || $category > 8) return DELIVERY_CPCR_CATEGORY_DEFAULT;
		else return $category;
	}

	function __GetLocation($location)
	{
		static $arCPCRCountries;
		static $arCPCRCity;

		$arLocation = CSaleLocation::GetByID($location);

		$arReturn = array();

		if (!is_array($arCPCRCountries))
		{
			require ("cpcr/locations.php");
		}

		foreach ($arCPCRCountries as $country_id => $country_title)
		{
			if (
				$country_title == $arLocation["COUNTRY_NAME_ORIG"]
				||
				$country_title == $arLocation["COUNTRY_SHORT_NAME"]
				||
				$country_title == $arLocation["COUNTRY_NAME_LANG"]
				||
				$country_title == $arLocation["COUNTRY_NAME"]
			)
			{
				$arReturn["COUNTRY"] = $country_id;
				break;
			}
		}

		$arReturn["CITY"] = $arLocation["CITY_NAME_LANG"];

		if (!is_array($arCPCRCity))
		{
			require ("cpcr/cities.php");
		}

		/*
		if (is_set($arCPCRCity, $arLocation["CITY_ID"]))
		{
			$arReturn["CITY_ID"] = $arCPCRCity[$arLocation["CITY_ID"]];
		}
		*/
		foreach ($arCPCRCity as $city_id => $city_title)
		{
			if (
				$city_title == $arLocation["CITY_NAME_ORIG"]
				||
				$city_title == $arLocation["CITY_SHORT_NAME"]
				||
				$city_title == $arLocation["CITY_NAME_LANG"]
				||
				$city_title == $arLocation["CITY_NAME"]
			)
			{
				$arReturn["CITY_ID"] = $city_id;
				break;
			}
		}

		$arReturn["ORIGINAL"] = array(
			"ID" => $arLocation["ID"],
			"COUNTRY_ID" => $arLocation["COUNTRY_ID"],
			"CITY_ID" => $arLocation["CITY_ID"],
		);

		return $arReturn;
	}

	public static function Calculate($profile, $arConfig, $arOrder, $STEP)
	{
		if ($STEP >= 3)
			return array(
				"RESULT" => "ERROR",
				"TEXT" => GetMessage('SALE_DH_CPCR_ERROR_CONNECT'),
			);

		$arOrder["WEIGHT"] = CSaleMeasure::Convert($arOrder["WEIGHT"], "G", "KG");
		if ($arOrder["WEIGHT"] <= 0) $arOrder["WEIGHT"] = 1; // weight must not be null - let it be 1 kg

		$arLocationFrom = CDeliveryCPCR::__GetLocation($arOrder["LOCATION_FROM"]);
		$arLocationTo = CDeliveryCPCR::__GetLocation($arOrder["LOCATION_TO"]);

		// caching is dependent from category, locations "from" & "to" and from weight interval
		$cache_id = "sale3|9.5.0|cpcr|".$arConfig["category"]['VALUE']."|".$arLocationFrom["ORIGINAL"]["COUNTRY_ID"]."|".$arLocationFrom["ORIGINAL"]["CITY_ID"]."|".$arLocationTo["ORIGINAL"]["COUNTRY_ID"]."|".$arLocationTo["ORIGINAL"]["CITY_ID"];

		if ($arOrder["WEIGHT"] <= 0.5) $cache_id .= "|0"; // first interval - up to 0.5 kg
		elseif ($arOrder["WEIGHT"] <= 1) $cache_id .= "|1"; //2nd interval - up to 1 kg
		else $cache_id .= "|".ceil($arOrder["WEIGHT"]); // other intervals - up to next natural number

		$obCache = new CPHPCache();

		if ($obCache->InitCache(DELIVERY_CPCR_CACHE_LIFETIME, $cache_id, "/"))
		{
			// cache found
			$vars = $obCache->GetVars();
			$arResult = $vars["RESULT"];
		}
		else
		{
			// format HTTP query request data
			$arQuery = array();

			$arQuery[] = DELIVERY_CPCR_SERVER_POST_FROM_COUNTRY."=".urlencode($arLocationFrom["COUNTRY"]);

			if (is_set($arLocationFrom["CITY_ID"]))
				$arQuery[] = DELIVERY_CPCR_SERVER_POST_FROM_CITY."=".urlencode($arLocationFrom["CITY_ID"]);
			else
				$arQuery[] = DELIVERY_CPCR_SERVER_POST_FROM_CITY_NAME."=".urlencode($GLOBALS['APPLICATION']->ConvertCharset($arLocationFrom["CITY"], LANG_CHARSET, 'windows-1251'));

			$arQuery[] = DELIVERY_CPCR_SERVER_POST_WEIGHT."=".urlencode($arOrder["WEIGHT"]);
			$arQuery[] = DELIVERY_CPCR_SERVER_POST_CATEGORY."="."1";//urlencode($arConfig["category"]["VALUE"]);

			// price coefficient will be added later - to make caching independent from price
			$arQuery[] = DELIVERY_CPCR_SERVER_POST_PRICE."=0";
			$arQuery[] = DELIVERY_CPCR_SERVER_POST_TO_COUNTRY."=".urlencode($arLocationTo["COUNTRY"]);

			/*
			if (is_set($arLocationTo["REGION"]))
				$arQuery[] = DELIVERY_CPCR_SERVER_POST_TO_REGION."=".urlencode($arLocationTo["REGION"]);
			else
				$arQuery[] = DELIVERY_CPCR_SERVER_POST_TO_REGION."=".urlencode(DELIVERY_CPCR_CITY_DEFAULT);
			*/

			if (is_set($arLocationTo["CITY_ID"]))
				$arQuery[] = DELIVERY_CPCR_SERVER_POST_TO_CITY."=".urlencode($arLocationTo["CITY_ID"]);
			else
				$arQuery[] = DELIVERY_CPCR_SERVER_POST_TO_CITY_NAME."=".urlencode($GLOBALS['APPLICATION']->ConvertCharset($arLocationTo["CITY"], LANG_CHARSET, 'windows-1251'));

			CDeliveryCPCR::__Write2Log(print_r($arLocationTo, true));

			$arQuery[] = DELIVERY_CPCR_SERVER_POST_ADDITIONAL;
			$query_string = implode("&", $arQuery);

			$query_page = DELIVERY_CPCR_SERVER_PAGE;

			// get data from server
			$ob = new CHTTP();
			$ob->http_timeout = 50;

			$data = $ob->Query(
					DELIVERY_CPCR_SERVER_METHOD,
					DELIVERY_CPCR_SERVER,
					DELIVERY_CPCR_SERVER_PORT,
					$query_page . (DELIVERY_CPCR_SERVER_METHOD == 'GET' ? ((strpos($query_page, '?') === false ? '?' : '&') . $query_string) : ''),
					DELIVERY_CPCR_SERVER_METHOD == 'POST' ? $query_string : false
					//,
					// "",
					// "" // Empty content-type because of CPCR inner bugs
				);

			if($data)
				$data = $GLOBALS["APPLICATION"]->ConvertCharset($ob->result, 'windows-1251', LANG_CHARSET);

			CDeliveryCPCR::__Write2Log($query_page);
			CDeliveryCPCR::__Write2Log($query_string);
			CDeliveryCPCR::__Write2Log($error_number.": ".$error_text);
			CDeliveryCPCR::__Write2Log($data);

			if (strpos($data, "<?xml") === false)
			{
				return array(
					"RESULT" => "ERROR",
					"TEXT" => GetMessage('SALE_DH_CPCR_ERROR_CONNECT'),
				);
			}

			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/xml.php");
			$objXML = new CDataXML();
			$objXML->LoadString($data);
			$arResult = $objXML->GetArray();

			$arProfiles = array(
				'SIMPLE' => '"Р“Р•РџРђР Р”-Р­РљРЎРџР Р•РЎРЎ"',
				'ECONOM' => '"РџР•Р›Р?РљРђРќ-РЎРўРђРќР”РђР Рў"',
				'SIMPLE13' => '"Р“Р•РџРђР Р”-Р­РљРЎРџР Р•РЎРЎ 13"',
				'SIMPLE18' => '"Р“Р•РџРђР Р”-Р­РљРЎРџР Р•РЎРЎ 18"',
				'BIZON' => '"Р‘Р?Р—РћРќ-РљРђР Р“Рћ"',
				'COLIBRI' => '"РљРћР›Р?Р‘Р Р?-Р”РћРљРЈРњР•РќРў"',
				'PELICAN' => '"РџР•Р›Р?РљРђРќ-РћРќР›РђР™Рќ"',
				'FRAXT' => '"Р¤Р РђРҐРў"',
				);
			$arTmpResult = array();

			if(isset($arResult["root"]["#"]["Error"]) AND is_array($arResult["root"]["#"]["Error"]))
			{
				return array(
					"RESULT" => "ERROR",
					"TEXT" => GetMessage('SALE_DH_CPCR_ERROR_CONNECT').' ('.htmlspecialcharsbx(strip_tags($arResult["root"]["#"]["Tariff"][0]["#"]["TariffType"][0]["#"])).')',
				);
			}
			else
			{
				if(!empty($arResult["root"]["#"]["Tariff"]))
				{
					foreach($arResult["root"]["#"]["Tariff"] as $key => $val)
					{
						foreach($val["#"] as $k => $v)
						{
							foreach ($arProfiles as $prof => $title)
							{
								if (ToUpper($v[0]["#"]) == ToUpper($title))
								{
									$arTmpResult[ToLower($prof)] = array(
										'VALUE' => $val["#"]["Total_Dost"][0]["#"],
										'TRANSIT' => $val["#"]["DP"][0]["#"]
									);
									unset($arProfiles[$prof]);
									break;
								}
							}
						}
					}
				}
				$arResult = $arTmpResult;
				if(count($arTmpResult) > 0)
				{
					$obCache->StartDataCache();
					$obCache->EndDataCache(
						array(
							"RESULT" => $arResult
						)
					);
				}
				else
				{
					return array(
						"RESULT" => "ERROR",
						"TEXT" => GetMessage('SALE_DH_CPCR_ERROR_CONNECT'),
					);
				}
			}
		}

		if (is_array($arResult[$profile]))
		{
			$arResult[$profile]['RESULT'] = 'OK';

			// it's starnge but it seems that CPCR new calculator doesnt count insurance tax at all. so, temporarily comment this line.
			// TODO: check this later
			//$arResult[$profile]['VALUE'] += $arOrder["PRICE"] * DELIVERY_CPCR_PRICE_TARIFF

			return $arResult[$profile];
		}
		else
		{
			return array(
				"RESULT" => "ERROR",
				"TEXT" => GetMessage('SALE_DH_CPCR_ERROR_RESPONSE'),
			);
		}
	}

public static 	function Compability($arOrder)
	{
		$arLocationFrom = CDeliveryCPCR::__GetLocation($arOrder["LOCATION_FROM"]);
		$arLocationTo = CDeliveryCPCR::__GetLocation($arOrder["LOCATION_TO"]);

		// delivery only from russia and to russia
		if (
			$arLocationFrom["COUNTRY"] != DELIVERY_CPCR_COUNTRY_DEFAULT
			||
			$arLocationTo["COUNTRY"] != DELIVERY_CPCR_COUNTRY_DEFAULT
		)
			return array();
		else
		{
			$arProfiles = array("simple", "econom");

			if ($arLocationFrom['CITY_ID'] == DELIVERY_CPCR_CITY_DEFAULT)
			{
				if (in_array($arLocationTo['CITY_ID'], array(
					'269|0', '328|0', '1587|0', '455|0', '551|0', '713|0', '873|0', '924|0', '1054|0', '552|0', '1243|0', '1309|0', '1448|0', '893|0', '1828|0', '1907|0', '189|0', '2011|0', '2137|0'
				)))
				{
					$arProfiles[] = "simple13";
				}

				if (in_array($arLocationTo['CITY_ID'], array(
					'199|0', '1063|0', '220|0', '286|0', '328|0', '347|0', '1071|0', '1587|0', '1916|0', '1726|0', '735|0', '785|0', '1083|0', '873|0', '1768|0', '1054|0', '1145|0', '552|0', '1176|0', '1243|0', '1309|0', '1387|0', '1472|0', '893|0', '1522|0', '1485|0', '1907|0', '189|0', '2011|0', '345|0'
				)))
				{
					$arProfiles[] = "simple18";
				}
			}
			elseif ($arLocationTo['CITY_ID'] == DELIVERY_CPCR_CITY_DEFAULT)
			{
				if (in_array($arLocationFrom['CITY_ID'], array(
					'2137|0', '1828|0', '1781|0', '1722|0', '1660|0', '1448|0', '924|0', '713|0', '551|0', '455|0', '286|0', '269|0', '122|0', '1746|0', '1042|0', '1759|0', '199|0', '1063|0', '220|0', '286|0', '328|0', '347|0', '1071|0', '1587|0', '1916|0', '1726|0', '735|0', '785|0', '1083|0', '873|0', '1768|0', '1054|0', '1145|0', '552|0', '1243|0', '1309|0', '1387|0', '1472|0', '893|0', '1522|0', '1485|0', '1907|0', '189|0', '2011|0', '345|0'
				)))
				{
					$arProfiles[] = "simple13";
				}

				if (in_array($arLocationFrom['CITY_ID'], array(
					'122|0', '1746|0', '1042|0', '1759|0', '199|0', '1063|0', '220|0', '286|0', '328|0', '347|0', '1071|0', '1587|0', '1916|0', '1726|0', '735|0', '785|0', '1083|0', '873|0', '1768|0', '1054|0', '1145|0', '552|0', '1176|0', '1243|0', '1309|0', '1387|0', '1472|0', '893|0', '1522|0', '1485|0', '1907|0', '189|0', '2011|0', '345|0'
				)))
				{
					$arProfiles[] = "simple18";
				}
			}

			return $arProfiles; //array("simple", "simple13", "simple18", "econom");
		}
	}

public static 	function __Write2Log($data)
	{
		if (defined('DELIVERY_CPCR_WRITE_LOG') && DELIVERY_CPCR_WRITE_LOG === 1)
		{
			$fp = fopen(dirname(__FILE__)."/cpcr.log", "a");
			fwrite($fp, "\r\n==========================================\r\n");
			fwrite($fp, $data);
			fclose($fp);
		}
	}
}

AddEventHandler("sale", "onSaleDeliveryHandlersBuildList", array('CDeliveryCPCR', 'Init'));
?>