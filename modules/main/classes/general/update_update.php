<?
/**********************************************************************/
/**    DO NOT MODIFY THIS FILE                                       **/
/**    MODIFICATION OF THIS FILE WILL ENTAIL SITE FAILURE            **/
/**********************************************************************/
if (!defined("UPDATE_SYSTEM_VERSION"))
	// define("UPDATE_SYSTEM_VERSION", "11.0.12");
if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/update_system.php"))
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/update_system.php");
	die();
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
// define("HELP_FILE", "updates/index.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_class.php");

@set_time_limit(0);
ini_set("track_errors", "1");
ignore_user_abort(true);

if(defined("SM_VERSION") && version_compare(SM_VERSION, "5.0.10") >= 0)
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_update5.php");
	die();
}

include(GetLangFileName($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/lang/", "/classes/general/update_update.php", $lang));
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/prolog.php");

$MAIN_RIGHT = $APPLICATION->GetGroupRight("main");
if ($MAIN_RIGHT<"R") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$strErrorMessage = "";

/* Настраиваем параметры вызова страницы */
$STEP = IntVal($STEP);
if ($STEP <= 0 || $STEP > 4)
	$STEP = 1;

if ($MAIN_RIGHT != "W")
	$STEP = 1;

if ($TYPE!="REGVER" && $TYPE!="LOADSRC" && $TYPE!="UPDUPD" && $TYPE!="LANGS" && $TYPE!="HELP" && $TYPE!="HISTORY" && $TYPE!="SAVE_KEY" && $TYPE!="ACTIVATE_KEY" && $TYPE!="ADD_SITE" && $TYPE!="SUBSCR_UPDATE" && $TYPE!="TURN_STABILITY" && $TYPE!="AGREE_LICENSE_ACT")
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

/*********** ВКЛЮЧЕНИЕ / ОТКЛЮЧЕНИЕ УСТАНОВКИ БЕТА-ВЕРСИЙ *************/

if ($TYPE=="TURN_STABILITY" && $MAIN_RIGHT=="W")
{
	$stableVersionsOnly = (($stableVersionsOnly == "Y") ? "N" : "Y");
	COption::SetOptionString("main", "stable_versions_only", $stableVersionsOnly);

	$TYPE = "UPDATE";
}

/*********** ДЕЙСТВИЯ НАД КЛЮЧОМ *************/

if ($TYPE=="SAVE_KEY" && $MAIN_RIGHT=="W")
{
	$NEW_LICENSE_KEY = preg_replace("/[^A-Za-z0-9_.-]/", "", $NEW_LICENSE_KEY);

	if (strlen($NEW_LICENSE_KEY)<=0)
		$strErrorMessage .= GetMessage("SUP_ENTER_KEY")." [PULK01]. ";
	elseif (strtolower($NEW_LICENSE_KEY)=="demo")
		$strErrorMessage .= GetMessage("SUP_ENTER_CORRECT_KEY")." [PULK02]. ";

	if (strlen($strErrorMessage)<=0)
	{
		if (!($fp = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/license_key.php", "w")))
		{
			$strErrorMessage .= GetMessage("SUP_CANT_OPEN_FILE")." [PULK03]. ";
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		fputs($fp, "<"."? \$"."LICENSE_KEY = \"".EscapePHPString($NEW_LICENSE_KEY)."\"; ?".">");
		fclose($fp);
	}

	$TYPE = "UPDATE";
}

/*********** ДЕЙСТВИЯ НАД ПОДПИСКОЙ *************/

if ($TYPE=="SUBSCR_UPDATE" && $MAIN_RIGHT=="W")
{
	$strSaveEmails = "";

	if (strlen($SUBSCR_DELETE)<=0)
	{
		if (strlen($SUBSCR_EMAIL)<=0)
			$strErrorMessage .= GetMessage("SUP_ERROR_NO_MAIL_U")." [PSUUE01]. ";

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
						$strErrorMessage .= str_replace("#EMAIL#", $arEmails[$i], GetMessage("SUP_ERROR_BAD_MAIL_U"))." [PSUUE02]. ";
					}
				}
			}
		}

		if (strlen($strErrorMessage)<=0)
		{
			if (strlen($strSaveEmails)<=0)
			{
				$strErrorMessage .= GetMessage("SUP_ERROR_NO_MAIL_U")." [PSUUE03]. ";
			}
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		CUpdateSystem::SubscribeUpdates($strSaveEmails, $strErrorMessage, LANG);
	}

	$TYPE = "UPDATE";
}

/*********** ДЕЙСТВИЯ НАД АКТИВАЦИЕЙ КЛЮЧА *************/

if ($TYPE=="ACTIVATE_KEY" && $MAIN_RIGHT=="W")
{
	if (strlen($NAME)<=0)
		$strErrorMessage .= GetMessage("SUP_ACT_NAME").". ";

	if (strlen($EMAIL)<=0)
		$strErrorMessage .= GetMessage("SUP_ACT_EMAIL").". ";
	elseif (!CUpdateSystem::CheckEMail($EMAIL))
		$strErrorMessage .= GetMessage("SUP_ACT_BAD_EMAIL").". ";

	if (strlen($SITE_URL)<=0)
		$strErrorMessage .= GetMessage("SUP_ACT_URL").". ";

	if ($GENERATE_USER == "Y")
	{
		if (strlen($USER_NAME) <= 0)
			$strErrorMessage .= GetMessage("SUP_ACT_NO_USER_NAME").". ";
		if (strlen($USER_LAST_NAME) <= 0)
			$strErrorMessage .= GetMessage("SUP_ACT_NO_USER_LAST_NAME").". ";
		if (strlen($USER_LOGIN) <= 0)
			$strErrorMessage .= GetMessage("SUP_ACT_NO_USER_LOGIN").". ";
		elseif (strlen($USER_LOGIN) < 3)
			$strErrorMessage .= GetMessage("SUP_ACT_SHORT_USER_LOGIN").". ";
		if (strlen($USER_PASSWORD) <= 0)
			$strErrorMessage .= GetMessage("SUP_ACT_NO_USER_PASSWORD").". ";
		if ($USER_PASSWORD != $USER_PASSWORD_CONFIRM)
			$strErrorMessage .= GetMessage("SUP_ACT_NO_USER_PASSWORD_CONFIRM").". ";
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
				"USER_LOGIN" => $USER_LOGIN,
				"USER_PASSWORD" => $USER_PASSWORD
			);
		CUpdateSystem::ActivateLicenseKey($arFields, $strErrorMessage, LANG, $stableVersionsOnly);
	}

	$TYPE = "UPDATE";
}

/*********** ДЕЙСТВИЯ НАД САЙТОМ *************/

if ($TYPE=="ADD_SITE" && $MAIN_RIGHT=="W" && $STEP==2)
{
	if (strlen($CHECK)<=0)
		$strErrorMessage .= GetMessage("SUP_ENTER_CHECK").". ";

	if (strlen($strErrorMessage)<=0)
	{
		CUpdateSystem::AddSites($CHECK, $strErrorMessage, LANG, $stableVersionsOnly);
	}

	$STEP = 1;
	if (strlen($strErrorMessage)<=0)
		$TYPE = "UPDATE";
}

/*********** ДЕЙСТВИЯ НАД РЕГИСТРАЦИЕЙ *************/

if ($TYPE=="REGVER" && $MAIN_RIGHT=="W")
{
	if (CUpdateSystem::RegisterVersion($strErrorMessage, LANG, $stableVersionsOnly))
	{
		LocalRedirect("sysupdate.php?STEP=1&TYPE=UPDATE&lang=".LANG);
	}
	$TYPE = "UPDATE";
}

/*********** ДЕЙСТВИЯ НАД ИСХОДНИКАМИ *************/

if ($TYPE=="LOADSRC" && $MAIN_RIGHT=="W")
{
	if (CUpdateSystem::LoadSources($strErrorMessage, LANG, $stableVersionsOnly))
	{
		LocalRedirect("sysupdate.php?STEP=1&TYPE=UPDATE&lang=".LANG);
	}
	$TYPE = "UPDATE";
}

/*********** ДЕЙСТВИЯ НАД СИСТЕМОЙ ОБНОВЛЕНИЙ *************/

if ($TYPE=="UPDUPD" && $MAIN_RIGHT=="W")
{
	if (CUpdateSystem::UpdateUpdate($strErrorMessage, LANG, $stableVersionsOnly))
	{
		LocalRedirect("sysupdate.php?STEP=1&TYPE=UPDATE&lang=".LANG);
	}
	$TYPE = "UPDATE";
}

/*********** ДЕЙСТВИЯ НАД МОДУЛЯМИ *************/

if ($TYPE=="UPDATE" && $STEP==4 && $MAIN_RIGHT=="W")
{
	if (strlen($UNIID)>0 && $_SESSION[$UNIID]=="Y")
	{
		CUpdateSystem::AddMessage2Log(GetMessage("SUP_NOT_REFRESH"), "PU400");
		$strErrorMessage .= GetMessage("SUP_NOT_REFRESH")." [PU400]. ";
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
			$strErrorMessage .= GetMessage("SUP_EMPTY_UPD_MOD")." [PU401]. ";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_EMPTY_UPD_MOD"), "PU401");
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		if (strlen($temporary_updates_dir)<=0)
		{
			$strErrorMessage .= GetMessage("SUP_EMPTY_TEMP_DIR")." [PU402]. ";
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
			$strErrorMessage .= GetMessage("SUP_UPD_LOAD_BREAK")." [PU404]. ";
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
			$strErrorMessage .= GetMessage("SUP_BAD_UPD_INSTALL")." [PU405]. ";
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
			$strErrorMessage .= GetMessage("SUP_NO_LOAD_MODULES")." [PU301]. ";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_NO_LOAD_MODULES"), "PU301");
		}

		if (strlen($strErrorMessage)<=0)
		{
			if (!CUpdateSystem::LoadModuleUpdates($arLoadModules, $strErrorMessage, LANG, $stableVersionsOnly))
			{
				$strErrorMessage .= GetMessage("SUP_UPD_LOAD_BREAK")." [PU302]. ";
				CUpdateSystem::AddMessage2Log(GetMessage("SUP_UPD_LOAD_BREAK"), "PU302");
			}
		}
	}
	else
	{
		$archiveFileName = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz";

		if (!file_exists($archiveFileName) || !is_file($archiveFileName))
		{
			$strErrorMessage .= str_replace("#FILE#", $archiveFileName, GetMessage("SUP_NO_TEMP_FILE"))." [PU3021]. ";
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		$temporary_updates_dir = "";
		if (!CUpdateSystem::UnGzipArchive($temporary_updates_dir, $strErrorMessage, "Y"))
		{
			$strErrorMessage .= GetMessage("SUP_UPD_LOAD_BREAK")." [PU303]. ";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_UPD_LOAD_BREAK"), "PU303");
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		if (!CUpdateSystem::CheckUpdatability($temporary_updates_dir, $strErrorMessage))
		{
			$strErrorMessage .= GetMessage("SUP_UPD_LOAD_BREAK")." [PU304]. ";
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
			$strErrorMessage .= GetMessage("SUP_UPD_LOAD_BREAK")." [PU305]. ";
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

/*********** ДЕЙСТВИЯ НАД ЯЗЫКАМИ *************/

if ($TYPE=="LANGS" && $STEP==3 && $MAIN_RIGHT=="W")
{
	if (strlen($UNIID)>0 && $_SESSION[$UNIID]=="Y")
	{
		CUpdateSystem::AddMessage2Log(GetMessage("SUP_NOT_REFRESH"), "PUL300");
		$strErrorMessage .= GetMessage("SUP_NOT_REFRESH")." [PUL300]. ";
	}

	if (strlen($strErrorMessage)<=0)
	{
		CUpdateSystem::AddMessage2Log("Query loading langs: ".$load_langs);

		$arLoadLangs = array();
		if (strlen($load_langs) > 0)
		{
			$arLoadLangs_tmp = explode(",", $load_langs);
			for ($i = 0; $i < count($arLoadLangs_tmp); $i++)
			{
				$arLoadLangs_tmp[$i] = Trim($arLoadLangs_tmp[$i]);
				if (strlen($arLoadLangs_tmp[$i])>0)
					$arLoadLangs[] = $arLoadLangs_tmp[$i];
			}
		}

		if (count($arLoadLangs)<=0)
		{
			$strErrorMessage .= GetMessage("SUP_NO_LANGS_LOAD")." [PUL301]. ";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_NO_LANGS_LOAD"), "PUL301");
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		if (!CUpdateSystem::LoadLangsUpdates($arLoadLangs, $strErrorMessage, LANG, $stableVersionsOnly))
		{
			$strErrorMessage .= GetMessage("SUP_LANG_LOAD_BREAK")." [PUL302]. ";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_LANG_LOAD_BREAK"), "PUL302");
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		$temporary_updates_dir = "";
		if (!CUpdateSystem::UnGzipArchive($temporary_updates_dir, $strErrorMessage, "Y"))
		{
			$strErrorMessage .= GetMessage("SUP_LANG_LOAD_BREAK")." [PUL303]. ";
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
			$strErrorMessage .= GetMessage("SUP_LANG_LOAD_BREAK")." [PUL304]. ";
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
			$strErrorMessage .= GetMessage("SUP_BAD_LANG_INSTALL")." [PUL305]. ";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_BAD_LANG_INSTALL"), "PUL305");
		}
	}
}

if ($TYPE=="LANGS" && $STEP==2 && $MAIN_RIGHT=="W")
{
	$arLangsUpdates = CUpdateSystem::GetServerLangsUpdates($strErrorMessage, LANG, $stableVersionsOnly);
}

/*********** ДЕЙСТВИЯ НАД ПОМОЩЬЮ *************/

if ($TYPE=="HELP" && $STEP==3 && $MAIN_RIGHT=="W")
{
	if (strlen($UNIID)>0 && $_SESSION[$UNIID]=="Y")
	{
		CUpdateSystem::AddMessage2Log(GetMessage("SUP_NOT_REFRESH"), "PUH300");
		$strErrorMessage .= GetMessage("SUP_NOT_REFRESH")." [PUH300]. ";
	}

	if (strlen($strErrorMessage)<=0)
	{
		CUpdateSystem::AddMessage2Log("Query loading helps: ".$load_help);

		if (strlen($load_help)<=0)
		{
			$strErrorMessage .= GetMessage("SUP_EMPTY_HELP_LANG")." [PUH301]. ";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_EMPTY_HELP_LANG"), "PUH301");
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		if (!CUpdateSystem::LoadHelpUpdates(array($load_help), $strErrorMessage, LANG, $stableVersionsOnly))
		{
			$strErrorMessage .= GetMessage("SUP_HELP_LOAD_BREAK")." [PUH302]. ";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_HELP_LOAD_BREAK"), "PUH302");
		}
	}

	if (strlen($strErrorMessage)<=0)
	{
		$temporary_updates_dir = "";
		if (!CUpdateSystem::UnGzipArchive($temporary_updates_dir, $strErrorMessage, "Y"))
		{
			$strErrorMessage .= GetMessage("SUP_HELP_LOAD_BREAK")." [PUH303]. ";
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
			$strErrorMessage .= GetMessage("SUP_BAD_HELP_INSTALL")." [PUH305]. ";
			CUpdateSystem::AddMessage2Log(GetMessage("SUP_BAD_HELP_INSTALL"), "PUH305");
		}
	}
}

if ($TYPE=="HELP" && $STEP==2 && $MAIN_RIGHT=="W")
{
	$arHelpUpdates = CUpdateSystem::GetServerHelpUpdates($strErrorMessage, LANG, $stableVersionsOnly);
}

/*********** КОНЕЦ ДЕЙСТВИЙ *************/

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
elseif ($TYPE=="HISTORY")
	$strTitle = GetMessage("SUP_TITLE_HISTORY");
elseif ($TYPE=="ADD_SITE")
	$strTitle = GetMessage("SUP_TITLE_ADD_SITE");

$APPLICATION->SetTitle($strTitle);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>

<?
/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@   ГЛАВНАЯ ФОРМА   @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/

/**********************************************************************/
/******   ШАГ 1  /ФОРМА/   ********************************************/
/**********************************************************************/
if ($TYPE=="UPDATE" && $STEP==1)
{
	if (!CUpdateSystem::IsGzipInstalled())
		echo "<font class=\"errortext\">".str_replace("#ZLIB_URL#", "http://www.php.net/manual/en/ref.zlib.php", GetMessage("SUP_ZLIB_RECOM"))."</font><br><br>";

	if (strlen($strErrorMessage)>0)
		echo "<font class=\"errortext\">".$strErrorMessage."</font><br><br>";

	// Если пришли ошибки с сервера
	if ($arTypesUpdates!==false
		&& isset($arTypesUpdates["ERROR"])
		&& count($arTypesUpdates["ERROR"])>0)
	{
		for ($i = 0; $i < count($arTypesUpdates["ERROR"]); $i++)
		{
			?><font class="errortext">
			<?if (strlen($arTypesUpdates["ERROR"][$i]["@"]["TYPE"])>0):?>
				[<?= $arTypesUpdates["ERROR"][$i]["@"]["TYPE"] ?>]
			<?endif;?>
			<?= $arTypesUpdates["ERROR"][$i]["#"] ?></font><br><?
		}
		?><br><?
	}

	if ($DB->type == "MYSQL")
	{
		$dbQueryRes = $DB->Query("select VERSION() as ver", True);
		if ($arQueryRes = $dbQueryRes->Fetch())
		{
			$curMySqlVer = trim($arQueryRes["ver"]);
			$arCurMySqlVer = explode(".", $curMySqlVer);
			if (IntVal($arCurMySqlVer[0]) < 4
				|| IntVal($arCurMySqlVer[0]) == 4 && IntVal($arCurMySqlVer[1]) < 0
				|| IntVal($arCurMySqlVer[0]) == 4 && IntVal($arCurMySqlVer[1]) == 0 && IntVal($arCurMySqlVer[2]) < 18)
			{
				?><font class="errortext">
				<?= GetMessage("SUP_MYSQL_L4") ?>
				</font><br><br><?
				$arTypesUpdates = false;
			}
		}
	}
	?>
	<font class="text">

	<?
	if ($stableVersionsOnly == "N")
	{
		if (class_exists("CAdminMessage"))
			CAdminMessage::ShowNote(GetMessage("SUP_STABLE_OFF"));
		else
			ShowNote(GetMessage("SUP_STABLE_OFF"));
	}
	?>

	<?
	if ($arTypesUpdates!==false
		&& isset($arTypesUpdates["CLIENT"])
		&& count($arTypesUpdates["CLIENT"])>0
		&& $arTypesUpdates["CLIENT"]["@"]["RESERVED"]!="Y")
	{
		?>
		<table align="right" style="border: 1px solid #DBE9F3; background-color: #F3F3F3;">
			<tr>
				<td>
					<font class="text">
						<b><?echo $arTypesUpdates["CLIENT"]["@"]["NAME"]?></b><br>
						<?= GetMessage("SUP_LICENSE_KEY") ?>: <?echo ($MAIN_RIGHT=="W") ? CUpdateSystem::GetLicenseKey() : "XXX-XX-XXXXXXXXXXX"?><br>
						<?echo GetMessage("SUP_EDITION")?> <?echo $arTypesUpdates["CLIENT"]["@"]["LICENSE"]?><br>
						<?echo GetMessage("SUP_SITES")?> <?echo ($arTypesUpdates["CLIENT"]["@"]["MAX_SITES"]>0? $arTypesUpdates["CLIENT"]["@"]["MAX_SITES"]:2)?><br>
						<?
						echo str_replace(
								"#DATE_TO#",
								((strlen($arTypesUpdates["CLIENT"]["@"]["DATE_TO"])>0) ? $arTypesUpdates["CLIENT"]["@"]["DATE_TO"] : "NA"),
								str_replace(
									"#DATE_FROM#",
									((strlen($arTypesUpdates["CLIENT"]["@"]["DATE_FROM"])>0) ? $arTypesUpdates["CLIENT"]["@"]["DATE_FROM"] : "NA"),
									GetMessage("SUP_ACTIVE_PERIOD")));
						echo "<br>";
						if (strlen($arTypesUpdates["CLIENT"]["@"]["HTTP_HOST"])>0)
							echo str_replace("#HOST#", $arTypesUpdates["CLIENT"]["@"]["HTTP_HOST"], GetMessage("SUP_UPD_HOST"));
						?>
					</font>
				</td>
			</tr>
		</table>
		<?
	}
	?>

	<?
	if ($arTypesUpdates!==false
		&& isset($arTypesUpdates["CLIENT"])
		&& !isset($arTypesUpdates["UPDATE_SYSTEM"])
		&& count($arTypesUpdates["CLIENT"])>0
		&& $arTypesUpdates["CLIENT"]["@"]["RESERVED"]=="Y")
	{
		?>
		<?= GetMessage("SUP_ACTIVATE_PROMT") ?>

		<form method="POST" action="sysupdate.php" name="activate_key_form">
			<center>
			<input type="hidden" name="TYPE" VALUE="ACTIVATE_KEY">
			<input type="hidden" name="STEP" VALUE="1">
			<input type="hidden" name="lang" VALUE="<?= htmlspecialchars($lang) ?>">

			<table width="100%" border="0" cellspacing="1" cellpadding="3" class="edittable">
				<tr>
					<td valign="top" align="right" width="50%" class="tablebody">
						<font class="tablefieldtext"><?= GetMessage("SUP_ACTIVATE_NAME") ?>:<font color="#FF0000"><b>*</b></font></font>
					</td>
					<td valign="top" align="left" width="50%" class="tablebody">
						<font class="tablebodytext">
						<input type="text" name="NAME" value="<?= htmlspecialchars($NAME) ?>" size="50" class="typeinput"><br>
						<small><?= GetMessage("SUP_ACTIVATE_NAME_DESCR") ?><br></small>
						</font>
					</td>
				</tr>
				<tr>
					<td valign="top" align="right" width="50%" class="tablebody">
						<font class="tablefieldtext"><?= GetMessage("SUP_ACTIVATE_EMAIL") ?>:<font color="#FF0000"><b>*</b></font></font>
					</td>
					<td valign="top" align="left" width="50%" class="tablebody">
						<input type="text" name="EMAIL" value="<?= htmlspecialchars($EMAIL) ?>" size="50" class="typeinput">
					</td>
				</tr>
				<tr>
					<td valign="top" align="right" width="50%" class="tablebody">
						<font class="tablefieldtext"><?= GetMessage("SUP_ACTIVATE_CONTARC_INFO") ?>:</font>
					</td>
					<td valign="top" align="left" width="50%" class="tablebody">
						<textarea name="CONTACT_INFO" rows="2" cols="46" class="typearea"><?= htmlspecialchars($CONTACT_INFO) ?></textarea>
					</td>
				</tr>
				<tr>
					<td valign="top" align="right" width="50%" class="tablebody">
						<font class="tablefieldtext"><?= GetMessage("SUP_ACTIVATE_URL") ?>:<font color="#FF0000"><b>*</b></font></font>
					</td>
					<td valign="top" align="left" width="50%" class="tablebody">
						<input type="text" name="SITE_URL" value="<?= htmlspecialchars($SITE_URL) ?>" size="50" class="typeinput">
					</td>
				</tr>
				<tr>
					<td valign="top" align="right" colspan="2">
						<img src="/bitrix/images/1.gif" width="1" height="8">
					</td>
				</tr>
				<tr>
					<td valign="top" align="left" class="tablebody" colspan="2">
						<font class="tablebodytext">
						<?= GetMessage("SUP_ACT_CREATE_USER_HINT") ?>

						<script language="JavaScript">
						<!--
						function EnableDisableUser(activateUserName)
						{
							var val = document.activate_key_form.GENERATE_USER.checked;

							document.activate_key_form.USER_NAME.disabled = !val;
							document.activate_key_form.USER_LAST_NAME.disabled = !val;
							document.activate_key_form.USER_LOGIN.disabled = !val;
							document.activate_key_form.USER_PASSWORD.disabled = !val;
							document.activate_key_form.USER_PASSWORD_CONFIRM.disabled = !val;

							var obj = document.getElementById("tr_user_name");
							obj.disabled = !val;
							obj = document.getElementById("tr_user_last_name");
							obj.disabled = !val;
							obj = document.getElementById("tr_user_login");
							obj.disabled = !val;
							obj = document.getElementById("tr_user_password");
							obj.disabled = !val;
							obj = document.getElementById("tr_user_password_confirm");
							obj.disabled = !val;

							if (activateUserName)
								try{document.activate_key_form.USER_NAME.focus();}catch(e){}
						}
						//-->
						</script>
						</font>
					</td>
				</tr>
				<tr>
					<td valign="top" align="right" width="50%">
						<font class="tablefieldtext"><?= GetMessage("SUP_ACT_F_CREATE_USER") ?>:</font>
					</td>
					<td valign="top" align="left" width="50%">
						<input type="checkbox" name="GENERATE_USER" OnClick="EnableDisableUser(true)" value="Y"<?if ($GENERATE_USER == "Y" || !isset($GENERATE_USER)) echo " checked";?>>
					</td>
				</tr>
				<tr id="tr_user_name">
					<td valign="top" align="right" width="50%">
						<font class="tablefieldtext"><?= GetMessage("SUP_ACT_F_USER_NAME") ?>:<font color="#FF0000"><b>*</b></font></font>
					</td>
					<td valign="top" align="left" width="50%">
						<input type="text" name="USER_NAME" value="<?= htmlspecialchars($USER_NAME) ?>" size="50" class="typeinput">
					</td>
				</tr>
				<tr id="tr_user_last_name">
					<td valign="top" align="right" width="50%">
						<font class="tablefieldtext"><?= GetMessage("SUP_ACT_F_USER_LAST_NAME") ?>:<font color="#FF0000"><b>*</b></font></font>
					</td>
					<td valign="top" align="left" width="50%">
						<input type="text" name="USER_LAST_NAME" value="<?= htmlspecialchars($USER_LAST_NAME) ?>" size="50" class="typeinput">
					</td>
				</tr>
				<tr id="tr_user_login">
					<td valign="top" align="right" width="50%">
						<font class="tablefieldtext"><?= GetMessage("SUP_ACT_F_USER_LOGIN") ?>:<font color="#FF0000"><b>*</b></font></font>
					</td>
					<td valign="top" align="left" width="50%">
						<input type="text" name="USER_LOGIN" value="<?= htmlspecialchars($USER_LOGIN) ?>" size="50" class="typeinput">
					</td>
				</tr>
				<tr id="tr_user_password">
					<td valign="top" align="right" width="50%">
						<font class="tablefieldtext"><?= GetMessage("SUP_ACT_F_USER_PASSWORD") ?>:<font color="#FF0000"><b>*</b></font></font>
					</td>
					<td valign="top" align="left" width="50%">
						<input type="password" name="USER_PASSWORD" value="" size="50" class="typeinput">
					</td>
				</tr>
				<tr id="tr_user_password_confirm">
					<td valign="top" align="right" width="50%">
						<font class="tablefieldtext"><?= GetMessage("SUP_ACT_F_USER_PASSWORD_CONFIRM") ?>:<font color="#FF0000"><b>*</b></font></font>
					</td>
					<td valign="top" align="left" width="50%">
						<input type="password" name="USER_PASSWORD_CONFIRM" value="" size="50" class="typeinput">
					</td>
				</tr>
			</table>
			<br>
			<input <?if ($MAIN_RIGHT<"W") echo "disabled" ?> class="button" type="submit" value="<?= GetMessage("SUP_ACTIVATE_DO") ?>">
			</center>
			<script language="JavaScript">
			<!--
			EnableDisableUser(false);
			//-->
			</script>
		</form>
		<br><br>
		<?
	}
	?>

	<?
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
	if (!$bLockUpdateSystemKernel && (strlen($strLicenseKey_tmp) <= 0
		|| strtolower($strLicenseKey_tmp) == "demo"
		|| $bLicenseNotFound))
	{
		if (defined("DEMO") && DEMO == "Y")
		{
			?>
			<?= str_replace("#URL#", "http://".(($lang=="ru") ? "www.bitrixsoft.ru/ru" : "www.bitrixsoft.com" )."/bsm_register.php?dt=".UrlEncode($SiteExpireDate), GetMessage("SUP_NO_KEY_PROMT")) ?>
			<br><br>

			<a href="http://<?= (($lang=="ru") ? "www.bitrixsoft.ru/ru" : "www.bitrixsoft.com" ) ?>/bsm_register.php?dt=<?echo UrlEncode($SiteExpireDate) ?>" target="_blank"><img src="/bitrix/images/main/icon2.gif" width="15" height="15" border="0" alt="<?= GetMessage("SUP_NO_KEY_ACT_ALT") ?>"> <?= GetMessage("SUP_NO_KEY_ACT") ?> &gt;&gt;</a>
			<br><br><br>

			<?= str_replace("#URL#", "/bitrix/admin/settings.php?mid_SELECTED=yes&mid=main", GetMessage("SUP_NO_KEY_ENTER_PROMT")) ?>
			<?
		}
		else
		{
			?>
			<?= str_replace("#URL_SET#", "/bitrix/admin/settings.php?mid_SELECTED=yes&mid=main", str_replace("#URL#", "http://www.bitrixsoft.".(($lang=="ru") ? "ru" : "com")."/support/", GetMessage("SUP_NO_KEY_PROMT_SRC"))) ?>
			<?
		}
		?>
		<form method="POST" action="sysupdate.php">
			<center>
			<input type="text" name="NEW_LICENSE_KEY" class="typeinput" VALUE="<?= (($MAIN_RIGHT<"W") ? "XXXXXXX" : htmlspecialchars($strLicenseKey_tmp)) ?>" size="30">
			<input type="hidden" name="TYPE" VALUE="SAVE_KEY">
			<input type="hidden" name="STEP" VALUE="1">
			<input type="hidden" name="lang" VALUE="<?= htmlspecialchars($lang) ?>">
			<input <?if ($MAIN_RIGHT<"W") echo "disabled" ?> class="button" type="submit" value="<?= GetMessage("SUP_NO_KEY_ENTER_DO") ?>">
			</center>
		</form>
		<br><br>
		<?
	}
	?>

	<?
	// Если есть обновление системы обновлений
	if ($arTypesUpdates!==false
		&& isset($arTypesUpdates["UPDATE_SYSTEM"]))
	{
		?>
		<?= GetMessage("SUP_UPDATE_UPDATE_PROMT") ?>
		<br><br>

		<a href="sysupdate.php?TYPE=UPDUPD&STEP=1&lang=<?= htmlspecialchars($lang) ?>"><img src="/bitrix/images/main/icon2.gif" width="15" height="15" border="0" alt="<?= GetMessage("SUP_UPD_UPD_ACT_ALT") ?>"> <?= GetMessage("SUP_UPD_UPD_ACT") ?> &gt;&gt;</a>
		<br><br><br>
		<?
	}
	?>

	<?
	$newLicense = COption::GetOptionString("main", "~new_license11_sign", "N");
	if (!$bLockUpdateSystemKernel && $newLicense == "Y")
	{
		// Если можно регистрировать продукт
		if ($arTypesUpdates!==false
			&& defined("DEMO") && DEMO=="Y"
			&& isset($arTypesUpdates["CLIENT"])
			&& !isset($arTypesUpdates["UPDATE_SYSTEM"])
			&& (
				$arTypesUpdates["CLIENT"]["@"]["ENC_TYPE"]=="F"
				|| $arTypesUpdates["CLIENT"]["@"]["ENC_TYPE"]=="E"))
		{
			?>
			<?= GetMessage("SUP_REG_PROMT") ?>
			<br><br>

			<a href="sysupdate.php?TYPE=REGVER&STEP=1&lang=<?= htmlspecialchars($lang) ?>"><img src="/bitrix/images/main/icon2.gif" width="15" height="15" border="0" alt="<?= GetMessage("SUP_REG_ACT_ALT") ?>"> <?= GetMessage("SUP_REG_ACT") ?> &gt;&gt;</a>
			<br><br><br>
			<?
		}
		?>

		<?
		// Если есть обновления по модулям
		if ($arTypesUpdates!==false
			&& isset($arTypesUpdates["MODULES"])
			&& !isset($arTypesUpdates["UPDATE_SYSTEM"])
			&& IntVal($arTypesUpdates["MODULES"]["@"]["COUNT"])>0)
		{
			?>
			<?= str_replace("#END#", CUpdateSystem::NumberEndings($arTypesUpdates["MODULES"]["@"]["COUNT"]), str_replace("#NUM#", $arTypesUpdates["MODULES"]["@"]["COUNT"], GetMessage("SUP_MUPD_PROMT"))) ?>
			<br><br>

			<a href="sysupdate.php?STEP=2&TYPE=UPDATE&lang=<?= htmlspecialchars($lang) ?>"><img src="/bitrix/images/main/icon2.gif" width="15" height="15" border="0" alt="<?= GetMessage("SUP_MUPD_ACT_ALT") ?>"> <?= GetMessage("SUP_MUPD_ACT") ?> &gt;&gt;</a>
			<br><br><br>
			<?
		}
		else
		{
			?>
			<?= GetMessage("SUP_MUPD_NO_UPD") ?>
			<br><br><br>
			<?
		}
		?>

		<?
		// Если можно закачать исходные тексты
		if ($arTypesUpdates!==false
			&& defined("ENCODE") && ENCODE=="Y"
			&& isset($arTypesUpdates["CLIENT"])
			&& !isset($arTypesUpdates["UPDATE_SYSTEM"])
			&& ($arTypesUpdates["CLIENT"]["@"]["ENC_TYPE"]=="F"))
		{
			?>
			<?= GetMessage("SUP_SRC_PROMT") ?>
			<br><br>

			<?if (!isset($arTypesUpdates["MODULES"])
				|| IntVal($arTypesUpdates["MODULES"]["@"]["COUNT"])<=0):?>
				<a href="sysupdate.php?TYPE=LOADSRC&STEP=1&lang=<?= htmlspecialchars($lang) ?>"><img src="/bitrix/images/main/icon2.gif" width="15" height="15" border="0" alt="<?= GetMessage("SUP_SRC_ACT_ALT") ?>"> <?= GetMessage("SUP_SRC_ACT") ?> &gt;&gt;</a>
				<br><br>
			<?endif;?>
			<br>
			<?
		}
		?>

		<?
		// Если есть обновления по языкам
		if ($arTypesUpdates!==false
			&& isset($arTypesUpdates["LANGS"])
			&& !isset($arTypesUpdates["UPDATE_SYSTEM"])
			&& (IntVal($arTypesUpdates["LANGS"]["@"]["COUNT"])>0
				|| IntVal($arTypesUpdates["LANGS"]["@"]["OTHER_COUNT"])>0)
			)
		{
			if (IntVal($arTypesUpdates["LANGS"]["@"]["COUNT"])>0)
				echo str_replace("#END#", CUpdateSystem::NumberEndings($arTypesUpdates["LANGS"]["@"]["COUNT"]), str_replace("#NUM#", $arTypesUpdates["LANGS"]["@"]["COUNT"], GetMessage("SUP_LUPD_PROMT")));
			if (IntVal($arTypesUpdates["LANGS"]["@"]["OTHER_COUNT"])>0)
				echo GetMessage("SUP_LUPD_PROMT_OTHER");
			?>
			<br><br>

			<a href="sysupdate.php?STEP=2&TYPE=LANGS&lang=<?= htmlspecialchars($lang) ?>"><img src="/bitrix/images/main/icon2.gif" width="15" height="15" border="0" alt="<?= GetMessage("SUP_LUPD_ACT_ALT") ?>"> <?= GetMessage("SUP_LUPD_ACT") ?> &gt;&gt;</a>
			<br><br><br>
			<?
		}
		?>

		<?
		// Если есть обновления по помощи
		if ($arTypesUpdates!==false
			&& isset($arTypesUpdates["HELPS"])
			&& !isset($arTypesUpdates["UPDATE_SYSTEM"])
			&& (IntVal($arTypesUpdates["HELPS"]["@"]["COUNT"])>0
				|| IntVal($arTypesUpdates["HELPS"]["@"]["OTHER_COUNT"])>0)
			)
		{
			if (IntVal($arTypesUpdates["HELPS"]["@"]["COUNT"])>0)
				echo str_replace("#END#", CUpdateSystem::NumberEndings($arTypesUpdates["HELPS"]["@"]["COUNT"], $lang, array(GetMessage("SUP_WORD_YAZIK_END1"), GetMessage("SUP_WORD_YAZIK_END2"), GetMessage("SUP_WORD_YAZIK_END3"), GetMessage("SUP_WORD_YAZIK_END4"))), str_replace("#NUM#", $arTypesUpdates["HELPS"]["@"]["COUNT"], GetMessage("SUP_HUPD_PROMT")));
			if (IntVal($arTypesUpdates["HELPS"]["@"]["OTHER_COUNT"])>0)
				echo GetMessage("SUP_HUPD_PROMT_OTHER");
			?>
			<br><br>

			<a href="sysupdate.php?STEP=2&TYPE=HELP&lang=<?= htmlspecialchars($lang) ?>"><img src="/bitrix/images/main/icon2.gif" width="15" height="15" border="0" alt="<?= GetMessage("SUP_HUPD_ACT_ALT") ?>"> <?= GetMessage("SUP_HUPD_ACT") ?> &gt;&gt;</a>
			<br><br><br>
			<?
		}
		?>

		<?
		if ($arTypesUpdates!==false
			&& isset($arTypesUpdates["CLIENT"])
			&& !isset($arTypesUpdates["UPDATE_SYSTEM"])
			&& count($arTypesUpdates["CLIENT"])>0
			&& $arTypesUpdates["CLIENT"]["@"]["RESERVED"]!="Y")
		{
			$arTypesUpdates["CLIENT"]["@"]["MAX_SITES"] = IntVal($arTypesUpdates["CLIENT"]["@"]["MAX_SITES"]);
			if ($arTypesUpdates["CLIENT"]["@"]["MAX_SITES"] > 0)
				echo str_replace("#END#", CUpdateSystem::NumberEndings($arTypesUpdates["CLIENT"]["@"]["MAX_SITES"], $lang, array(GetMessage("SUP_WORD_SAIT_END1"), GetMessage("SUP_WORD_SAIT_END2"), GetMessage("SUP_WORD_SAIT_END3"), GetMessage("SUP_WORD_SAIT_END4"))), str_replace("#NUM#", $arTypesUpdates["CLIENT"]["@"]["MAX_SITES"], GetMessage("SUP_CHECK_PROMT")));
			else
				echo GetMessage("SUP_CHECK_PROMT_2");
			?>
			<br><br>
			<?= GetMessage("SUP_CHECK_PROMT_1") ?>
			<br><br>

			<a href="sysupdate.php?TYPE=ADD_SITE&STEP=1&lang=<?= htmlspecialchars($lang) ?>"><img src="/bitrix/images/main/icon2.gif" width="15" height="15" border="0" alt="<?= GetMessage("SUP_SITES_ACT_ALT") ?>"> <?= GetMessage("SUP_CHECK_ACT") ?> &gt;&gt;</a>
			<br><br>
			<br>
			<?
		}
	}
	elseif (!$bLockUpdateSystemKernel)
	{
		?>
		<form method="POST" action="sysupdate.php" name="license_form">
			<font class="text">

			<script language="JavaScript">
			<!--
			function AgreeLicenseChecked(obj)
			{
				var obj1 = document.license_form.SUP_LICENSE_ENTER_DON;
				obj1.disabled = <?if ($MAIN_RIGHT < "W"):?>true<?else:?>!obj.checked<?endif;?>;
			}
			//-->
			</script>

			<?
			if (LANG == "ru")
				$license_text_file = "http://www.bitrixsoft.ru/license-ru.htm";
			else
				$license_text_file = "http://www.bitrixsoft.com/license-en.htm";
			?>
			<iframe name="license_text" src="<?= $license_text_file ?>" width="560" height="250" border="0" frameBorder="1" scrolling="yes"></iframe>

			<br><br>

			<input class="typeinput" type="checkbox" name="agree_license" value="Y" id="agree_license_id" OnClick="AgreeLicenseChecked(this)">
			&nbsp;<label for="agree_license_id"><?= GetMessage("LICENSE_AGREE_PROMT") ?></label>

			<br><br>

			<input type="hidden" name="TYPE" VALUE="AGREE_LICENSE_ACT">
			<input type="hidden" name="STEP" VALUE="1">
			<input type="hidden" name="lang" VALUE="<?= htmlspecialchars($lang) ?>">
			<input disabled id="SUP_LICENSE_ENTER_DO" name="SUP_LICENSE_ENTER_DON" class="button" type="submit" value="<?= GetMessage("SUP_LICENSE_ENTER_DO") ?>">
			</center>

			</font>

		</form>

		<br><br>
		<?
	}
	?>


	<?
	if (!$bLockUpdateSystemKernel)
	{
		if ($stableVersionsOnly == "N")
			echo GetMessage("SUP_STABLE_OFF_PROMT");
		else
			echo GetMessage("SUP_STABLE_ON_PROMT");
		?>
		<br><?= GetMessage("SUP_STABLE_PROMT");?>
		<br><br>

		<a href="sysupdate.php?TYPE=TURN_STABILITY&STEP=1&lang=<?= htmlspecialchars($lang) ?>"><img src="/bitrix/images/main/icon2.gif" width="15" height="15" border="0" alt="<?= (($stableVersionsOnly == "N") ? GetMessage("SUP_STABLE_TURN_ON") : GetMessage("SUP_STABLE_TURN_OFF")) ?>"> <?= (($stableVersionsOnly == "N") ? GetMessage("SUP_STABLE_TURN_ON") : GetMessage("SUP_STABLE_TURN_OFF")) ?> &gt;&gt;</a>
		<br><br>
		<br>
		<?
	}
	?>


	<?= GetMessage("SUP_1STEP_NOTES1") ?>
	<br><br>

	<!-- пользовательские / системные -->
	<?= GetMessage("SUP_1STEP_NOTES2") ?>
	<br><br>

	<a href="sysupdate.php?TYPE=HISTORY&lang=<?= LANG ?>"><?= GetMessage("SUP_HISTORY_LINK") ?></a><br><br>

	<br>

	<?
	if (!$bLockUpdateSystemKernel)
	{
		?>
		<!-- Подписка -->
		<form method="POST" action="sysupdate.php">
			<?if (isset($arTypesUpdates["CLIENT"]["@"]["SUBSCR_EMAIL"]) && strlen($arTypesUpdates["CLIENT"]["@"]["SUBSCR_EMAIL"])>0):?>
				<?= GetMessage("SUP_SUBSCR_ALREADY_U") ?><br>
			<?else:?>
				<?= GetMessage("SUP_SUBSCR_NEW_U") ?><br>
			<?endif;?>
			<input type="text" name="SUBSCR_EMAIL" class="typeinput" VALUE="<?= ((isset($arTypesUpdates["CLIENT"]["@"]["SUBSCR_EMAIL"])) ? htmlspecialchars($arTypesUpdates["CLIENT"]["@"]["SUBSCR_EMAIL"]) : "") ?>" size="40">
			<input type="hidden" name="TYPE" VALUE="SUBSCR_UPDATE">
			<input type="hidden" name="STEP" VALUE="1">
			<input type="hidden" name="lang" VALUE="<?= htmlspecialchars($lang) ?>">
			<?if (isset($arTypesUpdates["CLIENT"]["@"]["SUBSCR_EMAIL"]) && strlen($arTypesUpdates["CLIENT"]["@"]["SUBSCR_EMAIL"])>0):?>
				<input <?if ($MAIN_RIGHT<"W") echo "disabled" ?> class="button" type="submit" value="<?= GetMessage("SUP_SUBSCR_ALREADY_CHANGE_U") ?>">
				<input <?if ($MAIN_RIGHT<"W") echo "disabled" ?> class="button" type="submit" name="SUBSCR_DELETE" value="<?= GetMessage("SUP_SUBSCR_ALREADY_DEL_U") ?>">
			<?else:?>
				<input <?if ($MAIN_RIGHT<"W") echo "disabled" ?> class="button" type="submit" value="<?= GetMessage("SUP_SUBSCR_ALREADY_ADD_U") ?>">
			<?endif;?>
		</form>
		<br><br>
		<?
	}
	?>

	</font>
	<?
}

/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@   ФОРМЫ МОДУЛЕЙ   @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/

/**********************************************************************/
/******   ШАГ 2  /ФОРМА/   ********************************************/
/**********************************************************************/
if ($TYPE=="UPDATE" && $STEP==2 && $MAIN_RIGHT=="W")
{
	if (strlen($strErrorMessage)>0)
	{
		?><font class="errortext"><?= $strErrorMessage ?></font><br><br><?
	}

	// Если пришли ошибки с сервера
	if ($arModulesUpdates!==false
		&& isset($arModulesUpdates["ERROR"])
		&& count($arModulesUpdates["ERROR"])>0)
	{
		for ($i = 0; $i < count($arModulesUpdates["ERROR"]); $i++)
		{
			?><font class="errortext">
			<?if (strlen($arModulesUpdates["ERROR"][$i]["@"]["TYPE"])>0):?>
				[<?= $arModulesUpdates["ERROR"][$i]["@"]["TYPE"] ?>]
			<?endif;?>
			<?= $arModulesUpdates["ERROR"][$i]["#"] ?></font><br><?
		}
		?><br><?
	}
	?>
	<font class="text">
	<?= GetMessage("SUP_STEP2_UPD_PROMT") ?>
	</font>

	<?if (!$arModulesUpdates
		|| !isset($arModulesUpdates["MODULES"])
		|| !isset($arModulesUpdates["MODULES"]["#"]["MODULE"])
		|| !is_array($arModulesUpdates["MODULES"]["#"]["MODULE"])
		|| count($arModulesUpdates["MODULES"]["#"]["MODULE"])<=0):?>
		<p><center><font class="errortext"><?= GetMessage("SUP_STEP2_NO_MODULES") ?></font></center>
	<?endif;?>

	<table border="0" align="center" width="95%">
	<tr>
		<td align="left">
			<form method="post" action="sysupdate.php" onsubmit="return ValidateForm(this);">
				<input type="submit" name="load_button" id="load_button" value="<?= GetMessage("SUP_STEP2_LOAD_BTN") ?>" <?if (!isset($arModulesUpdates["MODULES"]) || !isset($arModulesUpdates["MODULES"]["#"]["MODULE"]) || !is_array($arModulesUpdates["MODULES"]["#"]["MODULE"]) || !count($arModulesUpdates["MODULES"]["#"]["MODULE"])>0) echo "disabled";?>>
				<input type="hidden" name="load_modules" id="load_modules" value="">
				<input type="hidden" name="STEP" value="3">
				<input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
				<input type="hidden" name="TYPE" value="UPDATE">
			</form>
		</td>
		<td align="right">
			<font class="text">
				<input type="text" name="total_size" id="total_size" size="20" style="font-size:11px;font-family:verdana,arial;border: 0 solid;" readonly value="">
			</font>
		</td>
	</tr>
	</table>
	<?
}

/**********************************************************************/
/******   ШАГ 3  /ФОРМА/   ********************************************/
/**********************************************************************/
if ($TYPE=="UPDATE" && $STEP==3 && $MAIN_RIGHT=="W")
{
	if (strlen($strErrorMessage)>0)
	{
		?><font class="errortext"><?= $strErrorMessage ?></font><br><br><?
	}

	// Если пришли ошибки с сервера
	if ($arModulesUpdates!==false
		&& isset($arModulesUpdates["ERROR"])
		&& count($arModulesUpdates["ERROR"])>0)
	{
		for ($i = 0; $i < count($arModulesUpdates["ERROR"]); $i++)
		{
			?><font class="errortext">
			<?if (strlen($arModulesUpdates["ERROR"][$i]["@"]["TYPE"])>0):?>
				[<?= $arModulesUpdates["ERROR"][$i]["@"]["TYPE"] ?>]
			<?endif;?>
			<?= $arModulesUpdates["ERROR"][$i]["#"] ?></font><br><?
		}
		?><br><?
	}
	?>
	<font class="text">
	<?= GetMessage("SUP_STEP3_UPD_PROMT") ?>
	</font>

	<?if (!$arModulesUpdates
		|| !isset($arModulesUpdates["MODULES"])
		|| !isset($arModulesUpdates["MODULES"]["#"]["MODULE"])
		|| !is_array($arModulesUpdates["MODULES"]["#"]["MODULE"])
		|| count($arModulesUpdates["MODULES"]["#"]["MODULE"])<=0):?>
		<p><center><font class="errortext"><?= GetMessage("SUP_STEP3_NO_MODULES") ?></font></center>
	<?endif;?>

	<table border="0" align="center" width="95%">
	<tr>
		<td align="left">
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
		<td align="right">
			<font class="text">
				<input type="text" name="total_size" id="total_size" size="20" style="font-size:11px;font-family:verdana,arial;border: 0 solid;" readonly value="">
			</font>
		</td>
	</tr>
	</table>
	<?
}

/**********************************************************************/
/******   ШАГИ 2 и 3  /ФОРМА/   ***************************************/
/**********************************************************************/
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
	<iframe ID="updates_list" name="updates_list" src="sys_update_list.php?lang=<?echo LANG?>" width="100%" height="100%"></iframe>
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
	oTotalSizeInfo.value = "<?= GetMessage("SUP_TOTAL_UPDS") ?>: "+totalNumUpd+" = "+iSize_tmp+" "+degKoeffName;


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

		oTotalSizeInfo.value = "<?= GetMessage("SUP_TOTAL_UPDS") ?>: "+totalNumUpd+" = "+iSize_tmp+" "+degKoeffName;
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

		oTotalSizeInfo.value = "<?= GetMessage("SUP_TOTAL_UPDS") ?>: "+totalNumUpd+" = "+iSize_tmp+" "+degKoeffName;
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

		ResizeUpdatesList();

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
	window.onresize = ResizeUpdatesList;
	setTimeout("PrepareUpdatesList();", 2000);
	</script>
	<?
}

/**********************************************************************/
/******   ШАГ 4  /ФОРМА/   ********************************************/
/**********************************************************************/
if ($TYPE=="UPDATE" && $STEP==4 && $MAIN_RIGHT=="W")
{
	if (strlen($strErrorMessage)>0)
	{
		?><font class="errortext"><?= $strErrorMessage ?></font><br><br><?
	}
	?>
	<font class="text">
	<?= GetMessage("SUP_STEP4_UPD_PROMT") ?>
	<br><br>

	<?
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
				{
					$strErrorModules .= " - <font class=\"text\">";
					$strErrorModules .= "<a href=\"module_admin.php?lang=".LANG."&id=".urlencode($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"])."&".bitrix_sessid_get()."&install=".urlencode(GetMessage("SUP_STEP4_INST"))."\" target=\"_blank\" title=\"".GetMessage("SUP_STEP4_INST_ALT")."\">".GetMessage("SUP_STEP4_INST_DO")."</a>";
					$strErrorModules .= "</font>";
				}
				$strErrorModules .= "<br>";
				$strErrorModules .= "<i>".$arErrorModules[$arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"]]."</i><br>";

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
				{
					$strSuccessModules .= " - <font class=\"text\">";
					$strSuccessModules .= "<a href=\"module_admin.php?lang=".LANG."&id=".urlencode($arModulesUpdates["MODULES"]["#"]["MODULE"][$i]["@"]["ID"])."&".bitrix_sessid_get()."&install=".urlencode(GetMessage("SUP_STEP4_INST"))."\" target=\"_blank\" title=\"".GetMessage("SUP_STEP4_INST_ALT")."\">".GetMessage("SUP_STEP4_INST_DO")."</a>";
					$strSuccessModules .= "</font>";
				}
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

		if (strlen($strSuccessModules)>0)
		{
			?><?= GetMessage("SUP_STEP4_SUCCESS") ?>:<br><br><?
			echo $strSuccessModules;
			echo "<br><br>";
		}

		if (strlen($strErrorModules)>0)
		{
			?><?= GetMessage("SUP_STEP4_ERROR") ?>:<br><br><?
			echo $strErrorModules;
			echo "<br><br>";
		}

		if (strlen($strNoneModules)>0)
		{
			?><?= GetMessage("SUP_STEP4_NONE") ?>:<br><br><?
			echo $strNoneModules;
			echo "<br><br>";
		}
	}
	?>

	<br>

	<a href="sysupdate.php?TYPE=HISTORY&lang=<?= LANG ?>"><?= GetMessage("SUP_HISTORY_LINK") ?></a><br><br>
	<a href="sysupdate.php?STEP=1&TYPE=UPDATE&lang=<?= LANG ?>">&lt;&lt;&nbsp;<?= GetMessage("SUP_MAIN_PAGE") ?></a>

	</font>
	<?
	CUpdateSystem::EraseOldFolders(2);
	CUpdateSystem::Report2Server($arServerReport);
}
/**********************************************************************/
/******   КОНЕЦ ШАГОВ  /ФОРМА/   **************************************/
/**********************************************************************/

/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@   ФОРМЫ ЯЗЫКОВ   @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/

/**********************************************************************/
/******   ШАГ 2  /ЯЗЫКИ/   ********************************************/
/**********************************************************************/
if ($TYPE=="LANGS" && $STEP==2 && $MAIN_RIGHT=="W")
{
	if (strlen($strErrorMessage)>0)
	{
		?><font class="errortext"><?= $strErrorMessage ?></font><br><br><?
	}

	// Если пришли ошибки с сервера
	if ($arLangsUpdates!==false
		&& isset($arLangsUpdates["ERROR"])
		&& count($arLangsUpdates["ERROR"])>0)
	{
		for ($i = 0; $i < count($arLangsUpdates["ERROR"]); $i++)
		{
			?><font class="errortext">
			<?if (strlen($arLangsUpdates["ERROR"][$i]["@"]["TYPE"])>0):?>
				[<?= $arLangsUpdates["ERROR"][$i]["@"]["TYPE"] ?>]
			<?endif;?>
			<?= $arLangsUpdates["ERROR"][$i]["#"] ?></font><br><?
		}
		?><br><?
	}
	?>
	<font class="text">
	<a href="sysupdate.php?STEP=1&TYPE=UPDATE&lang=<?= LANG ?>">&lt;&lt;&nbsp;<?= GetMessage("SUP_MAIN_PAGE") ?></a><br><br>
	<?= GetMessage("SUP_LSTEP2_PROMT") ?>
	</font>

	<?if (!$arLangsUpdates
		|| !isset($arLangsUpdates["LANGS"])
		|| !isset($arLangsUpdates["LANGS"]["#"]["LANG"])
		|| !is_array($arLangsUpdates["LANGS"]["#"]["LANG"])
		|| count($arLangsUpdates["LANGS"]["#"]["LANG"])<=0):?>
		<p><center><font class="errortext"><?= GetMessage("SUP_LSTEP2_NO_LANGS") ?></font></center>
	<?endif;?>

	<table border="0" align="center" width="95%">
	<tr>
		<td align="left">
			<form method="post" action="sysupdate.php" onsubmit="return ValidateForm(this);">
				<input type="submit" name="load_button" id="load_button" value="<?= GetMessage("SUP_LSTEP2_LOAD_BTN") ?>" disabled>
				<input type="hidden" name="load_langs" id="load_langs" value="">
				<input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
				<input type="hidden" name="STEP" value="3">
				<input type="hidden" name="UNIID" value="<?echo md5(time());?>">
				<input type="hidden" name="TYPE" value="LANGS">
			</form>
		</td>
	</tr>
	</table>

	<script language="javascript">
	var arLangs = new Array();
	</script>

	<?
	if ($arLangsUpdates
		&& isset($arLangsUpdates["LANGS"])
		&& is_array($arLangsUpdates["LANGS"]["#"]["LANG"])
		&& count($arLangsUpdates["LANGS"]["#"]["LANG"])>0)
	{
		?>
		<table border="0" align="center" width="95%">
		<tr>
			<td align="left">
				<font class="text">

				<?
				$strPHPDateFormat = CUpdateSystem::GetDateFormat(false);

				$strLangsHTML1 = "";
				$strLangsHTML2 = "";
				for ($i = 0; $i < count($arLangsUpdates["LANGS"]["#"]["LANG"]); $i++)
				{
					?>
					<script language="javascript">
						arLangs[<?= $i ?>] = Array('<?= $arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["ID"] ?>', 'N');
					</script>
					<?
					$dDate = mktime(0, 0, 0,
						substr($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["DATE"], 4, 2),
						substr($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["DATE"], 6, 2),
						substr($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["DATE"], 0, 4));

					$strLID = $arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["ID"];

					if ($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["TYPE"]=="INST")
					{
						$strLangsHTML1 .= "<input type=\"checkbox\" name=\"load_lang_cbox_".$i."\" id=\"load_lang_cbox_".$i."\" value=\"".$strLID."\" OnClick=\"LangClick(this, ".$i.")\">&nbsp;&nbsp;";
						$strLangsHTML1 .= "<b><label for=\"load_lang_cbox_".$i."\">[".htmlspecialchars($strLID)."]&nbsp;&nbsp;";
						$strLangsHTML1 .= htmlspecialchars($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["NAME"])."&nbsp;&nbsp;";
						$strLangsHTML1 .= "(".GetMessage("SUP_LSTEP2_FROM")."&nbsp;".Date($strPHPDateFormat, $dDate).")</label></b><br>";
					}
					else
					{
						$strLangsHTML2 .= "<input type=\"checkbox\" name=\"load_lang_cbox_".$i."\" id=\"load_lang_cbox_".$i."\" value=\"".$strLID."\" OnClick=\"LangClick(this, ".$i.")\">&nbsp;&nbsp;";
						$strLangsHTML2 .= "<label for=\"load_lang_cbox_".$i."\">[".htmlspecialchars($strLID)."]&nbsp;&nbsp;";
						$strLangsHTML2 .= htmlspecialchars($arLangsUpdates["LANGS"]["#"]["LANG"][$i]["@"]["NAME"])."&nbsp;&nbsp;";
						$strLangsHTML2 .= "(".GetMessage("SUP_LSTEP2_FROM")."&nbsp;".Date($strPHPDateFormat, $dDate).")</label><br>";
					}
				}

				if (strlen($strLangsHTML1)>0)
				{
					?>
					<hr align="left" width="98%" size="1" color="#CCCCCC">
					<b><?= GetMessage("SUP_LSTEP2_INST") ?>:</b><br><br>
					<?
					echo $strLangsHTML1;
					echo "<br><br>";
				}

				if (strlen($strLangsHTML2)>0)
				{
					?>
					<hr align="left" width="98%" size="1" color="#CCCCCC">
					<?= GetMessage("SUP_LSTEP2_OTHER") ?>:<br><br>
					<?
					echo $strLangsHTML2;
				}
				?>
				</font>
			</td>
		</tr>
		</table>
		<?
	}
	?>

	<script language="javascript">
	var ns4 = (document.layers) ? true : false;
	var ie4 = (document.all) ? true : false;

	var oLoadLangs = (ie4) ? document.all['load_langs'] : document.getElementById('load_langs');
	var oLoadButton = (ie4) ? document.all['load_button'] : document.getElementById('load_button');
	var totalNumUpd = 0;

	function ValidateForm(form)
	{
		oLoadButton.disabled = true;
		return true;
	}

	function MakeLoadLangsList()
	{
		var strLoadLangsUpd_tmp = "";
		for (i = 0; i < arLangs.length; i++)
		{
			if (arLangs[i][1]=="Y")
			{
				if (strLoadLangsUpd_tmp.length>0)
					strLoadLangsUpd_tmp = strLoadLangsUpd_tmp + ",";

				strLoadLangsUpd_tmp = strLoadLangsUpd_tmp + arLangs[i][0];
			}
		}
		return strLoadLangsUpd_tmp;
	}

	function LangClick(elem, ind)
	{
		if (elem.checked)
		{
			arLangs[ind][1] = "Y";
			oLoadLangs.value = MakeLoadLangsList();
			totalNumUpd = totalNumUpd + 1;
		}
		else
		{
			arLangs[ind][1] = "N";
			oLoadLangs.value = MakeLoadLangsList();
			totalNumUpd = totalNumUpd - 1;
		}

		if (totalNumUpd<=0)
		{
			oLoadButton.disabled = true;
		}
		else
		{
			oLoadButton.disabled = false;
		}
	}
	</script>
	<?
}

/**********************************************************************/
/******   ШАГ 3  /ЯЗЫКИ/   ********************************************/
/**********************************************************************/
if ($TYPE=="LANGS" && $STEP==3 && $MAIN_RIGHT=="W")
{
	if (strlen($strErrorMessage)>0)
	{
		?><font class="errortext"><?= $strErrorMessage ?></font><br><br><?
	}

	// Если пришли ошибки с сервера
	if ($arLangsUpdates!==false
		&& isset($arLangsUpdates["ERROR"])
		&& count($arLangsUpdates["ERROR"])>0)
	{
		for ($i = 0; $i < count($arLangsUpdates["ERROR"]); $i++)
		{
			?><font class="errortext">
			<?if (strlen($arLangsUpdates["ERROR"][$i]["@"]["TYPE"])>0):?>
				[<?= $arLangsUpdates["ERROR"][$i]["@"]["TYPE"] ?>]
			<?endif;?>
			<?= $arLangsUpdates["ERROR"][$i]["#"] ?></font><br><?
		}
		?><br><?
	}
	?>
	<font class="text">
	<?= GetMessage("SUP_STEP4_UPD_PROMT") ?>
	<br><br>

	<?
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

		if (strlen($strSuccessLangs)>0)
		{
			?><?= GetMessage("SUP_LSTEP3_SUCCESS") ?>:<br><br><?
			echo $strSuccessLangs;
			echo "<br><br>";
		}

		if (strlen($strErrorLangs)>0)
		{
			?><?= GetMessage("SUP_LSTEP3_ERROR") ?>:<br><br><?
			echo $strErrorLangs;
			echo "<br><br>";
		}

		if (strlen($strNoneLangs)>0)
		{
			?><?= GetMessage("SUP_LSTEP3_NONE") ?>:<br><br><?
			echo $strNoneLangs;
			echo "<br><br>";
		}
	}
	?>

	<br>

	<a href="sysupdate.php?TYPE=HISTORY&lang=<?= LANG ?>"><?= GetMessage("SUP_HISTORY_LINK") ?></a><br><br>
	<a href="sysupdate.php?STEP=1&TYPE=UPDATE&lang=<?= LANG ?>">&lt;&lt;&nbsp;<?= GetMessage("SUP_MAIN_PAGE") ?></a>

	</font>
	<?
	CUpdateSystem::EraseOldFolders(2);
}
/**********************************************************************/
/******   КОНЕЦ ШАГОВ  /ЯЗЫКИ/   **************************************/
/**********************************************************************/

/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@   ФОРМЫ ПОМОЩИ   @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/

/**********************************************************************/
/******   ШАГ 2  /ХЕЛП/   ********************************************/
/**********************************************************************/
if ($TYPE=="HELP" && $STEP==2 && $MAIN_RIGHT=="W")
{
	if (strlen($strErrorMessage)>0)
	{
		?><font class="errortext"><?= $strErrorMessage ?></font><br><br><?
	}

	// Если пришли ошибки с сервера
	if ($arHelpUpdates!==false
		&& isset($arHelpUpdates["ERROR"])
		&& count($arHelpUpdates["ERROR"])>0)
	{
		for ($i = 0; $i < count($arHelpUpdates["ERROR"]); $i++)
		{
			?><font class="errortext">
			<?if (strlen($arHelpUpdates["ERROR"][$i]["@"]["TYPE"])>0):?>
				[<?= $arHelpUpdates["ERROR"][$i]["@"]["TYPE"] ?>]
			<?endif;?>
			<?= $arHelpUpdates["ERROR"][$i]["#"] ?></font><br><?
		}
		?><br><?
	}
	?>
	<font class="text">
	<a href="sysupdate.php?STEP=1&TYPE=UPDATE&lang=<?= LANG ?>">&lt;&lt;&nbsp;<?= GetMessage("SUP_MAIN_PAGE") ?></a><br><br>
	<?= GetMessage("SUP_HSTEP2_PROMT") ?>
	</font>

	<?if (!$arHelpUpdates
		|| !isset($arHelpUpdates["HELPS"])
		|| !isset($arHelpUpdates["HELPS"]["#"]["HELP"])
		|| !is_array($arHelpUpdates["HELPS"]["#"]["HELP"])
		|| count($arHelpUpdates["HELPS"]["#"]["HELP"])<=0):?>
		<p><center><font class="errortext"><?= GetMessage("SUP_HSTEP2_NO_UPDS") ?></font></center>
	<?endif;?>

	<table border="0" align="center" width="95%">
	<tr>
		<td align="left">
			<form method="post" action="sysupdate.php" onsubmit="return ValidateForm(this);">
				<input type="submit" name="load_button" id="load_button" value="<?= GetMessage("SUP_HSTEP2_LOAD_BTN") ?>" disabled>
				<input type="hidden" name="load_help" id="load_help" value="">
				<input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
				<input type="hidden" name="STEP" value="3">
				<input type="hidden" name="UNIID" value="<?echo md5(time());?>">
				<input type="hidden" name="TYPE" value="HELP">
			</form>
		</td>
	</tr>
	</table>

	<?
	if ($arHelpUpdates
		&& isset($arHelpUpdates["HELPS"])
		&& is_array($arHelpUpdates["HELPS"]["#"]["HELP"])
		&& count($arHelpUpdates["HELPS"]["#"]["HELP"])>0)
	{
		?>
		<table border="0" align="center" width="95%">
		<tr>
			<td align="left">
				<font class="text">

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

					if ($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["TYPE"]=="INST")
					{
						$strHelpsHTML1 .= "<input type=\"radio\" name=\"load_lang_rd\" id=\"load_lang_rd_".$i."\" value=\"".$strLID."\" OnClick=\"HelpClick(this)\">&nbsp;&nbsp;";
						$strHelpsHTML1 .= "<b><label for=\"load_lang_rd_".$i."\">[".htmlspecialchars($strLID)."]&nbsp;&nbsp;";
						$strHelpsHTML1 .= htmlspecialchars($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["NAME"])."&nbsp;&nbsp;";
						$strHelpsHTML1 .= "(".GetMessage("SUP_LSTEP2_FROM")."&nbsp;".Date($strPHPDateFormat, $dDate).")</label></b><br>";
					}
					else
					{
						$strHelpsHTML2 .= "<input type=\"radio\" name=\"load_lang_rd\" id=\"load_lang_rd_".$i."\" value=\"".$strLID."\" OnClick=\"HelpClick(this)\">&nbsp;&nbsp;";
						$strHelpsHTML2 .= "<label for=\"load_lang_rd_".$i."\">[".htmlspecialchars($strLID)."]&nbsp;&nbsp;";
						$strHelpsHTML2 .= htmlspecialchars($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["NAME"])."&nbsp;&nbsp;";
						$strHelpsHTML2 .= "(".GetMessage("SUP_LSTEP2_FROM")."&nbsp;".Date($strPHPDateFormat, $dDate).")</label><br>";
					}
				}

				if (strlen($strHelpsHTML1)>0)
				{
					?>
					<hr align="left" width="98%" size="1" color="#CCCCCC">
					<b><?= GetMessage("SUP_HSTEP2_INST") ?>:</b><br><br>
					<?
					echo $strHelpsHTML1;
					echo "<br><br>";
				}

				if (strlen($strHelpsHTML2)>0)
				{
					?>
					<hr align="left" width="98%" size="1" color="#CCCCCC">
					<?= GetMessage("SUP_HSTEP2_OTHER") ?>:<br><br>
					<?
					echo $strHelpsHTML2;
				}
				?>
				</font>
			</td>
		</tr>
		</table>
		<?
	}
	?>

	<script language="javascript">
	var ns4 = (document.layers) ? true : false;
	var ie4 = (document.all) ? true : false;

	var oLoadHelps = (ie4) ? document.all['load_help'] : document.getElementById('load_help');
	var oLoadButton = (ie4) ? document.all['load_button'] : document.getElementById('load_button');

	function ValidateForm(form)
	{
		oLoadButton.disabled = true;
		return true;
	}

	function HelpClick(elem)
	{
		elem.checked = true;
		oLoadHelps.value = elem.value;
		oLoadButton.disabled = false;
	}
	</script>
	<?
}

/**********************************************************************/
/******   ШАГ 3  /ХЕЛП/   ********************************************/
/**********************************************************************/
if ($TYPE=="HELP" && $STEP==3 && $MAIN_RIGHT=="W")
{
	if (strlen($strErrorMessage)>0)
	{
		?><font class="errortext"><?= $strErrorMessage ?></font><br><br><?
	}

	// Если пришли ошибки с сервера
	if ($arHelpUpdates!==false
		&& isset($arHelpUpdates["ERROR"])
		&& count($arHelpUpdates["ERROR"])>0)
	{
		for ($i = 0; $i < count($arHelpUpdates["ERROR"]); $i++)
		{
			?><font class="errortext">
			<?if (strlen($arHelpUpdates["ERROR"][$i]["@"]["TYPE"])>0):?>
				[<?= $arHelpUpdates["ERROR"][$i]["@"]["TYPE"] ?>]
			<?endif;?>
			<?= $arHelpUpdates["ERROR"][$i]["#"] ?></font><br><?
		}
		?><br><?
	}
	?>
	<font class="text">
	<?= GetMessage("SUP_STEP4_UPD_PROMT") ?>
	<br><br>

	<?
	if ($arHelpUpdates
		&& isset($arHelpUpdates["HELPS"])
		&& is_array($arHelpUpdates["HELPS"]["#"]["HELP"])
		&& count($arHelpUpdates["HELPS"]["#"]["HELP"])>0)
	{
		$strPHPDateFormat = CUpdateSystem::GetDateFormat(false);

		for ($i = 0; $i < count($arHelpUpdates["HELPS"]["#"]["HELP"]); $i++)
		{
			if (array_key_exists($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["ID"], $arErrorHelp))
			{
				$dDate = mktime(0, 0, 0,
					substr($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["DATE"], 4, 2),
					substr($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["DATE"], 6, 2),
					substr($arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["DATE"], 0, 4));

				echo str_replace("#DATE#", Date($strPHPDateFormat, $dDate), str_replace("#HELP#", $arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["NAME"]." (".$arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["ID"].")", GetMessage("SUP_HSTEP3_UPD_ERR")));
				?>
				<br>
				<i><?= $arErrorHelp[$arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["ID"]] ?></i>
				<br><br>
				<?
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

				echo str_replace("#DATE#", Date($strPHPDateFormat, $dDate), str_replace("#HELP#", $arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["NAME"]." (".$arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["ID"].")", GetMessage("SUP_HSTEP3_UPD_SUC")));
				?>
				<br><br>
				<?
				CUpdateSystem::AddMessage2Log(
					str_replace("#DATE#", Date($strPHPDateFormat, $dDate), str_replace("#HELP#", $arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["NAME"]." (".$arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["ID"].")", GetMessage("SUP_HSTEP3_UPD_LOG"))),
					"UPD_SUCCESS");
			}
			else
			{
				echo str_replace("#HELP#", $arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["NAME"]." (".$arHelpUpdates["HELPS"]["#"]["HELP"][$i]["@"]["ID"].")", GetMessage("SUP_HSTEP3_UPD_NONE"));
				?>
				<br><br>
				<?
			}
		}
	}
	?>
	<br><br>
	<br>

	<a href="sysupdate.php?TYPE=HISTORY&lang=<?= LANG ?>"><?= GetMessage("SUP_HISTORY_LINK") ?></a><br><br>
	<a href="sysupdate.php?STEP=1&TYPE=UPDATE&lang=<?= LANG ?>">&lt;&lt;&nbsp;<?= GetMessage("SUP_MAIN_PAGE") ?></a>

	</font>
	<?
	CUpdateSystem::EraseOldFolders(2);
}
/**********************************************************************/
/******   КОНЕЦ ШАГОВ  /ХЕЛП/   **************************************/
/**********************************************************************/

/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@   ФОРМЫ ИСТОРИИ   @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/

/**********************************************************************/
/******   ИСТОРИЯ   ***************************************************/
/**********************************************************************/
if ($TYPE=="HISTORY")
{
	?>
	<font class="text">
	<a href="sysupdate.php?STEP=1&TYPE=UPDATE&lang=<?= LANG ?>">&lt;&lt;&nbsp;<?= GetMessage("SUP_MAIN_PAGE") ?></a><br><br>
	<?= GetMessage("SUP_HIST_PROMT") ?>
	<br><br>

	<?
	$iMaxNumRecs = 20;
	$arLogRecs = array();
	if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/updater.log")
		&& is_file($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/updater.log")
		&& is_readable($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/updater.log"))
	{
		$logf = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/updater.log", "r");
		while (!feof($logf))
		{
			$buffer = fgets($logf, 8192);
			if (substr($buffer, strlen("0000-00-00 00:00:00 "), strlen("- UPD_SUCCESS -"))=="- UPD_SUCCESS -")
			{
				array_unshift(
					$arLogRecs,
					array(
						"S",
						substr($buffer, 0, strlen("0000-00-00 00:00:00")),
						substr($buffer, strlen("0000-00-00 00:00:00 - UPD_SUCCESS - "))
					));
				if (count($arLogRecs)>$iMaxNumRecs)
					array_splice($arLogRecs, $iMaxNumRecs);
			}
			elseif (substr($buffer, strlen("0000-00-00 00:00:00 "), strlen("- UPD_ERROR -"))=="- UPD_ERROR -")
			{
				array_unshift(
					$arLogRecs,
					array(
						"E",
						substr($buffer, 0, strlen("0000-00-00 00:00:00")),
						substr($buffer, strlen("0000-00-00 00:00:00 - UPD_ERROR - "))
					));
				if (count($arLogRecs)>$iMaxNumRecs)
					array_splice($arLogRecs, $iMaxNumRecs);
			}
			elseif (substr($buffer, strlen("0000-00-00 00:00:00 "), strlen("- UPD_NOTE -"))=="- UPD_NOTE -")
			{
				array_unshift(
					$arLogRecs,
					array(
						"N",
						substr($buffer, 0, strlen("0000-00-00 00:00:00")),
						substr($buffer, strlen("0000-00-00 00:00:00 - UPD_NOTE - "))
					));
				if (count($arLogRecs)>$iMaxNumRecs)
					array_splice($arLogRecs, $iMaxNumRecs);
			}
		}
		fclose($logf);
	}
	if (count($arLogRecs)>0)
	{
		?>
		<script language="JavaScript">
		<!--
		<?
		for ($i = 0; $i < count($arLogRecs); $i++)
		{
			$strDetDescr = "";
			$arLogRecs[$i][3] = "N";
			$pos1 = strpos($arLogRecs[$i][2], "<br>");
			if ($pos1!==false)
			{
				$strDetDescr = Trim(substr($arLogRecs[$i][2], $pos1 + 4));
				$arLogRecs[$i][2] = substr($arLogRecs[$i][2], 0, $pos1);
				$arLogRecs[$i][3] = "Y";
			}
			?>
			DescrDetList_<?echo $i ?> = "<?= $strDetDescr ?>";
			<?
		}
		?>
		function DescrDetListWindow(sModule)
		{
			var left, top;
			if('['+typeof(window.event)+']' == "[object]")
			{
				top = (window.event.screenY+10);
				left = (window.event.screenX-125);
			}
			else
			{
				top = Math.floor((screen.height - 50)/2-14);
				left = Math.floor((screen.width - 250)/2-5);
			}
			var wnd = window.open('','','scrollbars=yes,resizable=yes,width=300,height=200,left='+left+',top='+top);
			wnd.document.write('<html><head><title><?echo GetMessage("HINT_WIND_TITLE") ?></title>\n');
			wnd.document.write('<script language="JavaScript">\n');
			wnd.document.write('<!'+'--\n');
			wnd.document.write('function KeyPress()\n');
			wnd.document.write('{\n');
			wnd.document.write('\tif(window.event.keyCode == 27)\n');
			wnd.document.write('\t\twindow.close();\n');
			wnd.document.write('}\n');
			wnd.document.write('//-'+'->\n');
			wnd.document.write('</script>\n');
			wnd.document.write('<meta http-equiv="Content-Type" content="text/html; charset=windows-1251"></head>\n');
			wnd.document.write('<body bgcolor="#FFFFFF" text="#000000" onKeyPress="KeyPress()">\n');
			wnd.document.write('<font face="Arial,helvetica" size="-1">');
			wnd.document.write(eval("DescrDetList_"+sModule));
			wnd.document.write('</font>');
			wnd.document.write('\n</body></html>');
		}
		//-->
		</script>

		<table border="0" cellspacing="1" cellpadding="2" width="99%">
		<tr>
			<td align="center" class="tablehead1"><font class="tableheadtext"><?= GetMessage("SUP_HIST_DATE") ?></font></td>
			<td align="center" class="tablehead2"><font class="tableheadtext"><?= GetMessage("SUP_HIST_DESCR") ?></font></td>
			<td align="center" class="tablehead3"><font class="tableheadtext"><?= GetMessage("SUP_HIST_STATUS") ?></font></td>
		</tr>
		<?
		for ($i = 0; $i < count($arLogRecs); $i++)
		{
			?>
			<tr>
				<td align="center" class="tablebody1">
					<font class="tablebodytext"><?= $arLogRecs[$i][1] ?></font>
				</td>
				<td align="left" class="tablebody2">
					<font class="tablebodytext">
						<?= $arLogRecs[$i][2] ?>
						<?if ($arLogRecs[$i][3]=="Y"):?>
							<a href="javascript:void(0)" onClick="DescrDetListWindow('<?echo $i ?>')" title="<?echo GetMessage("HINT_WIND_EXEC_ALT")?>"><?echo GetMessage("HINT_WIND_EXEC")?></a>
						<?endif;?>
					</font>
				</td>
				<td align="center" class="tablebody3">
					<font class="tablebodytext"><?
						if ($arLogRecs[$i][0]=="S")
							echo GetMessage("SUP_HIST_SUCCESS");
						elseif ($arLogRecs[$i][0]=="E")
							echo GetMessage("SUP_HIST_ERROR");
						elseif ($arLogRecs[$i][0]=="N")
							echo GetMessage("SUP_HIST_NOTES");
					?></font>
				</td>
			</tr>
			<?
		}
		?>
		</table>
		<?
	}
	else
	{
		?><b><?= GetMessage("SUP_HIST_EMPTY_LOG") ?></b><br><?
	}
	?>

	<br><br>
	<b><?= GetMessage("SUP_HIST_PNOTES1") ?></b> <?= GetMessage("SUP_HIST_PNOTES2") ?>
	</font>
	<?
}

/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@   ФОРМЫ ДОБАВЛЕНИЯ САЙТОВ   @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/

/**********************************************************************/
/******   ДОБАВЛЕНИЕ САЙТОВ   *****************************************/
/**********************************************************************/
if ($TYPE=="ADD_SITE" && $STEP==1 && $MAIN_RIGHT=="W")
{
	if (strlen($strErrorMessage)>0)
	{
		?><font class="errortext"><?= $strErrorMessage ?></font><br><br><?
	}
	?>
	<font class="text">
	<a href="sysupdate.php?STEP=1&TYPE=UPDATE&lang=<?= LANG ?>">&lt;&lt;&nbsp;<?= GetMessage("SUP_MAIN_PAGE") ?></a><br><br>

	<?= GetMessage("SUP_ADD_CHECK_PROMT") ?>:

	<form method="POST" action="sysupdate.php">
		<center>
		<input type="text" name="CHECK" class="typeinput" VALUE="" size="50">
		<input type="hidden" name="TYPE" VALUE="ADD_SITE">
		<input type="hidden" name="STEP" VALUE="2">
		<input type="hidden" name="lang" VALUE="<?= htmlspecialchars($lang) ?>">
		<input class="button" type="submit" value="<?= GetMessage("SUP_AD_CHECK_DO") ?>">
		</center>
	</form>

	</font>
	<?
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
