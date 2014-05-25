<?php
IncludeModuleLangFile(__FILE__);

class CSupportTimetableCache
{
	static $cache = array(
		"ID" =>				array("TYPE" => CSupportTableFields::VT_NUMBER,	"DEF_VAL" => 0,		"AUTO_CALCULATED" => true),
		"SLA_ID" =>			array("TYPE" => CSupportTableFields::VT_NUMBER,	"DEF_VAL" => 0),
		"DATE_FROM" =>		array("TYPE" => CSupportTableFields::VT_DATE_TIME,	"DEF_VAL" => null),
		"DATE_TILL" =>		array("TYPE" => CSupportTableFields::VT_DATE_TIME,	"DEF_VAL" => null),
		"W_TIME" =>			array("TYPE" => CSupportTableFields::VT_NUMBER,	"DEF_VAL" => 0),
		"W_TIME_INC" =>		array("TYPE" => CSupportTableFields::VT_NUMBER,	"DEF_VAL" => 0),
	);
	
	const TIMETABLE_CACHE = "b_ticket_timetable_cache";
	const SLA = " b_ticket_sla";
	const SLA_SHEDULE = "b_ticket_sla_shedule";
	const TICKET_HOLIDAYS = "b_ticket_holidays";
	const SLA_2_HOLIDAYS = "b_ticket_sla_2_holidays";
			
	static $arrH = null;
	static $arrS = null;
	static $timeZone = null;
	static $timeZoneOffset = null;
	static $MaxSlaResponseTime = null;
	
	public static function Possible($d = "")
	{
		if(!class_exists('DateTime'))
		{
			return false;
		}
		try
		{
			if(strlen($d) > 0)
			{
				$res = new DateTime($d);
			}
			else
			{
				$res = new DateTime(null, new DateTimeZone(date_default_timezone_get()));
			}
		}
		catch(Exception $e)
		{
			return false;
		}
		return true;
	}
	
	static function GetTimeZone()
	{		
		if(self::$timeZone === null)
		{
			if(self::Possible())
			{
				self::$timeZone = new DateTimeZone(date_default_timezone_get());
				$serverZone = COption::GetOptionString("main", "default_time_zone", "");
				if($serverZone != "") self::$timeZone = new DateTimeZone($serverZone);
			}
		}
		return self::$timeZone;		
	}
	
	static function GetTimeZoneOffset()
	{
		if(self::$timeZoneOffset === null)
		{
			if(self::Possible())
			{
				$localTime = new DateTime();
				$localOffset = $localTime->getOffset();
				$serverTime = new DateTime(null, self::GetTimeZone());
				self::$timeZoneOffset = $serverTime->getOffset() - $localOffset;
			}
			else 
			{
				self::$timeZoneOffset = 0;
			}
		}
		return self::$timeZoneOffset;
	}
	
	static function TimeStampInCurrTimeZone($d, $fromUserTZ = false)
	{
		return MakeTimeStamp($d) + self::GetTimeZoneOffset() - ($fromUserTZ ? CTimeZone::GetOffset() : 0);
	}
		
	static function err_mess()
	{
		$moduleID = "support";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/" . $moduleID . "/install/version.php");
		return "<br>Module: " . $moduleID . " <br>Class: CSupportTimetableCache<br>File: " . __FILE__;
	}

	static function GetMaxSlaResponseTime()
	{
		global $DB;

		if(self::$MaxSlaResponseTime != null)
		{
			return self::$MaxSlaResponseTime;
		}

		$err_mess = (self::err_mess())."<br>Function: GetMaxSlaResponseTime<br>Line: ";
		$tabNameSLA = self::SLA;
		$strSql = "
			SELECT
				RESPONSE_TIME,
				RESPONSE_TIME_UNIT
			FROM
				$tabNameSLA
			";
		$q = $DB->Query($strSql, false, $err_mess.__LINE__);

		self::$MaxSlaResponseTime = 0;
		while ($arrR = $q->Fetch())
		{
			$ct = CTicketReminder::ConvertResponseTimeUnit($arrR["RESPONSE_TIME"], $arrR["RESPONSE_TIME_UNIT"]);
			if(self::$MaxSlaResponseTime < $ct)
			{
				self::$MaxSlaResponseTime = $ct;
			}
		}
		return self::$MaxSlaResponseTime;
	}
	
	static function GetNumberOfDaysForward()
	{
		$supportCacheDaysBackward = intval(COption::GetOptionString("support", "SUPPORT_CACHE_DAYS_BACKWARD", 0));
		$slaDays = ceil(self::GetMaxSlaResponseTime() / 1440 * 5);
		return max($supportCacheDaysBackward, $slaDays);
	}
	
	static function GetNumberOfDaysBackward()
	{
		$supportCacheDaysForward = intval(COption::GetOptionString("support", "SUPPORT_CACHE_DAYS_FORWARD", 0));
		$slaDays = ceil(self::GetMaxSlaResponseTime() / 1440 * 5 + 20);
		return max($supportCacheDaysForward, $slaDays);
	}
	
	static function GetDayBegin($d)
	{
		if(self::Possible("@" . $d))
		{
			$localTime =  new DateTime("@" . $d); 
			$localTime->setTimezone(self::GetTimeZone());
			$localTime->setTime(0, 0, 0);
			return intval($localTime->format('U'));
		}
		else
		{
			return mktime(0, 0, 0, date("m", $d)  , date("d", $d), date("Y", $d));
		}
	}
	
	static function GetDayEnd($d)
	{
		if(self::Possible("@" . $d))
		{
			$localTime =  new DateTime("@" . $d); 
			$localTime->setTimezone(self::GetTimeZone());
			$localTime->setTime(23, 59, 59);
			return intval($localTime->format('U'));
		}
		else
		{
			return mktime(23, 59, 59, date("m", $d)  , date("d", $d), date("Y", $d));
		}
	}
	
	static function GetDayNom($d)
	{
		if(self::Possible())
		{
			$localTime = new DateTime(null, self::GetTimeZone());
			$localTime->setTimestamp($d);
			$dayNom = intval($localTime->format("w")) - 1;
		}
		else
		{
			$dayNom = date("w", $d) - 1;
		}
		if($dayNom < 0)
		{
			$dayNom = 6;
		}
		return $dayNom;
	}
	
	static function GetHolidays($dateB, $dateE, $arrS, $arFilter)
	{
		global $DB;
		$err_mess = (self::err_mess())."<br>Function: getHolidays<br>Line: ";
		$res = array();
		$tabNameHolidays = self::TICKET_HOLIDAYS;
		$tabNameS2H = self::SLA_2_HOLIDAYS;
		
		$arSqlSearch = Array();
		foreach($arFilter as $key => $val)
		{
		
			if((is_array($val) && count($val) <= 0) || (!is_array($val) && strlen($val) <= 0)) 
			{
				continue;
			}
			$key = strtoupper($key);
			if(is_array($val))
			{
				$val = implode(" | ", $val);
			}
			switch($key)
			{
				case "SLA_ID":
					$arSqlSearch[] = GetFilterQuery("HS.SLA_ID", $val, "N");	
					break;
			}
			
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		
		CTimeZone::Disable();
		$strSql = "
			SELECT
				HS.SLA_ID,
				H.OPEN_TIME,
				" . $DB->DateToCharFunction("H.DATE_FROM", "FULL") . " DATE_FROM,
				" . $DB->DateToCharFunction("H.DATE_TILL", "FULL") . " DATE_TILL
				
			FROM
				$tabNameHolidays H
				INNER JOIN $tabNameS2H HS
					ON H.ID = HS.HOLIDAYS_ID AND HS.HOLIDAYS_ID > 0
			WHERE
				H.DATE_FROM > " . $DB->CharToDateFunction(GetTime($dateB, "FULL")) . "
				AND H.DATE_FROM < " . $DB->CharToDateFunction(GetTime($dateE, "FULL")) . "
				AND $strSqlSearch
			ORDER BY
				SLA_ID,DATE_FROM
			";
		$q = $DB->Query($strSql, false, $err_mess.__LINE__);
		CTimeZone::Enable();
		
		$res0 = array();
		$oldSLA = -1;
		$goodSLA = array_keys($arrS);
		while ($arrR = $q->Fetch()) 
		{
			if(!CSupportTools::array_keys_exists("SLA_ID,OPEN_TIME,DATE_FROM,DATE_TILL", $arrR) || !in_array($arrR["SLA_ID"], $goodSLA))
			{
				continue;
			}
			$cSLA = $arrR["SLA_ID"];
			if($oldSLA != $cSLA) 
			{
				if(count($res0) > 0) $res[$oldSLA] = self::MergeIntervalsH($res0, $arrS[$oldSLA]);
				$res0 = array();
				$oldSLA = $cSLA;
			}
			$cOT = $arrR["OPEN_TIME"];
			$dtB = MakeTimeStamp($arrR["DATE_FROM"]);
			$dtE = MakeTimeStamp($arrR["DATE_TILL"]);
			$dtC = self::GetDayBegin($dtB);
			while($dtC <= $dtE && $dtC <= $dateE)
			{
				$dtCB = self::GetDayBegin($dtC);
				$dtCE = self::GetDayEnd($dtC);
				
				if(substr_count($cOT, "WORKDAY_") > 0)
				{
					$WN = str_replace("WORKDAY_", "" , $cOT);
					if($WN == "H") 
					{
						$res0[$dtC]["W"][] = array("F" => (max($dtB, $dtCB) - $dtCB), "T" => (min($dtE, $dtCE) - $dtCB));
					}
					elseif(isset($arrS[$cSLA][$WN]))
					{
						if(count($arrS[$cSLA][$WN]) > 0)
						{
							foreach($arrS[$cSLA][$WN] as $k => $v)
							{
								$res0[$dtC]["W"][] = $v;
							}
						}
						else
						{
							$res0[$dtC]["C"] = true;
						}

					}
				}
				else
				{
					if($cOT == "HOLIDAY_H")
					{
						$res0[$dtC]["H"][] = array("F" => (max($dtB, $dtCB) - $dtCB), "T" => (min($dtE, $dtCE) - $dtCB));
					}
					elseif($cOT == "HOLIDAY")
					{
						$res0[$dtC]["C"] = true;
					}
				}
				$dtC += 24*60*60;
			}
			
		}
		if(count($res0) > 0)
		{
			$res[$oldSLA] = self::MergeIntervalsH($res0, $arrS[$oldSLA]);
		}
				
		return $res;
	}
	
	public static function SortMethodH($a, $b)
	{
		if($a["F"] == $b["F"])
		{
			return 0;
		}
		return ($a["F"] < $b["F"]) ? -1 : 1;
	}
	
	static function MergeIntervalsH($arr, $arrS)
	{
		$res = array();
		$arrW = array();
		foreach($arr as $dtC => $v)
		{
			if(isset($v["C"])) 
			{
				$res[$dtC] = array();
				continue;
			}
			if(isset($v["W"]) && is_array($v["W"]) && count($v["W"]) > 0)
			{
				$arrW0 = $v["W"];
				uasort($arrW0, array("self", "SortMethodH"));
				$arrW = self::MergeIntervals($arrW0);
			}
			else
			{
				$arrW = $arrS[self::GetDayNom($dtC)];
			}
			$arrH0 = (isset($v["H"]) && is_array($v["H"]) && count($v["H"]) > 0) ? $v["H"] : array();
			uasort($arrH0, array("self", "SortMethodH"));
			$arrH = self::MergeIntervals($arrH0);
			
			$h = $w = 0;
			$wC = count($arrW) - 1;
			$hC = count($arrH) - 1;
			while(true)
			{
				if($w > $wC)
				{
					break;
				}
				if($h > $hC)
				{
					// остатки $wC в результат
					for($i = $w; $i <= $wC; $i++) $res[$dtC][] = array("F" => $arrW[$i]["F"], "T" => $arrW[$i]["T"]);
					break;
				}
								
				if($arrH[$h]["T"] < $arrW[$w]["T"])
				{
					if($arrW[$w]["F"] < $arrH[$h]["F"])
					{
						//h   ---
						//w -------
						$res[$dtC][] = array("F" => $arrW[$w]["F"], "T" => $arrH[$h]["F"]);
						$arrW[$w]["F"] = $arrH[$h]["T"];
					}
					else
					{
						//h ---     | ---
						//w     --- |  ---
						$arrW[$w]["F"] = max($arrW[$w]["F"], $arrH[$h]["T"]);
						
					}
					$h++;
				}
				else
				{
					//h -----
					//w  ---
					if(!($arrH[$h]["F"] <= $arrW[$w]["F"]))
					{
						//h     --- |  ---
						//w ---     | ---
						$res[$dtC][] = array("F" => $arrW[$w]["F"], "T" => min($arrW[$w]["T"], $arrH[$h]["F"]));
					}
					$w++;
				}
			}
		}
		return $res;
	}

public static 	static function InsertDefaultValues()
	{
		global $DB;
		$err_mess = (self::err_mess())."<br>Function: InsertDefaultValues<br>Line: ";
		$t_sla_shedule = self::SLA_SHEDULE;

		$arInsStr = array(
			"SLA_ID" => 0,
			"WEEKDAY_NUMBER" => 0,
			"OPEN_TIME" => "'24H'",
			"MINUTE_FROM" => 0,
			"MINUTE_TILL" => 0,
			"TIMETABLE_ID" => 0,

		);

		$strSql = "
			SELECT
				R.TIMETABLE_ID
			FROM
				(
				SELECT
					T.ID TIMETABLE_ID,
					SUM(" . CTicket::isnull( "S.ID", 0 ) .") V
				FROM
					b_ticket_timetable T
					LEFT JOIN $t_sla_shedule S
						ON T.ID = S.TIMETABLE_ID AND S.TIMETABLE_ID > 0
				GROUP BY
				T.ID
				) R
			WHERE
				R.V = 0
			";
		$q = $DB->Query($strSql, false, $err_mess.__LINE__);

		while ($arrR = $q->Fetch())
		{
			for($i=0;$i<=6;$i++)
			{
				$arInsStr["WEEKDAY_NUMBER"] = $i;
				$arInsStr["TIMETABLE_ID"] = intval($arrR["TIMETABLE_ID"]);
				$DB->Insert($t_sla_shedule, $arInsStr, $err_mess . __LINE__);
			}
		}

	}

public static 	static function GetShedule($arFilter)
	{
		global $DB;
		$err_mess = (self::err_mess())."<br>Function: getShedule<br>Line: ";
		$res = array();
		$t_sla = self::SLA;
		$t_sla_shedule = self::SLA_SHEDULE;
		
		$arSqlSearch = Array();
		foreach($arFilter as $key => $val)
		{
		
			if((is_array($val) && count($val) <= 0) || (!is_array($val) && strlen($val) <= 0))
			{
				continue;
			}
			$key = strtoupper($key);
			if(is_array($val))
			{
				$val = implode(" | ", $val);
			}
			switch($key)
			{
				case "SLA_ID":
					$arSqlSearch[] = GetFilterQuery("SLA.ID", $val, "N");	
					break;
			}
			
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		
		$strSql = "
			SELECT
				SLA.ID SLA_ID,
				S.WEEKDAY_NUMBER,
				S.OPEN_TIME,
				S.MINUTE_FROM,
				S.MINUTE_TILL
			FROM
				$t_sla SLA
				INNER JOIN $t_sla_shedule S
					ON SLA.TIMETABLE_ID = S.TIMETABLE_ID AND S.TIMETABLE_ID > 0
			WHERE
				$strSqlSearch
			ORDER BY
				SLA_ID, WEEKDAY_NUMBER, MINUTE_FROM
			";
		$q = $DB->Query($strSql, false, $err_mess.__LINE__);

		if(intval($q->SelectedRowsCount()) <= 0)
		{
			self::InsertDefaultValues();
			$q = $DB->Query($strSql, false, $err_mess.__LINE__);
		}
		
		$res0 = array();
		$noAdd = array();
		$oldSLA = -1;
		$oldWN = -1;
		while ($arrR = $q->Fetch()) 
		{
			if(!CSupportTools::array_keys_exists("SLA_ID,WEEKDAY_NUMBER,OPEN_TIME", $arrR))
			{
				continue;
			}
			$cSLA = $arrR["SLA_ID"];
			$cWN = intval($arrR["WEEKDAY_NUMBER"]);
			
			if($oldSLA != $cSLA || $oldWN != $cWN) 
			{
				if($oldSLA != -1)$res[$oldSLA][$oldWN] = self::MergeIntervals($res0);
				$res0 = array();
				$oldSLA = $cSLA;
				$oldWN = $cWN;
			}
			
			$cOT = $arrR["OPEN_TIME"];
			if(isset($noAdd[$cSLA][$cWN])) continue;
			
			switch($cOT)
			{
				case "24H":
					$res0 = array(0 => array("F" => 0, "T" => (24*60*60 - 1)));
					$noAdd[$cSLA][$cWN] = true;
					break;
				case "CLOSED":
					$res0 = array();
					$noAdd[$cSLA][$cWN] = true;
					break;
				case "CUSTOM":
					$res0[] = array("F" => min(intval($arrR["MINUTE_FROM"])*60, intval($arrR["MINUTE_TILL"])*60), "T" => max(intval($arrR["MINUTE_FROM"])*60, intval($arrR["MINUTE_TILL"])*60));
					break;
			}
			
		}
		if($oldSLA > 0) $res[$oldSLA][$oldWN] = self::MergeIntervals($res0);
		return $res;
	}
			
public static 	static function MergeIntervals($arr)
	{
		if(count($arr) <= 0)
		{
			return array();
		}
		$r = array(0 => $arr[0]);
		$i = 0;
		foreach($arr as $k => $v) 
		{
			if($r[$i]["T"] < $v["F"])
			{
				$r[++$i] = $v;
			}
			else
			{
				$r[$i]["T"] = max($r[$i]["T"], $v["T"]);
			}
		}
		return  $r;
	}
	
public static 	static function TimeToStr($t)
	{
		$s = intval(fmod($t, 60));
		$m = ($t - $s) / 60;
		$h = ($t - $m*60 - $s)/3600;
		return date("H:i", mktime($h, $m, 0, 1, 1, 2000));
	}
	
public static 	static function ToCache($arFilter = array(), $RSD = true, $arFromGetEndDate = null)
	{
		/*
		$arFilter(
			SLA => array()
		)
		*/
		global $DB;
		$currD = time();
		$uniq = "";
		$dbType = strtolower($DB->type);

		if($dbType === "mysql")
		{
			$DB->StartUsingMasterOnly();

			$uniq = COption::GetOptionString("main", "server_uniq_id", "");
			if(strlen($uniq)<=0)
			{
				$uniq = md5(uniqid(rand(), true));
				COption::SetOptionString("main", "server_uniq_id", $uniq);
			}

			$db_lock = $DB->Query("SELECT GET_LOCK('" . $uniq . "_supportToCache', 0) as L");
			$ar_lock = $db_lock->Fetch();
			if($ar_lock["L"] !== "1")
			{
				return;
			}

			//Перед пересчетом проверить в центральной базе что данных действительно нет, на случй здержки передачм данных в дочернюю базу(только для MYSQL)
			if(is_array($arFromGetEndDate))
			{
				$res = self::getEndDate($arFromGetEndDate["SLA"], $arFromGetEndDate["PERIOD_MIN"], $arFromGetEndDate["DATE_FROM"], true);
				if($res !== null)
				{
					return $res;
				}
			}
		}

		$err_mess = (self::err_mess())."<br>Function: toCache<br>Line: ";
		$timetable_cache = self::TIMETABLE_CACHE;
		$ndF = self::GetNumberOfDaysForward();
		$ndB = self::GetNumberOfDaysBackward();
				
		$dateF = self::GetDayBegin($currD - $ndB*24*60*60);
		$dateT = self::GetDayEnd($currD + $ndF*24*60*60);
		
		self::$arrS = self::GetShedule($arFilter);
		if(count(self::$arrS) <= 0)
		{
			return null;
		}
		self::$arrH = self::GetHolidays(($dateF - 24*60*60), ($dateT + 24*60*60), self::$arrS, $arFilter);
		
		$arrSLA = array_keys(self::$arrS);
		
		$arSqlSearch = Array();
		foreach($arFilter as $key => $val)
		{
			if((is_array($val) && count($val) <= 0) || (!is_array($val) && strlen($val) <= 0))
			{
				continue;
			}
			$key = strtoupper($key);
			if(is_array($val))
			{
				$val = implode(" | ", $val);
			}
			
			switch($key)
			{
				case "SLA_ID":
					$arSqlSearch[] = GetFilterQuery("SLA_ID", $val, "N");	
					break;
			}
			
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		
		$DB->Query("DELETE FROM $timetable_cache WHERE $strSqlSearch", false, $err_mess . __LINE__);
		$f = new CSupportTableFields(self::$cache);
		$colNames = null;
		$strList = "";
		$coma = "";
		foreach($arrSLA as $k => $sla)
		{
			$dateC = $dateF;
			$sum = 0;
			while($dateC <= $dateT)
			{
				if(isset(self::$arrH[$sla]) && array_key_exists($dateC, self::$arrH[$sla]))
				{
					$a = self::$arrH[$sla][$dateC];
				}
				else
				{
					$a = self::$arrS[$sla][self::GetDayNom($dateC)];
				}
				foreach($a as $k2 => $v2)
				{
					$sum = $sum + $v2["T"] - $v2["F"];
					$f->SLA_ID = $sla;
					$f->DATE_FROM = ($dateC + $v2["F"]);
					$f->DATE_TILL = ($dateC + $v2["T"]);
					$f->W_TIME = $v2["T"] - $v2["F"];
					$f->W_TIME_INC = $sum;

					CTimeZone::Disable();
					if($dbType === "mysql")
					{
						$arCurrTicketFields = $f->ToArray(CSupportTableFields::ALL, array(), true);
						if($colNames === null)
						{
							$colNames = implode(", ", array_keys($arCurrTicketFields));
						}
						$strCurrTicketFields = "(" . implode(",", $arCurrTicketFields) . ")";
						if(strlen($strList . ", " . $strCurrTicketFields) > 2000)
						{
							$strSql = "INSERT INTO " . $timetable_cache . " (" . $colNames. ") VALUES " . $strList;
							$strList = $strCurrTicketFields;
						}
						else
						{
							$strList .= $coma . $strCurrTicketFields;
							$coma = ", ";
							continue;
						}
						$DB->Query($strSql, false, $err_mess . __LINE__);
					}
					else
					{
						$DB->Insert($timetable_cache, $f->ToArray(CSupportTableFields::ALL, array(CSupportTableFields::NOT_NULL), true), $err_mess . __LINE__);
					}
					CTimeZone::Enable();
				}
				$dateC = self::GetDayBegin($dateC + 25*60*60);
			}
		}
		if($dbType === "mysql")
		{
			if(strlen($strList) > 0)
			{
				$strSql = "INSERT INTO " . $timetable_cache . " (" . $colNames. ") VALUES " . $strList;
				$DB->Query($strSql, false, $err_mess . __LINE__);
			}

			$DB->Query("SELECT RELEASE_LOCK('" . $uniq . "_supportToCache')");
			$DB->StopUsingMasterOnly();
			//CAgent::AddAgent("CSupportTimetableCache::UpdateDiscardedTickets(" . $currD . ");", "support", "N", 5*60);
		}

		
		if($RSD)
		{
			CTicketReminder::RecalculateSupportDeadline($arFilter);
		}
		return null;
	}

	function UpdateDiscardedTickets()
	{
		return "";
	}
	
	//$dateFrom - время сервера с часовым поясом из настроек текущего пользователя
public static 	static function getEndDate($sla, $periodMin0, $dateFrom, $secondTry = false)
	{
		global $DB;
		$err_mess = (self::err_mess())."<br>Function: getEndDate<br>Line: ";
		$sla = intval($sla);
		$periodMin = intval($periodMin0) * 60;
		$dateFromTS = MakeTimeStamp($dateFrom) - CTimeZone::GetOffset();
		$timetableCache = self::TIMETABLE_CACHE;
								
		CTimeZone::Disable();
		$strSql = "
			SELECT
				TC.ID,
				TC.SLA_ID,
				" . $DB->DateToCharFunction("TC.DATE_FROM", "FULL") . " DATE_FROM,
				" . $DB->DateToCharFunction("TC.DATE_TILL", "FULL") . " DATE_TILL,
				TC.W_TIME,
				TC.W_TIME_INC
			FROM 
				$timetableCache TC
				INNER JOIN (
					SELECT
						MAX(TC.DATE_FROM) MAX_DATE_FROM
					FROM
						$timetableCache TC
					WHERE
						SLA_ID = $sla AND DATE_FROM <= " . $DB->CharToDateFunction($dateFrom) . ") PZ
					ON TC.DATE_FROM = PZ.MAX_DATE_FROM AND SLA_ID = $sla";
		$q = $DB->Query($strSql, false, $err_mess.__LINE__);
		CTimeZone::Enable();

		if($arrR = $q->Fetch()) 
		{
			
			$delta =  intval($arrR["W_TIME_INC"]) -  intval($arrR["W_TIME"]) + min(($dateFromTS - MakeTimeStamp($arrR["DATE_FROM"])), intval($arrR["W_TIME"]));			
			$findD = $delta + $periodMin ;
									
			//CTimeZone::Disable();
			$strSql = "
				SELECT
					TC.ID,
					TC.SLA_ID,
					" . $DB->DateToCharFunction("TC.DATE_FROM", "FULL") . " DATE_FROM,
					" . $DB->DateToCharFunction("TC.DATE_TILL", "FULL") . " DATE_TILL,
					TC.W_TIME,
					TC.W_TIME_INC
				FROM 
					$timetableCache TC
					INNER JOIN (
						SELECT
							MIN(TC.DATE_FROM) DF
						FROM
							$timetableCache TC
						WHERE
							SLA_ID = $sla AND $findD <= W_TIME_INC AND W_TIME_INC <= ($findD + 2*24*60*60)) PZ
							ON TC.DATE_FROM = PZ.DF AND SLA_ID = $sla";
			$q2 = $DB->Query($strSql, false, $err_mess.__LINE__);
			//CTimeZone::Enable();
			
			if($arrR2 = $q2->Fetch())
			{
				$ts = MakeTimeStamp($arrR2["DATE_TILL"]) - (intval($arrR2["W_TIME_INC"]) - $findD);
				$ts2010 = mktime(0, 0, 0, 1, 1, 2010);
				if($ts > $ts2010)
				{
					return $ts;
				}
			}
		}

		if(!$secondTry)
		{
			$arOpt = array("SLA" => $sla, "PERIOD_MIN" => $periodMin0, "DATE_FROM" => $dateFrom);
			$res =  self::ToCache(array( "SLA_ID"=> $sla ), false, $arOpt);
			if($res !== null)
			{
				return $res;
			}
			return self::getEndDate($sla, $periodMin0, $dateFrom, true);
		}
		return null;
	}
	
public static 	function StartAgent()
	{
		CAgent::RemoveAgent("CSupportTimetableCache::toCache();", "support");
		$NOTIFY_AGENT_ID = CAgent::AddAgent("CSupportTimetableCache::toCache();", "support", "N", 7*86400);
	}
	
}
?>