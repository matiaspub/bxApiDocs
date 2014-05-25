<?
IncludeModuleLangFile(__FILE__);
$GLOBALS["BLOG_CANDIDATE"] = Array();

class CAllBlogCandidate
{
	/*************** ADD, UPDATE, DELETE *****************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		if ((is_set($arFields, "BLOG_ID") || $ACTION=="ADD") && IntVal($arFields["BLOG_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GC_EMPTY_BLOG_ID"), "EMPTY_BLOG_ID");
			return false;
		}
		elseif (is_set($arFields, "BLOG_ID"))
		{
			$arResult = CBlog::GetByID($arFields["BLOG_ID"]);
			if (!$arResult)
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["BLOG_ID"], GetMessage("BLG_GB_ERROR_NO_BLOG")), "ERROR_NO_BLOG");
				return false;
			}
		}

		if ((is_set($arFields, "USER_ID") || $ACTION=="ADD") && IntVal($arFields["USER_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GB_EMPTY_USER_ID"), "EMPTY_USER_ID");
			return false;
		}
		elseif (is_set($arFields, "USER_ID"))
		{
			$dbResult = CUser::GetByID($arFields["USER_ID"]);
			if (!$dbResult->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GB_ERROR_NO_USER_ID"), "ERROR_NO_USER_ID");
				return false;
			}
		}

		return True;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		unset($GLOBALS["BLOG_CANDIDATE"]["BLOG_CANDIDATE_CACHE_".$ID]);

		return $DB->Query("DELETE FROM b_blog_user2blog WHERE ID = ".$ID."", true);
	}

	//*************** SELECT *********************/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		if (isset($GLOBALS["BLOG_CANDIDATE"]["BLOG_CANDIDATE_CACHE_".$ID]) && is_array($GLOBALS["BLOG_CANDIDATE"]["BLOG_CANDIDATE_CACHE_".$ID]) && is_set($GLOBALS["BLOG_CANDIDATE"]["BLOG_CANDIDATE_CACHE_".$ID], "ID"))
		{
			return $GLOBALS["BLOG_CANDIDATE"]["BLOG_CANDIDATE_CACHE_".$ID];
		}
		else
		{
			$strSql =
				"SELECT U2B.ID, U2B.BLOG_ID, U2B.USER_ID ".
				"FROM b_blog_user2blog U2B ".
				"WHERE U2B.ID = ".$ID."";
			$dbResult = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arResult = $dbResult->Fetch())
			{
				$GLOBALS["BLOG_CANDIDATE"]["BLOG_CANDIDATE_CACHE_".$ID] = $arResult;
				return $arResult;
			}
		}

		return False;
	}

}
?>