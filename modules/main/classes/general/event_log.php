<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

IncludeModuleLangFile(__FILE__);

class CEventLog
{
	public static function Log($SEVERITY, $AUDIT_TYPE_ID, $MODULE_ID, $ITEM_ID, $DESCRIPTION = false, $SITE_ID = false)
	{
		return CEventLog::Add(array(
			"SEVERITY" => $SEVERITY,
			"AUDIT_TYPE_ID" => $AUDIT_TYPE_ID,
			"MODULE_ID" => $MODULE_ID,
			"ITEM_ID" => $ITEM_ID,
			"DESCRIPTION" => $DESCRIPTION,
			"SITE_ID" => $SITE_ID,
		));
	}

	public static function Add($arFields)
	{
		global $USER, $DB;
		static $arSeverity = array(
			"SECURITY" => 1,
			"ERROR" => 2,
			"WARNING" => 3,
			"INFO" => 4,
			"DEBUG" => 5
		);

		$url = preg_replace("/(&?sessid=[0-9a-z]+)/", "", $_SERVER["REQUEST_URI"]);
		$SITE_ID = defined("ADMIN_SECTION") && ADMIN_SECTION==true ? false : SITE_ID;

		$arFields = array(
			"SEVERITY" => array_key_exists($arFields["SEVERITY"], $arSeverity)? $arFields["SEVERITY"]: "UNKNOWN",
			"AUDIT_TYPE_ID" => strlen($arFields["AUDIT_TYPE_ID"]) <= 0? "UNKNOWN": $arFields["AUDIT_TYPE_ID"],
			"MODULE_ID" => strlen($arFields["MODULE_ID"]) <= 0? "UNKNOWN": $arFields["MODULE_ID"],
			"ITEM_ID" => strlen($arFields["ITEM_ID"]) <= 0? "UNKNOWN": $arFields["ITEM_ID"],
			"REMOTE_ADDR" => $_SERVER["REMOTE_ADDR"],
			"USER_AGENT" => $_SERVER["HTTP_USER_AGENT"],
			"REQUEST_URI" => $url,
			"SITE_ID" => strlen($arFields["SITE_ID"]) <= 0 ? $SITE_ID : $arFields["SITE_ID"],
			"USER_ID" => is_object($USER) && ($USER->GetID() > 0)? $USER->GetID(): false,
			"GUEST_ID" => (isset($_SESSION) && array_key_exists("SESS_GUEST_ID", $_SESSION) && $_SESSION["SESS_GUEST_ID"] > 0? $_SESSION["SESS_GUEST_ID"]: false),
			"DESCRIPTION" => $arFields["DESCRIPTION"],
		);

		return $DB->Add("b_event_log", $arFields, array("DESCRIPTION"), "", false, "", array("ignore_dml"=>true));
	}

	//Agent
	public static function CleanUpAgent()
	{
		global $DB;
		$cleanup_days = COption::GetOptionInt("main", "event_log_cleanup_days", 7);
		if($cleanup_days > 0)
		{
			$arDate = localtime(time());
			$date = mktime(0, 0, 0, $arDate[4]+1, $arDate[3]-$cleanup_days, 1900+$arDate[5]);
			$DB->Query("DELETE FROM b_event_log WHERE TIMESTAMP_X <= ".$DB->CharToDateFunction(ConvertTimeStamp($date, "FULL")));
		}
		return "CEventLog::CleanUpAgent();";
	}

	public static function GetList($arOrder = Array("ID" => "DESC"), $arFilter = array(), $arNavParams = false)
	{
		global $DB;
		$err_mess = "FILE: ".__FILE__."<br>LINE: ";

		$arSqlSearch = array();
		$arSqlOrder = array();

		$arFields = array("ID", "TIMESTAMP_X", "AUDIT_TYPE_ID", "MODULE_ID", "SEVERITY", "ITEM_ID", "SITE_ID", "REMOTE_ADDR", "USER_AGENT", "REQUEST_URI", "USER_ID", "GUEST_ID");
		$arOFields = array(
			"ID" => "L.ID",
			"TIMESTAMP_X" => "L.TIMESTAMP_X",
		);

		foreach($arFilter as $key => $val)
		{
			if(is_array($val))
			{
				if(count($val) <= 0)
					continue;
			}
			elseif(strlen($val) <= 0)
			{
				continue;
			}
			$key = strtoupper($key);
			switch($key)
			{
				case "ID":
					$arSqlSearch[] = "L.ID=".IntVal($val);
					break;
				case "TIMESTAMP_X_1":
					$arSqlSearch[] = "L.TIMESTAMP_X >= ".$DB->CharToDateFunction($DB->ForSql($val), "FULL");
					break;
				case "TIMESTAMP_X_2":
					$arSqlSearch[] = "L.TIMESTAMP_X <= ".$DB->CharToDateFunction($DB->ForSql($val), "FULL");
					break;
				case "=AUDIT_TYPE_ID":
					$arValues = array();
					if(is_array($val))
					{
						foreach($val as $value)
						{
							$value = trim($value);
							if(strlen($value))
								$arValues[$value] = $DB->ForSQL($value);
						}
					}
					elseif(is_string($val))
					{
						$value = trim($val);
						if(strlen($value))
							$arValues[$value] = $DB->ForSQL($value);
					}
					if(!empty($arValues))
						$arSqlSearch[] = "L.AUDIT_TYPE_ID in ('".implode("', '", $arValues)."')";
					break;
				case "=MODULE_ITEM":
					if(is_array($val))
					{
						$arSqlSearch2 = array();
						foreach($val as $value)
						{
							$arSqlSearchTmp = array();
							foreach($value as $item2 => $value2)
							{
								if (in_array($item2, $arFields))
									$arSqlSearchTmp[] = "L.".$item2." = '".$DB->ForSQL($value2)."'";
							}
							if(count($arSqlSearchTmp) > 0)
								$arSqlSearch2[] = implode(" AND ", $arSqlSearchTmp);
						}
						if(count($arSqlSearch2) > 0)
							$arSqlSearch[] = "(".implode(" OR ", $arSqlSearch2).")";
					}
					break;
				case "SEVERITY":
				case "AUDIT_TYPE_ID":
				case "MODULE_ID":
				case "ITEM_ID":
				case "SITE_ID":
				case "REMOTE_ADDR":
				case "USER_AGENT":
				case "REQUEST_URI":
					$arSqlSearch[] = GetFilterQuery("L.".$key, $val);
					break;
				case "USER_ID":
				case "GUEST_ID":
					$arSqlSearch[] = "L.".$key." = ".intval($val)."";
					break;
			}
		}

		foreach($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order);
			if (array_key_exists($by, $arOFields))
			{
				if ($order != "ASC")
					$order = "DESC".($DB->type=="ORACLE" ? " NULLS LAST" : "");
				else
					$order = "ASC".($DB->type=="ORACLE" ? " NULLS FIRST" : "");
				$arSqlOrder[$by] = $arOFields[$by]." ".$order;
			}
		}

		$strSql = "
			FROM
				b_event_log L
		";

		if(!empty($arSqlSearch))
			$strSql .=  " WHERE ".implode(" AND ", $arSqlSearch);

		if(is_array($arNavParams))
		{
			$res_cnt = $DB->Query("SELECT count(1) C".$strSql);
			$res_cnt = $res_cnt->Fetch();
			$cnt = $res_cnt["C"];

			if(!empty($arSqlOrder))
				$strSql .=  " ORDER BY ".implode(", ", $arSqlOrder);

			$res = new CDBResult();
			$res->NavQuery("
				SELECT
					ID
					,".$DB->DateToCharFunction("L.TIMESTAMP_X")." as TIMESTAMP_X
					,SEVERITY
					,AUDIT_TYPE_ID
					,MODULE_ID
					,ITEM_ID
					,REMOTE_ADDR
					,USER_AGENT
					,REQUEST_URI
					,SITE_ID
					,USER_ID
					,GUEST_ID
					,DESCRIPTION
			".$strSql, $cnt, $arNavParams);

			return $res;
		}
		else
		{
			if(!empty($arSqlOrder))
				$strSql .=  " ORDER BY ".implode(", ", $arSqlOrder);

			return $DB->Query("SELECT L.*, ".$DB->DateToCharFunction("L.TIMESTAMP_X")." as TIMESTAMP_X".$strSql, false, $err_mess.__LINE__);
		}
	}
}

class CEventMain
{
	public static function MakeMainObject()
	{
		$obj = new CEventMain;
		return $obj;
	}

	public static function GetFilter()
	{
		$arFilter = array();
		if(COption::GetOptionString("main", "event_log_register", "N") === "Y" || COption::GetOptionString("main", "event_log_user_delete", "N") === "Y" || COption::GetOptionString("main", "event_log_user_edit", "N") === "Y" || COption::GetOptionString("main", "event_log_user_groups", "N") === "Y")
		{
			$arFilter["USERS"] = GetMessage("LOG_TYPE_USERS");
		}
		return  $arFilter;
	}

	public static function GetAuditTypes()
	{
		return array(
			"USER_REGISTER" => "[USER_REGISTER] ".GetMessage("LOG_TYPE_NEW_USERS"),
			"USER_DELETE" => "[USER_DELETE] ".GetMessage("LOG_TYPE_USER_DELETE"),
			"USER_EDIT" => "[USER_EDIT] ".GetMessage("LOG_TYPE_USER_EDIT"),
			"USER_GROUP_CHANGED" => "[USER_GROUP_CHANGED] ".GetMessage("LOG_TYPE_USER_GROUP_CHANGED"),
			"BACKUP_ERROR" => "[BACKUP_ERROR] ".GetMessage("LOG_TYPE_BACKUP_ERROR"),
			"BACKUP_SUCCESS" => "[BACKUP_SUCCESS] ".GetMessage("LOG_TYPE_BACKUP_SUCCESS"),
			"SITE_CHECKER_SUCCESS" => "[SITE_CHECKER_SUCCESS] ".GetMessage("LOG_TYPE_SITE_CHECK_SUCCESS"),
			"SITE_CHECKER_ERROR" => "[SITE_CHECKER_ERROR] ".GetMessage("LOG_TYPE_SITE_CHECK_ERROR"),
		);
	}

	public static function GetEventInfo($row, $arParams)
	{
		$DESCRIPTION = unserialize($row["DESCRIPTION"]);
		$userURL = $EventPrint = "";
		$rsUser = CUser::GetByID($row['ITEM_ID']);
		if($arUser = $rsUser->GetNext())
			$userURL = SITE_DIR.CComponentEngine::MakePathFromTemplate($arParams['USER_PATH'], array("user_id" => $row['ITEM_ID'], "SITE_ID" => ""));
		$EventName = $DESCRIPTION["user"];
		switch($row['AUDIT_TYPE_ID'])
		{
			case "USER_REGISTER":
				$EventPrint = GetMessage("LOG_USER_REGISTER");
				break;
			case "USER_DELETE":
				$EventPrint = GetMessage("LOG_USER_DELETE");
				break;
			case "USER_EDIT":
				$EventPrint = GetMessage("LOG_USER_EDIT");
				break;
			case "USER_GROUP_CHANGED":
				$EventPrint = GetMessage("LOG_USER_GROUP_CHANGED");
				break;
		}

		return array(
			"eventType" => $EventPrint,
			"eventName" => $EventName,
			"eventURL" => $userURL,
		);
	}

	public static function GetFilterSQL($var)
	{
		$ar[] = array("AUDIT_TYPE_ID" => "USER_REGISTER");
		$ar[] = array("AUDIT_TYPE_ID" => "USER_DELETE");
		$ar[] = array("AUDIT_TYPE_ID" => "USER_EDIT");
		$ar[] = array("AUDIT_TYPE_ID" => "USER_GROUP_CHANGED");
		return $ar;
	}
}
