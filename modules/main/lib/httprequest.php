<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\Config;
use Bitrix\Main\Type;

/**
 * Class HttpRequest extends Request. Contains http specific request data.
 *
 * @package Bitrix\Main
 */
class HttpRequest extends Request
{
	/**
	 * @var Type\ParameterDictionary
	 */
	protected $queryString;

	/**
	 * @var Type\ParameterDictionary
	 */
	protected $postData;

	/**
	 * @var Type\ParameterDictionary
	 */
	protected $files;

	/**
	 * @var Type\ParameterDictionary
	 */
	protected $cookies;

	/**
	 * @var Type\ParameterDictionary
	 */
	protected $cookiesRaw;

	/**
	 * Creates new HttpRequest object
	 *
	 * @param Server $server
	 * @param array $queryString _GET
	 * @param array $postData _POST
	 * @param array $files _FILES
	 * @param array $cookies _COOKIE
	 */
	public function __construct(Server $server, array $queryString, array $postData, array $files, array $cookies)
	{
		$request = array_merge($queryString, $postData);
		parent::__construct($server, $request);

		$this->queryString = new Type\ParameterDictionary($queryString);
		$this->postData = new Type\ParameterDictionary($postData);
		$this->files = new Type\ParameterDictionary($files);
		$this->cookiesRaw = new Type\ParameterDictionary($cookies);
		$this->cookies = new Type\ParameterDictionary($this->prepareCookie($cookies));
	}

	/**
	 * Applies filter to the http request data. Preserve original values.
	 *
	 * @param Type\IRequestFilter $filter Filter object
	 */
	public function addFilter(Type\IRequestFilter $filter)
	{
		$filteredValues = $filter->filter(array(
			"get" => $this->queryString->values,
			"post" => $this->postData->values,
			"files" => $this->files->values,
			"cookie" => $this->cookiesRaw->values
			));

		if (isset($filteredValues['get']))
			$this->queryString->setValuesNoDemand($filteredValues['get']);
		if (isset($filteredValues['post']))
			$this->postData->setValuesNoDemand($filteredValues['post']);
		if (isset($filteredValues['files']))
			$this->files->setValuesNoDemand($filteredValues['files']);
		if (isset($filteredValues['cookie']))
		{
			$this->cookiesRaw->setValuesNoDemand($filteredValues['cookie']);
			$this->cookies = new Type\ParameterDictionary($this->prepareCookie($filteredValues['cookie']));
		}

		if (isset($filteredValues['get']) || isset($filteredValues['post']))
			$this->values = array_merge($this->queryString->values, $this->postData->values);
	}

	/**
	 * Returns the GET parameter of the current request.
	 *
	 * @param string $name Parameter name
	 * @return null|string
	 */
	public function getQuery($name)
	{
		return $this->queryString->get($name);
	}

	/**
	 * Returns the list of GET parameters of the current request.
	 *
	 * @return Type\ParameterDictionary
	 */
	public function getQueryList()
	{
		return $this->queryString;
	}

	/**
	 * Returns the POST parameter of the current request.
	 *
	 * @param $name
	 * @return null|string
	 */
	public function getPost($name)
	{
		return $this->postData->get($name);
	}

	/**
	 * Returns the list of POST parameters of the current request.
	 *
	 * @return Type\ParameterDictionary
	 */
	public function getPostList()
	{
		return $this->postData;
	}

	/**
	 * Returns the FILES parameter of the current request.
	 *
	 * @param $name
	 * @return null|string
	 */
	public function getFile($name)
	{
		return $this->files->get($name);
	}

	/**
	 * Returns the list of FILES parameters of the current request.
	 *
	 * @return Type\ParameterDictionary
	 */
	public function getFileList()
	{
		return $this->files;
	}

	/**
	 * Returns the COOKIES parameter of the current request.
	 *
	 * @param $name
	 * @return null|string
	 */
	public function getCookie($name)
	{
		return $this->cookies->get($name);
	}

	/**
	 * Returns the list of COOKIES parameters of the current request.
	 *
	 * @return Type\ParameterDictionary
	 */
	public function getCookieList()
	{
		return $this->cookies;
	}

	public function getCookieRaw($name)
	{
		return $this->cookiesRaw->get($name);
	}

	public function getCookieRawList()
	{
		return $this->cookiesRaw;
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

	/**
	 * Returns the User-Agent HTTP request header.
	 * @return null|string
	 */
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

	/**
	 * Returns the current page calculated from the request URI.
	 *
	 * @return string
	 */
	public function getRequestedPage()
	{
		if ($this->requestedPage === null)
		{
			if(($uri = $this->getRequestUri()) == '')
			{
				$this->requestedPage = parent::getRequestedPage();
			}
			else
			{
				$parsedUri = new Web\Uri("http://".$this->server->getHttpHost().$uri);
				$this->requestedPage = static::normalize(static::decode($parsedUri->getPath()));
			}
		}
		return $this->requestedPage;
	}

	/**
	 * Returns url-decoded and converted to the current encoding URI of the request (except the query string).
	 *
	 * @return string
	 */
	public function getDecodedUri()
	{
		$parsedUri = new Web\Uri("http://".$this->server->getHttpHost().$this->getRequestUri());

		$uri = static::decode($parsedUri->getPath());

		if(($query = $parsedUri->getQuery()) <> '')
		{
			$uri .= "?".$query;
		}

		return $uri;
	}

	protected static function decode($url)
	{
		return Text\Encoding::convertEncodingToCurrent(urldecode($url));
	}

	public function getHttpHost($raw = true)
	{
		if ($raw)
		{
			return $this->server->getHttpHost();
		}

		static $host = null;

		if ($host === null)
		{
			//scheme can be anything, it's used only for parsing
			$url = new Web\Uri("http://".$this->server->getHttpHost());
			$host = $url->getHost();
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

			$this->values += $vars;
			$this->queryString->values += $vars;
		}
	}

	/**
	 * @param array $cookies
	 * @return array
	 */
	protected function prepareCookie(array $cookies)
	{
		static $cookiePrefix = null;
		if ($cookiePrefix === null)
			$cookiePrefix = Config\Option::get("main", "cookie_name", "BITRIX_SM")."_";

		$cookiePrefixLength = strlen($cookiePrefix);

		$cookiesNew = array();
		foreach ($cookies as $name => $value)
		{
			if (strpos($name, $cookiePrefix) !== 0)
				continue;

			$cookiesNew[substr($name, $cookiePrefixLength)] = $value;
		}
		return $cookiesNew;
	}

	protected static function normalize($path)
	{
		if (substr($path, -1, 1) === "/")
		{
			$path .= "index.php";
		}

		$path = IO\Path::normalize($path);

		return $path;
	}

	/**
	 * Returns script file possibly corrected by urlrewrite.php or virtual_file_system.php.
	 *
	 * @return string
	 */
	public function getScriptFile()
	{
		$scriptName = $this->getScriptName();
		if($scriptName == "/bitrix/urlrewrite.php" || $scriptName == "/404.php" || $scriptName == "/bitrix/virtual_file_system.php")
		{
			if(($v = $this->server->get("REAL_FILE_PATH")) != null)
			{
				$scriptName = $v;
			}
		}
		return $scriptName;
	}

	/**
	 * Returns the array with predefined query parameters.
	 * @return array
	 */
	public static function getSystemParameters()
	{
		static $params = array(
			"login",
			"logout",
			"register",
			"forgot_password",
			"change_password",
			"confirm_registration",
			"confirm_code",
			"confirm_user_id",
			"bitrix_include_areas",
			"clear_cache",
			"show_page_exec_time",
			"show_include_exec_time",
			"show_sql_stat",
			"show_cache_stat",
			"show_link_stat",
		);
		return $params;
	}
}
