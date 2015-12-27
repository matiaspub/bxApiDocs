<?php
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

IncludeModuleLangFile(__FILE__);

class CSocServBoxAuth extends CSocServAuth
{
	const ID = "Box";
	const CONTROLLER_URL = "https://www.bitrix24.ru/controller";
	const LOGIN_PREFIX = "B_";

	/** @var CBoxOAuthInterface null  */
	protected $entityOAuth = null;

	protected $userId = null;

	public function __construct($userId = null)
	{
		$this->userId = $userId;
	}

	/**
	 * @param string $code=false
	 * @return CBoxOAuthInterface
	 */
	public function getEntityOAuth($code = false)
	{
		if(!$this->entityOAuth)
		{
			$this->entityOAuth = new CBoxOAuthInterface();
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
			array("box_appid", GetMessage("socserv_box_client_id"), "", array("text", 40)),
			array("box_appsecret", GetMessage("socserv_box_client_secret"), "", array("text", 40)),
			array("note"=>GetMessage("socserv_box_note", array('#URL#'=>CBoxOAuthInterface::GetRedirectURI()))),
		);
	}

	static public function GetFormHtml($arParams)
	{
		$url = static::getUrl('opener', null, $arParams);

		$phrase = ($arParams["FOR_INTRANET"]) ? GetMessage("socserv_box_form_note_intranet") : GetMessage("socserv_box_form_note");

		if($arParams["FOR_INTRANET"])
		{
			return array("ON_CLICK" => 'onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 680, 600)"');
		}
		else
		{
			return '<a href="javascript:void(0)" onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 680, 600)" class="bx-ss-button box-button"></a><span class="bx-spacer"></span><span>'.$phrase.'</span>';
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
			$state = CBoxOAuthInterface::GetRedirectURI()."?check_key=".$_SESSION["UNIQUE_KEY"]."&state=";
			$backurl = $APPLICATION->GetCurPageParam('', array("logout", "auth_service_error", "auth_service_id", "backurl"));
			$state .= urlencode("state=".urlencode("backurl=".urlencode($backurl).'&mode='.$location.(isset($arParams['BACKURL']) ? '&redirect_url='.urlencode($arParams['BACKURL']) : '')));
		}
		else
		{
			$state = 'site_id='.SITE_ID.'&backurl='.urlencode($APPLICATION->GetCurPageParam('check_key='.$_SESSION["UNIQUE_KEY"], array("logout", "auth_service_error", "auth_service_id", "backurl"))).'&mode='.$location.(isset($arParams['BACKURL']) ? '&redirect_url='.urlencode($arParams['BACKURL']) : '');
			$redirect_uri = CBoxOAuthInterface::GetRedirectURI();
		}

		return $this->entityOAuth->GetAuthUrl($redirect_uri, $state);
	}

	public function getStorageToken()
	{
		$accessToken = null;
		$userId = intval($this->userId);
		if($userId > 0)
		{
			$dbSocservUser = CSocServAuthDB::GetList(array(), array('USER_ID' => $userId, "EXTERNAL_AUTH_ID" => static::ID), false, false, array("USER_ID", "OATOKEN", "REFRESH_TOKEN", "OATOKEN_EXPIRES"));
			if($arOauth = $dbSocservUser->Fetch())
			{
				$accessToken = $arOauth["OATOKEN"];
				$accessTokenExpires = $arOauth["OATOKEN_EXPIRES"];

				$entityOauth = $this->getEntityOAuth();
				$entityOauth->setToken($accessToken);
				$entityOauth->setAccessTokenExpires($accessTokenExpires);

				if($entityOauth->checkAccessToken())
				{
					return $accessToken;
				}
				elseif(isset($arOauth["REFRESH_TOKEN"]))
				{
				if($entityOauth->getNewAccessToken($arOauth["REFRESH_TOKEN"], $arOauth["USER_ID"],true))
					{
						return $entityOauth->getToken();
					}
				}
			}
		}

		return $accessToken;
	}

	public function prepareUser($boxUser, $short = false)
	{
		$nameDetails = explode(" ", $boxUser['name'], 2);

		$id = $boxUser['id'];

		$arFields = array(
			'EXTERNAL_AUTH_ID' => static::ID,
			'XML_ID' => $id,
			'LOGIN' => static::LOGIN_PREFIX.$id,
			'NAME'=> $nameDetails[0],
			'LAST_NAME'=> $nameDetails[1],
			'EMAIL' => $boxUser["login"],
			'OATOKEN' => $this->entityOAuth->getToken(),
			'OATOKEN_EXPIRES' => $this->entityOAuth->getAccessTokenExpires(),
			'REFRESH_TOKEN' => $this->entityOAuth->getRefreshToken(),
		);

		if(!$short && !empty($boxUser['avatar_url']))
		{
			$picture_url = $boxUser['avatar_url'];
			$temp_path = CFile::GetTempName('', 'picture.jpg');

			$ob = new HttpClient(array(
				"redirect" => true
			));
			$ob->download($picture_url, $temp_path);

			$arPic = CFile::MakeFileArray($temp_path);
			if($arPic)
			{
				$arFields["PERSONAL_PHOTO"] = $arPic;
			}
		}

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

				$boxUser = $this->entityOAuth->GetCurrentUser();

				if(is_array($boxUser))
				{
					$arFields = self::prepareUser($boxUser);
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

class CBoxOAuthInterface extends CSocServOAuthTransport
{
	const SERVICE_ID = "Box";

	const AUTH_URL = "https://app.box.com/api/oauth2/authorize";
	const TOKEN_URL = "https://app.box.com/api/oauth2/token";

	const ACCOUNT_URL = "https://api.box.com/2.0/users/me";

	protected $oauthResult;

	static public function __construct($appID = false, $appSecret = false, $code = false)
	{
		if($appID === false)
		{
			$appID = trim(CSocServBoxAuth::GetOption("box_appid"));
		}

		if($appSecret === false)
		{
			$appSecret = trim(CSocServBoxAuth::GetOption("box_appsecret"));
		}

		parent::__construct($appID, $appSecret, $code);
	}

	static public function GetRedirectURI()
	{
		return CSocServUtil::ServerName(true)."/bitrix/tools/oauth/box.php";
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
		$token = $this->getStorageTokens();

		if(is_array($token))
		{
			if(!$this->code)
			{
				$this->access_token = $token["OATOKEN"];
				$this->accessTokenExpires = $token["OATOKEN_EXPIRES"];

				if($this->checkAccessToken())
				{
					return true;
				}
				elseif(isset($token["REFRESH_TOKEN"]))
				{
					if($this->getNewAccessToken($token["REFRESH_TOKEN"], $token["USER_ID"], true))
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

		$h = new HttpClient();
		$result = $h->post(static::TOKEN_URL, array(
			"code"=>$this->code,
			"client_id"=>$this->appID,
			"client_secret"=>$this->appSecret,
			"redirect_uri"=>$redirect_uri,
			"grant_type"=>"authorization_code",
		));

		$this->oauthResult = Json::decode($result);

		if(isset($this->oauthResult["access_token"]) && $this->oauthResult["access_token"] <> '')
		{
			$this->access_token = $this->oauthResult["access_token"];
			$this->accessTokenExpires = time() + $this->oauthResult["expires_in"];

			if(isset($this->oauthResult["refresh_token"]) && $this->oauthResult["refresh_token"] <> '')
			{
				$this->refresh_token = $this->oauthResult["refresh_token"];
			}

			$_SESSION["OAUTH_DATA"] = array(
				"OATOKEN" => $this->access_token,
			);

			return true;
		}
		return false;
	}

	public function getNewAccessToken($refreshToken = false, $userId = 0, $save = false, $scope = array())
	{
		if($this->appID == false || $this->appSecret == false)
		{
			return false;
		}

		if($refreshToken == false)
		{
			$refreshToken = $this->refresh_token;
		}

		$http = new HttpClient(array('socketTimeout' => $this->httpTimeout));

		$result = $http->post(static::TOKEN_URL, array(
			'client_id' => $this->appID,
			'client_secret' => $this->appSecret,
			'refresh_token' => $refreshToken,
			'grant_type' => 'refresh_token',
		));

		$arResult = Json::decode($result);

		if(isset($arResult["access_token"]) && $arResult["access_token"] <> '')
		{
			$this->access_token = $arResult["access_token"];
			$this->accessTokenExpires = time() + $arResult["expires_in"];
			$this->refresh_token = $arResult["refresh_token"];

			if($save && intval($userId) > 0)
			{
				$dbSocservUser = CSocServAuthDB::GetList(
					array(),
					array(
						"USER_ID" => intval($userId),
						"EXTERNAL_AUTH_ID" => CSocServBoxAuth::ID
					), false, false, array("ID")
				);

				$arOauth = $dbSocservUser->Fetch();

				if($arOauth)
				{
					CSocServAuthDB::Update(
						$arOauth["ID"], array(
							"OATOKEN" => $this->access_token,
							"OATOKEN_EXPIRES" => $this->accessTokenExpires,
							"REFRESH_TOKEN" => $this->refresh_token,
						)
					);
				}
			}

			return true;
		}
		return false;
	}


	public function GetCurrentUser()
	{
		if($this->access_token === false)
			return false;

		$h = new HttpClient();
		$h->setHeader("Authorization", "Bearer ".$this->access_token);

		$result = $h->get(static::ACCOUNT_URL);

		$result = Json::decode($result);

		if(is_array($result))
		{
			$result["access_token"] = $this->access_token;
		}

		return $result;
	}
}