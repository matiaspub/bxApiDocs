<?
class CSecuritySessionDB
{
	/**
	 * @return bool
	 */
	public static function Init()
	{
		return CSecurityDB::Init();
	}

	/**
	 * @param string $savePath - unused on this handler
	 * @param string $sessionName - unused on this handler
	 * @return bool
	 */
	public static function open($savePath, $sessionName)
	{
		return CSecurityDB::Init();
	}

	/**
	 * @return bool
	 */
	public static function close()
	{
		CSecurityDB::Lock(false);
		CSecurityDB::Disconnect();
		return true;
	}

	/**
	 * @param string $id - session id, must be valid hash
	 * @return string
	 */
	public static function read($id)
	{
		if(!self::isValidId($id))
			return "";

		if(!CSecurityDB::Lock($id, 60/*TODO: timelimit from php.ini?*/))
			CSecuritySession::triggerFatalError('Unable to get session lock within 60 seconds.');

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

		return "";
	}

	/**
	 * @param string $id - session id, must be valid hash
	 * @param array $sessionData
	 */
	public static function write($id, $sessionData)
	{
		if(!self::isValidId($id))
			return;

		if(CSecuritySession::isOldSessionIdExist())
			$oldSessionId = CSecuritySession::getOldSessionId();
		else
			$oldSessionId = $id;

		CSecurityDB::Query("
			delete from b_sec_session
			where SESSION_ID = '".$oldSessionId."'
		", "Module: security; Class: CSecuritySession; Function: write; File: ".__FILE__."; Line: ".__LINE__);

		CSecurityDB::QueryBind("
			insert into b_sec_session
			(SESSION_ID, TIMESTAMP_X, SESSION_DATA)
			values
			('".$id."', ".CSecurityDB::CurrentTimeFunction().", :SESSION_DATA)
		", array("SESSION_DATA" => base64_encode($sessionData))
		, "Module: security; Class: CSecuritySession; Function: write; File: ".__FILE__."; Line: ".__LINE__);
	}

	/**
	 * @param string $id - session id, must be valid hash
	 */
	public static function destroy($id)
	{
		if(!self::isValidId($id))
			return;

		CSecurityDB::Query("
			delete from b_sec_session
			where SESSION_ID = '".$id."'
		", "Module: security; Class: CSecuritySession; Function: destroy; File: ".__FILE__."; Line: ".__LINE__);

		if(CSecuritySession::isOldSessionIdExist())
			CSecurityDB::Query("
				delete from b_sec_session
				where SESSION_ID = '".CSecuritySession::getOldSessionId()."'
			", "Module: security; Class: CSecuritySession; Function: destroy; File: ".__FILE__."; Line: ".__LINE__);
	}

	/**
	 * @param int $maxLifeTime
	 */
	public static function gc($maxLifeTime)
	{
		CSecurityDB::Query("
			delete from b_sec_session
			where TIMESTAMP_X < ".CSecurityDB::SecondsAgo($maxLifeTime)."
			", "Module: security; Class: CSecuritySession; Function: gc; File: ".__FILE__."; Line: ".__LINE__);
	}

	/**
	 * @param string $pId
	 * @return bool
	 */
	protected static function isValidId($pId)
	{
		return (bool) preg_match("/^[\da-z]{1,32}$/i", $pId);
	}

}
