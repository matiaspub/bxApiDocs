<?
/*********************************************************************************
Delivery handler for EMS Russian Post Service (http://www.emspost.ru/)
It uses on-line api. Delivery only from Russia.
Files:
- ems/city.php - list of EMS city ids
- ems/country.php - list of EMS country ids
*********************************************************************************/

CModule::IncludeModule("sale");

IncludeModuleLangFile('/bitrix/modules/sale/delivery/delivery_ems.php');

// define('DELIVERY_EMS_CACHE_LIFETIME', 2592000); // cache lifetime - 30 days (60*60*24*30)
// define('DELIVERY_EMS_PRICE_TARIFF', 0.01); // price koefficient - 1%
// define('DELIVERY_EMS_WRITE_LOG', 0); // flag 'write to log'. use CDeliveryEMS::__WriteToLog() for logging.

class CDeliveryEMS
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
			"SID" => "ems",
			"NAME" => GetMessage('SALE_DH_EMS_NAME'),
			"DESCRIPTION" => GetMessage('SALE_DH_EMS_DESCRIPTION'),
			"DESCRIPTION_INNER" => GetMessage('SALE_DH_EMS_DESCRIPTION_INNER'),
			"BASE_CURRENCY" => $base_currency,

			"HANDLER" => __FILE__,

			/* Handler methods */
			"DBGETSETTINGS" => array("CDeliveryEMS", "GetSettings"),
			"DBSETSETTINGS" => array("CDeliveryEMS", "SetSettings"),
			"GETCONFIG" => array("CDeliveryEMS", "GetConfig"),

			"COMPABILITY" => array("CDeliveryEMS", "Compability"),
			"CALCULATOR" => array("CDeliveryEMS", "Calculate"),

			/* List of delivery profiles */
			"PROFILES" => array(
				"delivery" => array(
					"TITLE" => GetMessage("SALE_DH_EMS_DELIVERY_TITLE"),
					"DESCRIPTION" => '', //GetMessage("SALE_DH_EMS_DELIVERY_DESCRIPTION"),

					"RESTRICTIONS_WEIGHT" => array(0, 31500),
					"RESTRICTIONS_SUM" => array(0),
				),
			)
		);
	}

	public static function GetConfig()
	{
		$arConfig = array(
			"CONFIG_GROUPS" => array(
				"all" => GetMessage('SALE_DH_EMS_CONFIG_TITLE'),
			),

			"CONFIG" => array(
				"category" => array(
					"TYPE" => "DROPDOWN",
					"DEFAULT" => 'att',
					"TITLE" => GetMessage('SALE_DH_EMS_CONFIG_CATEGORY'),
					"GROUP" => "all",
					"VALUES" => array(
						'att' => GetMessage('SALE_DH_EMS_CONFIG_CATEGORY_att'),
						'doc' => GetMessage('SALE_DH_EMS_CONFIG_CATEGORY_doc'),
					),
				),
			),
		);

		return $arConfig;
	}

	public static function GetSettings($strSettings)
	{
		return array(
			"category" => $strSettings == 'doc' ? 'doc' : 'att'
		);
	}

	public static function SetSettings($arSettings)
	{
		return ($arSettings["category"] == 'doc' ? 'doc' : 'att');
	}

	public static function ConvertCharsetArray($arData, $charset_from, $charset_to)
	{
		if (!is_array($arData))
			return $GLOBALS['APPLICATION']->ConvertCharset($arData, $charset_from, $charset_to);

		foreach ($arData as $key => $value)
		{
			$arData[$key] = CDeliveryEMS::ConvertCharsetArray($value, $charset_from, $charset_to);
		}

		return $arData;
	}

	public static function JsObjectToPhp($data)
	{
		$arResult = array();

		if (function_exists('json_decode')) // php > 5.2.0 + php_json
		{
			$data = $GLOBALS['APPLICATION']->ConvertCharset($data, LANG_CHARSET, 'utf-8');

			// json_decode recognize only UTF strings
			$arResult = json_decode($data, true);

			if (is_array($arResult))
			{
				$arResult = CDeliveryEMS::ConvertCharsetArray($arResult, 'utf-8', LANG_CHARSET);
			}
		}
		elseif (substr($data, 0, 1) == '{') // object
		{
			$arResult = array();

			$depth = 0;
			$end_pos = 0;
			$arCommaPos = array();
			for ($i = 1, $len = strlen($data); $i < $len; $i++)
			{
				$cur_symbol = substr($data, $i, 1);
				if ($cur_symbol == '{' || $cur_symbol == '[')
					$depth++;
				elseif ($cur_symbol == ']')
					$depth--;
				elseif ($cur_symbol == '}')
				{
					if ($depth == 0)
					{
						$end_pos = $i;
						break;
					}
					else
					{
						$depth--;
					}
				}
				elseif ($cur_symbol == ',' && $depth == 0)
				{
					$arCommaPos[] = $i;
				}
			}

			if ($end_pos == 0)
				return false;

			$token = substr($data, 1, $end_pos-1);

			$arTokens = array();
			if (count($arCommaPos) > 0)
			{
				$prev_index = 0;
				foreach ($arCommaPos as $pos)
				{
					$arTokens[] = substr($token, $prev_index, $pos - $prev_index - 1);
					$prev_index = $pos;
				}
				$arTokens[] = substr($token, $prev_index);
			}
			else
			{
				$arTokens[] = $token;
			}

			foreach ($arTokens as $token)
			{
				$arTokenData = explode(":", $token, 2);

				if (substr($arTokenData[0], 0, 1) == '"')
					$arTokenData[0] = substr($arTokenData[0], 1, -1);

				$arResult[$arTokenData[0]] = CDeliveryEMS::JsObjectToPhp($arTokenData[1]);
			}
		}
		elseif (substr($data, 0, 1) == '[') // array
		{
			$arResult = array();

			$depth = 0;
			$end_pos = 0;
			$arCommaPos = array();

			for ($i = 1, $len = strlen($data); $i < $len; $i++)
			{
				$cur_symbol = substr($data, $i, 1);
				if ($cur_symbol == '{' || $cur_symbol == '[')
					$depth++;
				elseif ($cur_symbol == '}')
					$depth--;
				elseif ($cur_symbol == ']')
				{
					if ($depth == 0)
					{
						$end_pos = $i;
						break;
					}
					else
					{
						$depth--;
					}
				}
				elseif ($cur_symbol == ',' && $depth == 0)
				{
					$arCommaPos[] = $i;
				}
			}

			if ($end_pos == 0)
				return false;

			$token = substr($data, 1, $end_pos-1);

			if (count($arCommaPos) > 0)
			{
				$prev_index = 0;
				foreach ($arCommaPos as $pos)
				{
					$arResult[] = CDeliveryEMS::JsObjectToPhp(substr($token, $prev_index, $pos - $prev_index - 1));
					$prev_index = $pos;
				}
				$arResult[] = CDeliveryEMS::JsObjectToPhp(substr($token, $prev_index));
			}
			else
			{
				$arResult[] = CDeliveryEMS::JsObjectToPhp($token);
			}
		}
		else // scalar
		{
			if (substr($data, 0, 1) == '"')
				$data = substr($data, 1, -1);

			$arResult = $data;
		}

		return $arResult;
	}

	function __EMSQuery($method, $arParams = array())
	{
		$arQuery = array('method='.$method);

		foreach ($arParams as $key => $value)
			$arQuery[] = $key.'='.urlencode($value);

		$data = QueryGetData(
			'www.emspost.ru',
			80,
			'/api/rest',
			implode("&", $arQuery),
			$error_number = 0,
			$error_text = "",
			'GET'
		);

		$data = $GLOBALS['APPLICATION']->ConvertCharset($data, 'utf-8', LANG_CHARSET);

		if (($pos = strpos($data, "\n")) !== false)
		{
			$data = trim(substr($data, 0, $pos));
		}

		CDeliveryEMS::__Write2Log($error_number.": ".$error_text);
		CDeliveryEMS::__Write2Log($data);

		$arResult = CDeliveryEMS::JsObjectToPhp($data);

		return $arResult;
	}

	function __GetLocation($location)
	{
		$arLocation = CSaleLocation::GetByID($location);

		$arLocation["IS_RUSSIAN"] = CDeliveryEMS::__IsRussian($arLocation) ? "Y" : "N";

		if ($arLocation["IS_RUSSIAN"] == 'Y')
		{
			static $arEMSCityList;

			if (!is_array($arEMSCityList))
			{
				if (file_exists(dirname(__FILE__).'/ems/city.php'))
					require_once(dirname(__FILE__).'/ems/city.php');
			}

			$arLocation['CITY_NAME_ORIG'] = ToUpper($arLocation['CITY_NAME_ORIG']);
			$arLocation['CITY_SHORT_NAME'] = ToUpper($arLocation['CITY_SHORT_NAME']);
			$arLocation['CITY_NAME_LANG'] = ToUpper($arLocation['CITY_NAME_LANG']);
			$arLocation['CITY_NAME'] = ToUpper($arLocation['CITY_NAME']);

			if (is_array($arEMSCityList))
			{
				$arLocation['EMS_ID'] =
					$arEMSCityList[$arLocation['CITY_NAME_ORIG']] ? $arEMSCityList[$arLocation['CITY_NAME_ORIG']] :	(
						$arEMSCityList[$arLocation['CITY_SHORT_NAME']] ? $arEMSCityList[$arLocation['CITY_SHORT_NAME']] : (
							$arEMSCityList[$arLocation['CITY_NAME_LANG']] ? $arEMSCityList[$arLocation['CITY_NAME_LANG']] : (
								$arEMSCityList[$arLocation['CITY_NAME']] ? $arEMSCityList[$arLocation['CITY_NAME']] : (
									$arEMSCityList[ToUpper($arLocation['CITY_NAME'])] ? $arEMSCityList[ToUpper($arLocation['CITY_NAME'])] : ''
								)
							)
						)
					);

				$arLocation['EMS_TYPE'] = 'city';
			}
			else
			{
				$arLocation['EMS_CITIES_NOT_LOADED'] = true;
			}
		}
		else
		{
			static $arEMSCountryList;

			if (!is_array($arEMSCountryList))
			{
				if (file_exists(dirname(__FILE__).'/ems/country.php'))
					require_once(dirname(__FILE__).'/ems/country.php');
			}

			if (is_array($arEMSCountryList))
			{				
				$arLocation['EMS_ID'] =
					$arEMSCountryList[$arLocation['COUNTRY_NAME_ORIG']] ? $arEMSCountryList[$arLocation['COUNTRY_NAME_ORIG']] :	(
						$arEMSCountryList[$arLocation['COUNTRY_SHORT_NAME']] ? $arEMSCountryList[$arLocation['COUNTRY_SHORT_NAME']] : (
							$arEMSCountryList[$arLocation['COUNTRY_NAME_LANG']] ? $arEMSCountryList[$arLocation['COUNTRY_NAME_LANG']] : (
								$arEMSCountryList[$arLocation['COUNTRY_NAME']] ? $arEMSCountryList[$arLocation['COUNTRY_NAME']] : (
									$arEMSCountryList[ToUpper($arLocation['COUNTRY_NAME'])] ? $arEMSCountryList[ToUpper($arLocation['COUNTRY_NAME'])] : ''
								)
							)
						)
					);
			}
			else
			{
				$arLocation['EMS_COUNTRIES_NOT_LOADED'] = true;
			}
		}

		return $arLocation;
	}

	public static function Calculate($profile, $arConfig, $arOrder, $STEP, $TEMP = false)
	{
		//echo '<pre style="text-align: left;">'; print_r($arOrder); print_r($arConfig); echo '</pre>';

		if ($STEP >= 4)
			return array(
				"RESULT" => "ERROR",
				"TEXT" => GetMessage('SALE_DH_EMS_ERROR_CONNECT'),
			);

		if ($arOrder["WEIGHT"] <= 0) $arOrder["WEIGHT"] = 1;

		$arLocationTo = CDeliveryEMS::__GetLocation($arOrder["LOCATION_TO"]);

		if ($arLocationTo['IS_RUSSIAN'] == 'Y')
			$arLocationFrom = CDeliveryEMS::__GetLocation($arOrder["LOCATION_FROM"]);

		if (isset($arLocationTo['EMS_CITIES_NOT_LOADED']))
		{
			// get cities and proceed to next step

			$data = CDeliveryEMS::__EMSQuery('ems.get.locations', array('type' => 'cities', 'plain' => 'true'));

			if (!is_array($data) || $data['rsp']['stat'] != 'ok' || !is_array($data['rsp']['locations']))
			{
				return array(
					"RESULT" => "ERROR",
					"TEXT" => GetMessage('SALE_DH_EMS_ERROR_CONNECT'),
				);
			}

			$arCitiesList = array();
			foreach ($data['rsp']['locations'] as $arLocation)
			{
				$arCitiesList[$arLocation['name']] = $arLocation['value'];
			}

			CheckDirPath(dirname(__FILE__)."/ems/");
			if ($fp = fopen(dirname(__FILE__)."/ems/city.php", "w"))
			{
				fwrite($fp, '<'."?\r\n");
				fwrite($fp, '$'."arEMSCityList = array();\r\n");
				foreach ($arCitiesList as $key => $value)
				{
					fwrite($fp, '$'."arEMSCityList['".addslashes($key)."'] = '".htmlspecialcharsbx(trim($value))."';\r\n");
				}
				fwrite($fp, '?'.'>');
				fclose($fp);
			}

			return array(
				"RESULT" => "NEXT_STEP",
				"TEXT" => GetMessage('SALE_DH_EMS_CORRECT_CITIES'),
			);
		}

		if (isset($arLocationTo['EMS_COUNTRIES_NOT_LOADED']))
		{
			// get cities and proceed to next step

			$data = CDeliveryEMS::__EMSQuery('ems.get.locations', array('type' => 'countries', 'plain' => 'true'));

			if (!is_array($data) || $data['rsp']['stat'] != 'ok' || !is_array($data['rsp']['locations']))
			{
				return array(
					"RESULT" => "ERROR",
					"TEXT" => GetMessage('SALE_DH_EMS_ERROR_CONNECT'),
				);
			}

			$arCountriesList = array();
			foreach ($data['rsp']['locations'] as $arLocation)
			{
				$arCountriesList[$arLocation['name']] = $arLocation['value'];
			}

			CheckDirPath(dirname(__FILE__)."/ems/");
			if ($fp = fopen(dirname(__FILE__)."/ems/country.php", "w"))
			{
				fwrite($fp, '<'."?\r\n");
				fwrite($fp, '$'."arEMSCountryList = array();\r\n");
				foreach ($arCountriesList as $key => $value)
				{
					fwrite($fp, '$'."arEMSCountryList['".addslashes($key)."'] = '".htmlspecialcharsbx(trim($value))."';\r\n");
				}
				fwrite($fp, '?'.'>');
				fclose($fp);
			}

			return array(
				"RESULT" => "NEXT_STEP",
				"TEXT" => GetMessage('SALE_DH_EMS_CORRECT_COUNTRIES'),
			);
		}

		// echo '<pre style="text-align: left">';
		// print_r($arLocationFrom);
		// print_r($arLocationTo);
		// echo '</pre>';

		if (!$arLocationTo['EMS_ID'])
		{
			if ($arLocationTo['IS_RUSSIAN'] == 'Y')
				$text = str_replace('#CITY#', $arLocationTo['CITY_NAME_ORIG'], GetMessage('SALE_DH_EMS_ERROR_NO_CITY_TO'));
			else
				$text = str_replace('#COUNTRY#', $arLocationTo['COUNTRY_NAME_ORIG'], GetMessage('SALE_DH_EMS_ERROR_NO_COUNTRY_TO'));

			return array(
				"RESULT" => "ERROR",
				"TEXT" => $text,
			);
		}

		if ($arLocationTo['IS_RUSSIAN'] == 'Y' && !$arLocationFrom['EMS_ID'])
		{
			$text = str_replace('#CITY#', $arLocationFrom['CITY_NAME_ORIG'], GetMessage('SALE_DH_EMS_ERROR_NO_CITY_FROM'));

			return array(
				"RESULT" => "ERROR",
				"TEXT" => $text,
			);
		}


		$cache_id = "sale|8.0.3|ems|".$profile."|".$arConfig["category"]["VALUE"]."|".$arOrder["LOCATION_FROM"]."|".$arOrder["LOCATION_TO"];

		// 0-0.1,0.1-0.5,0.5-1,1-1.5,1.5-2,2-3....30-31,31-31.5

		if ($arOrder['WEIGHT'] < 100)
			$cache_id .= '|weight_0';
		elseif ($arOrder['WEIGHT'] < 2000)
			$cache_id .= '|weight_half_'.(ceil($arOrder['WEIGHT']/1000) * 2);
		elseif ($arOrder['WEIGHT'] < 31000)
			$cache_id .= '|weight_'.(ceil($arOrder['WEIGHT']/1000));
		else
			$cache_id .= '|weight_max';

		$obCache = new CPHPCache();
		/*if ($obCache->InitCache(DELIVERY_EMS_CACHE_LIFETIME, $cache_id, "/"))
		{
			$vars = $obCache->GetVars();
			$result = $vars["RESULT"];
			$transit = $vars["TRANSIT"];

			if ($arLocationTo['IS_RUSSIAN'] == 'Y')
				$result += $arOrder["PRICE"] * DELIVERY_EMS_PRICE_TARIFF;

			return array(
				"RESULT" => "OK",
				"VALUE" => $result,
				'TRANSIT' => $transit,
			);
		}*/

		$arParams = array();

		if ($arLocationTo['IS_RUSSIAN'] != 'Y')
		{
			$arParams['type'] = $arConfig["category"]["VALUE"];
		}
		else
		{
			$arParams['from'] = $arLocationFrom['EMS_ID'];
		}

		$arParams['to'] = $arLocationTo['EMS_ID'];
		$arParams['weight'] = $arOrder['WEIGHT'] / 1000;

		$arParams['plain'] = 'true';

		$data = CDeliveryEMS::__EMSQuery('ems.calculate', $arParams);

		//echo '<pre style="text-align: left;">answer: '; print_r($data); echo '</pre>';

		if (is_array($data) && $data['rsp']['stat'] == 'ok')
		{
			$obCache->StartDataCache();

			$result = doubleval($data['rsp']['price']);
			$transit = '';
			if ($data['rsp']['term'])
				$transit = $data['rsp']['term']['min'].'-'.$data['rsp']['term']['max'];

			$obCache->EndDataCache(
				array(
					"RESULT" => $result,
					"TRANSIT" => $transit,
				)
			);

			if ($arLocationTo['IS_RUSSIAN'] == 'Y')
				$result += $arOrder["PRICE"] * DELIVERY_EMS_PRICE_TARIFF;

			return array(
				"RESULT" => "OK",
				"VALUE" => $data['rsp']['price'],
				'TRANSIT' => $data['rsp']['term']['min'].'-'.$data['rsp']['term']['max']
			);
		}

		return array(
			"RESULT" => "ERROR",
			"TEXT" => GetMessage('SALE_DH_EMS_ERROR_RESPONSE').(is_array($data) ? ' ('.$data['rsp']['err']['msg'].')' : ''),
		);
	}

	public static function Compability($arOrder, $arConfig)
	{
		$arLocationFrom = CSaleLocation::GetByID($arOrder["LOCATION_FROM"]);

		if ($arConfig['category']['VALUE'] == 'doc' && $arOrder['WEIGHT'] > 2000)
			return array();
		elseif (CDeliveryEMS::__IsRussian($arLocationFrom) && $arOrder['WEIGHT'] <= 31500)
			return array('delivery');
		else
			return array();
	}

	function __IsRussian($arLocation)
	{
		return
			(ToUpper($arLocation["COUNTRY_NAME_ORIG"]) == "Р РћРЎРЎР?РЇ"
			|| ToUpper($arLocation["COUNTRY_SHORT_NAME"]) == "Р РћРЎРЎР?РЇ"
			|| ToUpper($arLocation["COUNTRY_NAME_LANG"]) == "Р РћРЎРЎР?РЇ"
			|| ToUpper($arLocation["COUNTRY_NAME_ORIG"]) == "RUSSIA"
			|| ToUpper($arLocation["COUNTRY_SHORT_NAME"]) == "RUSSIA"
			|| ToUpper($arLocation["COUNTRY_NAME_LANG"]) == "RUSSIA"
			|| ToUpper($arLocation["COUNTRY_NAME_ORIG"]) == "Р РћРЎРЎР?Р™РЎРљРђРЇ Р¤Р•Р”Р•Р РђР¦Р?РЇ"
			|| ToUpper($arLocation["COUNTRY_SHORT_NAME"]) == "Р РћРЎРЎР?Р™РЎРљРђРЇ Р¤Р•Р”Р•Р РђР¦Р?РЇ"
			|| ToUpper($arLocation["COUNTRY_NAME_LANG"]) == "Р РћРЎРЎР?Р™РЎРљРђРЇ Р¤Р•Р”Р•Р РђР¦Р?РЇ"
			|| ToUpper($arLocation["COUNTRY_NAME_ORIG"]) == "RUSSIAN FEDERATION"
			|| ToUpper($arLocation["COUNTRY_SHORT_NAME"]) == "RUSSIAN FEDERATION"
			|| ToUpper($arLocation["COUNTRY_NAME_LANG"]) == "RUSSIAN FEDERATION");
	}

	function __Write2Log($data)
	{
		if (defined('DELIVERY_EMS_WRITE_LOG') && DELIVERY_EMS_WRITE_LOG === 1)
		{
			$fp = fopen(dirname(__FILE__)."/ems.log", "a");
			fwrite($fp, "\r\n==========================================\r\n");
			fwrite($fp, $data);
			fclose($fp);
		}
	}
}

AddEventHandler("sale", "onSaleDeliveryHandlersBuildList", array('CDeliveryEMS', 'Init'));
?>