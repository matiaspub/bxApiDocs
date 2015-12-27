<?
$module_id = "socialnetwork";

CJSCore::Init(array("access"));

$SONET_RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($SONET_RIGHT>="R") :

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/options.php');
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/admin/settings.php');
IncludeModuleLangFile(__FILE__);

include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/include.php");

if ($REQUEST_METHOD=="GET" && strlen($RestoreDefaults)>0 && $SONET_RIGHT=="W" && check_bitrix_sessid())
{
	COption::RemoveOption("socialnetwork");
	$z = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
	while($zr = $z->Fetch())
	{
		$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
	}
}

$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();

$arUserPermsVar = array(
	SONET_RELATIONS_TYPE_NONE => GetMessage("SONET_PVU_NONE"),
	SONET_RELATIONS_TYPE_FRIENDS => GetMessage("SONET_PVU_FR"),
	SONET_RELATIONS_TYPE_AUTHORIZED => GetMessage("SONET_PVU_AUTHORIZED"),
	SONET_RELATIONS_TYPE_ALL => GetMessage("SONET_PVU_ALL"),
);

$arGroupPermsVar = array(
	SONET_ROLES_OWNER => GetMessage("SONET_PVG_OWNER"),
	SONET_ROLES_MODERATOR => GetMessage("SONET_PVG_MOD"),
	SONET_ROLES_USER => GetMessage("SONET_PVG_USER"),
	SONET_ROLES_AUTHORIZED => GetMessage("SONET_PVG_AUTHORIZED"),
	SONET_ROLES_ALL => GetMessage("SONET_PVG_ALL"),
);

$bIntranet = IsModuleInstalled('intranet');

$arFeatures = array(
	"forum" => GetMessage("SONET_FEATURE_FORUM"),
	"blog" => GetMessage("SONET_FEATURE_BLOG"),
	"photo" => GetMessage("SONET_FEATURE_PHOTO"),
);

$arTooltipFields = array(
	"LOGIN" => GetMessage("SONET_FIELD_LOGIN"),
	"NAME" => GetMessage("SONET_FIELD_NAME"),
	"SECOND_NAME" => GetMessage("SONET_FIELD_SECOND_NAME"),
	"LAST_NAME" => GetMessage("SONET_FIELD_LAST_NAME"),
	"EMAIL" => GetMessage("SONET_FIELD_EMAIL"),
	"LAST_LOGIN" => GetMessage("SONET_FIELD_LAST_LOGIN"),
	"DATE_REGISTER" => GetMessage("SONET_FIELD_DATE_REGISTER"),
	"PERSONAL_BIRTHDAY" => GetMessage("SONET_FIELD_PERSONAL_BIRTHDAY"),
	"PERSONAL_PROFESSION" => GetMessage("SONET_FIELD_PERSONAL_PROFESSION"),
	"PERSONAL_WWW" => GetMessage("SONET_FIELD_PERSONAL_WWW"),
	"PERSONAL_ICQ" => GetMessage("SONET_FIELD_PERSONAL_ICQ"),
	"PERSONAL_GENDER" => GetMessage("SONET_FIELD_PERSONAL_GENDER"),
	"PERSONAL_PHOTO" => GetMessage("SONET_FIELD_PERSONAL_PHOTO"),
	"PERSONAL_NOTES" => GetMessage("SONET_FIELD_PERSONAL_NOTES"),
	"PERSONAL_PHONE" => GetMessage("SONET_FIELD_PERSONAL_PHONE"),
	"PERSONAL_FAX" => GetMessage("SONET_FIELD_PERSONAL_FAX"),
	"PERSONAL_MOBILE" => GetMessage("SONET_FIELD_PERSONAL_MOBILE"),
	"PERSONAL_PAGER" => GetMessage("SONET_FIELD_PERSONAL_PAGER"),
	"PERSONAL_COUNTRY" => GetMessage("SONET_FIELD_PERSONAL_COUNTRY"),
	"PERSONAL_STATE" => GetMessage("SONET_FIELD_PERSONAL_STATE"),
	"PERSONAL_CITY" => GetMessage("SONET_FIELD_PERSONAL_CITY"),
	"PERSONAL_ZIP" => GetMessage("SONET_FIELD_PERSONAL_ZIP"),
	"PERSONAL_STREET" => GetMessage("SONET_FIELD_PERSONAL_STREET"),
	"PERSONAL_MAILBOX" => GetMessage("SONET_FIELD_PERSONAL_MAILBOX"),
	"WORK_COMPANY" => GetMessage("SONET_FIELD_WORK_COMPANY"),
	"WORK_DEPARTMENT" => GetMessage("SONET_FIELD_WORK_DEPARTMENT"),
	"WORK_POSITION" => GetMessage("SONET_FIELD_WORK_POSITION"),
	"MANAGERS" => GetMessage("SONET_FIELD_MANAGERS"),
	"WORK_WWW" => GetMessage("SONET_FIELD_WORK_WWW"),
	"WORK_PROFILE" => GetMessage("SONET_FIELD_WORK_PROFILE"),
	"WORK_LOGO" => GetMessage("SONET_FIELD_WORK_LOGO"),
	"WORK_NOTES" => GetMessage("SONET_FIELD_WORK_NOTES"),
	"WORK_PHONE" => GetMessage("SONET_FIELD_WORK_PHONE"),
	"WORK_FAX" => GetMessage("SONET_FIELD_WORK_FAX"),
	"WORK_PAGER" => GetMessage("SONET_FIELD_WORK_PAGER"),
	"WORK_COUNTRY" => GetMessage("SONET_FIELD_WORK_COUNTRY"),
	"WORK_STATE" => GetMessage("SONET_FIELD_WORK_STATE"),
	"WORK_CITY" => GetMessage("SONET_FIELD_WORK_CITY"),
	"WORK_ZIP" => GetMessage("SONET_FIELD_WORK_ZIP"),
	"WORK_STREET" => GetMessage("SONET_FIELD_WORK_STREET"),
	"WORK_MAILBOX" => GetMessage("SONET_FIELD_WORK_MAILBOX"),
);

$arRes = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("USER", 0, LANGUAGE_ID);
$arTooltipProperties = array();
if (!empty($arRes))
{
	foreach ($arRes as $key => $val)
	{
		$arTooltipProperties[$val["FIELD_NAME"]] = (strLen($val["EDIT_FORM_LABEL"]) > 0 ? $val["EDIT_FORM_LABEL"] : $val["FIELD_NAME"]);
	}
}

$arRatings = array();
$db_res = CRatings::GetList($aSort = array("ID" => "ASC"), array("ACTIVE" => "Y", "ENTITY_ID" => "USER"));
while ($res = $db_res->GetNext())
{
	$arRatings[$res["ID"]] = "[ ".$res["ID"]." ] ".$res["NAME"];
}

if ($bIntranet)
{
	$arTooltipFieldsDefault = array(
		"EMAIL",
		"WORK_PHONE",
		"PERSONAL_PHOTO",
		"PERSONAL_CITY",
		"WORK_COMPANY",
		"WORK_POSITION",
	);
}
else
{
	$arTooltipFieldsDefault = array(
		"PERSONAL_ICQ",
		"PERSONAL_BIRTHDAY",
		"PERSONAL_PHOTO",
		"PERSONAL_CITY",
		"WORK_COMPANY",
		"WORK_POSITION"
	);
}

if ($bIntranet)
{
	$arTooltipPropertiesDefault = array(
		"UF_DEPARTMENT",
		"UF_PHONE_INNER",
		"UF_SKYPE",
	);
}
else
{
	$arTooltipPropertiesDefault = array(
		"UF_SKYPE",
	);
}

$arTooltipFieldsDefault = serialize($arTooltipFieldsDefault);
$arTooltipPropertiesDefault = serialize($arTooltipPropertiesDefault);

if ($bIntranet)
{
	$arFeatures["tasks"] = GetMessage("SONET_FEATURE_TASKS");
}

$bCalendar = ((IsModuleInstalled("calendar") || $bIntranet)	&& CBXFeatures::IsFeatureEditable("calendar"));

if ($bCalendar)
{
	$arFeatures["calendar"] = GetMessage("SONET_FEATURE_CALENDAR");
}

if (IsModuleInstalled('search'))
{
	$arFeatures["search"] = GetMessage("SONET_FEATURE_SEARCH");
}

if (!function_exists('set_valign'))
{
	function set_valign($ctrlType, $bIsMultiple = false)
	{
		if (
			(
				in_array($ctrlType, array("select_fields", "select_properties", "select_rating", "select_user_perm", "select_user", "select_group"))
				&& $bIsMultiple == false
			)
			|| in_array($ctrlType, array("checkbox", "text"))
		)
		{
			return;
		}
		else
		{
			return "class=\"adm-detail-valign-top\"";
		}
	}
}

$arAllOptionsCommon = array(
	array("follow_default_type", GetMessage("SONET_LOG_FOLLOW_DEFAULT_TYPE"), "Y", Array("checkbox")),
	array("allow_livefeed_toall", GetMessage("SONET_LOG_ALLOW_TOALL"), "Y", Array("checkbox")),
	array("livefeed_toall_rights", GetMessage("SONET_LOG_TOALL_RIGHTS"), 'a:1:{i:0;s:2:"AU";}', Array("hidden")),
	array("default_livefeed_toall", GetMessage("SONET_LOG_DEFAULT_TOALL"), "Y", Array("checkbox")),
);

if (!IsModuleInstalled("intranet"))
{
	$arAllOptionsCommon[] = array("sonet_log_smart_filter", GetMessage("SONET_LOG_SMART_FILTER"), "N", Array("checkbox"));
}

$arAllOptions = array(
	array("allow_frields", GetMessage("SONET_ALLOW_FRIELDS"), "Y", Array("checkbox")),
	array("allow_tooltip", GetMessage("SONET_ALLOW_TOOLTIP"), "Y", Array("checkbox")),
	array("group_path_template", GetMessage("SONET_GROUP_PATH_TEMPLATE"), "", Array("text", 40)),
	array("messages_path", GetMessage("SONET_MESSAGES_PATH"), "/company/personal/messages/", Array("text", 40)),
	array("tooltip_fields", GetMessage("SONET_TOOLTIP_FIELDS"), $arTooltipFieldsDefault, Array("select_fields", true, 7)),
	array("tooltip_properties", GetMessage("SONET_TOOLTIP_PROPERTIES"), $arTooltipPropertiesDefault, Array("select_properties", true, 3)),
	array("tooltip_show_rating", GetMessage("SONET_TOOLTIP_SHOW_RATING"), "N", Array("checkbox")),
	array("tooltip_rating_id", GetMessage("SONET_TOOLTIP_RATING_ID"), serialize(Array()), Array("select_rating", true, 3))
);

$arAllOptionsUsers = array(
	array("default_user_viewfriends", GetMessage("SONET_USER_OPERATIONS_viewfriends"), SONET_RELATIONS_TYPE_ALL, Array("select_user_perm")),
	array("default_user_viewgroups", GetMessage("SONET_USER_OPERATIONS_viewgroups"), SONET_RELATIONS_TYPE_ALL, Array("select_user_perm")),
	array("default_user_viewprofile", GetMessage("SONET_USER_OPERATIONS_viewprofile"), SONET_RELATIONS_TYPE_ALL, Array("select_user_perm")),
	array("allow_forum_user", GetMessage("SONET_ALLOW_FORUM_USER"), "Y", Array("checkbox"), "showHideTab", "opt_user_feature_forum"),
	array("allow_photo_user", GetMessage("SONET_ALLOW_PHOTO_USER"), "Y", Array("checkbox"), "showHideTab", "opt_user_feature_photo"),
);

if ($bIntranet)
{
	$arAllOptionsUsers[] = array("allow_files_user", GetMessage("SONET_ALLOW_FILES_USER"), "Y", Array("checkbox"), "showHideTab", "opt_user_feature_files");
	$arAllOptionsUsers[] = array("allow_tasks_user", GetMessage("SONET_ALLOW_TASKS_USER"), "Y", Array("checkbox"), "showHideTab", "opt_user_feature_tasks");
}

if ($bCalendar)
{
	$arAllOptionsUsers[] = array("allow_calendar_user", GetMessage("SONET_ALLOW_CALENDAR_USER"), "Y", Array("checkbox"), "showHideTab", "opt_user_feature_calendar");
}

if (IsModuleInstalled('search'))
{
	$arAllOptionsUsers[] = array("allow_search_user", GetMessage("SONET_ALLOW_SEARCH_USER"), "N", Array("checkbox"), "showHideTab", "opt_user_feature_search");
}

$arAllOptionsUsersBlocks = array();

$arAllOptionsUsersBlocks["forum"][] = array("default_forum_operation_full_user", GetMessage("SONET_FORUM_OPERATION_FULL_USER"), SONET_RELATIONS_TYPE_NONE, Array("select_user"));
$arAllOptionsUsersBlocks["forum"][] = array("default_forum_operation_newtopic_user", GetMessage("SONET_FORUM_OPERATION_NEWTOPIC_USER"), SONET_RELATIONS_TYPE_NONE, Array("select_user"));
$arAllOptionsUsersBlocks["forum"][] = array("default_forum_operation_answer_user", GetMessage("SONET_FORUM_OPERATION_ANSWER_USER"), (CSocNetUser::IsFriendsAllowed() ? SONET_RELATIONS_TYPE_FRIENDS : SONET_RELATIONS_TYPE_AUTHORIZED), Array("select_user"));
$arAllOptionsUsersBlocks["forum"][] = array("default_forum_operation_view_user", GetMessage("SONET_FORUM_OPERATION_VIEW_USER"), SONET_RELATIONS_TYPE_ALL, Array("select_user"));

$arAllOptionsUsersBlocks["blog"][] = array("default_blog_operation_view_post_user", GetMessage("SONET_BLOG_OPERATION_VIEW_POST_USER"), SONET_RELATIONS_TYPE_ALL, Array("select_user"));
//$arAllOptionsUsersBlocks["blog"][] = array("default_blog_operation_premoderate_post_user", GetMessage("SONET_BLOG_OPERATION_PREMODERATE_POST_USER"), SONET_RELATIONS_TYPE_NONE, Array("select_user"));
//$arAllOptionsUsersBlocks["blog"][] = array("default_blog_operation_write_post_user", GetMessage("SONET_BLOG_OPERATION_WRITE_POST_USER"), SONET_RELATIONS_TYPE_NONE, Array("select_user"));
//$arAllOptionsUsersBlocks["blog"][] = array("default_blog_operation_moderate_post_user", GetMessage("SONET_BLOG_OPERATION_MODERATE_POST_USER"), SONET_RELATIONS_TYPE_NONE, Array("select_user"));
//$arAllOptionsUsersBlocks["blog"][] = array("default_blog_operation_full_post_user", GetMessage("SONET_BLOG_OPERATION_FULL_POST_USER"), SONET_RELATIONS_TYPE_NONE, Array("select_user"));
$arAllOptionsUsersBlocks["blog"][] = array("default_blog_operation_view_comment_user", GetMessage("SONET_BLOG_OPERATION_VIEW_COMMENT_USER"), SONET_RELATIONS_TYPE_ALL, Array("select_user"));
$arAllOptionsUsersBlocks["blog"][] = array("default_blog_operation_premoderate_comment_user", GetMessage("SONET_BLOG_OPERATION_PREMODERATE_COMMENT_USER"), SONET_RELATIONS_TYPE_ALL, Array("select_user"));
$arAllOptionsUsersBlocks["blog"][] = array("default_blog_operation_write_comment_user", GetMessage("SONET_BLOG_OPERATION_WRITE_COMMENT_USER"), SONET_RELATIONS_TYPE_ALL, Array("select_user"));
//$arAllOptionsUsersBlocks["blog"][] = array("default_blog_operation_moderate_comment_user", GetMessage("SONET_BLOG_OPERATION_MODERATE_COMMENT_USER"), SONET_RELATIONS_TYPE_NONE, Array("select_user"));
//$arAllOptionsUsersBlocks["blog"][] = array("default_blog_operation_full_comment_user", GetMessage("SONET_BLOG_OPERATION_FULL_COMMENT_USER"), SONET_RELATIONS_TYPE_NONE, Array("select_user"));

$arAllOptionsUsersBlocks["photo"][]	= array("default_photo_operation_write_user", GetMessage("SONET_PHOTO_OPERATION_WRITE_USER"), SONET_RELATIONS_TYPE_NONE, Array("select_user"));
$arAllOptionsUsersBlocks["photo"][]	= array("default_photo_operation_view_user", GetMessage("SONET_PHOTO_OPERATION_VIEW_USER"), SONET_RELATIONS_TYPE_ALL, Array("select_user"));

if ($bIntranet)
{
	$arAllOptionsUsersBlocks["tasks"][] = array("default_tasks_operation_view_user", GetMessage("SONET_TASKS_OPERATION_VIEW_USER"), SONET_RELATIONS_TYPE_ALL, Array("select_user"));
	$arAllOptionsUsersBlocks["tasks"][] = array("default_tasks_operation_view_all_user", GetMessage("SONET_TASKS_OPERATION_VIEW_ALL_USER"), SONET_RELATIONS_TYPE_NONE, Array("select_user"));
	$arAllOptionsUsersBlocks["tasks"][] = array("default_tasks_operation_create_tasks_user", GetMessage("SONET_TASKS_OPERATION_CREATE_TASKS_USER"), SONET_RELATIONS_TYPE_AUTHORIZED, Array("select_user"));
	$arAllOptionsUsersBlocks["tasks"][] = array("default_tasks_operation_edit_tasks_user", GetMessage("SONET_TASKS_OPERATION_EDIT_TASKS_USER"), SONET_RELATIONS_TYPE_NONE, Array("select_user"));
	$arAllOptionsUsersBlocks["tasks"][] = array("default_tasks_operation_delete_tasks_user", GetMessage("SONET_TASKS_OPERATION_DELETE_TASKS_USER"), SONET_RELATIONS_TYPE_NONE, Array("select_user"));

	if (COption::GetOptionString("intranet", "use_tasks_2_0", "N") != "Y")
		$arAllOptionsUsersBlocks["tasks"][] = array("default_tasks_operation_modify_folders_user", GetMessage("SONET_TASKS_OPERATION_MODIFY_FOLDERS_USER"), SONET_RELATIONS_TYPE_NONE, Array("select_user"));

	$arAllOptionsUsersBlocks["tasks"][] = array("default_tasks_operation_modify_common_views_user", GetMessage("SONET_TASKS_OPERATION_MODIFY_COMMON_VIEWS_USER"), SONET_RELATIONS_TYPE_NONE, Array("select_user"));
}

if ($bCalendar)
{
	$arAllOptionsUsersBlocks["calendar"][] = array("default_calendar_operation_write_user", GetMessage("SONET_CALENDAR_OPERATION_WRITE_USER"), SONET_RELATIONS_TYPE_NONE, Array("select_user"));
	$arAllOptionsUsersBlocks["calendar"][] = array("default_calendar_operation_view_user", GetMessage("SONET_CALENDAR_OPERATION_VIEW_USER"), SONET_RELATIONS_TYPE_ALL, Array("select_user"));
}

if (IsModuleInstalled('search'))
{
	$arAllOptionsUsersBlocks["search"][] = array("default_search_operation_view_user", GetMessage("SONET_SEARCH_OPERATION_VIEW_USER"), SONET_RELATIONS_TYPE_ALL, Array("select_user"));
}

$arAllOptionsUsersGender = array();
$arAllOptionsUsersGender["male"][] = array("default_user_picture_male", GetMessage("SONET_USER_PICTURE"), false, Array("image"));
$arAllOptionsUsersGender["female"][] = array("default_user_picture_female", GetMessage("SONET_USER_PICTURE"), false, Array("image"));
$arAllOptionsUsersGender["unknown"][] = array("default_user_picture_unknown", GetMessage("SONET_USER_PICTURE"), false, Array("image"));

$arAllOptionsGroups = array(
	array("allow_forum_group", GetMessage("SONET_ALLOW_FORUM_GROUP"), "Y", Array("checkbox"), "showHideTab", "opt_group_feature_forum"),
	array("allow_blog_group", GetMessage("SONET_ALLOW_BLOG_GROUP"), "Y", Array("checkbox"), "showHideTab", "opt_group_feature_blog"),
	array("allow_photo_group", GetMessage("SONET_ALLOW_PHOTO_GROUP"), "Y", Array("checkbox"), "showHideTab", "opt_group_feature_photo"),
);

if ($bIntranet)
{
	$arAllOptionsGroups[] = array("allow_files_group", GetMessage("SONET_ALLOW_FILES_GROUP"), "Y", Array("checkbox"), "showHideTab", "opt_group_feature_files");
	$arAllOptionsGroups[] = array("allow_tasks_group", GetMessage("SONET_ALLOW_TASKS_GROUP"), "Y", Array("checkbox"), "showHideTab", "opt_group_feature_tasks");
}

if ($bCalendar)
{
	$arAllOptionsGroups[] = array("allow_calendar_group", GetMessage("SONET_ALLOW_CALENDAR_GROUP"), "Y", Array("checkbox"), "showHideTab", "opt_group_feature_calendar");
}

if (IsModuleInstalled('search'))
{
	$arAllOptionsGroups[] = array("allow_search_group", GetMessage("SONET_ALLOW_SEARCH_GROUP"), "Y", Array("checkbox"), "showHideTab", "opt_group_feature_search");
}

$arAllOptionsGroupsBlocks = array();

$arAllOptionsGroupsBlocks["forum"][] = array("default_forum_operation_full_group", GetMessage("SONET_FORUM_OPERATION_FULL_GROUP"), SONET_ROLES_MODERATOR, Array("select_group"));
$arAllOptionsGroupsBlocks["forum"][] = array("default_forum_operation_newtopic_group", GetMessage("SONET_FORUM_OPERATION_NEWTOPIC_GROUP"), SONET_ROLES_USER, Array("select_group"));
$arAllOptionsGroupsBlocks["forum"][] = array("default_forum_operation_answer_group", GetMessage("SONET_FORUM_OPERATION_ANSWER_GROUP"), SONET_ROLES_USER, Array("select_group"));
$arAllOptionsGroupsBlocks["forum"][] = array("default_forum_operation_view_group", GetMessage("SONET_FORUM_OPERATION_VIEW_GROUP"), SONET_ROLES_USER, Array("select_group"));
$arAllOptionsGroupsBlocks["forum"][] = array("default_forum_create_default", GetMessage("SONET_FUNCTIONALITY_CREATE_DEFAULT"), "Y", Array("checkbox"));

$arAllOptionsGroupsBlocks["blog"][] = array("default_blog_operation_view_post_group", GetMessage("SONET_BLOG_OPERATION_VIEW_POST_GROUP"), SONET_ROLES_USER, Array("select_group"));
$arAllOptionsGroupsBlocks["blog"][] = array("default_blog_operation_premoderate_post_group", GetMessage("SONET_BLOG_OPERATION_PREMODERATE_POST_GROUP"), SONET_ROLES_USER, Array("select_group"));
$arAllOptionsGroupsBlocks["blog"][] = array("default_blog_operation_write_post_group", GetMessage("SONET_BLOG_OPERATION_WRITE_POST_GROUP"), SONET_ROLES_USER, Array("select_group"));
$arAllOptionsGroupsBlocks["blog"][] = array("default_blog_operation_moderate_post_group", GetMessage("SONET_BLOG_OPERATION_MODERATE_POST_GROUP"), SONET_ROLES_MODERATOR, Array("select_group"));
$arAllOptionsGroupsBlocks["blog"][] = array("default_blog_operation_full_post_group", GetMessage("SONET_BLOG_OPERATION_FULL_POST_GROUP"), SONET_ROLES_MODERATOR, Array("select_group"));
$arAllOptionsGroupsBlocks["blog"][] = array("default_blog_operation_view_comment_group", GetMessage("SONET_BLOG_OPERATION_VIEW_COMMENT_GROUP"), SONET_ROLES_USER, Array("select_group"));
$arAllOptionsGroupsBlocks["blog"][] = array("default_blog_operation_premoderate_comment_group", GetMessage("SONET_BLOG_OPERATION_PREMODERATE_COMMENT_GROUP"), SONET_ROLES_USER, Array("select_group"));
$arAllOptionsGroupsBlocks["blog"][] = array("default_blog_operation_write_comment_group", GetMessage("SONET_BLOG_OPERATION_WRITE_COMMENT_GROUP"), SONET_ROLES_USER, Array("select_group"));
$arAllOptionsGroupsBlocks["blog"][] = array("default_blog_operation_moderate_comment_group", GetMessage("SONET_BLOG_OPERATION_MODERATE_COMMENT_GROUP"), SONET_ROLES_MODERATOR, Array("select_group"));
$arAllOptionsGroupsBlocks["blog"][] = array("default_blog_operation_full_comment_group", GetMessage("SONET_BLOG_OPERATION_FULL_COMMENT_GROUP"), SONET_ROLES_MODERATOR, Array("select_group"));
$arAllOptionsGroupsBlocks["blog"][] = array("default_blog_create_default", GetMessage("SONET_FUNCTIONALITY_CREATE_DEFAULT"), "Y", Array("checkbox"));

$arAllOptionsGroupsBlocks["photo"][] = array("default_photo_operation_write_group", GetMessage("SONET_PHOTO_OPERATION_WRITE_GROUP"), SONET_ROLES_MODERATOR, Array("select_group"));
$arAllOptionsGroupsBlocks["photo"][] = array("default_photo_operation_view_group", GetMessage("SONET_PHOTO_OPERATION_VIEW_GROUP"), SONET_ROLES_USER, Array("select_group"));
$arAllOptionsGroupsBlocks["photo"][] = array("default_photo_create_default", GetMessage("SONET_FUNCTIONALITY_CREATE_DEFAULT"), "Y", Array("checkbox"));

if ($bIntranet)
{
	/*
	$arAllOptionsGroupsBlocks["files"][] = array("default_files_operation_view_group", GetMessage("SONET_FILES_OPERATION_VIEW_GROUP"), SONET_ROLES_USER, Array("select_group"));
	$arAllOptionsGroupsBlocks["files"][] = array("default_files_operation_write_limited_group", GetMessage("SONET_FILES_OPERATION_WRITE_LIMITED_GROUP"), SONET_ROLES_MODERATOR, Array("select_group"));
	$arAllOptionsGroupsBlocks["files"][] = array("default_files_operation_write_group", GetMessage("SONET_FILES_OPERATION_WRITE_GROUP"), SONET_ROLES_MODERATOR, Array("select_group"));
	$arAllOptionsGroupsBlocks["files"][] = array("default_files_create_default", GetMessage("SONET_FUNCTIONALITY_CREATE_DEFAULT"), "Y", Array("checkbox"));
	*/

	$arAllOptionsGroupsBlocks["tasks"][] = array("default_tasks_operation_view_group", GetMessage("SONET_TASKS_OPERATION_VIEW_GROUP"), SONET_ROLES_USER, Array("select_group"));
	$arAllOptionsGroupsBlocks["tasks"][] = array("default_tasks_operation_view_all_group", GetMessage("SONET_TASKS_OPERATION_VIEW_ALL_GROUP"), SONET_ROLES_USER, Array("select_group"));
	$arAllOptionsGroupsBlocks["tasks"][] = array("default_tasks_operation_create_tasks_group", GetMessage("SONET_TASKS_OPERATION_CREATE_TASKS_GROUP"), SONET_ROLES_USER, Array("select_group"));
	$arAllOptionsGroupsBlocks["tasks"][] = array("default_tasks_operation_edit_tasks_group", GetMessage("SONET_TASKS_OPERATION_EDIT_TASKS_GROUP"), SONET_ROLES_MODERATOR, Array("select_group"));
	$arAllOptionsGroupsBlocks["tasks"][] = array("default_tasks_operation_delete_tasks_group", GetMessage("SONET_TASKS_OPERATION_DELETE_TASKS_GROUP"), SONET_ROLES_MODERATOR, Array("select_group"));

	if (COption::GetOptionString("intranet", "use_tasks_2_0", "N") != "Y")
		$arAllOptionsGroupsBlocks["tasks"][] = array("default_tasks_operation_modify_folders_group", GetMessage("SONET_TASKS_OPERATION_MODIFY_FOLDERS_GROUP"), SONET_ROLES_MODERATOR, Array("select_group"));

	$arAllOptionsGroupsBlocks["tasks"][] = array("default_tasks_operation_modify_common_views_group", GetMessage("SONET_TASKS_OPERATION_MODIFY_COMMON_VIEWS_GROUP"), SONET_ROLES_MODERATOR, Array("select_group"));
	$arAllOptionsGroupsBlocks["tasks"][] = array("default_tasks_create_default", GetMessage("SONET_FUNCTIONALITY_CREATE_DEFAULT"), "Y", Array("checkbox"));
}

if ($bCalendar)
{
	$arAllOptionsGroupsBlocks["calendar"][] = array("default_calendar_operation_write_group", GetMessage("SONET_CALENDAR_OPERATION_WRITE_GROUP"), SONET_ROLES_MODERATOR, Array("select_group"));
	$arAllOptionsGroupsBlocks["calendar"][] = array("default_calendar_operation_view_group", GetMessage("SONET_CALENDAR_OPERATION_VIEW_GROUP"), SONET_ROLES_USER, Array("select_group"));
	$arAllOptionsGroupsBlocks["calendar"][] = array("default_calendar_create_default", GetMessage("SONET_FUNCTIONALITY_CREATE_DEFAULT"), "Y", Array("checkbox"));
}

if (IsModuleInstalled('search'))
{
	$arAllOptionsGroupsBlocks["search"][] = array("default_search_operation_view_group", GetMessage("SONET_SEARCH_OPERATION_VIEW_GROUP"), SONET_ROLES_USER, Array("select_group"));
	$arAllOptionsGroupsBlocks["search"][] = array("default_search_create_default", GetMessage("SONET_FUNCTIONALITY_CREATE_DEFAULT"), "Y", Array("checkbox"));
}

$arAllOptionsGroups[] = array("work_with_closed_groups", GetMessage("SONET_WORK_WITH_CLOSED_GROUPS"), "N", Array("checkbox"));

$arAllOptionsGroupsGender = array();
$arAllOptionsGroupsGender[] = array("default_group_picture", GetMessage("SONET_GROUP_PICTURE"), false, Array("image"));

$strWarning = "";
if (
	$REQUEST_METHOD == "POST" 
	&& strlen($Update) > 0 
	&& $SONET_RIGHT == "W" 
	&& check_bitrix_sessid()
)
{
	$tmp_count = count($arAllOptionsCommon);
	for ($i = 0; $i < $tmp_count; $i++)
	{
		$name = $arAllOptionsCommon[$i][0];
		$val = ${$name};
		if ($arAllOptionsCommon[$i][3][0] == "checkbox" && $val != "Y")
			$val = "N";
		elseif ($name == "livefeed_toall_rights")
		{
			if (
				is_array($val) 
				&& count($val) > 0
			)
				$val = serialize($val);
			else
				$val = serialize(array("G2"));
		}

		$prev_val = COption::GetOptionString("socialnetwork", $arAllOptionsCommon[$i][0], $arAllOptionsCommon[$i][2], "");
		if ($val != $prev_val)
			COption::SetOptionString("socialnetwork", $arAllOptionsCommon[$i][0], $val, $arAllOptionsCommon[$i][1], "");
	}

	$dbSites = CSite::GetList(($b = ""), ($o = ""), array("ACTIVE" => "Y"));

	$bFriendsDisabledForAllSites = true;
	$bFriendsEnabledForAnySite = false;
	$bFilesDisabledForAllSites = true;
	$bFilesEnabledForAnySite = false;
	$bBlogDisabledForAllSites = true;
	$bBlogEnabledForAnySite = false;
	$bPhotoDisabledForAllSites = true;
	$bPhotoEnabledForAnySite = false;
	$bForumDisabledForAllSites = true;
	$bForumEnabledForAnySite = false;
	$bTasksDisabledForAllSites = true;
	$bTasksEnabledForAnySite = false;
	$bCalendarDisabledForAllSites = true;
	$bCalendarEnabledForAnySite = false;

	while ($arSite = $dbSites->Fetch())
	{
		$tmp_count = count($arAllOptions);
		for ($i = 0; $i < $tmp_count; $i++)
		{
			$name = $arAllOptions[$i][0]."_".$arSite["ID"];
			$val = ${$name};
			if ($arAllOptions[$i][3][0] == "checkbox" && $val != "Y")
				$val = "N";

			if ($arAllOptions[$i][3][0] == "select_fields" || $arAllOptions[$i][3][0] == "select_properties" || $arAllOptions[$i][3][0] == "select_rating")
				if($arAllOptions[$i][3][1] == true): // multiple select
					if (!is_array($val))
						$val = array();
					$val = serialize($val);
				endif;

			$prev_val = COption::GetOptionString("socialnetwork", $arAllOptions[$i][0], $arAllOptions[$i][2], $arSite["ID"]);

			COption::SetOptionString("socialnetwork", $arAllOptions[$i][0], $val, $arAllOptions[$i][1], $arSite["ID"]);

			if ($arAllOptions[$i][0] == "allow_frields")
			{
				if ($val == "Y")
				{
					$bFriendsDisabledForAllSites = false;
					$bFriendsEnabledForAnySite = true;
				}
			}
		}

		$tmp_count = count($arAllOptionsUsers);
		for ($i = 0; $i < $tmp_count; $i++)
		{
			$name = $arAllOptionsUsers[$i][0]."_".$arSite["ID"];
			$val = ${$name};
			if ($arAllOptionsUsers[$i][3][0] == "checkbox" && $val != "Y")
				$val = "N";
			COption::SetOptionString("socialnetwork", $arAllOptionsUsers[$i][0], $val, $arAllOptionsUsers[$i][1], $arSite["ID"]);

			if ($arAllOptionsUsers[$i][0] == "allow_files_user")
			{
				if ($val == "Y")
				{
					$bFilesDisabledForAllSites = false;
					$bFilesEnabledForAnySite = true;
				}
			}

			if ($arAllOptionsUsers[$i][0] == "allow_photo_user")
			{
				if ($val == "Y")
				{
					$bPhotoDisabledForAllSites = false;
					$bPhotoEnabledForAnySite = true;
				}
			}

			if ($arAllOptionsUsers[$i][0] == "allow_forum_user")
			{
				if ($val == "Y")
				{
					$bForumDisabledForAllSites = false;
					$bForumEnabledForAnySite = true;
				}
			}

			if ($arAllOptionsUsers[$i][0] == "allow_tasks_user")
			{
				if ($val == "Y")
				{
					$bTasksDisabledForAllSites = false;
					$bTasksEnabledForAnySite = true;
				}
			}

			if ($arAllOptionsUsers[$i][0] == "allow_calendar_user")
			{
				if ($val == "Y")
				{
					$bCalendarDisabledForAllSites = false;
					$bCalendarEnabledForAnySite = true;
				}
			}
		}

		foreach ($arFeatures as $feature => $feature_name)
		{
			$tmp_count = count($arAllOptionsUsersBlocks[$feature]);
			for ($i = 0; $i < $tmp_count; $i++)
			{
				$name = $arAllOptionsUsersBlocks[$feature][$i][0]."_".$arSite["ID"];
				$val = ${$name};
				if ($arAllOptionsUsersBlocks[$feature][$i][3][0] == "checkbox" && $val != "Y")
					$val = "N";
				COption::SetOptionString("socialnetwork", $arAllOptionsUsersBlocks[$feature][$i][0], $val, $arAllOptionsUsersBlocks[$feature][$i][1], $arSite["ID"]);
			}
		}

		$arGender = array("male", "female", "unknown");
		foreach ($arGender as $gender)
		{
			$tmp_count = count($arAllOptionsUsersGender[$gender]);
			for ($i = 0; $i < $tmp_count; $i++)
			{
				$name = $arAllOptionsUsersGender[$gender][$i][0]."_".$arSite["ID"];

				$arPICTURE = $HTTP_POST_FILES[$name];
				$arPICTURE["del"] = ${$name."_del"};
				$arPICTURE["MODULE_ID"] = "socialnetwork";

				if ($old_fid = COption::GetOptionInt("socialnetwork", $arAllOptionsUsersGender[$gender][$i][0], false, $arSite["ID"]))
					$arPICTURE["old_file"] = $old_fid;

				$checkRes = CFile::CheckImageFile($arPICTURE, 0, 0, 0);

				if (strlen($checkRes) <= 0)
				{
					$fid = CFile::SaveFile($arPICTURE, "socialnetwork");
					if ($arPICTURE["del"] == "Y" || strlen($HTTP_POST_FILES[$name]["name"]) > 0)
						COption::SetOptionInt("socialnetwork", $arAllOptionsUsersGender[$gender][$i][0], intval($fid), $arAllOptionsUsersGender[$gender][$i][1], $arSite["ID"]);
				}
				else
					CAdminMessage::ShowMessage($checkRes);
			}
		}

		$tmp_count = count($arAllOptionsGroups);
		for ($i = 0; $i < $tmp_count; $i++)
		{
			$name = $arAllOptionsGroups[$i][0]."_".$arSite["ID"];
			$val = ${$name};
			if ($arAllOptionsGroups[$i][3][0] == "checkbox" && $val != "Y")
				$val = "N";
			COption::SetOptionString("socialnetwork", $arAllOptionsGroups[$i][0], $val, $arAllOptionsGroups[$i][1], $arSite["ID"]);

			if ($arAllOptionsUsers[$i][0] == "allow_tasks_group")
			{
				if ($val == "Y")
				{
					$bTasksDisabledForAllSites = false;
					$bTasksEnabledForAnySite = true;
				}
			}

			if ($arAllOptionsUsers[$i][0] == "allow_calendar_group")
			{
				if ($val == "Y")
				{
					$bCalendarDisabledForAllSites = false;
					$bCalendarEnabledForAnySite = true;
				}
			}
		}

		foreach ($arFeatures as $feature => $feature_name)
		{
			$tmp_count = count($arAllOptionsGroupsBlocks[$feature]);
			for ($i = 0; $i < $tmp_count; $i++)
			{
				$name = $arAllOptionsGroupsBlocks[$feature][$i][0]."_".$arSite["ID"];
				$val = ${$name};
				if ($arAllOptionsGroupsBlocks[$feature][$i][3][0] == "checkbox" && $val != "Y")
					$val = "N";
				COption::SetOptionString("socialnetwork", $arAllOptionsGroupsBlocks[$feature][$i][0], $val, $arAllOptionsGroupsBlocks[$feature][$i][1], $arSite["ID"]);
			}
		}

		$tmp_count = count($arAllOptionsGroupsGender);
		for ($i = 0; $i < $tmp_count; $i++)
		{
			$name = $arAllOptionsGroupsGender[$i][0]."_".$arSite["ID"];

			$arPICTURE = $HTTP_POST_FILES[$name];
			$arPICTURE["del"] = ${$name."_del"};
			$arPICTURE["MODULE_ID"] = "socialnetwork";

			if ($old_fid = COption::GetOptionInt("socialnetwork", $arAllOptionsGroupsGender[$i][0], false, $arSite["ID"]))
				$arPICTURE["old_file"] = $old_fid;

			$checkRes = CFile::CheckImageFile($arPICTURE, 0, 0, 0);

			if (strlen($checkRes) <= 0)
			{
				$fid = CFile::SaveFile($arPICTURE, "socialnetwork");
				if ($arPICTURE["del"] == "Y" || strlen($HTTP_POST_FILES[$name]["name"]) > 0)
					COption::SetOptionInt("socialnetwork", $arAllOptionsGroupsGender[$i][0], intval($fid), $arAllOptionsGroupsGender[$i][1], $arSite["ID"]);
			}
			else
				CAdminMessage::ShowMessage($checkRes);
		}
	}

	if ($bFriendsDisabledForAllSites)
	{
		if (CBXFeatures::IsFeatureEnabled("Friends"))
		{
			CBXFeatures::SetFeatureEnabled("Friends", false, false);
		}
	}
	elseif(
		$bFriendsEnabledForAnySite
		&& CBXFeatures::IsFeatureEditable("Friends")
		&& !CBXFeatures::IsFeatureEnabled("Friends")
	)
	{
		CBXFeatures::SetFeatureEnabled("Friends", true, false);
	}

	if ($bFilesDisabledForAllSites)
	{
		if (CBXFeatures::IsFeatureEnabled("PersonalFiles"))
		{
			CBXFeatures::SetFeatureEnabled("PersonalFiles", false, false);
		}
	}
	elseif(
		$bFilesEnabledForAnySite
		&& CBXFeatures::IsFeatureEditable("PersonalFiles")
		&& !CBXFeatures::IsFeatureEnabled("PersonalFiles")
	)
	{
		CBXFeatures::SetFeatureEnabled("PersonalFiles", true, false);
	}

	if ($bBlogDisabledForAllSites)
	{
		if (CBXFeatures::IsFeatureEnabled("PersonalBlog"))
		{
			CBXFeatures::SetFeatureEnabled("PersonalBlog", false, false);
		}
	}
	elseif(
		$bBlogEnabledForAnySite
		&& CBXFeatures::IsFeatureEditable("PersonalBlog")
		&& !CBXFeatures::IsFeatureEnabled("PersonalBlog")
	)
	{
		CBXFeatures::SetFeatureEnabled("PersonalBlog", true, false);
	}

	if ($bPhotoDisabledForAllSites)
	{
		if (CBXFeatures::IsFeatureEnabled("PersonalPhoto"))
		{
			CBXFeatures::SetFeatureEnabled("PersonalPhoto", false, false);
		}
	}
	elseif(
		$bPhotoEnabledForAnySite
		&& CBXFeatures::IsFeatureEditable("PersonalPhoto")
		&& !CBXFeatures::IsFeatureEnabled("PersonalPhoto")
	)
	{
		CBXFeatures::SetFeatureEnabled("PersonalPhoto", true, false);
	}

	if ($bForumDisabledForAllSites)
	{
		if (CBXFeatures::IsFeatureEnabled("PersonalForum"))
		{
			CBXFeatures::SetFeatureEnabled("PersonalForum", false, false);
		}
	}
	elseif(
		$bForumEnabledForAnySite
		&& CBXFeatures::IsFeatureEditable("PersonalForum")
		&& !CBXFeatures::IsFeatureEnabled("PersonalForum")
	)
	{
		CBXFeatures::SetFeatureEnabled("PersonalForum", true, false);
	}

	if ($bTasksDisabledForAllSites)
	{
		if (CBXFeatures::IsFeatureEnabled("Tasks"))
		{
			CBXFeatures::SetFeatureEnabled("Tasks", false, false);
		}

	}
	elseif(
		$bTasksEnabledForAnySite
		&& CBXFeatures::IsFeatureEditable("Tasks")
		&& !CBXFeatures::IsFeatureEnabled("Tasks")
	)
	{
		CBXFeatures::SetFeatureEnabled("Tasks", true, false);
	}

	if ($bCalendarDisabledForAllSites)
	{
		if (CBXFeatures::IsFeatureEnabled("Calendar"))
		{
			CBXFeatures::SetFeatureEnabled("Calendar", false, false);
		}
	}
	elseif(
		$bCalendarEnabledForAnySite
		&& CBXFeatures::IsFeatureEditable("Calendar")
		&& !CBXFeatures::IsFeatureEnabled("Calendar")
	)
	{
		CBXFeatures::SetFeatureEnabled("Calendar", true, false);
	}

	CBitrixComponent::clearComponentCache("bitrix:menu");
}

if (strlen($strWarning) > 0)
{
	CAdminMessage::ShowMessage($strWarning);
}

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("SONET_TAB_SET"), "ICON" => "socialnetwork_settings", "TITLE" => GetMessage("SONET_TAB_SET_ALT")),
	array("DIV" => "edit2", "TAB" => GetMessage("SONET_TAB_RIGHTS"), "ICON" => "socialnetwork_settings", "TITLE" => GetMessage("SONET_TAB_RIGHTS_ALT")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

$aSubTabs = array();
foreach ($arFeatures as $key => $value)
{
	$aSubTabs[] = array("DIV" => "opt_user_feature_".$key."_common", "TAB" => $value, 'TITLE' => GetMessage('SONET_SUBTAB_USER_TITLE_FEATURE').' "'.$value.'"');
}
$arChildTabControlUserCommon = new CAdminViewTabControl("childTabControlUserCommon", $aSubTabs);

$aSubTabs = array();
foreach ($arFeatures as $key => $value)
{
	$aSubTabs[] = array("DIV" => "opt_group_feature_".$key."_common", "TAB" => $value, 'TITLE' => GetMessage('SONET_SUBTAB_GROUP_TITLE_FEATURE').' "'.$value.'"');
}
$arChildTabControlGroupCommon = new CAdminViewTabControl("childTabControlGroupCommon", $aSubTabs);

$aSiteTabs = array();

$dbSites = CSite::GetList(($b = ""), ($o = ""), array("ACTIVE" => "Y"));
while ($arSite = $dbSites->Fetch())
{
	$aSiteTabs[] = array("DIV" => "opt_site_".$arSite["ID"], "TAB" => '['.$arSite["ID"].'] '.htmlspecialcharsbx($arSite["NAME"]), 'TITLE' => GetMessage('SONET_OPTIONS_FOR_SITE').' ['.$arSite["ID"].'] '.htmlspecialcharsbx($arSite["NAME"]));

	$aSubTabs = array();
	foreach ($arFeatures as $key => $value)
	{
		$aSubTabs[] = array(
			"DIV" => "opt_user_feature_".$key."_".$arSite["ID"],
			"TAB" => $value,
			"TITLE" => GetMessage("SONET_SUBTAB_USER_TITLE_FEATURE").' "'.$value.'"',
			"VISIBLE" => (COption::GetOptionString("socialnetwork", "allow_".$key."_user", "Y", $arSite["ID"]) == "Y")
		);
	}

	$arChildTabControlUser[$arSite["ID"]] = new CAdminViewTabControl("childTabControlUser_".$arSite["ID"], $aSubTabs);

	$aSubTabsGender = array(
		array("DIV" => "opt_user_gender_m_".$arSite["ID"], "TAB" => GetMessage("SONET_GENDER_M"), 'TITLE' => GetMessage('SONET_SUBTAB_USER_TITLE_GENDER_M')),
		array("DIV" => "opt_user_gender_f_".$arSite["ID"], "TAB" => GetMessage("SONET_GENDER_F"), 'TITLE' => GetMessage('SONET_SUBTAB_USER_TITLE_GENDER_F')),
		array("DIV" => "opt_user_gender_u_".$arSite["ID"], "TAB" => GetMessage("SONET_GENDER_U"), 'TITLE' => GetMessage('SONET_SUBTAB_USER_TITLE_GENDER_U')),
	);

	$arChildTabControlUserGender[$arSite["ID"]] = new CAdminViewTabControl("childTabControlUserGender_".$arSite["ID"], $aSubTabsGender);

	$aSubTabs = array();
	foreach ($arFeatures as $key => $value)
	{
		$aSubTabs[] = array(
			"DIV" => "opt_group_feature_".$key."_".$arSite["ID"],
			"TAB" => $value,
			"TITLE" => GetMessage('SONET_SUBTAB_GROUP_TITLE_FEATURE').' "'.$value.'"',
			"VISIBLE" => (COption::GetOptionString("socialnetwork", "allow_".$key."_group", "Y", $arSite["ID"]) == "Y")
		);
	}
	$arChildTabControlGroup[$arSite["ID"]] = new CAdminViewTabControl("childTabControlGroup_".$arSite["ID"], $aSubTabs);
}

$arChildTabControlSite = new CAdminViewTabControl("childTabControlSite", $aSiteTabs);

$siteList = array(
	array("ID" => "all", "NAME" => GetMessage("SONET_ALL_SITES"))
);
$rsSites = CSite::GetList($by="sort", $order="asc", array("ACTIVE" => "Y"));
$i = 1;
while($arRes = $rsSites->Fetch())
{
	$siteList[$i]["ID"] = $arRes["ID"];
	$siteList[$i]["NAME"] = $arRes["NAME"];
	$i++;
}
$siteCount = $i;

?>
<?
$tabControl->Begin();
?>

<script>
	BX.message({
		SLToAllDel: '<?=CUtil::JSEscape(GetMessage("SONET_LOG_TOALL_DEL"))?>'
	});

	function SelectSite(id)
	{
		<?
		for($i = 0; $i < $siteCount; $i++):
			?>
			document.getElementById('<?= CUtil::JSEscape(htmlspecialcharsbx($siteList[$i]["ID"]));?>_Propery').style.display='none';
			<?
		endfor;
		?>
		document.getElementById(id+'_Propery').style.display='';
	}
	
	function showHideTab(obTabControl, contentId)
	{
		if (BX('view_tab_' + contentId))
		{
			if (BX('view_tab_' + contentId).style.display != 'none')
			{
				obTabControl.DisableTab(contentId);
			}
			else
			{
				obTabControl.EnableTab(contentId);
			}
		}
	}
</script>

<form method="POST" name="sonet_opt_form" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?=LANGUAGE_ID?>" ENCTYPE="multipart/form-data"><?
?><?=bitrix_sessid_post()?><?
$tabControl->BeginNextTab();

	if (count($arAllOptionsCommon) > 0)
	{
		$tmp_count = count($arAllOptionsCommon);
		for ($i = 0; $i < $tmp_count; $i++):
			$Option = $arAllOptionsCommon[$i];
			$val = COption::GetOptionString("socialnetwork", $Option[0], $Option[2]);
			$type = $Option[3];

			if ($type[0] != "hidden")
			{
				?><tr id="<?=htmlspecialcharsbx($Option[0])?>_tr" style="display: <?=($Option[0] != "default_livefeed_toall" || COption::GetOptionString("socialnetwork", "allow_livefeed_toall", "Y") == "Y" ? "table-row" : "none")?>;">
					<td <?=set_valign($type[0], $type[1])?> width="40%"><?

						if ($type[0] == "checkbox")
							echo "<label for=\"".htmlspecialcharsbx($Option[0])."\">".$Option[1]."</label>";
						else
							echo $Option[1];
					?>:</td>
					<td width="60%"><?
						if($type[0]=="checkbox"):
							?><input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0])?>" id="<?echo htmlspecialcharsbx($Option[0])?>" value="Y"<?if($val=="Y")echo" checked";?>><?
						elseif($type[0]=="text"):
							?><input type="text" size="<?echo $type[1]?>" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0])?>"><?
						elseif($type[0]=="textarea"):
							?><textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0])?>"><?echo htmlspecialcharsbx($val)?></textarea><?
						endif;

						if ($Option[0] == "allow_livefeed_toall")
						{
							?><script>
								var toAllCheckBox = BX('allow_livefeed_toall');
							</script><?
						}
						elseif ($Option[0] == "default_livefeed_toall")
						{
							?>
							<script>
								var defaultToAllCont = BX('default_livefeed_toall_tr');
								if (toAllCheckBox && defaultToAllCont)
								{
									BX.bind(toAllCheckBox, 'click', BX.delegate(function(e) {
										defaultToAllCont.style.display = (this.checked ? "" : "none");
									}, toAllCheckBox));
								}
							</script>
							<?
						}
					?></td>
				</tr><?
			}
			elseif ($Option[0] == "livefeed_toall_rights")
			{
				$arToAllRights = unserialize($val);
				if (!$arToAllRights)
					$arToAllRights = unserialize($Option[2]);

				$access = new CAccess();
				$arNames = $access->GetNames($arToAllRights);

				?><tr id="RIGHTS_all" style="display: <?=(COption::GetOptionString("socialnetwork", "allow_livefeed_toall", "Y") == "Y" ? "table-row" : "none")?>;"><td>&nbsp;</td><td><?
				?><script>
				
					var rightsCont = BX('RIGHTS_all');
					if (toAllCheckBox && rightsCont)
					{
						BX.bind(toAllCheckBox, 'click', BX.delegate(function(e) {
							rightsCont.style.display = (this.checked ? "" : "none");
						}, toAllCheckBox));
					}
				
					function DeleteToAllAccessRow(ob)
					{
						var divNode = BX('RIGHTS_div', true);
						var div = BX.findParent(ob, {tag: 'div', className: 'toall-right'}, divNode);
						if (div)
							var right = div.getAttribute('data-bx-right');

						if (div && right)
						{
							BX.remove(div);
							var artoAllRightsNew = [];

							for(var i = 0; i < arToAllRights.length; i++)
								if (arToAllRights[i] != right)
									artoAllRightsNew[artoAllRightsNew.length] = arToAllRights[i];

							arToAllRights = BX.clone(artoAllRightsNew);

							var hidden_el = BX('<?=htmlspecialcharsbx($Option[0])?>_' + right);
							if (hidden_el)
								BX.remove(hidden_el);
						}
						return false;
					}

					function ShowToAllAccessPopup(val)
					{
						val = val || [];

						BX.Access.Init({
							other: {
								disabled: false,
								disabled_g2: true,
								disabled_cr: true
							},
							groups: { disabled: true },
							socnetgroups: { disabled: true },
							extranet: { disabled: true }
						});

						var startValue = {};
						for(var i = 0; i < val.length; i++)
							startValue[val[i]] = true;

						BX.Access.SetSelected(startValue);

						BX.Access.ShowForm({
							callback: function(arRights)
							{
								var divNode = BX('RIGHTS_div', true);
								var pr = false;

								for(var provider in arRights)
								{
									pr = BX.Access.GetProviderName(provider);
									for(var right in arRights[provider])
									{
										divNode.appendChild(BX.create('div', {
											attrs: {
												'data-bx-right': right
											},
											props: {
												'className': 'toall-right'
											},
											children: [
												BX.create('span', {
													html: (pr.length > 0 ? pr + ': ' : '') + arRights[provider][right].name + '&nbsp;'
												}),
												BX.create('a', {
													attrs: { 
														href: 'javascript:void(0);',
														title: BX.message('SLToAllDel')
													},
													props: {
														'className': 'access-delete'
													},
													events: {
														click: function() { DeleteToAllAccessRow(this); }
													}
												})
											]
										}));

										divNode.appendChild(BX.create('input', {
											attrs: {
												'type': 'hidden'
											},
											props: {
												'name': '<?=htmlspecialcharsbx($Option[0])?>[]',
												'id': '<?=htmlspecialcharsbx($Option[0])?>_' + right,
												'value': right
											}
										}));

										arToAllRights[arToAllRights.length] = arRights[provider][right].id;
									}
								}
							}
						});

						return false;
					}
				</script><?

				?><div id="RIGHTS_div"><?
				foreach($arToAllRights as $right)
				{
					?><input type="hidden" name="<?echo htmlspecialcharsbx($Option[0])?>[]" id="<?echo htmlspecialcharsbx($Option[0]."_".$right)?>" value="<?=htmlspecialcharsbx($right)?>"><?
					?><div data-bx-right="<?=$right?>" class="toall-right"><span><?=(!empty($arNames[$right]["provider"]) ? $arNames[$right]["provider"].": " : "").$arNames[$right]["name"]?>&nbsp;</span><a href="javascript:void(0);" onclick="DeleteToAllAccessRow(this);" class="access-delete" title="<?=GetMessage("SONET_LOG_TOALL_DEL")?>"></a></div><?
				}
				?></div><?
				?><script>
					var arToAllRights = <?=CUtil::PhpToJSObject($arToAllRights)?>;
				</script><?
				
				?><div style="padding-top: 5px;"><a href="javascript:void(0)" class="bx-action-href" onclick="ShowToAllAccessPopup(arToAllRights);"><?=GetMessage("SONET_LOG_TOALL_RIGHTS_ADD")?></a></div>
				</td></tr><?
			}

		endfor;
		
		?><tr><td colspan="2">&nbsp;</td></tr><?
	}
	?><tr>
		<td colspan="2"><?
		$arChildTabControlSite->Begin();

		for($j = 1; $j < $siteCount; $j++)
		{
			$arChildTabControlSite->BeginNextTab();
			?><table cellspacing="7" cellpadding="0" border="0" width="100%"><?
			$tmp_count = count($arAllOptions);
			for ($i = 0; $i < $tmp_count; $i++)
			{
				$Option = $arAllOptions[$i];
				$val = COption::GetOptionString("socialnetwork", $Option[0], $Option[2], $siteList[$j]["ID"]);

				if ($Option[0] == "allow_frields")
				{
					$bAllowFriends = ($val == "Y");
				}

				$type = $Option[3];

				if (in_array($type[0], array("select_fields", "select_properties", "select_rating")))
				{
					$val = ($type[1] == true ? unserialize($val) : array($val)); // multiple select
				}
				?><tr>
					<td <?=set_valign($type[0], $type[1])?> width="40%" align="right"><?
						if ($type[0]=="checkbox")
						{
							echo "<label for=\"".htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])."\">".$Option[1]."</label>";
						}
						else
						{
							echo $Option[1];
						}
					?>:</td>
					<td width="60%" align="left">
						<?if($type[0]=="checkbox"):?>
							<input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>" id="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>" value="Y"<?if($val=="Y")echo" checked";?>>
						<?elseif($type[0]=="text"):?>
							<input type="text" size="<?echo $type[1]?>" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>">
						<?elseif($type[0]=="textarea"):?>
							<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>"><?echo htmlspecialcharsbx($val)?></textarea>
						<?elseif($type[0]=="select_fields"):?>
							<select <?=($type[1] == true ? "multiple" : "")?> size="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?><?=($type[1] == true ? "[]" : "")?>">
							<? foreach ($arTooltipFields as $key => $value):
								?><option value="<?=$key?>" <?=(in_array($key, $val) ? "selected" : "")?>><?=$value?></option><?
							endforeach; ?>
							</select>
						<?elseif($type[0]=="select_properties"):?>
							<select <?=($type[1] == true ? "multiple" : "")?> size="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?><?=($type[1] == true ? "[]" : "")?>">
							<? foreach ($arTooltipProperties as $key => $value):
								?><option value="<?=$key?>" <?=(in_array($key, $val) ? "selected" : "")?>><?=$value?></option><?
							endforeach; ?>
							</select>
						<?elseif($type[0]=="select_rating"):?>
							<select <?=($type[1] == true ? "multiple" : "")?> size="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?><?=($type[1] == true ? "[]" : "")?>">
							<? foreach ($arRatings as $key => $value):
								?><option value="<?=$key?>" <?=(in_array($key, $val) ? "selected" : "")?>><?=$value?></option><?
							endforeach; ?>
							</select>
						<?endif?>
					</td>
				</tr><?
			}
			?><tr class="heading">
				<td colspan="2"><?=GetMessage("SONET_4_USERS")?></td>
			</tr><?
			$tmp_count = count($arAllOptionsUsers);
			for ($i = 0; $i < $tmp_count; $i++)
			{
				$Option = $arAllOptionsUsers[$i];

				$val = COption::GetOptionString("socialnetwork", $Option[0], $Option[2], $siteList[$j]["ID"]);
				$type = $Option[3];
				?><tr>
					<td <?=set_valign($type[0], $type[1])?> width="40%" align="right"><?
						if ($type[0]=="checkbox")
						{
							echo "<label for=\"".htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])."\">".$Option[1]."</label>";
						}
						else
						{
							echo $Option[1];
						}
					?>:</td>
					<td width="60%" align="left"><?
						if($type[0]=="checkbox")
						{
							?><input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>" id="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>" value="Y"<?=($val == "Y" ? " checked" : "")?> <?=(isset($Option[4]) && $Option[4] == 'showHideTab' ? ' onclick="showHideTab(childTabControlUser_'.$siteList[$j]["ID"].', \''.$Option[5].'_'.$siteList[$j]["ID"].'\');"' : '')?>><?
						}
						elseif($type[0]=="text")
						{
							?><input type="text" size="<?echo $type[1]?>" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>"><?
						}
						elseif($type[0]=="textarea")
						{
							?><textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>"><?echo htmlspecialcharsbx($val)?></textarea><?
						}
						elseif($type[0]=="select_user_perm")
						{
							?><select name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>"><?
								if (!$bAllowFriends)
								{
									if (in_array($val, array(SONET_RELATIONS_TYPE_FRIENDS, SONET_RELATIONS_TYPE_FRIENDS2)))
									{
										$val = SONET_RELATIONS_TYPE_NONE;
									}
								}
								elseif ($val == SONET_RELATIONS_TYPE_FRIENDS2)
								{
									$val = SONET_RELATIONS_TYPE_FRIENDS;
								}

								foreach ($arUserPermsVar as $key => $value)
								{
									if (
										!$bAllowFriends
										&& $key == SONET_RELATIONS_TYPE_FRIENDS
									)
									{
										continue;
									}
									?><option value="<?=$key?>" <?=($key == $val ? "selected" : "")?>><?=$value?></option><?
								}
							?></select><?
						}
					?></td>
				</tr><?
			}
			?><tr>
				<td colspan="2"><?
				$arChildTabControlUser[$siteList[$j]["ID"]]->Begin();

				foreach ($arAllOptionsUsersBlocks as $feature => $arAllOptionsUsersFeature):
					$arChildTabControlUser[$siteList[$j]["ID"]]->BeginNextTab();
					?><table cellspacing="7" cellpadding="0" border="0" width="100%"><?
					$tmp_count = count($arAllOptionsUsersFeature);
					for ($i = 0; $i < $tmp_count; $i++):
						$Option = $arAllOptionsUsersFeature[$i];

						if (count($Option) > 0)
						{
							$val = COption::GetOptionString("socialnetwork", $Option[0], $Option[2], $siteList[$j]["ID"]);
							$type = $Option[3];
							?>
							<tr>
								<td <?=set_valign($type[0], $type[1])?> width="40%" align="right"><?
									if ($type[0]=="checkbox")
										echo "<label for=\"".htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])."\">".$Option[1]."</label>";
									else
										echo $Option[1];
								?>:</td>
								<td width="60%">
									<?if($type[0]=="checkbox"):?>
										<input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>" id="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>" value="Y"<?if($val=="Y")echo" checked";?>>
									<?elseif($type[0]=="text"):?>
										<input type="text" size="<?echo $type[1]?>" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>">
									<?elseif($type[0]=="textarea"):?>
										<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>"><?echo htmlspecialcharsbx($val)?></textarea>
									<?elseif($type[0]=="select_user"):?>
										<select name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>">
										<?foreach ($arUserPermsVar as $permvar_key => $permvar_value)
										{
											preg_match('/^default_'.$feature.'_operation_([A-Za-z_]+)_user$/i', $Option[0], $matches);
											$operation = $matches[1];

											if (!$bAllowFriends)
											{
												if (in_array($val, array(SONET_RELATIONS_TYPE_FRIENDS, SONET_RELATIONS_TYPE_FRIENDS2)))
												{
													$val = SONET_RELATIONS_TYPE_NONE;
												}
											}
											elseif ($val == SONET_RELATIONS_TYPE_FRIENDS2)
											{
												$val = SONET_RELATIONS_TYPE_FRIENDS;
											}

											if (
												is_array($arSocNetFeaturesSettings[$feature]["operations"][$operation])
												&& (!array_key_exists("restricted", $arSocNetFeaturesSettings[$feature]["operations"][$operation])
												|| !in_array($permvar_key, $arSocNetFeaturesSettings[$feature]["operations"][$operation]["restricted"][SONET_ENTITY_USER]))
											)
											{
												if (
													!$bAllowFriends
													&& $permvar_key == SONET_RELATIONS_TYPE_FRIENDS
												)
												{
													continue;
												}
												?><option value="<?= $permvar_key ?>"<?= ($permvar_key == $val) ? " selected" : "" ?>><?= $permvar_value ?></option><?
											}
										}
										?></select>
									<?endif?>
								</td>
							</tr>
							<?
						}
					endfor;
					?></table><?
				endforeach;

				$arChildTabControlUser[$siteList[$j]["ID"]]->End();
				?></td>
			</tr>
			<tr>
				<td colspan="2"><?
				$arChildTabControlUserGender[$siteList[$j]["ID"]]->Begin();

				foreach ($arAllOptionsUsersGender as $gender => $arOptionUserGender):
					$arChildTabControlUserGender[$siteList[$j]["ID"]]->BeginNextTab();
					?><table cellspacing="7" cellpadding="0" border="0" width="100%"><?
					$tmp_count = count($arOptionUserGender);
					for ($i = 0; $i < $tmp_count; $i++):
						$Option = $arOptionUserGender[$i];

						if (count($Option) > 0)
						{
							$val = COption::GetOptionString("socialnetwork", $Option[0], $Option[2], $siteList[$j]["ID"]);
							$type = $Option[3];
							?><tr>
								<td <?=set_valign($type[0], $type[1])?> width="40%" align="right"><?
									echo $Option[1];
								?>:</td>
								<td width="60%">
									<?if($type[0]=="image"):?>
										<?echo CFile::InputFile(htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"]), 20, $val);?><br>
										<?echo CFile::ShowImage($val, 200, 200, "border=0", "", true)?>
									<?endif?>
								</td>
							</tr>
							<?
						}
					endfor;
					?></table><?
				endforeach;
				$arChildTabControlUserGender[$siteList[$j]["ID"]]->End();
				?></td>
			</tr>
			<tr class="heading">
				<td colspan="2"><?=GetMessage("SONET_4_GROUPS")?></td>
			</tr><?
			$tmp_count = count($arAllOptionsGroups);
			for ($i = 0; $i < $tmp_count; $i++):
				$Option = $arAllOptionsGroups[$i];
				$val = COption::GetOptionString("socialnetwork", $Option[0], $Option[2], $siteList[$j]["ID"]);
				$type = $Option[3];
				?><tr>
					<td <?=set_valign($type[0], $type[1])?> width="40%"><?
						if ($type[0]=="checkbox")
						{
							echo "<label for=\"".htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])."\">".$Option[1]."</label>";
						}
						else
						{
							echo $Option[1];
						}
					?>:</td>
					<td width="60%"><?
						if($type[0]=="checkbox")
						{
							?><input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>" id="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>" value="Y"<?=($val == "Y" ? " checked" : "")?> <?=(isset($Option[4]) && $Option[4] == 'showHideTab' ? ' onclick="showHideTab(childTabControlGroup_'.$siteList[$j]["ID"].', \''.$Option[5].'_'.$siteList[$j]["ID"].'\');"' : '')?>><?
						}
						elseif($type[0]=="text")
						{
							?><input type="text" size="<?echo $type[1]?>" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>"><?
						}
						elseif($type[0]=="textarea")
						{
							?><textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>"><?echo htmlspecialcharsbx($val)?></textarea><?
						}
					?></td>
				</tr><?
			endfor;
			?><tr>
				<td colspan="2"><?
				$arChildTabControlGroup[$siteList[$j]["ID"]]->Begin();

				foreach ($arAllOptionsGroupsBlocks as $feature => $arAllOptionsGroupsFeature):
					$arChildTabControlGroup[$siteList[$j]["ID"]]->BeginNextTab();
					?><table cellspacing="7" cellpadding="0" border="0" width="100%"><?
					$tmp_count = count($arAllOptionsGroupsFeature);
					for ($i = 0; $i < $tmp_count; $i++):
						$Option = $arAllOptionsGroupsFeature[$i];

						if (count($Option) > 0)
						{
							$val = COption::GetOptionString("socialnetwork", $Option[0], $Option[2], $siteList[$j]["ID"]);
							$type = $Option[3];
							?><tr>
								<td <?=set_valign($type[0], $type[1])?> width="40%" align="right"><?
									if ($type[0]=="checkbox")
										echo "<label for=\"".htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])."\">".$Option[1]."</label>";
									else
										echo $Option[1];
								?>:</td>
								<td width="60%">
									<?if($type[0]=="checkbox"):?>
										<input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>" id="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>" value="Y"<?if($val=="Y")echo" checked";?>>
									<?elseif($type[0]=="hidden"):?>
										<input type="hidden" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>">
									<?elseif($type[0]=="text"):?>
										<input type="text" size="<?echo $type[1]?>" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>">
									<?elseif($type[0]=="textarea"):?>
										<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>"><?echo htmlspecialcharsbx($val)?></textarea>
									<?elseif($type[0]=="select_group"):?>
										<select name="<?echo htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])?>">
										<?foreach ($arGroupPermsVar as $permvar_key => $permvar_value):
											preg_match('/^default_'.$feature.'_operation_([A-Za-z_]+)_group$/i', $Option[0], $matches);
											$operation = $matches[1];

											if (
												is_array($arSocNetFeaturesSettings[$feature]["operations"][$operation])
												&& (!array_key_exists("restricted", $arSocNetFeaturesSettings[$feature]["operations"][$operation])
												|| !in_array($permvar_key, $arSocNetFeaturesSettings[$feature]["operations"][$operation]["restricted"][SONET_ENTITY_GROUP]))
											):
												?><option value="<?= $permvar_key ?>"<?= ($permvar_key == $val) ? " selected" : "" ?>><?= $permvar_value ?></option><?
											endif;
										endforeach;?>
										</select><?
									endif;
								?></td>
							</tr><?
						}
					endfor;
					?></table><?
				endforeach;
				$arChildTabControlGroup[$siteList[$j]["ID"]]->End();
				?></td>
			</tr><?
			$tmp_count = count($arAllOptionsGroupsGender);
			for ($i = 0; $i < $tmp_count; $i++):
				$Option = $arAllOptionsGroupsGender[$i];

				$val = COption::GetOptionString("socialnetwork", $Option[0], $Option[2], $siteList[$j]["ID"]);
				$type = $Option[3];
				?><tr>
					<td <?=set_valign($type[0], $type[1])?> width="40%" align="right"><?
						if ($type[0]=="checkbox")
							echo "<label for=\"".htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"])."\">".$Option[1]."</label>";
						else
							echo $Option[1];
					?>:</td>
					<td width="60%" align="left">
						<?if($type[0]=="image"):?>
							<?echo CFile::InputFile(htmlspecialcharsbx($Option[0]."_".$siteList[$j]["ID"]), 20, $val);?><br>
							<?echo CFile::ShowImage($val, 200, 200, "border=0", "", true)?>
						<?endif?>
					</td>
				</tr><?
			endfor;
			?></table><?
		}
		$arChildTabControlSite->End();
		?></td>
	</tr><?
unset($value);
$tabControl->BeginNextTab();?>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
<?$tabControl->Buttons();?>
<script language="JavaScript">
function RestoreDefaults()
{
	if (confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>'))
		window.location = "<?echo $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?echo LANG?>&mid=<?echo urlencode($mid)."&".bitrix_sessid_get();?>";
}
</script>

<input type="submit" <?if ($SONET_RIGHT<"W") echo "disabled" ?> name="Update" value="<?echo GetMessage("MAIN_SAVE")?>" class="adm-btn-save">
<input type="hidden" name="Update" value="Y">
<input type="reset" name="reset" value="<?echo GetMessage("MAIN_RESET")?>">
<input type="button" <?if ($SONET_RIGHT<"W") echo "disabled" ?> title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="RestoreDefaults();" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
<?$tabControl->End();?>
</form>
<?endif;?>