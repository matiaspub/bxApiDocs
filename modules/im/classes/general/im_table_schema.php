<?

class CIMTableSchema
{
	static public function __construct()
	{
	}

	public static function OnGetTableSchema()
	{
		return array(
			"im" => array(
				"b_im_message" => array(
					"ID" => array(
						"b_im_relation" => "LAST_ID",
						"b_im_relation^" => "LAST_SEND_ID",
						"b_im_relation^^" => "START_ID",
					),
					"CHAT_ID" => array(
						"b_im_chat" => "ID",
					),
				),
				"b_im_chat" => array(
					"ID" => array(
						"b_im_message" => "CHAT_ID",
						"b_im_relation" => "CHAT_ID",
					),
				),
				"b_im_relation" => array(
					"CHAT_ID" => array(
						"b_im_chat" => "ID",
					),
				),
			),
			"main" => array(
				"b_user" => array(
					"ID" => array(
						"b_im_relation" => "USER_ID",
						"b_im_message" => "AUTHOR_ID",
						"b_im_chat" => "AUTHOR_ID",
					),
				),
				"b_module" => array(
					"ID" => array(
						"b_im_message" => "NOTIFY_MODULE",
					),
				),
			),
		);
	}
}

?>
