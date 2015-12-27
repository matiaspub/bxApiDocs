<?
/**
 * 
 * Класс-контейнер событий модуля <b>im</b>
 * 
 */
class _CEventsIm {
/**
 * Вызывается после подтверждения уведомления
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CIMNotify::Confirm<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnAfterConfirmNotify(){}

/**
 * Вызывается после получения контакт листа
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CIMContactList::GetList<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnAfterContactListGetList(){}

/**
 * Вызывается после удаления сообщения
 * </html
 * <i>Вызывается в методе:</i><br>
 * CIMMessage::Delete<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnAfterDeleteMessage(){}

/**
 * Вызывается после удаления уведомления
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CIMNotify::DeleteWithCheck<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnAfterDeleteNotify(){}

/**
 * Вызывается после добавления сообщения
 * 
 * <i>Вызывается в методе:</i><br>
 * CIMMessenger::Add<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnAfterMessagesAdd(){}

/**
 * Вызывается после добавления уведомления
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CIMMessenger::Add<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnAfterNotifyAdd(){}

/**
 * Вызывается перед подтверждением уведомления
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CIMNotify::Confirm<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeConfirmNotify(){}

/**
 * <p>Результат:</p> <p><img src="/upload/api_help/main/OnBeforeMessageNotifyAdd.png"></p> <br><br>
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * AddEventHandler("im", "OnBeforeMessageNotifyAdd", "___OnBeforeMessageNotifyAdd");
 * function ___OnBeforeMessageNotifyAdd($arFields)
 * {
 *     global $USER;
 * 
 *     if(!$USER-&gt;IsAdmin() &amp;&amp; $arFields['MESSAGE_TYPE'] == 'P')
 *     {
 *         $imMaxMessagePerDay = 10;
 * 
 *         $date = date('Ymd');
 *         $_SESSION['IM_ANTI_SPAM'][$date]++;
 *         if ($_SESSION['IM_ANTI_SPAM'][$date] &gt; $imMaxMessagePerDay)
 *         {
 *             return Array(
 *                 'reason' =&gt; 'Вы не можете отправлять более 10 сообщений в день',
 *                 'result' =&gt; false,
 *             );
 *         }
 *     }
 * }
 * 
 * Результат:<img src="/upload/api_help/main/OnBeforeMessageNotifyAdd.png">
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/im/events/onbeforemessagenotifyadd.php
 * @author Bitrix
 */
	public static function OnBeforeMessageNotifyAdd(){}


}
?>