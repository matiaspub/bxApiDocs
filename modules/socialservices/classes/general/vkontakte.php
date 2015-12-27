<?
IncludeModuleLangFile(__FILE__);

class CSocServVKontakte extends CSocServAuth
{
	const ID = "VKontakte";
	const CONTROLLER_URL = "https://www.bitrix24.ru/controller";

	protected $entityOAuth = null;

	static public function GetSettings()
	{
		return array(
			array("vkontakte_appid", GetMessage("socserv_vk_id"), "", Array("text", 40)),
			array("vkontakte_appsecret", GetMessage("socserv_vk_key"), "", Array("text", 40)),
			array("note"=>GetMessage("socserv_vk_sett_note")),
		);
	}

	public function GetFormHtml($arParams)
	{
		$url = $this->getUrl($arParams);

		$phrase = ($arParams["FOR_INTRANET"]) ? GetMessage("socserv_vk_note_intranet") : GetMessage("socserv_vk_note");
		if($arParams["FOR_INTRANET"])
			return array("ON_CLICK" => 'onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 660, 425)"');
		return '<a href="javascript:void(0)" onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 660, 425)" class="bx-ss-button vkontakte-button"></a><span class="bx-spacer"></span><span>'.$phrase.'</span>';
	}

	public function GetOnClickJs($arParams)
	{
		$url = $this->getUrl($arParams);
		return "BX.util.popup('".CUtil::JSEscape($url)."', 660, 425)";
	}

	public function getUrl($arParams)
	{
		global $APPLICATION;

		$gAuth = $this->getEntityOAuth();

		if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
		{
			$redirect_uri = self::CONTROLLER_URL."/redirect.php";
			$state = CSocServUtil::ServerName()."/bitrix/tools/oauth/liveid.php?state=";
			$backurl = urlencode($APPLICATION->GetCurPageParam('check_key='.$_SESSION["UNIQUE_KEY"], array("logout", "auth_service_error", "auth_service_id", "backurl")));
			$state .= urlencode(urlencode("backurl=".$backurl));
		}
		else
		{
			//$redirect_uri = CSocServUtil::GetCurUrl('auth_service_id='.self::ID);
			$redirect_uri = CSocServUtil::ServerName().$APPLICATION->GetCurPage().'?auth_service_id='.self::ID;

			$backurl = $APPLICATION->GetCurPageParam(
				'check_key='.$_SESSION["UNIQUE_KEY"],
				array("logout", "auth_service_error", "auth_service_id", "backurl")
			);

			$state = 'site_id='.SITE_ID.'&backurl='.urlencode($backurl).(isset($arParams['BACKURL']) ? '&redirect_url='.urlencode($arParams['BACKURL']) : '');
		}

		return $gAuth->GetAuthUrl($redirect_uri, $state);
	}

	public function getEntityOAuth($code = false)
	{
		if(!$this->entityOAuth)
		{
			$this->entityOAuth = new CVKontakteOAuthInterface();
		}

		if($code !== false)
		{
			$this->entityOAuth->setCode($code);
		}

		return $this->entityOAuth;
	}

	public function prepareUser($arVkUser, $short = false)
	{
		$first_name = $last_name = $gender = "";

		if($arVkUser['response']['0']['first_name'] <> '')
		{
			$first_name = $arVkUser['response']['0']['first_name'];
		}

		if($arVkUser['response']['0']['last_name'] <> '')
		{
			$last_name = $arVkUser['response']['0']['last_name'];
		}

		if(isset($arVkUser['response']['0']['sex']) && $arVkUser['response']['0']['sex'] != '')
		{
			if ($arVkUser['response']['0']['sex'] == '2')
				$gender = 'M';
			elseif ($arVkUser['response']['0']['sex'] == '1')
				$gender = 'F';
		}

		$arFields = array(
			'EXTERNAL_AUTH_ID' => self::ID,
			'XML_ID' => $arVkUser['response']['0']['uid'],
			'LOGIN' => "VKuser".$arVkUser['response']['0']['uid'],
			'EMAIL' => $this->entityOAuth->GetCurrentUserEmail(),
			'NAME'=> $first_name,
			'LAST_NAME'=> $last_name,
			'PERSONAL_GENDER' => $gender,
			'OATOKEN' => $this->entityOAuth->getToken(),
			'OATOKEN_EXPIRES' => $this->entityOAuth->getAccessTokenExpires(),
		);

		if(isset($arVkUser['response']['0']['photo_max_orig']) && self::CheckPhotoURI($arVkUser['response']['0']['photo_max_orig']))
		{
			if(!$short)
			{
				$arPic = CFile::MakeFileArray($arVkUser['response']['0']['photo_max_orig']);
				if($arPic)
				{
					$arFields["PERSONAL_PHOTO"] = $arPic;
				}
			}

			if(isset($arVkUser['response']['0']['bdate']))
			{
				if($date = MakeTimeStamp($arVkUser['response']['0']['bdate'], "DD.MM.YYYY"))
				{
					$arFields["PERSONAL_BIRTHDAY"] = ConvertTimeStamp($date);
				}
			}

			$arFields["PERSONAL_WWW"] = self::getProfileUrl($arVkUser['response']['0']['uid']);

			if(strlen(SITE_ID) > 0)
			{
				$arFields["SITE_ID"] = SITE_ID;
			}
		}

		return $arFields;
	}

	public function Authorize()
	{
		$GLOBALS["APPLICATION"]->RestartBuffer();
		$bSuccess = SOCSERV_AUTHORISATION_ERROR;

		if((isset($_REQUEST["code"]) && $_REQUEST["code"] <> '') && CSocServAuthManager::CheckUniqueKey())
		{
			if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
				$redirect_uri = self::CONTROLLER_URL."/redirect.php";
			else
				$redirect_uri = CSocServUtil::ServerName().$GLOBALS['APPLICATION']->GetCurPage().'?auth_service_id='.self::ID;

			$this->entityOAuth = $this->getEntityOAuth($_REQUEST['code']);
			if($this->entityOAuth->GetAccessToken($redirect_uri) !== false)
			{
				$arVkUser = $this->entityOAuth->GetCurrentUser();
				if(is_array($arVkUser) && ($arVkUser['response']['0']['uid'] <> ''))
				{
					$arFields = $this->prepareUser($arVkUser);
					$bSuccess = $this->AuthorizeUser($arFields);
				}
			}
		}

		$url = ($GLOBALS["APPLICATION"]->GetCurDir() == "/login/") ? "" : $GLOBALS["APPLICATION"]->GetCurDir();
		$aRemove = array("logout", "auth_service_error", "auth_service_id", "code", "error_reason", "error", "error_description", "check_key", "current_fieldset");


		if(isset($_REQUEST['backurl']) || isset($_REQUEST['redirect_url']))
		{
			$parseUrl = parse_url(isset($_REQUEST['redirect_url']) ? $_REQUEST['redirect_url'] : $_REQUEST['backurl']);

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

		if($bSuccess === SOCSERV_REGISTRATION_DENY)
		{
			$url = (preg_match("/\?/", $url)) ? $url.'&' : $url.'?';
			$url .= 'auth_service_id='.self::ID.'&auth_service_error='.$bSuccess;
		}
		elseif($bSuccess !== true)
		{
			$url = (isset($urlPath)) ? $urlPath.'?auth_service_id='.self::ID.'&auth_service_error='.$bSuccess : $GLOBALS['APPLICATION']->GetCurPageParam(('auth_service_id='.self::ID.'&auth_service_error='.$bSuccess), $aRemove);
		}

		if(CModule::IncludeModule("socialnetwork") && strpos($url, "current_fieldset=") === false)
		{
			$url = (preg_match("/\?/", $url)) ? $url."&current_fieldset=SOCSERV" : $url."?current_fieldset=SOCSERV";
		}

		echo '
<script type="text/javascript">
if(window.opener)
{
	window.opener.location = \''.CUtil::JSEscape($url).'\';
}
window.close();
</script>
';
		die();
	}

	public function getFriendsList($limit, &$next)
	{
		if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
			$redirect_uri = self::CONTROLLER_URL."/redirect.php";
		else
			$redirect_uri = CSocServUtil::ServerName().$GLOBALS['APPLICATION']->GetCurPage().'?auth_service_id='.self::ID;

		$vk = $this->getEntityOAuth();
		if($vk->GetAccessToken($redirect_uri) !== false)
		{
			$res = $vk->getCurrentUserFriends($limit, $next);
			if(is_array($res) && is_array($res['response']))
			{
				foreach($res['response'] as $key => $contact)
				{
					$res['response'][$key]['name'] = $contact["first_name"];
					$res['response'][$key]['url'] = "https://vk.com/id".$contact["uid"];
					$res['response'][$key]['picture'] = $contact['photo_200_orig'];

				}

				return $res['response'];
			}
		}

		return false;
	}

	public function sendMessage($uid, $message)
	{
		$vk = $this->getEntityOAuth();

		if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
			$redirect_uri = self::CONTROLLER_URL."/redirect.php";
		else
			$redirect_uri = CSocServUtil::ServerName().$GLOBALS['APPLICATION']->GetCurPage().'?auth_service_id='.self::ID;

		if($vk->GetAccessToken($redirect_uri) !== false)
		{
			$res = $vk->sendMessage($uid, $message);
		}

		return $res;
	}

	static public function getProfileUrl($uid)
	{
		return "http://vk.com/id".$uid;
	}
}

class CVKontakteOAuthInterface extends CSocServOAuthTransport
{
	const SERVICE_ID = "VKontakte";

	const AUTH_URL = "https://oauth.vk.com/authorize";
	const TOKEN_URL = "https://oauth.vk.com/access_token";
	const CONTACTS_URL = "https://api.vk.com/method/users.get";
	const FRIENDS_URL = "https://api.vk.com/method/friends.get";
	const MESSAGE_URL = "https://api.vk.com/method/messages.send";
	const APP_URL = "https://api.vk.com/method/apps.get";

	protected $userID = false;
	protected $userEmail = false;

	protected $scope = "friends,notify,offline,email";

	static public function __construct($appID=false, $appSecret=false, $code=false)
	{
		if($appID === false)
		{
			$appID = trim(CSocServVKontakte::GetOption("vkontakte_appid"));
		}

		if($appSecret === false)
		{
			$appSecret = trim(CSocServVKontakte::GetOption("vkontakte_appsecret"));
		}

		parent::__construct($appID, $appSecret, $code);
	}

	public function GetAuthUrl($redirect_uri, $state='')
	{
		return self::AUTH_URL.
			"?client_id=".urlencode($this->appID).
			"&redirect_uri=".urlencode($redirect_uri).
			"&scope=".$this->getScope().
			"&response_type=code".
			($state <> ''? '&state='.urlencode($state):'');
	}

	public function GetAccessToken($redirect_uri)
	{
		$token = $this->getStorageTokens();
		if(is_array($token))
		{
			$this->access_token = $token["OATOKEN"];
			return true;
		}

		if($this->code === false)
		{
			return false;
		}

		$query = array(
			"client_id"=>$this->appID,
			"client_secret"=>$this->appSecret,
			"code"=>$this->code,
			"redirect_uri"=>$redirect_uri,
		);

		$result = CHTTP::sPostHeader(self::TOKEN_URL, $query, array(), $this->httpTimeout);

		$arResult = CUtil::JsObjectToPhp($result);

		if((isset($arResult["access_token"]) && $arResult["access_token"] <> '') && isset($arResult["user_id"]) && $arResult["user_id"] <> '')
		{
			$this->access_token = $arResult["access_token"];
			$this->userID = $arResult["user_id"];
			$this->userEmail = $arResult["email"];

			$_SESSION["OAUTH_DATA"] = array("OATOKEN" => $this->access_token);
			return true;
		}
		return false;
	}

	public function GetCurrentUser()
	{
		if($this->access_token === false)
			return false;

		$result = CHTTP::sGetHeader(self::CONTACTS_URL.'?fields=uid,first_name,last_name,nickname,screen_name,sex,bdate,city,country,timezone,photo,photo_medium,photo_max_orig,photo_rec,email&access_token='.urlencode($this->access_token), array(), $this->httpTimeout);

		if(!defined("BX_UTF"))
			$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);

		return CUtil::JsObjectToPhp($result);
	}

	public function GetAppInfo()
	{
		if($this->access_token === false)
			return false;

		$h = new \Bitrix\Main\Web\HttpClient();
		$h->setTimeout($this->httpTimeout);

		$result = $h->get(self::APP_URL.'?fields=id&access_token='.urlencode($this->access_token));

		$result = \Bitrix\Main\Web\Json::decode($result);

		return $result['response'];
	}

	public function GetCurrentUserEmail()
	{
		return $this->userEmail;
	}

	public function GetCurrentUserFriends($limit, &$next)
	{
		if($this->access_token === false)
			return false;

		$url = self::FRIENDS_URL.'?uids='.$this->userID.'&fields=uid,first_name,last_name,nickname,screen_name,photo_200_orig,contacts,email&access_token='.urlencode($this->access_token);

		if($limit > 0)
		{
			$url .= "&count=".intval($limit)."&offset=".intval($next);
		}

		$result = CHTTP::sGetHeader($url, array(), $this->httpTimeout);

		if(!defined("BX_UTF"))
		{
			$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);
		}

		$result = CUtil::JsObjectToPhp($result);

		$next = $limit + $next;

		return $result;
	}

	public function sendMessage($uid, $message)
	{
		if($this->access_token === false)
			return false;

		$url = self::MESSAGE_URL;

		$message = CharsetConverter::ConvertCharset($message, LANG_CHARSET, "utf-8");

		$arPost = array(
			"user_id" => $uid,
			"access_token" => $this->access_token,
			"message"=> $message,
		);

		$ob = new \Bitrix\Main\Web\HttpClient();
		return $ob->post($url, $arPost);
	}
}
?>
