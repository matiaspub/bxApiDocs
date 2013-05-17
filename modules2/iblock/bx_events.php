<?
/**
 * 
 * Класс-контейнер событий модуля <b>iblock</b>
 * 
 */
class _CEventsIblock {
	/**
	 * Событие "OnAfterIBlockAdd" вызывается после попытки добавления нового информационного блока методом <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblock/add.php">CIBlock::Add</a>.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblock">Массив полей</a> нового
	 * информационного блока. Дополнительно, в элементе массива с
	 * индексом "RESULT" содержится результат работы (возвращаемое
	 * значение) метода <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblock/add.php">CIBlock::Add</a>
	 * и, в случае ошибки, элемент с индексом "RESULT_MESSAGE" будет содержать
	 * текст ошибки.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnAfterIBlockAdd</b>", Array("MyClass", "OnAfterIBlockAddHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnAfterIBlockAdd"
	 *     function OnAfterIBlockAddHandler(&amp;$arFields)
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
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onbeforeiblockadd.php">Событие
	 * "OnBeforeIBlockAdd"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblock/add.php">CIBlock::Add</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
	 * событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblockadd.php
	 * @author Bitrix
	 */
	public static function OnAfterIBlockAdd(&$arFields){}

	/**
	 * Событие "OnAfterIBlockElementAdd" вызывается после попытки добавления нового элемента информационного блока методом <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/add.php">CIBlockElement::Add</a>.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#felement">Массив полей</a> нового
	 * элемента информационного блока. Дополнительно, в элементе
	 * массива с индексом "RESULT" содержится результат работы
	 * (возвращаемое значение) метода <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/add.php">CIBlockElement::Add</a> и, в случае
	 * ошибки, элемент с индексом "RESULT_MESSAGE" будет содержать текст ошибки.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnAfterIBlockElementAdd</b>", Array("MyClass", "OnAfterIBlockElementAddHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnAfterIBlockElementAdd"
	 *     function OnAfterIBlockElementAddHandler(&amp;$arFields)
	 *     {
	 *         if($arFields["ID"]&gt;0)
	 *              AddMessage2Log("Запись с кодом ".$arFields["ID"]." добавлена.");
	 *         else
	 *              AddMessage2Log("Ошибка добавления записи (".$arFields["RESULT_MESSAGE"].").");
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onbeforeiblockelementadd.php">Событие
	 * "OnBeforeIBlockElementAdd"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/add.php">CIBlockElement::Add</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
	 * событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblockelementadd.php
	 * @author Bitrix
	 */
	public static function OnAfterIBlockElementAdd(&$arFields){}

	/**
	 * Событие "OnAfterIBlockElementUpdate" вызывается после попытки изменения элемента информационного блока методом <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/update.php">CIBlockElement::Update</a>.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#felement">Массив полей</a> изменяемого
	 * элемента информационного блока. Дополнительно, в элементе
	 * массива с индексом "RESULT" содержится результат работы
	 * (возвращаемое значение) метода <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/update.php">CIBlockElement::Update</a> и, в
	 * случае ошибки, элемент с индексом "RESULT_MESSAGE" будет содержать текст
	 * ошибки.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnAfterIBlockElementUpdate</b>", Array("MyClass", "OnAfterIBlockElementUpdateHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnAfterIBlockElementUpdate"
	 *     function OnAfterIBlockElementUpdateHandler(&amp;$arFields)
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
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onbeforeiblockelementupdate.php">Событие
	 * "OnBeforeIBlockElementUpdate"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/update.php">CIBlockElement::Update</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
	 * событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblockelementupdate.php
	 * @author Bitrix
	 */
	public static function OnAfterIBlockElementUpdate(&$arFields){}

	/**
	 * Событие "OnAfterIBlockPropertyAdd" вызывается после попытки добавления нового свойства информационного блока методом <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/add.php">CIBlockProperty::Add</a>.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fproperty">Массив полей</a> нового
	 * свойства информационного блока. Дополнительно, в элементе
	 * массива с индексом "RESULT" содержится результат работы
	 * (возвращаемое значение) метода <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/add.php">CIBlockProperty::Add</a> и, в
	 * случае ошибки, элемент с индексом "RESULT_MESSAGE" будет содержать текст
	 * ошибки.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnAfterIBlockPropertyAdd</b>", Array("MyClass", "OnAfterIBlockPropertyAddHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnAfterIBlockPropertyAdd"
	 *     function OnAfterIBlockPropertyAddHandler(&amp;$arFields)
	 *     {
	 *         if($arFields["ID"]&gt;0)
	 *              AddMessage2Log("Запись с кодом ".$arFields["ID"]." добавлена.");
	 *         else
	 *              AddMessage2Log("Ошибка добавления записи (".$arFields["RESULT_MESSAGE"].").");
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onbeforeiblockpropertyadd.php">Событие
	 * "OnBeforeIBlockPropertyAdd"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/add.php">CIBlockProperty::Add</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
	 * событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblockpropertyadd.php
	 * @author Bitrix
	 */
	public static function OnAfterIBlockPropertyAdd(&$arFields){}

	/**
	 * Событие "OnAfterIBlockPropertyUpdate" вызывается после попытки изменения свойства информационного блока методом <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/update.php">CIBlockProperty::Update</a>.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fproperty">Массив полей</a> изменяемого
	 * свойства информационного блока. Дополнительно, в элементе
	 * массива с индексом "RESULT" содержится результат работы
	 * (возвращаемое значение) метода <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/update.php">CIBlockProperty::Update</a> и, в
	 * случае ошибки, элемент с индексом "RESULT_MESSAGE" будет содержать текст
	 * ошибки.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnAfterIBlockPropertyUpdate</b>", Array("MyClass", "OnAfterIBlockPropertyUpdateHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnAfterIBlockPropertyUpdate"
	 *     function OnAfterIBlockPropertyUpdateHandler(&amp;$arFields)
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
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onbeforeiblockpropertyupdate.php">Событие
	 * "OnBeforeIBlockPropertyUpdate"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/update.php">CIBlockProperty::Update</a> </li> <li>
	 * <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
	 * событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblockpropertyupdate.php
	 * @author Bitrix
	 */
	public static function OnAfterIBlockPropertyUpdate(&$arFields){}

	/**
	 * Событие "OnAfterIBlockSectionAdd" вызывается после попытки добавления нового раздела информационного блока методом <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocksection/add.php">CIBlockSection::Add</a>.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fsection">Массив полей</a> нового
	 * раздела информационного блока. Дополнительно, в элементе массива
	 * с индексом "RESULT" содержится результат работы (возвращаемое
	 * значение) метода <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocksection/add.php">CIBlockSection::Add</a> и, в случае
	 * ошибки, элемент с индексом "RESULT_MESSAGE" будет содержать текст ошибки.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnAfterIBlockSectionAdd</b>", Array("MyClass", "OnAfterIBlockSectionAddHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnAfterIBlockSectionAdd"
	 *     function OnAfterIBlockSectionAddHandler(&amp;$arFields)
	 *     {
	 *         if($arFields["ID"]&gt;0)
	 *              AddMessage2Log("Запись с кодом ".$arFields["ID"]." добавлена.");
	 *         else
	 *              AddMessage2Log("Ошибка добавления записи (".$arFields["RESULT_MESSAGE"].").");
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onbeforeiblocksectionadd.php">Событие
	 * "OnBeforeIBlockSectionAdd"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocksection/add.php">CIBlockSection::Add</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
	 * событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblocksectionadd.php
	 * @author Bitrix
	 */
	public static function OnAfterIBlockSectionAdd(&$arFields){}

	/**
	 * Событие "OnAfterIBlockSectionUpdate" вызывается после попытки изменения раздела информационного блока методом <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocksection/update.php">CIBlockSection::Update</a>.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fsection">Массив полей</a> изменяемого
	 * раздела информационного блока. Дополнительно, в элементе массива
	 * с индексом "RESULT" содержится результат работы (возвращаемое
	 * значение) метода <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocksection/update.php">CIBlockSection::Update</a> и, в
	 * случае ошибки, элемент с индексом "RESULT_MESSAGE" будет содержать текст
	 * ошибки.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnAfterIBlockSectionUpdate</b>", Array("MyClass", "OnAfterIBlockSectionUpdateHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnAfterIBlockSectionUpdate"
	 *     function OnAfterIBlockSectionUpdateHandler(&amp;$arFields)
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
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onbeforeiblocksectionupdate.php">Событие
	 * "OnBeforeIBlockSectionUpdate"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocksection/update.php">CIBlockSection::Update</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
	 * событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblocksectionupdate.php
	 * @author Bitrix
	 */
	public static function OnAfterIBlockSectionUpdate(&$arFields){}

	/**
	 * Событие "OnAfterIBlockUpdate" вызывается после попытки изменения информационного блока методом <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblock/update.php">CIBlock::Update</a>.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblock">Массив полей</a> изменяемого
	 * информационного блока. Дополнительно, в элементе массива с
	 * индексом "RESULT" содержится результат работы (возвращаемое
	 * значение) метода <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblock/update.php">CIBlock::Update</a> и, в случае
	 * ошибки, элемент с индексом "RESULT_MESSAGE" будет содержать текст ошибки.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnAfterIBlockUpdate</b>", Array("MyClass", "OnAfterIBlockUpdateHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnAfterIBlockUpdate"
	 *     function OnAfterIBlockUpdateHandler(&amp;$arFields)
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
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onbeforeiblockupdate.php">Событие
	 * "OnBeforeIBlockUpdate"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblock/update.php">CIBlock::Update</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
	 * событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblockupdate.php
	 * @author Bitrix
	 */
	public static function OnAfterIBlockUpdate(&$arFields){}

	/**
	 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblock/add.php">CIBlock::Add</a> до вставки информационного блока, и может быть использовано для отмены вставки или переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblock">Массив полей</a> нового
	 * информационного блока.
	 *
	 *
	 *
	 * @return bool <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblock/add.php">CIBlock::Add</a><nobr>$APPLICATION-&gt;<a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnBeforeIBlockAdd</b>", Array("MyClass", "OnBeforeIBlockAddHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnBeforeIBlockAdd"
	 *     function OnBeforeIBlockAddHandler(&amp;$arFields)
	 *     {
	 *         if(strlen($arFields["CODE"])&lt;=0)
	 *         {
	 *             global $APPLICATION;
	 *             $APPLICATION-&gt;throwException("Введите мнемонический код.");
	 *             return false;
	 *         }
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onafteriblockadd.php">Событие
	 * "OnAfterIBlockAdd"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblock/add.php">CIBlock::Add</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
	 * событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockadd.php
	 * @author Bitrix
	 */
	public static function OnBeforeIBlockAdd(&$arParams){}

	/**
	 * <p>Вызывается перед удалением информационного блока.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID информационного блока.
	 *
	 *
	 *
	 * @return bool <a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // Подключаем к событию обработчик
	 * RegisterModuleDependences("iblock", 
	 *                           "OnBeforeIBlockDelete", 
	 *                           "catalog", 
	 *                           "CCatalog", 
	 *                           "OnIBlockDelete");
	 * 
	 * // Реализуем обработчик
	 * class CCatalog
	 * {
	 *   * * *
	 * 
	 *   function OnBeforeIBlockDelete($ID)
	 *   {
	 *     return false;
	 *   }
	 * }
	 * 
	 * // Теперь нельзя удалить информационный блок.
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockdelete.php
	 * @author Bitrix
	 */
	public static function OnBeforeIBlockDelete($ID){}

	/**
	 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/add.php">CIBlockElement::Add</a> до вставки информационного блока, и может быть использовано для отмены вставки или переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#felement">Массив полей</a> нового
	 * элемента информационного блока.
	 *
	 *
	 *
	 * @return bool <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/add.php">CIBlockElement::Add</a><nobr>$APPLICATION-&gt;<a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnBeforeIBlockElementAdd</b>", Array("MyClass", "OnBeforeIBlockElementAddHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnBeforeIBlockElementAdd"
	 *     function OnBeforeIBlockElementAddHandler(&amp;$arFields)
	 *     {
	 *         if(strlen($arFields["CODE"])&lt;=0)
	 *         {
	 *             global $APPLICATION;
	 *             $APPLICATION-&gt;throwException("Введите мнемонический код.");
	 *             return false;
	 *         }
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onafteriblockelementadd.php">Событие
	 * "OnAfterIBlockElementAdd"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/add.php">CIBlockElement::Add</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
	 * событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockelementadd.php
	 * @author Bitrix
	 */
	public static function OnBeforeIBlockElementAdd(&$arParams){}

	/**
	 * Событие "OnBeforeIBlockElementDelete" вызывается перед удалением элемента методом <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/delete.php">CIBlockElement::Delete</a>. Как правило задачи обработчика данного события - разрешить или запретить удаление.
	 *
	 *
	 *
	 *
	 * @param int $ID  ID удаляемого элемента.
	 *
	 *
	 *
	 * @return bool <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/delete.php">CIBlockElement::Delete</a><nobr></nobr><nobr>$APPLICATION-&gt;<a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnBeforeIBlockElementDelete</b>", Array("MyClass", "OnBeforeIBlockElementDeleteHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnBeforeIBlockElementDelete"
	 *     function OnBeforeIBlockElementDeleteHandler($ID)
	 *     {
	 *         if($ID==1)
	 *         {
	 *             global $APPLICATION;
	 *             $APPLICATION-&gt;throwException("элемент с ID=1 нельзя удалить.");
	 *             return false;
	 *         }
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/delete.php">CIBlockElement::Delete</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493"
	 * >Обработка событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockelementdelete.php
	 * @author Bitrix
	 */
	public static function OnBeforeIBlockElementDelete($ID){}

	/**
	 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/update.php">CIBlockElement::Update</a> до изменения элемента информационного блока, и может быть использовано для отмены изменения или для переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#felement">Массив полей</a> изменяемого
	 * элемента информационного блока.
	 *
	 *
	 *
	 * @return bool <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/update.php">CIBlockElement::Update</a><nobr>$APPLICATION-&gt;<a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnBeforeIBlockElementUpdate</b>", Array("MyClass", "OnBeforeIBlockElementUpdateHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnBeforeIBlockElementUpdate"
	 *     function OnBeforeIBlockElementUpdateHandler(&amp;$arFields)
	 *     {
	 *         if(strlen($arFields["CODE"])&lt;=0)
	 *         {
	 *             global $APPLICATION;
	 *             $APPLICATION-&gt;throwException("Введите мнемонический код. (ID:".$arFields["ID"].")");
	 *             return false;
	 *         }
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onafteriblockelementupdate.php">Событие
	 * "OnAfterIBlockElementUpdate"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/update.php">CIBlockElement::Update</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
	 * событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockelementupdate.php
	 * @author Bitrix
	 */
	public static function OnBeforeIBlockElementUpdate(&$arParams){}

	/**
	 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/add.php">CIBlockProperty::Add</a> до вставки свойства в инфоблок, и может быть использовано для отмены вставки или переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fproperty">Массив полей</a> нового
	 * свойства информационного блока.
	 *
	 *
	 *
	 * @return bool <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/add.php">CIBlockProperty::Add</a><nobr>$APPLICATION-&gt;<a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnBeforeIBlockPropertyAdd</b>", Array("MyClass", "OnBeforeIBlockPropertyAddHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnBeforeIBlockPropertyAdd"
	 *     function OnBeforeIBlockPropertyAddHandler(&amp;$arFields)
	 *     {
	 *         if(strlen($arFields["CODE"])&lt;=0)
	 *         {
	 *             global $APPLICATION;
	 *             $APPLICATION-&gt;throwException("Введите мнемонический код.");
	 *             return false;
	 *         }
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onafteriblockpropertyadd.php">Событие
	 * "OnAfterIBlockPropertyAdd"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/add.php">CIBlockProperty::Add</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
	 * событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockpropertyadd.php
	 * @author Bitrix
	 */
	public static function OnBeforeIBlockPropertyAdd(&$arParams){}

	/**
	 * Событие "OnBeforeIBlockPropertyDelete" вызывается перед удалением свойства методом <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/delete.php">CIBlockProperty::Delete</a>. Как правило задачи обработчика данного события - разрешить или запретить удаление.
	 *
	 *
	 *
	 *
	 * @param int $ID  ID удаляемого свойства.
	 *
	 *
	 *
	 * @return bool <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/delete.php">CIBlockProperty::Delete</a><nobr></nobr><nobr>$APPLICATION-&gt;<a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnBeforeIBlockPropertyDelete</b>", Array("MyClass", "OnBeforeIBlockPropertyDeleteHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnBeforeIBlockPropertyDelete"
	 *     function OnBeforeIBlockPropertyDeleteHandler($ID)
	 *     {
	 *         if($ID==1)
	 *         {
	 *             global $APPLICATION;
	 *             $APPLICATION-&gt;throwException("Свойство с ID=1 нельзя удалить.");
	 *             return false;
	 *         }
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/delete.php">CIBlockProperty::Delete</a> </li> <li>
	 * <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
	 * событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockpropertydelete.php
	 * @author Bitrix
	 */
	public static function OnBeforeIBlockPropertyDelete($ID){}

	/**
	 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/update.php">CIBlockProperty::Update</a> до изменения свойства информационного блока, и может быть использовано для отмены изменения или для переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fproperty">Массив полей</a> изменяемого
	 * свойства информационного блока.
	 *
	 *
	 *
	 * @return bool <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/update.php">CIBlockProperty::Update</a><nobr>$APPLICATION-&gt;<a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnBeforeIBlockPropertyUpdate</b>", Array("MyClass", "OnBeforeIBlockPropertyUpdateHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnBeforeIBlockPropertyUpdate"
	 *     function OnBeforeIBlockPropertyUpdateHandler(&amp;$arFields)
	 *     {
	 *         if(strlen($arFields["CODE"])&lt;=0)
	 *         {
	 *             global $APPLICATION;
	 *             $APPLICATION-&gt;throwException("Введите мнемонический код. (ID:".$arFields["ID"].")");
	 *             return false;
	 *         }
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onafteriblockpropertyupdate.php">Событие
	 * "OnAfterIBlockPropertyUpdate"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockproperty/update.php">CIBlockProperty::Update</a> </li> <li>
	 * <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
	 * событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockpropertyupdate.php
	 * @author Bitrix
	 */
	public static function OnBeforeIBlockPropertyUpdate(&$arParams){}

	/**
	 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocksection/add.php">CIBlockSection::Add</a> до вставки информационного блока, и может быть использовано для отмены вставки или переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fsection">Массив полей</a> нового
	 * раздела информационного блока.
	 *
	 *
	 *
	 * @return bool <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocksection/add.php">CIBlockSection::Add</a><nobr>$APPLICATION-&gt;<a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnBeforeIBlockSectionAdd</b>", Array("MyClass", "OnBeforeIBlockSectionAddHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnBeforeIBlockSectionAdd"
	 *     function OnBeforeIBlockSectionAddHandler(&amp;$arFields)
	 *     {
	 *         if(strlen($arFields["CODE"])&lt;=0)
	 *         {
	 *             global $APPLICATION;
	 *             $aMsg = array();
	 *             $aMsg[] = array("id"=&gt;"CODE", "text"=&gt;"Введите мнемонический код.");
	 *             $e = new CAdminException($aMsg);
	 *             $APPLICATION-&gt;throwException($e);
	 *             return false;
	 *         }
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onafteriblocksectionadd.php">Событие
	 * "OnAfterIBlockSectionAdd"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocksection/add.php">CIBlockSection::Add</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
	 * событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblocksectionadd.php
	 * @author Bitrix
	 */
	public static function OnBeforeIBlockSectionAdd(&$arParams){}

	/**
	 * Событие "OnBeforeIBlockSectionDelete" вызывается перед удалением раздела методом <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocksection/delete.php">CIBlockSection::Delete</a>. Как правило задачи обработчика данного события - разрешить или запретить удаление.
	 *
	 *
	 *
	 *
	 * @param int $ID  ID удаляемого раздела.
	 *
	 *
	 *
	 * @return bool <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocksection/delete.php">CIBlockSection::Delete</a><nobr></nobr><nobr>$APPLICATION-&gt;<a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnBeforeIBlockSectionDelete</b>", Array("MyClass", "OnBeforeIBlockSectionDeleteHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnBeforeIBlockSectionDelete"
	 *     function OnBeforeIBlockSectionDeleteHandler($ID)
	 *     {
	 *         if($ID==1)
	 *         {
	 *             global $APPLICATION;
	 *             $APPLICATION-&gt;throwException("раздел с ID=1 нельзя удалить.");
	 *             return false;
	 *         }
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocksection/delete.php">CIBlockSection::Delete</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493"
	 * >Обработка событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblocksectiondelete.php
	 * @author Bitrix
	 */
	public static function OnBeforeIBlockSectionDelete($ID){}

	/**
	 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocksection/update.php">CIBlockSection::Update</a> до изменения раздела информационного блока, и может быть использовано для отмены изменения или для переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fsection">Массив полей</a> изменяемого
	 * раздела информационного блока.
	 *
	 *
	 *
	 * @return bool <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocksection/update.php">CIBlockSection::Update</a><nobr>$APPLICATION-&gt;<a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnBeforeIBlockSectionUpdate</b>", Array("MyClass", "OnBeforeIBlockSectionUpdateHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnBeforeIBlockSectionUpdate"
	 *     function OnBeforeIBlockSectionUpdateHandler(&amp;$arFields)
	 *     {
	 *         if(strlen($arFields["CODE"])&lt;=0)
	 *         {
	 *             global $APPLICATION;
	 *             $APPLICATION-&gt;throwException("Введите мнемонический код. (ID:".$arFields["ID"].")");
	 *             return false;
	 *         }
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onafteriblocksectionupdate.php">Событие
	 * "OnAfterIBlockSectionUpdate"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblocksection/update.php">CIBlockSection::Update</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
	 * событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblocksectionupdate.php
	 * @author Bitrix
	 */
	public static function OnBeforeIBlockSectionUpdate(&$arParams){}

	/**
	 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblock/update.php">CIBlock::Update</a> до изменения информационного блока, и может быть использовано для отмены изменения или переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblock">Массив полей</a> изменяемого
	 * информационного блока.
	 *
	 *
	 *
	 * @return bool <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblock/update.php">CIBlock::Update</a><nobr>$APPLICATION-&gt;<a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // файл /bitrix/php_interface/init.php
	 * // регистрируем обработчик
	 * AddEventHandler("iblock", "<b>OnBeforeIBlockUpdate</b>", Array("MyClass", "OnBeforeIBlockUpdateHandler"));<br>
	 * class MyClass
	 * {
	 *     // создаем обработчик события "OnBeforeIBlockUpdate"
	 *     function OnBeforeIBlockUpdateHandler(&amp;$arFields)
	 *     {
	 *         if(strlen($arFields["CODE"])&lt;=0)
	 *         {
	 *             global $APPLICATION;
	 *             $APPLICATION-&gt;throwException("Введите мнемонический код. (ID:".$arFields["ID"].")");
	 *             return false;
	 *         }
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onafteriblockupdate.php">Событие
	 * "OnAfterIBlockUpdate"</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblock/update.php">CIBlock::Update</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
	 * событий</a> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockupdate.php
	 * @author Bitrix
	 */
	public static function OnBeforeIBlockUpdate(&$arParams){}

	/**
	 * <p>Вызывается в момент удаления информационного блока.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID информационного блока.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // Подключаем к событию обработчик
	 * RegisterModuleDependences("iblock", 
	 *                           "OnIBlockDelete", 
	 *                           "catalog", 
	 *                           "CCatalog", 
	 *                           "OnIBlockDelete");
	 * 
	 * // Реализуем обработчик
	 * class CCatalog
	 * {
	 *   * * *
	 * 
	 *   function OnIBlockDelete($ID)
	 *   {
	 *     return CCatalog::Delete($ID);
	 *   }
	 * }
	 * 
	 * // Теперь при удалении блока будет вызываться обработчик
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/oniblockdelete.php
	 * @author Bitrix
	 */
	public static function OnIBlockDelete($ID){}

	/**
	 * <p> Вызывается в момент удаления элемента информационного блока.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID элемента информационного блока.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // Подключаем к событию обработчик
	 * RegisterModuleDependences("iblock", 
	 *                           "OnIBlockElementDelete", 
	 *                           "catalog", 
	 *                           "CCatalogProduct", 
	 *                           "OnIBlockElementDelete");
	 * 
	 * // Реализуем обработчик
	 * class CCatalogProduct
	 * {
	 *   * * *
	 * 
	 *   function OnIBlockElementDelete($PRODUCT_ID)
	 *   {
	 *     global $DB;
	 *     echo "Удаляем...";
	 *     return True;
	 *   }
	 * }
	 * 
	 * // Теперь при удалении элемента будет вызываться обработчик
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/oniblockelementdelete.php
	 * @author Bitrix
	 */
	public static function OnIBlockElementDelete($ID){}

	/**
	 * <p>Событие вызывается при построении списка <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/user_properties/index.php">пользовательских свойств</a>. Обработчик этого события должен вернуть массив описывающий пользовательское свойство. Содержимое этого массива описано в методе <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/user_properties/GetUserTypeDescription.php">GetUserTypeDescription</a>. <br></p>
	 *
	 *
	 *
	 *
	 * @return array <p>Массив.</p>
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/user_properties/index.php">Пользовательские
	 * свойства</a></li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/user_properties/GetUserTypeDescription.php">GetUserTypeDescription</a> 
	 * </li> </ul><br>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/OnIBlockPropertyBuildList.php
	 * @author Bitrix
	 */
	public static function OnIBlockPropertyBuildList(){}

	/**
	 * 
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/OnStartIBlockElementAdd.php
	 * @author Bitrix
	 */
	public static function OnStartIBlockElementAdd(){}

	/**
	 * 
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/OnStartIBlockElementUpdate.php
	 * @author Bitrix
	 */
	public static function OnStartIBlockElementUpdate(){}


}?>