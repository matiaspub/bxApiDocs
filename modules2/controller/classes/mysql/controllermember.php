<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/classes/general/controllermember.php");

class CControllerMember extends CAllControllerMember
{
	function _CheckCommandId($member_guid, $command_id)
	{
		global $DB;
		$strSql =
			"SELECT C.ID, C.COMMAND, M.SECRET_ID, C.ADD_PARAMS ".
			"FROM b_controller_command C INNER JOIN b_controller_member M ON C.MEMBER_ID = M.MEMBER_ID ".
			"WHERE C.MEMBER_ID = '".$DB->ForSQL($member_guid, 32)."' ".
			"	AND C.COMMAND_ID = '".$DB->ForSQL($command_id, 32)."' ".
			"	AND C.DATE_EXEC IS NULL ".
			"	AND C.DATE_INSERT > DATE_ADD(now(), INTERVAL -1 MINUTE)";

		$dbr = $DB->Query($strSql);
		$ar = $dbr->Fetch();
		if(!$ar)
			return false;

		$strSql = "UPDATE b_controller_command SET DATE_EXEC=".$DB->CurrentTimeFunction()." WHERE ID=".$ar["ID"];
		$DB->Query($strSql);
		return $ar;
	}

	public static function UnregisterExpiredAgent($id = false)
	{
		global $DB;

		if($id>0)
			$strAddWhere = ' AND M.ID='.IntVal($id);
		else
			$strAddWhere = '';

		$strSql =
			"SELECT M.ID ".
			"FROM b_controller_member M  ".
			"   INNER JOIN b_controller_group G ON M.CONTROLLER_GROUP_ID=G.ID ".
			"WHERE G.TRIAL_PERIOD>0 ".
			"	AND TO_DAYS(now()) - TO_DAYS(M.IN_GROUP_FROM) >= G.TRIAL_PERIOD  ".
			"	AND SITE_ACTIVE = 'Y' ".
			$strAddWhere;

		$dbr = $DB->Query($strSql);
		while($ar = $dbr->Fetch())
		{
			if($id>0)
			{
				CControllerMember::CloseMember($id, true);
				return true;
			}
			else
				CControllerTask::Add(Array(
					"TASK_ID"=>"CLOSE_MEMBER",
					"CONTROLLER_MEMBER_ID"=>$ar["ID"],
					"INIT_EXECUTE_PARAMS"=>true,
					));
		}

		$strSql =
			"SELECT M.ID ".
			"FROM b_controller_member M ".
			"WHERE (DATE_ACTIVE_FROM IS NULL OR DATE_ACTIVE_FROM <= ".$DB->CurrentTimeFunction().") ".
			"	AND (DATE_ACTIVE_TO IS NULL OR DATE_ACTIVE_TO >= ".$DB->CurrentTimeFunction().") ".
			"	AND ACTIVE = 'Y' ".
			"	AND SITE_ACTIVE <> 'Y'".
			$strAddWhere;

		$dbr = $DB->Query($strSql);
		while($ar = $dbr->Fetch())
		{
			if($id>0)
			{
				CControllerMember::CloseMember($id, false);
				return true;
			}
			else
				CControllerTask::Add(Array(
					"TASK_ID"=>"CLOSE_MEMBER",
					"CONTROLLER_MEMBER_ID"=>$ar["ID"],
					"INIT_EXECUTE_PARAMS"=>false,
					));
		}

		$strSql =
			"SELECT M.ID ".
			"FROM b_controller_member M ".
			"WHERE (DATE_ACTIVE_FROM>".$DB->CurrentTimeFunction()." ".
			"	OR DATE_ACTIVE_TO<".$DB->CurrentTimeFunction()." ".
			"	OR ACTIVE = 'N') ".
			"	AND SITE_ACTIVE = 'Y'".
			$strAddWhere;

		$dbr = $DB->Query($strSql);
		while($ar = $dbr->Fetch())
		{
			if($id>0)
				CControllerMember::CloseMember($id, true);
			else
				CControllerTask::Add(Array(
					"TASK_ID"=>"CLOSE_MEMBER",
					"CONTROLLER_MEMBER_ID"=>$ar["ID"],
					"INIT_EXECUTE_PARAMS"=>true,
					));
		}
		if($id>0)
			return true;

		return "CControllerMember::UnregisterExpiredAgent();";
	}
}
?>
