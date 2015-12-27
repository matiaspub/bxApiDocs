<?
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/classes/general/runtimeservice.php");

class CBPAllTrackingService
	extends CBPRuntimeService
{
	protected $skipTypes = array();
	protected static $userGroupsCache = array();

	public function Start(CBPRuntime $runtime = null)
	{
		parent::Start($runtime);

		$skipTypes = \Bitrix\Main\Config\Option::get("bizproc", "log_skip_types", CBPTrackingType::ExecuteActivity.','.CBPTrackingType::CloseActivity);
		if ($skipTypes !== '')
			$this->skipTypes = explode(',', $skipTypes);
	}

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

	public static function parseStringParameter($string, $documentType = null)
	{
		if (!$documentType)
			$documentType = array('','','');
		return preg_replace_callback(
			CBPActivity::ValueInlinePattern,
			create_function(
				'$matches',
				'return CBPAllTrackingService::parseStringParameterMatches($matches, array("'.$documentType[0].'", "'.$documentType[1].'", "'.$documentType[2].'"));'
			),
			$string
		);
	}

	public static function parseStringParameterMatches($matches, $documentType = null)
	{
		$result = "";
		$documentType = is_array($documentType) ? array_filter($documentType) : null;

		if ($matches[1] == "user")
		{
			$user = $matches[2];

			$l = strlen("user_");
			if (substr($user, 0, $l) == "user_")
			{
				$result = CBPHelper::ConvertUserToPrintableForm(intval(substr($user, $l)));
			}
			elseif (strpos($user, 'group_') === 0)
			{
				$result = htmlspecialcharsbx(CBPHelper::getExtendedGroupName($user));
			}
			elseif ($documentType)
			{
				$v = implode(",", $documentType);
				if (!array_key_exists($v,self::$userGroupsCache ))
					self::$userGroupsCache[$v] = CBPDocument::GetAllowableUserGroups($documentType);

				$result = self::$userGroupsCache[$v][$user];
			}
			else
				$result = $user;
		}
		elseif ($matches[1] == "group")
		{
			if (strpos($matches[2], 'group_') === 0)
			{
				$result = htmlspecialcharsbx(CBPHelper::getExtendedGroupName($matches[2]));
			}
			elseif ($documentType)
			{
				$v = implode(",", $documentType);
				if (!array_key_exists($v, self::$userGroupsCache))
					self::$userGroupsCache[$v] = CBPDocument::GetAllowableUserGroups($documentType);

				$result = self::$userGroupsCache[$v][$matches[2]];
			}
			else
				$result = $matches[2];
		}
		else
		{
			$result = $matches[0];
		}
		return $result;
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