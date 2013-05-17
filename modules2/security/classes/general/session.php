<?
global $SECURITY_SESSION_OLD_ID;
$SECURITY_SESSION_OLD_ID = false;

class CSecuritySession
{
	public static function Init()
	{
		if(
			defined("BX_SECURITY_SESSION_MEMCACHE_HOST")
			&& CSecuritySessionMC::Init()
		)
		{
			//may return false with session.auto_start is set to On
			if(session_set_save_handler(
				array("CSecuritySessionMC", "open"),
				array("CSecuritySessionMC", "close"),
				array("CSecuritySessionMC", "read"),
				array("CSecuritySessionMC", "write"),
				array("CSecuritySessionMC", "destroy"),
				array("CSecuritySessionMC", "gc")
			))
			{
				register_shutdown_function("session_write_close");
			}
		}
		elseif(CSecuritySessionDB::Init())
		{
			//may return false with session.auto_start is set to On
			if(session_set_save_handler(
				array("CSecuritySessionDB", "open"),
				array("CSecuritySessionDB", "close"),
				array("CSecuritySessionDB", "read"),
				array("CSecuritySessionDB", "write"),
				array("CSecuritySessionDB", "destroy"),
				array("CSecuritySessionDB", "gc")
			))
			{
				register_shutdown_function("session_write_close");
			}
		}
	}

	public static function CleanUpAgent()
	{
		global $DB;
		$maxlifetime = intval(ini_get("session.gc_maxlifetime"));

		if($maxlifetime && !defined("BX_SECURITY_SESSION_MEMCACHE_HOST"))
		{
			$strSql = "
				delete from b_sec_session
				where TIMESTAMP_X < ".CSecurityDB::SecondsAgo($maxlifetime)."
			";
			if(CSecurityDB::Init())
				CSecurityDB::Query($strSql, "Module: security; Class: CSecuritySession; Function: CleanUpAgent; File: ".__FILE__."; Line: ".__LINE__);
			else
				$DB->Query($strSql, false, "Module: security; Class: CSecuritySession; Function: CleanUpAgent; File: ".__FILE__."; Line: ".__LINE__);
		}

		return "CSecuritySession::CleanUpAgent();";
	}

	public static function UpdateSessID()
	{
		global $SECURITY_SESSION_OLD_ID;

		$old_sess_id = session_id();
		session_regenerate_id();
		if(!version_compare(phpversion(),"4.3.3",">="))
		{
			setcookie(session_name(), session_id(), ini_get("session.cookie_lifetime"), "/");
		}
		$new_sess_id = session_id();

		//Delay database update to session write moment
		if(!$SECURITY_SESSION_OLD_ID)
			$SECURITY_SESSION_OLD_ID = $old_sess_id;
	}

	public static function CheckSessionId($id)
	{
		return preg_match("/^[\da-z]{1,32}$/i", $id);
	}
}
?>