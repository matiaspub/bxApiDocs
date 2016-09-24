<?
##############################################
# Bitrix Site Manager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:sources@bitrixsoft.com              #
##############################################
IncludeModuleLangFile(__FILE__);

//
class CControllerClient
{
	public static function IsInCommonKernel()
	{
		return file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_db_updater.php");
	}

	// Controller's authentication
	public static function OnExternalLogin(&$arParams)
	{
		global $USER, $APPLICATION;
		$FORMAT_DATE = false;
		$FORMAT_DATETIME = false;

		$prefix = COption::GetOptionString("main", "auth_controller_prefix", "controller");
		if(
			($prefix!='' && substr(strtolower($arParams["LOGIN"]), 0, strlen($prefix)) == $prefix)
			||
			($prefix=='' && strpos($arParams["LOGIN"], "\\")===false)
		)
		{
			$site = $prefix;
			if($prefix=='')
				$login = $arParams["LOGIN"];
			else
				$login = substr($arParams["LOGIN"], strlen($prefix)+1);
			$password = $arParams["PASSWORD"];
			$arVars = array("login"=>$login, "password"=>$password);

			$oRequest = new CControllerClientRequestTo("check_auth", $arVars);
			$oResponse = $oRequest->SendWithCheck();
			if($oResponse == false)
				return false;

			if(!$oResponse->OK())
			{
				$e = new CApplicationException(GetMessage("MAIN_CMEMBER_ERR1").": ".$oResponse->text);
				$APPLICATION->ThrowException($e);
				return false;
			}

			$arUser = $oResponse->arParameters['USER_INFO'];

		}
		elseif(
			COption::GetOptionString("main", "auth_controller_sso", "N")=="Y"
			&& strpos($arParams["LOGIN"], "\\") > 0
		)
		{
			$site = substr($arParams["LOGIN"], 0, strpos($arParams["LOGIN"], "\\"));
			$login = substr($arParams["LOGIN"], strpos($arParams["LOGIN"], "\\")+1);
			$password = $arParams["PASSWORD"];
			$arVars = array("login"=>$login, "password"=>$password, "site"=>$site);

			$oRequest = new CControllerClientRequestTo("remote_auth", $arVars);
			$oResponse = $oRequest->SendWithCheck();
			if($oResponse == false)
				return false;

			if(!$oResponse->OK())
			{
				$e = new CApplicationException(GetMessage("MAIN_CMEMBER_ERR1").": ".$oResponse->text);
				$APPLICATION->ThrowException($e);
				return false;
			}

			$arUser = $oResponse->arParameters['USER_INFO'];
		}
		elseif(
			COption::GetOptionString("controller", "auth_controller_enabled", "N") === "Y"
			&& strpos($arParams["LOGIN"], "\\") > 0
			&& CModule::IncludeModule("controller")
		)
		{
			$site = substr($arParams["LOGIN"], 0, strpos($arParams["LOGIN"], "\\"));
			$login = substr($arParams["LOGIN"], strpos($arParams["LOGIN"], "\\")+1);
			$password = $arParams["PASSWORD"];

			$url = strtolower(trim($site, " \t\r\n./"));
			if(substr($url, 0, 7) != "http://" && substr($url, 0, 8) != "https://")
				$url = array("http://".$url, "https://".$url);

			$dbr_mem = CControllerMember::GetList(
				Array(),
				Array(
					"=URL" => $url,
					"=DISCONNECTED" => "N",
					"=ACTIVE" => "Y"
				)
			);
			$ar_mem = $dbr_mem->Fetch();
			if(!$ar_mem)
				return false;

			$arGroupsMap = unserialize(COption::GetOptionString("controller", "auth_controller", serialize(array())));
			$res = CControllerMember::CheckUserAuth($ar_mem["ID"], $login, $password, $arGroupsMap);

			if(!is_array($res))
				return false;

			$arUser = $res['USER_INFO'];
			if(is_array($arUser))
				$arUser["CONTROLLER_ADMIN"] = "N";
			if (isset($res["FORMAT_DATE"]))
				$FORMAT_DATE = $res["FORMAT_DATE"];
			if (isset($res["FORMAT_DATETIME"]))
				$FORMAT_DATETIME = $res["FORMAT_DATETIME"];
		}
		else
		{
			return false;
		}

		////////////////////////////////////////////////////////
		/// сравнивать не просто логин, а полностью\логин
		/////////////////////////
		if(is_array($arUser) && strtolower($arUser['LOGIN']) == strtolower($login))
		{
			//When user did not fill any inforamtion about
			//we'll use first part of his e-mail like login
			if(strlen($arUser["NAME"]) == 0 && strlen($arUser["SECOND_NAME"]) == 0)
			{
				if(preg_match("/^(.+)@/", $arUser["LOGIN"], $match))
					$arUser["NAME"] = $match[1];
				else
					$arUser["NAME"] = $arUser["LOGIN"];
			}

			if($site=='')
				$arUser['LOGIN'] = $arUser['LOGIN'];
			else
				$arUser['LOGIN'] = $site."\\".$arUser['LOGIN'];

			$USER_ID = CControllerClient::UpdateUser($arUser, $FORMAT_DATE, $FORMAT_DATETIME);

			if($arUser["CONTROLLER_ADMIN"]=="Y")
			{
				AddEventHandler("main", "OnAfterUserLogin", array("CControllerClient", "OnAfterUserLogin"));
				$arParams["CONTROLLER_ADMIN"] = "Y";
			}

			$arParams["REMEMBER"] = "N";

			if (
				$ar_mem
				&& $USER_ID
				&& class_exists("\\Bitrix\\Controller\\AuthLogTable")
				&& \Bitrix\Controller\AuthLogTable::isEnabled()
			)
			{
				\Bitrix\Controller\AuthLogTable::logSiteToControllerAuth(
					$ar_mem["ID"],
					$USER_ID,
					true,
					'CONTROLLER_MEMBER',
					$arUser['NAME'].' '.$arUser['LAST_NAME'].' ('.$arUser['LOGIN'].')'
				);
			}

			return $USER_ID;
		}

		return false;
	}

	public static function OnAfterUserLogin(&$arParams)
	{
		global $USER;
		if($arParams["CONTROLLER_ADMIN"] === "Y")
			$USER->SetControllerAdmin();
	}

	public static function UpdateUser($arFields = Array(), $FORMAT_DATE = false, $FORMAT_DATETIME = false)
	{
		global $DB;

		$arFields["ACTIVE"] = "Y";
		$arFields["PASSWORD"] = \Bitrix\Main\Security\Random::getString(32);

		$oUser = new CUser;
		unset($arFields["ID"]);
		unset($arFields["TIMESTAMP_X"]);
		unset($arFields["DATE_REGISTER"]);
		if (
			isset($arFields["PERSONAL_BIRTHDAY"])
			&& $arFields["PERSONAL_BIRTHDAY"] != ''
			&& $FORMAT_DATE !== false
		)
		{
			$arFields["PERSONAL_BIRTHDAY"] = $DB->FormatDate($arFields["PERSONAL_BIRTHDAY"], $FORMAT_DATE, FORMAT_DATE);
		}

		$dbr_user = CUser::GetList($O = '', $B = '', array(
			"LOGIN_EQUAL_EXACT" => $arFields["LOGIN"],
			"EXTERNAL_AUTH_ID" => "__controller",
		));
		if ($ar_user = $dbr_user->Fetch())
		{
			$arFields['EXTERNAL_AUTH_ID'] = "__controller";
			$USER_ID = $ar_user["ID"];

			if(is_array($arFields["GROUPS_TO_ADD"]) && is_array($arFields["GROUPS_TO_DELETE"]))
			{
				$arFields["GROUP_ID"] = CUser::GetUserGroup($USER_ID);
				foreach($arFields["GROUPS_TO_DELETE"] as $group_id)
				{
					$group_id = CGroup::GetIDByCode($group_id);
					if($group_id > 0)
					{
						$p = array_search($group_id, $arFields["GROUP_ID"]);
						if($p !== false)
							unset($arFields["GROUP_ID"][$p]);
					}
				}
				foreach($arFields["GROUPS_TO_ADD"] as $group_id)
				{
					$group_id = CGroup::GetIDByCode($group_id);
					if($group_id > 0)
						$arFields["GROUP_ID"][] = $group_id;
				}
			}
			elseif(is_array($arFields["GROUP_ID"]))
			{
				$groups = $arFields["GROUP_ID"];
				$arFields["GROUP_ID"] = array();
				foreach($groups as $group_id)
				{
					$group_id = CGroup::GetIDByCode($group_id);
					if($group_id > 0)
						$arFields["GROUP_ID"][] = $group_id;
				}
			}

			if(!$oUser->Update($USER_ID, $arFields))
				return false;
		}
		else
		{
			$arFields['EXTERNAL_AUTH_ID'] = "__controller";
			$arFields["LID"] = SITE_ID;
			if(is_array($arFields["GROUP_ID"]))
			{
				$groups = $arFields["GROUP_ID"];
				$arFields["GROUP_ID"] = array();
				foreach($groups as $group_id)
				{
					$group_id = CGroup::GetIDByCode($group_id);
					if($group_id > 0)
						$arFields["GROUP_ID"][] = $group_id;
				}
			}

			$USER_ID = $oUser->Add($arFields);
		}

		return $USER_ID;
	}

	public static function AuthorizeAdmin($arParams = Array())
	{
		global $USER;
		$ADMIN_ID = 0;
		if($arParams["ID"]>0)
			$ADMIN_ID = $arParams["ID"];
		else
		{
			unset($arParams["GROUP_ID"]);
			$ADMIN_ID = CControllerClient::UpdateUser($arParams);
		}

		if($ADMIN_ID>0)
		{
			CUser::SetUserGroup($ADMIN_ID, Array(1));

			$USER->Authorize($ADMIN_ID);
			$USER->SetControllerAdmin();

			return $ADMIN_ID;
		}

		return false;
	}

	public static function AuthorizeUser($arParams = Array())
	{
		global $USER;

		$USER_ID = CControllerClient::UpdateUser($arParams);
		if($USER_ID > 0)
		{
			$USER->Authorize($USER_ID);
			return $USER_ID;
		}

		return false;
	}

	public static function OnExternalAuthList()
	{
		$arResult = Array(
				Array(
					"ID" => "__controller",
					"NAME" => GetMessage("MAIN_CMEMBER_AUTH_TYPE")
				)
			);

		return $arResult;
	}

	public static function PrepareUserInfo($arUser)
	{
		$arFields = array(
			"ID",
			"LOGIN",
			"NAME",
			"LAST_NAME",
			"EMAIL",
			"PERSONAL_PROFESSION",
			"PERSONAL_WWW",
			"PERSONAL_ICQ",
			"PERSONAL_GENDER",
			"PERSONAL_BIRTHDAY",
			"PERSONAL_PHONE",
			"PERSONAL_FAX",
			"PERSONAL_MOBILE",
			"PERSONAL_PAGER",
			"PERSONAL_STREET",
			"PERSONAL_MAILBOX",
			"PERSONAL_CITY",
			"PERSONAL_STATE",
			"PERSONAL_ZIP",
			"PERSONAL_COUNTRY",
			"PERSONAL_NOTES",
			"WORK_COMPANY",
			"WORK_DEPARTMENT",
			"WORK_POSITION",
			"WORK_WWW",
			"WORK_PHONE",
			"WORK_FAX",
			"WORK_PAGER",
			"WORK_STREET",
			"WORK_MAILBOX",
			"WORK_CITY",
			"WORK_STATE",
			"WORK_ZIP",
			"WORK_COUNTRY",
			"WORK_PROFILE",
			"WORK_NOTES",
		);

		$arSaveUser = array();
		foreach ($arFields as $key)
			$arSaveUser[$key] = $arUser[$key];

		return $arSaveUser;
	}

	public static function SendMessage($name, $status = 'Y', $description = '')
	{
		// send to controller
		$arVars =
			Array(
				"NAME"	=>	$name,
				"STATUS"	=>	$status,
				"DESCRIPTION"=>	$description,
				);

		$oRequest = new CControllerClientRequestTo("log", $arVars);
		if(($oResponse = $oRequest->SendWithCheck())==false)
			return false;

		if(!$oResponse->OK())
		{
			$e = new CApplicationException(GetMessage("MAIN_CMEMBER_ERR2").": ".$oResponse->text);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}
	}

	public static function InitTicket($controller_url)
	{
		// generating own member_id and temporary ticket
		$member_id = "m".\Bitrix\Main\Security\Random::getString(31);
		COption::SetOptionString("main", "controller_member_id", $member_id);

		$member_secret_id = "m".\Bitrix\Main\Security\Random::getString(31);
		COption::SetOptionString("main", "controller_member_secret_id", $member_secret_id);

		$ticket_id = "m".\Bitrix\Main\Security\Random::getString(31);
		COption::SetOptionString("main", "controller_ticket", time()."|".$ticket_id."|".$controller_url);
		COption::SetOptionString("main", "controller_url", $controller_url);

		return array($member_id, $member_secret_id, $ticket_id);
	}

	public static function JoinToControllerEx($controller_url, $controller_login, $controller_password, $arMemberParams = Array())
	{
		if(COption::GetOptionString("main", "controller_member", "N")=="Y")
			return false;

		if(strlen($arMemberParams["URL"])<=0)
			$arMemberParams["URL"] = $_SERVER['HTTP_HOST'];

		list($member_id, $member_secret_id, $ticket_id) = CControllerClient::InitTicket($controller_url);

		// send to controller
		$arVars =
			Array(
				"member_secret_id" => $member_secret_id,
				"ticket_id" => $ticket_id,
				"admin_login" => $controller_login,
				"admin_password" => $controller_password,
				"url" => $arMemberParams["URL"],
				"name" => $arMemberParams["NAME"],
				"contact_person" => $arMemberParams["CONTACT_PERSON"],
				"email" => $arMemberParams["EMAIL"],
				"shared_kernel" => ($arMemberParams["SHARED_KERNEL"]?"Y":"N"),
				);

		if($arMemberParams["CONTROLLER_GROUP"]>0)
			$arVars['group_id'] = $arMemberParams["CONTROLLER_GROUP"];

		$oRequest = new CControllerClientRequestTo("join", $arVars);
		if(($oResponse = $oRequest->Send())==false)
			return false;

		if(!$oResponse->OK())
		{
			$e = new CApplicationException(GetMessage("MAIN_CMEMBER_ERR3").": ".$oResponse->text);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		COption::SetOptionString("main", "controller_member", "Y");

		global $USER;
		$USER->Authorize($USER->GetID());

		return true;
	}

	public static function JoinToController($controller_url, $controller_login, $controller_password, $site_url = false, $controller_group = false, $site_name = false, $bSharedKernel = false)
	{
		$arMemberParams = Array(
				"URL" => $site_url,
				"NAME" => $site_name,
				"SHARED_KERNEL" => $bSharedKernel,
				"CONTROLLER_GROUP" => $controller_group
			);

		return CControllerClient::JoinToControllerEx($controller_url, $controller_login, $controller_password, $arMemberParams);
	}


	public static function RemoveFromController($controller_login, $controller_password)
	{
		if(COption::GetOptionString("main", "controller_member", "N")!="Y")
			return false;

		// send to controller
		$arVars =
			Array(
				"admin_login"	=>	$controller_login,
				"admin_password"=>	$controller_password,
				);

		$oRequest = new CControllerClientRequestTo("remove", $arVars);
		if(($oResponse = $oRequest->SendWithCheck())==false)
			return false;

		if(!$oResponse->OK())
		{
			$e = new CApplicationException(GetMessage("MAIN_CMEMBER_ERR4").": ".$oResponse->text);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		COption::SetOptionString("main", "controller_member", "N");
		return true;
	}

	public static function UpdateCounters()
	{
		if(COption::GetOptionString("main", "controller_member", "N") != "Y")
		{
			//remove this agent when disconnected from the controller
			return "";
		}
		else
		{
			$oRequest = new CControllerClientRequestTo("update_counters");
			$oResponse = $oRequest->SendWithCheck();

			if($oResponse == false)
				throw new \Bitrix\Main\SystemException("CControllerClient::UpdateCounters: unknown error");
			elseif(!$oResponse->OK())
				throw new \Bitrix\Main\SystemException("CControllerClient::UpdateCounters: ".$oResponse->text);

			return "CControllerClient::UpdateCounters();";
		}
	}

	public static function ExecuteEvent($eventName, $arParams = array())
	{
		if(COption::GetOptionString("main", "controller_member", "N") != "Y")
		{
			return null;
		}
		else
		{
			$oRequest = new CControllerClientRequestTo("execute_event", array(
				"event_name" => $eventName,
				"parameters" => $arParams,
			));
			$oResponse = $oRequest->SendWithCheck();

			if($oResponse == false)
				error_log("CControllerClient::ExecuteEvent: unknown error");
			elseif(!$oResponse->OK())
				error_log("CControllerClient::ExecuteEvent: ".$oResponse->text);

			return $oResponse->arParameters['result'];
		}
	}

	public static function Unlink()
	{
		$disconnect_command = COption::GetOptionString("main", "~controller_disconnect_command", "");
		if(strlen($disconnect_command)>0)
			eval($disconnect_command);
		COption::SetOptionString("main", "controller_member", "N");
	}

	public static function GetBackup($bRefresh = false)
	{
		static $arCachedData;
		if(!isset($arCachedData) || $bRefresh)
			$arCachedData = unserialize(COption::GetOptionString("main", "~controller_backup", ""));

		return $arCachedData;
	}

	public static function SetBackup($arBackup)
	{
		COption::SetOptionString("main", "~controller_backup", serialize($arBackup));
		CControllerClient::GetBackup(true);
	}

	public static function SetOptionString($module_id, $option_id, $value)
	{
		$arBackup = CControllerClient::GetBackup();
		if(!is_set($arBackup["options"][$module_id], $option_id))
		{
			$arBackup["options"][$module_id][$option_id] = COption::GetOptionString($module_id, $option_id, "");
			CControllerClient::SetBackup($arBackup);
		}
		COption::SetOptionString($module_id, $option_id, $value);
	}

	public static function RestoreOption($module_id, $option_id)
	{
		$arBackup = CControllerClient::GetBackup();
		if(is_set($arBackup["options"][$module_id], $option_id))
		{
			COption::SetOptionString($module_id, $option_id, $arBackup["options"][$module_id][$option_id]);
			unset($arBackup["options"][$module_id][$option_id]);
			CControllerClient::SetBackup($arBackup);
			return true;
		}
		return false;
	}

	public static function SetModules($arModules)
	{
		$arInstalled = Array();
		$arm = CModule::_GetCache();
		foreach($arm as $module_id => $tr)
			$arInstalled[] = $module_id;
		$arBackup = CControllerClient::GetBackup();
		if(!isset($arBackup["modules"]))
		{
			$arBackup["modules"] = $arInstalled;
			CControllerClient::SetBackup($arBackup);
		}

		foreach($arModules as $module_id=>$status)
		{
			if(!($oModule = CModule::CreateModuleObject($module_id)))
				continue;

			if($status=="Y" && !in_array($module_id, $arInstalled))
			{
				if(!method_exists($oModule, "InstallDB") || $oModule->InstallDB()===false)
					$oModule->Add();
			}
			elseif($status=="N" && in_array($module_id, $arInstalled))
			{
				$oModule->Remove();
			}
		}

		return true;
	}

	public static function RestoreModules()
	{
		$arBackup = CControllerClient::GetBackup();
		if(isset($arBackup["modules"]))
		{
			$oModule = new CModule();
			$arWasInstalled = $arBackup["modules"];

			$arNowInstalled = Array();
			$arm = CModule::_GetCache();
			foreach($arm as $module_id => $tr)
				$arNowInstalled[] = $module_id;

			foreach($arNowInstalled as $module_id)
			{
				if(!in_array($module_id, $arWasInstalled))
				{
					$oModule->MODULE_ID = $module_id;
					$oModule->Remove();
				}
				else
					unset($arWasInstalled[array_search($module_id, $arWasInstalled)]);
			}

			foreach($arWasInstalled as $module_id)
			{
				$oModule->MODULE_ID = $module_id;
				$oModule->Add();
			}

			unset($arBackup["modules"]);
			CControllerClient::SetBackup($arBackup);
		}
	}

	public static function RestoreGroupSecurity($group_code, $arModules)
	{
		$arBackup = CControllerClient::GetBackup();

		if(($group_id = CGroup::GetIDByCode($group_code))<=0)
			return false;

		$arBackup = CControllerClient::GetBackup();
		$old_settings = $arBackup["security"][$group_code];
		if(!isset($old_settings))
			return ;

		foreach($old_settings as $module_id=>$level)
		{
			if(!in_array($module_id, $arModules))
				continue;

			CGroup::SetModulePermission($group_id, $module_id, $level);
			unset($arBackup["security"][$group_code][$module_id]);
		}

		CControllerClient::SetBackup($arBackup);
	}

	public static function SetTaskSecurity($task_id, $module_id, $arOperations, $letter = '')
	{
		$ID = 0;
		$dbr_task = CTask::GetList(Array(), Array('NAME'=>$task_id, 'MODULE_ID'=>$module_id, "BINDING" => 'module'));
		if($ar_task = $dbr_task->Fetch())
		{
			if($ar_task['SYS']=='Y')
				return false;
			$ID = $ar_task['ID'];
		}

		$arFields = array(
				"NAME" => $task_id,
				"LETTER" => $letter,
				"BINDING" => 'module',
				"MODULE_ID" => $module_id
			);

		if($ID>0)
			$res = CTask::Update($arFields, $ID);
		else
		{
			$ID = CTask::Add($arFields);
			$res = ($ID>0);
			if($res)
			{
				$arBackup = CControllerClient::GetBackup();
				$arBackup['security_task'][] = $ID;
				CControllerClient::SetBackup($arBackup);
			}
		}

		if($res)
		{
			CTask::SetOperations($ID, $arOperations, true);
		}
	}

	public static function SetGroupSecurity($group_code, $arPermissions, $arSubGroups = false)
	{
		if(($group_id = CGroup::GetIDByCode($group_code))<=0)
			return false;

		$arBackup = CControllerClient::GetBackup();
		foreach($arPermissions as $module_id=>$level)
		{
			if(!is_set($arBackup["security"][$group_code], $module_id))
			{
				$arBackup["security"][$group_code][$module_id] = CGroup::GetModulePermission($group_id, $module_id);
			}

			CGroup::SetModulePermission($group_id, $module_id, $level);
		}

		if(is_array($arSubGroups))
		{
			$arSubordGroupID = Array();
			foreach($arSubGroups as $sub_group_id)
			{
				$sub_group_id = CGroup::GetIDByCode($sub_group_id);
				if($sub_group_id > 0)
					$arSubordGroupID[] = $sub_group_id;
			}

			if(!is_set($arBackup["security_subord_groups"], $group_code))
			{
				$arBackup["security_subord_groups"][$group_code] = CGroup::GetSubordinateGroups($group_id);
			}

			CGroup::SetSubordinateGroups($group_id, $arSubordGroupID);
		}

		CControllerClient::SetBackup($arBackup);
	}

	public static function RestoreSecurity($arExcludeGroups = array())
	{
		$arBackup = CControllerClient::GetBackup();
		if(!is_array($arBackup))
			return true;

		if(is_array($arBackup["security"]))
		{
			foreach($arBackup["security"] as $group_code=>$perms)
			{
				if(in_array($group_code, $arExcludeGroups))
					continue;

				if(($group_id = CGroup::GetIDByCode($group_code))>0)
				{
					foreach($perms as $module_id=>$level)
						CGroup::SetModulePermission($group_id, $module_id, $level);

					if(isset($arBackup["security_subord_groups"][$group_code]))
						CGroup::SetSubordinateGroups($group_id, $arBackup["security_subord_groups"][$group_code]);
				}
				unset($arBackup["security"][$group_code]);
				unset($arBackup["security_subord_groups"][$group_code]);
			}

			if(count($arBackup["security"])<=0)
				unset($arBackup["security"]);

			CControllerClient::SetBackup($arBackup);
		}

		return true;
	}

	public static function RestoreAll()
	{
		$arBackup = CControllerClient::GetBackup();
		if(!is_array($arBackup))
			return true;

		if(is_array($arBackup["options"]))
		{
			foreach($arBackup["options"] as $module_id=>$options)
			{
				foreach($options as $option_id=>$option_value)
				{
					COption::SetOptionString($module_id, $option_id, $option_value);
				}
			}
		}
		CControllerClient::RestoreModules();
		CControllerClient::RestoreSecurity();
		if(is_array($arBackup['security_task']))
			foreach($arBackup['security_task'] as $task_id)
				CTask::Delete($task_id);
		CControllerClient::SetBackup(array());
		return true;
	}

	public static function GetInstalledOptions($module_id)
	{
		$arOptions = CControllerClient::GetBackup();
		$arOptions = $arOptions["options"][$module_id];
		if(!is_array($arOptions))
			return Array();
		return $arOptions;
	}

	public static function RunCommand($command, $oRequest, $oResponse)
	{
		global $APPLICATION, $USER, $DB;
		return eval($command);
	}
}


/**
 * Class __CControllerPacket
 *
 * Base class for various types of packets.
 */
class __CControllerPacket
{
	public $debug_const = '';
	public $debug_file_const = '';

	var $member_id;
	var $session_id;
	var $version;
	var $strParameters = "";
	var $arParameters = array();
	var $hash;
	var $secret_id;
	var $encoding;

	/**
	 * Returns true if debug is enabled.
	 *
	 * @return boolean
	 */
	public function isDebugEnabled()
	{
		if (
			$this->debug_const
			&& defined($this->debug_const)
			&& constant($this->debug_const) === true
		)
			return true;
		else
			return false;
	}

	/**
	 * Returns debug logs directory.
	 * You may define constant CONTROLLER_CLIENT_LOG_DIR.
	 * It's value must end with directory delimiter.
	 * By default logging will be into /bitrix/controller_logs/ directory
	 * under the DOCUMENT_ROOT.
	 *
	 * @return string
	 */
	public function getDebugLogDirectory()
	{
		if ($this->debug_file_const && defined($this->debug_file_const))
			return (string)constant($this->debug_file_const);
		else
			return $_SERVER['DOCUMENT_ROOT'].'/bitrix/controller_logs/';
	}

	/**
	 * Returns log file name.
	 * It's equal to session identifier or if there is no session it's randomized.
	 *
	 * @return string
	 */
	public function getDebugLogFileName()
	{
		if ($this->session_id)
			return $this->session_id.".log";
		else
			return "gen_".\Bitrix\Main\Security\Random::getString(32).".log";

	}

	/**
	 * Writes given information into log file.
	 *
	 * @param string|array $sText Text to write to log.
	 *
	 * @return void
	 * @see __CControllerPacket::isDebugEnabled
	 * @see __CControllerPacket::getDebugLogDirectory
	 * @see __CControllerPacket::getDebugLogFileName
	 */
	public function Debug($sText)
	{
		if (!$this->isDebugEnabled())
			return;

		$filename = $this->getDebugLogDirectory().$this->getDebugLogFileName();

		ignore_user_abort(true);

		CheckDirPath($filename);
		file_put_contents($filename, date("Y-m-d H:i:s")." - ".(is_array($sText)? print_r($sText, true): $sText)."\n----------\n", FILE_APPEND | LOCK_EX);

		ignore_user_abort(false);
	}

	/**
	 * Unpacks $parameters and converts it using given $encoding into site charset.
	 * Preserves "file" member of the array.
	 *
	 * @param string $parameters Serialized data.
	 * @param string $encoding Encoding character set identifier.
	 *
	 * @return mixed|null
	 */
	protected function unpackParameters($parameters, $encoding = '')
	{
		if (CheckSerializedData($parameters))
		{
			$arParameters = unserialize($parameters);
			if ($encoding)
			{
				if (array_key_exists("file", $arParameters))
				{
					$file = $arParameters["file"];
					unset($arParameters["file"]);
					$this->_decode($arParameters, $encoding, SITE_CHARSET);
					$arParameters["file"] = $file;
				}
				else
				{
					$this->_decode($arParameters, $encoding, SITE_CHARSET);
				}
			}
			return $arParameters;
		}
		return null;
	}

	/**
	 * Recursively converts arParameter character set.
	 *
	 * @param array &$arParameters Input/Output parameter.
	 * @param string $encodingFrom Encoding character set identifier.
	 * @param string $encodingTo Encoding character set identifier.
	 *
	 * @return void
	 * @see CMain::ConvertCharset
	 */
	protected function _decode(&$arParameters, $encodingFrom ,$encodingTo)
	{
		global $APPLICATION;

		if (is_array($arParameters))
		{
			$res = array();
			foreach ($arParameters as $key => $value)
			{
				if (is_string($key))
				{
					$key = $APPLICATION->ConvertCharset($key, $encodingFrom, $encodingTo);
				}

				$this->_decode($value, $encodingFrom, $encodingTo);

				$res[$key] = $value;
			}
			$arParameters = $res;
		}
		elseif (is_string($arParameters))
		{
			$arParameters = $APPLICATION->ConvertCharset($arParameters, $encodingFrom, $encodingTo);
		}
	}

	/**
	 * Checks given parameters signature against $hash member.
	 *
	 * @param string $parameter,... Parameter to be checked
	 *
	 * @return boolean
	 */
	public function Check()
	{
		global $APPLICATION;

		$hash = implode("|", func_get_args());
		$md5hash = md5($hash);

		if ($md5hash != $this->hash)
		{
			if (is_object($APPLICATION))
			{
				$e = new CApplicationException("Hash check failed: hash(".$hash.")=".$md5hash." != ".$this->hash);
				$APPLICATION->ThrowException($e);
			}
			return false;
		}

		return true;
	}

	/**
	 * Returns signature for given parameters.
	 * Sets $hash member with calculated value.
	 *
	 * @param string $parameter,... Parameter to be checked
	 *
	 * @return boolean
	 */
	public function Sign()
	{
		$hash = implode("|", func_get_args());
		$this->hash = md5($hash);
		return $this->hash;
	}
}

///////////////////////////////////////////////////////////////////////////////////////////
// Базовый класс для классов типа Request:
//
// Для использования на контроллере:
// CControllerServerRequestTo - Класс для отправки запроса клиенту
// CControllerServerRequestFrom - Класс для получения запроса от клиента
//
// Для использования на клиенте:
// CControllerClientRequestTo - Класс для отправки запроса на сервер
// CControllerClientRequestFrom - Класс для получения запроса от сервера
///////////////////////////////////////////////////////////////////////////////////////////
class __CControllerPacketRequest extends __CControllerPacket
{
	public $operation;
	protected $hostname = '';

	///////////////////////////////////
	//Для работе в классах получающих результаты (CControllerClientRequestFrom, CControllerServerRequestFrom):
	///////////////////////////////////

	// заполняет объект переменными, пришедшими в $_REQUEST
	public function InitFromRequest()
	{
		$this->member_id = $_REQUEST['member_id'];
		$this->session_id = $_REQUEST['session_id'];
		$this->operation = $_REQUEST['operation'];

		if(isset($_REQUEST['version']))
			$this->version = $_REQUEST['version'];

		if(isset($_REQUEST['encoding']))
			$this->encoding = $_REQUEST['encoding'];

		if(isset($_REQUEST['parameters']))
		{
			$this->strParameters = base64_decode($_REQUEST['parameters']);
			$arParameters = $this->unpackParameters($this->strParameters, isset($_REQUEST['encoding'])? $_REQUEST['encoding']: '');
			if ($arParameters)
			{
				$this->arParameters = $arParameters;
			}
		}
		$this->hash = $_REQUEST['hash'];
	}

	/**
	 * Checks packet consistency.
	 *
	 * @return boolean
	 * @see __CControllerPacket::Check
	 */
	public function Check()
	{
		return parent::Check($this->operation, $this->strParameters, $this->secret_id);
	}

	/**
	 * Sets $hash member with calculated value.
	 *
	 * @return void
	 */
	public function Sign()
	{
		parent::Sign($this->operation, serialize($this->arParameters), $this->secret_id);
	}

	/**
	 * Check if it was internal call or browser redirect.
	 *
	 * @return boolean
	 **/
	static public function Internal()
	{
		return (count($_POST)>0);
	}

	///////////////////////////////////////
	// Для работы в классах отправляющих запросы (CControllerClientRequestTo, CControllerServerRequestTo):
	///////////////////////////////////////

	/**
	 * Returns url encoded and signed string of parameters.
	 *
	 * @return string
	 **/
	public function MakeRequestString()
	{
		$result = "operation=".$this->operation.
			"&version=".$this->version.
			"&session_id=".$this->session_id.
			"&member_id=".urlencode($this->member_id).
			"&encoding=".urlencode(SITE_CHARSET);

		if(is_array($this->arParameters))
		{
			$result .= "&parameters=".urlencode(base64_encode(serialize($this->arParameters)));
		}

		$this->Sign();
		$result .= "&hash=".urlencode($this->hash);

		return $result;
	}

	/**
	 * Asks user browser to do redirect.
	 * And thus make a hit with all needed payload.
	 *
	 * @return void
	 **/
	public function RedirectRequest($url)
	{
		if(strpos($url, "?")>0)
			$url .= '&';
		else
			$url .= '?';

		$url .= $this->MakeRequestString();

		if ($this->isDebugEnabled())
		{
			$this->Debug(array(
				__FILE__.":".__LINE__,
				"Request by redirect",
				"url" => $url,
				"Packet" => $this,
			));
		}

		LocalRedirect($url, true);
	}

	/**
	 * Sends packet.
	 *
	 * @param string $url
	 * @param string $page
	 * @return __CControllerPacketResponse
	 */
	public function Send($url = "", $page = "")
	{
		global $APPLICATION;

		$server_port = 80;
		$server_name = strtolower(trim($url, "/ \r\n\t"));
		if (substr($server_name, 0, 7)=='http://')
		{
			$server_name = substr($server_name, 7);
		}
		elseif (substr($server_name, 0, 8)=='https://')
		{
			$server_name = substr($server_name, 8);
			$server_port = 443;
		}

		if (preg_match('/.+:([0-9]+)$/', $server_name, $matches))
		{
			$server_port = $matches[1];
			$server_name = substr($server_name, 0, 0 - strlen($server_port) - 1);
		}

		$proxy_url = COption::GetOptionString("main", "controller_proxy_url", "");
		$proxy_port = COption::GetOptionString("main", "controller_proxy_port", "");

		// соединяемся с удаленным сервером
		$bUseProxy = (strlen($proxy_url) > 0 && strlen($proxy_port) > 0);

		if ($bUseProxy)
		{
			$proxy_port = intval($proxy_port);
			if ($proxy_port <= 0)
				$proxy_port = 80;

			$requestIP = $proxy_url;
			$requestPort = $proxy_port;
		}
		elseif ($this->hostname)
		{
			$requestIP = $this->hostname;
			$requestPort = $server_port;
		}
		else
		{
			$requestIP = $server_name;
			$requestPort = $server_port;
		}

		$conn = @fsockopen(($requestPort==443? 'ssl://': '').$requestIP, $requestPort, $errno, $errstr, 30);
		if (!$conn)
		{
			if ($this->isDebugEnabled())
			{
				$this->Debug(array(
					__FILE__.":".__LINE__,
					"We can't send request to the $server_name:$server_port from member#".$this->member_id,
					"errno" => $errno,
					"errstr" => $errstr,
					"Packet" => $this,
				));
			}

			if (is_object($APPLICATION))
			{
				$strError = GetMessage("MAIN_CMEMBER_ERR5").$server_name.":".$server_port." (".$errstr.")";
				$e = new CApplicationException(htmlspecialcharsex($strError));
				$APPLICATION->ThrowException($e);
			}

			return false;
		}

		$strVars = $this->MakeRequestString();

		if ($bUseProxy)
		{
			$strRequest = "POST http://".$server_name.":".$server_port.$page." HTTP/1.0\r\n";

			$proxy_user = COption::GetOptionString("main", "controller_proxy_user", "");
			$proxy_password = COption::GetOptionString("main", "controller_proxy_password", "");
			if (strlen($proxy_user) > 0)
			{
				$strRequest .= "Proxy-Authorization: Basic ".base64_encode($proxy_user.":".$proxy_password)."\r\n";
			}
		}
		else
		{
			$strRequest = "POST ".$page." HTTP/1.0\r\n";
		}
		$strRequest .= "User-Agent: BitrixControllerMember\r\n";
		$strRequest .= "Accept: */*\r\n";
		$strRequest .= "Host: ".$server_name."\r\n";
		$strRequest .= "Accept-Language: en\r\n";
		$strRequest .= "Content-type: application/x-www-form-urlencoded\r\n";
		$strRequest .= "Content-length: ".strlen($strVars)."\r\n\r\n";
		$strRequest .= $strVars."\r\n";

		if ($this->isDebugEnabled())
		{
			$this->Debug(array(
				__FILE__.":".__LINE__,
				"We send request to the $server_name:$server_port from member#".$this->member_id,
				"strVars" => strVars,
				"Packet" => $this,
			));
		}

		fputs($conn, $strRequest);

		$header = '';
		while (($line = fgets($conn, 4096)) && $line!="\r\n")
		{
			$header.=$line;
		}

		$result = '';
		while ($line = fread($conn, 4096))
		{
			$result .= $line;
		}

		fclose($conn);

		$packet_result = new __CControllerPacketResponse();
		$packet_result->secret_id = $this->secret_id;
		$packet_result->ParseResult($result);

		if ($this->isDebugEnabled())
		{
			$this->Debug(array(
				__FILE__.":".__LINE__,
				"We get response from $server_name:$server_port to member#".$packet_result->member_id,
				"secret_id" => $this->secret_id,
				"result" => $result,
				"Packet security check" => ($packet_result->Check()? "passed": "failed"),
				"Packet" => $packet_result,
			));
		}

		return $packet_result;
	}
}

/////////////////////////////////////////////////////////
// Базовый класс для классов типа Response:
//
// Для использования на контроллере:
// CControllerServerResponseFrom - Класс для получения ответа клиента на сервере
// CControllerServerResponseTo - Класс для отправки результатов выполнения запроса назад на клиента
//
// Для использования на клиенте:
// CControllerClientResponseFrom - Класс для получения ответа контроллера на клиенте
// CControllerClientResponseTo - Класс для отправки результатов выполнения запроса назад на контроллер
//////////////////////////////////////////////////////////
class __CControllerPacketResponse extends __CControllerPacket
{
	var $status, $text;

	public function _InitFromRequest($oPacket, $arExclude = array('operation', 'arParameters'))
	{
		if (is_object($oPacket))
		{
			$vars = get_object_vars($oPacket);
			foreach ($vars as $name=>$value)
			{
				if (!in_array($name, $arExclude))
				{
					$this->$name = $value;
				}
			}
		}
	}

	//////////////////////////////////////////////
	// Методы для работы в классах принимающих результат (CControllerServerResponseFrom, CControllerClientResponseFrom):
	//////////////////////////////////////////////

	/**
	 * Checks packet consistency.
	 *
	 * @return boolean
	 * @see __CControllerPacket::Check
	 */
	public function Check()
	{
		return parent::Check($this->status, $this->text, $this->strParameters, $this->secret_id);
	}

	/**
	 * Sets $hash member with calculated value.
	 *
	 * @return void
	 */
	public function Sign()
	{
		parent::Sign($this->status, $this->text, serialize($this->arParameters), $this->secret_id);
	}

	// Возвращает успешно ли выполнился запрос по статус его ответа
	public function OK()
	{
		return (substr($this->status, 0, 1)=="2");
	}

	// Разбирает строку ответа по полям объекта
	public function ParseResult($result)
	{
		global $APPLICATION;

		$ar_result = array();
		$pairs = explode('&', trim($result, " \n\r\t"));
		foreach ($pairs as $pair)
		{
			list($name, $value) = explode('=', $pair, 2);
			$ar_result[$name] = $value;
		}

		$this->session_id = urldecode($ar_result['session_id']);
		$this->member_id = urldecode($ar_result['member_id']);
		$this->hash = urldecode($ar_result['hash']);
		$this->status = urldecode($ar_result['status']);
		$this->text = urldecode($ar_result['text']);

		if (isset($ar_result['encoding']))
			$this->encoding = urldecode($ar_result['encoding']);

		$this->strParameters = base64_decode(urldecode($ar_result['parameters']));
		$arParameters = $this->unpackParameters($this->strParameters, isset($_REQUEST['encoding'])? $_REQUEST['encoding']: '');
		if ($arParameters)
		{
			$this->arParameters = $arParameters;
			if (isset($ar_result['encoding']))
			{
				if($this->text && is_object($APPLICATION))
					$this->text = $APPLICATION->ConvertCharset($this->text, $this->encoding, SITE_CHARSET);
			}
		}

		$this->version = $ar_result['version'];

		if (
			strlen($this->status) <= 0
			&& strlen($this->text) <= 0
			&& strlen($this->member_id) <= 0
		)
		{
			$this->status = "479";
			$this->text = GetMessage("MAIN_CMEMBER_ERR7")." ".substr($result, 0, 1000);
		}
	}


	///////////////////////////////////////
	// Базовые методы для использования в классах отправляющих результат (CControllerServerResponseTo, CControllerClientResponseTo):
	///////////////////////////////////////

	// возвращает отформатированную строку ответа в формате понятном для приема на сервере, с подписью
	public function GetResponseBody($log = false)
	{
		$result = "status=".urlencode($this->status).
			"&text=".urlencode($this->text).
			"&version=".urlencode($this->version).
			"&session_id=".urlencode($this->session_id).
			"&member_id=".urlencode($this->member_id).
			"&encoding=".urlencode(SITE_CHARSET);

		$result .= "&parameters=".urlencode(base64_encode(serialize($this->arParameters)));

		$this->Sign();
		$result .= "&hash=".urlencode($this->hash);

		if($log && $this->isDebugEnabled())
		{
			$this->Debug(array(
				__FILE__.":".__LINE__,
				"We send errored response back",
				"result" => $result,
				"Packet" => $this,
			));
		}

		return $result;
	}

	// отправляет ответ обратно
	public function Send()
	{
		if ($this->isDebugEnabled())
		{
			$this->Debug(array(
				__FILE__.":".__LINE__,
				"We send response back",
				"response body" => $this->GetResponseBody(),
				"Packet" => $this,
			));
		}

		while (@ob_end_flush());

		echo $this->GetResponseBody();
	}
}

// Класс для отправки запроса на сервер
class CControllerClientRequestTo extends __CControllerPacketRequest
{
	var $debug_const = "CONTROLLER_CLIENT_DEBUG";
	var $debug_file_const = "CONTROLLER_CLIENT_LOG_DIR";

	public function __construct($operation, $arParameters = Array())
	{
		$this->member_id = COption::GetOptionString("main", "controller_member_id", "");
		$this->secret_id = COption::GetOptionString("main", "controller_member_secret_id", "");
		$this->operation = $operation;
		$this->arParameters = $arParameters;
		$this->session_id = \Bitrix\Main\Security\Random::getString(32);
	}

	public function SendWithCheck($page="/bitrix/admin/controller_ws.php")
	{
		$oResponse = $this->Send("", $page);
		if($oResponse===false)
			return false;

		if(!$oResponse->Check())
		{
			if(is_object($GLOBALS["APPLICATION"]))
			{
				$e = new CApplicationException(GetMessage("MAIN_CMEMBER_ERR6"));
				$GLOBALS["APPLICATION"]->ThrowException($e);
			}
			return false;
		}

		return $oResponse;
	}

	public function Send($url = "", $page = "/bitrix/admin/controller_ws.php")
	{
		$this->Sign();
		$oResponsePacket = parent::Send(COption::GetOptionString("main", "controller_url", ""), $page);
		if($oResponsePacket === false)
			return false;

		$oResponse = new CControllerClientResponseFrom($oResponsePacket);
		return $oResponse;
	}
}


// Класс получения результата на клиенте (от контроллера)
class CControllerClientResponseFrom extends __CControllerPacketResponse
{
	var $debug_const = "CONTROLLER_CLIENT_DEBUG";
	var $debug_file_const = "CONTROLLER_CLIENT_LOG_DIR";

	public function __construct($oPacket)
	{
		$this->_InitFromRequest($oPacket, array());
	}
}

class CControllerClientRequestFrom extends __CControllerPacketRequest
{
	var $debug_const = "CONTROLLER_CLIENT_DEBUG";
	var $debug_file_const = "CONTROLLER_CLIENT_LOG_DIR";

	public function __construct()
	{
		$this->InitFromRequest();
		$this->Debug(array(
			"Request received from controller",
			"check" => ($this->Check()? 'checked': 'check failed'),
			"Packet" => $this,
		));
	}

	public function Check()
	{
		$member_id = COption::GetOptionString("main", "controller_member_id", "");
		if(strlen($member_id)<=0 || $member_id != $this->member_id)
		{
			$e = new CApplicationException("Bad member_id: ((get)".$member_id."!=(own)".$this->member_id.")");
			$GLOBALS["APPLICATION"]->ThrowException($e);

			return false;
		}

		$member_secret_id = COption::GetOptionString("main", "controller_member_secret_id", "");
		$this->secret_id = $member_secret_id;

		return parent::Check();
	}
}


// Класс для отсылки ответа на сервер
class CControllerClientResponseTo extends __CControllerPacketResponse
{
	var $debug_const = "CONTROLLER_CLIENT_DEBUG";
	var $debug_file_const = "CONTROLLER_CLIENT_LOG_DIR";

	public function __construct($oPacket = false)
	{
		$this->_InitFromRequest($oPacket);
	}
}

class CControllerTools
{
	public static function PackFileArchive($path)
	{
		include_once(dirname(__FILE__) . '/tar_gz.php');

		if (file_exists($path))
		{
			$path = realpath($path);

			$arcname = CTempFile::GetFileName(\Bitrix\Main\Security\Random::getString(32).'.tar.gz');
			CheckDirPath($arcname);
			$ob = new CArchiver($arcname);

			$rem_path = dirname($path);

			if ($ob->Add(array($path), false, $rem_path))
			{
				return $arcname;
			}
		}

		return false;
	}

	public static function UnpackFileArchive($strfile, $path_to)
	{
		global $APPLICATION;
		$res = true;

		$arcname = CTempFile::GetFileName(\Bitrix\Main\Security\Random::getString(32).'.tar.gz');
		CheckDirPath($arcname);

		if(file_put_contents($arcname, $strfile) !== false)
		{
			include_once(dirname(__FILE__) . '/tar_gz.php');
			$ob = new CArchiver($arcname);

			CheckDirPath($_SERVER['DOCUMENT_ROOT'].$path_to);
			$res = $ob->extractFiles($_SERVER['DOCUMENT_ROOT'].$path_to);

			if(!$res && is_object($APPLICATION))
			{
				$arErrors = $ob->GetErrors();
				if(count($arErrors))
				{
					$strError = "";
					foreach($arErrors as $error)
						$strError .= $error[1]."<br>";

					$e = new CApplicationException($strError);
					$APPLICATION->ThrowException($e);
				}
			}
		}

		return $res;
	}
}
