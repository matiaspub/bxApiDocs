<?php

IncludeModuleLangFile(__FILE__);

/**
 * Class CSaleYMLocation
 * Mapping yandex locations to bitrix locations
 */
class CSaleYMLocation
{
	private $cityNames = array();
	private $cacheId = "CSaleYMLocations";

	public function __construct()
	{
		$this->getData();
	}

	/**
	 * returns locations data
	 */
	private function getData()
	{
		$ttl = 2592000;
		$cityNames = array();

		$cacheManager = \Bitrix\Main\Application::getInstance()->getManagedCache();

		if($cacheManager->read($ttl, $this->cacheId))
		{
			$cityNames = $cacheManager->get($this->cacheId);
		}
		else
		{
			$cityNames = $this->loadDataToCache();
			$cacheManager->set($this->cacheId, $cityNames);
		}

		$this->cityNames = $cityNames;

		return $cityNames;
	}

	/**
	 * Loads data from base
	 */
	private function loadDataToCache()
	{
		$cityNames = array();

		$dbLocations = CSaleLocation::GetList(
			array(),
			array(),
			false,
			false,
			array("ID", "CITY_NAME_LANG")
		);

		while($arLocation = $dbLocations->Fetch())
		{
			if(isset($arLocation["CITY_NAME_LANG"]) && strlen($arLocation["CITY_NAME_LANG"]) > 0 )
			{
				$cityNames[$arLocation["ID"]] = $this->strToLower($arLocation["CITY_NAME_LANG"]);
			}
		}

		return $cityNames;
	}

	/**
	 * @param $cityName
	 * @return int location id
	 */
	public function getLocationByCityName($cityName)
	{
		$result =  array_search($this->strToLower($cityName), $this->cityNames);
		return $result;
	}

	/**
	 * @param $string
	 * @return string
	 */
	private function strToLower($string)
	{
		static $bigLetters = "";
		static $smallLetters = "";

		if($bigLetters == "" || $smallLetters == "")
		{
			$bigLetters = GetMessage("SALE_YML_BIG_LETTERS")."ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			$smallLetters = GetMessage("SALE_YML_SMALL_LETTERS")."abcdefghijklmnopqrstuvwxyz";
		}

		$result = strtr($string, $bigLetters, $smallLetters);
		return $result;
	}
}