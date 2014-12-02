<?
#############################################
# Bitrix Site Manager Forum					#
# Copyright (c) 2002-2009 Bitrix			#
# http://www.bitrixsoft.com					#
# mailto:admin@bitrixsoft.com				#
#############################################
IncludeModuleLangFile(__FILE__); 

class CAllVoteAnswer
{
	public static function err_mess()
	{
		$module_id = "vote";
		return "<br>Module: ".$module_id."<br>Class: CAllVoteAnswer<br>File: ".__FILE__;
	}
	
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $APPLICATION;
		$aMsg = array();
		$ID = intval($ID);
		$ACTION = ($ID > 0 && $ACTION == "UPDATE" ? "UPDATE" : "ADD");

		unset($arFields["ID"]);
		if (is_set($arFields, "QUESTION_ID") || $ACTION == "ADD"):
			$arFields["QUESTION_ID"] = intval($arFields["QUESTION_ID"]);
			if ($arFields["QUESTION_ID"] <= 0):
				$aMsg[] = array(
					"id" => "QUESTION_ID", 
					"text" => GetMessage("VOTE_FORGOT_QUESTION_ID"));
			endif;
		endif;
		
		if (is_set($arFields, "MESSAGE") || $ACTION == "ADD"):
			//$arFields["MESSAGE"] = trim($arFields["MESSAGE"]);
			$arFields["MESSAGE"] = ($arFields["MESSAGE"] != ' ') ? trim($arFields["MESSAGE"]):' ';
			if (strlen($arFields["MESSAGE"]) <= 0):
				$aMsg[] = array(
					"id" => "MESSAGE", 
					"text" => GetMessage("VOTE_FORGOT_MESSAGE"));
			endif;
		endif;
		
		if (is_set($arFields, "ACTIVE") || $ACTION == "ADD") $arFields["ACTIVE"] = ($arFields["ACTIVE"] == "N" ? "N" : "Y");
		unset($arFields["TIMESTAMP_X"]);
		if (is_set($arFields, "C_SORT") || $ACTION == "ADD") $arFields["C_SORT"] = (intval($arFields["C_SORT"]) > 0 ? intval($arFields["C_SORT"]) : 100);
		if (is_set($arFields, "COUNTER") || $ACTION == "ADD") $arFields["COUNTER"] = intval($arFields["COUNTER"]);
		if (is_set($arFields, "FIELD_TYPE") || $ACTION == "ADD") $arFields["FIELD_TYPE"] = intval($arFields["FIELD_TYPE"]);
		if (is_set($arFields, "FIELD_WIDTH") || $ACTION == "ADD") $arFields["FIELD_WIDTH"] = intval($arFields["FIELD_WIDTH"]);
		if (is_set($arFields, "FIELD_HEIGHT") || $ACTION == "ADD") $arFields["FIELD_HEIGHT"] = intval($arFields["FIELD_HEIGHT"]);
		
		if (is_set($arFields, "FIELD_PARAM") || $ACTION == "ADD") $arFields["FIELD_PARAM"] = substr(trim($arFields["FIELD_PARAM"]), 0, 255);
		if (is_set($arFields, "COLOR") || $ACTION == "ADD") $arFields["COLOR"] = substr(trim($arFields["COLOR"]), 0, 7);
		
		if(!empty($aMsg))
		{
			$e = new CAdminException(array_reverse($aMsg));
			$APPLICATION->ThrowException($e);
			return false;
		}
		return true;
	}

	public static function Add($arFields)
	{
		global $DB;

		if (!CVoteAnswer::CheckFields("ADD", $arFields))
			return false;
/***************** Event onBeforeVoteAnswerAdd *********************/
		foreach (GetModuleEvents("vote", "onBeforeVoteAnswerAdd", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				return false;
/***************** /Event ******************************************/
		if (empty($arFields))
			return false;

		if ($DB->type == "ORACLE")
			$arFields["ID"] = $DB->NextID("SQ_B_VOTE_ANSWER");

		$arInsert = $DB->PrepareInsert("b_vote_answer", $arFields);

		$DB->QueryBind("INSERT INTO b_vote_answer (".$arInsert[0].", TIMESTAMP_X) VALUES(".$arInsert[1].", ".$DB->GetNowFunction().")", array("MESSAGE" => $arFields["MESSAGE"]), false);
		$ID = intval($DB->type == "ORACLE" ? $arFields["ID"] : $DB->LastID());

/***************** Event onAfterVoteAnswerAdd **********************/
		foreach (GetModuleEvents("vote", "onAfterVoteAnswerAdd", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
/***************** /Event ******************************************/
		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;
		$arBinds = array();
		$ID = intval($ID);
		$err_mess = (self::err_mess())."<br>Function: Update<br>Line: ";

		if ($ID <= 0 || !CVoteAnswer::CheckFields("UPDATE", $arFields, $ID))
			return false;
/***************** Event onBeforeVoteQuestionUpdate ****************/
		foreach (GetModuleEvents("vote", "onBeforeVoteAnswerUpdate", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array(&$ID, &$arFields)) === false)
				return false;
/***************** /Event ******************************************/
		if (empty($arFields))
			return false;

		$arFields["~TIMESTAMP_X"] = $DB->GetNowFunction();
		$strUpdate = $DB->PrepareUpdate("b_vote_answer", $arFields);
		if (is_set($arFields, "MESSAGE"))
			$arBinds["MESSAGE"] = $arFields["MESSAGE"];

		if (!empty($strUpdate)):
			$strSql = "UPDATE b_vote_answer SET ".$strUpdate." WHERE ID=".$ID;
			$DB->QueryBind($strSql, $arBinds, false, $err_mess);
//			$DB->Query($strSql, false, $err_mess);
			endif;
/***************** Event onAfterVoteAnswerUpdate *******************/
		foreach (GetModuleEvents("vote", "onAfterVoteAnswerUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
/***************** /Event ******************************************/
		return $ID;
	}

	public static function Delete($ID, $QUESTION_ID = false, $VOTE_ID = false)
	{
		global $DB;
		$err_mess = (self::err_mess())."<br>Function: Delete<br>Line: ";
/***************** Event onBeforeVoteAnswerDelete ******************/
		foreach (GetModuleEvents("vote", "onBeforeVoteAnswerDelete", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$ID, &$QUESTION_ID, &$VOTE_ID)) === false)
				return false;
		}
/***************** /Event ******************************************/

		$ID = (intval($ID) > 0 ? intval($ID) : false);
		$QUESTION_ID = (intval($QUESTION_ID) > 0 ? intval($QUESTION_ID) : false);
		$VOTE_ID = (intval($VOTE_ID) > 0 ? intval($VOTE_ID) : false);
		if ($ID != false):
			$strSqlEventAnswer = "DELETE FROM b_vote_event_answer WHERE ANSWER_ID=".$ID;
			$strSqlAnswer = "DELETE FROM b_vote_answer WHERE ID=".$ID;
		elseif ($QUESTION_ID != false):
			$strSqlEventAnswer = "DELETE FROM b_vote_event_answer WHERE ANSWER_ID IN (
				SELECT VA.ID FROM b_vote_answer VA WHERE VA.QUESTION_ID = ".$QUESTION_ID.")";
			$strSqlAnswer = "DELETE FROM b_vote_answer WHERE QUESTION_ID = ".$QUESTION_ID;
		elseif ($VOTE_ID != false):
			$strSqlEventAnswer = "DELETE FROM b_vote_event_answer WHERE ANSWER_ID IN (
				SELECT VA.ID 
				FROM b_vote_answer VA, b_vote_question VQ 
				WHERE VA.QUESTION_ID = VQ.ID AND VQ.VOTE_ID = ".$VOTE_ID.")";
			$strSqlAnswer = "DELETE FROM b_vote_answer WHERE QUESTION_ID IN (
				SELECT VQ.ID FROM b_vote_question VQ WHERE VQ.VOTE_ID = ".$VOTE_ID.")";
		else:
			return false;
		endif;
		
		$DB->Query($strSqlEventAnswer, false, $err_mess.__LINE__);
		$DB->Query($strSqlAnswer, false, $err_mess.__LINE__);
/***************** Event onAfterVoteAnswerDelete *******************/
		foreach (GetModuleEvents("vote", "onAfterVoteAnswerDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $QUESTION_ID, $VOTE_ID));
/***************** /Event ******************************************/
		return true;
	}

	public static function GetList($QUESTION_ID, $by="s_c_sort", $order="asc", $arFilter=array(), $arAddParams = array())
	{
		global $DB;
		$QUESTION_ID = intval($QUESTION_ID);
		$arSqlSearch = Array();
		$arFilter = (is_array($arFilter) ? $arFilter : array());
		foreach ($arFilter as $key => $val)
		{
			if(empty($val) || $val === "NOT_REF")
				continue;
			$key = strtoupper($key);
			switch($key)
			{
				case "ID":
				case "FIELD_TYPE":
					$match = ($arFilter[$key."_EXACT_MATCH"]=="N" ? "Y" : "N");
					$arSqlSearch[] = GetFilterQuery("A.".$key, $val, $match);
					break;
				case "MESSAGE":
				case "FIELD_PARAM":
					$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" ? "N" : "Y");
					$arSqlSearch[] = GetFilterQuery("A.".$key, $val, $match);
					break;
				case "ACTIVE":
					$arSqlSearch[] = ($val=="Y") ? "A.ACTIVE='Y'" : "A.ACTIVE='N'";
					break;
			}
		}
		
		$order = ($order!="desc" ? "asc" : "desc");
		$by = (($by == "s_id" || $by == "s_counter") ? $by : "s_c_sort");
		if ($by == "s_id")				$strSqlOrder = " ORDER BY A.ID";
		elseif ($by == "s_counter")		$strSqlOrder = " ORDER BY A.COUNTER";
		else							$strSqlOrder = " ORDER BY A.C_SORT";
		$strSqlOrder .= " ".$order;
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSqlFrom = "FROM b_vote_answer A WHERE ".$strSqlSearch." and A.QUESTION_ID=".$QUESTION_ID."";
		$strSql = "SELECT A.* ".$strSqlFrom.$strSqlOrder;

		if ($arAddParams["nTopCount"] > 0)
		{
			$arAddParams["nTopCount"] = intval($arAddParams["nTopCount"]);
			if ($DB->type=="MSSQL")
				$strSql = "SELECT TOP ".$arAddParams["nTopCount"]." A.* ".$strSqlFrom.$strSqlOrder;
			else if ($DB->type=="ORACLE")
				$strSql = "SELECT * FROM(".$strSql.") WHERE ROWNUM<=".$arAddParams["nTopCount"];
			else
				$strSql = "SELECT A.* ".$strSqlFrom.$strSqlOrder." LIMIT 0,".$arAddParams["nTopCount"];
		}
		else if (is_set($arAddParams, "bDescPageNumbering"))
		{
			$db_res = $DB->Query("SELECT COUNT(A.ID) as CNT ".$strSqlFrom, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$iCnt = (($db_res && ($ar_res = $db_res->Fetch())) ? intval($ar_res["CNT"]) : 0 );
			$db_res =  new CDBResult();
			$db_res->NavQuery($strSql, $iCnt, $arAddParams);
			return $db_res;
		}
		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function GetListEx($arOrder = array("ID" => "ASC"), $arFilter=array())
	{
		global $DB;
		
		$arSqlSearch = Array();
		$strSqlSearch = "";
		$arSqlOrder = Array();
		$strSqlOrder = "";

		$arFilter = (is_array($arFilter) ? $arFilter : array());

		foreach ($arFilter as $key => $val)
		{
			if ($val === "NOT_REF")
				continue;
			$key_res = VoteGetFilterOperation($key);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$key = strtoupper($key_res["FIELD"]);
			
			switch($key)
			{
				case "ID":
				case "QUESTION_ID":
					$str = ($strNegative=="Y"?"NOT":"")."(VA.".$key." IS NULL OR VA.".$key."<=0)";
					if (!empty($val))
					{
						$str = ($strNegative=="Y"?" VA.".$key." IS NULL OR NOT ":"")."(VA.".$key." ".$strOperation." ".intval($val).")";
						if ($strOperation == "IN")
						{
							$val = array_unique(array_map("intval", (is_array($val) ? $val : explode(",", $val))), SORT_NUMERIC);
							if (!empty($val))
							{
								$str = ($strNegative=="Y"?" NOT ":"")."(VA.".$key." IN (".implode(",", $val)."))";
							}
						}
					}
					$arSqlSearch[] = $str;
					break;
				case "VOTE_ID":
					$str = ($strNegative=="Y"?"NOT":"")."(VQ.".$key." IS NULL OR VQ.".$key."<=0)";
					if (!empty($val))
					{
						$str = ($strNegative=="Y"?" VQ.".$key." IS NULL OR NOT ":"")."(VQ.".$key." ".$strOperation." ".intval($val).")";
						if ($strOperation == "IN")
						{
							$val = array_unique(array_map("intval", (is_array($val) ? $val : explode(",", $val))), SORT_NUMERIC);
							if (!empty($val))
							{
								$str = ($strNegative=="Y"?" NOT ":"")."(VQ.".$key." IN (".implode(",", $val)."))";
							}
						}
					}
					$arSqlSearch[] = $str;
					break;
				case "CHANNEL_ID":
					$str = ($strNegative=="Y"?"NOT":"")."(V.".$key." IS NULL OR V.".$key."<=0)";
					if (!empty($val))
					{
						$str = ($strNegative=="Y"?" V.".$key." IS NULL OR NOT ":"")."(V.".$key." ".$strOperation." ".intval($val).")";
						if ($strOperation == "IN")
						{
							$val = array_unique(array_map("intval", (is_array($val) ? $val : explode(",", $val))), SORT_NUMERIC);
							if (!empty($val))
							{
								$str = ($strNegative=="Y"?" NOT ":"")."(V.".$key." IN (".implode(",", $val)."))";
							}
						}
					}
					$arSqlSearch[] = $str;
					break;
				case "ACTIVE":
					if (empty($val))
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(VA.".$key." IS NULL OR ".($DB->type == "MSSQL" ? "LEN" : "LENGTH")."(VA.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" VA.".$key." IS NULL OR NOT ":"")."(VA.".$key." ".$strOperation." '".$DB->ForSql($val)."')";
					break;
			}
		}
		if (count($arSqlSearch) > 0)
			$strSqlSearch = " AND (".implode(") AND (", $arSqlSearch).") ";
		
		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by); $order = strtoupper($order);
			$by = (in_array($by, array("ACTIVE", "QUESTION_ID", "C_SORT", "COUNTER")) ? $by : "ID");
			if ($order!="ASC") $order = "DESC";
			if ($by == "ACTIVE") $arSqlOrder[] = " VA.ACTIVE ".$order." ";
			elseif ($by == "QUESTION_ID") $arSqlOrder[] = " VA.QUESTION_ID ".$order." ";
			elseif ($by == "C_SORT") $arSqlOrder[] = " VA.C_SORT ".$order." ";
			elseif ($by == "COUNTER") $arSqlOrder[] = " VA.COUNTER ".$order." ";
			else $arSqlOrder[] = " VA.ID ".$order." ";
		}
		DelDuplicateSort($arSqlOrder); 
		if (count($arSqlOrder) > 0)
			$strSqlOrder = " ORDER BY ".implode(", ", $arSqlOrder);

		$strSql = "
			SELECT V.CHANNEL_ID, VQ.VOTE_ID, VA.*
			FROM b_vote_answer VA
				INNER JOIN b_vote_question VQ ON (VA.QUESTION_ID = VQ.ID)
				INNER JOIN b_vote V ON (VQ.VOTE_ID = V.ID)
			WHERE 1=1  ".$strSqlSearch." ".$strSqlOrder;

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function GetGroupAnswers($ANSWER_ID)
	{
		$err_mess = (self::err_mess())."<br>Function: GetGroupAnswers<br>Line: ";
		global $DB;
		$ANSWER_ID = intval($ANSWER_ID);
		$strSql =
		"SELECT A.MESSAGE, count(A.ID) as COUNTER ".
		"FROM
			b_vote_event_answer A,
			b_vote_event_question Q,
			b_vote_event E
		WHERE
			A.ANSWER_ID = '$ANSWER_ID'
		and Q.ID = A.EVENT_QUESTION_ID
		and E.ID = Q.EVENT_ID
		and E.VALID = 'Y'
		GROUP BY A.MESSAGE
		ORDER BY COUNTER desc";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}
}