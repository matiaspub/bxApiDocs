<?
// define('STOP_STATISTICS', true);
// define('NO_AGENT_CHECK', true);
// define('DisableEventsCheck', true);
// define('BX_SECURITY_SHOW_MESSAGE', true);
// define("PUBLIC_AJAX_MODE", true);
// define("NOT_CHECK_PERMISSIONS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
IncludeModuleLangFile(__FILE__);
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$userId = $USER->GetID();

if (!CModule::IncludeModule("socialservices"))
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'SS_MODULE_NOT_INSTALLED'));
	die();
}
if (intval($userId) <= 0)
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'AUTHORIZE_ERROR'));
	die();
}

if (check_bitrix_sessid())
{
	CUtil::JSPostUnescape();
	if($_REQUEST['action'] == "getuserdata" || $_REQUEST['action'] == 'getsettings')
	{
		$serializedSocservUser = CUserOptions::GetOption("socialservices", "user_socserv_array", '', $userId);
		if(CheckSerializedData($serializedSocservUser))
			$arResult['SOCSERVARRAY'] = unserialize($serializedSocservUser);
		if(!isset($arResult['SOCSERVARRAY']) || !is_array($arResult['SOCSERVARRAY']))
			$arResult['SOCSERVARRAY'] = '';
		if($_REQUEST['checkEnabled'] == 'true')
			$arResult['ENABLED'] = CUserOptions::GetOption("socialservices", "user_socserv_enable", "N", $userId);
		$arResult['STARTSEND'] = CUserOptions::GetOption("socialservices", "user_socserv_start_day", "N", $userId);
		$arResult['ENDSEND'] = CUserOptions::GetOption("socialservices", "user_socserv_end_day", "N", $userId);
		$arResult['STARTTEXT'] = CUserOptions::GetOption("socialservices", "user_socserv_start_text", GetMessage("JS_CORE_SS_WORKDAY_START"), $userId);
		$arResult['ENDTEXT'] = CUserOptions::GetOption("socialservices", "user_socserv_end_text", GetMessage("JS_CORE_SS_WORKDAY_END"), $userId);
		$arResult['SOCSERVARRAYALL'] = CSocServAuthManager::GetUserArrayForSendMessages($userId);
		$arResult['USER_ID'] = $userId;
		$tooltipPathToUser = COption::GetOptionString("main", "TOOLTIP_PATH_TO_USER", false, SITE_ID);
		if($tooltipPathToUser)
			$pathToUser = str_replace("#user_id#", $userId, $tooltipPathToUser)."edit/?current_fieldset=SOCSERV#soc-serv-title-id";
		else
			$pathToUser = "/company/personal/user/$userId/edit/?current_fieldset=SOCSERV#soc-serv-title-id";
		$arResult["SETUP_MESSAGE"] = GetMessage(("JS_CORE_SS_SETUP_ACCOUNT"), array("#class#" => "class=\"bx-ss-soc-serv-setup-link\"", "#link#" => $pathToUser));

		if($_REQUEST['action'] == "getuserdata")
		{
			echo CUtil::PhpToJSObject($arResult);
		}
		else
		{
			$t = filemtime($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/js/socialservices/ss_timeman.js");

?>
BX.loadCSS('/bitrix/js/socialservices/css/ss.css');
BX.loadScript('/bitrix/js/socialservices/ss_timeman.js?<?=$t?>', function(){
	window.SOCSERV_DATA = <?=CUtil::PhpToJSObject($arResult);?>;
	BXTIMEMAN.WND.SOCSERV_WND = new BX.SocservTimeman();
	BXTIMEMAN.WND.SOCSERV_WND.showWnd();
});
<?
		}
	}
	elseif($_REQUEST['action'] == "saveuserdata")
	{
		if(isset($_POST["ENABLED"]))
		{
			$userSocServSendEnable = $_POST["ENABLED"];
			CUserOptions::SetOption("socialservices","user_socserv_enable",$userSocServSendEnable, false, $userId);
			$cache_id = 'socserv_user_option_'.$userId;
			$obCache = new CPHPCache;
			$cache_dir = '/bx/socserv_user_option';
			$obCache->Clean($cache_id, $cache_dir);
		}
		else
		{
			$arUserSocServ = '';
			$userSocServSendEnable = $userSocServSendStart = $userSocServSendEnd = 'N';
			$userSocServEndText = GetMessage("JS_CORE_SS_WORKDAY_END");
			$userSocServStartText = GetMessage("JS_CORE_SS_WORKDAY_START");
			if(isset($_POST["SOCSERVARRAY"]) && !empty($_POST["SOCSERVARRAY"]))
				$arUserSocServ = serialize($_POST["SOCSERVARRAY"]);
			if(isset($_POST["STARTSEND"]))
				$userSocServSendStart = $_POST["STARTSEND"];
			if(isset($_POST["ENDSEND"]))
				$userSocServSendEnd = $_POST["ENDSEND"];
			if(isset($_POST["STARTTEXT"]))
				$userSocServStartText = $_POST["STARTTEXT"];
			if(isset($_POST["ENDTEXT"]))
				$userSocServEndText = $_POST["ENDTEXT"];

			if($userSocServSendStart === 'Y' || $userSocServSendEnd === 'Y')
			{
				CUserOptions::SetOption("socialservices","user_socserv_enable", 'Y', false, $userId);
			}
			else
			{
				CUserOptions::SetOption("socialservices","user_socserv_enable", 'N', false, $userId);
			}
			CUserOptions::SetOption("socialservices","user_socserv_array",$arUserSocServ, false, $userId);
			CUserOptions::SetOption("socialservices","user_socserv_start_day",$userSocServSendStart, false, $userId);
			CUserOptions::SetOption("socialservices","user_socserv_end_day",$userSocServSendEnd, false, $userId);
			CUserOptions::SetOption("socialservices","user_socserv_start_text",$userSocServStartText, false, $userId);
			CUserOptions::SetOption("socialservices","user_socserv_end_text",$userSocServEndText, false, $userId);
		}
	}
	elseif($_REQUEST['action'] == "registernetwork")
	{
		$domain = ToLower(rtrim(trim($_REQUEST['url']), '/'));

		if(preg_match("/^http[s]{0,1}:\/\/[^\/]+/", $domain))
		{
			$res = CSocServBitrix24Net::registerSite($domain);
		}
		else
		{
			$res = array("error" => GetMessage("B24NET_REG_WRONG_URL"));
		}

		Header('Content-Type: application/json');
		echo \Bitrix\Main\Web\Json::encode($res);
	}
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>