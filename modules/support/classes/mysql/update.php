<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/support/classes/general/update.php");

class CSupportUpdate extends CAllSupportUpdate
{
	public static function err_mess()
	{
		$module_id = "support";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." <br>Class: CSupportUpdate<br>File: ".__FILE__;
	}
	
	public static function GetBD()
	{	
		return "MySQL";// "MySQL","MSSQL","Oracle"
	}
	
	
}

?>