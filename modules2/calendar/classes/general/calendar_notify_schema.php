<?
IncludeModuleLangFile(__FILE__);

class CCalendarNotifySchema
{
	static public function __construct()
	{
	}

	public static function OnGetNotifySchema()
	{
		return array(
			"calendar" => array(
				"invite" => Array(
					"NAME" => GetMessage('EC_NS_INVITE'),
					"MAIL" => true,
					"XMPP" => true,
				),
				"reminder" => Array(
					"NAME" => GetMessage('EC_NS_REMINDER'),
					"MAIL" => true,
					"XMPP" => true,
				),
				"change" => Array(
					"NAME" => GetMessage('EC_NS_CHANGE'),
					"MAIL" => true,
					"XMPP" => true,
				),
				"info" => Array(
					"NAME" => GetMessage('EC_NS_INFO'),
					"MAIL" => true,
					"XMPP" => true,
				),
			),
		);
	}
}

class CCalendarPullSchema
{
	public static function OnGetDependentModule()
	{
		return Array(
			'MODULE_ID' => "calendar",
			'USE' => Array("PUBLIC_SECTION")
		);
	}
}

?>
