<?
IncludeModuleLangFile(__FILE__);

class CBlogNotifySchema
{
	static public function __construct()
	{
	}

	public static function OnGetNotifySchema()
	{
		return array(
			"blog" => array(
				"post" => Array(
					"NAME" => GetMessage('BLG_NS_POST'),
					"MAIL" => true,
					"XMPP" => false,
				),
				"comment" => Array(
					"NAME" => GetMessage('BLG_NS_COMMENT'),
					"MAIL" => true,
					"XMPP" => false,
				),
				"mention" => Array(
					"NAME" => GetMessage('BLG_NS_MENTION'),
					"MAIL" => true,
					"XMPP" => false,
				),
			),
		);
	}
}

?>
