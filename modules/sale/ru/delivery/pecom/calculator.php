<?php

namespace Bitrix\Sale\Delivery\Pecom;

/**
 * Class Calculator
 * @package Bitrix\Sale\Delivery\Pecom
 */
class Calculator
{
	protected $arConfig = array();
	protected $arOrder = array();
	protected $profileId = "";
	protected $packsCount = 0;

	/**
	 * @param $arOrder
	 * @param $arConfig
	 * @param string $profileId
	 */
	public function __construct($arOrder, $arConfig, $profileId = "")
	{
		$this->arOrder = $arOrder;
		$this->arConfig = $arConfig;
		$this->profileId = $profileId;
	}

	/**
	 * Returns array wich can include "avia" and/or "auto" or be empty
	 * @return array
	 */
	public function getCompabilityInfo()
	{
		$arResult = array();

		$params = $this->createCompParamsStr();
		$jsonInfo = $this->send($params);
		$arInfo = json_decode($jsonInfo, true);

		if(isset($arInfo["auto"]))
			$arResult[] = "auto";

		if(isset($arInfo["avia"]))
			$arResult[] = "avia";

		return $arResult;
	}

	/**
	 * Returns calculations results
	 * @return array
	 */
	public function getPriceInfo()
	{
		try
		{
			$strParams = $this->createCalcParams();
			$jsonInfo = $this->send($strParams);
			$result = $this->parseCalcResult($jsonInfo);
		}
		catch(\Exception $e)
		{
			$result = array(
				'RESULT' => 'ERROR',
				'TEXT' => $e->getMessage()
			);
		}

		return $result;
	}

	protected function parseCalcResult($jsonInfo)
	{
		global $APPLICATION;
		$arInfo = json_decode($jsonInfo,true);

		if(is_array($arInfo) && !empty($arInfo))
		{
			if(isset($arInfo[$this->profileId][2]))
			{
				$price = 0;

				$price += intval($arInfo[$this->profileId][2]);

				if(isset($arInfo["take"][2]) && \CDeliveryPecom::isConfCheckedVal($this->arConfig, 'SERVICE_TAKE_ENABLED'))
					$price += intval($arInfo["take"][2]);


				if(isset($arInfo["deliver"][2]) && \CDeliveryPecom::isConfCheckedVal($this->arConfig, 'SERVICE_TAKE_ENABLED'))
					$price += intval($arInfo["deliver"][2]);

				foreach($arInfo as $key => $value)
					if(substr($key,0,3) == "ADD")
						$price += intval($arInfo[$key][2]);

				$arResult = array(
					'RESULT' => 'OK',
					'VALUE' => $price,
					'PACKS_COUNT' => $this->packsCount
				);

				if(isset($arInfo["periods"]))
				{
					$str = explode("<br>", $arInfo["periods"]);

					if(isset($str[0]))
					{
						$days = explode(":",$str[0]);

						if(isset($days[2]) && strlen($days[2]) > 0)
							$arResult["TRANSIT"] = $days[2];
					}
				}
			}
			else
			{
				if(isset($arInfo["error"]))
				{
					$error = implode("<br>", $arInfo["error"]);

					if(strtolower(SITE_CHARSET) != 'utf-8')
						$error = $APPLICATION->ConvertCharset($error, 'utf-8', SITE_CHARSET);
				}
				else
				{
					$error = GetMessage("SALE_DH_PECOM_ERROR");
				}

				$arResult = array(
					'RESULT' => 'ERROR',
					'TEXT' => $error
				);
			}
		}
		else
		{
			$arResult = array(
				'RESULT' => 'ERROR',
				'TEXT' => GetMessage("SALE_DH_PECOM_ERROR_NO_RESULTS")
			);
		}

		return $arResult;
	}

	protected static function send($strParams)
	{
		$http = new \Bitrix\Main\Web\HttpClient(array(
			"version" => "1.1",
			"socketTimeout" => 30,
			"streamTimeout" => 30,
			"redirect" => true,
			"redirectMax" => 5,
		));

		$jsnData = $http->post("http://www.pecom.ru/bitrix/components/pecom/calc/ajax.php", $strParams);
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
				"ITEM_ID" => "PECOM_CALCULATOR_SEND",
				"DESCRIPTION" => $strError,
			));
		}


		return $jsnData;
	}

	protected function createCompParamsStr()
	{
		$arLocation = Adapter::mapLocation($this->arOrder["LOCATION_TO"]);

		if(count($arLocation) > 0)
		{
			$params = 'places[0][]=1'.
				'&places[0][]=1'.
				'&places[0][]=1'.
				'&places[0][]=1'.
				'&places[0][]=1'.
				'&places[0][]=1'.
				'&places[0][]=0'.
				'&take[town]='.$this->arConfig["CITY_DELIVERY"]["VALUE"].
				'&take[tent]=0'.
				'&take[gidro]=0'.
				'&take[speed]=0'.
				'&take[moscow]=0'.
				'&deliver[town]='.strval(key($arLocation)).
				'&deliver[tent]=0'.
				'&delideliver[gidro]=0'.
				'&deliver[speed]=0'.
				'&deliver[moscow]=0'.
				'&plombir=0'.
				'&strah=0'.
				'&ashan=0';
		}
		else
		{
			$params = "";
		}

		return $params;
	}

	protected function createCalcParams()
	{	
		if(!isset($this->arOrder["WEIGHT"]))
			throw new \Exception(GetMessage("SALE_DH_PECOM_EXCPT_WEIGHT"));

		$locationTo = "";
		$arLocation = Adapter::mapLocation($this->arOrder["LOCATION_TO"]);

		if(empty($arLocation))
			throw new \Exception(GetMessage("SALE_DH_PECOM_EXCPT_EMPTY_LOCATION"));

		if(count($arLocation) > 1 && isset($this->arOrder["EXTRA_PARAMS"]["location"]))
		{
			$locationTo = $this->arOrder["EXTRA_PARAMS"]["location"];
		}
		elseif (count($arLocation) > 1 && !isset($this->arOrder["EXTRA_PARAMS"]["location"]))
		{
			throw new \Exception(GetMessage("SALE_DH_PECOM_EXCPT_MANY_LOCATIONS"));
		}

		if(count($arLocation) == 1)
		{
			$locationTo = key($arLocation);
		}

		if(!isset($this->arOrder["ITEMS"]) || !is_array($this->arOrder["ITEMS"]) || empty($this->arOrder["ITEMS"]))
			throw new \Exception(GetMessage("SALE_DH_PECOM_EXCPT_EMPTY_ITEMS"));

		$measureCoeff = 1000;
		$itemsStr = "";
		$loadingRange = true;
		$rigidPacking = \CDeliveryPecom::isConfCheckedVal($this->arConfig, 'SERVICE_OTHER_RIGID_PACKING');

		$arPacks = \CSaleDeliveryHelper::getBoxesFromConfig($this->profileId, $this->arConfig);

		$arPackagesParams = \CSaleDeliveryHelper::getRequiredPacks(
			$this->arOrder["ITEMS"],
			$arPacks,
			0);

		$this->packsCount = count($arPackagesParams);

		for($i = $this->packsCount-1; $i >= 0; $i--)
		{
			$item = $arPackagesParams[$i];
			$width = round(floatval($item["DIMENSIONS"]["WIDTH"])/$measureCoeff, 2);
			$lenght = round(floatval($item["DIMENSIONS"]["LENGTH"])/$measureCoeff, 2);
			$height = round(floatval($item["DIMENSIONS"]["HEIGHT"])/$measureCoeff, 2);
			$volume = floatval($item["VOLUME"]/(pow($measureCoeff,3)));

			if($width > \CDeliveryPecom::$EXTRA_DIMENSIONS_SIZE
				|| $lenght > \CDeliveryPecom::$EXTRA_DIMENSIONS_SIZE
				|| $height > \CDeliveryPecom::$EXTRA_DIMENSIONS_SIZE
				|| $item["WEIGHT"] > \CDeliveryPecom::$EXTRA_DEMENSIONS_WEIGHT
			)
				$loadingRange = false;

			$itemsStr .= 'places['.$i.'][]='.strval($width).
				'places['.$i.'][]='.strval($lenght).
				'places['.$i.'][]='.strval($height).
				'places['.$i.'][]='.strval($volume).
				'places['.$i.'][]='.$item["WEIGHT"].
				'places['.$i.'][]='.($loadingRange ? '1' : '0').
				'places['.$i.'][]='.($rigidPacking && \CDeliveryPecom::getConfValue($this->arConfig, 'SERVICE_OTHER_RIGID_PAYER') == \CDeliveryPecom::$PAYER_BUYER ? '1' : '0');
		}

		if(\CDeliveryPecom::isConfCheckedVal($this->arConfig, 'SERVICE_OTHER_PLOMBIR_ENABLE') && \CDeliveryPecom::getConfValue($this->arConfig, 'SERVICE_OTHER_PLOMBIR_PAYER') == \CDeliveryPecom::$PAYER_BUYER)
			$plombir = strval(intval(\CDeliveryPecom::getConfValue($this->arConfig, 'SERVICE_OTHER_PLOMBIR_COUNT')));
		else
			$plombir = "0";

		if(\CDeliveryPecom::isConfCheckedVal($this->arConfig, 'SERVICE_OTHER_INSURANCE') && \CDeliveryPecom::getConfValue($this->arConfig, 'SERVICE_OTHER_INSURANCE_PAYER') == \CDeliveryPecom::$PAYER_BUYER)
			$insurance = strval($this->arOrder["PRICE"]);
		else
			$insurance = "0";

		$result = $itemsStr.
			'&take[town]='.$this->arConfig["CITY_DELIVERY"]["VALUE"].
			'&take[tent]='.(\CDeliveryPecom::isConfCheckedVal($this->arConfig, 'SERVICE_TAKE_ENABLED') && \CDeliveryPecom::isConfCheckedVal($this->arConfig, 'SERVICE_TAKE_TENT_ENABLED') ? '1' : '0').
			'&take[gidro]='.(\CDeliveryPecom::isConfCheckedVal($this->arConfig, 'SERVICE_TAKE_ENABLED') && \CDeliveryPecom::isConfCheckedVal($this->arConfig, 'SERVICE_TAKE_HYDRO_ENABLED') ? '1' : '0').
			'&take[speed]=0'.
			'&take[moscow]=0'.
			'&deliver[town]='.$locationTo.
			'&deliver[tent]='.(\CDeliveryPecom::isConfCheckedVal($this->arConfig, 'SERVICE_DELIVERY_ENABLED') && \CDeliveryPecom::isConfCheckedVal($this->arConfig, 'SERVICE_DELIVERY_TENT_ENABLED') ? '1' : '0').
			'&delideliver[gidro]='.(\CDeliveryPecom::isConfCheckedVal($this->arConfig, 'SERVICE_DELIVERY_ENABLED') && \CDeliveryPecom::isConfCheckedVal($this->arConfig, 'SERVICE_DELIVERY_HYDRO_ENABLED') ? '1' : '0').
			'&deliver[speed]=0'.
			'&deliver[moscow]=0'.
			'&plombir='.$plombir.
			'&strah='.$insurance.
			'&ashan=0';

		return $result;
	}
}