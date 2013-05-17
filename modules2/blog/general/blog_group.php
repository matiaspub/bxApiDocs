<?
IncludeModuleLangFile(__FILE__);
$GLOBALS["BLOG_GROUP"] = Array();

class CAllBlogGroup
{
	/*************** ADD, UPDATE, DELETE *****************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GG_EMPTY_NAME"), "EMPTY_NAME");
			return false;
		}
		elseif (is_set($arFields, "NAME"))
		{
			$dbResult = CBlogGroup::GetList(array(), array("NAME" => $arFields["NAME"], "!ID" => $ID, "SITE_ID" => $arFields["SITE_ID"]), false, false, array("ID"));
			if ($dbResult->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GG_DUBLICATE_NAME"), "DUBLICATE_NAME");
				return false;
			}
		}

		if ((is_set($arFields, "SITE_ID") || $ACTION=="ADD") && strlen($arFields["SITE_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GG_EMPTY_SITE_ID"), "EMPTY_SITE_ID");
			return false;
		}
		elseif (is_set($arFields, "SITE_ID"))
		{
			$dbResult = CSite::GetByID($arFields["SITE_ID"]);
			if (!$dbResult->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["SITE_ID"], GetMessage("BLG_GG_ERROR_NO_SITE")), "ERROR_NO_SITE");
				return false;
			}
		}

		return True;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		$dbResult = CBlog::GetList(array(), array("GROUP_ID" => $ID), false, false, array("ID"));
		if ($dbResult->Fetch())
		{
			$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $ID, GetMessage("BLG_GG_ERROR_NOT_EMPTY")), "ERROR_NOT_EMPTY");
			return False;
		}

		unset($GLOBALS["BLOG_GROUP"]["BLOG_GROUP_CACHE_".$ID]);

		return $DB->Query("DELETE FROM b_blog_group WHERE ID = ".$ID."", true);
	}

	//*************** SELECT *********************/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if($ID <= 0)
			return false;

		if (isset($GLOBALS["BLOG_GROUP"]["BLOG_GROUP_CACHE_".$ID]) && is_array($GLOBALS["BLOG_GROUP"]["BLOG_GROUP_CACHE_".$ID]) && is_set($GLOBALS["BLOG_GROUP"]["BLOG_GROUP_CACHE_".$ID], "ID"))
		{
			return $GLOBALS["BLOG_GROUP"]["BLOG_GROUP_CACHE_".$ID];
		}
		else
		{
			$strSql =
				"SELECT G.ID, G.NAME, G.SITE_ID ".
				"FROM b_blog_group G ".
				"WHERE G.ID = ".$ID."";
			$dbResult = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arResult = $dbResult->Fetch())
			{
				$GLOBALS["BLOG_GROUP"]["BLOG_GROUP_CACHE_".$ID] = $arResult;
				return $arResult;
			}
		}

		return False;
	}

}
?>