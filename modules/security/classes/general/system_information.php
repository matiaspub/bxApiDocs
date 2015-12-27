<?php

class CSecuritySystemInformation
{
	/**
	 * Return system information, such as php version
	 *
	 * @return array
	 */
	public static function getSystemInformation()
	{
		$systemInformation = array(
			'php' => static::getPhpInfo(),
			'db' => static::getDbInfo(),
			'memcache' => static::getMemCacheInfo(),
			'environment' => static::getEnvironmentInfo()
		);
		return $systemInformation;
	}

	/**
	 * Return additional information, such as P&P or LDAP server information
	 *
	 * @since 14.5.4
	 * @return array
	 */
	public static function getAdditionalInformation()
	{
		$additionalInformation = array(
			'pulling' => static::getPullingInfo(),
			'sites' => static::getSites()
		);
		return $additionalInformation;
	}

	/**
	 * Return current host/port (in puny code for cyrillic domain)
	 *
	 * @return string
	 */
	public static function getCurrentHost()
	{
		$host = COption::GetOptionString("main", "server_name", $_SERVER["HTTP_HOST"]);
		if (!$host)
			$host = $_SERVER["HTTP_HOST"];
		return trim(CBXPunycode::ToASCII($host, $arErrors));
	}

	/**
	 * Return current host name (in puny code for cyrillic domain)
	 *
	 * @return string
	 */
	public static function getCurrentHostName()
	{
		$host = static::getCurrentHost();
		return preg_replace('#:\d+$#D', '', $host);
	}

	/**
	 * @since 14.0.4
	 * @return bool
	 */
	public static function isRunOnWin()
	{
		return (strtoupper(substr(PHP_OS, 0, 3)) === "WIN");
	}

	/**
	 * @since 14.0.6
	 * @return bool
	 */
	public static function isCliMode()
	{
		return PHP_SAPI === 'cli';
	}

	/**
	 * Validates IP address (IPv4 only).
	 *
	 * @since 15.5.0
	 * @param string $ip IP address for checking.
	 * @param bool $allowPrivate Fails or not for the following private IPv4 ranges: 10.0.0.0/8, 172.16.0.0/12 and 192.168.0.0/16.
	 * @param bool $allowRes Fails or not for the following reserved IPv4 ranges: 0.0.0.0/8, 169.254.0.0/16, 192.0.2.0/24, 127.0.0.0/24 and 224.0.0.0/4.
	 * @return bool
	 */
	public static function isIpValid($ip, $allowPrivate = false, $allowRes = false)
	{
		// ToDo: what about PHP filters?
		if (ip2long($ip) === false)
			return false;

		$ipOctets = explode('.', $ip);
		if (!$allowPrivate)
		{
			// php/ext/filter/logical_filters.c, FILTER_FLAG_NO_PRIV_RANGE
			if ($ipOctets[0] == 10)
				return false;

			if ($ipOctets[0] == 172 && $ipOctets[1] >= 16 && $ipOctets[1] <= 31)
				return false;

			if ($ipOctets[0] == 192 && $ipOctets[1] == 168)
				return false;

		}

		if (!$allowRes)
		{
			// php/ext/filter/logical_filters.c, FILTER_FLAG_NO_RES_RANGE
			if ($ipOctets[0] == 0)
				return false;

			if ($ipOctets[0] == 100 && $ipOctets[1] >= 64 && $ipOctets[1] <= 127)
				return false;

			if ($ipOctets[0] == 169 && $ipOctets[1] == 254)
				return false;

			if ($ipOctets[0] == 192 && $ipOctets[1] == 0 && $ipOctets[2] == 2)
				return false;

			if ($ipOctets[0] == 127 && $ipOctets[1] == 0 && $ipOctets[2] == 0)
				return false;

			if ($ipOctets[0] >= 224 && $ipOctets[0] <= 255)
				return false;
		}

		return true;
	}
	/**
	 * Return all sites (and domains) on current kernel
	 *
	 * @since 14.5.4
	 * @return array
	 */
	protected static function getSites()
	{
		$result = array();
		$dbSites = CSite::GetList($b = 'sort', $o = 'asc', array('ACTIVE' => 'Y'));
		while ($arSite = $dbSites->Fetch())
		{
			$domains = explode("\n", str_replace("\r", "\n", $arSite["DOMAINS"]));
			$domains = array_filter($domains);
			$result[] = array(
				'ID' => $arSite['ID'],
				'DOMAINS' => $domains
			);
		}

		return $result;
	}


	/**
	 * Return some information about P&P, such as publish url
	 *
	 * @since 14.5.4
	 * @return array
	 */
	protected static function getPullingInfo()
	{
		$result = array(
			'enabled' => CModule::IncludeModule('pull') && CPullOptions::ModuleEnable()
		);
		if ($result['enabled'])
		{
			$result['nginx_used'] = CPullOptions::GetQueueServerStatus();
			if ($result['nginx_used'])
			{
				$result['server_protocol'] = CPullOptions::GetQueueServerVersion();
				$result['publish_url'] = CPullOptions::GetPublishUrl();

				$result['pulling_url'] = CPullOptions::GetListenUrl();
				$result['pulling_url_secure'] = CPullOptions::GetListenSecureUrl();

				$result['websocket_url'] = CPullOptions::GetWebSocketUrl();
				$result['websocket_url_secure'] = CPullOptions::GetWebSocketSecureUrl();
			}
		}
		return $result;
	}

	/**
	 * Return information about environment, such as BitrixVM version
	 *
	 * @return array
	 */
	protected static function getEnvironmentInfo()
	{
		return array(
			"vm_version" => static::getBitrixVMVersion()
		);
	}

	/**
	 * Return BitrixVM version
	 *
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
	 *
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
	 *
	 * @return array
	 */
	protected static function getDbInfo()
	{
		global $DB;
		$result = array(
			"type"    => $DB->type,
			"version" => $DB->GetVersion(),
			"hosts"   => static::getDBHosts()
		);
		return $result;
	}

	/**
	 * Return used memcache SID (with cluster support)
	 *
	 * @return string
	 */
	protected static function getMemcacheSID()
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
	 *
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
	 *
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
	 *
	 * @return array
	 */
	protected static function getMemCachedHosts()
	{
		$clusterMemcache = static::getMemCacheInfoFromCluster();
		$nativeMemcache = static::getMemCacheInfoFromConstants();
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
	 *
	 * @return array
	 */
	protected static function getMemCacheInfo()
	{
		$memcacheHosts = static::getMemCachedHosts();
		if(!empty($memcacheHosts))
		{
			return array(
				"hosts" => $memcacheHosts,
				"sid" => static::getMemcacheSID()
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
		$cluserDB = static::getDBHostsFromCluster();
		$nativeDB = static::getDBHostsFromConstants();
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
	 *
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
			while($clusterDBServer = $clusterDBs->Fetch())
			{
				$result[] = array(
					"host" => $clusterDBServer["DB_HOST"]
				);
			}
		}
		return $result;
	}

	/**
	 * Return information about DB from Bitrix constants (in dbconn.php)
	 *
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
