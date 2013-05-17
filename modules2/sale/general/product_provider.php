<?
interface IBXSaleProductProvider
{
	/**
	* Method is called to get information about product from the catalog
	*
	* @param int $arFields["PRODUCT_ID"]
	* @param int $arFields["QUANTITY"]
	* @param string $arFields["RENEWAL"] (Y/N)
	* @param int $arFields["USER_ID"]
	* @param string $arFields["SITE_ID"]
	* @return array Product parameters
	*/
	public static function GetProductData($arFields);

	/**
	* Method is called when the order with products from the basket is placed
	*
	* @param int $arFields["PRODUCT_ID"]
	* @param int $arFields["QUANTITY"]
	* @param string $arFields["RENEWAL"] (Y/N)
	* @param int $arFields["USER_ID"]
	* @param string $arFields["SITE_ID"]
	* @return array
	*/
	public static function OrderProduct($arFields);

	/**
	* Method is called when the order with the product is canceled
	*
	* @param int $arFields["PRODUCT_ID"]
	* @param int $arFields["QUANTITY"]
	* @param bool $arFields["CANCEL"]
	*/
	public static function CancelProduct($arFields);

	/**
	* Method is called when the delivery is allowed for the order with this product
	*
	* @param int $arFields["PRODUCT_ID"]
	* @param int $arFields["USER_ID"]
	* @param bool $arFields["PAID"]
	* @param int $arFields["ORDER_ID"]
	* @param array Product parameters
	*/
	public static function DeliverProduct($arFields);

	/**
	* Method is called when the product is viewed
	*
	* @param int $arFields["PRODUCT_ID"]
	* @param int $arFields["USER_ID"]
	* @param string $arFields["SITE_ID"]
	* @return array Product parameters
	*/
	public static function ViewProduct($arFields);

	/**
	* Method is when recurring order is placed with this product
	*
	* @param int $arFields["PRODUCT_ID"]
	* @param int $arFields["USER_ID"]
	* @return array Product parameters or false
	*/	
	public static function RecurringOrderProduct($arFields);
	
	/**
	* Method is called to know if product provider supports stores
	*
	* @return int number of stores available or false if stores are not used
	*/
	public static function GetStoresCount($arFields = array());	

	/**
	* Method is called to get information from the product provider
	* about available stores for the specified product
	*
	* @param int $arFields["PRODUCT_ID"] - product ID
	* @return array of stores or false if stores are not used
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