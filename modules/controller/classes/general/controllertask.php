<?
##############################################
# Bitrix Site Manager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:sources@bitrixsoft.com              #
##############################################

IncludeModuleLangFile(__FILE__);

class CControllerTask
{
	public static function GetTaskArray()
	{
		return 	Array(
			'SET_SETTINGS'=>GetMessage("CTRLR_TASK_TYPE_SET_SETTINGS"),
			'UPDATE'=>GetMessage("CTRLR_TASK_TYPE_UPDATE"),
			'COUNTERS_UPDATE'=>GetMessage("CTRLR_TASK_TYPE_COUNTERS_UPDATE"),
			'REMOTE_COMMAND'=>GetMessage("CTRLR_TASK_TYPE_REMOTE_COMMAND"),
			'CLOSE_MEMBER'=>GetMessage("CTRLR_TASK_TYPE_CLOSE_MEMBER")
		);

	}

	public static function GetStatusArray()
	{
		return Array(
			'N'=>GetMessage("CTRLR_TASK_STATUS_NEW"),
			'P'=>GetMessage("CTRLR_TASK_STATUS_PART"),
			'Y'=>GetMessage("CTRLR_TASK_STATUS_COMPL"),
			'F'=>GetMessage("CTRLR_TASK_STATUS_FAIL"),
		);
	}

	public static function CheckFields(&$arFields, $ID = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$arMsg = Array();

		if($ID>0)
			unset($arFields["ID"]);

		global $DB;
		if(($ID===false || is_set($arFields, "TASK_ID")) && strlen($arFields["TASK_ID"])<=0)
			$arMsg[] = array("id"=>"TASK_ID", "text"=> GetMessage("CTRLR_TASK_ERR_ID"));
		elseif(is_set($arFields, "TASK_ID"))
		{
			$arTaskID = CControllerTask::GetTaskArray();
			if(!isset($arTaskID[$arFields['TASK_ID']]))
				$arMsg[] = array("id"=>"TASK_ID", "text"=> GetMessage("CTRLR_TASK_ERR_BAD_ID"));
		}

		if(($ID===false || is_set($arFields, "CONTROLLER_MEMBER_ID")) && Intval($arFields["CONTROLLER_MEMBER_ID"])<=0)
			$arMsg[] = array("id"=>"CONTROLLER_MEMBER_ID", "text"=> GetMessage("CTRLR_TASK_ERR_CLIENTID"));

		if(isset($arFields["INIT_EXECUTE"]))
			$arFields["INIT_CRC"] = crc32($arFields["INIT_EXECUTE"]);

		if(count($arMsg)<=0 && $ID===false)
		{
			$strSql = "
				SELECT INIT_EXECUTE
				FROM b_controller_task
				WHERE CONTROLLER_MEMBER_ID='".IntVal($arFields["CONTROLLER_MEMBER_ID"])."'
				AND TASK_ID='".$DB->ForSQL($arFields["TASK_ID"], 255)."'
				AND DATE_EXECUTE IS NULL
			";
			$dbr = $DB->Query($strSql);
			while($ar = $dbr->Fetch())
			{
				if(intval($ar["INIT_EXECUTE"]) == intval($arFields["INIT_EXECUTE"]))
				{
					$arMsg[] = array("id"=>"TASK_ID", "text"=> GetMessage("CTRLR_TASK_ERR_ALREADY")." [".IntVal($arFields["CONTROLLER_MEMBER_ID"])."].");
					break;
				}
			}
		}

		if(count($arMsg)>0)
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		if($ID===false && !is_set($arFields, "DATE_CREATE"))
			$arFields["~DATE_CREATE"] = $DB->CurrentTimeFunction();

		return true;
	}

	public static function Add($arFields)
	{
		global $DB;

		if(!CControllerTask::CheckFields($arFields))
			return false;

		unset($arFields["TIMESTAMP_X"]);
		unset($arFields["~TIMESTAMP_X"]);

		$ID = $DB->Add("b_controller_task", $arFields, array("INIT_EXECUTE", "INIT_EXECUTE_PARAMS"));

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;
		if(!CControllerTask::CheckFields($arFields, $ID))
			return false;

		unset($arFields["TIMESTAMP_X"]);
		$arFields["~TIMESTAMP_X"] = $DB->CurrentTimeFunction();

		$arUpdateBinds = array();
		$strUpdate = $DB->PrepareUpdateBind("b_controller_task", $arFields, "", false, $arUpdateBinds);

		$strSql = "UPDATE b_controller_task SET ".$strUpdate." WHERE ID=".intval($ID);

		$arBinds = array();
		foreach($arUpdateBinds as $field_id)
			$arBinds[$field_id] = $arFields[$field_id];

		$DB->QueryBind($strSql, $arBinds);

		return true;
	}

	public static function Delete($ID)
	{
		global $DB;
		$DB->Query("DELETE FROM b_controller_task WHERE ID=".intval($ID));
		return true;
	}

	public static function GetList($arOrder = Array(), $arFilter = Array(), $bCnt = false)
	{
		global $DB;

		static $arFields = array(
			"ID" => Array("FIELD_NAME" => "T.ID", "FIELD_TYPE" => "int"),
			"TIMESTAMP_X" => Array("FIELD_NAME" => "T.TIMESTAMP_X", "FIELD_TYPE" => "datetime"),
			"DATE_CREATE" => Array("FIELD_NAME" => "T.DATE_CREATE", "FIELD_TYPE" => "datetime"),
			"TASK_ID" => Array("FIELD_NAME" => "T.TASK_ID", "FIELD_TYPE" => "string"),
			"CONTROLLER_MEMBER_ID" => Array("FIELD_NAME" => "T.CONTROLLER_MEMBER_ID", "FIELD_TYPE" => "int"),
			"CONTROLLER_MEMBER_NAME" => Array("FIELD_NAME" => "M.NAME", "FIELD_TYPE" => "string"),
			"CONTROLLER_MEMBER_URL" => Array("FIELD_NAME" => "M.URL", "FIELD_TYPE" => "string"),
			"STATUS" => Array("FIELD_NAME" => "T.STATUS", "FIELD_TYPE" => "string"),
			"DATE_EXECUTE" => Array("FIELD_NAME" => "T.DATE_EXECUTE", "FIELD_TYPE" => "datetime"),
		);

		$obWhere = new CSQLWhere;
		$obWhere->SetFields($arFields);

		$arFilterNew = Array();
		foreach($arFilter as $k=>$value)
			if(is_array($value) || strlen($value)>0 || $value === false)
				$arFilterNew[$k]=$value;

		$strWhere = $obWhere->GetQuery($arFilterNew);

		if($bCnt)
			$strSql = "SELECT COUNT('x') as C, MIN(T.ID) as MIN_ID, MAX(T.ID) as MAX_ID ";
		else
			$strSql =
				"SELECT T.*, ".
				"	M.NAME as CONTROLLER_MEMBER_NAME, M.URL as CONTROLLER_MEMBER_URL, ".
				"	".$DB->DateToCharFunction("T.TIMESTAMP_X")."	as TIMESTAMP_X, ".
				"	".$DB->DateToCharFunction("T.DATE_EXECUTE")."	as DATE_EXECUTE, ".
				"	".$DB->DateToCharFunction("T.DATE_CREATE")."	as DATE_CREATE ";

		$strSql .=
			"FROM b_controller_task T  ".
			"	INNER JOIN b_controller_member M ON T.CONTROLLER_MEMBER_ID=M.ID ".
			(strlen($strWhere)<=0 ? "" : "WHERE ".$strWhere)." ".
			CControllerAgent::_OrderBy($arOrder, $arFields);

		$dbr = $DB->Query($strSql);
		$dbr->is_filtered = (strlen($strWhere)>0);
		return $dbr;
	}

	public static function GetByID($ID)
	{
		return CControllerTask::GetList(Array(), Array("ID"=>IntVal($ID)));
	}

	public static function ProcessTask($ID)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		global $DB;
		$ID = IntVal($ID);

		$uniq = $APPLICATION->GetServerUniqID();

		$uniq = "X".$uniq."_ctask";
		$STATUS = "0";

		// locking the task
		if(!CControllerAgent::_Lock($uniq))
		{
			AddMessage2Log('ERROR_GET_LOCK');
			return $STATUS;
		}

		// selecting task
		$strSql =
			"SELECT T.*, M.SHARED_KERNEL ".
			"FROM b_controller_task T LEFT JOIN b_controller_member M ON T.CONTROLLER_MEMBER_ID=M.ID ".
			"WHERE T.ID='".$ID."' AND T.STATUS<>'Y'";

		$db_task = $DB->Query($strSql);
		if($ar_task = $db_task->Fetch())
		{
			$arControllerLog = Array(
					'CONTROLLER_MEMBER_ID'=>$ar_task["CONTROLLER_MEMBER_ID"],
					'TASK_ID'=>$ar_task['ID'],
					'STATUS'=>'Y'
					);
			$RESULT = '';
			$STATUS = 'Y';
			unset($INIT_EXECUTE_PARAMS);
			switch($ar_task['TASK_ID'])
			{
				case 'SET_SETTINGS':
					$arControllerLog['NAME'] = 'SET_SETTINGS';
					$APPLICATION->ResetException();
					$res = CControllerMember::SetGroupSettings($ar_task["CONTROLLER_MEMBER_ID"], $ar_task['ID']);
					if($res!==false)
						$RESULT = $res;
					else
					{
						$e = $APPLICATION->GetException();
						$STATUS = "F";
						$RESULT = $e->GetString();
						$arControllerLog['STATUS'] = 'N';
					}


					break;

				case 'CLOSE_MEMBER':
					$arControllerLog['NAME'] = 'SITE_CLOSING';
					$APPLICATION->ResetException();
					$res = CControllerMember::CloseMember($ar_task["CONTROLLER_MEMBER_ID"], $ar_task['INIT_EXECUTE_PARAMS']);
					if($res!==false)
						$RESULT = $res;
					else
					{
						$e = $APPLICATION->GetException();
						$STATUS = "F";
						$RESULT = $e->GetString();
						$arControllerLog['STATUS'] = 'N';
					}
					break;
				case 'UPDATE':
					$arControllerLog['NAME'] = 'SITE_UPDATE';

					$APPLICATION->ResetException();
					if($ar_task["SHARED_KERNEL"] == "Y")
					{
						$STATUS = "F";
						$RESULT = GetMessage("CTRLR_TASK_ERR_KERNEL");
						$arControllerLog['STATUS'] = 'N';
					}
					else
					{
						$command = 'require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");';
						if($ar_task["STATUS"]=="P" && strlen($ar_task["INIT_EXECUTE_PARAMS"])>0)
							$command .= 'echo trim(CUpdateControllerSupport::Update("'.EscapePHPString($ar_task["INIT_EXECUTE_PARAMS"]).'"));';
						else
							$command .= 'echo trim(CUpdateControllerSupport::Update(""));';

						$res = CControllerMember::RunCommand($ar_task["CONTROLLER_MEMBER_ID"], $command, array(), $ar_task['ID']);
						if($res!==false)
						{
							if(($p = strpos($res, "|"))>0)
							{
								$result_code = substr($res, 0, $p);
								$RESULT = substr($res, $p + 1);
							}
							else
							{
								$result_code = $res;
								$RESULT = $res;
							}

							if($result_code=='ERR')
							{
								$STATUS = "F";
								$arControllerLog['STATUS'] = 'N';
							}
							elseif($result_code=='STP0') // STP
							{
								$STATUS = "P";
							}
							elseif($result_code!='FIN') // other command
							{
								$STATUS = "P";
								$INIT_EXECUTE_PARAMS = $result_code;
							}
							else
							{
								$RESULT = GetMessage("CTRLR_TASK_UPD_COMPL");
							}
						}
						else
						{
							$STATUS = "F";
							$e = $APPLICATION->GetException();
							$RESULT = $e->GetString();
							$arControllerLog['STATUS'] = 'N';
						}
					}
					break;

				case 'COUNTERS_UPDATE':
					$arControllerLog['NAME'] = 'UPDATE_COUNTERS';
					$APPLICATION->ResetException();
					$res = CControllerMember::UpdateCounters($ar_task["CONTROLLER_MEMBER_ID"], $ar_task['ID']);
					$RESULT = '';
					if($res!==false)
					{
						foreach($res as $k=>$v)
							$RESULT .= "$k=$v;\r\n";
					}
					else
					{
						$e = $APPLICATION->GetException();
						$STATUS = "F";
						$RESULT = $e->GetString();
						$arControllerLog['STATUS'] = 'N';
					}

					break;

				case 'REMOTE_COMMAND':
					$APPLICATION->ResetException();

					$arControllerLog['NAME'] = 'REMOTE_COMMAND';
					if(strlen($ar_task['INIT_EXECUTE_PARAMS'])>0)
						$ar_task['INIT_EXECUTE_PARAMS'] = unserialize($ar_task['INIT_EXECUTE_PARAMS']);
					else
						$ar_task['INIT_EXECUTE_PARAMS'] = Array();

					$res = CControllerMember::RunCommand($ar_task["CONTROLLER_MEMBER_ID"], $ar_task['INIT_EXECUTE'], $ar_task['INIT_EXECUTE_PARAMS'], $ar_task['ID']);
					if($res!==false)
						$RESULT = $res;
					else
					{
						$STATUS = "F";
						$e = $APPLICATION->GetException();
						$RESULT = $e->GetString();
						$arControllerLog['STATUS'] = 'N';
					}
					break;

				case 'SEND_FILE':
					$APPLICATION->ResetException();
					$arControllerLog['NAME'] = 'SEND_FILE';

					break;
			}

			if(!isset($arControllerLog['DESCRIPTION']))
				$arControllerLog['DESCRIPTION'] = $RESULT;

			CControllerLog::Add($arControllerLog);

			// updating status
			$arUpdateFields = array(
				"STATUS" => $STATUS,
				"~DATE_EXECUTE" => $DB->CurrentTimeFunction(),
				"RESULT_EXECUTE" => $RESULT,
				"INDEX_SALT" => rand(),
			);
			if(isset($INIT_EXECUTE_PARAMS))
				$arUpdateFields["INIT_EXECUTE_PARAMS"] = $INIT_EXECUTE_PARAMS;

			$arUpdateBinds = array();
			$strUpdate = $DB->PrepareUpdateBind("b_controller_task", $arUpdateFields, "", false, $arUpdateBinds);

			$strSql = "UPDATE b_controller_task SET ".$strUpdate." WHERE ID=".$ID;

			$arBinds = array();
			foreach($arUpdateBinds as $field_id)
				$arBinds[$field_id] = $arUpdateFields[$field_id];

			$DB->QueryBind($strSql, $arBinds);
		}

		// unlocking
		CControllerAgent::_UnLock($uniq);
		return $STATUS;
	}

	public static function ProcessAllTask()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$uniq = $APPLICATION->GetServerUniqID();

		$uniq = "X".$uniq."_controller_all_task";
		if(!CControllerAgent::_Lock($uniq))
			return false;

		$dbrTask = CControllerTask::GetList(Array("ID"=>"ASC"), Array("=STATUS"=>Array('N', 'P')));
		while($arTask = $dbrTask->Fetch())
			CControllerTask::ProcessTask($arTask["ID"]);

		CControllerAgent::_UnLock($uniq);
		return true;
	}
}
?>
