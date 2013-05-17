<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/classes/general/log_favorites.php");

class CSocNetLogFavorites extends CAllSocNetLogFavorites
{

	public static function Add($user_id, $log_id)
	{
		global $DB;

		if (intval($user_id) <= 0 || intval($log_id) <= 0)
			return false;

		$strSQL = "INSERT IGNORE INTO b_sonet_log_favorites (USER_ID, LOG_ID) VALUES (".$user_id.", ".$log_id.")";
		if ($DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__))
			return true;
		else
			return false;
	}

}
?>