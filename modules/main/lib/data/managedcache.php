<?php
namespace Bitrix\Main\Data;

use Bitrix\Main;

class ManagedCache
{
	/**
	 * @var Cache[]
	 */
	private $cache = array();
	private $cachePath = array();
	private $vars = array();
	private $ttl = array();

	/*Components managed(tagged) cache*/

	private $compCacheStack = array();
	private $salt = false;
	private $dbCacheTags = false;
	private $wasTagged = false;
	private $isMySql = false;

	public function __construct()
	{
		$this->isMySql = (static::getDbType() === "MYSQL");
	}

	private static function getDbType()
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

		if(defined("BX_COMP_MANAGED_CACHE"))
			$this->clearByTag(true);
	}

	// Use it to flush cache to the files.
	// Causion: only at the end of all operations!
	public static function finalize()
	{
		$cacheManager = Main\Application::getInstance()->getManagedCache();
		$obCache = Cache::createInstance();
		foreach ($cacheManager->cache as $uniqueId => $val)
		{
			if (array_key_exists($uniqueId, $cacheManager->vars))
			{
				$obCache->startDataCache($cacheManager->ttl[$uniqueId], $uniqueId, $cacheManager->cachePath[$uniqueId], $cacheManager->vars[$uniqueId], "managed_cache");
				$obCache->endDataCache();
			}
		}
	}

	private function initDbCache($path)
	{
		if (!isset($this->dbCacheTags[$path]))
		{
			$this->dbCacheTags[$path] = array();

			$con = Main\Application::getConnection();
			$sqlHelper = $con->getSqlHelper();

			$rs = $con->query("
				SELECT TAG
				FROM b_cache_tag
				WHERE SITE_ID = '".$sqlHelper->forSql(SITE_ID, 2)."'
				AND CACHE_SALT = '".$sqlHelper->forSql($this->salt, 4)."'
				AND RELATIVE_PATH = '".$sqlHelper->forSql($path, 4)."'
			");
			while ($ar = $rs->fetch())
			{
				$this->dbCacheTags[$path][$ar["TAG"]] = true;
			}
		}
	}

	private function initCompSalt()
	{
		if ($this->salt === false)
		{
			$context = Main\Application::getInstance()->getContext();
			$server = $context->getServer();
			$scriptName = $server->get("SCRIPT_NAME");
			if ($scriptName == "/bitrix/urlrewrite.php" && (($v = $server->get("REAL_FILE_PATH")) != null))
				$scriptName = $v;
			elseif ($scriptName == "/404.php" && (($v = $server->get("REAL_FILE_PATH")) != null))
				$scriptName = $v;

			$this->salt = "/".substr(md5($scriptName), 0, 3);
		}
	}

	public function getCompCachePath($relativePath)
	{
		// TODO: global var!
		global $BX_STATE;
		$this->initCompSalt();

		if ($BX_STATE === "WA")
			$salt = $this->salt;
		else
			$salt = "/".substr(md5($BX_STATE), 0, 3);

		$path = "/".SITE_ID.$relativePath.$salt;
		return $path;
	}

	public function startTagCache($relativePath)
	{
		array_unshift($this->compCacheStack, array($relativePath, array()));
	}

	public function endTagCache()
	{
		$this->initCompSalt();

		if ($this->wasTagged)
		{
			$con = Main\Application::getConnection();
			$sqlHelper = $con->getSqlHelper();

			// TODO: SITE_ID
			$siteIdForSql = $sqlHelper->forSql(SITE_ID, 2);
			$cacheSaltForSql = $this->salt;

			$strSqlPrefix = "
				INSERT ".($this->isMySql ? "IGNORE": "")." INTO b_cache_tag (SITE_ID, CACHE_SALT, RELATIVE_PATH, TAG)
				VALUES
			";
			$maxValuesLen = $this->isMySql ? 2048: 0;
			$strSqlValues = "";

			foreach ($this->compCacheStack as $arCompCache)
			{
				$path = $arCompCache[0];
				if (strlen($path))
				{
					$this->initDbCache($path);
					$sqlRELATIVE_PATH = $sqlHelper->forSql($path, 255);

					$sql = ",\n('".$siteIdForSql."', '".$cacheSaltForSql."', '".$sqlRELATIVE_PATH."',";

					foreach ($arCompCache[1] as $tag => $t)
					{
						if (!isset($this->dbCacheTags[$path][$tag]))
						{
							$strSqlValues .= $sql." '".$sqlHelper->forSql($tag, 50)."')";
							if (strlen($strSqlValues) > $maxValuesLen)
							{
								$con->queryExecute($strSqlPrefix.substr($strSqlValues, 2));
								$strSqlValues = "";
							}
							$this->dbCacheTags[$path][$tag] = true;
						}
					}
				}
			}
			if ($strSqlValues <> '')
			{
				$con->queryExecute($strSqlPrefix.substr($strSqlValues, 2));
			}
		}

		array_shift($this->compCacheStack);
	}

	public function abortTagCache()
	{
		array_shift($this->compCacheStack);
	}

	public function registerTag($tag)
	{
		if (count($this->compCacheStack))
		{
			$this->compCacheStack[0][1][$tag] = true;
			$this->wasTagged = true;
		}
	}

	public function clearByTag($tag)
	{
		$con = Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();

		if ($tag === true)
			$sqlWhere = " WHERE TAG <> '*'";
		else
			$sqlWhere = "  WHERE TAG = '".$sqlHelper->forSql($tag)."'";

		$arDirs = array();
		$rs = $con->query("SELECT * FROM b_cache_tag".$sqlWhere);
		while ($ar = $rs->fetch())
			$arDirs[$ar["RELATIVE_PATH"]] = $ar;

		$con->queryExecute("DELETE FROM b_cache_tag".$sqlWhere);

		$obCache = Cache::createInstance();
		foreach ($arDirs as $path => $ar)
		{
			$con->queryExecute("
				DELETE FROM b_cache_tag
				WHERE SITE_ID = '".$sqlHelper->forSql($ar["SITE_ID"])."'
				AND CACHE_SALT = '".$sqlHelper->forSql($ar["CACHE_SALT"])."'
				AND RELATIVE_PATH = '".$sqlHelper->forSql($ar["RELATIVE_PATH"])."'
			");

			if (preg_match("/^managed:(.+)$/", $path, $match))
				$this->cleanDir($match[1]);
			else
				$obCache->cleanDir($path);

			unset($this->dbCacheTags[$path]);
		}
	}
}
