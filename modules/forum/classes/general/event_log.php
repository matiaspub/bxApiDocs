<?
##############################################
# Bitrix Site Manager Forum                  #
# Copyright (c) 2002-2009 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
IncludeModuleLangFile(__FILE__);

class CForumEventLog
{
	public static function Log($object, $action, $id, $description = "", $title = "")
	{
		if (COption::GetOptionString("forum", "LOGS", "Q") <= "A")
			return false;
		$arTypesTitle = array(
			"FORUM_MESSAGE_APPROVE" => GetMessage("FORUM_MESSAGE_APPROVE"),
			"FORUM_MESSAGE_UNAPPROVE" => GetMessage("FORUM_MESSAGE_UNAPPROVE"),
			"FORUM_MESSAGE_MOVE" => GetMessage("FORUM_MESSAGE_MOVE"),
			"FORUM_MESSAGE_EDIT" => GetMessage("FORUM_MESSAGE_EDIT"),
			"FORUM_MESSAGE_DELETE" => GetMessage("FORUM_MESSAGE_DELETE"),
			"FORUM_MESSAGE_SPAM" => GetMessage("FORUM_MESSAGE_SPAM"),

			"FORUM_TOPIC_APPROVE" => GetMessage("FORUM_TOPIC_APPROVE"),
			"FORUM_TOPIC_UNAPPROVE" => GetMessage("FORUM_TOPIC_UNAPPROVE"),
			"FORUM_TOPIC_STICK" => GetMessage("FORUM_TOPIC_STICK"),
			"FORUM_TOPIC_UNSTICK" => GetMessage("FORUM_TOPIC_UNSTICK"),
			"FORUM_TOPIC_OPEN" => GetMessage("FORUM_TOPIC_OPEN"),
			"FORUM_TOPIC_CLOSE" => GetMessage("FORUM_TOPIC_CLOSE"),
			"FORUM_TOPIC_MOVE" => GetMessage("FORUM_TOPIC_MOVE"),
			"FORUM_TOPIC_EDIT" => GetMessage("FORUM_TOPIC_EDIT"),
			"FORUM_TOPIC_DELETE" => GetMessage("FORUM_TOPIC_DELETE"),
			"FORUM_TOPIC_SPAM" => GetMessage("FORUM_TOPIC_SPAM"),

			"FORUM_FORUM_EDIT" => GetMessage("FORUM_FORUM_EDIT"),
			"FORUM_FORUM_DELETE" => GetMessage("FORUM_FORUM_DELETE")
		);
		$object = strToUpper($object);
		$action = strToUpper($action);
		$type = "FORUM_".$object."_".$action;
		$title = trim($title);
		if (empty($title))
		{
			$title = $arTypesTitle[$type];
		}
		$description = trim($description);

		CEventLog::Log("NOTICE", $type, "forum", $id, $description);
	}

	public static function GetAuditTypes()
	{
		return array(
			"FORUM_MESSAGE_APPROVE" => "[FORUM_MESSAGE_APPROVE] ".GetMessage("FORUM_MESSAGE_APPROVE"),
			"FORUM_MESSAGE_UNAPPROVE" => "[FORUM_MESSAGE_UNAPPROVE] ".GetMessage("FORUM_MESSAGE_UNAPPROVE"),
			"FORUM_MESSAGE_MOVE" => "[FORUM_MESSAGE_MOVE] ".GetMessage("FORUM_MESSAGE_MOVE"),
			"FORUM_MESSAGE_EDIT" => "[FORUM_MESSAGE_EDIT] ".GetMessage("FORUM_MESSAGE_EDIT"),
			"FORUM_MESSAGE_DELETE" => "[FORUM_MESSAGE_DELETE] ".GetMessage("FORUM_MESSAGE_DELETE"),
			"FORUM_MESSAGE_SPAM" => "[FORUM_MESSAGE_DELETE] ".GetMessage("FORUM_MESSAGE_SPAM"),

			"FORUM_TOPIC_APPROVE" => "[FORUM_TOPIC_APPROVE] ".GetMessage("FORUM_TOPIC_APPROVE"),
			"FORUM_TOPIC_UNAPPROVE" => "[FORUM_TOPIC_UNAPPROVE] ".GetMessage("FORUM_TOPIC_UNAPPROVE"),
			"FORUM_TOPIC_STICK" => "[FORUM_TOPIC_STICK] ".GetMessage("FORUM_TOPIC_STICK"),
			"FORUM_TOPIC_UNSTICK" => "[FORUM_TOPIC_UNSTICK] ".GetMessage("FORUM_TOPIC_UNSTICK"),
			"FORUM_TOPIC_OPEN" => "[FORUM_TOPIC_OPEN] ".GetMessage("FORUM_TOPIC_OPEN"),
			"FORUM_TOPIC_CLOSE" => "[FORUM_TOPIC_CLOSE] ".GetMessage("FORUM_TOPIC_CLOSE"),
			"FORUM_TOPIC_MOVE" => "[FORUM_TOPIC_MOVE] ".GetMessage("FORUM_TOPIC_MOVE"),
			"FORUM_TOPIC_EDIT" => "[FORUM_TOPIC_EDIT] ".GetMessage("FORUM_TOPIC_EDIT"),
			"FORUM_TOPIC_DELETE" => "[FORUM_TOPIC_DELETE] ".GetMessage("FORUM_TOPIC_DELETE"),
			"FORUM_TOPIC_SPAM" => "[FORUM_TOPIC_DELETE] ".GetMessage("FORUM_TOPIC_SPAM"),

//			"FORUM_FORUM_EDIT" => "[FORUM_FORUM_EDIT] ".GetMessage("FORUM_FORUM_EDIT"),
//			"FORUM_FORUM_DELETE" => "[FORUM_FORUM_DELETE] ".GetMessage("FORUM_FORUM_DELETE")
		);
	}
}

class CEventForum
{
	public static function MakeForumObject()
	{
		$obj = new CEventForum;
		return $obj;
	}

	public static function GetFilter()
	{
		$arFilter = array();
		if (CModule::IncludeModule('forum'))
		{
			if (!COption::GetOptionString("forum", "LOGS", "Q") <= "A")
			{
				$arFilter["FORUM"] = GetMessage("LOG_FORUM");
			}
		}
		return  $arFilter;
	}

	public static function GetAuditTypes()
	{
		AddEventHandler("main", "GetAuditTypesForum", array("CForumEventLog", "GetAuditTypes"));
		foreach(GetModuleEvents("main", "GetAuditTypesForum", true) as $arEvent)
		{
			$AuditTypes = ExecuteModuleEventEx($arEvent);
		}
		return $AuditTypes;
	}

	public static function GetEventInfo($row, $arParams)
	{
		if (CModule::IncludeModule('forum'))
		{
			$DESCRIPTION = unserialize($row['DESCRIPTION']);
			$site_id = ($row['SITE_ID'] == "s1") ? "" : "site_".$row['SITE_ID']."/";
	// messages
			if (strpos($row['AUDIT_TYPE_ID'], "MESSAGE"))
			{
				$MID = $row['ITEM_ID'];
				$TID = $DESCRIPTION['TOPIC_ID'];
				$FID = $DESCRIPTION['FORUM_ID'];
				if ($arMessage = CForumMessage::GetByID($MID))
					$sPath = SITE_DIR.CComponentEngine::MakePathFromTemplate($arParams['FORUM_MESSAGE_PATH'], array("FORUM_ID" => $FID, "TOPIC_ID" => $TID, "TITLE_SEO" => $TID, "MESSAGE_ID" => $MID, "SITE_ID" => $site_id));
				else
					if ($arTopic = CForumTopic::GetByID($TID))
						$sPath = SITE_DIR.CComponentEngine::MakePathFromTemplate($arParams['FORUM_TOPIC_PATH'], array("FORUM_ID" => $FID, "TOPIC_ID" => $TID, "TITLE_SEO" => $TID, "SITE_ID" => $site_id));

				switch($row['AUDIT_TYPE_ID'])
				{
					case "FORUM_MESSAGE_APPROVE":
						$EventPrint = GetMessage("LOG_FORUM_MESSAGE_APPROVE");
						break;
					case "FORUM_MESSAGE_UNAPPROVE":
						$EventPrint = GetMessage("LOG_FORUM_MESSAGE_UNAPPROVE");
						break;
					case "FORUM_MESSAGE_MOVE":
						$EventPrint = GetMessage("LOG_FORUM_MESSAGE_MOVE");
						break;
					case "FORUM_MESSAGE_EDIT":
						$EventPrint = GetMessage("LOG_FORUM_MESSAGE_EDIT");
						break;
					case "FORUM_MESSAGE_DELETE":
						$EventPrint = GetMessage("LOG_FORUM_MESSAGE_DELETE");
						break;
				}
			}
			else
	// topics
			{
				$TID = $row["ITEM_ID"];
				$FID = $DESCRIPTION['FORUM_ID'];
				if ($arTopic = CForumTopic::GetByID($TID))
					$sPath = SITE_DIR.CComponentEngine::MakePathFromTemplate($arParams['FORUM_TOPIC_PATH'], array("FORUM_ID" => $FID, "TOPIC_ID" => $TID, "TITLE_SEO" => $TID, "SITE_ID" => $site_id));

				switch($row['AUDIT_TYPE_ID'])
				{
					case "FORUM_TOPIC_APPROVE":
						$EventPrint = GetMessage("LOG_FORUM_TOPIC_APPROVE");
						break;
					case "FORUM_TOPIC_UNAPPROVE":
						$EventPrint = GetMessage("LOG_FORUM_TOPIC_UNAPPROVE");
						break;
					case "FORUM_TOPIC_STICK":
						$EventPrint = GetMessage("LOG_FORUM_TOPIC_STICK");
						break;
					case "FORUM_TOPIC_UNSTICK":
						$EventPrint = GetMessage("LOG_FORUM_TOPIC_UNSTICK");
						break;
					case "FORUM_TOPIC_OPEN":
						$EventPrint = GetMessage("LOG_FORUM_TOPIC_OPEN");
						break;
					case "FORUM_TOPIC_CLOSE":
						$EventPrint = GetMessage("LOG_FORUM_TOPIC_CLOSE");
						break;
					case "FORUM_TOPIC_DELETE":
						$EventPrint = GetMessage("LOG_FORUM_TOPIC_DELETE");
						break;
					case "FORUM_TOPIC_MOVE":
						$EventPrint = GetMessage("LOG_FORUM_TOPIC_MOVE", array("#FORUM#" => $DESCRIPTION["FORUM_TITLE"]));
						break;
					case "FORUM_TOPIC_EDIT":
						$EventPrint = GetMessage("LOG_FORUM_TOPIC_EDIT");
						break;
				}
			}
			if($arForum = CForumNew::GetByID($FID))
			{
				$ForumPageURL = SITE_DIR.CComponentEngine::MakePathFromTemplate($arParams['FORUM_PATH'], array("FORUM_ID" => $FID, "SITE_ID" => $site_id));
				$resForum = "<a href =".$ForumPageURL.">".$arForum["NAME"]."</a>";
			}
			else
			{
				$resForum = GetMessage("LOG_FORUM");
			}
		}
		return array(
					"eventType" => $EventPrint,
					"eventName" => $DESCRIPTION['TITLE'],
					"eventURL" => $sPath,
					"pageURL" => $resForum
				);
	}

	public static function GetFilterSQL($var)
	{
		if (is_array($var))
			foreach($var as $key => $val)
				$ar[] = array("MODULE_ID" => $val);
		return $ar;
	}
}
?>
