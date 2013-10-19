<?php
IncludeModuleLangFile(__FILE__);

class CSupportHolidays
{
	static $holidays = array(
		"ID" =>				array("TYPE" => CSupportTableFields::VT_NUMBER,	"DEF_VAL" => 0,		"AUTO_CALCULATED" => true),
		"NAME" =>			array("TYPE" => CSupportTableFields::VT_STRING,	"DEF_VAL" => "", 	"MAX_STR_LEN" => 255),
		"DESCRIPTION" =>	array("TYPE" => CSupportTableFields::VT_STRING,	"DEF_VAL" => "", 	"MAX_STR_LEN" => 2000),
		"OPEN_TIME" =>		array("TYPE" => CSupportTableFields::VT_STRING,	"DEF_VAL" => "HOLIDAY", "LIST" => array("HOLIDAY_H", "HOLIDAY", "WORKDAY_H", "WORKDAY_0", "WORKDAY_1", "WORKDAY_2", "WORKDAY_3", "WORKDAY_4", "WORKDAY_5", "WORKDAY_6")),
		"DATE_FROM" =>		array("TYPE" => CSupportTableFields::VT_DATE_TIME,	"DEF_VAL" => null),
		"DATE_TILL" =>		array("TYPE" => CSupportTableFields::VT_DATE_TIME,	"DEF_VAL" => null),
		
	);
	
	static $sla2holidays = array(
		"SLA_ID" =>					array("TYPE" => CSupportTableFields::VT_NUMBER,	"DEF_VAL" => 0),
		"HOLIDAYS_ID" =>			array("TYPE" => CSupportTableFields::VT_NUMBER,	"DEF_VAL" => 0),
	);
	const table = "b_ticket_holidays";
	const table_s2h = "b_ticket_sla_2_holidays";
	const table_sla = "b_ticket_sla";
	
	
	static function err_mess()
	{
		$module_id = "support";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/" . $module_id . "/install/version.php");
		return "<br>Module: " . $module_id . " <br>Class: CSupportHolidays<br>File: " . __FILE__;
	}
	
	public static function Set($arFields, $arFieldsSLA) //$arFields, $arFieldsSLA = array(0 => array("HOLIDAYS_ID" => 1, "SLA_ID" => 1), 1 => array("HOLIDAYS_ID" => 2, "SLA_ID" => 2) ...)
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
			$f = new CSupportTableFields(self::$holidays);
			$f->FromArray($arFields);
		}
		else $f = $arFields;
		if(is_array($arFieldsSLA))
		{
			$f_s = new CSupportTableFields(self::$sla2holidays, CSupportTableFields::C_Table);
			$f_s->FromTable($arFieldsSLA);
		}
		else $f_s = $arFieldsSLA;
		
				
		$table = self::table;
		$table_s2h = self::table_s2h; 
		
		$isNew = ($f->ID <= 0);

		$objError = new CAdminException(array());
		if(strlen($f->NAME) <= 0)
		{
			$objError->AddMessage(array("text" => GetMessage('SUP_ERROR_EMPTY_NAME')));
		}
		if(strlen($f->OPEN_TIME) <= 0)
		{
			$objError->AddMessage(array("text" => GetMessage('SUP_ERROR_EMPTY_OPEN_TIME')));
		}
		$zd = mktime(0, 0, 0, 1, 1, 2010);
		if($f->DATE_FROM < $zd || $f->DATE_FROM === null || $f->DATE_TILL < $zd || $f->DATE_TILL === null || $f->DATE_FROM > $f->DATE_TILL)
		{
			if($f->DATE_FROM < $zd || $f->DATE_FROM === null)
			{
				$f->DATE_FROM = time() + CTimeZone::GetOffset();
			}

			if($f->DATE_TILL < $zd || $f->DATE_TILL === null)
			{
				$f->DATE_TILL = time() + CTimeZone::GetOffset();
			}
			$objError->AddMessage(array("text" => GetMessage('SUP_ERROR_EMPTY_DATE')));
		}

		if(count($objError->GetMessages())>0)
		{
			$APPLICATION->ThrowException($objError);
			return false;
		}
		
		$arFields_i = $f->ToArray(CSupportTableFields::ALL, array(CSupportTableFields::NOT_NULL), true);
		$res = 0;
		if(count($arFields_i) > 0)
		{
			if($isNew)
			{
				$res = $DB->Insert($table, $arFields_i, $err_mess . __LINE__);
				$f->ID = $res;
			}
			else
			{
				$res = $DB->Update($table, $arFields_i, "WHERE ID=" . $f->ID . "", $err_mess . __LINE__);
			}
		}
		
		if(intval($res) <= 0)
		{
			$APPLICATION->ThrowException(GetMessage('SUP_ERROR_DB_ERROR'));
			return false;
		}
		
		$DB->Query("DELETE FROM $table_s2h WHERE HOLIDAYS_ID = " . $f->ID, false, $err_mess . __LINE__);
		$f_s->ResetNext();
		while($f_s->Next())
		{
			$f_s->HOLIDAYS_ID = $f->ID;
			if($f_s->SLA_ID > 0)
			{
				$strSql = "INSERT INTO " . $table_s2h . "(SLA_ID, HOLIDAYS_ID) VALUES (" . $f_s->SLA_ID . ", " . $f_s->HOLIDAYS_ID . ")";
				$res = $DB->Query($strSql, false, $err_mess . __LINE__);
			}
		}
		
		CSupportTimetableCache::toCache(array("SLA_ID"=> $f_s->getColumn("SLA_ID")));
		
		return $f->ID;
	}

	// get Holidays list
	public static function GetList($arSort, $arFilter)
	{
	
		$err_mess = (self::err_mess())."<br>Function: GetList<br>Line: ";
		global $DB, $USER, $APPLICATION;
		$filter_keys = array_keys($arFilter);
		$table = self::table;
		$table_s2h = self::table_s2h;
		$arSqlSearch = Array();
		if(!is_array($arFilter)) $arFilter = Array();
		foreach($arFilter as $key => $val)
		{
			if((is_array($val) && count($val) <= 0) || (!is_array($val) && (strlen($val) <= 0 || $val === 'NOT_REF'))) continue;
			$key = strtoupper($key);
			if (is_array($val)) $val = implode(" | ",$val);
			switch($key)
			{
				case "ID":
					$arSqlSearch[] = GetFilterQuery("H.ID", $val, "N");
					break;
				case "~NAME":
					$arSqlSearch[] = GetFilterQuery("H.NAME", $val, "N");
					break;
				case "OPEN_TIME":
					$arSqlSearch[] = GetFilterQuery("H.OPEN_TIME", $val, "N");
					break;
				case "SLA_ID":
					$arSqlSearch[] = "H.ID IN (
						SELECT 
							S2H.HOLIDAYS_ID
						FROM
							$table_s2h S2H
						WHERE
							" . GetFilterQuery("S2H.SLA_ID", $val, "N") . ")";	
					break;
				case "PERIOD":
					if(is_array($val) && isset($val["FROM"]) && intval($val["FROM"]) > 0 && isset($val["TILL"]) && intval($val["TILL"]) > 0)
					{
						$arSqlSearch[] = "H.DATE_FROM <= " . $DB->CharToDateFunction(GetTime($val["TILL"], "FULL")) . " AND H.DATE_TILL >= " . $DB->CharToDateFunction(GetTime($val["FROM"], "FULL"));
					}
					break;
					
			}
		}
		
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);

		$arSort = is_array($arSort) ? $arSort : array();
		if(count($arSort) > 0)
		{
			$ar1 = array_merge($DB->GetTableFieldsList($table), array());
			$ar2 = array_keys($arSort);
			$arDiff = array_diff($ar2, $ar1);
			if(is_array($arDiff) && count($arDiff) > 0) foreach($arDiff as $value) unset($arSort[$value]);
		}
		if(count($arSort) <= 0) $arSort = array("ID" => "ASC");
		$fs = "";
		foreach($arSort as $by => $order) 
		{
			if(strtoupper($order) != "DESC") $order="ASC";
			if($by === "DATE_TILL" || $by === "DATE_FROM")
			{
				$fs .= ",
				" . $by . " " . $by . "_SORT";
				$arSqlOrder[] = $by . "_SORT " . $order;
			}
			else
			{
				$arSqlOrder[] = $by . " " . $order;
			}
			
		}
		if(is_array($arSqlOrder) && count($arSqlOrder) > 0) $strSqlOrder = " ORDER BY " . implode(",", $arSqlOrder);

		$strSql = "
			SELECT
				H.ID,
				H.NAME,
				H.DESCRIPTION,
				H.OPEN_TIME,
				" . $DB->DateToCharFunction("H.DATE_FROM", "FULL") . " DATE_FROM,
				" . $DB->DateToCharFunction("H.DATE_TILL", "FULL") . " DATE_TILL" . $fs . "
			FROM
				$table H
			WHERE
			$strSqlSearch
			$strSqlOrder
		";
		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $rs;
	}
	
	// get Holidays list
	public static function GetSLAByID($id, $needObj = false)
	{
		$err_mess = (self::err_mess())."<br>Function: GetList<br>Line: ";
		global $DB, $USER, $APPLICATION;		
		$table_s2h = self::table_s2h;
		$table_sla = self::table_sla;
		$id = intval($id);
	
		$strSql = "
			SELECT
				S2H.HOLIDAYS_ID,
				S2H.SLA_ID,
				SLA.NAME
			FROM
				$table_s2h S2H
				INNER JOIN $table_sla SLA
					ON S2H.SLA_ID = SLA.ID
						AND S2H.HOLIDAYS_ID = $id
			ORDER BY
				SLA.NAME
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		if(!$needObj) return $res;
		$f_s = new CSupportTableFields(self::$sla2holidays, CSupportTableFields::C_Table);
		$f_s->RemoveExistingRows();
		while ($resR = $res->Fetch()) 
		{
			$f_s->AddRow();
			$f_s->FromArray($resR);
		}
		return $f_s;
	}
	
	public static function GetOpenTimeArray()
	{
		return array(
			"GB_1" => "SUP_OPEN_TIME_HOLIDAY_G",
			"HOLIDAY_H"	=> "SUP_OPEN_TIME_HOLIDAY_H",
			"HOLIDAY"	=> "SUP_OPEN_TIME_HOLIDAY",
			"GE_1" => "",
			"GB_2" => "SUP_OPEN_TIME_WORKDAY_G",
			"WORKDAY_H"	=> "SUP_OPEN_TIME_WORKDAY_H",
			"WORKDAY_0"	=> "SUP_OPEN_TIME_WORKDAY_0",
			"WORKDAY_1"	=> "SUP_OPEN_TIME_WORKDAY_1",
			"WORKDAY_2"	=> "SUP_OPEN_TIME_WORKDAY_2",
			"WORKDAY_3"	=> "SUP_OPEN_TIME_WORKDAY_3",
			"WORKDAY_4"	=> "SUP_OPEN_TIME_WORKDAY_4",
			"WORKDAY_5"	=> "SUP_OPEN_TIME_WORKDAY_5",
			"WORKDAY_6"	=> "SUP_OPEN_TIME_WORKDAY_6",
			"GE_2" => "",
		);

	}
	
	public static function GetOpenTimeT($v)
	{
		$arr = self::GetOpenTimeArray();
		return (isset($arr[$v]) ? $arr[$v] : "");
	}
	
	// delete Holiday
	public static function Delete($id, $checkRights=true)
	{
		$err_mess = (self::err_mess())."<br>Function: Delete<br>Line: ";
		global $DB, $USER, $APPLICATION;
		$id = intval($id);
		$table = self::table;
		$table_s2h = self::table_s2h;
		
		if ($id <= 0) return false;
		
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

		// get affected sla
		$affected_sla = array();

		$res = $DB->Query("SELECT SLA_ID FROM b_ticket_sla_2_holidays WHERE HOLIDAYS_ID = $id");
		while ($row = $res->Fetch())
		{
			$affected_sla[] = $row['SLA_ID'];
		}

		// delete
		$DB->Query("DELETE FROM $table WHERE ID = $id", false, $err_mess . __LINE__);
		$DB->Query("DELETE FROM $table_s2h WHERE HOLIDAYS_ID = $id", false, $err_mess . __LINE__);

		// recalculate only affected sla
		CSupportTimetableCache::toCache(array("SLA_ID" => $affected_sla));

		return true;
	}
	
}
?>