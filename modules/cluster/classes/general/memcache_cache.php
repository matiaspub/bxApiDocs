<?
class CPHPCacheMemcacheCluster
{
	private static $obMemcache;
	private static $arOtherGroups = array();
	var $bQueue = null;
	var $sid;
	//cache stats
	var $written = false;
	var $read = false;
	// unfortunately is not available for memcache...

	private static $arList = false;

	public static function LoadConfig()
	{
		if(self::$arList === false)
		{
			if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/cluster/memcache.php"))
				include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/cluster/memcache.php");

			if(defined("BX_MEMCACHE_CLUSTER") && is_array($arList))
			{
				foreach($arList as $i => $arServer)
				{
					$bOtherGroup = defined("BX_CLUSTER_GROUP") && ($arServer["GROUP_ID"] !== BX_CLUSTER_GROUP);

					if(($arServer["STATUS"] !== "ONLINE") || $bOtherGroup)
						unset($arList[$i]);

					if($bOtherGroup)
						self::$arOtherGroups[$arServer["GROUP_ID"]] = true;
				}

				self::$arList = $arList;
			}
			else
				self::$arList = array();

		}
		return self::$arList;
	}

	public function __construct()
	{
		$this->CPHPCacheMemcache();
	}

	public function CPHPCacheMemcache()
	{
		if(!is_object(self::$obMemcache))
		{
			self::$obMemcache = new Memcache;
			$arServerList = CPHPCacheMemcacheCluster::LoadConfig();

			if(count($arServerList) == 1)
			{
				$arServer = array_pop($arServerList);
				self::$obMemcache->connect(
					$arServer["HOST"]
					,$arServer["PORT"]
				);
			}
			else
			{
				foreach($arServerList as $arServer)
				{
					self::$obMemcache->addServer(
						$arServer["HOST"]
						,$arServer["PORT"]
						,true //persistent
						,($arServer["WEIGHT"] > 0? $arServer["WEIGHT"]: 1)
						,1 //timeout
					);
				}
			}
		}

		if(defined("BX_CACHE_SID"))
			$this->sid = BX_MEMCACHE_CLUSTER.BX_CACHE_SID;
		else
			$this->sid = BX_MEMCACHE_CLUSTER;

		if(defined("BX_CLUSTER_GROUP"))
			$this->bQueue = true;
	}

	public static function IsAvailable()
	{
		return count(self::$arList) > 0;
	}

	public function QueueRun($param1, $param2, $param3)
	{
		$this->bQueue = false;
		$this->clean($param1, $param2, $param3);
	}

	public function clean($basedir, $initdir = false, $filename = false)
	{
		if(is_object(self::$obMemcache))
		{
			if(
				$this->bQueue
				&& class_exists('CModule')
				&& CModule::IncludeModule('cluster')
			)
			{
				foreach(self::$arOtherGroups as $group_id => $tmp)
					CClusterQueue::Add($group_id , 'CPHPCacheMemcacheCluster', $basedir, $initdir, $filename);
			}

			if(strlen($filename))
			{
				$basedir_version = self::$obMemcache->get($this->sid.$basedir);
				if($basedir_version === false || $basedir_version === '')
					return true;

				if($initdir !== false)
				{
					$initdir_version = self::$obMemcache->get($basedir_version."|".$initdir);
					if($initdir_version === false || $initdir_version === '')
						return true;
				}
				else
				{
					$initdir_version = "";
				}

				self::$obMemcache->replace($basedir_version."|".$initdir_version."|".$filename, "", 0, 1);
			}
			else
			{
				if(strlen($initdir))
				{
					$basedir_version = self::$obMemcache->get($this->sid.$basedir);
					if($basedir_version === false || $basedir_version === '')
						return true;

					self::$obMemcache->replace($basedir_version."|".$initdir, "", 0, 1);
				}
				else
				{
					self::$obMemcache->replace($this->sid.$basedir, "", 0, 1);
				}
			}
			return true;
		}

		return false;
	}

	public function read(&$arAllVars, $basedir, $initdir, $filename, $TTL)
	{
		$basedir_version = self::$obMemcache->get($this->sid.$basedir);
		if($basedir_version === false || $basedir_version === '')
			return false;

		if($initdir !== false)
		{
			$initdir_version = self::$obMemcache->get($basedir_version."|".$initdir);
			if($initdir_version === false || $initdir_version === '')
				return false;
		}
		else
		{
			$initdir_version = "";
		}

		$arAllVars = self::$obMemcache->get($basedir_version."|".$initdir_version."|".$filename);

		if($arAllVars === false || $arAllVars === '')
			return false;

		return true;
	}

	public function write($arAllVars, $basedir, $initdir, $filename, $TTL)
	{
		$basedir_version = self::$obMemcache->get($this->sid.$basedir);
		if($basedir_version === false || $basedir_version === '')
		{
			$basedir_version = md5(mt_rand());
			self::$obMemcache->set($this->sid.$basedir, $basedir_version);
		}

		if($initdir !== false)
		{
			$initdir_version = self::$obMemcache->get($basedir_version."|".$initdir);
			if($initdir_version === false || $initdir_version === '')
			{
				$initdir_version = md5(mt_rand());
				self::$obMemcache->set($basedir_version."|".$initdir, $initdir_version);
			}
		}
		else
		{
			$initdir_version = "";
		}

		self::$obMemcache->set($basedir_version."|".$initdir_version."|".$filename, $arAllVars, 0, time()+intval($TTL));
	}

	public static function IsCacheExpired($path)
	{
		return false;
	}
}
?>