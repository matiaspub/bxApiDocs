<?
/**
 * 
 * Класс-контейнер событий модуля <b>currency</b>
 * 
 */
class _CEventsCurrency {
/**
 * <p>OnBeforeCurrencyAdd - событие, вызываемое в методе <a href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__add.17dc7357.php">CCurrency::Add</a> перед добавлением валюты и перед проверкой полей. Позволяет изменить вносимые данные либо вообще отменить запись.</p>
 *
 *
 * @param array $arFields  Ассоциативный массив параметров валюты. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__add.17dc7357.php">CCurrency::Add</a>.
 *
 * @return bool <p>Возвращает <i>false</i> при отказе, возвращает <i>true</i> при успешном
 * разрешении на добавление.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__add.17dc7357.php">CCurrency::Add</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/events/onbeforecurrencyadd.php
 * @author Bitrix
 */
	public static function OnBeforeCurrencyAdd($arFields){}

/**
 * <p>OnCurrencyAdd - событие, вызываемое в случае успешного добавления новой валюты.</p>
 *
 *
 * @param string $currency  Код валюты.
 *
 * @param array $arFields  Ассоциативный массив параметров валюты. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__add.17dc7357.php">CCurrency::Add</a>.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__add.17dc7357.php">CCurrency::Add</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/events/oncurrencyadd.php
 * @author Bitrix
 */
	public static function OnCurrencyAdd($currency, $arFields){}

/**
 * <p>OnBeforeCurrencyUpdate - событие, вызываемое перед обновлением существующей валюты. Позволяет изменить вносимые данные.</p>
 *
 *
 * @param string $currency  Код валюты.
 *
 * @param array $arFields  Ассоциативный массив параметров валюты. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__update.16586d51.php">CCurrency::Update</a>.
 *
 * @return bool <p>Может вернуть <i>false</i>, если нужно воспрепятствовать обновлению.
 * В противном случае нужно вернуть значение <i>true</i>.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__update.16586d51.php">CCurrency::Update</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/events/onbeforecurrencyupdate.php
 * @author Bitrix
 */
	public static function OnBeforeCurrencyUpdate($currency, $arFields){}

/**
 * <p>OnCurrencyUpdate - событие, вызываемое в случае успешного изменения валюты.</p>
 *
 *
 * @param string $currency  Код валюты.
 *
 * @param array $arFields  Ассоциативный массив параметров валюты. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__update.16586d51.php">CCurrency::Update</a>.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__update.16586d51.php">CCurrency::Update</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/events/oncurrencyupdate.php
 * @author Bitrix
 */
	public static function OnCurrencyUpdate($currency, $arFields){}

/**
 * <p>OnBeforeCurrencyDelete - событие, вызываемое перед удалением валюты в методе <a href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__delete.140a51ba.php">CCurrency::Delete</a>.</p>
 *
 *
 * @param string $currency  Код валюты.
 *
 * @return bool <ul> <li> <i>true</i> - удаление разрешено;</li> <li> <i>false</i> - удаление
 * отменено.</li> </ul>
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__delete.140a51ba.php">CCurrency::Delete</a>
 * </li> </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/events/onbeforecurrencydelete.php
 * @author Bitrix
 */
	public static function OnBeforeCurrencyDelete($currency){}

/**
 * <p>OnCurrencyDelete - событие, вызываемое при удалении существующей валюты в методе <a href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__delete.140a51ba.php">CCurrency::Delete</a>. Может быть использовано для выполнения каких-либо действий при удалении валюты. Возвращаемое значение обработчика игнорируется.</p>
 *
 *
 * @param string $currency  Код валюты.
 *
 * @return mixed <h4>Параметры</h4><table class="tnormal" width="100%"> <tr> <th width="15%">Параметр</th>
 * <th>Описание</th> </tr> <tr> <td>currency</td> <td>Код валюты.</td> </tr> </table> <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__delete.140a51ba.php">CCurrency::Delete</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/events/oncurrencydelete.php
 * @author Bitrix
 */
	public static function OnCurrencyDelete($currency){}

/**
 * <p>CurrencyFormat - событие, вызываемое при форматировании цены в соответствии с настройками валюты.</p>
 *
 *
 * @param float $price  Цена (денежная сумма), которую нужно отформатировать.
 *
 * @param string $currency  Валюта, по правилам которой нужно производить форматирование.
 *
 * @return string <p>Если обработчик возвращает непустую строку, то она будет
 * возвращена и как результат работы метода <a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/currencyformat.php">CCurrencyLang::CurrencyFormat</a>.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/currencyformat.php">CCurrencyLang::CurrencyFormat</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/events/currencyformat.php
 * @author Bitrix
 */
	public static function CurrencyFormat($price, $currency){}

/**
 * <p>OnBeforeCurrencyRateAdd - событие, вызываемое в методе <a href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__add.a9ea23d5.php">CCurrencyRates::Add</a> перед добавлением курса валюты и перед проверкой полей. Позволяет изменить вносимые данные либо вообще отменить запись.</p>
 *
 *
 * @param array $arFields  Ассоциативный массив параметров курса валюты. Перечень
 * допустимых ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__add.a9ea23d5.php">CCurrencyRates::Add</a>.
 *
 * @return bool <p>Возвращает <i>false</i> при отказе, а <i>true</i> - при успешном разрешении
 * на добавление. </p>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__add.a9ea23d5.php">CCurrencyRates::Add</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/events/onbeforecurrencyrateadd.php
 * @author Bitrix
 */
	public static function OnBeforeCurrencyRateAdd($arFields){}

/**
 * <p>OnCurrencyRateAdd - событие, вызываемое в случае успешного добавления нового курса валюты.</p>
 *
 *
 * @param int $ID  Код курса валюты. </h
 *
 * @param array $arFields  Ассоциативный массив параметров курса валюты. Перечень
 * допустимых ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__add.a9ea23d5.php">CCurrencyRates::Add</a>.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__add.a9ea23d5.php">CCurrencyRates::Add</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/events/oncurrencyrateadd.php
 * @author Bitrix
 */
	public static function OnCurrencyRateAdd($ID, $arFields){}

/**
 * <p>OnBeforeCurrencyRateUpdate - событие, вызываемое перед обновлением существующего курса валюты. Позволяет изменить вносимые данные.</p>
 *
 *
 * @param int $ID  Код курса валюты. </h
 *
 * @param array $arFields  Ассоциативный массив параметров курса валюты. Перечень
 * допустимых ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__update.1f36666f.php">CCurrencyRates::Update</a>.
 *
 * @return bool <p>Может вернуть <i>false</i>, если нужно воспрепятствовать обновлению.
 * В противном случае нужно вернуть значение <i>true</i>.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__update.1f36666f.php">CCurrencyRates::Update</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/events/onbeforecurrencyrateupdate.php
 * @author Bitrix
 */
	public static function OnBeforeCurrencyRateUpdate($ID, $arFields){}

/**
 * <p>OnCurrencyRateUpdate - событие, вызываемое в случае успешного изменения курса валюты.</p>
 *
 *
 * @param int $ID  Код курса валюты. </h
 *
 * @param array $arFields  Ассоциативный массив параметров курса валюты. Перечень
 * допустимых ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__update.1f36666f.php">CCurrencyRates::Update</a>.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__update.1f36666f.php">CCurrencyRates::Update</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/events/oncurrencyrateupdate.php
 * @author Bitrix
 */
	public static function OnCurrencyRateUpdate($ID, $arFields){}

/**
 * <p>OnBeforeCurrencyRateDelete - событие, вызываемое перед удалением курса валюты в методе <a href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__delete.28de3643.php">CCurrencyRates::Delete</a>.</p>
 *
 *
 * @param int $ID  Код курса валюты. </h
 *
 * @return bool <ul> <li> <i>true</i> - удаление разрешено;</li> <li> <i>false</i> - удаление
 * отменено.</li> </ul>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__delete.28de3643.php">CCurrencyRates::Delete</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/events/onbeforecurrencyratedelete.php
 * @author Bitrix
 */
	public static function OnBeforeCurrencyRateDelete($ID){}

/**
 * <p>OnCurrencyRateDelete - событие, вызываемое при удалении существующего курса валюты в методе <a href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__delete.28de3643.php">CCurrencyRates::Delete</a>. Может быть использовано для выполнения каких-либо действий при удалении курса валюты.</p>
 *
 *
 * @param int $ID  Код курса валюты. </h
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__delete.28de3643.php">CCurrencyRates::Delete</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/events/oncurrencyratedelete.php
 * @author Bitrix
 */
	public static function OnCurrencyRateDelete($ID){}


}
?>