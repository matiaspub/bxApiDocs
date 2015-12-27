<?php

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
	switch ($errno)
	{
	case E_WARNING:
		$bRecord = true;
		break;
	case E_NOTICE:
		if (
			(strpos($errstr, "Undefined index:") === false)
			&& (strpos($errstr, "Undefined offset:") === false)
			&& !array_key_exists($uni_file_name.":".$errline, $arExclude)
		)
			$bRecord = true;
		break;
	default:
		break;
	}
	if ($bRecord)
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

class CPerfomanceKeeper
{
	public static function OnPageStart()
	{
		if (!defined("PERFMON_STOP"))
		{
			$end_time = COption::GetOptionInt("perfmon", "end_time");
			if (time() > $end_time)
			{
				CPerfomanceKeeper::SetActive(false);
				if (COption::GetOptionString("perfmon", "total_mark_value", "") == "measure")
					COption::SetOptionString("perfmon", "total_mark_value", "calc");
			}
			else
			{
				self::setDebugModeOn();

				global $perfmonErrors;
				$perfmonErrors = array();
				if (COption::GetOptionString("perfmon", "warning_log") === "Y")
					set_error_handler("perfmonErrorHandler");

				register_shutdown_function(array("CPerfomanceKeeper", "writeToDatabase"));
			}
		}
	}

	public static function setDebugModeOn()
	{
		global $DB, $APPLICATION;

		// define("PERFMON_STARTED", $DB->ShowSqlStat."|".\Bitrix\Main\Data\Cache::getShowCacheStat()."|".$APPLICATION->ShowIncludeStat);

		$DB->ShowSqlStat = true;
		$application = \Bitrix\Main\HttpApplication::getInstance();
		$application->getConnection()->startTracker();

		\Bitrix\Main\Data\Cache::setShowCacheStat(COption::GetOptionString("perfmon", "cache_log") === "Y");
		$APPLICATION->ShowIncludeStat = true;
	}

	public static function restoreDebugMode()
	{
		global $DB, $APPLICATION;

		$toRestore = explode("|", constant("PERFMON_STARTED"));

		$DB->ShowSqlStat = $toRestore[0];

		\Bitrix\Main\Data\Cache::setShowCacheStat($toRestore[1]);
		$APPLICATION->ShowIncludeStat = $toRestore[2];
	}

	public static function OnEpilog()
	{
		if (defined("PERFMON_STARTED"))
			self::restoreDebugMode();
	}

	public static function OnBeforeAfterEpilog()
	{
		if (defined("PERFMON_STARTED"))
		{
			global $DB;
			$DB->ShowSqlStat = true;
		}
	}

	public static function OnAfterAfterEpilog()
	{
		if (defined("PERFMON_STARTED"))
		{
			self::restoreDebugMode();
		}
	}

	public static function writeToDatabase()
	{
		$START_EXEC_CURRENT_TIME = microtime();

		global $DB, $APPLICATION;
		$application = \Bitrix\Main\Application::getInstance();
		$connection  = $application->getConnection();

		$connection->stopTracker();
		$DB->ShowSqlStat = false;
		if ($connection->getTracker())
			$arQueryDebug = $connection->getTracker()->getQueries();
		else
			$arQueryDebug = array();
		$arIncludeDebug = $APPLICATION->arIncludeDebug;

		$cache_log = COption::GetOptionString("perfmon", "cache_log") === "Y";
		$large_cache_log = COption::GetOptionString("perfmon", "large_cache_log") === "Y";
		$large_cache_size = floatval(COption::GetOptionString("perfmon", "large_cache_size")) * 1024;
		$sql_log = COption::GetOptionString("perfmon", "sql_log") === "Y";
		$slow_sql_log = COption::GetOptionString("perfmon", "slow_sql_log") === "Y";
		$slow_sql_time = floatval(COption::GetOptionString("perfmon", "slow_sql_time"));

		if ($slow_sql_log)
		{
			self::removeQueries($arQueryDebug, $arIncludeDebug, $slow_sql_time, $cache_log);
		}

		$query_count = 0;
		$query_time = 0.0;
		if ($sql_log)
		{
			self::countQueries($query_count, $query_time, $arQueryDebug, $arIncludeDebug);
		}

		$comps_count = 0;
		$comps_time = 0.0;
		if ($sql_log || $cache_log)
		{
			self::countComponents($comps_count, $comps_time, $arIncludeDebug);
		}

		$cache_count = array();
		/** @var \Bitrix\Main\Diag\CacheTracker $arCacheDebug */
		$arCacheDebug = null;
		if ($cache_log)
		{
			$arCacheDebug = \Bitrix\Main\Diag\CacheTracker::getCacheTracking();

			if ($large_cache_log)
				self::removeCaches($large_cache_size, $arCacheDebug, $arIncludeDebug);

			self::countCache($arCacheDebug, $cache_count);
			foreach ($arIncludeDebug as $ar)
			{
				if (array_key_exists("REL_PATH", $ar))
					self::countCache($ar["CACHE"], $cache_count);
			}
		}

		if ($_SERVER["SCRIPT_NAME"] == "/bitrix/urlrewrite.php" && isset($_SERVER["REAL_FILE_PATH"]))
			$SCRIPT_NAME = $_SERVER["REAL_FILE_PATH"];
		elseif ($_SERVER["SCRIPT_NAME"] == "/404.php" && isset($_SERVER["REAL_FILE_PATH"]))
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
			"CACHE_TYPE" => COption::GetOptionString("main", "component_cache_on", "Y") == "Y"? "Y": "N",
			"~CACHE_SIZE" => intval($GLOBALS["CACHE_STAT_BYTES"]),
			"~CACHE_COUNT_R" => intval($cache_count["R"]),
			"~CACHE_COUNT_W" => intval($cache_count["W"]),
			"~CACHE_COUNT_C" => intval($cache_count["C"]),
			"QUERIES" => $query_count,
			"~QUERIES_TIME" => $query_time,
			"SQL_LOG" => $sql_log? "Y": "N",
			"COMPONENTS" => $comps_count,
			"~COMPONENTS_TIME" => $comps_time,
			"~MENU_RECALC" => $APPLICATION->_menu_recalc_counter,
		);
		CPerfomanceKeeper::SetPageTimes($START_EXEC_CURRENT_TIME, $arFields);

		if ($query_count || $comps_count || $cache_count)
			$HIT_ID = $DB->Add("b_perf_hit", $arFields);
		else
			$HIT_ID = false;

		$NN = 0;
		if ($HIT_ID && $cache_log)
		{
			self::saveCaches($HIT_ID, false, $arCacheDebug, $NN);
		}

		$MM = 0;
		if ($HIT_ID && $sql_log)
		{
			if (is_array($arQueryDebug))
				self::saveQueries($HIT_ID, false, $arQueryDebug, $MM);
		}

		if ($HIT_ID && ($sql_log || $cache_log))
		{
			foreach ($arIncludeDebug as $ii => $ar)
			{
				if (!array_key_exists("REL_PATH", $ar))
					continue;

				$cache_count = array();
				if ($cache_log)
					self::countCache($ar["CACHE"], $cache_count);

				$arFields = array(
					"HIT_ID" => $HIT_ID,
					"NN" => $ii,
					"CACHE_TYPE" => $ar["CACHE_TYPE"],
					"~CACHE_SIZE" => intval($ar["CACHE_SIZE"]),
					"~CACHE_COUNT_R" => intval($cache_count["R"]),
					"~CACHE_COUNT_W" => intval($cache_count["W"]),
					"~CACHE_COUNT_C" => intval($cache_count["C"]),
					"COMPONENT_TIME" => $ar["TIME"],
					"QUERIES" => $ar["QUERY_COUNT"],
					"QUERIES_TIME" => $ar["QUERY_TIME"],
					"COMPONENT_NAME" => $ar["REL_PATH"],
				);
				$COMP_ID = $DB->Add("b_perf_component", $arFields);

				if ($sql_log && is_array($ar["QUERIES"]))
					self::saveQueries($HIT_ID, $COMP_ID, $ar["QUERIES"], $MM);

				if ($cache_log && is_array($ar["CACHE"]))
					self::saveCaches($HIT_ID, $COMP_ID, $ar["CACHE"], $NN);
			}
		}

		global $perfmonErrors;
		if ($HIT_ID && (count($perfmonErrors) > 0))
		{
			foreach ($perfmonErrors as $arError)
			{
				$arError["HIT_ID"] = $HIT_ID;
				$DB->Add("b_perf_error", $arError);
			}
		}
	}

	public static function SetPageTimes($START_EXEC_CURRENT_TIME, &$arFields)
	{
		list($usec, $sec) = explode(" ", $START_EXEC_CURRENT_TIME);
		$CURRENT_TIME = $sec + $usec;

		if (defined("START_EXEC_PROLOG_BEFORE_1"))
		{
			list($usec, $sec) = explode(" ", START_EXEC_PROLOG_BEFORE_1);
			$PROLOG_BEFORE_1 = $sec + $usec;

			if (defined("START_EXEC_AGENTS_1") && defined("START_EXEC_AGENTS_2"))
			{
				list($usec, $sec) = explode(" ", START_EXEC_AGENTS_2);
				$AGENTS_2 = $sec + $usec;
				list($usec, $sec) = explode(" ", START_EXEC_AGENTS_1);
				$AGENTS_1 = $sec + $usec;
				$arFields["~AGENTS_TIME"] = $AGENTS_2 - $AGENTS_1;
			}
			else
			{
				$arFields["~AGENTS_TIME"] = 0;
			}

			if (defined("START_EXEC_EVENTS_1") && defined("START_EXEC_EVENTS_2"))
			{
				list($usec, $sec) = explode(" ", START_EXEC_EVENTS_2);
				$EVENTS_2 = $sec + $usec;
				list($usec, $sec) = explode(" ", START_EXEC_EVENTS_1);
				$EVENTS_1 = $sec + $usec;
				$arFields["~EVENTS_TIME"] = $EVENTS_2 - $EVENTS_1;
			}
			else
			{
				$arFields["~EVENTS_TIME"] = 0;
			}

			if (defined("START_EXEC_PROLOG_AFTER_1"))
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

				if (defined("START_EXEC_EPILOG_AFTER_1"))
				{
					list($usec, $sec) = explode(" ", START_EXEC_EPILOG_AFTER_1);
					$EPILOG_AFTER_1 = $sec + $usec;
					$arFields["~EPILOG_BEFORE_TIME"] = $EPILOG_AFTER_1 - $EPILOG_BEFORE_1;
					$arFields["~EPILOG_AFTER_TIME"] = $CURRENT_TIME - $EPILOG_AFTER_1 - $arFields["~EVENTS_TIME"];
				}
				else
				{
					$arFields["~EPILOG_BEFORE_TIME"] = 0;
				}

				$arFields["~EPILOG_TIME"] = $CURRENT_TIME - $EPILOG_BEFORE_1;
			}

			$arFields["~PAGE_TIME"] = $CURRENT_TIME - $PROLOG_BEFORE_1;
		}
		else
		{
			$arFields["~PAGE_TIME"] = $CURRENT_TIME - START_EXEC_TIME;
		}
	}

	public static function removeQueries(&$arQueryDebug, &$arIncludeDebug, $slow_sql_time, $preserveComponents = false)
	{
		if (is_array($arQueryDebug))
		{
			foreach ($arQueryDebug as $i => $arQueryInfo)
			{
				if ($arQueryInfo["TIME"] < $slow_sql_time)
					unset($arQueryDebug[$i]);
			}
		}

		if (is_array($arIncludeDebug))
		{
			foreach ($arIncludeDebug as $i => $ar)
			{
				if (array_key_exists("REL_PATH", $ar) && is_array($ar["QUERIES"]))
				{
					foreach ($ar["QUERIES"] as $N => $arQueryInfo)
					{
						if ($arQueryInfo["TIME"] < $slow_sql_time)
							unset($arIncludeDebug[$i]["QUERIES"][$N]);
					}

					if (!$preserveComponents)
					{
						if (empty($arIncludeDebug[$i]["QUERIES"]))
						{
							unset($arIncludeDebug[$i]);
						}
					}
				}
				else
				{
					if (!$preserveComponents)
					{
						unset($arIncludeDebug[$i]);
					}
				}
			}
		}
	}

	public static function countQueries(&$query_count, &$query_time, $arQueryDebug, $arIncludeDebug)
	{
		$query_count = 0;
		$query_time = 0.0;

		if (is_array($arQueryDebug))
		{
			foreach ($arQueryDebug as $arQueryInfo)
			{
				$query_count++;
				$query_time += $arQueryInfo["TIME"];
			}
		}

		foreach ($arIncludeDebug as $ar)
		{
			if (array_key_exists("REL_PATH", $ar) && is_array($ar["QUERIES"]))
			{
				foreach ($ar["QUERIES"] as $arQueryInfo)
				{
					$query_count++;
					$query_time += $arQueryInfo["TIME"];
				}
			}
		}
	}

	public static function countComponents(&$comps_count, &$comps_time, $arIncludeDebug)
	{
		$comps_count = 0;
		$comps_time = 0.0;

		foreach ($arIncludeDebug as $ar)
		{
			if (array_key_exists("REL_PATH", $ar))
			{
				$comps_count++;
				$comps_time += $ar["TIME"];
			}
		}
	}

	public static function removeCaches($large_cache_size, &$arCacheDebug, &$arIncludeDebug)
	{
		if (is_array($arCacheDebug))
		{
			foreach ($arCacheDebug as $i => $arCacheInfo)
			{
				if (
					(
						$arCacheInfo["cache_size"] > 0
						&& $arCacheInfo["cache_size"] < $large_cache_size
					) || (
						$arCacheInfo["operation"] != "W"
						&& $arCacheInfo["operation"] != "R"
					)
				)
				{
					unset($arCacheDebug[$i]);
				}
			}
		}

		if (is_array($arIncludeDebug))
		{
			foreach ($arIncludeDebug as $i => $ar)
			{
				if (array_key_exists("REL_PATH", $ar) && isset($ar["CACHE"]) && is_array($ar["CACHE"]))
				{
					foreach ($ar["CACHE"] as $N => $arCacheInfo)
					{
						if (
							(
								$arCacheInfo["cache_size"] > 0
								&& $arCacheInfo["cache_size"] < $large_cache_size
							) || (
								$arCacheInfo["operation"] != "W"
								&& $arCacheInfo["operation"] != "R"
							)
						)
						{
							unset($arIncludeDebug[$i]["CACHE"][$N]);
						}
					}
				}
			}
		}
	}

	public static function countCache($arCacheDebug, &$cache_count)
	{
		if (is_array($arCacheDebug))
		{
			foreach ($arCacheDebug as $i => $arCacheInfo)
				$cache_count[$arCacheInfo["operation"]]++;
		}
	}

	public static function findCaller($trace, &$module_id, &$comp_id)
	{
		$module_id = false;
		$comp_id = false;
		foreach ($trace as $i => $arCallInfo)
		{
			if (array_key_exists("file", $arCallInfo))
			{
				$file = strtolower(str_replace("\\", "/", $arCallInfo["file"]));

				if (
					!$module_id
					&& !preg_match("/\\/(database|cache|managedcache)\\.php\$/", $file)
				)
				{
					$match = array();
					if (preg_match("#.*/bitrix/modules/(.+?)/#", $file, $match))
					{
						$module_id = $match[1];
					}
				}

				$match = array();
				if (
					!$comp_id
					&& preg_match("#.*/(?:bitrix|install)/components/(.+?)/(.+?)/#", $file, $match)
				)
				{
					$comp_id = $match[1].":".$match[2];
				}

				if ($module_id && $comp_id)
					break;
			}
		}
	}

	public static function saveQueries($HIT_ID, $COMP_ID, $arQueryDebug, &$NN)
	{
		global $DB;

		foreach ($arQueryDebug as $arQueryInfo)
		{
			self::findCaller($arQueryInfo["TRACE"], $module_id, $comp_id);

			$arFields = array(
				"HIT_ID" => $HIT_ID,
				"COMPONENT_ID" => $COMP_ID,
				"NN" => ++$NN,
				"QUERY_TIME" => $arQueryInfo["TIME"],
				"NODE_ID" => intval($arQueryInfo["NODE_ID"]),
				"MODULE_NAME" => $module_id,
				"COMPONENT_NAME" => $comp_id,
				"SQL_TEXT" => $arQueryInfo["QUERY"],
			);
			$SQL_ID = $DB->Add("b_perf_sql", $arFields, array("SQL_TEXT"));

			if ($SQL_ID && COption::GetOptionString("perfmon", "sql_backtrace") === "Y")
			{
				$pl = strlen(rtrim($_SERVER["DOCUMENT_ROOT"], "/"));
				foreach ($arQueryInfo["TRACE"] as $i => $arCallInfo)
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

	public static function saveCaches($HIT_ID, $COMP_ID, $arCacheDebug, &$NN)
	{
		global $DB;

		foreach ($arCacheDebug as $i => $arCacheInfo)
		{
			self::findCaller($arCacheInfo["TRACE"], $module_id, $comp_id);

			$arFields = array(
				"HIT_ID" => $HIT_ID,
				"COMPONENT_ID" => $COMP_ID,
				"NN" => ++$NN,
				"CACHE_SIZE" => $arCacheInfo["cache_size"],
				"OP_MODE" => $arCacheInfo["operation"],
				"MODULE_NAME" => $module_id,
				"COMPONENT_NAME" => $comp_id,
				"BASE_DIR" => $arCacheInfo["basedir"],
				"INIT_DIR" => $arCacheInfo["initdir"],
				"FILE_NAME" => $arCacheInfo["filename"],
				"FILE_PATH" => $arCacheInfo["path"],
			);
			$DB->Add("b_perf_cache", $arFields);
		}
	}

	public static function IsActive()
	{
		$bActive = false;
		foreach (GetModuleEvents("main", "OnPageStart", true) as $arEvent)
		{
			if ($arEvent["TO_MODULE_ID"] == "perfmon")
			{
				$bActive = true;
				break;
			}
		}
		return $bActive;
	}

	public static function SetActive($bActive = false, $end_time = 0)
	{
		if ($bActive)
		{
			if (!CPerfomanceKeeper::IsActive())
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
			if (CPerfomanceKeeper::IsActive())
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
