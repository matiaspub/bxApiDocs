<?
/**
 * 
 * Класс-контейнер событий модуля <b>form</b>
 * 
 */
class _CEventsForm {
	/**
	 * после добавления сервера CRM, с которым можно связать форму.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CFormCrm::Add
	 */
	public static function OnAfterFormCrmAdd(){}

	/**
	 * после удаления сервера CRM, с которым может быть связана форма.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CFormCrm::Delete
	 */
	public static function OnAfterFormCrmDelete(){}

	/**
	 * после обновления сервера CRM, с которым может быть связана форма.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CFormCrm::Update
	 */
	public static function OnAfterFormCrmUpdate(){}

	/**
	 * перед добавлением сервера CRM, с которым может быть связана форма.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CFormCrm::Add
	 */
	public static function OnBeforeFormCrmAdd(){}

	/**
	 * перед удалением сервера CRM, с которым может быть связана форма.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CFormCrm::Delete
	 */
	public static function OnBeforeFormCrmDelete(){}

	/**
	 * перед обновлением сервера CRM, с которым может быть связана форма.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CFormCrm::Update
	 */
	public static function OnBeforeFormCrmUpdate(){}

	/**
	 * перед добавлением нового результата веб-формы.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CFormResult::Add
	 */
	public static function onBeforeResultAdd(){}

	/**
	 * после добавления нового результата веб-формы.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/add.php">CFormResult::Add</a>
	 */
	public static function onAfterResultAdd(){}

	/**
	 * перед сохранением изменений существующего результата.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/update.php">CFormResult::Update</a>
	 */
	public static function onBeforeResultUpdate(){}

	/**
	 * после сохранения изменений результата веб-формы.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/update.php">CFormResult::Update</a>
	 */
	public static function onAfterResultUpdate(){}

	/**
	 * перед удалением результата веб-формы.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/delete.php">CFormResult::Delete</a>
	 */
	public static function onBeforeResultDelete(){}

	/**
	 * перед изменением статуса результата веб-формы.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/setstatus.php">CFormResult::SetStatus</a>
	 */
	public static function onBeforeResultStatusChange(){}

	/**
	 * после изменения статуса результата веб-формы.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformresult/setstatus.php">CFormResult::SetStatus</a>
	 */
	public static function onAfterResultStatusChange(){}

	/**
	 * при сборе списка кастомных валидаторов полей формы.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/getalllist.php">CFormValidator::GetAllList</a>
	 */
	public static function onFormValidatorBuildList(){}


}
?>