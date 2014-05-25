<?
IncludeModuleLangFile(__FILE__);

class CSocServYandexAuth extends CSocServAuth
{
	const ID = "YandexOAuth";

}

class CYandexOAuthInterface
{
	const AUTH_URL = "https://oauth.yandex.ru/authorize";
	const TOKEN_URL = "https://oauth.yandex.ru/token";

	const USERINFO_URL = "https://login.yandex.ru/info";

	protected $appID;
	protected $appSecret;

	protected $code = false;
	protected $access_token = false;
	protected $accessTokenExpires = 0;
	protected $refresh_token = '';

	// protected $scope = array(
	// 	'https://www.googleapis.com/auth/userinfo.email',
	// 	'https://www.googleapis.com/auth/userinfo.profile',
	// );

	protected $arResult = array();

	public function __construct($appID, $appSecret, $code = false)
	{
		$this->httpTimeout = SOCSERV_DEFAULT_HTTP_TIMEOUT;
		$this->appID = $appID;
		$this->appSecret = $appSecret;
		$this->code = $code;
	}

	public function getAccessTokenExpires()
	{
		return $this->accessTokenExpires;
	}

	public function setAccessTokenExpires($accessTokenExpires)
	{
		$this->accessTokenExpires = $accessTokenExpires;
	}

	public function getAppID()
	{
		return $this->appID;
	}

	public function getAppSecret()
	{
		return $this->appSecret;
	}

	public function setToken($access_token)
	{
		$this->access_token = $access_token;
	}

	public function getToken()
	{
		return $this->access_token;
	}

	public function setRefreshToken($refresh_token)
	{
		$this->refresh_token = $refresh_token;
	}

	public function getRefreshToken()
	{
		return $this->refresh_token;
	}

	// public function setScope($scope)
	// {
	// 	$this->scope = $scope;
	// }

	// public function getScope()
	// {
	// 	return $this->scope;
	// }

	public function setCode($code)
	{
		$this->code = $code;
	}

	// public function addScope($scope)
	// {
	// 	if(is_array($scope))
	// 		$this->scope = array_merge($this->scope, $scope);
	// 	else
	// 		$this->scope[] = $scope;
	// 	return $this;
	// }

	// public function getScopeEncode()
	// {
	// 	return implode('+', array_map('urlencode', array_unique($this->getScope())));
	// }

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

	public function GetAuthUrl($state = '')
	{
		return self::AUTH_URL
			."?response_type=code"
			."&client_id=".urlencode($this->appID)
			."&display=popup"
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
			elseif(isset($tokens["REFRESH_TOKEN"]))
			{
				if($this->getNewAccessToken($tokens["REFRESH_TOKEN"]))
				{
					return true;
				}
			}
		}

		if($this->code === false)
			return false;

		$result = CHTTP::sPostHeader(self::TOKEN_URL, array(
			"grant_type"=>"authorization_code",
			"code"=>$this->code,
			"client_id" => $this->appID,
		), array(
			"Authorization" => "Basic ".base64_encode($this->appID.':'.$this->appSecret)
		), $this->httpTimeout);

		echo $result;

		$this->arResult = CUtil::JsObjectToPhp($result);

		if(isset($this->arResult["access_token"]) && $this->arResult["access_token"] <> '')
		{
			// yandex doesn't send refresh tokens but I leave it here in case they will
			if(isset($this->arResult["refresh_token"]) && $this->arResult["refresh_token"] <> '')
			{
				$this->refresh_token = $this->arResult["refresh_token"];
			}
			$this->access_token = $this->arResult["access_token"];
			$this->accessTokenExpires = $this->arResult["expires_in"] + time();
			return true;
		}
		return false;
	}

	public function GetCurrentUser()
	{
		if($this->access_token === false)
			return false;

		$result = CHTTP::sGetHeader(self::USERINFO_URL.'?format=json&oauth_token='.urlencode($this->access_token), array(), $this->httpTimeout);

		if(!defined("BX_UTF"))
			$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);

		$result = CUtil::JsObjectToPhp($result);

		if(is_array($result))
		{
			$result["access_token"] = $this->access_token;
			$result["refresh_token"] = $this->refresh_token;
			$result["expires_in"] = $this->accessTokenExpires;
		}
		return $result;
	}

	private function getStorageTokens()
	{
		global $USER;

		$accessToken = '';
		if(is_object($USER))
		{
			$dbSocservUser = CSocServAuthDB::GetList(array(), array('USER_ID' => $USER->GetID(), "EXTERNAL_AUTH_ID" => CSocServYandexAuth::ID), false, false, array("OATOKEN", "REFRESH_TOKEN"));
			if($arOauth = $dbSocservUser->Fetch())
			{
				$accessToken = $arOauth["OATOKEN"];
			}
		}
		return $accessToken;
	}

	public function checkAccessToken()
	{
		return (($this->accessTokenExpires - 30) < time()) ? false : true;
	}

	// public function getNewAccessToken($refreshToken = false, $userId = 0, $save = false)
	// {
	// 	if($this->appID == false || $this->appSecret == false)
	// 		return false;

	// 	if($refreshToken == false)
	// 		$refreshToken = $this->refresh_token;

	// 	$result = CHTTP::sPostHeader(self::TOKEN_URL, array(
	// 		"refresh_token"=>$refreshToken,
	// 		"client_id"=>$this->appID,
	// 		"client_secret"=>$this->appSecret,
	// 		"grant_type"=>"refresh_token",
	// 	), array(), $this->httpTimeout);

	// 	$this->arResult = CUtil::JsObjectToPhp($result);

	// 	if(isset($this->arResult["access_token"]) && $this->arResult["access_token"] <> '')
	// 	{
	// 		$this->access_token = $this->arResult["access_token"];
	// 		$this->accessTokenExpires = $this->arResult["expires_in"] + time();
	// 		if($save && intval($userId) > 0)
	// 		{
	// 			$dbSocservUser = CSocServAuthDB::GetList(array(), array('USER_ID' => intval($userId), "EXTERNAL_AUTH_ID" => "GoogleOAuth"), false, false, array("ID"));
	// 			if($arOauth = $dbSocservUser->Fetch())
	// 				CSocServAuthDB::Update($arOauth["ID"], array("OATOKEN" => $this->access_token,"OATOKEN_EXPIRES" => $this->accessTokenExpires));
	// 		}
	// 		return true;
	// 	}
	// 	return false;
	// }
}