<?
IncludeModuleLangFile(__FILE__);

global $SUPPORT_CACHE_USER_ROLES;
$SUPPORT_CACHE_USER_ROLES  = Array();


/**
 * <b>CTicket</b> - класс для работы с обращениями. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/support/classes/cticket/index.php
 * @author Bitrix
 */
class CAllTicket
{

	const ADD = "ADD";
	const UPDATE = "UPDATE";
	const DELETE = "DELETE";
	const IGNORE = "IGNORE";
	const REOPEN = "REOPEN";
	const NEW_SLA = "NEW_SLA";
		
	public static function err_mess()
	{
		$module_id = "support";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." <br>Class: CAllTicket<br>File: ".__FILE__;
	}

	/***************************************************************

	Группа функций по работе с ролями на модуль

	Идентификаторы ролей:

	D - доступ закрыт
	R - клиент техподдержки
	T - сотрудник техподдержки
	V - демо-доступ
	W - администратор техподдержки

	*****************************************************************/

static 	function GetDeniedRoleID()
	{
		return "D";
	}

static 	function GetSupportClientRoleID()
	{
		return "R";
	}

public static 	function GetSupportTeamRoleID()
	{
		return "T";
	}

public static 	function GetDemoRoleID()
	{
		return "V";
	}

public static 	function GetAdminRoleID()
	{
		return "W";
	}

	// возвращает true если заданный пользователь имеет заданную роль на модуль
public static 	function HaveRole($role, $userID=false)
	{
		global $DB, $USER, $APPLICATION, $SUPPORT_CACHE_USER_ROLES;
		if (!is_object($USER)) $USER = new CUser;

		if ($userID===false && is_object($USER))
			$uid = $USER->GetID();
		else
			$uid = $userID;

		$arRoles = Array();
		if (array_key_exists($uid, $SUPPORT_CACHE_USER_ROLES) && is_array($SUPPORT_CACHE_USER_ROLES[$uid]))
		{
			$arRoles = $SUPPORT_CACHE_USER_ROLES[$uid];
		}
		else
		{
			$arrGroups = Array();
			if ($userID===false && is_object($USER))
				$arrGroups = $USER->GetUserGroupArray();
			else
				$arrGroups = CUser::GetUserGroup($userID);

			sort($arrGroups);
			$arRoles = $APPLICATION->GetUserRoles("support", $arrGroups);
			$SUPPORT_CACHE_USER_ROLES[$uid] = $arRoles;
		}

		if (in_array($role, $arRoles))
			return true;

		return false;

	}

	// true - если пользователь имеет роль "администратор техподдержки"
	// false - в противном случае
	function IsAdmin($userID=false)
	{
		global $USER;

		if ($userID===false && is_object($USER))
		{
			if ($USER->IsAdmin()) return true;
		}
		return CTicket::HaveRole(CTicket::GetAdminRoleID(), $userID);
	}

	// true - если пользователь имеет роль "демо-доступ"
	// false - в противном случае
public static 	function IsDemo($userID=false)
	{
		return CTicket::HaveRole(CTicket::GetDemoRoleID(), $userID);
	}

	// true - если пользователь имеет роль "сотрудник техподдержки"
	// false - в противном случае
public static 	function IsSupportTeam($userID=false)
	{
		return CTicket::HaveRole(CTicket::GetSupportTeamRoleID(), $userID);
	}

	// true - если пользователь имеет роль "сотрудник техподдержки"
	// false - в противном случае
public static 	function IsSupportClient($userID=false)
	{
		return CTicket::HaveRole(CTicket::GetSupportClientRoleID(), $userID);
	}

	public static function IsOwner($ticketID, $userID=false)
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: IsOwner<br>Line: ";
		global $DB, $USER;
		if ($userID===false && is_object($USER)) $userID = $USER->GetID();
		$userID = intval($userID);
		$ticketID = intval($ticketID);
		if ($userID<=0 || $ticketID<=0) return false;

		$strSql = "SELECT 'x' FROM b_ticket WHERE ID=$ticketID and (OWNER_USER_ID=$userID or CREATED_USER_ID=$userID)";
		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		if ($ar = $rs->Fetch()) return true;

		return false;
	}

	// возвращает роли заданного пользователя
	funpublic static ction GetRoles(&$isDemo, &$isSupportClient, &$isSupportTeam, &$isAdmin, &$isAccess, &$userID, $checkRights=true)
	{
		global $DB, $USER, $APPLICATION;
		static $arTicketUserRoles;
		$isDemo = $isSupportClient = $isSupportTeam = $isAdmin = $isAccess = false;
		if (is_object($USER)) $userID = intval($USER->GetID()); else $userID = 0;
		if ($checkRights)
		{
			if ($userID>0)
			{
				if (is_array($arTicketUserRoles) && in_array($userID, array_keys($arTicketUserRoles)))
				{
					$isDemo = $arTicketUserRoles[$userID]["isDemo"];
					$isSupportClient = $arTicketUserRoles[$userID]["isSupportClient"];
					$isSupportTeam = $arTicketUserRoles[$userID]["isSupportTeam"];
					$isAdmin = $arTicketUserRoles[$userID]["isAdmin"];
				}
				else
				{
					$isDemo = CTicket::IsDemo($userID);
					$isSupportClient = CTicket::IsSupportClient($userID);
					$isSupportTeam = CTicket::IsSupportTeam($userID);
					$isAdmin = CTicket::IsAdmin($userID);
					$arTicketUserRoles[$userID] = array(
						"isDemo"			=> $isDemo,
						"isSupportClient"	=> $isSupportClient,
						"isSupportTeam"		=> $isSupportTeam,
						"isAdmin"			=> $isAdmin,
						);
				}
			}
		}
		else $isAdmin = true;

		if ($isDemo || $isSupportClient || $isSupportTeam || $isAdmin) $isAccess = true;
	}

	// возвращает массив ID групп для которых задана роль
	// $role - идентификатор роли
	fpublic static unction GetGroupsByRole($role)
	{
		//Todo: определиться с доступом по умолчанию

		global $APPLICATION, $USER;
		if (!is_object($USER)) $USER = new CUser;

		$arGroups = array(); $arBadGroups = Array();
		$res = $APPLICATION->GetGroupRightList(Array("MODULE_ID" => "support"/*, "G_ACCESS" => $role*/));
		while($ar = $res->Fetch())
		{
			if ($ar["G_ACCESS"] == $role)
				$arGroups[] = $ar["GROUP_ID"];
			else
				$arBadGroups[] = $ar["GROUP_ID"];
		}

		$right = COption::GetOptionString("support", "GROUP_DEFAULT_RIGHT", "D");
		if ($right == $role)
		{
			$res = CGroup::GetList($v1="dropdown", $v2="asc", array("ACTIVE" => "Y"));
			while ($ar = $res->Fetch())
			{
				if (!in_array($ar["ID"],$arGroups) && !in_array($ar["ID"],$arBadGroups))
					$arGroups[] = $ar["ID"];
			}
		}

		return $arGroups;

		/*$arGroups = array();

		$z = CGroup::GetList($v1="dropdown", $v2="asc", array("ACTIVE" => "Y"));
		while($zr = $z->Fetch())
		{
			$arRoles = $APPLICATION->GetUserRoles("support", array(intval($zr["ID"])), "Y", "N");
			if (in_array($role, $arRoles)) $arGroups[] = intval($zr["ID"]);
		}

		return array_unique($arGroups);*/
	}

	// возвращает массив групп с ролью "администратор техподдержки"
public static 	function GetAdminGroups()
	{
		return CTicket::GetGroupsByRole(CTicket::GetAdminRoleID());
	}

	// возвращает массив групп с ролью "сотрудник техподдержки"
public static 	function GetSupportTeamGroups()
	{
		return CTicket::GetGroupsByRole(CTicket::GetSupportTeamRoleID());
	}

	// возвращает массив EMail адресов всех пользователей имеющих заданную роль
	function GetEmailsByRole($role)
	{
		global $DB, $APPLICATION, $USER;
		if (!is_object($USER)) $USER = new CUser;
		$arEmail = array();
		$arGroups = CTicket::GetGroupsByRole($role);
		if (is_array($arGroups) && count($arGroups)>0)
		{
			$rsUser = CUser::GetList($v1="id", $v2="desc", array("ACTIVE" => "Y", "GROUPS_ID" => $arGroups));
			while ($arUser = $rsUser->Fetch()) $arEmail[$arUser["EMAIL"]] = $arUser["EMAIL"];
		}
		return array_unique($arEmail);
	}

	// возвращает массив EMail'ов всех пользователей имеющих роль "администратор"
public static 	function GetAdminEmails()
	{
		return CTicket::GetEmailsByRole(CTicket::GetAdminRoleID());
	}

	// возвращает массив EMail'ов всех пользователей имеющих роль "сотрудник техподдержки"
public static 	function GetSupportTeamEmails()
	{
		return CTicket::GetEmailsByRole(CTicket::GetSupportTeamRoleID());
	}
	
public static 	function GetSupportTeamAndAdminUsers()
	{
		$arUser = array();
		$stg = CTicket::GetGroupsByRole(CTicket::GetSupportTeamRoleID());
		$sag = CTicket::GetGroupsByRole(CTicket::GetAdminRoleID());
		$sg = array();
		if(is_array($stg)) 
		{
			$sg = array_merge($sg, $stg);
		}
		if(is_array($sag)) 
		{
			$sg = array_merge($sg, $sag);
		}
		if(count($sg) > 0)
		{
			$cU = CUser::GetList($v1="id", $v2="asc", array("ACTIVE" => "Y", "GROUPS_ID" => $sg));
			while($arU = $cU->Fetch())
			{
				$arUser[] = intval($arU["ID"]);
			}
		}
		//if(count($arUser) <= 0)
		//{
			$arUser[] = 1;
		//}
		return array_unique($arUser);
	}

	/*****************************************************************
				Группа функций общие для всех классов
	*****************************************************************/

	// проверка полей фильтра
public static 	function CheckFilter($arFilter)
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: CheckFilter<br>Line: ";
		global $DB, $USER, $APPLICATION;
		$str = "";
		$arMsg = Array();

		$arDATES = array(
			"DATE_MODIFY_1",
			"DATE_MODIFY_2",
			"DATE_CREATE_1",
			"DATE_CREATE_2",
			);
		foreach($arDATES as $key)
		{
			if (is_set($arFilter, $key) && strlen($arFilter[$key])>0 && !CheckDateTime($arFilter[$key]))
				$arMsg[] = array("id"=>$key, "text"=> GetMessage("SUP_ERROR_REQUIRED_".$key));
				//$str.= GetMessage("SUP_ERROR_INCORRECT_".$key)."<br>";
		}

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		return true;
	}

	// проверка полей перед вставкой в базу данных
public static 	function CheckFields($arFields, $id, $arRequired)
	{
		global $DB, $USER, $APPLICATION, $MESS;

		$arMsg = Array();

		// проверяем указанные обязательные поля
		if (is_array($arRequired) && count($arRequired)>0)
		{
			foreach($arRequired as $key)
			{
				if ($id<=0 || ($id>0 && is_set($arFields, $key)))
				{
					if (!is_array($arFields[$key]) && (strlen($arFields[$key])<=0 || $arFields[$key]==="NOT_REF"))
					{
						$arMsg[] = array("id"=>$key, "text"=> GetMessage("SUP_ERROR_REQUIRED_".$key));
						//$str.= GetMessage("SUP_ERROR_REQUIRED_".$key)."<br>";
					}
				}
			}
		}

		// проверяем корректность дат
		$arDate = array(
			"DATE_CREATE",
			"DATE_MODIFY",
			"LAST_MESSAGE_DATE",
			);
		foreach($arDate as $key)
		{
			if (strlen($arFields[$key])>0)
			{
				if (!CheckDateTime($arFields[$key]))
					$arMsg[] = array("id"=>$key, "text"=> GetMessage("SUP_ERROR_INCORRECT_".$key));
					//$str.= GetMessage("SUP_ERROR_INCORRECT_".$key)."<br>";
			}
		}

		$arEmail = array(
			"EMAIL",
			);
		foreach($arEmail as $key)
		{
			if (strlen($arFields[$key])>0)
			{
				if (!check_email($arFields[$key]))
					$arMsg[] = array("id"=>$key, "text"=> GetMessage("SUP_ERROR_INCORRECT_".$key));
					//$str.= GetMessage("SUP_ERROR_INCORRECT_".$key)."<br>";
			}
		}

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		return true;
	}

	// предварительно обрабатывает массив значений для вставки в базу данных
public static 	function PrepareFields($arFields, $table, $id)
	{
		global $DB, $USER, $APPLICATION;

		$id = intval($id);
		$arFields_i = array();

		// числа
		$arrNUMBER = array(
			"SLA_ID",
			"AGENT_ID",
			"CATEGORY_ID",
			"CRITICALITY_ID",
			"STATUS_ID",
			"MARK_ID",
			"SOURCE_ID",
			"DIFFICULTY_ID",
			"DICTIONARY_ID",
			"TICKET_ID",
			"MESSAGE_ID",
			"AUTO_CLOSE_DAYS",
			"MESSAGES",
			"OVERDUE_MESSAGES",
			"EXTERNAL_ID",
			"OWNER_USER_ID",
			"OWNER_GUEST_ID",
			"CREATED_USER_ID",
			"CREATED_GUEST_ID",
			"MODIFIED_USER_ID",
			"MODIFIED_GUEST_ID",
			"RESPONSIBLE_USER_ID",
			"LAST_MESSAGE_USER_ID",
			"LAST_MESSAGE_GUEST_ID",
			"CURRENT_RESPONSIBLE_USER_ID",
			"USER_ID",
			"C_NUMBER",
			"C_SORT",
			"PRIORITY",
			"RESPONSE_TIME",
			"NOTICE_TIME",
			"WEEKDAY_NUMBER",
			"MINUTE_FROM",
			"MINUTE_TILL",
			"TIMETABLE_ID"
			);
		foreach($arrNUMBER as $key)
			if (is_set($arFields, $key))
				$arFields_i[$key] = (strlen($arFields[$key])>0) ? intval($arFields[$key]) : "null";

		// тип текста
		$arrTYPE = array(
			"PREVIEW_TYPE",
			"DESCRIPTION_TYPE",
			);
		foreach($arrTYPE as $key)
			if (is_set($arFields, $key))
				$arFields_i[$key] = $arFields[$key]=="text" ? "'text'" : "'html'";

		// булевые
		$arrBOOLEAN = array(
			"AUTO_CLOSED",
			"IS_SPAM",
			"LAST_MESSAGE_BY_SUPPORT_TEAM",
			"IS_HIDDEN",
			"IS_LOG",
			"IS_OVERDUE",
			"IS_SPAM",
			"MESSAGE_BY_SUPPORT_TEAM",
			"SET_AS_DEFAULT",
			"AUTO_CLOSED",
			"HOLD_ON",
			"NOT_CHANGE_STATUS",
			);
		foreach($arrBOOLEAN as $key)
			if (is_set($arFields, $key))
				$arFields_i[$key] = $arFields[$key]=="Y" ? "'Y'" : "'N'";

		// текст
		$arrTEXT = array(
			"OWNER_SID",
			"LAST_MESSAGE_SID",
			"SUPPORT_COMMENTS",
			"MESSAGE",
			"MESSAGE_SEARCH",
			"EXTERNAL_FIELD_1",
			"DESCR",
			"DESCRIPTION",
			);
		foreach($arrTEXT as $key)
			if (is_set($arFields, $key))
				$arFields_i[$key] = (strlen($arFields[$key])>0) ? "'".$DB->ForSql($arFields[$key])."'" : "null";

		// строка
		$arrSTRING = array(
			"NAME",
			"TITLE",
			"CREATED_MODULE_NAME",
			"MODIFIED_MODULE_NAME",
			"HASH",
			"EXTENSION_SUFFIX",
			"C_TYPE",
			"SID",
			"EVENT1",
			"EVENT2",
			"EVENT3",
			"RESPONSE_TIME_UNIT",
			"NOTICE_TIME_UNIT",
			"OPEN_TIME",
			"DEADLINE_SOURCE"
			);
		foreach($arrSTRING as $key)
			if (is_set($arFields, $key))
				$arFields_i[$key] = (strlen($arFields[$key])>0) ? "'".$DB->ForSql($arFields[$key], 255)."'" : "null";

		// даты
		$arDate = array(
			"TIMESTAMP_X",
			"DATE_CLOSE",
			"LAST_MESSAGE_DATE",
			);
		foreach($arDate as $key)
			if (is_set($arFields, $key))
				$arFields_i[$key] = (strlen($arFields[$key])>0) ? $DB->CharToDateFunction($arFields[$key]) : "null";

		/* изображения
		$arIMAGE = array();
		foreach($arIMAGE as $key)
		{
			if (is_set($arFields, $key))
			{
				if (is_array($arFields[$key]))
				{
					$arIMAGE = $arFields[$key];
					$arIMAGE["MODULE_ID"] = "support";
					$arIMAGE["del"] = $_POST[$key."_del"];
					if ($id>0)
					{
						$rs = $DB->Query("SELECT $key FROM $table WHERE ID=$id", false, $err_mess.__LINE__);
						$ar = $rs->Fetch();
						$arIMAGE["old_file"] = $ar[$key];
					}
					if (strlen($arIMAGE["name"])>0 || strlen($arIMAGE["del"])>0)
					{
						$fid = CFile::SaveFile($arIMAGE, "support");
						$arFields_i[$key] = (intval($fid)>0) ? intval($fid) : "null";
					}
				}
				else
				{
					if ($id>0)
					{
						$rs = $DB->Query("SELECT $key FROM $table WHERE ID=$id", false, $err_mess.__LINE__);
						$ar = $rs->Fetch();
						if (intval($ar[$key])>0) CFile::Delete($ar[$key]);
					}
					$arFields_i[$key] = intval($arFields[$key]);
				}
			}
		}*/

		if (is_set($arFields, "CREATED_USER_ID"))
		{
			if (intval($arFields["CREATED_USER_ID"])>0) $arFields_i["CREATED_USER_ID"] = intval($arFields["CREATED_USER_ID"]);
		}
		elseif($id<=0 && $USER->IsAuthorized()) $arFields_i["CREATED_USER_ID"] = intval($USER->GetID());

		if (is_set($arFields, "CREATED_GUEST_ID"))
		{
			if (intval($arFields["CREATED_GUEST_ID"])>0) $arFields_i["CREATED_GUEST_ID"] = intval($arFields["CREATED_GUEST_ID"]);
		}
		elseif($id<=0 && array_key_exists('SESS_GUEST_ID', $_SESSION)) $arFields_i["CREATED_GUEST_ID"] = intval($_SESSION["SESS_GUEST_ID"]);

		if (is_set($arFields, "MODIFIED_USER_ID"))
		{
			if (intval($arFields["MODIFIED_USER_ID"])>0) $arFields_i["MODIFIED_USER_ID"] = intval($arFields["MODIFIED_USER_ID"]);
		}
		elseif ($USER->IsAuthorized()) $arFields_i["MODIFIED_USER_ID"] = intval($USER->GetID());

		if (is_set($arFields, "MODIFIED_GUEST_ID"))
		{
			if (intval($arFields["MODIFIED_GUEST_ID"])>0) $arFields_i["MODIFIED_GUEST_ID"] = intval($arFields["MODIFIED_GUEST_ID"]);
		}
		elseif (array_key_exists('SESS_GUEST_ID', $_SESSION)) $arFields_i["MODIFIED_GUEST_ID"] = intval($_SESSION["SESS_GUEST_ID"]);

		if (is_set($arFields, "DATE_CREATE"))
		{
			if (strlen($arFields["DATE_CREATE"])>0) $arFields_i["DATE_CREATE"] = $DB->CharToDateFunction($arFields["DATE_CREATE"]);
		}
		elseif ($id<=0) $arFields_i["DATE_CREATE"] = $DB->CurrentTimeFunction();


		if (is_set($arFields, "LAST_MESSAGE_DATE"))
		{
			if (strlen($arFields["LAST_MESSAGE_DATE"])>0) $arFields_i["LAST_MESSAGE_DATE"] = $DB->CharToDateFunction($arFields["LAST_MESSAGE_DATE"]);
		}
		elseif ($id<=0) $arFields_i["LAST_MESSAGE_DATE"] = $DB->CurrentTimeFunction();



		if (is_set($arFields, "DATE_MODIFY"))
		{
			if (strlen($arFields["DATE_MODIFY"])>0) $arFields_i["DATE_MODIFY"] = $DB->CharToDateFunction($arFields["DATE_MODIFY"]);
		}
		else $arFields_i["DATE_MODIFY"] = $DB->CurrentTimeFunction();

		// убираем лишние поля для указанной таблицы
		unset($arFields_i["ID"]);
		$ar1 = $DB->GetTableFieldsList($table);
		$ar2 = array_keys($arFields_i);
		$arDiff = array_diff($ar2, $ar1);
		if (is_array($arDiff) && count($arDiff)>0) foreach($arDiff as $value) unset($arFields_i[$value]);

		return $arFields_i;
	}

public static 	function SplitTicket($arParam)
	{
		global $DB;
		$err_mess = (CAllTicket::err_mess())."<br>Function: SplitTicket<br>Line: ";

		$intLastTicketID 	 = IntVal($arParam['SOURCE_TICKET_ID']);
		$stLastTicketTitle	 = htmlspecialcharsEx($arParam['SOURCE_TICKET_TITLE']);
		$intSplitMesageID	 = IntVal($arParam['SOURCE_MESSAGE_NUM']);
		$stSplitMesageDate	 = MakeTimeStamp($arParam['SOURCE_MESSAGE_DATE'], "DD.MM.YYYY HH:MI:SS") ? $arParam['SOURCE_MESSAGE_DATE'] : '';

		// add to the previous post about ticket allocation of posts in a separate branch
		$arFields = array(
			"MESSAGE_CREATED_USER_ID"		=> $arParam['SPLIT_MESSAGE_USER_ID'],
			"MESSAGE_CREATED_MODULE_NAME"	=> "support",
			"MESSAGE_CREATED_GUEST_ID"		=> "null",
			"MESSAGE_SOURCE_ID"				=> $arParam['SOURCE_MESSAGE_ID'],
			"MESSAGE"						=> GetMessage("SUP_SPLIT_MESSAGE_USER_1", array("#MESSAGE_DATE#" => $stSplitMesageDate, "#TITLE#" => '# '.$arParam['SPLIT_TICKET_ID'].' "'.$arParam['SPLIT_TICKET_TITLE'].'"')),
			"LOG"							=> "N",
			"HIDDEN"						=> "N",
			"NOT_CHANGE_STATUS"				=> "Y",
			"MESSAGE_AUTHOR_USER_ID"		=> $arParam['SPLIT_MESSAGE_USER_ID'],
		);
		CTicket::AddMessage($intLastTicketID, $arFields, $arFiles=Array(), "N");

		// add to the previous post about ticket allocation of posts in a separate branch (support log)
		$arFields_log = array(
			"MESSAGE_CREATED_USER_ID"		=> $arParam['SPLIT_MESSAGE_USER_ID'],
			"MESSAGE_CREATED_MODULE_NAME"	=> "support",
			"MESSAGE_CREATED_GUEST_ID"		=> "null",
			"MESSAGE_SOURCE_ID"				=> $arParam['SOURCE_MESSAGE_ID'],
			"MESSAGE"						=> GetMessage("SUP_SPLIT_MESSAGE_LOG_1", array("#MESSAGE_ID#" => $intSplitMesageID, "#TITLE#" => '<a href="ticket_edit.php?ID='.$arParam['SPLIT_TICKET_ID'].'&lang='.LANGUAGE_ID.'"> # '.$arParam['SPLIT_TICKET_ID'].' "'.$arParam['SPLIT_TICKET_TITLE'].'"</a>')),
			"LOG"							=> "Y",
		);
		CTicket::AddMessage($intLastTicketID, $arFields_log, $arFiles_log=Array(), "N");

		// add a new ticket allocation message posted in a separate branch
		$arFields = array(
			"MESSAGE_CREATED_USER_ID"		=> $arParam['SPLIT_MESSAGE_USER_ID'],
			"MESSAGE_CREATED_MODULE_NAME"	=> "support",
			"MESSAGE_CREATED_GUEST_ID"		=> "null",
			"MESSAGE_SOURCE_ID"				=> $arParam['SOURCE_MESSAGE_ID'],
			"MESSAGE"						=> GetMessage("SUP_SPLIT_MESSAGE_USER_2", array("#MESSAGE_DATE#" => $stSplitMesageDate, "#TITLE#" => '# '.$intLastTicketID.' "'.$stLastTicketTitle.'"')),
			"LOG"							=> "N",
			"HIDDEN"						=> "N",
			"NOT_CHANGE_STATUS"				=> "Y",
			"MESSAGE_AUTHOR_USER_ID"		=> $arParam['SPLIT_MESSAGE_USER_ID'],
		);
		CTicket::AddMessage($arParam['SPLIT_TICKET_ID'], $arFields, $arFiles=Array(), "N");

		// add a new ticket allocation message posted in a separate branch (support log)
		$arFields_log = array(
			"MESSAGE_CREATED_USER_ID"		=> $arParam['SPLIT_MESSAGE_USER_ID'],
			"MESSAGE_CREATED_MODULE_NAME"	=> "support",
			"MESSAGE_CREATED_GUEST_ID"		=> "null",
			"MESSAGE_SOURCE_ID"				=> $arParam['SOURCE_MESSAGE_ID'],
			"MESSAGE"						=> GetMessage("SUP_SPLIT_MESSAGE_LOG_2", array("#MESSAGE_ID#" => $intSplitMesageID, "#TITLE#" => '<a href="ticket_edit.php?ID='.$intLastTicketID.'&lang='.LANGUAGE_ID.'"> # '.$intLastTicketID.' "'.$stLastTicketTitle.'"</a>')),
			"LOG"							=> "Y",
		);
		CTicket::AddMessage($arParam['SPLIT_TICKET_ID'], $arFields_log, $arFiles_log=Array(), "N");

		// If the message that we want to separate, there are attached files, copy them
		if (isset($arParam['SPLIT_ATTACH_FILE']))
		{
			$res = CTicket::GetMessageList($by='ID', $order='ASC', array('TICKET_ID'=>$arParam['SPLIT_TICKET_ID']), $is_filtered = false);
			$MESSAGE = $res->Fetch();
			foreach($arParam['SPLIT_ATTACH_FILE'] as $key => $iAttachFile)
			{
				$fid = CFile::CopyFile(intval($iAttachFile));
				if ($fid>0)
				{
					$arFields_fi = array(
						"HASH"				=> "'".$DB->ForSql(md5(uniqid(mt_rand(), true).time()), 255)."'",
						"MESSAGE_ID"		=> $MESSAGE['ID'],
						"FILE_ID"			=> $fid,
						"TICKET_ID"			=> $arParam['SPLIT_TICKET_ID'],
						"EXTENSION_SUFFIX"	=> "null"
					);
					$DB->Insert("b_ticket_message_2_file",$arFields_fi, $err_mess.__LINE__);
				}
			}
		}
	}

	/*****************************************************************
					Группа функций по работе со спамом
	*****************************************************************/

	fupublic static nction MarkMessageAsSpam($messageID, $exactly="Y", $checkRights="Y")
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: MarkMessageAsSpam<br>Line: ";
		global $DB, $USER;
		$messageID = intval($messageID);
		if ($messageID<=0) return;

		$bAdmin = "N";
		$bSupportTeam = "N";
		if ($checkRights=="Y")
		{
			$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";
			$bSupportTeam = (CTicket::IsSupportTeam()) ? "Y" : "N";
		}
		else
		{
			$bAdmin = "Y";
			$bSupportTeam = "Y";
		}

		if (($bAdmin=="Y" || $bSupportTeam=="Y") && CModule::IncludeModule("mail"))
		{
			$exactly = ($exactly=="Y" && $bAdmin=="Y") ? "Y" : "N";
			if ($rsMessage = CTicket::GetMessageByID($messageID, $checkRights))
			{
				if ($arMessage = $rsMessage->Fetch())
				{
					if ($arMessage["IS_LOG"]!="Y")
					{
						$email_id = intval($arMessage["EXTERNAL_ID"]);
						$header = $arMessage["EXTERNAL_FIELD_1"];
						$arFields = array("IS_SPAM" => "'".$exactly."'");
						$DB->Update("b_ticket_message",$arFields,"WHERE ID=".$messageID,$err_mess.__LINE__);

						$exactly = ($exactly=="Y") ? true : false;
						$rsEmail = CMailMessage::GetByID($email_id);
						if ($rsEmail->Fetch())
						{
							CMailMessage::MarkAsSpam($email_id, $exactly);
						}
						else
						{
							CmailFilter::MarkAsSpam($header." \n\r ".$arMessage["MESSAGE"], $exactly);
						}
					}
				}
			}
		}
	}

public static 	function UnMarkMessageAsSpam($messageID, $checkRights="Y")
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: UnMarkMessageAsSpam<br>Line: ";
		global $DB, $USER;
		$messageID = intval($messageID);
		if ($messageID<=0) return;

		$bAdmin = "N";
		$bSupportTeam = "N";
		if ($checkRights=="Y")
		{
			$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";
			$bSupportTeam = (CTicket::IsSupportTeam()) ? "Y" : "N";
		}
		else
		{
			$bAdmin = "Y";
			$bSupportTeam = "Y";
		}

		if (($bAdmin=="Y" || $bSupportTeam=="Y") && CModule::IncludeModule("mail"))
		{
			$rsMessage = CTicket::GetMessageByID($messageID, $checkRights);
			if ($arMessage = $rsMessage->Fetch())
			{
				$arFields = array("IS_SPAM" => "null");
				$DB->Update("b_ticket_message", $arFields, "WHERE ID=".$messageID, $err_mess.__LINE__);

				$email_id = intval($arMessage["EXTERNAL_ID"]);
				$header = $arMessage["EXTERNAL_FIELD_1"];
				$rsEmail = CMailMessage::GetByID($email_id);
				if ($rsEmail->Fetch())
				{
					CMailMessage::MarkAsSpam($email_id, false);
				}
				else
				{
					CmailFilter::DeleteFromSpamBase($header." \n\r ".$arMessage["MESSAGE"], true);
					CmailFilter::MarkAsSpam($header." \n\r ".$arMessage["MESSAGE"], false);
				}
			}
		}
	}

	public static function MarkAsSpam($ticketID, $exactly="Y", $checkRights="Y")
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: MarkAsSpam<br>Line: ";
		global $DB, $USER;
		$ticketID = intval($ticketID);
		if ($ticketID<=0) return;

		$bAdmin = "N";
		$bSupportTeam = "N";
		if ($checkRights=="Y")
		{
			$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";
			$bSupportTeam = (CTicket::IsSupportTeam()) ? "Y" : "N";
		}
		else
		{
			$bAdmin = "Y";
			$bSupportTeam = "Y";
		}

		if ($bAdmin=="Y" || $bSupportTeam=="Y")
		{
			$exactly = ($exactly=="Y" && $bAdmin=="Y") ? "Y" : "N";

			$arFilter = array("TICKET_ID" => $ticketID, "TICKET_ID_EXACT_MATCH" => "Y", "IS_LOG" => "N");
			$a = $b = $c = null;
			if ($rsMessages = CTicket::GetMessageList($a, $b, $arFilter, $c, $checkRights))
			{
				// помечаем исходное сообщение
				if ($arMessage = $rsMessages->Fetch())
				{
					CTicket::MarkMessageAsSpam($arMessage["ID"], $exactly, $checkRights);
				}
			}
			$arFields = array("IS_SPAM" => "'".$exactly."'");
			$DB->Update("b_ticket",$arFields,"WHERE ID=".$ticketID,$err_mess.__LINE__);
		}
	}

public static 	function UnMarkAsSpam($ticketID, $checkRights="Y")
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: UnMarkAsSpam<br>Line: ";
		global $DB, $USER;
		$ticketID = intval($ticketID);
		if ($ticketID<=0) return;

		if ($checkRights=="Y")
		{
			$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";
			$bSupportTeam = (CTicket::IsSupportTeam()) ? "Y" : "N";
		}
		else
		{
			$bAdmin = "Y";
			$bSupportTeam = "Y";
		}

		if ($bAdmin=="Y" || $bSupportTeam=="Y")
		{
			$arFilter = array("TICKET_ID" => $ticketID, "TICKET_ID_EXACT_MATCH" => "Y");
			$a = $b = $c = null;
			if ($rsMessages = CTicket::GetMessageList($a, $b, $arFilter, $c, $checkRights))
			{
				// снимаем отметку о спаме только у первого сообщения
				if ($arMessage = $rsMessages->Fetch())
				{
					CTicket::UnMarkMessageAsSpam($arMessage["ID"], $checkRights);
				}
			}
			$arFields = array("IS_SPAM" => "null");
			$DB->Update("b_ticket",$arFields,"WHERE ID=".$ticketID,$err_mess.__LINE__);
		}
	}


	/*****************************************************************
					Группа функций по управлению обращениями
	*****************************************************************/

	/*function UpdateLastParams($ticketID, $resetAutoClose=false, $changeLastMessageDate = true, $setReopenDefault = true)
	{	
		$err_mess = (CAllTicket::err_mess())."<br>Function: UpdateLastParams<br>Line: ";
		global $DB, $USER;
		$ticketID = intval($ticketID);
		if ($ticketID<=0) return;

		$arFields = array();
		//if ($resetAutoClose=="Y") $arFields["AUTO_CLOSE_DAYS"] = "null";

		
		// определим последнего автора
		$strSql = "
			SELECT
				ID,
				".$DB->DateToCharFunction("DATE_CREATE","FULL")." DATE_CREATE,
				OWNER_USER_ID,
				OWNER_GUEST_ID,
				OWNER_SID
			FROM
				b_ticket_message
			WHERE
				TICKET_ID=$ticketID
			AND(NOT(NOT_CHANGE_STATUS='Y'))
			AND(NOT(IS_HIDDEN='Y'))
			AND(NOT(IS_LOG='Y'))
			AND(NOT(IS_OVERDUE='Y'))
			ORDER BY
				C_NUMBER desc
			";
		$rs = $DB->Query($strSql,false,$err_mess.__LINE__);
		if ($arLastMess = $rs->Fetch())
		{
			$arFields["LAST_MESSAGE_USER_ID"] = $arLastMess["OWNER_USER_ID"];
			if ($changeLastMessageDate)
			{
				$arFields["LAST_MESSAGE_DATE"] = $DB->CharToDateFunction($arLastMess["DATE_CREATE"]);//NN
			}
			$arFields["LAST_MESSAGE_GUEST_ID"] = intval($arLastMess["OWNER_GUEST_ID"]);
			$arFields["LAST_MESSAGE_SID"] = "'".$DB->ForSql($arLastMess["OWNER_SID"],255)."'";
		}

		// определим количество сообщений
		$strSql = "
			SELECT
				SUM(CASE WHEN IS_HIDDEN='Y' THEN 0 ELSE 1 END) MESSAGES,
				SUM(TASK_TIME) ALL_TIME
			FROM
				b_ticket_message
			WHERE
				TICKET_ID = $ticketID
			and (IS_LOG='N' or IS_LOG is null or ".$DB->Length("IS_LOG")."<=0)
			and (IS_OVERDUE='N' or IS_OVERDUE is null or ".$DB->Length("IS_OVERDUE")."<=0)
			";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		$arFields["MESSAGES"] = intval($zr["MESSAGES"]);
		$arFields["PROBLEM_TIME"] = intval($zr["ALL_TIME"]);
		
		
		if ($setReopenDefault)
			$arFields["REOPEN"] = "'N'";
			
		/*
		AUTO_CLOSE_DAYS
		LAST_MESSAGE_DATE
		LAST_MESSAGE_USER_ID
		LAST_MESSAGE_GUEST_ID
		LAST_MESSAGE_SID
		MESSAGES
		REOPEN
		PROBLEM_TIME
		*//*
				
		$DB->Update("b_ticket",$arFields,"WHERE ID='".$ticketID."'",$err_mess.__LINE__);		
	}
	
	//$dateType = CTicket::ADD, CTicket::DELETE, CTicket::CURRENT_DATE
public static 	function UpdateLastParams2($ticketID, $dateType)
	{
		global $DB;
		$strUsers = implode(",", CTicket::GetSupportTeamAndAdminUsers());
		$err_mess = (CAllTicket::err_mess())."<br>Function: UpdateLastParams2<br>Line: ";
		$arFields=array();
		$arFields["D_1_USER_M_AFTER_SUP_M"] = "null";
		$arFields["ID_1_USER_M_AFTER_SUP_M"] = "null";
		$arFields["LAST_MESSAGE_BY_SUPPORT_TEAM"] = "'Y'";
		$arFields["SUPPORT_DEADLINE_NOTIFY"] = "null";
		$arFields["SUPPORT_DEADLINE"] = "null";
		$arFields["IS_OVERDUE"] = "'N'";
		$arFields["IS_NOTIFIED"] = "'N'";
				
		// Get last support response
		$M_ID = 0;
		$strSql = "
			SELECT
				T.ID ID,
				MAX(TM.ID) M_ID
			FROM
				b_ticket T
				INNER JOIN b_ticket_message TM
					ON T.ID = TM.TICKET_ID
						AND T.ID = $ticketID
						AND TM.OWNER_USER_ID IN($strUsers)
						AND (NOT(TM.IS_LOG='Y'))
						AND (NOT(TM.IS_HIDDEN='Y'))
						AND (NOT(TM.NOT_CHANGE_STATUS='Y'))
				
			GROUP BY
				T.ID";
				
		$rs = $DB->Query($strSql, false, $err_mess . __LINE__);
		if($arrRs = $rs->Fetch())
		{
			if(intval($arrRs["M_ID"]) > 0)
			{
				$M_ID = intval($arrRs["M_ID"]);
			}
		}
		
		// Get first user request after last support response
		$strSql = "
			SELECT
				T.SLA_ID,
				T.DATE_CLOSE,
				" . $DB->DateToCharFunction("T.DEADLINE_SOURCE_DATE", "FULL") . " DEADLINE_SOURCE_DATE,
				" . $DB->DateToCharFunction("T.D_1_USER_M_AFTER_SUP_M", "FULL") . " DATE_OLD,
				T.IS_OVERDUE,
				SLA.RESPONSE_TIME_UNIT,
				SLA.RESPONSE_TIME,
				SLA.NOTICE_TIME_UNIT,
				SLA.NOTICE_TIME,
				PZ2.M_ID,
				PZ2.D_1_USER_M_AFTER_SUP_M
			FROM
				b_ticket T
				INNER JOIN b_ticket_sla SLA
					ON T.SLA_ID = SLA.ID
						AND T.ID = $ticketID
				LEFT JOIN (SELECT
					TM.ID M_ID,
					TM.TICKET_ID,
					" . $DB->DateToCharFunction("TM.DATE_CREATE", "FULL") . " D_1_USER_M_AFTER_SUP_M
				FROM
					b_ticket_message TM
					INNER JOIN (SELECT
							T.ID ID,
							MIN(TM.ID) M_ID
						FROM
							b_ticket T
							INNER JOIN b_ticket_message TM
								ON T.ID = TM.TICKET_ID
								AND T.ID = $ticketID
								AND TM.ID > $M_ID
								AND (NOT(TM.IS_LOG='Y'))
								AND (NOT(TM.NOT_CHANGE_STATUS='Y'))
								AND (NOT(TM.IS_HIDDEN='Y'))
							
						GROUP BY
							T.ID) PZ
						ON TM.ID = PZ.M_ID) PZ2
						ON T.ID = PZ2.TICKET_ID
						
		";
		//AND (NOT(TM.IS_HIDDEN='Y'))
		$rs = $DB->Query($strSql, false, $err_mess . __LINE__);
		if(!($arrRs = $rs->Fetch()))
		{
			return;
		}

		$isOverdue = false;
		if(intval($arrRs["M_ID"]) > 0)
		{
			$arFields["D_1_USER_M_AFTER_SUP_M"] = $DB->CharToDateFunction($arrRs["D_1_USER_M_AFTER_SUP_M"]);
			$arFields["ID_1_USER_M_AFTER_SUP_M"] = intval($arrRs["M_ID"]);
			$arFields["LAST_MESSAGE_BY_SUPPORT_TEAM"] = "'N'";

			if($arrRs["IS_OVERDUE"] == "Y" && !(isset($dateType["EVENT"]) && in_array(CTicket::REOPEN, $dateType["EVENT"])))
			{
				unset($arFields["SUPPORT_DEADLINE_NOTIFY"]);
				unset($arFields["SUPPORT_DEADLINE"]);
				unset($arFields["IS_OVERDUE"]);
				unset($arFields["IS_NOTIFIED"]);
				$isOverdue = true;
			}
		}
				
		if( !$isOverdue && intval($arrRs["DATE_CLOSE"]) <= 0 && $arFields["LAST_MESSAGE_BY_SUPPORT_TEAM"] == "'N'")
		{
			$arrRs["ID"] =  $ticketID;
			CTicketReminder::RecalculateSupportDeadlineForOneTicket($arrRs, $arFields, $dateType);
		}
		else
		{
			if(isset($dateType["EVENT"]) && is_array($dateType["EVENT"]) && in_array(CTicket::REOPEN, $dateType["EVENT"]))
			{
				$arFields["DEADLINE_SOURCE_DATE"] = $DB->CharToDateFunction(GetTime(time() + CTimeZone::GetOffset(),"FULL"));
			}
			$DB->Update("b_ticket", $arFields, "WHERE ID='" . $ticketID . "'", $err_mess . __LINE__);
		}
				
	}*/

public static 	function UpdateLastParamsN($ticketID, $dateType, $recalculateSupportDeadline = true, $setReopenDefault = true)
	{	
		$err_mess = (CAllTicket::err_mess())."<br>Function: UpdateLastParamsN<br>Line: ";
		global $DB, $USER;
		$ticketID = intval($ticketID);
		if ($ticketID<=0) return;
		
		$arSupportTeam = CTicket::GetSupportTeamAndAdminUsers();
		
		$arFields = array(
			"LAST_MESSAGE_DATE" => "null",
			"LAST_MESSAGE_USER_ID" => "null",
			"LAST_MESSAGE_GUEST_ID" => "null",
			"LAST_MESSAGE_SID" => "null",
			"D_1_USER_M_AFTER_SUP_M" => "null",
			"ID_1_USER_M_AFTER_SUP_M" => "null",
			"LAST_MESSAGE_BY_SUPPORT_TEAM" => "'Y'",
		);
		if ($setReopenDefault)
		{
			$arFields["REOPEN"] = "'N'";
		}

		$DB->StartUsingMasterOnly();
		
		$strSql = "
			SELECT
				T.ID,
				T.SLA_ID,
				T.DATE_CLOSE,
				" . $DB->DateToCharFunction("T.DEADLINE_SOURCE_DATE", "FULL") . " DEADLINE_SOURCE_DATE,
				" . $DB->DateToCharFunction("T.D_1_USER_M_AFTER_SUP_M", "FULL") . " DATE_OLD,
				T.IS_OVERDUE,
				SLA.RESPONSE_TIME_UNIT,
				SLA.RESPONSE_TIME,
				SLA.NOTICE_TIME_UNIT,
				SLA.NOTICE_TIME
			FROM
				b_ticket T
				INNER JOIN b_ticket_sla SLA
					ON T.SLA_ID = SLA.ID
						AND T.ID = $ticketID
			";
		$rs = $DB->Query($strSql, false, $err_mess . __LINE__);
		$arTicket = $rs->Fetch();
		if(!$arTicket)
		{
			$DB->StopUsingMasterOnly();
			return;
		}

		$arMessagesAll = array();
		$arLastMess = null;
		$arFirstUserMessAfterSupportMess = null;
		$allTime = 0;
		$messages = 0;
		$messAfterSupportMess = true;

		$strSql = "
			SELECT
				ID,
				".$DB->DateToCharFunction("DATE_CREATE","FULL")." DATE_CREATE,
				OWNER_USER_ID,
				OWNER_GUEST_ID,
				OWNER_SID,
				TASK_TIME,
				IS_OVERDUE,
				IS_HIDDEN,
				NOT_CHANGE_STATUS
			FROM
				b_ticket_message
			WHERE
				TICKET_ID=$ticketID
			AND(NOT(IS_LOG='Y'))
			ORDER BY
				C_NUMBER
			";
			//NOT_CHANGE_STATUS
			//IS_HIDDEN
			//IS_OVERDUE
			
		$rs = $DB->Query($strSql,false,$err_mess.__LINE__);
		$DB->StopUsingMasterOnly();
		
		while($arM = $rs->Fetch())
		{
			$arMessagesAll[] = $arM;
			if($arM["IS_OVERDUE"] !== 'Y')
			{
				if($arM["IS_HIDDEN"] !== 'Y')
				{
					if($arM["NOT_CHANGE_STATUS"] !== 'Y')
					{
						$arLastMess = $arM;
					}
					$messages++;
				}
				$allTime += intval($arM["TASK_TIME"]);
			}
			if($arM["IS_HIDDEN"] !== 'Y' && $arM["NOT_CHANGE_STATUS"] !== 'Y')
			{
				if(in_array(intval($arM["OWNER_USER_ID"]), $arSupportTeam))
				{
					$arFirstUserMessAfterSupportMess = null;
					$messAfterSupportMess = true;
				}
				elseif($messAfterSupportMess)
				{
					$arFirstUserMessAfterSupportMess = $arM;
					$messAfterSupportMess = false;
				}
			}
		}

		if($arLastMess !== null)
		{
			$arFields["LAST_MESSAGE_USER_ID"] = $arLastMess["OWNER_USER_ID"];
			//if ($changeLastMessageDate)
			//{
				$arFields["LAST_MESSAGE_DATE"] = $DB->CharToDateFunction($arLastMess["DATE_CREATE"]);
			//}
			$arFields["LAST_MESSAGE_GUEST_ID"] = intval($arLastMess["OWNER_GUEST_ID"]);
			$arFields["LAST_MESSAGE_SID"] = "'" . $DB->ForSql($arLastMess["OWNER_SID"], 255) . "'";
		}
		$arFields["MESSAGES"] = $messages;
		$arFields["PROBLEM_TIME"] = $allTime;
		
		if($arFirstUserMessAfterSupportMess !== null)
		{
			$arFields["D_1_USER_M_AFTER_SUP_M"] = $DB->CharToDateFunction($arFirstUserMessAfterSupportMess["DATE_CREATE"]);
			$arFields["ID_1_USER_M_AFTER_SUP_M"] = intval($arFirstUserMessAfterSupportMess["ID"]);
			$arFields["LAST_MESSAGE_BY_SUPPORT_TEAM"] = "'N'";
		}
		
		if(is_array($dateType["EVENT"]) && in_array(CTicket::REOPEN, $dateType["EVENT"]))
		{
			$arFields["DEADLINE_SOURCE_DATE"] = $DB->CharToDateFunction(GetTime(time() + CTimeZone::GetOffset(),"FULL"));
		}
		elseif($arTicket["IS_OVERDUE"] == "Y")
		{
			$recalculateSupportDeadline = false;
		}
		
		$recalculateSupportDeadline = $recalculateSupportDeadline && (intval($arTicket["DATE_CLOSE"]) <= 0) && ($arFields["LAST_MESSAGE_BY_SUPPORT_TEAM"] == "'N'");
		
		if(!$recalculateSupportDeadline)
		{
			if ($arFields["LAST_MESSAGE_BY_SUPPORT_TEAM"] == "'Y'" || intval($arTicket["DATE_CLOSE"]) > 0)
			{
				$arFields["SUPPORT_DEADLINE_NOTIFY"] = "null";
				$arFields["SUPPORT_DEADLINE"] = "null";
				$arFields["IS_OVERDUE"] = "'N'";
				$arFields["IS_NOTIFIED"] = "'N'";
			}
		}
		
		$DB->Update("b_ticket", $arFields, "WHERE ID='" . $ticketID . "'", $err_mess . __LINE__);
		
		if($recalculateSupportDeadline)
		{
			$arTicket["M_ID"] = $arFirstUserMessAfterSupportMess["ID"];
			$arTicket["D_1_USER_M_AFTER_SUP_M"] = $arFirstUserMessAfterSupportMess["DATE_CREATE"];
			CTicketReminder::RecalculateSupportDeadlineForOneTicket($arTicket, $arFields, $dateType);
		}
		
		/*
		LAST_MESSAGE_DATE
		LAST_MESSAGE_USER_ID
		LAST_MESSAGE_GUEST_ID
		LAST_MESSAGE_SID
		MESSAGES
		REOPEN
		PROBLEM_TIME
		D_1_USER_M_AFTER_SUP_M
		ID_1_USER_M_AFTER_SUP_M
		LAST_MESSAGE_BY_SUPPORT_TEAM
		
		DEADLINE_SOURCE_DATE
		SUPPORT_DEADLINE_NOTIFY
		SUPPORT_DEADLINE
		IS_OVERDUE
		IS_NOTIFIED
		*/
	
	}

public static 	function UpdateMessages($ticketID)
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: UpdateMessages<br>Line: ";
		global $DB;
		$ticketID = intval($ticketID);
		if ($ticketID<=0) return;

		$arFields = array();

		// определим количество сообщений
		$strSql = "
			SELECT
				SUM(CASE WHEN IS_HIDDEN='Y' THEN 0 ELSE 1 END) MESSAGES,
				SUM(TASK_TIME) ALL_TIME
			FROM
				b_ticket_message
			WHERE
				TICKET_ID = $ticketID
			and (IS_LOG='N' or IS_LOG is null or ".$DB->Length("IS_LOG")."<=0)
			and (IS_OVERDUE='N' or IS_OVERDUE is null or ".$DB->Length("IS_OVERDUE")."<=0)
			";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		$arFields["MESSAGES"] = intval($zr["MESSAGES"]);
		$arFields["PROBLEM_TIME"] = intval($zr["ALL_TIME"]);

		$DB->Update("b_ticket",$arFields,"WHERE ID='".$ticketID."'",$err_mess.__LINE__);
	}

public static 	function GetFileList(&$by, &$order, $arFilter=array(), $checkRights = 'N')
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: GetFileList<br>Line: ";
		global $DB, $USER;
		$arSqlSearch = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			$filter_keys = array_keys($arFilter);
			$filterKeysCount = count($filter_keys);
			for ($i=0; $i<$filterKeysCount; $i++)
			{
				$key = $filter_keys[$i];
				$val = $arFilter[$filter_keys[$i]];
				if ((is_array($val) && count($val)<=0) || (!is_array($val) && (strlen($val)<=0 || $val==='NOT_REF')))
					continue;
				$match_value_set = (in_array($key."_EXACT_MATCH", $filter_keys)) ? true : false;
				$key = strtoupper($key);
				switch($key)
				{
					case "LINK_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("MF.ID",$val,$match);
						break;
					case "MESSAGE":
					case "TICKET_ID":
					case "FILE_ID":
					case "HASH":
					case "MESSAGE_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("MF.".$key,$val,$match);
						break;
				}
			}
		}
		if ($by == "s_id")				$strSqlOrder = "ORDER BY MF.ID";
		elseif ($by == "s_file_id")		$strSqlOrder = "ORDER BY F.ID";
		elseif ($by == "s_message_id")	$strSqlOrder = "ORDER BY MF.MESSAGE_ID";
		else
		{
			$by = "s_id";
			$strSqlOrder = "ORDER BY MF.ID";
		}
		if ($order=="desc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		else
		{
			$strSqlOrder .= " asc ";
			$order="asc";
		}

		$messageJoin = '';
		$ticketJoin = '';

		if ($checkRights == 'Y')
		{
			$bAdmin = (CTicket::IsAdmin()) ? 'Y' : 'N';
			$bSupportTeam = (CTicket::IsSupportTeam()) ? 'Y' : 'N';
			$bSupportClient = (CTicket::IsSupportClient()) ? 'Y' : 'N';
			$bDemo = (CTicket::IsDemo()) ? 'Y' : 'N';
			$uid = intval($USER->GetID());

			if ($bAdmin!='Y' && $bSupportTeam!='Y' && $bSupportClient!='Y' && $bDemo!='Y') return false;

			if (!($bAdmin == 'Y' || $bDemo == 'Y'))
			{
				// a list of users who own or are responsible for tickets, which we can show to our current user
				$ticketUsers = array($uid);

				// check if user has groups
				$result = $DB->Query('SELECT GROUP_ID FROM b_ticket_user_ugroup WHERE USER_ID = '.$uid.' AND CAN_VIEW_GROUP_MESSAGES = \'Y\'');
				if ($result)
				{
					// collect members of these groups
					$uGroups = array();

					while ($row = $result->Fetch())
					{
						$uGroups[] = $row['GROUP_ID'];
					}

					if (!empty($uGroups))
					{
						$result = $DB->Query('SELECT USER_ID FROM b_ticket_user_ugroup WHERE GROUP_ID IN ('.join(',', $uGroups).')');
						if ($result)
						{
							while ($row = $result->Fetch())
							{
								$ticketUsers[] = $row['USER_ID'];
							}
						}
					}
				}

				// build sql
				$strSqlSearchUser = "";

				if($bSupportTeam == 'Y')
				{
					$strSqlSearchUser = 'T.RESPONSIBLE_USER_ID IN ('.join(',', $ticketUsers).')';
				}
				elseif ($bSupportClient == 'Y')
				{
					$strSqlSearchUser = 'T.OWNER_USER_ID IN ('.join(',', $ticketUsers).')';
				}

				if ($strSqlSearchUser)
				{
					$ticketJoin = 'INNER JOIN b_ticket T ON (T.ID = MF.TICKET_ID)';
					$arSqlSearch[] = $strSqlSearchUser;
				}
			}

			if ($bSupportTeam!="Y" && $bAdmin!="Y")
			{
				$messageJoin = 'INNER JOIN b_ticket_message M ON (M.ID = MF.MESSAGE_ID)';

				$arSqlSearch[] = "M.IS_HIDDEN='N'";
				$arSqlSearch[] = "M.IS_LOG='N'";
			}
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				F.*, ".$DB->DateToCharFunction("F.TIMESTAMP_X")." as TIMESTAMP_X,
				MF.ID as LINK_ID,
				MF.HASH,
				MF.MESSAGE_ID,
				MF.TICKET_ID,
				MF.EXTENSION_SUFFIX
			FROM
				b_ticket_message_2_file MF
			INNER JOIN b_file F ON (MF.FILE_ID = F.ID)
			$ticketJoin
			$messageJoin
			WHERE
				$strSqlSearch
			$strSqlOrder
		";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}


	/**
	* <p>Метод возвращает данные по одному сообщению.</p>
	*
	*
	* @param int $ID  ID сообщения.</bod
	*
	* @param char(1) $CHECK_RIGHTS = "Y" "Y" - сообщение будет выбрано только в том случае если у
	* пользователя есть права на это сообщение (по умолчанию); "N" -
	* сообщение будет выбрано независимо от прав текущего
	* пользователя. Необязательный параметр. Изменен на <b>checkRights</b> с
	* версии 12.0.0
	*
	* @param char(1) $get_user_name = "Y" "Y" - при выборке сообщения будут также выбраны такие поля как
	* OWNER_EMAIL, OWNER_LOGIN, OWNER_NAME, CREATED_EMAIL, CREATED_LOGIN, CREATED_NAME, MODIFIED_EMAIL, MODIFIED_LOGIN,
	* MODIFIED_NAME, описывающие параметры пользователей имевших отношение к
	* данному сообщению (по умолчанию); "N" - вышеперечисленные поля не
	* будут выбраны, но зато это ускорит работу метода. Необязательный
	* параметр.
	*
	* @return record 
	*
	* <h4>Example</h4> 
	* <pre>
	* Array
	* (
	*     [ID] =&gt; 3585
	*     [TIMESTAMP_X] =&gt; 27.04.2004 12:46:02
	*     [DATE_CREATE] =&gt; 13.04.2004 11:57:04
	*     [C_NUMBER] =&gt; 22
	*     [TICKET_ID] =&gt; 647
	*     [MESSAGE] =&gt; <quote>Хорошо, тогда как насчет такой мысли: если группа указана в загружаемом файле, то она активируется (данное поведение можно регулировать при создании схемы загрузки). </quote>
	* Цитирую себя же. Вспомнил вот что: деактивировать рубрику при загрузке возможность есть (соответствующая галочка при создании схемы), а активировать- нет.
	* Разное получается поведение. А хотелось бы одинакового
	*     [MESSAGE_SEARCH] =&gt; <quote>ХОРОШО, ТОГДА КАК НАСЧЕТ ТАКОЙ МЫСЛИ: ЕСЛИ ГРУППА УКАЗАНА В ЗАГРУЖАЕМОМ ФАЙЛЕ, ТО ОНА АКТИВИРУЕТСЯ (ДАННОЕ ПОВЕДЕНИЕ МОЖНО РЕГУЛИРОВАТЬ ПРИ СОЗДАНИИ СХЕМЫ ЗАГРУЗКИ). </quote>
	* ЦИТИРУЮ СЕБЯ ЖЕ. ВСПОМНИЛ ВОТ ЧТО: ДЕАКТИВИРОВАТЬ РУБРИКУ ПРИ ЗАГРУЗКЕ ВОЗМОЖНОСТЬ ЕСТЬ (СООТВЕТСВУЮЩАЯ ГАЛОЧКА ПРИ СОЗДАНИИ СХЕМЫ), А АКТИВИРОВАТЬ- НЕТ.
	* РАЗНОЕ ПОЛУЧАЕТСЯ ПОВЕДЕНИЕ. А ХОТЕЛОСЬ БЫ ОДИНАКОВОГО
	*     [IMAGE] =&gt; 996
	*     [OWNER_USER_ID] =&gt; 166
	*     [OWNER_GUEST_ID] =&gt; 16218
	*     [OWNER_SID] =&gt; 
	*     [SOURCE_ID] =&gt; 0
	*     [CREATED_USER_ID] =&gt; 166
	*     [CREATED_GUEST_ID] =&gt; 16218
	*     [CREATED_MODULE_NAME] =&gt; support
	*     [MODIFIED_USER_ID] =&gt; 2
	*     [MODIFIED_GUEST_ID] =&gt; 6221
	*     [OWNER_EMAIL] =&gt; mail@server.com
	*     [OWNER_LOGIN] =&gt; ant
	*     [OWNER_NAME] =&gt; Поручик Лукаш
	*     [CREATED_EMAIL] =&gt; mail@server.com
	*     [CREATED_LOGIN] =&gt; ant
	*     [CREATED_NAME] =&gt; Поручик Лукаш
	*     [MODIFIED_EMAIL] =&gt; mail@server.com
	*     [MODIFIED_LOGIN] =&gt; nessy
	*     [MODIFIED_NAME] =&gt; Vitaly Kaplich
	* )
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/support/classes/cticket/getmessagebyid.php
	* @author Bitrix
	*/
	public static 	function GetMessageByID($id, $checkRights="Y", $get_user_name="Y")
	{
		$by = $order = $is_filtered = null;
		return CTicket::GetMessageList($by, $order, array("ID" => $id, "ID_EXACT_MATCH" => "Y"), $is_filtered, $checkRights, $get_user_name);
	}


	/**
	* <p>Метод возвращает данные по одному обращению.</p>
	*
	*
	* @param int $ID  ID обращения.
	*
	* @param char(2) $lang = LANG Двухсимвольный код языка в формате которого необходимо выбрать
	* временные параметры обращения (время создания, изменения,
	* закрытия); необязательный параметр, по умолчанию - код текущего
	* сайта.
	*
	* @param char(1) $CHECK_RIGHTS = "Y" Необязательный параметр. "Y" - будут выбраны только те обращения
	* которые текущий пользователь может просматривать (по умолчанию);
	* "N" - выбирать все обращения независимо от прав текущего
	* пользователя. Изменен на <b>checkRights</b> c 12.0.0
	*
	* @param char(1) $get_user_name = "Y" Необязательный параметр. "Y" - при выборке обращений будут также
	* выбраны такие поля как OWNER_LOGIN, OWNER_NAME, RESPONSIBLE_LOGIN, RESPONSIBLE_NAME, MODIFIED_LOGIN,
	* MODIFIED_NAME, LAST_MESSAGE_LOGIN, LAST_MESSAGE_NAME, CREATED_LOGIN, CREATED_EMAIL, CREATED_NAME, описывающие
	* параметры пользователей имевших отношение к данному обращению
	* (по умолчанию); "N" - вышеперечисленные поля не будут выбраны, но
	* зато это ускорит работу метода.
	*
	* @param char(1) $get_dictionary_name = "Y" Необязательный параметр. "Y" - при выборке обращений будут также
	* выбраны такие поля как CATEGORY_NAME, CATEGORY_SID, CRITICALITY_NAME, CRITICALITY_SID, STATUS_NAME,
	* STATUS_SID, MARK_NAME, MARK_SID, SOURCE_NAME, SOURCE_SID, описывающие поля из справочника
	* техподдержки (по умолчанию); "N" - вышеперечисленные поля не будут
	* выбраны, но зато это ускорит работу метода. Удален с 4.0.6
	*
	* @return record 
	*
	* <h4>Example</h4> 
	* <pre>
	* Array
	* (
	*     [ID] =&gt; 647
	*     [LID] =&gt; ru
	*     [DATE_CREATE] =&gt; 17.03.2004 15:27:05
	*     [TIMESTAMP_X] =&gt; 19.04.2004 13:37:30
	*     [DATE_CLOSE] =&gt; 
	*     [AUTO_CLOSED] =&gt; 
	*     [AUTO_CLOSE_DAYS] =&gt; 
	*     [CATEGORY_ID] =&gt; 27
	*     [CRITICALITY_ID] =&gt; 8
	*     [STATUS_ID] =&gt; 14
	*     [MARK_ID] =&gt; 21
	*     [SOURCE_ID] =&gt; 
	*     [TITLE] =&gt; Импорт-экспорт в торговый каталог
	*     [MESSAGES] =&gt; 30
	*     [OWNER_USER_ID] =&gt; 166
	*     [OWNER_GUEST_ID] =&gt; 14649
	*     [OWNER_SID] =&gt; 
	*     [CREATED_USER_ID] =&gt; 166
	*     [CREATED_GUEST_ID] =&gt; 14649
	*     [CREATED_MODULE_NAME] =&gt; support
	*     [RESPONSIBLE_USER_ID] =&gt; 12
	*     [MODIFIED_USER_ID] =&gt; 166
	*     [MODIFIED_GUEST_ID] =&gt; 16218
	*     [MODIFIED_MODULE_NAME] =&gt; support
	*     [LAST_MESSAGE_USER_ID] =&gt; 166
	*     [LAST_MESSAGE_GUEST_ID] =&gt; 16218
	*     [LAST_MESSAGE_SID] =&gt; 
	*     [SUPPORT_COMMENTS] =&gt; 
	*     [OWNER_LOGIN] =&gt; ant
	*     [OWNER_EMAIL] =&gt; mail@server.com
	*     [OWNER_NAME] =&gt; Поручик Лукаш
	*     [RESPONSIBLE_LOGIN] =&gt; wizard
	*     [RESPONSIBLE_EMAIL] =&gt; mail@server.com
	*     [RESPONSIBLE_NAME] =&gt; Фельдкурат Кац
	*     [MODIFIED_LOGIN] =&gt; ant
	*     [MODIFIED_EMAIL] =&gt; mail@server.com
	*     [MODIFIED_NAME] =&gt; Поручик Лукаш
	*     [LAST_MESSAGE_LOGIN] =&gt; ant
	*     [LAST_MESSAGE_EMAIL] =&gt; mail@server.com
	*     [LAST_MESSAGE_NAME] =&gt; Поручик Лукаш
	*     [CREATED_LOGIN] =&gt; ant
	*     [CREATED_EMAIL] =&gt; mail@server.com
	*     [CREATED_NAME] =&gt; Поручик Лукаш
	*     [CATEGORY_NAME] =&gt; Установка и настройка
	*     [CATEGORY_DESC] =&gt; 
	*     [CATEGORY_SID] =&gt; 
	*     [CRITICALITY_NAME] =&gt; Средняя
	*     [CRITICALITY_DESC] =&gt; 
	*     [CRITICALITY_SID] =&gt; middle
	*     [STATUS_NAME] =&gt; В стадии решения
	*     [STATUS_DESC] =&gt; 
	*     [STATUS_SID] =&gt; 
	*     [MARK_NAME] =&gt; Ответ устраивает
	*     [MARK_DESC] =&gt; 
	*     [MARK_SID] =&gt; 
	*     [SOURCE_NAME] =&gt; 
	*     [SOURCE_DESC] =&gt; 
	*     [SOURCE_SID] =&gt; 
	*     [LAMP] =&gt; yellow
	* )
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/support/classes/cticket/getbyid.php
	* @author Bitrix
	*/
	public static 	function GetByID($id, $lang=LANG, $checkRights="Y", $get_user_name="Y", $get_extra_names="Y", $arParams = Array())
	{
		$by = $order = $is_filtered = null;
		return CTicket::GetList($by, $order, array("ID" => $id, "ID_EXACT_MATCH" => "Y"), $is_filtered, $checkRights, $get_user_name, $get_extra_names, $lang, $arParams);
	}

	funpublic static ction getMaxId()
	{
		global $DB;

		$id = null;

		$result = $DB->Query("SELECT MAX(ID) as MAX_ID FROM b_ticket");
		if ($result)
		{
			$row = $result->Fetch();
			$id = $row['MAX_ID'];
		}

		return $id;
	}


	/**
	* <p>Метод удаляет обращение.</p>
	*
	*
	* @param int $TICKET_ID  ID обращения. С версии 12.0.0 изменен на <b>ticketID</b>.
	*
	* @param char(1) $CHECK_RIGHTS = "Y" "Y" - необходимо проверить право на удаление у текущего
	* пользователя (по умолчанию); "N" - прав проверять не надо.
	* Необязательный параметр. С версии 12.0.0 изменен на <b>checkRights</b>.
	*
	* @return record 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/support/classes/cticket/delete.php
	* @author Bitrix
	*/
	public static 	function Delete($ticketID, $checkRights="Y")
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: Delete<br>Line: ";
		global $DB, $USER;
		$ticketID = intval($ticketID);
		if ($ticketID<=0) return;
		$bAdmin = "N";
		if ($checkRights=="Y")
		{
			$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";
		}
		else
		{
			$bAdmin = "Y";
		}
		if ($bAdmin=="Y")
		{
			if (CTicket::ExecuteEvents('OnBeforeTicketDelete', $ticketID, false) === false)
				return false;
			CTicket::ExecuteEvents('OnTicketDelete', $ticketID, false);

			$strSql = "
				SELECT
					F.ID
				FROM
					b_ticket_message_2_file MF,
					b_file F
				WHERE
					MF.TICKET_ID = '$ticketID'
				and F.ID=MF.FILE_ID
				";
			$z = $DB->Query($strSql, false, $err_mess.__LINE__);
			while ($zr = $z->Fetch()) CFile::Delete($zr["ID"]);

			//CTicketReminder::Delete($ticketID);
			$DB->Query("DELETE FROM b_ticket_message_2_file WHERE TICKET_ID='$ticketID'", false, $err_mess.__LINE__);
			$DB->Query("DELETE FROM b_ticket_message WHERE TICKET_ID='$ticketID'", false, $err_mess.__LINE__);
			$GLOBALS["USER_FIELD_MANAGER"]->Delete("SUPPORT", $ticketID);
			$DB->Query("DELETE FROM b_ticket WHERE ID='$ticketID'", false, $err_mess.__LINE__);

			if (CSupportSearch::isIndexExists())
			{
				CSupportSearch::reindexTicket($ticketID);
			}
		}
	}

	function UpdateOnline($ticketID, $userID=false, $currentMode="")
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: UpdateOnline<br>Line: ";
		global $DB, $USER;
		if ($userID===false && is_object($USER)) $userID = $USER->GetID();
		$ticketID = intval($ticketID);
		$userID = intval($userID);
		if ($ticketID<=0 || $userID<=0) return;
		$arFields = array(
			"TIMESTAMP_X"	=> $DB->GetNowFunction(),
			"TICKET_ID"		=> $ticketID,
			"USER_ID"		=> $userID,
			);
		if ($currentMode!==false)
		{
			$arFields["CURRENT_MODE"] = strlen($currentMode)>0 ? "'".$DB->ForSQL($currentMode, 20)."'" : "null";
		}
		$rows = $DB->Update("b_ticket_online", $arFields, "WHERE TICKET_ID=$ticketID and USER_ID=$userID", $err_mess.__LINE__);
		if (intval($rows)<=0)
		{
			$DB->Insert("b_ticket_online",$arFields, $err_mess.__LINE__);
		}
	}

public static 	function SetTicket($arFields, $ticketID="", $checkRights="Y", $sendEmailToAuthor="Y", $sendEmailToTechsupport="Y")
	{
		//global $DB;
		//$DB->DebugToFile = true;
		$messageID = null;
		$x = CTicket::Set($arFields, $messageID, $ticketID, $checkRights, $sendEmailToAuthor, $sendEmailToTechsupport);
		//$DB->DebugToFile = false;
		return $x;
	}
	
	/*****************************************************************
									SET
	*****************************************************************/
	
public static 	static function addSupportText($cn)
	{
		if($cn > 0 && (CTicket::IsSupportTeam($cn) || CTicket::IsAdmin($cn))) return " " . GetMessage("SUP_TECHSUPPORT_HINT");
		return "";
	}
	
public static 	static function EmailsFromStringToArray($emails, $res = null)
	{
		if(!is_array($res)) $res = array();
		$arEmails = explode(",", $emails);
		if(is_array($arEmails) && count($arEmails) > 0)
		{
			foreach($arEmails as $email)
			{
				$email = trim($email);
				if(strlen($email) > 0)
				{
					preg_match_all("#[<\[\(](.*?)[>\]\)]#i".BX_UTF_PCRE_MODIFIER, $email, $arr);
					if(is_array($arr[1]) && count($arr[1]) > 0)
					{
						foreach($arr[1] as $email)
						{
							$email = trim($email);
							if(strlen($email) > 0 && !in_array($email, $res) && check_email($email))
							{
								$res[] = $email;
							}
						}
					}
					elseif(!in_array($email, $res) && check_email($email))
					{
						$res[] = $email;
					}
				}
			}
		}
		TrimArr($res);
		return $res;
	}
	
public static 	static function GetCSupportTableFields($name, $arrOrTable = CSupportTableFields::C_Array)
	{
		$n = CSupportTableFields::VT_NUMBER;
		$s = CSupportTableFields::VT_STRING;
		$yn = CSupportTableFields::VT_Y_N;
		$ynn = CSupportTableFields::VT_Y_N_NULL;
		$d = CSupportTableFields::VT_DATE;
		$dt = CSupportTableFields::VT_DATE_TIME;
		$tables = array(
			"b_ticket" => array(
				"ID" =>								array("TYPE" => $n,	"DEF_VAL" => 0,		"AUTO_CALCULATED" => true),
				"SITE_ID" =>						array("TYPE" => $s,	"DEF_VAL" => "", 	"MAX_STR_LEN" => 2),
				"DATE_CREATE" =>					array("TYPE" => $dt,	"DEF_VAL" => null	),
				"DAY_CREATE" =>						array("TYPE" => $d,	"DEF_VAL" => null	),
				"TIMESTAMP_X" =>					array("TYPE" => $dt,	"DEF_VAL" => null	),
				"DATE_CLOSE" =>						array("TYPE" => $dt,	"DEF_VAL" => null	),
				"AUTO_CLOSED" =>					array("TYPE" => $yn,	"DEF_VAL" => null	),
				"AUTO_CLOSE_DAYS" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"SLA_ID" =>							array("TYPE" => $n,	"DEF_VAL" => 1		),
				"NOTIFY_AGENT_ID" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"EXPIRE_AGENT_ID" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"OVERDUE_MESSAGES" =>				array("TYPE" => $n,	"DEF_VAL" => 0		),
				"IS_NOTIFIED" =>					array("TYPE" => $yn,	"DEF_VAL" => "N"	),
				"IS_OVERDUE" =>						array("TYPE" => $yn,	"DEF_VAL" => "N"	),
				"CATEGORY_ID" =>					array("TYPE" => $n,	"DEF_VAL" => null	),
				"CRITICALITY_ID" =>					array("TYPE" => $n,	"DEF_VAL" => null	),
				"STATUS_ID" =>						array("TYPE" => $n,	"DEF_VAL" => null	),
				"MARK_ID" =>						array("TYPE" => $n,	"DEF_VAL" => null	),
				"SOURCE_ID" =>						array("TYPE" => $n,	"DEF_VAL" => null	),
				"DIFFICULTY_ID" =>					array("TYPE" => $n,	"DEF_VAL" => null	),
				"TITLE" =>							array("TYPE" => $s,	"DEF_VAL" => "", 	"MAX_STR_LEN" => 255),
				"MESSAGES" =>						array("TYPE" => $n,	"DEF_VAL" => 0		),
				"IS_SPAM" =>						array("TYPE" => $ynn,	"DEF_VAL" => null	),
				"OWNER_USER_ID" =>					array("TYPE" => $n,	"DEF_VAL" => null	),
				"OWNER_GUEST_ID" =>					array("TYPE" => $n,	"DEF_VAL" => null	),
				"OWNER_SID" =>						array("TYPE" => $s,	"DEF_VAL" => null, 	"MAX_STR_LEN" => 255),
				"CREATED_USER_ID" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"CREATED_GUEST_ID" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"CREATED_MODULE_NAME" =>			array("TYPE" => $s,	"DEF_VAL" => "support", 	"MAX_STR_LEN" => 255),
				"RESPONSIBLE_USER_ID" =>			array("TYPE" => $n,	"DEF_VAL" => null	),
				"MODIFIED_USER_ID" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"MODIFIED_GUEST_ID" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"MODIFIED_MODULE_NAME" =>			array("TYPE" => $s,	"DEF_VAL" => null, 	"MAX_STR_LEN" => 255),
				"LAST_MESSAGE_USER_ID" =>			array("TYPE" => $n,	"DEF_VAL" => null	),
				"LAST_MESSAGE_GUEST_ID" =>			array("TYPE" => $n,	"DEF_VAL" => null	),
				"LAST_MESSAGE_SID" =>				array("TYPE" => $s,	"DEF_VAL" => null, 	"MAX_STR_LEN" => 255),
				"LAST_MESSAGE_BY_SUPPORT_TEAM" =>	array("TYPE" => $yn,	"DEF_VAL" => "N"	),
				"LAST_MESSAGE_DATE" =>				array("TYPE" => $dt,	"DEF_VAL" => null	),
				"SUPPORT_COMMENTS" =>				array("TYPE" => $s,	"DEF_VAL" => null, 	"MAX_STR_LEN" => 255),
				"PROBLEM_TIME" =>					array("TYPE" => $n,	"DEF_VAL" => null	),
				"HOLD_ON" =>						array("TYPE" => $yn,	"DEF_VAL" => "N"	),
				"REOPEN" =>							array("TYPE" => $yn,	"DEF_VAL" => "N"	),
				"COUPON" =>							array("TYPE" => $s,	"DEF_VAL" => null, 	"MAX_STR_LEN" => 255),
				"DEADLINE_SOURCE_DATE" =>			array("TYPE" => $dt,	"DEF_VAL" => null	),
			),
			
			"EventFields" => array(
				"ID" =>								array("TYPE" => $n,	"DEF_VAL" => null	),
				"LANGUAGE" =>						array("TYPE" => $s,	"DEF_VAL" => null	),
				"LANGUAGE_ID" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"WHAT_CHANGE" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"DATE_CREATE" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"TIMESTAMP" =>						array("TYPE" => $s,	"DEF_VAL" => null	),
				"DATE_CLOSE" =>						array("TYPE" => $s,	"DEF_VAL" => null	),
				"TITLE" =>							array("TYPE" => $s,	"DEF_VAL" => null	),
				"STATUS" =>							array("TYPE" => $s,	"DEF_VAL" => null	),
				"DIFFICULTY" =>						array("TYPE" => $s,	"DEF_VAL" => null	),
				"CATEGORY" =>						array("TYPE" => $s,	"DEF_VAL" => null	),
				"CRITICALITY" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"RATE" =>							array("TYPE" => $s,	"DEF_VAL" => null	),
				"SLA" =>							array("TYPE" => $s,	"DEF_VAL" => null	),
				"SOURCE" =>							array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGES_AMOUNT" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"SPAM_MARK" =>						array("TYPE" => $s,	"DEF_VAL" => null	),
				"ADMIN_EDIT_URL" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"PUBLIC_EDIT_URL" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"OWNER_EMAIL" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"OWNER_USER_ID" =>					array("TYPE" => $n,	"DEF_VAL" => null	),
				"OWNER_USER_NAME" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"OWNER_USER_LOGIN" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"OWNER_USER_EMAIL" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"OWNER_TEXT" =>						array("TYPE" => $s,	"DEF_VAL" => null	),
				"OWNER_SID" =>						array("TYPE" => $s,	"DEF_VAL" => null	),
				"SUPPORT_EMAIL" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"RESPONSIBLE_USER_ID" =>			array("TYPE" => $n,	"DEF_VAL" => null	),
				"RESPONSIBLE_USER_NAME" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"RESPONSIBLE_USER_LOGIN" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"RESPONSIBLE_USER_EMAIL" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"RESPONSIBLE_TEXT" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"SUPPORT_ADMIN_EMAIL" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"CREATED_USER_ID" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"CREATED_USER_LOGIN" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"CREATED_USER_EMAIL" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"CREATED_USER_NAME" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"CREATED_MODULE_NAME" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"CREATED_TEXT" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"MODIFIED_USER_ID" =>				array("TYPE" => $n,	"DEF_VAL" => null	),
				"MODIFIED_USER_LOGIN" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"MODIFIED_USER_EMAIL" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"MODIFIED_USER_NAME" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"MODIFIED_MODULE_NAME" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"MODIFIED_TEXT" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_AUTHOR_USER_ID" =>			array("TYPE" => $n,	"DEF_VAL" => null	),
				"MESSAGE_AUTHOR_USER_NAME" =>		array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_AUTHOR_USER_LOGIN" =>		array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_AUTHOR_USER_EMAIL" =>		array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_AUTHOR_TEXT" =>			array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_AUTHOR_SID" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_SOURCE" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_HEADER" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_BODY" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"MESSAGE_FOOTER" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"FILES_LINKS" =>					array("TYPE" => $s,	"DEF_VAL" => null	),
				"IMAGE_LINK" =>						array("TYPE" => $s,	"DEF_VAL" => null	),
				"SUPPORT_COMMENTS" =>				array("TYPE" => $s,	"DEF_VAL" => null	),
			),
			
		);
		
		if(!array_key_exists($name, $tables)) return null;
		
		return new CSupportTableFields($tables[$name], $arrOrTable);
	}
		
public static 	function Set_getFilesLinks($arFiles, $lID)
	{
		// сформируем ссылки на прикрепленые файлы
		$fl = null;
		if(is_array($arFiles) && count($arFiles) > 0)
		{
			$fl = GetMessage("SUP_ATTACHED_FILES")."\n";
			foreach($arFiles as $arFile)
			{
				$fl .= (CMain::IsHTTPS()? "https" : "http")."://" . $_SERVER["HTTP_HOST"] . "/bitrix/tools/ticket_show_file.php?hash=" . $arFile["HASH"] . "&action=download&lang=" . $lID . "\n";
			}
			if (strlen($fl) > 0) $fl .= "\n";
		}
		return $fl;
	}
	
public static 	function Set_WriteLog($nf, $v, $mf)
	{
		
		$change_log = "";
		$v->change = "";
		$v->change_hidden = "";
				
		if($v->isNew) // NEW
		{
			$v->arChange = array(); 
			if(strlen($nf->SLA_NAME) > 0)			$v->arChange["SLA_ID"] = "Y";
			if(strlen($nf->CATEGORY_NAME) > 0)		$v->arChange["CATEGORY_ID"] = "Y";
			if(strlen($nf->CRITICALITY_NAME) > 0)	$v->arChange["CRITICALITY_ID"] = "Y";				
			if(strlen($nf->STATUS_NAME) > 0)			$v->arChange["STATUS_ID"] = "Y";			
			if(strlen($nf->DIFFICULTY_NAME) > 0)		$v->arChange["DIFFICULTY_ID"] = "Y";
			if(strlen($mf->RESPONSIBLE_TEXT) > 0)	$v->arChange["RESPONSIBLE_USER_ID"] = "Y";
			if($v->bActiveCoupon) $change_log .= "<li>" . htmlspecialcharsEx(GetMessage('SUP_IS_SUPER_COUPON', array('#COUPON#' => $v->V_COUPON)));
		}
		
		if(!is_array($v->arChange) || count($v->arChange) <= 0) return;
				
		foreach($v->arChange as $key => $value)
		{
			if ($value != "Y") continue;
			
			switch ($key)
			{
				case "CLOSE":
					$v->change .= GetMessage("SUP_REQUEST_CLOSED")."\n";
					$change_log .= "<li>".GetMessage("SUP_REQUEST_CLOSED_LOG");
					break;
				case "OPEN":
					$v->change .= GetMessage("SUP_REQUEST_OPENED")."\n";
					$change_log .= "<li>".GetMessage("SUP_REQUEST_OPENED_LOG");
					break;
					
				case "HOLD_ON_ON":
					$v->change .= GetMessage("SUP_HOLD_ON_ON") . "\n";
					$change_log .= "<li>" . GetMessage("SUP_HOLD_ON_ON_LOG");
					break;
				case "HOLD_ON_OFF":
					$v->change .= GetMessage("SUP_HOLD_ON_OFF") . "\n";
					$change_log .= "<li>" . GetMessage("SUP_HOLD_ON_OFF_LOG");
					break;
					
				case "RESPONSIBLE_USER_ID":
					$v->change .= GetMessage("SUP_RESPONSIBLE_CHANGED") . "\n";
					$change_log .= "<li>" . htmlspecialcharsEx(GetMessage("SUP_RESPONSIBLE_CHANGED_LOG", array("#VALUE#" => $mf->RESPONSIBLE_TEXT)));
					break;
				case "CATEGORY_ID":
					$v->change .= GetMessage("SUP_CATEGORY_CHANGED") . "\n";
					$change_log .= "<li>" . htmlspecialcharsEx(GetMessage("SUP_CATEGORY_CHANGED_LOG", array("#VALUE#" => $nf->CATEGORY_NAME)));
					break;
				case "CRITICALITY_ID":
					$v->change .= GetMessage("SUP_CRITICALITY_CHANGED") . "\n";
					$change_log .= "<li>" . htmlspecialcharsEx(GetMessage("SUP_CRITICALITY_CHANGED_LOG", array("#VALUE#" => $nf->CRITICALITY_NAME)));
					break;
				case "STATUS_ID":
					$v->change .= GetMessage("SUP_STATUS_CHANGED") . "\n";
					$change_log .= "<li>" . htmlspecialcharsEx(GetMessage("SUP_STATUS_CHANGED_LOG", array("#VALUE#" => $nf->STATUS_NAME)));
					break;
				case "DIFFICULTY_ID":
					$v->change_hidden .= GetMessage("SUP_DIFFICULTY_CHANGED") . "\n";
					$change_log .= "<li>" . htmlspecialcharsEx(GetMessage("SUP_DIFFICULTY_CHANGED_LOG", array("#VALUE#" => $nf->DIFFICULTY_NAME)));
					break;
				case "MARK_ID":
					$v->change .= GetMessage("SUP_MARK_CHANGED") . "\n";
					$change_log .= "<li>" . htmlspecialcharsEx(GetMessage("SUP_MARK_CHANGED_LOG", array("#VALUE#" => $nf->MARK_NAME)));
					break;
				case "SLA_ID":
					$v->change .= GetMessage("SUP_SLA_CHANGED") . "\n";
					$change_log .= "<li>" . htmlspecialcharsEx(GetMessage("SUP_SLA_CHANGED_LOG", array("#VALUE#" => $nf->SLA_NAME)));
					break;
				case "TITLE":
					$v->change .= GetMessage("SUP_TITLE_CHANGED") . "\n";
					$change_log .= "<li>" . htmlspecialcharsEx(GetMessage("SUP_TITLE_CHANGED_LOG", array("#VALUE#" => $nf->TITLE)));
					break;
				case "MESSAGE":
					$v->change .= GetMessage("SUP_NEW_MESSAGE") . "\n";
					break;
				case "HIDDEN_MESSAGE":
					$v->change_hidden .= GetMessage("SUP_NEW_HIDDEN_MESSAGE")."\n";
					$line1 = str_repeat("=", 20);
					$line2 = str_repeat("=", 30);
					$mf->MESSAGE_HEADER = $line1 . " " . GetMessage("SUP_MAIL_HIDDEN_MESSAGE") . " " . $line2;
					break;
			}
		}

		if(!$v->isNew) $mf->WHAT_CHANGE = $v->change; // UPDATE
				
		// запишем изменения в лог
		if(strlen($change_log) > 0)
		{
			$arFields_log = $v->arFields_log;
			$arFields_log["MESSAGE"] = $change_log;
			$q = null;
			$arFields_log["IS_LOG"] = "Y";
			CTicket::AddMessage($nf->ID, $arFields_log, $q, "N", $v->newSLA);
		}
			
	}
	
public static 	function Set_sendMails($nf, $v, $arFields)
	{
		$I_Email = null;
		$U_Email = null;
		if(!$v->isNew) $U_Email = "Y"; // UPDATE
		else $I_Email = "Y";
		
		IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/support/classes/general/messages.php", $v->arrSite["LANGUAGE_ID"]);
					
		$mf = self::GetCSupportTableFields("EventFields");
		
		$mf->ADMIN_EDIT_URL = "/bitrix/admin/ticket_edit.php";
		$mf->LANGUAGE = $v->arrSite["LANGUAGE_ID"];
		$mf->LANGUAGE_ID = $v->arrSite["LANGUAGE_ID"];

		$arrSet = array(
			"ID"						=> "ID",
			"DATE_CREATE"				=> "DATE_CREATE",
			"TIMESTAMP"					=> "TIMESTAMP_X",
			"DATE_CLOSE"				=> "DATE_CLOSE",
			"TITLE"						=> "TITLE",
			"CATEGORY"					=> "CATEGORY_NAME",
			"CRITICALITY"				=> "CRITICALITY_NAME",
			"DIFFICULTY"				=> "DIFFICULTY_NAME",
			"STATUS"					=> "STATUS_NAME",
			"SLA"						=> "SLA_NAME",
			"OWNER_USER_ID"				=> "OWNER_USER_ID",
			"OWNER_GUEST_ID"			=> "OWNER_GUEST_ID",
			"OWNER_SID"					=> "OWNER_SID",
			"OWNER_USER_NAME"			=> "OWNER_NAME",
			"OWNER_USER_LOGIN"			=> "OWNER_LOGIN",
			"OWNER_USER_EMAIL"			=> "OWNER_EMAIL",
			"RESPONSIBLE_USER_ID"		=> "RESPONSIBLE_USER_ID",
			"RESPONSIBLE_USER_NAME"		=> "RESPONSIBLE_NAME",
			"RESPONSIBLE_USER_LOGIN"	=> "RESPONSIBLE_LOGIN",
			"RESPONSIBLE_USER_EMAIL"	=> "RESPONSIBLE_EMAIL",
			"CREATED_USER_ID"			=> "CREATED_USER_ID",
			"CREATED_GUEST_ID"			=> "CREATED_GUEST_ID",
			"CREATED_USER_LOGIN"		=> "CREATED_LOGIN",
			"CREATED_USER_EMAIL"		=> "CREATED_EMAIL",
			"CREATED_USER_NAME"			=> "CREATED_NAME"
		);
		
		if(!$v->isNew) // UPDATE
		{
			$arrSet["MODIFIED_USER_ID"]			= "MODIFIED_USER_ID";
			$arrSet["MODIFIED_GUEST_ID"]		= "MODIFIED_GUEST_ID";
			$arrSet["MODIFIED_USER_LOGIN"]		= "MODIFIED_LOGIN";
			$arrSet["MODIFIED_USER_EMAIL"]		= "MODIFIED_EMAIL";
			$arrSet["MODIFIED_USER_NAME"]		= "MODIFIED_NAME";
			$arrSet["RATE"]						= "MARK_NAME";
			$arrSet["MESSAGES_AMOUNT"]			= "MESSAGES";
		}
						
		
		$mf->FromArray((array)$nf, $arrSet);

		$arUserIDs = array($mf->OWNER_USER_ID, $mf->CREATED_USER_ID, $mf->MODIFIED_USER_ID, $mf->RESPONSIBLE_USER_ID);
		$arGuestIDs = array($mf->OWNER_GUEST_ID, $mf->CREATED_GUEST_ID, $mf->MODIFIED_GUEST_ID);
		$arStrUsers =CTicket::GetUsersPropertiesArray($arUserIDs, $arGuestIDs);

		// set name, login, email
		$userCategories = array('OWNER', 'RESPONSIBLE', 'CREATED');

		if (!$v->isNew)
		{
			$userCategories[] = 'MODIFIED';
		}

		foreach ($userCategories as $userCategory)
		{
			$propertyId = $userCategory.'_USER_ID';

			if ($mf->$propertyId > 0 && isset($arStrUsers['arUsers'][$mf->$propertyId]))
			{
				$name = CUser::FormatName(CSite::GetNameFormat(), $arStrUsers['arUsers'][$mf->$propertyId], true, true);
				$propertyName = $userCategory.'_USER_NAME';
				$mf->$propertyName = $name;

				$propertyLogin = $userCategory.'_USER_LOGIN';
				$mf->$propertyLogin = $arStrUsers['arUsers'][$mf->$propertyId]['LOGIN'];

				$propertyEmail = $userCategory.'_USER_EMAIL';
				$mf->$propertyEmail = $arStrUsers['arUsers'][$mf->$propertyId]['EMAIL'];
			}
		}

		$mf->FILES_LINKS = self::Set_getFilesLinks($v->arrFILES, $v->arrSite["LANGUAGE_ID"]);
		$mf->IMAGE_LINK = $mf->FILES_LINKS;
		
		$mf->MESSAGE_BODY = PrepareTxtForEmail($arFields["MESSAGE"], $v->arrSite["LANGUAGE_ID"], false, false);

		// сформируем email автора
		// Событие: "TICKET_CHANGE_BY_AUTHOR_FOR_AUTHOR"	- #DEFAULT_EMAIL_FROM# -> #OWNER_EMAIL# (Обращение изменено автором (для автора))
		// Событие: "TICKET_CHANGE_BY_SUPPORT_FOR_AUTHOR"	- #DEFAULT_EMAIL_FROM# -> #OWNER_EMAIL# (Обращение изменено сотрудником техподдержки (для автора))
		// Событие: "TICKET_NEW_FOR_AUTHOR"					- #DEFAULT_EMAIL_FROM# -> #OWNER_EMAIL# (Новое обращение (для автора))

		$ownerEmail = "";
		if($mf->OWNER_USER_ID > 0 && isset($arStrUsers["arUsers"][$mf->OWNER_USER_ID]))
		{
			$ownerEmail = $arStrUsers["arUsers"][$mf->OWNER_USER_ID]["EMAIL"];
		}
		elseif($mf->OWNER_GUEST_ID > 0 && isset($arStrUsers["arGuests"][$mf->OWNER_GUEST_ID]))
		{
			$ownerEmail = $arStrUsers["arGuests"][$mf->OWNER_GUEST_ID]["EMAIL"];
		}
		$arrOwnerEmails = self::EmailsFromStringToArray($mf->OWNER_SID,  array($ownerEmail));
		if(intval($mf->OWNER_USER_ID) > 0)
		{
			$rs = CTicket::GetResponsibleList($mf->OWNER_USER_ID, $I_Email, $U_Email);
			while($arr0 = $rs->Fetch())
			{
				if(strlen($arr0['EMAIL']) > 0)
				{
					$arrOwnerEmails[] = $arr0['EMAIL'];
				}
			}
		}

		$mf->OWNER_EMAIL = implode(", ", array_unique($arrOwnerEmails));
		
		// выбираем административные email'ы
		$arrAdminEMails = CTicket::GetAdminEmails();
		if(!is_array($arrAdminEMails))
		{
			$arrAdminEMails = array();
		}
		TrimArr($arrAdminEMails);
	
		// сформируем email техподдержки
		// Событие: "TICKET_OVERDUE_REMINDER"				- #DEFAULT_EMAIL_FROM# -> #SUPPORT_EMAIL# (Напоминание о необходимости ответа (для техподдержки))
		// Событие: "TICKET_CHANGE_FOR_TECHSUPPORT"			- #DEFAULT_EMAIL_FROM# -> #SUPPORT_EMAIL# (Изменения в обращении (для техподдержки))
		// Событие: "TICKET_NEW_FOR_TECHSUPPORT"			- #DEFAULT_EMAIL_FROM# -> #SUPPORT_EMAIL# (Новое обращение (для техподдержки))

		$arrSupportEmails = array();
		if($mf->RESPONSIBLE_USER_ID > 0 && isset($arStrUsers["arUsers"][$mf->RESPONSIBLE_USER_ID]))
		{
			$arrSupportEmails[] = $arStrUsers["arUsers"][$mf->RESPONSIBLE_USER_ID]["EMAIL"];

			// из группы ответсвенного, выбираем всех пользователей кто имеет доступ к рассылке
			$rs = CTicket::GetResponsibleList($mf->RESPONSIBLE_USER_ID, $I_Email, $U_Email, "Y");
			while($arr0 = $rs->Fetch()) if(strlen($arr0['EMAIL']) > 0)
			{
				$arrSupportEmails[] = $arr0['EMAIL'];
			}
		}
		if(count($arrSupportEmails) <= 0)
		{
			$arrSupportEmails = $arrAdminEMails;
		}
		if(count($arrSupportEmails) <= 0)
		{
			$se = COption::GetOptionString("main", "email_from", "");
			if(strlen($se) > 0) $arrSupportEmails[] = $se;
		}

		TrimArr($arrSupportEmails);
		$mf->SUPPORT_EMAIL = count($arrSupportEmails) > 0 ? TrimEx(implode(",", array_unique($arrSupportEmails)), ",") : "";


		// удалим продублированные адреса из макроса #SUPPORT_ADMIN_EMAIL#
		if(count($arrSupportEmails) > 0)
		{
			foreach($arrSupportEmails as $e)
			{
				unset($arrAdminEMails[$e]);
			}
		}
		$mf->SUPPORT_ADMIN_EMAIL = count($arrAdminEMails) > 0 ? TrimEx(implode(",", $arrAdminEMails), ",") : "";
	
		if(array_key_exists('PUBLIC_EDIT_URL', $arFields) && strlen($arFields['PUBLIC_EDIT_URL']) > 0)
		{
			$mf->PUBLIC_EDIT_URL = $arFields['PUBLIC_EDIT_URL'];
		}
		else
		{
			$peurl = COption::GetOptionString("support", "SUPPORT_DIR");
			$peurl = str_replace("#LANG_DIR#", $v->arrSite["DIR"], $peurl); // совместимость
			$peurl = str_replace("#SITE_DIR#", $v->arrSite["DIR"], $peurl);
			$peurl = str_replace("\\", "/", $peurl);
			$peurl = str_replace("//", "/", $peurl);
			$peurl = TrimEx($peurl, "/");
			$mf->PUBLIC_EDIT_URL = "/".$peurl."/".COption::GetOptionString("support", "SUPPORT_EDIT");
		}
		
		$mf->SUPPORT_COMMENTS = PrepareTxtForEmail($arFields["SUPPORT_COMMENTS"], $v->arrSite["LANGUAGE_ID"]);
		if(strlen($mf->SUPPORT_COMMENTS) > 0) $mf->SUPPORT_COMMENTS = "\n\n" . $mf->SUPPORT_COMMENTS . "\n";
		
		$mf->SOURCE = strlen($nf->SOURCE_NAME) <= 0 ? "" : "[" . $nf->SOURCE_NAME . "] ";

		$oUID = intval($mf->OWNER_USER_ID);
		$oGID = intval($mf->OWNER_GUEST_ID);
		if($oGID > 0)
		{
			$mf->OWNER_TEXT = $arStrUsers["arGuests"][$oGID]["HTML_NAME_S"];
			if($oUID > 0)
			{
				$mf->OWNER_TEXT .= self::addSupportText($oUID);
			}
		}
		elseif($oUID > 0)
		{
			$mf->OWNER_TEXT = $arStrUsers["arUsers"][$oUID]["HTML_NAME_S"];
			$mf->OWNER_TEXT .= self::addSupportText($oUID);
		}
		if(strlen(trim($mf->OWNER_SID)) > 0 && $mf->OWNER_SID != null)
		{
			$mf->OWNER_TEXT = " / " . $mf->OWNER_TEXT;
		}


		if($nf->CREATED_MODULE_NAME == "support" || strlen($nf->CREATED_MODULE_NAME) <= 0)
		{
			$cUID = intval($mf->CREATED_USER_ID);
			$cGID = intval($mf->CREATED_GUEST_ID);
			if($cGID > 0)
			{
				$mf->CREATED_TEXT = $arStrUsers["arGuests"][$cGID]["HTML_NAME_S"];
			}
			elseif($cUID > 0)
			{
				$mf->CREATED_TEXT = $arStrUsers["arUsers"][$cUID]["HTML_NAME_S"];
			}
		}
		else
		{
			$mf->CREATED_MODULE_NAME = "[" . $nf->CREATED_MODULE_NAME . "]";
		}

		$rUID = intval($mf->RESPONSIBLE_USER_ID);
		if($rUID > 0)
		{
			$mf->RESPONSIBLE_TEXT = $arStrUsers["arUsers"][$rUID]["HTML_NAME_S"];
			$mf->RESPONSIBLE_TEXT .= self::addSupportText($rUID);
		}


		/*
		if($mf->OWNER_USER_ID > 0 || strlen(trim($mf->OWNER_USER_LOGIN)) > 0)
		{
			$mf->OWNER_TEXT = "[" . $mf->OWNER_USER_ID . "] (" . $mf->OWNER_USER_LOGIN . ") " . $mf->OWNER_USER_NAME;
			if(strlen(trim($mf->OWNER_SID)) > 0 && $mf->OWNER_SID != null) $mf->OWNER_TEXT = " / " . $mf->OWNER_TEXT;
			$mf->OWNER_TEXT .= self::addSupportText($mf->OWNER_USER_ID);
		}

		if($nf->CREATED_MODULE_NAME == "support")
		{
			$mf->CREATED_MODULE_NAME = "";
			if($mf->CREATED_USER_ID > 0)
			{
				$mf->CREATED_TEXT = "[" . $mf->CREATED_USER_ID . "] (" . $mf->CREATED_USER_LOGIN . ") " . $mf->CREATED_USER_NAME . self::addSupportText($mf->CREATED_USER_ID);
			}
		}
		else
		{
			$mf->CREATED_MODULE_NAME = "[" . $nf->CREATED_MODULE_NAME . "]";
		}

		if($mf->RESPONSIBLE_USER_ID > 0)
		{
			$mf->RESPONSIBLE_TEXT = "[" . $mf->RESPONSIBLE_USER_ID . "] (" . $nf->RESPONSIBLE_LOGIN . ") " . $nf->RESPONSIBLE_NAME;
			$mf->RESPONSIBLE_TEXT .= self::addSupportText($mf->RESPONSIBLE_USER_ID);
		}
		*/
		
		if(!$v->isNew) // UPDATE
		{
			/*
			if($nf->MODIFIED_MODULE_NAME == "support" && strlen($nf->MODIFIED_MODULE_NAME) > 0)
			{
				$mf->MODIFIED_MODULE_NAME = "";
				if($mf->MODIFIED_USER_ID > 0)
				{
					$mf->MODIFIED_TEXT = "[" . $mf->MODIFIED_USER_ID . "] (" . $mf->MODIFIED_USER_LOGIN . ") " . $mf->MODIFIED_USER_NAME;
					$mf->MODIFIED_TEXT .= self::addSupportText($mf->MODIFIED_USER_ID);
				}
			}
			else $mf->MODIFIED_MODULE_NAME = "[" . $nf->MODIFIED_MODULE_NAME . "]";
			*/

			if($nf->MODIFIED_MODULE_NAME == "support" || strlen($nf->MODIFIED_MODULE_NAME) <= 0)
			{
				$rUID = intval($mf->MODIFIED_USER_ID);
				$rGID = intval($mf->MODIFIED_GUEST_ID);
				if($rGID > 0)
				{
					$mf->MODIFIED_TEXT = $arStrUsers["arGuests"][$rGID]["HTML_NAME_S"];
					if($rUID > 0)
					{
						$mf->MODIFIED_TEXT .= self::addSupportText($rUID);
					}
				}
				elseif($rUID > 0)
				{
					$mf->MODIFIED_TEXT = $arStrUsers["arUsers"][$rUID]["HTML_NAME_S"];
					$mf->MODIFIED_TEXT .= self::addSupportText($rUID);
				}
			}
			else
			{
				$mf->MODIFIED_MODULE_NAME = "[" . $nf->MODIFIED_MODULE_NAME . "]";
			}
			
			$mf->MESSAGE_SOURCE = "";
			if($rsSource = CTicketDictionary::GetByID($arFields["MESSAGE_SOURCE_ID"]))
			{
				$arSource = $rsSource->Fetch();
				$mf->MESSAGE_SOURCE = (array_key_exists("NAME", $arSource) && strlen($arSource["NAME"]) > 0) ? "[" . $arSource["NAME"] . "] " : "";
			}

			if((strlen(trim($arFields["MESSAGE_AUTHOR_SID"])) > 0 || intval($arFields["MESSAGE_AUTHOR_USER_ID"]) > 0) && $v->bSupportTeam)
			{
				$mf->MESSAGE_AUTHOR_USER_ID = $arFields["MESSAGE_AUTHOR_USER_ID"];
				$mf->MESSAGE_AUTHOR_SID = $arFields["MESSAGE_AUTHOR_SID"];
			}
			else $mf->MESSAGE_AUTHOR_USER_ID = $v->uid;
			
			$arMA = array();
			if($rsMA = CUser::GetByID($mf->MESSAGE_AUTHOR_USER_ID)) $arMA = $rsMA->Fetch();
			
			if($mf->MESSAGE_AUTHOR_USER_ID > 0 || strlen(trim($arMA["LOGIN"])) > 0)
			{
				$mf->MESSAGE_AUTHOR_TEXT = "[" . $mf->MESSAGE_AUTHOR_USER_ID . "] (" . $arMA["LOGIN"] . ") " . $arMA["NAME"] . " " . $arMA["LAST_NAME"];
				if(strlen(trim($arFields["MESSAGE_AUTHOR_SID"])) > 0) $mf->MESSAGE_AUTHOR_TEXT = " / " . $mf->MESSAGE_AUTHOR_TEXT;
				if($mf->MESSAGE_AUTHOR_USER_ID > 0) $mf->MESSAGE_AUTHOR_TEXT .= self::addSupportText($mf->MESSAGE_AUTHOR_USER_ID);
			}
			
			if(strlen(trim($arMA["NAME"])) > 0 || strlen(trim($arMA["LAST_NAME"])) > 0) $mf->MESSAGE_AUTHOR_USER_NAME	= trim($arMA["NAME"]) . " ". trim($arMA["LAST_NAME"]);
			if(strlen(trim($arMA["LOGIN"])) > 0) $mf->MESSAGE_AUTHOR_USER_LOGIN	= $arMA["LOGIN"];
			if(strlen(trim($arMA["EMAIL"])) > 0) $mf->MESSAGE_AUTHOR_USER_EMAIL	= $arMA["EMAIL"];
			
			$mf->MESSAGE_HEADER = str_repeat("=", 23) . " " . GetMessage("SUP_MAIL_MESSAGE") . " " . str_repeat("=", 34);
			
		
		}
		
		$mf->SPAM_MARK = "";
		if(strlen($nf->IS_SPAM) > 0)
		{
			if($nf->IS_SPAM == "Y") $mf->SPAM_MARK = "\n" . GetMessage("SUP_EXACTLY_SPAM") . "\n";
			else $mf->SPAM_MARK = "\n" . GetMessage("SUP_POSSIBLE_SPAM") . "\n";
		}
		
		self::Set_WriteLog($nf, $v, $mf);
		//$v  +change, +change_hidden
				
		if(!$v->isNew) // UPDATE
		{
			$mf->MESSAGE_FOOTER = str_repeat("=", strlen($mf->MESSAGE_HEADER));
		}
		
		if ($v->isNew && $v->bActiveCoupon) $mf->COUPON = $v->V_COUPON;
		
		$arEventFields_author = $mf->ToArray(CSupportTableFields::ALL); //, array(CSupportTableFields::NOT_NULL)
		$arEventFields_support = $arEventFields_author;

		// отсылаем письмо автору
		if($v->SEND_EMAIL_TO_AUTHOR == "Y" && ($v->isNew || strlen($v->change) > 0))
		{
			$EventType = "TICKET_NEW_FOR_AUTHOR";
			if(!$v->isNew) // UPDATE
			{
				// HIDDEN
				if($arFields["HIDDEN"] == "Y")
				{
					$arrUnsetHidden = array("MESSAGE_BODY", "IMAGE_LINK");
					foreach($arrUnsetHidden as $value) $arEventFields_author[$value] = "";
				}
				$EventType = "TICKET_CHANGE_BY_AUTHOR_FOR_AUTHOR";
				if(CTicket::IsSupportTeam($mf->MESSAGE_AUTHOR_USER_ID) || CTicket::IsAdmin($mf->MESSAGE_AUTHOR_USER_ID)) 
					$EventType = "TICKET_CHANGE_BY_SUPPORT_FOR_AUTHOR";
			}
			$arEventFields_author = CTicket::ExecuteEvents('OnBeforeSendMailToAuthor' , $arEventFields_author, $v->isNew, $EventType);
			if ($arEventFields_author) CEvent::Send($EventType, $v->arrSite["ID"], $arEventFields_author);
		}

		// отсылаем письмо техподдержке
		if($v->SEND_EMAIL_TO_TECHSUPPORT == "Y" && ($v->isNew || strlen($v->change) > 0 || strlen($v->change_hidden) > 0))
		{
			$EventType = "TICKET_NEW_FOR_TECHSUPPORT";
			if(!$v->isNew) // UPDATE
			{
				$arEventFields_support["WHAT_CHANGE"] .= $v->change_hidden;
				$EventType = "TICKET_CHANGE_FOR_TECHSUPPORT";
			}
			$arEventFields_support = CTicket::ExecuteEvents('OnBeforeSendMailToSupport', $arEventFields_support, $v->isNew);
			if ($arEventFields_support) CEvent::Send($EventType, $v->arrSite["ID"], $arEventFields_support);
		}
		
		
	}
	
	fupublic static nction Set_getResponsibleUser($v, $f, &$arFields)
	{
		global $DB;
		$err_mess = (CAllTicket::err_mess()) . "<br>Function: Set_getResponsibleUser<br>Line: ";
		
		// если обращение создается сотрудником техподдержки, администратором или демо пользователем
		$f->RESPONSIBLE_USER_ID = null;
		if($v->bSupportTeam || $v->bAdmin || $v->Demo) $f->FromArray($arFields, "RESPONSIBLE_USER_ID", array(CSupportTableFields::MORE0));
		if($f->RESPONSIBLE_USER_ID == null) unset($arFields["RESPONSIBLE_USER_ID"]);
			
		/*
		получим идентификаторы события и ответственного в зависимости от
			1) Категории
			2) Критичности
			3) Источника
		*/
		$strSql = "
			SELECT ID, C_TYPE, RESPONSIBLE_USER_ID, EVENT1, EVENT2, EVENT3
			FROM b_ticket_dictionary
			WHERE
				(ID=" . $f->CATEGORY_ID		. " AND C_TYPE='C') OR
				(ID=" . $f->CRITICALITY_ID	. " AND C_TYPE='K') OR
				(ID=" . $f->SOURCE_ID		. " AND C_TYPE='SR')
			ORDER BY
				C_TYPE
		";
		$z = $DB->Query($strSql, false, $err_mess . __LINE__);
		$v->category_set = false;
		while($zr = $z->Fetch())
		{
			// если
			//    1) ответственный определен в справочнике
			//    2) до сих пор он не был определен
			//    3) не был задан явно пользователем имеющим на это права
			if ($zr["C_TYPE"]=="C")
			{
				$v->T_EVENT1 = trim($zr["EVENT1"]);
				$v->T_EVENT2 = trim($zr["EVENT2"]);
				$v->T_EVENT3 = trim($zr["EVENT3"]);
				$v->category_set = true;
			}
			if($f->RESPONSIBLE_USER_ID == null && intval($zr["RESPONSIBLE_USER_ID"]) > 0)
			{
				$RU_ID = intval($zr["RESPONSIBLE_USER_ID"]);
				if(CTicket::IsSupportTeam($RU_ID) || CTicket::IsAdmin($RU_ID)) $f->RESPONSIBLE_USER_ID = $RU_ID;
				break;
			}
		}
		
		
		// если ответственный явно не определен то
		if($f->RESPONSIBLE_USER_ID == null)
		{
			// ответственный из настроек SLA
			$rsSLA = CTicketSLA::GetByID($f->SLA_ID);

			if($rsSLA !== false && $arSLA = $rsSLA->Fetch())
			{
				if(intval($arSLA["RESPONSIBLE_USER_ID"]) > 0)
				{
					$f->RESPONSIBLE_USER_ID = $arSLA["RESPONSIBLE_USER_ID"];
				}
			}
		}
		
		// ответственный из настроек модуля
		if ($f->RESPONSIBLE_USER_ID == null)
		{
			// берем из настроек модуля ответственного по умолчанию
			$RU_ID = intval(COption::GetOptionString("support", "DEFAULT_RESPONSIBLE_ID"));
			$f->RESPONSIBLE_USER_ID = $RU_ID;
		}
	}
	
public static 	function Set_getCOUPONandSLA($v, $f, $arFields)
	{
		global $APPLICATION;
		$slaID = 0;
		if(isset($arFields['SLA_ID']) && (intval($arFields['SLA_ID']) > 0))
		{
			$slaID = $arFields['SLA_ID'];
		}
		// получение купона
		if(array_key_exists('COUPON', $arFields) && strlen($arFields['COUPON']) > 0)
		{
			$v->bActiveCoupon = CSupportSuperCoupon::UseCoupon($arFields['COUPON']);
			if($v->bActiveCoupon)
			{
				$v->V_COUPON = $arFields['COUPON'];
				$rsCoupon = CSupportSuperCoupon::GetList(false, array('COUPON' => $arFields['COUPON']));
				//if($arCoupon = $rsCoupon->Fetch() && intval($arCoupon['SLA_ID']) > 0) $arFields['SLA_ID'] = intval($arCoupon['SLA_ID']);
				if($arCoupon = $rsCoupon->Fetch())
				{
					if(intval($arCoupon['SLA_ID']) > 0)
					{
						$slaID= intval($arCoupon['SLA_ID']);
					}
				}
			}
			else
			{
					$APPLICATION->ThrowException(GetMessage('SUP_ERROR_INVALID_COUPON'));
					return false;
			}
		}
		// получаем SLA
		if($slaID > 0)
		{
			//$f->FromArray($arFields, "SLA_ID", array(CSupportTableFields::MORE0));
			$f->SLA_ID = $slaID;
		}
		else
		{
			$f->SLA_ID = CTicketSLA::GetSLA($f->SITE_ID, $f->OWNER_USER_ID, $f->CATEGORY_ID, ($v->bActiveCoupon ? $v->V_COUPON : "") );
		}
		//elseif(intval($arFields["SLA_ID"]) <= 0) $f->SLA_ID = CTicketSLA::GetForUser($f->SITE_ID, $f->OWNER_USER_ID);
		
		return true;			
	}
	
public static 	function Set_InitVar(&$arFields, $id, $checkRights, $sendEmailToAuthor, $sendEmailToTechsupport)
	{
		global $APPLICATION, $USER, $DB;
				
		$f = self::GetCSupportTableFields("b_ticket");
		$v = (object)array();

		if(!is_object($USER))
		{
			$USER = new CUser;
		}
		$uid = $USER->GetID();
		if(isset($arFields["CURRENT_USER_ID"]) && intval($arFields["CURRENT_USER_ID"]) > 0)
		{
			$uid = intval($arFields["CURRENT_USER_ID"]);
		}
		
		$f->ID = intval($id);	
		$v->isNew = ($f->ID <= 0);
		
		$v->CHECK_RIGHTS = ($checkRights == "Y") ? "Y" : "N";
		$v->SEND_EMAIL_TO_AUTHOR = ($sendEmailToAuthor == "Y") ? "Y" : "N";
		$v->SEND_EMAIL_TO_TECHSUPPORT = ($sendEmailToTechsupport == "Y") ? "Y" : "N";
		
		$v->newSLA = false;
		
		// заголовок и сообщение - обязательные поля для нового обращения
		if($v->isNew)
		{
			if(strlen($arFields["TITLE"]) <= 0)
			{
				$APPLICATION->ThrowException(GetMessage('SUP_ERROR_EMPTY_TITLE'));
				return false;
			}

			if(strlen($arFields["MESSAGE"]) <= 0)
			{
				$APPLICATION->ThrowException(GetMessage('SUP_ERROR_EMPTY_MESSAGE'));
				return false;
			}
		}
		
		if(is_object($APPLICATION))
		{
			$APPLICATION->ResetException();
		}
		if(!$GLOBALS["USER_FIELD_MANAGER"]->CheckFields("SUPPORT", $f->ID, $arFields))
		{
			if(is_object($APPLICATION) && $APPLICATION->GetException())
			{
				return false;
			}
			else 
			{
				$APPLICATION->ThrowException("Unknown error. ");
				return false;
			}
		}
		
		// установка прав
		$v->bAdmin = $v->bSupportTeam = $v->bSupportClient = $v->bDemo = $v->bOwner = false;
		if($v->CHECK_RIGHTS == "Y")
		{
			$v->uid = $uid;
			$v->bAdmin = CTicket::IsAdmin($uid);
			$v->bSupportTeam = CTicket::IsSupportTeam($uid);
			$v->bSupportClient = CTicket::IsSupportClient($uid);
			$v->bDemo = CTicket::IsDemo($uid);
			if($v->isNew) $v->bOwner = true;
			else $v->bOwner = CTicket::IsOwner($f->ID, $v->uid);
		}
		else
		{
			$v->bAdmin = $v->bSupportTeam = $v->bSupportClient = $v->bDemo = $v->bOwner = true;
			$v->uid = 0;
		}
		if(!$v->bAdmin && !$v->bSupportTeam && !$v->bSupportClient) return false;
		if (!$v->bAdmin && !$v->bSupportTeam && ($v->bDemo && !$v->bOwner)) return false;
		
		// Это спам?
		$f->FromArray($arFields, "IS_SPAM");
		
		$v->bActiveCoupon = false;
				
		$f->FromArray($_SESSION, array("MODIFIED_GUEST_ID" => "SESS_GUEST_ID"), array(CSupportTableFields::MORE0));
		$f->FromArray($arFields, "OWNER_USER_ID,OWNER_SID,HOLD_ON", array(CSupportTableFields::MORE0, CSupportTableFields::NOT_EMTY_STR));
		
		// получим SITE_ID
		if(strlen($arFields["SITE_ID"]) > 0) $f->SITE_ID = $arFields["SITE_ID"];
		elseif(strlen($arFields["SITE"]) > 0) $f->SITE_ID = $arFields["SITE"];
		elseif(strlen($arFields["LANG"]) > 0) $f->SITE_ID = $arFields["LANG"];  // совместимость со старой версией
		else $f->SITE_ID = SITE_ID;
		
		// получаем ID записей справочника по SID
		$arr = array(
			"CATEGORY"			=> "C",
			"CRITICALITY"		=> "K",
			"STATUS"			=> "S",
			"MARK"				=> "M",
			"SOURCE"			=> "SR",
			"MESSAGE_SOURCE"	=> "SR",
			"DIFFICULTY" => "D"
		);
		foreach($arr as $key => $value)
		{
			if ((array_key_exists($key . "_ID", $arFields) || intval($arFields[ $key . "_ID" ]) <= 0) && array_key_exists($key . "_SID", $arFields) && strlen($arFields[ $key . "_SID" ]) > 0)
			{
				$z = CTicketDictionary::GetBySID($arFields[ $key . "_SID" ], $value,  $f->SITE_ID);
				$zr = $z->Fetch();
				$arFields[$key."_ID"] = $zr["ID"];
			}
		}		
		return array("v" => $v, "f" => $f);
	}
	

	/**
	* <p>Метод создает новое обращение, либо модифицирует существующее в случае указания во втором параметре ID сообщения. Возвращает ID созданного обращения, либо ID модифицированного обращения.</p>
	*
	*
	* @param array $arFields  Массив параметров обращения. В массиве допустимы следующие
	* индексы: <ul> <li>*TITLE - заголовок обращения (обязательное поле при
	* создании нового обращения) </li> <li>MESSAGE - тело сообщения
	* (обязательное поле при создании нового обращения) </li> <li>IMAGE -
	* массив описывающий загружаемое изображение; в массиве
	* допустимые следующие индексы: <ul> <li>name - исходное имя загружаемого
	* файла</li> <li>type - тип загружаемого файла (например: "image/gif")</li> <li>tmp_name
	* - имя временного файла на сервере</li> <li>error - код ошибки ("0" - нет
	* ошибок)</li> <li>size - размер загружаемого файла</li> <li>MODULE_ID -
	* идентификатор модуля ("support")</li> </ul> </li> <li>*OWNER_SID - символьный код
	* автора обращения; можно указать любое значение идентифицирующее
	* автора обращения - email, телефон, адрес и т.п. </li> <li>*OWNER_USER_ID - ID автора
	* обращения (по умолчанию - ID текущего пользователя) </li> <li>*SOURCE_SID -
	* символьный код источника обращения (по умолчанию - "web") </li>
	* <li>*CREATED_MODULE_NAME - идентификатор модуля из которого создаётся
	* обращение (по умолчанию - "support") </li> <li>**MESSAGE_AUTHOR_SID - символьный код
	* автора сообщения (можно указать любое значение идентифицирующее
	* автора сообщения - email, телефон, адрес и т.п.) </li> <li>**MESSAGE_AUTHOR_USER_ID - ID
	* пользователя - автора сообщения (по умолчанию - ID текущего
	* пользователя) </li> <li>**MESSAGE_SOURCE_SID - символьный код источника
	* сообщения (по умолчанию - "web") </li> <li>**MODIFIED_MODULE_NAME - идентификатор
	* модуля из которого обращение модифицируется (по умолчанию - "support")
	* </li> <li>**HIDDEN - "Y" - сообщение будет добавлено как скрытое и будет
	* видимо только сотрудникам техподдержки; "N" - сообщение будет
	* добавлено видимым как для автора обращения так и для сотрудников
	* техподдержки (по умолчанию) </li> <li>CATEGORY_SID - символьный код
	* категории </li> <li>CATEGORY_ID - ID категории.</li> <li>CRITICALITY_SID - символьный код
	* критичности </li> <li>STATUS_SID - символьный код статуса </li> <li>MARK_ID - ID
	* оценки ответов </li> <li>RESPONSIBLE_USER_ID - ID пользователя ответственного
	* за обращение </li> <li>SUPPORT_COMMENTS - комментарий видимый только
	* пользователям входящим в группу техподдержки </li> <li>CLOSE - "Y" -
	* обращение закрыть; "N" - обращение открыть </li> <li>AUTO_CLOSE_DAYS -
	* количество дней по истечении которых автоматически закрыть
	* обращение если за это время от автора не поступило сообщения </li>
	* </ul> * - данное поле может быть использовано для создания новых
	* обращений <br> ** - данное поле используется только при модификации
	* существующих обращений.
	*
	* @param int &$MESSAGE_ID  ID добавленного сообщения. Необязательный параметр.
	*
	* @param int $TICKET_ID = "" ID модифицируемого обращения.
	*
	* @param char(1) $CHECK_RIGHTS = "Y" Флаг необходимости проверки прав текущего пользователя: "Y" -
	* необходимо проверить права текущего пользователя под которым
	* создаётся обращение либо модифицируется; "N" - обращения создавать
	* и модифицировать независимо от прав текущего пользователя.
	* Необязательный параметр. Значение по умолчанию - "Y".
	*
	* @return int 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/support/classes/cticket/set.php
	* @author Bitrix
	*/
	public static 	function Set($arFields, &$MID, $id="", $checkRights="Y", $sendEmailToAuthor="Y", $sendEmailToTechsupport="Y")
	{						
		global $DB, $APPLICATION, $USER;
		
		$err_mess = (CAllTicket::err_mess()) . "<br>Function: Set<br>Line: ";
		
		$v0 = self::Set_InitVar($arFields, $id, $checkRights, $sendEmailToAuthor, $sendEmailToTechsupport);
		if(!is_array($v0)) return $v0;
		$v = $v0["v"]; /* isNew, CHECK_RIGHTS, SEND_EMAIL_TO_AUTHOR, SEND_EMAIL_TO_TECHSUPPORT, bAdmin, bSupportTeam, bSupportClient, bDemo, bOwner, uid, bActiveCoupon, IsSpam */
		/** @var CSupportTableFields $f */
		$f = $v0["f"]; /* ID, SITE_ID, MODIFIED_GUEST_ID, OWNER_USER_ID, OWNER_SID, HOLD_ON, IS_SPAM */

		// если модифицируем обращение то
		if(!$v->isNew)
		{
			unset($arFields['COUPON']);
			$arFields['ID'] = $f->ID;
			$arFields = CTicket::ExecuteEvents('OnBeforeTicketUpdate', $arFields, false);
			$v->closeDate = (isset($arFields["CLOSE"]) && $arFields["CLOSE"] == "Y"); //$close
			
			// запоминаем предыдущие важные значения
			$v->arrOldFields = array();
			$arr = array(
				"TITLE" => "T.TITLE",
				"RESPONSIBLE_USER_ID" => "T.RESPONSIBLE_USER_ID",
				"SLA_ID" => "T.SLA_ID",
				"CATEGORY_ID" => "T.CATEGORY_ID",
				"CRITICALITY_ID" => "T.CRITICALITY_ID",
				"STATUS_ID" => "T.STATUS_ID",
				"MARK_ID" => "T.MARK_ID",
				"DIFFICULTY_ID" => "T.DIFFICULTY_ID",
				"DATE_CLOSE" => "T.DATE_CLOSE",
				"HOLD_ON" => "T.HOLD_ON",
				"RESPONSE_TIME" => "S.RESPONSE_TIME",
				"RESPONSE_TIME_UNIT" => "S.RESPONSE_TIME_UNIT"
				);
			$str = "T.ID";
			foreach ($arr as $s) $str .= "," . $s;
			$strSql = "SELECT " . $str . ", SITE_ID FROM b_ticket T LEFT JOIN b_ticket_sla S ON T.SLA_ID = S.ID WHERE T.ID='" . $f->ID . "'";
			$z = $DB->Query($strSql, false, $err_mess . __LINE__);
			if($zr=$z->Fetch())
			{
				$f->SITE_ID = $zr["SITE_ID"];
				if(intval($v->uid) == $zr["RESPONSIBLE_USER_ID"]) $v->bSupportTeam = "Y";
				foreach ($arr as $key=>$s) $v->arrOldFields[$key] = $zr[$key];
			}
						
			$f->FromArray(
				$arFields,
				"SITE_ID,MODIFIED_MODULE_NAME,SLA_ID,SOURCE_ID",
				array(CSupportTableFields::MORE0,CSupportTableFields::NOT_EMTY_STR)
			);

			if (!$f->MODIFIED_MODULE_NAME)
			{
				$f->MODIFIED_MODULE_NAME = '';
			}

			$f->FromArray(
				$arFields,
				"CATEGORY_ID,RESPONSIBLE_USER_ID,STATUS_ID,DIFFICULTY_ID,CRITICALITY_ID,SUPPORT_COMMENTS"
			);
			if (isset($arFields['CHANGE_TITLE']))
			{
				$f->set('TITLE', $arFields['CHANGE_TITLE']);
			}
			$f->set("MODIFIED_USER_ID", $v->uid, array(CSupportTableFields::MORE0));
			$f->setCurrentTime("TIMESTAMP_X");
			if($v->closeDate)
			{
				$f->setCurrentTime("DATE_CLOSE");
			}
						
			// ?remake? {
			$v->IS_GROUP_USER = 'N';
			if($v->bAdmin) $IS_GROUP_USER = 'Y';
			elseif($v->CHECK_RIGHTS == 'Y' && ($v->bSupportClient || $v->bSupportTeam))
			{
				if($v->bSupportTeam) $join_query = '(T.RESPONSIBLE_USER_ID IS NOT NULL AND T.RESPONSIBLE_USER_ID=O.USER_ID)';
				else $join_query = '(T.OWNER_USER_ID IS NOT NULL AND T.OWNER_USER_ID=O.USER_ID)';
				
				$strSql = "SELECT 'x'
				FROM b_ticket T
				INNER JOIN b_ticket_user_ugroup O ON $join_query
				INNER JOIN b_ticket_user_ugroup C ON (O.GROUP_ID=C.GROUP_ID)
				INNER JOIN b_ticket_ugroups G ON (O.GROUP_ID=G.ID)
				WHERE T.ID='" . $f->ID . "' AND C.USER_ID='" . $v->uid . "' AND C.CAN_VIEW_GROUP_MESSAGES='Y' AND G.IS_TEAM_GROUP='" . ($v->bSupportTeam ? "Y" : "N") . "'";
				$z = $DB->Query($strSql);
				if($zr = $z->Fetch()) $v->IS_GROUP_USER = 'Y';
			}
			// }
			
			if(isset($arFields["AUTO_CLOSE_DAYS"]) && intval($arFields["AUTO_CLOSE_DAYS"]) >= 0)
			{
				if (intval($arFields["AUTO_CLOSE_DAYS"]) == 0)
				{
					// get from module settings
					$f->AUTO_CLOSE_DAYS = COption::GetOptionString('support', "DEFAULT_AUTO_CLOSE_DAYS");
				}
				else
				{
					$f->AUTO_CLOSE_DAYS = $arFields["AUTO_CLOSE_DAYS"];
				}
			}

			if(is_array($v->arrOldFields) && is_array($arFields) && $arFields["CLOSE"] == "N" && strlen($v->arrOldFields["DATE_CLOSE"] ) > 0)
			{
				$f->DATE_CLOSE = null;
				$f->REOPEN = "Y";
			}
				
			// Если есть что и мы Аднины или из группы ТП, запишем в базу
			$v->FirstUpdateRes = false;
			
			if($v->bSupportTeam || $v->bAdmin)
			{
				$arFields_i = $f->ToArray(CSupportTableFields::ALL, array(CSupportTableFields::ONLY_CHANGED), true);
				if($v->CHECK_RIGHTS == "N" && isset($arFields["MARK_ID"]) && intval($arFields["MARK_ID"]) > 0)
				{
					$arFields_i["MARK_ID"] = intval($arFields["MARK_ID"]);
				}
				if(count($arFields_i) > 0)
				{
					$v->SupportTeamUpdateRes = $DB->Update("b_ticket", $arFields_i, "WHERE ID='" . $f->ID . "'", $err_mess . __LINE__); //$rows1
					$GLOBALS["USER_FIELD_MANAGER"]->Update("SUPPORT", $f->ID, $arFields);
					
					// если указана отметка о спаме то установим отметку о спаме
					if (strlen($f->IS_SPAM) > 0) CTicket::MarkAsSpam($f->ID, $f->IS_SPAM, $v->CHECK_RIGHTS);
					
					$v->newSLA = (isset($arFields_i["SLA_ID"]) && $v->arrOldFields["SLA_ID"] != $arFields_i["SLA_ID"]);
				}
			}
			elseif($v->bOwner || $v->bSupportClient)
			{
				$arFields_i = $f->ToArray("TIMESTAMP_X,DATE_CLOSE,CRITICALITY_ID,MODIFIED_USER_ID,MODIFIED_GUEST_ID,MODIFIED_MODULE_NAME,REOPEN", array(CSupportTableFields::ONLY_CHANGED), true);
				$arFields_i["MARK_ID"] = intval($arFields["MARK_ID"]);
				if(count($arFields_i) > 0)
				{
					$v->SupportClientUpdateRes = $DB->Update("b_ticket",
												$arFields_i,
												"WHERE ID='" . $f->ID . "' AND (OWNER_USER_ID='" . $v->uid . "' OR CREATED_USER_ID='" . $v->uid . "' OR '" . $v->CHECK_RIGHTS . "'='N' OR '" . $v->IS_GROUP_USER . "'='Y')",
												$err_mess . __LINE__
					);
					$GLOBALS["USER_FIELD_MANAGER"]->Update("SUPPORT", $f->ID, $arFields);
				}
			}
			
			// поля для записи лога
			/*$arFields_log = array(
				"LOG"							=> "Y",
				"MESSAGE_CREATED_USER_ID"		=> $MODIFIED_USER_ID,
				"MESSAGE_CREATED_MODULE_NAME"	=> $MODIFIED_MODULE_NAME,
				"MESSAGE_CREATED_GUEST_ID"		=> $MODIFIED_GUEST_ID,
				"MESSAGE_SOURCE_ID"				=> intval($arFields["SOURCE_ID"])
			);*/
			
			$v->arFields_log = array(
				"LOG"							=> "Y",
				"MESSAGE_CREATED_USER_ID"		=> $f->MODIFIED_USER_ID,
				"MESSAGE_CREATED_MODULE_NAME"	=> $f->MODIFIED_MODULE_NAME,
				"MESSAGE_CREATED_GUEST_ID"		=> $f->MODIFIED_GUEST_ID,
				"MESSAGE_SOURCE_ID"				=> $f->SOURCE_ID
			);
			
			// если необходимо соблюдать права то
			if($v->CHECK_RIGHTS == "Y")
			{
				// если update техподдержки не прошел то
				if(intval($v->SupportTeamUpdateRes) <= 0)
				{
					// убираем из массива исходных значений то что может менять только техподдержка
					unset($v->arrOldFields["RESPONSIBLE_USER_ID"]);
					unset($v->arrOldFields["SLA_ID"]);
					unset($v->arrOldFields["CATEGORY_ID"]);
					unset($v->arrOldFields["DIFFICULTY_ID"]);
					unset($v->arrOldFields["STATUS_ID"]);
				}
				// если update автора не прошел то
				if (intval($v->SupportClientUpdateRes) <=0)
				{
					// убираем из массива исходных значений то что может менять только автор
					unset($v->arrOldFields["MARK_ID"]);
				}
			}
			
			// если состоялся один из updat'ов то
			if(intval($v->SupportTeamUpdateRes) > 0 || intval($v->SupportClientUpdateRes) > 0)
			{
				
				// добавляем сообщение
				$arFields["MESSAGE_CREATED_MODULE_NAME"] = $arFields["MODIFIED_MODULE_NAME"];
				if(is_set($arFields, "IMAGE")) $arFields["FILES"][] = $arFields["IMAGE"];
				$arFiles = null;
				$MID = CTicket::AddMessage($f->ID, $arFields, $arFiles, $v->CHECK_RIGHTS);
				$v->arrFILES = $arFiles;
				$MID = intval($MID);
				
				$dateType = array();
				$dateType["EVENT"] = array(CTicket::UPDATE);
				if($v->newSLA) 
				{
					$dateType["EVENT"][] = CTicket::NEW_SLA;
					$dateType["OLD_SLA_RESPONSE_TIME"] = $v->arrOldFields["RESPONSE_TIME"];
					$dateType["OLD_SLA_RESPONSE_TIME_UNIT"] = $v->arrOldFields["RESPONSE_TIME_UNIT"];
				}
				if($f->REOPEN == "Y") 
				{
					$dateType["EVENT"][] = CTicket::REOPEN;
				}
				//CTicket::UpdateLastParams2($f->ID, $dateType);
				CTicket::UpdateLastParamsN($f->ID, $dateType, true, true);

				/*// если обращение закрывали то
				if($v->closeDate)
				{
					// удалим агентов-напоминальщиков и обновим параметры обращения
					CTicketReminder::Remove($f->ID);
				}*/
				
				if(is_array($v->arrOldFields) && is_array($arFields))
				{
					// определяем что изменилось
					$v->arChange = array();
					if ($MID > 0)
					{
						if($arFields["HIDDEN"] != "Y") $v->arChange["MESSAGE"] = "Y";
						else $v->arChange["HIDDEN_MESSAGE"] = "Y";
					}
					if($arFields["CLOSE"] == "Y" && strlen($v->arrOldFields["DATE_CLOSE"]) <= 0)
					{
						$v->arChange["CLOSE"] = "Y";
					}
					elseif($arFields["CLOSE"] == "N" && strlen($v->arrOldFields["DATE_CLOSE"]) > 0)
					{
						$v->arChange["OPEN"] = "Y";
					}
					
					if(array_key_exists("HOLD_ON", $arFields))
					{
						if($v->arrOldFields["HOLD_ON"] == null)
						{
							$v->arrOldFields["HOLD_ON"] = 'N';
						}
						if($arFields["HOLD_ON"] == null)
						{
							$arFields["HOLD_ON"] = 'N';
						}
						if($v->arrOldFields["HOLD_ON"] != $arFields["HOLD_ON"])
						{
							if($arFields["HOLD_ON"] == "Y")
							{
								$v->arChange["HOLD_ON_ON"] = "Y";
							}
							else
							{
								$v->arChange["HOLD_ON_OFF"] = "Y";
							}
							
						}
						unset($v->arrOldFields["HOLD_ON"]);
					}
							
					foreach($v->arrOldFields as $key => $value)
					{
						if(isset($arFields[$key]))
						{
							if ($key === 'TITLE' && $value !== $arFields[$key])
							{
								$v->arChange[$key] = "Y";
							}
							elseif (intval($value) != intval($arFields[$key]))
							{
								$v->arChange[$key] = "Y";
							}
						}
					}
					
					// получим текущие значения обращения
					CTimeZone::Disable();
					$z = CTicket::GetByID($f->ID, $f->SITE_ID, "N");
					CTimeZone::Enable();

					if($zr = $z->Fetch())
					{
						$nf = (object)$zr;
					
						$rsSite = CSite::GetByID($nf->SITE_ID);
						$v->arrSite = $rsSite->Fetch();
						
						self::Set_sendMails($nf, $v, $arFields);
						
						//if ($v->arChange['SLA_ID'] == 'Y' || $v->arChange['OPEN'] == 'Y') CTicketReminder::Update($nf->ID, true);
					}
				}
				CTicket::ExecuteEvents('OnAfterTicketUpdate', $arFields, false);
			}
		}
		else
		{
			// restrict to set SLA_ID directly, allow through events or automatically
			if (isset($arFields['SLA_ID']) && !($v->bSupportTeam || $v->bAdmin || $v->bDemo || $v->bActiveCoupon))
			{
				unset($arFields['SLA_ID']);
			}

			$arFields = CTicket::ExecuteEvents('OnBeforeTicketAdd', $arFields, false);
			if(!$arFields) return false;
			
						
			if(!((strlen(trim($arFields["OWNER_SID"])) > 0 || intval($arFields["OWNER_USER_ID"]) > 0) && ($v->bSupportTeam || $v->bAdmin)))
			{
				$f->OWNER_USER_ID = ($v->uid > 0) ? $v->uid : null;
				$f->OWNER_SID = null;
				$f->OWNER_GUEST_ID = intval($_SESSION["SESS_GUEST_ID"]) > 0 ? intval($_SESSION["SESS_GUEST_ID"]) : null;
			}
						
			$f->FromArray($arFields, "CREATED_USER_ID,CREATED_MODULE_NAME,CATEGORY_ID,STATUS_ID,DIFFICULTY_ID,CRITICALITY_ID,SOURCE_ID,TITLE", array(CSupportTableFields::MORE0,CSupportTableFields::NOT_EMTY_STR));

			if (!$f->CREATED_USER_ID)
			{
				$f->set("CREATED_USER_ID", $v->uid, array(CSupportTableFields::MORE0));
			}

			$f->setCurrentTime("LAST_MESSAGE_DATE,DAY_CREATE,TIMESTAMP_X,DEADLINE_SOURCE_DATE");

			$f->DATE_CREATE = time() + CTimeZone::GetOffset();
			
			// если обращение создается сотрудником техподдержки, администратором или демо пользователем
			if($v->bSupportTeam || $v->bAdmin || $v->Demo)
			{
				$f->FromArray($arFields, "SUPPORT_COMMENTS", array(CSupportTableFields::NOT_EMTY_STR));
			}
			
			if(!self::Set_getCOUPONandSLA($v, $f, $arFields)) return false;
			// $f +SLA_ID $v +V_COUPON +bActiveCoupon
			
			if ($v->bActiveCoupon) $f->COUPON = $v->V_COUPON;
			
			self::Set_getResponsibleUser($v, $f, $arFields);
			// $f +RESPONSIBLE_USER_ID  $v +T_EVENT1 +T_EVENT2 +T_EVENT3
			
			// поля для записи лога
			$v->arFields_log = array(
				"LOG"							=> "Y",
				"MESSAGE_CREATED_USER_ID"		=> $f->CREATED_USER_ID,
				"MESSAGE_CREATED_MODULE_NAME"	=> $f->CREATED_MODULE_NAME,
				"MESSAGE_CREATED_GUEST_ID"		=> $f->MODIFIED_GUEST_ID,
				"MESSAGE_SOURCE_ID"				=> $f->SOURCE_ID
			);
			
			
			$acd0 = intval(COption::GetOptionString("support", "DEFAULT_AUTO_CLOSE_DAYS"));
			$f->AUTO_CLOSE_DAYS = (($acd0 <= 0) ? 7 : $acd0);
			$arFields["AUTO_CLOSE_DAYS"] = $f->AUTO_CLOSE_DAYS;
			
			$arFields_i = $f->ToArray(CSupportTableFields::ALL, array(CSupportTableFields::NOT_NULL,CSupportTableFields::NOT_DEFAULT), true);
			$id = $DB->Insert("b_ticket", $arFields_i, $err_mess . __LINE__);
			if(!($id > 0)) return $id;
			$f->ID = $id;
			$GLOBALS["USER_FIELD_MANAGER"]->Update("SUPPORT", $f->ID, $arFields);
						
			$arFields["MESSAGE_AUTHOR_SID"] = $f->OWNER_SID;
			$arFields["MESSAGE_AUTHOR_USER_ID"] = $f->OWNER_USER_ID;
			$arFields["MESSAGE_CREATED_MODULE_NAME"] = $f->CREATED_MODULE_NAME;
			$arFields["MESSAGE_SOURCE_ID"] = $f->SOURCE_ID;
			$arFields["HIDDEN"] = "N";
			$arFields["LOG"] = "N";
			$arFields["IS_LOG"] = "N";

			if (is_set($arFields, "IMAGE")) $arFields["FILES"][] = $arFields["IMAGE"];
			$arFiles = null;
			$MID = CTicket::AddMessage($f->ID, $arFields, $arFiles, $v->CHECK_RIGHTS);
			$v->arrFILES = $arFiles;
			$MID = intval($MID);
			
			if(intval($MID) > 0)
			{
				//CTicket::UpdateLastParams2($f->ID, array("EVENT"=>array(CTicket::ADD)));
				CTicket::UpdateLastParamsN($f->ID, array("EVENT"=>array(CTicket::ADD)), true, true);
				
				// если указана отметка о спаме то установим отметку о спаме
				if (strlen($f->IS_SPAM) > 0) CTicket::MarkAsSpam($f->ID, $f->IS_SPAM, $v->CHECK_RIGHTS);
				
				/********************************************
					$nf - Заново прочитанные из базы поля
				********************************************/

				CTimeZone::Disable();
				$z = CTicket::GetByID($f->ID, $f->SITE_ID, "N", "N");
				CTimeZone::Enable();
				
				if($zr = $z->Fetch())
				{
					$nf = (object)$zr;

					$rsSite = CSite::GetByID($nf->SITE_ID);
					$v->arrSite = $rsSite->Fetch();

					self::Set_sendMails($nf, $v, $arFields);

					// создаем событие в модуле статистики
					if(CModule::IncludeModule("statistic"))
					{
						if(!$v->category_set)
						{
							$v->T_EVENT1 = "ticket";
							$v->T_EVENT2 = "";
							$v->T_EVENT3 = "";
						}
						if(strlen($v->T_EVENT3) <= 0) $v->T_EVENT3 = "http://" . $_SERVER["HTTP_HOST"] . "/bitrix/admin/ticket_edit.php?ID=" . $f->ID . "&lang=" . $v->arrSite["LANGUAGE_ID"];
						CStatEvent::AddCurrent($v->T_EVENT1, $v->T_EVENT2, $v->T_EVENT3);
					}
					
				}
			}
			// !!! ПРОВЕРИТЬ $arFields ТОЧНО ЛИ ВСЕ $arFields[..] = .. ТАКИЕ ЖЕ КАК В ОРИГИНАЛЕ !!!
			$arFields['ID'] = $f->ID;
			$arFields['MID'] = $MID;
			CTicket::ExecuteEvents('OnAfterTicketAdd', $arFields, true);

		}
		return $f->ID;	
	}

	/***********************************************
			Старые функции для совместимости
	***********************************************/

public static 	function GetFUA($site_id)
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: GetFUA<br>Line: ";
		global $DB;
		if ($site_id=="all") $site_id = "";
		$arFilter = array("TYPE" => "F", "SITE" => $site_id);
		$v2 = $v3 = null;
		$rs = CTicketDictionary::GetList(($v1="s_dropdown"), $v2, $arFilter, $v3);
		return $rs;
	}

	public static function GetRefBookValues($type, $site_id=false)
	{
		$err_mess = (CAllTicket::err_mess())."<br>Function: GetRefBookValues<br>Line: ";
		global $DB;
		if ($site_id=="all") $site_id = "";
		$arFilter = array("TYPE" => $type, "SITE" => $site_id);
		$v2 = $v3 = null;
		$rs = CTicketDictionary::GetList(($v1="s_dropdown"), $v2, $arFilter, $v3);
		return $rs;
	}

public static 	function GetMessages($ticketID, $arFilter=array(), $checkRights="Y")
	{
		$arFilter["TICKET_ID"] = $ticketID;
		$arFilter["TICKET_ID_EXACT_MATCH"] = "Y";
		$by = $order = $is_filtered = null;
		return CTicket::GetMessageList($by, $order, $arFilter, $is_filtered, $checkRights, "Y");
	}

public static 	function GetResponsible()
	{
		return CTicket::GetSupportTeamList();
	}

public static 	function IsResponsible($userID=false)
	{
		return CTicket::IsSupportTeam($userID);
	}

public static 	function ExecuteEvents($message, $arFields, $isNew, &$eventType = false)
	{
		foreach(GetModuleEvents('support', $message, true) as $arr)
		{
			$arFields = ExecuteModuleEventEx($arr, array($arFields, $isNew, &$eventType));
		}

		return $arFields;
	}
	
public static 	function GetResponsibleList($userID, $CMGM = null, $CMUGM = null, $SG = null)
	{
				
		$condition = "";
		if($CMGM != null) $condition .= "
							AND TUG2.CAN_MAIL_GROUP_MESSAGES = '" . ($CMGM == "Y" ? "Y" : "N") . "'";
		if($CMUGM != null) $condition .= "
							AND TUG2.CAN_MAIL_UPDATE_GROUP_MESSAGES = '" . ($CMUGM == "Y" ? "Y" : "N") . "'";
		
		$condition2 = "";
		if($SG != null) $condition2 .= "
							AND TG.IS_TEAM_GROUP = '" . ($SG == "Y" ? "Y" : "N") . "'";
		
		
		$err_mess = (CTicket::err_mess())."<br>Function: GetSupportTeamMailList<br>Line: ";
		global $DB;
		$strSql = "
			SELECT
				U.ID as ID,
				U.LOGIN as LOGIN,
				". $DB->Concat(CTicket::isnull("U.LAST_NAME", "''"), CTicket::isnull("U.NAME", "''"), 'U.LOGIN')." as NAME,
				U.EMAIL as EMAIL
			FROM
				(
				SELECT
					TUG2.USER_ID AS USER_ID				
				FROM
					b_ticket_ugroups TG
					INNER JOIN b_ticket_user_ugroup TUG
						ON TG.ID = TUG.GROUP_ID" . $condition2 . "
					INNER JOIN b_ticket_user_ugroup TUG2
						ON TUG.USER_ID = '" . intval($userID) . "'
							AND TUG.GROUP_ID = TUG2.GROUP_ID" . $condition . "
				GROUP BY
					TUG2.USER_ID
				) TU
				INNER JOIN b_user U
					ON TU.USER_ID = U.ID
				ORDER BY
					U.ID
	
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

public static 	static function GetUsersPropertiesArray($arUserIDs = array(), $arGuestIDs = array())
	{
		$arGuestUserIDs = array();
		$arResUsers = array();
		$arResGuests = array();
		$siteNameFormat = CSite::GetNameFormat();
		$isActive = CModule::IncludeModule("statistic");
		$arUserIDs = array_map(intval, $arUserIDs);


		if(count($arGuestIDs) > 0)
		{
			$arGuestIDsU = array_unique($arGuestIDs);
			$arGuestIDsU = array_map(intval, $arGuestIDsU);
			$arGuestIDs = array();
			if($isActive)
			{
				$strGuests = implode("|", $arGuestIDsU);
				$f = "ID";
				$o = "asc";
				$isf = null;
				$rs = CGuest::GetList($f, $o, array( "ID" => $strGuests), $isf);
				while($ar = $rs->Fetch())
				{
					$arGuestUserIDs[] = intval($ar["LAST_USER_ID"]);
					$arGuestIDs[intval($ar["ID"])] = intval($ar["LAST_USER_ID"]);
				}
			}
			else
			{
				foreach($arGuestIDs as $k => $v)
				{
					$arGuestIDs[$v] = 0;
				}
			}
		}

		if(count($arUserIDs) > 0)
		{
			$arRespUserIDs = array_unique(array_merge($arUserIDs, $arGuestUserIDs));
			$strUsers = implode("|", $arRespUserIDs);
			$f = "ID";
			$o = "asc";
			$rs = CUser::GetList($f, $o, array( "ID" => $strUsers), array("FIELDS"=>array("NAME", "SECOND_NAME","LAST_NAME","LOGIN","ID","EMAIL")));
			while($ar = $rs->Fetch())
			{
				$arResUsers[intval($ar["ID"])] = $ar;
			}
		}

		foreach($arUserIDs as $k => $v)
		{
			if(!isset($arResUsers[$v]))
			{
				$arResUsers[$v] = array("NAME" => GetMessage("SUP_UNKNOWN_USER"), "SECOND_NAME" => "","LAST_NAME" => "","LOGIN" => GetMessage("SUP_UNKNOWN_USER"),"ID" => $v, "EMAIL" => "");
			}
			$name = CUser::FormatName($siteNameFormat, $arResUsers[$v], true, true);
			$arResUsers[$v]["HTML_NAME"] = "[<a title=\"" . GetMessage("SUP_USER_PROFILE") . "\" href=\"/bitrix/admin/user_edit.php?lang=" . LANGUAGE_ID . "&ID=" . $v . "\">" . $v."</a>] (" . htmlspecialcharsbx($arResUsers[$v]['LOGIN'])  . ") " . $name;
				//" (".$str_OWNER_LOGIN.") ".$str_OWNER_NAME;
			$arResUsers[$v]["HTML_NAME_S"] = "[" . $v . "] " . $name;
		}

		foreach($arGuestIDs as $k => $v)
		{
			if(isset($arResUsers[$v]))
			{
				$arResGuests[$k] = $arResUsers[$v];
				$arResGuests[$k]["UNKNOWN"] = false;
			}
			else
			{
				$arResGuests[$k] = array("NAME" => GetMessage("SUP_UNKNOWN_GUEST"), "SECOND_NAME" => "","LAST_NAME" => "","LOGIN" => GetMessage("SUP_UNKNOWN_GUEST"),"ID" => $v,"UNKNOWN" => true, "EMAIL" => "");
			}
			$name = CUser::FormatName($siteNameFormat, $arResGuests[$k], true, true);
			$arResGuests[$k]["HTML_NAME"] = "[<a title=\"" . GetMessage("SUP_USER_PROFILE") . "\" href=\"/bitrix/admin/user_edit.php?lang=" . LANGUAGE_ID . "&ID=" . $v . "\">" . $v."</a>] " . $name .
				" [<a title='" . GetMessage("SUP_GUEST_ID") . "'  href='/bitrix/admin/guest_list.php?lang=" . LANG . "&find_id=" . $k . "&find_id_exact_match=Y&set_filter=Y'>" . $k . "</a>]";
			$arResUsers[$v]["HTML_NAME_S"] = "[" . $v . "] " . $name . " [" . $k . "]";
		}

		return array("arUsers" => $arResUsers, "arGuests" => $arResGuests);
	}


	
	
}

?>
