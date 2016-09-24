<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Main\Web;

class Uri
{
	protected $scheme;
	protected $host;
	protected $port;
	protected $user;
	protected $pass;
	protected $path;
	protected $query;
	protected $fragment;

	/**
	 * @param string $url
	 */
	public function __construct($url)
	{
		if(strpos($url, "/") === 0)
		{
			//we don't support "current scheme" e.g. "//host/path"
			$url = "/".ltrim($url, "/");
		}

		$parsedUrl = parse_url($url);

		if($parsedUrl !== false)
		{
			$this->scheme = (isset($parsedUrl["scheme"])? strtolower($parsedUrl["scheme"]) : "http");
			$this->host = $parsedUrl["host"];
			if(isset($parsedUrl["port"]))
			{
				$this->port = $parsedUrl["port"];
			}
			else
			{
				$this->port = ($this->scheme == "https"? 443 : 80);
			}
			$this->user = $parsedUrl["user"];
			$this->pass = $parsedUrl["pass"];
			$this->path = ((isset($parsedUrl["path"])? $parsedUrl["path"] : "/"));
			$this->query = $parsedUrl["query"];
			$this->fragment = $parsedUrl["fragment"];
		}
	}

	/**
	 * @deprecated Use getLocator() or getUri().
	 */
	public function getUrl()
	{
		return $this->getLocator();
	}

	/**
	 * Return the URI without a fragment.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает URI без фрагмента.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/uri/getlocator.php
	* @author Bitrix
	*/
	public function getLocator()
	{
		$url = "";
		if($this->host <> '')
		{
			$url .= $this->scheme."://".$this->host;

			if(($this->scheme == "http" && $this->port <> 80) || ($this->scheme == "https" && $this->port <> 443))
			{
				$url .= ":".$this->port;
			}
		}

		$url .= $this->getPathQuery();

		return $url;
	}

	/**
	 * Return the URI with a fragment, if any.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает URI с фрагментом, если он имеется.</p> <p>Выполняет функции методов <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getcurpageparam.php" >CMain::GetCurPageParam</a> и <a href="http://dev.1c-bitrix.ru/api_help/main/functions/other/deleteparam.ph" >DeleteParam</a> в старом ядре.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/uri/geturi.php
	* @author Bitrix
	*/
	public function getUri()
	{
		$url = $this->getLocator();

		if($this->fragment <> '')
		{
			$url .= "#".$this->fragment;
		}

		return $url;
	}

	/**
	 * Returns the fragment.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает фрагмент.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/uri/getfragment.php
	* @author Bitrix
	*/
	public function getFragment()
	{
		return $this->fragment;
	}

	/**
	 * Returns the host.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает хост.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/uri/gethost.php
	* @author Bitrix
	*/
	public function getHost()
	{
		return $this->host;
	}

	/**
	 * Sets the host
	 * @param string $host Host name.
	 * @return $this
	 */
	
	/**
	* <p>Нестатический метод устанавливает хост.</p>
	*
	*
	* @param string $host  Host name.
	*
	* @return \Bitrix\Main\Web\Uri 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/uri/sethost.php
	* @author Bitrix
	*/
	public function setHost($host)
	{
		$this->host = $host;
		return $this;
	}

	/**
	 * Returns the password.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает пароль.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/uri/getpass.php
	* @author Bitrix
	*/
	public function getPass()
	{
		return $this->pass;
	}

	/**
	 * Returns the path.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает путь.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/uri/getpath.php
	* @author Bitrix
	*/
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Sets the path.
	 * @param string $path
	 * @return $this
	 */
	
	/**
	* <p>Нестатический метод устанавливает путь.</p>
	*
	*
	* @param string $path  
	*
	* @return \Bitrix\Main\Web\Uri 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/uri/setpath.php
	* @author Bitrix
	*/
	public function setPath($path)
	{
		$this->path = $path;
		return $this;
	}

	/**
	 * Returns the path with the query.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает путь с запросом.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/uri/getpathquery.php
	* @author Bitrix
	*/
	public function getPathQuery()
	{
		$pathQuery = $this->path;
		if($this->query <> "")
		{
			$pathQuery .= '?'.$this->query;
		}
		return $pathQuery;
	}

	/**
	 * Returns the port number.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает номер порта.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/uri/getport.php
	* @author Bitrix
	*/
	public function getPort()
	{
		return $this->port;
	}

	/**
	 * Returns the query.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает запрос.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/uri/getquery.php
	* @author Bitrix
	*/
	public function getQuery()
	{
		return $this->query;
	}

	/**
	 * Returns the scheme.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает схему.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/uri/getscheme.php
	* @author Bitrix
	*/
	public function getScheme()
	{
		return $this->scheme;
	}

	/**
	 * Returns the user.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает пользователя.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/uri/getuser.php
	* @author Bitrix
	*/
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * Deletes parameters from the query.
	 * @param array $params Parameters to delete.
	 * @return $this
	 */
	
	/**
	* <p>Нестатический метод удаляет параметры из запроса.</p> <p>Выполняет функции методов <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getcurpageparam.php" >CMain::GetCurPageParam </a> и <a href="http://dev.1c-bitrix.ru/api_help/main/functions/other/deleteparam.ph" >DeleteParam</a> в старом ядре.</p>
	*
	*
	* @param array $params  Удаляемые параметры.
	*
	* @return \Bitrix\Main\Web\Uri 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/uri/deleteparams.php
	* @author Bitrix
	*/
	public function deleteParams(array $params)
	{
		if($this->query <> '')
		{
			$currentParams = array();
			parse_str($this->query, $currentParams);

			foreach($params as $param)
			{
				unset($currentParams[$param]);
			}

			$this->query = http_build_query($currentParams, "", "&");
		}
		return $this;
	}

	/**
	 * Adds parameters to query or replaces existing ones.
	 * @param array $params Parameters to add.
	 * @return $this
	 */
	
	/**
	* <p>Нестатический метод добавляет параметры в запрос или заменяет существующие параметры.</p> <p>Выполняет функции метода <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getcurpageparam.php" >CMain::GetCurPageParam </a> в старом ядре.</p>
	*
	*
	* @param array $params  Параметры для добавления.
	*
	* @return \Bitrix\Main\Web\Uri 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/uri/addparams.php
	* @author Bitrix
	*/
	public function addParams(array $params)
	{
		$currentParams = array();
		if($this->query <> '')
		{
			parse_str($this->query, $currentParams);
		}

		$currentParams = array_replace($currentParams, $params);

		$this->query = http_build_query($currentParams, "", "&");

		return $this;
	}
}
