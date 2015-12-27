<?
IncludeModuleLangFile(__FILE__);

class COpenIDClient
{
	var $_trust_providers = array();

	public function SetTrustProviders($t)
	{
		if (is_array($t))
			$this->_trust_providers = array_filter($t);
	}

	public function CheckTrustProviders($url)
	{
		if (count($this->_trust_providers) <= 0)
			return true;

		$arUrl = CHTTP::ParseURL($url);
		foreach ($this->_trust_providers as $p)
			if (strpos($arUrl['host'], $p) !== false)
				return true;

		return false;
	}

	public static function GetOpenIDServerTags($url)
	{
		if ($str = @CHTTP::sGet($url, true))
		{
			$server = '';
			$delegate = '';

			if (preg_match('/<link[^>]+rel=(["\'])([^>"]*\s)?openid\.server(\s[^>"]*)?\1[^>]*>/i', $str, $arLinks))
				if (preg_match('/href=["\']([^"|\']+)["\']/i', $arLinks[0], $arHref))
					$server = $arHref[1];

			if (preg_match('/<link[^>]+rel=(["\'])([^>"]*\s)?openid.delegate(\s[^>"]*)?\1[^>]*>/i', $str, $arLinks))
				if (preg_match('/href=["\']([^"|\']+)["\']/i', $arLinks[0], $arHref))
					$delegate = $arHref[1];

			if (strlen($server) <= 0)
			{
				$GLOBALS['APPLICATION']->ThrowException(GetMessage('OPENID_CLIENT_NO_OPENID_SERVER_TAG'));
				return false;
			}
			return array('server' => $server, 'delegate' => $delegate);
		}
		$GLOBALS['APPLICATION']->ThrowException(GetMessage('OPENID_CLIENT_NO_OPENID_SERVER_TAG'));
		return false;
	}

	public function GetRedirectUrl($identity, $return_to=false)
	{
		if (strlen($identity) <= 0)
		{
			$GLOBALS['APPLICATION']->ThrowException(GetMessage('OPENID_CLIENT_EMPTY_IDENTITY'));
			return false;
		}

		if (strlen($identity) > 1024)
			$identity = substr($identity, 0, 1024); // may be 256 ????

		if (strpos(strtolower($identity), 'http://') === false && strpos(strtolower($identity), 'https://') === false)
			$identity = 'http://' . $identity;

		$_SESSION['BX_OPENID_IDENTITY'] = $identity;

		if ($arOpenidServerTags = $this->GetOpenIDServerTags($identity))
		{
			if (!$this->CheckTrustProviders($arOpenidServerTags['server']))
			{
				$GLOBALS['APPLICATION']->ThrowException(GetMessage('OPENID_CLIENT_CHECK_TRUST_PRIVIDERS_FAULT'));
				return false;
			}

			$protocol = (CMain::IsHTTPS() ? "https" : "http");
			$port = ($_SERVER['SERVER_PORT'] > 0 && $_SERVER['SERVER_PORT'] <> 80 && $_SERVER['SERVER_PORT'] <> 443? ':'.$_SERVER['SERVER_PORT']:'');
			$server_name = $protocol.'://'.$_SERVER['SERVER_NAME'].$port;

			if ($return_to === false)
				$return_to = $server_name.$GLOBALS['APPLICATION']->GetCurPageParam('', array('SEF_APPLICATION_CUR_PAGE_URL'), false);

			$return_to = preg_replace("|amp%3B|", '', $return_to);

			if (strlen($arOpenidServerTags['delegate']) > 0)
				$identity = $arOpenidServerTags['delegate'];

			$trust_root = $server_name.'/';

			$url = $arOpenidServerTags['server'] . (strpos($arOpenidServerTags['server'], '?')!==false ? '&' : '?').
				'openid.mode=checkid_setup'.
				'&openid.return_to='.urlencode($return_to).
				'&openid.identity='.urlencode($identity).
				'&openid.trust_root='.urlencode($trust_root).
				'&openid.sreg.required=email,fullname'.
				'&openid.sreg.optional=gender,dob,postcode,country,timezone';
			$_SESSION['BX_OPENID_RETURN_TO'] = $return_to;
			return $url;
		}
		return false;
	}

	public function Validate()
	{
		if(CSocServAuthManager::CheckUniqueKey())
		{
			if ($arOpenidServerTags = $this->GetOpenIDServerTags($_GET['openid_identity']))
			{
				$arParams = array(
					'openid.assoc_handle' => $_GET['openid_assoc_handle'],
					'openid.signed' => $_GET['openid_signed'],
					'openid.sig' => $_GET['openid_sig'],
				);
				$arSigned = explode(',', $_GET['openid_signed']);
				foreach ($arSigned as $s)
					$arParams['openid.' . $s] = $_GET['openid_' . str_replace('.', '_', $s)];

				$arParams['openid.mode'] = 'check_authentication';
				if(isset($_SESSION['BX_OPENID_RETURN_TO']))
				{
					$arParams['openid.return_to'] = $_SESSION['BX_OPENID_RETURN_TO'];
					unset($_SESSION['BX_OPENID_RETURN_TO']);
				}

				$str = CHTTP::sPost($arOpenidServerTags['server'], $arParams, true);

				if (preg_match('/is_valid\s*\:\s*/' . BX_UTF_PCRE_MODIFIER, $str))
				{
					return array(
						'server' => $arOpenidServerTags['server'],
						'identity' => $_GET['openid_identity']
					);
				}
				else
				{
					$GLOBALS['APPLICATION']->ThrowException(GetMessage('OPENID_CLIENT_ERROR_AUTH'));
				}
			}
		}
	//	self::CleanParam('ERROR');
		$GLOBALS['APPLICATION']->ThrowException(GetMessage('OPENID_CLIENT_ERROR_AUTH'));
		return false;
	}

	public static function CleanParam($state=false)
	{
		$arKillParams = array("check_key");
		foreach (array_keys($_GET) as $k)
			if (strpos($k, 'openid_') === 0)
				$arKillParams[] = $k;
		if ($state == 'ERROR')
			$GLOBALS['APPLICATION']->ThrowException(GetMessage('OPENID_CLIENT_ERROR_AUTH'));
		$redirect_url = $GLOBALS['APPLICATION']->GetCurPageParam(($state == 'ERROR' ? 'auth_service_error=1' : ''), $arKillParams, false);
		LocalRedirect($redirect_url, true);
	}

	public function Authorize()
	{
		global $APPLICATION, $USER;
		$errorCode = 1;
		if ($arOpenID = $this->Validate())
		{
			$arFields = array(
				'EXTERNAL_AUTH_ID' => 'OPENID#' . $arOpenID['server'],
				'XML_ID' => $arOpenID['identity'],
				'PASSWORD' => randString(30),
				'LID' => SITE_ID,
				"PERSONAL_WWW" => $arOpenID['identity'],
			);

			if (array_key_exists('openid_sreg_email', $_GET))
				$arFields['EMAIL'] = $_GET['openid_sreg_email'];

			if (array_key_exists('openid_sreg_gender', $_GET) && ($_GET['openid_sreg_gender'] == 'M' || $_GET['openid_sreg_gender'] == 'F'))
				$arFields['PERSONAL_GENDER'] = $_GET['openid_sreg_gender'];

			if (array_key_exists('openid_sreg_fullname', $_GET))
			{
				$fullname = (defined("BX_UTF")? $_GET['openid_sreg_fullname'] : CharsetConverter::ConvertCharset($_GET['openid_sreg_fullname'], 'UTF-8', LANG_CHARSET));
				$fullname = trim($fullname);
				if (($pos = strpos($fullname, ' ')) !== false)
				{
					$arFields['NAME'] = substr($fullname, 0, $pos);
					$arFields['LAST_NAME'] = substr($fullname, $pos + 1);
				}
				else
				{
					$arFields['NAME'] = $fullname;
				}
			}

			if (array_key_exists('openid_sreg_postcode', $_GET))
				$arFields['PERSONAL_ZIP'] = $_GET['openid_sreg_postcode'];

			if (array_key_exists('openid_sreg_timezone', $_GET))
				$arFields['TIME_ZONE'] = $_GET['openid_sreg_timezone'];

			if (array_key_exists('openid_sreg_country', $_GET))
				$arFields['PERSONAL_COUNTRY'] = GetCountryIdByCode($_GET['openid_sreg_country']);

			if (array_key_exists('openid_sreg_dob', $_GET))
				$arFields['PERSONAL_BIRTHDAY'] = CDatabase::FormatDate($_GET['openid_sreg_dob'], "YYYY-MM-DD", FORMAT_DATE);

			if (array_key_exists('BX_OPENID_IDENTITY', $_SESSION))
				$arFields['LOGIN'] = $_SESSION['BX_OPENID_IDENTITY'];
			else
				$arFields['LOGIN'] = $arOpenID['identity'];

			$arFields['LOGIN'] = preg_replace("#^(http://|https://)#i", "", $arFields['LOGIN']);

			$USER_ID = 0;

			if($GLOBALS["USER"]->IsAuthorized() && $GLOBALS["USER"]->GetID())
			{
				if(!CSocServAuth::isSplitDenied())
				{
					$arFields['USER_ID'] = $GLOBALS["USER"]->GetID();
					CSocServAuthDB::Add($arFields);
					self::CleanParam();
				}
				else
				{
					$errorCode = SOCSERV_REGISTRATION_DENY;
				}
			}
			else
			{
				$dbUsersOld = $GLOBALS["USER"]->GetList($by, $ord, array('XML_ID'=>$arFields['XML_ID'], 'EXTERNAL_AUTH_ID'=>$arFields['EXTERNAL_AUTH_ID'], 'ACTIVE'=>'Y'), array('NAV_PARAMS'=>array("nTopCount"=>"1")));
				$dbUsersNew = $GLOBALS["USER"]->GetList($by, $ord, array('XML_ID'=>$arFields['XML_ID'], 'EXTERNAL_AUTH_ID'=>'socservices', 'ACTIVE'=>'Y'),  array('NAV_PARAMS'=>array("nTopCount"=>"1")));
				$dbSocUser = CSocServAuthDB::GetList(array(),array('XML_ID'=>$arFields['XML_ID'], 'EXTERNAL_AUTH_ID'=>$arFields['EXTERNAL_AUTH_ID']),false,false,array("USER_ID", "ACTIVE"));
				if($arUser = $dbSocUser->Fetch())
				{
					if($arUser["ACTIVE"] === 'Y')
						$USER_ID = $arUser["USER_ID"];
				}
				elseif ($arUser = $dbUsersOld->Fetch())
				{
					$USER_ID = $arUser['ID'];
				}
				elseif($arUser = $dbUsersNew->Fetch())
				{
					$USER_ID = $arUser["ID"];
				}
				elseif(COption::GetOptionString("main", "new_user_registration", "N") == "Y")
				{
					$def_group = COption::GetOptionString('main', 'new_user_registration_def_group', '');
					if($def_group != '')
						$arFields['GROUP_ID'] = explode(',', $def_group);

					if(!empty($arFields['GROUP_ID']) && CSocServAuth::isAuthDenied($arFields['GROUP_ID']))
					{
						$errorCode = SOCSERV_REGISTRATION_DENY;
					}
					else
					{
						foreach(GetModuleEvents("main", "OnBeforeOpenIDUserAdd", true) as $arEvent)
							ExecuteModuleEventEx($arEvent, array($arFields));

						$arFieldsUser = $arFields;
						$arFieldsUser["EXTERNAL_AUTH_ID"] = "socservices";
						if(!($USER_ID = $GLOBALS["USER"]->Add($arFieldsUser)))
							return false;
						$arFields['CAN_DELETE'] = 'N';
						$arFields['USER_ID'] = $USER_ID;
						CSocServAuthDB::Add($arFields);
						unset($arFields['CAN_DELETE']);
					}
				}
				elseif(COption::GetOptionString("main", "new_user_registration", "N") == "N")
					$errorCode = 2;

				if (intval($USER_ID) > 0)
				{
					if($arUser && $arUser["XML_ID"] !== $arFields['XML_ID'])
					{
						$USER_ID = 0;
					}
				}

				if (intval($USER_ID) > 0)
				{
					$arGroups = $USER->GetUserGroup($USER_ID);
					if(CSocServAuth::isAuthDenied($arGroups))
					{
						$errorCode = SOCSERV_AUTHORISATION_ERROR;
					}
					else
					{
						$USER->AuthorizeWithOtp($USER_ID);

						$arKillParams = array("auth_service_id", "check_key");
						foreach (array_keys($_GET) as $k)
							if (strpos($k, 'openid_') === 0)
								$arKillParams[] = $k;

						$redirect_url = $APPLICATION->GetCurPageParam('', $arKillParams, false);

						foreach(GetModuleEvents("main", "OnBeforeOpenIDAuthFinalRedirect", true) as $arEvent)
							ExecuteModuleEventEx($arEvent, array($redirect_url, $USER_ID, $arFields));

						if ($redirect_url)
							LocalRedirect($redirect_url, true);

						return $USER_ID;
					}
				}
			}
		}
		$arKillParams = array("check_key");
		foreach (array_keys($_GET) as $k)
			if (strpos($k, 'openid') === 0)
				$arKillParams[] = $k;
		$redirect_url = $APPLICATION->GetCurPageParam('auth_service_error='.$errorCode, $arKillParams, false);
		LocalRedirect($redirect_url, true);
		return false;
	}

	/*public static*/
	public static function GetOpenIDAuthStep($request_var='OPENID_IDENTITY')
	{
		if (array_key_exists('openid_mode', $_GET) && $_GET['openid_mode'] == 'id_res')
			return 2;
		elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && array_key_exists($request_var, $_REQUEST) && strlen($_REQUEST[$request_var]))
			return 1;
		return 0;
	}
}
?>