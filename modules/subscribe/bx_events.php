<?
/**
 * 
 * Класс-контейнер событий модуля <b>subscribe</b>
 * 
 */
class _CEventsSubscribe {
/**
 * Событие "BeforePostingSendMail" вызывается перед отправкой выпуска из метода <a href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/cposting/cpostingsendmessage.php">CPosting::SendMessage</a>.
 *
 *
 * @param array $arFields  Массив следующего содержания: <ul> <li>POSTING_ID - идентификатор
 * выпуска.</li> <li>EMAIL - адрес на который будет отправлен выпуск.</li>
 * <li>SUBJECT - заголовок письма (в кодированном виде, если установлена
 * соответсветствующая настройка модуля).</li> <li>BODY - тело письма уже
 * отформатированное в соответствии со стандартом MIME.</li> <li>HEADER -
 * служебные заголовки.</li> <li>EMAIL_EX - расширенная информация о
 * получателе, см. <a
 * href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/cposting/cpostingfields.php">Поля CPosting.</a> </li> </ul>
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?<br>// файл /bitrix/php_interface/init.php<br>// регистрируем обработчик<br>AddEventHandler("subscribe", "<b>BeforePostingSendMail</b>", Array("MyClass", "BeforePostingSendMailHandler"));<br>
 * class MyClass
 * {
 * 	// создаем обработчик события "BeforePostingSendMail"
 * 	function BeforePostingSendMailHandler($arFields)
 * 	{
 * 		$USER_NAME = "Подписчик";
 * 		//Попробуем найти подписчика.
 * 		$rs = CSubscription::GetByEmail($arFields["EMAIL"]);
 * 		if($ar = $rs-&gt;Fetch())
 * 		{
 * 			if(intval($ar["USER_ID"]) &gt; 0)
 * 			{
 * 				$rsUser = CUser::GetByID($ar["USER_ID"]);
 * 				if($arUser = $rsUser-&gt;Fetch())
 * 				{
 * 					$USER_NAME = $arUser["NAME"]." ".$arUser["LAST_NAME"];
 * 				}
 * 			}
 * 		}
 * 		$arFields["BODY"] = str_replace("#NAME#", $USER_NAME, $arFields["BODY"]);<br>		return $arFields;<br>	}<br>}<br>?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/cposting/cpostingsendmessage.php">CPosting::SendMessage</a></li>
 * <li><a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3493" >Обработка
 * событий</a></li> </ul> </htm<a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/subscribe/events/beforepostingsendmail.php
 * @author Bitrix
 */
	public static function BeforePostingSendMail($arFields){}

/**
 * Вызывается при удалении сообщения.
 * </htm
 * <i>Вызывается в методе:</i><br>
 * subscribe::UnInstallDB<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/subscribe/events/index.php
 * @author Bitrix
 */
	public static function OnEventMessageDelete(){}

/**
 * Вызывается перед удалением подписки.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CSubscriptionGeneral::Delete<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/subscribe/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeSubscriptionDelete(){}

/**
 * Вызывается после удаления подписки.
 * 
 * <i>Вызывается в методе:</i><br>
 * CSubscriptionGeneral::Delete<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/subscribe/events/index.php
 * @author Bitrix
 */
	public static function OnAfterSubscriptionDelete(){}

/**
 * Вызывается при обновлении подписки.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CSubscriptionGeneral::CheckFields<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/subscribe/events/index.php
 * @author Bitrix
 */
	public static function OnStartSubscriptionUpdate(){}

/**
 * Вызывается перед обновлением подписки.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CSubscriptionGeneral::CheckFields<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/subscribe/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeSubscriptionUpdate(){}

/**
 * Вызывается при добавлении подписки.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CSubscriptionGeneral::CheckFields<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/subscribe/events/index.php
 * @author Bitrix
 */
	public static function OnStartSubscriptionAdd(){}

/**
 * Вызывается перед добавлением подписки.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CSubscriptionGeneral::CheckFields<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/subscribe/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeSubscriptionAdd(){}


}
?>