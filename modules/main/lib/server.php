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
	public function getHttpHost()
	{
		return $this->get("HTTP_HOST");
	}

	/**
	 * Returns server name.
	 *
	 * @return string | null
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
	public function getServerAddr()
	{
		return $this->get("SERVER_ADDR");
	}

	/**
	 * Returns server port.
	 *
	 * @return string | null
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
	public function getRequestUri()
	{
		return $this->get("REQUEST_URI");
	}

	/**
	 * Returns requested method.
	 *
	 * @return string | null
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
