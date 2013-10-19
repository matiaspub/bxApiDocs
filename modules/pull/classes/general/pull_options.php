<?
class CPullOptions
{
	public static function CheckNeedRun($bGetSectionStatus = true)
	{
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

	public static function SetNginxStatus($flag = "N")
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

	public static function SetPushStatus($flag = "N")
	{
		COption::SetOptionString("pull", "push", $flag=='Y'?'Y':'N');
		if ($flag == 'Y')
			CAgent::AddAgent("CPushManager::SendAgent();", "pull", "N", 30);
		else
			CAgent::RemoveAgent("CPushManager::SendAgent();", "pull");

		return true;
	}

	public static function SetPublishUrl($path = "")
	{
		if (strlen($path)<=0)
		{
			include_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/pull/default_option.php');
			$path = $pull_default_option["path_to_publish"];
		}

		COption::SetOptionString("pull", "path_to_publish", $path);
		return true;
	}

	public static function SetListenUrl($path = "")
	{
		if (strlen($path)<=0)
		{
			include_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/pull/default_option.php');
			$path = $pull_default_option["path_to_listener"];
		}

		COption::SetOptionString("pull", "path_to_listener", $path);
		return true;
	}

	public static function SetListenSecureUrl($path = "")
	{
		if (strlen($path)<=0)
		{
			include_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/pull/default_option.php');
			$path = $pull_default_option["path_to_listener_secure"];
		}

		COption::SetOptionString("pull", "path_to_listener_secure", $path);
		return true;
	}

	public static function SetWebSocketStatus($flag = "N")
	{
		COption::SetOptionString("pull", "websocket", $flag=='Y'?'Y':'N');

		if ($flag=='Y')
			CAgent::RemoveAgent("CPullStack::CheckExpireAgent();", "pull");
		else
			CAgent::AddAgent("CPullStack::CheckExpireAgent();", "pull", "N");

		return true;
	}

	public static function SetWebSocketUrl($path = "")
	{
		if (strlen($path)<=0)
		{
			include_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/pull/default_option.php');
			$path = $pull_default_option["path_to_websocket"];
		}

		COption::SetOptionString("pull", "path_to_websocket", $path);
		return true;
	}

	public static function SetWebSocketSecureUrl($path = "")
	{
		if (strlen($path)<=0)
		{
			include_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/pull/default_option.php');
			$path = $pull_default_option["path_to_websocket_secure"];
		}

		COption::SetOptionString("pull", "path_to_websocket_secure", $path);
		return true;
	}

	public static function GetNginxStatus()
	{
		$result = COption::GetOptionString("pull", "nginx", "N");
		return $result == 'N'? false: true;
	}

	public static function GetPushStatus()
	{
		$result = COption::GetOptionString("pull", "push", "N");
		return $result == 'N'? false: true;
	}

	public static function GetPublishUrl($channelId = "")
	{
		$url = COption::GetOptionString("pull", "path_to_publish").(strlen($channelId)>0?'?CHANNEL_ID='.$channelId:'');
		return $url;
	}

	public static function GetListenUrl($channelId = "")
	{
		if (!is_array($channelId) && strlen($channelId) > 0)
			$channelId = Array($channelId);
		else if (!is_array($channelId))
			$channelId = Array();

		$url = COption::GetOptionString("pull", "path_to_listener").(count($channelId)>0?'?CHANNEL_ID='.implode('/', $channelId):'');
		return $url;
	}

	public static function GetListenSecureUrl($channelId = "")
	{
		if (!is_array($channelId) && strlen($channelId) > 0)
			$channelId = Array($channelId);
		else if (!is_array($channelId))
			$channelId = Array();

		$url = COption::GetOptionString("pull", "path_to_listener_secure").(count($channelId)>0?'?CHANNEL_ID='.implode('/', $channelId):'');
		return $url;
	}

	public static function GetWebSocketStatus()
	{
		$result = COption::GetOptionString("pull", "websocket", "N");
		return $result == 'N'? false: true;
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

	public static function GetWebSocketSecureUrl($channelId = "")
	{
		if (!is_array($channelId) && strlen($channelId) > 0)
			$channelId = Array($channelId);
		else if (!is_array($channelId))
			$channelId = Array();

		$url = COption::GetOptionString("pull", "path_to_websocket_secure").(count($channelId)>0?'?CHANNEL_ID='.implode('/', $channelId):'');
		return $url;
	}

	public static function SendConfigDie()
	{
		$arMessage = Array(
			'module_id' => 'pull',
			'command' => 'config_die',
			'params' => ''
		);
		CPullStack::AddBroadcast($arMessage);
	}
}