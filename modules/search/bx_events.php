<?
/**
 * 
 * Класс-контейнер событий модуля <b>search</b>
 * 
 */
class _CEventsSearch {
/**
 * <p>Событие "BeforeIndex" вызывается перед индексацией элемента методом <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearch/indexs.php">CSearch::Index</a>.</p>
 *
 *
 * @param array $arFields  Массив следующего содержания: <ul> <li> <b>MODULE_ID</b> - идентификатор
 * модуля (не изменится);</li> <li> <b>ITEM_ID</b> - идентификатор элемента (не
 * изменится);</li> <li> <b>PARAM1</b> - первый параметр элемента;</li> <li> <b>PARAM2</b> -
 * второй параметр элемента;</li> <li> <b>DATE_FROM</b> - дата начала активности
 * элемента;</li> <li> <b>DATE_TO</b> - дата окончания активности элемента;</li>
 * <li> <b>TITLE</b> - заголовок;</li> <li> <b>BODY</b> - содержание;</li> <li> <b>TAGS</b> - теги
 * элемента;</li> <li> <b>SITE_ID</b> - массив сайтов;</li> <li> <b>PERMISSIONS</b> - массив
 * идентификаторов групп пользователей которым разрешено
 * чтение;</li> <li> <b>URL</b> - адрес относительно корня сайта, по которому
 * доступен данный элемент;</li> </ul>
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?<br>// файл /bitrix/php_interface/init.php<br>// регистрируем обработчик<br>AddEventHandler("search", "<b>BeforeIndex</b>", Array("MyClass", "BeforeIndexHandler"));<br>
 * class MyClass
 * {
 *     // создаем обработчик события "BeforeIndex"
 *     function BeforeIndexHandler($arFields)
 *     {
 * 	if($arFields["MODULE_ID"] == "iblock" &amp;&amp; $arFields["PARAM2"] == 33)
 * 	{
 * 		if(array_key_exists("BODY", $arFields))
 * 		{
 * 			$arFields["BODY"] .= " самые свежие новости";
 * 		}
 * 	}
 * 	return $arFields;
 *     }
 * }
 * ?&gt;
 * 
 * 
 * // регистрируем обработчик
 * AddEventHandler("search", "BeforeIndex", "BeforeIndexHandler");
 *  // создаем обработчик события "BeforeIndex"
 * function BeforeIndexHandler($arFields)
 * {
 *    if(!CModule::IncludeModule("iblock")) // подключаем модуль
 *       return $arFields;
 *    if($arFields["MODULE_ID"] == "iblock")
 *    {
 *       $db_props = CIBlockElement::GetProperty(                        // Запросим свойства индексируемого элемента
 *                                     $arFields["PARAM2"],         // BLOCK_ID индексируемого свойства
 *                                     $arFields["ITEM_ID"],          // ID индексируемого свойства
 *                                     array("sort" =&gt; "asc"),       // Сортировка (можно упустить)
 *                                     Array("CODE"=&gt;"CML2_ARTICLE")); // CODE свойства (в данном случае артикул)
 *       if($ar_props = $db_props-&gt;Fetch())
 *          $arFields["TITLE"] .= " ".$ar_props["VALUE"];   // Добавим свойство в конец заголовка индексируемого элемента
 *    }
 *    return $arFields; // вернём изменения
 * }
 * 
 * 
 * Смотрите также
 * <li><a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=5196">Как ограничить область поиска разделом инфоблока</a></li>
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearch/indexs.php">CSearch::Index</a></li> </ul><a
 * name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/search/events/beforeindex.php
 * @author Bitrix
 */
	public static function BeforeIndex($arFields){}

/**
 * <p>Событие "OnSearch" вызывается перед выполнением поисковых запросов методом <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearch/search.php">CSearch::Search</a>.</p>
 *
 *
 * @param string $strQuery  Поисковая фраза. Если используется поиск по тегам, то в начале
 * добавляется "tags:".
 *
 * @return string <p>Функция обработчик может вернуть строку вида
 * "параметр=значение" которая будет добавлена к ссылкам на
 * найденные элементы. Используется модулем статистики для учета
 * поисковых фраз внутреннего поисковика.</p>
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?
 * // файл /bitrix/php_interface/init.php
 * // регистрируем обработчик
 * AddEventHandler("search", "<b>OnSearch</b>", Array("MyClass", "OnSearchHandler"));<br>
 * class MyClass
 * {
 *     // создаем обработчик события "BeforeIndex"
 *     function OnSearchHandler($strQuery)
 *     {
 * 	if(strpos($strQuery, "tags:")!==false)
 * 		return "tags_search=Y";
 * 	else
 * 		return "";
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearch/search.php">CSearch::Search</a></li> <li><a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a></li> </ul> </htm<a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/search/events/onsearch.php
 * @author Bitrix
 */
	public static function OnSearch($strQuery){}

/**
 * <p>Событие "OnReindex" вызывается во время переиндексации данных модуля методами <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearch/reindexmodule.php">CSearch::ReindexModule</a> или <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearch/reindexall.php">CSearch::ReIndexAll</a>.</p>
 *
 *
 * @param array $NS  Массив в котором передается информация о начале текущего шага. <ul>
 * <li> <b>MODULE</b> - идентификатор модуля;</li> <li> <b>ID</b> - идентификатор
 * элемента;</li> <li> <b>SITE_ID</b> - массив сайтов;</li> </ul>
 *
 * @param string $oCallback  Объект модуля поиска для вызова метода индексации элемента.
 *
 * @param string $callback_method  Метод объекта модуля поиска для индексации элемента.
 *
 * @return bool 
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?<br><br>// регистрируем обработчик события "OnReindex" модуля "search"<br>RegisterModuleDependences("search", "OnReindex", "my_module", "CMyModule", "OnReindex");<br><br>// создаем в модуле my_module в классе CMyModule функцию-метод OnReindex<br>function OnReindex($NS, $oCallback, $callback_method)<br>{<br>	global $DB;<br><br>	$NS["ID"] = intval($NS["ID"]);<br>	if($NS["MODULE"]=="my_module" &amp;&amp; $NS["ID"] &gt; 0)<br>		$strWhere = "WHERE ID &gt; ".$NS["ID"];<br>	else<br>		$strWhere = "";<br><br>	$strSql =<br>		"SELECT FT.ID, FT.TITLE, FT.MESSAGE, ".<br>		"  DATE_FORMAT(FT.POST_DATE, '%d.%m.%Y %H:%i:%s') as POST_DATE, FT.LID ".<br>		"FROM b_my_table FT ".<br>		$strWhere.<br>		" ORDER BY FT.ID";<br><br>	$db_res = $DB-&gt;Query($strSql);<br>	while ($res = $db_res-&gt;Fetch())<br>	{<br>		$Result = array(<br>			"ID" =&gt; $res["ID"],<br>			"SITE_ID" =&gt; array("s1"),<br>			"DATE_CHANGE" =&gt; $res["POST_DATE"],<br>			"URL" =&gt; "/my_module/index.php?ID=".$res["ID"],<br>			"PERMISSIONS" =&gt; array(2),<br>			"TITLE" =&gt; $res["TITLE"],<br>			"BODY" =&gt; $res["MESSAGE"],<br>		);<br>		$index_res = call_user_func(array($oCallback, $callback_method), $Result);<br>		if(!$index_res)<br>			return $Result["ID"];<br>	}<br>	return false;<br>}<br><br>// вызываем переиндексацию модуля<br>CSearch::ReIndexModule("my_module");<br><br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/search/classes/csearch/reindexmodule.php">CSearch::ReindexModule</a></li> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/search/classes/csearch/reindexall.php">CSearch::ReIndexAll</a></li> <li><a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a></li> </ul> </htm<a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/search/events/onreindex.php
 * @author Bitrix
 */
	public static function OnReIndex($NS, $oCallback, $callback_method){}

/**
 * <p>Событие "OnSearchGetFileContent" вызывается во время переиндексации данных главного модуля <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearch/reindexfile.php">CSearch::ReIndexFile</a>.</p>
 *
 *
 * @param string $absolute_path  Абсолютный путь к индексируемому файлу.
 *
 * @param string $SEARCH_SESS_ID  Идентификатор текущей сессии индексации. Может использоваться в
 * обработчике события для добавления в поисковый индекс
 * дополнительного контента с помощью метода <a
 * href="http://dev.1c-bitrix.ru/api_help/search/classes/csearch/indexs.php">CSearch::Index</a>.
 *
 * @return mixed <p>Функция-обработчик может вернуть массив описывающий
 * содержимое файла. Массив должен иметь следующую структуру:</p> <ul>
 * <li> <b>TITLE</b> - заголовок (обязательное поле);</li> <li> <b>CONTENT</b> -
 * содержимое документа;</li> <li> <b>PROPERTIES</b> - массив свойств документа
 * (обязательное). Если свойств нет, то должен быть передан пустой
 * массив. Содержимое элемента этого массива с именем указанным в
 * настройках модуля как "Код свойства страницы в котором хранятся
 * теги" будет занесен в теги;</li> </ul> <p>Или может вернуть false, если не
 * знает как файл должен быть обработан.</p>
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?<br>//init.php<br><br>// индексируем сжатые gzip файлы.<br>// регистрируем обработчик события "OnSearchGetFileContent" модуля "search"<br>AddEventHandler("search", "OnSearchGetFileContent", array("CMyClass", "OnSearchGetFileContent_gzip"));<br><br>class CMyClass<br>{<br>	function OnSearchGetFileContent_gzip($absolute_path)<br>	{<br><br>		if(file_exists($absolute_path) &amp;&amp; is_file($absolute_path) &amp;&amp; substr($absolute_path, -3) == ".gz")<br>		{<br>			return array(<br>				"TITLE" =&gt; basename($absolute_path),<br>				"CONTENT" =&gt; implode("\n", gzfile($absolute_path)),<br>				"PROPERTIES" =&gt; array(),<br>			);<br>		}<br>		else<br>			return false;<br>	}<br>}<br><br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearch/reindexfile.php">CSearch::ReIndexFile</a></li>
 * <li><a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a></li> </ul> </htm<a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/search/events/onsearchgetfilecontent.php
 * @author Bitrix
 */
	public static function OnSearchGetFileContent($absolute_path, $SEARCH_SESS_ID){}

/**
 * <p>Событие "OnSearchGetURL" вызывается при форматировании элемента в результатах поиска из метода <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearch/fetch.php">CSearch::Fetch</a> и при построении Google Sitemap <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csitemap/create.php">CSiteMap::Create</a>. На данный момент событие вызывается только для параметризированных URL.</p>
 *
 *
 * @param array $arFields  Массив описывающий элемент поискового индекса.
 *
 * @return string <p>Функция-обработчик может применить форматирование к элементу
 * URL. И должна его вернуть даже если форматирование не было
 * применено.</p>
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?<br>//init.php<br><br>// регистрируем обработчик события "OnSearchGetURL" модуля "search"<br>AddEventHandler("search", "OnSearchGetURL", array("CMyClass", "OnSearchGetURL"));<br><br>class CMyClass<br>{<br>	function OnSearchGetURL($arFields)<br>	{<br>		$url = str_replace("#MY_SID#", md5(rand()), $arFields["URL"]);<br>		return $url;<br>	}<br>}<br><br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearch/fetch.php">CSearch::Fetch</a></li> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/search/classes/csitemap/create.php">CSiteMap::Create</a></li> <li><a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a></li> </ul> </htm<a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/search/events/onsearchgeturl.php
 * @author Bitrix
 */
	public static function OnSearchGetURL($arFields){}

/**
 * <p>Событие "OnSearchGetTag" вызывается при разборе строки тегов из функции <a href="http://dev.1c-bitrix.ru/api_help/search/functions/tags_prepare.php">Tags_prepare</a>.</p>
 *
 *
 * @param string $tag  Тег. </b
 *
 * @return string <p>Функция-обработчик может отфильтровать недопустимые символы
 * или значения тега. И должна его вернуть даже если форматирование
 * не было применено.</p>
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?<br>//init.php<br><br>// регистрируем обработчик события "OnSearchGetTag" модуля "search"<br>AddEventHandler("search", "OnSearchGetTag", array("CMyClass", "OnSearchGetTag"));<br><br>class CMyClass<br>{<br>	function OnSearchGetTag($tag)<br>	{<br>		static $stop = array(<br>			"АХ" =&gt; true,<br>			"ФУ" =&gt; true,<br>		);<br>		$tag = ToUpper($tag);<br>		if(array_key_exists($tag, $stop))<br>			return "";<br>		else<br>			return $tag;<br>	}<br>}<br><br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/search/functions/tags_prepare.php">Tags_prepare</a></li> <li><a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a></li> </ul> </htm<a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/search/events/onsearchgettag.php
 * @author Bitrix
 */
	public static function OnSearchGetTag($tag){}

/**
 * <p>Событие "OnBeforeFullReindexClear" вызывается во время полной переиндексации. В начале первого шага, непосредственно перед удалением всех данных поискового индекса. <br></p>
 *
 *
 * @return void 
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?<br><br>// регистрируем обработчик события "OnBeforeFullReindexClear" модуля "search"<br>RegisterModuleDependences("search", "OnBeforeFullReindexClear", "my_module", "CMyModule", "TruncateTables");<br><br>// создаем в модуле my_module в классе CMyModule функцию-метод TruncateTables<br>function TruncateTables()<br>{<br>	global $DB;<br>	$DB-&gt;Query("truncate table my_search_ext_data");<br>}<br><br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a></li> </ul> </htm<a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/search/events/onbeforefullreindexclear.php
 * @author Bitrix
 */
	public static function OnBeforeFullReindexClear(){}

/**
 * <p>Событие "OnBeforeIndexDelete" вызывается перед удалением части поискового индекса. <br></p>
 *
 *
 * @param string $strWhere  SQL условие для удаления. Представляет собой фильтр по полю
 * SEARCH_CONTENT_ID. <br>
 *
 * @return void 
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?<br><br>// регистрируем обработчик события "OnBeforeIndexDelete" модуля "search"<br>RegisterModuleDependences("search", "OnBeforeIndexDelete", "my_module", "CMyModule", "DeleteSearchExtData");<br><br>// создаем в модуле my_module в классе CMyModule функцию-метод TruncateTables<br>function DeleteSearchExData($strWhere)<br>{<br>	global $DB;<br>	$DB-&gt;Query("delete from my_search_ext_data where ".$strWhere);<br>}<br><br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a></li> </ul> </htm<a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/search/events/onbeforeindexdelete.php
 * @author Bitrix
 */
	public static function OnBeforeIndexDelete($strWhere){}

/**
 * <p>Событие "OnBeforeIndexUpdate" вызывается перед обновлением поискового индекса. <br></p>
 *
 *
 * @param int $ID  Уникальный идентификатор записи в поисковом индексе. <br>
 *
 * @param array $arFields  Поля поискового индекса.
 *
 * @return void 
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?<br><br>// регистрируем обработчик события "OnBeforeIndexUpdate" модуля "search"<br>RegisterModuleDependences("search", "OnBeforeIndexUpdate", "my_module", "CMyModule", "AddSearchExtData");<br><br>// создаем в модуле my_module в классе CMyModule функцию-метод AddSearchExtData<br>function AddSearchExtData($ID, $arFields)<br>{<br>	global $DB;<br>        if($arFields["MODULE_ID"]=="my_module")<br>	  $DB-&gt;Add("my_search_ext_data", array("SEARCH_CONTENT_ID"=&gt;$ID, "EXT_DATA"=&gt;time()));<br>}<br><br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a></li> </ul> </htm<a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/search/events/onbeforeindexupdate.php
 * @author Bitrix
 */
	public static function OnBeforeIndexUpdate($ID, $arFields){}

/**
 * <p>Событие "OnAfterIndexAdd" вызывается после добавления новых данных в поисковый индекс. <br></p>
 *
 *
 * @param int $ID  Уникальный идентификатор записи в поисковом индексе. <br>
 *
 * @param array $arFields  Поля поискового индекса.
 *
 * @return void 
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?<br><br>// регистрируем обработчик события "OnAfterIndexAdd" модуля "search"<br>RegisterModuleDependences("search", "OnAfterIndexAdd", "my_module", "CMyModule", "AddSearchExtData");<br><br>// создаем в модуле my_module в классе CMyModule функцию-метод AddSearchExtData<br>function AddSearchExtData($ID, $arFields)<br>{<br>	global $DB;<br>        if($arFields["MODULE_ID"]=="my_module")<br>	  $DB-&gt;Add("my_search_ext_data", array("SEARCH_CONTENT_ID"=&gt;$ID, "EXT_DATA"=&gt;time()));<br>}<br><br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a></li> </ul> </htm<a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/search/events/onafterindexadd.php
 * @author Bitrix
 */
	public static function OnAfterIndexAdd($ID, $arFields){}

/**
 * <p>Событие "OnSearchCheckPermissions" вызывается при построении поискового запроса. Позволяет задать дополнительные условия для определения прав доступа к результатам поиска. <br></p>
 *
 *
 * @param string $FIELD  Столбец таблицы поискового индекса для использования в
 * подзапросе (например: SC.ID или scsite.SEARCH_CONTENT_ID). <br>
 *
 * @return void 
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a></li> </ul> </htm<br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/search/events/onsearchcheckpermissions.php
 * @author Bitrix
 */
	public static function OnSearchCheckPermissions($FIELD){}


}
?>