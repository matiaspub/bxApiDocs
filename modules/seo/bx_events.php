<?
/**
 * 
 * Класс-контейнер событий модуля <b>seo</b>
 * 
 */
class _CEventsSeo {
/**
 * <p>Событие предназначено для назначения собственных проверок и вывода собственных рекомендаций в инструменте <b>Страница</b> модуля <b>Поисковая оптимизация</b>.</p> <br><br>
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/seo/events/onpagecheck.php
 * @author Bitrix
 */
	public static function onPageCheck(){}

/**
 * <p>Событие <b>OnSeoCountersGetList</b> предназначено для подключения собственного программного счетчика.</p> <a name="examples"></a>
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * function MyOnSeoCountersGetList() 
 * { 
 *   $value = rand(100, 500); 
 *   return  
 *       '&lt;div style="width: 150px; text-align: center; border: solid 1px red; margin: 1px; padding: 5px;"&gt;Тестовый счетчик: &lt;b&gt;'.$value.'&lt;/b&gt;&lt;/div&gt;' 
 *       .'&lt;!--/Start webdew.ro/--&gt;&lt;a href="http://www.webdew.ro/utils.php"&gt;&lt;img src="http://www.webdew.ro/pagerank/free-pagerank-display.php?a=getCode&amp;s=goo" title="Free PageRank Display Code" border="0px" alt="PageRank" /&gt;&lt;/a&gt;&lt;!--/End webdew.ro/--&gt;'; 
 * } 
 *  
 * addEventHandler('seo', 'OnSeoCountersGetList', 'MyOnSeoCountersGetList');
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/seo/events/onseocountersgetlist.php
 * @author Bitrix
 */
	public static function OnSeoCountersGetList(){}


}
?>