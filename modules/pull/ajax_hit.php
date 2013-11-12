<?
if($_SERVER["REQUEST_METHOD"] == "POST" && array_key_exists("PULL_AJAX_CALL", $_REQUEST) && $_REQUEST["PULL_AJAX_CALL"] === "Y")
{
	$arResult = array();
	global $USER, $APPLICATION, $DB;
	include($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/pull.request/ajax.php");
	die();
}
else if (!defined('BX_PULL_SKIP_INIT') && !(isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
		&& intval($GLOBALS['USER']->GetID()) > 0 && CModule::IncludeModule('pull') && CPullOptions::CheckNeedRun())
{
	// define("BX_PULL_SKIP_INIT", true);
	CJSCore::Init(array('pull'));

	global $APPLICATION;

	$pullConfig = Array();

	if (defined('BX_PULL_SKIP_LS'))
		$pullConfig['LOCAL_STORAGE'] = 'N';

	$pullChannel = CPullChannel::Get($GLOBALS['USER']->GetId());
	if (is_array($pullChannel))
	{
		$pullWebSocketStatus = false;
		$pullNginxStatus = CPullOptions::GetNginxStatus();

		$pullChannels = Array($pullChannel['CHANNEL_ID']);
		if ($pullNginxStatus)
		{
			if (defined('BX_PULL_SKIP_WEBSOCKET'))
			{
				$pullWebSocketStatus = false;
				$pullConfig['WEBSOCKET'] = 'N';
			}
			else
				$pullWebSocketStatus = CPullOptions::GetWebSocketStatus();

			$pullChannelShared = CPullChannel::GetShared();
			if (is_array($pullChannelShared))
			{
				$pullChannels[] = $pullChannelShared['CHANNEL_ID'];
				if ($pullChannel['CHANNEL_DT'] > $pullChannelShared['CHANNEL_DT'])
					$pullChannel['CHANNEL_DT'] = $pullChannelShared['CHANNEL_DT'];
			}
		}

		if (defined('BX_PULL_MOBILE'))
		{
			$pullConfig['MOBILE'] = 'Y';
			$pullPath = ($pullNginxStatus? (CMain::IsHTTPS()? CPullOptions::GetListenSecureUrl($pullChannels, true): CPullOptions::GetListenUrl($pullChannels, true)): '/bitrix/components/bitrix/pull.request/ajax.php?UPDATE_STATE');
		}
		else
			$pullPath = ($pullNginxStatus? (CMain::IsHTTPS()? CPullOptions::GetListenSecureUrl($pullChannels): CPullOptions::GetListenUrl($pullChannels)): '/bitrix/components/bitrix/pull.request/ajax.php?UPDATE_STATE');

		$pullConfig = $pullConfig+Array(
			'CHANNEL_ID' => implode('/', $pullChannels),
			'LAST_ID' => $pullChannel['LAST_ID'],
			'CHANNEL_DT' => $pullChannel['CHANNEL_DT'],
			'PATH' => $pullPath,
			'PATH_WS' => ($pullNginxStatus && $pullWebSocketStatus? (CMain::IsHTTPS()? CPullOptions::GetWebSocketSecureUrl($pullChannels): CPullOptions::GetWebSocketUrl($pullChannels)): ''),
			'METHOD' => ($pullNginxStatus? 'LONG': 'PULL'),
		);
	}

	$APPLICATION->AddAdditionalJS('<script type="text/javascript">BX.bind(window, "load", function() { BX.PULL.start('.(empty($pullConfig)? '': CUtil::PhpToJsObject($pullConfig)).'); });</script>');
}
?>
