<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/affiliate_plan.php");

class CSaleAffiliatePlan extends CAllSaleAffiliatePlan
{
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "SITE_ID", "NAME", "DESCRIPTION", "TIMESTAMP_X", "ACTIVE", "BASE_RATE", "BASE_RATE_TYPE", "BASE_RATE_CURRENCY", "MIN_PAY", "MIN_PLAN_VALUE");

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "AP.ID", "TYPE" => "int"),
				"SITE_ID" => array("FIELD" => "AP.SITE_ID", "TYPE" => "string"),
				"NAME" => array("FIELD" => "AP.NAME", "TYPE" => "string"),
				"DESCRIPTION" => array("FIELD" => "AP.DESCRIPTION", "TYPE" => "string"),
				"TIMESTAMP_X" => array("FIELD" => "AP.TIMESTAMP_X", "TYPE" => "datetime"),
				"ACTIVE" => array("FIELD" => "AP.ACTIVE", "TYPE" => "char"),
				"BASE_RATE" => array("FIELD" => "AP.BASE_RATE", "TYPE" => "double"),
				"BASE_RATE_TYPE" => array("FIELD" => "AP.BASE_RATE_TYPE", "TYPE" => "char"),
				"BASE_RATE_CURRENCY" => array("FIELD" => "AP.BASE_RATE_CURRENCY", "TYPE" => "string"),
				"MIN_PAY" => array("FIELD" => "AP.MIN_PAY", "TYPE" => "double"),
				"MIN_PLAN_VALUE" => array("FIELD" => "AP.MIN_PLAN_VALUE", "TYPE" => "double"),
				"MIN_PLAN_SUM" => array("FIELD" => "AP.MIN_PLAN_VALUE", "TYPE" => "double", "WHERE" => array("CSaleAffiliatePlan", "PrepareCurrency4Where")),

				"SECTION_ID" => array("FIELD" => "APS.ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_affiliate_plan_section APS ON (AP.ID = APS.PLAN_ID)"),
				"SECTION_MODULE_ID" => array("FIELD" => "APS.MODULE_ID", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_affiliate_plan_section APS ON (AP.ID = APS.PLAN_ID)"),
				"SECTION_SECTION_ID" => array("FIELD" => "APS.SECTION_ID", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_affiliate_plan_section APS ON (AP.ID = APS.PLAN_ID)"),
				"SECTION_RATE" => array("FIELD" => "APS.RATE", "TYPE" => "double", "FROM" => "LEFT JOIN b_sale_affiliate_plan_section APS ON (AP.ID = APS.PLAN_ID)"),
				"SECTION_RATE_TYPE" => array("FIELD" => "APS.RATE_TYPE", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_affiliate_plan_section APS ON (AP.ID = APS.PLAN_ID)"),
				"SECTION_RATE_CURRENCY" => array("FIELD" => "APS.RATE_CURRENCY", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_affiliate_plan_section APS ON (AP.ID = APS.PLAN_ID)"),
			);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_affiliate_plan AP ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			//echo "!1!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return False;
		}

		$strSql = 
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_sale_affiliate_plan AP ".
			"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])<=0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_sale_affiliate_plan AP ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			//echo "!2.1!=".htmlspecialcharsbx($strSql_tmp)."<br>";

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (strlen($arSqls["GROUPBY"]) <= 0)
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				// FOR MYSQL!!! ANOTHER CODE FOR ORACLE
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			//echo "!2.2!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])>0)
				$strSql .= "LIMIT ".IntVal($arNavStartParams["nTopCount"]);

			//echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

	public static function Add($arFields)
	{
		global $DB;

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1)=="=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CSaleAffiliatePlan::CheckFields("ADD", $arFields, 0))
			return false;

		$db_events = GetModuleEvents("sale", "OnBeforeAffiliatePlanAdd");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, Array(&$arFields))===false)
				return false;

		$arInsert = $DB->PrepareInsert("b_sale_affiliate_plan", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($arInsert[0])>0)
			{
				$arInsert[0] .= ", ";
				$arInsert[1] .= ", ";
			}
			$arInsert[0] .= $key;
			$arInsert[1] .= $value;
		}

		$strSql =
			"INSERT INTO b_sale_affiliate_plan(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		$events = GetModuleEvents("sale", "OnAfterAffiliatePlanAdd");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, Array($ID, $arFields));

		return $ID;
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

		if (!CSaleAffiliatePlan::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$db_events = GetModuleEvents("sale", "OnBeforeAffiliatePlanUpdate");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, Array($ID, &$arFields))===false)
				return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_affiliate_plan", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate)>0) $strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		$strSql = "UPDATE b_sale_affiliate_plan SET ".$strUpdate." WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		unset($GLOBALS["SALE_AFFILIATE_PLAN"]["SALE_AFFILIATE_PLAN_CACHE_".$ID]);

		$events = GetModuleEvents("sale", "OnAfterAffiliatePlanUpdate");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, Array($ID, $arFields));

		return $ID;
	}
}
?>