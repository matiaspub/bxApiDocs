<?php
IncludeModuleLangFile(__FILE__);
class CBitrixCloudMonitoring
{
	private static $instance = /*.(CBitrixCloudMonitoring).*/ null;
	private $result = /*.(CBitrixCloudMonitoringResult).*/null;
	private $interval = 0;
	/**
	 * Returns proxy class instance (singleton pattern)
	 *
	 * @return CBitrixCloudMonitoring
	 *
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
			self::$instance = new CBitrixCloudMonitoring;

		return self::$instance;
	}
	static public function getConfiguredDomains()
	{
		$result = array();
		$converter = CBXPunycode::GetConverter();

		$domainName = COption::GetOptionString("main", "server_name", "");
		if ($domainName != "")
			$result[$domainName] = $domainName;

		$by = "";
		$order = "";
		$siteList = CSite::GetList($by, $order, array("ACTIVE"=>"Y"));
		while($site = $siteList->Fetch())
		{
			$domains = explode("\r\n", $site["DOMAINS"]);
			foreach($domains as $domainName)
			{
				if ($domainName != "")
				{
					$punyName = $converter->Encode($domainName);
					if ($punyName !== false)
						$result[$punyName] = $domainName;
				}
			}
		}

		ksort($result);
		return $result;
	}
	static public function getList()
	{
		$web_service = new CBitrixCloudMonitoringWebService();
		$xml = $web_service->actionGetList();
		$xml = $xml->SelectNodes("/control/domains");
		if (is_object($xml))
		{
			$result = array();
			$children = $xml->children();
			if (is_array($children))
			{
				foreach($children as $domainXml)
				{
					$name = $domainXml->getAttribute("name");
					$emails = $domainXml->elementsByName("emails");
					$tests = $domainXml->elementsByName("tests");
					$result[] = array(
						"DOMAIN" => $name,
						"IS_HTTPS" => ($domainXml->getAttribute("https") === "true"? "Y": "N"),
						"LANG" => $domainXml->getAttribute("lang"),
						"EMAILS" => (is_array($emails)? explode(",", $emails[0]->textContent()): array()),
						"TESTS" => (is_array($tests)? explode(",", $tests[0]->textContent()): array()),
					);
				}
			}
			return $result;
		}
	}
	static public function addDevice($domain, $deviceId)
	{
		if ($deviceId != "")
		{
			$option = CBitrixCloudOption::getOption('monitoring_devices');
			$devices = $option->getArrayValue();
			$devices[] = $domain."|".$deviceId;
			$option->setArrayValue($devices);
		}
	}
	static public function deleteDevice($domain, $deviceId)
	{
		if ($deviceId != "")
		{
			$option = CBitrixCloudOption::getOption('monitoring_devices');
			$devices = $option->getArrayValue();
			$index = array_search($domain."|".$deviceId, $devices);
			if ($index !== false)
			{
				unset($devices[$index]);
				$option->setArrayValue($devices);
			}
		}
	}
	static public function getDevices($domain)
	{
		$result = array();
		$option = CBitrixCloudOption::getOption('monitoring_devices');
		$devices = $option->getArrayValue();
		foreach($devices as $domain_device)
		{
			if (list ($myDomain, $myDevice) = explode("|", $domain_device, 2))
			{
				if ($myDomain === $domain)
					$result[] = $myDevice;
			}
		}
		return $result;
	}
	/*
	 * Registers new monitoring job with the remote service.
	 * Returns empty string on success.
	 *
	 * @return string
	 *
	 */
	static public function startMonitoring($domain, $is_https, $language_id, $emails, $tests)
	{
		try {
			$web_service = new CBitrixCloudMonitoringWebService();
			$web_service->actionStart($domain, $is_https, $language_id, $emails, $tests);
			CBitrixCloudMonitoringResult::setExpirationTime(0);
			return "";
		}
		catch (CBitrixCloudException $e)
		{
			return $e->getMessage();//."[".htmlspecialcharsEx($e->getErrorCode())."]";
		}
	}
	/*
	 * Unregisters monitoring job with the remote service.
	 * Returns empty string on success.
	 *
	 * @return string
	 *
	 */
	static public function stopMonitoring($domain)
	{
		try {
			$web_service = new CBitrixCloudMonitoringWebService();
			$web_service->actionStop($domain);
			CBitrixCloudMonitoringResult::setExpirationTime(0);
			return "";
		}
		catch (CBitrixCloudException $e)
		{
			return $e->getMessage();//."[".htmlspecialcharsEx($e->getErrorCode())."]";
		}
	}
	public function setInterval($interval)
	{
		$interval = intval($interval);
		if (
			$interval != 7
			&& $interval != 30
			&& $interval != 90
			&& $interval != 365
		)
			$interval = 7;
		$this->interval = $interval;
		return $interval;
	}
	public function getInterval()
	{
		if ($this->interval <= 0)
		{
			$this->interval = intval(COption::GetOptionInt("bitrixcloud", "monitoring_interval"));
			if (
				$this->interval != 7
				&& $this->interval != 30
				&& $this->interval != 90
				&& $this->interval != 365
			)
				$this->interval = 7;
		}
		return $this->interval;
	}
	public function getMonitoringResults($interval = false)
	{
		if ($interval === false)
			$interval = $this->getInterval();
		else
			$interval = $this->setInterval($interval);

		if ($this->result === null)
		{
			try {
				if (CBitrixCloudMonitoringResult::isExpired())
				{
					$web_service = new CBitrixCloudMonitoringWebService();
					$xml = $web_service->actionGetInfo($interval);
					$domains = $xml->SelectNodes("/control/domains");
					if (is_object($domains))
					{
						$this->result = CBitrixCloudMonitoringResult::fromXMLNode($domains);
						$control = $xml->SelectNodes("/control");
						if (is_object($control))
						{
							$this->result->saveToOptions();
							CBitrixCloudMonitoringResult::setExpirationTime(strtotime($control->getAttribute("expires")));
						}
					}
				}
				else
				{
					$this->result = CBitrixCloudMonitoringResult::loadFromOptions();
				}
			}
			catch (CBitrixCloudException $e)
			{
				CBitrixCloudMonitoringResult::setExpirationTime(time() + 1800);
				return $e->getMessage();//."[".htmlspecialcharsEx($e->getErrorCode())."]";
			}
		}
		return $this->result;
	}
	public function getAlertsCurrentResult()
	{
		$alerts = false;
		if ($this->result)
		{
			$alerts = array();
			foreach ($this->result as $domainName => $domainResult)
			{
				foreach ($domainResult as $testId => $testResult)
				{
					if ($testResult->getStatus() === CBitrixCloudMonitoringResult::RED_LAMP)
						$alerts[$domainName][$testId] = $testId;
				}

				if (isset($alerts[$domainName]))
				{
					ksort($alerts[$domainName]);
					$alerts[$domainName] = implode(",", $alerts[$domainName]);
				}
			}
			ksort($alerts);
		}
		return $alerts;
	}
	static public function getAlertsStored()
	{
		return CBitrixCloudOption::getOption("monitoring_alert")->getArrayValue();
	}
	public function storeAlertsCurrentResult()
	{
		$alerts = $this->getAlertsCurrentResult();
		if (is_array($alerts))
		{
			CBitrixCloudOption::getOption("monitoring_alert")->setArrayValue($alerts);
		}
	}
	public function getWorstUptime($testId = "", $domainName = "")
	{
		$result = "";
		$maxDiff = 0;

		if ($this->result)
		{
			if ($domainName === "")
			{
				foreach ($this->result as $domainName => $domainResult)
				{
					foreach ($domainResult as $testId => $testResult)
					{
						if (
							($testId === "" || $testId === $testResult->getName())
							&& $testResult->getStatus() === CBitrixCloudMonitoringResult::RED_LAMP
						)
						{
							$uptime = explode("/", $testResult->getUptime());
							$diff = $uptime[1] - $uptime[0];
							if ($diff > $maxDiff)
							{
								$maxDiff = $diff;
								$result = $testResult->getUptime();
							}
						}
					}
				}
			}
			elseif (is_array($this->result[$domainName]))
			{
				foreach ($this->result[$domainName] as $testId => $testResult)
				{
					if (
						($testId === "" || $testId === $testResult->getName())
						&& $testResult->getStatus() === CBitrixCloudMonitoringResult::RED_LAMP
					)
					{
						$uptime = explode("/", $testResult->getUptime());
						$diff = $uptime[1] - $uptime[0];
						if ($diff > $maxDiff)
						{
							$maxDiff = $diff;
							$result = $testResult->getUptime();
						}
					}
				}
			}
		}

		return $result;
	}

	public static function startMonitoringAgent()
	{
		$monitoring = CBitrixCloudMonitoring::getInstance();
		$rsR = CLanguage::GetById("ru");
		if ($rsR->Fetch())
			$language_id = "ru";
		else
		{
			$rsD = CLanguage::GetById("de");
			if ($rsD->Fetch())
				$language_id = "de";
			else
				$language_id = "en";
		}

		$monitoring->startMonitoring(
			COption::GetOptionString("main", "server_name", ""),
			false,
			$language_id,
			array(
				COption::GetOptionString("main", "email_from", ""),
			),
			array(
				"test_lic",
				"test_domain_registration",
				"test_http_response_time",
			)
		);
		return "";
	}
}
