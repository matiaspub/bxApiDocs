<?php
namespace Bitrix\Main\Config;

final class Configuration
	implements \ArrayAccess, \Iterator, \Countable
{
	/**
	 * @var Configuration
	 */
	private static $instance;

	private $data = array();

	const CONFIGURATION_FILE_PATH = "/bitrix/.settings.php";

	public static function getValue($name)
	{
		$configuration = Configuration::getInstance();
		return $configuration->get($name);
	}

	public static function setValue($name, $value)
	{
		$configuration = Configuration::getInstance();
		$configuration->add($name, $value);
		$configuration->saveConfiguration();
	}

	private function __construct()
	{
	}

	/**
	 * @static
	 * @return Configuration
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	private function loadConfiguration()
	{
		$path = \Bitrix\Main\Application::getDocumentRoot().self::CONFIGURATION_FILE_PATH;
		$path = preg_replace("'[\\\/]+'", "/", $path);

		if (file_exists($path))
		{
			$this->data = include($path);
		}
	}

	public function saveConfiguration()
	{
		$path = \Bitrix\Main\Application::getDocumentRoot().self::CONFIGURATION_FILE_PATH;
		$path = preg_replace("'[\\\/]+'", "/", $path);

		$data = var_export($this->data, true);

		if (!is_writable($path))
			@chmod($path, 0644);
		file_put_contents($path, "<"."?php\n\$data=".$data.";\n");
	}

	public function add($name, $value)
	{
		if (empty($this->data))
			$this->loadConfiguration();

		if (!isset($this->data[$name]) || !$this->data[$name]["readonly"])
			$this->data[$name] = array("value" => $value, "readonly" => false);
	}

	private function addReadonly($name, $value)
	{
		if (empty($this->data))
			$this->loadConfiguration();

		$this->data[$name] = array("value" => $value, "readonly" => true);
	}

	public function delete($name)
	{
		if (empty($this->data))
			$this->loadConfiguration();

		if (isset($this->data[$name]) && !$this->data[$name]["readonly"])
			unset($this->data[$name]);
	}

	public function get($name)
	{
		if (empty($this->data))
			$this->loadConfiguration();

		if (isset($this->data[$name]))
			return $this->data[$name]["value"];

		return null;
	}

	public function offsetExists($name)
	{
		if (empty($this->data))
			$this->loadConfiguration();

		return isset($this->data[$name]);
	}

	public function offsetGet($name)
	{
		return $this->get($name);
	}

	public function offsetSet($name, $value)
	{
		$this->add($name, $value);
	}

	public function offsetUnset($name)
	{
		$this->delete($name);
	}

	public function current()
	{
		if (empty($this->data))
			$this->loadConfiguration();

		$c = current($this->data);

		return $c === false ? false : $c["value"];
	}

	public function next()
	{
		if (empty($this->data))
			$this->loadConfiguration();

		$c = next($this->data);

		return $c === false ? false : $c["value"];
	}

	public function key()
	{
		if (empty($this->data))
			$this->loadConfiguration();

		return key($this->data);
	}

	public function valid()
	{
		if (empty($this->data))
			$this->loadConfiguration();

		$key = $this->key();
		return isset($this->data[$key]);
	}

	public function rewind()
	{
		if (empty($this->data))
			$this->loadConfiguration();

		return reset($this->data);
	}

	public function count()
	{
		if (empty($this->data))
			$this->loadConfiguration();

		return count($this->data);
	}

	public static function wnc()
	{
		$configuration = Configuration::getInstance();
		$configuration->loadConfiguration();

		$ar = array(
			"DisableIndexPage" => array("value" => defined('BX_DISABLE_INDEX_PAGE'), "readonly" => false),
			"utf_mode" => array("value" => defined('BX_UTF'), "readonly" => true),
			"DefaultCharset" => array("value" => defined('BX_DEFAULT_CHARSET'), "readonly" => false),
			"DbPersistent" => array("value" => defined('DBPersistent') && DBPersistent, "readonly" => true),
			"DelayDbConnect" => array("value" => defined('DELAY_DB_CONNECT') && DELAY_DB_CONNECT, "readonly" => false),
			"UseMysqlEscapeFunction" => array("value" => defined('BX_USE_ESCAPE_FUNC') ? BX_USE_ESCAPE_FUNC : 0, "readonly" => false),
		//	"CacheSid" => array("value" => defined('BX_CACHE_SID') ? BX_CACHE_SID : "", "readonly" => false),
			"NoAcceleratorReset" => array("value" => defined('BX_NO_ACCELERATOR_RESET'), "readonly" => false),
			"http_status" => array("value" => (defined('BX_HTTP_STATUS') && BX_HTTP_STATUS) ? true : false, "readonly" => false),
			"http_auth_realm" => array("value" => defined('BX_HTTP_AUTH_REALM') ? BX_HTTP_AUTH_REALM : "Bitrix Site Manager", "readonly" => false),
		);

		$cache = array();
		if (defined('BX_CACHE_SID'))
			$cache["sid"] = BX_CACHE_SID;
		if (defined('BX_CACHE_TYPE'))
			$cache["type"] = BX_CACHE_TYPE;
		if (defined('BX_MEMCACHE_CLUSTER'))
		{
			$cache["type"] = array(
				"extension" => "memcache",
				"required_file" => "modules/cluster/classes/general/memcache_cache.php",
				"class_name" => "CPHPCacheMemcacheCluster",
			);
		}
		if (defined("BX_MEMCACHE_PORT"))
			$cache["memcache"]["port"] = intval(BX_MEMCACHE_PORT);
		if (defined("BX_MEMCACHE_HOST"))
			$cache["memcache"]["host"] = BX_MEMCACHE_HOST;
		$ar["cache"] = array("value" => $cache, "readonly" => false);

		$cacheFlags = array();
		$arCacheConsts = array("CACHED_b_option" => "config_options", "CACHED_b_lang_domain" => "site_domain");
		foreach ($arCacheConsts as $const => $name)
			$cacheFlags[$name] = defined($const) ? constant($const) : 0;
		$ar["cache_flags"] = array("value" => $cacheFlags, "readonly" => false);

		$ar["cookies"] = array("value" => array("secure" => false, "http_only" => false), "readonly" => false);

		$ar["exception_handling"] = array(
			"value" => array(
				"debug" => false,
				"handled_errors_types" => E_ALL & ~E_STRICT,
				"exception_errors_types" => E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT,
				"ignore_silence" => false,
				"assertion_throws_exception" => true,
				"assertion_error_type" => E_USER_ERROR,
				"log" => array(
					/*"class_name" => "...",
					"extension" => "...",
					"required_file" => "...",*/
					"settings" => array(
						"file" => "bitrix/modules/error.log",
						"log_size" => 1000000
					)
				),
			),
			"readonly" => false
		);

		foreach ($ar as $k => $v)
		{
			if ($configuration->get($k) === null)
			{
				if ($v["readonly"])
					$configuration->addReadonly($k, $v["value"]);
				else
					$configuration->add($k, $v["value"]);
			}
		}

		$configuration->saveConfiguration();
	}
}
