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
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CReport::Add<br><br>
	 */
	public static function OnBeforeReportAdd(){}

	/**
	 * перед удалением отчета.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CReport::Delete<br><br>
	 */
	public static function OnBeforeReportDelete(){}

	/**
	 * перед обновлением отчета.
	 * 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CReport::Update<br><br>
	 */
	public static function OnBeforeReportUpdate(){}

	/**
	 * после добавления отчета.</body>
	 * </html
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CReport::Add<br><br>
	 */
	public static function OnReportAdd(){}

	/**
	 * после удаления отчета.</body>
	 * </htm
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CReport::Delete<br><br>
	 */
	public static function OnReportDelete(){}

	/**
	 * после обновления отчета.</body>
	 * </html
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CReport::Update<br><br>
	 */
	public static function OnReportUpdate(){}


}
?>