<?php
IncludeModuleLangFile(__FILE__);

class CSocServDropboxAuth extends CSocServAuth
{
	const ID = "Dropbox";
	const CONTROLLER_URL = "https://www.bitrix24.ru/controller";
	const LOGIN_PREFIX = "DB_";

	/** @var CDropboxOAuthInterface null  */
	protected $entityOAuth = null;

	protected $userId = null;

	public function __construct($userId = null)
	{
		$this->userId = $userId;
	}

	/**
	 * @param string $code=false
	 * @return CDropboxOAuthInterface
	 */
	public function getEntityOAuth($code = false)
	{
		if(!$this->entityOAuth)
		{
			$this->entityOAuth = new CDropboxOAuthInterface();
		}

		if($code !== false)
		{
			$this->entityOAuth->setCode($code);
		}

		return $this->entityOAuth;
	}

	static public function GetSettings()
	{
		return array(
			array("dropbox_appid", GetMessage("socserv_dropbox_client_id"), "", array("text", 40)),
			array("dropbox_appsecret", GetMessage("socserv_dropbox_client_secret"), "", array("text", 40)),
			array("note"=>GetMessage("socserv_dropbox_note", array('#URL#'=>CDropboxOAuthInterface::GetRedirectURI()))),
		);
	}

	static public function GetFormHtml($arParams)
	{
		$url = static::getUrl('opener', null, $arParams);

		$phrase = ($arParams["FOR_INTRANET"]) ? GetMessage("socserv_dropbox_form_note_intranet") : GetMessage("socserv_dropbox_form_note");

		if($arParams["FOR_INTRANET"])
		{
			return array("ON_CLICK" => 'onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 680, 600)"');
		}
		else
		{
			return '<a href="javascript:void(0)" onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 680, 600)" class="bx-ss-button dropbox-button"></a><span class="bx-spacer"></span><span>'.$phrase.'</span>';
		}
	}

	static public function GetOnClickJs($arParams)
	{
		$url = static::getUrl('opener', null, $arParams);
		return "BX.util.popup('".CUtil::JSEscape($url)."', 680, 600)";
	}


	public function getUrl($location = 'opener', $addScope = null, $arParams = array())
	{
		global $APPLICATION;

		$this->entityOAuth = $this->getEntityOAuth();
		CSocServAuthManager::SetUniqueKey();
		if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
		{
			$redirect_uri = static::CONTROLLER_URL."/redirect.php";
			$state = CDropboxOAuthInterface::GetRedirectURI()."?check_key=".$_SESSION["UNIQUE_KEY"]."&state=";
			$backurl = $APPLICATION->GetCurPageParam('', array("logout", "auth_service_error", "auth_service_id", "backurl"));
			$state .= urlencode("state=".urlencode("backurl=".urlencode($backurl).'&mode='.$location.(isset($arParams['BACKURL']) ? '&redirect_url='.urlencode($arParams['BACKURL']) : '')));
		}
		else
		{
			$state = 'site_id='.SITE_ID.'&backurl='.urlencode($APPLICATION->GetCurPageParam('check_key='.$_SESSION["UNIQUE_KEY"], array("logout", "auth_service_error", "auth_service_id", "backurl"))).'&mode='.$location.(isset($arParams['BACKURL']) ? '&redirect_url='.urlencode($arParams['BACKURL']) : '');
			$redirect_uri = CDropboxOAuthInterface::GetRedirectURI();
		}

		return $this->entityOAuth->GetAuthUrl($redirect_uri, $state);
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
			}
		}

		return $accessToken;
	}

	public function prepareUser($arDropboxUser, $short = false)
	{
		$first_name = "";
		$last_name = "";
		if(is_array($arDropboxUser['name_details']))
		{
			$first_name = $arDropboxUser['name_details']['given_name'];
			$last_name = $arDropboxUser['name_details']['surname'];
		}

		$id = $arDropboxUser['uid'];

		$arFields = array(
			'EXTERNAL_AUTH_ID' => static::ID,
			'XML_ID' => $id,
			'LOGIN' => static::LOGIN_PREFIX.$id,
			'NAME'=> $first_name,
			'LAST_NAME'=> $last_name,
			'EMAIL' => $arDropboxUser["email"],
			'OATOKEN' => $this->entityOAuth->getToken(),
			'OATOKEN_EXPIRES' => $this->entityOAuth->getAccessTokenExpires(),
		);

		if(strlen(SITE_ID) > 0)
		{
			$arFields["SITE_ID"] = SITE_ID;
		}

		return $arFields;
	}

	public function Authorize()
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();

		$bSuccess = false;
		$bProcessState = false;
		$authError = SOCSERV_AUTHORISATION_ERROR;

		if(
			isset($_REQUEST["code"]) && $_REQUEST["code"] <> '' && CSocServAuthManager::CheckUniqueKey()
		)
		{
			$bProcessState = true;

			$this->entityOAuth = $this->getEntityOAuth($_REQUEST['code']);

			if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
			{
				$redirect_uri = static::CONTROLLER_URL."/redirect.php";
			}
			else
			{
				$redirect_uri = $this->getEntityOAuth()->GetRedirectURI();
			}

			if($this->entityOAuth->GetAccessToken($redirect_uri) !== false)
			{
				$arDropboxUser = $this->entityOAuth->GetCurrentUser();
				if(is_array($arDropboxUser))
				{
					$arFields = self::prepareUser($arDropboxUser);
					$authError = $this->AuthorizeUser($arFields);
					$bSuccess = $authError === true;
				}
			}
		}

		$url = ($APPLICATION->GetCurDir() == "/login/") ? "" : $APPLICATION->GetCurDir();
		$aRemove = array("logout", "auth_service_error", "auth_service_id", "code", "error_reason", "error", "error_description", "check_key", "current_fieldset");

		if(!$bProcessState)
		{
			unset($_REQUEST["state"]);
		}

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

		if($authError === SOCSERV_REGISTRATION_DENY)
		{
			$url = (preg_match("/\?/", $url)) ? $url.'&' : $url.'?';
			$url .= 'auth_service_id='.static::ID.'&auth_service_error='.SOCSERV_REGISTRATION_DENY;
		}
		elseif($bSuccess !== true)
		{
			$url = (isset($urlPath)) ? $urlPath.'?auth_service_id='.static::ID.'&auth_service_error='.$authError : $APPLICATION->GetCurPageParam(('auth_service_id='.static::ID.'&auth_service_error='.$authError), $aRemove);
		}

		if($addParams && CModule::IncludeModule("socialnetwork") && strpos($url, "current_fieldset=") === false)
		{
			$url = (preg_match("/\?/", $url)) ? $url."&current_fieldset=SOCSERV" : $url."?current_fieldset=SOCSERV";
		}

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
}

class CDropboxOAuthInterface extends CSocServOAuthTransport
{
	const SERVICE_ID = "Dropbox";

	const AUTH_URL = "https://www.dropbox.com/1/oauth2/authorize";
	const TOKEN_URL = "https://www.dropbox.com/1/oauth2/token";

	const ACCOUNT_URL = "https://api.dropbox.com/1/account/info";

	protected $oauthResult;

	static public function __construct($appID = false, $appSecret = false, $code = false)
	{
		if($appID === false)
		{
			$appID = trim(CSocServDropboxAuth::GetOption("dropbox_appid"));
		}

		if($appSecret === false)
		{
			$appSecret = trim(CSocServDropboxAuth::GetOption("dropbox_appsecret"));
		}

		parent::__construct($appID, $appSecret, $code);
	}

	static public function GetRedirectURI()
	{
		return CSocServUtil::ServerName(true)."/bitrix/tools/oauth/dropbox.php";
	}

	public function GetAuthUrl($redirect_uri, $state = '')
	{
		return static::AUTH_URL.
		"?client_id=".urlencode($this->appID).
		"&redirect_uri=".urlencode($redirect_uri).
		"&response_type=code".
		($state <> '' ? '&state='.urlencode($state) : '');
	}

	public function GetAccessToken($redirect_uri)
	{
		$tokens = $this->getStorageTokens();

		if(is_array($tokens))
		{
			$this->access_token = $tokens["OATOKEN"];

			if(!$this->code)
			{
				return true;
			}

			$this->deleteStorageTokens();
		}

		if($this->code === false)
		{
			return false;
		}

		$h = new \Bitrix\Main\Web\HttpClient();
		$result = $h->post(static::TOKEN_URL, array(
			"code"=>$this->code,
			"client_id"=>$this->appID,
			"client_secret"=>$this->appSecret,
			"redirect_uri"=>$redirect_uri,
			"grant_type"=>"authorization_code",
		));

		$this->oauthResult = \Bitrix\Main\Web\Json::decode($result);

		if(isset($this->oauthResult["access_token"]) && $this->oauthResult["access_token"] <> '')
		{
			if(isset($this->oauthResult["refresh_token"]) && $this->oauthResult["refresh_token"] <> '')
			{
				$this->refresh_token = $this->oauthResult["refresh_token"];
			}
			$this->access_token = $this->oauthResult["access_token"];

			$_SESSION["OAUTH_DATA"] = array(
				"OATOKEN" => $this->access_token,
			);

			return true;
		}
		return false;
	}

	public function GetCurrentUser()
	{
		if($this->access_token === false)
			return false;

		$h = new \Bitrix\Main\Web\HttpClient();
		$h->setHeader("Authorization", "Bearer ".$this->access_token);

		$result = $h->get(static::ACCOUNT_URL);

		$result = \Bitrix\Main\Web\Json::decode($result);

		if(is_array($result))
		{
			$result["access_token"] = $this->access_token;
		}

		return $result;
	}
}