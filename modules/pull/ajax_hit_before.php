<?
if($_SERVER["REQUEST_METHOD"] == "POST" && array_key_exists("PULL_AJAX_CALL", $_REQUEST) && $_REQUEST["PULL_AJAX_CALL"] === "Y")
{
	if (COption::GetOptionString("pull", "guest") == 'N')
	{
		if (!defined("NO_KEEP_STATISTIC"))
		{
			// define("NO_KEEP_STATISTIC", "Y");
		}
	}
}
?>