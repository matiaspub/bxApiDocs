<?
IncludeModuleLangFile(__FILE__);
class CTableSchema
{
	static public function __construct()
	{
	}

	public static function OnGetTableSchema()
	{
		return array(
			"main" => array(
				"b_rating_voting" => array(
					"ID" => array(
						"b_rating_vote" => "RATING_VOTING_ID",
						"b_rating_voting_prepare" => "RATING_VOTING_ID",
					),
					//"ENTITY_TYPE_ID=^FORUM_POST\$" => array(
					//	"b_forum_message" => "ID",
					//),
				),
				"b_rating" => array(
					"ID" => array(
						"b_rating_user" => "RATING_ID",
						"b_rating_subordinate" => "RATING_ID",
						"b_rating_results" => "RATING_ID",
						"b_rating_component_results" => "RATING_ID",
						"b_rating_component" => "RATING_ID",
					),
					),
				"b_rating_rule" => array(
					"ID" => array(
						"b_rating_rule_vetting" => "RULE_ID",
					),

				),
				"b_user" => array(
					"ID" => array(
						"b_rating_vote" => "USER_ID",
						"b_rating_voting" => "OWNER_ID",
						"b_rating_vote^" => "OWNER_ID",
						"b_rating_user" => "ENTITY_ID",
						"b_rating_subordinate" => "ENTITY_ID",
						
					)
				),
				"b_group" => array(
					"ID" => array(
						"b_rating_vote_group" => "GROUP_ID",
					)
				),
				"b_module" => array(
					"ID" => array(
						"b_admin_notify" => "MODULE_ID",
					)
				),
			),
		);
	}
}

?>