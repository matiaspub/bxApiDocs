<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/classes/general/log_pages.php");

class CSocNetLogPages extends CAllSocNetLogPages
{
	public static function Set($user_id, $page_last_date, $page_size, $page_num = 1, $site_id = SITE_ID)
	{
		global $DB;

		$user_id = intval($user_id);
		$page_size = intval($page_size);
		$page_num = intval($page_num);

		if (
			$user_id <= 0
			|| $page_size <= 0
			|| strlen($page_last_date) <= 0
		)
			return false;

		$page_last_date = $DB->CharToDateFunction($page_last_date);

		$strSQL = "
			INSERT INTO b_sonet_log_page (USER_ID, SITE_ID, PAGE_SIZE, PAGE_NUM, PAGE_LAST_DATE)
			VALUES (".$user_id.", '".$DB->ForSQL($site_id)."', ".$page_size.", ".$page_num.", ".$page_last_date.")
			ON DUPLICATE KEY UPDATE PAGE_LAST_DATE = ".$page_last_date."
		";
		$res = $DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
	}
}
?>