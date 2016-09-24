<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/option.php");


/**
 * <b>COption</b> - класс для работы с параметрами модулей, хранимых в базе данных.<br><br> Как правило управление параметрами модулей осуществляется в административном интерфейсе в настройках соответствующих модулей.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/coption/index.php
 * @author Bitrix
 */
class COption extends CAllOption
{
}


/**
 * <b>CPageOption</b> - класс для работы с <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2814#params" >параметрами страницы</a>.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cpageoption/index.php
 * @author Bitrix
 */
class CPageOption extends CAllPageOption
{
}
?>