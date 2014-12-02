<?
/********************************************************************************
Delivery handler for Ukrainian В«Nova poshtaВ»
http://novaposhta.ua
Tarif: http://novaposhta.ua/docs/internet_magaziny.pdf
Order's weight must be less or equal 100 Kg.
********************************************************************************/
CModule::IncludeModule('sale');

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/sale/delivery/delivery_ua_post.php');

class CDeliveryUaPost
{
	private static $MAX_WEIGHT = 100000;	// (g)

	private static $defaultTarifs = array(
			"BO" => 13, 			// Price for ordering UAH
			"T1" => 1.65, 			// WARE-WARE Price for 1 kg
			"WARE_DOOR" => array( 	// upper bound of weight gramm => price)
							2000 => 20,
							10000 => 25,
							100000 => 40
							),

			"DOOR_DOOR" => array(	//upper bound of weight gramm => price
							2000 => 55,
							5000 => 65,
							10000 => 75,
							20000 => 95,
							30000 => 115,
							50000 => 140,
							75000 => 170,
							100000 => 205
							),
			"OB_COMISS" => 0.5, // declared-value comission %
			"OB_COMISS_MIN" => 3 // min declared value comission UAH
				);

public static 	function Init()
	{
		return array(
			/* Basic description */
			'SID' => 'ua_post',
			'NAME' => GetMessage('SALE_DH_UP_NAME'),
			'DESCRIPTION' => GetMessage('SALE_DH_UP_DESCR1').' <a href="http://novaposhta.ua">http://novaposhta.ua</a>. '.GetMessage('SALE_DH_UP_DESCR2'),
			'DESCRIPTION_INNER' => GetMessage('SALE_DH_UP_DESCR1').' <a href="http://novaposhta.ua">http://novaposhta.ua</a>. '.GetMessage('SALE_DH_UP_DESCR2'),
			'BASE_CURRENCY' => 'UAH',
			'HANDLER' => __FILE__,
			/* Handler methods */
			'DBGETSETTINGS' => array('CDeliveryUaPost', 'GetSettings'),
			'DBSETSETTINGS' => array('CDeliveryUaPost', 'SetSettings'),
			'GETCONFIG' => array('CDeliveryUaPost', 'GetConfig'),
			'GETFEATURES' => array('CDeliveryUaPost', 'GetFeatures'),
			'COMPABILITY' => array('CDeliveryUaPost', 'Compability'),
			'CALCULATOR' => array('CDeliveryUaPost', 'Calculate'),

			/* List of delivery profiles */
			'PROFILES' => array(
				'ware' => array(
					'TITLE' => GetMessage('SALE_DH_UP_WARE_TITLE'),
					'DESCRIPTION' => GetMessage('SALE_DH_UP_WARE_DESCR'),
					'RESTRICTIONS_WEIGHT' => array(0, self::$MAX_WEIGHT),
					'RESTRICTIONS_SUM' => array(0),
					'TAX_RATE' => 0,
					'RESTRICTIONS_MAX_SIZE' => 0,
					'RESTRICTIONS_DIMENSIONS_SUM' => 0,
					'RESTRICTIONS_DIMENSIONS' => 0
					),
				'door' => array(
					'TITLE' => GetMessage('SALE_DH_UP_DOOR_TITLE'),
					'DESCRIPTION' => GetMessage('SALE_DH_UP_DOOR_DESCR'),
					'RESTRICTIONS_WEIGHT' => array(0, self::$MAX_WEIGHT),
					'RESTRICTIONS_SUM' => array(0),
					'TAX_RATE' => 0,
					'RESTRICTIONS_MAX_SIZE' => 0,
					'RESTRICTIONS_DIMENSIONS_SUM' => 0,
					'RESTRICTIONS_DIMENSIONS' => 0
					)
			)
		);
	}

public static 	function GetConfig()
	{
		$arConfig = array(
			'CONFIG_GROUPS' => array(
				'common' => GetMessage('SALE_DH_UP_GROUPS_COMMON'),
				'ware' => GetMessage('SALE_DH_UP_GROUPS_WARE'),
				'door' => GetMessage('SALE_DH_UP_GROUPS_DOOR')
			),
		);

		//common
		$arConfig['CONFIG']['DELIVERY_TO_POST'] = array(
					'TYPE' => 'RADIO',
					'DEFAULT' => 'ware',
					'TITLE' => GetMessage('SALE_DH_UP_DTP'),
					'VALUES' => array(
						'ware' => GetMessage('SALE_DH_UP_DTP_WARE'),
						'door' => GetMessage('SALE_DH_UP_DTP_DOOR')
						),
					'GROUP' => 'common',
		);

		$arConfig['CONFIG']['tarif_section_1'] = array(
					'TYPE' => 'SECTION',
					'TITLE' => GetMessage('SALE_DH_UP_TARIF_WW'),
					'GROUP' => 'common',
		);


		$arConfig['CONFIG']['TARIF_BO'] = array(
					'TYPE' => 'STRING',
					'DEFAULT' => self::$defaultTarifs['BO'],
					'TITLE' => GetMessage('SALE_DH_UP_TARIF_REG'),
					'GROUP' => 'common',
					'CHECK_FORMAT' => 'NUMBER'
		);

		$arConfig['CONFIG']['TARIF_T1'] = array(
					'TYPE' => 'STRING',
					'DEFAULT' => self::$defaultTarifs['T1'],
					'TITLE' => GetMessage('SALE_DH_UP_TARIF_T1'),
					'GROUP' => 'common',
					'CHECK_FORMAT' => 'NUMBER'
		);

		$arConfig['CONFIG']['tarif_section_2'] = array(
					'TYPE' => 'SECTION',
					'TITLE' => GetMessage('SALE_DH_UP_TARIF_WD'),
					'GROUP' => 'common',
		);

		$prevWeight = 0;
		foreach (self::$defaultTarifs["WARE_DOOR"] as $uperWeight => $price)
		{
			$arConfig['CONFIG']['TARIF_WARE_DOOR_'.$uperWeight] = array(
						'TYPE' => 'STRING',
						'DEFAULT' => $price,
						'TITLE' => ($prevWeight/1000).' - '.($uperWeight/1000).' '.GetMessage('SALE_DH_UP_KG').'.',
						'GROUP' => 'common',
						'CHECK_FORMAT' => 'NUMBER'
			);

			$prevWeight = $uperWeight;
		}

		$arConfig['CONFIG']['tarif_section_3'] = array(
					'TYPE' => 'SECTION',
					'TITLE' => GetMessage('SALE_DH_UP_TARIF_DD'),
					'GROUP' => 'common',
		);

		$prevWeight = 0;
		foreach (self::$defaultTarifs["DOOR_DOOR"] as $uperWeight => $price)
		{
			$arConfig['CONFIG']['TARIF_DOOR_DOOR_'.$uperWeight] = array(
						'TYPE' => 'STRING',
						'DEFAULT' => $price,
						'TITLE' => ($prevWeight/1000).' - '.($uperWeight/1000).' '.GetMessage('SALE_DH_UP_KG').'.',
						'GROUP' => 'common',
						'CHECK_FORMAT' => 'NUMBER'
			);

			$prevWeight = $uperWeight;
		}

		$arConfig['CONFIG']['tarif_section_4'] = array(
					'TYPE' => 'SECTION',
					'TITLE' => GetMessage('SALE_DH_UP_TARIF_DV'),
					'GROUP' => 'common',
		);

		$arConfig['CONFIG']['OB_COMISS'] = array(
					'TYPE' => 'STRING',
					'DEFAULT' => self::$defaultTarifs["OB_COMISS"],
					'TITLE' => GetMessage('SALE_DH_UP_TARIF_DV_VALUE').' %',
					'GROUP' => 'common',
					'CHECK_FORMAT' => 'NUMBER'
		);


		$arConfig['CONFIG']['OB_COMISS_MIN'] = array(
					'TYPE' => 'STRING',
					'DEFAULT' => self::$defaultTarifs["OB_COMISS_MIN"],
					'TITLE' => GetMessage('SALE_DH_UP_TARIF_DV_MIN'),
					'GROUP' => 'common',
					'CHECK_FORMAT' => 'NUMBER'
		);

		//ware
		$aviableBoxes = self::getAviableBoxes();

		foreach ($aviableBoxes as $boxId => $arBox)
			CSaleDeliveryHelper::makeBoxConfig($boxId, $arBox, 'ware', $arConfig);

		//door
		foreach ($aviableBoxes as $boxId => $arBox)
			CSaleDeliveryHelper::makeBoxConfig($boxId, $arBox, 'door', $arConfig);

		return $arConfig;
	}

public static 	function GetSettings($strSettings)
	{
		return unserialize($strSettings);
	}

public static 	function SetSettings($arSettings)
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

public static 	function GetFeatures($arConfig)
	{
		$arResult = array();

		if ($arConfig["DELIVERY_TO_POST"]["VALUE"] == "ware")
			$arResult[GetMessage("SALE_DH_UP_SHIPPING_HANDLING")] = GetMessage("SALE_DH_UP_DTP_WARE");
		else
			$arResult[GetMessage("SALE_DH_UP_SHIPPING_HANDLING")] = GetMessage("SALE_DH_UP_DTP_DOOR");

		if ($arConfig["OB_COMISS"]["VALUE"] != 0 && $arConfig["OB_COMISS_MIN"]["VALUE"] != 0)
			$arResult[GetMessage("SALE_DH_UP_FEATURE_VALUE")] = GetMessage("SALE_DH_UP_FEATURE_ENABLED");

		return $arResult;
	}

public static 	function Calculate($profile, $arConfig, $arOrder, $STEP, $TEMP = false)
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
						"TEXT" => GetMessage("SALE_DH_UP_OVERLOAD"),
					);
		}

		$totalPrice = 0;


		foreach ($arPackagesParams as $arPackage)
		{
			$totalPrice += self::calculatePackPrice($arPackage, $profile, $arConfig);
		}

		$arResult = array(
			'RESULT' => 'OK',
			'VALUE' => $totalPrice,
			'PACKS_COUNT' => $packageCount
		);
		return $arResult;
	}

public static 	function Compability($arOrder, $arConfig)
	{
		if(floatval($arOrder["WEIGHT"]) <= self::$MAX_WEIGHT)
			$profiles = array('ware', 'door');
		else
			$profiles = array();

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

	private static function getConfValue(&$arConfig, $key)
	{
		return CSaleDeliveryHelper::getConfValue($arConfig[$key]);
	}

	private static function getAviableBoxes()
	{
		return array(
					array(
						"NAME" => GetMessage("SALE_DH_UP_STNRD_BOX"),
						"DIMENSIONS" => array("0", "0", "0")
						)
			);
	}

	private static function calculatePackPrice($arPackage, $profile, $arConfig)
	{
		$arDebug = array();
		$totalPrice = 0;

		$BO = floatval(self::getConfValue($arConfig, 'TARIF_BO'));
		$arDebug[] = 'BO: '.$BO;

		$T1 = floatval(self::getConfValue($arConfig, 'TARIF_T1'));
		$arDebug[] = 'T1: '.$T1;

		$weightForCalc =self::getWeightForCalc($arPackage['WEIGHT'], $arPackage['VOLUME']);
		$arDebug[] = 'calc weight: '.$weightForCalc;

		$CK = floatval(self::getConfValue($arConfig, 'OB_COMISS'))*$arPackage['PRICE']/100; //%
		$minComiss = floatval(self::getConfValue($arConfig, 'OB_COMISS_MIN'));

		if($CK < $minComiss)
			$CK = $minComiss;

		$arDebug[] = 'ccomiss: '.$CK;

		$deliveeryToPost = self::getConfValue($arConfig, 'DELIVERY_TO_POST');

		if($profile == 'door' && $deliveeryToPost == 'door') //door-door
		{
			foreach (self::$defaultTarifs["DOOR_DOOR"] as $uperWeight => $value)
			{
				if($uperWeight > $weightForCalc)
				{
					$servicePrice = self::getConfValue($arConfig, 'TARIF_DOOR_DOOR_'.$uperWeight);
					$arDebug[] = 'Service price: '.$servicePrice;
					break;
				}
			}
		}
		else
		{
			$servicePrice = $T1*ceil($weightForCalc/1000); //ware-ware
			$arDebug[] = 'Service price: '.$servicePrice;

			if($profile != $deliveeryToPost)  //ware-door or door-ware
			{
				foreach (self::$defaultTarifs["WARE_DOOR"] as $uperWeight => $value)
				{
					if($uperWeight > $weightForCalc)
					{
						$price = self::getConfValue($arConfig, 'TARIF_WARE_DOOR_'.$uperWeight);
						$arDebug[] = 'ware-door price: '.$price;
						$servicePrice += $price;
						$arDebug[] = 'Service price: '.$servicePrice;
						break;
					}
				}
			}
		}

		$totalPrice = $servicePrice+$BO+$CK;
		$arDebug[] = 'Total value: '.$totalPrice;

		return $totalPrice;
	}

	public static function calcVolumeWeightByVolume($volume)
	{
		return $volume/4000000;
	}

	public static function getWeightForCalc($weight, $volume)
	{
		$volWeight = self::calcVolumeWeightByVolume($volume);

		if(floatval($weight) >= floatval($volWeight))
			$result = $weight;
		else
			$result = $volWeight;

		return $result;
	}
}

AddEventHandler('sale', 'onSaleDeliveryHandlersBuildList', array('CDeliveryUaPost', 'Init'));

?>