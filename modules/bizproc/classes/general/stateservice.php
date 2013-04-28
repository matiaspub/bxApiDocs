<?
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/classes/general/runtimeservice.php");

class CBPAllStateService
	extends CBPRuntimeService
{
	static public function SetStateTitle($workflowId, $stateTitle)
	{
		global $DB;

		$workflowId = trim($workflowId);
		if (strlen($workflowId) <= 0)
			throw new Exception("workflowId");

		$DB->Query(
			"UPDATE b_bp_workflow_state SET ".
			"	STATE_TITLE = ".(strlen($stateTitle) > 0 ? "'".$DB->ForSql($stateTitle)."'" : "NULL").", ".
			"	MODIFIED = ".$DB->CurrentTimeFunction()." ".
			"WHERE ID = '".$DB->ForSql($workflowId)."' "
		);
	}

	static public function SetStatePermissions($workflowId, $arStatePermissions = array(), $bRewrite = true)
	{
		global $DB;

		$workflowId = trim($workflowId);
		if (strlen($workflowId) <= 0)
			throw new Exception("workflowId");

		if ($bRewrite)
		{
			$DB->Query(
				"DELETE FROM b_bp_workflow_permissions ".
				"WHERE WORKFLOW_ID = '".$DB->ForSql($workflowId)."' "
			);
		}

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

		$arState = self::GetWorkflowState($workflowId);
		$documentService = $this->runtime->GetService("DocumentService");
		$documentService->SetPermissions($arState["DOCUMENT_ID"], $workflowId, $arStatePermissions, $bRewrite);
	}

	static public function GetStateTitle($workflowId)
	{
		global $DB;

		$workflowId = trim($workflowId);
		if (strlen($workflowId) <= 0)
			throw new Exception("workflowId");

		$db = $DB->Query("SELECT STATE_TITLE FROM b_bp_workflow_state WHERE ID = '".$DB->ForSql($workflowId)."' ");
		if ($ar = $db->Fetch())
			return $ar["STATE_TITLE"];

		return "";
	}

	public function	AddWorkflow($workflowId, $workflowTemplateId, $documentId, $starterUserId = 0)
	{
		global $DB;

		$arDocumentId = CBPHelper::ParseDocumentId($documentId);

		$workflowId = trim($workflowId);
		if (strlen($workflowId) <= 0)
			throw new Exception("workflowId");

		$workflowTemplateId = intval($workflowTemplateId);
		if ($workflowTemplateId <= 0)
			throw new Exception("workflowTemplateId");

		$starterUserId = intval($starterUserId);
		if ($starterUserId <= 0)
			$starterUserId = "NULL";

		$dbResult = $DB->Query(
			"SELECT ID ".
			"FROM b_bp_workflow_state ".
			"WHERE ID = '".$DB->ForSql($workflowId)."' "
		);

		if ($arResult = $dbResult->Fetch())
			throw new Exception("WorkflowAlreadyExists");

		$DB->Query(
			"INSERT INTO b_bp_workflow_state (ID, MODULE_ID, ENTITY, DOCUMENT_ID, DOCUMENT_ID_INT, WORKFLOW_TEMPLATE_ID, MODIFIED, STARTED, STARTED_BY) ".
			"VALUES ('".$DB->ForSql($workflowId)."', ".((strlen($arDocumentId[0]) > 0) ? "'".$DB->ForSql($arDocumentId[0])."'" : "NULL").", '".$DB->ForSql($arDocumentId[1])."', '".$DB->ForSql($arDocumentId[2])."', ".intval($arDocumentId[2]).", ".intval($workflowTemplateId).", ".$DB->CurrentTimeFunction().", ".$DB->CurrentTimeFunction().", ".$starterUserId.")"
		);
	}

	static public function DeleteWorkflow($workflowId)
	{
		global $DB;

		$workflowId = trim($workflowId);
		if (strlen($workflowId) <= 0)
			throw new Exception("workflowId");

		$DB->Query(
			"DELETE FROM b_bp_workflow_permissions ".
			"WHERE WORKFLOW_ID = '".$DB->ForSql($workflowId)."' "
		);

		$DB->Query(
			"DELETE FROM b_bp_workflow_state ".
			"WHERE ID = '".$DB->ForSql($workflowId)."' "
		);
	}

	static public function DeleteAllDocumentWorkflows($documentId)
	{
		self::DeleteByDocument($documentId);
	}


	private static function __ExtractState(&$arStates, $arResult)
	{
		if (!array_key_exists($arResult["ID"], $arStates))
		{
			$arStates[$arResult["ID"]] = array(
				"ID" => $arResult["ID"],
				"TEMPLATE_ID" => $arResult["WORKFLOW_TEMPLATE_ID"],
				"TEMPLATE_NAME" => $arResult["NAME"],
				"TEMPLATE_DESCRIPTION" => $arResult["DESCRIPTION"],
				"STATE_MODIFIED" => $arResult["MODIFIED"],
				"STATE_NAME" => $arResult["STATE"],
				"STATE_TITLE" => $arResult["STATE_TITLE"],
				"STATE_PARAMETERS" => (strlen($arResult["STATE_PARAMETERS"]) > 0 ? unserialize($arResult["STATE_PARAMETERS"]) : array()),
				"WORKFLOW_STATUS" => $arResult["STATUS"],
				"STATE_PERMISSIONS" => array(),
				"DOCUMENT_ID" => array($arResult["MODULE_ID"], $arResult["ENTITY"], $arResult["DOCUMENT_ID"]),
				"STARTED" => $arResult["STARTED"],
				"STARTED_BY" => $arResult["STARTED_BY"],
				"STARTED_FORMATTED" => $arResult["STARTED_FORMATTED"],
			);
		}

		if (strlen($arResult["PERMISSION"]) > 0 && strlen($arResult["OBJECT_ID"]) > 0)
		{
			$arResult["PERMISSION"] = strtolower($arResult["PERMISSION"]);

			if (!array_key_exists($arResult["PERMISSION"], $arStates[$arResult["ID"]]["STATE_PERMISSIONS"]))
				$arStates[$arResult["ID"]]["STATE_PERMISSIONS"][$arResult["PERMISSION"]] = array();

			$arStates[$arResult["ID"]]["STATE_PERMISSIONS"][$arResult["PERMISSION"]][] = $arResult["OBJECT_ID"];
		}
	}

	public static function GetDocumentStates($documentId, $workflowId = "")
	{
		global $DB;

		$arDocumentId = CBPHelper::ParseDocumentId($documentId);

		$sqlAdditionalFilter = "";
		$workflowId = trim($workflowId);
		if (strlen($workflowId) > 0)
			$sqlAdditionalFilter = " AND WS.ID = '".$DB->ForSql($workflowId)."' ";

		$dbResult = $DB->Query(
			"SELECT WS.ID, WS.WORKFLOW_TEMPLATE_ID, WS.STATE, WS.STATE_TITLE, WS.STATE_PARAMETERS, ".
			"	".$DB->DateToCharFunction("WS.MODIFIED", "FULL")." as MODIFIED, ".
			"	WS.MODULE_ID, WS.ENTITY, WS.DOCUMENT_ID, ".
			"	WT.NAME, WT.DESCRIPTION, WP.OBJECT_ID, WP.PERMISSION, WI.STATUS, ".
			"	WS.STARTED, WS.STARTED_BY ".
			"FROM b_bp_workflow_state WS ".
			"	LEFT JOIN b_bp_workflow_permissions WP ON (WS.ID = WP.WORKFLOW_ID) ".
			"	LEFT JOIN b_bp_workflow_template WT ON (WS.WORKFLOW_TEMPLATE_ID = WT.ID) ".
			"	LEFT JOIN b_bp_workflow_instance WI ON (WS.ID = WI.ID) ".
			"WHERE WS.DOCUMENT_ID = '".$DB->ForSql($arDocumentId[2])."' ".
			"	AND WS.ENTITY = '".$DB->ForSql($arDocumentId[1])."' ".
			"	AND WS.MODULE_ID ".((strlen($arDocumentId[0]) > 0) ? "= '".$DB->ForSql($arDocumentId[0])."'" : "IS NULL")." ".
			$sqlAdditionalFilter
		);

		$arStates = array();
		while ($arResult = $dbResult->Fetch())
			self::__ExtractState($arStates, $arResult);

		return $arStates;
	}

	public static function GetWorkflowState($workflowId)
	{
		global $DB;

		$workflowId = trim($workflowId);
		if (strlen($workflowId) <= 0)
			throw new Exception("workflowId");

		$dbResult = $DB->Query(
			"SELECT WS.ID, WS.WORKFLOW_TEMPLATE_ID, WS.STATE, WS.STATE_TITLE, WS.STATE_PARAMETERS, ".
			"	".$DB->DateToCharFunction("WS.MODIFIED", "FULL")." as MODIFIED, ".
			"	WS.MODULE_ID, WS.ENTITY, WS.DOCUMENT_ID, ".
			"	WT.NAME, WT.DESCRIPTION, WP.OBJECT_ID, WP.PERMISSION, WI.STATUS, ".
			"	WS.STARTED, WS.STARTED_BY, ".$DB->DateToCharFunction("WS.STARTED", "FULL")." as STARTED_FORMATTED ".
			"FROM b_bp_workflow_state WS ".
			"	LEFT JOIN b_bp_workflow_permissions WP ON (WS.ID = WP.WORKFLOW_ID) ".
			"	LEFT JOIN b_bp_workflow_template WT ON (WS.WORKFLOW_TEMPLATE_ID = WT.ID) ".
			"	LEFT JOIN b_bp_workflow_instance WI ON (WS.ID = WI.ID) ".
			"WHERE WS.ID = '".$DB->ForSql($workflowId)."' "
		);

		$arStates = array();
		while ($arResult = $dbResult->Fetch())
			self::__ExtractState($arStates, $arResult);

		$keys = array_keys($arStates);
		if (count($keys) > 0)
			$arStates = $arStates[$keys[0]];

		return $arStates;
	}

	public static function DeleteByDocument($documentId)
	{
		global $DB;

		$arDocumentId = CBPHelper::ParseDocumentId($documentId);

		$dbRes = $DB->Query(
			"SELECT ID ".
			"FROM b_bp_workflow_state ".
			"WHERE DOCUMENT_ID = '".$DB->ForSql($arDocumentId[2])."' ".
			"	AND ENTITY = '".$DB->ForSql($arDocumentId[1])."' ".
			"	AND MODULE_ID ".((strlen($arDocumentId[0]) > 0) ? "= '".$DB->ForSql($arDocumentId[0])."'" : "IS NULL")." "
		);
		while ($arRes = $dbRes->Fetch())
		{
			$DB->Query(
				"DELETE FROM b_bp_workflow_permissions ".
				"WHERE WORKFLOW_ID = '".$DB->ForSql($arRes["ID"])."' "
			);
		}

		$DB->Query(
			"DELETE FROM b_bp_workflow_state ".
			"WHERE DOCUMENT_ID = '".$DB->ForSql($arDocumentId[2])."' ".
			"	AND ENTITY = '".$DB->ForSql($arDocumentId[1])."' ".
			"	AND MODULE_ID ".((strlen($arDocumentId[0]) > 0) ? "= '".$DB->ForSql($arDocumentId[0])."'" : "IS NULL")." "
		);
	}

	public static function MergeStates($firstDocumentId, $secondDocumentId)
	{
		global $DB;

		$arFirstDocumentId = CBPHelper::ParseDocumentId($firstDocumentId);
		$arSecondDocumentId = CBPHelper::ParseDocumentId($secondDocumentId);

		$DB->Query(
			"UPDATE b_bp_workflow_state SET ".
			"	DOCUMENT_ID = '".$DB->ForSql($arFirstDocumentId[2])."', ".
			"	DOCUMENT_ID_INT = ".intval($arFirstDocumentId[2]).", ".
			"	ENTITY = '".$DB->ForSql($arFirstDocumentId[1])."', ".
			"	MODULE_ID = '".$DB->ForSql($arFirstDocumentId[0])."' ".
			"WHERE DOCUMENT_ID = '".$DB->ForSql($arSecondDocumentId[2])."' ".
			"	AND ENTITY = '".$DB->ForSql($arSecondDocumentId[1])."' ".
			"	AND MODULE_ID = '".$DB->ForSql($arSecondDocumentId[0])."' "
		);
	}
}
?>