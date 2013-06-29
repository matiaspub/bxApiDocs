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
	 * <i>Вызывается в методе:</i><br>
	 * CBPHistoryService::AddHistory
	 */
	public static function OnAddToHistory(){}

	/**
	 * перед удалением файла из истории.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CBPAllHistoryService::DeleteHistory
	 */
	public static function OnBeforeDeleteFileFromHistory(){}

	/**
	 * при создании экземпляра бизнес-процесса.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CBPRuntime::CreateWorkflow
	 */
	public static function OnCreateWorkflow(){}

	/**
	 * при создании задания бизнес-процесса.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CBPTaskService::Add
	 */
	public static function OnTaskAdd(){}

	/**
	 * при удалении задания бизнес-процесса.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CBPAllTaskService::DeleteByWorkflow
	 */
	public static function OnTaskDelete(){}

	/**
	 * при завершении задания бизнес-процесса.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CBPAllTaskService::MarkCompleted
	 */
	public static function OnTaskMarkCompleted(){}

	/**
	 * при обновлении задания бизнес-процесса.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CBPTaskService::Update
	 */
	public static function OnTaskUpdate(){}


}
?>