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

	protected $signature = null;

	public function __construct($appID, $appSecret, $portalURI, $redirectURI, $userId = null)
	{
		$this->appID = $appID;
		$this->appSecret = $appSecret;
		$this->portalURI = $portalURI;
		$this->redirectURI = $redirectURI;
		$this->userId = $userId == null ? $GLOBALS["USER"]->GetID() : $userId;
	}

	public function getEntityOAuth($code = false)
	{
		if(!$this->entityOAuth)
		{
			$this->entityOAuth = new CBitrixOAuthInterface($this->appID, $this->appSecret, $this->portalURI);
		}

		if($code !== false)
		{
			$this->entityOAuth->setCode($code);
		}

		return $this->entityOAuth;
	}

	public function addScope($scope)
	{
		return $this->getEntityOAuth()->addScope($scope);
	}

	public function getRequestTokenUrl()
	{
		return $this->getEntityOAuth()->GetAuthUrl($this->redirectURI);
	}

	public function getAccessToken($code, $addScope = null)
	{
		$this->getEntityOAuth()->setCode($code);
		if($addScope !== null)
		{
			$this->getEntityOAuth()->addScope($addScope);
		}

		$this->getEntityOAuth()->GetAccessToken($this->redirectURI);

		return $this->getEntityOAuth()->getToken();
	}

	public function getStorageToken()
	{
		$accessToken = null;
		$userId = intval($this->userId);
		if($userId > 0)
		{
			$dbSocservUser = CSocServAuthDB::GetList(array(), array('USER_ID' => $userId, 'XML_ID' => $this->appID, "EXTERNAL_AUTH_ID" => "Bitrix24OAuth", 'PERSONAL_WWW' => $this->portalURI), false, false, array("OATOKEN", "REFRESH_TOKEN", "OATOKEN_EXPIRES", "OASECRET"));
			if($arOauth = $dbSocservUser->Fetch())
			{
				$accessToken = $arOauth["OATOKEN"];
				if(
					empty($accessToken)
					|| (
						(intval($arOauth["OATOKEN_EXPIRES"]) > 0)
						&& (intval($arOauth["OATOKEN_EXPIRES"] < intval(time())))
					)
				)
				{
					if(isset($arOauth['REFRESH_TOKEN']))
					{
						$this->getEntityOAuth()->getNewAccessToken($arOauth['REFRESH_TOKEN'], $userId, true);
					}
					if(($accessToken = $this->getEntityOAuth()->getToken()) === false)
					{
						return null;
					}

					$this->getEntityOAuth()->saveDataDB();
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
				$gAuth->saveDataDB();
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
			CUserOptions::SetOption('socialservices', 'bitrix24_task_planer_gadget_code', $_REQUEST["code"]);
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

class CBitrixOAuthInterface extends CSocServOAuthTransport
{
	const SERVICE_ID = 'Bitrix24OAuth';

	protected $appID;
	protected $appSecret;
	protected $code = false;
	protected $access_token = false;
	protected $member_id = false;

	protected $signatureKey = false;

	protected $accessTokenExpires = 0;
	protected $refresh_token = '';
	protected $portalURI = '';
	protected $scope = array(
		'user',
		'entity',
	);

	public function __construct($appID, $appSecret, $portalURI, $code = false)
	{
		$this->portalURI = $portalURI;

		return parent::__construct($appID, $appSecret, $code);
	}

	public function getMemberId()
	{
		return $this->member_id;
	}

	public function getScopeEncode()
	{
		return implode(',', array_map('urlencode', array_unique($this->getScope())));
	}

	public function GetAuthUrl($redirect_uri, $state = '')
	{
		return $this->portalURI.'/oauth/authorize/'.
			"?client_id=".urlencode($this->appID).
			"&redirect_uri=".urlencode($redirect_uri).
			"&scope=".$this->getScopeEncode().
			"&response_type=code".
			($state != '' ? '&state='.urlencode($state) : '');
	}

	public function GetAccessToken($redirect_uri)
	{
		if($this->code === false)
		{
			return false;
		}

		$httpClient = new \Bitrix\Main\Web\HttpClient(array(
			"socketTimeout" => $this->httpTimeout
		));

		$result = $httpClient->get($this->portalURI.'/oauth/token/'.
			'?code='.$this->code.
			'&client_id='.$this->appID.
			'&client_secret='.$this->appSecret.
			'&redirect_uri='.$redirect_uri.
			'&scope='.$this->getScopeEncode().
			'&grant_type=authorization_code');

		$arResult = \Bitrix\Main\Web\Json::decode($result);

		if(isset($arResult["access_token"]) && $arResult["access_token"] <> '')
		{
			$this->access_token = $arResult["access_token"];
			$this->accessTokenExpires = time() + $arResult["expires_in"];
			$this->member_id = $arResult["member_id"];

			if(isset($arResult["refresh_token"]) && $arResult["refresh_token"] <> '')
			{
				$this->refresh_token = $arResult["refresh_token"];
			}

			return true;
		}
		return false;
	}

	public function getNewAccessToken($refreshToken, $userId = 0, $save = false, $scope = array())
	{
		if($this->appID == false || $this->appSecret == false)
		{
			return false;
		}

		if($scope != null)
		{
			$this->addScope($scope);
		}

		$httpClient = new \Bitrix\Main\Web\HttpClient(array(
			"socketTimeout" => $this->httpTimeout
		));

		$result = $httpClient->get($this->portalURI."/oauth/token/".
			"?client_id=".urlencode($this->appID).
			"&grant_type=refresh_token".
			"&client_secret=".$this->appSecret.
			"&refresh_token=".$refreshToken.
			'&scope='.$this->getScopeEncode());

		$arResult = \Bitrix\Main\Web\Json::decode($result);

		if(isset($arResult["access_token"]) && $arResult["access_token"] <> '')
		{
			$this->access_token = $arResult["access_token"];
			$this->accessTokenExpires = $arResult["expires_in"];
			$this->member_id = $arResult["member_id"];

			if(isset($arResult["refresh_token"]) && $arResult["refresh_token"] <> '')
			{
				$this->refresh_token = $arResult["refresh_token"];
			}

			if($save && intval($userId) > 0)
			{
				CUserOptions::SetOption('socialservices', 'bitrix24_task_planer_gadget_token', $this->access_token, false, $userId);
				CUserOptions::SetOption('socialservices', 'bitrix24_task_planer_gadget_token_expire', $this->accessTokenExpires + time(), false, $userId);
				CUserOptions::SetOption('socialservices', 'bitrix24_task_planer_gadget_refresh_token', $this->refresh_token, false, $userId);
			}

			return true;
		}
		return false;
	}


	public function saveDataDB()
	{
		global $USER;

		$dbSocUser = CSocServAuthDB::GetList(array(), array('XML_ID' => $this->appID, 'PERSONAL_WWW' => $this->portalURI, 'EXTERNAL_AUTH_ID' => "Bitrix24OAuth"), false, false, array("ID"));

		if($USER->IsAuthorized())
		{
			$arFields = array(
				'PERSONAL_WWW' => $this->portalURI,
				'XML_ID' => $this->appID,
				'EXTERNAL_AUTH_ID' => static::SERVICE_ID,
				'USER_ID' => $USER->GetID(),
				'OATOKEN' => $this->access_token,
				'OATOKEN_EXPIRES' => $this->accessTokenExpires,
				'OASECRET' => $this->getSignatureKey(),
				'LOGIN' => $this->appID,
			);

			if($this->refresh_token <> '')
			{
				$arFields['REFRESH_TOKEN'] = $this->refresh_token;
			}

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

	public function getSignatureKey()
	{
		if($this->member_id && $this->appSecret)
		{
			$this->signatureKey = md5($this->member_id.$this->appSecret);
		}

		return $this->signatureKey;
	}
}

class CBitrixPHPAppTransport
{
	protected $access_token = '';
	protected $signatureKey = false;

	protected $portalURI = '';
	protected $httpTimeout = SOCSERV_DEFAULT_HTTP_TIMEOUT;

	public function __construct($access_token, $portalURI, $signatureKey = false)
	{
		$this->access_token = $access_token;
		$this->portalURI = $portalURI;
		$this->signatureKey = $signatureKey;
	}

	public function setSignatureKey($signatureKey)
	{
		$this->signatureKey = $signatureKey;
	}

	protected function prepareAnswer($result)
	{
		return \Bitrix\Main\Web\Json::decode($result);
	}

	protected function prepareRequest($params)
	{
		if(is_array($params))
		{
			$params = CHTTP::PrepareData($params);
		}

		return $params;
	}

	public function call($methodName, $additionalParams = '')
	{
		$httpClient = new \Bitrix\Main\Web\HttpClient(array(
			"socketTimeout" => $this->httpTimeout
		));

		$result = $httpClient->post($this->portalURI.'/rest/'.$methodName, 'auth='.$this->access_token.'&'.static::prepareRequest($additionalParams));

		return $this->prepareAnswer($result);
	}

	public function callSigned($methodName, $additionalParams = '')
	{
		if($this->signatureKey)
		{
			$state = RandString(32);

			$result = $this->call($methodName, 'state=' . $state . "&" . static::prepareRequest($additionalParams));

			if(is_array($result) && isset($result["signature"]))
			{
				$signer = new Bitrix\Socialservices\Bitrix24Signer();
				$signer->setKey($this->signatureKey);

				//try
				//{

				$signatureCheck = $signer->unsign($result["signature"]);

				if(
					$signatureCheck["state"] === $state
				)
				{
					foreach($signatureCheck as $key => $value)
					{
						if($key !== "state")
						{
							if($result['result'][$key] !== $value)
							{
								return false;
							}
						}
					}

					unset($result["signature"]);

					return $result;
				}

				//}
				//catch (Bitrix\Main\Security\Sign\BadSignatureException $e)
				//{}
			}
		}

		return false;
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

		$httpClient = new \Bitrix\Main\Web\HttpClient();
		$result = $httpClient->post($this->portalURI.$batch_url, $arBatch);

		return $this->prepareAnswer($result);
	}

	public function getAllMethods()
	{
		return $this->call('methods', array('full' => 'true'));
	}

	public function getPlannerTasksId()
	{
		return $this->call('task.planner.getlist');
	}

	public function getCurrentUser($signatureKey = '')
	{
		if($signatureKey !== '')
		{
			$this->setSignatureKey($signatureKey);
		}

		if($this->signatureKey)
		{
			return $this->callSigned('user.current');
		}
		else
		{
			return $this->call('user.current');
		}
	}
}