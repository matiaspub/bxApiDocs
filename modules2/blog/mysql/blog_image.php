<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/blog/general/blog_image.php");

class CBlogImage extends CAllBlogImage
{
	/*************** ADD, UPDATE, DELETE *****************/
	public static function Add($arFields)
	{
		global $DB;

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1) == "=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CBlogImage::CheckFields("ADD", $arFields))
			return false;

		if (is_array($arFields['FILE_ID']))
		{
			if (
				array_key_exists("FILE_ID", $arFields)
				&& is_array($arFields["FILE_ID"])
				&& (
					!array_key_exists("MODULE_ID", $arFields["FILE_ID"])
					|| strlen($arFields["FILE_ID"]["MODULE_ID"]) <= 0
				)
			)
				$arFields["FILE_ID"]["MODULE_ID"] = "blog";

			$prefix = "blog";
			if(strlen($arFields["URL"]) > 0)
				$prefix .= "/".$arFields["URL"];

			CFile::SaveForDB($arFields, "FILE_ID", $prefix);
		}

		if (
			isset($arFields['FILE_ID']) &&
			( intval($arFields['FILE_ID']) == $arFields['FILE_ID'] )
		)
		{
			$arInsert = $DB->PrepareInsert("b_blog_image", $arFields);

			foreach ($arFields1 as $key => $value)
			{
				if (strlen($arInsert[0]) > 0)
					$arInsert[0] .= ", ";
				$arInsert[0] .= $key;
				if (strlen($arInsert[1]) > 0)
					$arInsert[1] .= ", ";
				$arInsert[1] .= $value;
			}

			if (strlen($arInsert[0]) > 0)
			{
				$strSql =
					"INSERT INTO b_blog_image(".$arInsert[0].") ".
					"VALUES(".$arInsert[1].")";
				$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

				$ID = IntVal($DB->LastID());

				return $ID;
			}
		}
		else
		{
				$GLOBALS["APPLICATION"]->ThrowException("Error Adding file by CFile::SaveForDB");
		}

		return False;
	}


	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = IntVal($ID);
		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1) == "=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}
		if (!CBlogImage::CheckFields("UPDATE", $arFields, $ID))
			return false;
		$strUpdate = $DB->PrepareUpdate("b_blog_image", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate) > 0)
				$strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}
		if (strlen($strUpdate) > 0)
		{
			$strSql =
				"UPDATE b_blog_image SET ".
				"	".$strUpdate." ".
				"WHERE ID = ".$ID." ";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			unset($GLOBALS["BLOG_IMAGE"]["BLOG_IMAGE_CACHE_".$ID]);

			return $ID;
		}

		return False;
	}

	//*************** SELECT *********************/
	public static function GetList($arOrder = Array("ID" => "DESC"), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "FILE_ID", "POST_ID", "BLOG_ID", "USER_ID", "TITLE", "TIMESTAMP_X", "IMAGE_SIZE");

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "G.ID", "TYPE" => "int"),
				"FILE_ID" => array("FIELD" => "G.FILE_ID", "TYPE" => "int"),
				"POST_ID" => array("FIELD" => "G.POST_ID", "TYPE" => "int"),
				"BLOG_ID" => array("FIELD" => "G.BLOG_ID", "TYPE" => "int"),
				"USER_ID" => array("FIELD" => "G.USER_ID", "TYPE" => "int"),
				"TITLE" => array("FIELD" => "G.TITLE", "TYPE" => "string"),
				"TIMESTAMP_X" => array("FIELD" => "G.TIMESTAMP_X", "TYPE" => "datetime"),
				"IMAGE_SIZE" => array("FIELD" => "G.IMAGE_SIZE", "TYPE" => "int"),
				"IS_COMMENT" => array("FIELD" => "G.IS_COMMENT", "TYPE" => "string"),
				"COMMENT_ID" => array("FIELD" => "G.COMMENT_ID", "TYPE" => "int"),
			);
		// <-- FIELDS

		$arSqls = CBlog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_blog_image G ".
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
			"FROM b_blog_image G ".
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
				"FROM b_blog_image G ".
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
