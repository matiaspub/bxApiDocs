<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Sources;

use \Bitrix\Main\ArgumentNullException;
use \Bitrix\Sale\TradingPlatform\Logger;
use \Bitrix\Sale\TradingPlatform\Ebay\Ebay;
use \Bitrix\Sale\TradingPlatform\Ebay\Feed\ResultsTable;

class Results extends DataSource implements \Iterator
{
	protected $siteId;
	protected $feedsToCheck = array();
	protected $resultFileContent = "";
	protected $remotePathTmpl = "";
	protected $filter = array();

	public function __construct($params)
	{
		if(!isset($params["SITE_ID"]) || strlen($params["SITE_ID"]) <= 0)
			throw new ArgumentNullException("SITE_ID");

		if(!isset($params["REMOTE_PATH_TMPL"]) || strlen($params["REMOTE_PATH_TMPL"]) <= 0)
			throw new ArgumentNullException("REMOTE_PATH_TMPL");

		if(!isset($params["FILTER"]))
			throw new ArgumentNullException("FILTER");

		$this->siteId = $params["SITE_ID"];
		$this->remotePathTmpl = $params["REMOTE_PATH_TMPL"];
		$this->filter = $params["FILTER"];
	}

	public function current()
	{
		return array(
			"RESULT_ID" => $this->key(),
			"CONTENT" => $this->resultFileContent
		);
	}

	public function key()
	{
		return key($this->feedsToCheck);
	}

	public function next()
	{
		$feedData = next($this->feedsToCheck);

		if($feedData !== false)
			$this->resultFileContent = $this->getFileContent($feedData);
	}

	public function rewind()
	{
		$this->feedsToCheck = array();

		$res = ResultsTable::getList(array(
			'filter' => $this->filter
		));

		while($feed = $res->fetch())
			$this->feedsToCheck[$feed["ID"]] = $feed;

		$feedData = reset($this->feedsToCheck);

		if($feedData !== false)
			$this->resultFileContent = $this->getFileContent($feedData);
	}

	public function valid()
	{
		return current($this->feedsToCheck) !== false;
	}

	protected function createRemotePath($feedData)
	{
		return str_replace(
			array(
				"##FEED_TYPE##",
				"##UPLOAD_DATE##"
			),
			array(
				$feedData["FEED_TYPE"],
				$feedData["UPLOAD_TIME"]->format("M-d-Y")
			),
			$this->remotePathTmpl
		);
	}

	protected function getFileContent($feedData)
	{
		$result = "";
		$timeToKeepFiles = 24;
		$tmpDir = \CTempFile::GetDirectoryName($timeToKeepFiles);
		CheckDirPath($tmpDir);

		$sftp = \Bitrix\Sale\TradingPlatform\Ebay\Helper::getSftp($this->siteId);
		$sftp->connect();
		$remotePath = $this->createRemotePath($feedData);
		$files = $sftp->getFilesList($remotePath);

		foreach($files as $file)
		{
			if(!strstr($file, $feedData["FILENAME"]))
				continue;

			if($sftp->downloadFile($remotePath."/".$file, $tmpDir.$file))
			{
				$result = file_get_contents($tmpDir.$file);
				Ebay::log(Logger::LOG_LEVEL_INFO, "EBAY_DATA_SOURCE_RESULTS_RECEIVED", $file, "File received successfully.", $this->siteId);
			}
			else
			{
				Ebay::log(Logger::LOG_LEVEL_ERROR, "EBAY_DATA_SOURCE_RESULTS_ERROR", $tmpDir.$file, "Can't receive file content.", $this->siteId);
			}
		}

		return $result;
	}
} 