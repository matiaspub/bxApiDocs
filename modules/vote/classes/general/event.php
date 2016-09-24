<?
#############################################
# Bitrix Site Manager Forum					#
# Copyright (c) 2002-2009 Bitrix			#
# http://www.bitrixsoft.com					#
# mailto:admin@bitrixsoft.com				#
#############################################

class CAllVoteEvent
{
	public static function err_mess()
	{
		$module_id = "vote";
		return "<br>Module: ".$module_id."<br>Class: CAllVoteEvent<br>File: ".__FILE__;
	}

	public static function GetByID($ID)
	{
		$ID = intval($ID);
		if ($ID<=0) return;
		$res = CVoteEvent::GetList($by, $order, array("ID" => $ID), $is_filtered, "Y");
		return $res;
	}

	public static function GetAnswer($EVENT_ID, $ANSWER_ID)
	{
		$err_mess = (self::err_mess())."<br>Function: GetAnswer<br>Line: ";
		global $DB;
		$EVENT_ID = intval($EVENT_ID);
		$ANSWER_ID = intval($ANSWER_ID);
		$strSql = "
			SELECT
				A.ANSWER_ID,
				A.MESSAGE
			FROM
				b_vote_event E,
				b_vote_event_answer A,
				b_vote_event_question Q				
			WHERE
				E.ID = '$EVENT_ID'
			and Q.EVENT_ID = E.ID
			and A.EVENT_QUESTION_ID = Q.ID
			and	A.ANSWER_ID = '$ANSWER_ID'
			";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		if ($zr = $z->Fetch())
		{
			if (strlen($zr["MESSAGE"])>0) return $zr["MESSAGE"]; else return $zr["ANSWER_ID"];
		}
		return false;
	}

	public static function Delete($EVENT_ID)
	{
		$err_mess = (self::err_mess())."<br>Function: Delete<br>Line: ";
		global $DB;
		$EVENT_ID = intval($EVENT_ID);
		if ($EVENT_ID <= 0):
			return;
		endif;
		// reset vote validity
		CVoteEvent::SetValid($EVENT_ID, "N");
		$DB->StartTransaction();
		// reset questions and asnwers voting events
		$DB->Query("DELETE FROM b_vote_event_answer WHERE EVENT_QUESTION_ID IN (".
			"SELECT VEQ.ID FROM b_vote_event_question VEQ WHERE VEQ.EVENT_ID=".$EVENT_ID.")", false, $err_mess.__LINE__);
		$DB->Query("DELETE FROM b_vote_event_question WHERE EVENT_ID=".$EVENT_ID, false, $err_mess.__LINE__);
		$DB->Update("b_vote_user", array("COUNTER" => "COUNTER - 1"), "WHERE ID IN (".
			"SELECT VOTE_USER_ID FROM b_vote_event WHERE ID=$EVENT_ID)",$err_mess.__LINE__);
		// reset voting events
		$res = $DB->Query("DELETE FROM b_vote_event WHERE ID=$EVENT_ID", false, $err_mess.__LINE__);
		$DB->Commit();
		return $res;
	}

	public static function SetValid($EVENT_ID, $valid)
	{
		$err_mess = (self::err_mess())."<br>Function: SetValid<br>Line: ";
		global $DB;
		$valid = ($valid == "Y" ? "Y" : "N");
		$arrQuestion = array();
		$EVENT_ID = intval($EVENT_ID);
		if ($EVENT_ID <= 0):
			return;
		endif;
		$arFields = ($valid == "Y" ? array("COUNTER" => "COUNTER + 1") : array("COUNTER" => "COUNTER - 1"));
		$strSql =
			"SELECT DISTINCT EA.ANSWER_ID, EQ.QUESTION_ID, E.VALID, E.VOTE_ID ".
			" FROM b_vote_event E ".
				" LEFT JOIN b_vote_event_question EQ ON (EQ.EVENT_ID = E.ID) ".
				" LEFT JOIN b_vote_event_answer EA ON (EA.EVENT_QUESTION_ID = EQ.ID) ".
			" WHERE E.ID = ".$EVENT_ID." AND E.VALID <> '".$valid."'";
		//echo $strSql;
		$db_res = $DB->Query($strSql, false, $err_mess.__LINE__);
		if ($db_res && ($res = $db_res->Fetch())):
			$VOTE_ID = $res["VOTE_ID"];
			$DB->StartTransaction();
			do {
				$DB->Update("b_vote_answer", $arFields, "WHERE ID='".$res["ANSWER_ID"]."'",$err_mess.__LINE__);
				if (!in_array($res["QUESTION_ID"], $arrQuestion)):
					$DB->Update("b_vote_question", $arFields, "WHERE ID='".$res["QUESTION_ID"]."'", $err_mess.__LINE__);
					$arrQuestion[] = $res["QUESTION_ID"];
				endif;
			} while ($res = $db_res->Fetch());
			
			// change valid flag
			$DB->Update("b_vote_event", array("VALID" => "'".$valid."'"), "WHERE ID=".$EVENT_ID, $err_mess.__LINE__);
			// decrement vote counter 
			unset($GLOBALS["VOTE_CACHE_VOTE_".$VOTE_ID]);
			$DB->Update("b_vote", $arFields, "WHERE ID='".intval($VOTE_ID)."'", $err_mess.__LINE__);
			$DB->Commit();
		endif;
	}

	public static function CheckStat($arParams, $bForseCheckStat = false)
	{
		global $DB;
		$err_mess = (self::err_mess())."<br>Function: CheckStat<br>Line: ";
		$VOTE_ID = intval($arParams["VOTE_ID"]);
		$arAnswers = array(); $arQuestions = array();

		if (!is_set($arParams, "ANSWERS") || !is_set($arParams, "QUESTIONS"))
		{
			$strSQL = "SELECT A.ID AS ANSWER_ID, Q.ID AS QUESTION_ID ".
				" FROM b_vote V ".
				" LEFT JOIN b_vote_question Q ON (V.ID = Q.VOTE_ID) ".
				" LEFT JOIN b_vote_answer A ON (Q.ID = A.QUESTION_ID ) ".
				" WHERE V.ID = ".$VOTE_ID;
			$db_res = $DB->Query($strSQL, false, $err_mess.__LINE__);
			if ($db_res && ($res = $db_res->Fetch()))
			{
				do {
					$arAnswers[] = $res["ANSWER_ID"];
					$arQuestions[] = $res["QUESTION_ID"];
				} while ($res = $db_res->Fetch());
				$arQuestions = array_unique($arQuestions);
			}
		}
		else
		{
			$arQuestions = (is_array($arParams["QUESTIONS"]) ? $arParams["QUESTIONS"] : array($arParams["QUESTIONS"]));
			$arAnswers = (is_array($arParams["ANSWERS"]) ? $arParams["ANSWERS"] : array($arParams["ANSWERS"]));
		}

		if (!empty($arAnswers) && !empty($arQuestions))
		{
			$strSQL ="SELECT E.ID AS EVENT_ID ".
				" FROM b_vote_event E ".
				" LEFT JOIN b_vote_event_question EQ ON (E.ID = EQ.EVENT_ID) ".
				" LEFT JOIN b_vote_event_answer EA ON (EA.EVENT_QUESTION_ID = EQ.ID) ".
				" WHERE E.VOTE_ID=".$VOTE_ID." AND (EA.ANSWER_ID NOT IN (".implode(",", $arAnswers).") OR EQ.QUESTION_ID NOT IN (".implode(",", $arQuestions)."))";
			$db_res = $DB->Query($strSQL, false, $err_mess.__LINE__);
			if ($db_res && ($res = $db_res->Fetch()))
			{
				$arEvetns = array();
				$bForseCheckStat = true;
				do {
					$arEvetns[] = $res["EVENT_ID"];
				} while ($res = $db_res->Fetch());

				$DB->StartTransaction();
				// reset questions and asnwers voting events
				$strSQL = implode(",", $arEvetns);
				$DB->Query("DELETE FROM b_vote_event_answer WHERE EVENT_QUESTION_ID IN (".
					"SELECT VEQ.ID FROM b_vote_event_question VEQ WHERE VEQ.EVENT_ID IN (".$strSQL."))", false, $err_mess.__LINE__);
				$DB->Query("DELETE FROM b_vote_event_question WHERE EVENT_ID IN (".$strSQL.")", false, $err_mess.__LINE__);
				// reset voting events
				$DB->Query("DELETE FROM b_vote_event WHERE ID IN (".$strSQL.")", false, $err_mess.__LINE__);
				$DB->Commit();
			}
		}
		if ($bForseCheckStat)
		{
			$DB->Query(
				"UPDATE b_vote V SET V.COUNTER=(".
					"SELECT COUNT(VE.ID) FROM b_vote_event VE WHERE VE.VOTE_ID=V.ID".
				") WHERE V.ID=".$VOTE_ID, false, $err_mess.__LINE__);
			$DB->Query(
				"UPDATE b_vote_question VQ SET VQ.COUNTER=(".
					"SELECT COUNT(VEQ.ID) FROM b_vote_event_question VEQ WHERE VEQ.QUESTION_ID=VQ.ID".
				") WHERE VQ.VOTE_ID=".$VOTE_ID, false, $err_mess.__LINE__);
			$DB->Query(
				"UPDATE b_vote_answer VA, b_vote_question VQ SET VA.COUNTER=(".
					" SELECT COUNT(VEA.ID) FROM b_vote_event_answer VEA WHERE VEA.ANSWER_ID=VA.ID".
				") WHERE VQ.ID = VA.QUESTION_ID AND VQ.VOTE_ID=".$VOTE_ID, false, $err_mess.__LINE__);
			$DB->Query(
				"UPDATE b_vote_user VU, b_vote_event VE SET VU.COUNTER=(".
					" SELECT COUNT(VE.ID) FROM b_vote_event VE WHERE VU.ID=VE.VOTE_USER_ID AND VE.VALID='Y' ".
				") WHERE VU.ID IN (SELECT DISTINCT VEE.VOTE_USER_ID FROM b_vote_event VEE WHERE VOTE_ID=".$VOTE_ID.")", false, $err_mess.__LINE__);
		}
	}

	public static function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered, $get_user="N")
	{
		$err_mess = (self::err_mess())."<br>Function: GetList<br>Line: ";
		global $DB;
		$arSqlSearch = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			$filter_keys = array_keys($arFilter);
			$count = count($filter_keys);
			for ($i=0; $i<$count; $i++)
			{
				$key = $filter_keys[$i];
				$val = $arFilter[$filter_keys[$i]];
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
				$match_value_set = (in_array($key."_EXACT_MATCH", $filter_keys)) ? true : false;
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("E.ID",$val,$match);
						break;
					case "VALID":
						$arSqlSearch[] = ($val=="Y") ? "E.VALID='Y'" : "E.VALID='N'";
						break;
					case "DATE_1":
						$arSqlSearch[] = "E.DATE_VOTE>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_2":
						$arSqlSearch[] = "E.DATE_VOTE<=".$DB->CharToDateFunction($val." 23:59:59", "FULL");
						break;
					case "VOTE_USER":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("E.VOTE_USER_ID",$val,$match);
						break;
					case "USER_ID":
						if ($get_user=="Y")
						{
							$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
							$arSqlSearch[] = GetFilterQuery("U.AUTH_USER_ID",$val,$match);
						}
						break;
					case "SESSION":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("E.STAT_SESSION_ID",$val,$match);
						break;
					case "IP":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("E.IP",$val,$match,array("."));
						break;
					case "VOTE":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("E.VOTE_ID, V.TITLE",$val,$match);
						break;
					case "VOTE_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("E.VOTE_ID",$val,$match);
						break;
				}
			}
		}

		if ($by == "s_id")					$strSqlOrder = "ORDER BY E.ID";
		elseif ($by == "s_valid")			$strSqlOrder = "ORDER BY E.VALID";
		elseif ($by == "s_date")			$strSqlOrder = "ORDER BY E.DATE_VOTE";
		elseif ($by == "s_session")			$strSqlOrder = "ORDER BY E.STAT_SESSION_ID";
		elseif ($by == "s_vote_user")		$strSqlOrder = "ORDER BY E.VOTE_USER_ID";
		elseif ($by == "s_vote")			$strSqlOrder = "ORDER BY E.VOTE_ID";
		elseif ($by == "s_ip")				$strSqlOrder = "ORDER BY E.IP";
		else 
		{
			$by = "s_id";
			$strSqlOrder = "ORDER BY E.ID";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		if ($get_user=="Y")
		{
			$select = " ,
				U.AUTH_USER_ID, U.STAT_GUEST_ID,
				A.LOGIN,
				".$DB->Concat("A.LAST_NAME", "' '", "A.NAME")."	AUTH_USER_NAME
			";
			$from = "
			LEFT JOIN b_vote_user U ON (U.ID = E.VOTE_USER_ID)
			LEFT JOIN b_user A ON (A.ID = U.AUTH_USER_ID)
			";

		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT 
				E.*,
				".$DB->DateToCharFunction("E.DATE_VOTE")."	DATE_VOTE,
				V.TITLE, V.DESCRIPTION, V.DESCRIPTION_TYPE
				$select
			FROM
				b_vote_event E
			INNER JOIN b_vote V ON (V.ID=E.VOTE_ID)
			$from
			WHERE
			$strSqlSearch
			$strSqlOrder
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}
}
?>
