<?
IncludeModuleLangFile(__FILE__);

// define("PULL_REVISION", 12);

global $APPLICATION, $DBType;

CModule::AddAutoloadClasses(
	"pull",
	array(
		"CPullChannel" => "classes/general/pull_channel.php",
		"CPullStack" => "classes/".$DBType."/pull_stack.php",
		"CPullWatch" => "classes/".$DBType."/pull_watch.php",
		"CPullOptions" => "classes/general/pull_options.php",
		"CPullTableSchema" => "classes/general/pull_table_schema.php",

		"CPullPush" => "classes/general/pull_push.php",
		"CPushMessage" => "classes/general/pushservices/push_message.php",
		"CPushService" => "classes/general/pushservices/push_service.php",
		"CPushManager" => "classes/general/pull_push.php",
		"CAppleMessage" => "classes/general/pushservices/apple_push.php",
		"CApplePush" => "classes/general/pushservices/apple_push.php",
		"CGoogleMessage" => "classes/general/pushservices/google_push.php",
		"CGooglePush" => "classes/general/pushservices/google_push.php",

	)
);

CJSCore::RegisterExt('pull', array(
	'js' => '/bitrix/js/pull/pull.js',
	'lang' => '/bitrix/modules/pull/lang/'.LANGUAGE_ID.'/js_pull.php',
	'rel' => defined('BX_PULL_SKIP_LS')? array('ajax'): array('ajax', 'ls')
));
CJSCore::RegisterExt('npull', array(
	'js' => '/bitrix/js/pull/npull.js',
	'rel' => defined('BX_PULL_SKIP_LS')? array('ajax'): array('ajax', 'ls')
));
?>
