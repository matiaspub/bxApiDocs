<?
/**
 * 
 * Класс-контейнер событий модуля <b>iblock</b>
 * 
 */
class _CEventsIblock {
/**
 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/add.php">CIBlock::Add</a> до вставки информационного блока, и может быть использовано для отмены вставки или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblock">Массив полей</a> нового
 * информационного блока.
 *
 * @return bool <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/add.php">CIBlock::Add</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 *             $APPLICATION-&gt;throwException("Введите символьный код.");
 *             return false;
 *         }
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblockadd.php">Событие
 * "OnAfterIBlockAdd"</a>   </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/add.php">CIBlock::Add</a>   </li> <li> <a
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
 * Событие "OnAfterIBlockAdd" вызывается после попытки добавления нового информационного блока методом <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/add.php">CIBlock::Add</a>.
 *
 *
 * @param array &$arFields  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblock">Массив полей</a> нового
 * информационного блока. Дополнительно, в элементе массива с
 * индексом "RESULT" содержится результат работы (возвращаемое
 * значение) метода <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/add.php">CIBlock::Add</a> и, в случае
 * ошибки, элемент с индексом "RESULT_MESSAGE" будет содержать текст ошибки.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockadd.php">Событие
 * "OnBeforeIBlockAdd"</a>   </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/add.php">CIBlock::Add</a>   </li> <li> <a
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
 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/update.php">CIBlock::Update</a> до изменения информационного блока, и может быть использовано для отмены изменения или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblock">Массив полей</a> изменяемого
 * информационного блока.
 *
 * @return bool <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/update.php">CIBlock::Update</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 *             $APPLICATION-&gt;throwException("Введите символьный код. (ID:".$arFields["ID"].")");
 *             return false;
 *         }
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblockupdate.php">Событие
 * "OnAfterIBlockUpdate"</a>   </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/update.php">CIBlock::Update</a>   </li> <li> <a
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
 * Событие "OnAfterIBlockUpdate" вызывается после попытки изменения информационного блока методом <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/update.php">CIBlock::Update</a>.
 *
 *
 * @param array &$arFields  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblock">Массив полей</a> изменяемого
 * информационного блока. Дополнительно, в элементе массива с
 * индексом "RESULT" содержится результат работы (возвращаемое
 * значение) метода <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/update.php">CIBlock::Update</a> и, в случае
 * ошибки, элемент с индексом "RESULT_MESSAGE" будет содержать текст ошибки.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockupdate.php">Событие
 * "OnBeforeIBlockUpdate"</a>   </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/update.php">CIBlock::Update</a>   </li> <li> <a
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
 * <p>Вызывается перед удалением информационного блока.</p>
 *
 *
 * @param mixed $intID  ID информационного блока.
 *
 * @return bool <a name="examples"></a>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
	public static function OnBeforeIBlockDelete($intID){}

/**
 * <p>Вызывается в момент удаления информационного блока.</p>
 *
 *
 * @param mixed $intID  ID информационного блока.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
	public static function OnIBlockDelete($intID){}

/**
 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/add.php">CIBlockProperty::Add</a> до вставки свойства в инфоблок, и может быть использовано для отмены вставки или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fproperty">Массив полей</a> нового
 * свойства информационного блока.
 *
 * @return bool <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/add.php">CIBlockProperty::Add</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 *             $APPLICATION-&gt;throwException("Введите символьный код.");
 *             return false;
 *         }
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblockpropertyadd.php">Событие
 * "OnAfterIBlockPropertyAdd"</a>   </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/add.php">CIBlockProperty::Add</a>   </li> <li> <a
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
 * Событие "OnAfterIBlockPropertyAdd" вызывается после попытки добавления нового свойства информационного блока методом <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/add.php">CIBlockProperty::Add</a>.
 *
 *
 * @param array &$arFields  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fproperty">Массив полей</a> нового
 * свойства информационного блока. Дополнительно, в элементе
 * массива с индексом "RESULT" содержится результат работы
 * (возвращаемое значение) метода <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/add.php">CIBlockProperty::Add</a> и, в
 * случае ошибки, элемент с индексом "RESULT_MESSAGE" будет содержать текст
 * ошибки.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockpropertyadd.php">Событие
 * "OnBeforeIBlockPropertyAdd"</a>   </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/add.php">CIBlockProperty::Add</a>   </li> <li> <a
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
 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/update.php">CIBlockProperty::Update</a> до изменения свойства информационного блока, и может быть использовано для отмены изменения или для переопределения некоторых полей.
 *
 *
 * @param array &$arParams  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fproperty">Массив полей</a>
 * изменяемого свойства информационного блока.
 *
 * @return bool <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/update.php">CIBlockProperty::Update</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 *             $APPLICATION-&gt;throwException("Введите символьный код. (ID:".$arFields["ID"].")");
 *             return false;
 *         }
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblockpropertyupdate.php">Событие
 * "OnAfterIBlockPropertyUpdate"</a>   </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/update.php">CIBlockProperty::Update</a>   </li>
 * <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a> </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockpropertyupdate.php
 * @author Bitrix
 */
	public static function OnBeforeIBlockPropertyUpdate(&$arParams){}

/**
 * при удалении свойства.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/delete.php">Delete</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/index.php
 * @author Bitrix
 */
	public static function OnIBlockPropertyDelete(){}

/**
 * Событие "OnAfterIBlockPropertyUpdate" вызывается после попытки изменения свойства информационного блока методом <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/update.php">CIBlockProperty::Update</a>.
 *
 *
 * @param array &$arFields  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fproperty">Массив полей</a>
 * изменяемого свойства информационного блока. Дополнительно, в
 * элементе массива с индексом "RESULT" содержится результат работы
 * (возвращаемое значение) метода <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/update.php">CIBlockProperty::Update</a> и, в
 * случае ошибки, элемент с индексом "RESULT_MESSAGE" будет содержать текст
 * ошибки.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockpropertyupdate.php">Событие
 * "OnBeforeIBlockPropertyUpdate"</a>   </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/update.php">CIBlockProperty::Update</a>   </li>
 * <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a> </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblockpropertyupdate.php
 * @author Bitrix
 */
	public static function OnAfterIBlockPropertyUpdate(&$arFields){}

/**
 * Событие "OnBeforeIBlockPropertyDelete" вызывается перед удалением свойства методом <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/delete.php">CIBlockProperty::Delete</a>. Как правило задачи обработчика данного события - разрешить или запретить удаление.
 *
 *
 * @param mixed $intID  ID удаляемого свойства.
 *
 * @return bool <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/delete.php">CIBlockProperty::Delete</a><nobr></nobr><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 * <h4>See Also</h4> 
 * <ul> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/delete.php">CIBlockProperty::Delete</a> </li> <li>
 * <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a> </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockpropertydelete.php
 * @author Bitrix
 */
	public static function OnBeforeIBlockPropertyDelete($intID){}

/**
 * <p>Событие вызывается при построении списка <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/user_properties/index.php">пользовательских свойств</a>. Обработчик этого события должен вернуть массив описывающий пользовательское свойство. Содержимое этого массива описано в методе <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/user_properties/GetUserTypeDescription.php">GetUserTypeDescription</a>.    <br></p>
 *
 *
 * @return array <p>Массив.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/user_properties/index.php">Пользовательские
 * свойства</a></li>     <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/user_properties/GetUserTypeDescription.php">GetUserTypeDescription</a> 
 * </li>  </ul><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/OnIBlockPropertyBuildList.php
 * @author Bitrix
 */
	public static function OnIBlockPropertyBuildList(){}

/**
 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/add.php">CIBlockSection::Add</a> до вставки информационного блока, и может быть использовано для отмены вставки или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fsection">Массив полей</a> нового
 * раздела информационного блока.
 *
 * @return bool <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/add.php">CIBlockSection::Add</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 *             $aMsg[] = array("id"=&gt;"CODE", "text"=&gt;"Введите символьный код.");
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
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblocksectionadd.php">Событие
 * "OnAfterIBlockSectionAdd"</a>   </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/add.php">CIBlockSection::Add</a>   </li> <li> <a
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
 * Событие "OnAfterIBlockSectionAdd" вызывается после попытки добавления нового раздела информационного блока методом <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/add.php">CIBlockSection::Add</a>.
 *
 *
 * @param array &$arFields  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fsection">Массив полей</a> нового
 * раздела информационного блока. Дополнительно, в элементе массива
 * с индексом "RESULT" содержится результат работы (возвращаемое
 * значение) метода <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/add.php">CIBlockSection::Add</a> и, в
 * случае ошибки, элемент с индексом "RESULT_MESSAGE" будет содержать текст
 * ошибки.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblocksectionadd.php">Событие
 * "OnBeforeIBlockSectionAdd"</a>   </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/add.php">CIBlockSection::Add</a>   </li> <li> <a
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
 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/update.php">CIBlockSection::Update</a> до изменения раздела информационного блока, и может быть использовано для отмены изменения или для переопределения некоторых полей.
 *
 *
 * @param array &$arParams  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fsection">Массив полей</a> изменяемого
 * раздела информационного блока.
 *
 * @return bool <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/update.php">CIBlockSection::Update</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 *             $APPLICATION-&gt;throwException("Введите символьный код. (ID:".$arFields["ID"].")");
 *             return false;
 *         }
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblocksectionupdate.php">Событие
 * "OnAfterIBlockSectionUpdate"</a>   </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/update.php">CIBlockSection::Update</a>   </li> <li>
 * <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a> </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblocksectionupdate.php
 * @author Bitrix
 */
	public static function OnBeforeIBlockSectionUpdate(&$arParams){}

/**
 * Событие "OnAfterIBlockSectionUpdate" вызывается после попытки изменения раздела информационного блока методом <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/update.php">CIBlockSection::Update</a>.
 *
 *
 * @param array &$arFields  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fsection">Массив полей</a> изменяемого
 * раздела информационного блока. Дополнительно, в элементе массива
 * с индексом "RESULT" содержится результат работы (возвращаемое
 * значение) метода <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/update.php">CIBlockSection::Update</a> и, в
 * случае ошибки, элемент с индексом "RESULT_MESSAGE" будет содержать текст
 * ошибки.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblocksectionupdate.php">Событие
 * "OnBeforeIBlockSectionUpdate"</a>   </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/update.php">CIBlockSection::Update</a>   </li> <li>
 * <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a> </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblocksectionupdate.php
 * @author Bitrix
 */
	public static function OnAfterIBlockSectionUpdate(&$arFields){}

/**
 * Событие "OnBeforeIBlockSectionDelete" вызывается перед удалением раздела методом <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/delete.php">CIBlockSection::Delete</a>. Как правило задачи обработчика данного события - разрешить или запретить удаление.
 *
 *
 * @param mixed $intID  ID удаляемого раздела.
 *
 * @return bool <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/delete.php">CIBlockSection::Delete</a><nobr></nobr><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/delete.php">CIBlockSection::Delete</a>
 * </li> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493"
 * >Обработка событий</a> </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblocksectiondelete.php
 * @author Bitrix
 */
	public static function OnBeforeIBlockSectionDelete($intID){}

/**
 * после удаления раздела.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/delete.php">Delete</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/index.php
 * @author Bitrix
 */
	public static function OnAfterIBlockSectionDelete(){}

/**
 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/add.php">CIBlockElement::Add</a> до вставки элемента информационного блока, и может быть использовано для отмены вставки или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#felement">Массив полей</a> нового
 * элемента информационного блока.
 *
 * @return bool <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/add.php">CIBlockElement::Add</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 *             $APPLICATION-&gt;throwException("Введите символьный код.");
 *             return false;
 *         }
 *     }
 * }
 * ?&gt;
 * //пример обработчика, который при сохранении элемента переводит в транслит его заголовок, добавляет к заголовку текущую дату (для уникальности) и передает в поле "Символьный код"
 * 
 * // файл /bitrix/php_interface/init.php 
 * / регистрируем обработчик
 * AddEventHandler("iblock", "OnBeforeIBlockElementAdd", Array("CymCode", "OnBeforeIBlockElementAddHandler")); 
 * 
 * class CymCode 
 * { 
 * 
 * // создаем обработчик события "OnBeforeIBlockElementAdd" 
 * function OnBeforeIBlockElementAddHandler(&amp;$arFields) 
 * { 
 *    if(strlen($arFields["CODE"])&lt;=0) 
 *    { 
 *       $arFields["CODE"] = CymCode::imTranslite($arFields["NAME"])."_".date('dmY'); 
 *            log_array($arFields); // убрать после отладки 
 * 
 *       return; 
 *    } 
 *   } 
 * 
 * // записывает все что передадут в /bitrix/log.txt 
 * function log_array() { 
 *    $arArgs = func_get_args(); 
 *    $sResult = ''; 
 *    foreach($arArgs as $arArg) { 
 *       $sResult .= "\n\n".print_r($arArg, true); 
 *    } 
 * 
 *    if(!defined('LOG_FILENAME')) { 
 *       define('LOG_FILENAME', $_SERVER['DOCUMENT_ROOT'].'/bitrix/log.txt'); 
 *    } 
 *    AddMessage2Log($sResult, 'log_array -&gt; '); 
 * } 
 * 
 * function imTranslite($str){ 
 * // транслитерация корректно работает на страницах с любой кодировкой 
 * // ISO 9-95 
 *    static $tbl= array(
 *       'а'=&gt;'a', 'б'=&gt;'b', 'в'=&gt;'v', 'г'=&gt;'g', 'д'=&gt;'d', 'е'=&gt;'e', 'ж'=&gt;'g', 'з'=&gt;'z',
 *       'и'=&gt;'i', 'й'=&gt;'y', 'к'=&gt;'k', 'л'=&gt;'l', 'м'=&gt;'m', 'н'=&gt;'n', 'о'=&gt;'o', 'п'=&gt;'p',
 *       'р'=&gt;'r', 'с'=&gt;'s', 'т'=&gt;'t', 'у'=&gt;'u', 'ф'=&gt;'f', 'ы'=&gt;'y', 'э'=&gt;'e', 'А'=&gt;'A',
 *       'Б'=&gt;'B', 'В'=&gt;'V', 'Г'=&gt;'G', 'Д'=&gt;'D', 'Е'=&gt;'E', 'Ж'=&gt;'G', 'З'=&gt;'Z', 'И'=&gt;'I',
 *       'Й'=&gt;'Y', 'К'=&gt;'K', 'Л'=&gt;'L', 'М'=&gt;'M', 'Н'=&gt;'N', 'О'=&gt;'O', 'П'=&gt;'P', 'Р'=&gt;'R',
 *       'С'=&gt;'S', 'Т'=&gt;'T', 'У'=&gt;'U', 'Ф'=&gt;'F', 'Ы'=&gt;'Y', 'Э'=&gt;'E', 'ё'=&gt;"yo", 'х'=&gt;"h",
 *       'ц'=&gt;"ts", 'ч'=&gt;"ch", 'ш'=&gt;"sh", 'щ'=&gt;"shch", 'ъ'=&gt;"", 'ь'=&gt;"", 'ю'=&gt;"yu", 'я'=&gt;"ya",
 *       'Ё'=&gt;"YO", 'Х'=&gt;"H", 'Ц'=&gt;"TS", 'Ч'=&gt;"CH", 'Ш'=&gt;"SH", 'Щ'=&gt;"SHCH", 'Ъ'=&gt;"", 'Ь'=&gt;"",
 *       'Ю'=&gt;"YU", 'Я'=&gt;"YA", ' '=&gt;"_", '№'=&gt;"", '«'=&gt;"&lt;", '»'=&gt;"&gt;", '—'=&gt;"-" 
 *    ); 
 *     return strtr($str, $tbl); 
 *  } 
 * }
 * 
 * //пропорциональное изменение размеров изображений, добавленных как пользовательское свойство: 
 * AddEventHandler("iblock", "OnBeforeIBlockElementAdd", Array("MyClass", "OnBeforeIBlockElementAddHandler"));
 * 
 * class MyClass
 * {
 *     function OnBeforeIBlockElementAddHandler(&amp;$arFields)
 *     {
 *     foreach($arFields[PROPERTY_VALUES][28] as &amp;$file):
 *        CAllFile::ResizeImage(
 *          $file, 
 *          array("width" =&gt; "200", "height" =&gt; "200"), 
 *          BX_RESIZE_IMAGE_PROPORTIONAL);
 *     endforeach;
 *     }
 * }
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblockelementadd.php">Событие
 * "OnAfterIBlockElementAdd"</a>   </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/add.php">CIBlockElement::Add</a>   </li> <li> <a
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
 * <p>Событие вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/add.php">CIBlockElement::Add</a> до добавления элемента инфоблока перед проверкой правильности заполнения полей.</p>
 *
 *
 * @param array &$arParams  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#felement">Массив полей</a> нового
 * элемента информационного блока.
 *
 * @return bool 
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/add.php">CIBlockElement::Add</a>  
 * </li> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493"
 * >Обработка событий</a> </li> </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/OnStartIBlockElementAdd.php
 * @author Bitrix
 */
	public static function OnStartIBlockElementAdd(&$arParams){}

/**
 * Событие "OnAfterIBlockElementAdd" вызывается после попытки добавления нового элемента информационного блока методом <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/add.php">CIBlockElement::Add</a>.
 *
 *
 * @param array &$arFields  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#felement">Массив полей</a> нового
 * элемента информационного блока. Дополнительно, в элементе
 * массива с индексом "RESULT" содержится результат работы
 * (возвращаемое значение) метода <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/add.php">CIBlockElement::Add</a> и, в
 * случае ошибки, элемент с индексом "RESULT_MESSAGE" будет содержать текст
 * ошибки.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockelementadd.php">Событие
 * "OnBeforeIBlockElementAdd"</a>   </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/add.php">CIBlockElement::Add</a>   </li> <li> <a
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
 * Событие вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/update.php">CIBlockElement::Update</a> до изменения элемента информационного блока, и может быть использовано для отмены изменения или для переопределения некоторых полей.
 *
 *
 * @param array &$arParams  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#felement">Массив полей</a> изменяемого
 * элемента информационного блока.
 *
 * @return bool <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/update.php">CIBlockElement::Update</a><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i><br>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 *             $APPLICATION-&gt;throwException("Введите символьный код. (ID:".$arFields["ID"].")");
 *             return false;
 *         }
 *     }
 * }
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblockelementupdate.php">Событие
 * "OnAfterIBlockElementUpdate"</a>   </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/update.php">CIBlockElement::Update</a>   </li> <li>
 * <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a> </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockelementupdate.php
 * @author Bitrix
 */
	public static function OnBeforeIBlockElementUpdate(&$arParams){}

/**
 * <p>Событие вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/update.php">CIBlockElement::Update</a> до изменения элемента информационного блока перед проверкой правильности заполнения полей, и может быть использовано для переопределения некоторых полей.</p>
 *
 *
 * @param array &$arParams  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#felement">Массив полей</a> изменяемого
 * элемента информационного блока.
 *
 * @return bool <i>OnStartIBlockElementUpdate</i><i>false</i><a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/update.php">CIBlockElement::Update</a><br>
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/update.php">CIBlockElement::Update</a>
 *   </li> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493"
 * >Обработка событий</a> </li> </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/OnStartIBlockElementUpdate.php
 * @author Bitrix
 */
	public static function OnStartIBlockElementUpdate(&$arParams){}

/**
 * Событие "OnAfterIBlockElementUpdate" вызывается после попытки изменения элемента информационного блока методом <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/update.php">CIBlockElement::Update</a>.
 *
 *
 * @param array &$arFields  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#felement">Массив полей</a> изменяемого
 * элемента информационного блока. Дополнительно, в элементе
 * массива с индексом "RESULT" содержится результат работы
 * (возвращаемое значение) метода <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/update.php">CIBlockElement::Update</a> и, в
 * случае ошибки, элемент с индексом "RESULT_MESSAGE" будет содержать текст
 * ошибки.<br><br><div class="note"> <b>Примечание:</b> изменения в массиве
 * <i>arFields</i> не приведут к изменениям в элементе инфоблока.</div>
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockelementupdate.php">Событие
 * "OnBeforeIBlockElementUpdate"</a>   </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/update.php">CIBlockElement::Update</a>   </li> <li>
 * <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a> </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblockelementupdate.php
 * @author Bitrix
 */
	public static function OnAfterIBlockElementUpdate(&$arFields){}

/**
 * Событие "OnBeforeIBlockElementDelete" вызывается перед удалением элемента методом <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/delete.php">CIBlockElement::Delete</a>. Как правило задачи обработчика данного события - разрешить или запретить удаление.
 *
 *
 * @param mixed $intID  ID удаляемого элемента.
 *
 * @return bool <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/delete.php">CIBlockElement::Delete</a><nobr></nobr><nobr>$APPLICATION-&gt;<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">ThrowException()</a></nobr><i>false</i>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/delete.php">CIBlockElement::Delete</a>
 * </li> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493"
 * >Обработка событий</a> </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onbeforeiblockelementdelete.php
 * @author Bitrix
 */
	public static function OnBeforeIBlockElementDelete($intID){}

/**
 * <p>Событие "OnAfterIBlockElementDelete" вызывается после того, как элемент и вся связанная с ним информация были удалены из базы данных.</p>
 *
 *
 * @param array &$arFields  <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#felement">Массив полей</a> элемента
 * информационного блока.
 *
 * @return mixed <p>Отсутствует.</p>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * // файл /bitrix/php_interface/init.php
 * // регистрируем обработчик
 * AddEventHandler("iblock", "OnAfterIBlockElementDelete", Array("MyClass", "OnAfterIBlockElementDeleteHandler"));
 * 
 * class MyClass
 * {
 *     // создаем обработчик события "OnAfterIBlockElementDelete"
 *     function OnAfterIBlockElementDeleteHandler($arFields)
 *     {
 *         // Выполняем какие-либо действия
 *      }
 * }
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblockelementdelete.php
 * @author Bitrix
 */
	public static function OnAfterIBlockElementDelete(&$arFields){}

/**
 * <p>Вызывается в момент удаления элемента информационного блока.</p> <p></p> <div class="note"> <b>Примечание:</b> начиная с версии 15.5.12,  событие вызывается до удаления из таблиц любых данных, связанных с элементом.</div>
 *
 *
 * @param mixed $intID  ID элемента информационного блока.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
	public static function OnIBlockElementDelete($intID){}

/**
 * перед внесением записи в лог.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/update.php">Update</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeEventLog(){}

/**
 * при поиске файла.
 * <i>Вызывается в методе:</i><br>
 * CIBlockElement::__GetFileContent<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/index.php
 * @author Bitrix
 */
	public static function OnSearchGetFileContent(){}

/**
 * при возвращении описания журналу событий
 * <i>Вызывается в методе:</i><br>
 * CEventIBlock::GetAuditTypes<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/index.php
 * @author Bitrix
 */
	public static function GetAuditTypesIblock(){}

/**
 * аналог <a href="/api_help/main/events/onadmincontextmenushow.php">OnAdminContextMenuShow</a> для списка SKU
 * <i>Вызывается в методе:</i><br>
 * CAdminSubContextMenu::Show<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/index.php
 * @author Bitrix
 */
	public static function OnAdminSubContextMenuShow(){}

/**
 * аналог <a href="/api_help/main/events/onadminlistdisplay.php">OnAdminListDisplay</a> для списка SKU
 * <i>Вызывается в методе:</i><br>
 * CAdminSubList::Display<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/index.php
 * @author Bitrix
 */
	public static function OnAdminSubListDisplay(){}

/**
 * <p>Событие "OnAfterIBlockElementSetPropertyValues" вызывается после попытки сохранения значений всех свойств элемента инфоблока методом <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/setpropertyvalues.php">CIBlockElement::SetPropertyValues</a>.</p>
 *
 *
 * @param int $ELEMENT_ID  Код элемента, значения свойств которого необходимо установить.
 *
 * @param int $IBLOCK_ID  Код информационного блока.
 *
 * @param array $PROPERTY_VALUES  Массив значений свойств, в котором коду свойства ставится в
 * соответствие значение свойства.
 *
 * @param string $PROPERTY_CODE  Код изменяемого свойства.
 *
 * @return mixed <p>Нет.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/setpropertyvalues.php">CIBlockElement::SetPropertyValues</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblockelementsetpropertyvalues.php
 * @author Bitrix
 */
	public static function OnAfterIBlockElementSetPropertyValues($ELEMENT_ID, $IBLOCK_ID, $PROPERTY_VALUES, $PROPERTY_CODE){}

/**
 * <p>Событие "OnAfterIBlockElementSetPropertyValuesEx" вызывается после попытки сохранения значений свойств элемента инфоблока методом <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/setpropertyvaluesex.php">CIBlockElement::SetPropertyValuesEx</a>.</p>
 *
 *
 * @param int $ELEMENT_ID  Код элемента, значения свойств которого необходимо установить.
 *
 * @param int $IBLOCK_ID  Код информационного блока.
 *
 * @param array $PROPERTY_VALUES  Массив значений свойств, в котором коду свойства ставится в
 * соответствие значение свойства.
 *
 * @param array $FLAGS  Массив параметров для оптимизации запросов к БД. Может содержать
 * следующие ключи: 	<ul> <li>NewElement - можно указать если заведомо
 * известно, что значений свойств у данного элемента нет. Экономит
 * один запрос к БД.</li>           <li>DoNotValidateLists - для свойств типа "список"
 * отключает проверку наличия значений в метаданных свойства.</li>      
 *    </ul>
 *
 * @return mixed <p>Нет.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/setpropertyvaluesex.php">CIBlockElement::SetPropertyValuesEx</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/onafteriblockelementsetpropertyvaluesex.php
 * @author Bitrix
 */
	public static function OnAfterIBlockElementSetPropertyValuesEx($ELEMENT_ID, $IBLOCK_ID, $PROPERTY_VALUES, $FLAGS){}

/**
 * <p>Событие <i>OnIBlockElementAdd</i> вызывается в момент добавления элемента информационного блока.</p> <p>Событие вызывается в момент, когда уже отработали все обработчики, изменяющие данные, а также уже произошла проверка данных и идет запись в базу. Изменять данные событие не позволяет. Основной сценарий использования - выполнить некий код перед работой с базой, будучи уверенным, что данные в базе будут изменены. </p>
 *
 *
 * @param array $arFields  <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/add.php">Массив значений
 * полей</a> элемента инфоблока.
 *
 * @return mixed <p>Нет.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/add.php">CIBlockElement::Add</a> </li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/oniblockelementadd.php
 * @author Bitrix
 */
	public static function OnIBlockElementAdd($arFields){}

/**
 * <p>Событие <i>OnIBlockElementUpdate</i> вызывается в момент изменения элемента информационного блока.</p> <p>Событие вызывается в момент, когда уже отработали все обработчики, изменяющие данные, а также уже произошла проверка данных и идет запись в базу. Изменять данные событие не позволяет. Основной сценарий использования - выполнить некий код перед работой с базой, будучи уверенным, что данные в базе будут изменены. </p>
 *
 *
 * @param array $newFields  Массив обновляемых полей и свойств элемента инфоблока.
 *
 * @param array $ar_wf_element  Текущие значения обновляемых полей, а также свойств элемента,
 * если инфоблок участвует в документообороте.
 *
 * @return mixed <p>Нет.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/update.php">CIBlockElement::Update</a>
 * </li> </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/oniblockelementupdate.php
 * @author Bitrix
 */
	public static function OnIBlockElementUpdate($newFields, $ar_wf_element){}

/**
 * <p>Событие <i>OnIBlockElementSetPropertyValues</i> вызывается в момент сохранения значений свойств элемента инфоблока.</p> <p>Событие вызывается в момент, когда уже отработали все обработчики, изменяющие данные, а также уже произошла проверка данных и идет запись в базу. Изменять данные событие не позволяет. Основной сценарий использования - выполнить некий код перед работой с базой, будучи уверенным, что данные в базе будут изменены. </p>
 *
 *
 * @param int $ELEMENT_ID  Идентификатор элемента инфоблока.
 *
 * @param int $IBLOCK_ID  Идентификатор инфоблока.
 *
 * @param array $PROPERTY_VALUES  <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/setpropertyvalues.php">Массив
 * значений свойств</a> элемента инфоблока.
 *
 * @param string $PROPERTY_CODE  Код изменяемого свойства. Если этот параметр отличен от <i>false</i>,
 * то изменяется только свойство с таким кодом.
 *
 * @param array $ar_prop  Массив, описывающий активные свойства инфоблока.
 *
 * @param array $arDBProps  Текущие значения свойств элемента.
 *
 * @return mixed <p>Нет.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/setpropertyvalues.php">CIBlockElement::SetPropertyValues</a>
 * </li> </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/events/oniblockelementsetpropertyvalues.php
 * @author Bitrix
 */
	public static function OnIBlockElementSetPropertyValues($ELEMENT_ID, $IBLOCK_ID, $PROPERTY_VALUES, $PROPERTY_CODE, $ar_prop, $arDBProps){}


}
?>