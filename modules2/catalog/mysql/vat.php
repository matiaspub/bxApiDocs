<?
/***************************************
		VAT rates
***************************************/

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/vat.php");

class CCatalogVat extends CAllCatalogVat
{
	public static function err_mess()
	{
		$module_id = "catalog";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." (".$arModuleVersion["VERSION"].")<br>Class: CCatalogVat<br>File: ".__FILE__;
	}
}
?>