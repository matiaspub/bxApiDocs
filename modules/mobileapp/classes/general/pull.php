<?
class CMobileAppPullSchema
{
	public static function OnGetDependentModule()
	{
		return Array(
			'MODULE_ID' => "mobileapp",
			'USE' => Array("PUBLIC_SECTION", "ADMIN_SECTION")
		);
	}
}
?>