<?
class CFormCrm extends CAllFormCrm
{
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		$TABLE = 'b_form_crm C';

		$arFields = array(
			"ID" => array("FIELD" => "C.ID", "TYPE" => "int"),
			"NAME" => array("FIELD" => "C.NAME", "TYPE" => "string"),
			"ACTIVE" => array("FIELD" => "C.ACTIVE", "TYPE" => "string"),
			"URL" => array("FIELD" => "C.URL", "TYPE" => "string"),
			"AUTH_HASH" => array("FIELD" => "C.AUTH_HASH", "TYPE" => "string"),
		);

		if (count($arSelectFields) <= 0)
		{
			foreach ($arFields as $k => $v)
			{
				if (!isset($v['FROM']))
					$arSelectFields[] = $k;
			}
		}
		elseif(in_array("*", $arSelectFields))
		{
			$arSelectFields = array_keys($arFields);
		}

		$arSqls = self::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		$strSql = "
SELECT
	".$arSqls["SELECT"]." "."
FROM
	".$TABLE."
	".$arSqls["FROM"]." "."
";

		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";

		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$arRes = $dbRes->Fetch();
			if ($arRes)
				return $arRes["CNT"];
			else
				return false;
		}

		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";
		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])<=0)
		{
			$strSql_tmp = "
SELECT COUNT(DISTINCT M.ID) as CNT
FROM
	".$TABLE."
	".$arSqls["FROM"]."
";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";

			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (strlen($arSqls["GROUPBY"]) <= 0)
			{
				$arRes = $dbRes->Fetch();
				if ($arRes)
					$cnt = $arRes["CNT"];
			}
			else
			{
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();
			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) > 0)
				$strSql .= "LIMIT ".intval($arNavStartParams["nTopCount"]);


			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}
}

?>