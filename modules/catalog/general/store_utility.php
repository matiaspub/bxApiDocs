<?php
IncludeModuleLangFile(__DIR__.'\\store_docs.php');

class CCatalogStoreControlUtil
{
	/** By store ID, returns its title and\or address.
	 * @param $storeId
	 * @return string
	 */
	public static function getStoreName($storeId)
	{
		static $dbStore = '';
		static $arStores = array();
		if($storeId <= 0)
			return '';

		$storeName = '';

		if($dbStore == '')
		{
			$dbStore = CCatalogStore::GetList(array(), array("ACTIVE" => "Y"));
		}
		if(empty($arStores))
			while($arStore = $dbStore->Fetch())
			{
				$arStores[] = $arStore;
			}

		foreach($arStores as $arStore)
			if($arStore["ID"] == $storeId)
			{
				$storeName = $arStore["ADDRESS"];
				$storeName = ($arStore["TITLE"] !== '') ? $arStore["TITLE"]." (".$storeName.") " : $storeName;
			}

		return $storeName;
	}

	/** Returns an array, containing information about the product block on its ID.
	 * @param $elementId
	 * @return array|string
	 */
	public static function getProductInfo($elementId)
	{
		$elementId = intval($elementId);
		$result = "";
		if($elementId <= 0)
			return $result;

		$dbProduct = CIBlockElement::GetList(array(), array("ID" => $elementId), false, false, array('ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'DETAIL_PICTURE', 'PREVIEW_PICTURE', 'IBLOCK_TYPE_ID', 'NAME'));
		while($arProduct = $dbProduct->GetNext())
		{
			$imgCode = "";

			if($arProduct["IBLOCK_ID"] > 0)
				$arProduct["EDIT_PAGE_URL"] = CIBlock::GetAdminElementEditLink($arProduct["IBLOCK_ID"], $elementId, array("find_section_section" => $arProduct["IBLOCK_SECTION_ID"]));

			if($arProduct["DETAIL_PICTURE"] > 0)
				$imgCode = $arProduct["DETAIL_PICTURE"];
			elseif($arProduct["PREVIEW_PICTURE"] > 0)
				$imgCode = $arProduct["PREVIEW_PICTURE"];

			$arProduct["NAME"] = ($arProduct["NAME"]);
			$arProduct["DETAIL_PAGE_URL"] = htmlspecialcharsex($arProduct["DETAIL_PAGE_URL"]);
			$arProduct["CURRENCY"] = htmlspecialcharsex($arProduct["CURRENCY"]);

			if($imgCode > 0)
			{
				$arFile = CFile::GetFileArray($imgCode);
				$arImgProduct = CFile::ResizeImageGet($arFile, array('width' => 80, 'height' => 80), BX_RESIZE_IMAGE_PROPORTIONAL, false, false);
				$arProduct["IMG_URL"] = $arImgProduct['src'];
			}

			return $arProduct;
		}
		return $result;
	}

	/** Checks whether the same method in the class, describing the transmitted document type. Calls this method and returns a set of fields for this type of document.
	 * @param $docType
	 * @return bool
	 */
	public static function getFields($docType)
	{
		if(strlen($docType) > 0 && isset(CCatalogDocs::$types[$docType]))
		{
			$documentClass = CCatalogDocs::$types[$docType];
			if(method_exists($documentClass, "getFields"))
			{
				return $documentClass::getFields();
			}
		}
		return false;
	}


	/** Generate a list of products on which did not match the total number and amount of all warehouses.
	 * @param $arProduct
	 * @param int $numberDisplayedElements
	 * @return string
	 */
	public static function showErrorProduct($arProduct, $numberDisplayedElements = 10)
	{
		$strError = '';
		$numberDisplayedElements = intval($numberDisplayedElements);
		if($numberDisplayedElements < 1)
			$numberDisplayedElements = 1;
		if(is_array($arProduct))
		{
			foreach($arProduct as $key => $product)
			{
				$strError .= "\n- ".$product;
				if($key >= ($numberDisplayedElements - 1))
				{
					$strError .= "\n...".GetMessage("CAT_DOC_AND_MORE", array("#COUNT#" => (count($arProduct) - $numberDisplayedElements)));
					break;
				}
			}
		}
		return $strError;
	}

	public static function getQuantityInformation($productId)
	{
		global $DB;

		return $DB->Query("SELECT SUM(SP.AMOUNT) as SUM, CP.QUANTITY_RESERVED as RESERVED FROM b_catalog_store_product SP INNER JOIN b_catalog_product CP ON SP.PRODUCT_ID = CP.ID INNER JOIN b_catalog_store CS ON SP.STORE_ID = CS.ID WHERE SP.PRODUCT_ID = ".$productId."  AND CS.ACTIVE = 'Y' GROUP BY QUANTITY_RESERVED ", true);
	}
}