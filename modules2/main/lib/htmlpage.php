<?php
namespace Bitrix\Main;

abstract class HtmlPage
	extends Page
{
	protected function initializeRequest()
	{
		$culture = $this->getContext()->getCulture();
		$charset = $culture->getCharset();

		if (
			(!defined("STATISTIC_ONLY") || !STATISTIC_ONLY)
			&& (Config\Option::get("main", "include_charset", "Y") == "Y")
			&& !empty($charset)
		)
		{
			$response = $this->getResponse();
			$response->addHeader("Content-Type", "text/html; charset=".$charset);
		}

		parent::initializeRequest();
	}

	protected function getHtmlToSpreadCookiesOverSites()
	{
		static $firstExecution = true;

		if (!$firstExecution)
			throw new NotSupportedException("getHtmlToSpreadCookiesOverSites() must be called only once");

		if (Config\Option::get("main", "ALLOW_SPREAD_COOKIE", "Y") !== "Y")
			return "";

		$result = "";

		$context = $this->getContext();
		/** @var $response HttpResponse */
		$response = $context->getResponse();

		$params = "";
		foreach ($response->getCookies() as $cookie)
		{
			/** @var $cookie \Bitrix\Main\Web\Cookie */
			if ($cookie->getSpread() & Web\Cookie::SPREAD_SITES)
				$params .= $cookie->getName().chr(1).$cookie->getValue().chr(1).$cookie->getExpires().chr(1).$cookie->getPath().chr(1).''.chr(1).$cookie->getSecure().chr(2);
		}

		if (isset($_SESSION['SPREAD_COOKIE']) && is_array($_SESSION['SPREAD_COOKIE']) && !empty($_SESSION['SPREAD_COOKIE']))
		{
			reset($_SESSION['SPREAD_COOKIE']);
			while (list($cookieName, $cookieData) = each($_SESSION['SPREAD_COOKIE']))
			{
				$cookieData["D"] = ""; // domain must be empty
				$params .= $cookieName.chr(1).$cookieData["V"].chr(1).$cookieData["T"].chr(1).$cookieData["F"].chr(1).$cookieData["D"].chr(1).$cookieData["S"].chr(2);
			}
			unset($_SESSION['SPREAD_COOKIE']);
		}

		if (!empty($params))
		{
			$server = $context->getServer();

			/** @var $request HttpRequest */
			$request = $context->getRequest();
			$versionFile = new IO\File(IO\Path::convertRelativeToAbsolute("/bitrix/modules/main/classes/general/version.php"));
			$salt = $request->getRemoteAddress()."|".$versionFile->getModificationTime()."|".LICENSE_KEY;
			$params = "s=".urlencode(base64_encode($params))."&k=".urlencode(md5($params.$salt));

			$domainList = array();
			$domainList[] = $server->getHttpHost();
			$recordset = SiteTable::getList(
				array(
					'filter' => array('ACTIVE' => 'Y'),
				)
			);
			while ($record = $recordset->Fetch())
			{
				$siteDomainsList = explode("\n", str_replace("\r", "\n", $record["DOMAINS"]));
				if (is_array($siteDomainsList) && count($siteDomainsList) > 0)
				{
					foreach ($siteDomainsList as $d)
					{
						$d = trim($d);
						if (!empty($d))
							$domainList[] = $d;
					}
				}
			}

			if (count($domainList) > 0)
			{
				$uniqueDomainList = array();
				$domainList = array_unique($domainList);
				$domainList2 = array_unique($domainList);
				foreach ($domainList as $domain1)
				{
					$goodDomain = true;
					foreach ($domainList2 as $domain2)
					{
						if (strlen($domain1) > strlen($domain2) && substr($domain1, -(strlen($domain2) + 1)) == ".".$domain2)
						{
							$goodDomain = false;
							break;
						}
					}
					if ($goodDomain)
						$uniqueDomainList[] = $domain1;
				}

				$protocol = ($request->isHttps()) ? "https://" : "http://";
				$host = $server->getHttpHost();
				$requestUri = $request->getRequestUri();

				$currentUri = new Web\Uri($protocol.$host."/".$requestUri, Web\UriType::ABSOLUTE);
				$currentUriParts = $currentUri->parse();

				foreach ($uniqueDomainList as $domain)
				{
					$uriString = $protocol.$domain."/bitrix/spread.php?".$params;
					$domainUri = new Web\Uri($uriString, Web\UriType::ABSOLUTE);
					$domainUriParts = $domainUri->parse();

					if ($currentUriParts["host"] != $domainUriParts["host"])
						$result .= '<img src="'.Text\String::htmlspecialchars($uriString).'" alt="" style="width:0px; height:0px; position:absolute; left:-1px; top:-1px;" />'."\n";
				}
			}
		}

		$firstExecution = false;

		return $result;
	}
}
