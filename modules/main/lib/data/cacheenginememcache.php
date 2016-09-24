<?php
namespace Bitrix\Main\Data;

use Bitrix\Main\Config;

class CacheEngineMemcache
	implements ICacheEngine, ICacheEngineStat
{
	private static $obMemcache = null;
	private static $isConnected = false;

	private static $baseDirVersion = array();
	private $sid = "BX";
	//cache stats
	private $written = false;
	private $read = false;
	// unfortunately is not available for memcache...

	protected $useLock = true;
	protected $ttlMultiplier = 2;
	protected static $locks = array();

	/**
	 * Engine constructor.
	 *
	 */
	public function __construct()
	{
		$cacheConfig = Config\Configuration::getValue("cache");

		if (self::$obMemcache == null)
		{
			self::$obMemcache = new \Memcache;

			$v = (isset($cacheConfig["memcache"]))? $cacheConfig["memcache"]: null;

			if ($v != null && isset($v["host"]) && $v["host"] != "")
			{
				if ($v != null && isset($v["port"]))
					$port = intval($v["port"]);
				else
					$port = 11211;

				if (self::$obMemcache->pconnect($v["host"], $port))
				{
					self::$isConnected = true;
				}
			}
		}

		if ($cacheConfig && is_array($cacheConfig))
		{
			if (isset($cacheConfig["use_lock"]))
			{
				$this->useLock = (bool)$cacheConfig["use_lock"];
			}

			if (isset($cacheConfig["sid"]) && ($cacheConfig["sid"] != ""))
			{
				$this->sid = $cacheConfig["sid"];
			}

			if (isset($cacheConfig["ttl_multiplier"]) && $this->useLock)
			{
				$this->ttlMultiplier = (integer)$cacheConfig["ttl_multiplier"];
			}
		}

		$this->sid .= ($this->useLock? 2: 3);

		if (!$this->useLock)
		{
			$this->ttlMultiplier = 1;
		}
	}

	/**
	 * Closes opened connection.
	 *
	 * @return void
	 */
	public static function close()
	{
		if (self::$obMemcache != null)
		{
			self::$obMemcache->close();
			self::$obMemcache = null;
		}
	}

	/**
	 * Returns number of bytes read from memcache or false if there was no read operation.
	 * Stub function always returns false.
	 *
	 * @return integer|false
	 */
	public function getReadBytes()
	{
		return $this->read;
	}

	/**
	 * Returns number of bytes written to memcache or false if there was no write operation.
	 * Stub function always returns false.
	 *
	 * @return integer|false
	 */
	public function getWrittenBytes()
	{
		return $this->written;
	}

	/**
	 * Returns physical file path after read or write operation.
	 * Stub function always returns '' (empty string).
	 *
	 * @return string
	 */
	static public function getCachePath()
	{
		return "";
	}

	/**
	 * Returns true if cache can be read or written.
	 *
	 * @return bool
	 */
	public static function isAvailable()
	{
		return self::$isConnected;
	}

	/**
	 * Tries to put non blocking exclusive lock on the cache entry.
	 * Returns true on success.
	 *
	 * @param string $baseDir Base cache directory (usually /bitrix/cache).
	 * @param string $initDir Directory within base.
	 * @param string $key Calculated cache key.
	 * @param integer $TTL Expiration period in seconds.
	 *
	 * @return boolean
	 */
	protected function lock($baseDir, $initDir, $key, $TTL)
	{
		if (
			isset(self::$locks[$baseDir])
			&& isset(self::$locks[$baseDir][$initDir])
			&& isset(self::$locks[$baseDir][$initDir][$key])
		)
		{
			return true;
		}
		elseif (self::$obMemcache->add($key, 1, 0, intval($TTL)))
		{
			self::$locks[$baseDir][$initDir][$key] = true;
			return true;
		}

		return false;
	}

	/**
	 * Releases the lock obtained by lock method.
	 *
	 * @param string $baseDir Base cache directory (usually /bitrix/cache).
	 * @param string $initDir Directory within base.
	 * @param string $key Calculated cache key.
	 * @param integer $TTL Expiration period in seconds.
	 *
	 * @return void
	 */
	protected function unlock($baseDir, $initDir = false, $key = false, $TTL = 0)
	{
		if ($key !== false)
		{
			if ($TTL > 0)
			{
				self::$obMemcache->set($key."~", 1, 0, time() + intval($TTL));
			}
			else
			{
				self::$obMemcache->replace($key."~", "", 0, 1);
			}

			unset(self::$locks[$baseDir][$initDir][$key]);
		}
		elseif ($initDir !== false)
		{
			if (isset(self::$locks[$baseDir][$initDir]))
			{
				foreach (self::$locks[$baseDir][$initDir] as $subKey)
				{
					$this->unlock($baseDir, $initDir, $subKey, $TTL);
				}
				unset(self::$locks[$baseDir][$initDir]);
			}
		}
		elseif ($baseDir !== false)
		{
			if (isset(self::$locks[$baseDir]))
			{
				foreach (self::$locks[$baseDir] as $subInitDir)
				{
					$this->unlock($baseDir, $subInitDir, false, $TTL);
				}
			}
		}
	}

	/**
	 * Cleans (removes) cache directory or file.
	 *
	 * @param string $baseDir Base cache directory (usually /bitrix/cache).
	 * @param string $initDir Directory within base.
	 * @param string $filename File name.
	 *
	 * @return void
	 */
	public function clean($baseDir, $initDir = false, $filename = false)
	{
		$key = false;
		if (is_object(self::$obMemcache))
		{
			if (strlen($filename))
			{
				if (!isset(self::$baseDirVersion[$baseDir]))
					self::$baseDirVersion[$baseDir] = self::$obMemcache->get($this->sid.$baseDir);

				if (self::$baseDirVersion[$baseDir] === false || self::$baseDirVersion[$baseDir] === '')
					return;

				if ($initDir !== false)
				{
					$initDirVersion = self::$obMemcache->get(self::$baseDirVersion[$baseDir]."|".$initDir);
					if ($initDirVersion === false || $initDirVersion === '')
						return;
				}
				else
				{
					$initDirVersion = "";
				}

				$key = self::$baseDirVersion[$baseDir]."|".$initDirVersion."|".$filename;
				self::$obMemcache->replace($key, "", 0, 1);
			}
			else
			{
				if (strlen($initDir))
				{
					if (!isset(self::$baseDirVersion[$baseDir]))
						self::$baseDirVersion[$baseDir] = self::$obMemcache->get($this->sid.$baseDir);

					if (self::$baseDirVersion[$baseDir] === false || self::$baseDirVersion[$baseDir] === '')
						return;

					self::$obMemcache->replace(self::$baseDirVersion[$baseDir]."|".$initDir, "", 0, 1);
				}
				else
				{
					if (isset(self::$baseDirVersion[$baseDir]))
						unset(self::$baseDirVersion[$baseDir]);

					self::$obMemcache->replace($this->sid.$baseDir, "", 0, 1);
				}
			}
		}
		$this->unlock($baseDir, $initDir, $key."~");
	}

	/**
	 * Reads cache from the memcache. Returns true if key value exists, not expired, and successfully read.
	 *
	 * @param mixed &$arAllVars Cached result.
	 * @param string $baseDir Base cache directory (usually /bitrix/cache).
	 * @param string $initDir Directory within base.
	 * @param string $filename File name.
	 * @param integer $TTL Expiration period in seconds.
	 *
	 * @return boolean
	 */
	public function read(&$arAllVars, $baseDir, $initDir, $filename, $TTL)
	{
		if (!isset(self::$baseDirVersion[$baseDir]))
			self::$baseDirVersion[$baseDir] = self::$obMemcache->get($this->sid.$baseDir);

		if (self::$baseDirVersion[$baseDir] === false || self::$baseDirVersion[$baseDir] === '')
			return false;

		if ($initDir !== false)
		{
			$initDirVersion = self::$obMemcache->get(self::$baseDirVersion[$baseDir]."|".$initDir);
			if ($initDirVersion === false || $initDirVersion === '')
				return false;
		}
		else
		{
			$initDirVersion = "";
		}

		$key = self::$baseDirVersion[$baseDir]."|".$initDirVersion."|".$filename;

		if ($this->useLock)
		{
			$cachedData = self::$obMemcache->get($key);
			if (!is_array($cachedData))
			{
				return false;
			}

			if ($cachedData["datecreate"] < (time() - $TTL)) //has expired
			{
				if ($this->lock($baseDir, $initDir, $key."~", $TTL))
				{
					return false;
				}
			}

			$arAllVars = $cachedData["content"];
		}
		else
		{
			$arAllVars = self::$obMemcache->get($key);
		}

		if ($arAllVars === false || $arAllVars === '')
		{
			return false;
		}

		return true;
	}

	/**
	 * Puts cache into the memcache.
	 *
	 * @param mixed $arAllVars Cached result.
	 * @param string $baseDir Base cache directory (usually /bitrix/cache).
	 * @param string $initDir Directory within base.
	 * @param string $filename File name.
	 * @param integer $TTL Expiration period in seconds.
	 *
	 * @return void
	 */
	public function write($arAllVars, $baseDir, $initDir, $filename, $TTL)
	{
		if (!isset(self::$baseDirVersion[$baseDir]))
			self::$baseDirVersion[$baseDir] = self::$obMemcache->get($this->sid.$baseDir);

		if (self::$baseDirVersion[$baseDir] === false || self::$baseDirVersion[$baseDir] === '')
		{
			self::$baseDirVersion[$baseDir] = $this->sid.md5(mt_rand());
			self::$obMemcache->set($this->sid.$baseDir, self::$baseDirVersion[$baseDir]);
		}

		if ($initDir !== false)
		{
			$initDirVersion = self::$obMemcache->get(self::$baseDirVersion[$baseDir]."|".$initDir);
			if ($initDirVersion === false || $initDirVersion === '')
			{
				$initDirVersion = $this->sid.md5(mt_rand());
				self::$obMemcache->set(self::$baseDirVersion[$baseDir]."|".$initDir, $initDirVersion);
			}
		}
		else
		{
			$initDirVersion = "";
		}

		$key = self::$baseDirVersion[$baseDir]."|".$initDirVersion."|".$filename;
		$time = time();
		$exp = $this->ttlMultiplier > 0? $time + intval($TTL) * $this->ttlMultiplier: 0;

		if ($this->useLock)
		{
			self::$obMemcache->set($key, array("datecreate" => $time, "content" => $arAllVars), 0, $exp);
			$this->unlock($baseDir, $initDir, $key."~", $TTL);
		}
		else
		{
			self::$obMemcache->set($key, $arAllVars, 0, $exp);
		}
	}

	/**
	 * Returns true if cache has been expired.
	 * Stub function always returns true.
	 *
	 * @param string $path Absolute physical path.
	 *
	 * @return boolean
	 */
	public static function isCacheExpired($path)
	{
		return false;
	}
}
