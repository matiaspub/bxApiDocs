<?
/***************************************
		Вопрос (поле) веб-формы
***************************************/


/**
 * <b>CFormField</b> - класс для работы с <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#question">вопросами</a> и <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#field">полями</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/classes/cformfield/index.php
 * @author Bitrix
 */
class CFormField extends CAllFormField
{
public static 	function err_mess()
	{
		$module_id = "form";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." (".$arModuleVersion["VERSION"].")<br>Class: CFormField<br>File: ".__FILE__;
	}
}

?>