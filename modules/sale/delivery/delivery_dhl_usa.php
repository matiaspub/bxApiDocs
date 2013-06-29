<?
/**********************************************************************
Delivery handler for DHL USA delivery service (http://www.dhl-usa.com/)
It uses on-line calculator. Calculation only from USA.
**********************************************************************/

CModule::IncludeModule("sale");

IncludeModuleLangFile('/bitrix/modules/sale/delivery/delivery_dhl_usa.php');

// define('DELIVERY_DHL_USA_WRITE_LOG', 0); // flag 'write to log'. use CDeliveryDHLUSA::__WriteToLog() for logging.
// define('DELIVERY_DHL_USA_CACHE_LIFETIME', 2592000); // cache lifetime - 30 days (60*60*24*30)

// define('DELIVERY_DHL_USA_PACKAGE_TYPE_DEFAULT', 'CP'); // default category for delivered goods

// define('DELIVERY_DHL_USA_SERVER', 'www.dhl-usa.com'); // server name to send data
// define('DELIVERY_DHL_USA_SERVER_PORT', 80); // server port
// define('DELIVERY_DHL_USA_SERVER_PAGE', '/ratecalculator/HandlerServlet'); // server page url
// define('DELIVERY_DHL_USA_SERVER_METHOD', 'POST'); // data send method

// define('DELIVERY_DHL_USA_VALUE_CHECK_STRING', 'Quote results'); // first check string - to determine whether delivery price is in response
define(
	'DELIVERY_DHL_USA_VALUE_CHECK_REGEXP', 
	'/\$([0-9\.\s]+)<\/a>/i'
); // second check string - regexp to parse final price from response

define (
	'DELIVERY_DHL_USA_TIME_CHECK_REGEXP',
	'/<td><div class="pL5">([0-9]+)\sdays<\/div><\/td>/i'
);

class CDeliveryDHLUSA
{
	public static function Init()
	{
		$arReturn = array(
			/* Basic description */
			"SID" => "dhlusa",
			"NAME" => GetMessage('SALE_DH_DHL_USA_NAME'),
			"DESCRIPTION" => GetMessage('SALE_DH_DHL_USA_DESCRIPTION'),
			"DESCRIPTION_INNER" => GetMessage('SALE_DH_DHL_USA_DESCRIPTION_INNER'),
			"BASE_CURRENCY" => 'USD',
			
			"HANDLER" => __FILE__,
			
			/* Handler methods */
			"DBGETSETTINGS" => array("CDeliveryDHLUSA", "GetSettings"),
			"DBSETSETTINGS" => array("CDeliveryDHLUSA", "SetSettings"),
			"GETCONFIG" => array("CDeliveryDHLUSA", "GetConfig"),
			
			"COMPABILITY" => array("CDeliveryDHLUSA", "Compability"),			
			"CALCULATOR" => array("CDeliveryDHLUSA", "Calculate"),			
			
			/* List of delivery profiles */	
			"PROFILES" => array(
				"simple" => array(
					"TITLE" => GetMessage("SALE_DH_DHL_USA_PROFILE_TITLE"),
					"DESCRIPTION" => GetMessage("SALE_DH_DHL_USA_PROFILE_DESCRIPTION"),
					
					"RESTRICTIONS_WEIGHT" => array(0, CSaleMeasure::Convert(150, "LBS", "G")),
					"RESTRICTIONS_SUM" => array(0),
				),
			),
		);

		
		return $arReturn;
	}
	
	public static function GetConfig()
	{
		$arConfig = array(
			"CONFIG_GROUPS" => array(
				"delivery" => GetMessage('SALE_DH_DHL_USA_CONFIG_DELIVERY_TITLE'),
			),
			
			"CONFIG" => array(
				"package_type" => array(
					"TYPE" => "DROPDOWN",
					"DEFAULT" => DELIVERY_DHL_USA_PACKAGE_TYPE_DEFAULT,
					"TITLE" => GetMessage('SALE_DH_DHL_USA_CONFIG_PACKAGE_TYPE'),
					"GROUP" => "delivery",
					"VALUES" => array(
						'EE' => GetMessage('SALE_DH_DHL_USA_CONFIG_PACKAGE_TYPE_EE'),
						'OD' => GetMessage('SALE_DH_DHL_USA_CONFIG_PACKAGE_TYPE_OD'),
						'CP' => GetMessage('SALE_DH_DHL_USA_CONFIG_PACKAGE_TYPE_CP'),
					),
				),
			),
		);
		
		return $arConfig; 
	}
	
	public static function GetSettings($strSettings)
	{
		return unserialize($strSettings);
	}
	
	public static function SetSettings($arSettings)
	{
		return serialize($arSettings);
	}
	
	function __GetLocation($location_id)
	{
		static $arDHLUSACountryList;
	
		$arLocation = CSaleLocation::GetByID($location_id, 'en');
		
		$dbZipList = CSaleLocation::GetLocationZIP($location_id);
		
		while ($arZip = $dbZipList->Fetch())
			$arLocation['ZIP_LIST'][] = $arZip['ZIP'];
		
		if (!is_array($arDHLUSACountryList))
		{
			require('dhl_usa/country.php');
		}
	
		$arLocation['COUNTRY_DHLUSA'] = $arDHLUSACountryList[ToUpper($arLocation['COUNTRY_NAME'])];
		
		return $arLocation;
	}
	
	public static function Calculate($profile, $arConfig, $arOrder, $STEP, $TEMP = false)
	{
		$arLocationFrom = CDeliveryDHLUSA::__GetLocation($arOrder['LOCATION_FROM']);
		$arLocationTo = CDeliveryDHLUSA::__GetLocation($arOrder['LOCATION_TO']);
	
		$location_from_zip = COption::GetOptionString('sale', 'location_zip');
		if ($location_from_zip) $arLocationFrom['ZIP_LIST'] = array($location_from_zip);
	
		$arOrder["WEIGHT"] = CSaleMeasure::Convert($arOrder["WEIGHT"], "G", "LBS");
		if ($arOrder["WEIGHT"] <= 0) $arOrder["WEIGHT"] = 0.1; // weight must not be null - let it be 1 gramm

		$cache_id = "dhl_usa"."|".$arConfig["category"]['VALUE']."|".$arOrder["LOCATION_FROM"]."|".$location_from_zip."|".$arOrder["LOCATION_TO"]."|".intval($arOrder['WEIGHT']);
		
		$obCache = new CPHPCache();
		
		if ($obCache->InitCache(DELIVERY_DHL_USA_CACHE_LIFETIME, $cache_id, "/"))
		{
			// cache found
			$vars = $obCache->GetVars();
			$result = $vars["RESULT"];
			$transit_time = $vars["TRANSIT"];
			
			return array(
				"RESULT" => "OK",
				"VALUE" => $result,
				"TRANSIT" => $transit_time,
			);
		}
		
		// format HTTP query request data
		$arQuery = array(
			'userStatus' => 'NON_AUTHENTICATED_USER',
			'customerType' => 'P',
			'ratesType' => 'book',
			'rateSuppressed' => 'N',
			'CLIENT' => 'CLASS_NAME_P',
			'CALLING_JSP' => '/jsp/ratesQuery.jsp',
			'INTGRTDSURVEY' => 'false',
			'totalPieces' => '1',
			'packagesPerMonth' => '',
		);
		
		$arQuery['originCountryCode'] = 'US';
		$arQuery['originZip'] = $arLocationFrom['ZIP_LIST'][0];
		
		$arQuery['destinationCountry'] = $arLocationTo['COUNTRY_DHLUSA'];
		$arQuery['destinationCountryName'] = $arLocationTo['COUNTRY_NAME'];
		$arQuery['destinationCity'] = $arLocationTo['CITY_NAME'];
		$arQuery['destinationZip'] = $arLocationTo['ZIP_LIST'][0];
		
		$timestamp = strtotime(date('Y-m-d'));
		$timestamp = strtotime('+1 day', $timestamp);
		
		// holidays list - http://www.dhl-usa.com/USSvcs/USSvcsHDay.asp?nav=FindServInfo/USHol
		$y = date('Y');
		$arHolidaysList = array(
			strtotime($y.'-01-01'), 
			strtotime($y.'-05-28'), 
			strtotime($y.'-06-04'), 
			strtotime($y.'-09-03'), 
			strtotime($y.'-11-22'), 
			strtotime($y.'-12-25')
		);
		
		while (date('N', $timestamp) > 5 || in_array($timestamp, $arHolidaysList))
			$timestamp += 86400;
		
		$arQuery['shipDate'] = date('d F, Y', $timestamp);
		
		$arQuery['pkgType'] = $arConfig['package_type']['VALUE'];
		$arQuery['packagingType'] = $arConfig['package_type']['VALUE'];
		
		$arQuery['packageWeight'] = $arOrder['WEIGHT'];
		
		$arQuery['dmnLength'] = '';
		$arQuery['dmnWidth'] = '';
		$arQuery['dmnHeight'] = '';
		
		$arQuery['dutiableFlag'] = 'N'; // !!!!!!
		
		foreach ($arQuery as $key => $value) $arQuery[$key] = urlencode($key).'='.urlencode($value);
		
		CDeliveryDHLUSA::__Write2Log(print_r($arQuery, true));
		CDeliveryDHLUSA::__Write2Log(implode('&', $arQuery));
		// get data from server
		$data = QueryGetData(
			DELIVERY_DHL_USA_SERVER, 
			DELIVERY_DHL_USA_SERVER_PORT,
			DELIVERY_DHL_USA_SERVER_PAGE,
			implode("&", $arQuery),
			$error_number = 0,
			$error_text = "",
			DELIVERY_DHL_USA_SERVER_METHOD
		);
		
		CDeliveryDHLUSA::__Write2Log($data);		
		
		if (strlen($data) <= 0)
		{
			return array(
				"RESULT" => "ERROR",
				"TEXT" => GetMessage('SALE_DH_DHL_USA_ERROR_CONNECT'),
			);
		}

		if (strstr($data, DELIVERY_DHL_USA_VALUE_CHECK_STRING))
		{
			// first check string found
			
			if (preg_match(
				DELIVERY_DHL_USA_VALUE_CHECK_REGEXP, 
				$data, 
				$matches
			))
			{
				$obCache->StartDataCache();
				// final price found
				$result = $matches[1];
				$result = preg_replace('/\s/', '', $result);
				$result = str_replace(',', '.', $result);
				$result = doubleval($result);

				$matches = array();
				$transit_time = 0;
				if (preg_match(
					DELIVERY_DHL_USA_TIME_CHECK_REGEXP,
					$data,
					$matches
				))
				{
					$transit_time = intval($matches[1]);
				}
				
				$obCache->EndDataCache(
					array(
						"RESULT" => $result,
						"TRANSIT" => $transit_time,
					)
				);
				
				return array(
					"RESULT" => "OK",
					"VALUE" => $result,
					"TRANSIT" => $transit_time,
				);
			}
			else
			{
				return array(
					"RESULT" => "ERROR",
					"TEXT" => GetMessage('SALE_DH_DHL_USA_ERROR_RESPONSE'),
				);
			}
		}

		return array(
			"RESULT" => "ERROR",
			"TEXT" => GetMessage('SALE_DH_DHL_USA_ERROR_RESPONSE'),
		);
	}
	
	public static function Compability($arOrder)
	{
		$arLocationFrom = CDeliveryDHLUSA::__GetLocation($arOrder['LOCATION_FROM']);
		
		if ($arLocationFrom['COUNTRY_DHLUSA'] != 'US') return array();
	
		return array('simple');
	} 
	
	public static function __Write2Log($data)
	{
		if (defined('DELIVERY_DHL_USA_WRITE_LOG') && DELIVERY_DHL_USA_WRITE_LOG === 1)
		{
			$fp = fopen(dirname(__FILE__)."/dhl_usa.log", "a");
			fwrite($fp, "\r\n==========================================\r\n");
			fwrite($fp, $data);
			fclose($fp);
		}
	}
}

AddEventHandler("sale", "onSaleDeliveryHandlersBuildList", array('CDeliveryDHLUSA', 'Init')); 
?>