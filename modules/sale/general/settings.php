<?
IncludeModuleLangFile(__FILE__);

class CAllSaleLang
{
	public static function Add($arFields)
	{
		global $DB;

		$db_result = $DB->Query("SELECT 'x' FROM b_sale_lang WHERE LID = '".$DB->ForSql($arFields["LID"], 2)."'");
		if ($db_result->Fetch())
			return false;
		else
		{
			$arInsert = $DB->PrepareInsert("b_sale_lang", $arFields);

			$strSql =
				"INSERT INTO b_sale_lang(".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return true;
	}

	public static function Update($LID, $arFields)
	{
		global $DB;

		if ($LID==$arFields["LID"])
		{
			$strUpdate = $DB->PrepareUpdate("b_sale_lang", $arFields);
			$strSql = "UPDATE b_sale_lang SET ".$strUpdate." WHERE LID = '".$DB->ForSql($LID, 2)."' ";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			return true;
		}
		else
		{
			die("h3jg53jh2g3jh6g");
		}
	}

	public static function Delete($LID)
	{
		global $DB;

		return $DB->Query("DELETE FROM b_sale_lang WHERE LID = '".$DB->ForSQL($LID, 2)."'", true);
	}

	public static function GetByID($LID)
	{
		global $DB;

		$strSql =
			"SELECT * ".
			"FROM b_sale_lang ".
			"WHERE LID = '".$DB->ForSQL($LID, 2)."'";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	public static function GetLangCurrency($LID)
	{
		$LID = trim($LID);
		if (strlen($LID)<=0) return false;
		$def_curr = "";
		if ($res = CSaleLang::GetByID($LID))
			$def_curr = $res["CURRENCY"];
		if (strlen($def_curr)<=0)
			$def_curr = COption::GetOptionString("sale", "default_currency", "RUB");
		return $def_curr;
	}
	
	public static function OnBeforeCurrencyDelete($currency)
	{
		global $DB;
		if (strlen($currency)<=0) return false;

		$strSql =
			"SELECT * ".
			"FROM b_sale_lang ".
			"WHERE CURRENCY = '".$DB->ForSQL($currency, 3)."'";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			$GLOBALS["APPLICATION"]->ThrowException(str_replace("#CURRENCY#", $currency, GetMessage("SKGO_ERROR_CURRENCY")), "ERROR_CURRENCY");
			return false;
		}
		
		return True;
	}

}



class CAllSaleGroupAccessToSite
{
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		if ((is_set($arFields, "GROUP_ID") || $ACTION=="ADD") && IntVal($arFields["GROUP_ID"])<=0)
		{
			$GLOBALS["APPLICATION"]->ThrowException("Empty group field", "EMPTY_GROUP_ID");
			return false;
		}

		if ((is_set($arFields, "SITE_ID") || $ACTION=="ADD") && strlen($arFields["SITE_ID"])<=0)
		{
			$GLOBALS["APPLICATION"]->ThrowException("Empty site field", "EMPTY_SITE_ID");
			return false;
		}

		return True;
	}

	public static function Update($ID, &$arFields)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGS_NO_ID"), "NO_ID");
			return false;
		}

		if (!CSaleGroupAccessToSite::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_site2group", $arFields);
		$strSql = "UPDATE b_sale_site2group SET ".$strUpdate." WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return True;
	}

	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		$strSql =
			"SELECT * ".
			"FROM b_sale_site2group ".
			"WHERE ID = ".$ID."";
		$dbGroupSite = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($arGroupSite = $dbGroupSite->Fetch())
			return $arGroupSite;

		return False;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGS_NO_DEL_ID"), "NO_ID");
			return false;
		}

		return $DB->Query("DELETE FROM b_sale_site2group WHERE ID = ".$ID." ", true);
	}

	public static function DeleteBySite($SITE_ID)
	{
		global $DB;

		$SITE_ID = Trim($SITE_ID);
		if (strlen($SITE_ID) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGS_NO_DEL_SITE"), "NO_SITE_ID");
			return false;
		}

		return $DB->Query("DELETE FROM b_sale_site2group WHERE SITE_ID = '".$DB->ForSql($SITE_ID, 2)."' ", true);
	}

	public static function DeleteByGroup($GROUP_ID)
	{
		global $DB;

		$GROUP_ID = IntVal($GROUP_ID);
		if ($GROUP_ID <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGS_NO_DEL_GROUP"), "NO_GROUP_ID");
			return false;
		}

		return $DB->Query("DELETE FROM b_sale_site2group WHERE GROUP_ID = ".$GROUP_ID." ", true);
	}
}


class CAllSaleGroupAccessToFlag
{
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		if ((is_set($arFields, "GROUP_ID") || $ACTION=="ADD") && IntVal($arFields["GROUP_ID"])<=0)
		{
			$GLOBALS["APPLICATION"]->ThrowException("Empty group field", "EMPTY_GROUP_ID");
			return false;
		}

		if ((is_set($arFields, "ORDER_FLAG") || $ACTION=="ADD") && strlen($arFields["ORDER_FLAG"])<=0)
		{
			$GLOBALS["APPLICATION"]->ThrowException("Empty flag field", "EMPTY_ORDER_FLAG");
			return false;
		}

		return True;
	}

	public static function Update($ID, &$arFields)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGS_NO_ID"), "NO_ID");
			return false;
		}

		if (!CSaleGroupAccessToFlag::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_order_flags2group", $arFields);
		$strSql = "UPDATE b_sale_order_flags2group SET ".$strUpdate." WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return True;
	}

	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		$strSql =
			"SELECT * ".
			"FROM b_sale_order_flags2group ".
			"WHERE ID = ".$ID."";
		$dbGroupSite = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($arGroupSite = $dbGroupSite->Fetch())
			return $arGroupSite;

		return False;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGS_NO_DEL_ID"), "NO_ID");
			return false;
		}

		return $DB->Query("DELETE FROM b_sale_order_flags2group WHERE ID = ".$ID." ", true);
	}

	public static function DeleteByGroup($GROUP_ID)
	{
		global $DB;

		$GROUP_ID = IntVal($GROUP_ID);
		if ($GROUP_ID <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGS_NO_DEL_GROUP"), "NO_GROUP_ID");
			return false;
		}

		return $DB->Query("DELETE FROM b_sale_order_flags2group WHERE GROUP_ID = ".$GROUP_ID." ", true);
	}

	public static function DeleteByFlag($ORDER_FLAG)
	{
		global $DB;

		$ORDER_FLAG = Trim($ORDER_FLAG);
		if (strlen($ORDER_FLAG) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGS_NO_DEL_FLAG"), "NO_ORDER_FLAG");
			return false;
		}

		return $DB->Query("DELETE FROM b_sale_order_flags2group WHERE ORDER_FLAG = '".$DB->ForSql($ORDER_FLAG, 1)."' ", true);
	}
}
?>