<?
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/classes/general/runtimeservice.php");

class CBPAllTrackingService
	extends CBPRuntimeService
{
	static public function DeleteAllWorkflowTracking($workflowId)
	{
		self::DeleteByWorkflow($workflowId);
	}

	public static function DumpWorkflow($workflowId)
	{
		global $DB;

		$workflowId = trim($workflowId);
		if (strlen($workflowId) <= 0)
			throw new Exception("workflowId");

		$dbResult = $DB->Query(
			"SELECT ID, TYPE, MODIFIED, ACTION_NAME, ACTION_TITLE, EXECUTION_STATUS, EXECUTION_RESULT, ACTION_NOTE, MODIFIED_BY ".
			"FROM b_bp_tracking ".
			"WHERE WORKFLOW_ID = '".$DB->ForSql($workflowId)."' ".
			"ORDER BY ID "
		);

		$r = array();
		$level = 0;
		while ($arResult = $dbResult->GetNext())
		{
			if ($arResult["TYPE"] == CBPTrackingType::CloseActivity)
			{
				$level--;
				$arResult["PREFIX"] = str_repeat("&nbsp;&nbsp;&nbsp;", $level > 0 ? $level : 0);
				$arResult["LEVEL"] = $level;
			}
			elseif ($arResult["TYPE"] == CBPTrackingType::ExecuteActivity)
			{
				$arResult["PREFIX"] = str_repeat("&nbsp;&nbsp;&nbsp;", $level > 0 ? $level : 0);
				$arResult["LEVEL"] = $level;
				$level++;
			}
			else
			{
				$arResult["PREFIX"] = str_repeat("&nbsp;&nbsp;&nbsp;", $level > 0 ? $level : 0);
				$arResult["LEVEL"] = $level;
			}

			$r[] = $arResult;
		}

		return $r;
	}

	static public function LoadReport($workflowId)
	{
		$result = array();

		$dbResult = CBPTrackingService::GetList(
			array("ID" => "ASC"),
			array("WORKFLOW_ID" => $workflowId, "TYPE" => CBPTrackingType::Report),
			false,
			false,
			array("ID", "MODIFIED", "ACTION_NOTE")
		);
		while ($arResult = $dbResult->GetNext())
			$result[] = $arResult;

		return $result;
	}

	public static function DeleteByWorkflow($workflowId)
	{
		global $DB;

		$workflowId = trim($workflowId);
		if (strlen($workflowId) <= 0)
			throw new Exception("workflowId");

		$DB->Query(
			"DELETE FROM b_bp_tracking ".
			"WHERE WORKFLOW_ID = '".$DB->ForSql($workflowId)."' ",
			true
		);
	}

	public static function ClearOldAgent()
	{
		CBPTrackingService::ClearOld(COption::GetOptionString("bizproc", "log_cleanup_days", "90"));
		return "CBPTrackingService::ClearOldAgent();";
	}
}

class CBPTrackingType
{
	const Unknown = 0;
	const ExecuteActivity = 1;
	const CloseActivity = 2;
	const CancelActivity = 3;
	const FaultActivity = 4;
	const Custom = 5;
	const Report = 6;
}
?>