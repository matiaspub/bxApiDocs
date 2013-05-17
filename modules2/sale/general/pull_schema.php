<?
IncludeModuleLangFile(__FILE__);

//RegisterModuleDependences("pull", "OnGetDependentModule", "sale", "CSalePullSchema", "OnGetDependentModule");

class CSalePullSchema
{
	public static function OnGetDependentModule()
	{
		return Array(
			'MODULE_ID' => "sale",
			'USE' => Array("PUBLIC_SECTION", "ADMIN_SECTION")
		);
	}
}

?>
