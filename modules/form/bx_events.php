<?
/**
 * 
 * Класс-контейнер событий модуля <b>form</b>
 * 
 */
class _CEventsForm {
/**
 * Вызывается после добавления сервера CRM, с которым можно связать форму.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CFormCrm::Add<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/form/events/index.php
 * @author Bitrix
 */
	public static function OnAfterFormCrmAdd(){}

/**
 * Вызывается после удаления сервера CRM, с которым может быть связана форма.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CFormCrm::Delete<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/form/events/index.php
 * @author Bitrix
 */
	public static function OnAfterFormCrmDelete(){}

/**
 * Вызывается после обновления сервера CRM, с которым может быть связана форма.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CFormCrm::Update<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/form/events/index.php
 * @author Bitrix
 */
	public static function OnAfterFormCrmUpdate(){}

/**
 * Вызывается перед добавлением сервера CRM, с которым может быть связана форма.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CFormCrm::Add<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/form/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeFormCrmAdd(){}

/**
 * Вызывается перед удалением сервера CRM, с которым может быть связана форма.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CFormCrm::Delete<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/form/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeFormCrmDelete(){}

/**
 * Вызывается перед обновлением сервера CRM, с которым может быть связана форма.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CFormCrm::Update<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/form/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeFormCrmUpdate(){}

/**
 * Обработчики события вызываются перед добавлением нового результата веб-формы. Может быть использовано для каких-либо дополнительных проверок или изменения значения полей результата веб-формы. Возврат обработчиком каких-либо значений не предполагается. Ошибки нужно возвращать посредством $APPLICATION-&gt;ThrowException().
 *
 *
 * @param int $WEB_FORM_ID  ID веб-формы.</bod
 *
 * @param array &$arFields  Массив полей результата для записи в БД.
 *
 * @param array &$arrVALUES  Массив значений ответов результата веб-формы.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * function my_onBeforeResultAdd($WEB_FORM_ID, &amp;$arFields, &amp;$arrVALUES)
 * {
 *   global $APPLICATION;
 *   
 *   // действие обработчика распространяется только на форму с ID=6
 *   if ($WEB_FORM_ID == 6) 
 *   {
 *     // в текстовый вопрос с ID=135 должен содержать целое число, большее 5ти.
 *     $arrVALUES['form_text_135'] = intval($arrVALUES['form_text_135']);
 *     if ($arrVALUES['form_text_135'] &lt; 5)
 *     {
 *       // если значение не подходит - отправим ошибку.
 *       $APPLICATION-&gt;ThrowException('Значение должно быть больше или равно 5!');
 *     }
 *   }
 * }
 * AddEventHandler('form', 'onBeforeResultAdd', 'my_onBeforeResultAdd');
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/events/onafterresultadd.php">Событие
 * "onAfterResultAdd"</a> </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/form/events/onbeforeresultupdate.php">Событие "onBeforeResultUpdate"</a>
 * </li> </ul> <a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/events/onbeforeresultadd.php
 * @author Bitrix
 */
	public static function onBeforeResultAdd($WEB_FORM_ID, &$arFields, &$arrVALUES){}

/**
 * Обработчики события вызываются после добавления нового результата веб-формы. Может быть использовано для совершения каких-либо дополнительных операций с результатом веб-формы, например, для рассылки дополнительных уведомлений посредством электронной почты. Для изменения полей результата веб-формы стоит использовать CFormResult::SetField(). Возврат обработчиком каких-либо значений не предполагается.
 *
 *
 * @param int $WEB_FORM_ID  ID веб-формы.</bod
 *
 * @param int $RESULT_ID  ID результата.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * //обработчик должен быть зарегистрирован в файле /bitrix/php_interface/init.php
 * 
 * function my_onAfterResultAddUpdate($WEB_FORM_ID, $RESULT_ID)
 * {
 *   // действие обработчика распространяется только на форму с ID=6
 *   if ($WEB_FORM_ID == 6) 
 *   {
 *     // запишем в дополнительное поле 'user_ip' IP-адрес пользователя
 *     CFormResult::SetField($RESULT_ID, 'user_ip', $_SERVER["REMOTE_ADDR"]);
 *   }
 * }
 * 
 * // зарегистрируем функцию как обработчик двух событий
 * AddEventHandler('form', 'onAfterResultAdd', 'my_onAfterResultAddUpdate');
 * AddEventHandler('form', 'onAfterResultUpdate', 'my_onAfterResultAddUpdate');
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/events/onbeforeresultadd.php">Событие
 * "onBeforeResultAdd"</a> </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/form/events/onafterresultupdate.php">Событие "onAfterResultUpdate"</a>
 * </li> </ul> <a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/events/onafterresultadd.php
 * @author Bitrix
 */
	public static function onAfterResultAdd($WEB_FORM_ID, $RESULT_ID){}

/**
 * Обработчики события вызываются перед сохранением изменений существующего результата веб-формы. Может быть использовано для каких-либо дополнительных проверок или изменения значения полей результата веб-формы. Возврат обработчиком каких-либо значений не предполагается. Ошибки можно возвращать посредством $APPLICATION-&gt;ThrowException().
 *
 *
 * @param int $WEB_FORM_ID  <span class="syntax">ID веб-формы</span>.</bod
 *
 * @param int $RESULT_ID  <span class="syntax">ID результата</span>.
 *
 * @param array &$arFields  <span class="syntax">Массив полей результата для записи в БД</span>.
 *
 * @param array &$arrVALUES  <span class="syntax">Массив значений ответов результата веб-формы</span>.
 *
 * @param string(1) $CHECK_RIGHTS  <span class="syntax">Флаг "Проверять права" (Y|N).</span> </htm
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * function my_onBeforeResultUpdate($WEB_FORM_ID, $RESULT_ID, $arFields, $arrVALUES)
 * {
 *   global $APPLICATION;
 *   
 *   // действие обработчика распространяется только на форму с ID=6
 *   if ($WEB_FORM_ID == 6) 
 *   {
 *     // в текстовый вопрос с ID=135 должен содержать целое число, большее 5ти.
 *     $arrVALUES['form_text_135'] = intval($arrVALUES['form_text_135']);
 *     if ($arrVALUES['form_text_135'] &lt; 5)
 *     {
 *       // если значение не подходит - отправим ошибку.
 *       $APPLICATION-&gt;ThrowException('Значение должно быть больше или равно 5!');
 *     }
 *   }
 * }
 * AddEventHandler('form', 'onBeforeResultUpdate', 'my_onBeforeResultUpdate');
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/form/events/onafterresultupdate.php">Событие
 * "onAfterResultUpdate"</a> </li></ul></bod<a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/events/onbeforeresultupdate.php
 * @author Bitrix
 */
	public static function onBeforeResultUpdate($WEB_FORM_ID, $RESULT_ID, &$arFields, &$arrVALUES, $CHECK_RIGHTS){}

/**
 * Обработчики события вызываются после сохранения изменений результата веб-формы. Может быть использовано для совершения каких-либо дополнительных операций с результатом веб-формы, например, для рассылки дополнительных уведомлений посредством электронной почты. Возврат обработчиком каких-либо значений не предполагается. Для изменения полей результата веб-формы стоит использовать CFormResult::SetField().
 *
 *
 * @param int $WEB_FORM_ID  <span class="syntax">ID веб-формы</span>.</bod
 *
 * @param int $RESULT_ID  <span class="syntax">ID результата</span>.
 *
 * @param string(1) $CHECK_RIGHTS  <span class="syntax">Флаг "Проверять права" (Y|N).</span> </htm
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * function my_onAfterResultAddUpdate($WEB_FORM_ID, $RESULT_ID)
 * {
 *   // действие обработчика распространяется только на форму с ID=6
 *   if ($WEB_FORM_ID == 6) 
 *   {
 *     // запишем в дополнительное поле 'user_ip' IP-адрес пользователя
 *     CFormResult::SetField($RESULT_ID, 'user_ip', $_SERVER["REMOTE_ADDR"]);
 *   }
 * }
 * 
 * // зарегистрируем функцию как обработчик двух событий
 * AddEventHandler('form', 'onAfterResultAdd', 'my_onAfterResultAddUpdate');
 * AddEventHandler('form', 'onAfterResultUpdate', 'my_onAfterResultAddUpdate');
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/events/onbeforeresultupdate.php">Событие
 * "onBeforeResultUpdate"</a> </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/form/events/onafterresultadd.php">Событие "onAfterResultAdd"</a> </li>
 * </ul> <a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/events/onafterresultupdate.php
 * @author Bitrix
 */
	public static function onAfterResultUpdate($WEB_FORM_ID, $RESULT_ID, $CHECK_RIGHTS){}

/**
 * Обработчики события вызываются перед удалением результата веб-формы. Может быть использовано, например, для рассылки дополнительных уведомлений посредством электронной почты или для запрета удаления результата. Возврат обработчиком каких-либо значений не предполагается. Ошибки можно возвращать посредством $APPLICATION-&gt;ThrowException().
 *
 *
 * @param int $WEB_FORM_ID  <span class="syntax">ID веб-формы</span>.</bod
 *
 * @param int $RESULT_ID  <span class="syntax">ID результата</span>.
 *
 * @param string(1) $CHECK_RIGHTS  <span class="syntax">Флаг "Проверять права" (Y|N).</span> </htm
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * function my_onBeforeResultDelete($WEB_FORM_ID, $RESULT_ID, $CHECK_RIGHTS)
 * {
 *   global $APPLICATION;
 *   
 *   // действие обработчика распространяется только на форму с ID=6
 *   if ($WEB_FORM_ID == 6 &amp;&amp; $RESULT_ID == 1) 
 *   {
 *       $APPLICATION-&gt;ThrowException('Этот результат нельзя удалить!');
 *   }
 * }
 * 
 * // зарегистрируем функцию как обработчик события
 * AddEventHandler('form', 'onBeforeResultDelete', 'my_onBeforeResultDelete');
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/events/onbeforeresultdelete.php
 * @author Bitrix
 */
	public static function onBeforeResultDelete($WEB_FORM_ID, $RESULT_ID, $CHECK_RIGHTS){}

/**
 * Обработчики события вызываются перед изменением статуса результата веб-формы. Может быть использовано для каких-либо дополнительных проверок или даже для изменения нового статуса, а также, как замена обработчика статуса веб-формы. Возврат обработчиком каких-либо значений не предполагается. Ошибки можно возвращать посредством $APPLICATION-&gt;ThrowException().
 *
 *
 * @param int $WEB_FORM_ID  <span class="syntax">ID веб-формы</span>.</bod
 *
 * @param int $RESULT_ID  <span class="syntax">ID результата</span>.
 *
 * @param int &$NEW_STATUS_ID  ID статуса.
 *
 * @param string(1) $CHECK_RIGHTS  <span class="syntax">Флаг "Проверять права" (Y|N).</span> </htm
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * function my_onBeforeResultStatusChange($WEB_FORM_ID, $RESULT_ID, $NEW_STATUS_ID, $CHECK_RIGHTS)
 * {
 *   global $USER;
 *   
 *   // действие обработчика распространяется только на форму с ID=6
 *   if ($WEB_FORM_ID == 6) 
 *   {
 *     // 1 - статус "в проверке" (по умолчанию), 2 - статус "принято"
 *     // результатам, присланным пользователем с правами администратора 
 *     // автоматически присвоим статус "принято".
 *     if ($USER-&gt;IsAdmin() &amp;&amp; $NEW_STATUS_ID == 1)
 *       $NEW_STATUS_ID = 2;
 *   }
 * }
 * 
 * // зарегистрируем функцию как обработчик события
 * AddEventHandler('form', 'onBeforeResultStatusChange', 'my_onBeforeResultStatusChange');
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/form/events/onafterresultstatuschange.php">Событие
 * "onAfterResultStatusChange"</a> </li></ul></bod<a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/events/onbeforeresultstatuschange.php
 * @author Bitrix
 */
	public static function onBeforeResultStatusChange($WEB_FORM_ID, $RESULT_ID, &$NEW_STATUS_ID, $CHECK_RIGHTS){}

/**
 * Обработчики события вызываются после изменения статуса результата веб-формы. Может быть использовано, например, для каких-либо дополнительных уведомлений посредством электронной почты, а также, как замена обработчика статуса веб-формы. Возврат обработчиком каких-либо значений не предполагается. Для изменения полей результата веб-формы стоит использовать CFormResult::SetField().
 *
 *
 * @param int $WEB_FORM_ID  <span class="syntax">ID веб-формы</span>.</bod
 *
 * @param int $RESULT_ID  <span class="syntax">ID результата</span>.
 *
 * @param int $NEW_STATUS_ID  ID статуса.
 *
 * @param string(1) $CHECK_RIGHTS  <span class="syntax">Флаг "Проверять права" (Y|N).</span> </htm
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * function my_onAfterResultStatusChange($WEB_FORM_ID, $RESULT_ID, $NEW_STATUS_ID, $CHECK_RIGHTS)
 * {
 *   global $USER;
 *   
 *   // действие обработчика распространяется только на форму с ID=6
 *   if ($WEB_FORM_ID == 6) 
 *   {
 *     // 1 - статус "в проверке" (по умолчанию), 2 - статус "принято"
 *     // запишем в скрытое поле 'status_user' идентификатор пользователя, 
 *     // совершившего изменение статуса.
 *     if ($NEW_STATUS_ID == 2)
 *       CFormResult::SetField($RESULT_ID, 'status_user', $USER-&gt;ID);
 *   }
 * }
 * 
 * // зарегистрируем функцию как обработчик события
 * AddEventHandler('form', 'onAfterResultStatusChange', 'my_onAfterResultStatusChange');
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/form/events/onbeforeresultstatuschange.php">Событие
 * "onBeforeResultStatusChange"</a> </li></ul></bod<a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/events/onafterresultstatuschange.php
 * @author Bitrix
 */
	public static function onAfterResultStatusChange($WEB_FORM_ID, $RESULT_ID, $NEW_STATUS_ID, $CHECK_RIGHTS){}

/**
 * Вызывается при сборе списка кастомных валидаторов полей формы.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformvalidator/getalllist.php">CFormValidator::GetAllList</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/form/events/index.php
 * @author Bitrix
 */
	public static function onFormValidatorBuildList(){}


}
?>