<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

	/**
	 * Class ResultsTable
	 *
	 * Fields:
	 * <ul>
	 * <li> ID int mandatory
	 * <li> FILENAME string(255) mandatory
	 * <li> FEED_TYPE string(255) mandatory
	 * <li> UPLOAD_TIME datetime mandatory
	 * <li> PROCESSING_REQUEST_ID string(50) optional
	 * <li> PROCESSING_RESULT string(100) optional
	 * <li> RESULTS string optional
	 * </ul>
	 *
	 * @package Bitrix\Sale\TradingPlatform\Ebay\Feed
	 **/

class ResultsTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_tp_ebay_fr';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_FEED_RESULTS_ENTITY_ID_FIELD'),
			),
			'FILENAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateFilename'),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_FEED_RESULTS_ENTITY_FILENAME_FIELD'),
			),
			'FEED_TYPE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateFeedType'),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_FEED_RESULTS_ENTITY_FEED_TYPE_FIELD'),
			),
			'UPLOAD_TIME' => array(
				'data_type' => 'datetime',
				'required' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_FEED_RESULTS_ENTITY_UPLOAD_TIME_FIELD'),
			),
			'PROCESSING_REQUEST_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateProcessingRequestId'),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_FEED_RESULTS_ENTITY_PROCESSING_REQUEST_ID_FIELD'),
			),
			'PROCESSING_RESULT' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateProcessingResult'),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_FEED_RESULTS_ENTITY_PROCESSING_RESULT_FIELD'),
			),
			'RESULTS' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_FEED_RESULTS_ENTITY_RESULTS_FIELD'),
			)
		);
	}
	public static function validateProcessingIsSuccess()
	{
		return array(
			new Entity\Validator\Length(null, 1),
		);
	}
	public static function validateFilename()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateFeedType()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
	public static function validateProcessingRequestId()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
	public static function validateProcessingResult()
	{
		return array(
			new Entity\Validator\Length(null, 100),
		);
	}
} 