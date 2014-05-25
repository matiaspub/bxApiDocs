<?
IncludeModuleLangFile(__FILE__);
$GLOBALS["BLOG_USER_GROUP"] = Array();

class CAllBlogUserGroup
{
	/*************** ADD, UPDATE, DELETE *****************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GUG_EMPTY_NAME"), "EMPTY_NAME");
			return false;
		}

		if ((is_set($arFields, "BLOG_ID") || $ACTION=="ADD") && IntVal($arFields["BLOG_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GUG_EMPTY_BLOG_ID"), "EMPTY_BLOG_ID");
			return false;
		}
		elseif (is_set($arFields, "BLOG_ID"))
		{
			$arResult = CBlog::GetByID($arFields["BLOG_ID"]);
			if (!$arResult)
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["BLOG_ID"], GetMessage("BLG_GUG_ERROR_NO_BLOG")), "ERROR_NO_BLOG");
				return false;
			}
		}

		return True;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID == 1 || $ID == 2)
			return False;

		$arGroup = CBlogUserGroup::GetByID($ID);
		if ($arGroup)
		{
			$dbResult = CBlogUserGroupPerms::GetList(
				array(),
				array("USER_GROUP_ID" => $ID, "BLOG_ID" => $arGroup["BLOG_ID"]),
				false,
				false,
				array("ID")
			);
			if ($arResult = $dbResult->Fetch())
			{
				if (!CBlogUserGroupPerms::Delete($arResult["ID"]))
					return False;
			}

			$DB->Query("DELETE FROM b_blog_user2user_group WHERE USER_GROUP_ID = ".$ID."", true);

			unset($GLOBALS["BLOG_USER_GROUP"]["BLOG_USER_GROUP_CACHE_".$ID]);

			return $DB->Query("DELETE FROM b_blog_user_group WHERE ID = ".$ID."", true);
		}

		return True;
	}

	public static function SetGroupPerms($ID, $blogID, $postID = 0, $permission = BLOG_PERMS_DENY, $permsType = BLOG_PERMS_POST)
	{
		global $DB;

		$ID = IntVal($ID);
		$blogID = IntVal($blogID);
		$postID = IntVal($postID);

		$arAvailPerms = array_keys($GLOBALS["AR_BLOG_PERMS"]);
		if (!in_array($permission, $arAvailPerms))
			$permission = $arAvailPerms[0];

		$permsType = (($permsType == BLOG_PERMS_COMMENT) ? BLOG_PERMS_COMMENT : BLOG_PERMS_POST);

		$bSuccess = True;

		$arUserGroup = CBlogUserGroup::GetByID($ID);
		if (!$arUserGroup)
		{
			$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $ID, GetMessage("BLG_GUG_ERROR_NO_USER_GROUP")), "ERROR_NO_USER_GROUP");
			$bSuccess = False;
		}

		if ($bSuccess)
		{
			$arBlog = CBlog::GetByID($blogID);
			if (!$arBlog)
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $blogID, GetMessage("BLG_GUG_ERROR_NO_BLOG")), "ERROR_NO_BLOG");
				$bSuccess = False;
			}
		}

		if ($bSuccess)
		{
			if ($postID > 0)
			{
				$arPost = CBlogPost::GetByID($postID);
				if (!$arPost)
				{
					$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $postID, GetMessage("BLG_GUG_ERROR_NO_POST")), "ERROR_NO_POST");
					$bSuccess = False;
				}
			}
		}

		if ($bSuccess)
		{
			$oldGroupPerms = CBlogUserGroup::GetGroupPerms(1, $blogID, 0, BLOG_PERMS_POST);

			$currentPerms = CBlogUserGroup::GetGroupPerms($ID, $blogID, $postID, $permsType);
			if ($currentPerms)
			{
				if ($currentPerms != $permission)
				{
					if ($postID > 0)
						$DB->Query(
							"UPDATE b_blog_user_group_perms SET ".
							"	PERMS = '".$DB->ForSql($permission)."' ".
							"WHERE BLOG_ID = ".$blogID." ".
							"	AND POST_ID = ".$postID." ".
							"	AND USER_GROUP_ID = ".$ID."".
							"	AND PERMS_TYPE = '".$DB->ForSql($permsType)."'"
						);
					else
						$DB->Query(
							"UPDATE b_blog_user_group_perms SET ".
							"	PERMS = '".$DB->ForSql($permission)."' ".
							"WHERE BLOG_ID = ".$blogID." ".
							"	AND USER_GROUP_ID = ".$ID." ".
							"	AND PERMS_TYPE = '".$DB->ForSql($permsType)."'".
							"	AND POST_ID IS NULL "
						);
				}
			}
			else
			{
				if ($postID > 0)
					$DB->Query(
						"INSERT INTO b_blog_user_group_perms (BLOG_ID, USER_GROUP_ID, PERMS_TYPE, POST_ID, PERMS) ".
						"VALUES (".$blogID.", ".$ID.", '".$DB->ForSql($permsType)."', ".$postID.", '".$DB->ForSql($permission)."') "
					);
				else
					$DB->Query(
						"INSERT INTO b_blog_user_group_perms (BLOG_ID, USER_GROUP_ID, PERMS_TYPE, POST_ID, PERMS) ".
						"VALUES (".$blogID.", ".$ID.", '".$DB->ForSql($permsType)."', null, '".$DB->ForSql($permission)."') "
					);
			}

			unset($GLOBALS["BLOG_USER_GROUP"]["BLOG_GROUP_PERMS_CACHE_".$blogID."_".$postID."_".$permsType."_".$ID]);
			unset($GLOBALS["BLOG_USER_GROUP"]["BLOG_USER_PERMS_CACHE_".$blogID."_".$postID."_".$permsType]);
		}

		if ($bSuccess)
		{
			if (CModule::IncludeModule("search"))
			{
				$newGroupPerms = CBlogUserGroup::GetGroupPerms(1, $blogID, 0, BLOG_PERMS_POST);
				if ($oldGroupPerms >= BLOG_PERMS_READ && $newGroupPerms < BLOG_PERMS_READ)
				{
					CSearch::DeleteIndex("blog", false, $blogID);
				}
				elseif ($oldGroupPerms < BLOG_PERMS_READ && $newGroupPerms >= BLOG_PERMS_READ)
				{
				}
			}
		}

		return $bSuccess;
	}

	//*************** SELECT *********************/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		if (isset($GLOBALS["BLOG_USER_GROUP"]["BLOG_USER_GROUP_CACHE_".$ID]) && is_array($GLOBALS["BLOG_USER_GROUP"]["BLOG_USER_GROUP_CACHE_".$ID]) && is_set($GLOBALS["BLOG_USER_GROUP"]["BLOG_USER_GROUP_CACHE_".$ID], "ID"))
		{
			return $GLOBALS["BLOG_USER_GROUP"]["BLOG_USER_GROUP_CACHE_".$ID];
		}
		else
		{
			$strSql =
				"SELECT G.ID, G.BLOG_ID, G.NAME ".
				"FROM b_blog_user_group G ".
				"WHERE G.ID = ".$ID."";
			$dbResult = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arResult = $dbResult->Fetch())
			{
				$GLOBALS["BLOG_USER_GROUP"]["BLOG_USER_GROUP_CACHE_".$ID] = $arResult;
				return $arResult;
			}
		}

		return False;
	}

	public static function GetGroupPerms($ID, $blogID, $postID = 0, $permsType = BLOG_PERMS_POST)
	{
		global $DB;

		$ID = IntVal($ID);
		$blogID = IntVal($blogID);
		$postID = IntVal($postID);
		$permsType = (($permsType == BLOG_PERMS_COMMENT) ? BLOG_PERMS_COMMENT : BLOG_PERMS_POST);

		$varName = "BLOG_GROUP_PERMS_CACHE_".$blogID."_".$postID."_".$permsType."_";

		if (isset($GLOBALS["BLOG_USER_GROUP"][$varName.$ID]) && is_array($GLOBALS["BLOG_USER_GROUP"][$varName.$ID]))
		{
			return $GLOBALS["BLOG_USER_GROUP"][$varName.$ID];
		}
		else
		{
			if ($postID > 0)
			{
				$strSql =
					"SELECT P.PERMS ".
					"FROM b_blog_user_group_perms P ".
					"WHERE P.BLOG_ID = ".$blogID." ".
					"	AND P.POST_ID = ".$postID." ".
					"	AND P.USER_GROUP_ID = ".$ID." ".
					"	AND P.PERMS_TYPE = '".$DB->ForSql($permsType)."' ";
				$dbResult = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
				if ($arResult = $dbResult->Fetch())
				{
					$GLOBALS["BLOG_USER_GROUP"][$varName.$ID] = $arResult["PERMS"];
					return $arResult["PERMS"];
				}
			}

			$strSql =
				"SELECT P.PERMS ".
				"FROM b_blog_user_group_perms P ".
				"WHERE P.BLOG_ID = ".$blogID." ".
				"	AND P.POST_ID IS NULL ".
				"	AND P.USER_GROUP_ID = ".$ID." ".
				"	AND P.PERMS_TYPE = '".$DB->ForSql($permsType)."' ";
			$dbResult = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arResult = $dbResult->Fetch())
			{
				$GLOBALS["BLOG_USER_GROUP"][$varName.$ID] = $arResult["PERMS"];
				return $arResult["PERMS"];
			}
		}

		return False;
	}
}
?>