<?
IncludeModuleLangFile(__FILE__);

class CAllSaleAffiliateTransact
{
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		if ((is_set($arFields, "AFFILIATE_ID") || $ACTION=="ADD") && IntVal($arFields["AFFILIATE_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SCGAT2_NO_AFF"), "EMPTY_AFFILIATE_ID");
			return false;
		}
		if ((is_set($arFields, "CURRENCY") || $ACTION=="ADD") && strlen($arFields["CURRENCY"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SCGAT2_NO_CURRENCY"), "EMPTY_CURRENCY");
			return false;
		}
		if ((is_set($arFields, "TRANSACT_DATE") || $ACTION=="ADD") && strlen($arFields["TRANSACT_DATE"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SCGAT2_NO_DATE"), "EMPTY_TRANSACT_DATE");
			return false;
		}

		if (is_set($arFields, "AMOUNT") || $ACTION=="ADD")
		{
			$arFields["AMOUNT"] = str_replace(",", ".", $arFields["AMOUNT"]);
			$arFields["AMOUNT"] = DoubleVal($arFields["AMOUNT"]);
		}

		if ((is_set($arFields, "DEBIT") || $ACTION=="ADD") && $arFields["DEBIT"] != "Y")
			$arFields["DEBIT"] = "N";

		if (is_set($arFields, "AFFILIATE_ID"))
		{
			$dbAddiliate = CSaleAffiliate::GetList(array(), array("ID" => $arFields["AFFILIATE_ID"]), false, false, array("ID"));
			if (!$dbAddiliate->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["AFFILIATE_ID"], GetMessage("SCGAT2_NO_AFF1")), "ERROR_NO_AFFILIATE_ID");
				return false;
			}
		}

		return True;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
			return False;

		return $DB->Query("DELETE FROM b_sale_affiliate_transact WHERE ID = ".$ID." ", true);
	}

	public static function OnAffiliateDelete($affiliateID)
	{
		global $DB;
		$affiliateID = IntVal($affiliateID);

		return $DB->Query("DELETE FROM b_sale_affiliate_transact WHERE AFFILIATE_ID = ".$affiliateID." ", true);
	}

	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
			return false;

		$strSql = 
			"SELECT AT.ID, AT.AFFILIATE_ID, AT.AMOUNT, AT.CURRENCY, AT.DEBIT, AT.DESCRIPTION, ".
			"	AT.EMPLOYEE_ID, ".
			"	".$DB->DateToCharFunction("AT.TIMESTAMP_X", "FULL")." as TIMESTAMP_X, ".
			"	".$DB->DateToCharFunction("AT.TRANSACT_DATE", "FULL")." as TRANSACT_DATE ".
			"FROM b_sale_affiliate_transact AT ".
			"WHERE AT.ID = ".$ID." ";

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($res = $db_res->Fetch())
			return $res;

		return false;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
			return False;

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1)=="=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CSaleAffiliateTransact::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_affiliate_transact", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate)>0) $strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		$strSql = "UPDATE b_sale_affiliate_transact SET ".$strUpdate." WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}
}
?>