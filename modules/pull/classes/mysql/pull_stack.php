<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/pull/classes/general/pull_stack.php");

class CPullStack extends CAllPullStack
{
	// check messages that are older than 24hours, remove them.
	// only works in PULL mode
	public static function CheckExpireAgent()
	{
		global $DB, $pPERIOD;
		$pPERIOD = 86400;

		$strSql = "SELECT count(ID) CNT FROM b_pull_stack WHERE DATE_CREATE < DATE_SUB(NOW(), INTERVAL 1 DAY)";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$strSql = "DELETE FROM b_pull_stack WHERE DATE_CREATE < DATE_SUB(NOW(), INTERVAL 1 DAY) LIMIT 1000";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			if ($arRes['CNT'] > 1000)
			{
				$pPERIOD = 600;
			}
		}

		return "CPullStack::CheckExpireAgent();";
	}
}
?>