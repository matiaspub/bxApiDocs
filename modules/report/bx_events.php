<?
/**
 * 
 * Класс-контейнер событий модуля <b>report</b>
 * 
 */
class _CEventsReport {
/**
 * перед добавлением отчета.
 * <i>Вызывается в методе:</i><br>
 * CReport::Add<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/report/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeReportAdd(){}

/**
 * перед удалением отчета.
 * <i>Вызывается в методе:</i><br>
 * CReport::Delete<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/report/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeReportDelete(){}

/**
 * перед обновлением отчета.
 * <i>Вызывается в методе:</i><br>
 * CReport::Update<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/report/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeReportUpdate(){}

/**
 * после добавления отчета.
 * <i>Вызывается в методе:</i><br>
 * CReport::Add<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/report/events/index.php
 * @author Bitrix
 */
	public static function OnReportAdd(){}

/**
 * после удаления отчета.
 * <i>Вызывается в методе:</i><br>
 * CReport::Delete<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/report/events/index.php
 * @author Bitrix
 */
	public static function OnReportDelete(){}

/**
 * после обновления отчета.
 * <i>Вызывается в методе:</i><br>
 * CReport::Update<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/report/events/index.php
 * @author Bitrix
 */
	public static function OnReportUpdate(){}


}
?>