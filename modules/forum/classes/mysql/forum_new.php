<?
##############################################
# Bitrix Site Manager Forum                  #
# Copyright (c) 2002-2009 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/classes/general/forum_new.php");

/**********************************************************************/
/************** FORUM *************************************************/
/**********************************************************************/
class CForumNew extends CAllForumNew
{
	public static function Add($arFields)
	{
		global $DB;

		if (!CForumNew::CheckFields("ADD", $arFields))
			return false;
/***************** Event onBeforeForumAdd **************************/
		foreach (GetModuleEvents("forum", "onBeforeForumAdd", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				return false;
		}
/***************** /Event ******************************************/
		if (empty($arFields))
			return false;
		$arInsert = $DB->PrepareInsert("b_forum", $arFields);
		$strSql = "INSERT INTO b_forum(".$arInsert[0].") VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ID = intVal($DB->LastID());

		if ($ID > 0)
		{
			foreach ($arFields["SITES"] as $key => $value)
			{
				$DB->Query("INSERT INTO b_forum2site (FORUM_ID, SITE_ID, PATH2FORUM_MESSAGE) VALUES(".$ID.", '".$DB->ForSql($key, 2)."', '".$DB->ForSql($value, 250)."')",
					false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			if (is_set($arFields, "GROUP_ID") && is_array($arFields["GROUP_ID"]))
			{
				CForumNew::SetAccessPermissions($ID, $arFields["GROUP_ID"]);
			}
		}
/***************** Event onAfterForumAdd ***************************/
		foreach (GetModuleEvents("forum", "onAfterForumAdd", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$ID, &$arFields));
/***************** /Event ******************************************/
		return $ID;
	}

	public static function OnReindex($NS = array(), $oCallback = NULL, $callback_method = "")
	{
		global $DB;

		$arResultAll = array();
		$arParams = array(
			"PERMISSION" => array(),
			"SITE" => array(),
			"DEFAULT_URL" => array());
		$search_message_count = intVal(COption::GetOptionInt("forum", "search_message_count", 0));

		$strNSJoin = "";
		$strFilter = "";

		if ($NS["MODULE"] == "forum" && intVal($NS["ID"]) > 0 && intVal($NS["CNT"]) > 0)
			$strFilter = " AND (FM.ID>".intVal($NS["ID"]).") ";
		elseif ($NS["MODULE"] == "forum" && intVal($NS["ID"]) > 0) // out of date
			$strFilter = " AND (FM.ID>=".intVal($NS["ID"]).") ";
		if ($NS["SITE_ID"] != "")
		{
			$strNSJoin .= " INNER JOIN b_forum2site FS ON (FS.FORUM_ID=F.ID) ";
			$strFilter .= " AND FS.SITE_ID='".$DB->ForSQL($NS["SITE_ID"])."' ";
		}

		$strSql =
			"SELECT STRAIGHT_JOIN FT.ID as TID, FM.ID as MID, FM.ID as ID, FT.FORUM_ID, FT.TITLE, ".
				CForumNew::Concat("-", array("FT.ID", "FT.TITLE_SEO"))." as TITLE_SEO,
				FT.DESCRIPTION, FT.TAGS, FT.HTML as FT_HTML,
				FM.PARAM1, FM.PARAM2, FM.POST_MESSAGE, FM.POST_MESSAGE_FILTER, FM.POST_MESSAGE_HTML, FM.AUTHOR_NAME, FM.AUTHOR_ID, FM.NEW_TOPIC,
				".$DB->DateToCharFunction("FM.POST_DATE")." as POST_DATE, ".$DB->DateToCharFunction("FM.EDIT_DATE")." as EDIT_DATE, FT.SOCNET_GROUP_ID, FT.OWNER_ID
			FROM b_forum_message FM use index (PRIMARY), b_forum_topic FT, b_forum F
			".$strNSJoin."
			WHERE (FM.TOPIC_ID = FT.ID) AND (F.ID = FT.FORUM_ID) AND (F.INDEXATION = 'Y') AND (FM.APPROVED = 'Y')
			".$strFilter."
			ORDER BY FM.ID";
		if ($search_message_count > 0)
			$strSql .= " LIMIT 0, ".$search_message_count;

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($db_res && COption::GetOptionString("forum", "FILTER", "Y") == "Y")
			$db_res = new _CMessageDBResult($db_res);
		$rownum = 0;
		while ($res = $db_res->Fetch())
		{
			$rownum++;
			if (empty($arParams["PERMISSION"][$res["FORUM_ID"]]))
			{
				$arGroups = CForumNew::GetAccessPermissions($res["FORUM_ID"]);
				$arParams["PERMISSION"][$res["FORUM_ID"]] = array();
				for ($i = 0; $i < count($arGroups); $i++)
				{
					if ($arGroups[$i][1] >= "E")
					{
						$arParams["PERMISSION"][$res["FORUM_ID"]][] = $arGroups[$i][0];
						if ($arGroups[$i][0]==2)
							break;
					}
				}
			}

			if (empty($arParams["SITE"][$res["FORUM_ID"]]))
			{
				$arParams["SITE"][$res["FORUM_ID"]] =  CForumNew::GetSites($res["FORUM_ID"]);
			}

			$arResult = array(
				"ID" => $res["MID"],
				"LID" => array(),
				"LAST_MODIFIED" => ((!empty($res["EDIT_DATE"])) ? $res["EDIT_DATE"] : $res["POST_DATE"]),
				"PARAM1" => $res["FORUM_ID"],
				"PARAM2" => $res["TID"],
				"USER_ID" => $res["AUTHOR_ID"],
				"ENTITY_TYPE_ID"  => ($res["NEW_TOPIC"] == "Y" ? "FORUM_TOPIC" : "FORUM_POST"),
				"ENTITY_ID" => ($res["NEW_TOPIC"] == "Y" ? $res["TID"] : $res["MID"]),
				"PERMISSIONS" => $arParams["PERMISSION"][$res["FORUM_ID"]],
				"TITLE" => $res["TITLE"].($res["NEW_TOPIC"] == "Y" && !empty($res["DESCRIPTION"]) ?
						", ".$res["DESCRIPTION"] : ""),
				"TAGS" => ($res["NEW_TOPIC"] == "Y" ? $res["TAGS"] : ""),
				"BODY" => GetMessage("AVTOR_PREF")." ".$res["AUTHOR_NAME"].". ".
					forumTextParser::clearAllTags(
						COption::GetOptionString("forum", "FILTER", "Y") != "Y" ? $res["POST_MESSAGE"] : $res["POST_MESSAGE_FILTER"]),
				"URL" => "",
				"INDEX_TITLE" => $res["NEW_TOPIC"] == "Y",
			);

			foreach ($arParams["SITE"][$res["FORUM_ID"]] as $key => $val)
			{
				$arResult["LID"][$key] = CForumNew::PreparePath2Message($val,
					array("FORUM_ID"=>$res["FORUM_ID"],
						"TOPIC_ID"=>$res["TID"], "TITLE_SEO"=>$res["TITLE_SEO"],
						"MESSAGE_ID"=>$res["MID"],
						"SOCNET_GROUP_ID" => $res["SOCNET_GROUP_ID"], "OWNER_ID" => $res["OWNER_ID"],
						"PARAM1" => $res["PARAM1"], "PARAM2" => $res["PARAM2"]));
				if (empty($arResult["URL"]) && !empty($arResult["LID"][$key]))
					$arResult["URL"] = $arResult["LID"][$key];
			}

			if (empty($arResult["URL"]))
			{
				if (empty($arParams["DEFAULT_URL"][$res["FORUM_ID"]]))
				{
					$arParams["DEFAULT_URL"][$res["FORUM_ID"]] = "/";
					foreach ($arParams["SITE"][$res["FORUM_ID"]] as $key => $val):
						$db_lang = CLang::GetByID($key);
						if ($db_lang && $ar_lang = $db_lang->Fetch()):
							$arParams["DEFAULT_URL"][$res["FORUM_ID"]] = $ar_lang["DIR"];
							break;
						endif;
					endforeach;
					$arParams["DEFAULT_URL"][$res["FORUM_ID"]] .= COption::GetOptionString("forum", "REL_FPATH", "").
						"forum/read.php?FID=#FID#&TID=#TID#&MID=#MID##message#MID#";
				}
				$arResult["URL"] = CForumNew::PreparePath2Message($arParams["DEFAULT_URL"][$res["FORUM_ID"]],
					array("FORUM_ID"=>$res["FORUM_ID"], "TOPIC_ID"=>$res["TID"], "MESSAGE_ID"=>$res["MID"],
						"SOCNET_GROUP_ID" => $res["SOCNET_GROUP_ID"], "OWNER_ID" => $res["OWNER_ID"],
						"PARAM1" => $res["PARAM1"], "PARAM2" => $res["PARAM2"]));
			}

			if($oCallback)
			{
				$resCall = call_user_func(array($oCallback, $callback_method), $arResult);
				if(!$resCall)
					return $arResult["ID"];
			}
			else
			{
				$arResultAll[] = $arResult;
			}
		}

		if ($oCallback && ($search_message_count > 0) && ($rownum >= ($search_message_count - 1)))
			return $arResult["ID"];
		if ($oCallback)
			return false;

		return $arResultAll;
	}

	public static function GetNowTime($ResultType = "timestamp")
	{
		global $DB;
		static $result = array();
		$ResultType = (in_array($ResultType, array("timestamp", "time")) ? $ResultType : "timestamp");
		if (empty($result)):
			$db_res = $DB->Query("SELECT ".$DB->DateToCharFunction($DB->GetNowFunction(), "FULL")." FORUM_DATE", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$res = $db_res->Fetch();
			$result["time"] = $res["FORUM_DATE"];
			$result["timestamp"] = MakeTimeStamp($res["FORUM_DATE"]);
		endif;
		return $result[$ResultType];
	}

	public static function Concat($glue = "", $pieces = array())
	{
		return "TRIM(BOTH '".$glue."' FROM REPLACE(CONCAT_WS('".$glue."',".implode(",", $pieces)."), '".$glue.$glue."', '".$glue."'))";
	}
}

/**********************************************************************/
/************** FORUM GROUP *******************************************/
/**********************************************************************/
class CForumGroup extends CAllForumGroup
{
	public static function Add($arFields)
	{
		global $DB;

		if (!CForumGroup::CheckFields("ADD", $arFields))
			return false;
		if(CACHED_b_forum_group !== false)
			$GLOBALS["CACHE_MANAGER"]->CleanDir("b_forum_group");
/***************** Event onBeforeGroupForumsAdd ********************/
		$events = GetModuleEvents("forum", "onBeforeGroupForumsAdd");
		while ($arEvent = $events->Fetch())
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				return false;
		}
/***************** /Event ******************************************/
		if (empty($arFields))
			return false;
		$arInsert = $DB->PrepareInsert("b_forum_group", $arFields);
		$strSql = "INSERT INTO b_forum_group(".$arInsert[0].") VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ID = intVal($DB->LastID());

		if (array_key_exists("LANG", $arFields))
		{
			foreach ($arFields["LANG"] as $l)
			{
				$arInsert = $DB->PrepareInsert("b_forum_group_lang", $l);
				$strSql = "INSERT INTO b_forum_group_lang(FORUM_GROUP_ID, ".$arInsert[0].") VALUES(".$ID.", ".$arInsert[1].")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
		CForumGroup::Resort();
/***************** Event onAfterGroupForumsAdd *********************/
		foreach (GetModuleEvents("forum", "onAfterGroupForumsAdd", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
/***************** /Event ******************************************/
		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;
		$ID = intVal($ID);
		if ($ID <= 0):
			return false;
		endif;

		if (!CForumGroup::CheckFields("UPDATE", $arFields, $ID))
			return false;
		if(CACHED_b_forum_group !== false)
			$GLOBALS["CACHE_MANAGER"]->CleanDir("b_forum_group");
/***************** Event onBeforeGroupForumsUpdate *****************/
		foreach (GetModuleEvents("forum", "onBeforeGroupForumsUpdate", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$ID, &$arFields)) === false)
				return false;
		}
/***************** /Event ******************************************/
		if (empty($arFields))
			return false;
		$strUpdate = $DB->PrepareUpdate("b_forum_group", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "UPDATE b_forum_group SET ".$strUpdate." WHERE ID = ".$ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		if (is_set($arFields, "LANG"))
		{
			$DB->Query("DELETE FROM b_forum_group_lang WHERE FORUM_GROUP_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			foreach ($arFields["LANG"] as $l)
			{
				$arInsert = $DB->PrepareInsert("b_forum_group_lang", $l);
				$strSql = "INSERT INTO b_forum_group_lang(FORUM_GROUP_ID, ".$arInsert[0].") VALUES(".$ID.", ".$arInsert[1].")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
		CForumGroup::Resort();
/***************** Event onAfterGroupForumsUpdate *****************/
		foreach (GetModuleEvents("forum", "onAfterGroupForumsUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
/***************** /Event ******************************************/
		return $ID;
	}
}
