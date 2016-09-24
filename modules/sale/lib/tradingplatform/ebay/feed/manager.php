<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed;

use \Bitrix\Main\SystemException;
use \Bitrix\Sale\TradingPlatform\Timer;
use \Bitrix\Sale\TradingPlatform\Logger;
use \Bitrix\Sale\TradingPlatform\Ebay\Ebay;

class Manager
{
	public static function createSftpQueue($feedType, $siteId, Timer $timer = null)
	{
		$params = array(
			"SITE_ID" => $siteId,
			"TIMER" => $timer
		);

		switch($feedType)
		{
			case 'PRODUCT':
				$params["FEED_TYPE"] =  "product";
				$params["COVER_TAG"] =  "ListingArray";
				$params["SCHEMA_FILE_NAME"] =  "Product.xsd";
				break;

			case 'INVENTORY':
				$params["FEED_TYPE"] =  "inventory";
				$params["COVER_TAG"] =  "InventoryArray";
				$params["SCHEMA_FILE_NAME"] =  "Inventory.xsd";
				break;

			case 'ORDER_ACK':
				$params["FEED_TYPE"] =  "order-ack";
				$params["COVER_TAG"] =  "OrderAckArray";
				$params["SCHEMA_FILE_NAME"] =  "OrderAck.xsd";
				break;

			case 'SHIPMENT':
				$params["FEED_TYPE"] =  "shipment";
				$params["COVER_TAG"] =  "ShipmentArray";
				$params["SCHEMA_FILE_NAME"] =  "Shipment.xsd";
				break;

			case 'IMAGE':
				$params["FEED_TYPE"] =  "image";
				$params["COVER_TAG"] =  "Images";
				$params["SCHEMA_FILE_NAME"] =  "Image.xsd";
				break;

			default:
				throw new SystemException("Unknown type of feed \"".$feedType."\". ".__METHOD__);
				break;
		}
		return new Data\Processors\SftpQueue($params);
	}

	/**
	 * @param $feedType
	 * @param $siteId
	 * @param int $timeLimit
	 * @return Feed|bool
	 * @throws SystemException
	 */
	public static function createFeed($feedType, $siteId, $timeLimit = 0)
	{
		$timer = new Timer($timeLimit);
		$feepParams = array(
			"TIMER" => new $timer
		);

		switch($feedType)
		{
			case 'PRODUCT':
				$feepParams["DATA_SOURCE"] =  new Data\Sources\Product(array("SITE_ID" => $siteId));
				$feepParams["DATA_CONVERTER"] = new Data\Converters\Product(array("SITE_ID" => $siteId));
				$feepParams["DATA_PROCESSOR"] = self::createSftpQueue($feedType, $siteId, $timer);
				break;

			case 'INVENTORY':
				$feepParams["DATA_SOURCE"] =  new Data\Sources\Product(array("SITE_ID" => $siteId));
				$feepParams["DATA_CONVERTER"] = new Data\Converters\Inventory;
				$feepParams["DATA_PROCESSOR"] = self::createSftpQueue($feedType, $siteId, $timer);
				break;

			case 'ORDER':
				$feepParams["DATA_SOURCE"] =  new Data\Sources\Order(array(
					"FEED_TYPE" => "order",
					"SCHEMA_FILE_NAME" => "Order.xsd",
					"SITE_ID" => $siteId
				));
				$feepParams["DATA_CONVERTER"] = new Data\Converters\Order;
				$feepParams["DATA_PROCESSOR"] = new Data\Processors\Order(array("SITE_ID" => $siteId));
				break;

			case 'ORDER_ACK':
				$feepParams["DATA_SOURCE"] =  new Data\Sources\SimpleArray;
				$feepParams["DATA_CONVERTER"] = new Data\Converters\OrderAck;
				$feepParams["DATA_PROCESSOR"] = self::createSftpQueue($feedType, $siteId, $timer);
				break;

			case 'SHIPMENT':
				$feepParams["DATA_SOURCE"] =  new Data\Sources\SimpleArray;
				$feepParams["DATA_CONVERTER"] = new Data\Converters\Shipment;
				$feepParams["DATA_PROCESSOR"] = self::createSftpQueue($feedType, $siteId, $timer);
				break;

			case 'IMAGE':
				$feepParams["DATA_SOURCE"] =  new Data\Sources\Product(array("SITE_ID" => $siteId));
				$feepParams["DATA_CONVERTER"] = new Data\Converters\Image(array("SITE_ID" => $siteId));
				$feepParams["DATA_PROCESSOR"] = self::createSftpQueue($feedType, $siteId, $timer);
				break;

			case 'PROCESS_RESULT':
				$feepParams["DATA_SOURCE"] =  new Data\Sources\Results(array(
					"SITE_ID" => $siteId,
					"REMOTE_PATH_TMPL" => "/store/##FEED_TYPE##/log/##UPLOAD_DATE##",
					"FILTER" => array(
						"PROCESSING_REQUEST_ID" => ""
					)
				));
				$feepParams["DATA_CONVERTER"] = new Data\Converters\ProcessResult;
				$feepParams["DATA_PROCESSOR"] = new Data\Processors\ProcessResult;
				break;

			case 'RESULTS':
				$feepParams["DATA_SOURCE"] =  new Data\Sources\Results(array(
					"SITE_ID" => $siteId,
					"REMOTE_PATH_TMPL" => "/store/##FEED_TYPE##/output/##UPLOAD_DATE##",
					"FILTER" => array(
						"RESULTS" => ""
					)
				));
				$feepParams["DATA_CONVERTER"] = new Data\Converters\Results;
				$feepParams["DATA_PROCESSOR"] = new Data\Processors\Results(array("SITE_ID" => $siteId));;
				break;

			default:
				throw new SystemException("Unknown type of feed \"".$feedType."\". ".__METHOD__);
				break;
		}

		$feed = new Feed($feepParams);
		Ebay::log(Logger::LOG_LEVEL_DEBUG, "EBAY_FEED_CREATED", $feedType, "Feed: ".$feedType.", site: ".$siteId, $siteId);
		return $feed;
	}
} 