<?
use \Bitrix\Main\Web\HttpClient;

IncludeModuleLangFile(__FILE__);

class CSocServGoogleOAuth extends CSocServAuth
{
	const ID = "GoogleOAuth";
	const CONTROLLER_URL = "https://www.bitrix24.ru/controller";
	const LOGIN_PREFIX = "G_";

	/** @var CGoogleOAuthInterface null  */
	protected $entityOAuth = null;

	protected $userId = null;

	public function __construct($userId = null)
	{
		$this->userId = $userId;
	}

	/**
	 * @param string $code=false
	 * @return CGoogleOAuthInterface
	 */
	public function getEntityOAuth($code = false)
	{
		if(!$this->entityOAuth)
		{
			$this->entityOAuth = new CGoogleOAuthInterface();
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
			array("google_appid", GetMessage("socserv_google_client_id"), "", Array("text", 40)),
			array("google_appsecret", GetMessage("socserv_google_client_secret"), "", Array("text", 40)),
			array("note"=>GetMessage("socserv_google_note", array('#URL#'=>CSocServUtil::ServerName()."/bitrix/tools/oauth/google.php"))),
		);
	}

	static public function GetFormHtml($arParams)
	{
		$url = static::getUrl('opener', null, $arParams);

		$phrase = ($arParams["FOR_INTRANET"]) ? GetMessage("socserv_google_form_note_intranet") : GetMessage("socserv_google_form_note");

		if($arParams["FOR_INTRANET"])
		{
			return array("ON_CLICK" => 'onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 580, 400)"');
		}
		else
		{
			return '<a href="javascript:void(0)" onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 580, 400)" class="bx-ss-button google-button"></a><span class="bx-spacer"></span><span>'.$phrase.'</span>';
		}
	}

	static public function GetOnClickJs($arParams)
	{
		$url = static::getUrl('opener', null, $arParams);
		return "BX.util.popup('".CUtil::JSEscape($url)."', 580, 400)";
	}

	public function getUrl($location = 'opener', $addScope = null, $arParams = array())
	{
		$this->entityOAuth = $this->getEntityOAuth();

		if($this->userId == null)
		{
			$this->entityOAuth->setRefreshToken("skip");
		}

		if($addScope !== null)
		{
			$this->entityOAuth->addScope($addScope);
		}
		CSocServAuthManager::SetUniqueKey();
		if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
		{
			$redirect_uri = static::CONTROLLER_URL."/redirect.php";
			$state = CSocServUtil::ServerName()."/bitrix/tools/oauth/google.php?check_key=".$_SESSION["UNIQUE_KEY"]."&state=";
			$backurl = $GLOBALS["APPLICATION"]->GetCurPageParam('', array("logout", "auth_service_error", "auth_service_id", "backurl"));
			$state .= urlencode('provider='.static::ID. "&state=".urlencode("backurl=".urlencode($backurl).'&mode='.$location.(isset($arParams['BACKURL']) ? '&redirect_url='.urlencode($arParams['BACKURL']) : '')));
		}
		else
		{
			$state = 'provider='.static::ID.'&site_id='.SITE_ID.'&backurl='.urlencode($GLOBALS["APPLICATION"]->GetCurPageParam('check_key='.$_SESSION["UNIQUE_KEY"], array("logout", "auth_service_error", "auth_service_id", "backurl"))).'&mode='.$location.(isset($arParams['BACKURL']) ? '&redirect_url='.urlencode($arParams['BACKURL']) : '');
			$redirect_uri = CSocServUtil::ServerName()."/bitrix/tools/oauth/google.php";
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

	public function prepareUser($arGoogleUser, $short = false)
	{
		$first_name = "";
		$last_name = "";
		if(is_array($arGoogleUser['name']))
		{
			$first_name = $arGoogleUser['name']['givenName'];
			$last_name = $arGoogleUser['name']['familyName'];
		}
		elseif($arGoogleUser['name'] <> '')
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

		$id = $arGoogleUser['id'];
		$email = $arGoogleUser['email'];

		if(strlen($arGoogleUser['email']) > 0)
		{
			$dbRes = \Bitrix\Main\UserTable::getList(array(
				'filter' => array(
					'=EXTERNAL_AUTH_ID' => 'socservices',
					'=XML_ID' => $email,
				),
				'select' => array('ID'),
				'limit' => 1
			));
			if($dbRes->fetch())
			{
				$id = $email;
			}
		}

		$arFields = array(
			'EXTERNAL_AUTH_ID' => static::ID,
			'XML_ID' => $id,
			'LOGIN' => static::LOGIN_PREFIX.$id,
			'EMAIL' => $email,
			'NAME'=> $first_name,
			'LAST_NAME'=> $last_name,
			'OATOKEN' => $this->entityOAuth->getToken(),
			'OATOKEN_EXPIRES' => $this->entityOAuth->getAccessTokenExpires(),
			'REFRESH_TOKEN' => $this->entityOAuth->getRefreshToken(),
		);

		if($arGoogleUser['gender'] <> '')
		{
			if($arGoogleUser['gender'] == 'male')
			{
				$arFields["PERSONAL_GENDER"] = 'M';
			}
			elseif($arGoogleUser['gender'] == 'female')
			{
				$arFields["PERSONAL_GENDER"] = 'F';
			}
		}

		if(!$short && isset($arGoogleUser['picture']) && static::CheckPhotoURI($arGoogleUser['picture']))
		{
			$arGoogleUser['picture'] = preg_replace("/\?.*$/", '', $arGoogleUser['picture']);
			$arPic = CFile::MakeFileArray($arGoogleUser['picture']);
			if($arPic)
			{
				$arFields["PERSONAL_PHOTO"] = $arPic;
			}
		}

		$arFields["PERSONAL_WWW"] = isset($arGoogleUser['link'])
			? $arGoogleUser['link']
			: $arGoogleUser['url'];

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
			if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
			{
				$redirect_uri = static::CONTROLLER_URL."/redirect.php";
			}
			else
			{
				$redirect_uri = CSocServUtil::ServerName()."/bitrix/tools/oauth/google.php";
			}

			$bProcessState = true;

			$this->entityOAuth = $this->getEntityOAuth($_REQUEST['code']);
			if($this->entityOAuth->GetAccessToken($redirect_uri) !== false)
			{
				$arGoogleUser = $this->entityOAuth->GetCurrentUser();

				if(is_array($arGoogleUser) && !isset($arGoogleUser["error"]))
				{
					$arFields = self::prepareUser($arGoogleUser);
					$authError = $this->AuthorizeUser($arFields);
					$bSuccess = $authError === true;
				}
			}
		}

		if(!$bProcessState)
		{
			unset($_REQUEST["state"]);
		}

		$url = ($APPLICATION->GetCurDir() == "/login/") ? "" : $APPLICATION->GetCurDir();
		$aRemove = array("logout", "auth_service_error", "auth_service_id", "code", "error_reason", "error", "error_description", "check_key", "current_fieldset", "state");

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
							if(strpos($value, $param . "=") === 0)
							{
								unset($arUrlQuery[$key]);
								break;
							}
						}
					}

					$url = (!empty($arUrlQuery)) ? $urlPath . '?' . implode("&", $arUrlQuery) : $urlPath;
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

	public function getFriendsList($limit, &$next)
	{
		if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
		{
			$redirect_uri = static::CONTROLLER_URL."/redirect.php";
		}
		else
		{
			$redirect_uri = CSocServUtil::ServerName()."/bitrix/tools/oauth/google.php";
		}

		$ob = $this->getEntityOAuth();
		if($ob->GetAccessToken($redirect_uri) !== false)
		{
			$res = $ob->getCurrentUserFriends($limit, $next);

			foreach($res as $key => $contact)
			{
				$contact['uid'] = $contact['email'];

				$arName = $contact['name'];

				$contact['first_name'] = trim($arName['givenName']);
				$contact['last_name'] = trim($arName['familyName']);
				$contact['second_name'] = trim($arName['additionalName']);

				if(!$contact['first_name'] && !$contact['last_name'])
				{
					$contact['first_name'] = $contact['uid'];
				}

				$res[$key] = $contact;
			}
		}

		return $res;
	}
}

class CGoogleOAuthInterface extends CSocServOAuthTransport
{
	const SERVICE_ID = "GoogleOAuth";

	const AUTH_URL = "https://accounts.google.com/o/oauth2/auth";
	const TOKEN_URL = "https://accounts.google.com/o/oauth2/token";
	const CONTACTS_URL = "https://www.googleapis.com/oauth2/v1/userinfo";
	const FRIENDS_URL = "https://www.google.com/m8/feeds/contacts/default/full";
	const TOKENINFO_URL = "https://www.googleapis.com/oauth2/v2/tokeninfo";

	protected $scope = array(
		'https://www.googleapis.com/auth/userinfo.email',
		'https://www.googleapis.com/auth/userinfo.profile',
		'https://www.google.com/m8/feeds',
	);

	protected $arResult = array();

	static public function __construct($appID = false, $appSecret = false, $code = false)
	{
		if($appID === false)
		{
			$appID = trim(CSocServGoogleOAuth::GetOption("google_appid"));
		}

		if($appSecret === false)
		{
			$appSecret = trim(CSocServGoogleOAuth::GetOption("google_appsecret"));
		}

		parent::__construct($appID, $appSecret, $code);
	}

	public function getScopeEncode()
	{
		return implode('+', array_map('urlencode', array_unique($this->getScope())));
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
		return static::AUTH_URL.
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
					if($this->getNewAccessToken($tokens["REFRESH_TOKEN"]))
					{
						return true;
					}
				}
			}

			$this->deleteStorageTokens();
		}


		if($this->code === false)
			return false;

		$result = CHTTP::sPostHeader(static::TOKEN_URL, array(
			"code"=>$this->code,
			"client_id"=>$this->appID,
			"client_secret"=>$this->appSecret,
			"redirect_uri"=>$redirect_uri,
			"grant_type"=>"authorization_code",
		), array(), $this->httpTimeout);
		$this->arResult = CUtil::JsObjectToPhp($result);

		if(isset($this->arResult["access_token"]) && $this->arResult["access_token"] <> '')
		{
			if(isset($this->arResult["refresh_token"]) && $this->arResult["refresh_token"] <> '')
			{
				$this->refresh_token = $this->arResult["refresh_token"];
			}
			$this->access_token = $this->arResult["access_token"];
			$this->accessTokenExpires = $this->arResult["expires_in"] + time();

			$_SESSION["OAUTH_DATA"] = array(
				"OATOKEN" => $this->access_token,
				"OATOKEN_EXPIRES" => $this->accessTokenExpires,
				"REFRESH_TOKEN" => $this->refresh_token,
			);

			return true;
		}
		return false;
	}

	public function GetCurrentUser()
	{
		if($this->access_token === false)
			return false;

		$h = new HttpClient(array(
			"socketTimeout" => $this->httpTimeout,
		));
		$result = $h->get(static::CONTACTS_URL.'?access_token='.urlencode($this->access_token));
		$result = \Bitrix\Main\Web\Json::decode($result);

		if(is_array($result))
		{
			$result["access_token"] = $this->access_token;
			$result["refresh_token"] = $this->refresh_token;
			$result["expires_in"] = $this->accessTokenExpires;
		}

		return $result;
	}

	public function GetAppInfo()
	{
		if($this->access_token === false)
			return false;

		$h = new \Bitrix\Main\Web\HttpClient();
		$h->setTimeout($this->httpTimeout);

		$result = $h->post(static::TOKENINFO_URL.'?access_token='.urlencode($this->access_token));

		$result = \Bitrix\Main\Web\Json::decode($result);

		if(is_array($result) && $result["audience"])
		{
			$result["id"] = $result["audience"];
		}

		return $result;
	}

	public function GetCurrentUserFriends($limit, &$next)
	{
		if($this->access_token === false)
			return false;

		$http = new HttpClient();
		$http->setHeader('GData-Version', '3.0');
		$http->setHeader('Authorization', 'Bearer '.$this->access_token);

		$url = static::FRIENDS_URL.'?';

		$limit = intval($limit);
		$next = intval($next);

		if($limit > 0)
		{
			$url .= '&max-results='.$limit;
		}

		if($next > 0)
		{
			$url .= '&start-index='.$next;
		}

		$result = $http->get($url);

		if(!defined("BX_UTF"))
			$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);

		if($http->getStatus() == 200)
		{
			$obXml = new \CDataXML();
			if($obXml->loadString($result))
			{
				$tree = $obXml->getTree();

				$total = $tree->elementsByName("totalResults");
				$total = intval($total[0]->textContent());

				$limitNode = $tree->elementsByName("itemsPerPage");
				$next += intval($limitNode[0]->textContent());

				if($next >= $total)
				{
					$next = '__finish__';
				}

				$arFriends = array();
				$arEntries = $tree->elementsByName('entry');
				foreach($arEntries as $entry)
				{
					$arEntry = array();
					$entryChildren = $entry->children();

					foreach ($entryChildren as $child)
					{
						$tag = $child->name();

						switch($tag)
						{
							case 'category':
							case 'updated':
							case 'edited';
								break;

							case 'name':
								$arEntry[$tag] = array();
								foreach($child->children() as $subChild)
								{
									$arEntry[$tag][$subChild->name()] = $subChild->textContent();
								}
							break;

							case 'email':

								if($child->getAttribute('primary') == 'true')
								{
									$arEntry[$tag] = $child->getAttribute('address');
								}

							break;
							default:

								$tagContent = $tag == 'link'
									? $child->getAttribute('href')
									: $child->textContent();

								if($child->getAttribute('rel'))
								{
									if(!isset($arEntry[$tag]))
									{
										$arEntry[$tag] = array();
									}

									$arEntry[$tag][preg_replace("/^[^#]*#/", "", $child->getAttribute('rel'))] = $tagContent;
								}
								elseif(isset($arEntry[$tag]))
								{
									if(!is_array($arEntry[$tag][0]) || !isset($arEntry[$tag][0]))
									{
										$arEntry[$tag] = array($arEntry[$tag], $tagContent);
									}
									else
									{
										$arEntry[$tag][] = $tagContent;
									}
								}
								else
								{
									$arEntry[$tag] = $tagContent;
								}
						}
					}

					if($arEntry['email'])
					{
						$arFriends[] = $arEntry;
					}
				}
				return $arFriends;
			}
		}

		return false;
	}

	public function getNewAccessToken($refreshToken = false, $userId = 0, $save = false)
	{
		if($this->appID == false || $this->appSecret == false)
			return false;

		if($refreshToken == false)
			$refreshToken = $this->refresh_token;

		$result = CHTTP::sPostHeader(static::TOKEN_URL, array(
			"refresh_token"=>$refreshToken,
			"client_id"=>$this->appID,
			"client_secret"=>$this->appSecret,
			"grant_type"=>"refresh_token",
		), array(), $this->httpTimeout);

		$this->arResult = CUtil::JsObjectToPhp($result);

		if(isset($this->arResult["access_token"]) && $this->arResult["access_token"] <> '')
		{
			$this->access_token = $this->arResult["access_token"];
			$this->accessTokenExpires = $this->arResult["expires_in"] + time();
			if($save && intval($userId) > 0)
			{
				$dbSocservUser = CSocServAuthDB::GetList(array(), array('USER_ID' => intval($userId), "EXTERNAL_AUTH_ID" => static::SERVICE_ID), false, false, array("ID"));
				if($arOauth = $dbSocservUser->Fetch())
					CSocServAuthDB::Update($arOauth["ID"], array("OATOKEN" => $this->access_token,"OATOKEN_EXPIRES" => $this->accessTokenExpires));
			}
			return true;
		}
		return false;
	}
}