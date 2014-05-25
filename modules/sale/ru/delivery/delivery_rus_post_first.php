<?
/********************************************************************************
Delivery handler for Russian Post Service (http://www.russianpost.ru/)
"First class" service.
Calculations based on RP rates:
http://www.russianpost.ru/rp/servise/ru/home/postuslug/1class/1class_tariffs
********************************************************************************/
CModule::IncludeModule('sale');

IncludeModuleLangFile('/bitrix/modules/sale/delivery/delivery_rus_post_first.php');

// define('DELIVERY_RPF_CSV_PATH', $_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/sale/ru/delivery/rus_post_first'); //where we can found csv files

class CDeliveryRusPostFirst
{
	private static $MAX_WEIGHT = 2500; 	// (g)
	private static $MAX_SUMM = 20000; 	// RUB
	private static $MAX_SIZE = 360; //milimeters
	private static $MAX_DIMENSIONS_SUMM = 700; //milimeters
	private static $MAX_DIMENSIONS = array("165", "100", "190"); //milimeters

	private static $BASE_WEIGHT = 100;	// Base weight gramm

	private static $TARIFS = array();
	private static $SERVICES = array();

	private static $TARIF_IDX = 0;
	private static $TARIF_DESCR = 1;

	/* Standard mandatory delivery handler functions */
	public static function Init()
	{
		self::$TARIFS = array(
							'WEIGHT_LESS_100' => array(6, GetMessage('SALE_DH_RPF_WRP_LESS_100')),
							'WEIGHT_MORE_100' => array(7, GetMessage('SALE_DH_RPF_WRP_MORE_100')),
			);

		self::$SERVICES = array(
							'NOTIFICATION_SIMPLE' => array(8, GetMessage('SALE_DH_RPF_SMPL_NTF')),
							'NOTIFICATION_REG' => array(9, GetMessage('SALE_DH_RPF_RGST_NTF')),
							'DECLARED_VALUE' => array(10, GetMessage('SALE_DH_RPF_DCL_VAL'))
			);

		return array(
			/* Basic description */
			'SID' => 'rus_post_first',
			'NAME' => GetMessage('SALE_DH_RPF_NAME'),
			'DESCRIPTION' => GetMessage('SALE_DH_RPF_DESCR').' <a href="http://www.russianpost.ru/rp/servise/ru/home/postuslug/1class">http://www.russianpost.ru/rp/servise/ru/home/postuslug/1class</a>',
			'DESCRIPTION_INNER' => GetMessage('SALE_DH_RPF_DESCR').' <a href="http://www.russianpost.ru/rp/servise/ru/home/postuslug/1class">http://www.russianpost.ru/rp/servise/ru/home/postuslug/1class</a>',
			'BASE_CURRENCY' => 'RUB',
			'HANDLER' => __FILE__,
			/* Handler methods */
			'DBGETSETTINGS' => array('CDeliveryRusPostFirst', 'GetSettings'),
			'DBSETSETTINGS' => array('CDeliveryRusPostFirst', 'SetSettings'),
			'GETCONFIG' => array('CDeliveryRusPostFirst', 'GetConfig'),
			'GETFEATURES' => array('CDeliveryRusPostFirst', 'GetFeatures'),
			'COMPABILITY' => array('CDeliveryRusPostFirst', 'Compability'),
			'CALCULATOR' => array('CDeliveryRusPostFirst', 'Calculate'),

			/* List of delivery profiles */
			'PROFILES' => array(
				'wrapper' => array(
					'TITLE' => GetMessage('SALE_DH_RPF_WRP_TITLE'),
					'DESCRIPTION' => GetMessage('SALE_DH_RPF_WRP_DESCR'),
					'RESTRICTIONS_WEIGHT' => array(0, self::$MAX_WEIGHT),
					'RESTRICTIONS_SUM' => array(0, self::$MAX_SUMM),
					'TAX_RATE' => 0,
					'RESTRICTIONS_MAX_SIZE' => self::$MAX_SIZE,
					'RESTRICTIONS_DIMENSIONS_SUM' => self::$MAX_DIMENSIONS_SUMM,
					'RESTRICTIONS_DIMENSIONS' => self::$MAX_DIMENSIONS
					)
			)
		);
	}

	public static function GetConfig($siteId = false)
	{
		$shopLocationId = CSaleHelper::getShopLocationId($siteId);
		$arShopLocation = CSaleLocation::GetByID($shopLocationId);

		$shopPrevLocationId = COption::GetOptionString('sale', 'delivery_rus_post_first_prev_loc', 0);

		/* if shop's location was changed */
		if($shopPrevLocationId != $shopLocationId)
		{
			COption::SetOptionString('sale', 'delivery_rus_post_first_prev_loc', $shopLocationId);
			COption::RemoveOption('sale', 'delivery_rus_post_first_tarifs');
		}

		$arConfig = array(
			'CONFIG_GROUPS' => array(
				'wrapper' => GetMessage('SALE_DH_RPF_WRP_TITLE'),
			),
		);

		$aviableBoxes = self::getAviableBoxes();

		foreach ($aviableBoxes as $boxId => $arBox)
			CSaleDeliveryHelper::makeBoxConfig($boxId, $arBox, 'wrapper', $arConfig);

		$arConfig['CONFIG']['tarif_section_1'] = array(
					'TYPE' => 'SECTION',
					'TITLE' => GetMessage('SALE_DH_RPF_TARIFS'),
					'GROUP' => 'wrapper',
		);

		$arTarifs = CSaleHelper::getOptionOrImportValues(
									'delivery_rus_post_first_tarifs',
									array('CDeliveryRusPostFirst', 'getTarifsByRegionFromCsv'),
									array($arShopLocation['REGION_NAME_LANG'])
						);

		foreach (self::$TARIFS as $arTarif)
		{
			$tarifId = $arTarif[self::$TARIF_IDX];

			$arConfig['CONFIG']['TARIF_'.$tarifId] = array(
						'TYPE' => 'STRING',
						'DEFAULT' => isset($arTarifs[$tarifId]) ? $arTarifs[$tarifId] : '0',
						'TITLE' => $arTarif[self::$TARIF_DESCR],
						'GROUP' => 'wrapper',
			);
		}


		/* Additional services */
		foreach (self::$SERVICES as $serviceId => $arService)
		{
			$tarifId = $arService[self::$TARIF_IDX];

			$arConfig['CONFIG']['service_'.$tarifId.'_section'] = array(
						'TYPE' => 'SECTION',
						'TITLE' => $arService[self::$TARIF_DESCR],
						'GROUP' => 'wrapper',
			);

			$arConfig['CONFIG']['service_'.$tarifId.'_enabled'] = array(
						'TYPE' => 'CHECKBOX',
						'TITLE' => GetMessage('SALE_DH_RPF_SRV_ALLOW'),
						'GROUP' => 'wrapper',
						'DEFAULT' => $serviceId == 'NOTIFICATION_REG' ? 'N' : 'Y',
						'HIDE_BY_NAMES' => array('service_'.$tarifId.'_value')
			);

			$arConfig['CONFIG']['service_'.$tarifId.'_value'] = array(
						'TYPE' => 'STRING',
						'TITLE' => GetMessage('SALE_DH_RPF_SRV_PRICE'),
						'GROUP' => 'wrapper',
						'DEFAULT' => isset($arTarifs[$tarifId]) ? $arTarifs[$tarifId] : '0',
			);
		}

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
			if (strlen($value) > 0)
				$arSettings[$key] = $value;
			else
				unset($arSettings[$key]);
		}

		return serialize($arSettings);
	}

	public static function GetFeatures($arConfig)
	{
		$arResult = array();

		if ($arConfig["service_".array_shift(array_values(self::$SERVICES["NOTIFICATION_SIMPLE"]))."_enabled"]["VALUE"] == "Y")
			$arResult[GetMessage("SALE_DH_RPF_SMPL_NTF")] = GetMessage("SALE_DH_RPF_FEATURE_ENABLED");

		if ($arConfig["service_".array_shift(array_values(self::$SERVICES["NOTIFICATION_REG"]))."_enabled"]["VALUE"] == "Y")
			$arResult[GetMessage("SALE_DH_RPF_RGST_NTF")] = GetMessage("SALE_DH_RPF_FEATURE_ENABLED");

		if ($arConfig["service_".array_shift(array_values(self::$SERVICES["DECLARED_VALUE"]))."_enabled"]["VALUE"] == "Y")
			$arResult[GetMessage("SALE_DH_RPF_FEATURE_VALUE")] = GetMessage("SALE_DH_RPF_FEATURE_ENABLED");

		return $arResult;
	}

	public static function Calculate($profile, $arConfig, $arOrder, $STEP, $TEMP = false)
	{
		$arPacks = CSaleDeliveryHelper::getBoxesFromConfig($profile, $arConfig);

		$arPackagesParams = CSaleDeliveryHelper::getRequiredPacks(
													$arOrder["ITEMS"],
													$arPacks,
													self::$MAX_WEIGHT);

		$packageCount = count($arPackagesParams);

		if(intval($packageCount) <= 0)
		{
			return array(
						"RESULT" => "ERROR",
						"TEXT" => GetMessage("SALE_DH_RPF_OVERLOAD"),
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
		$result = array();

		$aviableBoxes = CSaleDeliveryHelper::getBoxesFromConfig('wrapper', $arConfig);

		foreach ($aviableBoxes as $arBox)
		{
			if (CSaleDeliveryHandler::checkDimensions($arOrder["MAX_DIMENSIONS"], $arBox["DIMENSIONS"]))
			{
				$result = array('wrapper');
				break;
			}
		}

		return $result;
	}

	/* Particular handler helper functions*/

	public static function getTarifNumFromCsv($regionNameLang)
	{
		$csvFile = CSaleHelper::getCsvObject(DELIVERY_RPF_CSV_PATH.'/tarif_regions.csv');
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

		$csvFile = CSaleHelper::getCsvObject(DELIVERY_RPF_CSV_PATH.'/tarif_data.csv');
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

	private static function getAviableBoxes()
	{
		return array(
					array(
						"NAME" => GetMessage("SALE_DH_RPF_STNRD_BOX"),
						"DIMENSIONS" => array("165", "100", "190")
						)
			);
	}

	private static function calculatePackPrice($arPackage, $profile, $arConfig, $arLocationTo)
	{
		$arDebug = array();
		$basePrice = $totalPrice = 0;

		//2. Wrapper
		//2.1, 2.2  declared value, weight less 100 gramm

		$basePrice = floatval(self::getConfValue($arConfig, 'TARIF_'.self::$TARIFS['WEIGHT_LESS_100'][self::$TARIF_IDX]));
		$arDebug[] = 'Base Price less 100 g: '.$basePrice;

		// 2.3 weight more tha 100 g
		if($arPackage['WEIGHT'] > self::$BASE_WEIGHT)
		{
			$addWeight = ceil($arPackage['WEIGHT'] / self::$BASE_WEIGHT - 1);
			$addPrice = floatval(self::getConfValue($arConfig, 'TARIF_'.self::$TARIFS['WEIGHT_MORE_100'][self::$TARIF_IDX]));
			$arDebug[] = 'Price for additional weight more than 100 g: '.$addPrice;
			$basePrice += $addWeight * $addPrice;
		}

		$totalPrice = $basePrice;

		// 3.1 simple notification
		$snPrice = 0;
		if(self::isConfCheckedVal($arConfig, 'service_'.self::$SERVICES['NOTIFICATION_SIMPLE'][self::$TARIF_IDX].'_enabled'))
		{
			$snPrice = floatval(self::getConfValue($arConfig, 'service_'.self::$SERVICES['NOTIFICATION_SIMPLE'][self::$TARIF_IDX].'_value'));
			$arDebug[] = 'Simple notification: '.$snPrice;
			$totalPrice += $snPrice;
		}

		// 3.2. registered notification
		$rnPrice = 0;
		if(self::isConfCheckedVal($arConfig, 'service_'.self::$SERVICES['NOTIFICATION_REG'][self::$TARIF_IDX].'_enabled'))
		{
			$rnPrice = floatval(self::getConfValue($arConfig, 'service_'.self::$SERVICES['NOTIFICATION_REG'][self::$TARIF_IDX].'_value'));
			$arDebug[] = 'Registered notification: '.$rnPrice;
			$totalPrice += $rnPrice;
		}

		// 4. Service "declared value"
		$dvPrice = 0;
		if(self::isConfCheckedVal($arConfig, 'service_'.self::$SERVICES['DECLARED_VALUE'][self::$TARIF_IDX].'_enabled'))
		{
			$dvTarif = floatval(self::getConfValue($arConfig, 'service_'.self::$SERVICES['DECLARED_VALUE'][self::$TARIF_IDX].'_value'));
			$dvPrice += ($arPackage['PRICE']+$totalPrice)*$dvTarif;
			$arDebug[] = 'Declared value: '.$dvPrice;
			$totalPrice += $dvPrice;
		}

		$arDebug[] = 'Total value: '.$totalPrice;
		return $totalPrice;
	}
}

AddEventHandler('sale', 'onSaleDeliveryHandlersBuildList', array('CDeliveryRusPostFirst', 'Init'));

?>