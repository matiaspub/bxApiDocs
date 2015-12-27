<?
IncludeModuleLangFile(__FILE__);
//Never increase caching time here. There were cache clenup problems noticed.
if(!defined("CACHED_b_cluster_dbnode")) // define("CACHED_b_cluster_dbnode", 3600);
global $DB;
$db_type = strtolower($DB->type);
CModule::AddAutoloadClasses(
	"cluster",
	array(
		"CClusterGroup" => "classes/general/group.php",
		"CClusterQueue" => "classes/general/queue.php",
		"CAllClusterDBNode" => "classes/general/dbnode.php",
		"CClusterDBNode" => "classes/".$db_type."/dbnode.php",
		"CAllClusterDBNodeCheck" => "classes/general/dbnode_check.php",
		"CClusterDBNodeCheck" => "classes/".$db_type."/dbnode_check.php",
		"CClusterSlave" => "classes/".$db_type."/slave.php",
		"CClusterMemcache" =>  "classes/general/memcache.php",
		"CClusterWebnode" =>  "classes/general/webnode.php",
	)
);

if(defined("BX_CLUSTER_GROUP"))
	CClusterQueue::Run();

class CCluster
{
	static public function checkForServers($toBeAddedCount = 0)
	{
		$countLimit = intval(COption::GetOptionString('main', '~PARAM_MAX_SERVERS', 0));
		if ($countLimit > 0)
		{
			return (self::getServersCount() + $toBeAddedCount) <= $countLimit;
		}
		else
		{
			return true;
		}
	}

	public function getServersCount()
	{
		static $cache = null;
		if ($cache === null)
		{
			$hosts = array();
			foreach(self::getServerList() as $server)
			{
				if ($server["DEDICATED"] == "Y")
					$hosts[] = $server["HOST"];
			}
			$cache = count(array_unique($hosts));
		}
		return $cache;
	}

	static public function getServerList()
	{
		$servers = array_merge(
			CClusterDBNode::getServerList()
			,CClusterMemcache::getServerList()
			,CClusterWebnode::getServerList()
		);
		if (empty($servers))
		{
			$servers[] = array(
				"ID" => 0,
				"HOST" => "",
				"DEDICATED" => "Y",
				"EDIT_URL" => "",
			);
		}
		return $servers;
	}
}
?>