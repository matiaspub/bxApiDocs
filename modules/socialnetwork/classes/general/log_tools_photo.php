<?
IncludeModuleLangFile(__FILE__);

class CSocNetLogToolsPhoto
{
	public static function OnAfterPhotoUpload($arFields, $arComponentParams, $arComponentResult)
	{
		static $arSiteWorkgroupsPage;

		if (!CModule::IncludeModule("iblock"))
		{
			return;
		}

		if (
			!array_key_exists("IS_SOCNET", $arComponentParams)
			|| $arComponentParams["IS_SOCNET"] != "Y"
		)
		{
			return;
		}

		$bPassword = false;
		$arComponentResult["SECTION"]["PATH"] = array();
		
		$rsSectionAlbum = CIBlockSection::GetList(
			array(), 
			array(
				"ID" => intval($arFields["IBLOCK_SECTION"])
			),
			false, 
			array("ID", "LEFT_MARGIN", "RIGHT_MARGIN", "DEPTH_LEVEL")
		);

		if ($arSectionAlbum = $rsSectionAlbum->Fetch())
		{
			$dbSection = CIBlockSection::GetList(
				array("LEFT_MARGIN" => "ASC"),
				array(
					"IBLOCK_ID" => intval($arComponentParams["IBLOCK_ID"]),
					"<=LEFT_BORDER" => intval($arSectionAlbum["LEFT_MARGIN"]),
					">=RIGHT_BORDER" => intval($arSectionAlbum["RIGHT_MARGIN"]),
					"<=DEPTH_LEVEL" => intval($arSectionAlbum["DEPTH_LEVEL"]),
				),
				false, 
				array("ID", "IBLOCK_ID", "NAME", "CREATED_BY", "DEPTH_LEVEL", "LEFT_MARGIN", "RIGHT_MARGIN", "UF_PASSWORD")
			);

			while ($arPath = $dbSection->Fetch())
			{
				$arComponentResult["SECTION"]["PATH"][] = $arPath;
			}
		}

		foreach($arComponentResult["SECTION"]["PATH"] as $arPathSection)
		{
			if (strlen(trim($arPathSection["UF_PASSWORD"])) > 0)
			{
				$bPassword = true;
				break;
			}
		}

		if (
			array_key_exists("USER_ALIAS", $arComponentParams)
			&& strlen($arComponentParams["USER_ALIAS"]) > 0
		)
		{
			$arTmp = explode("_", $arComponentParams["USER_ALIAS"]);
			if (
				is_array($arTmp)
				&& count($arTmp) == 2
			)
			{
				$entity_type = $arTmp[0];
				$entity_id = $arTmp[1];

				if ($entity_type == "group")
					$entity_type = SONET_ENTITY_GROUP;
				elseif ($entity_type == "user")
					$entity_type = SONET_ENTITY_USER;
			}

			if (
				(
					!in_array($entity_type, array(SONET_ENTITY_GROUP, SONET_ENTITY_USER))
					|| intval($entity_id) <= 0
				)
				&& count($arComponentResult["SECTION"]["PATH"]) > 0
			)
			{
				$entity_type = SONET_ENTITY_USER;
				$entity_id = $arComponentResult["SECTION"]["PATH"][0]["CREATED_BY"];
			}
		}

		if (
			!in_array($entity_type, array(SONET_ENTITY_GROUP, SONET_ENTITY_USER))
			|| intval($entity_id) <= 0
		)
			return;

		if (!$arSiteWorkgroupsPage && IsModuleInstalled("extranet") && $entity_type == SONET_ENTITY_GROUP)
		{
			$rsSite = CSite::GetList($by="sort", $order="desc", Array("ACTIVE" => "Y"));
			while($arSite = $rsSite->Fetch())
				$arSiteWorkgroupsPage[$arSite["ID"]] = COption::GetOptionString("socialnetwork", "workgroups_page", $arSite["DIR"]."workgroups/", $arSite["ID"]);
		}

		if (is_set($arComponentParams["DETAIL_URL"]) && is_array($arSiteWorkgroupsPage) && $entity_type == SONET_ENTITY_GROUP)
			foreach($arSiteWorkgroupsPage as $groups_page)
				if (strpos($arComponentParams["DETAIL_URL"], $groups_page) === 0)
					$arComponentParams["DETAIL_URL"] = "#GROUPS_PATH#".substr($arComponentParams["DETAIL_URL"], strlen($groups_page), strlen($arComponentParams["DETAIL_URL"])-strlen($groups_page));

		$db_res = CSocNetLog::GetList(
			array(),
			array(
				"ENTITY_TYPE" => $entity_type,
				"ENTITY_ID" => $entity_id,
				"EVENT_ID" => "photo",
				"EXTERNAL_ID" => $arFields["IBLOCK_SECTION"]."_".$arFields["MODIFIED_BY"],
				">=LOG_UPDATE" => ConvertTimeStamp(AddToTimeStamp(array("MI" => -5))+CTimeZone::GetOffset(), "FULL")
			)
		);

		if (
			array_key_exists("SECTION", $arComponentResult)
			&& array_key_exists("NAME", $arComponentResult["SECTION"])
		)
			$strSectionName = $arComponentResult["SECTION"]["NAME"];
		else
			$strSectionName = $arComponentResult["SECTION"]["PATH"][count($arComponentResult["SECTION"]["PATH"])-1]["NAME"];
			
		if (
			array_key_exists("URL", $arComponentResult)
			&& array_key_exists("SECTION_EMPTY", $arComponentResult["URL"])
		)
			$strSectionUrl = $arComponentResult["URL"]["SECTION_EMPTY"];
		else
			$strSectionUrl = $arComponentResult["SECTION_URL"];

		$strSectionUrl = str_replace(array("#SECTION_ID#", "#section_id#"), $arFields["IBLOCK_SECTION"], $strSectionUrl);

		if ($db_res && $res = $db_res->Fetch())
		{
			if (strlen($res["PARAMS"]) > 0)
			{
				$arResParams = unserialize($res["PARAMS"]);
				array_push($arResParams["arItems"], $arFields["ID"]);
			}
			else
				return;

			$arLogParams = array(
				"COUNT" => $arResParams["COUNT"]+1,
				"IBLOCK_TYPE" => $arComponentParams["IBLOCK_TYPE"],
				"IBLOCK_ID" => $arComponentParams["IBLOCK_ID"],
				"ALIAS" => $arComponentParams["USER_ALIAS"],
				"DETAIL_URL" => $arResParams["DETAIL_URL"],
				"arItems" => $arResParams["arItems"]
			);

			$arSonetFields = array(
				"=LOG_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"PARAMS" => serialize($arLogParams)
			);

			CSocNetLog::Update($res["ID"], $arSonetFields);
			if (!$bPassword)
			{
				CSocNetLogRights::SetForSonet($res["ID"], $entity_type, $entity_id, "photo", "view");
			}			

			$logID = $res["ID"];
		}
		else
		{
			$arLogParams = array(
				"COUNT" => 1,
				"IBLOCK_TYPE" => $arComponentParams["IBLOCK_TYPE"],
				"IBLOCK_ID" => $arComponentParams["IBLOCK_ID"],
				"DETAIL_URL" => $arComponentParams["DETAIL_URL"],
				"ALIAS" => $arComponentParams["USER_ALIAS"],
				"arItems" => array($arFields["ID"])
			);

			$sAuthorName = GetMessage("SONET_PHOTO_LOG_GUEST");
			$sAuthorUrl = "";
			if ($GLOBALS["USER"]->IsAuthorized())
			{
				$sAuthorName = trim($GLOBALS["USER"]->GetFullName());
				$sAuthorName = (empty($sAuthorName) ? $GLOBALS["USER"]->GetLogin() : $sAuthorName);
			}

			$arSonetFields = array(
				"ENTITY_TYPE" => $entity_type,
				"ENTITY_ID" => $entity_id,
				"EVENT_ID" => "photo",
				"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"TITLE_TEMPLATE" => str_replace("#AUTHOR_NAME#", $sAuthorName, GetMessage("SONET_PHOTO_LOG_1")),
				"TITLE" => str_replace("#COUNT#", "1", GetMessage("SONET_PHOTO_LOG_2")),
				"MESSAGE" => "",
				"URL" => $strSectionUrl,
				"MODULE_ID" => false,
				"CALLBACK_FUNC" => false,
				"EXTERNAL_ID" => $arFields["IBLOCK_SECTION"]."_".$arFields["MODIFIED_BY"],
				"PARAMS" => serialize($arLogParams),
				"SOURCE_ID" => $arFields["IBLOCK_SECTION"]
			);

			$serverName = (defined("SITE_SERVER_NAME") && strLen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name");

			$arSonetFields["TEXT_MESSAGE"] = str_replace(array("#TITLE#"),
				array($strSectionName),
				GetMessage("SONET_PHOTO_LOG_MAIL_TEXT"));

			if ($GLOBALS["USER"]->IsAuthorized())
				$arSonetFields["USER_ID"] = $GLOBALS["USER"]->GetID();

			$logID = CSocNetLog::Add($arSonetFields, false);
			if (intval($logID) > 0)
			{
				CSocNetLog::Update($logID, array(
					"TMP_ID" => $logID,
					"RATING_TYPE_ID" => "IBLOCK_SECTION",
					"RATING_ENTITY_ID" => $arFields["IBLOCK_SECTION"]
				));

				if ($bPassword)
				{
					CSocNetLogRights::DeleteByLogID($logID);
					CSocNetLogRights::Add($logID, array("U".$GLOBALS["USER"]->GetID(), "SA"));
				}
				else
				{
					CSocNetLogRights::SetForSonet($logID, $entity_type, $entity_id, "photo", "view", true);
				}			

				CSocNetLog::CounterIncrement($logID);
			}
		}
		
		if ($entity_type == SONET_ENTITY_GROUP)
		{
			$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $logID));
			while ($arRight = $dbRight->Fetch())
			{
				if ($arRight["GROUP_CODE"] == "SG".$entity_id."_".SONET_ROLES_USER)
				{
					$title_tmp = str_replace(Array("\r\n", "\n"), " ", $strSectionName);
					$title = TruncateText($title_tmp, 100);
					$title_out = TruncateText($title_tmp, 255);

					$arNotifyParams = array(
						"LOG_ID" => $logID,
						"GROUP_ID" => array($entity_id),
						"NOTIFY_MESSAGE" => "",
						"FROM_USER_ID" => $arFields["MODIFIED_BY"],
						"URL" => $strSectionUrl,
						"MESSAGE" => GetMessage("SONET_IM_NEW_PHOTO", Array(
							"#title#" => "<a href=\"#URL#\" class=\"bx-notifier-item-action\">".$title."</a>",
						)),
						"MESSAGE_OUT" => GetMessage("SONET_IM_NEW_PHOTO", Array(
							"#title#" => $title_out
						))." (#URL#)",
						"EXCLUDE_USERS" => array($arFields["MODIFIED_BY"])
					);

					CSocNetSubscription::NotifyGroup($arNotifyParams);
					break;
				}
			}
		}
	}

	public static function OnAfterPhotoDrop($arFields, $arComponentParams)
	{
		if (
			array_key_exists("IS_SOCNET", $arComponentParams)
			&& $arComponentParams["IS_SOCNET"] == "Y"
			&& array_key_exists("USER_ALIAS", $arComponentParams)
			&& strlen($arComponentParams["USER_ALIAS"]) > 0
		)
		{
			$arTmp = explode("_", $arComponentParams["USER_ALIAS"]);
			if (
				is_array($arTmp)
				&& count($arTmp) == 2
			)
			{
				$entity_type = $arTmp[0];
				$entity_id = $arTmp[1];

				if ($entity_type == "group")
					$entity_type = SONET_ENTITY_GROUP;
				elseif ($entity_type == "user")
					$entity_type = SONET_ENTITY_USER;
			}

			if (
				(
					!in_array($entity_type, array(SONET_ENTITY_GROUP, SONET_ENTITY_USER))
					|| intval($entity_id) <= 0
				)
				&& intval($arFields["SECTION_ID"]) > 0
			)
			{
				$rsPath = CIBlockSection::GetNavChain(intval($arFields["IBLOCK_ID"]), $arFields["SECTION_ID"]);
				if($arPath = $rsPath->Fetch())
				{
					$entity_type = SONET_ENTITY_USER;
					$entity_id = $arPath["CREATED_BY"];
				}
			}

			if (
				!in_array($entity_type, array(SONET_ENTITY_GROUP, SONET_ENTITY_USER))
				|| intval($entity_id) <= 0
			)
				return;
		}
		else
			return;

		$db_res = CSocNetLog::GetList(
			array(),
			array(
				"ENTITY_TYPE" => $entity_type,
				"ENTITY_ID" => $entity_id,
				"EVENT_ID" => "photo",
				"SOURCE_ID" => $arFields["SECTION_ID"]
			)
		);
		while ($db_res && $res = $db_res->Fetch())
		{
			if (strlen($res["PARAMS"]) > 0)
			{
				$arResParams = unserialize($res["PARAMS"]);
			}
			else
			{
				continue;
			}

			if (is_array($arResParams) && in_array($arFields["ID"], $arResParams["arItems"]))
			{
				$arResParams["arItems"] = array_diff($arResParams["arItems"], array($arFields["ID"]));
			}
			else
			{
				continue;
			}

			if (count($arResParams["arItems"]) <= 0)
			{
				CSocNetLog::Delete($res["ID"]);
			}
			else
			{
				$arResParams["COUNT"]--;
				$arSonetFields = array(
					"PARAMS" => serialize($arResParams),
					"LOG_UPDATE" => $res["LOG_UPDATE"]
				);

				CSocNetLog::Update($res["ID"], $arSonetFields);
//				CSocNetLogRights::SetForSonet($res["ID"], $entity_type, $entity_id, "photo", "view");
			}
		}

		$db_res = CSocNetLog::GetList(
			array(),
			array(
				"ENTITY_TYPE" => $entity_type,
				"ENTITY_ID" => $entity_id,
				"EVENT_ID" => "photo_photo",
				"SOURCE_ID" => $arFields["ID"]
			)
		);
		while ($db_res && $res = $db_res->Fetch())
			CSocNetLog::Delete($res["ID"]);
	}

	public static function OnBeforeSectionDrop($sectionID, $arComponentParams, $arComponentResult, &$arSectionID, &$arElementID)
	{
		if (!CModule::IncludeModule("iblock"))
			return;

		if (
			array_key_exists("IS_SOCNET", $arComponentParams)
			&& $arComponentParams["IS_SOCNET"] == "Y"
			&& array_key_exists("USER_ALIAS", $arComponentParams)
			&& strlen($arComponentParams["USER_ALIAS"]) > 0
		)
		{
			$dbElement = CIBlockElement::GetList(
				array(),
				array(
					"IBLOCK_ID" => $arComponentParams["IBLOCK_ID"],
					"SECTION_ID" => $sectionID,
					"INCLUDE_SUBSECTIONS" => "Y"
				),
				false,
				false,
				array("ID")
			);

			$arElementID = array();
			while ($arElement = $dbElement->Fetch())
				$arElementID[] = $arElement["ID"];

			$dbSection = CIBlockSection::GetList(
				array("BS.LEFT_MARGIN" => "ASC"),
				array(
					"IBLOCK_ID" => $arComponentParams["IBLOCK_ID"],
					">=LEFT_MARGIN" => $arComponentResult["SECTION"]["LEFT_MARGIN"],
					"<=RIGHT_MARGIN" => $arComponentResult["SECTION"]["RIGHT_MARGIN"],
				),
				false,
				array("ID")
			);

			$arSectionID = array();
			while ($arSection = $dbSection->Fetch())
				$arSectionID[] = $arSection["ID"];
		}
		else
		{
			return;
		}
	}

	public static function OnAfterSectionDrop($ID, $arFields, $arComponentParams, $arComponentResult)
	{
		if (
			array_key_exists("IS_SOCNET", $arComponentParams)
			&& $arComponentParams["IS_SOCNET"] == "Y"
			&& array_key_exists("USER_ALIAS", $arComponentParams)
			&& strlen($arComponentParams["USER_ALIAS"]) > 0
		)
		{
			$arTmp = explode("_", $arComponentParams["USER_ALIAS"]);
			if (
				is_array($arTmp)
				&& count($arTmp) == 2
			)
			{
				$entity_type = $arTmp[0];
				$entity_id = $arTmp[1];

				if ($entity_type == "group")
					$entity_type = SONET_ENTITY_GROUP;
				elseif ($entity_type == "user")
					$entity_type = SONET_ENTITY_USER;
			}

			if (
				!in_array($entity_type, array(SONET_ENTITY_GROUP, SONET_ENTITY_USER))
				|| intval($entity_id) <= 0
			)
			{
				$entity_type = SONET_ENTITY_USER;
				$entity_id = $arComponentResult["GALLERY"]["CREATED_BY"];
			}

			if (
				!in_array($entity_type, array(SONET_ENTITY_GROUP, SONET_ENTITY_USER))
				|| intval($entity_id) <= 0
			)
				return;

		}
		else
			return;

		if (array_key_exists("SECTIONS_IN_TREE", $arFields))
		{
			foreach ($arFields["SECTIONS_IN_TREE"] as $sectionID)
			{
				$db_res = CSocNetLog::GetList(
					array(),
					array(
						"ENTITY_TYPE" => $entity_type,
						"ENTITY_ID" => $entity_id,
						"EVENT_ID" => "photo",
						"SOURCE_ID" => $sectionID
					)
				);
				while ($db_res && $res = $db_res->Fetch())
					CSocNetLog::Delete($res["ID"]);
			}
		}

		if (array_key_exists("ELEMENTS_IN_TREE", $arFields))
		{
			foreach ($arFields["ELEMENTS_IN_TREE"] as $elementID)
			{
				$db_res = CSocNetLog::GetList(
					array(),
					array(
						"ENTITY_TYPE" => $entity_type,
						"ENTITY_ID" => $entity_id,
						"EVENT_ID" => "photo_photo",
						"SOURCE_ID" => $elementID
					)
				);
				while ($db_res && $res = $db_res->Fetch())
					CSocNetLog::Delete($res["ID"]);
			}
		}
	}

	public static function OnAfterSectionEdit($arFields, $arComponentParams, $arComponentResult)
	{
		if (!CModule::IncludeModule("iblock"))
			return;

		if (
			array_key_exists("IS_SOCNET", $arComponentParams)
			&& $arComponentParams["IS_SOCNET"] == "Y"
			&& array_key_exists("USER_ALIAS", $arComponentParams)
			&& strlen($arComponentParams["USER_ALIAS"]) > 0
		)
		{
			$arTmp = explode("_", $arComponentParams["USER_ALIAS"]);
			if (
				is_array($arTmp)
				&& count($arTmp) == 2
			)
			{
				$entity_type = $arTmp[0];
				$entity_id = $arTmp[1];

				if ($entity_type == "group")
					$entity_type = SONET_ENTITY_GROUP;
				elseif ($entity_type == "user")
					$entity_type = SONET_ENTITY_USER;
			}

			if (
				!in_array($entity_type, array(SONET_ENTITY_GROUP, SONET_ENTITY_USER))
				|| intval($entity_id) <= 0
			)
			{
				$rsPath = CIBlockSection::GetNavChain(intval($arFields["IBLOCK_ID"]), $arComponentResult["SECTION"]["ID"]);
				if($arPath = $rsPath->Fetch())
				{
					$entity_type = SONET_ENTITY_USER;
					$entity_id = $arPath["CREATED_BY"];
				}
			}

			if (
				!in_array($entity_type, array(SONET_ENTITY_GROUP, SONET_ENTITY_USER))
				|| intval($entity_id) <= 0
			)
				return;
		}
		else
			return;

		if (
			strlen(trim($arComponentResult["SECTION"]["PASSWORD"])) <= 0
			&& strlen($arFields["UF_PASSWORD"]) > 0
		)
		{
			// hide photos

			$dbSection = CIBlockSection::GetList(
				array("BS.LEFT_MARGIN"=>"ASC"),
				array(
					"IBLOCK_ID" => $arFields["IBLOCK_ID"],
					">=LEFT_MARGIN" => $arComponentResult["SECTION"]["LEFT_MARGIN"],
					"<=RIGHT_MARGIN" => $arComponentResult["SECTION"]["RIGHT_MARGIN"],
				),
				false,
				array("ID", "CREATED_BY")
			);

			while ($arSection = $dbSection->Fetch())
			{
				$db_res = CSocNetLog::GetList(
					array(),
					array(
						"ENTITY_TYPE" => $entity_type,
						"ENTITY_ID" => $entity_id,
						"EVENT_ID"	=> "photo",
						"SOURCE_ID" => $arSection["ID"]
					)
				);
				while ($db_res && $res = $db_res->Fetch())
				{
					CSocNetLogRights::DeleteByLogID($res["ID"]);
					CSocNetLogRights::Add($res["ID"], array("U".$arSection["CREATED_BY"], "SA"));
				}
			}
			
			$dbElement = CIBlockElement::GetList(
				array(),
				array(
					"IBLOCK_ID" => $arFields["IBLOCK_ID"],
					"SECTION_ID" => $arComponentResult["SECTION"]["ID"],
					"INCLUDE_SUBSECTIONS" => "Y"
				),
				false,
				false,
				array("ID", "CREATED_BY")
			);

			while ($arElement = $dbElement->Fetch())
			{
				$db_res = CSocNetLog::GetList(
					array(),
					array(
						"EVENT_ID"	=> "photo_photo",
						"SOURCE_ID" => $arElement["ID"]
					)
				);
				while ($db_res && $res = $db_res->Fetch())
				{
					CSocNetLogRights::DeleteByLogID($res["ID"]);
					CSocNetLogRights::Add($res["ID"], array("U".$arElement["CREATED_BY"], "SA"));
				}
			}
		}
		elseif (
			strlen(trim($arComponentResult["SECTION"]["PASSWORD"])) > 0
			&& strlen($arFields["UF_PASSWORD"]) <= 0
		)
		{
			// show photos

			$dbSection = CIBlockSection::GetList(
				array("BS.LEFT_MARGIN"=>"ASC"),
				array(
					"IBLOCK_ID" => $arFields["IBLOCK_ID"],
					">=LEFT_MARGIN" => $arComponentResult["SECTION"]["LEFT_MARGIN"],
					"<=RIGHT_MARGIN" => $arComponentResult["SECTION"]["RIGHT_MARGIN"],
				),
				false,
				array("ID", "CREATED_BY")
			);

			while ($arSection = $dbSection->Fetch())
			{
				$db_res = CSocNetLog::GetList(
					array(),
					array(
						"ENTITY_TYPE" => $entity_type,
						"ENTITY_ID" => $entity_id,
						"EVENT_ID"	=> "photo",
						"SOURCE_ID" => $arSection["ID"]
					)
				);
				while ($db_res && $res = $db_res->Fetch())
				{
					CSocNetLogRights::DeleteByLogID($res["ID"]);
					CSocNetLogRights::SetForSonet($res["ID"], $entity_type, $entity_id, "photo", "view");
				}
			}
			
			$dbElement = CIBlockElement::GetList(
				array(),
				array(
					"IBLOCK_ID" => $arFields["IBLOCK_ID"],
					"SECTION_ID" => $arComponentResult["SECTION"]["ID"],
					"INCLUDE_SUBSECTIONS" => "Y"
				),
				false,
				false,
				array("ID", "CREATED_BY")
			);

			while ($arElement = $dbElement->Fetch())
			{
				$db_res = CSocNetLog::GetList(
					array(),
					array(
						"EVENT_ID"	=> "photo_photo",
						"SOURCE_ID" => $arElement["ID"]
					)
				);
				while ($db_res && $res = $db_res->Fetch())
				{
					CSocNetLogRights::DeleteByLogID($res["ID"]);
					CSocNetLogRights::SetForSonet($res["ID"], $entity_type, $entity_id, "photo", "view");
				}
			}
		}
		else
		{
			return;
		}
	}
}

class CSocNetPhotoCommentEvent
{
	public static function AddComment_PhotoAlbum($arFields)
	{
		$dbResult = CSocNetLog::GetList(
			array(),
			array(
				"EVENT_ID" => array("photo"),
				"ID" => $arFields["LOG_ID"]
			),
			false,
			false,
			array("ID", "SOURCE_ID", "USER_ID", "TITLE", "URL", "PARAMS")
		);

		$arLog = $dbResult->Fetch();
		if (!$arLog)
			$sError = GetMessage("SONET_PHOTO_ADD_COMMENT_SOURCE_ERROR");

		if (
			!$sError
			&& intval($arLog["USER_ID"]) > 0
			&& intval($arLog["SOURCE_ID"]) > 0
			&& $arLog["USER_ID"] != $GLOBALS["USER"]->GetID()
			&& CModule::IncludeModule("im")
			&& CModule::IncludeModule("iblock")
		)
		{
			$rsUnFollower = CSocNetLogFollow::GetList(
				array(
					"USER_ID" => $arLog["USER_ID"],
					"CODE" => "L".$arLog["ID"],
					"TYPE" => "N"
				),
				array("USER_ID")
			);

			$arUnFollower = $rsUnFollower->Fetch();
			if (!$arUnFollower)
			{
				$rsSection = CIBlockSection::GetByID($arLog["SOURCE_ID"]);
				if ($arSection = $rsSection->GetNext())
				{
					$arMessageFields = array(
						"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
						"TO_USER_ID" => $arLog["USER_ID"],
						"FROM_USER_ID" => $GLOBALS["USER"]->GetID(),
						"NOTIFY_TYPE" => IM_NOTIFY_FROM,
						"NOTIFY_MODULE" => "photogallery",
						"NOTIFY_EVENT" => "comment",
						"LOG_ID" => $arLog["ID"],
					);

					$arTmp = CSocNetLogTools::ProcessPath(array("SECTION_URL" => $arLog["URL"]), $arLog["USER_ID"]);
					$serverName = $arTmp["SERVER_NAME"];
					$arLog["URL"] = $arTmp["URLS"]["SECTION_URL"];

					$arMessageFields["NOTIFY_TAG"] = "PHOTOALBUM|COMMENT|".$arLog["SOURCE_ID"];
					$arMessageFields["NOTIFY_MESSAGE"] = GetMessage("SONET_PHOTOALBUM_IM_COMMENT", Array(
						"#album_title#" => "<a href=\"".$arLog["URL"]."\" class=\"bx-notifier-item-action\">".$arSection["NAME"]."</a>"
					));
					$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage("SONET_PHOTOALBUM_IM_COMMENT", Array(
						"#album_title#" => $arSection["NAME"]
					))." (".$serverName.$arLog["URL"].")#BR##BR#".$arFields["TEXT_MESSAGE"];

					$ID = CIMNotify::Add($arMessageFields);

					if(!empty($arFields["MENTION_ID"]))
					{
						//
					}
				}
			}
		}

		return array(
			"NO_SOURCE" => "Y",
			"ERROR" => $sError,
			"NOTES" => ""
		);
	}
	
	public static function FindLogType($logID)
	{
		$dbResult = CSocNetLog::GetList(
			array(),
			array("ID" => $logID),
			false,
			false,
			array("ID", "SOURCE_ID", "USER_ID", "TITLE", "URL", "PARAMS")
		);

		if ($arLog = $dbResult->Fetch())
		{
			if (strlen($arLog["PARAMS"]) > 0)
			{
				$arTmp = unserialize(htmlspecialcharsback($arLog["PARAMS"]));
				if ($arTmp)
				{
					$FORUM_ID = $arTmp["FORUM_ID"];
					$BLOG_ID = $arTmp["BLOG_ID"];

					if (
						array_key_exists("SECTION_NAME", $arTmp)
						&& strlen($arTmp["SECTION_NAME"]) > 0
					)
					{
						$log_section_name = $arTmp["SECTION_NAME"];
					}

					if (
						array_key_exists("SECTION_URL", $arTmp)
						&& strlen($arTmp["SECTION_URL"]) > 0
					)
					{
						$log_section_url = $arTmp["SECTION_URL"];
					}
				}
			}

			if (
				$FORUM_ID > 0 
				&& intval($arLog["SOURCE_ID"]) > 0
			)
			{
				$bFoundForum = true;
			}
			elseif (
				$BLOG_ID > 0 
				&& intval($arLog["SOURCE_ID"]) > 0
			)
			{
				$bFoundBlog = true;
			}
		}

		return array(
			"TYPE" => ($bFoundForum ? "FORUM" : ($bFoundBlog ? "BLOG" : false)),
			"ENTITY_ID" => ($bFoundForum ? $FORUM_ID : ($bFoundBlog ? $BLOG_ID : false)),
			"SECTION_NAME" => $log_section_name,
			"SECTION_URL" => $log_section_url,
			"LOG" => $arLog
		);
	}

	public static function AddComment_Photo($arFields)
	{
		$arLogType = self::FindLogType($arFields["LOG_ID"]);

		if ($arLogType["TYPE"] == "FORUM")
		{
			$arReturn = CSocNetPhotoCommentEvent::AddComment_Photo_Forum($arFields, $arLogType["ENTITY_ID"], $arLogType["LOG"]);
		}
		elseif ($arLogType["TYPE"] == "BLOG")
		{
			$arReturn = CSocNetPhotoCommentEvent::AddComment_Photo_Blog($arFields, $arLogType["ENTITY_ID"], $arLogType["LOG"]);
		}
		else
		{
			$arReturn =  array(
				"SOURCE_ID" => false,
				"ERROR" => GetMessage("SONET_PHOTO_ADD_COMMENT_SOURCE_ERROR"),
				"NOTES" => ""
			);
		}

		if (
			$arLogType["TYPE"]
			&& !empty($arReturn["IM_MESSAGE"])
		)
		{
			$arFieldsIM = Array(
				"TYPE" => "COMMENT",
				"TITLE" => $arLogType["LOG"]["TITLE"],
				"MESSAGE" => $arReturn["IM_MESSAGE"],
				"URL" => $arLogType["LOG"]["URL"],
				"SECTION_NAME" => $log_section_name,
				"SECTION_URL" => $log_section_url,
				"ID" => $arLogType["LOG"]["SOURCE_ID"],
				"PHOTO_AUTHOR_ID" => $arLogType["LOG"]["USER_ID"],
				"COMMENT_AUTHOR_ID" => $GLOBALS["USER"]->GetID(),
			);
			CSocNetPhotoCommentEvent::NotifyIm($arFieldsIM);
		}

		return $arReturn;
	}

	public static function AddComment_Photo_Forum($arFields, $FORUM_ID, $arLog)
	{
		if (!CModule::IncludeModule("forum"))
			return false;

		if (!CModule::IncludeModule("iblock"))
			return false;

		$ufFileID = array();
		$ufDocID = array();

		$arElement = false;
		$arFilteredText = array();

		$arFilter = array("ID" => $arLog["SOURCE_ID"]);
		$arSelectedFields = array("IBLOCK_ID", "ID", "NAME", "TAGS", "CODE", "IBLOCK_SECTION_ID", "DETAIL_PAGE_URL",
				"CREATED_BY", "PREVIEW_PICTURE", "PREVIEW_TEXT", "PROPERTY_FORUM_TOPIC_ID", "PROPERTY_FORUM_MESSAGE_CNT");
		$db_res = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelectedFields);
		if ($db_res && $res = $db_res->GetNext())
			$arElement = $res;

		if ($arElement)
		{
			// check iblock properties
			CSocNetLogTools::AddComment_Review_CheckIBlock_Forum($arElement);

			$dbMessage = CForumMessage::GetList(
				array(),
				array(
					"PARAM2" => $arElement["ID"]
				)
			);

			if (!$arMessage = $dbMessage->Fetch())
			{
				// Add Topic
				$TOPIC_ID = CSocNetLogTools::AddComment_Review_CreateRoot_Forum($arElement, $FORUM_ID);
				$bNewTopic = true;
			}
			else
				$TOPIC_ID = $arMessage["TOPIC_ID"];

			if(intval($TOPIC_ID) > 0)
			{
				if (COption::GetOptionString("forum", "FILTER", "Y") == "Y")
				{
					$arFields["TEXT_MESSAGE"] = $arFilteredText["TEXT_MESSAGE"] = CFilterUnquotableWords::Filter($arFields["TEXT_MESSAGE"]);
					$arFilteredText["MESSAGE"] = CFilterUnquotableWords::Filter($arFields["MESSAGE"]);
				}

				// Add comment
				$messageID = false;
				$arFieldsMessage = array(
					"POST_MESSAGE" => $arFields["TEXT_MESSAGE"],
					"USE_SMILES" => "Y",
					"PARAM2" => $arElement["ID"],
					"APPROVED" => "Y"
				);

				$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("SONET_COMMENT", $arTmp);
				if (is_array($arTmp))
				{
					if (array_key_exists("UF_SONET_COM_DOC", $arTmp))
						$GLOBALS["UF_FORUM_MESSAGE_DOC"] = $arTmp["UF_SONET_COM_DOC"];
					elseif (array_key_exists("UF_SONET_COM_FILE", $arTmp))
					{
						$arFieldsMessage["FILES"] = array();
						foreach($arTmp["UF_SONET_COM_FILE"] as $file_id)
							$arFieldsMessage["FILES"][] = array("FILE_ID" => $file_id);
					}
				}

				$messageID = ForumAddMessage("REPLY", $FORUM_ID, $TOPIC_ID, 0, $arFieldsMessage, $sError, $sNote);

				if (!$messageID)
					$strError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
				else
				{
					// get UF DOC value and FILE_ID there
					$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $messageID));
					while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
						$ufFileID[] = $arAddedMessageFiles["FILE_ID"];

					$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $messageID, LANGUAGE_ID);

					CSocNetLogTools::AddComment_Review_UpdateElement_Forum($arElement, $TOPIC_ID, $bNewTopic);
				}
			}
			else
				$strError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
		}
		else
			$strError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");

		return array_merge(
			$arFilteredText, 
			array(
				"SOURCE_ID" => $messageID,
				"RATING_TYPE_ID" => "FORUM_POST",
				"RATING_ENTITY_ID" => $messageID,
				"ERROR" => $strError,
				"NOTES" => "",
				"UF" => array(
					"FILE" => $ufFileID,
					"DOC" => $ufDocID
				),
				"IM_MESSAGE" => ($messageID ? $arFields["TEXT_MESSAGE"] : false)
			)
		);
	}

	public static function AddComment_Photo_Blog($arFields, $BLOG_ID, $arLog)
	{
		if (!CModule::IncludeModule("blog"))
			return false;

		if (!CModule::IncludeModule("iblock"))
			return false;

		$arElement = false;

		$arFilter = array("ID" => $arLog["SOURCE_ID"]);
		$arSelectedFields = array("IBLOCK_ID", "ID", "NAME", "TAGS", "CODE", "IBLOCK_SECTION_ID", "DETAIL_PAGE_URL",
				"CREATED_BY", "PREVIEW_PICTURE", "PREVIEW_TEXT", "PROPERTY_BLOG_POST_ID", "PROPERTY_BLOG_COMMENT_CNT", "PROPERTY_REAL_PICTURE");
		$db_res = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelectedFields);
		if ($db_res && $res = $db_res->GetNext())
			$arElement = $res;

		if ($arElement)
		{
			// check iblock properties
			$ELEMENT_BLOG_POST_ID = CSocNetLogTools::AddComment_Review_CheckIBlock_Blog($arElement);

			if ($ELEMENT_BLOG_POST_ID <= 0)
			{
				// Add Post
				$POST_ID = CSocNetLogTools::AddComment_Review_CreateRoot_Blog($arElement, $BLOG_ID);
				$bNewPost = true;
			}
			else
				$POST_ID = $ELEMENT_BLOG_POST_ID;

			if(intval($POST_ID) > 0)
			{
				// Add comment
				$commentID = false;

				$UserIP = CBlogUser::GetUserIP();
				$arFieldsComment = Array(
					"POST_ID" => $POST_ID,
					"BLOG_ID" => $BLOG_ID,
					"POST_TEXT" => trim($arFields["TEXT_MESSAGE"]),
					"DATE_CREATE" => ConvertTimeStamp(time()+CTimeZone::GetOffset(), "FULL"),
					"AUTHOR_IP" => $UserIP[0],
					"AUTHOR_IP1" => $UserIP[1],
					"PARENT_ID" => false
				);

				if($GLOBALS["USER"]->IsAuthorized())
					$arFieldsComment["AUTHOR_ID"] = $GLOBALS["USER"]->GetID();

				$commentID = CBlogComment::Add($arFieldsComment);
				if (!$commentID)
					$strError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
				else
					CSocNetLogTools::AddComment_Review_UpdateElement_Blog($arElement, $POST_ID, $BLOG_ID, $bNewPost);
			}
			else
				$strError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
		}
		else
			$strError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");

		return array(
			"SOURCE_ID" => $commentID,
			"RATING_TYPE_ID" => "BLOG_COMMENT",
			"RATING_ENTITY_ID" => $commentID,
			"ERROR" => $strError,
			"NOTES" => "",
			"IM_MESSAGE" => ($arFieldsComment ? $arFieldsComment["POST_TEXT"] : false)
		);
	}
	
	public static function UpdateComment_Photo($arFields)
	{
		$arLogType = self::FindLogType($arFields["LOG_ID"]);

		if ($arLogType["TYPE"] == "FORUM")
		{
			$arReturn = CSocNetLogTools::UpdateComment_Forum($arFields);
		}
		elseif ($arLogType["TYPE"] == "BLOG")
		{
//			$arReturn = CSocNetPhotoCommentEvent::UpdateComment_Photo_Blog($arFields, $arLogType["ENTITY_ID"], $arLogType["LOG"]);
		}
		else
		{
			$arReturn =  array(
				"SOURCE_ID" => false,
				"ERROR" => GetMessage("SONET_PHOTO_UPDATE_COMMENT_SOURCE_ERROR"),
				"NOTES" => ""
			);
		}

		return $arReturn;
	}

	public static function DeleteComment_Photo($arFields)
	{
		$arLogType = self::FindLogType($arFields["LOG_ID"]);

		if ($arLogType["TYPE"] == "FORUM")
		{
			$arReturn = CSocNetLogTools::DeleteComment_Forum($arFields);
		}
		elseif ($arLogType["TYPE"] == "BLOG")
		{
//			$arReturn = CSocNetPhotoCommentEvent::DeleteComment_Photo_Blog($arFields, $arLogType["ENTITY_ID"], $arLogType["LOG"]);
		}
		else
		{
			$arReturn =  array(
				"ERROR" => GetMessage("SONET_PHOTO_DELETE_COMMENT_SOURCE_ERROR"),
				"NOTES" => ""
			);
		}

		return $arReturn;
	}

	public function SetVars($arParams, $arResult)
	{
		if (
			!array_key_exists("IS_SOCNET", $arParams)
			|| $arParams["IS_SOCNET"] != "Y"
		)
			return;
		else
			$this->IsSocnet = true;

		$this->arPath["PATH_TO_SMILE"] = $arParams["PATH_TO_SMILE"];
		$this->arPath["DETAIL_URL"] = $arParams["~DETAIL_URL"];
		$this->arPath["SECTION_URL"] = $arParams["~SECTION_URL"];

		if (strtolower($arParams["COMMENTS_TYPE"]) == "forum")
			$this->ForumID = $arParams["FORUM_ID"];
		elseif (strtolower($arParams["COMMENTS_TYPE"]) == "blog")
		{
			$this->PhotoElementID = $arParams["ELEMENT_ID"];
			$this->PostID = $arResult["COMMENT_ID"];

			if (CModule::IncludeModule("blog"))
				if ($arBlog = CBlog::GetByUrl($arParams["BLOG_URL"]))
					$this->BlogID = $arBlog["ID"];
		}

		$this->bIsGroup = false;
		$this->entity_type = false;
		$this->entity_id = false;
		if (
			array_key_exists("USER_ALIAS", $arParams)
			&& strlen($arParams["USER_ALIAS"]) > 0
		)
		{
			$arTmp = explode("_", $arParams["USER_ALIAS"]);
			if (
				is_array($arTmp)
				&& count($arTmp) == 2
			)
			{
				$entity_type = $arTmp[0];
				$this->entity_id = $arTmp[1];

				if ($entity_type == "group")
					$this->entity_type = SONET_ENTITY_GROUP;
				else
					$this->entity_type = SONET_ENTITY_USER;
			}
		}
	}

	public function OnAfterPhotoCommentAddForum($ID, $arFields)
	{
		static $arSiteWorkgroupsPage;

		if (!CModule::IncludeModule('iblock'))
			return;

		if (!$this->IsSocnet)
			return;

		if (
			(
				!array_key_exists("PARAM1", $arFields)
				|| $arFields["PARAM1"] != "IB"
			)
			&& array_key_exists("PARAM2", $arFields)
			&& intval($arFields["PARAM2"]) > 0
		)
		{
			$bSocNetLogRecordExists = false;

			$dbRes = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID"	=> "photo_photo",
					"SOURCE_ID"	=> $arFields["PARAM2"] // file photo id
				),
				false,
				false,
				array("ID", "ENTITY_TYPE", "ENTITY_ID", "TMP_ID", "USER_ID", "TITLE", "URL", "PARAMS")
			);

			if ($arRes = $dbRes->Fetch())
			{
				$log_id = $arRes["ID"];
				$entity_type = $arRes["ENTITY_TYPE"];
				$entity_id = $arRes["ENTITY_ID"];
				$log_title = $arRes["TITLE"];
				$log_url = $arRes["URL"];
				$log_user_id = $arRes["USER_ID"];				

				if (strlen($arRes["PARAMS"]) > 0)
				{
					$arTmp = unserialize($arRes["PARAMS"]);
					if ($arTmp)
					{
						if (
							array_key_exists("SECTION_NAME", $arTmp)
							&& strlen($arTmp["SECTION_NAME"]) > 0
						)
							$log_section_name = $arTmp["SECTION_NAME"];

						if (
							array_key_exists("SECTION_URL", $arTmp)
							&& strlen($arTmp["SECTION_URL"]) > 0
						)
							$log_section_url = $arTmp["SECTION_URL"];
					}
				}

				$bSocNetLogRecordExists = true;
			}
			else
			{
				$rsElement = CIBlockElement::GetByID($arFields["PARAM2"]);
				if ($arElement = $rsElement->Fetch())
				{
					$url = $this->arPath["DETAIL_URL"];

					$sAuthorName = GetMessage("SONET_PHOTO_LOG_GUEST");
					if (intval($arElement["CREATED_BY"]) > 0)
					{
						$rsUser = CUser::GetByID($arElement["CREATED_BY"]);
						if ($arUser = $rsUser->Fetch())
							$sAuthorName = CUser::FormatName(CSite::GetNameFormat(false), $arUser, true, false);
					}

					if (
						in_array( $this->entity_type, array(SONET_ENTITY_USER, SONET_ENTITY_GROUP))
						&& intval($this->entity_id) > 0
					)
					{
						$entity_type = $this->entity_type;
						$entity_id = $this->entity_id;
						$alias = ($this->entity_type == SONET_ENTITY_GROUP ? "group" : "user")."_".$this->entity_id;
					}

					$arLogParams = array(
						"FORUM_ID" => intval($this->ForumID)
					);

					$rsIBlock = CIBlock::GetByID($arElement["IBLOCK_ID"]);
					if($arIBlock = $rsIBlock->Fetch())
					{
						$arLogParams["IBLOCK_ID"] = $arIBlock["ID"];
						$arLogParams["IBLOCK_TYPE"] = $arIBlock["IBLOCK_TYPE_ID"];
					}

					$rsSection = CIBlockSection::GetByID($arElement["IBLOCK_SECTION_ID"]);
					if ($arSection = $rsSection->Fetch())
					{
						$arLogParams["SECTION_ID"] = $arSection["ID"];
						$arLogParams["SECTION_NAME"] = $arSection["NAME"];
						$arLogParams["SECTION_URL"] = str_replace("#SECTION_ID#", $arSection["ID"], $this->arPath["SECTION_URL"]);

						$arSectionPath = array();
						$bPassword = false;

						$dbSection = CIBlockSection::GetList(
							array("LEFT_MARGIN" => "ASC"),
							array(
								"IBLOCK_ID" => intval($arLogParams["IBLOCK_ID"]),
								"<=LEFT_BORDER" => intval($arSection["LEFT_MARGIN"]),
								">=RIGHT_BORDER" => intval($arSection["RIGHT_MARGIN"]),
								"<=DEPTH_LEVEL" => intval($arSection["DEPTH_LEVEL"]),
							),
							false, 
							array("ID", "IBLOCK_ID", "NAME", "CODE", "CREATED_BY", "DEPTH_LEVEL", "LEFT_MARGIN", "RIGHT_MARGIN", "UF_PASSWORD")
						);

						while ($arPath = $dbSection->Fetch())
						{
							$arSectionPath[] = $arPath;
							if (strlen(trim($arPath["UF_PASSWORD"])) > 0)
							{
								$bPassword = true;
								break;
							}
						}

						if (!$alias)
						{
							$entity_type = SONET_ENTITY_USER;
							$entity_id = $arSectionPath[0]["CREATED_BY"];
							$alias = $arSectionPath[0]["CODE"];
						}

						if (!$arSiteWorkgroupsPage && IsModuleInstalled("extranet") && $entity_type == SONET_ENTITY_GROUP)
						{
							$rsSite = CSite::GetList($by="sort", $order="desc", Array("ACTIVE" => "Y"));
							while($arSite = $rsSite->Fetch())
								$arSiteWorkgroupsPage[$arSite["ID"]] = COption::GetOptionString("socialnetwork", "workgroups_page", $arSite["DIR"]."workgroups/", $arSite["ID"]);
						}

						if (is_set($arLogParams["SECTION_URL"]) && is_array($arSiteWorkgroupsPage) && $entity_type == SONET_ENTITY_GROUP)
							foreach($arSiteWorkgroupsPage as $groups_page)
								if (strpos($arLogParams["SECTION_URL"], $groups_page) === 0)
									$arLogParams["SECTION_URL"] = "#GROUPS_PATH#".substr($arLogParams["SECTION_URL"], strlen($groups_page), strlen($arLogParams["SECTION_URL"])-strlen($groups_page));
					}

					$arLogParams["ALIAS"] = $alias;

					$arSonetFields = array(
						"ENTITY_TYPE" => $entity_type,
						"ENTITY_ID" => $entity_id,
						"EVENT_ID" => "photo_photo",
						"LOG_DATE" => $arElement["TIMESTAMP_X"],
						"TITLE_TEMPLATE" => str_replace("#AUTHOR_NAME#", $sAuthorName, GetMessage("SONET_PHOTOPHOTO_LOG_1")),
						"TITLE" => $arElement["NAME"],
						"MESSAGE" => "",
						"TEXT_MESSAGE" => "",
						"URL" => CComponentEngine::MakePathFromTemplate($url, array(
							"ELEMENT_ID" => $arElement["ID"],
							"element_id" => $arElement["ID"],
							"SECTION_ID" => $arElement["IBLOCK_SECTION_ID"],
							"section_id" => $arElement["IBLOCK_SECTION_ID"]
						)),
						"MODULE_ID" => false,
						"CALLBACK_FUNC" => false,
						"SOURCE_ID" => $arElement["ID"],
						"PARAMS" => serialize($arLogParams),
						"RATING_TYPE_ID" => "IBLOCK_ELEMENT",
						"RATING_ENTITY_ID"=> $arElement["ID"],
					);

					if (intval($arElement["CREATED_BY"]) > 0)
						$arSonetFields["USER_ID"] = $arElement["CREATED_BY"];

					$log_id = CSocNetLog::Add($arSonetFields, false);
					if (intval($log_id) > 0)
					{
						$log_title = $arSonetFields["TITLE"];
						$log_url = $arSonetFields["URL"];
						$log_section_name = $arLogParams["SECTION_NAME"];
						$log_section_url = $arLogParams["SECTION_URL"];
						$log_user_id = $arSonetFields["USER_ID"];

						CSocNetLog::Update($log_id, array("TMP_ID" => $log_id));
						if ($bPassword)
						{
							CSocNetLogRights::DeleteByLogID($log_id);
							CSocNetLogRights::Add($log_id, array("U".$GLOBALS["USER"]->GetID(), "SA"));
						}
						else
						{
							CSocNetLogRights::SetForSonet($log_id, $entity_type, $entity_id, "photo", "view");
						}
					}
				}
			}

			if (intval($log_id) > 0)
			{
				$arForum = CForumNew::GetByID($this->ForumID);
				
				$parser = new textParser(LANGUAGE_ID, $this->arPath["PATH_TO_SMILE"]);
				$parser->image_params["width"] = false;
				$parser->image_params["height"] = false;
				
				$arAllow = array(
					"HTML" => "N",
					"ANCHOR" => "N",
					"BIU" => "N",
					"IMG" => "N",
					"LIST" => "N",
					"QUOTE" => "N",
					"CODE" => "N",
					"FONT" => "N",
					"UPLOAD" => $arForum["ALLOW_UPLOAD"],
					"NL2BR" => "N",
					"SMILES" => "N",
					"VIDEO" => "N"
				);
				
				$url = $this->arPath["DETAIL_URL"];
				
				if ($bSocNetLogRecordExists)
				{
					$arMessage = CForumMessage::GetByIDEx($ID);

					$url = CComponentEngine::MakePathFromTemplate($arParams["~URL_TEMPLATES_MESSAGE"],
						array("FID" => $arMessage["FORUM_ID"], "TID" => $arMessage["TOPIC_ID"], "MID" => $ID));

					$arFieldsForSocnet = array(
						"ENTITY_TYPE" => $entity_type,
						"ENTITY_ID" => $entity_id,
						"EVENT_ID" => "photo_comment",
						"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
						"MESSAGE" => $parser->convert(empty($arFields["POST_MESSAGE_FILTER"]) ? $arFields["POST_MESSAGE"] : $arFields["POST_MESSAGE_FILTER"], $arAllow),
						"TEXT_MESSAGE" => $parser->convert4mail(empty($arFields["POST_MESSAGE_FILTER"]) ? $arFields["POST_MESSAGE"] : $arFields["POST_MESSAGE_FILTER"]),
						"MODULE_ID" => false,
						"SOURCE_ID" => $ID,
						"LOG_ID" => $log_id,
						"RATING_TYPE_ID" => "FORUM_POST",
						"RATING_ENTITY_ID" => $ID,
					);

					if (intVal($arMessage["AUTHOR_ID"]) > 0)
						$arFieldsForSocnet["USER_ID"] = $arMessage["AUTHOR_ID"];

					$comment_id = CSocNetLogComments::Add($arFieldsForSocnet, false, false);
					if ($comment_id)
					{
						CSocNetLog::CounterIncrement($comment_id, false, false, "LC");

						$arFieldsIM = Array(
							"TYPE" => "COMMENT",
							"TITLE" => $log_title,
							"MESSAGE" => $arFieldsForSocnet["MESSAGE"],
							"URL" => $log_url,
							"SECTION_NAME" => $log_section_name,
							"SECTION_URL" => $log_section_url,
							"ID" => $arFields["PARAM2"],
							"PHOTO_AUTHOR_ID" => $log_user_id,
							"COMMENT_AUTHOR_ID" => $arMessage["AUTHOR_ID"],
						);
						CSocNetPhotoCommentEvent::NotifyIm($arFieldsIM);
					}
				}
				else
				{
					$dbComments = CForumMessage::GetListEx(
						array(),
						array('TOPIC_ID' => $arFields["TOPIC_ID"], "NEW_TOPIC" => "N")
					);

					while ($arComment = $dbComments->GetNext())
					{
						$url = CComponentEngine::MakePathFromTemplate($arParams["~URL_TEMPLATES_MESSAGE"],
							array("FID" => $arComment["FORUM_ID"], "TID" => $arComment["TOPIC_ID"], "MID" => $arComment["ID"]));

						$arFieldsForSocnet = array(
							"ENTITY_TYPE" => $entity_type,
							"ENTITY_ID" => $entity_id,
							"EVENT_ID" => "photo_comment",
							"=LOG_DATE" => $GLOBALS["DB"]->CharToDateFunction($arComment["POST_DATE"], "FULL", SITE_ID),
							"MESSAGE" => $parser->convert(empty($arComment["POST_MESSAGE_FILTER"]) ? $arComment["POST_MESSAGE"] : $arComment["POST_MESSAGE_FILTER"], $arAllow),
							"TEXT_MESSAGE" => $parser->convert4mail(empty($arComment["POST_MESSAGE_FILTER"]) ? $arComment["POST_MESSAGE"] : $arComment["POST_MESSAGE_FILTER"]),
							"MODULE_ID" => false,
							"SOURCE_ID" => $arComment["ID"],
							"LOG_ID" => $log_id,
							"RATING_TYPE_ID" => "FORUM_POST",
							"RATING_ENTITY_ID" => $arComment["ID"],
						);

						if (intVal($arComment["AUTHOR_ID"]) > 0)
						{
							$arFieldsForSocnet["USER_ID"] = $arComment["AUTHOR_ID"];
						}

						$comment_id = CSocNetLogComments::Add($arFieldsForSocnet, false, false);
						if ($comment_id)
						{
							CSocNetLog::CounterIncrement($comment_id, false, false, "LC");
							
							$arFieldsIM = Array(
								"TYPE" => "COMMENT",
								"TITLE" => $log_title,
								"MESSAGE" => $arFieldsForSocnet["MESSAGE"],
								"URL" => $log_url,
								"SECTION_NAME" => $log_section_name,
								"SECTION_URL" => $log_section_url,
								"ID" => $arFields["PARAM2"],
								"PHOTO_AUTHOR_ID" => $log_user_id,
								"COMMENT_AUTHOR_ID" => $arComment["AUTHOR_ID"],
							);
							CSocNetPhotoCommentEvent::NotifyIm($arFieldsIM);
						}
					}

					if ($arElement)
					{
						self::InheriteAlbumFollow($arElement["IBLOCK_SECTION_ID"], $log_id, (intVal($arElement["CREATED_BY"]) > 0 ? $arElement["CREATED_BY"] : false));
					}
				}
			}
		}
	}

	public function OnAfterPhotoCommentAddBlog($ID, $arFields)
	{
		if (!CModule::IncludeModule('iblock'))
			return;

		if (!$this->IsSocnet)
			return;

		if (intval($this->PhotoElementID) > 0)
		{
			$dbRes = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID"	=> "photo_photo",
					"SOURCE_ID"	=> $this->PhotoElementID
				),
				false,
				false,
				array("ID", "ENTITY_TYPE", "ENTITY_ID", "TMP_ID", "TITLE", "URL", "USER_ID", "PARAMS")
			);

			$bSocNetLogRecordExists = false;
			if ($arRes = $dbRes->Fetch())
			{
				$log_id = $arRes["ID"];
				$entity_type = $arRes["ENTITY_TYPE"];
				$entity_id = $arRes["ENTITY_ID"];
				$log_title = $arRes["TITLE"];
				$log_url = $arRes["URL"];
				$log_user_id = $arRes["USER_ID"];
				$bSocNetLogRecordExists = true;

				if (strlen($arRes["PARAMS"]) > 0)
				{
					$arTmp = unserialize($arRes["PARAMS"]);
					if ($arTmp)
					{
						if (
							array_key_exists("SECTION_NAME", $arTmp)
							&& strlen($arTmp["SECTION_NAME"]) > 0
						)
							$log_section_name = $arTmp["SECTION_NAME"];

						if (
							array_key_exists("SECTION_URL", $arTmp)
							&& strlen($arTmp["SECTION_URL"]) > 0
						)
							$log_section_url = $arTmp["SECTION_URL"];
					}
				}
			}
			else
			{
				$rsElement = CIBlockElement::GetByID($this->PhotoElementID);
				if ($arElement = $rsElement->Fetch())
				{
					$url = $this->arPath["DETAIL_URL"];

					$sAuthorName = GetMessage("SONET_PHOTO_LOG_GUEST");
					if (intval($arElement["CREATED_BY"]) > 0)
					{
						$rsUser = CUser::GetByID($arElement["CREATED_BY"]);
						if ($arUser = $rsUser->Fetch())
							$sAuthorName = CUser::FormatName(CSite::GetNameFormat(false), $arUser, true, false);
					}

					if (
						in_array($this->entity_type, array(SONET_ENTITY_USER, SONET_ENTITY_GROUP))
						&& intval($this->entity_id) > 0
					)
					{
						$entity_type = $this->entity_type;
						$entity_id = $this->entity_id;
						$alias = ($this->entity_type == SONET_ENTITY_GROUP ? "group" : "user")."_".$this->entity_id;
					}

					$arLogParams = array(
						"BLOG_ID" => intval($this->BlogID)
					);

					$rsIBlock = CIBlock::GetByID($arElement["IBLOCK_ID"]);
					if($arIBlock = $rsIBlock->Fetch())
					{
						$arLogParams["IBLOCK_ID"] = $arIBlock["ID"];
						$arLogParams["IBLOCK_TYPE"] = $arIBlock["IBLOCK_TYPE_ID"];
					}

					$rsSection = CIBlockSection::GetByID($arElement["IBLOCK_SECTION_ID"]);
					if ($arSection = $rsSection->Fetch())
					{
						$arLogParams["SECTION_ID"] = $arSection["ID"];
						$arLogParams["SECTION_NAME"] = $arSection["NAME"];
						$arLogParams["SECTION_URL"] = str_replace("#SECTION_ID#", $arSection["ID"], $this->arPath["SECTION_URL"]);

						$arSectionPath = array();
						$bPassword = false;

						$dbSectionPath = CIBlockSection::GetList(
							array("LEFT_MARGIN" => "ASC"),
							array(
								"IBLOCK_ID" => intval($arLogParams["IBLOCK_ID"]),
								"<=LEFT_BORDER" => intval($arSection["LEFT_MARGIN"]),
								">=RIGHT_BORDER" => intval($arSection["RIGHT_MARGIN"]),
								"<=DEPTH_LEVEL" => intval($arSection["DEPTH_LEVEL"]),
							),
							false, 
							array("ID", "IBLOCK_ID", "NAME", "CREATED_BY", "DEPTH_LEVEL", "LEFT_MARGIN", "RIGHT_MARGIN", "UF_PASSWORD")
						);

						while ($arPath = $dbSectionPath->Fetch())
						{
							$arSectionPath[] = $arPath;
							if (strlen(trim($arPath["UF_PASSWORD"])) > 0)
							{
								$bPassword = true;
								break;
							}
						}

						if (!$alias)
						{
							$entity_type = SONET_ENTITY_USER;
							$entity_id = $arSectionPath[0]["CREATED_BY"];
							$alias = $arSectionPath[0]["CODE"];
						}
					}

					$arLogParams["ALIAS"] = $alias;

					$arSonetFields = array(
						"ENTITY_TYPE" => $entity_type,
						"ENTITY_ID" => $entity_id,
						"EVENT_ID" => "photo_photo",
						"LOG_DATE" => $arElement["TIMESTAMP_X"],
						"TITLE_TEMPLATE" => str_replace("#AUTHOR_NAME#", $sAuthorName, GetMessage("SONET_PHOTOPHOTO_LOG_1")),
						"TITLE" => $arElement["NAME"],
						"MESSAGE" => "",
						"TEXT_MESSAGE" => "",
						"URL" => CComponentEngine::MakePathFromTemplate($url, array(
							"ELEMENT_ID" => $arElement["ID"],
							"element_id" => $arElement["ID"],
							"SECTION_ID" => $arElement["IBLOCK_SECTION_ID"],
							"section_id" => $arElement["IBLOCK_SECTION_ID"]
						)),
						"MODULE_ID" => false,
						"CALLBACK_FUNC" => false,
						"SOURCE_ID" => $arElement["ID"],
						"PARAMS" => serialize($arLogParams),
						"RATING_TYPE_ID" 	=> "IBLOCK_ELEMENT",
						"RATING_ENTITY_ID"=> $arElement["ID"],
					);

					if (intval($arElement["CREATED_BY"]) > 0)
						$arSonetFields["USER_ID"] = $arElement["CREATED_BY"];

					$log_id = CSocNetLog::Add($arSonetFields, false);
					if (intval($log_id) > 0)
					{
						$log_title = $arSonetFields["TITLE"];
						$log_url = $arSonetFields["URL"];
						$log_section_name = $arLogParams["SECTION_NAME"];
						$log_section_url = $arLogParams["SECTION_URL"];
						$log_user_id = $arSonetFields["USER_ID"];

						CSocNetLog::Update($log_id, array("TMP_ID" => $log_id));
						if ($bPassword)
						{
							CSocNetLogRights::DeleteByLogID($log_id);
							CSocNetLogRights::Add($log_id, array("U".$GLOBALS["USER"]->GetID(), "SA"));
						}
						else
						{
							CSocNetLogRights::SetForSonet($log_id, $entity_type, $entity_id, "photo", "view", true);
						}
					}
				}
			}

			if (intval($log_id) > 0)
			{
				$parserBlog = new blogTextParser(false, $this->arPath["PATH_TO_SMILE"]);
				$arAllow = array("HTML" => "N", "ANCHOR" => "N", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "N", "VIDEO" => "N");
				
				if ($bSocNetLogRecordExists)
				{
					$text4message = $parserBlog->convert($arFields["POST_TEXT"], true, array(), $arAllow);
					$text4mail = $parserBlog->convert4mail($arFields["POST_TEXT"]);

					$arFieldsForSocnet = array(
						"ENTITY_TYPE" => $entity_type,
						"ENTITY_ID" => $entity_id,
						"EVENT_ID" => "photo_comment",
						"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
						"MESSAGE" => $text4message,
						"TEXT_MESSAGE" => $text4mail,
						"MODULE_ID" => false,
						"SOURCE_ID" => $ID,
						"LOG_ID" => $log_id,
						"RATING_TYPE_ID" => "BLOG_COMMENT",
						"RATING_ENTITY_ID" => $ID,
					);

					if (intval($arFields["AUTHOR_ID"]) > 0)
						$arFieldsForSocnet["USER_ID"] = $arFields["AUTHOR_ID"];

					$comment_id = CSocNetLogComments::Add($arFieldsForSocnet, false, false);
					if ($comment_id)
					{
						CSocNetLog::CounterIncrement($comment_id, false, false, "LC");

						$arFieldsIM = Array(
							"TYPE" => "COMMENT",
							"TITLE" => $log_title,
							"MESSAGE" => $arFieldsForSocnet["MESSAGE"],
							"URL" => $log_url,
							"SECTION_NAME" => $log_section_name,
							"SECTION_URL" => $log_section_url,
							"ID" => $this->PhotoElementID,
							"PHOTO_AUTHOR_ID" => $log_user_id,
							"COMMENT_AUTHOR_ID" => $arFields["AUTHOR_ID"],
						);
						CSocNetPhotoCommentEvent::NotifyIm($arFieldsIM);
					}
				}
				else //socnetlog record didn't exist - adding all comments
				{
					$dbComments = CBlogComment::GetList(array(), 
						array(
							"BLOG_ID" => intval($this->BlogID), 
							"POST_ID" => intval($this->PostID)
						), 
						false, 
						false, 
						array("ID", "BLOG_ID", "POST_ID", "AUTHOR_ID", "POST_TEXT", "DATE_CREATE")
						);

					while ($arComment = $dbComments->GetNext())
					{
						$text4message = $parserBlog->convert($arComment["POST_TEXT"], true, array(), $arAllow);
						$text4mail = $parserBlog->convert4mail($arComment["POST_TEXT"]);

						$arFieldsForSocnet = array(
							"ENTITY_TYPE" => $entity_type,
							"ENTITY_ID" => $entity_id,
							"EVENT_ID" => "photo_comment",
							"=LOG_DATE" => $GLOBALS["DB"]->CharToDateFunction($arComment["DATE_CREATE"], "FULL", SITE_ID),
							"MESSAGE" => $text4message,
							"TEXT_MESSAGE" => $text4mail,
							"MODULE_ID" => false,
							"SOURCE_ID" => intval($arComment["ID"]),
							"LOG_ID" => $log_id,
							"RATING_TYPE_ID" => "BLOG_COMMENT",
							"RATING_ENTITY_ID" => intval($arComment["ID"]),
						);

						if (intval($arFields["AUTHOR_ID"]) > 0)
							$arFieldsForSocnet["USER_ID"] = $arFields["AUTHOR_ID"];

						$comment_id = CSocNetLogComments::Add($arFieldsForSocnet, false, false);
						if ($comment_id)
						{
							CSocNetLog::CounterIncrement($comment_id, false, false, "LC");

							$arFieldsIM = Array(
								"TYPE" => "COMMENT",
								"TITLE" => $log_title,
								"MESSAGE" => $arFieldsForSocnet["MESSAGE"],
								"URL" => $log_url,
								"SECTION_NAME" => $log_section_name,
								"SECTION_URL" => $log_section_url,
								"ID" => $this->PhotoElementID,
								"PHOTO_AUTHOR_ID" => $log_user_id,
								"COMMENT_AUTHOR_ID" => $arFields["AUTHOR_ID"],
							);
							CSocNetPhotoCommentEvent::NotifyIm($arFieldsIM);
						}
					}

					if ($arElement)
					{
						self::InheriteAlbumFollow($arElement["IBLOCK_SECTION_ID"], $log_id, (intVal($arElement["CREATED_BY"]) > 0 ? $arElement["CREATED_BY"] : false));
					}
				}
			}
		}
	}
	
	public static function InheriteAlbumFollow($albumId, $logId, $authorId = false)
	{
		$albumId = intval($albumId);
		$logId = intval($logId);

		if (
			!$albumId
			|| !$logId
		)
		{
			return false;
		}

		$dbAlbumLogEntry = CSocNetLog::GetList(
			array("ID" => "DESC"),
			array(
				"EVENT_ID"	=> "photo",
				"SOURCE_ID"	=> $albumId
			),
			false,
			false,
			array("ID")
		);

		if ($arAlbumLogEntry = $dbAlbumLogEntry->Fetch())
		{
			$rsFollower = CSocNetLogFollow::GetList(
				array(
					"CODE" => "L".$arAlbumLogEntry["ID"],
				),
				array("USER_ID", "TYPE")
			);

			while ($arFollower = $rsFollower->Fetch())
			{
				if (
					$authorId
					&& intval($authorId) == $arFollower["USER_ID"]
				)
				{
					continue;
				}

				CSocNetLogFollow::Set($arFollower["USER_ID"], "L".$logId, $arFollower["TYPE"], ConvertTimeStamp(time() + CTimeZone::GetOffset(), "FULL", SITE_ID));							
			}
		}
	}

	public function OnAfterPhotoCommentDeleteBlog($ID)
	{
		if (!$this->IsSocnet)
			return;

		if (intval($ID) > 0)
		{
			$dbRes = CSocNetLogComments::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID"	=> "photo_comment",
					"SOURCE_ID"	=> $ID
				),
				false,
				false,
				array("ID", "LOG_ID")
			);

			if ($arRes = $dbRes->Fetch())
			{
				$res = CSocNetLogComments::Delete($arRes["ID"]);

				if ($res)
				{
					$dbResult = CSocNetLog::GetList(
						array(),
						array("ID" => $arRes["LOG_ID"]),
						false,
						false,
						array("ID", "COMMENTS_COUNT")
					);

					if ($arLog = $dbResult->Fetch())
					{
						if ($arLog["COMMENTS_COUNT"] == 0)
							CSocNetLog::Delete($arRes["LOG_ID"]);
					}
				}
			}
		}
	}

	public static function NotifyIm($arParams)
	{
		if(
			!CModule::IncludeModule("im")
			|| intval($arParams["PHOTO_AUTHOR_ID"]) <= 0
			|| $arParams["PHOTO_AUTHOR_ID"] == intval($arParams["COMMENT_AUTHOR_ID"])
		)
			return;

		if (!array_key_exists("SECTION_NAME", $arParams))
			$arParams["SECTION_NAME"] = "";
		if (!array_key_exists("SECTION_URL", $arParams))
			$arParams["SECTION_URL"] = 0;

		$arMessageFields = array(
			"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
			"TO_USER_ID" => $arParams["PHOTO_AUTHOR_ID"],
			"FROM_USER_ID" => $arParams["COMMENT_AUTHOR_ID"],
			"NOTIFY_TYPE" => IM_NOTIFY_FROM,
			"NOTIFY_MODULE" => "photogallery",
			"NOTIFY_EVENT" => "comment",
		);

		$rsLog = CSocNetLog::GetList(
			array(),
			array(
				"EVENT_ID" => array("photo_photo"),
				"SOURCE_ID" => $arParams["ID"]
			),
			false,
			false,
			array("ID")
		);
		if ($arLog = $rsLog->Fetch())
		{
			$rsUnFollower = CSocNetLogFollow::GetList(
				array(
					"USER_ID" => $arParams["PHOTO_AUTHOR_ID"],
					"CODE" => "L".$arLog["ID"],
					"TYPE" => "N"
				),
				array("USER_ID")
			);
			if ($arUnFollower = $rsUnFollower->Fetch())
				return;

			$arMessageFields["LOG_ID"] = $arLog["ID"];
		}

		$arParams["TITLE"] = str_replace(Array("\r\n", "\n"), " ", $arParams["TITLE"]);
		$arParams["TITLE"] = TruncateText($arParams["TITLE"], 100);
		$arParams["TITLE_OUT"] = TruncateText($arParams["TITLE"], 255);

		$arTmp = CSocNetLogTools::ProcessPath(array("PHOTO_URL" => $arParams["URL"], "SECTION_URL" => $arParams["SECTION_URL"]), $arParams["PHOTO_AUTHOR_ID"]);
		$serverName = $arTmp["SERVER_NAME"];
		$arParams["URL"] = $arTmp["URLS"]["PHOTO_URL"];
		$arParams["SECTION_URL"] = $arTmp["URLS"]["SECTION_URL"];

		$arMessageFields["NOTIFY_TAG"] = "PHOTO|COMMENT|".$arParams["ID"];
		$arMessageFields["NOTIFY_MESSAGE"] = GetMessage("SONET_PHOTO_IM_COMMENT", Array(
			"#photo_title#" => "<a href=\"".$arParams["URL"]."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arParams["TITLE"])."</a>",
			"#album_title#" => "<a href=\"".$arParams["SECTION_URL"]."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arParams["SECTION_NAME"])."</a>"
		));
		$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage("SONET_PHOTO_IM_COMMENT", Array(
			"#photo_title#" => htmlspecialcharsbx($arParams["TITLE_OUT"]),
			"#album_title#" => htmlspecialcharsbx($arParams["SECTION_NAME"])
		))." (".$serverName.$arParams["URL"].")#BR##BR#".$arParams["MESSAGE"];

		$ID = CIMNotify::Add($arMessageFields);

		if(!empty($arParams["COMMENT_MENTION_ID"]))
		{
			//
		}
	}

}

?>