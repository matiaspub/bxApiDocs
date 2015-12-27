<?
IncludeModuleLangFile(__FILE__);

class CSecurityRedirect
{
	public static function BeforeLocalRedirect(&$url, $skip_security_check)
	{
		// ToDo: refactoring candidate

		//This define will be used on buffer end handler
		if(!defined("BX_SECURITY_LOCAL_REDIRECT"))
			// define("BX_SECURITY_LOCAL_REDIRECT", true);

		if(array_key_exists("LOCAL_REDIRECTS", $_SESSION))
		{
			if($_SESSION["LOCAL_REDIRECTS"]["C"] == 0 && strlen($_SESSION["LOCAL_REDIRECTS"]["R"]) == 0)
				$_SESSION["LOCAL_REDIRECTS"]["R"] = $_SERVER["HTTP_REFERER"];

			$_SESSION["LOCAL_REDIRECTS"]["C"]++;
		}
		else
		{
			$_SESSION["LOCAL_REDIRECTS"] = array("C" => 1, "R" => $_SERVER["HTTP_REFERER"]);
		}

		if($skip_security_check)
			return;

		/** global CMain $APPLICATION */
		global $APPLICATION;

		$url_l = str_replace(array("\r", "\n"), "", $url);

		//In case of absolute url will check if server to be redirected is our
		$bSkipCheck = false;
		if(preg_match('~^(?:http|https)://(.*?)(?:/|\?|#|$)~iD', $url_l, $arMatch))
		{
			if(defined("BX24_HOST_NAME"))
			{
				$arSite = array(
					"SERVER_NAME" => BX24_HOST_NAME,
					"DOMAINS" => ""
				);
			}
			elseif(defined("SITE_ID"))
			{
				$rsSite = CSite::GetByID(SITE_ID);
				$arSite = $rsSite->Fetch();
			}
			else
			{
				$arSite = false;
			}

			if(!$bSkipCheck && $arSite && $arSite["SERVER_NAME"])
			{
				$bSkipCheck = $arMatch[1] === $arSite["SERVER_NAME"];
			}

			if(!$bSkipCheck && $arSite && $arSite["DOMAINS"])
			{
				$arDomains = explode("\n", str_replace("\r", "\n", $arSite["DOMAINS"]));
				foreach($arDomains as $domain)
				{
					$domain = trim($domain, " \t\n\r");
					if(strlen($domain) > 0)
					{
						if($domain === substr($arMatch[1], -strlen($domain)))
						{
							$bSkipCheck = true;
							break;
						}
					}
				}
			}

			if(!$bSkipCheck)
			{
				$host = COption::GetOptionString("main", "server_name", "");
				$bSkipCheck = $host && $arMatch[1] === $host;
			}
		}

		if(!$bSkipCheck && preg_match("/^(http|https|ftp):\\/\\//i", $url_l))
		{
			$good = true;

			if($_SESSION["LOCAL_REDIRECTS"]["C"] > 1)
				$REFERER_TO_CHECK = $_SESSION["LOCAL_REDIRECTS"]["R"];
			else
				$REFERER_TO_CHECK = $_SERVER["HTTP_REFERER"];

			if($good && COption::GetOptionString("security", "redirect_referer_check") == "Y")
			{
				$good &= strlen($REFERER_TO_CHECK) > 0;
			}

			if($good && strlen($REFERER_TO_CHECK) > 0 && COption::GetOptionString("security", "redirect_referer_site_check") == "Y")
			{
				$valid_site = ($APPLICATION->IsHTTPS()? "https://": "http://").$_SERVER['HTTP_HOST']."/";
				$good &= strpos($REFERER_TO_CHECK, $valid_site) === 0;
			}

			if($good && COption::GetOptionString("security", "redirect_href_sign") == "Y")
			{
				$sid = COption::GetOptionString("security", "redirect_sid").$_SERVER["REMOTE_ADDR"];
				$good &= md5($sid.":".$url) === $_GET["af"];
			}

			if(!$good)
			{
				global $APPLICATION;

				if(COption::GetOptionString("security", "redirect_log") == "Y")
					CSecurityEvent::getInstance()->doLog(
							"SECURITY",
							"SECURITY_REDIRECT",
							$APPLICATION->GetCurPage(),
							$url
					);

				if(COption::GetOptionString("security", "redirect_action") == "show_message")
				{
					$timeout = intval(COption::GetOptionString("security", "redirect_message_timeout"));
					if($timeout <= 0)
						$timeout = 30;

					$mess = COption::GetOptionString("security", "redirect_message_warning_".LANGUAGE_ID);
					if(strlen($mess) <= 0)
						$mess = COption::GetOptionString("security", "redirect_message_warning");
					$charset = COption::GetOptionString("security", "redirect_message_charset");
					if(strlen($mess) <= 0)
					{
						$mess = CSecurityRedirect::GetDefaultMessage();
						$charset = LANG_CHARSET;
					}
					$html_mess = str_replace("+", "&#43;", htmlspecialcharsbx($mess));

					$url = htmlspecialcharsbx($url);
					$html_url = '<nobr><a href="'.$url.'">'.$url.'</a></nobr>';
					$html_mess = str_replace("#URL#", $html_url, $html_mess);
					header('X-Frame-Options: DENY');
					header('X-Robots-Tag: noindex, nofollow');
		?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?echo $charset?>" />
<meta http-equiv="Refresh" content="<?=$timeout?>; URL=<?=$url?>">
<meta name="robots" content="noindex, nofollow" />
<link rel="stylesheet" type="text/css" href="/bitrix/themes/.default/adminstyles.css" />
<link rel="stylesheet" type="text/css" href="/bitrix/themes/.default/404.css" />
</head>
<body>

<div class="error-404">
<table class="error-404" border="0" cellpadding="0" cellspacing="0" align="center">
	<tbody><tr class="top">
		<td class="left"><div class="empty"></div></td>
		<td><div class="empty"></div></td>
		<td class="right"><div class="empty"></div></td>
	</tr>
	<tr>
		<td class="left"><div class="empty"></div></td>
		<td class="content">
			<div class="description">
				<table cellpadding="0" cellspacing="0">
					<tbody><tr>
						<td><div class="icon"></div></td>
						<td><?=$html_mess?></td>
					</tr>
				</tbody></table>
			</div>
		</td>
		<td class="right"><div class="empty"></div></td>
	</tr>
	<tr class="bottom">
		<td class="left"><div class="empty"></div></td>
		<td><div class="empty"></div></td>
		<td class="right"><div class="empty"></div></td>
	</tr>
</tbody></table>
</div>
</body>
</html>
		<?
					die();
				}
				else
				{
					$url = COption::GetOptionString("security", "redirect_url");
				}
			}
		}
	}

	public static function GetDefaultMessage($language_id = false)
	{
		if($language_id===false)
		{
			return GetMessage("SEC_REDIRECT_DEFAULT_MESSAGE");
		}
		else
		{
			$mess = IncludeModuleLangFile(__FILE__, $language_id, true);
			if(strlen($mess["SEC_REDIRECT_DEFAULT_MESSAGE"]))
				return $mess["SEC_REDIRECT_DEFAULT_MESSAGE"];
			else
				return GetMessage("SEC_REDIRECT_DEFAULT_MESSAGE");
		}
	}

	public static function EndBufferContent(&$content)
	{
		//There was no looped local redirects
		//so it's only true referer
		if(!defined("BX_SECURITY_LOCAL_REDIRECT"))
			$_SESSION["LOCAL_REDIRECTS"] = array("C" => 0, "R" => $_SERVER["HTTP_REFERER"]);

		if(COption::GetOptionString("security", "redirect_href_sign") == "Y")
			$content = preg_replace_callback("#(<a\\s[^>/]*?href\\s*=\\s*)(['\"])(.+?)(\\2)#i", array("self", "ReplaceHREF"), $content);
	}

	protected static function ReplaceHREF($matches)
	{
		static $arUrls = false;
		static $sid = false;
		static $strDomains = false;

		if(!$arUrls)
		{
			$arUrls = self::GetUrls();
			$sid = COption::GetOptionString("security", "redirect_sid").$_SERVER["REMOTE_ADDR"];
			$arDomains = self::GetDomains();
			foreach($arDomains as $i => $domain)
				$arDomains[$i] = preg_quote($domain, "/");
			$strDomains = "/.*(".implode("|", $arDomains).")$/";
		}

		foreach($arUrls as $arUrl)
		{
			if(preg_match("/^(http(?:s){0,1}\\:\\/\\/(?:[a-zA-Z0-9\\.-])+){0,1}".preg_quote($arUrl["URL"], "/")."?.*?".preg_quote($arUrl["PARAMETER_NAME"], "/")."=(http|https|ftp)(:|%3A|&#37;3A)(\\/\\/|%2F%2F|&#37;2F&#37;2F)([^&]+)/im", $matches[3], $match))
			{
				if(strlen($match[1]) <= 0 || preg_match($strDomains, $match[1]))
				{
					$goto = $match[2].$match[3].$match[4].$match[5];
					$goto = str_replace(
						array("&#37;", "%3A", "%2F"),
						array("%", ":", "/"),
					$goto);

					return $matches[1].$matches[2].$matches[3]."&amp;af=".urlencode(md5($sid.":".urldecode($goto))).$matches[4];
				}
			}
		}
		return $matches[0];
	}

	public static function GetUrls()
	{
		/**
		 * global CDatabase $DB
		 * global CCacheManager $CACHE_MANAGER
		 */
		global $DB, $CACHE_MANAGER;
		if(CACHED_b_sec_redirect_url !== false)
		{
			$cache_id = "b_sec_redirect_url";
			if($CACHE_MANAGER->Read(CACHED_b_sec_filter_mask, $cache_id, "b_sec_redirect_url"))
			{
				$arUrls = $CACHE_MANAGER->Get($cache_id);
			}
			else
			{
				$arUrls = array();
				$rs = $DB->Query("SELECT URL, PARAMETER_NAME, IS_SYSTEM from b_sec_redirect_url ORDER BY IS_SYSTEM DESC, SORT ASC");
				while($ar = $rs->Fetch())
					$arUrls[] = $ar;

				$CACHE_MANAGER->Set($cache_id, $arUrls);
			}
		}
		else
		{
			$arUrls = array();
			$rs = $DB->Query("SELECT URL, PARAMETER_NAME, IS_SYSTEM from b_sec_redirect_url ORDER BY IS_SYSTEM DESC, SORT ASC");
			while($ar = $rs->Fetch())
				$arUrls[] = $ar;
		}
		return $arUrls;
	}

	public static function GetDomains()
	{
		/**
		 * global CDatabase $DB
		 * global CCacheManager $CACHE_MANAGER
		 */
		global $DB, $CACHE_MANAGER;
		if(CACHED_b_lang_domain !== false)
		{
			if($CACHE_MANAGER->Read(CACHED_b_lang_domain, "b_sec_domains", "b_lang_domain"))
			{
				$arDomains = $CACHE_MANAGER->Get("b_sec_domains");
			}
			else
			{
				$arDomains = array();
				$rs = $DB->Query("SELECT DOMAIN from b_lang_domain");
				while($ar = $rs->Fetch())
					$arDomains[] = $ar["DOMAIN"];

				$CACHE_MANAGER->Set("b_sec_domains", $arDomains);
			}
		}
		else
		{
			$arDomains = array();
			$rs = $DB->Query("SELECT DOMAIN from b_lang_domain");
			while($ar = $rs->Fetch())
				$arDomains[] = $ar["DOMAIN"];
		}
		return $arDomains;
	}

	public static function IsActive()
	{
		$bActive = false;
		foreach(GetModuleEvents("main", "OnBeforeLocalRedirect", true) as $event)
		{
			if(
				$event["TO_MODULE_ID"] == "security"
				&& $event["TO_CLASS"] == "CSecurityRedirect"
			)
			{
				$bActive = true;
				break;
			}
		}
		return $bActive;
	}

	public static function SetActive($bActive = false)
	{
		if($bActive)
		{
			if(!CSecurityRedirect::IsActive())
			{
				COption::SetOptionString("security", "redirect_sid", Bitrix\Main\Security\Random::getString(32));
				RegisterModuleDependences("main", "OnBeforeLocalRedirect", "security", "CSecurityRedirect", "BeforeLocalRedirect", "1");
				RegisterModuleDependences("main", "OnEndBufferContent", "security", "CSecurityRedirect", "EndBufferContent", "1");
			}
		}
		else
		{
			if(CSecurityRedirect::IsActive())
			{
				UnRegisterModuleDependences("main", "OnBeforeLocalRedirect", "security", "CSecurityRedirect", "BeforeLocalRedirect");
				UnRegisterModuleDependences("main", "OnEndBufferContent", "security", "CSecurityRedirect", "EndBufferContent");
			}
		}
	}

	public static function Update($arUrls)
	{
		/**
		 * global CDatabase $DB
		 * global CCacheManager $CACHE_MANAGER
		 */
		global $DB, $CACHE_MANAGER;

		if(is_array($arUrls))
		{
			$res = $DB->Query("DELETE FROM b_sec_redirect_url WHERE IS_SYSTEM <> 'Y'", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if($res)
			{
				$added = array();
				$i = 10;
				foreach($arUrls as $arUrl)
				{
					$url = trim($arUrl["URL"]);
					$param = trim($arUrl["PARAMETER_NAME"]);
					$key = $url.":".$param;

					if(strlen($url) && strlen($param) && !array_key_exists($key, $added))
					{
						$arUrl = array(
							"ID" => 1,
							"IS_SYSTEM" => "N",
							"SORT" => $i,
							"URL" => $url,
							"PARAMETER_NAME" => $param,
						);

						$DB->Add("b_sec_redirect_url", $arUrl);
						$i += 10;
						$added[$key] = true;
					}
				}

				if(CACHED_b_sec_redirect_url !== false)
					$CACHE_MANAGER->CleanDir("b_sec_redirect_url");

			}
		}

		return true;
	}

	public static function GetList()
	{
		/** global CDatabase $DB */
		global $DB;
		$res = $DB->Query("SELECT URL, PARAMETER_NAME, IS_SYSTEM from b_sec_redirect_url ORDER BY IS_SYSTEM DESC, SORT ASC");
		return $res;
	}

}
?>