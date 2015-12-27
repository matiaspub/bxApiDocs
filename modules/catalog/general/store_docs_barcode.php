<?
IncludeModuleLangFile(__FILE__);

class CCatalogStoreDocsBarcodeAll
{
	protected static function checkFields($action, &$arFields)
	{
		if ((($action == 'ADD') || is_set($arFields, "BARCODE")) && (strlen($arFields["BARCODE"]) <= 0))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CP_EMPTY_BARCODE"));
			return false;
		}

		return true;
	}

	public static function update($id, $arFields)
	{
		$id=intval($id);

		foreach(GetModuleEvents("catalog", "OnBeforeCatalogStoreDocsBarcodeUpdate", true) as $arEvent)
			if(ExecuteModuleEventEx($arEvent, array($id, &$arFields)) === false)
				return false;

		if($id < 0 || !self::checkFields('UPDATE', $arFields))
			return false;
		global $DB;
		$strUpdate = $DB->PrepareUpdate("b_catalog_docs_barcode", $arFields);
		$strSql = "UPDATE b_catalog_docs_barcode SET ".$strUpdate." WHERE ID = ".$id;
		if(!$DB->Query($strSql, true, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		foreach(GetModuleEvents("catalog", "OnStoreDocsBarcodeUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($id, $arFields));
		return true;
	}

	public static function delete($id)
	{
		global $DB;
		$id = intval($id);
		if ($id > 0)
		{
			foreach(GetModuleEvents("catalog", "OnBeforeCatalogStoreDocsBarcodeDelete", true) as $arEvent)
				if(ExecuteModuleEventEx($arEvent, array($id)) === false)
					return false;

			$DB->Query("DELETE FROM b_catalog_docs_barcode WHERE ID = ".$id." ", true);

			foreach(GetModuleEvents("OnCatalogStoreDocsBarcodeDelete", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($id));
			return true;
		}
		return false;
	}

	static function OnBeforeDocumentDelete($id)
	{
		global $DB;
		$id = intval($id);
		$dbElements = CCatalogStoreDocsElement::getList(array(), array("DOC_ID" => $id));
		while($arElement = $dbElements->Fetch())
		{
			if(!$DB->Query("DELETE FROM b_catalog_docs_barcode WHERE DOC_ELEMENT_ID = ".$arElement["ID"]." ", true))
				return false;
		}

		foreach(GetModuleEvents("catalog", "OnDocumentBarcodeDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($id));
	}

}