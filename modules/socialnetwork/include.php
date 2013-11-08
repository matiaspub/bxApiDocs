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

global $arSocNetAllowedSubscribeEntityTypes;
$arSocNetAllowedSubscribeEntityTypes = array(
		SONET_SUBSCRIBE_ENTITY_GROUP,
		SONET_SUBSCRIBE_ENTITY_USER
	);

global $arSocNetAllowedSubscribeEntityTypesDesc;
$arSocNetAllowedSubscribeEntityTypesDesc = array(
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

// please use in $arSocNetAllowedSubscribeEntityTypes only strings max 50 symbols (a-zA-Z0-9) length
$events = GetModuleEvents("socialnetwork", "OnFillSocNetAllowedSubscribeEntityTypes");
while ($arEvent = $events->Fetch())
	ExecuteModuleEventEx($arEvent, array(&$arSocNetAllowedSubscribeEntityTypes));

foreach ($arSocNetAllowedSubscribeEntityTypes as $key => $val)
	if (!preg_match('/^[a-zA-Z0-9]+$/', $val))
		unset($arSocNetAllowedSubscribeEntityTypes[$key]);

foreach ($arSocNetAllowedSubscribeEntityTypesDesc as $key => $val)
	if (!preg_match('/^[a-zA-Z0-9]+$/', $key))
		unset($arSocNetAllowedSubscribeEntityTypesDesc[$key]);

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
	"CSocNetLogPages" => "classes/".$DBType."/log_pages.php",
	"CSocNetLogFollow" => "classes/general/log_follow.php",
	"CSocNetLogSmartFilter" => "classes/".$DBType."/log_smartfilter.php",
	"CSocNetLogRestService" => "classes/general/rest.php",	
	"logTextParser" => "classes/general/log_tools.php",
	"CSocNetPhotoCommentEvent" => "classes/general/log_tools_photo.php",
	"CSocNetLogComments" => "classes/".$DBType."/log_comments.php",
	"CSocNetLogEvents" => "classes/".$DBType."/log_events.php",
	"CSocNetLogCounter" => "classes/".$DBType."/log_counter.php",
	"CSocNetLogFavorites" => "classes/".$DBType."/log_favorites.php",
	"CSocNetSubscription" => "classes/".$DBType."/subscription.php",
	"CSocNetSearch" => "classes/general/search.php",
	"CSocNetSearchReindex" => "classes/general/search_reindex.php",
	"CSocNetTextParser" => "classes/general/functions.php",
	"CSocNetTools" => "classes/general/functions.php",
	"CSocNetGroupAuthProvider" => "classes/general/authproviders.php",
	"CSocNetUserAuthProvider" => "classes/general/authproviders.php",
	"CSocNetLogDestination" => "classes/general/log_destination.php",
	"CSocNetNotifySchema" => "classes/general/notify_schema.php",
	"CSocNetPullSchema" => "classes/general/notify_schema.php",
	"Bitrix\\Socialnetwork\\WorkgroupTable" => "lib/workgroup.php",
	"\\Bitrix\\Socialnetwork\\WorkgroupTable" => "lib/workgroup.php",
	"socialnetwork" => "install/index.php",
);
CModule::AddAutoloadClasses("socialnetwork", $arClasses);

if (
	!defined("BX_MOBILE_LOG")
	|| BX_MOBILE_LOG != true
)
	CJSCore::RegisterExt('socnetlogdest', array(
		'js' => '/bitrix/js/socialnetwork/log-destination.js',
		'css' => '/bitrix/js/main/core/css/core_finder.css',
		'lang' => '/bitrix/modules/socialnetwork/lang/'.LANGUAGE_ID.'/install/js/log_destination.php',
		'rel' => array('core', 'popup', 'json')
	));

if (!CSocNetUser::IsFriendsAllowed())
	unset($arSocNetAllowedSubscribeEntityTypesDesc[SONET_SUBSCRIBE_ENTITY_USER]["HAS_MY"]);

global $arSocNetFeaturesSettings;
$arSocNetFeaturesSettings = array();
if (COption::GetOptionString("socialnetwork", "allow_forum_user", "Y") == "Y" || COption::GetOptionString("socialnetwork", "allow_forum_group", "Y") == "Y")
{
	$arSocNetFeaturesSettings["forum"] = array(
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
					"CLASS_FORMAT" => "CSocNetLogTools",
					"METHOD_FORMAT" => "FormatComment_Forum"
				)
			)
		)
	);

	if (COption::GetOptionString("socialnetwork", "allow_forum_user", "Y") == "Y")
	{
		$arSocNetFeaturesSettings["forum"]["subscribe_events"]["forum"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_USER] = array(
				"TITLE" => GetMessage("SOCNET_LOG_FORUM_USER"),
				"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_FORUM_USER_SETTINGS"),
				"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_FORUM_USER_SETTINGS_1"),
				"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_FORUM_USER_SETTINGS_2"),
			);

		$arSocNetFeaturesSettings["forum"]["allowed"][] = SONET_ENTITY_USER;
		$arSocNetFeaturesSettings["forum"]["operations"]["full"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_forum_operation_full_user", SONET_RELATIONS_TYPE_NONE);
		$arSocNetFeaturesSettings["forum"]["operations"]["newtopic"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_forum_operation_newtopic_user", SONET_RELATIONS_TYPE_NONE);
		$arSocNetFeaturesSettings["forum"]["operations"]["answer"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_forum_operation_answer_user", (CSocNetUser::IsFriendsAllowed() ? SONET_RELATIONS_TYPE_FRIENDS : SONET_RELATIONS_TYPE_AUTHORIZED));
		$arSocNetFeaturesSettings["forum"]["operations"]["view"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_forum_operation_view_user", SONET_RELATIONS_TYPE_ALL);
	}

	if (COption::GetOptionString("socialnetwork", "allow_forum_group", "Y") == "Y")
	{
		$arSocNetFeaturesSettings["forum"]["subscribe_events"]["forum"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP] = array(
			"TITLE" => GetMessage("SOCNET_LOG_FORUM_GROUP"),
			"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_FORUM_GROUP_SETTINGS"),
			"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_FORUM_GROUP_SETTINGS_1"),
			"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_FORUM_GROUP_SETTINGS_2"),
		);

		$arSocNetFeaturesSettings["forum"]["allowed"][] = SONET_ENTITY_GROUP;
		$arSocNetFeaturesSettings["forum"]["operations"]["full"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_forum_operation_full_group", SONET_ROLES_MODERATOR);
		$arSocNetFeaturesSettings["forum"]["operations"]["newtopic"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_forum_operation_newtopic_group", SONET_ROLES_USER);
		$arSocNetFeaturesSettings["forum"]["operations"]["answer"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_forum_operation_answer_group", SONET_ROLES_USER);
		$arSocNetFeaturesSettings["forum"]["operations"]["view"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_forum_operation_view_group", SONET_ROLES_USER);
	}
}

if (COption::GetOptionString("socialnetwork", "allow_photo_user", "Y") == "Y" || COption::GetOptionString("socialnetwork", "allow_photo_group", "Y") == "Y")
{
	$arSocNetFeaturesSettings["photo"] = array(
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
					"CLASS_FORMAT" => "CSocNetLogTools",
					"METHOD_FORMAT"	=> "FormatComment_Photo"
				)
			)
		)
	);

	if (COption::GetOptionString("socialnetwork", "allow_photo_user", "Y") == "Y")
	{
		$arSocNetFeaturesSettings["photo"]["subscribe_events"]["photo"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_USER] = array(
			"TITLE" => GetMessage("SOCNET_LOG_PHOTO_USER"),
			"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_PHOTO_USER_SETTINGS"),
			"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_PHOTO_USER_SETTINGS_1"),
			"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_PHOTO_USER_SETTINGS_2"),
		);

		$arSocNetFeaturesSettings["photo"]["allowed"][] = SONET_ENTITY_USER;
		$arSocNetFeaturesSettings["photo"]["operations"]["write"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_photo_operation_write_user", SONET_RELATIONS_TYPE_NONE);
		$arSocNetFeaturesSettings["photo"]["operations"]["view"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_photo_operation_view_user", SONET_RELATIONS_TYPE_ALL);
	}

	if (COption::GetOptionString("socialnetwork", "allow_photo_group", "Y") == "Y")
	{
		$arSocNetFeaturesSettings["photo"]["subscribe_events"]["photo"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP] = array(
				"TITLE" 			=> GetMessage("SOCNET_LOG_PHOTO_GROUP"),
				"TITLE_SETTINGS"	=> GetMessage("SOCNET_LOG_PHOTO_GROUP_SETTINGS"),
				"TITLE_SETTINGS_1"	=> GetMessage("SOCNET_LOG_PHOTO_GROUP_SETTINGS_1"),
				"TITLE_SETTINGS_2"	=> GetMessage("SOCNET_LOG_PHOTO_GROUP_SETTINGS_2"),
			);

		$arSocNetFeaturesSettings["photo"]["allowed"][] = SONET_ENTITY_GROUP;
		$arSocNetFeaturesSettings["photo"]["operations"]["write"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_photo_operation_write_group", SONET_ROLES_MODERATOR);
		$arSocNetFeaturesSettings["photo"]["operations"]["view"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_photo_operation_view_group", SONET_ROLES_USER);
	}
}

$bIntranet = IsModuleInstalled('intranet');
$bCalendar = ($bIntranet && CBXFeatures::IsFeatureEditable("calendar"));

if ($bCalendar)
{
	if (COption::GetOptionString("socialnetwork", "allow_calendar_user", "Y") == "Y" || COption::GetOptionString("socialnetwork", "allow_calendar_group", "Y") == "Y")
	{
		$arSocNetFeaturesSettings["calendar"] = array(
			"allowed" => array(),
			"operations" => array(
				"write" => array(),
				"view" => array(),
			),
			"minoperation" => array("view"),
/*
			"subscribe_events" => array(
				"calendar" =>  array(
					"ENTITIES"	=> array(),
					"OPERATION"	=> "view",
					"HAS_CB"	=> "Y"
				)
			)
*/
		);

		if (COption::GetOptionString("socialnetwork", "allow_calendar_user", "Y") == "Y")
		{
/*
			$arSocNetFeaturesSettings["calendar"]["subscribe_events"]["calendar"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_USER] = array(
					"TITLE" 			=> GetMessage("SOCNET_LOG_CALENDAR_USER"),
				);
*/
			$arSocNetFeaturesSettings["calendar"]["allowed"][] = SONET_ENTITY_USER;
			$arSocNetFeaturesSettings["calendar"]["operations"]["write"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_calendar_operation_write_user", SONET_RELATIONS_TYPE_NONE);
			$arSocNetFeaturesSettings["calendar"]["operations"]["view"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_calendar_operation_view_user", SONET_RELATIONS_TYPE_ALL);
		}

		if (COption::GetOptionString("socialnetwork", "allow_calendar_group", "Y") == "Y")
		{
/*
			$arSocNetFeaturesSettings["calendar"]["subscribe_events"]["calendar"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP] = array(
					"TITLE" 			=> GetMessage("SOCNET_LOG_CALENDAR_GROUP"),
				);
*/
			$arSocNetFeaturesSettings["calendar"]["allowed"][] = SONET_ENTITY_GROUP;
			$arSocNetFeaturesSettings["calendar"]["operations"]["write"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_calendar_operation_write_group", SONET_ROLES_MODERATOR);
			$arSocNetFeaturesSettings["calendar"]["operations"]["view"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_calendar_operation_view_group", SONET_ROLES_USER);
		}
	}
}

if ($bIntranet)
{
	if (COption::GetOptionString("socialnetwork", "allow_tasks_user", "Y") == "Y" || COption::GetOptionString("socialnetwork", "allow_tasks_group", "Y") == "Y")
	{
		$arSocNetFeaturesSettings["tasks"] = array(
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
			$arSocNetFeaturesSettings["tasks"]["subscribe_events"] = array(
				"tasks" =>  array(
					"ENTITIES" => array(),
					"OPERATION" => "view_all",
					"CLASS_FORMAT" => "CSocNetLogTools",
					"METHOD_FORMAT" => "FormatEvent_Task",
					"HAS_CB" => "Y",
				)
			);
		else
			$arSocNetFeaturesSettings["tasks"]["subscribe_events"] = array(
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
						"CLASS_FORMAT" => "CSocNetLogTools",
						"METHOD_FORMAT"	=> "FormatComment_Forum"
					)
				)
			);

		if (COption::GetOptionString("socialnetwork", "allow_tasks_user", "Y") == "Y")
		{
			$arSocNetFeaturesSettings["tasks"]["subscribe_events"]["tasks"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_USER] = array(
				"TITLE" => GetMessage("SOCNET_LOG_TASKS_USER"),
				"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_TASKS_USER_SETTINGS"),
				"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_TASKS_USER_SETTINGS_1"),
				"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_TASKS_USER_SETTINGS_2"),
			);

			$arSocNetFeaturesSettings["tasks"]["allowed"][] = SONET_ENTITY_USER;
			$arSocNetFeaturesSettings["tasks"]["operations"]["view"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_tasks_operation_view_user", SONET_RELATIONS_TYPE_ALL);
			$arSocNetFeaturesSettings["tasks"]["operations"]["view_all"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_tasks_operation_view_all_user", SONET_RELATIONS_TYPE_NONE);
			$arSocNetFeaturesSettings["tasks"]["operations"]["create_tasks"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_tasks_operation_create_tasks_user", SONET_RELATIONS_TYPE_AUTHORIZED);
			$arSocNetFeaturesSettings["tasks"]["operations"]["edit_tasks"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_tasks_operation_edit_tasks_user", SONET_RELATIONS_TYPE_NONE);
			$arSocNetFeaturesSettings["tasks"]["operations"]["delete_tasks"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_tasks_operation_delete_tasks_user", SONET_RELATIONS_TYPE_NONE);
			$arSocNetFeaturesSettings["tasks"]["operations"]["modify_folders"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_tasks_operation_modify_folders_user", SONET_RELATIONS_TYPE_NONE);
			$arSocNetFeaturesSettings["tasks"]["operations"]["modify_common_views"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_tasks_operation_modify_common_views_user", SONET_RELATIONS_TYPE_NONE);
		}

		if (COption::GetOptionString("socialnetwork", "allow_tasks_group", "Y") == "Y")
		{
			$arSocNetFeaturesSettings["tasks"]["subscribe_events"]["tasks"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP] = array(
				"TITLE" => GetMessage("SOCNET_LOG_TASKS_GROUP"),
				"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_TASKS_GROUP_SETTINGS"),
				"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_TASKS_GROUP_SETTINGS_1"),
				"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_TASKS_GROUP_SETTINGS_2"),
			);

			$arSocNetFeaturesSettings["tasks"]["allowed"][] = SONET_ENTITY_GROUP;
			$arSocNetFeaturesSettings["tasks"]["operations"]["view"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_tasks_operation_view_group", SONET_ROLES_USER);
			$arSocNetFeaturesSettings["tasks"]["operations"]["view_all"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_tasks_operation_view_all_group", SONET_ROLES_USER);
			$arSocNetFeaturesSettings["tasks"]["operations"]["create_tasks"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_tasks_operation_create_tasks_group", SONET_ROLES_USER);
			$arSocNetFeaturesSettings["tasks"]["operations"]["edit_tasks"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_tasks_operation_edit_tasks_group", SONET_ROLES_MODERATOR);
			$arSocNetFeaturesSettings["tasks"]["operations"]["delete_tasks"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_tasks_operation_delete_tasks_group", SONET_ROLES_MODERATOR);
			$arSocNetFeaturesSettings["tasks"]["operations"]["modify_folders"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_tasks_operation_modify_folders_group", SONET_ROLES_MODERATOR);
			$arSocNetFeaturesSettings["tasks"]["operations"]["modify_common_views"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_tasks_operation_modify_common_views_group", SONET_ROLES_MODERATOR);
		}
	}
}

if ($bIntranet)
{
	if (COption::GetOptionString("socialnetwork", "allow_files_user", "Y") == "Y" || COption::GetOptionString("socialnetwork", "allow_files_group", "Y") == "Y")
	{
		$arSocNetFeaturesSettings["files"] = array(
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
			$arSocNetFeaturesSettings["files"]["operations"]["bizproc"] = array();

		$arSocNetFeaturesSettings["files"]["operations"]["write"] = array();

		if (COption::GetOptionString("socialnetwork", "allow_files_user", "Y") == "Y")
		{
			$arSocNetFeaturesSettings["files"]["subscribe_events"]["files"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_USER] = array(
					"TITLE" => GetMessage("SOCNET_LOG_FILES_USER"),
					"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_FILES_USER_SETTINGS"),
					"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_FILES_USER_SETTINGS_1"),
					"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_FILES_USER_SETTINGS_2"),
				);

			$arSocNetFeaturesSettings["files"]["allowed"][] = SONET_ENTITY_USER;
			$arSocNetFeaturesSettings["files"]["operations"]["view"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_files_operation_view_user", (CSocNetUser::IsFriendsAllowed() ? SONET_RELATIONS_TYPE_FRIENDS : SONET_RELATIONS_TYPE_ALL));
			$arSocNetFeaturesSettings["files"]["operations"]["write_limited"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_files_operation_write_limited_user", SONET_RELATIONS_TYPE_NONE);
			$arSocNetFeaturesSettings["files"]["operations"]["write"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_files_operation_write_user", SONET_RELATIONS_TYPE_NONE);
		}

		if (COption::GetOptionString("socialnetwork", "allow_files_group", "Y") == "Y")
		{
			$arSocNetFeaturesSettings["files"]["subscribe_events"]["files"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP] = array(
					"TITLE" => GetMessage("SOCNET_LOG_FILES_GROUP"),
					"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_FILES_GROUP_SETTINGS"),
					"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_FILES_GROUP_SETTINGS_1"),
					"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_FILES_GROUP_SETTINGS_2"),
				);

			$arSocNetFeaturesSettings["files"]["allowed"][] = SONET_ENTITY_GROUP;
			$arSocNetFeaturesSettings["files"]["operations"]["view"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_files_operation_view_group", SONET_ROLES_USER);
			$arSocNetFeaturesSettings["files"]["operations"]["write_limited"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_files_operation_write_limited_group", SONET_ROLES_MODERATOR);
			$arSocNetFeaturesSettings["files"]["operations"]["write"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_files_operation_write_group", SONET_ROLES_MODERATOR);
		}
	}
}
if (COption::GetOptionString("socialnetwork", "allow_blog_user", "Y") == "Y" || COption::GetOptionString("socialnetwork", "allow_blog_group", "Y") == "Y")
{
	$arSocNetFeaturesSettings["blog"] = array(
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
		$arSocNetFeaturesSettings["blog"]["subscribe_events"]["blog"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_USER] = array(
				"TITLE" 			=> GetMessage("SOCNET_LOG_BLOG_USER"),
				"TITLE_SETTINGS"	=> GetMessage("SOCNET_LOG_BLOG_USER_SETTINGS"),
				"TITLE_SETTINGS_1"	=> GetMessage("SOCNET_LOG_BLOG_USER_SETTINGS_1"),
				"TITLE_SETTINGS_2"	=> GetMessage("SOCNET_LOG_BLOG_USER_SETTINGS_2"),
			);

		$arSocNetFeaturesSettings["blog"]["subscribe_events"]["blog_post"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_USER] = array(
				"TITLE" => GetMessage("SOCNET_LOG_BLOG_POST_USER")
			);

		$arSocNetFeaturesSettings["blog"]["allowed"][] = SONET_ENTITY_USER;
		$arSocNetFeaturesSettings["blog"]["operations"]["view_post"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_blog_operation_view_post_user", SONET_RELATIONS_TYPE_ALL);
		//$arSocNetFeaturesSettings["blog"]["operations"]["premoderate_post"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_blog_operation_premoderate_post_user", SONET_RELATIONS_TYPE_NONE);
		//$arSocNetFeaturesSettings["blog"]["operations"]["write_post"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_blog_operation_write_post_user", SONET_RELATIONS_TYPE_NONE);
		//$arSocNetFeaturesSettings["blog"]["operations"]["moderate_post"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_blog_operation_moderate_post_user", SONET_RELATIONS_TYPE_NONE);
		//$arSocNetFeaturesSettings["blog"]["operations"]["full_post"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_blog_operation_full_post_user", SONET_RELATIONS_TYPE_NONE);
		$arSocNetFeaturesSettings["blog"]["operations"]["view_comment"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_blog_operation_view_comment_user", SONET_RELATIONS_TYPE_ALL);
		$arSocNetFeaturesSettings["blog"]["operations"]["premoderate_comment"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_blog_operation_premoderate_comment_user", SONET_RELATIONS_TYPE_AUTHORIZED);
		$arSocNetFeaturesSettings["blog"]["operations"]["write_comment"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_blog_operation_write_comment_user", SONET_RELATIONS_TYPE_AUTHORIZED);
		//$arSocNetFeaturesSettings["blog"]["operations"]["moderate_comment"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_blog_operation_moderate_comment_user", SONET_RELATIONS_TYPE_NONE);
		//$arSocNetFeaturesSettings["blog"]["operations"]["full_comment"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_blog_operation_full_comment_user", SONET_RELATIONS_TYPE_NONE);

		//$arSocNetFeaturesSettings["blog"]["operations"]["write_post"]["restricted"][SONET_ENTITY_USER] = array(SONET_RELATIONS_TYPE_ALL);
		//$arSocNetFeaturesSettings["blog"]["operations"]["premoderate_post"]["restricted"][SONET_ENTITY_USER] = array(SONET_RELATIONS_TYPE_ALL);
		//$arSocNetFeaturesSettings["blog"]["operations"]["moderate_post"]["restricted"][SONET_ENTITY_USER] = array(SONET_RELATIONS_TYPE_ALL);
		//$arSocNetFeaturesSettings["blog"]["operations"]["full_post"]["restricted"][SONET_ENTITY_USER] = array(SONET_RELATIONS_TYPE_ALL);
		//$arSocNetFeaturesSettings["blog"]["operations"]["moderate_comment"]["restricted"][SONET_ENTITY_USER] = array(SONET_RELATIONS_TYPE_ALL);
		//$arSocNetFeaturesSettings["blog"]["operations"]["full_comment"]["restricted"][SONET_ENTITY_USER] = array(SONET_RELATIONS_TYPE_ALL);
	}

	if (COption::GetOptionString("socialnetwork", "allow_blog_group", "Y") == "Y")
	{
		$arSocNetFeaturesSettings["blog"]["subscribe_events"]["blog"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP] = array(
				"TITLE" 			=> GetMessage("SOCNET_LOG_BLOG_GROUP"),
				"TITLE_SETTINGS"	=> GetMessage("SOCNET_LOG_BLOG_GROUP_SETTINGS"),
				"TITLE_SETTINGS_1"	=> GetMessage("SOCNET_LOG_BLOG_GROUP_SETTINGS_1"),
				"TITLE_SETTINGS_2"	=> GetMessage("SOCNET_LOG_BLOG_GROUP_SETTINGS_2"),
			);

		$arSocNetFeaturesSettings["blog"]["subscribe_events"]["blog_post"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP] = array(
				"TITLE" => GetMessage("SOCNET_LOG_BLOG_POST_GROUP")
			);

		$arSocNetFeaturesSettings["blog"]["allowed"][] = SONET_ENTITY_GROUP;
		$arSocNetFeaturesSettings["blog"]["operations"]["view_post"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_view_post_group", SONET_ROLES_USER);
		$arSocNetFeaturesSettings["blog"]["operations"]["premoderate_post"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_premoderate_post_group", SONET_ROLES_USER);
		$arSocNetFeaturesSettings["blog"]["operations"]["write_post"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_write_post_group", SONET_ROLES_USER);
		$arSocNetFeaturesSettings["blog"]["operations"]["moderate_post"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_moderate_post_group", SONET_ROLES_MODERATOR);
		$arSocNetFeaturesSettings["blog"]["operations"]["full_post"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_full_post_group", SONET_ROLES_OWNER);
		$arSocNetFeaturesSettings["blog"]["operations"]["view_comment"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_view_comment_group", SONET_ROLES_USER);
		$arSocNetFeaturesSettings["blog"]["operations"]["premoderate_comment"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_premoderate_comment_group", SONET_ROLES_USER);
		$arSocNetFeaturesSettings["blog"]["operations"]["write_comment"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_write_comment_group", SONET_ROLES_USER);
		$arSocNetFeaturesSettings["blog"]["operations"]["moderate_comment"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_moderate_comment_group", SONET_ROLES_MODERATOR);
		$arSocNetFeaturesSettings["blog"]["operations"]["full_comment"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_full_comment_group", SONET_ROLES_MODERATOR);

		$arSocNetFeaturesSettings["blog"]["operations"]["write_post"]["restricted"][SONET_ENTITY_GROUP] = array(SONET_ROLES_ALL);
		$arSocNetFeaturesSettings["blog"]["operations"]["premoderate_post"]["restricted"][SONET_ENTITY_GROUP] = array(SONET_ROLES_ALL);
		$arSocNetFeaturesSettings["blog"]["operations"]["moderate_post"]["restricted"][SONET_ENTITY_GROUP] = array(SONET_ROLES_ALL);
		$arSocNetFeaturesSettings["blog"]["operations"]["full_post"]["restricted"][SONET_ENTITY_GROUP] = array(SONET_ROLES_ALL);
		$arSocNetFeaturesSettings["blog"]["operations"]["moderate_comment"]["restricted"][SONET_ENTITY_GROUP] = array(SONET_ROLES_ALL);
		$arSocNetFeaturesSettings["blog"]["operations"]["full_comment"]["restricted"][SONET_ENTITY_GROUP] = array(SONET_ROLES_ALL);
	}
	$arSocNetFeaturesSettings["blog"]["subscribe_events"]["blog_post_important"] = $arSocNetFeaturesSettings["blog"]["subscribe_events"]["blog_post"];
}
/*
if (COption::GetOptionString("socialnetwork", "allow_microblog_user", "Y") == "Y" || COption::GetOptionString("socialnetwork", "allow_microblog_group", "Y") == "Y")
{
	$arSocNetFeaturesSettings["microblog"] = array(
			"allowed" => array(),
			"minoperation" => array("view_post"),
			"operations" => array("view_post" => Array()),
			"subscribe_events" 			=> array(
				"blog_post_micro" =>  array(
					"ENTITIES"		=> array(),
					"OPERATION"		=> "view_post",
					"CLASS_FORMAT"	=> "CSocNetLogTools",
					"METHOD_FORMAT"	=> "FormatEvent_Microblog",
					"HAS_CB"		=> "Y",
					"FULL_SET"	=> array("blog_post_micro", "blog_comment_micro"),
					"COMMENT_EVENT"	=> array(
						"EVENT_ID"		=> "blog_comment_micro",
						"OPERATION"		=> "view_post",
						"ADD_CALLBACK"	=> array("CSocNetLogTools", "AddComment_Microblog"),
						"CLASS_FORMAT"	=> "CSocNetLogTools",
						"METHOD_FORMAT"	=> "FormatComment_Microblog"
					)
				),
			)
		);
	if (COption::GetOptionString("socialnetwork", "allow_microblog_user", "Y") == "Y")
	{
		$arSocNetFeaturesSettings["microblog"]["subscribe_events"]["blog_post_micro"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_USER] = array(
				"TITLE" => GetMessage("SOCNET_LOG_MICROBLOG_USER"),
				"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_MICROBLOG_USER_SETTINGS"),
				"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_MICROBLOG_USER_SETTINGS"),
				"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_MICROBLOG_USER_SETTINGS"),
			);

		$arSocNetFeaturesSettings["microblog"]["allowed"][] = SONET_ENTITY_USER;
		$arSocNetFeaturesSettings["microblog"]["operations"]["view_post"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_blog_operation_view_post_user", SONET_RELATIONS_TYPE_ALL);
	}

	if (COption::GetOptionString("socialnetwork", "allow_microblog_group", "Y") == "Y")
	{
		$arSocNetFeaturesSettings["microblog"]["subscribe_events"]["blog_post_micro"]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP] = array(
				"TITLE" => GetMessage("SOCNET_LOG_MICROBLOG_GROUP"),
				"TITLE_SETTINGS" => GetMessage("SOCNET_LOG_MICROBLOG_GROUP_SETTINGS"),
				"TITLE_SETTINGS_1" => GetMessage("SOCNET_LOG_MICROBLOG_GROUP_SETTINGS"),
				"TITLE_SETTINGS_2" => GetMessage("SOCNET_LOG_MICROBLOG_GROUP_SETTINGS"),
			);

		$arSocNetFeaturesSettings["microblog"]["allowed"][] = SONET_ENTITY_GROUP;
		$arSocNetFeaturesSettings["microblog"]["operations"]["view_post"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_blog_operation_view_post_group", SONET_ROLES_USER);
	}
}
*/

if (IsModuleInstalled('search'))
{
	if (COption::GetOptionString("socialnetwork", "allow_search_user", "N") == "Y" || COption::GetOptionString("socialnetwork", "allow_search_group", "Y") == "Y")
	{
		$arSocNetFeaturesSettings["search"] = array(
			"allowed" 		=> array(),
			"operations" 	=> array(),
			"minoperation"	=> array(),
		);

		if (COption::GetOptionString("socialnetwork", "allow_search_user", "N") == "Y")
		{
			$arSocNetFeaturesSettings["search"]["allowed"][] = SONET_ENTITY_USER;
			$arSocNetFeaturesSettings["search"]["operations"]["view"][SONET_ENTITY_USER] = COption::GetOptionString("socialnetwork", "default_search_operation_view_user", SONET_RELATIONS_TYPE_ALL);
		}

		if (COption::GetOptionString("socialnetwork", "allow_search_group", "Y") == "Y")
		{
			$arSocNetFeaturesSettings["search"]["allowed"][] = SONET_ENTITY_GROUP;
			$arSocNetFeaturesSettings["search"]["operations"]["view"][SONET_ENTITY_GROUP] = COption::GetOptionString("socialnetwork", "default_search_operation_view_group", SONET_ROLES_USER);
		}
	}
}

$events = GetModuleEvents("socialnetwork", "OnFillSocNetFeaturesList");
while ($arEvent = $events->Fetch())
	ExecuteModuleEventEx($arEvent, array(&$arSocNetFeaturesSettings));

global $arSocNetLogEvents;
$arSocNetLogEvents = array(
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

$events = GetModuleEvents("socialnetwork", "OnFillSocNetLogEvents");
while ($arEvent = $events->Fetch())
	ExecuteModuleEventEx($arEvent, array(&$arSocNetLogEvents));

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
	unset($arSocNetAllowedSubscribeEntityTypes[0]);

	foreach ($arSocNetLogEvents as $event_id_tmp => $arEventTmp)
		if (
			array_key_exists("ENTITIES", $arEventTmp)
			&& array_key_exists(SONET_SUBSCRIBE_ENTITY_GROUP, $arEventTmp["ENTITIES"])
		)
			unset($arSocNetLogEvents[$event_id_tmp]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP]);

	unset($arSocNetLogEvents["system_groups"]);
	foreach($arSocNetLogEvents["system"]["FULL_SET"] as $i => $event_id_tmp)
		if ($event_id_tmp == "system_groups")
			unset($arSocNetLogEvents["system"]["FULL_SET"][$i]);

	foreach ($arSocNetFeaturesSettings as $feature_id_tmp => $arFeatureTmp)
		if (array_key_exists("subscribe_events", $arFeatureTmp))
			foreach ($arFeatureTmp["subscribe_events"] as $event_id_tmp => $arEventTmp)
				if (
					array_key_exists("ENTITIES", $arEventTmp)
					&& array_key_exists(SONET_SUBSCRIBE_ENTITY_GROUP, $arEventTmp["ENTITIES"])
				)
					unset($arSocNetFeaturesSettings[$feature_id_tmp]["subscribe_events"][$event_id_tmp]["ENTITIES"][SONET_SUBSCRIBE_ENTITY_GROUP]);
}

if (!CBXFeatures::IsFeatureEnabled("Friends"))
{
	unset($arSocNetLogEvents["system_friends"]);
	foreach($arSocNetLogEvents["system"]["FULL_SET"] as $i => $event_id_tmp)
		if ($event_id_tmp == "system_friends")
			unset($arSocNetLogEvents["system"]["FULL_SET"][$i]);
	$arSocNetAllowedSubscribeEntityTypesDesc[SONET_SUBSCRIBE_ENTITY_USER]["HAS_MY"] = "N";
}

if (!defined("CACHED_b_sonet_group_subjects"))
	// define("CACHED_b_sonet_group_subjects", 3600);

class CSocNetUpdater
{
	public static function Run($version)
	{
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/updtr".$version.".php");
	}
}

?>