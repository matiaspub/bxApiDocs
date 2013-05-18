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
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnPageStart(){}

	/**
	 * в конце выполняемой части пролога сайта (после события <a href="/api_help/main/events/onpagestart.php">OnPageStart</a>).
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnBeforeProlog(){}

	/**
	 * в начале визуальной части пролога сайта.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CAllMain::PrologActions
	 */
	public static function OnProlog(){}

	/**
	 * в конце визуальной части эпилога сайта.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnEpilog(){}

	/**
	 * в конце выполняемой части эпилога сайта (после события <a href="/api_help/main/events/onepilog.php">OnEpilog</a>).
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnAfterEpilog(){}

	/**
	 * перед выводом буферизированного контента
	 * 
	 * <i>Вызывается в методе:</i>
	 * CAllMain::EndBufferContent
	 */
	public static function OnBeforeEndBufferContent(){}

	/**
	 * перед сбросом буфера контента
	 * 
	 * <i>Вызывается в методе:</i>
	 * CAllMain::RestartBuffer
	 */
	public static function OnBeforeRestartBuffer(){}

	/**
	 * при выводе буферизированного контента.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CAllMain::EndBufferContent
	 */
	public static function OnEndBufferContent(){}

	/**
	 * Ссылка на массив кнопок на панели. Структура массива описана на странице <a href="/api_help/main/general/admin.section/classes/cadmincontextmenu/cadmincontextmenu.php">Конструктор CAdminContextMenu</a>.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function items(){}

	/**
	 * Ссылка на объект класса <a href="/api_help/main/general/admin.section/classes/cadminlist/index.php">CAdminList</a>.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function list(){}

	/**
	 * Ссылка на объект класса <a href="/api_help/main/general/admin.section/classes/cadmintabcontrol/index.php">CAdminTabControl</a>. Использование члена класса $form-&gt;tabs позволяет получить доступ к закладкам формы. Структура массива закладок описана на странице <a href="/api_help/main/general/admin.section/classes/cadmintabcontrol/cadmintabcontrol.php">Конструктор CAdminTabControl</a>.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function form(){}

	/**
	 *  
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function В (){}

	/**
	 * Список полей (<a href="/api_help/main/reference/cgroup/index.php">класс CGroup</a>) изменяемой группы пользователей.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function arFields(){}

	/**
	 * Идентификатор изменяемой группы пользователей.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function ID(){}

	/**
	 * Массив полей для проверки имени входа и пароля: 
	 *         <ul>
<li>
<b>FIELDS</b> - Массив, содержащий набор полей вида Array("поле 1"=&gt;"значение 1", ...). При отправке все поля передаются в обработку шаблона USER_INFO. Содержит по умолчанию след. поля:</li>
	 *          
	 *           <ul>
<li>"USER_ID" - код пользователя, </li>
	 *            
	 *             <li>"STATUS" - текст статуса активности, </li>
	 *            
	 *             <li>"MESSAGE" - текст сообщения, </li>
	 *            
	 *             <li>"LOGIN" - имя входа, </li>
	 *            
	 *             <li>"CHECKWORD" - контрольная строка, </li>
	 *            
	 *             <li>"NAME" - имя пользователя, </li>
	 *            
	 *             <li>"LAST_NAME" - фамилия, </li>
	 *            
	 *             <li>"EMAIL" - E-Mail адрес 		</li>
	 *            </ul>
<li>
<b>USER_FIELDS</b> - Все <a href="/api_help/main/reference/cuser/index.php">поля пользователя</a>, информация о котором будет высылаться. </li>
	 *          
	 *           <li>
<b>SITE_ID</b> - код сайта, используется для определения шаблона почтового события USER_INFO.</li>
	 *          </ul>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function arParams(){}

	/**
	 * Абсолютный путь к файлу (включая document_root).
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function abs_path(){}

	/**
	 * Новое содержимое файла. Значение передается по ссылке. Таким образом, обработчик может изменить содержимое файла перед его сохранением.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function strContent(){}

	/**
	 * Идентификатор типа почтового события, например FORM_FILLING_ANKETA
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function $event(){}

	/**
	 * ID сайта, на котором был вызов метода CEvent::Send()
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function $lid(){}

	/**
	 * Массив параметров, которые передаются в обработчик события. 
	 *         <br>
	 *        Пример массива: 
	 *         <br><span class="Apple-style-span" style="color: rgb(51, 51, 51); font-family: Tahoma, Verdana, Helvetica, sans-serif; font-size: 13px; "> 
	 *           <pre>Array
	 *         (
	 *             [RS_FORM_ID] =&gt; 1
	 *             [RS_FORM_NAME] =&gt; Анкета посетителя сайта
	 *             [RS_FORM_VARNAME] =&gt; ANKETA
	 *             [RS_FORM_SID] =&gt; ANKETA
	 *             [RS_RESULT_ID] =&gt; 11
	 *             [RS_DATE_CREATE] =&gt; 24.07.2011 16:59:55
	 *             [RS_USER_ID] =&gt; 1
	 *             [RS_USER_EMAIL] =&gt; my@email.com
	 *             [RS_USER_NAME] =&gt;  
	 *             [RS_USER_AUTH] =&gt;  
	 *             [RS_STAT_GUEST_ID] =&gt; 1
	 *             [RS_STAT_SESSION_ID] =&gt; 1
	 *             [VS_NAME] =&gt; sfdsf
	 *             [VS_NAME_RAW] =&gt; sfdsf
	 *             [VS_BIRTHDAY] =&gt; 21.07.2011
	 *             [VS_BIRTHDAY_RAW] =&gt; 21.07.2011
	 *             [VS_ADDRESS] =&gt; sdfdsf
	 *             [VS_ADDRESS_RAW] =&gt; sdfdsf
	 *             [VS_MARRIED] =&gt; Да [4]
	 *             [VS_MARRIED_RAW] =&gt; Да
	 *             [VS_INTEREST] =&gt; физика (2) [7]
	 *                              программирование (5) [10]
	 *             [VS_INTEREST_RAW] =&gt; физика,программирование
	 *             [VS_AGE] =&gt; 30-39 (30) [13]
	 *             [VS_AGE_RAW] =&gt; 30-39
	 *             [VS_EDUCATION] =&gt; высшее (3) [19]
	 *             [VS_EDUCATION_RAW] =&gt; высшее
	 *             [VS_INCOME] =&gt;  
	 *             [VS_INCOME_RAW] =&gt;  
	 *             [VS_PHOTO] =&gt;  
	 *             [VS_PHOTO_RAW] =&gt;  
	 *         )</pre>
	 *          </span>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function $arFields(){}

	/**
	 * Идентификатор почтового шаблона (если указан) - с версии ядра 11.0.16.
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function $message_id(){}

	/**
	 * Массив с информацией об удаленном файле, содержащий ключи:
	 *  
	 *         <br>
	 *       
	 *  SUBDIR - подпапка в папке для загрузки файлов (обычно в /upload);
	 *  
	 *         <br>
	 *       
	 *  FILE_NAME - имя удаленного файла.
	 *  
	 *         <br>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function arFile(){}


}
?>