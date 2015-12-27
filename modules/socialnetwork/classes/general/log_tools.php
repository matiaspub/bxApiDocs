<?
IncludeModuleLangFile(__FILE__);

class CSocNetLogTools
{
	public static function FindFeatureByEventID($event_id)
	{
		$feature = false;

		$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();
		foreach ($arSocNetFeaturesSettings as $feature_tmp => $arFeature)
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
		$arSocNetLogEvents = CSocNetAllowed::GetAllowedLogEvents();

		if (
			array_key_exists($event_id, $arSocNetLogEvents)
			&& array_key_exists("ENTITIES", $arSocNetLogEvents[$event_id])
		)
		{
			if (
				!$entity_type
				|| ($entity_type && array_key_exists($entity_type, $arSocNetLogEvents[$event_id]["ENTITIES"]))
			)
			{
				$arEvent = $arSocNetLogEvents[$event_id];
			}
		}

		if (!$arEvent)
		{
			$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();
			foreach($arSocNetFeaturesSettings as $feature => $arFeature)
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
		$arSocNetLogEvents = CSocNetAllowed::GetAllowedLogEvents();

		foreach($arSocNetLogEvents as $event_id_tmp => $arEventTmp)
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
			$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();
			foreach($arSocNetFeaturesSettings as $feature => $arFeature)
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
		$arSocNetLogEvents = CSocNetAllowed::GetAllowedLogEvents();

		if (
			array_key_exists($log_event_id, $arSocNetLogEvents)
			&& array_key_exists("COMMENT_EVENT", $arSocNetLogEvents[$log_event_id])
		)
		{
			$arEvent = $arSocNetLogEvents[$log_event_id]["COMMENT_EVENT"];
		}
		else
		{
			$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();
			foreach ($arSocNetFeaturesSettings as $feature_id_tmp => $arFeatureTmp)
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
		$arSocNetLogEvents = CSocNetAllowed::GetAllowedLogEvents();

		foreach ($arSocNetLogEvents as $event_id_tmp => $arEventTmp)
		{
			if (
				array_key_exists("COMMENT_EVENT", $arEventTmp)
				&& isset($arEventTmp["COMMENT_EVENT"]["EVENT_ID"])
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
			$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();
			foreach ($arSocNetFeaturesSettings as $feature_id_tmp => $arFeatureTmp)
			{
				if (array_key_exists("subscribe_events", $arFeatureTmp))
				{
					foreach ($arFeatureTmp["subscribe_events"] as $event_id_tmp => $arEventTmp)
					{
						if (
							array_key_exists("COMMENT_EVENT", $arEventTmp)
							&& isset($arEventTmp["COMMENT_EVENT"]["EVENT_ID"])
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
		$arSocNetLogEvents = CSocNetAllowed::GetAllowedLogEvents();

		foreach ($arSocNetLogEvents as $event_id_tmp => $arEventTmp)
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
			$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();
			foreach($arSocNetFeaturesSettings as $arFeatureTmp)
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
		$arSocNetLogEvents = CSocNetAllowed::GetAllowedLogEvents();

		foreach ($arSocNetLogEvents as $event_id_tmp => $arEventTmp)
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
			$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();
			foreach ($arSocNetFeaturesSettings as $feature_id_tmp => $arFeatureTmp)
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
		if (
			isset($arParams["AVATAR_SIZE_COMMON"])
			&& intval($arParams["AVATAR_SIZE_COMMON"]) > 0
		)
		{
			$arParams["AVATAR_SIZE"] = intval($arParams["AVATAR_SIZE_COMMON"]);
		}
		elseif (intval($arParams["AVATAR_SIZE"]) <= 0)
		{
			$arParams["AVATAR_SIZE"] = 30;
		}

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
			$arCreatedBy = array();
			if (
				is_array($GLOBALS["arExtranetUserID"]) 
				&& in_array($arFields["USER_ID"], $GLOBALS["arExtranetUserID"]) 
			)
			{
				$arCreatedBy["IS_EXTRANET"] = "Y";
				$suffix = (SITE_TEMPLATE_ID != "bitrix24" ? GetMessage("SONET_LOG_EXTRANET_SUFFIX") : "");
			}
			else
			{
				$arCreatedBy["IS_EXTRANET"] = "N";
			}

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

				$arSocNetAllowedSubscribeEntityTypesDesc = CSocNetAllowed::GetAllowedEntityTypesDesc();
				$arEntity["FORMATTED"]["TYPE_NAME"] = $arSocNetAllowedSubscribeEntityTypesDesc[$arFields["ENTITY_TYPE"]]["TITLE_ENTITY"];

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
		{
			$arSiteServerName = array();
		}

		if (strlen($arFields["URL"]) > 0)
		{
			if (
				!$bAbsolute
				&& (
					strpos($arFields["URL"], "http://") === 0
					|| strpos($arFields["URL"], "https://") === 0
				)
			)
			{
				$bAbsolute = true;
			}

			if (!$bAbsolute)
			{
				if (
					$arFields["ENTITY_TYPE"] == SONET_ENTITY_GROUP 
					&& CModule::IncludeModule("extranet")
				)
				{
					$server_name = "#SERVER_NAME#";
				}
				else
				{
					$rsLogSite = CSocNetLog::GetSite($arFields["ID"]);
					if($arLogSite = $rsLogSite->Fetch())
					{
						$siteID = $arLogSite["LID"];
					}

					if (in_array($siteID, $arSiteServerName))
					{
						$server_name = $arSiteServerName[$siteID];
					}
					else
					{
						$rsSites = CSite::GetByID($siteID);
						$arSite = $rsSites->Fetch();
						$server_name = (strlen($arSite["SERVER_NAME"]) > 0 ? $arSite["SERVER_NAME"] : COption::GetOptionString("main", "server_name", $GLOBALS["SERVER_NAME"]));
						$arSiteServerName[$siteID] = $server_name;
					}
				}

				$protocol = (CMain::IsHTTPS() ? "https" : "http");
				$url = $protocol."://".$server_name.$arFields["URL"];
			}
			else
			{
				$url = $arFields["URL"];
			}
		}

		return $url;
	}

	public static function FormatEvent_Blog($arFields, $arParams, $bMail = false)
	{
		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
		{
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);
		}

		$arResult = array(
			"EVENT" => $arFields,
			"CREATED_BY" => CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail),
			"ENTITY" => CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, $bMail),
			"EVENT_FORMATTED"	=> array()
		);
		$arResult["CREATED_BY"]["ACTION_TYPE"] = "wrote";

		if (!$bMail)
		{
			$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams);
		}

		if ($bMail)
		{
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_BLOG_POST_MAIL");

			//if the title duplicates message, don't show it
			$arFields["TITLE"] = (
				strpos($arFields["MESSAGE"], $arFields["TITLE"]) === 0
					? ""
					: ' "'.$arFields["TITLE"].'"'
			);
		}
		else
		{
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_BLOG_POST");
		}

		$post_tmp = (
			!$bMail
			&& array_key_exists("URL", $arFields)
			&& strlen($arFields["URL"]) > 0
				? '<a href="'.$arFields["URL"].'">'.$arFields["TITLE"].'</a>'
				: $arFields["TITLE"]
		);

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
			"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : $arFields["~MESSAGE"])
		);

		if (!$bMail)
		{
			if (
				$arParams["NEW_TEMPLATE"] != "Y"
				|| $arFields["EVENT_ID"] == "idea"
			)
			{
				if (CModule::IncludeModule("blog"))
				{
					$parserLog = new blogTextParser(false, $arParams["PATH_TO_SMILE"]);
					$arImages = array();

					$arBlogPost = CBlogPost::GetByID($arFields["SOURCE_ID"]);
					if($arBlogPost["HAS_IMAGES"] != "N")
					{
						$res = CBlogImage::GetList(array("ID"=>"ASC"),array("POST_ID"=>$arBlogPost['ID'], "IS_COMMENT" => "N"));
						while ($arImage = $res->Fetch())
						{
							$arImages[$arImage['ID']] = $arImage['FILE_ID'];
						}
					}
				}
				else
				{
					$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
				}

				$arAllow = array(
					"HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", 
					"IMG" => "Y",
					"QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "MULTIPLE_BR" => "N", "VIDEO" => "Y", "LOG_VIDEO" => "N", "SHORT_ANCHOR" => "Y"
				);

				if (get_class($parserLog) == "blogTextParser")
				{
					$arResult["EVENT_FORMATTED"]["MESSAGE"] = $parserLog->html_cut(
						$parserLog->convert(
							htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]),
							true,
							$arImages, 
							$arAllow
						),
						10000
					);
				}
				else
				{
					$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx(
						$parserLog->convert(
							htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), 
							array(), 
							$arAllow
						)
					);
				}
				

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
			{
				$arRights[] = $arRight["GROUP_CODE"];
			}

			if ($arParams["MOBILE"] == "Y")
			{					
				$arResult["EVENT_FORMATTED"]["DESTINATION"] = CSocNetLogTools::FormatDestinationFromRights($arRights, array_merge($arParams, array("CREATED_BY" => $arFields["USER_ID"], "USE_ALL_DESTINATION" => true)), $iMoreCount);
				if (intval($iMoreCount) > 0)
				{
					$arResult["EVENT_FORMATTED"]["DESTINATION_MORE"] = $iMoreCount;
				}

			}
			else
			{
				$arResult["EVENT_FORMATTED"]["DESTINATION_CODE"] = CSocNetLogTools::GetDestinationFromRights($arRights, array_merge($arParams, array("CREATED_BY" => $arFields["USER_ID"])));
			}
		}
		else
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arFields);
			if (strlen($url) > 0)
			{
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
			}
		}

		$arResult["HAS_COMMENTS"] = (intval($arFields["SOURCE_ID"]) > 0 ? "Y" : "N");

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
		{
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);
		}

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

			$arAllow = array(
				"HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", 
				"IMG" => "Y", 
				"QUOTE" => "Y", 
				"CODE" => "Y", 
				"FONT" => "Y", 
				"LIST" => "Y", 
				"SMILES" => "Y", 
				"NL2BR" => "N", 
				"VIDEO" => "Y", 
				"LOG_VIDEO" => "N",
				"USERFIELDS" => $arFields["UF"],
				"USER" => "Y"
			);
			$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));

			$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);
		}
		else
		{
			$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
			$parserLog->pathToUser = $arParams["PATH_TO_USER"];

			$arAllow = array(
				"HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y", 
				"IMG" => "Y", 
				"QUOTE" => "Y", 
				"CODE" => "Y", 
				"FONT" => "Y", 
				"LIST" => "Y", 
				"SMILES" => "Y", 
				"NL2BR" => "Y", 
				"VIDEO" => "Y", 
				"LOG_VIDEO" => "N",
				"USERFIELDS" => $arFields["UF"],
				"USER" => "Y"
			);
			$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));
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
		{
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);
		}

		$arResult = array(
			"EVENT" => $arFields,
			"CREATED_BY" => CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail),
			"ENTITY" => CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, $bMail),
			"EVENT_FORMATTED" => array(),
		);

		if (!$bMail)
		{
			$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams);
		}

		if ($arFields["PARAMS"] == "type=M")
		{
			$title_tmp = (
				$bMail
					? GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_FORUM_MESSAGE_MAIL")
					: GetMessage("SONET_GL_EVENT_TITLE_FORUM_MESSAGE")
			);
		}
		else
		{
			$title_tmp = (
				$bMail
					? GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_FORUM_TOPIC_MAIL")
					: GetMessage("SONET_GL_EVENT_TITLE_FORUM_TOPIC")
			);
		}

		$topic_tmp = (
			!$bMail
			&& array_key_exists("URL", $arFields)
			&& strlen($arFields["URL"]) > 0
				? '<a href="'.$arFields["URL"].'">'.$arFields["TITLE"].'</a>'
				: $arFields["TITLE"]
		);

		$title = str_replace(
			array("#TITLE#", "#ENTITY#", "#CREATED_BY#"),
			array($topic_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
			$title_tmp
		);

		static $parser = false;
		if (CModule::IncludeModule("forum"))
		{
			if (!$parser)
			{
				$parser = new forumTextParser(LANGUAGE_ID);
			}

			$parser->pathToUser = $arParams["PATH_TO_USER"];
			$parser->LAZYLOAD = (isset($arParams["LAZYLOAD"]) && $arParams["LAZYLOAD"] == "Y" ? "Y" : "N");
			$parser->bMobile = ($arParams["MOBILE"] == "Y");

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
			static $parserLog = false;
			if ($arParams["MOBILE"] != "Y") 
			{
				if (!$parserLog)
				{
					$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
				}

				$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
					$parserLog->convert(
						str_replace("#CUT#", "", $arResult["EVENT_FORMATTED"]["MESSAGE"]),
						array(),
						array(
							"HTML" => "Y",
							"ALIGN" => "Y",
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
			if (get_class($parser) == "forumTextParser")
			{
				$parser->arUserfields = $arFields["UF"];
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = $parser->convert(
					$arResult["EVENT_FORMATTED"]["MESSAGE"],
					array(
						"HTML" => "N",
						"ALIGN" => "Y",
						"ANCHOR" => "Y", "BIU" => "Y",
						"IMG" => "Y", "QUOTE" => "Y",
						"CODE" => "Y", "FONT" => "Y",
						"LIST" => "Y", "SMILES" => "Y",
						"NL2BR" => "Y", "MULTIPLE_BR" => "N",
						"VIDEO" => "Y", "LOG_VIDEO" => "N",
						"SHORT_ANCHOR" => "Y",
						"USERFIELDS" => $arFields["UF"]
					),
					"html",
					$arResult["EVENT_FORMATTED"]["FILES"]);
				$arResult["EVENT_FORMATTED"]["PARSED_FILES"] = $parser->arFilesParsed;
			}
			else
			{
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = $parser->convert(
					$arResult["EVENT_FORMATTED"]["MESSAGE"],
					array(),
					array(
						"HTML" => "N",
						"ALIGN" => "Y",
						"ANCHOR" => "Y", "BIU" => "Y",
						"IMG" => "Y", "QUOTE" => "Y",
						"CODE" => "Y", "FONT" => "Y",
						"LIST" => "Y", "SMILES" => "Y",
						"NL2BR" => "Y", "MULTIPLE_BR" => "N",
						"VIDEO" => "Y", "LOG_VIDEO" => "N",
						"SHORT_ANCHOR" => "Y",
						"USERFIELDS" => $arFields["UF"]
					)
				);
			}

			$arResult["EVENT_FORMATTED"]["MESSAGE"] = str_replace(
				"#CUT#",
				'<br><a href="'.$arFields["URL"].'">'.GetMessage("SONET_GL_EVENT_BLOG_MORE").'</a>',
				htmlspecialcharsbx($arResult["EVENT_FORMATTED"]["MESSAGE"])
			);

			if ($arParams["MOBILE"] != "Y" && $arParams["NEW_TEMPLATE"] != "Y")
				$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort(
					$arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);

			if ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP)
			{
				$arResult["EVENT_FORMATTED"]["DESTINATION"] = array(
					array(
						"STYLE" => "sonetgroups",
						"TITLE" => $arResult["ENTITY"]["FORMATTED"]["NAME"],
						"URL" => $arResult["ENTITY"]["FORMATTED"]["URL"],
						"IS_EXTRANET" => (is_array($GLOBALS["arExtranetGroupID"]) && in_array($arFields["ENTITY_ID"], $GLOBALS["arExtranetGroupID"]))
					)
				);
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

	public static function FormatComment_Forum($arFields, $arParams, $bMail = false, $arLog = array())
	{
		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
		{
			IncludeModuleLangFile(__FILE__, $arFields["MAIL_LANGUAGE_ID"]);
		}

		$arResult = array(
			"EVENT_FORMATTED"	=> array(),
		);

		if ($bMail)
		{
			$arResult["CREATED_BY"] = CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail);
			$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arLog, $arParams, $bMail);
		}
		elseif($arParams["USE_COMMENT"] != "Y")
		{
			$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, false);
		}

		$title_tmp = (
			$bMail
				? GetMessage("SONET_GL_EVENT_TITLE_".($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_FORUM_MESSAGE_MAIL")
				: GetMessage("SONET_GL_EVENT_TITLE_FORUM_MESSAGE")
		);

		$topic_tmp = (
			!$bMail
			&& array_key_exists("URL", $arLog)
			&& strlen($arLog["URL"]) > 0
				? '<a href="'.$arLog["URL"].'">'.$arLog["TITLE"].'</a>'
				: $arLog["TITLE"]
		);

		$title = str_replace(
			array("#TITLE#", "#ENTITY#", "#CREATED_BY#"),
			array($topic_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
			$title_tmp
		);

		static $parser = false;
		if (CModule::IncludeModule("forum"))
		{
			if (!$parser)
			{
				$parser = new forumTextParser(LANGUAGE_ID);
			}

			$parser->pathToUser = $parser->userPath = $arParams["PATH_TO_USER"];
			$parser->bMobile = ($arParams["MOBILE"] == "Y");
			$parser->LAZYLOAD = (isset($arParams["LAZYLOAD"]) && $arParams["LAZYLOAD"] == "Y" ? "Y" : "N");

			$arFields["FILES"] = CForumFiles::GetByMessageID($arFields["SOURCE_ID"]);
		}

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => $title,
			"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : htmlspecialcharsBack($arFields["MESSAGE"])),
			"FILES" => (!!$arFields["FILES"] ? array_keys($arFields["FILES"]) : array())
		);

		if (!$bMail)
		{
			if ($arParams["MOBILE"] != "Y") 
			{
				static $parserLog = false;
				if (!$parserLog)
				{
					$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
				}

				$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
					$parserLog->convert(
						$arResult["EVENT_FORMATTED"]["MESSAGE"],
						array(),
						array(
							"HTML" => "Y",
							"ALIGN" => "Y",
							"ANCHOR" => "Y", "BIU" => "Y",
							"IMG" => "Y", "LOG_IMG" => "N",
							"QUOTE" => "Y", "LOG_QUOTE" => "N",
							"CODE" => "Y", "LOG_CODE" => "N",
							"FONT" => "Y", "LOG_FONT" => "N",
							"LIST" => "Y", "SMILES" => "Y",
							"NL2BR" => "Y", "MULTIPLE_BR" => "N",
							"VIDEO" => "Y", "LOG_VIDEO" => "N",
							"USERFIELDS" => $arFields["UF"]
						)
					),
					500
				);
			}

			$parser = (is_object($parser) ? $parser : (is_object($parserLog) ? $parserLog : new logTextParser(false, $arParams["PATH_TO_SMILE"])));
			if (get_class($parser) == "forumTextParser")
			{
				$parser->arUserfields = $arFields["UF"];
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parser->convert(
					$arResult["EVENT_FORMATTED"]["MESSAGE"],
					array(
						"HTML" => "N",
						"ALIGN" => "Y",
						"ANCHOR" => "Y", "BIU" => "Y",
						"IMG" => "Y", "QUOTE" => "Y",
						"CODE" => "Y", "FONT" => "Y",
						"LIST" => "Y", "SMILES" => "Y",
						"NL2BR" => "Y", "VIDEO" => "Y",
						"LOG_VIDEO" => "N", "SHORT_ANCHOR" => "Y",
						"USERFIELDS" => $arFields["UF"],
						"USER" => "Y"
					),
					"html",
					$arResult["EVENT_FORMATTED"]["FILES"]
				));
				$arResult["EVENT_FORMATTED"]["PARSED_FILES"] = $parser->arFilesIDParsed;
			}
			else
			{
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parser->convert(
					$arResult["EVENT_FORMATTED"]["MESSAGE"],
					array(),
					array(
						"HTML" => "N",
						"ALIGN" => "Y",
						"ANCHOR" => "Y", "BIU" => "Y",
						"IMG" => "Y", "QUOTE" => "Y",
						"CODE" => "Y", "FONT" => "Y",
						"LIST" => "Y", "SMILES" => "Y",
						"NL2BR" => "Y", "VIDEO" => "Y",
						"LOG_VIDEO" => "N", "SHORT_ANCHOR" => "Y",
						"USERFIELDS" => $arFields["UF"]
					)
				));
			}

			if ($arParams["MOBILE"] != "Y" && $arParams["NEW_TEMPLATE"] != "Y")
			{
				$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);
			}
		}
		else
		{
			if (strlen($arFields["URL"]) > 0)
			{
				$url = $arFields["URL"];
			}
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
				{
					$url = CComponentEngine::MakePathFromTemplate($arTmp["PATH_TO_MESSAGE"], array("MID" => $arFields["SOURCE_ID"]));
				}
			}

			$url = (
				strlen($url) > 0
					? CSocNetLogTools::FormatEvent_GetURL(array("ID" => $arLog["ID"], "URL" => $url))
					: CSocNetLogTools::FormatEvent_GetURL($arLog)
			);

			if (strlen($url) > 0)
			{
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
			}
		}

		if (
			$bMail
			&& strlen($arFields["MAIL_LANGUAGE_ID"]) > 0
		)
		{
			IncludeModuleLangFile(__FILE__, LANGUAGE_ID);
		}

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
		else
		{
			$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $arFields["ID"]));
			while ($arRight = $dbRight->Fetch())
			{
				$arRights[] = $arRight["GROUP_CODE"];
			}

			$arResult["EVENT_FORMATTED"]["DESTINATION"] = CSocNetLogTools::FormatDestinationFromRights($arRights, array_merge($arParams, array("CREATED_BY" => $arFields["USER_ID"])), $iMoreCount);
			if (intval($iMoreCount) > 0)
			{
				$arResult["EVENT_FORMATTED"]["DESTINATION_MORE"] = $iMoreCount;
			}
		}

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
					if (
						$arFields["ENTITY_TYPE"] == SONET_ENTITY_GROUP 
						&& (
							IsModuleInstalled("extranet")
							|| (strpos($arTmp["SECTION_URL"], "#GROUPS_PATH#") !== false)
						)
					)
					{
						$arTmp["SECTION_URL"] = str_replace("#GROUPS_PATH#", COption::GetOptionString("socialnetwork", "workgroups_page", "/workgroups/", SITE_ID), $arTmp["SECTION_URL"]);
					}
					if ($arParams["MOBILE"] == "Y")
					{
						$album_tmp .= ' '.htmlspecialcharsbx($arTmp["SECTION_NAME"]);
					}
					else
					{
						$album_tmp .= ' <a href="'.$arTmp["SECTION_URL"].'">'.htmlspecialcharsbx($arTmp["SECTION_NAME"]).'</a>';
					}
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
		{
			$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $arFields["ID"]));
			while ($arRight = $dbRight->Fetch())
			{
				$arRights[] = $arRight["GROUP_CODE"];
			}

			$arResult["EVENT_FORMATTED"]["DESTINATION"] = CSocNetLogTools::FormatDestinationFromRights($arRights, array_merge($arParams, array("CREATED_BY" => $arFields["USER_ID"])), $iMoreCount);
			if (intval($iMoreCount) > 0)
			{
				$arResult["EVENT_FORMATTED"]["DESTINATION_MORE"] = $iMoreCount;
			}
		}

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
			static $parserLog = false;
			if (CModule::IncludeModule("forum"))
			{
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
					"VIDEO" => "Y", "LOG_VIDEO" => "N",
					"USERFIELDS" => $arFields["UF"],
					"USER" => ($arParams["IM"] == "Y" ? "N" : "Y")
				);

				if (!$parserLog)
					$parserLog = new forumTextParser(LANGUAGE_ID);

				$parserLog->arUserfields = $arFields["UF"];
				$parserLog->pathToUser = $parserLog->userPath = $arParams["PATH_TO_USER"];
				$parserLog->bMobile = ($arParams["MOBILE"] == "Y");
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), $arAllow));
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = preg_replace("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/is".BX_UTF_PCRE_MODIFIER, "\\2", $arResult["EVENT_FORMATTED"]["MESSAGE"]);
			}
			else
			{
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

				if (!$parserLog)
					$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);

				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));
			}

			if (
				$arParams["MOBILE"] != "Y" 
				&& $arParams["NEW_TEMPLATE"] != "Y"
			)
			{
				if (CModule::IncludeModule("forum"))
					$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
						$parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), $arAllow),
						500
					);
				else
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

	public static function FormatComment_PhotoAlbum($arFields, $arParams, $bMail = false, $arLog = array())
	{
	
		$arResult = array(
			"EVENT_FORMATTED"	=> array(
				"TITLE" => ($bMail || $arParams["USE_COMMENT"] != "Y" ? GetMessage("SONET_GL_COMMENT_TITLE_PHOTO_ALBUM") : ""),
				"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : $arFields["MESSAGE"])
			),
		);

		if ($bMail)
		{
		}
		elseif($arParams["USE_COMMENT"] != "Y")
		{
			$arResult["ENTITY"] = CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, false);
		}

		if ($bMail)
		{

		}
		else
		{
			static $parserLog = false;
			if (CModule::IncludeModule("forum"))
			{
				$arAllow = array(
					"HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "LOG_IMG" => "N",
					"QUOTE" => "Y", "LOG_QUOTE" => "N",
					"CODE" => "Y", "LOG_CODE" => "N",
					"FONT" => "Y", "LOG_FONT" => "N",
					"LIST" => "Y",
					"SMILES" => "Y",
					"NL2BR" => "Y",
					"MULTIPLE_BR" => "N",
					"VIDEO" => "Y", "LOG_VIDEO" => "N",
					"USERFIELDS" => $arFields["UF"],
					"USER" => ($arParams["IM"] == "Y" ? "N" : "Y")
				);

				if (!$parserLog)
					$parserLog = new forumTextParser(LANGUAGE_ID);

				$parserLog->arUserfields = $arFields["UF"];
				$parserLog->pathToUser = $parserLog->userPath = $arParams["PATH_TO_USER"];
				$parserLog->bMobile = ($arParams["MOBILE"] == "Y");
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), $arAllow));
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = preg_replace("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/is".BX_UTF_PCRE_MODIFIER, "\\2", $arResult["EVENT_FORMATTED"]["MESSAGE"]);
			}
			else
			{
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

				if (!$parserLog)
					$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);

				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));
			}
		}

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
			if (
				$arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP 
				&& (
					IsModuleInstalled("extranet")
					|| (strpos($arFields["URL"], "#GROUPS_PATH#") !== false)
				)
			)
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
					{
						$message_24_2 = $changes_24 = "";
					}
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
				"MESSAGE" => htmlspecialcharsbx($arFields["MESSAGE"]),
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
		{
			$arResult["EVENT_FORMATTED"]["DESTINATION"] = array(
				array(
					"STYLE" => "sonetgroups",
					"TITLE" => $arResult["ENTITY"]["FORMATTED"]["NAME"],
					"URL" => $arResult["ENTITY"]["FORMATTED"]["URL"],
					"IS_EXTRANET" => (is_array($GLOBALS["arExtranetGroupID"]) && in_array($arFields["ENTITY_ID"], $GLOBALS["arExtranetGroupID"]))
				)
			);
		}

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
			$user_tmp = '';

			if ($bMail)
			{
				$user_tmp .= (
					strlen($arUser["NAME"]) > 0
					|| strlen($arUser["LAST_NAME"]) > 0
						? $arUser["NAME"]." ".$arUser["LAST_NAME"].$suffix
						: $arUser["LOGIN"].$suffix
				);
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
					array("FIELDS" => array("ID", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "PERSONAL_GENDER"))
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
					$arLastUser = $arUser;
				}
			}
		}

		if ($bMail)
			$title_tmp = GetMessage("SONET_GL_EVENT_TITLE_SYSTEM_".strtoupper($arFields["TITLE"])."_".($count > 1 ? "2" : "1")."_MAIL");
		else
		{
			if (in_array($arFields["TITLE"], array("moderate", "unmoderate", "join", "unjoin")))
			{
				if (
					$count == 1
					&& $arLastUser
				)
					$suffix = $arLastUser["PERSONAL_GENDER"];
				else
					$suffix = "";
			}
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
		{
			return false;
		}

		$ufFileID = array();
		$ufDocID = array();

		$dbResult = CSocNetLog::GetList(
			array(),
			array("ID" => $arFields["LOG_ID"]),
			false,
			false,
			array("ID", "SOURCE_ID", "SITE_ID", "TITLE", "PARAMS")
		);

		if ($arLog = $dbResult->Fetch())
		{
			$arMessage = CForumMessage::GetByID($arLog["SOURCE_ID"]);
			if ($arMessage)
			{
				$userID = $GLOBALS["USER"]->GetID();
				$notificationSiteId = false;

				$arLogSites = array();
				$rsLogSite = CSocNetLog::GetSite($arLog["ID"]);
				while ($arLogSite = $rsLogSite->Fetch())
				{
					$arLogSites[] = $arLogSite["LID"];
					if (
						!$notificationSiteId
						&& (
							!CModule::IncludeModule('extranet')
							|| $arLogSite["LID"] != CExtranet::GetExtranetSiteID()
						)
					)
					{
						$notificationSiteId = $arLogSite["LID"];
					}
				}

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
					"PERMISSION_EXTERNAL" => "Q",
					"PERMISSION" => $strPermission,
					"APPROVED" => "Y"
				);

				$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("SONET_COMMENT", $arTmp);
				if (is_array($arTmp))
				{
					if (array_key_exists("UF_SONET_COM_DOC", $arTmp))
					{
						$GLOBALS["UF_FORUM_MESSAGE_DOC"] = $arTmp["UF_SONET_COM_DOC"];
					}
					elseif (array_key_exists("UF_SONET_COM_FILE", $arTmp))
					{
						$arFieldsMessage["FILES"] = array();
						foreach($arTmp["UF_SONET_COM_FILE"] as $file_id)
						{
							$arFieldsMessage["FILES"][$file_id] = array("FILE_ID" => $file_id);
						}

						if (!empty($arFieldsMessage["FILES"]))
						{
							$arFileParams = array("FORUM_ID" => $arMessage["FORUM_ID"], "TOPIC_ID" => $arMessage["TOPIC_ID"]);
							if (CForumFiles::CheckFields($arFieldsMessage["FILES"], $arFileParams, "NOT_CHECK_DB"))
							{
								CForumFiles::Add(array_keys($arFieldsMessage["FILES"]), $arFileParams);
							}
						}
					}
				}

				$messageID = ForumAddMessage("REPLY", $arMessage["FORUM_ID"], $arMessage["TOPIC_ID"], 0, $arFieldsMessage, $sError, $sNote);
				unset($GLOBALS["UF_FORUM_MESSAGE_DOC"]);

				// get UF DOC value and FILE_ID there
				if ($messageID > 0)
				{
					$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $messageID));
					while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
					{
						$ufFileID[] = $arAddedMessageFiles["FILE_ID"];
					}

					$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $messageID, LANGUAGE_ID);
				}

				if (
					$messageID > 0
					&& CModule::IncludeModule("im")
					&& intval($arMessage["AUTHOR_ID"]) > 0
					&& $arMessage["AUTHOR_ID"] != $userID
				)
				{
					$rsUnFollower = CSocNetLogFollow::GetList(
						array(
							"USER_ID" => $arMessage["AUTHOR_ID"],
							"CODE" => "L".$arLog["ID"],
							"TYPE" => "N"
						),
						array("USER_ID")
					);

					$arUnFollower = $rsUnFollower->Fetch();
					if (!$arUnFollower)
					{
						$arMessageFields = array(
							"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
							"TO_USER_ID" => $arMessage["AUTHOR_ID"],
							"FROM_USER_ID" => $userID,
							"LOG_ID" => $arLog["ID"],
							"NOTIFY_TYPE" => IM_NOTIFY_FROM,
							"NOTIFY_MODULE" => "forum",
							"NOTIFY_EVENT" => "comment",
						);

						$arParams["TITLE"] = str_replace(Array("\r\n", "\n"), " ", $arLog["TITLE"]);
						$arParams["TITLE"] = TruncateText($arParams["TITLE"], 100);
						$arParams["TITLE_OUT"] = TruncateText($arParams["TITLE"], 255);
						
						$arParams["URL"] = "";
						if (strlen($arLog["PARAMS"]) > 0)
						{
							$arTmp = unserialize(htmlspecialcharsback($arLog["PARAMS"]));
							if (
								$arTmp 
								&& array_key_exists("PATH_TO_MESSAGE", $arTmp)
							)
								$arParams["URL"] = CComponentEngine::MakePathFromTemplate(
									$arTmp["PATH_TO_MESSAGE"], 
									array("MID" => $messageID)
								);
						}

						$arTmp = CSocNetLogTools::ProcessPath(array("MESSAGE_URL" => $arParams["URL"]), $arMessage["AUTHOR_ID"], $notificationSiteId);
						$serverName = $arTmp["SERVER_NAME"];
						$url = $arTmp["URLS"]["MESSAGE_URL"];

						$arMessageFields["NOTIFY_TAG"] = "FORUM|COMMENT|".$messageID;
						$arMessageFields["NOTIFY_MESSAGE"] = GetMessage("SONET_FORUM_IM_COMMENT", Array(
							"#title#" => (
								strlen($url) > 0 
									? "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arParams["TITLE"])."</a>"
									: htmlspecialcharsbx($arParams["TITLE"])
							)
						));

						$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage("SONET_FORUM_IM_COMMENT", Array(
							"#title#" => htmlspecialcharsbx($arParams["TITLE"])
						)).(strlen($url) > 0 
							? " (".$serverName.$url.")"
							: ""
						)."#BR##BR#".$arFields["TEXT_MESSAGE"];

						CIMNotify::Add($arMessageFields);
					}
				}
			}
			else
			{
				$sError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
			}
		}
		else
		{
			$sError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
		}

		return array(
			"SOURCE_ID" => $messageID,
			"RATING_TYPE_ID" => "FORUM_POST",
			"RATING_ENTITY_ID" => $messageID,
			"ERROR" => $sError,
			"NOTES" => $sNote,
			"UF" => array(
				"FILE" => $ufFileID,
				"DOC" => $ufDocID
			)
		);
	}

	public static function UpdateComment_Forum($arFields)
	{
		if (!CModule::IncludeModule("forum"))
		{
			return false;
		}

		if (
			!isset($arFields["SOURCE_ID"])
			|| intval($arFields["SOURCE_ID"]) <= 0
		)
		{
			return false;
		}

		$messageId = intval($arFields["SOURCE_ID"]);

		$ufFileID = array();
		$ufDocID = array();

		if ($arForumMessage = CForumMessage::GetByID($messageId))
		{
			$arFieldsMessage = array(
				"POST_MESSAGE" => $arFields["TEXT_MESSAGE"],
				"USE_SMILES" => "Y",
				"APPROVED" => "Y",
				"SONET_PERMS" => array("bCanFull" => true)
			);

			$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("SONET_COMMENT", $arTmp);
			if (is_array($arTmp))
			{
				if (array_key_exists("UF_SONET_COM_DOC", $arTmp))
				{
					$GLOBALS["UF_FORUM_MESSAGE_DOC"] = $arTmp["UF_SONET_COM_DOC"];
				}
				elseif (array_key_exists("UF_SONET_COM_FILE", $arTmp))
				{
					$arFieldsMessage["FILES"] = array();
					foreach($arTmp["UF_SONET_COM_FILE"] as $file_id)
					{
						$arFieldsMessage["FILES"][$file_id] = array("FILE_ID" => $file_id);
					}
					if (!empty($arFieldsMessage["FILES"]))
					{
						$arFileParams = array("FORUM_ID" => $arForumMessage["FORUM_ID"], "TOPIC_ID" => $arForumMessage["TOPIC_ID"]);
						if(CForumFiles::CheckFields($arFieldsMessage["FILES"], $arFileParams, "NOT_CHECK_DB"))
						{
							CForumFiles::Add(array_keys($arFieldsMessage["FILES"]), $arFileParams);
						}
					}
				}
			}

			$messageID = ForumAddMessage("EDIT", $arForumMessage["FORUM_ID"], $arForumMessage["TOPIC_ID"], $messageId, $arFieldsMessage, $sError, $sNote);
			unset($GLOBALS["UF_FORUM_MESSAGE_DOC"]);

			// get UF DOC value and FILE_ID there
			if ($messageID > 0)
			{
				$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $messageID));
				while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
				{
					$ufFileID[] = $arAddedMessageFiles["FILE_ID"];
				}

				$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $messageID, LANGUAGE_ID);
			}
		}
		else
		{
			$sError = GetMessage("SONET_UPDATE_COMMENT_SOURCE_ERROR");
		}

		return array(
			"ERROR" => $sError,
			"NOTES" => $sNote,
			"UF" => array(
				"FILE" => $ufFileID,
				"DOC" => $ufDocID
			)
		);
	}

	public static function DeleteComment_Forum($arFields)
	{
		$arRes = array();

		if (
			CModule::IncludeModule("forum")
			&& isset($arFields["SOURCE_ID"])
			&& intval($arFields["SOURCE_ID"]) > 0
			&& isset($arFields["EVENT_ID"])
			&& strlen($arFields["EVENT_ID"]) > 0
			&& isset($arFields["LOG_SOURCE_ID"])
			&& intval($arFields["LOG_SOURCE_ID"]) > 0
		)
		{
			$logEventMeta = CSocNetLogTools::FindLogEventByCommentID($arFields["EVENT_ID"]);

			if (
				true || // we are not ready to use \Bitrix\Forum\Comments\Feed yet
				$logEventMeta["EVENT_ID"] == "forum"
			)
			{
				if (CModule::IncludeModule("forum"))
				{
					ForumActions("DEL", array(
						"MID" => intval($arFields["SOURCE_ID"]),
						"PERMISSION" => "Y"
					), $strErrorMessage, $strOKMessage);

					$arRes["ERROR"] = $strErrorMessage;
					$arRes["NOTES"] = $strOKMessage;
				}
			}
			else
			{
				if ($logEventMeta)
				{
					$arForumMetaData = CSocNetLogTools::GetForumCommentMetaData($logEventMeta["EVENT_ID"]);
				}

				if ($arForumMetaData)
				{
					$messageId = intval($arFields["SOURCE_ID"]);

					$rsMessage = CForumMessage::GetList(
						array(),
						array("ID" => $messageId),
						false,
						0,
						array(
							"SELECT" => array("FORUM_ID")
						)
					);
					if ($arMessage = $rsMessage->Fetch())
					{
						$forumId = intval($arMessage["FORUM_ID"]);
					}

					if (
						$forumId
						&& intval($forumId) > 0
					)
					{
						if (
							(
								$arForumMetaData[0] == 'WF'
								|| $arForumMetaData[0] == 'FORUM'
							)
							&& isset($arFields["LOG_ID"])
							&& intval($arFields["LOG_ID"]) > 0
						)
						{
							$rsLog = CSocNetLog::GetList(
								array(),
								array("ID" => intval($arFields["LOG_ID"])),
								false,
								false,
								array("MESSAGE", "RATING_ENTITY_ID")
							);
							if ($arLog = $rsLog->Fetch())
							{
								if ($arForumMetaData[0] == 'WF')
								{
									$entityId = $arLog["MESSAGE"];
								}
								elseif ($arForumMetaData[0] == 'FORUM')
								{
									$entityId = $arLog["RATING_ENTITY_ID"];
								}
							}
						}
						elseif (
							$arForumMetaData[0] == 'FORUM'
							&& isset($arFields["LOG_ID"])
							&& intval($arFields["LOG_ID"]) > 0
						)
						{
							$rsLog = CSocNetLog::GetList(
								array(),
								array("ID" => intval($arFields["LOG_ID"])),
								false,
								false,
								array("MESSAGE")
							);
							if ($arLog = $rsLog->Fetch())
							{
								$entityId = $arLog["MESSAGE"];
							}
						}
						else
						{
							$entityId = $arFields["LOG_SOURCE_ID"];
						}

						$feed = new \Bitrix\Forum\Comments\Feed(
							intval($forumId),
							array(
								"type" => $arForumMetaData[1],
								"id" => intval($arFields["LOG_SOURCE_ID"]),
								"xml_id" => $arForumMetaData[0]."_".$entityId
							)
						);

						if (!$feed->delete($messageId))
						{
							$arRes["ERROR"] = "";
							foreach($feed->getErrors() as $error)
							{
								$arRes["ERROR"] .= $error->getMessage();
							}
						}
						else
						{
							$arRes["NOTES"] = GetMessage("SONET_DELETE_COMMENT_SOURCE_SUCCESS");
						}
					}
				}
			}
		}

		if (!isset($arRes["NOTES"]))
		{
			$arRes["ERROR"] = GetMessage("SONET_DELETE_COMMENT_SOURCE_ERROR");
		}

		return $arRes;
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
						"PARAM2" => $arElement["ID"],
						"APPROVED" => "Y"
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
			"APPROVED" => "Y",
			"XML_ID" => "IBLOCK_".$arElement["ID"]
		);

		if (
			isset($arElement["ENTITY_TYPE"])
			&& $arElement["ENTITY_TYPE"] == SONET_ENTITY_GROUP
			&& isset($arElement["ENTITY_ID"])
			&& intval($arElement["ENTITY_ID"]) > 0
		)
		{
			$arFields["SOCNET_GROUP_ID"] = intval($arElement["ENTITY_ID"]);
		}

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
				CForumTopic::Delete($TOPIC_ID);
				$TOPIC_ID = 0;
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

		$ufFileID = array();
		$ufDocID = array();

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
				// read shared cross-site FORUM_ID
				$forumID = COption::GetOptionString("tasks", "task_forum_id", 0, $siteId = '');

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
							"PERMISSION_EXTERNAL" => 'E',
							"PERMISSION" => 'E',
							"APPROVED" => "Y",
							'XML_ID' => 'TASK_' . $arTask['ID']
						);
						$TID = CForumTopic::Add($arTopicFields);
						if (intVal($TID) > 0)
						{
							$arFieldsFirstMessage = Array(
								"POST_MESSAGE" => $arTopicFields['XML_ID'],
								"AUTHOR_ID" => $arTopicFields["USER_START_ID"],
								"AUTHOR_NAME" => $arTopicFields["USER_START_NAME"],
								"FORUM_ID" => $arTopicFields["FORUM_ID"],
								"TOPIC_ID" => $TID,
								"APPROVED" => "Y",
								"NEW_TOPIC" => "Y",
								"PARAM1" => 'TK',
								"PARAM2" => $arTask['ID'],
								"PERMISSION_EXTERNAL" => 'E',
								"PERMISSION" => 'E',
							);
							CForumMessage::Add($arFieldsFirstMessage, false, array("SKIP_INDEXING" => "Y", "SKIP_STATISTIC" => "N"));

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
					else
					{
						// override forumId by fact forum, attached to the task
						if ($arTopic = CForumTopic::getByID($arTask['FORUM_TOPIC_ID']))
							$forumID = $arTopic['FORUM_ID'];
					}

					if ($forumID && $arTask["FORUM_TOPIC_ID"])
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
							"POST_MESSAGE" => $arFields["TEXT_MESSAGE"],
							"USE_SMILES" => "Y",
							"PERMISSION_EXTERNAL" => "Q",
							"PERMISSION" => $strPermission,
							"APPROVED" => "Y"
						);
						$MESSAGE_TYPE = 'REPLY';

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

						$messageID = ForumAddMessage($MESSAGE_TYPE, $forumID, $arTask["FORUM_TOPIC_ID"], 0, $arFieldsMessage, $sError, $sNote);

						// get UF DOC value and FILE_ID there
						if ($messageID > 0)
						{
							$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $messageID));
							while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
								$ufFileID[] = $arAddedMessageFiles["FILE_ID"];

							$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $messageID, LANGUAGE_ID);
						}

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
							$arUnFollowers = array();

							$rsUnFollower = CSocNetLogFollow::GetList(
								array(
									"USER_ID" => $arRecipientsIDs,
									"CODE" => "L".$arFields["LOG_ID"],
									"TYPE" => "N"
								),
								array("USER_ID")
							);
							while ($arUnFollower = $rsUnFollower->Fetch())
								$arUnFollowers[] = $arUnFollower["USER_ID"];

							$arRecipientsIDs = array_diff($arRecipientsIDs, $arUnFollowers);

							if (
								IsModuleInstalled("im") 
								&& CModule::IncludeModule("im") 
								&& sizeof($arRecipientsIDs)
							)
							{
								$extranetSiteId = false;
								if (CModule::IncludeModule('extranet')
									&& method_exists('CExtranet', 'GetExtranetSiteID')
								)
									$extranetSiteId = CExtranet::GetExtranetSiteID();

								foreach ($arRecipientsIDs as $recipientUserID)
								{
									$arFilter = array(
										"UF_DEPARTMENT" => false,
										"ID" => $recipientUserID
									);

									$rsUser = CUser::GetList(
										$by = "last_name", 
										$order = "asc", 
										$arFilter, 
										array("SELECT" => array("UF_DEPARTMENT"))
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

									$messageUrl = CComponentEngine::MakePathFromTemplate(
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

									$MESSAGE_SITE = preg_replace(
										array(
											'|\[\/USER\]|', 
											'|\[USER=\d+\]|',
											'|\[DISK\sFILE\sID=[n]*\d+\]|',
											'|\[DOCUMENT\sID=\d+\]|'
										), 
										'', 
										$arFields['TEXT_MESSAGE']
									);

									if (strlen($MESSAGE_SITE) >= 100)
									{
										$dot = '...';
										$MESSAGE_SITE = substr($MESSAGE_SITE, 0, 99);

										if (substr($MESSAGE_SITE, -1) === '[')
											$MESSAGE_SITE = substr($MESSAGE_SITE, 0, 98);

										if (
											(($lastLinkPosition = strrpos($MESSAGE_SITE, '[u')) !== false)
											|| (($lastLinkPosition = strrpos($MESSAGE_SITE, 'http://')) !== false)
											|| (($lastLinkPosition = strrpos($MESSAGE_SITE, 'https://')) !== false)
											|| (($lastLinkPosition = strrpos($MESSAGE_SITE, 'ftp://')) !== false)
											|| (($lastLinkPosition = strrpos($MESSAGE_SITE, 'ftps://')) !== false)
										)
										{
											if (strpos($MESSAGE_SITE, ' ', $lastLinkPosition) === false)
												$MESSAGE_SITE = substr($MESSAGE_SITE, 0, $lastLinkPosition);
										}

										$MESSAGE_SITE .= $dot;
									}

									$rsUser = CUser::GetList(
										$by = 'id',
										$order = 'asc',
										array('ID_EQUAL_EXACT' => (int) $userID),
										array('FIELDS' => array('PERSONAL_GENDER'))
									);

									$strMsgAddComment  = GetMessage("SONET_GL_EVENT_TITLE_TASK_COMMENT_MESSAGE_ADD");
									$strMsgEditComment = GetMessage("SONET_GL_EVENT_TITLE_TASK_COMMENT_MESSAGE_EDIT");

									if ($arUser = $rsUser->fetch())
									{
										switch ($arUser['PERSONAL_GENDER'])
										{
											case "F":
											case "M":
												$strMsgAddComment  = GetMessage("SONET_GL_EVENT_TITLE_TASK_COMMENT_MESSAGE_ADD" . '_' . $arUser['PERSONAL_GENDER']);
												$strMsgEditComment = GetMessage("SONET_GL_EVENT_TITLE_TASK_COMMENT_MESSAGE_EDIT" . '_' . $arUser['PERSONAL_GENDER']);
											break;

											default:
											break;
										}
									}

									$arMessageFields = array(
										"TO_USER_ID" => $recipientUserID,
										"FROM_USER_ID" => $userID, 
										"NOTIFY_TYPE" => IM_NOTIFY_FROM, 
										"NOTIFY_MODULE" => "tasks", 
										"NOTIFY_EVENT" => "comment",
										"NOTIFY_MESSAGE" => str_replace(
											array("#TASK_TITLE#", "#TASK_COMMENT_TEXT#"), 
											array('[URL=' . tasksServerName(). $messageUrl . "#message" . $messageID.']' . htmlspecialcharsbx($arTask["TITLE"]) . '[/URL]', '[COLOR=#000000]' . $MESSAGE_SITE . '[/COLOR]'), 
											($MESSAGE_TYPE != "EDIT" ? $strMsgAddComment : $strMsgEditComment)
										),
										"NOTIFY_MESSAGE_OUT" => str_replace(
											array("#TASK_TITLE#", "#TASK_COMMENT_TEXT#"), 
											array(htmlspecialcharsbx($arTask["TITLE"]), $MESSAGE_SITE . ' #BR# ' . tasksServerName() . $messageUrl . "#message" . $messageID . ' '), 
											($MESSAGE_TYPE != "EDIT" ? $strMsgAddComment : $strMsgEditComment)
										)."#BR##BR#".$arFields["TEXT_MESSAGE"],
										"NOTIFY_TAG" => "TASKS|COMMENT|".intval($arTask["ID"])."|".intval($recipientUserID)
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

							$arFilesIds = array_merge($ufFileID, (is_array($ufDocID) ? $ufDocID : array()));
							CTaskComments::fireOnAfterCommentAddEvent($messageID, $arTask['ID'], $arFields["TEXT_MESSAGE"], $arFilesIds);
						}
					}
				}
			}
		}

		if (!$messageID)
		{
			$sError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
		}

		return array(
			"SOURCE_ID" => $messageID,
			"MESSAGE" => ($arFieldsMessage ? $arFieldsMessage["POST_MESSAGE"] : false),
			"RATING_TYPE_ID" => "FORUM_POST",
			"RATING_ENTITY_ID" => $messageID,
			"ERROR" => $sError,
			"NOTES" => $sNote,
			"UF" => array(
				"FILE" => $ufFileID,
				"DOC" => $ufDocID
			),
			"URL" => $messageUrl
		);
	}

	public static function OnAfterPhotoUpload($arFields, $arComponentParams, $arComponentResult)
	{
		return CSocNetLogToolsPhoto::OnAfterPhotoUpload($arFields, $arComponentParams, $arComponentResult);
	}

	public static function OnAfterPhotoDrop($arFields, $arComponentParams)
	{
		return CSocNetLogToolsPhoto::OnAfterPhotoDrop($arFields, $arComponentParams);
	}

	public static function OnBeforeSectionDrop($sectionID, $arComponentParams, $arComponentResult, &$arSectionID, &$arElementID)
	{
		return CSocNetLogToolsPhoto::OnBeforeSectionDrop($sectionID, $arComponentParams, $arComponentResult, $arSectionID, $arElementID);
	}

	public static function OnAfterSectionDrop($ID, $arFields, $arComponentParams, $arComponentResult)
	{
		return CSocNetLogToolsPhoto::OnAfterSectionDrop($ID, $arFields, $arComponentParams, $arComponentResult);
	}

	public static function OnAfterSectionEdit($arFields, $arComponentParams, $arComponentResult)
	{
		return CSocNetLogToolsPhoto::OnAfterSectionEdit($arFields, $arComponentParams, $arComponentResult);
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
			public static function __DestinationRightsSort($a, $b)
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
				elseif (preg_match('/^CRMDEAL\d+$/', $a))
				{
					if (preg_match('/^CRMDEAL\d+$/', $b))
						return 0;
					elseif (
						preg_match('/^US\d+$/', $b)
						|| in_array($b, array("G2", "AU"))
					)
						return 1;
					else
						return -1;
				}
				elseif (preg_match('/^CRMCONTACT\d+$/', $a))
				{
					if (preg_match('/^CRMCONTACT\d+$/', $b))
						return 0;
					elseif (
						preg_match('/^US\d+$/', $b)
						|| in_array($b, array("G2", "AU"))
						|| preg_match('/^CRMDEAL\d+$/', $b)
					)
						return 1;
					else
						return -1;
				}
				elseif (preg_match('/^CRMCOMPANY\d+$/', $a))
				{
					if (preg_match('/^CRMCOMPANY\d+$/', $b))
						return 0;
					elseif (
						preg_match('/^US\d+$/', $b)
						|| in_array($b, array("G2", "AU"))
						|| preg_match('/^CRMDEAL\d+$/', $b)
						|| preg_match('/^CRMCONTACT\d+$/', $b)
					)
						return 1;
					else
						return -1;
				}
				elseif (preg_match('/^CRMLEAD\d+$/', $a))
				{
					if (preg_match('/^CRMLEAD\d+$/', $b))
						return 0;
					elseif (
						preg_match('/^US\d+$/', $b)
						|| in_array($b, array("G2", "AU"))
						|| preg_match('/^CRMDEAL\d+$/', $b)
						|| preg_match('/^CRMCONTACT\d+$/', $b)
						|| preg_match('/^CRMCOMPANY\d+$/', $b)
					)
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
						|| preg_match('/^CRMDEAL\d+$/', $b)
						|| preg_match('/^CRMCONTACT\d+$/', $b)
						|| preg_match('/^CRMCOMPANY\d+$/', $b)
						|| preg_match('/^CRMLEAD\d+$/', $b)
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
						|| preg_match('/^CRMDEAL\d+$/', $b)
						|| preg_match('/^CRMCONTACT\d+$/', $b)
						|| preg_match('/^CRMCOMPANY\d+$/', $b)
						|| preg_match('/^CRMLEAD\d+$/', $b)
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
						|| preg_match('/^CRMDEAL\d+$/', $b)
						|| preg_match('/^CRMCONTACT\d+$/', $b)
						|| preg_match('/^CRMCOMPANY\d+$/', $b)
						|| preg_match('/^CRMLEAD\d+$/', $b)
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
						|| preg_match('/^CRMDEAL\d+$/', $b)
						|| preg_match('/^CRMCONTACT\d+$/', $b)
						|| preg_match('/^CRMCOMPANY\d+$/', $b)
						|| preg_match('/^CRMLEAD\d+$/', $b)
					)
						return 1;
					else
						return -1;
				}
				elseif (preg_match('/^DR\d+$/', $a))
				{
					if (preg_match('/^DR\d+$/', $b))
						return 0;
					elseif (
						preg_match('/^US\d+$/', $b)
						|| in_array($b, array("G2", "AU"))
						|| preg_match('/^SG\d+_'.SONET_ROLES_USER.'$/', $b)
						|| preg_match('/^SG\d+_'.SONET_ROLES_MODERATOR.'$/', $b)
						|| preg_match('/^SG\d+_'.SONET_ROLES_OWNER.'$/', $b)
						|| preg_match('/^D\d+$/', $b)
						|| preg_match('/^CRMDEAL\d+$/', $b)
						|| preg_match('/^CRMCONTACT\d+$/', $b)
						|| preg_match('/^CRMCOMPANY\d+$/', $b)
						|| preg_match('/^CRMLEAD\d+$/', $b)
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
						|| preg_match('/^DR\d+$/', $b)
						|| preg_match('/^CRMDEAL\d+$/', $b)
						|| preg_match('/^CRMCONTACT\d+$/', $b)
						|| preg_match('/^CRMCOMPANY\d+$/', $b)
						|| preg_match('/^CRMLEAD\d+$/', $b)
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
						|| preg_match('/^DR\d+$/', $b)
						|| preg_match('/^U\d+$/', $b)
						|| preg_match('/^CRMDEAL\d+$/', $b)
						|| preg_match('/^CRMCONTACT\d+$/', $b)
						|| preg_match('/^CRMCOMPANY\d+$/', $b)
						|| preg_match('/^CRMLEAD\d+$/', $b)
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
		usort($arRights, "__DestinationRightsSort");

		$cnt = 0;
		$bAll = false;
		$bJustCount = false;
		$arParams["DESTINATION_LIMIT"] = (intval($arParams["DESTINATION_LIMIT"]) <= 0 ? 3 : $arParams["DESTINATION_LIMIT"]);

		$arModuleEvents = array();
		$db_events = GetModuleEvents("socialnetwork", "OnSocNetLogFormatDestination");
		while ($arEvent = $db_events->Fetch())
		{
			$arModuleEvents[] = $arEvent;
		}

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
						"TITLE" => (
							IsModuleInstalled("intranet") 
								? GetMessage("SONET_GL_DESTINATION_G2") 
								: GetMessage("SONET_GL_DESTINATION_G2_BSM")
						)
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
				{
					continue;
				}

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
							"URL" => str_replace("#user_id#", $arUserTmp["ID"], $arParams["PATH_TO_USER"]),
							"IS_EXTRANET" => (is_array($GLOBALS["arExtranetUserID"]) && in_array($arUserTmp["ID"], $GLOBALS["arExtranetUserID"]) ? "Y" : "N")
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
						$rsDepartmentTmp = CIBlockSection::GetByID($matches[1]);
						if ($arDepartmentTmp = $rsDepartmentTmp->GetNext())
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
							"URL" => str_replace("#group_id#", $arSonetGroup["ID"], $arParams["PATH_TO_GROUP"]),
							"IS_EXTRANET" => (is_array($GLOBALS["arExtranetGroupID"]) && in_array($arSonetGroup["ID"], $GLOBALS["arExtranetGroupID"]) ? "Y" : "N")
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
								"URL" => str_replace("#group_id#", $arSonetGroup["ID"], $arParams["PATH_TO_GROUP"]),
								"IS_EXTRANET" => (is_array($GLOBALS["arExtranetGroupID"]) && in_array($arSonetGroup["ID"], $GLOBALS["arExtranetGroupID"]) ? "Y" : "N")
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
								"URL" => str_replace("#group_id#", $arSonetGroup["ID"], $arParams["PATH_TO_GROUP"]),
								"IS_EXTRANET" => (is_array($GLOBALS["arExtranetGroupID"]) && in_array($arSonetGroup["ID"], $GLOBALS["arExtranetGroupID"]) ? "Y" : "N")
							);

							if (!array_key_exists($arSonetGroup["ID"], $arSonetGroups))
								$arSonetGroups[$arSonetGroup["ID"]] = array();
							$arSonetGroups[$arSonetGroup["ID"]][] = SONET_ROLES_OWNER;
						}
					}
				}
			}
			else
			{
				$cnt++;
				if (!$bJustCount)
				{
					foreach ($arModuleEvents as $arEvent)
					{
						ExecuteModuleEventEx($arEvent, array(&$arDestination, $right_tmp, $arRights, $arParams, $bCheckPermissions));
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

	public static function ProcessPath($arUrl, $user_id, $explicit_site_id = false)
	{
		static $arIntranetUsers, $arSiteData, $extranet_site_id, $intranet_site_id;

		if (!is_array($arUrl))
			$arUrl = array($arUrl);

		if (
			CModule::IncludeModule("extranet")
			&& !$arIntranetUsers
		)
		{
			$extranet_site_id = CExtranet::GetExtranetSiteID();
			$intranet_site_id = CSite::GetDefSite();
			$arIntranetUsers = CExtranet::GetIntranetUsers();
		}

		if (!$arSiteData)
		{
			$arSiteData = self::GetSiteData();
		}

		$user_site_id = (
			IsModuleInstalled("extranet") 
				? (
					(!in_array($user_id, $arIntranetUsers) && $extranet_site_id) 
						? $extranet_site_id 
						: ($explicit_site_id ? $explicit_site_id : $intranet_site_id)
				)
				: ($explicit_site_id ? $explicit_site_id : SITE_ID)
		);

		$server_name = (CMain::IsHTTPS() ? "https" : "http")."://".$arSiteData[$user_site_id]["SERVER_NAME"];

		$arUrl = str_replace(
			array("#SERVER_NAME#", "#GROUPS_PATH#", "#USER_PATH#"),
			array(
				$server_name,
				$arSiteData[$user_site_id]["GROUPS_PATH"],
				$arSiteData[$user_site_id]["USER_PATH"]
			),
			$arUrl
		);

		return array(
			"SERVER_NAME" => $server_name, 
			"URLS" => $arUrl,
			"DOMAIN" => (count($arSiteData) > 1 ? $arSiteData[$user_site_id]["SERVER_NAME"] : false)
		);
	}

	public static function GetSiteData()
	{
		$arSiteData = array();

		$rsSite = CSite::GetList($by="sort", $order="desc", Array("ACTIVE" => "Y"));
		while ($arSite = $rsSite->Fetch())
		{
			$serverName = htmlspecialcharsEx($arSite["SERVER_NAME"]);
			$arSiteData[$arSite["ID"]] = array(
				"GROUPS_PATH" => COption::GetOptionString("socialnetwork", "workgroups_page", $arSite["DIR"]."workgroups/", $arSite["ID"]),
				"USER_PATH" => COption::GetOptionString("socialnetwork", "user_page", $arSite["DIR"]."company/personal/", $arSite["ID"]),
				"SERVER_NAME" => (
					strlen($serverName) > 0
						? $serverName
						: (
							defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0
								? SITE_SERVER_NAME
								: COption::GetOptionString("main", "server_name", "")
						)
				)
			);
		}

		return $arSiteData;
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

	public static function GetDataFromRatingEntity($rating_entity_type_id, $rating_entity_id, $bCheckRights = true)
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
				$log_event_id = array("blog_post", "blog_post_important");
				break;
			case "BLOG_COMMENT":
				$log_type = "comment";
				$log_event_id = array("blog_comment", "photo_comment");
				break;
			case "FORUM_TOPIC":
				$log_type = "log";
				$log_event_id = array("forum");
				if (CModule::IncludeModule("forum"))
				{
					$dbForumMessage = CForumMessage::GetList(
						array("ID" => "ASC"), 
						array("TOPIC_ID" => $rating_entity_id),
						false,
						1
					);
					if ($arForumMessage = $dbForumMessage->Fetch())
					{
						$rating_entity_id = $arForumMessage["ID"];
					}
				}
				break;
			case "FORUM_POST":
				$log_type = "comment";
				$log_event_id = array("forum", "photo_comment", "files_comment", "commondocs_comment", "tasks_comment", "wiki_comment", "news_comment", "lists_new_element_comment");
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
			case "VOTING":
				$log_type = "log";
				$log_event_id = array("blog_post", "blog_post_important");
				if (CModule::IncludeModule("blog"))
				{
					$rsBlogPost = CBlogPost::GetList(
						array("ID" => "DESC"), 
						array("UF_BLOG_POST_VOTE" => $rating_entity_id),
						false,
						array("nTopCount" => 1),
						array("ID")
					);

					if ($arBlogPost = $rsBlogPost->Fetch())
					{
						$rating_entity_id = $arBlogPost["ID"];
					}
				}
				break;
			case "LISTS_NEW_ELEMENT":
				$log_type = "log";
				$log_event_id = array("lists_new_element");
				break;
			case "LOG_ENTRY":
				$log_type = "log_entry";
				break;
			case "LOG_COMMENT":
				$log_type = "log_comment";
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
					"CHECK_RIGHTS" => ($bCheckRights ? "Y" : "N"),
					"USE_SUBSCRIBE" => "N"
				)
			);
			if ($arLogSrc = $rsLogSrc->Fetch())
			{
				$log_id = $arLogSrc["ID"];
			}
		}
		elseif ($log_type == "log_entry")
		{
			$rsLogSrc = CSocNetLog::GetList(
				array(),
				array(
					"ID" => $rating_entity_id
				),
				false,
				false,
				array("ID"),
				array(
					"CHECK_RIGHTS" => ($bCheckRights ? "Y" : "N"),
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
					"CHECK_RIGHTS" => ($bCheckRights ? "Y" : "N"),
					"USE_SUBSCRIBE" => "N"
				)
			);
			if ($arLogCommentSrc = $rsLogCommentSrc->Fetch())
			{
				$log_id = $arLogCommentSrc["LOG_ID"];
				$log_comment_id = $arLogCommentSrc["ID"];
			}
		}
		elseif ($log_type == "log_comment")
		{
			$rsLogCommentSrc = CSocNetLogComments::GetList(
				array(),
				array(
					"ID" => $rating_entity_id
				),
				false,
				false,
				array("ID", "LOG_ID"),
				array(
					"CHECK_RIGHTS" => ($bCheckRights ? "Y" : "N"),
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

	public static function AddComment_Photo($arFields)
	{
		return CSocNetPhotoCommentEvent::AddComment_Photo($arFields);
	}

	public static function AddComment_Photo_Forum($arFields, $FORUM_ID, $arLog)
	{
		return CSocNetPhotoCommentEvent::AddComment_Photo_Forum($arFields, $FORUM_ID, $arLog);
	}

	public static function AddComment_Photo_Blog($arFields, $BLOG_ID, $arLog)
	{
		return CSocNetPhotoCommentEvent::AddComment_Photo_Blog($arFields, $BLOG_ID, $arLog);
	}
	
	public static function logUFfileShow($arResult, $arParams)
	{
		$result = false;
		if (
			$arParams["arUserField"]["FIELD_NAME"] == "UF_SONET_COM_FILE" 
			|| strpos($arParams["arUserField"]["FIELD_NAME"], "UF_SONET_COM_FILE") === 0
			|| $arParams["arUserField"]["FIELD_NAME"] == "UF_SONET_LOG_FILE"
			|| strpos($arParams["arUserField"]["FIELD_NAME"], "UF_SONET_LOG_FILE") === 0
		)
		{
			if (
				$arParams["arUserField"]["FIELD_NAME"] == "UF_SONET_COM_FILE" 
				|| strpos($arParams["arUserField"]["FIELD_NAME"], "UF_SONET_COM_FILE") === 0
			)
				$type = "comment";
			else
				$type = "post";

			if (sizeof($arResult["VALUE"]) > 0)
			{
				?><div class="feed-com-files">
					<div class="feed-com-files-title"><?=GetMessage("LOG_FILES")?></div>
					<div class="feed-com-files-cont"><?

					foreach ($arResult["VALUE"] as $fileID)
					{
						$arFile = CFile::GetFileArray($fileID);
						if($arFile)
						{
							$name = $arFile["ORIGINAL_NAME"];
							$ext = '';
							$dotpos = strrpos($name, ".");
							if (($dotpos !== false) && ($dotpos+1 < strlen($name)))
								$ext = substr($name, $dotpos+1);
							if (strlen($ext) < 3 || strlen($ext) > 5)
								$ext = '';
							$arFile["EXTENSION"] = $ext;
							$arFile["LINK"] = "/bitrix/components/bitrix/socialnetwork.log.ex/show_file.php?fid=".$fileID."&ltype=".$type;
							$arFile["FILE_SIZE"] = CFile::FormatSize($arFile["FILE_SIZE"]);
							?><div id="wdif-doc-<?=$arFile["ID"]?>" class="feed-com-file-wrap">
								<div class="feed-con-file-name-wrap">
									<div class="feed-con-file-icon feed-file-icon-<?=htmlspecialcharsbx($arFile["EXTENSION"])?>"></div>
									<a target="_blank" href="<?=htmlspecialcharsbx($arFile["LINK"])?>" class="feed-com-file-name"><?=htmlspecialcharsbx($arFile["ORIGINAL_NAME"])?></a>
									<span class="feed-con-file-size">(<?=$arFile["FILE_SIZE"]?>)</span>
								</div>
							</div><?
						}
					}

					?></div>
				</div><?
			}

			$result = true;
		}
		return $result;
	}
	
	function SetUFRights($files, $rights)
	{
		static $arTasks = null;

		if (!CModule::IncludeModule('iblock') || !CModule::IncludeModule('webdav'))
			return;

		if (!is_array($rights) || count($rights) <= 0)
			return false;

		if ($files === null || $files===false)
			return false;
		if (!is_array($files))
			$files = array($files);
			
		$arFiles = array();
		foreach($files as $id)
		{
			$id = intval($id);
			if (intval($id) > 0)
				$arFiles[] = $id;
		}

		if (count($arFiles) <= 0)
			return false;

		if ($arTasks == null)
			$arTasks = CWebDavIblock::GetTasks();

		$arCodes = array();
		foreach($rights as $value)
		{
			if (substr($value, 0, 2) === 'SG')
				$arCodes[] = $value.'_K';
			$arCodes[] = $value;
		}
		$arCodes = array_unique($arCodes);

		$i=0;
		$arViewRights = array();
		$curUserID = 'U'.$GLOBALS['USER']->GetID();
		foreach($arCodes as $right)
		{
			if ($curUserID == $right) // do not override owner's rights
				continue;
			$key = "n".$i++;
			$arViewRights[$key] = array(
				"GROUP_CODE" => $right,
				"TASK_ID" => $arTasks["R"],
			);
		}

		$ibe = new CIBlockElement();
		$dbWDFile = $ibe->GetList(array(), array("ID" => $arFiles, "SHOW_NEW" => "Y"), false, false, array("ID", "NAME", "SECTION_ID", "IBLOCK_ID", "WF_NEW"));
		$iblockIds = array();
		if ($dbWDFile)
		{
			while ($arWDFile = $dbWDFile->Fetch())
			{
				$id = $arWDFile["ID"];

				if ($arWDFile["WF_NEW"] == "Y")
					$ibe->Update($id, array("BP_PUBLISHED" => "Y"));

				if (CIBlock::GetArrayByID($arWDFile['IBLOCK_ID'], "RIGHTS_MODE") === "E")
				{
					$ibRights = CWebDavIblock::_get_ib_rights_object("ELEMENT", $id, $arWDFile["IBLOCK_ID"]);
					$ibRights->SetRights(CWebDavTools::appendRights($ibRights, $arViewRights, $arTasks));
					if(empty($iblockIds[$arWDFile["IBLOCK_ID"]]))
						$iblockIds[$arWDFile["IBLOCK_ID"]] = $arWDFile["IBLOCK_ID"];
				}
			}

			global $CACHE_MANAGER;

			foreach ($iblockIds as $iblockId)
				$CACHE_MANAGER->ClearByTag("iblock_id_".$iblockId);

			unset($iblockId);
		}
	}
	
	public static function GetAvailableGroups($isExtranetUser = false, $isExtranetSite = false)
	{
		static $arSonetGroupIDAvailable = false;

		if (is_array($arSonetGroupIDAvailable))
		{
			return $arSonetGroupIDAvailable;
		}
		else
		{
			$arSonetGroupIDAvailable = array();

			if (!$isExtranetUser)
			{
				$isExtranetUser = (CModule::IncludeModule("extranet") && !CExtranet::IsIntranetUser() ? "Y" : "N");
			}

			if (!$isExtranetSite)
			{
				$isExtranetSite = (CModule::IncludeModule("extranet") && CExtranet::IsExtranetSite() ? "Y" : "N");
			}

			$cache = new CPHPCache;
			$cache_time = 31536000;
			$cache_id = $GLOBALS["USER"]->GetID().($isExtranetUser == "Y" ? "_ex" : "");
			$cache_path = "/sonet/groups_available/".$GLOBALS["USER"]->GetID()."/";

			if ($cache->InitCache($cache_time, $cache_id, $cache_path))
			{
				$arCacheVars = $cache->GetVars();
				$arSonetGroupIDAvailable = $arCacheVars["arGroupID"];
			}
			else
			{
				$cache->StartDataCache($cache_time, $cache_id, $cache_path);
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
					$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_user2group_U".$GLOBALS["USER"]->GetID());
					$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_group");
				}

				$arFilter = array("CHECK_PERMISSIONS" => $GLOBALS["USER"]->GetID());

				if (
					$isExtranetUser == "Y"
					&& $isExtranetSite == "Y"
					&& CModule::IncludeModule("extranet")
				)
				{
					$arFilter["SITE_ID"] = CExtranet::GetExtranetSiteID();
				}

				$rsGroup = CSocNetGroup::GetList(
					array(),
					$arFilter,
					false,
					false,
					array("ID")
				);
				while($arGroup = $rsGroup->Fetch())
				{
					$arSonetGroupIDAvailable[] = $arGroup["ID"];
				}

				$arCacheData = array(
					"arGroupID" => $arSonetGroupIDAvailable
				);

				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					$GLOBALS["CACHE_MANAGER"]->EndTagCache();
				}

				$cache->EndDataCache($arCacheData);
			}

			return $arSonetGroupIDAvailable;
		}
	}

	public static function CanEditComment_Task($arParams)
	{
		$res = false;

		$forumId = COption::GetOptionString("tasks", "task_forum_id", 0, '');

		if (
			!empty($arParams)
			&& !empty($arParams["LOG_SOURCE_ID"])
			&& intval($arParams["LOG_SOURCE_ID"]) > 0
			&& intval($forumId) > 0
			&& CModule::IncludeModule('forum')
		)
		{
			try
			{
				$feed = new \Bitrix\Forum\Comments\Feed(
					intval($forumId),
					array(
						"type" => 'tk',
						"id" => intval($arParams["LOG_SOURCE_ID"]),
						"xml_id" => "TASK_".$arParams["LOG_SOURCE_ID"]
					)
				);
				$res = $feed->getEntity()->canEdit();
			}
			catch (Exception $e){
			}
		}

		return $res;
	}

	public static function CanEditOwnComment_Task($arParams)
	{
		$res = false;

		$forumId = COption::GetOptionString("tasks", "task_forum_id", 0, '');

		if (
			!empty($arParams)
			&& !empty($arParams["LOG_SOURCE_ID"])
			&& intval($arParams["LOG_SOURCE_ID"]) > 0
			&& intval($forumId) > 0
			&& CModule::IncludeModule('forum')
		)
		{
			try
			{
				$feed = new \Bitrix\Forum\Comments\Feed(
					intval($forumId),
					array(
						"type" => 'tk',
						"id" => intval($arParams["LOG_SOURCE_ID"]),
						"xml_id" => "TASK_".$arParams["LOG_SOURCE_ID"]
					)
				);
				$res = $feed->getEntity()->canEditOwn();
			}
			catch (Exception $e){

			}
		}

		return $res;
	}

	public static function DeleteComment_Task($arFields)
	{
		$arRes = array();

		$messageId = intval($arFields["SOURCE_ID"]);
		$forumId = COption::GetOptionString("tasks", "task_forum_id", 0, '');

		if (
			!empty($arFields)
			&& !empty($arFields["LOG_SOURCE_ID"])
			&& intval($arFields["LOG_SOURCE_ID"]) > 0
			&& intval($forumId) > 0
			&& CModule::IncludeModule('forum')
		)
		{
			$feed = new \Bitrix\Forum\Comments\Feed(
				intval($forumId),
				array(
					"type" => 'tk',
					"id" => intval($arFields["LOG_SOURCE_ID"]),
					"xml_id" => "TASK_".$arFields["LOG_SOURCE_ID"]
				)
			);

			if (!$feed->delete($messageId))
			{
				$arRes["ERROR"] = "";
				foreach($feed->getErrors() as $error)
				{
					$arRes["ERROR"] .= $error->getMessage();
				}
			}
			else
			{
				$arRes["NOTES"] = GetMessage("SONET_DELETE_COMMENT_SOURCE_SUCCESS");
			}
		}
		else
		{
			$arRes["ERROR"] = GetMessage("SONET_DELETE_COMMENT_SOURCE_ERROR");
		}

		return $arRes;
	}

	public static function GetForumCommentMetaData($logEventId)
	{
		static $arData = array(
//			"blog_post" => array("BLOG", "BG"),
			"tasks" => array("TASK", "TK", "FORUM|COMMENT"),
			"forum" => array("FORUM", "FM", "FORUM|COMMENT"),
			"photo_photo" => array("PHOTO", "PH", "FORUM|COMMENT"),
			"sonet" => array("SOCNET", "SC", ""),
			"calendar" => array("EVENT", "EV", ""),
			"lists_new_element" => array("WF", "WF", ""),
			"news" => array("IBLOCK", "IB", ""),
			"wiki" => array("IBLOCK", "IB", ""),
			"timeman_entry"=> array("TIMEMAN_ENTRY", "TE", ""),
			"report"=> array("TIMEMAN_REPORT", "TR", ""),
		);

		$arRes = false;

		if (isset($arData[$logEventId]))
		{
			$arRes = $arData[$logEventId];
		}

		return $arRes;
	}
}

class logTextParser extends CTextParser
{
	var $matchNum = 0;
	var $matchNum2 = 0;
	
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

			public function sonet_sortlen($a, $b) 
			{
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
		
		AddEventHandler("main", "TextParserAfterTags", Array(&$this, "ParserUser"));
	}

	public function convert($text, $arImages = array(), $allow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "TABLE" => "Y", "CUT_ANCHOR" => "N", "SHORT_ANCHOR" => "N"), $arParams = Array())
	{
		$this->allow = array(
			"HTML" => ($allow["HTML"] == "Y" ? "Y" : "N"),
			"NL2BR" => ($allow["NL2BR"] == "Y" ? "Y" : "N"),
			"LOG_NL2BR" => ($allow["LOG_NL2BR"] == "N" ? "N" : "Y"),
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
			"HEADER" => ($allow["HEADER"] == "N" ? "N" : "Y"),
			"USERFIELDS" => (!!$allow["USERFIELDS"] ? $allow["USERFIELDS"] : "N"),
			"USER" => ($allow["USER"] == "N" ? "N" : "Y")
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
				"#<img[^>]+src\s*=[\s'\"]*(((http|https|ftp)://[.-_:a-z0-9@]+)*(\/[-_/=:.a-z0-9@{}&?%]+)+)[\s'\"]*[^>]*>#is".BX_UTF_PCRE_MODIFIER,
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

		if ($this->allow["LOG_NL2BR"] == "Y")
		{
			$text = str_replace("<br />", "\n", $text);
		}
		$text = $this->convertText($text);
		if ($this->allow["LOG_NL2BR"] == "Y")
		{
			$text = str_replace("\n", "<br />", $text);
		}

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
		{
			return preg_replace(
				array(
					"/\[quote([^\]\<\>])*\]/i".BX_UTF_PCRE_MODIFIER,
					"/\[\/quote([^\]\<\>])*\]/i".BX_UTF_PCRE_MODIFIER,
				),
				"",
			$text);
		}
		else
		{
			return parent::convert_quote_tag($text);
		}
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
		{
			return parent::convert_font_attr($attr, $value, $text);
		}
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
	
	public static function ParserUser(&$text, &$obj)
	{

		if($obj->allow["USER"] != "N" && is_callable(array($obj, 'convert_user_callback')))
		{
			$obj->matchNum = 1;
			$obj->matchNum2 = 2;
			$text = preg_replace_callback(
				"/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/is".BX_UTF_PCRE_MODIFIER, 
				array($obj, "convert_user_callback"),
				$text
			);
		}
	}

	public function convert_user($userId = 0, $name = "")
	{
		$userId = intval($userId);
		if($userId > 0)
		{
			$anchor_id = RandString(8);
			return
				'<a class="blog-p-user-name'.(is_array($GLOBALS["arExtranetUserID"]) && in_array($userId, $GLOBALS["arExtranetUserID"]) ? ' feed-extranet-mention' : '').'" id="bp_'.$anchor_id.'" href="'.CComponentEngine::MakePathFromTemplate($this->pathToUser, array("user_id" => $userId)).'">'.$name.'</a>'.
				(
					!$this->bMobile
						? '<script type="text/javascript">BX.tooltip(\''.$userId.'\', "bp_'.$anchor_id.'", "'.CUtil::JSEscape($this->ajaxPage).'");</script>'
						: ''
				);
		}
		return;
	}

	private function convert_user_callback($m)
	{
		return $this->convert_user($m[$this->matchNum], $m[$this->matchNum2]);
	}
}

class CSocNetLogComponent
{
	private $arItems = null;

	public function __construct($params)
	{
		$this->arItems = $params["arItems"];
	}

	public function OnBeforeSonetLogFilterFill(&$arPageParamsToClear, &$arItemsTop, &$arItems)
	{
		$arItems = $this->arItems;
	}

	public static function ConvertPresetToFilters($arPreset, $arParams = array())
	{
		$arFilter = array();

		foreach ($arPreset as $tmp_id_1 => $arPresetFilterTmp)
		{
			$bCorrect = true;

			if (
				is_array($arPresetFilterTmp["FILTER"])
				&& (
					(
						!empty($arPresetFilterTmp["FILTER"]["EVENT_ID"])
						&& is_array($arPresetFilterTmp["FILTER"]["EVENT_ID"])
						&& count(array_diff($arPresetFilterTmp["FILTER"]["EVENT_ID"], array("tasks", "timeman_entry", "report"))) <= 0
						&& !IsModuleInstalled("tasks")
						&& !IsModuleInstalled("timeman")
					)
					|| (
						!empty($arPresetFilterTmp["FILTER"]["EXACT_EVENT_ID"])
						&& $arPresetFilterTmp["FILTER"]["EXACT_EVENT_ID"] == "lists_new_element"
						&& (
							!IsModuleInstalled("lists")
							|| !IsModuleInstalled("bizproc")
							|| !IsModuleInstalled("intranet")
							|| COption::GetOptionString("lists", "turnProcessesOn", "Y") != 'Y'
							|| (
								isset($arParams["GROUP_ID"])
								&& intval($arParams["GROUP_ID"]) > 0
							)
							|| (
								CModule::IncludeModule('extranet')
								&& CExtranet::IsExtranetSite()
							)
						)
					)
					|| (
						!empty($arPresetFilterTmp["ID"])
						&& $arPresetFilterTmp["ID"] == "extranet"
						&& (
							!IsModuleInstalled("extranet")
							|| !COption::GetOptionString("extranet", "extranet_site", false)
							|| COption::GetOptionString("extranet", "extranet_site") == SITE_ID
						)
					)
				)
			)
			{
				continue;
			}

			if (array_key_exists("NAME", $arPresetFilterTmp))
			{
				switch(strtoupper($arPresetFilterTmp["NAME"]))
				{
					case "#WORK#":
						$arPresetFilterTmp["NAME"] = GetMessage("SONET_INSTALL_LOG_PRESET_WORK"); // lang/include.php
						break;
					case "#FAVORITES#":
						$arPresetFilterTmp["NAME"] = GetMessage("SONET_INSTALL_LOG_PRESET_FAVORITES");
						break;
					case "#IMPORTANT#":
						$arPresetFilterTmp["NAME"] = GetMessage("SONET_INSTALL_LOG_PRESET_IMPORTANT");
						break;
					case "#MY#":
						$arPresetFilterTmp["NAME"] = GetMessage("SONET_INSTALL_LOG_PRESET_MY");
						break;
					case "#BIZPROC#":
						$arPresetFilterTmp["NAME"] = GetMessage("SONET_INSTALL_LOG_PRESET_BIZPROC");
						break;
					case "#EXTRANET#":
						$arPresetFilterTmp["NAME"] = GetMessage("SONET_INSTALL_LOG_PRESET_EXTRANET");
						break;
				}
			}

			if (
				array_key_exists("FILTER", $arPresetFilterTmp)
				&& is_array($arPresetFilterTmp["FILTER"])
			)
			{
				foreach($arPresetFilterTmp["FILTER"] as $tmp_id_2 => $filterTmp)
				{
					if (
						(
							!is_array($filterTmp)
							&& $filterTmp == "#CURRENT_USER_ID#"
						)
						|| (
							is_array($filterTmp)
							&& in_array("#CURRENT_USER_ID#", $filterTmp)
						)
					)
					{
						if (!$GLOBALS["USER"]->IsAuthorized())
						{
							$bCorrect = false;
							break;
						}
						elseif (!is_array($filterTmp))
						{
							$arPresetFilterTmp["FILTER"][$tmp_id_2] = $GLOBALS["USER"]->GetID();
						}
						elseif (is_array($filterTmp))
						{
							foreach($filterTmp as $tmp_id_3 => $valueTmp)
							{
								if ($valueTmp == "#CURRENT_USER_ID#")
								{
									$arPresetFilterTmp["FILTER"][$tmp_id_2][$tmp_id_3] = $GLOBALS["USER"]->GetID();
								}
							}
						}
					}
					elseif (
						(
							!is_array($filterTmp)
							&& $filterTmp == "#EXTRANET_SITE_ID#"
						)
						|| (
							is_array($filterTmp)
							&& in_array("#EXTRANET_SITE_ID#", $filterTmp)
						)
					)
					{
						if (
							!IsModuleInstalled("extranet")
							|| !COption::GetOptionString("extranet", "extranet_site", false)
						)
						{
							$bCorrect = false;
							break;
						}
						elseif (!is_array($filterTmp))
						{
							$arPresetFilterTmp["FILTER"][$tmp_id_2] = COption::GetOptionString("extranet", "extranet_site");
						}
						elseif (is_array($filterTmp))
						{
							foreach($filterTmp as $tmp_id_3 => $valueTmp)
							{
								if ($valueTmp == "#EXTRANET_SITE_ID#")
								{
									$arPresetFilterTmp["FILTER"][$tmp_id_2][$tmp_id_3] = COption::GetOptionString("extranet", "extranet_site");
								}
							}
						}
					}
				}
			}

			if ($bCorrect)
			{
				$arFilter[$arPresetFilterTmp["ID"]] = $arPresetFilterTmp;
			}
		}

		return $arFilter;
	}

	public static function OnSonetLogFilterProcess($preset_filter_top_id, $preset_filter_id, $arResultPresetFiltersTop, $arResultPresetFilters)
	{
		$arResult = array();

		if (
			strlen($preset_filter_id) > 0
			&& array_key_exists($preset_filter_id, $arResultPresetFilters)
			&& isset($arResultPresetFilters[$preset_filter_id]["FILTER"])
			&& is_array($arResultPresetFilters[$preset_filter_id]["FILTER"])
		)
		{
			if (array_key_exists("EXACT_EVENT_ID", $arResultPresetFilters[$preset_filter_id]["FILTER"]))
			{
				$arResult["PARAMS"]["EXACT_EVENT_ID"] = $arResultPresetFilters[$preset_filter_id]["FILTER"]["EXACT_EVENT_ID"];
				$arResult["GET_COMMENTS"] = false;
			}

			if (array_key_exists("!EXACT_EVENT_ID", $arResultPresetFilters[$preset_filter_id]["FILTER"]))
			{
				$arResult["PARAMS"]["!EXACT_EVENT_ID"] = $arResultPresetFilters[$preset_filter_id]["FILTER"]["!EXACT_EVENT_ID"];
				$arResult["GET_COMMENTS"] = false;
			}

			if (array_key_exists("EVENT_ID", $arResultPresetFilters[$preset_filter_id]["FILTER"]))
			{
				$arResult["PARAMS"]["EVENT_ID"] = $arResultPresetFilters[$preset_filter_id]["FILTER"]["EVENT_ID"];
				$arResult["GET_COMMENTS"] = false;
			}

			if (array_key_exists("CREATED_BY_ID", $arResultPresetFilters[$preset_filter_id]["FILTER"]))
			{
				$arResult["PARAMS"]["CREATED_BY_ID"] = $arResultPresetFilters[$preset_filter_id]["FILTER"]["CREATED_BY_ID"];
			}

			if (
				array_key_exists("FAVORITES_USER_ID", $arResultPresetFilters[$preset_filter_id]["FILTER"])
				&& $arResultPresetFilters[$preset_filter_id]["FILTER"]["FAVORITES_USER_ID"] == "Y"
			)
			{
				$arResult["PARAMS"]["FAVORITES"] = "Y";
				$arResult["GET_COMMENTS"] = false;
			}

			$arResult["PARAMS"]["SET_LOG_COUNTER"] = $arParams["SET_LOG_PAGE_CACHE"] = "N";
			$arResult["PARAMS"]["USE_FOLLOW"] = "N";

			if (array_key_exists("SITE_ID", $arResultPresetFilters[$preset_filter_id]["FILTER"]))
			{
				$arResult["PARAMS"]["FILTER_SITE_ID"] = $arResultPresetFilters[$preset_filter_id]["FILTER"]["SITE_ID"];
			}
		}

		return $arResult;
	}

	public static function GetSiteByDepartmentId($arDepartmentId)
	{
		if (!is_array($arDepartmentId))
		{
			$arDepartmentId = array($arDepartmentId);
		}

		$bFound = false;
		$arDefaultSite = false;

		$dbSitesList = CSite::GetList($b = "SORT", $o = "asc", array("ACTIVE" => "Y")); // cache used
		while ($arSite = $dbSitesList->GetNext())
		{
			if (
				!$arDefaultSite
				&& $arSite['DEF'] == 'Y'
			)
			{
				$arDefaultSite = $arSite;
			}

			$siteRootDepartmentId = COption::GetOptionString("main", "wizard_departament", false, $arSite["LID"], true);
			if ($siteRootDepartmentId)
			{
				if (in_array($siteRootDepartmentId, $arDepartmentId))
				{
					$arResult = $arSite;
					$bFound = true;
				}
				else
				{
					$arSubStructure = CIntranetUtils::getSubStructure($siteRootDepartmentId);
					$arSiteDepartmentId = array_keys($arSubStructure["DATA"]);

					foreach($arDepartmentId as $userDepartmentId)
					{
						if(in_array($userDepartmentId, $arSiteDepartmentId))
						{
							$arResult = $arSite;
							$bFound = true;
							break;
						}
					}
				}

				if($bFound)
				{
					break;
				}
			}
		}

		if (!$bFound)
		{
			$arResult = $arDefaultSite;
		}

		return $arResult;
	}

	public static function saveRawFilesToUF($arAttachedFilesRaw, $ufCode, &$arFields)
	{
		static $isDiskEnabled = false;
		static $isWebDavEnabled = false;

		if ($isDiskEnabled === false)
		{
			$isDiskEnabled = (
				\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false)
				&& CModule::includeModule('disk')
				&& ($storage = \Bitrix\Disk\Driver::getInstance()->getStorageByUserId($GLOBALS["USER"]->GetID()))
				&& ($folder = $storage->getFolderForUploadedFiles($GLOBALS["USER"]->GetID()))
					? "Y"
					: "N"
			);
		}

		if ($isWebDavEnabled === false)
		{
			$isWebDavEnabled = (
				IsModuleInstalled('webdav')
					? "Y"
					: "N"
			);
		}

		if (empty($arFields[$ufCode]))
		{
			$arFields[$ufCode] = array();
		}

		$arRelation = array();

		foreach ($arAttachedFilesRaw as $attachedFileRow)
		{
			if (
				!empty($attachedFileRow["base64"])
				&& !empty($attachedFileRow["url"])
			)
			{
				$fileContent = base64_decode($attachedFileRow["base64"]);
				$arUri = parse_url($attachedFileRow["url"]);
				if (
					!empty($arUri)
					&& !empty($arUri["path"])
				)
				{
					$fileName = $arUri["path"];
				}

				if (
					!empty($fileContent)
					&& !empty($fileName)
				)
				{
					$fileName = CTempFile::GetFileName($fileName);

					if(CheckDirPath($fileName))
					{
						file_put_contents($fileName, $fileContent);
						$arFile = CFile::MakeFileArray($fileName);

						if(is_array($arFile))
						{
							$resultId = false;
							if ($isDiskEnabled == "Y")
							{
								$file = $folder->uploadFile(
									$arFile, // file array
									array(
										'NAME' => $arFile["name"],
										'CREATED_BY' => $GLOBALS["USER"]->GetID()
									),
									array(),
									true
								);

								if ($file)
								{
									$resultId = \Bitrix\Disk\Uf\FileUserType::NEW_FILE_PREFIX.$file->getId();
								}
							}
							elseif ($isWebDavEnabled == "Y")
							{
								$webDavData = CWebDavIblock::getRootSectionDataForUser($GLOBALS["USER"]->GetID());
								if (is_array($webDavData))
								{
									$webDavObject = new CWebDavIblock(
										$webDavData["IBLOCK_ID"],
										"",
										array(
											"ROOT_SECTION_ID" => $webDavData["SECTION_ID"],
											"DOCUMENT_TYPE" => array("webdav", 'CIBlockDocumentWebdavSocnet', 'iblock_'.$webDavData['SECTION_ID'].'_user_'.intval($GLOBALS["USER"]->GetID()))
										)
									);

									if ($webDavObject)
									{
										$arParent = $webDavObject->GetObject(
											array(
												"section_id" => $webDavObject->GetMetaID("DROPPED")
											)
										);

										if (!$arParent["not_found"])
										{
											$path = $webDavObject->_get_path($arParent["item_id"], false);
											$tmpName = str_replace(array(":", ".", "/", "\\"), "_", ConvertTimeStamp(time(), "FULL"));
											$tmpOptions = array("path" => str_replace("//", "/", $path."/".$tmpName));
											$arParent = $webDavObject->GetObject($tmpOptions);
											if ($arParent["not_found"])
											{
												$rMKCOL = $webDavObject->MKCOL($tmpOptions);
												if (intval($rMKCOL) == 201)
												{
													$webDavData["SECTION_ID"] = $webDavObject->arParams["changed_element_id"];
												}
											}
											else
											{
												$webDavData["SECTION_ID"] = $arParent['item_id'];
												if (!$webDavObject->CheckUniqueName($tmpName, $webDavData["SECTION_ID"], $tmpRes))
												{
													$path = $webDavObject->_get_path($webDavData["SECTION_ID"], false);
													$tmpName = randString(6);
													$tmpOptions = array("path" => str_replace("//", "/", $path."/".$tmpName));
													$rMKCOL = $webDavObject->MKCOL($tmpOptions);
													if (intval($rMKCOL) == 201)
													{
														$webDavData["SECTION_ID"] = $webDavData->arParams["changed_element_id"];
													}
												}
											}
										}

										$options = array(
											"new" => true,
											'dropped' => true,
											"arFile" => $arFile,
											"arDocumentStates" => false,
											"arUserGroups" => array_merge($webDavObject->USER["GROUPS"], array("Author")),
											"FILE_NAME" => $arFile["name"],
											"IBLOCK_ID" => $webDavData["IBLOCK_ID"],
											"IBLOCK_SECTION_ID" => $webDavData["SECTION_ID"],
											"USER_FIELDS" => array()
										);

										$GLOBALS['USER_FIELD_MANAGER']->EditFormAddFields($webDavObject->GetUfEntity(), $options['USER_FIELDS']);

										$GLOBALS["DB"]->StartTransaction();

										if (!$webDavObject->put_commit($options))
										{
											$GLOBALS["DB"]->Rollback();
										}
										else
										{
											$GLOBALS["DB"]->Commit();
											$resultId = $options['ELEMENT_ID'];
										}
									}
								}
							}
							else // for main
							{
								$resultId = CFile::SaveFile($arFile, $arFile["MODULE_ID"]);
							}

							if ($resultId)
							{
								$arFields[$ufCode][] = $resultId;
							}

							if (!empty($attachedFileRow["id"]))
							{
								$arRelation[$attachedFileRow["id"]] = $resultId;
							}
						}
					}
				}
			}
		}

		if (!empty($arRelation))
		{
			$arFields["DETAIL_TEXT"] = preg_replace_callback(
				"/\[DISK\s+FILE\s+ID\s*=\s*pseudo@([\d]+)\]/is".BX_UTF_PCRE_MODIFIER,
				function ($matches) use ($arRelation, $isDiskEnabled, $isWebDavEnabled)
				{
					if (isset($arRelation[intval($matches[1])]))
					{
						if ($isDiskEnabled == "Y")
						{
							return "[DISK FILE ID=".$arRelation[intval($matches[1])]."]";
						}
						elseif ($isWebDavEnabled == "Y")
						{
							return "[DOCUMENT ID=".intval($arRelation[intval($matches[1])])."]";
						}
						else
						{
							return "[DISK FILE ID=pseudo@".$matches[1]."]";
						}
					}
					else
					{
						return "[DISK FILE ID=pseudo@".$matches[1]."]";
					}
				},
				$arFields["DETAIL_TEXT"]
			);
		}
	}

	public static function checkEmptyUFValue($fieldName)
	{
		if (
			isset($GLOBALS[$fieldName])
			&& is_array($GLOBALS[$fieldName])
			&& count($GLOBALS[$fieldName]) == 1
			&& $GLOBALS[$fieldName][0] == 'empty'
		)
		{
			$GLOBALS[$fieldName] = array();
		}
	}

	public static function isSetTrafficNeeded($arParams)
	{
		if (
			!isset($arParams["TRAFFIC_SET_PERIOD"])
			|| intval($arParams["TRAFFIC_SET_PERIOD"]) <= 0
		)
		{
			$arParams["TRAFFIC_SET_PERIOD"] = 60*60*24;
		}

		return (
			intval($arParams["PAGE_NUMBER"]) == 1
			&& $arParams["GROUP_CODE"] == '**'
			&& (time() - $arParams['TRAFFIC_LAST_DATE_TS']) > $arParams["TRAFFIC_SET_PERIOD"]
		);
	}

	public static function processDateTimeFormatParams(&$arParams = array())
	{
		global $DB;

		if (
			!is_array($arParams)
			|| empty($arParams)
		)
		{
			return;
		}

		$arParams["DATE_TIME_FORMAT"] = trim(
			!empty($arParams['DATE_TIME_FORMAT'])
				? ($arParams['DATE_TIME_FORMAT'] == 'FULL'
					? $DB->DateFormatToPHP(FORMAT_DATETIME)
					: $arParams['DATE_TIME_FORMAT']
				)
				: $DB->DateFormatToPHP(FORMAT_DATETIME)
		);
		$arParams["DATE_TIME_FORMAT"] = preg_replace('/[\/.,\s:][s]/', '', $arParams["DATE_TIME_FORMAT"]);
		$arParams["DATE_TIME_FORMAT_WITHOUT_YEAR"] = (
			isset($arParams["DATE_TIME_FORMAT_WITHOUT_YEAR"])
				? $arParams["DATE_TIME_FORMAT_WITHOUT_YEAR"]
				: preg_replace('/[\/.,\s-][Yyo]/', '', $arParams["DATE_TIME_FORMAT"])
		);
		$arParams["TIME_FORMAT"] = (
			isset($arParams["TIME_FORMAT"])
				? $arParams["TIME_FORMAT"]
				: preg_replace('/[\/.,\s]+$/', '', preg_replace('/^[\/.,\s]+/', '', preg_replace('/[dDjlFmMnYyo]/', '', $arParams["DATE_TIME_FORMAT"])))
		);
	}

	public static function getDateTimeFormatted($timestamp, $arFormatParams)
	{
		$arFormat = Array(
			"tommorow" => "tommorow, ".$arFormatParams["TIME_FORMAT"],
			"today" => "today, ".$arFormatParams["TIME_FORMAT"],
			"yesterday" => "yesterday, ".$arFormatParams["TIME_FORMAT"],
			"" => (
				date("Y", $timestamp) == date("Y")
					? $arFormatParams["DATE_TIME_FORMAT_WITHOUT_YEAR"]
					: $arFormatParams["DATE_TIME_FORMAT"]
			)
		);

		return (
			strcasecmp(LANGUAGE_ID, 'EN') !== 0
			&& strcasecmp(LANGUAGE_ID, 'DE') !== 0
				? ToLower(FormatDate($arFormat, $timestamp, time()+CTimeZone::GetOffset()))
				: FormatDate($arFormat, $timestamp, time()+CTimeZone::GetOffset())
		);
	}

	public static function getCommentRights($arParams)
	{
		$arResult = array(
			"COMMENT_RIGHTS_EDIT" => "N",
			"COMMENT_RIGHTS_DELETE" => "N"
		);

		$logEventId = (
			isset($arParams["EVENT_ID"])
			&& strlen($arParams["EVENT_ID"]) > 0
				? $arParams["EVENT_ID"]
				: false
		);

		$logSourceId = (
			isset($arParams["SOURCE_ID"])
			&& intval($arParams["SOURCE_ID"]) > 0
				? intval($arParams["SOURCE_ID"])
				: false
		);

		$bCheckAdminSession = (
			!isset($arParams["CHECK_ADMIN_SESSION"])
			|| $arParams["CHECK_ADMIN_SESSION"] != "N"
		);

		$arCommentEventMeta = CSocNetLogTools::FindLogCommentEventByLogEventID($logEventId);

		$bHasEditCallback = (
			is_array($arCommentEventMeta)
			&& isset($arCommentEventMeta["UPDATE_CALLBACK"])
			&& (
				$arCommentEventMeta["UPDATE_CALLBACK"] == "NO_SOURCE"
				|| is_callable($arCommentEventMeta["UPDATE_CALLBACK"])
			)
		);

		$bHasDeleteCallback = (
			is_array($arCommentEventMeta)
			&& isset($arCommentEventMeta["DELETE_CALLBACK"])
			&& (
				$arCommentEventMeta["DELETE_CALLBACK"] == "NO_SOURCE"
				|| is_callable($arCommentEventMeta["DELETE_CALLBACK"])
			)
		);

		if (
			$bHasEditCallback
			|| $bHasDeleteCallback
		)
		{
			$arEventMeta = CSocNetLogTools::FindLogEventByID($logEventId);

			if (
				!empty($arEventMeta)
				&& !empty($arEventMeta["COMMENT_EVENT"])
				&& !empty($arEventMeta["COMMENT_EVENT"]["METHOD_CANEDIT"])
			)
			{
				$res = call_user_func($arEventMeta["COMMENT_EVENT"]["METHOD_CANEDIT"], array(
					"LOG_SOURCE_ID" => $logSourceId
				));

				if ($res)
				{
					$arResult["COMMENT_RIGHTS_EDIT"] = ($bHasEditCallback ? "ALL" : "N");
					$arResult["COMMENT_RIGHTS_DELETE"] = ($bHasDeleteCallback ? "ALL" : "N");
				}
				else
				{
					if (!empty($arEventMeta["COMMENT_EVENT"]["METHOD_CANEDITOWN"]))
					{
						$res = call_user_func($arEventMeta["COMMENT_EVENT"]["METHOD_CANEDITOWN"], array(
							"LOG_SOURCE_ID" => $logSourceId
						));

						if ($res)
						{
							$arResult["COMMENT_RIGHTS_EDIT"] = ($bHasEditCallback ? "OWN" : "N");
							$arResult["COMMENT_RIGHTS_DELETE"] = ($bHasDeleteCallback ? "OWN" : "N");
						}
					}
					elseif ($GLOBALS["USER"]->IsAuthorized())
					{
						$arResult["COMMENT_RIGHTS_EDIT"] = (
							$bHasEditCallback
								? (IsModuleInstalled("intranet") ? "OWN" : "OWNLAST")
								: "N"
						);
						$arResult["COMMENT_RIGHTS_DELETE"] = (
							$bHasDeleteCallback
								? (IsModuleInstalled("intranet") ? "OWN" : "OWNLAST")
								: "N"
						);
					}
				}
			}
			elseif (CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, $bCheckAdminSession))
			{
				$arResult["COMMENT_RIGHTS_EDIT"] = ($bHasEditCallback ? "ALL" : "N");
				$arResult["COMMENT_RIGHTS_DELETE"] = ($bHasDeleteCallback ? "ALL" : "N");
			}
			elseif ($GLOBALS["USER"]->IsAuthorized())
			{
				$arResult["COMMENT_RIGHTS_EDIT"] = (
					$bHasEditCallback
						? (IsModuleInstalled("intranet") ? "OWN" : "OWNLAST")
						: "N"
				);
				$arResult["COMMENT_RIGHTS_DELETE"] = (
					$bHasDeleteCallback
						? (IsModuleInstalled("intranet") ? "OWN" : "OWNLAST")
						: "N"
				);
			}
		}

		return $arResult;
	}

	public static function canUserChangeComment($arParams)
	{
		$res = false;

		if (!is_array($arParams))
		{
			$arParams = array();
		}

		if (empty($arParams["LOG_EVENT_ID"]))
		{
			return $res;
		}

		if (
			!isset($arParams["USER_ID"])
			|| intval($arParams["USER_ID"]) <= 0
		)
		{
			$arParams["USER_ID"] = $GLOBALS["USER"]->GetId();
		}

		if (!isset($arParams["ACTION"]))
		{
			$arParams["ACTION"] = "edit";
		}

		$arParams["ACTION"] = ToUpper($arParams["ACTION"]);

		$rights = CSocNetLogComponent::getCommentRights(array(
			"EVENT_ID" => $arParams["LOG_EVENT_ID"],
			"SOURCE_ID" => (isset($arParams["LOG_SOURCE_ID"]) ? intval($arParams["LOG_SOURCE_ID"]) : false),
			"CHECK_ADMIN_SESSION" => (isset($arParams["CHECK_ADMIN_SESSION"]) && $arParams["CHECK_ADMIN_SESSION"] == "N" ? "N" : "Y")
		));

		$key = ($arParams["ACTION"] == "EDIT" ? "COMMENT_RIGHTS_EDIT" : "COMMENT_RIGHTS_DELETE");

		if (
			$rights[$key] == "OWNLAST"
			&& !empty($arParams["LOG_ID"])
			&& intval($arParams["LOG_ID"]) > 0
			&& !empty($arParams["COMMENT_ID"])
			&& intval($arParams["COMMENT_ID"]) > 0
		)
		{
			$rsResCheck = CSocNetLogComments::GetList(
				array("ID" => "DESC"),
				array(
					"LOG_ID" => intval($arParams["LOG_ID"])
				),
				false,
				false,
				array("ID")
			);
			if (
				($arResCheck = $rsResCheck->Fetch())
				&& ($arResCheck["ID"] == intval($arParams["COMMENT_ID"]))
				&& !empty($arParams["COMMENT_USER_ID"])
				&& intval($arParams["COMMENT_USER_ID"]) > 0
				&& intval($arParams["COMMENT_USER_ID"]) == intval($arParams["USER_ID"])
			)
			{
				$res = true;
			}
		}
		elseif (
			$rights[$key] == "ALL"
			|| (
				$rights[$key] == "OWN"
				&& !empty($arParams["COMMENT_USER_ID"])
				&& intval($arParams["COMMENT_USER_ID"]) > 0
				&& intval($arParams["COMMENT_USER_ID"]) == intval($arParams["USER_ID"])
			)
		)
		{
			$res = true;
		}

		return $res;
	}

	public static function getExtranetRedirectSite($extranetSiteId)
	{
		global $USER;

		$arRedirectSite = false;

		if ($USER->IsAuthorized())
		{
			$rsCurrentUser = CUser::GetById($USER->GetId());
			if ($arCurrentUser = $rsCurrentUser->Fetch())
			{
				$bCurrentUserIntranet = (
					!empty($arCurrentUser["UF_DEPARTMENT"])
					&& is_array($arCurrentUser["UF_DEPARTMENT"])
					&& intval($arCurrentUser["UF_DEPARTMENT"][0]) > 0
				);

				if (
					SITE_ID == $extranetSiteId
					&& $bCurrentUserIntranet
					&& !CSocNetUser::IsCurrentUserModuleAdmin()
				) // extranet -> intranet
				{
					$arRedirectSite = CSocNetLogComponent::GetSiteByDepartmentId($arCurrentUser["UF_DEPARTMENT"]);
					if ($arRedirectSite["LID"] == SITE_ID)
					{
						$arRedirectSite = false;
					}
				}

				if (
					SITE_ID != $extranetSiteId
					&& !$bCurrentUserIntranet
				) // intranet -> extranet
				{
					$rsRedirectSite = CSite::GetList($b = "SORT", $o = "asc", array("ACTIVE" => "Y", "LID" => $extranetSiteId)); // cache used
					$arRedirectSite = $rsRedirectSite->Fetch();
				}
			}
		}

		return $arRedirectSite;
	}

	public static function redirectExtranetSite($arRedirectSite, $componentPage, $arVariables, $arDefaultUrlTemplates404, $entity = "user")
	{
		if ($entity != "user")
		{
			$entity = "workgroup";
		}

		$url = (
			strlen(trim($arRedirectSite["SERVER_NAME"])) > 0
			&& $arRedirectSite["SERVER_NAME"] != SITE_SERVER_NAME
				? (CMain::IsHTTPS() ? "https" : "http")."://".$arRedirectSite["SERVER_NAME"]
				: ''
		).
		COption::GetOptionString("socialnetwork", ($entity == "user" ? "user_page" : "workgroups_page"), false, $arRedirectSite["LID"]).
		CComponentEngine::MakePathFromTemplate(
			$arDefaultUrlTemplates404[$componentPage],
			$arVariables
		);

		LocalRedirect($url);
	}

	public static function getCommentByRequest($commentId, $postId, $action = false, $checkPerms = true, $checkAdminSession = true)
	{
		$commentId = intval($commentId);
		$postId = intval($postId);

		$arOrder = array();

		$rsLog = CSocNetLog::GetList(
			array(),
			array(
				"ID" => $postId
			),
			false,
			false,
			array("EVENT_ID", "SOURCE_ID")
		);

		if ($arLog = $rsLog->Fetch())
		{
			$arCommentEvent = CSocNetLogTools::FindLogCommentEventByLogEventID($arLog["EVENT_ID"]);

			$arFilter = array(
				"EVENT_ID" => $arCommentEvent["EVENT_ID"]
			);

			if (
				isset($arCommentEvent["DELETE_CALLBACK"])
				&& $arCommentEvent["DELETE_CALLBACK"] != "NO_SOURCE"
			)
			{
				$arFilter["SOURCE_ID"] = $commentId; // forum etc.
			}
			else
			{
				$arFilter["ID"] = $commentId; // socialnetwork
			}

			$dbRes = CSocNetLogComments::GetList(
				$arOrder,
				$arFilter,
				false,
				false,
				array("ID", "EVENT_ID", "MESSAGE", "USER_ID", "SOURCE_ID", "LOG_SOURCE_ID", "UF_*")
			);

			if ($arRes = $dbRes->Fetch())
			{
				if ($checkPerms)
				{
					$bAllow = CSocNetLogComponent::canUserChangeComment(array(
						"ACTION" => $action,
						"LOG_ID" => $postId,
						"LOG_EVENT_ID" => $arLog["EVENT_ID"],
						"LOG_SOURCE_ID" => $arLog["SOURCE_ID"],
						"COMMENT_ID" => $arRes["ID"],
						"COMMENT_USER_ID" => $arRes["USER_ID"],
						"CHECK_ADMIN_SESSION" => ($checkAdminSession ? "Y" : "N")
					));
				}
				else
				{
					$bAllow = true;
				}

				if (!$bAllow)
				{
					$arRes = false;
				}
				else
				{
					if ($action == "edit") // data needed only for edit
					{
						$arUFMeta = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("SONET_COMMENT", 0, LANGUAGE_ID);
						$arRes["UF"] = array();

						foreach($arUFMeta as $field_name => $arUF)
						{
							if (
								array_key_exists($field_name, $arRes)
								&& !empty($arRes[$field_name])
							)
							{
								$arRes["UF"][$field_name] = $arUFMeta[$field_name];
								$arRes["UF"][$field_name]["VALUE"] = $arRes[$field_name];
								$arRes["UF"][$field_name]["ENTITY_VALUE_ID"] = $arRes["ID"];
								unset($arRes[$field_name]);
							}
						}
					}
				}
			}

			return $arRes;
		}

		return false;
	}

	public static function getCommentRatingType($logEventId, $logId = false)
	{
		$res = "LOG_COMMENT";

		$arCommentEventMeta = CSocNetLogTools::FindLogCommentEventByLogEventID($logEventId);
		if (
			$arCommentEventMeta
			&& isset($arCommentEventMeta["RATING_TYPE_ID"])
		)
		{
			$res = $arCommentEventMeta["RATING_TYPE_ID"];
		}
		elseif (
			$logEventId == "photo_photo"
			&& intval($logId) > 0
		)
		{
			$commentType = CSocNetPhotoCommentEvent::FindLogType($logId);
			if (
				$commentType
				&& isset($commentType["TYPE"])
			)
			{
				if ($commentType["TYPE"] == "FORUM")
				{
					$res = "FORUM_POST";
				}
				elseif ($commentType["TYPE"] == "BLOG")
				{
					$res = "BLOG_COMMENT";
				}
			}
		}
		elseif (in_array($logEventId, array("wiki", "calendar", "news", "lists_new_element", "timeman_entry", "report")))
		{
			$res = "FORUM_POST";
		}
		elseif (in_array($logEventId, array("idea")))
		{
			$res = "BLOG_COMMENT";
		}

		return $res;
	}
}
?>