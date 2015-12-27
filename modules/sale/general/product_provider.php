<?
interface IBXSaleProductProvider
{
	/**
	* Method is called to get information about product from the catalog.
	*
	* @param array $arFields			Product description.
	* 	keys are case sensitive:
	*		<ul>
	* 		<li>int PRODUCT_ID				Product id.
	*		<li>float QUANTITY				Product quantity.
	*		<li>string RENEWAL				Product or renewal (Y/N, default N).
	*		<li>int USER_ID					User id (only for admin pages).
	* 		<li>string SITE_ID				Site id (only for admin pages).
	* 		<li>string BASKET_ID			Basket id.
	*		<li>string CURRENCY				Site currency.
	*		<li>string CHECK_QUANTITY		Need check quantity (Y/N, default Y).
	* 		<li>string CHECK_PRICE			Get current optimal price (Y/N, default Y).
	* 		<li>string CHECK_COUPONS		Use coupons (Y/N, default Y).
	*		<li>string CHECK_DISCOUNT		Get optimal price with discount (Y/N, default Y).
	* 		<li>string NOTES				For catalog - old price type.
	*		</ul>
	* @return array|false
	*/
	public static function GetProductData($arFields);

	/**
	* Method is called when the order with products from the basket is placed.
	*
	* @param array $arFields			Product description.
	* 	keys are case sensitive:
	*		<ul>
	* 		<li>int PRODUCT_ID				Product id.
	*		<li>float QUANTITY				Product quantity.
	*		<li>string RENEWAL				Product or renewal (Y/N, default N).
	*		<li>int USER_ID					User id (only for admin pages).
	* 		<li>string SITE_ID				Site id (only for admin pages).
	* 		<li>string BASKET_ID			Basket id.
	*		<li>string CURRENCY				Site currency.
	*		<li>string CHECK_QUANTITY		Need check quantity (Y/N, default Y).
	*		<li>string CHECK_DISCOUNT		Get optimal price with discount (Y/N, default Y).
	*		</ul>
	* @return array|false
	*/
	public static function OrderProduct($arFields);

	/**
	* Method is called when the order with the product is canceled
	*
	* @param array $arFields			Product description.
	* 	keys are case sensitive:
	*		<ul>
	* 		<li>int PRODUCT_ID				Product id.
	*		<li>float QUANTITY				Product quantity.
	*		<li>string CANCEL				Cancel flag (Y/N).
	*		</ul>
	* @return bool
	*/
	public static function CancelProduct($arFields);

	/**
	* Method is called when the delivery is allowed for the order with this product
	*
	* @param array $arFields			Product description.
	* 	keys are case sensitive:
	*		<ul>
	*		<li>int PRODUCT_ID			Product id.
	*		<li>int USER_ID				User id.
	*		<li>bool $arFields["PAID"]	Paid or no.
	*		<li>int ORDER_ID			Order id.
	* 		<li>string BASKET_ID		Basket id.
	*		<ul>
	* @return array|false
	*/
	public static function DeliverProduct($arFields);

	/**
	* Method is called when the product is viewed
	*
	* @param array $arFields			Product description.
	* 	keys are case sensitive:
	*		<ul>
	* 		<li>int PRODUCT_ID				Product id.
	*		<li>int USER_ID					User id (only for admin pages).
	* 		<li>string SITE_ID				Site id (only for admin pages).
	* @return array|false
	*/
	public static function ViewProduct($arFields);

	/**
	* Method is when recurring order is placed with this product
	*
	* @param array $arFields			Product description.
	* 	keys are case sensitive:
	*		<ul>
	* 		<li>int PRODUCT_ID			Product id.
	*		<li>int USER_ID				User id.
	* @return array|false
	*/
	public static function RecurringOrderProduct($arFields);

	/**
	* Method is called to know if product provider supports stores. Return number of stores used as shipping centers available or false if stores are not used.
	*
	* @param array $arParams			Store params.
	* 	keys are case sensitive:
	*		<ul>
	* 		<li>string SITE_ID			Site id.
	*		</ul>
	* @return int
	*/
	public static function GetStoresCount($arParams = array());

	/**
	* Method is called to get information from the product provider
	* about available shipping stores for the specified product. Return list of stores or false if stores are not used.
	*
	* @param  array $arFields			Store params.
	* 	keys are case sensitive:
	*		<ul>
	* 		<li>string SITE_ID			Site id.
	*		<li>int PRODUCT_ID			Product ID.
	*		<li>bool ENUM_BY_ID			Return store ids as keys.
	*		<li>int BASKET_ID			Basket id.
	* @return array|false
	*/
	public static function GetProductStores($arFields);

	/**
	* Method is called when the product should be reserved
	*
	* @param int $arFields["PRODUCT_ID"]
	* @param int $arFields["QUANTITY_ADD"] - quantity which should be added to the QUANTITY_RESERVED value of the product during reservation
	* @param string $arFields["UNDO_RESERVATION"] Y/N
	* @return array
	*/
	public static function ReserveProduct($arFields);

	/**
	* Method is called to check product barcode
	*
	* @param int $arFields["PRODUCT_ID"]
	* @param int $arFields["BARCODE"]
	*/
	public static function CheckProductBarcode($arFields);

	/**
	* Method is called when the product is actually deducted from store
	*
	* @param int $arFields["PRODUCT_ID"]
	* @param int $arFields["QUANTITY"]
	* @param string $arFields["EMULATE"] Y/N
	* @param string $arFields["UNDO_DEDUCTION"] Y/N
	* @param string $arFields["PRODUCT_RESERVED"] Y/N
	* @param array $arFields["STORE_DATA"]
	*/
	public static function DeductProduct($arFields);
}
?>