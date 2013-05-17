<?php
IncludeModuleLangFile(__FILE__);

class CAllCatalogDocs
{

	static $types = array(
		"A" => "CCatalogArrivalDocs",
		"M" => "CCatalogMovingDocs",
		"R" => "CCatalogReturnsDocs",
		"D" => "CCatalogDeductDocs",
		"U" => "CCatalogUnReservedDocs",
	);

	/**
	 * @param $id
	 * @param $arFields = array(
	*       "DOC_TYPE" => "a",
	 *      "..." => "",
	 *      "PRODUCTS" => array(
				array(
				"ID" => 12,
				 *              "" =>,
	 *                  "BARCODE" => array("sdfedf", "erger")
				 *          ),
				array(
				"ID" => null,
				 *              "" =>,
				 *          ),
	 *
	 * )
	 * ),
	 * )
	 * @return bool
	 */
	public static function update($id, $arFields)
	{
		/** @global CDataBase $DB */
		global $DB;
		$id = intval($id);

		if(array_key_exists('DATE_CREATE',$arFields))
			unset($arFields['DATE_CREATE']);
		if(array_key_exists('DATE_MODIFY', $arFields))
			unset($arFields['DATE_MODIFY']);
		if(array_key_exists('DATE_STATUS', $arFields))
			unset($arFields['DATE_STATUS']);
		if(array_key_exists('CREATED_BY', $arFields))
			unset($arFields['CREATED_BY']);

		$arFields['~DATE_MODIFY'] = $DB->GetNowFunction();

		if($id <= 0 || !self::CheckFields('UPDATE',$arFields))
			return false;
		$strUpdate = $DB->PrepareUpdate("b_catalog_store_docs", $arFields);

		if(!empty($strUpdate))
		{
			$strSql = "UPDATE b_catalog_store_docs SET ".$strUpdate." WHERE ID = ".$id." ";
			if(!$DB->Query($strSql, true, "File: ".__FILE__."<br>Line: ".__LINE__))
				return false;
			/*if(isset($arFields["PRODUCTS"]))
			{

				foreach($arFields["PRODUCTS"] as $f)
				{
				if ()
				Prod::uPDATE($f["ID"], $f);
				}


			}*/
		}

		return true;
	}

	/**
	 * @param $id
	 * @return bool
	 */
	public static function delete($id)
	{
		global $DB;
		$id = intval($id);
		if ($id > 0)
		{
			$dbDocument = CCatalogDocs::getList(array(), array("ID" => $id));
			if($arDocument = $dbDocument->Fetch())
			{
				if($arDocument["STATUS"] == "Y")
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_STATUS"));
					return false;
				}
			}

			foreach(GetModuleEvents("catalog", "OnBeforeDocumentDelete", true) as $event)
				ExecuteModuleEventEx($event, array($id));

			$DB->Query("DELETE FROM b_catalog_store_docs WHERE ID = ".$id." ", true);
			return true;
		}
		return false;
	}

	/**
	 * @param $action
	 * @param $arFields
	 * @return bool
	 */
	protected function checkFields($action, &$arFields)
	{
		global $DB;
		if ((($action == 'ADD') || isset($arFields["DOC_TYPE"])) && strlen($arFields["DOC_TYPE"]) <= 0 && !isset(self::$types[$arFields["DOC_TYPE"]]))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_TYPE"));
			return false;
		}
		if ((($action == 'ADD') || isset($arFields["SITE_ID"])) && strlen($arFields["SITE_ID"]) <=0 )
		{
			return false;
		}
		if(isset($arFields["STATUS"]) && ($arFields["STATUS"] === 'Y'))
		{
			$arFields['~DATE_STATUS'] = $DB->GetNowFunction();
		}
		if(isset($arFields["DATE_DOCUMENT"]) && (!CDataBase::IsDate($arFields["DATE_DOCUMENT"])))
		{
			unset($arFields["DATE_DOCUMENT"]);
			$arFields['~DATE_DOCUMENT'] = $DB->GetNowFunction();
		}
		return true;
	}


	/**
	 * @param $documentId
	 * @param int $userId
	 * @return bool|string
	 */
	public static function conductDocument($documentId, $userId = 0)
	{
		global $DB;
		$result = false;
		$id = intval($documentId);
		$userId = intval($userId);
		$dbDocType = CCatalogDocs::getList(array(), array("ID" => $id));
		if($arDocType = $dbDocType->Fetch())
		{
			$documentClass = self::$types[$arDocType["DOC_TYPE"]];
			$arFields = array("ID" => $id, "USER_ID" => $userId);
			if(strlen($arDocType["CURRENCY"]) > 0)
				$arFields["CURRENCY"] = $arDocType["CURRENCY"];
			if(strlen($arDocType["CONTRACTOR_ID"]) > 0)
				$arFields["CONTRACTOR_ID"] = $arDocType["CONTRACTOR_ID"];

			$result = $documentClass::conductDocument($arFields);
			if($result !== false)
			{
				$arDocFields = array("STATUS" => "Y", "DATE_STATUS" => $DB->GetNowFunction());
				if($userId > 0)
				{
					$arDocFields["STATUS_BY"] = $arDocFields["MODIFIED_BY"] = $userId;
				}
				if(!self::Update($id, $arDocFields))
					return false;
			}
		}
		return $result;
	}

	/**
	 * @param $idDocument
	 * @param int $userId
	 * @return array|bool|string
	 */
	public static function cancellationDocument($idDocument, $userId = 0)
	{
		global $DB;
		$result = '';
		$id = intval($idDocument);
		$dbDocType = CCatalogDocs::getList(array(), array("ID" => $id));
		if($arDocType = $dbDocType->Fetch())
		{
			if($arDocType["STATUS"] !== "Y")
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_ERROR_CANCEL_STATUS"));
				return false;
			}
			$documentClass = self::$types[$arDocType["DOC_TYPE"]];
			$arFields = array("ID" => $id);

			$result = $documentClass::cancellationDocument($arFields);
			if($result !== false)
			{
				$arDocFields = array("STATUS" => "N", "DATE_STATUS" => $DB->GetNowFunction());

				if(intval($userId) > 0)
					$arDocFields["STATUS_BY"] = intval($userId);
				if(!self::Update($id, $arDocFields))
					return false;
			}
		}
		return $result;
	}
}

abstract class CCatalogTypesDocs
{
	protected function distributeElementsToStores($arFields, $userId)
	{
		global $DB;
		$arErrorElement = array();
		if(isset($arFields["ELEMENTS"]) && is_array($arFields["ELEMENTS"]))
		{
			foreach($arFields["ELEMENTS"] as $arElement)
			{
				$arErrorElement = self::checkTotalAmount($arElement);
				if(isset($arElement["STORE_FROM"]))
				{
					$rsResult = CCatalogStoreProduct::GetList(array(), array("PRODUCT_ID" => $arElement["PRODUCT_ID"], "STORE_ID" => $arElement["STORE_FROM"]), false, false, array('ID', 'AMOUNT'));
					$arID = $rsResult->Fetch();
					$storeFromName = CCatalogStoreControlUtil::getStoreName($arElement["STORE_FROM"]);
					$productInfo = CCatalogStoreControlUtil::getProductInfo($arElement["PRODUCT_ID"]);
					if(($arID !== false) || ($arElement["NEGATIVE_AMOUNT_TRACE"] == 'Y'))
					{
						$amountForUpdate = doubleval($arID["AMOUNT"]) - $arElement["AMOUNT"];
						if(($amountForUpdate >= 0) || ($arElement["NEGATIVE_AMOUNT_TRACE"] == 'Y'))
						{
							if(!CCatalogStoreProduct::UpdateFromForm(array("PRODUCT_ID" => $arElement['PRODUCT_ID'], "STORE_ID" => $arElement['STORE_FROM'], "AMOUNT" => $amountForUpdate)))
								return false;
						}
						else
						{
							$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_INSUFFICIENTLY_AMOUNT", array("#STORE#" => '"'.$storeFromName.'"', "#PRODUCT#" => '"'.$productInfo["NAME"].'"')));
							return false;
						}
					}
					else
					{
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_INSUFFICIENTLY_AMOUNT", array("#STORE#" => $storeFromName, "#PRODUCT#" => $productInfo["NAME"])));
						return false;
					}
				}
				if(isset($arElement["STORE_TO"]))
				{
					if(!CCatalogStoreProduct::addToBalanceOfStore($arElement["STORE_TO"], $arElement["PRODUCT_ID"], $arElement["AMOUNT"]))
						return false;
				}

				if(isset($arElement["BARCODES"]) && is_array($arElement["BARCODES"]))
				{
					foreach($arElement["BARCODES"] as $arBarCode)
					{
						if(!self::applyBarCode($arBarCode, $userId))
							return false;
					}
				}
				$dbAmount = $DB->Query("SELECT SUM(SP.AMOUNT) as SUM, CP.QUANTITY_RESERVED as RESERVED FROM b_catalog_store_product SP INNER JOIN b_catalog_product CP ON SP.PRODUCT_ID = CP.ID INNER JOIN b_catalog_store CS ON SP.STORE_ID = CS.ID WHERE SP.PRODUCT_ID = ".$arElement["PRODUCT_ID"]."  AND CS.ACTIVE = 'Y' GROUP BY QUANTITY_RESERVED ", true);
				if($arAmount = $dbAmount->Fetch())
				{
					$arFields = array();
					if(isset($arElement["PURCHASING_INFO"]))
					{
						$arFields = $arElement["PURCHASING_INFO"];
					}
					$arFields["QUANTITY"] = doubleval($arAmount["SUM"] - $arAmount["RESERVED"]);
					if(!CCatalogProduct::Update($arElement["PRODUCT_ID"], $arFields))
					{
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_PURCHASING_INFO_ERROR"));
						return false;
					}
				}
				else
					return false;
			}
		}
		if(!empty($arErrorElement))
			return $arErrorElement;
		return true;
	}

	protected function applyBarCode($arFields, $userId)
	{
		$barCode = $arFields["BARCODE"];
		$elementId = $arFields["PRODUCT_ID"];
		$storeToId = (isset($arFields["STORE_ID"])) ? $arFields["STORE_ID"] : 0;
		$storeFromId = (isset($arFields["STORE_FROM"])) ? $arFields["STORE_FROM"] : 0;
		$storeName = CCatalogStoreControlUtil::getStoreName($storeFromId);
		$productInfo = CCatalogStoreControlUtil::getProductInfo($elementId);
		$newStore = 0;
		$userId = intval($userId);
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
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_STORE_BARCODE", array("#STORE#" => '"'.$storeName.'"', "#PRODUCT#" => '"'.$productInfo["NAME"].'"', "#BARCODE#" => '"'.$barCode.'"')));
						return false;
					}
				}
			}
			else
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_BARCODE_ALREADY_EXIST", array("#PRODUCT#" => '"'.$productInfo["NAME"].'"', "#BARCODE#" => '"'.$barCode.'"')));
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
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_STORE_BARCODE", array("#STORE#" => '"'.$storeName.'"', "#PRODUCT#" => '"'.$productInfo["NAME"].'"', "#BARCODE#" => '"'.$barCode.'"')));
				return false;
			}
			elseif($storeToId > 0)
				$result = CCatalogStoreBarCode::Add(array("PRODUCT_ID" => $elementId, "STORE_ID" => $storeToId, "BARCODE" => $barCode, "MODIFIED_BY" => $userId, "CREATED_BY" => $userId));
		}

		return $result;
	}

	protected function checkTotalAmount($arElement)
	{
		global $DB, $APPLICATION;
		static $arErrorElement = array();
		$dbAmount = $DB->Query("SELECT SUM(SP.AMOUNT) as SUM, CP.QUANTITY as QUANTITY, CP.QUANTITY_RESERVED as RESERVED FROM b_catalog_store_product SP INNER JOIN b_catalog_product CP ON SP.PRODUCT_ID = CP.ID INNER JOIN b_catalog_store CS ON SP.STORE_ID = CS.ID WHERE SP.PRODUCT_ID = ".intval($arElement["PRODUCT_ID"])." AND CS.ACTIVE = 'Y' GROUP BY QUANTITY, QUANTITY_RESERVED ", true);
		if($arAmount = $dbAmount->Fetch())
		{
			$sumAmountOfAllStore = $arAmount["SUM"];
			$quantityReserv = $arAmount["RESERVED"];
			$quantityTotal = $arAmount["QUANTITY"] + $quantityReserv;
			if($sumAmountOfAllStore != $quantityTotal)
			{
				$element = CCatalogStoreControlUtil::getProductInfo($arElement["PRODUCT_ID"]);
				$arErrorElement[] = $element["NAME"];
				$APPLICATION->ThrowException(GetMessage("CAT_DOC_WRONG_STORE_AMOUNT"));
			}
		}
		return $arErrorElement;
	}

	protected function checkAmountField($arDocElement)
	{
		if(intval($arDocElement["AMOUNT"]) < 1)
		{
			$productInfo = CCatalogStoreControlUtil::getProductInfo($arDocElement["ELEMENT_ID"]);
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_AMOUNT", array("#PRODUCT#" => '"'.$productInfo["NAME"].'"')));
			return false;
		}
		return true;
	}

	/** The method of conducting a document, distributes products to warehouses, according to the document type.
	 * @param $arFields
	 */
	abstract static function conductDocument($arFields);

	/** Method cancels an instrument and perform the reverse action of conducting a document.
	 * @param $arFields
	 * @return mixed
	 */
	abstract static function cancellationDocument($arFields);

}

class CAllCatalogArrivalDocs extends CCatalogTypesDocs
{
	/**
	 * @return array
	 */
	static public function getFields()
	{
		return array(
			"ELEMENT_ID" => array("required" => 'Y'),
			"AMOUNT" => array("required" => 'Y'),
			"NET_PRICE" => array("required" => 'Y'),
			"STORE_TO" => array("required" => 'Y'),
			"BAR_CODE" => array("required" => 'N'),
			"CONTRACTOR" => array("required" => 'Y'),
			"CURRENCY" => array("required" => 'Y'),
			"TOTAL" => array("required" => 'N')
		);
	}

	/**
	 * @param $arFields
	 * @return array|bool
	 */
	public static function conductDocument($arFields)
	{
		$id = intval($arFields["ID"]);
		$currency = '';
		$userId = $arFields["USER_ID"];
		if(isset($arFields["CURRENCY"]) && strlen($arFields["CURRENCY"]) > 0)
			$currency = $arFields["CURRENCY"];
		if(!isset($arFields["CONTRACTOR_ID"]) || intval($arFields["CONTRACTOR_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_CONTRACTOR"));
			return false;
		}
		$arResult = array();
		$i = 0;
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $id));
		while($arDocElement = $dbDocElements->Fetch())
		{
			if(!self::checkAmountField($arDocElement))
			{
				return false;
			}

			$arResult["ELEMENTS"][$i] = array(
				"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
				"STORE_TO" => $arDocElement["STORE_TO"],
				"AMOUNT" => $arDocElement["AMOUNT"],
			);

			$arResult["ELEMENTS"][$i]["PURCHASING_INFO"]["PURCHASING_PRICE"] = $arDocElement["PURCHASING_PRICE"];
			$arResult["ELEMENTS"][$i]["PURCHASING_INFO"]["PURCHASING_CURRENCY"] = $currency;

			if(($arProductInfo = CCatalogProduct::GetByID($arDocElement["ELEMENT_ID"])))
			{
				$arResult["ELEMENTS"][$i]["NEGATIVE_AMOUNT_TRACE"] = $arProductInfo["NEGATIVE_AMOUNT_TRACE"];
				if($arProductInfo["BARCODE_MULTI"] == 'Y')
				{
					$dbDocBarcodes = CCatalogStoreDocsBarcode::getList(array(), array("DOC_ELEMENT_ID" => $arDocElement["ID"]));
					while($arDocBarcode = $dbDocBarcodes->Fetch())
					{
						$arResult["ELEMENTS"][$i]["BARCODES"][] = array(
							"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
							"STORE_ID" => $arDocElement["STORE_TO"],
							"BARCODE" => $arDocBarcode["BARCODE"],
						);
					}
					if($arDocElement["AMOUNT"] != count($arResult["ELEMENTS"][$i]["BARCODES"]))
					{
						$productInfo = CCatalogStoreControlUtil::getProductInfo($arDocElement["ELEMENT_ID"]);
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_COUNT", array("#PRODUCT#" => '"'.$productInfo["NAME"].'"')));
						return false;
					}
				}
			}
			$i++;
		}
		return (self::distributeElementsToStores($arResult, $userId));
	}

	/**
	 * @param $arFields
	 * @return array|bool
	 */
	public static function cancellationDocument($arFields)
	{
		$id = intval($arFields["ID"]);
		$userId = $arFields["USER_ID"];
		$arResult = array();
		$i = 0;
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $id));
		while($arDocElement = $dbDocElements->Fetch())
		{
			$arResult["ELEMENTS"][$i] = array(
				"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
				"STORE_FROM" => $arDocElement["STORE_TO"],
				"AMOUNT" => $arDocElement["AMOUNT"],
			);

//			$arResult["ELEMENTS"][$i]["PURCHASING_INFO"]["PURCHASING_PRICE"] = $arDocElement["PURCHASING_PRICE"];
//			$arResult["ELEMENTS"][$i]["PURCHASING_INFO"]["PURCHASING_CURRENCY"] = $currency;

			if(($arProductInfo = CCatalogProduct::GetByID($arDocElement["ELEMENT_ID"])))
			{
				$arResult["ELEMENTS"][$i]["NEGATIVE_AMOUNT_TRACE"] = 'Y';
				if($arProductInfo["BARCODE_MULTI"] == 'Y')
				{
					$dbDocBarcodes = CCatalogStoreDocsBarcode::getList(array(), array("DOC_ELEMENT_ID" => $arDocElement["ID"]));
					while($arDocBarcode = $dbDocBarcodes->Fetch())
					{
						$arResult["ELEMENTS"][$i]["BARCODES"][] = array(
							"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
							"STORE_FROM" => $arDocElement["STORE_TO"],
							"BARCODE" => $arDocBarcode["BARCODE"],
						);
					}
					if($arDocElement["AMOUNT"] != count($arResult["ELEMENTS"][$i]["BARCODES"]))
					{
						$productInfo = CCatalogStoreControlUtil::getProductInfo($arDocElement["ELEMENT_ID"]);
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_COUNT", array("#PRODUCT#" => '"'.$productInfo["NAME"].'"')));
						return false;
					}
				}
			}
			$i++;
		}
		return self::distributeElementsToStores($arResult, $userId);
	}

}

class CAllCatalogMovingDocs extends CCatalogTypesDocs
{
	/**
	 * @return array
	 */
	static public function getFields()
	{
		return array(
			"ELEMENT_ID" => array("required" => 'Y'),
			"AMOUNT" => array("required" => 'Y'),
			"STORE_TO" => array("required" => 'Y'),
			"BAR_CODE" => array("required" => 'N'),
			"STORE_FROM" => array("required" => 'Y'),
		);
	}

	/**
	 * @param $arFields
	 * @return array|bool
	 */
	public static function conductDocument($arFields)
	{
		$id = intval($arFields["ID"]);
		$arResult = array();
		$i = 0;
		$userId = $arFields["USER_ID"];
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $id));
		while($arDocElement = $dbDocElements->Fetch())
		{
			if(!self::checkAmountField($arDocElement))
			{
				return false;
			}

			$arResult["ELEMENTS"][$i] = array(
				"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
				"STORE_TO" => $arDocElement["STORE_TO"],
				"AMOUNT" => $arDocElement["AMOUNT"],
				"STORE_FROM" => $arDocElement["STORE_FROM"],
			);

			if(($arProductInfo = CCatalogProduct::GetByID($arDocElement["ELEMENT_ID"])))
			{
				$arResult["ELEMENTS"][$i]["NEGATIVE_AMOUNT_TRACE"] = $arProductInfo["NEGATIVE_AMOUNT_TRACE"];
				if($arProductInfo["BARCODE_MULTI"] == 'Y')
				{
					$dbDocBarcodes = CCatalogStoreDocsBarcode::getList(array(), array("DOC_ELEMENT_ID" => $arDocElement["ID"]));
					while($arDocBarcode = $dbDocBarcodes->Fetch())
					{
						$arResult["ELEMENTS"][$i]["BARCODES"][] = array(
							"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
							"STORE_ID" => $arDocElement["STORE_TO"],
							"BARCODE" => $arDocBarcode["BARCODE"],
							"STORE_FROM" => $arDocElement["STORE_FROM"],
						);
					}
					if($arDocElement["AMOUNT"] != count($arResult["ELEMENTS"][$i]["BARCODES"]))
					{
						$productInfo = CCatalogStoreControlUtil::getProductInfo($arDocElement["ELEMENT_ID"]);
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_COUNT", array("#PRODUCT#" => '"'.$productInfo["NAME"].'"')));
						return false;
					}
				}
			}
			$i++;
		}
		return (self::distributeElementsToStores($arResult, $userId));
	}

	/**
	 * @param $arFields
	 * @return array|bool
	 */
	public static function cancellationDocument($arFields)
	{
		$id = intval($arFields["ID"]);
		$arResult = array();
		$i = 0;
		$userId = $arFields["USER_ID"];
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $id));
		while($arDocElement = $dbDocElements->Fetch())
		{
			$arResult["ELEMENTS"][$i] = array(
				"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
				"STORE_FROM" => $arDocElement["STORE_TO"],
				"AMOUNT" => $arDocElement["AMOUNT"],
				"STORE_TO" => $arDocElement["STORE_FROM"],
			);

			if(($arProductInfo = CCatalogProduct::GetByID($arDocElement["ELEMENT_ID"])))
			{
				$arResult["ELEMENTS"][$i]["NEGATIVE_AMOUNT_TRACE"] = "Y";
				if($arProductInfo["BARCODE_MULTI"] == 'Y')
				{
					$dbDocBarcodes = CCatalogStoreDocsBarcode::getList(array(), array("DOC_ELEMENT_ID" => $arDocElement["ID"]));
					while($arDocBarcode = $dbDocBarcodes->Fetch())
					{
						$arResult["ELEMENTS"][$i]["BARCODES"][] = array(
							"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
							"STORE_FROM" => $arDocElement["STORE_TO"],
							"BARCODE" => $arDocBarcode["BARCODE"],
							"STORE_ID" => $arDocElement["STORE_FROM"],
						);
					}
					if($arDocElement["AMOUNT"] != count($arResult["ELEMENTS"][$i]["BARCODES"]))
					{
						$productInfo = CCatalogStoreControlUtil::getProductInfo($arDocElement["ELEMENT_ID"]);
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_COUNT", array("#PRODUCT#" => '"'.$productInfo["NAME"].'"')));
						return false;
					}
				}
			}
			$i++;
		}
		return self::distributeElementsToStores($arResult, $userId);
	}

}

class CAllCatalogReturnsDocs extends CCatalogTypesDocs
{
	/**
	 * @return array
	 */
	static public function getFields()
	{
		return array(
			"ELEMENT_ID" => array("required" => 'Y'),
			"AMOUNT" => array("required" => 'Y'),
			"STORE_TO" => array("required" => 'Y'),
			"BAR_CODE" => array("required" => 'N'),
		);
	}

	/**
	 * @param $arFields
	 * @return array|bool
	 */
	public static function conductDocument($arFields)
	{
		$id = intval($arFields["ID"]);
		$arResult = array();
		$i = 0;
		$userId = $arFields["USER_ID"];
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $id));
		while($arDocElement = $dbDocElements->Fetch())
		{
			if(!self::checkAmountField($arDocElement))
			{
				return false;
			}

			$arResult["ELEMENTS"][$i] = array(
				"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
				"STORE_TO" => $arDocElement["STORE_TO"],
				"AMOUNT" => $arDocElement["AMOUNT"],
			);

			if(($arProductInfo = CCatalogProduct::GetByID($arDocElement["ELEMENT_ID"])))
			{
				$arResult["ELEMENTS"][$i]["NEGATIVE_AMOUNT_TRACE"] = $arProductInfo["NEGATIVE_AMOUNT_TRACE"];
				if($arProductInfo["BARCODE_MULTI"] == 'Y')
				{
					$dbDocBarcodes = CCatalogStoreDocsBarcode::getList(array(), array("DOC_ELEMENT_ID" => $arDocElement["ID"]));
					while($arDocBarcode = $dbDocBarcodes->Fetch())
					{
						$arResult["ELEMENTS"][$i]["BARCODES"][] = array(
							"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
							"STORE_ID" => $arDocElement["STORE_TO"],
							"BARCODE" => $arDocBarcode["BARCODE"],
						);
					}
					if($arDocElement["AMOUNT"] != count($arResult["ELEMENTS"][$i]["BARCODES"]))
					{
						$productInfo = CCatalogStoreControlUtil::getProductInfo($arDocElement["ELEMENT_ID"]);
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_COUNT", array("#PRODUCT#" => '"'.$productInfo["NAME"].'"')));
						return false;
					}
					/*else
					{
						foreach($arResult["ELEMENTS"][$i]["BARCODES"] as $arBarCode)
						{
							$dbResult = CCatalogStoreBarCode::GetList(array(), array("BARCODE" => $arBarCode["BARCODE"]));
							if(!$arResultBarCode = $dbResult->Fetch())
							{
								$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_BARCODE", array("#BARCODE#" => '"'.$arBarCode["BARCODE"].'"')));
								return false;
							}
						}
					}*/
				}
			}
			$i++;
		}
		return self::distributeElementsToStores($arResult, $userId);
	}

	/**
	 * @param $arFields
	 * @return array|bool
	 */
	public static function cancellationDocument($arFields)
	{
		$id = intval($arFields["ID"]);
		$arResult = array();
		$i = 0;
		$userId = $arFields["USER_ID"];
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $id));
		while($arDocElement = $dbDocElements->Fetch())
		{
			$arResult["ELEMENTS"][$i] = array(
				"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
				"STORE_FROM" => $arDocElement["STORE_TO"],
				"AMOUNT" => $arDocElement["AMOUNT"],
			);

			if(($arProductInfo = CCatalogProduct::GetByID($arDocElement["ELEMENT_ID"])))
			{
				$arResult["ELEMENTS"][$i]["NEGATIVE_AMOUNT_TRACE"] = "Y";
				if($arProductInfo["BARCODE_MULTI"] == 'Y')
				{
					$dbDocBarcodes = CCatalogStoreDocsBarcode::getList(array(), array("DOC_ELEMENT_ID" => $arDocElement["ID"]));
					while($arDocBarcode = $dbDocBarcodes->Fetch())
					{
						$arResult["ELEMENTS"][$i]["BARCODES"][] = array(
							"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
							"STORE_FROM" => $arDocElement["STORE_TO"],
							"BARCODE" => $arDocBarcode["BARCODE"],
						);
					}
					if($arDocElement["AMOUNT"] != count($arResult["ELEMENTS"][$i]["BARCODES"]))
					{
						$productInfo = CCatalogStoreControlUtil::getProductInfo($arDocElement["ELEMENT_ID"]);
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_COUNT", array("#PRODUCT#" => '"'.$productInfo["NAME"].'"')));
						return false;
					}
					/*else
					{
						foreach($arResult["ELEMENTS"][$i]["BARCODES"] as $arBarCode)
						{
							$dbResult = CCatalogStoreBarCode::GetList(array(), array("BARCODE" => $arBarCode["BARCODE"]));
							if(!$arResultBarCode = $dbResult->Fetch())
							{
								$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_BARCODE", array("#BARCODE#" => '"'.$arBarCode["BARCODE"].'"')));
								return false;
							}
						}
					}*/
				}
			}
			$i++;
		}
		return self::distributeElementsToStores($arResult, $userId);
	}

}

class CAllCatalogDeductDocs extends CCatalogTypesDocs
{
	/**
	 * @return array
	 */
	static public function getFields()
	{
		return array(
			"ELEMENT_ID" => array("required" => 'Y'),
			"AMOUNT" => array("required" => 'Y'),
			"BAR_CODE" => array("required" => 'N'),
			"STORE_FROM" => array("required" => 'Y'),
		);
	}

	/**
	 * @param $arFields
	 * @return array|bool
	 */
	public static function conductDocument($arFields)
	{
		$id = intval($arFields["ID"]);
		$arResult = array();
		$i = 0;
		$userId = $arFields["USER_ID"];
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $id));
		while($arDocElement = $dbDocElements->Fetch())
		{
			if(!self::checkAmountField($arDocElement))
			{
				return false;
			}

			$arResult["ELEMENTS"][$i] = array(
				"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
				"AMOUNT" => $arDocElement["AMOUNT"],
				"STORE_FROM" => $arDocElement["STORE_FROM"],
			);

			if(($arProductInfo = CCatalogProduct::GetByID($arDocElement["ELEMENT_ID"])))
			{
				$arResult["ELEMENTS"][$i]["NEGATIVE_AMOUNT_TRACE"] = $arProductInfo["NEGATIVE_AMOUNT_TRACE"];
				if($arProductInfo["BARCODE_MULTI"] == 'Y')
				{
					$dbDocBarcodes = CCatalogStoreDocsBarcode::getList(array(), array("DOC_ELEMENT_ID" => $arDocElement["ID"]));
					while($arDocBarcode = $dbDocBarcodes->Fetch())
					{
						$arResult["ELEMENTS"][$i]["BARCODES"][] = array(
							"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
							"BARCODE" => $arDocBarcode["BARCODE"],
							"STORE_FROM" => $arDocElement["STORE_FROM"],
						);
					}
					if($arDocElement["AMOUNT"] != count($arResult["ELEMENTS"][$i]["BARCODES"]))
					{
						$productInfo = CCatalogStoreControlUtil::getProductInfo($arDocElement["ELEMENT_ID"]);
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_COUNT", array("#PRODUCT#" => '"'.$productInfo["NAME"].'"')));
						return false;
					}
				}
			}
			$i++;
		}
		return (self::distributeElementsToStores($arResult, $userId));
	}

	/**
	 * @param $arFields
	 * @return array|bool
	 */
	public static function cancellationDocument($arFields)
	{
		$id = intval($arFields["ID"]);
		$arResult = array();
		$i = 0;
		$userId = $arFields["USER_ID"];
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $id));
		while($arDocElement = $dbDocElements->Fetch())
		{
			$arResult["ELEMENTS"][$i] = array(
				"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
				"AMOUNT" => $arDocElement["AMOUNT"],
				"STORE_TO" => $arDocElement["STORE_FROM"],
			);

			if(($arProductInfo = CCatalogProduct::GetByID($arDocElement["ELEMENT_ID"])))
			{
				$arResult["ELEMENTS"][$i]["NEGATIVE_AMOUNT_TRACE"] = "Y";
				if($arProductInfo["BARCODE_MULTI"] == 'Y')
				{
					$dbDocBarcodes = CCatalogStoreDocsBarcode::getList(array(), array("DOC_ELEMENT_ID" => $arDocElement["ID"]));
					while($arDocBarcode = $dbDocBarcodes->Fetch())
					{
						$arResult["ELEMENTS"][$i]["BARCODES"][] = array(
							"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
							"BARCODE" => $arDocBarcode["BARCODE"],
							"STORE_ID" => $arDocElement["STORE_FROM"],
						);
					}
					if($arDocElement["AMOUNT"] != count($arResult["ELEMENTS"][$i]["BARCODES"]))
					{
						$productInfo = CCatalogStoreControlUtil::getProductInfo($arDocElement["ELEMENT_ID"]);
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_COUNT", array("#PRODUCT#" => '"'.$productInfo["NAME"].'"')));
						return false;
					}
				}
			}
			$i++;
		}
		return self::distributeElementsToStores($arResult, $userId);
	}

}

class CAllCatalogUnReservedDocs extends CCatalogTypesDocs
{
	/**
	 * @return array
	 */
	public static function getFields()
	{
		return array(
			"ELEMENT_ID" => array("required" => 'Y'),
			"AMOUNT" => array("required" => 'Y'),
			"RESERVED" => array("required" => 'Y'),
			"BAR_CODE" => array("required" => 'N'),
		);
	}

	/**
	 * @param $arFields
	 * @return array|bool
	 */
	public static function conductDocument($arFields)
	{
		global $DB;
		$id = intval($arFields["ID"]);
		$arResult = array();
		$i = 0;
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $id));
		while($arDocElement = $dbDocElements->Fetch())
		{
			if(!self::checkAmountField($arDocElement))
			{
				return false;
			}

			$arResult["ELEMENTS"][$i] = array(
				"PRODUCT_ID" => $arDocElement["ELEMENT_ID"],
				"AMOUNT" => $arDocElement["AMOUNT"],
			);

			$arProductInfo = CCatalogProduct::GetByID($arDocElement["ELEMENT_ID"]);
			$newReserved = $arProductInfo["QUANTITY_RESERVED"] - $arDocElement["AMOUNT"];
			if($newReserved >= 0)
			{
				if(!CCatalogProduct::Update($arDocElement["ELEMENT_ID"], array("QUANTITY_RESERVED" => $newReserved)))
					return false;
				$dbAmount = $DB->Query("SELECT SUM(SP.AMOUNT) as SUM, CP.QUANTITY_RESERVED as RESERVED FROM b_catalog_store_product SP INNER JOIN b_catalog_product CP ON SP.PRODUCT_ID = CP.ID INNER JOIN b_catalog_store CS ON SP.STORE_ID = CS.ID WHERE SP.PRODUCT_ID = ".$arDocElement["ELEMENT_ID"]."  AND CS.ACTIVE = 'Y' GROUP BY QUANTITY_RESERVED ", true);
				if($arAmount = $dbAmount->Fetch())
				{
					$arFields = array();
					$arFields["QUANTITY"] = doubleval($arAmount["SUM"] - $arAmount["RESERVED"]);
					if(!CCatalogProduct::Update($arDocElement["ELEMENT_ID"], $arFields))
					{
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_PURCHASING_INFO_ERROR"));
						return false;
					}
				}
			}
			else
			{
				$productInfo = CCatalogStoreControlUtil::getProductInfo($arDocElement["ELEMENT_ID"]);
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_RESERVED_AMOUNT", array("#PRODUCT#" => '"'.$productInfo["NAME"].'"')));
				return false;
			}
			$i++;
		}
		if($i > 0)
			return true;
		$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_WRONG_ELEMENT_COUNT"));
		return false;
	}

	/**
	 * @param $arFields
	 * @return array|bool
	 */
	public static function cancellationDocument($arFields)
	{
		global $DB;
		$id = intval($arFields["ID"]);
		$i = 0;
		$dbDocElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $id));
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
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_PURCHASING_INFO_ERROR"));
					return false;
				}
			}
			$i++;
		}
		if($i > 0)
			return true;
		return false;
	}

}

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
		if ($elementId <= 0)
			return $result;

		$dbProduct = CIBlockElement::GetList(array(), array("ID" => $elementId), false, false, array('ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'DETAIL_PICTURE', 'PREVIEW_PICTURE', 'IBLOCK_TYPE_ID', 'NAME'));
		while($arProduct = $dbProduct->GetNext())
		{

			$imgCode = "";

			if ($arProduct["IBLOCK_ID"] > 0)
				$arProduct["EDIT_PAGE_URL"] = CIBlock::GetAdminElementEditLink($arProduct["IBLOCK_ID"], $elementId);

			if ($arProduct["DETAIL_PICTURE"] > 0)
				$imgCode = $arProduct["DETAIL_PICTURE"];
			elseif ($arProduct["PREVIEW_PICTURE"] > 0)
				$imgCode = $arProduct["PREVIEW_PICTURE"];

			$arProduct["NAME"] = ($arProduct["NAME"]);
			$arProduct["DETAIL_PAGE_URL"] = htmlspecialcharsex($arProduct["DETAIL_PAGE_URL"]);
			$arProduct["CURRENCY"] = htmlspecialcharsex($arProduct["CURRENCY"]);

			if ($imgCode > 0)
			{
				$arFile = CFile::GetFileArray($imgCode);
				$arImgProduct = CFile::ResizeImageGet($arFile, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_PROPORTIONAL, false, false);
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
