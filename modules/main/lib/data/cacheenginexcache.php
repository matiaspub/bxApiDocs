<?php
namespace Bitrix\Main\Data;


class CacheEngineXCache
	implements ICacheEngine, ICacheEngineStat
{
	private $sid = "BX";
	//cache stats
	private $written = false;
	private $read = false;

	protected $useLock = true;
	protected $ttlMultiplier = 2;
	protected static $locks = array();

	/**
	 * Engine constructor.
	 *
	 */
	public function __construct()
	{
		$cacheConfig = \Bitrix\Main\Config\Configuration::getValue("cache");

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

		$this->sid .= !$this->useLock;

		if (!$this->useLock)
		{
			$this->ttlMultiplier = 1;
		}
	}

	/**
	 * Returns number of bytes read from xcache or false if there was no read operation.
	 *
	 * @return integer|false
	 */
	public function getReadBytes()
	{
		return $this->read;
	}

	/**
	 * Returns number of bytes written to xcache or false if there was no write operation.
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
	static public function isAvailable()
	{
		return function_exists('xcache_get');
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
		elseif (xcache_get($key)) //another process has the lock
		{
			return false;
		}
		else
		{
			$lock = xcache_inc($key, 1, intval($TTL));
			if ($lock === 1) //we are lucky to be the first
			{
				self::$locks[$baseDir][$initDir][$key] = true;
				return true;
			}
			//xcache_dec have to be never called due to concurrency with xcache_set($key."~", 1, intval($TTL));
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
				xcache_set($key, 1, intval($TTL));
			}
			else
			{
				xcache_unset($key);
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
		if (strlen($filename))
		{
			$baseDirVersion = xcache_get($this->sid.$baseDir);
			if ($baseDirVersion === null)
				return;

			if ($initDir !== false)
			{
				$initDirVersion = xcache_get($baseDirVersion."|".$initDir);
				if ($initDirVersion === null)
					return;
			}
			else
			{
				$initDirVersion = "";
			}

			$key = $baseDirVersion."|".$initDirVersion."|".$filename;
			xcache_unset($key);
		}
		else
		{
			if (strlen($initDir))
			{
				$baseDirVersion = xcache_get($this->sid.$baseDir);
				if ($baseDirVersion === null)
					return;

				xcache_unset($baseDirVersion."|".$initDir);
			}
			else
			{
				xcache_unset($this->sid.$baseDir);
			}
		}
		$this->unlock($baseDir, $initDir, $key."~");
	}

	/**
	 * Reads cache from the xcache. Returns true if key value exists, not expired, and successfully read.
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
		$baseDirVersion = xcache_get($this->sid.$baseDir);
		if ($baseDirVersion === null)
			return false;

		if ($initDir !== false)
		{
			$initDirVersion = xcache_get($baseDirVersion."|".$initDir);
			if ($initDirVersion === null)
				return false;
		}
		else
		{
			$initDirVersion = "";
		}

		$key = $baseDirVersion."|".$initDirVersion."|".$filename;
		$arAllVars = xcache_get($key);

		if ($arAllVars === null)
		{
			return false;
		}
		else
		{
			if ($this->useLock)
			{
				if ($this->lock($baseDir, $initDir, $key."~", $TTL))
				{
					return false;
				}
			}

			$this->read = strlen($arAllVars);
			$arAllVars = unserialize($arAllVars);
		}

		return true;
	}

	/**
	 * Puts cache into the xcache.
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
		$baseDirVersion = xcache_get($this->sid.$baseDir);
		if ($baseDirVersion === null)
		{
			$baseDirVersion = md5(mt_rand());
			if (!xcache_set($this->sid.$baseDir, $baseDirVersion))
				return;
		}

		if ($initDir !== false)
		{
			$initDirVersion = xcache_get($baseDirVersion."|".$initDir);
			if ($initDirVersion === null)
			{
				$initDirVersion = md5(mt_rand());
				if (!xcache_set($baseDirVersion."|".$initDir, $initDirVersion))
					return;
			}
		}
		else
		{
			$initDirVersion = "";
		}

		$arAllVars = serialize($arAllVars);
		$this->written = strlen($arAllVars);

		$key = $baseDirVersion."|".$initDirVersion."|".$filename;
		xcache_set($key, $arAllVars, intval($TTL) * $this->ttlMultiplier);

		if ($this->useLock)
		{
			$this->unlock($baseDir, $initDir, $key."~", $TTL);
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
	static public function isCacheExpired($path)
	{
		return false;
	}
} 