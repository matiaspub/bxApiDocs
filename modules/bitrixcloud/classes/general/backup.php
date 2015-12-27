<?php
IncludeModuleLangFile(__FILE__);
class CBitrixCloudBackup
{
	private static $instance = /*.(CBitrixCloudBackup).*/ null;
	private $init = false;
	private $infoXML = /*.(CDataXML).*/ null;
	private $quota = 0.0;
	private $files = /*.(array[int][string]string).*/ array();
	private $total_size = 0.0;
	private $last_backup_time = 0;
	/**
	 * Returns proxy class instance (singleton pattern)
	 *
	 * @return CBitrixCloudBackup
	 *
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
			self::$instance = new CBitrixCloudBackup;

		return self::$instance;
	}
	/**
	 * Loads and parses xml
	 *
	 * @param bool $force
	 * @return bool
	 *
	 */
	private function _getInformation($force = false)
	{
		if($this->init && !$force)
			return true;
		$this->init = true;

		try
		{
			$web_service = new CBitrixCloudBackupWebService();
			$web_service->setTimeout(10);
			$this->infoXML = $web_service->actionGetInformation();
		}
		catch (CBitrixCloudException $e)
		{
			return false;
		}
		$node = /*.(CDataXMLNode).*/ null;
		$node = $this->infoXML->SelectNodes("/control/quota/allow");
		if (is_object($node))
			$this->quota = CBitrixCloudCDNQuota::parseSize($node->textContent());

		$node = $this->infoXML->SelectNodes("/control/files");
		if (is_object($node))
		{
			$this->last_backup_time = 0;
			$this->total_size = 0.0;
			$this->files = /*.(array[int][string]string).*/ array();
			$nodeFiles = $node->elementsByName("file");
			foreach($nodeFiles as $nodeFile)
			{
				/* @var CDataXMLNode $nodeFile */
				$size = CBitrixCloudCDNQuota::parseSize($nodeFile->getAttribute("size"));
				$name = $nodeFile->getAttribute("name");
				$this->total_size += $size;
				$this->files[] = array(
					"FILE_NAME" => $name,
					"FILE_SIZE" => (string)$size,
				);
				$time = strtotime(preg_replace('/^(\\d{4})(\\d\\d)(\\d\\d)_(\\d\\d)(\\d\\d)(\\d\\d)(.*)$/', '\\1-\\2-\\3 \\4:\\5:\\6', $name));
				if($time > $this->last_backup_time)
					$this->last_backup_time = $time;
			}
		}

		return true;
	}
	/**
	 * Returns list of backup files
	 *
	 * @return array[int][string]string
	 *
	 */
	public function listFiles() /*. throws CBitrixCloudException .*/
	{
		$this->_getInformation();
		return $this->files;
	}
	/**
	 * Returns amount of space available for backup
	 *
	 * @return float
	 *
	 */
	public function getQuota() /*. throws CBitrixCloudException .*/
	{
		$this->_getInformation();
		return $this->quota;
	}
	/**
	 * Returns amount of space used by backup files
	 *
	 * @return float
	 *
	 */
	public function getUsage() /*. throws CBitrixCloudException .*/
	{
		$this->_getInformation();
		return $this->total_size;
	}
	/**
	 * Returns timestamp of the last saved backup
	 *
	 * @return int
	 *
	 */
	public function getLastTimeBackup() /*. throws CBitrixCloudException .*/
	{
		$this->_getInformation();
		return $this->last_backup_time;
	}
	/**
	 * Returns bucket object for backup operation.
	 *
	 * @param string $operation
	 * @param string $check_word
	 * @param string $file_name
	 * @return CBitrixCloudBackupBucket
	 * @throws CBitrixCloudException
	 */
	private function _getBucket($operation, $check_word, $file_name)
	{
		if (!CModule::IncludeModule('clouds'))
			throw new CBitrixCloudException("Module clouds not installed.");

		$web_service = new CBitrixCloudBackupWebService();
		if($operation === "write")
			$obXML = $web_service->actionWriteFile($check_word, $file_name);
		else
			$obXML = $web_service->actionReadFile($check_word, $file_name);

		$bucket_name = (is_object($node = $obXML->SelectNodes("/control/bucket/bucket_name")))? $node->textContent(): "";
		$bucket_location = (is_object($node = $obXML->SelectNodes("/control/bucket/bucket_location")))? $node->textContent(): "";
		$prefix = (is_object($node = $obXML->SelectNodes("/control/bucket/prefix")))? $node->textContent(): "";
		$access_key = (is_object($node = $obXML->SelectNodes("/control/bucket/access_key")))? $node->textContent(): "";
		$secret_key = (is_object($node = $obXML->SelectNodes("/control/bucket/secret_key")))? $node->textContent(): "";
		$session_token = (is_object($node = $obXML->SelectNodes("/control/bucket/session_token")))? $node->textContent(): "";
		$file_name = (is_object($node = $obXML->SelectNodes("/control/bucket/file_name")))? $node->textContent(): "";

		return new CBitrixCloudBackupBucket(
			$bucket_name,
			$prefix,
			$access_key,
			$secret_key,
			$session_token,
			$check_word,
			$file_name,
			$bucket_location
		);
	}
	/**
	 * Returns bucket object for downloading backup file.
	 *
	 * @param string $check_word
	 * @param string $file_name
	 * @return CBitrixCloudBackupBucket
	 * @throws CBitrixCloudException
	 */
	public function getBucketToReadFile($check_word, $file_name)
	{
		return $this->_getBucket("read", $check_word, $file_name);
	}
	/**
	 * Returns bucket object for uploading backup file.
	 *
	 * @param string $check_word
	 * @param string $file_name
	 * @return CBitrixCloudBackupBucket
	 * @throws CBitrixCloudException
	 */
	public function getBucketToWriteFile($check_word, $file_name)
	{
		return $this->_getBucket("write", $check_word, $file_name);
	}
	/**
	 * Deletes state stored in the database.
	 *
	 * @return CBitrixCloudBackup
	 */
	static public function clearOptions()
	{
		CBitrixCloudOption::getOption("backup_files")->delete();
		CBitrixCloudOption::getOption("backup_quota")->delete();
		CBitrixCloudOption::getOption("backup_total_size")->delete();
		CBitrixCloudOption::getOption("backup_last_backup_time")->delete();
		return $this;
	}
	/**
	 * Saves state into the database.
	 *
	 * @return CBitrixCloudBackup
	 */
	public function saveToOptions()
	{
		$this->_getInformation();
		$arFiles = array();
		foreach($this->files as $arFile)
		{
			$arFiles[$arFile["FILE_NAME"]] = $arFile["FILE_SIZE"];
		}
		ksort($arFiles);
		CBitrixCloudOption::getOption("backup_files")->setArrayValue($arFiles);
		CBitrixCloudOption::getOption("backup_quota")->setStringValue((string)$this->quota);
		CBitrixCloudOption::getOption("backup_total_size")->setStringValue((string)$this->total_size);
		CBitrixCloudOption::getOption("backup_last_backup_time")->setStringValue((string)$this->last_backup_time);
		return $this;
	}
	/**
	 * Restores state from the database.
	 *
	 * @return CBitrixCloudBackup
	 */
	public function loadFromOptions()
	{
		$this->files = /*.(array[int][string]string).*/ array();
		foreach(CBitrixCloudOption::getOption("backup_files")->getArrayValue() as $FILE_NAME => $FILE_SIZE)
		{
			$this->files[] = array(
				"FILE_NAME" => $FILE_NAME,
				"FILE_SIZE" => $FILE_SIZE,
			);
		}
		$this->quota = doubleval(CBitrixCloudOption::getOption("backup_quota")->getStringValue());
		$this->total_size = doubleval(CBitrixCloudOption::getOption("backup_total_size")->getStringValue());
		$this->last_backup_time = intval(CBitrixCloudOption::getOption("backup_last_backup_time")->getStringValue());
		$this->init = true;
		return $this;
	}
	/**
	 * Shows information about CDN free space in Admin's informer popup
	 *
	 * @return void
	 */
	static public function OnAdminInformerInsertItems()
	{
		$CDNAIParams = array(
			"TITLE" => GetMessage("BCL_BACKUP_AI_TITLE"),
			"COLOR" => "peach",
		);

		$backup = self::getInstance();
		$backup->loadFromOptions();
		$last_request_time_option = CBitrixCloudOption::getOption("backup_last_backup_time");
		try
		{
			if (
				$backup->getQuota() <= 0
				&& $last_request_time_option->getIntegerValue() <= 0
			)
			{
				$backup->_getInformation(true);
				$backup->saveToOptions();
				$last_request_time_option->setStringValue((string)time());
			}
		}
		catch (CBitrixCloudException $e)
		{
			///TODO show error to user
			return;
		}

		if ( $backup->getQuota() <= 0 )
			return;

		$arFiles = $backup->listFiles();
		if (empty($arFiles))
		{
			$PROGRESS_FREE = 100;
			$AVAIL = $backup->getQuota();
			$ALLOWED = CFile::FormatSize($backup->getQuota(), 0);
			$CDNAIParams["ALERT"] = true;
			$MESS = '<span class="adm-informer-strong-text">'.GetMessage("BCL_BACKUP_AI_NO_FILES").'</span>';
			$CDNAIParams["FOOTER"] = '<a href="/bitrix/admin/dump.php?lang='.LANGUAGE_ID.'">'.GetMessage("BCL_BACKUP_AI_DO_BACKUP_STRONGLY").'</a>';
		}
		elseif($backup->getLastTimeBackup() < (time()-7*24*3600))
		{
			$AVAIL = $backup->getQuota()-$backup->getUsage();
			if($AVAIL < 0.0)
				$AVAIL = 0.0;

			$PROGRESS_FREE = round($AVAIL/$backup->getQuota()*100);
			$ALLOWED = CFile::FormatSize($backup->getQuota(), 0);
			$CDNAIParams["ALERT"] = true;
			$MESS = '<span class="adm-informer-strong-text">'.GetMessage("BCL_BACKUP_AI_LAST_TIME").': '.FormatDate(array(
					"today" => "today",
					"yesterday" => "yesterday",
					"" => "dago",
				), $backup->getLastTimeBackup()).'.</span>';
			$CDNAIParams["FOOTER"] = '<a href="/bitrix/admin/dump.php?lang='.LANGUAGE_ID.'">'.GetMessage("BCL_BACKUP_AI_DO_BACKUP_STRONGLY").'</a>';
		}
		else
		{
			$AVAIL = $backup->getQuota()-$backup->getUsage();
			if($AVAIL < 0.0)
				$AVAIL = 0.0;

			$PROGRESS_FREE = round($AVAIL/$backup->getQuota()*100);
			$ALLOWED = CFile::FormatSize($backup->getQuota(), 0);
			$CDNAIParams["ALERT"] = false;
			$MESS = GetMessage("BCL_BACKUP_AI_LAST_TIME").': '.FormatDate(array(
					"today" => "today",
					"yesterday" => "yesterday",
					"" => "dago",
				), $backup->getLastTimeBackup());
			$CDNAIParams["FOOTER"] = '<a href="/bitrix/admin/dump.php?lang='.LANGUAGE_ID.'">'.GetMessage("BCL_BACKUP_AI_DO_BACKUP").'</a>';
		}

		if(isset($CDNAIParams["ALERT"]))
		{
			$PROGRESS_FREE_BAR = $PROGRESS_FREE < 0? 0: $PROGRESS_FREE;
			$CDNAIParams["HTML"] = '
				<div class="adm-informer-item-section">
					<span class="adm-informer-item-l">
						<span class="adm-informer-strong-text">'.GetMessage("BCL_BACKUP_AI_USAGE_TOTAL").'</span> '.$ALLOWED.'
					</span>
					<span class="adm-informer-item-r">
							<span class="adm-informer-strong-text">'.GetMessage("BCL_BACKUP_AI_USAGE_AVAIL").'</span> '.CFile::FormatSize($AVAIL, 0).'
					</span>
				</div>
				<div class="adm-informer-status-bar-block" >
					<div class="adm-informer-status-bar-indicator" style="width:'.(100-$PROGRESS_FREE_BAR).'%; "></div>
					<div class="adm-informer-status-bar-text">'.(100-$PROGRESS_FREE).'%</div>
				</div>
			'.$MESS;
			CAdminInformer::AddItem($CDNAIParams);
		}
	}
	/*
	 * Registers new backup job with the remote service.
	 * Returns empty string on success.
	 *
	 * @param string $secret_key
	 * @param string $url
	 * @param int $time
	 * @param array $weekdays
	 * @return string
	 *
	 */
	static public function addBackupJob($secret_key, $url, $time = 0, $weekdays = array())
	{
		try
		{
			$web_service = new CBitrixCloudBackupWebService();
			$web_service->actionAddBackupJob($secret_key, $url, $time, $weekdays);
			return "";
		}
		catch (CBitrixCloudException $e)
		{
			return $e->getMessage();//."[".htmlspecialcharsEx($e->getErrorCode())."]";
		}
	}
	/*
	 * Cancels backup job with the remote service.
	 * Returns empty string on success.
	 *
	 * @return string
	 *
	 */
	static public function deleteBackupJob()
	{
		try
		{
			$web_service = new CBitrixCloudBackupWebService();
			$web_service->actionDeleteBackupJob();
			return "";
		}
		catch (CBitrixCloudException $e)
		{
			return $e->getMessage();//."[".htmlspecialcharsEx($e->getErrorCode())."]";
		}
	}

	static public function getBackupJob()
	{
		try
		{
			$web_service = new CBitrixCloudBackupWebService();
			$infoXML = $web_service->actionGetBackupJob();
		}
		catch (CBitrixCloudException $e)
		{
			return $e->getMessage();//."[".htmlspecialcharsEx($e->getErrorCode())."]";
		}

		$result = array();
		$jobList = $infoXML->SelectNodes("/control/JobList");
		if (is_object($jobList))
		{
			$jobEntries = $jobList->elementsByName("JobEntry");
			foreach ($jobEntries as $jobEntry)
			{
				$info  = array();
				foreach($jobEntry->children() as $field)
				{
					$name = $field->name();
					$value = $field->textContent();
					$info[$name] = $value;
				}
				$result[] = array(
					"URL" => $info["Url"],
					"TIME" => $info["Time"],
					"WEEK_DAYS" => explode(",", $info["WeekDays"]),
					"STATUS" => $info["Status"],
					"FINISH_TIME" => $info["FinishTime"],
				);
			}
		}
		return $result;
	}
}
