<?
/**
 * 
 * Класс-контейнер событий модуля <b>bizproc</b>
 * 
 */
class _CEventsBizproc {
/**
 * Вызывается перед добавлением записи в историю.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CBPHistoryService::AddHistory<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/bizproc/events/index.php
 * @author Bitrix
 */
	public static function OnAddToHistory(){}

/**
 * Вызывается перед удалением файла из истории.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CBPAllHistoryService::DeleteHistory<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/bizproc/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeDeleteFileFromHistory(){}

/**
 * Вызывается при создании экземпляра бизнес-процесса.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CBPRuntime::CreateWorkflow<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/bizproc/events/index.php
 * @author Bitrix
 */
	public static function OnCreateWorkflow(){}

/**
 * Вызывается при создании задания бизнес-процесса.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CBPTaskService::Add<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/bizproc/events/index.php
 * @author Bitrix
 */
	public static function OnTaskAdd(){}

/**
 * Вызывается при удалении задания бизнес-процесса.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CBPAllTaskService::DeleteByWorkflow<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/bizproc/events/index.php
 * @author Bitrix
 */
	public static function OnTaskDelete(){}

/**
 * Вызывается после того, как производится удаление записи о задании пользователя. Если в БП несколько заданий (для разных пользователей) событие вызовется несколько раз.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CBPAllTaskService::MarkCompleted<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/bizproc/events/index.php
 * @author Bitrix
 */
	public static function OnTaskMarkCompleted(){}

/**
 * Вызывается при обновлении задания бизнес-процесса.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CBPTaskService::Update<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/bizproc/events/index.php
 * @author Bitrix
 */
	public static function OnTaskUpdate(){}


}
?>