<?
IncludeModuleLangFile(__FILE__);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");

class CBitrixCloudBackupWebService extends CBitrixCloudWebService
{
	private $file_name = "";
	private $check_word = "";
	private $spd = "";
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
		$arParams["spd"] = $this->spd;
		$arParams["lang"] = LANGUAGE_ID;
		$arParams["file_name"] = $this->file_name;
		$arParams["check_word"] = $this->check_word;
		$url = COption::GetOptionString("bitrixcloud", "backup_policy_url");
		$url = CHTTP::urlAddParams($url, $arParams, array(
			"encode" => true,
		));
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
			)), "");
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
		$this->check_word = "";
		$this->file_name = "";
		$this->spd = "";
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
		$this->check_word = $check_word;
		$this->file_name = $file_name;
		$this->spd = "";
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
		$this->check_word = "";
		$this->file_name = $file_name;
		$this->spd = CUpdateClient::getSpd();
		return $this->backup_action("write_file");
	}
}
?>
