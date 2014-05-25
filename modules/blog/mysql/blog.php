<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/blog/general/blog.php");

class CBlog extends CAllBlog
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

		if (!CBlog::CheckFields("ADD", $arFields))
			return false;
		elseif(!$GLOBALS["USER_FIELD_MANAGER"]->CheckFields("BLOG_BLOG", 0, $arFields))
			return false;

		foreach(GetModuleEvents("blog", "OnBeforeBlogAdd", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, Array(&$arFields))===false)
				return false;
		}

		$arInsert = $DB->PrepareInsert("b_blog", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($arInsert[0]) > 0)
				$arInsert[0] .= ", ";
			$arInsert[0] .= $key;
			if (strlen($arInsert[1]) > 0)
				$arInsert[1] .= ", ";
			$arInsert[1] .= $value;
		}

		$ID = false;
		if (strlen($arInsert[0]) > 0)
		{
			$strSql =
				"INSERT INTO b_blog(".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			$ID = IntVal($DB->LastID());

			if (is_set($arFields, "PERMS_POST"))
				CBlog::SetBlogPerms($ID, $arFields["PERMS_POST"], BLOG_PERMS_POST);
			if (is_set($arFields, "PERMS_COMMENT"))
				CBlog::SetBlogPerms($ID, $arFields["PERMS_COMMENT"], BLOG_PERMS_COMMENT);
				
			$GLOBALS["USER_FIELD_MANAGER"]->Update("BLOG_BLOG", $ID, $arFields);

			foreach(GetModuleEvents("blog", "OnBlogAdd", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, Array($ID, &$arFields));
			}
		}

		if ($ID && (is_set($arFields, "NAME") || is_set($arFields, "DESCRIPTION")))
		{
			if (CModule::IncludeModule("search"))
			{
				$arBlog = CBlog::GetByID($ID);
				if ($arBlog["ACTIVE"] == "Y" && $arBlog["SEARCH_INDEX"] == "Y" && $arBlog["USE_SOCNET"] != "Y")
				{
					$arGroup = CBlogGroup::GetByID($arBlog["GROUP_ID"]);

					if(strlen($path) > 0)
					{
						$path = str_replace("#blog_url#", $arBlog["URL"], $path);
						$arPostSite = array($arGroup["SITE_ID"] => $path);
					}
					else
					{
						$arPostSite = array(
							$arGroup["SITE_ID"] => CBlog::PreparePath(
									$arBlog["URL"],
									$arGroup["SITE_ID"],
									false,
									$arBlog["OWNER_ID"],
									$arBlog["SOCNET_GROUP_ID"]
							)
						);
					}

					$arSearchIndex = array(
						"SITE_ID" => $arPostSite,
						"LAST_MODIFIED" => $arBlog["DATE_UPDATE"],
						"PARAM1" => "BLOG",
						"PARAM2" => $arBlog["OWNER_ID"],
						"PERMISSIONS" => array(2),
						"TITLE" => $arBlog["NAME"],
						"BODY" => ((strlen($arBlog["DESCRIPTION"]) > 0) ? $arBlog["DESCRIPTION"] : $arBlog["NAME"]),
					);
					CSearch::Index("blog", "B".$ID, $arSearchIndex);
				}
			}
		}

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = IntVal($ID);
		if($ID <= 0)
			return false;
			
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

		if (!CBlog::CheckFields("UPDATE", $arFields, $ID))
			return false;
		elseif(!$GLOBALS["USER_FIELD_MANAGER"]->CheckFields("BLOG_BLOG", $ID, $arFields))
			return false;

		foreach(GetModuleEvents("blog", "OnBeforeBlogUpdate", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, Array($ID, &$arFields))===false)
				return false;
		}

		$arBlogOld = CBlog::GetByID($ID);

		$strUpdate = $DB->PrepareUpdate("b_blog", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate) > 0)
				$strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		if (strlen($strUpdate) > 0)
		{
			$strSql =
				"UPDATE b_blog SET ".
				"	".$strUpdate." ".
				"WHERE ID = ".$ID." ";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			unset($GLOBALS["BLOG"]["BLOG_CACHE_".$ID]);
			unset($GLOBALS["BLOG"]["BLOG4OWNER_CACHE_".$arBlogOld["OWNER_ID"]]);
			unset($GLOBALS["BLOG"]["BLOG4OWNERGROUP_CACHE_".$arBlogOld["SOCNET_GROUP_ID"]]);

			foreach(GetModuleEvents("blog", "OnBlogUpdate", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, Array($ID, &$arFields));
			}

			if (is_set($arFields, "PERMS_POST"))
				CBlog::SetBlogPerms($ID, $arFields["PERMS_POST"], BLOG_PERMS_POST);
			if (is_set($arFields, "PERMS_COMMENT"))
				CBlog::SetBlogPerms($ID, $arFields["PERMS_COMMENT"], BLOG_PERMS_COMMENT);
				
			$GLOBALS["USER_FIELD_MANAGER"]->Update("BLOG_BLOG", $ID, $arFields);
		}
		else
		{
			$ID = False;
		}

		if ($ID && (is_set($arFields, "NAME") || is_set($arFields, "DESCRIPTION")))
		{
			if (CModule::IncludeModule("search"))
			{
				$arBlog = CBlog::GetByID($ID);
				if (($arBlogOld["ACTIVE"] == "Y" && $arBlog["ACTIVE"] != "Y") || ($arBlogOld["SEARCH_INDEX"] == "Y" && $arBlog["SEARCH_INDEX"] != "Y"))
				{
					CSearch::DeleteIndex("blog", false, "COMMENT", $ID."|%");
					CSearch::DeleteIndex("blog", false, "POST", $ID);
					CSearch::DeleteIndex("blog", "B".$ID);
				}
				elseif ($arBlog["ACTIVE"] == "Y" && $arBlog["SEARCH_INDEX"] == "Y")
				{
					if($arBlog["USE_SOCNET"] == "Y")
					{
						CSearch::DeleteIndex("blog", "B".$ID);
					}
					else
					{
						$arGroup = CBlogGroup::GetByID($arBlog["GROUP_ID"]);
						if(strlen($path) > 0)
						{
							$path = str_replace("#blog_url#", $arBlog["URL"], $path);
							$arPostSite = array($arGroup["SITE_ID"] => $path);
						}
						else
						{
							$arPostSite = array(
								$arGroup["SITE_ID"] => CBlog::PreparePath(
										$arBlog["URL"],
										$arGroup["SITE_ID"],
										false,
										$arBlog["OWNER_ID"],
										$arBlog["SOCNET_GROUP_ID"]
									)
							);
						}


						$arSearchIndex = array(
							"SITE_ID" => $arPostSite,
							"LAST_MODIFIED" => $arBlog["DATE_UPDATE"],
							"PARAM1" => "BLOG",
							"PARAM2" => $arBlog["OWNER_ID"],
							"PERMISSIONS" => array(2),
							"TITLE" => $arBlog["NAME"],
							"BODY" => ((strlen($arBlog["DESCRIPTION"]) > 0) ? $arBlog["DESCRIPTION"] : $arBlog["NAME"]),
						);
						CSearch::Index("blog", "B".$ID, $arSearchIndex);
					}
				}
			}
		}
		return $ID;
	}

	//*************** SELECT *********************/
	public static function GetList($arOrder = Array("ID" => "DESC"), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB, $USER_FIELD_MANAGER, $USER;

		$obUserFieldsSql = new CUserTypeSQL;
		$obUserFieldsSql->SetEntity("BLOG_BLOG", "B.ID");
		$obUserFieldsSql->SetSelect($arSelectFields);
		$obUserFieldsSql->SetFilter($arFilter);
		$obUserFieldsSql->SetOrder($arOrder);

		if(!empty($arSelectFields) && !in_array("ID", $arSelectFields))
			$arSelectFields[] = "ID";

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "NAME", "DESCRIPTION", "DATE_CREATE", "DATE_UPDATE", "ACTIVE", "OWNER_ID", "URL", "REAL_URL", "GROUP_ID", "ENABLE_COMMENTS", "ENABLE_IMG_VERIF", "EMAIL_NOTIFY", "ENABLE_RSS", "LAST_POST_ID", "LAST_POST_DATE", "AUTO_GROUPS", "ALLOW_HTML", "SOCNET_GROUP_ID");
		if(in_array("*", $arSelectFields))
			$arSelectFields = array("ID", "NAME", "DESCRIPTION", "DATE_CREATE", "DATE_UPDATE", "ACTIVE", "OWNER_ID", "SOCNET_GROUP_ID", "URL", "REAL_URL", "GROUP_ID", "ENABLE_COMMENTS", "ENABLE_IMG_VERIF", "EMAIL_NOTIFY", "ENABLE_RSS", "ALLOW_HTML", "LAST_POST_ID", "LAST_POST_DATE", "AUTO_GROUPS", "SEARCH_INDEX", "USE_SOCNET", "OWNER_LOGIN", "OWNER_NAME", "OWNER_LAST_NAME", "OWNER_EMAIL", "OWNER", "GROUP_NAME", "GROUP_SITE_ID", "BLOG_USER_ALIAS", "BLOG_USER_AVATAR");

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "B.ID", "TYPE" => "int"),
				"NAME" => array("FIELD" => "B.NAME", "TYPE" => "string"),
				"DESCRIPTION" => array("FIELD" => "B.DESCRIPTION", "TYPE" => "string"),
				"DATE_CREATE" => array("FIELD" => "B.DATE_CREATE", "TYPE" => "datetime"),
				"DATE_UPDATE" => array("FIELD" => "B.DATE_UPDATE", "TYPE" => "datetime"),
				"ACTIVE" => array("FIELD" => "B.ACTIVE", "TYPE" => "char"),
				"OWNER_ID" => array("FIELD" => "B.OWNER_ID", "TYPE" => "int"),
				"SOCNET_GROUP_ID" => array("FIELD" => "B.SOCNET_GROUP_ID", "TYPE" => "int"),
				"URL" => array("FIELD" => "B.URL", "TYPE" => "string"),
				"REAL_URL" => array("FIELD" => "B.REAL_URL", "TYPE" => "string"),
				"GROUP_ID" => array("FIELD" => "B.GROUP_ID", "TYPE" => "int"),
				"ENABLE_COMMENTS" => array("FIELD" => "B.ENABLE_COMMENTS", "TYPE" => "char"),
				"ENABLE_IMG_VERIF" => array("FIELD" => "B.ENABLE_IMG_VERIF", "TYPE" => "char"),
				"EMAIL_NOTIFY" => array("FIELD" => "B.EMAIL_NOTIFY", "TYPE" => "char"),
				"ENABLE_RSS" => array("FIELD" => "B.ENABLE_RSS", "TYPE" => "char"),
				"ALLOW_HTML" => array("FIELD" => "B.ALLOW_HTML", "TYPE" => "char"),
				"LAST_POST_ID" => array("FIELD" => "B.LAST_POST_ID", "TYPE" => "int"),
				"LAST_POST_DATE" => array("FIELD" => "B.LAST_POST_DATE", "TYPE" => "datetime"),
				"AUTO_GROUPS" => array("FIELD" => "B.AUTO_GROUPS", "TYPE" => "string"),
				"SEARCH_INDEX" => array("FIELD" => "B.SEARCH_INDEX", "TYPE" => "char"),
				"USE_SOCNET" => array("FIELD" => "B.USE_SOCNET", "TYPE" => "char"),

				"OWNER_LOGIN" => array("FIELD" => "U.LOGIN", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U ON (B.OWNER_ID = U.ID)"),
				"OWNER_NAME" => array("FIELD" => "U.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U ON (B.OWNER_ID = U.ID)"),
				"OWNER_LAST_NAME" => array("FIELD" => "U.LAST_NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U ON (B.OWNER_ID = U.ID)"),
				"OWNER_SECOND_NAME" => array("FIELD" => "U.SECOND_NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U ON (B.OWNER_ID = U.ID)"),
				"OWNER_EMAIL" => array("FIELD" => "U.EMAIL", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U ON (B.OWNER_ID = U.ID)"),
				"OWNER" => array("FIELD" => "U.LOGIN,U.NAME,U.LAST_NAME,U.EMAIL,U.ID", "WHERE_ONLY" => "Y", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U ON (B.OWNER_ID = U.ID)"),

				"GROUP_NAME" => array("FIELD" => "G.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_blog_group G ON (B.GROUP_ID = G.ID)"),
				"GROUP_SITE_ID" => array("FIELD" => "G.SITE_ID", "TYPE" => "string", "FROM" => "INNER JOIN b_blog_group G ON (B.GROUP_ID = G.ID)"),

				"BLOG_USER_ALIAS" => array("FIELD" => "BU.ALIAS", "TYPE" => "string", "FROM" => "LEFT JOIN b_blog_user BU ON (B.OWNER_ID = BU.USER_ID)"),
				"BLOG_USER_AVATAR" => array("FIELD" => "BU.AVATAR", "TYPE" => "int", "FROM" => "LEFT JOIN b_blog_user BU ON (B.OWNER_ID = BU.USER_ID)"),
				
				"SOCNET_BLOG_READ" => array("FIELD" => "BS.BLOG_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_blog_socnet BS ON (B.ID = BS.BLOG_ID)"),
				"PERMS" => Array(),
			);
		// <-- FIELDS
		
		if(isset($USER) && is_object($USER) && $USER->IsAuthorized())
		{
			$arFields["PERMS"] = Array(
				"FIELD" => "bugp.PERMS",
				"TYPE" => "char",
				"FROM" => "INNER JOIN b_blog_user_group_perms bugp ON (B.ID = bugp.BLOG_ID)
							INNER JOIN b_blog_user2user_group bug ON (B.ID = bug.BLOG_ID AND bugp.USER_GROUP_ID = bug.USER_GROUP_ID)",
			);
			
			$arFields["PERMS_TYPE"] = Array(
				"FIELD" => "bugp.PERMS_TYPE",
				"TYPE" => "char",
				"FROM" => "INNER JOIN b_blog_user_group_perms bugp ON (B.ID = bugp.BLOG_ID)
							INNER JOIN b_blog_user2user_group bug ON (B.ID = bug.BLOG_ID AND bugp.USER_GROUP_ID = bug.USER_GROUP_ID)",
			);
			
			$arFields["PERMS_USER_ID"] = Array(
				"FIELD" => "bug.USER_ID",
				"TYPE" => "int",
				"FROM" => "INNER JOIN b_blog_user_group_perms bugp ON (B.ID = bugp.BLOG_ID)
							INNER JOIN b_blog_user2user_group bug ON (B.ID = bug.BLOG_ID AND bugp.USER_GROUP_ID = bug.USER_GROUP_ID)",
			);
			
			$arFields["PERMS_POST_ID"] = Array(
				"FIELD" => "bugp.POST_ID",
				"TYPE" => "int",
				"FROM" => "INNER JOIN b_blog_user_group_perms bugp ON (B.ID = bugp.BLOG_ID)
							INNER JOIN b_blog_user2user_group bug ON (B.ID = bug.BLOG_ID AND bugp.USER_GROUP_ID = bug.USER_GROUP_ID)",
			);
		}

		$arSqls = CBlog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields, $obUserFieldsSql);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		$r = $obUserFieldsSql->GetFilter();
		if(strlen($r)>0)
			$strSqlUFFilter = " (".$r.") ";

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
					$obUserFieldsSql->GetSelect()." ".
				"FROM b_blog B ".
				"	".$arSqls["FROM"]." ".
					$obUserFieldsSql->GetJoin("B.ID")." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "WHERE ".$arSqls["WHERE"]." ";
			if(strlen($arSqls["WHERE"]) > 0 && strlen($strSqlUFFilter) > 0)
				$strSql .= " AND ".$strSqlUFFilter." ";
			elseif(strlen($arSqls["WHERE"]) <= 0 && strlen($strSqlUFFilter) > 0)
				$strSql .= " WHERE ".$strSqlUFFilter." ";

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
				$obUserFieldsSql->GetSelect()." ".
			"FROM b_blog B ".
			"	".$arSqls["FROM"]." ".
				$obUserFieldsSql->GetJoin("B.ID")." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if(strlen($arSqls["WHERE"]) > 0 && strlen($strSqlUFFilter) > 0)
			$strSql .= " AND ".$strSqlUFFilter." ";
		elseif(strlen($arSqls["WHERE"]) <= 0 && strlen($strSqlUFFilter) > 0)
			$strSql .= " WHERE ".$strSqlUFFilter." ";

		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])<=0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_blog B ".
				"	".$arSqls["FROM"]." ".
				$obUserFieldsSql->GetJoin("B.ID")." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";
			if(strlen($arSqls["WHERE"]) > 0 && strlen($strSqlUFFilter) > 0)
				$strSql_tmp .= " AND ".$strSqlUFFilter." ";
			elseif(strlen($arSqls["WHERE"]) <= 0 && strlen($strSqlUFFilter) > 0)
				$strSql_tmp .= " WHERE ".$strSqlUFFilter." ";

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

			$dbRes->SetUserFields($USER_FIELD_MANAGER->GetUserFields("BLOG_BLOG"));
			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) > 0)
				$strSql .= "LIMIT ".IntVal($arNavStartParams["nTopCount"]);

			//echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$dbRes->SetUserFields($USER_FIELD_MANAGER->GetUserFields("BLOG_BLOG"));
		}
		//echo "!4!=".htmlspecialcharsbx($strSql)."<br>";

		return $dbRes;
	}
	
	public static function AddSocnetRead($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		if($ID <= 0)
			return false;
			
		if(CBlog::GetSocnetReadByBlog($ID))
			return true;
		$strSql =
			"INSERT INTO b_blog_socnet(BLOG_ID) ".
			"VALUES(".$ID.")";
		if($DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__))
			return true;
		
		return false;
	}
}
?>