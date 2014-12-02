<?
/********************************************************************************
Delivery handler for Russian Post Service (http://www.russianpost.ru/)
Calculations based on RP rates:
http://www.russianpost.ru/rp/servise/ru/home/postuslug/bookpostandparcel/local#parcel
********************************************************************************/
CModule::IncludeModule('sale');

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/sale/delivery/delivery_rus_post.php');

// define('DELIVERY_RP_CSV_PATH', $_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/sale/ru/delivery/rus_post'); //where we can found csv files

class CDeliveryRusPost
{
	private static $MAX_WEIGHT_HEAVY = 20000; // (g)
	private static $MAX_WEIGHT = 10000; // (g)
	private static $ZONES_COUNT = 5;	// Tarif zones count
	private static $BASE_WEIGHT = 500;	// Base weight gram


	//1.1	zone_number => tarif_number
	private static $TARIF_LESS_500 = array(
								1 => 1,
								2 => 2,
								3 => 3,
								4 => 4,
								5 => 5
	);

	private static $TARIF_MORE_500 = array(
								1 => 6,
								2 => 7,
								3 => 8,
								4 => 9,
								5 => 10
	);

	private static $TARIF_HEAVY_WEIGHT = 11;	//1.2
	private static $TARIF_FRAGILE = 14; 		//1.5
	private static $TARIF_DECLARED_VAL = 20;	//4.
	private static $TARIF_AVIA_STANDART = 15;	//2.1
	private static $TARIF_AVIA_HEAVY = 16;		//2.2

	private static $MAX_DIMENSIONS = array("425", "265", "380");

	/* Standard mandatory delivery handler functions */
	public static function Init()
	{
		return array(
			/* Basic description */
			'SID' => 'rus_post',
			'MULTISITE_CONFIG' => "Y",
			'NAME' => GetMessage('SALE_DH_RP_NAME'),
			'DESCRIPTION' => GetMessage('SALE_DH_RP_DESCRIPTION').' <a href="http://www.russianpost.ru">http://www.russianpost.ru</a>',
			'DESCRIPTION_INNER' => GetMessage('SALE_DH_RP_DESCRIPTION_INNER'),
			'BASE_CURRENCY' => 'RUB',

			'HANDLER' => __FILE__,

			/* Handler methods */
			'DBGETSETTINGS' => array('CDeliveryRusPost', 'GetSettings'),
			'DBSETSETTINGS' => array('CDeliveryRusPost', 'SetSettings'),
			'GETCONFIG' => array('CDeliveryRusPost', 'GetConfig'),
			'GETFEATURES' => array('CDeliveryRusPost', 'GetFeatures'),

			'COMPABILITY' => array('CDeliveryRusPost', 'Compability'),
			'CALCULATOR' => array('CDeliveryRusPost', 'Calculate'),

			/* List of delivery profiles */
			'PROFILES' => array(
				'land' => array(
					'TITLE' => GetMessage('SALE_DH_RP_LAND_TITLE'),
					'DESCRIPTION' => GetMessage('SALE_H_RP_LAND_DESCRIPTION'),
					'RESTRICTIONS_WEIGHT' => array(0, self::$MAX_WEIGHT_HEAVY),
					'RESTRICTIONS_SUM' => array(0),
					'TAX_RATE' => 0,
					'RESTRICTIONS_DIMENSIONS' => self::$MAX_DIMENSIONS
					),

				'avia' => array(
					'TITLE' => GetMessage('SALE_DH_RP_AVIA_TITLE'),
					'DESCRIPTION' => GetMessage('SALE_DH_RP_AVIA_DESCRIPTION'),
					'RESTRICTIONS_WEIGHT' => array(0, self::$MAX_WEIGHT_HEAVY),
					'RESTRICTIONS_SUM' => array(0),
					'TAX_RATE' => 0,
					'RESTRICTIONS_DIMENSIONS' => self::$MAX_DIMENSIONS
					)
			)
		);
	}

	public static function GetConfig($siteId = false)
	{
		$shopLocationId = CSaleHelper::getShopLocationId($siteId);
		$arShopLocation = CSaleLocation::GetByID($shopLocationId);
		$shopPrevLocationId = COption::GetOptionString('sale', 'delivery_rus_post_prev_loc', 0);

		/* if shop's location was changed */
		if($shopPrevLocationId != $shopLocationId)
		{
			COption::SetOptionString('sale', 'delivery_rus_post_prev_loc', $shopLocationId);
			COption::RemoveOption('sale', 'delivery_regs_to_zones');
			COption::RemoveOption('sale', 'delivery_rus_post_tarifs');
		}

		$arConfig = array(
			'CONFIG_GROUPS' => array(
				'zones' => GetMessage('SALE_DH_RP_CONFIG_GROUP_ZONES'),
				'tarifs' => GetMessage('SALE_DH_RP_CONFIG_GROUP_TARIFS'),
				'land' => GetMessage('SALE_DH_RP_CONFIG_GROUP_LAND'),
				'avia' => GetMessage('SALE_DH_RP_CONFIG_GROUP_AVIA'),
			),
		);

		// Zones tab
		$arRegions = CSaleDeliveryHelper::getRegionsList();
		$arZones = array();
		$arZones[0] = GetMessage('SALE_DH_RP_CONFIG_ZONES_EMPTY');

		for ($i = 1; $i <= self::$ZONES_COUNT; $i++)
			$arZones[$i] = GetMessage('SALE_DH_RP_CONFIG_ZONE').' '.$i;

		$arRegsToZones = CSaleHelper::getOptionOrImportValues(
									'delivery_regs_to_zones',
									array('CDeliveryRusPost', 'importZonesFromCsv'),
									array($arShopLocation['REGION_NAME_LANG'])
						);

		foreach ($arRegions as $regId => $regName)
		{
			$arConfig['CONFIG']['REG_'.$regId] = array(
						'TYPE' => 'DROPDOWN',
						'DEFAULT' => isset($arRegsToZones[$regId]) ? $arRegsToZones[$regId] : '0',
						'TITLE' => $regName,
						'GROUP' => 'zones',
						'VALUES'=> $arZones
			);
		}

		/*
		tarifs tab
		1. land
		1.1. Base Price
		*/

		$arConfig['CONFIG']['tarif_section_1'] = array(
					'TYPE' => 'SECTION',
					'TITLE' => GetMessage('SALE_DH_RP_WEIGHT_LESS'),
					'GROUP' => 'tarifs',
		);

		$arTarifs = CSaleHelper::getOptionOrImportValues(
									'delivery_rus_post_tarifs',
									array('CDeliveryRusPost', 'getTarifsByRegionFromCsv'),
									array($arShopLocation['REGION_NAME_LANG'])
						);

		foreach ($arZones as $zoneId => $zoneName)
		{
			if($zoneId <= 0)
				continue;

			$tarifId = self::$TARIF_LESS_500[$zoneId];
			$arConfig['CONFIG']['ZONE_RATE_MAIN_'.$zoneId] = array(
						'TYPE' => 'STRING',
						'DEFAULT' => isset($arTarifs[$tarifId]) ? $arTarifs[$tarifId] : '0',
						'TITLE' => $zoneName,
						'GROUP' => 'tarifs',
			);
		}

		$arConfig['CONFIG']['tarif_section_2'] = array(
					'TYPE' => 'SECTION',
					'TITLE' => GetMessage('SALE_DH_RP_WEIGHT_MORE'),
					'GROUP' => 'tarifs',
		);

		foreach ($arZones as $zoneId => $zoneName)
		{
			if($zoneId <= 0)
				continue;

			$tarifId = self::$TARIF_MORE_500[$zoneId];

			$arConfig['CONFIG']['ZONE_RATE_ADD_'.$zoneId] = array(
						'TYPE' => 'STRING',
						'DEFAULT' => isset($arTarifs[$tarifId]) ? $arTarifs[$tarifId] : '0',
						'TITLE' => $zoneName,
						'GROUP' => 'tarifs',
			);
		}

		/* Additional services */
		$arConfig['CONFIG']['tarif_add_services'] = array(
					'TYPE' => 'SECTION',
					'TITLE' => GetMessage('SALE_DH_RP_ADD_SRV'),
					'GROUP' => 'tarifs',
		);

		/* 1.2 Service heavy weight 10 - 20 kg */
		$tarifId = self::$TARIF_HEAVY_WEIGHT;
		$arConfig['CONFIG']['service_'.$tarifId.'_enabled'] = array(
					'TYPE' => 'CHECKBOX',
					'TITLE' => GetMessage('SALE_DH_RP_SRV_HEAVY'),
					'GROUP' => 'tarifs',
					'DEFAULT' => 'Y',
					'HIDE_BY_NAMES' => array('service_'.$tarifId.'_value')
		);

		$arConfig['CONFIG']['service_'.$tarifId.'_value'] = array(
					'TYPE' => 'STRING',
					'TITLE' => GetMessage('SALE_DH_RP_SRV_HEAVY_VAL').' %',
					'GROUP' => 'tarifs',
					'DEFAULT' => isset($arTarifs[$tarifId]) ? $arTarifs[$tarifId] : '0',
		);

		/* 1.5 Service fragile */
		$tarifId = self::$TARIF_FRAGILE;
		$arConfig['CONFIG']['service_'.$tarifId.'_enabled'] = array(
					'TYPE' => 'CHECKBOX',
					'TITLE' => GetMessage('SALE_DH_RP_SRV_FRGL'),
					'GROUP' => 'tarifs',
					'DEFAULT' => 'Y',
					'HIDE_BY_NAMES' => array('service_'.$tarifId.'_value'),
					'TOP_LINE' => 'Y'
		);

		$arConfig['CONFIG']['service_'.$tarifId.'_value'] = array(
					'TYPE' => 'STRING',
					'TITLE' => GetMessage('SALE_DH_RP_SRV_FRGL_VAL').' %',
					'GROUP' => 'tarifs',
					'DEFAULT' => isset($arTarifs[$tarifId]) ? $arTarifs[$tarifId] : '0'
		);

		/* 4. Service declared value */
		$tarifId = self::$TARIF_DECLARED_VAL;
		$arConfig['CONFIG']['service_'.$tarifId.'_enabled'] = array(
					'TYPE' => 'CHECKBOX',
					'TITLE' => GetMessage('SALE_DH_RP_SRV_DECL'),
					'GROUP' => 'tarifs',
					'DEFAULT' => 'Y',
					'HIDE_BY_NAMES' => array('service_'.$tarifId.'_value'),
					'TOP_LINE' => 'Y'
		);

		$arConfig['CONFIG']['service_'.$tarifId.'_value'] = array(
					'TYPE' => 'STRING',
					'TITLE' => GetMessage('SALE_DH_RP_SRV_DECL_VAL'),
					'GROUP' => 'tarifs',
					'DEFAULT' => isset($arTarifs[$tarifId]) ? $arTarifs[$tarifId] : '0',
		);

		// land tab
		$aviableBoxes = self::getAviableBoxes();

		foreach ($aviableBoxes as $boxId => $arBox)
			CSaleDeliveryHelper::makeBoxConfig($boxId, $arBox, 'land', $arConfig);

		/* 2.1 avia tab*/

		foreach ($aviableBoxes as $boxId => $arBox)
			CSaleDeliveryHelper::makeBoxConfig($boxId, $arBox, 'avia', $arConfig);

		$tarifId = self::$TARIF_AVIA_STANDART;
		$arConfig['CONFIG']['tarif_avia_services'] = array(
					'TYPE' => 'SECTION',
					'TITLE' => GetMessage('SALE_DH_RP_TARIFS_AVIA'),
					'GROUP' => 'avia',		);

		$arConfig['CONFIG']['tarif_avia_'.$tarifId.'_value'] = array(
					'TYPE' => 'STRING',
					'TITLE' => GetMessage('SALE_DH_RP_TARIF_AVIA_STNDRT'),
					'GROUP' => 'avia',
					'DEFAULT' => isset($arTarifs[$tarifId]) ? $arTarifs[$tarifId] : '0',
		);

		$tarifId = self::$TARIF_AVIA_HEAVY;
		$arConfig['CONFIG']['tarif_avia_'.$tarifId.'_value'] = array(
					'TYPE' => 'STRING',
					'TITLE' => GetMessage('SALE_DH_RP_TARIF_AVIA_HEAVY'),
					'GROUP' => 'avia',
					'DEFAULT' => isset($arTarifs[$tarifId]) ? $arTarifs[$tarifId] : '0',
		);

		return $arConfig;
	}

	public static function GetSettings($strSettings)
	{
		return unserialize($strSettings);
	}

	public static function SetSettings($arSettings)
	{
		foreach ($arSettings as $key => $value)
		{
			if (strlen($value) > 0 && (substr($key, 0, 4) != 'REG_' || $value != '0'))
			{
				$arSettings[$key] = $value;
			}
			else
				unset($arSettings[$key]);
		}

		return serialize($arSettings);
	}

	public static function GetFeatures($arConfig)
	{
		$arResult = array();

		if ($arConfig["service_".self::$TARIF_FRAGILE."_enabled"]["VALUE"] == "Y")
			$arResult[GetMessage("SALE_DH_RP_FEATURE_MARK")] = GetMessage("SALE_DH_RP_FEATURE_MARKED");

		if ($arConfig["service_".self::$TARIF_DECLARED_VAL."_enabled"]["VALUE"] == "Y")
			$arResult[GetMessage("SALE_DH_RP_FEATURE_VALUE")] = GetMessage("SALE_DH_RP_FEATURE_ENABLED");

		return $arResult;
	}

	public static function Calculate($profile, $arConfig, $arOrder, $STEP, $TEMP = false)
	{
		$maxWeight = self::isHeavyEnabled($arConfig) ? self::$MAX_WEIGHT_HEAVY : self::$MAX_WEIGHT;

		$arPacks = CSaleDeliveryHelper::getBoxesFromConfig($profile, $arConfig);

		$arPackagesParams = CSaleDeliveryHelper::getRequiredPacks(
													$arOrder["ITEMS"],
													$arPacks,
													$maxWeight);

		$packageCount = count($arPackagesParams);

		if(intval($packageCount) <= 0)
		{
			return array(
						"RESULT" => "ERROR",
						"TEXT" => GetMessage("SALE_DH_RP_OVERLOAD"),
					);
		}

		$totalPrice = 0;
		$arLocationTo = CSaleLocation::GetByID($arOrder['LOCATION_TO']);

		foreach ($arPackagesParams as $arPackage)
			$totalPrice += self::calculatePackPrice($arPackage, $profile, $arConfig, $arLocationTo);

		$arResult = array(
			'RESULT' => 'OK',
			'VALUE' => $totalPrice,
			'PACKS_COUNT' => $packageCount
		);
		return $arResult;
	}

	public static function Compability($arOrder, $arConfig)
	{
		$profiles = array('land', 'avia');

		$bHevyWeightEnabled = self::isConfCheckedVal($arConfig, 'service_'.self::$TARIF_HEAVY_WEIGHT.'_enabled');

		$maxWeight = $bHevyWeightEnabled ? self::$MAX_WEIGHT_HEAVY : self::$MAX_WEIGHT;

		foreach ($arOrder["ITEMS"] as $arItem)
		{
			if(floatval($arItem["WEIGHT"]) > $maxWeight)
			{
				$profiles = array();
				break;
			}
		}

		$arRes = array();

		foreach ($profiles as $profile)
		{
			$aviableBoxes = CSaleDeliveryHelper::getBoxesFromConfig($profile, $arConfig);

			foreach ($aviableBoxes as $arBox)
			{
				if (CSaleDeliveryHandler::checkDimensions($arOrder["MAX_DIMENSIONS"], $arBox["DIMENSIONS"]))
				{
					$arRes[] = $profile;
					break;
				}
			}
		}
		return $arRes;
	}

	/* Particular handler helper functions*/

	static public function importZonesFromCsv($regionNameLang)
	{
		if(strlen($regionNameLang) <= 0)
			return array();

		$COL_REG_NAME = 0;

		$csvFile = CSaleHelper::getCsvObject(DELIVERY_RP_CSV_PATH.'/zones.csv');
		$arRegsTo = $csvFile->Fetch();

		$arRegions = CSaleDeliveryHelper::getRegionsList(0, true); // RegName => RegId

		$arRegionsZones = array();

		while ($arRes = $csvFile->Fetch())
		{
			if(isset($arRes[$COL_REG_NAME]) && $regionNameLang == $arRes[$COL_REG_NAME])
				for ($i = 1, $l = count($arRes) - 1; $i <= $l; $i++)
				{
					if(isset($arRegsTo[$i])
						&&
						isset($arRegions[$arRegsTo[$i]])
						&&
						isset($arRes[$i])
					)
					{
						$arRegionsZones[$arRegions[$arRegsTo[$i]]] = $arRes[$i];
					}
				}
		}

		return $arRegionsZones;
	}

	/**
	 * If zip codes imported to locations, we try to link regions to zones
	 * using file /bitrix/modules/sale/delivery/rus_post/zip_zones.csv created
	 * from http://info.russianpost.ru/database/tzones.html
	 */
	static public function importZonesFromZipCsv()
	{
		$COL_ZIP = 0;
		$COL_ZONE = 1;
		$csvFile = CSaleHelper::getCsvObject(DELIVERY_RP_CSV_PATH.'/zip_zones.csv');
		$arRes = $csvFile->Fetch();

		$arRegions = CSaleDeliveryHelper::getRegionsList();
		$arRegionsZones = array();

		while ($arRes = $csvFile->Fetch())
		{
			$location = CSaleLocation::GetByZIP($arRes[$COL_ZIP]);

			if($location === false)
				continue;

			if(isset($arRegions[$location['REGION_ID']]))
				$arRegionsZones[$location['REGION_ID']] = $arRes[$COL_ZONE];

			unset($arRegions[$location['REGION_ID']]);

			if(empty($arRegions))
				break;
		}

		return $arRegionsZones;
	}

	public static function getTarifNumFromCsv($regionNameLang)
	{
		$csvFile = CSaleHelper::getCsvObject(DELIVERY_RP_CSV_PATH.'/tarif_regions.csv');
		$tarifNumber = false;
		$COL_TARIF_NUM = 0;

		while ($arRes = $csvFile->Fetch())
		{
			if(in_array($regionNameLang, $arRes))
			{
				$tarifNumber = $arRes[$COL_TARIF_NUM];
				break;
			}
		}
		return $tarifNumber;
	}

	public static function getTarifsByRegionFromCsv($regionNameLang)
	{
		if(strlen(trim($regionNameLang)) <= 0)
			return false;

		$tarifNumber = self::getTarifNumFromCsv($regionNameLang);

		if($tarifNumber === false)
			return false;

		$csvFile = CSaleHelper::getCsvObject(DELIVERY_RP_CSV_PATH.'/tarif_data.csv');
		$COL_TARIF_ITEMS = 0;
		$arTarifs = array();
		$arRes = $csvFile->Fetch();

		while ($arRes = $csvFile->Fetch())
		{
			if(!isset($arRes[$tarifNumber]))
				break;

			$arTarifs[$arRes[$COL_TARIF_ITEMS]] = $arRes[$tarifNumber];
		}

		return $arTarifs;
	}

	private static function getConfValue(&$arConfig, $key)
	{
		return CSaleDeliveryHelper::getConfValue($arConfig[$key]);
	}

	private static function isConfCheckedVal(&$arConfig, $key)
	{
		return 	$arConfig[$key]['VALUE'] == 'Y'
				||(
					!isset($arConfig[$key]['VALUE'])
					&& $arConfig[$key]['DEFAULT'] == 'Y'
				);
	}

	private static function isHeavyEnabled(&$arConfig)
	{
		return self::isConfCheckedVal($arConfig, 'service_'.self::$TARIF_HEAVY_WEIGHT.'_enabled');
	}

	private static function getAviableBoxes()
	{
		return array(
					array(
						"NAME" => GetMessage("SALE_DH_RP_STNDRD_BOX"),
						"DIMENSIONS" => array("425", "265", "380")
						)
			);
	}


	private static function calculatePackPrice($arPackage, $profile, $arConfig, $arLocationTo)
	{
		$arDebug = array();

		/*1 Land price
		1.1 Base Price less 10 kg*/

		$zoneTo = $arConfig['REG_'.$arLocationTo['REGION_ID']]['VALUE'];
		$basePrice = $totalPrice = 0;
		$basePrice = floatval(self::getConfValue($arConfig, 'ZONE_RATE_MAIN_'.$zoneTo));
		$arDebug[] = 'Base Price less 500 g: '.$basePrice;

		if($arPackage['WEIGHT'] > self::$BASE_WEIGHT)
		{
			$addWeight = ceil($arPackage['WEIGHT'] / self::$BASE_WEIGHT - 1);
			$addPrice = floatval(self::getConfValue($arConfig, 'ZONE_RATE_ADD_'.$zoneTo));
			$arDebug[] = 'Price for additional weight more than 500 g: '.$addWeight * $addPrice;
			$basePrice += $addWeight * $addPrice;
		}

		$totalPrice = $basePrice;

		/* 1.2 Service "heavy weight" 10 - 20 kg*/
		$hwPrice = 0;
		if($arPackage['WEIGHT'] >= self::$MAX_WEIGHT)
		{
			$hwTarif = floatval(self::getConfValue($arConfig, 'service_'.self::$TARIF_HEAVY_WEIGHT.'_value'));
			$hwPrice += $totalPrice*$hwTarif/100;
			$arDebug[] = 'Heavy weight: '.$hwPrice;
			$totalPrice += $hwPrice;
		}

		/* 1.5 Service "fragile" */
		$fPrice = 0;
		if(self::isConfCheckedVal($arConfig, 'service_'.self::$TARIF_FRAGILE.'_enabled'))
		{
			$fTarif = floatval(self::getConfValue($arConfig, 'service_'.self::$TARIF_FRAGILE.'_value'));
			$fPrice += $totalPrice*$fTarif/100;
			$arDebug[] = 'Fragile: '.$fPrice;
			$totalPrice += $fPrice;
		}

		/* 4. Service "declared value" */
		$dvPrice = 0;
		if(self::isConfCheckedVal($arConfig, 'service_'.self::$TARIF_DECLARED_VAL.'_enabled'))
		{
			$dvTarif = floatval(self::getConfValue($arConfig, 'service_'.self::$TARIF_DECLARED_VAL.'_value'));
			$dvPrice += ($arPackage['PRICE']+$totalPrice)*$dvTarif;
			$arDebug[] = 'Declared value: '.$dvPrice;
			$totalPrice += $dvPrice;
		}

		if($profile == 'avia')
		{
			$aviaPrice = 0;
			$aviaPrice = floatval(self::getConfValue($arConfig, 'tarif_avia_'.self::$TARIF_AVIA_STANDART.'_value'));
			$arDebug[] = 'avia price: '.$aviaPrice;
			$totalPrice += $aviaPrice;

			$aviaHeavyPrice = 0;
			if($arPackage['WEIGHT'] > self::$MAX_WEIGHT)
			{
				$aviaHeavyPrice = floatval(self::getConfValue($arConfig, 'tarif_avia_'.self::$TARIF_AVIA_HEAVY.'_value'));
				$arDebug[] = 'avia heavy price: '.$aviaHeavyPrice;
				$totalPrice += $aviaHeavyPrice;
			}
		}

		return $totalPrice;
	}

}

AddEventHandler('sale', 'onSaleDeliveryHandlersBuildList', array('CDeliveryRusPost', 'Init'));

?>