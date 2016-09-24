<?php
namespace Bitrix\Main\Data;

class CacheEngineApc
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
	 * Returns number of bytes read from apc or false if there was no read operation.
	 *
	 * @return integer|false
	 */
	public function getReadBytes()
	{
		return $this->read;
	}

	/**
	 * Returns number of bytes written to apc or false if there was no write operation.
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
		return function_exists('apc_fetch');
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
		elseif (apc_fetch($key)) //another process has the lock
		{
			return false;
		}
		else
		{
			$lock = apc_add($key, 1, intval($TTL));
			if ($lock) //we are lucky to be the first
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
				apc_store($key, 1, intval($TTL));
			}
			else
			{
				apc_delete($key);
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
			$baseDirVersion = apc_fetch($this->sid.$baseDir);
			if ($baseDirVersion === false)
				return;

			if ($initDir !== false)
			{
				$initDirVersion = apc_fetch($baseDirVersion."|".$initDir);
				if ($initDirVersion === false)
					return;
			}
			else
			{
				$initDirVersion = "";
			}

			$key = $baseDirVersion."|".$initDirVersion."|".$filename;
			apc_delete($key);
		}
		else
		{
			if (strlen($initDir))
			{
				$baseDirVersion = apc_fetch($this->sid.$baseDir);
				if ($baseDirVersion === false)
					return;

				apc_delete($baseDirVersion."|".$initDir);
			}
			else
			{
				apc_delete($this->sid.$baseDir);
			}
		}
		$this->unlock($baseDir, $initDir, $key."~");
	}

	/**
	 * Reads cache from the apc. Returns true if key value exists, not expired, and successfully read.
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
		$baseDirVersion = apc_fetch($this->sid.$baseDir);
		if ($baseDirVersion === false)
			return false;

		if ($initDir !== false)
		{
			$initDirVersion = apc_fetch($baseDirVersion."|".$initDir);
			if ($initDirVersion === false)
				return false;
		}
		else
		{
			$initDirVersion = "";
		}

		$key = $baseDirVersion."|".$initDirVersion."|".$filename;
		$arAllVars = apc_fetch($key);

		if ($arAllVars === false)
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
	 * Puts cache into the apc.
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
		$baseDirVersion = apc_fetch($this->sid.$baseDir);
		if ($baseDirVersion === false)
		{
			$baseDirVersion = md5(mt_rand());
			if (!apc_store($this->sid.$baseDir, $baseDirVersion))
				return;
		}

		if ($initDir !== false)
		{
			$initDirVersion = apc_fetch($baseDirVersion."|".$initDir);
			if ($initDirVersion === false)
			{
				$initDirVersion = md5(mt_rand());
				if (!apc_store($baseDirVersion."|".$initDir, $initDirVersion))
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
		apc_store($key, $arAllVars, intval($TTL) * $this->ttlMultiplier);

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
