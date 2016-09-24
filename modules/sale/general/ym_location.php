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
				$cityNames[$arLocation["ID"]] = ToLower($arLocation["CITY_NAME_LANG"]);
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
		$result =  array_search(ToLower($cityName), $this->cityNames);
		return $result;
	}
}