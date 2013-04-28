<?
IncludeModuleLangFile(__FILE__);

class CSocServTwitter extends CSocServAuth
{
	const ID = "Twitter";

	static public function GetSettings()
	{
		return array(
			array("twitter_key", GetMessage("socserv_tw_key"), "", Array("text", 40)),
			array("twitter_secret", GetMessage("socserv_tw_secret"), "", Array("text", 40)),
			array("note"=>GetMessage("socserv_tw_sett_note", array('#URL#'=>CSocServUtil::ServerName()))),
		);
	}

	static public function GetFormHtml($arParams)
	{
		$phrase = ($arParams["FOR_INTRANET"]) ? GetMessage("socserv_tw_note_intranet") : GetMessage("socserv_tw_note");
		$url = $GLOBALS['APPLICATION']->GetCurPageParam('auth_service_id='.self::ID.'&check_key='.$_SESSION["UNIQUE_KEY"], array("logout", "auth_service_error", "auth_service_id", "current_fieldset"));
		if($arParams["FOR_INTRANET"])
			return array("ON_CLICK" => 'onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 800, 450)"');
		return '<a href="javascript:void(0)" onclick="BX.util.popup(\''.htmlspecialcharsbx(CUtil::JSEscape($url)).'\', 800, 450)" class="bx-ss-button twitter-button"></a><span class="bx-spacer"></span><span>'.$phrase.'</span>';

	}

	static public function Authorize()
	{
		$GLOBALS["APPLICATION"]->RestartBuffer();
		$bSuccess = 1;

		$appID = trim(self::GetOption("twitter_key"));
		$appSecret = trim(self::GetOption("twitter_secret"));

		if(!isset($_REQUEST["oauth_token"]) || $_REQUEST["oauth_token"] == '')
		{
			$tw = new CTwitterInterface($appID, $appSecret);
			$callback = CSocServUtil::GetCurUrl('auth_service_id='.self::ID);
			//$callback = 'http://algerman.sam:6448/script.php?auth_service_id='.self::ID;
			if($tw->GetRequestToken($callback))
				$tw->RedirectAuthUrl();
		}
		elseif(CSocServAuthManager::CheckUniqueKey())
		{
			$tw = new CTwitterInterface($appID, $appSecret, $_REQUEST["oauth_token"], $_REQUEST["oauth_verifier"]);
			if(($arResult = $tw->GetAccessToken()) !== false && $arResult["user_id"] <> '')
			{

				$twUser = $tw->GetUserInfo($arResult["user_id"]);

				$first_name = $last_name = "";
				if($twUser["name"] <> '')
				{
					$aName = explode(" ", $twUser["name"]);
					$first_name = $aName[0];
					if(isset($aName[1]))
						$last_name = $aName[1];
				}

				$arFields = array(
					'EXTERNAL_AUTH_ID' => self::ID,
					'XML_ID' => $arResult["user_id"],
					'LOGIN' => $arResult["screen_name"],
					'NAME'=> $first_name,
					'LAST_NAME'=> $last_name,
				);
				if(isset($twUser["profile_image_url"]) && self::CheckPhotoURI($twUser["profile_image_url"]))
					if ($arPic = CFile::MakeFileArray($twUser["profile_image_url"]))
						$arFields["PERSONAL_PHOTO"] = $arPic;
				$arFields["PERSONAL_WWW"] = "https://twitter.com/".$arResult["screen_name"];
				if(strlen(SITE_ID) > 0)
					$arFields["SITE_ID"] = SITE_ID;
				if(COption::GetOptionString('socialservices','last_twit_id','1') == 1)
				{
					if(isset($twUser["status"]["id_str"]))
						COption::SetOptionString('socialservices', 'last_twit_id', $twUser["status"]["id_str"]);
				}

				$bSuccess = $this->AuthorizeUser($arFields);
			}
		}

		$aRemove = array("logout", "auth_service_error", "auth_service_id", "oauth_token", "oauth_verifier", "check_key", "current_fieldset");
		$url = $GLOBALS['APPLICATION']->GetCurPageParam(($bSuccess === true ? '' : 'auth_service_id='.self::ID.'&auth_service_error='.$bSuccess), $aRemove);
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

	static public function GetUserMessage($socServUserArray, $sinceId=0)
	{
		$result = array();
		$token = false;
		$secret = false;
		if(!empty($socServUserArray))
		{
			$hash = COption::GetOptionString("socialservices", "twitter_search_hash", "#b24");
			$appID = trim(self::GetOption("twitter_key"));
			$appSecret = trim(self::GetOption("twitter_secret"));
			if(is_array($socServUserArray[1]) && $arToken = $socServUserArray[1])
			{
				$key = array_rand($arToken, 1);
				$token = $arToken[$key];
				if(is_array($socServUserArray[2]))
					$secret = $socServUserArray[2][$key];
			}

			$tw = new CTwitterInterface($appID, $appSecret, $token, false, $secret);
			$result = $tw->SearchByHash($hash, $socServUserArray, $sinceId);
		}
		return $result;
	}

	static public function TwitterUserId($userId)
	{
		$dbSocservUser = CSocServAuthDB::GetList(array(), array('USER_ID' => $userId, 'EXTERNAL_AUTH_ID' => self::ID), false, false, array("ID"));
		$arOauth = $dbSocservUser->Fetch();
		if($arOauth["ID"])
			return $arOauth["ID"];
		return false;
	}

	public static function SendUserFeed($userId, $message, $messageId)
	{

		$appID = trim(self::GetOption("twitter_key"));
		$appSecret = trim(self::GetOption("twitter_secret"));
		$tw = new CTwitterInterface($appID, $appSecret);
		return $tw->SendTwit($userId, $message, $messageId);
	}

}

class CTwitterInterface
{
	const REQUEST_URL = "https://api.twitter.com/oauth/request_token";
	const AUTH_URL = "https://api.twitter.com/oauth/authenticate";
	const TOKEN_URL = "https://api.twitter.com/oauth/access_token";
	const API_URL = "https://api.twitter.com/1.1/users/show.json";
	const POST_URL = "https://api.twitter.com/1.1/statuses/update.json";
	const SEARCH_URL = "https://api.twitter.com/1.1/search/tweets.json";

	protected $appID;
	protected $appSecret;
	protected $token = false;
	protected $tokenVerifier = false;
	protected $tokenSecret = false;
	protected $oauthArray;

	static public function __construct($appID, $appSecret, $token=false, $tokenVerifier=false, $tokenSecret=false)
	{
		$this->httpTimeout = 10;
		$this->appID = $appID;
		$this->appSecret = $appSecret;
		$this->token = $token;
		$this->tokenVerifier = $tokenVerifier;
		if($this->token && isset($_SESSION["twitter_token_secret"]))
			$this->tokenSecret = $_SESSION["twitter_token_secret"];
		if($this->token && $tokenSecret)
			$this->tokenSecret = $tokenSecret;
	}

	protected function GetDefParams()
	{
		$this->oauthArray = array(
			"oauth_consumer_key" => $this->appID,
			"oauth_nonce" => md5(microtime().mt_rand()),
			"oauth_signature_method" => "HMAC-SHA1",
			"oauth_timestamp" => time(),
			"oauth_version" => "1.0",
		);

		return $this->oauthArray;
	}

	static public function GetRequestToken($callback)
	{
		$arParams = array_merge($this->GetDefParams(), array(
			"oauth_callback" => $callback,
		));

		$arParams["oauth_signature"] = $this->BuildSignature($this->GetSignatureString($arParams, self::REQUEST_URL));
		$result = CHTTP::sPostHeader(self::REQUEST_URL, $arParams, array(), $this->httpTimeout);
		parse_str($result, $arResult);
		if(isset($arResult["oauth_token"]) && $arResult["oauth_token"] <> '')
		{
			$this->token = $arResult["oauth_token"];
			$this->tokenSecret = $arResult["oauth_token_secret"];
			$_SESSION["twitter_token_secret"] = $this->tokenSecret;
			return true;
		}
		return false;
	}

	static public function RedirectAuthUrl()
	{
		if(!$this->token)
			return false;
		LocalRedirect(self::AUTH_URL."?oauth_token=".urlencode($this->token).'&check_key='.$_SESSION["UNIQUE_KEY"], true);
	}

	static public function GetAccessToken()
	{
		if(!$this->token || !$this->tokenVerifier || !$this->tokenSecret)
			return false;

		$arParams = array_merge($this->GetDefParams(), array(
			"oauth_token" => $this->token,
			"oauth_verifier" => $this->tokenVerifier,
		));

		$arParams["oauth_signature"] = $this->BuildSignature($this->GetSignatureString($arParams, self::TOKEN_URL));
		$result = CHTTP::sPostHeader(self::TOKEN_URL, $arParams, array(), $this->httpTimeout);
		parse_str($result, $arResult);
		if(isset($arResult["oauth_token"]) && $arResult["oauth_token"] <> '')
		{
			$this->token = $arResult["oauth_token"];
			$this->tokenSecret = $arResult["oauth_token_secret"];
			$_SESSION["OAUTH_DATA"] = array("OATOKEN" => $this->token, "OASECRET" => $this->tokenSecret);
			return $arResult;
		}
		return false;
	}

	static public function GetUserInfo($user_id)
	{
		$arParams = array_merge($this->GetDefParams(), array(
			"oauth_token" => $this->token,
			"user_id" => $user_id,
		));
		$arParams["oauth_signature"] = urlencode($this->BuildSignature($this->GetSignatureString($arParams, self::API_URL)));

		$arHeaders = array(
			"Authorization" => 'OAuth oauth_consumer_key="'.$arParams["oauth_consumer_key"].'", oauth_nonce="'.$arParams["oauth_nonce"].'", oauth_signature="'.$arParams["oauth_signature"].'", oauth_signature_method="HMAC-SHA1", oauth_timestamp="'.$arParams["oauth_timestamp"].'", oauth_token="'.$this->token.'", oauth_version="1.0"',
			"Content-type" => "application/x-www-form-urlencoded",
		);

		$result = CHTTP::sGetHeader(self::API_URL.'?user_id='.$user_id, $arHeaders, $this->httpTimeout);

		if(!defined("BX_UTF"))
			$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);
		return CUtil::JsObjectToPhp($result);
	}

	private function SetOauthKeys($socServUserId)
	{
		$dbSocservUser = CSocServAuthDB::GetList(array(), array('ID' => $socServUserId), false, false, array("OATOKEN", "OASECRET"));
		while($arOauth = $dbSocservUser->Fetch())
		{
			$this->token = $arOauth["OATOKEN"];
			$this->tokenSecret = $arOauth["OASECRET"];
		}
		if(!$this->token || !$this->tokenSecret)
			return false;
		return true;
	}

	static public function SearchByHash($hash, $socServUserArray, $sinceId)
	{
		if(!defined("BX_UTF"))
			$hash = CharsetConverter::ConvertCharset($hash, LANG_CHARSET, "utf-8");

		$arParams = array_merge(array("count" => 100, "include_entities" => "false"), $this->GetDefParams());
		$arParams = array_merge($arParams, array(
			"oauth_token" => $this->token,
			"q" => $hash,
			"since_id" => $sinceId,
		));
		$arParams["oauth_signature"] = urlencode($this->BuildSignature($this->GetSignatureString($arParams, self::SEARCH_URL)));
		$arHeaders = array(
			"Authorization" => 'OAuth oauth_consumer_key="'.$arParams["oauth_consumer_key"].'", oauth_nonce="'.$arParams["oauth_nonce"].'", oauth_signature="'.$arParams["oauth_signature"].'", oauth_signature_method="HMAC-SHA1", oauth_timestamp="'.$arParams["oauth_timestamp"].'", oauth_token="'.$this->token.'", oauth_version="1.0"',
			"Content-type" => "application/x-www-form-urlencoded",
		);
		$result = @CHTTP::sGetHeader(self::SEARCH_URL."?count=100&include_entities=false&q=".urlencode($hash)."&since_id=".$sinceId, $arHeaders, $this->httpTimeout);
		if(!defined("BX_UTF"))
			$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);
		$arResult = CUtil::JsObjectToPhp($result);
		//if(isset($arResult["search_metadata"]["next_results"]))
		//	$arTwits = self::GetAllPages($arResult);
		if(!empty($arTwits) && is_array($arTwits) && is_array($arResult["statuses"]))
			$arResult["statuses"] = array_merge($arResult["statuses"], $arTwits);
		if(is_array($arResult["statuses"]))
			foreach($arResult["statuses"] as $key => $value)
			{
				if(!$find = array_search($value["user"]["id_str"], $socServUserArray[0]))
					unset($arResult["statuses"][$key]);
				else
				{
					$arResult["statuses"][$key]["kp_user_id"] = $find;
					$arResult["statuses"][$key]["user_perms"] = self::GetUserPerms($value["user"]["id_str"]);
				}
			}
		return $arResult;
	}

	private function GetAllPages($arResult)
	{

		static $arTwits = array();
		if(!isset($arResult["search_metadata"]["next_results"]))
			return $arTwits;
		parse_str(preg_replace("|\?|", '', $arResult["search_metadata"]["next_results"]), $searchMetaData);
		$arParams = array_merge(array("count" => $searchMetaData["count"], "include_entities" => $searchMetaData["include_entities"], "max_id" => $searchMetaData["max_id"]), $this->GetDefParams());
		$arParams = array_merge($arParams, array(
			"oauth_token" => $this->token,
			"q" => $searchMetaData["q"],
		));
		$arParams["oauth_signature"] = urlencode($this->BuildSignature($this->GetSignatureString($arParams, self::SEARCH_URL)));
		$arHeaders = array(
			"Authorization" => 'OAuth oauth_consumer_key="'.$arParams["oauth_consumer_key"].'", oauth_nonce="'.$arParams["oauth_nonce"].'", oauth_signature="'.$arParams["oauth_signature"].'", oauth_signature_method="HMAC-SHA1", oauth_timestamp="'.$arParams["oauth_timestamp"].'", oauth_token="'.$this->token.'", oauth_version="1.0"',
			"Content-type" => "application/x-www-form-urlencoded",
		);
		$result = CHTTP::sGetHeader(self::SEARCH_URL."?count=".$searchMetaData["count"]."&include_entities=".$searchMetaData["include_entities"]."&max_id=".$searchMetaData["max_id"]."&q=".urlencode($searchMetaData["q"]), $arHeaders, $this->httpTimeout);

		if(!defined("BX_UTF"))
			$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);
		$arResult = CUtil::JsObjectToPhp($result);
		if(is_array($arResult["statuses"]))
			$arTwits = array_merge($arTwits, $arResult["statuses"]);
		return self::GetAllPages($arResult);
	}

	private function GetAllPagesNotAuth($arResult)
	{
		static $arTwits = array();
		if(!isset($arResult["next_page"]) || $arResult["page"] == 15 || intval($arResult["page"]) < 1)
			return $arTwits;
		$result = CHTTP::sGet(self::SEARCH_URL.$arResult["next_page"]);
		if(!defined("BX_UTF"))
			$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);
		$arResult = CUtil::JsObjectToPhp($result);
		$arTwits = array_merge($arTwits, $arResult["results"]);
		return self::GetAllPages($arResult);
	}

	static public function SendTwit($socServUserId, $message, $messageId)
	{
		$isSetOauthKeys = true;
		if(!$this->token || !$this->tokenSecret)
			$isSetOauthKeys = self::SetOauthKeys($socServUserId);

		if($isSetOauthKeys === false)
		{
			CSocServMessage::Delete($messageId);
			return false;
		}

		if(strlen($message) > 139)
			$message = substr($message, 0, 137)."...";

		if(!defined("BX_UTF"))
			$message = CharsetConverter::ConvertCharset($message, LANG_CHARSET, "utf-8");

		$arParams = array_merge($this->GetDefParams(), array(
			"oauth_token" => $this->token,
			"status"=> $message,
		));
		$arParams["oauth_signature"] = urlencode($this->BuildSignature($this->GetSignatureString($arParams, $this::POST_URL)));

		$arHeaders = array(
			"Authorization" => 'OAuth oauth_consumer_key="'.$arParams["oauth_consumer_key"].'", oauth_nonce="'.$arParams["oauth_nonce"].'", oauth_signature="'.$arParams["oauth_signature"].'", oauth_signature_method="HMAC-SHA1", oauth_timestamp="'.$arParams["oauth_timestamp"].'", oauth_token="'.$this->token.'", oauth_version="1.0"',
		);
		$arPost = array("status"=> $message);
		$result = @CHTTP::sPostHeader($this::POST_URL, $arPost, $arHeaders, $this->httpTimeout);
		if($result !== false)
		{
			if(!defined("BX_UTF"))
				$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);
			return CUtil::JsObjectToPhp($result);
		}
		else
			return false;

	}

	private function GetUserPerms($userXmlId)
	{
		$arUserPermis = array();
		$dbSocUser = CSocServAuthDB::GetList(array(), array('EXTERNAL_AUTH_ID'=>'Twitter', 'XML_ID'=>$userXmlId), false, false, array("PERMISSIONS"));
		while($arSocUser = $dbSocUser->Fetch())
		{
			$arUserPermis = unserialize($arSocUser["PERMISSIONS"]);
			if(is_array($arUserPermis))
				foreach($arUserPermis as $key=>$value)
					if($value == "UA")
						$arUserPermis[$key] = "G2";
		}
		if(!empty($arUserPermis))
			return $arUserPermis;
		else
			return array("UA" => array("UA"));
	}

	protected function urlencode($mixParams)
	{
		if(is_array($mixParams))
			return array_map(array($this, 'urlencode'), $mixParams);
		elseif (is_scalar($mixParams))
			return str_replace(array('+','%7E'), array(' ','~'), rawurlencode($mixParams));
		else
			return '';
	}

	protected function GetSignatureString($arParams, $url)
	{
		$typeRequest = "POST";
		if($url === self::API_URL || $url === self::SEARCH_URL)
			$typeRequest = "GET";
		if(array_key_exists('oauth_signature', $arParams))
			unset($arParams['oauth_signature']);

		return implode('&',
			$this->urlencode(
				array(
					$typeRequest,
					$url,
					$this->BuildQuery($arParams),
				)
			)
		);
	}

	protected function BuildQuery($params)
	{
		if (!$params)
			return '';

		$keys = $this->urlencode(array_keys($params));
		$values = $this->urlencode(array_values($params));
		$params = array_combine($keys, $values);

		uksort($params, 'strcmp');

		$pairs = array();
		foreach ($params as $parameter => $value)
		{
			if(is_array($value))
			{
				natsort($value);
				foreach ($value as $duplicate_value)
					$pairs[] = $parameter . '=' . $duplicate_value;
			}
			else
				$pairs[] = $parameter . '=' . $value;
		}
		return implode('&', $pairs);
	}

	protected function BuildSignature($sigString)
	{
		$key = implode('&',
			$this->urlencode(
				array(
					$this->appSecret,
					($this->tokenSecret? $this->tokenSecret : ''),
				)
			)
		);
		return base64_encode(hash_hmac('sha1', $sigString, $key, true));
	}
}

?>