<?
IncludeModuleLangFile(__FILE__);

class CSocNetNotifySchema
{
	static public function __construct()
	{
	}

	public static function OnGetNotifySchema()
	{
		return array(
			"socialnetwork" => array(
				"invite_user" => Array(
					"NAME" => GetMessage('SONET_NS_INVITE_USER'),
					"MAIL" => true,
					"XMPP" => false,
				),
				"invite_group" => Array(
					"NAME" => GetMessage('SONET_NS_INVITE_GROUP'),
					"MAIL" => true,
					"XMPP" => false,
				),
			),
		);
	}
}

class CSocNetPullSchema
{
	public static function OnGetDependentModule()
	{
		return Array(
			'MODULE_ID' => "socialnetwork",
			'USE' => Array("PUBLIC_SECTION")
		);
	}
}

?>
