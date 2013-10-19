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
		if($id > 0)
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
		if(isset($arFields["STATUS"]))
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
		$documentId = intval($documentId);
		$userId = intval($userId);
		$currency = null;
		$contractorId = 0;
		$result = false;
		$dbDocType = CCatalogDocs::getList(array(), array("ID" => $documentId));
		if($arDocType = $dbDocType->Fetch())
		{
			$documentClass = self::$types[$arDocType["DOC_TYPE"]];
			if(strlen($arDocType["CURRENCY"]) > 0)
				$currency = $arDocType["CURRENCY"];
			if(strlen($arDocType["CONTRACTOR_ID"]) > 0)
				$contractorId = $arDocType["CONTRACTOR_ID"];

			$result = $documentClass::conductDocument($documentId, $userId, $currency, $contractorId);
			if($result !== false)
			{
				$arDocFields = array("STATUS" => "Y");
				if($userId > 0)
				{
					$arDocFields["STATUS_BY"] = $arDocFields["MODIFIED_BY"] = $userId;
				}
				if(!self::Update($documentId, $arDocFields))
					return false;
			}
		}
		return $result;
	}

	/**
	 * @param $documentId
	 * @param int $userId
	 * @return array|bool|string
	 */
	public static function cancellationDocument($documentId, $userId = 0)
	{
		$result = '';
		$documentId = intval($documentId);
		$dbDocType = CCatalogDocs::getList(array(), array("ID" => $documentId));
		if($arDocType = $dbDocType->Fetch())
		{
			if($arDocType["STATUS"] !== "Y")
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CAT_DOC_ERROR_CANCEL_STATUS"));
				return false;
			}
			$documentClass = self::$types[$arDocType["DOC_TYPE"]];

			$result = $documentClass::cancellationDocument($documentId, $userId);
			if($result !== false)
			{
				$arDocFields = array("STATUS" => "N");

				if(intval($userId) > 0)
					$arDocFields["STATUS_BY"] = intval($userId);
				if(!self::Update($documentId, $arDocFields))
					return false;
			}
		}
		return $result;
	}

	public static function OnIBlockElementDelete($productID)
	{
		global $DB;
		$productID = IntVal($productID);
		if ($productID > 0)
		{
			return $DB->Query("DELETE FROM b_catalog_store_barcode WHERE PRODUCT_ID = ".$productID." ", true);
		}
	}

	public static function OnCatalogStoreDelete($storeID)
	{
		global $DB;
		$storeID = IntVal($storeID);
		if ($storeID > 0)
		{
			return $DB->Query("DELETE FROM b_catalog_store_barcode WHERE STORE_ID = ".$storeID." ", true);
		}
	}

}