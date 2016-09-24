<?
//We can not use object here due to PHP architecture
global $SECURITY_SESSION_DBH;
$SECURITY_SESSION_DBH = false;

/**
 * @deprecated since 16.0.0
 */
class CSecurityDB
{
	public static function Init($bDoConnect = false)
	{
		global $SECURITY_SESSION_DBH, $DB;
		static $DBLogin, $DBPassword, $DBName;

		if(is_resource($SECURITY_SESSION_DBH))
			return true;

		if(!is_object($DB))
			return false;

		if($bDoConnect)
		{
			$SECURITY_SESSION_DBH = @mysql_connect($DB->DBHost, $DB->DBLogin, $DB->DBPassword, true);
		}
		else
		{
			$DBLogin = $DB->DBLogin;
			$DBPassword = $DB->DBPassword;
			$DBName = $DB->DBName;
			return true;
		}

		//In case of error just skip it over
		if(!is_resource($SECURITY_SESSION_DBH))
			return false;

		if(!mysql_select_db($DB->DBName, $SECURITY_SESSION_DBH))
			return false;

		if(
			defined("BX_SECURITY_SQL_LOG_BIN")
			&& (
				BX_SECURITY_SQL_LOG_BIN === false
				|| BX_SECURITY_SQL_LOG_BIN === "N"
			)
		)
			CSecurityDB::Query("SET sql_log_bin = 0", "Module: security; Class: CSecurityDB; Function: Init; File: ".__FILE__."; Line: ".__LINE__);

		$rs = CSecurityDB::Query("SHOW TABLES LIKE 'b_sec_session'", "Module: security; Class: CSecurityDB; Function: Init; File: ".__FILE__."; Line: ".__LINE__);
		if(!is_resource($rs))
			return false;

		$ar = CSecurityDB::Fetch($rs);
		if($ar)
			return true;

		if(defined("MYSQL_TABLE_TYPE") && strlen(MYSQL_TABLE_TYPE) > 0)
		{
			$rs = CSecurityDB::Query("SET storage_engine = '".MYSQL_TABLE_TYPE."'", "Module: security; Class: CSecurityDB; Function: Init; File: ".__FILE__."; Line: ".__LINE__);
			if(!is_resource($rs))
				return false;
		}

		$rs = CSecurityDB::Query("CREATE TABLE b_sec_session
			(
				SESSION_ID VARCHAR(250) NOT NULL,
				TIMESTAMP_X TIMESTAMP NOT NULL,
				SESSION_DATA LONGTEXT,
				PRIMARY KEY(SESSION_ID),
				KEY ix_b_sec_session_time (TIMESTAMP_X)
			)
		", "Module: security; Class: CSecurityDB; Function: Init; File: ".__FILE__."; Line: ".__LINE__);

		return is_resource($rs);
	}

	public static function Disconnect()
	{
		global $SECURITY_SESSION_DBH;
		if(is_resource($SECURITY_SESSION_DBH))
		{
			mysql_close($SECURITY_SESSION_DBH);
			$SECURITY_SESSION_DBH = false;
		}
	}

	public static function CurrentTimeFunction()
	{
		return "now()";
	}

	public static function SecondsAgo($sec)
	{
		return "DATE_ADD(now(), INTERVAL - ".intval($sec)." SECOND)";
	}

	public static function Query($strSql, $error_position)
	{
		global $SECURITY_SESSION_DBH;

		if(!is_resource($SECURITY_SESSION_DBH))
			CSecurityDB::Init(true);

		if(is_resource($SECURITY_SESSION_DBH))
		{
			$strSql = preg_replace("/^\\s*SELECT\\s+(?!GET_LOCK|RELEASE_LOCK)/i", "SELECT SQL_NO_CACHE ", $strSql);
			$result = @mysql_query($strSql, $SECURITY_SESSION_DBH);
			if($result)
			{
				return $result;
			}
			else
			{
				$db_Error = mysql_error();
				AddMessage2Log($error_position." MySql Query Error: ".$strSql." [".$db_Error."]", "security");
			}
		}
		return false;
	}

	public static function QueryBind($strSql, $arBinds, $error_position)
	{
		foreach($arBinds as $key => $value)
			$strSql = str_replace(":".$key, "'".$value."'", $strSql);
		return CSecurityDB::Query($strSql, $error_position);
	}

	public static function Fetch($result)
	{
		if($result)
			return mysql_fetch_array($result, MYSQL_ASSOC);
		else
			return false;
	}

	public static function Lock($id, $timeout = 60)
	{
		static $lock_id = "";

		if($id === false)
		{
			if($lock_id)
				$rsLock = CSecurityDB::Query("DO RELEASE_LOCK('".$lock_id."')", "Module: security; Class: CSecurityDB; Function: Lock; File: ".__FILE__."; Line: ".__LINE__);
		}
		else
		{
			$rsLock = CSecurityDB::Query("SELECT GET_LOCK('".md5($id)."', ".intval($timeout).") as L", "Module: security; Class: CSecurityDB; Function: Lock; File: ".__FILE__."; Line: ".__LINE__);
			if($rsLock)
			{
				$arLock = CSecurityDB::Fetch($rsLock);
				if($arLock["L"]=="0")
					return false;
				else
					$lock_id = md5($id);
			}
		}
		return is_resource($rsLock);
	}

	public static function LockTable($table_name, $lock_id)
	{
		$rsLock = CSecurityDB::Query("SELECT GET_LOCK('".md5($lock_id)."', 0) as L", "Module: security; Class: CSecurityDB; Function: LockTable; File: ".__FILE__."; Line: ".__LINE__);
		if($rsLock)
		{
			$arLock = CSecurityDB::Fetch($rsLock);
			if($arLock["L"]=="0")
				return false;
			else
				return array("lock_id" => $lock_id);
		}
		else
		{
			return false;
		}
	}

	public static function UnlockTable($table_lock)
	{
		if(is_array($table_lock))
			CSecurityDB::Query("SELECT RELEASE_LOCK('".$table_lock["lock_id"]."')", "Module: security; Class: CSecurityDB; Function: UnlockTable; File: ".__FILE__."; Line: ".__LINE__);
	}
}
?>