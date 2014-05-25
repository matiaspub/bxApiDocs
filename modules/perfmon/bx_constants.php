<?
/**
 * PERFMON_STARTED
 */
define('PERFMON_STARTED', $DB->ShowSqlStat."|".\Bitrix\Main\Data\Cache::getShowCacheStat()."|".$APPLICATION->ShowIncludeStat);

/**
 * T_KEYWORD
 */
define('T_KEYWORD', 400);

/**
 * LOG_FILENAME
 */
define('LOG_FILENAME', $_SERVER["DOCUMENT_ROOT"]."/debug.trc");


?>