<?
class CIMSettings
{
	const SETTINGS = 'settings';
	const NOTIFY = 'notify';

	const CLIENT_SITE = 'site';
	const CLIENT_XMPP = 'xmpp';
	const CLIENT_MAIL = 'email';
	const CLIENT_PUSH = 'push';

	const START_MESSAGE_FIRST = 'first';
	const START_MESSAGE_LAST = 'last';

	const PRIVACY_MESSAGE = 'privacyMessage';
	const PRIVACY_CHAT = 'privacyChat';
	const PRIVACY_CALL = 'privacyCall';
	const PRIVACY_SEARCH = 'privacySearch';
	const PRIVACY_PROFILE = 'privacyProfile';
	const PRIVACY_RESULT_ALL = 'all';
	const PRIVACY_RESULT_CONTACT = 'contact';
	const PRIVACY_RESULT_NOBODY = 'nobody';

	const STATUS = 'status';

	public static function Get($userId = false)
	{
		global $USER, $CACHE_MANAGER;

		$userId = intval($userId);
		if ($userId == 0)
			$userId = $USER->GetId();

		$arSettings = Array();
		$res = $CACHE_MANAGER->Read(2678400, $cache_id="b_ims_".intval($userId), "b_im_options");
		if ($res)
			$arSettings = $CACHE_MANAGER->Get($cache_id);

		if(!is_array($arSettings) || !isset($arSettings['settings']) || !isset($arSettings['notify']))
		{
			$arSettings[self::SETTINGS] = CUserOptions::GetOption('im', self::SETTINGS, Array(), $userId);
			$arSettings[self::NOTIFY] = CUserOptions::GetOption('im', self::NOTIFY, Array(), $userId);
			$CACHE_MANAGER->Set($cache_id, $arSettings);
		}
		// Check fields and add default values
		$arSettings[self::SETTINGS] = self::checkValues(self::SETTINGS, $arSettings[self::SETTINGS]);
		$arSettings[self::NOTIFY] = self::checkValues(self::NOTIFY, $arSettings[self::NOTIFY]);
		return $arSettings;
	}

	public static function Set($type, $value, $userId = false)
	{
		if (!in_array($type, Array(self::SETTINGS, self::NOTIFY)))
			return false;

		global $USER, $USER_FIELD_MANAGER;
		$userId = intval($userId);
		if ($userId == 0)
			$userId = $USER->GetId();

		if (isset($value[self::STATUS]))
		{
			CIMStatus::Set($userId, Array('STATUS' => $value[self::STATUS]));
		}

		$arDefault = self::GetDefaultSettings($type);
		foreach ($value as $key => $val)
		{
			if (isset($arDefault[$key]) && $arDefault[$key] == $val)
			{
				if ($key == self::PRIVACY_SEARCH)
					$USER_FIELD_MANAGER->Update("USER", $userId, Array('UF_IM_SEARCH' => ''));
				unset($value[$key]);
			}
		}
		CUserOptions::SetOption('im', $type, $value, false, $userId);

		if (isset($value[self::PRIVACY_SEARCH]))
		{
			$USER_FIELD_MANAGER->Update("USER", $userId, Array('UF_IM_SEARCH' => $value[self::PRIVACY_SEARCH]));
		}

		self::ClearCache($userId);

		return true;
	}

	public static function SetSetting($type, $value, $userId = false)
	{
		if (!in_array($type, Array(self::SETTINGS, self::NOTIFY)))
			return false;

		global $USER, $USER_FIELD_MANAGER;
		$userId = intval($userId);
		if ($userId == 0)
			$userId = $USER->GetId();

		$arSettings = CUserOptions::GetOption('im', $type, Array(), $userId);
		foreach ($value as $key => $val)
			$arSettings[$key] = $val;

		if (isset($value[self::STATUS]))
		{
			CIMStatus::Set($userId, Array('STATUS' => $value[self::STATUS]));
		}

		$arDefault = self::GetDefaultSettings($type);
		foreach ($arSettings as $key => $val)
		{
			if (isset($arDefault[$key]) && $arDefault[$key] == $val)
			{
				if ($key == self::PRIVACY_SEARCH)
					$USER_FIELD_MANAGER->Update("USER", $userId, Array('UF_IM_SEARCH' => ''));
				unset($value[$key]);
			}
		}
		CUserOptions::SetOption('im', $type, $arSettings, false, $userId);
		if (isset($value[self::PRIVACY_SEARCH]))
		{
			$USER_FIELD_MANAGER->Update("USER", $userId, Array('UF_IM_SEARCH' => $value[self::PRIVACY_SEARCH]));
		}

		self::ClearCache($userId);

		return true;
	}

	public static function GetSetting($type, $value, $userId = false)
	{
		if (!in_array($type, Array(self::SETTINGS, self::NOTIFY)))
			return null;

		$arSettings = self::Get($userId);

		return isset($arSettings[$type][$value])? $arSettings[$type][$value]: null;
	}

	public static function GetNotifyAccess($userId, $moduleId, $eventId, $clientId)
	{
		$userId = intval($userId);
		if ($userId <= 0 || strlen($moduleId) <= 0 || strlen($eventId) <= 0 || strlen($clientId) <= 0)
			return false;

		$notifyId = $clientId.'|'.$moduleId.'|'.$eventId;
		$arSettings = self::Get($userId);
		if ($arSettings['settings']['notifyScheme'] == 'simple')
		{
			//if ($arSettings['settings']['notifySchemeLevel'] == 'important' && !$arSettings['notify']['important|'.$moduleId.'|'.$eventId])
			//	return false;
			if ($clientId == self::CLIENT_SITE && !$arSettings['settings']['notifySchemeSendSite'])
				return false;
			elseif ($clientId == self::CLIENT_XMPP && !$arSettings['settings']['notifySchemeSendXmpp'])
				return false;
			elseif ($clientId == self::CLIENT_MAIL && !$arSettings['settings']['notifySchemeSendEmail'])
				return false;
			elseif ($clientId == self::CLIENT_PUSH && !$arSettings['settings']['notifySchemeSendPush'])
				return false;

			return isset($arSettings['notify']) && array_key_exists($notifyId, $arSettings['notify']) && $arSettings['notify'][$notifyId] === false? false: true;
		}
		else
		{
			if (isset($arSettings['notify']) && array_key_exists($notifyId, $arSettings['notify']))
			{
				return $arSettings['notify'][$notifyId];
			}
			else if (isset($arSettings['notify']) && array_key_exists($clientId.'|im|default', $arSettings['notify']))
			{
				return $arSettings['notify'][$clientId.'|im|default'];
			}
		}
		return false;
	}

	public static function GetDefaultSettings($type)
	{
		$arDefault = Array();
		if ($type == self::SETTINGS)
		{
			$arDefault = Array(
				'status' => 'online',
				'bxdNotify' => true,
				'sshNotify' => true,
				'generalNotify' => true,
				'trackStatus' => '',
				'nativeNotify' => true,
				'viewOffline' => COption::GetOptionString("im", "view_offline"),
				'viewGroup' => COption::GetOptionString("im", "view_group"),
				'viewLastMessage' => true,
				'enableSound' => true,
				'enableBigSmile' => true,
				'sendByEnter' => COption::GetOptionString("im", "send_by_enter"),
				'correctText' => COption::GetOptionString("im", "correct_text"),
				'panelPositionHorizontal' => COption::GetOptionString("im", "panel_position_horizontal"),
				'panelPositionVertical' => COption::GetOptionString("im", "panel_position_vertical"),
				'loadLastMessage' => COption::GetOptionString("im", "load_last_message"),
				'loadLastNotify' => COption::GetOptionString("im", "load_last_notify"),
				'notifyAutoRead' => true,
				'notifyScheme' => 'simple',
				'notifySchemeLevel' => 'important',
				'notifySchemeSendSite' => true,
				'notifySchemeSendEmail' => true,
				'notifySchemeSendXmpp' => true,
				'notifySchemeSendPush' => true,
				'privacyMessage' => COption::GetOptionString("im", "privacy_message"),
				'privacyChat' => COption::GetOptionString("im", "privacy_chat"),
				'privacyCall' => COption::GetOptionString("im", "privacy_call"),
				'privacySearch' => COption::GetOptionString("im", "privacy_search"),
				'privacyProfile' => COption::GetOptionString("im", "privacy_profile"),
			);
		}
		elseif ($type == self::NOTIFY)
		{
			$arNotify = CIMNotifySchema::GetNotifySchema();
			foreach ($arNotify as $moduleId => $notifyTypes)
			{
				foreach ($notifyTypes['NOTIFY'] as $notifyId => $notify)
				{
					$arDefault[self::CLIENT_SITE.'|'.$moduleId.'|'.$notifyId] = $notify['SITE'];
					$arDefault[self::CLIENT_MAIL.'|'.$moduleId.'|'.$notifyId] = $notify['MAIL'];
					$arDefault[self::CLIENT_XMPP.'|'.$moduleId.'|'.$notifyId] = $notify['XMPP'];
					$arDefault[self::CLIENT_PUSH.'|'.$moduleId.'|'.$notifyId] = $notify['PUSH'];
					$arDefault['disabled|'.self::CLIENT_SITE.'|'.$moduleId.'|'.$notifyId] = $notify['DISABLED']['SITE'];
					$arDefault['disabled|'.self::CLIENT_MAIL.'|'.$moduleId.'|'.$notifyId] = $notify['DISABLED']['MAIL'];
					$arDefault['disabled|'.self::CLIENT_XMPP.'|'.$moduleId.'|'.$notifyId] = $notify['DISABLED']['XMPP'];
					$arDefault['disabled|'.self::CLIENT_PUSH.'|'.$moduleId.'|'.$notifyId] = $notify['DISABLED']['PUSH'];
					$arDefault['important|'.$moduleId.'|'.$notifyId] = is_bool($notify['IMPORTANT'])? $notify['IMPORTANT']: true;
				}
			}
		}
		return $arDefault;
	}

	public static function CheckValues($type, $value)
	{
		$arValues = Array();

		$arDefault = self::GetDefaultSettings($type);
		if ($type == self::SETTINGS)
		{
			foreach($arDefault as $key => $default)
			{
				if ($key == 'status')
				{
					$arValues[$key] = in_array($value[$key], Array('online', 'dnd', 'away'))? $value[$key]: $default;
				}
				else if ($key == 'panelPositionHorizontal')
				{
					$arValues[$key] = in_array($value[$key], Array('left', 'center', 'right'))? $value[$key]: $default;
				}
				else if ($key == 'panelPositionVertical')
				{
					$arValues[$key] = in_array($value[$key], Array('top', 'bottom'))? $value[$key]: $default;
				}
				else if ($key == 'notifyScheme')
				{
					$arValues[$key] = in_array($value[$key], Array('simple', 'expert'))? $value[$key]: $default;
				}
				else if (in_array($key, Array('privacyMessage', 'privacyChat', 'privacyCall', 'privacySearch')))
				{
					$arValues[$key] = in_array($value[$key], Array(self::PRIVACY_RESULT_ALL, self::PRIVACY_RESULT_CONTACT))? $value[$key]: $default;
				}
				else if ($key == 'privacyProfile')
				{
					$arValues[$key] = in_array($value[$key], Array(self::PRIVACY_RESULT_ALL, self::PRIVACY_RESULT_CONTACT, self::PRIVACY_RESULT_NOBODY))? $value[$key]: $default;
				}
				else if ($key == 'sendByEnter' && $value[$key] === 'Y') // for legacy
				{
					$arValues[$key] = true;
				}
				else if ($key == 'enableSound' && $value[$key] === 'N') // for legacy
				{
					$arValues[$key] = false;
				}
				else if ($key == 'notifySchemeLevel')
				{
					$arValues[$key] = in_array($value[$key], Array('normal', 'important'))? $value[$key]: $default;
				}
				else if ($key == 'trackStatus')
				{
					$value[$key] = explode(',', $value[$key]);
					foreach ($value[$key] as $k => $v)
					{
						if ($v != 'all')
						{
							$value[$key][$k] = intval($v);
							if ($value[$key][$k] == 0)
							{
								unset($value[$key][$k]);
							}
						}
					}
					$arValues[$key] = implode(',', $value[$key]);

				}
				else if (array_key_exists($key, $value))
				{
					$arValues[$key] = is_bool($value[$key])? $value[$key]: $default;
				}
				else
				{
					$arValues[$key] = $default;
				}
			}
		}
		else if ($type == self::NOTIFY)
		{
			foreach($arDefault as $key => $default)
			{
				if (array_key_exists($key, $value))
					$arValues[$key] = is_bool($value[$key])? $value[$key]: $default;
				else
					$arValues[$key] = $default;
			}
		}
		return $arValues;
	}

	public static function GetNotifyNames()
	{
		$arNames = Array();
		$arNotify = CIMNotifySchema::GetNotifySchema();
		foreach ($arNotify as $moduleId => $notifyTypes)
		{
			$arNames[$moduleId]['NAME'] = $notifyTypes['NAME'];
			if (strlen($notifyTypes['NAME']) <= 0)
			{
				$info = CModule::CreateModuleObject($moduleId);
				$arNames[$moduleId]['NAME'] = $info->MODULE_NAME;
			}
			foreach ($notifyTypes['NOTIFY'] as $notifyId => $notify)
			{
				$arNames[$moduleId]['NOTIFY'][$notifyId] = $notify['NAME'];
			}
		}

		return $arNames;
	}

	public static function GetSimpleNotifyBlocked($byModule = false)
	{
		$arNotifyBlocked = Array();

		$arSettings = self::Get();
		if ($arSettings[self::SETTINGS]['notifyScheme'] == 'expert')
		{
			foreach ($arSettings[self::NOTIFY] as $key => $value)
			{
				if ($value === false)
				{
					list($clientId, $moduleId, $notifyId) = explode('|', $key, 3);
					if ($clientId == self::CLIENT_SITE)
					{
						if (CIMNotifySchema::CheckDisableFeature($moduleId, $notifyId, $clientId))
							continue;
						if ($byModule)
							$arNotifyBlocked[$moduleId][$notifyId] = false;
						else
							$arNotifyBlocked[$moduleId.'|'.$notifyId] = false;
					}
				}
			}
		}
		else
		{
			foreach ($arSettings[self::NOTIFY] as $key => $value)
			{
				if ($value === false)
				{
					list($clientId, $moduleId, $notifyId) = explode('|', $key, 3);
					if (in_array($clientId, Array('push', 'important', 'disabled')))
						continue;
					
					if ($clientId == self::CLIENT_SITE)
					{
						if (CIMNotifySchema::CheckDisableFeature($moduleId, $notifyId, $clientId))
							continue;
						if ($byModule)
							$arNotifyBlocked[$moduleId][$notifyId] = false;
						else
							$arNotifyBlocked[$moduleId.'|'.$notifyId] = false;
					}
				}
			}
		}

		return $arNotifyBlocked;
	}

	public static function GetPrivacy($type, $userId = false)
	{
		$ar = CIMSettings::Get($userId);
		return array_key_exists($type, $ar[CIMSettings::SETTINGS])? $ar[CIMSettings::SETTINGS][$type]: false;
	}

	public static function GetStartChatMessage()
	{
		return COption::GetOptionString("im", 'start_chat_message');
	}

	public static function ClearCache($userId = false)
	{
		global $CACHE_MANAGER;

		$userId = intval($userId);
		if ($userId == 0)
			$CACHE_MANAGER->CleanDir("b_im_options");
		else
			$CACHE_MANAGER->Clean("b_ims_".intval($userId), "b_im_options");

		return true;
	}
}