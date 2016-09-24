<?php
namespace Bitrix\Main;

use Bitrix\Main\Type\ParameterDictionary;

/**
 * Represents server.
 */
class Server
	extends ParameterDictionary
{
	/**
	 * Creates server object.
	 *
	 * @param array $arServer
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести при создании объекта какие-то действия.</p>
	*
	*
	* @param array $arServer  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/server/__construct.php
	* @author Bitrix
	*/
	static public function __construct(array $arServer)
	{
		if (isset($arServer["DOCUMENT_ROOT"]))
			$arServer["DOCUMENT_ROOT"] = rtrim($arServer["DOCUMENT_ROOT"], "/\\");

		parent::__construct($arServer);
	}

	public function addFilter(Type\IRequestFilter $filter)
	{
		$filteredValues = $filter->filter($this->values);

		if ($filteredValues != null)
			$this->setValuesNoDemand($filteredValues);
	}

	/**
	 * Returns server document root.
	 *
	 * @return string | null
	 */
	
	/**
	* <p>Нестатический метод возвращает DOCUMENT_ROOT сервера.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/server/getdocumentroot.php
	* @author Bitrix
	*/
	public function getDocumentRoot()
	{
		return $this->get("DOCUMENT_ROOT");
	}

	/**
	 * Returns custom root folder.
	 * Server variable BX_PERSONAL_ROOT is used. If empty - returns /bitrix.
	 *
	 * @return string | null
	 */
	
	/**
	* <p>Нестатический метод возвращает установленную папку <code>root</code>. Используется серверная переменнтая BX_PERSONAL_ROOT. Если переменная пустая - возвращается <code>/bitrix</code>.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/server/getpersonalroot.php
	* @author Bitrix
	*/
	public function getPersonalRoot()
	{
		$r = $this->get("BX_PERSONAL_ROOT");
		if ($r == null || $r == "")
			$r = "/bitrix";

		return $r;
	}

	/**
	 * Returns server http host.
	 *
	 * @return string | null
	 */
	
	/**
	* <p>Нестатический метод возвращает http хост сервера.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/server/gethttphost.php
	* @author Bitrix
	*/
	public function getHttpHost()
	{
		return $this->get("HTTP_HOST");
	}

	/**
	 * Returns server name.
	 *
	 * @return string | null
	 */
	
	/**
	* <p>Нестатический метод возвращает имя сервера.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/server/getservername.php
	* @author Bitrix
	*/
	public function getServerName()
	{
		return $this->get("SERVER_NAME");
	}

	/**
	 * Returns server address.
	 *
	 * @return string | null
	 */
	
	/**
	* <p>Нестатический метод возвращает адрес сервера.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/server/getserveraddr.php
	* @author Bitrix
	*/
	public function getServerAddr()
	{
		return $this->get("SERVER_ADDR");
	}

	/**
	 * Returns server port.
	 *
	 * @return string | null
	 */
	
	/**
	* <p>Нестатический метод возвращает порт сервера.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/server/getserverport.php
	* @author Bitrix
	*/
	public function getServerPort()
	{
		return $this->get("SERVER_PORT");
	}

	/**
	 * Returns requested uri.
	 * /index.php/test1/test2?login=yes&back_url_admin=/
	 *
	 * @return string | null
	 */
	
	/**
	* <p>Нестатический метод возвращает запрошенный uri вида: <code>/index.php/test1/test2?login=yes&amp;back_url_admin=/</code></p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/server/getrequesturi.php
	* @author Bitrix
	*/
	public function getRequestUri()
	{
		return $this->get("REQUEST_URI");
	}

	/**
	 * Returns requested method.
	 *
	 * @return string | null
	 */
	
	/**
	* <p>Нестатический метод возвращает запрошенный метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/server/getrequestmethod.php
	* @author Bitrix
	*/
	public function getRequestMethod()
	{
		return $this->get("REQUEST_METHOD");
	}

	/**
	 * Returns PHP_SELF.
	 * /index.php/test1/test2
	 *
	 * @return string | null
	 */
	
	/**
	* <p>Нестатический метод возвращает PHP_SELF вида <code>/index.php/test1/test2</code></p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/server/getphpself.php
	* @author Bitrix
	*/
	public function getPhpSelf()
	{
		return $this->get("PHP_SELF");
	}

	/**
	 * Returns SCRIPT_NAME.
	 * /index.php
	 *
	 * @return string | null
	 */
	
	/**
	* <p>Нестатический метод возвращает SCRIPT_NAME вида <code>/index.php</code></p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/server/getscriptname.php
	* @author Bitrix
	*/
	public function getScriptName()
	{
		return $this->get("SCRIPT_NAME");
	}

	public function rewriteUri($url, $queryString, $redirectStatus = null)
	{
		$this->values["REQUEST_URI"] = $url;
		$this->values["QUERY_STRING"] = $queryString;
		if ($redirectStatus != null)
			$this->values["REDIRECT_STATUS"] = $redirectStatus;
	}

	public function transferUri($url, $queryString = "")
	{
		$this->values["REAL_FILE_PATH"] = $url;
		if ($queryString != "")
		{
			if (!isset($this->values["QUERY_STRING"]))
				$this->values["QUERY_STRING"] = "";
			if (isset($this->values["QUERY_STRING"]) && ($this->values["QUERY_STRING"] != ""))
				$this->values["QUERY_STRING"] .= "&";
			$this->values["QUERY_STRING"] .= $queryString;
		}
	}
}
