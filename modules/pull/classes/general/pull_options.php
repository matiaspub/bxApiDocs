<?
class CPullOptions
{
	public static function CheckNeedRun($bGetSectionStatus = true)
	{
		$arExcludeSites = CPullOptions::GetExcludeSites();
		if (isset($arExcludeSites[SITE_ID]))
			return false;

		global $CACHE_MANAGER;

		$bAdminSection = false;
		if(defined("ADMIN_SECTION") && ADMIN_SECTION == true)
			$bAdminSection = true;

		$arResult = Array();
		$res = $CACHE_MANAGER->Read(2592000, "pull_cnr");
		if ($res)
			$arResult = $CACHE_MANAGER->Get("pull_cnr");

		if(!$res)
		{
			$arResult = Array(
				'ADMIN_SECTION' => false,
				'PUBLIC_SECTION' => false
			);

			$arModule = self::GetDependentModule();
			foreach ($arModule as $moduleId => $options)
			{
				if (isset($options['ADMIN_SECTION']) && $options['ADMIN_SECTION'] == 'Y')
					$arResult['ADMIN_SECTION'] = true;
				if (isset($options['PUBLIC_SECTION']) && $options['PUBLIC_SECTION'] == 'Y')
					$arResult['PUBLIC_SECTION'] = true;
			}

			$CACHE_MANAGER->Set("pull_cnr", $arResult);
		}

		return $bGetSectionStatus? $arResult[$bAdminSection? 'ADMIN_SECTION': 'PUBLIC_SECTION']: $arResult;
	}

	public static function ModuleEnable()
	{
		$arResult = self::CheckNeedRun(false);
		return ($arResult['ADMIN_SECTION'] || $arResult['PUBLIC_SECTION'])? true: false;
	}

	public static function GetDependentModule()
	{
		$arModule = Array();
		foreach(GetModuleEvents("pull", "OnGetDependentModule", true) as $arEvent)
		{
			$ar = ExecuteModuleEventEx($arEvent);
			if (isset($ar['MODULE_ID']))
			{
				$arModule[$ar['MODULE_ID']] = Array(
					'MODULE_ID' => $ar['MODULE_ID'],
					'ADMIN_SECTION' => isset($ar['USE']) && in_array('ADMIN_SECTION', $ar['USE'])? true: false,
					'PUBLIC_SECTION' => isset($ar['USE']) && in_array('PUBLIC_SECTION', $ar['USE'])? true: false,
				);
			}
		}

		return $arModule;
	}

	public static function GetExcludeSites()
	{
		$result = COption::GetOptionString("pull", "exclude_sites", "a:0:{}");
		return unserialize($result);
	}

	public static function SetExcludeSites($sites)
	{
		if (!is_array($sites))
			return false;

		COption::SetOptionString("pull", "exclude_sites", serialize($sites));

		return true;
	}

	/*
	 * @deprecated No longer used by internal code and not recommended. Use CPullOptions::GetQueueServerStatus()
	 */
	public static function GetNginxStatus()
	{
		return self::GetQueueServerStatus();
	}
	public static function GetQueueServerStatus()
	{
		$result = COption::GetOptionString("pull", "nginx");
		return $result == 'N'? false: true;
	}
	public static function GetQueueServerHeaders()
	{
		$result = COption::GetOptionString("pull", "nginx_headers");
		return $result == 'Y'? true: false;
	}

	/*
	 * @deprecated No longer used by internal code and not recommended. Use CPullOptions::SetQueueServerStatus()
	 */
	public static function SetNginxStatus($flag = "N")
	{
		return self::SetQueueServerStatus($flag);
	}
	public static function SetQueueServerStatus($flag = "N")
	{
		COption::SetOptionString("pull", "nginx", $flag=='Y'?'Y':'N');

		if ($flag=='Y')
		{
			CAgent::AddAgent("CPullChannel::CheckOnlineChannel();", "pull", "N", 100, "", "Y", ConvertTimeStamp(time()+CTimeZone::GetOffset()+100, "FULL"));
			CAgent::RemoveAgent("CPullStack::CheckExpireAgent();", "pull");
		}
		else
		{
			CAgent::RemoveAgent("CPullChannel::CheckOnlineChannel();", "pull");
			CAgent::AddAgent("CPullStack::CheckExpireAgent();", "pull", "N");
		}

		return true;
	}

	public static function SetQueueServerHeaders($flag = "Y")
	{
		COption::SetOptionString("pull", "nginx_headers", $flag=='Y'?'Y':'N');
		return true;
	}
	public static function GetPushStatus()
	{
		$result = COption::GetOptionString("pull", "push");
		return $result == 'N'? false: true;
	}

	public static function SetPushStatus($flag = "N")
	{
		COption::SetOptionString("pull", "push", $flag=='Y'?'Y':'N');
		if ($flag == 'Y')
			CAgent::AddAgent("CPushManager::SendAgent();", "pull", "N", 30);
		else
			CAgent::RemoveAgent("CPushManager::SendAgent();", "pull");

		return true;
	}

	public static function GetPublishUrl($channelId = "")
	{
		$url = COption::GetOptionString("pull", "path_to_publish").(strlen($channelId)>0?'?CHANNEL_ID='.$channelId:'');
		return $url;
	}

	public static function SetPublishUrl($path = "")
	{
		if (strlen($path)<=0)
		{
			include($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/pull/default_option.php');
			$path = $pull_default_option["path_to_publish"];
		}

		COption::SetOptionString("pull", "path_to_publish", $path);
		return true;
	}

	public static function GetListenUrl($channelId = "", $mobile = false, $modern = false)
	{
		if (!is_array($channelId) && strlen($channelId) > 0)
			$channelId = Array($channelId);
		else if (!is_array($channelId))
			$channelId = Array();

		$url = COption::GetOptionString("pull", "path_to_".($modern? 'modern_': ($mobile? 'mobile_':''))."listener").(count($channelId)>0?'?CHANNEL_ID='.implode('/', $channelId):'');
		$url = str_replace('#PORT#', self::GetQueueServerVersion()>1? '': ':8893', $url);

		return $url;
	}

	public static function SetListenUrl($path = "", $mobile = false, $modern = false)
	{
		$pathValue = $path;

		if ($modern)
		{
			$pathName = "path_to_modern_listener";
		}
		else if ($mobile)
		{
			$pathName = "path_to_mobile_listener";
		}
		else
		{
			$pathName = "path_to_listener";
		}

		if (strlen($path)<=0)
		{
			include($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/pull/default_option.php');
			$pathValue = $pull_default_option[$pathName];
		}

		COption::SetOptionString("pull", $pathName, $pathValue);
		return true;
	}

	public static function GetListenSecureUrl($channelId = "", $mobile = false, $modern = false)
	{
		if (!is_array($channelId) && strlen($channelId) > 0)
			$channelId = Array($channelId);
		else if (!is_array($channelId))
			$channelId = Array();

		$url = COption::GetOptionString("pull", "path_to_".($modern? 'modern_': ($mobile? 'mobile_':''))."listener_secure").(count($channelId)>0?'?CHANNEL_ID='.implode('/', $channelId):'');
		$url = str_replace('#PORT#', self::GetQueueServerVersion()>1? '': ':8894', $url);

		return $url;
	}

	public static function SetListenSecureUrl($path = "", $mobile = false, $modern = false)
	{
		$pathValue = $path;

		if ($modern)
		{
			$pathName = "path_to_modern_listener_secure";
		}
		else if ($mobile)
		{
			$pathName = "path_to_mobile_listener_secure";
		}
		else
		{
			$pathName = "path_to_listener_secure";
		}

		if (strlen($path)<=0)
		{
			include($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/pull/default_option.php');
			$pathValue = $pull_default_option[$pathName];
		}

		COption::SetOptionString("pull", $pathName, $pathValue);
		return true;
	}

	/*
	 * Get version of QueueServer
	 * 1 version - nginx-push-stream-module 0.3.4
	 * 2 version - nginx-push-stream-module 0.4.0
	 */
	public static function GetQueueServerVersion()
	{
		return intval(COption::GetOptionInt("pull", "nginx_version"));
	}

	public static function SetQueueServerVersion($version)
	{
		COption::SetOptionInt("pull", "nginx_version", intval($version));

		return true;
	}

	public static function GetCommandPerHit()
	{
		return intval(COption::GetOptionInt("pull", "nginx_command_per_hit"));
	}

	public static function SetCommandPerHit($count)
	{
		COption::SetOptionInt("pull", "nginx_command_per_hit", intval($count));

		return true;
	}

	public static function GetWebSocketStatus()
	{
		return self::GetWebSocket() && self::GetQueueServerVersion()>1? true: false;
	}

	public static function GetWebSocket()
	{
		return COption::GetOptionString("pull", "websocket") == 'Y'? true: false;
	}

	public static function SetWebSocket($flag = "N")
	{
		COption::SetOptionString("pull", "websocket", $flag=='Y'?'Y':'N');
		return true;
	}

	public static function GetWebSocketUrl($channelId = "")
	{
		if (!is_array($channelId) && strlen($channelId) > 0)
			$channelId = Array($channelId);
		else if (!is_array($channelId))
			$channelId = Array();

		$url = COption::GetOptionString("pull", "path_to_websocket").(count($channelId)>0?'?CHANNEL_ID='.implode('/', $channelId):'');
		return $url;
	}

	public static function SetWebSocketUrl($path = "")
	{
		if (strlen($path)<=0)
		{
			include($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/pull/default_option.php');
			$path = $pull_default_option["path_to_websocket"];
		}

		COption::SetOptionString("pull", "path_to_websocket", $path);
		return true;
	}

	public static function GetWebSocketSecureUrl($channelId = "")
	{
		if (!is_array($channelId) && strlen($channelId) > 0)
			$channelId = Array($channelId);
		else if (!is_array($channelId))
			$channelId = Array();

		$url = COption::GetOptionString("pull", "path_to_websocket_secure").(count($channelId)>0?'?CHANNEL_ID='.implode('/', $channelId):'');
		return $url;
	}

	public static function SetWebSocketSecureUrl($path = "")
	{
		if (strlen($path)<=0)
		{
			include($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/pull/default_option.php');
			$path = $pull_default_option["path_to_websocket_secure"];
		}

		COption::SetOptionString("pull", "path_to_websocket_secure", $path);
		return true;
	}

	/* UTILITY */

	public static function SendConfigDie()
	{
		$arMessage = Array(
			'module_id' => 'pull',
			'command' => 'config_die',
			'params' => ''
		);
		CPullStack::AddBroadcast($arMessage);
	}

	public static function ClearCheckCache()
	{
		// init module cache
		$CModule = new CModule();
		$CModule->IsInstalled();

		CAgent::RemoveAgent("CPullOptions::ClearAgent();", "pull");
		CAgent::AddAgent("CPullOptions::ClearAgent();", "pull", "N", 30, "", "Y", ConvertTimeStamp(time()+CTimeZone::GetOffset()+30, "FULL"));
	}

	public static function ClearAgent()
	{
		global $CACHE_MANAGER;
		$CACHE_MANAGER->Clean("pull_cnr");

		if (self::ModuleEnable())
		{
			CAgent::AddAgent("CPullChannel::CheckOnlineChannel();", "pull", "N", 100, "", "Y", ConvertTimeStamp(time()+CTimeZone::GetOffset()+100, "FULL"));
			CAgent::AddAgent("CPullChannel::CheckExpireAgent();", "pull", "N", 43200, "", "Y", ConvertTimeStamp(time()+CTimeZone::GetOffset() + 43200, "FULL"));
			CAgent::AddAgent("CPullStack::CheckExpireAgent();", "pull", "N", 86400, "", "Y", ConvertTimeStamp(time()+CTimeZone::GetOffset() + 86400, "FULL"));
			CAgent::AddAgent("CPullWatch::CheckExpireAgent();", "pull", "N", 600, "", "Y", ConvertTimeStamp(time()+CTimeZone::GetOffset() + 600, "FULL"));
		}
		else
		{
			CAgent::RemoveAgent("CPullChannel::CheckOnlineChannel();", "pull");
			CAgent::RemoveAgent("CPullChannel::CheckExpireAgent();", "pull");
			CAgent::RemoveAgent("CPullStack::CheckExpireAgent();", "pull");
			CAgent::RemoveAgent("CPullWatch::CheckExpireAgent();", "pull");
			CAgent::RemoveAgent("CPushManager::SendAgent();", "pull");
		}
	}

	public static function OnEpilog()
	{
		$userId = 0;
		if (defined('PULL_USER_ID'))
		{
			$userId = PULL_USER_ID;
		}
		else if ($GLOBALS['USER'] && intval($GLOBALS['USER']->GetID()) > 0)
		{
			$userId = intval($GLOBALS['USER']->GetID());
		}

		if (!defined('BX_PULL_SKIP_INIT') && !(isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y') && $userId != 0 && CModule::IncludeModule('pull'))
		{
			// define("BX_PULL_SKIP_INIT", true);

			if (CPullOptions::CheckNeedRun())
			{
				CJSCore::Init(array('pull'));

				$pullConfig = CPullChannel::GetConfig($userId);

				global $APPLICATION;
				$APPLICATION->AddAdditionalJS('<script type="text/javascript">BX.bind(window, "load", function() { BX.PULL.start('.(empty($pullConfig)? '': CUtil::PhpToJsObject($pullConfig)).'); });</script>');
			}
		}
	}
}