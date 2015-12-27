<?
##############################################
# Bitrix Site Manager Forum                  #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
IncludeModuleLangFile(__FILE__);

class CAllForumMessage
{
	//---------------> Message add, update, delete
	public static function CanUserAddMessage($TID, $arUserGroups, $iUserID = 0, $ExternalPermission = false)
	{
		$TID = intVal($TID);
		$arTopic = ($TID > 0 ? CForumTopic::GetByID($TID) : false);
		if ($arTopic)
		{
			if (!CForumUser::IsLocked($iUserID)):
				$strPerms = ($ExternalPermission == false ? CForumNew::GetUserPermission($arTopic["FORUM_ID"], $arUserGroups) : $ExternalPermission);
			else:
				$strPerms = CForumNew::GetPermissionUserDefault($arTopic["FORUM_ID"], $arUserGroups);
			endif;
			if ($strPerms >= "Y")
				return true;
			elseif ($strPerms < "I")
				return false;
			$arForum = CForumNew::GetByID($arTopic["FORUM_ID"]);
			if ($arForum["ACTIVE"] != "Y")
				return False;
			return ($strPerms < "U" && ($arTopic["STATE"] != "Y") ? false : true);
		}
		return False;
	}

	public static function CanUserUpdateMessage($MID, $arUserGroups, $iUserID = 0, $ExternalPermission = false)
	{
		$MID = intVal($MID);
		$arMessage = CForumMessage::GetByIDEx($MID, array("GET_FORUM_INFO" => "Y", "GET_TOPIC_INFO" => "Y", "FILTER" => "N"));
		$arTopic = $arMessage["TOPIC_INFO"];
		$arForum = $arMessage["FORUM_INFO"];
		if ($arMessage)
		{
			if (!CForumUser::IsLocked($iUserID)):
				$strPerms = ($ExternalPermission == false ? CForumNew::GetUserPermission($arTopic["FORUM_ID"], $arUserGroups) : $ExternalPermission);
			else:
				$strPerms = CForumNew::GetPermissionUserDefault($arTopic["FORUM_ID"], $arUserGroups);
			endif;
			if ($strPerms >= "Y")
				return true;
			if ($strPerms < "I" || $arForum["ACTIVE"] !="Y")
				return false;
			elseif ($strPerms >= "U")
				return true;
			if ($arTopic["STATE"] != "Y")
				return false;
			$iUserID = intVal($iUserID);
			if ($iUserID <= 0 || intVal($arMessage["AUTHOR_ID"]) != $iUserID)
				return false;
			if (COption::GetOptionString("forum", "USER_EDIT_OWN_POST", "N") == "Y")
				return true;
			$iCnt = CForumMessage::GetList(array("ID" => "ASC"), array("TOPIC_ID"=>$arTopic["ID"], ">ID"=>$MID), True);
			if (intVal($iCnt) <= 0)
				return true;
		}
		return false;
	}

	public static function CanUserDeleteMessage($MID, $arUserGroups, $iUserID = 0, $ExternalPermission = false)
	{
		$MID = intVal($MID);
		$arMessage = CForumMessage::GetByIDEx($MID, array("GET_FORUM_INFO" => "Y", "GET_TOPIC_INFO" => "N", "FILTER" => "N"));
		$arForum = $arMessage["FORUM_INFO"];
		if ($arMessage)
		{
			$FID = intVal($arMessage["FORUM_ID"]);
			if (!CForumUser::IsLocked($iUserID)):
				$strPerms = ($ExternalPermission == false ? CForumNew::GetUserPermission($arForum["ID"], $arUserGroups) : $ExternalPermission);
			else:
				$strPerms = CForumNew::GetPermissionUserDefault($arForum["ID"], $arUserGroups);
			endif;
			if ($strPerms >= "Y")
				return true;
			elseif ($arForum["ACTIVE"] != "Y")
				return false;
			return ($strPerms >= "U" ? true : false);
		}
		return false;
	}

	public static function CheckFields($ACTION, &$arFields, $ID = 0, $arParams = array())
	{
		$aMsg = array();
		$ID = intVal($ID);

		$arMessage = $arFields;

		if ($ACTION != "ADD" && $ID > 0 && (!is_set($arFields, "AUTHOR_NAME") || !is_set($arFields, "TOPIC_ID") || !is_set($arFields, "FORUM_ID")))
		{
			$arMessage = CForumMessage::GetByID($ID, array("FILTER" => "N"));
		}

		$bDeduplication = true;
		if ((is_set($arFields, "FORUM_ID") || $ACTION=="ADD") && intVal($arFields["FORUM_ID"])<=0)
		{
			$aMsg[] = array(
				"id"=>'empty_forum_id',
				"text" => GetMessage("F_ERR_EMPTY_FORUM_ID"));
		}
		else
		{
			$forumID = (($ACTION == 'ADD') ? intVal($arFields["FORUM_ID"]) : intVal($arMessage["FORUM_ID"]));
			$arForum = CForumNew::GetByID($forumID);
			if (!is_array($arForum))
			{
				$aMsg[] = array(
					"id"=>'invalid_forum_id',
					"text" => GetMessage("F_ERR_INVALID_FORUM_ID"));
			}
			else
			{
				$bDeduplication = ($arForum['DEDUPLICATION'] === 'Y');
			}
		}

		if ((is_set($arFields, "TOPIC_ID") || $ACTION=="ADD") && intVal($arFields["TOPIC_ID"])<=0)
		{
			$aMsg[] = array(
				"id"=>'empty_topic_id',
				"text" => GetMessage("F_ERR_EMPTY_TOPIC_ID"));
		}
		if ((is_set($arFields, "AUTHOR_NAME") || $ACTION=="ADD") && strLen($arFields["AUTHOR_NAME"])<=0)
		{
			$aMsg[] = array(
				"id"=>'empty_author_name',
				"text" => GetMessage("F_ERR_EMPTY_AUTHOR_NAME"));
		}

		if ((is_set($arFields, "POST_MESSAGE") || $ACTION=="ADD") && strLen($arFields["POST_MESSAGE"])<=0 &&
				(!is_set($arFields, 'FILES') || sizeof($arFields['FILES']) < 1))
		{
			$aMsg[] = array(
				"id"=>'empty_post_message',
				"text" => GetMessage("F_ERR_EMPTY_POST_MESSAGE"));
		}
		elseif (is_set($arFields, "POST_MESSAGE") && $arFields["NEW_TOPIC"] != "Y")
		{
			$arFields["POST_MESSAGE_CHECK"] = md5($arFields["POST_MESSAGE"] . (is_set($arFields, 'FILES')?serialize($arFields['FILES']):''));

			if ($bDeduplication)
			{
				$iCnt = CForumMessage::GetList(array(), array("TOPIC_ID" => $arMessage["TOPIC_ID"], "!ID" => $ID,
					"AUTHOR_NAME" => $arMessage["AUTHOR_NAME"], "POST_MESSAGE_CHECK" => $arFields["POST_MESSAGE_CHECK"]), true);
				if (intVal($iCnt)>0)
				{
					$aMsg[] = array(
						"id"=>'message_already_exists',
						"text" => GetMessage("F_ERR_MESSAGE_ALREADY_EXISTS"));
				}
			}
		}

		if (!is_set($arFields, "FILES"))
			$arFields["FILES"] = array();
		if (is_set($arFields, "ATTACH_IMG"))
		{
			if (!empty($arFields["ATTACH_IMG"]))
				$arFields["FILES"][] = $arFields["ATTACH_IMG"];
			unset($arFields["ATTACH_IMG"]);
		}
		if (!empty($arFields["FILES"]))
		{
			if ($ID > 0)
			{
				$arParams = !empty($arParams) ? $arParams : CForumMessage::GetByID($ID, array("FILTER" => "N"));
				$arParams["MESSAGE_ID"] = $ID;
			}
			else
			{
				$arParams = array("FORUM_ID" => $arMessage["FORUM_ID"], "MESSAGE_ID" => 0, "USER_ID" => $arFields["AUTHOR_ID"]);
			}
			if (!CForumFiles::CheckFields($arFields["FILES"], $arParams))
			{
				$res = $GLOBALS["APPLICATION"]->GetException();
				$aMsg[] = array(
					"id" => 'attach_error',
					"text" => $res->GetString());
			}
		}
		else
		{
			unset($arFields["FILES"]);
		}

		if (intVal($arFields["TOPIC_ID"]) > 0)
		{
			$res = CForumTopic::GetById($arFields["TOPIC_ID"]);
			if (!$res)
			{
				$aMsg[] = array(
					"id" => 'topic_is_not_exists',
					"text" => GetMessage("F_ERR_TOPIC_IS_NOT_EXISTS"));
			}
			elseif ($res["STATE"] == "L")
			{
				$aMsg[] = array(
					"id" => 'topic_is_link',
					"text" => GetMessage("F_ERR_TOPIC_IS_LINK"));
			}
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException(array_reverse($aMsg));
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}
		else if(!$GLOBALS["USER_FIELD_MANAGER"]->CheckFields("FORUM_MESSAGE", $ID, $arFields))
			return false;

		if (is_set($arFields, "AUTHOR_ID") || $ACTION=="ADD") $arFields["AUTHOR_ID"] = intVal($arFields["AUTHOR_ID"]) <= 0 ? false : $arFields["AUTHOR_ID"];
		if (is_set($arFields, "USE_SMILES") || $ACTION=="ADD") $arFields["USE_SMILES"] = ($arFields["USE_SMILES"] == "N" ? "N" : "Y");
		if (is_set($arFields, "NEW_TOPIC") || $ACTION=="ADD") $arFields["NEW_TOPIC"] = ($arFields["NEW_TOPIC"] == "Y" ? "Y" : "N");
		if (is_set($arFields, "APPROVED") || $ACTION=="ADD") $arFields["APPROVED"] = ($arFields["APPROVED"] == "N" ? "N" : "Y");
		if (is_set($arFields, "SOURCE_ID") || $ACTION=="ADD") $arFields["SOURCE_ID"] = ($arFields["SOURCE_ID"] == "EMAIL" ? "EMAIL" : "WEB");

// ********************************* QUOTA ********************************* //
//		if ((!empty($arFields["POST_MESSAGE"])) && (COption::GetOptionInt("main", "disk_space") > 0))
//		{
//			$quota = new CDiskQuota();
//			if (!$quota->checkDiskQuota(strLen($arFields["POST_MESSAGE"])))
//				return false;
//		}
// ********************************* QUOTA ********************************* //

		return True;
	}

	public static function Update($ID, $arFields, $skip_counts = false, $strUploadDir = false)
	{
		global $DB;
		$ID = intVal($ID);
		$strSql = "";
		$strUploadDir = ($strUploadDir === false ? "forum" : $strUploadDir);

		if ($ID <= 0 || !CForumMessage::CheckFields("UPDATE", $arFields, $ID) || empty($arFields))
			return false;

//		if (!$skip_counts || IsModuleInstalled("search") || is_array($arFields["ATTACH_IMG"]) || is_array($arFields["FILES"]))
//		{
			$arMessage_prev = CForumMessage::GetByID($ID, array("FILTER" => "N"));
//		}

		if 	(is_set($arFields, "POST_MESSAGE") || is_set($arFields, "FORUM_ID"))
		{
			$arFields["POST_MESSAGE_HTML"] = '';
			$arFields["POST_MESSAGE_FILTER"] = '';
		}
		$arr = array(
			"AUTHOR_NAME" => $arMessage_prev["AUTHOR_NAME"],
			"AUTHOR_EMAIL" => $arMessage_prev["AUTHOR_EMAIL"],
			"EDITOR_NAME" => $arMessage_prev["EDITOR_NAME"],
			"EDITOR_EMAIL" => $arMessage_prev["EDITOR_EMAIL"],
			"EDIT_REASON" => $arMessage_prev["EDIT_REASON"]);
		$bUpdateHTML = false;
		foreach ($arr as $key => $val):
			if (is_set($arFields, $key) && $val != $arFields[$key]):
				$bUpdateHTML = true;
				break;
			endif;
		endforeach;
		if ($bUpdateHTML):
			$arFields["HTML"] = '';
		endif;

		if (is_set($arFields, "POST_DATE") && (strLen(trim($arFields["POST_DATE"])) <= 0))
		{
			$strSql = ", POST_DATE=".$DB->GetNowFunction();
			unset($arFields["POST_DATE"]);
		}

		if (!is_set($arFields, "EDIT_DATE"))
		{
			$strSql .= ", EDIT_DATE=".$DB->GetNowFunction();
		}
		else
		{
			if (strLen(trim($arFields["EDIT_DATE"])) <= 0)
			{
				$strSql .= ", EDIT_DATE=".$DB->GetNowFunction();
				unset($arFields["EDIT_DATE"]);
			}
		}
/***************** Event onBeforeMessageUpdate *********************/
		foreach (GetModuleEvents("forum", "onBeforeMessageUpdate", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$ID, &$arFields, &$strUploadDir)) === false)
				return false;
		}
/***************** /Event ******************************************/
/***************** Attach ******************************************/
		$arFiles = array();
		if (is_array($arFields["ATTACH_IMG"]))
			$arFields["FILES"] = array($arFields["ATTACH_IMG"]);
		unset($arFields["ATTACH_IMG"]);
		if (is_array($arFields["FILES"]) && !empty($arFields["FILES"]))
		{
			$res = array("FORUM_ID" => $arMessage_prev["FORUM_ID"],
				"TOPIC_ID" => $arMessage_prev["TOPIC_ID"],
				"MESSAGE_ID" => $ID,
				"USER_ID" => $arFields["EDITOR_ID"], "upload_dir" => $strUploadDir);
			$arFiles = CForumFiles::Save($arFields["FILES"], $res, false);
			$db_res = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $ID));
			if ($db_res && $res = $db_res->Fetch())
			{
				do
				{
					$arFiles[$res["FILE_ID"]] = $res;
				} while ($db_res && $res = $db_res->Fetch());
			}
			if (!empty($arFiles))
			{
				$arFiles = array_keys($arFiles);
				sort($arFiles);
				$arFields["ATTACH_IMG"] = $arFiles[0];
			}
			else
			{
				$arFields["ATTACH_IMG"] = 0;
			}
			unset($arFields["FILES"]);
		}
/***************** Attach/******************************************/
		if (empty($arFields) && empty($strSql))
			return false;
		$strUpdate = $DB->PrepareUpdate("b_forum_message", $arFields, $strUploadDir);
		$strSql = "UPDATE b_forum_message SET ".$strUpdate.$strSql." WHERE ID = ".$ID;

		$DB->QueryBind($strSql,
			array("POST_MESSAGE" => $arFields["POST_MESSAGE"],
				"POST_MESSAGE_HTML" => $arFields["POST_MESSAGE_HTML"],
				"POST_MESSAGE_FILTER" => $arFields["POST_MESSAGE_FILTER"],
				"EDIT_REASON" => $arFields["EDIT_REASON"],
				"HTML" => $arFields["HTML"]));
/***************** Attach ******************************************/
		if (!empty($arFiles))
		{
			$res = array(
				"FORUM_ID" => (is_set($arFields, "FORUM_ID") ? $arFields["FORUM_ID"] : $arMessage_prev["FORUM_ID"]),
				"TOPIC_ID" => (is_set($arFields, "TOPIC_ID") ? $arFields["TOPIC_ID"] : $arMessage_prev["TOPIC_ID"]),
				"MESSAGE_ID" => $ID);
			CForumFiles::UpdateByID($arFiles, $res);
		}
/***************** Attach/******************************************/
/***************** Quota *******************************************/
		$_SESSION["SESS_RECOUNT_DB"] = "Y";

		$GLOBALS["USER_FIELD_MANAGER"]->Update("FORUM_MESSAGE", $ID, $arFields);
/***************** Event onAfterMessageUpdate **********************/
		foreach (GetModuleEvents("forum", "onAfterMessageUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$ID, &$arFields, $arMessage_prev));
/***************** /Event ******************************************/
		unset($GLOBALS["FORUM_CACHE"]["MESSAGE"][$ID]);
		unset($GLOBALS["FORUM_CACHE"]["MESSAGE_FILTER"][$ID]);

		if (!$skip_counts || IsModuleInstalled("search"))
		{
			$arMessage = CForumMessage::GetByIDEx($ID, array("GET_TOPIC_INFO" => "Y", "GET_FORUM_INFO" => "Y", "FILTER" => "Y"));
			if (!$skip_counts)
			{
				// author
				if ($arMessage["AUTHOR_ID"] != $arMessage_prev["AUTHOR_ID"]):
					CForumUser::SetStat($arMessage_prev["AUTHOR_ID"], array("MESSAGE" => $arMessage_prev, "ACTION" => "DECREMENT"));
					CForumUser::SetStat($arMessage["AUTHOR_ID"], array("MESSAGE" => $arMessage, "ACTION" => "INCREMENT"));
				endif;

				// Topic
				if ($arMessage["TOPIC_ID"] != $arMessage_prev["TOPIC_ID"]):
					CForumTopic::SetStat($arMessage_prev["TOPIC_ID"]);
					CForumTopic::SetStat($arMessage["TOPIC_ID"]);
				endif;

				// Forum
				if ($arMessage["FORUM_ID"] != $arMessage_prev["FORUM_ID"]):
					CForumNew::SetStat($arMessage_prev["FORUM_ID"], array("MESSAGE" => $arMessage_prev, "ACTION" => "DECREMENT"));
					CForumNew::SetStat($arMessage["FORUM_ID"], array("MESSAGE" => $arMessage, "ACTION" => "INCREMENT"));
				endif;

				if ($arMessage["APPROVED"] != $arMessage_prev["APPROVED"]):
					if ($arMessage["AUTHOR_ID"] == $arMessage_prev["AUTHOR_ID"]):
						CForumUser::SetStat($arMessage["AUTHOR_ID"], array("MESSAGE" => $arMessage, "ACTION" => "UPDATE"));
					endif;
					if ($arMessage["TOPIC_ID"] == $arMessage_prev["TOPIC_ID"]):
						CForumTopic::SetStat($arMessage["TOPIC_ID"]);
					endif;
					if ($arMessage["FORUM_ID"] == $arMessage_prev["FORUM_ID"]):
						CForumNew::SetStat($arMessage["FORUM_ID"], array("MESSAGE" => $arMessage, "ACTION" => "UPDATE"));
					endif;
					$bUpdatedStatistic = true;
				endif;
			}
			$arForum = CForumNew::GetByID($arMessage["FORUM_ID"]);
			if (CModule::IncludeModule("search") && $arForum["INDEXATION"] == "Y")
			{
				// if message was removed from indexing forum to no-indexing forum we must delete index
				if ($arMessage_prev["FORUM_INFO"]["INDEXATION"] == "Y" &&
					$arMessage["FORUM_INFO"]["INDEXATION"] != "Y")
				{
					CSearch::DeleteIndex("forum", $ID);
				}
				elseif ($arMessage["FORUM_INFO"]["INDEXATION"] == "Y" &&
					$arMessage_prev["APPROVED"] != "N" && $arMessage["APPROVED"] == "N")
				{
					CSearch::DeleteIndex("forum", $ID);
				}
				elseif ($arMessage["APPROVED"] == "Y")
				{
					CForumMessage::Reindex($ID, $arMessage);
				}
			}
		}
		return $ID;
	}

	public static function Reindex($ID, &$arMessage)
	{
		if (!($ID > 0) || !CModule::IncludeModule("search"))
			return array("FORUM_ID", "TOPIC_ID", "TITLE_SEO", "MESSAGE_ID", "SOCNET_GROUP_ID", "OWNER_ID", "PARAM1", "PARAM2");
		if (!is_array($arMessage) || !array_key_exists("FORUM_INFO", $arMessage) || !array_key_exists("TOPIC_INFO", $arMessage))
			$arMessage = CForumMessage::GetByIDEx($ID, array("GET_TOPIC_INFO" => "Y", "GET_FORUM_INFO" => "Y", "FILTER" => "Y"));

		$arMessage["POST_MESSAGE"] = (COption::GetOptionString("forum", "FILTER", "Y") == "Y" ?
			$arMessage["POST_MESSAGE_FILTER"] : $arMessage["POST_MESSAGE"]);

		$arParams = array(
			"PERMISSION" => array(),
			"SITE" => CForumNew::GetSites($arMessage["FORUM_ID"]),
			"DEFAULT_URL" => "/");

		$arGroups = CForumNew::GetAccessPermissions($arMessage["FORUM_ID"]);
		for ($i = 0; $i < count($arGroups); $i++)
		{
			if ($arGroups[$i][1] >= "E")
			{
				$arParams["PERMISSION"][] = $arGroups[$i][0];
				if ($arGroups[$i][0] == 2)
					break;
			}
		}

		$arSearchInd = array(
			"LID" => array(),
			"LAST_MODIFIED" => $arMessage["POST_DATE"],
			"PARAM1" => $arMessage["FORUM_ID"],
			"PARAM2" => $arMessage["TOPIC_ID"],
			"PERMISSIONS" => $arParams["PERMISSION"],
			"TITLE" => $arMessage["TOPIC_INFO"]["TITLE"].($arMessage["NEW_TOPIC"] == "Y" && !empty($arMessage["TOPIC_INFO"]["DESCRIPTION"]) ?
					", ".$arMessage["TOPIC_INFO"]["DESCRIPTION"] : ""),
			"TAGS" => (($arMessage["NEW_TOPIC"] == "Y") ? $arMessage["TOPIC_INFO"]["TAGS"] : ""),
			"BODY" => GetMessage("AVTOR_PREF")." ".$arMessage["AUTHOR_NAME"].". ".(forumTextParser::clearAllTags($arMessage["POST_MESSAGE"])),
			"ENTITY_TYPE_ID"  => $arMessage["NEW_TOPIC"] == "Y"? "FORUM_TOPIC": "FORUM_POST",
			"ENTITY_ID"  => $arMessage["NEW_TOPIC"] == "Y"? $arMessage["TOPIC_ID"]: $arMessage["ID"],
			"USER_ID" => $arMessage["AUTHOR_ID"],
			"URL" => "",
			"INDEX_TITLE" => $arMessage["NEW_TOPIC"] == "Y",
		);

		// get mentions
		$arMentionedUserID = CForumMessage::GetMentionedUserID($arMessage["POST_MESSAGE"]);
		if (!empty($arMentionedUserID))
		{
			$arSearchInd["PARAMS"] = array(
				"mentioned_user_id" => $arMentionedUserID
			);
		}

		$urlPatterns = array(
			"FORUM_ID" => $arMessage["FORUM_ID"],
			"TOPIC_ID" => $arMessage["TOPIC_ID"],
			"TITLE_SEO" => $arMessage["TOPIC_INFO"]["TITLE_SEO"],
			"MESSAGE_ID" => $arMessage["ID"],
			"SOCNET_GROUP_ID" => $arMessage["TOPIC_INFO"]["SOCNET_GROUP_ID"],
			"OWNER_ID" => $arMessage["TOPIC_INFO"]["OWNER_ID"],
			"PARAM1" => $arMessage["PARAM1"],
			"PARAM2" => $arMessage["PARAM2"]);
		foreach ($arParams["SITE"] as $key => $val)
		{
			$arSearchInd["LID"][$key] = CForumNew::PreparePath2Message($val, $urlPatterns);
			if (empty($arSearchInd["URL"]) && !empty($arSearchInd["LID"][$key]))
				$arSearchInd["URL"] = $arSearchInd["LID"][$key];
		}

		if (empty($arSearchInd["URL"]))
		{
			foreach ($arParams["SITE"] as $key => $val)
			{
				$db_lang = CLang::GetByID($key);
				if ($db_lang && $ar_lang = $db_lang->Fetch())
				{
					$arParams["DEFAULT_URL"] = $ar_lang["DIR"];
					break;
				}
			}
			$arParams["DEFAULT_URL"] .= COption::GetOptionString("forum", "REL_FPATH", "").
				"forum/read.php?FID=#FID#&TID=#TID#&MID=#MID##message#MID#";
			$arSearchInd["URL"] = CForumNew::PreparePath2Message($arParams["DEFAULT_URL"], $urlPatterns);
		}
		CSearch::DeleteIndex("forum", $ID);
		CSearch::Index("forum", $ID, $arSearchInd, true);
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = intVal($ID);
		$arMessage = array();
		if ($ID > 0)
			$arMessage = CForumMessage::GetByID($ID, array("FILTER" => "N"));
		if (empty($arMessage))
			return false;
/***************** Event onBeforeMessageAdd ************************/
		foreach (GetModuleEvents("forum", "onBeforeMessageDelete", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$ID, $arMessage)) === false)
				return false;
		}
/***************** /Event ******************************************/
		$AUTHOR_ID = intVal($arMessage["AUTHOR_ID"]);
		$TOPIC_ID = intVal($arMessage["TOPIC_ID"]);
		$FORUM_ID = intVal($arMessage["FORUM_ID"]);

		$DB->StartTransaction();
		// delete votes
		if ($arMessage["PARAM1"] == "VT" && intVal($arMessage["PARAM2"]) > 0 && IsModuleInstalled("vote")):
			CModule::IncludeModule("vote");
			CVote::Delete($arMessage["PARAM2"]);
		endif;
		// delete files
		CForumFiles::Delete(array("MESSAGE_ID" => $ID), array("DELETE_MESSAGE_FILE" => "Y"));
		// delete message
		$DB->Query("DELETE FROM b_forum_message WHERE ID=".$ID);
		// after delete
		$db_res = CForumMessage::GetList(array("ID" => "ASC"), array("TOPIC_ID" => $TOPIC_ID), false, 1);
		$res = false;
		if (!($db_res && $res = $db_res->Fetch())):
			CForumTopic::Delete($TOPIC_ID);
		else:
			// if deleted message was first
			if ($arMessage["NEW_TOPIC"] == "Y")
				$DB->Update('b_forum_message', array('NEW_TOPIC' => '"Y"'), "WHERE ID=".$res["ID"]);
			CForumTopic::SetStat($TOPIC_ID);
		endif;
		$DB->Commit();

		$GLOBALS["USER_FIELD_MANAGER"]->Delete("FORUM_MESSAGE", $ID);

		if ($AUTHOR_ID > 0):
			CForumUser::SetStat($AUTHOR_ID);
		endif;
		CForumNew::SetStat($FORUM_ID);
/***************** Event onBeforeMessageAdd ************************/
		foreach (GetModuleEvents("forum", "onAfterMessageDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arMessage));
/***************** /Event ******************************************/
		if (CModule::IncludeModule("search"))
		{
			CSearch::DeleteIndex("forum", $ID);
			if (is_array($res) && !empty($res))
				CForumMessage::Reindex($res["ID"], $res);
		}
		return true;
	}

	//---------------> Message list
	public static function GetByID($ID, $arAddParams = array())
	{
		global $DB;
		$ID = intVal($ID);
		if ($ID <= 0):
			return false;
		endif;

		$arAddParams = (is_array($arAddParams) ? $arAddParams : array($arAddParams));
		$arAddParams["FILTER"] = ($arAddParams["FILTER"] == "Y" && COption::GetOptionString("forum", "FILTER", "Y") == "Y" ? "Y" : "N");

		if (!array_key_exists($ID, $GLOBALS["FORUM_CACHE"]["MESSAGE"]))
		{
			$strSql = "SELECT FM.*, ".$DB->DateToCharFunction("FM.POST_DATE", "FULL")." as POST_DATE
				FROM b_forum_message FM
				WHERE FM.ID = ".$ID;
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($db_res && $res = $db_res->Fetch())
			{
				$GLOBALS["FORUM_CACHE"]["MESSAGE"][$ID] = $res;
				if ($arAddParams["FILTER"] != "Y"):
					unset($res["HTML"]);
				endif;
				$db_res_filter = new CDBResult;
				$db_res_filter->InitFromArray(array($res));
				$db_res_filter = new _CMessageDBResult($db_res_filter, $arAddParams);
				if ($res_filter = $db_res_filter->Fetch())
					$GLOBALS["FORUM_CACHE"]["MESSAGE_FILTER"][$ID] = $res_filter;
			}
		}

		$res = ($arAddParams["FILTER"] == "Y" ? $GLOBALS["FORUM_CACHE"]["MESSAGE_FILTER"][$ID] :
			$GLOBALS["FORUM_CACHE"]["MESSAGE"][$ID]);

		if ($arAddParams["getFiles"] == "Y" && !empty($res))
			$res["FILES"] = CForumFiles::getByMessageID($ID);

		return $res;
	}

	
	/**
	* <p>Возвращает массив параметров сообщения, а так же сопутствующие параметры, по его коду <i>ID</i>.</p>
	*
	*
	* @param int $ID  Код сообщения.
	*
	* @param array $arAddParams = Array() Массив добавления параметров.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Распечатаем на экран все возвращаемые параметры сообщения
	* $arMessage = CForumMessage::GetByIDEx($MID);
	* if ($arMessage)
	* {
	*   echo "&lt;pre&gt;";
	*   print_r($arMessage);
	*   echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/forum/fields.php#cforummessage">Поля сообщения</a>
	* </li></ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforummessage/getbyidex.php
	* @author Bitrix
	*/
	public static function GetByIDEx($ID, $arAddParams = array())
	{
		global $DB;
		$ID = intVal($ID);
		$res = false;
		if ($ID <= 0)
			return false;

		$arAddParams = (is_array($arAddParams) ? $arAddParams : array($arAddParams));
		$arAddParams["GET_TOPIC_INFO"] = ($arAddParams["GET_TOPIC_INFO"] == "Y" ? "Y" : "N");
		$arAddParams["FILTER_TOPIC_INFO"] = ($arAddParams["FILTER_TOPIC_INFO"] == "N" ? "N" : "Y");
		$arAddParams["GET_FORUM_INFO"] = ($arAddParams["GET_FORUM_INFO"] == "Y" ? "Y" : "N");
		$arAddParams["FILTER_FORUM_INFO"] = ($arAddParams["FILTER_FORUM_INFO"] == "N" ? "N" : "Y");
		$arAddParams["FILTER_MESSAGE_INFO"] = ($arAddParams["FILTER_MESSAGE_INFO"] == "N" ? "N" : "Y");
		if (COption::GetOptionString("forum", "FILTER", "Y") == "Y"):
			$arAddParams["FILTER"] = (is_set($arAddParams, "FILTER") ? $arAddParams["FILTER"] : "P");
			$arAddParams["FILTER"] = ($arAddParams["FILTER"] == "Y" || $arAddParams["FILTER"] == "P" ? $arAddParams["FILTER"] : "N");
		else:
			$arAddParams["FILTER"] = "N";
		endif;
		if ($arAddParams["FILTER"] == "N"):
			$arAddParams["FILTER_TOPIC_INFO"] = "N";
			$arAddParams["FILTER_FORUM_INFO"] = "N";
			$arAddParams["FILTER_MESSAGE_INFO"] = "N";
		elseif ($arAddParams["FILTER"] == "P"):
			$arAddParams["FILTER_MESSAGE_INFO"] = "N";
		endif;

		$arSqlSelect = array();
		$arSqlFrom = array();
		if ($arAddParams["GET_TOPIC_INFO"] == "Y")
		{
			$arSqlSelect[] = CForumTopic::GetSelectFields(array("sPrefix" => "FT_", "sReturnResult" => "string"));
			if ($arAddParams["FILTER_TOPIC_INFO"] != "N")
				$arSqlSelect[] = "FT.HTML as FT_HTML";
			$arSqlSelect[] = "FT.XML_ID as FT_XML_ID";
			$arSqlFrom[] =  "INNER JOIN b_forum_topic FT ON (FM.TOPIC_ID = FT.ID)";
		}
		if ($arAddParams["GET_FORUM_INFO"] == "Y")
		{
			$arSqlSelect[] = CForumNew::GetSelectFields(array("sPrefix" => "F_", "sReturnResult" => "string"));
			if ($arAddParams["FILTER_FORUM_INFO"] != "N")
				$arSqlSelect[] = "F.HTML as F_HTML";
			$arSqlFrom[] =  "INNER JOIN b_forum F ON (FM.FORUM_ID = F.ID)";
		}

		$strSql =
			"SELECT FM.*, ".$DB->DateToCharFunction("FM.POST_DATE", "FULL")." as POST_DATE,
				FU.SHOW_NAME, FU.DESCRIPTION, FU.NUM_POSTS, FU.POINTS as NUM_POINTS, FU.SIGNATURE, FU.AVATAR, FU.RANK_ID,
				".$DB->DateToCharFunction("FU.DATE_REG", "SHORT")." as DATE_REG,
				U.EMAIL, U.PERSONAL_ICQ, U.LOGIN, U.NAME, U.SECOND_NAME, U.LAST_NAME, U.PERSONAL_PHOTO".
				(!empty($arSqlSelect) ? ", ".implode(", ", $arSqlSelect) : "")."
			FROM b_forum_message FM
				LEFT JOIN b_forum_user FU ON (FM.AUTHOR_ID = FU.USER_ID)
				LEFT JOIN b_user U ON (FM.AUTHOR_ID = U.ID)
				".implode(" ", $arSqlFrom)."
			WHERE FM.ID = ".$ID."";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($db_res && $res = $db_res->Fetch()):
			if ($arAddParams["FILTER_MESSAGE_INFO"] == "N"):
				unset($res["HTML"]);
			endif;

			if ($arAddParams["GET_TOPIC_INFO"] == "Y" && COption::GetOptionString("forum", "FILTER", "Y") == "Y"):
				$arTopic = array();
				foreach ($res as $key => $val):
					if (substr($key, 0, 3) == "FT_")
						$arTopic[substr($key, 3)] = $val;
				endforeach;
				if (!empty($arTopic)):
					$GLOBALS["FORUM_CACHE"]["TOPIC"][intVal($res["TOPIC_INFO"]["ID"])] = $arTopic;
					$db_res_filter = new CDBResult;
					$db_res_filter->InitFromArray(array($arTopic));
					$db_res_filter = new _CTopicDBResult($db_res_filter);
					if ($res_filter = $db_res_filter->Fetch())
						$GLOBALS["FORUM_CACHE"]["TOPIC_FILTER"][intVal($res["TOPIC_INFO"]["ID"])] = $res_filter;
				endif;
			endif;
			$db_res = new CDBResult;
			$db_res->InitFromArray(array($res));
			$db_res = new _CMessageDBResult($db_res, $arAddParams);
			$res = $db_res->Fetch();

			if ($arAddParams["GET_TOPIC_INFO"] == "Y" || $arAddParams["GET_FORUM_INFO"] == "Y"):
				$res["TOPIC_INFO"] = array();
				$res["FORUM_INFO"] = array();
				$res["MESSAGE_INFO"] = array();
				foreach ($res as $key => $val):
					if (substr($key, 0, 3) == "FT_")
						$res["TOPIC_INFO"][substr($key, 3)] = $val;
					elseif (substr($key, 0, 2) == "F_")
						$res["FORUM_INFO"][substr($key, 2)] = $val;
					else
						$res["MESSAGE_INFO"][$key] = $val;
				endforeach;
				if (COption::GetOptionString("forum", "FILTER", "Y") != "Y" && !empty($res["TOPIC_INFO"])):
					$GLOBALS["FORUM_CACHE"]["TOPIC"][intVal($res["TOPIC_INFO"]["ID"])] = $res["TOPIC_INFO"];
				endif;
				if (!empty($res["FORUM_INFO"])):
					$GLOBALS["FORUM_CACHE"]["FORUM"][intVal($res["FORUM_INFO"]["ID"])] = $res["FORUM_INFO"];
				endif;
			endif;
			if ($arAddParams["getFiles"] == "Y" && !empty($res))
				$res["FILES"] = CForumFiles::getByMessageID($ID);
			return $res;
		endif;
		return false;
	}

	//---------------> Message utils
	public static function GetMessagePage($ID, $mess_per_page, $arUserGroups, $TID = false, $arAddParams = array())
	{
		$ID = intVal($ID);
		$mess_per_page = intVal($mess_per_page);

		$arAddParams = (is_array($arAddParams) ? $arAddParams : array($arAddParams));
		$arAddParams["ORDER_DIRECTION"] = ($arAddParams["ORDER_DIRECTION"] == "DESC" ? "DESC" : "ASC");
		$arAddParams["FILTER"] = (is_array($arAddParams["FILTER"]) ? $arAddParams["FILTER"] : array());

		if ($mess_per_page <= 0)
			return 0;

		if (!empty($arAddParams["PERMISSION_EXTERNAL"])):
			$arMessage = array("TOPIC_ID" => $TID);
			$permission = $arAddParams["PERMISSION_EXTERNAL"];
		else:
			$arMessage = CForumMessage::GetByID($ID, array("FILTER" => "N"));
			$permission = CForumNew::GetUserPermission($arMessage["FORUM_ID"], $arUserGroups);
		endif;

		$arFilter = $arAddParams["FILTER"] + array("TOPIC_ID" => $arMessage["TOPIC_ID"]);
		if ($permission < "Q"):
			$arFilter["APPROVED"] = "Y";
		endif;

		if ($arAddParams["ORDER_DIRECTION"] == "DESC"):
			$arFilter[">ID"] = $ID;
		else:
			$arFilter["<ID"] = $ID;
		endif;

		$iCnt = CForumMessage::GetList(array("ID" => $arAddParams["ORDER_DIRECTION"]), $arFilter, True);
		$iCnt = intVal($iCnt);
		return intVal($iCnt/$mess_per_page) + 1;
	}

	public static function SendMailMessage($MID, $arFields = array(), $strLang = false, $mailTemplate = false)
	{
		global $USER;
		$MID = intVal($MID);
		$arMessage = array(); $arTopic = array(); $arForum = array(); $arFiles = array();
		$mailTemplate = ($mailTemplate === false ? "NEW_FORUM_MESSAGE" : $mailTemplate);
		$event = new CEvent;
		if ($MID > 0)
		{
			CTimeZone::Disable();
			$arMessage = CForumMessage::GetByIDEx($MID, array("GET_TOPIC_INFO" => "Y", "GET_FORUM_INFO" => "Y", "FILTER" => "Y"));
			CTimeZone::Enable();

			$db_files = CForumFiles::GetList(array("MESSAGE_ID" => "ASC"), array("MESSAGE_ID" => $MID));
			if ($db_files && $res = $db_files->Fetch())
			{
				do
				{
					$arFiles[$res["ID"]] = CFile::GetFileArray($res["FILE_ID"]);
				} while ($res = $db_files->Fetch());
			}
		}
		if (empty($arMessage))
			return false;

		$arTopic = $arMessage["TOPIC_INFO"];
		$arForum = $arMessage["FORUM_INFO"];
		$TID = intVal($arMessage["TOPIC_ID"]);
		$FID = intVal($arMessage["FORUM_ID"]);

		if (!is_set($arFields, "FORUM_ID")) $arFields["FORUM_ID"] = $arMessage["FORUM_ID"];
		if (!is_set($arFields, "FORUM_NAME")) $arFields["FORUM_NAME"] = $arForum["NAME"];
		if (!is_set($arFields, "TOPIC_ID")) $arFields["TOPIC_ID"] = $arMessage["TOPIC_ID"];
		if (!is_set($arFields, "MESSAGE_ID")) $arFields["MESSAGE_ID"] = $arMessage["ID"];
		if (!is_set($arFields, "TOPIC_TITLE")) $arFields["TOPIC_TITLE"] = $arTopic["TITLE"];

		if (!is_set($arFields, "MESSAGE_DATE")) $arFields["MESSAGE_DATE"] = $arMessage["POST_DATE"];
		if (!is_set($arFields, "AUTHOR")) $arFields["AUTHOR"] = $arMessage["AUTHOR_NAME"];
		if (!is_set($arFields, "TAPPROVED")) $arFields["TAPPROVED"] = $arTopic["APPROVED"];
		if (!is_set($arFields, "MAPPROVED")) $arFields["MAPPROVED"] = $arMessage["APPROVED"];
		if (!is_set($arFields, "FROM_EMAIL")) $arFields["FROM_EMAIL"] = COption::GetOptionString("forum", "FORUM_FROM_EMAIL", "nomail@nomail.nomail");

		//If the message is from socialnetwork, check if mail processor exists for this social network
		if($arTopic["SOCNET_GROUP_ID"]>0)
		{
			if(CModule::IncludeModule("mail") && CModule::IncludeModule("socialnetwork"))
			{
				$arMailParams = CForumEMail::GetForumFilters($FID, $arTopic["SOCNET_GROUP_ID"]);
				//If the processor exists:
				if($arMailParams)
				{
					global $DB;
					if($arMessage["XML_ID"]=='')
					{
						//check if MSG_ID field exists, generate it if not
						$arMessage["XML_ID"] = "M".$MID.".".md5(uniqid())."@".($_SERVER["SERVER_NAME"]!=''?$_SERVER["SERVER_NAME"]:$_SERVER["SERVER_ADDR"]);
						$DB->Query("UPDATE b_forum_message SET XML_ID='".$DB->ForSQL($arMessage["XML_ID"])."' WHERE ID=".$MID);
					}

					//get MSG_ID topics, it would be IN_REPLY_TO
					if($arTopic["XML_ID"]=='')
					{
						$arTopic["XML_ID"] = "T".$TID.".".md5(uniqid())."@".($_SERVER["SERVER_NAME"]!=''?$_SERVER["SERVER_NAME"]:$_SERVER["SERVER_ADDR"]);
						$DB->Query("UPDATE b_forum_topic SET XML_ID='".$DB->ForSQL($arTopic["XML_ID"])."' WHERE ID=".$TID);
					}

					//fill FROM_EMAIL from AUTHOR_NAME + FROM_EMAIL or AUTHOR_EMAIL or from 'b_user' by AUTHOR_ID depending on the settings of mail processor
					if($arMailParams['USE_EMAIL'] == 'Y' && $arMessage["AUTHOR_EMAIL"]!='')
						$arFields["FROM_EMAIL"] = '"'.$arMessage['AUTHOR_NAME'].'" <'.$arMessage['AUTHOR_EMAIL'].'>';
					elseif($arMailParams['USE_EMAIL'] == 'Y' && $arMessage["EMAIL"]!='')
						$arFields["FROM_EMAIL"] = '"'.$arMessage['AUTHOR_NAME'].'" <'.$arMessage['EMAIL'].'>';
					else
						$arFields["FROM_EMAIL"] = '"'.$arMessage['AUTHOR_NAME'].'" <'.$arMailParams['EMAIL'].'>';

					if($arMessage["NEW_TOPIC"]=="Y")
					{
						$arFields["=Message-Id"] = $arFields["MSG_ID"] = "<".$arTopic["XML_ID"].">";
					}
					else
					{
						$arFields["TOPIC_TITLE"] = "Re".($arMessage["TOPIC_INFO"]["POSTS"]>1?"[".$arMessage["TOPIC_INFO"]["POSTS"]."]":"").": ".$arFields["TOPIC_TITLE"];
						$arFields["=Message-Id"] = $arFields["MSG_ID"] = "<".$arMessage["XML_ID"].">";
						$arFields["=In-Reply-To"] = $arFields["IN_REPLY_TO"] = "<".$arTopic["XML_ID"].">";
					}
					//fill REPLY_TO from the settings of the mail processor
					$arFields["=Reply-To"] = $arFields["REPLY_TO"] = $arMailParams["EMAIL"];
					$arFields["FORUM_EMAIL"] = $arMailParams["EMAIL"];

					$arSocNetGroup = CSocNetGroup::GetById($arTopic["SOCNET_GROUP_ID"]);
					$arFields["FORUM_NAME"] = $arSocNetGroup["NAME"];

					if($arMailParams["SUBJECT_SUF"] != '')
						$arFields["TOPIC_TITLE"] .= ' '.$arMailParams["SUBJECT_SUF"];
					if($arMailParams["USE_SUBJECT"] == "Y")
						$arFields["=Subject"] = $arFields["TOPIC_TITLE"];

					$arFields["PATH2FORUM"] = CComponentEngine::MakePathFromTemplate($arMailParams["URL_TEMPLATES_MESSAGE"], array("FID" => $arMessage["FORUM_ID"], "TID" => $arMessage["TOPIC_ID"], "TITLE_SEO" => $arMessage["TOPIC_INFO"]["TITLE_SEO"], "MID" => $arMessage["ID"]));
				}
				else
					return false;
			}
			else
				return false;
		}
		else
		{
			$arForumSites = CForumNew::GetSites($FID);
			foreach ($arForumSites as $site_id => $path):
				$arForumSites[$site_id] = trim(CForumNew::PreparePath2Message($arForumSites[$site_id],
						array("FORUM_ID" => $arMessage["FORUM_ID"], "TOPIC_ID" => $arMessage["TOPIC_ID"], "TITLE_SEO" => $arMessage["TOPIC_INFO"]["TITLE_SEO"], "MESSAGE_ID" => $arMessage["ID"],
							"SOCNET_GROUP_ID" => $arTopic["SOCNET_GROUP_ID"], "OWNER_ID" => $arTopic["OWNER_ID"],
							"PARAM1" => $arMessage["PARAM1"], "PARAM2" => $arMessage["PARAM2"])));
				if (empty($arForumSites[$site_id])):
					$db_lang = CSite::GetByID($site_id);
					$arForumSites[$site_id] = "/";
					if ($ar_lang = $db_lang->Fetch())
						$arForumSites[$site_id] = $ar_lang["DIR"];
					$arForumSites[$site_id] = COption::GetOptionString("forum", "REL_FPATH", "").
							"forum/read.php?FID=#FID#&TID=#TID#&MID=#MID##message#MID#";
				endif;
			endforeach;
			foreach(GetModuleEvents("forum", "onBeforeMailMessageSend", true) as $arEvent)
			{
				if (ExecuteModuleEventEx($arEvent, array(&$mailTemplate, &$arForumSites, &$arFields, $arForum, $arTopic, $arMessage)) === false)
					return false;
			}
		}

		/*
		??
		ALTER TABLE dbo.B_FORUM_MESSAGE ADD
			MSG_ID varchar(255) NULL,
			MAIL_MESSAGE_ID int NULL

		*/

		$arFilter = array(
			"FORUM_ID" => $FID,
			"TOPIC_ID_OR_NULL" => $TID,
			"ACTIVE" => "Y",
			">=PERMISSION" => (($arTopic["APPROVED"] != "Y" || $arMessage["APPROVED"] != "Y") ? "Q" : "E")
			);
		if ($arMessage["NEW_TOPIC"] != "Y")
			$arFilter["NEW_TOPIC_ONLY"] = "N";
		if ($mailTemplate == "NEW_FORUM_MESSAGE")
			$arFilter["LAST_SEND_OR_NULL"] = $MID;

		if($arTopic["SOCNET_GROUP_ID"]>0)
		{
			$mailTemplate = "FORUM_NEW_MESSAGE_MAIL";
			$arFilter["SOCNET_GROUP_ID"] = $arTopic["SOCNET_GROUP_ID"];
		}
		else
			$arFilter["SOCNET_GROUP_ID"] = false;

		$db_res = CForumSubscribe::GetListEx(array("USER_ID" => "ASC"), $arFilter);
		$arID = array(); $arSiteFields = array();
		$currentUserID = false;
		while ($res = $db_res->Fetch())
		{
			// SUBSC_GET_MY_MESSAGE - Send my messages to myself.
			if ($res["SUBSC_GET_MY_MESSAGE"] == "N" && $res["USER_ID"] == $USER->GetId())
				continue;

			// SUBSC_GROUP_MESSAGE  - Group messages.
			if ($currentUserID == $res["USER_ID"])
				continue;

			// Check email
			if (empty($res["EMAIL"]))
				continue;

			if($mailTemplate == "FORUM_NEW_MESSAGE_MAIL" && $res["USER_ID"] == $arMessage["AUTHOR_ID"])
				continue;

			$currentUserID = $res["USER_ID"];
			$arFields_tmp = $arFields;

			if (!is_set($arFields_tmp, "PATH2FORUM"))
			{
				$arFields_tmp["PATH2FORUM"] = $arForumSites[$res["SITE_ID"]];
			}

			if (!is_set($arFields_tmp, "MESSAGE_TEXT"))
			{
				if (!isset(${"parser_".$res["SITE_ID"]}))
					${"parser_".$res["SITE_ID"]} = new forumTextParser($res["SITE_ID"]);
				if (empty($arSiteFields[$res["SITE_ID"]]))
				{
					$arSiteFields[$res["SITE_ID"]] = $event->GetSiteFieldsArray($res["SITE_ID"]);
					$db_site = CSite::GetByID($res["SITE_ID"]);
					if ($db_site && $arSite = $db_site->Fetch())
					{
						$arSiteFields[$res["SITE_ID"]] = array_merge($arSiteFields[$res["SITE_ID"]], $arSite,
							array("LANG_MESS" => IncludeModuleLangFile(__FILE__, $arSiteFields[$res["SITE_ID"]]["LANGUAGE_ID"], true)));
						$arSiteFields[$res["SITE_ID"]]["ATTACHED_FILES"] = $arSiteFields[$res["SITE_ID"]]["LANG_MESS"]["F_ATTACHED_FILES"];
					}
				}
				if (!empty($arSiteFields[$res["SITE_ID"]]["SERVER_NAME"]))
					${"parser_".$res["SITE_ID"]}->serverName = $arSiteFields[$res["SITE_ID"]]["SERVER_NAME"];
				${"parser_".$res["SITE_ID"]}->arFiles = $arFiles;

				$POST_MESSAGE_HTML = $arMessage["POST_MESSAGE"];
				if (COption::GetOptionString("forum", "FILTER", "Y") == "Y")
					$POST_MESSAGE_HTML = (empty($arMessage["POST_MESSAGE_FILTER"]) ? CFilterUnquotableWords::Filter($POST_MESSAGE_HTML) : $arMessage["POST_MESSAGE_FILTER"]);
				$arFields_tmp["MESSAGE_TEXT"] = ${"parser_".$res["SITE_ID"]}->convert4mail($POST_MESSAGE_HTML);
				$arFields_tmp["PARSED_FILES"] = ${"parser_".$res["SITE_ID"]}->arFilesIDParsed;
				$tmp = array_diff(array_keys($arFiles), ${"parser_".$res["SITE_ID"]}->arFilesIDParsed);
				if (!empty($tmp))
				{
					$str = "[FILE ID=".implode("]\n[FILE ID=", $tmp)."]";
					${"parser_".$res["SITE_ID"]}->ParserFile($str, ${"parser_".$res["SITE_ID"]}, "mail");
					$arFields_tmp["MESSAGE_TEXT"] .= "\n\n".$arSiteFields[$res["SITE_ID"]]["ATTACHED_FILES"]."\n".$str;
				}
			}

			$arFields_tmp["RECIPIENT"] = $res["EMAIL"];
			$event->Send($mailTemplate, $res["SITE_ID"], $arFields_tmp, "N");
			$arID[] = $res["ID"];
			if (count($arID) > 255)
			{
				CForumSubscribe::UpdateLastSend($MID, implode(",", $arID));
				$arID = array();
			}
		}
		if (count($arID) > 0)
		{
			CForumSubscribe::UpdateLastSend($MID, implode(",", $arID));
		}
		return true;
	}

	public static function GetFirstUnreadEx($FID, $TID, $arUserGroups) // out-of-date function
	{
		$FID = intVal($FID);
		$TID = intVal($TID);
		if ($FID<=0) return false;

		$f_PERMISSION = CForumNew::GetUserPermission($FID, $arUserGroups);
		return CForumMessage::GetFirstUnread($FID, $TID, $f_PERMISSION);
	}

	public static function GetFirstUnread($FID, $TID, $PERMISSION) // out-of-date function
	{
		$FID = intVal($FID);
		$TID = intVal($TID);
		if ($FID<=0) return false;
		if (strLen($PERMISSION)<=0) return false;

		$MESSAGE_ID = 0;
		$TOPIC_ID = 0;

		$read_forum_cookie = COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_FORUM_0";
		if (strLen($_SESSION["first_read_forum_".$FID])<=0 || intVal($_SESSION["first_read_forum_".$FID])<0)
		{
			if (isset($_COOKIE[$read_forum_cookie]) && strLen($_COOKIE[$read_forum_cookie])>0)
			{
				$arForumCookie = explode("/", $_COOKIE[$read_forum_cookie]);
				$i = 0;
				while ($i < count($arForumCookie))
				{
					if (intVal($arForumCookie[$i])==$FID)
					{
						$iCurFirstReadForum = intVal($arForumCookie[$i+1]);
						break;
					}
					$i += 2;
				}
			}

			$read_forum_cookie1 = COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_FORUM_".$FID;
			if (isset($_COOKIE[$read_forum_cookie1]) && intVal($_COOKIE[$read_forum_cookie1])>0)
			{
				if ($iCurFirstReadForum<intVal($_COOKIE[$read_forum_cookie1]))
				{
					$iCurFirstReadForum = intVal($_COOKIE[$read_forum_cookie1]);
				}
			}

			$_SESSION["first_read_forum_".$FID] = intVal($iCurFirstReadForum);
		}
		if (is_null($_SESSION["read_forum_".$FID]) || strLen($_SESSION["read_forum_".$FID])<=0)
		{
			$_SESSION["read_forum_".$FID] = "0";
		}

		$arFilter = array("FORUM_ID" => $FID);
		if (intVal($_SESSION["first_read_forum_" . $FID])>0)
			$arFilter[">ID"] = intVal($_SESSION["first_read_forum_" . $FID]);
		if ($_SESSION["read_forum_" . $FID]!="0")
		{
			$arFMIDsTmp = explode(",", $_SESSION["read_forum_" . $FID]);
			if (count($arFMIDsTmp)>950)
			{
				for ($i1 = 0; $i1<count($arFMIDsTmp); $i1++)
				{
					if (intVal($_SESSION["first_read_forum_" . $FID]) < intVal($arFMIDsTmp[$i1]))
					{
						$_SESSION["first_read_forum_" . $FID] = intVal($arFMIDsTmp[$i1]);
					}
				}
				$_SESSION["read_forum_" . $FID] = "0";
				$arFilter[">ID"] = intVal($_SESSION["first_read_forum_" . $FID]);
			}
			else
			{
				$arFilter["!@ID"] = $_SESSION["read_forum_" . $FID];
			}
		}
		if ($PERMISSION<="Q") $arFilter["APPROVED"] = "Y";
		if ($TID>0) $arFilter["TOPIC_ID"] = $TID;

		//$db_res = CForumMessage::GetList(array("ID"=>"ASC"), $arFilter, false, 1);
		$db_res = CForumMessage::QueryFirstUnread($arFilter);

		if ($res = $db_res->Fetch())
		{
			$MESSAGE_ID = $res["ID"];
			$TOPIC_ID = $res["TOPIC_ID"];
		}

		return array($TOPIC_ID, $MESSAGE_ID);
	}

	public static function OnSocNetLogFormatEvent($arEvent, $arParams)
	{
		if ($arEvent["EVENT_ID"] == "forum")
		{
			$arTmp = explode("&", $arEvent["PARAMS"]);
			foreach ($arTmp as $strTmp)
			{
				list($key, $value) = explode("=", $strTmp, 2);
				if($key == "type")
				{
					$type = $value;
					break;
				}
			}

			if ($type == "M")
				$arEvent["TITLE_TEMPLATE"] = "#USER_NAME# ".GetMessage("F_SONET_MESSAGE_TITLE");
			elseif ($type == "T")
				$arEvent["TITLE_TEMPLATE"] = "#USER_NAME# ".GetMessage("F_SONET_TOPIC_TITLE");
		}

		return $arEvent;
	}

	/**
	 * @param $arFilter - array("FORUM_ID" => 241, "TOPIC_ID" => 82383, "APPROVED" => "Y")
	 * @param $rights - string(1) (A|R|U|W);
	 */
	public static function setWebdavRights($arFilter, $rights)
	{
		if (IsModuleInstalled("webdav"))
		{
			$arFilter = (is_array($arFilter) ? $arFilter : array($arFilter));
			$arFilter[">UF_FORUM_MESSAGE_DOC"] = 0;
			$db_res = CForumMessage::GetList(array("ID" => "ASC"), $arFilter, false, 0, array("SELECT" => array("UF_FORUM_MESSAGE_DOC")));
			$arDocs = array();
			if ($db_res && ($res = $db_res->Fetch()))
			{
				do {
					if (!empty($res["UF_FORUM_MESSAGE_DOC"]) && is_array($res["UF_FORUM_MESSAGE_DOC"]))
						$arDocs = array_merge($arDocs, $res["UF_FORUM_MESSAGE_DOC"]);
				} while ($res = $db_res->Fetch());
			}
			if (!empty($arDocs) && CModule::IncludeModule("webdav"))
			{
				CWebDavIblock::appendRightsOnElements($arDocs, $rights);
			}
		}
	}

	public static function GetMentionedUserID($strMessage)
	{
		$arMentionedUserID = array();
								
		if (strlen($strMessage) > 0)
		{
			preg_match_all("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/is".BX_UTF_PCRE_MODIFIER, $strMessage, $arMention);
			if (!empty($arMention))
			{
				$arMentionedUserID = array_merge($arMentionedUserID, $arMention[1]);
			}
		}

		return $arMentionedUserID;
	}
}

class _CMessageDBResult extends CDBResult
{
	var $sNameTemplate = '';
	public function _CMessageDBResult($res, $params = array())
	{
		$this->sNameTemplate = (!empty($params["sNameTemplate"]) ? $params["sNameTemplate"] : '');
		$this->checkUserFields = false;
		$this->arUserFields = false;
		if (array_key_exists("SELECT", $params))
		{
			$this->arUserFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("FORUM_MESSAGE", 0, LANGUAGE_ID);
			$this->checkUserFields = (!empty($this->arUserFields));
		}
		parent::CDBResult($res);
	}
	public function Fetch()
	{
		global $DB;
		$arFields = array();
		if($res = parent::Fetch())
		{
			if (COption::GetOptionString("forum", "MESSAGE_HTML", "N") == "Y" ||
				COption::GetOptionString("forum", "FILTER", "Y") == "Y")
			{
				$res["POST_MESSAGE_HTML"] = trim($res["POST_MESSAGE_HTML"]);
				$res["POST_MESSAGE_FILTER"] = trim($res["POST_MESSAGE_FILTER"]);
				if (empty($res["POST_MESSAGE_HTML"]) && COption::GetOptionString("forum", "MESSAGE_HTML", "N") == "Y" ||
					empty($res["POST_MESSAGE_FILTER"]) && COption::GetOptionString("forum", "FILTER", "Y") == "Y")
				{
					$arForum = CForumNew::GetByID($res["FORUM_ID"]);
					if ((COption::GetOptionString("forum", "FILTER", "Y") == "Y") && empty($res["POST_MESSAGE_FILTER"]))
					{
						$arFields["POST_MESSAGE_FILTER"] = CFilterUnquotableWords::Filter($res["POST_MESSAGE"]);
						$arFields["POST_MESSAGE_FILTER"] = (empty($arFields["POST_MESSAGE_FILTER"]) ? "*" : $arFields["POST_MESSAGE_FILTER"]);
					}
					if (COption::GetOptionString("forum", "MESSAGE_HTML", "N") == "Y" && empty($res["POST_MESSAGE_HTML"]))
					{
						/* Info about one file is saved in old table field ATTACH_IMG */
						$arFiles = false;
						if (intval($res["ATTACH_IMG"]) > 0)
						{
							$arFiles = array();
							$db_files = CForumFiles::GetList(array("MESSAGE_ID" => "ASC"), array("MESSAGE_ID" => $res["ID"]));
							if ($db_files && $res_file = $db_files->Fetch())
							{
								do
								{
									$res_file["SRC"] = CFile::GetFileSRC($res);
									$arFiles[$res_file["ID"]] = $res_file;
								} while ($res_file = $db_files->Fetch());
							}
						}
						$parser = new forumTextParser(LANGUAGE_ID);
						$allow = forumTextParser::GetFeatures($arForum);
						$allow['SMILES'] = ($res["USE_SMILES"] == "Y" ? $allow['SMILES'] : "N");
						$POST_MESSAGE_HTML = (is_set($arFields, "POST_MESSAGE_FILTER") ? $arFields["POST_MESSAGE_FILTER"] : $res["POST_MESSAGE"]);
						$arFields["POST_MESSAGE_HTML"] = $parser->convert($POST_MESSAGE_HTML, $allow, "html", $arFiles);
					}
					$strUpdate = $DB->PrepareUpdate("b_forum_message", $arFields);
					$strSql = "UPDATE b_forum_message SET ".$strUpdate." WHERE ID = ".intVal($res["ID"]);
					if ($DB->QueryBind($strSql, $arFields, false, "File: ".__FILE__."<br>Line: ".__LINE__))
					{
						foreach ($arFields as $key => $val)
							$res[$key] = $val;
					}
				}
			}
			if (COption::GetOptionString("forum", "FILTER", "Y") == "Y")
			{
				if (is_set($res, "HTML") || is_set($res, "FM_HTML"))
				{
					$arr = @unserialize(is_set($res, "HTML") ? $res["HTML"] : $res["FM_HTML"]);
					if (empty($arr) || !is_array($arr))
					{
						$arr = array(
							"AUTHOR_NAME" => $res["AUTHOR_NAME"],
							"AUTHOR_EMAIL" => $res["AUTHOR_EMAIL"],
							"EDITOR_NAME" => $res["EDITOR_NAME"],
							"EDITOR_EMAIL" => $res["EDITOR_EMAIL"],
							"EDIT_REASON" => $res["EDIT_REASON"]);
						foreach ($arr as $key => $val)
						{
							if (!empty($val)):
								$val = CFilterUnquotableWords::Filter($val);
								$arr[$key] = (empty($val) ? "*" : $val);
							else:
								$arr[$key] = '';
							endif;
						}
						$arFields = array("HTML" => serialize($arr));
						$strUpdate = $DB->PrepareUpdate("b_forum_message", $arFields);
						$strSql = "UPDATE b_forum_message SET ".$strUpdate." WHERE ID = ".intVal($res["ID"]);
						$DB->QueryBind($strSql, $arFields, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					}
					foreach ($arr as $key => $val)
					{
						$res["~".$key] = $res[$key];
						$res["".$key] = $val;
					}
				}

				if (!empty($res["FT_HTML"]))
				{
					$arr = @unserialize($res["FT_HTML"]);
					if (is_array($arr) && !empty($arr["TITLE"]))
					{
						foreach ($arr as $key => $val)
						{
							$res["~FT_".$key] = $res["FT_".$key];
							$res["FT_".$key] = $val;
						}
					}
				}

				if (!empty($res["F_HTML"]))
				{
					$arr = @unserialize($res["F_HTML"]);
					if (is_array($arr))
					{
						foreach ($arr as $key => $val)
						{
							$res["~F_".$key] = $res["F_".$key];
							$res["F_".$key] = $val;

						}
					}
					if (!empty($res["FT_TITLE"]))
						$res["F_TITLE"] = $res["FT_TITLE"];
				}
			}
			if (!empty($this->sNameTemplate))
			{
				$arTmp = array();
				foreach (array(
					"AUTHOR_ID" => "AUTHOR_NAME",
					"EDITOR_ID" => "EDITOR_NAME",
					"USER_START_ID" => "USER_START_NAME") as $id => $name)
				{
					if (array_key_exists($id, $res))
					{
						$tmp = "";
						if (!empty($res[$id]))
						{
							if (in_array($res[$id], $arTmp))
							{
								$tmp = $arTmp[$res[$id]];
							}
							else
							{
								$arTmp[$res[$id]] = $tmp = (!empty($res[$name."_FRMT"]) ? $res[$name."_FRMT"] :
									CForumUser::GetFormattedNameByUserID($res[$id], $this->sNameTemplate, ($id == "AUTHOR_ID" ? $res : array())));
							}
						}
						$res[$name] = (!empty($tmp) ? $tmp : $res[$name]);
						unset($res[$name."_FRMT"]);
					}
				}
			}

			if ($this->checkUserFields)
			{
				$arUF = array_intersect_key($res, $this->arUserFields);
				if (empty($arUF))
					$this->checkUserFields = false;
				else
				{
					foreach($arUF as $k => $v)
					{
						$res[$k] = $this->arUserFields[$k];
						$res[$k]["ENTITY_VALUE_ID"] = $res["ID"];
						$res[$k]["VALUE"] = $v;
					}
				}
			}
		}
		return $res;
	}
}

class CALLForumFiles
{
	public static function getByMessageID($ID)
	{
		$ID = intval($ID);
		$res = array();
		if ($ID > 0 )
		{
			if (!is_array($GLOBALS["FORUM_CACHE"]["MESSAGE_FILES"]))
				$GLOBALS["FORUM_CACHE"]["MESSAGE_FILES"] = array();

			if (!array_key_exists($ID, $GLOBALS["FORUM_CACHE"]["MESSAGE_FILES"]))
			{
				$GLOBALS["FORUM_CACHE"]["MESSAGE_FILES"][$ID] = array();
				$db_files = CForumFiles::GetList(array("MESSAGE_ID" => "ASC"), array("MESSAGE_ID" => $ID));
				if ($db_files && ($res_file = $db_files->Fetch()))
				{
					do {
						$GLOBALS["FORUM_CACHE"]["MESSAGE_FILES"][$ID][$res_file["FILE_ID"]] = $res_file;
					} while ($res_file = $db_files->Fetch());
				}
			}
			$res = $GLOBALS["FORUM_CACHE"]["MESSAGE_FILES"][$ID];
		}
		return $res;
	}

	public static function CheckFields(&$arFields, &$arParams, $ACTION = "ADD", $extParams = array())
	{
		$aMsg = array();
		$arFiles = (!is_array($arFields) ? array($arFields) : $arFields);
		$arParams = (!is_array($arParams) ? array($arParams) : $arParams);
		$arParams["FORUM_ID"] = intVal($arParams["FORUM_ID"]);
		if (isset($arParams["TOPIC_ID"]))
			$arParams["TOPIC_ID"] = intVal($arParams["TOPIC_ID"]);
		$arParams["MESSAGE_ID"] = intVal($arParams["MESSAGE_ID"]);
		$arParams["USER_ID"] = intVal($arParams["USER_ID"]);

		if (empty($arFiles))
			return true;
		elseif (!empty($arFiles["name"]))
			$arFiles = array($arFiles);
		$ACTION = ($ACTION == "UPDATE" || "NOT_CHECK_DB" ? $ACTION : "ADD");

		if ($arParams["FORUM_ID"] <= 0):
			$aMsg[] = array(
				"id" => 'bad_forum',
				"text" => GetMessage("F_ERR_EMPTY_FORUM_ID"));
		else:
			// Y - Image files		F - Files of specified type		A - All files
			$arForum = (!!$extParams["FORUM"] ? $extParams["FORUM"] : CForumNew::GetByID($arParams["FORUM_ID"]));
			if (empty($arForum))
				$aMsg[] = array(
					"id" => 'bad_forum',
					"text" => GetMessage("F_ERR_FORUM_IS_LOST"));
			elseif (!in_array($arForum["ALLOW_UPLOAD"], array("Y", "F", "A")))
				$aMsg[] = array(
					"id" => 'bad_forum_permission',
					"text" => GetMessage("F_ERR_UPOAD_IS_DENIED"));
		endif;
		if (empty($aMsg)):
			$arFilesExists = array();
			$iFileSize = intVal(COption::GetOptionString("forum", "file_max_size", 5242880));
			foreach ($arFiles as $key => $val):
				$res = "";
				if (strLen($val["name"]) <= 0 && intVal($val["FILE_ID"]) <= 0):
					unset($arFiles[$key]);
					continue;
				elseif (strLen($val["name"]) > 0):
					if ($arForum["ALLOW_UPLOAD"] == "Y"):
						$res = CFile::CheckImageFile($val, $iFileSize, 0, 0);
					elseif ($arForum["ALLOW_UPLOAD"] == "F"):
						$res = CFile::CheckFile($val, $iFileSize, false, $arForum["ALLOW_UPLOAD_EXT"]);
					else:
						$res = CFile::CheckFile($val, $iFileSize, false, false);
					endif;
					if (strLen($res) > 0)
					{
						$aMsg[] = array(
							"id"=>'attach_error',
							"text" => $res);
					}
				endif;

				if (intVal($val["FILE_ID"]) > 0):
					$arFiles[$key]["old_file"] = $val["FILE_ID"];
					$arFilesExists[$val["FILE_ID"]] = $val;
					continue;
				endif;
			endforeach;
			if ($ACTION != "NOT_CHECK_DB" && !empty($arFilesExists))
			{
				$arFilter = array("FILE_FORUM_ID" => $arParams["FORUM_ID"]);
				if (isset($arParams["TOPIC_ID"]))
					$arFilter["FILE_TOPIC_ID"] = $arParams["TOPIC_ID"];
				if (isset($arParams["MESSAGE_ID"]))
					$arFilter["FILE_MESSAGE_ID"] = $arParams["MESSAGE_ID"];
				$arFilter["@FILE_ID"] = array_keys($arFilesExists);

				$db_res = CForumFiles::GetList(array("FILE_ID" => "ASC"), $arFilter);
				if ($db_res && $res = $db_res->Fetch())
				{
					do
					{
						unset($arFilesExists[$res["FILE_ID"]]);
					}while ($res = $db_res->Fetch());
				}

				if (!empty($arFilesExists))
				{
					$aMsg[] = array(
						"id" => 'attach_error',
						"text" => str_replace("#FILE_ID#", implode(", ", array_keys($arFilesExists)), GetMessage("F_ERR_UPOAD_FILES_IS_LOST")));
				}
			}
		endif;
		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}
		$arFields = $arFiles;
		return true;
	}

	public static function Add($arFileID, &$arParams, $bCheckFields = false)
	{
		if (!is_array($arFileID))
			$arFileID = array($arFileID);

		//if ($bCheckFields && !CForumFiles::CheckFields($arFields, $arParams, "ADD")) // TODO add check file by forum params
			//return false;

		$strUploadDir = (!is_set($arParams, "upload_dir") ? "forum/upload" : $arParams["upload_dir"]);
		foreach($arFileID as $fileID)
		{
			$arParams["FILE_ID"] = $fileID;
			$arInsert = $GLOBALS["DB"]->PrepareInsert("b_forum_file", $arParams, $strUploadDir);
			$strSql = "INSERT INTO b_forum_file(".$arInsert[0].") VALUES(".$arInsert[1].")";
			$GLOBALS["DB"]->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return true;
	}

	public static function Save(&$arFields, $arParams, $bCheckFields = true)
	{
		global $DB;
		if ($bCheckFields && !CForumFiles::CheckFields($arFields, $arParams, "ADD"))
			return false;
		$arFiles = array();
		$arParams = (is_array($arParams) ? $arParams : array($arParams));
		$strUploadDir = (!is_set($arParams, "upload_dir") ? "forum/upload" : $arParams["upload_dir"]);
		$arParams = array("FORUM_ID" => intVal($arParams["FORUM_ID"]), "USER_ID" => intVal($arParams["USER_ID"]),
			"TOPIC_ID" => 0, "MESSAGE_ID" => 0);
		foreach ($arFields as $key => $val):
			$val["MODULE_ID"] = "forum";
			$val["FILE_ID"] = intVal($val["FILE_ID"]);
			$val["old_file"] = intVal($val["old_file"]);
			if ($val["FILE_ID"] <= 0 && $val["old_file"] > 0)
				$val["FILE_ID"] = $val["old_file"];
			$old_file = $val["FILE_ID"];
			unset($val["old_file"]);
			if (!empty($val["name"])):
			{
				$res = CFile::SaveFile($val, $strUploadDir, true, true);
				$DB->Commit();
				if ($res > 0)
				{
					CForumFiles::Add($res, $arParams);
					$arFiles[$res] = $arParams;
				}
				if (($res > 0 || !empty($val["del"])) && $old_file > 0)
				{
					CFile::Delete($old_file);
					unset($arFields[$key]);
				}
			}
			elseif (!empty($val["del"])):
				CFile::Delete($val["FILE_ID"]);
				unset($arFields[$key]);
			else:
				$arFiles[$val["FILE_ID"]] = $val;
			endif;
		endforeach;
		return $arFiles;
	}

	public static function UpdateByID($ID, $arFields)
	{
		$ID = (is_array($ID) ? $ID : array($ID));
		$arFields = (is_array($arFields) ? $arFields : array($arFields));
		$res = array();
		foreach ($ID as $val):
			$val = intVal($val);
			if ($val > 0)
				$res[] = $val;
		endforeach;
		$ID = $res;
		$res = array();
		foreach ($arFields as $key => $val):
			if (intVal($val) > 0 && in_array($key, array("FORUM_ID", "TOPIC_ID", "MESSAGE_ID")))
				$res[$key] = $val;
		endforeach;
		$arFields = $res;
		if (empty($ID) || empty($arFields))
			return false;
		$strUpdate = $GLOBALS["DB"]->PrepareUpdate("b_forum_file", $arFields);
		$strSql = "UPDATE b_forum_file SET ".$strUpdate." WHERE FILE_ID IN(".implode(",", $ID).")";
		$GLOBALS["DB"]->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function Delete($arFields = array(), $arParams = array())
	{
		global $DB;
		$arFields = (is_array($arFields) ? $arFields : array($arFields));
		$arParams = (is_array($arParams) ? $arParams : array($arParams));
		$arSQL = array();
		if (empty($arFields))
			return false;
		if (intVal($arFields["FILE_ID"]) > 0)
			$arSQL[] = "FILE_ID=".intVal($arFields["FILE_ID"]);
		if (intVal($arFields["MESSAGE_ID"]) > 0 && (!empty($arSQL) || $arParams["DELETE_MESSAGE_FILE"] == "Y"))
			$arSQL[] = "MESSAGE_ID=".intVal($arFields["MESSAGE_ID"]);
		if (intVal($arFields["TOPIC_ID"]) > 0 && (!empty($arSQL) || $arParams["DELETE_TOPIC_FILE"] == "Y"))
			$arSQL[] = "TOPIC_ID=".intVal($arFields["TOPIC_ID"]);
		if (intVal($arFields["FORUM_ID"]) > 0 && (!empty($arSQL) || $arParams["DELETE_FORUM_FILE"] == "Y"))
			$arSQL[] = "FORUM_ID=".intVal($arFields["FORUM_ID"]);
		if (empty($arSQL))
			return false;
		$db_res = $DB->Query("SELECT * from b_forum_file where ".implode(" AND ", $arSQL), false, "FILE: ".__FILE__." LINE:".__LINE__);
		if ($db_res && $res = $db_res->Fetch())
		{
			do
			{
				CFile::Delete($res["FILE_ID"]);
			} while ($res = $db_res->Fetch());
		}
	}

	public static function OnFileDelete($arFile)
	{
		$result = true;
		if($arFile["MODULE_ID"] == "forum")
		{
			$GLOBALS["DB"]->Query("DELETE from b_forum_file where FILE_ID=".$arFile["ID"], false, "FILE: ".__FILE__." LINE:".__LINE__);
		}
		return $result;
	}
}
?>
