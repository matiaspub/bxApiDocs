<?
/********************************************************************************
Simple delivery handler. 
It uses fixed delivery price for any location groups. Needs at least one group of locations to be configured.
********************************************************************************/
CModule::IncludeModule("sale");

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/sale/delivery/delivery_simple.php');

class CDeliverySimple
{
	public static function Init()
	{
		return array(
			/* Basic description */
			"SID" => "simple",
			"NAME" => GetMessage('SALE_DH_SIMPLE_NAME'),
			"DESCRIPTION" => GetMessage('SALE_DH_SIMPLE_DESCRIPTION'),
			"DESCRIPTION_INNER" => GetMessage('SALE_DH_SIMPLE_DESCRIPTION_INNER'),
			"BASE_CURRENCY" => COption::GetOptionString("sale", "default_currency", "RUB"),

			"HANDLER" => __FILE__,
			
			/* Handler methods */
			"DBGETSETTINGS" => array("CDeliverySimple", "GetSettings"),
			"DBSETSETTINGS" => array("CDeliverySimple", "SetSettings"),
			"GETCONFIG" => array("CDeliverySimple", "GetConfig"),
			
			"COMPABILITY" => array("CDeliverySimple", "Compability"),			
			"CALCULATOR" => array("CDeliverySimple", "Calculate"),			
			
			/* List of delivery profiles */
			"PROFILES" => array(
				"simple" => array(
					"TITLE" => GetMessage("SALE_DH_SIMPLE_SIMPLE_TITLE"),
					"DESCRIPTION" => GetMessage("SALE_DH_SIMPLE_SIMPLE_DESCRIPTION"),
					
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
				"all" => GetMessage('SALE_DH_SIMPLE_CONFIG_TITLE'),
			),
			
			"CONFIG" => array(),
		);
		
		$dbLocationGroups = CSaleLocationGroup::GetList();
		while ($arLocationGroup = $dbLocationGroups->Fetch())
		{
			$arConfig["CONFIG"]["price_".$arLocationGroup["ID"]] = array(
				"TYPE" => "STRING",
				"DEFAULT" => "",
				"TITLE" => GetMessage("SALE_DH_SIMPLE_GROUP_PRICE")." \"".$arLocationGroup["NAME"]."\" (".COption::GetOptionString("sale", "default_currency", "RUB").')',
				"GROUP" => "all",
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
				$arSettings[$key] = doubleval($value);
			else
				unset($arSettings[$key]);
		}
	
		return serialize($arSettings);
	}

	public static function __GetLocationPrice($LOCATION_ID, $arConfig)
	{
		$dbLocationGroups = CSaleLocationGroup::GetLocationList(array("LOCATION_ID" => $LOCATION_ID));
		
		while ($arLocationGroup = $dbLocationGroups->Fetch())
		{
			if (
				array_key_exists('price_'.$arLocationGroup["LOCATION_GROUP_ID"], $arConfig) 
				&& 
				strlen($arConfig['price_'.$arLocationGroup["LOCATION_GROUP_ID"]]["VALUE"]) > 0
			)
			{
				return $arConfig['price_'.$arLocationGroup["LOCATION_GROUP_ID"]]["VALUE"];
			}
		}

		return false;
	}
	
	public static function Calculate($profile, $arConfig, $arOrder, $STEP, $TEMP = false)
	{
		return CDeliverySimple::__GetLocationPrice($arOrder["LOCATION_TO"], $arConfig);
	}
	
	public static function Compability($arOrder, $arConfig)
	{
		$price = CDeliverySimple::__GetLocationPrice($arOrder["LOCATION_TO"], $arConfig);
		
		if ($price === false)
			return array();
		else
			return array('simple');
	} 
}

AddEventHandler("sale", "onSaleDeliveryHandlersBuildList", array('CDeliverySimple', 'Init')); 
?>