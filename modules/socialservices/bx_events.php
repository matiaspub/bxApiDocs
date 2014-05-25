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
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CSocServAuthDB::Add<br><br>
	 */
	public static function OnAfterSocServUserAdd(){}

	/**
	 * после обновления данных пользователя </body>
	 * </html>ызывается в методе:</i><br>
	 * CSocServAuth::Update<br><br>
	 */
	public static function OnAfterSocServUserUpdate(){}

	/**
	 * при построении списка сервисов авторизации</body>
	 * </html>
	 * 
	 * 
	 * <i>В</i><br>
	 * CSocServAuthManager::__construct<br><br>
	 */
	public static function OnAuthServicesBuildList(){}

	/**
	 * перед редиректом на сайт авторизованного через соцсервис пользователя </body>
	 * </html>
	 * 
	 * 
	 * <i>Вызывается в методе:DClient::Authorize<br><br>
	 */
	public static function OnBeforeOpenIDAuthFinalRedirect(){}

	/**
	 * перед добавлением пользователя через OpenID авторизацию </body>
	 * </html>
	 * 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * COpenIbr><br>
	 */
	public static function OnBeforeOpenIDUserAdd(){}

	/**
	 * перед удалением пользователя </body>
	 * </html>
	 * 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CSocServAuth::Delete<br><atic function OnBeforeSocServUserDelete(){}

	/**
	 * при публикации сообщения соцсервиса  </body>
	 * </html>
	 * 
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CSocServAuthManager::PostIntoBuzz<br><br>
	 */
	public static function OnPublishSocServMessage(){}


}
?>