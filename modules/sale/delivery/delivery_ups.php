<?
/******************************************************************************/
/* UPS Delivery Handler. Tarifification files can be found at http://ups.com  */
/* Delete ups/*.php files if you change tarification csv files                */
/******************************************************************************/
CModule::IncludeModule("sale");

IncludeModuleLangFile('/bitrix/modules/sale/delivery/delivery_ups.php');

// define('DELIVERY_UPS_ZONES_PHP_FILE', 'ups/zones.php');
// define('DELIVERY_UPS_EXPORT_PHP_FILE', 'ups/export.php');

class CDeliveryUPS
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
			"SID" => "ups",
			"NAME" => GetMessage('SALE_DH_UPS_NAME'),
			"DESCRIPTION" => GetMessage('SALE_DH_UPS_DESCRIPTION'),
			"DESCRIPTION_INNER" => GetMessage('SALE_DH_UPS_DESCRIPTION_INNER'),
			"BASE_CURRENCY" => $base_currency,

			"HANDLER" => __FILE__,

			/* Handler methods */
			"DBGETSETTINGS" => array("CDeliveryUPS", "GetSettings"),
			"DBSETSETTINGS" => array("CDeliveryUPS", "SetSettings"),
			"GETCONFIG" => array("CDeliveryUPS", "GetConfig"),

			"COMPABILITY" => array("CDeliveryUPS", "Compability"),
			"CALCULATOR" => array("CDeliveryUPS", "Calculate"),

			/* List of delivery profiles */
			"PROFILES" => array(
				"express" => array(
					"TITLE" => GetMessage("SALE_DH_UPS_EXPRESS_TITLE"),
					"DESCRIPTION" => GetMessage("SALE_DH_UPS_EXPRESS_DESCRIPTION"),

					"RESTRICTIONS_WEIGHT" => array(0, CSaleMeasure::Convert(150, "LBS", "G")),
					"RESTRICTIONS_SUM" => array(0),
				),

				"express_saver" => array(
					"TITLE" => GetMessage("SALE_DH_UPS_EXPRESS_SAVER_TITLE"),
					"DESCRIPTION" => GetMessage("SALE_DH_UPS_EXPRESS_SAVER_DESCRIPTION"),

					"RESTRICTIONS_WEIGHT" => array(0, CSaleMeasure::Convert(150, "LBS", "G")),
					"RESTRICTIONS_SUM" => array(0),
				),
			)
		);
	}

	public static function GetConfig()
	{
		$dir = substr(dirname(__FILE__), strlen($_SERVER["DOCUMENT_ROOT"]));

		$arConfig = array(
			"CONFIG_GROUPS" => array(
				"tariff_tables" => GetMessage('SALE_DH_UPS_TARIFF_TITLE'),
			),

			"CONFIG" => array(
				"zones_csv" => array(
					"TYPE" => "TEXT",
					"TITLE" => GetMessage('SALE_DH_UPS_CONFIG_zones_csv'),
					"DEFAULT" => $dir."/ups/ru_csv_zones.csv",
					"GROUP" => "tariff_tables",
				),

				"export_csv" => array(
					"TYPE" => "TEXT",
					"TITLE" => GetMessage('SALE_DH_UPS_CONFIG_export_csv'),
					"DEFAULT" => $dir."/ups/ru_csv_export.csv",
					"GROUP" => "tariff_tables",
				),
			),
		);

		return $arConfig;
	}

	public static function GetSettings($strSettings)
	{
		list($zones_path, $export_path) = explode(";", $strSettings);

		return array(
			"zones_csv" => $zones_path,
			"export_csv" => $export_path
		);
	}

	public static function SetSettings($arSettings)
	{
		return $arSettings["zones_csv"].";".$arSettings["export_csv"];
	}

	function __parseZonesFile($file)
	{
		$arResult = array();

		$fp = fopen($_SERVER["DOCUMENT_ROOT"].$file, "r");
		while ($data = fgetcsv($fp, 1000, ","))
		{
			if (count($data >= 9) && strlen($data[1]) == 2)
			{
				if (substr($data[2], -3) == " EU") $data[2] = substr($data[2], 0, -3);

				$arResult[$data[1]] = array(
					$data[2],
					intval($data[6]),
					intval($data[8])
				);
			}
		}

		fclose($fp);

		$data_file = dirname(__FILE__)."/".DELIVERY_UPS_ZONES_PHP_FILE;

		if ($fp = fopen($data_file, "w"))
		{
			fwrite($fp, '<'.'?'."\r\n");
			fwrite($fp, '$arUPSZones = array('."\r\n");

			foreach ($arResult as $key => $arRow)
			{
				fwrite($fp, '"'.$key.'" => array("'.$arRow[0].'", '.intval($arRow[1]).', '.intval($arRow[2]).'),'."\r\n");
			}

			fwrite($fp, ');'."\r\n");
			fwrite($fp, '?'.'>');
			fclose($fp);
		}

		return $arResult;
	}

	function __parseExportFile($file)
	{
		$arResult = array();

		$fp = fopen($_SERVER["DOCUMENT_ROOT"].$file, "r");

		$check = 0;
		$bSkip = true;
		$arResult = array();
		while ($data = fgetcsv($fp, 1000, ","))
		{
			if (stristr($data[0], "service option"))
			{
				if (stristr($data[1], "express saver"))
					$current_profile = "express_saver";
				else
					$current_profile = "express";

				$arResult[$current_profile] = array();
			}
			elseif (stristr($data[1], 'weight'))
			{
				if ($check == 0)
				{
					$bSkip = true;
					$check++;
				}
				elseif ($check == 1)
				{
					$bSkip = false;
					$check = 0;
				}
			}
			elseif (count($data) == 10)
			{
				if ($bSkip) continue;
				else
				{
					foreach ($data as $key => $value)
					{
						$value = trim($value);
						$value = str_replace(".", '', $value);
						$value = str_replace(",", '.', $value);
						$data[$key] = $value;
					}

					if (doubleval($data[1]) <= 0)
					{
						$bSkip = true;
						continue;
					}

					$arResult[$current_profile][] = $data;
				}
			}
		}

		fclose($fp);

		$arFinalResult = array();

		foreach ($arResult as $profile_id => $arProfileResult)
		{
			$arFinalResult[$profile_id] = array();

			foreach ($arProfileResult as $key => $arWeightValues)
			{
				array_shift($arWeightValues);
				$weight_value = $arWeightValues[0];
				unset($arWeightValues[0]);

				$arFinalResult[$profile_id][$weight_value] = $arWeightValues;
			}
		}

		$data_file = dirname(__FILE__)."/".DELIVERY_UPS_EXPORT_PHP_FILE;

		if ($fp = fopen($data_file, "w"))
		{
			fwrite($fp, '<'.'?'."\r\n");
			fwrite($fp, '$arUPSExport = array('."\r\n");

			foreach ($arFinalResult as $profile => $arWeightValues)
			{
				fwrite($fp, '"'.$profile.'" => array('."\r\n");

				foreach ($arWeightValues as $weight => $arZoneValues)
				{
					fwrite($fp, '"'.$weight.'" => array(');

					foreach ($arZoneValues as $zone => $value)
					{
						fwrite($fp, $zone.' => '.$value.', ');
					}

					fwrite($fp, '),'."\r\n");
				}

				fwrite($fp, '),'."\r\n");
			}

			fwrite($fp, ');'."\r\n");
			fwrite($fp, '?'.'>');
			fclose($fp);
		}

		return $arFinalResult;
	}

	function __GetZones($file)
	{
		static $arUPSZones;

		if (is_array($arUPSZones)) return $arUPSZones;

		if (file_exists(dirname(__FILE__)."/".DELIVERY_UPS_ZONES_PHP_FILE))
			require(DELIVERY_UPS_ZONES_PHP_FILE);

		if (!is_array($arUPSZones) || count($arUPSZones) <= 0)
			$arUPSZones = CDeliveryUPS::__parseZonesFile($file);

		return $arUPSZones;
	}

	function __GetExport($file)
	{
		static $arUPSExport;

		if (is_array($arUPSExport)) return $arUPSExport;

		if (file_exists(dirname(__FILE__)."/".DELIVERY_UPS_EXPORT_PHP_FILE))
			require(DELIVERY_UPS_EXPORT_PHP_FILE);

		if (!is_array($arUPSExport) || count($arUPSExport) <= 0)
			$arUPSExport = CDeliveryUPS::__parseExportFile($file);

		return $arUPSExport;
	}


	function __GetLocation(&$arLocation, $arConfig)
	{
		$zones_file = $arConfig["zones_csv"]["VALUE"];
		$arZones = CDeliveryUPS::__GetZones($zones_file);

		foreach ($arZones as $country_id => $arZone)
		{
			if (
				($arLocation["COUNTRY_NAME_ORIG"] && stristr($arZone[0], $arLocation["COUNTRY_NAME_ORIG"]) !== false)
				|| ($arLocation["COUNTRY_SHORT_NAME"] && stristr($arZone[0], $arLocation["COUNTRY_SHORT_NAME"]) !== false)
				|| ($arLocation["COUNTRY_NAME_LANG"] && stristr($arZone[0], $arLocation["COUNTRY_NAME_LANG"]) !== false)
				|| ($arLocation["COUNTRY_NAME_ORIG"] && stristr($arLocation["COUNTRY_NAME_ORIG"], $arZone[0]) !== false)
				|| ($arLocation["COUNTRY_SHORT_NAME"] && stristr($arLocation["COUNTRY_SHORT_NAME"], $arZone[0]) !== false)
				|| ($arLocation["COUNTRY_NAME_LANG"] && stristr($arLocation["COUNTRY_NAME_LANG"], $arZone[0]) !== false)
			)
			{
				$arLocation["COUNTRY_SID"] = $country_id;
				break;
			}
		}
	}


	public static function Calculate($profile, $arConfig, $arOrder, $STEP, $TEMP = false)
	{
		$arOrder["WEIGHT"] = CSaleMeasure::Convert($arOrder["WEIGHT"], "G", "KG");

		$arLocationTo = CSaleLocation::GetByID($arOrder["LOCATION_TO"]);
		
		if (LANGUAGE_ID !== 'en')
		{
			$arCountry = CSaleLocation::GetCountryLangByID($arLocationTo['COUNTRY_ID'], 'en');
			if (false !== $arCountry)
				$arLocationTo['COUNTRY_NAME_LANG'] = $arCountry['NAME'];
		}
		
		CDeliveryUPS::__GetLocation($arLocationTo, $arConfig);

		$arPriceTable = CDeliveryUPS::__GetExport($arConfig["export_csv"]["VALUE"]);
		$arZones = CDeliveryUPS::__GetZones($zones_file);

		reset($arPriceTable);
		do
		{
			list($key, $arZoneTable) = each($arPriceTable[$profile]);
		}
		while ($key && (doubleval($arOrder["WEIGHT"]) > doubleval($key)));

		$zone = $arZones[$arLocationTo["COUNTRY_SID"]][$profile == "express_saver" ? 1 : 2];

		$sum = $arPriceTable[$profile][$key][$zone];

		return array(
			"RESULT" => "OK",
			"VALUE" => $sum
		);
	}


	public static function Compability($arOrder, $arConfig)
	{
		if (intval($arOrder["LOCATION_FROM"]) <= 0) 
			return array();

		$arLocationFrom = CSaleLocation::GetByID($arOrder["LOCATION_FROM"]);
		$arLocationTo = CSaleLocation::GetByID($arOrder["LOCATION_TO"]);

		if ($arLocationFrom["COUNTRY_ID"] == $arLocationTo["COUNTRY_ID"]) 
			return array();

		if (LANGUAGE_ID !== 'en')
		{
			$arCountry = CSaleLocation::GetCountryLangByID($arLocationTo['COUNTRY_ID'], 'en');
			if (false !== $arCountry)
				$arLocationTo['COUNTRY_NAME_LANG'] = $arCountry['NAME'];
		}
			
		CDeliveryUPS::__GetLocation($arLocationTo, $arConfig);

		if (strlen($arLocationTo["COUNTRY_SID"]) <= 0) 
			return array();

		$zones_file = $arConfig["zones_csv"]["VALUE"];
		$arZones = CDeliveryUPS::__GetZones($zones_file);

		$arZoneTo = $arZones[$arLocationTo["COUNTRY_SID"]];

		if (intval($arZoneTo[1]) > 0) 
			return array("express", "express_saver");
		else 
			return array("express");
	}
}

AddEventHandler("sale", "onSaleDeliveryHandlersBuildList", array('CDeliveryUPS', 'Init'));
?>