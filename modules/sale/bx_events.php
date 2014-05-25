<?
/**
 * 
 * Класс-контейнер событий модуля <b>sale</b>
 * 
 */
class _CEventsSale {
	/**
	 * Вызывается при формировании фильтра для списка заказов в административной части. Позволяет модифицировать значения фильтра. Для этого обработчик события должен вернуть модифицированный массив фильтра. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>arFilter</i></span></td> <td>Сформированный фильтр</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function OnOrderListFilter(){}

	/**
	 * Сформированный фильтр
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function arFilter(){}

	/**
	 * Вызывается при формировании фильтра для выбора товаров для заказа. Позволяет модифицировать значения фильтра. Для этого обработчик события должен вернуть модифицированный массив фильтра. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>arFilter</i></span></td> <td>Сформированный фильтр</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function OnProductSearchFormIBlock(){}

	/**
	 * Вызывается для каждого товара в форме поиска товара. Возвращаемое значение - массив (в формате JScript) новых параметров товара. Может использоваться, если товары в заказе должны иметь не те параметры, которые они имеют в каталоге. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Код товара</td> </tr>
<tr>
<td><span class="syntax"><i>arParams</i></span></td> <td>Текущее значение, передающееся в качестве параметров товара (массив в формате JScript)</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function OnProductSearchForm(){}

	/**
	 * Идентификатор пользователя Интернет-магазина
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function ID(){}

	/**
	 * Текущее значение, передающееся в качестве параметров товара (массив в формате JScript)
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function arParams(){}

	/**
	 * Вызывается в методе CSaleAffiliate::CalculateAffiliate, если модуль товара не catalog. Позволяет задать секции для товара, расположенного не в модуле каталог, используемые для планов аффилиатов. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody>
<tr>
<td><span class="syntax"><i>MODULE</i></span></td> <td>Идентификатор модуля товара корзины</td> </tr>
<tr>
<td><span class="syntax"><i>PRODUCT_ID</i></span></td> <td>Идентификатор товара</td> </tr>
</tbody></table>

	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CSaleAffiliate::CalculateAffiliate
	 */
	public static function OnAffiliateGetSections(){}

	/**
	 * Идентификатор модуля товара корзины
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function MODULE(){}

	/**
	 * Идентификатор товара
	 * 
	 * <i>Вызывается в методе:</i><br>
	 */
	public static function PRODUCT_ID(){}

	/**
	 * Вызывается при удалении пользователя Интернет-магазина. 
	 *         <br><b>Параметры</b> 		 
	 *         <table width="100%" class="tnormal"><tbody><tr>
<td><span class="syntax"><i>ID</i></span></td> <td>Идентификатор пользователя Интернет-магазина</td> </tr></tbody></table>

	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CSaleUser::Delete
	 */
	public static function OnSaleUserDelete(){}

	/**
	 * Системное, не описывается.
	 *       
	 * 
	 * <i>Вызывается в методе:</i><br>
	 * CSaleDeliveryHandler::__getRegisteredHandlers
	 */
	public static function onSaleDeliveryHandlersBuildList(){}


}
?>