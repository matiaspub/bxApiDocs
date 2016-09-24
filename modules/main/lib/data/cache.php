<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Main\Data;

use Bitrix\Main;
use Bitrix\Main\Config;
use Bitrix\Main\Diag;

interface ICacheEngine
{
	public function isAvailable();
	static public function clean($baseDir, $initDir = false, $filename = false);
	static public function read(&$arAllVars, $baseDir, $initDir, $filename, $TTL);
	static public function write($arAllVars, $baseDir, $initDir, $filename, $TTL);
	static public function isCacheExpired($path);
}

interface ICacheEngineStat
{
	static public function getReadBytes();
	public function getWrittenBytes();
	static public function getCachePath();
}

class Cache
{
	/**
	 * @var ICacheEngine | ICacheBackend
	 */
	private $cacheEngine;

	private $content;
	private $vars;
	private $TTL;
	private $uniqueString;
	private $baseDir;
	private $initDir;
	private $filename;
	private $isStarted = false;

	private static $showCacheStat = false;
	private static $clearCache = null;
	private static $clearCacheSession = null;

	protected $forceRewriting = false;

	public static function createCacheEngine()
	{
		static $cacheEngine = null;
		if ($cacheEngine)
		{
			return clone $cacheEngine;
		}

		// Events can't be used here because events use cache

		$cacheType = "files";
		$v = Config\Configuration::getValue("cache");
		if ($v != null && isset($v["type"]) && !empty($v["type"]))
			$cacheType = $v["type"];

		if (is_array($cacheType))
		{
			if (isset($cacheType["class_name"]))
			{
				if (!isset($cacheType["extension"]) || extension_loaded($cacheType["extension"]))
				{
					if (isset($cacheType["required_file"]) && ($requiredFile = Main\Loader::getLocal($cacheType["required_file"])) !== false)
						require_once($requiredFile);
					if (isset($cacheType["required_remote_file"]))
						require_once($cacheType["required_remote_file"]);

					$className = $cacheType["class_name"];
					if (class_exists($className))
						$cacheEngine = new $className();
				}
			}
		}
		else
		{
			switch ($cacheType)
			{
				case "memcache":
					if (extension_loaded('memcache'))
						$cacheEngine = new CacheEngineMemcache();
					break;
				case "apc":
					if (extension_loaded('apc'))
						$cacheEngine = new CacheEngineApc();
					break;
				case "xcache":
					if (extension_loaded('xcache'))
						$cacheEngine = new CacheEngineXCache();
					break;
				case "files":
					$cacheEngine = new CacheEngineFiles();
					break;
				case "none":
					$cacheEngine = new CacheEngineNone();
					break;
				default:
					break;
			}
		}

		if ($cacheEngine == null)
		{
			$cacheEngine = new CacheEngineNone();
			trigger_error("Cache engine is not found", E_USER_WARNING);
		}

		if (!$cacheEngine->isAvailable())
		{
			$cacheEngine = new CacheEngineNone();
			trigger_error("Cache engine is not available", E_USER_WARNING);
		}

		return clone $cacheEngine;
	}

	public static function getCacheEngineType()
	{
		$obj = self::createCacheEngine();
		$class = get_class($obj);
		if (($pos = strrpos($class, "\\")) !== false)
			$class = substr($class, $pos + 1);
		return strtolower($class);
	}

	/**
	 * @return Cache
	 */
	public static function createInstance()
	{
		$cacheEngine = static::createCacheEngine();
		return new static($cacheEngine);
	}

	public function __construct($cacheEngine)
	{
		$this->cacheEngine = $cacheEngine;
	}

	public static function setShowCacheStat($showCacheStat)
	{
		static::$showCacheStat = $showCacheStat;
	}

	public static function getShowCacheStat()
	{
		return static::$showCacheStat;
	}

	/**
	 * A privileged user wants to skip cache on this hit.
	 * @param bool $clearCache
	 */
	public static function setClearCache($clearCache)
	{
		static::$clearCache = $clearCache;
	}

	/**
	 * A privileged user wants to skip cache on this session.
	 * @param bool $clearCacheSession
	 */
	public static function setClearCacheSession($clearCacheSession)
	{
		static::$clearCacheSession = $clearCacheSession;
	}

	public static function getSalt()
	{
		$context = Main\Application::getInstance()->getContext();
		$server = $context->getServer();

		$scriptName = $server->get("SCRIPT_NAME");
		if ($scriptName == "/bitrix/urlrewrite.php" && (($v = $server->get("REAL_FILE_PATH")) != null))
		{
			$scriptName = $v;
		}
		elseif ($scriptName == "/404.php" && (($v = $server->get("REAL_FILE_PATH")) != null))
		{
			$scriptName = $v;
		}
		return "/".substr(md5($scriptName), 0, 3);
	}

	/**
	 * Returns true if a privileged user wants to skip reading from cache (on this hit or session).
	 * @return bool
	 */
	public static function shouldClearCache()
	{
		global $USER;

		if (isset(static::$clearCacheSession) || isset(static::$clearCache))
		{
			if (is_object($USER) && $USER->CanDoOperation('cache_control'))
			{
				if (isset(static::$clearCacheSession))
				{
					if (static::$clearCacheSession === true)
						$_SESSION["SESS_CLEAR_CACHE"] = "Y";
					else
						unset($_SESSION["SESS_CLEAR_CACHE"]);
				}

				if (isset(static::$clearCache) && (static::$clearCache === true))
				{
					return true;
				}
			}
		}

		if (isset($_SESSION["SESS_CLEAR_CACHE"]) && $_SESSION["SESS_CLEAR_CACHE"] === "Y")
		{
			return true;
		}

		return false;
	}

	public static function getPath($uniqueString)
	{
		$un = md5($uniqueString);
		return substr($un, 0, 2)."/".$un.".php";
	}

	public function clean($uniqueString, $initDir = false, $baseDir = "cache")
	{
		$personalRoot = Main\Application::getPersonalRoot();
		$baseDir = $personalRoot."/".$baseDir."/";
		$filename = $this->getPath($uniqueString);

		if (static::$showCacheStat)
			Diag\CacheTracker::add(0, "", $baseDir, $initDir, "/".$filename, "C");

		return $this->cacheEngine->clean($baseDir, $initDir, "/".$filename);
	}

	public function cleanDir($initDir = false, $baseDir = "cache")
	{
		$personalRoot = Main\Application::getPersonalRoot();
		$baseDir = $personalRoot."/".$baseDir."/";

		if (static::$showCacheStat)
			Diag\CacheTracker::add(0, "", $baseDir, $initDir, "", "C");

		return $this->cacheEngine->clean($baseDir, $initDir);
	}

	public function initCache($TTL, $uniqueString, $initDir = false, $baseDir = "cache")
	{
		if ($initDir === false)
		{
			$request = Main\Context::getCurrent()->getRequest();
			$initDir = $request->getRequestedPageDirectory();
		}

		$personalRoot = Main\Application::getPersonalRoot();
		$this->baseDir = $personalRoot."/".$baseDir."/";
		$this->initDir = $initDir;
		$this->filename = "/".$this->getPath($uniqueString);
		$this->TTL = $TTL;
		$this->uniqueString = $uniqueString;
		$this->vars = false;

		if ($TTL <= 0)
			return false;

		if ($this->forceRewriting)
			return false;

		if (static::shouldClearCache())
			return false;

		$arAllVars = array("CONTENT" => "", "VARS" => "");
		if (!$this->cacheEngine->read($arAllVars, $this->baseDir, $this->initDir, $this->filename, $this->TTL))
			return false;

		if (static::$showCacheStat)
		{
			$read = 0;
			$path = '';
			if ($this->cacheEngine instanceof ICacheEngineStat)
			{
				$read = $this->cacheEngine->getReadBytes();
				$path = $this->cacheEngine->getCachePath();
			}
			elseif ($this->cacheEngine instanceof \ICacheBackend)
			{
				/** @noinspection PhpUndefinedFieldInspection */
				$read = $this->cacheEngine->read;

				/** @noinspection PhpUndefinedFieldInspection */
				$path = $this->cacheEngine->path;
			}
			Diag\CacheTracker::addCacheStatBytes($read);
			Diag\CacheTracker::add($read, $path, $this->baseDir, $this->initDir, $this->filename, "R");
		}

		$this->content = $arAllVars["CONTENT"];
		$this->vars = $arAllVars["VARS"];

		return true;
	}

	public function output()
	{
		echo $this->content;
	}

	public function getVars()
	{
		return $this->vars;
	}

	public function startDataCache($TTL = false, $uniqueString = false, $initDir = false, $vars = array(), $baseDir = "cache")
	{
		$narg = func_num_args();
		if($narg<=0)
			$TTL = $this->TTL;
		if($narg<=1)
			$uniqueString = $this->uniqueString;
		if($narg<=2)
			$initDir = $this->initDir;
		if($narg<=3)
			$vars = $this->vars;

		if ($this->initCache($TTL, $uniqueString, $initDir, $baseDir))
		{
			$this->output();
			return false;
		}

		if ($TTL <= 0)
			return true;

		ob_start();
		$this->vars = $vars;
		$this->isStarted = true;

		return true;
	}

	public function abortDataCache()
	{
		if (!$this->isStarted)
			return;

		$this->isStarted = false;
		ob_end_flush();
	}

	public function endDataCache($vars=false)
	{
		if (!$this->isStarted)
			return;

		$this->isStarted = false;

		$arAllVars = array(
			"CONTENT" => ob_get_contents(),
			"VARS" => ($vars!==false ? $vars : $this->vars),
		);

		$this->cacheEngine->write($arAllVars, $this->baseDir, $this->initDir, $this->filename, $this->TTL);

		if (static::$showCacheStat)
		{
			$written = 0;
			$path = '';
			if ($this->cacheEngine instanceof ICacheEngineStat)
			{
				$written = $this->cacheEngine->getWrittenBytes();
				$path = $this->cacheEngine->getCachePath();
			}
			elseif ($this->cacheEngine instanceof \ICacheBackend)
			{
				/** @noinspection PhpUndefinedFieldInspection */
				$written = $this->cacheEngine->written;

				/** @noinspection PhpUndefinedFieldInspection */
				$path = $this->cacheEngine->path;
			}
			Diag\CacheTracker::addCacheStatBytes($written);
			Diag\CacheTracker::add($written, $path, $this->baseDir, $this->initDir, $this->filename, "W");
		}

		if (strlen(ob_get_contents()) > 0)
			ob_end_flush();
		else
			ob_end_clean();
	}

	public function isCacheExpired($path)
	{
		return $this->cacheEngine->isCacheExpired($path);
	}

	public function isStarted()
	{
		return $this->isStarted;
	}

	public static function clearCache($full = false, $initDir = "")
	{
		if (($full !== true) && ($full !== false) && ($initDir === "") && is_string($full))
		{
			$initDir = $full;
			$full = true;
		}

		$res = true;

		if ($full === true)
		{
			$obCache = static::createInstance();
			$obCache->cleanDir($initDir, "cache");
		}

		$path = Main\Loader::getPersonal("cache".$initDir);
		if (is_dir($path) && ($handle = opendir($path)))
		{
			while (($file = readdir($handle)) !== false)
			{
				if ($file === "." || $file === "..")
					continue;

				if (is_dir($path."/".$file))
				{
					if (!static::clearCache($full, $initDir."/".$file))
					{
						$res = false;
					}
					else
					{
						@chmod($path."/".$file, BX_DIR_PERMISSIONS);
						//We suppress error handle here because there may be valid cache files in this dir
						@rmdir($path."/".$file);
					}
				}
				elseif ($full)
				{
					@chmod($path."/".$file, BX_FILE_PERMISSIONS);
					if (!unlink($path."/".$file))
						$res = false;
				}
				elseif (substr($file, -4) === ".php")
				{
					$c = static::createInstance();
					if ($c->isCacheExpired($path."/".$file))
					{
						@chmod($path."/".$file, BX_FILE_PERMISSIONS);
						if (!unlink($path."/".$file))
							$res = false;
					}
				}
				else
				{
					//We should skip unknown file
					//it will be deleted with full cache cleanup
				}
			}
			closedir($handle);
		}

		return $res;
	}

	/**
	 * Sets the forced mode to ignore TTL and rewrite the cache.
	 * @param bool $mode
	 */
	public function forceRewriting($mode)
	{
		$this->forceRewriting = (bool)$mode;
	}
}
