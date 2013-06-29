<?php
namespace Bitrix\Main;

use \Bitrix\Main\Web;
use \Bitrix\Main\Text;

class HttpRequest
	extends Request
{
	/**
	 * @var System\ReadonlyDictionary
	 */
	protected $queryString;

	/**
	 * @var System\ReadonlyDictionary
	 */
	protected $postData;

	/**
	 * @var System\ReadonlyDictionary
	 */
	protected $files;

	/**
	 * @var System\ReadonlyDictionary
	 */
	protected $cookies;

	public function __construct(Server $server, array $queryString, array $postData, array $files, array $cookies)
	{
		$request = array_merge($queryString, $postData);
		parent::__construct($server, $request);

		$this->queryString = new \Bitrix\Main\System\ReadonlyDictionary($queryString);
		$this->postData = new \Bitrix\Main\System\ReadonlyDictionary($postData);
		$this->files = new \Bitrix\Main\System\ReadonlyDictionary($files);

		$cookiePrefix = \Bitrix\Main\Config\Option::get("main", "cookie_name", "BITRIX_SM")."_";
		$cookiePrefixLength = strlen($cookiePrefix);

		$cookiesNew = array();
		foreach ($cookies as $name => $value)
		{
			$nameNew = $name;
			if (strpos($name, $cookiePrefix) === 0)
				$nameNew = substr($name, $cookiePrefixLength);
			$cookiesNew[$nameNew] = $value;
		}
		$this->cookies = new \Bitrix\Main\System\ReadonlyDictionary($cookiesNew);
	}

	public function getQuery($name)
	{
		return $this->queryString->get($name);
	}

	public function getQueryList()
	{
		return $this->queryString;
	}

	public function getPost($name)
	{
		return $this->postData->get($name);
	}

	public function getPostList()
	{
		return $this->postData;
	}

	public function getFile($name)
	{
		return $this->files->get($name);
	}

	public function getFileList()
	{
		return $this->files;
	}

	public function getCookie($name)
	{
		return $this->cookies->get($name);
	}

	public function getCookieList()
	{
		return $this->cookies;
	}

	public function getRemoteAddress()
	{
		return $this->server->get("REMOTE_ADDR");
	}

	public function getRequestUri()
	{
		return $this->server->getRequestUri();
	}

	public function getRequestMethod()
	{
		return $this->server->getRequestMethod();
	}

	public function isPost()
	{
		return ($this->getRequestMethod() == "POST");
	}

	public function getUserAgent()
	{
		return $this->server->get("HTTP_USER_AGENT");
	}

	public function getAcceptedLanguages()
	{
		static $acceptedLanguages = array();

		if (empty($acceptedLanguages))
		{
			$acceptedLanguagesString = $this->server->get("HTTP_ACCEPT_LANGUAGE");
			$arAcceptedLanguages = explode(",", $acceptedLanguagesString);
			foreach ($arAcceptedLanguages as $langString)
			{
				$arLang = explode(";", $langString);
				$acceptedLanguages[] = $arLang[0];
			}
		}

		return $acceptedLanguages;
	}

	public function getRequestedPage()
	{
		if ($this->requestedFile != null)
			return $this->requestedFile;

		$page = $this->getRequestUri();
		if ($page == "")
			return $this->requestedFile = parent::getRequestedPage();

		$page = urldecode($page);
		$page = Text\Encoding::convertEncodingToCurrent($page);

		$uri = new Web\Uri($page, Web\UriType::RELATIVE);

		return $this->requestedFile = $uri->convertToPath();
	}

	public function getHttpHost($raw = true)
	{
		if ($raw)
			return $this->server->getHttpHost();

		static $host = null;

		if ($host === null)
		{
			$host = $this->server->getHttpHost();
			$hostScheme = $this->isHttps() ? "https://" : "http://";

			$url = new Web\Uri($hostScheme.$host, Web\UriType::ABSOLUTE);

			$host = $url->parse(Web\UriPart::HOST);
			$host = trim($host, "\t\r\n\0 .");
		}

		return $host;
	}

	public function isHttps()
	{
		$port = $this->server->get("SERVER_PORT");
		$https = $this->server->get("HTTPS");
		return ($port == 443 || (($https != null) && (strtolower($https) == "on")));
	}

	public function modifyByQueryString($queryString)
	{
		if ($queryString != "")
		{
			parse_str($queryString, $vars);

			$this->arValues += $vars;
			$this->queryString->arValues += $vars;
		}
	}
}
