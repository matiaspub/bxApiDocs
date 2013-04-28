<?
IncludeModuleLangFile(__FILE__);

class CControllerLog
{
	public static function GetNameArray()
	{
		if(ControllerIsSharedMode())
			return Array(
				'REMOTE_COMMAND'=>GetMessage("CTRLR_LOG_TYPE_REMOTE_COMMAND"),
				'SET_SETTINGS'=>GetMessage("CTRLR_LOG_TYPE_SET_SETTINGS"),
				'SITE_UPDATE'=>GetMessage("CTRLR_LOG_TYPE_SITE_UPDATE"),
				'REGISTRATION'=>GetMessage("CTRLR_LOG_TYPE_REGISTRATION"),
				'UNREGISTRATION'=>GetMessage("CTRLR_LOG_TYPE_UNREGISTRATION"),
				'SITE_UPDATE_KERNEL'=>GetMessage("CTRLR_LOG_TYPE_SITE_UPDATE_KERNEL"),
				'SITE_UPDATE_KERNEL_DB'=>GetMessage("CTRLR_LOG_TYPE_SITE_UPDATE_KERNEL_DB"),
				'UPDATE_COUNTERS'=>GetMessage("CTRLR_LOG_TYPE_UPDATE_COUNTERS"),
				'AUTH'=>GetMessage("CTRLR_LOG_TYPE_AUTH"),
				'SITE_CLOSING'=>GetMessage("CTRLR_LOG_TYPE_SITE_CLOSE"),
			);


		return Array(
			'REMOTE_COMMAND'=>GetMessage("CTRLR_LOG_TYPE_REMOTE_COMMAND"),
			'SET_SETTINGS'=>GetMessage("CTRLR_LOG_TYPE_SET_SETTINGS"),
			'SITE_UPDATE'=>GetMessage("CTRLR_LOG_TYPE_SITE_UPDATE"),
			'REGISTRATION'=>GetMessage("CTRLR_LOG_TYPE_REGISTRATION"),
			'UNREGISTRATION'=>GetMessage("CTRLR_LOG_TYPE_UNREGISTRATION"),
			'UPDATE_COUNTERS'=>GetMessage("CTRLR_LOG_TYPE_UPDATE_COUNTERS"),
			'AUTH'=>GetMessage("CTRLR_LOG_TYPE_AUTH"),
			'SITE_CLOSING'=>GetMessage("CTRLR_LOG_TYPE_SITE_CLOSE"),
		);
	}

	public static function CheckFields(&$arFields, $ID = false)
	{
		$arErrMsg = Array();

		if($ID>0)
			unset($arFields["ID"]);

		global $DB;
		if(($ID===false || is_set($arFields, "NAME")) && strlen($arFields["NAME"])<=0)
			$arMsg[] = array("id"=>"NAME", "text"=> GetMessage("CTRLR_LOG_ERR_NAME"));

		if(($ID===false || is_set($arFields, "CONTROLLER_MEMBER_ID")) && IntVal($arFields["CONTROLLER_MEMBER_ID"])<=0)
		{
			if(is_set($arFields, "NAME") && $arFields["NAME"] == "SITE_UPDATE_KERNEL")
				$arFields["CONTROLLER_MEMBER_ID"] = 0;
			else
				$arMsg[] = array("id"=>"CONTROLLER_MEMBER_ID", "text"=> GetMessage("CTRLR_LOG_ERR_UID"));
		}

		if(count($arMsg)>0)
		{
			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		return true;
	}

	public static function Add($arFields)
	{
		global $DB, $USER;

		if(!CControllerLog::CheckFields($arFields))
			return false;

		if(!isset($arFields["USER_ID"]) && is_object($USER))
			$arFields["USER_ID"] = $USER->GetId();

		unset($arFields["TIMESTAMP_X"]);
		unset($arFields["~TIMESTAMP_X"]);

		$ID = $DB->Add("b_controller_log", $arFields, array("DESCRIPTION"));

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;
		if(!CControllerLog::CheckFields($arFields, $ID))
			return false;

		unset($arFields["TIMESTAMP_X"]);
		$arFields["~TIMESTAMP_X"] = $DB->CurrentTimeFunction();

		$arUpdateBinds = array();
		$strUpdate = $DB->PrepareUpdateBind("b_controller_log", $arFields, "", false, $arUpdateBinds);

		$strSql = "UPDATE b_controller_log SET ".$strUpdate." WHERE ID=".intval($ID);

		$arBinds = array();
		foreach($arUpdateBinds as $field_id)
			$arBinds[$field_id] = $arFields[$field_id];

		$DB->QueryBind($strSql, $arBinds);

		return true;
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		$DB->Query("DELETE FROM b_controller_log WHERE ID=".$ID);
		return true;
	}

	public static function GetList($arOrder = Array(), $arFilter = Array())
	{
		global $DB;

		static $arFields = array(
			"ID" => Array("FIELD_NAME" => "L.ID", "FIELD_TYPE" => "int"),
			"CONTROLLER_MEMBER_ID" => Array("FIELD_NAME" => "L.CONTROLLER_MEMBER_ID", "FIELD_TYPE" => "int"),
			"NAME" => Array("FIELD_NAME" => "L.NAME", "FIELD_TYPE" => "string"),
			"TIMESTAMP_X" => Array("FIELD_NAME" => "L.TIMESTAMP_X", "FIELD_TYPE" => "datetime"),
			"TASK_ID" => Array("FIELD_NAME" => "L.TASK_ID", "FIELD_TYPE" => "int"),
			"TASK_NAME" => Array("FIELD_NAME" => "T.TASK_ID", "FIELD_TYPE" => "string"),
			"USER_ID" => Array("FIELD_NAME" => "L.USER_ID", "FIELD_TYPE" => "int"),
			"USER_NAME" => Array("FIELD_NAME" => "L.USER_ID", "FIELD_TYPE" => "string"),
			"USER_LAST_NAME" => Array("FIELD_NAME" => "U.LAST_NAME", "FIELD_TYPE" => "string"),
			"USER_LOGIN" => Array("FIELD_NAME" => "U.LOGIN", "FIELD_TYPE" => "string"),
			"STATUS" => Array("FIELD_NAME" => "L.STATUS", "FIELD_TYPE" => "string"),
			"CONTROLLER_MEMBER_NAME" => Array("FIELD_NAME" => "M.NAME", "FIELD_TYPE" => "string"),
			"CONTROLLER_MEMBER_URL" => Array("FIELD_NAME" => "M.URL", "FIELD_TYPE" => "string"),
		);

		$obWhere = new CSQLWhere;
		$obWhere->SetFields($arFields);

		$arFilterNew = Array();
		foreach($arFilter as $k=>$value)
			if(strlen($value)>0 || $value === false)
				$arFilterNew[$k]=$value;

		$strWhere = $obWhere->GetQuery($arFilterNew);

		$strSql =
			"SELECT L.*, ".
			"	M.NAME as CONTROLLER_MEMBER_NAME, M.URL as CONTROLLER_MEMBER_URL, ".
			"	U.NAME as USER_NAME, U.LAST_NAME as USER_LAST_NAME, U.LOGIN as USER_LOGIN, ".
			"	T.TASK_ID as TASK_NAME, ".
			"	".$DB->DateToCharFunction("L.TIMESTAMP_X")."	as TIMESTAMP_X ".
			"FROM b_controller_log L  ".
			"	LEFT JOIN b_controller_member M ON L.CONTROLLER_MEMBER_ID=M.ID ".
			"	LEFT JOIN b_controller_task T ON T.ID=L.TASK_ID ".
			"	LEFT JOIN b_user U ON U.ID=L.USER_ID ".
			(strlen($strWhere)<=0 ? "" : "WHERE ".$strWhere)." ".
			CControllerAgent::_OrderBy($arOrder, $arFields);

		$dbr = $DB->Query($strSql);
		$dbr->is_filtered = (strlen($strWhere)>0);
		return $dbr;
	}

	public static function GetByID($ID)
	{
		return CControllerLog::GetList(Array(), Array("ID"=>IntVal($ID)));
	}
}
?>
