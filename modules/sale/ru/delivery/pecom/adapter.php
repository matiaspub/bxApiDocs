<?php

namespace Bitrix\Sale\Delivery\Pecom;

/**
 * Class Adapter
 * @package Bitrix\Sale\Delivery\Pecom
 */
class Adapter
{
	public static function preparePreregistrationReqData($arOrder, $profileId, $arConfig)
	{
		$result = array();
		$result["sender"] = array(
			"inn" => $arConfig["INN"]["VALUE"],
			"city" => static::getFilialAndCity($arConfig["CITY_DELIVERY"]["VALUE"]),
			"title" => $arConfig["NAME"]["VALUE"],
			"phone" => $arConfig["PHONE"]["VALUE"],
		);

		$inn = "";
		$city = "";
		$title = "";
		$phone = "";
		$address = "";

		if(isset($extraParams["location"]))
			$city = $extraParams["location"];

		$dbOrderProps = \CSaleOrderPropsValue::GetOrderProps($arOrder["ID"]);

		while ($arOrderProps = $dbOrderProps->Fetch())
		{
			if($arOrderProps["CODE"] == "COMPANY" || $arOrderProps["CODE"] == "FIO")
				$title = $arOrderProps["VALUE"];

			if($arOrderProps["CODE"] == "INN")
				$inn = $arOrderProps["VALUE"];

			if($arOrderProps["CODE"] == "PHONE")
				$phone = $arOrderProps["VALUE"];

			if($arOrderProps["CODE"] == "LOCATION")
			{
				$location = $arOrderProps["VALUE"];
				$locDelivery = Adapter::mapLocation($location); // todo: if more than one
				$city = static::getFilialAndCity(key($locDelivery));
			}

			if($arOrderProps["CODE"] == "ADDRESS")
				$address = $arOrderProps["VALUE"];
		}

		$arPacks = \CSaleDeliveryHelper::getBoxesFromConfig($profileId, $arConfig);

		$arPackagesParams = \CSaleDeliveryHelper::getRequiredPacks(
			$arOrder["ITEMS"],
			$arPacks,
			0);

		$result["cargos"] = array(
			array(
				"common" => array(
					"positionsCount" => count($arPackagesParams),
					"decription" => GetMessage("SALE_DH_PECOM_DESCRIPTION_GOODS"),
					"orderNumber" => $arOrder["ACCOUNT_NUMBER"],
					"paymentForm" => $arConfig["PAYMENT_FORM"]["VALUE"],
					"accompanyingDocuments" => false
				),
				"receiver" => array(
					"inn" => $inn,
					"city" => $city,
					"title" => $title,
					"phone" => $phone,
					"addressStock" => $address
				),
				"services" => array(

					"transporting" =>array(
						"payer" => array(
							"type" => 1
						)
					),

					"hardPacking" => array(
						"enabled" => \CDeliveryPecom::isConfCheckedVal($arConfig, 'SERVICE_OTHER_RIGID_PACKING'),
						"payer" => array(
							"type" => \CDeliveryPecom::getConfValue($arConfig, 'SERVICE_OTHER_RIGID_PAYER')
						)
					),

					//!hardPacking or palletTransporting - not both
					"palletTransporting" => array(
						"enabled" => !\CDeliveryPecom::isConfCheckedVal($arConfig, 'SERVICE_OTHER_RIGID_PACKING') && \CDeliveryPecom::isConfCheckedVal($arConfig, 'SERVICE_OTHER_PALLETE'),
						"payer" => array(
							"type" => \CDeliveryPecom::getConfValue($arConfig, 'SERVICE_OTHER_PALLETE_PAYER')
						)
					),

					"insurance" => array(
						"enabled" => \CDeliveryPecom::isConfCheckedVal($arConfig, 'SERVICE_OTHER_INSURANCE'),
						"payer" => array(
							"type" => \CDeliveryPecom::getConfValue($arConfig, 'SERVICE_OTHER_INSURANCE_PAYER')
						),
						"cost" => intval($arOrder["PRICE"])
					),

					"sealing" => array(
						"enabled" => \CDeliveryPecom::isConfCheckedVal($arConfig, 'SERVICE_OTHER_PLOMBIR_ENABLE'),
						"payer" => array(
							"type" => \CDeliveryPecom::getConfValue($arConfig, 'SERVICE_OTHER_PLOMBIR_PAYER')
						)
					),

					"strapping" => array(
						"enabled" => false
					),

					"documentsReturning" => array(
						"enabled" => false
					),

					"delivery" => array(
						"enabled" => \CDeliveryPecom::isConfCheckedVal($arConfig, 'SERVICE_DELIVERY_ENABLED'),
						"payer" => array(
							"type" => \CDeliveryPecom::getConfValue($arConfig, 'SERVICE_OTHER_DELIVERY_PAYER')
						)
					)
				)
			)
		);

		return $result;
	}

	/**
	 * Returns Pecom .location id
	 * @param $locationId - Bitrix location id
	 * @param bool $cleanCache
	 * @return array - Pecom location(s) id
	 */
	public static function mapLocation($locationId, $cleanCache = false)
	{
		$cityName = static::getCityNameFromLocationId($locationId);

		if(!$cityName)
			return array();

		$ttl = 2592000;
		$cacheId = "SaleDeliveryPecomMapLocations".$locationId;
		$data = array();

		$cacheManager = \Bitrix\Main\Application::getInstance()->getManagedCache();

		if($cleanCache)
		{
			$cacheManager->clean($cacheId);
		}

		if($cacheManager->read($ttl, $cacheId))
		{
			$data = $cacheManager->get($cacheId);
		}

		if(empty($data))
		{
			$pecCities = static::getAllPecomCities();
			$data = array();

			foreach($pecCities as $key => $cities)
			{
				foreach($cities as $smallCityKey => $smallCityName)
				{
					$pos = strpos($smallCityName, $cityName);
					if($pos !== false
						&& (
							strlen($cityName) == strlen($smallCityName)
							|| (
								substr($smallCityName,$pos+strlen($cityName), 1) == " "
								&& (
									$pos == 0
									|| substr($smallCityName,$pos-1, 1) == " "
								)
							)
						)
					)
					{
						$data[$smallCityKey] = $smallCityName;
					}
				}
			}

			$cacheManager->set($cacheId, $data);
		}

		return $data;
	}

	public static function getAllPecomCities($cleanCache = false)
	{
		global $APPLICATION;
		$ttl = 2592000;
		$data = array();
		$cacheId = "SaleDeliveryPecomCities";

		$cacheManager = \Bitrix\Main\Application::getInstance()->getManagedCache();

		if($cleanCache)
		{
			$cacheManager->clean($cacheId);
		}

		if($cacheManager->read($ttl, $cacheId))
		{
			$data = $cacheManager->get($cacheId);
		}

		if(empty($data))
		{
			$http = new \Bitrix\Main\Web\HttpClient(array(
				"version" => "1.1",
				"socketTimeout" => 30,
				"streamTimeout" => 30,
				"redirect" => true,
				"redirectMax" => 5,
			));

			$jsnData = $http->get("http://www.pecom.ru/ru/calc/towns.php");
			$errors = $http->getError();

			if (!$jsnData && !empty($errors))
			{
				$strError = "";

				foreach($errors as $errorCode => $errMes)
					$strError .= $errorCode.": ".$errMes;

				\CEventLog::Add(array(
					"SEVERITY" => "ERROR",
					"AUDIT_TYPE_ID" => "SALE_DELIVERY",
					"MODULE_ID" => "sale",
					"ITEM_ID" => "PECOM_GET_TOWNS",
					"DESCRIPTION" => $strError,
				));
			}

			$data = json_decode($jsnData, true);

			if(strtolower(SITE_CHARSET) != 'utf-8')
			{
				$data = $APPLICATION->ConvertCharsetArray($data, 'utf-8', SITE_CHARSET);
				if(is_array($data))
				{
					foreach($data as $key => $value)
					{
						$newKey = $APPLICATION->ConvertCharset($key, 'utf-8', SITE_CHARSET);
						$data[$newKey] = $value;
						unset($data[$key]);
					}
				}
			}

			if(!is_array($data))
				$data = array();

			$cacheManager->set($cacheId, $data);
		}

		return $data;
	}

	public static function getCityNameFromLocationId($locationId)
	{
		$loc = \CSaleLocation::GetById($locationId);
		return isset($loc["CITY_NAME_LANG"]) ? $loc["CITY_NAME_LANG"] : false;
	}

	public static function getFilialAndCity($cityId)
	{
		$result = false;
		$ttl = 2592000;
		$cacheId = "SaleDeliveryPecomFilialAndCity".$cityId;
		$cacheManager = \Bitrix\Main\Application::getInstance()->getManagedCache();

		if(strlen($cityId) > 0)
		{
			if($cacheManager->read($ttl, $cacheId))
			{
				$result = $cacheManager->get($cacheId);
			}
			else
			{
				$locations = static::getAllPecomCities();
				foreach($locations as $filial => $cities)
				{
					foreach($cities as $cId => $city)
					{
						if($cityId == $cId)
						{
							$result = $filial;

							if($filial != $city)
							{
								$arCity = explode(" ", $city);
								$result .= " ".$arCity[0];
							}

							break;
						}
					}
				}

				$cacheManager->set($cacheId, $result);
			}
		}

		return $result;
	}

	public static function prepareSubscribeReqData($arCargoCodes, $email = "", $phone = "")
	{
		$arData = array();

		if(is_array($arCargoCodes) && !empty($arCargoCodes) && (strlen($email) > 0 || strlen($phone) > 0))
		{
			$arData["cargoCodes"] = $arCargoCodes;

			if(strlen($email) > 0)
				$arData["email"] = $email;

			if(strlen($phone) > 0)
				$arData["phone"] = $phone;
		}

		return $arData;
	}
}