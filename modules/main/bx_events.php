<?
/**
 * 
 * Класс-контейнер событий модуля <b>main</b>
 * 
 */
class _CEventsMain {
	/**
	 * в начале выполняемой части пролога сайта, после подключения всех библиотек и отработки <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3436" target="_blank">агентов</a>.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function OnPageStart(){}

	/**
	 * в конце выполняемой части пролога сайта (после события <a href="/api_help/main/events/onpagestart.php">OnPageStart</a>).
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function OnBeforeProlog(){}

	/**
	 * в начале визуальной части пролога сайта.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CAllMain::PrologActions
	 */
	public static function OnProlog(){}

	/**
	 * в конце визуальной части эпилога сайта.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function OnEpilog(){}

	/**
	 * в конце выполняемой части эпилога сайта (после события <a href="/api_help/main/events/onepilog.php">OnEpilog</a>).
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function OnAfterEpilog(){}

	/**
	 * перед выводом буферизированного контента
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CAllMain::EndBufferContent
	 */
	public static function OnBeforeEndBufferContent(){}

	/**
	 * перед сбросом буфера контента
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CAllMain::RestartBuffer
	 */
	public static function OnBeforeRestartBuffer(){}

	/**
	 * при выводе буферизированного контента.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CAllMain::EndBufferContent
	 */
	public static function OnEndBufferContent(){}


}
?>