<?
IncludeModuleLangFile(__FILE__);

class CWikiNotifySchema
{
	static public function __construct()
	{
	}

	public static function OnGetNotifySchema()
	{
		return array(
			"wiki" => array(
				"comment" => Array(
					"NAME" => GetMessage("WIKI_NS_COMMENT"),
				),
/*
				"mention" => Array(
					"NAME" => GetMessage("WIKI_NS_MENTION"),
				),
*/
			),
		);
	}
}

?>
