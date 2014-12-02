<?
#############################################
# Bitrix Site Manager Forum					#
# Copyright (c) 2002-2009 Bitrix			#
# http://www.bitrixsoft.com					#
# mailto:admin@bitrixsoft.com				#
#############################################
IncludeModuleLangFile(__FILE__); 

class CAllVoteChannel
{
	public static function err_mess()
	{
		$module_id = "vote";
		return "<br>Module: ".$module_id."<br>Class: CAllVoteChannel<br>File: ".__FILE__;
	}

	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $DB, $APPLICATION;
		$aMsg = array();
		$ID = intVal($ID);

		foreach(array("TITLE", "SYMBOLIC_NAME") as $key)
		{
			if (is_set($arFields, $key) || $ACTION == "ADD")
			{
				$arFields[$key] = trim($arFields[$key]);
				if (empty($arFields[$key]))
					$aMsg[] = array(
						"id" => $key,
						"text" => GetMessage("VOTE_FORGOT_".$key));
//				GetMessage("VOTE_FORGOT_SYMBOLIC_NAME");
//				GetMessage("VOTE_FORGOT_TITLE");
			}
		}
		if (is_set($arFields, "SITE") || $ACTION == "ADD")
		{
			if (!(is_array($arFields["SITE"]) && !empty($arFields["SITE"])))
			{
				$aMsg[] = array(
					"id" => "SITE",
					"text" => GetMessage("VOTE_FORGOT_SITE"));
			}
			else
			{
				reset($arFields["SITE"]);
			}
		}
		if (empty($aMsg) && is_set($arFields, "SYMBOLIC_NAME"))
		{
			if (preg_match("/[^a-z_0-9]/is", $arFields["SYMBOLIC_NAME"], $matches))
			{
				$aMsg[] = array(
					"id" => "SYMBOLIC_NAME",
					"text" => GetMessage("VOTE_INCORRECT_SYMBOLIC_NAME"));
			}
			elseif (is_set($arFields, "SITE"))
			{
				$arFilter = array(
					"ID" => "~".$ID,
					"SITE" => $arFields["SITE"],
					"ACTIVE" => "Y",
					"SID" => $arFields["SYMBOLIC_NAME"],
					"SID_EXACT_MATCH" => "Y");
				$db_res = CVoteChannel::GetList($v1, $v2, $arFilter, $v3);
				if ($db_res && ($res = $db_res->Fetch()))
				{
					$aMsg[] = array(
						"id" => "SYMBOLIC_NAME",
						"text" => str_replace(
							"#ID#", $res["ID"],
							GetMessage("VOTE_SYMBOLIC_NAME_ALREADY_IN_USE")));
				}
			}
			if (empty($aMsg))
				$arFields["SYMBOLIC_NAME"] = strtoupper($arFields["SYMBOLIC_NAME"]);
		}

		unset($arFields["TIMESTAMP_X"]);

		if (is_set($arFields, "FIRST_SITE_ID") || $ACTION == "ADD")
		{
			$arFields["=FIRST_SITE_ID"] = $DB->ForSql($arFields["FIRST_SITE_ID"], 2);
			unset($arFields["FIRST_SITE_ID"]);
		}

		if (is_set($arFields, "C_SORT") || $ACTION == "ADD") $arFields["C_SORT"] = trim($arFields["C_SORT"]);
		foreach(array("ACTIVE", "HIDDEN", "VOTE_SINGLE", "USE_CAPTCHA") as $key)
			if (is_set($arFields, $key) || $ACTION == "ADD") $arFields[$key] = ($arFields[$key] == "Y" ? "Y" : "N");

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		return true;
	}

	public static function Add($arFields)
	{
		global $DB;

		if (!self::CheckFields("ADD", $arFields))
			return false;
/***************** Event onBeforeMessageAdd ************************/
		foreach (GetModuleEvents("vote", "onBeforeVoteChannelAdd", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				return false;
/***************** /Event ******************************************/
		if ($DB->type == "ORACLE")
			$arFields["ID"] = $DB->NextID("SQ_B_VOTE_CHANNEL");

		$arInsert = $DB->PrepareInsert("b_vote_channel", $arFields);

		$strSql = "INSERT INTO b_vote_channel (".$arInsert[0].", TIMESTAMP_X) ".
			"VALUES(".$arInsert[1].", ".$DB->GetNowFunction().")";

		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = intval($DB->type == "ORACLE" ? $arFields["ID"] : $DB->LastID());

		if ($ID > 0)
		{
			foreach ($arFields["SITE"] as $sid)
			{
				$strSql = "INSERT INTO b_vote_channel_2_site (CHANNEL_ID, SITE_ID) ".
					"VALUES ($ID, '".$DB->ForSql($sid, 2)."')";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
		if (is_array($arFields["GROUP_ID"]) && !empty($arFields["GROUP_ID"]))
			self::SetAccessPermissions($ID, $arFields["GROUP_ID"]);
/***************** Events onAfterMessageAdd ************************/
		foreach (GetModuleEvents("vote", "onAfterVoteChannelAdd", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
/***************** /Events *****************************************/

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;
		if (!self::CheckFields("UPDATE", $arFields, $ID))
			return false;
		$ID = intval($ID);
		/***************** Event onBeforeMessageAdd ************************/
		foreach (GetModuleEvents("vote", "onBeforeVoteChannelUpdate", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				return false;
		/***************** /Event ******************************************/

		$strUpdate = $DB->PrepareUpdate("b_vote_channel", $arFields);

		$strSql = "UPDATE b_vote_channel SET ".$strUpdate." WHERE ID=".$ID;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if (!empty($arFields["SITE"]))
		{
			$DB->Query("DELETE FROM b_vote_channel_2_site WHERE CHANNEL_ID=".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			foreach ($arFields["SITE"] as $sid)
			{
				$strSql = "INSERT INTO b_vote_channel_2_site (CHANNEL_ID, SITE_ID) ".
					"VALUES ($ID, '".$DB->ForSql($sid, 2)."')";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
		if (is_array($arFields["GROUP_ID"]) && !empty($arFields["GROUP_ID"]))
			self::SetAccessPermissions($ID, $arFields["GROUP_ID"]);
		/***************** Events onAfterMessageAdd ************************/
		foreach (GetModuleEvents("vote", "onAfterVoteChannelUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
		/***************** /Events *****************************************/

		return $ID;
	}

	public static function SetAccessPermissions($ID, $arGroups)
	{
		global $DB;
		$ID = intVal($ID);
		$arGroups = (is_array($arGroups) ? $arGroups : array());
		$arMainGroups = array();
		if ($ID <= 0 || empty($arGroups))
			return false;

		$db_res = CGroup::GetList($by = "ID", $order = "ASC");
		if ($db_res && $res = $db_res->Fetch())
		{
			do
			{
				$arMainGroups[$res["ID"]] = $res["ID"];
			} while ($res = $db_res->Fetch());
			$arGroups = array_intersect_key($arGroups, $arMainGroups);

			$DB->Query(
				"DELETE FROM b_vote_channel_2_group WHERE CHANNEL_ID=".$ID,
				false, "File: ".__FILE__."<br>Line: ".__LINE__);

			foreach ($arGroups as $key => $val)
			{
				$key = intval($key); $val = intval($val);
				if ($key <= 1 || !in_array($val, $GLOBALS["aVotePermissions"]["reference_id"]))
					continue;
				$arFields = array(
					"CHANNEL_ID" => $ID,
					"GROUP_ID" => $key,
					"PERMISSION" => "'".$val."'");
				$DB->Insert("b_vote_channel_2_group", $arFields, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
		return true;
	}

	public static function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = (CVoteChannel::err_mess())."<br>Function: GetList<br>Line: ";
		global $DB;
		$arSqlSearch = Array();
		$left_join = "";
		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				if(is_array($val))
				{
					if(count($val) <= 0)
						continue;
				}
				else
				{
					if( (strlen($val) <= 0) || ($val === "NOT_REF") )
						continue;
				}
				$match_value_set = array_key_exists($key."_EXACT_MATCH", $arFilter);
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("C.ID",$val,$match);
						break;
					case "SITE_ID":
					case "SITE":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("CS.SITE_ID", $val, $match);
						$left_join = "LEFT JOIN b_vote_channel_2_site CS ON (C.ID = CS.CHANNEL_ID)";
						break;
					case "TITLE":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("C.TITLE",$val,$match);
						break;
					case "SID":
					case "SYMBOLIC_NAME":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("C.SYMBOLIC_NAME",$val,$match);
						break;
					case "HIDDEN":
					case "ACTIVE":
						$arSqlSearch[] = ($val=="Y") ? "C.".$key."='Y'" : "C.".$key."='N'";
						break;
					case "FIRST_SITE_ID":
					case "LID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("C.FIRST_SITE_ID",$val,$match);
						break;
				}
			}
		}

		if ($by == "s_id")					$strSqlOrder = "ORDER BY C.ID";
		elseif ($by == "s_timestamp")		$strSqlOrder = "ORDER BY C.TIMESTAMP_X";
		elseif ($by == "s_c_sort")			$strSqlOrder = "ORDER BY C.C_SORT";
		elseif ($by == "s_active")			$strSqlOrder = "ORDER BY C.ACTIVE";
		elseif ($by == "s_hidden")			$strSqlOrder = "ORDER BY C.HIDDEN";
		elseif ($by == "s_symbolic_name")	$strSqlOrder = "ORDER BY C.SYMBOLIC_NAME";
		elseif ($by == "s_title")			$strSqlOrder = "ORDER BY C.TITLE ";
		elseif ($by == "s_votes")			$strSqlOrder = "ORDER BY VOTES";
		else
		{
			$by = "s_id";
			$strSqlOrder = "ORDER BY C.ID";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
		SELECT CC.*, C.*, C.FIRST_SITE_ID LID, C.SYMBOLIC_NAME SID,
				".$DB->DateToCharFunction("C.TIMESTAMP_X")." TIMESTAMP_X
		FROM (
			SELECT C.ID, count(V.ID) VOTES
			FROM b_vote_channel C
				LEFT JOIN b_vote V ON (V.CHANNEL_ID = C.ID)
				".$left_join."
			WHERE ".$strSqlSearch."
			GROUP BY C.ID) CC
		INNER JOIN b_vote_channel C ON (C.ID = CC.ID)
		".$strSqlOrder;

		$is_filtered = IsFiltered($strSqlSearch);

		if (VOTE_CACHE_TIME===false || strpos($_SERVER['REQUEST_URI'], '/bitrix/admin/')!==false)
		{
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);
			return $res;
		}
		else
		{
			global $CACHE_MANAGER;
			$md5 = md5($strSql);
			$arCache = array();
			if($CACHE_MANAGER->Read(VOTE_CACHE_TIME, "b_vote_channel_".$md5, "b_vote_channel"))
			{
				$arCache = $CACHE_MANAGER->Get("b_vote_channel_".$md5);
			}
			else
			{
				$res = $DB->Query($strSql, false, $err_mess.__LINE__);
				while($ar = $res->Fetch())
					$arCache[] = $ar;

				$CACHE_MANAGER->Set("b_vote_channel_".$md5, $arCache);
			}

			$r = new CDBResult();
			$r->InitFromArray($arCache);
			unset($arCache);
			return $r;
		}
	}

	public static function GetSiteArray($CHANNEL_ID)
	{
		$err_mess = (CAllVoteChannel::err_mess())."<br>Function: GetSiteArray<br>Line: ";
		global $DB;
		$CHANNEL_ID = intval($CHANNEL_ID);
		if ($CHANNEL_ID<=0) return false;

		$arCache = Array();

		if (VOTE_CACHE_TIME===false)
		{
			$arrRes = array();
			$rs = $DB->Query("SELECT CS.SITE_ID FROM b_vote_channel_2_site CS WHERE CS.CHANNEL_ID = ".$CHANNEL_ID, false, $err_mess.__LINE__);
			while ($ar = $rs->Fetch()) $arrRes[] = $ar["SITE_ID"];
			return $arrRes;
		}
		else
		{
			global $CACHE_MANAGER;
			if($CACHE_MANAGER->Read(VOTE_CACHE_TIME, "b_vote_channel_2_site", "b_vote_channel_2_site"))
			{
				$arCache = $CACHE_MANAGER->Get("b_vote_channel_2_site");
			}
			else
			{
				$rs = $DB->Query('SELECT * '.'FROM b_vote_channel_2_site', false, $err_mess.__LINE__);
				while ($ar = $rs->Fetch()) 
					$arCache[$ar["CHANNEL_ID"]][] = $ar["SITE_ID"];

				$CACHE_MANAGER->Set("b_vote_channel_2_site", $arCache);
			}
			if (array_key_exists($CHANNEL_ID, $arCache))
				return $arCache[$CHANNEL_ID];
			else
				return array();
		}

	}

	public static function Delete($ID)
	{
		global $DB;
		$err_mess = (CAllVoteChannel::err_mess())."<br>Function: Delete<br>Line: ";
		$ID = intval($ID);
		if ($ID <= 0):
			return true;
		endif;
		/***************** Event onBeforeVoteChannelDelete ******************/
		foreach (GetModuleEvents("vote", "onBeforeVoteChannelDelete", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array(&$ID)) === false)
				return false;
		/***************** /Event ******************************************/

		// drop votes
		$z = $DB->Query("SELECT ID FROM b_vote WHERE CHANNEL_ID='$ID'", false, $err_mess.__LINE__);
		while ($zr = $z->Fetch()) CVote::Delete($zr["ID"]);
		
		$DB->Query("DELETE FROM b_vote_channel_2_group WHERE CHANNEL_ID=".$ID, false, $err_mess.__LINE__);
		$DB->Query("DELETE FROM b_vote_channel_2_site WHERE CHANNEL_ID=".$ID, false, $err_mess.__LINE__);
		$res = $DB->Query("DELETE FROM b_vote_channel WHERE ID=".$ID, false, $err_mess.__LINE__);
		/***************** Event onAfterVoteChannelDelete ******************/
		foreach (GetModuleEvents("vote", "onAfterVoteChannelDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID));
		/***************** /Event ******************************************/
		return $res;
	}

	public static function GetByID($ID)
	{
		$ID = intval($ID);
		if ($ID <= 0)
			return false;
		$res = CVoteChannel::GetList($by, $order, array("ID" => $ID), $is_filtered);
		return $res;
	}

	public static function GetArrayGroupPermission($channel_id)
	{
		global $DB;

		$strSql =
			"SELECT * ".
			"FROM b_vote_channel_2_group ".
			"WHERE CHANNEL_ID = '".intval($channel_id)."'";

		$dbres = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$arRes = Array();
		while($res = $dbres->Fetch())
			$arRes[$res["GROUP_ID"]] = $res["PERMISSION"];

		return $arRes;

	}

	public static function GetGroupPermission($channel_id, $arGroups=false, $params = array())
	{
		global $DB, $USER, $CACHE_MANAGER, $APPLICATION;
		$err_mess = (CAllVoteChannel::err_mess())."<br>Function: GetGroupPermission<br>Line: ";
		$channel_id = trim($channel_id);
		$arGroups = ($arGroups === false ? $USER->GetUserGroupArray() : $arGroups);
		$arGroups = ((!is_array($arGroups) || empty($arGroups)) ? array(2) : $arGroups);
		$groups = implode(",", $arGroups);
		$params = is_array($params) ? $params : array("get_from_database" => $params);

		$cache = array(
			"channel_id" => $channel_id,
			"groups" => $arGroups,
			"get_from_database" => $params["get_from_database"]);
		$cache_id = "b_vote_perm_".md5(serialize($cache));
		$permission = 0;

		if (VOTE_CACHE_TIME !== false && $CACHE_MANAGER->Read(VOTE_CACHE_TIME, $cache_id, "b_vote_perm"))
		{
			$permission = intval($CACHE_MANAGER->Get($cache_id));
		}
		else
		{
			if ($params["get_from_database"] != "Y")
				$permission = ((in_array(1, $USER->GetUserGroupArray()) || $APPLICATION->GetGroupRight("vote") >= "W") ? 4 : $permission);

			if ($permission <= 0 && !empty($groups))
			{
				$strSql =
					"SELECT BVC2G.CHANNEL_ID, BVC.SYMBOLIC_NAME CHANNEL_SID, MAX(BVC2G.PERMISSION) as PERMISSION
				FROM b_vote_channel_2_group BVC2G
				INNER JOIN b_vote_channel BVC ON (BVC2G.CHANNEL_ID = BVC.ID)
				WHERE ".($params["CHANNEL_SID"] != "Y" ? "BVC2G.CHANNEL_ID" : "BVC.SYMBOLIC_NAME").
						"='".$DB->ForSql($channel_id)."' and GROUP_ID in ($groups)
				GROUP BY BVC2G.CHANNEL_ID, BVC.SYMBOLIC_NAME";
				$db_res = $DB->Query($strSql, false, $err_mess.__LINE__);
				if ($db_res && ($res = $db_res->Fetch()))
				{
					$permission = intval($res["PERMISSION"]);
					if (VOTE_CACHE_TIME !== false)
					{
						$cache["channel_id"] = $res["CHANNEL_SID"];
						$cache_id = "b_vote_perm_".md5(serialize($cache));
						$CACHE_MANAGER->Set($cache_id, $permission);
						$cache["channel_id"] = trim($res["CHANNEL_ID"]);
					}
				}
			}
			if (VOTE_CACHE_TIME !== false)
			{
				$cache_id = "b_vote_perm_".md5(serialize($cache));
				$CACHE_MANAGER->Set($cache_id, $permission);
			}
		}
		return $permission;
	}
}

class CVoteDiagramType
{
	var $arType = Array();

	public function CVoteDiagramType($directCall=true)
	{
		if ($directCall)
		{
			trigger_error("CVoteDiagramType is singleton!", E_USER_ERROR);
			return;
		}

		$this->arType = Array(
			VOTE_DEFAULT_DIAGRAM_TYPE => GetMessage("VOTE_DIAGRAM_TYPE_HISTOGRAM"),
			"circle" => GetMessage("VOTE_DIAGRAM_TYPE_CIRCLE")
		);
	}

	public static function &getInstance()
	{
		static $instance;
		if (!is_object($instance))
			$instance = new CVoteDiagramType(false);

		return $instance;
	}

}

function VoteGetFilterOperation($key)
{
	$strNegative = "N";
	if (substr($key, 0, 1)=="!")
	{
		$key = substr($key, 1);
		$strNegative = "Y";
	}

	if (substr($key, 0, 2)==">=")
	{
		$key = substr($key, 2);
		$strOperation = ">=";
	}
	elseif (substr($key, 0, 1)==">")
	{
		$key = substr($key, 1);
		$strOperation = ">";
	}
	elseif (substr($key, 0, 2)=="<=")
	{
		$key = substr($key, 2);
		$strOperation = "<=";
	}
	elseif (substr($key, 0, 1)=="<")
	{
		$key = substr($key, 1);
		$strOperation = "<";
	}
	elseif (substr($key, 0, 1)=="@")
	{
		$key = substr($key, 1);
		$strOperation = "IN";
	}
	elseif (substr($key, 0, 1)=="%")
	{
		$key = substr($key, 1);
		$strOperation = "LIKE";
	}
	else
	{
		$strOperation = "=";
	}

	return array("FIELD"=>$key, "NEGATIVE"=>$strNegative, "OPERATION"=>$strOperation);
}