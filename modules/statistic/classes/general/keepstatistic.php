<?
class CKeepStatistics
{
	static $HIT_ID = 0;

	public static function GetCuurentHitID()
	{
		return self::$HIT_ID;
	}

	public static function CheckSkip()
	{
		global $USER;
		$GO = true;
		$skipMode = COption::GetOptionString("statistic", "SKIP_STATISTIC_WHAT");
		switch($skipMode)
		{
			case "none":
				break;
			case "both":
			case "groups":
				$arUserGroups = $USER->GetUserGroupArray();
				$arSkipGroups = explode(",", COption::GetOptionString("statistic", "SKIP_STATISTIC_GROUPS"));
				foreach($arSkipGroups as $key=>$value)
				{
					if(in_array(intval($value), $arUserGroups))
					{
						$GO = false;
						break;
					}
				}
				if($skipMode=="groups")
					break;
				//else
				//	continue checking
			case "ranges":
				if($skipMode=="both" && $GO)
					break;//in case group check failed
				$GO = true;
				if(preg_match("/^.*?(\d+)\.(\d+)\.(\d+)\.(\d+)[\s-]*/", $_SERVER["REMOTE_ADDR"], $arIPAdress))
				{
					$arSkipIPRanges = explode("\n", COption::GetOptionString("statistic", "SKIP_STATISTIC_IP_RANGES"));
					foreach($arSkipIPRanges as $key=>$value)
					{
						if(preg_match("/^.*?(\d+)\.(\d+)\.(\d+)\.(\d+)[\s-]*(\d+)\.(\d+)\.(\d+)\.(\d+)/", $value, $arIPRange))
						{
							if(
								intval($arIPAdress[1]) >= intval($arIPRange[1]) && intval($arIPAdress[1]) <= intval($arIPRange[5]) &&
								intval($arIPAdress[2]) >= intval($arIPRange[2]) && intval($arIPAdress[2]) <= intval($arIPRange[6]) &&
								intval($arIPAdress[3]) >= intval($arIPRange[3]) && intval($arIPAdress[3]) <= intval($arIPRange[7]) &&
								intval($arIPAdress[4]) >= intval($arIPRange[4]) && intval($arIPAdress[4]) <= intval($arIPRange[8])
							)
							{
								$GO = false;
								break;
							}
						}
					}
				}
				break;
		}
		return $GO;
	}
	/////////////////////////////
	// Main statistics function
	/////////////////////////////
	public static function Keep($HANDLE_CALL=false)
	{

		__SetNoKeepStatistics();
		__GoogleAd();

		$GO = true;
		if(defined("STOP_STATISTICS")) $GO = false;
		if($HANDLE_CALL) $GO = true;

		if($GO && $_SESSION["SESS_NO_KEEP_STATISTIC"]!="Y" && !defined("NO_KEEP_STATISTIC"))
		{
			$GLOBALS["DB"]->StartUsingMasterOnly();
			if(CStatistics::CheckSkip())
				CStatistics::ReallyKeep();
			$GLOBALS["DB"]->StopUsingMasterOnly();
		}
	}

	public static function ReallyKeep()
	{
		global $USER, $APPLICATION, $STOP_SAVE_STATISTIC, $STOP_MESSAGE, $STOP_REDIRECT_URL, $STOP, $STOP_LIST_ID, $STOP_MESSAGE_LID;
		$DB = CDatabase::GetModuleConnection('statistic');

		$SITE_ID = "";
		if (defined("ADMIN_SECTION") && ADMIN_SECTION===true) $sql_site = "null";
		elseif (defined("SITE_ID"))
		{
			$sql_site = "'".$DB->ForSql(SITE_ID,2)."'";
			$SITE_ID = SITE_ID;
		}
		else $sql_site = "null";

		$ADV_NA = COption::GetOptionString("statistic", "ADV_NA");
		__SetReferer("referer1", "REFERER1_SYN");
		__SetReferer("referer2", "REFERER2_SYN");
		__SetReferer("referer3", "REFERER3_SYN");

		$SAVE_HITS	= (COption::GetOptionString("statistic", "SAVE_HITS")=="N") ? "N" : "Y";
		$SAVE_VISITS	= (COption::GetOptionString("statistic", "SAVE_VISITS")=="N") ? "N" : "Y";
		$SAVE_REFERERS	= (COption::GetOptionString("statistic", "SAVE_REFERERS")=="N") ? "N" : "Y";
		$SAVE_PATH_DATA	= (COption::GetOptionString("statistic", "SAVE_PATH_DATA")=="N") ? "N" : "Y";

		$stmp = time();
		$hour = date("G", $stmp); // 0..23
		$weekday = date("w", $stmp); // 0..6
		if ($weekday==0) $weekday = 7;
		$month = date("n", $stmp); // 1..12

		if ($STOP_SAVE_STATISTIC!="N" or $STOP!="Y")
		{
			if (isset($_SESSION["SESS_ADD_TO_FAVORITES"]) && $_SESSION["SESS_ADD_TO_FAVORITES"]=="Y")
			{
				$FAVORITES="Y";
				$_SESSION["SESS_ADD_TO_FAVORITES"]="";
			}
			else
			{
				$FAVORITES = "N";
			}

			$ERROR_404 = (defined("ERROR_404") && ERROR_404=="Y") ? "Y" : "N";
			$DB_now = $DB->GetNowFunction(); // save function for use in sql
			$DB_now_date = $DB->GetNowDate(); // save function for use in sql
			$STOP_LIST_ID = intval($STOP_LIST_ID);
			if ($ERROR_404=="Y") init_get_params($APPLICATION->GetCurUri());

			$IS_USER_AUTHORIZED = (intval($_SESSION["SESS_LAST_USER_ID"])>0 && is_object($USER) && $USER->IsAuthorized()) ? "Y" : "N";

			stat_session_register("SESS_SEARCHER_ID");
			stat_session_register("SESS_SEARCHER_NAME");
			stat_session_register("SESS_SEARCHER_CHECK_ACTIVITY");
			stat_session_register("SESS_SEARCHER_SAVE_STATISTIC");
			stat_session_register("SESS_SEARCHER_HIT_KEEP_DAYS");
			stat_session_register("SESS_LAST_PROTOCOL");
			stat_session_register("SESS_LAST_URI");
			stat_session_register("SESS_LAST_HOST");
			stat_session_register("SESS_LAST_PAGE");
			stat_session_register("SESS_LAST_DIR");
			stat_session_register("SESS_HTTP_REFERER");
			stat_session_register("SESS_COUNTRY_ID");
			stat_session_register("SESS_CITY_ID");
			stat_session_register("SESS_SESSION_ID");
			stat_session_register("SESS_REFERER_ID");
			stat_session_register("FROM_SEARCHER_ID");
			stat_session_register("SESS_FROM_SEARCHERS");
			stat_session_register("SESS_REQUEST_URI_CHANGE");
			stat_session_register("SESS_LAST_DIR_ID");
			stat_session_register("SESS_LAST_PAGE_ID");
			stat_session_register("SESS_GRABBER_STOP_TIME");
			stat_session_register("SESS_GRABBER_DEFENCE_STACK");
			stat_session_register("ACTIVITY_EXCEEDING_NOTIFIED");

			// SESSION_DATA_ID will be false when there is no sessions stored
			// true when session was not found in database
			// and an integer when was found and populated to $SESSION array
			$SESSION_DATA_ID = CKeepStatistics::RestoreSession();

			// Let's check activity limit
			$BLOCK_ACTIVITY = CStatistics::BlockVisitorActivity();

			// Activity under the limit
			if (!$BLOCK_ACTIVITY)
			{
				//Check if searcher was not deleted from searchers list
				if (intval($_SESSION["SESS_SEARCHER_ID"]) > 0)
				{
					$strSql = "
						SELECT ID
						FROM b_stat_searcher
						WHERE ID = '".intval($_SESSION["SESS_SEARCHER_ID"])."'
					";
					$z = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					if(!$z->Fetch())
						unset($_SESSION["SESS_SEARCHER_ID"]);
				}

				// We did not check for searcher
				if(strlen($_SESSION["SESS_SEARCHER_ID"])<=0)
				{
					// is it searcher hit?
					$strSql = "
						SELECT
							ID, NAME, SAVE_STATISTIC, HIT_KEEP_DAYS, CHECK_ACTIVITY
						FROM
							b_stat_searcher
						WHERE
							ACTIVE = 'Y'
						and ".$DB->Length("USER_AGENT").">0
						and upper('".$DB->ForSql($_SERVER["HTTP_USER_AGENT"],500)."') like ".$DB->Concat("'%'", "upper(USER_AGENT)", "'%'")."
						ORDER BY ".$DB->Length("USER_AGENT")." desc, ID
						";

					$z = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					if ($zr = $z->Fetch())
					{
						$_SESSION["SESS_SEARCHER_ID"] = intval($zr["ID"]);
						$_SESSION["SESS_SEARCHER_NAME"] = $zr["NAME"];
						$_SESSION["SESS_SEARCHER_CHECK_ACTIVITY"] = $zr["CHECK_ACTIVITY"];
						$_SESSION["SESS_SEARCHER_SAVE_STATISTIC"] = $zr["SAVE_STATISTIC"];
						$_SESSION["SESS_SEARCHER_HIT_KEEP_DAYS"] = $zr["HIT_KEEP_DAYS"];
						//Here was warning "A session is active. You cannot change the session module's ini settings at this time."
						//@ini_set("url_rewriter.tags", "");
					}
					$_SESSION["SESS_SEARCHER_ID"] = intval($_SESSION["SESS_SEARCHER_ID"]);
				}

				/************************************************
						Searcher section
				************************************************/

				// searcher detected
				if (intval($_SESSION["SESS_SEARCHER_ID"])>0)
				{
					$_SESSION["SESS_SEARCHER_ID"] = intval($_SESSION["SESS_SEARCHER_ID"]);

					// let's update day counter
					$arFields = Array(
							"DATE_LAST"	=> $DB_now,
							"TOTAL_HITS"	=> "TOTAL_HITS + 1"
							);
					$rows = $DB->Update("b_stat_searcher_day",$arFields,"WHERE SEARCHER_ID='".$_SESSION["SESS_SEARCHER_ID"]."' and DATE_STAT=".$DB_now_date,"File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
					// there is no stat for the day yet
					if (intval($rows)<=0)
					{
						// add it
						$arFields_i = Array(
							"DATE_STAT"	=> $DB_now_date,
							"DATE_LAST"	=> $DB_now,
							"SEARCHER_ID"	=> $_SESSION["SESS_SEARCHER_ID"],
							"TOTAL_HITS"	=> 1
							);
						$DB->Insert("b_stat_searcher_day",$arFields_i, "File: ".__FILE__."<br>Line: ".__LINE__);
					}
					elseif (intval($rows)>1) // have to cleanup duplicates
					{
						$strSql = "SELECT ID FROM b_stat_searcher_day WHERE SEARCHER_ID='".$_SESSION["SESS_SEARCHER_ID"]."' and DATE_STAT=".$DB_now_date." ORDER BY ID";
						$i=0;
						$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
						while ($ar = $rs->Fetch())
						{
							$i++;
							if ($i>1)
							{
								$strSql = "DELETE FROM b_stat_searcher_day WHERE ID = ".$ar["ID"];
								$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
							}
						}
					}

					// save indexed page if neccessary
					if ($_SESSION["SESS_SEARCHER_SAVE_STATISTIC"]=="Y")
					{
						$sql_HIT_KEEP_DAYS = (strlen($_SESSION["SESS_SEARCHER_HIT_KEEP_DAYS"])>0) ? intval($_SESSION["SESS_SEARCHER_HIT_KEEP_DAYS"]) : "null";
						$arFields = Array(
							"DATE_HIT" => $DB_now,
							"SEARCHER_ID" => intval($_SESSION["SESS_SEARCHER_ID"]),
							"URL" => "'".$DB->ForSql(__GetFullRequestUri(),2000)."'",
							"URL_404" => "'".$ERROR_404."'",
							"IP" => "'".$DB->ForSql($_SERVER["REMOTE_ADDR"],15)."'",
							"USER_AGENT" => "'".$DB->ForSql($_SERVER["HTTP_USER_AGENT"],500)."'",
							"HIT_KEEP_DAYS" => $sql_HIT_KEEP_DAYS,
							"SITE_ID" => $sql_site
							);
						$id = $DB->Insert("b_stat_searcher_hit",$arFields, "File: ".__FILE__."<br>Line: ".__LINE__);
						if ($ERROR_404=="N")
						{
							CStatistics::Set404("b_stat_searcher_hit", "ID = ".intval($id), array("URL_404" => "Y"));
						}
					}
				}
				else // it is not searcher
				{

					/************************************************
							Visitor section
					************************************************/

					/************************************************
						Variables which describe current page
					************************************************/

					$CURRENT_DIR = __GetCurrentDir();
					$CURRENT_PAGE = __GetCurrentPage();

					$CURRENT_PROTOCOL	= (CMain::IsHTTPS()) ? "https://" : "http://"; // protocol
					$CURRENT_PORT		= $_SERVER["SERVER_PORT"]; // port
					$CURRENT_HOST		= $_SERVER["HTTP_HOST"]; // domain
					$CURRENT_PAGE		= __GetFullRequestUri($CURRENT_PAGE);// w/o parameters
					$CURRENT_URI		= __GetFullRequestUri(); // with params
					$CURRENT_DIR		= __GetFullRequestUri($CURRENT_DIR); // catalog

					/************************************************
							Country detection
					************************************************/

					if(strlen($_SESSION["SESS_COUNTRY_ID"])<=0)
					{
						$obCity = new CCity;
						$_SESSION["SESS_COUNTRY_ID"] = $obCity->GetCountryCode();
						$_SESSION["SESS_CITY_ID"] = $obCity->GetCityID();
					}

					/************************************************
							IP => number
					************************************************/

					$REMOTE_ADDR_NUMBER = ip2number($_SERVER["REMOTE_ADDR"]);

					/************************************************
							Advertising campaign
					************************************************/

					CStatistics::Set_Adv();

					/************************************************
							Guest ID detection
					************************************************/

					$arGuest = CStatistics::Set_Guest();

					// Setup default advertising campaign
					if ($ADV_NA=="Y" && intval($_SESSION["SESS_ADV_ID"])<=0 && intval($_SESSION["SESS_LAST_ADV_ID"])<=0)
					{
						$_SESSION["referer1"] = COption::GetOptionString("statistic", "AVD_NA_REFERER1");
						$_SESSION["referer2"] = COption::GetOptionString("statistic", "AVD_NA_REFERER2");
						CStatistics::Set_Adv();
						$arGuest = CStatistics::Set_Guest();
					}

					/************************************************
							Session section
					************************************************/

					$_SESSION["SESS_SESSION_ID"] = intval($_SESSION["SESS_SESSION_ID"]);

					//session already exists
					if($_SESSION["SESS_SESSION_ID"] > 0)
					{
						$SESSION_NEW = "N";
						// update
						$arFields = Array(
							"USER_ID"		=> intval($_SESSION["SESS_LAST_USER_ID"]),
							"USER_AUTH"		=> "'".$IS_USER_AUTHORIZED."'",
							"USER_AGENT"		=> "'".$DB->ForSql($_SERVER["HTTP_USER_AGENT"],500)."'",
							"DATE_LAST"		=> $DB_now,
							"IP_LAST"		=> "'".$DB->ForSql($_SERVER["REMOTE_ADDR"],15)."'",
							"IP_LAST_NUMBER"	=> $REMOTE_ADDR_NUMBER,
							"HITS"			=> "HITS + 1",
							);
						$rows = $DB->Update("b_stat_session", $arFields, "WHERE ID='".$_SESSION["SESS_SESSION_ID"]."'", "File: ".__FILE__."<br>Line: ".__LINE__);
						// was cleaned up
						if (intval($rows)<=0)
						{
							// store as new
							$_SESSION["SESS_SESSION_ID"] = 0;
							if ($ADV_NA=="Y" && intval($_SESSION["SESS_ADV_ID"])<=0 && intval($_SESSION["SESS_LAST_ADV_ID"])<=0)
							{
								$_SESSION["referer1"] = COption::GetOptionString("statistic", "AVD_NA_REFERER1");
								$_SESSION["referer2"] = COption::GetOptionString("statistic", "AVD_NA_REFERER2");
							}
							CStatistics::Set_Adv();
							$arGuest = CStatistics::Set_Guest();
						}
					}

					// it is new session
					if($_SESSION["SESS_SESSION_ID"] <= 0)
					{
						$SESSION_NEW = "Y";

						// save session data
						$arFields = Array(
							"GUEST_ID"		=> intval($_SESSION["SESS_GUEST_ID"]),
							"NEW_GUEST"		=> "'".$DB->ForSql($_SESSION["SESS_GUEST_NEW"])."'",
							"USER_ID"		=> intval($_SESSION["SESS_LAST_USER_ID"]),
							"USER_AUTH"		=> "'".$DB->ForSql($IS_USER_AUTHORIZED)."'",
							"URL_FROM"		=> "'".$DB->ForSql($_SERVER["HTTP_REFERER"],2000)."'",
							"URL_TO"		=> "'".$DB->ForSql($CURRENT_URI,2000)."'",
							"URL_TO_404"		=> "'".$DB->ForSql($ERROR_404)."'",
							"URL_LAST"		=> "'".$DB->ForSql($CURRENT_URI,2000)."'",
							"URL_LAST_404"		=> "'".$DB->ForSql($ERROR_404)."'",
							"USER_AGENT"		=> "'".$DB->ForSql($_SERVER["HTTP_USER_AGENT"],500)."'",
							"DATE_STAT"		=> $DB_now_date,
							"DATE_FIRST"		=> $DB_now,
							"DATE_LAST"		=> $DB_now,
							"IP_FIRST"		=> "'".$DB->ForSql($_SERVER["REMOTE_ADDR"],15)."'",
							"IP_FIRST_NUMBER"	=> "'".$DB->ForSql($REMOTE_ADDR_NUMBER)."'",
							"IP_LAST"		=> "'".$DB->ForSql($_SERVER["REMOTE_ADDR"],15)."'",
							"IP_LAST_NUMBER"	=> "'".$DB->ForSql($REMOTE_ADDR_NUMBER)."'",
							"PHPSESSID"		=> "'".$DB->ForSql(session_id(),255)."'",
							"STOP_LIST_ID"		=> "'".$DB->ForSql($STOP_LIST_ID)."'",
							"COUNTRY_ID"		=> "'".$DB->ForSql($_SESSION["SESS_COUNTRY_ID"],2)."'",
							"CITY_ID"		=> $_SESSION["SESS_CITY_ID"] > 0? intval($_SESSION["SESS_CITY_ID"]): "null",
							"ADV_BACK"		=> "null",
							"FIRST_SITE_ID"		=> $sql_site,
							"LAST_SITE_ID"		=> $sql_site,
							"HITS"			=> 1,
							);

						// campaign?
						if (intval($_SESSION["SESS_ADV_ID"])>0)
						{
							$arFields["ADV_ID"] = intval($_SESSION["SESS_ADV_ID"]);
							$arFields["ADV_BACK"] = "'N'";
							$arFields["REFERER1"] = "'".$DB->ForSql($_SESSION["referer1"],255)."'";
							$arFields["REFERER2"] = "'".$DB->ForSql($_SESSION["referer2"],255)."'";
							$arFields["REFERER3"] = "'".$DB->ForSql($_SESSION["referer3"],255)."'";
						}
						elseif (intval($_SESSION["SESS_LAST_ADV_ID"])>0) // comeback?
						{
							$arFields["ADV_ID"] = intval($_SESSION["SESS_LAST_ADV_ID"]);
							$arFields["ADV_BACK"] = "'Y'";
							$arFields["REFERER1"] = "'".$DB->ForSql($arGuest["last_referer1"],255)."'";
							$arFields["REFERER2"] = "'".$DB->ForSql($arGuest["last_referer2"],255)."'";
						}

						// look for the same IP?
						$day_host_counter = 1;
						$day_host_counter_site = strlen($SITE_ID)>0? 1: 0;
						$strSql = "
							SELECT S.FIRST_SITE_ID
							FROM b_stat_session S
							WHERE S.IP_FIRST_NUMBER = ".$REMOTE_ADDR_NUMBER."
								AND S.DATE_STAT=".$DB_now_date."
						";
						$e = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
						while($er = $e->Fetch())
						{
							$day_host_counter = 0;
							if ($SITE_ID==$er["FIRST_SITE_ID"])
							{
								$day_host_counter_site = 0;
								break;
							}
						}

						$_SESSION["SESS_SESSION_ID"] = intval($DB->Insert("b_stat_session",$arFields, "File: ".__FILE__."<br>Line: ".__LINE__));

						if ($ERROR_404=="N")
						{
							CStatistics::Set404("b_stat_session", "ID = ".$_SESSION["SESS_SESSION_ID"], array("URL_TO_404" => "Y", "URL_LAST_404" => "Y"));
						}

						$day_guest_counter = 0;
						$new_guest_counter = 0;
						// new guest
						if ($_SESSION["SESS_GUEST_NEW"]=="Y")
						{
							// update day statistic
							$day_guest_counter = 1;
							$new_guest_counter = 1;
						}
						else // guest was here
						{
							// first hit for today
							if ($_SESSION["SESS_LAST"]!="Y")
							{
								// update day statistic
								$day_guest_counter = 1;
								$_SESSION["SESS_LAST"] = "Y";
							}
						}

						// update day counter
						$arFields = Array(
							"SESSIONS"	=> 1,
							"C_HOSTS"	=> intval($day_host_counter),
							"GUESTS"	=> intval($day_guest_counter),
							"NEW_GUESTS"	=> intval($new_guest_counter),
							"SESSION"	=> 1,
							"HOST"		=> intval($day_host_counter),
							"GUEST"		=> intval($day_guest_counter),
							"NEW_GUEST"	=> intval($new_guest_counter),
						);
						// when current day is already exists
						// we have to update it
						$rows = CTraffic::IncParam($arFields);
						if ($rows!==false && $rows<=0)
						// otherwise
						{
							// add new one
							CStatistics::SetNewDay(
								1,				// HOSTS
								0,				// TOTAL_HOSTS (now ignored)
								1,				// SESSIONS
								0,				// HITS
								intval($new_guest_counter),	// NEW_GUESTS
								1				// GUESTS
							);

							// and update it
							CTraffic::IncParam(
								array(
									"SESSION"	=> 1,
									"HOST"		=> 1,
									"GUEST"		=> 1,
									"NEW_GUEST"	=> intval($new_guest_counter),
									)
								);
						}

						// site is not defined
						if (strlen($SITE_ID)>0)
						{
							// обновляем счетчик "по дням" для текущего сайта
							$arFields = Array(
								"SESSIONS"	=> 1,
								"C_HOSTS"	=> intval($day_host_counter_site),
								"SESSION"	=> 1,
								"HOST"		=> intval($day_host_counter_site),
							);
							// обновим счетчики траффика для текущего дня
							$rows = CTraffic::IncParam(array(), $arFields, $SITE_ID);
							// если текущего дня для сайта в базе еще нет то
							if ($rows!==false && intval($rows)<=0)
							{
								// добавляем его
								CStatistics::SetNewDayForSite(
									$SITE_ID,
									1,	// HOSTS
									0,	// TOTAL_HOSTS  (now ignored)
									1	// SESSIONS
									);

								// обновим счетчики траффика для текущего дня
								CTraffic::IncParam(
									array(),
									array(
										"SESSION"	=> 1,
										"HOST"		=> 1,
										),
									$SITE_ID
									);
							}
						}

						// если страна определена то
						if (strlen($_SESSION["SESS_COUNTRY_ID"])>0)
						{
							$arFields = Array(
								"SESSIONS"	=> 1,
								"NEW_GUESTS"	=> $new_guest_counter,
							);
							CStatistics::UpdateCountry($_SESSION["SESS_COUNTRY_ID"], $arFields);
						}

						if($_SESSION["SESS_CITY_ID"] > 0)
						{
							$arFields = Array(
								"SESSIONS"	=> 1,
								"NEW_GUESTS"	=> $new_guest_counter,
							);
							CStatistics::UpdateCity($_SESSION["SESS_CITY_ID"], $arFields);
						}

						// обновляем гостя
						$arFields = Array(
							"SESSIONS" => "SESSIONS + 1",
							"LAST_SESSION_ID" => $_SESSION["SESS_SESSION_ID"],
							"LAST_USER_AGENT" => "'".$DB->ForSql($_SERVER["HTTP_USER_AGENT"],500)."'",
							"LAST_COUNTRY_ID" => "'".$DB->ForSql($_SESSION["SESS_COUNTRY_ID"],2)."'",
							"LAST_CITY_ID" => $_SESSION["SESS_CITY_ID"] > 0? intval($_SESSION["SESS_CITY_ID"]): "null",
						);
						//
						if($obCity)
						{
							$arFields["LAST_CITY_INFO"] = "'".$obCity->ForSQL()."'";
						}
						// если это прямой заход по рекламной кампании то
						if (intval($_SESSION["SESS_ADV_ID"])>0)
						{
							// обновляем рекламную кампанию последнего захода гостя
							$arFields["LAST_ADV_ID"] = intval($_SESSION["SESS_ADV_ID"]);
							$arFields["LAST_ADV_BACK"] = "'N'";
							$arFields["LAST_REFERER1"] = "'".$DB->ForSql($_SESSION["referer1"],255)."'";
							$arFields["LAST_REFERER2"] = "'".$DB->ForSql($_SESSION["referer2"],255)."'";
							$arFields["LAST_REFERER3"] = "'".$DB->ForSql($_SESSION["referer3"],255)."'";
						}
						elseif (intval($_SESSION["SESS_LAST_ADV_ID"])>0) // иначе если это возврат то
						{
							// взводим флаг возврата на последнем заходе гостя
							$arFields["LAST_ADV_BACK"] = "'Y'";
							$arFields["LAST_REFERER1"] = "'".$DB->ForSql($arGuest["last_referer1"],255)."'";
							$arFields["LAST_REFERER2"] = "'".$DB->ForSql($arGuest["last_referer2"],255)."'";
						}

						if ($_SESSION["SESS_GUEST_NEW"]=="Y")
							$arFields["FIRST_SESSION_ID"] = $_SESSION["SESS_SESSION_ID"];
						$rows = $DB->Update("b_stat_guest",$arFields,"WHERE ID=".intval($_SESSION["SESS_GUEST_ID"]),"File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);

						// обновляем рекламные кампании
						if (intval($_SESSION["SESS_ADV_ID"])>0 || intval($_SESSION["SESS_LAST_ADV_ID"])>0)
						{
							CStatistics::Update_Adv();
						}

						/************************************************
								Referring sites
						************************************************/
						if(
							$SAVE_REFERERS != "N"
							&& __GetReferringSite($PROT, $SN, $SN_WithoutPort, $PAGE_FROM)
							&& strlen($SN) > 0
							&& $SN != $_SERVER["HTTP_HOST"]
						)
						{
							$REFERER_LIST_ID = CStatistics::GetRefererListID($PROT, $SN, $PAGE_FROM, $CURRENT_URI, $ERROR_404, $sql_site);

							/************************************************
									Search phrases
							************************************************/

							if (substr($SN,0,4)=="www.")
								$sql = "('".$DB->ForSql(substr($SN,4),255)."' like P.DOMAIN or '".$DB->ForSql($SN,255)."' like P.DOMAIN)";
							else
								$sql = "'".$DB->ForSql($SN,255)."' like P.DOMAIN";
							$strSql = "
								SELECT
									S.ID,
									S.NAME,
									P.DOMAIN,
									P.VARIABLE,
									P.CHAR_SET
								FROM
									b_stat_searcher S,
									b_stat_searcher_params P
								WHERE
									S.ACTIVE='Y'
								and	P.SEARCHER_ID = S.ID
								and	".$sql."
							";
							$q = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
							if ($qr = $q->Fetch())
							{
								$_SESSION["FROM_SEARCHER_ID"] = $qr["ID"];
								$FROM_SEARCHER_NAME = $qr["NAME"];
								$FROM_SEARCHER_PHRASE = "";
								if (strlen($qr["VARIABLE"])>0)
								{
									$page=substr($PAGE_FROM, strpos($PAGE_FROM, "?")+1);
									$bIsUTF8 = is_utf8_url($page);
									parse_str($page, $arr);
									$arrVar = explode(",",$qr["VARIABLE"]);
									foreach ($arrVar as $var)
									{
										$var = trim($var);
										$phrase = $arr[$var];

										if (get_magic_quotes_gpc())
											$phrase = stripslashes($phrase);

										if($bIsUTF8)
										{
											$phrase_temp = trim($APPLICATION->ConvertCharset($phrase, "utf-8", LANG_CHARSET));
											if(strlen($phrase_temp))
												$phrase = $phrase_temp;
										}
										elseif(strlen($qr["CHAR_SET"]) > 0)
										{
											$phrase_temp = trim($APPLICATION->ConvertCharset($phrase, $qr["CHAR_SET"], LANG_CHARSET));
											if(strlen($phrase_temp))
												$phrase = $phrase_temp;
										}

										$phrase = trim($phrase);
										if(strlen($phrase))
										{
											$FROM_SEARCHER_PHRASE .= (strlen($FROM_SEARCHER_PHRASE)>0) ? " / ".$phrase : $phrase;
										}
									}
								}
								//echo "FROM_SEARCHER_PHRASE = ".$FROM_SEARCHER_PHRASE."<br>\n";
								// если извлекли поисковую фразу, то занесем ее в базу
								if (strlen($FROM_SEARCHER_PHRASE)>0)
								{
									$arFields = Array(
										"DATE_HIT" => $DB_now,
										"SEARCHER_ID" => intval($_SESSION["FROM_SEARCHER_ID"]),
										"REFERER_ID" => $REFERER_LIST_ID,
										"PHRASE" => "'".$DB->ForSql($FROM_SEARCHER_PHRASE,255)."'",
										"URL_FROM" => "'".$DB->ForSql($PROT.$SN.$PAGE_FROM,2000)."'",
										"URL_TO" => "'".$DB->ForSql($CURRENT_URI,2000)."'",
										"URL_TO_404" => "'".$ERROR_404."'",
										"SESSION_ID" => $_SESSION["SESS_SESSION_ID"],
										"SITE_ID" => $sql_site,
									);
									$id = $DB->Insert("b_stat_phrase_list", $arFields, "File: ".__FILE__."<br>Line: ".__LINE__);
									if ($ERROR_404=="N")
									{
										CStatistics::Set404("b_stat_phrase_list", "ID = ".intval($id), array("URL_TO_404" => "Y"));
									}

									// запомним поисковую фразу в сессии
									$_SESSION["SESS_SEARCH_PHRASE"] = $FROM_SEARCHER_PHRASE;

									// увеличим счетчик фраз у поисковой системы
									$_SESSION["SESS_FROM_SEARCHERS"][] = $_SESSION["FROM_SEARCHER_ID"];
									$arFields = Array("PHRASES" => "PHRASES + 1");
									$rows = $DB->Update("b_stat_searcher",$arFields,"WHERE ID=".intval($_SESSION["FROM_SEARCHER_ID"]), "File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);

								}
							}
						}
					}

					/************************************************
								Hits
					************************************************/

					if($_SESSION["SESS_SESSION_ID"] > 0)
					{
						if ($SAVE_HITS!="N")
						{
							// добавляем хит
							$arFields = Array(
								"SESSION_ID" => $_SESSION["SESS_SESSION_ID"],
								"DATE_HIT" => $DB_now,
								"GUEST_ID" => intval($_SESSION["SESS_GUEST_ID"]),
								"NEW_GUEST" => "'".$DB->ForSql($_SESSION["SESS_GUEST_NEW"])."'",
								"USER_ID" => intval($_SESSION["SESS_LAST_USER_ID"]),
								"USER_AUTH" => "'".$IS_USER_AUTHORIZED."'",
								"URL" => "'".$DB->ForSql($CURRENT_URI,2000)."'",
								"URL_404" => "'".$ERROR_404."'",
								"URL_FROM" => "'".$DB->ForSql(isset($_SERVER["HTTP_REFERER"])? $_SERVER["HTTP_REFERER"]: "", 2000)."'",
								"IP" => "'".$DB->ForSql($_SERVER["REMOTE_ADDR"],15)."'",
								"METHOD" => "'".$DB->ForSql($_SERVER["REQUEST_METHOD"],10)."'",
								"COOKIES" => "'".$DB->ForSql(GetCookieString(),2000)."'",
								"USER_AGENT" => "'".$DB->ForSql($_SERVER["HTTP_USER_AGENT"],500)."'",
								"STOP_LIST_ID" => "'".$STOP_LIST_ID."'",
								"COUNTRY_ID" => "'".$DB->ForSql($_SESSION["SESS_COUNTRY_ID"],2)."'",
								"CITY_ID" => $_SESSION["SESS_CITY_ID"] > 0? intval($_SESSION["SESS_CITY_ID"]): "null",
								"SITE_ID" => $sql_site,
							);
							self::$HIT_ID = intval($DB->Insert("b_stat_hit", $arFields, "File: ".__FILE__."<br>Line: ".__LINE__));
							if($ERROR_404=="N")
							{
								CStatistics::Set404("b_stat_hit", "ID = ".self::$HIT_ID, array("URL_404" => "Y"));
							}
						}

						// если гость на данном хите добавил в фавориты и до этого еще не добавлял то
						$favorites_counter = 0;
						if ($FAVORITES=="Y" && $_SESSION["SESS_GUEST_FAVORITES"]=="N")
						{
							$ALLOW_ADV_FAVORITES = "Y";
							$_SESSION["SESS_GUEST_FAVORITES"] = "Y";
							$favorites_counter = 1;
						}
						// обновляем счетчик "по дням"
						$arFields = Array(
							"HITS"		=> 1,
							"FAVORITES"	=> $favorites_counter,
							"HIT"		=> 1,
							"FAVORITE"	=> $favorites_counter,
						);
						// если текущий день есть в базе то
						// обновим счетчики траффика для текущего дня
						$rows = CTraffic::IncParam($arFields);
						if($rows!==false && intval($rows)<=0)
						{
							// если текущий день не определен в базе то
							// добавляем его
							$new_guest_counter = ($_SESSION["SESS_GUEST_NEW"]=="Y") ? 1 : 0;
							CStatistics::SetNewDay(
								1,				// HOSTS
								0,				// TOTAL_HOSTS (now ignored)
								1,				// SESSIONS
								1,				// HITS
								$new_guest_counter,		// NEW_GUESTS
								1,				// GUESTS
								$favorites_counter		// FAVORITES
								);

							// обновим счетчики траффика для текущего дня
							CTraffic::IncParam(
								array(
									"SESSION"	=> 1,
									"HIT"		=> 1,
									"HOST"		=> 1,
									"GUEST"		=> 1,
									"NEW_GUEST"	=> $new_guest_counter,
									"FAVORITE"	=> $favorites_counter
									)
								);
						}

						// если сайт определен то
						if (strlen($SITE_ID)>0)
						{
							// обновляем счетчик "по дням"
							$arFields = Array(
								"HITS" => 1,
								"HIT" => 1,
							);
							// если текущий день сайта определен в базе то
							// обновим счетчики траффика для текущего дня
							$rows = CTraffic::IncParam(array(), $arFields, $SITE_ID);
							if($rows!==false && intval($rows)<=0)
							{
								// если текущий день сайта не определен в базе то
								// добавляем его
								CStatistics::SetNewDayForSite(
									$SITE_ID,
									1,			// HOSTS
									0,			// TOTAL_HOSTS (now ignored)
									1,			// SESSIONS
									1			// HITS
									);

								// обновим счетчики траффика для текущего дня
								CTraffic::IncParam(
									array(),
									array(
										"SESSION"	=> 1,
										"HIT"		=> 1,
										"HOST"		=> 1,
										),
									$SITE_ID
									);
							}
						}

						/************************************************
										Пути по сайту
						************************************************/

						if ($SAVE_PATH_DATA!="N")
							CStatistics::SavePathData($SITE_ID, $CURRENT_PAGE, $ERROR_404);

						/************************************************
									Посещение разделов и страниц
						************************************************/

						if ($SAVE_VISITS!="N")
							CStatistics::SaveVisits($sql_site, $SESSION_NEW, $CURRENT_DIR, $CURRENT_PAGE, $ERROR_404);

						// обновляем сессию
						$arFields = Array(
							//"HITS"			=> "HITS + 1",
							"LAST_HIT_ID"	=> self::$HIT_ID,
							"URL_LAST"		=> "'".$DB->ForSql($CURRENT_URI,2000)."'",
							"URL_LAST_404"	=> "'".$ERROR_404."'",
							"DATE_LAST"		=> $DB_now,
							"LAST_SITE_ID"	=> $sql_site
							);
						if ($SESSION_NEW=="Y") $arFields["FIRST_HIT_ID"] = self::$HIT_ID;
						if ($FAVORITES=="Y") $arFields["FAVORITES"] = "'Y'";
						$DB->Update("b_stat_session",$arFields,"WHERE ID=".$_SESSION["SESS_SESSION_ID"], "File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
						if ($ERROR_404=="N")
						{
							CStatistics::Set404("b_stat_session", "ID = ".$_SESSION["SESS_SESSION_ID"], array("URL_LAST_404" => "Y"));
						}

						// обновляем гостя
						$arFields = Array(
							"HITS"			=> "HITS + 1",
							"LAST_SESSION_ID"	=> $_SESSION["SESS_SESSION_ID"],
							"LAST_DATE"		=> $DB_now,
							"LAST_USER_ID"		=> intval($_SESSION["SESS_LAST_USER_ID"]),
							"LAST_USER_AUTH"	=> "'".$IS_USER_AUTHORIZED."'",
							"LAST_URL_LAST"		=> "'".$DB->ForSql($CURRENT_URI,2000)."'",
							"LAST_URL_LAST_404"	=> "'".$ERROR_404."'",
							"LAST_USER_AGENT"	=> "'".$DB->ForSql($_SERVER["HTTP_USER_AGENT"],500)."'",
							"LAST_IP"		=> "'".$DB->ForSql($_SERVER["REMOTE_ADDR"],15)."'",
							"LAST_COOKIE"		=> "'".$DB->ForSql(GetCookieString(),2000)."'",
							"LAST_LANGUAGE"		=> "'".$DB->ForSql($_SERVER["HTTP_ACCEPT_LANGUAGE"],255)."'",
							"LAST_SITE_ID"		=> $sql_site
							);
						if ($FAVORITES=="Y") $arFields["FAVORITES"] = "'Y'";
						$DB->Update("b_stat_guest",$arFields,"WHERE ID=".intval($_SESSION["SESS_GUEST_ID"]),"File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
						if ($ERROR_404=="N")
						{
							CStatistics::Set404("b_stat_guest", "ID = ".intval($_SESSION["SESS_GUEST_ID"]), array("LAST_URL_LAST_404" => "Y"));
						}

						// обновляем прямые рекламные кампании
						if (intval($_SESSION["SESS_ADV_ID"])>0)
						{
							// увеличиваем счетчик хитов на прямом заходе
							$arFields = Array(
								"DATE_LAST"	=> $DB_now,
								"HITS"		=> "HITS+1"
								);
							if ($FAVORITES=="Y" && $ALLOW_ADV_FAVORITES=="Y")
							{
								// увеличиваем счетчик посетителей добавивших в избранное на прямом заходе
								$arFields["FAVORITES"] = "FAVORITES + 1";
								$favorite = 1;
							}
							$DB->Update("b_stat_adv",$arFields,"WHERE ID=".intval($_SESSION["SESS_ADV_ID"]), "File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);

							// обновляем счетчик хитов по дням
							$arFields = Array("HITS" => "HITS+1", "FAVORITES" => "FAVORITES + ".intval($favorite));
							$rows = $DB->Update("b_stat_adv_day",$arFields,"WHERE ADV_ID=".intval($_SESSION["SESS_ADV_ID"])." and DATE_STAT=".$DB_now_date,"File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
							// если его нет то
							if (intval($rows)<=0)
							{
								// добавляем его
								$arFields = Array(
									"ADV_ID"		=> intval($_SESSION["SESS_ADV_ID"]),
									"DATE_STAT"		=> $DB_now_date,
									"HITS"			=> 1,
									"FAVORITES"		=> intval($favorite)
									);
								$DB->Insert("b_stat_adv_day",$arFields, "File: ".__FILE__."<br>Line: ".__LINE__);
							}
						}
						// обновляем рекламные кампании по возврату
						elseif (intval($_SESSION["SESS_LAST_ADV_ID"])>0)
						{
							// увеличиваем счетчик хитов на возврате
							$arFields = Array(
								"DATE_LAST"		=> $DB_now,
								"HITS_BACK"		=> "HITS_BACK+1"
								);
							if ($FAVORITES=="Y" && $ALLOW_ADV_FAVORITES=="Y")
							{
								// увеличиваем счетчик посетителей добавивших в избранное на возврате
								$arFields["FAVORITES_BACK"] = "FAVORITES_BACK + 1";
								$favorite = 1;
							}
							$DB->Update("b_stat_adv",$arFields,"WHERE ID=".intval($_SESSION["SESS_LAST_ADV_ID"]), "File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);

							$arFields = Array("HITS_BACK" => "HITS_BACK+1", "FAVORITES_BACK" => "FAVORITES_BACK + ".intval($favorite));
							// обновляем счетчик хитов по дням
							$rows = $DB->Update("b_stat_adv_day",$arFields,"WHERE ADV_ID=".intval($_SESSION["SESS_LAST_ADV_ID"])." and DATE_STAT=".$DB_now_date,"File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
							// если его нет то
							if (intval($rows)<=0)
							{
								// добавляем его
								$arFields = Array(
									"ADV_ID" => intval($_SESSION["SESS_LAST_ADV_ID"]),
									"DATE_STAT" => $DB_now_date,
									"HITS_BACK" => 1,
									"FAVORITES_BACK" => intval($favorite),
								);
								$DB->Insert("b_stat_adv_day",$arFields, "File: ".__FILE__."<br>Line: ".__LINE__);
							}
						}

						// обрабатываем событие
						if (defined("GENERATE_EVENT") && GENERATE_EVENT=="Y")
						{
							global $event1, $event2, $event3, $goto, $money, $currency, $site_id;
							if(strlen($site_id) <= 0)
								$site_id = false;
							CStatistics::Set_Event($event1, $event2, $event3, $goto, $money, $currency, $site_id);
						}

						// увеличиваем счетчик хитов у страны
						if (strlen($_SESSION["SESS_COUNTRY_ID"])>0)
						{
							CStatistics::UpdateCountry($_SESSION["SESS_COUNTRY_ID"], Array("HITS" => 1));
						}

						if($_SESSION["SESS_CITY_ID"] > 0)
						{
							CStatistics::UpdateCity($_SESSION["SESS_CITY_ID"], Array("HITS" => 1));
						}

						if (
							isset($_SESSION["SESS_FROM_SEARCHERS"])
							&& is_array($_SESSION["SESS_FROM_SEARCHERS"])
							&& !empty($_SESSION["SESS_FROM_SEARCHERS"])
						)
						{
							// обновляем счетчик хитов у поисковых фраз для поисковиков
							$arFields = Array("PHRASES_HITS" => "PHRASES_HITS+1");
							$_SESSION["SESS_FROM_SEARCHERS"] = array_unique($_SESSION["SESS_FROM_SEARCHERS"]);
							if(count($_SESSION["SESS_FROM_SEARCHERS"]) > 0)
							{
								$str = "0";
								foreach($_SESSION["SESS_FROM_SEARCHERS"] as $value)
									$str .= ", ".intval($value);
								$DB->Update("b_stat_searcher",$arFields,"WHERE ID in ($str)", "File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
							}
						}

						if (isset($_SESSION["SESS_REFERER_ID"]) && intval($_SESSION["SESS_REFERER_ID"])>0)
						{
							// обновляем ссылающиеся
							$arFields = Array("HITS"=>"HITS+1");
							$DB->Update("b_stat_referer", $arFields, "WHERE ID=".intval($_SESSION["SESS_REFERER_ID"]), "File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
						}
					}

					/*******************************************************
						Переменные хранящие параметры предыдущей страницы
					*******************************************************/

					$_SESSION["SESS_HTTP_REFERER"] = $_SESSION["SESS_LAST_URI"];
					$_SESSION["SESS_LAST_PROTOCOL"] = $CURRENT_PROTOCOL;
					$_SESSION["SESS_LAST_PORT"] = $CURRENT_PORT;
					$_SESSION["SESS_LAST_HOST"] = $CURRENT_HOST;
					$_SESSION["SESS_LAST_URI"] = $CURRENT_URI;
					$_SESSION["SESS_LAST_PAGE"] = $CURRENT_PAGE;
					$_SESSION["SESS_LAST_DIR"] = $CURRENT_DIR;
				}
			}
			else // if (!$BLOCK_ACTIVITY)
			{
				/************************************************
					Обработка превышения лимита активности
				*************************************************/

				$fname = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/activity_limit.php";
				if(file_exists($fname))
				{
					include($fname);
				}
				else
				{
					CHTTP::SetStatus("503 Service Unavailable");
					die();
				}
			}

			/************************************************************
				Обрабатываем ситуацию когда не поддерживаются
				сессии и/или не сохраняются куки
			*************************************************************/

			// если мы делали select из таблицы b_stat_session_data то
			if($SESSION_DATA_ID)
			{
				$arrSTAT_SESSION = stat_session_register(true);
				$sess_data_for_db = (strtolower($DB->type)=="oracle") ? "'".$DB->ForSql(serialize($arrSTAT_SESSION), 2000)."'" :  "'".$DB->ForSql(serialize($arrSTAT_SESSION))."'";
				// если в результате этого select'а были выбраны данные то
				if((intval($SESSION_DATA_ID) > 0) && ($SESSION_DATA_ID !== true))
				{
					// обновляем их
					$arFields = array(
						"DATE_LAST" => $DB_now,
						"GUEST_MD5" => "'".get_guest_md5()."'",
						"SESS_SESSION_ID" => intval($_SESSION["SESS_SESSION_ID"]),
						"SESSION_DATA" => $sess_data_for_db
					);
					$DB->Update("b_stat_session_data", $arFields, "WHERE ID = ".intval($SESSION_DATA_ID), "File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
				}
				else
				{
					// иначе вставляем эти данные
					$arFields = array(
						"DATE_FIRST" => $DB_now,
						"DATE_LAST" => $DB_now,
						"GUEST_MD5" => "'".get_guest_md5()."'",
						"SESS_SESSION_ID" => intval($_SESSION["SESS_SESSION_ID"]),
						"SESSION_DATA" => $sess_data_for_db
						);
					$DB->Insert("b_stat_session_data", $arFields, "File: ".__FILE__."<br>Line: ".__LINE__);
				}
			}
		} // if ($STOP_SAVE_STATISTIC!="N" or $STOP!="Y")

		if ($STOP=="Y")
		{
			$z = CLanguage::GetByID($STOP_MESSAGE_LID);
			$zr = $z->Fetch();
			$charset = (strlen($zr["CHARSET"])>0) ? $zr["CHARSET"] : "windows-1251";

			//We have URL with no MESSAGE
			if((strlen($STOP_REDIRECT_URL) > 0) && (strlen($STOP_MESSAGE) <= 0))
			{//So just do redirect
				LocalRedirect($STOP_REDIRECT_URL, true);
			}
			//We have some to say
			elseif(strlen($STOP_MESSAGE)>0)
			{
				$STOP_MESSAGE .= " [".$STOP_LIST_ID."]";
echo '<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset='.$charset.'">
		'.(strlen($STOP_REDIRECT_URL) > 0? '<meta http-equiv="Refresh" content="3;URL='.htmlspecialcharsbx($STOP_REDIRECT_URL).'">': '').'
	</head>
	<body>
		<div align="center"><h3>'.$STOP_MESSAGE.'</h3></div>
	</body>
</html>';
			}
			die();
		}
	}

public static 	function RestoreSession()
	{
		global $APPLICATION;
		// if there is no session ID
		if(intval($_SESSION["SESS_SESSION_ID"]) <= 0)
		{
			if(COption::GetOptionString("statistic", "SAVE_SESSION_DATA") == "Y")
			{
				// try to use coockie
				$COOKIE_GUEST_ID = intval($APPLICATION->get_cookie("GUEST_ID"));
				if($COOKIE_GUEST_ID <= 0)
				{
					// restore session data from b_stat_session_data
					$z = CStatistics::GetSessionDataByMD5(get_guest_md5());
					if($zr = $z->Fetch())
					{
						$arrSESSION_DATA = unserialize($zr["SESSION_DATA"]);
						if(is_array($arrSESSION_DATA))
						{
							foreach($arrSESSION_DATA as $key => $value)
								$_SESSION[$key] = $value;
						}
						return intval($zr["ID"]); //Guest was found
					}
					return true; //Just tried to restore session data
				}
			}
		}
		return false; //We have no choice to restore guest session
	}

	// обновляем счетчики сессий и новых посетителей у страны
public static 	function UpdateCountry($COUNTRY_ID, $arFields, $DATE=false, $DATE_FORMAT="SHORT", $SIGN="+")
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$COUNTRY_ID = $DB->ForSql($COUNTRY_ID, 2);

		$ar = array();
		foreach($arFields as $name=>$value)
		{
			$ar[$name] = $name.$SIGN.intval($value);
		}
		$DB->Update("b_stat_country", $ar, "WHERE ID='".$COUNTRY_ID."'","",false,false,false);

		$rows = $DB->Update("b_stat_country_day", $ar, "WHERE COUNTRY_ID='".$COUNTRY_ID."' and  ".CStatistics::DBDateCompare("DATE_STAT", $DATE, $DATE_FORMAT),"",false,false,false);
		if(intval($rows)<=0 && $SIGN=="+")
		{
			$ar = array();
			foreach($arFields as $name=>$value)
			{
				$ar[$name] = intval($value);
			}
			$ar["COUNTRY_ID"]="'".$COUNTRY_ID."'";
			$ar["DATE_STAT"]= $DB->GetNowDate();
			$DB->Insert("b_stat_country_day",$ar);
		}
		elseif(intval($rows)>1) // если обновили более одного дня то
		{
			// удалим лишние
			$rs = $DB->Query("SELECT ID FROM b_stat_country_day WHERE COUNTRY_ID='".$COUNTRY_ID."' and  ".CStatistics::DBDateCompare("DATE_STAT", $DATE, $DATE_FORMAT)." ORDER BY ID", false);
			$ar = $rs->Fetch();
			while($ar = $rs->Fetch())
			{
				$DB->Query("DELETE FROM b_stat_country_day WHERE ID = ".$ar["ID"], false);
			}
		}
	}

public static 	function UpdateCity($CITY_ID, $arFields, $DATE=false, $DATE_FORMAT="SHORT", $SIGN="+")
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$CITY_ID = intval($CITY_ID);

		$ar = array();
		foreach($arFields as $name=>$value)
		{
			$ar[$name] = $name.$SIGN.intval($value);
		}
		$DB->Update("b_stat_city", $ar, "WHERE ID = ".$CITY_ID, "", false, false, false);

		$rows = $DB->Update("b_stat_city_day", $ar, "WHERE CITY_ID = ".$CITY_ID." and ".CStatistics::DBDateCompare("DATE_STAT", $DATE, $DATE_FORMAT),"",false,false,false);
		if(intval($rows)<=0 && $SIGN=="+")
		{
			$ar = array();
			foreach($arFields as $name=>$value)
			{
				$ar[$name] = intval($value);
			}
			$ar["CITY_ID"] = $CITY_ID;
			$ar["DATE_STAT"] = $DB->GetNowDate();
			$DB->Insert("b_stat_city_day", $ar);
		}
		elseif(intval($rows)>1) // если обновили более одного дня то
		{
			// удалим лишние
			$rs = $DB->Query("SELECT ID FROM b_stat_city_day WHERE CITY_ID = ".$CITY_ID." and ".CStatistics::DBDateCompare("DATE_STAT", $DATE, $DATE_FORMAT)." ORDER BY ID", false);
			$ar = $rs->Fetch();
			while($ar = $rs->Fetch())
			{
				$DB->Query("DELETE FROM b_stat_city_day WHERE ID = ".$ar["ID"], false);
			}
		}
	}

public static 	function SavePathData($SITE_ID, $CURRENT_PAGE, $ERROR_404)
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$DB_now = $DB->GetNowFunction();
		$DB_now_date = $DB->GetNowDate();
		$STEPS = intval(COption::GetOptionString("statistic", "MAX_PATH_STEPS"));

		if($_SESSION["SESS_LAST_PAGE"]==$CURRENT_PAGE)
			return;

		$COUNTER_ABNORMAL = 0; // счетчик показывающий сколько раз прошли по данному пути без поддержки HTTP_REFERER

		// получим ссылающуюся страницу
		if (strlen($_SERVER["HTTP_REFERER"])<=0)
		{
			if (strlen($_SESSION["SESS_LAST_PAGE"])>0) $COUNTER_ABNORMAL = 1;
			$PATH_REFERER = __GetFullReferer($_SESSION["SESS_LAST_PAGE"]);
		}
		else $PATH_REFERER = __GetFullReferer();

		if($PATH_REFERER==$CURRENT_PAGE)
			return;

		// получим из кэша данные по предыдущему пути: ID пути, набор страниц и т.д.
		if(strlen($PATH_REFERER)>0)
		{
			$where1 = " and C.PATH_LAST_PAGE = '".$DB->ForSql($PATH_REFERER,255)."'";
		}
		else
		{
			$where1 = " and (C.PATH_LAST_PAGE is null or ".$DB->Length("C.PATH_LAST_PAGE")."<=0)";
		}
		$strSql = CStatistics::DBTopSql("
			SELECT /*TOP*/
				C.ID as CACHE_ID,
				C.PATH_ID,
				C.PATH_PAGES,
				C.PATH_FIRST_PAGE,
				C.PATH_FIRST_PAGE_SITE_ID,
				C.PATH_FIRST_PAGE_404,
				C.PATH_STEPS,
				C.PATH_LAST_PAGE,
				C.IS_LAST_PAGE
			FROM
				b_stat_path_cache C
			WHERE
				C.SESSION_ID = ".intval($_SESSION['SESS_SESSION_ID'])."
			$where1
			ORDER BY
				C.ID desc
			", 1);

		$rsPREV_PATH = $DB->Query($strSql,false,"File: ".__FILE__."<br>Line: ".__LINE__);
		$arPREV_PATH = $rsPREV_PATH->Fetch();

		$arrUpdate404_1 = array();
		$arrUpdate404_2 = array();

		// сформируем переменные описывающие текущий путь
		$CURRENT_PATH_ID = GetStatPathID($CURRENT_PAGE, $arPREV_PATH["PATH_ID"]);
		$tmp_SITE_ID = (strlen($SITE_ID)>0) ? "[".$SITE_ID."] " : "";
		$CURRENT_PATH_PAGES_404 = $arPREV_PATH["PATH_PAGES"].$tmp_SITE_ID."ERROR_404: ".$CURRENT_PAGE."\n";

		if ($ERROR_404=="Y")
		{
			$CURRENT_PATH_PAGES = $CURRENT_PATH_PAGES_404;
		}
		else
		{
			$CURRENT_PATH_PAGES = $arPREV_PATH["PATH_PAGES"].$tmp_SITE_ID.$CURRENT_PAGE."\n";

			if(strtolower($DB->type)=="oracle")
				$arrUpdate404_1["PATH_PAGES"] = substr($CURRENT_PATH_PAGES_404, 0, 2000);
			elseif(strtolower($DB->type)=="mssql")
				$arrUpdate404_1["PATH_PAGES"] = substr($CURRENT_PATH_PAGES_404, 0, 7000);
			else
				$arrUpdate404_1["PATH_PAGES"] = $CURRENT_PATH_PAGES_404;

			$arrUpdate404_2["PAGES"] = $arrUpdate404_1["PATH_PAGES"];
		}

		$CURRENT_PATH_STEPS = intval($arPREV_PATH["PATH_STEPS"])+1;
		if (strlen($arPREV_PATH["PATH_FIRST_PAGE"])>0)
		{
			$FIRST_PAGE = $arPREV_PATH["PATH_FIRST_PAGE"];
			$FIRST_PAGE_SITE_ID = $arPREV_PATH["PATH_FIRST_PAGE_SITE_ID"];
			$FIRST_PAGE_404 = ($arPREV_PATH["PATH_FIRST_PAGE_404"]=="Y") ? "Y" : "N";
		}
		else
		{
			$FIRST_PAGE = $CURRENT_PAGE;
			$FIRST_PAGE_SITE_ID = $SITE_ID;
			$FIRST_PAGE_404 = $ERROR_404;

			if ($ERROR_404=="N")
			{
				$arrUpdate404_1["PATH_FIRST_PAGE_404"] = "Y";
				$arrUpdate404_2["FIRST_PAGE_404"] = "Y";
			}
		}

		if(strtolower($DB->type)=="oracle")
			$sql_CURRENT_PATH_PAGES = $DB->ForSql($CURRENT_PATH_PAGES, 2000);
		elseif(strtolower($DB->type)=="mssql")
			$sql_CURRENT_PATH_PAGES = $DB->ForSql($CURRENT_PATH_PAGES, 7000);
		else
			$sql_CURRENT_PATH_PAGES = $DB->ForSql($CURRENT_PATH_PAGES);

		$sql_FIRST_PAGE_SITE_ID = strlen($FIRST_PAGE_SITE_ID)>0 ? "'".$DB->ForSql($FIRST_PAGE_SITE_ID,2)."'" : "null";

		$sql_LAST_PAGE_SITE_ID = strlen($SITE_ID)>0 ? "'".$DB->ForSql($SITE_ID,2)."'" : "null";

		// вставим данный путь в кэш
		$arFields = array(
			"SESSION_ID"			=> intval($_SESSION['SESS_SESSION_ID']),
			"PATH_ID"			=> intval($CURRENT_PATH_ID),
			"PATH_PAGES"			=> "'".$sql_CURRENT_PATH_PAGES."'",
			"PATH_FIRST_PAGE"		=> "'".$DB->ForSql($FIRST_PAGE, 255)."'",
			"PATH_FIRST_PAGE_404"		=> "'".$DB->ForSql($FIRST_PAGE_404)."'",
			"PATH_FIRST_PAGE_SITE_ID"	=> $sql_FIRST_PAGE_SITE_ID,
			"PATH_LAST_PAGE"		=> "'".$DB->ForSql($CURRENT_PAGE,255)."'",
			"PATH_LAST_PAGE_404"		=> "'".$DB->ForSql($ERROR_404)."'",
			"PATH_LAST_PAGE_SITE_ID"	=> $sql_LAST_PAGE_SITE_ID,
			"PATH_STEPS"			=> $CURRENT_PATH_STEPS,
			"DATE_HIT"			=> $DB_now,
			"IS_LAST_PAGE"			=> "'Y'"
			);
		$id = $DB->Insert("b_stat_path_cache",$arFields, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($ERROR_404=="N")
		{
			$arrUpdate404_1["PATH_LAST_PAGE_404"] = "Y";
			$arrUpdate404_2["LAST_PAGE_404"] = "Y";
		}
		CStatistics::Set404("b_stat_path_cache", "ID = ".intval($id), $arrUpdate404_1);

		// увеличим счетчик динамики по текущему пути
		$arFields = array(
			"COUNTER"		=> "COUNTER + 1",
			"COUNTER_FULL_PATH"	=> "COUNTER_FULL_PATH + 1",
			"COUNTER_ABNORMAL"	=> "COUNTER_ABNORMAL + ".intval($COUNTER_ABNORMAL),
		);
		$rows = $DB->Update("b_stat_path",$arFields,"WHERE PATH_ID='".$CURRENT_PATH_ID."' and DATE_STAT=".$DB_now_date, "File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);

		if (intval($rows)<=0)
		{
			$sql_PARENT_PATH_ID = (strlen($arPREV_PATH["PATH_ID"])>0) ? $arPREV_PATH["PATH_ID"] : "null";
			$arFields = array(
				"PATH_ID"		=> intval($CURRENT_PATH_ID),
				"PARENT_PATH_ID"	=> $sql_PARENT_PATH_ID,
				"DATE_STAT"		=> $DB_now_date,
				"COUNTER"		=> 1,
				"COUNTER_FULL_PATH"	=> 1,
				"COUNTER_ABNORMAL"	=> intval($COUNTER_ABNORMAL),
				"PAGES"			=> "'".$sql_CURRENT_PATH_PAGES."'",
				"FIRST_PAGE"		=> "'".$DB->ForSql($FIRST_PAGE,255)."'",
				"FIRST_PAGE_SITE_ID"	=> $sql_FIRST_PAGE_SITE_ID,
				"FIRST_PAGE_404"	=> "'".$DB->ForSql($FIRST_PAGE_404)."'",
				"PREV_PAGE"		=> "'".$DB->ForSql($arPREV_PATH["PATH_LAST_PAGE"])."'",
				"PREV_PAGE_HASH"	=> crc32ex($arPREV_PATH["PATH_LAST_PAGE"]),
				"LAST_PAGE"		=> "'".$DB->ForSql($CURRENT_PAGE,255)."'",
				"LAST_PAGE_404"		=> "'".$DB->ForSql($ERROR_404)."'",
				"LAST_PAGE_SITE_ID"	=> $sql_LAST_PAGE_SITE_ID,
				"LAST_PAGE_HASH"	=> crc32ex($CURRENT_PAGE),
				"STEPS"			=> $CURRENT_PATH_STEPS
				);
			if($CURRENT_PATH_STEPS<=$STEPS)
			{
				$id = $DB->Insert("b_stat_path",$arFields, "File: ".__FILE__."<br>Line: ".__LINE__);
				CStatistics::Set404("b_stat_path", "ID = ".intval($id), $arrUpdate404_2);
			}
		}

		// если предыдущая страница считалась последней страницей в пути то
		if ($arPREV_PATH["IS_LAST_PAGE"]=="Y")
		{
			// сбросим счетчик конечных путей для предыдущей страницы
			$arFields = array("COUNTER_FULL_PATH" => "COUNTER_FULL_PATH - 1");
			$DB->Update("b_stat_path",$arFields,"WHERE PATH_ID='".$arPREV_PATH["PATH_ID"]."' and DATE_STAT=".$DB_now_date, "File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);

			// сбросим флаг того что предудущая страница - последняя страница в пути
			$arFields = array("IS_LAST_PAGE" => "'N'");
			$DB->Update("b_stat_path_cache",$arFields,"WHERE ID='".$arPREV_PATH["CACHE_ID"]."'","File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
		}

		// зафиксируем счетчик пути в связке с рекламной кампанией
		if (intval($_SESSION["SESS_ADV_ID"])>0)
		{
			$ADV_ID = intval($_SESSION["SESS_ADV_ID"]);
			$arFields = array(
				"COUNTER"		=> "COUNTER + 1",
				"COUNTER_FULL_PATH"	=> "COUNTER_FULL_PATH + 1"
			);
			$sql_COUNTER = 1;
			$sql_COUNTER_FULL_PATH = 1;
			$sql_COUNTER_BACK = 0;
			$sql_COUNTER_FULL_PATH_BACK = 0;
			$ADV_BACK = "N";
		}
		elseif (intval($_SESSION["SESS_LAST_ADV_ID"])>0)
		{
			$ADV_ID = intval($_SESSION["SESS_LAST_ADV_ID"]);
			$arFields = array(
				"COUNTER_BACK"			=> "COUNTER_BACK + 1",
				"COUNTER_FULL_PATH_BACK"	=> "COUNTER_FULL_PATH_BACK + 1"
			);
			$sql_COUNTER = 0;
			$sql_COUNTER_FULL_PATH = 0;
			$sql_COUNTER_BACK = 1;
			$sql_COUNTER_FULL_PATH_BACK = 1;
			$ADV_BACK = "Y";
		}
		else
			return; //ADV_ID == 0

		$rows = $DB->Update("b_stat_path_adv",$arFields,"WHERE ADV_ID=".intval($ADV_ID)." and PATH_ID='".$CURRENT_PATH_ID."' and DATE_STAT=".$DB_now_date, "File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
		if (intval($rows)<=0)
		{
			$arFields = array(
				"ADV_ID"			=> intval($ADV_ID),
				"PATH_ID"			=> intval($CURRENT_PATH_ID),
				"DATE_STAT"			=> $DB_now_date,
				"COUNTER"			=> $sql_COUNTER,
				"COUNTER_BACK"			=> $sql_COUNTER_BACK,
				"COUNTER_FULL_PATH"		=> $sql_COUNTER_FULL_PATH,
				"COUNTER_FULL_PATH_BACK"	=> $sql_COUNTER_FULL_PATH_BACK,
				"STEPS"				=> $CURRENT_PATH_STEPS,
			);
			if($CURRENT_PATH_STEPS<=$STEPS)
			{
				$DB->Insert("b_stat_path_adv", $arFields, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
		if ($arPREV_PATH["IS_LAST_PAGE"]=="Y")
		{
			if ($ADV_BACK=="N")
			{
				$arFields = array("COUNTER_FULL_PATH" => "COUNTER_FULL_PATH - 1");
				$DB->Update("b_stat_path_adv", $arFields, "WHERE ADV_ID='".$ADV_ID."' and PATH_ID='".$arPREV_PATH["PATH_ID"]."' and DATE_STAT=".$DB_now_date, "File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
			}
			elseif ($ADV_BACK=="Y")
			{
				$arFields = array("COUNTER_FULL_PATH_BACK" => "COUNTER_FULL_PATH_BACK - 1");
				$DB->Update("b_stat_path_adv", $arFields, "WHERE ADV_ID='".$ADV_ID."' and PATH_ID='".$arPREV_PATH["PATH_ID"]."' and DATE_STAT=".$DB_now_date, "File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
			}
		}
	}

public static 	function SaveVisits($sql_site, $SESSION_NEW, $CURRENT_DIR, $CURRENT_PAGE, $ERROR_404)
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$DB_now_date = $DB->GetNowDate();
		$enter_counter = ($SESSION_NEW=="Y") ? 1 : 0;
		if (strlen($CURRENT_DIR)>0 && strlen($CURRENT_PAGE)>0)
		{
			$LAST_DIR_ID = intval($_SESSION["SESS_LAST_DIR_ID"]);
			$LAST_PAGE_ID = intval($_SESSION["SESS_LAST_PAGE_ID"]);
			$CURRENT_DIR_ID = 0;
			$CURRENT_PAGE_ID = 0;
			$exit_dir_counter = 0; // счетчик точки выхода для раздела
			$exit_page_counter = 0; // счетчик точки выхода для страницы
			if ($_SESSION["SESS_LAST_DIR"]!=$CURRENT_DIR || $_SESSION["SESS_LAST_PAGE"]!=$CURRENT_PAGE)
			{
				$strSql = "
					SELECT
						ID,
						DIR
					FROM
						b_stat_page
					WHERE
						DATE_STAT = ".$DB_now_date."
					and (
						(URL='".$DB->ForSql($CURRENT_DIR,2000)."' and DIR='Y') or
						(URL='".$DB->ForSql($CURRENT_PAGE,2000)."' and DIR='N')
						)
					";

				$rsID = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				while ($arID = $rsID->Fetch())
				{
					if ($arID["DIR"]=="Y") $CURRENT_DIR_ID = $arID["ID"];
					elseif ($arID["DIR"]=="N") $CURRENT_PAGE_ID = $arID["ID"];
				}
				if ($CURRENT_DIR_ID!=$LAST_DIR_ID) $exit_dir_counter = 1;
				if ($CURRENT_PAGE_ID!=$LAST_PAGE_ID) $exit_page_counter = 1;
			}
			else
			{
				$CURRENT_DIR_ID = $LAST_DIR_ID;
				$CURRENT_PAGE_ID = $LAST_PAGE_ID;
			}

			// определим ID рекламной кампании
			if (intval($_SESSION["SESS_ADV_ID"])>0)
			{
				$ADV_ID = intval($_SESSION["SESS_ADV_ID"]);
				$bADV_BACK = false;
			}
			elseif (intval($_SESSION["SESS_LAST_ADV_ID"])>0)
			{
				$ADV_ID = intval($_SESSION["SESS_LAST_ADV_ID"]);
				$bADV_BACK = true;
			}
			else
				$ADV_ID = 0;

			// обновляем раздел
			if ($LAST_DIR_ID>0 && $exit_dir_counter>0)
			{
				$arFields = array("EXIT_COUNTER" => "EXIT_COUNTER - 1");
				$DB->Update("b_stat_page",$arFields,"WHERE ID = '".$LAST_DIR_ID."'","File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
				if ($ADV_ID>0)
				{
					if ($bADV_BACK)
					{
						$arFields = array(
							"EXIT_COUNTER_BACK" => "EXIT_COUNTER_BACK - 1"
						);
					}
					$DB->Update("b_stat_page_adv",$arFields,"WHERE PAGE_ID = '".$LAST_DIR_ID."' and ADV_ID=".$ADV_ID,"File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
				}
			}

			$adv_rows_dir = 0;
			if ($CURRENT_DIR_ID>0)
			{
				$arFields = Array(
					"COUNTER"		=> "COUNTER + 1",
					"EXIT_COUNTER"		=> "EXIT_COUNTER + ".$exit_dir_counter,
					"ENTER_COUNTER"		=> "ENTER_COUNTER + ".$enter_counter
					);
				$DB->Update("b_stat_page",$arFields,"WHERE ID = '".$CURRENT_DIR_ID."'","File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
				if ($ADV_ID>0)
				{
					if ($bADV_BACK)
					{
						$arFields = Array(
							"COUNTER_BACK"			=> "COUNTER_BACK + 1",
							"EXIT_COUNTER_BACK"		=> "EXIT_COUNTER_BACK + ".$exit_dir_counter,
							"ENTER_COUNTER_BACK"	=> "ENTER_COUNTER_BACK + ".$enter_counter
							);
					}
					$adv_rows_dir = $DB->Update("b_stat_page_adv",$arFields,"WHERE PAGE_ID = '".$CURRENT_DIR_ID."' and ADV_ID = ".$ADV_ID, "File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
				}
			}
			else
			{
				$arFields = Array(
					"DATE_STAT"		=> $DB_now_date,
					"COUNTER"		=> 1,
					"EXIT_COUNTER"		=> 1,
					"ENTER_COUNTER"		=> $enter_counter,
					"DIR"			=> "'Y'",
					"URL"			=> "'".$DB->ForSql($CURRENT_DIR,2000)."'",
					"URL_HASH"		=> crc32ex($CURRENT_DIR),
					"SITE_ID"		=> $sql_site
					);
				$CURRENT_DIR_ID = $DB->Insert("b_stat_page",$arFields,"File: ".__FILE__."<br>Line: ".__LINE__);
			}
			$_SESSION["SESS_LAST_DIR_ID"] = $CURRENT_DIR_ID;

			if (intval($adv_rows_dir)<=0)
			{
				if ($ADV_ID>0)
				{
					$arFields = Array(
						"DATE_STAT"		=> $DB_now_date,
						"PAGE_ID"		=> $CURRENT_DIR_ID,
						"ADV_ID"		=> $ADV_ID,
						"COUNTER"		=> 1,
						"EXIT_COUNTER"		=> 1,
						"ENTER_COUNTER"		=> $enter_counter,
						"COUNTER_BACK"		=> 0,
						"EXIT_COUNTER_BACK"	=> 0,
						"ENTER_COUNTER_BACK"	=> 0
						);
					if ($bADV_BACK)
					{
						$arFields["COUNTER"] = 0;
						$arFields["EXIT_COUNTER"] = 0;
						$arFields["ENTER_COUNTER"] = 0;
						$arFields["COUNTER_BACK"] = 1;
						$arFields["EXIT_COUNTER_BACK"] = 1;
						$arFields["ENTER_COUNTER_BACK"] = $enter_counter;
					}
					$DB->Insert("b_stat_page_adv",$arFields,"File: ".__FILE__."<br>Line: ".__LINE__);
				}
			}

			// обновим страницу
			if ($LAST_PAGE_ID>0 && $exit_page_counter>0)
			{
				$arFields = array("EXIT_COUNTER" => "EXIT_COUNTER - 1");
				$DB->Update("b_stat_page",$arFields,"WHERE ID = '".$LAST_PAGE_ID."'","File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
				if ($ADV_ID>0)
				{
					if ($bADV_BACK)
					{
						$arFields = array(
							"EXIT_COUNTER_BACK" => "EXIT_COUNTER_BACK - 1"
						);
					}
					$DB->Update("b_stat_page_adv",$arFields,"WHERE PAGE_ID = '".$LAST_PAGE_ID."' and ADV_ID=".$ADV_ID,"File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
				}
			}

			$adv_rows_page = 0;
			if ($CURRENT_PAGE_ID>0)
			{
				$arFields = Array(
					"COUNTER"		=> "COUNTER + 1",
					"EXIT_COUNTER"		=> "EXIT_COUNTER + ".$exit_page_counter,
					"ENTER_COUNTER"		=> "ENTER_COUNTER + ".$enter_counter,
					"URL_404"		=> "'".$ERROR_404."'"
				);
				$DB->Update("b_stat_page",$arFields,"WHERE ID = '".$CURRENT_PAGE_ID."'","File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
				if ($ERROR_404=="N")
				{
					CStatistics::Set404("b_stat_page", "ID = ".intval($CURRENT_PAGE_ID), array("URL_404" => "Y"));
				}

				if ($ADV_ID>0)
				{
					if ($bADV_BACK)
					{
						$arFields = Array(
							"COUNTER_BACK"		=> "COUNTER_BACK + 1",
							"EXIT_COUNTER_BACK"	=> "EXIT_COUNTER_BACK + ".$exit_dir_counter,
							"ENTER_COUNTER_BACK"	=> "ENTER_COUNTER_BACK + ".$enter_counter
							);
					}
					unset($arFields["URL_404"]);
					$adv_rows_page = $DB->Update("b_stat_page_adv",$arFields,"WHERE PAGE_ID = '".$CURRENT_PAGE_ID."' and ADV_ID = ".$ADV_ID,"File: ".__FILE__."<br>Line: ".__LINE__,false,false,false);
				}
			}
			else
			{
				$arFields = Array(
					"DATE_STAT"		=> $DB_now_date,
					"COUNTER"		=> 1,
					"EXIT_COUNTER"		=> 1,
					"ENTER_COUNTER"		=> $enter_counter,
					"DIR"			=> "'N'",
					"URL"			=> "'".$DB->ForSql($CURRENT_PAGE,2000)."'",
					"URL_404"		=> "'".$ERROR_404."'",
					"URL_HASH"		=> crc32ex($CURRENT_PAGE),
					"SITE_ID"		=> $sql_site
					);
				$CURRENT_PAGE_ID = $DB->Insert("b_stat_page",$arFields,"File: ".__FILE__."<br>Line: ".__LINE__);
				if ($ERROR_404=="N")
				{
					CStatistics::Set404("b_stat_page", "ID = ".intval($CURRENT_PAGE_ID), array("URL_404" => "Y"));
				}
			}
			$_SESSION["SESS_LAST_PAGE_ID"] = $CURRENT_PAGE_ID;

			if (intval($adv_rows_page)<=0 && $ADV_ID>0)
			{
				$arFields = Array(
					"DATE_STAT"		=> $DB_now_date,
					"PAGE_ID"		=> $CURRENT_PAGE_ID,
					"ADV_ID"		=> $ADV_ID,
					"COUNTER"		=> $bADV_BACK?0:1,
					"EXIT_COUNTER"		=> $bADV_BACK?0:1,
					"ENTER_COUNTER"		=> $bADV_BACK?0:$enter_counter,
					"COUNTER_BACK"		=> $bADV_BACK?1:0,
					"EXIT_COUNTER_BACK"	=> $bADV_BACK?1:0,
					"ENTER_COUNTER_BACK"	=> $bADV_BACK?$enter_counter:0,
				);
				$DB->Insert("b_stat_page_adv",$arFields,"File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
	}

	/************************************************
			Referring sites
	************************************************/
public static 	function GetRefererListID($PROT, $SN, $PAGE_FROM, $CURRENT_URI, $ERROR_404, $sql_site)
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$DB_now = $DB->GetNowFunction();

		// ID of the referer
		$rsReferer = $DB->Query("
			SELECT ID
			FROM b_stat_referer
			WHERE SITE_NAME = '".$DB->ForSql($SN, 255)."'
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$arReferer = $rsReferer->Fetch();

		if($arReferer)
		{
			// update session counter
			$DB->Update(
				"b_stat_referer",
				array("SESSIONS" => "SESSIONS + 1"),
				"WHERE ID=".$arReferer["ID"],
				"File: ".__FILE__."<br>Line: ".__LINE__, false, false, false
			);
			$_SESSION["SESS_REFERER_ID"] = intval($arReferer["ID"]);
		}
		else
		{
			// add new one
			$arFields = array(
				"DATE_FIRST" => $DB_now,
				"DATE_LAST" => $DB_now,
				"SITE_NAME" => "'".$DB->ForSql($SN, 255)."'",
				"SESSIONS" => 1,
			);
			$_SESSION["SESS_REFERER_ID"] = intval($DB->Insert("b_stat_referer", $arFields, "File: ".__FILE__."<br>Line: ".__LINE__));
		}

		// save referring fact to database
		$arFields = array(
			"DATE_HIT" => $DB_now,
			"REFERER_ID" => $_SESSION["SESS_REFERER_ID"],
			"PROTOCOL" => "'".$DB->ForSql($PROT, 10)."'",
			"SITE_NAME" => "'".$DB->ForSql($SN, 255)."'",
			"URL_FROM" => "'".$DB->ForSql($PAGE_FROM, 2000)."'",
			"URL_TO" => "'".$DB->ForSql($CURRENT_URI, 2000)."'",
			"URL_TO_404" => "'".$ERROR_404."'",
			"SESSION_ID" => intval($_SESSION["SESS_SESSION_ID"]),
			"ADV_ID" => intval($_SESSION["SESS_ADV_ID"]),
			"SITE_ID" => $sql_site,
		);

		$REFERER_LIST_ID = intval($DB->Insert("b_stat_referer_list", $arFields, "File: ".__FILE__."<br>Line: ".__LINE__));
		if($ERROR_404=="N")
		{
			CStatistics::Set404("b_stat_referer_list", "ID = ".$REFERER_LIST_ID, array("URL_TO_404" => "Y"));
		}

		return $REFERER_LIST_ID;
	}
}
?>
