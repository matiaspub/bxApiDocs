<?
function SetNoKeepStatistics()
{
	@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/license_key.php");
	if (strlen($LICENSE_KEY)>0)
	{
		if (strlen($_SESSION["SESS_NO_KEEP_STATISTIC"])<=0)
		{
			$_SESSION["SESS_NO_KEEP_STATISTIC"] = $_REQUEST["no_keep_statistic_".$LICENSE_KEY];
			if (strlen($_SESSION["SESS_NO_AGENT_STATISTIC"])<=0)
			{
				$_SESSION["SESS_NO_AGENT_STATISTIC"] = $_SESSION["SESS_NO_KEEP_STATISTIC"];
			}
		}
		if (strlen($_SESSION["SESS_NO_AGENT_STATISTIC"])<=0)
		{
			$_SESSION["SESS_NO_AGENT_STATISTIC"] = $_REQUEST["no_agent_statistic_".$LICENSE_KEY];
		}
	}
}
?>