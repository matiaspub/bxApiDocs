<?
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if(!defined('B24NETWORK_URL'))
{
	// define('B24NETWORK_URL', 'https://www.bitrix24.net');
}

class CSocServBitrix24Net extends CSocServAuth
{
	const ID = "Bitrix24Net";
	const NETWORK_URL = B24NETWORK_URL;

	protected $entityOAuth = null;

	static public function GetSettings()
	{
		return array(
			array("bitrix24net_id", Loc::getMessage("socserv_b24net_id"), "", array("text", 40)),
			array("bitrix24net_secret", Loc::getMessage("socserv_b24net_secret"), "", array("text", 40)),
			array("note"=>GetMessage("socserv_b24net_sett_note"))
		);
	}

	public function getFormHtml($arParams)
	{
		$url = $this->getUrl("popup");

		$phrase = ($arParams["FOR_INTRANET"]) ? Loc::getMessage("socserv_b24net_note_intranet") : Loc::getMessage("socserv_b24net_note");

		return $arParams["FOR_INTRANET"]
			? array("ON_CLICK" => 'onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 800, 600)"')
			: '<a href="javascript:void(0)" onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 800, 600)" class="bx-ss-button bitrix24net-button bitrix24net-button-'.LANGUAGE_ID.'"></a><span class="bx-spacer"></span><span>'.$phrase.'</span>';
	}

	public function GetOnClickJs()
	{
		$url = $this->getUrl("popup");
		return "BX.util.popup('".CUtil::JSEscape($url)."', 800, 600)";
	}

	public function getEntityOAuth($code = false)
	{
		if(!$this->entityOAuth)
		{
			$this->entityOAuth = new CBitrix24NetOAuthInterface();
		}

		if($code !== false)
		{
			$this->entityOAuth->setCode($code);
		}

		return $this->entityOAuth;
	}

	public function getUrl($mode = "page")
	{
		$redirect_uri = CSocServUtil::GetCurUrl('auth_service_id='.self::ID);

		$state =
			'site_id='.SITE_ID
			.'&backurl='.urlencode($GLOBALS["APPLICATION"]->GetCurPageParam(
				'check_key='.CSocServAuthManager::GetUniqueKey(),
				array(
					"logout", "auth_service_error", "auth_service_id", "check_key"
				)
			))
			.'&mode='.$mode;

		return $this->getEntityOAuth()->GetAuthUrl($redirect_uri, $state);
	}

	public function getInviteUrl($userId, $checkword)
	{
		return $this->getEntityOAuth()->GetInviteUrl($userId, $checkword);
	}

	public function addScope($scope)
	{
		return $this->getEntityOAuth()->addScope($scope);
	}

	public function Authorize()
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();

		$bProcessState = false;
		$authError = SOCSERV_AUTHORISATION_ERROR;

		if(
			(isset($_REQUEST["code"]) && $_REQUEST["code"] <> '')
			&& CSocServAuthManager::CheckUniqueKey()
		)
		{
			$redirect_uri = CSocServUtil::ServerName().'/bitrix/tools/oauth/bitrix24net.php';
			$bProcessState = true;

			if($this->getEntityOAuth($_REQUEST["code"])->GetAccessToken($redirect_uri) !== false)
			{

				$arB24NetUser = $this->entityOAuth->GetCurrentUser();
				if($arB24NetUser)
				{
					if(isset($_REQUEST['checkword']) && $arB24NetUser['PROFILE_ID'] > 0)
					{
						$profileId = $arB24NetUser['PROFILE_ID'];
						$checkword = trim($_REQUEST['checkword']);

						$dbRes = CUser::getById($profileId);
						$arUser = $dbRes->fetch();

						if($arUser && !$arUser['LAST_LOGIN'])
						{
							if($arUser['CONFIRM_CODE'] == $checkword)
							{
								$arUserFields = array(
									'CONFIRM_CODE' => '',
									'XML_ID' => $arB24NetUser['ID'],
									'EXTERNAL_AUTH_ID' => 'socservices',
								);

								if($arUser['NAME'] == '' && $arUser['LAST_NAME'] == '')
								{
									$arUserFields['NAME'] = $arB24NetUser['NAME'];
									$arUserFields['LAST_NAME'] = $arB24NetUser['LAST_NAME'];

									if(
										strlen($arB24NetUser['PERSONAL_PHOTO']) > 0
										&& self::CheckPhotoURI($arB24NetUser['PERSONAL_PHOTO']))
									{
										$arUserFields['PERSONAL_PHOTO'] = CFile::MakeFileArray($arB24NetUser['PERSONAL_PHOTO']);
									}
								}

								$obUser = new CUser();
								if($obUser->update($profileId, $arUserFields))
								{
									foreach(GetModuleEvents("main", "OnUserInitialize", true) as $arEvent)
									{
										ExecuteModuleEventEx($arEvent, array($profileId, $arUserFields));
									}
								}
							}
						}
					}

					$arFields = array(
						'EXTERNAL_AUTH_ID' => self::ID,
						'XML_ID' => $arB24NetUser["ID"],
						'LOGIN' => "B24_".$arB24NetUser["ID"],
						'NAME' => $arB24NetUser["NAME"],
						'LAST_NAME' => $arB24NetUser["LAST_NAME"],
						'EMAIL' => $arB24NetUser["EMAIL"],
						'PERSONAL_WWW' => $arB24NetUser["PROFILE"],
						'OATOKEN' => $this->entityOAuth->getToken(),
						'REFRESH_TOKEN' => $this->entityOAuth->getRefreshToken(),
						'OATOKEN_EXPIRES' => $this->entityOAuth->getAccessTokenExpires(),
					);

					if(IsModuleInstalled('bitrix24'))
					{
						$arFields['LOGIN'] = $arFields['EMAIL'];
					}

					if(strlen(SITE_ID) > 0)
					{
						$arFields["SITE_ID"] = SITE_ID;
					}

					$authError = $this->AuthorizeUser($arFields);
				}
			}
		}

		$bSuccess = $authError === true;
		$aRemove = array("logout", "auth_service_error", "auth_service_id", "code", "error_reason", "error", "error_description", "check_key", "current_fieldset", "checkword");

		$url = ($APPLICATION->GetCurDir() == "/login/") ? "" : $APPLICATION->GetCurDir();

		$mode = 'page';

		if(!$bProcessState)
		{
			unset($_REQUEST["state"]);
		}

		if(isset($_REQUEST["state"]))
		{
			$arState = array();
			parse_str($_REQUEST["state"], $arState);

			if(isset($arState['backurl']) || isset($arState['redirect_url']))
			{
				$parseUrl = parse_url(isset($arState['redirect_url']) ? $arState['redirect_url'] : $arState['backurl']);

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

		if(strlen($url) <= 0 || preg_match("'^(http://|https://|ftp://|//)'i", $url))
		{
			$url = CSocServUtil::ServerName().'/';
		}

		$url = CUtil::JSEscape($url);

		if($bSuccess)
		{
			unset($_SESSION['B24_NETWORK_REDIRECT_TRY']);
		}
		else
		{
			if(IsModuleInstalled('bitrix24'))
			{
				if(isset($_SESSION['B24_NETWORK_REDIRECT_TRY']))
				{
					unset($_SESSION['B24_NETWORK_REDIRECT_TRY']);
					$url = self::getUrl();
					$url .= (strpos($url, '?') >= 0 ? '&' : '?').'skip_redirect=1';
				}else
				{
					$_SESSION['B24_NETWORK_REDIRECT_TRY'] = true;
					$url = '/';
				}
			}
			else
			{
				if($authError === SOCSERV_REGISTRATION_DENY)
				{
					$url = (preg_match("/\?/", $url)) ? $url.'&' : $url.'?';
					$url .= 'auth_service_id='.self::ID.'&auth_service_error='.$authError;
				}
				elseif($bSuccess !== true)
				{
					$url = (isset($urlPath)) ? $urlPath.'?auth_service_id='.self::ID.'&auth_service_error='.$authError : $GLOBALS['APPLICATION']->GetCurPageParam(('auth_service_id='.self::ID.'&auth_service_error='.$authError), $aRemove);
				}
			}
		}

		if(CModule::IncludeModule("socialnetwork") && strpos($url, "current_fieldset=") === false)
		{
			$url .= ((strpos($url, "?") === false) ? '?' : '&')."current_fieldset=SOCSERV";
		}

		$location = ($mode == "popup")
			? 'if(window.opener) window.opener.location = \''.$url.'\'; window.close();'
			: 'window.location = \''.$url.'\';';
?>
<script type="text/javascript">
<?=$location?>
</script>
<?

		die();
	}

	public static function registerSite($domain)
	{
		$query = new \Bitrix\Main\Web\HttpClient();
		$result = $query->get(B24NETWORK_URL.'/client.php?action=register&redirect_uri='.urlencode($domain.'/bitrix/tools/oauth/bitrix24net.php').'&key='.urlencode(LICENSE_KEY));

		$arResult = \Bitrix\Main\Web\Json::decode($result);

		if(is_array($arResult))
		{
			return $arResult;
		}
		else
		{
			return array("error" => "Unknown response", "error_details" => $result);
		}
	}
}

class CBitrix24NetOAuthInterface
{
	const NET_URL = B24NETWORK_URL;

	const INVITE_URL = "/invite/";
	const PASSPORT_URL = "/id/";
	const AUTH_URL = "/oauth/authorize/";
	const TOKEN_URL = "/oauth/token/";

	protected $appID;
	protected $appSecret;
	protected $code = false;
	protected $access_token = false;
	protected $accessTokenExpires = 0;
	protected $refresh_token = '';
	protected $scope = array(
		'auth',
	);

	protected $arResult = array();

	public function __construct($appID = false, $appSecret = false, $code = false)
	{
		if($appID === false)
		{
			$appID = trim(CSocServBitrix24Net::GetOption("bitrix24net_id"));
		}

		if($appSecret === false)
		{
			$appSecret = trim(CSocServBitrix24Net::GetOption("bitrix24net_secret"));
		}

		list($prefix, $suffix) = explode(".", $appID, 2);

		if($prefix === 'site')
		{
			$this->addScope("client");
		}
		elseif($prefix == 'b24')
		{
			$this->addScope('profile');
		}

		$this->httpTimeout = SOCSERV_DEFAULT_HTTP_TIMEOUT;
		$this->appID = $appID;
		$this->appSecret = $appSecret;
		$this->code = $code;
	}

	public function getAppID()
	{
		return $this->appID;
	}

	public function getAppSecret()
	{
		return $this->appSecret;
	}

	public function getAccessTokenExpires()
	{
		return $this->accessTokenExpires;
	}

	public function setAccessTokenExpires($accessTokenExpires)
	{
		$this->accessTokenExpires = $accessTokenExpires;
	}

	public function getToken()
	{
		return $this->access_token;
	}

	public function setToken($access_token)
	{
		$this->access_token = $access_token;
	}

	public function getRefreshToken()
	{
		return $this->refresh_token;
	}

	public function setRefreshToken($refresh_token)
	{
		$this->refresh_token = $refresh_token;
	}

	public function setCode($code)
	{
		$this->code = $code;
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
		return implode(',', array_map('urlencode', array_unique($this->getScope())));
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
		return self::NET_URL.self::AUTH_URL.
			"?user_lang=".LANGUAGE_ID.
			"&client_id=".urlencode($this->appID).
			"&redirect_uri=".urlencode($redirect_uri).
			"&scope=".$this->getScopeEncode().
			"&response_type=code".
			//($this->refresh_token <> '' ? '' : '&approval_prompt=force').
			($state <> '' ? '&state='.urlencode($state) : '');
	}

	public function getInviteUrl($userId, $checkword)
	{
		return self::NET_URL.self::INVITE_URL.
			"?user_lang=".LANGUAGE_ID.
			"&client_id=".urlencode($this->appID).
			"&profile_id=".$userId.
			"&checkword=".$checkword;
	}

	public function GetAccessToken($redirect_uri = '')
	{
		$token = $this->getStorageTokens();

		// getStorageTokens returns null for unauthorized user
		if(is_array($token))
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

		if($this->code === false)
		{
			return false;
		}

		$http = new \Bitrix\Main\Web\HttpClient(array('socketTimeout' => $this->httpTimeout));

		$result = $http->get(self::NET_URL.self::TOKEN_URL.'?'.http_build_query(array(
			'code' => $this->code,
			'client_id' => $this->appID,
			'client_secret' => $this->appSecret,
			'redirect_uri' => $redirect_uri,
			'scope' => implode(',',$this->getScope()),
			'grant_type' => 'authorization_code',
		)));

		$arResult = CUtil::JsObjectToPhp($result);

		if(isset($arResult["access_token"]) && $arResult["access_token"] <> '')
		{
			if(isset($arResult["refresh_token"]) && $arResult["refresh_token"] <> '')
			{
				$this->refresh_token = $arResult["refresh_token"];
			}

			$this->access_token = $arResult["access_token"];
			$this->accessTokenExpires = time() + $arResult["expires_in"];

			return true;
		}
		return false;
	}

	public function getNewAccessToken($refreshToken = false, $userId = 0, $save = false, $scope = array())
	{
		if($this->appID == false || $this->appSecret == false)
			return false;

		if($refreshToken == false)
			$refreshToken = $this->refresh_token;

		if($scope != null)
			$this->addScope($scope);

		$http = new \Bitrix\Main\Web\HttpClient(array('socketTimeout' => $this->httpTimeout));

		$result = $http->get(self::NET_URL.self::TOKEN_URL.'?'.http_build_query(array(
			'client_id' => $this->appID,
			'client_secret' => $this->appSecret,
			'refresh_token' => $refreshToken,
			'scope' => implode(',',$this->getScope()),
			'grant_type' => 'refresh_token',
		)));

		$arResult = CUtil::JsObjectToPhp($result);

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
						"EXTERNAL_AUTH_ID" => CSocServBitrix24Net::ID
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
		if($this->access_token)
		{
			$ob = new CBitrix24NetTransport($this->access_token);
			$res = $ob->getProfile();

			if(!isset($res['error']))
			{
				return $res['result'];
			}
		}

		return false;
	}

	public function UpdateCurrentUser($arFields)
	{
		if($this->access_token)
		{
			$ob = new CBitrix24NetTransport($this->access_token);
			$res = $ob->updateProfile($arFields);

			if(!isset($res['error']))
			{
				return $res['result'];
			}
		}

		return false;
	}

	private function getStorageTokens()
	{
		global $USER;

		$accessToken = '';
		if(is_object($USER) && $USER->IsAuthorized())
		{
			$dbSocservUser = CSocServAuthDB::GetList(
				array(),
				array(
					'USER_ID' => $USER->GetID(),
					"EXTERNAL_AUTH_ID" => CSocServBitrix24Net::ID
				), false, false, array("USER_ID", "OATOKEN", "OATOKEN_EXPIRES", "REFRESH_TOKEN")
			);

			$accessToken = $dbSocservUser->Fetch();
		}
		return $accessToken;
	}

	private function checkAccessToken()
	{
		return (($this->accessTokenExpires - 30) < time()) ? false : true;
	}
}

class CBitrix24NetTransport
{
	const SERVICE_URL = "/rest/";

	const METHOD_METHODS = 'methods';
	const METHOD_BATCH = 'batch';
	const METHOD_PROFILE = 'profile';
	const METHOD_PROFILE_ADD = 'profile.add';
	const METHOD_PROFILE_ADD_CHECK = 'profile.add.check';
	const METHOD_PROFILE_UPDATE = 'profile.update';
	const METHOD_PROFILE_DELETE = 'profile.delete';
	const METHOD_PROFILE_CHANNEL = 'profile.channel';

	protected $access_token = '';
	protected $httpTimeout = SOCSERV_DEFAULT_HTTP_TIMEOUT;

	public static function init()
	{
		$ob = new CBitrix24NetOAuthInterface();
		if($ob->GetAccessToken() !== false)
		{
			$token = $ob->getToken();
			return new self($token);
		}

		return false;
	}

	public function __construct($access_token)
	{
		$this->access_token = $access_token;
	}

	protected function prepareResponse($result)
	{
		return \Bitrix\Main\Web\Json::decode($result);
	}

	protected function prepareRequest(array $request)
	{
		$request["auth"] = $this->access_token;

		return $this->convertRequest($request);
	}

	protected function convertRequest(array $request)
	{
		global $APPLICATION;

		return $APPLICATION->ConvertCharsetArray($request, LANG_CHARSET, 'utf-8');
	}

	public function call($methodName, $additionalParams = null)
	{
		if(!is_array($additionalParams))
		{
			$additionalParams = array();
		}

		$request = $this->prepareRequest($additionalParams);

		$http = new \Bitrix\Main\Web\HttpClient(array('socketTimeout' => $this->httpTimeout));
		$result = $http->post(
			CBitrix24NetOAuthInterface::NET_URL.self::SERVICE_URL.$methodName,
			$request
		);

		try
		{
			$res = $this->prepareResponse($result);
		}
		catch(\Bitrix\Main\ArgumentException $e)
		{
			$res = false;
		}

		if(!$res)
		{
			AddMessage2Log('Strange answer from Network! '.$http->getStatus().' '.$result);
		}

		return $res;
	}

	public function batch($actions)
	{
		$arBatch = array();

		if(is_array($actions))
		{
			foreach($actions as $query_key => $arCmd)
			{
				list($cmd, $arParams) = array_values($arCmd);
				$arBatch['cmd'][$query_key] = $cmd.(is_array($arParams) ? '?'.http_build_query($arParams) : '');
			}
		}

		return $this->call(self::METHOD_BATCH, $arBatch);
	}

	public function getMethods()
	{
		return $this->call(self::METHOD_METHODS);
	}

	public function getProfile()
	{
		return $this->call(self::METHOD_PROFILE);
	}

	public function addProfile($arFields)
	{
		return $this->call(self::METHOD_PROFILE_ADD, $arFields);
	}

	public function checkProfile($arFields)
	{
		return $this->call(self::METHOD_PROFILE_ADD_CHECK, $arFields);
	}

	public function updateProfile($arFields)
	{
		return $this->call(self::METHOD_PROFILE_UPDATE, $arFields);
	}

	public function deleteProfile($ID)
	{
		return $this->call(self::METHOD_PROFILE_DELETE, array("ID" => $ID));
	}

	public function getProfileChannel()
	{
		$this->httpTimeout = 2;
		return $this->call(self::METHOD_PROFILE_CHANNEL);
	}
}

class CBitrix24NetPortalTransport extends CBitrix24NetTransport
{
	protected $clientId = null;
	protected $clientSecret = null;

	public static function init()
	{
		$result = parent::init();

		if(!$result)
		{
			$interface = new CBitrix24NetOAuthInterface();
			$result = new self($interface->getAppID(), $interface->getAppSecret());
		}

		return $result;
	}

	public function __construct($clientId, $clientSecret)
	{
		$this->clientId = $clientId;
		$this->clientSecret = $clientSecret;

		return parent::__construct('');
	}

	protected function prepareRequest(array $request)
	{
		$request["client_id"] = $this->clientId;
		$request["client_secret"] = $this->clientSecret;

		return $this->convertRequest($request);
	}

}
