<?
function perfmonErrorHandler($errno, $errstr, $errfile, $errline)
{
	global $perfmonErrors;
	//if(count($perfmonErrors) > 100)
	//	return false;
	static $arExclude = array(
		"/modules/main/classes/general/cache.php:150" => true,
	);
	$uni_file_name = str_replace("\\", "/", substr($errfile, strlen($_SERVER["DOCUMENT_ROOT"].BX_ROOT)));
	$bRecord = false;
	switch($errno)
	{
		case E_WARNING:
			$bRecord = true;
			break;
		case E_NOTICE:
			if(
				(strpos($errstr, "Undefined index:") === false)
				&& (strpos($errstr, "Undefined offset:") === false)
				&& !array_key_exists($uni_file_name.":".$errline, $arExclude)
			)
				$bRecord = true;
			break;
		default:
			break;
	}
	if($bRecord)
	{
		$perfmonErrors[] = array(
			"ERRNO" => $errno,
			"ERRSTR" => $errstr,
			"ERRFILE" => $errfile,
			"ERRLINE" => $errline,
		);
	}
	//Continue with default handling
	return false;
}

class CAllPerfomanceKeeper
{
	public static function OnPageStart()
	{
		if(!defined("PERFMON_STOP"))
		{
			$end_time = COption::GetOptionInt("perfmon", "end_time");
			if(time() > $end_time)
			{
				CPerfomanceKeeper::SetActive(false);
				if(COption::GetOptionString("perfmon", "total_mark_value", "") == "measure");
					COption::SetOptionString("perfmon", "total_mark_value", "calc");
			}
			else
			{
				global $DB, $APPLICATION;
				// define("PERFMON_STARTED", $DB->ShowSqlStat);
				$DB->ShowSqlStat = true;
				$APPLICATION->ShowIncludeStat = true;

				global $perfmonErrors;
				$perfmonErrors = array();
				if(COption::GetOptionString("perfmon", "warning_log") === "Y")
					set_error_handler("perfmonErrorHandler");
			}
		}
	}

	public static function OnEpilog()
	{
		if(defined("PERFMON_STARTED"))
		{
			global $DB;
			$DB->ShowSqlStat = constant("PERFMON_STARTED");
		}
	}

	public static function OnBeforeAfterEpilog()
	{
		if(defined("PERFMON_STARTED"))
		{
			global $DB;
			$DB->ShowSqlStat = true;
		}
	}

	public static function OnAfterAfterEpilog()
	{
		if(defined("PERFMON_STARTED"))
		{
			$START_EXEC_CURRENT_TIME = microtime();

			global $DB, $APPLICATION;
			$DB->ShowSqlStat = false;
			$sql_log = COption::GetOptionString("perfmon", "sql_log") === "Y"? "Y": "N";
			$slow_sql_log = COption::GetOptionString("perfmon", "slow_sql_log") === "Y"? "Y": "N";
			$slow_sql_time = floatval(COption::GetOptionString("perfmon", "slow_sql_time"));
			$bHasSQL2Write = false;

			$cquery_count = 0;
			$cquery_time  = 0;
			if(($sql_log === "Y") && ($slow_sql_log === "Y") && is_array($DB->arQueryDebug))
			{
				foreach($DB->arQueryDebug as $i => $arQueryInfo)
					if($arQueryInfo["TIME"] < $slow_sql_time)
						unset($DB->arQueryDebug[$i]);
					else
					{
						$bHasSQL2Write = true;
						$cquery_count++;
						$cquery_time  += $arQueryInfo["TIME"];
					}
			}

			$comps_count = 0;
			$comps_time = 0.0;
			if($cquery_count > 0)
			{
				$query_count = $cquery_count;
				$query_time  = $cquery_time;
			}
			else
			{
				$query_count = intval($DB->cntQuery);
				$query_time  = $DB->timeQuery > 0? $DB->timeQuery: 0;
			}
			foreach($APPLICATION->arIncludeDebug as $i => $ar)
			{
				if($slow_sql_log === "Y")
				{
					$cquery_count = 0;
					$cquery_time  = 0;
					foreach($ar["QUERIES"] as $N => $arQueryInfo)
					{
						if($arQueryInfo["TIME"] < $slow_sql_time)
							unset($APPLICATION->arIncludeDebug[$i]["QUERIES"][$N]);
						else
						{
							$bHasSQL2Write = true;
							$cquery_count++;
							$cquery_time  += $arQueryInfo["TIME"];
						}
					}

					if($cquery_count == 0)
						unset($APPLICATION->arIncludeDebug[$i]);
					else
					{
						$query_count += $cquery_count;
						$query_time  += $cquery_time;
						$comps_count++;
						$comps_time += $ar["TIME"];
					}
				}
				else
				{
					$query_count += $ar["QUERY_COUNT"];
					$query_time  += $ar["QUERY_TIME"];
					$comps_count++;
					$comps_time += $ar["TIME"];
				}
			}

			if($_SERVER["SCRIPT_NAME"] == "/bitrix/urlrewrite.php" && isset($_SERVER["REAL_FILE_PATH"]))
				$SCRIPT_NAME = $_SERVER["REAL_FILE_PATH"];
			elseif($_SERVER["SCRIPT_NAME"] == "/404.php" && isset($_SERVER["REAL_FILE_PATH"]))
				$SCRIPT_NAME = $_SERVER["REAL_FILE_PATH"];
			else
				$SCRIPT_NAME = $_SERVER["SCRIPT_NAME"];

			$arFields = array(
				"~DATE_HIT" => $DB->GetNowFunction(),
				"IS_ADMIN" => defined("ADMIN_SECTION")? "Y": "N",
				"REQUEST_METHOD" => $_SERVER["REQUEST_METHOD"],
				"SERVER_NAME" => $_SERVER["SERVER_NAME"],
				"SERVER_PORT" => $_SERVER["SERVER_PORT"],
				"SCRIPT_NAME" => $SCRIPT_NAME,
				"REQUEST_URI" => $_SERVER["REQUEST_URI"],
				"INCLUDED_FILES" => function_exists("get_included_files")? count(get_included_files()): false,
				"MEMORY_PEAK_USAGE" => function_exists("memory_get_peak_usage")? memory_get_peak_usage(): false,
				"CACHE_TYPE" => COption::GetOptionString("main", "component_cache_on", "Y")=="Y"? "Y": "N",
				"~CACHE_SIZE" => intval($GLOBALS["CACHE_STAT_BYTES"]),
				"QUERIES" => $query_count,
				"~QUERIES_TIME" => $query_time,
				"SQL_LOG" => $sql_log,
				"COMPONENTS" => $comps_count,
				"~COMPONENTS_TIME" => $comps_time,
				"~MENU_RECALC" => $APPLICATION->_menu_recalc_counter,
			);
			CPerfomanceKeeper::SetPageTimes($START_EXEC_CURRENT_TIME, $arFields);

			if($slow_sql_log !== "Y" || $bHasSQL2Write)
				$HIT_ID = $DB->Add("b_perf_hit", $arFields);
			else
				$HIT_ID = false;

			if($HIT_ID && ($sql_log === "Y") && is_array($DB->arQueryDebug))
			{
				$NN = 0;
				foreach($DB->arQueryDebug as $i => $arQueryInfo)
				{
					$module_id = false;
					$comp_id = false;
					foreach($arQueryInfo["TRACE"] as $i => $arCallInfo)
					{
						if(array_key_exists("file", $arCallInfo))
						{
							$file = strtolower(str_replace("\\", "/", $arCallInfo["file"]));
							if(!$module_id && substr($file, -12) != "database.php")
							{
								if(preg_match("#.*/bitrix/modules/(.+?)/#", $file, $match))
								{
									$module_id = $match[1];
								}
							}
							if(!$comp_id && preg_match("#.*/bitrix/components/(.+?)/(.+?)/#", $file, $match))
							{
								$comp_id = $match[1].":".$match[2];
							}
							if($module_id && $comp_id)
								break;
						}
					}

					$arFields = array(
						"HIT_ID" => $HIT_ID,
						"NN" => ++$NN,
						"QUERY_TIME" => $arQueryInfo["TIME"],
						"MODULE_NAME" => $module_id,
						"COMPONENT_NAME" => $comp_id,
						"SQL_TEXT" => $arQueryInfo["QUERY"],
					);
					$SQL_ID = $DB->Add("b_perf_sql", $arFields, array("SQL_TEXT"));

					if($SQL_ID && COption::GetOptionString("perfmon", "sql_backtrace") === "Y")
					{
						$pl = strlen(rtrim($_SERVER["DOCUMENT_ROOT"], "/"));
						foreach($arQueryInfo["TRACE"] as $i => $arCallInfo)
						{
							$DB->Add("b_perf_sql_backtrace", array(
								"ID" => 1,
								"SQL_ID" => $SQL_ID,
								"NN" => $i,
								"FILE_NAME" => substr($arCallInfo["file"], $pl),
								"LINE_NO" => $arCallInfo["line"],
								"CLASS_NAME" => $arCallInfo["class"],
								"FUNCTION_NAME" => $arCallInfo["function"],
							));
						}
					}
				}

				foreach($APPLICATION->arIncludeDebug as $ii => $ar)
				{
					$arFields = array(
						"HIT_ID" => $HIT_ID,
						"NN" => $ii,
						"CACHE_TYPE" => $ar["CACHE_TYPE"],
						"CACHE_SIZE" => intval($ar["CACHE_SIZE"]),
						"COMPONENT_TIME" => $ar["TIME"],
						"QUERIES" => $ar["QUERY_COUNT"],
						"QUERIES_TIME" => $ar["QUERY_TIME"],
						"COMPONENT_NAME" => $ar["REL_PATH"],
					);
					$COMP_ID = $DB->Add("b_perf_component", $arFields);
					foreach($ar["QUERIES"] as $N => $arQueryInfo)
					{
						$module_id = false;
						$comp_id = false;
						foreach($arQueryInfo["TRACE"] as $i => $arCallInfo)
						{
							if(array_key_exists("file", $arCallInfo))
							{
								$file = strtolower(str_replace("\\", "/", $arCallInfo["file"]));
								if(!$module_id && substr($file, -12) != "database.php")
								{
									if(preg_match("#.*/bitrix/modules/(.+?)/#", $file, $match))
									{
										$module_id = $match[1];
									}
								}
								if(!$comp_id && preg_match("#.*/bitrix/components/(.+?)/(.+?)/#", $file, $match))
								{
									$comp_id = $match[1].":".$match[2];
								}
								if($module_id && $comp_id)
									break;
							}
						}

						$arFields = array(
							"HIT_ID" => $HIT_ID,
							"COMPONENT_ID" => $COMP_ID,
							"NN" => ++$NN,
							"QUERY_TIME" => $arQueryInfo["TIME"],
							"MODULE_NAME" => $module_id,
							"COMPONENT_NAME" => $comp_id,
							"SQL_TEXT" => $arQueryInfo["QUERY"],
						);
						$SQL_ID = $DB->Add("b_perf_sql", $arFields, array("SQL_TEXT"));

						$pl = strlen(rtrim($_SERVER["DOCUMENT_ROOT"], "/"));
						if($SQL_ID && COption::GetOptionString("perfmon", "sql_backtrace") === "Y")
						{
							foreach($arQueryInfo["TRACE"] as $i => $arCallInfo)
							{
								$DB->Add("b_perf_sql_backtrace", array(
									"ID" => 1,
									"SQL_ID" => $SQL_ID,
									"NN" => $i,
									"FILE_NAME" => substr($arCallInfo["file"], $pl),
									"LINE_NO" => $arCallInfo["line"],
									"CLASS_NAME" => $arCallInfo["class"],
									"FUNCTION_NAME" => $arCallInfo["function"],
								));
							}
						}
					}
				}
			}
			global $perfmonErrors;
			if($HIT_ID && (count($perfmonErrors) > 0))
			{
				foreach($perfmonErrors as $arError)
				{
					$arError["HIT_ID"] = $HIT_ID;
					$ERR_ID = $DB->Add("b_perf_error", $arError);
				}
			}
			$DB->ShowSqlStat = constant("PERFMON_STARTED");
		}
	}

	public static function SetPageTimes($START_EXEC_CURRENT_TIME, &$arFields)
	{
		list($usec, $sec) = explode(" ", $START_EXEC_CURRENT_TIME);
		$CURRENT_TIME = $sec + $usec;

		if(defined("START_EXEC_PROLOG_BEFORE_1"))
		{
			list($usec, $sec) = explode(" ", START_EXEC_PROLOG_BEFORE_1);
			$PROLOG_BEFORE_1 = $sec + $usec;

			list($usec, $sec) = explode(" ", START_EXEC_AGENTS_2);
			$AGENTS_2 = $sec + $usec;
			list($usec, $sec) = explode(" ", START_EXEC_AGENTS_1);
			$AGENTS_1 = $sec + $usec;
			$arFields["~AGENTS_TIME"] = $AGENTS_2 - $AGENTS_1;

			if(defined("START_EXEC_PROLOG_AFTER_1"))
			{
				list($usec, $sec) = explode(" ", START_EXEC_PROLOG_AFTER_1);
				$PROLOG_AFTER_1 = $sec + $usec;
				list($usec, $sec) = explode(" ", START_EXEC_PROLOG_AFTER_2);
				$PROLOG_AFTER_2 = $sec + $usec;
				$arFields["~PROLOG_AFTER_TIME"] = $PROLOG_AFTER_2 - $PROLOG_AFTER_1;

				$arFields["~PROLOG_BEFORE_TIME"] = $PROLOG_AFTER_1 - $PROLOG_BEFORE_1;

				$arFields["~PROLOG_TIME"] = round($PROLOG_AFTER_2 - $PROLOG_BEFORE_1 - $arFields["~AGENTS_TIME"], 4);

				list($usec, $sec) = explode(" ", START_EXEC_EPILOG_BEFORE_1);
				$EPILOG_BEFORE_1 = $sec + $usec;

				$arFields["~WORK_AREA_TIME"] = $EPILOG_BEFORE_1 - $PROLOG_AFTER_2;

				list($usec, $sec) = explode(" ", START_EXEC_EPILOG_AFTER_1);
				$EPILOG_AFTER_1 = $sec + $usec;

				$arFields["~EPILOG_BEFORE_TIME"] = $EPILOG_AFTER_1 - $EPILOG_BEFORE_1;
			}

			list($usec, $sec) = explode(" ", START_EXEC_EVENTS_2);
			$EVENTS_2 = $sec + $usec;
			list($usec, $sec) = explode(" ", START_EXEC_EVENTS_1);
			$EVENTS_1 = $sec + $usec;
			$arFields["~EVENTS_TIME"] = $EVENTS_2 - $EVENTS_1;

			if(defined("START_EXEC_PROLOG_AFTER_1"))
			{
				$arFields["~EPILOG_AFTER_TIME"] = $CURRENT_TIME - $EPILOG_AFTER_1 - $arFields["~EVENTS_TIME"];

				$arFields["~EPILOG_TIME"] = $CURRENT_TIME - $EPILOG_BEFORE_1;
			}

			$arFields["~PAGE_TIME"] = $CURRENT_TIME - $PROLOG_BEFORE_1;
		}
		else
		{
			$arFields["~PAGE_TIME"] = $CURRENT_TIME - START_EXEC_TIME;
		}
	}

	public static function IsActive()
	{
		$bActive = false;
		foreach(GetModuleEvents("main", "OnPageStart", true) as $arEvent)
		{
			if($arEvent["TO_MODULE_ID"] == "perfmon")
			{
				$bActive = true;
				break;
			}
		}
		return $bActive;
	}

	public static function SetActive($bActive = false, $end_time = 0)
	{
		if($bActive)
		{
			if(!CPerfomanceKeeper::IsActive())
			{
				RegisterModuleDependences("main", "OnPageStart", "perfmon", "CPerfomanceKeeper", "OnPageStart", "1");
				RegisterModuleDependences("main", "OnEpilog", "perfmon", "CPerfomanceKeeper", "OnEpilog", "1000");
				RegisterModuleDependences("main", "OnAfterEpilog", "perfmon", "CPerfomanceKeeper", "OnBeforeAfterEpilog", "1");
				RegisterModuleDependences("main", "OnAfterEpilog", "perfmon", "CPerfomanceKeeper", "OnAfterAfterEpilog", "1000");
				RegisterModuleDependences("main", "OnLocalRedirect", "perfmon", "CPerfomanceKeeper", "OnAfterAfterEpilog", "1000");
			}
			COption::SetOptionInt("perfmon", "end_time", $end_time);
		}
		else
		{
			if(CPerfomanceKeeper::IsActive())
			{
				UnRegisterModuleDependences("main", "OnPageStart", "perfmon", "CPerfomanceKeeper", "OnPageStart");
				UnRegisterModuleDependences("main", "OnEpilog", "perfmon", "CPerfomanceKeeper", "OnEpilog");
				UnRegisterModuleDependences("main", "OnAfterEpilog", "perfmon", "CPerfomanceKeeper", "OnBeforeAfterEpilog");
				UnRegisterModuleDependences("main", "OnAfterEpilog", "perfmon", "CPerfomanceKeeper", "OnAfterAfterEpilog");
				UnRegisterModuleDependences("main", "OnLocalRedirect", "perfmon", "CPerfomanceKeeper", "OnAfterAfterEpilog");
			}
		}
	}
}
?>