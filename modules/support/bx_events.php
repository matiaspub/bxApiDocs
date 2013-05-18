<?
/**
 * 
 * Класс-контейнер событий модуля <b>support</b>
 * 
 */
class _CEventsSupport {
	/**
	 * <span style="border-collapse: separate;" class="Apple-style-span"> 
	 *           <p>В массиве находятся параметры события, переданные в обработчик события.</p>
	 *          </span><span style="white-space: pre; border-collapse: separate;" class="Apple-style-span">Пример массива:</span><span style="border-collapse: separate;" class="Apple-style-span"> </span> 
	 *         <br><pre>Array
	 * (
	 *     [ID] =&gt; 1
	 *     [LANGUAGE] =&gt; ru
	 *     [LANGUAGE_ID] =&gt; ru
	 *     [WHAT_CHANGE] =&gt; &lt; добавлено сообщение &gt;
	 * 
	 *     [DATE_CREATE] =&gt; 24.07.2011 19:22:38
	 *     [TIMESTAMP] =&gt; 24.07.2011 20:19:57
	 *     [DATE_CLOSE] =&gt; 
	 *     [TITLE] =&gt; Моё первое сообщение
	 *     [STATUS] =&gt; В стадии решения
	 *     [DIFFICULTY] =&gt; Средний
	 *     [CATEGORY] =&gt; Общие вопросы
	 *     [CRITICALITY] =&gt; Высокая
	 *     [RATE] =&gt; 
	 *     [SLA] =&gt; По умолчанию
	 *     [SOURCE] =&gt; 
	 *     [MESSAGES_AMOUNT] =&gt; 10
	 *     [SPAM_MARK] =&gt; 
	 *     [ADMIN_EDIT_URL] =&gt; /bitrix/admin/ticket_edit.php
	 *     [PUBLIC_EDIT_URL] =&gt; /communication/support/
	 *     [OWNER_EMAIL] =&gt; mifd@dfdf.ru
	 *     [OWNER_USER_ID] =&gt; 2
	 *     [OWNER_USER_NAME] =&gt; Василий Петров
	 *     [OWNER_USER_LOGIN] =&gt; bx_test
	 *     [OWNER_USER_EMAIL] =&gt; mifd@dfdf.ru
	 *     [OWNER_TEXT] =&gt; [2] (bx_test) Василий Петров
	 *     [OWNER_SID] =&gt; 
	 *     [SUPPORT_EMAIL] =&gt; my@email.com
	 *     [RESPONSIBLE_USER_ID] =&gt; 
	 *     [RESPONSIBLE_USER_NAME] =&gt;  
	 *     [RESPONSIBLE_USER_LOGIN] =&gt; 
	 *     [RESPONSIBLE_USER_EMAIL] =&gt; 
	 *     [RESPONSIBLE_TEXT] =&gt; 
	 *     [SUPPORT_ADMIN_EMAIL] =&gt; 
	 *     [CREATED_USER_ID] =&gt; 2
	 *     [CREATED_USER_LOGIN] =&gt; bx_test
	 *     [CREATED_USER_EMAIL] =&gt; mifd@dfdf.ru
	 *     [CREATED_USER_NAME] =&gt; Василий Петров
	 *     [CREATED_MODULE_NAME] =&gt; 
	 *     [CREATED_TEXT] =&gt; [2] (bx_test) Василий Петров
	 *     [MODIFIED_USER_ID] =&gt; 2
	 *     [MODIFIED_USER_LOGIN] =&gt; bx_test
	 *     [MODIFIED_USER_EMAIL] =&gt; mifd@dfdf.ru
	 *     [MODIFIED_USER_NAME] =&gt; Василий Петров
	 *     [MODIFIED_MODULE_NAME] =&gt; 
	 *     [MODIFIED_TEXT] =&gt; [2] (bx_test) Василий Петров
	 *     [MESSAGE_AUTHOR_USER_ID] =&gt; 2
	 *     [MESSAGE_AUTHOR_USER_NAME] =&gt; Василий Петров
	 *     [MESSAGE_AUTHOR_USER_LOGIN] =&gt; bx_test
	 *     [MESSAGE_AUTHOR_USER_EMAIL] =&gt; mifd@dfdf.ru
	 *     [MESSAGE_AUTHOR_TEXT] =&gt; [2] (bx_test) Василий Петров
	 *     [MESSAGE_AUTHOR_SID] =&gt; 
	 *     [MESSAGE_SOURCE] =&gt; 
	 *     [MESSAGE_HEADER] =&gt; ======================= СООБЩЕНИЕ ==================================
	 *     [MESSAGE_BODY] =&gt; 
	 * 
	 * Ваше обращение успешно решили.
	 * 
	 *     [MESSAGE_FOOTER] =&gt; ====================================================================
	 *     [FILES_LINKS] =&gt; 
	 *     [IMAGE_LINK] =&gt; 
	 *     [SUPPORT_COMMENTS] =&gt; 
	 * )</pre>
	 *         
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function $arFields(){}

	/**
	 * Содержит true в случае если это сообщение первое в обращении, иначе возвращается false 
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function $is_new(){}


}
?>