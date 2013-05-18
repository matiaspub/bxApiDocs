<?
IncludeModuleLangFile(__FILE__);

class CAllControllerCounter
{
	public static function GetTypeArray()
	{
		return array(
			'I' => GetMessage("CTRL_COUNTER_TYPE_INT"),
			'F' => GetMessage("CTRL_COUNTER_TYPE_FLOAT"),
			'S' => GetMessage("CTRL_COUNTER_TYPE_STRING"),
			'D' => GetMessage("CTRL_COUNTER_TYPE_DATETIME"),
		);
	}

	public static function GetTypeColumn($TYPE)
	{
		switch($TYPE)
		{
			case 'I': return "VALUE_INT";
			case 'F': return "VALUE_FLOAT";
			case 'D': return "VALUE_DATE";
			case 'S': return "VALUE_STRING";
			default: return "VALUE_STRING";
		}
	}

	public static function GetTypeUserType($TYPE)
	{
		switch($TYPE)
		{
			case 'I': return "int";
			case 'F': return "float";
			case 'D': return "datetime";
			case 'S': return "string";
			default: return "string";
		}
	}

	public static function CheckFields(&$arFields, $ID = false)
	{
		global $APPLICATION;
		$arErrMsg = Array();

		if($ID > 0)
			unset($arFields["ID"]);

		if(($ID===false || array_key_exists("NAME", $arFields)) && strlen($arFields["NAME"])<=0)
			$arMsg[] = array("id"=>"NAME", "text"=> GetMessage("CTRL_COUNTER_ERR_NAME"));

		if(($ID===false || array_key_exists("COUNTER_TYPE", $arFields)) && !array_key_exists($arFields["COUNTER_TYPE"], CControllerCounter::GetTypeArray()))
			$arFields["COUNTER_TYPE"] = 'I';

		if(array_key_exists("COUNTER_FORMAT", $arFields) && !array_key_exists($arFields["COUNTER_FORMAT"], CControllerCounter::GetFormatArray()))
			$arFields["COUNTER_FORMAT"] = false;

		if(($ID===false || array_key_exists("COMMAND", $arFields)) && strlen($arFields["COMMAND"])<=0)
			$arMsg[] = array("id"=>"COMMAND", "text"=> GetMessage("CTRL_COUNTER_ERR_COMMAND"));

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		return true;
	}

	public static function UpdateGroups($ID, $arGroups)
	{
		global $DB;
		$ID = intval($ID);

		$DB->Query("DELETE FROM b_controller_counter_group WHERE CONTROLLER_COUNTER_ID = ".$ID);
		if(is_array($arGroups) && !empty($arGroups))
		{
			$DB->Query("
				INSERT INTO b_controller_counter_group
				(CONTROLLER_GROUP_ID, CONTROLLER_COUNTER_ID)
				SELECT ID, ".$ID."
				FROM b_controller_group
				WHERE ID in (".implode(", ", array_map("intval", $arGroups)).")
			");
		}
	}

	public static function SetGroupCounters($CONTROLLER_GROUP_ID, $arCounters)
	{
		global $DB;
		$CONTROLLER_GROUP_ID = intval($CONTROLLER_GROUP_ID);

		$DB->Query("DELETE FROM b_controller_counter_group WHERE CONTROLLER_GROUP_ID = ".$CONTROLLER_GROUP_ID);
		if(is_array($arCounters) && !empty($arCounters))
		{
			$DB->Query("
				INSERT INTO b_controller_counter_group
				(CONTROLLER_GROUP_ID, CONTROLLER_COUNTER_ID)
				SELECT ".$CONTROLLER_GROUP_ID.", ID
				FROM b_controller_counter
				WHERE ID in (".implode(", ", array_map("intval", $arCounters)).")
			");
		}
	}

	public static function Add($arFields)
	{
		global $DB;

		if(!CControllerCounter::CheckFields($arFields))
			return false;

		unset($arFields["TIMESTAMP_X"]);
		$arFields["~TIMESTAMP_X"] = $DB->CurrentTimeFunction();

		$ID = $DB->Add("b_controller_counter", $arFields, array("COMMAND"));

		if(array_key_exists("CONTROLLER_GROUP_ID", $arFields))
			CControllerCounter::UpdateGroups($ID, $arFields["CONTROLLER_GROUP_ID"]);

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;
		$ID = intval($ID);

		if(!CControllerCounter::CheckFields($arFields, $ID))
			return false;

		unset($arFields["TIMESTAMP_X"]);
		$arFields["~TIMESTAMP_X"] = $DB->CurrentTimeFunction();

		$arUpdateBinds = array();
		$strUpdate = $DB->PrepareUpdateBind("b_controller_counter", $arFields, "", false, $arUpdateBinds);

		$strSql = "UPDATE b_controller_counter SET ".$strUpdate." WHERE ID=".$ID;

		$arBinds = array();
		foreach($arUpdateBinds as $field_id)
			$arBinds[$field_id] = $arFields[$field_id];

		if(!$DB->QueryBind($strSql, $arBinds))
			return false;

		if(array_key_exists("CONTROLLER_GROUP_ID", $arFields))
			CControllerCounter::UpdateGroups($ID, $arFields["CONTROLLER_GROUP_ID"]);

		return true;
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = intval($ID);

		$DB->Query("DELETE FROM b_controller_counter_value WHERE CONTROLLER_COUNTER_ID = ".$ID);
		$DB->Query("DELETE FROM b_controller_counter_group WHERE CONTROLLER_COUNTER_ID = ".$ID);
		$DB->Query("DELETE FROM b_controller_counter WHERE ID = ".$ID);

		return true;
	}

	public static function GetList($arOrder=false, $arFilter=false)
	{
		global $DB;

		if(!is_array($arOrder))
			$arOrder = array();

		$arQueryOrder = array();
		foreach($arOrder as $strColumn => $strDirection)
		{
			$strColumn = strtoupper($strColumn);
			$strDirection = strtoupper($strDirection)=="ASC"? "ASC": "DESC";
			switch($strColumn)
			{
				case "ID":
				case "NAME":
					$arSelect[] = $strColumn;
					$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
					break;
			}
		}

		$obQueryWhere = new CSQLWhere;
		$arFields = array(
			"ID" => array(
				"TABLE_ALIAS" => "cc",
				"FIELD_NAME" => "cc.ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"CONTROLLER_GROUP_ID" => array(
				"TABLE_ALIAS" => "ccg",
				"FIELD_NAME" => "ccg.CONTROLLER_GROUP_ID",
				"FIELD_TYPE" => "int",
				"JOIN" => "INNER JOIN b_controller_counter_group ccg ON ccg.CONTROLLER_COUNTER_ID = cc.ID",
				"LEFT_JOIN" => "LEFT JOIN b_controller_counter_group ccg ON ccg.CONTROLLER_COUNTER_ID = cc.ID",
			),
		);
		$obQueryWhere->SetFields($arFields);

		if(!is_array($arFilter))
			$arFilter = array();
		$strQueryWhere = $obQueryWhere->GetQuery($arFilter);

		$bDistinct = $obQueryWhere->bDistinctReqired;

		$strSql = "
			SELECT ".($bDistinct? "DISTINCT": "")."
				cc.*
				,".$DB->DateToCharFunction("cc.TIMESTAMP_X")." TIMESTAMP_X
			FROM
				b_controller_counter cc
			".$obQueryWhere->GetJoins()."
		";

		if($strQueryWhere)
		{
			$strSql .= "
				WHERE
				".$strQueryWhere."
			";
		}

		if(count($arQueryOrder) > 0)
		{
			$strSql .= "
				ORDER BY
				".implode(", ", $arQueryOrder)."
			";
		}

		return $DB->Query($strSql);
	}

	public static function GetArrayByID($ID)
	{
		global $DB;
		$ID = intval($ID);

		$rs = CControllerCounter::GetList(array(), array("=ID" => $ID));
		$ar = $rs->Fetch();
		if(is_array($ar))
		{
			//GetCounterGroups
			$ar["CONTROLLER_GROUP_ID"] = array();
			$rs = $DB->Query("SELECT CONTROLLER_GROUP_ID FROM b_controller_counter_group WHERE CONTROLLER_COUNTER_ID = ".$ID);
			while($a = $rs->Fetch())
				$ar["CONTROLLER_GROUP_ID"][$a["CONTROLLER_GROUP_ID"]] = $a["CONTROLLER_GROUP_ID"];
		}
		return $ar;
	}

	public static function GetMemberCounters($CONTROLLER_MEMBER_ID)
	{
		global $DB;
		$CONTROLLER_MEMBER_ID = intval($CONTROLLER_MEMBER_ID);

		$rs = $DB->Query("
			SELECT
				cc.ID
				,cc.NAME
				,cc.COUNTER_TYPE
				,cc.COUNTER_FORMAT
				,cc.COMMAND
			FROM
				b_controller_member cm
				INNER JOIN b_controller_counter_group ccg ON ccg.CONTROLLER_GROUP_ID = cm.CONTROLLER_GROUP_ID
				INNER JOIN b_controller_counter cc ON cc.ID = ccg.CONTROLLER_COUNTER_ID
			WHERE
				cm.ID = ".$CONTROLLER_MEMBER_ID."
			ORDER BY
				cc.NAME
		");

		return $rs;
	}

	public static function UpdateMemberValues($CONTROLLER_MEMBER_ID, $arValues)
	{
		global $DB;
		$CONTROLLER_MEMBER_ID = intval($CONTROLLER_MEMBER_ID);

		$DB->Query("DELETE FROM b_controller_counter_value WHERE CONTROLLER_MEMBER_ID = ".$CONTROLLER_MEMBER_ID);
		foreach($arValues as $CONTROLLER_COUNTER_ID => $value)
		{
			$CONTROLLER_COUNTER_ID = intval($CONTROLLER_COUNTER_ID);
			if($CONTROLLER_COUNTER_ID > 0)
			{
				if(isset($arValues["DATE_FORMAT"]) && CheckDateTime($value, $arValues["DATE_FORMAT"]))
					$sqlDate = $DB->CharToDateFunction($DB->FormatDate($value, $arValues["DATE_FORMAT"], CLang::GetDateFormat("FULL", LANGUAGE_ID)));
				elseif(CheckDateTime($value, "YYYY-MM-DD HH:MI:SS"))
					$sqlDate = $DB->CharToDateFunction($DB->FormatDate($value, "YYYY-MM-DD HH:MI:SS", CLang::GetDateFormat("FULL", LANGUAGE_ID)));
				else
					$sqlDate = "NULL";

				$DB->Query("
					INSERT INTO b_controller_counter_value
					(CONTROLLER_MEMBER_ID, CONTROLLER_COUNTER_ID, VALUE_INT, VALUE_FLOAT, VALUE_DATE, VALUE_STRING)
					SELECT
						cm.ID
						,cc.ID
						,".intval($value)."
						,".roundDB($value)."
						,".$sqlDate."
						,'".$DB->ForSQL($value, 255)."'
					FROM
						b_controller_member cm
						INNER JOIN b_controller_counter_group ccg ON ccg.CONTROLLER_GROUP_ID = cm.CONTROLLER_GROUP_ID
						INNER JOIN b_controller_counter cc ON cc.ID = ccg.CONTROLLER_COUNTER_ID
					WHERE
						cm.ID = ".$CONTROLLER_MEMBER_ID."
						and cc.ID = ".$CONTROLLER_COUNTER_ID."
				");
			}
		}

		return true;
	}

	public static function GetFormatArray()
	{
		return array(
			'' => GetMessage("CTRL_COUNTER_FORMAT_NONE"),
			'F' => GetMessage("CTRL_COUNTER_TYPE_FILE_SIZE"),
		);
	}

	public static function FormatValue($value, $format)
	{
		if($format === "F")
			return CFile::FormatSize($value);
		else
			return $value;
	}
}

class CControllerCounterResult extends CDBResult
{
	public static function CControllerCounterResult($res)
	{
		parent::CDBResult($res);
	}

	public static function Fetch()
	{
		$res = parent::Fetch();
		if($res)
			$res["DISPLAY_VALUE"] = CControllerCounter::FormatValue($res["VALUE"], $res["COUNTER_FORMAT"]);
		return $res;
	}
}

?>
