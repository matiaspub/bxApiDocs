<?
IncludeModuleLangFile(__FILE__);

class CAllSocNetLog
{
	/***************************************/
	/********  DATA MODIFICATION  **********/
	/***************************************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		static $arSiteWorkgroupsPage;

		global $DB, $arSocNetAllowedEntityTypes;

		if (!$arSiteWorkgroupsPage && IsModuleInstalled("extranet") && $arFields["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
		{
			$rsSite = CSite::GetList($by="sort", $order="desc", Array("ACTIVE" => "Y"));
			while($arSite = $rsSite->Fetch())
				$arSiteWorkgroupsPage[$arSite["ID"]] = COption::GetOptionString("socialnetwork", "workgroups_page", $arSite["DIR"]."workgroups/", $arSite["ID"]);
		}

		if ($ACTION != "ADD" && IntVal($ID) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException("System error 870164", "ERROR");
			return false;
		}

		$newEntityType = "";

		if ((is_set($arFields, "ENTITY_TYPE") || $ACTION=="ADD") && StrLen($arFields["ENTITY_TYPE"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GL_EMPTY_ENTITY_TYPE"), "EMPTY_ENTITY_TYPE");
			return false;
		}
		elseif (is_set($arFields, "ENTITY_TYPE"))
		{
			if (!in_array($arFields["ENTITY_TYPE"], CSocNetAllowed::GetAllowedEntityTypes()))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GL_ERROR_NO_ENTITY_TYPE"), "ERROR_NO_ENTITY_TYPE");
				return false;
			}

			$newEntityType = $arFields["ENTITY_TYPE"];
		}

		if ((is_set($arFields, "ENTITY_ID") || $ACTION=="ADD") && IntVal($arFields["ENTITY_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GL_EMPTY_ENTITY_ID"), "EMPTY_ENTITY_ID");
			return false;
		}
		elseif (is_set($arFields, "ENTITY_ID"))
		{
			if (StrLen($newEntityType) <= 0 && $ID > 0)
			{
				$arRe = CAllSocNetLog::GetByID($ID);
				if ($arRe)
					$newEntityType = $arRe["ENTITY_TYPE"];
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
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GL_ERROR_NO_ENTITY_ID"), "ERROR_NO_ENTITY_ID");
					return false;
				}
			}
			elseif ($newEntityType == SONET_ENTITY_USER)
			{
				$dbResult = CUser::GetByID($arFields["ENTITY_ID"]);
				if (!$dbResult->Fetch())
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GL_ERROR_NO_ENTITY_ID"), "ERROR_NO_ENTITY_ID");
					return false;
				}
			}
		}

		if (
			$ACTION == "ADD"
			&& (
				!is_set($arFields, "SITE_ID")
				|| (
					(is_array($arFields["SITE_ID"]) && count($arFields["SITE_ID"]) <= 0)
					|| (!is_array($arFields["SITE_ID"]) && StrLen($arFields["SITE_ID"]) <= 0)
				)
			)
		)
		{
			if ($newEntityType == SONET_ENTITY_GROUP)
			{
				$arSites = array();
				$rsGroupSite = CSocNetGroup::GetSite($arFields["ENTITY_ID"]);
				while($arGroupSite = $rsGroupSite->Fetch())
					$arSites[] = $arGroupSite["LID"];
				$arFields["SITE_ID"] = $arSites;
			}
			else
				$arFields["SITE_ID"] = array(SITE_ID);
		}

		if ((is_set($arFields, "EVENT_ID") || $ACTION=="ADD") && StrLen($arFields["EVENT_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GL_EMPTY_EVENT_ID"), "EMPTY_EVENT_ID");
			return false;
		}
		elseif (is_set($arFields, "EVENT_ID"))
		{
			$arFields["EVENT_ID"] = strtolower($arFields["EVENT_ID"]);
			$arEvent = CSocNetLogTools::FindLogEventByID($arFields["EVENT_ID"], $arFields["ENTITY_TYPE"]);
			if (!$arEvent)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GL_ERROR_NO_FEATURE_ID"), "ERROR_NO_FEATURE");
				return false;
			}
		}

		if (is_set($arFields, "USER_ID"))
		{
			$dbResult = CUser::GetByID($arFields["USER_ID"]);
			if (!$dbResult->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GL_ERROR_NO_USER_ID"), "ERROR_NO_USER_ID");
				return false;
			}
		}

		if (is_set($arFields, "LOG_DATE") && (!$DB->IsDate($arFields["LOG_DATE"], false, LANG, "FULL")))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GL_EMPTY_DATE_CREATE"), "EMPTY_LOG_DATE");
			return false;
		}

		if ((is_set($arFields, "TITLE") || $ACTION=="ADD") && StrLen($arFields["TITLE"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GL_EMPTY_TITLE"), "EMPTY_TITLE");
			return false;
		}

		if (!$GLOBALS["USER_FIELD_MANAGER"]->CheckFields("SONET_LOG", $ID, $arFields))
			return false;

		if (is_set($arFields["URL"]) && is_array($arSiteWorkgroupsPage))
			foreach($arSiteWorkgroupsPage as $groups_page)
				if (strpos($arFields["URL"], $groups_page) === 0)
					$arFields["URL"] = "#GROUPS_PATH#".substr($arFields["URL"], strlen($groups_page), strlen($arFields["URL"])-strlen($groups_page));

		return True;
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
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GL_WRONG_PARAMETER_ID"), "ERROR_NO_ID");
			return false;
		}

		$dbResult = CSocNetLog::GetList(Array(), Array("ID" => $ID));
		if ($arResult = $dbResult->GetNext())
			return $arResult;

		return False;
	}

	public static function MakeTitle($titleTemplate, $title, $url = "", $bHtml = true)
	{
		if (StrLen($url) > 0)
			$title = ($bHtml ? "<a href=\"".$url."\">".$title."</a>" : $title." [".$url."]");

		if (StrLen($titleTemplate) > 0)
		{
			if (StrPos($titleTemplate, "#TITLE#") !== false)
				return Str_Replace("#TITLE#", $title, $titleTemplate);
			else
				return $titleTemplate." \"".$title."\"";
		}
		else
		{
			return $title;
		}
	}

	/***************************************/
	/**********  SEND EVENTS  **************/
	/***************************************/
	public static function __InitUserTmp($userID)
	{
		$title = "";

		$dbUser = CUser::GetByID($userID);
		if ($arUser = $dbUser->GetNext())
			$title .= CSocNetUser::FormatName($arUser["NAME"], $arUser["LAST_NAME"], $arUser["LOGIN"]);

		return $title;
	}

	public static function __InitUsersTmp($message, $titleTemplate1, $titleTemplate2)
	{
		$arUsersID = explode(",", $message);

		$title = "";

		$bFirst = true;
		$count = 0;
		foreach ($arUsersID as $userID)
		{
			$titleTmp = CSocNetLog::__InitUserTmp($userID);

			if (StrLen($titleTmp) > 0)
			{
				if (!$bFirst)
					$title .= ", ";
				$title .= $titleTmp;
				$count++;
			}

			$bFirst = false;
		}

		return Str_Replace("#TITLE#", $title, (($count > 1) ? $titleTemplate2 : $titleTemplate1));
	}

	public static function __InitGroupTmp($groupID)
	{
		$title = "";

		$arGroup = CSocNetGroup::GetByID($groupID);
		if ($arGroup)
			$title .= $arGroup["NAME"];

		return $title;
	}

	public static function __InitGroupsTmp($message, $titleTemplate1, $titleTemplate2)
	{
		$arGroupsID = explode(",", $message);

		$title = "";

		$bFirst = true;
		$count = 0;
		foreach ($arGroupsID as $groupID)
		{
			$titleTmp = CSocNetLog::__InitGroupTmp($groupID);

			if (StrLen($titleTmp) > 0)
			{
				if (!$bFirst)
					$title .= ", ";
				$title .= $titleTmp;
				$count++;
			}

			$bFirst = false;
		}

		return Str_Replace("#TITLE#", $title, (($count > 1) ? $titleTemplate2 : $titleTemplate1));
	}

	public static function SendEventAgent($ID, $mailTemplate = "SONET_NEW_EVENT", $tmp_id = false)
	{
		if (CSocNetLog::SendEvent($ID, $mailTemplate, $tmp_id, true))
			return "";
		else
			return "CSocNetLog::SendEventAgent(".$ID.", '".$mailTemplate."', ".($tmp_id ? $tmp_id : 'false').");";
	}

	public static function SendEvent($ID, $mailTemplate = "SONET_NEW_EVENT", $tmp_id = false, $bAgent = false, $bTransport = false)
	{
		$arSocNetAllowedSubscribeEntityTypesDesc = CSocNetAllowed::GetAllowedEntityTypesDesc();

		$ID = IntVal($ID);
		$tmp_id = IntVal($tmp_id);

		if ($ID <= 0)
		{
			return false;
		}

		if ($tmp_id > 0)
		{
			$arFilter = array("ID" => $tmp_id);
		}
		else
		{
			$arFilter = array("ID" => $ID);
		}

		$dbLog = CSocNetLog::GetList(
			array(),
			$arFilter,
			false,
			false,
			array("ID", "ENTITY_TYPE", "ENTITY_ID", "USER_ID", "USER_NAME", "USER_LAST_NAME", "USER_SECOND_NAME", "USER_LOGIN", "EVENT_ID", "LOG_DATE", "TITLE_TEMPLATE", "TITLE", "MESSAGE", "TEXT_MESSAGE", "URL", "MODULE_ID", "CALLBACK_FUNC", "SITE_ID", "PARAMS", "SOURCE_ID", "GROUP_NAME", "CREATED_BY_NAME", "CREATED_BY_SECOND_NAME", "CREATED_BY_LAST_NAME", "CREATED_BY_LOGIN", "LOG_SOURCE_ID"),
			array("MIN_ID_JOIN" => true)
		);
		$arLog = $dbLog->Fetch();
		if (!$arLog)
			return $bAgent;

		if (MakeTimeStamp($arLog["LOG_DATE"]) > (time() + CTimeZone::GetOffset()))
		{
			$agent = "CSocNetLog::SendEventAgent(".$ID.", '".CUtil::addslashes($mailTemplate)."', ".($tmp_id ? $tmp_id : 'false').");";
			$rsAgents = CAgent::GetList(array("ID"=>"DESC"), array("NAME" => $agent));
			if(!$rsAgents->Fetch())
			{
				$res = CAgent::AddAgent($agent, "socialnetwork", "N", 0, $arLog["LOG_DATE"], "Y", $arLog["LOG_DATE"]);
				if(!$res)
					$GLOBALS["APPLICATION"]->ResetException();
			}
			elseif ($bAgent)
			{
				CAgent::RemoveAgent($agent, "socialnetwork");
				CAgent::AddAgent($agent, "socialnetwork", "N", 0, $arLog["LOG_DATE"], "Y", $arLog["LOG_DATE"]);
				return true;
			}
			return false;
		}

		$arEvent = CSocNetLogTools::FindLogEventByID($arLog["EVENT_ID"], $arLog["ENTITY_TYPE"]);
		if (
			$arEvent
			&& array_key_exists("CLASS_FORMAT", $arEvent)
			&& array_key_exists("METHOD_FORMAT", $arEvent)
		)
		{
			$dbSiteCurrent = CSite::GetByID(SITE_ID);
			if ($arSiteCurrent = $dbSiteCurrent->Fetch())
				if ($arSiteCurrent["LANGUAGE_ID"] != LANGUAGE_ID)
					$arLog["MAIL_LANGUAGE_ID"] = $arSiteCurrent["LANGUAGE_ID"];

			$arLog["FIELDS_FORMATTED"] = call_user_func(array($arEvent["CLASS_FORMAT"], $arEvent["METHOD_FORMAT"]), $arLog, array(), true);
		}

		if (
			array_key_exists($arLog["ENTITY_TYPE"], $arSocNetAllowedSubscribeEntityTypesDesc)
			&& array_key_exists("HAS_MY", $arSocNetAllowedSubscribeEntityTypesDesc[$arLog["ENTITY_TYPE"]])
			&& $arSocNetAllowedSubscribeEntityTypesDesc[$arLog["ENTITY_TYPE"]]["HAS_MY"] == "Y"
			&& array_key_exists("CLASS_OF", $arSocNetAllowedSubscribeEntityTypesDesc[$arLog["ENTITY_TYPE"]])
			&& array_key_exists("METHOD_OF", $arSocNetAllowedSubscribeEntityTypesDesc[$arLog["ENTITY_TYPE"]])
			&& strlen($arSocNetAllowedSubscribeEntityTypesDesc[$arLog["ENTITY_TYPE"]]["CLASS_OF"]) > 0
			&& strlen($arSocNetAllowedSubscribeEntityTypesDesc[$arLog["ENTITY_TYPE"]]["METHOD_OF"]) > 0
			&& method_exists($arSocNetAllowedSubscribeEntityTypesDesc[$arLog["ENTITY_TYPE"]]["CLASS_OF"], $arSocNetAllowedSubscribeEntityTypesDesc[$arLog["ENTITY_TYPE"]]["METHOD_OF"])
		)
		{
			$arOfEntities = call_user_func(array($arSocNetAllowedSubscribeEntityTypesDesc[$arLog["ENTITY_TYPE"]]["CLASS_OF"], $arSocNetAllowedSubscribeEntityTypesDesc[$arLog["ENTITY_TYPE"]]["METHOD_OF"]), $arLog["ENTITY_ID"]);
		}

		if ($bTransport)
		{
			$arListParams = array(
				"USE_SUBSCRIBE" => "Y",
				"ENTITY_TYPE" => $arLog["ENTITY_TYPE"],
				"ENTITY_ID" => $arLog["ENTITY_ID"],
				"EVENT_ID" => $arLog["EVENT_ID"],
				"USER_ID" => $arLog["USER_ID"],
				"OF_ENTITIES" => $arOfEntities,
				"TRANSPORT" => array("M", "X")
			);

			$arLogSites = array();
			$rsLogSite = CSocNetLog::GetSite($ID);

			while($arLogSite = $rsLogSite->Fetch())
				$arLogSites[] = $arLogSite["LID"];

			if (CModule::IncludeModule("extranet"))
			{
				$arSites = array();
				$dbSite = CSite::GetList($by="sort", $order="desc", array("ACTIVE" => "Y"));
				while($arSite = $dbSite->Fetch())
					$arSites[$arSite["ID"]] = array(
						"DIR" => (strlen(trim($arSite["DIR"])) > 0 ? $arSite["DIR"] : "/"),
						"SERVER_NAME" => (strlen(trim($arSite["SERVER_NAME"])) > 0 ? $arSite["SERVER_NAME"] : COption::GetOptionString("main", "server_name", $_SERVER["HTTP_HOST"]))
					);

				$extranet_site_id = CExtranet::GetExtranetSiteID();
				$intranet_site_id = CSite::GetDefSite();

				$arIntranetUsers = CExtranet::GetIntranetUsers();
			}

			$dbSubscribers = CSocNetLogEvents::GetList(
				array("TRANSPORT" => "DESC"),
				array(
					"USER_ACTIVE" => "Y",
					"SITE_ID" => array_merge($arLogSites, array(false))
				),
				false,
				false,
				array("USER_ID", "SITE_ID", "ENTITY_TYPE", "ENTITY_ID", "ENTITY_CB", "ENTITY_MY", "USER_NAME", "USER_LAST_NAME", "USER_LOGIN", "USER_LID", "USER_EMAIL", "TRANSPORT"),
				$arListParams
			);

			$arListParams = array(
				"USE_SUBSCRIBE" => "Y",
				"ENTITY_TYPE" => $arLog["ENTITY_TYPE"],
				"ENTITY_ID" => $arLog["ENTITY_ID"],
				"EVENT_ID" => $arLog["EVENT_ID"],
				"USER_ID" => $arLog["USER_ID"],
				"OF_ENTITIES" => $arOfEntities,
				"TRANSPORT" => "N"
			);

			$dbUnSubscribers = CSocNetLogEvents::GetList(
				array("TRANSPORT" => "DESC"),
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
				$arUnSubscribers[] = $arUnSubscriber["USER_ID"]."_".$arUnSubscriber["ENTITY_TYPE"]."_".$arUnSubscriber["ENTITY_ID"]."_".$arUnSubscriber["ENTITY_MY"]."_".$arUnSubscriber["ENTITY_CB"]."_".$arUnSubscriber["EVENT_ID"];

			$bHasAccessAll = CSocNetLogRights::CheckForUserAll($arLog["ID"]);

			$arSentUserID = array("M" => array(), "X" => array());
			while ($arSubscriber = $dbSubscribers->Fetch())
			{
				if (
					is_array($arIntranetUsers)
					&& !in_array($arSubscriber["USER_ID"], $arIntranetUsers)
					&& !in_array($extranet_site_id, $arLogSites)
				)
					continue;

				if (
					array_key_exists($arSubscriber["TRANSPORT"], $arSentUserID)
					&& in_array($arSubscriber["USER_ID"], $arSentUserID[$arSubscriber["TRANSPORT"]])
				)
					continue;

				if (
					intval($arSubscriber["ENTITY_ID"]) != 0
					&& $arSubscriber["EVENT_ID"] == "all"
					&&
					(
						in_array($arSubscriber["USER_ID"]."_".$arSubscriber["ENTITY_TYPE"]."_".$arSubscriber["ENTITY_ID"]."_N_".$arSubscriber["ENTITY_CB"]."_".$arLog["EVENT_ID"], $arUnSubscribers)
						|| in_array($arSubscriber["USER_ID"]."_".$arSubscriber["ENTITY_TYPE"]."_".$arSubscriber["ENTITY_ID"]."_Y_".$arSubscriber["ENTITY_CB"]."_".$arLog["EVENT_ID"], $arUnSubscribers)
					)
				)
					continue;
				elseif (
					intval($arSubscriber["ENTITY_ID"]) == 0
					&& $arSubscriber["ENTITY_CB"] == "N"
					&& $arSubscriber["EVENT_ID"] != "all"
					&&
					(
						in_array($arSubscriber["USER_ID"]."_".$arSubscriber["ENTITY_TYPE"]."_".$arLog["ENTITY_ID"]."_Y_N_all", $arUnSubscribers)
						|| in_array($arSubscriber["USER_ID"]."_".$arSubscriber["ENTITY_TYPE"]."_".$arLog["ENTITY_ID"]."_N_N_all", $arUnSubscribers)
						|| in_array($arSubscriber["USER_ID"]."_".$arSubscriber["ENTITY_TYPE"]."_".$arLog["ENTITY_ID"]."_Y_N_".$arLog["EVENT_ID"], $arUnSubscribers)
						|| in_array($arSubscriber["USER_ID"]."_".$arSubscriber["ENTITY_TYPE"]."_".$arLog["ENTITY_ID"]."_N_N_".$arLog["EVENT_ID"], $arUnSubscribers)
					)
				)
					continue;

				$arSentUserID[$arSubscriber["TRANSPORT"]][] = $arSubscriber["USER_ID"];

				if (!$bHasAccessAll)
				{
					$bHasAccess = CSocNetLogRights::CheckForUserOnly($arLog["ID"], $arSubscriber["USER_ID"]);
					if (!$bHasAccess)
						continue;
				}

				if (CModule::IncludeModule("extranet"))
				{
					$server_name = $arSites[((!in_array($arSubscriber["USER_ID"], $arIntranetUsers) && $extranet_site_id) ? $extranet_site_id : $intranet_site_id)]["SERVER_NAME"];
					$arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL_TO_SEND"] = str_replace(
						array("#SERVER_NAME#", "#GROUPS_PATH#"),
						array(
							$server_name,
							COption::GetOptionString("socialnetwork", "workgroups_page", false, ((!in_array($arSubscriber["USER_ID"], $arIntranetUsers) && $extranet_site_id) ? $extranet_site_id : $intranet_site_id))
						),
						$arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL"]
					);
					$arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["MESSAGE_TO_SEND"] = str_replace(
						array("#SERVER_NAME#", "#GROUPS_PATH#"),
						array(
							$server_name,
							COption::GetOptionString("socialnetwork", "workgroups_page", false, ((!in_array($arSubscriber["USER_ID"], $arIntranetUsers) && $extranet_site_id) ? $extranet_site_id : $intranet_site_id))
						),
						$arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["MESSAGE"]
					);
				}
				else
					$arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["MESSAGE_TO_SEND"] = $arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["MESSAGE"];

				switch ($arSubscriber["TRANSPORT"])
				{
					case "X":

						if (
							array_key_exists("URL_TO_SEND", $arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
							&& strlen($arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL_TO_SEND"]) > 0
						)
							$link = GetMessage("SONET_GL_SEND_EVENT_LINK").$arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL_TO_SEND"];
						elseif (
							array_key_exists("URL", $arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
							&& strlen($arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL"]) > 0
						)
							$link = GetMessage("SONET_GL_SEND_EVENT_LINK").$arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL"];
						else
							$link = "";

						$arMessageFields = array(
							"FROM_USER_ID" => (intval($arLog["USER_ID"]) > 0 ? $arLog["USER_ID"] : 1),
							"TO_USER_ID" => $arSubscriber["USER_ID"],
							"MESSAGE" => $arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["TITLE"]." #BR#".$arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["MESSAGE_TO_SEND"].(strlen($link) > 0 ? "#BR# ".$link : ""),
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
						$arFields["TITLE"] = str_replace("#BR#", "\n", $arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["TITLE"]);
						$arFields["MESSAGE"] = str_replace("#BR#", "\n", $arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["MESSAGE_TO_SEND"]);
						$arFields["ENTITY"] = $arLog["FIELDS_FORMATTED"]["ENTITY"]["FORMATTED"];
						$arFields["ENTITY_TYPE"] = $arLog["FIELDS_FORMATTED"]["ENTITY"]["TYPE_MAIL"];

						if (
							array_key_exists("URL_TO_SEND", $arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
							&& strlen($arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL_TO_SEND"]) > 0
						)
							$arFields["URL"] = $arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL_TO_SEND"];
						elseif (
							array_key_exists("URL", $arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
							&& strlen($arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL"]) > 0
						)
							$arFields["URL"] = $arLog["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["URL"];
						else
							$arFields["URL"] = $arLog["URL"];

						if (CModule::IncludeModule("extranet"))
							$arUserGroup = CUser::GetUserGroup($arSubscriber["USER_ID"]);

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
									continue;
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

		CSocNetLog::CounterIncrement($arLog["ID"], $arLog["EVENT_ID"], $arOfEntities);

		return true;
	}

	public static function CounterIncrement($entityId, $event_id = false, $arOfEntities = false, $type = "L", $bForAllAccess = false)
	{
		if (intval($entityId) <= 0)
		{
			return false;
		}

		if (
			!$bForAllAccess
			|| strtolower($GLOBALS["DB"]->type) != "mysql"
		)
		{
			CUserCounter::IncrementWithSelect(
				CSocNetLogCounter::GetSubSelect2(
					$entityId,
					array(
						"TYPE" => $type,
						"FOR_ALL_ACCESS" => $bForAllAccess,
						"MULTIPLE" => "Y"
					)
				)
			);
		}
		else // for all, mysql only
		{
			$tag = time();
			CUserCounter::IncrementWithSelect(
				CSocNetLogCounter::GetSubSelect2(
					$entityId,
					array(
						"TYPE" => $type,
						"FOR_ALL_ACCESS_ONLY" => true,
						"TAG_SET" => $tag,
						"MULTIPLE" => "Y"
					)
				),
				false, // sendpull
				array(
					"TAG_SET" => $tag
				)
			);

			CUserCounter::IncrementWithSelect(
				CSocNetLogCounter::GetSubSelect2(
					$entityId,
					array(
						"TYPE" => $type,
						"FOR_ALL_ACCESS_ONLY" => false,
						"MULTIPLE" => "Y"
					)
				),
				true, // sendpull
				array(
					"TAG_CHECK" => $tag
				)
			);
		}

		if ($event_id == "blog_post_important")
		{
			CUserCounter::IncrementWithSelect(
				CSocNetLogCounter::GetSubSelect2(
					$entityId,
					array(
						"TYPE" => "L", 
						"CODE" => "'BLOG_POST_IMPORTANT'",
						"FOR_ALL_ACCESS" => $bForAllAccess,
						"MULTIPLE" => "N"
					)
				)
			);
		}
	}

	public static function CounterDecrement($log_id, $event_id = false, $type = "L", $bForAllAccess = false)
	{
		if (intval($log_id) <= 0)
			return false;

		CUserCounter::IncrementWithSelect(
			CSocNetLogCounter::GetSubSelect2(
				$log_id, 
				array(
					"TYPE" => $type,
					"DECREMENT" => true,
					"FOR_ALL_ACCESS" => $bForAllAccess
				)
			)
		);

		if ($event_id == "blog_post_important")
		{
			CUserCounter::IncrementWithSelect(
				CSocNetLogCounter::GetSubSelect2(
					$log_id, 
					array(
						"TYPE" => "L",
						"CODE" => "'BLOG_POST_IMPORTANT'",
						"DECREMENT" => true,
						"FOR_ALL_ACCESS" => $bForAllAccess
					)
				)
			);
		}
	}

	public static function ClearOldAgent()
	{
		return "";
	}

	public static function GetSign($url, $userID = false, $site_id = false)
	{
		if (!$url || strlen(trim($url)) <= 0)
			return false;

		if (!$userID)
			$userID = $GLOBALS["USER"]->GetID();

		if ($hash = CUser::GetHitAuthHash($url, $userID))
			return $hash;
		else
		{
			$hash = CUser::AddHitAuthHash($url, $userID, $site_id);
			return $hash;
		}
	}

	public static function CheckSign($sign, $userId)
	{
		return (md5($userId."||".CSocNetLog::GetUniqLogID()) == $sign);
	}

	public static function OnSocNetLogFormatEvent($arEvent, $arParams)
	{
		if ($arEvent["EVENT_ID"] == "system" || $arEvent["EVENT_ID"] == "system_friends" || $arEvent["EVENT_ID"] == "system_groups")
		{
			$arEvent["TITLE_TEMPLATE"] = "";
			$arEvent["URL"] = "";

			switch ($arEvent["TITLE"])
			{
				case "join":
					list($titleTmp, $messageTmp) = CSocNetLog::InitUsersTmp($arEvent["MESSAGE"], GetMessage("SONET_GL_TITLE_JOIN1"), GetMessage("SONET_GL_TITLE_JOIN2"), $arParams);
					$arEvent["TITLE"] = $titleTmp;
					$arEvent["MESSAGE_FORMAT"] = $messageTmp;
					break;
				case "unjoin":
					list($titleTmp, $messageTmp) = CSocNetLog::InitUsersTmp($arEvent["MESSAGE"], GetMessage("SONET_GL_TITLE_UNJOIN1"), GetMessage("SONET_GL_TITLE_UNJOIN2"), $arParams);
					$arEvents["TITLE"] = $titleTmp;
					$arEvents["MESSAGE_FORMAT"] = $messageTmp;
					break;
				case "moderate":
					list($titleTmp, $messageTmp) = CSocNetLog::InitUsersTmp($arEvent["MESSAGE"], GetMessage("SONET_GL_TITLE_MODERATE1"), GetMessage("SONET_GL_TITLE_MODERATE2"), $arParams);
					$arEvent["TITLE"] = $titleTmp;
					$arEvent["MESSAGE_FORMAT"] = $messageTmp;
					break;
				case "unmoderate":
					list($titleTmp, $messageTmp) = CSocNetLog::InitUsersTmp($arEvent["MESSAGE"], GetMessage("SONET_GL_TITLE_UNMODERATE1"), GetMessage("SONET_GL_TITLE_UNMODERATE2"), $arParams);
					$arEvent["TITLE"] = $titleTmp;
					$arEvent["MESSAGE_FORMAT"] = $messageTmp;
					break;
				case "owner":
					list($titleTmp, $messageTmp) = CSocNetLog::InitUsersTmp($arEvent["MESSAGE"], GetMessage("SONET_GL_TITLE_OWNER1"), GetMessage("SONET_GL_TITLE_OWNER1"), $arParams);
					$arEvent["TITLE"] = $titleTmp;
					$arEvent["MESSAGE_FORMAT"] = $messageTmp;
					break;
				case "friend":
					list($titleTmp, $messageTmp) = CSocNetLog::InitUsersTmp($arEvent["MESSAGE"], GetMessage("SONET_GL_TITLE_FRIEND1"), GetMessage("SONET_GL_TITLE_FRIEND1"), $arParams);
					$arEvent["TITLE"] = $titleTmp;
					$arEvent["MESSAGE_FORMAT"] = $messageTmp;
					break;
				case "unfriend":
					list($titleTmp, $messageTmp) = CSocNetLog::InitUsersTmp($arEvent["MESSAGE"], GetMessage("SONET_GL_TITLE_UNFRIEND1"), GetMessage("SONET_GL_TITLE_UNFRIEND1"), $arParams);
					$arEvent["TITLE"] = $titleTmp;
					$arEvent["MESSAGE_FORMAT"] = $messageTmp;
					break;
				case "group":
					list($titleTmp, $messageTmp) = CSocNetLog::InitGroupsTmp($arEvent["MESSAGE"], GetMessage("SONET_GL_TITLE_GROUP1"), GetMessage("SONET_GL_TITLE_GROUP1"), $arParams);
					$arEvent["TITLE"] = $titleTmp;
					$arEvent["MESSAGE_FORMAT"] = $messageTmp;
					break;
				case "ungroup":
					list($titleTmp, $messageTmp) = CSocNetLog::InitGroupsTmp($arEvent["MESSAGE"], GetMessage("SONET_GL_TITLE_UNGROUP1"), GetMessage("SONET_GL_TITLE_UNGROUP1"), $arParams);
					$arEvent["TITLE"] = $titleTmp;
					$arEvent["MESSAGE_FORMAT"] = $messageTmp;
					break;
				case "exclude_user":
					list($titleTmp, $messageTmp) = CSocNetLog::InitGroupsTmp($arEvent["MESSAGE"], GetMessage("SONET_GL_TITLE_EXCLUDE_USER1"), GetMessage("SONET_GL_TITLE_EXCLUDE_USER1"), $arParams);
					$arEvent["TITLE"] = $titleTmp;
					$arEvent["MESSAGE_FORMAT"] = $messageTmp;
					break;
				case "exclude_group":
					list($titleTmp, $messageTmp) = CSocNetLog::InitUsersTmp($arEvent["MESSAGE"], GetMessage("SONET_GL_TITLE_EXCLUDE_GROUP1"), GetMessage("SONET_GL_TITLE_EXCLUDE_GROUP1"), $arParams);
					$arEvent["TITLE"] = $titleTmp;
					$arEvent["MESSAGE_FORMAT"] = $messageTmp;
					break;
				default:
					continue;
					break;
			}
		}
		return $arEvent;
	}

	public static function InitUserTmp($userID, $arParams, $bCurrentUserIsAdmin = "unknown", $bRSS = false)
	{
		$title = "";
		$message = "";
		$bUseLogin = $arParams['SHOW_LOGIN'] != "N" ? true : false;

		$dbUser = CUser::GetByID($userID);
		if ($arUser = $dbUser->Fetch())
		{
			if ($bCurrentUserIsAdmin == "unknown")
				$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin();

			$canViewProfile = CSocNetUserPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), $arUser["ID"], "viewprofile", $bCurrentUserIsAdmin);
			$pu = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arUser["ID"]));

			if (!$bRSS && $canViewProfile)
				$title .= "<a href=\"".$pu."\">";
			$title .= CUser::FormatName($arParams['NAME_TEMPLATE'], $arUser, $bUseLogin);
			if (!$bRSS && $canViewProfile)
				$title .= "</a>";

			if (intval($arUser["PERSONAL_PHOTO"]) <= 0)
			{
				switch ($arUser["PERSONAL_GENDER"])
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
				$arUser["PERSONAL_PHOTO"] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, SITE_ID);
			}
			$arImage = CSocNetTools::InitImage($arUser["PERSONAL_PHOTO"], 100, "/bitrix/images/socialnetwork/nopic_user_100.gif", 100, $pu, $canViewProfile);

			$message = $arImage["IMG"];
		}

		return array($title, $message);
	}

	public static function InitUsersTmp($message, $titleTemplate1, $titleTemplate2, $arParams, $bCurrentUserIsAdmin = "unknown", $bRSS = false)
	{
		$arUsersID = explode(",", $message);

		$message = "";
		$title = "";

		$bFirst = true;
		$count = 0;

		if ($bCurrentUserIsAdmin == "unknown")
			$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin();

		foreach ($arUsersID as $userID)
		{
			list($titleTmp, $messageTmp) = CSocNetLog::InitUserTmp($userID, $arParams, $bCurrentUserIsAdmin, $bRSS);

			if (StrLen($titleTmp) > 0)
			{
				if (!$bFirst)
					$title .= ", ";
				$title .= $titleTmp;
				$count++;
			}

			if (StrLen($messageTmp) > 0)
			{
				if (!$bFirst)
					$message .= " ";
				$message .= $messageTmp;
			}

			$bFirst = false;
		}
		return array(Str_Replace("#TITLE#", $title, (($count > 1) ? $titleTemplate2 : $titleTemplate1)), $message);
	}

	public static function InitGroupTmp($groupID, $arParams, $bRSS = false)
	{
		$title = "";
		$message = "";

		$arGroup = CSocNetGroup::GetByID($groupID);
		if ($arGroup)
		{
			$pu = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $arGroup["ID"]));

			if (!$bRSS)
				$title .= "<a href=\"".$pu."\">";
			$title .= $arGroup["NAME"];
			if (!$bRSS)
				$title .= "</a>";

			if (intval($arGroup["IMAGE_ID"]) <= 0)
				$arGroup["IMAGE_ID"] = COption::GetOptionInt("socialnetwork", "default_group_picture", false, SITE_ID);

			$arImage = CSocNetTools::InitImage($arGroup["IMAGE_ID"], 100, "/bitrix/images/socialnetwork/nopic_group_100.gif", 100, $pu, true);

			$message = $arImage["IMG"];
		}

		return array($title, $message);
	}

	public static function InitGroupsTmp($message, $titleTemplate1, $titleTemplate2, $arParams, $bRSS = false)
	{
		$arGroupsID = explode(",", $message);

		$message = "";
		$title = "";

		$bFirst = true;
		$count = 0;
		foreach ($arGroupsID as $groupID)
		{
			list($titleTmp, $messageTmp) = CSocNetLog::InitGroupTmp($groupID, $arParams, $bRSS);

			if (StrLen($titleTmp) > 0)
			{
				if (!$bFirst)
					$title .= ", ";
				$title .= $titleTmp;
				$count++;
			}

			if (StrLen($messageTmp) > 0)
			{
				if (!$bFirst)
					$message .= " ";
				$message .= $messageTmp;
			}

			$bFirst = false;
		}

		return array(Str_Replace("#TITLE#", $title, (($count > 1) ? $titleTemplate2 : $titleTemplate1)), $message);
	}

	public static function ShowGroup($arEntityDesc, $strEntityURL, $arParams)
	{
		return CSocNetLogTools::ShowGroup($arEntityDesc, $strEntityURL, $arParams);
	}

	public static function ShowUser($arEntityDesc, $strEntityURL, $arParams)
	{
		return CSocNetLogTools::ShowUser($arEntityDesc, $strEntityURL, $arParams);
	}

	public static function FormatEvent_FillTooltip($arFields, $arParams)
	{
		return CSocNetLogTools::FormatEvent_FillTooltip($arFields, $arParams);
	}

	public static function FormatEvent_CreateAvatar($arFields, $arParams, $source = "CREATED_BY_")
	{
		return CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams, $source);
	}

	public static function FormatEvent_IsMessageShort($message, $short_message = false)
	{
		return CSocNetLogTools::FormatEvent_IsMessageShort($message, $short_message);
	}

	public static function FormatEvent_BlogPostComment($arFields, $arParams, $bMail = false)
	{
		return CSocNetLogTools::FormatEvent_Blog($arFields, $arParams, $bMail);
	}

	public static function FormatEvent_Forum($arFields, $arParams, $bMail = false)
	{
		return CSocNetLogTools::FormatEvent_Forum($arFields, $arParams, $bMail);
	}

	public static function FormatEvent_Photo($arFields, $arParams, $bMail = false)
	{
		return CSocNetLogTools::FormatEvent_Photo($arFields, $arParams, $bMail);
	}

	public static function FormatEvent_Files($arFields, $arParams, $bMail = false)
	{
		return CSocNetLogTools::FormatEvent_Files($arFields, $arParams, $bMail);
	}

	public static function FormatEvent_Task($arFields, $arParams, $bMail = false)
	{
		return CSocNetLogTools::FormatEvent_Task($arFields, $arParams, $bMail);
	}

	public static function FormatEvent_SystemGroups($arFields, $arParams, $bMail = false)
	{
		return CSocNetLogTools::FormatEvent_SystemGroups($arFields, $arParams, $bMail);
	}

	public static function FormatEvent_SystemFriends($arFields, $arParams, $bMail = false)
	{
		return CSocNetLogTools::FormatEvent_SystemFriends($arFields, $arParams, $bMail);
	}

	public static function FormatEvent_System($arFields, $arParams, $bMail = false)
	{
		return CSocNetLogTools::FormatEvent_System($arFields, $arParams, $bMail);
	}

	public static function FormatEvent_Microblog($arFields, $arParams, $bMail = false)
	{
		return CSocNetLogTools::FormatEvent_Microblog($arFields, $arParams, $bMail);
	}

	public static function SetCacheLastLogID($id)
	{
		return CSocNetLogTools::SetCacheLastLogID("log", $id);
	}

	public static function GetCacheLastLogID()
	{
		return CSocNetLogTools::GetCacheLastLogID("log");
	}

	public static function SetUserCache($user_id, $max_id, $max_viewed_id, $count)
	{
		CSocNetLogTools::SetUserCache("log", $user_id, $max_id, $max_viewed_id, $count);
	}

	public static function GetUserCache($user_id)
	{
		return CSocNetLogTools::GetUserCache("log", $user_id);
	}

	public static function GetSite($log_id)
	{
		global $DB;
		$strSql = "SELECT L.*, LS.* FROM b_sonet_log_site LS, b_lang L WHERE L.LID=LS.SITE_ID AND LS.LOG_ID=".IntVal($log_id);
		return $DB->Query($strSql);
	}
	
	public static function GetSimpleOrQuery($val, $key, $strOperation, $strNegative, $OrFields, &$arFields, &$arFilter)
	{
		if ($strNegative != "Y")
		{
			$arOrFields = explode("|", $OrFields);
			if (count($arOrFields) > 1)
			{
				$strOrFields = "";
				foreach($arOrFields as $i => $field)
				{
					if ($i > 0)
						$strOrFields .= " OR ";
					$strOrFields .= "(".$field." ".$strOperation." '".$GLOBALS["DB"]->ForSql($val)."')";
				}
				return $strOrFields;
			}
			else
				return false;
		}
		else
			return false;
	}
}
?>