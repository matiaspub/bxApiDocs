<?
/**
 * 
 * Класс-контейнер событий модуля <b>forum</b>
 * 
 */
class _CEventsForum {
	/**
	 * <i>Вызывается в методе:</i>
	 * CEventForum::GetAuditTypes
	 */
	public static function GetAuditTypesForum(){}

	/**
	 * после удаления форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumnew/delete.php">CForumNew::Delete</a>
	 */
	public static function OnAfterForumDelete(){}

	/**
	 * после добавления форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumnew/add.php">CForumNew::Add</a>
	 */
	public static function onAfterForumAdd(){}

	/**
	 * после редактирования форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumnew/update.php">CForumNew::Update</a>
	 */
	public static function onAfterForumUpdate(){}

	/**
	 * после добавления группы форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumgroup/add.php">CForumGroup::Add</a>
	 */
	public static function onAfterGroupForumsAdd(){}

	/**
	 * после редактирования группы форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumgroup/update.php">CForumGroup::Update</a>
	 */
	public static function onAfterGroupForumsUpdate(){}

	/**
	 * после добавления сообщения форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforummessage/add.php">CForumMessage::Add</a>
	 */
	public static function onAfterMessageAdd(){}

	/**
	 * после удаления сообщения форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforummessage/delete.php">CForumMessage::Delete</a>
	 */
	public static function onAfterMessageDelete(){}

	/**
	 * после редактирования сообщения форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforummessage/update.php">CForumMessage::Update</a>
	 */
	public static function onAfterMessageUpdate(){}

	/**
	 * после копирования персонального сообщения.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CForumPrivateMessage::Copy
	 */
	public static function onAfterPMCopy(){}

	/**
	 * после окончательного удаления персонального сообщения.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumprivatemessage/delete.php">CForumPrivateMessage::Delete</a>
	 */
	public static function onAfterPMDelete(){}

	/**
	 * после отправки персонального сообщения.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CForumPrivateMessage::Send
	 */
	public static function onAfterPMSend(){}

	/**
	 * после удаления персонального сообщения в корзину.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumprivatemessage/delete.php">CForumPrivateMessage::Delete</a>
	 */
	public static function onAfterPMTrash(){}

	/**
	 * после добавления темы форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/add.php">CForumTopic::Add</a>
	 */
	public static function onAfterTopicAdd(){}

	/**
	 * после удаления темы форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/delete.php">CForumTopic::Delete</a>
	 */
	public static function onAfterTopicDelete(){}

	/**
	 * после редактирования темы форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/update.php">CForumTopic::Update</a>
	 */
	public static function onAfterTopicUpdate(){}

	/**
	 * после добавления пользователя форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuser/add.php">CForumUser::Add</a>
	 */
	public static function onAfterUserAdd(){}

	/**
	 * после удаления пользователя форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuser/delete.php">CForumUser::Delete</a>
	 */
	public static function onAfterUserDelete(){}

	/**
	 * после редактирования пользователя форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuser/update.php">CForumUser::Update</a>
	 */
	public static function onAfterUserUpdate(){}

	/**
	 * перед добавлением форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumnew/add.php">CForumNew::Add</a>
	 */
	public static function onBeforeForumAdd(){}

	/**
	 * перед удалением форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumnew/delete.php">CForumNew::Delete</a>
	 */
	public static function OnBeforeForumDelete(){}

	/**
	 * перед редактированием форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumnew/update.php">CForumNew::Update</a>
	 */
	public static function onBeforeForumUpdate(){}

	/**
	 * перед созданием группы форумов.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumgroup/add.php">CForumGroup::Add</a>
	 */
	public static function onBeforeGroupForumsAdd(){}

	/**
	 * перед редактированием группы форумов.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumgroup/update.php">CForumGroup::Update</a>
	 */
	public static function onBeforeGroupForumsUpdate(){}

	/**
	 * перед добавлением сообщения форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforummessage/add.php">CForumMessage::Add</a>
	 */
	public static function onBeforeMessageAdd(){}

	/**
	 * перед удалением сообщения форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforummessage/delete.php">CForumMessage::Delete</a>
	 */
	public static function onBeforeMessageDelete(){}

	/**
	 * перед редактированием сообщения форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforummessage/update.php">CForumMessage::Update</a>
	 */
	public static function onBeforeMessageUpdate(){}

	/**
	 * перед копированием персонального сообщения.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CForumPrivateMessage::Copy
	 */
	public static function onBeforePMCopy(){}

	/**
	 * перед удалением персонального сообщения.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumprivatemessage/delete.php">CForumPrivateMessage::Delete</a>
	 */
	public static function onBeforePMDelete(){}

	/**
	 * перед чтением персонального сообщения.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CForumPrivateMessage::MakeRead
	 */
	public static function onBeforePMMakeRead(){}

	/**
	 * перед отправкой персонального сообщения.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumprivatemessage/send.php">CForumPrivateMessage::Send</a>
	 */
	public static function onBeforePMSend(){}

	/**
	 * перед редактированием персонального сообщения.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumprivatemessage/update.php">CForumPrivateMessage::Update</a>
	 */
	public static function onBeforePMUpdate(){}

	/**
	 * перед добавлением темы форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/add.php">CForumTopic::Add</a>
	 */
	public static function onBeforeTopicAdd(){}

	/**
	 * перед удалением темы форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/delete.php">CForumTopic::Delete</a>
	 */
	public static function onBeforeTopicDelete(){}

	/**
	 * перед редактированием темы форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/update.php">CForumTopic::Update</a>
	 */
	public static function onBeforeTopicUpdate(){}

	/**
	 * перед добавлением пользователя форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuser/add.php">CForumUser::Add</a>
	 */
	public static function onBeforeUserAdd(){}

	/**
	 * перед удалением пользователя форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuser/delete.php">CForumUser::Delete</a>
	 */
	public static function onBeforeUserDelete(){}

	/**
	 * перед редактированием пользователя форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuser/update.php">CForumUser::Update</a>
	 */
	public static function onBeforeUserUpdate(){}

	/**
	 * при удалении форума.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/forum/developer/cforumnew/delete.php">CForumNew::Delete</a>
	 */
	public static function OnForumDelete(){}


}
?>