<?
IncludeModuleLangFile(__FILE__);

class CSocServBitrixOAuth extends CSocServAuth
{
	const ID = "Bitrix24OAuth";

	/** @var CBitrixOAuthInterface null  */
	protected $entityOAuth = null;
	protected $appID;
	protected $appSecret;
	protected $portalURI = '';
	protected $redirectURI = '';

	protected $userId = null;

	public function __construct($appID, $appSecret, $portalURI, $redirectURI, $userId = null)
	{
		$this->appID = $appID;
		$this->appSecret = $appSecret;
		$this->portalURI = $portalURI;
		$this->redirectURI = $redirectURI;
		$this->userId = $userId;
		$this->entityOAuth = new CBitrixOAuthInterface($this->appID, $this->appSecret, $this->portalURI);
	}

	public function getEntityOAuth()
	{
		return $this->entityOAuth;
	}

	public function addScope($scope)
	{
		return $this->entityOAuth->addScope($scope);
	}


	public function getRequestTokenUrl($location = 'opener')
	{
		return $this->entityOAuth->GetAuthUrl($this->redirectURI);
	}

	public function getAccessToken($code, $addScope = null)
	{
		$this->entityOAuth->setCode($code);
		if($addScope !== null)
			$this->entityOAuth->addScope($addScope);
		$this->entityOAuth->GetAccessToken($this->redirectURI);

		return CUtil::JsObjectToPhp($this->entityOAuth->getToken());
	}

	public function getStorageToken()
	{
		$accessToken = null;
		$userId = intval($this->userId);
		if($userId > 0)
		{
			$dbSocservUser = CSocServAuthDB::GetList(array(), array('USER_ID' => $userId, 'XML_ID' => $this->appID, "EXTERNAL_AUTH_ID" => "Bitrix24OAuth", 'PERSONAL_WWW' => $this->portalURI), false, false, array("OATOKEN", "REFRESH_TOKEN", "OATOKEN_EXPIRES"));
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

	public function Authorize()
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();
		if((isset($_REQUEST["code"]) && $_REQUEST["code"] <> '') && CSocServAuthManager::CheckUniqueKey())
		{
			$redirect_uri = CSocServUtil::ServerName().'/bitrix/tools/oauth/bitrix24.php';
			$userId = intval($_REQUEST['uid']);
			$appID = trim(COption::GetOptionString("socialservices", "bitrix24_gadget_appid", ''));
			$appSecret = trim(COption::GetOptionString("socialservices", "bitrix24_gadget_appsecret", ''));
			$portalURI = $_REQUEST['domain'];
			if(strpos($portalURI, "http://") === false && strpos($portalURI, "https://") === false)
				$portalURI = "https://".$portalURI;
			$gAuth = new CBitrixOAuthInterface($appID, $appSecret, $portalURI, $_REQUEST["code"]);

			$this->entityOAuth = $gAuth;
			$gAuth->addScope(explode(',', $_REQUEST["scope"]));
			if($gAuth->GetAccessToken($redirect_uri) !== false)
			{

			}
		}
		$url = CSocServUtil::ServerName().BX_ROOT;
		$mode = 'opener';
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

	static public function gadgetAuthorize()
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();

		if((isset($_REQUEST["code"]) && $_REQUEST["code"] <> '') && CSocServAuthManager::CheckUniqueKey())
		{
			$userId = intval($_REQUEST['uid']);
			CUserOptions::SetOption('socialservices', 'bitrix24_task_planer_gadget_code', $_REQUEST["code"], false, $userId);
		}
		$url = CSocServUtil::ServerName().BX_ROOT;
		$mode = 'opener';
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

class CBitrixOAuthInterface
{
	protected $appID;
	protected $appSecret;
	protected $code = false;
	protected $access_token = false;
	protected $accessTokenExpires = 0;
	protected $refresh_token = '';
	protected $portalURI = '';
	protected $scope = array(
		'user',
		'entity',
	);

	public function setCode($code)
	{
		$this->code = $code;
	}

	public function setAccessTokenExpires($accessTokenExpires)
	{
		$this->accessTokenExpires = $accessTokenExpires;
	}

	public function setAccessToken($access_token)
	{
		$this->access_token = $access_token;
	}

	public function getAccessTokenExpires()
	{
		return $this->accessTokenExpires;
	}

	public function getRefreshToken()
	{
		return $this->refresh_token;
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
		return implode(',', array_map('urlencode', array_unique($this->getScope())));
	}

	public function __construct($appID, $appSecret, $portalURI, $code = false)
	{
		$this->httpTimeout = SOCSERV_DEFAULT_HTTP_TIMEOUT;
		$this->appID = $appID;
		$this->portalURI = $portalURI;
		$this->appSecret = $appSecret;
		$this->code = $code;
	}

	public function GetAuthUrl($redirect_uri, $state = '')
	{
		return $this->portalURI.'/oauth/authorize/'.
			"?client_id=".urlencode($this->appID).
			"&redirect_uri=".urlencode($redirect_uri).
			"&scope=".$this->getScopeEncode().
			"&response_type=code".
			($this->refresh_token <> '' ? '' : '&approval_prompt=force').
			($state <> '' ? '&state='.urlencode($state) : '');
	}

	public function GetAccessToken($redirect_uri)
	{
		if($this->code === false)
			return false;
		$result = CHTTP::sGetHeader($this->portalURI.'/oauth/token/'.
			'?code='.$this->code.
			'&client_id='.$this->appID.
			'&client_secret='.$this->appSecret.
			'&redirect_uri='.$redirect_uri.
			'&scope='.$this->getScopeEncode().
			'&grant_type=authorization_code',
			array(), $this->httpTimeout);

		$arResult = CUtil::JsObjectToPhp($result);

		if(isset($arResult["access_token"]) && $arResult["access_token"] <> '')
		{
			if(isset($arResult["refresh_token"]) && $arResult["refresh_token"] <> '')
			{
				$this->refresh_token = $arResult["refresh_token"];
			}
			$this->access_token = $arResult["access_token"];
			$this->accessTokenExpires = $arResult["expires_in"];
			$this->saveDataDB();
			return true;
		}
		return false;
	}

	private function checkAccessToken()
	{
		return (($this->accessTokenExpires - 30) < time()) ? false : true;
	}

	private function saveDataDB()
	{
		$dbSocUser = CSocServAuthDB::GetList(array(), array('XML_ID' => $this->appID, 'PERSONAL_WWW' => $this->portalURI, 'EXTERNAL_AUTH_ID' => "Bitrix24OAuth"), false, false, array("ID"));

		if($GLOBALS["USER"]->IsAuthorized() && $GLOBALS["USER"]->GetID())
		{
			$arFields = array(
				'PERSONAL_WWW' => $this->portalURI,
				'XML_ID' => $this->appID,
				'EXTERNAL_AUTH_ID' => "Bitrix24OAuth",
				'USER_ID' => $GLOBALS["USER"]->GetID(),
				'OATOKEN' => $this->access_token,
				'OATOKEN_EXPIRES' => $this->accessTokenExpires + time(),
				'LOGIN' => $this->appID,
			);

			if($this->refresh_token <> '')
				$arFields['REFRESH_TOKEN'] = $this->refresh_token;

			if($arUser = $dbSocUser->Fetch())
			{
				return CSocServAuthDB::Update($arUser["ID"], $arFields);
			}
			else
			{
				return CSocServAuthDB::Add($arFields);
			}
		}
		return true;
	}

	public function getNewAccessToken($refreshToken, $userId = 0, $save = false, $scope = array())
	{
		if($this->appID == false || $this->appSecret == false)
			return false;
		if($scope != null)
			$this->addScope($scope);
		$result = CHTTP::sGetHeader($this->portalURI."/oauth/token/".
			"?client_id=".urlencode($this->appID).
			"&grant_type=refresh_token".
			"&client_secret=".$this->appSecret.
			"&refresh_token=".$refreshToken.
			'&scope='.$this->getScopeEncode(), array(), $this->httpTimeout);

		$arResult = CUtil::JsObjectToPhp($result);

		if(isset($arResult["access_token"]) && $arResult["access_token"] <> '')
		{
			$this->access_token = $arResult["access_token"];
			$this->accessTokenExpires = $arResult["expires_in"];
			$this->refresh_token = $arResult["refresh_token"];
			if($save && intval($userId) > 0)
			{
				CUserOptions::SetOption('socialservices', 'bitrix24_task_planer_gadget_token', $this->access_token, false, $userId);
				CUserOptions::SetOption('socialservices', 'bitrix24_task_planer_gadget_token_expire', $this->accessTokenExpires + time(), false, $userId);
				CUserOptions::SetOption('socialservices', 'bitrix24_task_planer_gadget_refresh_token', $this->refresh_token, false, $userId);
			}
			$this->saveDataDB();
			return true;
		}
		return false;
	}
}

class CBitrixPHPAppTransport
{
	protected $access_token = '';
	protected $portalURI = '';
	protected $httpTimeout = SOCSERV_DEFAULT_HTTP_TIMEOUT;

	public function __construct($access_token, $portalURI)
	{
		$this->access_token = $access_token;
		$this->portalURI = $portalURI;
	}

	protected function prepareAnswer($result)
	{
		if(!defined("BX_UTF"))
			$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);
		return CUtil::JsObjectToPhp($result);
	}

	public function call($methodName, $additionalParams = '')
	{
		$arHeaders = array();

		if(is_array($additionalParams))
		{
			$additionalParams = CHTTP::PrepareData($additionalParams);
		}

		$result = CHTTP::sGetHeader($this->portalURI.'/rest/'.$methodName.'?auth='.$this->access_token.'&'.$additionalParams, $arHeaders, $this->httpTimeout);

		return $this->prepareAnswer($result);
	}

	public function batch($actions)
	{
		$arBatch = array();

		if(is_array($actions))
		{
			foreach($actions as $query_key => $arCmd)
			{
				list($cmd, $arParams) = array_values($arCmd);
				$arBatch['cmd'][$query_key] = $cmd.'?'.CHTTP::PrepareData($arParams);
			}
		}
		$arBatch['auth'] = $this->access_token;
		$batch_url = '/rest/batch';

		$ht = new CHTTP();
		$result = $ht->Post($this->portalURI.$batch_url, $arBatch);

		return $this->prepareAnswer($result);
	}

	public function getAllMethods()
	{
		$arHeaders = array();
		$result = CHTTP::sGetHeader($this->portalURI.'/rest/methods.json?auth='.$this->access_token.'&full=true', $arHeaders, $this->httpTimeout);

		return $this->prepareAnswer($result);
	}

	public function getPlannerTasksId()
	{
		$arHeaders = array();
		$result = CHTTP::sGetHeader($this->portalURI.'/rest/task.planner.getlist?auth='.$this->access_token, $arHeaders, $this->httpTimeout);

		return $this->prepareAnswer($result);
	}

	protected function prepareQuery($method, $arFields)
	{
		if(!is_array($arFields))
			$arFields = array();

		$arFields['action'] = $method;
		$arFields['lang'] = LANGUAGE_ID;

		return $arFields;
	}
}