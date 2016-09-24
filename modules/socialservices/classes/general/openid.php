<?
IncludeModuleLangFile(__FILE__);

class CSocServOpenID
{
	static public function Authorize($identity=false, $var=false)
	{
		if($var === false)
			$var = 'OPENID_IDENTITY_OPENID';

		$step = COpenIDClient::GetOpenIDAuthStep($var);
		if($step > 0)
		{
			$obOpenID = new COpenIDClient();

			if($step == 2)
			{
				return $obOpenID->Authorize();
			}
			elseif($step == 1)
			{
				if($identity === false || is_array($identity))
				{
					$identity = $_REQUEST[$var];
				}

				CSocServAuthManager::SetUniqueKey();
				$return_to = CSocServUtil::GetCurUrl("auth_service_id=".urlencode($_REQUEST["auth_service_id"])."&check_key=".$_SESSION["UNIQUE_KEY"], array("SEF_APPLICATION_CUR_PAGE_URL", "auth_service_error", "auth_service_id", "login"));

				if($url = $obOpenID->GetRedirectUrl($identity, $return_to))
					LocalRedirect($url, true);
				else
					LocalRedirect(CSocServUtil::GetCurUrl("auth_service_id=".urlencode($_REQUEST["auth_service_id"])."&auth_service_error=1"));
					return false;
			}
		}
		return false;
	}

	static public function GetFormHtml($arParams)
	{
		return '
<span class="bx-ss-icon openid"></span>
<span>'.'OpenID:'.'</span>
<input type="text" name="OPENID_IDENTITY_OPENID" value="'.$arParams["LAST_LOGIN"].'" size="30" />
'.bitrix_sessid_post().'
<input type="hidden" name="auth_service_error" value="" />
<input type="submit" class="button" name="" value="'.GetMessage("socserv_openid_login").'" />
';
	}
}

class CSocServYandex extends CSocServOpenID
{
	static public function Authorize($identity=false, $var=false)
	{
		if($identity === false || is_array($identity))
			$identity = "http://openid.yandex.ru/".$_REQUEST['OPENID_IDENTITY_YANDEX'];

		return parent::Authorize($identity, 'OPENID_IDENTITY_YANDEX');
	}

	static public function GetFormHtml($arParams)
	{
		$login = '';
		$matches = array();
		if(preg_match('#openid.yandex.ru/([^/$]+)#i', $arParams["~LAST_LOGIN"], $matches))
			$login = $matches[1];
		return '
<span class="bx-ss-icon yandex"></span>
<input type="text" name="OPENID_IDENTITY_YANDEX" value="'.htmlspecialcharsbx($login).'" size="20" />
<span>@yandex.ru</span>
'.bitrix_sessid_post().'
<input type="hidden" name="auth_service_error" value="" />
<input type="submit" class="button" name="" value="'.GetMessage("socserv_openid_login").'" />
';
	}
}

class CSocServMailRu extends CSocServOpenID
{
	static public function Authorize($identity=false, $var=false)
	{
		if($identity === false || is_array($identity))
			$identity = "http://openid.mail.ru/mail/".$_REQUEST['OPENID_IDENTITY_MAILRU'];

		return parent::Authorize($identity, 'OPENID_IDENTITY_MAILRU');
	}

	static public function GetFormHtml($arParams)
	{
		$login = '';
		$matches = array();
		if(preg_match('#openid.mail.ru/mail/([^/$]+)#i', $arParams["~LAST_LOGIN"], $matches))
			$login = $matches[1];

		return '
<span class="bx-ss-icon openid-mail-ru"></span>
<input type="text" name="OPENID_IDENTITY_MAILRU" value="'.htmlspecialcharsbx($login).'" size="20" />
<span>@mail.ru</span>
'.bitrix_sessid_post().'
<input type="hidden" name="auth_service_error" value="" />
<input type="submit" class="button" name="" value="'.GetMessage("socserv_openid_login").'" />
';
	}
}

class CSocServLivejournal extends CSocServOpenID
{
	static public function Authorize($identity=false, $var=false)
	{
		if($identity === false || is_array($identity))
			$identity = $_REQUEST['OPENID_IDENTITY_LIVEJOURNAL'].".livejournal.com";

		return parent::Authorize($identity, 'OPENID_IDENTITY_LIVEJOURNAL');
	}

	static public function GetFormHtml($arParams)
	{
		$login = '';
		$matches = array();
		if(preg_match('#([^\.]+).livejournal.com#i', $arParams["~LAST_LOGIN"], $matches))
			$login = $matches[1];
		return '
<span class="bx-ss-icon livejournal"></span>
<input type="text" name="OPENID_IDENTITY_LIVEJOURNAL" value="'.htmlspecialcharsbx($login).'" size="20" />
<span>.livejournal.com</span>
'.bitrix_sessid_post().'
<input type="hidden" name="auth_service_error" value="" />
<input type="submit" class="button" name="" value="'.GetMessage("socserv_openid_login").'" />
';
	}
}

class CSocServLiveinternet extends CSocServOpenID
{
	static public function Authorize($identity=false, $var=false)
	{
		if($identity === false || is_array($identity))
			$identity = "http://www.liveinternet.ru/users/".$_REQUEST['OPENID_IDENTITY_LIVEINTERNET']."/";

		return parent::Authorize($identity, 'OPENID_IDENTITY_LIVEINTERNET');
	}

	static public function GetFormHtml($arParams)
	{
		$login = '';
		$matches = array();
		if(preg_match('#www.liveinternet.ru/users/([^/$]+)#i', $arParams["~LAST_LOGIN"], $matches))
			$login = $matches[1];
		return '
<span class="bx-ss-icon liveinternet"></span>
<span>liveinternet.ru/users/</span>
<input type="text" name="OPENID_IDENTITY_LIVEINTERNET" value="'.htmlspecialcharsbx($login).'" size="15" />
'.bitrix_sessid_post().'
<input type="hidden" name="auth_service_error" value="" />
<input type="submit" class="button" name="" value="'.GetMessage("socserv_openid_login").'" />
';
	}
}

class CSocServBlogger extends CSocServOpenID
{
	static public function Authorize($identity=false, $var=false)
	{
		if($identity === false || is_array($identity))
			$identity = "http://".$_REQUEST['OPENID_IDENTITY_BLOGGER'].".blogspot.com/";

		return parent::Authorize($identity, 'OPENID_IDENTITY_BLOGGER');
	}

	static public function GetFormHtml($arParams)
	{
		$login = '';
		$matches = array();
		if(preg_match('#([^\.]+).blogspot.com#i', $arParams["~LAST_LOGIN"], $matches))
			$login = $matches[1];
		return '
<span class="bx-ss-icon blogger"></span>
<input type="text" name="OPENID_IDENTITY_BLOGGER" value="'.htmlspecialcharsbx($login).'" size="20" />
<span>.blogspot.com</span>
'.bitrix_sessid_post().'
<input type="hidden" name="auth_service_error" value="" />
<input type="submit" class="button" name="" value="'.GetMessage("socserv_openid_login").'" />
';
	}
}

class CSocServRambler extends CSocServOpenID
{
	static public function Authorize($identity=false, $var=false)
	{
		if($identity === false || is_array($identity))
			$identity = "http://id.rambler.ru/users/".$_REQUEST['OPENID_IDENTITY_RAMBLER'];

		return parent::Authorize($identity, 'OPENID_IDENTITY_RAMBLER');
	}

	static public function GetFormHtml($arParams)
	{
		$login = '';
		$matches = array();
		if(preg_match('#id.rambler.ru/users/([^/$]+)#i', $arParams["~LAST_LOGIN"], $matches))
			$login = $matches[1];
		return '
<span class="bx-ss-icon rambler"></span>
<input type="text" name="OPENID_IDENTITY_RAMBLER" value="'.htmlspecialcharsbx($login).'" size="20" />
<span>@rambler.ru</span>
'.bitrix_sessid_post().'
<input type="hidden" name="auth_service_error" value="" />
<input type="submit" class="button" name="" value="'.GetMessage("socserv_openid_login").'" />
';
	}
}

?>