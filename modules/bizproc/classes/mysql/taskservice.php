<?
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/classes/general/taskservice.php");

class CBPTaskService
	extends CBPAllTaskService
{
	static public function CreateTask($arFields)
	{
		return self::Add($arFields);
	}

	public static function Add($arFields)
	{
		global $DB;

		self::ParseFields($arFields, 0);

		$arInsert = $DB->PrepareInsert("b_bp_task", $arFields);

		$strSql =
			"INSERT INTO b_bp_task (".$arInsert[0].", MODIFIED) ".
			"VALUES(".$arInsert[1].", ".$DB->CurrentTimeFunction().")";
		$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

		$taskId = intval($DB->LastID());

		if ($taskId > 0)
		{
			$ar = array();
			foreach ($arFields["USERS"] as $userId)
			{
				$userId = intval($userId);
				if (in_array($userId, $ar))
					continue;

				$DB->Query(
					"INSERT INTO b_bp_task_user (USER_ID, TASK_ID, ORIGINAL_USER_ID) ".
					"VALUES (".intval($userId).", ".intval($taskId).", ".intval($userId).") "
				);

				CUserCounter::Increment($userId, 'bp_tasks', '**');

				$ar[] = $userId;
			}

			self::onTaskChange($taskId, $arFields, CBPTaskChangedStatus::Add);

			foreach (GetModuleEvents("bizproc", "OnTaskAdd", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($taskId, $arFields));
		}

		return $taskId;
	}

	public static function Update($id, $arFields)
	{
		global $DB;

		$id = intval($id);
		if ($id <= 0)
			throw new Exception("id");

		self::ParseFields($arFields, $id);

		$strUpdate = $DB->PrepareUpdate("b_bp_task", $arFields);

		$strSql =
			"UPDATE b_bp_task SET ".
			"	".$strUpdate.", ".
			"	MODIFIED = ".$DB->CurrentTimeFunction()." ".
			"WHERE ID = ".intval($id)." ";
		$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

		$removedUsers = array();

		if (is_set($arFields, "USERS"))
		{
			$dbResUser = $DB->Query("SELECT USER_ID FROM b_bp_task_user WHERE TASK_ID = ".intval($id)." ");
			while ($arResUser = $dbResUser->Fetch())
			{
				CUserCounter::Decrement($arResUser["USER_ID"], 'bp_tasks', '**');
				$removedUsers[] = $arResUser["USER_ID"];
			}
			$DB->Query("DELETE FROM b_bp_task_user WHERE TASK_ID = ".intval($id)." ");

			$ar = array();
			foreach ($arFields["USERS"] as $userId)
			{
				$userId = intval($userId);
				if (in_array($userId, $ar))
					continue;

				$DB->Query(
					"INSERT INTO b_bp_task_user (USER_ID, TASK_ID, ORIGINAL_USER_ID) ".
					"VALUES (".intval($userId).", ".intval($id).", ".intval($userId).") "
				);

				CUserCounter::Increment($userId, 'bp_tasks', '**');

				$ar[] = $userId;
			}
		}

		$userStatuses = array();
		if (isset($arFields['STATUS']) && $arFields['STATUS'] > CBPTaskStatus::Running)
		{
			$dbResUser = $DB->Query("SELECT USER_ID FROM b_bp_task_user WHERE TASK_ID = ".$id." AND STATUS = ".CBPTaskUserStatus::Waiting);
			while ($arResUser = $dbResUser->Fetch())
			{
				CUserCounter::Decrement($arResUser["USER_ID"], 'bp_tasks', '**');

				if ($arFields['STATUS'] == CBPTaskStatus::Timeout)
					$userStatuses[$arResUser["USER_ID"]] = CBPTaskUserStatus::No;
				else
					$removedUsers[] = $arResUser["USER_ID"];
			}
			if ($arFields['STATUS'] == CBPTaskStatus::Timeout)
			{
				$DB->Query("UPDATE b_bp_task_user SET STATUS = ".CBPTaskUserStatus::No.", DATE_UPDATE = ".$DB->CurrentTimeFunction()
					." WHERE TASK_ID = ".$id." AND STATUS = ".CBPTaskUserStatus::Waiting);
			}
			else
				$DB->Query("DELETE FROM b_bp_task_user WHERE TASK_ID = ".$id." AND STATUS = ".CBPTaskUserStatus::Waiting);
		}

		foreach (GetModuleEvents("bizproc", "OnTaskUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($id, $arFields));

		if ($removedUsers)
			$arFields['USERS_REMOVED'] = $removedUsers;
		if ($userStatuses)
			$arFields['USERS_STATUSES'] = $userStatuses;

		self::onTaskChange($id, $arFields, CBPTaskChangedStatus::Update);
		return $id;
	}

	public static function GetList($arOrder = array("ID" => "DESC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "WORKFLOW_ID", "ACTIVITY", "ACTIVITY_NAME", "MODIFIED", "OVERDUE_DATE", "NAME", "DESCRIPTION", "PARAMETERS");

		static $arFields = array(
			"ID" => Array("FIELD" => "T.ID", "TYPE" => "int"),
			"WORKFLOW_ID" => Array("FIELD" => "T.WORKFLOW_ID", "TYPE" => "string"),
			"ACTIVITY" => Array("FIELD" => "T.ACTIVITY", "TYPE" => "string"),
			"ACTIVITY_NAME" => Array("FIELD" => "T.ACTIVITY_NAME", "TYPE" => "string"),
			"MODIFIED" => Array("FIELD" => "T.MODIFIED", "TYPE" => "datetime"),
			"OVERDUE_DATE" => Array("FIELD" => "T.OVERDUE_DATE", "TYPE" => "datetime"),
			"NAME" => Array("FIELD" => "T.NAME", "TYPE" => "string"),
			"DESCRIPTION" => Array("FIELD" => "T.DESCRIPTION", "TYPE" => "string"),
			"PARAMETERS" => Array("FIELD" => "T.PARAMETERS", "TYPE" => "string"),
			"IS_INLINE" => Array("FIELD" => "T.IS_INLINE", "TYPE" => "string"),
			"STATUS" => Array("FIELD" => "T.STATUS", "TYPE" => "int"),
			'DOCUMENT_NAME' => Array("FIELD" => "T.DOCUMENT_NAME", "TYPE" => "string"),
			"USER_ID" => Array("FIELD" => "TU.USER_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_bp_task_user TU ON (T.ID = TU.TASK_ID)"),
			"USER_STATUS" => Array("FIELD" => "TU.STATUS", "TYPE" => "int", "FROM" => "INNER JOIN b_bp_task_user TU ON (T.ID = TU.TASK_ID)"),
			"WORKFLOW_TEMPLATE_ID" => Array("FIELD" => "WS.WORKFLOW_TEMPLATE_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_bp_workflow_state WS ON (T.WORKFLOW_ID = WS.ID)"),
			"MODULE_ID" => Array("FIELD" => "WS.MODULE_ID", "TYPE" => "string", "FROM" => "INNER JOIN b_bp_workflow_state WS ON (T.WORKFLOW_ID = WS.ID)"),
			"ENTITY" => Array("FIELD" => "WS.ENTITY", "TYPE" => "string", "FROM" => "INNER JOIN b_bp_workflow_state WS ON (T.WORKFLOW_ID = WS.ID)"),
			"WORKFLOW_TEMPLATE_NAME" => Array("FIELD" => "WT.NAME", "TYPE" => "string",
											"FROM" => array("INNER JOIN b_bp_workflow_state WS ON (T.WORKFLOW_ID = WS.ID)",
												"INNER JOIN b_bp_workflow_template WT ON (WS.WORKFLOW_TEMPLATE_ID = WT.ID)")),
			"WORKFLOW_TEMPLATE_TEMPLATE_ID" => Array("FIELD" => "WT.ID", "TYPE" => "int",
													"FROM" => array("INNER JOIN b_bp_workflow_state WS ON (T.WORKFLOW_ID = WS.ID)",
														"INNER JOIN b_bp_workflow_template WT ON (WS.WORKFLOW_TEMPLATE_ID = WT.ID)")),
			'WORKFLOW_STATE' => array("FIELD" => "WS.STATE_TITLE", "TYPE" => "string", "FROM" => "INNER JOIN b_bp_workflow_state WS ON (T.WORKFLOW_ID = WS.ID)"),
			'WORKFLOW_STARTED' => array("FIELD" => "WS.STARTED", "TYPE" => "datetime", "FROM" => "INNER JOIN b_bp_workflow_state WS ON (T.WORKFLOW_ID = WS.ID)"),
			'WORKFLOW_STARTED_BY' => array("FIELD" => "WS.STARTED_BY", "TYPE" => "int", "FROM" => "INNER JOIN b_bp_workflow_state WS ON (T.WORKFLOW_ID = WS.ID)"),
		);

		$arSqls = CBPHelper::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_bp_task T ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return False;
		}

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_bp_task T ".
			"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) <= 0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_bp_task T ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (strlen($arSqls["GROUPBY"]) <= 0)
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();
			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) > 0)
				$strSql .= "LIMIT ".intval($arNavStartParams["nTopCount"]);
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		$dbRes = new CBPTaskResult($dbRes);
		return $dbRes;
	}
}
?>