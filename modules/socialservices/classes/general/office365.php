<?
IncludeModuleLangFile(__FILE__);

class CSocServOffice365OAuth extends CSocServAuth
{
	const ID = "Office365";
	const CONTROLLER_URL = "https://www.bitrix24.ru/controller";

	/** @var COffice365OAuthInterface null  */
	protected $entityOAuth = null;

	public function getEntityOAuth()
	{
		if(!$this->entityOAuth)
		{
			$this->entityOAuth = new COffice365OAuthInterface();
			$this->entityOAuth->setUser($this->userId);
		}

		return $this->entityOAuth;
	}

	public function GetSettings()
	{
		return array(
			array("office365_appid", GetMessage("socserv_office365_client_id"), "", Array("text", 40)),
			array("office365_appsecret", GetMessage("socserv_office365_client_secret"), "", Array("text", 40)),
			array("office365_tenant", GetMessage("socserv_office365_tenant"), "", Array("text", 40)),
			array("note"=>GetMessage("socserv_office365_form_note", array('#URL#'=>$this->getEntityOAuth()->getRedirectUri()))),
		);
	}

	static public function CheckSettings()
	{
		return self::GetOption('office365_appid') !== '' && self::GetOption('office365_appsecret') !== '';
	}

	public function GetFormHtml($arParams)
	{
		$url = $this->getUrl('opener', null, $arParams);
		if($arParams["FOR_INTRANET"])
			return array("ON_CLICK" => 'onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 580, 400)"');
		return '<a href="javascript:void(0)" onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 580, 400)" class="bx-ss-button liveid-button"></a><span class="bx-spacer"></span><span>'.GetMessage("MAIN_OPTION_COMMENT").'</span>';
	}

	public function GetOnClickJs($arParams)
	{
		$url = $this->getUrl('opener', null, $arParams);
		return "BX.util.popup('".CUtil::JSEscape($url)."', 580, 400)";
	}

	public function getUrl($location = 'opener', $addScope = null, $arParams = array())
	{
		global $APPLICATION;

		CSocServAuthManager::SetUniqueKey();
		if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
		{
			$redirect_uri = self::CONTROLLER_URL."/redirect.php";
			$state = $this->getEntityOAuth()->getRedirectUri()."?state=";
			$backurl = urlencode($GLOBALS["APPLICATION"]->GetCurPageParam('check_key='.$_SESSION["UNIQUE_KEY"], array("logout", "auth_service_error", "auth_service_id", "backurl"))).(isset($arParams['BACKURL']) ? '&redirect_url='.urlencode($arParams['BACKURL']) : '').'&mode='.$location;
			$state .= urlencode(urlencode("backurl=".$backurl));
		}
		else
		{
			$backurl = $APPLICATION->GetCurPageParam(
				'check_key='.$_SESSION["UNIQUE_KEY"],
				array("logout", "auth_service_error", "auth_service_id", "backurl")
			);

			$redirect_uri = $this->getEntityOAuth()->getRedirectUri();
			$state = 'site_id='.SITE_ID.'&backurl='.urlencode($backurl).(isset($arParams['BACKURL']) ? '&redirect_url='.urlencode($arParams['BACKURL']) : '').'&mode='.$location;
		}

		return $this->getEntityOAuth()->GetAuthUrl($redirect_uri, $state);
	}

	public function getStorageToken()
	{
		$accessToken = null;
		$userId = intval($this->userId);
		if($userId > 0)
		{
			$dbSocservUser = CSocServAuthDB::GetList(array(), array('USER_ID' => $userId, "EXTERNAL_AUTH_ID" => static::ID), false, false, array("OATOKEN", "REFRESH_TOKEN", "OATOKEN_EXPIRES"));
			if($arOauth = $dbSocservUser->Fetch())
			{
				$accessToken = $arOauth["OATOKEN"];

				if(empty($accessToken) || ((intval($arOauth["OATOKEN_EXPIRES"]) > 0) && (intval($arOauth["OATOKEN_EXPIRES"] < intval(time())))))
				{
					if(isset($arOauth['REFRESH_TOKEN']))
						$this->entityOAuth->getNewAccessToken($arOauth['REFRESH_TOKEN'], $userId, true);
					if(($accessToken = $this->entityOAuth->getToken()) === false)
						return null;
				}
			}
		}

		return $accessToken;
	}

	static public function prepareUser($office365User)
	{
		$email = $first_name = $last_name = "";
		$login = "Office365".$office365User['id'];
		$uId = $office365User['id'];

		if($office365User['givenName'] <> '')
		{
			$first_name = $office365User['givenName'];
		}

		if($office365User['surname'] <> '')
		{
			$last_name = $office365User['surname'];
		}

		if($office365User['mail'] <> '')
		{
			$email = $office365User['mail'];
			$login = $office365User['mail'];
		}

		$arFields = array(
			'EXTERNAL_AUTH_ID' => self::ID,
			'XML_ID' => $uId,
			'LOGIN' => $login,
			'EMAIL' => $email,
			'NAME'=> $first_name,
			'LAST_NAME'=> $last_name,
		);

		$arFields["PERSONAL_PHONE"] = $office365User["telephoneNumber"];

		if(isset($office365User['access_token']))
		{
			$arFields["OATOKEN"] = $office365User['access_token'];
		}

		if(isset($office365User['refresh_token']))
		{
			$arFields["REFRESH_TOKEN"] = $office365User['refresh_token'];
		}

		if(isset($office365User['expires_in']))
		{
			$arFields["OATOKEN_EXPIRES"] = time() + $office365User['expires_in'];
		}

		if(strlen(SITE_ID) > 0)
		{
			$arFields["SITE_ID"] = SITE_ID;
		}

		$arFields["PERMISSIONS"] = serialize(array(
			"tenant" => $office365User["tenant"],
		));

		return $arFields;
	}

	public function Authorize()
	{
		global $APPLICATION;

		$APPLICATION->RestartBuffer();

		$bProcessState = false;
		$bSuccess = SOCSERV_AUTHORISATION_ERROR;

		if(isset($_REQUEST["code"]) && $_REQUEST["code"] != '' && CSocServAuthManager::CheckUniqueKey())
		{
			$this->getEntityOAuth()->setCode($_REQUEST["code"]);

			$bProcessState = true;

			if($this->getEntityOAuth()->GetAccessToken() !== false)
			{
				$office365User = $this->getEntityOAuth()->GetCurrentUser();
				if(is_array($office365User) &&  ($office365User['id'] <> ''))
				{
					$office365User["tenant"] = preg_replace("/^.*@/", "", $office365User["userPrincipalName"]);

					$allowAuth = true;
					$tenantRestriction = self::GetOption("office365_tenant");
					if(!empty($tenantRestriction))
					{
						$allowAuth = $office365User["tenant"] == $tenantRestriction;
					}

					if($allowAuth)
					{
						$arFields = self::prepareUser($office365User);
						$bSuccess = $this->AuthorizeUser($arFields);
					}
				}
			}
		}

		if(!$bProcessState)
		{
			unset($_REQUEST["state"]);
		}

		$url = ($APPLICATION->GetCurDir() == "/login/") ? "" : $APPLICATION->GetCurDir();
		$aRemove = array("logout", "auth_service_error", "auth_service_id", "code", "error_reason", "error", "error_description", "check_key", "current_fieldset");

		$mode = 'opener';
		$addParams = true;
		if(isset($_REQUEST["state"]))
		{
			$arState = array();
			parse_str($_REQUEST["state"], $arState);
			if(isset($arState['backurl']) || isset($arState['redirect_url']))
			{
				$url = !empty($arState['redirect_url']) ? $arState['redirect_url'] : $arState['backurl'];
				if(substr($url, 0, 1) !== "#")
				{
					$parseUrl = parse_url($url);
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
				else
				{
					$addParams = false;
				}
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
		{
			$url = (isset($parseUrl))
				? $urlPath.'?auth_service_id='.self::ID.'&auth_service_error='.$bSuccess
				: $APPLICATION->GetCurPageParam(('auth_service_id='.self::ID.'&auth_service_error='.$bSuccess), $aRemove);
		}

		if($addParams && CModule::IncludeModule("socialnetwork") && strpos($url, "current_fieldset=") === false)
			$url = (preg_match("/\?/", $url)) ? $url."&current_fieldset=SOCSERV" : $url."?current_fieldset=SOCSERV";

		$url = CUtil::JSEscape($url);

		if($addParams)
		{
			$location = ($mode == "opener") ? 'if(window.opener) window.opener.location = \''.$url.'\'; window.close();' : ' window.location = \''.$url.'\';';
		}
		else
		{
			//fix for chrome
			$location = ($mode == "opener") ? 'if(window.opener) window.opener.location = window.opener.location.href + \''.$url.'\'; window.close();' : ' window.location = window.location.href + \''.$url.'\';';
		}

		$JSScript = '
		<script type="text/javascript">
		'.$location.'
		</script>
		';

		echo $JSScript;

		die();
	}

	static public function getProfileUrl($id)
	{
		return 'https://portal.office.com/';
	}

}

class COffice365OAuthInterface extends CSocServOAuthTransport
{
	const SERVICE_ID = "Office365";

	const AUTH_URL = "https://login.microsoftonline.com/common/oauth2/authorize";
	const TOKEN_URL = "https://login.microsoftonline.com/common/oauth2/token";

	const VERSION = "/v1.0";

	const CONTACTS_URL = "/me/";

	const REDIRECT_URI = "/bitrix/tools/oauth/office365.php";

	protected $resource = "https://graph.microsoft.com";

	static public function __construct($appID = false, $appSecret = false, $code=false)
	{
		if($appID === false)
		{
			$appID = trim(CSocServOffice365OAuth::GetOption("office365_appid"));
		}

		if($appSecret === false)
		{
			$appSecret = trim(CSocServOffice365OAuth::GetOption("office365_appsecret"));
		}

		parent::__construct($appID, $appSecret, $code);
	}

	public function GetAuthUrl($redirect_uri, $state='')
	{
		return static::AUTH_URL.
		"?client_id=".urlencode($this->appID).
		"&redirect_uri=".urlencode($redirect_uri).
		"&response_type=code".
		"&resource=".urlencode($this->resource).
		($state <> ''? '&state='.urlencode($state):'');
	}

	public function GetAccessToken($redirect_uri = false)
	{
		$tokens = $this->getStorageTokens();

		if(is_array($tokens))
		{
			$this->access_token = $tokens["OATOKEN"];
			$this->accessTokenExpires = $tokens["OATOKEN_EXPIRES"];

			if(!$this->code)
			{
				if($this->checkAccessToken())
				{
					return true;
				}
				elseif(isset($tokens["REFRESH_TOKEN"]))
				{
					if($this->getNewAccessToken($tokens["REFRESH_TOKEN"], $this->userId, true))
					{
						return true;
					}
				}
			}

			$this->deleteStorageTokens();
		}

		if($this->code === false)
		{
			return false;
		}

		if($redirect_uri === false)
		{
			if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
			{
				$redirect_uri = \CSocServOffice365OAuth::CONTROLLER_URL."/redirect.php";
			}
			else
			{
				$redirect_uri = $this->getRedirectUri();
			}
		}

		$httpClient = new \Bitrix\Main\Web\HttpClient();

		$result = $httpClient->post(static::TOKEN_URL, array(
			"code"=>$this->code,
			"client_id"=>$this->appID,
			"client_secret"=>$this->appSecret,
			"redirect_uri"=>$redirect_uri,
			"grant_type"=>"authorization_code",
			"resource" => $this->resource,
		));

		$arResult = \Bitrix\Main\Web\Json::decode($result);

		if(isset($arResult["access_token"]) && $arResult["access_token"] <> '')
		{
			$this->access_token = $arResult["access_token"];
			$this->accessTokenExpires = $arResult["expires_in"];
			if(isset($arResult["refresh_token"]) && $arResult["refresh_token"] <> '')
			{
				$this->refresh_token = $arResult["refresh_token"];
			}
			$_SESSION["OAUTH_DATA"] = array("OATOKEN" => $this->access_token);
			return true;
		}
		return false;
	}

	public function getNewAccessToken($refreshToken, $userId = 0, $save = false)
	{
		if($this->appID == false || $this->appSecret == false)
			return false;

		$httpClient = new \Bitrix\Main\Web\HttpClient();

		$result = $httpClient->post(static::TOKEN_URL, array(
			"refresh_token"=>$refreshToken,
			"client_id"=>$this->appID,
			"client_secret"=>$this->appSecret,
			"grant_type"=>"refresh_token",
		));

		$arResult = \Bitrix\Main\Web\Json::decode($result);

		if(isset($arResult["access_token"]) && $arResult["access_token"] <> '')
		{
			$this->access_token = $arResult["access_token"];
			$this->accessTokenExpires = $arResult["expires_in"];
			if($save && intval($userId) > 0)
			{
				$dbSocservUser = CSocServAuthDB::GetList(array(), array('USER_ID' => intval($userId), "EXTERNAL_AUTH_ID" => static::SERVICE_ID), false, false, array("ID"));
				if($arOauth = $dbSocservUser->Fetch())
					CSocServAuthDB::Update($arOauth["ID"], array("OATOKEN" => $this->access_token,"OATOKEN_EXPIRES" => time() + $this->accessTokenExpires));
			}
			return true;
		}
		return false;
	}

	public function getResource()
	{
		return $this->resource;
	}

	public function GetCurrentUser()
	{
		if($this->access_token === false)
			return false;

		$httpClient = new \Bitrix\Main\Web\HttpClient();
		$httpClient->setHeader("Authorization", "Bearer ". $this->access_token);

		$result = $httpClient->get($this->resource.static::VERSION.static::CONTACTS_URL);
		$result = \Bitrix\Main\Web\Json::decode($result);

		if(is_array($result))
		{
			$result["access_token"] = $this->access_token;
			$result["refresh_token"] = $this->refresh_token;
			$result["expires_in"] = $this->accessTokenExpires;
		}
		return $result;
	}

	public function getTenant()
	{
		$tokenInfo = $this->getStorageTokens();
		if($tokenInfo && $tokenInfo["PERMISSIONS"])
		{
			$permissions = unserialize($tokenInfo["PERMISSIONS"]);

			return $permissions["tenant"];
		}

		return false;
	}

	static public function getRedirectUri()
	{
		return \CHTTP::URN2URI(static::REDIRECT_URI);
	}
}

/* @deprecated */
class COffice365OAuthInterfaceOld extends COffice365OAuthInterface
{
	const RESOURCE_TPL = "https://#TENANT#-my.sharepoint.com";

	const VERSION = "/_api/v2.0";

	protected $tenant = null;

	public function __construct($tenant = false, $appID = false, $appSecret = false, $code=false)
	{
		if($tenant === false)
		{
			$tenant = trim(CSocServOffice365OAuth::GetOption("office365_tenant"));
		}

		$this->setTenant($tenant);

		return parent::__construct($appID, $appSecret, $code);
	}

	public function getTenant()
	{
		return $this->tenant;
	}

	public function setTenant($tenant)
	{
		$this->tenant = $tenant;
		$this->resource = str_replace("#TENANT#", $this->tenant, static::RESOURCE_TPL);
	}
}

?>