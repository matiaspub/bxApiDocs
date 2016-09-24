<?
#############################################
# Bitrix Site Manager Forum					#
# Copyright (c) 2002-2009 Bitrix			#
# http://www.bitrixsoft.com					#
# mailto:admin@bitrixsoft.com				#
#############################################
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/vote/classes/general/vote.php");

class CVote extends CAllVote
{
	public static function err_mess()
	{
		$module_id = "vote";
		return "<br>Module: ".$module_id."<br>Class: CVote<br>File: ".__FILE__;
	}

	public static function GetDropDownList()
	{
		global $DB;
		$err_mess = (CVote::err_mess())."<br>Function: GetDropDownList<br>Line: ";
		$strSql = "
			SELECT
				ID as REFERENCE_ID,
				concat('[', ID, '] ', case when TITLE is null then '' else TITLE end) as REFERENCE
			FROM b_vote
			ORDER BY C_SORT, ID
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	public static function GetActiveVoteID($CHANNEL_ID)
	{
		global $DB;
		$CHANNEL_ID = intval($CHANNEL_ID);
		if ($CHANNEL_ID > 0)
		{
			if (!array_key_exists($CHANNEL_ID, $GLOBALS["VOTE_CACHE"]["CHANNEL"]))
			{
				$db_res = $DB->Query("SELECT MAX(V.ID) AS ACTIVE_VOTE_ID ".
					" FROM b_vote V ".
					" WHERE V.CHANNEL_ID=".intval($CHANNEL_ID)." AND V.ACTIVE = 'Y' AND ".
					" NOW() >= V.DATE_START AND V.DATE_END >= NOW()");
				$GLOBALS["VOTE_CACHE"]["CHANNEL"][$CHANNEL_ID] = ($db_res && ($tmp = $db_res->Fetch())) ? $tmp : array("ACTIVE_VOTE_ID" => 0);
			}
			return $GLOBALS["VOTE_CACHE"]["CHANNEL"][$CHANNEL_ID]["ACTIVE_VOTE_ID"];
		}
		return false;
	}

	public static function CheckVotingIP($VOTE_ID, $REMOTE_ADDR, $KEEP_IP_SEC, $params = array())
	{
		global $DB;
		$err_mess = (CVote::err_mess())."<br>Function: CheckVotingIP<br>Line: ";
		$VOTE_ID = intval($VOTE_ID);
		$KEEP_IP_SEC = intval($KEEP_IP_SEC);
		$params = (is_array($params) ? $params : array($params));
		$params["RETURN_SEARCH_STRING"] = ($params["RETURN_SEARCH_STRING"] == "Y" ? "Y" : "N");

		$arSqlSelect = array("VE.VOTE_ID", "VE.IP");
		$arSqlSearch = array(
			"VE.VOTE_ID='".$VOTE_ID."'",
			"VE.IP='".$DB->ForSql($REMOTE_ADDR, 15)."'");
		if ($KEEP_IP_SEC > 0):
			$arSqlSelect[] = "TIMESTAMPDIFF(SECOND, VE.DATE_VOTE, NOW()) AS KEEP_IP_SEC";
			$arSqlSearch[] = "(FROM_UNIXTIME(UNIX_TIMESTAMP(CURRENT_TIMESTAMP) - ".$KEEP_IP_SEC.") <= VE.DATE_VOTE)";
		endif;
		if ($params["RETURN_SEARCH_STRING"] == "Y"):
			return implode(" AND ", $arSqlSearch);
		elseif ($params["RETURN_SEARCH_ARRAY"] == "Y"):
			return array("search" => implode(" AND ", $arSqlSearch), "select" => implode(",", $arSqlSelect));
		endif;
		$strSql = "SELECT VE.ID FROM b_vote_event VE WHERE ".implode(" AND ", $arSqlSearch);
		$db_res = $DB->Query($strSql, false, $err_mess.__LINE__);
		if ($db_res && $res = $db_res->Fetch()):
			return false;
		endif;
		return true;
	}

	public static function GetNextStartDate($CHANNEL_ID)
	{
		global $DB;
		$err_mess = (CVote::err_mess())."<br>Function: GetNextStartDate<br>Line: ";
		$CHANNEL_ID = intval($CHANNEL_ID);
		$strSql = "
			SELECT
				".$DB->DateToCharFunction("max(DATE_ADD(DATE_END, INTERVAL 1 SECOND))")." MIN_DATE_START
			FROM
				b_vote
			WHERE
				CHANNEL_ID = '$CHANNEL_ID'
			";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		if (strlen($zr["MIN_DATE_START"])<=0)
			return GetTime(time()+CTimeZone::GetOffset(), "FULL");
		else
			return $zr["MIN_DATE_START"];
	}

	public static function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		global $DB;
		$err_mess = (CVote::err_mess())."<br>Function: GetList<br>Line: ";
		$arSqlSearch = array();
		$arFilter = (is_array($arFilter) ? $arFilter : array());
		foreach ($arFilter as $key => $val)
		{
			if (empty($val) || (is_string($val) && $val === "NOT_REF")): 
				continue;
			endif;
			$key = strtoupper($key);
			switch($key)
			{
				case "ID":
					$match = ($arFilter[$key."_EXACT_MATCH"] == "N" ? "Y" : "N");
					$arSqlSearch[] = GetFilterQuery("V.ID", $val, $match);
					break;
				case "ACTIVE":
					$arSqlSearch[] = "V.ACTIVE = '".($val == "Y" ? "Y" : "N")."'";
					break;
				case "DATE_START_1":
					$arSqlSearch[] = "V.DATE_START >= ".$DB->CharToDateFunction($val, "SHORT");
					break;
				case "DATE_START_2":
					$arSqlSearch[] = "V.DATE_START < ".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
					break;
				case "DATE_END_1":
					$arSqlSearch[] = "V.DATE_END >= ".$DB->CharToDateFunction($val, "SHORT");
					break;
				case "DATE_END_2":
					$arSqlSearch[] = "V.DATE_END < ".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
					break;
				case "LAMP":
					if ($val == "red")
						$arSqlSearch[] = "(V.ACTIVE<>'Y' or now()<V.DATE_START or now()>V.DATE_END)";
					elseif ($val == "green")
						$arSqlSearch[] = "(V.ACTIVE='Y' and now()>=V.DATE_START and now()<=V.DATE_END)";
					break;
				case "CHANNEL":
					$match = ($arFilter[$key."_EXACT_MATCH"] == "Y" ? "N" : "Y");
					$arSqlSearch[] = GetFilterQuery("C.ID, C.TITLE, C.SYMBOLIC_NAME", $val, $match);
					break;
				case "CHANNEL_ID":
					$match = ($arFilter[$key."_EXACT_MATCH"] == "N" ? "Y" : "N");
					$arSqlSearch[] = GetFilterQuery("V.CHANNEL_ID", $val, $match);
					break;
				case "CHANNEL_ACTIVE":
				case "CHANNEL_HIDDEN":
					$arSqlSearch[] = "C.".str_replace("CHANNEL_", "", $key)." = '".($val == "Y" ? "Y" : "N")."'";
					break;
				case "TITLE":
				case "DESCRIPTION":
					$match = ($arFilter[$key."_EXACT_MATCH"] == "Y" ? "N" : "Y");
					$arSqlSearch[] = GetFilterQuery("V.".$key, $val, $match);
					break;
				case "COUNTER_1":
					$arSqlSearch[] = "V.COUNTER>='".intval($val)."'";
					break;
				case "COUNTER_2":
					$arSqlSearch[] = "V.COUNTER<='".intval($val)."'";
					break;
			}
		}
		if ($by == "s_id")					$strSqlOrder = "ORDER BY V.ID";
		elseif ($by == "s_title")			$strSqlOrder = "ORDER BY V.TITLE";
		elseif ($by == "s_date_start")		$strSqlOrder = "ORDER BY V.DATE_START";
		elseif ($by == "s_date_end")		$strSqlOrder = "ORDER BY V.DATE_END";
		elseif ($by == "s_lamp")			$strSqlOrder = "ORDER BY LAMP";
		elseif ($by == "s_counter")			$strSqlOrder = "ORDER BY V.COUNTER";
		elseif ($by == "s_active")			$strSqlOrder = "ORDER BY V.ACTIVE";
		elseif ($by == "s_c_sort")			$strSqlOrder = "ORDER BY V.C_SORT";
		elseif ($by == "s_channel")			$strSqlOrder = "ORDER BY V.CHANNEL_ID";
		else
		{
			$by = "s_id";
			$strSqlOrder = "ORDER BY V.ID";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT VV.*, C.TITLE as CHANNEL_TITLE, C.ACTIVE as CHANNEL_ACTIVE,
				C.HIDDEN as CHANNEL_HIDDEN, V.*,
				CASE WHEN (C.ACTIVE = 'Y' AND V.ACTIVE = 'Y' AND V.DATE_START <= NOW() AND NOW() <= V.DATE_END)
					THEN IF (C.VOTE_SINGLE != 'Y', 'green', 'yellow')
					ELSE 'red'
				END AS LAMP,
				".$DB->DateToCharFunction("V.TIMESTAMP_X")." TIMESTAMP_X,
				".$DB->DateToCharFunction("V.DATE_START")."	DATE_START,
				".$DB->DateToCharFunction("V.DATE_END")." DATE_END,
				UNIX_TIMESTAMP(V.DATE_END) - UNIX_TIMESTAMP(V.DATE_START) PERIOD
			FROM (
				SELECT V.ID, COUNT(Q.ID) QUESTIONS
				FROM b_vote V
					INNER JOIN b_vote_channel C ON (C.ID=V.CHANNEL_ID)
					LEFT JOIN b_vote_question Q ON (Q.VOTE_ID=V.ID)
					WHERE ".$strSqlSearch."
					GROUP BY V.ID
			) VV
			INNER JOIN b_vote V ON (V.ID = VV.ID)
			INNER JOIN b_vote_channel C ON (C.ID = V.CHANNEL_ID) ".
			$strSqlOrder;
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$res = new _CVoteDBResult($res);
		$is_filtered = IsFiltered($strSqlSearch);
		return $res;
	}

	public static function GetListEx($arOrder = array(), $arFilter = array())
	{
		global $DB;
		$arSqlSearch = array();
		$arOrder = (is_array($arOrder) ? $arOrder : array());
		$arFilter = (is_array($arFilter) ? $arFilter : array());
		foreach ($arFilter as $key => $val)
		{
			$key_res = CVote::GetFilterOperation($key);
			$key = strtoupper($key_res["FIELD"]);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];

			switch($key)
			{
				case "CHANNEL_ID":
				case "COUNTER":
				case "ID":
					$str = ($strNegative=="Y"?"NOT":"")."(V.".$key." IS NULL OR V.".$key."<=0)";
					if (!empty($val))
					{
						$str = ($strNegative=="Y"?" V.".$key." IS NULL OR NOT ":"")."(V.".$key." ".$strOperation." ".intVal($val).")";
						if ($strOperation == "IN")
						{
							$val = array_unique(array_map("intval", (is_array($val) ? $val : explode(",", $val))), SORT_NUMERIC);
							if (!empty($val))
								$str = ($strNegative=="Y"?" NOT ":"")."(V.".$key." IN (".implode(",", $val)."))";
						}
					}
					$arSqlSearch[] = $str;
					break;
				case "ACTIVE":
					if (empty($val))
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(V.".$key." IS NULL OR LENGTH(V.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" V.".$key." IS NULL OR NOT ":"")."(V.".$key." ".$strOperation." '".$DB->ForSql($val)."' )";
					break;
				case "DATE_START":
				case "DATE_END":
					if (empty($val))
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(V.".$key." IS NULL OR LENGTH(V.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" V.".$key." IS NULL OR NOT ":"")."(V.".$key." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL")." )";
					break;
			}
		}
		$strSqlSearch = (!empty($arSqlSearch) ? " AND (".implode(") AND (", $arSqlSearch).") " : "");
		$arSqlOrder = array();
		foreach($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$by = (in_array($by, array("ID", "TITLE", "DATE_START", "DATE_END", "COUNTER", "ACTIVE", "C_SORT", "CHANNEL_ID")) ? $by : "ID");
			$arSqlOrder[] = "V.".$by." ".(strtoupper($order) == "ASC" ? "ASC" : "DESC");
		}
		DelDuplicateSort($arSqlOrder);
		$strSqlOrder = (!empty($arSqlOrder) ? "ORDER BY ".implode(",", $arSqlOrder) : "");

		$strSql = "
			SELECT V.*,
				C.TITLE as CHANNEL_TITLE,
				C.SYMBOLIC_NAME as CHANNEL_SYMBOLIC_NAME,
				C.C_SORT as CHANNEL_C_SORT,
				C.FIRST_SITE_ID as CHANNEL_FIRST_SITE_ID,
				C.ACTIVE as CHANNEL_ACTIVE,
				C.HIDDEN as CHANNEL_HIDDEN,
				C.TITLE as CHANNEL_TITLE,
				C.VOTE_SINGLE as CHANNEL_VOTE_SINGLE,
				C.USE_CAPTCHA as CHANNEL_USE_CAPTCHA,
				".$DB->DateToCharFunction("V.TIMESTAMP_X")." TIMESTAMP_X,
				".$DB->DateToCharFunction("V.DATE_START")." DATE_START,
				".$DB->DateToCharFunction("V.DATE_END")." DATE_END,
				CASE WHEN (C.ACTIVE = 'Y' AND V.ACTIVE = 'Y' AND V.DATE_START <= NOW() AND NOW() <= V.DATE_END)
					THEN IF (C.VOTE_SINGLE != 'Y', 'green', 'yellow')
					ELSE 'red'
				END AS LAMP
			FROM b_vote V
			INNER JOIN b_vote_channel C ON (V.CHANNEL_ID = C.ID)
			WHERE 1=1 ".$strSqlSearch." ".$strSqlOrder;
		return new _CVoteDBResult($DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__));
	}

	public static function GetPublicList($arFilter=Array(), $strSqlOrder="ORDER BY C.C_SORT, C.ID, V.DATE_START desc", $params = array())
	{
		global $DB, $USER;
		$err_mess = (CVote::err_mess())."<br>Function: GetPublicList<br>Line: ";
		$arSqlSearch = array();
		$arFilter = (is_array($arFilter) ? $arFilter : array());
		$params = (is_array($params) ? $params : array());
		$left_join = "";

		foreach ($arFilter as $key => $val)
		{
			if (empty($val) || (is_string($val) && $val === "NOT_REF"))
				continue;
			$key = strtoupper($key);
			switch($key)
			{
				case "SITE":
					$val = (is_array($val) ? implode(" | ", $val) : $val);
					$match = ($arFilter[$key."_EXACT_MATCH"] == "N" ? "Y" : "N");
					$arSqlSearch[] = GetFilterQuery("CS.SITE_ID", $val, $match);
					$left_join = "LEFT JOIN b_vote_channel_2_site CS ON (C.ID = CS.CHANNEL_ID)";
					break;
				case "CHANNEL":
					$match = ($arFilter[$key."_EXACT_MATCH"] == "N" ? "Y" : "N");
					if (is_array($val)):
						$arr = array();
						foreach ($val as $v):
							$v = trim($v);
							if (!empty($v))
							{
								$arr[] = GetFilterQuery("C.SYMBOLIC_NAME", $v, $match);
							}
						endforeach;
						if (!empty($arr)):
							$arSqlSearch[] = "((".implode(") OR (", $arr)."))";
						endif;
					else:
						$arSqlSearch[] = GetFilterQuery("C.SYMBOLIC_NAME", $val, $match);
					endif;
					break;
				case "FIRST_SITE_ID":
				case "LID":
					$match = ($arFilter[$key."_EXACT_MATCH"] == "N" ? "Y" : "N");
					$arSqlSearch[] = GetFilterQuery("C.FIRST_SITE_ID",$val,$match);
					break;
			}
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$is_admin = in_array(1, $USER->GetUserGroupArray());
		$groups = $USER->GetGroups();
		$iCnt = 0;

		if (array_key_exists("bDescPageNumbering", $params) && $params["nTopCount"] <= 0 || $params["bCount"] === true)
		{
			$strSql = "SELECT COUNT(V1.ID) CNT
				FROM (
					SELECT V.CHANNEL_ID, V.ID, ".($is_admin ? "2" : "max(G.PERMISSION)")." as MAX_PERMISSION
					FROM b_vote V
					INNER JOIN b_vote_channel C ON (C.ACTIVE = 'Y' AND C.HIDDEN = 'N' AND V.CHANNEL_ID = C.ID)
					LEFT JOIN b_vote_channel_2_group G ON (G.CHANNEL_ID = C.ID and G.GROUP_ID in ($groups))
					$left_join
					WHERE
						$strSqlSearch
						AND V.ACTIVE = 'Y' AND V.DATE_START <= NOW()
					GROUP BY V.CHANNEL_ID, V.ID
					".($is_admin ? "" : "
					HAVING MAX_PERMISSION > 0")."
				) V1";
			$db_res = $DB->Query($strSql, false, $err_mess.__LINE__);
			if ($db_res && ($res = $db_res->Fetch()))
				$iCnt = intval($res["CNT"]);
			if ($params["bCount"] === true)
				return $iCnt;
		}
		$strSql = "
			SELECT C.TITLE CHANNEL_TITLE, V.*,
				".$DB->DateToCharFunction("V.DATE_START")."	DATE_START,
				".$DB->DateToCharFunction("V.DATE_END")."	DATE_END, 
				V4.MAX_PERMISSION, V4.LAMP
			FROM (
				SELECT V.CHANNEL_ID, V.ID,
					".($is_admin ? "2" : "max(G.PERMISSION)")." as MAX_PERMISSION, 
					IF((C.VOTE_SINGLE = 'Y'), 
						(IF(V.ID = VV.ACTIVE_VOTE_ID, 'green', 'red')), 
						(IF(V.ACTIVE = 'Y' AND V.DATE_START <= NOW() AND NOW() <= V.DATE_END, 'green', 'red'))) LAMP 
				FROM b_vote V
				INNER JOIN b_vote_channel C ON (C.ACTIVE = 'Y' AND C.HIDDEN = 'N' AND V.CHANNEL_ID = C.ID)
				LEFT JOIN (
					SELECT VVV.CHANNEL_ID, MAX(VVV.ID) AS ACTIVE_VOTE_ID
					FROM b_vote VVV, b_vote_channel CCC
					WHERE VVV.CHANNEL_ID = CCC.ID AND CCC.VOTE_SINGLE='Y' AND VVV.ACTIVE = 'Y' 
						AND NOW() >= VVV.DATE_START AND VVV.DATE_END >= NOW()
					GROUP BY VVV.CHANNEL_ID) VV ON (VV.CHANNEL_ID = V.CHANNEL_ID)
				LEFT JOIN b_vote_channel_2_group G ON (G.CHANNEL_ID = C.ID and G.GROUP_ID in ($groups))
				$left_join
				WHERE
					$strSqlSearch
					AND V.ACTIVE = 'Y' AND V.DATE_START <= NOW()
				GROUP BY V.CHANNEL_ID, V.ID
				".($is_admin ? "" : "
				HAVING MAX_PERMISSION > 0")."
			) V4
			INNER JOIN b_vote V ON (V4.ID = V.ID)
			INNER JOIN b_vote_channel C ON (V4.CHANNEL_ID = C.ID) 
			".$DB->ForSql($strSqlOrder);

		if (array_key_exists("bDescPageNumbering", $params) && $params["nTopCount"] <= 0)
		{
			$db_res =  new CDBResult();
			$db_res->NavQuery($strSql, $iCnt, $params);
		}
		else
		{
			if ($params["nTopCount"] > 0)
				$strSql .= " LIMIT 0,".intval($params["nTopCount"]);
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return $db_res;
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
}