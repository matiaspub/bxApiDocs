<?
class CSocNetSearch
{
	var $_params;
	var $_user_id;
	var $_group_id;

	/*
	arParams
		PATH_TO_GROUP

		PATH_TO_GROUP_BLOG
		PATH_TO_USER_BLOG

		FORUM_ID
		PATH_TO_GROUP_FORUM_MESSAGE
		PATH_TO_USER_FORUM_MESSAGE

		PHOTO_GROUP_IBLOCK_ID
		PATH_TO_GROUP_PHOTO_ELEMENT
		PHOTO_USER_IBLOCK_ID
		PATH_TO_USER_PHOTO_ELEMENT

		CALENDAR_GROUP_IBLOCK_ID
		PATH_TO_GROUP_CALENDAR_ELEMENT

		TASK_IBLOCK_ID
		PATH_TO_GROUP_TASK_ELEMENT
		PATH_TO_USER_TASK_ELEMENT

		FILES_PROPERTY_CODE
		FILES_FORUM_ID
		FILES_GROUP_IBLOCK_ID
		PATH_TO_GROUP_FILES_ELEMENT
		PATH_TO_GROUP_FILES
		FILES_USER_IBLOCK_ID
		PATH_TO_USER_FILES_ELEMENT
		PATH_TO_USER_FILES
	*/
	public function __construct($user_id, $group_id, $arParams)
	{
		$this->CSocNetSearch($user_id, $group_id, $arParams);
	}

	public function CSocNetSearch($user_id, $group_id, $arParams)
	{
		$this->_user_id = intval($user_id);
		$this->_group_id = intval($group_id);
		$this->_params = $arParams;
	}

	public static function OnUserRelationsChange($user_id)
	{
		if(CModule::IncludeModule('search'))
		{
			CSearchUser::DeleteByUserID($user_id);
		}

		$provider = new CSocNetGroupAuthProvider();
		$provider->DeleteByUser($user_id);

		$provider = new CSocNetUserAuthProvider();
		$provider->DeleteByUser($user_id);

		$dbFriend = CSocNetUserRelations::GetRelatedUsers($user_id, SONET_RELATIONS_FRIEND);
		while ($arFriend = $dbFriend->Fetch())
		{
			$friendID = (($user_id == $arFriend["FIRST_USER_ID"]) ? $arFriend["SECOND_USER_ID"] : $arFriend["FIRST_USER_ID"]);
			$provider->DeleteByUser($friendID);
		}
	}

	public static function SetFeaturePermissions($entity_type, $entity_id, $feature, $operation, $new_perm)
	{
		if(substr($operation, 0, 4) == "view")//This kind of extremely dangerous optimization
		{
			if(CModule::IncludeModule('search'))
			{
				global $arSonetFeaturesPermsCache;
				unset($arSonetFeaturesPermsCache[$entity_type."_".$entity_id]);

				$arGroups = CSocNetSearch::GetSearchGroups($entity_type, $entity_id, $feature, $operation);
				$arParams = CSocNetSearch::GetSearchParams($entity_type, $entity_id, $feature, $operation);

				CSearch::ChangePermission(false, $arGroups, false, false, false, false, $arParams);
			}
		}
		if (
			$feature == "blog"
			&& in_array($operation, Array("view_post", "view_comment"))
			&& CModule::IncludeModule('blog')
		)
		{
			if($operation == "view_post")
			{
				CBlogPost::ChangeSocNetPermission($entity_type, $entity_id, $operation);
			}

			if($operation == "view_post")
			{
				$arPost = CBlogPost::GetSocNetPostsPerms($entity_type, $entity_id);
				foreach($arPost as $id => $perms)
				{
					CSearch::ChangePermission("blog", $perms["PERMS"], "P".$id);
				}
			}
			else
			{
				$arTmpCache = Array();
				$arPost = CBlogPost::GetSocNetPostsPerms($entity_type, $entity_id);
				$dbComment = CBlogComment::GetSocNetPostsPerms($entity_type, $entity_id);
				while($arComment = $dbComment->Fetch())
				{
					if(!empty($arPost[$arComment["POST_ID"]]))
					{
						if(empty($arPost[$arComment["POST_ID"]]["PERMS_CALC"]))
						{
							$arPost[$arComment["POST_ID"]]["PERMS_CALC"] = array();
							if(is_array($arPost[$arComment["POST_ID"]]["PERMS_FULL"]) && !empty($arPost[$arComment["POST_ID"]]["PERMS_FULL"]))
							{
								foreach($arPost[$arComment["POST_ID"]]["PERMS_FULL"] as $e => $v)
								{
									if(in_array($v["TYPE"], Array("SG", "U")))
									{
										$type = $v["TYPE"] == "SG" ? "G" : "U";
										if(array_key_exists($type.$v["ID"], $arTmpCache))
										{
											$spt = $arTmpCache[$type.$v["ID"]];
										}
										else
										{
											$spt = CBlogPost::GetSocnetGroups($type, $v["ID"], "view_comment");
											$arTmpCache[$type.$v["ID"]] = $spt;
										}
										foreach($spt as $vv)
										{
											if(!in_array($vv, $arPost[$arComment["POST_ID"]]["PERMS_CALC"]))
												$arPost[$arComment["POST_ID"]]["PERMS_CALC"][] = $vv;
										}
									}
									else
									{
										$arPost[$arComment["POST_ID"]]["PERMS_CALC"][] = $e;
									}
								}
							}
						}

						CSearch::ChangePermission("blog", $arPost[$arComment["POST_ID"]]["PERMS_CALC"], "C".$arComment["ID"]);
					}
				}
			}
		}
	}

	public static function GetSearchParams($entity_type, $entity_id, $feature, $operation)
	{
		return array(
			"feature_id" => "S".$entity_type."_".$entity_id."_".$feature."_".$operation,
			($entity_type == "G"? "socnet_group": "socnet_user") => $entity_id,
		);
	}

	public static function GetSearchGroups($entity_type, $entity_id, $feature, $operation)
	{
		$arResult = array();

		if($entity_type == "G")
		{
			$prefix = "SG".$entity_id."_";
			$letter = CSocNetFeaturesPerms::GetOperationPerm(SONET_ENTITY_GROUP, $entity_id, $feature, $operation);
			switch($letter)
			{
				case "N"://All
					$arResult[] = 'G2';
					break;
				case "L"://Authorized
					$arResult[] = 'AU';
					break;
				case "K"://Group members includes moderators and admins
					$arResult[] = $prefix.'K';
				case "E"://Moderators includes admins
					$arResult[] = $prefix.'E';
				case "A"://Admins
					$arResult[] = $prefix.'A';
					break;
			}
		}
		else
		{
			$prefix = "SU".$entity_id."_";
			$letter = CSocNetFeaturesPerms::GetOperationPerm(SONET_ENTITY_USER, $entity_id, $feature, $operation);
			switch($letter)
			{
				case "A"://All
					$arResult[] = 'G2';
					break;
				case "C"://Authorized
					$arResult[] = 'AU';
					break;
				case "E"://Friends of friends (has no rights yet) so it counts as
				case "M"://Friends
					$arResult[] = $prefix.'M';
				case "Z"://Personal
					$arResult[] = $prefix.'Z';
					break;
			}
		}

		return $arResult;
	}

	public static function OnSearchReindex($NS = Array(), $oCallback = NULL, $callback_method = "")
	{
		global $DB;
		$arResult = array();

		if ($NS["MODULE"]=="socialnetwork" && strlen($NS["ID"]) > 0)
			$id = intval($NS["ID"]);
		else
			$id = 0;//very first id

		if($NS["SITE_ID"]!="")
		{
			$strNSJoin1 .= " INNER JOIN b_sonet_group_site sgs ON sgs.GROUP_ID=g.ID ";
			$strNSFilter1 .= " AND sgs.SITE_ID='".$DB->ForSQL($NS["SITE_ID"])."' ";
		}

		$strSql = "
			SELECT
				g.ID
				,".$DB->DateToCharFunction("g.DATE_UPDATE")." as DATE_UPDATE
				,g.NAME
				,g.DESCRIPTION
				,g.SUBJECT_ID
				,g.KEYWORDS
				,g.VISIBLE
			FROM
				b_sonet_group g
				".$strNSJoin1."
			WHERE
				g.ACTIVE = 'Y'
				".$strNSFilter1."
				AND g.ID > ".$id."
			ORDER BY
				g.ID
		";

		$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($ar = $rs->Fetch())
		{
			$arSearchIndexSiteID = array();
			$rsGroupSite = CSocNetGroup::GetSite($ar["ID"]);

			while($arGroupSite = $rsGroupSite->Fetch())
				$arSearchIndexSiteID[$arGroupSite["LID"]] = str_replace("#group_id#", $ar["ID"], COption::GetOptionString("socialnetwork", "group_path_template", "/workgroups/group/#group_id#/", $arGroupSite["LID"]));

			$Result = Array(
				"ID" => "G".$ar["ID"],
				"LAST_MODIFIED" => $ar["DATE_UPDATE"],
				"TITLE" => $ar["NAME"],
				"BODY" => CSocNetTextParser::killAllTags($ar["DESCRIPTION"]),
				"SITE_ID" => $arSearchIndexSiteID,
				"PARAM1" => $ar["SUBJECT_ID"],
				"PARAM2" => $ar["ID"],
				"PARAM3" => "GROUP",
				"PERMISSIONS" => (
					$ar["VISIBLE"] == "Y"?
						array('G2')://public
						array(
							'SG'.$ar["ID"].'_A',//admins
							'SG'.$ar["ID"].'_E',//moderators
							'SG'.$ar["ID"].'_K',//members
						)
				),
				"PARAMS" =>array(
					"socnet_group" => $ar["ID"],
					"entity" => "socnet_group",
				),
				"TAGS" => $ar["KEYWORDS"],
			);

			if($oCallback)
			{
				$res = call_user_func(array(&$oCallback, $callback_method), $Result);
				if(!$res)
					return $Result["ID"];
			}
			else
				$arResult[] = $Result;
		}

		if ($oCallback)
			return false;

		return $arResult;
	}

	public static function OnSearchPrepareFilter($strSearchContentAlias, $field, $val)
	{
		if(defined("BX_COMP_MANAGED_CACHE") && in_array($field, array("SOCIAL_NETWORK_USER", "SOCIAL_NETWORK_GROUP")))
		{
			$tag_val = (is_array($val) ? serialize($val) : $val);
			$tag_field = ($field == "SOCIAL_NETWORK_GROUP" ? SONET_ENTITY_GROUP : SONET_ENTITY_USER);
			$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_search_".$tag_field."_".$tag_val);
		}
	}

	public static function OnSearchCheckPermissions($FIELD)
	{
		global $DB, $USER;
		$user_id = intval($USER->GetID());
		$arResult = array();

		if($user_id > 0)
		{
			$arResult[] = "SU".$user_id."_Z";
			$rsFriends = CSocNetUserRelations::GetList(
				array(),
				array(
					"USER_ID" => $user_id,
					"RELATION" => SONET_RELATIONS_FRIEND
				),
				false,
				false,
				array("ID", "FIRST_USER_ID", "SECOND_USER_ID", "DATE_CREATE", "DATE_UPDATE", "INITIATED_BY")
			);
			while($arFriend = $rsFriends->Fetch())
			{
				if($arFriend["FIRST_USER_ID"] != $user_id)
					$arResult[] = "SU".$arFriend["FIRST_USER_ID"]."_M";
				if($arFriend["SECOND_USER_ID"] != $user_id)
					$arResult[] = "SU".$arFriend["SECOND_USER_ID"]."_M";
			}
		}

		$rsGroups = CSocNetUserToGroup::GetList(
			array(),
			array("USER_ID" => $user_id),
			false,
			false,
			array("GROUP_ID", "ROLE")
		);
		while($arGroup = $rsGroups->Fetch())
			$arResult[] = "SG".$arGroup["GROUP_ID"]."_".$arGroup["ROLE"];

		return $arResult;
	}

	public function BeforeIndexForum($arFields, $entity_type, $entity_id, $feature, $operation, $path_template)
	{
		global $USER;
		static $arSiteData;

		$SECTION_ID = "";
		$ELEMENT_ID = intval($_REQUEST["photo_element_id"]);
		if (empty($ELEMENT_ID))
		{
			$ELEMENT_ID = intval($_REQUEST["ELEMENT_ID"]);
		}

		if(
			$ELEMENT_ID > 0
			&& CModule::IncludeModule('iblock')
		)
		{
			$rsSections = CIBlockElement::GetElementGroups($ELEMENT_ID, true);
			$arSection = $rsSections->Fetch();
			if($arSection)
			{
				$SECTION_ID = $arSection["ID"];
			}
		}

		if (
			count($arFields["LID"]) > 1
			&& (
				(
					$entity_type == SONET_ENTITY_GROUP
					&& CModule::IncludeModule("extranet")
				)
				|| $entity_type == SONET_ENTITY_USER
			)
		)
		{
			if (!$arSiteData)
			{
				$arSiteData = CSocNetLogTools::GetSiteData();
			}

			foreach($arSiteData as $siteId => $arUrl)
			{
				if (
					$entity_type == SONET_ENTITY_GROUP
					&& strpos($path_template, $arUrl["GROUPS_PATH"]) === 0
				)
				{
					$path_template = str_replace($arUrl["GROUPS_PATH"], "#GROUP_PATH#", $path_template);
					break;
				}
				elseif (
					$entity_type == SONET_ENTITY_USER
					&& strpos($path_template, $arUrl["USER_PATH"]) === 0
				)
				{
					$path_template = str_replace($arUrl["USER_PATH"], "#USER_PATH#", $path_template);
					break;
				}
			}
		}

		foreach($arFields["LID"] as $site_id => $url)
		{
			$arFields["URL"] = $arFields["LID"][$site_id] = str_replace(
				array(
					"#user_id#",
					"#group_id#",
					"#topic_id#",
					"#message_id#",
					"#action#",
					"#user_alias#",
					"#section_id#",
					"#element_id#",
					"#task_id#",
					"#GROUP_PATH#",
					"#USER_PATH#"
				),
				array(
					($this->_user_id > 0 ? $this->_user_id : $USER->GetID()),
					$this->_group_id,
					$arFields["PARAM2"],
					$arFields["ITEM_ID"],
					"view",
					($entity_type=="G"? "group_": "user_").$entity_id,
					$SECTION_ID,
					$ELEMENT_ID,
					$ELEMENT_ID,
					($arSiteData ? $arSiteData[$site_id]["GROUPS_PATH"] : ""),
					($arSiteData ? $arSiteData[$site_id]["USER_PATH"] : "")
				),
				$path_template
			);
		}

		if (
			($feature == 'tasks') &&
			(COption::GetOptionString("intranet", "use_tasks_2_0", "N") == 'Y') &&
			($arFields["PARAM1"] == COption::GetOptionString("tasks", "task_forum_id", 0)) &&
			CModule::IncludeModule('tasks')
		)
		{
			if (!preg_match('/^EVENT_[0-9]+/', $arFields["TITLE"], $match)) // calendar comments live in the same TASK_FORUM_ID :(
			{
				$rsTask = CTasks::GetList(array(), array("FORUM_TOPIC_ID" => $arFields['PARAM2']));
				if ($arTask = $rsTask->Fetch())
				{
					$arFields['PERMISSIONS'] = CTasks::__GetSearchPermissions($arTask);
				}
			}
		}
		else
		{
			$arFields["PERMISSIONS"] = $this->GetSearchGroups(
				$entity_type,
				$entity_id,
				$feature,
				$operation
			);

			$paramsTmp = $this->GetSearchParams(
				$entity_type,
				$entity_id,
				$feature,
				$operation
			);
			$arFields["PARAMS"] = (!empty($arFields["PARAMS"]) ? array_merge($paramsTmp, $arFields["PARAMS"]) : $paramsTmp);
		}

		return $arFields;
	}

	public static function Url($url, $params, $ancor)
	{
		$url_params = array();
		$p = strpos($url, "?");
		if($p !== false)
		{
			$ar = explode("&", substr($url, $p + 1));
			foreach($ar as $str)
			{
				list($name, $value) = explode("=", $str, 2);
				$url_params[$name] = $name."=".$value;
			}
			$url = substr($url, 0, $p);
		}

		foreach($params as $name => $value)
			$url_params[$name] = $name."=".$value;

		if(count($url_params))
			return $url."?".implode("&", $url_params).(strlen($ancor)? "#".$ancor: "");
		else
			return $url.(strlen($ancor)? "#".$ancor: "");
	}

	public static function BeforeIndex($arFields)
	{
		global $USER;

		//Check if we in right context
		if (
			!is_object($this) 
			|| !is_array($this->_params)
		)
		{
			return $arFields;
		}

		if (isset($arFields["REINDEX_FLAG"]))
		{
			return $arFields;
		}

		//This was group modification
		if($this->_group_id)
		{
			if(
				$arFields["MODULE_ID"] == "forum" 
				&& intval($arFields["PARAM1"]) == intval($this->_params["FORUM_ID"])
			)
			{
				// forum feature
				$arFields["LID"] = array();
				$rsGroupSite = CSocNetGroup::GetSite($this->_group_id);
				while($arGroupSite = $rsGroupSite->Fetch())
				{
					$arFields["LID"][$arGroupSite["LID"]] = $arFields["URL"];
				}

				$arFields = $this->BeforeIndexForum($arFields,
					SONET_ENTITY_GROUP, $this->_group_id,
					"forum", "view",
					$this->_params["PATH_TO_GROUP_FORUM_MESSAGE"]
				);
			}
			elseif(
				$arFields["MODULE_ID"] == "forum" 
				&& intval($arFields["PARAM1"]) == intval($this->_params["FILES_FORUM_ID"])
			)
			{
				$arFields = $this->BeforeIndexForum($arFields,
					SONET_ENTITY_GROUP, $this->_group_id,
					"files", "view",
					$this->Url($this->_params["PATH_TO_GROUP_FILES_ELEMENT"], array("MID"=>"#message_id#"), "message#message_id#")
				);
			}
			elseif(
				$arFields["MODULE_ID"] == "forum" 
				&& intval($arFields["PARAM1"]) == intval($this->_params["TASK_FORUM_ID"])
				&& !preg_match('/^EVENT_[0-9]+/', $arFields["TITLE"], $match) // calendar comments live in the same TASK_FORUM_ID :(
			)
			{
				$arFields = $this->BeforeIndexForum(
					$arFields,
					SONET_ENTITY_GROUP,
					$this->_group_id,
					"tasks",
					"view",
					$this->Url(
						$this->_params["PATH_TO_GROUP_TASK_ELEMENT"], 
						array(
							"MID"=>"#message_id#"
						),
						"message#message_id#"
					)
				);
			}
			elseif(
				$arFields["MODULE_ID"] == "forum" 
				&& preg_match('/^EVENT_[0-9]+/', $arFields["TITLE"], $match)
			)
			{
				$arFields = array(
					"TITLE" => "",
					"BODY" => ""
				);
			}
			elseif(
				$arFields["MODULE_ID"] == "forum" 
				&& intval($arFields["PARAM1"]) == intval($this->_params["PHOTO_FORUM_ID"])
			)
			{
				$arFields = $this->BeforeIndexForum($arFields,
					SONET_ENTITY_GROUP, $this->_group_id,
					"photo", "view",
					$this->Url($this->_params["PATH_TO_GROUP_PHOTO_ELEMENT"], array("MID"=>"#message_id#"), "message#message_id#")
				);
			}
			elseif(
				$arFields["MODULE_ID"] == "blog"
				&& ($arFields["PARAM1"] == "POST" || $arFields["PARAM1"] == "MICROBLOG")
			)
			{
/*
				$paramsTmp = $this->GetSearchParams(
					SONET_ENTITY_GROUP, $this->_group_id,
					'blog', 'view_post'
				);

				$arFields["PARAMS"] = (!empty($arFields["PARAMS"]) ? array_merge($paramsTmp, $arFields["PARAMS"]) : $paramsTmp);
*/
			}
			elseif(
				$arFields["MODULE_ID"] == "blog"
				&& $arFields["PARAM1"] == "COMMENT"
			)
			{
/*
				$paramsTmp = $this->GetSearchParams(
					SONET_ENTITY_GROUP, $this->_group_id,
					'blog', 'view_comment'
				);
				$arFields["PARAMS"] = (!empty($arFields["PARAMS"]) ? array_merge($paramsTmp, $arFields["PARAMS"]) : $paramsTmp);
*/
			}
		}
		elseif($this->_user_id)
		{
			if(
				$arFields["MODULE_ID"] == "forum" 
				&& intval($arFields["PARAM1"]) == intval($this->_params["FORUM_ID"])
			)
			{
				// forum feature
				$arFields["LID"] = array(SITE_ID => $arFields["URL"]);

				$arFields = $this->BeforeIndexForum($arFields,
					SONET_ENTITY_USER, $this->_user_id,
					"forum", "view",
					$this->_params["PATH_TO_USER_FORUM_MESSAGE"]
				);
			}
			elseif(
				$arFields["MODULE_ID"] == "forum" 
				&& intval($arFields["PARAM1"]) == intval($this->_params["FILES_FORUM_ID"])
			)
			{
				$arFields = $this->BeforeIndexForum($arFields,
					SONET_ENTITY_USER, $this->_user_id,
					"files", "view",
					$this->Url($this->_params["PATH_TO_USER_FILES_ELEMENT"], array("MID"=>"#message_id#"), "message#message_id#")
				);
			}
			elseif(
				$arFields["MODULE_ID"] == "forum" 
				&& intval($arFields["PARAM1"]) == intval($this->_params["TASK_FORUM_ID"])
			)
			{
				if (!preg_match('/^EVENT_[0-9]+/', $arFields["TITLE"], $match)) // calendar comments live in the same TASK_FORUM_ID :(
				{
					$arFields = $this->BeforeIndexForum($arFields,
						SONET_ENTITY_USER, $this->_user_id,
						"tasks", "view_all",
						$this->Url($this->_params["PATH_TO_USER_TASK_ELEMENT"], array("MID"=>"#message_id#"), "message#message_id#")
					);
				}
			}
			elseif(
				$arFields["MODULE_ID"] == "forum" 
				&& preg_match('/^EVENT_[0-9]+/', $arFields["TITLE"], $match)
			) // don't index calendar comments!
			{
				$arFields = array(
					"TITLE" => "",
					"BODY" => ""
				);
			}
			elseif(
				$arFields["MODULE_ID"] == "forum" 
				&& intval($arFields["PARAM1"]) == intval($this->_params["PHOTO_FORUM_ID"])
			)
			{
				$arFields = $this->BeforeIndexForum(
					$arFields,
					SONET_ENTITY_USER,
					$this->_user_id,
					"photo",
					"view",
					$this->Url(
						$this->_params["PATH_TO_USER_PHOTO_ELEMENT"],
						array("MID"=>"#message_id#"),
						"message#message_id#"
					)
				);
			}
			elseif(
				$arFields["MODULE_ID"] == "blog"
				&& (
					$arFields["PARAM1"] == "POST" 
					|| $arFields["PARAM1"] == "MICROBLOG"
				)
			)
			{
				$paramsTmp = $this->GetSearchParams(
					SONET_ENTITY_USER, $this->_user_id,
					'blog', 'view_post'
				);
				$arFields["PARAMS"] = (!empty($arFields["PARAMS"]) ? array_merge($paramsTmp, $arFields["PARAMS"]) : $paramsTmp);
			}
			elseif(
				$arFields["MODULE_ID"] == "blog"
				&& $arFields["PARAM1"] == "COMMENT"
			)
			{
				$paramsTmp = $this->GetSearchParams(
					SONET_ENTITY_USER, $this->_user_id,
					'blog', 'view_comment'
				);
				$arFields["PARAMS"] = (!empty($arFields["PARAMS"]) ? array_merge($paramsTmp, $arFields["PARAMS"]) : $paramsTmp);
			}
		}

		foreach(GetModuleEvents("socialnetwork", "BeforeIndexSocNet", true) as $arEvent)
		{
			$arEventResult = ExecuteModuleEventEx($arEvent, array($this, $arFields));
			if(is_array($arEventResult))
			{
				$arFields = $arEventResult;
			}
		}

		return $arFields;
	}

	public function IndexIBlockElement($arFields, $entity_id, $entity_type, $feature, $operation, $path_template, $arFieldList)
	{
		$ID = intval($arFields["ID"]);
		$IBLOCK_ID = intval($arFields["IBLOCK_ID"]);
		$IBLOCK_SECTION_ID = (is_array($arFields["IBLOCK_SECTION"])) ? $arFields["IBLOCK_SECTION"][0] : $arFields["IBLOCK_SECTION"];
		$arItem = array();

		if($entity_type == "G")
			$url = str_replace(
				array("#group_id#", "#user_alias#", "#section_id#", "#element_id#", "#action#", "#task_id#", "#name#"),
				array($entity_id, "group_".$entity_id, $IBLOCK_SECTION_ID, $arFields["ID"], "view", $arFields["ID"], urlencode($arFields["NAME"])),
				$path_template
			);
		else
			$url = str_replace(
				array("#user_id#", "#user_alias#", "#section_id#", "#element_id#", "#action#", "#task_id#"),
				array($entity_id, "user_".$entity_id, $IBLOCK_SECTION_ID, $arFields["ID"], "view", $arFields["ID"]),
				$path_template
			);

		$body = "";
		if($feature == "wiki")
			$CWikiParser = new CWikiParser();
		foreach($arFieldList as $field)
		{
			if($field == "PREVIEW_TEXT" || $field == "DETAIL_TEXT")
			{
				if(isset($CWikiParser))
					$arFields[$field] = HTMLToTxt($CWikiParser->parseForSearch($arFields[$field]));
				elseif(isset($arFields[$field."_TYPE"]) && $arFields[$field."_TYPE"] === "html")
					$arFields[$field] = HTMLToTxt($arFields[$field]);
			}

			$body .= $arFields[$field]."\n\r";
		}

		if(isset($CWikiParser))
			$title = preg_replace('/^category:/i'.BX_UTF_PCRE_MODIFIER, GetMessage('CATEGORY_NAME').':', $arFields['NAME']);
		else
			$title = $arFields["NAME"];

		$arPermissions = $this->GetSearchGroups(
			$entity_type,
			$entity_id,
			$feature,
			$operation
		);

		if (CIBlock::GetArrayByID($IBLOCK_ID, "RIGHTS_MODE") == "E")
		{
			$obElementRights = new CIBlockElementRights($IBLOCK_ID, $arFields["ID"]);
			$arPermissions = $obElementRights->GetGroups(array("element_read"));
		}

		$arSearchIndexParams = $this->GetSearchParams(
			$entity_type,
			$entity_id,
			$feature,
			$operation
		);

		CSearch::Index("socialnetwork", $ID, array(
			"LAST_MODIFIED" => ConvertTimeStamp(time()+CTimeZone::GetOffset(), "FULL"),
			"TITLE" => $title,
			"BODY" => $body,
			"SITE_ID" => array(SITE_ID => $url),
			"PARAM1" => CIBlock::GetArrayByID($IBLOCK_ID, "IBLOCK_TYPE_ID"),
			"PARAM2" => $IBLOCK_ID,
			"PARAM3" => $feature,
			"TAGS" => $arFields["TAGS"],
			"PERMISSIONS" => $arPermissions,
			"PARAMS" => $arSearchIndexParams,
		), true);

		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_search_".$entity_type."_".$entity_id);
		}
	}

	public function IBlockElementUpdate(&$arFields)
	{
		//Do not index workflow history
		$WF_PARENT_ELEMENT_ID = intval($arFields["WF_PARENT_ELEMENT_ID"]);
		if($WF_PARENT_ELEMENT_ID > 0 && $WF_PARENT_ELEMENT_ID != intval($arFields["ID"]))
			return;

		if(!CModule::IncludeModule('search'))
			return;

		//And do not index wf drafts
		$rsElement = CIBlockElement::GetList(
			array(),
			array("=ID"=>$arFields["ID"]),
			false,
			false,
			array(
				"ID",
				"NAME",
				"IBLOCK_SECTION_ID",
				"WF_PARENT_ELEMENT_ID",
				"WF_STATUS_ID",
			)
		);
		$dbElement = $rsElement->Fetch();
		if(!$dbElement)
			return;

		if(!isset($arFields["NAME"]))
			$arFields["NAME"] = $dbElement["NAME"];

		if(!isset($arFields["IBLOCK_SECTION"]))
			$arFields["IBLOCK_SECTION"] = $dbElement["IBLOCK_SECTION_ID"];
		elseif (is_array($arFields["IBLOCK_SECTION"]) && isset($arFields["IBLOCK_SECTION"][0]))
			$arFields["IBLOCK_SECTION"] = $arFields["IBLOCK_SECTION"][0];

		switch(intval($arFields["IBLOCK_ID"]))
		{

		case intval($this->_params["PHOTO_GROUP_IBLOCK_ID"]):
			$path_template = trim($this->_params["PATH_TO_GROUP_PHOTO_ELEMENT"]);
			if(strlen($path_template))
				$this->IndexIBlockElement($arFields, $this->_group_id, "G", "photo", "view", $path_template, array("PREVIEW_TEXT"));
			break;

		case intval($this->_params["PHOTO_USER_IBLOCK_ID"]):
			$path_template = trim($this->_params["PATH_TO_USER_PHOTO_ELEMENT"]);
			if(strlen($path_template))
				$this->IndexIBlockElement($arFields, $this->_user_id, "U", "photo", "view", $path_template, array("PREVIEW_TEXT"));
			break;

		case intval($this->_params["CALENDAR_GROUP_IBLOCK_ID"]):
			$path_template = trim($this->_params["PATH_TO_GROUP_CALENDAR_ELEMENT"]);
			if(strlen($path_template))
				$this->IndexIBlockElement($arFields, $this->_group_id, "G", "calendar", "view", $path_template, array("DETAIL_TEXT"));
			break;

		case intval($this->_params["TASK_IBLOCK_ID"]):
			if(is_array($arFields["IBLOCK_SECTION"]))
			{
				foreach($arFields["IBLOCK_SECTION"] as $section_id)
					break;
			}
			else
			{
				$section_id = $arFields["IBLOCK_SECTION"];
			}
			$section_id = intval($section_id);

			if($section_id)
			{
				$rsPath = CIBlockSection::GetNavChain($arFields["IBLOCK_ID"], $section_id);
				$arSection = $rsPath->Fetch();
				if($arSection)
				{
					if($arSection["EXTERNAL_ID"]=="users_tasks")
					{
						$rsAssigned = CIBlockElement::GetProperty($arFields["IBLOCK_ID"], $arFields["ID"], "sort", "asc", array("CODE"=>"TASKASSIGNEDTO", "EMPTY"=>"N"));
						$arAssigned = $rsAssigned->Fetch();
						$path_template = trim($this->_params["PATH_TO_USER_TASK_ELEMENT"]);
						if($arAssigned && strlen($path_template))
							$this->IndexIBlockElement($arFields, $arAssigned["VALUE"], "U", "tasks", "view_all", $path_template, array("DETAIL_TEXT"));
					}
					elseif(intval($arSection["EXTERNAL_ID"]) > 0)
					{
						$path_template = trim($this->_params["PATH_TO_GROUP_TASK_ELEMENT"]);
						if(strlen($path_template))
							$this->IndexIBlockElement($arFields, intval($arSection["EXTERNAL_ID"]), "G", "tasks", "view", $path_template, array("DETAIL_TEXT"));
					}
				}
			}
			break;

		case intval($this->_params["FILES_GROUP_IBLOCK_ID"]):
			$path_template = trim($this->_params["PATH_TO_GROUP_FILES_ELEMENT"]);
			if(strlen($path_template))
			{
				$property = strtoupper(trim($this->_params["FILES_PROPERTY_CODE"]));
				if(strlen($property) <= 0)
					$property = "FILE";

				$rsFile = CIBlockElement::GetProperty($arFields["IBLOCK_ID"], $arFields["ID"], "sort", "asc", array("CODE"=>$property, "EMPTY"=>"N"));
				$arFile = $rsFile->Fetch();
				if($arFile)
				{
					$arFile = CIBlockElement::__GetFileContent($arFile["VALUE"]);
					if(is_array($arFile))
					{
						$arFields["FILE_CONTENT"] = $arFile["CONTENT"];
						if(strlen($arFields["TAGS"]))
							$arFields["TAGS"] .= ",".$arFile["PROPERTIES"][COption::GetOptionString("search", "page_tag_property")];
						else
							$arFields["TAGS"] = $arFile["PROPERTIES"][COption::GetOptionString("search", "page_tag_property")];
					}
				}
				$this->IndexIBlockElement($arFields, $this->_group_id, "G", "files", "view", $path_template, array("FILE_CONTENT", "DETAIL_TEXT"));
			}
			break;

		case intval($this->_params["FILES_USER_IBLOCK_ID"]):
			$path_template = trim($this->_params["PATH_TO_USER_FILES_ELEMENT"]);
			if(strlen($path_template))
			{
				$property = strtoupper(trim($this->_params["FILES_PROPERTY_CODE"]));
				if(strlen($property) <= 0)
					$property = "FILE";

				$rsFile = CIBlockElement::GetProperty($arFields["IBLOCK_ID"], $arFields["ID"], "sort", "asc", array("CODE"=>$property, "EMPTY"=>"N"));
				$arFile = $rsFile->Fetch();
				if($arFile)
				{
					$arFile = CIBlockElement::__GetFileContent($arFile["VALUE"]);
					if(is_array($arFile))
					{
						$arFields["FILE_CONTENT"] = $arFile["CONTENT"];
						if(strlen($arFields["TAGS"]))
							$arFields["TAGS"] .= ",".$arFile["PROPERTIES"][COption::GetOptionString("search", "page_tag_property")];
						else
							$arFields["TAGS"] = $arFile["PROPERTIES"][COption::GetOptionString("search", "page_tag_property")];
					}
				}
				$this->IndexIBlockElement($arFields, $this->_user_id, "U", "files", "view", $path_template, array("FILE_CONTENT", "DETAIL_TEXT"));
			}
			break;
		case intval(COption::GetOptionInt("wiki", "socnet_iblock_id")):
			if(CModule::IncludeModule("wiki"))
				$this->IndexIBlockElement($arFields, $this->_group_id, "G", "wiki", "view", $this->_params["PATH_TO_GROUP"]."wiki/#name#/", array("DETAIL_TEXT"));
			break;
		}
	}

	public static function IBlockElementDelete($zr)
	{
		if(CModule::IncludeModule("search"))
		{
			CSearch::DeleteIndex("socialnetwork", IntVal($zr["ID"]));
		}
	}

	public function IndexIBlockSection($arFields, $entity_id, $entity_type, $feature, $operation, $path_template)
	{
		$rSection = CIBlockSection::GetByID($arFields['ID']);
		$arSection = $rSection->Fetch();

		$path = array();
		$rsPath = CIBlockSection::GetNavChain($arFields["IBLOCK_ID"], $arFields['ID']);
		while($arPath = $rsPath->Fetch())
			$path[] = $arPath['NAME'];
		$path = implode("/", array_slice($path, 1));

		$ID = intval($arFields["ID"]);
		$IBLOCK_ID = intval($arFields["IBLOCK_ID"]);
		$arItem = array();

		if($entity_type == "G")
			$url = str_replace(
				array("#group_id#", "#user_alias#", "#section_id#", "#element_id#", "#action#", "#task_id#", "#name#", "#path#"),
				array($entity_id, "group_".$entity_id, $arFields["IBLOCK_SECTION"], $arFields["ID"], "view", $arFields["ID"], urlencode($arFields["NAME"]), $path),
				$path_template
			);
		else
			$url = str_replace(
				array("#user_id#", "#user_alias#", "#section_id#", "#element_id#", "#action#", "#task_id#", "#path#"),
				array($entity_id, "user_".$entity_id, $arFields["IBLOCK_SECTION"], $arFields["ID"], "view", $arFields["ID"], $path),
				$path_template
			);

		$body = "";

		$title = $arFields["NAME"];

		$arPermissions = $this->GetSearchGroups(
			$entity_type,
			$entity_id,
			$feature,
			$operation
		);

		if (CIBlock::GetArrayByID($IBLOCK_ID, "RIGHTS_MODE") == "E")
		{
			$obSectionRights = new CIBlockSectionRights($IBLOCK_ID, $arFields["ID"]);
			$arPermissions = $obSectionRights->GetGroups(array("section_read"));
		}

		$arSearchIndexParams = $this->GetSearchParams(
			$entity_type,
			$entity_id,
			$feature,
			$operation
		);

		CSearch::Index("socialnetwork", 'S'.$ID, array(
			"LAST_MODIFIED" => ConvertTimeStamp(time()+CTimeZone::GetOffset(), "FULL"),
			"TITLE" => $title,
			"BODY" => $body,
			"SITE_ID" => array(SITE_ID => $url),
			"PARAM1" => CIBlock::GetArrayByID($IBLOCK_ID, "IBLOCK_TYPE_ID"),
			"PARAM2" => $IBLOCK_ID,
			"PARAM3" => $feature,
			"TAGS" => "",
			"PERMISSIONS" => $arPermissions,
			"PARAMS" => $arSearchIndexParams,
		), true);

		if(defined("BX_COMP_MANAGED_CACHE"))
			$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_search_".$entity_type."_".$entity_id);
	}

	public function IBlockSectionUpdate(&$arFields)
	{
		if(!CModule::IncludeModule('search'))
			return;

		switch(intval($arFields["IBLOCK_ID"]))
		{
			case intval($this->_params["FILES_USER_IBLOCK_ID"]):
				$path_template = trim($this->_params["PATH_TO_USER_FILES"]);
				if(strlen($path_template))
				{
					$this->IndexIBlockSection($arFields, $this->_user_id, "U", "files", "view", $path_template);
				}
			break;

			case intval($this->_params["FILES_GROUP_IBLOCK_ID"]):
				$path_template = trim($this->_params["PATH_TO_GROUP_FILES"]);
				if(strlen($path_template))
				{
					$this->IndexIBlockSection($arFields, $this->_group_id, "G", "files", "view", $path_template);
				}
			break;
		}
	}

	public static function IBlockSectionDelete($zr)
	{
		if(CModule::IncludeModule("search"))
		{
			CSearch::DeleteIndex("socialnetwork", 'S'.IntVal($zr["ID"]));
		}
	}


	public static function OnBeforeIndexUpdate($ID, $arFields)
	{
		if (
			isset($arFields["PARAMS"])
			&& isset($arFields["PARAMS"]["socnet_group"])
		)
		{
			CBitrixComponent::clearComponentCache("bitrix:search.tags.cloud");
		}
	}

	public static function OnAfterIndexAdd($ID, $arFields)
	{
		if (
			isset($arFields["PARAMS"])
			&& isset($arFields["PARAMS"]["socnet_group"])
		)
		{
			CBitrixComponent::clearComponentCache("bitrix:search.tags.cloud");
		}
	}
}
?>
