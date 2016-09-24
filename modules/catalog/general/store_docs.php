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
	 * @param $arFields
	 * @return bool
	 */
	public static function update($id, $arFields)
	{
		/** @global CDataBase $DB */
		global $DB;
		$id = (int)$id;

		foreach(GetModuleEvents("catalog", "OnBeforeDocumentUpdate", true) as $arEvent)
			if(ExecuteModuleEventEx($arEvent, array($id, &$arFields)) === false)
				return false;

		if(array_key_exists('DATE_CREATE',$arFields))
			unset($arFields['DATE_CREATE']);
		if(array_key_exists('DATE_MODIFY', $arFields))
			unset($arFields['DATE_MODIFY']);
		if(array_key_exists('DATE_STATUS', $arFields))
			unset($arFields['DATE_STATUS']);
		if(array_key_exists('CREATED_BY', $arFields))
			unset($arFields['CREATED_BY']);

		$arFields['~DATE_MODIFY'] = $DB->GetNowFunction();

		if ($id <= 0 || !static::checkFields('UPDATE', $arFields))
			return false;
		$strUpdate = $DB->PrepareUpdate("b_catalog_store_docs", $arFields);

		if(!empty($strUpdate))
		{
			$strSql = "update b_catalog_store_docs set ".$strUpdate." where ID = ".$id;
			if(!$DB->Query($strSql, true, "File: ".__FILE__."<br>Line: ".__LINE__))
				return false;

			if(isset($arFields["ELEMENT"]))
			{
				foreach($arFields["ELEMENT"] as $arElement)
				{
					if(is_array($arElement))
						CCatalogStoreDocsElement::update($arElement["ID"], $arElement);
				}
			}

			foreach(GetModuleEvents("catalog", "OnDocumentUpdate", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($id, $arFields));
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
		$id = (int)$id;
		if($id > 0)
		{
			$dbDocument = CCatalogDocs::getList(array(), array("ID" => $id), false, false, array('ID', 'STATUS'));
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

			$DB->Query("DELETE FROM b_catalog_store_docs WHERE ID = ".$id, true);

			foreach(GetModuleEvents("catalog", "OnDocumentDelete", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($id));

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
		global $APPLICATION;

		if((($action == 'ADD') || isset($arFields["DOC_TYPE"])) && strlen($arFields["DOC_TYPE"]) <= 0 && !isset(self::$types[$arFields["DOC_TYPE"]]))
		{
			$APPLICATION->ThrowException(GetMessage("CAT_DOC_WRONG_TYPE"));
			return false;
		}
		if((($action == 'ADD') || isset($arFields["SITE_ID"])) && strlen($arFields["SITE_ID"]) <=0 )
		{
			$APPLICATION->ThrowException(GetMessage("CAT_DOC_WRONG_SITE_ID"));
			return false;
		}
		if ($action == 'ADD' || array_key_exists('STATUS', $arFields))
		{
			$arFields['STATUS'] = ('Y' == $arFields['STATUS'] ? 'Y' : 'N');
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
		global $APPLICATION;

		$documentId = (int)$documentId;
		$userId = (int)$userId;
		$currency = null;
		$contractorId = 0;
		$result = false;
		$dbDocType = CCatalogDocs::getList(
			array(),
			array("ID" => $documentId),
			false,
			false,
			array('ID', 'DOC_TYPE', 'CURRENCY', 'CONTRACTOR_ID', 'STATUS')
		);
		if($arDocType = $dbDocType->Fetch())
		{
			if ('Y' != $arDocType['STATUS'])
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
					if(!self::update($documentId, $arDocFields))
						return false;
				}
			}
			else
			{
				$APPLICATION->ThrowException(GetMessage("CAT_DOC_STATUS_ALREADY_YES"));
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
		$documentId = (int)$documentId;
		$userId = (int)$userId;
		$dbDocType = CCatalogDocs::getList(
			array(),
			array("ID" => $documentId),
			false,
			false,
			array('ID', 'DOC_TYPE', 'STATUS')
		);
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

				if($userId > 0)
					$arDocFields["STATUS_BY"] = $userId;
				if(!self::update($documentId, $arDocFields))
					return false;
			}
		}
		return $result;
	}

	public static function OnIBlockElementDelete($productID)
	{
		global $DB;
		$productID = (int)$productID;
		if($productID > 0)
		{
			$dbDeleteElements = CCatalogStoreDocsElement::getList(array(), array("ELEMENT_ID" => $productID), false, false, array('ID'));
			while($arDeleteElements = $dbDeleteElements->fetch())
			{
				CCatalogStoreDocsElement::delete($arDeleteElements["ID"]);
			}
			return $DB->Query("delete from b_catalog_store_barcode where PRODUCT_ID = ".$productID, true);
		}
		return true;
	}

	public static function OnCatalogStoreDelete($storeID)
	{
		global $DB;
		$storeID = (int)$storeID;
		if ($storeID <= 0)
			return false;

		return $DB->Query("delete from b_catalog_store_barcode where STORE_ID = ".$storeID, true);
	}

	public static function OnBeforeIBlockElementDelete($productID)
	{
		global $APPLICATION;

		$productID = (int)$productID;
		if ($productID > 0)
		{
			$dbStoreDocs = CCatalogDocs::getList(array(), array("PRODUCTS_ELEMENT_ID" => $productID, "STATUS" => "Y"), false, false, array('ID'));
			if ($arStoreDocs = $dbStoreDocs->fetch())
			{
				$APPLICATION->ThrowException(GetMessage("CAT_DOC_ERROR_ELEMENT_IN_DOCUMENT_EXT"));
				return false;
			}
		}
		return true;
	}
}