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
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести  при создании объекта какие-то действия.</p>
	*
	*
	* @param mixed $Bitrix  
	*
	* @param Bitri $Main  массив _GET
	*
	* @param Server $server  массив _POST
	*
	* @param array $queryString  массив _FILES
	*
	* @param array $postData  массив _COOKIE
	*
	* @param array $files  
	*
	* @param array $cookies  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httprequest/__construct.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод применяет фильтр к данным запроса с сохранением оригинальных значений.</p>
	*
	*
	* @param mixed $Bitrix  Объект фильтра
	*
	* @param Bitri $Main  
	*
	* @param Mai $Type  
	*
	* @param IRequestFilter $filter  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httprequest/addfilter.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает GET параметр текущего запроса.</p>
	*
	*
	* @param string $name  Название параметра
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httprequest/getquery.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает список GET параметров текущего запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Type\ParameterDictionary 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httprequest/getquerylist.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает POST параметры текущего запроса.</p>
	*
	*
	* @param mixed $name  Запрос
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httprequest/getpost.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает список POST параметров текущего запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Type\ParameterDictionary 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httprequest/getpostlist.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает параметры FILES текущего запроса.</p>
	*
	*
	* @param mixed $name  
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httprequest/getfile.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает список параметров FILES текущего запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Type\ParameterDictionary 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httprequest/getfilelist.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает параметры COOKIES из текущего запроса.</p> <p>Анаолг метода <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/get_cookie.php" >CMain::get_cookie</a> в старом ядре.</p>
	*
	*
	* @param mixed $name  
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httprequest/getcookie.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает список параметров COOKIES текущего запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Type\ParameterDictionary 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httprequest/getcookielist.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает запрошенный заголовок юзер-агента HTTP.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httprequest/getuseragent.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает текущую страницу, полученную из запрошенного URI.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httprequest/getrequestedpage.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает расшифрованный URL, конвертированный в текущий кодированный URI запроса. (За исключением строки запроса.)</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httprequest/getdecodeduri.php
	* @author Bitrix
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

	/**
	 * Returns the host from the server variable without a port number.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает узел переменной сервера без номера порта.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httprequest/gethttphost.php
	* @author Bitrix
	*/
	public function getHttpHost()
	{
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
		if($this->server->get("SERVER_PORT") == 443)
		{
			return true;
		}

		$https = $this->server->get("HTTPS");
		if($https !== null && strtolower($https) == "on")
		{
			return true;
		}

		return (Config\Configuration::getValue("https_request") === true);
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
	
	/**
	* <p>Нестатический метод возвращает файл скрипта при необходимости откорректированный посредством <b>urlrewrite.php</b> или файл <b>virtual_file_system.php</b>.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httprequest/getscriptfile.php
	* @author Bitrix
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
	
	/**
	* <p>Статический метод возвращает массив с предопределёнными параметрами запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httprequest/getsystemparameters.php
	* @author Bitrix
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
