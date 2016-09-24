<?
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/classes/general/stateservice.php");

class CBPStateService
	extends CBPAllStateService
{
	public function SetState($workflowId, $arState, $arStatePermissions = array())
	{
		global $DB;

		$workflowId = trim($workflowId);
		if (strlen($workflowId) <= 0)
			throw new Exception("workflowId");

		$state = trim($arState["STATE"]);
		$stateTitle = trim($arState["TITLE"]);
		$stateParameters = "";
		if (count($arState["PARAMETERS"]) > 0)
			$stateParameters = serialize($arState["PARAMETERS"]);

		$DB->Query(
			"UPDATE b_bp_workflow_state SET ".
			"	STATE = ".(strlen($state) > 0 ? "'".$DB->ForSql($state)."'" : "NULL").", ".
			"	STATE_TITLE = ".(strlen($stateTitle) > 0 ? "'".$DB->ForSql($stateTitle)."'" : "NULL").", ".
			"	STATE_PARAMETERS = ".(strlen($stateParameters) > 0 ? "'".$DB->ForSql($stateParameters)."'" : "NULL").", ".
			"	MODIFIED = ".$DB->CurrentTimeFunction()." ".
			"WHERE ID = '".$DB->ForSql($workflowId)."' "
		);

		if ($arStatePermissions !== false)
		{
			$arState = self::GetWorkflowState($workflowId);
			$runtime = $this->runtime;
			if (!isset($runtime) || !is_object($runtime))
				$runtime = CBPRuntime::GetRuntime();
			$documentService = $runtime->GetService("DocumentService");
			$documentService->SetPermissions($arState["DOCUMENT_ID"], $workflowId, $arStatePermissions, true);
			$documentType = $documentService->GetDocumentType($arState["DOCUMENT_ID"]);
			if ($documentType)
				$arStatePermissions = $documentService->toInternalOperations($documentType, $arStatePermissions);

			$DB->Query(
				"DELETE FROM b_bp_workflow_permissions ".
				"WHERE WORKFLOW_ID = '".$DB->ForSql($workflowId)."' "
			);

			foreach ($arStatePermissions as $permission => $arObjects)
			{
				foreach ($arObjects as $object)
				{
					$DB->Query(
						"INSERT INTO b_bp_workflow_permissions (WORKFLOW_ID, OBJECT_ID, PERMISSION) ".
						"VALUES ('".$DB->ForSql($workflowId)."', '".$DB->ForSql($object)."', '".$DB->ForSql($permission)."')"
					);
				}
			}
		}
	}

	static public function SetStateParameters($workflowId, $arStateParameters = array())
	{
		global $DB;

		$workflowId = trim($workflowId);
		if (strlen($workflowId) <= 0)
			throw new Exception("workflowId");

		$stateParameters = "";
		if (count($arStateParameters) > 0)
			$stateParameters = serialize($arStateParameters);

		$DB->Query(
			"UPDATE b_bp_workflow_state SET ".
			"	STATE_PARAMETERS = ".(strlen($stateParameters) > 0 ? "'".$DB->ForSql($stateParameters)."'" : "NULL").", ".
			"	MODIFIED = ".$DB->CurrentTimeFunction()." ".
			"WHERE ID = '".$DB->ForSql($workflowId)."' "
		);
	}

	static public function AddStateParameter($workflowId, $arStateParameter)
	{
		global $DB;

		$workflowId = trim($workflowId);
		if (strlen($workflowId) <= 0)
			throw new Exception("workflowId");

		$dbResult = $DB->Query(
			"SELECT STATE_PARAMETERS ".
			"FROM b_bp_workflow_state ".
			"WHERE ID = '".$DB->ForSql($workflowId)."' "
		);

		if ($arResult = $dbResult->Fetch())
		{
			$stateParameters = array();
			if (strlen($arResult["STATE_PARAMETERS"]) > 0)
				$stateParameters = unserialize($arResult["STATE_PARAMETERS"]);

			$stateParameters[] = $arStateParameter;

			$stateParameters = serialize($stateParameters);

			$DB->Query(
				"UPDATE b_bp_workflow_state SET ".
				"	STATE_PARAMETERS = ".(strlen($stateParameters) > 0 ? "'".$DB->ForSql($stateParameters)."'" : "NULL").", ".
				"	MODIFIED = ".$DB->CurrentTimeFunction()." ".
				"WHERE ID = '".$DB->ForSql($workflowId)."' "
			);
		}
	}

	static public function DeleteStateParameter($workflowId, $name)
	{
		global $DB;

		$workflowId = trim($workflowId);
		if (strlen($workflowId) <= 0)
			throw new Exception("workflowId");

		$dbResult = $DB->Query(
			"SELECT STATE_PARAMETERS ".
			"FROM b_bp_workflow_state ".
			"WHERE ID = '".$DB->ForSql($workflowId)."' "
		);

		if ($arResult = $dbResult->Fetch())
		{
			$stateParameters = array();
			if (strlen($arResult["STATE_PARAMETERS"]) > 0)
				$stateParameters = unserialize($arResult["STATE_PARAMETERS"]);

			$ar = array();
			foreach ($stateParameters as $v)
			{
				if ($v["NAME"] != $name)
					$ar[] = $v;
			}

			$stateParameters = "";
			if (count($ar) > 0)
				$stateParameters = serialize($ar);

			$DB->Query(
				"UPDATE b_bp_workflow_state SET ".
				"	STATE_PARAMETERS = ".(strlen($stateParameters) > 0 ? "'".$DB->ForSql($stateParameters)."'" : "NULL").", ".
				"	MODIFIED = ".$DB->CurrentTimeFunction()." ".
				"WHERE ID = '".$DB->ForSql($workflowId)."' "
			);
		}
	}

	public static function __InsertStateHack($id, $moduleId, $entity, $documentId, $templateId, $state, $stateTitle, $stateParameters, $arStatePermissions)
	{
		global $DB;

		$DB->Query(
			"INSERT INTO b_bp_workflow_state (ID, MODULE_ID, ENTITY, DOCUMENT_ID, DOCUMENT_ID_INT, WORKFLOW_TEMPLATE_ID, MODIFIED, STATE, STATE_TITLE, STATE_PARAMETERS) ".
			"VALUES ('".$DB->ForSql($id)."', '".$DB->ForSql($moduleId)."', '".$DB->ForSql($entity)."', '".$DB->ForSql($documentId)."', ".intval($documentId).", ".intval($templateId).", ".$DB->CurrentTimeFunction().", '".$DB->ForSql($state)."', '".$DB->ForSql($stateTitle)."', ".(strlen($stateParameters) > 0 ? "'".$DB->ForSql($stateParameters)."'" : "NULL").")"
		);

		foreach ($arStatePermissions as $permission => $arObjects)
		{
			foreach ($arObjects as $object)
			{
				$DB->Query(
					"INSERT INTO b_bp_workflow_permissions (WORKFLOW_ID, OBJECT_ID, PERMISSION) ".
					"VALUES ('".$DB->ForSql($id)."', '".$DB->ForSql($object)."', '".$DB->ForSql($permission)."')"
				);
			}
		}
	}
}
?>
