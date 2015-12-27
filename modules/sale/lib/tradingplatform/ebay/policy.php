<?php
/* NOT FOR RELEASE*/
namespace Bitrix\Sale\TradingPlatform\Ebay;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\Encoding;
use Bitrix\Sale\TradingPlatform\Logger;
use Bitrix\Sale\TradingPlatform\Xml2Array;

class Policy
{
	protected $http;
	protected $authToken = "";
	protected $siteId = "";

	const TYPE_RETURN = 0;
	const TYPE_PAYMENT = 1;
	const TYPE_SHIPPING = 2;
	const URL = "https://svcs.ebay.com/services/selling/v1/SellerProfilesManagementService";

	public function __construct($authToken, $siteId)
	{
		if(!strlen($authToken))
			throw new ArgumentNullException("authToken");

		if(!strlen($siteId))
			throw new ArgumentNullException("siteId");

		$this->authToken = $authToken;
		$this->siteId = $siteId;

		$this->http = new HttpClient(array(
			"version" => "1.1",
			"socketTimeout" => 30,
			"streamTimeout" => 30,
			"redirect" => true,
			"redirectMax" => 5,
		));
	}

	public function getItemsList()
	{
		static $result = null;

		if($result === null)
			$result = \Bitrix\Sale\TradingPlatform\Xml2Array::convert($this->getItems());

		return $result;
	}

	/**
	 * @param int $type Policy::TYPE_RETURN||Policy::TYPE_PAYMENT||Policy::TYPE_SHIPPING
	 * @return array
	 */
	public function getPoliciesNames($type)
	{
		$policiesList = $this->getItemsList();

		if(empty($policiesList))
			return array();

		if($type == self::TYPE_RETURN)
			$policyBranch = $policiesList["returnPolicyProfileList"]["ReturnPolicyProfile"];
		elseif($type == self::TYPE_PAYMENT)
			$policyBranch = $policiesList["paymentProfileList"]["PaymentProfile"];
		elseif($type == self::TYPE_SHIPPING)
			$policyBranch = $policiesList["shippingPolicyProfile"]["ShippingPolicyProfile"];
		else
			throw new ArgumentOutOfRangeException("type");

		if(empty($policyBranch) || !is_array($policyBranch))
			return array();

		$result = array();
		$policies = Xml2Array::normalize($policyBranch);

		foreach($policies as $policy)
		{
			if(
				isset($policy["profileName"])
				&& strlen($policy["profileName"]) > 0
				&& isset($policy["profileId"])
				&& strlen($policy["profileId"]) > 0
			)
			{
				$result[$policy["profileId"]] = $policy["profileName"];
			}
		}

		return $result;
	}

	protected function getItems()
	{
		$data = '<?xml version="1.0" encoding="utf-8"?>
			<getSellerProfilesRequest xmlns="http://www.ebay.com/marketplace/sellings">
			</getSellerProfilesRequest>';

		return $this->sendRequest("getSellerProfiles", $data);
	}

	protected function sendRequest($operationName, $data)
	{
		$this->http->setHeader("X-EBAY-SOA-CONTENT-TYPE", "text/xml");
		$this->http->setHeader("X-EBAY-SOA-GLOBAL-ID", "EBAY-RU");
		$this->http->setHeader("X-EBAY-SOA-SERVICE-NAME", "SellerProfilesManagementService");
		$this->http->setHeader("X-EBAY-SOA-OPERATION-NAME", $operationName); //addSellerProfile getSellerProfiles
		$this->http->setHeader("X-EBAY-SOA-REQUEST-DATA-FORMAT", "XML");
		$this->http->setHeader("X-EBAY-SOA-RESPONSE-DATA-FORMAT", "XML");
		$this->http->setHeader("X-EBAY-SOA-SECURITY-TOKEN", $this->authToken);

		if(strtolower(SITE_CHARSET) != 'utf-8')
			$data = Encoding::convertEncodingArray($data, SITE_CHARSET, 'UTF-8');

		$result = $this->http->post(self::URL, $data);
		$errors = $this->http->getError();

		if (!$result && !empty($errors))
		{
			$strError = "";

			foreach($errors as $errorCode => $errMes)
				$strError .= $errorCode.": ".$errMes;

			Ebay::log(Logger::LOG_LEVEL_INFO, "EBAY_POLICY_REQUEST_ERROR", $operationName, $strError, $this->siteId);
		}
		else
		{
			$status = $this->http->getStatus();

			if ($status != 200)
				Ebay::log(Logger::LOG_LEVEL_INFO, "EBAY_POLICY_REQUEST_HTTP_ERROR", $operationName, 'HTTP error code: '.$status, $this->siteId);

			if(strtolower(SITE_CHARSET) != 'utf-8')
				$result = Encoding::convertEncodingArray($result, 'UTF-8', SITE_CHARSET);
		}

		return $result;
	}
} 