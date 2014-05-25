<?php
IncludeModuleLangFile(__FILE__);

class CSupportTimetable
{

	static $fieldsTypes = array(
		"ID" =>					array("TYPE" => CSupportTableFields::VT_NUMBER,	"DEF_VAL" => 0,		"AUTO_CALCULATED" => true),
		"NAME" =>				array("TYPE" => CSupportTableFields::VT_STRING,	"DEF_VAL" => "", 	"MAX_STR_LEN" => 255),
		"DESCRIPTION" =>		array("TYPE" => CSupportTableFields::VT_STRING,	"DEF_VAL" => "", 	"MAX_STR_LEN" => 2000),
	);
	static $fieldsTypesShedule = array(
		"ID" =>					array("TYPE" => CSupportTableFields::VT_NUMBER,	"DEF_VAL" => 0,		"AUTO_CALCULATED" => true),
		"SLA_ID" =>				array("TYPE" => CSupportTableFields::VT_NUMBER,	"DEF_VAL" => 0),
		"TIMETABLE_ID" =>		array("TYPE" => CSupportTableFields::VT_NUMBER,	"DEF_VAL" => null),
		"WEEKDAY_NUMBER" =>		array("TYPE" => CSupportTableFields::VT_NUMBER,	"DEF_VAL" => 0),
		"OPEN_TIME" =>			array("TYPE" => CSupportTableFields::VT_STRING,	"DEF_VAL" => "24H", "LIST" => array("24H", "CLOSED", "CUSTOM")),
		"MINUTE_FROM" =>		array("TYPE" => CSupportTableFields::VT_NUMBER,	"DEF_VAL" => null),
		"MINUTE_TILL" =>		array("TYPE" => CSupportTableFields::VT_NUMBER,	"DEF_VAL" => null),
		
	);
	const TABLE = "b_ticket_timetable";
	const TABLE_SHEDULE = "b_ticket_sla_shedule";
	
	
	static function err_mess()
	{
		$module_id = "support";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/" . $module_id . "/install/version.php");
		return "<br>Module: " . $module_id . " <br>Class: CSupportTimetable<br>File: " . __FILE__;
	}
	
	public static function Set($arFields, $arFieldsShedule) //$arFields, $arFieldsShedule = array(0 => array("ID" => 1 ...), 1 => array("ID" => 3 ...) ...)
	{
		global $DB, $APPLICATION;
		$err_mess = (self::err_mess())."<br>Function: Set<br>Line: ";
		$isDemo = null;
		$isSupportClient = null;
		$isSupportTeam = null;
		$isAdmin = null;
		$isAccess = null;
		$userID = null;
		CTicket::GetRoles($isDemo, $isSupportClient, $isSupportTeam, $isAdmin, $isAccess, $userID);
		if(!$isAdmin)
		{
			$arMsg = Array();
			$arMsg[] = array("id"=>"PERMISSION", "text"=> GetMessage("SUP_ERROR_ACCESS_DENIED"));
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		
		if(is_array($arFields))
		{
			$f = new CSupportTableFields(self::$fieldsTypes);
			$f->FromArray($arFields);
		}
		else $f = $arFields;
		if(is_array($arFieldsShedule))
		{
			$f_s = new CSupportTableFields(self::$fieldsTypesShedule, CSupportTableFields::C_Table);
			$f_s->FromTable($arFieldsShedule);
		}
		else $f_s = $arFieldsShedule;
				
		$table = self::TABLE;
		$table_shedule = self::TABLE_SHEDULE; 
		
		$id = $f->ID;
		$isNew = ($f->ID <= 0);
		
		if(strlen($f->NAME) <= 0)
		{
				$APPLICATION->ThrowException(GetMessage('SUP_ERROR_EMPTY_NAME'));
				return false;
		}
		
		$arFields_i = $f->ToArray(CSupportTableFields::ALL, array(CSupportTableFields::NOT_NULL,CSupportTableFields::NOT_DEFAULT), true);
		$res = 0;
		if(count($arFields_i) > 0)
		{
			if($isNew)
			{
				$res = $DB->Insert($table, $arFields_i, $err_mess . __LINE__);
			}
			else
			{
				$res = $DB->Update($table, $arFields_i, "WHERE ID=" . $id . "", $err_mess . __LINE__);
			}
		}
		
		if(intval($res) <= 0)
		{
			$APPLICATION->ThrowException(GetMessage('SUP_ERROR_DB_ERROR'));
			return false;
		}
		if($isNew)
		{
			$id = $res;
		}
		
		$DB->Query("DELETE FROM $table_shedule WHERE TIMETABLE_ID = $id", false, $err_mess . __LINE__);
		$noWrite = array();
		$f_s->ResetNext();
		while($f_s->Next())
		{
			$f_s->TIMETABLE_ID = $id;
			if (isset($noWrite[$f_s->WEEKDAY_NUMBER]) && ($noWrite[$f_s->WEEKDAY_NUMBER] != "CUSTOM" || $f_s->OPEN_TIME != "CUSTOM") )
			{
				continue;
			}
			if($f_s->OPEN_TIME == "CUSTOM" && $f_s->MINUTE_FROM <= 0 && $f_s->MINUTE_TILL <= 0)
			{
				continue;
			}
			$DB->Insert($table_shedule, $f_s->ToArray(CSupportTableFields::ALL, array(CSupportTableFields::NOT_NULL), true), $err_mess . __LINE__);
			$noWrite[$f_s->WEEKDAY_NUMBER] = $f_s->OPEN_TIME;
		}
		for($i = 0; $i <= 6; $i++) 
		{
			$a = array(
				"SLA_ID" => 0,
				"TIMETABLE_ID" =>  intval($id),
				"WEEKDAY_NUMBER" => intval($i),
				"OPEN_TIME" => "'CLOSED'",
				"MINUTE_FROM" => null,
				"MINUTE_TILL" => null
			);
			if (!isset($noWrite[$i]))
			{
				$DB->Insert($table_shedule, $a, $err_mess . __LINE__);
			}
		}

		// recalculate only affected sla
		$affected_sla = array();

		$res = $DB->Query("SELECT ID FROM b_ticket_sla WHERE TIMETABLE_ID = $id");
		while ($row = $res->Fetch())
		{
			$affected_sla[] = $row['ID'];
		}

		CSupportTimetableCache::toCache(array('SLA_ID' => $affected_sla));

		return $id;
	}

	// get Timetable list
	public static function GetList($arSort = null, $arFilter = null)
	{
		$err_mess = (self::err_mess())."<br>Function: GetList<br>Line: ";
		global $DB, $USER, $APPLICATION;
		$table = self::TABLE;
		$arSqlSearch = Array();
		if(!is_array($arFilter))
		{
			$arFilter = Array();
		}
		foreach($arFilter as $key => $val)
		{
			if((is_array($val) && count($val) <= 0) || (!is_array($val) && (strlen($val) <= 0 || $val === 'NOT_REF')))
			{
				continue;
			}
			$key = strtoupper($key);
			if (is_array($val))
			{
				$val = implode(" | ",$val);
			}
			switch($key)
			{
				case "ID":
					$arSqlSearch[] = GetFilterQuery("T.ID", $val, "N");
					break;
				case "~NAME":
				//case "DESCRIPTION":
					$arSqlSearch[] = GetFilterQuery("T.NAME", $val, "N");
					break;
			}
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);

		$arSort = is_array($arSort) ? $arSort : array();
		if(isset($arSort["DESCRIPTION"]))
		{
			unset($arSort["DESCRIPTION"]);
		}
		if(count($arSort) > 0)
		{
			$ar1 = array_merge($DB->GetTableFieldsList($table), array());
			$ar2 = array_keys($arSort);
			$arDiff = array_diff($ar2, $ar1);
			if(is_array($arDiff) && count($arDiff) > 0) foreach($arDiff as $value) unset($arSort[$value]);
		}
		if(count($arSort) <= 0)
		{
			$arSort = array("ID" => "asc");
		}
		foreach($arSort as $by => $order)
		{
			if(strtoupper($order) != "DESC")
			{
				$order="ASC";
			}
			$arSqlOrder[] = $by . " " . $order;
		}
		if(is_array($arSqlOrder) && count($arSqlOrder) > 0)
		{
			$strSqlOrder = " ORDER BY " . implode(",", $arSqlOrder);
		}
		
		$strSql = "
			SELECT
				T.*
			FROM
				$table T
			WHERE
			$strSqlSearch
			$strSqlOrder
			";
		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $rs;
	}
	
	public static function GetSheduleByID($id, $needObj = false)
	{
		global $DB;
		$err_mess = (self::err_mess())."<br>Function: Set<br>Line: ";
		$tableShedule = self::TABLE_SHEDULE;
		$id = intval($id);
		
		$strSql = "
			SELECT
				T.*
			FROM
				$tableShedule T
			WHERE
				T.TIMETABLE_ID = $id
			";
		$res = $DB->Query($strSql, false, $err_mess . __LINE__);
		if(!$needObj)
		{
			return $res;
		}
		$f_s = new CSupportTableFields(self::$fieldsTypesShedule, CSupportTableFields::C_Table);
		$f_s->RemoveExistingRows();
		while ($resR = $res->Fetch()) 
		{
			$f_s->AddRow();
			$f_s->FromArray($resR);
		}
		return $f_s;
	}
	
	// delete Timetable
	public static function Delete($id, $checkRights=true)
	{
		$err_mess = (self::err_mess())."<br>Function: Delete<br>Line: ";
		global $DB, $USER, $APPLICATION;
		$id = intval($id);
		$table = self::TABLE;
		$tableShedule = self::TABLE_SHEDULE;
		
		if($id <= 0)
		{
			return false;
		}
		
		$isDemo = null;
		$isSupportClient = null;
		$isSupportTeam = null;
		$isAdmin = null;
		$isAccess = null;
		$userID = null;
		CTicket::GetRoles($isDemo, $isSupportClient, $isSupportTeam, $isAdmin, $isAccess, $userID, $checkRights);

		if(!$isAdmin)
		{
			$arMsg = Array();
			$arMsg[] = array("id"=>"PERMISSION", "text"=> GetMessage("SUP_ERROR_ACCESS_DENIED"));
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		
		
		$strSql = "SELECT DISTINCT 'x' FROM b_ticket_sla WHERE TIMETABLE_ID = $id";
		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		if (!$rs->Fetch())
		{
				$DB->Query("DELETE FROM $table WHERE ID = $id", false, $err_mess . __LINE__);
				$DB->Query("DELETE FROM $tableShedule WHERE TIMETABLE_ID = $id", false, $err_mess . __LINE__);
				return true;
		}
		else
			$APPLICATION->ThrowException(str_replace("#ID#", "$id", GetMessage("SUP_ERROR_TIMETABLE_HAS_SLA")));
		
		return false;
	}


}
?>