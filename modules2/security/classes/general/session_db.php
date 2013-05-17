<?
class CSecuritySessionDB
{
	public static function Init()
	{
		return CSecurityDB::Init();
	}

	public static function open($save_path, $session_name)
	{
		return CSecurityDB::Init();
	}

	public static function close()
	{
		CSecurityDB::Lock(false);
		CSecurityDB::Disconnect();
		return true;
	}

	public static function read($id)
	{
		if(preg_match("/^[\da-z]{1,32}$/i", $id))
		{
			if(!CSecurityDB::Lock($id, 60/*TODO: timelimit from php.ini?*/))
				die('Unable to get session lock within 60 seconds.');

			$rs = CSecurityDB::Query("
				select SESSION_DATA
				from b_sec_session
				where SESSION_ID = '".$id."'
			", "Module: security; Class: CSecuritySession; Function: read; File: ".__FILE__."; Line: ".__LINE__);
			$ar = CSecurityDB::Fetch($rs);
			if($ar)
			{
				$res = base64_decode($ar["SESSION_DATA"]);
				return $res;
			}
		}
	}

	public static function write($id, $sess_data)
	{
		global $SECURITY_SESSION_OLD_ID;

		if(preg_match("/^[\da-z]{1,32}$/i", $id))
		{
			if($SECURITY_SESSION_OLD_ID && preg_match("/^[\da-z]{1,32}$/i", $SECURITY_SESSION_OLD_ID))
				$old_sess_id = $SECURITY_SESSION_OLD_ID;
			else
				$old_sess_id = $id;

			CSecurityDB::Query("
				delete from b_sec_session
				where SESSION_ID = '".$old_sess_id."'
			", "Module: security; Class: CSecuritySession; Function: write; File: ".__FILE__."; Line: ".__LINE__);

			CSecurityDB::QueryBind("
				insert into b_sec_session
				(SESSION_ID, TIMESTAMP_X, SESSION_DATA)
				values
				('".$id."', ".CSecurityDB::CurrentTimeFunction().", :SESSION_DATA)
			", array("SESSION_DATA" => base64_encode($sess_data))
			, "Module: security; Class: CSecuritySession; Function: write; File: ".__FILE__."; Line: ".__LINE__);
		}
	}

	public static function destroy($id)
	{
		if(preg_match("/^[\da-z]{1,32}$/i", $id))
		{
			CSecurityDB::Query("
				delete from b_sec_session
				where SESSION_ID = '".$id."'
			", "Module: security; Class: CSecuritySession; Function: destroy; File: ".__FILE__."; Line: ".__LINE__);

			if($SECURITY_SESSION_OLD_ID && preg_match("/^[\da-z]{1,32}$/i", $SECURITY_SESSION_OLD_ID))
				CSecurityDB::Query("
					delete from b_sec_session
					where SESSION_ID = '".$SECURITY_SESSION_OLD_ID."'
				", "Module: security; Class: CSecuritySession; Function: destroy; File: ".__FILE__."; Line: ".__LINE__);
		}
	}

	public static function gc($maxlifetime)
	{
		CSecurityDB::Query("
			delete from b_sec_session
			where TIMESTAMP_X < ".CSecurityDB::SecondsAgo($maxlifetime)."
			", "Module: security; Class: CSecuritySession; Function: gc; File: ".__FILE__."; Line: ".__LINE__);
	}
}
?>