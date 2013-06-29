<?
/**
 * 
 * Класс-контейнер событий модуля <b>support</b>
 * 
 */
class _CEventsSupport {
	/**
	 * после использования купона.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CSupportSuperCoupon::UseCoupon
	 */
	public static function OnAfterUseCoupon(){}

	/**
	 * перед пересылкой купона по e-mail.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CSupportSuperCoupon::UseCoupon
	 */
	public static function OnBeforeSendCouponEMail(){}

	/**
	 * перед добавлением тикета.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * <a href="http://dev.1c-bitrix.ru/api_help/support/classes/cticket/set.php">CTicket::Set</a>
	 */
	public static function OnBeforeTicketAdd(){}

	/**
	 * перед удалением тикета.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * <a href="http://dev.1c-bitrix.ru/api_help/support/classes/cticket/delete.php">CTicket::Delete</a>
	 */
	public static function OnBeforeTicketDelete(){}

	/**
	 * перед изменением тикета.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * <a href="http://dev.1c-bitrix.ru/api_help/support/classes/cticket/set.php">CTicket::Set</a>
	 */
	public static function OnBeforeTicketUpdate(){}

	/**
	 * при удалении тикета.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * <a href="http://dev.1c-bitrix.ru/api_help/support/classes/cticket/delete.php">CTicket::Delete</a>
	 */
	public static function OnTicketDelete(){}

	/**
	 * после добавления тикета.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * <a href="http://dev.1c-bitrix.ru/api_help/support/classes/cticket/set.php">CTicket::Set</a>
	 */
	public static function OnAfterTicketAdd(){}

	/**
	 * после изменения тикета.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * <a href="http://dev.1c-bitrix.ru/api_help/support/classes/cticket/set.php">CTicket::Set</a>
	 */
	public static function OnAfterTicketUpdate(){}

	/**
	 * перед отправкой письма клиенту модуля <b>Тех. поддержка</b>.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CTicket::Set_sendMails
	 */
	public static function OnBeforeSendMailToAuthor(){}

	/**
	 * перед отправкой письма сотруднику модуля <b>Тех. поддержка</b>.
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CTicket::Set_sendMails
	 */
	public static function OnBeforeSendMailToSupport(){}


}
?>