<?
IncludeModuleLangFile(__FILE__);

class CForumNotifySchema
{
	static public function __construct()
	{
	}

	public static function OnGetNotifySchema()
	{
		return array(
			"forum" => array(
				"comment" => Array(
					"NAME" => GetMessage("FORUM_NS_COMMENT"),
					"MAIL" => true,
					"XMPP" => false,
				),
/*
				"mention" => Array(
					"NAME" => GetMessage("FORUM_NS_MENTION"),
					"MAIL" => true,
					"XMPP" => false,
				),
*/
			),
		);
	}
}

?>
