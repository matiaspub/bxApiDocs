<?

/***************************************
		Ответ на вопрос веб-формы
***************************************/


/**
 * <b>CFormAnswer</b> - класс для работы с <a href="http://dev.1c-bitrix.ru/api_help/form/terms.php#answer">ответами</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/classes/cformanswer/index.php
 * @author Bitrix
 */
class CFormAnswer extends CAllFormAnswer
{
	public static function err_mess()
	{
		$module_id = "form";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." (".$arModuleVersion["VERSION"].")<br>Class: CFormAnswer<br>File: ".__FILE__;
	}
}
?>