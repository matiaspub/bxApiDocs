<?
IncludeModuleLangFile(__FILE__);

$GLOBALS["SALE_AFFILIATE_PLAN_SECTION"] = Array();

class CAllSaleAffiliatePlanSection
{
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		if ((is_set($arFields, "PLAN_ID") || $ACTION=="ADD") && IntVal($arFields["PLAN_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SCGAPS1_NO_PLAN"), "EMPTY_PLAN_ID");
			return false;
		}
		if ((is_set($arFields, "MODULE_ID") || $ACTION=="ADD") && StrLen($arFields["MODULE_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SCGAPS1_NO_MODULE"), "EMPTY_MODULE_ID");
			return false;
		}
		if ((is_set($arFields, "SECTION_ID") || $ACTION=="ADD") && StrLen($arFields["SECTION_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SCGAPS1_NO_SECTION"), "EMPTY_SECTION_ID");
			return false;
		}

		$ID = IntVal($ID);
		$arPlanSection = false;
		if ($ACTION != "ADD")
		{
			if ($ID <= 0)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SCGAPS1_BAD_FUNC"), "FUNCTION_ERROR");
				return false;
			}
			else
			{
				$arPlanSection = CSaleAffiliatePlanSection::GetByID($ID);
				if (!$arPlanSection)
				{
					$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $ID, GetMessage("SCGAPS1_NO_RECORD")), "NO_PLAN_SECTION");
					return false;
				}
			}
		}

		if (is_set($arFields, "RATE"))
		{
			$arFields["RATE"] = str_replace(",", ".", $arFields["RATE"]);
			$arFields["RATE"] = DoubleVal($arFields["RATE"]);
		}

		if ((is_set($arFields, "RATE_TYPE") || $ACTION=="ADD") && $arFields["RATE_TYPE"] != "F")
			$arFields["RATE_TYPE"] = "P";

		if ($ACTION == "ADD")
		{
			if ($arFields["RATE_TYPE"] == "P")
				$arFields["RATE_CURRENCY"] = false;

			if ($arFields["RATE_TYPE"] == "F" && (!is_set($arFields, "RATE_CURRENCY") || StrLen($arFields["RATE_CURRENCY"]) <= 0))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SCGAPS1_NO_CURRENCY"), "EMPTY_RATE_CURRENCY");
				return false;
			}
		}
		else
		{
			if (!is_set($arFields, "RATE_TYPE"))
				$arFields["RATE_TYPE"] = $arPlanSection["RATE_TYPE"];

			if ($arFields["RATE_TYPE"] == "P")
			{
				$arFields["RATE_CURRENCY"] = false;
			}
			elseif ($arFields["RATE_TYPE"] == "F")
			{
				if (!is_set($arFields, "RATE_CURRENCY"))
					$arFields["RATE_CURRENCY"] = $arPlanSection["RATE_CURRENCY"];

				if (!is_set($arFields, "RATE_CURRENCY") || StrLen($arFields["RATE_CURRENCY"]) <= 0)
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SCGAPS1_NO_CURRENCY"), "EMPTY_RATE_CURRENCY");
					return false;
				}
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

		unset($GLOBALS["SALE_AFFILIATE_PLAN_SECTION"]["SALE_AFFILIATE_PLAN_SECTION_CACHE_".$ID]);

		return $DB->Query("DELETE FROM b_sale_affiliate_plan_section WHERE ID = ".$ID." ", true);
	}

	public static function DeleteByPlan($planID, $arSectionIDs)
	{
		global $DB;

		$planID = IntVal($planID);
		if ($planID <= 0)
			return False;

		$strSectionIDs = "0";
		for ($i = 0; $i < count($arSectionIDs); $i++)
		{
			if (IntVal($arSectionIDs[$i]) > 0)
				$strSectionIDs .= ",".IntVal($arSectionIDs[$i]);
		}

		return $DB->Query("DELETE FROM b_sale_affiliate_plan_section WHERE PLAN_ID = ".$planID." AND ID NOT IN (".$strSectionIDs.")", true);
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

		if (!CSaleAffiliatePlanSection::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_affiliate_plan_section", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate)>0) $strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		$strSql = "UPDATE b_sale_affiliate_plan_section SET ".$strUpdate." WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		unset($GLOBALS["SALE_AFFILIATE_PLAN_SECTION"]["SALE_AFFILIATE_PLAN_SECTION_CACHE_".$ID]);

		return $ID;
	}

	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
			return false;

		if (isset($GLOBALS["SALE_AFFILIATE_PLAN_SECTION"]["SALE_AFFILIATE_PLAN_SECTION_CACHE_".$ID]) && is_array($GLOBALS["SALE_AFFILIATE_PLAN_SECTION"]["SALE_AFFILIATE_PLAN_SECTION_CACHE_".$ID]))
		{
			return $GLOBALS["SALE_AFFILIATE_PLAN_SECTION"]["SALE_AFFILIATE_PLAN_SECTION_CACHE_".$ID];
		}
		else
		{
			$strSql = 
				"SELECT APS.ID, APS.PLAN_ID, APS.MODULE_ID, APS.SECTION_ID, APS.RATE, APS.RATE_TYPE, APS.RATE_CURRENCY ".
				"FROM b_sale_affiliate_plan_section APS ".
				"WHERE APS.ID = ".$ID." ";

			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($res = $db_res->Fetch())
			{
				$GLOBALS["SALE_AFFILIATE_PLAN_SECTION"]["SALE_AFFILIATE_PLAN_SECTION_CACHE_".$ID] = $res;
				return $GLOBALS["SALE_AFFILIATE_PLAN_SECTION"]["SALE_AFFILIATE_PLAN_SECTION_CACHE_".$ID];
			}
		}

		return false;
	}
}
?>