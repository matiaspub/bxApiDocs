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
				"NAME" => GetMessage('BLG_NS'),
				"NOTIFY" => Array(
					"post" => Array(
						"NAME" => GetMessage('BLG_NS_POST'),
					),
					"comment" => Array(
						"NAME" => GetMessage('BLG_NS_COMMENT'),
					),
					"mention" => Array(
						"NAME" => GetMessage('BLG_NS_MENTION'),
					),
					"share" => Array(
						"NAME" => GetMessage('BLG_NS_SHARE'),
					),
				),
			),
		);
	}
}

?>
