<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/affiliate.php");

class CSaleAffiliate extends CAllSaleAffiliate
{
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "SITE_ID", "USER_ID", "AFFILIATE_ID", "PLAN_ID", "ACTIVE", "TIMESTAMP_X", "DATE_CREATE", "PAID_SUM", "APPROVED_SUM", "PENDING_SUM", "ITEMS_NUMBER", "ITEMS_SUM", "LAST_CALCULATE", "AFF_SITE", "AFF_DESCRIPTION", "FIX_PLAN");

		// FIELDS -->
		$arFields = array(
			"ID" => array("FIELD" => "A.ID", "TYPE" => "int"),
			"SITE_ID" => array("FIELD" => "A.SITE_ID", "TYPE" => "string"),
			"USER_ID" => array("FIELD" => "A.USER_ID", "TYPE" => "int"),
			"AFFILIATE_ID" => array("FIELD" => "A.AFFILIATE_ID", "TYPE" => "int"),
			"PLAN_ID" => array("FIELD" => "A.PLAN_ID", "TYPE" => "int"),
			"ACTIVE" => array("FIELD" => "A.ACTIVE", "TYPE" => "char"),
			"TIMESTAMP_X" => array("FIELD" => "A.TIMESTAMP_X", "TYPE" => "datetime"),
			"DATE_CREATE" => array("FIELD" => "A.DATE_CREATE", "TYPE" => "datetime"),
			"PAID_SUM" => array("FIELD" => "A.PAID_SUM", "TYPE" => "double"),
			"APPROVED_SUM" => array("FIELD" => "A.APPROVED_SUM", "TYPE" => "double"),
			"PENDING_SUM" => array("FIELD" => "A.PENDING_SUM", "TYPE" => "double"),
			"ITEMS_NUMBER" => array("FIELD" => "A.ITEMS_NUMBER", "TYPE" => "int"),
			"ITEMS_SUM" => array("FIELD" => "A.ITEMS_SUM", "TYPE" => "double"),
			"LAST_CALCULATE" => array("FIELD" => "A.LAST_CALCULATE", "TYPE" => "datetime"),
			"AFF_SITE" => array("FIELD" => "A.AFF_SITE", "TYPE" => "string"),
			"AFF_DESCRIPTION" => array("FIELD" => "A.AFF_DESCRIPTION", "TYPE" => "string"),
			"FIX_PLAN" => array("FIELD" => "A.FIX_PLAN", "TYPE" => "char"),

			"PLAN_SITE_ID" => array("FIELD" => "AP.SITE_ID", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_affiliate_plan AP ON (A.PLAN_ID = AP.ID)"),
			"PLAN_NAME" => array("FIELD" => "AP.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_affiliate_plan AP ON (A.PLAN_ID = AP.ID)"),
			"PLAN_DESCRIPTION" => array("FIELD" => "AP.DESCRIPTION", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_affiliate_plan AP ON (A.PLAN_ID = AP.ID)"),
			"PLAN_TIMESTAMP_X" => array("FIELD" => "AP.TIMESTAMP_X", "TYPE" => "datetime", "FROM" => "INNER JOIN b_sale_affiliate_plan AP ON (A.PLAN_ID = AP.ID)"),
			"PLAN_ACTIVE" => array("FIELD" => "AP.ACTIVE", "TYPE" => "char", "FROM" => "INNER JOIN b_sale_affiliate_plan AP ON (A.PLAN_ID = AP.ID)"),
			"PLAN_BASE_RATE" => array("FIELD" => "AP.BASE_RATE", "TYPE" => "double", "FROM" => "INNER JOIN b_sale_affiliate_plan AP ON (A.PLAN_ID = AP.ID)"),
			"PLAN_BASE_RATE_TYPE" => array("FIELD" => "AP.BASE_RATE_TYPE", "TYPE" => "char", "FROM" => "INNER JOIN b_sale_affiliate_plan AP ON (A.PLAN_ID = AP.ID)"),
			"PLAN_BASE_RATE_CURRENCY" => array("FIELD" => "AP.BASE_RATE_CURRENCY", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_affiliate_plan AP ON (A.PLAN_ID = AP.ID)"),
			"PLAN_MIN_PAY" => array("FIELD" => "AP.MIN_PAY", "TYPE" => "double", "FROM" => "INNER JOIN b_sale_affiliate_plan AP ON (A.PLAN_ID = AP.ID)"),
			"PLAN_MIN_PLAN_VALUE" => array("FIELD" => "AP.MIN_PLAN_VALUE", "TYPE" => "double", "FROM" => "INNER JOIN b_sale_affiliate_plan AP ON (A.PLAN_ID = AP.ID)"),

			"USER_LOGIN" => array("FIELD" => "U.LOGIN", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (A.USER_ID = U.ID)"),
			"USER_NAME" => array("FIELD" => "U.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (A.USER_ID = U.ID)"),
			"USER_LAST_NAME" => array("FIELD" => "U.LAST_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (A.USER_ID = U.ID)"),
			"USER_EMAIL" => array("FIELD" => "U.EMAIL", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (A.USER_ID = U.ID)"),
			"USER_USER" => array("FIELD" => "U.LOGIN,U.NAME,U.LAST_NAME,U.EMAIL,U.ID", "WHERE_ONLY" => "Y", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (A.USER_ID = U.ID)"),

			"ORDER_ID" => array("FIELD" => "O.ID", "TYPE" => "int", "FROM" => "INNER JOIN b_sale_order O ON (A.ID = O.AFFILIATE_ID)"),
			"ORDER_DATE_ALLOW_DELIVERY" => array("FIELD" => "O.DATE_ALLOW_DELIVERY", "TYPE" => "datetime", "FROM" => "INNER JOIN b_sale_order O ON (A.ID = O.AFFILIATE_ID)"),
			"ORDER_ALLOW_DELIVERY" => array("FIELD" => "O.ALLOW_DELIVERY", "TYPE" => "char", "FROM" => "INNER JOIN b_sale_order O ON (A.ID = O.AFFILIATE_ID)"),
		);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_affiliate A ".
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
			"FROM b_sale_affiliate A ".
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
				"FROM b_sale_affiliate A ".
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

		if (!CSaleAffiliate::CheckFields("ADD", $arFields, 0))
			return false;

		$db_events = GetModuleEvents("sale", "OnBeforeBAffiliateAdd");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, Array(&$arFields))===false)
				return false;

		$arInsert = $DB->PrepareInsert("b_sale_affiliate", $arFields);

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
			"INSERT INTO b_sale_affiliate(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		$events = GetModuleEvents("sale", "OnAfterBAffiliateAdd");
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

		if (!CSaleAffiliate::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$db_events = GetModuleEvents("sale", "OnBeforeAffiliateUpdate");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, Array($ID, &$arFields))===false)
				return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_affiliate", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate) > 0)
				$strUpdate = ", ".$strUpdate;
			$strUpdate = $key."=".$value.$strUpdate;
		}

		$strSql = "UPDATE b_sale_affiliate SET ".$strUpdate." WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		unset($GLOBALS["SALE_AFFILIATE"]["SALE_AFFILIATE_CACHE_".$ID]);

		$events = GetModuleEvents("sale", "OnAfterAffiliateUpdate");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, Array($ID, $arFields));

		return $ID;
	}
}
?>