<?
/**
 * 
 * Класс-контейнер событий модуля <b>im</b>
 * 
 */
class _CEventsIm {
	/**
	 * после подтверждения уведомления
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CIMNotify::Confirm
	 */
	public static function OnAfterConfirmNotify(){}

	/**
	 * после получения контакт листа
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CIMContactList::GetList
	 */
	public static function OnAfterContactListGetList(){}

	/**
	 * после удаления сообщения
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CIMMessage::Delete
	 */
	public static function OnAfterDeleteMessage(){}

	/**
	 * после удаления уведомления
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CIMNotify::DeleteWithCheck
	 */
	public static function OnAfterDeleteNotify(){}

	/**
	 * после добавления сообщения
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CIMMessenger::Add
	 */
	public static function OnAfterMessagesAdd(){}

	/**
	 * после добавления уведомления
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CIMMessenger::Add
	 */
	public static function OnAfterNotifyAdd(){}

	/**
	 * перед подтверждением уведомления
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CIMNotify::Confirm
	 */
	public static function OnBeforeConfirmNotify(){}

	/**
	 * перед добавлением уведомления или сообщения
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CIMMessenger::Add
	 */
	public static function OnBeforeMessageNotifyAdd(){}


}
?>