<?php

namespace Bitrix\Sale\TradingPlatform\Ebay;

use Bitrix\Main\Error;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\TradingPlatform\Sftp;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\TradingPlatform\Ebay\Feed\Manager;

Loc::loadMessages(__FILE__);

/**
 * Class Helper
 * Useful methods to work with ebay.
 * @package Bitrix\Sale\TradingPlatform\Ebay
 */
class Helper
{
	/**
	 * Checks all necessary extensions etc.
	 * @return Result
	 * @throws SystemException
	 */
	public static function checkEnveronment()
	{
		$result = new Result();

		if(!extension_loaded('ssh2'))
			$result->addError( new Error(Loc::getMessage("SALE_EBAY_HLP_CHECK_ERROR_SSH2")));

		if(!extension_loaded('SimpleXML'))
			$result->addError( new Error(Loc::getMessage("SALE_EBAY_HLP_CHECK_ERROR_SIMPLEXML")));

		return $result;
	}

	/**
	 * @return string Path to SFTP exchange folders.
	 */
	public static function getSftpPath()
	{
		return 	$_SERVER["DOCUMENT_ROOT"]."/bitrix/tradingplatforms/ebay/sftp";
	}

	/**
	 * Creates filestructure for information exchange via sftp.
	 * @return bool
	 */
	public static function createFeedFileStructure()
	{
		$sftpDir = self::getSftpPath();
		$directory = new Directory($sftpDir);

		if(!$directory->isExists())
			$directory->create();

		foreach(array("product", "inventory", "image", "order-ack", "shipment") as $feedType)
		{
			$feedDir = new Directory($sftpDir."/".$feedType);

			if(!$feedDir->isExists())
				$feedDir->create();

			foreach(array("xml", "tmp", "zip") as $stage)
			{
				$stageDir = new Directory($sftpDir."/".$feedType."/".$stage);

				if(!$stageDir->isExists())
					$stageDir->create();
			}
		}

		return true;
	}

	/**
	 * Creates events for sending e-mail.
	 * @return bool
	 */
	public static function installEvents()
	{
		$dbEvent = \CEventMessage::GetList($b="ID", $order="ASC", Array("EVENT_NAME" => "SALE_EBAY_ERROR"));

		if(!($dbEvent->Fetch()))
		{
			$langs = \CLanguage::GetList(($b=""), ($o=""));
			while($lang = $langs->Fetch())
			{
				$lid = $lang["LID"];
				$obEventType = new \CEventType;
				$obEventType->Add(array(
					"EVENT_NAME"    => "SALE_EBAY_ERROR",
					"NAME"          => Loc::getMessage("SALE_EBAY_HLP_EVNT_TYPE_ERROR"),
					"LID"       => $lid,
					"DESCRIPTION"   =>"
					#ERROR_TYPE# - ".Loc::getMessage("SALE_EBAY_HLP_EVNT_TYPE_ERROR_TYPE")."
					#ERROR_DETAILS# - ".Loc::getMessage("SALE_EBAY_HLP_EVNT_TYPE_ERROR_DETAIL")."
					#EMAIL_FROM# - ".Loc::getMessage("SALE_EBAY_HLP_EVNT_TYPE_FROM")."
					#EMAIL_TO# - ".Loc::getMessage("SALE_EBAY_HLP_EVNT_TYPE_TO")."
					#BCC# - ".Loc::getMessage("SALE_EBAY_HLP_EVNT_TYPE_BCC")
				));

				$arSites = array();
				$sites = \CSite::GetList(($b=""), ($o=""), Array("LANGUAGE_ID"=>$lid));
				while ($site = $sites->Fetch())
					$arSites[] = $site["LID"];

				if(count($arSites) > 0)
				{
					$arr = array();
					$arr["ACTIVE"]      = "Y";
					$arr["EVENT_NAME"]  = "SALE_EBAY_ERROR";
					$arr["LID"]     = $arSites;
					$arr["EMAIL_FROM"]  = "#DEFAULT_EMAIL_FROM#";
					$arr["EMAIL_TO"]    = "#EMAIL_TO#";
					$arr["BCC"]         = "#BCC#";
					$arr["SUBJECT"]     = "#SITE_NAME# ".Loc::getMessage("SALE_EBAY_EVNT_MSG_SBUJ']").".";
					$arr["BODY_TYPE"]   = "text";
					$arr["MESSAGE"]     =
						Loc::getMessage("SALE_EBAY_EVNT_MSG_INFO_SITE")." #SITE_NAME#.\n\n".
						Loc::getMessage("SALE_EBAY_EVNT_MSG").":\n\n".
						"#ERROR_TYPE#\n\n".
						"#ERROR_DETAILS#";

					$obTemplate = new \CEventMessage;
					$obTemplate->Add($arr);
				}
			}
		}

		return true;
	}

	/**
	 * @return array Ebay order statuses.
	 */
	public static function getEbayOrderStatuses()
	{
		return array(
			"Active",
			"Completed",
			"Canceled",
			"Inactive"
		);
	}

	/**
	 * Sends tracknumber to ebay.
	 * @param string $orderId Order id.
	 * @param string $val "Y"|"N"
	 * @return bool
	 */
	public static function onSaleDeductOrder($orderId, $val)
	{
		if($val != "Y")
			return false;

		$order = \CSaleOrder::GetByID($orderId);

		if(strlen($order["XML_ID"]) <= 0 || substr($order["XML_ID"], 0, 4) != Ebay::TRADING_PLATFORM_CODE)
			return false;

		$ebayOrderId = substr($order["XML_ID"], strlen(Ebay::TRADING_PLATFORM_CODE)+1);

		$shipmentInfo =	array();
		$trackingInfo = array();

		if(strlen($order["TRACKING_NUMBER"]) > 0)
		{
			$ebayDelivery = "Other";
			$ebay = \Bitrix\Sale\TradingPlatform\Ebay\Ebay::getInstance();
			$settings = $ebay->getSettings();

			if(isset($settings[$order["LID"]]["DELIVERY"]) && is_array($settings[$order["LID"]]["DELIVERY"]))
			{
				foreach($settings[$order["LID"]]["DELIVERY"] as $eDelivery => $bDelivery)
				{
					if($bDelivery == $order["DELIVERY_ID"])
					{
						$ebayDelivery = $eDelivery;
						break;
					}
				}
			}

			$trackingInfo = array(
				"SERVICE" => $ebayDelivery,
				"NUMBER" => $order["TRACKING_NUMBER"]
			);
		}

		$orderLineItemIds = array();

		$dbBasket = \CSaleBasket::GetList(
			array(),
			array("ORDER_ID" => $orderId),
			false,
			false,
			array("XML_ID")
		);

		while ($arBasket = $dbBasket->GetNext())
			$orderLineItemIds[] = $arBasket["XML_ID"];

		foreach($orderLineItemIds as $orderLineItemId)
		{
			$tmpShipmentInfo =	array(
				"ORDER_ID" => $ebayOrderId,
				"ORDER_LINE_ITEM_ID" => $orderLineItemId,
			);

			if(!empty($trackingInfo))
				$tmpShipmentInfo["TRACKING"] = $trackingInfo;

			$shipmentInfo[] = $tmpShipmentInfo;
		}

		$ebayFeed = Manager::createFeed("SHIPMENT", $order["LID"]);
		$ebayFeed->setSourceData($shipmentInfo);
		$ebayFeed->exchangeData("");

		return true;
	}

	/**
	 * @return array Audit types.
	 * Before using it needs to execute:
	 * RegisterModuleDependences('main', 'OnEventLogGetAuditTypes', 'sale', 'Bitrix\Sale\TradingPlatform\Ebay\Ebay', 'OnEventLogGetAuditTypes');
	 */
	public static function OnEventLogGetAuditTypes()
	{
		$prefix = 'eBay: ';

		$result = array(
			"EBAY_FEED_ERROR" => Loc::getMessage("SALE_EBAY_AT_FEED_ERROR"),
			"EBAY_AGENT_FEED_STARTED" => Loc::getMessage("SALE_EBAY_AT_AGENT_FEED_STARTED"),
			"EBAY_FEED_CREATED" => Loc::getMessage("SALE_EBAY_AT_FEED_CREATED"),
			"EBAY_DATA_PROCESSOR_ORDER_ERROR" => Loc::getMessage("SALE_EBAY_AT_DATA_PROCESSOR_ORDER_ERROR"),
			"EBAY_DATA_PROCESSOR_ORDER_PROCESSED" => Loc::getMessage("SALE_EBAY_AT_DATA_PROCESSOR_ORDER_PROCESSED"),
			"EBAY_DATA_PROCESSOR_SFTPQUEUE_SEND" => Loc::getMessage("SALE_EBAY_AT_DATA_PROCESSOR_SFTPQUEUE_SEND"),
			"EBAY_DATA_SOURCE_ORDERFILE_RECEIVED" => Loc::getMessage("SALE_EBAY_AT_DATA_SOURCE_ORDERFILE_RECEIVED"),
			"EBAY_DATA_PROCESSOR_ORDER_CREATED" => Loc::getMessage("SALE_EBAY_AT_DATA_PROCESSOR_ORDER_CREATED"),
			"EBAY_DATA_PROCESSOR_ORDER_PROCESSING" => Loc::getMessage("SALE_EBAY_AT_DATA_PROCESSOR_ORDER_PROCESSING"),
			"EBAY_DATA_SOURCE_RESULTS_RECEIVED" => Loc::getMessage("SALE_EBAY_AT_DATA_SOURCE_RESULTS_RECEIVED"),
			"EBAY_DATA_SOURCE_RESULTS_ERROR" => Loc::getMessage("SALE_EBAY_AT_DATA_SOURCE_RESULTS_ERROR"),
			"EBAY_AGENT_ADDING_RESULT" => Loc::getMessage("SALE_EBAY_AT_AGENT_ADDING_RESULT"),
			"EBAY_FEED_RESULTS_ERROR" => Loc::getMessage("SALE_EBAY_AT_FEED_RESULTS_ERROR"),
			"EBAY_POLICY_REQUEST_ERROR" => Loc::getMessage("SALE_EBAY_AT_POLICY_REQUEST_ERROR"),
			"EBAY_POLICY_REQUEST_HTTP_ERROR" => Loc::getMessage("SALE_EBAY_AT_POLICY_REQUEST_HTTP_ERROR"),
			"EBAY_DATA_PROCESSOR_ORDER_SKIPPED" => Loc::getMessage("SALE_EBAY_AT_DATA_PROCESSOR_ORDER_SKIPPED"),
			"EBAY_DATA_PROCESSOR_ORDER_ALREADY_EXIST" => Loc::getMessage("SALE_EBAY_AT_DATA_PROCESSOR_ORDER_ALREADY_EXIST"),
			"EBAY_DATA_PROCESSOR_ORDER_PROCESSING_TRANSACTION_ITEM_NOT_FOUND" => Loc::getMessage("SALE_EBAY_AT_DATA_PROCESSOR_ORDER_PROCESSING_TR_NOT_FOUND"),
			"EBAY_DATA_PROCESSOR_ORDER_PROCESSING_TRANSACTION_ITEM_SKU_NOT_FOUND" => Loc::getMessage("SALE_EBAY_AT_DATA_PROCESSOR_ORDER_PROCESSING_TRANSACTION_ITEM_SKU_NOT_FOUND"),
			"EBAY_DATA_PROCESSOR_ORDER_TRANSACTION_ITEM_CREATE_ERROR" => Loc::getMessage("SALE_EBAY_AT_DATA_PROCESSOR_ORDER_TRANSACTION_ITEM_CREATE_ERROR"),
			"EBAY_DATA_PROCESSOR_ORDER_CREATE_ERROR_SET_BASKET" => Loc::getMessage("SALE_EBAY_AT_DATA_PROCESSOR_ORDER_CREATE_ERROR_SET_BASKET"),
			"EBAY_DATA_PROCESSOR_ORDER_CANCELING_ERROR" => Loc::getMessage("SALE_EBAY_AT_DATA_PROCESSOR_ORDER_CANCELING_ERROR"),
			"EBAY_DATA_PROCESSOR_ORDER_DEDUCTIOING_ERROR" => Loc::getMessage("SALE_EBAY_AT_DATA_PROCESSOR_ORDER_DEDUCTIOING_ERROR"),
			"EBAY_DATA_PROCESSOR_ORDER_CHANGE_STATUS_ERROR" => Loc::getMessage("SALE_EBAY_AT_DATA_PROCESSOR_ORDER_CHANGE_STATUS_ERROR"),
			"EBAY_DATA_PROCESSOR_ORDER_SAVE_ERROR" => Loc::getMessage("SALE_EBAY_AT_DATA_PROCESSOR_ORDER_SAVE_ERROR"),
			"EBAY_DATA_PROCESSOR_ORDER_CORR_SAVE_ERROR" => Loc::getMessage("SALE_EBAY_AT_DATA_PROCESSOR_ORDER_CORR_SAVE_ERROR"),
			"EBAY_DATA_PROCESSOR_SFTPQUEUE_FLUSHING" => Loc::getMessage("SALE_EBAY_AT_DATA_PROCESSOR_SFTPQUEUE_FLUSHING"),
		);

		array_walk($result, function(&$value, $key, $prefix)	{
				$value = $prefix.$value;
			},
			$prefix
		);

		return $result;
	}

	/**
	 * @param string $siteId
	 * @return \Bitrix\Sale\TradingPlatform\Sftp
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function getSftp($siteId)
	{
		if(strlen($siteId) <= 0)
			throw new ArgumentNullException("siteId");

		static $sftp = array();

		if(!isset($sftp[$siteId]))
		{
			$ebay = \Bitrix\Sale\TradingPlatform\Ebay\Ebay::getInstance();
			$settings = $ebay->getSettings();

			$sftp[$siteId] = new Sftp(
				$settings[$siteId]["SFTP_LOGIN"],
				$settings[$siteId]["SFTP_PASS"]
			);
		}

		return $sftp[$siteId];
	}

	/**
	 * Returns category variations.
	 * If variations no found in table get them through API and saves to table.
	 * @param string $ebayCategoryId Ebay category id.
	 * @param string $siteId Sitte id.
	 * @param bool $localInfoOnly Get it from Ebay site if not found in table.
	 * @return array
	 */
	public static function getEbayCategoryVariations($ebayCategoryId, $siteId, $localInfoOnly = false)
	{
		$result = array();

		$categoriesVarResult = CategoryVariationTable::getList( array(
			'select' => array('ID', 'NAME', 'REQUIRED'),
			'order' => array('NAME' =>'ASC'),
			'filter' => array("=CATEGORY_ID" => $ebayCategoryId),
			'group' => array('NAME')
		));

		while($var = $categoriesVarResult->fetch())
			$result[$var['ID']] = $var;

		if(empty($result) && !$localInfoOnly)
		{
			$categories = new \Bitrix\Sale\TradingPlatform\Ebay\Api\Categories($siteId);
			$rfrCount = $categories->refreshVariationsTableData(array($ebayCategoryId));

			if(intval($rfrCount) > 0)
				$result = self::getEbayCategoryVariations($ebayCategoryId, $siteId, true);
		}

		return $result;
	}
} 