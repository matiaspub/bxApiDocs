<?
IncludeModuleLangFile(__FILE__);

class CForumNotifySchema
{
	static public function __construct()
	{
	}

	public static function OnGetNotifySchema()
	{
		return IsModuleInstalled('bitrix24')? array(): array(
			"forum" => array(
				"comment" => Array(
					"NAME" => GetMessage("FORUM_NS_COMMENT"),
				),
/*
				"mention" => Array(
					"NAME" => GetMessage("FORUM_NS_MENTION"),
				),
*/
			),
		);
	}
}

?>
