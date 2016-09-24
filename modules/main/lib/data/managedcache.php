<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Main\Data;

use Bitrix\Main;

class ManagedCache
{
	/**
	 * @var Cache[]
	 */
	protected $cache = array();
	protected $cachePath = array();
	protected $vars = array();
	protected $ttl = array();

	static public function __construct()
	{
	}

	protected static function getDbType()
	{
		static $type = null;
		if ($type === null)
		{
			$cm = Main\Application::getInstance()->getConnectionPool();
			$type = $cm->getDefaultConnectionType();
		}
		return $type;
	}

	// Tries to read cached variable value from the file
	// Returns true on success
	// otherwise returns false
	public function read($ttl, $uniqueId, $tableId = false)
	{
		if (isset($this->cache[$uniqueId]))
		{
			return true;
		}
		else
		{
			$this->cache[$uniqueId] = Cache::createInstance();
			$this->cachePath[$uniqueId] = static::getDbType().($tableId === false ? "" : "/".$tableId);
			$this->ttl[$uniqueId] = $ttl;
			return $this->cache[$uniqueId]->initCache($ttl, $uniqueId, $this->cachePath[$uniqueId], "managed_cache");
		}
	}

	static public function getImmediate($ttl, $uniqueId, $tableId = false)
	{
		$cache = Cache::createInstance();
		$cachePath = static::getDbType().($tableId === false ? "" : "/".$tableId);

		if($cache->initCache($ttl, $uniqueId, $cachePath, "managed_cache"))
			return $cache->getVars();
		return false;
	}

	// This method is used to read the variable value
	// from the cache after successfull Read
	public function get($uniqueId)
	{
		if (array_key_exists($uniqueId, $this->vars))
			return $this->vars[$uniqueId];
		elseif (isset($this->cache[$uniqueId]))
			return $this->cache[$uniqueId]->getVars();
		else
			return false;
	}

	// Sets new value to the variable
	public function set($uniqueId, $val)
	{
		if(isset($this->cache[$uniqueId]))
		{
			$this->vars[$uniqueId] = $val;
		}
	}

	public function setImmediate($uniqueId, $val)
	{
		if(isset($this->cache[$uniqueId]))
		{
			$obCache = Cache::createInstance();
			$obCache->startDataCache($this->ttl[$uniqueId], $uniqueId, $this->cachePath[$uniqueId], $val, "managed_cache");
			$obCache->endDataCache();

			unset($this->cache[$uniqueId]);
			unset($this->cachePath[$uniqueId]);
			unset($this->vars[$uniqueId]);
		}
	}

	// Marks cache entry as invalid
	public function clean($uniqueId, $tableId = false)
	{
		$obCache = Cache::createInstance();
		$obCache->clean(
			$uniqueId,
			static::getDbType().($tableId === false ? "" : "/".$tableId),
			"managed_cache"
		);
		if(isset($this->cache[$uniqueId]))
		{
			unset($this->cache[$uniqueId]);
			unset($this->cachePath[$uniqueId]);
			unset($this->vars[$uniqueId]);
		}
	}

	// Marks cache entries associated with the table as invalid
	public function cleanDir($tableId)
	{
		$dbType = static::getDbType();
		$strPath = $dbType."/".$tableId;
		foreach ($this->cachePath as $uniqueId => $Path)
		{
			if ($Path == $strPath)
			{
				unset($this->cache[$uniqueId]);
				unset($this->cachePath[$uniqueId]);
				unset($this->vars[$uniqueId]);
			}
		}
		$obCache = Cache::createInstance();
		$obCache->cleanDir($dbType."/".$tableId, "managed_cache");
	}

	// Clears all managed_cache
	public function cleanAll()
	{
		$this->cache = array();
		$this->cachePath = array();
		$this->vars = array();
		$this->ttl = array();

		$obCache = Cache::createInstance();
		$obCache->cleanDir(false, "managed_cache");

		$taggedCache = Main\Application::getInstance()->getTaggedCache();
		$taggedCache->clearByTag(true);
	}

	// Use it to flush cache to the files.
	// Causion: only at the end of all operations!
	public static function finalize()
	{
		$cacheManager = Main\Application::getInstance()->getManagedCache();
		$cache = Cache::createInstance();
		foreach ($cacheManager->cache as $uniqueId => $val)
		{
			if (array_key_exists($uniqueId, $cacheManager->vars))
			{
				$cache->startDataCache($cacheManager->ttl[$uniqueId], $uniqueId, $cacheManager->cachePath[$uniqueId], $cacheManager->vars[$uniqueId], "managed_cache");
				$cache->endDataCache();
			}
		}
	}

	static public function getCompCachePath($relativePath)
	{
		// TODO: global var!
		global $BX_STATE;

		if ($BX_STATE === "WA")
		{
			$salt = Cache::getSalt();
		}
		else
		{
			$salt = "/".substr(md5($BX_STATE), 0, 3);
		}

		$path = "/".SITE_ID.$relativePath.$salt;
		return $path;
	}
}
