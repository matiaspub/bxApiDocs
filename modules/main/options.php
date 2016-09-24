<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 */

IncludeModuleLangFile(__FILE__);

if(!$USER->CanDoOperation('view_other_settings') && !$USER->CanDoOperation('edit_other_settings'))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$mid = $_REQUEST["mid"];

$arGROUPS = array();
$groups = array();
$z = CGroup::GetList(($v1=""), ($v2=""), array("ACTIVE"=>"Y", "ADMIN"=>"N", "ANONYMOUS"=>"N"));
while($zr = $z->Fetch())
{
	$ar = array();
	$ar["ID"] = intval($zr["ID"]);
	$ar["NAME"] = htmlspecialcharsbx($zr["NAME"]);
	$arGROUPS[] = $ar;

	$groups[$zr["ID"]] = $zr["NAME"]." [".$zr["ID"]."]";
}

if($_SERVER["REQUEST_METHOD"] == "GET" && $USER->IsAdmin() && $_REQUEST["RestoreDefaults"] <> '' && check_bitrix_sessid())
{
	$aSaveVal = array(
		array("NAME"=>"admin_passwordh", "DEF"=>""),
		array("NAME"=>"PARAM_MAX_SITES", "DEF"=>"2"),
		array("NAME"=>"PARAM_MAX_USERS", "DEF"=>"0"),
		array("NAME"=>"crc_code", "DEF"=>""),
		array("NAME"=>"vendor", "DEF"=>"1c_bitrix"),
		array("NAME"=>"distributive6", "DEF"=>"N"),
	);
	foreach($aSaveVal as $i=>$aParam)
		$aSaveVal[$i]["VALUE"] = COption::GetOptionString("main", $aParam["NAME"], $aParam["DEF"]);

	COption::RemoveOption("main");

	foreach($aSaveVal as $aParam)
		COption::SetOptionString("main", $aParam["NAME"], $aParam["VALUE"]);

	foreach($arGROUPS as $value)
		$APPLICATION->DelGroupRight("main", array($value["ID"]));
}

if($_SERVER["REQUEST_METHOD"] == "GET" && $USER->CanDoOperation('edit_other_settings') && $_REQUEST["GenKey"] <> '' && check_bitrix_sessid())
{
	$sec = new CRsaSecurity();
	$arKeys = $sec->Keygen();
	if($arKeys !== false)
	{
		$sec->SaveKeys($arKeys);
		CAdminMessage::ShowNote(GetMessage("MAIN_OPT_SECURE_KEY_SUCCESS"));
	}
	else
	{
		CAdminMessage::ShowMessage(GetMessage("MAIN_OPT_SECURE_KEY_ERROR"));
	}
}

$bEmailIndex = (COption::GetOptionString("main", "new_user_email_uniq_check", "N") !== "Y") && !$DB->IndexExists("b_user", array("EMAIL"));

$arSmileGallery = CSmileGallery::getListForForm();
foreach ($arSmileGallery as $key => $value)
	$arSmileGallery[$key] = htmlspecialcharsback($value);

$arAllOptions = array(
	"main" => Array(
		Array("site_name", GetMessage("MAIN_OPTION_SITENAME"), $SERVER_NAME, Array("text", 30)),
		Array("server_name", GetMessage("MAIN_OPTION_SERVERNAME"), $SERVER_NAME, Array("text", 30)),
		Array("cookie_name", GetMessage("MAIN_PREFIX"), "BITRIX_SM", Array("text", 30)),
		Array("ALLOW_SPREAD_COOKIE", GetMessage("MAIN_OPTION_ALLOW_SPREAD_COOKIE"), "Y", Array("checkbox", "Y")),
		Array("header_200", GetMessage("HEADER_200"), "N", Array("checkbox", "Y")),
		Array("error_reporting", GetMessage("MAIN_ERROR_REPORTING"), E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE, Array("selectbox", Array(E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE=>GetMessage("MAIN_OPTION_ERROR1"), E_ALL^E_NOTICE=>GetMessage("MAIN_OPTION_ERROR2"), 0=>GetMessage("MAIN_OPTION_ERROR3")))),
		Array("use_hot_keys", GetMessage("main_options_use_hot_keys"), "Y", Array("checkbox", "Y")),
		Array("smile_gallery_id", GetMessage("MAIN_OPTIONS_SMILE_GALLERY_ID"), 0, Array("selectbox", $arSmileGallery)),

		GetMessage("main_options_mail"),
		Array("all_bcc", GetMessage("MAIN_EMAIL"), "", Array("text", 30)),
		Array("send_mid", GetMessage("MAIN_SEND_MID"), "N", Array("checkbox", "Y")),
		Array("fill_to_mail", GetMessage("FILL_TO_MAIL_M"), "N", Array("checkbox", "Y")),
		Array("email_from", GetMessage("MAIN_EMAIL_FROM"), "admin@".$SERVER_NAME, Array("text", 30)),
		Array("CONVERT_UNIX_NEWLINE_2_WINDOWS", GetMessage("MAIN_CONVERT_UNIX_NEWLINE_2_WINDOWS"), "N", Array("checkbox", "Y")),
		Array("convert_mail_header", GetMessage("MAIN_OPTION_CONVERT_8BIT"), "Y", Array("checkbox", "Y")),
		Array("attach_images", GetMessage("MAIN_OPTION_ATTACH_IMAGES"), "N", array("checkbox", "Y")),
		Array("max_file_size", GetMessage("MAIN_OPTION_MAX_FILE_SIZE"), "0", Array("text", 10)),
		Array("mail_event_period", GetMessage("main_option_mail_period"), "14", Array("text", 10)),
		Array("mail_event_bulk", GetMessage("main_option_mail_bulk"), "5", Array("text", 10)),
		Array("mail_additional_parameters", GetMessage("MAIN_OPTION_MAIL_ADDITIONAL_PARAMETERS"), "", Array("text", 30)),

		GetMessage("main_options_files"),
		Array("disk_space", GetMessage("MAIN_DISK_SPACE"), "", Array("text", 30)),
		Array("upload_dir", GetMessage("MAIN_UPLOAD_PARAM"), "upload", Array("text", 30)),
		Array("save_original_file_name", GetMessage("MAIN_OPTION_SAVE_ORIG_NAMES"), "N", Array("checkbox", "Y")),
		Array("translit_original_file_name", GetMessage("MAIN_OPTION_TRANSLIT"), "N", Array("checkbox", "Y")),
		Array("convert_original_file_name", GetMessage("MAIN_OPTION_FNAME_CONV_AUTO"), "Y", Array("checkbox", "Y")),
		Array("image_resize_quality", GetMessage("MAIN_OPTIONS_IMG_QUALITY"), "95", Array("text", "10")),
		Array("bx_fast_download", GetMessage("MAIN_OPT_BX_FAST_DOWNLOAD"), "N", Array("checkbox", "N")),
		Array("note" => GetMessage("MAIN_OPT_BX_FAST_DOWNLOAD_HINT")),

		GetMessage("MAIN_OPTIMIZE_CSS_SETTINGS"),
		Array("optimize_css_files", GetMessage("MAIN_OPTIMIZE_CSS"), "N", Array("checkbox", "Y")),
		Array("optimize_js_files", GetMessage("MAIN_OPTIMIZE_JS"), "N", Array("checkbox", "Y")),
		Array("use_minified_assets", GetMessage("MAIN_USE_MINIFIED_ASSETS"), "Y", Array("checkbox", "Y")),
		Array("move_js_to_body", GetMessage("MAIN_MOVE_JS_TO_BODY"), "N", Array("checkbox", "Y")),
		Array("compres_css_js_files", GetMessage("MAIN_COMPRES_CSS_JS"), "N", Array("checkbox", "Y")),

		GetMessage("MAIN_OPTIMIZE_TRANSLATE_SETTINGS"),
		Array("translate_key_yandex", GetMessage("MAIN_TRANSLATE_KEY_YANDEX"), "", Array("text", 30)),
		Array("note" => GetMessage("MAIN_TRANSLATE_KEY_YANDEX_HINT")),

		GetMessage("MAIN_OPT_TIME_ZONES"),
		array("curr_time", GetMessage("MAIN_OPT_TIME_ZONES_LOCAL"), GetMessage("MAIN_OPT_TIME_ZONES_DIFF")." ".date('O')." (".date('Z').")<br>".GetMessage("MAIN_OPT_TIME_ZONES_DIFF_STD")." ".(date('I')? GetMessage("MAIN_OPT_TIME_ZONES_DIFF_STD_S") : GetMessage("MAIN_OPT_TIME_ZONES_DIFF_STD_ST"))."<br>".GetMessage("MAIN_OPT_TIME_ZONES_DIFF_DATE")." ".date('r'), array("statichtml")),
	),
	"update" => Array(
		Array("update_site", GetMessage("MAIN_UPDATE_SERVER"), "www.bitrixsoft.com", Array("text", 30)),
		Array("update_site_proxy_addr", GetMessage("MAIN_UPDATE_SERVER_PR_AD"), "", Array("text", 30)),
		Array("update_site_proxy_port", GetMessage("MAIN_UPDATE_SERVER_PR_PR"), "", Array("text", 30)),
		Array("update_site_proxy_user", GetMessage("MAIN_UPDATE_SERVER_PR_US"), "", Array("text", 30, "noautocomplete"=>true)),
		Array("update_site_proxy_pass", GetMessage("MAIN_UPDATE_SERVER_PR_PS"), "", Array("password", 30)),
		Array("strong_update_check", GetMessage("MAIN_STRONGUPDATECHECK"), "Y", Array("checkbox", "Y")),
		Array("stable_versions_only", GetMessage("MAIN_STABLEVERSIONS"), "Y", Array("checkbox", "Y")),
		Array("update_autocheck", GetMessage("MAIN_OPTIONS_AUTOCHECK"), "", Array("selectbox", Array(""=>GetMessage("MAIN_OPTIONS_AUTOCHECK_NO"), "1"=>GetMessage("MAIN_OPTIONS_AUTOCHECK_1"), "7"=>GetMessage("MAIN_OPTIONS_AUTOCHECK_7"), "30"=>GetMessage("MAIN_OPTIONS_AUTOCHECK_30")))),
		Array("update_stop_autocheck", GetMessage("MAIN_OPTIONS_STOP_AUTOCHECK"), "N", Array("checkbox", "Y")),
		Array("update_is_gzip_installed", GetMessage("MAIN_UPDATE_IS_GZIP_INSTALLED"), "Y", Array("checkbox", "Y")),
		Array("update_load_timeout", GetMessage("MAIN_UPDATE_LOAD_TIMEOUT"), "30", Array("text", "30")),
	),
	"auth" => Array(
		Array("store_password", GetMessage("MAIN_REMEMBER"), "Y", Array("checkbox", "Y")),
		Array("use_secure_password_cookies", GetMessage("MAIN_OPTION_USE_SECURE_PASSWORD_COOKIE"), "N", Array("checkbox", "Y")),
		Array("auth_multisite", GetMessage("MAIN_OPTION_AUTH_TO_ALL_DOM"), "N", Array("checkbox", "Y")),
		Array("allow_socserv_authorization", GetMessage("MAIN_OPTION_SOCSERV_AUTH"), "Y", Array("checkbox", "Y")),
		Array("use_digest_auth", GetMessage("MAIN_OPT_HTTP_DIGEST"), "N", Array("checkbox", "Y")),
		Array("note"=>GetMessage("MAIN_OPT_DIGEST_NOTE")),
		Array("custom_register_page", GetMessage("MAIN_OPT_REGISTER_PAGE"), "", Array("text", 40)),
		Array("auth_components_template", GetMessage("MAIN_OPTIONS_AUTH_TEMPLATE") , "", Array("text", 40)),

		GetMessage("MAIN_OPT_SECURE_AUTH"),
		Array("use_encrypted_auth", GetMessage("MAIN_OPT_SECURE_PASS"), "N", Array("checkbox", "Y"), (CRsaSecurity::Possible()? "N":"Y")),
	),
	"event_log" => Array(
		Array("event_log_cleanup_days", GetMessage("MAIN_EVENT_LOG_CLEANUP_DAYS"), "7", Array("text", 5)),

		GetMessage("MAIN_AUDIT_OPTIONS"),
		Array("event_log_logout", GetMessage("MAIN_EVENT_LOG_LOGOUT"), "N", Array("checkbox", "Y")),
		Array("event_log_login_success", GetMessage("MAIN_EVENT_LOG_LOGIN_SUCCESS"), "N", Array("checkbox", "Y")),
		Array("event_log_login_fail", GetMessage("MAIN_EVENT_LOG_LOGIN_FAIL"), "N", Array("checkbox", "Y")),
		Array("event_log_register", GetMessage("MAIN_EVENT_LOG_REGISTER"), "N", Array("checkbox", "Y")),
		Array("event_log_register_fail", GetMessage("MAIN_EVENT_LOG_REGISTER_FAIL"), "N", Array("checkbox", "Y")),
		Array("event_log_password_request", GetMessage("MAIN_EVENT_LOG_PASSWORD_REQUEST"), "N", Array("checkbox", "Y")),
		Array("event_log_password_change", GetMessage("MAIN_EVENT_LOG_PASSWORD_CHANGE"), "N", Array("checkbox", "Y")),
		Array("event_log_user_edit", GetMessage("MAIN_EVENT_LOG_USER_EDIT"), "N", Array("checkbox", "Y")),
		Array("event_log_user_delete", GetMessage("MAIN_EVENT_LOG_USER_DELETE"), "N", Array("checkbox", "Y")),
		Array("event_log_user_groups", GetMessage("MAIN_EVENT_LOG_USER_GROUPS"), "N", Array("checkbox", "Y")),
		Array("event_log_group_policy", GetMessage("MAIN_EVENT_LOG_GROUP_POLICY"), "N", Array("checkbox", "Y")),
		Array("event_log_module_access", GetMessage("MAIN_EVENT_LOG_MODULE_ACCESS"), "N", Array("checkbox", "Y")),
		Array("event_log_file_access", GetMessage("MAIN_EVENT_LOG_FILE_ACCESS"), "N", Array("checkbox", "Y")),
		Array("event_log_task", GetMessage("MAIN_EVENT_LOG_TASK"), "N", Array("checkbox", "Y")),
		Array("event_log_marketplace", GetMessage("MAIN_EVENT_LOG_MARKETPLACE"), "Y", Array("checkbox", "Y")),
	),
	"controller_auth" => Array(
		Array("auth_controller_prefix", GetMessage("MAIN_OPTION_CTRL_PREF"), "controller", Array("text", "30")),
		Array("auth_controller_sso", GetMessage("MAIN_OPTION_CTRL_THR"), "N", Array("checkbox", "Y")),
	),
);

$aZones = CTimeZone::GetZones();
$arAllOptions["main"][] = array("use_time_zones", GetMessage("MAIN_OPT_USE_TIMEZONES"), "N", array("checkbox", "Y", 'onclick="this.form.default_time_zone.disabled = this.form.auto_time_zone.disabled = !this.checked;"'));
$arAllOptions["main"][] = array("default_time_zone", GetMessage("MAIN_OPT_TIME_ZONE_DEF"), "", array("selectbox", $aZones));
$arAllOptions["main"][] = array("auto_time_zone", GetMessage("MAIN_OPT_TIME_ZONE_AUTO"), "N", array("checkbox", "Y"));

if (\Bitrix\Main\Analytics\SiteSpeed::isLicenseAccepted())
{
	$arAllOptions["main"][] = GetMessage("MAIN_CATALOG_STAT_SETTINGS");
	$arAllOptions["main"][] = array("gather_catalog_stat", GetMessage("MAIN_GATHER_CATALOG_STAT"), "Y", Array("checkbox", "Y"));
}

$arAllOptions["main"][] = GetMessage("main_options_map");
$arAllOptions["main"][] = Array("map_top_menu_type", GetMessage("MAIN_TOP_MENU_TYPE"), "top", Array("text", 30));
$arAllOptions["main"][] = Array("map_left_menu_type", GetMessage("MAIN_LEFT_MENU_TYPE"), "left", Array("text", 30));

$arAllOptions["main"][] = GetMessage("MAIN_OPTIONS_URL_PREVIEW");
$arAllOptions["main"][] = Array("url_preview_enable", GetMessage("MAIN_OPTION_URL_PREVIEW_ENABLE"), "N", array("checkbox", "Y"));
$arAllOptions["main"][] = Array("url_preview_save_images", GetMessage("MAIN_OPTION_URL_PREVIEW_SAVE_IMAGES"), "N", array("checkbox", "Y"));

CJSCore::Init(array('access'));

//show the public panel for users
$arCodes = unserialize(COption::GetOptionString("main", "show_panel_for_users"));
if(!is_array($arCodes))
	$arCodes = array();

//hide the public panel for users
$arHideCodes = unserialize(COption::GetOptionString("main", "hide_panel_for_users"));
if(!is_array($arHideCodes))
	$arHideCodes = array();

$access = new CAccess();
$arNames = $access->GetNames(array_merge($arCodes, $arHideCodes));

$panel = "
<script type=\"text/javascript\">

function InsertAccess(arRights, divId, hiddenName)
{
	var div = BX(divId);
	for(var provider in arRights)
	{
		for(var id in arRights[provider])
		{
			var pr = BX.Access.GetProviderPrefix(provider, id);
			var newDiv = document.createElement('DIV');
			newDiv.style.marginBottom = '4px';
			newDiv.innerHTML = '<input type=\"hidden\" name=\"'+hiddenName+'\" value=\"'+id+'\">' + (pr? pr+': ':'') + arRights[provider][id].name + '&nbsp;<a href=\"javascript:void(0);\" onclick=\"DeleteAccess(this, \\''+id+'\\')\" class=\"access-delete\"></a>';
			div.appendChild(newDiv);
		}
	}
}

function DeleteAccess(ob, id)
{
	var div = BX.findParent(ob, {'tag':'div'});
	div.parentNode.removeChild(div);
}

function ShowPanelFor()
{
	BX.Access.Init({
		other: {disabled:true}
	});
	BX.Access.SetSelected({});
	BX.Access.ShowForm({
		callback: function(obSelected)
		{
			InsertAccess(obSelected, 'bx_access_div', 'show_panel_for_users[]');
		}
	});
}

function HidePanelFor()
{
	BX.Access.Init();
	BX.Access.SetSelected({});
	BX.Access.ShowForm({
		callback: function(obSelected)
		{
			InsertAccess(obSelected, 'bx_access_hide_div', 'hide_panel_for_users[]');
		}
	});
}
</script>

<div id=\"bx_access_div\">
";

foreach($arCodes as $code)
	$panel .= '<div style="margin-bottom:4px"><input type="hidden" name="show_panel_for_users[]" value="'.$code.'">'.($arNames[$code]["provider"] <> ''? $arNames[$code]["provider"].': ':'').$arNames[$code]["name"].'&nbsp;<a href="javascript:void(0);" onclick="DeleteAccess(this, \''.$code.'\')" class="access-delete"></a></div>';

$panel .= '</div><a href="javascript:void(0)" class="bx-action-href" onclick="ShowPanelFor()">'.GetMessage("main_sett_add_users").'</a>';

$panelHide = "
<div id=\"bx_access_hide_div\">
";

foreach($arHideCodes as $code)
	$panelHide .= '<div style="margin-bottom:4px"><input type="hidden" name="hide_panel_for_users[]" value="'.$code.'">'.($arNames[$code]["provider"] <> ''? $arNames[$code]["provider"].': ':'').$arNames[$code]["name"].'&nbsp;<a href="javascript:void(0);" onclick="DeleteAccess(this, \''.$code.'\')" class="access-delete"></a></div>';

$panelHide .= '</div><a href="javascript:void(0)" class="bx-action-href" onclick="HidePanelFor()">'.GetMessage("main_sett_add_users").'</a>';

$arAllOptions["main"][] = GetMessage("main_sett_public_panel");
$arAllOptions["main"][] = Array("", GetMessage("main_sett_public_panel_show"), $panel, Array("statichtml"));
$arAllOptions["main"][] = Array("", GetMessage("main_sett_public_panel_hide"), $panelHide, Array("statichtml"));

if(CRsaSecurity::Possible())
{
	$sec = new CRsaSecurity();
	$arKeys = $sec->LoadKeys();

	$mess = ($arKeys === false? GetMessage("MAIN_OPT_SECURE_KEY_NOT_FOUND") : GetMessage("MAIN_OPT_SECURE_KEY", array("#KEYLEN#"=>$arKeys["chunk"]*8)));
	$mess .= '<br><br><input type="button" name="" value="'.GetMessage("MAIN_OPT_SECURE_GENKEY").'" onclick="window.location=\'/bitrix/admin/settings.php?GenKey=Y&lang='.LANGUAGE_ID.'&mid='.urlencode($mid).'&'.bitrix_sessid_get().'&tabControl_active_tab=edit6\'">';

	$arAllOptions["auth"][] = Array("", GetMessage("MAIN_OPT_SECURE_KEY_LABEL"), $mess, Array("statichtml"));

	if($sec->GetLib() == 'bcmath')
		$arAllOptions["auth"][] = array("note"=>GetMessage("MAIN_OPT_SECURE_NOTE"));
}
else
{
	$arAllOptions["auth"][] = array("note"=>GetMessage("MAIN_OPT_EXT_NOTE"));
}

$arAllOptions["auth"][] = GetMessage("MAIN_REGISTRATION_OPTIONS");
$arAllOptions["auth"][] = Array("new_user_registration", GetMessage("MAIN_REGISTER"), "Y", Array("checkbox", "Y"));
$arAllOptions["auth"][] = Array("captcha_registration", GetMessage("MAIN_OPTION_FNAME_CAPTCHA"), "N", Array("checkbox", "Y"));
$arAllOptions["auth"][] = Array("new_user_registration_def_group", GetMessage("MAIN_REGISTER_GROUP"), "", Array("multiselectbox", $groups));
$arAllOptions["auth"][] = Array("new_user_email_required", GetMessage("MAIN_OPTION_EMAIL_REQUIRED"), "Y", Array("checkbox", "Y", 'onclick="BxReqEmail(this)"'));
$arAllOptions["auth"][] = Array("new_user_registration_email_confirmation", GetMessage("MAIN_REGISTER_EMAIL_CONFIRMATION", array("#EMAIL_TEMPLATES_URL#" => "/bitrix/admin/message_admin.php?lang=".LANGUAGE_ID."&set_filter=Y&find_type_id=NEW_USER_CONFIRM")), "N", Array("checkbox", "Y"));
$arAllOptions["auth"][] = Array("new_user_registration_cleanup_days", GetMessage("MAIN_REGISTER_CLEANUP_DAYS"), "7", Array("text", 5));
$arAllOptions["auth"][] = Array("new_user_email_uniq_check", GetMessage("MAIN_REGISTER_EMAIL_UNIQ_CHECK").($bEmailIndex? "<br>".GetMessage("MAIN_REGISTER_EMAIL_INDEX_WARNING"): ""), "N", Array("checkbox", "Y"));

$arAllOptions["auth"][] = GetMessage("MAIN_OPTION_SESS");
$arAllOptions["auth"][] = Array("session_expand", GetMessage("MAIN_OPTION_SESS_EXPAND"), "Y", Array("checkbox", "Y"));
$arAllOptions["auth"][] = Array("session_auth_only", GetMessage("MAIN_OPTION_SESS_AUTH"), "Y", Array("checkbox", "Y"));
$arAllOptions["auth"][] = Array("session_show_message", GetMessage("MAIN_OPTION_SESS_MESS"), "Y", Array("checkbox", "Y"));

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "main_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
	array("DIV" => "edit6", "TAB" => GetMessage("MAIN_TAB_6"), "ICON" => "main_settings", "TITLE" => GetMessage("MAIN_OPTION_REG")),
	array("DIV" => "edit8", "TAB" => GetMessage("MAIN_TAB_8"), "ICON" => "main_settings", "TITLE" => GetMessage("MAIN_OPTION_EVENT_LOG")),
	array("DIV" => "edit5", "TAB" => GetMessage("MAIN_TAB_5"), "ICON" => "main_settings", "TITLE" => GetMessage("MAIN_OPTION_UPD")),
	array("DIV" => "edit2", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "main_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS")),
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);

$SET_LICENSE_KEY = "";
if($_SERVER["REQUEST_METHOD"]=="POST" && strlen($_POST["Update"])>0 && ($USER->CanDoOperation('edit_other_settings') && $USER->CanDoOperation('edit_groups')) && check_bitrix_sessid())
{
	if(LICENSE_KEY !== $_POST["SET_LICENSE_KEY"])
	{
		$SET_LICENSE_KEY = preg_replace("/[^A-Za-z0-9_.-]/", "", $_POST["SET_LICENSE_KEY"]);

		file_put_contents(
			$_SERVER["DOCUMENT_ROOT"].BX_ROOT."/license_key.php",
			"<"."? $"."LICENSE_KEY = \"".EscapePHPString($SET_LICENSE_KEY)."\"; ?".">"
		);
	}

	foreach($arAllOptions as $aOptGroup)
	{
		foreach($aOptGroup as $option)
		{
			__AdmSettingsSaveOption("main", $option);
		}
	}
	COption::SetOptionString("main", "admin_lid", $_POST["admin_lid"]);
	COption::SetOptionString("main", "show_panel_for_users", serialize($_POST["show_panel_for_users"]));
	COption::SetOptionString("main", "hide_panel_for_users", serialize($_POST["hide_panel_for_users"]));

	$cleanup_days = COption::GetOptionInt("main", "new_user_registration_cleanup_days", 7);
	if($cleanup_days > 0 && COption::GetOptionString("main", "new_user_registration_email_confirmation", "N") === "Y")
	{
		CAgent::AddAgent("CUser::CleanUpAgent();", "main", "N", 24*60*60);
	}
	else
	{
		CAgent::RemoveAgent("CUser::CleanUpAgent();", "main");
	}

	$cleanup_days = COption::GetOptionInt("main", "event_log_cleanup_days", 7);
	if($cleanup_days > 0)
	{
		CAgent::AddAgent("CEventLog::CleanUpAgent();", "main", "N", 24*60*60);
	}
	else
	{
		CAgent::RemoveAgent("CEventLog::CleanUpAgent();", "main");
	}

	if((COption::GetOptionString("main", "new_user_email_uniq_check", "N") === "Y") && !$DB->IndexExists("b_user", array("EMAIL")))
	{
		if(strtolower($DB->type) === "oracle")
			$DB->Query("CREATE INDEX ix_b_user_email on b_user(UPPER(EMAIL))", true);
		else
			$DB->Query("CREATE INDEX ix_b_user_email on b_user(EMAIL)", true);
	}
	$bEmailIndex = (COption::GetOptionString("main", "new_user_email_uniq_check", "N") !== "Y") && !$DB->IndexExists("b_user", array("EMAIL"));
	foreach($arAllOptions["auth"] as $i => $arOption)
		if($arOption[0] === "new_user_email_uniq_check")
			$arAllOptions["auth"][$i][1] = GetMessage("MAIN_REGISTER_EMAIL_UNIQ_CHECK").($bEmailIndex? "<br>".GetMessage("MAIN_REGISTER_EMAIL_INDEX_WARNING"): "");

	$module_id = "main";
	COption::SetOptionString($module_id, "GROUP_DEFAULT_TASK", $GROUP_DEFAULT_TASK, "Task for groups by default");
	$letter = ($l = CTask::GetLetter($GROUP_DEFAULT_TASK)) ? $l : 'D';
	COption::SetOptionString($module_id, "GROUP_DEFAULT_RIGHT", $letter, "Right for groups by default");

	$nID = COperation::GetIDByName('edit_subordinate_users');
	$arTasksInModule = Array();
	foreach($arGROUPS as $value)
	{
		$tid = ${"TASKS_".$value["ID"]};
		$arTasksInModule[$value["ID"]] = Array('ID' => $tid);

		$subOrdGr = false;
		if (strlen($tid) > 0 && in_array($nID,CTask::GetOperations($tid)) && isset($_POST['subordinate_groups_'.$value["ID"]]))
			$subOrdGr = $_POST['subordinate_groups_'.$value["ID"]];

		CGroup::SetSubordinateGroups($value["ID"], $subOrdGr);

		$rt = ($tid) ? CTask::GetLetter($tid) : '';
		if (strlen($rt) > 0 && $rt != "NOT_REF")
			$APPLICATION->SetGroupRight($module_id, $value["ID"], $rt);
		else
			$APPLICATION->DelGroupRight($module_id, array($value["ID"]));
	}

	CGroup::SetTasksForModule($module_id, $arTasksInModule);

	if($_REQUEST["back_url_settings"] <> "" && $_REQUEST["Apply"] == "")
		LocalRedirect($_REQUEST["back_url_settings"]);
	else
		LocalRedirect("/bitrix/admin/settings.php?lang=".LANGUAGE_ID."&mid=".urlencode($mid)."&tabControl_active_tab=".urlencode($_REQUEST["tabControl_active_tab"])."&back_url_settings=".urlencode($_REQUEST["back_url_settings"]));
}

if($SET_LICENSE_KEY == "")
	$SET_LICENSE_KEY = LICENSE_KEY;

if ($_SERVER["REQUEST_METHOD"]=="POST" && $_POST["stop_site"]=="Y" && $USER->CanDoOperation('edit_other_settings') && check_bitrix_sessid())
{
	COption::SetOptionString("main", "site_stopped", "Y");
	CAdminMessage::ShowNote(GetMessage("MAIN_OPTION_PUBL_CLOSES"));
}

if ($_SERVER["REQUEST_METHOD"]=="POST" && $_POST["start_site"]=="Y" && $USER->CanDoOperation('edit_other_settings') && check_bitrix_sessid())
{
	COption::SetOptionString("main", "site_stopped", "N");
	CAdminMessage::ShowNote(GetMessage("MAIN_OPTION_PUBL_OPENED"));
}

function ShowParamsHTMLByArray($arParams)
{
	foreach($arParams as $Option)
	{
		__AdmSettingsDrawRow("main", $Option);
	}
}
?>
<form name="main_options" method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&amp;lang=<?echo LANG?>">
<?=bitrix_sessid_post()?>
<?
$tabControl->Begin();
$tabControl->BeginNextTab();
?>
	<tr class="heading">
		<td colspan="2"><b><?echo GetMessage("main_options_sys")?></b></td>
	</tr>
	<tr>
		<td><?echo GetMessage("MAIN_ADMIN_DEFAULT_LANG")?></td>
		<td><?=CLangAdmin::SelectBox("admin_lid", COption::GetOptionString("main", "admin_lid", "en"));?></td>
	</tr>
	<?
	ShowParamsHTMLByArray($arAllOptions["main"]);
	?>
<?
$tabControl->BeginNextTab();
?>
	<tr class="heading">
		<td colspan="2"><b><?echo GetMessage("MAIN_OPTION_CTRL_LOC")?></b></td>
	</tr>

<?
ShowParamsHTMLByArray($arAllOptions["auth"]);

$tabControl->BeginNextTab();
ShowParamsHTMLByArray($arAllOptions["event_log"]);
?>

<?if(COption::GetOptionString("main", "controller_member", "N")=="Y"):?>
	<tr class="heading">
		<td colspan="2"><b><?echo GetMessage("MAIN_OPTION_CTRL_REM")?></b></td>
	</tr>
<?
ShowParamsHTMLByArray($arAllOptions["controller_auth"]);
?>

<?endif?>



<?
$tabControl->BeginNextTab();
?>
	<tr>
		<td width="50%"><?echo GetMessage("MAIN_OPTION_LICENSE_KEY")?></td>
		<td width="50%"><input type="text" size="30" maxlength="40" value="<?echo ($USER->CanDoOperation('edit_other_settings') ? htmlspecialcharsbx($SET_LICENSE_KEY) : "XXX-XX-XXXXXXXXXXXXX")?>" name="SET_LICENSE_KEY">
		</td>
	</tr>

	<?
	ShowParamsHTMLByArray($arAllOptions["update"]);
	?>
<?
$tabControl->BeginNextTab();

$module_id="main";
$GROUP_DEFAULT_TASK = COption::GetOptionString($module_id, "GROUP_DEFAULT_TASK", "");

if ($GROUP_DEFAULT_TASK == '')
{
	$GROUP_DEFAULT_RIGHT = COption::GetOptionString($module_id, "GROUP_DEFAULT_RIGHT", "D");
	$GROUP_DEFAULT_TASK = CTask::GetIdByLetter($GROUP_DEFAULT_RIGHT,$module_id,'module');
	if ($GROUP_DEFAULT_TASK)
		COption::SetOptionString($module_id, "GROUP_DEFAULT_TASK", $GROUP_DEFAULT_TASK);
}
?>
	<tr>
		<td width="50%"><b><?=GetMessage("MAIN_BY_DEFAULT");?></b></td>
		<td width="50%">
		<script>var arSubordTasks = [];</script>
		<?
		$arTasksInModule = CTask::GetTasksInModules(true,$module_id,'module');
		$nID = COperation::GetIDByName('edit_subordinate_users');
		$arTasks = $arTasksInModule['main'];
		echo SelectBoxFromArray("GROUP_DEFAULT_TASK", $arTasks, htmlspecialcharsbx($GROUP_DEFAULT_TASK));

		$show_subord = false;
		$arTaskIds = $arTasks['reference_id'];
		$arSubordTasks = Array();
		$l = count($arTaskIds);
		for ($i=0;$i<$l;$i++)
		{
			$arOpInTask = CTask::GetOperations($arTaskIds[$i]);
			if (in_array($nID,$arOpInTask))
			{
				$arSubordTasks[] = $arTaskIds[$i];
				?><script>
				arSubordTasks.push(<?=$arTaskIds[$i]?>);
				</script><?
			}
		}

		?>
		<script>
		var taskSelectOnchange = function(select)
		{
			var show = false;
			for (var s = 0; s < arSubordTasks.length; s++)
			{
				if (arSubordTasks[s].toString() == select.value)
				{
					show = true;
					break;
				}
			}
			var div = jsUtils.FindNextSibling(select, "div");
			if (show)
				div.style.display = 'block';
			else
				div.style.display = 'none';
		};
		</script>
		</td>
	</tr>
<?
$arUsedGroups = array();
$arTaskInModule = CGroup::GetTasksForModule('main');
foreach($arGROUPS as $value):
	$v = (isset($arTaskInModule[$value["ID"]]['ID'])? $arTaskInModule[$value["ID"]]['ID'] : false);
	if($v == false)
		continue;
	$arUsedGroups[$value["ID"]] = true;
?>
	<tr valign="top">
		<td><?=$value["NAME"]." [<a title=\"".GetMessage("MAIN_USER_GROUP_TITLE")."\" href=\"/bitrix/admin/group_edit.php?ID=".$value["ID"]."&amp;lang=".LANGUAGE_ID."\">".$value["ID"]."</a>]:"?></td>
		<td>
		<?
		echo SelectBoxFromArray("TASKS_".$value["ID"], $arTasks, $v, GetMessage("MAIN_DEFAULT"), 'onchange="taskSelectOnchange(this)"');
		$show_subord = (in_array($v,$arSubordTasks));
		?>
		<div<?echo $show_subord? '' : ' style="display:none"';?>>
			<div style="padding:6px 0 6px 0"><?=GetMessage('SUBORDINATE_GROUPS');?>:</div>
			<select name="subordinate_groups_<?=$value["ID"]?>[]" multiple size="6">
			<?
			$arSubordinateGroups = CGroup::GetSubordinateGroups($value["ID"]);
			foreach($arGROUPS as $v_gr)
			{
				if ($v_gr['ID'] == $value["ID"])
					continue;
				?><option value="<?=$v_gr['ID']?>" <?echo (in_array($v_gr['ID'],$arSubordinateGroups)) ? 'selected' : ''?>><? echo $v_gr['NAME'].' ['.$v_gr['ID'].']'?></option><?
			}
			?>
			</select>
		</div>
		</td>
	</tr>
<?endforeach;?>

<?
if(count($arGROUPS) > count($arUsedGroups)):
?>
<tr valign="top">
	<td><select onchange="settingsSetGroupID(this)">
		<option value=""><?echo GetMessage("group_rights_select")?></option>
<?
foreach($arGROUPS as $group):
	if($arUsedGroups[$group["ID"]] == true)
		continue;
?>
		<option value="<?=$group["ID"]?>"><?=$group["NAME"]." [".$group["ID"]."]"?></option>
<?endforeach?>
	</select></td>
		<td>
		<?
		echo SelectBoxFromArray("", $arTasks, "", GetMessage("MAIN_DEFAULT"), 'onchange="taskSelectOnchange(this)"');
		?>
		<div style="display:none">
			<div style="padding:6px 0 6px 0"><?=GetMessage('SUBORDINATE_GROUPS');?>:</div>
			<select name="" multiple size="6">
			<?
			foreach($arGROUPS as $v_gr)
			{
				?><option value="<?=$v_gr['ID']?>"><? echo $v_gr['NAME'].' ['.$v_gr['ID'].']'?></option><?
			}
			?>
			</select>
		</div>
		</td>
</tr>
<tr>
	<td colspan="2">
<script type="text/javascript">
function settingsSetGroupID(el)
{
	var tr = jsUtils.FindParentObject(el, "tr");
	var sel = jsUtils.FindChildObject(tr.cells[1], "select");
	sel.name = "TASKS_"+el.value;

	var div = jsUtils.FindNextSibling(sel, "div");
	sel = jsUtils.FindChildObject(div, "select");
	sel.name = "subordinate_groups_"+el.value+"[]";
}

function settingsAddRights(a)
{
	var row = jsUtils.FindParentObject(a, "tr");
	var tbl = row.parentNode;

	var tableRow = tbl.rows[row.rowIndex-1].cloneNode(true);
	tbl.insertBefore(tableRow, row);

	var sel = jsUtils.FindChildObject(tableRow.cells[1], "select");
	sel.name = "";
	sel.selectedIndex = 0;

	var div = jsUtils.FindNextSibling(sel, "div");
	div.style.display = "none";
	sel = jsUtils.FindChildObject(div, "select");
	sel.name = "";
	sel.selectedIndex = -1;

	sel = jsUtils.FindChildObject(tableRow.cells[0], "select");
	sel.selectedIndex = 0;
}
</script>
<a href="javascript:void(0)" onclick="settingsAddRights(this)" hidefocus="true" class="bx-action-href"><?echo GetMessage("group_rights_add")?></a>
	</td>
</tr>
<?endif?>

<?$tabControl->Buttons();?>

<script type="text/javascript">
function RestoreDefaults()
{
	if(confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>'))
		window.location = "<?echo $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?=LANGUAGE_ID?>&mid=<?echo urlencode($mid)?>&<?echo bitrix_sessid_get()?>";
}

BX.ready(function(){
	var f = document.forms['main_options'];
	if(f.use_time_zones)
		f.default_time_zone.disabled = f.auto_time_zone.disabled = !f.use_time_zones.checked;
});

</script>
<?if($_REQUEST["back_url_settings"] <> ""):?>
<input <?if (!$USER->CanDoOperation('edit_other_settings')) echo "disabled" ?> type="submit" name="Save" value="<?echo GetMessage("MAIN_SAVE")?>" title="<?echo GetMessage("MAIN_OPT_SAVE_TITLE")?>" class="adm-btn-save">
<?endif?>
<input <?if (!$USER->CanDoOperation('edit_other_settings')) echo "disabled" ?> type="submit" name="Apply" value="<?echo GetMessage("MAIN_OPT_APPLY")?>" title="<?echo GetMessage("MAIN_OPT_APPLY_TITLE")?>"<?if($_REQUEST["back_url_settings"] == ""):?>  class="adm-btn-save"<?endif?>>
<?if($_REQUEST["back_url_settings"] <> ""):?>
<input type="button" name="" value="<?echo GetMessage("MAIN_OPT_CANCEL")?>" title="<?echo GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?echo htmlspecialcharsbx(CUtil::JSEscape($_REQUEST["back_url_settings"]))?>'">
<?endif?>
<input <?if (!$USER->IsAdmin()) echo "disabled" ?> type="button" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="RestoreDefaults();" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
<input type="hidden" name="Update" value="Y">
<input type="hidden" name="back_url_settings" value="<?echo htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
<?$tabControl->End();?>
</form>

<script type="text/javascript">
function BxReqEmail(input)
{
	BX("new_user_registration_email_confirmation").disabled = !input.checked;
}
BX.ready(function(){BxReqEmail(BX("new_user_email_required"));});
</script>
<?
$message = null;

if(
	!IsModuleInstalled("controller")
	&& $_SERVER["REQUEST_METHOD"] == "POST"
	&& ($_POST["controller_join"] <> '' || $_POST["controller_remove"] <> '' || $_POST["controller_save_proxy"] <> '')
	&& $USER->IsAdmin()
	&& check_bitrix_sessid()
)
{
	COption::SetOptionString("main", "controller_proxy_url", $_POST["controller_proxy_url"]);
	COption::SetOptionString("main", "controller_proxy_port", $_POST["controller_proxy_port"]);
	COption::SetOptionString("main", "controller_proxy_user", $_POST["controller_proxy_user"]);
	COption::SetOptionString("main", "controller_proxy_password", $_POST["controller_proxy_password"]);
}

if(
	!IsModuleInstalled("controller")
	&& $_SERVER["REQUEST_METHOD"] == "POST"
	&& ($_POST["controller_join"] <> '' && $_POST["controller_save_proxy"] == '')
	&& $USER->IsAdmin()
	&& check_bitrix_sessid()
	&& COption::GetOptionString("main", "controller_member", "N") != "Y"
)
{
	if($_POST["controller_url"] <> '')
	{
		if($_POST["controller_login"] == '' || $_POST["controller_password"] == '')
		{
			list($member_id, $member_secret_id, $ticket_id) = CControllerClient::InitTicket($_POST["controller_url"]);
			LocalRedirect($_POST["controller_url"]."/bitrix/admin/controller_member_edit.php?lang=".LANGUAGE_ID.'&URL='.urlencode($_POST["site_url"]).'&NAME='.urlencode($_POST["site_name"]).'&MEMBER_ID='.$member_id.'&SECRET_ID='.$member_secret_id.'&TICKET_ID='.$ticket_id.'&back_url='.urlencode(($APPLICATION->IsHTTPS()?"https://":"http://").$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']));
		}
		else
		{
			if(!CControllerClient::JoinToController($_POST["controller_url"], $_POST["controller_login"], $_POST["controller_password"], $_POST["site_url"], false, $_POST["site_name"]))
			{
				if ($e = $APPLICATION->GetException())
					$message = new CAdminMessage(GetMessage("MAIN_ERROR_SAVING"), $e);
			}
		}
	}
	else
	{
		$message = new CAdminMessage(GetMessage("main_options_url_error"));
	}
}

$bControllerRemoveError = false;
if(
	!IsModuleInstalled("controller")
	&& $_SERVER["REQUEST_METHOD"] == "POST"
	&& ($_POST["controller_remove"] <> '' && $_POST["controller_save_proxy"] == '')
	&& $USER->IsAdmin()
	&& check_bitrix_sessid()
	&& COption::GetOptionString("main", "controller_member", "N") == "Y"
)
{
	$controller_url = COption::GetOptionString("main", "controller_url", "");
	if($_POST["controller_login"] == '' || $_POST["controller_password"] == '')
	{
		LocalRedirect($controller_url."/bitrix/admin/controller_member_edit.php?lang=".LANGUAGE_ID.'&act=unregister&member_id='.urlencode(COption::GetOptionString("main", "controller_member_id", "")).'&back_url='.urlencode(($APPLICATION->IsHTTPS()?"https://":"http://").$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']));
	}
	else
	{
		if(!CControllerClient::RemoveFromController($_POST["controller_login"], $_POST["controller_password"]))
		{
			if($_REQUEST['remove_anywhere'] == 'Y')
			{
				CControllerClient::Unlink();
			}
			else
			{
				$bControllerRemoveError = true;
				if ($e = $APPLICATION->GetException())
					$message = new CAdminMessage(GetMessage("MAIN_ERROR_SAVING"), $e);
			}
		}
	}
}
if($message)
	echo $message->Show();
?>
<h2><?=GetMessage("MAIN_SUB2")?></h2>
<?
$aTabs = Array();
$aTabs = array(
	array("DIV" => "fedit2", "TAB" => GetMessage("MAIN_TAB_4"), "ICON" => "main_settings", "TITLE" => GetMessage("MAIN_OPTION_PUBL"))
);

if(!IsModuleInstalled("controller"))
	$aTabs[] = array("DIV" => "fedit4", "TAB" => GetMessage("MAIN_OPTION_CONTROLLER_TAB"), "ICON" => "main_settings", "TITLE" => GetMessage("MAIN_OPTION_CONTROLLER_TAB_TITLE"));

$diskSpace = COption::GetOptionInt("main", "disk_space")*1024*1024;
if ($diskSpace > 0)
{
	$aTabs[] = array("DIV" => "fedit3", "TAB" => GetMessage("MAIN_TAB_7"), "ICON" => "main_settings", "TITLE" => GetMessage("MAIN_OPTION_DISC_SPACE"));
}

$tabControl = new CAdminTabControl("tabControl2", $aTabs, true, true);

$tabControl->Begin();
?>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&amp;lang=<?echo LANG?>">
<?=bitrix_sessid_post()?>
<input type="hidden" name="tabControl2_active_tab" value="fedit2">

<?$tabControl->BeginNextTab();?>
<tr>
	<td colspan="2" align="left">
		<?if(COption::GetOptionString("main", "site_stopped", "N")=="Y"):?>
			<span style="color:red;"><?echo GetMessage("MAIN_OPTION_PUBL_CLOSES")?></span>
		<?else:?>
			<span style="color:green;"><?echo GetMessage("MAIN_OPTION_PUBL_OPENED")?></span>
		<?endif?>
		<br><br>
	</td>
</tr>
<tr>
	<td colspan="2" align="left">
		<?if(COption::GetOptionString("main", "site_stopped", "N")=="Y"):?>
			<input type="hidden" name="start_site" value="Y">
			<input type="submit" <?if (!$USER->CanDoOperation('edit_other_settings')) echo "disabled" ?> name="start_siteb" value="<?echo GetMessage("MAIN_OPTION_PUBL_OPEN")?>">
		<?else:?>
			<input type="hidden" name="stop_site" value="Y">
			<input type="submit" <?if (!$USER->CanDoOperation('edit_other_settings')) echo "disabled" ?> name="stop_siteb" value="<?echo GetMessage("MAIN_OPTION_PUBL_CLOSE")?>">
		<?endif?>
	</td>
</tr>

<?$tabControl->EndTab();?>
</form>

<?if(!IsModuleInstalled("controller")):?>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&amp;lang=<?echo LANG?>">
<?=bitrix_sessid_post()?>
<input type="hidden" name="tabControl2_active_tab" value="fedit4">
<?$tabControl->BeginNextTab();?>
<?
if(COption::GetOptionString("main", "controller_member", "N")!="Y"):
	if(strlen($site_url)<=0)
		$site_url = ($APPLICATION->IsHTTPS()?"https://":"http://").$_SERVER['HTTP_HOST'];
?>
	<script>
	function __ClickContrlMemb()
	{
		if(document.getElementById('controller_url').value.length<=0)
		{
			alert('<?=GetMessage("MAIN_OPTION_CONTROLLER_ALERT")?>');
			return false;
		}
		return confirm('<?=GetMessage("MAIN_OPTION_CONTROLLER_ALERT2")?>');
	}
	</script>
	<tr class="adm-detail-required-field">
		<td><?echo GetMessage("MAIN_OPTION_CONTROLLER_URL")?></td>
		<td><input type="text" size="30" maxlength="255" value="<?=htmlspecialcharsbx($_POST["controller_url"]);?>" name="controller_url" id="controller_url"></td>
	</tr>
	<tr class="heading">
		<td colspan="2"><b><?echo GetMessage("MAIN_OPTION_CONTROLLER_ADDIT_SECT")?></b></td>
	</tr>
	<tr>
		<td><?echo GetMessage("MAIN_OPTION_CONTROLLER_ADM_LOGIN")?></td>
		<td><input type="text" size="30" maxlength="255" value="<?=htmlspecialcharsbx($_POST["controller_login"]);?>" name="controller_login" id="controller_login"></td>
	</tr>
	<tr>
		<td><?echo GetMessage("MAIN_OPTION_CONTROLLER_ADM_PASSWORD")?></td>
		<td><input type="password" size="30" maxlength="255" value="<?=htmlspecialcharsbx($_POST["controller_password"]);?>" name="controller_password" id="controller_password"></td>
	</tr>
	<tr>
		<td><?echo GetMessage("MAIN_OPTION_CONTROLLER_SITENAME")?></td>
		<td><input type="text" size="30" maxlength="255" value="<?=htmlspecialcharsbx($_POST["site_name"]);?>" name="site_name" id="site_name"></td>
	</tr>
	<tr>
		<td><?echo GetMessage("MAIN_OPTION_CONTROLLER_SITEURL")?></td>
		<td><input type="text" size="30" maxlength="255" value="<?=htmlspecialcharsbx($_POST["site_url"]);?>" name="site_url" id="site_url"></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<input type="hidden" name="controller_join" value="Y">
			<input type="submit" name="controller_join" value="<?echo GetMessage("MAIN_OPTION_CONTROLLER_ADD_BUTT")?>" <?if (!$USER->IsAdmin()) echo "disabled" ?> class="adm-btn-save">
		</td>
	</tr>
<?else: //if(COption::GetOptionString("main", "controller_member", "N")!="Y"?>
	<script>
	function __ClickContrlMemb()
	{
		return confirm('<?=GetMessage("MAIN_OPTION_CONTROLLER_ALERT3")?>');
	}
	</script>
	<tr>
		<td><span class="required">*</span><?echo GetMessage("MAIN_OPTION_CONTROLLER_INFO")?></td>
		<td><?=htmlspecialcharsbx(COption::GetOptionString("main", "controller_url", ""));?></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<input type="hidden" name="controller_remove" value="Y">
			<input type="submit" name="controller_remove" value="<?echo GetMessage("MAIN_OPTION_CONTROLLER_UN_BUTT")?>" <?if (!$USER->IsAdmin()) echo "disabled" ?>>
		</td>
	</tr>
	<?if($bControllerRemoveError):?>
	<tr>
		<td><label for="remove_anywhere"><?echo GetMessage("MAIN_OPTION_CONTROLLER_UN_CHECKB")?></label></td>
		<td><input type="checkbox" name="remove_anywhere" id="remove_anywhere" value="Y"></td>
	</tr>
	<?endif;?>
	<tr class="heading">
		<td colspan="2"><b><?echo GetMessage("MAIN_OPTION_CONTROLLER_ADDIT_SECT")?></b></td>
	</tr>
	<tr>
		<td><?echo GetMessage("MAIN_OPTION_CONTROLLER_ADM_LOGIN")?></td>
		<td><input type="text" size="30" maxlength="255" value="<?=htmlspecialcharsbx($_POST["controller_login"]);?>" name="controller_login" id="controller_login"></td>
	</tr>
	<tr>
		<td><?echo GetMessage("MAIN_OPTION_CONTROLLER_ADM_PASSWORD")?></td>
		<td><input type="password" size="30" maxlength="255" value="<?=htmlspecialcharsbx($_POST["controller_password"]);?>" name="controller_password" id="controller_password"></td>
	</tr>
<?endif; //if(COption::GetOptionString("main", "controller_member", "N")!="Y"?>
	<tr class="heading">
		<td colspan="2"><b><?echo GetMessage("MAIN_OPTION_CONTROLLER_PROXY_SECTION")?></b></td>
	</tr>
	<tr>
		<td><?echo GetMessage("MAIN_OPTION_CONTROLLER_PROXY_ADDR")?></td>
		<td><input type="text" size="30" maxlength="255" value="<?=htmlspecialcharsbx(COption::GetOptionString("main", "controller_proxy_url"));?>" name="controller_proxy_url" id="controller_proxy_url"></td>
	</tr>
	<tr>
		<td><?echo GetMessage("MAIN_OPTION_CONTROLLER_PROXY_PORT")?></td>
		<td><input type="text" size="30" maxlength="255" value="<?=htmlspecialcharsbx(COption::GetOptionString("main", "controller_proxy_port"));?>" name="controller_proxy_port" id="controller_proxy_port"></td>
	</tr>
	<tr>
		<td><?echo GetMessage("MAIN_OPTION_CONTROLLER_PROXY_USER")?></td>
		<td><input type="text" size="30" maxlength="255" value="<?=htmlspecialcharsbx(COption::GetOptionString("main", "controller_proxy_user"));?>" name="controller_proxy_user" id="controller_proxy_user"></td>
	</tr>
	<tr>
		<td><?echo GetMessage("MAIN_OPTION_CONTROLLER_PROXY_PASSWORD")?></td>
		<td><input type="password" size="30" maxlength="255" value="<?=htmlspecialcharsbx(COption::GetOptionString("main", "controller_proxy_password"));?>" name="controller_proxy_password" id="controller_proxy_password"></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<input type="submit" name="controller_save_proxy" value="<?echo GetMessage("MAIN_OPTION_CONTROLLER_PROXY_SAVE")?>" <?if (!$USER->IsAdmin()) echo "disabled" ?>>
		</td>
	</tr>
<?$tabControl->EndTab();?>
</form>
<?endif; //if(IsModuleInstalled("controller"))?>

<?if ($diskSpace <= 0):?>
<?$tabControl->End();?>
<?else: ?>
<?$tabControl->EndTab();?>
<?$tabControl->BeginNextTab();?>
<tr>
<td align="left">
<IFRAME style="width:0; height:0; border:none;" src="javascript:void(0)" name="frame_disk_quota" id="frame_disk_quota"></IFRAME>
<?
	$arParam = array();
	$usedSpace = 0;
	$quota = new CDiskQuota();

	foreach (array("db", "files") as $name):
		$res = array();
		if (COption::GetOptionString("main_size", "~".$name."_params"))
			$res = unserialize(COption::GetOptionString("main_size", "~".$name."_params"));
		if ($res)
		{
			$res = array_merge(
				$res,
				array("size" => COption::GetOptionString("main_size", "~".$name)));
		}
		else
		{
			$res = array("size" => COption::GetOptionString("main_size", "~".$name));
		}
		$res["status"] = (($res["status"] == "d") && (intVal(time() - $res["time"]) < 86400)) ? "done" : ($res["status"] == "c" ? "c" : "");
		$res["size_in_per"] = ($diskSpace > 0) ? round(($res["size"]/$diskSpace), 2) : 0;
		$arParam[$name] = $res;
		$usedSpace += $res["size"];
	endforeach;

// ********************************** Progress bar ******************************************************
	?><table><tr><td><div class="pbar-mark-red"></div></td><td><input type="radio" name="size" id="db" value="db" checked="checked" onclick="CheckButtons(this);" /><input type="hidden" name="result_db" id="result_db" value="<?=$arParam["db"]["status"]?>" />
	<label for="db"><?=GetMessage("MAIN_OPTION_SIZE_DB")?>: <span id="div_db"><?=round(($arParam["db"]["size"]/1048576), 2)?></span>Mb
	(<span id="div_time_db"><?=date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG)), $arParam["db"]["time"])?></span>)
	</label></td></tr>
	<tr><td><div class="pbar-mark-green"></div></td><td><input type="radio" name="size" id="files" value="files" onclick="CheckButtons(this);" /><input type="hidden" name="result_files" id="result_files" value="<?=$arParam["files"]["status"]?>" /> <label for="files"><?=GetMessage("MAIN_OPTION_SIZE_DISTR")?>: <span id="div_files"><?=round(($arParam["files"]["size"]/1048576), 2)?></span>Mb</label>
	(<span id="div_time_files"><?=date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG)), $arParam["files"]["time"])?></span>)</td></tr></table><?
	$usedSpace = intVal(($usedSpace/$diskSpace)*100);
?><div class="pbar-outer">
		<div id="pb_db" class="pbar-inner-red<?=($arParam["db"]["status"] == "done" ? "" : "-error")?>" style="width:<?=intVal($arParam["db"]["size_in_per"]*350)?>px; padding-left:<?=intVal($arParam["db"]["size_in_per"]*350)?>px;">&nbsp;</div><div id="pb_files" class="pbar-inner-green<?=($arParam["files"]["status"] == "done" ? "" : "-error")?>" style="width:<?=intVal($arParam["files"]["size_in_per"]*350)?>px; padding-left:<?=intVal($arParam["files"]["size_in_per"]*350)?>px;">&nbsp;</div>
</div>
<div class="pbar-title-outer"><div class="pbar-title-inner"><?=str_replace(array("#USED_SPACE#", "#DISK_QUOTA#"), array("<span id=\"used_size\">".intVal($usedSpace)."</span>%", COption::GetOptionInt("main", "disk_space")." Mb"), GetMessage("MAIN_OPTION_SIZE_PROGRESS_BAR"))?></div></div><br />
	<input type="button" id="butt_start" value="<?=GetMessage("MAIN_OPTION_SIZE_RECOUNT")?>" <?=((!$USER->CanDoOperation('edit_other_settings')) ? "disabled": "onclick=\"StartReCount()\"")?>  class="adm-btn-save"/>
	<input type="button" id="butt_cont" value="<?=GetMessage("MAIN_OPTION_SIZE_CONTINUE")?>" disabled="disabled" <?=((!$USER->CanDoOperation('edit_other_settings')) ? "disabled":  "onclick=\"StartReCount('from_the_last')\"")?> />
	<input type="button" id="butt_stop" value="<?=GetMessage("MAIN_OPTION_SIZE_STOP")?>" disabled="disabled" <?=((!$USER->CanDoOperation('edit_other_settings')) ? "disabled": "onclick=\"StopReCount()\"")?> />
	</td>
</tr>
<?$tabControl->End();?>
<?if ($USER->CanDoOperation('edit_other_settings')):?>
<script language="JavaScript">
var result = {'stop':false, 'done':true, 'error':false, 'db':{'size': <?=intVal($arParam["db"]["size"])?>}, 'files':{'size':<?=intVal($arParam["files"]["size"])?>}};
diskSpace = <?=$diskSpace?>;
window.onStepDone = function(name){
	if (name && diskSpace > 0)
	{
		if (document.getElementById('pb_'+name))
		{
			document.getElementById('pb_'+name).className = document.getElementById('pb_'+name).className.replace(/\-error/gi, "");
			if (result[name]['status'] != 'd')
				document.getElementById('pb_'+name).className += "-error";
			document.getElementById('pb_'+name).style.width = ((result[name]['size']/diskSpace)*350)+'px';
			document.getElementById('pb_'+name).style.paddingLeft = ((result[name]['size']/diskSpace)*350)+'px';
			document.getElementById('div_'+name).innerHTML = Math.round(result[name]['size']/1048576*100)/100;
			document.getElementById('div_time_'+name).innerHTML = result[name]['time'];
			document.getElementById('used_size').innerHTML = parseInt(((parseInt(result['db']['size']) + parseInt(result['files']['size']))/diskSpace)*100);

			document.getElementById('result_'+name).value = result[name]['status'];
		}
	}
	if (result['stop'] == true)
		CheckButtons();
};

function CheckButtons(handle)
{
	document.getElementById('butt_start').disabled = true;
	document.getElementById('butt_cont').disabled = true;
	document.getElementById('butt_stop').disabled = true;

	if (!handle)
	{
		var elem = document.getElementsByName('size');
		for(var ii = 0; ii < elem.length; ii++)
		{
			if (elem[ii].checked == true)
			{
				handle = elem[ii];
				break;
			}
		}
	}
	if (handle)
	{
		if (document.getElementById('result_' + handle.id).value.substr(0,1) == 'c')
		{
			document.getElementById('butt_cont').disabled = false;
			document.getElementById('butt_start').disabled = false;
		}
		if (document.getElementById('result_' + handle.id).value.substr(0,1) == 'd' ||
			!document.getElementById('result_' + handle.id).value)
		{
			document.getElementById('butt_start').disabled = false;
		}
	}
	return true;
}

function StartReCount(step)
{
	var name="";
	var elem = document.getElementsByName('size');
	for(var tmp = 0; tmp < elem.length; tmp++)
	{
		if (elem[tmp].checked == true)
		{
			var id = elem[tmp].id;
			name = elem[tmp].value;
		}
		elem[tmp].disabled = true;
	}
	if (name != "")
	{
		CloseWaitWindow();
		result['stop'] = false;
		result['done'] = true;
		ShowWaitWindow();
		if (step == 'from_the_last')
		{
			setTimeout('DoNext(\''+name+'\', \''+id+'\')', 1000);
		}
		else
		{
			setTimeout('DoNext(\''+name+'\', \''+id+'\', \'begin\')', 1000);
		}
		document.getElementById('butt_start').disabled = true;
		document.getElementById('butt_cont').disabled = true;
		document.getElementById('butt_stop').disabled = false;
	}
}

function StopReCount()
{
	var elem = document.getElementsByName('size');
	for(var tmp = 0; tmp < elem.length; tmp++)
	{
		elem[tmp].disabled = false;
	}
	setTimeout('CheckButtons()', 1000);
	CloseWaitWindow();
	result['stop'] = true;
	result['done'] = true;
}

function DoNext(name, id, recount)
{
	if (!name || name=='undefined')
	{
		name = 'size_files';
		id = name;
	}
	var str = '';

	if (result['stop'] == false)
	{
		if (result['done'] == true)
		{
			result['done'] = false;
			if (recount == 'begin')
				str = '&recount=begin';
			document.getElementById('frame_disk_quota').src='/bitrix/admin/quota.php?name=' + id + '&id=' + name + str + '&<?echo bitrix_sessid_get()?>';
		}
	}
	else
	{
		StopReCount();
		return;
	}
	setTimeout('DoNext(\''+name+'\', \''+id+'\')', 1000);
}
CheckButtons();
</script>
<?endif;?>
<?endif;?>
