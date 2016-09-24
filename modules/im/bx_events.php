<?
/**
 * 
 * Класс-контейнер событий модуля <b>im</b>
 * 
 */
class _CEventsIm {
/**
 * после добавления сообщения
 * <i>Вызывается в методе:</i><br>
 * CIMMessenger::Add<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnAfterMessagesAdd(){}

/**
 * перед добавлением сообщения
 * <i>Вызывается в методе:</i><br>
 * CIMMessage::Add<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeMessagesAdd(){}

/**
 * после подтверждения уведомления
 * <i>Вызывается в методе:</i><br>
 * CIMNotify::Confirm<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnAfterConfirmNotify(){}

/**
 * перед подтверждением уведомления
 * <i>Вызывается в методе:</i><br>
 * CIMNotify::Confirm<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeConfirmNotify(){}

/**
 * после удаления уведомления
 * <i>Вызывается в методе:</i><br>
 * CIMNotify::DeleteWithCheck<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnAfterDeleteNotify(){}

/**
 * после добавления уведомления
 * <i>Вызывается в методе:</i><br>
 * CIMMessenger::Add<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnAfterNotifyAdd(){}

/**
 * после удаления сообщения
 * <i>Вызывается в методе:</i><br>
 * CIMMessage::Delete<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnAfterDeleteMessage(){}

/**
 * после получения контакт листа
 * <i>Вызывается в методе:</i><br>
 * CIMContactList::GetList<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnAfterContactListGetList(){}

/**
 * <p>Результат:</p> <p><img src="//opt-560835.ssl.1c-bitrix-cdn.ru/upload/api_help/main/OnBeforeMessageNotifyAdd.png?145018767331985"></p> <br><br>
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 * }Результат:<img src="//opt-560835.ssl.1c-bitrix-cdn.ru/upload/api_help/main/OnBeforeMessageNotifyAdd.png?145018767331985">
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/im/events/onbeforemessagenotifyadd.php
 * @author Bitrix
 */
	public static function OnBeforeMessageNotifyAdd(){}

/**
 * после редактирования сообщения
 * <i>Вызывается в методе:</i><br>
 * CIMMessenger::Update<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnAfterMessagesUpdate(){}

/**
 * после удаления сообщения
 * <i>Вызывается в методе:</i><br>
 * CIMMessenger::Delete<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnAfterMessagesDelete(){}

/**
 * после загрузки файла
 * <i>Вызывается в методе:</i><br>
 * CIMDisk::UploadFile<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/im/events/index.php
 * @author Bitrix
 */
	public static function OnAfterFileUpload(){}

/**
 * Событие вызывается после прочтения чата.
 *
 *
 * @param array $arFields  Массив параметров чата.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/im/events/onafterchatread.php
 * @author Bitrix
 */
	public static function OnAfterChatRead($arFields){}


}
?>