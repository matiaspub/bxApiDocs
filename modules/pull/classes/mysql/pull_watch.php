<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/pull/classes/general/pull_watch.php");

class CPullWatch extends CAllPullWatch
{
	// check watch that are older than 10minutes, remove them.
	public static function CheckExpireAgent()
	{
		global $DB, $pPERIOD;
		if (!CPullOptions::ModuleEnable())
			return false;

		$pPERIOD = 1200;

		$strSql = "SELECT count(ID) CNT FROM b_pull_watch WHERE DATE_CREATE < DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$strSql = "DELETE FROM b_pull_watch WHERE DATE_CREATE < DATE_SUB(NOW(), INTERVAL 30 MINUTE) LIMIT 1000";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			if ($arRes['CNT'] > 1000)
			{
				$pPERIOD = 180;
			}
		}

		return "CPullWatch::CheckExpireAgent();";
	}
}
?>