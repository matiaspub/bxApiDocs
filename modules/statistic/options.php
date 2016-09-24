<?
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
IncludeModuleLangFile(__FILE__);

function CheckFDate($date, $mess) // date check
{
	global $strError;
	if (strlen($date)>0)
	{
		$str = "";
		if (!CheckDateTime($date)) $str.= $mess."<br>";
		$strError .= $str;
		if (strlen($str)>0) return false;
	}
	return true;
}

$statDB = CDatabase::GetModuleConnection('statistic');
$err_mess = "FILE: ".__FILE__."<br>\nLINE: ";
$module_id = "statistic";
$STAT_RIGHT = $APPLICATION->GetGroupRight($module_id);
$strError = "";
if ($STAT_RIGHT>="R"):

	$aTabs = array(
		array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "statistic_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
		array("DIV" => "edit6", "TAB" => GetMessage("STAT_OPT_TAB_ADV"), "ICON" => "statistic_settings", "TITLE" => GetMessage("STAT_OPT_TAB_ADV_TITLE")),
		array("DIV" => "edit7", "TAB" => GetMessage("STAT_OPT_TAB_CITY"), "ICON" => "statistic_settings", "TITLE" => GetMessage("STAT_OPT_TAB_CITY_TITLE")),
		array("DIV" => "edit2", "TAB" => GetMessage("STAT_OPT_TAB_STORAGE"), "ICON" => "statistic_settings", "TITLE" => GetMessage("STAT_OPT_TAB_STORAGE_TITLE")),
		array("DIV" => "edit3", "TAB" => GetMessage("STAT_OPT_TAB_TIME"), "ICON" => "statistic_settings", "TITLE" => GetMessage("STAT_OPT_TAB_TIME_TITLE")),
		array("DIV" => "edit4", "TAB" => GetMessage("STAT_OPT_TAB_SKIP"), "ICON" => "statistic_settings", "TITLE" => GetMessage("STAT_OPT_TAB_SKIP_TITLE")),
		array("DIV" => "edit5", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "statistic_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS")),
	);
	$tabControl = new CAdminTabControl("tabControl", $aTabs);

	$aTabs = array(
		array("DIV" => "fedit2", "TAB" => GetMessage("STAT_OPT_TAB_CLEANUP"), "ICON" => "statistic_settings", "TITLE" => GetMessage("STAT_OPT_TAB_CLEANUP_TITLE")),
	);
	if (strtolower($statDB->type)=="mysql")
		$aTabs[] = array("DIV" => "fedit3", "TAB" => GetMessage("STAT_OPT_TAB_OPTIMIZE"), "ICON" => "statistic_settings", "TITLE" => GetMessage("STAT_OPT_TAB_OPTIMIZE_TITLE"));
	if($STAT_RIGHT>="W" && ($bCheckForDDL = CStatistics::CheckForDDL()))
	{
		$aTabs[] = array("DIV" => "fedit4", "TAB" => GetMessage("STAT_OPT_TAB_INDEX"), "ICON" => "statistic_settings", "TITLE" => GetMessage("STAT_OPT_TAB_INDEX_TITLE"));
	}
	$tabControl2 = new CAdminTabControl("tabControl2", $aTabs, true, true);

	if ($REQUEST_METHOD=="POST" && $STAT_RIGHT=="W" && strlen($RestoreDefaults)>0 && check_bitrix_sessid())
	{
		COption::RemoveOption($module_id);
		$z = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
		while($zr = $z->Fetch())
			$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
		LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
	}

	$cookie_name = COption::GetOptionString("main", "cookie_name", "BITRIX_SM");

	$arOPTIONS =	Array(
		"TAB1" => Array(
			"ONLINE_INTERVAL" => Array("ONLINE_INTERVAL", GetMessage("STAT_OPT_ONLINE_INTERVAL"), Array("text", 5)),
			"RECORDS_LIMIT" => Array("RECORDS_LIMIT", GetMessage("STAT_OPT_RECORDS_LIMIT"), Array("text", 5)),
			"GRAPH_WEIGHT" => Array("GRAPH_WEIGHT", GetMessage("STAT_OPT_GRAPH_WEIGHT"), Array("text", 5)),
			"GRAPH_HEIGHT" => Array("GRAPH_HEIGHT", GetMessage("STAT_OPT_GRAPH_HEIGHT"), Array("text", 5)),
			"DIAGRAM_DIAMETER" => Array("DIAGRAM_DIAMETER", GetMessage("STAT_OPT_DIAGRAM_DIAMETER"), Array("text", 5)),
			"STAT_LIST_TOP_SIZE" => Array("STAT_LIST_TOP_SIZE", GetMessage("STAT_OPT_STAT_LIST_TOP_SIZE"), Array("text", 5)),
			"ADV_DETAIL_TOP_SIZE" => Array("ADV_DETAIL_TOP_SIZE", GetMessage("STAT_OPT_ADV_DETAIL_TOP_SIZE"), Array("text", 5)),
			"SAVE_SESSION_DATA" => Array("SAVE_SESSION_DATA", GetMessage("STAT_OPT_SAVE_SESSION_DATA"), Array("checkbox", "Y")),
			"USE_AUTO_OPTIMIZE" => "",
			"BASE_CURRENCY" => "",
		),

		"TAB2" => Array(
			1 => GetMessage("STAT_OPT_TIME_TRAFFIC_SECTION"),
			"VISIT_DAYS" => Array("VISIT_DAYS", GetMessage("STAT_OPT_TIME_VISIT_DAYS"), Array("text", 5), "CStatistics::CleanUpVisits();","b_stat_page, b_stat_page_adv"),
			"PATH_DAYS" => Array("PATH_DAYS", GetMessage("STAT_OPT_TIME_PATH_DAYS"), Array("text", 5), "CStatistics::CleanUpPathDynamic();","b_stat_path, b_stat_path_adv"),

			2 => GetMessage("STAT_OPT_TIME_REFERER_SECTION"),
			"PHRASES_DAYS" => Array("PHRASES_DAYS", GetMessage("STAT_OPT_TIME_PHRASES_DAYS"), Array("text", 5), "CStatistics::CleanUpPhrases();","b_stat_phrase_list"),
			"REFERER_LIST_DAYS" => Array("REFERER_LIST_DAYS", GetMessage("STAT_OPT_TIME_REFERER_LIST_DAYS"), Array("text", 5), "CStatistics::CleanUpRefererList();","b_stat_referer_list"),
			"REFERER_DAYS" => Array("REFERER_DAYS", GetMessage("STAT_OPT_TIME_REFERER_DAYS"), Array("text", 5), "CStatistics::CleanUpReferer();","b_stat_referer"),

			3 => GetMessage("STAT_OPT_TIME_EVENTS_SECTION"),
			"EVENTS_DAYS" => Array("EVENTS_DAYS", GetMessage("STAT_OPT_TIME_EVENTS_DAYS"), Array("text", 5), "CStatistics::CleanUpEvents();","b_stat_event_list"),
			"EVENT_DYNAMIC_DAYS"=> Array("EVENT_DYNAMIC_DAYS", GetMessage("STAT_OPT_TIME_EVENTS_DYNAMIC_DAYS"), Array("text", 5), "CStatistics::CleanUpEventDynamic();","b_stat_event_day"),

			4 => GetMessage("STAT_OPT_TIME_ADV_SECTION"),
			"ADV_GUEST_DAYS" => Array("ADV_GUEST_DAYS", GetMessage("STAT_OPT_TIME_ADV_GUEST_DAYS"), Array("text", 5), "CStatistics::CleanUpAdvGuests();","b_stat_adv_guest"),
			"ADV_DAYS" => Array("ADV_DAYS", GetMessage("STAT_OPT_TIME_ADV_DAYS"), Array("text", 5), "CStatistics::CleanUpAdvDynamic();","b_stat_adv_day, b_stat_adv_event_day"),

			5 => GetMessage("STAT_OPT_TIME_SEARCHER_SECTION"),
			"SEARCHER_HIT_DAYS" => Array("SEARCHER_HIT_DAYS", GetMessage("STAT_OPT_TIME_SEARCHER_HIT_DAYS"), Array("text", 5), "CStatistics::CleanUpSearcherHits();","b_stat_searcher_hit"),
			"SEARCHER_DAYS" => Array("SEARCHER_DAYS", GetMessage("STAT_OPT_TIME_SEARCHER_DAYS"), Array("text", 5), "CStatistics::CleanUpSearcherDynamic();","b_stat_searcher_day"),

			6 => GetMessage("STAT_OPT_TIME_GEO_SECTION"),
			"CITY_DAYS" => Array("CITY_DAYS", GetMessage("STAT_OPT_TIME_CITY_DAYS"), Array("text", 5), "CStatistics::CleanUpCities();","b_stat_city_day"),
			"COUNTRY_DAYS" => Array("COUNTRY_DAYS", GetMessage("STAT_OPT_TIME_COUNTRY_DAYS"), Array("text", 5), "CStatistics::CleanUpCountries();","b_stat_country_day"),

			7 => GetMessage("STAT_OPT_TIME_GUEST_SECTION"),
			"GUEST_DAYS" => Array("GUEST_DAYS", GetMessage("STAT_OPT_TIME_GUEST_DAYS"), Array("text", 5), "CStatistics::CleanUpGuests();","b_stat_guest"),

			8 => GetMessage("STAT_OPT_TIME_SESSION_SECTION"),
			"SESSION_DAYS" => Array("SESSION_DAYS", GetMessage("STAT_OPT_TIME_SESSION_DAYS"), Array("text", 5), "CStatistics::CleanUpSessions();","b_stat_session"),

			9 => GetMessage("STAT_OPT_TIME_HIT_TITLE"),
			"HIT_DAYS" => Array("HIT_DAYS", GetMessage("STAT_OPT_TIME_HIT_DAYS"), Array("text", 5), "CStatistics::CleanUpHits();","b_stat_hit"),
		),

		"TAB3" => Array(
			1 => GetMessage("STAT_OPT_STORAGE_TRAFFIC_SECTION"),
			"SAVE_VISITS" => Array("SAVE_VISITS", GetMessage("STAT_OPT_STORAGE_SAVE_VISITS"), Array("checkbox", "Y")),
			"SAVE_PATH_DATA" => Array("SAVE_PATH_DATA", GetMessage("STAT_OPT_STORAGE_SAVE_PATH_DATA"), Array("checkbox", "Y")),
			"MAX_PATH_STEPS" => Array("MAX_PATH_STEPS", GetMessage("STAT_OPT_STORAGE_MAX_PATH_STEPS"), Array("text", 5)),
			"IMPORTANT_PAGE_PARAMS" => Array("IMPORTANT_PAGE_PARAMS", GetMessage("STAT_OPT_STORAGE_IMPORTANT_PAGE_PARAMS"), Array("text", 40)),
			"DIRECTORY_INDEX" => Array("DIRECTORY_INDEX", GetMessage("STAT_OPT_STORAGE_DIRECTORY_INDEX"), Array("text", 40)),

			2 => GetMessage("STAT_OPT_STORAGE_SEARCHER_SECTION"),
			"BROWSERS" => "",

			3 => GetMessage("STAT_OPT_STORAGE_EVENTS_SECTION"),
			"EVENT_GID_BASE64_ENCODE" => Array("EVENT_GID_BASE64_ENCODE", GetMessage("STAT_OPT_STORAGE_EVENT_GID_BASE64_ENCODE"), Array("checkbox", "Y")),
			"EVENT_GID_SITE_ID" => Array("EVENT_GID_SITE_ID", GetMessage("STAT_OPT_STORAGE2_EVENT_GID_SITE_ID", array("#HREF#"=>'/bitrix/admin/event_edit.php?lang='.LANGUAGE_ID)), Array("text", 20)),
			"USER_EVENTS_LOAD_HANDLERS_PATH" => Array("USER_EVENTS_LOAD_HANDLERS_PATH", GetMessage("STAT_OPT_STORAGE2_USER_EVENTS_LOAD_HANDLERS_PATH", array("#HREF#"=>'/bitrix/admin/event_edit.php?lang='.LANGUAGE_ID)), Array("text", 40)),

			4 => GetMessage("STAT_OPT_STORAGE_REFERER_SECTION"),
			"SAVE_REFERERS" => Array("SAVE_REFERERS", GetMessage("STAT_OPT_STORAGE_SAVE_REFERERS"), Array("checkbox", "Y")),
			"REFERER_TOP" => Array("REFERER_TOP", GetMessage("STAT_OPT_STORAGE_REFERER_TOP"), Array("text", 5)),

			5 => GetMessage("STAT_OPT_STORAGE_HIT_SECTION"),
			"SAVE_HITS" => Array("SAVE_HITS", GetMessage("STAT_OPT_STORAGE_SAVE_HITS"), Array("checkbox", "Y")),

		),

		"TAB4" => Array(
			"SKIP_STATISTIC_WHAT"		=> "",
			"SKIP_STATISTIC_GROUPS"		=> "",
			"SKIP_STATISTIC_IP_RANGES"	=> "",
		),

		"TAB5" => Array(
			"ADV_NA" => Array("ADV_NA", GetMessage("STAT_OPT_ADV_USE_DEFAULT_ADV"), Array("checkbox", "Y")),
			"ADV_AUTO_CREATE" => Array("ADV_AUTO_CREATE", GetMessage("STAT_OPT_ADV_AUTO_CREATE"), Array("checkbox", "Y")),
			"REFERER_CHECK" => Array("REFERER_CHECK", GetMessage("STAT_OPT_REFERER_CHECK2"), Array("checkbox", "Y")),
			"SEARCHER_EVENTS" => Array("SEARCHER_EVENTS", GetMessage("STAT_OPT_SEARCHER_EVENTS"), Array("checkbox", "Y")),
			"REFERER1_SYN" => Array("REFERER1_SYN", GetMessage("STAT_OPT_ADV_REFERER1_SYN"), Array("text", 30)),
			"REFERER2_SYN" => Array("REFERER2_SYN", GetMessage("STAT_OPT_ADV_REFERER2_SYN"), Array("text", 30)),
			"REFERER3_SYN" => Array("REFERER3_SYN", GetMessage("STAT_OPT_ADV_REFERER3_SYN"), Array("text", 30)),
			"ADV_EVENTS_DEFAULT" => "",

			1 => GetMessage("STAT_OPT_ADV_OPENSTAT_SECTION"),
			"OPENSTAT_ACTIVE" => Array("OPENSTAT_ACTIVE", GetMessage("STAT_OPT_ADV_OPENSTAT_ACTIVE"), Array("checkbox", "N")),
			"OPENSTAT_R1_TEMPLATE" => Array("OPENSTAT_R1_TEMPLATE", GetMessage("STAT_OPT_ADV_OPENSTAT_R1_TEMPLATE"), Array("text", 30)),
			"OPENSTAT_R2_TEMPLATE" => Array("OPENSTAT_R2_TEMPLATE", GetMessage("STAT_OPT_ADV_OPENSTAT_R2_TEMPLATE"), Array("text", 30)),
		),
	);

	if($REQUEST_METHOD=="POST" && strlen($Update.$Apply)>0 && $STAT_RIGHT>="W" && check_bitrix_sessid())
	{
		if (CheckFDate($next_exec, GetMessage("STAT_OPT_WRONG_NEXT_EXEC")))
		{
			foreach($arOPTIONS as $arOp)
			{
				foreach($arOp as $arOption)
				{
					if (is_array($arOption))
					{
						$name = $arOption[0];
						$val = $_REQUEST[$name];
						$type = $arOption[2][0];
						if($type=="checkbox" && $val!="Y")
							$val="N";
						COption::SetOptionString($module_id, $name, $val);
						if (${$name."_clear"}=="Y")
						{
							$func=$arOption[3];
							eval($func);
						}
					}
				}
			}

			COption::SetOptionString($module_id, "IP_LOOKUP_CLASS", $IP_LOOKUP_CLASS);
			COption::SetOptionString($module_id, "ADV_EVENTS_DEFAULT", $ADV_EVENTS_DEFAULT);
			COption::SetOptionString($module_id, "USE_AUTO_OPTIMIZE", $USE_AUTO_OPTIMIZE);
			InitBVar($recount_base_currency);

			if ($recount_base_currency=="Y")
				CStatistics::RecountBaseCurrency($BASE_CURRENCY);

			COption::SetOptionString($module_id, "BASE_CURRENCY", $BASE_CURRENCY);
			$arr = array();
			$arr = preg_split("/[\n\r]+/", $BROWSERS);
			$statDB->Query("DELETE FROM b_stat_browser", false, $err_mess.__LINE__);
			foreach ($arr as $u)
			{
				if (strlen($u)>0)
				{
					$arFields = Array("USER_AGENT" => "'".$statDB->ForSql($u,255)."'");
					$statDB->Insert("b_stat_browser",$arFields, $err_mess.__LINE__);
				}
			}

			if($SKIP_STATISTIC_WHAT!='groups' && $SKIP_STATISTIC_WHAT!='ranges' && $SKIP_STATISTIC_WHAT!='both')
				$SKIP_STATISTIC_WHAT='none';
			COption::SetOptionString($module_id, "SKIP_STATISTIC_WHAT", $SKIP_STATISTIC_WHAT);

			if(!is_array($arSKIP_STATISTIC_GROUPS))
				$arSKIP_STATISTIC_GROUPS=array();
			if($SKIP_STATISTIC_WHAT=='groups' || $SKIP_STATISTIC_WHAT=='both')
				if(count($arSKIP_STATISTIC_GROUPS)<1)
					$strError.=GetMessage("STAT_OPT_ERR_NO_GROUPS")."<br>";
				else
					COption::SetOptionString($module_id, "SKIP_STATISTIC_GROUPS", implode(",", $arSKIP_STATISTIC_GROUPS));
			else
				COption::SetOptionString($module_id, "SKIP_STATISTIC_GROUPS", "");

			if($SKIP_STATISTIC_WHAT=='ranges' || $SKIP_STATISTIC_WHAT=='both')
				if($SKIP_STATISTIC_IP_RANGES=="")
					$strError.=GetMessage("STAT_OPT_ERR_NO_RANGES")."<br>";
				else
					COption::SetOptionString($module_id, "SKIP_STATISTIC_IP_RANGES", $SKIP_STATISTIC_IP_RANGES);
			else
				COption::SetOptionString($module_id, "SKIP_STATISTIC_IP_RANGES", "");

			CAgent::RemoveAgent("SendDailyStatistics();","statistic");
			if (strlen($next_exec)>0)
			{
				CAgent::AddAgent("SendDailyStatistics();","statistic","Y", 86400,"","Y",$next_exec);
			}

			if($DEFENCE_ON!="Y") $DEFENCE_ON="N";
			COption::SetOptionString($module_id, "DEFENCE_ON", $DEFENCE_ON);
			if ($DEFENCE_ON=="Y")
			{
				COption::SetOptionString($module_id, "DEFENCE_STACK_TIME", $DEFENCE_STACK_TIME);
				COption::SetOptionString($module_id, "DEFENCE_MAX_STACK_HITS", $DEFENCE_MAX_STACK_HITS);
				COption::SetOptionString($module_id, "DEFENCE_DELAY", $DEFENCE_DELAY);
				COption::SetOptionString($module_id, "DEFENCE_LOG", $DEFENCE_LOG==="Y"? "Y": "N");
			}
		}

		$Update = $Update.$Apply;
		ob_start();
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
		ob_end_clean();

		if($strError=="")
		{
			if(strlen($Update)>0 && strlen($_REQUEST["back_url_settings"])>0)
				LocalRedirect($_REQUEST["back_url_settings"]);
			else
				LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
		}
	}

	$ADV_EVENTS_DEFAULT = COption::GetOptionString($module_id, "ADV_EVENTS_DEFAULT");
	$USE_AUTO_OPTIMIZE = COption::GetOptionString($module_id, "USE_AUTO_OPTIMIZE");
	$BASE_CURRENCY = COption::GetOptionString($module_id, "BASE_CURRENCY");
	$DEFENCE_ON = COption::GetOptionString($module_id, "DEFENCE_ON");
	$DEFENCE_STACK_TIME = COption::GetOptionString($module_id, "DEFENCE_STACK_TIME");
	$DEFENCE_MAX_STACK_HITS = COption::GetOptionString($module_id, "DEFENCE_MAX_STACK_HITS");
	$DEFENCE_DELAY = COption::GetOptionString($module_id, "DEFENCE_DELAY");
	$DEFENCE_LOG = COption::GetOptionString($module_id, "DEFENCE_LOG");

	$BROWSERS = "";
	$rows = $statDB->Query("SELECT USER_AGENT FROM b_stat_browser ORDER BY ID", false, $err_mess.__LINE__);
	while ($row = $rows->Fetch())
		$BROWSERS .= $row["USER_AGENT"]."\n";

	$SKIP_STATISTIC_WHAT = COption::GetOptionString($module_id, "SKIP_STATISTIC_WHAT");
	$arSKIP_STATISTIC_GROUPS = explode(",", COption::GetOptionString($module_id, "SKIP_STATISTIC_GROUPS"));
	$SKIP_STATISTIC_IP_RANGES = COption::GetOptionString($module_id, "SKIP_STATISTIC_IP_RANGES");

	if (strlen($cleanup)>0 && $REQUEST_METHOD=="POST" && $STAT_RIGHT>="W" && check_bitrix_sessid())
	{
		if (CheckFDate($cleanup_date, GetMessage("STAT_OPT_WRONG_CLEANUP_DATE")))
		{
			set_time_limit(0);
			ignore_user_abort(true);
			if (CStatistics::CleanUp($cleanup_date, $arErrors))
			{
				$_SESSION["STAT_strNote"] .= GetMessage("STAT_OPT_CLEAN_UP_OK")."<br>";
			}
			else
			{
				$strError .= GetMessage("STAT_OPT_CLEAN_UP_ERRORS")."<br><pre>".mydump($arErrors)."</pre><br>";
			}
		}
		if($strError=="")
		{
			LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl2->ActiveTabParam());
		}
	}

	if(strlen($runsql)>0 && $REQUEST_METHOD=="POST" && $STAT_RIGHT>="W" && check_bitrix_sessid())
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$bDone = true;
		if(count($ar = CStatistics::GetDDL())>0)
		{
			foreach($ar as $arDDL)
			{
				if(!CStatistics::ExecuteDDL($arDDL["ID"]))
				{
					$strError.=$arDDL["SQL_TEXT"].":(".$statDB->db_Error.")<br>";
					$bDone=false;
				}
			}
		}
		if($bDone)
		{
			$_SESSION["STAT_strNote"] .= GetMessage("STAT_OPT_INDEXED")."<br>";
			COption::RemoveOption("statistic", "sql_to_run");
		}
		if($strError=="")
		{
			LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl2->ActiveTabParam());
		}
	}

	if (strlen($optimize)>0 && $REQUEST_METHOD=="POST" && $STAT_RIGHT>="W" && check_bitrix_sessid())
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$fname = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/install/db/".strtolower($statDB->type). "/optimize.sql";
		if (file_exists($fname))
		{
			$arErrors = $statDB->RunSQLBatch($fname);
			if (!$arErrors)
				$_SESSION["STAT_strNote"] .= GetMessage("STAT_OPT_OPTIMIZED")."<br>";
			else
				$strError .= GetMessage("STAT_OPT_OPTIMIZE_ERRORS")."<br>".mydump($arErrors)."<br>";
		}
		if($strError=="")
		{
			LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl2->ActiveTabParam());
		}
	}

	if(strlen($strError)>0)
		CAdminMessage::ShowMessage($strError);
	if(strlen($_SESSION["STAT_strNote"])>0)
	{
		CAdminMessage::ShowNote($_SESSION["STAT_strNote"]);
		unset($_SESSION["STAT_strNote"]);
	}

	$tabControl->Begin();
	?>
	<form name="form_settings" method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&amp;lang=<?=LANGUAGE_ID?>">
	<?$tabControl->BeginNextTab();?>
	<tr>
		<td width="40%">
			<?echo GetMessage("STAT_OPT_DAILY_REPORT_TIME2")?>
		</td>
		<td nowrap width="60%"><?
			if (strlen($next_exec)<=0 || strlen($strError)<=0)
			{
				$strSql = "
					SELECT ".$DB->DateToCharFunction("NEXT_EXEC")."	NEXT_EXEC
					FROM b_agent
					WHERE NAME='SendDailyStatistics();' and MODULE_ID='statistic'
				";
				$z = $GLOBALS["DB"]->Query($strSql, false, $err_mess.__LINE__);
				$zr = $z->Fetch();
				$next_exec = $zr["NEXT_EXEC"];
			}

			echo CalendarDate("next_exec", htmlspecialcharsbx($next_exec), "form_settings", "19");
		?></td>
	</tr>
	<?
	foreach($arOPTIONS["TAB1"] as $key => $Option):
		if (!is_array($Option)):
			if($key == "USE_AUTO_OPTIMIZE" && strtolower($statDB->type) == "mysql"):?>
				<tr>
					<td><label for="<?=$key?>"><?echo GetMessage("STAT_OPT_USE_AUTO_OPTIMIZE")?></label></td>
					<td nowrap><input type="checkbox" name="<?=$key?>" id="<?=$key?>" value="Y" <?if(${$key}=="Y") echo "checked";?>></td>
				</tr>
			<?elseif($key == "BASE_CURRENCY" && CModule::IncludeModule("currency")):?>
				<tr>
					<td class="adm-detail-valign-top"><?echo GetMessage("STAT_OPT_BASE_CURRENCY")?></td>
					<td><?echo CCurrency::SelectBox("BASE_CURRENCY", $BASE_CURRENCY, " ", True, "") ?><br>
					<input type="checkbox" name="recount_base_currency" id="recount_base_currency" value="Y"><label for="recount_base_currency"><?echo GetMessage("STAT_OPT_DO_RECOUNT")?></label></td>
				</tr>
			<?endif;
		else:
			$val = COption::GetOptionString($module_id, $Option[0]);
			$type = $Option[2];
			?>
			<tr>
				<td <?if($type[0]=="textarea") echo 'class="adm-detail-valign-top"'?>><label for="<?echo htmlspecialcharsbx($Option[0])?>"><?echo $Option[1]?></label></td>
				<td nowrap>
					<?if($type[0]=="checkbox"):?>
						<input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0])?>" id="<?echo htmlspecialcharsbx($Option[0])?>" value="Y"<?if($val=="Y")echo" checked";?>>
					<?elseif($type[0]=="text"):?>
						<input type="text" size="<?echo $type[1]?>" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0])?>">
					<?elseif($type[0]=="textarea"):?>
						<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0])?>"><?echo htmlspecialcharsbx($val)?></textarea>
					<?endif;?>
				</td>
			</tr>
		<?endif;
	endforeach;?>
	<tr class="heading">
		<td align="center" colspan="2" nowrap><?echo GetMessage("STAT_OPT_GRABBER_DEFENCE_SECTION")?></td>
	</tr>
	<?if (CModule::IncludeModule("fileman")):?>
	<tr>
		<td align="center" colspan=2>[ <a href="/bitrix/admin/fileman_file_edit.php?lang=<?=LANGUAGE_ID?>&amp;full_src=Y&amp;path=%2Fbitrix%2Factivity_limit.php"><?echo GetMessage("STAT_OPT_GRABBER_EDIT_503_TEMPLATE_LINK")?></a> ]</td>
	</tr>
	<?endif;?>
	<tr>
		<td nowrap><label for="DEFENCE_ON"><?echo GetMessage("STAT_OPT_DEFENCE_ON")?></label></td>
		<td><?echo InputType("checkbox","DEFENCE_ON","Y",$DEFENCE_ON,false,"","OnClick=\"ChangeDefenceSwitch()\" id=\"DEFENCE_ON\"")?></td>
	</tr>
	<tr>
		<td nowrap><?echo GetMessage("STAT_OPT_DEFENCE_DELAY")?></td>
		<td><input size="3" type="text" name="DEFENCE_DELAY" id="DEFENCE_DELAY" value="<?=htmlspecialcharsbx($DEFENCE_DELAY)?>">&nbsp;<?echo GetMessage("STAT_OPT_DEFENCE_DELAY_MEASURE_SEC")?></td>
	</tr>
	<tr>
		<td nowrap><?echo GetMessage("STAT_OPT_DEFENCE_STACK_TIME")?></td>
		<td><input size="3" type="text" name="DEFENCE_STACK_TIME" id="DEFENCE_STACK_TIME" value="<?=htmlspecialcharsbx($DEFENCE_STACK_TIME)?>">&nbsp;<?echo GetMessage("STAT_OPT_DEFENCE_STACK_TIME_MEASURE_SEC")?></td>
	</tr>
	<tr>
		<td nowrap><?echo GetMessage("STAT_OPT_DEFENCE_MAX_STACK_HITS")?></td>
		<td><input size="3" type="text" name="DEFENCE_MAX_STACK_HITS" id="DEFENCE_MAX_STACK_HITS" value="<?=htmlspecialcharsbx($DEFENCE_MAX_STACK_HITS)?>">&nbsp;<?echo GetMessage("STAT_OPT_DEFENCE_MAX_STACK_HITS_MEASURE")?></td>
	</tr>
	<tr>
		<td nowrap><label for="DEFENCE_LOG"><?echo GetMessage("STAT_OPT_DEFENCE_LOG", array("#HREF#"=>"/bitrix/admin/event_log.php?lang=".LANGUAGE_ID."&set_filter=Y&find_type=audit_type_id&find_audit_type[]=STAT_ACTIVITY_LIMIT"))?></label></td>
		<td><?echo InputType("checkbox", "DEFENCE_LOG", "Y", $DEFENCE_LOG)?></td>
	</tr>
	<?$tabControl->EndTab();?>
	<script language="JavaScript">
	function ChangeDefenceSwitch()
	{
		var obSwitch = document.getElementById("DEFENCE_ON");
		document.getElementById("DEFENCE_DELAY").disabled = !obSwitch.checked;
		document.getElementById("DEFENCE_STACK_TIME").disabled = !obSwitch.checked;
		document.getElementById("DEFENCE_MAX_STACK_HITS").disabled = !obSwitch.checked;
		document.getElementById("DEFENCE_MAX_STACK_HITS").disabled = !obSwitch.checked;
		document.getElementById("DEFENCE_LOG").disabled = !obSwitch.checked;
	}
	ChangeDefenceSwitch();
	</script>

	<?$tabControl->BeginNextTab();
	foreach($arOPTIONS["TAB5"] as $key => $Option):
		if(!is_array($Option)):
			if($key == "ADV_EVENTS_DEFAULT"):?>
				<tr>
					<td width="40%"><?echo GetMessage("STAT_OPT_ADV_EVENTS_DEFAULT")?></td>
					<td nowrap width="60%"><?
						$arr = array(
							"reference" => array(
								GetMessage("STAT_OPT_ADV_EVENTS_SHOW_LINK"),
								GetMessage("STAT_OPT_ADV_EVENTS_SHOW_LIST"),
								GetMessage("STAT_OPT_ADV_EVENTS_GROUP_BY_EVENT1"),
								GetMessage("STAT_OPT_ADV_EVENTS_GROUP_BY_EVENT2"),
							),
							"reference_id" => array(
								"link",
								"list",
								"event1",
								"event2",
							),
						);
						echo SelectBoxFromArray("ADV_EVENTS_DEFAULT", $arr, htmlspecialcharsbx($ADV_EVENTS_DEFAULT));
					?></td>
				</tr>
			<?else:?>
				<tr class="heading">
					<td valign="top" colspan="2" align="center"><b><?=$Option?></b></td>
				</tr>
			<?endif;
		else:
			$val = COption::GetOptionString($module_id, $Option[0]);
			$type = $Option[2];
			?>
			<tr>
				<td width="40%" <?if($type[0]=="textarea") echo 'class="adm-detail-valign-top"'?>><label for="<?echo htmlspecialcharsbx($Option[0])?>"><?echo $Option[1]?></label></td>
				<td nowrap width="60%">
					<?if($type[0]=="checkbox"):?>
						<input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0])?>" id="<?echo htmlspecialcharsbx($Option[0])?>" value="Y"<?if($val=="Y")echo" checked";?>>
					<?elseif($type[0]=="text"):?>
						<input type="text" size="<?echo $type[1]?>" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0])?>">
					<?elseif($type[0]=="textarea"):?>
						<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0])?>"><?echo htmlspecialcharsbx($val)?></textarea>
					<?endif;?>
				</td>
			</tr>
		<?endif;
	endforeach;

	$tabControl->BeginNextTab();?>
	<tr>
		<td colspan="2">
			<?echo GetMessage("STAT_OPT_CITY_HEADER")?><br><br>
			<table class="internal">
				<tr class="heading">
					<td><?echo GetMessage("STAT_OPT_CITY_SOURCE")?></td>
					<td><?echo GetMessage("STAT_OPT_CITY_AVAILABLE")?></td>
					<td><?echo GetMessage("STAT_OPT_CITY_COUNTRY_LOOKUP")?></td>
					<td><?echo GetMessage("STAT_OPT_CITY_CITY_LOOKUP")?></td>
					<td><?echo GetMessage("STAT_OPT_CITY_IS_IN_USE")?></td>
				</tr>
				<?
				$selected = CCity::GetHandler();
				foreach (GetModuleEvents($module_id, "OnCityLookup", true) as $arEvent):
					$ob = ExecuteModuleEventEx($arEvent);
					$arDescr = $ob->GetDescription();?>
					<tr>
						<td><?echo $arDescr["DESCRIPTION"]?></td>
						<td style="text-align:center"><?echo $arDescr["IS_INSTALLED"]? GetMessage("MAIN_YES"): GetMessage("MAIN_NO")?></td>
						<td style="text-align:center"><?echo $arDescr["IS_INSTALLED"]? ($arDescr["CAN_LOOKUP_COUNTRY"]? GetMessage("MAIN_YES"): GetMessage("MAIN_NO")): "-"?></td>
						<td style="text-align:center"><?echo $arDescr["IS_INSTALLED"]? ($arDescr["CAN_LOOKUP_CITY"]? GetMessage("MAIN_YES"): GetMessage("MAIN_NO")): "-"?></td>
						<td style="text-align:center"><input type="radio" name="IP_LOOKUP_CLASS" value="<?echo $arDescr["CLASS"]?>" <?echo ($arDescr["CLASS"] == $selected? "checked": "")?>></td>
					</tr>
				<?endforeach?>
			</table>

			<?
			echo BeginNote();
			$obCity = new CCity();
			$arCity = $obCity->GetFullInfo();
			foreach($arCity as $FIELD_ID => $arField)
			{
				echo $arField["TITLE"], ": ", $arField["VALUE"], "<br>";
			}
			echo EndNote();
			?>

		</td>
	</tr>

	<?
	$tabControl->BeginNextTab();
	foreach($arOPTIONS["TAB3"] as $key => $Option):
		if(!is_array($Option)):
			if ($key == "BROWSERS"):?>
				<tr>
					<td class="adm-detail-valign-top" width="40%"><?=GetMessage("STAT_OPT_STORAGE_BROWSERS")?></td>
					<td nowrap width="60%"><textarea name="BROWSERS" rows="5" cols="30"><?=htmlspecialcharsbx($BROWSERS)?></textarea></td>
				</tr>
			<?else:?>
				<tr class="heading">
					<td valign="top" colspan="2" align="center"><b><?=$Option?></b></td>
				</tr>
			<?endif;
		else:
			$val = COption::GetOptionString($module_id, $Option[0]);
			$type = $Option[2];
			?>
			<tr>
				<td <?if($type[0]=="textarea") echo 'class="adm-detail-valign-top"'?> width="40%"><label for="<?echo htmlspecialcharsbx($Option[0])?>"><?echo $Option[1]?></label></td>
				<td nowrap width="60%">
					<?if($type[0]=="checkbox"):?>
						<input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0])?>" id="<?echo htmlspecialcharsbx($Option[0])?>" value="Y"<?if($val=="Y")echo" checked";?>>
					<?elseif($type[0]=="text"):?>
						<input type="text" size="<?echo $type[1]?>" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0])?>">
					<?elseif($type[0]=="textarea"):?>
						<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0])?>"><?echo htmlspecialcharsbx($val)?></textarea>
					<?endif;?>
				</td>
			</tr>
		<?endif;
	endforeach;

	$arTABLES = array();
	if(strtolower($statDB->type) == "mysql")
	{
		$strSql = "SHOW TABLE STATUS like 'b_stat_%'";
		$rs = $statDB->Query($strSql,false,$err_mess.__LINE__);
		while($ar = $rs->Fetch())
			$arTABLES[strtolower(trim($ar["Name"]))] = $ar["Rows"];
	}

	$tabControl->BeginNextTab();
	foreach($arOPTIONS["TAB2"] as $key => $Option):
		if(!is_array($Option)):?>
			<tr class="heading">
				<td valign="top" colspan="2" align="center"><b><?=$Option?></b></td>
			</tr>
		<?else:
			$val = COption::GetOptionString($module_id, $Option[0]);
			$type = $Option[2];
			?>
			<tr>
				<td width="40%"><?echo $Option[1]?></td>
				<td nowrap width="60%">
					<?if($type[0]=="text"):
						if (strlen($Option[4])>0)
						{
							$count = 0;
							$arr = explode(",",$Option[4]);
							if(strtolower($statDB->type) == "mysql")
							{
								foreach($arr as $table) $count += intval($arTABLES[strtolower(trim($table))]);
							}
							else
							{
								foreach($arr as $table)
								{
									$strSql = "SELECT count('x') as COUNT FROM ".$table;
									$z = $statDB->Query($strSql,false,$err_mess.__LINE__);
									$zr = $z->Fetch();
									$count += intval($zr["COUNT"]);
								}
							}
						}
						?>
						<input type="text" size="<?echo $type[1]?>" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0])?>"><?
						if(strlen($Option[3]) > 0):?>
							&nbsp;<label for="<?echo htmlspecialcharsbx($Option[0])?>_clear"><?echo GetMessage("STAT_OPT_TIME_CLEAR")?>:</label>&nbsp;<input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0])?>_clear" id="<?echo htmlspecialcharsbx($Option[0])?>_clear" value="Y">
						<?endif;?>
						<?if(strlen($Option[4]) > 0):?>
							&nbsp;&nbsp;(<?echo GetMessage("STAT_OPT_TIME_RECORDS")?>&nbsp;<?echo $count?>)
						<?endif;?>
					<?endif?>
				</td>
			</tr>
		<?endif;
	endforeach;

	$tabControl->BeginNextTab();
	foreach($arOPTIONS["TAB4"] as $key => $Option):
		if(!is_array($Option)):
			if ($key=="SKIP_STATISTIC_WHAT"):?>
				<tr>
					<td class="adm-detail-valign-top" width="40%"><?echo GetMessage("STAT_OPT_SKIP_RULES")?>:</td>
					<td width="60%">
						<input type="radio" name="SKIP_STATISTIC_WHAT" id="SKIP_STATISTIC_WHAT_none" value="none" OnClick="manageSkip('none')"<?=$SKIP_STATISTIC_WHAT=="none"?" checked":""?>><label for="SKIP_STATISTIC_WHAT_none"><?=GetMessage("STAT_OPT_SKIP_NONE")?></label><br>
						<input type="radio" name="SKIP_STATISTIC_WHAT" id="SKIP_STATISTIC_WHAT_groups" value="groups" OnClick="manageSkip('groups')"<?=$SKIP_STATISTIC_WHAT=="groups"?" checked":""?>><label for="SKIP_STATISTIC_WHAT_groups"><?=GetMessage("STAT_OPT_SKIP_GROUPS")?></label><br>
						<input type="radio" name="SKIP_STATISTIC_WHAT" id="SKIP_STATISTIC_WHAT_ranges" value="ranges" OnClick="manageSkip('ranges')"<?=$SKIP_STATISTIC_WHAT=="ranges"?" checked":""?>><label for="SKIP_STATISTIC_WHAT_ranges"><?=GetMessage("STAT_OPT_SKIP_RANGES")?></label><br>
						<input type="radio" name="SKIP_STATISTIC_WHAT" id="SKIP_STATISTIC_WHAT_both" value="both" OnClick="manageSkip('both')"<?=$SKIP_STATISTIC_WHAT=="both"?" checked":""?>><label for="SKIP_STATISTIC_WHAT_both"><?=GetMessage("STAT_OPT_SKIP_BOTH")?></label><br>
					</td>
				</tr>
			<?elseif($key == "SKIP_STATISTIC_GROUPS"):
				$rUserGroups = CGroup::GetList($by = "c_sort", $order = "asc");
				while ($arUserGroups = $rUserGroups->Fetch())
				{
					$ug_id[] = $arUserGroups["ID"];
					$ug[] = "[".$arUserGroups["ID"]."] ".$arUserGroups["NAME"];
				}
				?>
				<tr>
					<td class="adm-detail-valign-top" width="40%"><?=GetMessage("STAT_OPT_SKIP_GROUPS_LABEL")?>:<br><img src="/bitrix/images/statistic/mouse.gif" width="44" height="21" border=0 alt=""></td>
					<td nowrap width="60%"><?=SelectBoxMFromArray("arSKIP_STATISTIC_GROUPS[]", array("REFERENCE" => $ug, "REFERENCE_ID" => $ug_id), $arSKIP_STATISTIC_GROUPS, "", false, 10);?></td>
				</tr>
			<?elseif($key == "SKIP_STATISTIC_IP_RANGES"):?>
				<tr>
					<td class="adm-detail-valign-top" width="40%"><?=GetMessage("STAT_OPT_SKIP_RANGES_LABEL")?>:</td>
					<td nowrap width="60%"><textarea name="SKIP_STATISTIC_IP_RANGES" rows="5" cols="30"><?=htmlspecialcharsbx($SKIP_STATISTIC_IP_RANGES)?></textarea><br>
							<?=GetMessage("STAT_OPT_SKIP_SAMPLE")?>:<br>
							192.168.0.2-192.168.0.20<br>
							10.0.0.7-10.0.0.7</td>
				</tr>
			<?else:?>
				<tr class="heading">
					<td valign="top" colspan="2" align="center"><b><?=$Option?></b></td>
				</tr>
			<?endif;
		else:
			$val = COption::GetOptionString($module_id, $Option[0]);
			$type = $Option[2];
			?>
			<tr>
				<td width="40%" <?if($type[0]=="textarea") echo 'class="adm-detail-valign-top"'?>><label for="<?echo htmlspecialcharsbx($Option[0])?>"><?echo $Option[1]?></label></td>
				<td nowrap width="60%">
					<?if($type[0]=="checkbox"):?>
						<input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0])?>" id="<?echo htmlspecialcharsbx($Option[0])?>" value="Y"<?if($val=="Y")echo" checked";?>>
					<?elseif($type[0]=="text"):?>
						<input type="text" size="<?echo $type[1]?>" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0])?>">
					<?elseif($type[0]=="textarea"):?>
						<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0])?>"><?echo htmlspecialcharsbx($val)?></textarea>
					<?endif;?>
				</td>
			</tr>
		<?endif;
	endforeach;
	$tabControl->EndTab();
	?>
	<script language="JavaScript">
		manageSkip(false);
		function manageSkip(what)
		{
			var groups = document.getElementsByName('arSKIP_STATISTIC_GROUPS[]')[0];
			var ranges = document.getElementsByName('SKIP_STATISTIC_IP_RANGES')[0];
			if(what==false)
			{
				var radio = document.getElementsByName('SKIP_STATISTIC_WHAT');
				for(var i=0;i<radio.length;i++)
					if(radio[i].checked)
						what=radio[i].value;
			}
			groups.disabled = what != 'groups' && what != 'both';
			ranges.disabled = what != 'ranges' && what != 'both';
		}
	</script>

	<?
	$tabControl->BeginNextTab();?>
	<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
	<?$tabControl->Buttons();?>
	<input <?if ($STAT_RIGHT<"W") echo "disabled" ?> type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>" class="adm-btn-save">
	<input <?if ($STAT_RIGHT<"W") echo "disabled" ?> type="submit" name="Apply" value="<?=GetMessage("MAIN_OPT_APPLY")?>" title="<?=GetMessage("MAIN_OPT_APPLY_TITLE")?>">
	<?if(strlen($_REQUEST["back_url_settings"])>0):?>
		<input <?if ($STAT_RIGHT<"W") echo "disabled" ?> type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
	<?endif?>
	<input <?if ($STAT_RIGHT<"W") echo "disabled" ?> type="submit" name="RestoreDefaults" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="return confirm('<?echo GetMessageJS("MAIN_HINT_RESTORE_DEFAULTS_WARNING")?>')" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
	<?=bitrix_sessid_post();?>
	<?$tabControl->End();?>
	<?if(strlen($_REQUEST["back_url_settings"])>0):?>
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
	<?endif?>
	</form>

	<a name="services"></a>
	<h2><?echo GetMessage("STAT_OPT_SYSTEM_PROC")?></h2>

	<?$tabControl2->Begin();?>

	<form name="cleanupform" method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&amp;lang=<?=LANGUAGE_ID?>">
	<?$tabControl2->BeginNextTab();?>
		<tr>
			<td width="40%" nowrap><?echo GetMessage("STAT_OPT_CLEANUP_DATE2")?></td>
			<td width="60%"><?echo CalendarDate("cleanup_date", htmlspecialcharsbx($cleanup_date), "cleanupform", "10")?></td>
		</tr>
		<tr>
			<td align="left" colspan="2"><input type="button" <?if ($STAT_RIGHT<"W") echo "disabled" ?> name="cleanup" value="<?echo GetMessage("STAT_OPT_CLEANUP_BUTTON")?>" OnClick="javascript: CleanUpSubmit();"><input type="hidden" name="cleanup" value="Y"><input type="hidden" name="lang" value="<?=LANGUAGE_ID?>"></td>
		</tr>
	<?$tabControl2->EndTab();?>
	<script language="JavaScript">
		function CleanUpSubmit()
		{
			if(confirm('<?=GetMessageJS("STAT_OPT_CLEANUP_CONFIRMATION")?>'))
				document.cleanupform.submit();
		}
	</script>
	<?if(strlen($_REQUEST["back_url_settings"])>0):?>
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
	<?endif?>
	<?=bitrix_sessid_post();?>
	</form>

	<?if (strtolower($statDB->type)=="mysql"):?>
		<form name="optimizeform" method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&amp;lang=<?=LANGUAGE_ID?>">
		<?$tabControl2->BeginNextTab();?>
		<tr>
			<td align="left" colspan="2">
				<input type="button" <?if ($STAT_RIGHT<"W") echo "disabled" ?> name="cleanup" value="<?echo GetMessage("STAT_OPT_OPTIMIZE_BUTTON")?>" OnClick="javascript: OptimizeSubmit();"><input type="hidden" name="optimize" value="Y"><input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
				<SCRIPT LANGUAGE="JavaScript">
					function OptimizeSubmit()
					{
						if(confirm('<?=GetMessageJS("STAT_OPT_OPTIMIZE_CONFIRMATION")?>'))	document.optimizeform.submit();
					}
				</SCRIPT>
			</td>
		</tr>
		<?$tabControl2->EndTab();?>
		<?if(strlen($_REQUEST["back_url_settings"])>0):?>
			<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
		<?endif?>
		<?=bitrix_sessid_post();?>
		</form>
	<?endif;?>
	<?if($STAT_RIGHT>="W" && $bCheckForDDL):?>
		<form name="optimizeform" method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&amp;lang=<?=LANGUAGE_ID?>">
		<?$tabControl2->BeginNextTab();?>
		<tr>
			<td align="left" colspan="2">
				<?
				$arDDL = CStatistics::GetDDL();
				foreach($arDDL as $DDL):
					echo htmlspecialcharsbx($DDL["SQL_TEXT"])."<br>";
				endforeach;?>
				<br>
				<input type="submit" name="runsql" value="<?echo GetMessage("STAT_OPT_INDEX_CREATE_BUTTON")?>">
				<input type="hidden" name="runsql" value="Y">
				<input type="hidden" name="tabControl2_active_tab" value="fedit4">
			<br>
			<?echo BeginNote('width="100%"');?>
			<span class="required"><?echo GetMessage("STAT_OPT_INDEX_ATTENTION")?></span> - <?echo GetMessage("STAT_OPT_INDEX_ATTENTION_DETAIL")?>
			<?echo EndNote();?>
			</td>
		</tr>
		<?$tabControl2->EndTab();?>
		<?if(strlen($_REQUEST["back_url_settings"])>0):?>
			<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
		<?endif?>
		<?=bitrix_sessid_post();?>
		</form>
	<?endif;?>
	<?$tabControl2->End();?>
<?endif;?>
