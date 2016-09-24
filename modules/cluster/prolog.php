<?
// define("ADMIN_MODULE_NAME", "cluster");

$message = null;
if(CModule::IncludeModule('cluster'))
{
	if(!CCluster::checkForServers(0))
	{
		$message = new CAdminMessage(array("MESSAGE"=>GetMessage("CLUSTER_SERVER_COUNT_ERROR"), "TYPE"=>"ERROR"));
	}
}
?>