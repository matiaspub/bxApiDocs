<?php
IncludeModuleLangFile(__FILE__);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");

class CBitrixCloudBackupWebService extends CBitrixCloudWebService
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
		$arParams["lang"] = LANGUAGE_ID;
		foreach($this->addParams as $key => $value)
			$arParams[$key] = $value;

		$url = COption::GetOptionString("bitrixcloud", "backup_policy_url");
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
	protected function backup_action($action) /*. throws CBitrixCloudException .*/
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
			throw new CBitrixCloudException(GetMessage("BCL_BACKUP_WS_SERVER", array(
				"#STATUS#" => "-1",
			)), $this->getServerResult());
		}

		return $obXML;
	}
	/**
	 *
	 * @return CDataXML
	 *
	 */
	public function actionGetInformation() /*. throws CBitrixCloudException .*/
	{
		$this->addStr = "";
		$this->addParams = array();
		return $this->backup_action("get_info");
	}
	/**
	 *
	 * @param string $check_word
	 * @param string $file_name
	 * @return CDataXML
	 *
	 */
	public function actionReadFile($check_word, $file_name) /*. throws CBitrixCloudException .*/
	{
		$this->addStr = "";
		$this->addParams = array(
			"check_word" => $check_word,
			"file_name" => $file_name,
		);
		return $this->backup_action("read_file");
	}
	/**
	 *
	 * @param string $check_word
	 * @param string $file_name
	 * @return CDataXML
	 *
	 */
	public function actionWriteFile($check_word, $file_name) /*. throws CBitrixCloudException .*/
	{
		$this->addStr = "";
		$this->addParams = array(
			"file_name" => $file_name,
			"spd" => CUpdateClient::getSpd(),
			"CHHB" => $_SERVER["HTTP_HOST"],
			"CSAB" => $_SERVER["SERVER_ADDR"],
		);
		return $this->backup_action("write_file");
	}
	/**
	 *
	 * @param string $secret_key
	 * @param string $url
	 * @param int $time
	 * @param array $weekdays
	 * @return CDataXML
	 *
	 */
	public function actionAddBackupJob($secret_key, $url, $time = 0, $weekdays = array()) /*. throws CBitrixCloudException .*/
	{
		if ($secret_key == "")
		{
			throw new CBitrixCloudException(GetMessage("BCL_BACKUP_EMPTY_SECRET_KEY"), "");
		}

		$parsedUrl = parse_url($url);
		if (
			!is_array($parsedUrl)
			|| !($parsedUrl["scheme"] === "http" || $parsedUrl["scheme"] === "https")
			|| strlen($parsedUrl["host"]) <= 0
			|| !(intval($parsedUrl["port"]) == 0 || intval($parsedUrl["port"]) == 80)
			|| strlen($parsedUrl["path"]) > 0
			|| strlen($parsedUrl["user"]) > 0
			|| strlen($parsedUrl["pass"]) > 0
			|| strlen($parsedUrl["query"]) > 0
			|| strlen($parsedUrl["fragment"]) > 0
		)
		{
			throw new CBitrixCloudException(GetMessage("BCL_BACKUP_WRONG_URL"), "");
		}

		$time = intval($time);
		if ($time < 0 || $time >= 24*3600)
		{
			throw new CBitrixCloudException(GetMessage("BCL_BACKUP_WRONG_TIME"), "");
		}

		$weekdaysIsOk = is_array($weekdays);
		if ($weekdaysIsOk)
		{
			foreach ($weekdays as $dow)
			{
				if (intval($dow) < 0 || intval($dow) > 6)
					$weekdaysIsOk = false;
			}
		}
		if (!$weekdaysIsOk)
		{
			throw new CBitrixCloudException(GetMessage("BCL_BACKUP_WRONG_WEEKDAYS"), "");
		}

		$h = intval($time/3600);
		$time -= $h*3600;
		$m = intval($time/60);
		$this->addParams = array(
			"secret_key" => trim($secret_key),
			"time" => $h.":".$m,
			"domain" => $parsedUrl["host"],
			"spd" => CUpdateClient::getSpd(),
			"CHHB" => $_SERVER["HTTP_HOST"],
			"CSAB" => $_SERVER["SERVER_ADDR"],
		);

		if ($parsedUrl["scheme"] === "https")
		{
			$this->addParams["domain_is_https"] = "Y";
		}

		$this->addStr = "";
		foreach ($weekdays as $dow)
		{
			$this->addStr .= "&ar_weekdays[]=".intval($dow);
		}

		return $this->backup_action("add_backup_job");
	}
	/**
	 *
	 * @return CDataXML
	 * @throws CBitrixCloudException
	 */
	public function actionDeleteBackupJob()
	{
		$this->addStr = "";
		$this->addParams = array(
			"spd" => CUpdateClient::getSpd(),
			"CHHB" => $_SERVER["HTTP_HOST"],
			"CSAB" => $_SERVER["SERVER_ADDR"],
		);

		return $this->backup_action("delete_backup_job");
	}
	/**
	 *
	 * @return CDataXML
	 * @throws CBitrixCloudException
	 */
	public function actionGetBackupJob()
	{
		$this->addStr = "";
		$this->addParams = array(
			"spd" => CUpdateClient::getSpd(),
			"CHHB" => $_SERVER["HTTP_HOST"],
			"CSAB" => $_SERVER["SERVER_ADDR"],
		);

		return $this->backup_action("get_backup_job");
	}
}
