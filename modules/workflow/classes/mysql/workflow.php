<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/workflow/classes/general/workflow.php");

class CWorkflow extends CAllWorkflow
{
	public static function err_mess()
	{
		return "<br>Module: workflow<br>Class: CAllWorkflow<br>File: ".__FILE__;
	}

	public static function Insert($arFields)
	{
		$err_mess = (CWorkflow::err_mess())."<br>Function: Insert<br>Line: ";
		global $DB;
		$arInsert = $DB->PrepareInsert("b_workflow_document", $arFields, "workflow");
		$DB->Query("
			INSERT INTO b_workflow_document
			(DATE_MODIFY, DATE_ENTER,  ".$arInsert[0].")
			VALUES
			(now(), now(), ".$arInsert[1].")",
		false, $err_mess.__LINE__);
		$ID = $DB->LastID();
		$LOG_ID = CWorkflow::SetHistory($ID);
		CWorkflow::SetMove($ID, $arFields["STATUS_ID"], 0, $LOG_ID);
		return $ID;
	}

	public static function Update($arFields, $DOCUMENT_ID)
	{
		$err_mess = (CWorkflow::err_mess())."<br>Function: Update<br>Line: ";
		global $DB;
		$z = CWorkflow::GetByID($DOCUMENT_ID);
		$change = false;
		if ($zr = $z->Fetch())
		{
			if (
				$zr["STATUS_ID"] != $arFields["STATUS_ID"]
				|| $zr["BODY"] != $arFields["BODY"]
				|| $zr["BODY_TYPE"] != $arFields["BODY_TYPE"]
				|| $zr["COMMENTS"] != $arFields["COMMENTS"]
				|| $zr["FILENAME"] != $arFields["FILENAME"]
				|| $zr["SITE_ID"] != $arFields["SITE_ID"]
				|| $zr["TITLE"] != $arFields["TITLE"]
			)
			{
				$change = true;
			}
		}

		$strUpdate = $DB->PrepareUpdate("b_workflow_document", $arFields, "workflow");
		if ($strUpdate)
		{
			$DB->Query("
				UPDATE b_workflow_document
				SET ".$strUpdate.", DATE_MODIFY=now(), DATE_ENTER=now()
				WHERE ID = ".$DOCUMENT_ID
			, false, $err_mess.__LINE__);
		}

		if ($change)
		{
			$LOG_ID = CWorkflow::SetHistory($DOCUMENT_ID);
			CWorkflow::SetMove($DOCUMENT_ID, $arFields["STATUS_ID"], $zr["STATUS_ID"], $LOG_ID);
		}
	}

	public static function GetLockStatus($DOCUMENT_ID, &$locked_by, &$date_lock)
	{
		$err_mess = (CWorkflow::err_mess())."<br>Function: GetLockStatus<br>Line: ";
		global $DB, $USER;
		$DOCUMENT_ID = intval($DOCUMENT_ID);
		$MAX_LOCK = intval(COption::GetOptionString("workflow","MAX_LOCK_TIME","60"));
		$uid = intval($USER->GetID());
		$strSql = "
			SELECT
				LOCKED_BY,
				".$DB->DateToCharFunction("DATE_LOCK")." DATE_LOCK,
				if (DATE_LOCK is null, 'green',
					if(DATE_ADD(DATE_LOCK,interval $MAX_LOCK MINUTE)<now(), 'green',
						if(LOCKED_BY=$uid, 'yellow', 'red'))) LOCK_STATUS
			FROM
				b_workflow_document
			WHERE
				ID = '$DOCUMENT_ID'
			";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		$locked_by = $zr["LOCKED_BY"];
		$date_lock = $zr["DATE_LOCK"];
		return $zr["LOCK_STATUS"];
	}

	public static function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = (CWorkflow::err_mess())."<br>Function: GetList<br>Line: ";
		global $DB, $USER, $APPLICATION;
		$arSqlSearch = Array();
		$strSqlSearch = "";
		$MAX_LOCK = intval(COption::GetOptionString("workflow","MAX_LOCK_TIME","60"));
		$arGroups = $USER->GetUserGroupArray();
		if (!is_array($arGroups)) $arGroups[] = 2;
		$groups = implode(",",$arGroups);
		$uid = intval($USER->GetID());
		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				if (strlen($val)<=0 || "$val"=="NOT_REF")
					continue;
				if (is_array($val) && count($val)<=0)
					continue;
				$match_value_set = (array_key_exists($key."_EXACT_MATCH", $arFilter) ? true : false);
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
						$match = ($match_value_set && $arFilter[$key."_EXACT_MATCH"]=="N") ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("D.ID",$val,$match);
						break;
					case "DATE_MODIFY_1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "D.DATE_MODIFY >= ".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_MODIFY_2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "D.DATE_MODIFY < ".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "MODIFIED_BY":
						$match = ($match_value_set && $arFilter[$key."_EXACT_MATCH"]=="Y") ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("D.MODIFIED_BY, UM.LOGIN, UM.NAME, UM.LAST_NAME", $val, $match);
						break;
					case "MODIFIED_USER_ID":
						$match = ($match_value_set && $arFilter[$key."_EXACT_MATCH"]=="N") ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("D.MODIFIED_BY",$val,$match);
						break;
					case "LOCK_STATUS":
						$arSqlSearch[] = "
						if (D.DATE_LOCK is null, 'green',
							if(DATE_ADD(D.DATE_LOCK, interval $MAX_LOCK MINUTE)<now(), 'green',
								if(D.LOCKED_BY=$uid, 'yellow', 'red'))) = '".$DB->ForSql($val)."'";
						break;
					case "STATUS":
						$match = ($match_value_set && $arFilter[$key."_EXACT_MATCH"]=="Y") ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("D.STATUS_ID, S.TITLE",$val,$match);
						break;
					case "STATUS_ID":
						$match = ($match_value_set && $arFilter[$key."_EXACT_MATCH"]=="N") ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("D.STATUS_ID",$val,$match);
						break;
					case "SITE_ID":
					case "TITLE":
					case "BODY":
						$match = ($match_value_set && $arFilter[$key."_EXACT_MATCH"]=="Y") ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("D.".$key,$val,$match);
						break;
					case "FILENAME":
						$match = ($match_value_set && $arFilter[$key."_EXACT_MATCH"]=="Y") ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("D.FILENAME",$val,$match, array("/", "\\", ".", "_"));
						break;
				}
			}
		}

		if ($by == "s_id") $strSqlOrder = "ORDER BY D.ID";
		elseif ($by == "s_lock_status") $strSqlOrder = "ORDER BY LOCK_STATUS";
		elseif ($by == "s_date_modify") $strSqlOrder = "ORDER BY D.DATE_MODIFY";
		elseif ($by == "s_modified_by") $strSqlOrder = "ORDER BY D.MODIFIED_BY";
		elseif ($by == "s_filename") $strSqlOrder = "ORDER BY D.FILENAME";
		elseif ($by == "s_title") $strSqlOrder = "ORDER BY D.TITLE";
		elseif ($by == "s_site_id") $strSqlOrder = "ORDER BY D.SITE_ID";
		elseif ($by == "s_status") $strSqlOrder = "ORDER BY D.STATUS_ID";
		else
		{
			$by = "s_date_modify";
			$strSqlOrder = "ORDER BY D.DATE_MODIFY";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		if (CWorkflow::IsAdmin())
		{
			$strSql = "
				SELECT DISTINCT
					D.*,
					".$DB->DateToCharFunction("D.DATE_ENTER")." DATE_ENTER,
					".$DB->DateToCharFunction("D.DATE_MODIFY")." DATE_MODIFY,
					".$DB->DateToCharFunction("D.DATE_LOCK")." DATE_LOCK,
					concat('(',UM.LOGIN,') ',ifnull(UM.NAME,''),' ',ifnull(UM.LAST_NAME,'')) MUSER_NAME,
					concat('(',UE.LOGIN,') ',ifnull(UE.NAME,''),' ',ifnull(UE.LAST_NAME,'')) EUSER_NAME,
					S.TITLE STATUS_TITLE,
					if (D.DATE_LOCK is null, 'green',
						if(DATE_ADD(D.DATE_LOCK, interval $MAX_LOCK MINUTE)<now(), 'green',
							if(D.LOCKED_BY=$uid, 'yellow', 'red'))) LOCK_STATUS
				FROM
					b_workflow_document D
					LEFT JOIN b_workflow_status S ON (S.ID = D.STATUS_ID)
					LEFT JOIN b_user UM ON (UM.ID = D.MODIFIED_BY)
					LEFT JOIN b_user UE ON (UE.ID = D.ENTERED_BY)
				WHERE
				$strSqlSearch
				$strSqlOrder
				";
		}
		else
		{
			$strSql = "
				SELECT DISTINCT
					D.*,
					".$DB->DateToCharFunction("D.DATE_ENTER")." DATE_ENTER,
					".$DB->DateToCharFunction("D.DATE_MODIFY")." DATE_MODIFY,
					".$DB->DateToCharFunction("D.DATE_LOCK")." DATE_LOCK,
					concat('(',UM.LOGIN,') ',ifnull(UM.NAME,''),' ',ifnull(UM.LAST_NAME,'')) MUSER_NAME,
					concat('(',UE.LOGIN,') ',ifnull(UE.NAME,''),' ',ifnull(UE.LAST_NAME,'')) EUSER_NAME,
					S.TITLE STATUS_TITLE,
					if (D.DATE_LOCK is null, 'green',
						if(DATE_ADD(D.DATE_LOCK, interval $MAX_LOCK MINUTE)<now(), 'green',
							if(D.LOCKED_BY=$uid, 'yellow', 'red'))) LOCK_STATUS
				FROM
					b_workflow_document D
					INNER JOIN b_workflow_status2group G ON (G.STATUS_ID = D.STATUS_ID)
					LEFT JOIN b_workflow_status S ON (S.ID = D.STATUS_ID)
					LEFT JOIN b_user UM ON (UM.ID = D.MODIFIED_BY)
					LEFT JOIN b_user UE ON (UE.ID = D.ENTERED_BY)
				WHERE
				$strSqlSearch
				and G.GROUP_ID in ($groups)
				and G.PERMISSION_TYPE >= '2'
				$strSqlOrder
				";
		}

		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		$arr = array();
		while($ar=$rs->Fetch())
		{
			if($USER->CanDoFileOperation('fm_edit_in_workflow', Array($ar["SITE_ID"], $ar["FILENAME"])))
				$arr[] = $ar;
		}
		$rs = new CDBResult;
		$rs->InitFromArray($arr);
		return $rs;
	}

	public static function GetByID($ID)
	{
		$err_mess = (CWorkflowStatus::err_mess())."<br>Function: GetByID<br>Line: ";
		global $DB, $USER;
		$ID = intval($ID);
		$MAX_LOCK = intval(COption::GetOptionString("workflow","MAX_LOCK_TIME","60"));
		$uid = intval($USER->GetID());
		$strSql = "
			SELECT
				D.*,
				".$DB->DateToCharFunction("D.DATE_ENTER")." DATE_ENTER,
				".$DB->DateToCharFunction("D.DATE_MODIFY")." DATE_MODIFY,
				".$DB->DateToCharFunction("D.DATE_LOCK")." DATE_LOCK,
				concat('(',UM.LOGIN,') ',ifnull(UM.NAME,''),' ',ifnull(UM.LAST_NAME,'')) MUSER_NAME,
				concat('(',UE.LOGIN,') ',ifnull(UE.NAME,''),' ',ifnull(UE.LAST_NAME,'')) EUSER_NAME,
				concat('(',UL.LOGIN,') ',ifnull(UL.NAME,''),' ',ifnull(UL.LAST_NAME,'')) LUSER_NAME,
				UE.EMAIL EUSER_EMAIL,
				S.TITLE STATUS_TITLE,
				if (D.DATE_LOCK is null, 'green',
					if(DATE_ADD(D.DATE_LOCK, interval $MAX_LOCK MINUTE)<now(), 'green',
						if(D.LOCKED_BY=$uid, 'yellow', 'red'))) LOCK_STATUS
			FROM
				b_workflow_document D
				LEFT JOIN b_user UM ON (UM.ID = D.MODIFIED_BY)
				LEFT JOIN b_user UE ON (UE.ID = D.ENTERED_BY)
				LEFT JOIN b_user UL ON (UL.ID = D.LOCKED_BY)
				LEFT JOIN b_workflow_status S ON (S.ID = D.STATUS_ID)
			WHERE
				D.ID = $ID
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	public static function GetByFilename($FILENAME, $SITE_ID, $arFilter = false)
	{
		if(!is_array($arFilter))
		{
			$arFilter = array(
				"!STATUS_ID" => 1,
			);
		}

		$obQueryWhere = new CSQLWhere;
		$obQueryWhere->SetFields(array(
			"STATUS_ID" => array(
				"TABLE_ALIAS" => "D",
				"FIELD_NAME" => "D.STATUS_ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
		));
		$strSqlWhere = $obQueryWhere->GetQuery($arFilter);

		$err_mess = (CWorkflowStatus::err_mess())."<br>Function: GetByFilename<br>Line: ";
		global $DB, $USER;
		$MAX_LOCK = intval(COption::GetOptionString("workflow","MAX_LOCK_TIME","60"));
		$uid = intval($USER->GetID());
		$strSql = "
			SELECT
				D.*,
				".$DB->DateToCharFunction("D.DATE_ENTER")." DATE_ENTER,
				".$DB->DateToCharFunction("D.DATE_MODIFY")." DATE_MODIFY,
				".$DB->DateToCharFunction("D.DATE_LOCK")." DATE_LOCK,
				concat('(',UM.LOGIN,') ',ifnull(UM.NAME,''),' ',ifnull(UM.LAST_NAME,'')) MUSER_NAME,
				concat('(',UE.LOGIN,') ',ifnull(UE.NAME,''),' ',ifnull(UE.LAST_NAME,'')) EUSER_NAME,
				concat('(',UL.LOGIN,') ',ifnull(UL.NAME,''),' ',ifnull(UL.LAST_NAME,'')) LUSER_NAME,
				S.TITLE STATUS_TITLE,
				if (D.DATE_LOCK is null, 'green',
					if(DATE_ADD(D.DATE_LOCK, interval $MAX_LOCK MINUTE)<now(), 'green',
						if(D.LOCKED_BY=$uid, 'yellow', 'red'))) LOCK_STATUS
			FROM
				b_workflow_document D
				LEFT JOIN b_user UM ON (UM.ID = D.MODIFIED_BY)
				LEFT JOIN b_user UE ON (UE.ID = D.ENTERED_BY)
				LEFT JOIN b_user UL ON (UL.ID = D.LOCKED_BY)
				LEFT JOIN b_workflow_status S ON (S.ID = D.STATUS_ID)
			WHERE
				SITE_ID = '".$DB->ForSql($SITE_ID, 2)."'
				AND D.FILENAME = '".$DB->ForSql($FILENAME, 255)."'
				".($strSqlWhere? "AND ".$strSqlWhere: "")."
		";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	public static function GetHistoryList(&$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = (CWorkflow::err_mess())."<br>Function: GetHistoryList<br>Line: ";
		global $DB;
		$arSqlSearch = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				if (strlen($val)<=0 || "$val"=="NOT_REF")
					continue;
				if (is_array($val) && count($val)<=0)
					continue;
				$match_value_set = (array_key_exists($key."_EXACT_MATCH", $arFilter)) ? true : false;
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("L.ID",$val,$match);
						break;
					case "DOCUMENT_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("L.DOCUMENT_ID",$val,$match);
						break;
					case "DATE_MODIFY_1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "L.TIMESTAMP_X >= ".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_MODIFY_2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "L.TIMESTAMP_X < ".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "MODIFIED_BY":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("L.MODIFIED_BY", $val, $match);
						break;
					case "MODIFIED_USER":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("L.MODIFIED_BY, U.LOGIN, U.NAME, U.LAST_NAME", $val, $match);
						break;
					case "TITLE":
					case "SITE_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("L.".$key,$val,$match);
						break;
					case "FILENAME":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("L.FILENAME",$val,$match, array("/", "\\", ".", "_"));
						break;
					case "BODY":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("L.BODY",$val,$match,array(),"Y");
						break;
					case "STATUS":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("L.STATUS_ID, S.TITLE",$val,$match);
						break;
					case "STATUS_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("L.STATUS_ID",$val,$match);
						break;
				}
			}
		}

		if ($by == "s_id") $strSqlOrder = "ORDER BY L.ID";
		elseif ($by == "s_document_id") $strSqlOrder = "ORDER BY L.DOCUMENT_ID";
		elseif ($by == "s_date_modify") $strSqlOrder = "ORDER BY L.TIMESTAMP_X";
		elseif ($by == "s_modified_by") $strSqlOrder = "ORDER BY L.MODIFIED_BY";
		elseif ($by == "s_filename") $strSqlOrder = "ORDER BY L.FILENAME";
		elseif ($by == "s_site_id") $strSqlOrder = "ORDER BY L.SITE_ID";
		elseif ($by == "s_title") $strSqlOrder = "ORDER BY L.TITLE";
		elseif ($by == "s_status") $strSqlOrder = "ORDER BY L.STATUS_ID";
		else
		{
			$by = "s_id";
			$strSqlOrder = "ORDER BY L.ID";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT DISTINCT
				L.*,
				".$DB->DateToCharFunction("L.TIMESTAMP_X")." TIMESTAMP_X,
				concat('(',U.LOGIN,') ',ifnull(U.NAME,''),' ',ifnull(U.LAST_NAME,'')) USER_NAME,
				S.TITLE STATUS_TITLE
			FROM
				b_workflow_log L
				LEFT JOIN b_workflow_status S ON (S.ID = L.STATUS_ID)
				LEFT JOIN b_user U ON (U.ID = L.MODIFIED_BY)
			WHERE
			$strSqlSearch
			$strSqlOrder
			";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}

	public static function GetHistoryByID($ID)
	{
		$err_mess = (CWorkflow::err_mess())."<br>Function: GetHistoryByID<br>Line: ";
		global $DB;
		$ID = intval($ID);
		$strSql = "
			SELECT DISTINCT
				L.*,
				".$DB->DateToCharFunction("L.TIMESTAMP_X")." TIMESTAMP_X,
				concat('(',U.LOGIN,') ',ifnull(U.NAME,''),' ',ifnull(U.LAST_NAME,'')) USER_NAME,
				S.TITLE STATUS_TITLE
			FROM
				b_workflow_log L
				LEFT JOIN b_workflow_status S ON (S.ID = L.STATUS_ID)
				LEFT JOIN b_user U ON (U.ID = L.MODIFIED_BY)
			WHERE
				L.ID = ".$ID."
		";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	public static function CleanUpHistory()
	{
		$err_mess = (CWorkflow::err_mess())."<br>Function: CleanUpHistory<br>Line: ";
		global $DB;
		$HISTORY_DAYS = intval(COption::GetOptionString("workflow","HISTORY_DAYS","-1"));
		if ($HISTORY_DAYS>=0)
		{
			$strSql = "
				DELETE FROM b_workflow_log
				WHERE
					to_days(now())-to_days(TIMESTAMP_X)>=$HISTORY_DAYS
				";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
		if (CModule::IncludeModule("iblock")) CIblockElement::WF_CleanUpHistory();
	}

	public static function CleanUpPublished()
	{
		$err_mess = (CWorkflow::err_mess())."<br>Function: CleanUpPublished<br>Line: ";
		global $DB;
		$DAYS_AFTER_PUBLISHING = intval(COption::GetOptionString("workflow","DAYS_AFTER_PUBLISHING","0"));
		if ($DAYS_AFTER_PUBLISHING>=0)
		{
			$strSql = "
				SELECT
					ID
				FROM
					b_workflow_document
				WHERE
					STATUS_ID = 1
				and to_days(now())-to_days(DATE_MODIFY)>=$DAYS_AFTER_PUBLISHING
				";
			$z = $DB->Query($strSql, false, $err_mess.__LINE__);
			while($zr=$z->Fetch())
			{
				CWorkflow::Delete($zr["ID"]);
			}
		}
	}

	public static function GetFileList($DOCUMENT_ID)
	{
		$err_mess = (CAllWorkflow::err_mess())."<br>Function: GetFileList<br>Line: ";
		global $DB;
		$DOCUMENT_ID = intval($DOCUMENT_ID);
		$strSql = "
			SELECT
				F.*, D.SITE_ID,
				".$DB->DateToCharFunction("F.TIMESTAMP_X")." TIMESTAMP_X,
				concat('(',U.LOGIN,') ',ifnull(U.NAME,''),' ',ifnull(U.LAST_NAME,'')) USER_NAME
			FROM
				b_workflow_document D
				INNER JOIN b_workflow_file F ON (F.DOCUMENT_ID = D.ID)
				LEFT JOIN b_user U ON (U.ID = F.MODIFIED_BY)
			WHERE
				D.ID = ".$DOCUMENT_ID."
			ORDER BY
				F.TIMESTAMP_X desc
		";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $z;
	}
}
