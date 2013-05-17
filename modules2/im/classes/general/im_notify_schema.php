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
						foreach($arNotifyType as $notifyEvent => $arConfig)
						{
							$arConfig['ID'] = $notifyEvent;
							self::$arNotifySchema[$moduleId][$notifyEvent] = $arConfig;
						}
					}
				}
			}
		}
		return self::$arNotifySchema;
	}

	public static function CheckEnableFeature($moduleId, $notifyEvent, $feature)
	{
		$feature = strtoupper($feature);
		if ($feature == IM_FEATURE_XMPP || $feature == IM_FEATURE_MAIL)
		{
			$arNotifySchema = self::GetNotifySchema();
			if (isset($arNotifySchema[$moduleId][$notifyEvent][$feature]) && $arNotifySchema[$moduleId][$notifyEvent][$feature] === false)
				return false;
			elseif (isset($arNotifySchema["im"]["default"][$feature]))
				return $arNotifySchema["im"]["default"][$feature] === true? true: false;

		}
		return false;
	}

	public static function OnGetNotifySchema()
	{
		return array(
			"im" => array(
				"default" => Array(
					"NAME" => GetMessage('IM_NS_DEFAULT'),
					"MAIL" => true,
					"XMPP" => true,
				),
			),
			"main" => array(
				"rating_vote" => Array(
					"NAME" => GetMessage('IM_NS_MAIN_RATING_VOTE'),
					"MAIL" => true,
					"XMPP" => false,
				),
			),
			"bizproc" => array(
				"activity" => Array(
					"NAME" => GetMessage('IM_NS_BIZPROC_ACTIVITY'),
					"MAIL" => true,
					"XMPP" => true,
				),
			),
		);
	}
}

?>
