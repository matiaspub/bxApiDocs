<?
IncludeModuleLangFile(__FILE__);

class CSocServGoogleOAuth extends CSocServAuth
{
	const ID = "GoogleOAuth";
	const CONTROLLER_URL = "https://www.bitrix24.ru/controller";

	/** @var CGoogleOAuthInterface null  */
	protected $entityOAuth = null;
	/**
	 * @var CUser null
	 */
	protected $user = null;

	public function __construct($user = null)
	{
		$this->user = $user;
	}

	public function getEntityOAuth()
	{
		return $this->entityOAuth;
	}

	static public function GetSettings()
	{
		return array(
			array("google_appid", GetMessage("socserv_google_client_id"), "", Array("text", 40)),
			array("google_appsecret", GetMessage("socserv_google_client_secret"), "", Array("text", 40)),
			array("note"=>GetMessage("socserv_google_note", array('#URL#'=>CSocServUtil::ServerName()."/bitrix/tools/oauth/google.php"))),
		);
	}

	static public function GetFormHtml($arParams)
	{
		$url = self::getUrl();
		$phrase = ($arParams["FOR_INTRANET"]) ? GetMessage("socserv_google_form_note_intranet") : GetMessage("socserv_google_form_note");
		if($arParams["FOR_INTRANET"])
			return array("ON_CLICK" => 'onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 580, 400)"');
		return '<a href="javascript:void(0)" onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 580, 400)" class="bx-ss-button google-button"></a><span class="bx-spacer"></span><span>'.$phrase.'</span>';
	}

	public function getUrl($location = 'opener', $addScope = null)
	{
		$appID = trim(self::GetOption("google_appid"));
		$appSecret = trim(self::GetOption("google_appsecret"));

		$this->entityOAuth = new CGoogleOAuthInterface($appID, $appSecret);
		if($this->user == null)
			$this->entityOAuth->setRefreshToken("skip");
		if($addScope !== null)
			$this->entityOAuth->addScope($addScope);

		if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
		{
			$redirect_uri = self::CONTROLLER_URL."/redirect.php";
			$state = urlencode(CSocServUtil::GetCurUrl('auth_service_id='.self::ID.'&check_key='.$_SESSION["UNIQUE_KEY"].'&mode='.$location));
		}
		else
		{
			$state = 'site_id='.SITE_ID.'&backurl='.urlencode($GLOBALS["APPLICATION"]->GetCurPageParam('check_key='.$_SESSION["UNIQUE_KEY"], array("logout", "auth_service_error", "auth_service_id", "backurl"))).'&mode='.$location;
			$redirect_uri = CSocServUtil::ServerName()."/bitrix/tools/oauth/google.php";
		}

		return $this->entityOAuth->GetAuthUrl($redirect_uri, $state);
	}

	public function getStorageToken()
	{
		$accessToken = null;
		if(is_object($this->user))
		{
			$dbSocservUser = CSocServAuthDB::GetList(array(), array('USER_ID' => $this->user->GetID(), "EXTERNAL_AUTH_ID" => "GoogleOAuth"), false, false, array("OATOKEN", "REFRESH_TOKEN", "OATOKEN_EXPIRES"));
			if($arOauth = $dbSocservUser->Fetch())
			{
				$accessToken = $arOauth["OATOKEN"];

				if(empty($accessToken) || ((intval($arOauth["OATOKEN_EXPIRES"]) > 0) && (intval($arOauth["OATOKEN_EXPIRES"] < intval(time())))))
				{
					if(isset($arOauth['REFRESH_TOKEN']))
						$this->entityOAuth->getNewAccessToken($arOauth['REFRESH_TOKEN'], $this->user->GetID(), true);
					if(($accessToken = $this->entityOAuth->getToken()) === false)
						return null;
				}
			}
		}

		return $accessToken;
	}

	public function Authorize()
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();
		$bSuccess = SOCSERV_AUTHORISATION_ERROR;
		if((isset($_REQUEST["code"]) && $_REQUEST["code"] <> '') && CSocServAuthManager::CheckUniqueKey())
		{
			if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
				$redirect_uri = self::CONTROLLER_URL."/redirect.php";
			else
				$redirect_uri = CSocServUtil::ServerName()."/bitrix/tools/oauth/google.php";

			$appID = trim(self::GetOption("google_appid"));
			$appSecret = trim(self::GetOption("google_appsecret"));

			$gAuth = new CGoogleOAuthInterface($appID, $appSecret, $_REQUEST["code"]);

			$this->entityOAuth = $gAuth;

			if($gAuth->GetAccessToken($redirect_uri) !== false)
			{
				$arGoogleUser = $gAuth->GetCurrentUser();

				if(is_array($arGoogleUser) && ($arGoogleUser['email'] <> ''))
				{
					$first_name = $last_name = $gender = "";
					if($arGoogleUser['name'] <> '')
					{
						$aName = explode(" ", $arGoogleUser['name']);
						if($arGoogleUser['given_name'] <> '')
							$first_name = $arGoogleUser['given_name'];
						else
							$first_name = $aName[0];
						if($arGoogleUser['family_name'] <> '')
							$last_name = $arGoogleUser['family_name'];
						elseif(isset($aName[1]))
							$last_name = $aName[1];
					}
					$email = $arGoogleUser['email'];
					if($arGoogleUser['gender'] <> '')
						if($arGoogleUser['gender'] == 'male')
							$gender = 'M';
						elseif($arGoogleUser['gender'] == 'female')
							$gender = 'F';

					$arFields = array(
						'EXTERNAL_AUTH_ID' => self::ID,
						'XML_ID' => $email,
						'LOGIN' => "G_".$email,
						'EMAIL' => $email,
						'NAME'=> $first_name,
						'LAST_NAME'=> $last_name
					);

					if($gender != "")
						$arFields['PERSONAL_GENDER'] = $gender;

					if(isset($arGoogleUser['picture']) && self::CheckPhotoURI($arGoogleUser['picture']))
						if($arPic = CFile::MakeFileArray($arGoogleUser['picture']))
							$arFields["PERSONAL_PHOTO"] = $arPic;

					$arFields["PERSONAL_WWW"] = $arGoogleUser['link'];

					if(isset($arGoogleUser['access_token']))
						$arFields["OATOKEN"] = $arGoogleUser['access_token'];

					if(isset($arGoogleUser['refresh_token']))
						$arFields["REFRESH_TOKEN"] = $arGoogleUser['refresh_token'];

					if(isset($arGoogleUser['expires_in']))
						$arFields["OATOKEN_EXPIRES"] = time() + $arGoogleUser['expires_in'];

					if(strlen(SITE_ID) > 0)
						$arFields["SITE_ID"] = SITE_ID;
					$bSuccess = $this->AuthorizeUser($arFields);
				}
			}
		}
		$url = ($APPLICATION->GetCurDir() == "/login/") ? "" : $APPLICATION->GetCurDir();
		$aRemove = array("logout", "auth_service_error", "auth_service_id", "code", "error_reason", "error", "error_description", "check_key", "current_fieldset");

		$mode = 'opener';
		if(isset($_REQUEST["state"]))
		{
			$arState = array();
			parse_str($_REQUEST["state"], $arState);
			if(isset($arState['backurl']))
			{
				$parseUrl = parse_url($arState['backurl']);
				$urlPath = $parseUrl["path"];
				$arUrlQuery = explode('&', $parseUrl["query"]);

				foreach($arUrlQuery as $key => $value)
				{
					foreach($aRemove as $param)
					{
						if(strpos($value, $param."=") === 0)
						{
							unset($arUrlQuery[$key]);
							break;
						}
					}
				}

				$url = (!empty($arUrlQuery)) ? $urlPath.'?'.implode("&", $arUrlQuery) : $urlPath;
			}

			if(isset($arState['mode']))
			{
				$mode = $arState['mode'];
			}
		}
		if($bSuccess === SOCSERV_REGISTRATION_DENY)
		{
			$url = (preg_match("/\?/", $url)) ? $url.'&' : $url.'?';
			$url .= 'auth_service_id='.self::ID.'&auth_service_error='.SOCSERV_REGISTRATION_DENY;
		}
		elseif($bSuccess !== true)
			$url = (isset($urlPath)) ? $urlPath.'?auth_service_id='.self::ID.'&auth_service_error='.$bSuccess : $APPLICATION->GetCurPageParam(('auth_service_id='.self::ID.'&auth_service_error='.$bSuccess), $aRemove);
		if(CModule::IncludeModule("socialnetwork") && strpos($url, "current_fieldset=") === false)
			$url = (preg_match("/\?/", $url)) ? $url."&current_fieldset=SOCSERV" : $url."?current_fieldset=SOCSERV";

		$url = CUtil::JSEscape($url);
		$location = ($mode == "opener") ? 'if(window.opener) window.opener.location = \''.$url.'\'; window.close();' : ' window.location = \''.$url.'\';';

		$JSScript = '
		<script type="text/javascript">
		'.$location.'
		</script>
		';

		echo $JSScript;
		
		die();
	}
}

class CGoogleOAuthInterface
{
	const AUTH_URL = "https://accounts.google.com/o/oauth2/auth";
	const TOKEN_URL = "https://accounts.google.com/o/oauth2/token";
	const CONTACTS_URL = "https://www.googleapis.com/oauth2/v1/userinfo";

	protected $appID;
	protected $appSecret;
	protected $code = false;
	protected $access_token = false;
	protected $accessTokenExpires = 0;
	protected $refresh_token = '';
	protected $scope = array(
		'https://www.googleapis.com/auth/userinfo.email',
		'https://www.googleapis.com/auth/userinfo.profile',
	);

	public function getAccessTokenExpires()
	{
		return $this->accessTokenExpires;
	}

	public function getAppID()
	{
		return $this->appID;
	}

	public function getAppSecret()
	{
		return $this->appSecret;
	}

	public function getToken()
	{
		return $this->access_token;
	}

	/**
	 * @param string $refresh_token
	 */
	public function setRefreshToken($refresh_token)
	{
		$this->refresh_token = $refresh_token;
	}

	public function setScope($scope)
	{
		$this->scope = $scope;
	}

	public function getScope()
	{
		return $this->scope;
	}

	public function addScope($scope)
	{
		if(is_array($scope))
			$this->scope = array_merge($this->scope, $scope);
		else
			$this->scope[] = $scope;
		return $this;
	}

	public function getScopeEncode()
	{
		return implode('+', array_map('urlencode', array_unique($this->getScope())));
	}

	public function __construct($appID, $appSecret, $code = false)
	{
		$this->httpTimeout = SOCSERV_DEFAULT_HTTP_TIMEOUT;
		$this->appID = $appID;
		$this->appSecret = $appSecret;
		$this->code = $code;
	}

	public function GetAuthUrl($redirect_uri, $state = '')
	{
		return self::AUTH_URL.
			"?client_id=".urlencode($this->appID).
			"&redirect_uri=".urlencode($redirect_uri).
			"&scope=".$this->getScopeEncode().
			"&response_type=code".
			"&access_type=offline".
			($this->refresh_token <> '' ? '' : '&approval_prompt=force').
			($state <> '' ? '&state='.urlencode($state) : '');
	}

	public function GetAccessToken($redirect_uri)
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
			"code"=>$this->code,
			"client_id"=>$this->appID,
			"client_secret"=>$this->appSecret,
			"redirect_uri"=>$redirect_uri,
			"grant_type"=>"authorization_code",
		), array(), $this->httpTimeout);

		$arResult = CUtil::JsObjectToPhp($result);

		if(isset($arResult["access_token"]) && $arResult["access_token"] <> '')
		{
			if(isset($arResult["refresh_token"]) && $arResult["refresh_token"] <> '')
			{
				$this->refresh_token = $arResult["refresh_token"];
			}
			$this->access_token = $arResult["access_token"];
			$this->accessTokenExpires = $arResult["expires_in"];
			return true;
		}
		return false;
	}

	public function GetCurrentUser()
	{
		if($this->access_token === false)
			return false;

		$result = CHTTP::sGetHeader(self::CONTACTS_URL.'?access_token='.urlencode($this->access_token), array(), $this->httpTimeout);

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
			$dbSocservUser = CSocServAuthDB::GetList(array(), array('USER_ID' => $USER->GetID(), "EXTERNAL_AUTH_ID" => "GoogleOAuth"), false, false, array("OATOKEN", "REFRESH_TOKEN"));
			if($arOauth = $dbSocservUser->Fetch())
				$accessToken = $arOauth["OATOKEN"];
		}
		return $accessToken;
	}

	private function checkAccessToken()
	{
		return (($this->accessTokenExpires - 30) < time()) ? false : true;
	}

	public function getNewAccessToken($refreshToken, $userId = 0, $save = false)
	{
		if($this->appID == false || $this->appSecret == false)
			return false;

		$result = CHTTP::sPostHeader(self::TOKEN_URL, array(
			"refresh_token"=>$refreshToken,
			"client_id"=>$this->appID,
			"client_secret"=>$this->appSecret,
			"grant_type"=>"refresh_token",
		), array(), $this->httpTimeout);

		$arResult = CUtil::JsObjectToPhp($result);

		if(isset($arResult["access_token"]) && $arResult["access_token"] <> '')
		{
			$this->access_token = $arResult["access_token"];
			$this->accessTokenExpires = $arResult["expires_in"];
			if($save && intval($userId) > 0)
			{
				$dbSocservUser = CSocServAuthDB::GetList(array(), array('USER_ID' => intval($userId), "EXTERNAL_AUTH_ID" => "GoogleOAuth"), false, false, array("ID"));
				if($arOauth = $dbSocservUser->Fetch())
					CSocServAuthDB::Update($arOauth["ID"], array("OATOKEN" => $this->access_token,"OATOKEN_EXPIRES" => time() + $this->accessTokenExpires));
			}
			return true;
		}
		return false;
	}
}