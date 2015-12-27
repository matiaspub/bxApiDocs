<?php

IncludeModuleLangFile(__FILE__);


class CLdapServer
{
	var $arFields;
	/**
	 *
	 * @param $arOrder
	 * @param $arFilter
	 * @return __CLDAPServerDBResult
	 */
	public static function GetList($arOrder=Array(), $arFilter=Array())
	{
		global $USER, $DB, $APPLICATION;
		$strSql =
				"SELECT ls.*, ".
				"	".$DB->DateToCharFunction("ls.TIMESTAMP_X")."	as TIMESTAMP_X, ".
				"	".$DB->DateToCharFunction("ls.SYNC_LAST")."	as SYNC_LAST ".
				"FROM b_ldap_server ls ";

		if(!is_array($arFilter))
			$arFilter = Array();

		$arSqlSearch = Array();
		$filter_keys = array_keys($arFilter);
		$fkCount = count($filter_keys);

		for($i=0; $i<$fkCount; $i++)
		{
			$val = $arFilter[$filter_keys[$i]];
			$key=$filter_keys[$i];
			$res = CLdapUtil::MkOperationFilter($key);
			$key = strtoupper($res["FIELD"]);
			$cOperationType = $res["OPERATION"];
			switch($key)
			{
				case "ACTIVE":
				case "SYNC":
				case "CONVERT_UTF8":
				case "USER_GROUP_ACCESSORY":
					$arSqlSearch[] = CLdapUtil::FilterCreate("ls.".$key, $val, "string_equal", $cOperationType);
					break;
				case "ID":
				case "PORT":
				case "MAX_PAX_SIZE":
					$arSqlSearch[] = CLdapUtil::FilterCreate("ls.".$key, $val, "number", $cOperationType);
					break;
				case "TIMESTAMP_X":
					$arSqlSearch[] = CLdapUtil::FilterCreate("ls.".$key, $val, "date", $cOperationType);
					break;
				case "SYNC_LAST":
					$arSqlSearch[] = CLdapUtil::FilterCreate("ls.".$key, $val, "date", $cOperationType);
					break;
				case "CODE":
				case "NAME":
				case "DESCRIPTION":
				case "SERVER":
				case "ADMIN_LOGIN":
				case "ADMIN_PASSWORD":
				case "BASE_DN":
				case "GROUP_FILTER":
				case "GROUP_ID_ATTR":
				case "GROUP_NAME_ATTR":
				case "GROUP_MEMBERS_ATTR":
				case "USER_FILTER":
				case "USER_ID_ATTR":
				case "USER_NAME_ATTR":
				case "USER_LAST_NAME_ATTR":
				case "USER_EMAIL_ATTR":
				case "USER_GROUP_ATTR":
					$arSqlSearch[] = CldapUtil::FilterCreate("ls.".$key, $val, "string", $cOperationType);
					break;
			}
		}

		$is_filtered = false;
		$strSqlSearch = "";

		for($i=0, $ssCount=count($arSqlSearch); $i<$ssCount; $i++)
		{
			if(strlen($arSqlSearch[$i])>0)
			{
				$is_filtered = true;
				$strSqlSearch .= " AND  (".$arSqlSearch[$i].") ";
			}
		}

		$arSqlOrder = Array();
		foreach($arOrder as $by=>$order)
		{
			$order = strtolower($order);
			if ($order!="asc")
				$order = "desc".($DB->type=="ORACLE"?" NULLS LAST":"");
			else
				$order = "asc".($DB->type=="ORACLE"?" NULLS FIRST":"");

			switch(strtoupper($by))
			{
				case "ID":
				case "NAME":
				case "CODE":
				case "ACTIVE":
				case "CONVERT_UTF8":
				case "SERVER":
				case "PORT":
				case "ADMIN_LOGIN":
				case "ADMIN_PASSWORD":
				case "BASE_DN":
				case "GROUP_FILTER":
				case "SYNC":
				case "SYNC_LAST":
				case "GROUP_ID_ATTR":
				case "GROUP_NAME_ATTR":
				case "GROUP_MEMBERS_ATTR":
				case "USER_FILTER":
				case "USER_ID_ATTR":
				case "USER_NAME_ATTR":
				case "USER_LAST_NAME_ATTR":
				case "USER_EMAIL_ATTR":
				case "USER_GROUP_ATTR":
				case "USER_GROUP_ACCESSORY":
				case "MAX_PAX_SIZE":
					$arSqlOrder[] = " ls.".$by." ".$order." ";
					break;
				default:
					$arSqlOrder[] = " ls.TIMESTAMP_X ".$order." ";
			}
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder); for ($i=0; $i<count($arSqlOrder); $i++)
		{
			if($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= strtolower($arSqlOrder[$i]);
		}

		$strSql .= " WHERE 1=1 ".$strSqlSearch.$strSqlOrder;

		$res = $DB->Query($strSql);
		$res = new __CLDAPServerDBResult($res);
		return $res;
	}

	/**
	 *
	 * @param $ID
	 * @return __CLDAPServerDBResult
	 */
	public static function GetByID($ID)
	{
		return CLdapServer::GetList(Array(), $arFilter=Array("ID"=>IntVal($ID)));
	}

	public static function CheckFields($arFields, $ID=false)
	{
		global $DB, $APPLICATION;

		$strErrors = "";
		$arMsg = Array();

		if(($ID===false || is_set($arFields, "NAME")) && strlen($arFields["NAME"])<1)
			$arMsg[] = array("id"=>"NAME", "text"=> GetMessage("LDAP_ERR_EMPTY")." ".GetMessage("LDAP_ERR_NAME"));
			//$strErrors .= GetMessage("LDAP_ERR_NAME").", ";

		if(($ID===false || is_set($arFields, "SERVER")) && strlen($arFields["SERVER"])<1)
			$arMsg[] = array("id"=>"SERVER", "text"=> GetMessage("LDAP_ERR_EMPTY")." ".GetMessage("LDAP_ERR_SERVER"));
			//$strErrors .= GetMessage("LDAP_ERR_SERVER").", ";

		if(($ID===false || is_set($arFields, "PORT")) && strlen($arFields["PORT"])<1)
			$arMsg[] = array("id"=>"PORT", "text"=> GetMessage("LDAP_ERR_EMPTY")." ".GetMessage("LDAP_ERR_PORT"));
			//$strErrors .= GetMessage("LDAP_ERR_PORT").", ";

		if(($ID===false || is_set($arFields, "BASE_DN")) && strlen($arFields["BASE_DN"])<1)
			//$strErrors .= GetMessage("LDAP_ERR_BASE_DN").", ";
			$arMsg[] = array("id"=>"BASE_DN", "text"=> GetMessage("LDAP_ERR_EMPTY")." ".GetMessage("LDAP_ERR_BASE_DN"));

		if(($ID===false || is_set($arFields, "GROUP_FILTER")) && strlen($arFields["GROUP_FILTER"])<1)
			//$strErrors .= GetMessage("LDAP_ERR_GROUP_FILT").", ";
			$arMsg[] = array("id"=>"GROUP_FILTER", "text"=> GetMessage("LDAP_ERR_EMPTY")." ".GetMessage("LDAP_ERR_GROUP_FILT"));

		if(($ID===false || is_set($arFields, "GROUP_ID_ATTR")) && strlen($arFields["GROUP_ID_ATTR"])<1)
			$arMsg[] = array("id"=>"GROUP_ID_ATTR", "text"=> GetMessage("LDAP_ERR_EMPTY")." ".GetMessage("LDAP_ERR_GROUP_ATTR"));
			//$strErrors .= GetMessage("LDAP_ERR_GROUP_ATTR").", ";

		if(($ID===false || is_set($arFields, "USER_FILTER")) && strlen($arFields["USER_FILTER"])<1)
			$arMsg[] = array("id"=>"USER_FILTER", "text"=> GetMessage("LDAP_ERR_EMPTY")." ".GetMessage("LDAP_ERR_USER_FILT"));
			//$strErrors .= GetMessage("LDAP_ERR_USER_FILT").", ";

		if(($ID===false || is_set($arFields, "USER_ID_ATTR")) && strlen($arFields["USER_ID_ATTR"])<1)
			$arMsg[] = array("id"=>"USER_ID_ATTR", "text"=> GetMessage("LDAP_ERR_EMPTY")." ".GetMessage("LDAP_ERR_USER_ATTR"));
			//$strErrors .= GetMessage("LDAP_ERR_USER_ATTR").", ";

		//if(strlen($strErrors)>0)
		//{
		//	$APPLICATION->throwException(GetMessage("LDAP_ERR_EMPTY").substr($strErrors, 0, -2));
		//	return false;
		//}

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		return true;
	}

	public static function Add($arFields)
	{
		global $DB, $APPLICATION;
		$APPLICATION->ResetException();

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";

		if(is_set($arFields, "SYNC") && $arFields["SYNC"]!="Y")
			$arFields["SYNC"]="N";

		if(is_set($arFields, "CONVERT_UTF8") && $arFields["CONVERT_UTF8"]!="Y")
			$arFields["CONVERT_UTF8"]="N";

		if(is_set($arFields, "USER_GROUP_ACCESSORY") && $arFields["USER_GROUP_ACCESSORY"]!="Y")
			$arFields["USER_GROUP_ACCESSORY"]="N";

		if(!CLdapServer::CheckFields($arFields))
			return false;

		if(is_set($arFields, "ADMIN_PASSWORD"))
			$arFields["ADMIN_PASSWORD"]=CLdapUtil::Crypt($arFields["ADMIN_PASSWORD"]);

		if(is_set($arFields, "FIELD_MAP") && is_array($arFields["FIELD_MAP"]))
		{
			$arFields["USER_NAME_ATTR"] = "".$arFields["FIELD_MAP"]["NAME"];
			$arFields["USER_LAST_NAME_ATTR"] = "".$arFields["FIELD_MAP"]["LAST_NAME"];
			$arFields["USER_EMAIL_ATTR"] = "".$arFields["FIELD_MAP"]["EMAIL"];

			$arFields["FIELD_MAP"] = serialize($arFields["FIELD_MAP"]);
		}

		$ID = CDatabase::Add("b_ldap_server", $arFields);

		if(is_set($arFields, 'GROUPS'))
			CLdapServer::SetGroupMap($ID, $arFields['GROUPS']);

		if($arFields["SYNC"]=="Y")
			CLdapServer::__UpdateAgentPeriod($ID, $arFields["SYNC_PERIOD"]);

		return $ID;
	}

	public static function __UpdateAgentPeriod($server_id, $time)
	{
		$server_id = IntVal($server_id);
		$time = IntVal($time);

		CAgent::RemoveAgent("CLdapServer::SyncAgent(".$server_id.");", "ldap");
		if($time>0)
			CAgent::AddAgent("CLdapServer::SyncAgent(".$server_id.");", "ldap", "N", $time*60*60);
	}

	public static function SyncAgent($id)
	{
		CLdapServer::Sync($id);
		return "CLdapServer::SyncAgent(".$id.");";
	}

	/*********************************************************************
	*********************************************************************/
	public static function Update($ID, $arFields)
	{
		global $DB, $APPLICATION;
		$APPLICATION->ResetException();

		$ID = IntVal($ID);

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";

		if(is_set($arFields, "SYNC") && $arFields["SYNC"]!="Y")
			$arFields["SYNC"]="N";

		if(is_set($arFields, "SYNC_USER_ADD") && $arFields["SYNC_USER_ADD"] != "Y")
			$arFields["SYNC_USER_ADD"] = "N";

		if(is_set($arFields, "CONVERT_UTF8") && $arFields["CONVERT_UTF8"]!="Y")
			$arFields["CONVERT_UTF8"]="N";

		if(is_set($arFields, "USER_GROUP_ACCESSORY") && $arFields["USER_GROUP_ACCESSORY"]!="Y")
			$arFields["USER_GROUP_ACCESSORY"]="N";

		if(is_set($arFields, "IMPORT_STRUCT") && $arFields["IMPORT_STRUCT"]!="Y")
			$arFields["IMPORT_STRUCT"]="N";

		if(is_set($arFields, "STRUCT_HAVE_DEFAULT") && $arFields["STRUCT_HAVE_DEFAULT"]!="Y")
			$arFields["STRUCT_HAVE_DEFAULT"]="N";

		if(!CLdapServer::CheckFields($arFields, $ID))
			return false;

		if(is_set($arFields, "ADMIN_PASSWORD"))
			$arFields["ADMIN_PASSWORD"]=CLdapUtil::Crypt($arFields["ADMIN_PASSWORD"]);

		if(is_set($arFields, "FIELD_MAP") && is_array($arFields["FIELD_MAP"]))
		{
			$arFields["USER_NAME_ATTR"] = "".$arFields["FIELD_MAP"]["NAME"];
			$arFields["USER_LAST_NAME_ATTR"] = "".$arFields["FIELD_MAP"]["LAST_NAME"];
			$arFields["USER_EMAIL_ATTR"] = "".$arFields["FIELD_MAP"]["EMAIL"];

			$arFields["FIELD_MAP"] = serialize($arFields["FIELD_MAP"]);
		}

		if(isset($arFields["SYNC"]) || isset($arFields["SYNC_PERIOD"]))
		{
			$dbld = CLdapServer::GetById($ID);
			$arLdap = $dbld->Fetch();
		}

		$strUpdate = $DB->PrepareUpdate("b_ldap_server", $arFields);

		$strSql =
			"UPDATE b_ldap_server SET ".
				$strUpdate." ".
			"WHERE ID=".$ID;

		$DB->Query($strSql);

		if(is_set($arFields, 'GROUPS'))
			CLdapServer::SetGroupMap($ID, $arFields['GROUPS']);

		if(isset($arFields["SYNC"]) || isset($arFields["SYNC_PERIOD"]))
		{
			if($arLdap)
			{
				if(isset($arFields["SYNC"]))
				{
					if($arFields["SYNC"]!="Y" && $arLdap["SYNC"]=="Y")
						CLdapServer::__UpdateAgentPeriod($ID, 0);
					elseif($arFields["SYNC"]=="Y" && $arLdap["SYNC"]!="Y")
						CLdapServer::__UpdateAgentPeriod($ID, (isset($arFields["SYNC_PERIOD"])? $arFields["SYNC_PERIOD"] : $arLdap["SYNC_PERIOD"]));
					elseif(isset($arFields["SYNC_PERIOD"]) && $arLdap["SYNC_PERIOD"]!=$arFields["SYNC_PERIOD"])
						CLdapServer::__UpdateAgentPeriod($ID, $arFields["SYNC_PERIOD"]);
				}
				elseif($arLdap["SYNC_PERIOD"]!=$arFields["SYNC_PERIOD"])
					CLdapServer::__UpdateAgentPeriod($ID, $arFields["SYNC_PERIOD"]);
			}
		}

		return true;
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);

		$strSql = "DELETE FROM b_ldap_group WHERE LDAP_SERVER_ID=".$ID;
		if(!$DB->Query($strSql, true))
			return false;

		$strSql = "DELETE FROM b_ldap_server WHERE ID=".$ID;
		return $DB->Query($strSql, true);
	}

	public static function GetGroupMap($ID)
	{
		global $DB, $APPLICATION;
		$ID = IntVal($ID);
		return $DB->Query("SELECT GROUP_ID, LDAP_GROUP_ID FROM b_ldap_group WHERE LDAP_SERVER_ID=".$ID." AND NOT (GROUP_ID=-1)");
	}

	public static function GetGroupBan($ID)
	{
		global $DB, $APPLICATION;
		$ID = IntVal($ID);
		return $DB->Query("SELECT LDAP_GROUP_ID FROM b_ldap_group WHERE LDAP_SERVER_ID=".$ID." AND GROUP_ID=-1");
	}


	public static function SetGroupMap($ID, $arFields)
	{
		global $DB, $APPLICATION;
		$ID = IntVal($ID);
		$DB->Query("DELETE FROM b_ldap_group WHERE LDAP_SERVER_ID=".$ID);
		foreach($arFields as $arGroup)
		{
			// check whether entry is valid, and if it is - add it
			if(array_key_exists('GROUP_ID',$arGroup) && ($arGroup['GROUP_ID']>0 || $arGroup['GROUP_ID']==-1) && strlen($arGroup['LDAP_GROUP_ID'])>0)
			{
				$strSql =
					"SELECT 'x' ".
					"FROM b_ldap_group ".
					"WHERE LDAP_SERVER_ID=".$ID." ".
					"	AND GROUP_ID = ".IntVal($arGroup['GROUP_ID'])." ".
					"	AND LDAP_GROUP_ID = '".$DB->ForSQL($arGroup['LDAP_GROUP_ID'], 255)."' ";
				$r = $DB->Query($strSql);
				if(!$r->Fetch())
				{
					$strSql =
						"INSERT INTO b_ldap_group(GROUP_ID, LDAP_GROUP_ID, LDAP_SERVER_ID)".
						"VALUES(".IntVal($arGroup['GROUP_ID']).", '".$DB->ForSQL($arGroup['LDAP_GROUP_ID'], 255)."', ".$ID.")";
					$DB->Query($strSql);
				}
			}
		}
	}


	public static function Sync($ldap_server_id)
	{
		global $DB, $USER, $APPLICATION;

		if(!is_object($USER))
		{
			$USER = new CUser();
			$bUSERGen = true;
		}

		$dbLdapServers = CLdapServer::GetById($ldap_server_id);
		if(!($oLdapServer = $dbLdapServers->GetNextServer()))
			return false;

		if(!$oLdapServer->Connect())
			return false;

		if(!$oLdapServer->BindAdmin())
		{
			$oLdapServer->Disconnect();
			return false;
		}

		$APPLICATION->ResetException();
		$db_events = GetModuleEvents("ldap", "OnLdapBeforeSync");
		while($arEvent = $db_events->Fetch())
		{
			$arParams['oLdapServer'] = $oLdapServer;
			if(ExecuteModuleEventEx($arEvent, array(&$arParams))===false)
			{
				if(!($err = $APPLICATION->GetException()))
					$APPLICATION->ThrowException("Unknown error");
				return false;
			}
		}

		// select all users from LDAP
		$arLdapUsers = array();
		$ldapLoginAttr = strtolower($oLdapServer->arFields["~USER_ID_ATTR"]);

		$APPLICATION->ResetException();
		$dbLdapUsers = $oLdapServer->GetUserList();
		$ldpEx = $APPLICATION->GetException();

		while($arLdapUser = $dbLdapUsers->Fetch())
			$arLdapUsers[strtolower($arLdapUser[$ldapLoginAttr])] = $arLdapUser;
		unset($dbLdapUsers);

		// select all Bitrix CMS users for this LDAP
		$arUsers = Array();

		CTimeZone::Disable();
		$dbUsers = CUser::GetList($o, $b, Array("EXTERNAL_AUTH_ID"=>"LDAP#".$ldap_server_id));
		CTimeZone::Enable();

		while($arUser = $dbUsers->Fetch())
			$arUsers[strtolower($arUser["LOGIN"])] = $arUser;
		unset($dbUsers);

		if(!$ldpEx || $ldpEx->msg != 'LDAP_SEARCH_ERROR')
			$arDelLdapUsers = array_diff(array_keys($arUsers), array_keys($arLdapUsers));

		if(strlen($oLdapServer->arFields["SYNC_LAST"])>0)
			$syncTime = MakeTimeStamp($oLdapServer->arFields["SYNC_LAST"]);
		else
			$syncTime = 0;

		$arCache = array();

		// selecting a list of groups, from which users will not be imported
		$noImportGroups = array();

		$dbGroups = CLdapServer::GetGroupBan($ldap_server_id);
		while($arGroup = $dbGroups->Fetch())
			$noImportGroups[md5($arGroup['LDAP_GROUP_ID'])] = $arGroup['LDAP_GROUP_ID'];

		$cnt = 0;
		// have to update $oLdapServer->arFields["FIELD_MAP"] for user fields
		// for each one of them looking for similar in user list
		foreach($arLdapUsers as $userLogin => $arLdapUserFields)
		{
			if(!is_array($arUsers[$userLogin]))
			{
				if($oLdapServer->arFields["SYNC_USER_ADD"] != "Y")
					continue;

				// if user is not found among already existing ones, then import him
				// в $arLdapUserFields - user fields from ldap
				$userActive = $oLdapServer->getLdapValueByBitrixFieldName("ACTIVE", $arLdapUserFields);

				if($userActive != "Y")
					continue;

				$arUserFields = $oLdapServer->GetUserFields($arLdapUserFields, $departmentCache);

				// $arUserFields here contains LDAP user fields for a LDAP user
				// make a check, whether this user belongs to those groups only, from which import will not be made...
				$allUserGroups = $arUserFields['LDAP_GROUPS'];

				$userImportIsBanned = true;
				foreach ($allUserGroups as $groupId)
				{
					$groupId = trim($groupId);
					if (!empty($groupId) && !array_key_exists(md5($groupId), $noImportGroups))
					{
						$userImportIsBanned = false;
						break;
					}
				}

				// ...if he does not, then import him
				if (!$userImportIsBanned || empty($allUserGroups))
					$oLdapServer->SetUser($arUserFields);
			}
			else
			{
				// if date of update is set, then compare it
				$ldapTime = time();
				if($syncTime>0
					&& strlen($oLdapServer->arFields["SYNC_ATTR"])>0
					&& preg_match("'([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})\.0Z'", $arLdapUserFields[strtolower($oLdapServer->arFields["SYNC_ATTR"])], $arTimeMatch)
					)
				{
					$ldapTime = gmmktime($arTimeMatch[4], $arTimeMatch[5], $arTimeMatch[6], $arTimeMatch[2], $arTimeMatch[3], $arTimeMatch[1]);
					$userTime = MakeTimeStamp($arUsers[$userLogin]["TIMESTAMP_X"]);
				}

				if($syncTime<$ldapTime || $syncTime<$userTime)
				{
					// make an update
					$arUserFields = $oLdapServer->GetUserFields($arLdapUserFields,$arCache);
					$arUserFields["ID"] = $arUsers[$userLogin]["ID"];

					//echo $arUserFields["LOGIN"]." - updated<br>";
					$oLdapServer->SetUser($arUserFields);
					$cnt++;
				}
			}
		}

		foreach ($arDelLdapUsers as $userLogin)
		{
			$USER = new CUser();
			if (isset($arUsers[$userLogin]) && $arUsers[$userLogin]['ACTIVE'] == 'Y')
			{
				$ID = intval($arUsers[$userLogin]["ID"]);
				$USER->Update($ID, array('ACTIVE' => 'N'));
			}
		}

		$oLdapServer->Disconnect();
		CLdapServer::Update($ldap_server_id, Array("~SYNC_LAST"=>$DB->CurrentTimeFunction()));

		if($bUSERGen)
			unset($USER);

		return $cnt;
	}
}

class __CLDAPServerDBResult extends CDBResult
{
public static 	function Fetch()
	{
		if($res = parent::Fetch())
		{
			$res["ADMIN_PASSWORD"] = CLdapUtil::Decrypt($res["ADMIN_PASSWORD"]);
			$res["FIELD_MAP"] = unserialize($res["FIELD_MAP"]);
			if(!is_array($res["FIELD_MAP"]))
				$res["FIELD_MAP"] = Array();
		}

		return $res;
	}

public 	function GetNextServer()
	{
		if(!($r = $this->GetNext()))
			return $r;
		$ldap = new CLDAP();
		$ldap->arFields = $r;
		return $ldap;
	}
}


?>