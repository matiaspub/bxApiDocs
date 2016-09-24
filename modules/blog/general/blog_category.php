<?
IncludeModuleLangFile(__FILE__);
$GLOBALS["BLOG_CATEGORY"] = Array();

class CAllBlogCategory
{
	/*************** ADD, UPDATE, DELETE *****************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GCT_EMPTY_NAME"), "EMPTY_NAME");
			return false;
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
		
		if(is_set($arFields, "NAME"))
		{
			if(intval($arFields["BLOG_ID"])>0)
			{
				$blogID = $arFields["BLOG_ID"];
			}
			elseif(IntVal($ID)>0)
			{
				$arCat = CBlogCategory::GetByID($ID);
				$blogID = $arCat["BLOG_ID"];
			}
			else
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GCT_EMPTY_BLOG_ID"), "EMPTY_BLOG_ID");
				return false;
			}

			if(strlen($arFields["NAME"]) > 255)
			{
				$arFields["NAME"] = substr($arFields["NAME"], 0, 255);
			}
			$dbCategory = CBlogCategory::GetList(array(), array("BLOG_ID" => $blogID, "NAME" => $arFields["NAME"]));
			while($arCategory = $dbCategory->Fetch())
			{
				if ($ID != $arCategory["ID"])
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_ALREADY_EXIST"), "ALREADY_EXIST");
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

		$DB->Query("UPDATE b_blog_post SET CATEGORY_ID = null WHERE CATEGORY_ID = ".$ID."", true);

		unset($GLOBALS["BLOG_CATEGORY"]["BLOG_CATEGORY_CACHE_".$ID]);

		return $DB->Query("DELETE FROM b_blog_category WHERE ID = ".$ID."", true);
	}

	//*************** SELECT *********************/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		if (isset($GLOBALS["BLOG_CATEGORY"]["BLOG_CATEGORY_CACHE_".$ID]) && is_array($GLOBALS["BLOG_CATEGORY"]["BLOG_CATEGORY_CACHE_".$ID]) && is_set($GLOBALS["BLOG_CATEGORY"]["BLOG_CATEGORY_CACHE_".$ID], "ID"))
		{
			return $GLOBALS["BLOG_CATEGORY"]["BLOG_CATEGORY_CACHE_".$ID];
		}
		else
		{
			$strSql =
				"SELECT C.ID, C.BLOG_ID, C.NAME ".
				"FROM b_blog_category C ".
				"WHERE C.ID = ".$ID."";
			$dbResult = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arResult = $dbResult->Fetch())
			{
				$GLOBALS["BLOG_CATEGORY"]["BLOG_CATEGORY_CACHE_".$ID] = $arResult;
				return $arResult;
			}
		}

		return False;
	}

}
?>