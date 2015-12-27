<?
/**
 * 
 * Класс-контейнер событий модуля <b>fileman</b>
 * 
 */
class _CEventsFileman {
/**
 * Событие "OnBeforeHTMLEditorScriptsGet" вызывается перед загрузкой JavaScript и CSS файлов редактора и позволяет добавить пользовательские файлы, которые будут подгружаться после файлов визуального редактора. Создание обработчика данного представляет собой простейший способ модифицировать встроенный визуальный редактор путём расширения или переопределения текущего функционала.
 *
 *
 * @param string $editorName  Имя подключаемого редактора.
 *
 * @param array $arEditorParams  Массив параметров подключаемого редактора.
 *
 * @return array 
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?
 * // файл /bitrix/php_interface/init.php
 * // регистрируем обработчик
 * AddEventHandler("fileman", "<b>OnBeforeHTMLEditorScriptsGet</b>", "addEditorScriptsHandler");
 * function addEditorScriptsHandler($editorName,$arEditorParams)
 * {
 * 	// Проверяем, если подключается редактор для редактирования статических страниц
 * 	if ($editor_name == 'filesrc')
 * 		return array(
 * 			"JS" =&gt; array('my_scripts.js'),
 * 			"CSS" =&gt; array('my_styles.css')
 * 		);
 * 		
 * 	return array();
 * }
 * ?&gt;
 * 
 * &lt;?
 * // файл /bitrix/admin/htmleditor2/my_scripts.js
 * // Переопределяем стандартную панель инструментов, удаляя из нее кнопки "Настройки", "Выделить все" и "Проверка орфографии"
 * arToolbars['standart'] = [
 * 	BX_MESS.TBSStandart,
 * 		[
 * 		arButtons['Fullscreen'], 'separator',
 * 		arButtons['Cut'], arButtons['Copy'], arButtons['Paste'], arButtons['pasteword'], arButtons['pastetext'], arButtons['separator'],
 * 		arButtons['Undo'], arButtons['Redo'], arButtons['separator'],
 * 		arButtons['borders'], 'separator',
 * 		arButtons['table'], arButtons['anchor'], arButtons['CreateLink'], arButtons['deletelink'], arButtons['image'], 'separator',
 * 		arButtons['SpecialChar'], arButtons['spellcheck']
 * 		]
 * 	];
 * ?&gt;
 * 
 * //< файл /bitrix/admin/htmleditor2/my_styles.css
 * переопределяем цвет фона редактора (только для Mozilla Firefox) >//
 * .bxedmainframe IFRAME{
 * 	background-color: #CCCCCC;
 * }
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/fileman/events/onincludehtmleditorscript.php">Событие
 * "OnIncludeHTMLEditorScript"</a> </li></ul></bod<a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/fileman/events/onbeforehtmleditorscriptsget.php
 * @author Bitrix
 */
	public static function OnBeforeHTMLEditorScriptsGet($editorName, $arEditorParams){}

/**
 * Вызывается перед подключением JavaScript-файлов упрощенного редактора.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CLightHTMLEditor::Init<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/fileman/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeLightEditorScriptsGet(){}

/**
 * Событие "OnIncludeHTMLEditorScript" вызывается после подключения файлов визуального редактора. Может использоваться в тех случаях, когда для модификации редактора недостаточно стандартного подключения внешних JavaScript файлов (<a href="http://dev.1c-bitrix.ru/api_help/fileman/events/onbeforehtmleditorscriptsget.php">Событие "OnBeforeHTMLEditorScriptsGet"</a>).
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?
 * // файл /bitrix/php_interface/init.php
 * // регистрируем обработчик
 * AddEventHandler("fileman", "<b>OnIncludeHTMLEditorScript</b>", "OnIncludeHTMLEditorHandler");
 * function OnIncludeHTMLEditorHandler()
 * {
 * 	?&gt;
 * 	&lt;script&gt;
 * 	//Переопределение функции установки полноэкранного режима редактора
 * 	BXHTMLEditor.prototype.SetFullscreen_ = BXHTMLEditor.prototype.SetFullscreen;
 * 	BXHTMLEditor.prototype.SetFullscreen = function (bFull)
 * 	{
 * 		alert('My alert!');
 * 		this.SetFullscreen_(bFull);
 * 	}
 * 	&lt;/script&gt;
 * 	&lt;?
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/fileman/events/onbeforehtmleditorscriptsget.php">Событие
 * "OnBeforeHTMLEditorScriptsGet"</a> </li></ul></bod<a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/fileman/events/onincludehtmleditorscript.php
 * @author Bitrix
 */
	public static function OnIncludeHTMLEditorScript(){}

/**
 * Вызывается непосредственно после подключения упрощенного редактора, на странице вызова, но до его инициализации.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CLightHTMLEditor::InitScripts<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/fileman/events/index.php
 * @author Bitrix
 */
	public static function OnIncludeLightEditorScript(){}

/**
 * Вызывается происходит перед созданием и выдачей элемента медиабиблиотеки в виде HTML для показа пользователю.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CMedialib::GetItemViewHTML<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/fileman/events/index.php
 * @author Bitrix
 */
	public static function OnMedialibItemView(){}


}
?>