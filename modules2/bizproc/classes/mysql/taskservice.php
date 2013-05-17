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
					"INSERT INTO b_bp_task_user (USER_ID, TASK_ID) ".
					"VALUES (".intval($userId).", ".intval($taskId).") "
				);

				CUserCounter::Increment($userId, 'bp_tasks', '**');

				$ar[] = $userId;
			}

			$events = GetModuleEvents("bizproc", "OnTaskAdd");
			while ($arEvent = $events->Fetch())
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

		if (is_set($arFields, "USERS"))
		{
			$DB->Query("DELETE FROM b_bp_task_user WHERE TASK_ID = ".intval($id)." ");

			CUserCounter::ClearByTag($id, 'bp_tasks', '**');

			$ar = array();
			foreach ($arFields["USERS"] as $userId)
			{
				$userId = intval($userId);
				if (in_array($userId, $ar))
					continue;

				$DB->Query(
					"INSERT INTO b_bp_task_user (USER_ID, TASK_ID) ".
					"VALUES (".intval($userId).", ".intval($id).") "
				);

				CUserCounter::Increment($userId, 'bp_tasks', '**');

				$ar[] = $userId;
			}
		}

		$events = GetModuleEvents("bizproc", "OnTaskUpdate");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($id, $arFields));

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
			"USER_ID" => Array("FIELD" => "TU.USER_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_bp_task_user TU ON (T.ID = TU.TASK_ID)"),
			"WORKFLOW_TEMPLATE_ID" => Array("FIELD" => "WS.WORKFLOW_TEMPLATE_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_bp_workflow_state WS ON (T.WORKFLOW_ID = WS.ID)"),
			"WORKFLOW_TEMPLATE_NAME" => Array("FIELD" => "WT.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_bp_workflow_state WS ON (T.WORKFLOW_ID = WS.ID) INNER JOIN b_bp_workflow_template WT ON (WS.WORKFLOW_TEMPLATE_ID = WT.ID)"),
			"WORKFLOW_TEMPLATE_TEMPLATE_ID" => Array("FIELD" => "WT.ID", "TYPE" => "int", "FROM" => "INNER JOIN b_bp_workflow_state WS ON (T.WORKFLOW_ID = WS.ID) INNER JOIN b_bp_workflow_template WT ON (WS.WORKFLOW_TEMPLATE_ID = WT.ID)"),
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

			//echo "!1!=".htmlspecialcharsbx($strSql)."<br>";

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

			//echo "!2.1!=".htmlspecialcharsbx($strSql_tmp)."<br>";

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

			//echo "!2.3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) > 0)
				$strSql .= "LIMIT ".intval($arNavStartParams["nTopCount"]);

			//echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		$dbRes = new CBPTaskResult($dbRes);
		return $dbRes;
	}
}
?>