<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Main\Data;

use Bitrix\Main;

class TaggedCache
{
	protected $compCacheStack = array();
	protected $salt = false;
	protected $dbCacheTags = array();
	protected $wasTagged = false;
	protected $isMySql = false;

	public function __construct()
	{
		$this->isMySql = (static::getDbType() === "MYSQL");
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

	protected function initDbCache($path)
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
				AND RELATIVE_PATH = '".$sqlHelper->forSql($path)."'
			");
			while ($ar = $rs->fetch())
			{
				$this->dbCacheTags[$path][$ar["TAG"]] = true;
			}
		}
	}

	protected function initCompSalt()
	{
		if ($this->salt === false)
		{
			$this->salt = Cache::getSalt();
		}
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
				if ($path <> '')
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
		if (!empty($this->compCacheStack))
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
		{
			$sqlWhere = " WHERE TAG <> '*'";
		}
		else
		{
			$sqlWhere = "  WHERE TAG = '".$sqlHelper->forSql($tag)."'";
		}

		$arDirs = array();
		$rs = $con->query("SELECT * FROM b_cache_tag".$sqlWhere);
		while ($ar = $rs->fetch())
		{
			$arDirs[$ar["RELATIVE_PATH"]] = $ar;
		}

		$con->queryExecute("DELETE FROM b_cache_tag".$sqlWhere);

		$cache = Cache::createInstance();
		$managedCache = Main\Application::getInstance()->getManagedCache();

		foreach ($arDirs as $path => $ar)
		{
			$con->queryExecute("
				DELETE FROM b_cache_tag
				WHERE SITE_ID = '".$sqlHelper->forSql($ar["SITE_ID"])."'
				AND CACHE_SALT = '".$sqlHelper->forSql($ar["CACHE_SALT"])."'
				AND RELATIVE_PATH = '".$sqlHelper->forSql($ar["RELATIVE_PATH"])."'
			");

			if (preg_match("/^managed:(.+)$/", $path, $match))
			{
				$managedCache->cleanDir($match[1]);
			}
			else
			{
				$cache->cleanDir($path);
			}

			unset($this->dbCacheTags[$path]);
		}
	}
}
