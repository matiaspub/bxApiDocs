<?
IncludeModuleLangFile(__FILE__);

class CSocNetForumComments
{
	public static function FindLogEventIDByForumEntityID($forumEntityType)
	{
		$event_id = false;
		$arSocNetLogEvents = CSocNetAllowed::GetAllowedLogEvents();

		foreach ($arSocNetLogEvents as $event_id_tmp => $arEventTmp)
		{
			if (
				array_key_exists("FORUM_COMMENT_ENTITY", $arEventTmp)
				&& $arEventTmp["FORUM_COMMENT_ENTITY"] == $forumEntityType
			)
			{
				$event_id = $event_id_tmp;
				break;
			}
		}

		$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();
		foreach ($arSocNetFeaturesSettings as $feature_tmp => $arFeature)
		{
			if (array_key_exists("subscribe_events", $arFeature))
			{
				foreach ($arFeature["subscribe_events"] as $event_id_tmp => $arEventTmp)
				{
					if (
						array_key_exists("FORUM_COMMENT_ENTITY", $arEventTmp)
						&& $arEventTmp["FORUM_COMMENT_ENTITY"] == $forumEntityType
					)
					{
						$event_id = $event_id_tmp;
						break;
					}
				}
			}
		}

		return $event_id;
	}

	public static function onAfterCommentAdd($entityType, $entityId, $arData)
	{
		$log_event_id = CSocNetForumComments::FindLogEventIDByForumEntityID($entityType);
		if (!$log_event_id)
			return false;

		$arLogCommentEvent = CSocNetLogTools::FindLogCommentEventByLogEventID($log_event_id);
		if (!$arLogCommentEvent)
			return false;

		$arLogEvent = CSocNetLogTools::FindLogEventByID($log_event_id);

		$entityId = intval($entityId);
		if ($entityId <= 0)
		{
			return;
		}

		$messageId = $arData['MESSAGE_ID'];
		if ($messageId <= 0)
		{
			return;
		}

		$arMessage = CForumMessage::GetByID($messageId);
		if (!$arMessage)
		{
			return;
		}

		$strURL = $GLOBALS['APPLICATION']->GetCurPageParam("", array("IFRAME", "MID", "SEF_APPLICATION_CUR_PAGE_URL", BX_AJAX_PARAM_ID, "result", "sessid"));
		$strURL = ForumAddPageParams(
			$strURL,
			array(
				"MID" => $messageId,
				"result" => "reply"
			),
			false,
			false
		);

		$sText = (COption::GetOptionString("forum", "FILTER", "Y") == "Y" ? $arMessage["POST_MESSAGE_FILTER"] : $arMessage["POST_MESSAGE"]);

		$dbRes = CSocNetLog::GetList(
			array("ID" => "DESC"),
			array(
				"EVENT_ID" => $log_event_id,
				"SOURCE_ID" => $entityId
			),
			false,
			false,
			array("ID", "ENTITY_TYPE", "ENTITY_ID")
		);

		if ($arRes = $dbRes->Fetch())
		{
			$log_id = $arRes["ID"];
			$entity_type = $arRes["ENTITY_TYPE"];
			$entity_id = $arRes["ENTITY_ID"];

			$parser = new CTextParser();
			$parser->allow = array("HTML" => 'N',"ANCHOR" => 'Y',"BIU" => 'Y',"IMG" => "Y","VIDEO" => "Y","LIST" => 'N',"QUOTE" => 'Y',"CODE" => 'Y',"FONT" => 'Y',"SMILES" => "N","UPLOAD" => 'N',"NL2BR" => 'N',"TABLE" => "Y");

			$arFieldsForSocnet = array(
				"ENTITY_TYPE" => $entity_type,
				"ENTITY_ID" => $entity_id,
				"EVENT_ID" => $arLogCommentEvent["EVENT_ID"],
				"=LOG_DATE" => $GLOBALS['DB']->CurrentTimeFunction(),
				"USER_ID" => $arMessage["AUTHOR_ID"],
				"MESSAGE" => $sText,
				"TEXT_MESSAGE" => $parser->convert4mail($sText),
				"URL" => str_replace("?IFRAME=Y", "", str_replace("&IFRAME=Y", "", str_replace("IFRAME=Y&", "", $strURL))),
				"MODULE_ID" => (array_key_exists("MODULE_ID", $arLogCommentEvent) && strlen($arLogCommentEvent["MODULE_ID"]) > 0 ? $arLogCommentEvent["MODULE_ID"] : ""),
				"SOURCE_ID" => $messageId,
				"LOG_ID" => $log_id
			);

			if (
				!array_key_exists("RATING_TYPE_ID", $arLogCommentEvent)
				|| $arLogCommentEvent == "FORUM_POST"
			)
			{
				$arFieldsForSocnet["RATING_TYPE_ID"] = "FORUM_POST";
				$arFieldsForSocnet["RATING_ENTITY_ID"] = $messageId;
			}

			$ufFileID = array();
			$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $messageId));
			while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
			{
				$ufFileID[] = $arAddedMessageFiles["FILE_ID"];
			}

			if (count($ufFileID) > 0)
			{
				$arFieldsForSocnet["UF_SONET_COM_FILE"] = $ufFileID;
			}

			$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $messageId, LANGUAGE_ID);
			if ($ufDocID)
			{
				$arFieldsForSocnet["UF_SONET_COM_DOC"] = $ufDocID;
			}

			$comment_id = CSocNetLogComments::Add($arFieldsForSocnet, false, false);
			CSocNetLog::CounterIncrement(
				$comment_id, 
				false, 
				false, 
				"LC",
				CSocNetLogRights::CheckForUserAll($log_id)
			);
		}

		foreach (GetModuleEvents("socialnetwork", "onAfterCommentAddAfter", true) as $arModuleEvent)
			ExecuteModuleEventEx($arModuleEvent, array(
				$entityType,
				$entityId,
				$arData,
				$log_id
			));

		foreach (GetModuleEvents("socialnetwork", "OnForumCommentIMNotify", true) as $arModuleEvent) // send notification
			ExecuteModuleEventEx($arModuleEvent, array(
				$entityType,
				$entityId,
				array(
					"LOG_ID" => $log_id,
					"USER_ID" => $arMessage["AUTHOR_ID"],
					"MESSAGE_ID" => $messageId,
					"MESSAGE" => $sText,
					"URL" => $strURL
				)
			));
	}

	public static function onAfterCommentUpdate($entityType, $entityId, $arData)
	{
		$log_event_id = CSocNetForumComments::FindLogEventIDByForumEntityID($entityType);
		if (!$log_event_id)
			return false;

		$arLogCommentEvent = CSocNetLogTools::FindLogCommentEventByLogEventID($log_event_id);
		if (!$arLogCommentEvent)
			return false;

		$arLogEvent = CSocNetLogTools::FindLogEventByID($log_event_id);

		$entityId = intval($entityId);
		if ($entityId <= 0)
			return;	

		if (empty($arData["MESSAGE_ID"]))
			return;

		$parser = new CTextParser();
		$parser->allow = array("HTML" => 'N',"ANCHOR" => 'Y',"BIU" => 'Y',"IMG" => "Y","VIDEO" => "Y","LIST" => 'N',"QUOTE" => 'Y',"CODE" => 'Y',"FONT" => 'Y',"SMILES" => "N","UPLOAD" => 'N',"NL2BR" => 'N',"TABLE" => "Y");

		switch ($arData["ACTION"])
		{
			case "DEL":
			case "HIDE":
				$dbLogComment = CSocNetLogComments::GetList(
					array("ID" => "DESC"),
					array(
						"EVENT_ID"	=> array($arLogCommentEvent["EVENT_ID"]),
						"SOURCE_ID" => intval($arData["MESSAGE_ID"])
					),
					false,
					false,
					array("ID")
				);
				while ($arLogComment = $dbLogComment->Fetch())
					CSocNetLogComments::Delete($arLogComment["ID"]);
				break;
			case "SHOW":
				$dbLogComment = CSocNetLogComments::GetList(
					array("ID" => "DESC"),
					array(
						"EVENT_ID"	=> array($arLogCommentEvent["EVENT_ID"]),
						"SOURCE_ID" => intval($arData["MESSAGE_ID"])
					),
					false,
					false,
					array("ID")
				);
				$arLogComment = $dbLogComment->Fetch();
				if (!$arLogComment)
				{
					$arMessage = CForumMessage::GetByID(intval($arData["MESSAGE_ID"]));
					if ($arMessage)
					{
						$dbLog = CSocNetLog::GetList(
							array("ID" => "DESC"),
							array(
								"EVENT_ID" => $log_event_id,
								"SOURCE_ID" => $entityId
							),
							false,
							false,
							array("ID", "ENTITY_TYPE", "ENTITY_ID")
						);

						if ($arLog = $dbLog->Fetch())
						{
							$log_id = $arLog["ID"];
							$entity_type = $arLog["ENTITY_TYPE"];
							$entity_id = $arLog["ENTITY_ID"];

							$sText = (COption::GetOptionString("forum", "FILTER", "Y") == "Y" ? $arMessage["POST_MESSAGE_FILTER"] : $arMessage["POST_MESSAGE"]);
							$strURL = $GLOBALS['APPLICATION']->GetCurPageParam("", array("IFRAME", "MID", "SEF_APPLICATION_CUR_PAGE_URL", BX_AJAX_PARAM_ID, "result"));
							$strURL = ForumAddPageParams(
								$strURL,
								array(
									"MID" => intval($arData["MESSAGE_ID"]),
									"result" => "reply"
								),
								false,
								false
							);

							$arFieldsForSocnet = array(
								"ENTITY_TYPE" => $entity_type,
								"ENTITY_ID" => $entity_id,
								"EVENT_ID" => $arLogCommentEvent["EVENT_ID"],
								"MESSAGE" => $sText,
								"TEXT_MESSAGE" => $parser->convert4mail($sText),
								"URL" => str_replace("?IFRAME=Y", "", str_replace("&IFRAME=Y", "", str_replace("IFRAME=Y&", "", $strURL))),
								"MODULE_ID" => (array_key_exists("MODULE_ID", $arLogCommentEvent) && strlen($arLogCommentEvent["MODULE_ID"]) > 0 ? $arLogCommentEvent["MODULE_ID"] : ""),
								"SOURCE_ID" => intval($arData["MESSAGE_ID"]),
								"LOG_ID" => $log_id,
								"RATING_TYPE_ID" => "FORUM_POST",
								"RATING_ENTITY_ID" => intval($arData["MESSAGE_ID"])
							);

							$arFieldsForSocnet["USER_ID"] = $arMessage["AUTHOR_ID"];
							$arFieldsForSocnet["=LOG_DATE"] = $GLOBALS["DB"]->CurrentTimeFunction();

							$ufFileID = array();
							$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => intval($arData["MESSAGE_ID"])));
							while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
								$ufFileID[] = $arAddedMessageFiles["FILE_ID"];

							if (count($ufFileID) > 0)
								$arFieldsForSocnet["UF_SONET_COM_FILE"] = $ufFileID;

							$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", intval($arData["MESSAGE_ID"]), LANGUAGE_ID);
							if ($ufDocID)
								$arFieldsForSocnet["UF_SONET_COM_DOC"] = $ufDocID;

							$comment_id = CSocNetLogComments::Add($arFieldsForSocnet, false, false);
							CSocNetLog::CounterIncrement(
								$comment_id, 
								false, 
								false, 
								"LC",
								CSocNetLogRights::CheckForUserAll($log_id)
							);
						}
					}
				}
				break;
			case "EDIT":
				$arMessage = CForumMessage::GetByID(intval($arData["MESSAGE_ID"]));
				if ($arMessage)
				{
					$dbLogComment = CSocNetLogComments::GetList(
						array("ID" => "DESC"),
						array(
							"EVENT_ID"	=> array($arLogCommentEvent["EVENT_ID"]),
							"SOURCE_ID" => intval($arData["MESSAGE_ID"])
						),
						false,
						false,
						array("ID")
					);
					$arLogComment = $dbLogComment->Fetch();
					if ($arLogComment)
					{
						$sText = (COption::GetOptionString("forum", "FILTER", "Y") == "Y" ? $arMessage["POST_MESSAGE_FILTER"] : $arMessage["POST_MESSAGE"]);
						$arFieldsForSocnet = array(
							"MESSAGE" => $sText,
							"TEXT_MESSAGE" => $parser->convert4mail($sText),
						);

						$ufFileID = array();
						$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => intval($arData["MESSAGE_ID"])));
						while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
							$ufFileID[] = $arAddedMessageFiles["FILE_ID"];

						if (count($ufFileID) > 0)
							$arFieldsForSocnet["UF_SONET_COM_FILE"] = $ufFileID;

						$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", intval($arData["MESSAGE_ID"]), LANGUAGE_ID);
						if ($ufDocID)
							$arFieldsForSocnet["UF_SONET_COM_DOC"] = $ufDocID;

						CSocNetLogComments::Update($arLogComment["ID"], $arFieldsForSocnet);
					}
				}
				break;
			default:
		}

		foreach (GetModuleEvents("socialnetwork", "onAfterCommentUpdateAfter", true) as $arModuleEvent)
			ExecuteModuleEventEx($arModuleEvent, array(
				$entityType,
				$entityId,
				$arData,
				$log_id
			));
	}
}
?>