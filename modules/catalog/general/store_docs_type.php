<?
IncludeModuleLangFile(__DIR__.'\\store_docs.php');

abstract class CCatalogDocsTypes
{
	protected static $clearAutoCache = array();

	/** The method of conducting a document, distributes products to warehouses, according to the document type.
	 * @param $documentId
	 * @param $userId
	 * @param $currency
	 * @param $contractorId
	 * @return mixed
	 */
	abstract function conductDocument($documentId, $userId, $currency, $contractorId);

	/** Method cancels an instrument and perform the reverse action of conducting a document.
	 * @param $documentId
	 * @param $userId
	 * @return mixed
	 */
	abstract function cancellationDocument($documentId, $userId);

	/** The method checks the correctness of the data warehouse. If successful, enrolling \ debits to the storage required amount of product.
	 * @param $arFields
	 * @param $userId
	 * @return array|bool
	 */
	protected function distributeElementsToStores($arFields, $userId)
	{
		global $DB, $APPLICATION;

		$arErrorElement = array();
		if (isset($arFields["ELEMENTS"]) && is_array($arFields["ELEMENTS"]))
		{
			foreach ($arFields["ELEMENTS"] as $elementId => $arElements)
			{
				$arErrorElement = self::checkTotalAmount($elementId, $arElements['ELEMENT_NAME']);
				while (list($key, $arElement) = each($arElements["POSITIONS"]))
				{
					if (is_array($arElement))
					{
						if (isset($arElement["STORE_FROM"]))
						{
							$rsResult = CCatalogStoreProduct::GetList(array(), array("PRODUCT_ID" => $arElement["PRODUCT_ID"], "STORE_ID" => $arElement["STORE_FROM"]), false, false, array('ID', 'AMOUNT'));
							$arID = $rsResult->Fetch();
							if(($arID !== false) || ($arElements["NEGATIVE_AMOUNT_TRACE"] == 'Y'))
							{
								$amountForUpdate = doubleval($arID["AMOUNT"]) - $arElement["AMOUNT"];
								if(($amountForUpdate >= 0) || ($arElements["NEGATIVE_AMOUNT_TRACE"] == 'Y'))
								{
									if(!CCatalogStoreProduct::UpdateFromForm(array("PRODUCT_ID" => $arElement['PRODUCT_ID'], "STORE_ID" => $arElement['STORE_FROM'], "AMOUNT" => $amountForUpdate)))
										return false;
								}
								else
								{
									$storeFromName = CCatalogStoreControlUtil::getStoreName($arElement["STORE_FROM"]);
									$APPLICATION->ThrowException(GetMessage("CAT_DOC_INSUFFICIENTLY_AMOUNT", array("#STORE#" => '"'.$storeFromName.'"', "#PRODUCT#" => '"'.$arElements["ELEMENT_NAME"].'"')));
									return false;
								}
							}
							else
							{
								$storeFromName = CCatalogStoreControlUtil::getStoreName($arElement["STORE_FROM"]);
								$APPLICATION->ThrowException(GetMessage("CAT_DOC_INSUFFICIENTLY_AMOUNT", array("#STORE#" => $storeFromName, "#PRODUCT#" => $arElements["ELEMENT_NAME"])));
								return false;
							}
						}
						if (isset($arElement["STORE_TO"]))
						{
							if(!CCatalogStoreProduct::addToBalanceOfStore($arElement["STORE_TO"], $arElement["PRODUCT_ID"], $arElement["AMOUNT"]))
								return false;
						}

						if (isset($arElements["BARCODES"]) && is_array($arElements["BARCODES"]))
						{
							foreach($arElements["BARCODES"] as $key => $arBarCode)
							{
								$arBarCode['ELEMENT_NAME'] = $arElements['ELEMENT_NAME'];
								if(!self::applyBarCode($arBarCode, $userId))
									return false;
								else
									unset($arElements["BARCODES"][$key]);
							}
						}
						$dbAmount = $DB->Query("SELECT SUM(SP.AMOUNT) as SUM, CP.QUANTITY_RESERVED as RESERVED FROM b_catalog_store_product SP INNER JOIN b_catalog_product CP ON SP.PRODUCT_ID = CP.ID INNER JOIN b_catalog_store CS ON SP.STORE_ID = CS.ID WHERE SP.PRODUCT_ID = ".$arElement["PRODUCT_ID"]."  AND CS.ACTIVE = 'Y' GROUP BY QUANTITY_RESERVED ", true);
						if ($arAmount = $dbAmount->Fetch())
						{
							$arFields = array();
							if (isset($arElement["PURCHASING_INFO"]))
							{
								$arFields = $arElement["PURCHASING_INFO"];
							}
							$arFields["QUANTITY"] = doubleval($arAmount["SUM"] - $arAmount["RESERVED"]);
							if (!CCatalogProduct::Update($arElement["PRODUCT_ID"], $arFields))
							{
								$APPLICATION->ThrowException(GetMessage("CAT_DOC_PURCHASING_INFO_ERROR"));
								return false;
							}
							if (isset($arElements['OLD_QUANTITY']) && self::isNeedClearPublicCache(
									$arElements['OLD_QUANTITY'],
									$arFields['QUANTITY'],
									$arElements['QUANTITY_TRACE'],
									$arElements['CAN_BUY_ZERO']
								))
							{
								$productInfo = array(
									'CAN_BUY_ZERO' => $arElements['CAN_BUY_ZERO'],
									'NEGATIVE_AMOUNT_TRACE' => $arElements['NEGATIVE_AMOUNT_TRACE'],
									'QUANTITY_TRACE' => $arElements['QUANTITY_TRACE'],
									'OLD_QUANTITY' => $arElements['OLD_QUANTITY'],
									'QUANTITY' => $arFields['QUANTITY'],
									'DELTA' => $arFields['QUANTITY'] - $arElements['OLD_QUANTITY'],
									'IBLOCK_ID' => $arElements['IBLOCK_ID']
								);
								self::clearPublicCache($arElement["PRODUCT_ID"], $productInfo);
							}
						}
						else
						{
							return false;
						}
					}
				}
			}
		}
		if(!empty($arErrorElement))
			return $arErrorElement;
		return true;
	}

	/** The method works with barcodes. If necessary, check the uniqueness of multiple barcodes.
	 * @param $arFields
	 * @param $userId
	 * @return bool|int
	 */
	protected function applyBarCode($arFields, $userId)
	{
		global $APPLICATION;

		$barCode = $arFields["BARCODE"];
		$elementId = $arFields["PRODUCT_ID"];
		$storeToId = (isset($arFields["STORE_ID"])) ? $arFields["STORE_ID"] : 0;
		$storeFromId = (isset($arFields["STORE_FROM"])) ? $arFields["STORE_FROM"] : 0;
		$newStore = 0;
		$userId = (int)$userId;
		$result = false;
		$rsProps = CCatalogStoreBarCode::GetList(array(), array("BARCODE" => $barCode), false, false, array('ID', 'STORE_ID', 'PRODUCT_ID'));
		if($arBarCode = $rsProps->Fetch())
		{
			if($storeFromId > 0) // deduct or moving
			{
				if($storeToId > 0) // moving
				{
					if($arBarCode["STORE_ID"] == $storeFromId && $arBarCode["PRODUCT_ID"] == $elementId)
						$newStore = $storeToId;
					else
					{
						$storeName = CCatalogStoreControlUtil::getStoreName($storeFromId);
						$APPLICATION->ThrowException(GetMessage("CAT_DOC_WRONG_STORE_BARCODE", array("#STORE#" => '"'.$storeName.'"', "#PRODUCT#" => '"'.$arFields["ELEMENT_NAME"].'"', "#BARCODE#" => '"'.$barCode.'"')));
						return false;
					}
				}
			}
			else
			{
				$APPLICATION->ThrowException(GetMessage("CAT_DOC_BARCODE_ALREADY_EXIST", array("#PRODUCT#" => '"'.$arFields["ELEMENT_NAME"].'"', "#BARCODE#" => '"'.$barCode.'"')));
				return false;
			}
			if($newStore > 0)
				$result = CCatalogStoreBarCode::update($arBarCode["ID"], array("STORE_ID" => $storeToId, "MODIFIED_BY" => $userId));
			else
				$result = CCatalogStoreBarCode::delete($arBarCode["ID"]);
		}
		else
		{
			if($storeFromId > 0)
			{
				$storeName = CCatalogStoreControlUtil::getStoreName($storeFromId);
				$APPLICATION->ThrowException(GetMessage("CAT_DOC_WRONG_STORE_BARCODE", array("#STORE#" => '"'.$storeName.'"', "#PRODUCT#" => '"'.$arFields["ELEMENT_NAME"].'"', "#BARCODE#" => '"'.$barCode.'"')));
				return false;
			}
			elseif($storeToId > 0)
				$result = CCatalogStoreBarCode::Add(array("PRODUCT_ID" => $elementId, "STORE_ID" => $storeToId, "BARCODE" => $barCode, "MODIFIED_BY" => $userId, "CREATED_BY" => $userId));
		}

		return $result;
	}

	protected function checkTotalAmount($elementId, $name = '')
	{
		global $DB, $APPLICATION;
		static $arErrorElement = array();

		$elementId = (int)$elementId;
		$name = (string)$name;

		$dbAmount = $DB->Query("SELECT SUM(SP.AMOUNT) as SUM, CP.QUANTITY as QUANTITY, CP.QUANTITY_RESERVED as RESERVED FROM b_catalog_store_product SP INNER JOIN b_catalog_product CP ON SP.PRODUCT_ID = CP.ID INNER JOIN b_catalog_store CS ON SP.STORE_ID = CS.ID WHERE SP.PRODUCT_ID = ".$elementId." AND CS.ACTIVE = 'Y' GROUP BY QUANTITY, QUANTITY_RESERVED ", true);
		if($arAmount = $dbAmount->Fetch())
		{
			$sumAmountOfAllStore = $arAmount["SUM"];
			$quantityReserv = $arAmount["RESERVED"];
			$quantityTotal = $arAmount["QUANTITY"] + $quantityReserv;
			if($sumAmountOfAllStore != $quantityTotal)
			{
				if ($name == '')
				{
					$dbProduct = CIBlockElement::GetList(
						array(),
						array("ID" => $elementId),
						false,
						false,
						array('ID', 'NAME')
					);
					if ($arProduct = $dbProduct->Fetch())
					{
						$name = $arProduct['NAME'];
					}
				}
				if ($name == '')
					$name = $elementId;
				$arErrorElement[] = $name;
				$APPLICATION->ThrowException(GetMessage("CAT_DOC_WRONG_STORE_AMOUNT"));
			}
		}
		return $arErrorElement;
	}

	protected function checkAmountField($arDocElement, $name = '')
	{
		global $APPLICATION;
		$name = (string)$name;
		if(doubleval($arDocElement["AMOUNT"]) <= 0)
		{
			if ($name == '')
			{
				$dbProduct = CIBlockElement::GetList(
					array(),
					array("ID" => $arDocElement["ELEMENT_ID"]),
					false,
					false,
					array('ID', 'NAME')
				);
				if ($arProduct = $dbProduct->Fetch())
				{
					$name = $arProduct['NAME'];
				}
			}
			if ($name == '')
				$name = $arDocElement["ELEMENT_ID"];
			$APPLICATION->ThrowException(GetMessage("CAT_DOC_WRONG_AMOUNT", array("#PRODUCT#" => '"'.$name.'"')));
			return false;
		}
		return true;
	}

	protected static function isNeedClearPublicCache($currentQuantity, $newQuantity, $quantityTrace, $canBuyZero, $ratio = 1)
	{
		if (!defined('BX_COMP_MANAGED_CACHE'))
			return false;
		if ($canBuyZero == 'Y' || $quantityTrace == 'N')
			return false;
		if ($currentQuantity * $newQuantity > 0)
			return false;
		return true;
	}

	protected static function clearPublicCache($productID, $productInfo = array())
	{
		global $CACHE_MANAGER;

		$productID = (int)$productID;
		if ($productID <= 0)
			return;
		$iblockID = (int)(isset($productInfo['IBLOCK_ID']) ? $productInfo['IBLOCK_ID'] : CIBlockElement::GetIBlockByID($productID));
		if ($iblockID <= 0)
			return;
		if (defined('BX_COMP_MANAGED_CACHE') && !isset(self::$clearAutoCache[$iblockID]))
		{
			$CACHE_MANAGER->ClearByTag('iblock_id_'.$iblockID);
			self::$clearAutoCache[$iblockID] = true;
		}

		$productInfo['ID'] = $productID;
		$productInfo['ELEMENT_IBLOCK_ID'] = $iblockID;
		$productInfo['IBLOCK_ID'] = $iblockID;
		foreach (GetModuleEvents('catalog', 'OnProductQuantityTrace', true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($productID, $productInfo));
		}
	}
}

class CAllCatalogArrivalDocsType extends CCatalogDocsTypes
{
	/** The method returns an array of fields needed for this type of document.
	 * @return array
	 */
	static public function getFields()
	{
		return array(
			"ELEMENT_ID" => array("required" => 'Y'),
			"AMOUNT" => array("required" => 'Y'),
			"NET_PRICE" => array("required" => 'Y'),
			"STORE_TO" => array("required" => 'Y'),
			"BAR_CODE" => array("required" => 'Y'),
			"CONTRACTOR" => array("required" => 'Y'),
			"CURRENCY" => array("required" => 'Y'),
			"TOTAL" => array("required" => 'Y')
		);
	}

	/**
	 * @param $documentId
	 * @param $userId
	 * @param $currency
	 * @param $contractorId
	 * @return array|bool
	 */
	static public function conductDocument($documentId, $userId, $currency, $contractorId)
	{
		global $APPLICATION;

		$documentId = (int)$documentId;
		$userId = (int)$userId;
		$contractorId = (int)$contractorId;
		$arResult = array(
			'ELEMENTS' => array()
		);
		$arElement = array();
		$arDocElementId = array();
		$sumAmountBarcodes = array();
		$currency = ($currency !== null) ? $currency : '';
		if($contractorId <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("CAT_DOC_WRONG_CONTRACTOR"));
			return false;
		}
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $documentId));
		while($arDocElement = $dbDocElements->Fetch())
		{
			$elementId = (int)$arDocElement["ELEMENT_ID"];

			if(!self::checkAmountField($arDocElement))
			{
				return false;
			}
			if (!isset($arElement[$elementId]))
				$arElement[$elementId] = array();
			$arElement[$elementId][$arDocElement["ID"]] = $arDocElement;
			if (!isset($arDocElementId[$elementId]))
				$arDocElementId[$elementId] = array();
			$arDocElementId[$elementId][] = $arDocElement["ID"];
			if (!isset($sumAmountBarcodes[$elementId]))
				$sumAmountBarcodes[$elementId] = 0;
			$sumAmountBarcodes[$elementId] += $arDocElement["AMOUNT"];
			if (!isset($arResult["ELEMENTS"][$elementId]))
			{
				$arResult["ELEMENTS"][$elementId] = array(
					'POSITIONS' => array()
				);
			}
			$arResult["ELEMENTS"][$elementId]["POSITIONS"][] = array(
				"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
				"STORE_TO" => $arDocElement["STORE_TO"],
				"AMOUNT" => $arDocElement["AMOUNT"],
				"PURCHASING_INFO" => array("PURCHASING_PRICE" => $arDocElement["PURCHASING_PRICE"], "PURCHASING_CURRENCY" => $currency),
			);
		}
		if(empty($arElement))
			return false;

		$dbProductInfo = CCatalogProduct::GetList(
			array(),
			array('ID' => array_keys($arElement)),
			false,
			false,
			array('ID', 'NEGATIVE_AMOUNT_TRACE', 'BARCODE_MULTI', 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO', 'ELEMENT_NAME', 'ELEMENT_IBLOCK_ID')
		);

		while ($arProductInfo = $dbProductInfo->Fetch())
		{
			$id = $arProductInfo["ID"];

			$arResult["ELEMENTS"][$id]["NEGATIVE_AMOUNT_TRACE"] = $arProductInfo["NEGATIVE_AMOUNT_TRACE"];
			$arResult["ELEMENTS"][$id]["QUANTITY_TRACE"] = $arProductInfo["QUANTITY_TRACE"];
			$arResult["ELEMENTS"][$id]["CAN_BUY_ZERO"] = $arProductInfo["CAN_BUY_ZERO"];
			$arResult["ELEMENTS"][$id]["OLD_QUANTITY"] = $arProductInfo["QUANTITY"];
			$arResult["ELEMENTS"][$id]["IBLOCK_ID"] = $arProductInfo["ELEMENT_IBLOCK_ID"];
			$arResult["ELEMENTS"][$id]["ELEMENT_NAME"] = $arProductInfo["ELEMENT_NAME"];

			if($arProductInfo["BARCODE_MULTI"] == 'Y')
			{
				$arResult["ELEMENTS"][$id]["BARCODES"] = array();
				$dbDocBarcodes = CCatalogStoreDocsBarcode::getList(array(), array("DOC_ELEMENT_ID" => $arDocElementId[$id]));
				while($arDocBarcode = $dbDocBarcodes->Fetch())
				{
					$arResult["ELEMENTS"][$id]["BARCODES"][] = array(
						"PRODUCT_ID" => $id,
						"BARCODE" => $arDocBarcode["BARCODE"],
						"STORE_ID" => $arElement[$id][$arDocBarcode["DOC_ELEMENT_ID"]]["STORE_TO"],
					);
				}
				if($sumAmountBarcodes[$id] != count($arResult["ELEMENTS"][$id]["BARCODES"]))
				{
					$APPLICATION->ThrowException(GetMessage("CAT_DOC_WRONG_COUNT", array("#PRODUCT#" => '"'.$arProductInfo["ELEMENT_NAME"].'"')));
					return false;
				}
			}
		}
		return self::distributeElementsToStores($arResult, $userId);
	}

	/**
	 * @param $documentId
	 * @param $userId
	 * @return array|bool
	 */
	static public function cancellationDocument($documentId, $userId)
	{
		global $APPLICATION;

		$documentId = (int)$documentId;
		$userId = (int)$userId;
		$arResult = array(
			'ELEMENTS' => array()
		);
		$arElement = array();
		$arDocElementId = array();
		$sumAmountBarcodes = array();
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $documentId));
		while($arDocElement = $dbDocElements->Fetch())
		{
			$elementId = (int)$arDocElement["ELEMENT_ID"];

			if(!self::checkAmountField($arDocElement))
			{
				return false;
			}
			if (!isset($arElement[$elementId]))
				$arElement[$elementId] = array();
			$arElement[$elementId][$arDocElement["ID"]] = $arDocElement;
			if (!isset($arDocElementId[$elementId]))
				$arDocElementId[$elementId] = array();
			$arDocElementId[$elementId][] = $arDocElement["ID"];
			if (!isset($sumAmountBarcodes[$elementId]))
				$sumAmountBarcodes[$elementId] = 0;
			$sumAmountBarcodes[$elementId] += $arDocElement["AMOUNT"];
			if (!isset($arResult["ELEMENTS"][$elementId]))
			{
				$arResult["ELEMENTS"][$elementId] = array(
					'POSITIONS' => array()
				);
			}
			$arResult["ELEMENTS"][$elementId]["POSITIONS"][] = array(
				"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
				"STORE_FROM" => $arDocElement["STORE_TO"],
				"AMOUNT" => $arDocElement["AMOUNT"],
			);
		}

		if(empty($arElement))
			return false;

		$dbProductInfo = CCatalogProduct::GetList(
			array(),
			array('ID' => array_keys($arElement)),
			false,
			false,
			array('ID', 'NEGATIVE_AMOUNT_TRACE', 'BARCODE_MULTI', 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO', 'ELEMENT_NAME', 'ELEMENT_IBLOCK_ID')
		);
		while($arProductInfo = $dbProductInfo->Fetch())
		{
			$id = $arProductInfo["ID"];
			$arResult["ELEMENTS"][$id]["NEGATIVE_AMOUNT_TRACE"] = $arProductInfo["NEGATIVE_AMOUNT_TRACE"];
			$arResult["ELEMENTS"][$id]["QUANTITY_TRACE"] = $arProductInfo["QUANTITY_TRACE"];
			$arResult["ELEMENTS"][$id]["CAN_BUY_ZERO"] = $arProductInfo["CAN_BUY_ZERO"];
			$arResult["ELEMENTS"][$id]["OLD_QUANTITY"] = $arProductInfo["QUANTITY"];
			$arResult["ELEMENTS"][$id]["IBLOCK_ID"] = $arProductInfo["ELEMENT_IBLOCK_ID"];
			$arResult["ELEMENTS"][$id]["ELEMENT_NAME"] = $arProductInfo["ELEMENT_NAME"];

			if ($arProductInfo["BARCODE_MULTI"] == 'Y')
			{
				$arResult["ELEMENTS"][$id]["BARCODES"] = array();
				$dbDocBarcodes = CCatalogStoreDocsBarcode::getList(array(), array("DOC_ELEMENT_ID" => $arDocElementId[$id]));
				while($arDocBarcode = $dbDocBarcodes->Fetch())
				{
					$arResult["ELEMENTS"][$id]["BARCODES"][] = array(
						"PRODUCT_ID" => $id,
						"BARCODE" => $arDocBarcode["BARCODE"],
						"STORE_FROM" => $arElement[$id][$arDocBarcode["DOC_ELEMENT_ID"]]["STORE_TO"],
					);
				}
				if($sumAmountBarcodes[$id] != count($arResult["ELEMENTS"][$id]["BARCODES"]))
				{
					$APPLICATION->ThrowException(GetMessage("CAT_DOC_WRONG_COUNT", array("#PRODUCT#" => '"'.$arProductInfo["ELEMENT_NAME"].'"')));
					return false;
				}
			}
		}
		return self::distributeElementsToStores($arResult, $userId);
	}

}

class CAllCatalogMovingDocsType extends CCatalogDocsTypes
{
	/** The method returns an array of fields needed for this type of document.
	 * @return array
	 */
	static public function getFields()
	{
		return array(
			"ELEMENT_ID" => array("required" => 'Y'),
			"AMOUNT" => array("required" => 'Y'),
			"STORE_TO" => array("required" => 'Y'),
			"BAR_CODE" => array("required" => 'Y'),
			"STORE_FROM" => array("required" => 'Y'),
		);
	}

	/**
	 * @param $documentId
	 * @param $userId
	 * @param $currency
	 * @param $contractorId
	 * @return array|bool
	 */
	static public function conductDocument($documentId, $userId, $currency, $contractorId)
	{
		global $APPLICATION;

		$documentId = (int)$documentId;
		$userId = (int)$userId;
		$arResult = array(
			'ELEMENTS' => array()
		);
		$arElement = array();
		$arDocElementId = array();
		$sumAmountBarcodes = array();
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $documentId));
		while($arDocElement = $dbDocElements->Fetch())
		{
			$elementId = (int)$arDocElement["ELEMENT_ID"];

			if(!self::checkAmountField($arDocElement))
			{
				return false;
			}
			if (!isset($arElement[$elementId]))
				$arElement[$elementId] = array();
			$arElement[$elementId][$arDocElement["ID"]] = $arDocElement;
			if (!isset($arDocElementId[$elementId]))
				$arDocElementId[$elementId] = array();
			$arDocElementId[$elementId][] = $arDocElement["ID"];
			if (!isset($sumAmountBarcodes[$elementId]))
				$sumAmountBarcodes[$elementId] = 0;
			$sumAmountBarcodes[$elementId] += $arDocElement["AMOUNT"];
			if (!isset($arResult["ELEMENTS"][$elementId]))
			{
				$arResult["ELEMENTS"][$elementId] = array(
					'POSITIONS' => array()
				);
			}
			$arResult["ELEMENTS"][$elementId]["POSITIONS"][] = array(
				"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
				"STORE_TO" => $arDocElement["STORE_TO"],
				"AMOUNT" => $arDocElement["AMOUNT"],
				"STORE_FROM" => $arDocElement["STORE_FROM"],
			);
		}

		if (empty($arElement))
			return false;

		$dbProductInfo = CCatalogProduct::GetList(
			array(),
			array('ID' => array_keys($arElement)),
			false,
			false,
			array('ID', 'NEGATIVE_AMOUNT_TRACE', 'BARCODE_MULTI', 'ELEMENT_NAME')
		);

		while($arProductInfo = $dbProductInfo->Fetch())
		{
			$id = $arProductInfo["ID"];

			$arResult["ELEMENTS"][$id]["NEGATIVE_AMOUNT_TRACE"] = $arProductInfo["NEGATIVE_AMOUNT_TRACE"];
			$arResult["ELEMENTS"][$id]["ELEMENT_NAME"] = $arProductInfo["ELEMENT_NAME"];

			if($arProductInfo["BARCODE_MULTI"] == 'Y')
			{
				$arResult["ELEMENTS"][$id]["BARCODES"] = array();
				$dbDocBarcodes = CCatalogStoreDocsBarcode::getList(array(), array("DOC_ELEMENT_ID" => $arDocElementId[$id]));
				while($arDocBarcode = $dbDocBarcodes->Fetch())
				{
					$arResult["ELEMENTS"][$id]["BARCODES"][] = array(
						"PRODUCT_ID" => $id,
						"BARCODE" => $arDocBarcode["BARCODE"],
						"STORE_ID" => $arElement[$id][$arDocBarcode["DOC_ELEMENT_ID"]]["STORE_TO"],
						"STORE_FROM" => $arElement[$id][$arDocBarcode["DOC_ELEMENT_ID"]]["STORE_FROM"],
					);
				}

				if($sumAmountBarcodes[$id] != count($arResult["ELEMENTS"][$id]["BARCODES"]))
				{
					$APPLICATION->ThrowException(GetMessage("CAT_DOC_WRONG_COUNT", array("#PRODUCT#" => '"'.$arProductInfo["ELEMENT_NAME"].'"')));
					return false;
				}
			}
		}
		return self::distributeElementsToStores($arResult, $userId);
	}

	/**
	 * @param $documentId
	 * @param $userId
	 * @return array|bool
	 */
	static public function cancellationDocument($documentId, $userId)
	{
		global $APPLICATION;

		$documentId = (int)$documentId;
		$userId = (int)$userId;
		$arResult = array(
			'ELEMENTS' => array()
		);
		$arElement = array();
		$arDocElementId = array();
		$sumAmountBarcodes = array();
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $documentId));
		while($arDocElement = $dbDocElements->Fetch())
		{
			$elementId = (int)$arDocElement["ELEMENT_ID"];

			if(!self::checkAmountField($arDocElement))
			{
				return false;
			}
			if (!isset($arElement[$elementId]))
				$arElement[$elementId] = array();
			$arElement[$elementId][$arDocElement["ID"]] = $arDocElement;
			if (!isset($arDocElementId[$elementId]))
				$arDocElementId[$elementId] = array();
			$arDocElementId[$elementId][] = $arDocElement["ID"];
			if (!isset($sumAmountBarcodes[$elementId]))
				$sumAmountBarcodes[$elementId] = 0;
			$sumAmountBarcodes[$elementId] += $arDocElement["AMOUNT"];
			if (!isset($arResult["ELEMENTS"][$elementId]))
			{
				$arResult["ELEMENTS"][$elementId] = array(
					'POSITIONS' => array()
				);
			}
			$arResult["ELEMENTS"][$elementId]["POSITIONS"][] = array(
				"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
				"STORE_FROM" => $arDocElement["STORE_TO"],
				"AMOUNT" => $arDocElement["AMOUNT"],
				"STORE_TO" => $arDocElement["STORE_FROM"],
			);
		}

		if(empty($arElement))
			return false;

		$dbProductInfo = CCatalogProduct::GetList(
			array(),
			array('ID' => array_keys($arElement)),
			false,
			false,
			array('ID', 'NEGATIVE_AMOUNT_TRACE', 'BARCODE_MULTI', 'ELEMENT_NAME')
		);
		while($arProductInfo = $dbProductInfo->Fetch())
		{
			$id = $arProductInfo["ID"];
			$arResult["ELEMENTS"][$id]["NEGATIVE_AMOUNT_TRACE"] = $arProductInfo["NEGATIVE_AMOUNT_TRACE"];
			$arResult["ELEMENTS"][$id]["ELEMENT_NAME"] = $arProductInfo["ELEMENT_NAME"];

			if($arProductInfo["BARCODE_MULTI"] == 'Y')
			{
				$arResult["ELEMENTS"][$id]["BARCODES"] = array();
				$dbDocBarcodes = CCatalogStoreDocsBarcode::getList(array(), array("DOC_ELEMENT_ID" => $arDocElementId[$id]));
				while($arDocBarcode = $dbDocBarcodes->Fetch())
				{
					$arResult["ELEMENTS"][$id]["BARCODES"][] = array(
						"PRODUCT_ID" => $id,
						"BARCODE" => $arDocBarcode["BARCODE"],
						"STORE_FROM" => $arElement[$id][$arDocBarcode["DOC_ELEMENT_ID"]]["STORE_TO"],
						"STORE_ID" => $arElement[$id][$arDocBarcode["DOC_ELEMENT_ID"]]["STORE_FROM"],
					);
				}
				if($sumAmountBarcodes[$id] != count($arResult["ELEMENTS"][$id]["BARCODES"]))
				{

					$APPLICATION->ThrowException(GetMessage("CAT_DOC_WRONG_COUNT", array("#PRODUCT#" => '"'.$arProductInfo["ELEMENT_NAME"].'"')));
					return false;
				}
			}
		}
		return self::distributeElementsToStores($arResult, $userId);
	}

}

class CAllCatalogReturnsDocsType extends CCatalogDocsTypes
{
	/** The method returns an array of fields needed for this type of document.
	 * @return array
	 */
	static public function getFields()
	{
		return array(
			"ELEMENT_ID" => array("required" => 'Y'),
			"AMOUNT" => array("required" => 'Y'),
			"STORE_TO" => array("required" => 'Y'),
			"BAR_CODE" => array("required" => 'y'),
		);
	}

	/**
	 * @param $documentId
	 * @param $userId
	 * @param $currency
	 * @param $contractorId
	 * @return array|bool
	 */
	static public function conductDocument($documentId, $userId, $currency, $contractorId)
	{
		global $APPLICATION;

		$documentId = (int)$documentId;
		$userId = (int)$userId;
		$arResult = array(
			'ELEMENTS' => array()
		);
		$arElement = array();
		$arDocElementId = array();
		$sumAmountBarcodes = array();
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $documentId));
		while($arDocElement = $dbDocElements->Fetch())
		{
			$elementId = (int)$arDocElement["ELEMENT_ID"];

			if(!self::checkAmountField($arDocElement))
			{
				return false;
			}
			if (!isset($arElement[$elementId]))
				$arElement[$elementId] = array();
			$arElement[$elementId][$arDocElement["ID"]] = $arDocElement;
			if (!isset($arDocElementId[$elementId]))
				$arDocElementId[$elementId] = array();
			$arDocElementId[$elementId][] = $arDocElement["ID"];
			if (!isset($sumAmountBarcodes[$elementId]))
				$sumAmountBarcodes[$elementId] = 0;
			$sumAmountBarcodes[$elementId] += $arDocElement["AMOUNT"];
			if (!isset($arResult["ELEMENTS"][$elementId]))
			{
				$arResult["ELEMENTS"][$elementId] = array(
					'POSITIONS' => array()
				);
			}
			$arResult["ELEMENTS"][$elementId]["POSITIONS"][] = array(
				"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
				"AMOUNT" => $arDocElement["AMOUNT"],
				"STORE_TO" => $arDocElement["STORE_TO"],
			);
		}

		if(empty($arElement))
			return false;

		$dbProductInfo = CCatalogProduct::GetList(
			array(),
			array('ID' => array_keys($arElement)),
			false,
			false,
			array('ID', 'NEGATIVE_AMOUNT_TRACE', 'BARCODE_MULTI', 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO', 'ELEMENT_NAME', 'ELEMENT_IBLOCK_ID')
		);

		while($arProductInfo = $dbProductInfo->Fetch())
		{
			$id = $arProductInfo["ID"];

			$arResult["ELEMENTS"][$id]["NEGATIVE_AMOUNT_TRACE"] = $arProductInfo["NEGATIVE_AMOUNT_TRACE"];
			$arResult["ELEMENTS"][$id]["QUANTITY_TRACE"] = $arProductInfo["QUANTITY_TRACE"];
			$arResult["ELEMENTS"][$id]["CAN_BUY_ZERO"] = $arProductInfo["CAN_BUY_ZERO"];
			$arResult["ELEMENTS"][$id]["OLD_QUANTITY"] = $arProductInfo["QUANTITY"];
			$arResult["ELEMENTS"][$id]["IBLOCK_ID"] = $arProductInfo["ELEMENT_IBLOCK_ID"];
			$arResult["ELEMENTS"][$id]["ELEMENT_NAME"] = $arProductInfo["ELEMENT_NAME"];

			if($arProductInfo["BARCODE_MULTI"] == 'Y')
			{
				$arResult["ELEMENTS"][$id]["BARCODES"] = array();
				$dbDocBarcodes = CCatalogStoreDocsBarcode::getList(array(), array("DOC_ELEMENT_ID" => $arDocElementId[$id]));
				while($arDocBarcode = $dbDocBarcodes->Fetch())
				{
					$arResult["ELEMENTS"][$id]["BARCODES"][] = array(
						"PRODUCT_ID" => $id,
						"BARCODE" => $arDocBarcode["BARCODE"],
						"STORE_ID" => $arElement[$id][$arDocBarcode["DOC_ELEMENT_ID"]]["STORE_TO"],
					);
				}

				if($sumAmountBarcodes[$id] != count($arResult["ELEMENTS"][$id]["BARCODES"]))
				{
					$APPLICATION->ThrowException(GetMessage("CAT_DOC_WRONG_COUNT", array("#PRODUCT#" => '"'.$arProductInfo["ELEMENT_NAME"].'"')));
					return false;
				}
			}
		}
		return self::distributeElementsToStores($arResult, $userId);
	}

	/**
	 * @param $documentId
	 * @param $userId
	 * @return array|bool
	 */
	static public function cancellationDocument($documentId, $userId)
	{
		global $APPLICATION;

		$documentId = (int)$documentId;
		$userId = (int)$userId;
		$arResult = array(
			'ELEMENTS' => array()
		);
		$arElement = array();
		$arDocElementId = array();
		$sumAmountBarcodes = array();
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $documentId));
		while($arDocElement = $dbDocElements->Fetch())
		{
			$elementId = (int)$arDocElement["ELEMENT_ID"];

			if(!self::checkAmountField($arDocElement))
			{
				return false;
			}
			if (!isset($arElement[$elementId]))
				$arElement[$elementId] = array();
			$arElement[$elementId][$arDocElement["ID"]] = $arDocElement;
			if (!isset($arDocElementId[$elementId]))
				$arDocElementId[$elementId] = array();
			$arDocElementId[$elementId][] = $arDocElement["ID"];
			if (!isset($sumAmountBarcodes[$elementId]))
				$sumAmountBarcodes[$elementId] = 0;
			$sumAmountBarcodes[$elementId] += $arDocElement["AMOUNT"];
			if (!isset($arResult["ELEMENTS"][$elementId]))
			{
				$arResult["ELEMENTS"][$elementId] = array(
					'POSITIONS' => array()
				);
			}
			$arResult["ELEMENTS"][$elementId]["POSITIONS"][] = array(
				"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
				"AMOUNT" => $arDocElement["AMOUNT"],
				"STORE_FROM" => $arDocElement["STORE_TO"],
			);
		}

		if(empty($arElement))
			return false;

		$dbProductInfo = CCatalogProduct::GetList(
			array(),
			array('ID' => array_keys($arElement)),
			false,
			false,
			array('ID', 'NEGATIVE_AMOUNT_TRACE', 'BARCODE_MULTI', 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO', 'ELEMENT_NAME', 'ELEMENT_IBLOCK_ID')
		);
		while($arProductInfo = $dbProductInfo->Fetch())
		{
			$id = $arProductInfo["ID"];
			$arResult["ELEMENTS"][$id]["NEGATIVE_AMOUNT_TRACE"] = $arProductInfo["NEGATIVE_AMOUNT_TRACE"];
			$arResult["ELEMENTS"][$id]["QUANTITY_TRACE"] = $arProductInfo["QUANTITY_TRACE"];
			$arResult["ELEMENTS"][$id]["CAN_BUY_ZERO"] = $arProductInfo["CAN_BUY_ZERO"];
			$arResult["ELEMENTS"][$id]["OLD_QUANTITY"] = $arProductInfo["QUANTITY"];
			$arResult["ELEMENTS"][$id]["IBLOCK_ID"] = $arProductInfo["ELEMENT_IBLOCK_ID"];
			$arResult["ELEMENTS"][$id]["ELEMENT_NAME"] = $arProductInfo["ELEMENT_NAME"];

			if($arProductInfo["BARCODE_MULTI"] == 'Y')
			{
				$arResult["ELEMENTS"][$id]["BARCODES"] = array();
				$dbDocBarcodes = CCatalogStoreDocsBarcode::getList(array(), array("DOC_ELEMENT_ID" => $arDocElementId[$id]));
				while($arDocBarcode = $dbDocBarcodes->Fetch())
				{
					$arResult["ELEMENTS"][$id]["BARCODES"][] = array(
						"PRODUCT_ID" => $id,
						"BARCODE" => $arDocBarcode["BARCODE"],
						"STORE_FROM" => $arElement[$id][$arDocBarcode["DOC_ELEMENT_ID"]]["STORE_TO"],
					);
				}
				if($sumAmountBarcodes[$id] != count($arResult["ELEMENTS"][$id]["BARCODES"]))
				{
					$APPLICATION->ThrowException(GetMessage("CAT_DOC_WRONG_COUNT", array("#PRODUCT#" => '"'.$arProductInfo["ELEMENT_NAME"].'"')));
					return false;
				}
			}
		}
		return self::distributeElementsToStores($arResult, $userId);
	}

}

class CAllCatalogDeductDocsType extends CCatalogDocsTypes
{
	/** The method returns an array of fields needed for this type of document.
	 * @return array
	 */
	static public function getFields()
	{
		return array(
			"ELEMENT_ID" => array("required" => 'Y'),
			"AMOUNT" => array("required" => 'Y'),
			"BAR_CODE" => array("required" => 'Y'),
			"STORE_FROM" => array("required" => 'Y'),
		);
	}

	/**
	 * @param $documentId
	 * @param $userId
	 * @param $currency
	 * @param $contractorId
	 * @return array|bool
	 */
	static public function conductDocument($documentId, $userId, $currency, $contractorId)
	{
		global $APPLICATION;

		$documentId = (int)$documentId;
		$userId = (int)$userId;
		$arResult = array(
			'ELEMENTS' => array()
		);
		$arElement = array();
		$arDocElementId = array();
		$sumAmountBarcodes = array();
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $documentId));
		while($arDocElement = $dbDocElements->Fetch())
		{
			$elementId = (int)$arDocElement["ELEMENT_ID"];

			if(!self::checkAmountField($arDocElement))
			{
				return false;
			}
			if (!isset($arElement[$elementId]))
				$arElement[$elementId] = array();
			$arElement[$elementId][$arDocElement["ID"]] = $arDocElement;
			if (!isset($arDocElementId[$elementId]))
				$arDocElementId[$elementId] = array();
			$arDocElementId[$elementId][] = $arDocElement["ID"];
			if (!isset($sumAmountBarcodes[$elementId]))
				$sumAmountBarcodes[$elementId] = 0;
			$sumAmountBarcodes[$elementId] += $arDocElement["AMOUNT"];
			if (!isset($arResult["ELEMENTS"][$elementId]))
			{
				$arResult["ELEMENTS"][$elementId] = array(
					'POSITIONS' => array()
				);
			}
			$arResult["ELEMENTS"][$elementId]["POSITIONS"][] = array(
				"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
				"AMOUNT" => $arDocElement["AMOUNT"],
				"STORE_FROM" => $arDocElement["STORE_FROM"],
			);
		}

		if(empty($arElement))
			return false;

		$dbProductInfo = CCatalogProduct::GetList(
			array(),
			array('ID' => array_keys($arElement)),
			false,
			false,
			array('ID', 'NEGATIVE_AMOUNT_TRACE', 'BARCODE_MULTI', 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO', 'ELEMENT_NAME', 'ELEMENT_IBLOCK_ID')
		);

		while($arProductInfo = $dbProductInfo->Fetch())
		{
			$id = $arProductInfo["ID"];

			$arResult["ELEMENTS"][$id]["NEGATIVE_AMOUNT_TRACE"] = $arProductInfo["NEGATIVE_AMOUNT_TRACE"];
			$arResult["ELEMENTS"][$id]["QUANTITY_TRACE"] = $arProductInfo["QUANTITY_TRACE"];
			$arResult["ELEMENTS"][$id]["CAN_BUY_ZERO"] = $arProductInfo["CAN_BUY_ZERO"];
			$arResult["ELEMENTS"][$id]["OLD_QUANTITY"] = $arProductInfo["QUANTITY"];
			$arResult["ELEMENTS"][$id]["IBLOCK_ID"] = $arProductInfo["ELEMENT_IBLOCK_ID"];
			$arResult["ELEMENTS"][$id]["ELEMENT_NAME"] = $arProductInfo["ELEMENT_NAME"];

			if($arProductInfo["BARCODE_MULTI"] == 'Y')
			{
				$arResult["ELEMENTS"][$id]["BARCODES"] = array();
				$dbDocBarcodes = CCatalogStoreDocsBarcode::getList(array(), array("DOC_ELEMENT_ID" => $arDocElementId[$id]));
				while($arDocBarcode = $dbDocBarcodes->Fetch())
				{
					$arResult["ELEMENTS"][$id]["BARCODES"][] = array(
						"PRODUCT_ID" => $id,
						"BARCODE" => $arDocBarcode["BARCODE"],
						"STORE_FROM" => $arElement[$id][$arDocBarcode["DOC_ELEMENT_ID"]]["STORE_FROM"],
					);
				}

				if($sumAmountBarcodes[$id] != count($arResult["ELEMENTS"][$id]["BARCODES"]))
				{
					$APPLICATION->ThrowException(GetMessage("CAT_DOC_WRONG_COUNT", array("#PRODUCT#" => '"'.$arProductInfo["ELEMENT_NAME"].'"')));
					return false;
				}
			}
		}
		return self::distributeElementsToStores($arResult, $userId);
	}

	/**
	 * @param $documentId
	 * @param $userId
	 * @return array|bool
	 */
	static public function cancellationDocument($documentId, $userId)
	{
		global $APPLICATION;

		$documentId = (int)$documentId;
		$userId = (int)$userId;
		$arResult = array(
			'ELEMENTS' => array()
		);
		$arElement = array();
		$arDocElementId = array();
		$sumAmountBarcodes = array();
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $documentId));
		while($arDocElement = $dbDocElements->Fetch())
		{
			$elementId = (int)$arDocElement["ELEMENT_ID"];

			if(!self::checkAmountField($arDocElement))
			{
				return false;
			}
			if (!isset($arElement[$elementId]))
				$arElement[$elementId] = array();
			$arElement[$elementId][$arDocElement["ID"]] = $arDocElement;
			if (!isset($arDocElementId[$elementId]))
				$arDocElementId[$elementId] = array();
			$arDocElementId[$elementId][] = $arDocElement["ID"];
			if (!isset($sumAmountBarcodes[$elementId]))
				$sumAmountBarcodes[$elementId] = 0;
			$sumAmountBarcodes[$elementId] += $arDocElement["AMOUNT"];
			if (!isset($arResult["ELEMENTS"][$elementId]))
			{
				$arResult["ELEMENTS"][$elementId] = array(
					'POSITIONS' => array()
				);
			}
			$arResult["ELEMENTS"][$elementId]["POSITIONS"][] = array(
				"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
				"AMOUNT" => $arDocElement["AMOUNT"],
				"STORE_TO" => $arDocElement["STORE_FROM"],
			);
		}

		if(empty($arElement))
			return false;

		$dbProductInfo = CCatalogProduct::GetList(
			array(),
			array('ID' => array_keys($arElement)),
			false,
			false,
			array('ID', 'NEGATIVE_AMOUNT_TRACE', 'BARCODE_MULTI', 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO', 'ELEMENT_NAME', 'ELEMENT_IBLOCK_ID')
		);
		while($arProductInfo = $dbProductInfo->Fetch())
		{
			$id = $arProductInfo["ID"];
			$arResult["ELEMENTS"][$id]["NEGATIVE_AMOUNT_TRACE"] = $arProductInfo["NEGATIVE_AMOUNT_TRACE"];
			$arResult["ELEMENTS"][$id]["QUANTITY_TRACE"] = $arProductInfo["QUANTITY_TRACE"];
			$arResult["ELEMENTS"][$id]["CAN_BUY_ZERO"] = $arProductInfo["CAN_BUY_ZERO"];
			$arResult["ELEMENTS"][$id]["OLD_QUANTITY"] = $arProductInfo["QUANTITY"];
			$arResult["ELEMENTS"][$id]["IBLOCK_ID"] = $arProductInfo["ELEMENT_IBLOCK_ID"];
			$arResult["ELEMENTS"][$id]["ELEMENT_NAME"] = $arProductInfo["ELEMENT_NAME"];

			if($arProductInfo["BARCODE_MULTI"] == 'Y')
			{
				$arResult["ELEMENTS"][$id]["BARCODES"] = array();
				$dbDocBarcodes = CCatalogStoreDocsBarcode::getList(array(), array("DOC_ELEMENT_ID" => $arDocElementId[$id]));
				while($arDocBarcode = $dbDocBarcodes->Fetch())
				{
					$arResult["ELEMENTS"][$id]["BARCODES"][] = array(
						"PRODUCT_ID" => $id,
						"BARCODE" => $arDocBarcode["BARCODE"],
						"STORE_ID" => $arElement[$id][$arDocBarcode["DOC_ELEMENT_ID"]]["STORE_FROM"],
					);
				}
				if($sumAmountBarcodes[$id] != count($arResult["ELEMENTS"][$id]["BARCODES"]))
				{
					$APPLICATION->ThrowException(GetMessage("CAT_DOC_WRONG_COUNT", array("#PRODUCT#" => '"'.$arProductInfo["ELEMENT_NAME"].'"')));
					return false;
				}
			}
		}
		return self::distributeElementsToStores($arResult, $userId);
	}

}

class CAllCatalogUnReservedDocsType extends CCatalogDocsTypes
{
	/** The method returns an array of fields needed for this type of document.
	 * @return array
	 */
	public static function getFields()
	{
		return array(
			"ELEMENT_ID" => array("required" => 'Y'),
			"AMOUNT" => array("required" => 'Y'),
			"RESERVED" => array("required" => 'Y'),
		);
	}

	/**
	 * @param $documentId
	 * @param $userId
	 * @param $currency
	 * @param $contractorId
	 * @return bool
	 */
	static public function conductDocument($documentId, $userId, $currency, $contractorId)
	{
		global $DB, $APPLICATION;

		$documentId = (int)$documentId;
		$i = 0;
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $documentId));
		while($arDocElement = $dbDocElements->Fetch())
		{
			if (!self::checkAmountField($arDocElement))
			{
				return false;
			}

			$arProductInfo = CCatalogProduct::GetByID($arDocElement["ELEMENT_ID"]);
			$newReserved = $arProductInfo["QUANTITY_RESERVED"] - $arDocElement["AMOUNT"];
			if($newReserved >= 0)
			{
				if(!CCatalogProduct::Update($arDocElement["ELEMENT_ID"], array("QUANTITY_RESERVED" => $newReserved)))
					return false;
				$dbAmount = $DB->Query("SELECT SUM(SP.AMOUNT) as SUM, CP.QUANTITY_RESERVED as RESERVED FROM b_catalog_store_product SP INNER JOIN b_catalog_product CP ON SP.PRODUCT_ID = CP.ID INNER JOIN b_catalog_store CS ON SP.STORE_ID = CS.ID WHERE SP.PRODUCT_ID = ".$arDocElement["ELEMENT_ID"]."  AND CS.ACTIVE = 'Y' GROUP BY QUANTITY_RESERVED ", true);
				if($arAmount = $dbAmount->Fetch())
				{
					$arFields = array(
						"QUANTITY" => doubleval($arAmount["SUM"] - $arAmount["RESERVED"])
					);
					if(!CCatalogProduct::Update($arDocElement["ELEMENT_ID"], $arFields))
					{
						$APPLICATION->ThrowException(GetMessage("CAT_DOC_PURCHASING_INFO_ERROR"));
						return false;
					}
					if (self::isNeedClearPublicCache(
						$arProductInfo['QUANTITY'],
						$arFields['QUANTITY'],
						$arProductInfo['QUANTITY_TRACE'],
						$arProductInfo['CAN_BUY_ZERO']
					))
					{
						$productInfo = array(
							'CAN_BUY_ZERO' => $arProductInfo['CAN_BUY_ZERO'],
							'NEGATIVE_AMOUNT_TRACE' => $arProductInfo['NEGATIVE_AMOUNT_TRACE'],
							'QUANTITY_TRACE' => $arProductInfo['QUANTITY_TRACE'],
							'OLD_QUANTITY' => $arProductInfo['QUANTITY'],
							'QUANTITY' => $arFields['QUANTITY'],
							'DELTA' => $arFields['QUANTITY'] - $arProductInfo['QUANTITY']
						);
						self::clearPublicCache($arProductInfo['ID'], $productInfo);
					}
				}
			}
			else
			{
				$name = '';
				$dbProduct = CIBlockElement::GetList(
					array(),
					array("ID" => $arDocElement["ELEMENT_ID"]),
					false,
					false,
					array('ID', 'NAME')
				);
				if ($arProduct = $dbProduct->Fetch())
				{
					$name = $arProduct['NAME'];
				}
				if ($name == '')
					$name = $arDocElement["ELEMENT_ID"];
				$APPLICATION->ThrowException(GetMessage("CAT_DOC_WRONG_RESERVED_AMOUNT", array("#PRODUCT#" => '"'.$name.'"')));
				return false;
			}
			$i++;
		}
		return ($i > 0);
	}

	/**
	 * @param $documentId
	 * @param $userId
	 * @return bool
	 */
	static public function cancellationDocument($documentId, $userId)
	{
		global $DB, $APPLICATION;

		$documentId = (int)$documentId;
		$i = 0;
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $documentId));
		while($arDocElement = $dbDocElements->Fetch())
		{
			$arResult = array();
			$arProductInfo = CCatalogProduct::GetByID($arDocElement["ELEMENT_ID"]);
			$newReserved = $arProductInfo["QUANTITY_RESERVED"] + $arDocElement["AMOUNT"];
			$arResult["QUANTITY_RESERVED"] = $newReserved;

			$dbAmount = $DB->Query("SELECT SUM(SP.AMOUNT) as SUM, CP.QUANTITY_RESERVED as RESERVED FROM b_catalog_store_product SP INNER JOIN b_catalog_product CP ON SP.PRODUCT_ID = CP.ID INNER JOIN b_catalog_store CS ON SP.STORE_ID = CS.ID WHERE SP.PRODUCT_ID = ".$arDocElement["ELEMENT_ID"]."  AND CS.ACTIVE = 'Y' GROUP BY QUANTITY_RESERVED ", true);
			if($arAmount = $dbAmount->Fetch())
			{
				$arResult["QUANTITY"] = doubleval($arAmount["SUM"] - $newReserved);
				if(!CCatalogProduct::Update($arDocElement["ELEMENT_ID"], $arResult))
				{
					$APPLICATION->ThrowException(GetMessage("CAT_DOC_PURCHASING_INFO_ERROR"));
					return false;
				}
				if (self::isNeedClearPublicCache(
					$arProductInfo['QUANTITY'],
					$arResult['QUANTITY'],
					$arProductInfo['QUANTITY_TRACE'],
					$arProductInfo['CAN_BUY_ZERO']
				))
				{
					$productInfo = array(
						'CAN_BUY_ZERO' => $arProductInfo['CAN_BUY_ZERO'],
						'NEGATIVE_AMOUNT_TRACE' => $arProductInfo['NEGATIVE_AMOUNT_TRACE'],
						'QUANTITY_TRACE' => $arProductInfo['QUANTITY_TRACE'],
						'OLD_QUANTITY' => $arProductInfo['QUANTITY'],
						'QUANTITY' => $arResult['QUANTITY'],
						'DELTA' => $arResult['QUANTITY'] - $arProductInfo['QUANTITY']
					);
					self::clearPublicCache($arProductInfo['ID'], $productInfo);
				}
			}
			$i++;
		}
		return ($i > 0);
	}
}
?>