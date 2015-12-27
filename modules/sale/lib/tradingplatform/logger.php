<?php

namespace Bitrix\Sale\TradingPlatform;

use \Bitrix\Main\SystemException;

/**
 * Class Logger
 * Recoring operations for trading platforms.
 * @package Bitrix\Sale\TradingPlatform
 */
class Logger
{
	const LOG_LEVEL_DISABLE = 0;
	const LOG_LEVEL_ERROR = 10;
	const LOG_LEVEL_INFO = 20;
	const LOG_LEVEL_DEBUG = 30;

	protected $logLevel = self::LOG_LEVEL_ERROR;
	protected $severities = array();

	/**
	 * Constructor
	 * @param int $logLevel Log level..
	 */
	public function __construct($logLevel = self::LOG_LEVEL_ERROR)
	{
		$this->setLevel($logLevel);

		$this->severities = array(
			self::LOG_LEVEL_ERROR => "ERROR",
			self::LOG_LEVEL_INFO => "INFO",
			self::LOG_LEVEL_DEBUG => "DEBUG"
		);
	}

	/**
	 * @param int $level Record level.
	 * @param string $type Record type.
	 * @param string $itemId Identifier of record object.
	 * @param string $description Record description.
	 * @return bool
	 * @throws \Bitrix\Main\SystemException
	 */
	public function addRecord($level, $type, $itemId, $description)
	{

		if($this->logLevel < $level || $level == static::LOG_LEVEL_DISABLE)
			return false;

		if(!array_key_exists($level, $this->severities))
			throw new SystemException("Unknown type of severity: ".$level.". ".__METHOD__);

		$eventLog = new \CEventLog;

		return $eventLog->Add(array(
			"SEVERITY" => $this->severities[$level],
			"AUDIT_TYPE_ID" => $type,
			"MODULE_ID" => "sale",
			"ITEM_ID" => $itemId,
			"DESCRIPTION" => $description,
		));
	}

	/**
	 * Sets log level
	 * @param int $logLevel Log level.
	 */
	public function setLevel($logLevel)
	{
		$this->logLevel = $logLevel;
	}
} 