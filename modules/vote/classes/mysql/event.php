<?
##############################################
# Bitrix Site Manager Forum                  #
# Copyright (c) 2002-2009 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/vote/classes/general/event.php");

class CVoteEvent extends CAllVoteEvent
{
	public static function err_mess()
	{
		$module_id = "vote";
		return "<br>Module: ".$module_id."<br>Class: CVoteEvent<br>File: ".__FILE__;
	}

	public static function GetUserAnswerStat($arSort = array(), $arFilter = array(), $arParams = array())
	{
		global $DB, $USER;
		$err_mess = (self::err_mess())."<br>Function: GetUserAnswerStat<br>Line: ";
		$arFilter = (is_array($arFilter) ? $arFilter : array());
		if (!is_array($arSort) && $arSort > 0)
		{
			$arFilter["VOTE_ID"] = $arSort;
			$arFilter["VALID"] = "Y";
			$arSort = array();
		}
		$arFilter["bGetMemoStat"] = ($arFilter["bGetMemoStat"] == "N" ? "N" : "Y");

		$arSqlSelect = $arSqlSearch = $arSqlGroup = array();
		$strSqlSelect = $strSqlSearch = $strSqlGroup = "";

		foreach ($arFilter as $key => $val)
		{
			$key_res = VoteGetFilterOperation($key);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$key = strtoupper($key_res["FIELD"]);
			switch($key)
			{
				case "ID":
				case "VOTE_ID":
				case "QUESTION_ID":
				case "ANSWER_ID":
				case "USER_ID":
				case "AUTH_USER_ID":
					switch ($key)
					{
						case "ID":
						case "VOTE_ID":
							$key = ("VE.".$key);
							break;
						case "QUESTION_ID":
							$key = ("VEQ.".$key);
							break;
						case "ANSWER_ID":
							$key = ("VEA.".$key);
							break;
						case "USER_ID":
						case "AUTH_USER_ID":
							$key = "VU.AUTH_USER_ID";
							break;
					}

					$str = ($strNegative=="Y"?"NOT":"")."(".$key." IS NULL OR ".$key."<=0)";
					if (!empty($val))
					{
						$str = ($strNegative=="Y"?" ".$key." IS NULL OR NOT ":"")."(".$key." ".$strOperation." ".intVal($val).")";
						if ($strOperation == "IN")
						{
							$val = array_unique(array_map("intval", (is_array($val) ? $val : explode(",", $val))), SORT_NUMERIC);
							if (!empty($val))
							{
								$str = ($strNegative=="Y"?" NOT ":"")."(".$key." IN (".implode(",", $val)."))";
							}
						}
					}
					$arSqlSearch[] = $str;
					break;
				case "VALID":
					if (empty($val))
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(VE.".$key." IS NULL OR LENGTH(VE.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" VE.".$key." IS NULL OR NOT ":"")."(VE.".$key." ".$strOperation." '".$DB->ForSql($val)."' )";
					break;
				case "BGETMEMOSTAT":
					if ($val == "Y")
					{
						$arSqlGroup[] = $arSqlSelect[] = "VEA.MESSAGE";
						$arSqlSearch[] = "VEA.MESSAGE != ' '";
					}
					break;
				case "BGETVOTERS":
					$arSqlSearch[] = "VU.AUTH_USER_ID > 0";
					$arFilter["bGetVoters"] = intval($val === "Y" ? $USER->GetID() : $val);
					break;
				case "BGETEVENTRESULTS":
					$arFilter["bGetEventResults"] = intval($arFilter["bGetEventResults"]);
					if ($arFilter["bGetEventResults"] > 0)
						$arSqlSelect[] = "MAX(CASE WHEN VE.ID=".$arFilter["bGetEventResults"]." THEN VEA.ANSWER_ID ELSE NULL END) AS RESTORED_ANSWER_ID";
					break;
			}
		}
		if (!empty($arSqlSearch))
			$strSqlSearch = " AND (".implode(") AND (", $arSqlSearch).") ";
		if (!empty($arSqlSelect))
			$strSqlSelect = ", ".implode(", ", $arSqlSelect);
		if (!empty($arSqlGroup))
			$strSqlGroup = ", ".implode(", ", $arSqlGroup);

		$strSql =
			"SELECT VEQ.QUESTION_ID, VEA.ANSWER_ID, COUNT(VEA.ID) as COUNTER, ".
				"MIN(TIMESTAMPDIFF(SECOND, VE.DATE_VOTE, NOW())) AS LAST_VOTE".$strSqlSelect.
			" FROM b_vote_event VE ".
				" INNER JOIN b_vote_event_question VEQ ON (VEQ.EVENT_ID = VE.ID) ".
				" INNER JOIN b_vote_event_answer VEA ON (VEA.EVENT_QUESTION_ID = VEQ.ID) ".
				" LEFT JOIN b_vote_user VU ON (VU.ID = VE.VOTE_USER_ID)".
			" WHERE 1=1 ".$strSqlSearch.
			" GROUP BY VEQ.QUESTION_ID, VEA.ANSWER_ID".$strSqlGroup.
			" ORDER BY COUNTER DESC";
		if (isset($arFilter["bGetVoters"]))
		{
			$strSql = "SELECT COUNT(VEG.COUNTER) AS CNT FROM (".
				"SELECT 'x' AS COUNTER ".
				" FROM b_vote_event VE ".
				" INNER JOIN b_vote_event_question VEQ ON (VEQ.EVENT_ID = VE.ID) ".
				" INNER JOIN b_vote_event_answer VEA ON (VEA.EVENT_QUESTION_ID = VEQ.ID) ".
				" LEFT JOIN b_vote_user VU ON (VU.ID = VE.VOTE_USER_ID)".
				" WHERE 1=1 ".$strSqlSearch.
				" GROUP BY VEQ.QUESTION_ID, VEA.ANSWER_ID, VU.AUTH_USER_ID".$strSqlGroup.") VEG";
			$db_res = $DB->Query($strSql);
			if ($db_res && ($res = $db_res->Fetch()))
			{
				$strSql = "SELECT VEQ.QUESTION_ID, VEA.ANSWER_ID, VU.AUTH_USER_ID, COUNT(DISTINCT VEA.ID) as COUNTER, \n\t".
					"MIN(TIMESTAMPDIFF(SECOND, VE.DATE_VOTE, NOW())) AS LAST_VOTE, \n\t".
					($arFilter["bGetVoters"] > 0 ?
						"SUM(case when RV0.ID is not null then 1 else 0 end) RANK" : "0 as RANK").$strSqlSelect."\n".
				"FROM b_vote_event VE \n\t".
					"INNER JOIN b_vote_event_question VEQ ON (VEQ.EVENT_ID = VE.ID) \n\t".
					"INNER JOIN b_vote_event_answer VEA ON (VEA.EVENT_QUESTION_ID = VEQ.ID) \n\t".
					"LEFT JOIN b_vote_user VU ON (VU.ID = VE.VOTE_USER_ID)\n\t".
					"LEFT JOIN b_rating_user RV ON (RV.ENTITY_ID = VU.AUTH_USER_ID AND RV.RATING_ID = ".intval(CRatings::GetAuthorityRating()).")\n".
					($arFilter["bGetVoters"] > 0 ?
						"\tLEFT JOIN b_rating_vote RV0 ON (RV0.USER_ID = ".$arFilter["bGetVoters"]." AND RV0.OWNER_ID = VU.AUTH_USER_ID) \n" : "").
				" WHERE 1=1 ".$strSqlSearch."\n".
				" GROUP BY VEQ.QUESTION_ID, VEA.ANSWER_ID, VU.AUTH_USER_ID".$strSqlGroup."\n".
				" ORDER BY ".(IsModuleInstalled("intranet") ? "RV.VOTE_WEIGHT DESC, RANK DESC" : "RANK DESC, RV.VOTE_WEIGHT DESC").", VU.AUTH_USER_ID ASC";
				$db_res = new CDBResult();
				$db_res->NavQuery($strSql, $res["CNT"], $arParams);
			}
		}
		else
		{
			$db_res = $DB->Query($strSql, false, $err_mess.__LINE__);
		}
		return $db_res;
	}
}