<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/classes/general/workflowpersister.php");

class CBPWorkflowPersister
	extends CBPAllWorkflowPersister
{
	private static $instance;

	private function __construct()
	{
		$this->serviceInstanceId = uniqid("", true);
		$this->ownershipDelta = 300;
		$this->useGZipCompression = function_exists("gzcompress");
	}

	public static function GetPersister() 
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	protected function RetrieveWorkflow($instanceId)
	{
		global $DB;

		$queryCondition = 
			"( ".
			"	(OWNER_ID = '".$DB->ForSql($this->serviceInstanceId)."' ".
			"		AND OWNED_UNTIL >= ".$DB->CurrentTimeFunction().") ".
			"	OR ".
			"	(OWNER_ID IS NULL) ".
			"	OR ".
			"	(OWNER_ID IS NOT NULL ".
			"		AND OWNED_UNTIL < ".$DB->CurrentTimeFunction().") ".
			") ";

		$buffer = "";
		$dbResult = $DB->Query(
			"SELECT WORKFLOW, IF (".$queryCondition.", 'Y', 'N') as UPDATEABLE ".
			"FROM b_bp_workflow_instance ".
			"WHERE ID = '".$DB->ForSql($instanceId)."' "
		);
		if ($arResult = $dbResult->Fetch())
		{
			if ($arResult["UPDATEABLE"] == "Y")
			{
				$DB->Query(
					"UPDATE b_bp_workflow_instance SET ".
					"	OWNER_ID = '".$DB->ForSql($this->serviceInstanceId)."', ".
					"	OWNED_UNTIL = ".$DB->CharToDateFunction(date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $this->GetOwnershipTimeout()))." ".
					"WHERE ID = '".$DB->ForSql($instanceId)."'"
				);

				$buffer = $arResult["WORKFLOW"];
			}
			else
			{
				throw new Exception(GetMessage("BPCGWP_WF_LOCKED"));
			}
		}
		else
		{
			throw new Exception(GetMessage("BPCGWP_INVALID_WF"));
		}

		return $buffer;
	}

	protected function InsertWorkflow($id, $buffer, $status, $bUnlocked)
	{
		global $DB;

		$queryCondition = 
			"( ".
			"	(OWNER_ID = '".$DB->ForSql($this->serviceInstanceId)."' ".
			"		AND OWNED_UNTIL >= ".$DB->CurrentTimeFunction().") ".
			"	OR ".
			"	(OWNER_ID IS NULL) ".
			"	OR ".
			"	(OWNER_ID IS NOT NULL ".
			"		AND OWNED_UNTIL < ".$DB->CurrentTimeFunction().") ".
			") ";

		if ($status == CBPWorkflowStatus::Completed || $status == CBPWorkflowStatus::Terminated)
		{
			$DB->Query(
				"DELETE FROM b_bp_workflow_instance ".
				"WHERE ID = '".$DB->ForSql($id)."' ".
				"	AND ".$queryCondition." "
			);
		}
		else
		{
			$dbResult = $DB->Query(
				"SELECT ID, IF (".$queryCondition.", 'Y', 'N') as UPDATEABLE ".
				"FROM b_bp_workflow_instance ".
				"WHERE ID = '".$DB->ForSql($id)."' "
			);
			if ($arResult = $dbResult->Fetch())
			{
				if ($arResult["UPDATEABLE"] == "Y")
				{
					$DB->Query(
						"UPDATE b_bp_workflow_instance SET ".
						"	WORKFLOW = '".$DB->ForSql($buffer)."', ".
						"	STATUS = ".intval($status).", ".
						"	MODIFIED = ".$DB->CurrentTimeFunction().", ".
						"	OWNER_ID = ".($bUnlocked ? "NULL" : "'".$DB->ForSql($this->serviceInstanceId)."'").", ".
						"	OWNED_UNTIL = ".($bUnlocked ? "NULL" : $DB->CharToDateFunction(date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $this->GetOwnershipTimeout())))." ".
						"WHERE ID = '".$DB->ForSql($id)."' "
					);
				}
				else
				{
					throw new Exception(GetMessage('BPCGWP_WF_LOCKED'));
				}
			}
			else
			{
				$DB->Query(
					"INSERT INTO b_bp_workflow_instance (ID, WORKFLOW, STATUS, MODIFIED, OWNER_ID, OWNED_UNTIL) ".
					"VALUES ('".$DB->ForSql($id)."', '".$DB->ForSql($buffer)."', ".intval($status).", ".$DB->CurrentTimeFunction().", ".($bUnlocked ? "NULL" : "'".$DB->ForSql($this->serviceInstanceId)."'").", ".($bUnlocked ? "NULL" : $DB->CharToDateFunction(date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $this->GetOwnershipTimeout()))).")"
				);
			}
		}
	}
	
}
?>