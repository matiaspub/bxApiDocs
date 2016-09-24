<?
/**
 * 
 * Класс-контейнер событий модуля <b>socialnetwork</b>
 * 
 */
class _CEventsSocialnetwork {
/**
 * <p>Вызывается перед удалением связи между пользователем и рабочей группой.</p>
 *
 *
 * @param mixed $intID  ID записи.
 *
 * @return bool <p>Обработчик должен вернуть false в случае если необходимо отменить
 * удаление связи.</p><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetUserToGroupDelete.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetUserToGroupDelete($intID){}

/**
 * <p>Вызывается в момент удаления связи между пользователем и рабочей группой.</p>
 *
 *
 * @param mixed $intID  ID связи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetUserToGroupDelete.php
 * @author Bitrix
 */
	public static function OnSocNetUserToGroupDelete($intID){}

/**
 * Событие вызывается в методе создания новой рабочей группы до вставки, и может быть использовано для отмены вставки или переопределения некоторых полей.
 *
 *
 * @param array &$arParams  Массив полей новой рабочей группы.
 *
 * @return bool <p>Для отмены добавления и прекращении выполнения метода создания
 * рабочей группы необходимо в функции-обработчике вернуть <i>false</i>.
 * </p><br><br>
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
 * @param int $ID  ID добавленной группы.
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
 * @param int $ID  Идентификатор группы
 *
 * @param array &$arParams  Массив полей изменяемой записи.
 *
 * @return bool <i>false</i><br><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetGroupUpdate.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetGroupUpdate($ID, &$arParams){}

/**
 * Событие вызывается после изменения рабочей группы.
 *
 *
 * @param int $IDarray  Идентификатор группы
 *
 * @param $ID &$arFields  Массив полей изменяемой записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetGroupUpdate.php
 * @author Bitrix
 */
	public static function OnSocNetGroupUpdate($IDarray, &$arFields){}

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
 * <p>Вызывается перед удалением рабочей группы.</p>
 *
 *
 * @param mixed $intID  ID рабочей группы.
 *
 * @return bool <p>Обработчик должен вернуть false в случае если необходимо отменить
 * удаление рабочей группы.</p><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetGroupDelete.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetGroupDelete($intID){}

/**
 * <p>Вызывается в момент удаления рабочей группы.</p>
 *
 *
 * @param mixed $intID  ID рабочей группы.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetGroupDelete.php
 * @author Bitrix
 */
	public static function OnSocNetGroupDelete($intID){}

/**
 * <p>Вызывается перед удалением дополнительного функционала.</p>
 *
 *
 * @param mixed $intID  ID записи.
 *
 * @return bool <br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetFeatures.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetFeatures($intID){}

/**
 * <p>Вызывается в момент удаления дополнительного функционала.</p>
 *
 *
 * @param mixed $intID  ID записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetFeatures.php
 * @author Bitrix
 */
	public static function OnSocNetFeatures($intID){}

/**
 * Событие вызывается в методе изменения параметров дополнительного функционала до изменения, и может быть использовано для отмены изменения или переопределения некоторых полей.
 *
 *
 * @param int $IDarray  идентификатор группы
 *
 * @param $ID &$arParams  Массив полей изменяемой записи.
 *
 * @return bool <i>false</i><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetFeaturesUpdate.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetFeaturesUpdate($IDarray, &$arParams){}

/**
 * Событие вызывается после изменения записи дополнительного функционала.
 *
 *
 * @param int $IDarray  Идентификатор группы
 *
 * @param $ID &$arFields  Массив полей изменяемой записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetFeaturesUpdate.php
 * @author Bitrix
 */
	public static function OnSocNetFeaturesUpdate($IDarray, &$arFields){}

/**
 * <p>Вызывается перед удалением прав на дополнительный функционал.</p>
 *
 *
 * @param mixed $intID  ID записи.
 *
 * @return bool <br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetFeaturesPermsDelete.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetFeaturesPermsDelete($intID){}

/**
 * <p>Вызывается в момент удаления права на дополнительный функционал.</p>
 *
 *
 * @param mixed $intID  ID записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetFeaturesPermsDelete.php
 * @author Bitrix
 */
	public static function OnSocNetFeaturesPermsDelete($intID){}

/**
 * Событие вызывается в методе изменения параметров права на доступ к дополнительному функционалу до изменения, и может быть использовано для отмены изменения или переопределения некоторых полей.
 *
 *
 * @param int $IDarray  идентификатор группы
 *
 * @param $ID &$arParams  Массив полей изменяемой записи.
 *
 * @return bool <i>false</i><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetFeaturesPermsUpdate.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetFeaturesPermsUpdate($IDarray, &$arParams){}

/**
 * Событие вызывается после изменения права на дополнительный функционал.
 *
 *
 * @param int $IDarray  идентификатор группы
 *
 * @param $ID &$arFields  Массив полей изменяемой записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetFeaturesPermsUpdate.php
 * @author Bitrix
 */
	public static function OnSocNetFeaturesPermsUpdate($IDarray, &$arFields){}

/**
 * <p>Вызывается перед удалением сообщения.</p>
 *
 *
 * @param mixed $intID  ID сообщения.
 *
 * @return bool <p>Обработчик должен вернуть false в случае если необходимо отменить
 * удаление сообщения.</p><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetMessagesDelete.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetMessagesDelete($intID){}

/**
 * <p>Вызывается в момент удаления сообщения.</p>
 *
 *
 * @param mixed $intID  ID сообщения.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetMessagesDelete.php
 * @author Bitrix
 */
	public static function OnSocNetMessagesDelete($intID){}

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
 * @param mixed $intID  Массив полей новой записи.
 *
 * @param array &$arFields  
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetMessagesAdd.php
 * @author Bitrix
 */
	public static function OnSocNetMessagesAdd($intID, &$arFields){}

/**
 * Событие вызывается в методе изменения параметров сообщения до изменения, и может быть использовано для отмены изменения или переопределения некоторых полей.
 *
 *
 * @param int $IDarray  идентификатор группы
 *
 * @param $ID &$arParams  Массив полей изменяемой записи.
 *
 * @return bool <i>false</i><br><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetMessagesUpdate.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetMessagesUpdate($IDarray, &$arParams){}

/**
 * Событие вызывается после изменения сообщения.
 *
 *
 * @param int $IDarray  Идентификатор группы
 *
 * @param $ID &$arFields  Массив полей изменяемой записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetMessagesUpdate.php
 * @author Bitrix
 */
	public static function OnSocNetMessagesUpdate($IDarray, &$arFields){}

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
 * @param int $IDarray  идентификатор группы
 *
 * @param $ID &$arParams  Массив полей изменяемой записи.
 *
 * @return bool <i>false</i><br><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetUserToGroupUpdate.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetUserToGroupUpdate($IDarray, &$arParams){}

/**
 * Событие вызывается после изменения связи между пользователем и рабочей группой.
 *
 *
 * @param int $IDarray  Идентификатор группы
 *
 * @param $ID &$arFields  Массив полей изменяемой записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetUserToGroupUpdate.php
 * @author Bitrix
 */
	public static function OnSocNetUserToGroupUpdate($IDarray, &$arFields){}

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
 * @param int $IDarray  идентификатор группы
 *
 * @param $ID &$arParams  Массив полей изменяемой записи.
 *
 * @return bool <i>false</i><br><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetUserRelationsUpdate.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetUserRelationsUpdate($IDarray, &$arParams){}

/**
 * Событие вызывается после изменения связи между пользователями.
 *
 *
 * @param int $IDarray  Идентификатор группы
 *
 * @param $ID &$arFields  Массив полей изменяемой записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetUserRelationsUpdate.php
 * @author Bitrix
 */
	public static function OnSocNetUserRelationsUpdate($IDarray, &$arFields){}

/**
 * <p>Вызывается в момент удаления связи между пользователями.</p>
 *
 *
 * @param mixed $intID  ID связи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetUserRelationsDelete.php
 * @author Bitrix
 */
	public static function OnSocNetUserRelationsDelete($intID){}

/**
 * <p>Вызывается перед удалением связи между пользователями.</p>
 *
 *
 * @param mixed $intID  ID связи.
 *
 * @return bool <p>Обработчик должен вернуть false в случае если необходимо отменить
 * удаление связи.</p><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetUserRelationsDelete.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetUserRelationsDelete($intID){}

/**
 * при включенной поддержке ЧПУ компонента социальной сети в самом начале работы компонента. Позволяет подключить свои пути для ЧПУ в комплексном компоненте соцсети.
 * <pre class="syntax">OnParseSocNetComponentPathHandler (&amp;$arDefaultUrlTemplates404, &amp;$arCustomPagesPath, $arParams) {
 * } </pre>
 * 
 * <i>Вызывается в методе:</i><br>
 *  <br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnParseSocNetComponentPath(){}

/**
 * добавляет тип сущности Живой ленты.
 * <i>Вызывается в методе:</i><br>
 * CSocNetAllowed::RunEventForAllowedEntityType<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnFillSocNetAllowedSubscribeEntityTypes(){}

/**
 * при добавлении прав на запись Живой ленты.
 * <i>Вызывается в методе:</i><br>
 * CSocNetLogRights::Add<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetLogRightsAdd(){}

/**
 * при изменении прав на запись Живой ленты.
 * <i>Вызывается в методе:</i><br>
 * CSocNetLogRights::Update<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetLogRightsUpdate(){}

/**
 * при отправке запроса на вступление в группу соцсети
 * <i>Вызывается в методе:</i><br>
 * CSocNetUserToGroup::SendRequestToJoinGroup<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnSocNetSendRequestToJoinGroup(){}

/**
 * при получении подтверждения запроса о членстве в группе соцсети
 * <i>Вызывается в методе:</i><br>
 * CSocNetUserToGroup::UserConfirmRequestToBeMember<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnSocNetUserConfirmRequestToBeMember(){}

/**
 * при получении отклонении запроса о членстве в группе соцсети
 * <i>Вызывается в методе:</i><br>
 * CSocNetUserToGroup::UserRejectRequestToBeMember<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/index.php
 * @author Bitrix
 */
	public static function OnSocNetUserRejectRequestToBeMember(){}

/**
 * Событие вызывается перед добавлением комментария в Живую ленту..
 *
 *
 * @param int $IDarray  Идентификатор добавленного комментария.
 *
 * @param $ID &$arFields  Массив полей добавляемого комментария.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/onbeforesocnetlogcommentadd.php
 * @author Bitrix
 */
	public static function OnBeforeSocNetLogCommentAdd($IDarray, &$arFields){}

/**
 * Событие вызывается после добавления комментария в Живую ленту.
 *
 *
 * @param array $arFields  Массив полей добавляемого комментария.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/onaftersocnetlogcommentadd.php
 * @author Bitrix
 */
	public static function OnAfterSocNetLogCommentAdd($arFields){}

/**
 * Событие вызывается после изменения записи Живой ленты.
 *
 *
 * @param int $IDarray  Идентификатор записи.
 *
 * @param $ID $arFields  Массив полей изменяемой записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/onaftersocnetlogupdate.php
 * @author Bitrix
 */
	public static function OnAfterSocNetLogUpdate($IDarray, $arFields){}

/**
 * Событие вызывается после добавлении записи Живой ленты.
 *
 *
 * @param array $arFields  Массив полей добавляемой записи.
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/onaftersocnetlogadd.php
 * @author Bitrix
 */
	public static function OnAfterSocNetLogAdd($arFields){}


}
?>