<?
IncludeModuleLangFile(__FILE__);

class CCatalogStoreProductAll
{
	protected static function CheckFields($action, &$arFields)
	{
		if ((($action == 'ADD') || isset($arFields["STORE_ID"])) && intval($arFields["STORE_ID"])<=0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CP_EMPTY_STORE"));
			return false;
		}
		if ((($action == 'ADD') || isset($arFields["PRODUCT_ID"])) && intval($arFields["PRODUCT_ID"])<=0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CP_EMPTY_PRODUCT"));
			return false;
		}
		if  (!is_numeric($arFields["AMOUNT"]))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CP_FALSE_AMOUNT"));
			return false;
		}

		return true;
	}

	public static function UpdateFromForm($arFields)
	{
		$rsProps = CCatalogStoreProduct::GetList(array(),array("PRODUCT_ID"=>$arFields['PRODUCT_ID'], "STORE_ID"=>$arFields['STORE_ID']),false,false,array('ID'));
		if($arID = $rsProps->GetNext())
			return self::Update($arID["ID"],$arFields);
		else
			return CCatalogStoreProduct::Add($arFields);
	}

	public static function Update($id, $arFields)
	{
		$id=intval($id);
		if($id<0 || !self::CheckFields('UPDATE',$arFields))
			return false;
		global $DB;
		$strUpdate = $DB->PrepareUpdate("b_catalog_store_product", $arFields);
		$strSql = "UPDATE b_catalog_store_product SET ".$strUpdate." WHERE ID = ".$id;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return true;
	}

	public static function OnIBlockElementDelete($ProductID)
	{
		global $DB;
		$ProductID = IntVal($ProductID);
		if ($ProductID > 0)
		{
			return $DB->Query("DELETE FROM b_catalog_store_product WHERE PRODUCT_ID = ".$ProductID." ", true);
		}
	}

	public static function Delete($id)
	{
		global $DB;
		$id = intval($id);
		if ($id > 0)
		{
			$DB->Query("DELETE FROM b_catalog_store_product WHERE ID = ".$id." ", true);
			return true;
		}
		return false;
	}

	public static function addToBalanceOfStore($storeId, $productId, $amount)
	{
		$rsProps = CCatalogStoreProduct::GetList(array(), array("PRODUCT_ID" => $productId, "STORE_ID" => $storeId), false, false, array('ID', 'AMOUNT'));
		if($arID = $rsProps->Fetch())
		{
			$amount = $arID["AMOUNT"] + $amount;
			return self::Update($arID["ID"], array("AMOUNT" => $amount));
		}
		else
			return CCatalogStoreProduct::Add(array("PRODUCT_ID" => $productId, "STORE_ID" => $storeId, "AMOUNT" => $amount));
	}
}