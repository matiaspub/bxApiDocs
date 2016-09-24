<?
/**
 * 
 * Класс-контейнер событий модуля <b>ldap</b>
 * 
 */
class _CEventsLdap {
/**
 * перед логином пользователя.
 * <i>Вызывается в методе:</i><br>
 * CLDAP::OnUserLogin<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/ldap/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeUserLogin(){}

/**
 * перед синхронизацией с сервером LDAP, позволяет добавлять свою логику синхронизации.
 * <i>Вызывается в методе:</i><br>
 * CLdapServer::Sync<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/ldap/events/index.php
 * @author Bitrix
 */
	public static function OnLdapBeforeSync(){}

/**
 * после преобразования атрибутов AD/LDAP в пользовательские свойства, позволяет изменять свойства пользователя, добавлять свою логику определение департамента и начальника.
 * <i>Вызывается в методе:</i><br>
 * CLDAP::GetUserFields<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/ldap/events/index.php
 * @author Bitrix
 */
	public static function OnLdapUserFields(){}


}
?>