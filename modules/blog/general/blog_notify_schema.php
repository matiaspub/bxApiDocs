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
					),
				),
			),
		);
	}
}

?>
