<?
/**
 * 
 * Класс-контейнер событий модуля <b>calendar</b>
 * 
 */
class _CEventsCalendar {
	/**
	 * в момент вывода на страницу базовой верстки календаря.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CCalendarSceleton::Build
	 */
	public static function OnAfterBuildSceleton(){}

	/**
	 * после удаления события календаря.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CCalendarEvent::Delete
	 */
	public static function OnAfterCalendarEventDelete(){}

	/**
	 * после изменения события календаря.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CCalendarEvent::Edit
	 */
	public static function OnAfterCalendarEventEdit(){}

	/**
	 * после обновления пользовательских полей календаря.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CCalendarEvent::UpdateUserFields
	 */
	public static function OnAfterCalendarEventUserFieldsUpdate(){}

	/**
	 * перед выводом на страницу базовой верстки календаря.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CCalendarSceleton::Build
	 */
	public static function OnBeforeBuildSceleton(){}

	/**
	 * перед удалением события календаря.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CCalendarEvent::Delete
	 */
	public static function OnBeforeCalendarEventDelete(){}

	/**
	 * в момент отсылки напоминания о событии календаря.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CCalendar::ReminderAgent
	 */
	public static function OnRemindEvent(){}

	/**
	 * при отсылке ряда уведомлений календаря.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CCalendar::SendMessage
	 */
	public static function OnSendInvitationMessage(){}


}
?>