<?
IncludeModuleLangFile(__FILE__);

class CCalendarLiveFeed
{
	public static function AddEvent(&$arSocNetFeaturesSettings)
	{
		$arSocNetFeaturesSettings['calendar']['subscribe_events'] = array(
			'calendar' => array(
				'ENTITIES' => array(
					SONET_SUBSCRIBE_ENTITY_USER => array()
				),
				"FORUM_COMMENT_ENTITY" => "EV",
				'OPERATION' => 'view',
				'CLASS_FORMAT' => 'CCalendarLiveFeed',
				'METHOD_FORMAT' => 'FormatEvent',
				'HAS_CB' => 'Y',
				'FULL_SET' => array("calendar", "calendar_comment"),
				"COMMENT_EVENT" => array(
					"MODULE_ID" => "calendar",
					"EVENT_ID" => "calendar_comment",
					"OPERATION" => "view",
					"OPERATION_ADD" => "log_rights",
					"ADD_CALLBACK" => array("CCalendarLiveFeed", "AddComment_Calendar"),
					"UPDATE_CALLBACK" => array("CSocNetLogTools", "UpdateComment_Forum"),
					"DELETE_CALLBACK" => array("CSocNetLogTools", "DeleteComment_Forum"),
					"CLASS_FORMAT" => "CSocNetLogTools",
					"METHOD_FORMAT" => "FormatComment_Forum"
				)
			)
		);
	}

	public static function FormatEvent($arFields, $arParams, $bMail = false)
	{
		global $APPLICATION, $CACHE_MANAGER;

		$arResult = array(
			"EVENT" => $arFields
		);

		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			$CACHE_MANAGER->RegisterTag("CALENDAR_EVENT_".intval($arFields["SOURCE_ID"]));
			$CACHE_MANAGER->RegisterTag("CALENDAR_EVENT_LIST");
		}

		$eventViewResult = $APPLICATION->IncludeComponent('bitrix:calendar.livefeed.view', '', array(
			"EVENT_ID" => $arFields["SOURCE_ID"],
			"USER_ID" => $arFields["USER_ID"],
			"PATH_TO_USER" => $arParams["PATH_TO_USER"],
			"MOBILE" => $arParams["MOBILE"]
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);

		$arResult["EVENT_FORMATTED"] = Array(
			"TITLE" => GetMessage("EC_EDEV_EVENT"),
			"TITLE_24" => GetMessage("EC_EDEV_EVENT"),
			"MESSAGE" => $eventViewResult['MESSAGE'],
			"FOOTER_MESSAGE" => $eventViewResult['FOOTER_MESSAGE'],
			"IS_IMPORTANT" => false,
			"STYLE" => "calendar-confirm"
		);

		$eventId = $arFields["SOURCE_ID"];
		if (!$eventId)
			$eventId = 0;

		$calendarUrl = CCalendar::GetPath('user', $arFields["USER_ID"]);
		$editUrl = $calendarUrl.((strpos($calendarUrl, "?") === false) ? '?' : '&').'EVENT_ID=EDIT'.$eventId;

		$arResult["EVENT_FORMATTED"]["URL"] = $calendarUrl.((strpos($calendarUrl, "?") === false) ? '?' : '&').'EVENT_ID='.$eventId;

		$arRights = array();
		$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $arFields["ID"]));
		while ($arRight = $dbRight->Fetch())
			$arRights[] = $arRight["GROUP_CODE"];

		$arResult["EVENT_FORMATTED"]["DESTINATION"] = CSocNetLogTools::FormatDestinationFromRights($arRights, array_merge($arParams, array("CREATED_BY" => $arFields["USER_ID"])));

		if (isset($eventViewResult['CACHED_JS_PATH']))
			$arResult['CACHED_JS_PATH'] = $eventViewResult['CACHED_JS_PATH'];

		$arResult['ENTITY']['FORMATTED']["NAME"] = "ENTITY FORMATTED NAME";
		$arResult['ENTITY']['FORMATTED']["URL"] = $arResult["EVENT_FORMATTED"]["URL"];

		$arResult['AVATAR_SRC'] = CSocNetLog::FormatEvent_CreateAvatar($arFields, $arParams, 'CREATED_BY');
		$arFieldsTooltip = array(
			'ID' => $arFields['USER_ID'],
			'NAME' => $arFields['~CREATED_BY_NAME'],
			'LAST_NAME' => $arFields['~CREATED_BY_LAST_NAME'],
			'SECOND_NAME' => $arFields['~CREATED_BY_SECOND_NAME'],
			'LOGIN' => $arFields['~CREATED_BY_LOGIN'],
		);
		$arResult['CREATED_BY']['TOOLTIP_FIELDS'] = CSocNetLog::FormatEvent_FillTooltip($arFieldsTooltip, $arParams);

		return $arResult;
	}

	public static function OnSonetLogEntryMenuCreate($arLogEvent)
	{
		if (
			is_array($arLogEvent["FIELDS_FORMATTED"])
			&& is_array($arLogEvent["FIELDS_FORMATTED"]["EVENT"])
			&& array_key_exists("EVENT_ID", $arLogEvent["FIELDS_FORMATTED"]["EVENT"])
			&& $arLogEvent["FIELDS_FORMATTED"]["EVENT"]["EVENT_ID"] == "calendar"
		)
		{
			global $USER;

			if ($USER->GetId() == $arLogEvent["FIELDS_FORMATTED"]["EVENT"]['USER_ID'])
			{
				$eventId = $arLogEvent["FIELDS_FORMATTED"]["EVENT"]["SOURCE_ID"];
				$editUrl = CCalendar::GetPath('user', $arLogEvent["FIELDS_FORMATTED"]["EVENT"]['USER_ID']);
				$editUrl = $editUrl.((strpos($editUrl, "?") === false) ? '?' : '&').'EVENT_ID=EDIT'.$eventId;

				return array(
					array(
						'text' => GetMessage("EC_T_EDIT"),
						'href' => $editUrl
					),
					array(
						'text' => GetMessage("EC_T_DELETE"),
						'onclick' => 'if(window.oViewEventManager[\''.$eventId.'\']){window.oViewEventManager[\''.$eventId.'\'].DeleteEvent();};'
					)
				);
			}
			else
			{
				return false;
			}
		}
		else
			return false;
	}

	public static function AddComment_Calendar($arFields)
	{
		global $DB;
		if (!CModule::IncludeModule("forum"))
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
			$arCalendarEvent = CCalendarEvent::GetById($arLog["SOURCE_ID"]);
			if ($arCalendarEvent)
			{
				$arCalendarSettings = CCalendar::GetSettings();
				$forumID = $arCalendarSettings["forum_id"];

				if ($forumID)
				{
					$arFilter = array(
						"FORUM_ID" => $forumID,
						"XML_ID" => "EVENT_".$arLog["SOURCE_ID"]
					);
					$dbTopic = CForumTopic::GetList(null, $arFilter);
					if ($dbTopic && ($arTopic = $dbTopic->Fetch()))
						$topicID = $arTopic["ID"];
					else
						$topicID = 0;

					$currentUserId = CCalendar::GetCurUserId();
					$strPermission = ($currentUserId == $arCalendarEvent["OWNER_ID"] ? "Y" : "M");

					$arFieldsMessage = array(
						"POST_MESSAGE" => $arFields["TEXT_MESSAGE"],
						"USE_SMILES" => "Y",
						"PERMISSION_EXTERNAL" => "Q",
						"PERMISSION" => $strPermission,
						"APPROVED" => "Y"
					);

					if ($topicID === 0)
					{
						$arFieldsMessage["TITLE"] = "EVENT_".$arLog["SOURCE_ID"];
						$arFieldsMessage["TOPIC_XML_ID"] = "EVENT_".$arLog["SOURCE_ID"];
					}

					$arTmp = false;
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

					$messageID = ForumAddMessage(($topicID > 0 ? "REPLY" : "NEW"), $forumID, $topicID, 0, $arFieldsMessage, $sError, $sNote);

					// get UF DOC value and FILE_ID there
					if ($messageID > 0)
					{
						$messageUrl = CCalendar::GetPath("user", $arCalendarEvent["OWNER_ID"]);
						$messageUrl = $messageUrl.((strpos($messageUrl, "?") === false) ? "?" : "&")."EVENT_ID=".$arCalendarEvent["ID"]."&MID=".$messageID;

						$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $messageID));
						while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
							$ufFileID[] = $arAddedMessageFiles["FILE_ID"];

						$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $messageID, LANGUAGE_ID);
					}
				}
			}
		}

		if (!$messageID)
			$sError = GetMessage("EC_LF_ADD_COMMENT_SOURCE_ERROR");

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

	public static function OnAfterSonetLogEntryAddComment($arSonetLogComment)
	{
		if ($arSonetLogComment["EVENT_ID"] != "calendar_comment")
			return;

		$dbLog = CSocNetLog::GetList(
			array(),
			array(
				"ID" => $arSonetLogComment["LOG_ID"],
				"EVENT_ID" => "calendar"
			),
			false,
			false,
			array("ID", "SOURCE_ID")
		);

		if (
			($arLog = $dbLog->Fetch())
			&& (intval($arLog["SOURCE_ID"]) > 0)
		)
		{
			CCalendarLiveFeed::NotifyComment(
				$arLog["SOURCE_ID"],
				array(
					"USER_ID" => $arSonetLogComment["USER_ID"],
					"MESSAGE" => $arSonetLogComment["MESSAGE"],
					"URL" => $arSonetLogComment["URL"]
				)
			);
		}

	}

	public static function OnForumCommentIMNotify($entityType, $eventID, $arComment)
	{
		if (
			$entityType != "EV"
			|| !CModule::IncludeModule("im")
		)
		{
			return;
		}

		if (
			isset($arComment["MESSAGE_ID"])
			&& intval($arComment["MESSAGE_ID"]) > 0
			&& ($arCalendarEvent = CCalendarEvent::GetById($eventID))
		)
		{
			$arComment["URL"] = CCalendar::GetPath("user", $arCalendarEvent["OWNER_ID"], true);
			$arComment["URL"] .= ((strpos($arComment["URL"], "?") === false) ? "?" : "&")."EVENT_ID=".$arCalendarEvent["ID"]."&MID=".intval($arComment["MESSAGE_ID"]);
		}

		CCalendarLiveFeed::NotifyComment($eventID, $arComment);
	}

	public static function onAfterCommentAddAfter($entityType, $eventID, $arData, $logID = false)
	{
		if ($entityType != "EV")
			return;

		if (intval($logID) <= 0)
			return;

		CCalendarLiveFeed::SetCommentFileRights($arData, $logID);
	}

	public static function onAfterCommentUpdateAfter($entityType, $eventID, $arData, $logID = false)
	{
		if ($entityType != "EV")
			return;

		if (intval($logID) <= 0)
			return;

		if (
			!is_array($arData)
			|| !array_key_exists("ACTION", $arData)
			|| $arData["ACTION"] != "EDIT"
		)
			return;

		CCalendarLiveFeed::SetCommentFileRights($arData, $logID);
	}

	public static function SetCommentFileRights($arData, $logID)
	{
		if (intval($logID) <= 0)
			return;

		$arAccessCodes = array();
		$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $logID));
		while ($arRight = $dbRight->Fetch())
			$arAccessCodes[] = $arRight["GROUP_CODE"];

		$arFilesIds = $arData["PARAMS"]["UF_FORUM_MESSAGE_DOC"];
		$UF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("FORUM_MESSAGE", $arData["MESSAGE_ID"], LANGUAGE_ID);
		CCalendar::UpdateUFRights($arFilesIds, $arAccessCodes, $UF["UF_FORUM_MESSAGE_DOC"]);
	}

	public static function NotifyComment($eventID, $arComment)
	{
		if (!CModule::IncludeModule("im"))
		{
			return;
		}

		if (intval($eventID) <= 0)
		{
			return;
		}

		if ($arCalendarEvent = CCalendarEvent::GetById($eventID))
		{
			$rsUser = CUser::GetList(
				$by = 'id',
				$order = 'asc',
				array('ID_EQUAL_EXACT' => intval($arComment["USER_ID"])),
				array('FIELDS' => array('PERSONAL_GENDER'))
			);

			$strMsgAddComment  = GetMessage("EC_LF_COMMENT_MESSAGE_ADD");
			$strMsgEditComment = GetMessage("EC_LF_COMMENT_MESSAGE_ADD");

			if ($arUser = $rsUser->fetch())
			{
				switch ($arUser['PERSONAL_GENDER'])
				{
					case "F":
					case "M":
						$strMsgAddComment = GetMessage("EC_LF_COMMENT_MESSAGE_ADD" . '_' . $arUser['PERSONAL_GENDER']);
						break;
					default:
						break;
				}
			}

			$url = CCalendar::GetPathForCalendarEx($arComment["USER_ID"]);
			$url = $url.((strpos($url, "?") === false) ? '?' : '&').'EVENT_ID='.$eventID.'&EVENT_DATE='.$arCalendarEvent['DT_FROM'];

			$arMessageFields = array(
				"FROM_USER_ID" => $arComment["USER_ID"],
				"NOTIFY_TYPE" => IM_NOTIFY_FROM,
				"NOTIFY_MODULE" => "calendar",
				"NOTIFY_EVENT" => "event_comment",
				"NOTIFY_MESSAGE" => str_replace(
					array("#EVENT_TITLE#"),
					array(strlen($url) > 0 ? "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".$arCalendarEvent["NAME"]."</a>" : $arCalendarEvent["NAME"]),
					$strMsgAddComment
				),
				"NOTIFY_MESSAGE_OUT" => str_replace(
					array("#EVENT_TITLE#"),
					array($arCalendarEvent["NAME"]),
					$strMsgAddComment
				).(strlen($url) > 0 ? " (".$url.")" : "")."#BR##BR#".$arComment["MESSAGE"]
			);

			if (is_array($arCalendarEvent["~ATTENDEES"]))
			{
				foreach($arCalendarEvent["~ATTENDEES"] as $arAttendee)
				{
					if ($arAttendee["USER_ID"] != $arComment["USER_ID"] && $arAttendee["STATUS"] != 'N')
					{
						$arMessageFields1 = array_merge($arMessageFields, array(
							"TO_USER_ID" => $arAttendee["USER_ID"]
						));
						CIMNotify::Add($arMessageFields1);
					}
				}
			}
		}
	}

	public static function EditCalendarEventEntry($arFields = array(), $arUFFields = array(), $arAccessCodes = array(), $params = array())
	{
		global $DB;

		if ($arFields['SECTION'])
			$arFields['SECTIONS'] = array($arFields['SECTION']);

		$arFields["OWNER_ID"] = $params["userId"];
		$arFields["CAL_TYPE"] = $params["type"];

		// Add author for new event
		if (!$arFields["ID"])
			$arAccessCodes[] = 'U'.$params["userId"];

		$arAccessCodes = array_unique($arAccessCodes);
		$arAttendees = CCalendar::GetDestinationUsers($arAccessCodes);

		if (trim($arFields["NAME"]) === '')
			$arFields["NAME"] = GetMessage('EC_DEFAULT_EVENT_NAME');

		$arFields['IS_MEETING'] = !empty($arAttendees) && $arAttendees != array($params["userId"]);

		if (isset($arFields['RRULE']) && !empty($arFields['RRULE']))
		{
			if (is_array($arFields['RRULE']['BYDAY']))
				$arFields['RRULE']['BYDAY'] = implode(',', $arFields['RRULE']['BYDAY']);
		}

		if ($arFields['IS_MEETING'])
		{
			$arFields['ATTENDEES_CODES'] = $arAccessCodes;
			$arFields['ATTENDEES'] = $arAttendees;
			$arFields['MEETING_HOST'] = $params["userId"];
			$arFields['MEETING'] = array(
				'HOST_NAME' => CCalendar::GetUserName($params["userId"]),
				'TEXT' => '',
				'OPEN' => false,
				'NOTIFY' => true,
				'REINVITE' => false
			);
		}
		else
		{
			$arFields['ATTENDEES'] = false;
		}

		$eventId = CCalendar::SaveEvent(
			array(
				'arFields' => $arFields,
				'autoDetectSection' => true
			)
		);

		if ($eventId > 0)
		{
			if (count($arUFFields) > 0)
				CCalendarEvent::UpdateUserFields($eventId, $arUFFields);

			foreach($arAccessCodes as $key => $value)
				if ($value == "UA")
				{
					unset($arAccessCodes[$key]);
					$arAccessCodes[] = "G2";
					break;
				}

			if ($arFields['IS_MEETING'] && !empty($arUFFields['UF_WEBDAV_CAL_EVENT']))
			{
				$UF = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields("CALENDAR_EVENT", $eventId, LANGUAGE_ID);
				CCalendar::UpdateUFRights($arUFFields['UF_WEBDAV_CAL_EVENT'], $arAccessCodes, $UF['UF_WEBDAV_CAL_EVENT']);
			}

			$arSoFields = Array(
				"ENTITY_TYPE" => SONET_SUBSCRIBE_ENTITY_USER,
				"ENTITY_ID" => $params["userId"],
				"USER_ID" => $params["userId"],
				"=LOG_DATE" => $DB->CurrentTimeFunction(),
				"TITLE_TEMPLATE" => "#TITLE#",
				"TITLE" => $arFields["NAME"],
				"MESSAGE" => '',
				"TEXT_MESSAGE" => ''
			);

			$dbRes = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID" => "calendar",
					"SOURCE_ID" => $eventId
				),
				false,
				false,
				array("ID")
			);

			$arCodes = array();
			foreach($arAccessCodes as $value)
			{
				if (substr($value, 0, 2) === 'SG')
					$arCodes[] = $value.'_K';
				$arCodes[] = $value;
			}
			$arCodes = array_unique($arCodes);

			if ($arRes = $dbRes->Fetch())
			{
				CSocNetLog::Update($arRes["ID"], $arSoFields);
				CSocNetLogRights::DeleteByLogID($arRes["ID"]);
				CSocNetLogRights::Add($arRes["ID"], $arCodes);
			}
			else
			{
				$arSoFields = array_merge($arSoFields, array(
					"EVENT_ID" => "calendar",
					"SITE_ID" => SITE_ID,
					"SOURCE_ID" => $eventId,
					"ENABLE_COMMENTS" => "Y",
					"CALLBACK_FUNC" => false
				));
				$logID = CSocNetLog::Add($arSoFields, false);
				CSocNetLogRights::Add($logID, $arCodes);
			}
		}
	}

	public static function OnEditCalendarEventEntry($eventId, $arFields = array(), $attendeesCodes = array())
	{
		global $DB;

		if ($eventId > 0)
		{
			$arSoFields = Array(
				"ENTITY_ID" => $arFields["OWNER_ID"],
				"USER_ID" => $arFields["OWNER_ID"],
				"=LOG_DATE" =>$DB->CurrentTimeFunction(),
				"TITLE_TEMPLATE" => "#TITLE#",
				"TITLE" => $arFields["NAME"],
				"MESSAGE" => "",
				"TEXT_MESSAGE" => ""
			);

			$arAccessCodes = array();
			foreach($attendeesCodes as $value)
			{
				if ($value == "UA")
					$arAccessCodes[] = "G2";
				else
					$arAccessCodes[] = $value;
			}

			$dbRes = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID" => "calendar",
					"SOURCE_ID" => $eventId
				),
				false,
				false,
				array("ID")
			);

			$arCodes = array();
			foreach($arAccessCodes as $value)
			{
				if (substr($value, 0, 2) === 'SG')
					$arCodes[] = $value.'_K';
				$arCodes[] = $value;
			}

			if ($arFields['IS_MEETING'] && $arFields['MEETING_HOST'] && !in_array('U'.$arFields['MEETING_HOST'], $arCodes))
			{
				$arCodes[] = 'U'.$arFields['MEETING_HOST'];
			}
			$arCodes = array_unique($arCodes);

			if ($arRes = $dbRes->Fetch())
			{
				if (
					isset($arRes["ID"])
					&& intval($arRes["ID"]) > 0
				)
				{
					CSocNetLog::Update($arRes["ID"], $arSoFields);
					CSocNetLogRights::DeleteByLogID($arRes["ID"]);
					CSocNetLogRights::Add($arRes["ID"], $arCodes);
				}
			}
			else
			{
				$arSoFields = array_merge($arSoFields, array(
					"ENTITY_TYPE" => SONET_SUBSCRIBE_ENTITY_USER,
					"EVENT_ID" => "calendar",
					"SITE_ID" => SITE_ID,
					"SOURCE_ID" => $eventId,
					"ENABLE_COMMENTS" => "Y",
					"CALLBACK_FUNC" => false
				));
				$logID = CSocNetLog::Add($arSoFields, false);
				CSocNetLogRights::Add($logID, $arCodes);
			}
		}
	}

	// Do delete from socialnetwork live feed here
	public static function OnDeleteCalendarEventEntry($eventId, $arFields = array())
	{
		if (CModule::IncludeModule("socialnetwork"))
		{
			$dbRes = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID" => "calendar",
					"SOURCE_ID" => $eventId
				),
				false,
				false,
				array("ID")
			);
			while ($arRes = $dbRes->Fetch())
				CSocNetLog::Delete($arRes["ID"]);
		}
	}

	public static function FixForumCommentURL($arData)
	{
		if(
			in_array($arData["MODULE_ID"], array("forum", "FORUM"))
			&& $arData['ENTITY_TYPE_ID'] === 'FORUM_POST'
			&& intval($arData['PARAM1']) > 0
			&& preg_match('/^EVENT_([0-9]+)/', $arData["TITLE"], $match)
		)
		{
			$arCalendarSettings = CCalendar::GetSettings();
			$forumID = $arCalendarSettings["forum_id"];
			$eventID = intval($match[1]);

			if (
				intval($arData['PARAM1']) == $forumID
				&& $eventID > 0
				&& !empty($arCalendarSettings["pathes"])
				&& ($arCalendarEvent = CCalendarEvent::GetById($eventID))
				&& strlen($arCalendarEvent["CAL_TYPE"]) > 0
				&& in_array($arCalendarEvent["CAL_TYPE"], array("user", "group"))
				&& intval($arCalendarEvent["OWNER_ID"]) > 0
			)
			{
				foreach ($arData['LID'] as $siteId => $value)
				{
					$messageUrl = false;

					if (
						array_key_exists($siteId, $arCalendarSettings["pathes"])
						&& is_array($arCalendarSettings["pathes"][$siteId])
						&& !empty($arCalendarSettings["pathes"][$siteId])
					)
					{
						if ($arCalendarEvent["CAL_TYPE"] == "user")
						{
							if (
								array_key_exists("path_to_user_calendar", $arCalendarSettings["pathes"][$siteId])
								&& !empty($arCalendarSettings["pathes"][$siteId]["path_to_user_calendar"])
							)
							{
								$messageUrl = CComponentEngine::MakePathFromTemplate(
									$arCalendarSettings["pathes"][$siteId]["path_to_user_calendar"],
									array(
										"user_id" => $arCalendarEvent['OWNER_ID'],
									)
								);
							}
						}
						else
						{
							if (
								array_key_exists("path_to_group_calendar", $arCalendarSettings["pathes"][$siteId])
								&& !empty($arCalendarSettings["pathes"][$siteId]["path_to_group_calendar"])
							)
							{
								$messageUrl = CComponentEngine::MakePathFromTemplate(
									$arCalendarSettings["pathes"][$siteId]["path_to_group_calendar"],
									array(
										"group_id" => $arCalendarEvent['OWNER_ID'],
									)
								);
							}
						}
					}

					$arData['LID'][$siteId] = ($messageUrl ? $messageUrl."?EVENT_ID=".$arCalendarEvent["ID"]."&MID=".$arData['ENTITY_ID']."#message".$arData['ENTITY_ID'] : "");
				}

				return $arData;
			}

			return array(
				"TITLE" => "",
				"BODY" => ""
			);
		}
	}

}
?>