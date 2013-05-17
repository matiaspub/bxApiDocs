<?php
namespace Bitrix\Main;

/**
 * Represents server.
 */
class Server
	extends \Bitrix\Main\System\ReadonlyDictionary
{
	/**
	 * Creates server object.
	 *
	 * @param array $arServer
	 */
	static public function __construct(array $arServer)
	{
		if (isset($arServer["DOCUMENT_ROOT"]))
			$arServer["DOCUMENT_ROOT"] = rtrim($arServer["DOCUMENT_ROOT"], "\\/");

		parent::__construct($arServer);
	}

	/**
	 * Returns server document root.
	 *
	 * @return string | null
	 */
	static public function getDocumentRoot()
	{
		return $this->get("DOCUMENT_ROOT");
	}

	/**
	 * Returns custom root folder.
	 * Server variable BX_PERSONAL_ROOT is used. If empty - returns /bitrix.
	 *
	 * @return string | null
	 */
	static public function getPersonalRoot()
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
	static public function getHttpHost()
	{
		return $this->get("HTTP_HOST");
	}

	/**
	 * Returns server name.
	 *
	 * @return string | null
	 */
	static public function getServerName()
	{
		return $this->get("SERVER_NAME");
	}

	/**
	 * Returns server address.
	 *
	 * @return string | null
	 */
	static public function getServerAddr()
	{
		return $this->get("SERVER_ADDR");
	}

	/**
	 * Returns server port.
	 *
	 * @return string | null
	 */
	static public function getServerPort()
	{
		return $this->get("SERVER_PORT");
	}

	/**
	 * Returns requested uri.
	 * /index.php/test1/test2?login=yes&back_url_admin=/
	 *
	 * @return string | null
	 */
	static public function getRequestUri()
	{
		return $this->get("REQUEST_URI");
	}

	/**
	 * Returns requested method.
	 *
	 * @return string | null
	 */
	static public function getRequestMethod()
	{
		return $this->get("REQUEST_METHOD");
	}

	/**
	 * Returns PHP_SELF.
	 * /index.php/test1/test2
	 *
	 * @return string | null
	 */
	static public function getPhpSelf()
	{
		return $this->get("PHP_SELF");
	}

	/**
	 * Returns SCRIPT_NAME.
	 * /index.php
	 *
	 * @return string | null
	 */
	static public function getScriptName()
	{
		return $this->get("SCRIPT_NAME");
	}

	static public function rewriteUri($url, $queryString, $redirectStatus = null)
	{
		$this->arValues["REQUEST_URI"] = $url;
		$this->arValues["QUERY_STRING"] = $queryString;
		if ($redirectStatus != null)
			$this->arValues["REDIRECT_STATUS"] = $redirectStatus;
	}

	static public function transferUri($url, $queryString)
	{
		$this->arValues["REAL_FILE_PATH"] = $url;
		if ($queryString != "")
		{
			if (!isset($this->arValues["QUERY_STRING"]))
				$this->arValues["QUERY_STRING"] = "";
			if (isset($this->arValues["QUERY_STRING"]) && ($this->arValues["QUERY_STRING"] != ""))
				$this->arValues["QUERY_STRING"] .= "&";
			$this->arValues["QUERY_STRING"] .= $queryString;
		}
	}
}
