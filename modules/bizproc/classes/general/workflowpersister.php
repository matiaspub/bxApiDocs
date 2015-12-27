<?
IncludeModuleLangFile(__FILE__);

/**
* Workflow persistence service.
*/
class CBPAllWorkflowPersister
{
	const LOCK_BY_TIME = false;
	protected $serviceInstanceId = "";
	protected $ownershipDelta = 300;
	protected $useGZipCompression = false;

	static public function __clone()
	{
		trigger_error('Clone in not allowed.', E_USER_ERROR);
	}

	protected function GetOwnershipTimeout()
	{
		return time() + $this->ownershipDelta;
	}

	public function LoadWorkflow($instanceId)
	{
		$state = $this->RetrieveWorkflow($instanceId);
		if (strlen($state) > 0)
			return $this->RestoreFromSerializedForm($state);

		throw new Exception("WorkflowNotFound");
	}

	private function RestoreFromSerializedForm($buffer)
	{
		if ($this->useGZipCompression)
			$buffer = gzuncompress($buffer);

		if (strlen($buffer) <= 0)
			throw new Exception("EmptyWorkflowInstance");

		$activity = CBPActivity::Load($buffer);
		return $activity;
	}

	public static function __InsertWorkflowHack($id, $buffer)
	{
		$p = CBPWorkflowPersister::GetPersister();
		if ($p->useGZipCompression)
			$buffer = gzcompress($buffer, 9);
		$p->InsertWorkflow($id, $buffer, 1, true);
	}

	public function SaveWorkflow(CBPActivity $rootActivity, $bUnlocked)
	{
		if ($rootActivity == null)
			throw new Exception("rootActivity");

		$workflowStatus = $rootActivity->GetWorkflowStatus();

		$buffer = "";
		if (($workflowStatus != CBPWorkflowStatus::Completed) && ($workflowStatus != CBPWorkflowStatus::Terminated))
			$buffer = $this->GetSerializedForm($rootActivity);

		$this->InsertWorkflow($rootActivity->GetWorkflowInstanceId(), $buffer, $workflowStatus, $bUnlocked);
	}

	private function GetSerializedForm(CBPActivity $rootActivity)
	{
		$buffer = $rootActivity->Save();

		if ($this->useGZipCompression)
			$buffer = gzcompress($buffer, 9);
		return $buffer;
	}

	public function UnlockWorkflow(CBPActivity $rootActivity)
	{
		global $DB;

		if ($rootActivity == null)
			throw new Exception("rootActivity");

		$DB->Query(
			"UPDATE b_bp_workflow_instance SET ".
			"	OWNER_ID = NULL, ".
			"	OWNED_UNTIL = NULL ".
			"WHERE ID = '".$DB->ForSql($rootActivity->GetWorkflowInstanceId())."' ".
			"	AND ( ".
			"		(OWNER_ID = '".$DB->ForSql($this->serviceInstanceId)."' ".
			"			AND OWNED_UNTIL >= ".$DB->CurrentTimeFunction().") ".
			"		OR ".
			"		(OWNER_ID IS NULL) ".
			"		OR ".
			"		(OWNER_ID IS NOT NULL ".
			"			AND OWNED_UNTIL < ".$DB->CurrentTimeFunction().") ".
			"	)"
		);
	}

	protected function getLockerQueryCondition()
	{
		global $DB;

		if (!static::LOCK_BY_TIME)
		{
			return "(OWNER_ID IS NULL OR OWNER_ID = '".$DB->ForSql($this->serviceInstanceId)."')";
		}

		return
			"( ".
			"	(OWNER_ID = '".$DB->ForSql($this->serviceInstanceId)."' ".
			"		AND OWNED_UNTIL >= ".$DB->CurrentTimeFunction().") ".
			"	OR ".
			"	(OWNER_ID IS NULL) ".
			"	OR ".
			"	(OWNER_ID IS NOT NULL ".
			"		AND OWNED_UNTIL < ".$DB->CurrentTimeFunction().") ".
			") ";
	}
}
?>