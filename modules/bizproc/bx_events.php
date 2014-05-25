<?
/**
 * 
 * Класс-контейнер событий модуля <b>bizproc</b>
 * 
 */
class _CEventsBizproc {
	/**
	 * перед добавлением записи в историю.
	 * 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CBPHistoryService::AddHistory<br><br>
	 */
	public static function OnAddToHistory(){}

	/**
	 * перед удалением файла из истории.
	 * 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CBPAllHistoryService::DeleteHistory<br><br>
	 */
	public static function OnBeforeDeleteFileFromHistory(){}

	/**
	 * при создании экземпляра бизнес-процесса.
	 * 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CBPRuntime::CreateWorkflow<br><br>
	 */
	public static function OnCreateWorkflow(){}

	/**
	 * при создании задания бизнес-процесса.
	 * 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CBPTaskService::Add<br><br>
	 */
	public static function OnTaskAdd(){}

	/**
	 * при удалении задания бизнес-процесса.
	 * 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CBPAllTaskService::DeleteByWorkflow<br><br>
	 */
	public static function OnTaskDelete(){}

	/**
	 * после того, как производится удаление записи о задании пользователя. Если в БП несколько заданий (для разных пользователей) событие вызовется несколько раз.
	 * 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CBPAllTaskService::MarkCompleted<br><br>
	 */
	public static function OnTaskMarkCompleted(){}

	/**
	 * при обновлении задания бизнес-процесса.
	 * 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CBPTaskService::Update<br><br>
	 */
	public static function OnTaskUpdate(){}


}
?>