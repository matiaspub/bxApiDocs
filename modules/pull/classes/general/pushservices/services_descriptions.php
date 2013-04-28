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
				"ID" => "GOOGLE",
				"CLASS" => "CGooglePush",
				"NAME" => "Google Cloud Messages"


		));
	}
}

AddEventHandler("pull", "OnPushServicesBuildList", array("CPushDescription", "GetDescription"));
?>