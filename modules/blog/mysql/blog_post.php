<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/blog/general/blog_post.php");

class CBlogPost extends CAllBlogPost
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

		if (!CBlogPost::CheckFields("ADD", $arFields))
			return false;
		elseif(!$GLOBALS["USER_FIELD_MANAGER"]->CheckFields("BLOG_POST", 0, $arFields))
			return false;

		foreach(GetModuleEvents("blog", "OnBeforePostAdd", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, Array(&$arFields))===false)
				return false;
		}

		if (
			array_key_exists("ATTACH_IMG", $arFields)
			&& is_array($arFields["ATTACH_IMG"])
			&& (
				!array_key_exists("MODULE_ID", $arFields["ATTACH_IMG"])
				|| strlen($arFields["ATTACH_IMG"]["MODULE_ID"]) <= 0
			)
		)
			$arFields["ATTACH_IMG"]["MODULE_ID"] = "blog";

		$prefix = "blog";
		if(strlen($arFields["URL"]) > 0)
			$prefix .= "/".$arFields["URL"];

		CFile::SaveForDB($arFields, "ATTACH_IMG", $prefix);

		$arInsert = $DB->PrepareInsert("b_blog_post", $arFields);

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
				"INSERT INTO b_blog_post(".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			$ID = IntVal($DB->LastID());

			foreach(GetModuleEvents("blog", "OnBeforePostUserFieldUpdate", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, Array("BLOG_POST", $ID, $arFields));

			$GLOBALS["USER_FIELD_MANAGER"]->Update("BLOG_POST", $ID, $arFields);
		}

		if ($ID)
		{
			$arPost = CBlogPost::GetByID($ID);
			CBlog::SetStat($arPost["BLOG_ID"]);

			CBlogPost::SetPostPerms($ID, $arFields["PERMS_POST"], BLOG_PERMS_POST);
			CBlogPost::SetPostPerms($ID, $arFields["PERMS_COMMENT"], BLOG_PERMS_COMMENT);

			$arFields["SC_PERM"] = Array();
			if(array_key_exists("SOCNET_RIGHTS", $arFields))
				$arFields["SC_PERM"] = CBlogPost::AddSocNetPerms($ID, $arFields["SOCNET_RIGHTS"], $arPost);

			foreach(GetModuleEvents("blog", "OnPostAdd", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, Array($ID, &$arFields));

			if (CModule::IncludeModule("search"))
			{
				if ($arPost["DATE_PUBLISHED"] == "Y"
					&& $arPost["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH
					&& CBlogUserGroup::GetGroupPerms(1, $arPost["BLOG_ID"], $ID, BLOG_PERMS_POST) >= BLOG_PERMS_READ)
				{
					$tag = "";
					$arBlog = CBlog::GetByID($arPost["BLOG_ID"]);
					if($arBlog["SEARCH_INDEX"] == "Y")
					{
						$arGroup = CBlogGroup::GetByID($arBlog["GROUP_ID"]);

						if(strlen($arFields["PATH"]) > 0)
						{
							$arFields["PATH"] = (
								strlen($arFields["CODE"]) > 0 
									? str_replace("#post_id#", $arFields["CODE"], $arFields["PATH"]) 
									: str_replace("#post_id#", $ID, $arFields["PATH"])
							);

							$arPostSite = array(
								$arGroup["SITE_ID"] => $arFields["PATH"]
							);
						}
						else
						{
							$arPostSite = array(
								$arGroup["SITE_ID"] => CBlogPost::PreparePath(
										$arBlog["URL"],
										$arPost["ID"],
										$arGroup["SITE_ID"],
										false,
										$arBlog["OWNER_ID"],
										$arBlog["SOCNET_GROUP_ID"]
									)
							);
						}

						if (
							$arBlog["USE_SOCNET"] == "Y" 
							&& CModule::IncludeModule("extranet")
						)
						{
							$arPostSiteExt = CExtranet::GetSitesByLogDestinations($arFields["SC_PERM"]);
							foreach($arPostSiteExt as $lid)
							{
								if (!array_key_exists($lid, $arPostSite))
								{
									$arPostSite[$lid] = str_replace(
										array("#user_id#", "#post_id#"), 
										array($arBlog["OWNER_ID"], $arPost["ID"]), 
										COption::GetOptionString("socialnetwork", "userblogpost_page", false, $lid)
									);
								}
							}
						}

						if(strlen($arPost["CATEGORY_ID"])>0)
						{
							$arC = explode(",", $arPost["CATEGORY_ID"]);
							$arTag = Array();
							foreach($arC as $v)
							{
								$arCategory = CBlogCategory::GetByID($v);
								$arTag[] = $arCategory["NAME"];
							}
							$tag =  implode(",", $arTag);
						}

						$searchContent = blogTextParser::killAllTags($arPost["DETAIL_TEXT"]);
						$searchContent .= "\r\n" . $GLOBALS["USER_FIELD_MANAGER"]->OnSearchIndex("BLOG_POST", $arPost["ID"]);

						$authorName = "";
						if(IntVal($arPost["AUTHOR_ID"]) > 0)
						{
							$dbUser = CUser::GetByID($arPost["AUTHOR_ID"]);
							if($arUser = $dbUser->Fetch())
							{
								$arTmpUser = array(
										"NAME" => $arUser["NAME"],
										"LAST_NAME" => $arUser["LAST_NAME"],
										"SECOND_NAME" => $arUser["SECOND_NAME"],
										"LOGIN" => $arUser["LOGIN"],
									);
								$authorName = CUser::FormatName(CSite::GetNameFormat(), $arTmpUser, false, false);
								if(strlen($authorName) > 0)
									$searchContent .= "\r\n".$authorName;
							}
						}

						$arSearchIndex = array(
							"SITE_ID" => $arPostSite,
							"LAST_MODIFIED" => $arPost["DATE_PUBLISH"],
							"PARAM1" => "POST",
							"PARAM2" => $arPost["BLOG_ID"],
							"PARAM3" => $arPost["ID"],
							"PERMISSIONS" => array(2),
							"TITLE" => blogTextParser::killAllTags($arPost["TITLE"]),
							"BODY" => $searchContent,
							"TAGS" => $tag,
							"USER_ID" => $arPost["AUTHOR_ID"],
							"ENTITY_TYPE_ID" => "BLOG_POST",
							"ENTITY_ID" => $arPost["ID"],
						);

						if($arBlog["USE_SOCNET"] == "Y")
						{
							if(is_array($arFields["SC_PERM"]))
							{
								$arSearchIndex["PERMISSIONS"] = $arFields["SC_PERM"];
								if(!in_array("U".$arPost["AUTHOR_ID"], $arSearchIndex["PERMISSIONS"]))
									$arSearchIndex["PERMISSIONS"][] = "U".$arPost["AUTHOR_ID"];

								$sgId = array();
								foreach($arFields["SC_PERM"] as $perm)
								{
									if(strpos($perm, "SG") !== false)
									{
										$sgIdTmp = str_replace("SG", "", substr($perm, 0, strpos($perm, "_")));
										if(!in_array($sgIdTmp, $sgId) && IntVal($sgIdTmp) > 0)
											$sgId[] = $sgIdTmp;
									}
								}

								if(!empty($sgId))
								{
									$arSearchIndex["PARAMS"] = array(
										"socnet_group" => $sgId,
										"entity" => "socnet_group",
									);
								}
							}

							// get mentions and grats
							$arMentionedUserID = CBlogPost::GetMentionedUserID($arPost);
							if (!empty($arMentionedUserID))
							{
								if (!isset($arSearchIndex["PARAMS"]))
								{
									$arSearchIndex["PARAMS"] = array();
								}
								$arSearchIndex["PARAMS"]["mentioned_user_id"] = $arMentionedUserID;
							}
						}

						CSearch::Index("blog", "P".$ID, $arSearchIndex);
					}
				}
			}
		}

		return $ID;
	}

	public static function Update($ID, $arFields, $bSearchIndex = true)
	{
		global $DB;

		$ID = IntVal($ID);
		if(strlen($arFields["PATH"]) > 0)
			$arFields["PATH"] = str_replace("#post_id#", $ID, $arFields["PATH"]);

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1) == "=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CBlogPost::CheckFields("UPDATE", $arFields, $ID))
			return false;
		elseif(!$GLOBALS["USER_FIELD_MANAGER"]->CheckFields("BLOG_POST", $ID, $arFields))
			return false;

		foreach(GetModuleEvents("blog", "OnBeforePostUpdate", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, Array($ID, &$arFields))===false)
				return false;
		}

		$arOldPost = CBlogPost::GetByID($ID);

		if(is_array($arFields["ATTACH_IMG"]))
		{
			if (
				!array_key_exists("MODULE_ID", $arFields["ATTACH_IMG"])
				|| strlen($arFields["ATTACH_IMG"]["MODULE_ID"]) <= 0
			)
				$arFields["ATTACH_IMG"]["MODULE_ID"] = "blog";

			$prefix = "blog";
			if(strlen($arFields["URL"]) > 0)
				$prefix .= "/".$arFields["URL"];
			CFile::SaveForDB($arFields, "ATTACH_IMG", $prefix);
		}

		$strUpdate = $DB->PrepareUpdate("b_blog_post", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate) > 0)
				$strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		if (strlen($strUpdate) > 0)
		{
			$oldPostPerms = CBlogUserGroup::GetGroupPerms(1, $arOldPost["BLOG_ID"], $ID, BLOG_PERMS_POST);

			$strSql =
				"UPDATE b_blog_post SET ".
				"	".$strUpdate." ".
				"WHERE ID = ".$ID." ";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			unset(static::$arBlogPostCache[$ID]);

			foreach(GetModuleEvents("blog", "OnBeforePostUserFieldUpdate", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, Array("BLOG_POST", $ID, $arFields));

			$GLOBALS["USER_FIELD_MANAGER"]->Update("BLOG_POST", $ID, $arFields);
		}
		else
		{
			$ID = False;
		}

		if ($ID)
		{
			$arNewPost = CBlogPost::GetByID($ID);
			if($arNewPost["PUBLISH_STATUS"] != $arOldPost["PUBLISH_STATUS"]  || $arNewPost["BLOG_ID"] != $arOldPost["BLOG_ID"])
				CBlog::SetStat($arNewPost["BLOG_ID"]);

			if ($arNewPost["BLOG_ID"] != $arOldPost["BLOG_ID"])
				CBlog::SetStat($arOldPost["BLOG_ID"]);

			if (is_set($arFields, "PERMS_POST"))
				CBlogPost::SetPostPerms($ID, $arFields["PERMS_POST"], BLOG_PERMS_POST);
			if (is_set($arFields, "PERMS_COMMENT"))
				CBlogPost::SetPostPerms($ID, $arFields["PERMS_COMMENT"], BLOG_PERMS_COMMENT);

			if(array_key_exists("SOCNET_RIGHTS", $arFields))
			{
				$arFields["SC_PERM_OLD"] = CBlogPost::GetSocNetPermsCode($ID);
				$arFields["SC_PERM"] = CBlogPost::UpdateSocNetPerms($ID, $arFields["SOCNET_RIGHTS"], $arNewPost);
			}

			foreach(GetModuleEvents("blog", "OnPostUpdate", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, Array($ID, &$arFields));

			if ($bSearchIndex && CModule::IncludeModule("search"))
			{
				$newPostPerms = CBlogUserGroup::GetGroupPerms(1, $arNewPost["BLOG_ID"], $ID, BLOG_PERMS_POST);
				$arBlog = CBlog::GetByID($arNewPost["BLOG_ID"]);

				if (
					$arOldPost["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH &&
					$oldPostPerms >= BLOG_PERMS_READ
					&& (
						$arNewPost["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_PUBLISH ||
						$newPostPerms < BLOG_PERMS_READ
						)
					|| $arBlog["SEARCH_INDEX"] != "Y"
					)
				{
					CSearch::Index("blog", "P".$ID,
						array(
							"TITLE" => "",
							"BODY" => ""
						)
					);
					CSearch::DeleteIndex("blog", false, "COMMENT", $arBlog["ID"]."|".$ID);
				}
				elseif (
					$arNewPost["DATE_PUBLISHED"] == "Y"
					&& $arNewPost["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH
					&& $newPostPerms >= BLOG_PERMS_READ
					&& $arBlog["SEARCH_INDEX"] == "Y"
					)
				{
					$tag = "";
					$arGroup = CBlogGroup::GetByID($arBlog["GROUP_ID"]);
					if(strlen($arFields["PATH"]) > 0)
					{
						$arPostSite = array($arGroup["SITE_ID"] => $arFields["PATH"]);
					}
					elseif(strlen($arNewPost["PATH"]) > 0)
					{
						$arNewPost["PATH"] = (
							strlen($arNewPost["CODE"]) > 0 
								? str_replace("#post_id#", $arNewPost["CODE"], $arNewPost["PATH"]) 
								: str_replace("#post_id#", $ID, $arNewPost["PATH"])
						);
						$arPostSite = array($arGroup["SITE_ID"] => $arNewPost["PATH"]);
					}
					else
					{
						$arPostSite = array(
							$arGroup["SITE_ID"] => CBlogPost::PreparePath(
									$arBlog["URL"],
									$arNewPost["ID"],
									$arGroup["SITE_ID"],
									false,
									$arBlog["OWNER_ID"],
									$arBlog["SOCNET_GROUP_ID"]
								)
						);
					}

					if (
						$arBlog["USE_SOCNET"] == "Y" 
						&& CModule::IncludeModule("extranet")
					)
					{
						$arPostSiteExt = CExtranet::GetSitesByLogDestinations($arFields["SC_PERM"]);
						foreach($arPostSiteExt as $lid)
						{
							if (!array_key_exists($lid, $arPostSite))
							{
								$arPostSite[$lid] = str_replace(
									array("#user_id#", "#post_id#"), 
									array($arBlog["OWNER_ID"], $arNewPost["ID"]), 
									COption::GetOptionString("socialnetwork", "userblogpost_page", false, $lid)
								);
							}
						}
					}

					if(strlen($arNewPost["CATEGORY_ID"])>0)
					{
						$arC = explode(",", $arNewPost["CATEGORY_ID"]);
						$arTag = Array();
						foreach($arC as $v)
						{
							$arCategory = CBlogCategory::GetByID($v);
							$arTag[] = $arCategory["NAME"];
						}
						$tag =  implode(",", $arTag);
					}

					$searchContent = blogTextParser::killAllTags($arNewPost["DETAIL_TEXT"]);
					$searchContent .= "\r\n" . $GLOBALS["USER_FIELD_MANAGER"]->OnSearchIndex("BLOG_POST", $arNewPost["ID"]);

					$authorName = "";
					if(IntVal($arNewPost["AUTHOR_ID"]) > 0)
					{
						$dbUser = CUser::GetByID($arNewPost["AUTHOR_ID"]);
						if($arUser = $dbUser->Fetch())
						{
							$arTmpUser = array(
									"NAME" => $arUser["NAME"],
									"LAST_NAME" => $arUser["LAST_NAME"],
									"SECOND_NAME" => $arUser["SECOND_NAME"],
									"LOGIN" => $arUser["LOGIN"],
								);
							$authorName = CUser::FormatName(CSite::GetNameFormat(), $arTmpUser, false, false);
							if(strlen($authorName) > 0)
								$searchContent .= "\r\n".$authorName;
						}
					}

					$arSearchIndex = array(
						"SITE_ID" => $arPostSite,
						"LAST_MODIFIED" => $arNewPost["DATE_PUBLISH"],
						"PARAM1" => "POST",
						"PARAM2" => $arNewPost["BLOG_ID"],
						"PARAM3" => $arNewPost["ID"],
						"PERMISSIONS" => array(2),
						"TITLE" => $arNewPost["TITLE"],
						"BODY" => $searchContent,
						"TAGS" => $tag,
						"USER_ID" => $arNewPost["AUTHOR_ID"],
						"ENTITY_TYPE_ID" => "BLOG_POST",
						"ENTITY_ID" => $arNewPost["ID"],
					);

					$bIndexComment = false;
					if($arBlog["USE_SOCNET"] == "Y")
					{
						if(!empty($arFields["SC_PERM"]))
						{
							$arSearchIndex["PERMISSIONS"] = $arFields["SC_PERM"];
							if($arFields["SC_PERM"] != $arFields["SC_PERM_OLD"])
								$bIndexComment = true;
						}
						else
							$arSearchIndex["PERMISSIONS"] = CBlogPost::GetSocnetPermsCode($ID);

						if(!in_array("U".$arNewPost["AUTHOR_ID"], $arSearchIndex["PERMISSIONS"]))
							$arSearchIndex["PERMISSIONS"][] = "U".$arNewPost["AUTHOR_ID"];

						if(is_array($arSearchIndex["PERMISSIONS"]))
						{
							$sgId = array();
							foreach($arSearchIndex["PERMISSIONS"] as $perm)
							{
								if(strpos($perm, "SG") !== false)
								{
									$sgIdTmp = str_replace("SG", "", substr($perm, 0, strpos($perm, "_")));
									if(!in_array($sgIdTmp, $sgId) && IntVal($sgIdTmp) > 0)
										$sgId[] = $sgIdTmp;
								}
							}

							if(!empty($sgId))
							{
								$arSearchIndex["PARAMS"] = array(
									"socnet_group" => $sgId,
									"entity" => "socnet_group",
								);
							}
						}

						// get mentions and grats
						$arMentionedUserID = CBlogPost::GetMentionedUserID($arNewPost);
						if (!empty($arMentionedUserID))
						{
							if (!isset($arSearchIndex["PARAMS"]))
							{
								$arSearchIndex["PARAMS"] = array();
							}
							$arSearchIndex["PARAMS"]["mentioned_user_id"] = $arMentionedUserID;
						}
					}

					CSearch::Index("blog", "P".$ID, $arSearchIndex, True);

					if(($arOldPost["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_PUBLISH && $arNewPost["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH) || $bIndexComment) //index comments
					{
						$arParamsComment = Array(
							"BLOG_ID" => $arBlog["ID"],
							"POST_ID" => $ID,
							"SITE_ID" => $arGroup["SITE_ID"],
							"PATH" => $arPostSite[$arGroup["SITE_ID"]]."?commentId=#comment_id###comment_id#",
							"BLOG_URL" => $arBlog["URL"],
							"OWNER_ID" => $arBlog["OWNER_ID"],
							"SOCNET_GROUP_ID" => $arBlog["SOCNET_GROUP_ID"],
							"USE_SOCNET" => $arBlog["USE_SOCNET"],
						);

						CBlogComment::_IndexPostComments($arParamsComment);
					}
				}
			}
		}

		BXClearCache(true, '/blog/socnet_post/gen/'.intval($ID / 100)."/".$ID);

		return $ID;
	}

	//*************** SELECT *********************/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		if (
			!empty(static::$arBlogPostCache[$ID])
			&& is_set(static::$arBlogPostCache[$ID], "ID")
		)
		{
			return static::$arBlogPostCache[$ID];
		}
		else
		{
			static $strSql;
			if (!isset($strSql))
			$strSql =
				"SELECT P.*, IF(P.DATE_PUBLISH <= NOW(), 'Y', 'N') as DATE_PUBLISHED, ".
				"	".$DB->DateToCharFunction("P.DATE_CREATE", "FULL")." as DATE_CREATE, ".
				"	".$DB->DateToCharFunction("P.DATE_PUBLISH", "FULL")." as DATE_PUBLISH ".
				"FROM b_blog_post P ".
				"WHERE P.ID = ";
			$dbResult = $DB->Query($strSql.$ID, False, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arResult = $dbResult->Fetch())
			{
				static::$arBlogPostCache[$ID] = $arResult;
				return $arResult;
			}
		}

		return False;
	}

	public static function GetList($arOrder = Array("ID" => "DESC"), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB, $USER_FIELD_MANAGER, $USER;

		$obUserFieldsSql = new CUserTypeSQL;
		$obUserFieldsSql->SetEntity("BLOG_POST", "P.ID");
		$obUserFieldsSql->SetSelect($arSelectFields);
		$obUserFieldsSql->SetFilter($arFilter);
		$obUserFieldsSql->SetOrder($arOrder);

		if (isset($arFilter["DATE_PUBLISH_DAY"]) && isset($arFilter["DATE_PUBLISH_MONTH"]) && isset($arFilter["DATE_PUBLISH_YEAR"]))
		{
			if (strlen($arFilter["DATE_PUBLISH_YEAR"]) == 2)
				$arFilter["DATE_PUBLISH_YEAR"] = "20".$arFilter["DATE_PUBLISH_YEAR"];
			$date1 = mktime(0, 0, 0, $arFilter["DATE_PUBLISH_MONTH"], $arFilter["DATE_PUBLISH_DAY"], $arFilter["DATE_PUBLISH_YEAR"]);
			$date2 = mktime(0, 0, 0, $arFilter["DATE_PUBLISH_MONTH"], $arFilter["DATE_PUBLISH_DAY"] + 1, $arFilter["DATE_PUBLISH_YEAR"]);
			$arFilter[">=DATE_PUBLISH"] = ConvertTimeStamp($date1, "SHORT", SITE_ID);
			$arFilter["<DATE_PUBLISH"] = ConvertTimeStamp($date2, "SHORT", SITE_ID);

			unset($arFilter["DATE_PUBLISH_DAY"]);
			unset($arFilter["DATE_PUBLISH_MONTH"]);
			unset($arFilter["DATE_PUBLISH_YEAR"]);
		}
		elseif (isset($arFilter["DATE_PUBLISH_MONTH"]) && isset($arFilter["DATE_PUBLISH_YEAR"]))
		{
			if (strlen($arFilter["DATE_PUBLISH_YEAR"]) == 2)
				$arFilter["DATE_PUBLISH_YEAR"] = "20".$arFilter["DATE_PUBLISH_YEAR"];
			$date1 = mktime(0, 0, 0, $arFilter["DATE_PUBLISH_MONTH"], 1, $arFilter["DATE_PUBLISH_YEAR"]);
			$date2 = mktime(0, 0, 0, $arFilter["DATE_PUBLISH_MONTH"] + 1, 1, $arFilter["DATE_PUBLISH_YEAR"]);
			$arFilter[">=DATE_PUBLISH"] = ConvertTimeStamp($date1, "SHORT", SITE_ID);
			$arFilter["<DATE_PUBLISH"] = ConvertTimeStamp($date2, "SHORT", SITE_ID);

			unset($arFilter["DATE_PUBLISH_MONTH"]);
			unset($arFilter["DATE_PUBLISH_YEAR"]);
		}
		elseif (isset($arFilter["DATE_PUBLISH_YEAR"]))
		{
			if (strlen($arFilter["DATE_PUBLISH_YEAR"]) == 2)
				$arFilter["DATE_PUBLISH_YEAR"] = "20".$arFilter["DATE_PUBLISH_YEAR"];
			$date1 = mktime(0, 0, 0, 1, 1, $arFilter["DATE_PUBLISH_YEAR"]);
			$date2 = mktime(0, 0, 0, 1, 1, $arFilter["DATE_PUBLISH_YEAR"] + 1);
			$arFilter[">=DATE_PUBLISH"] = ConvertTimeStamp($date1, "SHORT", SITE_ID);
			$arFilter["<DATE_PUBLISH"] = ConvertTimeStamp($date2, "SHORT", SITE_ID);

			unset($arFilter["DATE_PUBLISH_YEAR"]);
		}

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "TITLE", "BLOG_ID", "AUTHOR_ID", "PREVIEW_TEXT", "PREVIEW_TEXT_TYPE", "DETAIL_TEXT", "DETAIL_TEXT_TYPE", "DATE_CREATE", "DATE_PUBLISH", "KEYWORDS", "PUBLISH_STATUS", "ATRIBUTE", "ATTACH_IMG", "ENABLE_TRACKBACK", "ENABLE_COMMENTS", "VIEWS", "NUM_COMMENTS", "CODE", "MICRO");
		if(in_array("*", $arSelectFields))
			$arSelectFields = array("ID", "TITLE", "BLOG_ID", "AUTHOR_ID", "PREVIEW_TEXT", "PREVIEW_TEXT_TYPE", "DETAIL_TEXT", "DETAIL_TEXT_TYPE", "DATE_CREATE", "DATE_PUBLISH", "KEYWORDS", "PUBLISH_STATUS", "ATRIBUTE", "ATTACH_IMG", "ENABLE_TRACKBACK", "ENABLE_COMMENTS", "NUM_COMMENTS", "NUM_TRACKBACKS", "VIEWS", "FAVORITE_SORT", "CATEGORY_ID", "PERMS", "AUTHOR_LOGIN", "AUTHOR_NAME", "AUTHOR_LAST_NAME", "AUTHOR_SECOND_NAME", "AUTHOR_EMAIL", "AUTHOR", "BLOG_USER_ALIAS", "BLOG_USER_AVATAR", "BLOG_URL", "BLOG_OWNER_ID", "BLOG_ACTIVE", "BLOG_GROUP_ID", "BLOG_GROUP_SITE_ID", "BLOG_SOCNET_GROUP_ID", "BLOG_ENABLE_RSS", "BLOG_USE_SOCNET", "CODE", "MICRO");

		if((array_key_exists("BLOG_GROUP_SITE_ID", $arFilter) || in_array("BLOG_GROUP_SITE_ID", $arSelectFields)) && !in_array("BLOG_URL", $arSelectFields))
			$arSelectFields[] = "BLOG_URL";

		// FIELDS -->
		$arFields = array(
			"ID" => array("FIELD" => "P.ID", "TYPE" => "int"),
			"TITLE" => array("FIELD" => "P.TITLE", "TYPE" => "string"),
			"CODE" => array("FIELD" => "P.CODE", "TYPE" => "string"),
			"BLOG_ID" => array("FIELD" => "P.BLOG_ID", "TYPE" => "int"),
			"AUTHOR_ID" => array("FIELD" => "P.AUTHOR_ID", "TYPE" => "int"),
			"PREVIEW_TEXT" => array("FIELD" => "P.PREVIEW_TEXT", "TYPE" => "string"),
			"PREVIEW_TEXT_TYPE" => array("FIELD" => "P.PREVIEW_TEXT_TYPE", "TYPE" => "string"),
			"DETAIL_TEXT" => array("FIELD" => "P.DETAIL_TEXT", "TYPE" => "string"),
			"DETAIL_TEXT_TYPE" => array("FIELD" => "P.DETAIL_TEXT_TYPE", "TYPE" => "string"),
			"DATE_CREATE" => array("FIELD" => "P.DATE_CREATE", "TYPE" => "datetime"),
			"DATE_PUBLISH" => array("FIELD" => "P.DATE_PUBLISH", "TYPE" => "datetime"),
			"KEYWORDS" => array("FIELD" => "P.KEYWORDS", "TYPE" => "string"),
			"PUBLISH_STATUS" => array("FIELD" => "P.PUBLISH_STATUS", "TYPE" => "string"),
			"ATRIBUTE" => array("FIELD" => "P.ATRIBUTE", "TYPE" => "string"),
			"ATTACH_IMG" => array("FIELD" => "P.ATTACH_IMG", "TYPE" => "int"),
			"ENABLE_TRACKBACK" => array("FIELD" => "P.ENABLE_TRACKBACK", "TYPE" => "string"),
			"ENABLE_COMMENTS" => array("FIELD" => "P.ENABLE_COMMENTS", "TYPE" => "string"),
			"NUM_COMMENTS" => array("FIELD" => "P.NUM_COMMENTS", "TYPE" => "int"),
			"NUM_TRACKBACKS" => array("FIELD" => "P.NUM_TRACKBACKS", "TYPE" => "int"),
			"VIEWS" => array("FIELD" => "P.VIEWS", "TYPE" => "int"),
			"FAVORITE_SORT" => array("FIELD" => "P.FAVORITE_SORT", "TYPE" => "int"),
			"CATEGORY_ID" => array("FIELD" => "P.CATEGORY_ID", "TYPE" => "string"),
			"PATH" => array("FIELD" => "P.PATH", "TYPE" => "string"),
			"MICRO" => array("FIELD" => "P.MICRO", "TYPE" => "string"),
			"HAS_IMAGES" => array("FIELD" => "P.HAS_IMAGES", "TYPE" => "string"),
			"HAS_PROPS" => array("FIELD" => "P.HAS_PROPS", "TYPE" => "string"),
			"HAS_TAGS" => array("FIELD" => "P.HAS_TAGS", "TYPE" => "string"),
			"HAS_COMMENT_IMAGES" => array("FIELD" => "P.HAS_COMMENT_IMAGES", "TYPE" => "string"),
			"HAS_SOCNET_ALL" => array("FIELD" => "P.HAS_SOCNET_ALL", "TYPE" => "string"),
			"SEO_TITLE" => array("FIELD" => "P.SEO_TITLE", "TYPE" => "string"),
			"SEO_TAGS" => array("FIELD" => "P.SEO_TAGS", "TYPE" => "string"),
			"SEO_DESCRIPTION" => array("FIELD" => "P.SEO_DESCRIPTION", "TYPE" => "string"),

			"PERMS" => array(),

			"AUTHOR_LOGIN" => array("FIELD" => "U.LOGIN", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U ON (P.AUTHOR_ID = U.ID)"),
			"AUTHOR_NAME" => array("FIELD" => "U.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U ON (P.AUTHOR_ID = U.ID)"),
			"AUTHOR_LAST_NAME" => array("FIELD" => "U.LAST_NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U ON (P.AUTHOR_ID = U.ID)"),
			"AUTHOR_SECOND_NAME" => array("FIELD" => "U.SECOND_NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U ON (P.AUTHOR_ID = U.ID)"),
			"AUTHOR_EMAIL" => array("FIELD" => "U.EMAIL", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U ON (P.AUTHOR_ID = U.ID)"),
			"AUTHOR" => array("FIELD" => "U.LOGIN, U.NAME, U.LAST_NAME, U.EMAIL, U.ID", "WHERE_ONLY" => "Y", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U ON (P.AUTHOR_ID = U.ID)"),

			"CATEGORY_NAME" => array("FIELD" => "PCN.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_blog_category PCN ON (P.CATEGORY_ID = PCN.ID)"),
			"CATEGORY_ID_F" => array("FIELD" => "PC.CATEGORY_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_blog_post_category PC ON (PC.POST_ID = P.ID)"),

			"BLOG_USER_ALIAS" => array("FIELD" => "BU.ALIAS", "TYPE" => "string", "FROM" => "LEFT JOIN b_blog_user BU ON (P.AUTHOR_ID = BU.USER_ID)"),
			"BLOG_USER_AVATAR" => array("FIELD" => "BU.AVATAR", "TYPE" => "int", "FROM" => "LEFT JOIN b_blog_user BU ON (P.AUTHOR_ID = BU.USER_ID)"),

			"BLOG_URL" => array("FIELD" => "B.URL", "TYPE" => "string", "FROM" => "INNER JOIN b_blog B ON (P.BLOG_ID = B.ID)"),
			"BLOG_OWNER_ID" => array("FIELD" => "B.OWNER_ID", "TYPE" => "string", "FROM" => "INNER JOIN b_blog B ON (P.BLOG_ID = B.ID)"),
			"BLOG_ACTIVE" => array("FIELD" => "B.ACTIVE", "TYPE" => "string", "FROM" => "INNER JOIN b_blog B ON (P.BLOG_ID = B.ID)"),
			"BLOG_GROUP_ID" => array("FIELD" => "B.GROUP_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_blog B ON (P.BLOG_ID = B.ID)"),
			"BLOG_ENABLE_RSS" => array("FIELD" => "B.ENABLE_RSS", "TYPE" => "string", "FROM" => "INNER JOIN b_blog B ON (P.BLOG_ID = B.ID)"),
			"BLOG_USE_SOCNET" => array("FIELD" => "B.USE_SOCNET", "TYPE" => "string", "FROM" => "INNER JOIN b_blog B ON (P.BLOG_ID = B.ID)"),
			"BLOG_NAME" => array("FIELD" => "B.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_blog B ON (P.BLOG_ID = B.ID)"),

			"BLOG_GROUP_SITE_ID" => array("FIELD" => "BG.SITE_ID", "TYPE" => "string", "FROM" => "INNER JOIN b_blog_group BG ON (B.GROUP_ID = BG.ID)"),

			"SOCNET_BLOG_READ" => array("FIELD" => "BSR.BLOG_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_blog_socnet BSR ON (P.BLOG_ID = BSR.BLOG_ID)"),
			"BLOG_SOCNET_GROUP_ID" => array("FIELD" => "B.SOCNET_GROUP_ID", "TYPE" => "string", "FROM" => "INNER JOIN b_blog B ON (P.BLOG_ID = B.ID)"),
			"SOCNET_GROUP_ID" => array("FIELD" => "SR1.ENTITY_ID", "TYPE" => "string", "FROM" => "INNER JOIN b_blog_socnet_rights SR1 ON (P.ID = SR1.POST_ID AND SR1.ENTITY_TYPE = 'SG')"),
			"SOCNET_SITE_ID" => array("FIELD" => "SLS.SITE_ID", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_log BSL ON (BSL.EVENT_ID in ('blog_post', 'blog_post_micro', 'blog_post_important') AND BSL.SOURCE_ID = P.ID) ".
				"LEFT JOIN b_sonet_log_site SLS ON BSL.ID = SLS.LOG_ID"),

			"COMMENT_ID" => array("FIELD" => "PC.ID", "TYPE" => "string", "FROM" => "INNER JOIN b_blog_comment PC ON (P.ID = PC.POST_ID)"),
		);
		$ii = 0;
		foreach ($arFilter as $key => $val)
		{
			$key_res = CBlog::GetFilterOperation($key);
			$k = $key_res["FIELD"];
			if (strpos($k, "POST_PARAM_") === 0)
			{
				$user_id = 0; $ii++; $pref = "BPP".$ii;
				if (is_array($val))
				{
					$user_id = (isset($val["USER_ID"]) ? intval($val["USER_ID"]) : 0);
					$arFilter[$key] = $val["VALUE"];
				}
				$arSelectFields[] = $k;
				$arFields[$k] = array("FIELD" => $pref.".VALUE", "TYPE" => "string",
					"FROM" => "LEFT JOIN b_blog_post_param ".$pref." ON (P.ID = ".$pref.".POST_ID AND ".$pref.".USER_ID".
						($user_id <= 0 ? " IS NULL" : "=".$user_id)." AND ".$pref.".NAME='".$GLOBALS["DB"]->ForSql(substr($k, 11), 50)."')");
			}
		}
		if(isset($arFilter["GROUP_CHECK_PERMS"]))
		{
			if(is_array($arFilter["GROUP_CHECK_PERMS"]))
			{
				foreach($arFilter["GROUP_CHECK_PERMS"] as $val)
				{
					if(IntVal($val)>0)
					{
						$arFields["POST_PERM_".$val] = Array(
								"FIELD" => "BUGP".$val.".PERMS",
								"TYPE" => "string",
								"FROM" => "LEFT JOIN b_blog_user_group_perms BUGP".$val."
											ON (P.BLOG_ID = BUGP".$val.".BLOG_ID
												AND P.ID = BUGP".$val.".POST_ID
												AND BUGP".$val.".USER_GROUP_ID = ".$val."
												AND BUGP".$val.".PERMS_TYPE = '".BLOG_PERMS_POST."')"
							);
						$arSelectFields[] = "POST_PERM_".$val;
					}
				}
			}
			else
			{
				if(IntVal($arFilter["GROUP_CHECK_PERMS"])>0)
				{
					$arFields["POST_PERM_".$arFilter["GROUP_CHECK_PERMS"]] = Array(
							"FIELD" => "BUGP.PERMS",
							"TYPE" => "string",
							"FROM" => "LEFT JOIN b_blog_user_group_perms BUGP
										ON (P.BLOG_ID = BUGP.BLOG_ID
											AND P.ID = BUGP.POST_ID
											AND BUGP.USER_GROUP_ID = ".$arFilter["GROUP_CHECK_PERMS"]."
											AND BUGP.PERMS_TYPE = '".BLOG_PERMS_POST."')"
						);
					$arSelectFields[] = "POST_PERM_".$arFilter["GROUP_CHECK_PERMS"];
				}
			}
			unset($arFilter["GROUP_CHECK_PERMS"]);
		}

		// rating variable
		if (
			in_array("RATING_TOTAL_VALUE", $arSelectFields) ||
			in_array("RATING_TOTAL_VOTES", $arSelectFields) ||
			in_array("RATING_TOTAL_POSITIVE_VOTES", $arSelectFields) ||
			in_array("RATING_TOTAL_NEGATIVE_VOTES", $arSelectFields) ||
			array_key_exists("RATING_TOTAL_VALUE", $arOrder) ||
			array_key_exists("RATING_TOTAL_VOTES", $arOrder) ||
			array_key_exists("RATING_TOTAL_POSITIVE_VOTES", $arOrder) ||
			array_key_exists("RATING_TOTAL_NEGATIVE_VOTES", $arOrder)
		)
		{
			$arSelectFields[] = 'RATING_TOTAL_VALUE';
			$arSelectFields[] = 'RATING_TOTAL_VOTES';
			$arSelectFields[] = 'RATING_TOTAL_POSITIVE_VOTES';
			$arSelectFields[] = 'RATING_TOTAL_NEGATIVE_VOTES';
			$arFields["RATING_TOTAL_VALUE"] = array("FIELD" => $DB->IsNull('RV.TOTAL_VALUE', '0'), "ORDER" => "RATING_TOTAL_VALUE", "TYPE" => "double", "FROM" => "LEFT JOIN b_rating_voting RV ON ( RV.ENTITY_TYPE_ID = 'BLOG_POST' AND RV.ENTITY_ID = P.ID )");
			$arFields["RATING_TOTAL_VOTES"] = array("FIELD" => $DB->IsNull('RV.TOTAL_VOTES', '0'), "ORDER" => "RATING_TOTAL_VALUE", "TYPE" => "int", "FROM" => "LEFT JOIN b_rating_voting RV ON ( RV.ENTITY_TYPE_ID = 'BLOG_POST' AND RV.ENTITY_ID = P.ID )");
			$arFields["RATING_TOTAL_POSITIVE_VOTES"] = array("FIELD" => $DB->IsNull('RV.TOTAL_POSITIVE_VOTES', '0'), "ORDER" => "RATING_TOTAL_POSITIVE_VOTES", "TYPE" => "int", "FROM" => "LEFT JOIN b_rating_voting RV ON ( RV.ENTITY_TYPE_ID = 'BLOG_POST' AND RV.ENTITY_ID = P.ID )");
			$arFields["RATING_TOTAL_NEGATIVE_VOTES"] = array("FIELD" => $DB->IsNull('RV.TOTAL_NEGATIVE_VOTES', '0'), "ORDER" => "RATING_TOTAL_POSITIVE_VOTES", "TYPE" => "int", "FROM" => "LEFT JOIN b_rating_voting RV ON ( RV.ENTITY_TYPE_ID = 'BLOG_POST' AND RV.ENTITY_ID = P.ID )");
		}
		if (in_array("RATING_USER_VOTE_VALUE", $arSelectFields))
		{
			global $USER;
			if (isset($USER) && is_object($USER))
			{
				$arSelectFields[] = 'RATING_USER_VOTE_VALUE';
				$arFields["RATING_USER_VOTE_VALUE"] =  Array("FIELD" => $DB->IsNull('RVV.VALUE', '0'), "ORDER" => "RATING_USER_VOTE_VALUE",  "TYPE" => "double", "FROM" => "LEFT JOIN b_rating_vote RVV ON RVV.ENTITY_TYPE_ID = 'BLOG_POST' AND RVV.ENTITY_ID = P.ID  AND RVV.USER_ID = ".intval($USER->GetId()));
			}
		}

		// <-- FIELDS
		$bNeedDistinct = false;
		$blogModulePermissions = $GLOBALS["APPLICATION"]->GetGroupRight("blog");
		if ($blogModulePermissions < "W")
		{
			$user_id = 0;
			if(isset($USER) && is_object($USER) && $USER->IsAuthorized())
				$user_id = $GLOBALS["USER"]->GetID();

			if(!CBlog::IsBlogOwner($arFilter["BLOG_ID"], $user_id))
			{
				$arUserGroups = CBlogUser::GetUserGroups($user_id, IntVal($arFilter["BLOG_ID"]), "Y", BLOG_BY_USER_ID);
				$strUserGroups = "0";
				foreach($arUserGroups as $v)
					$strUserGroups .= ",".IntVal($v);

				$arFields["PERMS"] = array("FIELD" => "UGP.PERMS", "TYPE" => "string", "FROM" => "INNER JOIN b_blog_user_group_perms UGP ON (P.ID = UGP.POST_ID AND P.BLOG_ID = UGP.BLOG_ID AND UGP.USER_GROUP_ID IN (".$strUserGroups.") AND UGP.PERMS_TYPE = '".BLOG_PERMS_POST."')");
				$bNeedDistinct = true;
			}
			else
				$arFields["PERMS"] = array("FIELD" => "'W'", "TYPE" => "string");
		}
		else
		{
			$arFields["PERMS"] = array("FIELD" => "'W'", "TYPE" => "string");
		}

		$arSqls = CBlog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields, $obUserFieldsSql);

		if(array_key_exists("SOCNET_GROUP_ID", $arFilter) || array_key_exists("SOCNET_GROUP_ID", $arFilter))
			$bNeedDistinct = true;
		if(array_key_exists("FOR_USER", $arFilter))
		{
			if(IntVal($arFilter["FOR_USER"]) > 0) //authorized user
			{
				if($arFilter["FOR_USER_TYPE"] == "ALL")
				{
					$arSqls["FROM"] .=
								" INNER JOIN b_blog_socnet_rights SR ON (P.ID = SR.POST_ID) ".
								" LEFT JOIN b_user_access UA ON (UA.ACCESS_CODE = SR.ENTITY AND UA.USER_ID = ".IntVal($arFilter["FOR_USER"]).") ";
					if(strlen($arSqls["WHERE"]) > 0)
						$arSqls["WHERE"] .= " AND ";
					$arSqls["WHERE"] .= " (SR.ENTITY_TYPE != 'SG') AND ".
										" (SR.ENTITY = 'U".IntVal($arFilter["FOR_USER"])."' OR (UA.USER_ID is not NULL AND SR.ENTITY_TYPE = 'DR') OR P.AUTHOR_ID = '".IntVal($arFilter["FOR_USER"])."')";
				}
				elseif($arFilter["FOR_USER_TYPE"] == "SELF")
				{
					$arSqls["FROM"] .=
								" INNER JOIN b_blog_socnet_rights SR ON (P.ID = SR.POST_ID) ".
								" LEFT JOIN b_user_access UA ON (UA.ACCESS_CODE = SR.ENTITY AND UA.USER_ID = ".IntVal($arFilter["FOR_USER"]).") ";
					if(strlen($arSqls["WHERE"]) > 0)
						$arSqls["WHERE"] .= " AND ";
					$arSqls["WHERE"] .= " (SR.ENTITY = 'U".IntVal($arFilter["FOR_USER"])."' OR (UA.USER_ID is not NULL AND SR.ENTITY_TYPE = 'DR')) ";
				}
				elseif($arFilter["FOR_USER_TYPE"] == "DR")
				{
					$arSqls["FROM"] .=
								" INNER JOIN b_blog_socnet_rights SR ON (P.ID = SR.POST_ID) " .
								" LEFT JOIN b_user_access UA ON (UA.ACCESS_CODE = SR.ENTITY AND UA.USER_ID = ".IntVal($arFilter["FOR_USER"]).") ";
					if(strlen($arSqls["WHERE"]) > 0)
						$arSqls["WHERE"] .= " AND ";
					$arSqls["WHERE"] .= " (UA.USER_ID is not NULL AND SR.ENTITY_TYPE = 'DR') ";
				}
				else
				{
					$arSqls["FROM"] .=
								" INNER JOIN b_blog_socnet_rights SR ON (P.ID = SR.POST_ID) " .
								" LEFT JOIN b_user_access UA ON (UA.ACCESS_CODE = SR.ENTITY AND UA.USER_ID = ".IntVal($arFilter["FOR_USER"]).") ";
					if(strlen($arSqls["WHERE"]) > 0)
						$arSqls["WHERE"] .= " AND ";
					$arSqls["WHERE"] .= " (UA.USER_ID is not NULL OR SR.ENTITY = 'AU') ";
				}
			}
			else
			{
				$arSqls["FROM"] .=
							" INNER JOIN b_blog_socnet_rights SR ON (P.ID = SR.POST_ID) ".
							" INNER JOIN b_user_access UA ON (UA.ACCESS_CODE = SR.ENTITY AND UA.USER_ID = 0)";
			}
			$bNeedDistinct = true;
		}

		if($bNeedDistinct)
			$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);
		else
			$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		$r = $obUserFieldsSql->GetFilter();
		if(strlen($r)>0)
			$strSqlUFFilter = " (".$r.") ";

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
					$obUserFieldsSql->GetSelect()." ".
				"FROM b_blog_post P ".
				"	".$arSqls["FROM"]." ".
					$obUserFieldsSql->GetJoin("P.ID")." ";
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
			"FROM b_blog_post P ".
			"	".$arSqls["FROM"]." ".
				$obUserFieldsSql->GetJoin("P.ID")." ";
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
				"SELECT COUNT(DISTINCT P.ID) as CNT ".
				"FROM b_blog_post P ".
				"	".$arSqls["FROM"]." ".
					$obUserFieldsSql->GetJoin("P.ID")." ";
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

			$dbRes->SetUserFields($USER_FIELD_MANAGER->GetUserFields("BLOG_POST"));
			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) > 0)
				$strSql .= "LIMIT ".IntVal($arNavStartParams["nTopCount"]);

			//echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$dbRes->SetUserFields($USER_FIELD_MANAGER->GetUserFields("BLOG_POST"));
		}
		//echo "!4!=".htmlspecialcharsbx($strSql)."<br>";
		return $dbRes;
	}

	public static function GetListCalendar($blogID, $year = false, $month = false, $day = false)
	{
		global $DB;

		$blogID = IntVal($blogID);

		if ($year)
			if (strlen($year) == 2)
				$year = "20".$year;

		if ($year && $month && $day)
		{
			$date1 = mktime(0, 0, 0, $month, $day, $year);
			$date2 = mktime(0, 0, 0, $month, $day + 1, $year);
		}
		elseif ($month && $year)
		{
			$date1 = mktime(0, 0, 0, $month, 1, $year);
			$date2 = mktime(0, 0, 0, $month + 1, 1, $year);
		}
		elseif ($year)
		{
			$date1 = mktime(0, 0, 0, 1, 1, $year);
			$date2 = mktime(0, 0, 0, 1, 1, $year + 1);
		}
		$datePublishFrom = ConvertTimeStamp($date1, "SHORT", SITE_ID);
		$datePublishTo = ConvertTimeStamp($date2, "SHORT", SITE_ID);

		$arUserGroups = CBlogUser::GetUserGroups(($GLOBALS["USER"]->IsAuthorized() ? $GLOBALS["USER"]->GetID() : 0), $arFilter["BLOG_ID"], "Y", BLOG_BY_USER_ID);
		$strUserGroups = "0";
		foreach($arUserGroups as $v)
			$strUserGroups .= ",".IntVal($v);

		$strFromPerms =
			"	LEFT JOIN b_blog_user_group_perms UGP ".
			"		ON (P.ID = UGP.POST_ID ".
			"			AND P.BLOG_ID = UGP.BLOG_ID ".
			"			AND UGP.USER_GROUP_ID IN (".$strUserGroups.") ".
			"			AND UGP.PERMS_TYPE = '".$DB->ForSql(BLOG_PERMS_POST)."') ";
		$strWherePerms = " AND (UGP.PERMS > 'D') ";

		$blogModulePermissions = $GLOBALS["APPLICATION"]->GetGroupRight("blog");
		if ($blogModulePermissions >= "W")
		{
			$strFromPerms = "";
			$strWherePerms = "";
		}

		$strSql =
			"SELECT DATE_FORMAT(P.DATE_PUBLISH, '%Y-%m-%d') as DATE_PUBLISH1, COUNT(P.ID) as CNT ".
			"FROM b_blog_post P ".$strFromPerms." ".
			"WHERE P.BLOG_ID = ".$blogID." ".
			"	AND P.DATE_PUBLISH >= ".$DB->CharToDateFunction($DB->ForSql($datePublishFrom), "SHORT")." ".
			"	AND P.DATE_PUBLISH < ".$DB->CharToDateFunction($DB->ForSql($datePublishTo), "SHORT")." ".
			"	AND P.PUBLISH_STATUS = '".$DB->ForSql(BLOG_PUBLISH_STATUS_PUBLISH)."' ".
			"	".$strWherePerms." ".
			"GROUP BY DATE_PUBLISH1 ".
			"ORDER BY DATE_PUBLISH1 ";

		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$arResult = array();
		while ($arRes = $dbRes->Fetch())
		{
			$arDate = explode("-", $arRes["DATE_PUBLISH1"]);
			$arResult[] = array(
				"YEAR" => $arDate[0],
				"MONTH" => $arDate[1],
				"DAY" => $arDate[2],
				"DATE" => ConvertTimeStamp(mktime(0, 0, 0, $arDate[1], $arDate[2], $arDate[0]), "SHORT", LANG)
			);
		}

		return $arResult;
	}
}
?>