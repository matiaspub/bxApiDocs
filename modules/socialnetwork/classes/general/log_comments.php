<?
IncludeModuleLangFile(__FILE__);

class CAllSocNetLogComments
{
	/***************************************/
	/********  DATA MODIFICATION  **********/
	/***************************************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		static $arSiteWorkgroupsPage;

		global $DB, $arSocNetAllowedEntityTypes;

		if (
			!$arSiteWorkgroupsPage 
			&& IsModuleInstalled("extranet") 
			&& $arFields["ENTITY_TYPE"] == SONET_ENTITY_GROUP
		)
		{
			$rsSite = CSite::GetList($by="sort", $order="desc", Array("ACTIVE" => "Y"));
			while($arSite = $rsSite->Fetch())
			{
				$arSiteWorkgroupsPage[$arSite["ID"]] = COption::GetOptionString("socialnetwork", "workgroups_page", $arSite["DIR"]."workgroups/", $arSite["ID"]);
			}
		}

		if ($ACTION != "ADD" && IntVal($ID) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException("System error 870164", "ERROR");
			return false;
		}

		$newEntityType = "";

		if ((is_set($arFields, "ENTITY_TYPE") || $ACTION=="ADD") && StrLen($arFields["ENTITY_TYPE"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GLC_EMPTY_ENTITY_TYPE"), "EMPTY_ENTITY_TYPE");
			return false;
		}
		elseif (is_set($arFields, "ENTITY_TYPE"))
		{
			if (!in_array($arFields["ENTITY_TYPE"], CSocNetAllowed::GetAllowedEntityTypes()))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GLC_ERROR_NO_ENTITY_TYPE"), "ERROR_NO_ENTITY_TYPE");
				return false;
			}

			$newEntityType = $arFields["ENTITY_TYPE"];
		}

		if ((is_set($arFields, "ENTITY_ID") || $ACTION=="ADD") && IntVal($arFields["ENTITY_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GLC_EMPTY_ENTITY_ID"), "EMPTY_ENTITY_ID");
			return false;
		}
		elseif (is_set($arFields, "ENTITY_ID"))
		{
			if (StrLen($newEntityType) <= 0 && $ID > 0)
			{
				$arRe = CAllSocNetLog::GetByID($ID);
				if ($arRe)
				{
					$newEntityType = $arRe["ENTITY_TYPE"];
				}
			}
			if (StrLen($newEntityType) <= 0)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GL_ERROR_CALC_ENTITY_TYPE"), "ERROR_CALC_ENTITY_TYPE");
				return false;
			}

			if ($newEntityType == SONET_ENTITY_GROUP)
			{
				$arResult = CSocNetGroup::GetByID($arFields["ENTITY_ID"]);
				if ($arResult == false)
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GLC_ERROR_NO_ENTITY_ID"), "ERROR_NO_ENTITY_ID");
					return false;
				}
			}
			elseif ($newEntityType == SONET_ENTITY_USER)
			{
				$dbResult = CUser::GetByID($arFields["ENTITY_ID"]);
				if (!$dbResult->Fetch())
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GLC_ERROR_NO_ENTITY_ID"), "ERROR_NO_ENTITY_ID");
					return false;
				}
			}
		}

		if ((is_set($arFields, "LOG_ID") || $ACTION=="ADD") && intval($arFields["LOG_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GLC_EMPTY_LOG_ID"), "EMPTY_LOG_ID");
			return false;
		}

		if ((is_set($arFields, "EVENT_ID") || $ACTION=="ADD") && strlen($arFields["EVENT_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GLC_EMPTY_EVENT_ID"), "EMPTY_EVENT_ID");
			return false;
		}
		elseif (is_set($arFields, "EVENT_ID"))
		{
			$arFields["EVENT_ID"] = strtolower($arFields["EVENT_ID"]);
			$arEvent = CSocNetLogTools::FindLogCommentEventByID($arFields["EVENT_ID"]);
			if (!$arEvent)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GLC_ERROR_NO_FEATURE_ID"), "ERROR_NO_FEATURE");
				return false;
			}
		}

		if (is_set($arFields, "USER_ID"))
		{
			$dbResult = CUser::GetByID($arFields["USER_ID"]);
			if (!$dbResult->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GLC_ERROR_NO_USER_ID"), "ERROR_NO_USER_ID");
				return false;
			}
		}

		if (is_set($arFields, "LOG_DATE") && (!$DB->IsDate($arFields["LOG_DATE"], false, LANG, "FULL")))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GLC_EMPTY_DATE_CREATE"), "EMPTY_LOG_DATE");
			return false;
		}

		if (is_set($arFields["URL"]) && is_array($arSiteWorkgroupsPage))
			foreach($arSiteWorkgroupsPage as $groups_page)
				if (strpos($arFields["URL"], $groups_page) === 0)
					$arFields["URL"] = "#GROUPS_PATH#".substr($arFields["URL"], strlen($groups_page), strlen($arFields["URL"])-strlen($groups_page));

		if (!$GLOBALS["USER_FIELD_MANAGER"]->CheckFields("SONET_COMMENT", $ID, $arFields))
			return false;

		return True;
	}

	public static function Delete($ID, $bSetSource = false)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GLC_WRONG_PARAMETER_ID"), "ERROR_NO_ID");
			return false;
		}

		$bSuccess = false;

		if ($arComment = CSocNetLogComments::GetByID($ID))
		{
			if ($bSetSource)
			{
				if (strlen($arComment["EVENT_ID"]) > 0)
				{
					$arCommentEvent = CSocNetLogTools::FindLogCommentEventByID($arComment["EVENT_ID"]);
					if (
						!$arCommentEvent
						|| !array_key_exists("DELETE_CALLBACK", $arCommentEvent)
						|| !is_callable($arCommentEvent["DELETE_CALLBACK"])
					)
					{
						$bSetSource = false;
					}
				}
			}

			$bSuccess = true;

			if ($bSetSource)
			{
				$arSource = CSocNetLogComments::SetSource($arComment, "DELETE");
			}

			if (
				!$bSetSource
				|| (
					is_array($arSource)
					&& (
						!isset($arSource["ERROR"])
						|| empty($arSource["ERROR"])
					)
				)
			)
			{
				if ($bSuccess)
				{
					$bSuccess = $DB->Query("DELETE FROM b_sonet_log_comment WHERE ID = ".$ID."", true);
				}

				if ($bSuccess)
				{
					$GLOBALS["USER_FIELD_MANAGER"]->Delete("SONET_COMMENT", $ID);

					$db_events = GetModuleEvents("socialnetwork", "OnSocNetLogCommentDelete");
					while ($arEvent = $db_events->Fetch())
					{
						ExecuteModuleEventEx($arEvent, array($ID));
					}

					if (intval($arComment["LOG_ID"]) > 0)
					{
						CSocNetLogComments::UpdateLogData($arComment["LOG_ID"], false, true);

						$cache = new CPHPCache;
						$cache->CleanDir("/sonet/log/".intval(intval($arComment["LOG_ID"]) / 1000)."/".$arComment["LOG_ID"]."/comments/");
					}
				}

			}
			elseif (
				is_array($arSource)
				&& isset($arSource["ERROR"])
				&& is_string($arSource["ERROR"])
				&& !empty($arSource["ERROR"])
			)
			{
				$GLOBALS["APPLICATION"]->ThrowException($arSource["ERROR"], "ERROR_DELETE_SOURCE");
				$bSuccess = false;
			}
		}

		return $bSuccess;
	}

	public static function DeleteNoDemand($userID)
	{
		global $DB;

		$userID = IntVal($userID);
		if ($userID <= 0)
			return false;

		$DB->Query("DELETE FROM b_sonet_log_comment WHERE ENTITY_TYPE = 'U' AND ENTITY_ID = ".$userID."", true);

		return true;
	}

	/***************************************/
	/**********  DATA SELECTION  ***********/
	/***************************************/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GLC_WRONG_PARAMETER_ID"), "ERROR_NO_ID");
			return false;
		}

		$dbResult = CSocNetLogComments::GetList(array(), array("ID" => $ID));
		if ($arResult = $dbResult->GetNext())
			return $arResult;

		return false;
	}

	/***************************************/
	/**********  SEND EVENTS  **************/
	/***************************************/

	public static function SendEvent($ID, $mailTemplate = "SONET_NEW_EVENT", $bTransport = false)
	{
		$arSocNetAllowedSubscribeEntityTypesDesc = CSocNetAllowed::GetAllowedEntityTypesDesc();

		$ID = IntVal($ID);
		if ($ID <= 0)
			return false;

		$arFilter = array("ID" => $ID);

		$dbLogComments = CSocNetLogComments::GetList(
			array(),
			$arFilter,
			false,
			false,
			array("ID", "LOG_ID", "ENTITY_TYPE", "ENTITY_ID", "USER_ID", "USER_NAME", "USER_LAST_NAME", "USER_SECOND_NAME", "USER_LOGIN", "EVENT_ID", "LOG_DATE", "MESSAGE", "TEXT_MESSAGE", "URL", "MODULE_ID", "GROUP_NAME", "CREATED_BY_NAME", "CREATED_BY_SECOND_NAME", "CREATED_BY_LAST_NAME", "CREATED_BY_LOGIN", "LOG_SITE_ID", "SOURCE_ID", "LOG_SOURCE_ID")
		);
		$arLogComment = $dbLogComments->Fetch();
		if (!$arLogComment)
			return false;

		$arLog = array();
		if (intval($arLogComment["LOG_ID"]) > 0)
		{
			$dbLog = CSocNetLog::GetList(
				array(),
				array("ID" => $arLogComment["LOG_ID"])
			);
			$arLog = $dbLog->Fetch();
			if (!$arLog)
				$arLog = array();
		}

		$arEvent = CSocNetLogTools::FindLogCommentEventByID($arLogComment["EVENT_ID"]);

		if (
			$arEvent
			&& array_key_exists("CLASS_FORMAT", $arEvent)
			&& array_key_exists("METHOD_FORMAT", $arEvent)
			&& strlen($arEvent["CLASS_FORMAT"]) > 0
			&& strlen($arEvent["METHOD_FORMAT"]) > 0
		)
		{
			$dbSiteCurrent = CSite::GetByID(SITE_ID);
			if ($arSiteCurrent = $dbSiteCurrent->Fetch())
				if ($arSiteCurrent["LANGUAGE_ID"] != LANGUAGE_ID)
					$arLogComment["MAIL_LANGUAGE_ID"] = $arSiteCurrent["LANGUAGE_ID"];

			$arLogComment["FIELDS_FORMATTED"] = call_user_func(array($arEvent["CLASS_FORMAT"], $arEvent["METHOD_FORMAT"]), $arLogComment, array(), true, $arLog);
		}

		if (
			array_key_exists($arLogComment["ENTITY_TYPE"], $arSocNetAllowedSubscribeEntityTypesDesc)
			&& array_key_exists("HAS_MY", $arSocNetAllowedSubscribeEntityTypesDesc[$arLogComment["ENTITY_TYPE"]])
			&& $arSocNetAllowedSubscribeEntityTypesDesc[$arLogComment["ENTITY_TYPE"]]["HAS_MY"] == "Y"
			&& array_key_exists("CLASS_OF", $arSocNetAllowedSubscribeEntityTypesDesc[$arLogComment["ENTITY_TYPE"]])
			&& array_key_exists("METHOD_OF", $arSocNetAllowedSubscribeEntityTypesDesc[$arLogComment["ENTITY_TYPE"]])
			&& strlen($arSocNetAllowedSubscribeEntityTypesDesc[$arLogComment["ENTITY_TYPE"]]["CLASS_OF"]) > 0
			&& strlen($arSocNetAllowedSubscribeEntityTypesDesc[$arLogComment["ENTITY_TYPE"]]["METHOD_OF"]) > 0
			&& method_exists($arSocNetAllowedSubscribeEntityTypesDesc[$arLogComment["ENTITY_TYPE"]]["CLASS_OF"], $arSocNetAllowedSubscribeEntityTypesDesc[$arLogComment["ENTITY_TYPE"]]["METHOD_OF"])
		)
		{
			$arOfEntities = call_user_func(array($arSocNetAllowedSubscribeEntityTypesDesc[$arLogComment["ENTITY_TYPE"]]["CLASS_OF"], $arSocNetAllowedSubscribeEntityTypesDesc[$arLogComment["ENTITY_TYPE"]]["METHOD_OF"]), $arLogComment["ENTITY_ID"]);
		}

		if ($bTransport)
		{
			$arListParams = array(
				"USE_SUBSCRIBE" => "Y",
				"ENTITY_TYPE" => $arLogComment["ENTITY_TYPE"],
				"ENTITY_ID" => $arLogComment["ENTITY_ID"],
				"EVENT_ID" => $arLogComment["EVENT_ID"],
				"USER_ID" => $arLogComment["USER_ID"],
				"OF_ENTITIES" => $arOfEntities,
				"TRANSPORT" => array("M", "X")
			);

			$arLogSites = array();
			$rsLogSite = CSocNetLog::GetSite($arLog["ID"]);
			while($arLogSite = $rsLogSite->Fetch())
			{
				$arLogSites[] = $arLogSite["LID"];
			}

			if (CModule::IncludeModule("extranet"))
			{
				if ($arLogComment["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
				{
					$arSites = array();
					$dbSite = CSite::GetList($by="sort", $order="desc", array("ACTIVE" => "Y"));
					while($arSite = $dbSite->Fetch())
					{
						$arSites[$arSite["ID"]] = array(
							"DIR" => (strlen(trim($arSite["DIR"])) > 0 ? $arSite["DIR"] : "/"),
							"SERVER_NAME" => (strlen(trim($arSite["SERVER_NAME"])) > 0 ? $arSite["SERVER_NAME"] : COption::GetOptionString("main", "server_name", $_SERVER["HTTP_HOST"]))
						);
					}

					$intranet_site_id = CSite::GetDefSite();
				}
				$arIntranetUsers = CExtranet::GetIntranetUsers();
				$extranet_site_id = CExtranet::GetExtranetSiteID();
			}

			$dbSubscribers = CSocNetLogEvents::GetList(
				array(
					"TRANSPORT" => "DESC"
				),
				array(
					"USER_ACTIVE" => "Y",
					"SITE_ID" => array_merge($arLogSites, array(false))
				),
				false,
				false,
				array("USER_ID", "ENTITY_TYPE", "ENTITY_ID", "ENTITY_CB", "ENTITY_MY", "USER_NAME", "USER_LAST_NAME", "USER_LOGIN", "USER_LID", "USER_EMAIL", "TRANSPORT"),
				$arListParams
			);

			$arListParams = array(
				"USE_SUBSCRIBE" => "Y",
				"ENTITY_TYPE" => $arLogComment["ENTITY_TYPE"],
				"ENTITY_ID" => $arLogComment["ENTITY_ID"],
				"EVENT_ID" => $arLogComment["EVENT_ID"],
				"USER_ID" => $arLogComment["USER_ID"],
				"OF_ENTITIES" => $arOfEntities,
				"TRANSPORT" => "N"
			);

			$dbUnSubscribers = CSocNetLogEvents::GetList(
				array(
					"TRANSPORT" => "DESC"
				),
				array(
					"USER_ACTIVE" => "Y",
					"SITE_ID" => array_merge($arLogSites, array(false))
				),
				false,
				false,
				array("USER_ID", "SITE_ID", "ENTITY_TYPE", "ENTITY_ID", "ENTITY_CB", "ENTITY_MY", "TRANSPORT", "EVENT_ID"),
				$arListParams
			);

			$arUnSubscribers = array();
			while ($arUnSubscriber = $dbUnSubscribers->Fetch())
			{
				$arUnSubscribers[] = $arUnSubscriber["USER_ID"]."_".$arUnSubscriber["ENTITY_TYPE"]."_".$arUnSubscriber["ENTITY_ID"]."_".$arUnSubscriber["ENTITY_MY"]."_".$arUnSubscriber["ENTITY_CB"]."_".$arUnSubscriber["EVENT_ID"];
			}

			$bHasAccessAll = CSocNetLogRights::CheckForUserAll(($arLog["ID"] ? $arLog["ID"] : $arLogComment["LOG_ID"]));

			$arSentUserID = array("M" => array(), "X" => array());
			while ($arSubscriber = $dbSubscribers->Fetch())
			{
				if (
					is_array($arIntranetUsers) 
					&& !in_array($arSubscriber["USER_ID"], $arIntranetUsers)
					&& !in_array($extranet_site_id, $arLogSites)
				)
				{
					continue;
				}

				if (
					array_key_exists($arSubscriber["TRANSPORT"], $arSentUserID)
					&& in_array($arSubscriber["USER_ID"], $arSentUserID[$arSubscriber["TRANSPORT"]])
				)
				{
					continue;
				}

				if (
					intval($arSubscriber["ENTITY_ID"]) != 0
					&& $arSubscriber["EVENT_ID"] == "all"
					&& 
					(
						in_array($arSubscriber["USER_ID"]."_".$arSubscriber["ENTITY_TYPE"]."_".$arSubscriber["ENTITY_ID"]."_N_".$arSubscriber["ENTITY_CB"]."_".$arLogComment["EVENT_ID"], $arUnSubscribers)
						|| in_array($arSubscriber["USER_ID"]."_".$arSubscriber["ENTITY_TYPE"]."_".$arSubscriber["ENTITY_ID"]."_Y_".$arSubscriber["ENTITY_CB"]."_".$arLogComment["EVENT_ID"], $arUnSubscribers)
					)
				)
				{
					continue;
				}
				elseif (
					intval($arSubscriber["ENTITY_ID"]) == 0
					&& $arSubscriber["ENTITY_CB"] == "N"
					&& $arSubscriber["EVENT_ID"] != "all"
					&& 
					(
						in_array($arSubscriber["USER_ID"]."_".$arSubscriber["ENTITY_TYPE"]."_".$arLogComment["ENTITY_ID"]."_Y_N_all", $arUnSubscribers)
						|| in_array($arSubscriber["USER_ID"]."_".$arSubscriber["ENTITY_TYPE"]."_".$arLogComment["ENTITY_ID"]."_N_N_all", $arUnSubscribers)
						|| in_array($arSubscriber["USER_ID"]."_".$arSubscriber["ENTITY_TYPE"]."_".$arLogComment["ENTITY_ID"]."_Y_N_".$arLogComment["EVENT_ID"], $arUnSubscribers)
						|| in_array($arSubscriber["USER_ID"]."_".$arSubscriber["ENTITY_TYPE"]."_".$arLogComment["ENTITY_ID"]."_N_N_".$arLogComment["EVENT_ID"], $arUnSubscribers)
					)
				)
				{
					continue;
				}

				$arSentUserID[$arSubscriber["TRANSPORT"]][] = $arSubscriber["USER_ID"];

				if (!$bHasAccessAll)
				{
					$bHasAccess = CSocNetLogRights::CheckForUserOnly(($arLog["ID"] ? $arLog["ID"] : $arLogComment["LOG_ID"]), $arSubscriber["USER_ID"]);
					if (!$bHasAccess)
					{
						continue;
					}
				}

				if (
					$arLogComment["ENTITY_TYPE"] == SONET_ENTITY_GROUP 
					&& is_array($arIntranetUsers) 
					&& CModule::IncludeModule("extranet")
				)
				{
					$server_name = $arSites[((!in_array($arSubscriber["USER_ID"], $arIntranetUsers) && $extranet_site_id) ? $extranet_site_id : $intranet_site_id)]["SERVER_NAME"];
					$arLogComment["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL_TO_SEND"] = str_replace(
						array("#SERVER_NAME#", "#GROUPS_PATH#"), 
						array(
							$server_name, 
							COption::GetOptionString("socialnetwork", "workgroups_page", false, ((!in_array($arSubscriber["USER_ID"], $arIntranetUsers) && $extranet_site_id) ? $extranet_site_id : $intranet_site_id))
						), 
						$arLogComment["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL"]
					);
				}
				else
				{
					$arLogComment["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL_TO_SEND"] = $arLogComment["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL"];
				}

				switch ($arSubscriber["TRANSPORT"])
				{
					case "X":
						$link = (
							array_key_exists("URL_TO_SEND", $arLogComment["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
							&& strlen($arLogComment["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL_TO_SEND"]) > 0
								? GetMessage("SONET_GLC_SEND_EVENT_LINK").$arLogComment["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL_TO_SEND"]
								: ""
						);

						$arMessageFields = array(
							"FROM_USER_ID" => (intval($arLogComment["USER_ID"]) > 0 ? $arLogComment["USER_ID"] : 1),
							"TO_USER_ID" => $arSubscriber["USER_ID"],
							"MESSAGE" => $arLogComment["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["TITLE"]." #BR# ".$arLogComment["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["MESSAGE"].(strlen($link) > 0 ? "#BR# ".$link : ""),
							"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
							"MESSAGE_TYPE" => SONET_MESSAGE_SYSTEM,
							"IS_LOG" => "Y"
						);
						CSocNetMessages::Add($arMessageFields);
						break;				
					case "M":
						$arFields["SUBSCRIBER_ID"] = $arSubscriber["USER_ID"];
						$arFields["SUBSCRIBER_NAME"] = $arSubscriber["USER_NAME"];
						$arFields["SUBSCRIBER_LAST_NAME"] = $arSubscriber["USER_LAST_NAME"];
						$arFields["SUBSCRIBER_LOGIN"] = $arSubscriber["USER_LOGIN"];
						$arFields["SUBSCRIBER_EMAIL"] = $arSubscriber["USER_EMAIL"];
						$arFields["EMAIL_TO"] = $arSubscriber["USER_EMAIL"];
						$arFields["TITLE"] = str_replace("#BR#", "\n", $arLogComment["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["TITLE"]);
						$arFields["MESSAGE"] = str_replace("#BR#", "\n", $arLogComment["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["MESSAGE"]);
						$arFields["ENTITY"] = $arLogComment["FIELDS_FORMATTED"]["ENTITY"]["FORMATTED"];
						$arFields["ENTITY_TYPE"] = $arLogComment["FIELDS_FORMATTED"]["ENTITY"]["TYPE_MAIL"];

						$arFields["URL"] = (
							array_key_exists("URL_TO_SEND", $arLogComment["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
							&& strlen($arLogComment["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL_TO_SEND"]) > 0
								? $arLogComment["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL_TO_SEND"]
								: $arLogComment["URL"]
						);

						if (CModule::IncludeModule("extranet"))
						{
							$arUserGroup = CUser::GetUserGroup($arSubscriber["USER_ID"]);
						}

						foreach ($arLogSites as $site_id_tmp)
						{
							if (IsModuleInstalled("extranet"))
							{
								if (
									(
										CExtranet::IsExtranetSite($site_id_tmp)
										&& in_array(CExtranet::GetExtranetUserGroupID(), $arUserGroup)
									)
									||
									(
										!CExtranet::IsExtranetSite($site_id_tmp)
										&& !in_array(CExtranet::GetExtranetUserGroupID(), $arUserGroup)
									)
								)
								{
									$siteID = $site_id_tmp;
									break;
								}
								else
								{
									continue;
								}
							}
							else
							{
								$siteID = $site_id_tmp;
								break;
							}
						}

						if (!$siteID)
							$siteID = (defined("SITE_ID") ? SITE_ID : $arSubscriber["SITE_ID"]);

						if (StrLen($siteID) <= 0)
							$siteID = $arSubscriber["USER_LID"];
						if (StrLen($siteID) <= 0)
							continue;

						$event = new CEvent;
						$event->Send($mailTemplate, $siteID, $arFields, "N");
						break;
					default:
				}
			}
		}

		if (
			!$bHasAccessAll
			|| strtolower($GLOBALS["DB"]->type) != "mysql"
		)
		{
			CUserCounter::IncrementWithSelect(
				CSocNetLogCounter::GetSubSelect2(
					$arLogComment["ID"],
					array(
						"TYPE" => "LC",
						"FOR_ALL_ACCESS" => $bHasAccessAll
					)
				)
			);
		}
		else // for all, mysql only
		{
			$tag = time();
			CUserCounter::IncrementWithSelect(
				CSocNetLogCounter::GetSubSelect2(
					$arLogComment["ID"],
					array(
						"TYPE" => "LC",
						"FOR_ALL_ACCESS_ONLY" => true,
						"TAG_SET" => $tag
					)
				),
				false, // sendpull
				array(
					"TAG_SET" => $tag
				)
			);

			CUserCounter::IncrementWithSelect(
				CSocNetLogCounter::GetSubSelect2(
					$arLogComment["ID"],
					array(
						"TYPE" => "LC",
						"FOR_ALL_ACCESS_ONLY" => false
					)
				),
				true, // sendpull
				array(
					"TAG_CHECK" => $tag
				)
			);
		}

		return true;
	}

	public static function UpdateLogData($log_id, $bSetDate = true, $bSetDateByLastComment = false)
	{
		$dbResult = CSocNetLogComments::GetList(array(), array("LOG_ID" => $log_id), array());
		$comments_count = $dbResult;

		$dbResult = CSocNetLog::GetList(array(), array("ID" => $log_id), false, false, array("ID", "COMMENTS_COUNT", "LOG_DATE"));
		while ($arResult = $dbResult->Fetch())
		{
			$arFields = array("COMMENTS_COUNT" => $comments_count);
			if ($bSetDateByLastComment)
			{
				$dbComment = CSocNetLogComments::GetList(array("LOG_DATE" => "DESC"), array("LOG_ID" => $log_id), false, false, array("ID", "LOG_DATE"));
				if ($arComment = $dbComment->Fetch())
					$arFields["LOG_UPDATE"] = $arComment["LOG_DATE"];
				else
					$arFields["LOG_UPDATE"] = $arResult["LOG_DATE"];
			}
			elseif ($bSetDate)
				$arFields["=LOG_UPDATE"] = $GLOBALS["DB"]->CurrentTimeFunction();

			CSocNetLog::Update($arResult["ID"], $arFields);
			CSocNetLogFollow::DeleteByLogID($log_id, "Y", true); // not only delete but update to NULL for existing records
		}	
	}

	public static function SetSource($arFields, $action = false)
	{
		$arCallback = false;

		if (!$action)
		{
			$action = "ADD";
		}

		if (!in_array($action, array("ADD", "UPDATE", "DELETE")))
		{
			return false;
		}

		$arEvent = CSocNetLogTools::FindLogCommentEventByID($arFields["EVENT_ID"]);
		if ($arEvent)
		{
			$arCallback = $arEvent[$action."_CALLBACK"];
		}

		if (
			$arCallback 
			&& is_callable($arCallback)
		)
		{
			$arSource = call_user_func_array($arCallback, array($arFields));
		}

		return $arSource;
	}

	public static function SendMentionNotification($arCommentFields)
	{
		if (!CModule::IncludeModule("im"))
		{
			return false;
		}

		switch ($arCommentFields["EVENT_ID"])
		{
			case "forum":
				$arTitleRes = self::OnSendMentionGetEntityFields_Forum($arCommentFields);
				break;
			default:
				$db_events = GetModuleEvents("socialnetwork", "OnSendMentionGetEntityFields");
				while ($arEvent = $db_events->Fetch())
				{
					$arTitleRes = ExecuteModuleEventEx($arEvent, array($arCommentFields));
					if ($arTitleRes)
					{
						break;
					}
				}
		}

		if (
			$arTitleRes 
			&& is_array($arTitleRes)
			&& !empty($arTitleRes["NOTIFY_MESSAGE"])
		)
		{
			$arMessageFields = array(
				"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
				"FROM_USER_ID" => $arCommentFields["USER_ID"],
				"NOTIFY_TYPE" => IM_NOTIFY_FROM,
				"NOTIFY_MODULE" => (!empty($arTitleRes["NOTIFY_MODULE"]) ? $arTitleRes["NOTIFY_MODULE"] : "socialnetwork"),
				"NOTIFY_EVENT" => "mention",
				"NOTIFY_TAG" => (!empty($arTitleRes["NOTIFY_TAG"]) ? $arTitleRes["NOTIFY_TAG"] : "LOG_COMMENT|COMMENT_MENTION|".$arCommentFields["ID"])
			);

			preg_match_all("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/ies".BX_UTF_PCRE_MODIFIER, $arCommentFields["MESSAGE"], $arMention);

			if(!empty($arMention))
			{
				$arMention = $arMention[1];
				$arExcludeUsers = array();

				if (!empty($arCommentFields["LOG_ID"]))
				{
					$rsUnFollower = CSocNetLogFollow::GetList(
						array(
							"CODE" => "L".$arCommentFields["LOG_ID"],
							"TYPE" => "N"
						),
						array("USER_ID")
					);

					while ($arUnFollower = $rsUnFollower->Fetch())
					{
						$arExcludeUsers[] = $arUnFollower["USER_ID"];
					}
				}
				
				$arSourceURL = array(
					"URL" => $arTitleRes["URL"]
				);
				if (!empty($arTitleRes["CRM_URL"]))
				{
					$arSourceURL["CRM_URL"] = $arTitleRes["CRM_URL"];
				}

				foreach ($arMention as $mentionUserID)
				{
					$bHaveRights = CSocNetLogRights::CheckForUserOnly($arCommentFields["LOG_ID"], $mentionUserID);
					if (
						!$bHaveRights
						&& $arTitleRes["IS_CRM"] == "Y"
					)
					{
						$dbLog = CSocNetLog::GetList(
							array(),
							array(
								"ID" => $arCommentFields["LOG_ID"],
								"ENTITY_TYPE" => $arCommentFields["ENTITY_TYPE"],
							),
							false,
							false,
							array("ID"),
							array(
								"IS_CRM" => "Y",
								"CHECK_CRM_RIGHTS" => "Y",
								"USER_ID" => $mentionUserID,
								"USE_SUBSCRIBE" => "N"
							)
						);
						if ($arLog = $dbLog->Fetch())
						{
							$bHaveCrmRights = true;
						}
					}

					if (
						in_array($mentionUserID, $arExcludeUsers)
						|| (!$bHaveRights && !$bHaveCrmRights)
					)
					{
						continue;
					}

					$url = false;

					if (
						!empty($arSourceURL["URL"]) 
						|| !empty($arSourceURL["CRM_URL"])
					)
					{
						$arTmp = CSocNetLogTools::ProcessPath(
							$arSourceURL,
							$mentionUserID
						);

						if (
							$arTitleRes["IS_CRM"] == "Y" 
							&& !$bHaveRights 
							&& !empty($arTmp["URLS"]["CRM_URL"])
						)
						{
							$url = $arTmp["URLS"]["CRM_URL"];
						}
						else
						{
							$url = $arTmp["URLS"]["URL"];
						}
						$serverName = (strpos($url, "http://") === 0 || strpos($url, "https://") === 0 ? "" : $arTmp["SERVER_NAME"]);
					}

					$arMessageFields["TO_USER_ID"] = $mentionUserID;
					$arMessageFields["NOTIFY_MESSAGE"] = str_replace(array("#url#", "#server_name#"), array($url, $serverName), $arTitleRes["NOTIFY_MESSAGE"]);
					$arMessageFields["NOTIFY_MESSAGE_OUT"] = (!empty($arTitleRes["NOTIFY_MESSAGE_OUT"]) ? str_replace(array("#url#", "#server_name#"), array($url, $serverName), $arTitleRes["NOTIFY_MESSAGE_OUT"]) : "");

					CIMNotify::Add($arMessageFields);
				}

				$arMentionedDestCode = array();
				foreach($arMention as $val)
				{
					$arMentionedDestCode[] = "U".$val;
				}

				\Bitrix\Main\FinderDestTable::merge(array(
					"CONTEXT" => "mention",
					"CODE" => array_unique($arMentionedDestCode)
				));
			}
		}
	}

	public static function OnSendMentionGetEntityFields_Forum($arCommentFields)
	{	
		if ($arCommentFields["EVENT_ID"] != "forum")
		{
			return false;
		}

		$dbLog = CSocNetLog::GetList(
			array(),
			array(
				"ID" => $arCommentFields["LOG_ID"],
				"EVENT_ID" => "forum"
			),
			false,
			false,
			array("ID", "TITLE")
		);

		if ($arLog = $dbLog->Fetch())
		{
			$genderSuffix = "";
			$dbUsers = CUser::GetList(($by="ID"), ($order="desc"), array("ID" => $arCommentFields["USER_ID"]), array("PERSONAL_GENDER", "LOGIN", "NAME", "LAST_NAME", "SECOND_NAME"));
			if ($arUser = $dbUsers->Fetch())
			{
				$genderSuffix = $arUser["PERSONAL_GENDER"];
			}

			$strPathToLogEntry = str_replace("#log_id#", $arLog["ID"], COption::GetOptionString("socialnetwork", "log_entry_page", "/company/personal/log/#log_id#/", SITE_ID));
			$strPathToLogEntryComment = $strPathToLogEntry.(strpos($strPathToLogEntry, "?") !== false ? "&" : "?")."commentID=".$arCommentFields["ID"];

			$title = str_replace(Array("\r\n", "\n"), " ", $arLog["TITLE"]);
			$title = TruncateText($title, 100);
			$title_out = TruncateText($title, 255);
						
			$arReturn = array(
				"URL" => $strPathToLogEntryComment,
				"NOTIFY_TAG" => "FORUM|COMMENT_MENTION|".$arCommentFields["ID"],
				"NOTIFY_MESSAGE" => GetMessage("SONET_GLC_FORUM_MENTION".(strlen($genderSuffix) > 0 ? "_".$genderSuffix : ""), Array(
					"#title#" => "<a href=\"#url#\" class=\"bx-notifier-item-action\">".$title."</a>"
				)),
				"NOTIFY_MESSAGE_OUT" => GetMessage("SONET_GLC_FORUM_MENTION".(strlen($genderSuffix) > 0 ? "_".$genderSuffix : ""), Array(
					"#title#" => $title_out
				))." ("."#server_name##url#)"
			);

			return $arReturn;
		}
		else
		{
			return false;
		}
	}
	
}
?>