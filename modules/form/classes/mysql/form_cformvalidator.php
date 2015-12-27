<?
/**
 * Form validator class
 *
 */


/**
 * <b>CFormValidator</b> - класс для работы с <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#validator">валидаторами</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/index.php
 * @author Bitrix
 */
class CFormValidator extends CAllFormValidator 
{
	public static function err_mess()
	{
		$module_id = "form";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." (".$arModuleVersion["VERSION"].")<br>Class: CFormValidator<br>File: ".__FILE__;
	}	
}
?>