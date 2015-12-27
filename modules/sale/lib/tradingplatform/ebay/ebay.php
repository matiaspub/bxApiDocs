<?php

namespace Bitrix\Sale\TradingPlatform\Ebay;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\TradingPlatform\Logger;
use Bitrix\Sale\TradingPlatform\Platform;

Loc::loadMessages(__FILE__);

class Ebay extends Platform
{
	//todo: check if token for sftp is expired

	const TRADING_PLATFORM_CODE = "ebay";
	const API_URL = "https://api.ebay.com/ws/api.dll";

	/**
	 * @return Ebay
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function getInstance()
	{
		return parent::getInstance(self::TRADING_PLATFORM_CODE);
	}

	public static function getSftpTokenUrl($accountName)
	{
		return "http://www.1c-bitrix.ru/buy_tmp/ebay/".
			"?action=OAUTH_AUTH".
			"&LICENCE_HASH=".self::getLicenseHash().
			"&BACK_URL=".urlencode((\CMain::IsHTTPS() ? "https://" : "http://").$_SERVER['HTTP_HOST']).
			"&ACCOUNT_NAME=".$accountName;
	}

	public static function getApiTokenUrl()
	{
		return "http://www.1c-bitrix.ru/buy_tmp/ebay/".
			"?action=GET_AUTH_URL&LICENCE_HASH=".self::getLicenseHash().
			"&BACK_URL=".urlencode((\CMain::IsHTTPS() ? "https://" : "http://").$_SERVER['HTTP_HOST']);
	}

	protected static function getLicenseHash()
	{
		return md5(defined("LICENSE_KEY") ? LICENSE_KEY : "DEMO");
	}

	/**
	 * Sets Ebay active.
	 * @return bool
	 * @throws \Bitrix\Main\SystemException
	 */
	public function setActive()
	{
		if($this->isActive())
			return true;

		RegisterModuleDependences("sale", "OnSaleDeductOrder", "sale", '\Bitrix\Sale\TradingPlatform\Ebay\Helper', "onSaleDeductOrder", 100);
		return parent::setActive();
	}

	/**
	 * Sets Ebay inactive.
	 * @return bool
	 */
	public function unsetActive()
	{
		if(!$this->isActive())
			return true;

		UnRegisterModuleDependences("sale", "OnSaleDeductOrder", "sale", '\Bitrix\Sale\TradingPlatform\Ebay\Helper', "onSaleDeductOrder", 100);
		return parent::unsetActive();
	}

	/**
	 * Installs all necessary stuff for Ebay.
	 * @return bool
	 */
	public function install()
	{
		RegisterModuleDependences('main', 'OnEventLogGetAuditTypes', 'sale', '\Bitrix\Sale\TradingPlatform\Ebay\Helper', 'OnEventLogGetAuditTypes');

		$tptAddRes =  \Bitrix\Sale\TradingPlatformTable::add(array(
			"CODE" => $this->getCode(),
			"ACTIVE" => "N",
			"NAME" => Loc::getMessage("SALE_EBAY_NAME"),
			"DESCRIPTION" => Loc::getMessage("SALE_EBAY_DESCRIPTION"),
			"CATALOG_SECTION_TAB_CLASS_NAME" => '\Bitrix\Sale\TradingPlatform\Ebay\CatalogSectionTabHandler',
			"CLASS" => '\Bitrix\Sale\TradingPlatform\Ebay\Ebay'
		));

		$ebay = Ebay::getInstance();
		$catMapEntRes = \Bitrix\Sale\TradingPlatform\MapEntityTable::add(array(
			"TRADING_PLATFORM_ID" => $ebay->getId(),
			"CODE" => "CATEGORY"
		));

		$eventRes = Helper::installEvents();
		$fsRes = Helper::createFeedFileStructure();

		return $tptAddRes->isSuccess()
				&& $catMapEntRes->isSuccess()
				&& $eventRes
				&& $fsRes;
	}

	/**
	 * Sends error description to e-mail
	 * @param string $type Type of error.
	 * @param string $details Error details.
	 * @param string $siteId Site id.
	 * @return bool
	 */
	public function sendErrorMail($type, $details, $siteId)
	{
		if(!isset($this->settings[$siteId]["EMAIL_ERRORS"]) || strlen($this->settings[$siteId]["EMAIL_ERRORS"]) <= 0)
			return false;

		$loggerTypes = Helper::OnEventLogGetAuditTypes();
		$errorType = isset($loggerTypes[$type]) ? $loggerTypes[$type] : $type;

		$fields = array(
			"EMAIL_TO" => $this->settings[$siteId]["EMAIL_ERRORS"],
			"ERROR_TYPE" => $errorType,
			"ERROR_DETAILS" => $details
		);

		$event = new \CEvent;

		return $event->Send("SALE_EBAY_ERROR", $siteId, $fields, "N");
	}

	/**
	 * Log events to system log & sends error to email.
	 * @param int $level Log level of event.
	 * @param string $type Event type.
	 * @param string $itemId Item id.
	 * @param string $description Event description.
	 * @param string $siteId Site id.
	 * @return bool
	 */
	public static function log($level, $type, $itemId, $description, $siteId)
	{
		static $ebay = null;

		if($ebay === null)
		{
			$ebay = self::getInstance();
			$settings = $ebay->getSettings();

			if(isset($settings[$siteId]["LOG_LEVEL"]))
			{
				$logLevel =  $settings[$siteId]["LOG_LEVEL"];
				$ebay->logger->setLevel($logLevel);
			}
		}

		if($level == Logger::LOG_LEVEL_ERROR)
			$ebay->sendErrorMail($type, $description, $siteId);

		return $ebay->addLogRecord($level, $type, $itemId, $description);
	}

	public static function onAfterUpdateShipment(\Bitrix\Main\Event $event, array $additional)
	{
		$data = array();
		$ebay = self::getInstance();
		$settings = $ebay->getSettings();
		$deliveryName = "Other";

		if(
			!empty($settings[$additional["SITE_ID"]]["MAPS"]["SHIPMENT"])
			&& is_array($settings[$additional["SITE_ID"]]["MAPS"]["SHIPMENT"])
		)
		{
			$map = array_flip($settings[$additional["SITE_ID"]]["MAPS"]["SHIPMENT"]);

			if(isset($map[$additional['DELIVERY_ID']]))
			{
				$deliveryName = $map[$additional['DELIVERY_ID']];

				if(substr($deliveryName,0,3) == "RU_")
					$deliveryName = substr($deliveryName, 3);
			}
		}

		if(
			!empty($additional["PARAMS"]["ORDER_LINES"])
			&& is_array($additional["PARAMS"]["ORDER_LINES"])
			&& !empty($additional["PARAMS"]["ORDER_ID"])
		)
		{
			foreach($additional["PARAMS"]["ORDER_LINES"] as $lineId)
			{
				$data[] = array(
					"ORDER_ID" => $additional["PARAMS"]["ORDER_ID"],
					"ORDER_LINE_ITEM_ID" => $lineId,
					"DELIVERY_NAME" => $deliveryName,
					"TRACKING_NUMBER" => $additional['TRACKING_NUMBER']
				);
			}
		}

		if(!empty($data))
		{
			$ebayFeed = \Bitrix\Sale\TradingPlatform\Ebay\Feed\Manager::createFeed("SHIPMENT", $additional["SITE_ID"]);
			$ebayFeed->setSourceData(array($data));
			$ebayFeed->processData();
			\Bitrix\Sale\TradingPlatform\Ebay\Agent::add('SHIPMENT', $additional["SITE_ID"], 1, true);
		}
	}

	public static function getApiUrl()
	{
		return static::API_URL;
	}
}