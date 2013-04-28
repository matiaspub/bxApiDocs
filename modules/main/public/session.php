<?
// define("NO_KEEP_STATISTIC", true);
// define("BX_SKIP_SESSION_TERMINATE_TIME", true);
// define("NOT_CHECK_FILE_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if($_SESSION["BX_SESSION_COUNTER"] > 1)
{
	if(check_bitrix_sessid())
	{
		//interval=0 - no user activity
		//interval>0 - expand session for user activity time
		$interval = intval($_REQUEST["interval"]);
		$nextTime = time() + $interval + ($interval>0? 60:0);
		if($_SESSION["BX_SESSION_TERMINATE_TIME"] < $nextTime)
			$_SESSION["BX_SESSION_TERMINATE_TIME"] = $nextTime;
		die("OK");
	}
}
elseif($USER->IsAuthorized())
{
	$cookie_prefix = COption::GetOptionString('main', 'cookie_name', 'BITRIX_SM');
	$salt = $_COOKIE[$cookie_prefix.'_UIDH']."|".$_SERVER["REMOTE_ADDR"]."|".@filemtime($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/version.php")."|".LICENSE_KEY."|".CMain::GetServerUniqID();
	if($_REQUEST["k"] == md5($_REQUEST["sessid"].$salt))
	{
		bitrix_sessid_set($_REQUEST['sessid']);
		die("SESSION_CHANGED");
	}
}
echo "SESSION_EXPIRED";
?>