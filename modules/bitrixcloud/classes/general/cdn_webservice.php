<?php
IncludeModuleLangFile(__FILE__);

class CBitrixCloudCDNWebService extends CBitrixCloudWebService
{
	private $domain = "";
	private $https = null;
	private $optimize = null;
	/**
	 *
	 * @param string $domain
	 * @return void
	 *
	 */
	public function __construct($domain, $optimize = null, $https = null)
	{
		$this->domain = $domain;
		if (isset($optimize))
			$this->optimize = ($optimize == true);
		if (isset($https))
			$this->https = ($https == true);
	}
	/**
	 * Returns URL to update policy
	 *
	 * @param array[string]string $arParams
	 * @return string
	 *
	 */
	protected function getActionURL($arParams = /*.(array[string]string).*/ array())
	{
		$arErrors = /*.(array[int]string).*/ array();
		$domainTmp = CBXPunycode::ToASCII($this->domain, $arErrors);
		if (strlen($domainTmp) > 0)
			$domain = $domainTmp;
		else
			$domain = $this->domain;

		$arParams["license"] = md5(LICENSE_KEY);
		$arParams["domain"] = $domain;
		if (isset($this->https))
			$arParams["https"] = ($this->https? "Y": "N");
		if (isset($this->optimize))
			$arParams["optimize"] = ($this->optimize? "Y": "N");
		$url = COption::GetOptionString("bitrixcloud", "cdn_policy_url");
		$url = CHTTP::urlAddParams($url, $arParams, array(
			"encode" => true,
		));
		return $url;
	}
	/**
	 *
	 * @return CDataXML
	 *
	 */
	public function actionQuota() /*. throws CBitrixCloudException .*/
	{
		return $this->action("get_quota_info");
	}
	/**
	 *
	 * @return CDataXML
	 *
	 */
	public function actionStop() /*. throws CBitrixCloudException .*/
	{
		return $this->action("stop");
	}
	/**
	 *
	 * @return CDataXML
	 *
	 */
	public function actionGetConfig() /*. throws CBitrixCloudException .*/
	{
		return $this->action("");
	}
}
