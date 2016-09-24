<?php
namespace Bitrix\Main\Web;

class Cookie
{
	const SPREAD_SITES = 1;
	const SPREAD_DOMAIN = 2;

	protected $domain;
	protected $expires;
	protected $httpOnly = true;
	protected $spread;
	protected $name;
	protected $path = '/';
	protected $secure = false;
	protected $value;

	public function __construct($name, $value, $expires = null)
	{
		$this->path = "/";
		$this->name = static::generateCookieName($name);
		$this->value = $value;
		$this->expires = $expires;
		if ($this->expires === null)
			$this->expires = time() + 31104000; //60*60*24*30*12;
		$this->spread = static::SPREAD_DOMAIN | static::SPREAD_SITES;
		$this->setDefaultsFromConfig();
	}

	protected static function generateCookieName($name)
	{
		$cookiePrefix = \Bitrix\Main\Config\Option::get("main", "cookie_name", "BITRIX_SM")."_";
		if (strpos($name, $cookiePrefix) !== 0)
			$name = $cookiePrefix.$name;
		return $name;
	}

	protected function setDefaultsFromConfig()
	{
		$cookiesSettings = \Bitrix\Main\Config\Configuration::getValue("cookies");

		$this->secure = (($cookiesSettings && isset($cookiesSettings["secure"])) ? $cookiesSettings["secure"] : false);
		$this->httpOnly = (($cookiesSettings && isset($cookiesSettings["http_only"])) ? $cookiesSettings["http_only"] : true);
	}

	public function setDomain($domain)
	{
		$this->domain = $domain;
	}

	public function getDomain()
	{
		if (is_null($this->domain))
			$this->domain = $this->getCookieDomain();

		return $this->domain;
	}

	public function setExpires($expires)
	{
		$this->expires = $expires;
	}

	public function getExpires()
	{
		return $this->expires;
	}

	public function setHttpOnly($httpOnly)
	{
		$this->httpOnly = $httpOnly;
	}

	public function getHttpOnly()
	{
		return $this->httpOnly;
	}

	public function setName($name)
	{
		$this->name = static::generateCookieName($name);
	}

	public function getName()
	{
		return $this->name;
	}

	public function setPath($path)
	{
		$this->path = $path;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function setSecure($secure)
	{
		$this->secure = $secure;
	}

	public function getSecure()
	{
		return $this->secure;
	}

	public function setValue($value)
	{
		$this->value = $value;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function setSpread($spread)
	{
		$this->spread = $spread;
	}

	public function getSpread()
	{
		return $this->spread;
	}

	protected function getCookieDomain()
	{
		static $bCache = false;
		static $cache  = false;
		if ($bCache)
			return $cache;

		$context = \Bitrix\Main\Application::getInstance()->getContext();
		$server = $context->getServer();

		$cacheFlags = \Bitrix\Main\Config\Configuration::getValue("cache_flags");
		$cacheTtl = (isset($cacheFlags["site_domain"]) ? $cacheFlags["site_domain"] : 0);

		if ($cacheTtl === false)
		{
			$connection = \Bitrix\Main\Application::getConnection();
			$sqlHelper = $connection->getSqlHelper();

			$sql = "SELECT DOMAIN ".
				"FROM b_lang_domain ".
				"WHERE '".$sqlHelper->forSql('.'.$server->getHttpHost())."' like ".$sqlHelper->getConcatFunction("'%.'", "DOMAIN")." ".
				"ORDER BY ".$sqlHelper->getLengthFunction("DOMAIN")." ";
			$recordset = $connection->query($sql);
			if ($record = $recordset->fetch())
				$cache = $record['DOMAIN'];
		}
		else
		{
			$managedCache = \Bitrix\Main\Application::getInstance()->getManagedCache();

			if ($managedCache->read($cacheTtl, "b_lang_domain", "b_lang_domain"))
			{
				$arLangDomain = $managedCache->get("b_lang_domain");
			}
			else
			{
				$arLangDomain = array("DOMAIN" => array(), "LID" => array());

				$connection = \Bitrix\Main\Application::getConnection();
				$sqlHelper = $connection->getSqlHelper();

				$recordset = $connection->query(
					"SELECT * ".
					"FROM b_lang_domain ".
					"ORDER BY ".$sqlHelper->getLengthFunction("DOMAIN")
				);
				while ($record = $recordset->fetch())
				{
					$arLangDomain["DOMAIN"][] = $record;
					$arLangDomain["LID"][$record["LID"]][] = $record;
				}
				$managedCache->set("b_lang_domain", $arLangDomain);
			}

			foreach ($arLangDomain["DOMAIN"] as $domain)
			{
				if (strcasecmp(substr('.'.$server->getHttpHost(), -(strlen($domain['DOMAIN']) + 1)), ".".$domain['DOMAIN']) == 0)
				{
					$cache = $domain['DOMAIN'];
					break;
				}
			}
		}

		$bCache = true;
		return $cache;
	}
}
