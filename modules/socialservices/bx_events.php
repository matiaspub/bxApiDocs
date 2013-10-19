<?
/**
 * 
 * Класс-контейнер событий модуля <b>socialservices</b>
 * 
 */
class _CEventsSocialservices {
	/**
	 * после добавления пользователя через соцсерсис
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CSocServAuthDB::Add
	 */
	public static function OnAfterSocServUserAdd(){}

	/**
	 * после обновления данных пользователя 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CSocServAuth::Update
	 */
	public static function OnAfterSocServUserUpdate(){}

	/**
	 * при построении списка сервисов авторизации
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CSocServAuthManager::__construct
	 */
	public static function OnAuthServicesBuildList(){}

	/**
	 * перед редиректом на сайт авторизованного через соцсервис пользователя 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * COpenIDClient::Authorize
	 */
	public static function OnBeforeOpenIDAuthFinalRedirect(){}

	/**
	 * перед добавлением пользователя через OpenID авторизацию 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * COpenIDClient::Authorize
	 */
	public static function OnBeforeOpenIDUserAdd(){}

	/**
	 * перед удалением пользователя 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CSocServAuth::Delete
	 */
	public static function OnBeforeSocServUserDelete(){}

	/**
	 * при публикации сообщения соцсерсиса  
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CSocServAuthManager::PostIntoBuzz
	 */
	public static function OnPublishSocServMessage(){}


}
?>