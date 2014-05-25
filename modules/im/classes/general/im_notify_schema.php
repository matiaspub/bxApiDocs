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
							$arConfig['SITE'] = true;
							$arConfig['MAIL'] = true;
							$arConfig['XMPP'] = true;

							self::$arNotifySchema[$moduleId]['NOTIFY'][$notifyEvent] = $arConfig;
						}
					}
				}
			}
		}
		return self::$arNotifySchema;
	}

	public static function CheckEnableFeature($moduleId, $notifyEvent, $feature)
	{
		return true;
	}

	public static function OnGetNotifySchema()
	{
		return array(
			"im" => array(
				"NOTIFY" => Array(
					"message" => Array(
						"NAME" => GetMessage('IM_NS_MESSAGE'),
					),
					"default" => Array(
						"NAME" => GetMessage('IM_NS_DEFAULT'),
					),
				),
			),
			"main" => array(
				"NAME" => GetMessage('IM_NS_MAIN'),
				"NOTIFY" => Array(
					"rating_vote" => Array(
						"NAME" => GetMessage('IM_NS_MAIN_RATING_VOTE'),
					),
				),
			),
			"bizproc" => array(
				"NOTIFY" => Array(
					"activity" => Array(
						"NAME" => GetMessage('IM_NS_BIZPROC_ACTIVITY'),
					),
				),
			),
		);
	}
}

?>
