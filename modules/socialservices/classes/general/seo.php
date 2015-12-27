<?php
class CSeoOAuthInterface extends CSocServOAuthTransport
{
	const AUTH_URL = "http://seo.sigurd.bx/oauth/authorize/";
	const TOKEN_URL = "http://seo.sigurd.bx/oauth/token/";

	protected $arResult = array();

	static public function GetRedirectURI()
	{
		return CSocServUtil::ServerName()."/bitrix/tools/seo_client.php";
	}

	public function getResult()
	{
		return $this->arResult;
	}

	public function getError()
	{
		return is_array($this->arResult) && isset($this->arResult['error'])
			? $this->arResult['error']
			: '';
	}

	public function GetAuthUrl($redirect_uri, $state = '')
	{
		return self::AUTH_URL
		."?response_type=code"
		."&client_id=".urlencode($this->appID)
		."&redirect_uri=".urlencode($redirect_uri)
		.(!empty($state) ? "&state=".urlencode($state) : '');
	}

	public function GetAccessToken()
	{
		if(($tokens = $this->getStorageTokens()) && is_array($tokens))
		{
			$this->access_token = $tokens["OATOKEN"];
			if($this->checkAccessToken())
			{
				return true;
			}
		}

		if($this->code === false)
			return false;

		$h = new \Bitrix\Main\Web\HttpClient(array("socketTimeout" => $this->httpTimeout));
		$h->setAuthorization($this->appID, $this->appSecret);

		$result = $h->post(self::TOKEN_URL, array(
			"grant_type"=>"authorization_code",
			"code"=>$this->code,
			"client_id" => $this->appID,
		));

		$this->arResult = \Bitrix\Main\Web\Json::decode($result);

		if(isset($this->arResult["access_token"]) && $this->arResult["access_token"] <> '')
		{
			// yandex doesn't send refresh tokens but I leave it here in case they will
			if(isset($this->arResult["refresh_token"]) && $this->arResult["refresh_token"] <> '')
			{
				$this->refresh_token = $this->arResult["refresh_token"];
			}
			$this->access_token = $this->arResult["access_token"];
			$this->accessTokenExpires = $this->arResult["expires_in"] + time();

			$_SESSION["OAUTH_DATA"] = array(
				"OATOKEN" => $this->access_token,
			);

			return true;
		}
		return false;
	}
}