<?
/**
 * 
 * Класс-контейнер событий модуля <b>main</b>
 * 
 */
class _CEventsMain {
/**
 * Событие "OnPageStart" вызывается в начале выполняемой части пролога сайта, после подключения всех библиотек и отработки <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3436" >Агентов</a>.
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * RegisterModuleDependences("main", "<b>OnPageStart</b>", "statistic", "CStatistic", "Stoplist", "100");
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeprolog.php">Событие
 * "OnBeforeProlog"</a></li> <li><a href="http://dev.1c-bitrix.ru/api_help/main/general/pageplan.php">Этапы
 * выполнения страницы и доступные на каждом этапе переменные и
 * константы </a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onpagestart.php
 * @author Bitrix
 */
	public static function OnPageStart(){}

/**
 * Событие "OnBeforeProlog" вызывается в выполняемой части пролога сайта (после события <a href="http://dev.1c-bitrix.ru/api_help/main/events/onpagestart.php">OnPageStart</a>).
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/php_interface/init.php
 * AddEventHandler("main", "<b>OnBeforeProlog</b>", "MyOnBeforePrologHandler", 50);<br>
 * function MyOnBeforePrologHandler()
 * {
 *    global $USER;
 *    if(SITE_TEMPLATE_ID=='mynewtemplate' &amp;&amp; $_SERVER['REMOTE_ADDR']!='127.0.0.1' &amp;&amp; !$USER-&gt;IsAdmin())
 *       die('This template temporary unavailable.');
 * }
 * ?&gt;Смотрите также<li><a href="http://dev.1c-bitrix.ru/community/forums/messages/forum6/topic17229/message95191/#message95191">Как посчитать количество пользователей онлайн</a></li>
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onpagestart.php">Событие "OnPageStart"</a></li>
 * <li><a href="http://dev.1c-bitrix.ru/api_help/main/general/pageplan.php">Этапы выполнения
 * страницы и доступные на каждом этапе переменные и константы </a></li>
 * <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493"
 * ></a>События</li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforeprolog.php
 * @author Bitrix
 */
	public static function OnBeforeProlog(){}

/**
 * Событие "OnProlog"  вызывается в начале визуальной части пролога сайта.
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 *     RegisterModuleDependences("main", 
 *                               "<b>OnProlog</b>",
 *                               "statistic",
 *                               "CStatistic",
 *                               "StartBuffer", "100");
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeprolog.php">Событие "OnBeforeProlog"</a>
 * </li> 	<li><a href="http://dev.1c-bitrix.ru/api_help/main/general/pageplan.php">Этапы выполнения
 * страницы и  доступные на каждом этапе переменные и константы
 * </a></li> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493"
 * ></a>События</li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onprolog.php
 * @author Bitrix
 */
	public static function OnProlog(){}

/**
 * Событие "OnEpilog" вызывается в конце визуальной части эпилога сайта.
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * AddEventHandler("main", "<b>OnEpilog</b>", "statistic", "CStatistic", "Set404", "100");
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/events/onafterepilog.php">Событие "OnAfterEpilog"</a>
 * </li> <li><a href="http://dev.1c-bitrix.ru/api_help/main/general/pageplan.php">Этапы выполнения
 * страницы и доступные на каждом этапе переменные и константы </a></li>
 * <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493"
 * ></a>События</li>   </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onepilog.php
 * @author Bitrix
 */
	public static function OnEpilog(){}

/**
 * <p>Событие "OnAfterEpilog" возникает в конце выполняемой части эпилога сайта (после события <a href="http://dev.1c-bitrix.ru/api_help/main/events/onepilog.php">OnEpilog</a>).  </p>
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * RegisterModuleDependences("main", 
 *                           "<b>OnAfterEpilog</b>", 
 *                           "compression",
 *                           "CCompress",
 *                           "OnAfterEpilog");
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onepilog.php">Событие "OnEpilog"</a></li> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/general/pageplan.php">Этапы выполнения страницы и
 * доступные на каждом этапе переменные и константы </a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onafterepilog.php
 * @author Bitrix
 */
	public static function OnAfterEpilog(){}

/**
 * перед выводом буферизированного контента
 * <i>Вызывается в методе:</i><br>
 * CAllMain::EndBufferContent<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/main/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeEndBufferContent(){}

/**
 * перед сбросом буфера контента
 * <i>Вызывается в методе:</i><br>
 * CAllMain::RestartBuffer<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/main/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeRestartBuffer(){}

/**
 * <p>Вызывается при выводе буферизированного контента.</p>
 *
 *
 * @param mixed $Frame  
 *
 * @param Fram $endBuffering  
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onendbuffercontent.php
 * @author Bitrix
 */
	public static function OnEndBufferContent($Frame, $endBuffering){}

/**
 * <p>Событие OnAdminContextMenuShow вызывается в функции <a href="http://dev.1c-bitrix.ru/api_help/main/general/admin.section/classes/cadmincontextmenu/show.php">CAdminContextMenu::Show()</a> при выводе в административном разделе панели кнопок. Событие позволяет модифицировать или добавить собственные кнопки на панель.</p>
 *
 *
 * @param array &$items  Ссылка на массив кнопок на панели. Структура массива описана на
 * странице <a
 * href="http://dev.1c-bitrix.ru/api_help/main/general/admin.section/classes/cadmincontextmenu/cadmincontextmenu.php">Конструктор
 * CAdminContextMenu</a>.
 *
 * @return void 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?<br>AddEventHandler("main", "OnAdminContextMenuShow", "MyOnAdminContextMenuShow");<br>function MyOnAdminContextMenuShow(&amp;$items)<br>{<br>	//add custom button to the index page toolbar<br>	if($GLOBALS["APPLICATION"]-&gt;GetCurPage(true) == "/bitrix/admin/index.php")<br>		$items[] = array("TEXT"=&gt;"Настройки модулей", "ICON"=&gt;"", "TITLE"=&gt;"Страница настроек модулей", "LINK"=&gt;"settings.php?lang=".LANGUAGE_ID);<br>}<br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493"
 * ></a>События</li>     <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/general/admin.section/classes/cadmincontextmenu/index.php">Класс
 * CAdminContextMenu</a></li>  </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onadmincontextmenushow.php
 * @author Bitrix
 */
	public static function OnAdminContextMenuShow(&$items){}

/**
 * <p>Событие OnAdminListDisplay вызывается в функции <a href="http://dev.1c-bitrix.ru/api_help/main/general/admin.section/classes/cadminlist/display.php">CAdminList::Display()</a> при выводе в административном разделе списка элементов. Событие позволяет модифицировать объект списка, в частности, добавить произвольные групповые действия над элементами списка, добавить команды в меню действий элемента списка и т.п.</p>
 *
 *
 * @param object &$list  Ссылка на объект класса <a
 * href="http://dev.1c-bitrix.ru/api_help/main/general/admin.section/classes/cadminlist/index.php">CAdminList</a>.
 *
 * @return void 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?<br>AddEventHandler("main", "OnAdminListDisplay", "MyOnAdminListDisplay");<br>function MyOnAdminListDisplay(&amp;$list)<br>{<br>	//add custom group action<br>	if($list-&gt;table_id == "tbl_posting")<br>		$list-&gt;arActions["status_draft"] = "Статус: Черновик";<br>}<br>//process custom action<br>AddEventHandler("main", "OnBeforeProlog", "MyOnBeforeProlog");<br>function MyOnBeforeProlog()<br>{<br>	if($_SERVER["REQUEST_METHOD"] == "POST" &amp;&amp; $_POST["action"] == "status_draft" &amp;&amp; is_array($_POST["ID"]) &amp;&amp; $GLOBALS["APPLICATION"]-&gt;GetCurPage() == "/bitrix/admin/posting_admin.php")<br>	{<br>		if($GLOBALS["APPLICATION"]-&gt;GetGroupRight("subscribe") == "W" &amp;&amp; check_bitrix_sessid())<br>		{<br>			if(CModule::IncludeModule("subscribe"))<br>			{<br>				$cPosting = new CPosting;<br>				foreach($_POST["ID"] as $ID)<br>					if(($ID = intval($ID)) &gt; 0)<br>						$cPosting-&gt;ChangeStatus($ID, "D");<br>			}<br>		}<br>	}<br>}<br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493"
 * ></a>События</li>    <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/general/admin.section/classes/cadminlistrow/index.php">Класс
 * CAdminListRow</a></li>  </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onadminlistdisplay.php
 * @author Bitrix
 */
	public static function OnAdminListDisplay(&$list){}

/**
 * <p>Событие OnAdminTabControlBegin вызывается в функции <a href="http://dev.1c-bitrix.ru/api_help/main/general/admin.section/classes/cadmintabcontrol/begin.php">CAdminTabControl::Begin()</a> при выводе в административном интерфейсе формы редактирования. Событие позволяет изменить или добавить собственные вкладки формы редактирования.</p>
 *
 *
 * @param  &$form  Ссылка на объект класса <a
 * href="http://dev.1c-bitrix.ru/api_help/main/general/admin.section/classes/cadmintabcontrol/index.php">CAdminTabControl</a>.
 * Использование члена класса $form-&gt;tabs позволяет получить доступ к
 * закладкам формы. Структура массива закладок описана на странице
 * <a
 * href="http://dev.1c-bitrix.ru/api_help/main/general/admin.section/classes/cadmintabcontrol/cadmintabcontrol.php">Конструктор
 * CAdminTabControl</a>.
 *
 * @return void 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?<br>AddEventHandler("main", "OnAdminTabControlBegin", "MyOnAdminTabControlBegin");<br>function MyOnAdminTabControlBegin(&amp;$form)<br>{<br>	if($GLOBALS["APPLICATION"]-&gt;GetCurPage() == "/bitrix/admin/posting_edit.php")<br>	{<br>		$form-&gt;tabs[] = array("DIV" =&gt; "my_edit", "TAB" =&gt; "Дополнительно", "ICON"=&gt;"main_user_edit", "TITLE"=&gt;"Дополнительные параметры", "CONTENT"=&gt;<br>			'&lt;tr valign="top"&gt;<br>				&lt;td&gt;Дополнительные заголовки письма:&lt;/td&gt;<br>				&lt;td&gt;<br>					&lt;input type="text" name="MY_HEADERS[]" value="" size="30"&gt;&lt;br&gt;<br>					&lt;input type="text" name="MY_HEADERS[]" value="" size="30"&gt;&lt;br&gt;<br>					&lt;input type="text" name="MY_HEADERS[]" value="" size="30"&gt;&lt;br&gt;<br>				&lt;/td&gt;<br>			&lt;/tr&gt;'<br>		);<br>	}<br>}<br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493"
 * ></a>События</li>   <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/general/admin.section/rubric_edit.php">Создание формы
 * редактирования</a></li>  </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onadmintabcontrolbegin.php
 * @author Bitrix
 */
	public static function OnAdminTabControlBegin(&$form){}

/**
 * 
 *
 *
 * @return mixed <p></p>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * <br><br>
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <p></p><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onafterajaxresponse.php
 * @author Bitrix
 */
	public static function onAfterAjaxResponse(){}

/**
 * <p>Событие вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/add.php">CGroup::Add</a> после добавления группы,  и может быть использовано для действий, связанных с группой.</p>
 *
 *
 * @param array &$arFields  Список полей (<a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/index.php">класс
 * CGroup</a>) добавляемой группы пользователей. Массив содержит в том
 * числе ключ <em>ID</em> с идентификатором добавленной группы.
 *
 * @return bool <p>Не используется.</p>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?<br>AddEventHandler("main", "OnAfterGroupAdd", "MyOnAfterGroupAdd");<br>function MyOnAfterGroupAdd(&amp;$arFields)<br>{<br>	AddMessage2Log(print_r($arFields, true));<br>}?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/index.php">CGroup</a> </li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li> 
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onaftergroupadd.php
 * @author Bitrix
 */
	public static function OnAfterGroupAdd(&$arFields){}

/**
 * <p>Событие вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/update.php">CGroup::Update</a> после изменения полей группы,  и может быть использовано для дополнительных действий, связанных с группой.</p>
 *
 *
 * @param int $intID  Идентификатор измененной группы пользователей.
 *
 * @param array &$arFields  Список полей (<a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/index.php">класс
 * CGroup</a>) измененной группы пользователей.
 *
 * @return void <p>Не используется.</p>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?<br>AddEventHandler("main", "OnAfterGroupUpdate", "MyOnAfterGroupUpdate");<br>function MyOnAfterGroupUpdate($ID, &amp;$arFields)<br>{<br>	if($ID == 1)<br>		AddMessage2Log(print_r($arFields, true));<br>}<br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/index.php">CGroup</a> </li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li> 
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onaftergroupupdate.php
 * @author Bitrix
 */
	public static function OnAfterGroupUpdate($intID, &$arFields){}

/**
 * Событие "OnAfterUserAdd" вызывается после попытки добавления нового пользователя методом <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/add.php">CUser::Add</a>.
 *
 *
 * @param array &$arFields  <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php#fuser">Массив полей</a> нового
 * пользователя. Дополнительно, в элементе массива с индексом "RESULT"
 * содержится результат работы (возвращаемое значение) метода <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/add.php">CUser::Add</a> и, в случае ошибки,
 * элемент с индексом "RESULT_MESSAGE" будет содержать текст ошибки.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/php_interface/init.php
 * // регистрируем обработчик
 * AddEventHandler("main", "<b>OnAfterUserAdd</b>", Array("MyClass", "OnAfterUserAddHandler"));<br>
 * class MyClass
 * {
 *     // создаем обработчик события "OnAfterUserAdd"
 *     function OnAfterUserAddHandler(&amp;$arFields)
 *     {
 *         if($arFields["ID"]&gt;0)
 *             AddMessage2Log("Запись с кодом ".$arFields["ID"]." добавлена.");
 *         else
 *             AddMessage2Log("Ошибка добавления записи (".$arFields["RESULT_MESSAGE"].").");
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuseradd.php">Событие
 * "OnBeforeUserAdd"</a></li>   <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/add.php">CUser::Add</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onafteruseradd.php
 * @author Bitrix
 */
	public static function OnAfterUserAdd(&$arFields){}

/**
 * Обработчик события будет вызван из метода <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/authorize.php">CUser::Authorize</a> после авторизации пользователя, передавая в параметре <i>user_fields</i> массив всех полей авторизованного пользователя.
 *
 *
 * @param array $user_fields  Массив всех <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php">полей
 * пользователя</a>.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/php_interface/init.php
 * // регистрируем обработчик
 * AddEventHandler("main", "<b>OnAfterUserAuthorize</b>", Array("MyClass", "OnAfterUserAuthorizeHandler"));<br>
 * class MyClass
 * {
 *     // создаем обработчик события "OnAfterUserAuthorize"
 *     function OnAfterUserAuthorizeHandler($arUser)
 *     {
 * 		 // выполняем все действия связанные с авторизацией
 *       
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/authorize.php">CUser::Authorize</a> </li> <li>
 * <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?&amp;COURSE_ID=43&amp;LESSON_ID=3574"
 * ></a>Внешняя авторизация </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onafteruserauthorize.php
 * @author Bitrix
 */
	public static function OnAfterUserAuthorize($user_fields){}

/**
 * <p>Событие "OnAfterUserLogin" вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/login.php">CUser::Login</a> после попытки авторизовать пользователя, проверив имя входа <i><span class="syntax"><i>arParams</i></span>['LOGIN']</i> и пароль <i><span class="syntax"><i>arParams</i></span>['PASSWORD']</i>.</p>
 *
 *
 * @param array &$arParams  Массив полей проверки имени входа и пароля:       <ul> <li> <b>USER_ID</b> - в
 * случае если авторизация прошла успешно содержит код
 * пользователя 	      </li> <li> <b>RESULT_MESSAGE</b> - массив с информационным
 * текстом, описывающий результат проверки пользователя, в
 * дальнейшем используется функцией <a
 * href="http://dev.1c-bitrix.ru/api_help/main/functions/other/showmessage.php">ShowMessage</a> для вывода
 * сообщения. </li>         <li> <b>LOGIN</b> - Логин пользователя</li>         <li>
 * <b>PASSWORD</b> - Пароль. Если параметр <b>PASSWORD_ORIGINAL</b> равен"Y", то в данном
 * параметре был передан оригинальный пароль, в противном случае
 * был передан хеш (md5) от оригинального пароля. </li>         <li> <b>REMEMBER</b> -
 * Если значение равно "Y", то авторизация пользователя должна быть
 * сохранена в куках.</li>         <li> <b>PASSWORD_ORIGINAL</b> - Если значение равно "Y",
 * то это означает что <b>PASSWORD</b> не был сконвертирован в MD5 (т.е. в
 * параметре <b>PASSWORD</b> был передан реальный пароль вводимый
 * пользователем с клавиатуры), если значение равно "N", то это
 * означает что <b>PASSWORD</b> уже сконвертирован в MD5.</li>     </ul>
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * AddEventHandler("main", "<b>OnAfterUserLogin</b>", Array("MyClass", "OnAfterUserLoginHandler"));<br>
 * class MyClass
 * {
 *     // создаем обработчик события "OnAfterUserLogin"
 *     function OnAfterUserLoginHandler(&amp;$fields)
 *     {
 *         // если логин не успешен то
 *         if($fields['USER_ID']&lt;=0)
 *         {
 *             // счетчик неудавшихся попыток логина
 *             $_SESSION["AUTHORIZE_FAILURE_COUNTER"]++;
 * 
 *             // если количество неудачных попыток авторизации превышает 10, то
 *             if ($_SESSION["AUTHORIZE_FAILURE_COUNTER"]&gt;10)
 *             {
 *                 // ищем пользователя по логину
 *                 $rsUser = CUser::GetByLogin($fields['LOGIN']);
 *                 // и если нашли, то
 *                 if ($arUser = $rsUser-&gt;Fetch())
 *                 {
 *                     // блокируем бюджет пользователя
 *                     $user = new CUser;
 *                     $user-&gt;Update($arUser["ID"],array("ACTIVE" =&gt; "N"));
 * 
 *                     // задаем сообщение
 *                     $fields['RESULT_MESSAGE'] = array("TYPE" =&gt; "ERROR", "MESSAGE" =&gt; "Ваш бюджет блокирован.");
 *                 }
 *             }
 *         }
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserlogin.php">Событие
 * "OnBeforeUserLogin"</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/login.php">CUser::Login</a> </li>  <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li> <li>
 * <a href="http://dev.1c-bitrix.ru/learning/course/index.php?&amp;COURSE_ID=43&amp;LESSON_ID=3574" ></a>Внешняя
 * авторизация </li>     </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onafteruserlogin.php
 * @author Bitrix
 */
	public static function OnAfterUserLogin(&$arParams){}

/**
 * Событие "OnAfterUserLoginByHash" вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/loginbyhash.php">CUser::LoginByHash()</a> после проверки имени входа <i><span class="syntax"><i>arParams</i><b></b></span>['LOGIN']</i> и хеша от пароля <span class="syntax"><i>arParams</i></span><i>['HASH']</i> и попытки авторизовать пользователя. В параметре <span class="syntax"><i>arParams</i></span><i>['USER_ID']</i> будет передан код пользователя которого удалось авторизовать, а также массив с сообщением об ошибке <i><span class="syntax"><i>arParams</i><b></b></span>['RESULT_MESSAGE']</i>.
 *
 *
 * @param array &$arParams  Массив полей проверки имени входа и пароля:       <ul> <li> <b>USER_ID</b> - в
 * случае, если авторизация прошла успешно, содержит код
 * пользователя           </li> <li> <b>RESULT_MESSAGE</b> - массив с информационным
 * текстом, описывающий результат проверки пользователя, в
 * дальнейшем используется функцией <a
 * href="http://dev.1c-bitrix.ru/api_help/main/functions/other/showmessage.php">ShowMessage</a> для вывода
 * сообщения. </li> 	            <li> <b>LOGIN</b> - Логин пользователя</li> 	            <li>
 * <b>HASH</b> - Пароль. Специальный хеш от пароля пользователя. Данный
 * хеш как правило хранится в куках пользователя.</li>       </ul>
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * // файл /bitrix/php_interface/init.php
 * // регистрируем обработчик
 * AddEventHandler("main", "<b>OnAfterUserLoginByHash</b>", Array("MyClass", "OnAfterUserLoginByHashHandler"));<br>
 * class MyClass
 * {
 *     // создаем обработчик события "OnAfterUserLoginByHash"
 *     function OnAfterUserLoginByHashHandler(&amp;$arParams)
 *     {
 *         if($arParams['USER_ID']&lt;=0)
 *         {
 *             //переопределим сообщение об ошибке.
 *             $arParams['RESULT_MESSAGE'] = Array("MESSAGE" =&gt; "New error.", "TYPE" =&gt; "ERROR");
 *         }
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserloginbyhash.php">Событие
 * "OnBeforeUserLoginByHash"</a></li>   <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/loginbyhash.php">CUser::LoginByHash</a> </li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li> <li>
 * <a href="http://dev.1c-bitrix.ru/learning/course/index.php?&amp;COURSE_ID=43&amp;LESSON_ID=3574" ></a>Внешняя
 * авторизация </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onafteruserloginbyhash.php
 * @author Bitrix
 */
	public static function OnAfterUserLoginByHash(&$arParams){}

/**
 * Событие "OnAfterUserLogout" вызывается после <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/logout.php">завершения авторизации</a> пользователя.
 *
 *
 * @param array &$arParams  Массив параметров:          <ul> <li> <b>USER_ID</b> - код пользователя</li>             
 *        <li> <b>SUCCESS </b>- результат операции: true, если авторизация
 * завершена, false - в случае ошибки или отмены завершения авторзации
 * обработчиком события <a
 * href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserlogout.php">OnBeforeUserLogout</a>.</li>          </ul>
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/modules/my_module_id/include.php
 * class MyClass
 * {
 *     // создаем обработчик события "OnAfterUserLogout"
 *     function OnAfterUserLogoutHandler($arParams)
 *     {
 *         // здесь выполняем все что касается завершения авторизации
 *         ...
 *     }
 * }
 * ?&gt;&lt;?
 * // регистрируем обработчик события "OnAfterUserLogout"
 * RegisterModuleDependences("main", "<b>OnAfterUserLogout</b>", 
 * "my_module_id", "MyClass", "OnAfterUserLogoutHandler");?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserlogout.php">Событие
 * "OnBeforeUserLogout"</a> </li>   <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/logout.php">CUser::Logout</a> </li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onafteruserlogout.php
 * @author Bitrix
 */
	public static function OnAfterUserLogout(&$arParams){}

/**
 * Событие "OnAfterUserRegister" вызывается после попытки регистрации нового пользователя методом <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/register.php">CUser::Register</a>.
 *
 *
 * @param array &$arFields  Массив полей регистрации нового пользователя:          <ul> <li> <b>USER_ID</b>
 * - в случае если регистрация прошла успешно содержит код нового
 * пользователя </li>                     <li> <b>RESULT_MESSAGE</b> - массив с
 * информационным текстом, описывающий результат регистрации
 * пользователя, в дальнейшем используется функцией <a
 * href="http://dev.1c-bitrix.ru/api_help/main/functions/other/showmessage.php">ShowMessage</a> для вывода
 * сообщения. </li>                     <li> <b>LOGIN</b> - имя входа пользователя </li>      
 *               <li> <b>NAME</b> - имя пользователя 	 </li>                     <li> <b>LAST_NAME</b> -
 * фамилия пользователя 	 </li>                     <li> <b>PASSWORD</b> - пароль 	 </li>         
 *            <li> <b>CONFIRM_PASSWORD</b> - подтверждение пароля 	 </li>                     <li>
 * <b>CHECKWORD</b> - новое контрольное слово для смены пароля 	 </li>                  
 *   <li> <b>EMAIL</b> - EMail пользователя 	 </li>                     <li> <b>ACTIVE</b> - флаг
 * активности [Y|N] 	 </li>                     <li> <b>SITE_ID</b> - ID сайта по умолчанию
 * для уведомлений 	 </li>                     <li> <b>GROUP_ID</b> - массив ID групп
 * пользователя </li>                     <li> <b>USER_IP</b> - IP адрес пользователя </li>   
 *                  <li> <b>USER_HOST</b> - хост пользователя </li>          </ul>
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?<br>// файл /bitrix/modules/my_module_id/include.php<br>class MyClass<br>{<br>    // создаем обработчик события "OnAfterUserRegister"<br>    function OnAfterUserRegisterHandler(&amp;$arFields)<br>    {<br>        // если регистрация успешна то<br>        if($arFields["USER_ID"]&gt;0)<br>        {<br>            // если текущий сайт - r1, то<br>            if(SITE_ID=="r1")<br>            {<br>                // зададим сообщение об успешной регистрации на сайте r1<br>                $arFields["RESULT_MESSAGE"]["MESSAGE"] = "Вы успешно зарегистрировались на сайте \"Мой любимый сайт 1\"";<br>            }<br>            elseif(SITE_ID=="r2")<br>            {<br>                // зададим сообщение об успешной регистрации на сайте r2<br>                $arFields["RESULT_MESSAGE"]["MESSAGE"] = "Вы успешно зарегистрировались на сайте \"Мой любимый сайт 2\"";<br><br>            }<br>        }<br>        return $arFields;<br>    }<br>}<br>?&gt;&lt;?<br>// регистрируем обработчик события "OnAfterUserRegister"<br>RegisterModuleDependences("main", "<b>OnAfterUserRegister</b>", "my_module_id", "MyClass", "OnAfterUserRegisterHandler");<br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserregister.php">Событие
 * "OnBeforeUserRegister"</a> </li>     <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>     <br> </li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" > </a>   <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/register.php">CUser::Register</a> </li>  </ul><a
 * name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onafteruserregister.php
 * @author Bitrix
 */
	public static function OnAfterUserRegister(&$arFields){}

/**
 * Событие "OnAfterUserSimpleRegister" вызывается после попытки упрощённой регистрации нового пользователя методом <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/simpleregister.php">CUser::SimpleRegister</a>.
 *
 *
 * @param array &$arFields  Массив полей  регистрации нового пользователя: 	  <ul> <li> <b>USER_ID</b> - в
 * случае если регистрация прошла успешно содержит код нового
 * пользователя    		  </li> <li> <b>RESULT_MESSAGE</b> - массив с информационным
 * текстом, описывающий результат регистрации пользователя, в
 * дальнейшем используется функцией <a
 * href="http://dev.1c-bitrix.ru/api_help/main/functions/other/showmessage.php">ShowMessage</a> для вывода
 * сообщения.         </li> <li> <b>PASSWORD</b> - пароль 	      </li> <li> <b>CONFIRM_PASSWORD</b> -
 * подтверждение пароля 		  </li> <li> <b>CHECKWORD</b> - контрольное слово для
 * смены пароля 		  </li> <li> <b>EMAIL</b> - EMail пользователя 		  </li> <li> <b>ACTIVE</b> -
 * флаг активности [Y|N] 		  </li> <li> <b>SITE_ID</b> - ID сайта по умолчанию для
 * уведомлений 		  </li> <li> <b>GROUP_ID</b> - массив ID групп пользователя         	   
 *   </li> <li> <b>USER_IP</b> - IP адрес пользователя          	      </li> <li> <b>USER_HOST</b> -
 * хост пользователя         </li> </ul>
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // регистрируем обработчик события "OnAfterUserSimpleRegister"
 * RegisterModuleDependences("main", "<b>OnAfterUserSimpleRegister</b>", "my_module_id", "MyClass", "OnAfterUserSimpleRegisterHandler");
 * ?&gt;
 * &lt;?
 * // файл /bitrix/modules/my_module_id/include.php
 * class MyClass
 * {
 *     // создаем обработчик события "OnAfterUserSimpleRegister"
 *     function OnAfterUserSimpleRegisterHandler(&amp;$fields)
 *     {
 *         // если регистрация успешна то
 *         if($fields["USER_ID"]&gt;0)
 *         {
 *             // зададим сообщение об успешной регистрации
 *             $fields["RESULT_MESSAGE"]["MESSAGE"] = "Вы успешно зарегистрировались на сайте. Ваш логин - ".$fields["LOGIN"];
 *         }
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeusersimpleregister.php">Событие
 * "OnBeforeUserSimpleRegister"</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/register.php">CUser::Register</a></li> </ul><a
 * name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onafterusersimpleregister.php
 * @author Bitrix
 */
	public static function OnAfterUserSimpleRegister(&$arFields){}

/**
 * Событие <b>OnAfterUserUpdate</b> вызывается после попытки изменения свойств пользователя методом <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/update.php">CUser::Update</a>.
 *
 *
 * @param array &$arFields  <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php#fuser">Массив полей</a>
 * изменяемого пользователя. Дополнительно, в элементе массива с
 * индексом <i>RESULT</i> содержится результат работы (возвращаемое
 * значение) метода <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/update.php">CUser::Update</a> и, в случае
 * ошибки, элемент с индексом <i>RESULT_MESSAGE</i> будет содержать текст
 * ошибки. <p>Если изменяется <i>$arFields["RESULT"]</i> на <i>false</i>, то необходимо
 * устанавливать <i>$USER-&gt;LAST_ERROR</i>.</p>
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/php_interface/init.php
 * // регистрируем обработчик
 * AddEventHandler("main", "<b>OnAfterUserUpdate</b>", Array("MyClass", "OnAfterUserUpdateHandler"));<br>
 * class MyClass
 * {
 *     // создаем обработчик события "OnAfterUserUpdate"
 *     function OnAfterUserUpdateHandler(&amp;$arFields)
 *     {
 *         if($arFields["RESULT"])
 *             AddMessage2Log("Запись с кодом ".$arFields["ID"]." изменена.");
 *         else
 *             AddMessage2Log("Ошибка изменения записи ".$arFields["ID"]." (".$arFields["RESULT_MESSAGE"].").");
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserupdate.php">Событие
 * "OnBeforeUserUpdate"</a></li>   <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/update.php">CUser::Update</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onafteruserupdate.php
 * @author Bitrix
 */
	public static function OnAfterUserUpdate(&$arFields){}

/**
 * <p>Событие "OnBeforeChangeFile" вызывается при изменении файла методом <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/savefilecontent.php">$APPLICATION-&gt;SaveFileContent</a>, перед его сохранением. Событие добавлено в версии 8.5.1 ядра. Контент в событие передается по ссылке.</p>
 *
 *
 * @param string $abs_path  Абсолютный путь к файлу (включая document_root).
 *
 * @param string &$strContent  Новое содержимое файла. Значение передается по ссылке. Таким
 * образом, обработчик может изменить содержимое файла перед его
 * сохранением.
 *
 * @return bool <p>При возврате true поизводится сохранение файла. При возврате false
 * сохранение файла отменяется.</p><h4>Параметры</h4><table class="tnormal"
 * width="100%"><tbody> <tr> <th>Параметр</th> <th>Описание</th> </tr> <tr> <td><i>abs_path</i></td>
 * <td>Абсолютный путь к файлу (включая document_root).</td> </tr> <tr>
 * <td><i>strContent</i></td> <td>Новое содержимое файла. Значение передается по
 * ссылке. Таким образом, обработчик может изменить содержимое
 * файла перед его сохранением.</td> </tr> </tbody></table>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * // файл /bitrix/php_interface/init.php
 * AddEventHandler("main", "OnBeforeChangeFile", "MyBeforeChangeFile");
 * 
 * function MyBeforeChangeFile($abs_path, $content)
 * {
 * 	if(strpos($content, "Вася") !== false)
 * 	{
 * 		$GLOBALS['APPLICATION']-&gt;ThrowException("Вы не можете сохранять слово 'Вася' в документе! (".$abs_path.")");
 * 		return false;
 * 	}
 * 	return true;
 * }
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/events/onchangepermissions.php">Событие
 * "OnChangePermissions"</a> </li>   <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/savefilecontent.php">CMain::SaveFileContent</a> </li> <li>
 * <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li> 
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforechangefile.php
 * @author Bitrix
 */
	public static function OnBeforeChangeFile($abs_path, &$strContent){}

/**
 * Событие <b>OnBeforeEventAdd</b> вызывается в момент добавления почтового события <a href="http://dev.1c-bitrix.ru/api_help/main/general/mailevents.php">в таблицу b_event</a>. Как правило, задача обработчика данного события - изменить или добавить какое-либо значение, передаваемое в макросы почтового шаблона.   <p></p>
 *
 *
 * @param string &$event  Идентификатор типа почтового события, например FORM_FILLING_ANKETA
 *
 * @param string &$lid  ID сайта, на котором был вызов метода CEvent::Send()
 *
 * @param array &$arFields  Массив параметров, которые передаются в обработчик события.         
 * <br>        Пример массива:          <br><span class="Apple-style-span" style="color: rgb(51, 51, 51);
 * font-family: Tahoma, Verdana, Helvetica, sans-serif; font-size: 13px; ">            <pre bgcolor="#323232" style="padding:5px;">Array         (            
 * [RS_FORM_ID] =&gt; 1             [RS_FORM_NAME] =&gt; Анкета посетителя сайта            
 * [RS_FORM_VARNAME] =&gt; ANKETA             [RS_FORM_SID] =&gt; ANKETA             [RS_RESULT_ID] =&gt; 11            
 * [RS_DATE_CREATE] =&gt; 24.07.2011 16:59:55             [RS_USER_ID] =&gt; 1             [RS_USER_EMAIL] =&gt;
 * my@email.com             [RS_USER_NAME] =&gt;               [RS_USER_AUTH] =&gt;               [RS_STAT_GUEST_ID] =&gt;
 * 1             [RS_STAT_SESSION_ID] =&gt; 1             [VS_NAME] =&gt; sfdsf             [VS_NAME_RAW] =&gt; sfdsf      
 *       [VS_BIRTHDAY] =&gt; 21.07.2011             [VS_BIRTHDAY_RAW] =&gt; 21.07.2011             [VS_ADDRESS] =&gt;
 * sdfdsf             [VS_ADDRESS_RAW] =&gt; sdfdsf             [VS_MARRIED] =&gt; Да [4]             [VS_MARRIED_RAW]
 * =&gt; Да             [VS_INTEREST] =&gt; физика (2) [7]                             
 * программирование (5) [10]             [VS_INTEREST_RAW] =&gt;
 * физика,программирование             [VS_AGE] =&gt; 30-39 (30) [13]             [VS_AGE_RAW] =&gt;
 * 30-39             [VS_EDUCATION] =&gt; высшее (3) [19]             [VS_EDUCATION_RAW] =&gt; высшее          
 *   [VS_INCOME] =&gt;               [VS_INCOME_RAW] =&gt;               [VS_PHOTO] =&gt;               [VS_PHOTO_RAW]
 * =&gt;           )</pre>          </span>
 *
 * @param string &$message_id  Идентификатор почтового шаблона (если указан). Если переменная
 * определена в обработчике, то среди нескольких почтовых шаблонов
 * по одному типу будет отправлен только с этим ID, а не все.
 *
 * @param string &$files  Файл
 *
 * @return mixed <div>Нет. Начиная с версии ядра 11.0.16 при возврате <em>false</em> добавление
 * почтового события отменяется.</div>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * //Обработчик в файле /bitrix/php_interface/init.php
 * AddEventHandler("main", "OnBeforeEventAdd", array("MyClass", "OnBeforeEventAddHandler"));
 * class MyClass
 * {
 * 	function OnBeforeEventAddHandler(&amp;$event, &amp;$lid, &amp;$arFields)
 * 	{
 * 		$arFields["NEW_FIELD"] = "Новый макрос для почтового шаблона";
 * 		$arFields["VS_BIRTHDAY"] = "Изменение существующего макроса";
 * 		$lid = 's2'; //Изменяем привязку к сайту
 * 	}
 * }
 * ?&gt;<br><br>
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeeventsend.php">Событие
 * "OnBeforeEventSend"</a> </li>     <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cevent/send.php">CEvent::Send</a> </li>   <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li> 
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforeeventadd.php
 * @author Bitrix
 */
	public static function OnBeforeEventAdd(&$event, &$lid, &$arFields, &$message_id, &$files){}

/**
 * Событие "OnBeforeEventMessageDelete" вызывается перед <a href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/delete.php">удалением почтового шаблона</a>. Как правило задачи обработчика данного события - разрешить или запретить удаление почтового шаблона.
 *
 *
 * @param int $template_id  ID удаляемого почтового шаблона.
 *
 * @return bool <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/delete.php">CEventMessage::Delete</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/modules/my_module_id/include.php
 * class MyClass
 * {
 *     // создаем обработчик события "OnBeforeEventMessageDelete"
 *     function OnBeforeEventMessageDeleteHandler($template_id)
 *     {
 *         // проверим есть ли связанные с удаляемым шаблоном записи
 *         $strSql = "SELECT * FROM my_table WHERE EMAIL_TEMPLATE_ID=".intval($template_id);
 *         $rs = $DB-&gt;Query($strSql, false, "FILE: ".__FILE__."&lt;br&gt;LINE: ".__LINE__);
 * 
 *         // если связанные записи есть то
 *         if ($ar = $rs-&gt;Fetch()) 
 *         {
 *             // запретим удаление почтового шаблона
 *             global $APPLICATION;
 *             $APPLICATION-&gt;throwException("В моей таблице есть связанные записи.");
 *             return false;
 *         }
 *     }
 * }
 * ?&gt;
 * &lt;?
 * // регистрируем обработчик события "OnBeforeEventMessageDelete"
 * RegisterModuleDependences("main", "<b>OnBeforeEventMessageDelete</b>", 
 *              "my_module_id", "MyClass", "OnBeforeEventMessageDeleteHandler");
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/oneventmessagedelete.php">Событие
 * "OnEventMessageDelete"</a></li> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/delete.php">CEventMessage::Delete</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforeeventmessagedelete.php
 * @author Bitrix
 */
	public static function OnBeforeEventMessageDelete($template_id){}

/**
 * <br><br>
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * AddEventHandler('main', 'OnBeforeEventSend', Array("MyForm", "my_OnBeforeEventSend"));
 * class MyForm
 * {
 *    function my_OnBeforeEventSend($arFields, $arTemplate)
 *    {
 *         
 *          //получим сообщение
 *          $mess = $arTemplate["MESSAGE"];
 *          foreach($arFields as $keyField =&gt; $arField)
 *             $mess = str_replace('#'.$keyField.'#', $arField, $mess); //подставляем значения в шаблон
 *    }
 * }
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforeeventsend.php
 * @author Bitrix
 */
	public static function OnBeforeEventSend(){}

/**
 * <p>Событие вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/add.php">CGroup::Add</a> до добавления группы,  и может быть использовано для отмены добавления или переопределения некоторых полей.</p>
 *
 *
 * @param array &$arFields  Список полей (<a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/index.php">класс
 * CGroup</a>) добавляемой группы пользователей.
 *
 * @return bool <p>Для отмены добавления группы и прекращении выполнения метода <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/add.php">CGroup::Add</a> необходимо в
 * функции-обработчике создать исключение методом
 * <nobr>$APPLICATION-&gt;</nobr><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a> и вернуть
 * <i>false</i>.</p>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?<br>AddEventHandler("main", "OnBeforeGroupAdd", "MyOnBeforeGroupAdd");<br>function MyOnBeforeGroupAdd(&amp;$arFields)<br>{<br>	if($arFields["DESCRIPTION"] == '')<br>		$arFields["DESCRIPTION"] = "Описание группы";<br>}<br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/index.php">CGroup</a> </li>  <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li> 
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforegroupadd.php
 * @author Bitrix
 */
	public static function OnBeforeGroupAdd(&$arFields){}

/**
 * Событие "OnBeforeGroupDelete" вызывается перед <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/delete.php">удалением группы пользователей</a>. Как правило задачи обработчика данного события - разрешить или запретить удаление группы пользователей.
 *
 *
 * @param int $group_id  ID удаляемой группы пользователей.
 *
 * @return bool <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/delete.php">CGroup::Delete</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/php_interface/init.php
 * // регистрируем обработчик
 * AddEventHandler("main", "<b>OnBeforeGroupDelete</b>", Array("MyClass", "OnBeforeGroupDeleteHandler"));<br>class MyClass
 * {
 *     // создаем обработчик события "OnBeforeGroupDelete"
 *     function OnBeforeGroupDeleteHandler($group_id)
 *     {
 *         // проверим есть ли связанные с удаляемой группой пользователей записи
 *         $strSql = "SELECT * FROM my_table WHERE GROUP_ID=".intval($group_id);
 *         $rs = $DB-&gt;Query($strSql, false, "FILE: ".__FILE__."&lt;br&gt;LINE: ".__LINE__);
 * 
 *         // если связанные записи есть то
 *         if ($ar = $rs-&gt;Fetch()) 
 *         {
 *             // запретим удаление группы пользователей
 *             global $APPLICATION;
 *             $APPLICATION-&gt;throwException("В моей таблице есть связанные записи.");
 *             return false;
 *         }
 *     }
 * }
 * ?&gt;
 * &lt;?
 * // регистрируем обработчик события "OnBeforeGroupDelete" для модуля my_module_id
 * RegisterModuleDependences("main", "<b>OnBeforeGroupDelete</b>", 
 *      "my_module_id", "MyClass", "OnBeforeGroupDeleteHandler");
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/ongroupdelete.php">Событие
 * "OnGroupDelete"</a></li> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/delete.php">CGroup::Delete</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforegroupdelete.php
 * @author Bitrix
 */
	public static function OnBeforeGroupDelete($group_id){}

/**
 * <p>Событие вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/update.php">CGroup::Update</a> до изменения полей группы,  и может быть использовано для отмены изменения или переопределения некоторых полей.</p>
 *
 *
 * @param int $intID  Идентификатор изменяемой группы пользователей.
 *
 * @param array &$arFields  Список полей (<a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/index.php">класс
 * CGroup</a>) изменяемой группы пользователей.
 *
 * @return bool <p>Для отмены изменения и прекращении выполнения метода <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/update.php">CGroup::Update</a> необходимо в
 * функции-обработчике создать исключение методом
 * <nobr>$APPLICATION-&gt;</nobr><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a> и вернуть
 * <i>false</i>.</p>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?<br>AddEventHandler("main", "OnBeforeGroupUpdate", "MyOnBeforeGroupUpdate");<br>function MyOnBeforeGroupUpdate($ID, &amp;$arFields)<br>{<br>	if($ID == 1)<br>		$arFields["DESCRIPTION"] = "Главная группа админов.";<br>}<br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/index.php">CGroup</a> </li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li> 
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforegroupupdate.php
 * @author Bitrix
 */
	public static function OnBeforeGroupUpdate($intID, &$arFields){}

/**
 * Событие "OnBeforeLanguageDelete" вызывается перед <a href="http://dev.1c-bitrix.ru/api_help/main/reference/clanguage/delete.php">удалением языка</a>. Как правило задачи обработчика данного события - разрешить или запретить удаление языка.
 *
 *
 * @param string $language_id  ID удаляемого языка.
 *
 * @return bool <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/clanguage/delete.php">CLanguage::Delete</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/modules/my_module_id/include.php
 * class MyClass
 * {
 *     // создаем обработчик события "OnBeforeLanguageDelete"
 *     function OnBeforeLanguageDeleteHandler($language_id)
 *     {
 *         // проверим есть ли связанные с удаляемым языком записи
 *         $strSql = "SELECT * FROM my_table WHERE LANGUAGE_ID=".$DB-&gt;ForSql($language_id);
 *         $rs = $DB-&gt;Query($strSql, false, "FILE: ".__FILE__."&lt;br&gt;LINE: ".__LINE__);
 * 
 *         // если связанные записи есть то
 *         if ($ar = $rs-&gt;Fetch()) 
 *         {
 *             // запретим удаление языка
 *             global $APPLICATION;
 *             $APPLICATION-&gt;throwException("В моей таблице есть связанные записи.");
 *             return false;
 *         }
 *     }
 * }
 * ?&gt;
 * &lt;?
 * // регистрируем обработчик события "OnBeforeLanguageDelete"
 * RegisterModuleDependences("main", "<b>OnBeforeLanguageDelete</b>", 
 *          "my_module_id", "MyClass", "OnBeforeLanguageDeleteHandler");
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onlanguagedelete.php">Событие
 * "OnLanguageDelete"</a></li> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/clanguage/delete.php">CLanguage::Delete</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li> </ul>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforelanguagedelete.php
 * @author Bitrix
 */
	public static function OnBeforeLanguageDelete($language_id){}

/**
 * Событие "OnBeforeSiteDelete" вызывается перед <a href="http://dev.1c-bitrix.ru/api_help/main/reference/csite/delete.php">удалением сайта</a>. Как правило задачи обработчика данного события - разрешить или запретить удаление сайта.
 *
 *
 * @param string $site_id  ID удаляемого сайта.
 *
 * @return bool <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/delete.php">CSite::Delete</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/modules/my_module_id/include.php
 * class MyClass
 * {
 *     // создаем обработчик события "OnBeforeSiteDelete"
 *     function OnBeforeSiteDeleteHandler($site_id)
 *     {
 *         // проверим есть ли связанные с удаляемым сайтом записи
 *         $strSql = "SELECT * FROM my_table WHERE SITE_ID=".$DB-&gt;ForSql($site_id);
 *         $rs = $DB-&gt;Query($strSql, false, "FILE: ".__FILE__."&lt;br&gt;LINE: ".__LINE__);
 * 
 *         // если связанные записи есть то
 *         if ($ar = $rs-&gt;Fetch()) 
 *         {
 *             // запретим удаление сайта
 *             global $APPLICATION;
 *             $APPLICATION-&gt;throwException("В моей таблице есть связанные записи.");
 *             return false;
 *         }
 *     }
 * }
 * ?&gt;
 * &lt;?
 * // регистрируем обработчик события "OnBeforeSiteDelete"
 * RegisterModuleDependences("main", "<b>OnBeforeSiteDelete</b>", 
 *       "my_module_id", "MyClass", "OnBeforeSiteDeleteHandler");
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onsitedelete.php">Событие "OnSiteDelete"</a></li>
 * <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/csite/delete.php">CSite::Delete</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforesitedelete.php
 * @author Bitrix
 */
	public static function OnBeforeSiteDelete($site_id){}

/**
 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/add.php">CUser::Add</a> до вставки нового пользователя, и может быть использовано для отмены вставки или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php#fuser">Массив полей</a> нового
 * пользователя.
 *
 * @return bool <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/add.php">CUser::Add</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/php_interface/init.php
 * // регистрируем обработчик
 * AddEventHandler("main", "<b>OnBeforeUserAdd</b>", Array("MyClass", "OnBeforeUserAddHandler"));<br>
 * class MyClass
 * {
 *     // создаем обработчик события "OnBeforeUserAdd"
 *     function OnBeforeUserAddHandler(&amp;$arFields)
 *     {
 *         if(strlen($arFields["LAST_NAME"])&lt;=0)
 *         {
 *             global $APPLICATION;
 *             $APPLICATION-&gt;throwException("Пожалуйста, введите фамилию.");
 *             return false;
 *         }
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onafteruseradd.php">Событие
 * "OnAfterUserAdd"</a></li>   <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/add.php">CUser::Add</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuseradd.php
 * @author Bitrix
 */
	public static function OnBeforeUserAdd(&$arParams){}

/**
 * Событие "OnBeforeUserDelete" вызывается перед <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/delete.php">удалением пользователя</a>. Как правило задачи обработчика данного события - разрешить или запретить удаление пользователя.
 *
 *
 * @param int $user_id  ID удаляемого пользователя.
 *
 * @return bool <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/delete.php">CUser::Delete</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/php_interface/init.php
 * // регистрируем обработчик
 * AddEventHandler("main", "<b>OnBeforeUserDelete</b>", Array("MyClass", "OnBeforeUserDeleteHandler"));<br>class MyClass
 * {
 *     // создаем обработчик события "<b>OnBeforeUserDelete</b>"
 *     function OnBeforeUserDeleteHandler($user_id)
 *     {
 *         // проверим есть ли связанные с удаляемым пользователем записи
 *         $strSql = "SELECT * FROM my_table WHERE USER_ID=".intval($user_id);
 *         $rs = $DB-&gt;Query($strSql, false, "FILE: ".__FILE__."&lt;br&gt;LINE: ".__LINE__);
 * 
 *         // если связанные записи есть то
 *         if ($ar = $rs-&gt;Fetch()) 
 *         {
 *             // запретим удаление пользователя
 *             global $APPLICATION;
 *             $APPLICATION-&gt;throwException("В моей таблице есть связанные записи.");
 *             return false;
 *         }
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onuserdelete.php">Событие "OnUserDelete"</a></li>
 * <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/delete.php">CUser::Delete</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserdelete.php
 * @author Bitrix
 */
	public static function OnBeforeUserDelete($user_id){}

/**
 * Событие "OnBeforeUserLogin" вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/login.php">CUser::Login</a> до проверки имени входа <i><span class="syntax"><i>arParams</i></span>['LOGIN']</i> и пароля <i><span class="syntax"><i>arParams</i></span>['PASSWORD'] </i> и попытки авторизовать пользователя, и может быть использовано для прекращения процесса проверки или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  Массив полей для проверки имени входа и пароля: 	      <ul> <li> <b>LOGIN</b> -
 * Логин пользователя</li> 	      <li> <b>PASSWORD</b> - Пароль. Если параметр
 * <b>PASSWORD_ORIGINAL</b> равен"Y", то в данном параметре был передан
 * оригинальный пароль, в противном случае был передан хеш (md5) от
 * оригинального пароля. </li>           <li> <b>REMEMBER</b> - Если значение равно
 * "Y", то авторизация пользователя должна быть сохранена в куках.</li>  
 *         <li> <b>PASSWORD_ORIGINAL</b> - Если значение равно "Y", то это означает что
 * <b>PASSWORD</b> не был сконвертирован в MD5 (т.е. в параметре <b>PASSWORD</b> был
 * передан реальный пароль вводимый пользователем с клавиатуры),
 * если значение равно "N", то это означает что <b>PASSWORD</b> уже
 * сконвертирован в MD5.</li>         </ul>
 *
 * @return bool <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/login.php">CUser::Login</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/php_interface/init.php
 * // регистрируем обработчик
 * AddEventHandler("main", "<b>OnBeforeUserLogin</b>", Array("MyClass", "OnBeforeUserLoginHandler"));<br>
 * class MyClass
 * {
 *     // создаем обработчик события "OnBeforeUserLogin"
 *     function OnBeforeUserLoginHandler(&amp;$arFields)
 *     {
 *         // здесь выполняем любые действия связанные 
 *         if(strtolower($arFields["LOGIN"])=="guest")
 *         {
 *             global $APPLICATION;
 *             $APPLICATION-&gt;throwException("Пользователь с именем входа Guest не может быть авторизован.");
 *             return false;
 *         }
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onafteruserlogin.php">Событие
 * "OnAfterUserLogin"</a></li>   <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/login.php">CUser::Login</a>  </li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li> <li>
 * <a href="http://dev.1c-bitrix.ru/learning/course/index.php?&amp;COURSE_ID=43&amp;LESSON_ID=3574" ></a>Внешняя
 * авторизация </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserlogin.php
 * @author Bitrix
 */
	public static function OnBeforeUserLogin(&$arParams){}

/**
 * <p>Событие "OnBeforeUserLoginByHash" вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/loginbyhash.php">CUser::LoginByHash()</a> до проверки имени входа <i><span class="syntax"><i>arParams</i><b></b></span>['LOGIN']</i>,  хеша от пароля <i><span class="syntax"><i>arParams</i><b></b></span>['HASH']</i> и попытки авторизовать пользователя.      </p>
 *
 *
 * @param array &$arParams  Массив полей для проверки имени входа и пароля:       <ul> <li> <b>LOGIN</b> -
 * Имя входа  пользователя.</li>         <li> <b>HASH</b> - Специальный хеш от
 * пароля пользователя. Данный хеш как правило хранится в куках
 * пользователя.</li>       </ul>
 *
 * @return bool <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/loginbyhash.php">CUser::LoginByHash</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/php_interface/init.php
 * // регистрируем обработчик
 * AddEventHandler("main", "<b>OnBeforeUserLoginByHash</b>", Array("MyClass", "OnBeforeUserLoginByHashHandler"));<br>
 * class MyClass
 * {
 *     // создаем обработчик события "OnBeforeUserLoginByHash"
 *     function OnBeforeUserLoginByHashHandler(&amp;$arParams)
 *     {
 *         // здесь выполняем любые действия связанные 
 *         if(strtolower($arParams['LOGIN'])=="guest")
 *         {
 *             global $APPLICATION;
 *             $APPLICATION-&gt;throwException("Пользователь с именем входа Guest не может авторизоваться по хешу.");
 *             return false;
 *         }
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onafteruserloginbyhash.php">Событие
 * "OnAfterUserLoginByHash"</a></li> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/loginbyhash.php">CUser::LoginByHash</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li> <li>
 * <a href="http://dev.1c-bitrix.ru/learning/course/index.php?&amp;COURSE_ID=43&amp;LESSON_ID=3574" ></a>Внешняя
 * авторизация </li>   </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserloginbyhash.php
 * @author Bitrix
 */
	public static function OnBeforeUserLoginByHash(&$arParams){}

/**
 * <p>Вызывается перед завершением сеанса авторизации пользователя методом <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/logout.php">CUser::Logout</a> и может быть использовано для отмены завершения сеанса. </p>
 *
 *
 * @param array &$arParams  Массив параметров: 	  <ul> <li> <b>USER_ID</b> - код пользователя</li> 	</ul>
 *
 * @return bool 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/php_interface/init.php
 * // регистрируем обработчик
 * AddEventHandler("main", "<b>OnBeforeUserLogout</b>", Array("MyClass", "OnBeforeUserLogoutHandler"));<br>class MyClass
 * {
 *     function OnBeforeUserLogoutHandler($arParams)
 *     {
 *         if($arParams['ID']==10)
 *             return false;
 *     }
 * }
 * ?&gt;
 * &lt;?
 * // регистрируем обработчик события "OnBeforeUserLogout"
 * RegisterModuleDependences("main", "<b>OnBeforeUserLogout</b>", 
 * "my_module_id", "MyClass", "OnBeforeUserLogoutHandler");?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onafteruserlogout.php">Событие
 * "OnAfterUserLogout"</a></li> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/logout.php">CUser::Logout</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserlogout.php
 * @author Bitrix
 */
	public static function OnBeforeUserLogout(&$arParams){}

/**
 * Событие "OnBeforeUserRegister" вызывается до попытки регистрации нового пользователя методом <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/register.php">CUser::Register</a> и может быть использовано для прекращения процесса регистрации или переопределения некоторых полей.  <p class="note"><b>Примечание</b>: функция будет вызываться также при подтверждении регистрации (событие <a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserupdate.php">OnBeforeUserUpdate</a>), где ключа LOGIN нет.</p>
 *
 *
 * @param array &$arArgs  Массив полей регистрации нового пользователя: 	         <ul> <li> <b>LOGIN</b> -
 * имя входа пользователя 		</li>                    <li> <b>NAME</b> - имя пользователя
 * 		</li>                    <li> <b>LAST_NAME</b> - фамилия пользователя 		</li>                   
 * <li> <b>PASSWORD</b> - пароль 		</li>                    <li> <b>CONFIRM_PASSWORD</b> - подтверждение
 * пароля 		</li>                    <li> <b>CHECKWORD</b> - новое контрольное слово для
 * смены пароля 		</li>                    <li> <b>EMAIL</b> - EMail пользователя 		</li>           
 *         <li> <b>ACTIVE</b> - флаг активности [Y|N] 		</li>                    <li> <b>SITE_ID</b> - ID
 * сайта по умолчанию для уведомлений 		</li>                    <li> <b>GROUP_ID</b> -
 * массив ID групп пользователя 	 </li>                    <li> <b>USER_IP</b> - IP адресс
 * пользователя 	 </li>                    <li> <b>USER_HOST</b> - хост пользователя 	</li>    
 *     </ul>        На основании массива полей происходит добавление
 * пользователя и отсылка почтового события NEW_USER.
 *
 * @return bool <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/register.php">CUser::Register</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/modules/my_module_id/include.php
 * class MyClass
 * {
 *     // создаем обработчик события "OnBeforeUserRegister"
 *     function OnBeforeUserRegisterHandler(&amp;$arFields)
 *     {
 *         // если пользователь пришел по рекламной кампании #34, то
 *         if ($_SESSION["SESS_LAST_ADV_ID"]==34)
 *         {
 *             // добавляем его в группу #3
 *             $arFields["GROUP_ID"][] = 3;    
 * 
 *             // добавим административный комментарий
 *             if (intval($_SESSION["SESS_ADV_ID"])&gt;0)
 *                 $arFields["ADMIN_NOTES"] = "Рекламная кампания #34 - прямой заход";
 *             else
 *                 $arFields["ADMIN_NOTES"] = "Рекламная кампания #34 - возврат";
 * 
 *             $arFields["SITE_ID"] = "ru";
 *         }
 *     }
 * }
 * ?&gt;&lt;?
 * // регистрируем обработчик события "OnBeforeUserRegister"
 * RegisterModuleDependences("main", "<b>OnBeforeUserRegister</b>", "my_module_id", "MyClass", "OnBeforeUserRegisterHandler");
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onafteruserregister.php">Событие
 * "OnAfterUserRegister"</a></li>    <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >События</a>  </li>  
 * <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/register.php">CUser::Register</a> </li>  </ul><a
 * name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserregister.php
 * @author Bitrix
 */
	public static function OnBeforeUserRegister(&$arArgs){}

/**
 * Событие "OnBeforeUserSimpleRegister" вызывается до попытки упрощённой регистрации нового пользователя методом <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/simpleregister.php">CUser::SimpleRegister</a>  и может быть использовано для прекращения процесса регистрации или переопределения некоторых полей.
 *
 *
 * @param array &$arFields  Массив полей упрощённой регистрации нового пользователя:<ul> <li>
 * <b>PASSWORD</b> - пароль 		</li> <li> <b>CONFIRM_PASSWORD</b> - подтверждение пароля 		</li>
 * <li> <b>CHECKWORD</b> - контрольное слово для смены пароля 		</li> <li> <b>EMAIL</b> -
 * EMail пользователя 		</li> <li> <b>ACTIVE</b> - флаг активности [Y|N] 		</li> <li>
 * <b>SITE_ID</b> - ID сайта по умолчанию для уведомлений 		</li> <li> <b>GROUP_ID</b> -
 * массив ID групп пользователя         	    </li> <li> <b>USER_IP</b> - IP адрес
 * пользователя          	    </li> <li> <b>USER_HOST</b> - хост пользователя         </li>
 * </ul> 	На основании массива полей происходит добавление
 * пользователя и отсылка почтового события NEW_USER.
 *
 * @return bool <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/simpleregister.php">CUser::SimpleRegister</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/php_interface/init.php
 * AddEventHandler(
 *     "main", 
 *     "<b>OnBeforeUserSimpleRegister</b>", 
 *     Array("MyClass", "OnBeforeUserSimpleRegisterHandler"), 
 *     100, 
 *     $_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/scripts/onbeforeusersimplereg.php"
 * );
 * ?&gt;
 * &lt;?
 * // файл /bitrix/php_interface/scripts/onbeforeusersimplereg.php
 * class MyClass
 * {
 *     // создаем обработчик события "OnBeforeUserSimpleRegister"
 *     function OnBeforeUserSimpleRegisterHandler(&amp;$arFields)
 *     {
 *         if (strpos($arFields["EMAIL"], "@mysite.com")===false)
 *         {
 *             global $APPLICATION;
 *             $APPLICATION-&gt;ThrowException("Регистрация возможно только для EMail адресов домена mysite.com");
 *             return false;
 *         }
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/events/onafterusersimpleregister.php">Событие
 * "OnAfterUserSimpleRegister"</a> </li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li> <li>
 * <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/register.php">CUser::Register</a> </li> </ul><a
 * name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforeusersimpleregister.php
 * @author Bitrix
 */
	public static function OnBeforeUserSimpleRegister(&$arFields){}

/**
 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/update.php">CUser::Update</a> до изменения параметров пользователя, и может быть использовано для отмены изменения или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php#fuser">Массив полей</a>
 * изменяемого пользователя.
 *
 * @return bool <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/update.php">CUser::Update</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/php_interface/init.php
 * // регистрируем обработчик
 * AddEventHandler("main", "<b>OnBeforeUserUpdate</b>", Array("MyClass", "OnBeforeUserUpdateHandler"));<br>
 * class MyClass
 * {
 *     // создаем обработчик события "OnBeforeUserUpdate"
 *     function OnBeforeUserUpdateHandler(&amp;$arFields)
 *     {
 *         if(is_set($arFields, "LAST_NAME") &amp;&amp; strlen($arFields["LAST_NAME"])&lt;=0)
 *         {
 *             global $APPLICATION;
 *             $APPLICATION-&gt;throwException("Пожалуйста, введите фамилию. (ID:".$arFields["ID"].")");
 *             return false;
 *         }
 *     }
 * }
 * ?&gt;Смотрите также<li><a href="http://dev.1c-bitrix.ru/community/webdev/user/11948/blog/11321/">Как измененить пароль пользователя с подтверждением старого</a></li>
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/events/onafteruserupdate.php">Событие
 * "OnAfterUserUpdate"</a>   </li> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/update.php">CUser::Update</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserupdate.php
 * @author Bitrix
 */
	public static function OnBeforeUserUpdate(&$arParams){}

/**
 * 
 *
 *
 * @return mixed <p></p>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * function OnBuildGlobalMenu(&amp;$aGlobalMenu, &amp;$aModuleMenu)
 *     {
 *      global $USER;
 *      if(!$USER-&gt;IsAdmin())
 *       return;
 * 
 *      $aMenu = array(
 *       "parent_menu" =&gt; "global_menu_content",
 *       "section" =&gt; "clouds",
 *       "sort" =&gt; 150,
 *       "text" =&gt; GetMessage("CLO_STORAGE_MENU"),
 *       "title" =&gt; GetMessage("CLO_STORAGE_TITLE"),
 *       "url" =&gt; "clouds_index.php?lang=".LANGUAGE_ID,
 *       "icon" =&gt; "clouds_menu_icon",
 *       "page_icon" =&gt; "clouds_page_icon",
 *       "items_id" =&gt; "menu_clouds",
 *       "more_url" =&gt; array(
 *           "clouds_index.php",
 *       ),
 *       "items" =&gt; array()
 *      );
 * 
 *      $rsBuckets = CCloudStorageBucket::GetList(array("SORT"=&gt;"DESC", "ID"=&gt;"ASC"));
 *      while($arBucket = $rsBuckets-&gt;Fetch())
 *       $aMenu["items"][] = array(
 *           "text" =&gt; $arBucket["BUCKET"],
 *           "url" =&gt; "clouds_file_list.php?lang=".LANGUAGE_ID."&amp;bucket=".$arBucket["ID"]."&amp;path=/",
 *           "more_url" =&gt; array(
 *            "clouds_file_list.php?bucket=".$arBucket["ID"],
 *           ),
 *           "title" =&gt; "",
 *           "page_icon" =&gt; "clouds_page_icon",
 *           "items_id" =&gt; "menu_clouds_bucket_".$arBucket["ID"],
 *           "module_id" =&gt; "clouds",
 *           "items" =&gt; array()
 *       );
 * 
 *      if(!empty($aMenu["items"]))
 *       $aModuleMenu[] = $aMenu;
 *     }
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <p></p><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onbuildglobalmenu.php
 * @author Bitrix
 */
	public static function OnBuildGlobalMenu(){}

/**
 * Событие "OnChangePermissions" вызывается при изменении прав доступа  к файлу или папке методом <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/setfileaccesspermission.php">$APPLICATION-&gt;SetFileAccessPermission</a>.
 *
 *
 * @param array $site_path  Массив вида: array("идентификатор сайта", "путь к файлу относительно
 * корня этого сайта").
 *
 * @param array $permissions  Массив прав доступа.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/modules/search/classes/mysql/search.php
 * class CSearch extends CAllSearch
 * {
 *     // создаем обработчик события "OnChangePermissions"
 *     function OnChangeFilePermissions($path, $permission)
 *     {
 *         CMain::InitPathVars($site, $path);
 *         $DOC_ROOT = CSite::GetSiteDocRoot($site);
 * 
 *         while(strlen($path)&gt;0 &amp;&amp; $path[strlen($path)-1]=="/") //отрежем / в конце, если есть
 *             $path=substr($path, 0, strlen($path)-1);
 * 
 *         global $APPLICATION, $DB;
 *         if(file_exists($DOC_ROOT.$path))
 *         {
 *             @set_time_limit(300);
 *             $arGroups = CSearch::GetGroupCached();
 *             if(is_dir($DOC_ROOT.$path))
 *             {
 *                 $handle  = @opendir($DOC_ROOT.$path);
 *                 while($file = @readdir($handle))
 *                 {
 *                     if($file == "." || $file == "..") continue;
 *                     if(is_dir($DOC_ROOT.$path."/".$file))
 *                     {
 *                         if($path."/".$file=="/bitrix")
 *                             continue;
 *                     }
 *                     CSearch::OnChangeFilePermissions(Array($site, $path."/".$file), Array());
 *                 }
 *             }
 *             else //if(is_dir($DOCUMENT_ROOT.$path))
 *             {
 *                 $strGPerm = "0";
 *                 for($i=0; $i&lt;count($arGroups); $i++)
 *                 {
 *                     if($arGroups[$i]&gt;1)
 *                     {
 *                         $p = $APPLICATION-&gt;GetFileAccessPermission(Array($site, $path), Array($arGroups[$i]));
 *                         if($p&gt;="R")
 *                         {
 *                             $strGPerm .= ",".$arGroups[$i];
 *                             if($arGroups[$i]==2) break;
 *                         }
 *                     }
 *                 }
 * 
 *                 $r = $DB-&gt;Query("SELECT ID FROM b_search_content WHERE MODULE_ID='main' AND ITEM_ID='".$site."|".$path."'");
 *                 while($arR = $r-&gt;Fetch())
 *                     $DB-&gt;Query("DELETE FROM b_search_content_group WHERE SEARCH_CONTENT_ID=".$arR["ID"]);
 * 
 *                 $strSql =
 *                     "INSERT INTO b_search_content_group(SEARCH_CONTENT_ID, GROUP_ID) ".
 *                     "SELECT S.ID, G.ID ".
 *                     "FROM b_search_content S, b_group G ".
 *                     "WHERE MODULE_ID='main' ".
 *                     "    AND ITEM_ID='".$site."|".$path."' ".
 *                     "    AND G.ID IN (".$strGPerm.") ";
 * 
 *                 $DB-&gt;Query($strSql);
 *             } //if(is_dir($DOCUMENT_ROOT.$path))
 *         }//if(file_exists($DOCUMENT_ROOT.$path))
 *     }
 * }
 * ?&gt;
 * &lt;?
 * // регистрируем обработчик события "OnChangePermissions"
 * RegisterModuleDependences("main", "<b>OnChangePermissions</b>", "search", "CSearch", "OnChangeFilePermissions");
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforechangefile.php">Событие
 * "OnChangeFile"</a> </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/setfileaccesspermission.php">CMain::SetFileAccessPermission</a>
 * </li> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493"
 * ></a>События</li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onchangepermissions.php
 * @author Bitrix
 */
	public static function OnChangePermissions($site_path, $permissions){}

/**
 * Событие "OnEventMessageDelete" вызывается во время <a href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/delete.php">удаления почтового шаблона</a>. Как правило задачи обработчика данного события - очистить базу данных от записей связанных с удаляемым почтовым шаблоном.
 *
 *
 * @param int $template_id  ID удаляемого почтового шаблона.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/modules/my_module_id/include.php
 * class MyClass
 * {
 *     // создаем обработчик события "OnEventMessageDelete"
 *     function OnEventMessageDeleteHandler($template_id)
 *     {
 *         // удалим связанные записи
 *         $strSql = "DELETE FROM my_table WHERE TEMPLATE_ID=".intval($template_id);
 *         $rs = $DB-&gt;Query($strSql, false, "FILE: ".__FILE__."&lt;br&gt;LINE: ".__LINE__);
 *     }
 * }
 * ?&gt;
 * &lt;?
 * // регистрируем обработчик события "OnEventMessageDelete"
 * RegisterModuleDependences("main", "<b>OnEventMessageDelete</b>", 
 *           "my_module_id", "MyClass", "OnEventMessageDeleteHandler");
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeeventmessagedelete.php">Событие
 * "OnBeforeEventMessageDelete"</a></li> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/delete.php">CEventMessage::Delete</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/oneventmessagedelete.php
 * @author Bitrix
 */
	public static function OnEventMessageDelete($template_id){}

/**
 * Событие "OnExternalAuthList" вызывается для получения списка источников внешней авторизации<a href="http://dev.1c-bitrix.ru/api_help/main/reference/clanguage/delete.php"></a> при вызове метода <a href="../cuser/getexternalauthlist.php.html">CUser::GetExternalAuthList</a>.
 *
 *
 * @return array <code>Array(Array("ID"=&gt;"Код источника 1", "NAME"=&gt;"Название источника 1"),
 * ...)</code><br>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * AddEventHandler("main", "OnExternalAuthList", Array("__IPBAuth", "OnExternalAuthList"));<br>class __IPBAuth<br>{<br>  function OnExternalAuthList()<br>  {<br>     return Array(
 *       Array("ID"=&gt;"IPB", "NAME"=&gt;"Invision Power Board")<br>      );<br> }<br>}
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493"
 * ></a>События</li> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/getexternalauthlist.php">CUser::GetExternalAuthList
 * </a></li> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?&amp;COURSE_ID=43&amp;LESSON_ID=3574"
 * ></a>Внешняя авторизация </li>     </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onexternalauthlist.php
 * @author Bitrix
 */
	public static function OnExternalAuthList(){}

/**
 * <p>Событие "OnFileDelete" вызывается после удаления файла в методе <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cfile/delete.php">CFile::Delete</a>. Событие может использоваться для удаления производной от файла информации (созданных при загрузке картинки эскизов и т.п.).</p>
 *
 *
 * @param array $arFile  Массив с информацией об удаленном файле, содержащий ключи:         <br>
 *  SUBDIR - подпапка в папке для загрузки файлов (обычно в /upload);         <br> 
 * FILE_NAME - имя удаленного файла.         <br>
 *
 * @return void <p>Не используется.</p>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?<br>AddEventHandler("main", "OnFileDelete", "MyOnFileDelete");<br>function MyOnFileDelete($arFile)<br>{<br>	$fname = $_SERVER["DOCUMENT_ROOT"]."/upload/resize/".$arFile["SUBDIR"]."/small_".$arFile["FILE_NAME"];<br>	if(file_exists($fname))<br>		unlink($fname);<br><br>}<br><br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493"
 * ></a>События</li>  </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onfiledelete.php
 * @author Bitrix
 */
	public static function OnFileDelete($arFile){}

/**
 * Событие "OnGroupDelete" вызывается в момент <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/delete.php">удаления группы пользователей</a>. Как правило задачи обработчика данного события - очистить базу данных от записей связанных с удаляемой группой пользователей.
 *
 *
 * @param int $group_id  ID удаляемой группы пользователей.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/php_interface/init.php
 * AddEventHandler("main", "<b>OnGroupDelete</b>", Array("MyClass", "OnGroupDeleteHandler"));<br>class MyClass
 * {
 *     // создаем обработчик события "OnGroupDelete"
 *     function OnGroupDeleteHandler($group_id)
 *     {
 *         // удалим связанные записи
 *         $strSql = "DELETE FROM my_table WHERE GROUP_ID=".intval($group_id);
 *         $rs = $DB-&gt;Query($strSql, false, "FILE: ".__FILE__."&lt;br&gt;LINE: ".__LINE__);
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforegroupdelete.php">Событие
 * "OnBeforeGroupDelete"</a></li> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cgroup/delete.php">CGroup::Delete</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/ongroupdelete.php
 * @author Bitrix
 */
	public static function OnGroupDelete($group_id){}

/**
 * Событие "OnLanguageDelete" вызывается во время <a href="http://dev.1c-bitrix.ru/api_help/main/reference/clanguage/delete.php">удаления языка</a>. Как правило задачи обработчика данного события - очистить базу данных от записей связанных с удаляемым языком.
 *
 *
 * @param int $language_id  ID удаляемого языка.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/modules/my_module_id/include.php
 * class MyClass
 * {
 *     // создаем обработчик события "OnLanguageDelete"
 *     function OnLanguageDeleteHandler($language_id)
 *     {
 *         // удалим связанные записи
 *         $strSql = "DELETE FROM my_table WHERE LANGUAGE_ID=".$DB-&gt;ForSql($language_id);
 *         $rs = $DB-&gt;Query($strSql, false, "FILE: ".__FILE__."&lt;br&gt;LINE: ".__LINE__);
 *     }
 * }
 * ?&gt;
 * &lt;?
 * // регистрируем обработчик события "OnLanguageDelete"
 * RegisterModuleDependences("main", "<b>OnLanguageDelete</b>", 
 *             "my_module_id", "MyClass", "OnLanguageDeleteHandler");
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforelanguagedelete.php">Событие
 * "OnBeforeLanguageDelete"</a></li> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/clanguage/delete.php">CLanguage::Delete</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onlanguagedelete.php
 * @author Bitrix
 */
	public static function OnLanguageDelete($language_id){}

/**
 * Событие "OnPanelCreate" вызывается в момент сбора данных для <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/showpanel.php">построения</a> <a href="http://dev.1c-bitrix.ru/api_help/main/general/panel.php">панели управления</a> в публичной части сайта. Как правило задачи обработчика данного события - добавлять свои кнопки в панель управления сайтом.
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/php_interface/init.php
 * // регистрируем обработчик
 * AddEventHandler("main", "<b>OnPanelCreate</b>", Array("MyClass", "OnPanelCreateHandler"));<br>class MyClass
 * {
 *     // добавим кнопку в панель управления
 *     function OnPanelCreateHandler()
 *     {
 *         global $APPLICATION;
 *         $APPLICATION-&gt;AddPanelButton(array(
 *             "HREF"      =&gt; "/bitrix/admin/my_page.php", // ссылка на кнопке
 *             "SRC"       =&gt; "/bitrix/images/my_module_id/button_image.gif", // картинка на кнопке
 *             "ALT"       =&gt; "Текст всплывающей подсказки на кнопке", 
 *             "MAIN_SORT" =&gt; 300, 
 *             "SORT"      =&gt; 10
 *             ));
 *     }
 *     //<
 *     Теперь при выводе панели управления в публичной части сайта
 *     будет также всегда выводиться наша кнопка
 *     >//
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/general/panel.php">Панель управления</a></li>
 * <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/addpanelbutton.php">CMain::AddPanelButton</a></li>
 * <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/showpanel.php">CMain::ShowPanel</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onpanelcreate.php
 * @author Bitrix
 */
	public static function OnPanelCreate(){}

/**
 * Событие "OnSendUserInfo" вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/senduserinfo.php">CUser::SendUserInfo</a> и предназначено для возможности переопределения параметров для <a href="http://dev.1c-bitrix.ru/api_help/main/general/mailevents.php">отправки почтового события</a> USER_INFO.
 *
 *
 * @param array &$arParams  Массив полей для проверки имени входа и пароля:          <ul> <li> <b>FIELDS</b>
 * - Массив, содержащий набор полей вида Array("поле 1"=&gt;"значение 1", ...).
 * При отправке все поля передаются в обработку шаблона USER_INFO.
 * Содержит по умолчанию след. поля:</li>                     <ul> <li>"USER_ID" - код
 * пользователя, </li>                         <li>"STATUS" - текст статуса активности,
 * </li>                         <li>"MESSAGE" - текст сообщения, </li>                         <li>"LOGIN" -
 * имя входа, </li>                         <li>"CHECKWORD" - контрольная строка, </li>           
 *              <li>"NAME" - имя пользователя, </li>                         <li>"LAST_NAME" -
 * фамилия, </li>                         <li>"EMAIL" - E-Mail адрес 		</li>            </ul> <li>
 * <b>USER_FIELDS</b> - Все <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/index.php">поля
 * пользователя</a>, информация о котором будет высылаться. </li>             
 *        <li> <b>SITE_ID</b> - код сайта, используется для определения шаблона
 * почтового события USER_INFO.</li>          </ul>
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;? <br>// файл /bitrix/php_interface/init.php <br>AddEventHandler("main", "OnSendUserInfo", "MyOnSendUserInfoHandler"); <br>function MyOnSendUserInfoHandler(&amp;$arParams) <br>{ <br>   if(strlen($arParams['USER_FIELDS']['LAST_NAME'])&lt;=0) <br>       $arParams['FIELDS']['CUSTOM_NAME'] = $arParams['USER_FIELDS']['LAST_NAME']; <br>   else <br>       $arParams['FIELDS']['CUSTOM_NAME'] = $arParams['USER_FIELDS']['LOGIN']; <br>   // теперь в шаблоне USER_INFO можно использовать макрос #CUSTOM_NAME# <br>} <br>? &gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/senduserinfo.php">CUser::SendUserInfo</a> </li>
 *     <li> <a href="http://dev.1c-bitrix.ru/api_help/main/general/mailevents.php">Почтовые события</a>
 * </li>   <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493"
 * ></a>События</li>  </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onsenduserinfo.php
 * @author Bitrix
 */
	public static function OnSendUserInfo(&$arParams){}

/**
 * Событие "OnSiteDelete" вызывается во время <a href="http://dev.1c-bitrix.ru/api_help/main/reference/csite/delete.php">удаления сайта</a>. Как правило задачи обработчика данного события - очистить базу данных от записей связанных с удаляемым сайтом.
 *
 *
 * @param int $site_id  ID удаляемого сайта.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/modules/my_module_id/include.php
 * class MyClass
 * {
 *     // создаем обработчик события "OnSiteDelete"
 *     function OnSiteDeleteHandler($site_id)
 *     {
 *         // удалим связанные записи
 *         $strSql = "DELETE FROM my_table WHERE SITE_ID=".$DB-&gt;ForSql($site_id);
 *         $rs = $DB-&gt;Query($strSql, false, "FILE: ".__FILE__."&lt;br&gt;LINE: ".__LINE__);
 *     }
 * }
 * ?&gt;
 * &lt;?
 * // регистрируем обработчик события "OnSiteDelete"
 * RegisterModuleDependences("main", "<b>OnSiteDelete</b>", 
 *        "my_module_id", "MyClass", "OnSiteDeleteHandler");
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforesitedelete.php">Событие
 * "OnBeforeSiteDelete"</a></li> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/csite/delete.php">CSite::Delete</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li> </ul>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onsitedelete.php
 * @author Bitrix
 */
	public static function OnSiteDelete($site_id){}

/**
 * Событие "OnUserDelete" вызывается в момент <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/delete.php">удаления пользователя</a>. Как правило задачи обработчика данного события - очистить базу данных от записей связанных с удаляемым пользователем.
 *
 *
 * @param int $user_id  ID удаляемого пользователя.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // файл /bitrix/modules/my_module_id/include.php
 * class MyClass
 * {
 *     // создаем обработчик события "OnUserDelete"
 *     function OnUserDeleteHandler($user_id)
 *     {
 *         // удалим связанные записи
 *        global $DB;
 *         $strSql = "DELETE FROM my_table WHERE USER_ID=".intval($user_id);
 *         $rs = $DB-&gt;Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
 *     }
 * }
 * ?&gt;
 * &lt;?
 * // регистрируем обработчик события "OnUserDelete"
 * RegisterModuleDependences("main", "<b>OnUserDelete</b>", 
 *         "my_module_id", "MyClass", "OnUserDeleteHandler");
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserdelete.php">Событие
 * "OnBeforeUserDelete"</a></li> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/delete.php">CUser::Delete</a></li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onuserdelete.php
 * @author Bitrix
 */
	public static function OnUserDelete($user_id){}

/**
 * <p>Событие <b>OnUserLoginExternal</b> предназначено для возможности проверки имени входа и пароля во внешнем источнике. Обработчики этого события вызываются в методе <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/login.php">CUser::Login</a>, если ни один обработчик события <b> OnBegoreUserLogin</b> не вернул <i>false</i>,  перед стандартной проверкой имени входа <span class="syntax"><i>arParams</i></span><i>['LOGIN']</i>, пароля <span class="syntax"><i>arParams</i></span><i>['PASSWORD']</i> и попытки авторизовать пользователя.</p>
 *
 *
 * @param array &$arParams  Массив полей для проверки имени входа и пароля:       <ul> <li> <b>LOGIN</b> -
 * Логин пользователя</li>         <li> <b>PASSWORD</b> - Пароль. Если параметр
 * <b>PASSWORD_ORIGINAL</b> равен"Y", то в данном параметре был передан
 * оригинальный пароль, в противном случае был передан хеш (md5) от
 * оригинального пароля. </li>         <li> <b>REMEMBER</b> - Если значение равно "Y",
 * то авторизация пользователя должна быть сохранена в куках.</li>       
 *  <li> <b>PASSWORD_ORIGINAL</b> - Если значение равно "Y", то это означает что
 * <b>PASSWORD</b> не был сконвертирован в MD5 (т.е. в параметре <b>PASSWORD</b> был
 * передан реальный пароль вводимый пользователем с клавиатуры),
 * если значение равно "N", то это означает что <b>PASSWORD</b> уже
 * сконвертирован в MD5.</li>     </ul>
 *
 * @return mixed <br>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // пример авторизации пользователя из таблиц форума Innovision Power Board
 * 
 * // файл /bitrix/php_interface/init.php
 * AddEventHandler("main", "OnUserLoginExternal", Array("__IPBAuth", "OnUserLoginExternal"));
 * AddEventHandler("main", "OnExternalAuthList", Array("__IPBAuth", "OnExternalAuthList"));
 * 
 * define("IPB_TABLE_PREFIX", "ibf_");
 * define("IPB_VERSION", "2");
 * 
 * class __IPBAuth
 * {
 *     function OnUserLoginExternal(&amp;$arArgs)
 *     {
 *         $groups_map = Array(
 *             //<'IPB Group ID' =&gt; 'Local Group ID',>//
 *             '4' =&gt; '1'
 *             );
 *         $table_user = IPB_TABLE_PREFIX."members";
 *         $table_converge = IPB_TABLE_PREFIX."members_converge";
 * 
 *         global $DB, $USER, $APPLICATION;
 *      extract($arArgs);
 * 
 *         if(IPB_VERSION == '1')
 *         {
 *             $strSql = "SELECT * FROM ".$table_user." WHERE name='".
 *                       $DB-&gt;ForSql($LOGIN)."' AND password='".md5($PASSWORD)."'";
 *         }
 *         else
 *         {
 *             $strSql =
 *                 "SELECT t1.* ".
 *                 "FROM ".$table_user." t1, ".$table_converge." t2 ".
 *                 "WHERE t1.name='".$DB-&gt;ForSql($LOGIN)."' ".
 *                 "    AND t1.email = t2.converge_email ".
 *                 "    AND t2.converge_pass_hash = MD5(CONCAT(MD5(t2.converge_pass_salt), '".md5($PASSWORD)."'))";
 *         }
 * 
 *         $dbAuthRes = $DB-&gt;Query($strSql);
 *         if($arAuthRes = $dbAuthRes-&gt;Fetch())
 *         {
 *             $arFields = Array(
 *                 "LOGIN" =&gt; $LOGIN,
 *                 "NAME" =&gt; $arAuthRes['title'],
 *                 "PASSWORD" =&gt; $PASSWORD,
 *                 "EMAIL" =&gt; $arAuthRes['email'],
 *                 "ACTIVE" =&gt; "Y",
 *                 "EXTERNAL_AUTH_ID"=&gt;"IPB",
 *                 "LID" =&gt; SITE_ID
 *                 );
 * 
 *             $oUser = new CUser;
 *             $res = CUser::GetList($O, $B, Array("LOGIN_EQUAL_EXACT"=&gt;$LOGIN, "EXTERNAL_AUTH_ID"=&gt;"IPB"));
 *             if(!($ar_res = $res-&gt;Fetch()))
 *                 $ID = $oUser-&gt;Add($arFields);
 *             else
 *             {
 *                 $ID = $ar_res["ID"];
 *                 $oUser-&gt;Update($ID, $arFields);
 *             }
 * 
 *             if($ID&gt;0)
 *             {
 *                 $USER-&gt;SetParam("IPB_USER_ID", $arAuthRes['id']);
 * 
 *                 $user_group = $arAuthRes['mgroup'];
 *                 $arUserGroups = CUser::GetUserGroup($ID);
 *                 foreach($groups_map as $ext_group_id =&gt; $group_id)
 *                 {
 *                     if($ext_group_id==$user_group)
 *                         $arUserGroups[] = $group_id;
 *                     else
 *                     {
 *                         $arUserGroupsTmp = Array();
 *                         foreach($arUserGroups as $grid)
 *                             if($grid != $group_id)
 *                                 $arUserGroupsTmp[] = $grid;
 *                         $arUserGroups = $arUserGroupsTmp;
 *                     }
 *                 }
 *                 CUser::SetUserGroup($ID, $arUserGroups);
 *                 $arParams["STORE_PASSWORD"] = "N";
 * 
 *                 return $ID;
 *             }
 *         }
 *     }
 * 
 *     function OnExternalAuthList()
 *     {
 *         return Array(
 *             Array("ID"=&gt;"IPB", "NAME"=&gt;"Invision Power Board")
 *             );
 *     }
 * }?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li>  <a href="http://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserlogin.php">Событие
 * "OnBeforeUserLogin"</a> </li>   <li><a
 * href="http://dev.1c-bitrix.ru/api_help/main/events/onafteruserlogin.php">Событие "OnAfterUserLogin"</a></li>  
 * <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cuser/login.php">CUser::Login</a>  </li> <li> <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" ></a>События</li> <li>
 * <a href="http://dev.1c-bitrix.ru/learning/course/index.php?&amp;COURSE_ID=43&amp;LESSON_ID=3574" ></a>Внешняя
 * авторизация </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/events/onuserloginexternal.php
 * @author Bitrix
 */
	public static function OnUserLoginExternal(&$arParams){}

/**
 * Аналогично дополнительной обработке onsuccess.
 * <i>Вызывается в методе:</i><br>
 * <br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/main/js_lib/ajax/events/index.php
 * @author Bitrix
 */
	public static function onAjaxSuccess(){}

/**
 * Аналогично дополнительной обработке onfailure.
 * <i>Вызывается в методе:</i><br>
 * <br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/main/js_lib/ajax/events/index.php
 * @author Bitrix
 */
	public static function onAjaxFailure(){}


}
?>