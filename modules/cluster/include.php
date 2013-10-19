<?
if(!defined("CACHED_b_cluster_dbnode")) // define("CACHED_b_cluster_dbnode", 360000);
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
?>