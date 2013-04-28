<?
// define("ADMIN_MODULE_NAME", "statistic");
// define("ADMIN_MODULE_ICON", "<a href=\"stat_list.php?lang=".LANG."\"><img src=\"/bitrix/images/statistic/statistic.gif\" width=\"48\" height=\"48\" border=\"0\" alt=\"".GetMessage("STAT_MODULE_TITLE")."\" title=\"".GetMessage("STAT_MODULE_TITLE")."\"></a>");

$message = null;
if(CModule::IncludeModule('statistic'))
{
	if(!$message && CStatistics::CheckForDDL())
	{
		$message = new CAdminMessage(Array("MESSAGE"=>GetMessage("STAT_NEW_INDEXES_NOT_INSTALLED").' <a href="settings.php?lang='.LANG.'&amp;mid=statistic&amp;tabControl2_active_tab=fedit4#services">'.GetMessage("STAT_NEW_INDEXES_INSTALL").'</a>', "TYPE"=>"ERROR", "HTML"=>true));
	}

	if(!$message && CModule::IncludeModule("currency"))
	{
		$base_currency = GetStatisticBaseCurrency();
		if(strlen($base_currency)<=0)
			$message = new CAdminMessage(Array("MESSAGE"=>GetMessage("STAT_BASE_CURRENCY_NOT_INSTALLED").' <a href="settings.php?lang='.LANG.'&amp;mid=statistic">('.GetMessage("STAT_CHOOSE_CURRENCY").')</a>', "TYPE"=>"ERROR", "HTML"=>true));
	}
}
?>