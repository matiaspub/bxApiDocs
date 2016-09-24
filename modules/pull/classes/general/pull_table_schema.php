<?

class CPullTableSchema
{
	static public function __construct()
	{
	}

	public static function OnGetTableSchema()
	{
		return array(
			"pull" => array(
				"b_pull_channel" => array(
					"CHANNEL_ID" => array(
						"b_pull_stack" => "CHANNEL_ID",
						"b_pull_watch" => "CHANNEL_ID",
					),
				),
				"b_pull_stack" => array(
					"CHANNEL_ID" => array(
						"b_pull_channel" => "CHANNEL_ID",
					),
				),
			),
			"main" => array(
				"b_user" => array(
					"ID" => array(
						"b_pull_channel" => "USER_ID",
						"b_pull_push" => "USER_ID",
						"b_pull_push_queue" => "USER_ID",
						"b_pull_watch" => "USER_ID",
					),
				),
			),
		);
	}
}

?>
