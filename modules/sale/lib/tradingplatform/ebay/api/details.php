<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Api;

use Bitrix\Main\Text\Encoding;
use Bitrix\Sale\TradingPlatform\Helper;
use Bitrix\Sale\TradingPlatform\Xml2Array;

class Details extends Entity
{
	protected function requestData()
	{
		$data = '<?xml version="1.0" encoding="utf-8"?>
			<GeteBayDetailsRequest xmlns="urn:ebay:apis:eBLBaseComponents">
			<RequesterCredentials>
			<eBayAuthToken>'.$this->authToken.'</eBayAuthToken>
			</RequesterCredentials>
		</GeteBayDetailsRequest>';

		$dataXml = $this->apiCaller->sendRequest("GeteBayDetails", $data);

		if(strtolower(SITE_CHARSET) != 'utf-8')
			$dataXml = Encoding::convertEncoding($dataXml, 'UTF-8', SITE_CHARSET);

		$result = Xml2Array::convert($dataXml);
		return $result;
	}

	protected function getData()
	{
		$result = array();
		$ttl = 2592000; //month
		$cacheManager = \Bitrix\Main\Application::getInstance()->getManagedCache();
		$cacheId = "BITRIX_SALE_TRADINGPLATFORM_EBAY_API_DETAILS_".$this->siteId;

		if($cacheManager->read($ttl, $cacheId))
			$result = $cacheManager->get($cacheId);

		if(empty($result))
		{
			$result = $this->requestData();

			if(!empty($result))
				$cacheManager->set($cacheId, $result);
		}

		return $result;
	}

	public function getListShipping()
	{
		static $result = null;

		if($result === null)
		{
			$result = array();
			$data = $this->getData();

			if(isset($data["ShippingServiceDetails"]) && is_array($data["ShippingServiceDetails"]))
			{
				foreach($data["ShippingServiceDetails"] as $service)
				{
					if(!in_array($service["ShippingService"], self::getUsableDeliveries()))
						continue;

					$result[$service["ShippingService"]] = $service["Description"];
				}
			}
		}

		return $result;
	}

	public static function getUsableDeliveries()
	{
		return array(
			'RU_ExpeditedDelivery','RU_ExpeditedMoscowOnly','RU_StandardDelivery','RU_StandardMoscowOnly',
			'RU_EconomyDelivery', 'RU_OvernightDelivery', 'RU_LocalPickup'
		);
	}

	public function getListPayments()
	{
		static $result = null;

		if($result === null)
		{
			$result = array();
			$data = $this->getData();

			if(isset($data["PaymentOptionDetails"]) && is_array($data["PaymentOptionDetails"]))
			{
				$data["PaymentOptionDetails"] = Xml2Array::normalize($data["PaymentOptionDetails"]);

				foreach($data["PaymentOptionDetails"] as $payment)
				{
					if(!in_array($payment["PaymentOption"], self::getUsablePaySystems()))
						continue;

					$result[$payment["PaymentOption"]] = $payment["Description"];
				}
			}
		}

		return $result;
	}

	public static function getUsablePaySystems()
	{
		return array('PayPal');
	}


}