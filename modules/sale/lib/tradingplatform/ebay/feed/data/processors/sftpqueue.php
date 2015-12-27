<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Processors;

use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\SystemException;
use \Bitrix\Sale\TradingPlatform\Sftp;
use \Bitrix\Main\ArgumentNullException;
use \Bitrix\Sale\TradingPlatform\Logger;
use \Bitrix\Sale\TradingPlatform\Ebay\Ebay;
use \Bitrix\Sale\TradingPlatform\Ebay\Feed\QueueTable;
use \Bitrix\Sale\TradingPlatform\Ebay\Feed\ResultsTable;

class SftpQueue extends DataProcessor
{
	// todo: check if the record alredy exist

	protected $feedType;
	protected $coverTag = null;
	protected $schemeFileName = null;
	protected $fileNameSalt;
	protected $remotePath;
	protected $siteId;
	protected $timer = null;
	protected $path;

	public function __construct(array $params)
	{
		if(!isset($params["FEED_TYPE"]) || strlen($params["FEED_TYPE"]) <= 0)
			throw new ArgumentNullException("FEED_TYPE");

		if($this->feedType == "ORDER_ACK")
			$this->feedType = "order-ack";
		else
			$this->feedType = strtolower($params["FEED_TYPE"]);

		if(!isset($params["SITE_ID"]) || strlen($params["SITE_ID"]) <= 0)
			throw new ArgumentNullException("SITE_ID");

		$this->siteId = $params["SITE_ID"];

		if(isset($params["COVER_TAG"]) && strlen($params["COVER_TAG"]) > 0)
			$this->coverTag = $params["COVER_TAG"];

		if(isset($params["SCHEMA_FILE_NAME"]))
			$this->schemeFileName = $params["SCHEMA_FILE_NAME"];

		if(isset($params["TIMER"]))
			$this->timer = $params["TIMER"];

		$this->fileNameSalt = mktime();
		$this->remotePath = "/store/".$this->feedType;
		$this->path = \Bitrix\Sale\TradingPlatform\Ebay\Helper::getSftpPath()."/".$this->feedType;
	}

	protected function prepareFile($file)
	{
		$res = file_put_contents($file, '<?xml version="1.0" encoding="UTF-8"?>'."\n");

		if(!$res)
			throw new SystemException("Can't flush data feed \"".$this->feedType."\" to file ".$file);

		if($this->coverTag !== null)
			file_put_contents($file, "<".$this->coverTag.">\n", FILE_APPEND);
	}

	protected function flushData()
	{
		$fileXml = "";

		$feedDataRes = QueueTable::getList(array(
			"filter" => array(
				"FEED_TYPE" => $this->feedType
			)
		));

		$filePrepared = false;

		while($feedData = $feedDataRes->fetch())
		{
			if(!$filePrepared)
			{
				$fileXml = $this->path."/xml/".$this->feedType."_".$this->fileNameSalt.".xml";
				$this->prepareFile($fileXml);
				$filePrepared = true;
			}

			Ebay::log(Logger::LOG_LEVEL_DEBUG, "EBAY_DATA_PROCESSOR_SFTPQUEUE_FLUSHING", $this->feedType, print_r($feedData["DATA"],true), $this->siteId);

			if(strtolower(SITE_CHARSET) != 'utf-8')
				$feedData["DATA"] = \Bitrix\Main\Text\Encoding::convertEncoding($feedData["DATA"], SITE_CHARSET, 'UTF-8');

			$res = file_put_contents($fileXml, $feedData["DATA"], FILE_APPEND);

			if($res !== false)
				QueueTable::delete($feedData["ID"]);
			else
				throw new SystemException("Can't flush data feed \"".$this->feedType."\" to file ".$fileXml);
		}

		if($this->coverTag !== null && $filePrepared)
			file_put_contents($fileXml, "</".$this->coverTag.">\n", FILE_APPEND);

		return $fileXml;
	}

	public function process($data)
	{
		return $this->addData($data);
	}

	public function addData($data)
	{
		$result = QueueTable::add(array(
			"FEED_TYPE" => $this->feedType,
			"DATA" => $data
		));

		return $result->isSuccess();
	}

	public function sendData()
	{
		$xmlFile = $this->flushData();

		if(!$xmlFile)
			return false;

		$tmpFile = $this->packData($xmlFile);
		$zipFile = new \Bitrix\Main\IO\File($tmpFile);
		$zipFile->rename($this->path."/zip/".$this->feedType."_".$this->fileNameSalt.".zip");
		$this->sendDataSftp();

		$checkResultsInterval = 5; //min.
		\Bitrix\Sale\TradingPlatform\Ebay\Agent::add('RESULTS', $this->siteId, $checkResultsInterval, true);

		return true;
	}

	protected function packData($xmlFile)
	{
		$tmpDir = $this->path."/tmp";
		$archiveName = $tmpDir."/".$this->feedType."_".$this->fileNameSalt.".zip";
		$oArchiver = \CBXArchive::GetArchive($archiveName, "ZIP");
		$oArchiver->SetOptions(array(
			"REMOVE_PATH" => $this->path."/xml",
			"ADD_PATH" => $this->feedType
		));

		if($oArchiver->Pack($xmlFile))
			\Bitrix\Main\IO\File::deleteFile($xmlFile);

		return $archiveName;
	}

	protected function sendDataSftp()
	{
		$directory = new \Bitrix\Main\IO\Directory($this->path."/zip");

		if(!$directory->isExists())
			throw new SystemException("Directory".$this->path."/zip does not exist! ".__METHOD__);

		$filesToSend  = $directory->getChildren();

		if(empty($filesToSend))
			return false;

		$sftp = \Bitrix\Sale\TradingPlatform\Ebay\Helper::getSftp($this->siteId);
		$sftp->connect();

		for($i = 0; $i < count($filesToSend); $i++)
		{
			$directoryEntry = $filesToSend[$i];
			$localPath = $directoryEntry->getPath();

			if((!($directoryEntry instanceof \Bitrix\Main\IO\File)) || GetFileExtension($localPath) != "zip")
				continue;

			$remote = $this->remotePath."/".$directoryEntry->getName();

			while(!$this->checkOuterConditions($sftp))
			{
				if($this->timer !== null && !$this->timer->check(15))
					return false;

				sleep(10);
			}

			if($sftp->uploadFile($localPath, $remote))
			{
				$directoryEntry->delete();
				ResultsTable::add(array(
					"FILENAME" => $directoryEntry->getName(),
					"FEED_TYPE" => $this->feedType,
					"UPLOAD_TIME" => DateTime::createFromTimestamp(time())
				));
				Ebay::log(Logger::LOG_LEVEL_INFO, "EBAY_DATA_PROCESSOR_SFTPQUEUE_SEND", $remote, "File sent successfully.", $this->siteId);
			}
		}

		return true;
	}

	protected function checkOuterConditions($sftp)
	{
		$files = $sftp->getFilesList($this->remotePath);

		if(!empty($files))
			return false;

		if($this->feedType == "inventory" || $this->feedType == "image")
		{
			$filesProd = $sftp->getFilesList("/store/product");
			$filesProdInProc = $sftp->getFilesList("/store/product/inprocess");

			if(!empty($filesProd) || !empty($filesProdInProc))
				return false;
		}

		return true;
	}
}
