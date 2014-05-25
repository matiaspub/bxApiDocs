<?php
namespace Bitrix\Main\Config;

use Bitrix\Main;

final class Configuration
	implements \ArrayAccess, \Iterator, \Countable
{
	/**
	 * @var Configuration
	 */
	private static $instance;

	private $storedData = null;
	private $data = array();
	private $isLoaded = false;

	const CONFIGURATION_FILE_PATH = "/bitrix/.settings.php";
	const CONFIGURATION_FILE_PATH_EXTRA = "/bitrix/.settings_extra.php";

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

	private function getPath($path)
	{
		$path = Main\Loader::getDocumentRoot().$path;
		return preg_replace("'[\\\\/]+'", "/", $path);
	}

	private function loadConfiguration()
	{
		$this->isLoaded = false;

		$path = static::getPath(self::CONFIGURATION_FILE_PATH);
		if (file_exists($path))
		{
			$this->data = include($path);
		}

		$pathExtra = static::getPath(self::CONFIGURATION_FILE_PATH_EXTRA);
		if (file_exists($pathExtra))
		{
			$dataTmp = include($pathExtra);
			if (is_array($dataTmp) && !empty($dataTmp))
			{
				$this->storedData = $this->data;
				foreach ($dataTmp as $k => $v)
				{
					$this->data[$k] = $v;
				}
			}
		}

		$this->isLoaded = true;
	}

	public function saveConfiguration()
	{
		if (!$this->isLoaded)
			$this->loadConfiguration();

		$path = static::getPath(self::CONFIGURATION_FILE_PATH);

		$data = ($this->storedData !== null) ? $this->storedData : $this->data;
		$data = var_export($data, true);

		if (!is_writable($path))
			@chmod($path, 0644);
		file_put_contents($path, "<"."?php\nreturn ".$data.";\n");
	}

	public function add($name, $value)
	{
		if (!$this->isLoaded)
			$this->loadConfiguration();

		if (!isset($this->data[$name]) || !$this->data[$name]["readonly"])
			$this->data[$name] = array("value" => $value, "readonly" => false);
		if (($this->storedData !== null) && (!isset($this->storedData[$name]) || !$this->storedData[$name]["readonly"]))
			$this->storedData[$name] = array("value" => $value, "readonly" => false);
	}

	/**
	 * Changes readonly params.
	 * Warning! Developer must use this method very carfully!.
	 * You must use this method only if you know what you do!
	 * @param string $name
	 * @param array $value
	 * @return void
	 */
	public function addReadonly($name, $value)
	{
		if (!$this->isLoaded)
			$this->loadConfiguration();

		$this->data[$name] = array("value" => $value, "readonly" => true);
		if ($this->storedData !== null)
			$this->storedData[$name] = array("value" => $value, "readonly" => true);
	}

	public function delete($name)
	{
		if (!$this->isLoaded)
			$this->loadConfiguration();

		if (isset($this->data[$name]) && !$this->data[$name]["readonly"])
			unset($this->data[$name]);
		if (($this->storedData !== null) && isset($this->storedData[$name]) && !$this->storedData[$name]["readonly"])
			unset($this->storedData[$name]);
	}

	public function get($name)
	{
		if (!$this->isLoaded)
			$this->loadConfiguration();

		if (isset($this->data[$name]))
			return $this->data[$name]["value"];

		return null;
	}

	public function offsetExists($name)
	{
		if (!$this->isLoaded)
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
		if (!$this->isLoaded)
			$this->loadConfiguration();

		$c = current($this->data);

		return $c === false ? false : $c["value"];
	}

	public function next()
	{
		if (!$this->isLoaded)
			$this->loadConfiguration();

		$c = next($this->data);

		return $c === false ? false : $c["value"];
	}

	public function key()
	{
		if (!$this->isLoaded)
			$this->loadConfiguration();

		return key($this->data);
	}

	public function valid()
	{
		if (!$this->isLoaded)
			$this->loadConfiguration();

		$key = $this->key();
		return isset($this->data[$key]);
	}

	public function rewind()
	{
		if (!$this->isLoaded)
			$this->loadConfiguration();

		return reset($this->data);
	}

	public function count()
	{
		if (!$this->isLoaded)
			$this->loadConfiguration();

		return count($this->data);
	}

	public static function wnc()
	{
		$configuration = Configuration::getInstance();
		$configuration->loadConfiguration();

		$ar = array(
			"utf_mode" => array("value" => defined('BX_UTF'), "readonly" => true),
			"default_charset" => array("value" => defined('BX_DEFAULT_CHARSET'), "readonly" => false),
			"no_accelerator_reset" => array("value" => defined('BX_NO_ACCELERATOR_RESET'), "readonly" => false),
			"http_status" => array("value" => (defined('BX_HTTP_STATUS') && BX_HTTP_STATUS) ? true : false, "readonly" => false),
		);

		$cache = array();
		if (defined('BX_CACHE_SID'))
			$cache["sid"] = BX_CACHE_SID;
		if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/cluster/memcache.php"))
		{
			$arList = null;
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/cluster/memcache.php");
			if (defined("BX_MEMCACHE_CLUSTER") && is_array($arList))
			{
				foreach ($arList as $listKey => $listVal)
				{
					$bOtherGroup = defined("BX_CLUSTER_GROUP") && ($listVal["GROUP_ID"] !== BX_CLUSTER_GROUP);

					if (($listVal["STATUS"] !== "ONLINE") || $bOtherGroup)
						unset($arList[$listKey]);
				}

				if (count($arList) > 0)
				{
					$cache["type"] = array(
						"extension" => "memcache",
						"required_file" => "modules/cluster/classes/general/memcache_cache.php",
						"class_name" => "CPHPCacheMemcacheCluster",
					);
				}
			}
		}
		if (!isset($cache["type"]))
		{
			if (defined('BX_CACHE_TYPE'))
			{
				$cache["type"] = BX_CACHE_TYPE;

				switch ($cache["type"])
				{
					case "memcache":
					case "CPHPCacheMemcache":
						$cache["type"] = "memcache";
						break;
					case "eaccelerator":
					case "CPHPCacheEAccelerator":
						$cache["type"] = "eaccelerator";
						break;
					case "apc":
					case "CPHPCacheAPC":
						$cache["type"] = "apc";
						break;
					case "xcache":
					case "CPHPCacheXCache":
						$cache["type"] = array(
							"extension" => "xcache",
							"required_file" => "modules/main/classes/general/cache_xcache.php",
							"class_name" => "CPHPCacheXCache",
						);
						break;
					default:
						if (defined("BX_CACHE_CLASS_FILE") && file_exists(BX_CACHE_CLASS_FILE))
						{
							$cache["type"] = array(
								"required_remote_file" => BX_CACHE_CLASS_FILE,
								"class_name" => BX_CACHE_TYPE
							);
						}
						else
						{
							$cache["type"] = "files";
						}
						break;
				}
			}
			else
			{
				$cache["type"] = "files";
			}
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

		$ar["cookies"] = array("value" => array("secure" => false, "http_only" => true), "readonly" => false);

		$ar["exception_handling"] = array(
			"value" => array(
				"debug" => true,
				"handled_errors_types" => E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE,
				"exception_errors_types" => E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_WARNING & ~E_COMPILE_WARNING,
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

		global $DBType, $DBHost, $DBName, $DBLogin, $DBPassword;

		$DBType = strtolower($DBType);
		if ($DBType == 'mysql')
			$dbClassName = "\\Bitrix\\Main\\DB\\MysqlConnection";
		elseif ($DBType == 'mssql')
			$dbClassName = "\\Bitrix\\Main\\DB\\MssqlConnection";
		else
			$dbClassName = "\\Bitrix\\Main\\DB\\OracleConnection";

		$ar['connections']['value']['default'] = array(
			'className' => $dbClassName,
			'host' => $DBHost,
			'database' => $DBName,
			'login' => $DBLogin,
			'password' => $DBPassword,
			'options' =>  ((!defined("DBPersistent") || DBPersistent) ? 1 : 0) | ((defined("DELAY_DB_CONNECT") && DELAY_DB_CONNECT === true) ? 2 : 0)
		);
		$ar['connections']['readonly'] = true;

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

		$filename1 = $_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/after_connect.php";
		$filename2 = $_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/after_connect_d7.php";
		if (file_exists($filename1) && !file_exists($filename2))
		{
			$source = file_get_contents($filename1);
			$source = trim($source);
			$pos = 2;
			if (strtolower(substr($source, 0, 5)) == '<?php')
				$pos = 5;
			$source = substr($source, 0, $pos)."\n".'$connection = \Bitrix\Main\Application::getConnection();'.substr($source, $pos);
			$source = preg_replace("#\\\$DB->Query\(#i", "\$connection->queryExecute(", $source);
			file_put_contents($filename2, $source);
		}
	}
}
