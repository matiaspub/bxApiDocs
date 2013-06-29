<?php
namespace Bitrix\Main;

class HttpResponse
	extends Response
{
	private $cookies = array();
	private $headers = array();

	//private $contextType;

	public function addHeader($name, $value = '')
	{
		if (empty($name))
			throw new ArgumentNullException("name");

		if ($value == "")
			$this->headers[] = $name;
		else
			$this->headers[] = array($name, $value);
	}

	public function addCookie(\Bitrix\Main\Web\Cookie $cookie)
	{
		$this->cookies[] = $cookie;
	}

	public function getCookies()
	{
		return $this->cookies;
	}

	public function storeCookies()
	{
		$storedCookies = array();

		foreach ($this->cookies as $cookie)
		{
			/** @var $cookie \Bitrix\Main\Web\Cookie */
			if ($cookie->getSpread() & \Bitrix\Main\Web\Cookie::SPREAD_SITES)
				$storedCookies[$cookie->getName()] = array("V" => $cookie->getValue(), "T" => $cookie->getExpires(), "F" => $cookie->getPath(), "D" => $cookie->getDomain(), "S" => $cookie->getSecure(), "H" => $cookie->getHttpOnly());
		}

		$_SESSION['SPREAD_COOKIE'] = $storedCookies;
	}

	private function createStandardHeaders()
	{
		$server = $this->context->getServer();
		if (($server->get("REDIRECT_STATUS") != null) && ($server->get("REDIRECT_STATUS") == 404))
		{
			if (Config\Option::get("main", "header_200", "N") == "Y")
				$this->setStatus("200 OK");
		}

		$dispatcher = Application::getInstance()->getDispatcher();
		$key = $dispatcher->getLicenseKey();
		$this->addHeader("X-Powered-CMS", "Bitrix Site Manager (".($key == "DEMO" ? "DEMO" : md5("BITRIX".$key."LICENCE")).")");

		if (Config\Option::get("main", "set_p3p_header", "Y") == "Y")
			$this->addHeader("P3P", "policyref=\"/bitrix/p3p.xml\", CP=\"NON DSP COR CUR ADM DEV PSA PSD OUR UNR BUS UNI COM NAV INT DEM STA\"");
	}

	protected function writeHeaders()
	{
		$this->createStandardHeaders();

		if (is_array($this->headers))
		{
			foreach ($this->headers as $header)
				$this->setHeader($header);
		}
		if (is_array($this->cookies))
		{
			foreach ($this->cookies as $cookie)
				$this->setCookie($cookie);
		}
	}

	protected function setHeader($header)
	{
		if (is_array($header))
			header(sprintf("%s: %s", $header[0], $header[1]));
		else
			header($header);
	}

	protected function setCookie(\Bitrix\Main\Web\Cookie $cookie)
	{
		if ($cookie->getSpread() & \Bitrix\Main\Web\Cookie::SPREAD_DOMAIN)
		{
			setcookie(
				$cookie->getName(),
				$cookie->getValue(),
				$cookie->getExpires(),
				$cookie->getPath(),
				$cookie->getDomain(),
				$cookie->getSecure(),
				$cookie->getHttpOnly()
			);
		}
	}

	public function setStatus($status)
	{
		$httpStatus = \Bitrix\Main\Config\Configuration::getValue("http_status");

		$cgiMode = (stristr(php_sapi_name(), "cgi") !== false);
		if ($cgiMode && (($httpStatus == null) || ($httpStatus == false)))
		{
			$this->addHeader("Status", $status);
		}
		else
		{
			$server = $this->context->getServer();
			$this->addHeader($server->get("SERVER_PROTOCOL")." ".$status);
		}
	}


}
