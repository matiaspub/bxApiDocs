<?
global $DB;
$db_type = strtolower($DB->type);
CModule::AddAutoloadClasses(
	"controller",
	array(
		"controller" => "install/index.php",
		"CControllerAgent" => "classes/".$db_type."/controlleragent.php",
		"CControllerServerRequestTo" => "classes/general/controller.php",
		"CControllerServerResponseFrom" => "classes/general/controller.php",
		"CControllerServerRequestFrom" => "classes/general/controller.php",
		"CControllerServerResponseTo" => "classes/general/controller.php",
		"CControllerGroup" => "classes/general/controllergroup.php",
		"CControllerGroupSettings" => "classes/general/controllergroup.php",
		"IControllerGroupOption" => "classes/general/controllergroup.php",
		"CControllerLog" => "classes/general/controllerlog.php",
		"CControllerMember" => "classes/".$db_type."/controllermember.php",
		"CControllerTask" => "classes/general/controllertask.php",
		"CAllControllerCounter" => "classes/general/counter.php",
		"CControllerCounter" => "classes/".$db_type."/counter.php",
	)
);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/controller_member.php");

function ControllerIsSharedMode()
{
	return is_dir($_SERVER['DOCUMENT_ROOT']."/bitrix/clients");
}
?>
