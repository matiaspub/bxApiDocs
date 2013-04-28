<?
/**
 * 
 * Класс-контейнер событий модуля <b>socialnetwork</b>
 * 
 */
class CEventsSocialnetwork {
	/**
	 * <p>Вызывается перед удалением дополнительного функционала.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID записи.
	 *
	 *
	 *
	 * @return bool 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetFeatures.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetFeatures($ID){}

	/**
	 * Событие вызывается в методе создания новой записи дополнительного функционала до вставки, и может быть использовано для отмены вставки или переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  Массив полей новой записи.
	 *
	 *
	 *
	 * @return bool <i>false</i><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetFeaturesAdd.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetFeaturesAdd(&$arParams){}

	/**
	 * Событие вызывается в методе создания записи права на дополнительный функционал до вставки, и может быть использовано для отмены вставки или переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  Массив полей новой записи.
	 *
	 *
	 *
	 * @return bool <i>false</i><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetFeaturesPermsAdd.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetFeaturesPermsAdd(&$arParams){}

	/**
	 * <p>Вызывается перед удалением прав на дополнительный функционал.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID записи.
	 *
	 *
	 *
	 * @return bool 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetFeaturesPermsDelete.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetFeaturesPermsDelete($ID){}

	/**
	 * Событие вызывается в методе изменения параметров права на доступ к дополнительному функционалу до изменения, и может быть использовано для отмены изменения или переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  Массив полей изменяемой записи.
	 *
	 *
	 *
	 * @return bool <i>false</i><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetFeaturesPermsUpdate.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetFeaturesPermsUpdate(&$arParams){}

	/**
	 * Событие вызывается в методе изменения параметров дополнительного функционала до изменения, и может быть использовано для отмены изменения или переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  Массив полей изменяемой записи.
	 *
	 *
	 *
	 * @return bool <i>false</i><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetFeaturesUpdate.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetFeaturesUpdate(&$arParams){}

	/**
	 * Событие вызывается в методе создания новой рабочей группы до вставки, и может быть использовано для отмены вставки или переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  Массив полей новой рабочей группы.
	 *
	 *
	 *
	 * @return bool <i>false</i><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetGroupAdd.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetGroupAdd(&$arParams){}

	/**
	 * <p>Вызывается перед удалением рабочей группы.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID рабочей группы.
	 *
	 *
	 *
	 * @return bool 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetGroupDelete.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetGroupDelete($ID){}

	/**
	 * Событие вызывается в методе изменения параметров рабочей группы до изменения, и может быть использовано для отмены изменения или переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  Массив полей изменяемой записи.
	 *
	 *
	 *
	 * @return bool <i>false</i><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetGroupUpdate.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetGroupUpdate(&$arParams){}

	/**
	 * Событие вызывается в методе создания сообщения до вставки, и может быть использовано для отмены вставки или переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  Массив полей новой записи.
	 *
	 *
	 *
	 * @return bool <i>false</i><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetMessagesAdd.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetMessagesAdd(&$arParams){}

	/**
	 * <p>Вызывается перед удалением сообщения.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID сообщения.
	 *
	 *
	 *
	 * @return bool 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetMessagesDelete.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetMessagesDelete($ID){}

	/**
	 * Событие вызывается в методе изменения параметров сообщения до изменения, и может быть использовано для отмены изменения или переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  Массив полей изменяемой записи.
	 *
	 *
	 *
	 * @return bool <i>false</i><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetMessagesUpdate.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetMessagesUpdate(&$arParams){}

	/**
	 * Событие вызывается в методе создания связи между пользователями до вставки, и может быть использовано для отмены вставки или переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  Массив полей новой записи.
	 *
	 *
	 *
	 * @return bool <i>false</i><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetUserRelationsAdd.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetUserRelationsAdd(&$arParams){}

	/**
	 * <p>Вызывается перед удалением связи между пользователями.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID связи.
	 *
	 *
	 *
	 * @return bool 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetUserRelationsDelete.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetUserRelationsDelete($ID){}

	/**
	 * Событие вызывается в методе изменения параметров связи между пользователями до изменения, и может быть использовано для отмены изменения или переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  Массив полей изменяемой записи.
	 *
	 *
	 *
	 * @return bool <i>false</i><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetUserRelationsUpdate.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetUserRelationsUpdate(&$arParams){}

	/**
	 * Событие вызывается в методе создания связи между пользователем и рабочей группой до вставки, и может быть использовано для отмены вставки или переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  Массив полей новой записи.
	 *
	 *
	 *
	 * @return bool <i>false</i><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetUserToGroupAdd.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetUserToGroupAdd(&$arParams){}

	/**
	 * <p>Вызывается перед удалением связи между пользователем и рабочей группой.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID записи.
	 *
	 *
	 *
	 * @return bool 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetUserToGroupDelete.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetUserToGroupDelete($ID){}

	/**
	 * Событие вызывается в методе изменения параметров связи между пользователем и рабочей группой до изменения, и может быть использовано для отмены изменения или переопределения некоторых полей.
	 *
	 *
	 *
	 *
	 * @param array &$arParams  Массив полей изменяемой записи.
	 *
	 *
	 *
	 * @return bool <i>false</i><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetUserToGroupUpdate.php
	 * @author Bitrix
	 */
	public static function OnBeforeSocNetUserToGroupUpdate(&$arParams){}

	/**
	 * Событие вызывается при инициализации модуля социальной сети после заполнения массива дополнительного функционала. Оно может быть использовано для изменения массива дополнительного функционала.
	 *
	 *
	 *
	 *
	 * @param array &$arSocNetFeaturesSettings  Массив с описанием дополнительного функционала.
	 *
	 *
	 *
	 * @return bool 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnFillSocNetFeaturesList.php
	 * @author Bitrix
	 */
	public static function OnFillSocNetFeaturesList(&$arSocNetFeaturesSettings){}

	/**
	 * <p>Вызывается в момент удаления дополнительного функционала.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID записи.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetFeatures.php
	 * @author Bitrix
	 */
	public static function OnSocNetFeatures($ID){}

	/**
	 * Событие вызывается после добавления нового дополнительного функционала.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  Массив полей новой записи.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetFeaturesAdd.php
	 * @author Bitrix
	 */
	public static function OnSocNetFeaturesAdd(&$arFields){}

	/**
	 * Событие вызывается после добавления новой записи права на дополнительный функционал.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  Массив полей новой записи.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetFeaturesPermsAdd.php
	 * @author Bitrix
	 */
	public static function OnSocNetFeaturesPermsAdd(&$arFields){}

	/**
	 * <p>Вызывается в момент удаления права на дополнительный функционал.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID записи.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetFeaturesPermsDelete.php
	 * @author Bitrix
	 */
	public static function OnSocNetFeaturesPermsDelete($ID){}

	/**
	 * Событие вызывается после изменения права на дополнительный функционал.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  Массив полей измененной записи.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetFeaturesPermsUpdate.php
	 * @author Bitrix
	 */
	public static function OnSocNetFeaturesPermsUpdate(&$arFields){}

	/**
	 * Событие вызывается после изменения записи дополнительного функционала.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  Массив полей измененной записи.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetFeaturesUpdate.php
	 * @author Bitrix
	 */
	public static function OnSocNetFeaturesUpdate(&$arFields){}

	/**
	 * Событие вызывается после добавления новой рабочей группы.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  Массив из двух параметров - <i>$ID</i> и <i>$arFields</i>, причем второй - по
	 * ссылке.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetGroupAdd.php
	 * @author Bitrix
	 */
	public static function OnSocNetGroupAdd(&$arFields){}

	/**
	 * <p>Вызывается в момент удаления рабочей группы.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID рабочей группы.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetGroupDelete.php
	 * @author Bitrix
	 */
	public static function OnSocNetGroupDelete($ID){}

	/**
	 * Событие вызывается после изменения рабочей группы.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  Массив полей измененной записи.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetGroupUpdate.php
	 * @author Bitrix
	 */
	public static function OnSocNetGroupUpdate(&$arFields){}

	/**
	 * Событие вызывается после добавления нового сообщения.
	 *
	 *
	 *
	 *
	 * @param int $ID  Массив полей новой записи.
	 *
	 *
	 *
	 * @param array &$arFields  
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetMessagesAdd.php
	 * @author Bitrix
	 */
	public static function OnSocNetMessagesAdd($ID, &$arFields){}

	/**
	 * <p>Вызывается в момент удаления сообщения.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID сообщения.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetMessagesDelete.php
	 * @author Bitrix
	 */
	public static function OnSocNetMessagesDelete($ID){}

	/**
	 * Событие вызывается после изменения сообщения.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  Массив полей измененной записи.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetMessagesUpdate.php
	 * @author Bitrix
	 */
	public static function OnSocNetMessagesUpdate(&$arFields){}

	/**
	 * Событие вызывается после добавления новой связи между пользователями.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  Массив полей новой записи.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetUserRelationsAdd.php
	 * @author Bitrix
	 */
	public static function OnSocNetUserRelationsAdd(&$arFields){}

	/**
	 * <p>Вызывается в момент удаления связи между пользователями.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID связи.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetUserRelationsDelete.php
	 * @author Bitrix
	 */
	public static function OnSocNetUserRelationsDelete($ID){}

	/**
	 * Событие вызывается после изменения связи между пользователями.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  Массив полей измененной записи.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetUserRelationsUpdate.php
	 * @author Bitrix
	 */
	public static function OnSocNetUserRelationsUpdate(&$arFields){}

	/**
	 * Событие вызывается после добавления новой связи между пользователем и рабочей группой.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  Массив полей новой записи.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetUserToGroupAdd.php
	 * @author Bitrix
	 */
	public static function OnSocNetUserToGroupAdd(&$arFields){}

	/**
	 * <p>Вызывается в момент удаления связи между пользователем и рабочей группой.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID связи.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetUserToGroupDelete.php
	 * @author Bitrix
	 */
	public static function OnSocNetUserToGroupDelete($ID){}

	/**
	 * Событие вызывается после изменения связи между пользователем и рабочей группой.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  Массив полей измененной записи.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetUserToGroupUpdate.php
	 * @author Bitrix
	 */
	public static function OnSocNetUserToGroupUpdate(&$arFields){}


}?>