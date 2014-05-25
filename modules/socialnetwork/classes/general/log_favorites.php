<?
class CAllSocNetLogFavorites
{

	public static function Change($user_id, $log_id)
	{
		global $DB, $APPLICATION;

		$user_id = intval($user_id);
		$log_id = intval($log_id);

		if ($user_id <= 0 || $log_id <= 0)
		{
			if ($user_id <= 0)
				$APPLICATION->ThrowException(GetMessage("SONET_LF_WRONG_PARAMETER_USER_ID"), "EMPTY_USER_ID");
			else
				$APPLICATION->ThrowException(GetMessage("SONET_LF_WRONG_PARAMETER_LOG_ID"), "EMPTY_LOG_ID");

			return false;
		}

		$strSQL = "SELECT * FROM b_sonet_log_favorites WHERE USER_ID = ".$user_id." AND LOG_ID = ".$log_id;
		$dbRes = $DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		if (!$arRes = $dbRes->Fetch())
		{
			$strSQL = "INSERT INTO b_sonet_log_favorites (USER_ID, LOG_ID) VALUES(".$user_id.", ".$log_id.")";
			if ($DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__))
				return "Y";
			else
				$APPLICATION->ThrowException(GetMessage("SONET_LF_CANNOT_INSERT"), "CANNOT_INSERT");
		}
		else
		{
			$strSQL = "DELETE FROM b_sonet_log_favorites WHERE USER_ID = ".$user_id." AND LOG_ID = ".$log_id;
			if ($DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__))
				return "N";
			else
				$APPLICATION->ThrowException(GetMessage("SONET_LF_CANNOT_INSERT"), "CANNOT_DELETE");
		}

		return false;
	}

}
?>