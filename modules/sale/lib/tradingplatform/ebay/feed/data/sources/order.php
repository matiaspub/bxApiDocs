<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Sources;

use Bitrix\Main\ArgumentNullException;
use \Bitrix\Sale\TradingPlatform\Logger;
use \Bitrix\Sale\TradingPlatform\Ebay\Ebay;

class Order extends DataSource implements \Iterator
{
	protected $remotePath;
	protected $siteId;
	protected $dataFiles = array();
	protected $currentFileIdx = 0;
	protected $startFileIdx = 0;
	protected $orderLatest = "";

	public function __construct($params)
	{
		if(!isset($params["FEED_TYPE"]) || strlen($params["FEED_TYPE"]) <= 0)
			throw new ArgumentNullException("FEED_TYPE");

		$this->remotePath = "/store/".$params["FEED_TYPE"]."/output/".date('M-d-Y', time());
		$this->orderLatest = "/store/".$params["FEED_TYPE"]."/output/order-latest";

		if(!isset($params["SITE_ID"]) || strlen($params["SITE_ID"]) <= 0)
			throw new ArgumentNullException("SITE_ID");

		$this->siteId = $params["SITE_ID"];
		$this->dataFiles = $this->receiveFiles();
	}

	public function setStartPosition($startPos = "")
	{
		if(strlen($startPos) > 0)
			$this->startFileIdx = $startPos;
	}

	public function current()
	{
		$content = file_get_contents($this->dataFiles[$this->currentFileIdx]);
		$skipLength = strtolower(SITE_CHARSET) != 'utf-8' ? 3 : 1;
		$content = substr($content, $skipLength);
		$content = "<?xml version='1.0' encoding='UTF-8'?>".$content;
		return $content;
	}

	public function key()
	{
		return $this->currentFileIdx;
	}

	public function next()
	{
		$this->currentFileIdx++;
	}

	public function rewind()
	{
		$this->currentFileIdx = $this->startFileIdx;
	}

	public function valid()
	{
		return isset($this->dataFiles[$this->currentFileIdx])
			&& $this->dataFiles[$this->currentFileIdx]
			&& \Bitrix\Main\IO\File::isFileExists($this->dataFiles[$this->currentFileIdx]);
	}

	protected function receiveFiles()
	{
		$result = array();
		$timeToKeepFiles = 24;
		$tmpDir = \CTempFile::GetDirectoryName($timeToKeepFiles);
		CheckDirPath($tmpDir);

		$sftp = \Bitrix\Sale\TradingPlatform\Ebay\Helper::getSftp($this->siteId);
		$sftp->connect();

		/*
		$orderFiles = $sftp->getFilesList($this->remotePath);

		foreach($orderFiles as $file)
		{
			if($sftp->downloadFile($this->remotePath."/".$file, $tmpDir.$file))
			{
				$result[] = $tmpDir.$file;
				Ebay::log(Logger::LOG_LEVEL_INFO, "EBAY_DATA_SOURCE_ORDERFILE_RECEIVED", $file, "File received successfully.", $this->siteId);
			}
		}
		*/

		$file = "orderLatest";

		if($sftp->downloadFile($this->orderLatest, $tmpDir.$file))
		{
			$result[] = $tmpDir.$file;
			Ebay::log(Logger::LOG_LEVEL_INFO, "EBAY_DATA_SOURCE_ORDERFILE_RECEIVED", $file, "File received successfully.", $this->siteId);
		}

		return $result;
	}
} 