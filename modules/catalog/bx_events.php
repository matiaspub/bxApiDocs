<?
/**
 * 
 * Класс-контейнер событий модуля <b>catalog</b>
 * 
 */
class _CEventsCatalog {
/**
 * <p>OnBeforeDiscountAdd - событие, вызываемое в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount_add.php">CCatalogDiscount::Add</a> перед добавлением новой скидки. Позволяет изменить данные до вызова <b>CCatalogDiscount::CheckFields</b> или вообще отменить запись.</p>
 *
 *
 * @param array &$arFields  Ассоциативный массив параметров. Перечень допустимых ключей
 * массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount_add.php">CCatalogDiscount::Add</a>.
 *
 * @return bool <p>Возвращает <i>false</i> при отказе, возвращает <i>true</i> при успешном
 * разрешении на добавление.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount_add.php">CCatalogDiscount::Add</a>
 * </li> </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforediscountadd.php
 * @author Bitrix
 */
	public static function OnBeforeDiscountAdd(&$arFields){}

/**
 * <p>OnBeforeDiscountUpdate - событие, вызываемое в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.update.php">CCatalogDiscount::Update</a> перед обновлением существующей скидки. Позволяет изменить данные до вызова <b>CCatalogDiscount::CheckFields</b> или отменить обновление.</p>
 *
 *
 * @param int $ID  Код скидки.
 *
 * @param array &$arFields  Ассоциативный массив параметров. Перечень допустимых ключей
 * массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.update.php">CCatalogDiscount::Update</a>.
 *
 * @return bool <p>Может вернуть <i>false</i>, если нужно воспрепятствовать обновлению.
 * В противном случае нужно вернуть значение <i>true</i>.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.update.php">CCatalogDiscount::Update</a>
 * </li> </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforediscountupdate.php
 * @author Bitrix
 */
	public static function OnBeforeDiscountUpdate($ID, &$arFields){}

/**
 * <p>OnBeforeCouponAdd - событие, вызываемое перед добавлением нового купона в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/add.php">CCatalogDiscountCoupon::Add</a>. Позволяет изменить данные до вызова <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/checkfields.php">CCatalogDiscountCoupon::CheckFields</a> или отменить запись.</p>
 *
 *
 * @param array &$arFields  Ассоциативный массив параметров купона. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/add.php">CCatalogDiscountCoupon::Add</a>.
 *
 * @param bool &$bAffectDataFile  Параметр метода <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/add.php">CCatalogDiscountCoupon::Add</a>,
 * указывающий на необходимость перегенерировать файл скидок и
 * купонов (эти действия осуществляет метод <b>CCatalogDiscount::GenerateDataFile</b>).
 * Параметр может принимать значения true/false. <br><br> Параметр является
 * устаревшим с версии 12.0 и передается только для совместимости.
 *
 * @return bool <p>Возвращает <i>false</i> при отказе, возвращает <i>true</i> при успешном
 * разрешении на добавление.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/add.php">CCatalogDiscountCoupon::Add</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforecouponadd.php
 * @author Bitrix
 */
	public static function OnBeforeCouponAdd(&$arFields, &$bAffectDataFile){}

/**
 * <p>OnBeforeCouponDelete - событие, вызываемое перед удалением купона в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/delete.php">CCatalogDiscountCoupon::Delete</a>.</p>
 *
 *
 * @param int $ID  Идентификатор удаляемого купона.
 *
 * @param bool &$bAffectDataFile  Параметр метода <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/add.php">CCatalogDiscountCoupon::Add</a>,
 * указывающий на необходимость перегенерировать файл скидок и
 * купонов (эти действия осуществляет метод <b>CCatalogDiscount::GenerateDataFile</b>).
 * Параметр может принимать значения true/false. <br><br> Параметр является
 * устаревшим с версии 12.0 и передается только для совместимости.
 *
 * @return bool <ul> <li> <i>true</i> - удаление разрешено;</li> <li> <i>false</i> - удаление
 * отменено.</li> </ul>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/delete.php">CCatalogDiscountCoupon::Delete</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforecoupondelete.php
 * @author Bitrix
 */
	public static function OnBeforeCouponDelete($ID, &$bAffectDataFile){}

/**
 * <p>OnBeforeCouponUpdate - событие, вызываемое в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/update.php">CCatalogStore::Update</a> перед обновлением параметров купона. Позволяет изменить данные до вызова <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/checkfields.php">CCatalogDiscountCoupon::CheckFields</a> или отменить обновление.</p>
 *
 *
 * @param int $ID  Идентификатор изменяемого купона.
 *
 * @param array &$arFields  Ассоциативный массив параметров купона. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/update.php">CCatalogDiscountCoupon::Update</a>.
 *
 * @return bool <p>Может вернуть <i>false</i>, если нужно воспрепятствовать обновлению.
 * В противном случае нужно вернуть значение <i>true</i>.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/update.php">CCatalogDiscountCoupon::Update</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforecouponupdate.php
 * @author Bitrix
 */
	public static function OnBeforeCouponUpdate($ID, &$arFields){}

/**
 * <p>OnBeforeDiscountDelete - событие, вызываемое перед удалением скидки в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.delete.php">CCatalogDiscount::Delete</a>.</p>
 *
 *
 * @param int $ID  Идентификатор удаляемой скидки.
 *
 * @return bool <ul> <li> <i>true</i> - удаление разрешено;</li> <li> <i>false</i> - удаление
 * отменено.</li> </ul>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.delete.php">CCatalogDiscount::Delete</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforediscountdelete.php
 * @author Bitrix
 */
	public static function OnBeforeDiscountDelete($ID){}

/**
 * Вызывается перед удалением документа.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CCatalogDocs::delete<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeDocumentDelete(){}

/**
 * Вызывается перед удалением элемента.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CCatalog::OnBeforeIBlockElementDelete<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/index.php
 * @author Bitrix
 */
	public static function OnBeforeIBlockElementDelete(){}

/**
 * <p>OnCouponAdd - событие, вызываемое в случае успешного добавления купона.</p>
 *
 *
 * @param int $ID  Идентификатор добавленного купона.
 *
 * @param array $arFields  Ассоциативный массив параметров купона. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/add.php">CCatalogDiscountCoupon::Add</a>.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/add.php">CCatalogDiscountCoupon::Add</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/oncouponadd.php
 * @author Bitrix
 */
	public static function OnCouponAdd($ID, $arFields){}

/**
 * <p>OnCouponDelete - событие, вызываемое при удалении существующего купона в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/delete.php">CCatalogDiscountCoupon::Delete</a>. Может быть использовано для выполнения каких-либо действий при удалении купона.</p>
 *
 *
 * @param int $ID  Идентификатор купона. </htm
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/delete.php">CCatalogDiscountCoupon::Delete</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/oncoupondelete.php
 * @author Bitrix
 */
	public static function OnCouponDelete($ID){}

/**
 * <p>OnCouponUpdate - событие, вызываемое в случае успешного изменения информации о купоне.</p>
 *
 *
 * @param int $ID  Идентификатор изменяемого купона.
 *
 * @param array $arFields  Ассоциативный массив параметров купона. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/update.php">CCatalogDiscountCoupon::Update</a>.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/update.php">CCatalogDiscountCoupon::Update</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/oncouponupdate.php
 * @author Bitrix
 */
	public static function OnCouponUpdate($ID, $arFields){}

/**
 * <p>OnDiscountAdd - событие, вызываемое в случае успешного добавления скидки.</p>
 *
 *
 * @param int $ID  Идентификатор добавленной скидки.
 *
 * @param array $arFields  Ассоциативный массив параметров скидки. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount_add.php">CCatalogDiscount::Add</a>.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount_add.php">CCatalogDiscount::Add</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/ondiscountadd.php
 * @author Bitrix
 */
	public static function OnDiscountAdd($ID, $arFields){}

/**
 * <p>OnDiscountDelete - событие, вызываемое при удалении существующей скидки в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.delete.php">CCatalogDiscount::Delete</a>. Может быть использовано для выполнения каких-либо действий при удалении скидки.</p>
 *
 *
 * @param int $ID  Идентификатор скидки.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.delete.php">CCatalogDiscount::Delete</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/ondiscountdelete.php
 * @author Bitrix
 */
	public static function OnDiscountDelete($ID){}

/**
 * <p>OnDiscountUpdate - событие, вызываемое в случае успешного изменения параметров скидки.</p>
 *
 *
 * @param int $ID  Идентификатор изменяемой скидки.
 *
 * @param array $arFields  Ассоциативный массив параметров скидки. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.update.php">CCatalogDiscount::Update</a>.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.update.php">CCatalogDiscount::Update</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/ondiscountupdate.php
 * @author Bitrix
 */
	public static function OnDiscountUpdate($ID, $arFields){}

/**
 * Вызывается после OnBeforeDocumentDelete в методе CCatalogStoreDocsBarcodeAll::OnBeforeDocumentDelete.
 * </
 * <i>Вызывается в методе:</i><br>
 * CCatalogStoreDocsBarcodeAll::OnBeforeDocumentDelete<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/index.php
 * @author Bitrix
 */
	public static function OnDocumentBarcodeDelete(){}

/**
 * Вызывается после OnDocumentBarcodeDelete в методе CCatalogStoreDocsElementAll::OnDocumentBarcodeDelete.
 * </
 * <i>Вызывается в методе:</i><br>
 * CCatalogStoreDocsElementAll::OnDocumentBarcodeDelete<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/index.php
 * @author Bitrix
 */
	public static function OnDocumentElementDelete(){}

/**
 * <p>OnGenerateCoupon - событие, вызываемое в функции <b>CatalogGenerateCoupon()</b>. Позволяет заменить стандартный метод генерации кода купона.</p> <p></p> <div class="note"> <b>Примечание:</b> длина купона не может быть больше 32 символов.</div>
 *
 *
 * @return string <p>Сгенерированный код купона.</p> <br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/ongeneratecoupon.php
 * @author Bitrix
 */
	public static function OnGenerateCoupon(){}

/**
 * <p>OnGetDiscountResult - событие, вызываемое перед окончанием работы метода <b>CCatalogDiscount::GetDiscount</b>. Позволяет выполнить некоторые действия над полученными результатами работы этого метода.</p>
 *
 *
 * @param array &$arResult  Массив выбранных скидок.
 *
 * @return mixed <p>Нет.</p></bo<br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/ongetdiscountresult.php
 * @author Bitrix
 */
	public static function OnGetDiscountResult(&$arResult){}

/**
 * Вызывается в начале метода CCatalogDiscSave::GetDiscount.
 * </
 * <i>Вызывается в методе:</i><br>
 * CCatalogDiscountSave::GetDiscount<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/index.php
 * @author Bitrix
 */
	public static function OnGetDiscountSave(){}

/**
 * <p>OnGetNearestQuantityPrice - событие, вызываемое в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getnearestquantityprice.3c16046d.php">CCatalogProduct::GetNearestQuantityPrice</a>. Позволяет заменить стандартный метод поиска количества товара, доступного для покупки.</p>
 *
 *
 * @param int $intProductID  Идентификатор товара.
 *
 * @param int $quantity  
 *
 * @param array $arUserGroups  Количество товара, ближайшее продаваемое количество к которому
 * необходимо найти.
 *
 * @return mixed <p>В результате работы обработчика могут быть возвращены
 * следующие значения:</p> <ul> <li> <i>true</i> - обработчик ничего не сделал,
 * будет выполнена работа метода <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getnearestquantityprice.3c16046d.php">CCatalogProduct::GetNearestQuantityPrice</a>;</li>
 * <li> <i>false</i> - доступное для покупки количество найдено не было,
 * работа метода прерывается;</li> <li>ближайшее к заданному количество
 * товара, которое можно положить в корзину.</li> </ul>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getnearestquantityprice.3c16046d.php">CCatalogProduct::GetNearestQuantityPrice</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/ongetnearestquantityprice.php
 * @author Bitrix
 */
	public static function OnGetNearestQuantityPrice($intProductID, $quantity, $arUserGroups){}

/**
 * <p>OnGetNearestQuantityPriceResult - событие, вызываемое перед окончанием работы метода <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getnearestquantityprice.3c16046d.php">CCatalogProduct::GetNearestQuantityPrice</a>. Позволяет выполнить некоторые действия над полученным результатом работы этого метода.</p>
 *
 *
 * @param int &$nearestQuantity  Количество товара (результат работы метода <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getnearestquantityprice.3c16046d.php">CCatalogProduct::GetNearestQuantityPrice</a>).
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getnearestquantityprice.3c16046d.php">CCatalogProduct::GetNearestQuantityPrice</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/ongetnearestquantitypriceresult.php
 * @author Bitrix
 */
	public static function OnGetNearestQuantityPriceResult(&$nearestQuantity){}

/**
 * <p>OnGetOptimalPrice - событие, вызываемое в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getoptimalprice.7c16046d.php">CCatalogProduct::GetOptimalPrice</a>. Позволяет заменить стандартный метод выборки наименьшей цены для товара (использование этого обработчика для реализации алгоритмов, требующих информации о корзине, невозможно).</p>
 *
 *
 * @param int $intProductID  Идентификатор товара.
 *
 * @param int $quantity  
 *
 * @param array $arUserGroups  Количество товара. </htm
 *
 * @param string $renewal  Массив групп, которым принадлежит пользователь.
 *
 * @param array $arPrices  (Y|N) Флаг продления подписки.
 *
 * @param string $siteID  Массив цен.
 *
 * @param array $arDiscountCoupons  Идентификатор сайта, для которого производится вычисление.
 *
 * @return mixed <p>В результате работы обработчика могут быть возвращены
 * следующие значения:</p> <ul> <li> <i>true</i> - обработчик ничего не сделал,
 * будет выполнена работа метода <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getoptimalprice.7c16046d.php">CCatalogProduct::GetOptimalPrice</a>;</li>
 * <li> <i>false</i> - возникла ошибка, работа метода прерывается;</li>
 * <li>массив, описывающий наименьшую цену для товара.</li> </ul>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getoptimalprice.7c16046d.php">CCatalogProduct::GetOptimalPrice</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/ongetoptimalprice.php
 * @author Bitrix
 */
	public static function OnGetOptimalPrice($intProductID, $quantity, $arUserGroups, $renewal, $arPrices, $siteID, $arDiscountCoupons){}

/**
 * <p>OnGetOptimalPriceResult - событие, вызываемое перед окончанием работы метода <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getoptimalprice.7c16046d.php">CCatalogProduct::GetOptimalPrice</a>. Позволяет выполнить некоторые действия над полученным результатом работы этого метода.</p>
 *
 *
 * @param array &$arResult  Массив, описывающий наименьшую цену для товара.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getoptimalprice.7c16046d.php">CCatalogProduct::GetOptimalPrice</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/ongetoptimalpriceresult.php
 * @author Bitrix
 */
	public static function OnGetOptimalPriceResult(&$arResult){}

/**
 * <p>OnCountPriceWithDiscount - событие, вызываемое в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__countpricewithdiscount.9c16046d.php">CCatalogProduct::CountPriceWithDiscount</a>. Позволяет заменить стандартный метод вычисления цены, получающейся после применения цепочки скидок.</p>
 *
 *
 * @param double $price  Цена.</b
 *
 * @param string $currency  Валюта цены.
 *
 * @param array $arDiscounts  Массив ассоциативных массивов скидок. Вид массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__countpricewithdiscount.9c16046d.php">CCatalogProduct::CountPriceWithDiscount</a>.
 *
 * @return mixed <p>В результате работы обработчика могут быть возвращены
 * следующие значения:</p> <ul> <li> <i>true</i> - обработчик ничего не сделал,
 * будет выполнена работа метода <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__countpricewithdiscount.9c16046d.php">CCatalogProduct::CountPriceWithDiscount</a>;</li>
 * <li> <i>false</i> - возникла ошибка, работа метода прерывается;</li>
 * <li>величина цены.</li> </ul>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__countpricewithdiscount.9c16046d.php">CCatalogProduct::CountPriceWithDiscount</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/oncountpricewithdiscount.php
 * @author Bitrix
 */
	public static function OnCountPriceWithDiscount($price, $currency, $arDiscounts){}

/**
 * <p>OnCountPriceWithDiscountResult - событие, вызываемое перед окончанием работы метода <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__countpricewithdiscount.9c16046d.php">CCatalogProduct::CountPriceWithDiscount</a>. Позволяет выполнить некоторые действия над полученным результатом работы этого метода.</p>
 *
 *
 * @param double &$currentPrice_min  Цена после применения цепочки скидок.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__countpricewithdiscount.9c16046d.php">CCatalogProduct::CountPriceWithDiscount</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/oncountpricewithdiscountresult.php
 * @author Bitrix
 */
	public static function OnCountPriceWithDiscountResult(&$currentPrice_min){}

/**
 * Вызывается при вычислении накопительной скидки.
 * 
 * 
 * <i>Вызывается в методе:</i><br>
 * CCatalogDiscountSave::__SaleOrderSumm<br><br>
 * 
 * 
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/index.php
 * @author Bitrix
 */
	public static function OnSaleOrderSumm(){}

/**
 * OnBeforePriceAdd - событие, вызываемое перед добавлением новой цены товара. На вход получает ID цены и ссылку на массив полей цены (см. <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/add.php">CPrice::Add </a>).
 *
 *
 * @param array &$arFields  Ассоциативный массив параметров ценового предложения.
 * Допустимые параметры: <ul> <li> <b>PRODUCT_ID </b> - код товара;</li> <li> <b>EXTRA_ID</b> -
 * код наценки;</li> <li> <b>CATALOG_GROUP_ID</b> - код типа цены;</li> <li> <b>PRICE</b> -
 * цена;</li> <li> <b>CURRENCY</b> - валюта цены;</li> <li> <b>QUANTITY_FROM</b> - количество
 * товара, начиная с приобретения которого действует эта цена;</li> <li>
 * <b>QUANTITY_TO</b> - количество товара, при приобретении которого
 * заканчивает действие эта цена. <p></p> <div class="note"> <b>Примечание:</b>
 * если необходимо, чтобы значения параметров <b>QUANTITY_FROM</b> и
 * <b>QUANTITY_TO</b> не были заданы, необходимо указать у них в качестве
 * значения false либо не задавать поля <b>QUANTITY_FROM</b> и <b>QUANTITY_TO</b> в
 * измененном массиве. </div> </li> </ul> Если установлен код наценки, то
 * появляется возможность автоматически пересчитывать эту цену при
 * изменении базовой цены или процента наценки.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/add.php">CPrice::Add </a> </li> </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforepriceadd.php
 * @author Bitrix
 */
	public static function OnBeforePriceAdd(&$arFields){}

/**
 * OnBeforePriceUpdate - событие, вызываемое перед обновлением существующей цены. На входе получает ID цены и ссылку на массив полей.
 *
 *
 * @param int $ID  Идентификатор цены. </h
 *
 * @param array &$arFields  Ассоциативный массив параметров ценового предложения.
 * Допустимые параметры: <ul> <li> <b>PRODUCT_ID </b> - код товара;</li> <li> <b>EXTRA_ID</b> -
 * код наценки;</li> <li> <b>CATALOG_GROUP_ID</b> - код типа цены;</li> <li> <b>PRICE</b> -
 * цена;</li> <li> <b>CURRENCY</b> - валюта цены;</li> <li> <b>QUANTITY_FROM</b> - количество
 * товара, начиная с приобретения которого действует эта цена;</li> <li>
 * <b>QUANTITY_TO</b> - количество товара, при приобретении которого
 * заканчивает действие эта цена. <p></p> <div class="note"> <b>Примечание:</b>
 * если необходимо, чтобы значения параметров <b>QUANTITY_FROM</b> и
 * <b>QUANTITY_TO</b> не были заданы, необходимо указать у них в качестве
 * значения false либо не задавать поля <b>QUANTITY_FROM</b> и <b>QUANTITY_TO</b> в
 * измененном массиве. </div> </li> </ul> Если установлен код наценки, то
 * появляется возможность автоматически пересчитывать эту цену при
 * изменении базовой цены или процента наценки.
 *
 * @return bool <p>Может вернуть <i>false</i>, если нужно воспрепятствовать обновлению.
 * В противном случае нужно вернуть значение <i>true</i>.</p>
 *
 * <h4>Example</h4> 
 * <pre>
 * //Обработчик запрещает менять валюту цен на любую, кроме рублей
 * function NationalCurrency(ID, &amp;arFields)  
 * {  
 *    if (array_key_exists('CURRENCY', $arFields) &amp;&amp; $arFields['CURRENCY'] != 'RUB')  
 * {   
 *       return false;  
 *    } 
 * 
 *    else 
 *    { 
 *       return true; 
 *    }
 * }
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul><li><a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/update.php">CPrice::Update</a></li></ul>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforepriceupdate.php
 * @author Bitrix
 */
	public static function OnBeforePriceUpdate($ID, &$arFields){}

/**
 * <b>OnBeforePriceDelete</b> - событие, вызываемое перед удалением существующей цены товара в методе CPrice::Delete(). На вход получает ID цены товара (см. <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php">CPrice::Delete </a>). Если обработчик возвращает false, удаление будет отменено.
 *
 *
 * @param int $ID  код цены товара (ценового предложения)
 *
 * @return OnBeforePriceDelete <ul> <li> <i>true</i>, если удаление разрешено </li> <li> <i>false</i>, если удаление
 * запрещено </li> </ul>
 *
 * <h4>Example</h4> 
 * <pre>
 * В процессе выгрузки после обновления цены товара все ценовые предложения, которых нет в файле выгрузки, удаляются. Для предотвращения удаления цены можно использовать обработчик (пример рабочий, но замедляющий работу системы):
 * 
 * 
 * AddEventHandler("catalog", "OnBeforePriceDelete", "BXOnBeforePriceDelete"); 
 *  
 * function BXOnBeforePriceDelete($ID) { 
 *     $arPrice = CPrice::GetByID($ID); 
 *     if ($arPrice["CATALOG_GROUP_ID"] == 12){ 
 *         return false; 
 *     } 
 * }
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php">CPrice::Delete
 * </a> </li> </ul>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforepricedelete.php
 * @author Bitrix
 */
	public static function OnBeforePriceDelete($ID){}

/**
 * <b> OnPriceDelete</b> - событие, вызываемое при удалении существующей цены товара в методе CPrice::Delete(). На вход получает ID цены товара (см. <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php">CPrice::Delete </a>). Может быть использовано для выполнения каких-либо действий при удалении цены. Возвращаемое значение обработчика игнорируется.
 *
 *
 * @param int $ID  код цены товара (ценового предложения)
 *
 * @return mixed 
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php">CPrice::Delete
 * </a></li> </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onpricedelete.php
 * @author Bitrix
 */
	public static function OnPriceDelete($ID){}

/**
 * <b>OnBeforeProductPriceDelete</b> - событие, вызываемое перед удалением существующих цен товара в методе CPrice::DeleteByProduct(). На вход получает ID товара и массив ID цен, не подлежащих удалению. Если обработчик возвращает <i>false</i>, удаление будет отменено.
 *
 *
 * @param int $ProductID  код товара
 *
 * @param array &$arExceptionIDs  Массив, содержащий ID цен, которые необходимо оставить (не удалять)
 *
 * @return mixed <ul> <li> <i>true</i>, если удаление разрешено </li> <li> <i>false</i>, если удаление
 * запрещено </li> </ul>
 *
 * <h4>Example</h4> 
 * <pre>
 * <b>Запрет на удаление цен для товаров</b> (обработчик в файле <i>/bitrix/php_interface/init.php</i>)
 * 
 * function DeleteProductPriceStop ($intID,&amp;$arExceptionIDs)<br>{<br>   return false;<br>}<br>AddEventHandler("catalog", "OnBeforeProductPriceDelete", "DeleteProductPriceStop");<br>
 * <b>Запрет на удаление рублевых цен для товаров</b>
 * 
 * function DeleteProductPriceStopRub ($intID,&amp;$arExceptionIDs)<br>{<br>   if (CModule::IncludeModule('catalog'))<br>   {<br>      $rsPrices = CPrice::GetList(array(),array('PRODUCT_ID' =&gt; $intID,'CURRENCY' =&gt; 'RUB'));<br>      while ($arPrice = $rsPrices-&gt;Fetch())<br>      {<br>         $arExceptionIDs[] = $arPrice['ID'];<br>      }<br>   }<br>   return true;<br>}<br>AddEventHandler("catalog", "OnBeforeProductPriceDelete", "DeleteProductPriceStopRub");<br>
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <p><b>Методы</b></p></bo<ul> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/deletebyproduct.php">CPrice::DeleteByProduct</a> </li>
 * <li> <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php">CPrice::Delete</a>
 * </li> </ul><p><b>События</b></p></bod<ul> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/events/onproductpricedelete.php">OnProductPriceDelete</a> </li> </ul><a
 * name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforeproductpricedelete.php
 * @author Bitrix
 */
	public static function OnBeforeProductPriceDelete($ProductID, &$arExceptionIDs){}

/**
 * <i>OnProductPriceDelete</i> - событие, вызываемое в процессе удаления существующих цен товара в методе CPrice::DeleteByProduct(). На вход получает ID товара и массив ID цен, не подлежащих удалению.
 *
 *
 * @param int $ProductID  Код товара.
 *
 * @param array $arExceptionIDs  Массив, содержащий ID цен, которые необходимо оставить (не удалять)
 *
 * @return mixed 
 *
 * <h4>See Also</h4> 
 * <p><b>Методы</b></p></bo<ul> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/deletebyproduct.php">CPrice::DeleteByProduct</a> </li>
 * <li> <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php">CPrice::Delete</a>
 * </li> </ul><p><b>События</b></p></bod<ul><li> <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforeproductpricedelete.php">OnBeforeProductPriceDelete</a>
 * </li></ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onproductpricedelete.php
 * @author Bitrix
 */
	public static function OnProductPriceDelete($ProductID, $arExceptionIDs){}

/**
 * <p>Событие вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__add.933e0eb4.php">CCatalogProduct::Add</a> перед добавлением товара. Позволяет изменить вносимые данные до вызова <b>CCatalogProduct::CheckFields</b> либо вообще отменить запись.</p>
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
 * @return bool <p>Возвращает <i>false</i> при отказе, возвращает <i>true</i> при успешном
 * разрешении на добавление.</p> <a name="examples"></a>
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
 * <p>OnBeforeCatalogDelete - событие, вызываемое перед удалением записи о том, что инфоблок является торговым каталогом. Вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__delete.b8b22efb.php">CCatalog::Delete</a>.</p>
 *
 *
 * @param int $ID  Код информационного блока - каталога.
 *
 * @return bool <ul> <li> <i>true</i> - удаление разрешено;</li> <li> <i>false</i> - удаление
 * отменено.</li> </ul>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__delete.b8b22efb.php">CCatalog::Delete</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforecatalogdelete.php
 * @author Bitrix
 */
	public static function OnBeforeCatalogDelete($ID){}

/**
 * <p>OnBeforeGroupAdd - событие, вызываемое перед добавлением нового типа цены в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__add.c71829a2.php">CCatalogGroup::Add</a>. Позволяет изменить данные до вызова <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/checkfields.php">CCatalogGroup::CheckFields</a> или отменить создание.</p>
 *
 *
 * @param array &$arFields  Ассоциативный массив параметров типа цены. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__add.c71829a2.php">CCatalogGroup::Add</a>.
 *
 * @return bool <p>Возвращает <i>false</i> при отказе, возвращает <i>true</i> при успешном
 * разрешении на добавление.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__add.c71829a2.php">CCatalogGroup::Add</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforegroupadd.php
 * @author Bitrix
 */
	public static function OnBeforeGroupAdd(&$arFields){}

/**
 * <p>OnBeforeGroupDelete - событие, вызываемое перед удалением типа цены в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__delete.dbdc5f0d.php">CCatalogGroup::Delete</a>. Событие будет вызвано только в том случае, если тип цены реально существует и не является базовым.</p>
 *
 *
 * @param int $ID  Код удаляемого типа цены.
 *
 * @return bool <ul> <li> <i>true</i> - удаление разрешено;</li> <li> <i>false</i> - удаление
 * отменено.</li> </ul>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__delete.dbdc5f0d.php">CCatalogGroup::Delete</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforegroupdelete.php
 * @author Bitrix
 */
	public static function OnBeforeGroupDelete($ID){}

/**
 * <p>OnBeforeGroupUpdate - событие, вызываемое в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__update.a6d06df4.php">CCatalogGroup::Update</a> перед обновлением типа цены. Позволяет изменить данные до вызова <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/checkfields.php">CCatalogGroup::CheckFields</a> или отменить обновление.</p>
 *
 *
 * @param int $ID  Код изменяемого типа цены.
 *
 * @param array &$arFields  Ассоциативный массив параметров типа цены. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__update.a6d06df4.php">CCatalogGroup::Update</a>.
 *
 * @return bool <p>Может вернуть <i>false</i>, если нужно воспрепятствовать обновлению.
 * В противном случае нужно вернуть значение <i>true</i>.</p>
 *
 * <h4>Example</h4> 
 * <pre>
 * AddEventHandler("catalog", "OnBeforeGroupUpdate", Array("My_Class", "OnBeforeGroupUpdate"));
 * 
 * class My_Class
 * {
 * //запрещает редактировать базовый тип цен
 *     function OnBeforeGroupUpdate($ID, &amp;$arFields)
 *     {
 *         $base = (string)(isset($arFields['BASE']) ? $arFields['BASE'] : '');
 *         if ($base == '')
 *         {
 *             $groupIterator = CCatalogGroup::GetListEx(
 *                 array(),
 *                 array('ID' =&gt; $ID),
 *                 false,
 *                 false,
 *                 array('ID', 'BASE')
 *             );
 *             if ($group = $groupIterator-&gt;Fetch())
 *             {
 *                 $base = $group['BASE'];
 *                 unset($group);
 *             }
 *             unset($groupIterator);
 *         }
 *         return ($base != 'Y');
 *     }
 * }
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__update.a6d06df4.php">CCatalogGroup::Update</a></li>
 * </ul>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforegroupupdate.php
 * @author Bitrix
 */
	public static function OnBeforeGroupUpdate($ID, &$arFields){}

/**
 * <p>OnBeforeProductUpdate - событие, вызываемое в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__update.bc9a623b.php">CCatalogProduct::Update</a> перед обновлением параметров товара. Позволяет изменить данные до вызова <b>CCatalogProduct::CheckFields</b> либо отменить обновление.</p>
 *
 *
 * @param int $ID  Идентификатор товара.
 *
 * @param array &$arFields  Ассоциативный массив параметров товара. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__update.bc9a623b.php">CCatalogProduct::Update</a>.
 *
 * @return bool <p>Может вернуть <i>false</i>, если нужно воспрепятствовать обновлению.
 * В противном случае нужно вернуть значение <i>true</i>.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__update.a6d06df4.php">CCatalogGroup::Update</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforeproductupdate.php
 * @author Bitrix
 */
	public static function OnBeforeProductUpdate($ID, &$arFields){}

/**
 * <p>OnCatalogDelete - событие, вызываемое при удалении записи о том, что инфоблок является торговым каталогом. Вызывается в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__delete.b8b22efb.php">CCatalog::Delete</a> и может быть использовано для выполнения каких-либо действий при удалении.</p>
 *
 *
 * @param int $ID  Идентификатор информационного блока - каталога.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__delete.b8b22efb.php">CCatalog::Delete</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/oncatalogdelete.php
 * @author Bitrix
 */
	public static function OnCatalogDelete($ID){}

/**
 * <p>OnGetDiscount - событие, вызываемое в методе <b>CCatalogDiscount::GetDiscount</b>. Позволяет заменить стандартный метод выбора скидок.</p>
 *
 *
 * @param int $intProductID  Идентификатор товара.
 *
 * @param int $intIBlockID  Идентификатор инфоблока.
 *
 * @param array $arCatalogGroups  Массив идентификаторов типов цен, для которых необходимо вернуть
 * скидки.
 *
 * @param array $arUserGroups  Массив групп, к которым принадлежит пользователь.
 *
 * @param string $strRenewal  Флаг "Продление подписки".
 *
 * @param string $siteID  Идентификатор сайта.
 *
 * @param array $arDiscountCoupons  Массив купонов, которые влияют на выборку скидок. Если задано
 * значение <i>false</i>, то массив купонов будет взят из
 * <b>CCatalogDiscountCoupon::GetCoupons</b>. Если будет передан пустой массив купонов,
 * то купонные скидки учитываться не будут вообще.
 *
 * @param bool $boolSKU  Определяет нужно ли выполнять проверку (true), что товар является
 * торговым предложением, или проверку не проводить (false). <br><br>Если
 * параметр принимает значение true и товар является торговым
 * предложением, то выборка скидок будет сделана и для основного
 * товара.
 *
 * @param bool $boolGetIDS  Параметр определяет возвращать только идентификаторы скидок (true)
 * или полную информацию по скидкам (false).
 *
 * @return mixed <p>В результате работы обработчика могут быть возвращены
 * следующие значения:</p> <ul> <li> <i>true</i> - обработчик ничего не сделал,
 * будет выполнена работа метода <b>CCatalogDiscount::GetDiscount</b>;</li> <li> <i>false</i> -
 * возникла ошибка, работа метода прерывается;</li> <li>массив, где
 * каждый элемент - это идентификатор скидки (если <i>boolGetIDS</i> равен
 * <i>true</i>) или ассоциативный массив (<i>boolGetIDS</i> принимает значение
 * <i>false</i>) с ключами: <ul> <li> <b>ID</b> - код записи;</li> <li> <b>TYPE</b> - тип
 * записи;</li> <li> <b>SITE_ID</b> - сайт;</li> <li> <b>ACTIVE</b> - флаг активности;</li> <li>
 * <b>ACTIVE_FROM</b> - дата начала действия скидки;</li> <li> <b>ACTIVE_TO</b> - дата
 * окончания действия скидки;</li> <li> <b>RENEWAL</b> - флаг "Скидка на
 * продление";</li> <li> <b>NAME</b> - название скидки;</li> <li> <b>SORT</b> - индекс
 * сортировки;</li> <li> <b>MAX_DISCOUNT</b> - максимальная величина скидки;</li> <li>
 * <b>VALUE_TYPE</b> - тип скидки (P - в процентах, F - фиксированая величина);</li>
 * <li> <b>VALUE</b> - величина скидки;</li> <li> <b>CURRENCY</b> - валюта;</li> <li> <b>PRIORITY</b> -
 * приоритет применимости;</li> <li> <b>LAST_DISCOUNT</b> - флаг "Прекратить
 * дальнейшее применение скидок";</li> <li> <b>COUPON</b> - код купона;</li> <li>
 * <b>COUPON_ONE_TIME</b> - тип купона (Y - купон на 1 позицию заказа, O - купон на 1
 * заказ, N - многоразовый купон);</li> <li> <b>COUPON_ACTIVE</b> - флаг активности
 * купона;</li> <li> <b>UNPACK</b> - строка фильтра, который проверяет
 * попадание товара под скидку.</li> </ul> </li> </ul> <br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/ongetdiscount.php
 * @author Bitrix
 */
	public static function OnGetDiscount($intProductID, $intIBlockID, $arCatalogGroups, $arUserGroups, $strRenewal, $siteID, $arDiscountCoupons, $boolSKU, $boolGetIDS){}

/**
 * <p>OnGroupAdd - событие, вызываемое в случае успешного создания нового типа цены.</p>
 *
 *
 * @param int $groupID  Идентификатор типа цены.
 *
 * @param array $arFields  Ассоциативный массив параметров типа цены. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__add.c71829a2.php">CCatalogGroup::Add</a>.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__add.c71829a2.php">CCatalogGroup::Add</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/ongroupadd.php
 * @author Bitrix
 */
	public static function OnGroupAdd($groupID, $arFields){}

/**
 * <p>OnGroupDelete - событие, вызываемое при удалении существующего типа цены в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__delete.dbdc5f0d.php">CCatalogGroup::Delete</a>. Может быть использовано для выполнения каких-либо действий при удалении типа цены.</p> <p></p> <div class="note"> <b>Примечание:</b> событие может быть вызвано, если тип цены не является базовым.</div>
 *
 *
 * @param int $ID  Идентификатор типа цены.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__delete.dbdc5f0d.php">CCatalogGroup::Delete</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/ongroupdelete.php
 * @author Bitrix
 */
	public static function OnGroupDelete($ID){}

/**
 * <p>OnGroupUpdate - событие, вызываемое в случае успешного изменения типа цены.</p>
 *
 *
 * @param int $ID  Код изменяемого типа цены.
 *
 * @param array $arFields  Ассоциативный массив параметров типа цены. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__update.a6d06df4.php">CCatalogGroup::Update</a>.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__update.a6d06df4.php">CCatalogGroup::Update</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/ongroupupdate.php
 * @author Bitrix
 */
	public static function OnGroupUpdate($ID, $arFields){}

/**
 * <p>OnPriceAdd - событие, вызываемое в случае успешного создания нового ценового предложения (новой цены) для товара.</p>
 *
 *
 * @param int $ID  Идентификатор добавленного ценового предложения.
 *
 * @param array $arFields  Ассоциативный массив параметров ценового предложения. Перечень
 * допустимых ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/add.php">CPrice::Add</a>.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/add.php">CPrice::Add</a></li> </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onpriceadd.php
 * @author Bitrix
 */
	public static function OnPriceAdd($ID, $arFields){}

/**
 * <p>OnPriceUpdate - событие, вызываемое в случае успешного изменения ценового предложения (цены) товара.</p>
 *
 *
 * @param int $ID  Код изменяемого ценового предложения.
 *
 * @param array $arFields  Ассоциативный массив параметров ценового предложения. Перечень
 * допустимых ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/update.php">CPrice::Update</a>.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/update.php">CPrice::Update</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onpriceupdate.php
 * @author Bitrix
 */
	public static function OnPriceUpdate($ID, $arFields){}

/**
 * <p>OnProductAdd - событие, вызываемое в случае успешного добавления параметров товара к элементу каталога.</p>
 *
 *
 * @param int $ID  Идентификатор товара.
 *
 * @param array $arFields  Ассоциативный массив параметров товара. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__add.933e0eb4.php">CCatalogProduct::Add</a>.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__add.933e0eb4.php">CCatalogProduct::Add</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onproductadd.php
 * @author Bitrix
 */
	public static function OnProductAdd($ID, $arFields){}

/**
 * <p>OnProductUpdate - событие, вызываемое в случае успешного изменения параметров товара.</p>
 *
 *
 * @param int $ID  Идентификатор товара.
 *
 * @param array $arFields  Ассоциативный массив параметров товара. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__update.bc9a623b.php">CCatalogProduct::Update</a>.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__update.bc9a623b.php">CCatalogProduct::Update</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onproductupdate.php
 * @author Bitrix
 */
	public static function OnProductUpdate($ID, $arFields){}

/**
 * <p>OnGetDiscountByPrice - событие, вызываемое при вычислении скидки на цену с кодом <i>productPriceID</i> товара для пользователя, принадлежащего к группам пользователей <i>arUserGroups</i>. Позволяет изменить логику работы метода <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getdiscountbyprice.php">CCatalogDiscount::GetDiscountByPrice</a>.</p>
 *
 *
 * @param int $productPriceID  Код цены.</bod
 *
 * @param array $arUserGroups  Массив групп, которым принадлежит пользователь.
 *
 * @param string $renewal  Флаг "Продление подписки".
 *
 * @param string $siteID  Идентификатор сайта.
 *
 * @param array $arDiscountCoupons  Массив купонов, которые влияют на выборку скидок. Если задано
 * значение <i>false</i>, то массив купонов будет взят из
 * <b>CCatalogDiscountCoupon::GetCoupons</b>. Если будет передан пустой массив купонов,
 * то купонные скидки учитываться не будут вообще.
 *
 * @return mixed <p>Если обработчик возвращает значение, отличное от <i>true</i>, то это
 * значение будет возвращено и как результат работы метода <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getdiscountbyprice.php">CCatalogDiscount::GetDiscountByPrice</a>.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getdiscountbyprice.php">CCatalogDiscount::GetDiscountByPrice</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/ongetdiscountbyprice.php
 * @author Bitrix
 */
	public static function OnGetDiscountByPrice($productPriceID, $arUserGroups, $renewal, $siteID, $arDiscountCoupons){}

/**
 * <p>OnGetDiscountByProduct - событие, вызываемое при вычислении скидки на товар с кодом <i>productID</i> для пользователя, принадлежащего к группам пользователей <i>arUserGroups</i>.</p>
 *
 *
 * @param int $productID  Код товара.
 *
 * @param array $arUserGroups  Массив групп, которым принадлежит пользователь.
 *
 * @param string $renewal  Флаг "Продление подписки".
 *
 * @param array $arCatalogGroups  Массив типов цен, для которых искать скидку.
 *
 * @param string $siteID  Идентификатор сайта.
 *
 * @param array $arDiscountCoupons  Массив купонов, которые влияют на выборку скидок. Если задано
 * значение <i>false</i>, то массив купонов будет взят из
 * <b>CCatalogDiscountCoupon::GetCoupons</b>. Если будет передан пустой массив купонов,
 * то купонные скидки учитываться не будут вообще.
 *
 * @return mixed <p>Если обработчик возвращает значение, отличное от <i>true</i>, то это
 * значение будет возвращено и как результат работы метода <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getdiscountbyproduct.php">CCatalogDiscount::GetDiscountByProduct</a>.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getdiscountbyproduct.php">CCatalogDiscount::GetDiscountByProduct</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/ongetdiscountbyproduct.php
 * @author Bitrix
 */
	public static function OnGetDiscountByProduct($productID, $arUserGroups, $renewal, $arCatalogGroups, $siteID, $arDiscountCoupons){}

/**
 * <p>OnBeforeCatalogImport1C - событие, вызываемое перед началом процедуры обмена: перед загрузкой XML в базу данных после загрузки файла на сервер.</p>
 *
 *
 * @param array $arParams  Параметры подключения компонента обмена.
 *
 * @param string $ABS_FILE_NAME  Полный путь к XML-файлу обмена.
 *
 * @return string <p>Если возвращает непустую строку, то обмен завершается с
 * сообщением об ошибке равным этой строке.</p> <br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforecatalogimport1c.php
 * @author Bitrix
 */
	public static function OnBeforeCatalogImport1C($arParams, $ABS_FILE_NAME){}

/**
 * <p>OnSuccessCatalogImport1C - событие, вызываемое после окончания обмена одним XML-файлом.</p>
 *
 *
 * @param array $arParams  Параметры подключения компонента обмена.
 *
 * @param string $ABS_FILE_NAME  Полный путь к XML-файлу обмена.
 *
 * @return void <p>Нет.</p></bo<br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onsuccesscatalogimport1c.php
 * @author Bitrix
 */
	public static function OnSuccessCatalogImport1C($arParams, $ABS_FILE_NAME){}

/**
 * <p>OnBeforeStoreProductAdd - событие, вызываемое перед созданием новой записи о добавлении товара на склад. Позволяет изменить данные до вызова <b>CCatalogStoreProduct::CheckFields</b> или отменить запись.</p>
 *
 *
 * @param array $arFields  Ассоциативный массив параметров. Допустимые ключи: <ul> <li>PRODUCT_ID - ID
 * товара;</li> <li>STORE_ID - ID склада;</li> <li>AMOUNT - количество товара.</li> </ul>
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstoreproduct/add.php">CCatalogStoreProduct::Add</a> </li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforestoreproductadd.php
 * @author Bitrix
 */
	public static function OnBeforeStoreProductAdd($arFields){}

/**
 * <p>OnBeforeStoreProductDelete - событие, вызываемое перед удалением записи из таблицы остатков товара с кодом ID в методе <b>CCatalogStoreProductAll::Delete</b>. Если обработчик возвращает <i>false</i>, то удаление будет отменено. </p>
 *
 *
 * @param int $ID  Код записи для удаления.
 *
 * @return mixed <ul> <li> <i>true</i> - удаление разрешено;</li> <li> <i>false</i> - удаление
 * отменено.</li> </ul> <br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforestoreproductdelete.php
 * @author Bitrix
 */
	public static function OnBeforeStoreProductDelete($ID){}

/**
 * <p>OnBeforeStoreProductUpdate - событие, вызываемое перед изменением записи в таблице остатков товара. Позволяет изменить данные до вызова <b>CCatalogStoreProduct::CheckFields</b> или отменить обновление.</p>
 *
 *
 * @param intI $D  
 *
 * @param array $arFields  Ассоциативный массив параметров. Допустимые ключи: <ul> <li>PRODUCT_ID - ID
 * товара;</li> <li>STORE_ID - ID склада;</li> <li>AMOUNT - количество товара.</li> </ul>
 *
 * @return mixed <p>Нет.</p></bo<br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforestoreproductupdate.php
 * @author Bitrix
 */
	public static function OnBeforeStoreProductUpdate($D, $arFields){}

/**
 * <p>OnStoreProductAdd - событие, вызываемое в случае успешного создания новой записи о добавлении товара на склад.</p>
 *
 *
 * @param int $ID  Код записи.
 *
 * @param array $arFields  Ассоциативный массив параметров. Допустимые ключи: <ul> <li>PRODUCT_ID - ID
 * товара;</li> <li>STORE_ID - ID склада;</li> <li>AMOUNT - количество товара.</li> </ul>
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstoreproduct/add.php">CCatalogStoreProduct::Add</a> </li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onstoreproductadd.php
 * @author Bitrix
 */
	public static function OnStoreProductAdd($ID, $arFields){}

/**
 * <p>OnStoreProductDelete - событие, вызываемое в случае успешного удаления записи из таблицы остатков товара на складе (метод <b>CCatalogStoreProductAll::Delete</b>).</p>
 *
 *
 * @return mixed <p>Нет.</p></bo<br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onstoreproductdelete.php
 * @author Bitrix
 */
	public static function OnStoreProductDelete(){}

/**
 * <p>OnStoreProductUpdate - событие, вызываемое в случае успешного изменения записи в таблице остатков товара с кодом ID в методе <b>CCatalogStoreProductAll::Update</b>. </p>
 *
 *
 * @param int $ID  Код записи для изменения.
 *
 * @param array $arFields  Ассоциативный массив параметров. Допустимые ключи: <ul> <li>PRODUCT_ID - ID
 * товара;</li> <li>STORE_ID - ID склада;</li> <li>AMOUNT - количество товара.</li> </ul>
 *
 * @return mixed <p>Нет.</p></bo<br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onstoreproductupdate.php
 * @author Bitrix
 */
	public static function OnStoreProductUpdate($ID, $arFields){}

/**
 * <p>OnBeforeCatalogStoreUpdate - событие, вызываемое в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/update.php">CCatalogStore::Update</a> перед обновлением параметров склада. Позволяет изменить данные до вызова <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/checkfields.php">CCatalogStore::CheckFields</a> или отменить обновление.</p>
 *
 *
 * @param int $id  Идентификатор изменяемого склада.
 *
 * @param array &$arFields  Ассоциативный массив параметров склада. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/update.php">CCatalogStore::Update</a>.
 *
 * @return bool <p>Может вернуть <i>false</i>, если нужно воспрепятствовать обновлению.
 * В противном случае нужно вернуть значение <i>true</i>.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/update.php">CCatalogStore::Update</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforecatalogstoreupdate.php
 * @author Bitrix
 */
	public static function OnBeforeCatalogStoreUpdate($id, &$arFields){}

/**
 * <p>OnCatalogStoreUpdate - событие, вызываемое в случае успешного изменения параметров склада.</p>
 *
 *
 * @param int $id  Идентификатор изменяемого склада.
 *
 * @param array $arFields  Ассоциативный массив параметров склада. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/update.php">CCatalogStore::Update</a>.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/update.php">CCatalogStore::Update</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/oncatalogstoreupdate.php
 * @author Bitrix
 */
	public static function OnCatalogStoreUpdate($id, $arFields){}

/**
 * <p>OnBeforeCatalogStoreDelete - событие, вызываемое перед удалением склада в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/delete.php">CCatalogStore::Delete</a>.</p>
 *
 *
 * @param int $id  Идентификатор удаляемого склада.
 *
 * @return bool <ul> <li> <i>true</i> - удаление разрешено;</li> <li> <i>false</i> - удаление
 * отменено.</li> </ul>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/delete.php">CCatalogStore::Delete</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforecatalogstoredelete.php
 * @author Bitrix
 */
	public static function OnBeforeCatalogStoreDelete($id){}

/**
 * <p>OnCatalogStoreDelete - событие, вызываемое при удалении существующего склада в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/delete.php">CCatalogStore::Delete</a>. Может быть использовано для выполнения каких-либо действий при удалении склада.</p>
 *
 *
 * @param int $id  Идентификатор склада.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/delete.php">CCatalogStore::Delete</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/oncatalogstoredelete.php
 * @author Bitrix
 */
	public static function OnCatalogStoreDelete($id){}

/**
 * <p>OnBeforeCatalogStoreAdd - событие, вызываемое перед добавлением нового склада в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/add.php">CCatalogStore::Add</a>. Позволяет изменить данные до вызова <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/checkfields.php">CCatalogStore::CheckFields</a> или отменить создание.</p>
 *
 *
 * @param array &$arFields  Ассоциативный массив параметров склада. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/add.php">CCatalogStore::Add</a>.
 *
 * @return bool <p>Возвращает <i>false</i> при отказе, возвращает <i>true</i> при успешном
 * разрешении на добавление.</p>
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/add.php">CCatalogStore::Add</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforecatalogstoreadd.php
 * @author Bitrix
 */
	public static function OnBeforeCatalogStoreAdd(&$arFields){}

/**
 * <p>OnCatalogStoreAdd - событие, вызываемое в случае успешного добавления нового склада.</p>
 *
 *
 * @param int $lastId  Идентификатор добавленного склада.
 *
 * @param array $arFields  Ассоциативный массив параметров склада. Перечень допустимых
 * ключей массива смотрите в <a
 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/add.php">CCatalogStore::Add</a>.
 *
 * @return mixed <p>Нет.</p></bo
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/add.php">CCatalogStore::Add</a></li>
 * </ul><br><br>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/events/oncatalogstoreadd.php
 * @author Bitrix
 */
	public static function OnCatalogStoreAdd($lastId, $arFields){}


}
?>