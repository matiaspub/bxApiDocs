<?
#############################################
# Bitrix Site Manager Forum					#
# Copyright (c) 2002-2009 Bitrix			#
# http://www.bitrixsoft.com					#
# mailto:admin@bitrixsoft.com				#
#############################################
IncludeModuleLangFile(__FILE__);

class CAllVoteQuestion
{
	public static function err_mess()
	{
		$module_id = "vote";
		return "<br>Module: ".$module_id."<br>Class: CAllVoteQuestion<br>File: ".__FILE__;
	}

	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		$aMsg = array();
		$ID = intVal($ID);
		$ACTION = ($ACTION == "UPDATE" ? "UPDATE" : "ADD");
		$arQuestion = array();
		if ($ID > 0 && $ACTION == "UPDATE"):
			$db_res = CVoteQuestion::GetByID($ID);
			if (!($db_res && $arQuestion = $db_res->Fetch())):
				$aMsg[] = array(
					"id" => "ID",
					"text" => GetMessage("VOTE_QUESTION_NOT_FOUND"));
			endif;
		endif;

		unset($arFields["ID"]);
		if (is_set($arFields, "VOTE_ID") || $ACTION == "ADD")
		{
			$arFields["VOTE_ID"] = intVal($arFields["VOTE_ID"]);
			if ($arFields["VOTE_ID"] <= 0):
				$aMsg[] = array(
					"id" => "VOTE_ID",
					"text" => GetMessage("VOTE_FORGOT_VOTE_ID"));
			endif;
		}
		if (is_set($arFields, "QUESTION") || $ACTION == "ADD")
		{
			$arFields["QUESTION"] = trim($arFields["QUESTION"]);
			if (empty($arFields["QUESTION"])):
				$aMsg[] = array(
					"id" => "QUESTION",
					"text" => GetMessage("VOTE_FORGOT_QUESTION"));
			endif;
		}
		if (is_set($arFields, "IMAGE_ID") && strLen($arFields["IMAGE_ID"]["name"]) <= 0 && strLen($arFields["IMAGE_ID"]["del"]) <= 0)
		{
			unset($arFields["IMAGE_ID"]);
		}
		elseif (is_set($arFields, "IMAGE_ID"))
		{
			if ($str = CFile::CheckImageFile($arFields["IMAGE_ID"])):
				$aMsg[] = array(
					"id" => "IMAGE_ID",
					"text" => $str);
			else:
				$arFields["IMAGE_ID"]["MODULE_ID"] = "vote";
				if (!empty($arQuestion)):
					$arFields["IMAGE_ID"]["old_file"] = $arQuestion["IMAGE_ID"];
				endif;
			endif;
		}

		if (is_set($arFields, "ACTIVE") || $ACTION == "ADD") $arFields["ACTIVE"] = ($arFields["ACTIVE"] == "N" ? "N" : "Y");
		unset($arFields["TIMESTAMP_X"]);
		if (is_set($arFields, "C_SORT") || $ACTION == "ADD") $arFields["C_SORT"] = (intVal($arFields["C_SORT"]) > 0 ? intVal($arFields["C_SORT"]) : 100);
		if (is_set($arFields, "COUNTER") || $ACTION == "ADD") $arFields["COUNTER"] = intVal($arFields["COUNTER"]);
		if (is_set($arFields, "QUESTION_TYPE") || $ACTION == "ADD") $arFields["QUESTION_TYPE"] = ($arFields["QUESTION_TYPE"] == "html" ? "html" : "text");
		if (is_set($arFields, "DIAGRAM") || $ACTION == "ADD") $arFields["DIAGRAM"] = ($arFields["DIAGRAM"] == "N" ? "N" : "Y");
		if (is_set($arFields, "DIAGRAM_TYPE") && (empty($arFields["DIAGRAM_TYPE"]) || in_array($arFields["DIAGRAM_TYPE"], GetVoteDiagramArray()))):
			$arFields["DIAGRAM_TYPE"] = VOTE_DEFAULT_DIAGRAM_TYPE;
		endif;
		if (is_set($arFields, "TEMPLATE")) $arFields["TEMPLATE"] = substr(trim($arFields["TEMPLATE"]), 0, 255);
		if (is_set($arFields, "TEMPLATE_NEW")) $arFields["TEMPLATE_NEW"] = substr(trim($arFields["TEMPLATE_NEW"]), 0, 255);

		if ((is_set($arFields, "TEMPLATE") ||is_set($arFields, "TEMPLATE_NEW")) &&
			COption::GetOptionString("vote", "VOTE_COMPATIBLE_OLD_TEMPLATE", "Y") == "Y")
		{
			$old_module_version = CVote::IsOldVersion();
			if ($old_module_version != "Y")
				unset($arFields["TEMPLATE"]);
			else
				unset($arFields["TEMPLATE_NEW"]);
		}

		if(!empty($aMsg))
		{
			global $APPLICATION;
			$e = new CAdminException(array_reverse($aMsg));
			$APPLICATION->ThrowException($e);
			return false;
		}
		return true;
	}

	public static function Add($arFields, $strUploadDir = false)
	{
		global $DB;
		$strUploadDir = ($strUploadDir === false ? "vote" : $strUploadDir);

		if (!CVoteQuestion::CheckFields("ADD", $arFields))
			return false;
/***************** Event onBeforeVoteQuestionAdd *******************/
		foreach (GetModuleEvents("vote", "onBeforeVoteQuestionAdd", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				return false;
/***************** /Event ******************************************/
		if (empty($arFields))
			return false;

		if (
			array_key_exists("IMAGE_ID", $arFields)
			&& is_array($arFields["IMAGE_ID"])
			&& (
				!array_key_exists("MODULE_ID", $arFields["IMAGE_ID"])
				|| strlen($arFields["IMAGE_ID"]["MODULE_ID"]) <= 0
			)
		)
			$arFields["IMAGE_ID"]["MODULE_ID"] = "vote";

		CFile::SaveForDB($arFields, "IMAGE_ID", $strUploadDir);

		if ($DB->type == "ORACLE")
			$arFields["ID"] = $DB->NextID("SQ_B_VOTE_QUESTION");

		$arInsert = $DB->PrepareInsert("b_vote_question", $arFields);

		$DB->QueryBind("INSERT INTO b_vote_question (".$arInsert[0].", TIMESTAMP_X) VALUES(".$arInsert[1].", ".$DB->GetNowFunction().")", array("QUESTION" => $arFields["QUESTION"]), false);
		$ID = intval($DB->type == "ORACLE" ? $arFields["ID"] : $DB->LastID());

/***************** Event onAfterVoteQuestionAdd ********************/
		foreach (GetModuleEvents("vote", "onAfterVoteQuestionAdd", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
/***************** /Event ******************************************/
		return $ID;
	}

	public static function Update($ID, $arFields, $strUploadDir = false)
	{
		global $DB;
		$arBinds = array();
		$err_mess = (CAllVoteQuestion::err_mess())."<br>Function: Update<br>Line: ";
		$strUploadDir = ($strUploadDir === false ? "vote" : $strUploadDir);

		$ID = intVal($ID);
		if ($ID <= 0 || !CVoteQuestion::CheckFields("UPDATE", $arFields, $ID))
			return false;
/***************** Event onBeforeVoteQuestionUpdate ****************/
		foreach (GetModuleEvents("vote", "onBeforeVoteQuestionUpdate", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array(&$ID, &$arFields)) === false)
				return false;
/***************** /Event ******************************************/
		if (empty($arFields))
			return false;

		if (
			array_key_exists("IMAGE_ID", $arFields)
			&& is_array($arFields["IMAGE_ID"])
			&& (
				!array_key_exists("MODULE_ID", $arFields["IMAGE_ID"])
				|| strlen($arFields["IMAGE_ID"]["MODULE_ID"]) <= 0
			)
		)
			$arFields["IMAGE_ID"]["MODULE_ID"] = "vote";

		CFile::SaveForDB($arFields, "IMAGE_ID", $strUploadDir);

		$arFields["~TIMESTAMP_X"] = $DB->GetNowFunction();
		$strUpdate = $DB->PrepareUpdate("b_vote_question", $arFields);
		if (is_set($arFields, "QUESTION"))
			$arBinds["QUESTION"] = $arFields["QUESTION"];

		if (!empty($strUpdate))
			$DB->QueryBind("UPDATE b_vote_question SET ".$strUpdate." WHERE ID=".$ID, $arBinds, false, $err_mess);

		unset($GLOBALS["VOTE_CACHE"]["QUESTION"][$ID]);
/***************** Event onAfterVoteQuestionUpdate *****************/
		foreach (GetModuleEvents("vote", "onAfterVoteQuestionUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
/***************** /Event ******************************************/
		return $ID;
	}

	public static function Copy($ID, $newVoteID)
	{
		$ID = intVal($ID);
		if ($ID <= 0)
			return false;
		$newVoteID = intVal($newVoteID);
		if ($newVoteID <= 0)
			return false;
		$res = CVoteQuestion::GetByID($ID);
		if (!($arQuestion = $res->Fetch()))
			return false;
		$arQuestion['VOTE_ID'] = $newVoteID;
		unset($arQuestion['ID']);
		$newQuestionID = CVoteQuestion::Add($arQuestion);
		if ($newQuestionID === false)
			return false;
		$state = true;
		$rAnswers = CVoteAnswer::GetList($ID);
		while ($arAnswer = $rAnswers->Fetch())
		{
			$arAnswer['QUESTION_ID'] = $newQuestionID;
			unset($arAnswer['ID']);
			$state = $state && (CVoteAnswer::Add($arAnswer) !== false);
		}
		if (!$state) return $state;
		CVoteQuestion::Reset($newQuestionID);

		return $newQuestionID;
	}

	public static function GetNextSort($VOTE_ID)
	{
		global $DB;
		$err_mess = (CAllVoteQuestion::err_mess())."<br>Function: GetNextSort<br>Line: ";
		$VOTE_ID = intval($VOTE_ID);
		$strSql = "SELECT max(C_SORT) as MAX_SORT FROM b_vote_question WHERE VOTE_ID='$VOTE_ID'";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		return (intval($zr["MAX_SORT"]) + 100);
	}

	public static function GetByID($ID)
	{
		$ID = intval($ID);
		$res = false;
		if ($ID <= 0):
			return false;
		endif;
		if (!is_array($GLOBALS["VOTE_CACHE"]["QUESTION"]))
			$GLOBALS["VOTE_CACHE"]["QUESTION"] = array();

		if (!array_key_exists($ID, $GLOBALS["VOTE_CACHE"]["QUESTION"]))
		{
			$db_res = CVoteQuestion::GetList(0, $by, $order, array("ID" => $ID), $is_filtered);
			if ($db_res) $res = $db_res->Fetch();
			$GLOBALS["VOTE_CACHE"]["QUESTION"][$ID] = $res;
		}
		$db_res = new CDBResult;
		$db_res->InitFromArray(array($GLOBALS["VOTE_CACHE"]["QUESTION"][$ID]));
		return $db_res;
	}

	public static function GetList($VOTE_ID, &$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		global $DB;
		$err_mess = (CAllVoteQuestion::err_mess())."<br>Function: GetList<br>Line: ";

		$VOTE_ID = intval($VOTE_ID);
		$arSqlSearch = array();
		$arFilter = (is_array($arFilter) ? $arFilter : array($arFilter));

		foreach ($arFilter as $key => $val)
		{
			if (empty($key) || empty($val) || $val === "NOT_REF"):
				continue;
			endif;
			$key_res = VoteGetFilterOperation($key);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$key = strtoupper($key_res["FIELD"]);

			switch($key)
			{
				case "ID":
					$match = ($arFilter[$key."_EXACT_MATCH"] == "N" ? "Y" : "N"); //turn off
					$arSqlSearch[] = GetFilterQuery("Q.ID", $val, $match);
					break;
				case "DIAGRAM":
				case "ACTIVE":
				case "REQUIRED":
						$arSqlSearch[] = ($strNegative=="Y"?" Q.".$key." IS NULL OR NOT ":"")." (Q.".$key." ".$strOperation." '".$DB->ForSql($val)."')";
					break;
				case "QUESTION":
					$match = ($arFilter[$key."_EXACT_MATCH"] != "N" ? "Y" : "N"); //turn on
					$arSqlSearch[] = GetFilterQuery("Q.QUESTION", $val, $match);
					break;
			}
		}
		if ($VOTE_ID > 0)
			$arSqlSearch[] = "Q.VOTE_ID = ".$VOTE_ID;

		// Order
		$by1 = strtoupper(strpos($by, "s_") === 0 ? substr($by, 2) : $by);
		$order = ($order != "desc" ? "asc" : "desc");
		$order1 = strtoupper($order);
		if (in_array($by1, array("ID", "TIMESTAMP_X", "ACTIVE", "DIAGRAM", "C_SORT", "REQUIRED"))):
			$strSqlOrder = "Q.".$by1." ".$order1;
		else:
			$by = "s_c_sort";
			$strSqlOrder = "Q.C_SORT ".$order1;
		endif;

		// Sql
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT Q.*,
				".$DB->DateToCharFunction("Q.TIMESTAMP_X","SHORT")." TIMESTAMP_X
			FROM b_vote_question Q
			WHERE ".$strSqlSearch."
			ORDER BY ".$strSqlOrder;
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}

	public static function GetListEx($arOrder = array("ID" => "ASC"), $arFilter=array())
	{
		global $DB;

		$arSqlSearch = array();
		$strSqlSearch = "";
		$arSqlOrder = array();
		$strSqlOrder = "";

		$arFilter = (is_array($arFilter) ? $arFilter : array());
		foreach ($arFilter as $key => $val)
		{
			if($val === "NOT_REF")
				continue;
			$key_res = VoteGetFilterOperation($key);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$key = strtoupper($key_res["FIELD"]);

			switch($key)
			{
				case "ID":
				case "VOTE_ID":
					$str = ($strNegative=="Y"?"NOT":"")."(VQ.".$key." IS NULL OR VQ.".$key."<=0)";
					if (!empty($val))
					{
						$str = ($strNegative=="Y"?" VQ.".$key." IS NULL OR NOT ":"")."(VQ.".$key." ".$strOperation." ".intVal($val).")";
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
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(V.".$key." IS NULL OR V.".$key."<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" V.".$key." IS NULL OR NOT ":"")."(V.".$key." ".$strOperation." ".intVal($val).")";
					break;
				case "ACTIVE":
					if (empty($val))
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(VQ.".$key." IS NULL OR ".($DB->type == "MSSQL" ? "LEN" : "LENGTH")."(VQ.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" VQ.".$key." IS NULL OR NOT ":"")."(VQ.".$key." ".$strOperation." '".$DB->ForSql($val)."')";
					break;
			}
		}
		if (count($arSqlSearch) > 0)
			$strSqlSearch = " AND (".implode(") AND (", $arSqlSearch).") ";

		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by); $order = strtoupper($order);
			$by = ($by == "ACTIVE" ? $by : "ID");
			if ($order!="ASC") $order = "DESC";
			if ($by == "ACTIVE") $arSqlOrder[] = " VQ.ACTIVE ".$order." ";
			else $arSqlOrder[] = " VQ.ID ".$order." ";
		}
		DelDuplicateSort($arSqlOrder);
		if (count($arSqlOrder) > 0)
			$strSqlOrder = " ORDER BY ".implode(", ", $arSqlOrder);

		$strSql = "
			SELECT VQ.*
			FROM
				b_vote_question VQ, b_vote V
			WHERE VQ.VOTE_ID = V.ID ".
			$strSqlSearch."
			".$strSqlOrder;
		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function Delete($ID, $VOTE_ID = false)
	{
		global $DB;
		$err_mess = (CVoteQuestion::err_mess())."<br>Function: Delete<br>Line: ";
/***************** Event onBeforeVoteQuestionDelete ****************/
		foreach (GetModuleEvents("vote", "onBeforeVoteQuestionDelete", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array(&$ID, &$VOTE_ID)) === false)
				return false;
/***************** /Event ******************************************/
		if (!CVoteAnswer::Delete(false, $ID, $VOTE_ID))
			return false;

		$ID = (intVal($ID) > 0 ? intVal($ID) : false);
		$VOTE_ID = (intVal($VOTE_ID) > 0 ? intVal($VOTE_ID) : false);
		if ($ID === false && $VOTE_ID === false):
			return false;
		elseif ($ID === false):
			$strSqlID = "SELECT Q.ID FROM b_vote_question Q WHERE Q.VOTE_ID=".$VOTE_ID;
		else:
			$strSqlID = "".$ID."";
		endif;

		$DB->StartTransaction();
		$strSql = "SELECT IMAGE_ID FROM b_vote_question WHERE ID IN (".$strSqlID.") AND IMAGE_ID > 0";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		while ($zr = $z->Fetch()) CFile::Delete($zr["IMAGE_ID"]);

		// drop question events
		if (!$DB->Query("DELETE FROM b_vote_event_question WHERE QUESTION_ID IN (".$strSqlID.")", false, $err_mess.__LINE__)):
			$DB->Rollback();
			return false;
		endif;
		// drop question
		if ($ID === false):
			$strSql = "DELETE FROM b_vote_question WHERE VOTE_ID=".$VOTE_ID;
		else:
			$strSql = "DELETE FROM b_vote_question WHERE ID=".$ID;
		endif;

		if (!$DB->Query($strSql, false, $err_mess.__LINE__)):
			$DB->Rollback();
			return false;
		endif;

		$DB->Commit();
/***************** Cleaning cache **********************************/
		if ($ID === false)
			unset($GLOBALS["VOTE_CACHE"]["QUESTION"]);
		else
			unset($GLOBALS["VOTE_CACHE"]["QUESTION"][$ID]);

/***************** Cleaning cache/**********************************/

/***************** Event onAfterForumDelete ************************/
		foreach (GetModuleEvents("vote", "onAfterVoteQuestionDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $VOTE_ID));
/***************** /Event ******************************************/
		return true;
	}

	public static function Reset($ID, $VOTE_ID = false)
	{
		global $DB;
		$err_mess = (CVoteQuestion::err_mess())."<br>Function: Reset<br>Line: ";
		$ID = (intVal($ID) > 0 ? intVal($ID) : false);
		$VOTE_ID = (intVal($VOTE_ID) > 0 ? intVal($VOTE_ID) : false);

		if ($ID > 0):
			$strSqlID = "".$ID."";
		elseif ($VOTE_ID > 0):
			$strSqlID = "SELECT Q.ID FROM b_vote_question Q WHERE Q.VOTE_ID=".$VOTE_ID;
		else:
			return false;
		endif;

		// drop answer events
		$DB->Query("DELETE FROM b_vote_event_answer WHERE EVENT_QUESTION_ID IN (
			SELECT ID FROM b_vote_event_question WHERE QUESTION_ID IN (".$strSqlID."))", false, $err_mess.__LINE__);
		// drop question events
		$DB->Query("DELETE FROM b_vote_event_question WHERE QUESTION_ID IN (".$strSqlID.")", false, $err_mess.__LINE__);

		// zeroize answers counter
		$arFields = array("COUNTER"=>"0");
		$DB->Update("b_vote_answer", $arFields, "WHERE QUESTION_ID IN (".$strSqlID.")", $err_mess.__LINE__);

		// zeroize questions counter
		$arFields = array("COUNTER" => "0");
		$DB->Update("b_vote_question", $arFields, "WHERE ".(
			$ID > 0 ? "ID = ".$ID."" : "VOTE_ID = ".$VOTE_ID.""), $err_mess.__LINE__);


/***************** Cleaning cache **********************************/
		if ($ID === false)
			unset($GLOBALS["VOTE_CACHE"]["QUESTION"]);
		else
			unset($GLOBALS["VOTE_CACHE"]["QUESTION"][$ID]);
/***************** Cleaning cache/**********************************/
		return true;
	}

	public static function setActive($ID, $activate = true)
	{
		$ID = intval($ID);
		if ($ID <= 0)
			return false;
		$activate = (!!$activate);
/***************** Event onBeforeVoteQuestionUpdate ****************/
		foreach (GetModuleEvents("vote", "onVoteQuestionActivate", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array($ID, $activate)) === false)
				return false;
/***************** /Event ******************************************/

		global $DB;
		$err_mess = (CAllVoteQuestion::err_mess())."<br>Function: activate<br>Line: ";
		$strUpdate = $DB->PrepareUpdate("b_vote_question", array("ACTIVE" => ($activate ? "Y" : "N"), "~TIMESTAMP_X" => $DB->GetNowFunction()));
		$DB->QueryBind("UPDATE b_vote_question SET ".$strUpdate." WHERE ID=".$ID, array(), false, $err_mess);
		unset($GLOBALS["VOTE_CACHE"]["QUESTION"][$ID]);

		return $ID;
	}
}
?>