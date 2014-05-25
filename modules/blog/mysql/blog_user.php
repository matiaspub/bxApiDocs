<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/blog/general/blog_user.php");

class CBlogUser extends CAllBlogUser
{
	/*************** ADD, UPDATE, DELETE *****************/
	public static function Add($arFields)
	{
		global $DB;
		if(strlen($arFields["PATH"]) > 0)
		{
			$path = $arFields["PATH"];
			unset($arFields["PATH"]);
		}

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1) == "=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CBlogUser::CheckFields("ADD", $arFields))
			return false;

		if (
			array_key_exists("AVATAR", $arFields)
			&& is_array($arFields["AVATAR"])
			&& (
				!array_key_exists("MODULE_ID", $arFields["AVATAR"])
				|| strlen($arFields["AVATAR"]["MODULE_ID"]) <= 0
			)
		)
			$arFields["AVATAR"]["MODULE_ID"] = "blog";

		CFile::SaveForDB($arFields, "AVATAR", "blog/avatar");

		$arInsert = $DB->PrepareInsert("b_blog_user", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($arInsert[0]) > 0)
				$arInsert[0] .= ", ";
			$arInsert[0] .= $key;
			if (strlen($arInsert[1]) > 0)
				$arInsert[1] .= ", ";
			$arInsert[1] .= $value;
		}

		$ID = False;
		if (strlen($arInsert[0]) > 0)
		{
			$strSql =
				"INSERT INTO b_blog_user(".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			$ID = IntVal($DB->LastID());
		}

		if ($ID)
		{
			if (CModule::IncludeModule("search"))
			{
				$arBlogUser = CBlogUser::GetByID($ID);

				$dbUser = CUser::GetByID($arBlogUser["USER_ID"]);
				$arUser = $dbUser->Fetch();

					$arBlog = CBlog::GetByOwnerID($arBlogUser["USER_ID"]);
					if ($arBlog)
					{
						$arGroup = CBlogGroup::GetByID($arBlog["GROUP_ID"]);
						if(strlen($path) > 0)
						{
							$arPostSite = array($arGroup["SITE_ID"] => $path);
						}
						else
						{
							$arPostSite = array(
								$arGroup["SITE_ID"] => CBlogUser::PreparePath(
										$arBlogUser["USER_ID"],
										$arGroup["SITE_ID"]
									)
							);
						}
					}
					else
					{
						if(strlen($arUser["LID"]) <= 0)
							$arUser["LID"] = SITE_ID;
						if(strlen($path) > 0)
						{
							$arPostSite = array($arUser["LID"] => $path);
						}
						else
						{
							$arPostSite = array($arUser["LID"] => CBlogUser::PreparePath($arBlogUser["USER_ID"], $arUser["LID"]));
						}
					}

				$arSearchIndex = array(
					"SITE_ID" => $arPostSite,
					"LAST_MODIFIED" => ConvertTimeStamp(false, "FULL", false),
					"PARAM1" => "USER",
					"PARAM2" => $arBlogUser["USER_ID"],
					"PERMISSIONS" => array(2),
					"TITLE" => CBlogUser::GetUserName($arBlogUser["ALIAS"], $arUser["NAME"], $arUser["LAST_NAME"], $arUser["LOGIN"], $arUser["SECOND_NAME"]),
					"BODY" => blogTextParser::killAllTags($arBlogUser["INTERESTS"]." ".$arBlogUser["DESCRIPTION"])
				);

				CSearch::Index("blog", "U".$ID, $arSearchIndex);
			}
		}

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = IntVal($ID);
		
		if(strlen($arFields["PATH"]) > 0)
		{
			$path = $arFields["PATH"];
			unset($arFields["PATH"]);
		}

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1) == "=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CBlogUser::CheckFields("UPDATE", $arFields, $ID))
			return false;

		if (
			array_key_exists("AVATAR", $arFields)
			&& is_array($arFields["AVATAR"])
			&& (
				!array_key_exists("MODULE_ID", $arFields["AVATAR"])
				|| strlen($arFields["AVATAR"]["MODULE_ID"]) <= 0
			)
		)
			$arFields["AVATAR"]["MODULE_ID"] = "blog";

		CFile::SaveForDB($arFields, "AVATAR", "blog/avatar");

		$strUpdate = $DB->PrepareUpdate("b_blog_user", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate) > 0)
				$strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		if (strlen($strUpdate) > 0)
		{
			$arUser = CBlogUser::GetByID($ID, BLOG_BY_BLOG_USER_ID);

			$strSql =
				"UPDATE b_blog_user SET ".
				"	".$strUpdate." ".
				"WHERE ID = ".$ID." ";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			unset($GLOBALS["BLOG_USER"]["BLOG_USER_CACHE_".$ID]);
			unset($GLOBALS["BLOG_USER"]["BLOG_USER1_CACHE_".$arUser["USER_ID"]]);
		}
		else
		{
			$ID = false;
		}

		if ($ID && !(count($arFields1)==1 && strlen($arFields1["LAST_VISIT"])>0))
		{
			if (CModule::IncludeModule("search"))
			{
				$arBlogUser = CBlogUser::GetByID($ID);

				$dbUser = CUser::GetByID($arBlogUser["USER_ID"]);
				$arUser = $dbUser->Fetch();

				$arBlog = CBlog::GetByOwnerID($arBlogUser["USER_ID"]);
				if ($arBlog)
				{
					$arGroup = CBlogGroup::GetByID($arBlog["GROUP_ID"]);
					if(strlen($path) > 0)
					{
						$arPostSite = array($arGroup["SITE_ID"] => $path);
					}
					else
					{
						$arPostSite = array(
							$arGroup["SITE_ID"] => CBlogUser::PreparePath(
									$arBlogUser["USER_ID"],
									$arGroup["SITE_ID"]
								)
						);
					}
				}
				else
				{
					if(strlen($arUser["LID"]) <= 0)
						$arUser["LID"] = SITE_ID;
					if(strlen($path) > 0)
					{
						$arPostSite = array($arUser["LID"] => $path);
					}
					else
					{
						$arPostSite = array($arUser["LID"] => CBlogUser::PreparePath($arBlogUser["USER_ID"], $arUser["LID"]));
					}
				}
				if(strlen($arBlogUser["LAST_VISIT"])<=0)
					$arBlogUser["LAST_VISIT"] = ConvertTimeStamp(false, "FULL", false);
				$arSearchIndex = array(
					"SITE_ID" => $arPostSite,
					"LAST_MODIFIED" => $arBlogUser["LAST_VISIT"],
					"PARAM1" => "USER",
					"PARAM2" => $arBlogUser["USER_ID"],
					"PERMISSIONS" => array(2),
					"TITLE" => CBlogUser::GetUserName($arBlogUser["ALIAS"], $arUser["NAME"], $arUser["LAST_NAME"], $arUser["LOGIN"], $arUser["SECOND_NAME"]),
					"BODY" => blogTextParser::killAllTags($arBlogUser["INTERESTS"]." ".$arBlogUser["DESCRIPTION"])
				);

				CSearch::Index("blog", "U".$ID, $arSearchIndex);
			}
		}

		return $ID;
	}

	//*************** SELECT *********************/
	public static function GetList($arOrder = Array("ID" => "DESC"), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "USER_ID", "ALIAS", "DESCRIPTION", "AVATAR", "INTERESTS", "LAST_VISIT", "DATE_REG", "ALLOW_POST");

		// FIELDS -->
		$arFields = array(
			"ID" => array("FIELD" => "B.ID", "TYPE" => "int"),
			"USER_ID" => array("FIELD" => "B.USER_ID", "TYPE" => "int"),
			"ALIAS" => array("FIELD" => "B.ALIAS", "TYPE" => "string"),
			"DESCRIPTION" => array("FIELD" => "B.DESCRIPTION", "TYPE" => "string"),
			"AVATAR" => array("FIELD" => "B.AVATAR", "TYPE" => "int"),
			"INTERESTS" => array("FIELD" => "B.INTERESTS", "TYPE" => "string"),
			"LAST_VISIT" => array("FIELD" => "B.LAST_VISIT", "TYPE" => "datetime"),
			"DATE_REG" => array("FIELD" => "B.DATE_REG", "TYPE" => "datetime"),
			"ALLOW_POST" => array("FIELD" => "B.ALLOW_POST", "TYPE" => "char"),

			"USER_LOGIN" => array("FIELD" => "U.LOGIN", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (B.USER_ID = U.ID)"),
			"USER_NAME" => array("FIELD" => "U.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (B.USER_ID = U.ID)"),
			"USER_LAST_NAME" => array("FIELD" => "U.LAST_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (B.USER_ID = U.ID)"),
			"USER_SECOND_NAME" => array("FIELD" => "U.SECOND_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (B.USER_ID = U.ID)"),
			"USER_EMAIL" => array("FIELD" => "U.EMAIL", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (B.USER_ID = U.ID)"),
			"USER" => array("FIELD" => "U.LOGIN,U.NAME,U.LAST_NAME,U.SECOND_NAME,U.EMAIL,U.ID", "WHERE_ONLY" => "Y", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (B.USER_ID = U.ID)"),

			"GROUP_GROUP_ID" => array("FIELD" => "U2UG.USER_GROUP_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_blog_user2user_group U2UG ON (B.USER_ID = U2UG.USER_ID)"),
			"GROUP_BLOG_ID" => array("FIELD" => "U2UG.BLOG_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_blog_user2user_group U2UG ON (B.USER_ID = U2UG.USER_ID)"),
		);
		// <-- FIELDS

		$arSqls = CBlog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_blog_user B ".
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
			"FROM b_blog_user B ".
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
				"FROM b_blog_user B ".
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

	public static function GetUserFriendsList($ID, $userID = False, $bAuth = False, $limit = 20, $arGroup = Array())
	{
		global $DB;

		$ID = IntVal($ID);
		$userID = IntVal($userID);
		$bAuth = ($bAuth ? True : False);
		$limit = IntVal($limit);
		if ($limit <= 0)
			$limit = 20;

		$strSql =
			"SELECT BP.ID, BP.DATE_PUBLISH, B.ID as BLOG_ID, B.URL ".
			"FROM b_blog B1 ".
			"	INNER JOIN b_blog_user2user_group U2UG ".
			"		ON (B1.ID = U2UG.BLOG_ID) ".
			"	INNER JOIN b_blog B ".
			"		ON (U2UG.USER_ID = B.OWNER_ID) ".
			"	INNER JOIN b_blog_post BP ".
			"		ON (B.ID = BP.BLOG_ID ".
			"			AND BP.DATE_PUBLISH <= ".$DB->CurrentTimeFunction()." ".
			"			AND BP.PUBLISH_STATUS = '".$DB->ForSql(BLOG_PUBLISH_STATUS_PUBLISH)."') ".
			"	INNER JOIN b_blog_user_group_perms UGP2 ".
			"		ON (B.ID = UGP2.BLOG_ID ".
			"			AND UGP2.USER_GROUP_ID = 1 ".
			"			AND BP.ID = UGP2.POST_ID) ";

		if ($bAuth)
			$strSql .=
				"	INNER JOIN b_blog_user_group_perms UGP3 ".
				"		ON (B.ID = UGP3.BLOG_ID ".
				"			AND UGP3.USER_GROUP_ID = 2 ".
				"			AND BP.ID = UGP3.POST_ID) ";

		$strSql .=
			"	LEFT JOIN b_blog_user2user_group U2UG1 ".
			"		ON (B.ID = U2UG1.BLOG_ID AND U2UG1.USER_ID = ".$userID.") ".
			"	LEFT JOIN b_blog_user_group_perms UGP ".
			"		ON (B.ID = UGP.BLOG_ID ".
			"			AND U2UG1.USER_GROUP_ID = UGP.USER_GROUP_ID ".
			"			AND BP.ID = UGP.POST_ID) ".
			"WHERE B1.OWNER_ID = ".$ID." ".
			"	AND B.ACTIVE = 'Y' ".
			"	AND B1.ACTIVE = 'Y' ";
		
		if(!empty($arGroup))
		{
			foreach($arGroup as $k => $v)
			{
				if(IntVal($v) <= 0)
					unset($arGroup[$k]);
				else
					$arGroup[$k] = IntVal($v);
			}
			$strGroupID = implode(",", $arGroup);

			$strSql .= "	AND B.GROUP_ID in (".$strGroupID.") ".
						"	AND B1.GROUP_ID in (".$strGroupID.") ";
		}

		$strSql .= "	AND (UGP.PERMS > '".$DB->ForSql(BLOG_PERMS_DENY)."' ".
			"		OR UGP2.PERMS > '".$DB->ForSql(BLOG_PERMS_DENY)."' ";

		if ($bAuth)
			$strSql .= "		OR UGP3.PERMS > '".$DB->ForSql(BLOG_PERMS_DENY)."' ";

		$strSql .= 
			") ".
		"GROUP BY BP.ID, BP.DATE_PUBLISH, B.ID, B.URL ".
		"ORDER BY BP.DATE_PUBLISH DESC ".
		"LIMIT ".$limit." ";

		$dbResult = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $dbResult;
	}
}
?>