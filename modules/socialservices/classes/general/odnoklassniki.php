<?
IncludeModuleLangFile(__FILE__);

class CSocServOdnoklassniki extends CSocServAuth
{
	const ID = "Odnoklassniki";
	const CONTROLLER_URL = "https://www.bitrix24.ru/controller";

	protected $entityOAuth = null;

	static public function GetSettings()
	{
		return array(
			array("odnoklassniki_appid", GetMessage("socserv_odnoklassniki_client_id"), "", Array("text", 40)),
			array("odnoklassniki_appkey", GetMessage("socserv_odnoklassniki_client_key"), "", Array("text", 40)),
			array("odnoklassniki_appsecret", GetMessage("socserv_odnoklassniki_client_secret"), "", Array("text", 40)),
			array("note"=>GetMessage("socserv_odnoklassniki_form_note", array('#URL#'=>CSocServUtil::ServerName()."/bitrix/tools/oauth/odnoklassniki.php"))),
		);
	}

	public function getEntityOAuth()
	{
		return $this->entityOAuth;
	}

	public function GetFormHtml($arParams)
	{
		$url = $this->getUrl('opener', null, $arParams);
		$phrase = ($arParams["FOR_INTRANET"]) ? GetMessage("MAIN_OPTION_COMMENT1_INTRANET") : GetMessage("MAIN_OPTION_COMMENT1");

		if($arParams["FOR_INTRANET"])
			return array("ON_CLICK" => 'onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 580, 400)"');
		return '<a href="javascript:void(0)" onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 580, 400)" class="bx-ss-button odnoklassniki-button"></a><span class="bx-spacer"></span><span>'.$phrase.'</span>';
	}

	public function GetOnClickJs($arParams)
	{
		$url = $this->getUrl('opener', null, $arParams);
		return "BX.util.popup('".CUtil::JSEscape($url)."', 580, 400)";
	}


	public function getUrl($location = 'opener', $addScope = null, $arParams = array())
	{
		global $APPLICATION;

		$this->entityOAuth = new COdnoklassnikiInterface();

		if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
		{
			$redirect_uri = self::CONTROLLER_URL."/redirect.php";
			$state = CSocServUtil::ServerName()."/bitrix/tools/oauth/odnoklassniki.php?state=";
			$backurl = urlencode($GLOBALS["APPLICATION"]->GetCurPageParam('check_key='.$_SESSION["UNIQUE_KEY"], array("logout", "auth_service_error", "auth_service_id", "backurl"))).'&mode='.$location;
			$state .= urlencode(urlencode("backurl=".$backurl));
		}
		else
		{
			$backurl = $APPLICATION->GetCurPageParam(
				'check_key='.$_SESSION["UNIQUE_KEY"],
				array("logout", "auth_service_error", "auth_service_id", "backurl")
			);
			$redirect_uri = CSocServUtil::ServerName()."/bitrix/tools/oauth/odnoklassniki.php";
			$state = 'site_id='.SITE_ID.'&backurl='.urlencode($backurl).(isset($arParams['BACKURL']) ? '&redirect_url='.urlencode($arParams['BACKURL']) : '').'&mode='.$location;
		}

		return $this->entityOAuth->GetAuthUrl($redirect_uri, $state);
	}

	public function Authorize()
	{
		global $APPLICATION;

		$APPLICATION->RestartBuffer();
		$bSuccess = SOCSERV_AUTHORISATION_ERROR;
		$bProcessState = false;

		if((isset($_REQUEST["code"]) && $_REQUEST["code"] <> '') && CSocServAuthManager::CheckUniqueKey())
		{
			$bProcessState = true;

			if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
				$redirect_uri = self::CONTROLLER_URL."/redirect.php";
			else
				$redirect_uri= CSocServUtil::ServerName()."/bitrix/tools/oauth/odnoklassniki.php";

			$appID = trim(self::GetOption("odnoklassniki_appid"));
			$appSecret = trim(self::GetOption("odnoklassniki_appsecret"));
			$appKey = trim(self::GetOption("odnoklassniki_appkey"));

			$gAuth = new COdnoklassnikiInterface($appID, $appSecret, $appKey, $_REQUEST["code"]);

			if($gAuth->GetAccessToken($redirect_uri) !== false)
			{
				$arOdnoklUser = $gAuth->GetCurrentUser();

				if(is_array($arOdnoklUser) && ($arOdnoklUser['uid'] <> ''))
				{
					$uid = $arOdnoklUser['uid'];
					$first_name = $last_name = $gender = "";
					if($arOdnoklUser['first_name'] <> '')
						$first_name = $arOdnoklUser['first_name'];
					if($arOdnoklUser['last_name'] <> '')
						$last_name = $arOdnoklUser['last_name'];
					if(isset($arOdnoklUser['gender']) && $arOdnoklUser['gender'] != '')
					{
						if($arOdnoklUser['gender'] == 'male')
							$gender = 'M';
						elseif($arOdnoklUser['gender'] == 'female')
							$gender = 'F';
					}

					$arFields = array(
						'EXTERNAL_AUTH_ID' => self::ID,
						'XML_ID' => "OK".$uid,
						'LOGIN' => "OKuser".$uid,
						'NAME'=> $first_name,
						'LAST_NAME'=> $last_name,
						'PERSONAL_GENDER' => $gender,
					);
					if(isset($arOdnoklUser['birthday']))
						if($date = MakeTimeStamp($arOdnoklUser['birthday'], "YYYY-MM-DD"))
							$arFields["PERSONAL_BIRTHDAY"] = ConvertTimeStamp($date);
					if(isset($arOdnoklUser['pic_2']) && self::CheckPhotoURI($arOdnoklUser['pic_2']))
						if ($arPic = CFile::MakeFileArray($arOdnoklUser['pic_2'].'&name=/'.md5($arOdnoklUser['pic_2']).'.jpg'))
							$arFields["PERSONAL_PHOTO"] = $arPic;
					$arFields["PERSONAL_WWW"] = "http://odnoklassniki.ru/profile/".$uid;
					if(strlen(SITE_ID) > 0)
						$arFields["SITE_ID"] = SITE_ID;
					$bSuccess = $this->AuthorizeUser($arFields);
				}
			}
		}

		if(!$bProcessState)
		{
			unset($_REQUEST["state"]);
		}

		$url = ($APPLICATION->GetCurDir() == "/login/") ? "" : $APPLICATION->GetCurDir();
		$aRemove = array("logout", "auth_service_error", "auth_service_id", "code", "error_reason", "error", "error_description", "check_key", "current_fieldset");

		$mode = 'opener';
		if(isset($_REQUEST["state"]))
		{
			$arState = array();
			parse_str($_REQUEST["state"], $arState);
			if(isset($arState['backurl']) || isset($arState['redirect_url']))
			{
				$parseUrl = parse_url(!empty($arState['redirect_url']) ? $arState['redirect_url'] : $arState['backurl']);
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

		if($bSuccess === SOCSERV_REGISTRATION_DENY)
		{
			$url = (preg_match("/\?/", $url)) ? $url.'&' : $url.'?';
			$url .= 'auth_service_id='.self::ID.'&auth_service_error='.SOCSERV_REGISTRATION_DENY;
		}
		elseif($bSuccess !== true)
		{
			$url = (isset($parseUrl))
				? $urlPath.'?auth_service_id='.self::ID.'&auth_service_error='.$bSuccess
				: $APPLICATION->GetCurPageParam(('auth_service_id='.self::ID.'&auth_service_error='.$bSuccess), $aRemove);
		}

		if(CModule::IncludeModule("socialnetwork") && strpos($url, "current_fieldset=") === false)
			$url = (preg_match("/\?/", $url)) ? $url."&current_fieldset=SOCSERV" : $url."?current_fieldset=SOCSERV";

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

	public static function SendUserFeed($userId, $message)
	{
		$appID = trim(self::GetOption("odnoklassniki_appid"));
		$appSecret = trim(self::GetOption("odnoklassniki_appsecret"));
		$appKey = trim(self::GetOption("odnoklassniki_appkey"));
		$gAuth = new COdnoklassnikiInterface($appID, $appSecret, $appKey);
		$result = $gAuth->SendFeed($userId, $message);
		return $result;
	}

}

class COdnoklassnikiInterface
{
	const AUTH_URL = "http://www.odnoklassniki.ru/oauth/authorize";
	const TOKEN_URL = "http://api.odnoklassniki.ru/oauth/token.do";
	const CONTACTS_URL = "http://api.odnoklassniki.ru/fb.do";

	protected $appID;
	protected $appSecret;
	protected $appKey;
	protected $code = false;
	protected $access_token = false;
	protected $sign = false;
	protected $refresh_token = '';
	protected $userId = 0;

	public function __construct($appID = false, $appSecret = false, $appKey = false, $code=false)
	{
		if($appID === false)
		{
			$appID = trim(CSocServLiveIDOAuth::GetOption("odnoklassniki_appid"));
		}

		if($appSecret === false)
		{
			$appSecret = trim(CSocServLiveIDOAuth::GetOption("odnoklassniki_appsecret"));
		}

		if($appKey === false)
		{
			$appKey = trim(CSocServLiveIDOAuth::GetOption("odnoklassniki_appkey"));
		}

		$this->httpTimeout = SOCSERV_DEFAULT_HTTP_TIMEOUT;
		$this->appID = $appID;
		$this->appSecret = $appSecret;
		$this->code = $code;
		$this->appKey = $appKey;
	}

	public function GetAuthUrl($redirect_uri, $state='')
	{
		return self::AUTH_URL.
			"?client_id=".urlencode($this->appID).
			"&redirect_uri=".urlencode($redirect_uri).
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
			$_SESSION["OAUTH_DATA"] = array("OATOKEN" => $this->access_token);
			if(isset($arResult["refresh_token"]) && $arResult["refresh_token"] <> '')
			{
				$this->refresh_token = $arResult["refresh_token"];
				$_SESSION["OAUTH_DATA"]["REFRESH_TOKEN"] = $this->refresh_token;
			}

			$arguments = array();
			$arguments["application_key"] = $this->appKey;
			$arguments['method'] = 'users.getCurrentUser';
			ksort($arguments);
			$this->sign = strtolower(md5('application_key='.$arguments["application_key"].'method='.$arguments['method'].md5($this->access_token.$this->appSecret)));
			return true;
		}
		return false;
	}

	public function GetCurrentUser()
	{
		if($this->access_token === false)
			return false;

		$result = CHTTP::sGetHeader(self::CONTACTS_URL."?method=users.getCurrentUser&application_key=".$this->appKey."&access_token=".$this->access_token."&sig=".$this->sign, array(), $this->httpTimeout);
		if(!defined("BX_UTF"))
			$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);

		return CUtil::JsObjectToPhp($result);
	}

	public function SendFeed($socServUserId, $message, $getNewToken=true)
	{
		if(!$this->access_token || intval($this->userId) < 1)
			self::SetOauthKeys($socServUserId);
		if(!defined("BX_UTF"))
			$message = CharsetConverter::ConvertCharset($message, LANG_CHARSET, "utf-8");
		$this->sign = strtolower(md5('application_key='.$this->appKey.'method=users.setStatusstatus='.$message.md5($this->access_token.$this->appSecret)));
		$result = CHTTP::sGetHeader(self::CONTACTS_URL."?method=users.setStatus&application_key=".$this->appKey."&access_token=".$this->access_token."&sig=".$this->sign."&status=".urlencode($message), array(), $this->httpTimeout);

		if(!defined("BX_UTF"))
			$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);
		$arResult = CUtil::JsObjectToPhp($result);
		if($getNewToken === true && isset($arResult["error_code"]) && $arResult["error_code"] == "102")
			{
				$newToken = self::RefreshToken($socServUserId);
				if($newToken === true)
					self::SendFeed($socServUserId, $message, false);
				else
					return false;
			}
		return $arResult;
	}

	private function SetOauthKeys($socServUserId)
	{
		$dbSocservUser = CSocServAuthDB::GetList(array(), array('ID' => $socServUserId), false, false, array("OATOKEN", "XML_ID", "REFRESH_TOKEN"));
		while($arOauth = $dbSocservUser->Fetch())
		{
			$this->access_token = $arOauth["OATOKEN"];
			$this->userId = preg_replace("|\D|", '', $arOauth["XML_ID"]);
			$this->refresh_token = $arOauth["REFRESH_TOKEN"];
		}
	}

	private function RefreshToken($socServUserId)
	{
		$result = CHTTP::sPostHeader(self::TOKEN_URL, array(
			"refresh_token"=>$this->refresh_token,
			"client_id"=>$this->appID,
			"client_secret"=>$this->appSecret,
			"grant_type"=>"refresh_token",
		), array(), $this->httpTimeout);
		$arResult = CUtil::JsObjectToPhp($result);

		if(isset($arResult["access_token"]) && $arResult["access_token"] <> '')
		{
			$this->access_token = $arResult["access_token"];
			CSocServAuthDB::Update($socServUserId, array("OATOKEN" => $arResult["access_token"]));
			return true;
		}
		return false;
	}
}
?>