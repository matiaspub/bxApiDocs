<?
/**
 * 
 * Класс-контейнер событий модуля <b>socialnetwork</b>
 * 
 */
class _CEventsSocialnetwork {
/**
 * Вызывается 
 * <i>Вызывается в методе:</i><br>
 * CSocNetLogComments::Add<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnAfterSocNetLogCommentAdd(){}

/**
 * Событие вызывается при инициализации модуля социальной сети после заполнения массива дополнительного функционала. Оно может быть использовано для изменения массива дополнительного функционала.
 *
 *
 * @param array &$arSocNetFeaturesSettings  Массив с описанием дополнительного функционала.
 *
 * @return bool 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnFillSocNetFeaturesList.php
 * @author Bitrix
 */
	public static function OnFillSocNetFeaturesList(&$arSocNetFeaturesSettings){}

/**
 * Вызывается  
 * <i>Вызывается в методе:</i><br>
 *   <br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnFillSocNetLogEvents(){}

/**
 * Вызывается  
 * <i>Вызывается в методе:</i><br>
 * CSocNetLogTools::ShowSourceType<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnShowSocNetSourceType(){}

/**
 * <p>Вызывается перед удалением рабочей группы.</p>
 *
 *
 * @param int $ID  ID рабочей группы.
 *
 * @return bool <p>Обработчик должен вернуть false в случае если необходимо отменить
 * удаление рабочей группы.</p> <br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetGroupDelete.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetGroupDelete($ID){}

/**
 * <p>Вызывается в момент удаления рабочей группы.</p>
 *
 *
 * @param int $ID  ID рабочей группы.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetGroupDelete.php
 * @author Bitrix
 */
	public static function OnSocNetGroupDelete($ID){}

/**
 * <p>Вызывается перед удалением дополнительного функционала.</p>
 *
 *
 * @param int $ID  ID записи.</bo
 *
 * @return bool <br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetFeatures.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetFeatures($ID){}

/**
 * <p>Вызывается в момент удаления дополнительного функционала.</p>
 *
 *
 * @param int $ID  ID записи.</bo
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetFeatures.php
 * @author Bitrix
 */
	public static function OnSocNetFeatures($ID){}

/**
 * Событие вызывается в методе изменения параметров дополнительного функционала до изменения, и может быть использовано для отмены изменения или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  Массив полей изменяемой записи.
 *
 * @return bool <i>false</i><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetFeaturesUpdate.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetFeaturesUpdate(&$arParams){}

/**
 * Событие вызывается после изменения записи дополнительного функционала.
 *
 *
 * @param array &$arFields  Массив полей измененной записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetFeaturesUpdate.php
 * @author Bitrix
 */
	public static function OnSocNetFeaturesUpdate(&$arFields){}

/**
 * <p>Вызывается перед удалением прав на дополнительный функционал.</p>
 *
 *
 * @param int $ID  ID записи.</bo
 *
 * @return bool <br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetFeaturesPermsDelete.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetFeaturesPermsDelete($ID){}

/**
 * <p>Вызывается в момент удаления права на дополнительный функционал.</p>
 *
 *
 * @param int $ID  ID записи.</bo
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetFeaturesPermsDelete.php
 * @author Bitrix
 */
	public static function OnSocNetFeaturesPermsDelete($ID){}

/**
 * Событие вызывается в методе изменения параметров права на доступ к дополнительному функционалу до изменения, и может быть использовано для отмены изменения или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  Массив полей изменяемой записи.
 *
 * @return bool <i>false</i><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetFeaturesPermsUpdate.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetFeaturesPermsUpdate(&$arParams){}

/**
 * Событие вызывается после изменения права на дополнительный функционал.
 *
 *
 * @param array &$arFields  Массив полей измененной записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetFeaturesPermsUpdate.php
 * @author Bitrix
 */
	public static function OnSocNetFeaturesPermsUpdate(&$arFields){}

/**
 * <p>Вызывается перед удалением сообщения.</p>
 *
 *
 * @param int $ID  ID сообщения.</bod
 *
 * @return bool <p>Обработчик должен вернуть false в случае если необходимо отменить
 * удаление сообщения.</p> <br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetMessagesDelete.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetMessagesDelete($ID){}

/**
 * <p>Вызывается в момент удаления сообщения.</p>
 *
 *
 * @param int $ID  ID сообщения.</bod
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetMessagesDelete.php
 * @author Bitrix
 */
	public static function OnSocNetMessagesDelete($ID){}

/**
 * <p>Вызывается перед удалением связи между пользователем и рабочей группой.</p>
 *
 *
 * @param int $ID  ID записи.</bo
 *
 * @return bool <p>Обработчик должен вернуть false в случае если необходимо отменить
 * удаление связи.</p> <br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetUserToGroupDelete.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetUserToGroupDelete($ID){}

/**
 * <p>Вызывается в момент удаления связи между пользователем и рабочей группой.</p>
 *
 *
 * @param int $ID  ID связи.</bod
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetUserToGroupDelete.php
 * @author Bitrix
 */
	public static function OnSocNetUserToGroupDelete($ID){}

/**
 * <p>Вызывается перед удалением связи между пользователями.</p>
 *
 *
 * @param int $ID  ID связи.</bod
 *
 * @return bool <p>Обработчик должен вернуть false в случае если необходимо отменить
 * удаление связи.</p> <br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetUserRelationsDelete.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetUserRelationsDelete($ID){}

/**
 * <p>Вызывается в момент удаления связи между пользователями.</p>
 *
 *
 * @param int $ID  ID связи.</bod
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetUserRelationsDelete.php
 * @author Bitrix
 */
	public static function OnSocNetUserRelationsDelete($ID){}

/**
 * Событие вызывается в методе создания новой рабочей группы до вставки, и может быть использовано для отмены вставки или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  Массив полей новой рабочей группы.
 *
 * @return bool <p>Для отмены добавления и прекращении выполнения метода создания
 * рабочей группы необходимо в функции-обработчике вернуть <i>false</i>.
 * </p> <br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetGroupAdd.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetGroupAdd(&$arParams){}

/**
 * Событие вызывается после добавления новой рабочей группы.
 *
 *
 * @param int $ID  ID добавленной группы. </ht
 *
 * @param array &$arFields  Поля группы, по ссылке.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetGroupAdd.php
 * @author Bitrix
 */
	public static function OnSocNetGroupAdd($ID, &$arFields){}

/**
 * Событие вызывается в методе изменения параметров рабочей группы до изменения, и может быть использовано для отмены изменения или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  Массив полей изменяемой записи.
 *
 * @return bool <i>false</i><br><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetGroupUpdate.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetGroupUpdate(&$arParams){}

/**
 * Вызывается 
 * <i>Вызывается в методе:</i><br>
 * CSocNetLogComments::Add<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetLogCommentAdd(){}

/**
 * Событие вызывается после изменения рабочей группы.
 *
 *
 * @param array &$arFields  Массив полей измененной записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetGroupUpdate.php
 * @author Bitrix
 */
	public static function OnSocNetGroupUpdate(&$arFields){}

/**
 * Событие вызывается в методе создания новой записи дополнительного функционала до вставки, и может быть использовано для отмены вставки или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  Массив полей новой записи.
 *
 * @return bool <i>false</i><br><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetFeaturesAdd.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetFeaturesAdd(&$arParams){}

/**
 * Событие вызывается после добавления нового дополнительного функционала.
 *
 *
 * @param array &$arFields  Массив полей новой записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetFeaturesAdd.php
 * @author Bitrix
 */
	public static function OnSocNetFeaturesAdd(&$arFields){}

/**
 * Событие вызывается в методе создания записи права на дополнительный функционал до вставки, и может быть использовано для отмены вставки или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  Массив полей новой записи.
 *
 * @return bool <i>false</i><br><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetFeaturesPermsAdd.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetFeaturesPermsAdd(&$arParams){}

/**
 * Событие вызывается после добавления новой записи права на дополнительный функционал.
 *
 *
 * @param array &$arFields  Массив полей новой записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetFeaturesPermsAdd.php
 * @author Bitrix
 */
	public static function OnSocNetFeaturesPermsAdd(&$arFields){}

/**
 * Событие вызывается в методе создания сообщения до вставки, и может быть использовано для отмены вставки или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  Массив полей новой записи.
 *
 * @return bool <i>false</i><br><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetMessagesAdd.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetMessagesAdd(&$arParams){}

/**
 * Событие вызывается после добавления нового сообщения.
 *
 *
 * @param int $ID  Массив полей новой записи.
 *
 * @param array &$arFields  
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetMessagesAdd.php
 * @author Bitrix
 */
	public static function OnSocNetMessagesAdd($ID, &$arFields){}

/**
 * Событие вызывается в методе изменения параметров сообщения до изменения, и может быть использовано для отмены изменения или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  Массив полей изменяемой записи.
 *
 * @return bool <i>false</i><br><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetMessagesUpdate.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetMessagesUpdate(&$arParams){}

/**
 * Событие вызывается после изменения сообщения.
 *
 *
 * @param array &$arFields  Массив полей измененной записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetMessagesUpdate.php
 * @author Bitrix
 */
	public static function OnSocNetMessagesUpdate(&$arFields){}

/**
 * Вызывается  при отправке запроса на вступление в группу соцсети
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CSocNetUserToGroup::SendRequestToJoinGroup<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnSocNetSendRequestToJoinGroup(){}

/**
 * Вызывается  при получении подтверждения запроса о членстве в группе соцсети
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CSocNetUserToGroup::UserConfirmRequestToBeMember<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnSocNetUserConfirmRequestToBeMember(){}

/**
 * Вызывается  при получении отклонении запроса о членстве в группе соцсети
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CSocNetUserToGroup::UserRejectRequestToBeMember<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnSocNetUserRejectRequestToBeMember(){}

/**
 * Событие вызывается в методе создания связи между пользователем и рабочей группой до вставки, и может быть использовано для отмены вставки или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  Массив полей новой записи.
 *
 * @return bool <i>false</i><br><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetUserToGroupAdd.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetUserToGroupAdd(&$arParams){}

/**
 * Событие вызывается после добавления новой связи между пользователем и рабочей группой.
 *
 *
 * @param array &$arFields  Массив полей новой записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetUserToGroupAdd.php
 * @author Bitrix
 */
	public static function OnSocNetUserToGroupAdd(&$arFields){}

/**
 * Событие вызывается в методе изменения параметров связи между пользователем и рабочей группой до изменения, и может быть использовано для отмены изменения или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  Массив полей изменяемой записи.
 *
 * @return bool <i>false</i><br><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetUserToGroupUpdate.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetUserToGroupUpdate(&$arParams){}

/**
 * Вызывается 
 * <i>Вызывается в методе:</i><br>
 * <br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnFillSocNetAllowedSubscribeEntityTypes(){}

/**
 * Событие вызывается после изменения связи между пользователем и рабочей группой.
 *
 *
 * @param array &$arFields  Массив полей измененной записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetUserToGroupUpdate.php
 * @author Bitrix
 */
	public static function OnSocNetUserToGroupUpdate(&$arFields){}

/**
 * Событие вызывается в методе создания связи между пользователями до вставки, и может быть использовано для отмены вставки или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  Массив полей новой записи.
 *
 * @return bool <i>false</i><br><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetUserRelationsAdd.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetUserRelationsAdd(&$arParams){}

/**
 * Событие вызывается после добавления новой связи между пользователями.
 *
 *
 * @param array &$arFields  Массив полей новой записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetUserRelationsAdd.php
 * @author Bitrix
 */
	public static function OnSocNetUserRelationsAdd(&$arFields){}

/**
 * Событие вызывается в методе изменения параметров связи между пользователями до изменения, и может быть использовано для отмены изменения или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  Массив полей изменяемой записи.
 *
 * @return bool <i>false</i><br><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetUserRelationsUpdate.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetUserRelationsUpdate(&$arParams){}

/**
 * Событие вызывается после изменения связи между пользователями.
 *
 *
 * @param array &$arFields  Массив полей измененной записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetUserRelationsUpdate.php
 * @author Bitrix
 */
	public static function OnSocNetUserRelationsUpdate(&$arFields){}

/**
 * Вызывается  при добавлении прав на запись Живой ленты.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CSocNetLogRights::Add<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetLogRightsAdd(){}

/**
 * Вызывается  при изменении прав на запись Живой ленты.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CSocNetLogRights::Update<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetLogRightsUpdate(){}

/**
 * Вызывается  при включенной поддержке ЧПУ компонента социальной сети в самом начале работы компонента. Позволяет подключить свои пути для ЧПУ в комплексном компоненте соцсети.
 * <pre class="syntax">OnParseSocNetComponentPathHandler (&amp;$arDefaultUrlTemplates404, &amp;$arCustomPagesPath, $arParams) {
 * } </pre>
 * 
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 *  <br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnParseSocNetComponentPath(){}


}
?>