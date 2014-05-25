<?
/********************************************************************************
Delivery handler for Kazakhstan's Post Service (http://www.kazpost.kz/)
http://www.kazpost.kz/downloads/urlic/%D0%A2%D0%B0%D1%80%D0%B8%D1%84%D1%8B%20%D0%BD%D0%B0%20%D1%83%D1%81%D0%BB%D1%83%D0%B3%D1%83%20%D0%9F%D0%B5%D1%80%D0%B5%D1%81%D1%8B%D0%BB%D0%BA%D0%B0%20%D0%BF%D0%BE%D1%81%D1%8B%D0%BB%D0%BE%D0%BA%20%D0%B2%20%D0%BF%D1%80%D0%B5%D0%B4%D0%B5%D0%BB%D0%B0%D1%85%20%D0%A0%D0%9A%20%D0%B4%D0%BB%D1%8F%20%D0%B4%D0%B8%D1%81%D1%82%D0%B0%D0%BD%D1%86%D0%B8%D0%BE%D0%BD%D0%BD%D1%8B%D1%85%20%D0%BA%D0%BE%D0%BC%D0%BF%D0%B0%D0%BD%D0%B8%D0%B9%20%D0%B8%20%D0%B8%D0%BD%D1%82%D0%B5%D1%80%D0%BD%D0%B5%D1%82%20%D0%BC%D0%B0%D0%B3%D0%B0%D0%B7%D0%B8%D0%BD%D0%BE%D0%B2.xlsx
********************************************************************************/
CModule::IncludeModule('sale');

IncludeModuleLangFile('/bitrix/modules/sale/delivery/delivery_kaz_post.php');

class CDeliveryKazPost
{
	private static $MAX_WEIGHT = 10000;	// (g)
	private static $MAX_DIMENSIONS = array("800", "800", "500"); //milimeters

	private static $BASE_WEIGHT = 1000;	// Base weight gramm

	private static $TARIFS = array();
	private static $TARIF_IDX = 0;
	private static $TARIF_DEFAULT = 1;
	private static $TARIF_DESCR = 2;

	public static function Init()
	{
		self::$TARIFS = array(
							"BASE" => array(
								'WEIGHT_LESS_1000' => array(1, 550, GetMessage('SALE_DH_KP_LESS_1')),
								'WEIGHT_MORE_1000' => array(2, 50, GetMessage('SALE_DH_KP_MORE_1'))
								),

							"CAPITAL" => array(
								'WEIGHT_LESS_1000' => array(3, 450, GetMessage('SALE_DH_KP_LESS_1')),
								'WEIGHT_MORE_1000' => array(4, 50, GetMessage('SALE_DH_KP_MORE_1')),
								)
			);

		return array(
			/* Basic description */
			'SID' => 'kaz_post',
			'NAME' => GetMessage('SALE_DH_KP_NAME'),
			'DESCRIPTION' => GetMessage('SALE_DH_KP_DESCR').' <a href="http://www.kazpost.kz">http://www.kazpost.kz</a>',
			'DESCRIPTION_INNER' => GetMessage('SALE_DH_KP_DESCR').' <a href="http://www.kazpost.kz">http://www.kazpost.kz</a>',
			'BASE_CURRENCY' => 'KZT',
			'HANDLER' => __FILE__,
			/* Handler methods */
			'DBGETSETTINGS' => array('CDeliveryKazPost', 'GetSettings'),
			'DBSETSETTINGS' => array('CDeliveryKazPost', 'SetSettings'),
			'GETCONFIG' => array('CDeliveryKazPost', 'GetConfig'),
			'COMPABILITY' => array('CDeliveryKazPost', 'Compability'),
			'CALCULATOR' => array('CDeliveryKazPost', 'Calculate'),

			/* List of delivery profiles */
			'PROFILES' => array(
				'distant_inner' => array(
					'TITLE' => GetMessage('SALE_DH_KP_DI_TITLE'),
					'DESCRIPTION' => GetMessage('SALE_DH_KP_DI_DESCR'),
					'RESTRICTIONS_WEIGHT' => array(0, self::$MAX_WEIGHT),
					'RESTRICTIONS_SUM' => array(0),
					'TAX_RATE' => 0,
					'RESTRICTIONS_MAX_SIZE' => 0,
					'RESTRICTIONS_DIMENSIONS_SUM' => 0,
					'RESTRICTIONS_DIMENSIONS' => self::$MAX_DIMENSIONS
					)
			)
		);
	}

	public static function GetConfig()
	{
		$arConfig = array(
			'CONFIG_GROUPS' => array(
				'distant_inner' => GetMessage('SALE_DH_KP_DI_TITLE'),
			),
		);

		$aviableBoxes = self::getAviableBoxes();

		foreach ($aviableBoxes as $boxId => $arBox)
			CSaleDeliveryHelper::makeBoxConfig($boxId, $arBox, 'distant_inner', $arConfig);

		$arConfig['CONFIG']['tarif_section_1'] = array(
					'TYPE' => 'SECTION',
					'TITLE' => GetMessage('SALE_DH_KP_TARIF_TITLE'),
					'GROUP' => 'distant_inner',
		);

		foreach (self::$TARIFS["BASE"] as $arTarif)
		{
			$tarifId = $arTarif[self::$TARIF_IDX];
			$arConfig['CONFIG']['TARIF_'.$tarifId] = array(
						'TYPE' => 'STRING',
						'DEFAULT' => $arTarif[self::$TARIF_DEFAULT],
						'TITLE' => $arTarif[self::$TARIF_DESCR],
						'GROUP' => 'distant_inner',
			);
		}

		$arConfig['CONFIG']['tarif_section_2'] = array(
					'TYPE' => 'SECTION',
					'TITLE' => GetMessage('SALE_DH_KP_TARIF_IREG'),
					'GROUP' => 'distant_inner',
		);

		foreach (self::$TARIFS["CAPITAL"] as $arTarif)
		{
			$tarifId = $arTarif[self::$TARIF_IDX];
			$arConfig['CONFIG']['TARIF_'.$tarifId] = array(
						'TYPE' => 'STRING',
						'DEFAULT' => $arTarif[self::$TARIF_DEFAULT],
						'TITLE' => $arTarif[self::$TARIF_DESCR],
						'GROUP' => 'distant_inner',
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
						"TEXT" => GetMessage("SALE_DH_KP_OVERLOAD"),
					);
		}

		$totalPrice = 0;

		$shopLocationId = CSaleHelper::getShopLocationId();
		$arShopLocation = CSaleLocation::GetByID($shopLocationId);
		$arLocationTo = CSaleLocation::GetByID($arOrder['LOCATION_TO']);

		foreach ($arPackagesParams as $arPackage)
			$totalPrice += self::calculatePackPrice($arPackage, $profile, $arConfig, $arShopLocation['REGION_ID'], $arLocationTo['REGION_ID']);

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

		$aviableBoxes = CSaleDeliveryHelper::getBoxesFromConfig('distant_inner', $arConfig);

		foreach ($aviableBoxes as $arBox)
		{
			if (CSaleDeliveryHandler::checkDimensions($arOrder["MAX_DIMENSIONS"], $arBox["DIMENSIONS"]))
			{
				$result = array('distant_inner');
				break;
			}
		}

		return $result;
	}

	private static function getConfValue(&$arConfig, $key)
	{
		return CSaleDeliveryHelper::getConfValue($arConfig[$key]);
	}

	private static function getAviableBoxes()
	{
		return array(
					array(
						"NAME" => GetMessage("SALE_DH_KP_STNDRD_BOX"),
						"DIMENSIONS" => array("800", "800", "500")
						)
			);
	}

	private static function calculatePackPrice($arPackage, $profile, $arConfig, $regionIdFrom, $regionIdTo)
	{
		$arDebug = array();
		$basePrice = $totalPrice = 0;

		if($regionIdFrom == $regionIdTo)
			$tarifGroup = 'CAPITAL';
		else
			$tarifGroup = 'BASE';

		$basePrice = floatval(self::getConfValue($arConfig, 'TARIF_'.self::$TARIFS[$tarifGroup]['WEIGHT_LESS_1000'][self::$TARIF_IDX]));
		$arDebug[] = 'Base Price less 1000 g: '.$basePrice;

		if($arPackage['WEIGHT'] > self::$BASE_WEIGHT)
		{
			$addWeight = ceil(($arPackage['WEIGHT'] - self::$BASE_WEIGHT)/500);
			$addPrice = floatval(self::getConfValue($arConfig, 'TARIF_'.self::$TARIFS[$tarifGroup]['WEIGHT_MORE_1000'][self::$TARIF_IDX]));
			$arDebug[] = 'Price for additional weight more than 1000 g: '.$addWeight * $addPrice;
			$basePrice += $addWeight * $addPrice;
		}

		$totalPrice = $basePrice;
		$arDebug[] = 'Total value: '.$totalPrice;
		//var_dump($arDebug);
		return $totalPrice;
	}
}

AddEventHandler('sale', 'onSaleDeliveryHandlersBuildList', array('CDeliveryKazPost', 'Init'));

?>