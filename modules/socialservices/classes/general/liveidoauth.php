<?
IncludeModuleLangFile(__FILE__);

class CSocServLiveIDOAuth extends CSocServAuth
{
	const ID = "LiveIDOAuth";

	static public function GetSettings()
	{
		return array(
			array("liveid_appid", GetMessage("socserv_liveid_client_id"), "", Array("text", 40)),
			array("liveid_appsecret", GetMessage("socserv_liveid_client_secret"), "", Array("text", 40)),
			array("note"=>GetMessage("socserv_liveid_form_note", array('#URL#'=>CSocServUtil::ServerName()))),
		);
	}

	static public function GetFormHtml($arParams)
	{
		$appID = trim(self::GetOption("liveid_appid"));
		$appSecret = trim(self::GetOption("liveid_appsecret"));
		$gAuth = new CLiveIDOAuthInterface($appID, $appSecret);
		$redirect_uri = CSocServUtil::GetCurUrl('auth_service_id='.self::ID, array("current_fieldset"));
		$state = 'site_id='.SITE_ID.'&backurl='.($GLOBALS["APPLICATION"]->GetCurPageParam('check_key='.$_SESSION["UNIQUE_KEY"], array("logout", "auth_service_error", "auth_service_id", "backurl")));
		$url = $gAuth->GetAuthUrl($redirect_uri, $state);
		if($arParams["FOR_INTRANET"])
			return array("ON_CLICK" => 'onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 580, 400)"');
		return '<a href="javascript:void(0)" onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 580, 400)" class="bx-ss-button liveid-button"></a><span class="bx-spacer"></span><span>'.GetMessage("MAIN_OPTION_COMMENT").'</span>';
	}

	static public function Authorize()
	{
		$GLOBALS["APPLICATION"]->RestartBuffer();
		$bSuccess = 1;
			if(isset($_REQUEST["code"]) && $_REQUEST["code"] != '' && CSocServAuthManager::CheckUniqueKey())
			{
				$redirect_uri= CSocServUtil::GetCurUrl('auth_service_id='.self::ID, array("code", "state", "backurl", "check_key"));
				$appID = trim(self::GetOption("liveid_appid"));
				$appSecret = trim(self::GetOption("liveid_appsecret"));

				$gAuth = new CLiveIDOAuthInterface($appID, $appSecret, $_REQUEST["code"]);

				if($gAuth->GetAccessToken($redirect_uri) !== false)
				{
					$arLiveIDUser = $gAuth->GetCurrentUser();

					if ($arLiveIDUser['id'] <> '')
					{
						$email = $first_name = $last_name = "";
						$login = "LiveID".$arLiveIDUser['id'];
						$uId = $arLiveIDUser['id'];
						if($arLiveIDUser['first_name'] <> '')
							$first_name = $arLiveIDUser['first_name'];
						if($arLiveIDUser['last_name'] <> '')
							$last_name = $arLiveIDUser['last_name'];
						if($arLiveIDUser['emails']['preferred'] <> '')
						{
							$email = $arLiveIDUser['emails']['preferred'];
							$login = $arLiveIDUser['emails']['preferred'];
							$uId = $arLiveIDUser['emails']['preferred'];
						}
						$arFields = array(
							'EXTERNAL_AUTH_ID' => self::ID,
							'XML_ID' => $uId,
							'LOGIN' => $login,
							'EMAIL' => $email,
							'NAME'=> $first_name,
							'LAST_NAME'=> $last_name,
						);
						$arFields["PERSONAL_WWW"] = $arLiveIDUser["link"];
						if(strlen(SITE_ID) > 0)
							$arFields["SITE_ID"] = SITE_ID;
						$bSuccess = $this->AuthorizeUser($arFields);

					}
				}
			}

		$url = ($GLOBALS["APPLICATION"]->GetCurDir() == "/login/") ? "/auth/" : $GLOBALS["APPLICATION"]->GetCurDir();

		if(isset($_REQUEST["state"]))
		{
			$arState = array();
			parse_str($_REQUEST["state"], $arState);

			if(isset($arState['backurl']))
				$url = parse_url($arState['backurl'], PHP_URL_PATH);
		}

		$aRemove = array("logout", "auth_service_error", "auth_service_id", "code", "error_reason", "error", "error_description", "check_key", "current_fieldset", "backurl", "state");
		if($bSuccess === 2)
		{
			$url = (preg_match("/\?/", $url)) ? $url.'&' : $url.'?';
			$url .= 'auth_service_id='.self::ID.'&auth_service_error='.$bSuccess;
		}
		elseif($bSuccess !== true)
			$url = (isset($parseUrl)) ? $parseUrl.'?auth_service_id='.self::ID.'&auth_service_error='.$bSuccess : $GLOBALS['APPLICATION']->GetCurPageParam(('auth_service_id='.self::ID.'&auth_service_error='.$bSuccess), $aRemove);
		if(CModule::IncludeModule("socialnetwork"))
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

class CLiveIDOAuthInterface
{
	const AUTH_URL = "https://login.live.com/oauth20_authorize.srf";
	const TOKEN_URL = "https://login.live.com/oauth20_token.srf";
	const CONTACTS_URL = "https://apis.live.net/v5.0/me/";

	protected $appID;
	protected $appSecret;
	protected $code = false;
	protected $access_token = false;
	
	static public function __construct($appID, $appSecret, $code=false)
	{
		$this->httpTimeout = 10;
		$this->appID = $appID;
		$this->appSecret = $appSecret;
		$this->code = $code;
	}

	static public function GetAuthUrl($redirect_uri, $state='')
	{
		return self::AUTH_URL.
			"?client_id=".urlencode($this->appID).
			"&redirect_uri=".urlencode($redirect_uri).
			"&scope=wl.signin,wl.basic,wl.offline_access,wl.emails".
			"&response_type=code".
			($state <> ''? '&state='.urlencode($state):'');
	}
	
	static public function GetAccessToken($redirect_uri)
	{
		if($this->code === false)
			return false;

		$result = CHTTP::sPostHeader(self::TOKEN_URL, array(
			"code"=>$this->code,
			"client_id"=>$this->appID,
			"client_secret"=>$this->appSecret,
			"redirect_uri"=>$redirect_uri,
			"grant_type"=>"authorization_code",
		), array(), $this->httpTimeout);

		$arResult = CUtil::JsObjectToPhp($result);

		if(isset($arResult["access_token"]) && $arResult["access_token"] <> '')
		{
			$this->access_token = $arResult["access_token"];
			$_SESSION["OAUTH_DATA"] = array("OATOKEN" => $this->access_token);
			return true;
		}
		return false;
	}
	
	static public function GetCurrentUser()
	{
		if($this->access_token === false)
			return false;

		$result = CHTTP::sGetHeader(self::CONTACTS_URL."?access_token=".urlencode($this->access_token), array(), $this->httpTimeout);
		if(!defined("BX_UTF"))
			$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);

		return CUtil::JsObjectToPhp($result);
	}
}
?>