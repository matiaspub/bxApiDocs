<?
IncludeModuleLangFile(__FILE__);

class CSocServGoogleOAuth extends CSocServAuth
{
	const ID = "GoogleOAuth";
	const CONTROLLER_URL = "https://www.bitrix24.ru/controller";

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
		$appID = trim(self::GetOption("google_appid"));
		$appSecret = trim(self::GetOption("google_appsecret"));

		$gAuth = new CGoogleOAuthInterface($appID, $appSecret);

		if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
		{
			$redirect_uri = self::CONTROLLER_URL."/redirect.php";
			$state = urlencode(CSocServUtil::GetCurUrl('auth_service_id='.self::ID.'&check_key='.$_SESSION["UNIQUE_KEY"]));
		}
		else
		{
			$state = 'site_id='.SITE_ID.'&backurl='.($GLOBALS["APPLICATION"]->GetCurPageParam('check_key='.$_SESSION["UNIQUE_KEY"], array("logout", "auth_service_error", "auth_service_id", "backurl")));
			$redirect_uri = CSocServUtil::ServerName()."/bitrix/tools/oauth/google.php";
		}

		$url = $gAuth->GetAuthUrl($redirect_uri, $state);
		$phrase = ($arParams["FOR_INTRANET"]) ? GetMessage("socserv_google_form_note_intranet") : GetMessage("socserv_google_form_note");
		if($arParams["FOR_INTRANET"])
			return array("ON_CLICK" => 'onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 580, 400)"');
		return '<a href="javascript:void(0)" onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 580, 400)" class="bx-ss-button google-button"></a><span class="bx-spacer"></span><span>'.$phrase.'</span>';
	}

	public function Authorize()
	{
		$GLOBALS["APPLICATION"]->RestartBuffer();
		$bSuccess = 1;
		if((isset($_REQUEST["code"]) && $_REQUEST["code"] <> '') && CSocServAuthManager::CheckUniqueKey())
		{
			if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
				$redirect_uri = self::CONTROLLER_URL."/redirect.php";
			else
				$redirect_uri = CSocServUtil::ServerName()."/bitrix/tools/oauth/google.php";

			$appID = trim(self::GetOption("google_appid"));
			$appSecret = trim(self::GetOption("google_appsecret"));

			$gAuth = new CGoogleOAuthInterface($appID, $appSecret, $_REQUEST["code"]);

			if($gAuth->GetAccessToken($redirect_uri) !== false)
			{
				$arGoogleUser = $gAuth->GetCurrentUser();

				if($arGoogleUser['email'] <> '')
				{
					$first_name = $last_name = $gender = "";
					if($arGoogleUser['name'] <> '')
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
					$email = $arGoogleUser['email'];
					if($arGoogleUser['gender'] <> '')
						if($arGoogleUser['gender'] == 'male')
							$gender = 'M';
						elseif($arGoogleUser['gender'] == 'female')
							$gender = 'F';

					$arFields = array(
						'EXTERNAL_AUTH_ID' => self::ID,
						'XML_ID' => $email,
						'LOGIN' => "G_".$email,
						'EMAIL' => $email,
						'NAME'=> $first_name,
						'LAST_NAME'=> $last_name
					);

					if($gender != "")
						$arFields['PERSONAL_GENDER'] = $gender;

					if(isset($arGoogleUser['picture']) && self::CheckPhotoURI($arGoogleUser['picture']))
						if($arPic = CFile::MakeFileArray($arGoogleUser['picture']))
							$arFields["PERSONAL_PHOTO"] = $arPic;

					$arFields["PERSONAL_WWW"] = $arGoogleUser['link'];
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
			{
				$parseUrl = parse_url($arState['backurl'], PHP_URL_PATH);
				$url = $parseUrl;
			}
		}
		$aRemove = array("logout", "auth_service_error", "auth_service_id", "code", "error_reason", "error", "error_description", "check_key", "current_fieldset");
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

class CGoogleOAuthInterface
{
	const AUTH_URL = "https://accounts.google.com/o/oauth2/auth";
	const TOKEN_URL = "https://accounts.google.com/o/oauth2/token";
	const CONTACTS_URL = "https://www.googleapis.com/oauth2/v1/userinfo";

	protected $appID;
	protected $appSecret;
	protected $code = false;
	protected $access_token = false;

	public function __construct($appID, $appSecret, $code=false)
	{
		$this->httpTimeout = 10;
		$this->appID = $appID;
		$this->appSecret = $appSecret;
		$this->code = $code;
	}

	public function GetAuthUrl($redirect_uri, $state='')
	{
		return self::AUTH_URL.
			"?client_id=".urlencode($this->appID).
			"&redirect_uri=".urlencode($redirect_uri).
			"&scope=https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fuserinfo.email+https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fuserinfo.profile".
			"&response_type=code".
			($state <> ''? '&state='.urlencode($state):'');
	}

	public function GetAccessToken($redirect_uri)
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
			return true;
		}
		return false;
	}

	public function GetCurrentUser()
	{
		if($this->access_token === false)
			return false;

		$result = CHTTP::sGetHeader(self::CONTACTS_URL.'?access_token='.urlencode($this->access_token), array(), $this->httpTimeout);

		if(!defined("BX_UTF"))
			$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);

		return CUtil::JsObjectToPhp($result);
	}
}
?>