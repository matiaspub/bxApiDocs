<?
IncludeModuleLangFile(__FILE__);

$GLOBALS["BLOG"] = Array();

class CAllBlog
{
	public static function IsBlogOwner($ID, $userID)
	{
		$ID = IntVal($ID);
		$userID = IntVal($userID);
		if ($userID <= 0)
			return False;

		$arBlog = CBlog::GetByID($ID);
		if ($arBlog)
		{
			if (IntVal($arBlog["OWNER_ID"]) == $userID && $arBlog["ACTIVE"] == "Y")
				return True;
		}
		return False;
	}

	public static function CanUserCreateBlog($userID = 0)
	{

		$userID = IntVal($userID);

		if ($userID > 0 && CBlogUser::IsLocked($userID))
			return False;
		
		$arGroups = false;
		if($userID > 0)
			$arGroups = CUser::GetUserGroup($userID);
			
		$blogModulePermissions = $GLOBALS["APPLICATION"]->GetGroupRight("blog", $arGroups);
		if ($blogModulePermissions >= "N")
			return True;

		return False;
//		return True;
	}

	public static function CanUserViewBlogs($arUserGroups = array())
	{
		/*
		$blogModulePermissions = $GLOBALS["APPLICATION"]->GetGroupRight("blog");
		if ($blogModulePermissions >= "K")
			return True;

		return False;
		*/
		return True;
	}

	public static function CanUserManageBlog($ID, $userID = 0)
	{
		$ID = IntVal($ID);
		$userID = IntVal($userID);

		if ($userID <= 0)
			return False;

		$blogModulePermissions = $GLOBALS["APPLICATION"]->GetGroupRight("blog");
		if ($blogModulePermissions >= "W")
			return True;

		if (CBlog::IsBlogOwner($ID, $userID))
			return True;

		return False;
	}

	public static function GetBlogUserPostPerms($ID, $userID = 0)
	{
		$ID = IntVal($ID);
		$userID = IntVal($userID);

		$arAvailPerms = array_keys($GLOBALS["AR_BLOG_PERMS"]);

		$blogModulePermissions = $GLOBALS["APPLICATION"]->GetGroupRight("blog");
		if ($blogModulePermissions >= "W")
			return $arAvailPerms[count($arAvailPerms) - 1];

		if (CBlog::IsBlogOwner($ID, $userID))
			return $arAvailPerms[count($arAvailPerms) - 1];

		$arBlogUser = CBlogUser::GetByID($userID, BLOG_BY_USER_ID);
		if ($arBlogUser && $arBlogUser["ALLOW_POST"] != "Y")
			return $arAvailPerms[0];

		$arUserGroups = CBlogUser::GetUserGroups($userID, $ID, "Y", BLOG_BY_USER_ID);

		$perms = CBlogUser::GetUserPerms($arUserGroups, $ID, 0, BLOG_PERMS_POST, BLOG_BY_USER_ID);
		if ($perms)
			return $perms;

		return $arAvailPerms[0];
	}

	public static function GetBlogUserCommentPerms($ID, $userID)
	{
		$ID = IntVal($ID);
		$userID = IntVal($userID);

		$arAvailPerms = array_keys($GLOBALS["AR_BLOG_PERMS"]);

		$blogModulePermissions = $GLOBALS["APPLICATION"]->GetGroupRight("blog");
		if ($blogModulePermissions >= "W")
			return $arAvailPerms[count($arAvailPerms) - 1];

		if (CBlog::IsBlogOwner($ID, $userID))
			return $arAvailPerms[count($arAvailPerms) - 1];

		$arBlog = CBlog::GetByID($ID);
		if ($arBlog["ENABLE_COMMENTS"] != "Y")
			return $arAvailPerms[0];

		$arBlogUser = CBlogUser::GetByID($userID, BLOG_BY_USER_ID);
		if ($arBlogUser && $arBlogUser["ALLOW_POST"] != "Y")
			return $arAvailPerms[0];

		$arUserGroups = CBlogUser::GetUserGroups($userID, $ID, "Y", BLOG_BY_USER_ID);

		$perms = CBlogUser::GetUserPerms($arUserGroups, $ID, 0, BLOG_PERMS_COMMENT, BLOG_BY_USER_ID);
		if ($perms)
			return $perms;

		return $arAvailPerms[0];
	}

	/*************** ADD, UPDATE, DELETE *****************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $DB;

		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GB_EMPTY_NAME"), "EMPTY_NAME");
			return false;
		}
		/*
		elseif (is_set($arFields, "NAME"))
		{
			$dbResult = CBlog::GetList(array(), array("NAME" => $arFields["NAME"], "!ID" => $ID), false, false, array("ID"));
			if ($dbResult->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GB_DUBLICATE_NAME"), "DUBLICATE_NAME");
				return false;
			}
		}
		*/

		if ((is_set($arFields, "URL") || $ACTION=="ADD") && strlen($arFields["URL"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GB_EMPTY_URL"), "EMPTY_URL");
			return false;
		}
		elseif (is_set($arFields, "URL"))
		{
			$urlCheck = preg_replace("/[^a-zA-Z0-9_-]/is", "", $arFields["URL"]);
			if ($urlCheck != $arFields["URL"])
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GB_BAD_URL"), "BAD_URL");
				return false;
			}

			$dbResult = CBlog::GetList(array(), array("URL" => $arFields["URL"], "!ID" => $ID), false, false, array("ID"));
			if ($dbResult->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GB_DUBLICATE_URL"), "DUBLICATE_URL");
				return false;
			}

			if (in_array(strtolower($arFields["URL"]), $GLOBALS["AR_BLOG_RESERVED_NAMES"]))
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#NAME#", $arFields["URL"], GetMessage("BLG_GB_RESERVED_NAME")), "RESERVED_NAME");
				return false;
			}
		}

		if (is_set($arFields, "DATE_CREATE") && (!$DB->IsDate($arFields["DATE_CREATE"], false, LANG, "FULL")))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GB_EMPTY_DATE_CREATE"), "EMPTY_DATE_CREATE");
			return false;
		}

		if (is_set($arFields, "DATE_UPDATE") && (!$DB->IsDate($arFields["DATE_UPDATE"], false, LANG, "FULL")))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GB_EMPTY_DATE_UPDATE"), "EMPTY_DATE_UPDATE");
			return false;
		}

		if (is_set($arFields, "LAST_POST_DATE") && (!$DB->IsDate($arFields["LAST_POST_DATE"], false, LANG, "FULL") && strlen($arFields["LAST_POST_DATE"]) > 0))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GB_EMPTY_LAST_POST_DATE"), "EMPTY_LAST_POST_DATE");
			return false;
		}

		if($ACTION=="ADD" && (IntVal($arFields["OWNER_ID"]) <= 0 && IntVal($arFields["SOCNET_GROUP_ID"]) <= 0))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GB_EMPTY_OWNER_ID"), "EMPTY_OWNER_ID");
			return false;
		}
		
		if (IntVal($arFields["OWNER_ID"]) > 0)
		{
			$dbResult = CUser::GetByID($arFields["OWNER_ID"]);
			if (!$dbResult->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GB_ERROR_NO_OWNER_ID"), "ERROR_NO_OWNER_ID");
				return false;
			}
		}
		
		if(is_set($arFields, "OWNER_ID") && is_set($arFields, "SOCNET_GROUP_ID") && IntVal($arFields["OWNER_ID"]) <= 0 && IntVal($arFields["SOCNET_GROUP_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GB_EMPTY_OWNER_ID"), "EMPTY_OWNER_ID");
			return false;
		}

		if ((is_set($arFields, "GROUP_ID") || $ACTION=="ADD") && IntVal($arFields["GROUP_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GB_EMPTY_GROUP_ID"), "EMPTY_GROUP_ID");
			return false;
		}
		elseif (is_set($arFields, "GROUP_ID"))
		{
			$dbResult = CBlogGroup::GetByID($arFields["GROUP_ID"]);
			if (!$dbResult)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("BLG_GB_ERROR_NO_GROUP_ID"), "ERROR_NO_GROUP_ID");
				return false;
			}
		}

		if ((is_set($arFields, "ACTIVE") || $ACTION=="ADD") && $arFields["ACTIVE"] != "Y" && $arFields["ACTIVE"] != "N")
			$arFields["ACTIVE"] = "Y";
		if ((is_set($arFields, "ENABLE_COMMENTS") || $ACTION=="ADD") && $arFields["ENABLE_COMMENTS"] != "Y" && $arFields["ENABLE_COMMENTS"] != "N")
			$arFields["ENABLE_COMMENTS"] = "Y";
		if ((is_set($arFields, "ENABLE_IMG_VERIF") || $ACTION=="ADD") && $arFields["ENABLE_IMG_VERIF"] != "Y" && $arFields["ENABLE_IMG_VERIF"] != "N")
			$arFields["ENABLE_IMG_VERIF"] = "N";
		if ((is_set($arFields, "ENABLE_RSS") || $ACTION=="ADD") && $arFields["ENABLE_RSS"] != "Y" && $arFields["ENABLE_RSS"] != "N")
			$arFields["ENABLE_RSS"] = "N";
		if ((is_set($arFields, "ALLOW_HTML") || $ACTION=="ADD") && $arFields["ALLOW_HTML"] != "Y" && $arFields["ALLOW_HTML"] != "N")
			$arFields["ALLOW_HTML"] = "N";
		if ((is_set($arFields, "USE_SOCNET") || $ACTION=="ADD") && $arFields["USE_SOCNET"] != "Y" && $arFields["USE_SOCNET"] != "N")
			$arFields["USE_SOCNET"] = "N";


		return True;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$bSuccess = True;

		foreach(GetModuleEvents("blog", "OnBeforeBlogDelete", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, Array($ID))===false)
				return false;
		}

		foreach(GetModuleEvents("blog", "OnBlogDelete", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, Array($ID));
		}

		$arBlog = CBlog::GetByID($ID);

		$DB->StartTransaction();

		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_blog_user2blog WHERE BLOG_ID = ".$ID."", true);
		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_blog_user_group_perms WHERE BLOG_ID = ".$ID."", true);
		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_blog_user2user_group WHERE BLOG_ID = ".$ID."", true);
		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_blog_user_group WHERE BLOG_ID = ".$ID."", true);
		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_blog_trackback WHERE BLOG_ID = ".$ID."", true);
		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_blog_comment WHERE BLOG_ID = ".$ID."", true);
		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_blog_post WHERE BLOG_ID = ".$ID."", true);
		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_blog_category WHERE BLOG_ID = ".$ID."", true);

		if ($bSuccess)
		{
			unset($GLOBALS["BLOG"]["BLOG_CACHE_".$ID]);
			unset($GLOBALS["BLOG"]["BLOG4OWNER_CACHE_".$arBlog["OWNER_ID"]]);
		}

		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_blog WHERE ID = ".$ID."", true);

		if ($bSuccess)
			$DB->Commit();
		else
			$DB->Rollback();

		if ($bSuccess)
		{
			if (CModule::IncludeModule("search"))
			{
				CSearch::DeleteIndex("blog", false, "COMMENT", $ID."|%");
				CSearch::DeleteIndex("blog", false, "POST", $ID);
				CSearch::DeleteIndex("blog", "B".$ID);
			}
		}
		
		if ($bSuccess)
		{
			$res = CBlogImage::GetList(array(), array("BLOG_ID"=>$ID));
			while($aImg = $res->Fetch())
				CBlogImage::Delete($aImg['ID']);
		}
		if ($bSuccess)
			$GLOBALS["USER_FIELD_MANAGER"]->Delete("BLOG_BLOG", $ID);
		
		CBlog::DeleteSocnetRead($ID);

		return $bSuccess;
	}

	public static function SetBlogPerms($ID, $arPerms = array(), $permsType = BLOG_PERMS_POST)
	{
		global $DB;

		$ID = IntVal($ID);
		$permsType = (($permsType == BLOG_PERMS_COMMENT) ? BLOG_PERMS_COMMENT : BLOG_PERMS_POST);

		$arBlog = CBlog::GetByID($ID);
		if ($arBlog)
		{
			foreach ($arPerms as $key => $value)
			{
				$dbGroupPerms = CBlogUserGroupPerms::GetList(
					array(),
					array(
						"BLOG_ID" => $ID,
						"USER_GROUP_ID" => $key,
						"PERMS_TYPE" => $permsType,
						"POST_ID" => 0
					),
					false,
					false,
					array("ID")
				);
				if ($arGroupPerms = $dbGroupPerms->Fetch())
				{
					CBlogUserGroupPerms::Update(
						$arGroupPerms["ID"],
						array("PERMS" => $value)
					);
				}
				else
				{
					CBlogUserGroupPerms::Add(
						array(
							"BLOG_ID" => $arBlog["ID"],
							"USER_GROUP_ID" => $key,
							"PERMS_TYPE" => $permsType,
							"POST_ID" => false,
							"AUTOSET" => "N",
							"PERMS" => $value
						)
					);
				}
			}
		}
	}

	public static function SetStat($ID)
	{
		global $DB;
		$ID = IntVal($ID);

		CTimeZone::Disable();
		$dbBlogPost = CBlogPost::GetList(
			array("DATE_PUBLISH" => "DESC"),
			array(
				"BLOG_ID" => $ID,
				"<=DATE_PUBLISH" => ConvertTimeStamp(false, "FULL", false),
				"PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH
			),
			false,
			array("nTopCount" => 1),
			array("ID", "DATE_PUBLISH")
		);
		CTimeZone::Enable();

		if ($arBlogPost = $dbBlogPost->Fetch())
		{
			$arFields = array(
				"LAST_POST_ID" => $arBlogPost["ID"],
				"LAST_POST_DATE" => $arBlogPost["DATE_PUBLISH"],
				"=DATE_UPDATE" => $DB->CurrentTimeFunction()
			);
		}
		else
		{
			$arFields = array(
				"LAST_POST_ID" => false,
				"LAST_POST_DATE" => false,
				"=DATE_UPDATE" => $DB->CurrentTimeFunction()
			);
		}
		CBlog::Update($ID, $arFields);
	}

	//*************** COMMON UTILS *********************/
	public static function GetFilterOperation($key)
	{
		$strNegative = "N";
		if (substr($key, 0, 1)=="!")
		{
			$key = substr($key, 1);
			$strNegative = "Y";
		}

		$strOrNull = "N";
		if (substr($key, 0, 1)=="+")
		{
			$key = substr($key, 1);
			$strOrNull = "Y";
		}

		if (substr($key, 0, 2)==">=")
		{
			$key = substr($key, 2);
			$strOperation = ">=";
		}
		elseif (substr($key, 0, 1)==">")
		{
			$key = substr($key, 1);
			$strOperation = ">";
		}
		elseif (substr($key, 0, 2)=="<=")
		{
			$key = substr($key, 2);
			$strOperation = "<=";
		}
		elseif (substr($key, 0, 1)=="<")
		{
			$key = substr($key, 1);
			$strOperation = "<";
		}
		elseif (substr($key, 0, 1)=="@")
		{
			$key = substr($key, 1);
			$strOperation = "IN";
		}
		elseif (substr($key, 0, 1)=="~")
		{
			$key = substr($key, 1);
			$strOperation = "LIKE";
		}
		elseif (substr($key, 0, 1)=="%")
		{
			$key = substr($key, 1);
			$strOperation = "QUERY";
		}
		else
		{
			$strOperation = "=";
		}

		return array("FIELD" => $key, "NEGATIVE" => $strNegative, "OPERATION" => $strOperation, "OR_NULL" => $strOrNull);
	}

	public static function PrepareSql(&$arFields, $arOrder, &$arFilter, $arGroupBy, $arSelectFields, $obUserFieldsSql = false)
	{
		global $DB;

		$strSqlSelect = "";
		$strSqlFrom = "";
		$strSqlWhere = "";
		$strSqlGroupBy = "";
		$strSqlOrderBy = "";

		$arGroupByFunct = array("COUNT", "AVG", "MIN", "MAX", "SUM");

		$arAlreadyJoined = array();

		// GROUP BY -->
		if (is_array($arGroupBy) && count($arGroupBy)>0)
		{
			$arSelectFields = $arGroupBy;
			foreach ($arGroupBy as $key => $val)
			{
				$val = strtoupper($val);
				$key = strtoupper($key);
				if (array_key_exists($val, $arFields) && !in_array($key, $arGroupByFunct))
				{
					if (strlen($strSqlGroupBy) > 0)
						$strSqlGroupBy .= ", ";
					$strSqlGroupBy .= $arFields[$val]["FIELD"];

					if (isset($arFields[$val]["FROM"])
						&& strlen($arFields[$val]["FROM"]) > 0
						&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$val]["FROM"];
						$arAlreadyJoined[] = $arFields[$val]["FROM"];
					}
				}
			}
		}
		// <-- GROUP BY

		// SELECT -->
		$arFieldsKeys = array_keys($arFields);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSqlSelect = "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT ";
		}
		else
		{
			if (isset($arSelectFields) && !is_array($arSelectFields) && is_string($arSelectFields) && strlen($arSelectFields)>0 && array_key_exists($arSelectFields, $arFields))
				$arSelectFields = array($arSelectFields);

			if (!isset($arSelectFields)
				|| !is_array($arSelectFields)
				|| count($arSelectFields)<=0
				|| in_array("*", $arSelectFields))
			{
				foreach($arFieldsKeys as $fkey)
				{
					if (isset($arFields[$fkey]["WHERE_ONLY"]) && $arFields[$fkey]["WHERE_ONLY"] == "Y")
						continue;

					if (strlen($strSqlSelect) > 0)
						$strSqlSelect .= ", ";

					if ($arFields[$fkey]["TYPE"] == "datetime")
					{
						if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($fkey, $arOrder)))
							$strSqlSelect .= $arFields[$fkey]["FIELD"]." as ".$fkey."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$fkey]["FIELD"], "FULL")." as ".$fkey;
					}
					elseif ($arFields[$fkey]["TYPE"] == "date")
					{
						if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($fkey, $arOrder)))
							$strSqlSelect .= $arFields[$fkey]["FIELD"]." as ".$fkey."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$fkey]["FIELD"], "SHORT")." as ".$fkey;
					}
					else
						$strSqlSelect .= $arFields[$fkey]["FIELD"]." as ".$fkey;

					if (isset($arFields[$fkey]["FROM"])
						&& strlen($arFields[$fkey]["FROM"]) > 0
						&& !in_array($arFields[$fkey]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$fkey]["FROM"];
						$arAlreadyJoined[] = $arFields[$fkey]["FROM"];
					}
				}
			}
			else
			{
				foreach ($arSelectFields as $key => $val)
				{
					$val = strtoupper($val);
					$key = strtoupper($key);
					if (array_key_exists($val, $arFields))
					{
						if (strlen($strSqlSelect) > 0)
							$strSqlSelect .= ", ";

						if (in_array($key, $arGroupByFunct))
						{
							$strSqlSelect .= $key."(".$arFields[$val]["FIELD"].") as ".$val;
						}
						else
						{
							if ($arFields[$val]["TYPE"] == "datetime")
							{
								if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($val, $arOrder)))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "FULL")." as ".$val;
							}
							elseif ($arFields[$val]["TYPE"] == "date")
							{
								if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($val, $arOrder)))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "SHORT")." as ".$val;
							}
							else
								$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val;
						}

						if (isset($arFields[$val]["FROM"])
							&& strlen($arFields[$val]["FROM"]) > 0
							&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
						{
							if (strlen($strSqlFrom) > 0)
								$strSqlFrom .= " ";
							$strSqlFrom .= $arFields[$val]["FROM"];
							$arAlreadyJoined[] = $arFields[$val]["FROM"];
						}
					}
				}
			}

			if (strlen($strSqlGroupBy) > 0)
			{
				if (strlen($strSqlSelect) > 0)
					$strSqlSelect .= ", ";
				$strSqlSelect .= "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT";
			}
			else
				$strSqlSelect = "%%_DISTINCT_%% ".$strSqlSelect;
		}
		// <-- SELECT

		// WHERE -->
		$arSqlSearch = Array();

		if (!is_array($arFilter))
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		foreach($filter_keys as $fkey)
		{
			$vals = $arFilter[$fkey];
			if (!is_array($vals))
				$vals = array($vals);

			$key = $fkey;
			$key_res = CBlog::GetFilterOperation($key);
			$key = $key_res["FIELD"];
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$strOrNull = $key_res["OR_NULL"];

			$arSqlSearch_tmp = array();
			if (array_key_exists($key, $arFields))
			{
				if (count($vals) > 0)
				{
					if ($strOperation == "IN")
					{
						if (isset($arFields[$key]["WHERE"]))
						{
							$arSqlSearch_tmp1 = call_user_func_array(
									$arFields[$key]["WHERE"],
									array($vals, $key, $strOperation, $strNegative, $arFields[$key]["FIELD"], $arFields, $arFilter)
								);
							if ($arSqlSearch_tmp1 !== false)
								$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
						}
						else
						{
							if ($arFields[$key]["TYPE"] == "int")
							{
								array_walk($vals, create_function("&\$item", "\$item=IntVal(\$item);"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "(1 = 2)";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " NOT " : "")."(".$arFields[$key]["FIELD"]." IN (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "double")
							{
								array_walk($vals, create_function("&\$item", "\$item=DoubleVal(\$item);"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "(1 = 2)";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
							{
								array_walk($vals, create_function("&\$item", "\$item=\"'\".\$GLOBALS[\"DB\"]->ForSql(\$item).\"'\";"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "(1 = 2)";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "datetime")
							{
								array_walk($vals, create_function("&\$item", "\$item=\"'\".\$GLOBALS[\"DB\"]->CharToDateFunction(\$GLOBALS[\"DB\"]->ForSql(\$item), \"FULL\").\"'\";"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "1 = 2";
								else
									$arSqlSearch_tmp[] = ($strNegative=="Y"?" NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "date")
							{
								array_walk($vals, create_function("&\$item", "\$item=\"'\".\$GLOBALS[\"DB\"]->CharToDateFunction(\$GLOBALS[\"DB\"]->ForSql(\$item), \"SHORT\").\"'\";"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "1 = 2";
								else
									$arSqlSearch_tmp[] = ($strNegative=="Y"?" NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
						}
					}
					else
					{
						foreach($vals as $val)
						{
							if (isset($arFields[$key]["WHERE"]))
							{
								$arSqlSearch_tmp1 = call_user_func_array(
										$arFields[$key]["WHERE"],
										array($val, $key, $strOperation, $strNegative, $arFields[$key]["FIELD"], $arFields, $arFilter)
									);
								if ($arSqlSearch_tmp1 !== false)
									$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
							}
							else
							{
								if ($arFields[$key]["TYPE"] == "int")
								{
									if ((IntVal($val) == 0) && (strpos($strOperation, "=") !== False))
										$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
									else
										$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".IntVal($val)." )";
								}
								elseif ($arFields[$key]["TYPE"] == "double")
								{
									$val = str_replace(",", ".", $val);

									if ((DoubleVal($val) == 0) && (strpos($strOperation, "=") !== False))
										$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
									else
										$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".DoubleVal($val)." )";
								}
								elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
								{
									if ($strOperation == "QUERY")
									{
										$arSqlSearch_tmp[] = GetFilterQuery($arFields[$key]["FIELD"], $val, "Y");
									}
									else
									{
										if ((strlen($val) == 0) && (strpos($strOperation, "=") !== False))
											$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$DB->Length($arFields[$key]["FIELD"])." <= 0) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
										else
											$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
									}
								}
								elseif ($arFields[$key]["TYPE"] == "datetime")
								{
									if (strlen($val) <= 0)
										$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
									else
										$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")";
								}
								elseif ($arFields[$key]["TYPE"] == "date")
								{
									if (strlen($val) <= 0)
										$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
									else
										$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "SHORT").")";
								}
							}
						}
					}
				}

				if (isset($arFields[$key]["FROM"])
					&& strlen($arFields[$key]["FROM"]) > 0
					&& !in_array($arFields[$key]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$key]["FROM"];
					$arAlreadyJoined[] = $arFields[$key]["FROM"];
				}

				$strSqlSearch_tmp = "";
				foreach($arSqlSearch_tmp as $arSqlS)
				{
					if (strlen($strSqlSearch_tmp) > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arSqlS.")";
				}
				if ($strOrNull == "Y")
				{
					if (strlen($strSqlSearch_tmp) > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." IS ".($strNegative=="Y" ? "NOT " : "")."NULL)";

					if (strlen($strSqlSearch_tmp) > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					if ($arFields[$key]["TYPE"] == "int" || $arFields[$key]["TYPE"] == "double")
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." 0)";
					elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." '')";
				}

				if ($strSqlSearch_tmp != "")
					$arSqlSearch[] = "(".$strSqlSearch_tmp.")";
			}
		}

		foreach($arSqlSearch as $sqlS)
		{
			if (strlen($strSqlWhere) > 0)
				$strSqlWhere .= " AND ";
			$strSqlWhere .= "(".$sqlS.")";
		}
		// <-- WHERE

		// ORDER BY -->
		$arSqlOrder = Array();
		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order);

			if ($order != "ASC")
				$order = "DESC";
			else
				$order = "ASC";

			if (array_key_exists($by, $arFields))
			{
				$arSqlOrder[] = " ".(array_key_exists("ORDER", $arFields[$by])? $arFields[$by]["ORDER"]: $arFields[$by]["FIELD"])." ".$order." ";

				if (isset($arFields[$by]["FROM"])
					&& strlen($arFields[$by]["FROM"]) > 0
					&& !in_array($arFields[$by]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$by]["FROM"];
					$arAlreadyJoined[] = $arFields[$by]["FROM"];
				}
			}
			elseif($obUserFieldsSql)
			{
				$arSqlOrder[] = " ".$obUserFieldsSql->GetOrder($by)." ".$order." ";
			}
		}

		$strSqlOrderBy = "";
		DelDuplicateSort($arSqlOrder); 
		foreach($arSqlOrder as $sqlO)
		{
			if (strlen($strSqlOrderBy) > 0)
				$strSqlOrderBy .= ", ";

			if(strtoupper($DB->type)=="ORACLE")
			{
				if(substr($sqlO, -3)=="ASC")
					$strSqlOrderBy .= $sqlO." NULLS FIRST";
				else
					$strSqlOrderBy .= $sqlO." NULLS LAST";
			}
			else
				$strSqlOrderBy .= $sqlO;
		}
		// <-- ORDER BY

		return array(
				"SELECT" => $strSqlSelect,
				"FROM" => $strSqlFrom,
				"WHERE" => $strSqlWhere,
				"GROUPBY" => $strSqlGroupBy,
				"ORDERBY" => $strSqlOrderBy
			);
	}

	//*************** SELECT *********************/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		
		if($ID <= 0)
			return false;

		if (!isset($GLOBALS["BLOG"]["BLOG_CACHE_".$ID]) || !is_array($GLOBALS["BLOG"]["BLOG_CACHE_".$ID]) || !is_set($GLOBALS["BLOG"]["BLOG_CACHE_".$ID], "ID"))
		{
			$strSql =
				"SELECT B.ID, B.NAME, B.DESCRIPTION, B.ACTIVE, B.OWNER_ID, B.URL, B.GROUP_ID, ".
				"	B.ENABLE_COMMENTS, B.ENABLE_IMG_VERIF, B.EMAIL_NOTIFY, B.ENABLE_RSS, B.REAL_URL, ".
				"	B.LAST_POST_ID, B.AUTO_GROUPS, B.ALLOW_HTML, B.SEARCH_INDEX, B.SOCNET_GROUP_ID, B.USE_SOCNET, ".
				"	".$DB->DateToCharFunction("B.DATE_CREATE", "FULL")." as DATE_CREATE, ".
				"	".$DB->DateToCharFunction("B.DATE_UPDATE", "FULL")." as DATE_UPDATE, ".
				"	".$DB->DateToCharFunction("B.LAST_POST_DATE", "FULL")." as LAST_POST_DATE ".
				"FROM b_blog B ".
				"WHERE B.ID = ".$ID."";
			
			$dbResult = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arResult = $dbResult->Fetch())
			{
				$GLOBALS["BLOG"]["BLOG_CACHE_".$ID] = $arResult;
			}
		}
		
		if(!empty($GLOBALS["BLOG"]["BLOG_CACHE_".$ID]))
		{
			return $GLOBALS["BLOG"]["BLOG_CACHE_".$ID];
		}

		return False;
	}

	public static function GetByOwnerID($ID, $arGroup = Array())
	{
		global $DB;

		$ID = IntVal($ID);
		if($ID <= 0)
			return false;
		
		$groups = "";
		if(is_array($arGroup) && !empty($arGroup))
		{
			$i = 0;
			foreach($arGroup as $v)
			{
				if(IntVal($v) > 0)
				{
					if($i != 0)
						$groups .= ",";
					$groups .= IntVal($v);
					$i++;
				}
			}
		}
		
		if(strlen($groups) <= 0)
			$groups = "ALL";

		if (!isset($GLOBALS["BLOG"]["BLOG4OWNER_CACHE_".$ID][$groups]) || !is_array($GLOBALS["BLOG"]["BLOG4OWNER_CACHE_".$ID][$groups]) || !is_set($GLOBALS["BLOG"]["BLOG4OWNER_CACHE_".$ID][$groups], "ID"))
		{
			$strSql =
				"SELECT B.ID, B.NAME, B.DESCRIPTION, B.ACTIVE, B.OWNER_ID, B.URL, B.GROUP_ID, ".
				"	B.ENABLE_COMMENTS, B.ENABLE_IMG_VERIF, B.EMAIL_NOTIFY, B.ENABLE_RSS, B.REAL_URL, ".
				"	B.LAST_POST_ID, B.AUTO_GROUPS, B.ALLOW_HTML, B.SEARCH_INDEX, B.SOCNET_GROUP_ID, B.USE_SOCNET, ".
				"	".$DB->DateToCharFunction("B.DATE_CREATE", "FULL")." as DATE_CREATE, ".
				"	".$DB->DateToCharFunction("B.DATE_UPDATE", "FULL")." as DATE_UPDATE, ".
				"	".$DB->DateToCharFunction("B.LAST_POST_DATE", "FULL")." as LAST_POST_DATE ".
				"FROM b_blog B ".
				"WHERE B.OWNER_ID = ".$ID." ";
			if(strlen($groups) > 0 && $groups != "ALL")
				$strSql .= "	AND B.GROUP_ID IN (".$groups.")";

			$dbResult = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arResult = $dbResult->Fetch())
			{
				$GLOBALS["BLOG"]["BLOG4OWNER_CACHE_".$ID][$groups] = $arResult;
			}
		}
		
		
		if(!empty($GLOBALS["BLOG"]["BLOG4OWNER_CACHE_".$ID][$groups]))
		{
			return $GLOBALS["BLOG"]["BLOG4OWNER_CACHE_".$ID][$groups];
		}

		return False;
	}

	public static function GetByUrl($BLOG_URL, $arGroup = Array())
	{
		global $DB;

		$BLOG_URL = preg_replace("/[^a-zA-Z0-9_-]/is", "", Trim($BLOG_URL));
		if(strlen($BLOG_URL) <= 0)
			return false;
			
		$groups = "";
		if(is_array($arGroup) && !empty($arGroup))
		{
			$i = 0;
			foreach($arGroup as $v)
			{
				if(IntVal($v) > 0)
				{
					if($i != 0)
						$groups .= ",";
					$groups .= IntVal($v);
					$i++;
				}
			}
		}
		if(strlen($groups) <= 0)
			$groups = "ALL";

		if (!isset($GLOBALS["BLOG"]["BLOGBYURL_CACHE_".$BLOG_URL][$groups]) || !is_array($GLOBALS["BLOG"]["BLOGBYURL_CACHE_".$BLOG_URL][$groups]) || !is_set($GLOBALS["BLOG"]["BLOGBYURL_CACHE_".$BLOG_URL][$groups], "ID"))
		{
			$strSql =
				"SELECT B.ID, B.NAME, B.DESCRIPTION, B.ACTIVE, B.OWNER_ID, B.URL, B.GROUP_ID, ".
				"	B.ENABLE_COMMENTS, B.ENABLE_IMG_VERIF, B.EMAIL_NOTIFY, B.ENABLE_RSS, B.REAL_URL, ".
				"	B.LAST_POST_ID, B.AUTO_GROUPS, B.ALLOW_HTML, B.SEARCH_INDEX, B.SOCNET_GROUP_ID, B.USE_SOCNET, ".
				"	".$DB->DateToCharFunction("B.DATE_CREATE", "FULL")." as DATE_CREATE, ".
				"	".$DB->DateToCharFunction("B.DATE_UPDATE", "FULL")." as DATE_UPDATE, ".
				"	".$DB->DateToCharFunction("B.LAST_POST_DATE", "FULL")." as LAST_POST_DATE ".
				"FROM b_blog B ".
				"WHERE B.URL = '".$DB->ForSql($BLOG_URL)."' ";
			if(strlen($groups) > 0 && $groups != "ALL")
				$strSql .= "	AND B.GROUP_ID IN (".$groups.")";

			$dbResult = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arResult = $dbResult->Fetch())
			{
				$GLOBALS["BLOG"]["BLOGBYURL_CACHE_".$BLOG_URL][$groups] = $arResult;
			}
		}

		if(!empty($GLOBALS["BLOG"]["BLOGBYURL_CACHE_".$BLOG_URL][$groups]))
		{
			return $GLOBALS["BLOG"]["BLOGBYURL_CACHE_".$BLOG_URL][$groups];
		}

		return False;
	}
	
	public static function GetBySocNetGroupID($ID, $arGroup = Array())
	{
		global $DB;

		$ID = IntVal($ID);
		if($ID <= 0)
			return false;
			
		$groups = "";
		if(is_array($arGroup) && !empty($arGroup))
		{
			$i = 0;
			foreach($arGroup as $v)
			{
				if(IntVal($v) > 0)
				{
					if($i != 0)
						$groups .= ",";
					$groups .= IntVal($v);
					$i++;
				}
			}
		}
		if(strlen($groups) <= 0)
			$groups = "ALL";

		if (!isset($GLOBALS["BLOG"]["BLOG4OWNERGROUP_CACHE_".$ID][$groups]) || !is_array($GLOBALS["BLOG"]["BLOG4OWNERGROUP_CACHE_".$ID][$groups]) || !is_set($GLOBALS["BLOG"]["BLOG4OWNERGROUP_CACHE_".$ID][$groups], "ID"))
		{
			$strSql =
				"SELECT B.ID, B.NAME, B.DESCRIPTION, B.ACTIVE, B.OWNER_ID, B.URL, B.GROUP_ID, ".
				"	B.ENABLE_COMMENTS, B.ENABLE_IMG_VERIF, B.EMAIL_NOTIFY, B.ENABLE_RSS, B.REAL_URL, ".
				"	B.LAST_POST_ID, B.AUTO_GROUPS, B.ALLOW_HTML, B.SEARCH_INDEX, B.SOCNET_GROUP_ID, B.USE_SOCNET,  ".
				"	".$DB->DateToCharFunction("B.DATE_CREATE", "FULL")." as DATE_CREATE, ".
				"	".$DB->DateToCharFunction("B.DATE_UPDATE", "FULL")." as DATE_UPDATE, ".
				"	".$DB->DateToCharFunction("B.LAST_POST_DATE", "FULL")." as LAST_POST_DATE  ".
				"FROM b_blog B ".
				"WHERE B.SOCNET_GROUP_ID = ".$ID." ";
			if(strlen($groups) > 0 && $groups != "ALL")
				$strSql .= "	AND B.GROUP_ID IN (".$groups.")";

			$dbResult = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arResult = $dbResult->Fetch())
			{
				$GLOBALS["BLOG"]["BLOG4OWNERGROUP_CACHE_".$ID][$groups] = $arResult;
			}
		}

		if(!empty($GLOBALS["BLOG"]["BLOG4OWNERGROUP_CACHE_".$ID][$groups]))
		{
			return $GLOBALS["BLOG"]["BLOG4OWNERGROUP_CACHE_".$ID][$groups];
		}

		return False;
	}

	public static function BuildRSS($ID, $type = "RSS .92", $numPosts = 10, $blogTemplate="", $postTemplate="", $userTemplate="", $bSoNet = false, $arParams = Array())
	{
		$ID = IntVal($ID);
		if($ID <= 0 && $arParams["USE_SOCNET"] != "Y")
			return false;
		$numPosts = IntVal($numPosts);
		$type = strtolower(preg_replace("/[^a-zA-Z0-9.]/is", "", $type));
		if ($type != "rss2.0" && $type != "atom.03")
			$type = "rss.92";

		$rssText = False;

		$arBlog = CBlog::GetByID($ID);
		if (($arBlog && $arBlog["ACTIVE"] == "Y" && $arBlog["ENABLE_RSS"] == "Y") || $arParams["USE_SOCNET"] == "Y")
		{
			if(!empty($arBlog))
				$arGroup = CBlogGroup::GetByID($arBlog["GROUP_ID"]);
			if($arGroup["SITE_ID"] == SITE_ID || $arParams["USE_SOCNET"] == "Y")
			{
				$now = date("r");
				$nowISO = date("Y-m-d\TH:i:s").substr(date("O"), 0, 3).":".substr(date("O"), -2, 2);

				$serverName = "";
				$charset = "";
				$language = "";
				$dbSite = CSite::GetList(($b = "sort"), ($o = "asc"), array("LID" => SITE_ID));
				if ($arSite = $dbSite->Fetch())
				{
					$serverName = $arSite["SERVER_NAME"];
					$charset = $arSite["CHARSET"];
					$language = $arSite["LANGUAGE_ID"];
				}

				if (strlen($serverName) <= 0)
				{
					if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0)
						$serverName = SITE_SERVER_NAME;
					else
						$serverName = COption::GetOptionString("main", "server_name", "");
				}

				if (strlen($charset) <= 0)
				{
					if (defined("SITE_CHARSET") && strlen(SITE_CHARSET) > 0)
						$charset = SITE_CHARSET;
					else
						$charset = "windows-1251";
				}

				if($bSoNet)
				{
					if(IntVal($arParams["USER_ID"]) > 0)
					{
						$dbUser = CUser::GetByID($arParams["USER_ID"]);
						if($arUser = $dbUser->Fetch())
						{
							$blogName = htmlspecialcharsbx(GetMessage("BLG_RSS_NAME_SONET", Array("#AUTHOR_NAME#" => CUser::FormatName(CSite::GetNameFormat(false), $arUser, true))));
							$blogURL = htmlspecialcharsbx("http://".$serverName.CComponentEngine::MakePathFromTemplate($blogTemplate, array("user_id" => $arParams["USER_ID"])));
						}
					}
					else
					{
						if($arGroupSoNet = CSocNetGroup::GetByID($arParams["SOCNET_GROUP_ID"]))
						{
							$blogName = htmlspecialcharsbx(GetMessage("BLG_RSS_NAME_SONET_GROUP", Array("#GROUP_NAME#" => $arGroupSoNet["NAME"])));
							$blogURL = htmlspecialcharsbx("http://".$serverName.CComponentEngine::MakePathFromTemplate($blogTemplate, array("group_id" => $arParams["SOCNET_GROUP_ID"])));
						}
					}
					$blogDescr = "";
				}
				else
				{
					if(strlen($blogTemplate)>0)
						$blogURL = htmlspecialcharsbx("http://".$serverName.CComponentEngine::MakePathFromTemplate($blogTemplate, array("blog" => $arBlog["URL"], "user_id" => $arBlog["OWNER_ID"], "group_id" => $arBlog["SOCNET_GROUP_ID"])));
					else
						$blogURL = htmlspecialcharsbx("http://".$serverName.CBlog::PreparePath($arBlog["URL"], $arGroup["SITE_ID"]));
					$blogName = htmlspecialcharsbx($arBlog["NAME"]);
					$blogDescr = htmlspecialcharsbx($arBlog["DESCRIPTION"]);
				}

				$rssText = "";
				if ($type == "rss.92")
				{
					$rssText .= "<"."?xml version=\"1.0\" encoding=\"".$charset."\"?".">\n\n";
					$rssText .= "<rss version=\".92\">\n";
					$rssText .= " <channel>\n";
					$rssText .= "	<title>".$blogName."</title>\n";
					$rssText .= "	<link>".$blogURL."</link>\n";
					$rssText .= "	<description>".$blogDescr."</description>\n";
					$rssText .= "	<language>".$language."</language>\n";
					$rssText .= "	<docs>http://backend.userland.com/rss092</docs>\n";
					$rssText .= "\n";
				}
				elseif ($type == "rss2.0")
				{
					$rssText .= "<"."?xml version=\"1.0\" encoding=\"".$charset."\"?".">\n\n";
					$rssText .= "<rss version=\"2.0\">\n";
					$rssText .= " <channel>\n";
					$rssText .= "	<title>".$blogName."</title>\n";
					//$rssText .= "	<guid>".$blogURL."</guid>\n";
					$rssText .= "	<link>".$blogURL."</link>\n";
					$rssText .= "	<description>".$blogDescr."</description>\n";
					$rssText .= "	<language>".$language."</language>\n";
					$rssText .= "	<docs>http://backend.userland.com/rss2</docs>\n";
					$rssText .= "	<pubDate>".$now."</pubDate>\n";
					$rssText .= "\n";
				}
				elseif ($type == "atom.03")
				{
					$atomID = "tag:".htmlspecialcharsbx($serverName).",".date("Y-m-d").":".$ID;

					$rssText .= "<"."?xml version=\"1.0\" encoding=\"".$charset."\"?".">\n\n";
					$rssText .= "<feed version=\"0.3\" xmlns=\"http://purl.org/atom/ns#\" xml:lang=\"".$language."\">\n";
					$rssText .= "  <title>".$blogName."</title>\n";
					$rssText .= "  <tagline>".$blogURL."</tagline>\n";
					//$rssText .= "  <link href=\"".$blogURL."\"/>";
					$rssText .= "  <id>".$atomID."</id>\n";
					$rssText .= "  <link rel=\"alternate\" type=\"text/html\" href=\"".$blogURL."\" />\n";
					$rssText .= "  <copyright>Copyright (c) ".$blogURL."</copyright>\n";
					$rssText .= "  <modified>".$nowISO."</modified>\n";
					$rssText .= "\n";
				}
				$user_id = IntVal($GLOBALS["USER"]->GetID());
				if($bSoNet)
				{
					$postPerm = BLOG_PERMS_DENY;
					if(IntVal($arParams["SOCNET_GROUP_ID"]) > 0)
					{
						if (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "view_post"))
							$postPerm = BLOG_PERMS_READ;
						if (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "write_post"))
							$postPerm = BLOG_PERMS_WRITE;
						if (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "full_post", CSocNetUser::IsCurrentUserModuleAdmin()) || $GLOBALS["APPLICATION"]->GetGroupRight("blog") >= "W")
							$postPerm = BLOG_PERMS_FULL;
					}
					else
					{
						if($user_id == $arParams["USER_ID"])
							$postPerm = BLOG_PERMS_FULL;
						elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_USER, $arParams["USER_ID"], "blog", "view_post"))
							$postPerm = BLOG_PERMS_READ;
					}
				}
				else
				{
					$postPerm = CBlog::GetBlogUserPostPerms($ID, IntVal($user_id));
				}

				if($postPerm >= BLOG_PERMS_READ)
				{
					$parser = new blogTextParser();
					$arParserParams = Array(
						"imageWidth" => $arParams["IMAGE_MAX_WIDTH"],
						"imageHeight" => $arParams["IMAGE_MAX_HEIGHT"],
					);
					if($bSoNet)
					{
						$arFilter = array(
							"<=DATE_PUBLISH" => ConvertTimeStamp(false, "FULL", false),
							"PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH,
							"BLOG_ACTIVE" => "Y",
							"BLOG_GROUP_SITE_ID" => SITE_ID,
						);
						if(IntVal($arParams["SOCNET_GROUP_ID"]) > 0)
							$arFilter["SOCNET_GROUP_ID"] = $arParams["SOCNET_GROUP_ID"];
						else
						{
							$arFilter["FOR_USER"] = $user_id;
							$arFilter["AUTHOR_ID"] = $arParams["USER_ID"];
						}
					}
					else
					{
						$arFilter = array(
							"BLOG_ID" => $ID,
							"<=DATE_PUBLISH" => ConvertTimeStamp(false, "FULL", false),
							"PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH,
							"MICRO" => "N",
						);

					}
					CTimeZone::Disable();
					$dbPosts = CBlogPost::GetList(
						array("DATE_PUBLISH" => "DESC"),
						$arFilter,
						false,
						array("nTopCount" => $numPosts),
						array("ID", "TITLE", "DETAIL_TEXT", "DATE_PUBLISH", "AUTHOR_ID", "AUTHOR_NAME", "AUTHOR_LAST_NAME", "BLOG_USER_ALIAS", "DETAIL_TEXT_TYPE", "CODE", "PATH")
					);
					CTimeZone::Enable();

					while ($arPost = $dbPosts->Fetch())
					{
						if(!$bSoNet)
						{
							$perms = CBlogPost::GetBlogUserPostPerms($arPost["ID"], ($GLOBALS["USER"]->IsAuthorized() ? $GLOBALS["USER"]->GetID() : 0));
							if ($perms < BLOG_PERMS_READ)
								continue;
						}

						//$title = htmlspecialcharsEx($arPost["TITLE"]);
						$title = str_replace(
							array("&", "<", ">", "\""),
							array("&amp;", "&lt;", "&gt;", "&quot;"),
							$arPost["TITLE"]);
						
						$res = CBlogImage::GetList(array("ID"=>"ASC"),array("POST_ID"=>$arPost['ID'], "BLOG_ID"=>$ID, "IS_COMMENT" => "N"));
						while ($arImage = $res->Fetch())
									$arImages[$arImage['ID']] = $arImage['FILE_ID'];
						
						$arDate = ParseDateTime($arPost["DATE_PUBLISH"], CSite::GetDateFormat("FULL", $arGroup["SITE_ID"]));
						$date = date("r", mktime($arDate["HH"], $arDate["MI"], $arDate["SS"], $arDate["MM"], $arDate["DD"], $arDate["YYYY"]));

						if(strlen($arPost["PATH"]) > 0)
							$url = htmlspecialcharsbx("http://".$serverName.str_replace("#post_id#", CBlogPost::GetPostID($arPost["ID"], $arPost["CODE"], $arParams["ALLOW_POST_CODE"]), $arPost["PATH"]));
						elseif(strLen($postTemplate)>0)
							$url = htmlspecialcharsbx("http://".$serverName.CComponentEngine::MakePathFromTemplate($postTemplate, array("blog" => $arBlog["URL"], "post_id"=>CBlogPost::GetPostID($arPost["ID"], $arPost["CODE"], $arParams["ALLOW_POST_CODE"]), "user_id" => (IntVal($arParams["USER_ID"]) > 0 ? $arParams["USER_ID"] : $arBlog["OWNER_ID"]), "group_id" => (IntVal($arParams["SOCNET_GROUP_ID"]) > 0 ? $arParams["SOCNET_GROUP_ID"] : $arBlog["SOCNET_GROUP_ID"]))));
						else
							$url = htmlspecialcharsbx("http://".$serverName.CBlogPost::PreparePath($arBlog["URL"], $arPost["ID"], $arGroup["SITE_ID"]));

						$category = htmlspecialcharsbx($arPost["CATEGORY_NAME"]);
						
						$BlogUser = CBlogUser::GetByID($arPost["AUTHOR_ID"], BLOG_BY_USER_ID); 
						$dbUser = CUser::GetByID($arPost["AUTHOR_ID"]);
						$arUser = $dbUser->Fetch();
						$author = htmlspecialcharsex(CBlogUser::GetUserName($BlogUser["ALIAS"], $arUser["NAME"], $arUser["LAST_NAME"], $arUser["LOGIN"], $arUser["SECOND_NAME"]));

						if(strLen($userTemplate)>0)
							$authorURL = htmlspecialcharsbx("http://".$serverName.CComponentEngine::MakePathFromTemplate($userTemplate, array("user_id"=>$arPost["AUTHOR_ID"])));
						else
							$authorURL = htmlspecialcharsbx("http://".$serverName.CBlogUser::PreparePath($arPost["AUTHOR_ID"], $arGroup["SITE_ID"]));

						if($arPost["DETAIL_TEXT_TYPE"] == "html")
							$text = $parser->convert_to_rss($arPost["DETAIL_TEXT"], $arImages, array("HTML" => "Y", "ANCHOR" => "Y", "IMG" => "Y", "SMILES" => "Y", "NL2BR" => "N", "QUOTE" => "Y", "CODE" => "Y"), true, $arParserParams);
						else
							$text = $parser->convert_to_rss($arPost["DETAIL_TEXT"], $arImages, false, !$bSoNet, $arParserParams);
						
						if(!$bSoNet)
							$text .= "<br /><a href=\"".$url."\">".GetMessage("BLG_GB_RSS_DETAIL")."</a>";

						$text = "<![CDATA[".$text."]]>";


						if ($type == "rss.92")
						{
							$rssText .= "    <item>\n";
							$rssText .= "      <title>".$title."</title>\n";
							$rssText .= "      <description>".$text."</description>\n";
							$rssText .= "      <link>".$url."</link>\n";
							$rssText .= "    </item>\n";
							$rssText .= "\n";
						}
						elseif ($type == "rss2.0")
						{
							$rssText .= "    <item>\n";
							$rssText .= "      <title>".$title."</title>\n";
							$rssText .= "      <description>".$text."</description>\n";
							$rssText .= "      <link>".$url."</link>\n";
							$rssText .= "      <guid>".$url."</guid>\n";
							$rssText .= "      <pubDate>".$date."</pubDate>\n";
							if(strlen($category) > 0)
								$rssText .= "      <category>".$category."</category>\n";
							$rssText .= "    </item>\n";
							$rssText .= "\n";
						}
						elseif ($type == "atom.03")
						{
							$atomID = "tag:".htmlspecialcharsbx($serverName).":".$arBlog["URL"]."/".$arPost["ID"];

							$timeISO = mktime($arDate["HH"], $arDate["MI"], $arDate["SS"], $arDate["MM"], $arDate["DD"], $arDate["YYYY"]);
							$dateISO = date("Y-m-d\TH:i:s", $timeISO).substr(date("O", $timeISO), 0, 3).":".substr(date("O", $timeISO), -2, 2);

							$titleRel = htmlspecialcharsbx($arPost["TITLE"]);

							$rssText .= "<entry>\n";
							$rssText .= "  <title type=\"text/html\">".$title."</title>\n";
							$rssText .= "  <link rel=\"alternate\" type=\"text/html\" href=\"".$url."\"/>\n";
							$rssText .= "  <issued>".$dateISO."</issued>\n";
							$rssText .= "  <modified>".$nowISO."</modified>\n";
							$rssText .= "  <id>".$atomID."</id>\n";
							$rssText .= "  <content type=\"text/html\" mode=\"escaped\" xml:lang=\"".$language."\" xml:base=\"".$blogURL."\">\n";
							$rssText .= $text."\n";
							$rssText .= "  </content>\n";
							$rssText .= "  <link rel=\"related\" type=\"text/html\" href=\"".$url."\" title=\"".$titleRel."\"/>\n";
							$rssText .= "  <author>\n";
							$rssText .= "    <name>".$author."</name>\n";
							$rssText .= "    <url>".$authorURL."</url>\n";
							$rssText .= "  </author>\n";
							$rssText .= "</entry>\n";
							$rssText .= "\n";
						}
					}
				}

				if ($type == "rss.92")
					$rssText .= "  </channel>\n</rss>";
				elseif ($type == "rss2.0")
					$rssText .= "  </channel>\n</rss>";
				elseif ($type == "atom.03")
					$rssText .= "\n\n</feed>";
			}
		}

		return $rssText;
	}

	public static function PreparePath($blogUrl, $siteID = False, $is404 = True, $userID = 0, $groupID = 0)
	{
		$blogUrl = Trim($blogUrl);
		if (!$siteID)
			$siteID = SITE_ID;

		$dbPath = CBlogSitePath::GetList(array(), array("SITE_ID"=>$siteID));
		while($arPath = $dbPath->Fetch())
		{
			if(strlen($arPath["TYPE"])>0)
				$arPaths[$arPath["TYPE"]] = $arPath["PATH"];
			else
				$arPaths["OLD"] = $arPath["PATH"];
		}
		if($groupID > 0 && strlen($arPaths["G"])>0)
		{
			$result = str_replace("#blog#", $blogUrl, $arPaths["G"]);
			$result = str_replace("#user_id#", $userID, $result);
			$result = str_replace("#group_id#", $groupID, $result);
		}
		elseif(strlen($arPaths["B"])>0)
		{
			$result = str_replace("#blog#", $blogUrl, $arPaths["B"]);
			$result = str_replace("#user_id#", $userID, $result);
			$result = str_replace("#group_id#", $groupID, $result);
		}
		else
		{
			if($is404)
				$result = htmlspecialcharsbx($arPaths["OLD"])."/".htmlspecialcharsbx($blogUrl)."/";
			else
				$result = htmlspecialcharsbx($arPaths["OLD"])."/blog.php?blog=".$blogUrl;
		}

		return $result;
	}

	public static function IsFriend($ID, $userID)
	{
		global $DB;

		$ID = IntVal($ID);
		$userID = IntVal($userID);

		if ($ID <= 0 || $userID <= 0)
			return False;

		$cnt = CBlogUser::GetList(
			array(),
			array("USER_ID" => $userID, "GROUP_BLOG_ID" => $ID),
			array()
		);

		return ($cnt > 0);
	}
	
	public static function BuildRSSAll($GroupId = 0, $type = "RSS .92", $numPosts = 10, $siteID = SITE_ID, $postTemplate="", $userTemplate="", $arAvBlog = Array(), $arPathTemplates = Array(), $arGroupID = Array(), $bUserSocNet = "N")
	{
		$GroupId = IntVal($GroupId);
		$numPosts = IntVal($numPosts);
		$user_id = IntVal($GLOBALS["USER"]->GetID());
		$type = strtolower(preg_replace("/[^a-zA-Z0-9.]/is", "", $type));
		if ($type != "rss2.0" && $type != "atom.03")
			$type = "rss.92";

		$rssText = False;
		$groupIdArray = array();
		$arGroup = Array();
		
		if($GroupId > 0)
		{
			if((!empty($arGroupID) && in_array($GroupId, $arGroupID)) || empty($arGroupID))
			{
				$arGroup = CBlogGroup::GetByID($GroupId);
			}
		}

		$now = date("r");
		$nowISO = date("Y-m-d\TH:i:s").substr(date("O"), 0, 3).":".substr(date("O"), -2, 2);

		$serverName = "";
		$charset = "";
		$language = "";
		$dbSite = CSite::GetList(($b = "sort"), ($o = "asc"), array("LID" => SITE_ID));
		if ($arSite = $dbSite->Fetch())
		{
			$serverName = $arSite["SERVER_NAME"];
			$charset = $arSite["CHARSET"];
			$language = $arSite["LANGUAGE_ID"];
		}

		if (strlen($serverName) <= 0)
		{
			if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0)
				$serverName = SITE_SERVER_NAME;
			else
				$serverName = COption::GetOptionString("main", "server_name", "");
		}

		if (strlen($charset) <= 0)
		{
			if (defined("SITE_CHARSET") && strlen(SITE_CHARSET) > 0)
				$charset = SITE_CHARSET;
			else
				$charset = "windows-1251";
		}

		$blogURL = "http://".$serverName;
		if($GroupId > 0)
			$blogName = GetMessage("BLG_RSS_ALL_GROUP_TITLE")." \"".htmlspecialcharsbx($arGroup["NAME"])."\" (".$serverName.")";
		else
			$blogName = GetMessage("BLG_RSS_ALL_TITLE")." \"".htmlspecialcharsbx($arSite["NAME"])."\" (".$serverName.")";

		$rssText = "";
		if ($type == "rss.92")
		{
			$rssText .= "<"."?xml version=\"1.0\" encoding=\"".$charset."\"?".">\n\n";
			$rssText .= "<rss version=\".92\">\n";
			$rssText .= " <channel>\n";
			$rssText .= "	<title>".$blogName."</title>\n";
			$rssText .= "	<link>".$blogURL."</link>\n";
			$rssText .= "	<guid>".$blogURL."</guid>\n";
			$rssText .= "	<language>".$language."</language>\n";
			$rssText .= "	<docs>http://backend.userland.com/rss092</docs>\n";
			$rssText .= "\n";
		}
		elseif ($type == "rss2.0")
		{
			$rssText .= "<"."?xml version=\"1.0\" encoding=\"".$charset."\"?".">\n\n";
			$rssText .= "<rss version=\"2.0\">\n";
			$rssText .= " <channel>\n";
			$rssText .= "	<title>".$blogName."</title>\n";
			$rssText .= "	<description>".$blogName."</description>\n";
			$rssText .= "	<link>".$blogURL."</link>\n";
			$rssText .= "	<language>".$language."</language>\n";
			$rssText .= "	<docs>http://backend.userland.com/rss2</docs>\n";
			$rssText .= "	<pubDate>".$now."</pubDate>\n";
			$rssText .= "\n";
		}
		elseif ($type == "atom.03")
		{
			$atomID = "tag:".htmlspecialcharsbx($serverName).",".date("Y-m-d");

			$rssText .= "<"."?xml version=\"1.0\" encoding=\"".$charset."\"?".">\n\n";
			$rssText .= "<feed version=\"0.3\" xmlns=\"http://purl.org/atom/ns#\" xml:lang=\"".$language."\">\n";
			$rssText .= "  <title>".$blogName."</title>\n";
			$rssText .= "  <tagline>".$blogURL."</tagline>\n";
			$rssText .= "  <id>".$atomID."</id>\n";
			$rssText .= "  <link rel=\"alternate\" type=\"text/html\" href=\"".$blogURL."\" />\n";
			$rssText .= "  <copyright>Copyright (c) ".$blogURL."</copyright>\n";
			$rssText .= "  <modified>".$nowISO."</modified>\n";
			$rssText .= "\n";
		}

		$parser = new blogTextParser();
		$arParserParams = Array(
			"imageWidth" => $arPathTemplates["IMAGE_MAX_WIDTH"],
			"imageHeight" => $arPathTemplates["IMAGE_MAX_HEIGHT"],
		);

		$arFilter = array(
				"<=DATE_PUBLISH" => ConvertTimeStamp(false, "FULL", false),
				"PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH,
				"BLOG_ENABLE_RSS" => "Y",
				"MICRO" => "N",
			);
			
		$arSelFields = array("ID", "TITLE", "DETAIL_TEXT", "DATE_PUBLISH", "AUTHOR_ID", "BLOG_USER_ALIAS", "BLOG_ID", "DETAIL_TEXT_TYPE", "BLOG_URL", "BLOG_OWNER_ID", "BLOG_SOCNET_GROUP_ID", "BLOG_GROUP_SITE_ID", "CODE", "PATH");

		if(!empty($arGroup))
			$arFilter["BLOG_GROUP_ID"] = $arGroup["ID"];
		elseif(count($arGroupID) > 0)
			$arFilter["BLOG_GROUP_ID"] = $arGroupID;
		if(count($arAvBlog) > 0)
			$arFilter["BLOG_ID"] = $arAvBlog;
		if($siteID !== false)
			$arFilter["BLOG_GROUP_SITE_ID"] = $siteID;
		if($bUserSocNet == "Y")
		{
			$arFilter["BLOG_USE_SOCNET"] = "Y";
			$arFilter["FOR_USER"] = $user_id;
			unset($arFilter["MICRO"]);
		}
		
		CTimeZone::Disable();
		$dbPosts = CBlogPost::GetList(
			array("DATE_PUBLISH" => "DESC"),
			$arFilter,
			false,
			array("nTopCount" => $numPosts),
			$arSelFields
		);
		CTimeZone::Enable();

		while ($arPost = $dbPosts->Fetch())
		{
			$perms = CBlogPost::GetBlogUserPostPerms($arPost["ID"], ($GLOBALS["USER"]->IsAuthorized() ? $GLOBALS["USER"]->GetID() : 0));
			if ($perms < BLOG_PERMS_READ)
				continue;

			$dbUser = CUser::GetByID($arPost["AUTHOR_ID"]);
			$arUser = $dbUser->Fetch();
			$author = CBlogUser::GetUserName($arPost["BLOG_USER_ALIAS"], $arUser["NAME"], $arUser["LAST_NAME"], $arUser["LOGIN"], $arUser["SECOND_NAME"]);

			$title = str_replace(
				array("&", "<", ">", "\""),
				array("&amp;", "&lt;", "&gt;", "&quot;"),
				$author.": ".$arPost["TITLE"]);

			
			$arImages = Array();
			$res = CBlogImage::GetList(array("ID"=>"ASC"),array("POST_ID"=>$arPost["ID"], "BLOG_ID"=>$arPost["BLOG_ID"], "IS_COMMENT" => "N"));
			while ($arImage = $res->Fetch())
						$arImages[$arImage['ID']] = $arImage['FILE_ID'];

			$arDate = ParseDateTime($arPost["DATE_PUBLISH"], CSite::GetDateFormat("FULL", $arPost["BLOG_GROUP_SITE_ID"]));
			$date = date("r", mktime($arDate["HH"], $arDate["MI"], $arDate["SS"], $arDate["MM"], $arDate["DD"], $arDate["YYYY"]));
			
			if(strlen($arPost["PATH"]) > 0)
			{
				$url = htmlspecialcharsbx("http://".$serverName.str_replace("#post_id#", CBlogPost::GetPostID($arPost["ID"], $arPost["CODE"], $arParams["ALLOW_POST_CODE"]), $arPost["PATH"]));
			}
			elseif(!empty($arPathTemplates))
			{
				if(IntVal($arPost["BLOG_SOCNET_GROUP_ID"]) > 0 && strlen($arPathTemplates["GROUP_BLOG_POST"]) > 0)
					$url = htmlspecialcharsbx("http://".$serverName.CComponentEngine::MakePathFromTemplate($arPathTemplates["GROUP_BLOG_POST"], array("blog" => $arPost["BLOG_URL"], "post_id" => CBlogPost::GetPostID($arPost["ID"], $arPost["CODE"], $arPathTemplates["ALLOW_POST_CODE"]), "user_id"=>$arPost["BLOG_OWNER_ID"], "group_id"=>$arPost["BLOG_SOCNET_GROUP_ID"])));
				else
					$url = htmlspecialcharsbx("http://".$serverName.CComponentEngine::MakePathFromTemplate($arPathTemplates["BLOG_POST"], array("blog" => $arPost["BLOG_URL"], "post_id" => CBlogPost::GetPostID($arPost["ID"], $arPost["CODE"], $arPathTemplates["ALLOW_POST_CODE"]), "user_id"=>$arPost["BLOG_OWNER_ID"], "group_id"=>$arPost["BLOG_SOCNET_GROUP_ID"])));
			}
			elseif(strLen($postTemplate)>0)
			{
				$url = htmlspecialcharsbx("http://".$serverName.CComponentEngine::MakePathFromTemplate($postTemplate, array("blog" => $arPost["BLOG_URL"], "post_id"=>$arPost["ID"], "user_id"=>$arPost["BLOG_OWNER_ID"], "group_id"=>$arPost["BLOG_SOCNET_GROUP_ID"])));
			}
			else
			{
				$url = htmlspecialcharsbx("http://".$serverName.CBlogPost::PreparePath(htmlspecialcharsbx($arPost["BLOG_URL"]), $arPost["ID"], $arPost["BLOG_GROUP_SITE_ID"]));
			}

			$category = htmlspecialcharsbx($arPost["CATEGORY_NAME"]);
			if(strlen($arPathTemplates["USER"]) > 0)
			{
				$authorURL = htmlspecialcharsbx("http://".$serverName.CComponentEngine::MakePathFromTemplate($arPathTemplates["USER"], array("user_id"=>$arPost["AUTHOR_ID"], "group_id"=>$arPost["BLOG_SOCNET_GROUP_ID"])));
			}
			elseif(strLen($userTemplate)>0)
				$authorURL = htmlspecialcharsbx("http://".$serverName.CComponentEngine::MakePathFromTemplate($userTemplate, array("user_id"=>$arPost["AUTHOR_ID"], "group_id"=>$arPost["BLOG_SOCNET_GROUP_ID"])));
			else
				$authorURL = htmlspecialcharsbx("http://".$serverName.CBlogUser::PreparePath($arPost["AUTHOR_ID"], $arPost["BLOG_GROUP_SITE_ID"]));
			
			if($arPost["DETAIL_TEXT_TYPE"] == "html")
				$text = $parser->convert_to_rss($arPost["DETAIL_TEXT"], $arImages, array("HTML" => "Y", "ANCHOR" => "Y", "IMG" => "Y", "SMILES" => "Y", "NL2BR" => "N", "QUOTE" => "Y", "CODE" => "Y"), true, $arParserParams);
			else
				$text = $parser->convert_to_rss($arPost["DETAIL_TEXT"], $arImages, false, true, $arParserParams);
			
			if($bUserSocNet != "Y")
				$text .= "<br /><a href=\"".$url."\">".GetMessage("BLG_GB_RSS_DETAIL")."</a>";
			$text = "<![CDATA[".$text."]]>";
			
			if ($type == "rss.92")
			{
				$rssText .= "    <item>\n";
				$rssText .= "      <title>".$title."</title>\n";
				$rssText .= "      <description>".$text."</description>\n";
				$rssText .= "      <link>".$url."</link>\n";
				$rssText .= "    </item>\n";
				$rssText .= "\n";
			}
			elseif ($type == "rss2.0")
			{
				$rssText .= "    <item>\n";
				$rssText .= "      <title>".$title."</title>\n";
				$rssText .= "      <description>".$text."</description>\n";
				$rssText .= "      <link>".$url."</link>\n";
				$rssText .= "      <guid>".$url."</guid>\n";
				$rssText .= "      <pubDate>".$date."</pubDate>\n";
				if(strlen($category) > 0)
					$rssText .= "      <category>".$category."</category>\n";
				$rssText .= "    </item>\n";
				$rssText .= "\n";
			}
			elseif ($type == "atom.03")
			{
				$atomID = "tag:".htmlspecialcharsbx($serverName).":".$arBlog["URL"]."/".$arPost["ID"];

				$timeISO = mktime($arDate["HH"], $arDate["MI"], $arDate["SS"], $arDate["MM"], $arDate["DD"], $arDate["YYYY"]);
				$dateISO = date("Y-m-d\TH:i:s", $timeISO).substr(date("O", $timeISO), 0, 3).":".substr(date("O", $timeISO), -2, 2);

				$titleRel = htmlspecialcharsbx($arPost["TITLE"]);

				$rssText .= "<entry>\n";
				$rssText .= "  <title type=\"text/html\">".$title."</title>\n";
				$rssText .= "  <link rel=\"alternate\" type=\"text/html\" href=\"".$url."\"/>\n";
				$rssText .= "  <issued>".$dateISO."</issued>\n";
				$rssText .= "  <modified>".$nowISO."</modified>\n";
				$rssText .= "  <id>".$atomID."</id>\n";
				$rssText .= "  <content type=\"text/html\" mode=\"escaped\" xml:lang=\"".$language."\" xml:base=\"".$blogURL."\">\n";
				$rssText .= $text."\n";
				$rssText .= "  </content>\n";
				$rssText .= "  <link rel=\"related\" type=\"text/html\" href=\"".$url."\" title=\"".$titleRel."\"/>\n";
				$rssText .= "  <author>\n";
				$rssText .= "    <name>".htmlspecialcharsbx($author)."</name>\n";
				$rssText .= "    <url>".$authorURL."</url>\n";
				$rssText .= "  </author>\n";
				$rssText .= "</entry>\n";
				$rssText .= "\n";
			}
		}

		if ($type == "rss.92")
			$rssText .= "  </channel>\n</rss>";
		elseif ($type == "rss2.0")
			$rssText .= "  </channel>\n</rss>";
		elseif ($type == "atom.03")
			$rssText .= "\n\n</feed>";

		return $rssText;
	}
	
	public static function DeleteSocnetRead($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		if($ID <= 0)
			return false;
		return $DB->Query("DELETE FROM b_blog_socnet WHERE BLOG_ID = ".$ID."", true);
	}
	
	public static function GetSocnetReadByBlog($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		if($ID <= 0)
			return false;
		$dbRes = $DB->Query("select BLOG_ID from b_blog_socnet WHERE BLOG_ID = ".$ID."", true);
		if($dbRes -> Fetch())
			return true;
		return false;
	}
	
	public static function SendPing($blogName, $blogUrl, $blogXml = "")
	{
		if (defined("SITE_CHARSET") && strlen(SITE_CHARSET) > 0)
			$serverCharset = SITE_CHARSET;
		else
			$serverCharset = "windows-1251";
		if(strlen($blogName) <= 0)
			return false;
		if(strlen($blogUrl) <= 0)
			return false;

		$query = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
		<methodCall>
			<methodName>weblogUpdates.ping</methodName>
			<params>
				<param>
					<value>".htmlspecialcharsEx($blogName)."</value>
				</param>
				<param>
					<value>".htmlspecialcharsEx($blogUrl)."</value>
				</param>";
		if(strlen($blogXml) > 0)
			$query .= "				<param>
					<value>".htmlspecialcharsEx($blogXml)."</value>
				</param>";
		$query .= "	</params>
		</methodCall>";

		$query = $GLOBALS["APPLICATION"]->ConvertCharset($query, $serverCharset, "UTF-8");
		
		if($urls = COption::GetOptionString("blog", "send_blog_ping_address", "http://ping.blogs.yandex.ru/RPC2\r\nhttp://rpc.weblogs.com/RPC2"))
		{
			$arUrls = explode("\r\n", $urls);
			foreach($arUrls as $v)
			{
				if(strlen($v) > 0)
				{
					$v = str_replace("http://", "", $v);
					$pingUrl = str_replace("https://", "", $v);
					$arPingUrl = explode("/", $v);

					$host = trim($arPingUrl[0]);
					unset($arPingUrl[0]);
					$path = "/".trim(implode("/", $arPingUrl));

					$arHost = explode(":", $host);
					$port = ((count($arHost) > 1) ? $arHost[1] : 80);
					$host = $arHost[0];

					if(strlen($host) > 0)
					{
						$fp = @fsockopen($host, $port, $errnum, $errstr, 30);
						if ($fp)
						{ 
							$out = "";
							$out .= "POST ".$path." HTTP/1.1\r\n";
							$out .= "Host: ".$host." \r\n";
							$out .= "Content-type: text/xml\r\n";
							$out .= "User-Agent: bitrixBlog\r\n";
							$out .= "Content-length: ".strlen($query)."\r\n\r\n";
							$out = $GLOBALS["APPLICATION"]->ConvertCharset($out, $serverCharset, "UTF-8");
							$out .= $query;
							
							fwrite($fp, $out);
							fclose($fp);
						}
					}
				}
			}
		}
	}
	
	public static function GetWritableSocnetBlogs($user_id = 0, $type = "U", $site_id = SITE_ID)
	{
		if(CModule::IncludeModule("socialnetwork"))
		{
			if(IntVal($user_id) <= 0)
				return false;
			$user_id = IntVal($user_id);
			
			global $DB;
			if($type == "G")
			{
				$strSql = "SELECT b.ENTITY_ID as ID, bf.OPERATION_ID, bf.ROLE, bu.USER_ID, blg.NAME as BLOG_NAME, blg.ID as BLOG_ID, g.NAME
					FROM b_sonet_features b
					INNER JOIN b_sonet_features2perms bf ON (b.ID = bf.FEATURE_ID AND bf.OPERATION_ID in ('write_post', 'full_post', 'premoderate_post', 'moderate_post'))
					LEFT  JOIN b_blog blg ON blg.SOCNET_GROUP_ID = b.ENTITY_ID
					LEFT  JOIN b_blog_group blgg ON (blg.GROUP_ID = blgg.ID AND blgg.SITE_ID = '".$DB->ForSql($site_id)."')
					LEFT JOIN b_sonet_user2group bu ON (b.ENTITY_ID = bu.GROUP_ID AND bf.ROLE = bu.ROLE AND bu.USER_ID = '".$user_id."')
					LEFT JOIN b_sonet_group g on (b.ENTITY_ID = g.ID)
					where
					b.FEATURE='blog' AND b.ACTIVE='Y' AND b.ENTITY_TYPE = 'G'";
			}
			else
			{
				$strSql = "SELECT b.ENTITY_ID as ID, bf.OPERATION_ID, bf.ROLE, ur.FIRST_USER_ID, ur.SECOND_USER_ID, blg.NAME as BLOG_NAME, blg.ID as BLOG_ID, u.LOGIN, u.NAME, u.LAST_NAME
						FROM b_sonet_features b
					INNER JOIN b_sonet_features2perms bf ON (b.ID = bf.FEATURE_ID AND bf.OPERATION_ID in ('write_post', 'full_post', 'premoderate_post', 'moderate_post'))
					LEFT  JOIN b_blog blg ON (blg.OWNER_ID = b.ENTITY_ID AND blg.USE_SOCNET = 'Y')
					LEFT  JOIN b_blog_group blgg ON (blg.GROUP_ID = blgg.ID AND blgg.SITE_ID = '".$DB->ForSql($site_id)."')
					LEFT JOIN b_sonet_user_relations ur ON (((ur.FIRST_USER_ID = '".$user_id."' AND ur.SECOND_USER_ID=b.ENTITY_ID)
						OR (ur.SECOND_USER_ID = '".$user_id."' AND ur.FIRST_USER_ID=b.ENTITY_ID))
						AND ur.RELATION = 'F')
					LEFT JOIN b_user u ON (b.ENTITY_ID = u.ID)
					where
					b.FEATURE='blog' AND b.ACTIVE='Y' AND b.ENTITY_TYPE = 'U'";
			}
			$dbRes = $DB->Query($strSql);
			return $dbRes;
		}
		return false;

	}
}
?>
