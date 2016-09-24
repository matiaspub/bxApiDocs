<?
IncludeModuleLangFile(__FILE__);
class CSaleOrderHelper
{
	/*
	* check if barcode is valid (exists on the given store)
	*/
	public static function isBarCodeValid($arParams)
	{
		$bResult = false;
		$arBasket = array();

		if (intval($arParams["basketItemId"]) > 0)
		{
			$dbBasket = CSaleBasket::GetList(
				array("ID" => "DESC"),
				array("ID" => $arParams["basketItemId"]),
				false,
				false,
				array("ID", "PRODUCT_ID", "PRODUCT_PROVIDER_CLASS", "MODULE", "BARCODE_MULTI")
			);

			$arBasket = $dbBasket->GetNext();
		}
		else
		{
			$arBasket = array(
				"PRODUCT_PROVIDER_CLASS" => $arParams["productProvider"],
				"MODULE" => $arParams["moduleName"],
				"PRODUCT_ID" => $arParams["productId"],
				"BARCODE_MULTI" => $arParams["barcodeMult"]
			);
		}

		if (!empty($arBasket) && is_array($arBasket))
		{
			/** @var $productProvider IBXSaleProductProvider */
			if ($productProvider = CSaleBasket::GetProductProvider($arBasket))
			{
				$arCheckBarcodeFields = array(
					"BARCODE"    => $arParams["barcode"],
					"PRODUCT_ID" => $arBasket["PRODUCT_ID"],
					"ORDER_ID"   => $arParams["orderId"]
				);

				if ($arBasket["BARCODE_MULTI"] == "Y")
					$arCheckBarcodeFields["STORE_ID"] = $arParams["storeId"];

				$res = $productProvider::CheckProductBarcode($arCheckBarcodeFields);

				if($res)
					$bResult = true;
			}
		}

		return $bResult;
	}

	/*
	* check if total ordered quantity = quantity on stores
	*/
	public static function checkQuantity($arProducts)
	{
		$result = true;
		$sumQuantityOnStores = array();
		foreach ($arProducts as $id => $arProduct)
		{
			if (CSaleBasketHelper::isSetParent($arProduct))
				continue;

			if (!empty($arProduct["STORES"]) && is_array($arProduct["STORES"]))
			{
				$sumQuantityOnStores[$id] = 0;
				foreach ($arProduct["STORES"] as $arStore)
				{
					$sumQuantityOnStores[$id] += $arStore["QUANTITY"];
				}

				if ($sumQuantityOnStores[$id] != $arProduct["QUANTITY"])
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("NEWO_ERR_STORE_QUANTITY_NOT_EQUAL_TOTAL_QUANTITY", array("#PRODUCT_NAME#" => $arProduct["NAME"]))."<br>");
					$result = false;
					break;
				}
			}
			else
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("NEWO_ERR_STORE_WRONG_INFO", array("#PRODUCT_NAME#" => $arProduct["NAME"]))."<br>");
				$result = false;
				break;
			}
		}

		return $result;
	}

	/*
	* check if barcodes are valid for deduction
	*/
	public static function checkBarcodes($arProducts)
	{
		$result = true;

		foreach ($arProducts as $arProduct)
		{
			if ($arProduct["BARCODE_MULTI"] == "Y" && is_array($arProduct["STORES"]) && !empty($arProduct["STORES"]))
			{
				foreach ($arProduct["STORES"] as $arStore)
				{
					if (
							isset($arStore["QUANTITY"])
							&&
							intval($arStore["QUANTITY"]) > 0
							&&
							(
								!isset($arStore["BARCODE"])
								||
								count($arStore["BARCODE"]) != intval($arStore["QUANTITY"])
							)
						)
					{
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("NEWO_ERR_STORE_NO_BARCODES", array("#PRODUCT_NAME#" => $arProduct["NAME"], "#STORE_ID#" => $arStore["STORE_ID"]))."<br>");
						$result = false;
						break 2;
					}

					if (count($arStore["BARCODE"]) != $arStore["QUANTITY"])
					{
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("NEWO_ERR_STORE_QUANTITY_BARCODE", array("#PRODUCT_NAME#" => $arProduct["NAME"], "#STORE_ID#" => $arStore["STORE_ID"]))."<br>");
						$result = false;
						break 2;
					}

					foreach ($arStore["BARCODE"] as $bValue)
					{
						if (strlen($bValue) <= 0)
						{
							$GLOBALS["APPLICATION"]->ThrowException(GetMessage("NEWO_ERR_STORE_EMPTY_BARCODES", array("#PRODUCT_NAME#" => $arProduct["NAME"], "#STORE_ID#" => $arStore["STORE_ID"], "#BARCODE#" => $arStore["BARCODE"][$j]))."<br>");
							$result = false;
							break 3;
						}
					}

					if (!empty($arStore["BARCODE_FOUND"]))
					{
						foreach ($arStore["BARCODE_FOUND"] as $j => $bfValue)
						{
							if ($bfValue == "N")
							{
								$GLOBALS["APPLICATION"]->ThrowException(GetMessage("NEWO_ERR_STORE_BARCODES", array("#PRODUCT_NAME#" => $arProduct["NAME"], "#STORE_ID#" => $arStore["STORE_ID"], "#BARCODE#" => $arStore["BARCODE"][$j]))."<br>");
								$result = false;
								break 3;
							}
						}
					}
				}
			}
			else if ($arProduct["BARCODE_MULTI"] == "N" && is_array($arProduct["STORES"]) && !empty($arProduct["STORES"]))
			{
				//check if store info contains all necessary fields
				foreach ($arProduct["STORES"] as $arRecord)
				{
					if (!isset($arRecord["STORE_ID"]) || intVal($arRecord["STORE_ID"]) < 0 || (!isset($arRecord["AMOUNT"])) || intVal($arRecord["AMOUNT"]) < 0)
					{
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("NEWO_ERR_STORE_WRONG_INFO", array("#PRODUCT_NAME#" => $arProduct["NAME"]))."<br>");
						$result = false;
						break 2;
					}
				}
			}
		}

		return $result;
	}
}
