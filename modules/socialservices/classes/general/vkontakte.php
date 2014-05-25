<?
IncludeModuleLangFile(__FILE__);

class CSocServVKontakte extends CSocServAuth
{
	const ID = "VKontakte";
	const CONTROLLER_URL = "https://www.bitrix24.ru/controller";


	static public function GetSettings()
	{
		return array(
			array("vkontakte_appid", GetMessage("socserv_vk_id"), "", Array("text", 40)),
			array("vkontakte_appsecret", GetMessage("socserv_vk_key"), "", Array("text", 40)),
			array("note"=>GetMessage("socserv_vk_sett_note")),
		);
	}

	static public function GetFormHtml($arParams)
	{
		$appID = trim(self::GetOption("vkontakte_appid"));
		$appSecret = trim(self::GetOption("vkontakte_appsecret"));

		$gAuth = new CVKontakteOAuthInterface($appID, $appSecret);

		if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
		{
			$redirect_uri = self::CONTROLLER_URL."/redirect.php";
			$state = CSocServUtil::ServerName()."/bitrix/tools/oauth/liveid.php?state=";
			$backurl = urlencode($GLOBALS["APPLICATION"]->GetCurPageParam('check_key='.$_SESSION["UNIQUE_KEY"], array("logout", "auth_service_error", "auth_service_id", "backurl")));
			$state .= urlencode(urlencode("backurl=".$backurl));
		}
		else
		{
			//$redirect_uri = CSocServUtil::GetCurUrl('auth_service_id='.self::ID);
			$redirect_uri = CSocServUtil::ServerName().$GLOBALS['APPLICATION']->GetCurPage(true).'?auth_service_id='.self::ID;
			$state = urlencode('site_id='.SITE_ID.'&backurl='.urlencode($GLOBALS["APPLICATION"]->GetCurPageParam('check_key='.$_SESSION["UNIQUE_KEY"], array("logout", "auth_service_error", "auth_service_id", "backurl"))));
		}

		$url = $gAuth->GetAuthUrl($redirect_uri, $state);
		$phrase = ($arParams["FOR_INTRANET"]) ? GetMessage("socserv_vk_note_intranet") : GetMessage("socserv_vk_note");
		if($arParams["FOR_INTRANET"])
			return array("ON_CLICK" => 'onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 580, 400)"');
		return '<a href="javascript:void(0)" onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 580, 400)" class="bx-ss-button vkontakte-button"></a><span class="bx-spacer"></span><span>'.$phrase.'</span>';

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
				$redirect_uri = CSocServUtil::ServerName().$GLOBALS['APPLICATION']->GetCurPage(true).'?auth_service_id='.self::ID;
			$appID = trim(self::GetOption("vkontakte_appid"));
			$appSecret = trim(self::GetOption("vkontakte_appsecret"));

			$gAuth = new CVKontakteOAuthInterface($appID, $appSecret, $_REQUEST["code"]);
			if($gAuth->GetAccessToken($redirect_uri) !== false)
			{
				$arVkUser = $gAuth->GetCurrentUser();

				if(is_array($arVkUser) && ($arVkUser['response']['0']['uid'] <> ''))
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
						'NAME'=> $first_name,
						'LAST_NAME'=> $last_name,
						'PERSONAL_GENDER' => $gender,
					);

					if(isset($arVkUser['response']['0']['photo_big']) && self::CheckPhotoURI($arVkUser['response']['0']['photo_big']))
						if ($arPic = CFile::MakeFileArray($arVkUser['response']['0']['photo_big']))
							$arFields["PERSONAL_PHOTO"] = $arPic;
					if(isset($arVkUser['response']['0']['bdate']))
						if ($date = MakeTimeStamp($arVkUser['response']['0']['bdate'], "DD.MM.YYYY"))
							$arFields["PERSONAL_BIRTHDAY"] = ConvertTimeStamp($date);
					$arFields["PERSONAL_WWW"] = "http://vk.com/id".$arVkUser['response']['0']['uid'];
					if(strlen(SITE_ID) > 0)
						$arFields["SITE_ID"] = SITE_ID;

					$bSuccess = $this->AuthorizeUser($arFields);
				}
			}
		}

		$url = ($GLOBALS["APPLICATION"]->GetCurDir() == "/login/") ? "" : $GLOBALS["APPLICATION"]->GetCurDir();
		$aRemove = array("logout", "auth_service_error", "auth_service_id", "code", "error_reason", "error", "error_description", "check_key", "current_fieldset");

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
		}
		if($bSuccess === SOCSERV_REGISTRATION_DENY)
		{
			$url = (preg_match("/\?/", $url)) ? $url.'&' : $url.'?';
			$url .= 'auth_service_id='.self::ID.'&auth_service_error='.$bSuccess;
		}
		elseif($bSuccess !== true)
			$url = (isset($urlPath)) ? $urlPath.'?auth_service_id='.self::ID.'&auth_service_error='.$bSuccess : $GLOBALS['APPLICATION']->GetCurPageParam(('auth_service_id='.self::ID.'&auth_service_error='.$bSuccess), $aRemove);
		if(CModule::IncludeModule("socialnetwork") && strpos($url, "current_fieldset=") === false)
			$url = (preg_match("/\?/", $url)) ? $url."&current_fieldset=SOCSERV" : $url."?current_fieldset=SOCSERV";

		echo '
<script type="text/javascript">
if(window.opener)
	window.opener.location = \''.CUtil::JSEscape($url).'\';
window.close();
</script>
';
		die();
	}
}

class CVKontakteOAuthInterface
{
	const AUTH_URL = "https://oauth.vk.com/authorize";
	const TOKEN_URL = "https://oauth.vk.com/access_token";
	const CONTACTS_URL = "https://api.vk.com/method/users.get";

	protected $appID;
	protected $appSecret;
	protected $code = false;
	protected $access_token = false;
	protected $userID = false;

	public function __construct($appID, $appSecret, $code=false)
	{
		$this->httpTimeout = SOCSERV_DEFAULT_HTTP_TIMEOUT;
		$this->appID = $appID;
		$this->appSecret = $appSecret;
		$this->code = $code;
	}

	public function GetAuthUrl($redirect_uri, $state='')
	{
		return self::AUTH_URL.
			"?client_id=".urlencode($this->appID).
			"&redirect_uri=".$redirect_uri.
			"&scope=friends,video,offline".
			"&response_type=code".
			($state <> ''? '&state='.urlencode($state):'');
	}

	public function GetAccessToken($redirect_uri)
	{
		if($this->code === false)
			return false;

		$result = CHTTP::sPostHeader(self::TOKEN_URL, array(
			"client_id"=>$this->appID,
			"client_secret"=>$this->appSecret,
			"code"=>$this->code,
			"redirect_uri"=>$redirect_uri,
		), array(), $this->httpTimeout);

		$arResult = CUtil::JsObjectToPhp($result);

		if((isset($arResult["access_token"]) && $arResult["access_token"] <> '') && isset($arResult["user_id"]) && $arResult["user_id"] <> '')
		{
			$this->access_token = $arResult["access_token"];
			$this->userID = $arResult["user_id"];
			$_SESSION["OAUTH_DATA"] = array("OATOKEN" => $this->access_token);
			return true;
		}
		return false;
	}

	public function GetCurrentUser()
	{
		if($this->access_token === false)
			return false;

		$result = CHTTP::sGetHeader(self::CONTACTS_URL.'?uids='.$this->userID.'&fields=uid,first_name,last_name,nickname,screen_name,sex,bdate,city,country,timezone,photo,photo_medium,photo_big,photo_rec&access_token='.urlencode($this->access_token), array(), $this->httpTimeout);

		if(!defined("BX_UTF"))
			$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);

		return CUtil::JsObjectToPhp($result);
	}
}
?>
