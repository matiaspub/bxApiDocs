<?
IncludeModuleLangFile(__FILE__);

class CCatalogStoreDocsElementAll
{
	protected static function CheckFields($action, &$arFields)
	{
		if((($action == 'ADD') || isset($arFields["DOC_ID"])) && intval($arFields["DOC_ID"]) <= 0)
		{
			return false;
		}
		if((isset($arFields["ELEMENT_ID"])) && intval($arFields["ELEMENT_ID"]) <= 0)
		{
			return false;
		}
		if((isset($arFields["PURCHASING_PRICE"])))
		{
			$arFields["PURCHASING_PRICE"] =  preg_replace("|\s|", '', $arFields["PURCHASING_PRICE"]);
		}

		return true;
	}

	public static function update($id, $arFields)
	{
		$id = intval($id);

		foreach(GetModuleEvents("catalog", "OnBeforeCatalogStoreDocsElementUpdate", true) as $arEvent)
			if(ExecuteModuleEventEx($arEvent, array($id, &$arFields)) === false)
				return false;

		if($id < 0 || !self::CheckFields('UPDATE',$arFields))
			return false;
		global $DB;
		$strUpdate = $DB->PrepareUpdate("b_catalog_docs_element", $arFields);
		$strSql = "UPDATE b_catalog_docs_element SET ".$strUpdate." WHERE ID = ".$id;
		if(!$DB->Query($strSql, true, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		foreach(GetModuleEvents("catalog", "OnCatalogStoreDocsElementUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($id, $arFields));
		return true;
	}

	public static function delete($id)
	{
		global $DB;
		$id = intval($id);
		if($id > 0)
		{
			foreach(GetModuleEvents("catalog", "OnBeforeCatalogStoreDocsElementDelete", true) as $arEvent)
				if(ExecuteModuleEventEx($arEvent, array($id)) === false)
					return false;

			$DB->Query("DELETE FROM b_catalog_docs_barcode WHERE DOC_ELEMENT_ID = ".$id." ", true);
			$DB->Query("DELETE FROM b_catalog_docs_element WHERE ID = ".$id." ", true);

			foreach(GetModuleEvents("catalog", "OnCatalogStoreDocsElementDelete", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($id));

			return true;
		}
		return false;
	}

	static function OnDocumentBarcodeDelete($id)
	{
		global $DB;
		$id = intval($id);
		if(!$DB->Query("DELETE FROM b_catalog_docs_element WHERE DOC_ID = ".$id." ", true))
			return false;

		foreach(GetModuleEvents("catalog", "OnDocumentElementDelete", true) as $event)
			ExecuteModuleEventEx($event, array($id));
	}
}