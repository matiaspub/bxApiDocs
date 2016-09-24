<?
IncludeModuleLangFile(__FILE__);

class CBlogNotifySchema
{
	static public function __construct()
	{
	}

	public static function OnGetNotifySchema()
	{
		$ar = array(
			"post" => Array(
				"NAME" => GetMessage('BLG_NS_POST'),
				"PUSH" => 'Y'
			),
			"post_mail" => Array(
				"NAME" => GetMessage('BLG_NS_POST_MAIL'),
				"PUSH" => 'Y'
			),
			"comment" => Array(
				"NAME" => GetMessage('BLG_NS_COMMENT'),
				"PUSH" => 'N'
			),
			"mention" => Array(
				"NAME" => GetMessage('BLG_NS_MENTION'),
				"PUSH" => 'N'
			),
			"share" => Array(
				"NAME" => GetMessage('BLG_NS_SHARE'),
				"PUSH" => 'N'
			),
			"share2users" => Array(
				"NAME" => GetMessage('BLG_NS_SHARE2USERS'),
				"PUSH" => 'Y'
			)
		);

		if (IsModuleInstalled('intranet'))
		{
			$ar["broadcast_post"] = Array(
				"NAME" => GetMessage('BLG_NS_BROADCAST_POST'),
				"SITE" => "N",
				"MAIL" => "Y",
				"XMPP" => "N",
				"PUSH" => "Y",
				"DISABLED" => Array(IM_NOTIFY_FEATURE_SITE, IM_NOTIFY_FEATURE_XMPP)
			);
		}

		return array(
			"blog" => array(
				"NAME" => GetMessage('BLG_NS'),
				"NOTIFY" => $ar,
			),
		);
	}
}

?>
