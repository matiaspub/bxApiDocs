<?
/**
 * 
 * Класс-контейнер событий модуля <b>statistic</b>
 * 
 */
class _CEventsStatistic {
	/**
	 * Идентификатор типа события event1.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function event1(){}

	/**
	 * Идентификатор типа события event2.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function event2(){}

	/**
	 * <a href="/api_help/statistic/terms.php#event3">Дополнительный параметр event3</a> события.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function event3(){}

	/**
	 * Дата в <a href="/api_help/main/general/constants.php#format_datetime">текущем формате</a>.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function date(){}

	/**
	 * <a href="/api_help/statistic/terms.php#gid">Специальный параметр</a> в котором закодированы все необходимые данные для добавления события.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function gid(){}

	/**
	 * Денежная сумма.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function money(){}

	/**
	 * Трехсимвольный идентификатор валюты. Идентификаторы валют задаются в модуле "Валюты".
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function currency(){}

	/**
	 * Флаг отрицательной суммы. Используется когда необходимо зафиксировать событие о возврате денег (chargeback). Возможные значения: 
	 *         <ul>
<li>
<b>Y</b> - денежная сумма отрицательная; </li>
	 *         
	 *           <li>
<b>N</b> - денежная сумма положительная. </li>
	 *         </ul>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function chargeback(){}

	/**
	 * при поиске города. Позволяет использовать альтернативные механизмы определения города посетителя. В качестве примера можно посмотреть  <code>/bitrix/modules/statistic/tools/geoip*.php</code>.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CCity::GetHandler
	 */
	public static function OnCityLookup(){}


}
?>