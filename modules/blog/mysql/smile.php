<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/blog/general/smile.php");

class CBlogSmile extends CAllBlogSmile
{
	public static function Add($arFields)
	{
		global $DB, $CACHE_MANAGER;

		if (!CBlogSmile::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_blog_smile", $arFields);

		$strSql =
			"INSERT INTO b_blog_smile(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ID = IntVal($DB->LastID());

		for ($i = 0; $i<count($arFields["LANG"]); $i++)
		{
			$arInsert = $DB->PrepareInsert("b_blog_smile_lang", $arFields["LANG"][$i]);
			$strSql =
				"INSERT INTO b_blog_smile_lang(SMILE_ID, ".$arInsert[0].") ".
				"VALUES(".$ID.", ".$arInsert[1].")";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		$CACHE_MANAGER->Clean("b_blog_smile");
		BXClearCache(true, "/blog/smiles/");

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $DB, $CACHE_MANAGER;
		$ID = IntVal($ID);
		if ($ID<=0) return False;

		if (!CBlogSmile::CheckFields("UPDATE", $arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_blog_smile", $arFields);
		$strSql = "UPDATE b_blog_smile SET ".$strUpdate." WHERE ID = ".$ID;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if (is_set($arFields, "LANG"))
		{
			$DB->Query("DELETE FROM b_blog_smile_lang WHERE SMILE_ID = ".$ID."");

			for ($i = 0; $i<count($arFields["LANG"]); $i++)
			{
				$arInsert = $DB->PrepareInsert("b_blog_smile_lang", $arFields["LANG"][$i]);
				$strSql =
					"INSERT INTO b_blog_smile_lang(SMILE_ID, ".$arInsert[0].") ".
					"VALUES(".$ID.", ".$arInsert[1].")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
		$CACHE_MANAGER->Clean("b_blog_smile");
		BXClearCache(true, "/blog/smiles/");

		return $ID;
	}

	public static function GetList($arOrder = Array("ID" => "DESC"), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "SMILE_TYPE", "TYPING", "IMAGE", "DESCRIPTION", "CLICKABLE", "SORT", "IMAGE_WIDTH", "IMAGE_HEIGHT");

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "B.ID", "TYPE" => "int"),
				"SMILE_TYPE" => array("FIELD" => "B.SMILE_TYPE", "TYPE" => "char"),
				"TYPING" => array("FIELD" => "B.TYPING", "TYPE" => "string"),
				"IMAGE" => array("FIELD" => "B.IMAGE", "TYPE" => "string"),
				"DESCRIPTION" => array("FIELD" => "B.DESCRIPTION", "TYPE" => "string"),
				"CLICKABLE" => array("FIELD" => "B.CLICKABLE", "TYPE" => "char"),
				"SORT" => array("FIELD" => "B.SORT", "TYPE" => "int"),
				"IMAGE_WIDTH" => array("FIELD" => "B.IMAGE_WIDTH", "TYPE" => "int"),
				"IMAGE_HEIGHT" => array("FIELD" => "B.IMAGE_HEIGHT", "TYPE" => "int"),

				"LANG_ID" => array("FIELD" => "BL.ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_blog_smile_lang BL ON (B.ID = BL.SMILE_ID".((isset($arFilter["LANG_LID"]) && strlen($arFilter["LANG_LID"]) > 0) ? " AND BL.LID = '".$arFilter["LANG_LID"]."'" : "").")"),
				"LANG_SMILE_ID" => array("FIELD" => "BL.SMILE_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_blog_smile_lang BL ON (B.ID = BL.SMILE_ID".((isset($arFilter["LANG_LID"]) && strlen($arFilter["LANG_LID"]) > 0) ? " AND BL.LID = '".$arFilter["LANG_LID"]."'" : "").")"),
				"LANG_LID" => array("FIELD" => "BL.LID", "TYPE" => "string", "FROM" => "LEFT JOIN b_blog_smile_lang BL ON (B.ID = BL.SMILE_ID".((isset($arFilter["LANG_LID"]) && strlen($arFilter["LANG_LID"]) > 0) ? " AND BL.LID = '".$arFilter["LANG_LID"]."'" : "").")"),
				"LANG_NAME" => array("FIELD" => "BL.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_blog_smile_lang BL ON (B.ID = BL.SMILE_ID".((isset($arFilter["LANG_LID"]) && strlen($arFilter["LANG_LID"]) > 0) ? " AND BL.LID = '".$arFilter["LANG_LID"]."'" : "").")"),
			);
		// <-- FIELDS

		$arSqls = CBlog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_blog_smile B ".
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
			"FROM b_blog_smile B ".
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
				"FROM b_blog_smile B ".
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
				// ТОЛЬКО ДЛЯ MYSQL!!! ДЛЯ ORACLE ДРУГОЙ КОД
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			//echo "!2.2!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) > 0)
				$strSql .= "LIMIT ".IntVal($arNavStartParams["nTopCount"]);

			//echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}
}
?>