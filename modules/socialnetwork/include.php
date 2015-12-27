<?
global $DBType;

IncludeModuleLangFile(__FILE__);

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/socialnetwork/tools.php");

// define("SONET_RELATIONS_FRIEND", "F");
// define("SONET_RELATIONS_REQUEST", "Z");
// define("SONET_RELATIONS_BAN", "B");

// define("SONET_ROLES_OWNER", "A");
// define("SONET_ROLES_MODERATOR", "E");
// define("SONET_ROLES_USER", "K");
// define("SONET_ROLES_BAN", "T");
// define("SONET_ROLES_REQUEST", "Z");
// define("SONET_ROLES_ALL", "N");
// define("SONET_ROLES_AUTHORIZED", "L");

// define("SONET_RELATIONS_TYPE_ALL", "A");
// define("SONET_RELATIONS_TYPE_AUTHORIZED", "C");
// define("SONET_RELATIONS_TYPE_FRIENDS2", "E");
// define("SONET_RELATIONS_TYPE_FRIENDS", "M");
// define("SONET_RELATIONS_TYPE_NONE", "Z");

// define("SONET_INITIATED_BY_USER", "U");
// define("SONET_INITIATED_BY_GROUP", "G");

// define("SONET_MESSAGE_SYSTEM", "S");
// define("SONET_MESSAGE_PRIVATE", "P");

// define("DisableSonetLogVisibleSubscr", true);

$arClasses = array(
	"CSocNetGroup" => "classes/".$DBType."/group.php",
	"CSocNetGroupSubject" => "classes/".$DBType."/group_subject.php",
	"CSocNetUserToGroup" => "classes/".$DBType."/user_group.php",
	"CSocNetFeatures" => "classes/".$DBType."/group_features.php",
	"CSocNetFeaturesPerms" => "classes/".$DBType."/group_features_perms.php",
	"CSocNetUserRelations" => "classes/".$DBType."/user_relations.php",
	"CSocNetSmile" => "classes/".$DBType."/smile.php",
	"CSocNetUser" => "classes/".$DBType."/user.php",
	"CSocNetUserPerms" => "classes/".$DBType."/user_perms.php",
	"CSocNetUserEvents" => "classes/".$DBType."/user_events.php",
	"CSocNetMessages" => "classes/".$DBType."/messages.php",
	"CSocNetEventUserView" => "classes/".$DBType."/event_user_view.php",
	"CSocNetLog" => "classes/".$DBType."/log.php",
	"CSocNetLogTools" => "classes/general/log_tools.php",
	"CSocNetLogToolsPhoto" => "classes/general/log_tools_photo.php",
	"CSocNetForumComments" => "classes/general/log_forum_comments.php",
	"CSocNetLogRights" => "classes/general/log_rights.php",
	"CSocNetLogPages" => "classes/general/log_pages.php",
	"CSocNetLogFollow" => "classes/general/log_follow.php",
	"CSocNetLogSmartFilter" => "classes/".$DBType."/log_smartfilter.php",
	"CSocNetLogRestService" => "classes/general/rest.php",
	"logTextParser" => "classes/general/log_tools.php",
	"CSocNetPhotoCommentEvent" => "classes/general/log_tools_photo.php",
	"CSocNetLogComments" => "classes/".$DBType."/log_comments.php",
	"CSocNetLogEvents" => "classes/".$DBType."/log_events.php",
	"CSocNetLogCounter" => "classes/".$DBType."/log_counter.php",
	"CSocNetLogFavorites" => "classes/".$DBType."/log_favorites.php",
	"CSocNetLogComponent" => "classes/general/log_tools.php",
	"CSocNetSubscription" => "classes/".$DBType."/subscription.php",
	"CSocNetSearch" => "classes/general/search.php",
	"CSocNetSearchReindex" => "classes/general/search_reindex.php",
	"CSocNetTextParser" => "classes/general/functions.php",
	"CSocNetTools" => "classes/general/functions.php",
	"CSocNetAllowed" => "classes/general/functions.php",
	"CSocNetGroupAuthProvider" => "classes/general/authproviders.php",
	"CSocNetUserAuthProvider" => "classes/general/authproviders.php",
	"CSocNetLogDestination" => "classes/general/log_destination.php",
	"CSocNetNotifySchema" => "classes/general/notify_schema.php",
	"CSocNetPullSchema" => "classes/general/notify_schema.php",
	"Bitrix\\Socialnetwork\\WorkgroupTable" => "lib/workgroup.php",
	"\\Bitrix\\Socialnetwork\\WorkgroupTable" => "lib/workgroup.php",
	"Bitrix\\Socialnetwork\\LogPageTable" => "lib/logpags.php",
	"\\Bitrix\\Socialnetwork\\LogPageTable" => "lib/logpage.php",
	"socialnetwork" => "install/index.php",
);
CModule::AddAutoloadClasses("socialnetwork", $arClasses);

global $arSocNetAllowedRolesForUserInGroup;
$arSocNetAllowedRolesForUserInGroup = array(SONET_ROLES_MODERATOR, SONET_ROLES_USER, SONET_ROLES_BAN, SONET_ROLES_REQUEST, SONET_ROLES_OWNER);

global $arSocNetAllowedRolesForFeaturesPerms;
$arSocNetAllowedRolesForFeaturesPerms = array(SONET_ROLES_MODERATOR, SONET_ROLES_USER, SONET_ROLES_ALL, SONET_ROLES_OWNER, SONET_ROLES_AUTHORIZED);

global $arSocNetAllowedInitiatePerms;
$arSocNetAllowedInitiatePerms = array(SONET_ROLES_MODERATOR, SONET_ROLES_USER, SONET_ROLES_OWNER);

global $arSocNetAllowedSpamPerms;
$arSocNetAllowedSpamPerms = array(SONET_ROLES_MODERATOR, SONET_ROLES_USER, SONET_ROLES_OWNER, SONET_ROLES_ALL);

global $arSocNetAllowedRelations;
$arSocNetAllowedRelations = array(SONET_RELATIONS_FRIEND, SONET_RELATIONS_REQUEST, SONET_RELATIONS_BAN);

global $arSocNetAllowedRelationsType;
$arSocNetAllowedRelationsType = array(SONET_RELATIONS_TYPE_ALL, SONET_RELATIONS_TYPE_FRIENDS2, SONET_RELATIONS_TYPE_FRIENDS, SONET_RELATIONS_TYPE_NONE, SONET_RELATIONS_TYPE_AUTHORIZED);

global $arSocNetAllowedInitiatedByType;
$arSocNetAllowedInitiatedByType = array(SONET_INITIATED_BY_USER, SONET_INITIATED_BY_GROUP);

// define("SONET_ENTITY_GROUP", "G");
// define("SONET_ENTITY_USER", "U");

// define("SONET_SUBSCRIBE_ENTITY_GROUP", "G");
// define("SONET_SUBSCRIBE_ENTITY_USER", "U");

global $arSocNetAllowedEntityTypes;
$arSocNetAllowedEntityTypes = array(SONET_ENTITY_GROUP, SONET_ENTITY_USER);

$arEntityTypesDescTmp = array(
	SONET_SUBSCRIBE_ENTITY_GROUP => array(
		"TITLE_LIST" => GetMessage("SOCNET_LOG_LIST_G_ALL"),
		"TITLE_LIST_MY" => GetMessage("SOCNET_LOG_LIST_G_ALL_MY"),
		"TITLE_ENTITY" => GetMessage("SOCNET_LOG_G"),
		"TITLE_ENTITY_XDI" => GetMessage("SOCNET_LOG_XDI_G"),
		"TITLE_SETTINGS_ALL" => GetMessage("SOCNET_LOG_GROUP_SETTINGS_ALL"),
		"TITLE_SETTINGS_ALL_1" => GetMessage("SOCNET_LOG_GROUP_SETTINGS_ALL_1"),
		"TITLE_SETTINGS_ALL_2" => GetMessage("SOCNET_LOG_GROUP_SETTINGS_ALL_2"),
		"USE_CB_FILTER" => "Y",
		"HAS_MY" => "Y",
		"CLASS_MY"	=> "CSocNetTools",
		"METHOD_MY"	=> "GetMyGroups",
		"CLASS_OF" => "CSocNetTools",
		"METHOD_OF"	=> "GetGroupUsers",
		"CLASS_MY_BY_ID" => "CSocNetTools",
		"METHOD_MY_BY_ID" => "IsMyGroup",
		"CLASS_DESC_GET" => "CSocNetGroup",
		"METHOD_DESC_GET" => "GetByID",
		"CLASS_DESC_SHOW" => "CSocNetLogTools",
		"METHOD_DESC_SHOW" => "ShowGroup",
		"URL_PARAM_KEY" => "PATH_TO_GROUP",
		"URL_PATTERN" => "group_id",
		"HAS_SITE_ID" => "Y",
		"XDIMPORT_ALLOWED" => "Y"
	),
	SONET_SUBSCRIBE_ENTITY_USER	=> array(
		"TITLE_LIST" => GetMessage("SOCNET_LOG_LIST_U_ALL"),
		"TITLE_LIST_MY" => GetMessage("SOCNET_LOG_LIST_U_ALL_MY"),
		"TITLE_ENTITY" => GetMessage("SOCNET_LOG_U"),
		"TITLE_ENTITY_XDI" => GetMessage("SOCNET_LOG_XDI_U"),
		"TITLE_SETTINGS_ALL" => GetMessage("SOCNET_LOG_USER_SETTINGS_ALL"),
		"TITLE_SETTINGS_ALL_1" => GetMessage("SOCNET_LOG_USER_SETTINGS_ALL_1"),
		"TITLE_SETTINGS_ALL_2" => GetMessage("SOCNET_LOG_USER_SETTINGS_ALL_2"),
		"USE_CB_FILTER" => "Y",
		"HAS_CB" => "Y",
		"HAS_MY" => "Y",
		"CLASS_MY" => "CSocNetTools",
		"METHOD_MY"	=> "GetMyUsers",
		"CLASS_OF" => "CSocNetTools",
		"METHOD_OF" => "GetMyUsers",
		"CLASS_MY_BY_ID" => "CSocNetTools",
		"METHOD_MY_BY_ID" => "IsMyUser",
		"CLASS_DESC_GET" => "CSocNetUser",
		"METHOD_DESC_GET" => "GetByID",
		"CLASS_DESC_SHOW" => "CSocNetLogTools",
		"METHOD_DESC_SHOW" => "ShowUser",
		"URL_PARAM_KEY" => "PATH_TO_USER",
		"URL_PATTERN" => "user_id",
		"XDIMPORT_ALLOWED" => "Y"
	)
);

if (
	!CSocNetUser::IsFriendsAllowed()
	|| !CBXFeatures::IsFeatureEnabled("Friends")
)
{
	$arEntityTypesDescTmp[SONET_SUBSCRIBE_ENTITY_USER]["HAS_MY"] = "N";
}

$arEntityTypeTmp = array(
	SONET_SUBSCRIBE_ENTITY_USER,
	SONET_SUBSCRIBE_ENTITY_GROUP
);

CSocNetAllowed::AddAllowedEntityType($arEntityTypeTmp);

foreach ($arEntityTypesDescTmp as $entityTypeDescCode => $arEntityTypeDesc)
{
	CSocNetAllowed::AddAllowedEntityTypeDesc($entityTypeDescCode, $arEntityTypeDesc);
}

if (
	!defined("BX_MOBILE_LOG")
	|| BX_MOBILE_LOG != true
)
{
	CModule::IncludeModule('intranet');
	IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/socialnetwork/install/js/log_destination.php');
	CJSCore::RegisterExt('socnetlogdest', array(
		'js' => '/bitrix/js/socialnetwork/log-destination.js',
		'css' => '/bitrix/js/main/core/css/core_finder.css',
		'lang_additional' => array(
			'LM_POPUP_TITLE' => GetMessage("LM_POPUP_TITLE"),
			'LM_POPUP_TAB_LAST' => GetMessage("LM_POPUP_TAB_LAST"),
			'LM_POPUP_TAB_SG' => GetMessage("LM_POPUP_TAB_SG"),
			'LM_POPUP_TAB_STRUCTURE' => GetMessage("LM_POPUP_TAB_STRUCTURE"),
			'LM_POPUP_CHECK_STRUCTURE' => GetMessage("LM_POPUP_CHECK_STRUCTURE"),
			'LM_POPUP_TAB_LAST_USERS' => GetMessage("LM_POPUP_TAB_LAST_USERS"),
			'LM_POPUP_TAB_LAST_CONTACTS' => GetMessage("LM_POPUP_TAB_LAST_CONTACTS"),
			'LM_POPUP_TAB_LAST_COMPANIES' => GetMessage("LM_POPUP_TAB_LAST_COMPANIES"),
			'LM_POPUP_TAB_LAST_LEADS' => GetMessage("LM_POPUP_TAB_LAST_LEADS"),
			'LM_POPUP_TAB_LAST_DEALS' => GetMessage("LM_POPUP_TAB_LAST_DEALS"),
			'LM_POPUP_TAB_LAST_SG' => GetMessage("LM_POPUP_TAB_LAST_SG"),
			'LM_POPUP_TAB_LAST_STRUCTURE' => GetMessage("LM_POPUP_TAB_LAST_STRUCTURE"),
			'LM_POPUP_CHECK_STRUCTURE' => GetMessage("LM_POPUP_CHECK_STRUCTURE"),
			'LM_SEARCH_PLEASE_WAIT' => GetMessage("LM_SEARCH_PLEASE_WAIT"),
			'LM_EMPTY_LIST' => GetMessage("LM_EMPTY_LIST"),
			'LM_PLEASE_WAIT' => GetMessage("LM_PLEASE_WAIT"),
			'LM_CREATE_SONETGROUP_TITLE' => GetMessage("LM_CREATE_SONETGROUP_TITLE"),
			'LM_CREATE_SONETGROUP_BUTTON_CREATE' => GetMessage("LM_CREATE_SONETGROUP_BUTTON_CREATE"),
			'LM_CREATE_SONETGROUP_BUTTON_CANCEL' => GetMessage("LM_CREATE_SONETGROUP_BUTTON_CANCEL"),
			'LM_POPUP_WAITER_TEXT' => GetMessage("LM_POPUP_WAITER_TEXT")
		),
		'rel' => array('core', 'popup', 'json', 'finder')
	));
}

// forum
$arFeatureTmp = array(
	"allowed" => array(),
	"operations" => array(
		"full" => array(),
		"newtopic" => array(),
		"answer" => array(),
		"view" => array(),
	),
	"minoperation" => array("view"),
	"subscribe_events" => array(
		"forum" =>  array(
			"ENTITIES" => array(),
			"OPERATION" => "view",
			"CLASS_FORMAT" => "CSocNetLogTools",
			"METHOD_FORMAT" => "FormatEvent_Forum",
			"HAS_CB" => "Y",
			"COMMENT_EVENT"	=> array(
				"EVENT_ID" => "forum",
				"OPERATION" => "view",
				"OPERATION_ADD" => "answer",
				"ADD_CALLBACK" => array("CSocNetLogTools", "AddComment_Forum"),
				"UPDATE_CALLBACK" => array("CSocNetLogTools", "UpdateComment_Forum"),
				"DELETE_CALLBACK" => array("CSocNetLogTools", "DeleteComment_Forum"),
				"CLASS_FORMAT" => "CSocNetLogTools",
				"METHOD_FORMAT" => "FormatComment_Forum",
				"RATING_TYPE_ID" => "FORUM_POST"
			)
		)
	)
);

if (COption::GetOptionString("socialnetwork", "allow_forum_user", "Y") == "Y")
{
	$arFeatureTmp["subscribe_events"]["forum"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_USER] = array(
			"TITLE" => GetMessage("SOCNET_LOG_FORUM_USER"),
			"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_FORUM_USER_SETTINGS"),
			"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_FORUM_USER_SETTINGS_1"),
			"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_FORUM_USER_SETTINGS_2"),
		);

	$arFeatureTmp["allowed"][] = SONET_ENTITY_USER;
	$arFeatureTmp["operations"]["full"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_forum_operation_full_user", SONET_RELATIONS_TYPE_NONE);
	$arFeatureTmp["operations"]["newtopic"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_forum_operation_newtopic_user", SONET_RELATIONS_TYPE_NONE);
	$arFeatureTmp["operations"]["answer"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_forum_operation_answer_user", (CSocNetUser::IsFriendsAllowed() ? SONET_RELATIONS_TYPE_FRIENDS : SONET_RELATIONS_TYPE_AUTHORIZED));
	$arFeatureTmp["operations"]["view"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_forum_operation_view_user", SONET_RELATIONS_TYPE_ALL);
}

if (COption::GetOptionString("socialnetwork", "allow_forum_group", "Y") == "Y")
{
	$arFeatureTmp["subscribe_events"]["forum"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP] = array(
		"TITLE" => GetMessage("SOCNET_LOG_FORUM_GROUP"),
		"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_FORUM_GROUP_SETTINGS"),
		"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_FORUM_GROUP_SETTINGS_1"),
		"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_FORUM_GROUP_SETTINGS_2"),
	);

	$arFeatureTmp["allowed"][] = SONET_ENTITY_GROUP;
	$arFeatureTmp["operations"]["full"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_forum_operation_full_group", SONET_ROLES_MODERATOR);
	$arFeatureTmp["operations"]["newtopic"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_forum_operation_newtopic_group", SONET_ROLES_USER);
	$arFeatureTmp["operations"]["answer"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_forum_operation_answer_group", SONET_ROLES_USER);
	$arFeatureTmp["operations"]["view"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_forum_operation_view_group", SONET_ROLES_USER);
}

CSocNetAllowed::AddAllowedFeature("forum", $arFeatureTmp);

// photo
$arFeatureTmp = array(
	"allowed" => array(),
	"operations" => array(
		"write" => array(),
		"view" => array(),
	),
	"minoperation" => array("view"),
	"subscribe_events" => array(
		"photo" =>  array(
			"ENTITIES" => array(),
			"OPERATION" => "view",
			"CLASS_FORMAT" => "CSocNetLogTools",
			"METHOD_FORMAT"	=> "FormatEvent_Photo",
			"HAS_CB" => "Y",
			"FULL_SET" => array("photo", "photo_photo", "photo_comment"),
			"COMMENT_EVENT"	=> array(
				"EVENT_ID" => "photoalbum_comment",
				"OPERATION" => "view",
				"OPERATION_ADD"	=> "view",
				"ADD_CALLBACK" => array("CSocNetPhotoCommentEvent", "AddComment_PhotoAlbum"),
				"UPDATE_CALLBACK" => "NO_SOURCE",
				"DELETE_CALLBACK" => "NO_SOURCE",
				"CLASS_FORMAT" => "CSocNetLogTools",
				"METHOD_FORMAT"	=> "FormatComment_PhotoAlbum",
				"RATING_TYPE_ID" => "LOG_COMMENT"
			)
		),
		"photo_photo" =>  array(
			"OPERATION" => "view",
			"CLASS_FORMAT" => "CSocNetLogTools",
			"METHOD_FORMAT"	=> "FormatEvent_PhotoPhoto",
			"HIDDEN" => true,
			"HAS_CB" => "Y",
			"ENTITIES" => array(
				SONET_SUBSCRIBE_ENTITY_USER => array(),
				SONET_SUBSCRIBE_ENTITY_GROUP => array()
			),
			"COMMENT_EVENT"	=> array(
				"EVENT_ID" => "photo_comment",
				"OPERATION" => "view",
				"OPERATION_ADD"	=> "view",
				"ADD_CALLBACK" => array("CSocNetPhotoCommentEvent", "AddComment_Photo"),
				"UPDATE_CALLBACK" => array("CSocNetPhotoCommentEvent", "UpdateComment_Photo"),
				"DELETE_CALLBACK" => array("CSocNetPhotoCommentEvent", "DeleteComment_Photo"),
				"CLASS_FORMAT" => "CSocNetLogTools",
				"METHOD_FORMAT"	=> "FormatComment_Photo"
			)
		)
	)
);

if (COption::GetOptionString("socialnetwork", "allow_photo_user", "Y") == "Y")
{
	$arFeatureTmp["subscribe_events"]["photo"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_USER] = array(
		"TITLE" => GetMessage("SOCNET_LOG_PHOTO_USER"),
		"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_PHOTO_USER_SETTINGS"),
		"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_PHOTO_USER_SETTINGS_1"),
		"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_PHOTO_USER_SETTINGS_2"),
	);

	$arFeatureTmp["allowed"][] = SONET_ENTITY_USER;
	$arFeatureTmp["operations"]["write"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_photo_operation_write_user", SONET_RELATIONS_TYPE_NONE);
	$arFeatureTmp["operations"]["view"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_photo_operation_view_user", SONET_RELATIONS_TYPE_ALL);
}

if (COption::GetOptionString("socialnetwork", "allow_photo_group", "Y") == "Y")
{
	$arFeatureTmp["subscribe_events"]["photo"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP] = array(
			"TITLE" 			=> GetMessage("SOCNET_LOG_PHOTO_GROUP"),
			"TITLE_SETTINGS"	=> GetMessage("SOCNET_LOG_PHOTO_GROUP_SETTINGS"),
			"TITLE_SETTINGS_1"	=> GetMessage("SOCNET_LOG_PHOTO_GROUP_SETTINGS_1"),
			"TITLE_SETTINGS_2"	=> GetMessage("SOCNET_LOG_PHOTO_GROUP_SETTINGS_2"),
		);

	$arFeatureTmp["allowed"][] = SONET_ENTITY_GROUP;
	$arFeatureTmp["operations"]["write"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_photo_operation_write_group", SONET_ROLES_MODERATOR);
	$arFeatureTmp["operations"]["view"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_photo_operation_view_group", SONET_ROLES_USER);
}

CSocNetAllowed::AddAllowedFeature("photo", $arFeatureTmp);

$bIntranet = IsModuleInstalled('intranet');
$bCalendar = ($bIntranet && CBXFeatures::IsFeatureEditable("calendar"));

// calendar
if ($bCalendar)
{
	$arFeatureTmp = array(
		"allowed" => array(),
		"operations" => array(
			"write" => array(),
			"view" => array(),
		),
		"minoperation" => array("view"),
	);

	if (COption::GetOptionString("socialnetwork", "allow_calendar_user", "Y") == "Y")
	{
		$arFeatureTmp["allowed"][] = SONET_ENTITY_USER;
		$arFeatureTmp["operations"]["write"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_calendar_operation_write_user", SONET_RELATIONS_TYPE_NONE);
		$arFeatureTmp["operations"]["view"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_calendar_operation_view_user", SONET_RELATIONS_TYPE_ALL);
	}

	if (COption::GetOptionString("socialnetwork", "allow_calendar_group", "Y") == "Y")
	{
		$arFeatureTmp["allowed"][] = SONET_ENTITY_GROUP;
		$arFeatureTmp["operations"]["write"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_calendar_operation_write_group", SONET_ROLES_MODERATOR);
		$arFeatureTmp["operations"]["view"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_calendar_operation_view_group", SONET_ROLES_USER);
	}

	CSocNetAllowed::AddAllowedFeature("calendar", $arFeatureTmp);
}

// tasks
if ($bIntranet)
{
	$arFeatureTmp = array(
		"allowed" => array(),
		"operations" => array(
			"view" => array(),
			"view_all" => array(),
			"create_tasks" => array(),
			"edit_tasks" => array(),
			"delete_tasks" => array(),
			"modify_folders" => array(),
			"modify_common_views" => array(),
		),
		"minoperation" => array("view_all", "view")
	);

	$use_tasks_2_0 = COption::GetOptionString("intranet", "use_tasks_2_0", "N");
	if ($use_tasks_2_0 != "Y")
	{
		$arFeatureTmp["subscribe_events"] = array(
			"tasks" =>  array(
				"ENTITIES" => array(),
				"OPERATION" => "view_all",
				"CLASS_FORMAT" => "CSocNetLogTools",
				"METHOD_FORMAT" => "FormatEvent_Task",
				"HAS_CB" => "Y",
			)
		);
	}
	else
	{
		$arFeatureTmp["subscribe_events"] = array(
			"tasks" =>  array(
				"ENTITIES" => array(),
				"OPERATION" => "view",
				"CLASS_FORMAT" => "CSocNetLogTools",
				"METHOD_FORMAT" => "FormatEvent_Task2",
				"HAS_CB" => "Y",
				"FULL_SET"	=> array("tasks", "tasks_comment"),
				"COMMENT_EVENT"	=> array(
					"EVENT_ID" => "tasks_comment",
					"OPERATION" => "view",
					"OPERATION_ADD"	=> "log_rights",
					"ADD_CALLBACK" => array("CSocNetLogTools", "AddComment_Tasks"),
					"UPDATE_CALLBACK" => array("CSocNetLogTools", "UpdateComment_Forum"),
					"DELETE_CALLBACK" => array("CSocNetLogTools", "DeleteComment_Task"),
					"CLASS_FORMAT" => "CSocNetLogTools",
					"METHOD_FORMAT"	=> "FormatComment_Forum",
					"METHOD_CANEDIT" => array("CSocNetLogTools", "CanEditComment_Task"),
					"METHOD_CANEDITOWN" => array("CSocNetLogTools", "CanEditOwnComment_Task"),
					"RATING_TYPE_ID" => "FORUM_POST"
				)
			)
		);
	}

	if (COption::GetOptionString("socialnetwork", "allow_tasks_user", "Y") == "Y")
	{
		$arFeatureTmp["subscribe_events"]["tasks"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_USER] = array(
			"TITLE" => GetMessage("SOCNET_LOG_TASKS_USER"),
			"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_TASKS_USER_SETTINGS"),
			"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_TASKS_USER_SETTINGS_1"),
			"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_TASKS_USER_SETTINGS_2"),
		);

		$arFeatureTmp["allowed"][] = SONET_ENTITY_USER;
		$arFeatureTmp["operations"]["view"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_tasks_operation_view_user", SONET_RELATIONS_TYPE_ALL);
		$arFeatureTmp["operations"]["view_all"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_tasks_operation_view_all_user", SONET_RELATIONS_TYPE_NONE);
		$arFeatureTmp["operations"]["create_tasks"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_tasks_operation_create_tasks_user", SONET_RELATIONS_TYPE_AUTHORIZED);
		$arFeatureTmp["operations"]["edit_tasks"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_tasks_operation_edit_tasks_user", SONET_RELATIONS_TYPE_NONE);
		$arFeatureTmp["operations"]["delete_tasks"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_tasks_operation_delete_tasks_user", SONET_RELATIONS_TYPE_NONE);
		$arFeatureTmp["operations"]["modify_folders"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_tasks_operation_modify_folders_user", SONET_RELATIONS_TYPE_NONE);
		$arFeatureTmp["operations"]["modify_common_views"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_tasks_operation_modify_common_views_user", SONET_RELATIONS_TYPE_NONE);
	}

	if (COption::GetOptionString("socialnetwork", "allow_tasks_group", "Y") == "Y")
	{
		$arFeatureTmp["subscribe_events"]["tasks"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP] = array(
			"TITLE" => GetMessage("SOCNET_LOG_TASKS_GROUP"),
			"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_TASKS_GROUP_SETTINGS"),
			"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_TASKS_GROUP_SETTINGS_1"),
			"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_TASKS_GROUP_SETTINGS_2"),
		);

		$arFeatureTmp["allowed"][] = SONET_ENTITY_GROUP;
		$arFeatureTmp["operations"]["view"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_tasks_operation_view_group", SONET_ROLES_USER);
		$arFeatureTmp["operations"]["view_all"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_tasks_operation_view_all_group", SONET_ROLES_USER);
		$arFeatureTmp["operations"]["create_tasks"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_tasks_operation_create_tasks_group", SONET_ROLES_USER);
		$arFeatureTmp["operations"]["edit_tasks"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_tasks_operation_edit_tasks_group", SONET_ROLES_MODERATOR);
		$arFeatureTmp["operations"]["delete_tasks"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_tasks_operation_delete_tasks_group", SONET_ROLES_MODERATOR);
		$arFeatureTmp["operations"]["modify_folders"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_tasks_operation_modify_folders_group", SONET_ROLES_MODERATOR);
		$arFeatureTmp["operations"]["modify_common_views"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_tasks_operation_modify_common_views_group", SONET_ROLES_MODERATOR);
	}

	CSocNetAllowed::AddAllowedFeature("tasks", $arFeatureTmp);
}

// files
if (
	$bIntranet
	&& (
		COption::GetOptionString("socialnetwork", "allow_files_user", "Y") == "Y" 
		|| COption::GetOptionString("socialnetwork", "allow_files_group", "Y") == "Y"
	)
)
{
	$arFeatureTmp = array(
		"allowed" => array(),
		"operations" => array(
			"view" => array(),
			"write_limited" => array(),
		),
		"minoperation" => array("view"),
		"subscribe_events" => array(
			"files" => array(
				"ENTITIES" => array(),
				"OPERATION" => "view",
				"CLASS_FORMAT" => "CSocNetLogTools",
				"METHOD_FORMAT" => "FormatEvent_Files",
				"HAS_CB" => "Y",
				"FULL_SET" => array("files", "files_comment"),
				"COMMENT_EVENT" => array(
					"EVENT_ID" => "files_comment",
					"OPERATION" => "view",
					"OPERATION_ADD" => "",
					"ADD_CALLBACK" => array("CSocNetLogTools", "AddComment_Files"),
					"CLASS_FORMAT" => "CSocNetLogTools",
					"METHOD_FORMAT" => "FormatComment_Files"
				)
			)
		)
	);

	if (IsModuleInstalled("bizproc"))
	{
		$arFeatureTmp["operations"]["bizproc"] = array();
	}

	$arFeatureTmp["operations"]["write"] = array();

	if (COption::GetOptionString("socialnetwork", "allow_files_user", "Y") == "Y")
	{
		$arFeatureTmp["subscribe_events"]["files"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_USER] = array(
				"TITLE" => GetMessage("SOCNET_LOG_FILES_USER"),
				"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_FILES_USER_SETTINGS"),
				"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_FILES_USER_SETTINGS_1"),
				"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_FILES_USER_SETTINGS_2"),
			);

		$arFeatureTmp["allowed"][] = SONET_ENTITY_USER;
		$arFeatureTmp["operations"]["view"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_files_operation_view_user", (CSocNetUser::IsFriendsAllowed() ? SONET_RELATIONS_TYPE_FRIENDS : SONET_RELATIONS_TYPE_ALL));
		$arFeatureTmp["operations"]["write_limited"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_files_operation_write_limited_user", SONET_RELATIONS_TYPE_NONE);
		$arFeatureTmp["operations"]["write"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_files_operation_write_user", SONET_RELATIONS_TYPE_NONE);
	}

	if (COption::GetOptionString("socialnetwork", "allow_files_group", "Y") == "Y")
	{
		$arFeatureTmp["subscribe_events"]["files"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP] = array(
				"TITLE" => GetMessage("SOCNET_LOG_FILES_GROUP"),
				"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_FILES_GROUP_SETTINGS"),
				"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_FILES_GROUP_SETTINGS_1"),
				"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_FILES_GROUP_SETTINGS_2"),
			);

		$arFeatureTmp["allowed"][] = SONET_ENTITY_GROUP;
		$arFeatureTmp["operations"]["view"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_files_operation_view_group", SONET_ROLES_USER);
		$arFeatureTmp["operations"]["write_limited"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_files_operation_write_limited_group", SONET_ROLES_MODERATOR);
		$arFeatureTmp["operations"]["write"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_files_operation_write_group", SONET_ROLES_MODERATOR);
	}

	CSocNetAllowed::AddAllowedFeature("files", $arFeatureTmp);
}

if (
	COption::GetOptionString("socialnetwork", "allow_blog_user", "Y") == "Y" 
	|| COption::GetOptionString("socialnetwork", "allow_blog_group", "Y") == "Y"
)
{
	$arFeatureTmp = array(
		"allowed" => array(),
		"operations" => array(
			"view_post" => array(),
			"premoderate_post" => array(),
			"write_post" => array(),
			"moderate_post" => array(),
			"full_post" => array(),
			"view_comment" => array(),
			"premoderate_comment" => array(),
			"write_comment" => array(),
			"moderate_comment" => array(),
			"full_comment" => array(),
		),
		"minoperation" => array("view_comment", "view_post"),
		"subscribe_events" => array(
			"blog" =>  array(
				"ENTITIES" => array(),
				"OPERATION" => "",
				"NO_SET" => true,
				"REAL_EVENT_ID" => "blog_post",
				"FULL_SET"	=> array("blog", "blog_post", "blog_post_important", "blog_comment")
			),
			"blog_post" => array(
				"ENTITIES" => array(),
				"OPERATION" => "view_post",
				"HIDDEN" => true,
				"CLASS_FORMAT"	=> "CSocNetLogTools",
				"METHOD_FORMAT" => "FormatEvent_Blog",
				"HAS_CB" => "Y",
				"COMMENT_EVENT" => array(
					"EVENT_ID"	=> "blog_comment",
					"OPERATION" => "view_comment",
					"OPERATION_ADD" => "premoderate_comment",
					"ADD_CALLBACK"	=> array("CSocNetLogTools", "AddComment_Blog"),
					"CLASS_FORMAT"	=> "CSocNetLogTools",
					"METHOD_FORMAT" => "FormatComment_Blog"
				)
			),
			"blog_comment" => array(
				"ENTITIES" => array(),
				"OPERATION" => "view_comment",
				"HIDDEN" => true,
				"CLASS_FORMAT"	=> "CSocNetLogTools",
				"METHOD_FORMAT"	=> "FormatEvent_Blog",
				"HAS_CB" => "Y"
			)
		)
	);

	if (COption::GetOptionString("socialnetwork", "allow_blog_user", "Y") == "Y")
	{
		$arFeatureTmp["subscribe_events"]["blog"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_USER] = array(
				"TITLE" 			=> GetMessage("SOCNET_LOG_BLOG_USER"),
				"TITLE_SETTINGS"	=> GetMessage("SOCNET_LOG_BLOG_USER_SETTINGS"),
				"TITLE_SETTINGS_1"	=> GetMessage("SOCNET_LOG_BLOG_USER_SETTINGS_1"),
				"TITLE_SETTINGS_2"	=> GetMessage("SOCNET_LOG_BLOG_USER_SETTINGS_2"),
			);

		$arFeatureTmp["subscribe_events"]["blog_post"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_USER] = array(
				"TITLE" => GetMessage("SOCNET_LOG_BLOG_POST_USER")
			);

		$arFeatureTmp["allowed"][] = SONET_ENTITY_USER;
		$arFeatureTmp["operations"]["view_post"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_blog_operation_view_post_user", SONET_RELATIONS_TYPE_ALL);
		$arFeatureTmp["operations"]["view_comment"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_blog_operation_view_comment_user", SONET_RELATIONS_TYPE_ALL);
		$arFeatureTmp["operations"]["premoderate_comment"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_blog_operation_premoderate_comment_user", SONET_RELATIONS_TYPE_AUTHORIZED);
		$arFeatureTmp["operations"]["write_comment"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_blog_operation_write_comment_user", SONET_RELATIONS_TYPE_AUTHORIZED);
	}

	if (COption::GetOptionString("socialnetwork", "allow_blog_group", "Y") == "Y")
	{
		$arFeatureTmp["subscribe_events"]["blog"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP] = array(
				"TITLE" 			=> GetMessage("SOCNET_LOG_BLOG_GROUP"),
				"TITLE_SETTINGS"	=> GetMessage("SOCNET_LOG_BLOG_GROUP_SETTINGS"),
				"TITLE_SETTINGS_1"	=> GetMessage("SOCNET_LOG_BLOG_GROUP_SETTINGS_1"),
				"TITLE_SETTINGS_2"	=> GetMessage("SOCNET_LOG_BLOG_GROUP_SETTINGS_2"),
			);

		$arFeatureTmp["subscribe_events"]["blog_post"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP] = array(
				"TITLE" => GetMessage("SOCNET_LOG_BLOG_POST_GROUP")
			);

		$arFeatureTmp["allowed"][] = SONET_ENTITY_GROUP;
		$arFeatureTmp["operations"]["view_post"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_view_post_group", SONET_ROLES_USER);
		$arFeatureTmp["operations"]["premoderate_post"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_premoderate_post_group", SONET_ROLES_USER);
		$arFeatureTmp["operations"]["write_post"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_write_post_group", SONET_ROLES_USER);
		$arFeatureTmp["operations"]["moderate_post"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_moderate_post_group", SONET_ROLES_MODERATOR);
		$arFeatureTmp["operations"]["full_post"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_full_post_group", SONET_ROLES_OWNER);
		$arFeatureTmp["operations"]["view_comment"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_view_comment_group", SONET_ROLES_USER);
		$arFeatureTmp["operations"]["premoderate_comment"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_premoderate_comment_group", SONET_ROLES_USER);
		$arFeatureTmp["operations"]["write_comment"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_write_comment_group", SONET_ROLES_USER);
		$arFeatureTmp["operations"]["moderate_comment"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_moderate_comment_group", SONET_ROLES_MODERATOR);
		$arFeatureTmp["operations"]["full_comment"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_full_comment_group", SONET_ROLES_MODERATOR);

		$arFeatureTmp["operations"]["write_post"]["restricted"][SONET_ENTITY_GROUP] = array(SONET_ROLES_ALL);
		$arFeatureTmp["operations"]["premoderate_post"]["restricted"][SONET_ENTITY_GROUP] = array(SONET_ROLES_ALL);
		$arFeatureTmp["operations"]["moderate_post"]["restricted"][SONET_ENTITY_GROUP] = array(SONET_ROLES_ALL);
		$arFeatureTmp["operations"]["full_post"]["restricted"][SONET_ENTITY_GROUP] = array(SONET_ROLES_ALL);
		$arFeatureTmp["operations"]["moderate_comment"]["restricted"][SONET_ENTITY_GROUP] = array(SONET_ROLES_ALL);
		$arFeatureTmp["operations"]["full_comment"]["restricted"][SONET_ENTITY_GROUP] = array(SONET_ROLES_ALL);
	}
	$arFeatureTmp["subscribe_events"]["blog_post_important"] = $arFeatureTmp["subscribe_events"]["blog_post"];

	CSocNetAllowed::AddAllowedFeature("blog", $arFeatureTmp);
}

if (
	IsModuleInstalled('search')
	&& (
		COption::GetOptionString("socialnetwork", "allow_search_user", "N") == "Y" 
		|| COption::GetOptionString("socialnetwork", "allow_search_group", "Y") == "Y"
	)
)
{
	$arFeatureTmp = array(
		"allowed" => array(),
		"operations" => array(),
		"minoperation" => array(),
	);

	if (COption::GetOptionString("socialnetwork", "allow_search_user", "N") == "Y")
	{
		$arFeatureTmp["allowed"][] = SONET_ENTITY_USER;
		$arFeatureTmp["operations"]["view"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_search_operation_view_user", SONET_RELATIONS_TYPE_ALL);
	}

	if (COption::GetOptionString("socialnetwork", "allow_search_group", "Y") == "Y")
	{
		$arFeatureTmp["allowed"][] = SONET_ENTITY_GROUP;
		$arFeatureTmp["operations"]["view"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_search_operation_view_group", SONET_ROLES_USER);
	}

	CSocNetAllowed::AddAllowedFeature("search", $arFeatureTmp);
}

$arLogEvents = array(
	"system" =>  array(
		"ENTITIES"	=> array(
			SONET_SUBSCRIBE_ENTITY_GROUP => array(
				"TITLE" => GetMessage("SOCNET_LOG_SYSTEM_GROUP"),
				"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_SYSTEM_GROUP_SETTINGS"),
				"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_SYSTEM_GROUP_SETTINGS_1"),
				"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_SYSTEM_GROUP_SETTINGS_2"),
				"OPERATION" => "viewsystemevents",
			),
			SONET_SUBSCRIBE_ENTITY_USER => array(
				"TITLE" => GetMessage("SOCNET_LOG_SYSTEM_USER"),
				"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_SYSTEM_USER_SETTINGS"),
				"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_SYSTEM_USER_SETTINGS_1"),
				"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_SYSTEM_USER_SETTINGS_2"),
				"OPERATION" => "viewprofile"
			)
		),
		"FULL_SET" => array("system", "system_friends", "system_groups"),
		"CLASS_FORMAT"	=> "CSocNetLogTools",
		"METHOD_FORMAT" => "FormatEvent_System"
	),
	"system_groups" => array(
		"ENTITIES" => array(
			SONET_SUBSCRIBE_ENTITY_USER => array(
				"TITLE" => GetMessage("SOCNET_LOG_SYSTEM_GROUPS_USER"),
				"OPERATION" => "viewgroups"
			)
		),
		"HIDDEN" => true,
		"CLASS_FORMAT" => "CSocNetLogTools",
		"METHOD_FORMAT" => "FormatEvent_SystemGroups"
	),
	"system_friends" =>  array(
		"ENTITIES" => array(
			SONET_SUBSCRIBE_ENTITY_USER => array(
				"TITLE" => GetMessage("SOCNET_LOG_SYSTEM_FRIENDS_USER"),
				"OPERATION" => "viewfriends"
			)
		),
		"HIDDEN" => true,
		"CLASS_FORMAT" => "CSocNetLogTools",
		"METHOD_FORMAT" => "FormatEvent_SystemFriends"
	)
);

foreach ($arLogEvents as $eventCode => $arLogEventTmp)
{
	CSocNetAllowed::AddAllowedLogEvent($eventCode, $arLogEventTmp);
}

global $arSocNetUserOperations;
$arSocNetUserOperations = array(
	"invitegroup" => SONET_RELATIONS_TYPE_AUTHORIZED,
	"message" => SONET_RELATIONS_TYPE_AUTHORIZED,
	"videocall" => SONET_RELATIONS_TYPE_AUTHORIZED,
	"viewfriends" => COption::GetOptionString("socialnetwork", "default_user_viewfriends", SONET_RELATIONS_TYPE_ALL),
	"viewgroups" => COption::GetOptionString("socialnetwork", "default_user_viewgroups", SONET_RELATIONS_TYPE_ALL),
	"viewprofile" => COption::GetOptionString("socialnetwork", "default_user_viewprofile", SONET_RELATIONS_TYPE_ALL),
);

global $arSocNetUserEvents;
$arSocNetUserEvents = array(
	"SONET_NEW_MESSAGE",
	"SONET_VIDEO_CALL",
	"SONET_INVITE_FRIEND",
	"SONET_INVITE_GROUP",
	"SONET_AGREE_FRIEND",
	"SONET_BAN_FRIEND"
);

if(
	!IsModuleInstalled("video")
	|| !CBXFeatures::IsFeatureEnabled("VideoConference")
)
{
	unset($arSocNetUserOperations["videocall"]);
	unset($arSocNetUserEvents[1]);
}

if (!CBXFeatures::IsFeatureEnabled("WebMessenger"))
{
	unset($arSocNetUserOperations["message"]);
	unset($arSocNetUserEvents[0]);
}

if (!CBXFeatures::IsFeatureEnabled("Workgroups"))
{
	unset($arSocNetUserOperations["invitegroup"]);
	unset($arSocNetUserOperations["viewgroups"]);
	unset($arSocNetUserEvents[3]);
}

if (!defined("CACHED_b_sonet_group_subjects"))
{
	// define("CACHED_b_sonet_group_subjects", 3600);
}

class CSocNetUpdater
{
	public static function Run($version)
	{
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/updtr".$version.".php");
	}
}

?>