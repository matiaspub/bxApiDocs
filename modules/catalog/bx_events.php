<?
/**
 * 
 * Класс-контейнер событий модуля <b>catalog</b>
 * 
 */
class _CEventsCatalog {
	/**
	 * перед добавлением купона.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/add.php">CCatalogDiscountCoupon::Add</a>
	 */
	public static function OnBeforeCouponAdd(){}

	/**
	 * перед удалением купона.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CCatalogDiscountCoupon::Delete
	 */
	public static function OnBeforeCouponDelete(){}

	/**
	 * перед изменением купона.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CCatalogDiscountCoupon::Update
	 */
	public static function OnBeforeCouponUpdate(){}

	/**
	 * перед удалением скидки.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.delete.php">CCatalogDiscount::Delete</a>
	 */
	public static function OnBeforeDiscountDelete(){}

	/**
	 * перед удалением документа.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CCatalogDocs::delete
	 */
	public static function OnBeforeDocumentDelete(){}

	/**
	 * перед удалением элемента.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CCatalog::OnBeforeIBlockElementDelete
	 */
	public static function OnBeforeIBlockElementDelete(){}

	/**
	 * при пересчете цены, к которой применена скидка.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__countpricewithdiscount.9c16046d.php">CCatalogProduct::CountPriceWithDiscount</a>
	 */
	public static function OnCountPriceWithDiscount(){}

	/**
	 * в конце метода CCatalogProduct::CountPriceWithDiscount.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__countpricewithdiscount.9c16046d.php">CCatalogProduct::CountPriceWithDiscount</a>
	 */
	public static function OnCountPriceWithDiscountResult(){}

	/**
	 * при добавлении купона.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/add.php">CCatalogDiscountCoupon::Add</a>
	 */
	public static function OnCouponAdd(){}

	/**
	 * при удалении купона.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CCatalogDiscountCoupon::Delete
	 */
	public static function OnCouponDelete(){}

	/**
	 * при изменении купона.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CCatalogDiscountCoupon::Update
	 */
	public static function OnCouponUpdate(){}

	/**
	 * при добавлении скидки.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount_add.php">CCatalogDiscount::Add</a>
	 */
	public static function OnDiscountAdd(){}

	/**
	 * при удалении скидки.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.delete.php">CCatalogDiscount::Delete</a>
	 */
	public static function OnDiscountDelete(){}

	/**
	 * при изменении скидки.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.update.php">CCatalogDiscount::Update</a>
	 */
	public static function OnDiscountUpdate(){}

	/**
	 * после OnBeforeDocumentDelete в методе CCatalogStoreDocsBarcodeAll::OnBeforeDocumentDelete.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CCatalogStoreDocsBarcodeAll::OnBeforeDocumentDelete
	 */
	public static function OnDocumentBarcodeDelete(){}

	/**
	 * после OnDocumentBarcodeDelete в методе CCatalogStoreDocsElementAll::OnDocumentBarcodeDelete.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CCatalogStoreDocsElementAll::OnDocumentBarcodeDelete
	 */
	public static function OnDocumentElementDelete(){}

	/**
	 * при генерации купона.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CatalogGenerateCoupon
	 */
	public static function OnGenerateCoupon(){}

	/**
	 * в конце метода CCatalogProduct::GetDiscount
	 * 
	 * <i>Вызывается в методе:</i>
	 * CCatalogDiscount::GetDiscount
	 */
	public static function OnGetDiscountResult(){}

	/**
	 * в начале метода CCatalogDiscSave::GetDiscount
	 * 
	 * <i>Вызывается в методе:</i>
	 * CCatalogDiscountSave::GetDiscount
	 */
	public static function OnGetDiscountSave(){}

	/**
	 * в начале метода CCatalogProduct::GetNearestQuantityPrice
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getnearestquantityprice.3c16046d.php">CCatalogProduct::GetNearestQuantityPrice</a>
	 */
	public static function OnGetNearestQuantityPrice(){}

	/**
	 * в конце метода CCatalogProduct::GetNearestQuantityPrice
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getnearestquantityprice.3c16046d.php">CCatalogProduct::GetNearestQuantityPrice</a>
	 */
	public static function OnGetNearestQuantityPriceResult(){}

	/**
	 * при поиске оптимальной цены товара.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getoptimalprice.7c16046d.php">CCatalogProduct::GetOptimalPrice</a>
	 */
	public static function OnGetOptimalPrice(){}

	/**
	 * ели не отработало событие OnGetOptimalPrice в методе CCatalogProduct::GetOptimalPrice
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getoptimalprice.7c16046d.php">CCatalogProduct::GetOptimalPrice</a>
	 */
	public static function OnGetOptimalPriceResult(){}

	/**
	 * при вычислении накопительной скидки.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CCatalogDiscountSave::__SaleOrderSumm
	 */
	public static function OnSaleOrderSumm(){}

	/**
	 * перед добавлением новой цены товара.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/add.php">CPrice::Add</a>
	 */
	public static function OnBeforePriceAdd(){}

	/**
	 * перед изменением существующей цены.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/update.php">CPrice::Update</a>
	 */
	public static function OnBeforePriceUpdate(){}

	/**
	 * перед удалением существующей цены.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php">CPrice::Delete</a>
	 */
	public static function OnBeforePriceDelete(){}

	/**
	 * в процессе удаления существующей цены.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php">CPrice::Delete</a>
	 */
	public static function OnPriceDelete(){}

	/**
	 * перед удалением цен в методе CPrice::DeleteByProduct().
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/deletebyproduct.php">CPrice::DeleteByProduct</a>
	 */
	public static function OnBeforeProductPriceDelete(){}

	/**
	 * в процессе удаления цен в методе CPrice::DeleteByProduct().
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/deletebyproduct.php">CPrice::DeleteByProduct</a>
	 */
	public static function OnProductPriceDelete(){}

	/**
	 * перед добавлением товара.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__add.933e0eb4.php">CCatalogProduct::Add</a>
	 */
	public static function OnBeforeProductAdd(){}

	/**
	 * перед удалением каталога.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CCatalog::OnBeforeCatalogDelete
	 */
	public static function OnBeforeCatalogDelete(){}

	/**
	 * перед добавлением группы.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__add.c71829a2.php">CCatalogGroup::Add</a>
	 */
	public static function OnBeforeGroupAdd(){}

	/**
	 * перед удалением группы.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__delete.dbdc5f0d.php">CCatalogGroup::Delete</a>
	 */
	public static function OnBeforeGroupDelete(){}

	/**
	 * перед изменением группы.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__update.a6d06df4.php">CCatalogGroup::Update</a>
	 */
	public static function OnBeforeGroupUpdate(){}

	/**
	 * перед изменением свойств товара.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__update.bc9a623b.php">CCatalogProduct::Update</a>
	 */
	public static function OnBeforeProductUpdate(){}

	/**
	 * при удалении каталога.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__delete.b8b22efb.php">CCatalog::Delete</a>
	 */
	public static function OnCatalogDelete(){}

	/**
	 * при получении скидки.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CCatalogDiscount::GetDiscount
	 */
	public static function OnGetDiscount(){}

	/**
	 * при удалении группы.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__delete.dbdc5f0d.php">CCatalogGroup::Delete</a>
	 */
	public static function OnGroupDelete(){}

	/**
	 * при изменении группы.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccataloggroup/ccataloggroup__update.a6d06df4.php">CCatalogGroup::Update</a>
	 */
	public static function OnGroupUpdate(){}

	/**
	 * при добавлении новой цены.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/add.php">CPrice::Add</a>
	 */
	public static function OnPriceAdd(){}

	/**
	 * в процессе обновления существующей цены.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/update.php">CPrice::Update</a>
	 */
	public static function OnPriceUpdate(){}

	/**
	 * при добавлении товара.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__add.933e0eb4.php">CCatalogProduct::Add</a>
	 */
	public static function OnProductAdd(){}

	/**
	 * в процессе изменения свойств товара.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__update.bc9a623b.php">CCatalogProduct::Update</a>
	 */
	public static function OnProductUpdate(){}

	/**
	 * для изменения логики метода GetDiscountByPrice.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getdiscountbyprice.php">CCatalogDiscount::GetDiscountByPrice</a>
	 */
	public static function OnGetDiscountByPrice(){}

	/**
	 * для изменения логики метода GetDiscountByProduct.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getdiscountbyproduct.php">CCatalogDiscount::GetDiscountByProduct</a>
	 */
	public static function OnGetDiscountByProduct(){}

	/**
	 * после успешного импорта товаров из 1с. Событие компонента <a href="http://dev.1c-bitrix.ru/user_help/content/iblock/components_2/catalog/catalog_import_1c.php" target="_blank">catalog.import.1c</a>

	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function OnSuccessCatalogImport1C(){}

	/**
	 * Ассоциативный массив параметров ценового предложения. Допустимые параметры: 
	 *         <ul>
<li>
<b>PRODUCT_ID </b> - код товара;</li>
	 *          	 
	 *           <li>
<b>EXTRA_ID</b> - код наценки;</li>
	 *          	 
	 *           <li>
<b>CATALOG_GROUP_ID</b> - код типа цены;</li>
	 *          	 
	 *           <li>
<b>PRICE</b> - цена;</li>
	 *          	 
	 *           <li>
<b>CURRENCY</b> - валюта цены;</li>
	 *          	 
	 *           <li>
<b>QUANTITY_FROM</b> - количество товара, начиная с приобретения которого действует эта цена;</li>
	 *          	 
	 *           <li>
<b>QUANTITY_TO</b> - количество товара, при приобретении которого заканчивает действие эта цена. 	 
	 *             <p class="note">Если необходимо, чтобы значения параметров <b>QUANTITY_FROM</b> и <b>QUANTITY_TO</b> не были 	 заданы, необходимо указать у них в качестве значения false либо не задавать поля <b>QUANTITY_FROM</b> и <b>QUANTITY_TO</b> в измененном массиве. </p>
	 *            	</li>
	 *          	 	 </ul>
	 *        Если установлен код наценки, то появляется возможность автоматически пересчитывать эту цену при изменении базовой цены или процента наценки. 
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function arFields(){}

	/**
	 * код цены товара (ценового предложения) 
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function ID(){}

	/**
	 * код товара
	 * 
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function ProductID(){}

	/**
	 * Массив, содержащий ID цен, которые необходимо оставить (не удалять)
	 * 
	 * <i>Вызывается в методе:</i>
	 */
	public static function arExceptionIDs(){}


}
?>