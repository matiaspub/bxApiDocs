<?
/**
 * 
 * Класс-контейнер событий модуля <b>forum</b>
 * 
 */
class _CEventsForum {
/**
 * <i>Вызывается в методе:</i><br>
 * CEventForum::GetAuditTypes<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function GetAuditTypesForum(){}

/**
 * после удаления форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumnew/delete.php">CForumNew::Delete</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function OnAfterForumDelete(){}

/**
 * после добавления форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumnew/add.php">CForumNew::Add</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onAfterForumAdd(){}

/**
 * после редактирования форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumnew/update.php">CForumNew::Update</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onAfterForumUpdate(){}

/**
 * после добавления группы форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumgroup/add.php">CForumGroup::Add</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onAfterGroupForumsAdd(){}

/**
 * после редактирования группы форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumgroup/update.php">CForumGroup::Update</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onAfterGroupForumsUpdate(){}

/**
 * после добавления сообщения форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforummessage/add.php">CForumMessage::Add</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onAfterMessageAdd(){}

/**
 * после удаления сообщения форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforummessage/delete.php">CForumMessage::Delete</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onAfterMessageDelete(){}

/**
 * после редактирования сообщения форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforummessage/update.php">CForumMessage::Update</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onAfterMessageUpdate(){}

/**
 * после копирования персонального сообщения.
 * <i>Вызывается в методе:</i><br>
 * CForumPrivateMessage::Copy<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onAfterPMCopy(){}

/**
 * после отправки персонального сообщения.
 * <i>Вызывается в методе:</i><br>
 * CForumPrivateMessage::Send<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onAfterPMSend(){}

/**
 * после добавления темы форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/add.php">CForumTopic::Add</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onAfterTopicAdd(){}

/**
 * после удаления темы форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/delete.php">CForumTopic::Delete</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onAfterTopicDelete(){}

/**
 * после редактирования темы форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/update.php">CForumTopic::Update</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onAfterTopicUpdate(){}

/**
 * после добавления пользователя форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuser/add.php">CForumUser::Add</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onAfterUserAdd(){}

/**
 * после удаления пользователя форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuser/delete.php">CForumUser::Delete</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onAfterUserDelete(){}

/**
 * после редактирования пользователя форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuser/update.php">CForumUser::Update</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onAfterUserUpdate(){}

/**
 * перед добавлением форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumnew/add.php">CForumNew::Add</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforeForumAdd(){}

/**
 * перед удалением форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumnew/delete.php">CForumNew::Delete</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeForumDelete(){}

/**
 * перед редактированием форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumnew/update.php">CForumNew::Update</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforeForumUpdate(){}

/**
 * перед созданием группы форумов.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumgroup/add.php">CForumGroup::Add</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforeGroupForumsAdd(){}

/**
 * перед редактированием группы форумов.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumgroup/update.php">CForumGroup::Update</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforeGroupForumsUpdate(){}

/**
 * перед отправкой сообщения на почту.
 * <i>Вызывается в методе:</i><br>
 * CForumMessage::SendMailMessage<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforeMailMessageSend(){}

/**
 * перед добавлением сообщения форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforummessage/add.php">CForumMessage::Add</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforeMessageAdd(){}

/**
 * перед удалением сообщения форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforummessage/delete.php">CForumMessage::Delete</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforeMessageDelete(){}

/**
 * перед редактированием сообщения форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforummessage/update.php">CForumMessage::Update</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforeMessageUpdate(){}

/**
 * перед копированием персонального сообщения.
 * <i>Вызывается в методе:</i><br>
 * CForumPrivateMessage::Copy<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforePMCopy(){}

/**
 * перед удалением персонального сообщения.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumprivatemessage/delete.php">CForumPrivateMessage::Delete</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforePMDelete(){}

/**
 * перед чтением персонального сообщения.
 * <i>Вызывается в методе:</i><br>
 * CForumPrivateMessage::MakeRead<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforePMMakeRead(){}

/**
 * перед отправкой персонального сообщения.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumprivatemessage/send.php">CForumPrivateMessage::Send</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforePMSend(){}

/**
 * перед редактированием персонального сообщения.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumprivatemessage/update.php">CForumPrivateMessage::Update</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforePMUpdate(){}

/**
 * перед добавлением темы форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/add.php">CForumTopic::Add</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforeTopicAdd(){}

/**
 * перед удалением темы форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/delete.php">CForumTopic::Delete</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforeTopicDelete(){}

/**
 * перед редактированием темы форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/update.php">CForumTopic::Update</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforeTopicUpdate(){}

/**
 * перед добавлением пользователя форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuser/add.php">CForumUser::Add</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforeUserAdd(){}

/**
 * перед удалением пользователя форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuser/delete.php">CForumUser::Delete</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforeUserDelete(){}

/**
 * перед редактированием пользователя форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuser/update.php">CForumUser::Update</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onBeforeUserUpdate(){}

/**
 * при удалении форума.
 * <i>Вызывается в методе:</i><br>
 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumnew/delete.php">CForumNew::Delete</a><br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function OnForumDelete(){}

/**
 * при модерировании сообщения форума.
 * <i>Вызывается в методе:</i><br>
 * ForumModerateMessage<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/forum/events/index.php
 * @author Bitrix
 */
	public static function onMessageModerate(){}


}
?>