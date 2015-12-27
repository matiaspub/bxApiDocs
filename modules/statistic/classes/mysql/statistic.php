<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/classes/general/statistic.php");


/**
 * <b>CStatistics</b> - класс содержащий общие методы работы с модулем "Статистика". 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatistics/index.php
 * @author Bitrix
 */
class CStatistics extends CAllStatistics
{
	public static function CleanUpTableByDate($cleanup_date, $table_name, $date_name)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		if (strlen($cleanup_date)>0)
		{
			$stmp = MkDateTime(ConvertDateTime($cleanup_date,"D.M.Y"),"d.m.Y");
			if ($stmp)
			{
				$strSql = "DELETE FROM $table_name WHERE $date_name<FROM_UNIXTIME('$stmp')";
				$DB->Query($strSql, false, $err_mess.__LINE__);
			}
		}
	}

	public static function GetSessionDataByMD5($GUEST_MD5)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$php_session_time = intval(ini_get("session.gc_maxlifetime"));
		$strSql = "
			SELECT
				ID,
				SESSION_DATA
			FROM
				b_stat_session_data
			WHERE
				GUEST_MD5 = '".$DB->ForSql($GUEST_MD5)."'
			and DATE_LAST > DATE_ADD(now(), INTERVAL - $php_session_time SECOND)
			LIMIT 1
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	public static function CleanUpPathDynamic()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "PATH_DAYS"));
		//$STEPS = intval(COption::GetOptionString("statistic", "MAX_PATH_STEPS"));
		if ($DAYS>=0)
		{
			$strSql = "
				DELETE FROM b_stat_path
				WHERE DATE_STAT <= DATE_SUB(CURDATE(),INTERVAL $DAYS DAY)
				OR DATE_STAT is null
			";//STEPS removed due to insert check
			$DB->Query($strSql, false, $err_mess.__LINE__);
			$strSql = "
				DELETE FROM b_stat_path_adv
				WHERE DATE_STAT <= DATE_SUB(CURDATE(),INTERVAL $DAYS DAY)
				OR DATE_STAT is null
			";//STEPS removed due to insert check
			$DB->Query($strSql, false, $err_mess.__LINE__);
			if(COption::GetOptionString("statistic", "USE_AUTO_OPTIMIZE")=="Y")
			{
				$DB->Query("OPTIMIZE TABLE b_stat_path", false, $err_mess.__LINE__);
				$DB->Query("OPTIMIZE TABLE b_stat_path_adv", false, $err_mess.__LINE__);
			}
		}
	}

	public static function CleanUpPathCache()
	{
		__SetNoKeepStatistics();
		if ($_SESSION["SESS_NO_AGENT_STATISTIC"]!="Y" && !defined("NO_AGENT_STATISTIC"))
		{
			set_time_limit(0);
			ignore_user_abort(true);
			$err_mess = "File: ".__FILE__."<br>Line: ";
			$DB = CDatabase::GetModuleConnection('statistic');
			$php_session_time = intval(ini_get("session.gc_maxlifetime"));
			$strSql = "
				DELETE FROM b_stat_path_cache WHERE
					DATE_HIT < DATE_ADD(now(), INTERVAL - $php_session_time SECOND) or
					DATE_HIT is null
					";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
		return "CStatistics::CleanUpPathCache();";
	}

	public static function CleanUpSessionData()
	{
		__SetNoKeepStatistics();
		if ($_SESSION["SESS_NO_AGENT_STATISTIC"]!="Y" && !defined("NO_AGENT_STATISTIC"))
		{
			set_time_limit(0);
			ignore_user_abort(true);
			$err_mess = "File: ".__FILE__."<br>Line: ";
			$DB = CDatabase::GetModuleConnection('statistic');
			$php_session_time = intval(ini_get("session.gc_maxlifetime"));
			$strSql = "
				DELETE FROM b_stat_session_data WHERE
					DATE_LAST < DATE_ADD(now(), INTERVAL - $php_session_time SECOND) or
					DATE_LAST is null
					";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
		return "CStatistics::CleanUpSessionData();";
	}

	public static function CleanUpSearcherDynamic()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "SEARCHER_DAYS"));
		$SID = 0;
		if ($DAYS>=0)
		{
			$strSql = "
				SELECT
					ID,
					ifnull(DYNAMIC_KEEP_DAYS,'$DAYS') as DYNAMIC_KEEP_DAYS
				FROM
					b_stat_searcher
				";
			$w = $DB->Query($strSql, false, $err_mess.__LINE__);
			while ($wr = $w->Fetch())
			{
				$SDAYS = intval($wr["DYNAMIC_KEEP_DAYS"]);
				$SID = intval($wr["ID"]);
				$strSql = "
					SELECT
						ID,
						TOTAL_HITS
					FROM
						b_stat_searcher_day
					WHERE
						SEARCHER_ID = $SID
						AND DATE_STAT <= DATE_SUB(CURDATE(),INTERVAL $SDAYS DAY)
				";
				$z = $DB->Query($strSql, false, $err_mess.__LINE__);
				while ($zr=$z->Fetch())
				{
					$ID = $zr["ID"];
					if (intval($zr["TOTAL_HITS"])>0)
					{
						$arFields = Array(
							"DATE_CLEANUP"	=> $DB->GetNowFunction(),
							"TOTAL_HITS"	=> "TOTAL_HITS + ".intval($zr["TOTAL_HITS"]),
							);
						$DB->Update("b_stat_searcher",$arFields,"WHERE ID='$SID'",$err_mess.__LINE__);
					}
					$strSql = "DELETE FROM b_stat_searcher_day WHERE ID='$ID'";
					$DB->Query($strSql, false, $err_mess.__LINE__);
				}
			}
			if (intval($SID)>0 && COption::GetOptionString("statistic", "USE_AUTO_OPTIMIZE")=="Y")
			{
				$DB->Query("OPTIMIZE TABLE b_stat_searcher_day", false, $err_mess.__LINE__);
			}
		}
	}

	public static function CleanUpEventDynamic()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "EVENT_DYNAMIC_DAYS"));
		$EID = 0;
		if ($DAYS>=0)
		{
			$strSql = "
				SELECT
					ID,
					ifnull(DYNAMIC_KEEP_DAYS,'".$DAYS."') as DYNAMIC_KEEP_DAYS
				FROM
					b_stat_event
				";
			$w = $DB->Query($strSql, false, $err_mess.__LINE__);
			while ($wr = $w->Fetch())
			{
				$EDAYS = intval($wr["DYNAMIC_KEEP_DAYS"]);
				$EID = intval($wr["ID"]);
				$strSql = "
					SELECT
						ID,
						COUNTER,
						MONEY
					FROM
						b_stat_event_day
					WHERE
						EVENT_ID = ".$EID."
						AND DATE_STAT <= DATE_SUB(CURDATE(),INTERVAL ".$EDAYS." DAY)
				";
				$z = $DB->Query($strSql, false, $err_mess.__LINE__);
				while ($zr=$z->Fetch())
				{
					$ID = $zr["ID"];
					if (intval($zr["COUNTER"])>0)
					{
						$arFields = Array(
							"DATE_CLEANUP"	=> $DB->GetNowFunction(),
							"COUNTER"	=> "COUNTER + ".intval($zr["COUNTER"]),
							"MONEY"		=> "MONEY + ".doubleval($zr["MONEY"])
							);
						$DB->Update("b_stat_event",$arFields,"WHERE ID='$EID'",$err_mess.__LINE__);
					}
					$strSql = "DELETE FROM b_stat_event_day WHERE ID='$ID'";
					$DB->Query($strSql, false, $err_mess.__LINE__);
				}
			}
			if (intval($EID)>0 && COption::GetOptionString("statistic", "USE_AUTO_OPTIMIZE")=="Y")
			{
				$DB->Query("OPTIMIZE TABLE b_stat_event_day", false, $err_mess.__LINE__);
			}
		}
	}

	public static function CleanUpAdvDynamic()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "ADV_DAYS"));
		if ($DAYS>=0)
		{
			$strSql = "
				DELETE FROM b_stat_adv_day
				WHERE DATE_STAT <= DATE_SUB(CURDATE(),INTERVAL $DAYS DAY)
				OR DATE_STAT is null
			";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			$strSql = "
				DELETE FROM b_stat_adv_event_day
				WHERE DATE_STAT <= DATE_SUB(CURDATE(),INTERVAL $DAYS DAY)
				OR DATE_STAT is null
			";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			if (COption::GetOptionString("statistic", "USE_AUTO_OPTIMIZE")=="Y")
			{
				$DB->Query("OPTIMIZE TABLE b_stat_adv_day", false, $err_mess.__LINE__);
				$DB->Query("OPTIMIZE TABLE b_stat_adv_event_day", false, $err_mess.__LINE__);
			}
		}
	}

	public static function CleanUpPhrases()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "PHRASES_DAYS"));
		if ($DAYS>=0)
		{
			$strSql = "
				DELETE FROM b_stat_phrase_list
				WHERE DATE_HIT <= DATE_SUB(CURDATE(),INTERVAL $DAYS DAY)
				OR DATE_HIT is null
			";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			if(COption::GetOptionString("statistic", "USE_AUTO_OPTIMIZE")=="Y")
			{
				$DB->Query("OPTIMIZE TABLE b_stat_phrase_list", false, $err_mess.__LINE__);
			}
		}
	}

	public static function CleanUpRefererList()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "REFERER_LIST_DAYS"));
		if($DAYS>=0)
		{
			$strSql = "
				DELETE FROM b_stat_referer_list
				WHERE DATE_HIT <= DATE_SUB(CURDATE(),INTERVAL $DAYS DAY)
				OR DATE_HIT is null
			";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			if(COption::GetOptionString("statistic", "USE_AUTO_OPTIMIZE")=="Y")
			{
				$DB->Query("OPTIMIZE TABLE b_stat_referer_list", false, $err_mess.__LINE__);
			}
		}
	}

	public static function CleanUpReferer()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = COption::GetOptionString("statistic", "REFERER_DAYS");
		$TOP = COption::GetOptionString("statistic", "REFERER_TOP");
		$DAYS = intval($DAYS);
		if ($DAYS>=0)
		{
			$strSql = "SELECT ID FROM b_stat_referer ORDER BY SESSIONS desc LIMIT ".intval($TOP);
			$z = $DB->Query($strSql,false,$err_mess.__LINE__);
			$str = "0";
			while ($zr=$z->Fetch()) $str .= ",".$zr["ID"];
			$strSql = "
				DELETE FROM b_stat_referer
				WHERE
					(DATE_LAST <= DATE_SUB(CURDATE(),INTERVAL $DAYS DAY)
					OR DATE_LAST is null)
					and ID not in ($str)
					";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			if (COption::GetOptionString("statistic", "USE_AUTO_OPTIMIZE")=="Y")
			{
				$DB->Query("OPTIMIZE TABLE b_stat_referer", false, $err_mess.__LINE__);
			}
		}
	}

	public static function CleanUpVisits()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$VISIT_DAYS = COption::GetOptionString("statistic", "VISIT_DAYS");
		$VISIT_DAYS = intval($VISIT_DAYS);
		if ($VISIT_DAYS>=0)
		{
			$strSql = "
				DELETE FROM b_stat_page
				WHERE
					DATE_STAT <= DATE_SUB(CURDATE(),INTERVAL $VISIT_DAYS DAY)
					OR DATE_STAT is null
				";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			$strSql = "
				DELETE FROM b_stat_page_adv
				WHERE
					DATE_STAT <= DATE_SUB(CURDATE(),INTERVAL $VISIT_DAYS DAY)
					OR DATE_STAT is null
				";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			if (COption::GetOptionString("statistic", "USE_AUTO_OPTIMIZE")=="Y")
			{
				$DB->Query("OPTIMIZE TABLE b_stat_page", false, $err_mess.__LINE__);
				$DB->Query("OPTIMIZE TABLE b_stat_page_adv", false, $err_mess.__LINE__);
			}
		}
	}

	public static function CleanUpCities()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "CITY_DAYS"));
		if($DAYS >= 0)
		{
			$strSql = "
				DELETE FROM b_stat_city_day
				WHERE DATE_STAT <= DATE_SUB(CURDATE(),INTERVAL $DAYS DAY)
			";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			if (COption::GetOptionString("statistic", "USE_AUTO_OPTIMIZE")=="Y")
			{
				$DB->Query("OPTIMIZE TABLE b_stat_city_day", false, $err_mess.__LINE__);
			}
		}
	}

	public static function CleanUpCountries()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "COUNTRY_DAYS"));
		if($DAYS >= 0)
		{
			$strSql = "
				DELETE FROM b_stat_country_day
				WHERE DATE_STAT <= DATE_SUB(CURDATE(),INTERVAL $DAYS DAY)
				OR DATE_STAT is null
			";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			if (COption::GetOptionString("statistic", "USE_AUTO_OPTIMIZE")=="Y")
			{
				$DB->Query("OPTIMIZE TABLE b_stat_country_day", false, $err_mess.__LINE__);
			}
		}
	}

	public static function CleanUpGuests()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "GUEST_DAYS"));
		if($DAYS>=0)
		{
			$strSql = "
				DELETE FROM b_stat_guest
				WHERE LAST_DATE <= DATE_SUB(CURDATE(),INTERVAL $DAYS DAY)
				OR LAST_DATE is null
			";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			if (COption::GetOptionString("statistic", "USE_AUTO_OPTIMIZE")=="Y")
			{
				$DB->Query("OPTIMIZE TABLE b_stat_guest", false, $err_mess.__LINE__);
			}
		}
	}

	public static function CleanUpSessions()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "SESSION_DAYS"));
		if ($DAYS>=0)
		{
			$strSql = "
				DELETE FROM b_stat_session
				WHERE DATE_LAST <= DATE_SUB(CURDATE(),INTERVAL $DAYS DAY)
				OR DATE_LAST is null
			";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			if(COption::GetOptionString("statistic", "USE_AUTO_OPTIMIZE")=="Y")
			{
				$DB->Query("OPTIMIZE TABLE b_stat_session", false, $err_mess.__LINE__);
				$DB->Query("OPTIMIZE TABLE b_stat_session_data", false, $err_mess.__LINE__);
			}
		}
	}

	public static function CleanUpHits()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "HIT_DAYS"));
		if ($DAYS>=0)
		{
			$strSql = "
				DELETE FROM b_stat_hit
				WHERE DATE_HIT <= DATE_SUB(CURDATE(),INTERVAL $DAYS DAY)
				OR DATE_HIT is null
			";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			if (COption::GetOptionString("statistic", "USE_AUTO_OPTIMIZE")=="Y")
			{
				$DB->Query("OPTIMIZE TABLE b_stat_hit", false, $err_mess.__LINE__);
			}
		}
	}

	public static function CleanUpSearcherHits()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "SEARCHER_HIT_DAYS"));

		$strSql = "
			DELETE FROM b_stat_searcher_hit
			WHERE HIT_KEEP_DAYS IS NULL
			AND DATE_HIT <= DATE_SUB(CURDATE(), INTERVAL $DAYS DAY)
		";
		$DB->Query($strSql, false, $err_mess.__LINE__);

		$strSql = "
			DELETE sh.* FROM
			b_stat_searcher s
			STRAIGHT_JOIN b_stat_searcher_hit sh
			WHERE s.ID = sh.SEARCHER_ID
			AND s.HIT_KEEP_DAYS is not null
			AND sh.DATE_HIT <= DATE_SUB(CURDATE(), INTERVAL s.HIT_KEEP_DAYS DAY)
		";
		$DB->Query($strSql, false, $err_mess.__LINE__);

		if(COption::GetOptionString("statistic", "USE_AUTO_OPTIMIZE")=="Y")
		{
			$DB->Query("OPTIMIZE TABLE b_stat_searcher_hit", false, $err_mess.__LINE__);
		}
	}

	public static function CleanUpAdvGuests()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$ADV_GUEST_DAYS = COption::GetOptionString("statistic", "ADV_GUEST_DAYS");
		$ADV_GUEST_DAYS = intval($ADV_GUEST_DAYS);
		if ($ADV_GUEST_DAYS>=0)
		{
			$strSql = "
				DELETE FROM b_stat_adv_guest WHERE
				(
					to_days(now())-to_days(DATE_GUEST_HIT)>=$ADV_GUEST_DAYS or
					DATE_GUEST_HIT is null or
					length(DATE_GUEST_HIT)<=0
				)
				and
				(
					to_days(now())-to_days(DATE_HOST_HIT)>=$ADV_GUEST_DAYS or
					DATE_HOST_HIT is null or
					length(DATE_HOST_HIT)<=0
				)
			";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			if(COption::GetOptionString("statistic", "USE_AUTO_OPTIMIZE")=="Y")
			{
				$DB->Query("OPTIMIZE TABLE b_stat_adv_guest", false, $err_mess.__LINE__);
			}
		}
	}

	public static function CleanUpEvents()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "EVENTS_DAYS"));

		$strSql = "
			DELETE FROM b_stat_event_list
			WHERE KEEP_DAYS IS NULL
			AND DATE_ENTER <= DATE_SUB(CURDATE(), INTERVAL $DAYS DAY)
		";
		$DB->Query($strSql, false, $err_mess.__LINE__);

		$strSql = "
			DELETE el.* FROM
			b_stat_event e
			STRAIGHT_JOIN b_stat_event_list el
			WHERE e.ID = el.EVENT_ID
			AND e.KEEP_DAYS is not null
			AND el.DATE_ENTER <= DATE_SUB(CURDATE(), INTERVAL e.KEEP_DAYS DAY)
		";
		$DB->Query($strSql, false, $err_mess.__LINE__);

		if(COption::GetOptionString("statistic", "USE_AUTO_OPTIMIZE")=="Y")
		{
			$DB->Query("OPTIMIZE TABLE b_stat_event_list", false, $err_mess.__LINE__);
		}
	}

	public static function SetNewDayForSite($SITE_ID=false, $HOSTS=0, $TOTAL_HOSTS=0, $SESSIONS=0, $HITS=0)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');

		if ($SITE_ID===false)
		{
			$SITE_ID = "";
			if (!(defined("ADMIN_SECTION") && ADMIN_SECTION===true) && defined("SITE_ID"))
			{
				$SITE_ID = SITE_ID;
			}
		}
		if (strlen($SITE_ID)>0)
		{
			$strSql = "SELECT D.ID FROM b_stat_day_site D WHERE D.DATE_STAT=CURDATE() AND SITE_ID = '".$DB->ForSql($SITE_ID, 2)."'";
			$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
			if (!$rs->Fetch())
			{
				$arFields = Array(
					"DATE_STAT"	=> "curdate()",
					"SITE_ID"	=> "'".$DB->ForSql($SITE_ID, 2)."'",
					"C_HOSTS"	=> intval($HOSTS),
					"SESSIONS"	=> intval($SESSIONS),
					"HITS"		=> intval($HITS),
					);
				$ID = $DB->Insert("b_stat_day_site", $arFields, $err_mess.__LINE__, false, "", true);
			}
			//Calculate attentiveness for yesturday
			$strSql = "
				SELECT D.ID, ".$DB->DateToCharFunction("D.DATE_STAT","SHORT")." DATE_STAT
				FROM b_stat_day_site D
				WHERE D.DATE_STAT=DATE_SUB(CURDATE(),INTERVAL 1 DAY)
				AND SITE_ID = '".$DB->ForSql($SITE_ID, 2)."'
			";
			$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
			if($ar=$rs->Fetch())
			{
				$arF = CSession::GetAttentiveness($ar["DATE_STAT"], $SITE_ID);
				if (is_array($arF)) $DB->Update("b_stat_day_site",$arF,"WHERE ID='".$ar["ID"]."'",$err_mess.__LINE__);
			}
		}
	}

	public static function SetNewDay($HOSTS=0, $TOTAL_HOSTS=0, $SESSIONS=0, $HITS=0, $NEW_GUESTS=0, $GUESTS=0, $FAVORITES=0)
	{
		__SetNoKeepStatistics();
		if ($_SESSION["SESS_NO_AGENT_STATISTIC"]!="Y" && !defined("NO_AGENT_STATISTIC"))
		{
			$err_mess = "File: ".__FILE__."<br>Line: ";
			$DB = CDatabase::GetModuleConnection('statistic');

			$strSql = "SELECT D.ID FROM b_stat_day D WHERE D.DATE_STAT=CURDATE()";
			$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
			if(!$rs->Fetch())
			{
				$arFields = Array(
					"DATE_STAT"	=> "curdate()",
					"C_HOSTS"	=> intval($HOSTS),
					"SESSIONS"	=> intval($SESSIONS),
					"GUESTS"	=> intval($GUESTS),
					"HITS"		=> intval($HITS),
					"FAVORITES"	=> intval($FAVORITES),
					"NEW_GUESTS"	=> intval($NEW_GUESTS),
					);
				$ID = $DB->Insert("b_stat_day", $arFields, $err_mess.__LINE__, false, "", true);
			}
			//Calculate attentiveness for yesturday
			$strSql = "
				SELECT D.ID, ".$DB->DateToCharFunction("D.DATE_STAT","SHORT")." DATE_STAT
				FROM b_stat_day D
				WHERE D.DATE_STAT=DATE_SUB(CURDATE(),INTERVAL 1 DAY)
			";
			$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
			if($ar=$rs->Fetch())
			{
				$arF = CSession::GetAttentiveness($ar["DATE_STAT"]);
				if (is_array($arF)) $DB->Update("b_stat_day",$arF,"WHERE ID='".$ar["ID"]."'",$err_mess.__LINE__);
			}
		}
		return "CStatistics::SetNewDay();";
	}

	public static function DBDateAdd($date, $days=1)
	{
		return $date." + INTERVAL ".$days." DAY";
	}

	public static function DBTopSql($strSql, $nTopCount=false)
	{
		if($nTopCount===false)
			$nTopCount = intval(COption::GetOptionString('statistic','RECORDS_LIMIT'));
		else
			$nTopCount = intval($nTopCount);
		if($nTopCount>0)
			return str_replace("/*TOP*/", "", $strSql)."\nLIMIT ".$nTopCount;
		else
			return str_replace("/*TOP*/", "", $strSql);
	}

	public static function DBFirstDate($strSql)
	{
		return "ifnull(".$strSql.",'1980-01-01')";
	}

	public static function DBDateDiff($date1, $date2)
	{
		return "UNIX_TIMESTAMP(".$date1.")-UNIX_TIMESTAMP(".$date2.")";
	}
}

?>
