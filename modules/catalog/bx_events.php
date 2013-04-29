<?
/**
 * 
 * Класс-контейнер событий модуля <b>catalog</b>
 * 
 */
class _CEventsCatalog {
	/**
	 * OnBeforePriceAdd - событие, вызываемое перед добавлением новой цены товара. На вход получает ссылку на массив полей цены (см. <a href="http://dev.1c-bitrix.ruapi_help/catalog/classes/cprice/add.php">CPrice::Add </a>). На вход получает ID цены и ссылку на массив полей.
	 *
	 *
	 *
	 *
	 * @param array &$arFields  Ассоциативный массив параметров ценового предложения.
	 * Допустимые параметры: <ul> <li> <b>PRODUCT_ID </b> - код товара;</li> <li> <b>EXTRA_ID</b> -
	 * код наценки;</li> <li> <b>CATALOG_GROUP_ID</b> - код типа цены;</li> <li> <b>PRICE</b> -
	 * цена;</li> <li> <b>CURRENCY</b> - валюта цены;</li> <li> <b>QUANTITY_FROM</b> - количество
	 * товара, начиная с приобретения которого действует эта цена;</li> <li>
	 * <b>QUANTITY_TO</b> - количество товара, при приобретении которого
	 * заканчивает действие эта цена. <p class="note">Если необходимо, чтобы
	 * значения параметров <b>QUANTITY_FROM</b> и <b>QUANTITY_TO</b> не были заданы,
	 * необходимо указать у них в качестве значения false либо не задавать
	 * поля <b>QUANTITY_FROM</b> и <b>QUANTITY_TO</b> в измененном массиве. </p> </li> </ul> Если
	 * установлен код наценки, то появляется возможность автоматически
	 * пересчитывать эту цену при изменении базовой цены или процента
	 * наценки.
	 *
	 *
	 *
	 * @return OnBeforePriceAdd 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <a href="http://dev.1c-bitrix.ruapi_help/catalog/classes/cprice/add.php">CPrice::Add </a><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforepriceadd.php
	 * @author Bitrix
	 */
	public static function OnBeforePriceAdd(&$arFields){}

	/**
	 * <b>OnBeforePriceDelete</b> - событие, вызываемое перед удалением существующей цены товара в методе CPrice::Delete(). На вход получает ID цены товара (см. <a href="http://dev.1c-bitrix.ruapi_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php">CPrice::Delete </a>). Если обработчик возвращает false, удаление будет отменено.
	 *
	 *
	 *
	 *
	 * @param int $ID  код цены товара (ценового предложения)
	 *
	 *
	 *
	 * @return OnBeforePriceDelete <ul> <li> <i>true</i>, если удаление разрешено </li> <li> <i>false</i>, если удаление
	 * запрещено </li> </ul>
	 *
	 *
	 * <h4>See Also</h4> 
	 * <a href="http://dev.1c-bitrix.ruapi_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php">CPrice::Delete </a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforepricedelete.php
	 * @author Bitrix
	 */
	public static function OnBeforePriceDelete($ID){}

	/**
	 * OnBeforePriceUpdate - событие, вызываемое перед обновлением существующей цены. На входе получает ID цены и ссылку на массив полей. Может вернуть false, если нужно воспрепятствовать обновлению. В противном случае нужно вернуть значение true.
	 *
	 *
	 *
	 *
	 * @param int $ID  Ассоциативный массив параметров ценового предложения.
	 * Допустимые параметры: <ul> <li> <b>PRODUCT_ID </b> - код товара;</li> <li> <b>EXTRA_ID</b> -
	 * код наценки;</li> <li> <b>CATALOG_GROUP_ID</b> - код типа цены;</li> <li> <b>PRICE</b> -
	 * цена;</li> <li> <b>CURRENCY</b> - валюта цены;</li> <li> <b>QUANTITY_FROM</b> - количество
	 * товара, начиная с приобретения которого действует эта цена;</li> <li>
	 * <b>QUANTITY_TO</b> - количество товара, при приобретении которого
	 * заканчивает действие эта цена. <p class="note">Если необходимо, чтобы
	 * значения параметров <b>QUANTITY_FROM</b> и <b>QUANTITY_TO</b> не были заданы,
	 * необходимо указать у них в качестве значения false либо не задавать
	 * поля <b>QUANTITY_FROM</b> и <b>QUANTITY_TO</b> в измененном массиве. </p> </li> </ul> Если
	 * установлен код наценки, то появляется возможность автоматически
	 * пересчитывать эту цену при изменении базовой цены или процента
	 * наценки.
	 *
	 *
	 *
	 * @param array &$arFields  
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <a href="http://dev.1c-bitrix.ruapi_help/catalog/classes/cprice/update.php">CPrice::Update</a><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforepriceupdate.php
	 * @author Bitrix
	 */
	public static function OnBeforePriceUpdate($ID, &$arFields){}

	/**
	 * <p>Событие вызывается перед добавлением товара и перед проверкой полей.</p>
	 *
	 *
	 *
	 *
	 * @param array &$arFields  Ассоциативный массив, ключами которого являются названия
	 * параметров товара, а значениями - новые значения параметров.<br>
	 * Допустимые ключи: <ul> <li> <b>ID</b> - код товара (элемента каталога -
	 * обязательный);</li> <li> <b>QUANTITY</b> - количество товара на складе;</li> <li>
	 * <b>QUANTITY_TRACE</b> - флаг (Y/N) "уменьшать ли количество при заказе";</li> <li>
	 * <b>WEIGHT</b> - вес единицы товара;</li> <li> <b>PRICE_TYPE</b> - тип цены (S -
	 * одноразовый платеж, R - регулярные платежи, T - пробная подписка;)</li>
	 * <li> <b>RECUR_SCHEME_TYPE</b> - тип периода подписки ("H" - час, "D" - сутки, "W" -
	 * неделя, "M" - месяц, "Q" - квартал, "S" - полугодие, "Y" - год);</li> <li>
	 * <b>RECUR_SCHEME_LENGTH</b> - длина периода подписки;</li> <li> <b>TRIAL_PRICE_ID</b> - код
	 * товара, для которого данный товар является пробным;</li> <li>
	 * <b>WITHOUT_ORDER</b> - флаг "Продление подписки без оформления заказа";</li>
	 * <li> <b>VAT_ID</b> - код НДС;</li> <li> <b>VAT_INCLUDED</b> - флаг (Y/N) включен ли НДС в
	 * цену.</li> </ul>
	 *
	 *
	 *
	 * @return mixed <p>Возвращает <i>false</i> при отказе, возвращает <i>true</i> при успешном
	 * разрешении на добавление. Тип данных, возвращаемых функцией -
	 * <i>boolean</i>.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * AddEventHandler("catalog", "OnBeforeProductAdd", Array("My_Class", "OnBeforeProductAdd"));   
	 *   
	 * class My_Class  
	 * {
	 *   function OnBeforeProductAdd(&amp;$arFields)
	 *   { 
	 *     $arFields["QUANTITY_TRACE"] = "Y"; 
	 *     return true;
	 *   }
	 * }
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforeproductadd.php
	 * @author Bitrix
	 */
	public static function OnBeforeProductAdd(&$arFields){}

	/**
	 * <b>OnBeforeProductPriceDelete</b> - событие, вызываемое перед удалением существующих цен товара в методе CPrice::DeleteByProduct(). На вход получает ID товара и массив ID цен, не подлежащих удалению. Если обработчик возвращает <i>false</i>, удаление будет отменено.
	 *
	 *
	 *
	 *
	 * @param int $ProductID  код товара
	 *
	 *
	 *
	 * @param array &$arExceptionIDs  Массив, содержащий ID цен, которые необходимо оставить (не удалять)
	 *
	 *
	 *
	 * @return OnBeforeProductPriceDelete <ul> <li> <i>true</i>, если удаление разрешено </li> <li> <i>false</i>, если удаление
	 * запрещено </li> </ul>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <b>Запрет на удаление цен для товаров</b>/bitrix/php_interface/init.phpfunction DeleteProductPriceStop ($intID,&amp;$arExceptionIDs)<br>{<br>   return false;<br>}<br>AddEventHandler("catalog", "OnBeforeProductPriceDelete", "DeleteProductPriceStop");<br>
<b>Запрет на удаление рублевых цен для товаров</b>function DeleteProductPriceStopRub ($intID,&amp;$arExceptionIDs)<br>{<br>   if (CModule::IncludeModule('catalog'))<br>   {<br>      $rsPrices = CPrice::GetList(array(),array('PRODUCT_ID' =&gt; $intID,'CURRENCY' =&gt; 'RUB'));<br>      while ($arPrice = $rsPrices-&gt;Fetch())<br>      {<br>         $arExceptionIDs[] = $arPrice['ID'];<br>      }<br>   }<br>   return true;<br>}<br>AddEventHandler("catalog", "OnBeforeProductPriceDelete", "DeleteProductPriceStopRub");<br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p><b>Методы</b></p><ul> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/catalog/classes/cprice/deletebyproduct.php">CPrice::DeleteByProduct</a> </li> <li>
	 * <a href="http://dev.1c-bitrix.ruapi_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php">CPrice::Delete</a> </li>
	 * </ul><p><b>События</b></p><ul> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/catalog/events/onproductpricedelete.php">OnProductPriceDelete</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforeproductpricedelete.php
	 * @author Bitrix
	 */
	public static function OnBeforeProductPriceDelete($ProductID, &$arExceptionIDs){}

	/**
	 * <b> OnPriceDelete</b> - событие, вызываемое при удалении существующей цены товара в методе CPrice::Delete(). На вход получает ID цены товара (см. <a href="http://dev.1c-bitrix.ruapi_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php">CPrice::Delete </a>). Может быть использовано для выполнения каких-либо действий при удалении цены. Возвращаемое значение обработчика игнорируется.
	 *
	 *
	 *
	 *
	 * @param int $ID  код цены товара (ценового предложения)
	 *
	 *
	 *
	 * @return OnPriceDelete 
	 *
	 *
	 * <h4>See Also</h4> 
	 * <a href="http://dev.1c-bitrix.ruapi_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php">CPrice::Delete </a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onpricedelete.php
	 * @author Bitrix
	 */
	public static function OnPriceDelete($ID){}

	/**
	 * <i>OnProductPriceDelete</i> - событие, вызываемое в процессе удаления существующих цен товара в методе CPrice::DeleteByProduct(). На вход получает ID товара и массив ID цен, не подлежащих удалению.
	 *
	 *
	 *
	 *
	 * @param int $ProductID  код товара
	 *
	 *
	 *
	 * @param array $arExceptionIDs  Массив, содержащий ID цен, которые необходимо оставить (не удалять)
	 *
	 *
	 *
	 * @return OnProductPriceDelete 
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p><b>Методы</b></p><ul> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/catalog/classes/cprice/deletebyproduct.php">CPrice::DeleteByProduct</a> </li> <li>
	 * <a href="http://dev.1c-bitrix.ruapi_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php">CPrice::Delete</a> </li>
	 * </ul><p><b>События</b></p><ul><li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/catalog/events/onbeforeproductpricedelete.php">OnBeforeProductPriceDelete</a>
	 * </li></ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onproductpricedelete.php
	 * @author Bitrix
	 */
	public static function OnProductPriceDelete($ProductID, $arExceptionIDs){}


}?>