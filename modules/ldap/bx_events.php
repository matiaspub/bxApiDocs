<?
/**
 * 
 * Класс-контейнер событий модуля <b>ldap</b>
 * 
 */
class _CEventsLdap {
	/**
	 * перед логином пользователя.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CLDAP::OnUserLogin
	 */
	public static function OnBeforeUserLogin(){}

	/**
	 * перед синхронизацией с сервером LDAP.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CLdapServer::Sync
	 */
	public static function OnLdapBeforeSync(){}

	/**
	 * после преобразования атрибутов AD/LDAP в пользовательские свойства.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CLDAP::GetUserFields
	 */
	public static function OnLdapUserFields(){}


}
?>