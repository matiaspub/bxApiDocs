<?
IncludeModuleLangFile(__FILE__);

// define("IM_REVISION", 30);

// define("IM_MESSAGE_SYSTEM", "S");
// define("IM_MESSAGE_PRIVATE", "P");
// define("IM_MESSAGE_GROUP", "G");

// define("IM_NOTIFY_CONFIRM", 1);
// define("IM_NOTIFY_FROM", 2);
// define("IM_NOTIFY_SYSTEM", 4);

// define("IM_STATUS_UNREAD", 0);
// define("IM_STATUS_NOTIFY", 1);
// define("IM_STATUS_READ", 2);

// define("IM_CALL_NONE", 0);
// define("IM_CALL_VIDEO", 1);
// define("IM_CALL_AUDIO", 2);

// define("IM_CALL_STATUS_NONE", 0);
// define("IM_CALL_STATUS_WAIT", 1);
// define("IM_CALL_STATUS_ANSWER", 2);
// define("IM_CALL_STATUS_DECLINE", 3);

// define("IM_CALL_END_BUSY", 'busy');
// define("IM_CALL_END_DECLINE", 'decline');
// define("IM_CALL_END_TIMEOUT", 'waitTimeout');
// define("IM_CALL_END_ACCESS", 'errorAccess');
// define("IM_CALL_END_OFFLINE", 'errorOffline');


// define("IM_SPEED_NOTIFY", 1);
// define("IM_SPEED_MESSAGE", 2);
// define("IM_SPEED_GROUP", 3);

// define("IM_FEATURE_DESKTOP", "DESKTOP");
// define("IM_FEATURE_XMPP", "XMPP");
// define("IM_FEATURE_MAIL", "MAIL");

global $DBType;

CModule::AddAutoloadClasses(
	"im",
	array(
		"CIMSettings" => "classes/general/im_settings.php",
		"CIMMessenger" => "classes/general/im_messenger.php",
		"CIMNotify" => "classes/general/im_notify.php",
		"CIMContactList" => "classes/".$DBType."/im_contact_list.php",
		"CIMChat" => "classes/general/im_chat.php",
		"CIMMessage" => "classes/general/im_message.php",
		"CIMHistory" => "classes/general/im_history.php",
		"CIMEvent" => "classes/general/im_event.php",
		"CIMCall" => "classes/general/im_call.php",
		"CIMMail" => "classes/general/im_mail.php",
		"CIMConvert" => "classes/general/im_convert.php",
		"CIMTableSchema" => "classes/general/im_table_schema.php",
		"CIMNotifySchema" => "classes/general/im_notify_schema.php",
		"CIMRestService" => "classes/general/im_rest.php",
	)
);

$jsCoreRel = array('popup', 'ajax', 'fx', 'ls', 'date', 'json', 'webrtc');
if (CModule::IncludeModule('voximplant'))
	$jsCoreRel[] = 'voximplant';

CJSCore::RegisterExt('im', array(
	'js' => '/bitrix/js/im/im.js',
	'css' => '/bitrix/js/im/css/messenger.css',
	'lang' => '/bitrix/modules/im/lang/'.LANGUAGE_ID.'/js_im.php',
	'rel' => $jsCoreRel
));

CJSCore::RegisterExt('desktop', array(
	'js' => '/bitrix/js/im/desktop.js',
	'css' => '/bitrix/js/im/css/desktop.css',
	'lang' => '/bitrix/modules/im/lang/'.LANGUAGE_ID.'/js_desktop.php',
	'rel' => array('popup', 'ajax', 'fx', 'ls', 'date', 'json')
));
?>