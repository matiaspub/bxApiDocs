<?
function __OnAfterSetOption_disk_space($value)
{
	if(COption::GetOptionInt("main", "disk_space") > 0)
		RegisterModuleDependences("main", "OnEpilog", "main", "CDiskQuota", "setDBSize");
	else
		UnRegisterModuleDependences("main", "OnEpilog", "main", "CDiskQuota", "setDBSize");
}
AddEventHandler("main", 'OnAfterSetOption_disk_space', '__OnAfterSetOption_disk_space');
?>