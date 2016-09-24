<?
if(!CModule::IncludeModule('iblock'))
	return false;

if(!defined("CACHED_b_lists_permission")) // define("CACHED_b_lists_permission", 36000);

// define('SONET_LISTS_NEW_POST_ENTITY', 'WF');

CModule::AddAutoloadClasses(
	"lists",
	array(
		"lists" => "install/index.php",
		"CListPermissions" => "classes/general/listperm.php",
		"CLists" => "classes/general/lists.php",
		"CList" => "classes/general/list.php",
		"CListFieldTypeList" => "classes/general/listfieldtypes.php",
		"CListFieldType" => "classes/general/listfieldtype.php",
		"CListField" => "classes/general/listfield.php",
		"CListFieldList" => "classes/general/listfields.php",
		"CListElementField" => "classes/general/listfield.php",
		"CListPropertyField" => "classes/general/listfield.php",
		"CListFields" => "classes/general/listfields.php",
		"CListFile" => "classes/general/listfile.php",
		"CListsParameters" => "classes/general/parameters.php",
		"CListFileControl" => "classes/general/comp_lib.php",
		"CListsSocnet" => "classes/general/listsocnet.php",
		"CListsLiveFeed" => "lib/livefeed.php",
		"BizprocDocument" => "lib/bizprocdocument.php",

		"bitrix\\lists\\importer" => "lib/importer.php",
		"bitrix\\lists\\bizprocdocumentlists" => "lib/bizprocdocumentlists.php",
	)
);

CJSCore::RegisterExt('lists', array(
	'js' => '/bitrix/js/lists/js/lists.js',
	'css' => '/bitrix/js/lists/css/lists.css',
	'lang' => BX_ROOT.'/modules/lists/lang/'.LANGUAGE_ID.'/install/js/lists.php',
	'rel' => array('core', 'popup', 'json', 'ajax')
));
?>