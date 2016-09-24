<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/store_barcode.php");

class CSaleStoreBarcode extends CAllSaleStoreBarcode
{
	static public function Add($arFields)
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

		if (!CSaleStoreBarcode::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_sale_store_barcode", $arFields);

		if (!array_key_exists("DATE_CREATE", $arFields))
		{
			$arInsert[0] .= ", DATE_CREATE";
			$arInsert[1] .= ", ".$DB->GetNowFunction();
		}

		if (!array_key_exists("DATE_MODIFY", $arFields))
		{
			$arInsert[0] .= ", DATE_MODIFY";
			$arInsert[1] .= ", ".$DB->GetNowFunction();
		}

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
			"INSERT INTO b_sale_store_barcode(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		return $ID;
	}

	public static function Update($ID, $arFields, $bDateUpdate = true)
	{
		global $DB;

		$ID = IntVal($ID);

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1)=="=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CSaleStoreBarcode::CheckFields("UPDATE", $arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_store_barcode", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate)>0) $strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		$strSql =
			"UPDATE b_sale_store_barcode SET ".
			"	".$strUpdate." ";
		if($bDateUpdate)
			$strSql .=	",	DATE_MODIFY = ".$DB->GetNowFunction()." ";
		$strSql .=	"WHERE ID = ".$ID." ";
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}

	public static function GetList($arOrder = Array("ID"=>"DESC"), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (array_key_exists("DATE_FROM", $arFilter))
		{
			$val = $arFilter["DATE_FROM"];
			unset($arFilter["DATE_FROM"]);
			$arFilter[">=DATE_CREATE"] = $val;
		}
		if (array_key_exists("DATE_TO", $arFilter))
		{
			$val = $arFilter["DATE_TO"];
			unset($arFilter["DATE_TO"]);
			$arFilter["<=DATE_CREATE"] = $val;
		}

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "BASKET_ID", "BARCODE", "STORE_ID", "QUANTITY", "DATE_CREATE", "DATE_MODIFY", "CREATED_BY", "MODIFIED_BY");
		elseif (in_array("*", $arSelectFields))
			$arSelectFields = array("ID", "BASKET_ID", "BARCODE", "STORE_ID", "QUANTITY", "DATE_CREATE", "DATE_MODIFY", "CREATED_BY", "MODIFIED_BY");

		// FIELDS -->
		$arFields = array(
			"ID" => array("FIELD" => "SB.ID", "TYPE" => "int"),
			"BASKET_ID" => array("FIELD" => "SB.BASKET_ID", "TYPE" => "int"),
			"BARCODE" => array("FIELD" => "SB.BARCODE", "TYPE" => "string"),
			"STORE_ID" => array("FIELD" => "SB.STORE_ID", "TYPE" => "int"),
			"QUANTITY" => array("FIELD" => "SB.QUANTITY", "TYPE" => "double"),
			"DATE_CREATE" => array("FIELD" => "SB.DATE_CREATE", "TYPE" => "datetime"),
			"CREATED_BY" => array("FIELD" => "SB.CREATED_BY", "TYPE" => "int"),
			"DATE_MODIFY" => array("FIELD" => "SB.DATE_MODIFY", "TYPE" => "datetime"),
			"MODIFIED_BY" => array("FIELD" => "SB.MODIFIED_BY", "TYPE" => "int"),			
		);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_store_barcode SB ".
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
			"FROM b_sale_store_barcode SB ".
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
				"FROM b_sale_store_barcode SB ".
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
}