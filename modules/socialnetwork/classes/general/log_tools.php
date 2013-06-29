<?
IncludeModuleLangFile(__FILE__);

class CSocNetLogTools
{
	public static function FindFeatureByEventID($event_id)
	{
		$feature = false;

		foreach ($GLOBALS["arSocNetFeaturesSettings"] as $feature_tmp => $arFeature)
		{
			if (array_key_exists("subscribe_events", $arFeature))
			{
				if (array_key_exists($event_id, $arFeature["subscribe_events"]))
				{
					$feature = $feature_tmp;
					break;
				}

				foreach ($arFeature["subscribe_events"] as $event_id_tmp => $arEventTmp)
				{
					if (
						array_key_exists("COMMENT_EVENT", $arEventTmp)
						&& array_key_exists("EVENT_ID", $arEventTmp["COMMENT_EVENT"])
						&& $arEventTmp["COMMENT_EVENT"]["EVENT_ID"] == $event_id
					)
					{
						$feature = $feature_tmp;
						break;
					}
				}

				if ($feature)
					break;
			}
		}

		return $feature;
	}

	public static function FindLogEventByID($event_id, $entity_type = false)
	{
		$arEvent = false;

		if (
			array_key_exists($event_id, $GLOBALS["arSocNetLogEvents"])
			&& array_key_exists("ENTITIES", $GLOBALS["arSocNetLogEvents"][$event_id])
		)
		{
			if (
				!$entity_type
				|| ($entity_type && array_key_exists($entity_type, $GLOBALS["arSocNetLogEvents"][$event_id]["ENTITIES"]))
			)
				$arEvent = $GLOBALS["arSocNetLogEvents"][$event_id];
		}

		if (!$arEvent)
		{
			foreach($GLOBALS["arSocNetFeaturesSettings"] as $feature => $arFeature)
			{
				if (
					array_key_exists("subscribe_events", $arFeature)
					&& array_key_exists($event_id, $arFeature["subscribe_events"])
					&& array_key_exists("ENTITIES", $arFeature["subscribe_events"][$event_id])

				)
				{
					if (
						!$entity_type
						|| ($entity_type && array_key_exists($entity_type, $arFeature["subscribe_events"][$event_id]["ENTITIES"]))
					)
						$arEvent = $arFeature["subscribe_events"][$event_id];
					break;
				}
			}
		}

		return $arEvent;
	}

	public static function FindLogCommentEventByID($event_id)
	{
		$arEvent = false;

		foreach($GLOBALS["arSocNetLogEvents"] as $event_id_tmp => $arEventTmp)
		{
			if (
				array_key_exists("COMMENT_EVENT", $arEventTmp)
				&& array_key_exists("EVENT_ID", $arEventTmp["COMMENT_EVENT"])
				&& $event_id == $arEventTmp["COMMENT_EVENT"]["EVENT_ID"]
			)
			{
				$arEvent = $arEventTmp["COMMENT_EVENT"];
				break;
			}
		}

		if (!$arEvent)
		{
			foreach($GLOBALS["arSocNetFeaturesSettings"] as $feature => $arFeature)
			{
				if (array_key_exists("subscribe_events", $arFeature))
				{
					foreach( $arFeature["subscribe_events"] as $event_id_tmp => $arEventTmp)
					{
						if (
							array_key_exists("COMMENT_EVENT", $arEventTmp)
							&& array_key_exists("EVENT_ID", $arEventTmp["COMMENT_EVENT"])
							&& $event_id == $arEventTmp["COMMENT_EVENT"]["EVENT_ID"]
						)
						{
							$arEvent = $arEventTmp["COMMENT_EVENT"];
							break;
						}
					}

					if ($arEvent)
						break;
				}
			}
		}

		return $arEvent;
	}

	public static function FindLogCommentEventByLogEventID($log_event_id)
	{
		$arEvent = false;

		if (
			array_key_exists($log_event_id, $GLOBALS["arSocNetLogEvents"])
			&& array_key_exists("COMMENT_EVENT", $GLOBALS["arSocNetLogEvents"][$log_event_id])
		)
			$arEvent = $GLOBALS["arSocNetLogEvents"][$log_event_id]["COMMENT_EVENT"];
		else
		{
			foreach ($GLOBALS["arSocNetFeaturesSettings"] as $feature_id_tmp => $arFeatureTmp)
			{
				if (
					array_key_exists("subscribe_events", $arFeatureTmp)
					&& array_key_exists($log_event_id, $arFeatureTmp["subscribe_events"])
					&& array_key_exists("COMMENT_EVENT", $arFeatureTmp["subscribe_events"][$log_event_id])
				)
				{
					$arEvent = $arFeatureTmp["subscribe_events"][$log_event_id]["COMMENT_EVENT"];
					break;
				}
			}
		}

		return $arEvent;
	}

	public static function FindLogEventByCommentID($event_id)
	{
		$arEvent = false;

		foreach ($GLOBALS["arSocNetLogEvents"] as $event_id_tmp => $arEventTmp)
		{
			if (
				array_key_exists("COMMENT_EVENT", $arEventTmp)
				&& is_array($arEventTmp["COMMENT_EVENT"])
				&& array_key_exists("EVENT_ID", $arEventTmp["COMMENT_EVENT"])
				&& $arEventTmp["COMMENT_EVENT"]["EVENT_ID"] == $event_id
			)
			{
				$arEvent = $arEventTmp;
				$arEvent["EVENT_ID"] = $event_id_tmp;
				break;
			}
		}

		if (!$arEvent)
		{
			foreach ($GLOBALS["arSocNetFeaturesSettings"] as $feature_id_tmp => $arFeatureTmp)
			{
				if (array_key_exists("subscribe_events", $arFeatureTmp))
				{
					foreach ($arFeatureTmp["subscribe_events"] as $event_id_tmp => $arEventTmp)
					{
						if (
							array_key_exists("COMMENT_EVENT", $arEventTmp)
							&& is_array($arEventTmp["COMMENT_EVENT"])
							&& array_key_exists("EVENT_ID", $arEventTmp["COMMENT_EVENT"])
							&& $arEventTmp["COMMENT_EVENT"]["EVENT_ID"] == $event_id
						)
						{
							$arEvent = $arEventTmp;
							$arEvent["EVENT_ID"] = $event_id_tmp;
							break;
						}
					}

					if ($arEvent)
						break;
				}
			}
		}

		return $arEvent;
	}

	public static function FindFullSetByEventID($event_id)
	{
		$bFound = false;
		foreach ($GLOBALS["arSocNetLogEvents"] as $event_id_tmp => $arEventTmp)
		{
			if (
				array_key_exists("FULL_SET", $arEventTmp)
				&& in_array($event_id, $arEventTmp["FULL_SET"])
			)
			{
				$arFullSet = $arEventTmp["FULL_SET"];
				$bFound = true;
				break;
			}
		}

		if (!$bFound)
		{
			foreach($GLOBALS["arSocNetFeaturesSettings"] as $arFeatureTmp)
			{
				if (array_key_exists("subscribe_events", $arFeatureTmp))
				{
					foreach($arFeatureTmp["subscribe_events"] as $event_id_tmp => $arEventTmp)
					{
						if (
							array_key_exists("FULL_SET", $arEventTmp)
							&& in_array($event_id, $arEventTmp["FULL_SET"])
						)
						{
							$arFullSet = $arEventTmp["FULL_SET"];
							$bFound = true;
							break;
						}
					}
					if ($bFound)
						break;
				}
			}
		}

		if (!$bFound)
			$arFullSet = array($event_id);

		return $arFullSet;
	}

	public static function FindFullSetEventIDByEventID($event_id)
	{
		$event_id_fullset = false;

		foreach ($GLOBALS["arSocNetLogEvents"] as $event_id_tmp => $arEventTmp)
		{
			if (
				array_key_exists("FULL_SET", $arEventTmp)
				&& is_array($arEventTmp["FULL_SET"])
				&& in_array($event_id, $arEventTmp["FULL_SET"])
			)
			{
				$event_id_fullset = $event_id_tmp;
				break;
			}
		}

		if (!$event_id_fullset)
		{
			foreach ($GLOBALS["arSocNetFeaturesSettings"] as $feature_id_tmp => $arFeatureTmp)
			{
				if (array_key_exists("subscribe_events", $arFeatureTmp))
				{
					foreach ($arFeatureTmp["subscribe_events"] as $event_id_tmp => $arEventTmp)
					{
						if (
							array_key_exists("FULL_SET", $arEventTmp)
							&& is_array($arEventTmp["FULL_SET"])
							&& in_array($event_id, $arEventTmp["FULL_SET"])
						)
						{
							$event_id_fullset = $event_id_tmp;
							break;
						}
					}
				}
				if ($event_id_fullset)
					break;
			}
		}

		return $event_id_fullset;
	}

	public static function ShowGroup($arEntityDesc, $strEntityURL, $arParams)
	{
		if (strlen($strEntityURL) > 0)
			$name = "<a href=\"".$strEntityURL."\">".$arEntityDesc["NAME"]."</a>";
		else
			$name = $arEntityDesc["NAME"];

		return $name;
	}

	public static function ShowUser($arEntityDesc, $strEntityURL, $arParams)
	{
		$name = $GLOBALS["APPLICATION"]->IncludeComponent("bitrix:main.user.link",
			'',
			array(
				"ID" => $arEntityDesc["ID"],
				"HTML_ID" => "subscribe_list_".$arEntityDesc["ID"],
				"NAME" => $arEntityDesc["~NAME"],
				"LAST_NAME" => $arEntityDesc["~LAST_NAME"],
				"SECOND_NAME" => $arEntityDesc["~SECOND_NAME"],
				"LOGIN" => $arEntityDesc["~LOGIN"],
				"USE_THUMBNAIL_LIST" => "N",
				"PROFILE_URL" => $strEntityURL,
				"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["~PATH_TO_MESSAGES_CHAT"],
				"PATH_TO_SONET_USER_PROFILE" => $arParams["~PATH_TO_USER"],
				"PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
				"SHOW_FIELDS" => $arParams["SHOW_FIELDS_TOOLTIP"],
				"USER_PROPERTY" => $arParams["USER_PROPERTY_TOOLTIP"],
				"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
				"SHOW_YEAR" => $arParams["SHOW_YEAR"],
				"CACHE_TYPE" => $arParams["CACHE_TYPE"],
				"CACHE_TIME" => $arParams["CACHE_TIME"],
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
				"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
				"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
				"DO_RETURN"	=> "Y",
				"INLINE" => "Y",
			),
			false,
			array("HIDE_ICONS" => "Y")
		);

		return $name;
	}

	public static function HasLogEventCreatedBy($event_id)
	{
		$bFound	= false;

		$arEvent = CSocNetLogTools::FindLogEventByID($event_id);
		if ($arEvent)
		{
			if (
				array_key_exists("HAS_CB", $arEvent)
				&& $arEvent["HAS_CB"] == "Y"
			)
				$bFound	= true;
		}
		else
		{
			$arEvent = CSocNetLogTools::FindLogCommentEventByID($event_id);
			if ($arEvent)
				$bFound	= true;
		}

		return $bFound;
	}

	public static function FormatEvent_FillTooltip($arFields, $arParams)
	{
		return array(
			"ID" => $arFields["ID"],
			"NAME" => $arFields["NAME"],
			"LAST_NAME" => $arFields["LAST_NAME"],
			"SECOND_NAME" => $arFields["SECOND_NAME"],
			"LOGIN" => $arFields["LOGIN"],
			"USE_THUMBNAIL_LIST" => "N",
			"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["PATH_TO_MESSAGES_CHAT"],
			"PATH_TO_SONET_USER_PROFILE" => $arParams["PATH_TO_USER"],
			"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
			"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
			"SHOW_YEAR" => $arParams["SHOW_YEAR"],
			"CACHE_TYPE" => $arParams["CACHE_TYPE"],
			"CACHE_TIME" => $arParams["CACHE_TIME"],
			"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
			"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
			"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
			"INLINE" => "Y"
		);
	}

	public static function FormatEvent_CreateAvatar($arFields, $arParams, $source = "CREATED_BY_", $site_id = SITE_ID)
	{
		if (intval($arParams["AVATAR_SIZE"]) <= 0)
			$arParams["AVATAR_SIZE"] = 30;

		if (strlen($source) > 0 && substr($source, -1) != "_")
			$source .= "_";

		$AvatarPath = false;

		if (intval($arFields[$source."PERSONAL_PHOTO"]) <= 0)
		{
			switch ($arFields[$source."PERSONAL_GENDER"])
			{
				case "M":
					$suffix = "male";
					break;
				case "F":
					$suffix = "female";
					break;
				default:
					$suffix = "unknown";
			}
			$arFields[$source."PERSONAL_PHOTO"] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, $site_id);
		}

		if (intval($arFields[$source."PERSONAL_PHOTO"]) > 0)
		{
			static $cachedAvatars = array();
			if (empty($cachedAvatars[$arParams["AVATAR_SIZE"]][$arFields[$source."PERSONAL_PHOTO"]]))
			{
				$imageFile = CFile::GetFileArray($arFields[$source."PERSONAL_PHOTO"]);
				if ($imageFile !== false)
				{
					$arFileTmp = CFile::ResizeImageGet(
						$imageFile,
						array("width" => $arParams["AVATAR_SIZE"], "height" => $arParams["AVATAR_SIZE"]),
						BX_RESIZE_IMAGE_EXACT,
						false
					);
					$AvatarPath = $arFileTmp["src"];
					$cachedAvatars[$arParams["AVATAR_SIZE"]][$arFields[$source."PERSONAL_PHOTO"]] = $AvatarPath;
				}
			}
			else
				$AvatarPath = $cachedAvatars[$arParams["AVATAR_SIZE"]][$arFields[$source."PERSONAL_PHOTO"]];
		}

		return $AvatarPath;
	}

	public static function FormatEvent_CreateAvatarGroup($arFields, $arParams)
	{
		if (intval($arParams["AVATAR_SIZE"]) <= 0)
			$arParams["AVATAR_SIZE"] = 30;

		$AvatarPath = false;

		if (intval($arFields["IMAGE_ID"]) <= 0)
			$arFields["IMAGE_ID"] = COption::GetOptionInt("socialnetwork", "default_group_picture", false, SITE_ID);

		if (intval($arFields["IMAGE_ID"]) > 0)
		{
			$imageFile = CFile::GetFileArray($arFields["IMAGE_ID"]);
			if ($imageFile !== false)
			{
				$arFileTmp = CFile::ResizeImageGet(
					$imageFile,
					array("width" => $arParams["AVATAR_SIZE"], "height" => $arParams["AVATAR_SIZE"]),
					BX_RESIZE_IMAGE_EXACT,
					false
				);
				$AvatarPath = $arFileTmp["src"];
			}
		}

		return $AvatarPath;
	}


	public static function FormatEvent_IsMessageShort($message, $short_message = false)
	{
		if (!$short_message)
			return (
				strlen(HTMLToTxt($message)) < 1000
				&& (strlen($message) - strlen(HTMLToTxt(htmlspecialcharsback($message)))) <= 0
				? true
				: false
			);
		else
			return (
				strlen($short_message) < 1000
				&& (strlen(htmlspecialcharsback($message)) - strlen($short_message)) <= 0
				? true
				: false
			);
	}

	public static function FormatEvent_GetCreatedBy($arFields, $arParams, $bMail, $bFirstCaps = false)
	{
		if (intval($arFields["USER_ID"]) > 0)
		{
			$suffix = (is_array($GLOBALS["arExtranetUserID"]) && in_array($arFields["USER_ID"], $GLOBALS["arExtranetUserID"]) ? GetMessage("SONET_LOG_EXTRANET_SUFFIX") : "");

			if ($bMail)
			{
				if (
					strlen($arFields["CREATED_BY_NAME"]) > 0
					|| strlen($arFields["CREATED_BY_LAST_NAME"]) > 0
				)
					$arCreatedBy["FORMATTED"] = GetMessage("SONET_GL_EVENT_USER".($bFirstCaps ? "_CAPS" : ""))." ".$arFields["CREATED_BY_NAME"]." ".$arFields["CREATED_BY_LAST_NAME"].$suffix;
				else
					$arCreatedBy["FORMATTED"] = GetMessage("SONET_GL_EVENT_USER".($bFirstCaps ? "_CAPS" : ""))." ".$arFields["CREATED_BY_LOGIN"].$suffix;
			}
			else
			{
				$arFieldsTooltip = array(
					"ID" => $arFields["USER_ID"],
					"NAME" => $arFields["~CREATED_BY_NAME"],
					"LAST_NAME" => $arFields["~CREATED_BY_LAST_NAME"],
					"SECOND_NAME" => $arFields["~CREATED_BY_SECOND_NAME"],
					"LOGIN" => $arFields["~CREATED_BY_LOGIN"]
				);
				$arParams["NAME_TEMPLATE"] .= $suffix;
				$arCreatedBy["TOOLTIP_FIELDS"] = CSocNetLogTools::FormatEvent_FillTooltip($arFieldsTooltip, $arParams);
			}
		}
		else
			$arCreatedBy["FORMATTED"] = GetMessage("SONET_GL_EVENT_ANONYMOUS_USER".($bFirstCaps ? "_CAPS" : ""));

		return $arCreatedBy;
	}

	public static function FormatEvent_GetEntity($arFields, $arParams, $bMail)
	{
		if (
			$arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_USER
			&& intval($arFields["ENTITY_ID"]) > 0
		)
		{
			$suffix = (is_array($GLOBALS["arExtranetUserID"]) && in_array($arFields["ENTITY_ID"], $GLOBALS["arExtranetUserID"]) ? GetMessage("SONET_LOG_EXTRANET_SUFFIX") : "");

			if ($bMail)
			{
				if (
					strlen($arFields["USER_NAME"]) > 0
					|| strlen($arFields["USER_LAST_NAME"]) > 0
				)
					$arEntity["FORMATTED"] = $arFields["USER_NAME"]." ".$arFields["USER_LAST_NAME"].$suffix;
				else
					$arEntity["FORMATTED"] = $arFields["USER_LOGIN"].$suffix;
				$arEntity["TYPE_MAIL"] = GetMessage("SONET_GL_EVENT_ENTITY_U");
			}
			else
			{
				$arFieldsTooltip = array(
					"ID" => $arFields["ENTITY_ID"],
					"NAME" => $arFields["~USER_NAME"],
					"LAST_NAME" => $arFields["~USER_LAST_NAME"],
					"SECOND_NAME" => $arFields["~USER_SECOND_NAME"],
					"LOGIN" => $arFields["~USER_LOGIN"],
				);
				$arParams["NAME_TEMPLATE"] .= $suffix;
				$arEntity["TOOLTIP_FIELDS"] = CSocNetLogTools::FormatEvent_FillTooltip($arFieldsTooltip, $arParams);
				$arEntity["FORMATTED"] = "";
			}
		}
		elseif (
			$arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP
			&& intval($arFields["ENTITY_ID"]) > 0
		)
		{
			$suffix = (is_array($GLOBALS["arExtranetGroupID"]) && in_array($arFields["ENTITY_ID"], $GLOBALS["arExtranetGroupID"]) ? GetMessage("SONET_LOG_EXTRANET_SUFFIX") : "");

			if ($bMail)
			{
				$arEntity["FORMATTED"] = $arFields["GROUP_NAME"].$suffix;
				$arEntity["TYPE_MAIL"] = GetMessage("SONET_GL_EVENT_ENTITY_G");
			}
			else
			{
				$url = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $arFields["ENTITY_ID"]));
				$arEntity["FORMATTED"]["TYPE_NAME"] = $GLOBALS["arSocNetAllowedSubscribeEntityTypesDesc"][$arFields["ENTITY_TYPE"]]["TITLE_ENTITY"];
				$arEntity["FORMATTED"]["URL"] = $url;
				$arEntity["FORMATTED"]["NAME"] = $arFields["GROUP_NAME"].$suffix;
			}
		}

		return $arEntity;
	}

	public static function FormatEvent_GetURL($arFields, $bAbsolute = false)
	{
		$url = false;
		static $arSiteServerName;

		if (!$arSiteServerName)
			$arSiteServerName = array();

		if (strlen($arFields["URL"]) > 0)
		{
			if (
				!$bAbsolute
				&& (
					strpos($arFields["URL"], "http://") === 0
					|| strpos($arFields["URL"], "https://") === 0
				)
			)
				$bAbsolute = true;

			if (!$bAbsolute)
			{
				if ($arFields["ENTITY_TYPE"] == SONET_ENTITY_GROUP && CModule::IncludeModule("extranet"))
					$server_name = "#SERVER_NAME#";
				else
				{
					$rsLogSite = CSocNetLog::GetSite($arFields["ID"]);
					if($arLogSite = $rsLogSite->Fetch())
						$siteID = $arLogSite["LID"];

					if (in_array($siteID, $arSiteServerName))
						$server_name = $arSiteServerName[$siteID];
					else
					{
						$rsSites = CSite::GetByID($siteID);
						$arSite = $rsSites->Fetch();

						if (strlen($arSite["SERVER_NAME"]) > 0)
							$server_name = $arSite["SERVER_NAME"];
						else
							$server_name = COption::GetOptionString("main", "server_name", $GLOBALS["SERVER_NAME"]);

						$arSiteServerName[$siteID] = $server_name;
					}
				}

				$protocol = (CMain::IsHTTPS() ? "https" : "http");
				$url = $protocol."://".$server_name.$arFields["URL"];
			}
			else
				$url = $arFields["URL"];
		}

		return $url;
	}

	public static function FormatEvent_Blog($arFields, $arParams, $bMail = false)
	{
		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);

		$arResult = array(
			"EVENT" => $arFields,
			"CREATED_BY" => CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail),
			"ENTITY" => CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, $bMail),
			"EVENT_FORMATTED"	=> array()
		);
		$arResult["CREATED_BY"]["ACTION_TYPE"] = "wrote";

		if (!$bMail)
			$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams);

		if ($bMail)
		{
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_BLOG_POST_MAIL");

			//if the title duplicates message, don't show it
			if (strpos($arFields["MESSAGE"], $arFields["TITLE"]) === 0)
				$arFields["TITLE"] = "";
			else
				$arFields["TITLE"] = ' "'.$arFields["TITLE"].'"';
		}
		else
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_BLOG_POST");

		if (
			!$bMail
			&& array_key_exists("URL", $arFields)
			&& strlen($arFields["URL"]) > 0
		)
			$post_tmp = '<a href="'.$arFields["URL"].'">'.$arFields["TITLE"].'</a>';
		else
			$post_tmp = $arFields["TITLE"];

		$title = str_replace(
			array("#TITLE#", "#ENTITY#", "#CREATED_BY#"),
			array($post_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
			$title_tmp
		);

		$title = trim(preg_replace('/\s+/', ' ', $title));

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => $title,
			"TITLE_24" => GetMessage("SONET_GL_EVENT_TITLE_BLOG_POST_24"),
			"TITLE_24_2" => $arFields["TITLE"],
			"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : "")
		);

		if (!$bMail)
		{
			if ($arParams["NEW_TEMPLATE"] != "Y")
			{
				$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
				$arAllow = array(
					"HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "LOG_IMG" => "N",
					"QUOTE" => "Y", "LOG_QUOTE" => "N",
					"CODE" => "Y", "LOG_CODE" => "N",
					"FONT" => "Y", "LOG_FONT" => "N",
					"LIST" => "Y",
					"SMILES" => "Y",
					"NL2BR" => "N",
					"MULTIPLE_BR" => "N",
					"VIDEO" => "Y", "LOG_VIDEO" => "N", "SHORT_ANCHOR" => "Y"
				);

				$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
					$parserLog->convert(htmlspecialcharsback(str_replace("#CUT#",	"", $arResult["EVENT_FORMATTED"]["MESSAGE"])), array(), $arAllow),
					1000
				);

				$arAllow = array("HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "MULTIPLE_BR" => "N", "VIDEO" => "Y", "LOG_VIDEO" => "N", "SHORT_ANCHOR" => "Y");
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));

				$arResult["EVENT_FORMATTED"]["MESSAGE"] = str_replace(
					"#CUT#",
					'<br><a href="'.$arFields["URL"].'">'.GetMessage("SONET_GL_EVENT_BLOG_MORE").'</a>',
					$arResult["EVENT_FORMATTED"]["MESSAGE"]
				);

				$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);

				if ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP)
					$arResult["EVENT_FORMATTED"]["DESTINATION"] = array(
						array(
							"STYLE" => "sonetgroups",
							"TITLE" => $arResult["ENTITY"]["FORMATTED"]["NAME"],
							"URL" => $arResult["ENTITY"]["FORMATTED"]["URL"],
						)
				);
			}

			$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $arFields["ID"]));
			while ($arRight = $dbRight->Fetch())
				$arRights[] = $arRight["GROUP_CODE"];

			if ($arParams["MOBILE"] == "Y")
			{					
				$arResult["EVENT_FORMATTED"]["DESTINATION"] = CSocNetLogTools::FormatDestinationFromRights($arRights, array_merge($arParams, array("CREATED_BY" => $arFields["USER_ID"], "USE_ALL_DESTINATION" => true)), $iMoreCount);
				if (intval($iMoreCount) > 0)
					$arResult["EVENT_FORMATTED"]["DESTINATION_MORE"] = $iMoreCount;
			}
			else
				$arResult["EVENT_FORMATTED"]["DESTINATION_CODE"] = CSocNetLogTools::GetDestinationFromRights($arRights, array_merge($arParams, array("CREATED_BY" => $arFields["USER_ID"])));
		}
		else
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arFields);
			if (strlen($url) > 0)
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}

		$arResult["HAS_COMMENTS"] = (intval($arFields["SOURCE_ID"]) > 0 ? "Y" : "N");

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);

		return $arResult;
	}

	public static function FormatComment_Blog($arFields, $arParams, $bMail = false, $arLog = array())
	{
		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);

		$arResult = array(
			"EVENT_FORMATTED"	=> array(),
		);

		if ($bMail)
		{
			$arResult["CREATED_BY"] = CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail);
			$arResult["CREATED_BY"]["ACTION_TYPE"] = "wrote";
			$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arLog, $arParams, $bMail);
		}
		elseif($arParams["USE_COMMENT"] != "Y")
			$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, false);

		if ($bMail)
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_BLOG_COMMENT_MAIL");
		else
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_BLOG_COMMENT");

		if (
			!$bMail
			&& array_key_exists("URL", $arLog)
			&& strlen($arLog["URL"]) > 0
		)
			$post_tmp = '<a href="'.$arLog["URL"].'">'.$arLog["TITLE"].'</a>';
		else
			$post_tmp = $arLog["TITLE"];

		$title = str_replace(
			array("#TITLE#", "#ENTITY#", "#CREATED_BY#"),
			array($post_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
			$title_tmp
		);

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => $title,
			"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : $arFields["MESSAGE"])
		);

		if ($bMail)
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arLog);
			if (strlen($url) > 0)
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}
		elseif ($arParams["NEW_TEMPLATE"] != "Y")
		{
			$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
			$arAllow = array(
				"HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y",
				"IMG" => "Y", "LOG_IMG" => "N",
				"QUOTE" => "Y", "LOG_QUOTE" => "N",
				"CODE" => "Y", "LOG_CODE" => "N",
				"FONT" => "Y", "LOG_FONT" => "N",
				"LIST" => "Y",
				"SMILES" => "Y",
				"NL2BR" => "Y",
				"MULTIPLE_BR" => "N",
				"VIDEO" => "Y", "LOG_VIDEO" => "N"
			);

			$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
				$parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow),
				500
			);

			$arAllow = array("HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "LOG_VIDEO" => "N");
			$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));

			$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);
		}

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);

		return $arResult;
	}

	public static function FormatEvent_Microblog($arFields, $arParams, $bMail = false)
	{
		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);

		$arResult = array(
			"EVENT" => $arFields,
			"CREATED_BY" => CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail),
			"ENTITY" => CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, $bMail),
			"EVENT_FORMATTED" => array(),
		);
		$arResult["CREATED_BY"]["ACTION_TYPE"] = "wrote";

		if (!$bMail)
			$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams);

		if ($bMail)
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_BLOG_POST_MICRO_MAIL");
		else
		{
			if(strlen($arFields["URL"]) > 0)
				$title_tmp = "<a href=\"".$arFields["URL"]."\">".GetMessage("SONET_GL_EVENT_TITLE_BLOG_POST_MICRO")."</a>";
			else
				$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_BLOG_POST_MICRO");
		}

		if (
			!$bMail
			&& array_key_exists("URL", $arFields)
			&& strlen($arFields["URL"]) > 0
		)
			$post_tmp = '<a href="'.$arFields["URL"].'">'.$arFields["TITLE"].'</a>';
		else
			$post_tmp = $arFields["TITLE"];

		$title = str_replace(
			array("#TITLE#", "#ENTITY#", "#CREATED_BY#"),
			array($post_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
			$title_tmp
		);

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => $title,
			"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : $arFields["MESSAGE"])
		);

		if (!$bMail)
		{
			if ($arParams["NEW_TEMPLATE"] != "Y")
			{
				$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
				$arAllow = array(
					"HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "LOG_IMG" => "N",
					"QUOTE" => "Y", "LOG_QUOTE" => "N",
					"CODE" => "Y", "LOG_CODE" => "N",
					"FONT" => "Y", "LOG_FONT" => "N",
					"LIST" => "Y",
					"SMILES" => "Y",
					"NL2BR" => "N",
					"MULTIPLE_BR" => "N",
					"VIDEO" => "Y", "LOG_VIDEO" => "N"
				);

				$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
					$parserLog->convert(htmlspecialcharsback(str_replace("#CUT#",	"", $arResult["EVENT_FORMATTED"]["MESSAGE"])), array(), $arAllow),
					1000
				);

				$arAllow = array("HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "MULTIPLE_BR" => "N", "VIDEO" => "Y", "LOG_VIDEO" => "N");
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));

				$arResult["EVENT_FORMATTED"]["MESSAGE"] = str_replace(
					"#CUT#",
					'<br><a href="'.$arFields["URL"].'">'.GetMessage("SONET_GL_EVENT_BLOG_MORE").'</a>',
					$arResult["EVENT_FORMATTED"]["MESSAGE"]
				);

				$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);
			}
		}
		else
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arFields);
			if (strlen($url) > 0)
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}

		$arResult["HAS_COMMENTS"] = (intval($arFields["SOURCE_ID"]) > 0 ? "Y" : "N");

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);

		return $arResult;
	}

	public static function FormatComment_Microblog($arFields, $arParams, $bMail = false, $arLog = array())
	{
		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);

		$arResult = array(
				"EVENT_FORMATTED"	=> array(),
			);

		if ($bMail)
		{
			$arResult["CREATED_BY"] = CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail, true);
			$arResult["CREATED_BY"]["ACTION_TYPE"] = "wrote";
			$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arLog, $arParams, $bMail);
		}
		elseif($arParams["USE_COMMENT"] != "Y")
			$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, false);

		if ($bMail)
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_BLOG_COMMENT_MICRO_MAIL");
		else
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_BLOG_COMMENT_MICRO");

		if (
			!$bMail
			&& array_key_exists("URL", $arLog)
			&& strlen($arLog["URL"]) > 0
		)
			$post_tmp = '<a href="'.$arLog["URL"].'">'.$arLog["TITLE"].'</a>';
		else
			$post_tmp = $arLog["TITLE"];

		$title = str_replace(
			array("#TITLE#", "#ENTITY#", "#CREATED_BY#"),
			array($post_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
			$title_tmp
		);

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => $title,
			"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : $arFields["MESSAGE"])
		);

		if ($bMail)
		{
			$url = CSocNetLogTools::FormatEvent_GetURL((strlen($arFields["URL"]) > 0 ? $arFields : $arLog));
			if (strlen($url) > 0)
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}
		elseif ($arParams["NEW_TEMPLATE"] != "Y")
		{
			$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
			$arAllow = array(
				"HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y",
				"IMG" => "Y", "LOG_IMG" => "N",
				"QUOTE" => "Y", "LOG_QUOTE" => "N",
				"CODE" => "Y", "LOG_CODE" => "N",
				"FONT" => "Y", "LOG_FONT" => "N",
				"LIST" => "Y",
				"SMILES" => "Y",
				"NL2BR" => "Y",
				"MULTIPLE_BR" => "N",
				"VIDEO" => "Y", "LOG_VIDEO" => "N"
			);

			$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
				$parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow),
				500
			);

			$arAllow = array("HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "LOG_VIDEO" => "N");
			$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));

			$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);
		}

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);

		return $arResult;
	}

	public static function FormatEvent_Forum($arFields, $arParams, $bMail = false)
	{
		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);

		$arResult = array(
			"EVENT" => $arFields,
			"CREATED_BY" => CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail),
			"ENTITY" => CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, $bMail),
			"EVENT_FORMATTED" => array(),
		);

		if (!$bMail)
			$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams);

		if ($arFields["PARAMS"] == "type=M")
		{
			if ($bMail)
				$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_FORUM_MESSAGE_MAIL");
			else
				$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_FORUM_MESSAGE");
		}
		else
		{
			if ($bMail)
				$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_FORUM_TOPIC_MAIL");
			else
				$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_FORUM_TOPIC");
		}

		if (
			!$bMail
			&& array_key_exists("URL", $arFields)
			&& strlen($arFields["URL"]) > 0
		)
			$topic_tmp = '<a href="'.$arFields["URL"].'">'.$arFields["TITLE"].'</a>';
		else
			$topic_tmp = $arFields["TITLE"];

		$title = str_replace(
			array("#TITLE#", "#ENTITY#", "#CREATED_BY#"),
			array($topic_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
			$title_tmp
		);

		$parser = false;
		if (CModule::IncludeModule("forum")){
			$parser = new forumTextParser(LANGUAGE_ID);
			$arFields["FILES"] = CForumFiles::getByMessageID($arFields["SOURCE_ID"]);
		}
		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => $title,
			"TITLE_24" => GetMessage("SONET_GL_EVENT_TITLE_FORUM_TOPIC_24"),
			"TITLE_24_2" => $arFields["TITLE"],
			"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : $arFields["~MESSAGE"]),
			"FILES" => (!!$arFields["FILES"] ? array_keys($arFields["FILES"]) : array())
		);

		if (!$bMail)
		{
			$parserLog = false;
			if ($arParams["MOBILE"] != "Y") {
				$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
				$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
					$parserLog->convert(
						str_replace("#CUT#", "", $arResult["EVENT_FORMATTED"]["MESSAGE"]),
						array(),
						array(
							"HTML" => "Y",
							"ANCHOR" => "Y", "BIU" => "Y",
							"IMG" => "Y", "LOG_IMG" => "N",
							"QUOTE" => "Y", "LOG_QUOTE" => "N",
							"CODE" => "Y", "LOG_CODE" => "N",
							"FONT" => "Y", "LOG_FONT" => "N",
							"LIST" => "Y", "SMILES" => "Y",
							"NL2BR" => "N", "MULTIPLE_BR" => "N",
							"VIDEO" => "Y", "LOG_VIDEO" => "N"
						)),
					1000
				);
			}

			$parser = (is_object($parser) ? $parser : (is_object($parserLog) ? $parserLog : new logTextParser(false, $arParams["PATH_TO_SMILE"])));
			$arResult["EVENT_FORMATTED"]["MESSAGE"] = $parser->convert(
				$arResult["EVENT_FORMATTED"]["MESSAGE"],
				array(
					"HTML" => "N",
					"ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "QUOTE" => "Y",
					"CODE" => "Y", "FONT" => "Y",
					"LIST" => "Y", "SMILES" => "Y",
					"NL2BR" => "Y", "MULTIPLE_BR" => "N",
					"VIDEO" => "Y", "LOG_VIDEO" => "N",
					"SHORT_ANCHOR" => "Y"),
				"html",
				$arResult["EVENT_FORMATTED"]["FILES"]);
			$arResult["EVENT_FORMATTED"]["PARSED_FILES"] = $parser->arFilesParsed;
			$arResult["EVENT_FORMATTED"]["MESSAGE"] = str_replace(
				"#CUT#",
				'<br><a href="'.$arFields["URL"].'">'.GetMessage("SONET_GL_EVENT_BLOG_MORE").'</a>',
				htmlspecialcharsbx($arResult["EVENT_FORMATTED"]["MESSAGE"])
			);

			if ($arParams["MOBILE"] != "Y" && $arParams["NEW_TEMPLATE"] != "Y")
				$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort(
					$arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);

			if ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP)
				$arResult["EVENT_FORMATTED"]["DESTINATION"] = array(
					array(
						"STYLE" => "sonetgroups",
						"TITLE" => $arResult["ENTITY"]["FORMATTED"]["NAME"],
						"URL" => $arResult["ENTITY"]["FORMATTED"]["URL"],
					)
				);
		}
		else
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arFields);
			if (strlen($url) > 0)
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}

		$arResult["HAS_COMMENTS"] = (intval($arFields["SOURCE_ID"]) > 0 ? "Y" : "N");

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);

		return $arResult;
	}

	public static function FormatComment_Forum($arFields, $arParams, $bMail = false, $arLog = array())
	{
		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);

		$arResult = array(
				"EVENT_FORMATTED"	=> array(),
			);

		if ($bMail)
		{
			$arResult["CREATED_BY"] = CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail);
			$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arLog, $arParams, $bMail);
		}
		elseif($arParams["USE_COMMENT"] != "Y")
			$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, false);

		if ($bMail)
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_FORUM_MESSAGE_MAIL");
		else
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_FORUM_MESSAGE");

		if (
			!$bMail
			&& array_key_exists("URL", $arLog)
			&& strlen($arLog["URL"]) > 0
		)
			$topic_tmp = '<a href="'.$arLog["URL"].'">'.$arLog["TITLE"].'</a>';
		else
			$topic_tmp = $arLog["TITLE"];

		$title = str_replace(
			array("#TITLE#", "#ENTITY#", "#CREATED_BY#"),
			array($topic_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
			$title_tmp
		);

		$parser = false;
		if (CModule::IncludeModule("forum")) {
			$parser = new forumTextParser(LANGUAGE_ID);
			$arFields["FILES"] = CForumFiles::GetByMessageID($arFields["SOURCE_ID"]);
		}

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => $title,
			"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : htmlspecialcharsBack($arFields["MESSAGE"])),
			"FILES" => (!!$arFields["FILES"] ? array_keys($arFields["FILES"]) : array())
		);

		if (!$bMail)
		{
			if ($arParams["MOBILE"] != "Y") {
				$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
				$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
					$parserLog->convert(
						$arResult["EVENT_FORMATTED"]["MESSAGE"],
						array(),
						array(
							"HTML" => "Y",
							"ANCHOR" => "Y", "BIU" => "Y",
							"IMG" => "Y", "LOG_IMG" => "N",
							"QUOTE" => "Y", "LOG_QUOTE" => "N",
							"CODE" => "Y", "LOG_CODE" => "N",
							"FONT" => "Y", "LOG_FONT" => "N",
							"LIST" => "Y", "SMILES" => "Y",
							"NL2BR" => "Y", "MULTIPLE_BR" => "N",
							"VIDEO" => "Y", "LOG_VIDEO" => "N"
						)
					),
					500
				);
			}
			$parser = (is_object($parser) ? $parser : (is_object($parserLog) ? $parserLog : new logTextParser(false, $arParams["PATH_TO_SMILE"])));
			$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parser->convert(
				$arResult["EVENT_FORMATTED"]["MESSAGE"],
				array(
					"HTML" => "N",
					"ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "QUOTE" => "Y",
					"CODE" => "Y", "FONT" => "Y",
					"LIST" => "Y", "SMILES" => "Y",
					"NL2BR" => "Y", "VIDEO" => "Y",
					"LOG_VIDEO" => "N", "SHORT_ANCHOR" => "Y"),
				"html",
				$arResult["EVENT_FORMATTED"]["FILES"]
			));
			$arResult["EVENT_FORMATTED"]["PARSED_FILES"] = $parser->arFilesIDParsed;

			if ($arParams["MOBILE"] != "Y" && $arParams["NEW_TEMPLATE"] != "Y") {
				$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort(
					$arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);
			}
		}
		else
		{
			if (strlen($arFields["URL"]) > 0)
				$url = $arFields["URL"];
			elseif (
				strlen($arLog["PARAMS"]) > 0
				&& unserialize($arLog["PARAMS"])
			)
			{
				$arTmp = unserialize($arLog["PARAMS"]);
				if (
					array_key_exists("PATH_TO_MESSAGE", $arTmp)
					&& strlen($arTmp["PATH_TO_MESSAGE"]) > 0
				)
					$url = CComponentEngine::MakePathFromTemplate($arTmp["PATH_TO_MESSAGE"], array("MID" => $arFields["SOURCE_ID"]));
			}

			if (strlen($url) > 0)
				$url = CSocNetLogTools::FormatEvent_GetURL(array("ID" => $arLog["ID"], "URL" => $url));
			else
				$url = CSocNetLogTools::FormatEvent_GetURL($arLog);

			if (strlen($url) > 0)
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);

		return $arResult;
	}

	public static function FormatEvent_Photo($arFields, $arParams, $bMail = false)
	{
		static $arAlbumName = array();

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);

		$arResult = array(
			"EVENT" => $arFields,
			"CREATED_BY" => CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail),
			"ENTITY" => CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, $bMail),
			"EVENT_FORMATTED" => array(),
		);

		if (!$bMail)
			$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams);

		$count = false;
		if (strlen($arFields["PARAMS"]) > 0)
		{
			$arTmp = unserialize(htmlspecialcharsback($arFields["PARAMS"]));
			if ($arTmp)
				$count = $arTmp["COUNT"];
			else
			{
				$arFieldsParams = explode("&", $arFields["PARAMS"]);
				if (is_array($arFieldsParams) && count($arFieldsParams) > 0)
				{
					foreach ($arFieldsParams as $tmp)
					{
						list($key, $value) = explode("=", $tmp);
						if ($key == "count")
						{
							$count = $value;
							break;
						}
					}
				}
			}
		}

		if (!$count)
			$count_tmp = "";
		else
			$count_tmp = intval($count);

		$album_default = GetMessage("SONET_GL_EVENT_TITLE_PHOTO_ALBUM");
		$album_default_24 = GetMessage("SONET_GL_EVENT_TITLE_PHOTO_ALBUM_24");
		$album_default_24_mobile = GetMessage("SONET_GL_EVENT_TITLE_PHOTO_ALBUM_24_MOBILE");

		$section_name = false;
		if (
			intval($arFields["SOURCE_ID"]) > 0
			&& CModule::IncludeModule('iblock')
		)
		{
			if (array_key_exists($arFields["SOURCE_ID"], $arAlbumName))
				$section_name = $arAlbumName[$arFields["SOURCE_ID"]];
			else
			{
				$rsSection = CIBlockSection::GetByID($arFields["SOURCE_ID"]);
				if ($arSection = $rsSection->GetNext())
				{
					$section_name = $arSection["NAME"];
					$arAlbumName[$arFields["SOURCE_ID"]] = $arSection["NAME"];

					if(defined("BX_COMP_MANAGED_CACHE"))
						$GLOBALS["CACHE_MANAGER"]->RegisterTag("iblock_id_".$arSection["IBLOCK_ID"]);
				}
			}
		}

		if (
			!$bMail
			&& array_key_exists("URL", $arFields)
			&& strlen($arFields["URL"]) > 0
		)
		{
			$album_tmp = ($section_name ? $album_default.' <a href="'.$arFields["URL"].'">'.$section_name.'</a>' : '<a href="'.$arFields["URL"].'">'.$album_default.'</a>');
			$album_tmp_24 = ($section_name ? $album_default_24.': <a href="'.$arFields["URL"].'">'.$section_name.'</a>' : '<a href="'.$arFields["URL"].'">'.$album_default_24.'</a>');
			$album_tmp_24_mobile = ($section_name ? $album_default_24_mobile.': '.$section_name : $album_default_24_mobile);
		}
		else
			$album_tmp = ($section_name ? $album_default.' '.$section_name : $album_default);

		if ($bMail)
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_PHOTO_MAIL");
		elseif ($arParams["MOBILE"] == "Y")
			$title_tmp_24 = GetMessage("SONET_GL_EVENT_TITLE_PHOTO_24_MOBILE");
		else
		{
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_PHOTO");
			switch ($arFields["CREATED_BY_PERSONAL_GENDER"])
			{
				case "M":
					$suffix = "_M";
					break;
				case "F":
					$suffix = "_F";
					break;
				default:
					$suffix = "";
			}
			$title_tmp_24 = GetMessage("SONET_GL_EVENT_TITLE_PHOTO_24".$suffix);
		}

		$title = str_replace(
			array("#ALBUM#", "#COUNT#", "#ENTITY#", "#CREATED_BY#"),
			array($album_tmp, $count_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
			$title_tmp
		);

		if (!$bMail)
			$title_24 = str_replace(
				array("#ALBUM#", "#COUNT#", "#ENTITY#", "#CREATED_BY#"),
				array(($arParams["MOBILE"] == "Y" ? $album_tmp_24_mobile : $album_tmp_24), $count_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
				$title_tmp_24
			);

		if ($arParams["MOBILE"] == "Y")
			$arResult["EVENT_FORMATTED"] = array(
				"TITLE_24" => $title_24,
				"MESSAGE" => "",
			);
		else
			$arResult["EVENT_FORMATTED"] = array(
				"TITLE" => $title,
				"TITLE_24" => $title_24,
				"MESSAGE" => "",
				"IS_MESSAGE_SHORT" => true
			);

		if ($bMail)
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arFields);
			if (strlen($url) > 0)
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}
		elseif ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP)
			$arResult["EVENT_FORMATTED"]["DESTINATION"] = array(
				array(
					"STYLE" => "sonetgroups",
					"TITLE" => $arResult["ENTITY"]["FORMATTED"]["NAME"],
					"URL" => $arResult["ENTITY"]["FORMATTED"]["URL"],
				)
			);

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);

		return $arResult;
	}

	public static function FormatEvent_PhotoPhoto($arFields, $arParams, $bMail = false)
	{
		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);

		$arResult = array(
			"EVENT" => $arFields,
			"CREATED_BY" => CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail),
			"ENTITY" => CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, $bMail),
			"EVENT_FORMATTED" => array(),
		);

		$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams);

		$album_tmp = GetMessage("SONET_GL_EVENT_TITLE_PHOTO_ALBUM");
		if (strlen($arFields["PARAMS"]) > 0)
		{
			$arTmp = unserialize(htmlspecialcharsback($arFields["PARAMS"]));
			if ($arTmp && array_key_exists("SECTION_NAME", $arTmp))
			{
				if (
					!$bMail
					&& array_key_exists("SECTION_URL", $arTmp)
					&& strlen($arTmp["SECTION_URL"]) > 0
				)
				{
					if ($arFields["ENTITY_TYPE"] == SONET_ENTITY_GROUP && IsModuleInstalled("extranet"))
						$arTmp["SECTION_URL"] = str_replace("#GROUPS_PATH#", COption::GetOptionString("socialnetwork", "workgroups_page", "/workgroups/", SITE_ID), $arTmp["SECTION_URL"]);
					if ($arParams["MOBILE"] == "Y")
						$album_tmp .= ' '.htmlspecialcharsbx($arTmp["SECTION_NAME"]);
					else
						$album_tmp .= ' <a href="'.$arTmp["SECTION_URL"].'">'.htmlspecialcharsbx($arTmp["SECTION_NAME"]).'</a>';
				}
				else
					$album_tmp .= ' '.htmlspecialcharsbx($arTmp["SECTION_NAME"]);
			}
		}

		$title = str_replace(
			array("#ALBUM#", "#ENTITY#", "#CREATED_BY#"),
			array($album_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
			($arParams["MOBILE"] == "Y" ? GetMessage("SONET_GL_EVENT_TITLE_PHOTOPHOTO_MOBILE") : GetMessage("SONET_GL_EVENT_TITLE_PHOTOPHOTO"))
		);

		if ($arParams["MOBILE"] == "Y")
			$arResult["EVENT_FORMATTED"] = array(
				"TITLE" => $title,
				"MESSAGE" => "",
			);
		else
			$arResult["EVENT_FORMATTED"] = array(
				"TITLE" => $title,
				"MESSAGE" => $arFields["MESSAGE"],
				"IS_MESSAGE_SHORT" => "Y"
			);

		if (!$bMail)
			if ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP)
				$arResult["EVENT_FORMATTED"]["DESTINATION"] = array(
					array(
						"STYLE" => "sonetgroups",
						"TITLE" => $arResult["ENTITY"]["FORMATTED"]["NAME"],
						"URL" => $arResult["ENTITY"]["FORMATTED"]["URL"],
					)
				);

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);

		return $arResult;
	}

	public static function FormatComment_Photo($arFields, $arParams, $bMail = false, $arLog = array())
	{
		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);

		$arResult = array(
				"EVENT_FORMATTED"	=> array(),
			);

		if ($bMail)
		{
			$arResult["CREATED_BY"] = CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail);
			$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arLog, $arParams, $bMail);
		}
		elseif($arParams["USE_COMMENT"] != "Y")
			$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, false);

		if (
			!$bMail
			&& array_key_exists("URL", $arLog)
			&& strlen($arLog["URL"]) > 0
		)
			$photo_tmp = '<a href="'.$arLog["URL"].'">'.$arLog["TITLE"].'</a>';
		else
			$photo_tmp = $arLog["TITLE"];

		if ($bMail)
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_PHOTO_COMMENT_MAIL");
		else
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_PHOTO_COMMENT");

		$album_name = "";
		if (
			array_key_exists("PARAMS", $arLog)
			&& strlen($arLog["PARAMS"]) > 0
		)
		{
			$arTmp = unserialize($arLog["PARAMS"]);
			if ($arTmp && array_key_exists("SECTION_NAME", $arTmp))
				$album_name = $arTmp["SECTION_NAME"];
		}

		if ($bMail)
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_PHOTO_COMMENT_MAIL");
		else
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_PHOTO_COMMENT");

		$title = str_replace(
			array("#TITLE#", "#ENTITY#", "#CREATED_BY#", "#ALBUM#"),
			array($photo_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : ""), $album_name),
			$title_tmp
		);

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => ($bMail || $arParams["USE_COMMENT"] != "Y" ? $title : ""),
			"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : $arFields["MESSAGE"])
		);

		if ($bMail)
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arLog);
			if (strlen($url) > 0)
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}
		else
		{
			$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
			$arAllow = array(
				"HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y",
				"IMG" => "Y", "LOG_IMG" => "N",
				"QUOTE" => "Y", "LOG_QUOTE" => "N",
				"CODE" => "Y", "LOG_CODE" => "N",
				"FONT" => "Y", "LOG_FONT" => "N",
				"LIST" => "Y",
				"SMILES" => "Y",
				"NL2BR" => "Y",
				"MULTIPLE_BR" => "N",
				"VIDEO" => "Y", "LOG_VIDEO" => "N"
			);

			$arAllow = array("HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "LOG_VIDEO" => "N", "SHORT_ANCHOR" => "Y");
			$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));

			if (
				$arParams["MOBILE"] != "Y" 
				&& $arParams["NEW_TEMPLATE"] != "Y"
			)
			{
				$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
					$parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow),
					500
				);
				$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);
			}
		}

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);

		return $arResult;
	}

	public static function FormatEvent_Files($arFields, $arParams, $bMail = false)
	{
		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);

		$arResult = array(
			"EVENT" => $arFields,
			"CREATED_BY" => CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail),
			"ENTITY" => CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, $bMail),
			"EVENT_FORMATTED" => array(),
		);

		if (!$bMail)
			$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams);

		if (
			!$bMail
			&& array_key_exists("URL", $arFields)
			&& strlen($arFields["URL"]) > 0
		)
		{
			if ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP && IsModuleInstalled("extranet"))
			{
				$arFields["URL"] = str_replace("#GROUPS_PATH#", COption::GetOptionString("socialnetwork", "workgroups_page", "/workgroups/", SITE_ID), $arFields["URL"]);
				$arResult["EVENT"]["URL"] = $arFields["URL"];
			}
			$file_tmp = '<a href="'.$arFields["URL"].'">'.$arFields["TITLE"].'</a>';
		}
		else
			$file_tmp = $arFields["TITLE"];

		if ($bMail)
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_FILE_MAIL");
		else
		{
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_FILE");

			switch ($arFields["CREATED_BY_PERSONAL_GENDER"])
			{
				case "M":
					$suffix = "_M";
					break;
				case "F":
					$suffix = "_F";
					break;
				default:
					$suffix = "";
			}
			$title_tmp_24 = GetMessage("SONET_GL_EVENT_TITLE_FILE_24".$suffix);
		}

		$title = str_replace(
			array("#TITLE#", "#ENTITY#", "#CREATED_BY#"),
			array($file_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
			$title_tmp
		);

		if ($arParams["MOBILE"] == "Y")
			$arResult["EVENT_FORMATTED"] = array(
				"TITLE_24" => GetMessage("SONET_GL_EVENT_TITLE_FILE_24_MOBILE"),
				"MESSAGE" => $arFields["MESSAGE"]
			);
		else
			$arResult["EVENT_FORMATTED"] = array(
				"TITLE" => ($bMail ? $title : ""),
				"MESSAGE_TITLE_24" => $title_tmp_24,
				"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : $arFields["MESSAGE"])
			);

		if (!$bMail)
			$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = true;

		$arResult["HAS_COMMENTS"] = "N";
		if (
			intval($arFields["SOURCE_ID"]) > 0
			&& array_key_exists("PARAMS", $arFields)
			&& strlen($arFields["PARAMS"]) > 0
		)
		{
			$arFieldsParams = explode("&", $arFields["PARAMS"]);
			if (is_array($arFieldsParams) && count($arFieldsParams) > 0)
			{
				foreach ($arFieldsParams as $tmp)
				{
					list($key, $value) = explode("=", $tmp);
					if ($key == "forum_id")
					{
						$arResult["HAS_COMMENTS"] = "Y";
						break;
					}
				}
			}
		}

		if ($bMail)
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arFields);
			if (strlen($url) > 0)
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}

		if (!$bMail)
		{
			$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $arFields["ID"]));
			while ($arRight = $dbRight->Fetch())
				$arRights[] = $arRight["GROUP_CODE"];

			$arResult["EVENT_FORMATTED"]["DESTINATION"] = CSocNetLogTools::FormatDestinationFromRights($arRights, array_merge($arParams, array("CREATED_BY" => $arFields["USER_ID"])), $iMoreCount);
			if (intval($iMoreCount) > 0)
				$arResult["EVENT_FORMATTED"]["DESTINATION_MORE"] = $iMoreCount;
		}

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);

		return $arResult;
	}

	public static function FormatComment_Files($arFields, $arParams, $bMail = false, $arLog = array())
	{
		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);

		$arResult = array(
				"EVENT_FORMATTED"	=> array(),
			);

		if ($bMail)
		{
			$arResult["CREATED_BY"] = CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail);
			$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arLog, $arParams, $bMail);
		}
		elseif($arParams["USE_COMMENT"] != "Y")
			$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, false);

		if (
			!$bMail
			&& array_key_exists("URL", $arLog)
			&& strlen($arLog["URL"]) > 0
		)
			$file_tmp = '<a href="'.$arLog["URL"].'">'.$arLog["TITLE"].'</a>';
		else
			$file_tmp = $arLog["TITLE"];

		if ($bMail)
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_FILE_COMMENT_MAIL");
		else
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_FILE_COMMENT");

		$title = str_replace(
			array("#TITLE#", "#ENTITY#", "#CREATED_BY#"),
			array($file_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
			$title_tmp
		);

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => ($bMail || $arParams["USE_COMMENT"] != "Y" ? $title : ""),
			"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : $arFields["MESSAGE"])
		);

		if (!$bMail)
		{
			$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
			$arAllow = array(
				"HTML" => "Y",
				"ANCHOR" => "Y",
				"BIU" => "Y",
				"IMG" => "Y",
				"QUOTE" => "Y",
				"CODE" => "Y",
				"FONT" => "Y",
				"LIST" => "Y",
				"SMILES" => "Y",
				"NL2BR" => "N",
				"VIDEO" => "Y",
				"LOG_VIDEO"	=> "N",
				"SHORT_ANCHOR" => "Y"
			);
			$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));
		}

		if ($bMail)
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arLog);
			if (strlen($url) > 0)
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);

		return $arResult;
	}

	public static function FormatEvent_Task($arFields, $arParams, $bMail = false)
	{
		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);

		$arResult = array(
			"EVENT" => $arFields,
			"CREATED_BY" => CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail),
			"ENTITY" => CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, $bMail),
			"EVENT_FORMATTED" => array(),
		);

		if (!$bMail)
			$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams);

		if (
			!$bMail
			&& array_key_exists("URL", $arFields)
			&& strlen($arFields["URL"]) > 0
		)
			$task_tmp = '<a href="'.$arFields["URL"].'">'.$arFields["TITLE"].'</a>';
		else
			$task_tmp = $arFields["TITLE"];

		$title_tmp = str_replace(
			"#TITLE#",
			$task_tmp,
			$arFields["TITLE_TEMPLATE"]
		);

		if ($bMail)
			$title = str_replace(
				array("#TASK#", "#ENTITY#", "#CREATED_BY#"),
				array($title_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
				GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_TASK_MAIL")
			);
		else
			$title = $title_tmp;

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => $title,
			"MESSAGE" => ($bMail ? str_replace(array("<nobr>", "</nobr>"), array("", ""), $arFields["TEXT_MESSAGE"]) : $arFields["MESSAGE"])
		);

		if ($bMail)
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arFields);
			if (strlen($url) > 0)
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);

		return $arResult;
	}

	public static function FormatEvent_Task2($arFields, $arParams, $bMail = false)
	{
		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);

		// Prevent module versions dependency between tasks and socialnetwork
		if (
			CModule::IncludeModule('tasks')
			&& method_exists('CTaskNotifications', 'FormatTask4SocialNetwork')
		)
		{
			// Code moved out to tasks, use it

			if (
				$bMail
				&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
			)
				IncludeModuleLangFile(__FILE__, LANGUAGE_ID);

			return (CTaskNotifications::FormatTask4SocialNetwork($arFields, $arParams, $bMail));
		}

		// Code wasn't moved out to tasks yet, use current function

		$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/tasks/css/tasks.css");

		$arFields["PARAMS"] = unserialize($arFields["~PARAMS"]);

		$arResult = array(
			"EVENT" => $arFields,
			"CREATED_BY" => CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail),
			"ENTITY" => CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, $bMail),
			"EVENT_FORMATTED" => array(),
		);

		if (!$bMail)
			$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams);

		if (
			!$bMail
			&& $arParams["MOBILE"] != "Y"
			&& array_key_exists("URL", $arFields)
			&& strlen($arFields["URL"]) > 0
		)
			$task_tmp = '<a href="'.$arFields["URL"].'" onclick="if (taskIFramePopup.isLeftClick(event)) {taskIFramePopup.view('.$arFields["SOURCE_ID"].'); return false;}">'.$arFields["TITLE"].'</a>';
		else
			$task_tmp = $arFields["TITLE"];

		$title_tmp = str_replace(
			"#TITLE#",
			$task_tmp,
			GetMessage("SONET_GL_EVENT_TITLE_TASK")
		);

		if($arFields["PARAMS"] && $arFields["PARAMS"]["CREATED_BY"])
		{
			$suffix = (is_array($GLOBALS["arExtranetUserID"]) && in_array($arFields["PARAMS"]["CREATED_BY"], $GLOBALS["arExtranetUserID"]) ? GetMessage("SONET_LOG_EXTRANET_SUFFIX") : "");

			$rsUser = CUser::GetByID(intval($arFields["PARAMS"]["CREATED_BY"]));
			if ($arUser = $rsUser->Fetch())
				$title_tmp .= " (".str_replace("#USER_NAME#", CUser::FormatName(CSite::GetNameFormat(false), $arUser).$suffix, GetMessage("SONET_GL_EVENT_TITLE_TASK_CREATED")).")";
		}

		if ($bMail)
			$title = str_replace(
				array("#TASK#", "#ENTITY#", "#CREATED_BY#"),
				array($title_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
				GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_TASK_MAIL")
			);
		else
		{
			$title = $title_tmp;

			if (
				!is_array($arFields["PARAMS"])
				|| !array_key_exists("TYPE", $arFields["PARAMS"])
				|| strlen($arFields["PARAMS"]["TYPE"]) <= 0
			)
				$arFields["PARAMS"]["TYPE"] = "DEFAULT";

			switch ($arFields["CREATED_BY_PERSONAL_GENDER"])
			{
				case "M":
					$suffix = "_M";
					break;
				case "F":
					$suffix = "_F";
					break;
				default:
					$suffix = "";
			}
			$title_24 = str_replace("#TITLE#", $task_tmp, GetMessage("SONET_GL_EVENT_TITLE_TASK_".strtoupper($arFields["PARAMS"]["TYPE"])."_24".$suffix));
		}

		if (
			!$bMail 
			&& (
				in_array($arFields["PARAMS"]["TYPE"], array("create", "status"))
				|| (
					$arFields["PARAMS"]["TYPE"] == "modify"
					&& is_array($arFields["PARAMS"]["CHANGED_FIELDS"])
				)
			)
			&& CModule::IncludeModule("tasks")
		)
		{
			$rsTask = CTasks::GetByID($arFields["SOURCE_ID"], false);
			if ($arTask = $rsTask->Fetch())
			{
				$task_datetime = $arTask["CHANGED_DATE"];

				if ($arFields["PARAMS"]["TYPE"] == "create")
				{
					if ($arParams["MOBILE"] == "Y")
					{
						$title_24 = GetMessage("SONET_GL_TASKS2_NEW_TASK_MESSAGE");
						$message_24_1 = $task_tmp;
					}
					else
					{
						$message = $message_24_1 = GetMessage("SONET_GL_TASKS2_NEW_TASK_MESSAGE");
						$message_24_2 = $changes_24 = "";
					}
				}
				elseif ($arFields["PARAMS"]["TYPE"] == "modify")
				{
					$arChangesFields = $arFields["PARAMS"]["CHANGED_FIELDS"];
					$changes_24 = implode(", ", CTaskNotifications::__Fields2Names($arChangesFields));

					if ($arParams["MOBILE"] == "Y")
					{
						$title_24 = GetMessage("SONET_GL_TASKS2_TASK_CHANGED_MESSAGE_24_1");
						$message_24_1 = $task_tmp;
					}
					else
					{
						$message = str_replace("#CHANGES#", implode(", ", CTaskNotifications::__Fields2Names($arChangesFields)), GetMessage("SONET_GL_TASKS2_TASK_CHANGED_MESSAGE"));
						$message_24_1 = GetMessage("SONET_GL_TASKS2_TASK_CHANGED_MESSAGE_24_1");
						$message_24_2 = GetMessage("SONET_GL_TASKS2_TASK_CHANGED_MESSAGE_24_2");
					}				
				}
				elseif ($arFields["PARAMS"]["TYPE"] == "status")
				{
					$message = GetMessage("SONET_GL_TASKS2_TASK_STATUS_MESSAGE_".$arTask["STATUS"]);
					$message_24_1 = GetMessage("SONET_GL_TASKS2_TASK_STATUS_MESSAGE_".$arTask["STATUS"]."_24");

					if ($arTask["STATUS"] == 7)
					{
						$message = str_replace("#TASK_DECLINE_REASON#", $arTask["DECLINE_REASON"], $message);
						$message_24_2 = GetMessage("SONET_GL_TASKS2_TASK_STATUS_MESSAGE_".$arTask["STATUS"]."_24_2");
						$changes_24 = $arTask["DECLINE_REASON"];
					}
					elseif ($arTask["STATUS"] == 4)
					{
						$message_24_2 = GetMessage("SONET_GL_TASKS2_TASK_STATUS_MESSAGE_".$arTask["STATUS"]."_24_2");			
						$changes_24 = GetMessage("SONET_GL_TASKS2_TASK_STATUS_MESSAGE_4_24_CHANGES");
					}
					else
						$message_24_2 = $changes_24 = "";
				}

				ob_start();
				$GLOBALS['APPLICATION']->IncludeComponent(
					"bitrix:tasks.task.livefeed", 
					($arParams["MOBILE"] == "Y" ? 'mobile' : ''), 
					array(
						"MOBILE" => ($arParams["MOBILE"] == "Y" ? "Y" : "N"),
						"TASK" => $arTask,
						"MESSAGE" => $message,
						"MESSAGE_24_1" => $message_24_1,
						"MESSAGE_24_2" => $message_24_2,
						"CHANGES_24" => $changes_24,
						"NAME_TEMPLATE"	=> $arParams["NAME_TEMPLATE"],
						"PATH_TO_USER" => $arParams["PATH_TO_USER"]
					), 
					null, 
					array("HIDE_ICONS" => "Y")
				);
				$arFields["MESSAGE"] = ob_get_contents();
				ob_end_clean();
			}
		}

		if ($arParams["MOBILE"] == "Y")
			$arResult["EVENT_FORMATTED"] = array(
				"TITLE" => $title,
				"TITLE_24" => $title_24,
				"MESSAGE" => $arFields["MESSAGE"],
				"DESCRIPTION" => $message_24_1,
				"DESCRIPTION_STYLE" => "task"
			);
		else 
		{
			$arResult["EVENT_FORMATTED"] = array(
				"TITLE" => $title,
				"TITLE_24" => $title_24,
				"MESSAGE" => ($bMail ? str_replace(array("<nobr>", "</nobr>"), array("", ""), $arFields["TEXT_MESSAGE"]) : $arFields["MESSAGE"]),
				"SHORT_MESSAGE" => ($bMail ? str_replace(array("<nobr>", "</nobr>"), array("", ""), $arFields["TEXT_MESSAGE"]) : $arFields["~MESSAGE"]),
				"IS_MESSAGE_SHORT" => true,
				"STYLE" => "tasks-info"
			);

			if (
				!$bMail 
				&& strlen($task_datetime) > 0
			)
				$arResult["EVENT_FORMATTED"]["LOG_DATE_FORMAT"] = $task_datetime;
		}

		if ($bMail)
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arFields);
			if (strlen($url) > 0)
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}
		elseif ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP)
			$arResult["EVENT_FORMATTED"]["DESTINATION"] = array(
				array(
					"STYLE" => "sonetgroups",
					"TITLE" => $arResult["ENTITY"]["FORMATTED"]["NAME"],
					"URL" => $arResult["ENTITY"]["FORMATTED"]["URL"],
				)
			);

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);

		return $arResult;
	}

	public static function FormatEvent_SystemGroups($arFields, $arParams, $bMail = false)
	{
		global $arSocNetLogGroups;

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);

		$arResult = array(
			"EVENT" => $arFields,
			"CREATED_BY" => array(),
			"ENTITY" => array(),
			"EVENT_FORMATTED" => array(),
		);

		if (
			$arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_USER
			&& intval($arFields["ENTITY_ID"]) > 0
		)
		{
			$suffix = (is_array($GLOBALS["arExtranetUserID"]) && in_array($arFields["ENTITY_ID"], $GLOBALS["arExtranetUserID"]) ? GetMessage("SONET_LOG_EXTRANET_SUFFIX") : "");

			if ($bMail)
			{
				if (
					strlen($arFields["USER_NAME"]) > 0
					|| strlen($arFields["USER_LAST_NAME"]) > 0
				)
					$arResult["ENTITY"]["FORMATTED"] = $arFields["USER_NAME"]." ".$arFields["USER_LAST_NAME"].$suffix;
				else
					$arResult["ENTITY"]["FORMATTED"] = $arFields["USER_LOGIN"].$suffix;

				$arResult["ENTITY"]["TYPE_MAIL"] = GetMessage("SONET_GL_EVENT_ENTITY_U");
			}
			else
			{
				$arFieldsTooltip = array(
					"ID" => $arFields["ENTITY_ID"],
					"NAME" => $arFields["~USER_NAME"],
					"LAST_NAME" => $arFields["~USER_LAST_NAME"],
					"SECOND_NAME" => $arFields["~USER_SECOND_NAME"],
					"LOGIN" => $arFields["~USER_LOGIN"],
				);
				$arParams["NAME_TEMPLATE"] .= $suffix;
				$arResult["CREATED_BY"]["TOOLTIP_FIELDS"] = CSocNetLogTools::FormatEvent_FillTooltip($arFieldsTooltip, $arParams);

				if (!$bMail)
					$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams, "USER_");
			}
		}

		if (intval($arFields["MESSAGE"]) > 0)
		{
			if (!is_array($arSocNetLogGroups))
				$arSocNetLogGroups = array();

			if (array_key_exists($arFields["MESSAGE"], $arSocNetLogGroups))
				$arGroup = $arSocNetLogGroups[$arFields["MESSAGE"]];
			else
			{
				$rsGroup = CSocNetGroup::GetList(
					array("ID" => "DESC"),
					array(
						"ID" => $arFields["MESSAGE"],
						"ACTIVE" => "Y"
					)
				);
				$arGroup = $rsGroup->GetNext();
			}

			if ($arGroup)
			{
				if (!array_key_exists($arGroup["ID"], $arSocNetLogGroups))
				{
					$arSocNetLogGroups[$arGroup["ID"]] = $arGroup;
					if(defined("BX_COMP_MANAGED_CACHE"))
						$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_group_".$arGroup["ID"]);
				}

				$suffix = (is_array($GLOBALS["arExtranetGroupID"]) && in_array($arFields["MESSAGE"], $GLOBALS["arExtranetGroupID"]) ? GetMessage("SONET_LOG_EXTRANET_SUFFIX") : "");

				if ($bMail)
					$group_tmp = $arGroup["NAME"].$suffix;
				else
				{
					$url = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $arFields["MESSAGE"]));
					$group_tmp = '<a href="'.$url.'">'.$arGroup["NAME"].$suffix.'</a>';
				}

				if ($bMail)
					$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_SYSTEM_GROUPS_".strtoupper($arFields["TITLE"])."_MAIL");
				else
				{
					$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_SYSTEM_GROUPS_".strtoupper($arFields["TITLE"]).(strlen(trim($arFields["USER_PERSONAL_GENDER"])) > 0 ? "_".$arFields["USER_PERSONAL_GENDER"] : ""));
					$title_tmp_24 = GetMessage("SONET_GL_EVENT_TITLE_SYSTEM_GROUPS_".strtoupper($arFields["TITLE"])."_24".(strlen(trim($arFields["USER_PERSONAL_GENDER"])) > 0 ? "_".$arFields["USER_PERSONAL_GENDER"] : ""));
				}

				$title = str_replace(
					array("#GROUP_NAME#", "#ENTITY#"),
					array($group_tmp, $arResult["ENTITY"]["FORMATTED"]),
					$title_tmp
				);

				if ($bMail)
					$arResult["EVENT_FORMATTED"] = array(
						"TITLE" => $title,
						"MESSAGE" => false
					);
				else
				{
					switch ($arFields["TITLE"])
					{
						case "group":
							$classname = "join-group";
							break;
						case "exclude_user":
						case "ungroup":
							$classname = "leave-group";
							break;
						default:
							$classname = "";
					}

					$arResult["EVENT_FORMATTED"] = array(
						"TITLE" => false,
						"TITLE_24" => $title_tmp_24,
						"MESSAGE" => $title,
						"IS_MESSAGE_SHORT" => true,
						"DESTINATION" => array(
							array(
								"STYLE" => "sonetgroups",
								"TITLE" => $group_tmp
							)
						),
						"STYLE" => $classname
					);
				}
			}
		}

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);

		return $arResult;
	}

	public static function FormatEvent_SystemFriends($arFields, $arParams, $bMail = false)
	{
		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);

		$arResult = array();
		$bActiveUsers = false;

		if (intval($arFields["MESSAGE"]) > 0)
		{
			$dbUser = CUser::GetByID($arFields["MESSAGE"]);
			if ($arUser = $dbUser->Fetch())
			{
				if(defined("BX_COMP_MANAGED_CACHE"))
					$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_NAME_".intval($arUser["ID"]));

				$messageUserID = $arFields["MESSAGE"];

				if (
					$arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_USER 
					&& intval($arFields["ENTITY_ID"]) > 0
				)
				{ 
					$dbUser2 = CUser::GetByID($arFields["ENTITY_ID"]);
					if ($arUser2 = $dbUser2->Fetch())
					{
						if(defined("BX_COMP_MANAGED_CACHE"))
							$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_NAME_".intval($arUser2["ID"]));

						$secondUserID = $arFields["ENTITY_ID"];
						$bActiveUsers = true;
					}
				}
			}
		}

		if ($bActiveUsers)
		{
			$arResult = array(
				"EVENT" => $arFields,
				"CREATED_BY" => array(),
				"ENTITY" => array(),
				"EVENT_FORMATTED" => array(),
			);

			$suffix = (is_array($GLOBALS["arExtranetUserID"]) && in_array($secondUserID, $GLOBALS["arExtranetUserID"]) ? GetMessage("SONET_LOG_EXTRANET_SUFFIX") : "");

			if ($bMail)
			{
				if (strlen($arFields["USER_NAME"]) > 0 || strlen($arFields["USER_LAST_NAME"]) > 0)
					$arResult["ENTITY"]["FORMATTED"] = $arFields["USER_NAME"]." ".$arFields["USER_LAST_NAME"].$suffix;
				else
					$arResult["ENTITY"]["FORMATTED"] = $arFields["USER_LOGIN"].$suffix;

				$arResult["ENTITY"]["TYPE_MAIL"] = GetMessage("SONET_GL_EVENT_ENTITY_U");
			}
			else
			{
				$arFieldsTooltip = array(
					"ID" => $secondUserID,
					"NAME" => $arFields["~USER_NAME"],
					"LAST_NAME" => $arFields["~USER_LAST_NAME"],
					"SECOND_NAME" => $arFields["~USER_SECOND_NAME"],
					"LOGIN" => $arFields["~USER_LOGIN"],
				);
				$oldNameTemplate = $arParams["NAME_TEMPLATE"];
				$arParams["NAME_TEMPLATE"] .= $suffix;
				$arResult["CREATED_BY"]["TOOLTIP_FIELDS"] = CSocNetLogTools::FormatEvent_FillTooltip($arFieldsTooltip, $arParams);
				$arParams["NAME_TEMPLATE"] = $oldNameTemplate;

				if (!$bMail)
					$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams, "USER_");
			}

			$suffix = (is_array($GLOBALS["arExtranetUserID"]) && in_array($messageUserID, $GLOBALS["arExtranetUserID"]) ? GetMessage("SONET_LOG_EXTRANET_SUFFIX") : "");

			if ($bMail)
			{
				if (strlen($arUser["NAME"]) > 0 || strlen($arUser["LAST_NAME"]) > 0)
					$user_tmp .= $arUser["NAME"]." ".$arUser["LAST_NAME"].$suffix;
				else
					$user_tmp .= $arUser["LOGIN"].$suffix;
			}
			else
			{
				$ajax_page = $GLOBALS["APPLICATION"]->GetCurPageParam("", array("bxajaxid", "logout"));

				$suffix = (is_array($GLOBALS["arExtranetUserID"]) && in_array($messageUserID, $GLOBALS["arExtranetUserID"]) ? GetMessage("SONET_LOG_EXTRANET_SUFFIX") : "");

				$oldNameTemplate = $arParams["NAME_TEMPLATE"];
				$arParams["NAME_TEMPLATE"] .= $suffix;

				$anchor_id = RandString(8);

				$user_tmp .= '<span class="" id="anchor_'.$anchor_id.'">'.CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false)).'</span>';
				$user_tmp .= '<script type="text/javascript">';
				$user_tmp .= 'BX.tooltip('.$arUser["ID"].', "anchor_'.$anchor_id.'", "'.CUtil::JSEscape($ajax_page).'");';
				$user_tmp .= '</script>';
					
				$arParams["NAME_TEMPLATE"] = $oldNameTemplate;					
			}

			if ($bMail)
				$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_SYSTEM_FRIENDS_".strtoupper($arFields["TITLE"])."_MAIL");
			else
			{
				$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_SYSTEM_FRIENDS_".strtoupper($arFields["TITLE"]).(strlen(trim($arFields["USER_PERSONAL_GENDER"])) > 0 ? "_".$arFields["USER_PERSONAL_GENDER"]: ""));
				$title_tmp_24 = GetMessage("SONET_GL_EVENT_TITLE_SYSTEM_FRIENDS_".strtoupper($arFields["TITLE"])."_24".(strlen(trim($arFields["USER_PERSONAL_GENDER"])) > 0 ? "_".$arFields["USER_PERSONAL_GENDER"] : ""));
			}

			$title = str_replace(
				array("#USER_NAME#", "#ENTITY#"),
				array($user_tmp, $arResult["ENTITY"]["FORMATTED"]),
				$title_tmp
			);

			if ($bMail)
			{
				$arResult["EVENT_FORMATTED"] = array(
					"TITLE" => $title,
					"MESSAGE" => false
				);

				$friends_page = COption::GetOptionString("socialnetwork", "friends_page", false, SITE_ID);
				if (strlen($friends_page) > 0)
				{
					$arFields["URL"] = str_replace(array("#user_id#", "#USER_ID#"), $secondUserID, $friends_page);
					$arResult["EVENT_FORMATTED"]["URL"] = CSocNetLogTools::FormatEvent_GetURL($arFields);
				}
			}
			else
			{
				switch ($arFields["TITLE"])
				{
					case "friend":
						$classname = "join-group";
						break;
					case "unfriend":
						$classname = "leave-group";
						break;
					default:
						$classname = "";
				}

				$arResult["EVENT_FORMATTED"] = array(
					"TITLE" => false,
					"TITLE_24" => $title_tmp_24,
					"MESSAGE" => $title,
					"IS_MESSAGE_SHORT" => true,
					"DESTINATION" => array(
						array(
							"STYLE" => "users",
							"TITLE" => $user_tmp,
							"URL" => str_replace(array("#user_id#", "#USER_ID#", "#id#", "#ID#"), $arFields["MESSAGE"], $arParams["~PATH_TO_USER"])
						)
					),
					"STYLE" => $classname
				);
			}
		}

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);

		return $arResult;
	}

	public static function FormatEvent_System($arFields, $arParams, $bMail = false)
	{
		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);

		$arResult = array(
			"EVENT" => $arFields,
			"CREATED_BY" => array(),
			"ENTITY" => array(),
			"EVENT_FORMATTED" => array(),
		);

		if (intval($arFields["ENTITY_ID"]) > 0)
		{
			$suffix = (is_array($GLOBALS["arExtranetGroupID"]) && in_array($arFields["ENTITY_ID"], $GLOBALS["arExtranetGroupID"]) ? GetMessage("SONET_LOG_EXTRANET_SUFFIX") : "");
			if ($bMail)
			{
				$arResult["ENTITY"]["FORMATTED"] = $arFields["GROUP_NAME"].$suffix;
				$arResult["ENTITY"]["TYPE_MAIL"] = GetMessage("SONET_GL_EVENT_ENTITY_G");
			}
			elseif (strpos($arFields["MESSAGE"], ",") > 0)
				$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, false);
		}

		if (in_array($arFields["TITLE"], array("moderate", "unmoderate", "join", "unjoin")))
		{
			if (strpos($arFields["MESSAGE"], ",") !== false)
			{
				$arResult["CREATED_BY"] = false;
				$arGroup = array(
					"IMAGE_ID" => $arFields["GROUP_IMAGE_ID"]
				);
				$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatarGroup($arGroup, $arParams); // group avatar
			}
			else
			{
				$suffix = (is_array($GLOBALS["arExtranetUserID"]) && in_array($arFields["MESSAGE"], $GLOBALS["arExtranetUserID"]) ? GetMessage("SONET_LOG_EXTRANET_SUFFIX") : "");

				$dbUser = CUser::GetByID($arFields["MESSAGE"]);
				if ($arUser = $dbUser->Fetch())
				{
					$arFieldsTooltip = array(
						"ID" => $arUser["ID"],
						"NAME" => $arUser["NAME"],
						"LAST_NAME" => $arUser["LAST_NAME"],
						"SECOND_NAME" => $arUser["SECOND_NAME"],
						"LOGIN" => $arUser["LOGIN"],
					);
					$oldNameTemplate = $arParams["NAME_TEMPLATE"];
					$arParams["NAME_TEMPLATE"] .= $suffix;
					$arResult["CREATED_BY"]["TOOLTIP_FIELDS"] = CSocNetLogTools::FormatEvent_FillTooltip($arFieldsTooltip, $arParams);
					$arParams["NAME_TEMPLATE"] = $oldNameTemplate;

					if (!$bMail)
						$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arUser, $arParams, "");
				}
			}
		}
		else
		{
			$suffix = (is_array($GLOBALS["arExtranetUserID"]) && in_array($arFields["USER_ID"], $GLOBALS["arExtranetUserID"]) ? GetMessage("SONET_LOG_EXTRANET_SUFFIX") : "");

			$arFieldsTooltip = array(
				"ID" => $arFields["USER_ID"],
				"NAME" => $arFields["~CREATED_BY_NAME"],
				"LAST_NAME" => $arFields["~CREATED_BY_LAST_NAME"],
				"SECOND_NAME" => $arFields["~CREATED_BY_SECOND_NAME"],
				"LOGIN" => $arFields["~CREATED_BY_LOGIN"],
			);
			$oldNameTemplate = $arParams["NAME_TEMPLATE"];
			$arParams["NAME_TEMPLATE"] .= $suffix;
			$arResult["CREATED_BY"]["TOOLTIP_FIELDS"] = CSocNetLogTools::FormatEvent_FillTooltip($arFieldsTooltip, $arParams);
			$arParams["NAME_TEMPLATE"] = $oldNameTemplate;

			if (!$bMail)
				$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams, "CREATED_BY_");
		}

		if (strlen($arFields["MESSAGE"]) > 0)
		{
			$arUsersID = explode(",", $arFields["MESSAGE"]);

			$bFirst = true;
			$count = 0;
			$user_tmp = "";

			if ($bMail)
			{
				$dbUser = CUser::GetList(
					($by="last_name"),
					($order="asc"),
					array(
						"ID" => implode(" | ", $arUsersID)
					)
				);
				while($arUser = $dbUser->Fetch())
				{
					$suffix = (is_array($GLOBALS["arExtranetUserID"]) && in_array($arUser["ID"], $GLOBALS["arExtranetUserID"]) ? GetMessage("SONET_LOG_EXTRANET_SUFFIX") : "");

					$count++;
					if (!$bFirst)
						$user_tmp .= ", ";

					if (
						strlen($arUser["NAME"]) > 0
						|| strlen($arUser["LAST_NAME"]) > 0
					)
						$user_tmp .= $arUser["NAME"]." ".$arUser["LAST_NAME"].$suffix;
					else
						$user_tmp .= $arUser["LOGIN"].$suffix;

					$bFirst = false;
				}
			}
			else
			{
				$ajax_page = $GLOBALS["APPLICATION"]->GetCurPageParam("", array("bxajaxid", "logout"));
				$dbUser = CUser::GetList(
					($by="last_name"),
					($order="asc"),
					array(
						"ID" => implode(" | ", $arUsersID)
					),
					array("FIELDS" => array("ID", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN"))
				);
				while($arUser = $dbUser->Fetch())
				{
					if (defined("BX_COMP_MANAGED_CACHE"))
						$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_NAME_".intval($arUser["ID"]));

					$suffix = (is_array($GLOBALS["arExtranetUserID"]) && in_array($arUser["ID"], $GLOBALS["arExtranetUserID"]) ? GetMessage("SONET_LOG_EXTRANET_SUFFIX") : "");

					$count++;
					if (!$bFirst)
						$user_tmp .= ", ";

					$oldNameTemplate = $arParams["NAME_TEMPLATE"];
					$arParams["NAME_TEMPLATE"] .= $suffix;

					$anchor_id = RandString(8);

					if ($arParams["MOBILE"] == "Y")
						$user_tmp .= '<a href="'.str_replace(array("#user_id#", "#USER_ID#", "#id#", "#ID#"), $arUser["ID"], $arParams["~PATH_TO_USER"]).'">'.CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false)).'</a>';
					else
					{
						$user_tmp .= '<a class="" id="anchor_'.$anchor_id.'" href="'.str_replace(array("#user_id#", "#USER_ID#", "#id#", "#ID#"), $arUser["ID"], $arParams["~PATH_TO_USER"]).'">'.CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false)).'</a>';
						$user_tmp .= '<script type="text/javascript">';
						$user_tmp .= 'BX.tooltip('.$arUser["ID"].', "anchor_'.$anchor_id.'", "'.CUtil::JSEscape($ajax_page).'");';
						$user_tmp .= '</script>';
					}

					$arParams["NAME_TEMPLATE"] = $oldNameTemplate;

					$bFirst = false;
				}
			}
		}

		if ($bMail)
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_SYSTEM_".strtoupper($arFields["TITLE"])."_".($count > 1 ? "2" : "1")."_MAIL");
		else
		{
			if (in_array($arFields["TITLE"], array("moderate", "unmoderate", "join", "unjoin")) && $arUser)
				$suffix = $arUser["PERSONAL_GENDER"];
			else
				$suffix = $arFields["CREATED_BY_PERSONAL_GENDER"];

			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_SYSTEM_".strtoupper($arFields["TITLE"])."_".($count > 1 ? "2" : "1".(strlen(trim($suffix)) > 0 ? "_".$suffix : "")));

			$title_tmp_24 = GetMessage("SONET_GL_EVENT_TITLE_SYSTEM_".strtoupper($arFields["TITLE"])."_".($count > 1 ? "2_24" : "1_24".(strlen(trim($suffix)) > 0 ? "_".$suffix : "")));
		}

		$url = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $arFields["ENTITY_ID"]));
		$suffix = (is_array($GLOBALS["arExtranetGroupID"]) && in_array($arFields["ENTITY_ID"], $GLOBALS["arExtranetGroupID"]) ? GetMessage("SONET_LOG_EXTRANET_SUFFIX") : "");

		if (strlen($url) > 0)
			$group_tmp = '<a href="'.$url.'">'.$arFields["GROUP_NAME"].'</a>'.$suffix;
		else
			$group_tmp = $arFields["GROUP_NAME"].$suffix;

		$title = str_replace(
			array("#USER_NAME#", "#ENTITY#", "#GROUP_NAME#"),
			array($user_tmp, $arResult["ENTITY"]["FORMATTED"], $group_tmp),
			$title_tmp
		);

		$title_tmp_24 = str_replace(
			array("#USER_NAME#"),
			array($user_tmp),
			$title_tmp_24
		);

		if ($bMail)
			$arResult["EVENT_FORMATTED"] = array(
				"TITLE" => $title,
				"MESSAGE" => false
			);
		else
		{
			switch ($arFields["TITLE"])
			{
				case "join":
				case "moderate":
				case "owner":
					$classname = "join-group";
					break;
				case "unjoin":
				case "exclude_group":
				case "unmoderate":
					$classname = "leave-group";
					break;
				default:
					$classname = "";
			}

			if ($arParams["MOBILE"] == "Y")
				$arResult["EVENT_FORMATTED"] = array(
					"TITLE_24" => $title_tmp_24,
					"DESTINATION" => array(
						array(
							"STYLE" => "",
							"TITLE" => $arFields["GROUP_NAME"].$suffix,
							"URL" => $url
						)
					),
					"STYLE" => $classname
				);
			else 
				$arResult["EVENT_FORMATTED"] = array(
					"TITLE" => false,
					"MESSAGE" => $title,
					"IS_MESSAGE_SHORT" => true,
					"TITLE_24" => $title_tmp_24,
					"DESTINATION" => array(
						array(
							"STYLE" => "sonetgroups",
							"TITLE" => $arFields["GROUP_NAME"].$suffix,
							"URL" => $url
						)
					),
					"STYLE" => $classname
				);
		}

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);

		return $arResult;
	}

	public static function SetCacheLastLogID($type = "log", $id)
	{
		global $CACHE_MANAGER;

		$CACHE_MANAGER->Read(86400*365, "socnet_log_".$type."_id", "log");
		$CACHE_MANAGER->Clean("socnet_log_".$type."_id", "log");
		$CACHE_MANAGER->Read(86400*365, "socnet_log_".$type."_id", "log");
		$CACHE_MANAGER->SetImmediate("socnet_log_".$type."_id", intval($id));
	}

	public static function GetCacheLastLogID($type = "log")
	{
		global $CACHE_MANAGER;

		$id = 0;
		if ($CACHE_MANAGER->Read(86400*365, "socnet_log_".$type."_id", "log"))
			$id = $CACHE_MANAGER->Get("socnet_log_".$type."_id");

		return $id;
	}

	public static function SetUserCache($type = "log", $user_id, $max_id, $max_viewed_id, $count, $bSetViewTime = false, $LastViewTS = 0)
	{
		global $CACHE_MANAGER;

		$user_id = intval($user_id);

		$CACHE_MANAGER->Read(86400*365, "socnet_log_user_".$type."_".$user_id);
		$CACHE_MANAGER->Clean("socnet_log_user_".$type."_".$user_id);
		$CACHE_MANAGER->Read(86400*365, "socnet_log_user_".$type."_".$user_id);

		$CACHE_MANAGER->SetImmediate("socnet_log_user_".$type."_".$user_id, array(
			"Type" => $type,
			"UserID" => $user_id,
			"MaxID" => intval($max_id),
			"MaxViewedID" => intval($max_viewed_id),
			"Count" => intval($count),
			"LastVisitTS" => time(),
			"LastViewTS" => ($bSetViewTime ? time() : intval($LastViewTS))
		));
	}

	public static function GetUserCache($type = "log", $user_id)
	{
		global $CACHE_MANAGER;

		if ($CACHE_MANAGER->Read(86400*365, "socnet_log_user_".$type."_".intval($user_id)))
			return $CACHE_MANAGER->Get("socnet_log_user_".$type."_".intval($user_id));
		else
			return array(
				"Type" => "",
				"UserID" => 0,
				"MaxID" => 0,
				"MaxViewedID" => 0,
				"Count" => 0,
				"LastVisitTS" => 0,
				"LastViewTS" => 0
			);
	}

	public static function AddComment_Forum($arFields)
	{
		if (!CModule::IncludeModule("forum"))
			return false;

		$dbResult = CSocNetLog::GetList(
			array(),
			array("ID" => $arFields["LOG_ID"]),
			false,
			false,
			array("ID", "SOURCE_ID", "SITE_ID")
		);

		if ($arLog = $dbResult->Fetch())
		{
			$arMessage = CForumMessage::GetByID($arLog["SOURCE_ID"]);
			if ($arMessage)
			{
				$userID = $GLOBALS["USER"]->GetID();

				$arLogSites = array();
				$rsLogSite = CSocNetLog::GetSite($arLog["ID"]);
				while ($arLogSite = $rsLogSite->Fetch())
					$arLogSites[] = $arLogSite["LID"];

				$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin($arLogSites);

				if ($arFields["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
				{
					if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "forum", "full", $bCurrentUserIsAdmin))
						$strPermission = "Y";
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "forum", "newtopic", $bCurrentUserIsAdmin))
						$strPermission = "M";
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "forum", "answer", $bCurrentUserIsAdmin))
						$strPermission = "I";
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "forum", "view", $bCurrentUserIsAdmin))
						$strPermission = "E";
				}
				else
				{
					if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "forum", "full", $bCurrentUserIsAdmin))
						$strPermission = "Y";
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "forum", "newtopic", $bCurrentUserIsAdmin))
						$strPermission = "M";
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "forum", "answer", $bCurrentUserIsAdmin))
						$strPermission = "I";
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "forum", "view", $bCurrentUserIsAdmin))
						$strPermission = "E";
				}

				$arFieldsMessage = array(
					"POST_MESSAGE" => $arFields["TEXT_MESSAGE"],
					"USE_SMILES" => "Y",
					"PERMISSION_EXTERNAL" => $strPermission,
					"PERMISSION" => $strPermission
				);
				$messageID = ForumAddMessage("REPLY", $arMessage["FORUM_ID"], $arMessage["TOPIC_ID"], 0, $arFieldsMessage, $sError, $sNote);
			}
			else
				$sError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
		}
		else
			$sError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");

		return array(
			"SOURCE_ID" => $messageID,
			"RATING_TYPE_ID" => "FORUM_POST",
			"RATING_ENTITY_ID" => $messageID,
			"ERROR" => $sError,
			"NOTES" => $sNote
		);
	}

	public static function AddComment_Blog($arFields)
	{
		if (!CModule::IncludeModule("blog"))
			return false;

		$dbResult = CSocNetLog::GetList(
			array(),
			array("ID" => $arFields["LOG_ID"]),
			false,
			false,
			array("ID", "SOURCE_ID", "SITE_ID")
		);

		if ($arLog = $dbResult->Fetch())
		{
			$arPost = CBlogPost::GetByID($arLog["SOURCE_ID"]);
			if ($arPost)
			{
				$arBlog = CBlog::GetByID($arPost["BLOG_ID"]);
				$userID = $GLOBALS["USER"]->GetID();

				$arLogSites = array();
				$rsLogSite = CSocNetLog::GetSite($arLog["ID"]);
				while ($arLogSite = $rsLogSite->Fetch())
					$arLogSites[] = $arLogSite["LID"];

				$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin($arLogSites);
				$strPermission = BLOG_PERMS_DENY;
				$strPostPermission = BLOG_PERMS_DENY;

				if ($arFields["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
				{
					if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "full_post", $bCurrentUserIsAdmin))
						$strPostPermission = BLOG_PERMS_FULL;
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "premoderate_post"))
						$strPostPermission = BLOG_PERMS_PREMODERATE;
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "write_post"))
						$strPostPermission = BLOG_PERMS_WRITE;
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "moderate_post"))
						$strPostPermission = BLOG_PERMS_MODERATE;
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "view_post"))
						$strPostPermission = BLOG_PERMS_READ;

					if($strPostPermission > BLOG_PERMS_DENY)
					{
						if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "full_comment", $bCurrentUserIsAdmin))
							$strPermission = BLOG_PERMS_FULL;
						elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "moderate_comment"))
							$strPermission = BLOG_PERMS_MODERATE;
						elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "write_comment"))
							$strPermission = BLOG_PERMS_WRITE;
						elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "premoderate_comment"))
							$strPermission = BLOG_PERMS_PREMODERATE;
						elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "view_comment"))
							$strPermission = BLOG_PERMS_READ;
					}
				}
				else
				{
					if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "full_post", $bCurrentUserIsAdmin) || $GLOBALS["APPLICATION"]->GetGroupRight("blog") >= "W" || $arParams["USER_ID"] == $user_id)
						$strPostPermission = BLOG_PERMS_FULL;
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "moderate_post"))
						$strPostPermission = BLOG_PERMS_MODERATE;
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "write_post"))
						$strPostPermission = BLOG_PERMS_WRITE;
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "premoderate_post"))
						$strPostPermission = BLOG_PERMS_PREMODERATE;
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "view_post"))
						$strPostPermission = BLOG_PERMS_READ;

					if($strPostPermission > BLOG_PERMS_DENY)
					{
						if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "full_comment", $bCurrentUserIsAdmin) || $GLOBALS["APPLICATION"]->GetGroupRight("blog") >= "W" || $arParams["USER_ID"] == $user_id)
							$strPermission = BLOG_PERMS_FULL;
						elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "moderate_comment"))
							$strPermission = BLOG_PERMS_MODERATE;
						elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "write_comment"))
							$strPermission = BLOG_PERMS_WRITE;
						elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "premoderate_comment"))
							$strPermission = BLOG_PERMS_PREMODERATE;
						elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "view_comment"))
							$strPermission = BLOG_PERMS_READ;
					}
				}

				$UserIP = CBlogUser::GetUserIP();
				$path_to_post = ($arFields["ENTITY_TYPE"] == SONET_ENTITY_GROUP ? $arFields["PATH_TO_GROUP_BLOG_POST"] : $arFields["PATH_TO_USER_BLOG_POST"]);

				$arFieldsComment = Array(
					"POST_ID" => $arPost["ID"],
					"BLOG_ID" => $arBlog["ID"],
					"POST_TEXT" => $arFields["TEXT_MESSAGE"],
					"DATE_CREATE" => ConvertTimeStamp(time()+CTimeZone::GetOffset(), "FULL"),
					"AUTHOR_IP" => $UserIP[0],
					"AUTHOR_IP1" => $UserIP[1],
					"AUTHOR_ID" => $userID,
					"PARENT_ID" => false
				);

				if($strPermission == BLOG_PERMS_PREMODERATE)
				{
					$arFieldsComment["PUBLISH_STATUS"] = BLOG_PUBLISH_STATUS_READY;
					$strNotes = GetMessage("SONET_GL_ADD_COMMENT_BLOG_PREMODERATE");
				}

				$commentUrl = CComponentEngine::MakePathFromTemplate(
					htmlspecialcharsBack($path_to_post),
					array(
						"blog" => $arBlog["URL"],
						"post_id" => CBlogPost::GetPostID($arPost["ID"], $arPost["CODE"], $arFields["BLOG_ALLOW_POST_CODE"]),
						"user_id" => $arBlog["OWNER_ID"],
						"group_id" => ($arFields["ENTITY_TYPE"] == SONET_ENTITY_GROUP ? $arFields["ENTITY_ID"] : false)
					)
				);

				$arFieldsComment["PATH"] = $commentUrl.(strpos($arFieldsComment["PATH"], "?") !== false ? "&" : "?")."commentId=#comment_id###comment_id#";

				$commentId = CBlogComment::Add($arFieldsComment);
				if($strPermission == BLOG_PERMS_PREMODERATE)
					unset($commentId);

				BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/comment/".$arPost["ID"]."/");
				BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/post/".$arPost["ID"]."/");
				BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/first_page/");
				BXClearCache(True, "/".SITE_ID."/blog/last_comments/");
				BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/rss_out/".$arPost["POST_ID"]."/C/");
				BXClearCache(True, "/".SITE_ID."/blog/last_messages/");
				BXClearCache(True, "/".SITE_ID."/blog/commented_posts/");
				BXClearCache(True, "/".SITE_ID."/blog/popular_posts/");
			}
			else
				$strError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
		}

		return array(
			"SOURCE_ID" => $commentId,
			"RATING_TYPE_ID" => "BLOG_COMMENT",
			"RATING_ENTITY_ID" => $commentId,
			"ERROR" => $strError,
			"NOTES"	=> $strNotes
		);
	}

	public static function AddComment_Microblog($arFields)
	{
		if (!CModule::IncludeModule("blog"))
			return false;

		$dbResult = CSocNetLog::GetList(
			array(),
			array("ID" => $arFields["LOG_ID"]),
			false,
			false,
			array("ID", "SOURCE_ID", "SITE_ID")
		);

		if ($arLog = $dbResult->Fetch())
		{
			$arPost = CBlogPost::GetByID($arLog["SOURCE_ID"]);
			if ($arPost)
			{
				$arBlog = CBlog::GetByID($arPost["BLOG_ID"]);
				$userID = $GLOBALS["USER"]->GetID();

				$arLogSites = array();
				$rsLogSite = CSocNetLog::GetSite($arLog["ID"]);
				while ($arLogSite = $rsLogSite->Fetch())
					$arLogSites[] = $arLogSite["LID"];

				$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin($arLogSites);
				$strPermission = BLOG_PERMS_DENY;
				$strPostPermission = BLOG_PERMS_DENY;

				if ($arFields["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
				{
					if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "full_post", $bCurrentUserIsAdmin))
						$strPostPermission = BLOG_PERMS_FULL;
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "premoderate_post"))
						$strPostPermission = BLOG_PERMS_PREMODERATE;
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "write_post"))
						$strPostPermission = BLOG_PERMS_WRITE;
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "moderate_post"))
						$strPostPermission = BLOG_PERMS_MODERATE;
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "view_post"))
						$strPostPermission = BLOG_PERMS_READ;

					if($strPostPermission > BLOG_PERMS_DENY)
					{
						if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "full_comment", $bCurrentUserIsAdmin))
							$strPermission = BLOG_PERMS_FULL;
						elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "moderate_comment"))
							$strPermission = BLOG_PERMS_MODERATE;
						elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "write_comment"))
							$strPermission = BLOG_PERMS_WRITE;
						elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "premoderate_comment"))
							$strPermission = BLOG_PERMS_PREMODERATE;
						elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "blog", "view_comment"))
							$strPermission = BLOG_PERMS_READ;
					}
				}
				else
				{
					if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "full_post", $bCurrentUserIsAdmin) || $GLOBALS["APPLICATION"]->GetGroupRight("blog") >= "W" || $arParams["USER_ID"] == $user_id)
						$strPostPermission = BLOG_PERMS_FULL;
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "moderate_post"))
						$strPostPermission = BLOG_PERMS_MODERATE;
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "write_post"))
						$strPostPermission = BLOG_PERMS_WRITE;
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "premoderate_post"))
						$strPostPermission = BLOG_PERMS_PREMODERATE;
					elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "view_post"))
						$strPostPermission = BLOG_PERMS_READ;

					if($strPostPermission > BLOG_PERMS_DENY)
					{
						if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "full_comment", $bCurrentUserIsAdmin) || $GLOBALS["APPLICATION"]->GetGroupRight("blog") >= "W" || $arParams["USER_ID"] == $user_id)
							$strPermission = BLOG_PERMS_FULL;
						elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "moderate_comment"))
							$strPermission = BLOG_PERMS_MODERATE;
						elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "write_comment"))
							$strPermission = BLOG_PERMS_WRITE;
						elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "premoderate_comment"))
							$strPermission = BLOG_PERMS_PREMODERATE;
						elseif (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "blog", "view_comment"))
							$strPermission = BLOG_PERMS_READ;
					}
				}

				$UserIP = CBlogUser::GetUserIP();
				$path_to_post = ($arFields["ENTITY_TYPE"] == SONET_ENTITY_GROUP ? $arFields["PATH_TO_GROUP_MICROBLOG_POST"] : $arFields["PATH_TO_USER_MICROBLOG_POST"]);

				$arFieldsComment = Array(
					"POST_ID" => $arPost["ID"],
					"BLOG_ID" => $arBlog["ID"],
					"POST_TEXT" => $arFields["TEXT_MESSAGE"],
					"DATE_CREATE" => ConvertTimeStamp(time()+CTimeZone::GetOffset(), "FULL"),
					"AUTHOR_IP" => $UserIP[0],
					"AUTHOR_IP1" => $UserIP[1],
					"AUTHOR_ID" => $userID,
					"PARENT_ID" => false
				);

				if($strPermission == BLOG_PERMS_PREMODERATE)
				{
					$arFieldsComment["PUBLISH_STATUS"] = BLOG_PUBLISH_STATUS_READY;
					$strNotes = GetMessage("SONET_GL_ADD_COMMENT_BLOG_PREMODERATE");
				}

				$commentUrl = CComponentEngine::MakePathFromTemplate(
					htmlspecialcharsBack($path_to_post),
					array(
						"blog" => $arBlog["URL"],
						"post_id" => CBlogPost::GetPostID($arPost["ID"], $arPost["CODE"], $arFields["BLOG_ALLOW_POST_CODE"]),
						"user_id" => $arBlog["OWNER_ID"],
						"group_id" => ($arFields["ENTITY_TYPE"] == SONET_ENTITY_GROUP ? $arFields["ENTITY_ID"] : false)
					)
				);

				$arFieldsComment["PATH"] = $commentUrl.(strpos($arFieldsComment["PATH"], "?") !== false ? "&" : "?")."commentId=#comment_id###comment_id#";

				$commentId = CBlogComment::Add($arFieldsComment);
				if($strPermission == BLOG_PERMS_PREMODERATE)
					unset($commentId);

				BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/comment/".$arPost["ID"]."/");
				BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/post/".$arPost["ID"]."/");
				BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/first_page/");
				BXClearCache(True, "/".SITE_ID."/blog/last_comments/");
				BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/rss_out/".$arPost["POST_ID"]."/C/");
				BXClearCache(True, "/".SITE_ID."/blog/last_messages/");
				BXClearCache(True, "/".SITE_ID."/blog/commented_posts/");
				BXClearCache(True, "/".SITE_ID."/blog/popular_posts/");
			}
			else
				$strError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
		}

		return array(
			"SOURCE_ID" => $commentId,
			"RATING_TYPE_ID" => "BLOG_COMMENT",
			"RATING_ENTITY_ID" => $commentId,
			"ERROR" => $strError,
			"NOTES"	=> $strNotes
		);
	}

	public static function AddComment_Files($arFields)
	{
		if (!CModule::IncludeModule("forum"))
			return false;

		if (!CModule::IncludeModule("iblock"))
			return false;

		$dbResult = CSocNetLog::GetList(
			array(),
			array("ID" => $arFields["LOG_ID"]),
			false,
			false,
			array("ID", "SOURCE_ID", "PARAMS")
		);

		$bFound = false;
		if ($arLog = $dbResult->Fetch())
		{
			if (strlen($arLog["PARAMS"]) > 0)
			{
				$arFieldsParams = explode("&", $arLog["PARAMS"]);
				if (is_array($arFieldsParams) && count($arFieldsParams) > 0)
				{
					foreach ($arFieldsParams as $tmp)
					{
						list($key, $value) = explode("=", $tmp);
						if ($key == "forum_id")
						{
							$FORUM_ID = intval($value);
							break;
						}
					}
				}
			}
			if ($FORUM_ID > 0 && intval($arLog["SOURCE_ID"]) > 0)
				$bFound = true;
		}

		if ($bFound)
		{
			$arElement = false;

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
						"PARAM1" => "IB",
						"PARAM2" => $arElement["ID"]
					)
				);

				if (!$arMessage = $dbMessage->Fetch())
				{
					// Add Topic and Root Message
					$arForum = CForumNew::GetByID($FORUM_ID);
					$sImage = "";
					if (intVal($arElement["PREVIEW_PICTURE"]) > 0):
						$arImage = CFile::GetFileArray($arElement["PREVIEW_PICTURE"]);
						if (!empty($arImage))
							$sImage = ($arForum["ALLOW_IMG"] == "Y" ? "[IMG]".$arImage["SRC"]."[/IMG]" : '');
					endif;
					$sElementPreview = $arElement["~PREVIEW_TEXT"];
					if ($arForum["ALLOW_HTML"] != "Y")
						$sElementPreview = strip_tags($sElementPreview);

					$strFirstMessage = str_replace(array("#IMAGE#", "#TITLE#", "#BODY#"),
						array($sImage, $arElement["~NAME"], $sElementPreview),
						GetMessage("WD_TEMPLATE_MESSAGE"));

					$TOPIC_ID = CSocNetLogTools::AddComment_Review_CreateRoot_Forum($arElement, $FORUM_ID, true, $strFirstMessage);
					$bNewTopic = true;
				}
				else
					$TOPIC_ID = $arMessage["TOPIC_ID"];

				if(intval($TOPIC_ID) > 0)
				{
					// Add comment
					$messageID = false;
					$arFieldsMessage = array(
						"POST_MESSAGE" => $arFields["TEXT_MESSAGE"],
						"USE_SMILES" => "Y",
						"PARAM2" => $arElement["ID"]
					);
					$messageID = ForumAddMessage("REPLY", $FORUM_ID, $TOPIC_ID, 0, $arFieldsMessage, $sError, $sNote);

					if (!$messageID)
						$strError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
					else
						CSocNetLogTools::AddComment_Review_UpdateElement_Forum($arElement, $TOPIC_ID, $bNewTopic);
				}
				else
					$strError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
			}
			else
				$strError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
		}
		else
			$strError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");

		return array(
			"SOURCE_ID" => $messageID,
			"RATING_TYPE_ID" => "FORUM_POST",
			"RATING_ENTITY_ID" => $messageID,
			"ERROR" => $strError,
			"NOTES" => ""
		);
	}

	public static function AddComment_Photo($arFields)
	{
		$dbResult = CSocNetLog::GetList(
			array(),
			array("ID" => $arFields["LOG_ID"]),
			false,
			false,
			array("ID", "SOURCE_ID", "PARAMS")
		);

		$bFoundForum = false;
		$bFoundBlog = false;
		if ($arLog = $dbResult->Fetch())
		{
			if (strlen($arLog["PARAMS"]) > 0)
			{
				$arTmp = unserialize(htmlspecialcharsback($arLog["PARAMS"]));
				if ($arTmp)
				{
					$FORUM_ID = $arTmp["FORUM_ID"];
					$BLOG_ID = $arTmp["BLOG_ID"];
				}
			}
			if ($FORUM_ID > 0 && intval($arLog["SOURCE_ID"]) > 0)
				$bFoundForum = true;
			elseif ($BLOG_ID > 0 && intval($arLog["SOURCE_ID"]) > 0)
				$bFoundBlog = true;
		}

		if ($bFoundForum)
			$arReturn = CSocNetLogTools::AddComment_Photo_Forum($arFields, $FORUM_ID, $arLog);
		elseif ($bFoundBlog)
			$arReturn = CSocNetLogTools::AddComment_Photo_Blog($arFields, $BLOG_ID, $arLog);
		else
			$arReturn =  array(
				"SOURCE_ID" => false,
				"ERROR" => GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR"),
				"NOTES" => ""
			);

		return $arReturn;
	}

	public static function AddComment_Photo_Forum($arFields, $FORUM_ID, $arLog)
	{
		if (!CModule::IncludeModule("forum"))
			return false;

		if (!CModule::IncludeModule("iblock"))
			return false;

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
					"PARAM2" => $arElement["ID"]
				);
				$messageID = ForumAddMessage("REPLY", $FORUM_ID, $TOPIC_ID, 0, $arFieldsMessage, $sError, $sNote);

				if (!$messageID)
					$strError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
				else
					CSocNetLogTools::AddComment_Review_UpdateElement_Forum($arElement, $TOPIC_ID, $bNewTopic);
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
				"NOTES" => ""
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
			"NOTES" => ""
		);
	}

	public static function AddComment_Review_CheckIBlock($arElement)
	{
		return CSocNetLogTools::AddComment_Review_CheckIBlock_Forum($arElement);
	}

	public static function AddComment_Review_CheckIBlock_Forum($arElement)
	{
		if (!CModule::IncludeModule("iblock"))
			return false;

		if (!CModule::IncludeModule("forum"))
			return false;

		$needProperty = array();
		$ELEMENT_IBLOCK_ID = intVal($arElement["IBLOCK_ID"]);
		$ELEMENT_NAME = Trim($arElement["~NAME"]);
		$ELEMENT_FORUM_TOPIC_ID = intVal($arElement["PROPERTY_FORUM_TOPIC_ID_VALUE"]);
		$ELEMENT_FORUM_MESSAGE_CNT = intVal($arElement["PROPERTY_FORUM_MESSAGE_CNT_VALUE"]);

		if ($ELEMENT_FORUM_TOPIC_ID <= 0):
			$db_res = CIBlockElement::GetProperty($ELEMENT_IBLOCK_ID, $arElement["ID"], false, false, array("CODE" => "FORUM_TOPIC_ID"));
			if (!($db_res && $res = $db_res->Fetch()))
				$needProperty[] = "FORUM_TOPIC_ID";
		endif;
		if ($ELEMENT_FORUM_MESSAGE_CNT <= 0):
			$db_res = CIBlockElement::GetProperty($ELEMENT_IBLOCK_ID, $arElement["ID"], false, false, array("CODE" => "FORUM_MESSAGE_CNT"));
			if (!($db_res && $res = $db_res->Fetch()))
				$needProperty[] = "FORUM_MESSAGE_CNT";
		endif;
		if (!empty($needProperty)):
			$obProperty = new CIBlockProperty;
			$res = true;
			foreach ($needProperty as $nameProperty)
			{
				$sName = trim($nameProperty == "FORUM_TOPIC_ID" ? GetMessage("F_FORUM_TOPIC_ID") : GetMessage("F_FORUM_MESSAGE_CNT"));
				$sName = (empty($sName) ? $nameProperty : $sName);
				$res = $obProperty->Add(array(
					"IBLOCK_ID" => $ELEMENT_IBLOCK_ID,
					"ACTIVE" => "Y",
					"PROPERTY_TYPE" => "N",
					"MULTIPLE" => "N",
					"NAME" => $sName,
					"CODE" => $nameProperty
					)
				);
			}
		endif;

		// Set NULL for topic_id if it was deleted
		if ($ELEMENT_FORUM_TOPIC_ID > 0):
			$arTopic = CForumTopic::GetByID($ELEMENT_FORUM_TOPIC_ID);
			if (!$arTopic || !is_array($arTopic) || count($arTopic) <= 0)
			{
				CIBlockElement::SetPropertyValues($arElement["ID"], $ELEMENT_IBLOCK_ID, 0, "FORUM_TOPIC_ID");
				$ELEMENT_FORUM_TOPIC_ID = 0;
			}
		endif;

		return true;
	}

	public static function AddComment_Review_CheckIBlock_Blog($arElement)
	{
		if (!CModule::IncludeModule("iblock"))
			return false;

		if (!CModule::IncludeModule("blog"))
			return false;

		$needProperty = array();
		$ELEMENT_IBLOCK_ID = intVal($arElement["IBLOCK_ID"]);
		$ELEMENT_NAME = Trim($arElement["~NAME"]);
		$ELEMENT_BLOG_POST_ID = intVal($arElement["PROPERTY_BLOG_POST_ID_VALUE"]);
		$ELEMENT_BLOG_COMMENT_CNT = intVal($arElement["PROPERTY_BLOG_COMMENT_CNT_VALUE"]);

		if ($ELEMENT_BLOG_POST_ID <= 0):
			$db_res = CIBlockElement::GetProperty($ELEMENT_IBLOCK_ID, $arElement["ID"], false, false, array("CODE" => "BLOG_POST_ID"));
			if (!($db_res && $res = $db_res->Fetch()))
				$needProperty[] = "BLOG_POST_ID";
		endif;
		if ($ELEMENT_BLOG_COMMENT_CNT <= 0):
			$db_res = CIBlockElement::GetProperty($ELEMENT_IBLOCK_ID, $arElement["ID"], false, false, array("CODE" => "BLOG_COMMENT_CNT"));
			if (!($db_res && $res = $db_res->Fetch()))
				$needProperty[] = "BLOG_COMMENT_CNT";
		endif;
		if (!empty($needProperty)):
			$obProperty = new CIBlockProperty;
			$res = true;
			foreach ($needProperty as $nameProperty)
			{
				$sName = trim($nameProperty == "BLOG_POST_ID" ? GetMessage("P_BLOG_POST_ID") : GetMessage("P_BLOG_COMMENTS_CNT"));
				$sName = (empty($sName) ? $nameProperty : $sName);
				$res = $obProperty->Add(array(
					"IBLOCK_ID" => $ELEMENT_IBLOCK_ID,
					"ACTIVE" => "Y",
					"PROPERTY_TYPE" => "N",
					"MULTIPLE" => "N",
					"NAME" => $sName,
					"CODE" => $nameProperty
					)
				);
			}
		endif;

		// Set NULL for post_id if it was deleted
		if ($ELEMENT_BLOG_POST_ID > 0):
			$arTopic = CBlogPost::GetByID($ELEMENT_BLOG_POST_ID);
			if (!$arTopic || !is_array($arTopic) || count($arTopic) <= 0)
			{
				CIBlockElement::SetPropertyValues($arElement["ID"], $ELEMENT_IBLOCK_ID, 0, "BLOG_POST_ID");
				$ELEMENT_BLOG_POST_ID = 0;
			}
		endif;

		return $ELEMENT_BLOG_POST_ID;
	}

	public static function AddComment_Review_CreateRoot($arElement, $forumID, $bPostFirstMessage = false, $strFirstMessage = "")
	{
		return CSocNetLogTools::AddComment_Review_CreateRoot_Forum($arElement, $forumID, $bPostFirstMessage, $strFirstMessage);
	}

	public static function AddComment_Review_CreateRoot_Forum($arElement, $forumID, $bPostFirstMessage = false, $strFirstMessage = "")
	{
		if (!CModule::IncludeModule("forum"))
			return false;

		if ($bPostFirstMessage && strlen($strFirstMessage) <= 0)
			return false;

		// Add Topic
		$arUserStart = array(
			"ID" => intVal($arElement["~CREATED_BY"]),
			"NAME" => $GLOBALS["FORUM_STATUS_NAME"]["guest"]
		);
		if ($arUserStart["ID"] > 0)
		{
			$res = array();
			$db_res = CForumUser::GetListEx(array(), array("USER_ID" => $arElement["~CREATED_BY"]));
			if ($db_res && $res = $db_res->Fetch()):
				$res["FORUM_USER_ID"] = intVal($res["ID"]);
				$res["ID"] = $res["USER_ID"];
			else:
				$db_res = CUser::GetByID($arElement["~CREATED_BY"]);
				if ($db_res && $res = $db_res->Fetch()):
					$res["SHOW_NAME"] = COption::GetOptionString("forum", "USER_SHOW_NAME", "Y");
					$res["USER_PROFILE"] = "N";
				endif;
			endif;
			if (!empty($res)):
				$arUserStart = $res;
				$sName = ($res["SHOW_NAME"] == "Y" ? trim($res["NAME"]." ".$res["LAST_NAME"]) : "");
				$arUserStart["NAME"] = (empty($sName) ? trim($res["LOGIN"]) : $sName);
			endif;
		}
		$arUserStart["NAME"] = (empty($arUserStart["NAME"]) ? $GLOBALS["FORUM_STATUS_NAME"]["guest"] : $arUserStart["NAME"]);

		$GLOBALS["DB"]->StartTransaction();
		$arFields = Array(
			"TITLE" => $arElement["~NAME"],
			"TAGS" => $arElement["~TAGS"],
			"FORUM_ID" => $forumID,
			"USER_START_ID" => $arUserStart["ID"],
			"USER_START_NAME" => $arUserStart["NAME"],
			"LAST_POSTER_NAME" => $arUserStart["NAME"],
			"APPROVED" => "Y"
		);
		$TOPIC_ID = CForumTopic::Add($arFields);

		if ($bPostFirstMessage && intVal($TOPIC_ID) > 0)
		{
			if (COption::GetOptionString("forum", "FILTER", "Y") == "Y")
				$strFirstMessage = CFilterUnquotableWords::Filter($strFirstMessage);

			// Add post as new message
			$arFields = Array(
				"POST_MESSAGE" => $strFirstMessage,
				"AUTHOR_ID" => $arUserStart["ID"],
				"AUTHOR_NAME" => $arUserStart["NAME"],
				"FORUM_ID" => $forumID,
				"TOPIC_ID" => $TOPIC_ID,
				"APPROVED" => "Y",
				"NEW_TOPIC" => "Y",
				"PARAM1" => "IB",
				"PARAM2" => intVal($arElement["ID"])
			);
			$MID = CForumMessage::Add($arFields, false, array("SKIP_INDEXING" => "Y", "SKIP_STATISTIC" => "N"));

			if (intVal($MID) <= 0)
			{
				$arError[] = array(
					"code" => "message is not added 1",
					"title" => GetMessage("F_ERR_ADD_MESSAGE"));
				CForumTopic::Delete($TID);
				$TID = 0;
			}
			elseif ($arParams["SUBSCRIBE_AUTHOR_ELEMENT"] == "Y" && intVal($arResult["ELEMENT"]["~CREATED_BY"]) > 0)
			{
				if ($arUserStart["USER_PROFILE"] == "N"):
					$arUserStart["FORUM_USER_ID"] = CForumUser::Add(array("USER_ID" => $arResult["ELEMENT"]["~CREATED_BY"]));
				endif;
				if (intVal($arUserStart["FORUM_USER_ID"]) > 0):
					CForumSubscribe::Add(array(
						"USER_ID" => $arResult["ELEMENT"]["~CREATED_BY"],
						"FORUM_ID" => $arParams["FORUM_ID"],
						"SITE_ID" => SITE_ID,
						"TOPIC_ID" => $TID,
						"NEW_TOPIC_ONLY" => "N")
					);
					BXClearCache(true, "/bitrix/forum/user/".$arResult["ELEMENT"]["~CREATED_BY"]."/subscribe/"); // Sorry, Max.
				endif;
			}
		}
		elseif (intVal($TOPIC_ID) <= 0)
		{
			$GLOBALS["DB"]->Rollback();
			return false;
		}

		$GLOBALS["DB"]->Commit();

		return $TOPIC_ID;
	}

	public static function AddComment_Review_CreateRoot_Blog($arElement, $blogID)
	{
		if (!CModule::IncludeModule("blog"))
			return false;

		$arBlog = CBlog::GetByID($blogID);

		$arElement["DETAIL_PICTURE"] = CFile::GetFileArray($arElement["DETAIL_PICTURE"]);
		$arElement["REAL_PICTURE"] = CFile::GetFileArray($arElement["PROPERTY_REAL_PICTURE_VALUE"]);

		if (!empty($arElement["TAGS"]))
		{
			$arCategoryVal = explode(",", $arElement["TAGS"]);
			foreach($arCategoryVal as $k => $v)
			{
				if ($id = CBlogCategory::Add(array("BLOG_ID" => $arBlog["ID"],"NAME" => $v)))
					$arCategory[] = $id;
			}
		}

		$arFields=array(
			"TITLE" => $arElement["NAME"],
			"DETAIL_TEXT" =>
				"[IMG]http://".$_SERVER['HTTP_HOST'].$arElement["DETAIL_PICTURE"]["SRC"]."[/IMG]\n".
				"[URL=http://".$_SERVER['HTTP_HOST'].$arElement["~DETAIL_PAGE_URL"]."]".$arElement["NAME"]."[/URL]\n".
				(!empty($arElement["TAGS"]) ? $arElement["TAGS"]."\n" : "").
				$arElement["~DETAIL_TEXT"]."\n".
				"[URL=http://".$_SERVER['HTTP_HOST'].$arElement["REAL_PICTURE"]["SRC"]."]".GetMessage("P_ORIGINAL")."[/URL]",
			"CATEGORY_ID" => implode(",", $arCategory),
			"PUBLISH_STATUS" => "P",
			"PERMS_POST" => array(),
			"PERMS_COMMENT" => array(),
			"=DATE_CREATE" => $GLOBALS["DB"]->GetNowFunction(),
			"=DATE_PUBLISH" => $GLOBALS["DB"]->GetNowFunction(),
			"AUTHOR_ID" =>	(!empty($arElement["CREATED_BY"]) ? $arElement["CREATED_BY"] : 1),
			"BLOG_ID" => $arBlog["ID"],
			"ENABLE_TRACKBACK" => "N"
		);

		$POST_ID = CBlogPost::Add($arFields);

		return $POST_ID;
	}

	public static function AddComment_Review_UpdateElement($arElement, $topicID, $bNewTopic)
	{
		CSocNetLogTools::AddComment_Review_UpdateElement_Forum($arElement, $topicID, $bNewTopic);
	}

	public static function AddComment_Review_UpdateElement_Forum($arElement, $topicID, $bNewTopic)
	{
		if (!CModule::IncludeModule("forum"))
			return false;

		if ($bNewTopic):
			CIBlockElement::SetPropertyValues($arElement["ID"], $arElement["IBLOCK_ID"], intVal($topicID), "FORUM_TOPIC_ID");
			$FORUM_MESSAGE_CNT = 1;
		else:
			$FORUM_MESSAGE_CNT = CForumMessage::GetList(array(), array("TOPIC_ID" => $topicID, "APPROVED" => "Y", "!PARAM1" => "IB"), true);
		endif;
		CIBlockElement::SetPropertyValues($arElement["ID"], $arElement["IBLOCK_ID"], intVal($FORUM_MESSAGE_CNT), "FORUM_MESSAGE_CNT");
		ForumClearComponentCache("bitrix:forum.topic.reviews");
	}

	public static function AddComment_Review_UpdateElement_Blog($arElement, $postID, $blogID, $bNewPost)
	{
		if (!CModule::IncludeModule("blog"))
			return false;

		if ($bNewPost):
			CIBlockElement::SetPropertyValues($arElement["ID"], $arElement["IBLOCK_ID"], intVal($postID), "BLOG_POST_ID");
			$BLOG_COMMENT_CNT = 1;
		else:
			$BLOG_COMMENT_CNT = CBlogComment::GetList(array(), array("POST_ID" => $postID), array());
		endif;

		CIBlockElement::SetPropertyValues($arElement["ID"], $arElement["IBLOCK_ID"], intVal($BLOG_COMMENT_CNT), "BLOG_COMMENT_CNT");

		$arBlog = CBlog::GetByID($blogID);

		BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/comment/".$postID."/");
		BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/post/".$postID."/");
		BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/first_page/");
		BXClearCache(True, "/".SITE_ID."/blog/last_comments/");
		BXClearCache(True, "/".SITE_ID."/blog/".$arBlog["URL"]."/rss_out/".$postID."/C/");
		BXClearCache(True, "/".SITE_ID."/blog/last_messages/");
		BXClearCache(True, "/".SITE_ID."/blog/commented_posts/");
		BXClearCache(True, "/".SITE_ID."/blog/popular_posts/");
	}

	public static function AddComment_Tasks($arFields)
	{
		global $DB;
		if (!CModule::IncludeModule("forum"))
			return false;

		if (!CModule::IncludeModule("tasks"))
			return false;

		$dbResult = CSocNetLog::GetList(
			array(),
			array("ID" => $arFields["LOG_ID"]),
			false,
			false,
			array("ID", "SOURCE_ID", "SITE_ID")
		);

		if ($arLog = $dbResult->Fetch())
		{
			$rsTask = CTasks::GetById($arLog["SOURCE_ID"]);
			if ($arTask = $rsTask->Fetch())
			{
				$forumID = COption::GetOptionString("tasks", "task_forum_id");

				if ($forumID)
				{
					if (!$arTask["FORUM_TOPIC_ID"])
					{
						$arUserStart = array(
							"ID" => intVal($arTask["CREATED_BY"]),
							"NAME" => $GLOBALS["FORUM_STATUS_NAME"]["guest"]
						);
						if ($arUserStart["ID"] > 0)
						{
							$res = array();
							$db_res = CForumUser::GetListEx(array(), array("USER_ID" => $arTask["CREATED_BY"]));
							if ($db_res && $res = $db_res->Fetch())
							{
								$res["FORUM_USER_ID"] = intVal($res["ID"]);
								$res["ID"] = $res["USER_ID"];
							}
							else
							{
								$db_res = CUser::GetByID($arTask["CREATED_BY"]);
								if ($db_res && $res = $db_res->Fetch())
								{
									$res["SHOW_NAME"] = COption::GetOptionString("forum", "USER_SHOW_NAME", "Y");
									$res["USER_PROFILE"] = "N";
								}
							}
							if (!empty($res))
							{
								$arUserStart = $res;
								$sName = ($res["SHOW_NAME"] == "Y" ? trim($res["NAME"]." ".$res["LAST_NAME"]) : "");
								$arUserStart["NAME"] = (empty($sName) ? trim($res["LOGIN"]) : $sName);
							}
						}
						$arUserStart["NAME"] = (empty($arUserStart["NAME"]) ? $GLOBALS["FORUM_STATUS_NAME"]["guest"] : $arUserStart["NAME"]);
						$DB->StartTransaction();
						$arTopicFields = Array(
							"TITLE" => $arTask["TITLE"],
							"FORUM_ID" => $forumID,
							"USER_START_ID" => $arUserStart["ID"],
							"USER_START_NAME" => $arUserStart["NAME"],
							"LAST_POSTER_NAME" => $arUserStart["NAME"],
							"APPROVED" => "Y"
						);
						$TID = CForumTopic::Add($arTopicFields);
						if (intVal($TID) > 0)
						{
							$arTask["FORUM_TOPIC_ID"] = $TID;
							$arTaskFields = array("FORUM_TOPIC_ID" => $TID);

							$task = new CTasks();
							$task->Update($arTask["ID"], $arTaskFields);
						}
						if (!$arTask["FORUM_TOPIC_ID"])
						{
							$DB->Rollback();
						}
						else
						{
							$DB->Commit();
						}
					}

					if ($arTask["FORUM_TOPIC_ID"])
					{
						$userID = $GLOBALS["USER"]->GetID();

						$arLogSites = array();
						$rsLogSite = CSocNetLog::GetSite($arLog["ID"]);
						while ($arLogSite = $rsLogSite->Fetch())
							$arLogSites[] = $arLogSite["LID"];

						$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin($arLogSites);

						if ($arFields["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
						{
							if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_GROUP, $arFields["ENTITY_ID"], "tasks", "view", $bCurrentUserIsAdmin))
								$strPermission = "I";
						}
						else
						{
							if (CSocNetFeaturesPerms::CanPerformOperation($userID, SONET_ENTITY_USER, $arFields["ENTITY_ID"], "tasks", "view", $bCurrentUserIsAdmin))
								$strPermission = "I";
						}

						$arFieldsMessage = array(
							"POST_MESSAGE"			=> $arFields["TEXT_MESSAGE"],
							"USE_SMILES"			=> "Y",
							"PERMISSION_EXTERNAL"	=> $strPermission,
							"PERMISSION" 			=> $strPermission
						);
						$MESSAGE_TYPE = 'REPLY';
						$messageID = ForumAddMessage($MESSAGE_TYPE, $forumID, $arTask["FORUM_TOPIC_ID"], 0, $arFieldsMessage, $sError, $sNote);

						if ($messageID && ($arMessage = CForumMessage::GetByID($messageID)))
						{
							$arLogFields = array(
								"TASK_ID" => $arTask["ID"],
								"USER_ID" => $userID,
								"CREATED_DATE" => $arMessage["POST_DATE"],
								"FIELD" => "COMMENT",
								"TO_VALUE" => $messageID
							);
							$log = new CTaskLog();
							$log->Add($arLogFields);

							// notification to IM
							$arRecipientsIDs = CTaskNotifications::GetRecipientsIDs($arTask);

							if (IsModuleInstalled("im") && CModule::IncludeModule("im") && sizeof($arRecipientsIDs))
							{
								$extranetSiteId = false;
								if (CModule::IncludeModule('extranet')
									&& method_exists('CExtranet', 'GetExtranetSiteID')
								)
								{
									$extranetSiteId = CExtranet::GetExtranetSiteID();
								}

								foreach ($arRecipientsIDs as $recipientUserID)
								{
									$arFilter = array(
										'UF_DEPARTMENT' => false,
										'ID'            => $recipientUserID
										);

									$rsUser = CUser::GetList(
										$by = 'last_name', 
										$order = 'asc', 
										$arFilter, 
										array('SELECT' => array('UF_DEPARTMENT'))
										);

									$isExtranetUser = false;

									if ($arUser = $rsUser->Fetch())
										$isExtranetUser = true;

									if ($isExtranetUser && ($extranetSiteId !== false))
									{
										if ($arTask["GROUP_ID"])
										{
											$pathTemplate = str_replace(
												"#group_id#", 
												$arTask["GROUP_ID"], 
												COption::GetOptionString(
													"tasks", 
													"paths_task_group_entry", 
													"/extranet/workgroups/group/#group_id#/tasks/task/view/#task_id#/", 
													$extranetSiteId
													)
												);

											$pathTemplate = str_replace(
												"#GROUP_ID#", 
												$arTask["GROUP_ID"], 
												$pathTemplate
												);
										}
										else
										{
											$pathTemplate = COption::GetOptionString(
												"tasks", 
												"paths_task_user_entry", 
												"/extranet/contacts/personal/user/#user_id#/tasks/task/view/#task_id#/", 
												$extranetSiteId
												);
										}
									}
									else
									{
										if ($arTask["GROUP_ID"])
										{
											$pathTemplate = str_replace(
												"#group_id#", 
												$arTask["GROUP_ID"], 
												COption::GetOptionString(
													"tasks", 
													"paths_task_group_entry", 
													"/workgroups/group/#group_id#/tasks/task/view/#task_id#/", 
													$arLog["SITE_ID"]
													)
												);

											$pathTemplate = str_replace(
												"#GROUP_ID#", 
												$arTask["GROUP_ID"], 
												$pathTemplate
												);
										}
										else
										{
											$pathTemplate = COption::GetOptionString(
												"tasks", 
												"paths_task_user_entry", 
												"/company/personal/user/#user_id#/tasks/task/view/#task_id#/", 
												$arLog["SITE_ID"]
												);
										}
									}

									$messageUrl = tasksServerName()
										. CComponentEngine::MakePathFromTemplate(
											$pathTemplate, 
											array(
												"user_id" => $recipientUserID, 
												"task_id" => $arTask["ID"], 
												"action"  => "view"
												)
											);

									if (strpos($messageUrl, "?") === false)
										$messageUrl .= "?";
									else
										$messageUrl .= "&";
									
									$messageUrl .= "MID=" . $messageID;

									$MESSAGE_SITE = trim(
										htmlspecialcharsbx(
											strip_tags(
												str_replace(
													array("\r\n","\n","\r"), 
													' ', 
													htmlspecialcharsback($arFields['TEXT_MESSAGE'])
													)
												)
											)
										);
									$dot = strlen($MESSAGE_SITE)>=100? '...': '';
									$MESSAGE_SITE = substr($MESSAGE_SITE, 0, 99) . $dot;

									$arMessageFields = array(
										"TO_USER_ID"     => $recipientUserID,
										"FROM_USER_ID"   => $userID, 
										"NOTIFY_TYPE"    => IM_NOTIFY_FROM, 
										"NOTIFY_MODULE"  => "tasks", 
										"NOTIFY_EVENT" 	 => "comment",
										"NOTIFY_MESSAGE" => str_replace(
											array("#TASK_TITLE#", "#TASK_COMMENT_TEXT#"), 
											array('[URL=' . $messageUrl . "#message" . $messageID.']' . htmlspecialcharsbx($arTask["TITLE"]) . '[/URL]', '[COLOR=#000000]' . $MESSAGE_SITE . '[/COLOR]'), 
											($MESSAGE_TYPE != "EDIT" ? GetMessage("SONET_GL_EVENT_TITLE_TASK_COMMENT_MESSAGE_ADD") : GetMessage("SONET_GL_EVENT_TITLE_TASK_COMMENT_MESSAGE_EDIT"))
										),
										"NOTIFY_MESSAGE_OUT" => str_replace(
											array("#TASK_TITLE#", "#TASK_COMMENT_TEXT#"), 
											array(htmlspecialcharsbx($arTask["TITLE"]), $MESSAGE_SITE . ' #BR# ' . $messageUrl . "#message" . $messageID . ' '), 
											($MESSAGE_TYPE != "EDIT" ? GetMessage("SONET_GL_EVENT_TITLE_TASK_COMMENT_MESSAGE_ADD") : GetMessage("SONET_GL_EVENT_TITLE_TASK_COMMENT_MESSAGE_EDIT"))
										),
									);

									CIMNotify::Add($arMessageFields);
								}
							}

							CSocNetLog::Update(
								$arFields["LOG_ID"],
								array(
									'PARAMS' => serialize(array('TYPE' => 'comment'))
								)
							);
						}
					}
				}				
			}
		}

		if (!$messageID)
			$sError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");

		return array(
			"SOURCE_ID" => $messageID,
			"RATING_TYPE_ID" => "FORUM_POST",
			"RATING_ENTITY_ID" => $messageID,
			"ERROR" => $sError,
			"NOTES" => $sNote
		);
	}

	public static function OnAfterPhotoUpload($arFields, $arComponentParams, $arComponentResult)
	{
		static $arSiteWorkgroupsPage;

		if (!CModule::IncludeModule("iblock"))
			return;

		if (
			!array_key_exists("IS_SOCNET", $arComponentParams)
			|| $arComponentParams["IS_SOCNET"] != "Y"
		)
			return;

		$arComponentResult["SECTION"]["PATH"] = array();
		$rsPath = CIBlockSection::GetNavChain(intval($arComponentParams["IBLOCK_ID"]), intval($arFields["IBLOCK_SECTION"]));
		while($arPath = $rsPath->Fetch())
			$arComponentResult["SECTION"]["PATH"][] = $arPath;

		foreach($arComponentResult["SECTION"]["PATH"] as $arPathSection)
			if (strlen(trim($arPathSection["PASSWORD"])) > 0)
				return;

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
				$arSiteWorkgroupsPage[$arSite["ID"]] = COption::GetOptionString("socialnetwork", "workgroup_page", $arSite["DIR"]."workgroups/", $arSite["ID"]);
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
			CSocNetLogRights::SetForSonet($res["ID"], $entity_type, $entity_id, "photo", "view");
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

			$sAuthorName = GetMessage("SONET_LOG_GUEST");
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
				"URL" => str_replace(array("#SECTION_ID#", "#section_id#"), $arFields["IBLOCK_SECTION"], $arComponentResult["URL"]["SECTION_EMPTY"]),
				"MODULE_ID" => false,
				"CALLBACK_FUNC" => false,
				"EXTERNAL_ID" => $arFields["IBLOCK_SECTION"]."_".$arFields["MODIFIED_BY"],
				"PARAMS" => serialize($arLogParams),
				"SOURCE_ID" => $arFields["IBLOCK_SECTION"]
			);

			$serverName = (defined("SITE_SERVER_NAME") && strLen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name");

			$arSonetFields["TEXT_MESSAGE"] = str_replace(array("#TITLE#"),
				array($arComponentResult["SECTION"]["TITLE"]),
				GetMessage("SONET_PHOTO_LOG_MAIL_TEXT"));

			if ($GLOBALS["USER"]->IsAuthorized())
				$arSonetFields["USER_ID"] = $GLOBALS["USER"]->GetID();

			$logID = CSocNetLog::Add($arSonetFields, false);
			if (intval($logID) > 0)
			{
				CSocNetLog::Update($logID, array("TMP_ID" => $logID));
				CSocNetLogRights::SetForSonet($logID, $entity_type, $entity_id, "photo", "view", true);
				CSocNetLog::SendEvent($logID, "SONET_NEW_EVENT", $logID);
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
				$arResParams = unserialize($res["PARAMS"]);
			else
				continue;

			if (is_array($arResParams) && in_array($arFields["ID"], $arResParams["arItems"]))
				$arResParams["arItems"] = array_diff($arResParams["arItems"], array($arFields["ID"]));
			else
				continue;

			if (count($arResParams["arItems"]) <= 0)
				CSocNetLog::Delete($res["ID"]);
			else
			{
				$arResParams["COUNT"]--;
				$arSonetFields = array(
					"PARAMS" => serialize($arResParams),
					"LOG_UPDATE" => $res["LOG_UPDATE"]
				);

				CSocNetLog::Update($res["ID"], $arSonetFields);
				CSocNetLogRights::SetForSonet($res["ID"], $entity_type, $entity_id, "photo", "view");
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
			return;
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
				array("ID")
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
					CSocNetLog::Delete($res["ID"]);
			}
		}
		elseif (
			strlen(trim($arComponentResult["SECTION"]["PASSWORD"])) > 0
			&& strlen($arFields["UF_PASSWORD"]) <= 0
		)
		{
			// show photos
		}
		else
			return;
	}

	public static function FormatDestinationFromRights($arRights, $arParams, &$iMoreCount = false)
	{
		if (empty($arRights))
			return array();

		static $arDepartments = array();

		$arDestination = array();
		$arSonetGroups = array();

		$bCheckPermissions = (!array_key_exists("CHECK_PERMISSIONS_DEST", $arParams) || $arParams["CHECK_PERMISSIONS_DEST"] != "N");

		if (!function_exists("__DestinationRightsSort"))
		{
			function __DestinationRightsSort($a, $b)
			{
				if ($a == $b)
					return 0;
				elseif (preg_match('/^US\d+$/', $a))
					return -1;
				elseif (in_array($a, array("G2", "AU")))
				{
					if (in_array($b, array("G2", "AU")))
						return 0;
					elseif (preg_match('/^US\d+$/', $b))
						return 1;
					else
						return -1;
				}
				elseif (preg_match('/^SG\d+_'.SONET_ROLES_USER.'$/', $a))
				{
					if (preg_match('/^SG\d+_'.SONET_ROLES_USER.'$/', $b))
						return 0;
					elseif (
						preg_match('/^US\d+$/', $b)
						|| in_array($b, array("G2", "AU"))
					)
						return 1;
					else
						return -1;
				}
				elseif (preg_match('/^SG\d+_'.SONET_ROLES_MODERATOR.'$/', $a))
				{
					if (preg_match('/^SG\d+_'.SONET_ROLES_MODERATOR.'$/', $b))
						return 0;
					elseif (
						preg_match('/^US\d+$/', $b)
						|| in_array($b, array("G2", "AU"))
						|| preg_match('/^SG\d+_'.SONET_ROLES_USER.'$/', $b)
					)
						return 1;
					else
						return -1;
				}
				elseif (preg_match('/^SG\d+_'.SONET_ROLES_OWNER.'$/', $a))
				{
					if (preg_match('/^SG\d+_'.SONET_ROLES_OWNER.'$/', $b))
						return 0;
					elseif (
						preg_match('/^US\d+$/', $b)
						|| in_array($b, array("G2", "AU"))
						|| preg_match('/^SG\d+_'.SONET_ROLES_USER.'$/', $b)
						|| preg_match('/^SG\d+_'.SONET_ROLES_MODERATOR.'$/', $b)
					)
						return 1;
					else
						return -1;
				}
				elseif (preg_match('/^D\d+$/', $a))
				{
					if (preg_match('/^D\d+$/', $b))
						return 0;
					elseif (
						preg_match('/^US\d+$/', $b)
						|| in_array($b, array("G2", "AU"))
						|| preg_match('/^SG\d+_'.SONET_ROLES_USER.'$/', $b)
						|| preg_match('/^SG\d+_'.SONET_ROLES_MODERATOR.'$/', $b)
						|| preg_match('/^SG\d+_'.SONET_ROLES_OWNER.'$/', $b)
					)
						return 1;
					else
						return -1;
				}
				elseif(preg_match('/^U\d+$/', $a))
				{
					if (preg_match('/^U\d+$/', $b))
						return 0;
					elseif (
						preg_match('/^US\d+$/', $b)
						|| in_array($b, array("G2", "AU"))
						|| preg_match('/^SG\d+_'.SONET_ROLES_USER.'$/', $b)
						|| preg_match('/^SG\d+_'.SONET_ROLES_MODERATOR.'$/', $b)
						|| preg_match('/^SG\d+_'.SONET_ROLES_OWNER.'$/', $b)
						|| preg_match('/^D\d+$/', $b)
					)
						return 1;
					else
						return -1;
				}
				elseif(preg_match('/^G\d+$/', $a))
				{
					if (preg_match('/^G\d+$/', $b))
						return 0;
					elseif (
						preg_match('/^US\d+$/', $b)
						|| in_array($b, array("G2", "AU"))
						|| preg_match('/^SG\d+_'.SONET_ROLES_USER.'$/', $b)
						|| preg_match('/^SG\d+_'.SONET_ROLES_MODERATOR.'$/', $b)
						|| preg_match('/^SG\d+_'.SONET_ROLES_OWNER.'$/', $b)
						|| preg_match('/^D\d+$/', $b)
						|| preg_match('/^U\d+$/', $b)
					)
						return 1;
					else
						return -1;
				}
				else
					return 0;
			}
		}

		$arRights = array_unique($arRights);
		uasort($arRights, "__DestinationRightsSort");

		$cnt = 0;
		$bAll = false;
		$bJustCount = false;
		$arParams["DESTINATION_LIMIT"] = (intval($arParams["DESTINATION_LIMIT"]) <= 0 ? 3 : $arParams["DESTINATION_LIMIT"]);

		foreach($arRights as $right_tmp)
		{
			if ($cnt >= $arParams["DESTINATION_LIMIT"])
				$bJustCount = true;

			if (in_array($right_tmp, array("G1")) && count($arRights) > 1)
				continue;
			elseif (
				preg_match('/^US\d+$/', $right_tmp, $matches)
				|| in_array($right_tmp, array("G2", "AU"))
			)
			{
				if ($bAll)
					continue;

				if (
					isset($arParams["USE_ALL_DESTINATION"])
					&& $arParams["USE_ALL_DESTINATION"]
					&& in_array($right_tmp, array("G2", "AU"))
				)
					continue;

				if (!$bJustCount)
					$arDestination[] = array(
						"STYLE" => "all-users",
						"TITLE" => (IsModuleInstalled("intranet") ? GetMessage("SONET_GL_DESTINATION_G2") : GetMessage("SONET_GL_DESTINATION_G2_BSM"))
					);

				$bAll = true;
				$cnt++;
			}
			elseif (preg_match('/^G(\d+)$/', $right_tmp, $matches))
			{
				$cnt++;
				if (!$bJustCount)
				{
					$rsGroupTmp = CGroup::GetByID($matches[1]);
					if ($arGroupTmp = $rsGroupTmp->Fetch())
						$arDestination[] = array(
							"TYPE" => "G",
							"ID" => $arGroupTmp["ID"],
							"STYLE" => "groups",
							"TITLE" => $arGroupTmp["NAME"],
							"URL" => ""
						);
				}
			}
			elseif (preg_match('/^U(\d+)$/', $right_tmp, $matches))
			{
				if (
					array_key_exists("CREATED_BY", $arParams) 
					&& intval($arParams["CREATED_BY"]) > 0
					&& $arParams["CREATED_BY"] == $matches[1]
				)
					continue;

				$cnt++;
				if (!$bJustCount)
				{
					$rsUserTmp = CUser::GetByID($matches[1]);
					if ($arUserTmp = $rsUserTmp->Fetch())
						$arDestination[] = array(
							"TYPE" => "U",
							"ID" => $arUserTmp["ID"],
							"STYLE" => "users",
							"TITLE" => CUser::FormatName($arParams["NAME_TEMPLATE"], $arUserTmp, ($arParams["SHOW_LOGIN"] == "Y")),
							"URL" => str_replace("#user_id#", $arUserTmp["ID"], $arParams["PATH_TO_USER"])
						);
				}
			}
			elseif (
				(
					preg_match('/^D(\d+)$/', $right_tmp, $matches)
					|| preg_match('/^DR(\d+)$/', $right_tmp, $matches)
				)
				&& CModule::IncludeModule("iblock")
			)
			{
				$cnt++;
				if (!$bJustCount)
				{
					if (array_key_exists($matches[1], $arDepartments))
						$arDepartmentTmp = $arDepartments[$matches[1]];
					else
					{
						$rsDepartmentTmp = CIBLockSection::GetByID($matches[1]);
						if ($arDepartmentTmp = $rsDepartmentTmp->Fetch())
							$arDepartments[$matches[1]] = $arDepartmentTmp;
					}

					if ($arDepartmentTmp)
						$arDestination[] = array(
							"TYPE" => "D",
							"ID" => $arDepartmentTmp["ID"],
							"STYLE" => "department",
							"TITLE" => $arDepartmentTmp["NAME"],
							"URL" => str_replace(array("#ID#", "#id#"), $arDepartmentTmp["ID"], $arParams["PATH_TO_CONPANY_DEPARTMENT"])
						);
				}
			}
			elseif (
				preg_match('/^SG(\d+)_'.SONET_ROLES_USER.'$/', $right_tmp, $matches)
				|| preg_match('/^SG(\d+)$/', $right_tmp, $matches)
			)
			{
				if (
					array_key_exists($matches[1], $arSonetGroups)
					&& is_array($arSonetGroups[$matches[1]])
					&& in_array(SONET_ROLES_USER, $arSonetGroups[$matches[1]])
				)
					continue;

				$cnt++;
				if (!$bJustCount)
				{
					// already cached
					$arSonetGroup = CSocNetGroup::GetByID($matches[1], $bCheckPermissions);
					if ($arSonetGroup)
					{
						$arDestination[] = array(
							"TYPE" => "SG",
							"ID" => $arSonetGroup["ID"],
							"STYLE" => "sonetgroups",
							"TITLE" => $arSonetGroup["NAME"],
							"URL" => str_replace("#group_id#", $arSonetGroup["ID"], $arParams["PATH_TO_GROUP"])
						);

						if (!array_key_exists($arSonetGroup["ID"], $arSonetGroups))
							$arSonetGroups[$arSonetGroup["ID"]] = array();
						$arSonetGroups[$arSonetGroup["ID"]][] = SONET_ROLES_USER;
					}
				}
			}
			elseif (preg_match('/^SG(\d+)_'.SONET_ROLES_MODERATOR.'$/', $right_tmp, $matches))
			{
				if (!in_array("SG".$matches[1]."_".SONET_ROLES_USER, $arRights))
				{
					$cnt++;
					if (!$bJustCount)
					{
						$arSonetGroup = CSocNetGroup::GetByID($matches[1], $bCheckPermissions);
						if ($arSonetGroup)
						{
							$arDestination[] = array(
								"TYPE" => "SG",
								"ID" => $arSonetGroup["ID"],
								"STYLE" => "sonetgroups",
								"TITLE" => $arSonetGroup["NAME"].GetMessage("SONET_GL_DESTINATION_SG_MODERATOR"),
								"URL" => str_replace("#group_id#", $arSonetGroup["ID"], $arParams["PATH_TO_GROUP"])
							);

							if (!array_key_exists($arSonetGroup["ID"], $arSonetGroups))
								$arSonetGroups[$arSonetGroup["ID"]] = array();
							$arSonetGroups[$arSonetGroup["ID"]][] = SONET_ROLES_MODERATOR;
						}
					}
				}
			}
			elseif (preg_match('/^SG(\d+)_'.SONET_ROLES_OWNER.'$/', $right_tmp, $matches))
			{
				if (!in_array("SG".$matches[1]."_".SONET_ROLES_USER, $arRights) && !in_array("SG".$matches[1]."_".SONET_ROLES_MODERATOR, $arRights))
				{
					$cnt++;
					if (!$bJustCount)
					{
						$arSonetGroup = CSocNetGroup::GetByID($matches[1], $bCheckPermissions);
						if ($arSonetGroup)
						{
							$arDestination[] = array(
								"TYPE" => "SG",
								"ID" => $arSonetGroup["ID"],
								"STYLE" => "sonetgroups",
								"TITLE" => $arSonetGroup["NAME"].GetMessage("SONET_GL_DESTINATION_SG_OWNER"),
								"URL" => str_replace("#group_id#", $arSonetGroup["ID"], $arParams["PATH_TO_GROUP"])
							);

							if (!array_key_exists($arSonetGroup["ID"], $arSonetGroups))
								$arSonetGroups[$arSonetGroup["ID"]] = array();
							$arSonetGroups[$arSonetGroup["ID"]][] = SONET_ROLES_OWNER;
						}
					}
				}
			}
		}

		if ($cnt > $arParams["DESTINATION_LIMIT"])
			$iMoreCount = $cnt - $arParams["DESTINATION_LIMIT"];

		return $arDestination;
	}

	public static function GetDestinationFromRights($arRights, $arParams)
	{
		if (empty($arRights))
			return array();

		static $arDepartments = array();

		$arDestination = array();
		$arSonetGroups = array();

		$arRights = array_unique($arRights);

		$bAll = false;
		$arParams["DESTINATION_LIMIT"] = (intval($arParams["DESTINATION_LIMIT"]) <= 0 ? 3 : $arParams["DESTINATION_LIMIT"]);
		$bCheckPermissions = (!array_key_exists("CHECK_PERMISSIONS_DEST", $arParams) || $arParams["CHECK_PERMISSIONS_DEST"] != "N");

		foreach($arRights as $right_tmp)
		{
			if (in_array($right_tmp, array("G1")) && count($arRights) > 1)
				continue;
			elseif (in_array($right_tmp, array("G2", "AU")))
			{
				if ($bAll)
					continue;

				$arDestination[] = $right_tmp;
				$bAll = true;
			}
			elseif (preg_match('/^G(\d+)$/', $right_tmp, $matches))
				$arDestination[] = $matches[1];
			elseif (preg_match('/^U(\d+)$/', $right_tmp, $matches))
			{
				if (
					array_key_exists("CREATED_BY", $arParams) 
					&& intval($arParams["CREATED_BY"]) > 0
					&& $arParams["CREATED_BY"] == $matches[1]
				)
					continue;

				$arDestination[] = $right_tmp;
			}
			elseif (
				preg_match('/^D(\d+)$/', $right_tmp, $matches)
				|| preg_match('/^DR(\d+)$/', $right_tmp, $matches)
			)
				$arDestination[] = $right_tmp;
			elseif (
				preg_match('/^SG(\d+)_'.SONET_ROLES_USER.'$/', $right_tmp, $matches)
				|| preg_match('/^SG(\d+)$/', $right_tmp, $matches)
			)
			{
				if (
					array_key_exists($matches[1], $arSonetGroups)
					&& is_array($arSonetGroups[$matches[1]])
					&& in_array(SONET_ROLES_USER, $arSonetGroups[$matches[1]])
				)
					continue;

				// already cached
				$arSonetGroup = CSocNetGroup::GetByID($matches[1], $bCheckPermissions);
				if ($arSonetGroup)
				{
					$arDestination[] = "SG".$matches[1];

					if (!array_key_exists($arSonetGroup["ID"], $arSonetGroups))
						$arSonetGroups[$arSonetGroup["ID"]] = array();
					$arSonetGroups[$arSonetGroup["ID"]][] = SONET_ROLES_USER;
				}
			}
			elseif (preg_match('/^SG(\d+)_'.SONET_ROLES_MODERATOR.'$/', $right_tmp, $matches))
			{
				if (!in_array("SG".$matches[1]."_".SONET_ROLES_USER, $arRights))
				{
					$arSonetGroup = CSocNetGroup::GetByID($matches[1], $bCheckPermissions);
					if ($arSonetGroup)
					{
						$arDestination[] = "SG".$matches[1];

						if (!array_key_exists($arSonetGroup["ID"], $arSonetGroups))
							$arSonetGroups[$arSonetGroup["ID"]] = array();
						$arSonetGroups[$arSonetGroup["ID"]][] = SONET_ROLES_MODERATOR;
					}
				}
			}
			elseif (preg_match('/^SG(\d+)_'.SONET_ROLES_OWNER.'$/', $right_tmp, $matches))
			{
				if (
					!in_array("SG".$matches[1]."_".SONET_ROLES_USER, $arRights) 
					&& !in_array("SG".$matches[1]."_".SONET_ROLES_MODERATOR, $arRights)
				)
				{
					$arSonetGroup = CSocNetGroup::GetByID($matches[1], $bCheckPermissions);
					if ($arSonetGroup)
					{
						$arDestination[] = "SG".$matches[1];

						if (!array_key_exists($arSonetGroup["ID"], $arSonetGroups))
							$arSonetGroups[$arSonetGroup["ID"]] = array();
						$arSonetGroups[$arSonetGroup["ID"]][] = SONET_ROLES_OWNER;
					}
				}
			}
		}

		return $arDestination;
	}

	public static function ShowSourceType($source_type = false, $bMobile = false)
	{
		if (!$source_type)
			return false;
		else
		{
			$events = GetModuleEvents("socialnetwork", "OnShowSocNetSourceType");
			while ($arEvent = $events->Fetch())
			{
				$arResult = ExecuteModuleEventEx($arEvent, array($source_type, $bMobile));
				if (is_array($arResult))
					return $arResult;
			}
		}
	}

	public static function GetDataFromRatingEntity($rating_entity_type_id, $rating_entity_id)
	{
		$rating_entity_type_id = preg_replace("/[^a-z0-9_-]/i", "", $rating_entity_type_id);
		$rating_entity_id = intval($rating_entity_id);
		
		if (strlen($rating_entity_type_id) <= 0)
			return false;
			
		if ($rating_entity_id <= 0)
			return false;
			
		switch ($rating_entity_type_id)
		{
			case "BLOG_POST":
				$log_type = "log";
				$log_event_id = array("blog_post");
				break;
			case "BLOG_COMMENT":
				$log_type = "comment";
				$log_event_id = array("blog_comment", "photo_comment");
				break;
			case "FORUM_TOPIC":
				$log_type = "log";
				$log_event_id = array("forum");
				break;
			case "FORUM_POST":
				$log_type = "comment";
				$log_event_id = array("forum", "photo_comment", "files_comment", "commondocs_comment", "tasks_comment", "wiki_comment");
				break;
			case "IBLOCK_ELEMENT":
				$log_type = "log";
				$log_event_id = array("photo_photo", "files", "commondocs", "wiki");
				break;
			case "INTRANET_NEW_USER":
				$log_type = "log";
				$log_event_id = array("intranet_new_user");
				break;
			case "INTRANET_NEW_USER_COMMENT":
				$log_type = "comment";
				$log_event_id = array("intranet_new_user_comment");
				break;
			case "BITRIX24_NEW_USER":
				$log_type = "log";
				$log_event_id = array("bitrix24_new_user");
				break;
			case "BITRIX24_NEW_USER_COMMENT":
				$log_type = "comment";
				$log_event_id = array("bitrix24_new_user_comment");
				break;
			default:
		}

		if ($log_type == "log")
		{
			$rsLogSrc = CSocNetLog::GetList(
				array(),
				array(
					"EVENT_ID" => $log_event_id,
					"SOURCE_ID" => $rating_entity_id
				),
				false,
				false,
				array("ID"),
				array(
					"CHECK_RIGHTS" => "Y",
					"USE_SUBSCRIBE" => "N"
				)
			);
			if ($arLogSrc = $rsLogSrc->Fetch())
				$log_id = $arLogSrc["ID"];
		}
		elseif ($log_type == "comment")
		{
			$rsLogCommentSrc = CSocNetLogComments::GetList(
				array(),
				array(
					"EVENT_ID" => $log_event_id,
					"SOURCE_ID" => $rating_entity_id
				),
				false,
				false,
				array("ID", "LOG_ID"),
				array(
					"CHECK_RIGHTS" => "Y",
					"USE_SUBSCRIBE" => "N"
				)
			);
			if ($arLogCommentSrc = $rsLogCommentSrc->Fetch())
			{
				$log_id = $arLogCommentSrc["LOG_ID"];
				$log_comment_id = $arLogCommentSrc["ID"];
			}
		}
		
		if ($log_id > 0)
		{
			$arResult = array("LOG_ID" => $log_id);
			if ($log_comment_id > 0)
				$arResult["LOG_COMMENT_ID"] = $log_comment_id;

			return $arResult;
		}
		else
			return false;
	}
}

class CSocNetPhotoCommentEvent
{
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
				array("ID", "ENTITY_TYPE", "ENTITY_ID", "TMP_ID")
			);

			if ($arRes = $dbRes->Fetch())
			{
				$log_id = $arRes["ID"];
				$entity_type = $arRes["ENTITY_TYPE"];
				$entity_id = $arRes["ENTITY_ID"];
				$bSocNetLogRecordExists = true;
			}
			else
			{
				$rsElement = CIBlockElement::GetByID($arFields["PARAM2"]);
				if ($arElement = $rsElement->Fetch())
				{
					$url = $this->arPath["DETAIL_URL"];

					$sAuthorName = GetMessage("SONET_LOG_GUEST");
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

						if (!$alias)
						{
							$arSectionPath = array();
							$rsPath = CIBlockSection::GetNavChain($arLogParams["IBLOCK_ID"], intval($arLogParams["SECTION_ID"]));
							if($arPath = $rsPath->Fetch())
							{
								$entity_type = SONET_ENTITY_USER;
								$entity_id = $arPath["CREATED_BY"];
								$alias = $arPath["CODE"];
							}
						}

						if (!$arSiteWorkgroupsPage && IsModuleInstalled("extranet") && $entity_type == SONET_ENTITY_GROUP)
						{
							$rsSite = CSite::GetList($by="sort", $order="desc", Array("ACTIVE" => "Y"));
							while($arSite = $rsSite->Fetch())
								$arSiteWorkgroupsPage[$arSite["ID"]] = COption::GetOptionString("socialnetwork", "workgroup_page", $arSite["DIR"]."workgroups/", $arSite["ID"]);
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
						"RATING_TYPE_ID" 	=> "IBLOCK_ELEMENT",
						"RATING_ENTITY_ID"=> $arElement["ID"],
					);

					if (intval($arElement["CREATED_BY"]) > 0)
						$arSonetFields["USER_ID"] = $arElement["CREATED_BY"];

					$log_id = CSocNetLog::Add($arSonetFields, false);
					if (intval($log_id) > 0)
					{
						CSocNetLog::Update($log_id, array("TMP_ID" => $log_id));
						CSocNetLogRights::SetForSonet($log_id, $entity_type, $entity_id, "photo", "view");
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
					"SMILES" => "N"
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

					CSocNetLogComments::Add($arFieldsForSocnet);
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
							$arFieldsForSocnet["USER_ID"] = $arComment["AUTHOR_ID"];

						CSocNetLogComments::Add($arFieldsForSocnet);
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
				array("ID", "ENTITY_TYPE", "ENTITY_ID", "TMP_ID")
			);

			$bSocNetLogRecordExists = false;
			if ($arRes = $dbRes->Fetch())
			{
				$log_id = $arRes["ID"];
				$entity_type = $arRes["ENTITY_TYPE"];
				$entity_id = $arRes["ENTITY_ID"];
				$bSocNetLogRecordExists = true;
			}
			else
			{
				$rsElement = CIBlockElement::GetByID($this->PhotoElementID);
				if ($arElement = $rsElement->Fetch())
				{
					$url = $this->arPath["DETAIL_URL"];

					$sAuthorName = GetMessage("SONET_LOG_GUEST");
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

						if (!$alias)
						{
							$arSectionPath = array();
							$rsPath = CIBlockSection::GetNavChain($arLogParams["IBLOCK_ID"], intval($arLogParams["SECTION_ID"]));
							if($arPath = $rsPath->Fetch())
							{
								$entity_type = SONET_ENTITY_USER;
								$entity_id = $arPath["CREATED_BY"];
								$alias = $arPath["CODE"];
							}
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
						CSocNetLog::Update($log_id, array("TMP_ID" => $log_id));
						CSocNetLogRights::SetForSonet($log_id, $entity_type, $entity_id, "photo", "view", true);
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

					CSocNetLogComments::Add($arFieldsForSocnet);
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

						CSocNetLogComments::Add($arFieldsForSocnet);
					}
				}
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
}

class logTextParser extends CTextParser
{
	public function logTextParser($strLang = False, $pathToSmile = false)
	{
		$this->CTextParser();
		global $CACHE_MANAGER;

		$this->MaxStringLen = 0;
		$this->smiles = array();
		if ($strLang === False)
			$strLang = LANGUAGE_ID;
		$this->pathToSmile = $pathToSmile;

		if($CACHE_MANAGER->Read(604800, "b_sonet_smile"))
			$arSmiles = $CACHE_MANAGER->Get("b_sonet_smile");
		else
		{
			$db_res = CSocNetSmile::GetList(array("SORT" => "ASC"), array("SMILE_TYPE" => "S"/*, "LANG_LID" => $strLang*/), false, false, Array("LANG_LID", "ID", "IMAGE", "DESCRIPTION", "TYPING", "SMILE_TYPE", "SORT"));
			while ($res = $db_res->Fetch())
			{
				$tok = strtok($res['TYPING'], " ");
				while ($tok !== false)
				{
					$arSmiles[$res['LANG_LID']][] = array(
						'TYPING' => $tok,
						'IMAGE'  => stripslashes($res['IMAGE']), // stripslashes is not needed here
						'DESCRIPTION' => stripslashes($res['NAME']) // stripslashes is not needed here
					);
					$tok = strtok(" ");
				}
			}

			function sonet_sortlen($a, $b) {
				if (strlen($a["TYPING"]) == strlen($b["TYPING"]))
					return 0;

				return (strlen($a["TYPING"]) > strlen($b["TYPING"])) ? -1 : 1;
			}

			foreach ($arSmiles as $LID => $arSmilesLID)
			{
				uasort($arSmilesLID, 'sonet_sortlen');
				$arSmiles[$LID] = $arSmilesLID;
			}

			$CACHE_MANAGER->Set("b_sonet_smile", $arSmiles);
		}
		$this->smiles = $arSmiles[$strLang];
	}

	public function convert($text, $arImages = array(), $allow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "TABLE" => "Y", "CUT_ANCHOR" => "N", "SHORT_ANCHOR" => "N"), $arParams = Array())
	{
		$this->allow = array(
			"HTML" => ($allow["HTML"] == "Y" ? "Y" : "N"),
			"NL2BR" => ($allow["NL2BR"] == "Y" ? "Y" : "N"),
			"MULTIPLE_BR" => ($allow["MULTIPLE_BR"] == "N" ? "N" : "Y"),
			"CODE" => ($allow["CODE"] == "N" ? "N" : "Y"),
			"LOG_CODE" => ($allow["LOG_CODE"] == "N" ? "N" : "Y"),
			"VIDEO" => ($allow["VIDEO"] == "N" ? "N" : "Y"),
			"LOG_VIDEO" => ($allow["LOG_VIDEO"] == "N" ? "N" : "Y"),
			"ANCHOR" => ($allow["ANCHOR"] == "N" ? "N" : "Y"),
			"LOG_ANCHOR" => ($allow["LOG_ANCHOR"] == "N" ? "N" : "Y"),
			"BIU" => ($allow["BIU"] == "N" ? "N" : "Y"),
			"IMG" => ($allow["IMG"] == "N" ? "N" : "Y"),
			"LOG_IMG" => ($allow["LOG_IMG"] == "N" ? "N" : "Y"),
			"QUOTE" => ($allow["QUOTE"] == "N" ? "N" : "Y"),
			"LOG_QUOTE" => ($allow["LOG_QUOTE"] == "N" ? "N" : "Y"),
			"FONT" => ($allow["FONT"] == "N" ? "N" : "Y"),
			"LOG_FONT" => ($allow["LOG_FONT"] == "N" ? "N" : "Y"),
			"LIST" => ($allow["LIST"] == "N" ? "N" : "Y"),
			"SMILES" => ($allow["SMILES"] == "N" ? "N" : "Y"),
			"TABLE" => ($allow["TABLE"] == "N" ? "N" : "Y"),
			"ALIGN" => ($allow["ALIGN"] == "N" ? "N" : "Y"),
			"CUT_ANCHOR" => ($allow["CUT_ANCHOR"] == "Y" ? "Y" : "N"),
			"SHORT_ANCHOR" => ($allow["SHORT_ANCHOR"] == "Y" ? "Y" : "N"),
			"HEADER" => ($allow["HEADER"] == "N" ? "N" : "Y")
		);

		if ($this->allow["HTML"] != "Y")
		{
			$text = preg_replace("#(<br[\s]*\/>)#is".BX_UTF_PCRE_MODIFIER, "", $text);

			$text = preg_replace(
				array(
					"#<a[^>]+href\s*=\s*('|\")(.+?)(?:\\1)[^>]*>(.*?)</a[^>]*>#is".BX_UTF_PCRE_MODIFIER,
					"#<a[^>]+href(\s*=\s*)([^'\"\>])+>(.*?)</a[^>]*>#is".BX_UTF_PCRE_MODIFIER),
				"[url=\\2]\\3[/url]", $text);

			$replaced = 0;
			do
			{
				$text = preg_replace(
					"/<([busi])[^>a-z]*>(.+?)<\\/(\\1)[^>a-z]*>/is".BX_UTF_PCRE_MODIFIER,
					"[\\1]\\2[/\\1]",
				$text, -1, $replaced);
			}
			while($replaced > 0);

			$text = preg_replace(
				"#<img[^>]+src\s*=[\s'\"]*(((http|https|ftp)://[.-_:a-z0-9@]+)*(\/[-_/=:.a-z0-9@{}&?]+)+)[\s'\"]*[^>]*>#is".BX_UTF_PCRE_MODIFIER,
				"[img]\\1[/img]", $text);

			$text = preg_replace(
				array(
					"/\<font[^>]+size\s*=[\s'\"]*([0-9]+)[\s'\"]*[^>]*\>(.+?)\<\/font[^>]*\>/is".BX_UTF_PCRE_MODIFIER,
					"/\<font[^>]+color\s*=[\s'\"]*(\#[a-f0-9]{6})[^>]*\>(.+?)\<\/font[^>]*>/is".BX_UTF_PCRE_MODIFIER,
					"/\<font[^>]+face\s*=[\s'\"]*([a-z\s\-]+)[\s'\"]*[^>]*>(.+?)\<\/font[^>]*>/is".BX_UTF_PCRE_MODIFIER),
				array(
					"[size=\\1]\\2[/size]",
					"[color=\\1]\\2[/color]",
					"[font=\\1]\\2[/font]"),
				$text);

			$text = preg_replace(
				array(
					"/\<ul((\s[^>]*)|(\s*))\>(.+?)<\/ul([^>]*)\>/is".BX_UTF_PCRE_MODIFIER,
					"/\<ol((\s[^>]*)|(\s*))\>(.+?)<\/ol([^>]*)\>/is".BX_UTF_PCRE_MODIFIER,
					"/\<li((\s[^>]*)|(\s*))\>/is".BX_UTF_PCRE_MODIFIER,
					),
				array(
					"[list]\\4[/list]",
					"[list=1]\\4[/list]",
					"[*]",
					),
				$text);

			$text = preg_replace(
				array(
					"/\<table((\s[^>]*)|(\s*))\>(.+?)<\/table([^>]*)\>/is".BX_UTF_PCRE_MODIFIER,
					"/\<tr((\s[^>]*)|(\s*))\>(.*?)<\/tr([^>]*)\>/is".BX_UTF_PCRE_MODIFIER,
					"/\<td((\s[^>]*)|(\s*))\>(.*?)<\/td([^>]*)\>/is".BX_UTF_PCRE_MODIFIER,
					),
				array(
					"[table]\\4[/table]",
					"[tr]\\4[/tr]",
					"[td]\\4[/td]",
					),
				$text);

			if ($this->allow["QUOTE"]=="Y")
				$text = preg_replace("#<(/?)quote(.*?)>#is", "[\\1quote]", $text);

		}
		if ($this->allow["LOG_IMG"] == "N")
			$text = preg_replace("/(\[file([^\]]*)id\s*=\s*([0-9]+)([^\]]*)\])/is", "", $text);

		$text = str_replace("<br />", "\n", $text);
		$text = $this->convertText($text);
		$text = str_replace("\n", "<br />", $text);

		$text = preg_replace("#^(<br[\s]*\/>[\s\n]*)+#is".BX_UTF_PCRE_MODIFIER, "", $text);
		$text = preg_replace("#(<br[\s]*\/>[\s\n]*)+$#is".BX_UTF_PCRE_MODIFIER, "", $text);

		if ($this->allow["MULTIPLE_BR"] == "N")
			$text = preg_replace("#(<br[\s]*\/>[\s\n]*)+#is".BX_UTF_PCRE_MODIFIER, "<br />", $text);

		return trim($text);
	}

	public function convert_anchor_tag($url, $text, $pref="")
	{
		if ($this->allow["LOG_ANCHOR"] == "N")
			return "[URL]".$text."[/URL]";
		else
			return parent::convert_anchor_tag($url, $text, $pref);
	}

	public function convert_image_tag($url = "", $params = "")
	{
		if ($this->allow["LOG_IMG"] == "N")
		{
// use thumbnail?
			return "";
		}
		else
			return parent::convert_image_tag($url, $params);
	}

	public function pre_convert_code_tag ($text = "")
	{
		if (strLen($text)<=0) return;

		$text = str_replace("\\\"", "\"", $text);

		$word_separator = str_replace("\]", "", $this->word_separator);
		$text = preg_replace("'(?<=^|[".$word_separator."]|\s)((http|https|news|ftp|aim|mailto)://[\.\-\_\:a-z0-9\@]([^\s\'\"\[\]\{\}])*)'is",
			"[nomodify]\\1[/nomodify]", $text);

		return $text;
	}

	public function convert_code_tag($text = "")
	{
		$text = preg_replace("#(<br[\s]*\/>)#is".BX_UTF_PCRE_MODIFIER, "", $text);
		if ($this->allow["LOG_CODE"] == "N")
		{
			$text = str_replace(Array("[nomodify]", "[/nomodify]"), Array("", ""), $text);
			return $text;
		}
		else
			return parent::convert_code_tag($text);
	}

	public function convert_quote_tag($text = "")
	{
		if ($this->allow["LOG_QUOTE"] == "N")
			return preg_replace(
				array(
					"/\[quote([^\]\<\>])*\]/ie".BX_UTF_PCRE_MODIFIER,
					"/\[\/quote([^\]\<\>])*\]/ie".BX_UTF_PCRE_MODIFIER,
				),
				"",
			$text);
		else
			return parent::convert_quote_tag($text);
	}

	public function convert_font_attr($attr, $value = "", $text = "")
	{
		if (strlen($text)<=0) return "";
		$text = str_replace("\\\"", "\"", $text);
		if (strlen($value)<=0) return $text;

		if ($this->allow["LOG_FONT"] == "N")
		{
			return $text;
		}
		else
			return parent::convert_font_attr($attr, $value, $text);
	}

	public function convert_video($params, $path)
	{
		if (strLen($path) <= 0)
			return "";

		if ($this->allow["LOG_VIDEO"] == "N")
			return '<a href="'.$path.'">'.$path.'</a>';
		else
			return parent::convert_video($params, $path);
	}
}
?>