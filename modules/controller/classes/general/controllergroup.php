<?
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/admin_tools.php");
IncludeModuleLangFile(__FILE__);

class CControllerGroup
{
	public static function CheckDefaultUpdate()
	{
		$dbr_groups = CControllerGroup::GetList(Array(), Array("<UPDATE_PERIOD"=>0));
		while($ar_group = $dbr_groups->Fetch())
			CControllerGroup::SetGroupSettings($ar_group["ID"]);

		return "CControllerGroup::CheckDefaultUpdate();";
	}

	public static function CheckFields(&$arFields, $ID = false)
	{
		$arErrMsg = Array();

		if($ID>0)
			unset($arFields["ID"]);

		global $DB;
		if(($ID===false || is_set($arFields, "NAME")) && strlen($arFields["NAME"])<=0)
			$arMsg[] = array("id"=>"NAME", "text"=> GetMessage("CTRLR_GRP_ERR_NAME"));

		if(isset($arFields["UPDATE_PERIOD"]) && ($arFields["UPDATE_PERIOD"]<0 || trim($arFields["UPDATE_PERIOD"])==''))
			$arFields["UPDATE_PERIOD"] = -1;

		if(count($arMsg)>0)
		{
			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		global $USER;
		if(!is_set($arFields, "MODIFIED_BY") && is_object($USER))
			$arFields["MODIFIED_BY"] = $USER->GetID();
		if($ID===false && !is_set($arFields, "CREATED_BY") && is_object($USER))
			$arFields["CREATED_BY"] = $USER->GetID();
		if($ID===false && !is_set($arFields, "DATE_CREATE"))
			$arFields["~DATE_CREATE"] = $DB->CurrentTimeFunction();

		return true;
	}

	public static function Add($arFields)
	{
		global $DB;

		if(!CControllerGroup::CheckFields($arFields))
			return false;

		unset($arFields["TIMESTAMP_X"]);
		unset($arFields["~TIMESTAMP_X"]);

		$ID = $DB->Add("b_controller_group", $arFields, array("DESCRIPTION", "INSTALL_INFO", "UNINSTALL_INFO", "INSTALL_PHP", "UNINSTALL_PHP"));

		if(isset($arFields["UPDATE_PERIOD"]))
			CControllerGroup::__UpdateAgentPeriod($ID, $arFields["UPDATE_PERIOD"]);

		if(isset($arFields["COUNTER_UPDATE_PERIOD"]))
			CControllerGroup::__CounterUpdateAgentPeriod($ID, $arFields["COUNTER_UPDATE_PERIOD"]);

		return $ID;

	}

	public static function Update($ID, $arFields)
	{
		global $DB;
		if(!CControllerGroup::CheckFields($arFields, $ID))
			return false;

		if(isset($arFields["UPDATE_PERIOD"]) || isset($arFields["COUNTER_UPDATE_PERIOD"]))
		{
			$dbr_group = CControllerGroup::GetById($ID);
			$ar_group = $dbr_group->Fetch();
			if(isset($arFields["UPDATE_PERIOD"]) && $ar_group["UPDATE_PERIOD"]!=$arFields["UPDATE_PERIOD"])
				CControllerGroup::__UpdateAgentPeriod($ID, $arFields["UPDATE_PERIOD"]);
			if(isset($arFields["COUNTER_UPDATE_PERIOD"]) && $ar_group["COUNTER_UPDATE_PERIOD"]!=$arFields["COUNTER_UPDATE_PERIOD"])
				CControllerGroup::__CounterUpdateAgentPeriod($ID, $arFields["COUNTER_UPDATE_PERIOD"]);
		}

		unset($arFields["TIMESTAMP_X"]);
		$arFields["~TIMESTAMP_X"] = $DB->CurrentTimeFunction();

		$arUpdateBinds = array();
		$strUpdate = $DB->PrepareUpdateBind("b_controller_group", $arFields, "", false, $arUpdateBinds);

		$strSql = "UPDATE b_controller_group SET ".$strUpdate." WHERE ID=".intval($ID);

		$arBinds = array();
		foreach($arUpdateBinds as $field_id)
			$arBinds[$field_id] = $arFields[$field_id];

		if(!$DB->QueryBind($strSql, $arBinds))
			return false;
		else
			return true;
	}

	public static function __UpdateAgentPeriod($group_id, $time)
	{
		$group_id = IntVal($group_id);
		$time = IntVal($time);

		CAgent::RemoveAgent("CControllerGroup::__UpdateSettingsAgent(".$group_id.");", "controller");
		if($time>0)
			CAgent::AddAgent("CControllerGroup::__UpdateSettingsAgent(".$group_id.");", "controller", "N", $time*60);
	}

	public static function __CounterUpdateAgentPeriod($group_id, $time)
	{
		$group_id = IntVal($group_id);
		$time = IntVal($time);

		CAgent::RemoveAgent("CControllerGroup::__UpdateCountersAgent(".$group_id.");", "controller");
		if($time>0)
			CAgent::AddAgent("CControllerGroup::__UpdateCountersAgent(".$group_id.");", "controller", "N", $time*60);
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		if($ID==1)
		{
			$e = new CApplicationException(GetMessage("CTRL_GRP_DEL_ERR_DEF"));
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}
		$dbres = $DB->Query("SELECT COUNT('x') as C FROM b_controller_member WHERE CONTROLLER_GROUP_ID=".$ID);
		$arres = $dbres->Fetch();
		if($arres["C"]>0)
		{
			$e = new CApplicationException(GetMessage("CTRLR_GRP_DEL_ERR"));
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		$DB->Query("DELETE FROM b_controller_counter_group WHERE CONTROLLER_GROUP_ID = ".$ID);
		$DB->Query("DELETE FROM b_controller_group WHERE ID=".$ID);
		return true;
	}

	public static function GetList($arOrder = Array(), $arFilter = Array())
	{
		global $DB;

		static $arFields = array(
			"ID" => Array("FIELD_NAME" => "G.ID", "FIELD_TYPE" => "int"),
			"NAME" => Array("FIELD_NAME" => "G.NAME", "FIELD_TYPE" => "string"),
			"TIMESTAMP_X" => Array("FIELD_NAME" => "G.TIMESTAMP_X", "FIELD_TYPE" => "datetime"),
			"MODIFIED_BY" => Array("FIELD_NAME" => "G.MODIFIED_BY", "FIELD_TYPE" => "int"),
			"UPDATE_PERIOD" => Array("FIELD_NAME" => "G.UPDATE_PERIOD", "FIELD_TYPE" => "int"),
			"MODIFIED_BY_USER" => Array("FIELD_TYPE" => "string"),
			"DATE_CREATE" => Array("FIELD_NAME" => "G.DATE_CREATE", "FIELD_TYPE" => "datetime"),
			"CREATED_BY" => Array("FIELD_NAME" => "G.CREATED_BY", "FIELD_TYPE" => "int"),
			"CREATED_BY_USER" => Array("FIELD_TYPE" => "string"),
			"TRIAL_PERIOD" => Array("FIELD_NAME" => "G.TRIAL_PERIOD", "FIELD_TYPE" => "int"),
			"COUNTER_UPDATE_PERIOD" => Array("FIELD_NAME" => "G.COUNTER_UPDATE_PERIOD", "FIELD_TYPE" => "int"),
			"CHECK_COUNTER_FREE_SPACE" => Array("FIELD_NAME" => "G.CHECK_COUNTER_FREE_SPACE", "FIELD_TYPE" => "string"),
			"CHECK_COUNTER_SITES" => Array("FIELD_NAME" => "G.CHECK_COUNTER_SITES", "FIELD_TYPE" => "string"),
			"CHECK_COUNTER_USERS" => Array("FIELD_NAME" => "G.CHECK_COUNTER_USERS", "FIELD_TYPE" => "string"),
			"CHECK_COUNTER_LAST_AUTH" => Array("FIELD_NAME" => "G.CHECK_COUNTER_LAST_AUTH", "FIELD_TYPE" => "string"),
		);

		$arFields["MODIFIED_BY_USER"]["FIELD_NAME"] = $DB->Concat("UM.LOGIN", "UM.NAME", "UM.LAST_NAME");
		$arFields["CREATED_BY_USER"]["FIELD_NAME"] = $DB->Concat("UC.LOGIN", "UC.NAME", "UC.LAST_NAME");

		$obWhere = new CSQLWhere;
		$obWhere->SetFields($arFields);

		$arFilterNew = Array();
		foreach($arFilter as $k=>$value)
			if(strlen($value)>0 || $value === false)
				$arFilterNew[$k]=$value;

		$strWhere = $obWhere->GetQuery($arFilterNew);

		$strSql =
			"SELECT G.*, ".
			"	UC.LOGIN as CREATED_BY_LOGIN, UC.NAME as CREATED_BY_NAME, UC.LAST_NAME as CREATED_BY_LAST_NAME, ".
			"	UM.LOGIN as MODIFIED_BY_LOGIN, UM.NAME as MODIFIED_BY_NAME, UM.LAST_NAME as MODIFIED_BY_LAST_NAME, ".
			"	".$DB->DateToCharFunction("G.TIMESTAMP_X")."	as TIMESTAMP_X, ".
			"	".$DB->DateToCharFunction("G.DATE_CREATE")."	as DATE_CREATE ".
			"FROM b_controller_group G  ".
			"	LEFT JOIN b_user UC ON UC.ID=G.CREATED_BY ".
			"	LEFT JOIN b_user UM ON UM.ID=G.MODIFIED_BY ".
			(strlen($strWhere)<=0 ? "" : "WHERE ".$strWhere)." ".
			CControllerAgent::_OrderBy($arOrder, $arFields);

		$dbr = $DB->Query($strSql);
		$dbr->is_filtered = (strlen($strWhere)>0);
		return $dbr;
	}

	public static function GetByID($ID)
	{
		return CControllerGroup::GetList(Array(), Array("ID"=>IntVal($ID)));
	}

	public static function GetGroupSettings($group_id)
	{
		$dbr_group = CControllerGroup::GetByID($group_id);
		if($ar_group = $dbr_group->Fetch())
		{
			$arSettings = unserialize($ar_group["INSTALL_INFO"]);
			$strCommand = CControllerGroupSettings::GeneratePHPInstall($arSettings);
			return $strCommand.$ar_group["INSTALL_PHP"];
		}

		return false;
	}

	public static function RunCommand($group_id, $php_script, $arParameters = Array())
	{
		global $DB;
		$group_id = IntVal($group_id);

		if($php_script == 'COUNTERS_UPDATE' || $php_script == 'SET_SETTINGS' || $php_script == 'UPDATE')
		{
			$task_id = $php_script;
			$php_script = "";
		}
		else
		{
			$task_id = 'REMOTE_COMMAND';
		}

		$arUpdateFields = array(
			"~DATE_CREATE" => $DB->CurrentTimeFunction(),
			"INIT_EXECUTE" => (strlen($php_script)? $php_script: false),
			"INIT_EXECUTE_PARAMS" => (count($arParameters)? serialize($arParameters): false),
		);
		$arUpdateBinds = array();
		$strUpdate = "
			UPDATE b_controller_task
			SET ".$DB->PrepareUpdateBind("b_controller_task", $arUpdateFields, "", false, $arUpdateBinds)."
			WHERE
				CONTROLLER_MEMBER_ID = #'MID'#
				AND TASK_ID = '$task_id'
				AND DATE_EXECUTE IS NULL
		";

		$arInsertFields = array(
			"~DATE_CREATE" => $DB->CurrentTimeFunction(),
			"TASK_ID" => $task_id,
			"INIT_EXECUTE" => (strlen($php_script)? $php_script: false),
			"INIT_EXECUTE_PARAMS" => (count($arParameters)? serialize($arParameters): false),
			"DATE_EXECUTE" => false,
		);

		$strSql = "SELECT M.ID FROM b_controller_member M WHERE M.CONTROLLER_GROUP_ID=".$group_id." AND M.ACTIVE = 'Y'";
		$rsMembers = $DB->Query($strSql);
		while($arMember = $rsMembers->Fetch())
		{
			$arBinds = array();
			foreach($arUpdateBinds as $field_id)
				$arBinds[$field_id] = $arUpdateFields[$field_id];
			$rsUpdate = $DB->QueryBind(str_replace("#'MID'#", $arMember["ID"], $strUpdate), $arBinds);

			if($rsUpdate->AffectedRowsCount() <= 0)
			{
				$arInsertFields["CONTROLLER_MEMBER_ID"] = $arMember["ID"];
				$DB->Add("b_controller_task", $arInsertFields, array("INIT_EXECUTE", "INIT_EXECUTE_PARAMS"));
			}
		}
	}

	public static function __UpdateCountersAgent($group_id)
	{
		CControllerGroup::UpdateCounters($group_id);
		return "CControllerGroup::__UpdateCountersAgent(".$group_id.");";
	}

	public static function UpdateCounters($group_id)
	{
		CControllerGroup::RunCommand($group_id, 'COUNTERS_UPDATE');
	}

	public static function __UpdateSettingsAgent($group_id)
	{
		CControllerGroup::SetGroupSettings($group_id);
		return "CControllerGroup::__UpdateSettingsAgent(".$group_id.");";
	}

	public static function SetGroupSettings($group_id)
	{
		CControllerGroup::RunCommand($group_id, 'SET_SETTINGS');
	}

	public static function SiteUpdate($group_id)
	{
		CControllerGroup::RunCommand($group_id, 'UPDATE');
	}
}

class CControllerGroupSettings
{
	public static function GetData()
	{
		$arModules = Array(
			"main" => Array(
				"name"=>GetMessage("CTRLR_GRP_SET_MAIN_NAME"),
				"options" => Array(
					"component_cache_on" => Array(GetMessage("CTRLR_GRP_SET_MAIN_OPT_CACHE"), "N", Array("checkbox", "Y")),
					"error_reporting" => Array(
						GetMessage("CTRLR_GRP_SET_MAIN_OPT_ERRREP"),
						85,
						Array("selectbox",
							Array(
								"85"=>GetMessage("CTRLR_GRP_SET_MAIN_OPT_ERRREP_1"),
								"2039"=>GetMessage("CTRLR_GRP_SET_MAIN_OPT_ERRREP_2"),
								"0" =>GetMessage("CTRLR_GRP_SET_MAIN_OPT_ERRREP_3")
							)
						)
					),
					"all_bcc" => Array(GetMessage("CTRLR_GRP_SET_MAIN_OPT_EMAIL"), "", Array("text", 30)),
					"disk_space" => Array(GetMessage("CTRLR_GRP_SET_MAIN_OPT_QUOTA"),	"", Array("text", 30)),

					"__registration" => GetMessage("CTRLR_GRP_SET_MAIN_OPT_REG"),
					"new_user_registration" => Array(GetMessage("CTRLR_GRP_SET_MAIN_OPT_CANREG"), "N", Array("checkbox", "Y")),
					"store_password" => Array(GetMessage("CTRLR_GRP_SET_MAIN_OPT_STORE_AUTH"), "Y", Array("checkbox", "Y")),
					"captcha_registration" => Array(GetMessage("CTRLR_GRP_SET_MAIN_OPT_CAPTCHA"), "N", Array("checkbox", "Y")),
					"auth_comp2" => Array(GetMessage("CTRLR_GRP_SET_MAIN_OPT_C2_0"), "N", Array("checkbox", "Y")),
					"auth_controller_prefix" => Array(GetMessage("CTRLR_GRP_SET_MAIN_PREFIX"), "controller", Array("text", "30")),
					"auth_controller_sso" => Array(GetMessage("CTRLR_GRP_SET_MAIN_AUTH_REM"), "N", Array("checkbox", "Y")),

					"__updates"=>GetMessage("CTRLR_GRP_SET_MAIN_OPT_UPD"),
					"update_site" => Array(GetMessage("CTRLR_GRP_SET_MAIN_OPT_UPD_SER"), "", Array("text", 30)),
					"update_site_proxy_addr" => Array(GetMessage("CTRLR_GRP_SET_MAIN_OPT_UPD_PROXY"), "", Array("text", 30)),
					"update_site_proxy_port" => Array(GetMessage("CTRLR_GRP_SET_MAIN_OPT_UPD_PROXY_PORT"), "", Array("text", 30)),
					"update_site_proxy_user" => Array(GetMessage("CTRLR_GRP_SET_MAIN_OPT_UPD_PROXY_NAME"), "", Array("text", 30)),
					"update_site_proxy_pass" => Array(GetMessage("CTRLR_GRP_SET_MAIN_OPT_UPD_PROXY_PASS"), "", Array("text", 30)),
					"strong_update_check" => Array(GetMessage("CTRLR_GRP_SET_MAIN_OPT_UPD_STRONG"), "Y", Array("checkbox", "Y")),
					"stable_versions_only" => Array(GetMessage("CTRLR_GRP_SET_MAIN_OPT_UPD_STABLE"), "Y", Array("checkbox", "Y")),
				),
			),
			"fileman" => Array(
				"name" => GetMessage("CTRLR_GRP_SET_FILEMAN"),
				"options" => Array(
					"~allowed_components" 	=> Array(GetMessage("CTRLR_GRP_SET_FILEMAN_OPT_AV_COMP"), "", Array("textarea", 5, 30)),
				),
			),
			"advertising" => Array("name"=>GetMessage("CTRLR_GRP_SET_ADVERTISING")),
			"bitrix24" => Array("name"=>GetMessage("CTRLR_GRP_SET_BITRIX24")),
			"bizproc" => Array("name"=>GetMessage("CTRLR_GRP_SET_BIZPROC")),
			"bizprocdesigner" => Array("name"=>GetMessage("CTRLR_GRP_SET_BIZPROCDESIGNER")),
			"blog" => Array("name"=>GetMessage("CTRLR_GRP_SET_BLOG")),
			"calendar" => Array("name"=>GetMessage("CTRLR_GRP_SET_CALENDAR")),
			"catalog" => Array("name"=>GetMessage("CTRLR_GRP_SET_CATALOG")),
			"clouds" => Array("name"=>GetMessage("CTRLR_GRP_SET_CLOUDS")),
			"cluster" => Array("name"=>GetMessage("CTRLR_GRP_SET_CLUSTER")),
			"compression" => Array("name"=>GetMessage("CTRLR_GRP_SET_COMPRESSION")),
			"controller" => Array("name"=>GetMessage("CTRLR_GRP_SET_CONTROLLER")),
			"crm" => Array("name"=>GetMessage("CTRLR_GRP_SET_CRM")),
			"currency" => Array("name"=>GetMessage("CTRLR_GRP_SET_CURRENCY")),
			"dav" => Array("name"=>GetMessage("CTRLR_GRP_SET_DAV")),
			"extranet" => Array("name"=>GetMessage("CTRLR_GRP_SET_EXTRANET")),
			"form" => Array("name"=>GetMessage("CTRLR_GRP_SET_FORM")),
			"forum" => Array("name"=>GetMessage("CTRLR_GRP_SET_FORUM")),
			"iblock" => Array("name"=>GetMessage("CTRLR_GRP_SET_IBLOCK")),
			"idea" => Array("name"=>GetMessage("CTRLR_GRP_SET_IDEA")),
			"intranet" => Array("name"=>GetMessage("CTRLR_GRP_SET_INTRANET")),
			"ldap" => Array("name"=>GetMessage("CTRLR_GRP_SET_LDAP")),
			"learning" => Array("name"=>GetMessage("CTRLR_GRP_SET_LEARNING")),
			"lists" => Array("name"=>GetMessage("CTRLR_GRP_SET_LISTS")),
			"mail" => Array("name"=>GetMessage("CTRLR_GRP_SET_MAIL")),
			"meeting" => Array("name"=>GetMessage("CTRLR_GRP_SET_MEETING")),
			"perfmon" => Array("name"=>GetMessage("CTRLR_GRP_SET_PERFMON")),
			"photogallery" => Array("name"=>GetMessage("CTRLR_GRP_SET_PHOTOGALLERY")),
			"report" => Array("name"=>GetMessage("CTRLR_GRP_SET_REPORT")),
			"sale" => Array("name"=>GetMessage("CTRLR_GRP_SET_SALE")),
			"search" => Array("name"=>GetMessage("CTRLR_GRP_SET_SEARCH")),
			"security" => Array("name"=>GetMessage("CTRLR_GRP_SET_SECURITY")),
			"seo" => Array("name"=>GetMessage("CTRLR_GRP_SET_SEO")),
			"socialnetwork" => Array("name"=>GetMessage("CTRLR_GRP_SET_SOCIALNETWORK")),
			"socialservices" => Array("name"=>GetMessage("CTRLR_GRP_SET_SOCIALSERVICES")),
			"statistic" => Array("name"=>GetMessage("CTRLR_GRP_SET_STATISTIC")),
			"subscribe" => Array("name"=>GetMessage("CTRLR_GRP_SET_SUBSCRIBE")),
			"support" => Array("name"=>GetMessage("CTRLR_GRP_SET_SUPPORT")),
			"tasks" => Array("name"=>GetMessage("CTRLR_GRP_SET_TASKS")),
			"timeman" => Array("name"=>GetMessage("CTRLR_GRP_SET_TIMEMAN")),
			"translate" => Array("name"=>GetMessage("CTRLR_GRP_SET_TRANSLATE")),
			"video" => Array("name"=>GetMessage("CTRLR_GRP_SET_VIDEO")),
			"videomost" => Array("name"=>GetMessage("CTRLR_GRP_SET_VIDEOMOST")),
			"videoport" => Array("name"=>GetMessage("CTRLR_GRP_SET_VIDEOPORT")),
			"vote" => Array("name"=>GetMessage("CTRLR_GRP_SET_VOTE")),
			"webdav" => Array("name"=>GetMessage("CTRLR_GRP_SET_WEBDAV")),
			"webservice" => Array("name"=>GetMessage("CTRLR_GRP_SET_WEBSERVICE")),
			"wiki" => Array("name"=>GetMessage("CTRLR_GRP_SET_WIKI")),
			"workflow" => Array("name"=>GetMessage("CTRLR_GRP_SET_WORKFLOW")),
			"xdimport" => Array("name"=>GetMessage("CTRLR_GRP_SET_XDIMPORT")),
			"xmpp" => Array("name"=>GetMessage("CTRLR_GRP_SET_XMPP")),
		);

		uasort($arModules, array("CControllerGroupSettings", "_cmp"));
		return $arModules;
	}

	public static function _cmp($a, $b)
	{
		return strcmp($a["name"], $b["name"]);
	}

	public static function GetModules()
	{
		$arInfo = CControllerGroupSettings::GetData();
		$arModules = Array();
		foreach($arInfo as $mname=>$arProp)
		{
			$arModules[$mname] = $arProp["name"];
		}
		return $arModules;
	}


	public static function GenerateInput($id, $arInfo, $curVal=false, $context='default')
	{
		$res = '<input type="checkbox" id="'.htmlspecialcharsbx('ACT_'.$id).'" '.($curVal===false?'':'checked').' name="'.htmlspecialcharsbx('OPTIONS['.$context.']['.$id.']').'" value="Y" title="'.GetMessage("CTRLR_GRP_REASSIGN").
			'" onclick="'.htmlspecialcharsbx('document.getElementById(\''.CUtil::AddSlashes($id).'\').disabled=!this.checked;if(this.checked)document.getElementById(\''.CUtil::AddSlashes($id).'\').focus();').'">';
		if($curVal===false)
			$strDis = ' disabled ';
		else
			$strDis = '';

		$arInput = $arInfo[2];
		if($arInput[0]=='checkbox')
		{
			$res .= '<select name="'.htmlspecialcharsbx('OPTIONS['.$context.']['.$id.']').'" id="'.htmlspecialcharsbx($id).'"'.$strDis.'>'.
			'<option value="N">'.GetMessage("CTRLR_GRP_OPT_NO").'</option>'.
			'<option value="Y"'.($curVal!==false && $curVal=='Y'?' selected':'').'>'.GetMessage("CTRLR_GRP_OPT_YES").'</option>'.
			'</select>';
		}
		elseif($arInput[0]=='text')
		{
			$res .= '<input type="text" name="'.htmlspecialcharsbx('OPTIONS['.$context.']['.$id.']').'" '.$strDis.'id="'.htmlspecialcharsbx($id).
				'" size="'.htmlspecialcharsbx($arInput[1]).'" value="'.htmlspecialcharsbx($curVal===false?$arInput[2]:$curVal).'">';
		}
		elseif($arInput[0]=='selectbox')
		{
			$res .= '<select name="'.htmlspecialcharsbx('OPTIONS['.$context.']['.$id.']').'" '.$strDis.'id="'.htmlspecialcharsbx($id).'">';
			foreach($arInput[1] as $enum_id => $enum_value)
			{
				$res .= '<option value="'.htmlspecialcharsbx($enum_id).'"'.
					($curVal!==false && $curVal==$enum_id?' selected':'').
					'>'.htmlspecialcharsEx($enum_value).'</option>';
			}
			$res .= '</select>';
		}
		elseif($arInput[0]=="textarea")
		{
			$res .= '<br><textarea rows="'.htmlspecialcharsbx($arInput[1]).'" cols="'.htmlspecialcharsbx($arInput[2]).'" name="'.htmlspecialcharsbx('OPTIONS['.$context.']['.$id.']').'" '.$strDis.' id="'.htmlspecialcharsbx($id).'">'.htmlspecialcharsbx($curVal===false? $arInput[3]: $curVal).'</textarea>';
		}

		return $res;
	}

	public static function Get3rdPartyOptions()
	{
		$arResult = array();
		foreach (GetModuleEvents("controller", "OnGetGroupSettings", true) as $arEvent)
		{
			$Object = ExecuteModuleEventEx($arEvent);
			if (is_object($Object))
				$arResult[] = $Object;
		}
		return $arResult;
	}

	public static function GeneratePHPInstall($arValues)
	{
		$str = '';
		$arDefValues = $arValues["default"]["options"];
		$arInfo = CControllerGroupSettings::GetData();

		if(isset($arValues["default"]["modules"]))
		{
			$vArr = '';
			foreach($arInfo as $module_id=>$arProp)
			{
				if($module_id == 'main')
					continue;

				if(in_array($module_id, $arValues["default"]["modules"]))
					$vArr .= '"'.$module_id.'"=>"Y", ';
				else
					$vArr .= '"'.$module_id.'"=>"N", ';
			}
			$str .= 'CControllerClient::SetModules(Array('.$vArr.'));'."\r\n";
		}
		else
			$str .= 'CControllerClient::RestoreModules();'."\r\n";

		foreach($arInfo as $mname=>$arProp)
		{
				if(!is_array($arProp["options"]) || count($arProp["options"])<=0)
					continue;
				$arOptions = $arProp["options"];
				foreach($arOptions as $id=>$arOptionParams)
				{
					if(isset($arDefValues[$mname][$id]))
						$str .= 'CControllerClient::SetOptionString("'.EscapePHPString($mname).'", "'.EscapePHPString($id).'", "'.EscapePHPString($arDefValues[$mname][$id]).'");'."\r\n";
					elseif(substr($id, 0, 2)!='__')
						$str .= 'CControllerClient::RestoreOption("'.EscapePHPString($mname).'", "'.EscapePHPString($id).'");'."\r\n";
				}
		}

		$arSecurity = $arValues["default"]["security"];
		if($arSecurity["limit_admin"] == "Y")
			$str .= 'CControllerClient::SetOptionString("main", "~controller_limited_admin", "Y");'."\r\n";
		else
			$str .= 'CControllerClient::SetOptionString("main", "~controller_limited_admin", "N");'."\r\n";

		$subordinate_id = COperation::GetIDByName('edit_subordinate_users');

		$arGroups = Array();
		$arUniqTasks = Array();
		if(is_array($arSecurity["groups"]))
		{
			foreach($arSecurity["groups"] as $group_id=>$arPermissions)
			{
				$arDefinedPermissions = Array();
				$arUnDefinedPermissions = Array();
				$bSubOrdGroups = false;
				foreach($arInfo as $module_id=>$arProp)
				{
					if(isset($arPermissions[$module_id]))
					{
						$arDefinedPermissions[$module_id] = $arPermissions[$module_id];

						$task_id = $arPermissions[$module_id];

						if(strlen($task_id)>1 && (!is_array($arUniqTasks[$module_id]) || !in_array($task_id, $arUniqTasks[$module_id])))
						{
							$arUniqTasks[$module_id][] = $task_id;
							$dbr_task = CTask::GetList(Array(), Array('NAME'=>$task_id, 'MODULE_ID'=>$module_id, "BINDING" => 'module'));
							if($ar_task = $dbr_task->Fetch())
							{
								if($module_id == 'main' || $ar_task['SYS']!='Y')
								{
									$arOperations = CTask::GetOperations($ar_task["ID"], true);

									if($ar_task['SYS']!='Y')
									{
										$str .= 'CControllerClient::SetTaskSecurity('.CControllerGroupSettings::__PHPToString($task_id).', '.CControllerGroupSettings::__PHPToString($module_id).', '.CControllerGroupSettings::__PHPToString($arOperations).', '.CControllerGroupSettings::__PHPToString($ar_task["LETTER"]).');'."\r\n";
									}

									if($module_id == 'main' && in_array('edit_subordinate_users', $arOperations))
									{
										$bSubOrdGroups = true;
									}
								}
							}
						}
					}
					else
						$arUnDefinedPermissions[] = $module_id;
				}

				$str .= 'CControllerClient::RestoreGroupSecurity('.CControllerGroupSettings::__PHPToString($group_id).', '.CControllerGroupSettings::__PHPToString($arUnDefinedPermissions).');'."\r\n";

				if($bSubOrdGroups)
				{
					$arSGroupsTmp = preg_split("/[\r\n,;]+/", $arSecurity["subord_groups"][$group_id]);
					$arSGroups = array();
					foreach($arSGroupsTmp as $sGroupTmp)
					{
						$sGroupTmp = trim($sGroupTmp);
						if ($sGroupTmp != '')
							$arSGroups[] = $sGroupTmp;
					}

					$str .= 'CControllerClient::SetGroupSecurity('.CControllerGroupSettings::__PHPToString($group_id).', '.CControllerGroupSettings::__PHPToString($arDefinedPermissions).', '.CControllerGroupSettings::__PHPToString($arSGroups).');'."\r\n";
				}
				else
					$str .= 'CControllerClient::SetGroupSecurity('.CControllerGroupSettings::__PHPToString($group_id).', '.CControllerGroupSettings::__PHPToString($arDefinedPermissions).');'."\r\n";

				$arGroups[] = $group_id;
			}
		}

		$str .= 'CControllerClient::RestoreSecurity('.CControllerGroupSettings::__PHPToString($arGroups).');'."\r\n";

		$arThirdSettings = CControllerGroupSettings::Get3rdPartyOptions();
		foreach($arThirdSettings as $obOption)
		{
			$str .= $obOption->GetOptionPHPCode($arValues);
		}

		return $str;
	}

	public static function __PHPToString($arData)
	{
		if(is_array($arData))
		{
			if($arData == array_values($arData))
			{
				foreach($arData as $key => $value)
					$arData[$key] = CControllerGroupSettings::__PHPToString($value);

				$res = "Array(".implode(", ", $arData).")";
			}
			else
			{
				$res = "Array(";
				foreach ($arData as $key => $value)
				{
					$res .= '"'.EscapePHPString($key).'" => ';
					$res .= CControllerGroupSettings::__PHPToString($value).", ";
				}
				$res .= ")";
			}
			return $res;
		}
		else
			return '"'.EscapePHPString($arData).'"';
	}

	public static function SetGroupSettings()
	{
	}
}

class IControllerGroupOption
{
	var $id = 'UNDEFINED';
	public function GetName()
	{
		return GetMessage("CTRLR_GRP_SETTINGS")." ".$this->id;
	}

	public static function GetIcon()
	{
		return "controller_group_edit";
	}

	public static function GetTitle()
	{
		return GetMessage("CTRLR_GRP_SETTINGS_TITLE");
	}


	public static function GetOptionArray()
	{
		return Array();
	}

	public function GetOptionPHPCode($arAllValues)
	{
		$arValues = $arAllValues[$this->id];
		$arOptions = $this->GetOptionArray();
		$str = "";
		foreach($arOptions as $id=>$arOptionParams)
		{
			if(isset($arValues[$id]))
				$str .= 'CControllerClient::SetOptionString("'.EscapePHPString($this->id).'", "'.EscapePHPString($id).'", "'.EscapePHPString($arValues[$id]).'");'."\r\n";
			elseif(substr($id, 0, 2)!='__')
				$str .= 'CControllerClient::RestoreOption("'.EscapePHPString($this->id).'", "'.EscapePHPString($id).'");'."\r\n";
		}
		return $str;
	}
}
?>
