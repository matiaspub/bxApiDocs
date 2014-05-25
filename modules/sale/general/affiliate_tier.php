<?
IncludeModuleLangFile(__FILE__);

$GLOBALS["SALE_AFFILIATE_TIER"] = Array();

class CAllSaleAffiliateTier
{
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		if ((is_set($arFields, "SITE_ID") || $ACTION=="ADD") && StrLen($arFields["SITE_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SCGAT1_NO_SITE"), "EMPTY_SITE_ID");
			return false;
		}

		if (is_set($arFields, "RATE1"))
		{
			$arFields["RATE1"] = str_replace(",", ".", $arFields["RATE1"]);
			$arFields["RATE1"] = DoubleVal($arFields["RATE1"]);
		}

		if (is_set($arFields, "RATE2"))
		{
			$arFields["RATE2"] = str_replace(",", ".", $arFields["RATE2"]);
			$arFields["RATE2"] = DoubleVal($arFields["RATE2"]);
		}

		if (is_set($arFields, "RATE3"))
		{
			$arFields["RATE3"] = str_replace(",", ".", $arFields["RATE3"]);
			$arFields["RATE3"] = DoubleVal($arFields["RATE3"]);
		}

		if (is_set($arFields, "RATE4"))
		{
			$arFields["RATE4"] = str_replace(",", ".", $arFields["RATE4"]);
			$arFields["RATE4"] = DoubleVal($arFields["RATE4"]);
		}

		if (is_set($arFields, "RATE5"))
		{
			$arFields["RATE5"] = str_replace(",", ".", $arFields["RATE5"]);
			$arFields["RATE5"] = DoubleVal($arFields["RATE5"]);
		}

		return True;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
			return False;

		unset($GLOBALS["SALE_AFFILIATE_TIER"]["SALE_AFFILIATE_TIER_CACHE_".$ID]);

		return $DB->Query("DELETE FROM b_sale_affiliate_tier WHERE ID = ".$ID." ", true);
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

		if (!CSaleAffiliateTier::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_affiliate_tier", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate)>0) $strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		$strSql = "UPDATE b_sale_affiliate_tier SET ".$strUpdate." WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		unset($GLOBALS["SALE_AFFILIATE_TIER"]["SALE_AFFILIATE_TIER_CACHE_".$ID]);

		return $ID;
	}

	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
			return false;

		if (isset($GLOBALS["SALE_AFFILIATE_TIER"]["SALE_AFFILIATE_TIER_CACHE_".$ID]) && is_array($GLOBALS["SALE_AFFILIATE_TIER"]["SALE_AFFILIATE_TIER_CACHE_".$ID]))
		{
			return $GLOBALS["SALE_AFFILIATE_TIER"]["SALE_AFFILIATE_TIER_CACHE_".$ID];
		}
		else
		{
			$strSql = 
				"SELECT AT.ID, AT.SITE_ID, AT.RATE1, AT.RATE2, AT.RATE3, AT.RATE4, AT.RATE5 ".
				"FROM b_sale_affiliate_tier AT ".
				"WHERE AT.ID = ".$ID." ";

			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($res = $db_res->Fetch())
			{
				$GLOBALS["SALE_AFFILIATE_TIER"]["SALE_AFFILIATE_TIER_CACHE_".$ID] = $res;
				return $GLOBALS["SALE_AFFILIATE_TIER"]["SALE_AFFILIATE_TIER_CACHE_".$ID];
			}
		}

		return false;
	}
}
?>