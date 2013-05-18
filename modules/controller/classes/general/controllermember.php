<?
IncludeModuleLangFile(__FILE__);

class CAllControllerMember
{
	/******************************* Public methods ************************************/
	public static function CheckMember($member_id, $member_url = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$dbr_member = CControllerMember::GetById($member_id);
		$ar_member = $dbr_member->Fetch();
		if (!$ar_member)
		{
			$e = new CApplicationException(GetMessage("CTRLR_MEM_ERR1")." ".htmlspecialcharsex($member_id));
			$APPLICATION->ThrowException($e);
			return false;
		}

		$oRequest = new CControllerServerRequestTo($ar_member, "ping");
		$oResponse = $oRequest->Send();
		if ($oResponse !== false)
		{
			return $oResponse->OK();
		}

		return false;
	}

	public static function RunCommandRedirect($member_id, $command, $arParameters = Array(), $log = true)
	{
		if(!($arMember = CControllerMember::GetMember($member_id)))
			return false;

		$command_id = CControllerMember::AddCommand($arMember["MEMBER_ID"], $command, $arParameters, false);

		$arVars = Array("command_id"=>$command_id);
		$oRequest = new CControllerServerRequestTo($arMember, "run", $arVars);

		if($log)
		{
			$arControllerLog = Array(
				'NAME'=>'REMOTE_COMMAND',
				'CONTROLLER_MEMBER_ID'=>$member_id,
				'DESCRIPTION'=>GetMessage("CTRLR_MEM_LOG_DESC_COMMAND").$command,
				'STATUS'=>'Y'
			);
			CControllerLog::Add($arControllerLog);
		}

		$oRequest->RedirectRequest($arMember["URL"]."/bitrix/admin/main_controller.php");

		return true;
	}

	public static function RunCommandWithLog($member_id, $command, $arParameters = Array(), $task_id=false, $operation = 'run')
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$arControllerLog = Array(
				'NAME'=>'REMOTE_COMMAND',
				'CONTROLLER_MEMBER_ID'=>$member_id,
				'DESCRIPTION'=>"command: '$command'\n\noperation: '$operation'",
				'STATUS'=>'Y'
			);

		$res = CControllerMember::RunCommand($member_id, $command, $arParameters, $task_id, $operation);
		if($res === false)
		{
			$e = $APPLICATION->GetException();
			$arControllerLog['DESCRIPTION'] = $e->GetString()."\r\n".$arControllerLog['DESCRIPTION'];
			$arControllerLog['STATUS'] = 'N';
		}

		CControllerLog::Add($arControllerLog);
		return $res;
	}

	public static function RunCommand($member_id, $command, $arParameters = Array(), $task_id=false, $operation = 'run')
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		global $DB;

		if(!($arMember = CControllerMember::GetMember($member_id)))
			return false;

		if($operation == "run_immediate")
		{
			$arVars = array("command"=>$command);
		}
		else
		{
			$command_id = CControllerMember::AddCommand($arMember["MEMBER_ID"], $command, $arParameters, $task_id);

			if($command_id !== false)
				$arVars = array("command_id" => $command_id);
			else
				$arVars = array();
		}

		if(empty($arVars))
		{
			if(strlen($DB->GetErrorMessage()) > 0)
				$e = new CApplicationException(GetMessage("CTRLR_MEM_ERR3")." ".$DB->GetErrorMessage());
			else
				$e = new CApplicationException(GetMessage("CTRLR_MEM_ERR2"));
			$APPLICATION->ThrowException($e);
			return false;
		}

		$oRequest = new CControllerServerRequestTo($arMember, $operation, $arVars);

		/*@var $oResponse CControllerServerResponseFrom*/
		if(($oResponse = $oRequest->Send())===false)
			return false;

		if(!$oResponse->Check())
		{
			$e = new CApplicationException(GetMessage("CTRLR_MEM_ERR2"));
			$APPLICATION->ThrowException($e);
			return false;
		}

		if($oResponse->OK() || preg_match("/^(STP0|FIN)/", $oResponse->text))
			return $oResponse->text;

		$str = strlen($oResponse->text)? $oResponse->text: $oResponse->status;
		$e = new CApplicationException(GetMessage("CTRLR_MEM_ERR3")." ".$str);
		$APPLICATION->ThrowException($e);

		return false;
	}

	public static function SendFileWithLog($member_id, $file)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$arControllerLog = array(
			'NAME' => 'SEND_FILE',
			'CONTROLLER_MEMBER_ID' => $member_id,
			'DESCRIPTION' => "sendfile",
			'STATUS' => 'Y',
		);

		$res = CControllerMember::SendFile();
		if ($res === false)
		{
			$e = $APPLICATION->GetException();
			$arControllerLog['DESCRIPTION'] = $e->GetString()."\n".$arControllerLog['DESCRIPTION'];
			$arControllerLog['STATUS'] = 'N';
		}

		CControllerLog::Add($arControllerLog);
		return $res;

	}

	public static function SendFile()
	{
		return false;
	}

	public static function UpdateCounters($member_id, $task_id = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		global $DB;
		$member_id = intval($member_id);

		$arMember = CControllerMember::GetMember($member_id);
		if(!$arMember)
		{
			$e = new CApplicationException("Member #".$member_id." is not found.");
			$APPLICATION->ThrowException($e);
			return false;
		}

		$dbr_group = CControllerGroup::GetByID($arMember["CONTROLLER_GROUP_ID"]);
		if(!$ar_group = $dbr_group->Fetch())
		{
			$e = new CApplicationException("Group #".$arMember["CONTROLLER_GROUP_ID"]." is not found.");
			$APPLICATION->ThrowException($e);
			return false;
		}

		$strCommand = '$arResult = array("DATE_FORMAT" => CSite::GetDateFormat());';
		if($ar_group["CHECK_COUNTER_FREE_SPACE"] == "Y")
			$strCommand .= "\n".'$quota = new CDiskQuota(); $disk_quota = $quota->GetDiskQuota(); if(is_bool($disk_quota))$arResult["COUNTER_FREE_SPACE"] = -1; else $arResult["COUNTER_FREE_SPACE"] = round($disk_quota/1024, 2);';
		if($ar_group["CHECK_COUNTER_SITES"] == "Y")
			$strCommand .= "\n".'$dbr = CSite::GetList(($by="sort"), ($order="asc"), array("ACTIVE"=>Y)); $arResult["COUNTER_SITES"] = $dbr->SelectedRowsCount();';
		if($ar_group["CHECK_COUNTER_USERS"] == "Y")
			$strCommand .= "\n".'$dbr = $GLOBALS["DB"]->Query("SELECT COUNT(1) as USER_COUNT FROM b_user U WHERE (U.EXTERNAL_AUTH_ID IS NULL OR U.EXTERNAL_AUTH_ID=\'\')"); $ar = $dbr->Fetch(); $arResult["COUNTER_USERS"] = $ar["USER_COUNT"];';
		if($ar_group["CHECK_COUNTER_LAST_AUTH"] == "Y")
			$strCommand .= "\n".'$dbr = $GLOBALS["DB"]->Query("SELECT MAX(U.LAST_LOGIN) as LAST_LOGIN FROM b_user U"); $ar = $dbr->Fetch(); $arResult["COUNTER_LAST_AUTH"] = $ar["LAST_LOGIN"];';

		$rsCounters = CControllerCounter::GetMemberCounters($member_id);
		while($arCounter = $rsCounters->Fetch())
			$strCommand .= "\n".'$arResult['.$arCounter['ID'].'] = eval("'.EscapePHPString($arCounter["COMMAND"]).'");';

		$strCommand .= "\n".'foreach($arResult as $k=>$v) echo urlencode($k),"=",urlencode($v),"&";';

		foreach(GetModuleEvents("controller", "OnBeforeUpdateCounters", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($arMember, $ar_group, &$strCommand));
		}

		$command_result = CControllerMember::RunCommand($member_id, $strCommand, array(), $task_id, 'run_immediate');
		if($command_result===false)
		{
			$e = $APPLICATION->GetException();
			if(!is_object($e))
			{
				$e = new CApplicationException("Command execution error.");
				$APPLICATION->ThrowException($e);
			}
			return false;
		}

		$ar_command_result = array();
		parse_str($command_result, $ar_command_result);

		//Try to guess encoding and convert to controller site charset
		foreach($ar_command_result as $k => $v)
			$ar_command_result[$k] = CUtil::ConvertToLangCharset($v);

		$arFields = array(
			"TIMESTAMP" => $arMember["TIMESTAMP_X"],
			"~COUNTERS_UPDATED" => $DB->CurrentTimeFunction(),
		);
		if(array_key_exists('COUNTER_FREE_SPACE', $ar_command_result))
			$arFields['COUNTER_FREE_SPACE'] = intval($ar_command_result['COUNTER_FREE_SPACE']);
		if(array_key_exists('COUNTER_SITES', $ar_command_result))
			$arFields['COUNTER_SITES'] = intval($ar_command_result['COUNTER_SITES']);
		if(array_key_exists('COUNTER_USERS', $ar_command_result))
			$arFields['COUNTER_USERS'] = intval($ar_command_result['COUNTER_USERS']);
		if(array_key_exists('COUNTER_LAST_AUTH', $ar_command_result))
			$arFields['COUNTER_LAST_AUTH'] = $DB->FormatDate($ar_command_result['COUNTER_LAST_AUTH'], 'YYYY-MM-DD HH:MI:SS', CSite::GetDateFormat());

		if(!CControllerMember::Update($member_id, $arFields))
		{
			$e = $APPLICATION->GetException();
			$e = new CApplicationException(GetMessage("CTRLR_MEM_COUNTERS_ERR1").$e->GetString());
			$APPLICATION->ThrowException($e);
			return false;
		}

		CControllerCounter::UpdateMemberValues($member_id, $ar_command_result);

		return $arFields;
	}


	public static function SetGroupSettings($member_id, $task_id = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!($arMember = CControllerMember::GetMember($member_id)))
			return false;

		$arControllerLog = Array(
				'NAME'=>'SET_SETTINGS',
				'CONTROLLER_MEMBER_ID'=>$arMember['ID'],
				'DESCRIPTION'=>GetMessage("CTRLR_MEM_LOG_DESC_GROUP").' '.$arMember["CONTROLLER_GROUP_ID"],
				'STATUS'=>'Y'
			);

		$result = false;
		if($strCommand = CControllerGroup::GetGroupSettings($arMember["CONTROLLER_GROUP_ID"]))
		{
			$dbr_group = CControllerGroup::GetByID($arMember["CONTROLLER_GROUP_ID"]);
			if($ar_group = $dbr_group->Fetch())
			{
				if($ar_group["TRIAL_PERIOD"]>0 && $arMember["IN_GROUP_FROM"])
				{
					$tFrom = MakeTimeStamp($arMember["IN_GROUP_FROM"], FORMAT_DATE);
					$tTo = $tFrom + $ar_group["TRIAL_PERIOD"]*24*60*60 - 1;

					$strCommand .= "\r\nCOption::SetOptionString('main', '~controller_trial', '".$tTo."');";
				}
				else
					$strCommand .= "\r\nCOption::RemoveOption('main', '~controller_trial');";
			}

			$result = CControllerMember::RunCommand($member_id, $strCommand, array(), $task_id);
		}
		else
		{
			$e = new CApplicationException(GetMessage("CTRLR_MEM_ERR4")." ".$arMember["CONTROLLER_GROUP_ID"]);
			$APPLICATION->ThrowException($e);
		}

		if($task_id === false)
		{
			if($result === false)
			{
				$e = $APPLICATION->GetException();
				$arControllerLog['DESCRIPTION'] = $e->GetString();
				$arControllerLog['STATUS'] = 'N';
			}

			CControllerLog::Add($arControllerLog);
		}
		return $result;
	}

	public static function RemoveGroupSettings($member_id)
	{
		return true;
	}

	public static function addHistoryNote($CONTROLLER_MEMBER_ID, $strNote)
	{
		global $DB, $USER;

		if(is_object($USER))
			$USER_ID = $USER->GetID();
		else
			$USER_ID = 1;

		$DB->Add("b_controller_member_log", array(
			"CONTROLLER_MEMBER_ID" => $CONTROLLER_MEMBER_ID,
			"USER_ID" => $USER_ID,
			"~CREATED_DATE" => $DB->CurrentTimeFunction(),
			"FIELD" => "NOTE",
			"NOTES" => $strNote,
		), array("NOTES"));
	}

	public static function logChanges($CONTROLLER_MEMBER_ID, $arFieldsOld, $arFieldsNew, $strNote)
	{
		global $DB, $USER;
		static $arFieldsToLog = array("CONTROLLER_GROUP_ID", "SITE_ACTIVE");

		if(is_object($USER))
			$USER_ID = $USER->GetID();
		else
			$USER_ID = 1;

		foreach($arFieldsToLog as $FIELD)
		{
			if(
				isset($arFieldsOld[$FIELD])
				&& isset($arFieldsNew[$FIELD])
				&& $arFieldsOld[$FIELD] != $arFieldsNew[$FIELD]
			)
				$DB->Add("b_controller_member_log", array(
					"CONTROLLER_MEMBER_ID" => $CONTROLLER_MEMBER_ID,
					"USER_ID" => $USER_ID,
					"~CREATED_DATE" => $DB->CurrentTimeFunction(),
					"FIELD" => $FIELD,
					"FROM_VALUE" => $arFieldsOld[$FIELD],
					"TO_VALUE" => $arFieldsNew[$FIELD],
					"NOTES" => $strNote,
				), array("FROM_VALUE", "TO_VALUE", "NOTES"));
		}
	}

	public static function getLog($arFilter)
	{
		global $DB;

		$obQueryWhere = new CSQLWhere;
		$arFields = array(
			"CONTROLLER_MEMBER_ID" => array(
				"TABLE_ALIAS" => "l",
				"FIELD_NAME" => "l.CONTROLLER_MEMBER_ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"FIELD" => array(
				"TABLE_ALIAS" => "l",
				"FIELD_NAME" => "l.FIELD",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
		);
		$obQueryWhere->SetFields($arFields);

		if(!is_array($arFilter))
			$arFilter = array();
		$strQueryWhere = $obQueryWhere->GetQuery($arFilter);

		$strSql = "
			SELECT l.*
				,".$DB->DateToCharFunction("l.CREATED_DATE", "FULL")." CREATED_DATE
				,".$DB->Concat("'('", "U.LOGIN", "') '", "U.NAME", "' '", "U.LAST_NAME")." USER_ID_USER
			FROM b_controller_member_log l
			LEFT JOIN b_user U ON U.ID = l.USER_ID
		";

		if($strQueryWhere)
		{
			$strSql .= "
				WHERE
				".$strQueryWhere."
			";
		}

		$strSql .= "
			ORDER BY l.ID DESC
		";

		return $DB->Query($strSql);
	}

	public static function Add($arFields)
	{
		/** @global CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;
		/** @global CDatabase $DB */
		global $DB;

		if(!CControllerMember::CheckFields($arFields))
			return false;

		if(!$USER_FIELD_MANAGER->CheckFields("CONTROLLER_MEMBER", 0, $arFields))
			return false;

		$arFields["SITE_ACTIVE"] = "X";
		unset($arFields["TIMESTAMP_X"]);
		unset($arFields["~TIMESTAMP_X"]);

		$ID = $DB->Add("b_controller_member", $arFields, array("NOTES"));

		CControllerMember::UnregisterExpiredAgent($ID);
		$USER_FIELD_MANAGER->Update("CONTROLLER_MEMBER", $ID, $arFields);

		return $ID;

	}

	public static function Update($ID, $arFields, $strNote = "")
	{
		/** @global CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;
		/** @global CDatabase $DB */
		global $DB;

		$dbr_m = CControllerMember::GetByID($ID);
		$ar_m = $dbr_m->Fetch();

		if(!CControllerMember::CheckFields($arFields, $ID))
			return false;

		if(!$USER_FIELD_MANAGER->CheckFields("CONTROLLER_MEMBER", $ID, $arFields))
			return false;

		if(
			isset($arFields["CONTROLLER_GROUP_ID"])
			&& $ar_m["CONTROLLER_GROUP_ID"] != $arFields["CONTROLLER_GROUP_ID"]
			&& !isset($arFields["IN_GROUP_FROM"])
		)
			$arFields["~IN_GROUP_FROM"] = $DB->CurrentTimeFunction();

		unset($arFields["TIMESTAMP_X"]);
		if(array_key_exists("TIMESTAMP", $arFields))
			$arFields["~TIMESTAMP_X"] = $DB->CharToDateFunction($arFields["TIMESTAMP"]);
		else
			$arFields["~TIMESTAMP_X"] = $DB->CurrentTimeFunction();

		$arUpdateBinds = array();
		$strUpdate = $DB->PrepareUpdateBind("b_controller_member", $arFields, "", false, $arUpdateBinds);

		$strSql = "UPDATE b_controller_member SET ".$strUpdate." WHERE ID=".intval($ID);

		$arBinds = array();
		foreach($arUpdateBinds as $field_id)
			$arBinds[$field_id] = $arFields[$field_id];

		$DB->QueryBind($strSql, $arBinds);

		$dbr_m = CControllerMember::GetByID($ID);
		$ar_n = $dbr_m->Fetch();

		CControllerMember::logChanges($ID, $ar_m, $ar_n, $strNote);

		$USER_FIELD_MANAGER->Update("CONTROLLER_MEMBER", $ID, $arFields);

		if(
			isset($arFields["CONTROLLER_GROUP_ID"])
			&& $ar_m["CONTROLLER_GROUP_ID"] != $arFields["CONTROLLER_GROUP_ID"]
		)
		{
			CControllerMember::SetGroupSettings($ID);
		}

		if(
			isset($arFields["ACTIVE"]) && $ar_m["ACTIVE"] != $arFields["ACTIVE"]
			|| isset($arFields["DATE_ACTIVE_FROM"]) && $ar_m["DATE_ACTIVE_FROM"] != $arFields["DATE_ACTIVE_FROM"]
			|| isset($arFields["DATE_ACTIVE_TO"]) && $ar_m["DATE_ACTIVE_TO"] != $arFields["DATE_ACTIVE_TO"]
		)
		{
			CControllerMember::UnregisterExpiredAgent($ID);
		}

		return true;
	}

	public static function Delete($ID)
	{
		global $DB, $USER_FIELD_MANAGER;
		$ID = intval($ID);

		$USER_FIELD_MANAGER->Delete("CONTROLLER_MEMBER", $ID);
		$DB->Query("DELETE FROM b_controller_log WHERE CONTROLLER_MEMBER_ID = ".$ID);
		$DB->Query("DELETE FROM b_controller_task WHERE CONTROLLER_MEMBER_ID = ".$ID);
		$DB->Query("DELETE FROM b_controller_counter_value WHERE CONTROLLER_MEMBER_ID = ".$ID);
		$DB->Query("DELETE FROM b_controller_member_log WHERE CONTROLLER_MEMBER_ID = ".$ID);
		$DB->Query("DELETE FROM b_controller_member WHERE ID = ".$ID);

		return true;
	}

	public static function GetList($arOrder = array(), $arFilter = array(), $arSelect = array(), $arOptions = array())
	{
		global $DB, $USER_FIELD_MANAGER;

		$bEmptySelect = !is_array($arSelect) || empty($arSelect);

		if(is_array($arOrder) && is_array($arSelect))
			foreach($arOrder as $k => $v)
				$arSelect[] = $k;

		$obUserFieldsSql = new CUserTypeSQL;
		$obUserFieldsSql->SetEntity("CONTROLLER_MEMBER", "M.ID");
		$obUserFieldsSql->SetSelect($arSelect);
		$obUserFieldsSql->SetFilter($arFilter);
		$obUserFieldsSql->SetOrder($arOrder);

		static $arFields = array(
			"ID" => array(
				"FIELD_NAME" => "M.ID",
				"FIELD_TYPE" => "int",
			),
			"MEMBER_ID" => array(
				"FIELD_NAME" => "M.MEMBER_ID",
				"FIELD_TYPE" => "string",
			),
			"SECRET_ID" => array(
				"FIELD_NAME" => "M.SECRET_ID",
				"FIELD_TYPE" => "string",
			),
			"NAME" => array(
				"FIELD_NAME" => "M.NAME",
				"FIELD_TYPE" => "string",
			),
			"EMAIL" => array(
				"FIELD_NAME" => "M.EMAIL",
				"FIELD_TYPE" => "string",
			),
			"CONTACT_PERSON" => array(
				"FIELD_NAME" => "M.CONTACT_PERSON",
				"FIELD_TYPE" => "string",
			),
			"URL" => array(
				"FIELD_NAME" => "M.URL",
				"FIELD_TYPE" => "string",
			),
			"CONTROLLER_GROUP_ID" => array(
				"FIELD_NAME" => "M.CONTROLLER_GROUP_ID",
				"FIELD_TYPE" => "int",
			),
			"CONTROLLER_GROUP_NAME" => array(
				"FIELD_NAME" => "G.NAME",
				"FIELD_TYPE" => "string",
				"TABLE_ALIAS" => "G",
				"JOIN" => "INNER JOIN b_controller_group G ON G.ID = M.CONTROLLER_GROUP_ID",
				"LEFT_JOIN" => "LEFT JOIN b_controller_group G ON G.ID = M.CONTROLLER_GROUP_ID",
			),
			"IN_GROUP_FROM" => array(
				"FIELD_NAME" => "M.IN_GROUP_FROM",
				"FIELD_TYPE" => "datetime",
				"FORMAT" => "FULL",
			),
			"SHARED_KERNEL" => array(
				"FIELD_NAME" => "M.SHARED_KERNEL",
				"FIELD_TYPE" => "string",
			),
			"ACTIVE" => array(
				"FIELD_NAME" => "M.ACTIVE",
				"FIELD_TYPE" => "string",
			),
			"SITE_ACTIVE" => array(
				"FIELD_NAME" => "M.SITE_ACTIVE",
				"FIELD_TYPE" => "string",
			),
			"DISCONNECTED" => array(
				"FIELD_NAME" => "M.DISCONNECTED",
				"FIELD_TYPE" => "string",
			),
			"DATE_ACTIVE_FROM" => array(
				"FIELD_NAME" => "M.DATE_ACTIVE_FROM",
				"FIELD_TYPE" => "datetime",
				"FORMAT" => "SHORT",
			),
			"DATE_ACTIVE_TO" => array(
				"FIELD_NAME" => "M.DATE_ACTIVE_TO",
				"FIELD_TYPE" => "datetime",
				"FORMAT" => "SHORT",
			),
			"TIMESTAMP_X" => array(
				"FIELD_NAME" => "M.TIMESTAMP_X",
				"FIELD_TYPE" => "datetime",
				"FORMAT" => "FULL",
			),
			"MODIFIED_BY" => array(
				"FIELD_NAME" => "M.MODIFIED_BY",
				"FIELD_TYPE" => "int",
			),
			"MODIFIED_BY_USER" => array(
				"FIELD_TYPE" => "string",
			),
			"DATE_CREATE" => array(
				"FIELD_NAME" => "M.DATE_CREATE",
				"FIELD_TYPE" => "datetime",
				"FORMAT" => "FULL",
			),
			"CREATED_BY" => array(
				"FIELD_NAME" => "M.CREATED_BY",
				"FIELD_TYPE" => "int",
			),
			"CREATED_BY_USER" => array(
				"FIELD_TYPE" => "string",
			),
			"COUNTER_FREE_SPACE" => array(
				"FIELD_NAME" => "M.COUNTER_FREE_SPACE",
				"FIELD_TYPE" => "int",
			),
			"COUNTER_SITES" => array(
				"FIELD_NAME" => "M.COUNTER_SITES",
				"FIELD_TYPE" => "int",
			),
			"COUNTER_USERS" => array(
				"FIELD_NAME" => "M.COUNTER_USERS",
				"FIELD_TYPE" => "int",
			),
			"COUNTER_LAST_AUTH" => array(
				"FIELD_NAME" => "M.COUNTER_LAST_AUTH",
				"FIELD_TYPE" => "datetime",
				"FORMAT" => "FULL",
			),
			"COUNTERS_UPDATED" => array(
				"FIELD_NAME" => "M.COUNTERS_UPDATED",
				"FIELD_TYPE" => "datetime",
				"FORMAT" => "FULL",
			),
			"NOTES" => array(
				"FIELD_NAME" => "M.NOTES",
			),
		);

		$arFields["MODIFIED_BY_USER"]["FIELD_NAME"] = $DB->Concat("'('", "UM.LOGIN", "') '", "UM.NAME", "' '", "UM.LAST_NAME");
		$arFields["CREATED_BY_USER"]["FIELD_NAME"] = $DB->Concat("'('", "UC.LOGIN", "') '", "UC.NAME", "' '", "UC.LAST_NAME");

		$rsCounters = CControllerCounter::GetList();
		while($arCounter = $rsCounters->Fetch())
			$arFields["COUNTER_".$arCounter["ID"]] = array(
				"FIELD_NAME" => "CCV_".$arCounter["ID"].".".CControllerCounter::GetTypeColumn($arCounter["COUNTER_TYPE"]),
				"FIELD_TYPE" => CControllerCounter::GetTypeUserType($arCounter["COUNTER_TYPE"]),
				"TABLE_ALIAS" => "CCV_".$arCounter["ID"],
				"JOIN" => "INNER JOIN b_controller_counter_value CCV_".$arCounter["ID"]." ON CCV_".$arCounter["ID"].".CONTROLLER_COUNTER_ID = ".$arCounter["ID"]." AND CCV_".$arCounter["ID"].".CONTROLLER_MEMBER_ID = M.ID",
				"LEFT_JOIN" => "LEFT JOIN b_controller_counter_value CCV_".$arCounter["ID"]." ON CCV_".$arCounter["ID"].".CONTROLLER_COUNTER_ID = ".$arCounter["ID"]." AND CCV_".$arCounter["ID"].".CONTROLLER_MEMBER_ID = M.ID",
			);

		$obWhere = new CSQLWhere;
		$obWhere->SetFields($arFields);

		$arDateFields = array();
		foreach($arFields as $code => $arField)
			if($arField["FIELD_TYPE"] == "datetime")
				$arDateFields[] = $code;
		$date_field = "/(".implode("|", $arDateFields).")\$/";

		$arFilterNew = array();
		if(is_array($arFilter))
		{
			foreach($arFilter as $k => $value)
			{
				if(is_array($value))
				{
					if(!empty($value))
						$arFilterNew[$k] = $value;
				}
				elseif($value === false)
				{
					$arFilterNew[$k] = $value;
				}
				elseif(strlen($value) > 0)
				{
					if(array_key_exists("date_format", $arOptions) && preg_match($date_field, $k))
						$arFilterNew[$k] = ConvertTimeStamp(MakeTimeStamp($value, $arOptions["date_format"]), "FULL");
					else
						$arFilterNew[$k] = $value;
				}
			}
		}

		$strWhere = "1 = 1";

		$r = $obWhere->GetQuery($arFilterNew);
		if(strlen($r) > 0)
			$strWhere .= " AND (".$r.") ";

		$r = $obUserFieldsSql->GetFilter();
		if(strlen($r) > 0)
			$strWhere .= " AND (".$r.") ";

		if(is_array($arOrder))
		{
			foreach($arOrder as $key => $value)
			{
				$key = strtoupper($key);
				if(array_key_exists($key, $arFields) && isset($arFields[$key]["LEFT_JOIN"]))
					$obWhere->c_joins[$key]++;
			}
		}

		if($bEmptySelect)
		{
			$arSelectAdd = array(
				"ID",
				"MEMBER_ID",
				"SECRET_ID",
				"NAME",
				"URL",
				"EMAIL",
				"CONTACT_PERSON",
				"CONTROLLER_GROUP_ID",
				"DISCONNECTED",
				"SHARED_KERNEL",
				"ACTIVE",
				"DATE_ACTIVE_FROM",
				"DATE_ACTIVE_TO",
				"SITE_ACTIVE",
				"TIMESTAMP_X",
				"MODIFIED_BY",
				"DATE_CREATE",
				"CREATED_BY",
				"IN_GROUP_FROM",
				"NOTES",
				"COUNTER_FREE_SPACE",
				"COUNTER_SITES",
				"COUNTER_USERS",
				"COUNTER_LAST_AUTH",
				"COUNTERS_UPDATED",
				"MODIFIED_BY_USER",
				"CREATED_BY_USER",
			);
			if(is_array($arSelect))
				$arSelect = array_merge($arSelect, $arSelectAdd);
			else
				$arSelect = $arSelectAdd;
		}

		$duplicates = array("ID" => 1);
		$strSelect = "M.ID AS ID\n";
		foreach($arSelect as $key)
		{
			$key = strtoupper($key);
			if(array_key_exists($key, $arFields) && !array_key_exists($key, $duplicates))
			{
				$duplicates[$key]++;

				if(isset($arFields[$key]["LEFT_JOIN"]))
					$obWhere->c_joins[$key]++;

				if($arFields[$key]["FIELD_TYPE"] == "datetime")
				{
					if(array_key_exists("date_format", $arOptions))
						$strSelect .= ",".$DB->DateFormatToDB($arOptions["date_format"], $arFields[$key]["FIELD_NAME"])." AS ".$key."\n";
					else
						$strSelect .= ",".$arFields[$key]["FIELD_NAME"]." AS ".$key."_TMP,".$DB->DateToCharFunction($arFields[$key]["FIELD_NAME"], $arFields[$key]["FORMAT"])." AS ".$key."\n";
				}
				else
					$strSelect .= ",".$arFields[$key]["FIELD_NAME"]." AS ".$key."\n";
			}
		}

		$bUseSubQuery = false;
		if(
			$DB->type == "ORACLE"
			&& $obUserFieldsSql->GetDistinct()
		)
		{
			$bUseSubQuery = true;
		}

		if($bUseSubQuery)
		{
			$ob = new CUserTypeSQL;
			$ob->SetEntity("CONTROLLER_MEMBER", "M.ID");
			$ob->SetSelect($arSelect);
			$ob->SetOrder($arOrder);

			$strSql = "
				SELECT ".$strSelect.$ob->GetSelect()."
				FROM b_controller_member M
					LEFT JOIN b_user UC ON UC.ID = M.CREATED_BY
					LEFT JOIN b_user UM ON UM.ID = M.MODIFIED_BY
					".$obWhere->GetJoins()."
					".$ob->GetJoin("M.ID")."
				WHERE M.ID IN (
					SELECT M.ID
					FROM b_controller_member M
					".$obWhere->GetJoins()."
					".$obUserFieldsSql->GetJoin("M.ID")."
					WHERE ".$strWhere."
				)
				".CControllerAgent::_OrderBy($arOrder, $arFields, $ob)."
			";
		}
		else
			$strSql = "
				SELECT ".($obUserFieldsSql->GetDistinct()? "DISTINCT": "")." ".$strSelect.$obUserFieldsSql->GetSelect()."
				FROM b_controller_member M
					LEFT JOIN b_user UC ON UC.ID = M.CREATED_BY
					LEFT JOIN b_user UM ON UM.ID = M.MODIFIED_BY
					".$obWhere->GetJoins()."
					".$obUserFieldsSql->GetJoin("M.ID")."
				WHERE ".$strWhere."
				".CControllerAgent::_OrderBy($arOrder, $arFields, $obUserFieldsSql)."
			";

		$dbr = $DB->Query($strSql);
		$dbr->is_filtered = $strWhere != "1 = 1";
		$dbr->SetUserFields($USER_FIELD_MANAGER->GetUserFields("CONTROLLER_MEMBER"));

		return $dbr;
	}

	public static function GetByID($ID)
	{
		return CControllerMember::GetList(Array(), Array("ID"=>IntVal($ID)));
	}


	public static function GetByGuid($guid)
	{
		return CControllerMember::GetList(Array(), Array("=MEMBER_ID"=>$guid));
	}

	public static function RegisterMemberByPassword($ar_member, $admin_login, $admin_password, $controller_url = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if($controller_url===false)
			$controller_url = COption::GetOptionString("controller", "controller_url", ($APPLICATION->IsHTTPS()?"https://":"http://").$_SERVER['HTTP_HOST']);

		if(!isset($ar_member['MEMBER_ID']))
			$ar_member['MEMBER_ID'] = substr("m".md5(uniqid(rand(), true)), 0, 32);

		if(!isset($ar_member['SECRET_ID']))
			$ar_member['SECRET_ID'] = substr("m".md5(uniqid(rand(), true)), 0, 32);

		if(!CControllerMember::CheckFields($ar_member))
			return false;

		$arParameters = Array(
			"member_id" => $ar_member['MEMBER_ID'],
			"member_secret_id" => $ar_member['SECRET_ID'],
			"controller_url" => $controller_url,
			"admin_login" => $admin_login,
			"admin_password" => $admin_password,
			"join_command" => '
				COption::SetOptionString("main", "controller_member", "Y");
				COption::SetOptionString("main", "controller_ticket", "");
				RegisterModuleDependences("main", "OnUserLoginExternal", "main", "CControllerClient", "OnExternalLogin", 1);
				RegisterModuleDependences("main", "OnExternalAuthList", "main", "CControllerClient", "OnExternalAuthList");
			',
			"disconnect_command" => '
				CControllerClient::RestoreAll();
				COption::SetOptionString("main", "controller_member", "N");
				COption::SetOptionString("main", "controller_member_id", "");
				COption::SetOptionString("main", "controller_url", "");
				UnRegisterModuleDependences("main", "OnUserLoginExternal", "main", "CControllerClient", "OnExternalLogin");
				UnRegisterModuleDependences("main", "OnExternalAuthList", "main", "CControllerClient", "OnExternalAuthList");
			'
		);

		$oRequest = new CControllerServerRequestTo($ar_member, "simple_register", $arParameters);
		if(($oResponse = $oRequest->Send())===false)
		{
			return false;
		}

		$result = $oResponse->OK();

		if($result === false)
		{
			$e = new CApplicationException($oResponse->text);
			$APPLICATION->ThrowException($e);
		}
		else
		{
			$APPLICATION->ResetException();
			if($ID = CControllerMember::Add($ar_member))
			{
				$arControllerLog = Array(
					'NAME'=>'REGISTRATION',
					'CONTROLLER_MEMBER_ID'=>$ID,
					'DESCRIPTION'=>GetMessage("CTRLR_MEM_LOG_DESC_JOIN_BY_TICKET"),
					'STATUS'=>'Y'
				);
				CControllerLog::Add($arControllerLog);
				CControllerMember::SetGroupSettings($ID);
				return $ID;
			}
		}

		return false;
	}

	public static function CheckUserAuth($member_id, $login, $password, $arRemGroups = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$arMember = CControllerMember::GetMember($member_id);
		if(!$arMember)
			return false;

		// send query to the client in order to check authorization
		$arParameters = array("login"=>$login, "password"=>$password);
		$oRequest = new CControllerServerRequestTo($arMember, 'check_auth', $arParameters);
		$oResponse = $oRequest->Send();
		if($oResponse === false)
			return false;

		if($oResponse->OK() === false)
		{
			$e = new CApplicationException($oResponse->text);
			$APPLICATION->ThrowException($e);
			return false;
		}
		elseif(COption::GetOptionString("controller", "auth_trans_enabled", "N") === "Y")
		{
			$current_groups = $oResponse->arParameters['USER_INFO']["GROUP_ID"];
			if(!is_array($arRemGroups))
				$arRemGroups = unserialize(COption::GetOptionString("controller", "auth_trans", serialize(array())));
		}
		elseif(COption::GetOptionString("controller", "auth_controller_enabled", "N") === "Y")
		{
			$current_groups = $oResponse->arParameters['USER_INFO']["GROUP_ID"];
			if(!is_array($arRemGroups))
				$arRemGroups = unserialize(COption::GetOptionString("controller", "auth_controller", serialize(array())));
		}
		else
		{
			return false;
		}

		$GROUP_ID = array();
		$GROUPS_TO_ADD = array();
		$GROUPS_TO_DELETE = array();
		foreach($arRemGroups as $arTGroup)
		{
			$bFound = false;
			foreach($current_groups as $group_id)
			{
				if($arTGroup["FROM"] == $group_id)
				{
					$GROUP_ID[] = $arTGroup["TO"];
					$GROUPS_TO_ADD[] = $arTGroup["TO"];
					$bFound = true;
				}
			}

			if(!$bFound)
				$GROUPS_TO_DELETE[] = $arTGroup["TO"];
		}

		$oResponse->arParameters['USER_INFO']['GROUP_ID'] = $GROUP_ID;
		$oResponse->arParameters['USER_INFO']['GROUPS_TO_ADD'] = $GROUPS_TO_ADD;
		$oResponse->arParameters['USER_INFO']['GROUPS_TO_DELETE'] = $GROUPS_TO_DELETE;

		// return to the client
		return $oResponse->arParameters;
	}


	public static function RegisterMemberByPHP($ar_member)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$controller_url = COption::GetOptionString("controller", "controller_url", ($APPLICATION->IsHTTPS()?"https://":"http://").$_SERVER['HTTP_HOST']);

		if(!isset($ar_member['MEMBER_ID']))
			$ar_member['MEMBER_ID'] = substr("m".md5(uniqid(rand(), true)), 0, 32);

		if(!isset($ar_member['SECRET_ID']))
			$ar_member['SECRET_ID'] = substr("m".md5(uniqid(rand(), true)), 0, 32);

		$arParameters = Array(
			"member_id" => $ar_member['MEMBER_ID'],
			"member_secret_id" => $ar_member['SECRET_ID'],
			"controller_url" => $controller_url,
			"join_command" => '
				COption::SetOptionString("main", "controller_member", "Y");
				COption::SetOptionString("main", "controller_ticket", "");
				RegisterModuleDependences("main", "OnUserLoginExternal", "main", "CControllerClient", "OnExternalLogin", 1);
				RegisterModuleDependences("main", "OnExternalAuthList", "main", "CControllerClient", "OnExternalAuthList");
			',
			"disconnect_command" => '
				CControllerClient::RestoreAll();
				COption::SetOptionString("main", "controller_member", "N");
				COption::SetOptionString("main", "controller_member_id", "");
				COption::SetOptionString("main", "controller_url", "");
				UnRegisterModuleDependences("main", "OnUserLoginExternal", "main", "CControllerClient", "OnExternalLogin");
				UnRegisterModuleDependences("main", "OnExternalAuthList", "main", "CControllerClient", "OnExternalAuthList");
			'
		);

		if($ar_member["ID"] = CControllerMember::Add($ar_member))
		{
			$ar_member["REG_PARAMS"] = $arParameters;
			return $ar_member;
		}
		return false;
	}


	public static function RegisterMemberByTicket($ar_member, $ticket_id, $session_id)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if($ar_member["ID"]>0)
			$ID = $ar_member["ID"];
		else
			$ID = false;

		$member_id = $ar_member["MEMBER_ID"];

		if(!CControllerMember::CheckFields($ar_member, $ID))
			return false;

		$ar_member["MEMBER_ID"] = $member_id;

		$arParameters = Array(
			"controller_ticket_id"=>$ticket_id,
			"join_command" => '
				COption::SetOptionString("main", "controller_member", "Y");
				COption::SetOptionString("main", "controller_ticket", "");
				RegisterModuleDependences("main", "OnUserLoginExternal", "main", "CControllerClient", "OnExternalLogin", 1);
				RegisterModuleDependences("main", "OnExternalAuthList", "main", "CControllerClient", "OnExternalAuthList");
			',
			"disconnect_command" => '
				CControllerClient::RestoreAll();
				COption::SetOptionString("main", "controller_member", "N");
				COption::SetOptionString("main", "controller_member_id", "");
				COption::SetOptionString("main", "controller_url", "");
				UnRegisterModuleDependences("main", "OnUserLoginExternal", "main", "CControllerClient", "OnExternalLogin", 1);
				UnRegisterModuleDependences("main", "OnExternalAuthList", "main", "CControllerClient", "OnExternalAuthList");
			'
		);

		$oRequest = new CControllerServerRequestTo($ar_member, "register", $arParameters);
		if($session_id)
			$oRequest->session_id = $session_id;

		if(($oResponse = $oRequest->Send())==false)
			return false;

		$result = $oResponse->OK();

		if($result === false)
		{
			$e = new CApplicationException($oResponse->text);
			$APPLICATION->ThrowException($e);
			return false;
		}


		if($ID>0)
		{
			$ar_member["DISCONNECTED"] = "N";
			$ID = CControllerMember::Update($ID, $ar_member) ? $ID : false;
		}
		else
			$ID = CControllerMember::Add($ar_member);

		if($ID>0)
		{
			$arControllerLog = Array(
					'NAME'=>'REGISTRATION',
					'CONTROLLER_MEMBER_ID'=>$ID,
					'DESCRIPTION'=>GetMessage("CTRLR_MEM_LOG_DESC_JOIN_BY_TICKET2"),
					'STATUS'=>'Y'
				);

			CControllerLog::Add($arControllerLog);
			CControllerMember::SetGroupSettings($ID);

			if(!isset($ar_member["DISCONNECTED"]) || $ar_member["DISCONNECTED"]=="N")
			{
				// add join event
				CTimeZone::Disable();
				$db_res = CControllerMember::GetByID($ID);
				CTimeZone::Enable();

				if($arFields = $db_res->Fetch())
				{
					foreach (GetModuleEvents("controller", "OnAfterRegisterMemberByTicket", true) as $arEvent)
					{
						ExecuteModuleEventEx($arEvent, array(&$arFields));
					}

					CEvent::Send("CONTROLLER_MEMBER_REGISTER", SITE_ID, $arFields);
				}
			}

			return $ID;
		}

		return false;
	}

	public static function CheckFields(&$arFields, $ID = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		/** @global CDatabase $DB */
		global $DB;
		/** @global CUser $USER */
		global $USER;

		$arMsg = array();
		if ($ID > 0)
		{
			unset($arFields["ID"]);
		}

		if ($ID === false)
		{
			if (!array_key_exists("MEMBER_ID", $arFields))
			{
				$arFields["MEMBER_ID"] = substr("m".md5(uniqid(rand(), true)), 0, 32);
			}
			elseif (array_key_exists("MEMBER_ID", $arFields))
			{
				if (intval($arFields["MEMBER_ID"]) > 0)
				{
					$arMsg[] = array(
						"id" => "MEMBER_ID",
						"text" => GetMessage("CTRLR_MEM_ERR_MEMBER_ID"),
					);
				}
				else
				{
					$strSqlCheck = "
						SELECT 'x'
						FROM b_controller_member
						WHERE MEMBER_ID = '".$DB->ForSQL($arFields['MEMBER_ID'], 32)."'
						AND ID <> ".intval($ID)."
					";
					$dbrCheck = $DB->Query($strSqlCheck);
					if ($dbrCheck->Fetch())
					{
						$arMsg[] = array(
							"id" => "MEMBER_ID",
							"text" => GetMessage("CTRLR_MEM_ERR_MEMBER_UID"),
						);
					}
				}
			}
		}

		if (($ID === false || array_key_exists("NAME", $arFields)) && strlen($arFields["NAME"]) <= 0)
		{
			$arMsg[] = array(
				"id" => "NAME",
				"text" => GetMessage("CTRLR_MEM_ERR_MEMBER_NAME"),
			);
		}

		if (($ID === false || array_key_exists("URL", $arFields)) && strlen($arFields["URL"]) <= 0)
		{
			$arMsg[] = array(
				"id" => "URL",
				"text" => GetMessage("CTRLR_MEM_ERR_MEMBER_URL"),
			);
		}

		if ($ID === false && !array_key_exists("CONTROLLER_GROUP_ID", $arFields))
		{
			$arFields["CONTROLLER_GROUP_ID"] = COption::GetOptionInt("controller", "default_group", 1);
		}

		if ($ID === false)
		{
			$dbEvents = GetModuleEvents("controller", "OnBeforeControllerMemberAdd", true);
		}
		else
		{
			$dbEvents = GetModuleEvents("controller", "OnBeforeControllerMemberUpdate", true);
		}

		$APPLICATION->ResetException();
		foreach($dbEvents as $arEvent)
		{
			$bEventRes = ExecuteModuleEventEx($arEvent, array($ID, &$arFields));
			if ($bEventRes === false)
			{
				$ex = $APPLICATION->GetException();
				$arMsg[] = array(
					"text" => ($ex? $ex->GetString(): "Unknown error."),
				);
			}
		}

		if (!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		if (isset($arFields["URL"]))
		{
			$arFields["URL"] = CControllerMember::_GoodURL($arFields["URL"]);
		}

		if (array_key_exists("ACTIVE", $arFields) && $arFields["ACTIVE"] != "Y")
		{
			$arFields["ACTIVE"] = "N";
		}

		if (array_key_exists("SHARED_KERNEL", $arFields) && $arFields["SHARED_KERNEL"] != "Y")
		{
			$arFields["SHARED_KERNEL"] = "N";
		}

		if (array_key_exists("DISCONNECTED", $arFields) && $arFields["DISCONNECTED"] != "Y" && $arFields["DISCONNECTED"]!="I")
		{
			$arFields["DISCONNECTED"] = "N";
		}

		if (!array_key_exists("MODIFIED_BY", $arFields) && is_object($USER))
		{
			$arFields["MODIFIED_BY"] = $USER->GetID();
		}

		if ($ID === false && !array_key_exists("CREATED_BY", $arFields) && is_object($USER))
		{
			$arFields["CREATED_BY"] = $USER->GetID();
		}

		if ($ID === false && !array_key_exists("DATE_CREATE", $arFields))
		{
			$arFields["~DATE_CREATE"] = $DB->CurrentTimeFunction();
		}

		return true;
	}

	public static function CloseMember($member_id, $bClose = true, $task_id = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!($arMember = CControllerMember::GetMember($member_id)))
			return false;

		$arControllerLog = Array(
				'NAME'=>'SITE_CLOSING',
				'CONTROLLER_MEMBER_ID'=>$arMember['ID'],
				'DESCRIPTION'=>($bClose ? GetMessage("CTRLR_MEM_LOG_SITE_CLO") : GetMessage("CTRLR_MEM_LOG_SITE_OPE")),
				'STATUS'=>'Y',
				"TASK_ID"=>$task_id
			);

		$result = false;
		$strCommand = 'CControllerClient::SetOptionString("main", "site_stopped", "'.($bClose?'Y':'N').'");';
		$result = CControllerMember::RunCommand($member_id, $strCommand, array(), $task_id);
		if($result === false)
		{
			$e = $APPLICATION->GetException();
			$arControllerLog['DESCRIPTION'] = $e->GetString();
			$arControllerLog['STATUS'] = 'N';
		}
		else
		{
			CControllerMember::Update($arMember['ID'], Array('SITE_ACTIVE'=>(!$bClose?'Y':'N')));
		}

		CControllerLog::Add($arControllerLog);

		// close event
		CTimeZone::Disable();
		$db_res = CControllerMember::GetByID($arMember["ID"]);
		CTimeZone::Enable();
		if($arFields = $db_res->Fetch())
		{
			foreach (GetModuleEvents("controller", "OnAfterCloseMember", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array(&$arFields));
			}

			if($bClose)
				CEvent::Send("CONTROLLER_MEMBER_CLOSED", SITE_ID, $arFields);
			else
				CEvent::Send("CONTROLLER_MEMBER_OPENED", SITE_ID, $arFields);
		}

		return $result;
	}

	public static function UnRegister($member_id)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(($ar_member = CControllerMember::GetMember($member_id))===false)
			return false;

		$arParameters = Array();
		$oRequest = new CControllerServerRequestTo($ar_member, "unregister", $arParameters);
		$oResponse = $oRequest->Send();

		$arControllerLog = Array(
				'NAME'=>'UNREGISTRATION',
				'CONTROLLER_MEMBER_ID'=>$ar_member['ID'],
				'STATUS'=>'Y',
				'DESCRIPTION'=>GetMessage("CTRLR_MEM_LOG_DISCON"),
			);

		if($oResponse==false)
		{
			$e = $APPLICATION->GetException();
			$arControllerLog['DESCRIPTION'] = $e->GetString();
			$result = false;
		}
		else
		{
			$result = $oResponse->OK();
			$arControllerLog['DESCRIPTION'] = $oResponse->text;
			if($result === false)
			{
				$e = new CApplicationException(GetMessage("CTRLR_MEM_LOG_DISCON_ERR")." ".$oResponse->text);
				$APPLICATION->ThrowException($e);
			}
			else
				CControllerMember::Update($ar_member['ID'], Array('DISCONNECTED'=>'Y'));
		}


		if($result === false)
		{
			$arControllerLog['DESCRIPTION'] = $e->GetString()."\r\n".$arControllerLog['DESCRIPTION'];
			$arControllerLog['STATUS'] = 'N';
		}

		CControllerLog::Add($arControllerLog);

		return $result;
	}

	public static function AddCommand($member_guid, $command, $arAddParams = Array(), $task_id = false)
	{
		global $DB;

		// unique command code
		$command_id = md5(uniqid(rand(), true));

		$db_id = $DB->Add(
			"b_controller_command",
			array(
				"MEMBER_ID" => $member_guid,
				"COMMAND_ID" => $command_id,
				"TASK_ID" => $task_id,
				"COMMAND" => $command,
				"~DATE_INSERT" => $DB->CurrentTimeFunction(),
				"ADD_PARAMS" => (count($arAddParams)>0?serialize($arAddParams):false)
			),
			array("COMMAND", "ADD_PARAMS"),
			"", true //bIgnoreErrors
		);

		if($db_id > 0)
			return $command_id;
		else
			return false;
	}

	public static function GetMember($id)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$dbr_member = CControllerMember::GetById($id);
		$ar_member = $dbr_member->Fetch();
		if(!$ar_member)
		{
			$e = new CApplicationException(GetMessage("CTRLR_MEM_ERR6")." ".htmlspecialcharsex($id));
			$APPLICATION->ThrowException($e);
			return false;
		}

		if($ar_member['DISCONNECTED'] == 'Y')
		{
			$e = new CApplicationException(GetMessage("CTRLR_MEM_ERR7"));
			$APPLICATION->ThrowException($e);
			return false;
		}

		return $ar_member;
	}

	public static function SiteUpdate($member_id)
	{
		CControllerTask::Add(Array(
			"TASK_ID"=>"UPDATE",
			"CONTROLLER_MEMBER_ID"=>$member_id
		));
	}

	public static function _GoodURL($url)
	{
		$url = strtolower(trim($url, " \t\r\n./"));
		if(substr($url, 0, 7) != "http://" && substr($url, 0, 8) != "https://")
			$url = "http://".$url;
		return $url;
	}
}
?>
