<?php
namespace Bitrix\Sale;

/**
 * Class SalesZone
 * @package Bitrix\Sale *
 */
class SalesZone
{
	/**
	 * @param string $lang - language Id
	 * @return array - list of all regions
	 */
	public static function getAllRegions($lang)
	{
		static $result = null;

		if($result === null)
		{
			$result = array();
			$dbRegionList = \CSaleLocation::GetRegionList(array(), array(), $lang);

			while ($arRegion = $dbRegionList->GetNext())
				$result[$arRegion["ID"]] = $arRegion["NAME_LANG"];
		}

		return $result;
	}

	/**
	 * @param string $lang - language Id
	 * @return array - list of all cities
	 */
	public static function getAllCities($lang)
	{
		static $result = null;

		if($result === null)
		{
			$result = array();
			$dbCityList = \CSaleLocation::GetCityList(array(), array(), $lang);
			while($arCity = $dbCityList->GetNext())
				$result[$arCity["ID"]] = $arCity["NAME_LANG"];
		}

		return $result;
	}

	/**
	 * Checks if country Id is in list of sales zone countries Ids.
	 * @param int $countryId
	 * @param string $siteId
	 * @return bool
	 */
	public static function checkCountryId($countryId, $siteId)
	{
		$cIds = static::getCountriesIds($siteId);
		return in_array($countryId, $cIds) || in_array("", $cIds);
	}

	/**
	 * Checks if regionId is in list of sales zone regions Ids
	 * @param int $regionId
	 * @param string $siteId
	 * @return bool
	 */
	public static function checkRegionId($regionId, $siteId)
	{
		$rIds = static::getRegionsIds($siteId);
		return in_array($regionId, $rIds) || in_array("", $rIds);
	}

	/**
	 * Checks if citiy Id is in list of sales zone cities Ids
	 * @param int $cityId
	 * @param string $siteId
	 * @return bool
	 */
	public static function checkCityId($cityId, $siteId)
	{
		$cIds = static::getCitiesIds($siteId);
		return in_array($cityId, $cIds) || in_array("", $cIds);
	}

	/**
	 * Checks if location id is in sales zone
	 * @param int $locationId
	 * @param string $siteId
	 * @return bool
	 */
	public static function checkLocationId($locationId, $siteId)
	{
		$result = false;

		$arLocation = \CSaleLocation::GetByID($locationId);

		if(static::checkCountryId($arLocation["COUNTRY_ID"], $siteId)
			&& static::checkRegionId($arLocation["REGION_ID"], $siteId)
			&& static::checkCityId($arLocation["CITY_ID"], $siteId)
		)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @param string $siteId
	 * @return array - sales zones cities Ids
	 */
	public static function getCitiesIds($siteId)
	{
		return explode(":" , \COption::GetOptionString('sale', 'sales_zone_cities', '', $siteId));
	}

	/**
	 * @param string $siteId
	 * @return array - sales zones regions Ids
	 */
	public static function getRegionsIds($siteId)
	{
		return explode(":" , \COption::GetOptionString('sale', 'sales_zone_regions', '', $siteId));
	}

	/**
	 * @param string $siteId
	 * @return array - sales zones countries Ids
	 */
	public static function getCountriesIds($siteId)
	{
		return explode(":" , \COption::GetOptionString('sale', 'sales_zone_countries', '', $siteId));
	}

	/**
	 * Returns filter for using in queries such as in bitrix/modules/sale/install/components/bitrix/sale.ajax.locations/search.php
	 * @param string $object (city|region|country)
	 * @param string $siteId
	 * @return array
	 */
	public static function makeSearchFilter($object, $siteId)
	{
		$result = array();

		$countries = static::getCountriesIds($siteId);
		$regions = static::getRegionsIds($siteId);
		$cities = static::getCitiesIds($siteId);

		if(!in_array("", $cities) && $object == "city")
			$result = array("CITY_ID" => $cities);
		elseif(!in_array("", $regions) && ($object == "city"  || $object == "region"))
			$result = array("REGION_ID" => $regions);
		elseif(!in_array("", $countries))
			$result = array("COUNTRY_ID" => $countries);

		return $result;
	}

	/**
	 * @param array $countriesIds
	 * @param string $lang
	 * @return array - regions from sales zone
	 */
	public static function getRegions($countriesIds = array(), $lang = LANGUAGE_ID)
	{

		$regions = array();
		$regionsList = static::getAllRegions($lang);
		$getCountryNull = in_array("NULL", $countriesIds) ? true : false;
		$filter = in_array("", $countriesIds) ? array() : array(($getCountryNull ? "+" : "")."COUNTRY_ID" => $countriesIds);

		$dbLocationsList = \CSaleLocation::GetList(
			array("SORT"=>"ASC", "REGION_NAME_LANG"=>"ASC"),
			$filter,
			array("REGION_ID", "COUNTRY_ID")
		);

		while($arRegion = $dbLocationsList->GetNext())
			if(strlen($arRegion["REGION_ID"]) > 0 && $arRegion["REGION_ID"] != "0")
				$regions[$arRegion["REGION_ID"]] = $regionsList[$arRegion["REGION_ID"]];

		return $regions;
	}

	/**
	 * @param array $countriesIds
	 * @param array $regionsIds
	 * @param string $lang
	 * @return array cities list from sales zone
	 */
	public static function getCities($countriesIds = array(), $regionsIds = array(), $lang )
	{
		$cities = array();
		$citiesList = static::getAllCities($lang);
		$getRegionNull = in_array("NULL", $regionsIds) ? true : false;
		$getRegionAll = in_array("", $regionsIds) ? true : false;
		$getCountryNull = in_array("NULL", $countriesIds) ? true : false;
		$getCountryAll = in_array("", $countriesIds) ? true : false;

		$filter = in_array("", $regionsIds) ? array() : array(($getRegionNull ? "+" : "")."REGION_ID" => $regionsIds);

		foreach($countriesIds as $countryId)
		{
			if(($getRegionNull || $getRegionAll) && !$getCountryAll)
				$filter[($getCountryNull ? "+" : "")."COUNTRY_ID"] = $countryId;

			$dbLocationsList = \CSaleLocation::GetList(
				array("SORT"=>"ASC", "CITY_NAME_LANG"=>"ASC"),
				$filter,
				array("CITY_ID")
			);

			while($arCity = $dbLocationsList->GetNext())
				if(strlen($arCity["CITY_ID"]) > 0)
					$cities[$arCity["CITY_ID"]] =  $citiesList[$arCity["CITY_ID"]];
		}

		return  $cities;
	}
}