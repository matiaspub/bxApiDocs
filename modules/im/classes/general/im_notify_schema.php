<?
IncludeModuleLangFile(__FILE__);

class CIMNotifySchema
{
	protected static $arNotifySchema = null;

	static public function __construct()
	{
	}

	public static function GetNotifySchema()
	{
		if (is_null(self::$arNotifySchema))
		{
			self::$arNotifySchema = Array();
			foreach(GetModuleEvents("im", "OnGetNotifySchema", true) as $arEvent)
			{
				$ar = ExecuteModuleEventEx($arEvent);
				if(is_array($ar))
				{
					foreach($ar as $moduleId => $arNotifyType)
					{
						self::$arNotifySchema[$moduleId]['NAME'] = isset($arNotifyType['NOTIFY']) && isset($arNotifyType['NAME'])? $arNotifyType['NAME']: '';

						$arNotify = $arNotifyType;
						if (isset($arNotifyType['NOTIFY']))
							$arNotify = $arNotifyType['NOTIFY'];

						foreach($arNotify as $notifyEvent => $arConfig)
						{
							if (!isset($arConfig['PUSH']) || $arConfig['PUSH'] == 'NONE')
							{
								$arConfig['DISABLED'][] = IM_NOTIFY_FEATURE_PUSH;
							}

							$arConfig['SITE'] = !isset($arConfig['SITE']) || $arConfig['SITE'] == 'Y'? true: false;
							$arConfig['MAIL'] = !isset($arConfig['MAIL']) || $arConfig['MAIL'] == 'Y'? true: false;
							$arConfig['XMPP'] = !isset($arConfig['XMPP']) || $arConfig['XMPP'] == 'Y'? true: false;
							$arConfig['PUSH'] = isset($arConfig['PUSH']) && $arConfig['PUSH'] == 'Y'? true: false;

							$arDisabled['SITE'] = isset($arConfig['DISABLED']) && in_array(IM_NOTIFY_FEATURE_SITE, $arConfig['DISABLED'])? true: false;
							$arDisabled['MAIL'] = isset($arConfig['DISABLED']) && in_array(IM_NOTIFY_FEATURE_MAIL, $arConfig['DISABLED'])? true: false;
							$arDisabled['XMPP'] = isset($arConfig['DISABLED']) && in_array(IM_NOTIFY_FEATURE_XMPP, $arConfig['DISABLED'])? true: false;
							$arDisabled['PUSH'] = isset($arConfig['DISABLED']) && in_array(IM_NOTIFY_FEATURE_PUSH, $arConfig['DISABLED'])? true: false;
							$arConfig['DISABLED'] = $arDisabled;

							$arConfig['LIFETIME'] = intval($arConfig['LIFETIME']);

							self::$arNotifySchema[$moduleId]['NOTIFY'][$notifyEvent] = $arConfig;
						}
					}
				}
			}
		}
		return self::$arNotifySchema;
	}

	public static function CheckDisableFeature($moduleId, $notifyEvent, $feature)
	{
		$arNotifySchema = self::GetNotifySchema();

		return (bool)$arNotifySchema[$moduleId]['NOTIFY'][$notifyEvent]['DISABLED'][strtoupper($feature)];
	}

	public static function GetDefaultFeature($moduleId, $notifyEvent, $feature)
	{
		$arNotifySchema = self::GetNotifySchema();

		return (bool)$arNotifySchema[$moduleId]['NOTIFY'][$notifyEvent][strtoupper($feature)];
	}

	public static function GetLifetime($moduleId, $notifyEvent)
	{
		$arNotifySchema = self::GetNotifySchema();

		return intval($arNotifySchema[$moduleId]['NOTIFY'][$notifyEvent]['LIFETIME']);
	}

	public static function OnGetNotifySchema()
	{
		$config = array(
			"im" => Array(
				"NAME" => GetMessage('IM_NS_IM'),
				"NOTIFY" => Array(
					"message" => Array(
						"NAME" => GetMessage('IM_NS_MESSAGE'),
						"PUSH" => 'Y',
						"DISABLED" => Array(IM_NOTIFY_FEATURE_SITE, IM_NOTIFY_FEATURE_XMPP)
					),
					"chat" => Array(
						"NAME" => GetMessage('IM_NS_CHAT'),
						"MAIL" => 'N',
						"PUSH" => 'Y',
						"DISABLED" => Array(IM_NOTIFY_FEATURE_SITE, IM_NOTIFY_FEATURE_XMPP, IM_NOTIFY_FEATURE_MAIL)
					),
					"like" => Array(
						"NAME" => GetMessage('IM_NS_LIKE'),
					),
					/*"mention" => Array(
						"NAME" => GetMessage('IM_NS_MENTION'),
						"PUSH" => 'Y',
					),*/
					"default" => Array(
						"NAME" => GetMessage('IM_NS_DEFAULT'),
						"PUSH" => 'N',
						"MAIL" => 'N',
					),
				)
			)
		);

		if (!IsModuleInstalled("b24network"))
		{
			$config["main"] = array(
				"NAME" => GetMessage('IM_NS_MAIN'),
				"NOTIFY" => Array(
					"rating_vote" => Array(
						"NAME" => GetMessage('IM_NS_MAIN_RATING_VOTE'),
						"LIFETIME" => 86400*7
					),
					"rating_vote_mentioned" => Array(
						"NAME" => GetMessage('IM_NS_MAIN_RATING_VOTE_MENTIONED'),
						"LIFETIME" => 86400*7
					),
				),
			);
			$config["bizproc"] = array(
				"NOTIFY" => Array(
					"activity" => Array(
						"NAME" => GetMessage('IM_NS_BIZPROC_ACTIVITY'),
					),
				),
			);
		}

		return $config;
	}
}

?>
