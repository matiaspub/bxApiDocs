<?
/**
 * 
 * Класс-контейнер событий модуля <b>report</b>
 * 
 */
class _CEventsReport {
	/**
	 * перед добавлением отчета.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CReport::Add
	 */
	public static function OnBeforeReportAdd(){}

	/**
	 * перед удалением отчета.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CReport::Delete
	 */
	public static function OnBeforeReportDelete(){}

	/**
	 * перед обновлением отчета.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CReport::Update
	 */
	public static function OnBeforeReportUpdate(){}

	/**
	 * после добавления отчета.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CReport::Add
	 */
	public static function OnReportAdd(){}

	/**
	 * после удаления отчета.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CReport::Delete
	 */
	public static function OnReportDelete(){}

	/**
	 * после обновления отчета.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CReport::Update
	 */
	public static function OnReportUpdate(){}


}
?>