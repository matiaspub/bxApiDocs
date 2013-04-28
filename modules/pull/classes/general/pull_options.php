<?
class CPullOptions
{
	public static function CheckNeedRun($bGetSectionStatus = true)
	{
		global $CACHE_MANAGER;

		$bAdminSection = false;
		if(defined("ADMIN_SECTION") && ADMIN_SECTION == true)
			$bAdminSection = true;

		$res = $CACHE_MANAGER->Read(2592000, "pull_cnr");
		if ($res)
		{
			$arResult = $CACHE_MANAGER->Get("pull_cnr");
		}
		else
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
			CAgent::AddAgent("CPullChannel::CheckExpireAgent();", "pull", "N", 43200, "", "Y", ConvertTimeStamp(time()+CTimeZone::GetOffset() + 43200, "FULL"));
			CAgent::AddAgent("CPullStack::CheckExpireAgent();", "pull", "N", 86400, "", "Y", ConvertTimeStamp(time()+CTimeZone::GetOffset() + 86400, "FULL"));
			CAgent::AddAgent("CPullWatch::CheckExpireAgent();", "pull", "N", 600, "", "Y", ConvertTimeStamp(time()+CTimeZone::GetOffset() + 600, "FULL"));
		}
		else
		{
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
			CAgent::RemoveAgent("CPullStack::CheckExpireAgent();", "pull");
		else
			CAgent::AddAgent("CPullStack::CheckExpireAgent();", "pull", "N");

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
			$path = 'http://127.0.0.1:8895/bitrix/pub/';

		COption::SetOptionString("pull", "path_to_publish", $path);
		return true;
	}

	public static function SetListenUrl($path = "")
	{
		if (strlen($path)<=0)
			$path = (CMain::IsHTTPS() ? "https" : "http")."://#DOMAIN#".(CMain::IsHTTPS() ? ":8894" : ":8893").(BX_UTF ? '/bitrix/sub/' : '/bitrix/subwin/');

		COption::SetOptionString("pull", "path_to_listener", $path);
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
		$url = COption::GetOptionString("pull", "path_to_publish", "").(strlen($channelId)>0?'?CHANNEL_ID='.$channelId:'');
		return $url;
	}

	public static function GetListenUrl($channelId = "")
	{
		$url = COption::GetOptionString("pull", "path_to_listener", "").(strlen($channelId)>0?'?CHANNEL_ID='.$channelId:'');
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