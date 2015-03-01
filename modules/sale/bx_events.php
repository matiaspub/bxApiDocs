<?
/**
 * 
 * Класс-контейнер событий модуля <b>sale</b>
 * 
 */
class _CEventsSale {
	/**
	 * Вызывается перед добавлением типа плательщика, может быть использовано для отмены или модификации данных. <br> <br>
	 * Параметры <br> <br>
	 * &arFields	Массив полей типа плательщика	Add	4.0.6 <br>
	 */
	public static function OnBeforePersonTypeAdd(&$arFields){}
	/**
	 * Вызывается после добавления типа плательщика. <br> <br>
	 * Параметры <br> <br>
	 * ID	Идентификатор добавленного типа плательщика <br> <br>
	 * arFields	Массив полей типа плательщика	Add	4.0.6 <br>
	 */
	public static function OnPersonTypeAdd(&$arFields){}
	/**
	 * Вызывается перед изменением типа плательщика, может быть использовано для отмены или модификации данных. <br> <br>
	 * Параметры <br> <br>
	 * ID	Идентификатор типа плательщика <br> <br>
	 * &arFields	Массив полей типа плательщика	Update	4.0.6 <br>
	 */
	public static function OnBeforePersonTypeUpdate(&$arFields){}
	/**
	 * Вызывается после изменения типа плательщика. <br> <br>
	 * Параметры <br> <br>
	 * ID	Идентификатор типа плательщика <br> <br>
	 * arFields	Массив полей типа плательщика	Update	4.0.6 <br>
	 */
	public static function OnPersonTypeUpdate($arFields){}
	/**
	 * Вызывается перед удалением типа плательщика, может быть использовано для отмены. <br> <br>
	 * Параметры <br> <br>
	 * ID	Идентификатор типа плательщика	Delete	4.0.6 <br>
	 */
	public static function OnBeforePersonTypeDelete($ID){}
	/**
	 * Вызывается после удаления типа плательщика <br> <br>
	 * Параметры <br> <br>
	 * ID	Идентификатор типа плательщика	Delete	4.0.6 <br>
	 */
	public static function OnPersonTypeDelete($ID){}


	/**	Вызывается после добавления города.
	 * Параметры <br>
	 * ID	Идентификатор города <br>
	 * arFields	Массив полей города	AddCity	4.0.6 <br>
	*/
	/**
	 * Вызывается после удаления города. <br>
	 * Параметры <br>
	 * ID	Идентификатор города	DeleteCity	4.0.6 <br>
	*/
	public static function OnCityAdd(){}
	/**
	 * Вызывается после изменения города. <br>
	 * Параметры <br>
	 * ID	Идентификатор города <br>
	 * arFields	Массив полей полей города	UpdateCity	4.0.6 <br>
	*/
	public static function OnCityDelete(){}
	/**
	 * Вызывается перед добавлением города <br>
	 * Параметры <br>
	 * arFields	Массив новых параметров.	AddCity	4.0.6 <br>
	*/
	public static function OnCityUpdate(){}
	/**
	 * Вызывается перед удалением города. <br>
	 * Параметры <br>
	 * ID	Код записи города	DeleteCity	4.0.6 <br>
	*/
	public static function OnBeforeCityAdd(){}
	/**
	 * Вызывается перед обновлением города <br>
	 * Параметры <br>
	 * ID	Код записи города. <br>
	 * arFields	Массив новых параметров города.	UpdateCity	4.0.6 <br>
	*/
	public static function OnBeforeCityDelete(){}
	/**
	 * Вызывается после удаления региона. <br>
	 * Параметры <br>
	 * ID	Идентификатор региона	CSaleLocation::DeleteRegion	12.0.0 <br>
	*/
	public static function OnBeforeCityUpdate(){}
	/**
	 * Вызывается до удаления региона, может быть использовано для отмены удаления. <br>
	 * Параметры <br>
	 * ID	Идентификатор региона	CSaleLocation::DeleteRegion	12.0.0 <br>
	*/
	public static function OnRegionDelete(){}
	/**
	 * Вызывается после обновления региона. <br>
	 * Параметры <br>
	 * ID	Идентификатор региона <br>
	 * arFields	Массив полей региона	CSaleLocation::UpdateRegion	12.0.0 <br>
	*/
	public static function OnBeforeRegionDelete(){}
	/**
	 * Вызывается до обновления региона, может быть использовано для отмены или модификации данных <br>
	 * Параметры <br>
	 * ID	Идентификатор региона <br>
	 * arFields	Массив полей региона	CSaleLocation::UpdateRegion	12.0.0 <br>
	*/
	public static function OnRegionUpdate(){}
	/**
	 * Вызывается перед добавлением региона. <br>
	 * Параметры <br>
	 * arFields	Массив полей региона.	CSaleLocation::AddRegion	12.0.0 <br>
	*/
	public static function OnBeforeRegionUpdate(){}
	/**
	 * Вызывается после добавлением региона <br>
	 * Параметры <br>
	 * ID	Идентификатор региона <br>
	 * arFields	Массив полей региона	CSaleLocation::AddRegion	12.0.0 <br>
	*/
	public static function OnBeforeRegionAdd(){}
	/**
	 * Вызывается после добавления страны <br>
	 * Параметры <br>
	 * ID	Идентификатор страны <br>
	 * arFields	Массив полей страны	AddCountry	4.0.6 <br>
	*/
	public static function OnRegionAdd(){}
	/**
	 * Вызывается после удаления страны. <br>
	 * Параметры <br>
	 * ID	Идентификатор страны	DeleteCountry	4.0.6 <br>
	*/
	public static function OnCountryAdd(){}
	/**
	 * Вызывается после изменения страны <br>
	 * Параметры <br>
	 * ID	Идентификатор страны <br>
	 * arFields	Массив полей страны	UpdateCountry	4.0.6 <br>
	*/
	public static function OnCountryDelete(){}
	/**
	 * Вызывается перед добавлением страны. <br>
	 * Параметры <br>
	 * arFields	Массив новых параметров страны	AddCountry	4.0.6 <br>
	*/
	public static function OnCountryUpdate(){}
	/**
	 * Вызывается перед удалением страны. <br>
	 * Параметры <br>
	 * ID	Идентификатор страны	DeleteCountry	4.0.6 <br>
	*/
	public static function OnBeforeCountryAdd(){}
	/**
	 * Вызывается перед обновлением страны. <br>
	 * Параметры <br>
	 * ID	Идентификатор страны <br>
	 * arFields	Массив новых параметров страны	UpdateCountry	4.0.6 <br>
	*/
	public static function OnBeforeCountryDelete(){}
	/**
	 * Вызывается после удаления местоположения <br>
	 * Параметры <br>
	 * ID	Идентификатор местоположения	Delete	4.0.6 <br>
	*/
	public static function OnBeforeCountryUpdate(){}
	/**
	 * Вызывается после удаления всех местоположений. <br>
	 * Параметров нет	DeleteAll	4.0.6 <br>
	*/
	public static function OnLocationDelete(){}
	/**
	 * Вызывается после добавления местоположения <br>
	 * Параметры <br>
	 * ID	Идентификатор местоположения <br>
	 * arFields	Массив полей местоположения	AddLocation	4.0.6 <br>
	*/
	public static function OnLocationDeleteAll(){}
	/**
	 * Вызывается после обновления местоположения. <br>
	 * Параметры <br>
	 * ID	Идентификатор местоположения <br>
	 * arFields	Массив полей местоположения	UpdateLocation	4.0.6 <br>
	*/
	public static function OnLocationAdd(){}
	/**
	 * Вызывается перед добавлением местоположения <br>
	 * Параметры <br>
	 * arFields	Массив новых параметров местоположения	AddLocation	4.0.6 <br>
	*/
	public static function OnLocationUpdate(){}
	/**
	 * Вызывается перед удалением местоположения. <br>
	 * Параметры <br>
	 * ID	Код записи местоположения	Delete	4.0.6 <br>
	*/
	public static function OnBeforeLocationAdd(){}
	/**
	 * Вызывается перед удалением всех местоположений. <br>
	 * Параметров нет.	DeleteAll	4.0.6 <br>
	*/
	public static function OnBeforeLocationDelete(){}
	/**
	 * Вызывается перед изменением местоположения. <br>
	 * Параметры <br>
	 * ID	Идентификатор местоположения <br>
	 * arFields	Массив полей местоположения	UpdateLocation	4.0.6 <br>
	*/
	public static function OnBeforeLocationDeleteAll(){}
	/**
	 * Вызывается после добавления группы местоположений <br>
	 * Параметры <br>
	 * ID	Идентификатор группы местоположений <br>
	 * arFields	Массив полей группы местоположений	Add	4.0.6 <br>
	*/
	public static function OnBeforeLocationUpdate(){}
	/**
	 * Вызывается после удаления группы местоположений. <br>
	 * Параметры <br>
	 * ID	Идентификатор группы местоположений	Delete	4.0.6 <br>
	*/
	public static function OnLocationGroupAdd(){}
	/**
	 * Вызывается после после группы местоположений <br>
	 * Параметры <br>
	 * ID	Идентификатор группы местоположений <br>
	 * arFields	Массив полей группы местоположений	Update	4.0.6 <br>
	*/
	public static function OnLocationGroupDelete(){}
	/**
	 * Вызывается перед добавлением группы местоположений. <br>
	 * Параметры <br>
	 * arFields	Массив новых параметров группы местоположений	Add	4.0.6 <br>
	*/
	public static function OnLocationGroupUpdate(){}
	/**
	 * Вызывается перед удалением группы местоположений. <br>
	 * Параметры <br>
	 * ID	Код записи группы местоположений	Delete	4.0.6 <br>
	*/
	public static function OnBeforeLocationGroupAdd(){}
	/**
	 * Вызывается перед изменением группы местоположений <br>
	 * Параметры <br>
	 * ID	Идентификатор группы местоположений <br>
	 * arFields	Массив полей группы местоположений	Update	4.0.6 <br>
	 */
	public static function OnBeforeLocationGroupDelete(){}
	/**
	 * Вызывается перед добавлением заказа, может быть использовано для отмены или модификации данных. <br>
	 * Параметры <br>
	 * &arFields	Массив полей заказа	Add	4.0.6 <br>
	 */
	public static function OnBeforeOrderAdd(){}
	/**
	 * Вызывается после добавления заказа. <br>
	 * Параметры <br>
	 * ID	Идентификатор добавленного заказа <br>
	 * arFields	Массив полей заказа	Add	4.0.6 <br>
	 */
	public static function OnOrderAdd(){}
	/**
	 * Вызывается перед изменением заказа, может быть использовано для отмены или модификации данных. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * arFields	Массив полей заказа	Update	4.0.6 <br>
	 */
	public static function OnBeforeOrderUpdate(){}
	/**
	 * Вызывается после изменения заказа. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * arFields	Массив полей заказа	Update	4.0.6 <br>
	 */
	public static function OnOrderUpdate(){}
	/**
	 * Вызывается перед удалением заказа, может быть использовано для отмены. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа	Delete	4.0.6 <br>
	 */
	public static function OnBeforeOrderDelete(){}
	/**
	 * вызывается после удаления заказа <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * Success	Индикатор успешности. Выводит true, если удаление произошло успешно. И false, если удаление заказа не произошло (например, не удалось удалить корзину заказа, или подписку по заказу).	Delete	4.0.6 <br>
	 */
	public static function OnOrderDelete(){}
	/**
	 * Вызывается после калькуляции заказа. В событии передается &arOrder, те можно вносить правки в массив заказа в обработчике события. <br>
	 * Параметры <br>
	 * arOrder	Массив параметров заказа	CSaleOrder::DoCalculateOrder	11.5.0 <br>
	 */
	public static function OnSaleCalculateOrder(){}
	/**
	 * Вызывается после расчёта скидки на заказ. <br>
	 * Параметры <br>
	 * arOrder	Массив параметров заказа	CSaleOrder::DoCalculateOrder	11.5.0 <br>
	 */
	public static function OnSaleCalculateOrderDiscount(){}
	/**
	 * Вызывается после расчёта доставки. <br>
	 * Параметры <br>
	 * arOrder	Массив параметров заказа	CSaleOrder::DoCalculateOrder	11.5.0 <br>
	 */
	public static function OnSaleCalculateOrderDelivery(){}
	/**
	 * Вызывается после расчёта налога на доставку. <br>
	 * Параметры <br>
	 * arOrder	Массив параметров заказа	CSaleOrder::DoCalculateOrder	11.5.0 <br>
	 */
	public static function OnSaleCalculateOrderDeliveryTax(){}
	/**
	 * Вызывается после определения платёжной системы. <br>
	 * Параметры <br>
	 * arOrder	Массив параметров заказа	CSaleOrder::DoCalculateOrder	11.5.0 <br>
	 */
	public static function OnSaleCalculateOrderPaySystem(){}
	/**
	 * Вызывается после определения типа плательщика. <br>
	 * Параметры <br>
	 * arOrder	Массив параметров заказа	CSaleOrder::DoCalculateOrder	11.5.0 <br>
	 */
	public static function OnSaleCalculateOrderPersonType(){}
	/**
	 * Вызывается после формирования свойств плательщика. <br>
	 * Параметры <br>
	 * arOrder	Массив параметров заказа	CSaleOrder::DoCalculateOrder	11.5.0 <br>
	 */
	public static function OnSaleCalculateOrderProps(){}
	/**
	 * Вызывается после формирования массива заказа из корзины. <br>
	 * Параметры <br>
	 * arOrder	Массив параметров заказа	CSaleOrder::DoCalculateOrder	11.5.0 <br>
	 */
	public static function OnSaleCalculateOrderShoppingCart(){}
	/**
	 * Вызывается после определения налогов. <br>
	 * Параметры <br>
	 * arOrder	Массив параметров заказа	CSaleOrder::DoCalculateOrder	11.5.0 <br>
	 */
	public static function OnSaleCalculateOrderShoppingCartTax(){}
	/**
	 * Вызывается перед добавлением статуса заказа, может быть использовано для отмены или модификации данных. <br>
	 * Параметры <br>
	 * &arFields	Массив полей статуса заказа	Add	4.0.6 <br>
	 */
	public static function OnBeforeStatusAdd(){}
	/**
	 * Вызывается после добавления статуса заказа. <br>
	 * Параметры <br>
	 * ID	Идентификатор добавленного статуса заказа <br>
	 * arFields	Массив полей статуса заказа	Add	4.0.6 <br>
	 */
	public static function OnStatusAdd(){}
	/**
	 * Вызывается перед изменением статуса заказа, может быть использовано для отмены или модификации данных. <br>
	 * Параметры <br>
	 * ID	Идентификатор статуса заказа <br>
	 * &arFields	Массив полей статуса заказа	Update	4.0.6 <br>
	 */
	public static function OnBeforeStatusUpdate(){}
	/**
	 * Вызывается после изменения статуса заказа. <br>
	 * Параметры <br>
	 * ID	Идентификатор статуса заказа <br>
	 * arFields	Массив полей статуса заказа <br>
	 * Событие не срабатывает при изменении статуса заказа. Для этого используйте событие OnSaleStatusOrder.	Update	4.0.6 <br>
	 */
	public static function OnStatusUpdate(){}
	/**
	 * Вызывается перед удалением статуса заказа, может быть использовано для отмены. <br>
	 * Параметры <br>
	 * ID	Идентификатор статуса заказа	Delete	4.0.6 <br>
	 */
	public static function OnBeforeStatusDelete(){}
	/**
	 * Вызывается после удаления статуса заказа <br>
	 * Параметры <br>
	 * ID	Идентификатор статуса заказа	Delete	4.0.6 <br>
	 */
	public static function OnStatusDelete(){}
	/**
	 * Вызывается до добавления аффилиата. <br>
	 * Параметры <br>
	 * $arFields	Массив изменяемых параметров	CSaleAffiliate::Add	12.0.4 <br>
	 */
	public static function OnBeforeBAffiliateAdd(){}
	/**
	 * Вызывается после добавления аффилиата. <br>
	 * Параметры <br>
	 * $ID	Код добавленного аффилиата. <br>
	 * arFields	Массив параметров	CSaleAffiliate::Add	12.0.4 <br>
	 */
	public static function OnAfterBAffiliateAdd(){}
	/**
	 * Вызывается до обновления <br>
	 * Параметры <br>
	 * ID	Код аффилиата <br>
	 * $arFields	Массив изменяемых параметров	CSaleAffiliate::Update	12.0.4 <br>
	 */
	public static function OnBeforeAffiliateUpdate(){}
	/**
	 * Вызывается после обновления <br>
	 * Параметры <br>
	 * ID	Код аффилиата <br>
	 * $arFields	Массив параметров	CSaleAffiliate::Update	12.0.4 <br>
	 */
	public static function OnAfterAffiliateUpdate(){}
	/**
	 * Вызывается перед удалением <br>
	 * Параметры <br>
	 * ID	Код аффилиата	CSaleAffiliate::Delete	12.0.4 <br>
	 */
	public static function OnBeforeAffiliateDelete(){}
	/**
	 * Вызывается после удаления <br>
	 * Параметры <br>
	 * ID	Код аффилиата <br>
	 * $bResult	Результат удаления (true/false)Массив параметров	CSaleAffiliate::Delete	12.0.4 <br>
	 */
	public static function OnAfterAffiliateDelete(){}
	/**
	 * Вызывается перед калькуляцией <br>
	 * Параметры <br>
	 * $arAffiliate	Массив параметров аффилиата <br>
	 * $dateFrom, $dateTo	Период калькуляции <br>
	 * $datePlanFrom, $datePlanTo	Период определения плана	CSaleAffiliate::CalculateAffiliate	12.0.4 <br>
	 */
	public static function OnBeforeAffiliateCalculate(){}
	/**
	 * Вызывается после калькуляции <br>
	 * Параметры <br>
	 * affiliateID	Код аффилиата	CSaleAffiliate::CalculateAffiliate	12.0.4 <br>
	 */
	public static function OnAfterAffiliateCalculate(){}
	/**
	 * Вызывается перед выплатой суммы на счёт <br>
	 * Параметры <br>
	 * $arAffiliate	Массив данных аффилиата. <br>
	 * $payType	Статус что делать с суммой.	CSaleAffiliate::PayAffiliate	12.0.4 <br>
	 */
	public static function OnBeforePayAffiliate(){}
	/**
	 * Вызывается после выплат <br>
	 * Параметры <br>
	 * affiliateID	Код аффилиата	CSaleAffiliate::PayAffiliate	12.0.4 <br>
	 */
	public static function OnAfterPayAffiliate(){}
	/**
	 * Вызывается до сохранения плана <br>
	 * Параметры <br>
	 * $arFields	Массив параметров плана	CSaleAffiliatePlan::Add	12.0.4 <br>
	 */
	public static function OnBeforeAffiliatePlanAdd(){}
	/**
	 * Вызывается после сохранения плана <br>
	 * Параметры <br>
	 * $ID	Код плана <br>
	 * $arFields	Массив параметров плана	CSaleAffiliatePlan::Add	12.0.4 <br>
	 */
	public static function OnAfterAffiliatePlanAdd(){}
	/**
	 * Вызывается до обновления плана. <br>
	 * Параметры <br>
	 * $ID	Код плана. <br>
	 * $arFields	Массив параметров плана	CSaleAffiliatePlan::Update	12.0.4 <br>
	 */
	public static function OnBeforeAffiliatePlanUpdate(){}
	/**
	 * Вызывается после обновления плана. <br>
	 * Параметры <br>
	 * $ID	Код плана. <br>
	 * $arFields	Массив параметров плана	CSaleAffiliatePlan::Update	12.0.4 <br>
	 */
	public static function OnAfterAffiliatePlanUpdate(){}
	/**
	 * Вызывается до удаления плана <br>
	 * Параметры <br>
	 * $ID	Код плана	CSaleAffiliatePlan::Delete	12.0.4 <br>
	 */
	public static function OnBeforeAffiliatePlanDelete(){}
	/**
	 * Вызывается после удаления плана <br>
	 * Параметры <br>
	 * $ID	Код плана <br>
	 * $bResult	Результат удаления (true/false)	CSaleAffiliatePlan::Delete	12.0.4 <br>
	 */
	public static function OnAfterAffiliatePlanDelete(){}
	/**
	 * Вызывается перед изменением флага оплаты заказа, может быть использовано для отмены. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * val	Флаг оплаты (Y - оплатить заказ, N - снять оплату заказа) <br>
	 * bWithdraw	Значение true отражает изменение флага на внутреннем счете пользователя; значение false изменяет только флаг, не затрагивая счет <br>
	 * bPay	Если параметр bWithdraw установлен в true, то установка параметра bPay в true приведет к тому, что необходимая сумма денег будет внесена на счет покупателя перед оплатой, а установка в false приведет к тому, что оплата будет происходить целиком с внутреннего счета; если параметр bWithdraw установлен в false, то операции со счетом не производятся и значение параметра bPay не играет роли. <br>
	 * recurringID	Должен быть равен 0 <br>
	 * arAdditionalFields	Массив дополнительно обновляемых параметров (обычно это номер и дата платежного поручения)	PayOrder	4.0.6 <br>
	 */
	public static function OnSaleBeforePayOrder(){}
	/**
	 * Вызывается после изменения флага оплаты заказа. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * val	Флаг оплаты (Y - выставление оплаты, N - снятие оплаты)	PayOrder	4.0.6 <br>
	 */
	public static function OnSalePayOrder(){}
	/**
	 * Вызывается перед изменением флага разрешения доставки заказа, может быть использовано для отмены. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * val	Флаг разрешения доставки (Y - разрешено, N - запрещено) <br>
	 * recurringID	Должен быть равен 0 <br>
	 * arAdditionalFields	Массив дополнительно обновляемых параметров (обычно это номер и дата документа отгрузки)	DeliverOrder	4.0.6 <br>
	 */
	public static function OnSaleBeforeDeliveryOrder(){}
	/**
	 * Вызывается после изменения флага разрешения доставки заказа. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * val	Флаг разрешения доставки (Y - разрешено, N - запрещено)	DeliverOrder	4.0.6 <br>
	 */
	public static function OnSaleDeliveryOrder(){}
	/**
	 * Вызывается перед изменением флага отмены заказа, может быть использовано для отмены. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * val	Флаг отмены заказа (Y - отменено, N - не отменено)	CancelOrder	4.0.6 <br>
	 */
	public static function OnSaleBeforeCancelOrder(){}
	/**
	 * Вызывается после изменения флага отмены заказа. <br>
	 * Параметры <br>
	 * orderId	Идентификатор заказа <br>
	 * value	Флаг отмены заказа (Y - отменено, N - не отменено) <br>
	 * description	Причина отмены. Изменить это поле нельзя, только чтение.	CancelOrder	4.0.6 <br>
	 */
	public static function OnSaleCancelOrder(){}
	/**
	 * Вызывается перед изменением статуса заказа, может быть использовано для отмены. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * val	Идентификатор статуса	StatusOrder	4.0.6 <br>
	 */
	public static function OnSaleBeforeStatusOrder(){}
	/**
	 * Вызывается после изменения статуса заказа. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * val	Идентификатор статуса	StatusOrder	4.0.6 <br>
	 */
	public static function OnSaleStatusOrder(){}
	/**
	 * Вызывается перед добавлением записи в корзину, может быть использовано для отмены или модификации данных. <br>
	 * Параметры <br>
	 * &arFields	Массив полей записи корзины	Add	8.0.3 <br>
	 */
	public static function OnBeforeBasketAdd(){}
	/**
	 * Вызывается после добавления записи в корзину. <br>
	 * Параметры <br>
	 * ID	Идентификатор добавленной записи <br>
	 * arFields	Массив полей записи корзины	Add	8.0.3 <br>
	 */
	public static function OnBasketAdd(){}
	/**
	 * Вызывается перед изменением записи в корзине, может быть использовано для отмены или модификации данных. <br>
	 * Параметры <br>
	 * ID	Идентификатор записи в корзине <br>
	 * &arFields	Массив полей записи корзины	Update	8.0.3 <br>
	 */
	public static function OnBeforeBasketUpdate(){}
	/**
	 * Вызывается после изменения записи в корзине. <br>
	 * Параметры <br>
	 * ID	Идентификатор записи в корзине <br>
	 * arFields	Массив полей записи корзины	CSaleBasket::_Update	8.0.3 <br>
	 */
	public static function OnBasketUpdate(){}
	/**
	 * Вызывается перед изменением корзины после проверки массива $arFields. <br>
	 * Параметры <br>
	 * ID	Код записи товара в корзине <br>
	 * arFields	Массив новых параметров элемента корзины	CSaleBasket::_Update	11.5.0 <br>
	 */
	public static function OnBeforeBasketUpdateAfterCheck(){}
	/**
	 * Вызывается перед удалением записи из корзины, может быть использовано для отмены. <br>
	 * Параметры <br>
	 * ID	Идентификатор записи в корзине	Delete	8.0.3 <br>
	 */
	public static function OnBeforeBasketDelete(){}
	/**
	 * Вызывается после удаления записи из корзины <br>
	 * Параметры <br>
	 * ID	Идентификатор записи в корзине	Delete	8.0.3 <br>
	 */
	public static function OnBasketDelete(){}
	/**
	 * Добавляет переданные купоны. Событие является системным. <br>

	 * Параметры <br>
	 * intUserID	Идентификатор пользователя, для которого передаются купоны. <br>
	 * arCoupons	Один купон или массив передаваемых купонов. <br>
	 * arModules	Массив идентификаторов модулей, которые должны принять список купонов. Если массив пустой, то обработчик события должен обработать все модули, которые имеют обработчик этого события.	CSaleBasket::DoSaveOrderBasket	11.5.0 <br>
	 */
	public static function OnSetCouponList(){}
	/**
	 * Удаляет из списка переданные купоны. Событие является системным. <br>

	 * Параметры <br>
	 * intUserID	Идентификатор пользователя, для которого передаются купоны. <br>
	 * arCoupons	Один купон или массив передаваемых купонов. <br>
	 * arModules	Массив идентификаторов модулей, которые должны удалить из списка переданные купоны. Если массив пустой, то обработчик события должен обработать все модули, которые имеют обработчик этого события.	CSaleBasket::DoSaveOrderBasket	11.5.0 <br>
	 */
	public static function OnClearCouponList(){}
	/**
	 * Полностью удаляет список купонов. Событие является системным. <br>

	 * Параметры <br>
	 * intUserID	Идентификатор пользователя, для которого удаляются купоны. <br>
	 * arModules	Массив идентификаторов модулей, из которых удаляется список купонов. Если массив пустой, то обработчик события должен обработать все модули, которые имеют обработчик этого события.	CSaleBasket::DoSaveOrderBasket	11.5.0 <br>
	 */
	public static function OnDeleteCouponList(){}
	/**
	 * Вызывается перед отправкой письма о новом заказе, может быть использовано для модификации данных, изменения идентификатора типа почтового события, по которому будет осуществлена отправка, и отмены отправки письма. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * &eventName	Тип почтового события по которому будет осуществлена отправка <br>
	 * &arFields	Массив данных о заказе, которые будут подставлены в почтовый шаблон	CSaleOrder::DoSaveOrder	11.5.0 <br>
	 */
	public static function OnOrderNewSendEmail(){}
	/**
	 * Вызывается перед отправкой письма о разрешении доставки заказа, может быть использовано для модификации данных, изменения идентификатора типа почтового события, по которому будет осуществлена отправка, и отмены отправки письма. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * &eventName	Тип почтового события по которому будет осуществлена отправка <br>
	 * &arFields	Массив данных о заказе, которые будут подставлены в почтовый шаблон	DeliverOrder	11.0.0 <br>
	 */
	public static function OnOrderDeliverSendEmail(){}
	/**
	 * Вызывается перед отправкой письма об оплате заказа, может быть использовано для модификации данных, изменения идентификатора типа почтового события, по которому будет осуществлена отправка, и отмены отправки письма. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * &eventName	Тип почтового события по которому будет осуществлена отправка <br>
	 * &arFields	Массив данных о заказе, которые будут подставлены в почтовый шаблон	PayOrder	11.0.0 <br>
	 */
	public static function OnOrderPaySendEmail(){}
	/**
	 * Вызывается перед отправкой письма об отмене заказа, может быть использовано для модификации данных, изменения идентификатора типа почтового события, по которому будет осуществлена отправка, и отмены отправки письма. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * &eventName	Тип почтового события по которому будет осуществлена отправка <br>
	 * &arFields	Массив данных о заказе, которые будут подставлены в почтовый шаблон <br>
	 * CancelOrder	11.0.0 <br>
	 */
	public static function OnOrderCancelSendEmail(){}
	/**
	 * Вызывается в момент формирования письма клиенту о смене статуса заказа. Может быть использовано для переопределения текста письма или его дополнения (для этого обработчик события должен возвращать необходимый текст). <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * val	Идентификатор статуса заказа	StatusOrder	4.0.6 <br>
	 */
	public static function OnSaleStatusEMail(){}
	/**
	 * Вызывается перед отправкой письма о cмене статуса заказа, может быть использовано для модификации данных, изменения идентификатора типа почтового события, по которому будет осуществлена отправка, и отмены отправки письма. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * &eventName	Тип почтового события по которому будет осуществлена отправка <br>
	 * &arFields	Массив данных о заказе, которые будут подставлены в почтовый шаблон <br>
	 * val	Идентификатор статуса заказа	StatusOrder	11.0.0 <br>
	 */
	public static function OnOrderStatusSendEmail(){}
	/**
	 * Вызывается перед отправкой письма о напоминании оплаты заказа, может быть использовано для модификации данных, изменения идентификатора типа почтового события, по которому будет осуществлена отправка, и отмены отправки письма. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * &eventName	Тип почтового события по которому будет осуществлена отправка <br>
	 * &arFields	Массив данных о заказе, которые будут подставлены в почтовый шаблон <br>
	 * CSaleOrder::RemindPayment	11.0.0 <br>
	 */
	public static function OnOrderRemindSendEmail(){}
	/**
	 * Вызывается перед отправкой письма о добавлении заказа на продление подписки, может быть использовано для модификации данных, изменения идентификатора типа почтового события, по которому будет осуществлена отправка, и отмены отправки письма. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * &eventName	Тип почтового события по которому будет осуществлена отправка <br>
	 * &arFields	Массив данных о заказе, которые будут подставлены в почтовый шаблон <br>
	 * CSaleOrder::RemindPayment	11.0.0 <br>
	 */
	public static function OnOrderRecurringSendEmail(){}
	/**
	 * Вызывается перед отправкой письма об отмене заказа на продление подписки, может быть использовано для модификации данных, изменения идентификатора типа почтового события, по которому будет осуществлена отправка, и отмены отправки письма. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * &eventName	Тип почтового события по которому будет осуществлена отправка <br>
	 * &arFields	Массив данных о заказе, которые будут подставлены в почтовый шаблон <br>
	 * CSaleRecurring::CancelRecurring	11.0.0 <br>
	 */
	public static function OnOrderRecurringCancelSendEmail(){}
	/**
	 * Вызывается в компоненте bitrix:sale.order.ajax после формирования списка доступных типов плательщика, может быть использовано для модификации данных. <br>
	 * Параметры <br>
	 * &arResult	Массив arResult компонента <br>
	 * &arUserResult	Массив arUserResult компонента, содержащий текущие выбранные пользовательские данные. <br>
	 * arParams	Массив параметров компонента		11.0.0 <br>
	 */
	public static function OnSaleComponentOrderOneStepPersonType(){}
	/**
	 * Вызывается в компоненте bitrix:sale.order.ajax после формирования списка доступных свойств заказа, может быть использовано для модификации данных. <br>
	 * Параметры <br>
	 * &arResult	Массив arResult компонента <br>
	 * &arUserResult	Массив arUserResult компонента, содержащий текущие выбранные пользовательские данные <br>
	 * arParams	Массив параметров компонента		11.0.0 <br>
	 */
	public static function OnSaleComponentOrderOneStepOrderProps(){}
	/**
	 * Вызывается в компоненте bitrix:sale.order.ajax после формирования списка доступных служб доставки, может быть использовано для модификации данных. <br>
	 * Параметры <br>
	 * &arResult	Массив arResult компонента <br>
	 * &arUserResult	Массив arUserResult компонента, содержащий текущие выбранные пользовательские данные <br>
	 * arParams	Массив параметров компонента		11.0.0 <br>
	 */
	public static function OnSaleComponentOrderOneStepDelivery(){}
	/**
	 * Вызывается в компоненте bitrix:sale.order.ajax после формирования списка доступных платежных систем, может быть использовано для модификации данных. <br>
	 * Параметры <br>
	 * &arResult	Массив arResult компонента <br>
	 * &arUserResult	Массив arUserResult компонента, содержащий текущие выбранные пользовательские данные. <br>
	 * arParams	Массив параметров компонента		11.0.0 <br>
	 */
	public static function OnSaleComponentOrderOneStepPaySystem(){}
	/**
	 * Вызывается в компоненте bitrix:sale.order.ajax после формирования всех данных компонента на этапе заполнения формы заказа, может быть использовано для модификации данных. <br>
	 * Параметры <br>
	 * &arResult	Массив arResult компонента <br>
	 * &arUserResult	Массив arUserResult компонента, содержащий текущие выбранные пользовательские данные <br>
	 * arParams	Массив параметров компонента		11.0.0 <br>
	 */
	public static function OnSaleComponentOrderOneStepProcess(){}
	/**
	 * Вызывается в компоненте bitrix:sale.order.ajax после создания заказа и всех его параметров, после отправки письма, но до редиректа на страницу с информацией о созданном заказе и оплате заказа. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * arOrder	Массив полей заказа	CSaleOrder::DoSaveOrder	11.0.0 <br>
	 */
	public static function OnSaleComponentOrderOneStepComplete(){}
	/**
	 * Вызывается в компоненте bitrix:sale.order.ajax после создания заказа и всех его параметров, после отправки письма, перед выводом страницы об успешно созданном заказе и оплате заказа. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * arOrder	Массив полей заказа <br>
	 * arParams	Массив параметров компонента		11.0.0 <br>
	 */
	public static function OnSaleComponentOrderOneStepFinal(){}
	/**
	 * Вызывается в компоненте bitrix:sale.order.full после создания заказа и всех его параметров. <br>
	 * Параметры <br>
	 * ID	Идентификатор заказа <br>
	 * arOrder	Массив полей заказа <br>
	 * arParams	Массив параметров компонента		8.0.0 <br>
	 */
	public static function OnSaleComponentOrderComplete(){}
	/**
	 * Вызывается в компоненте bitrix:sale.order.ajax перед подсчётом скидки при оформлении заказа. Можно использовать в том числе для присвоения/отъёма у пользователя купонов соответствующих скидок для расчёта индивидуальной скидки. <br>
	 * Параметры <br>
	 * arResult	Массив параметров заказа <br>
	 * arUserResult	Массив параметров пользователя <br>
	 * arParams	Массив параметров компонента		12.0.0 <br>
	 */
	public static function OnSaleComponentOrderOneStepDiscountBefore(){}
	/**
	 * Вызывается при формировании фильтра для списка заказов в административной части. Позволяет модифицировать значения фильтра. Для этого обработчик события должен вернуть модифицированный массив фильтра. <br>
	 * Параметры <br>
	 * arFilter	Сформированный фильтр		5.1.0 <br>
	 */
	public static function OnOrderListFilter(){}
	/**
	 * Вызывается при формировании фильтра для выбора товаров для заказа. Позволяет модифицировать значения фильтра. Для этого обработчик события должен вернуть модифицированный массив фильтра. <br>
	 * Параметры <br>
	 * arFilter	Сформированный фильтр		5.1.0 <br>
	 */
	public static function OnProductSearchFormIBlock(){}
	/**
	 * Вызывается для каждого товара в форме поиска товара. Возвращаемое значение - массив (в формате JScript) новых параметров товара. Может использоваться, если товары в заказе должны иметь не те параметры, которые они имеют в каталоге. <br>
	 * Параметры <br>
	 * ID	Код товара <br>
	 * arParams	Текущее значение, передающееся в качестве параметров товара (массив в формате JScript)		5.1.0 <br>
	 */
	public static function OnProductSearchForm(){}
	/**
	 * Вызывается в методе CSaleAffiliate::CalculateAffiliate, если модуль товара не catalog. Позволяет задать секции для товара, расположенного не в модуле каталог, используемые для планов аффилиатов. <br>
	 * Параметры <br>
	 * MODULE	Идентификатор модуля товара корзины <br>
	 * PRODUCT_ID	Идентификатор товара	CSaleAffiliate::CalculateAffiliate	5.1.0
	 */
	public static function OnAffiliateGetSections(){}
	/**
	 * Вызывается при удалении пользователя Интернет-магазина. <br>
	 * Параметры <br>
	 * ID	Идентификатор пользователя Интернет-магазина	CSaleUser::Delete	3.2.3 <br>
	 * onSaleDeliveryHandlersBuildList	Системное, не описывается.	CSaleDeliveryHandler::__getRegisteredHandlers	6.5.0 <br>
	 */
	public static function OnSaleUserDelete(){}
}
?>