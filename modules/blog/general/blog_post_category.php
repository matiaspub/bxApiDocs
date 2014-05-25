<?
IncludeModuleLangFile(__FILE__);

class CAllBlogPostCategory
{
	/*************** ADD, UPDATE, DELETE *****************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		if ((is_set($arFields, "POST_ID") || $ACTION=="ADD") && strlen($arFields["POST_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GCT_EMPTY_POST_ID"), "EMPTY_POST_ID");
			return false;
		}
		elseif (is_set($arFields, "POST_ID"))
		{
			$arResult = CBlogPost::GetByID($arFields["POST_ID"]);
			if (!$arResult)
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["POST_ID"], GetMessage("BLG_GCT_ERROR_NO_POST")), "ERROR_NO_POST");
				return false;
			}
		}

		if ((is_set($arFields, "BLOG_ID") || $ACTION=="ADD") && IntVal($arFields["BLOG_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GCT_EMPTY_BLOG_ID"), "EMPTY_BLOG_ID");
			return false;
		}
		elseif (is_set($arFields, "BLOG_ID"))
		{
			$arResult = CBlog::GetByID($arFields["BLOG_ID"]);
			if (!$arResult)
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["BLOG_ID"], GetMessage("BLG_GCT_ERROR_NO_BLOG")), "ERROR_NO_BLOG");
				return false;
			}
		}

		if ((is_set($arFields, "CATEGORY_ID") || $ACTION=="ADD") && IntVal($arFields["CATEGORY_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GCT_EMPTY_CATEGORY_ID"), "EMPTY_CATEGORY_ID");
			return false;
		}
		elseif (is_set($arFields, "CATEGORY_ID"))
		{
			$arResult = CBlogCategory::GetByID($arFields["CATEGORY_ID"]);
			if (!$arResult)
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["CATEGORY_ID"], GetMessage("BLG_GCT_ERROR_NO_CATEGORY")), "ERROR_NO_CATEGORY");
				return false;
			}
		}

		return True;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		return $DB->Query("DELETE FROM b_blog_post_category WHERE ID = ".$ID."", true);
	}

	//*************** SELECT *********************/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		$strSql =
			"SELECT C.ID, C.BLOG_ID, C.POST_ID, C.CATEGORY_ID ".
			"FROM b_blog_post_category C ".
			"WHERE C.ID = ".$ID."";
		$dbResult = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arResult = $dbResult->Fetch())
		{
			return $arResult;
		}
		return False;
	}
	
	public static function DeleteByPostID($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		return $DB->Query("DELETE FROM b_blog_post_category WHERE POST_ID = ".$ID."", true);
	}
}
?>