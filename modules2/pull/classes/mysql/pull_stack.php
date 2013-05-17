<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/pull/classes/general/pull_stack.php");

class CPullStack extends CAllPullStack
{
	// check messages that are older than 24hours, remove them.
	// only works in PULL mode
	public static function CheckExpireAgent()
	{
		global $DB;
		if (!CPullOptions::ModuleEnable())
			return false;

		CAgent::RemoveAgent("CPullStack::CheckExpireAgent();", "pull");

		$strSql = "SELECT count(ID) CNT FROM b_pull_stack WHERE DATE_CREATE < DATE_SUB(NOW(), INTERVAL 1 DAY)";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$strSql = "DELETE FROM b_pull_stack WHERE DATE_CREATE < DATE_SUB(NOW(), INTERVAL 1 DAY) LIMIT 1000";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			if ($arRes['CNT'] > 1000)
			{
				CAgent::AddAgent("CPullStack::CheckExpireAgent();", "pull", "N", 600, "", "Y", ConvertTimeStamp(time()+CTimeZone::GetOffset()+600, "FULL"));
				return false;
			}
		}

		CAgent::AddAgent("CPullStack::CheckExpireAgent();", "pull", "N", 86400, "", "Y", ConvertTimeStamp(time()+CTimeZone::GetOffset()+86400, "FULL"));
		return false;
	}
}
?>