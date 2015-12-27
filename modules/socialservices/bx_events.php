<?
/**
 * 
 * Класс-контейнер событий модуля <b>socialservices</b>
 * 
 */
class _CEventsSocialservices {
/**
 * Вызывается после добавления пользователя через соцсерсис
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CSocServAuthDB::Add<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialservices/events/index.php
 * @author Bitrix
 */
	public static function OnAfterSocServUserAdd(){}

/**
 * Вызывается после обновления данных пользователя 
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CSocServAuth::Update<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialservices/events/index.php
 * @author Bitrix
 */
	public static function OnAfterSocServUserUpdate(){}

/**
 * Вызывается при построении списка сервисов авторизации
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CSocServAuthManager::__construct<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialservices/events/index.php
 * @author Bitrix
 */
	public static function OnAuthServicesBuildList(){}

/**
 * Вызывается перед редиректом на сайт авторизованного через соцсервис пользователя 
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * COpenIDClient::Authorize<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialservices/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeOpenIDAuthFinalRedirect(){}

/**
 * Вызывается перед добавлением пользователя через OpenID авторизацию 
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * COpenIDClient::Authorize<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialservices/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeOpenIDUserAdd(){}

/**
 * Вызывается перед удалением пользователя 
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CSocServAuth::Delete<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialservices/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeSocServUserDelete(){}

/**
 * Вызывается при публикации сообщения соцсервиса  
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CSocServAuthManager::PostIntoBuzz<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialservices/events/index.php
 * @author Bitrix
 */
	public static function OnPublishSocServMessage(){}


}
?>