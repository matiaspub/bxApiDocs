<?php

class CSecuritySystemInformation
{
	/**
	 * Return system information, such as php version
	 * @return array
	 */
	public static function getSystemInformation()
	{
		$systemInformation = array(
			"php" => self::getPhpInfo(),
			"db" => self::getDbInfo(),
			"memcache" => self::getMemCacheInfo(),
			"environment" => self::getEnvironmentInfo()
		);
		return $systemInformation;
	}

	/**
	 * Return current domain name (in puny code for cyrillic domain)
	 * @return string
	 */
	public static function getCurrentHost()
	{
		$host = COption::GetOptionString("main", "server_name", $_SERVER["HTTP_HOST"]);
		if($host == "")
			$host = $_SERVER["HTTP_HOST"];
		return CBXPunycode::ToASCII($host, $arErrors);
	}

	/**
	 * Return information about environment, such as BitrixVM version
	 * @return array
	 */
	protected static function getEnvironmentInfo()
	{
		return array(
			"vm_version" => self::getBitrixVMVersion()
		);
	}

	/**
	 * Return BitrixVM version
	 * @return string
	 */
	protected static function getBitrixVMVersion()
	{
		$result = getenv('BITRIX_VA_VER');
		if(!$result)
			$result = "";
		return $result;
	}

	/**
	 * Return information about php configuration
	 * @return array
	 */
	protected static function getPhpInfo()
	{
		$result = array(
			"sapi" => @php_sapi_name(),
			"version" => @phpversion(),
			"extensions" => implode(', ',@get_loaded_extensions())
		);
		return $result;
	}

	/**
	 * Return information about DB configuration
	 * @return array
	 */
	protected static function getDbInfo()
	{
		global $DB;
		$result = array(
			"type"    => $DB->type,
			"version" => $DB->GetVersion(),
			"hosts"   => self::getDBHosts()
		);
		return $result;
	}

	/**
	 * @return string
	 */
	protected static function getMemCacheSID()
	{
		$result = "";
		if(defined("BX_MEMCACHE_CLUSTER"))
			$result .= BX_MEMCACHE_CLUSTER;
		if(defined("BX_CACHE_SID"))
			$result .= BX_CACHE_SID;

		return $result;
	}

	/**
	 * Return information about memcached from cluster module
	 * @return array
	 */
	protected static function getMemCacheInfoFromCluster()
	{
		$result = array();
		if(CModule::IncludeModule("cluster"))
		{
			$clusterMemcaches = CClusterMemcache::GetList();
			while($clusterMemcacheServer = $clusterMemcaches->Fetch())
			{
//				if($clusterMemcacheServer["STATUS"] == "ONLINE"){
				$result[] = array(
					"host" => $clusterMemcacheServer["HOST"],
					"port" => $clusterMemcacheServer["PORT"],
				);
//				}
			}
		}
		return $result;
	}

	/**
	 * Return information about memcached from Bitrix constants (in dbconn.php)
	 * @return array
	 */
	protected static function getMemCacheInfoFromConstants()
	{
		$result = array();
		if(defined('BX_MEMCACHE_HOST'))
			$result["host"] = BX_MEMCACHE_HOST;

		if(defined('BX_MEMCACHE_PORT'))
			$result["port"] = BX_MEMCACHE_PORT;

		if(!empty($result))
		{
			return array($result);
		}
		else
		{
			return array();
		}
	}

	/**
	 * Return memcached hosts
	 * @return array
	 */
	protected static function getMemCachedHosts()
	{
		$clusterMemcache = self::getMemCacheInfoFromCluster();
		$nativeMemcache = self::getMemCacheInfoFromConstants();
		if(!empty($clusterMemcache) || !empty($nativeMemcache))
		{
			return array_merge($clusterMemcache, $nativeMemcache);
		}
		else
		{
			return array();
		}
	}

	/**
	 * Return summary information about memcached
	 * @return array
	 */
	protected static function getMemCacheInfo()
	{
		$memcacheHosts = self::getMemCachedHosts();
		if(!empty($memcacheHosts))
		{
			return array(
				"hosts" => $memcacheHosts,
				"sid" => self::getMemCacheSID()
			);
		}
		else
		{
			return array();
		}
	}

	/**
	 * @return array
	 */
	protected static function getDBHosts()
	{
		$cluserDB = self::getDBHostsFromCluster();
		$nativeDB = self::getDBHostsFromConstants();
		if(!empty($nativeDB) || !empty($cluserDB))
		{
			return array_merge($nativeDB, $cluserDB);
		}
		else
		{
			return array();
		}
	}

	/**
	 * Return information about DB from cluster module
	 * @return array
	 */
	protected static function getDBHostsFromCluster()
	{
		$result = array();
		if(CModule::IncludeModule("cluster"))
		{
			$clusterDBs = CClusterDBNode::GetList(
				array(//Order
					"ID" => "ASC",
				)
				,array(//Filter
					"=ROLE_ID" => array("SLAVE", "MASTER")
				)
				,array(//Select
					"DB_HOST"
				)
			);
			while($clusterDBServer = $clusterDBs->Fetch()) {
				$result[] = array(
					"host" => $clusterDBServer["DB_HOST"]
				);
			}
		}
		return $result;
	}

	/**
	 * Return information about DB from Bitrix constants (in dbconn.php)
	 * @return array
	 */
	protected static function getDBHostsFromConstants()
	{
		/** @global CDatabase $DB */
		global $DB;
		$result = array("host" => $DB->DBHost);
		return $result;
	}
}
