<?
//**********************************************************************/
//**    DO NOT MODIFY THIS FILE                                       **/
//**    MODIFICATION OF THIS FILE WILL ENTAIL SITE FAILURE            **/
//**********************************************************************/
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
IncludeModuleLangFile(__FILE__);

$MAIN_RIGHT = $APPLICATION->GetGroupRight("main");
if ($MAIN_RIGHT<"R") 
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

class CUpdateOutput
{
	public static function ShowBlock($top, $bottom="", $icon="")
	{
		$s = '
<div class="update-block">
<table cellspacing="0" cellpadding="0" border="0" class="update-block">
	<tr class="top">
		<td class="left"><div class="empty"></div></td>
		<td><div class="empty"></div></td>
		<td class="right"><div class="empty"></div></td>
	</tr>
	<tr>
		<td class="left"><div class="empty"></div></td>
		<td class="content">
			<div class="top">
				<table cellpadding="0" cellspacing="0">
					<tr>
						<td class="icon"><div class="icon '.$icon.'"></div></td>
						<td>'.$top.'</td>
					</tr>
				</table>
			</div>';

		if($bottom <> "")
		{
			$s .= '
			<div class="bottom">
				<table cellpadding="0" cellspacing="0" width="100%">
					<tr>
						<td class="icon" width="0%"><div class="icon"></div></td>
						<td width="100%">'.$bottom.'</td>
					</tr>
				</table>
			</div>
';			
		}
		
		$s .= '
		</td>
		<td class="right"><div class="empty"></div></td>
	</tr>
	<tr class="bottom">
		<td class="left"><div class="empty"></div></td>
		<td><div class="empty"></div></td>
		<td class="right"><div class="empty"></div></td>
	</tr>
</table>
</div>
';
		return $s;
	}

	public static function ShowScript()
	{
		if(defined("__CUpdateOutputScript"))
			return "";
		// define("__CUpdateOutputScript", true);
		return '
<script type="text/javascript">
function ToggleInfo(id, obj)
{
	var visible = jsUtils.ToggleDiv(id);
	jsUserOptions.SaveOption("sysupdate", "hidden_blocks", id, (visible? "on":"off"));
	obj.className = (visible? "button button-up":"button button-down");
	obj.title = (visible? "'.GetMessage("SUP_COLLAPSE").'":"'.GetMessage("SUP_EXPAND").'");
}
</script>
';		
	}

	public static function ShowBlockInfo($top, $bottom, $id)
	{
		$aOpt = CUserOptions::GetOption("sysupdate", "hidden_blocks", array());
		$s = CUpdateOutput::ShowScript();
		$s .= '
<div class="update-block-info">
<table cellspacing="0" cellpadding="0" border="0" class="update-block-info">
	<tr class="top">
		<td class="left"><div class="empty"></div></td>
		<td><div class="empty"></div></td>
		<td class="right"><div class="empty"></div></td>
	</tr>
	<tr>
		<td class="left"><div class="empty"></div></td>
		<td class="content">
			<div class="top">
			<table cellspacing="0" width="100%">
				<tr>
					<td class="caption">'.$top.'</td>
					<td align="right"><a class="button '.($aOpt[$id]=="off"? "button-down":"button-up").'" title="'.($aOpt[$id]=="off"? GetMessage("SUP_EXPAND"):GetMessage("SUP_COLLAPSE")).'" href="javascript:void(0);" onclick="ToggleInfo(\''.$id.'\', this)"></a></td>
				</tr>
			</table>
			</div>
			
			<div class="bottom" id="'.$id.'" style="display:'.($aOpt[$id]=="off"? "none":"block").'">'.$bottom.'</div>
		</td>
		<td class="right"><div class="empty"></div></td>
	</tr>
	<tr class="bottom">
		<td class="left"><div class="empty"></div></td>
		<td><div class="empty"></div></td>
		<td class="right"><div class="empty"></div></td>
	</tr>
</table>
</div>
';
		return $s;
	}
}

$strErrorMessage = "";

/* Настраиваем параметры вызова страницы */
$STEP = IntVal($STEP);
if ($STEP <= 0 || $STEP > 4)
	$STEP = 1;

if ($MAIN_RIGHT != "W")
	$STEP = 1;

if ($TYPE!="REGVER" && $TYPE!="LOADSRC" && $TYPE!="UPDUPD" && $TYPE!="LANGS" && $TYPE!="HELP" && $TYPE!="SAVE_KEY" && $TYPE!="ACTIVATE_KEY" && $TYPE!="ADD_SITE" && $TYPE!="SUBSCR_UPDATE" && $TYPE!="TURN_STABILITY" && $TYPE!="AGREE_LICENSE_ACT")
{
	$TYPE = "UPDATE";
}

if ($TYPE=="REGVER" || $TYPE=="LOADSRC" || $TYPE=="UPDUPD" || $TYPE=="SAVE_KEY" || $TYPE=="ACTIVATE_KEY" || $TYPE=="SUBSCR_UPDATE" || $TYPE=="TURN_STABILITY" || $TYPE=="AGREE_LICENSE_ACT")
{
	$STEP = 1;
}
/* Конец настройки параметров вызова страницы */

$bLockUpdateSystemKernel = CUpdateSystem::IsInCommonKernel();

if ($bLockUpdateSystemKernel)
{
	$STEP = 1;
	$TYPE = "UPDATE";
	$strErrorMessage .= GetMessage("SUP_CANT_CONTRUPDATE").". ";
}

$stableVersionsOnly = COption::GetOptionString("main", "stable_versions_only", "Y");

if ($TYPE=="AGREE_LICENSE_ACT" && $MAIN_RIGHT=="W")
{
	COption::SetOptionString("main", "~new_license11_sign", "Y");
	$TYPE = "UPDATE";
}

//*********** ВКЛЮЧЕНИЕ / ОТКЛЮЧЕНИЕ УСТАНОВКИ БЕТА-ВЕРСИЙ *************/

if ($TYPE=="TURN_STABILITY" && $MAIN_RIGHT=="W")
{
	$stableVersionsOnly = (($stableVersionsOnly == "Y") ? "N" : "Y");
	COption::SetOptionString("main", "stable_versions_only", $stableVersionsOnly);

	$TYPE = "UPDATE";
}

//*********** ДЕЙСТВИЯ НАД КЛЮЧОМ *************/

if ($TYPE=="SAVE_KEY" && $MAIN_RIGHT=="W")
{
	$NEW_LICENSE_KEY = preg_replace("/[^A-Za-z0-9_.-]/", "", $NEW_LICENSE_KEY);

	if (strlen($NEW_LICENSE_KEY)<=0)
		$strErrorMessage .= "[PULK01] ".GetMessage("SUP_ENTER_KEY").".<br>";
	elseif (strtolower($NEW_LICENSE_KEY)=="demo")
		$strErrorMessage .= "[PULK02] ".GetMessage("SUP_ENTER_CORRECT_KEY").".<br>";

	if (strlen($strErrorMessage)<=0)
	{
		if (!($fp = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/license_key.php", "w")))
		{
			$strErrorMessage .= "[PULK03] ".GetMessage("SUP_CANT_OPEN_FILE").".<br>";
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		fputs($fp, "<"."? \$"."LICENSE_KEY = \"".EscapePHPString($NEW_LICENSE_KEY)."\"; ?".">");
		fclose($fp);
	}

	$TYPE = "UPDATE";
}

//*********** ДЕЙСТВИЯ НАД ПОДПИСКОЙ *************/

if ($TYPE=="SUBSCR_UPDATE" && $MAIN_RIGHT=="W")
{
	$strSaveEmails = "";

	if (strlen($SUBSCR_DELETE)<=0)
	{
		if (strlen($SUBSCR_EMAIL)<=0)
			$strErrorMessage .= "[PSUUE01] ".GetMessage("SUP_ERROR_NO_MAIL_U").".<br>";

		if (strlen($strErrorMessage)<=0)
		{
			$arEmails = explode(",", $SUBSCR_EMAIL);
			for ($i = 0; $i < count($arEmails); $i++)
			{
				$arEmails[$i] = Trim($arEmails[$i]);
				if (strlen($arEmails[$i])>0)
				{
					if (CUpdateSystem::CheckEMail($arEmails[$i]))
					{
						if (strlen($strSaveEmails)>0)
							$strSaveEmails .= ",";
						$strSaveEmails .= $arEmails[$i];
					}
					else
					{
						$strErrorMessage .= "[PSUUE02] ".GetMessage("SUP_ERROR_BAD_MAIL_U", array("#EMAIL#"=>$arEmails[$i])).".<br>";
					}
				}
			}
		}

		if (strlen($strErrorMessage)<=0)
		{
			if (strlen($strSaveEmails)<=0)
			{
				$strErrorMessage .= "[PSUUE03] ".GetMessage("SUP_ERROR_NO_MAIL_U").".<br>";
			}
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		CUpdateSystem::SubscribeUpdates($strSaveEmails, $strErrorMessage, LANG);
	}

	$TYPE = "UPDATE";
}

//*********** ДЕЙСТВИЯ НАД АКТИВАЦИЕЙ КЛЮЧА *************/

if ($TYPE=="ACTIVATE_KEY" && $MAIN_RIGHT=="W")
{
	if (strlen($NAME)<=0)
		$strErrorMessage .= GetMessage("SUP_ACT_NAME").".<br>";

	if (strlen($EMAIL)<=0)
		$strErrorMessage .= GetMessage("SUP_ACT_EMAIL").".<br>";
	elseif (!CUpdateSystem::CheckEMail($EMAIL))
		$strErrorMessage .= GetMessage("SUP_ACT_BAD_EMAIL").".<br>";

	if (strlen($SITE_URL)<=0)
		$strErrorMessage .= GetMessage("SUP_ACT_URL").".<br>";

	if ($GENERATE_USER == "Y")
	{
		if (strlen($USER_NAME) <= 0)
			$strErrorMessage .= GetMessage("SUP_ACT_NO_USER_NAME").".<br>";
		if (strlen($USER_LAST_NAME) <= 0)
			$strErrorMessage .= GetMessage("SUP_ACT_NO_USER_LAST_NAME").".<br>";
		if (strlen($USER_LOGIN_A) <= 0)
			$strErrorMessage .= GetMessage("SUP_ACT_NO_USER_LOGIN").".<br>";
		elseif (strlen($USER_LOGIN_A) < 3)
			$strErrorMessage .= GetMessage("SUP_ACT_SHORT_USER_LOGIN").".<br>";
		if (strlen($USER_PASSWORD) < 6)
			$strErrorMessage .= GetMessage("SUP_ACT_NO_USER_PASSWORD").".<br>";
		if ($USER_PASSWORD != $USER_PASSWORD_CONFIRM)
			$strErrorMessage .= GetMessage("SUP_ACT_NO_USER_PASSWORD_CONFIRM").".<br>";
	}
	else
	{
		if (strlen($USER_LOGIN) < 3)
			$strErrorMessage .= GetMessage("SUP_ACT_NO_USER_LOGIN").".<br>";
	}

	if (strlen($strErrorMessage) <= 0)
	{
		$arFields = array(
				"NAME" => $NAME,
				"EMAIL" => $EMAIL,
				"SITE_URL" => $SITE_URL,
				"CONTACT_INFO" => $CONTACT_INFO,
				"GENERATE_USER" => (($GENERATE_USER == "Y") ? "Y" : "N"),
				"USER_NAME" => $USER_NAME,
				"USER_LAST_NAME" => $USER_LAST_NAME,
				"USER_LOGIN" => (($GENERATE_USER == "Y") ? $USER_LOGIN_A : $USER_LOGIN),
				"USER_PASSWORD" => $USER_PASSWORD,
				
				"PHONE" => $PHONE,
				"CONTACT_PERSON" => $CONTACT_PERSON,
				"CONTACT_EMAIL" => $CONTACT_EMAIL,
				"CONTACT_PHONE" => $CONTACT_PHONE,
			);
		CUpdateSystem::ActivateLicenseKey($arFields, $strErrorMessage, LANG, $stableVersionsOnly);
	}

	$TYPE = "UPDATE";
}

//*********** ДЕЙСТВИЯ НАД САЙТОМ *************/

if ($TYPE=="ADD_SITE" && $MAIN_RIGHT=="W" && $STEP==2)
{
	if (strlen($CHECK)<=0)
		$strErrorMessage .= GetMessage("SUP_ENTER_CHECK_EX").".<br>";

	if (strlen($strErrorMessage)<=0)
	{
		CUpdateSystem::AddSites($CHECK, $strErrorMessage, LANG, $stableVersionsOnly);
	}

	$STEP = 1;
	if (strlen($strErrorMessage)<=0)
		$TYPE = "UPDATE";
}

//*********** ДЕЙСТВИЯ НАД РЕГИСТРАЦИЕЙ *************/

if ($TYPE=="REGVER" && $MAIN_RIGHT=="W")
{
	if (CUpdateSystem::RegisterVersion($strErrorMessage, LANG, $stableVersionsOnly))
	{
		LocalRedirect("sysupdate.php?STEP=1&TYPE=UPDATE&lang=".LANG);
	}
	$TYPE = "UPDATE";
}

//*********** ДЕЙСТВИЯ НАД ИСХОДНИКАМИ *************/

if ($TYPE=="LOADSRC" && $MAIN_RIGHT=="W")
{
	if (CUpdateSystem::LoadSources($strErrorMessage, LANG, $stableVersionsOnly))
	{
		LocalRedirect("sysupdate.php?STEP=1&TYPE=UPDATE&lang=".LANG);
	}
	$TYPE = "UPDATE";
}

//*********** ДЕЙСТВИЯ НАД СИСТЕМОЙ ОБНОВЛЕНИЙ *************/

if ($TYPE=="UPDUPD" && $MAIN_RIGHT=="W")
{
	if (CUpdateSystem::UpdateUpdate($strErrorMessage, LANG, $stableVersionsOnly))
	{
		LocalRedirect("sysupdate.php?STEP=1&TYPE=UPDATE&lang=".LANG);
	}
	$TYPE = "UPDATE";
}

//*********** ДЕЙСТВИЯ НАД МОДУЛЯМИ *************/

if ($TYPE=="UPDATE" && $STEP==4 && $MAIN_RIGHT=="W")
{
	if (strlen($UNIID)>0 && $_SESSION[$UNIID]=="Y")
	{
		CUpdateSystem::AddMessage2Log(GetMessage("SUP_NOT_REFRESH"), "PU400");
		$strErrorMessage .= "[PU400] ".GetMessage("SUP_NOT_REFRESH").".<br>";
	}

	if (strlen($strErrorMessage)<=0)
	{
		CUpdateSystem::AddMessage2Log("Query updating modules: ".$load_modules);

		$arLoadModules = array();
		if (strlen($load_modules) > 0)
		{
			$arLoadModules_tmp = explode(",", $load_modules);
			for ($i = 0; $i < count($arLoadModules_tmp); $i++)
			{
				$arLoadModules_tmp[$i] = Trim($arLoadModules_tmp[$i]);
				if (strlen($arLoadModules_tmp[$i])>0)
					$arLoadModules[] = $arLoadModules_tmp[$i];
			}
		}

		if (count($arLoadModules)<=0)
		{
			$strErrorMessage .= "[PU401] ".GetMessage("SUP_EMPTY_UPD_MOD").".<br>";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_EMPTY_UPD_MOD"), "PU401");
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		if (strlen($temporary_updates_dir)<=0)
		{
			$strErrorMessage .= "[PU402] ".GetMessage("SUP_EMPTY_TEMP_DIR").".<br>";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_EMPTY_TEMP_DIR"), "PU402");
		}
	}

	$arModulesUpdates = false;
	if (strlen($strErrorMessage)<=0)
	{
		$arModulesUpdates = CUpdateSystem::GetLoadedModuleUpdates($temporary_updates_dir, $strErrorMessage);
	}

	if (strlen($strErrorMessage)<=0)
	{
		if (!CUpdateSystem::CheckVersions($arModulesUpdates, $strErrorMessage, $arLoadModules))
		{
			$strErrorMessage .= "[PU404] ".GetMessage("SUP_UPD_LOAD_BREAK").".<br>";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_UPD_LOAD_BREAK"), "PU404");
		}
	}

	$arErrorModules = array();
	$arSuccessModules = array();
	if (strlen($strErrorMessage)<=0)
	{
		if (strlen($UNIID)>0) $_SESSION[$UNIID] = "Y";

		if (!CUpdateSystem::UpdateKernel($temporary_updates_dir, $arLoadModules, $strErrorMessage, $arErrorModules, $arSuccessModules))
		{
			$strErrorMessage .= "[PU405] ".GetMessage("SUP_BAD_UPD_INSTALL").".<br>";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_BAD_UPD_INSTALL"), "PU405");
		}
		else
		{
			$db_events = GetModuleEvents("main", "OnUpdatesInstalled");
			while ($arEvent = $db_events->Fetch())
				ExecuteModuleEvent($arEvent, Array("successModules" => $arSuccessModules, "loadModules" => $arLoadModules, "errorModules" => $arErrorModules, "modulesUpdates" => $arModulesUpdates));
		
		}
		
	}

}

if ($TYPE=="UPDATE" && $STEP==3 && $MAIN_RIGHT=="W")
{
	CUpdateSystem::AddMessage2Log("Query loading modules: ".$load_modules);

	if (!isset($FAS) || $FAS != "Y")
	{
		$arLoadModules = array();
		if (strlen($load_modules) > 0)
		{
			$arLoadModules_tmp = explode(",", $load_modules);
			for ($i = 0; $i < count($arLoadModules_tmp); $i++)
			{
				$arLoadModules_tmp[$i] = Trim($arLoadModules_tmp[$i]);
				if (strlen($arLoadModules_tmp[$i])>0)
					$arLoadModules[] = $arLoadModules_tmp[$i];
			}
		}

		if (count($arLoadModules)<=0)
		{
			$strErrorMessage .= "[PU301] ".GetMessage("SUP_NO_LOAD_MODULES").".<br>";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_NO_LOAD_MODULES"), "PU301");
		}

		if (strlen($strErrorMessage)<=0)
		{
			if (!CUpdateSystem::LoadModuleUpdates($arLoadModules, $strErrorMessage, LANG, $stableVersionsOnly))
			{
				$strErrorMessage .= "[PU302] ".GetMessage("SUP_UPD_LOAD_BREAK").".<br>";
				CUpdateSystem::AddMessage2Log(GetMessage("SUP_UPD_LOAD_BREAK"), "PU302");
			}
		}
	}
	else
	{
		$archiveFileName = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz";

		if (!file_exists($archiveFileName) || !is_file($archiveFileName))
		{
			$strErrorMessage .= "[PU3021] ".GetMessage("SUP_NO_TEMP_FILE", array("#FILE#"=>$archiveFileName)).".<br>";
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		$temporary_updates_dir = "";
		if (!CUpdateSystem::UnGzipArchive($temporary_updates_dir, $strErrorMessage, "Y"))
		{
			$strErrorMessage .= "[PU303] ".GetMessage("SUP_UPD_LOAD_BREAK").".<br>";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_UPD_LOAD_BREAK"), "PU303");
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		if (!CUpdateSystem::CheckUpdatability($temporary_updates_dir, $strErrorMessage))
		{
			$strErrorMessage .= "[PU304] ".GetMessage("SUP_UPD_LOAD_BREAK").".<br>";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_UPD_LOAD_BREAK"), "PU304");
		}
	}

	$arModulesUpdates = false;
	if (strlen($strErrorMessage)<=0)
	{
		$arModulesUpdates = CUpdateSystem::GetLoadedModuleUpdates($temporary_updates_dir, $strErrorMessage);
	}

	if (strlen($strErrorMessage)<=0)
	{
		if (!CUpdateSystem::CheckVersions($arModulesUpdates, $strErrorMessage, false))
		{
			$strErrorMessage .= "[PU305] ".GetMessage("SUP_UPD_LOAD_BREAK").".<br>";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_UPD_LOAD_BREAK"), "PU305");
		}
	}

	if (strlen($strErrorMessage) > 0)
	{
		$STEP = 2;
	}
}

if ($TYPE=="UPDATE" && $STEP==2 && $MAIN_RIGHT=="W")
{
	$arModulesUpdates = CUpdateSystem::GetServerModuleUpdates($strErrorMessage, LANG, $stableVersionsOnly);
}

if ($TYPE=="UPDATE" && $STEP==1)
{
	if (!$bLockUpdateSystemKernel)
	{
		$arTypesUpdates = CUpdateSystem::GetAvailableUpdateTypes($strErrorMessage, LANG, $stableVersionsOnly);
		COption::SetOptionString("main", "PARAM_MAX_SITES", $arTypesUpdates["CLIENT"]["@"]["MAX_SITES"]);
	}
}

//*********** ДЕЙСТВИЯ НАД ЯЗЫКАМИ *************/

if ($TYPE=="LANGS" && $STEP==3 && $MAIN_RIGHT=="W")
{
	if (strlen($UNIID)>0 && $_SESSION[$UNIID]=="Y")
	{
		CUpdateSystem::AddMessage2Log(GetMessage("SUP_NOT_REFRESH"), "PUL300");
		$strErrorMessage .= "[PUL300] ".GetMessage("SUP_NOT_REFRESH").".<br>";
	}

	if (strlen($strErrorMessage)<=0)
	{
		CUpdateSystem::AddMessage2Log("Query loading langs: ".implode(",", $load_langs));

		$arLoadLangs = array();
		foreach($load_langs as $langTmp)
			if(($langTmp = trim($langTmp)) <> "")
				$arLoadLangs[] = $langTmp;

		if (count($arLoadLangs)<=0)
		{
			$strErrorMessage .= "[PUL301] ".GetMessage("SUP_NO_LANGS_LOAD").".<br>";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_NO_LANGS_LOAD"), "PUL301");
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		if (!CUpdateSystem::LoadLangsUpdates($arLoadLangs, $strErrorMessage, LANG, $stableVersionsOnly))
		{
			$strErrorMessage .= "[PUL302] ".GetMessage("SUP_LANG_LOAD_BREAK").".<br>";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_LANG_LOAD_BREAK"), "PUL302");
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		$temporary_updates_dir = "";
		if (!CUpdateSystem::UnGzipArchive($temporary_updates_dir, $strErrorMessage, "Y"))
		{
			$strErrorMessage .= "[PUL303] ".GetMessage("SUP_LANG_LOAD_BREAK").".<br>";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_LANG_LOAD_BREAK"), "PUL303");
		}
	}

	$arLangsUpdates = false;
	if (strlen($strErrorMessage)<=0)
	{
		$arLangsUpdates = CUpdateSystem::GetLoadedLangsUpdates($temporary_updates_dir, $strErrorMessage);
	}

/*
	if (strlen($strErrorMessage)<=0)
	{
		if (!CUpdateSystem::CheckUpdatability($temporary_updates_dir, $strErrorMessage))
		{
			$strErrorMessage .= "[PUL304] ".GetMessage("SUP_LANG_LOAD_BREAK").".<br>";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_LANG_LOAD_BREAK"), "PUL304");
		}
	}
*/

	$arErrorLangs = array();
	$arSuccessLangs = array();

	if (strlen($strErrorMessage)<=0)
	{
		if (strlen($UNIID)>0) $_SESSION[$UNIID] = "Y";

		if (!CUpdateSystem::UpdateLangs($temporary_updates_dir, $arLoadLangs, $strErrorMessage, $arErrorLangs, $arSuccessLangs))
		{
			$strErrorMessage .= "[PUL305] ".GetMessage("SUP_BAD_LANG_INSTALL").".<br>";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_BAD_LANG_INSTALL"), "PUL305");
		}
	}
}

if ($TYPE=="LANGS" && $STEP==2 && $MAIN_RIGHT=="W")
{
	$arLangsUpdates = CUpdateSystem::GetServerLangsUpdates($strErrorMessage, LANG, $stableVersionsOnly);
}

//*********** ДЕЙСТВИЯ НАД ПОМОЩЬЮ *************/

if ($TYPE=="HELP" && $STEP==3 && $MAIN_RIGHT=="W")
{
	if (strlen($UNIID)>0 && $_SESSION[$UNIID]=="Y")
	{
		CUpdateSystem::AddMessage2Log(GetMessage("SUP_NOT_REFRESH"), "PUH300");
		$strErrorMessage .= "[PUH300] ".GetMessage("SUP_NOT_REFRESH").".<br>";
	}

	if (strlen($strErrorMessage)<=0)
	{
		CUpdateSystem::AddMessage2Log("Query loading helps: ".$load_help);

		if (strlen($load_help)<=0)
		{
			$strErrorMessage .= "[PUH301] ".GetMessage("SUP_EMPTY_HELP_LANG").".<br>";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_EMPTY_HELP_LANG"), "PUH301");
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		if (!CUpdateSystem::LoadHelpUpdates(array($load_help), $strErrorMessage, LANG, $stableVersionsOnly))
		{
			$strErrorMessage .= "[PUH302] ".GetMessage("SUP_HELP_LOAD_BREAK").".<br>";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_HELP_LOAD_BREAK"), "PUH302");
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		$temporary_updates_dir = "";
		if (!CUpdateSystem::UnGzipArchive($temporary_updates_dir, $strErrorMessage, "Y"))
		{
			$strErrorMessage .= "[PUH303] ".GetMessage("SUP_HELP_LOAD_BREAK").".<br>";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_HELP_LOAD_BREAK"), "PUH303");
		}
	}

	$arHelpUpdates = false;
	if (strlen($strErrorMessage)<=0)
	{
		$arHelpUpdates = CUpdateSystem::GetLoadedHelpUpdates($temporary_updates_dir, $strErrorMessage);
	}

	$arErrorHelp = array();
	$arSuccessHelp = array();
	if (strlen($strErrorMessage)<=0)
	{
		if (strlen($UNIID)>0) $_SESSION[$UNIID] = "Y";

		if (!CUpdateSystem::UpdateHelp($temporary_updates_dir, array($load_help), $strErrorMessage, $arErrorHelp, $arSuccessHelp))
		{
			$strErrorMessage .= "[PUH305] ".GetMessage("SUP_BAD_HELP_INSTALL").".<br>";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_BAD_HELP_INSTALL"), "PUH305");
		}
	}
}

if ($TYPE=="HELP" && $STEP==2 && $MAIN_RIGHT=="W")
{
	$arHelpUpdates = CUpdateSystem::GetServerHelpUpdates($strErrorMessage, LANG, $stableVersionsOnly);
}

//*********** КОНЕЦ ДЕЙСТВИЙ *************/

$strTitle = GetMessage("SUP_TITLE_BASE");
if ($TYPE=="UPDATE" && $STEP==1)
	$strTitle = GetMessage("SUP_TITLE_UPD_1");
elseif ($TYPE=="UPDATE" && $STEP==2)
	$strTitle = GetMessage("SUP_TITLE_UPD_2");
elseif ($TYPE=="UPDATE" && $STEP==3)
	$strTitle = GetMessage("SUP_TITLE_UPD_3");
elseif ($TYPE=="UPDATE" && $STEP==4)
	$strTitle = GetMessage("SUP_TITLE_UPD_4");
elseif ($TYPE=="LANGS" && $STEP==2)
	$strTitle = GetMessage("SUP_TITLE_LANG_2");
elseif ($TYPE=="LANGS" && $STEP==3)
	$strTitle = GetMessage("SUP_TITLE_LANG_3");
elseif ($TYPE=="HELP" && $STEP==2)
	$strTitle = GetMessage("SUP_TITLE_HELP_2");
elseif ($TYPE=="HELP" && $STEP==3)
	$strTitle = GetMessage("SUP_TITLE_HELP_3");
elseif ($TYPE=="ADD_SITE")
	$strTitle = GetMessage("SUP_TITLE_ACTIVATE_CHECK");

$APPLICATION->SetTitle($strTitle);
$APPLICATION->SetAdditionalCSS("/bitrix/themes/".ADMIN_THEME_ID."/sysupdate.css");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>

<?
/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@   ГЛАВНАЯ ФОРМА   @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/

$aMenu = array();
if(defined("SM_VERSION") && version_compare(SM_VERSION, "6.0.4") >= 0)
{
	$aMenu[] = array(
		"TEXT"=>GetMessage("SUP_NEW_UPDATE"),
		"TITLE"=>GetMessage("SUP_NEW_UPDATE_TITLE"),
		"LINK"=>"update_system.php?lang=".LANGUAGE_ID,
		"ICON"=>"btn_update",
	);
	$aMenu[] = array("SEPARATOR" => "Y");
}
$aMenu[] = array(
	"TEXT"=>GetMessage("SUP_START"),
	"TITLE"=>GetMessage("SUP_START_TITLE"),
	"LINK"=>"sysupdate.php?lang=".LANGUAGE_ID,
);
$aMenu[] = array(
	"TEXT"=>GetMessage("SUP_LOG"),
	"TITLE"=>GetMessage("SUP_LOG_TITLE"),
	"LINK"=>"sysupdate_log.php?lang=".LANGUAGE_ID,
	"ICON"=>"btn_update_log",
);
$context = new CAdminContextMenu($aMenu);
$context->Show();

//**********************************************************************/
//******   ШАГ 1  /ФОРМА/   ********************************************/
//**********************************************************************/
if ($TYPE=="UPDATE" && $STEP==1)
{
	$sWarning = "";

//*********************************
//* Проверка настроек
//*********************************
ob_start();
?>
<table cellspacing="0">
	<tr>
<?
if($stableVersionsOnly == "N"):
	$sWarning .= GetMessage("SUP_STABLE_OFF")."<br>";
?>
		<td><div class="lamp-yellow"></div></td>
		<td><?echo GetMessage("SUP_BETA")?></td>
<?else:?>
		<td><div class="lamp-green"></div></td>
		<td><?echo GetMessage("SUP_RELEASE")?></td>
<?endif;?>
	</tr>
	<tr>
<?
if(!CUpdateSystem::IsGzipInstalled()):
	$sWarning .= GetMessage("SUP_ZLIB_RECOM", array("#ZLIB_URL#"=>"http://www.php.net/manual/en/ref.zlib.php"))."<br>";
?>
		<td><div class="lamp-yellow"></div></td>
		<td><?echo GetMessage("SUP_NO_ZLIB")?></td>
<?else:?>
		<td><div class="lamp-green"></div></td>
		<td><?echo GetMessage("SUP_ZLIB")?></td>
<?endif;?>
	</tr>
<?if ($DB->type == "MYSQL"):?>
	<tr>
<?
		$dbQueryRes = $DB->Query("select VERSION() as ver", True);
		if ($arQueryRes = $dbQueryRes->Fetch())
		{
			$curMySqlVer = trim($arQueryRes["ver"]);
			$arCurMySqlVer = explode(".", $curMySqlVer);
			if (IntVal($arCurMySqlVer[0]) < 4
				|| IntVal($arCurMySqlVer[0]) == 4 && IntVal($arCurMySqlVer[1]) < 0
				|| IntVal($arCurMySqlVer[0]) == 4 && IntVal($arCurMySqlVer[1]) == 0 && IntVal($arCurMySqlVer[2]) < 18)
			{
				$strErrorMessage .= "[PVER01] ".GetMessage("SUP_MYSQL_L4", array("#VERS#"=>$curMySqlVer));
				$arTypesUpdates = false;
?>
		<td><div class="lamp-red"></div></td>
		<td><?echo GetMessage("SUP_NO_MYSQL")?></td>
<?
			}
			else
			{
?>
		<td><div class="lamp-green"></div></td>
		<td><?echo GetMessage("SUP_MYSQL")?></td>
<?
			}
		}
?>
	</tr>
<?endif?>
</table>
</div>
<?
$sMess = ob_get_contents();
ob_end_clean();

echo CUpdateOutput::ShowBlockInfo(GetMessage("SUP_SETTINGS"), $sMess, "info_settings");

//*********************************
//* Ответ сервера
//*********************************
	ob_start();
	$sMess = "";
?>
		<table cellspacing="0">
<?
	if ($arTypesUpdates!==false
		&& isset($arTypesUpdates["CLIENT"])
		&& count($arTypesUpdates["CLIENT"])>0
		&& $arTypesUpdates["CLIENT"]["@"]["RESERVED"]!="Y")
	{
		?>
			<tr>
				<td><?echo GetMessage("SUP_REGISTERED")?></td>
				<td><?echo $arTypesUpdates["CLIENT"]["@"]["NAME"]?></td>
			</tr>
			<tr>
				<td><?= GetMessage("SUP_LICENSE_KEY") ?>:</td>
				<td><?echo ($MAIN_RIGHT=="W"? CUpdateSystem::GetLicenseKey() : "XXX-XX-XXXXXXXXXXX")?></td>
			</tr>
			<tr>
				<td><?echo GetMessage("SUP_EDITION")?></td>
				<td><?echo $arTypesUpdates["CLIENT"]["@"]["LICENSE"]?></td>
			</tr>
			<tr>
				<td><?echo GetMessage("SUP_SITES")?></td>
				<td><?echo ($arTypesUpdates["CLIENT"]["@"]["MAX_SITES"]>0? $arTypesUpdates["CLIENT"]["@"]["MAX_SITES"]:GetMessage("SUP_CHECK_PROMT_2"))?></td>
			</tr>
			<tr>
				<td><?echo GetMessage("SUP_ACTIVE")?></td>
				<td><?echo GetMessage("SUP_ACTIVE_PERIOD", array("#DATE_TO#"=>((strlen($arTypesUpdates["CLIENT"]["@"]["DATE_TO"])>0) ? $arTypesUpdates["CLIENT"]["@"]["DATE_TO"] : "<i>N/A</i>"), "#DATE_FROM#"=>((strlen($arTypesUpdates["CLIENT"]["@"]["DATE_FROM"])>0) ? $arTypesUpdates["CLIENT"]["@"]["DATE_FROM"] : "<i>N/A</i>")));?></td>
			</tr>
<?
if(strlen($arTypesUpdates["CLIENT"]["@"]["HTTP_HOST"])>0):
?>
			<tr>
				<td><?echo GetMessage("SUP_SERVER")?></td>
				<td><?echo $arTypesUpdates["CLIENT"]["@"]["HTTP_HOST"]?></td>
			</tr>
<?endif?>
<?
	}
	else
	{
?>
			<tr>
				<td><?echo GetMessage("SUP_SERVER")?></td>
				<td><?echo (($s=COption::GetOptionString("main", "update_site"))==""? GetMessage("SUP_NO_SERVER"):$s)?></td>
			</tr>
<?
	}
?>
		</table>
<?
	$sMess = ob_get_contents();
	ob_end_clean();

	echo CUpdateOutput::ShowBlockInfo(GetMessage("SUP_ANSWER"), $sMess, "info_server");

//*********************************
//* Вывод ошибок
//*********************************
	// Если пришли ошибки с сервера
	$bLicenceNotActive = false;
	if ($arTypesUpdates!==false	&& isset($arTypesUpdates["ERROR"]) && count($arTypesUpdates["ERROR"])>0)
	{
		for ($i = 0; $i < count($arTypesUpdates["ERROR"]); $i++)
		{
			$strErrorMessage .= ($arTypesUpdates["ERROR"][$i]["@"]["TYPE"] <> ""? "[".$arTypesUpdates["ERROR"][$i]["@"]["TYPE"]."] " : "").$arTypesUpdates["ERROR"][$i]["#"]."<br>";
			if($arTypesUpdates["ERROR"][$i]["@"]["TYPE"] == "LICENSE_NOT_ACTIVE_A")
				$bLicenceNotActive = true;
		}
	}

	if($strErrorMessage <> "")
		CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("SUP_ERRORS"), "TYPE"=>"ERROR", "DETAILS"=>$strErrorMessage, "HTML"=>true));

	if($bLicenceNotActive)
		CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("SUP_UPDATES_TITLE"), "TYPE"=>"OK", "DETAILS"=>GetMessage("SUP_UPDATES"), "HTML"=>true));

	if($sWarning <> "")
		CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("SUP_WARNING"), "TYPE"=>"OK", "DETAILS"=>$sWarning, "HTML"=>true));

//*********************************
//* Регистрация отложенного ключа
//*********************************
	if ($arTypesUpdates!==false
		&& isset($arTypesUpdates["CLIENT"])
		&& !isset($arTypesUpdates["UPDATE_SYSTEM"])
		&& count($arTypesUpdates["CLIENT"])>0
		&& $arTypesUpdates["CLIENT"]["@"]["RESERVED"]=="Y")
	{
			echo CUpdateOutput::ShowBlock(GetMessage("SUP_ACTIVATE_PROMT"), "", "icon-licence");

			$aTabs = array(
				array("DIV" => "edit1", "TAB" => GetMessage("SUP_ACT_TAB"), "TITLE"=>GetMessage("SUP_ACT_TAB_TITLE")),
			);
			$tabControl = new CAdminTabControl("tabControl", $aTabs);
		?>
		<script>
		<!--
		function ActivateEnableDisableUser(value)
		{
			document.activate_key_form.USER_NAME.disabled = !value;
			document.activate_key_form.USER_LAST_NAME.disabled = !value;
			document.getElementById("USER_LOGIN_activate").disabled = !value;
			document.getElementById("USER_LOGIN").disabled = value;
			document.activate_key_form.USER_PASSWORD.disabled = !value;
			document.activate_key_form.USER_PASSWORD_CONFIRM.disabled = !value;
			document.activate_key_form.USER_EMAIL.disabled = !value;

			if(!value)
			{
				document.getElementById("new-user").style.display = 'none';
				document.getElementById("exist-user").style.display = 'block';
			}
			else
			{
				document.getElementById("new-user").style.display = 'block';
				document.getElementById("exist-user").style.display = 'none';
			}
		}

		function validate()
		{
			var bEr = false;
			var erImg = '<img src="/bitrix/themes/.default/images/icon_warn.gif" width="20" height="20" alt="Error" title="Error" align="left" />';

			document.getElementById('errorDiv').style.diplay = 'none'; 
			document.getElementById('id_activate_name_error').innerHTML = '';
			document.getElementById('SITE_URL_error').innerHTML = '';
			document.getElementById('PHONE_error').innerHTML = '';
			document.getElementById('EMAIL_error').innerHTML = '';
			document.getElementById('CONTACT_PERSON_error').innerHTML = '';
			document.getElementById('CONTACT_EMAIL_error').innerHTML = '';
			document.getElementById('CONTACT_PHONE_error').innerHTML = '';

			if(document.getElementById('id_activate_name').value.length <= 3)
			{
				document.getElementById('id_activate_name_error').innerHTML = erImg;
				bEr = true;
			}
			if(document.getElementById('SITE_URL').value.length <= 3)
			{
				document.getElementById('SITE_URL_error').innerHTML = erImg;
				bEr = true;
			}
			if(document.getElementById('PHONE').value.length <= 3)
			{
				document.getElementById('PHONE_error').innerHTML = erImg;
				bEr = true;
			}
			if(document.getElementById('EMAIL').value.length <= 3)
			{
				document.getElementById('EMAIL_error').innerHTML = erImg;
				bEr = true;
			}
			if(document.getElementById('CONTACT_PERSON').value.length <= 3)
			{
				document.getElementById('CONTACT_PERSON_error').innerHTML = erImg;
				bEr = true;
			}
			if(document.getElementById('CONTACT_EMAIL').value.length <= 3)
			{
				document.getElementById('CONTACT_EMAIL_error').innerHTML = erImg;
				bEr = true;
			}
			if(document.getElementById('CONTACT_PHONE').value.length <= 3)
			{
				document.getElementById('CONTACT_PHONE_error').innerHTML = erImg;
				bEr = true;
			}
			
			if(document.getElementById('GENERATE_USER').checked)
			{
				document.getElementById('USER_NAME_error').innerHTML = '';
				document.getElementById('USER_LAST_NAME_error').innerHTML = '';
				document.getElementById('USER_LOGIN_error').innerHTML = '';
				document.getElementById('USER_PASSWORD_error').innerHTML = '';
				document.getElementById('USER_PASSWORD_CONFIRM_error').innerHTML = '';
				document.getElementById('USER_EMAIL_error').innerHTML = '';

				if(document.getElementById('USER_NAME').value.length <= 3)
				{
					document.getElementById('USER_NAME_error').innerHTML = erImg;
					bEr = true;
				}		
				if(document.getElementById('USER_LAST_NAME').value.length <= 3)
				{
					document.getElementById('USER_LAST_NAME_error').innerHTML = erImg;
					bEr = true;
				}		
				if(document.getElementById('USER_LOGIN_activate').value.length < 3)
				{
					document.getElementById('USER_LOGIN_error').innerHTML = erImg;
					bEr = true;
				}		
				if(document.getElementById('USER_PASSWORD').value.length < 6)
				{
					document.getElementById('USER_PASSWORD_error').innerHTML = erImg;
					bEr = true;
				}		
				if(document.getElementById('USER_PASSWORD').value != document.getElementById('USER_PASSWORD_CONFIRM').value)
				{
					document.getElementById('USER_PASSWORD_error').innerHTML = erImg;
					bEr = true;
					document.getElementById('USER_PASSWORD_CONFIRM_error').innerHTML = erImg;
					bEr = true;
				}		
				if(document.getElementById('USER_EMAIL').value.length <= 3)
				{
					document.getElementById('USER_EMAIL_error').innerHTML = erImg;
					bEr = true;
				}
			}
			else
			{
				if(document.getElementById('USER_LOGIN').value.length < 3)
				{
					document.getElementById('USER_LOGIN_EXIST_error').innerHTML = erImg;
					bEr = true;
				}		
			}
			
			if(bEr)
			{
				document.getElementById('errorDiv').innerHTML = '<table style="color:red;"><tr><td><img src="/bitrix/themes/.default/images/icon_error.gif" width="32" height="32" alt="Error" title="Error" align="left" valign="center"/></td><td><b><?=GetMessage("SUP_SUBA_CONFIRM_ERROR")?></b></td></tr></table>'; 
				document.getElementById('errorDiv').style.border = "1px solid red";

				return false;
			}
			else
			{
				return true;
			}
		}
		//-->
		</script>

		<form method="POST" action="sysupdate.php" name="activate_key_form" id="activate_key_form" onsubmit="return validate();">
			<input type="hidden" name="TYPE" VALUE="ACTIVATE_KEY">
			<input type="hidden" name="STEP" VALUE="1">
			<input type="hidden" name="lang" VALUE="<?=LANGUAGE_ID?>">
		<?
		$tabControl->Begin();
		$tabControl->BeginNextTab();
		?>
		<tr>
			<td colspan="2"><div id="errorDiv" style="width:30%;"></div></td>
		</tr>
		<tr class="heading">
			<td colspan="2"><?echo GetMessage("SUP_REG_INFO")?></td>
		</tr>

			<tr>
				<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_RI_NAME") ?>:</td>
				<td width="50%" nowrap><div id="id_activate_name_error"></div><input type="text" id="id_activate_name" name="NAME" value="<?=htmlspecialcharsEx($_POST["NAME"])?>" size="40"></td>
			</tr>
			<tr>
				<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_RI_URI") ?>:</td>
				<td width="50%"><div id="SITE_URL_error"></div><input type="text" id="SITE_URL" name="SITE_URL" value="<?=htmlspecialcharsEx($_POST["SITE_URL"])?>" size="40"></td>
			</tr>
			<tr>
				<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_RI_PHONE") ?>:</td>
				<td width="50%"><div id="PHONE_error"></div><input type="text" id="PHONE" name="PHONE" value="<?=htmlspecialcharsEx($_POST["PHONE"])?>" size="40"></td>
			</tr>
			<tr>
				<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_RI_EMAIL") ?>:</td>
				<td width="50%"><div id="EMAIL_error"></div><input type="text" id="EMAIL" name="EMAIL" value="<?=htmlspecialcharsEx($_POST["EMAIL"])?>" size="40"></td>
			</tr>
			<tr>
				<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_RI_CONTACT_PERSON") ?>:</td>
				<td width="50%"><div id="CONTACT_PERSON_error"></div><input type="text" id="CONTACT_PERSON" name="CONTACT_PERSON" value="<?=htmlspecialcharsEx($_POST["CONTACT_PERSON"])?>" size="40"></td>
			</tr>
			<tr>
				<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_RI_CONTACT_EMAIL") ?>:</td>
				<td width="50%"><div id="CONTACT_EMAIL_error"></div><input type="text" id="CONTACT_EMAIL" name="CONTACT_EMAIL" value="<?=htmlspecialcharsEx($_POST["CONTACT_EMAIL"])?>" size="40"></td>
			</tr>
			<tr>
				<td width="50%"><span class="required">*</span><?= GetMessage("SUP_SUBA_RI_CONTACT_PHONE") ?>:</td>
				<td width="50%"><div id="CONTACT_PHONE_error"></div><input type="text" id="CONTACT_PHONE" name="CONTACT_PHONE" value="<?=htmlspecialcharsEx($_POST["CONTACT_PHONE"])?>" size="40"></td>
			</tr>
			<tr>
				<td width="50%"><?= GetMessage("SUP_SUBA_RI_CONTACT") ?>:</td>
				<td width="50%"><input type="text" name="CONTACT_INFO" value="<?=htmlspecialcharsEx($_POST["CONTACT_INFO"])?>" size="40"></td>
			</tr>

		<tr class="heading">
			<td colspan="2"><?echo GetMessage("SUP_REG_INFO_USER")?></td>
		</tr>			
		<tr>
			<td colspan="2">
				<?echo BeginNote()?>
				<?= GetMessage("SUP_SUBA_UI_HINT") ?><br />

				<input name="GENERATE_USER" id="GENERATE_USER" type="radio" onclick="ActivateEnableDisableUser(true)" value="Y"<?if($GENERATE_USER != "N") echo " checked"?>><label for="GENERATE_USER"><?= GetMessage("SUP_SUBA_UI_CREATE") ?></label><br />
				<input name="GENERATE_USER" id="GENERATE_USER_NO" type="radio" onclick="ActivateEnableDisableUser(false)" value="N"<?if($GENERATE_USER == "N") echo " checked"?>><label for="GENERATE_USER_NO"><?echo GetMessage("SUP_SUBA_UI_EXIST");?></label>
				<?echo EndNote()?>
			</td>
		</tr>

		<tr>
			<td colspan="2">
				<div id="new-user">
					<table width="100%" border="0">
					<tr id="tr_USER_NAME">
						<td width="50%" class="field-name" style="padding: 3px;"><span class="required">*</span><?= GetMessage("SUP_SUBA__UI_NAME") ?>:</td>
						<td width="50%" style="padding: 3px;"><div id="USER_NAME_error"></div><input type="text" id="USER_NAME" name="USER_NAME" value="<?=htmlspecialcharsEx($_POST["USER_NAME"])?>" size="40"></td>
					</tr>
					<tr id="tr_USER_LAST_NAME">
						<td width="50%" class="field-name" style="padding: 3px;"><span class="required">*</span><?= GetMessage("SUP_SUBA_UI_LASTNAME") ?>:</td>
						<td width="50%" style="padding: 3px;"><div id="USER_LAST_NAME_error"></div><input type="text" id="USER_LAST_NAME" name="USER_LAST_NAME" value="<?=htmlspecialcharsEx($_POST["USER_LAST_NAME"])?>" size="40"></td>
					</tr>
					<tr id="tr_USER_LOGIN">
						<td width="50%" class="field-name" style="padding: 3px;"><span class="required">*</span><?= GetMessage("SUP_SUBA_UI_LOGIN") ?>:</td>
						<td width="50%" style="padding: 3px;"><div id="USER_LOGIN_error"></div><input type="text" id="USER_LOGIN_activate" name="USER_LOGIN_A" value="<?=htmlspecialcharsEx($_POST["USER_LOGIN_A"])?>" size="40"></td>
					</tr>
					<tr id="tr_USER_PASSWORD">
						<td width="50%" class="field-name" style="padding: 3px;"><span class="required">*</span><?= GetMessage("SUP_SUBA_UI_PASSWORD") ?>:</td>
						<td width="50%" style="padding: 3px;"><div id="USER_PASSWORD_error"></div><input type="password" id="USER_PASSWORD" name="USER_PASSWORD" value="" size="40" autocomplete="off"></td>
					</tr>
					<tr id="tr_USER_PASSWORD_CONFIRM">
						<td width="50%" class="field-name" style="padding: 3px;"><span class="required">*</span><?= GetMessage("SUP_SUBA_UI_PASSWORD_CONF") ?>:</td>
						<td width="50%" style="padding: 3px;"><div id="USER_PASSWORD_CONFIRM_error"></div><input type="password" id="USER_PASSWORD_CONFIRM" name="USER_PASSWORD_CONFIRM" value="" size="40"></td>
					</tr>
					<tr id="tr_USER_EMAIL">
						<td width="50%" class="field-name" style="padding: 3px;"><span class="required">*</span>E-mail:</td>
						<td width="50%" style="padding: 3px;"><div id="USER_EMAIL_error"></div><input type="text" id="USER_EMAIL" name="USER_EMAIL" value="<?=htmlspecialcharsEx($_POST["USER_EMAIL"])?>" size="40"></td>
					</tr>
					</table>
				</div>
				<div id="exist-user" style="display:none;">
					<table width="100%" border="0">
					<tr>
						<td width="50%" class="field-name" style="padding: 3px;"><span class="required">*</span><?= GetMessage("SUP_SUBA_UI_LOGIN") ?>:</td>
						<td width="50%" style="padding: 3px;"><div id="USER_LOGIN_EXIST_error"></div><input id="USER_LOGIN" name="USER_LOGIN" maxlength="50" value="<?=htmlspecialcharsEx($_POST["USER_LOGIN"])?>" size="40" type="text"></td>
					</tr>
					</table>
				</div>
		</td>
	</tr>
	<?
		if($GENERATE_USER == "N")
		{
		?>
			<script>
			<!--
			ActivateEnableDisableUser(false);
			document.getElementById("GENERATE_USER").checked = false;
			document.getElementById("GENERATE_USER_NO").checked = true;
			//-->
			</script>
			<?
		}
	?>
		<?
		$tabControl->Buttons();
		?>
		<input <?if ($MAIN_RIGHT<"W") echo "disabled" ?> type="submit" value="<?= GetMessage("SUP_ACTIVATE_DO") ?>">
		<?
		$tabControl->End();
		?>
		</form>

		<?echo BeginNote()?>
		<span class="required">*</span> <?echo GetMessage("SUP_REG_NOTE")?>
		<?echo EndNote()?>
		<?
	}

//*********************************
//* Licence key
//*********************************
	$bLicenseNotFound = False;
	if ($arTypesUpdates !== false
		&& isset($arTypesUpdates["ERROR"])
		&& count($arTypesUpdates["ERROR"]) > 0)
	{
		for ($i = 0; $i < count($arTypesUpdates["ERROR"]); $i++)
		{
			if ($arTypesUpdates["ERROR"][$i]["@"]["TYPE"] == "LICENSE_NOT_FOUND")
			{
				$bLicenseNotFound = True;
				break;
			}
		}
	}

	$strLicenseKey_tmp = CUpdateSystem::GetLicenseKey();
	if (!$bLockUpdateSystemKernel
		&& (strlen($strLicenseKey_tmp) <= 0
		|| strtolower($strLicenseKey_tmp) == "demo"
		|| $bLicenseNotFound))
	{
		if(defined("DEMO") && DEMO == "Y")
		{
			$sMess = GetMessage("SUP_NO_KEY_PROMT", array("#URL#"=>"http://".(LANGUAGE_ID=="ru"? "www.bitrixsoft.ru" : "www.bitrixsoft.com" )."/bsm_register.php?dt=".UrlEncode($SiteExpireDate)));
			$sLink = '<a href="http://'.(LANGUAGE_ID=="ru"? "www.bitrixsoft.ru" : "www.bitrixsoft.com").'/bsm_register.php?dt='.UrlEncode($SiteExpireDate).'" target="_blank">'.GetMessage("SUP_NO_KEY_ACT").'</a>';
			echo CUpdateOutput::ShowBlock($sMess, $sLink, "icon-licence");
		}

		if(defined("DEMO") && DEMO == "Y")
			$sMess = GetMessage("SUP_NO_KEY_ENTER_PROMT", array("#URL#"=>"/bitrix/admin/settings.php"));
		else
			$sMess = GetMessage("SUP_NO_KEY_PROMT_SRC", array("#URL_SET#"=>"/bitrix/admin/settings.php", "#URL#"=>"http://www.bitrixsoft.".(LANGUAGE_ID=="ru"? "ru" : "com")."/support/"));
		ob_start();
?>
		<form method="POST" action="sysupdate.php">
			<input type="text" name="NEW_LICENSE_KEY" value="<?= (($MAIN_RIGHT<"W") ? "XXXXXXX" : htmlspecialchars($strLicenseKey_tmp)) ?>" size="30">
			<input <?if ($MAIN_RIGHT<"W") echo "disabled" ?> type="submit" value="<?= GetMessage("SUP_NO_KEY_ENTER_DO") ?>">
			<input type="hidden" name="TYPE" value="SAVE_KEY">
			<input type="hidden" name="STEP" value="1">
			<input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
		</form>
<?
		$sForm = ob_get_contents();
		ob_end_clean();
		echo CUpdateOutput::ShowBlock($sMess, $sForm, "icon-licence");
	}
?>

<?
//*********************************
//* Update of update system
//*********************************
	if ($arTypesUpdates!==false && isset($arTypesUpdates["UPDATE_SYSTEM"]))
	{
		$sLink = '<a href="sysupdate.php?TYPE=UPDUPD&amp;STEP=1&amp;lang='.LANGUAGE_ID.'">'.GetMessage("SUP_UPD_UPD_ACT").'</a>';
		echo CUpdateOutput::ShowBlock(GetMessage("SUP_UPDATE_UPDATE_PROMT"), $sLink, "icon-update");
	}

	$newLicense = COption::GetOptionString("main", "~new_license11_sign", "N");
	if (!$bLockUpdateSystemKernel && $newLicense == "Y")
	{
//*********************************
//* Если можно регистрировать продукт
//*********************************
		if ($arTypesUpdates!==false
			&& defined("DEMO") && DEMO=="Y"
			&& isset($arTypesUpdates["CLIENT"])
			&& !isset($arTypesUpdates["UPDATE_SYSTEM"])
			&& (
				$arTypesUpdates["CLIENT"]["@"]["ENC_TYPE"]=="F"
				|| $arTypesUpdates["CLIENT"]["@"]["ENC_TYPE"]=="E"))
		{
			$sLink = '<a href="sysupdate.php?TYPE=REGVER&amp;STEP=1&amp;lang='.LANGUAGE_ID.'">'.GetMessage("SUP_REG_ACT").'</a>';
			echo CUpdateOutput::ShowBlock(GetMessage("SUP_REG_PROMT"), $sLink, "icon-licence");
		}

//*********************************
//* Если есть обновления по модулям
//*********************************
		if ($arTypesUpdates!==false
			&& isset($arTypesUpdates["MODULES"])
			&& !isset($arTypesUpdates["UPDATE_SYSTEM"])
			&& IntVal($arTypesUpdates["MODULES"]["@"]["COUNT"])>0)
		{
			$sMess = GetMessage("SUP_MUPD_PROMT", array("#NUM#"=>$arTypesUpdates["MODULES"]["@"]["COUNT"], "#END#"=>CUpdateSystem::NumberEndings($arTypesUpdates["MODULES"]["@"]["COUNT"])));
			$sLink = '<a href="sysupdate.php?STEP=2&amp;TYPE=UPDATE&amp;lang='.LANGUAGE_ID.'">'.GetMessage("SUP_MUPD_ACT").'</a>';
			echo CUpdateOutput::ShowBlock($sMess, $sLink, "icon-main");
		}
		else
		{
			echo CUpdateOutput::ShowBlock(GetMessage("SUP_MUPD_NO_UPD"), "", "icon-main");
		}

//*********************************
//* Если можно закачать исходные тексты
//*********************************
		if ($arTypesUpdates!==false
			&& defined("ENCODE") && ENCODE=="Y"
			&& isset($arTypesUpdates["CLIENT"])
			&& !isset($arTypesUpdates["UPDATE_SYSTEM"])
			&& ($arTypesUpdates["CLIENT"]["@"]["ENC_TYPE"]=="F"))
		{
			$sLink = "";
			if(!isset($arTypesUpdates["MODULES"]) || IntVal($arTypesUpdates["MODULES"]["@"]["COUNT"])<=0)
				$sLink = '<a href="sysupdate.php?TYPE=LOADSRC&amp;STEP=1&amp;lang='.LANGUAGE_ID.'">'.GetMessage("SUP_SRC_ACT").'</a>';
			echo CUpdateOutput::ShowBlock(GetMessage("SUP_SRC_PROMT"), $sLink, "icon-sources");
		}

//*********************************
//* Если есть обновления по языкам
//*********************************
		if ($arTypesUpdates!==false
			&& isset($arTypesUpdates["LANGS"])
			&& !isset($arTypesUpdates["UPDATE_SYSTEM"])
			&& (IntVal($arTypesUpdates["LANGS"]["@"]["COUNT"])>0
				|| IntVal($arTypesUpdates["LANGS"]["@"]["OTHER_COUNT"])>0)
			)
		{
			$sMess = "";
			if (IntVal($arTypesUpdates["LANGS"]["@"]["COUNT"])>0)
				$sMess .= GetMessage("SUP_LUPD_PROMT", array("#END#"=>CUpdateSystem::NumberEndings($arTypesUpdates["LANGS"]["@"]["COUNT"]), "#NUM#"=>$arTypesUpdates["LANGS"]["@"]["COUNT"]));
			if (IntVal($arTypesUpdates["LANGS"]["@"]["OTHER_COUNT"])>0)
				$sMess .= ($sMess <> ""? "<br><br>":"").GetMessage("SUP_LUPD_PROMT_OTHER");
			$sLink = '<a href="sysupdate.php?STEP=2&amp;TYPE=LANGS&amp;lang='.LANGUAGE_ID.'">'.GetMessage("SUP_LUPD_ACT").'</a>';
			echo CUpdateOutput::ShowBlock($sMess, $sLink, "icon-lang");
		}

//*********************************
//* Если есть обновления по помощи
//*********************************
		if ($arTypesUpdates!==false
			&& isset($arTypesUpdates["HELPS"])
			&& !isset($arTypesUpdates["UPDATE_SYSTEM"])
			&& (IntVal($arTypesUpdates["HELPS"]["@"]["COUNT"])>0
				|| IntVal($arTypesUpdates["HELPS"]["@"]["OTHER_COUNT"])>0)
			)
		{
			$sMess = "";
			if (IntVal($arTypesUpdates["HELPS"]["@"]["COUNT"])>0)
				$sMess .= GetMessage("SUP_HUPD_PROMT", array("#END#"=>CUpdateSystem::NumberEndings($arTypesUpdates["HELPS"]["@"]["COUNT"], LANGUAGE_ID, array(GetMessage("SUP_WORD_YAZIK_END1"), GetMessage("SUP_WORD_YAZIK_END2"), GetMessage("SUP_WORD_YAZIK_END3"), GetMessage("SUP_WORD_YAZIK_END4"))), "#NUM#"=>$arTypesUpdates["HELPS"]["@"]["COUNT"]));
			if (IntVal($arTypesUpdates["HELPS"]["@"]["OTHER_COUNT"])>0)
				$sMess .= ($sMess <> ""? "<br><br>":"").GetMessage("SUP_HUPD_PROMT_OTHER");
			$sLink = '<a href="sysupdate.php?STEP=2&amp;TYPE=HELP&amp;lang='.LANGUAGE_ID.'">'.GetMessage("SUP_HUPD_ACT").'</a>';
			echo CUpdateOutput::ShowBlock($sMess, $sLink, "icon-help");
		}

//*********************************
//* Add site licence
//*********************************
		if ($arTypesUpdates!==false
			&& isset($arTypesUpdates["CLIENT"])
			&& !isset($arTypesUpdates["UPDATE_SYSTEM"])
			&& count($arTypesUpdates["CLIENT"])>0
			&& $arTypesUpdates["CLIENT"]["@"]["RESERVED"]!="Y")
		{
			$arTypesUpdates["CLIENT"]["@"]["MAX_SITES"] = IntVal($arTypesUpdates["CLIENT"]["@"]["MAX_SITES"]);
			$sMess = "";
			if ($arTypesUpdates["CLIENT"]["@"]["MAX_SITES"] > 0)
				$sMess .= GetMessage("SUP_CHECK_PROMT", array("#END#"=>CUpdateSystem::NumberEndings($arTypesUpdates["CLIENT"]["@"]["MAX_SITES"], LANGUAGE_ID, array(GetMessage("SUP_WORD_SAIT_END1"), GetMessage("SUP_WORD_SAIT_END2"), GetMessage("SUP_WORD_SAIT_END3"), GetMessage("SUP_WORD_SAIT_END4"))), "#NUM#"=>$arTypesUpdates["CLIENT"]["@"]["MAX_SITES"]));
			else
				$sMess .= GetMessage("SUP_CHECK_PROMT_2");
			$sMess .= "<br><br>";
			$sMess .= GetMessage("SUP_CHECK_PROMT_1");
			$sLink = '<a href="sysupdate.php?TYPE=ADD_SITE&amp;STEP=1&amp;lang='.LANGUAGE_ID.'">'.GetMessage("SUP_CHECK_ACT").'</a>';
			echo CUpdateOutput::ShowBlock($sMess, $sLink, "icon-licence");
		}
	}
	elseif (!$bLockUpdateSystemKernel)
	{
//*****************************
//* Licence Agreement 
//*****************************
		if(LANGUAGE_ID == "ru")
			$license_text_file = "http://www.bitrixsoft.ru/license-ru.htm";
		else
			$license_text_file = "http://www.bitrixsoft.com/license-en.htm";
		$sMess = GetMessage("SUP_LICENSE_PROMT").'<br><br><iframe name="license_text" src="'.$license_text_file.'" style="width:500px; height:250px; display:block;"></iframe>';

		ob_start();
?>
		<form method="POST" action="sysupdate.php" name="license_form">
			<input name="agree_license" type="checkbox" value="Y" id="agree_license_id" onclick="document.license_form.SUP_LICENSE_ENTER_DON.disabled=<?if($MAIN_RIGHT < "W"):?>true<?else:?>!this.checked<?endif;?>">
			<label for="agree_license_id"><?= GetMessage("LICENSE_AGREE_PROMT") ?></label>
			<br>
			<input disabled id="SUP_LICENSE_ENTER_DO" name="SUP_LICENSE_ENTER_DON" type="submit" value="<?= GetMessage("SUP_LICENSE_ENTER_DO") ?>">
			<input type="hidden" name="TYPE" VALUE="AGREE_LICENSE_ACT">
			<input type="hidden" name="STEP" VALUE="1">
			<input type="hidden" name="lang" VALUE="<?= LANGUAGE_ID ?>">
		</form>
<?
		$sForm = ob_get_contents();
		ob_end_clean();
		echo CUpdateOutput::ShowBlock($sMess, $sForm, "icon-main");
	}

//*****************************
//* Stable or beta version 
//*****************************
	if (!$bLockUpdateSystemKernel)
	{
		if($stableVersionsOnly == "N")
			$sMess = GetMessage("SUP_STABLE_OFF_PROMT");
		else
			$sMess = GetMessage("SUP_STABLE_ON_PROMT");
		$sMess .= '<br><br>'.GetMessage("SUP_STABLE_PROMT");
		
		$sLink = '<a href="sysupdate.php?TYPE=TURN_STABILITY&amp;STEP=1&amp;lang='.LANGUAGE_ID.'">'.($stableVersionsOnly == "N"? GetMessage("SUP_STABLE_TURN_ON") : GetMessage("SUP_STABLE_TURN_OFF")).'</a>';
		
		echo CUpdateOutput::ShowBlock($sMess, $sLink, "icon-beta");
	}
//*****************************
//* Subscription for updates 
//*****************************
	if (!$bLockUpdateSystemKernel)
	{
		if(isset($arTypesUpdates["CLIENT"]["@"]["SUBSCR_EMAIL"]) && $arTypesUpdates["CLIENT"]["@"]["SUBSCR_EMAIL"] <> "")
			$sMess = GetMessage("SUP_SUBSCR_ALREADY_U");
		else
			$sMess = GetMessage("SUP_SUBSCR_NEW_U");

		ob_start();
	?>
		<form method="POST" action="sysupdate.php">
			<input type="text" name="SUBSCR_EMAIL" value="<?= ((isset($arTypesUpdates["CLIENT"]["@"]["SUBSCR_EMAIL"])) ? htmlspecialchars($arTypesUpdates["CLIENT"]["@"]["SUBSCR_EMAIL"]) : "") ?>" size="30">
			<?if (isset($arTypesUpdates["CLIENT"]["@"]["SUBSCR_EMAIL"]) && $arTypesUpdates["CLIENT"]["@"]["SUBSCR_EMAIL"] <> ""):?>
			<input <?if ($MAIN_RIGHT<"W") echo "disabled" ?> type="submit" value="<?= GetMessage("SUP_SUBSCR_ALREADY_CHANGE_U") ?>">
			<input <?if ($MAIN_RIGHT<"W") echo "disabled" ?> type="submit" name="SUBSCR_DELETE" value="<?= GetMessage("SUP_SUBSCR_ALREADY_DEL_U") ?>">
			<?else:?>
			<input <?if ($MAIN_RIGHT<"W") echo "disabled" ?> type="submit" value="<?= GetMessage("SUP_SUBSCR_ALREADY_ADD_U") ?>">
			<?endif;?>
			<input type="hidden" name="TYPE" value="SUBSCR_UPDATE">
			<input type="hidden" name="STEP" value="1">
			<input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
		</form>
	<?	
		$sForm = ob_get_contents();
		ob_end_clean();
		
		echo CUpdateOutput::ShowBlock($sMess, $sForm, "icon-subscribe");
	}
?>

<p class="update-dots">
	<?= GetMessage("SUP_1STEP_NOTES1") ?>
	<br><br>
	<?= GetMessage("SUP_1STEP_NOTES2") ?>
</p>

<?
}

/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@   ФОРМЫ МОДУЛЕЙ   @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/

//**********************************************************************/
//******   ШАГ 2  /ФОРМА/   ********************************************/
//**********************************************************************/
if ($TYPE=="UPDATE" && $STEP==2 && $MAIN_RIGHT=="W")
{
	// Если пришли ошибки с сервера
	if ($arModulesUpdates!==false
		&& isset($arModulesUpdates["ERROR"])
		&& count($arModulesUpdates["ERROR"])>0)
	{
		for ($i = 0; $i < count($arModulesUpdates["ERROR"]); $i++)
			$strErrorMessage .= ($arModulesUpdates["ERROR"][$i]["@"]["TYPE"] <> ""? "[".$arModulesUpdates["ERROR"][$i]["@"]["TYPE"]."] " : "").$arModulesUpdates["ERROR"][$i]["#"]."<br>";
	}

	if (!$arModulesUpdates
		|| !isset($arModulesUpdates["MODULES"])
		|| !isset($arModulesUpdates["MODULES"]["#"]["MODULE"])
		|| !is_array($arModulesUpdates["MODULES"]["#"]["MODULE"])
		|| count($arModulesUpdates["MODULES"]["#"]["MODULE"])<=0)
	{
		$strErrorMessage .= GetMessage("SUP_STEP2_NO_MODULES")."<br>";
	}

	if($strErrorMessage <> "")
		CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("SUP_ERRORS"), "TYPE"=>"ERROR", "DETAILS"=>$strErrorMessage, "HTML"=>true));

	ob_start();
	$sMess = GetMessage("SUP_STEP2_UPD_PROMT");
?>
	<table border="0" cellspacing="0" cellpadding="0" width="100%">
	<tr>
		<td style="padding:0px;">
			<form method="post" action="sysupdate.php" onsubmit="return ValidateForm(this);">
				<input type="submit" name="load_button" id="load_button" value="<?= GetMessage("SUP_STEP2_LOAD_BTN") ?>" <?if (!isset($arModulesUpdates["MODULES"]) || !isset($arModulesUpdates["MODULES"]["#"]["MODULE"]) || !is_array($arModulesUpdates["MODULES"]["#"]["MODULE"]) || !count($arModulesUpdates["MODULES"]["#"]["MODULE"])>0) echo "disabled";?>>
				<input type="hidden" name="load_modules" id="load_modules" value="">
				<input type="hidden" name="STEP" value="3">
				<input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
				<input type="hidden" name="TYPE" value="UPDATE">
			</form>
		</td>
		<td align="right" style="padding:0px;">
			<div id="total_size"></div>
		</td>
	</tr>
	</table>
	<br>
<?
}

//**********************************************************************/
//******   ШАГ 3  /ФОРМА/   ********************************************/
//**********************************************************************/
if ($TYPE=="UPDATE" && $STEP==3 && $MAIN_RIGHT=="W")
{
	// Если пришли ошибки с сервера
	if ($arModulesUpdates!==false
		&& isset($arModulesUpdates["ERROR"])
		&& count($arModulesUpdates["ERROR"])>0)
	{
		for ($i = 0; $i < count($arModulesUpdates["ERROR"]); $i++)
			$strErrorMessage .= ($arModulesUpdates["ERROR"][$i]["@"]["TYPE"] <> ""? "[".$arModulesUpdates["ERROR"][$i]["@"]["TYPE"]."] " : "").$arModulesUpdates["ERROR"][$i]["#"]."<br>";
	}

	if (!$arModulesUpdates
		|| !isset($arModulesUpdates["MODULES"])
		|| !isset($arModulesUpdates["MODULES"]["#"]["MODULE"])
		|| !is_array($arModulesUpdates["MODULES"]["#"]["MODULE"])
		|| count($arModulesUpdates["MODULES"]["#"]["MODULE"])<=0)
	{
		$strErrorMessage .= GetMessage("SUP_STEP3_NO_MODULES")."<br>";
	}

	if($strErrorMessage <> "")
		CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("SUP_ERRORS"), "TYPE"=>"ERROR", "DETAILS"=>$strErrorMessage, "HTML"=>true));

	ob_start();
	$sMess = GetMessage("SUP_STEP3_UPD_PROMT");
?>
	<table border="0" cellspacing="0" cellpadding="0" width="100%">
	<tr>
		<td style="padding:0px;">
			<form method="post" action="sysupdate.php" onsubmit="return ValidateForm(this);">
				<input type="submit" name="load_button" id="load_button" value="<?= GetMessage("SUP_STEP3_LOAD_BTN") ?>" <?if (!isset($arModulesUpdates["MODULES"]) || !isset($arModulesUpdates["MODULES"]["#"]["MODULE"]) || !is_array($arModulesUpdates["MODULES"]["#"]["MODULE"]) || !count($arModulesUpdates["MODULES"]["#"]["MODULE"])>0) echo "disabled";?>>
				<input type="hidden" name="load_modules" id="load_modules" value="">
				<input type="hidden" name="temporary_updates_dir" value="<?= htmlspecialchars($temporary_updates_dir) ?>">
				<input type="hidden" name="UNIID" value="<?echo md5(time());?>">
				<input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
				<input type="hidden" name="STEP" value="4">
				<input type="hidden" name="TYPE" value="UPDATE">
			</form>
		</td>
		<td align="right" style="padding:0px;">
			<div id="total_size"></div>
		</td>
	</tr>
	</table>
	<br>
<?
}

//**********************************************************************/
//******   ШАГИ 2 и 3  /ФОРМА/   ***************************************/
//**********************************************************************/
if ($TYPE=="UPDATE" && ($STEP==2 || $STEP==3) && $MAIN_RIGHT=="W")
{
	?>
	<script language="javascript">
	var arModules = new Array();

	<?
	if ($arModulesUpdates
		&& isset($arModulesUpdates["MODULES"])
		&& is_array($arModulesUpdates["MODULES"]["#"]["MODULE"])
		&& count($arModulesUpdates["MODULES"]["#"]["MODULE"])>0)
	{
		$arModuleCurVersions = array();
		for ($i = 0; $i < count($arModulesUpdates["MODULES"]["#"]["MODULE"]); $i++)
		{
			?>
			var arDepends = new Array();
			var arDepends1 = new Array();
			<?
			$arDepends = array();
			for ($j = 0; $j < count($arModulesUpdates["MODULES"]["#"]["MODULE"]); $j++)
			{
				if ($i != $j)
				{
					if (is_array($arModulesUpdates["MODULES"]["#"]["MODULE"][$j]["#"]["VERSION_CONTROL"]))
					{
						for ($k = 0; $k < count($arModulesUpdates["MODULES"]["#"]["MODULE"][$j]["#"]["VERSION_CONTROL"]); $k++)
						{
							if ($arModulesUpdates["MODULES"]["#"]["MODULE"][$j]["#"]["VERSION_CONTROL"][$k]["@"]["MODUL"]==$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"])
							{
								if (!array_key_exists($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"], $arModuleCurVersions))
									$arModuleCurVersions[$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"]] = CUpdateSystem::GetModuleVersion($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"]);

								$i_cur_version = $arModuleCurVersions[$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"]];
								if (!$i_cur_version
									|| strlen($i_cur_version)<=0
									|| CUpdateSystem::CompareVersions(
										$arModulesUpdates["MODULES"]["#"]["MODULE"][$j]["#"]["VERSION_CONTROL"][$k]["@"]["VERSION"],
										$i_cur_version
										) > 0)
								{
									$arDepends[] = $j;
								}
							}
						}
					}
				}
			}
			if (count($arDepends)>0)
			{
				for ($j = 0; $j < count($arDepends); $j++)
				{
					?>arDepends[<?= $j ?>]=<?= $arDepends[$j] ?>;<?
				}
			}

			$arDepends1 = array();
			if (is_array($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["VERSION_CONTROL"]))
			{
				for ($j = 0; $j < count($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["VERSION_CONTROL"]); $j++)
				{
					if (!array_key_exists($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["VERSION_CONTROL"][$j]["@"]["MODUL"], $arModuleCurVersions))
						$arModuleCurVersions[$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["VERSION_CONTROL"][$j]["@"]["MODUL"]] = CUpdateSystem::GetModuleVersion($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["VERSION_CONTROL"][$j]["@"]["MODUL"]);

					$i_cur_version = $arModuleCurVersions[$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["VERSION_CONTROL"][$j]["@"]["MODUL"]];
					if (!$i_cur_version
						|| strlen($i_cur_version)<=0
						|| CUpdateSystem::CompareVersions(
							$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["VERSION_CONTROL"][$j]["@"]["VERSION"],
							$i_cur_version
							) > 0)
					{
						for ($k = 0; $k < count($arModulesUpdates["MODULES"]["#"]["MODULE"]); $k++)
						{
							if ($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["VERSION_CONTROL"][$j]["@"]["MODUL"]==$arModulesUpdates["MODULES"]["#"]["MODULE"][$k]["@"]["ID"])
							{
								$arDepends1[] = $k;
								break;
							}
						}
					}
				}
			}
			if (count($arDepends1)>0)
			{
				for ($j = 0; $j < count($arDepends1); $j++)
				{
					?>arDepends1[<?= $j ?>]=<?= $arDepends1[$j] ?>;<?
				}
			}
			?>

			arModules[<?= $i ?>] = Array(
				"<?= $arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"] ?>",
				"<?= $arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["NAME"] ?>",
				"<?= $arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["VERSION"] ?>",
				<?= $arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["SIZE"] ?>,
				"<?
				$strModuleDescr = "";
				if ($STEP==2)
				{
					if (is_array($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["DESCRIPTION"]))
					{
						for ($j = 0; $j < count($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["DESCRIPTION"]); $j++)
						{
							if (strlen($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["DESCRIPTION"][$j]["@"]["VERSION"])>0)
								$strModuleDescr .= "<br><b>".$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["DESCRIPTION"][$j]["@"]["VERSION"]."</b><br>";
							$strModuleDescr .= $arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["DESCRIPTION"][$j]["#"]."<br>";
						}
					}
				}
				if (is_array($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["VERSION_CONTROL"]))
				{
					$strModuleDescr .= "<br><b>".GetMessage("SUP_UPD_DESCR_VERC").":</b><br>";
					for ($j = 0; $j < count($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["VERSION_CONTROL"]); $j++)
					{
						$strModuleDescr .= "-&nbsp;".$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["VERSION_CONTROL"][$j]["@"]["MODUL"]." ";
						$strModuleDescr .= str_replace("#VERS#", $arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["VERSION_CONTROL"][$j]["@"]["VERSION"], GetMessage("SUP_UPD_DESCR_VERC_N"))." ";
						if (strlen($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["VERSION_CONTROL"][$j]["@"]["NOTES"])>0)
						{
							$strModuleDescr .= "<small>(".$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["VERSION_CONTROL"][$j]["@"]["NOTES"].")</small>";
						}
						$strModuleDescr .= "<br>";
					}
				}
				$strModuleDescr = preg_replace("#</?pre>#i", " ", $strModuleDescr);
				$strModuleDescr = preg_replace("/[\s\n\r]+/", " ", $strModuleDescr);
				$strModuleDescr = addslashes($strModuleDescr);
				echo CUpdateSystem::InsertSpaces($strModuleDescr, 80);
				?>",
				"Y",
				"<?= $arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["NEW"] ?>",
				arDepends,
				arDepends1
				);
			<?
		}
	}
	?>
	</script>

	<!--<hr align="left" width="98%" size="1" color="#CCCCCC">-->
	<!--<div ID="updates_list_div" style="position: absolute; height: 300px; z-index: 10;">-->
	<iframe ID="updates_list" name="updates_list" src="sys_update_list.php?lang=<?echo LANG?>" width="100%" height="400"></iframe>
	<!--</div>-->

	<script language="javascript">
	var ns4 = (document.layers) ? true : false;
	var ie4 = (document.all) ? true : false;

	var oUpdatesList;
	var oUpdatesTbl;

	var totalNumUpd = arModules.length;
	var totalSizeUpd = 0;
	for (i = 0; i < arModules.length; i++)
	{
		totalSizeUpd = totalSizeUpd + arModules[i][3];
	}

	degKoeff = 1000;
	degKoeffName = "Kb";
	if (1*totalSizeUpd>500000)
	{
		degKoeff = 1000000;
		degKoeffName = "Mb";
	}

	var oTotalSizeInfo = (ie4) ? document.all['total_size'] : document.getElementById('total_size');
	var iSize_tmp = Math.round((totalSizeUpd/degKoeff)*10)/10;
	if (iSize_tmp<=0)
	{
		iSize_tmp = 0.1;
	}
	oTotalSizeInfo.innerHTML = "<?= GetMessage("SUP_TOTAL_UPDS") ?>: "+totalNumUpd+" = "+iSize_tmp+" "+degKoeffName;


	// Modules to load
	var oLoadModules = (ie4) ? document.all['load_modules'] : document.getElementById('load_modules');
	oLoadModules.value = MakeLoadModulesList();

	var oLoadButton = (ie4) ? document.all['load_button'] : document.getElementById('load_button');
	if (totalNumUpd<=0)
	{
		oLoadButton.disabled = true;
	}
	else
	{
		oLoadButton.disabled = false;
	}


	function ValidateForm(form)
	{
		oLoadButton.disabled = true;
		return true;
	}


	function MakeLoadModulesList()
	{
		var strLoadModulesUpd_tmp = "";
		for (i = 0; i < arModules.length; i++)
		{
			if (arModules[i][5]=="Y")
			{
				if (strLoadModulesUpd_tmp.length>0)
					strLoadModulesUpd_tmp = strLoadModulesUpd_tmp + ",";

				strLoadModulesUpd_tmp = strLoadModulesUpd_tmp + arModules[i][0];
			}
		}
		return strLoadModulesUpd_tmp;
	}
	// End modules to load


	function findlayer(name,doc)
	{
		var i,layer;
		for (i = 0; i < doc.layers.length; i++)
		{
			layer=doc.layers[i];
			if (layer.name==name)
				return layer;
			if (layer.document.layers.length>0)
				if ((layer=findlayer(name,layer.document))!=null)
					return layer;
		}
		return null;
	}


	// Manage updates list
	function MakeUpdateText(ind, enbl)
	{
		var strTxt = "";
		if (enbl=="Y")
		{
			strTxt += '<table border="0" width="100%" cellspacing="1" cellpadding="3"><tr><td width="100%">';
			strTxt += '<font class="text">';

			strTxt += '<font class="titletext"><?= GetMessage("SUP_MODULE") ?> "'+arModules[ind][1]+'" ('+arModules[ind][0]+')</font>';
			if (arModules[ind][6]=="Y")
			{
				strTxt += '<font color="#00AA00"><b> - <?= GetMessage("SUP_NEW") ?>!</b></font>';
			}
			strTxt += '<br>';
			strTxt += '<?= GetMessage("SUP_LAST_VERSION") ?>: '+arModules[ind][2]+'<br>';

			degKoeff = 1000;
			degKoeffName = "Kb";
			if (1*arModules[ind][3]>500000)
			{
				degKoeff = 1000000;
				degKoeffName = "Mb";
			}
			var iSize_tmp = Math.round((arModules[ind][3]/degKoeff)*10)/10;
			if (iSize_tmp<=0)
			{
				iSize_tmp = 0.1;
			}

			strTxt += '<?= GetMessage("SUP_LOAD_SIZE") ?>: '+iSize_tmp+' '+degKoeffName+'<br>';

			strTxt += '</font>';
			strTxt += '</td><td align="right" width="0%">';

			strTxt += '<input type="button" value="<?= GetMessage("SUP_DELETE_FROM_LOAD") ?>" OnClick="DoEvent(\'DisableUpdate('+ind+')\')">';

			strTxt += '</td></tr><tr><td>';
			strTxt += '<font class="text">';

			strTxt += arModules[ind][4];

			strTxt += '</font>';
			strTxt += '</td></tr></table>';
			strTxt += '<hr width="100%" size="1" color="#CCCCCC">';
		}
		else
		{
			strTxt += '<table border="0" width="100%" cellspacing="1" cellpadding="3"><tr><td disabled width="100%">';

			strTxt += '<font class="text"><font color="#AAAAAA"><?= GetMessage("SUP_MODULE") ?> "'+arModules[ind][1]+'" ('+arModules[ind][0]+')</font></font><br>';

			strTxt += '</font>';
			strTxt += '</td><td align="right" width="0%">';

			strTxt += '<input type="button" value="<?= GetMessage("SUP_ADD_TO_LOAD") ?>" OnClick="DoEvent(\'EnableUpdate('+ind+')\')">';

			strTxt += '</td></tr></table>';


			strTxt += '<hr width="100%" size="1" color="#CCCCCC">';
		}
		return strTxt;
	}

	function updates_list_DisableUpdate(ind)
	{
		arModules[ind][5] = "N";
		oLoadModules.value = MakeLoadModulesList();

		totalNumUpd = totalNumUpd - 1;
		totalSizeUpd = totalSizeUpd - arModules[ind][3];

		degKoeff = 1000;
		degKoeffName = "Kb";
		if (1*totalSizeUpd>500000)
		{
			degKoeff = 1000000;
			degKoeffName = "Mb";
		}
		var iSize_tmp = Math.round((totalSizeUpd/degKoeff)*10)/10;
		if (iSize_tmp<=0)
		{
			iSize_tmp = 0.1;
		}

		oTotalSizeInfo.innerHTML = "<?= GetMessage("SUP_TOTAL_UPDS") ?>: "+totalNumUpd+" = "+iSize_tmp+" "+degKoeffName;
		if (totalNumUpd<=0)
		{
			oLoadButton.disabled = true;
		}

		oRow = oUpdatesTbl.rows[ind];
		oRow.cells[0].innerHTML = MakeUpdateText(ind, "N");


		if (arModules[ind][7].length > 0)
		{
			var i1;
			for (i1 = 0; i1 < arModules[ind][7].length; i1++)
			{
				if (arModules[arModules[ind][7][i1]][5]=="Y")
				{
					updates_list_DisableUpdate(arModules[ind][7][i1]);
					alert('<?= GetMessage("SUP_ALERT_PART1") ?> "'+arModules[arModules[ind][7][i1]][1]+'" ('+arModules[arModules[ind][7][i1]][0]+') <?= GetMessage("SUP_ALERT_PART2") ?> "'+arModules[ind][1]+'" ('+arModules[ind][0]+'). <?= GetMessage("SUP_ALERT_PART3") ?> "'+arModules[arModules[ind][7][i1]][1]+'" ('+arModules[arModules[ind][7][i1]][0]+') <?= GetMessage("SUP_ALERT_PART4") ?>.');
				}
			}
		}
	}

	function updates_list_EnableUpdate(ind)
	{
		arModules[ind][5] = "Y";
		oLoadModules.value = MakeLoadModulesList();

		totalNumUpd = totalNumUpd + 1;
		totalSizeUpd = totalSizeUpd + arModules[ind][3];

		degKoeff = 1000;
		degKoeffName = "Kb";
		if (1*totalSizeUpd>500000)
		{
			degKoeff = 1000000;
			degKoeffName = "Mb";
		}
		var iSize_tmp = Math.round((totalSizeUpd/degKoeff)*10)/10;
		if (iSize_tmp<=0)
		{
			iSize_tmp = 0.1;
		}

		oTotalSizeInfo.innerHTML = "<?= GetMessage("SUP_TOTAL_UPDS") ?>: "+totalNumUpd+" = "+iSize_tmp+" "+degKoeffName;
		if (totalNumUpd>0)
		{
			oLoadButton.disabled = false;
		}

		oRow = oUpdatesTbl.rows[ind];
		oRow.cells[0].innerHTML = MakeUpdateText(ind, "Y");


		if (arModules[ind][8].length > 0)
		{
			var i1;
			for (i1 = 0; i1 < arModules[ind][8].length; i1++)
			{
				if (arModules[arModules[ind][8][i1]][5]!="Y")
				{
					updates_list_EnableUpdate(arModules[ind][8][i1]);
					alert('<?= GetMessage("SUP_ALERT1_PART1") ?> "'+arModules[arModules[ind][8][i1]][1]+'" ('+arModules[arModules[ind][8][i1]][0]+') <?= GetMessage("SUP_ALERT1_PART2") ?> "'+arModules[ind][1]+'" ('+arModules[ind][0]+'). <?= GetMessage("SUP_ALERT1_PART3") ?> "'+arModules[arModules[ind][8][i1]][1]+'" ('+arModules[arModules[ind][8][i1]][0]+') <?= GetMessage("SUP_ALERT1_PART4") ?>.');
				}
			}
		}
	}

	var XXXXXXXXX = 0;
	function PrepareUpdatesList()
	{
		if (XXXXXXXXX > 0) return;
		XXXXXXXXX = 1;

//		ResizeUpdatesList();

		if (ns4)
		{
			oUpdatesList = document.getElementById("updates_list");
			oUpdatesTbl = oUpdatesList.document.getElementById('updates_items');
		}
		else if (ie4)
		{
			oUpdatesList = document.frames("updates_list");
			oUpdatesTbl = oUpdatesList.document.all['updates_items'];
		}
		else
		{
			oUpdatesList = document.getElementById("updates_list");
			oUpdatesTbl = oUpdatesList.contentDocument.getElementById('updates_items');
		}

		if (oUpdatesTbl)
		{
			for (i = 0; i < arModules.length; i++)
			{
				oRow = oUpdatesTbl.insertRow(i);
				oCell = oRow.insertCell(0);
				oCell.innerHTML = MakeUpdateText(i, "Y");
			}
		}
		else
		{
			XXXXXXXXX = 0;
			setTimeout("PrepareUpdatesList();", 2000);
		}
	}

	function ResizeUpdatesList()
	{
//		oUpdatesListDiv = (ns4) ? findlayer('updates_list_div', document) : (ie4) ? document.all['updates_list_div'] : document.getElementById('updates_list_div');

		var oUpdatesListDiv = (ie4) ? document.all['updates_list'] : document.getElementById('updates_list');

		if (ns4)
		{
			newHeight = window.innerHeight - pageYOffset - 27;
//			if (window.innerHeight<700 || newHeight<200)
//				oUpdatesListDiv.height = window.innerHeight-70;
//			else
			if (newHeight>200)
				oUpdatesListDiv.height = newHeight;
		}
		else if (ie4)
		{
			newHeight = document.body.clientHeight - oUpdatesListDiv.offsetTop - 27;
//			if (document.body.clientHeight<700 || newHeight<200)
//				oUpdatesListDiv.style.height = document.body.clientHeight-70;
//			else
			if (newHeight>200)
				oUpdatesListDiv.style.height = newHeight;
		}
		else
		{
			newHeight = window.innerHeight-pageYOffset - 27;
//			if (window.innerHeight<700 || newHeight<200)
//				oUpdatesListDiv.style.height = window.innerHeight-50;
//			else
			if (newHeight>200)
				oUpdatesListDiv.style.height = newHeight;

			newWidth = window.innerWidth-pageXOffset-190;
			oUpdatesListDiv.style.width = newWidth;
		}
	}
	// End manage updates list

	window.onload = PrepareUpdatesList;
//	window.onresize = ResizeUpdatesList;
	setTimeout("PrepareUpdatesList();", 2000);
	</script>
	<?
	$sForm = ob_get_contents();
	ob_end_clean();

	echo CUpdateOutput::ShowBlock($sMess, $sForm, "icon-main");
}

//**********************************************************************/
//******   ШАГ 4  /ФОРМА/   ********************************************/
//**********************************************************************/
if ($TYPE=="UPDATE" && $STEP==4 && $MAIN_RIGHT=="W")
{
	if($strErrorMessage <> "")
		CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("SUP_ERRORS"), "TYPE"=>"ERROR", "DETAILS"=>$strErrorMessage, "HTML"=>true));

	echo BeginNote('width="100%"');
	echo GetMessage("SUP_STEP4_UPD_PROMT");
	echo EndNote();

	$arServerReport = array();

	if ($arModulesUpdates
		&& isset($arModulesUpdates["MODULES"])
		&& is_array($arModulesUpdates["MODULES"]["#"]["MODULE"])
		&& count($arModulesUpdates["MODULES"]["#"]["MODULE"])>0)
	{
		$strSuccessModules = "";
		$strErrorModules = "";
		$strNoneModules = "";

		for ($i = 0; $i < count($arModulesUpdates["MODULES"]["#"]["MODULE"]); $i++)
		{
			if (array_key_exists($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"], $arErrorModules))
			{
				$strErrorModules .= "<b>".$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["NAME"]."</b> (".$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"].")";
				if ($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["NEW"]=="Y")
					$strErrorModules .= " - <a href=\"module_admin.php?lang=".LANG."&id=".urlencode($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"])."&".bitrix_sessid_get()."&install=".urlencode(GetMessage("SUP_STEP4_INST"))."\" target=\"_blank\" title=\"".GetMessage("SUP_STEP4_INST_ALT")."\">".GetMessage("SUP_STEP4_INST_DO")."</a>";
				$strErrorModules .= "<br><i>".$arErrorModules[$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"]]."</i><br>";

				CUpdateSystem::AddMessage2Log(
					str_replace("#VERS#", $arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["VERSION"], str_replace("#MODULE#", $arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["NAME"]." (".$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"].")", GetMessage("SUP_STEP4_UPD_LOG"))).
					$arErrorModules[$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"]],
					"UPD_ERROR");
				$arServerReport[$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"]] = array($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["VERSION"], $arErrorModules[$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"]]);
			}
			elseif (array_key_exists($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"], $arSuccessModules))
			{
				$strSuccessModules .= "<b>".$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["NAME"]."</b> (".$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"].")";
				if ($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["NEW"]=="Y")
					$strSuccessModules .= " - <a href=\"module_admin.php?lang=".LANG."&id=".urlencode($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"])."&".bitrix_sessid_get()."&install=".urlencode(GetMessage("SUP_STEP4_INST"))."\" target=\"_blank\" title=\"".GetMessage("SUP_STEP4_INST_ALT")."\">".GetMessage("SUP_STEP4_INST_DO")."</a>";
				$strSuccessModules .= "<br>";

				$strModuleDescr = "";
				if (is_array($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["DESCRIPTION"]))
				{
					for ($j = 0; $j < count($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["DESCRIPTION"]); $j++)
					{
						if (strlen($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["DESCRIPTION"][$j]["@"]["VERSION"])>0)
							$strModuleDescr .= "<br><b>".$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["DESCRIPTION"][$j]["@"]["VERSION"]."</b><br>";
						$strModuleDescr .= $arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["#"]["DESCRIPTION"][$j]["#"]."<br>";
					}
				}

				$strModuleDescr = preg_replace("#</?pre>#i", " ", $strModuleDescr);
				$strModuleDescr = preg_replace("/[\s\n\r]+/", " ", $strModuleDescr);
				$strModuleDescr = addslashes($strModuleDescr);

				CUpdateSystem::AddMessage2Log(
					str_replace("#VERS#", $arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["VERSION"], str_replace("#MODULE#", $arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["NAME"]." (".$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"].")", GetMessage("SUP_STEP4_UPD_LOG")))."<br>".$strModuleDescr,
					"UPD_SUCCESS");

				$arServerReport[$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"]] = array($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["VERSION"], "S");
			}
			else
			{
				$strNoneModules .= "<b>".$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["NAME"]."</b> (".$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"].")<br>";
				$arServerReport[$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"]] = array($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["VERSION"], "N");
			}
		}

		$sMess = "";
		if($strSuccessModules <> "")
			$sMess .= "<p>".GetMessage("SUP_STEP4_SUCCESS").":<br>".$strSuccessModules."</p>";
		if($strErrorModules <> "")
			$sMess .= "<p>".GetMessage("SUP_STEP4_ERROR").":<br>".$strErrorModules."</p>";
		if($strNoneModules <> "")
			$sMess .= "<p>".GetMessage("SUP_STEP4_NONE").":<br>".$strNoneModules."</p>";

		echo CUpdateOutput::ShowBlock($sMess, "", "icon-main");
	}
	CUpdateSystem::EraseOldFolders(2);
	CUpdateSystem::Report2Server($arServerReport);
}
//**********************************************************************/
//******   КОНЕЦ ШАГОВ  /ФОРМА/   **************************************/
//**********************************************************************/

/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@   ФОРМЫ ЯЗЫКОВ   @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/

//**********************************************************************/
//******   ШАГ 2  /ЯЗЫКИ/   ********************************************/
//**********************************************************************/
if ($TYPE=="LANGS" && $STEP==2 && $MAIN_RIGHT=="W")
{
	// Если пришли ошибки с сервера
	if ($arLangsUpdates!==false
		&& isset($arLangsUpdates["ERROR"])
		&& count($arLangsUpdates["ERROR"])>0)
	{
		for ($i = 0; $i < count($arLangsUpdates["ERROR"]); $i++)
			$strErrorMessage .= ($arLangsUpdates["ERROR"][$i]["@"]["TYPE"] <> ""? "[".$arLangsUpdates["ERROR"][$i]["@"]["TYPE"]."] " : "").$arLangsUpdates["ERROR"][$i]["#"]."<br>";
	}

	if (!$arLangsUpdates
		|| !isset($arLangsUpdates["LANGS"])
		|| !isset($arLangsUpdates["LANGS"]["#"]["LANG"])
		|| !is_array($arLangsUpdates["LANGS"]["#"]["LANG"])
		|| count($arLangsUpdates["LANGS"]["#"]["LANG"])<=0)
	{
		$strErrorMessage .= GetMessage("SUP_LSTEP2_NO_LANGS")."<br>";
	}

	if($strErrorMessage <> "")
		CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("SUP_ERRORS"), "TYPE"=>"ERROR", "DETAILS"=>$strErrorMessage, "HTML"=>true));

	if ($arLangsUpdates
		&& isset($arLangsUpdates["LANGS"])
		&& is_array($arLangsUpdates["LANGS"]["#"]["LANG"])
		&& count($arLangsUpdates["LANGS"]["#"]["LANG"])>0)
	{
		ob_start();
?>
	<form method="post" action="sysupdate.php" onsubmit="document.getElementById('load_button').disabled=true; return true;">
<?
		$strPHPDateFormat = CUpdateSystem::GetDateFormat(false);

		$strLangsHTML1 = "";
		$strLangsHTML2 = "";
		for ($i = 0; $i < count($arLangsUpdates["LANGS"]["#"]["LANG"]); $i++)
		{
			$dDate = mktime(0, 0, 0,
				substr($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["DATE"], 4, 2),
				substr($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["DATE"], 6, 2),
				substr($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["DATE"], 0, 4));

			$strLID = $arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["ID"];

			$s = 
				"<input type=\"checkbox\" name=\"load_langs[]\" id=\"load_lang_cbox_".$i."\" value=\"".$strLID."\" onclick=\"LangClick(this)\">".
				"<label for=\"load_lang_cbox_".$i."\">[".htmlspecialchars($strLID)."] ".
				htmlspecialchars($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["NAME"])." ".
				"(".GetMessage("SUP_LSTEP2_FROM")."&nbsp;".Date($strPHPDateFormat, $dDate).")</label><br>";
			if ($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["TYPE"]=="INST")
				$strLangsHTML1 .= $s;
			else
				$strLangsHTML2 .= $s;
		}

		if($strLangsHTML1 <> "")
		{
			?>
			<p><b><?= GetMessage("SUP_LSTEP2_INST") ?>:</b></p>
			<?
			echo "<p>".$strLangsHTML1."</p>";
		}

		if($strLangsHTML2 <> "")
		{
			?>
			<p<?if($strLangsHTML1 <> "") echo ' class="update-dots"'?>><?= GetMessage("SUP_LSTEP2_OTHER") ?>:</p>
			<?
			echo "<p>".$strLangsHTML2."</p>";
		}
?>
		<input type="submit" name="load_button" id="load_button" value="<?= GetMessage("SUP_LSTEP2_LOAD_BTN") ?>" disabled>
		<input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
		<input type="hidden" name="STEP" value="3">
		<input type="hidden" name="UNIID" value="<?echo md5(time());?>">
		<input type="hidden" name="TYPE" value="LANGS">
	</form>
<?

		$sForm = ob_get_contents();
		ob_end_clean();
		
		echo CUpdateOutput::ShowBlock(GetMessage("SUP_LSTEP2_PROMT"), $sForm, "icon-lang");
?>
<script type="text/javascript">
function LangClick(elem)
{
	var dis = true;
	if(elem.checked)
		dis = false;
	else
	{
		var boxes = elem.form.elements[elem.name];
		if(boxes.length > 1)
		{
			for(var i in boxes)
				if(boxes[i].checked)
				{
					dis = false;
					break;
				}
		}
	}
	document.getElementById('load_button').disabled = dis;
}
</script>
<?
	}
}

//**********************************************************************/
//******   ШАГ 3  /ЯЗЫКИ/   ********************************************/
//**********************************************************************/
if ($TYPE=="LANGS" && $STEP==3 && $MAIN_RIGHT=="W")
{
	// Если пришли ошибки с сервера
	if ($arLangsUpdates!==false
		&& isset($arLangsUpdates["ERROR"])
		&& count($arLangsUpdates["ERROR"])>0)
	{
		for ($i = 0; $i < count($arLangsUpdates["ERROR"]); $i++)
			$strErrorMessage .= ($arLangsUpdates["ERROR"][$i]["@"]["TYPE"] <> ""? "[".$arLangsUpdates["ERROR"][$i]["@"]["TYPE"]."] " : "").$arLangsUpdates["ERROR"][$i]["#"]."<br>";
	}

	if($strErrorMessage <> "")
		CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("SUP_ERRORS"), "TYPE"=>"ERROR", "DETAILS"=>$strErrorMessage, "HTML"=>true));

	echo BeginNote('width="100%"');
	echo GetMessage("SUP_STEP4_UPD_PROMT");
	echo EndNote();

	if ($arLangsUpdates
		&& isset($arLangsUpdates["LANGS"])
		&& is_array($arLangsUpdates["LANGS"]["#"]["LANG"])
		&& count($arLangsUpdates["LANGS"]["#"]["LANG"])>0)
	{
		$strSuccessLangs = "";
		$strErrorLangs = "";
		$strNoneLangs = "";

		$strPHPDateFormat = CUpdateSystem::GetDateFormat(false);

		for ($i = 0; $i < count($arLangsUpdates["LANGS"]["#"]["LANG"]); $i++)
		{
			if (array_key_exists($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["ID"], $arErrorLangs))
			{
				$strErrorLangs .= "<b>[".$arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["ID"]."] ".$arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["NAME"]."</b><br>";
				$strErrorLangs .= "<i>".$arErrorLangs[$arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["ID"]]."</i><br>";

				$dDate = mktime(0, 0, 0,
					substr($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["DATE"], 4, 2),
					substr($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["DATE"], 6, 2),
					substr($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["DATE"], 0, 4));

				CUpdateSystem::AddMessage2Log(
					str_replace("#DATE#", Date($strPHPDateFormat, $dDate), str_replace("#LANG#", $arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["NAME"]." (".$arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["ID"].")", GetMessage("SUP_LSTEP3_UPD_LOG")))." ".
					$arErrorLangs[$arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["ID"]],
					"UPD_ERROR");
			}
			elseif (array_key_exists($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["ID"], $arSuccessLangs))
			{
				$strSuccessLangs .= "<b>[".$arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["ID"]."] ".$arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["NAME"]."</b><br>";

				$dDate = mktime(0, 0, 0,
					substr($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["DATE"], 4, 2),
					substr($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["DATE"], 6, 2),
					substr($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["DATE"], 0, 4));

				CUpdateSystem::AddMessage2Log(
					str_replace("#DATE#", Date($strPHPDateFormat, $dDate), str_replace("#LANG#", $arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["NAME"]." (".$arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["ID"].")", GetMessage("SUP_LSTEP3_UPD_LOG"))),
					"UPD_SUCCESS");
			}
			else
			{
				$strNoneLangs .= "<b>[".$arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["ID"]."] ".$arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["NAME"]."</b><br>";
			}
		}

		$sMess = "";
		if (strlen($strSuccessLangs)>0)
			$sMess .= "<p>".GetMessage("SUP_LSTEP3_SUCCESS").":<br>".$strSuccessLangs."</p>";
		if (strlen($strErrorLangs)>0)
			$sMess .= "<p>".GetMessage("SUP_LSTEP3_ERROR").":<br>".$strErrorLangs."</p>";
		if (strlen($strNoneLangs)>0)
			$sMess .= "<p>".GetMessage("SUP_LSTEP3_NONE").":<br>".$strNoneLangs."</p>";

		echo CUpdateOutput::ShowBlock($sMess, "", "icon-lang");
	}

	CUpdateSystem::EraseOldFolders(2);
}
//**********************************************************************/
//******   КОНЕЦ ШАГОВ  /ЯЗЫКИ/   **************************************/
//**********************************************************************/

/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@   ФОРМЫ ПОМОЩИ   @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/

//**********************************************************************/
//******   ШАГ 2  /ХЕЛП/   ********************************************/
//**********************************************************************/
if($TYPE=="HELP" && $STEP==2 && $MAIN_RIGHT=="W")
{
	// Если пришли ошибки с сервера
	if ($arHelpUpdates!==false
		&& isset($arHelpUpdates["ERROR"])
		&& count($arHelpUpdates["ERROR"])>0)
	{
		for ($i = 0; $i < count($arHelpUpdates["ERROR"]); $i++)
			$strErrorMessage .= ($arHelpUpdates["ERROR"][$i]["@"]["TYPE"] <> ""? "[".$arHelpUpdates["ERROR"][$i]["@"]["TYPE"]."] " : "").$arHelpUpdates["ERROR"][$i]["#"]."<br>";
	}

	if (!$arHelpUpdates
		|| !isset($arHelpUpdates["HELPS"])
		|| !isset($arHelpUpdates["HELPS"]["#"]["HELP"])
		|| !is_array($arHelpUpdates["HELPS"]["#"]["HELP"])
		|| count($arHelpUpdates["HELPS"]["#"]["HELP"])<=0)
	{
		$strErrorMessage .= GetMessage("SUP_HSTEP2_NO_UPDS")."<br>";
	}

	if($strErrorMessage <> "")
		CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("SUP_ERRORS"), "TYPE"=>"ERROR", "DETAILS"=>$strErrorMessage, "HTML"=>true));

	if ($arHelpUpdates
		&& isset($arHelpUpdates["HELPS"])
		&& is_array($arHelpUpdates["HELPS"]["#"]["HELP"])
		&& count($arHelpUpdates["HELPS"]["#"]["HELP"])>0)
	{
		ob_start();
?>
	<form method="post" action="sysupdate.php" onsubmit="document.getElementById('load_button').disabled=true; return true;">
<?

		$strPHPDateFormat = CUpdateSystem::GetDateFormat(false);

		$strHelpsHTML1 = "";
		$strHelpsHTML2 = "";
		for ($i = 0; $i < count($arHelpUpdates["HELPS"]["#"]["HELP"]); $i++)
		{
			$dDate = mktime(0, 0, 0,
				substr($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["DATE"], 4, 2),
				substr($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["DATE"], 6, 2),
				substr($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["DATE"], 0, 4));

			$strLID = $arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["ID"];

			$s = 
				"<input type=\"radio\" name=\"load_help\" id=\"load_lang_rd_".$i."\" value=\"".$strLID."\" onclick=\"document.getElementById('load_button').disabled=false;\">".
				"<label for=\"load_lang_rd_".$i."\">[".htmlspecialchars($strLID)."] ".
				htmlspecialchars($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["NAME"])." ".
				"(".GetMessage("SUP_LSTEP2_FROM")."&nbsp;".Date($strPHPDateFormat, $dDate).")</label><br>";
			if($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["TYPE"]=="INST")
				$strHelpsHTML1 .= $s;
			else
				$strHelpsHTML2 .= $s;
		}

		if($strHelpsHTML1 <> "")
		{
			?>
			<p><b><?= GetMessage("SUP_HSTEP2_INST") ?>:</b></p>
			<?
			echo "<p>".$strHelpsHTML1."</p>";
		}

		if($strHelpsHTML2 <> "")
		{
			?>
			<p<?if($strHelpsHTML1 <> "") echo ' class="update-dots"'?>><?= GetMessage("SUP_HSTEP2_OTHER") ?>:</p>
			<?
			echo "<p>".$strHelpsHTML2."</p>";
		}
?>
		<input type="submit" name="load_button" id="load_button" value="<?= GetMessage("SUP_HSTEP2_LOAD_BTN") ?>" disabled>
		<input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
		<input type="hidden" name="STEP" value="3">
		<input type="hidden" name="UNIID" value="<?echo md5(time());?>">
		<input type="hidden" name="TYPE" value="HELP">
	</form>
<?
		$sForm = ob_get_contents();
		ob_end_clean();
		
		echo CUpdateOutput::ShowBlock(GetMessage("SUP_HSTEP2_PROMT"), $sForm, "icon-help");
	}
}

//**********************************************************************/
//******   ШАГ 3  /ХЕЛП/   ********************************************/
//**********************************************************************/
if ($TYPE=="HELP" && $STEP==3 && $MAIN_RIGHT=="W")
{
	// Если пришли ошибки с сервера
	if ($arHelpUpdates!==false
		&& isset($arHelpUpdates["ERROR"])
		&& count($arHelpUpdates["ERROR"])>0)
	{
		for ($i = 0; $i < count($arHelpUpdates["ERROR"]); $i++)
			$strErrorMessage .= ($arHelpUpdates["ERROR"][$i]["@"]["TYPE"] <> ""? "[".$arHelpUpdates["ERROR"][$i]["@"]["TYPE"]."] " : "").$arHelpUpdates["ERROR"][$i]["#"]."<br>";
	}

	if($strErrorMessage <> "")
		CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("SUP_ERRORS"), "TYPE"=>"ERROR", "DETAILS"=>$strErrorMessage, "HTML"=>true));

	echo BeginNote('width="100%"');
	echo GetMessage("SUP_STEP4_UPD_PROMT");
	echo EndNote();

	if ($arHelpUpdates
		&& isset($arHelpUpdates["HELPS"])
		&& is_array($arHelpUpdates["HELPS"]["#"]["HELP"])
		&& count($arHelpUpdates["HELPS"]["#"]["HELP"])>0)
	{
		$strPHPDateFormat = CUpdateSystem::GetDateFormat(false);

		$sMess = "";
		for ($i = 0; $i < count($arHelpUpdates["HELPS"]["#"]["HELP"]); $i++)
		{
			if (array_key_exists($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["ID"], $arErrorHelp))
			{
				$dDate = mktime(0, 0, 0,
					substr($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["DATE"], 4, 2),
					substr($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["DATE"], 6, 2),
					substr($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["DATE"], 0, 4));

				$sMess .= 
					'<p>'.GetMessage("SUP_HSTEP3_UPD_ERR", array("#DATE#"=>Date($strPHPDateFormat, $dDate), "#HELP#"=>$arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["NAME"]." (".$arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["ID"].")")).'<br>'.
					'<i>'.$arErrorHelp[$arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["ID"]].'</i></p>';

				CUpdateSystem::AddMessage2Log(
					str_replace("#DATE#", Date($strPHPDateFormat, $dDate), str_replace("#HELP#", $arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["NAME"]." (".$arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["ID"].")", GetMessage("SUP_HSTEP3_UPD_LOG")))." ".
					$arErrorHelp[$arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["ID"]],
					"UPD_ERROR");
			}
			elseif (array_key_exists($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["ID"], $arSuccessHelp))
			{
				$dDate = mktime(0, 0, 0,
					substr($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["DATE"], 4, 2),
					substr($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["DATE"], 6, 2),
					substr($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["DATE"], 0, 4));

				$sMess .= '<p>'.GetMessage("SUP_HSTEP3_UPD_SUC", array("#DATE#"=>Date($strPHPDateFormat, $dDate), "#HELP#"=>$arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["NAME"]." (".$arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["ID"].")")).'</p>';

				CUpdateSystem::AddMessage2Log(
					str_replace("#DATE#", Date($strPHPDateFormat, $dDate), str_replace("#HELP#", $arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["NAME"]." (".$arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["ID"].")", GetMessage("SUP_HSTEP3_UPD_LOG"))),
					"UPD_SUCCESS");
			}
			else
			{
				$sMess .= '<p>'.GetMessage("SUP_HSTEP3_UPD_NONE", array("#HELP#"=>$arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["NAME"]." (".$arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["ID"].")")).'</p>';
			}
		}
		echo CUpdateOutput::ShowBlock($sMess, "", "icon-help");
	}
	CUpdateSystem::EraseOldFolders(2);
}
//**********************************************************************/
//******   КОНЕЦ ШАГОВ  /ХЕЛП/   **************************************/
//**********************************************************************/

/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@   ФОРМЫ ДОБАВЛЕНИЯ САЙТОВ   @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/

//**********************************************************************/
//******   ДОБАВЛЕНИЕ САЙТОВ   *****************************************/
//**********************************************************************/
if ($TYPE=="ADD_SITE" && $STEP==1 && $MAIN_RIGHT=="W")
{
	if($strErrorMessage <> "")
		CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("SUP_ERRORS"), "TYPE"=>"ERROR", "DETAILS"=>$strErrorMessage, "HTML"=>true));

	ob_start();
?>
	<form method="POST" action="sysupdate.php">
		<input type="text" name="CHECK" VALUE="" size="30">
		<input type="submit" value="<?= GetMessage("SUP_AD_CHECK_DO") ?>">
		<input type="hidden" name="TYPE" VALUE="ADD_SITE">
		<input type="hidden" name="STEP" VALUE="2">
		<input type="hidden" name="lang" VALUE="<?= LANGUAGE_ID ?>">
	</form>
<?
	$sForm = ob_get_contents();
	ob_end_clean();
	
	echo CUpdateOutput::ShowBlock(GetMessage("SUP_ADD_CHECK_PROMT").":", $sForm, "icon-licence");
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
