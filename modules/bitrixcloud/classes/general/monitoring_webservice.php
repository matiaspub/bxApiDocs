<?php
IncludeModuleLangFile(__FILE__);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");

class CBitrixCloudMonitoringWebService extends CBitrixCloudWebService
{
	private $addParams = array();
	private $addStr = "";
	/**
	 * Returns URL to backup webservice
	 *
	 * @param array[string]string $arParams
	 * @return string
	 *
	 */
	protected function getActionURL($arParams = /*.(array[string]string).*/ array())
	{
		$arParams["license"] = md5(LICENSE_KEY);
		$arParams["spd"] = CUpdateClient::getSpd();
		$arParams["CHHB"] = $_SERVER["HTTP_HOST"];
		$arParams["CSAB"] = $_SERVER["SERVER_ADDR"];
		foreach($this->addParams as $key => $value)
			$arParams[$key] = $value;

		$url = COption::GetOptionString("bitrixcloud", "monitoring_policy_url");
		$url = CHTTP::urlAddParams($url, $arParams, array(
			"encode" => true,
		)).$this->addStr;

		return $url;
	}
	/**
	 * Returns action response XML and check CRC
	 *
	 * @param string $action
	 * @return CDataXML
	 * @throws CBitrixCloudException
	 */
	protected function monitoring_action($action)
	{
		$obXML = $this->action($action);
		$node = $obXML->SelectNodes("/control");
		if (is_object($node))
		{
			$spd = $node->getAttribute("crc_code");
			if(strlen($spd) > 0)
				CUpdateClient::setSpd($spd);
		}
		else
		{
			throw new CBitrixCloudException(GetMessage("BCL_MON_WS_SERVER", array(
				"#STATUS#" => "-1",
			)), $this->getServerResult());
		}

		return $obXML;
	}
	/**
	 *
	 * @return CDataXML
	 * @throws CBitrixCloudException
	 */
	public function actionGetList()
	{
		$this->addStr = "";
		$this->addParams = array(
			"lang" => LANGUAGE_ID,
		);

		return $this->monitoring_action("monitoring_get_list");
	}
	/**
	 *
	 * @return CDataXML
	 * @throws CBitrixCloudException
	 */
	public function actionStart($domain, $is_https, $language_id, $emails, $tests)
	{
		$this->addStr = "";
		$this->addParams = array(
			"domain" => $domain,
			"domain_is_https" => $is_https? "Y": "N",
			"lang" => $language_id,
		);

		if (is_array($emails))
		{
			foreach($emails as $email)
			{
				$email = trim($email);
				if (strlen($email) > 0)
					$this->addStr .= "&ar_emails[]=".urlencode($email);
			}
		}

		if (is_array($tests))
		{
			foreach($tests as $test)
			{
				$test = trim($test);
				if (strlen($test) > 0)
					$this->addStr .= "&ar_tests[]=".urlencode($test);
			}
		}

		$option = CBitrixCloudOption::getOption('monitoring_devices');
		$devices = $option->getArrayValue();
		foreach($devices as $domain_device)
		{
			if (list ($myDomain, $myDevice) = explode("|", $domain_device, 2))
			{
				if ($myDomain === $domain)
					$this->addStr .= "&ar_devices[]=".urlencode($myDevice);
			}
		}

		$this->monitoring_action("monitoring_start");
	}
	/**
	 *
	 * @return CDataXML
	 * @throws CBitrixCloudException
	 */
	public function actionStop($domain)
	{
		$this->addStr = "";
		$this->addParams = array(
			"domain" => $domain,
			"lang" => LANGUAGE_ID,
		);

		return $this->monitoring_action("monitoring_stop");
	}
	/**
	 *
	 * @return CDataXML
	 * @throws CBitrixCloudException
	 */
	public function actionGetInfo()
	{
		$this->addStr = "";
		$this->addParams = array(
			"lang" => LANGUAGE_ID,
			"interval" => COption::GetOptionString("bitrixcloud", "monitoring_interval"),
		);

		return $this->monitoring_action("monitoring_get_info");
	}
}
