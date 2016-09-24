<?

class CPushDescription
{
	static public function GetDescription()
	{
		return array(
			array(
				"ID" => "APPLE",
				"CLASS" => "CApplePush",
				"NAME" => "Apple Push Notifications"
			),
			array(
				"ID" => "APPLE/VOIP",
				"CLASS" => "CApplePushVoip",
				"NAME" => "Apple Push Notifications (Voip Service)"
			),
			array(
				"ID" => "GOOGLE/REV2",
				"CLASS" => "CGooglePushInteractive",
				"NAME" => "Google Cloud Messages rev.2"
			),
			array(
				"ID" => "GOOGLE",
				"CLASS" => "CGooglePush",
				"NAME" => "Google Cloud Messages"


		));
	}
}

AddEventHandler("pull", "OnPushServicesBuildList", array("CPushDescription", "GetDescription"));
?>