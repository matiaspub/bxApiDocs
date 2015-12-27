<?
IncludeModuleLangFile(__FILE__);

class CSocServFacebook extends CSocServAuth
{
	const ID = "Facebook";
	const CONTROLLER_URL = "https://www.bitrix24.ru/controller";

	protected $entityOAuth = null;

	static public function GetSettings()
	{
		return array(
			array("facebook_appid", GetMessage("socserv_fb_id"), "", Array("text", 40)),
			array("facebook_appsecret", GetMessage("socserv_fb_secret"), "", Array("text", 40)),
			array("note"=>GetMessage("socserv_fb_sett_note")),
		);
	}

	public function GetFormHtml($arParams)
	{
		$url = $this->getUrl($arParams);

		$phrase = ($arParams["FOR_INTRANET"])
			? GetMessage("socserv_fb_note_intranet")
			: GetMessage("socserv_fb_note");

		return $arParams["FOR_INTRANET"]
			? array("ON_CLICK" => 'onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 580, 400)"')
			: '<a href="javascript:void(0)" onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 580, 400)" class="bx-ss-button facebook-button"></a><span class="bx-spacer"></span><span>'.$phrase.'</span>';
	}

	public function GetOnClickJs($arParams)
	{
		$url = $this->getUrl($arParams);
		return "BX.util.popup('".CUtil::JSEscape($url)."', 680, 600)";
	}

	public function getUrl($arParams)
	{
		if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
		{
			$redirect_uri = self::CONTROLLER_URL."/redirect.php?redirect_to=".urlencode(CSocServUtil::GetCurUrl('auth_service_id='.self::ID.'&check_key='.$_SESSION["UNIQUE_KEY"]));
		}
		else
		{
			$redirect_uri = CSocServUtil::GetCurUrl('auth_service_id='.self::ID.'&check_key='.$_SESSION["UNIQUE_KEY"]);

			if(isset($arParams['BACKURL']) && !preg_match("/&backurl=/", $redirect_uri))
			{
				$redirect_uri .= '&backurl='.urlencode($arParams['BACKURL']);
			}
		}

		return $this->getEntityOAuth()->GetAuthUrl($redirect_uri);
	}

	public function getEntityOAuth($code = false)
	{
		if(!$this->entityOAuth)
		{
			$this->entityOAuth = new CFacebookInterface();
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

	public function prepareUser($arFBUser, $short = false)
	{
		$arFields = array(
			'EXTERNAL_AUTH_ID' => self::ID,
			'XML_ID' => $arFBUser["id"],
			'LOGIN' => "FB_".$arFBUser["id"],
			'EMAIL' => ($arFBUser["email"] != '') ? $arFBUser["email"] : '',
			'NAME'=> $arFBUser["first_name"],
			'LAST_NAME'=> $arFBUser["last_name"],
			'OATOKEN' => $this->entityOAuth->getToken(),
			'OATOKEN_EXPIRES' => $this->entityOAuth->getAccessTokenExpires(),
		);

		if(!$short && isset($arFBUser['picture']['data']['url']) && !$arFBUser['picture']['data']['is_silhouette'])
		{
			$picture_url = CFacebookInterface::GRAPH_URL.'/'.$arFBUser['id'].'/picture?type=large';
			$temp_path = CFile::GetTempName('', 'picture.jpg');

			$ob = new \Bitrix\Main\Web\HttpClient(array(
				"redirect" => true
			));
			$ob->download($picture_url, $temp_path);

			$arPic = CFile::MakeFileArray($temp_path);
			if($arPic)
			{
				$arFields["PERSONAL_PHOTO"] = $arPic;
			}
		}

		if(isset($arFBUser['birthday']))
		{
			if($date = MakeTimeStamp($arFBUser['birthday'], "MM/DD/YYYY"))
			{
				$arFields["PERSONAL_BIRTHDAY"] = ConvertTimeStamp($date);
			}
		}

		if(isset($arFBUser['gender']) && $arFBUser['gender'] != '')
		{
			if($arFBUser['gender'] == 'male')
			{
				$arFields["PERSONAL_GENDER"] = 'M';
			}
			elseif($arFBUser['gender'] == 'female')
			{
				$arFields["PERSONAL_GENDER"] = 'F';
			}
		}

		$arFields["PERSONAL_WWW"] = $this->getProfileUrl($arFBUser['id']);

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

		$authError = SOCSERV_AUTHORISATION_ERROR;

		if(
			isset($_REQUEST["code"]) && $_REQUEST["code"] <> ''
			&& CSocServAuthManager::CheckUniqueKey()
		)
		{
			if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
			{
				$redirect_uri = self::CONTROLLER_URL."/redirect.php?redirect_to=".urlencode(CSocServUtil::GetCurUrl('auth_service_id='.self::ID, array("code")));
			}
			else
			{
				if(isset($_SESSION["FACEBOOK_OAUTH_LAST_REDIRECT_URI"]))
				{
					$redirect_uri = $_SESSION["FACEBOOK_OAUTH_LAST_REDIRECT_URI"];
					unset($_SESSION["FACEBOOK_OAUTH_LAST_REDIRECT_URI"]);
				}
				else
				{
					$redirect_uri = CSocServUtil::GetCurUrl('auth_service_id=' . self::ID, array("code"));
				}
			}

			$this->entityOAuth = $this->getEntityOAuth($_REQUEST['code']);
			if($this->entityOAuth->GetAccessToken($redirect_uri) !== false)
			{
				$arFBUser = $this->entityOAuth->GetCurrentUser();
				if(is_array($arFBUser) && isset($arFBUser["id"]))
				{
					$arFields = self::prepareUser($arFBUser);
					$authError = $this->AuthorizeUser($arFields);
				}
			}
		}

		$bSuccess = $authError === true;

		$aRemove = array("logout", "auth_service_error", "auth_service_id", "code", "error_reason", "error", "error_description", "check_key", "current_fieldset");

		if($bSuccess)
		{
			CSocServUtil::checkOAuthProxyParams();

			$url = ($GLOBALS["APPLICATION"]->GetCurDir() == "/login/") ? "" : $GLOBALS["APPLICATION"]->GetCurDir();

			if(isset($_REQUEST['backurl']))
			{
				$parseUrl = parse_url($_REQUEST['backurl']);

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
		}

		if($authError === SOCSERV_REGISTRATION_DENY)
		{
			$url = (preg_match("/\?/", $url)) ? $url.'&' : $url.'?';
			$url .= 'auth_service_id='.self::ID.'&auth_service_error='.$authError;
		}
		elseif($bSuccess !== true)
		{
			$url = (isset($urlPath)) ? $urlPath.'?auth_service_id='.self::ID.'&auth_service_error='.$authError : $GLOBALS['APPLICATION']->GetCurPageParam(('auth_service_id='.self::ID.'&auth_service_error='.$authError), $aRemove);
		}

		if(CModule::IncludeModule("socialnetwork") && strpos($url, "current_fieldset=") === false)
		{
			$url .= ((strpos($url, "?") === false) ? '?' : '&')."current_fieldset=SOCSERV";
		}
?>
<script type="text/javascript">
if(window.opener)
	window.opener.location = '<?=CUtil::JSEscape($url)?>';
window.close();
</script>
<?
		die();
	}

	public function getFriendsList($limit, &$next)
	{
		$fb = new CFacebookInterface();

		if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
		{
			$redirect_uri = self::CONTROLLER_URL."/redirect.php?redirect_to=".urlencode(CSocServUtil::GetCurUrl('auth_service_id='.self::ID, array("code")));
		}
		else
		{
			$redirect_uri = CSocServUtil::GetCurUrl('auth_service_id='.self::ID, array("code"));
		}

		if($fb->GetAccessToken($redirect_uri) !== false)
		{
			$res = $fb->GetCurrentUserFriends($limit, $next);
			if(is_array($res))
			{
				foreach($res['data'] as $key => $value)
				{
					$res['data'][$key]['uid'] = $value['id'];
					$res['data'][$key]['url'] = $this->getProfileUrl($value['id']);

					if(is_array($value['picture']))
					{
						if(!$value['picture']['data']['is_silhouette'])
						{
							$res['data'][$key]['picture'] = CFacebookInterface::GRAPH_URL.'/'.$value['id'].'/picture?type=large';
						}
						else
						{
							$res['data'][$key]['picture'] = '';
						}
						//$res['data'][$key]['picture'] = $value['picture']['data']['url'];
					}
				}

				return $res['data'];
			}
		}

		return false;
	}

	static public function sendMessage($uid, $message)
	{
		$fb = new CFacebookInterface();

		if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
		{
			$redirect_uri = self::CONTROLLER_URL."/redirect.php?redirect_to=".urlencode(CSocServUtil::GetCurUrl('auth_service_id='.self::ID, array("code")));
		}
		else
		{
			$redirect_uri = CSocServUtil::GetCurUrl('auth_service_id='.self::ID, array("code"));
		}

		if($fb->GetAccessToken($redirect_uri) !== false)
		{
			$res = $fb->sendMessage($uid, $message);
		}


		return $res;
	}

	static public function getMessages($uid)
	{
		$fb = new CFacebookInterface();

		if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
		{
			$redirect_uri = self::CONTROLLER_URL."/redirect.php?redirect_to=".urlencode(CSocServUtil::GetCurUrl('auth_service_id='.self::ID, array("code")));
		}
		else
		{
			$redirect_uri = CSocServUtil::GetCurUrl('auth_service_id='.self::ID, array("code"));
		}

		if($fb->GetAccessToken($redirect_uri) !== false)
		{
			$res = $fb->getMessages($uid);
		}

		return $res;
	}
	static public function getProfileUrl($uid)
	{
		return "http://www.facebook.com/".$uid;
	}

	public static function SendUserFeed($userId, $message, $messageId)
	{
		$fb = new CFacebookInterface();
		return $fb->SendFeed($userId, $message, $messageId);
	}

}

class CFacebookInterface extends CSocServOAuthTransport
{
	const SERVICE_ID = "Facebook";

	const AUTH_URL = "https://www.facebook.com/dialog/oauth";
	const GRAPH_URL = "https://graph.facebook.com";

	protected $userId = false;

	protected $scope = "email,publish_actions";

	static public function __construct($appID = false, $appSecret = false, $code=false)
	{
		if($appID === false)
		{
			$appID = trim(CSocServFacebook::GetOption("facebook_appid"));
		}

		if($appSecret === false)
		{
			$appSecret = trim(CSocServFacebook::GetOption("facebook_appsecret"));
		}

		parent::__construct($appID, $appSecret, $code);
	}

	public function GetAuthUrl($redirect_uri)
	{
		$_SESSION["FACEBOOK_OAUTH_LAST_REDIRECT_URI"] = $redirect_uri;

		return self::AUTH_URL."?client_id=".$this->appID."&redirect_uri=".urlencode($redirect_uri)."&scope=".$this->getScope()."&display=popup";
	}

	public function GetAccessToken($redirect_uri)
	{
		$token = $this->getStorageTokens();
		if(is_array($token))
		{
			$this->access_token = $token["OATOKEN"];
			$this->accessTokenExpires = $token["OATOKEN_EXPIRES"];

			if($this->checkAccessToken())
			{
				return true;
			}
		}

		if($this->code === false)
		{
			return false;
		}

		$result = CHTTP::sGetHeader(self::GRAPH_URL.'/oauth/access_token?client_id='.$this->appID.'&client_secret='.$this->appSecret.'&redirect_uri='.urlencode($redirect_uri).'&code='.urlencode($this->code), array(), $this->httpTimeout);

		$arResult = array();
		$arResultLongLive = array();
		parse_str($result, $arResult);
		if(isset($arResult["access_token"]) && $arResult["access_token"] <> '')
		{
			$result = CHTTP::sGetHeader(self::GRAPH_URL."/oauth/access_token?grant_type=fb_exchange_token&client_id=".$this->appID."&client_secret=".$this->appSecret."&fb_exchange_token=".$arResult["access_token"], array(), $this->httpTimeout);
			parse_str($result, $arResultLongLive);
			if(isset($arResultLongLive["access_token"]) && $arResultLongLive["access_token"] <> '')
			{
				$arResult["access_token"] = $arResultLongLive["access_token"];
				$arResult["expires"] = $arResultLongLive["expires"];
				$_SESSION["OAUTH_DATA"] = array(
					"OATOKEN" => $arResultLongLive["access_token"],
					"OATOKEN_EXPIRES" => time() + $arResultLongLive['expires'],
				);
			}

			$this->access_token = $arResult["access_token"];
			$this->accessTokenExpires = time() + $arResult["expires"];

			return true;
		}
		return false;
	}

	public function GetCurrentUser()
	{
		if($this->access_token === false)
			return false;

		$result = CHTTP::sGetHeader(self::GRAPH_URL.'/me?access_token='.$this->access_token."&fields=picture,id,name,first_name,last_name,gender,birthday,email", array(), $this->httpTimeout);

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

		$result = $h->get(self::GRAPH_URL.'/debug_token?input_token='.$this->access_token.'&access_token='.$this->appID."|".$this->appSecret);
		$result = \Bitrix\Main\Web\Json::decode($result);

		if($result["data"]["app_id"])
		{
			$result["id"] = $result["data"]["app_id"];
		}

		return $result;
	}

	public function GetCurrentUserFriends($limit, &$next)
	{
		if($this->access_token === false)
			return false;

		if(empty($next))
		{
			$url = self::GRAPH_URL.'/me/friends?access_token='.$this->access_token."&fields=picture,id,name,first_name,last_name,gender,birthday,email";

			if($limit > 0)
			{
				$url .= "&limit=".intval($limit)."&offset=".intval($next);
			}
		}
		else
		{
			$url = $next;
		}

		$result = CHTTP::sGetHeader($url, array(), $this->httpTimeout);

		if(!defined("BX_UTF"))
			$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);

		$result = CUtil::JsObjectToPhp($result);

		if(is_array($result['paging']) && !empty($result['paging']['next']))
		{
			$next = $result['paging']['next'];
		}
		else
		{
			$next = '';
		}

		return $result;
	}

	public function SendFeed($socServUserId, $message, $messageId)
	{
		$isSetOauthKeys = true;
		if(!$this->access_token || !$this->userId)
			$isSetOauthKeys = self::SetOauthKeys($socServUserId);

		if($isSetOauthKeys === false)
		{
			CSocServMessage::Delete($messageId);
			return false;
		}

		$message = CharsetConverter::ConvertCharset($message, LANG_CHARSET, "utf-8");
		$arPost = array("access_token" => $this->access_token, "message"=> $message);
		$result = @CHTTP::sPostHeader($this::GRAPH_URL."/".$this->userId."/feed", $arPost, array(), $this->httpTimeout);
		if($result !== false)
		{
			if(!defined("BX_UTF"))
				$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);
			return CUtil::JsObjectToPhp($result);
		}
		else
			return false;
	}

	public function sendMessage($uid, $message)
	{
		if($this->access_token === false)
			return false;

		$url = self::GRAPH_URL.'/'.$uid.'/apprequests';

		$message = CharsetConverter::ConvertCharset($message, LANG_CHARSET, "utf-8");
		$arPost = array("access_token" => $this->access_token, "message"=> $message);

		$ob = new \Bitrix\Main\Web\HttpClient();
		return $ob->post($url, $arPost);
	}

	public function getMessages($uid)
	{
		if($this->access_token === false)
			return false;

		$url = self::GRAPH_URL.'/'.$uid.'/apprequests?access_token='.$this->access_token;

		$ob = new \Bitrix\Main\Web\HttpClient();
		return $ob->get($url);
	}

	private function SetOauthKeys($socServUserId)
	{
		$dbSocservUser = CSocServAuthDB::GetList(array(), array('ID' => $socServUserId), false, false, array("OATOKEN", "XML_ID"));
		while($arOauth = $dbSocservUser->Fetch())
		{
			$this->access_token = $arOauth["OATOKEN"];
			$this->userId = $arOauth["XML_ID"];
		}
		if(!$this->access_token || !$this->userId)
			return false;
		return true;
	}
}
?>