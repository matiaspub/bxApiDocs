<?php

class CSecurityEvent
{
	private static $instance = null;

	private $isDBEngineActive = true;
	private $isSyslogEngineActive = false;
	private $syslogFacility = "";
	private $syslogPriority = "";
	private $isFileEngineActive = false;
	private $filePath = "";

	/** @var CSecurityEventMessageFormatter $messageFormatter */
	private $messageFormatter = null;

	private static $syslogFacilities = array(
		LOG_SYSLOG   => "LOG_SYSLOG",
		LOG_AUTH     => "LOG_AUTH",
		LOG_AUTHPRIV => "LOG_AUTHPRIV",
		LOG_DAEMON   => "LOG_DAEMON",
		LOG_USER     => "LOG_USER"
	);

	private static $syslogPriorities = array(
		LOG_EMERG   => "LOG_EMERG",
		LOG_ALERT   => "LOG_ALERT",
		LOG_CRIT    => "LOG_CRIT",
		LOG_ERR     => "LOG_ERR",
		LOG_WARNING => "LOG_WARNING",
		LOG_NOTICE  => "LOG_NOTICE",
		LOG_INFO    => "LOG_INFO",
		LOG_DEBUG   => "LOG_DEBUG"
	);

	private function __construct()
	{
		$this->initializeDBEngine(COption::getOptionString("security", "security_event_db_active") == "Y");
		$this->initializeSyslogEngine(COption::getOptionString("security", "security_event_syslog_active") == "Y");
		$this->initializeFileEngine(COption::getOptionString("security", "security_event_file_active") == "Y");
		$this->messageFormatter = new CSecurityEventMessageFormatter(
			COption::getOptionString("security", "security_event_format"),
			COption::getOptionString("security", "security_event_userinfo_format")
		);
	}

	/**
	 * @param bool $pActive
	 */
	private function initializeFileEngine($pActive = false)
	{
		if($pActive)
		{
			$this->isFileEngineActive = true;
			$this->filePath = COption::getOptionString("security", "security_event_file_path");
			if(!checkDirPath($this->filePath))
				$this->isFileEngineActive = false;
		}
		else
		{
			$this->isFileEngineActive = false;
		}
	}

	/**
	 * @param bool $pActive
	 */
	private function initializeDBEngine($pActive = false)
	{
		$this->isDBEngineActive = $pActive;
	}

	/**
	 * @param bool $pActive
	 */
	private function initializeSyslogEngine($pActive = false)
	{
		if($pActive)
		{
			$this->isSyslogEngineActive = true;
			if(self::isRunOnWin())
				$this->syslogFacility = LOG_USER;
			else
				$this->syslogFacility = COption::getOptionString("security", "security_event_syslog_facility");

			$this->syslogPriority = COption::getOptionString("security", "security_event_syslog_priority");
			openlog("Bitrix WAF", LOG_ODELAY, $this->syslogFacility);
		}
		else
		{
			$this->isSyslogEngineActive = false;
		}
	}

	/**
	 * @return CSecurityEvent
	 */
	public static function getInstance()
	{
		if(is_null(self::$instance))
		{
			self::$instance = new CSecurityEvent();
		}
		return self::$instance;
	}

	/**
	 * @param string $pSeverity
	 * @param string $pAuditType
	 * @param string $pItemName
	 * @param string $pItemDescription
	 * @return bool
	 */
	public function doLog($pSeverity, $pAuditType, $pItemName, $pItemDescription)
	{
		$savedInDB = $savedInFile = $savedInSyslog = false;
		if($this->isDBEngineActive)
		{
			$savedInDB = CEventLog::log($pSeverity, $pAuditType, "security", $pItemName, "=".base64_encode($pItemDescription));
		}
		$message = "";
		if($this->isSyslogEngineActive)
		{
			$message = $this->messageFormatter->format($pAuditType, $pItemName, $pItemDescription);
			$savedInSyslog = syslog($this->syslogPriority, $message);
		}
		if($this->isFileEngineActive)
		{
			if (!$message)
				$message = $this->messageFormatter->format($pAuditType, $pItemName, $pItemDescription);

			$message .= "\n";
			$savedInFile = file_put_contents($this->filePath, $message, FILE_APPEND) > 0;
		}
		return ($savedInDB || $savedInSyslog || $savedInFile);
	}

	/**
	 * @return array
	 */
	public static function getSyslogPriorities()
	{
		return self::$syslogPriorities;
	}

	/**
	 * @return array
	 */
	public static function getSyslogFacilities()
	{
		if(self::isRunOnWin())
			return array(LOG_USER => "LOG_USER");
		else
			return self::$syslogFacilities;
	}

	/**
	 * Return WAF events count for Admin's informer popup and Admin's gadget
	 * @param string $pTimestamp  - from date
	 * @return integer
	 */
	public function getEventsCount($pTimestamp = '')
	{
		if(!$this->isDBEngineActive)
			return 0;

		/**
		 * @global CCacheManager $CACHE_MANAGER
		 * @global CDataBase $DB
		 */
		global $DB, $CACHE_MANAGER;
		$ttl = 3600;
		$cacheId = 'sec_events_count';
		$cacheDir = '/security/events';
		
		if($CACHE_MANAGER->read($ttl, $cacheId, $cacheDir))
		{
			$result = $CACHE_MANAGER->get($cacheId);
		}
		else
		{
			if(strlen($pTimestamp) <= 0)
			{
				$days = COption::getOptionInt("main", "event_log_cleanup_days", 7);
				if($days > 7)
					$days = 7;
				$pTimestamp = convertTimeStamp(time()-$days*24*3600+CTimeZone::getOffset());
			}

			$arAudits = array(
				"SECURITY_FILTER_SQL",
				"SECURITY_FILTER_XSS",
				"SECURITY_FILTER_XSS2",
				"SECURITY_FILTER_PHP"
			);

			$strAuditsSql = implode("', '",$arAudits);

			$strSql = "
				SELECT COUNT(ID) AS COUNT
				FROM
					b_event_log
				WHERE
					AUDIT_TYPE_ID in ('".$strAuditsSql."')
				AND
					(MODULE_ID = 'security' and MODULE_ID is not null)
				AND
					TIMESTAMP_X >= ".$DB->charToDateFunction($DB->forSQL($pTimestamp))."
			";

			$res = $DB->query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);

			if($arRes = $res->fetch())
				$result = $arRes["COUNT"];
			else
				$result = 0;

			$CACHE_MANAGER->set($cacheId, $result);
		}

		return $result;
	}

	public function getMessageFormatter()
	{
		return $this->messageFormatter;
	}

	/**
	 * @return bool
	 */
	private static function isRunOnWin()
	{
		return (strtoupper(substr(PHP_OS, 0, 3)) === "WIN");
	}

	/**
	 *
	 */
	private function __clone()
	{
		/* ... @return Singleton */
	}

	/**
	 *
	 */
	private function __wakeup()
	{
		/* ... @return Singleton */
	}
}
