<?
class CSecuritySession
{
	protected static $oldSessionId = null;

	public static function Init()
	{
		if(
			CSecuritySessionMC::isStorageEnabled()
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

		if($maxlifetime && !CSecuritySessionMC::isStorageEnabled())
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
		$oldSessionId = session_id();
		session_regenerate_id();
		$newSessionId = session_id();

		//Delay database update to session write moment
		if(!self::$oldSessionId)
			self::$oldSessionId = $oldSessionId;
	}

	public static function isOldSessionIdExist()
	{
		return self::$oldSessionId && self::CheckSessionId(self::$oldSessionId);
	}

	public static function getOldSessionId()
	{
		return self::$oldSessionId;
	}

	public static function CheckSessionId($id)
	{
		return preg_match("/^[\da-z]{1,32}$/i", $id);
	}
}
