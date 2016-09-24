<?
/**********************************************************************/
/**    DO NOT MODIFY THIS FILE                                       **/
/**    MODIFICATION OF THIS FILE WILL ENTAIL SITE FAILURE            **/
/**********************************************************************/

//TODO: СИСТЕМА ОБНОВЛЕНИЙ, module.php, module_admin.php, 
//все файлы с CModule::CreateModuleObject ИЗМЕНЕНЫ!

// define("DEFAULT_UPDATE_SERVER", "www.bitrixsoft.com");
//define("DEFAULT_UPDATE_SERVER", "mysql.smn");

IncludeModuleLangFile(__FILE__);

if (!function_exists("file_get_contents"))
{
	function file_get_contents($filename)
	{
		$fd = fopen("$filename", "rb");
		$content = fread($fd, filesize($filename));
		fclose($fd);
		return $content;
	}
}

if (!defined("US_SHARED_KERNEL_PATH"))
	// define("US_SHARED_KERNEL_PATH", "/bitrix");

if (!defined("US_CALL_TYPE"))
	// define("US_CALL_TYPE", "ALL");

if (!defined("US_BASE_MODULE"))
	// define("US_BASE_MODULE", "main");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_class.php");

$GLOBALS["UPDATE_STRONG_UPDATE_CHECK"] = "";
$GLOBALS["CACHE4UPDATESYS_LICENSE_KEY"] = "";

class CUpdateClientPartner
{
	public static function RegisterModules(&$strError, $lang = false, $stableVersionsOnly = false)
	{
		$strError_tmp = "";

		CUpdateClientPartner::AddMessage2Log("exec CUpdateClientPartner::RegisterModules");

		$strQuery = CUpdateClientPartner::__CollectRequestData(
			$strError_tmp,
			$lang,
			$stableVersionsOnly,
			array(),
			array()
		);
		if ($strQuery === False || StrLen($strQuery) <= 0 || StrLen($strError_tmp) > 0)
		{
			$strError .= $strError_tmp;
			CUpdateClientPartner::AddMessage2Log("Empty query list", "GUL01");
			return False;
		}

		CUpdateClientPartner::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strQuery));

		$stime = CUpdateClientPartner::__GetMicroTime();
		$content = CUpdateClientPartner::__GetHTTPPage("REG", $strQuery, $strError_tmp);

		if (strlen($content) <= 0)
		{
			if (StrLen($strError_tmp) <= 0)
				$strError_tmp = "[GNSU02] ".GetMessage("SUPZ_EMPTY_ANSWER").". ";
		}

		CUpdateClientPartner::AddMessage2Log("TIME RegisterModules(request) ".Round(CUpdateClientPartner::__GetMicroTime() - $stime, 3)." sec");

		if (strlen($strError_tmp) <= 0)
		{
			if (!($fp1 = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz", "wb")))
				$strError_tmp .= "[URV02] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates", GetMessage("SUPP_RV_ER_TEMP_FILE")).". ";
		}

		if (strlen($strError_tmp) <= 0)
		{
			if (!fwrite($fp1, $content))
				$strError_tmp .= "[URV03] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz", GetMessage("SUPP_RV_WRT_TEMP_FILE")).". ";

			@fclose($fp1);
		}

		if (strlen($strError_tmp) <= 0)
		{
			$updatesDirTmp = "";
			if (!CUpdateClientPartner::UnGzipArchive($updatesDirTmp, $strError_tmp, true))
				$strError_tmp .= "[URV04] ".GetMessage("SUPP_RV_BREAK").". ";
		}

		if (strlen($strError_tmp) <= 0)
		{
			$updatesDirFull = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$updatesDirTmp;
			if (!file_exists($updatesDirFull."/update_info.xml") || !is_file($updatesDirFull."/update_info.xml"))
				$strError_tmp .= "[URV05] ".str_replace("#FILE#", $updatesDirFull."/update_info.xml", GetMessage("SUPP_RV_ER_DESCR_FILE")).". ";
		}

		if (strlen($strError_tmp) <= 0)
		{
			if (!is_readable($updatesDirFull."/update_info.xml"))
				$strError_tmp .= "[URV06] ".str_replace("#FILE#", $updatesDirFull."/update_info.xml", GetMessage("SUPP_RV_READ_DESCR_FILE")).". ";
		}

		if (strlen($strError_tmp) <= 0)
			$content = file_get_contents($updatesDirFull."/update_info.xml");

		//echo "!1!".htmlspecialcharsbx($content)."!2!";

		if (strlen($strError_tmp) <= 0)
		{
			$arRes = Array();
			CUpdateClientPartner::__ParseServerData($content, $arRes, $strError_tmp);
		}

		if (strlen($strError_tmp) <= 0)
		{
			if (isset($arRes["DATA"]["#"]["ERROR"]) && is_array($arRes["DATA"]["#"]["ERROR"]) && count($arRes["DATA"]["#"]["ERROR"]) > 0)
			{
				for ($i = 0, $n = count($arRes["DATA"]["#"]["ERROR"]); $i < $n; $i++)
				{
					if (strlen($arRes["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"]) > 0)
						$strError_tmp .= "[".$arRes["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"]."] ";

					$strError_tmp .= $arRes["DATA"]["#"]["ERROR"][$i]["#"].". ";
				}
			}
		}

		if (strlen($strError_tmp) <= 0)
		{
			$handle = @opendir($updatesDirFull);
			if ($handle)
			{
				while (false !== ($dir = readdir($handle)))
				{
					if ($dir == "." || $dir == "..")
						continue;

					if (file_exists($_SERVER["DOCUMENT_ROOT"].US_SHARED_KERNEL_PATH."/modules/".$dir))
					{
						$strError_tmp1 = "";
						CUpdateClientPartner::__CopyDirFiles($updatesDirFull."/".$dir, $_SERVER["DOCUMENT_ROOT"].US_SHARED_KERNEL_PATH."/modules/".$dir, $strError_tmp1, False);
						if (strlen($strError_tmp1) > 0)
							$strError_tmp .= $strError_tmp1;
					}
				}
				closedir($handle);
			}
		}

		if (strlen($strError_tmp) <= 0)
		{
			CUpdateClientPartner::AddMessage2Log("Modules registered successfully!", "CURV");
			CUpdateClientPartner::__DeleteDirFilesEx($updatesDirFull);
		}

		if (strlen($strError_tmp) > 0)
		{
			CUpdateClientPartner::AddMessage2Log($strError_tmp, "CURV");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}


	public static function loadModule4Wizard($moduleId, $lang = false)
	{
		$errorMessage = "";
		$arUpdateDescription = array();

		$loadResult = CUpdateClientPartner::LoadModulesUpdates($errorMessage, $arUpdateDescription, $lang, "Y", array($moduleId), true);

		if ($loadResult == "S")
		{
			CUpdateClientPartner::AddMessage2Log("loadModule4Wizard-Step", "LM4W01");
			return "STP";
		}
		elseif ($loadResult == "E")
		{
			if (strlen($errorMessage) <= 0)
				$errorMessage = "[LM4W02] ".GetMessage("SUPC_ME_PACK");
			CUpdateClientPartner::AddMessage2Log($errorMessage, "LM4W02");
			return "ERR".$errorMessage;
		}
		elseif ($loadResult == "F")
		{
			CUpdateClientPartner::AddMessage2Log("Finish - NOUPDATES", "LM4W03");
			return "FIN";
		}

		$temporaryUpdatesDir = "";
		if (!CUpdateClientPartner::UnGzipArchive($temporaryUpdatesDir, $errorMessage, true))
		{
			$errorMessage .= "[LM4W04] ".GetMessage("SUPC_ME_PACK").". ";
			CUpdateClientPartner::AddMessage2Log(GetMessage("SUPC_ME_PACK"), "LM4W04");
			return "ERR".$errorMessage;
		}

		if (!CUpdateClientPartner::CheckUpdatability($temporaryUpdatesDir, $errorMessage))
		{
			$errorMessage .= "[LM4W05] ".GetMessage("SUPC_ME_CHECK").". ";
			CUpdateClientPartner::AddMessage2Log(GetMessage("SUPC_ME_CHECK"), "LM4W05");
			return "ERR".$errorMessage;
		}

		$arStepUpdateInfo = $arUpdateDescription;

		if (isset($arStepUpdateInfo["DATA"]["#"]["ERROR"]))
		{
			for ($i = 0, $cnt = count($arStepUpdateInfo["DATA"]["#"]["ERROR"]); $i < $cnt; $i++)
				$errorMessage .= "[".$arStepUpdateInfo["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"]."] ".$arStepUpdateInfo["DATA"]["#"]["ERROR"][$i]["#"];
			return "ERR".$errorMessage;
		}

		if (isset($arStepUpdateInfo["DATA"]["#"]["NOUPDATES"]))
		{
			CUpdateClientPartner::ClearUpdateFolder($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$temporaryUpdatesDir);
			CUpdateClientPartner::AddMessage2Log("Finish - NOUPDATES", "LM4W06");
			return "FIN";
		}
		else
		{
			if (!CUpdateClientPartner::UpdateStepModules($temporaryUpdatesDir, $errorMessage))
			{
				$errorMessage .= "[LM4W07] ".GetMessage("SUPC_ME_UPDATE").". ";
				CUpdateClientPartner::AddMessage2Log(GetMessage("SUPC_ME_UPDATE"), "LM4W07");
				return "ERR".$errorMessage;
			}

			return "STP";
		}
	}


	public static function LoadModuleNoDemand($moduleId, &$strError, $stableVersionsOnly = "Y", $lang = false)
	{
		$strError_tmp = "";

		CUpdateClientPartner::AddMessage2Log("exec CUpdateClientPartner::LoadModuleNoDemand");

		$stableVersionsOnly = (($stableVersionsOnly == "N") ? "N" : "Y");

		if ($lang === false)
			$lang = LANGUAGE_ID;

		$strQuery = CUpdateClientPartner::__CollectRequestData($strError_tmp, $lang, $stableVersionsOnly, array($moduleId), array(), true);
		if ($strQuery === False || StrLen($strQuery) <= 0 || StrLen($strError_tmp) > 0)
		{
			if (StrLen($strError_tmp) <= 0)
				$strError_tmp = "[GNSU01] ".GetMessage("SUPZ_NO_QSTRING").". ";
		}

		if (StrLen($strError_tmp) <= 0)
		{
			CUpdateClientPartner::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strQuery));

			$stime = CUpdateClientPartner::__GetMicroTime();
			$content = CUpdateClientPartner::__GetHTTPPage("MODULE", $strQuery, $strError_tmp);
			if (strlen($content) <= 0)
			{
				if (StrLen($strError_tmp) <= 0)
					$strError_tmp = "[GNSU02] ".GetMessage("SUPZ_EMPTY_ANSWER").". ";
			}

			CUpdateClientPartner::AddMessage2Log("TIME LoadModuleNoDemand(request) ".Round(CUpdateClientPartner::__GetMicroTime() - $stime, 3)." sec");
		}

		if (StrLen($strError_tmp) <= 0)
		{
			if (!($fp1 = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz", "wb")))
				$strError_tmp = "[GNSU03] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates", GetMessage("SUPP_RV_ER_TEMP_FILE")).". ";
		}

		if (StrLen($strError_tmp) <= 0)
		{
			fwrite($fp1, $content);
			fclose($fp1);
		}

		if (strlen($strError_tmp) <= 0)
		{
			$temporaryUpdatesDir = "";
			if (!CUpdateClientPartner::UnGzipArchive($temporaryUpdatesDir, $strError_tmp, true))
			{
				$strError_tmp .= "[CL02] ".GetMessage("SUPC_ME_PACK").". ";
				CUpdateClientPartner::AddMessage2Log(GetMessage("SUPC_ME_PACK"), "CL02");
			}
		}

		$arStepUpdateInfo = array();
		if (strlen($strError_tmp) <= 0)
			$arStepUpdateInfo = CUpdateClientPartner::GetStepUpdateInfo($temporaryUpdatesDir, $strError_tmp);

		if (StrLen($strError_tmp) <= 0)
		{
			if (isset($arStepUpdateInfo["DATA"]["#"]["ERROR"]))
			{
				for ($i = 0, $cnt = count($arStepUpdateInfo["DATA"]["#"]["ERROR"]); $i < $cnt; $i++)
					$strError_tmp .= "[".$arStepUpdateInfo["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"]."] ".$arStepUpdateInfo["DATA"]["#"]["ERROR"][$i]["#"];
			}
		}

		if (strlen($strError_tmp) <= 0)
		{
			if (!CUpdateClientPartner::UpdateStepModules($temporaryUpdatesDir, $strError_tmp))
			{
				$strError_tmp .= "[CL04] ".GetMessage("SUPC_ME_UPDATE").". ";
				CUpdateClientPartner::AddMessage2Log(GetMessage("SUPC_ME_UPDATE"), "CL04");
			}
		}

		if (strlen($strError_tmp) > 0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CURV");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}

	public static function SearchModulesEx($arOrder, $arFilter, $searchPage, $lang, &$strError)
	{
		$strError_tmp = "";
		$arResult = array();

		CUpdateClientPartner::AddMessage2Log("exec CUpdateClientPartner::SearchModulesEx");

		$arOrderKeys = array_keys($arOrder);

		$strQuery = CUpdateClientPartner::__CollectRequestData(
			$strError_tmp,
			$lang,
			$stableVersionsOnly,
			array(),
			array(
				"search_module_id" => $arFilter["ID"],
				"search_module" => $arFilter["NAME"],
				"search_category" => (is_array($arFilter["CATEGORY"]) ? implode(",", $arFilter["CATEGORY"]) : $arFilter["CATEGORY"]),
				"search_type" => (is_array($arFilter["TYPE"]) ? implode(",", $arFilter["TYPE"]) : $arFilter["TYPE"]),
				"search_order" => $arOrder[$arOrderKeys[0]],
				"search_order_by" => $arOrderKeys[0],
				"search_page" => $searchPage
			)
		);
		if ($strQuery === False || StrLen($strQuery) <= 0 || StrLen($strError_tmp) > 0)
		{
			$strError .= $strError_tmp;
			CUpdateClientPartner::AddMessage2Log("Empty query list", "GUL01");
			return False;
		}

		CUpdateClientPartner::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strQuery));

		$stime = CUpdateClientPartner::__GetMicroTime();
		$content = CUpdateClientPartner::__GetHTTPPage("SEARCH", $strQuery, $strError_tmp);

		CUpdateClientPartner::AddMessage2Log("TIME SearchModulesEx(request) ".Round(CUpdateClientPartner::__GetMicroTime() - $stime, 3)." sec");

		$arResult = Array();
		if (strlen($strError_tmp) <= 0)
			CUpdateClientPartner::__ParseServerData($content, $arResult, $strError_tmp);

		if (strlen($strError_tmp) <= 0)
		{
			if (!isset($arResult["DATA"]) || !is_array($arResult["DATA"]))
				$strError_tmp .= "[UGAUT01] ".GetMessage("SUPP_GAUT_SYSERR").". ";
		}

		if (strlen($strError_tmp) <= 0)
		{
			$arResult = $arResult["DATA"]["#"];
			if (!is_array($arResult["CLIENT"]) && (!isset($arResult["ERROR"]) || !is_array($arResult["ERROR"])))
				$strError_tmp .= "[UGAUT01] ".GetMessage("SUPP_GAUT_SYSERR").". ";
		}

		if (isset($arResult["ERROR"]))
		{
			for ($i = 0, $cnt = count($arResult["ERROR"]); $i < $cnt; $i++)
				$strError_tmp .= "[".$arResult["ERROR"][$i]["@"]["TYPE"]."] ".$arResult["ERROR"][$i]["#"];
		}

		if (strlen($strError_tmp) > 0)
		{
			CUpdateClientPartner::AddMessage2Log($strError_tmp, "GUL02");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return $arResult;
	}

	public static function SearchModules($searchModule, $lang)
	{
		$strError_tmp = "";
		$arResult = array();

		CUpdateClientPartner::AddMessage2Log("exec CUpdateClientPartner::SearchModules");

		$strQuery = CUpdateClientPartner::__CollectRequestData(
			$strError_tmp,
			$lang,
			$stableVersionsOnly,
			array(),
			array("search_module" => $searchModule)
		);
		if ($strQuery === False || StrLen($strQuery) <= 0 || StrLen($strError_tmp) > 0)
		{
			$strError .= $strError_tmp;
			CUpdateClientPartner::AddMessage2Log("Empty query list", "GUL01");
			return False;
		}

		CUpdateClientPartner::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strQuery));

		$stime = CUpdateClientPartner::__GetMicroTime();
		$content = CUpdateClientPartner::__GetHTTPPage("SEARCH", $strQuery, $strError_tmp);

		CUpdateClientPartner::AddMessage2Log("TIME SearchModules(request) ".Round(CUpdateClientPartner::__GetMicroTime() - $stime, 3)." sec");

		$arResult = Array();
		if (strlen($strError_tmp) <= 0)
			CUpdateClientPartner::__ParseServerData($content, $arResult, $strError_tmp);

		if (strlen($strError_tmp) <= 0)
		{
			if (!isset($arResult["DATA"]) || !is_array($arResult["DATA"]))
				$strError_tmp .= "[UGAUT01] ".GetMessage("SUPP_GAUT_SYSERR").". ";
		}

		if (strlen($strError_tmp) <= 0)
		{
			$arResult = $arResult["DATA"]["#"];
			if (!is_array($arResult["CLIENT"]) && (!isset($arResult["ERROR"]) || !is_array($arResult["ERROR"])))
				$strError_tmp .= "[UGAUT01] ".GetMessage("SUPP_GAUT_SYSERR").". ";
		}

		if (strlen($strError_tmp) > 0)
		{
			CUpdateClientPartner::AddMessage2Log($strError_tmp, "GUL02");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return $arResult;
	}
	
	/** Пишет сообщения в лог файл системы обновлений. Чистит лог, если нужно. **/
	public static function AddMessage2Log($sText, $sErrorCode = "")
	{
		$MAX_LOG_SIZE = 1000000;
		$READ_PSIZE = 8000;
		$LOG_FILE = $_SERVER["DOCUMENT_ROOT"].US_SHARED_KERNEL_PATH."/modules/updater_partner.log";
		$LOG_FILE_TMP = $_SERVER["DOCUMENT_ROOT"].US_SHARED_KERNEL_PATH."/modules/updater_partner_tmp1.log";

		if (strlen($sText)>0 || strlen($sErrorCode)>0)
		{
			$old_abort_status = ignore_user_abort(true);

			if (file_exists($LOG_FILE))
			{
				$log_size = @filesize($LOG_FILE);
				$log_size = IntVal($log_size);

				if ($log_size > $MAX_LOG_SIZE)
				{
					if (!($fp = @fopen($LOG_FILE, "rb")))
					{
						ignore_user_abort($old_abort_status);
						return False;
					}

					if (!($fp1 = @fopen($LOG_FILE_TMP, "wb")))
					{
						ignore_user_abort($old_abort_status);
						return False;
					}

					$iSeekLen = IntVal($log_size-$MAX_LOG_SIZE/2.0);
					fseek($fp, $iSeekLen);

					do
					{
						$data = fread($fp, $READ_PSIZE);
						if (strlen($data) == 0)
							break;

						@fwrite($fp1, $data);
					}
					while(true);

					@fclose($fp);
					@fclose($fp1);

					@copy($LOG_FILE_TMP, $LOG_FILE);
					@unlink($LOG_FILE_TMP);
				}
				clearstatcache();
			}

			if ($fp = @fopen($LOG_FILE, "ab+"))
			{
				if (flock($fp, LOCK_EX))
				{
					@fwrite($fp, date("Y-m-d H:i:s")." - ".$sErrorCode." - ".$sText."\n");
					@fflush($fp);
					@flock($fp, LOCK_UN);
					@fclose($fp);
				}
			}
			ignore_user_abort($old_abort_status);
		}
	}

	public static function GetRequestedModules($strAddModule)
	{
		$arRequestedModules = array();

		$arClientModules = CUpdateClientPartner::GetCurrentModules($strError_tmp);
		if (strlen($strError_tmp) <= 0)
		{
			if (count($arClientModules) > 0)
			{
				foreach ($arClientModules as $key => $value)
				{
					if (strpos($key, ".") !== false)
						$arRequestedModules[] = $key;
				}
			}
		}

		if (strlen($strAddModule) > 0)
		{
			$arAddModule = explode(",", $strAddModule);
			foreach ($arAddModule as $value)
			{
				$value = trim($value);
				if (strlen($value) > 0 && strpos($value, ".") !== false)
					$arRequestedModules[] = $value;
			}
		}

		return $arRequestedModules;
	}

	/** Получение лицензионного ключа текущего клиента **/
	public static function GetLicenseKey()
	{
		if (defined("US_LICENSE_KEY"))
			return US_LICENSE_KEY;
		if (defined("LICENSE_KEY"))
			return LICENSE_KEY;
		if (!isset($GLOBALS["CACHE4UPDATESYS_LICENSE_KEY"])	|| $GLOBALS["CACHE4UPDATESYS_LICENSE_KEY"]=="")
		{
			$LICENSE_KEY = "demo";
			if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/license_key.php"))
				include($_SERVER["DOCUMENT_ROOT"]."/bitrix/license_key.php");
			$GLOBALS["CACHE4UPDATESYS_LICENSE_KEY"] = $LICENSE_KEY;
		}
		return $GLOBALS["CACHE4UPDATESYS_LICENSE_KEY"];
	}

	/* Получить обновления следующего шага */
	public static function GetNextStepUpdates(&$strError, $lang = false, $stableVersionsOnly = "Y", $arRequestedModules = array(), $bStrongList = false)
	{
		$strError_tmp = "";

		CUpdateClientPartner::AddMessage2Log("exec CUpdateClientPartner::GetNextStepUpdates");

		$strQuery = CUpdateClientPartner::__CollectRequestData($strError_tmp, $lang, $stableVersionsOnly, $arRequestedModules, array(), $bStrongList);
		if ($strQuery === False || StrLen($strQuery) <= 0 || StrLen($strError_tmp) > 0)
		{
			if (StrLen($strError_tmp) <= 0)
				$strError_tmp = "[GNSU01] ".GetMessage("SUPZ_NO_QSTRING").". ";
		}

		if (StrLen($strError_tmp) <= 0)
		{
			CUpdateClientPartner::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strQuery));

			$stime = CUpdateClientPartner::__GetMicroTime();
			$content = CUpdateClientPartner::__GetHTTPPage("STEPM", $strQuery, $strError_tmp);
			if (strlen($content) <= 0)
			{
				if (StrLen($strError_tmp) <= 0)
					$strError_tmp = "[GNSU02] ".GetMessage("SUPZ_EMPTY_ANSWER").". ";
			}

			CUpdateClientPartner::AddMessage2Log("TIME GetNextStepUpdates(request) ".Round(CUpdateClientPartner::__GetMicroTime() - $stime, 3)." sec");
		}

		if (StrLen($strError_tmp) <= 0)
		{
			if (!($fp1 = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz", "wb")))
				$strError_tmp = "[GNSU03] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates", GetMessage("SUPP_RV_ER_TEMP_FILE")).". ";
		}

		if (StrLen($strError_tmp) <= 0)
		{
			fwrite($fp1, $content);
			fclose($fp1);
		}

		if (strlen($strError_tmp) > 0)
		{
			CUpdateClientPartner::AddMessage2Log($strError_tmp, "GNSU00");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}

	// Распаковывает архив файлов update_archive.gz в папкy $updatesDir
	public static function UnGzipArchive(&$updatesDir, &$strError, $bDelArch = true)
	{
		$strError_tmp = "";

		CUpdateClientPartner::AddMessage2Log("exec CUpdateClientPartner::UnGzipArchive");
		$stime = CUpdateClientPartner::__GetMicroTime();

		$archiveFileName = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz";

		if (!file_exists($archiveFileName) || !is_file($archiveFileName))
			$strError_tmp .= "[UUGZA01] ".str_replace("#FILE#", $archiveFileName, GetMessage("SUPP_UGA_NO_TMP_FILE")).". ";

		if (strlen($strError_tmp) <= 0)
		{
			if (!is_readable($archiveFileName))
				$strError_tmp .= "[UUGZA02] ".str_replace("#FILE#", $archiveFileName, GetMessage("SUPP_UGA_NO_READ_FILE")).". ";
		}

		if (strlen($strError_tmp) <= 0)
		{
			$updatesDir = "update_m".time();
			$updatesDirFull = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$updatesDir;
			CUpdateClientPartner::__CheckDirPath($updatesDirFull."/", true);

			if (!file_exists($updatesDirFull) || !is_dir($updatesDirFull))
				$strError_tmp .= "[UUGZA03] ".str_replace("#FILE#", $updatesDirFull, GetMessage("SUPP_UGA_NO_TMP_CAT")).". ";
			elseif (!is_writable($updatesDirFull))
				$strError_tmp .= "[UUGZA04] ".str_replace("#FILE#", $updatesDirFull, GetMessage("SUPP_UGA_WRT_TMP_CAT")).". ";
		}

		if (strlen($strError_tmp) <= 0)
		{
			$bCompressionUsed = True;

			$fd = fopen($archiveFileName, "rb");
			$flabel = fread($fd, strlen("BITRIX"));
			fclose($fd);

			if ($flabel == "BITRIX")
				$bCompressionUsed = False;
		}

		if (strlen($strError_tmp) <= 0)
		{
			if ($bCompressionUsed)
				$zp = gzopen($archiveFileName, "rb9f");
			else
				$zp = fopen($archiveFileName, "rb");

			if (!$zp)
				$strError_tmp .= "[UUGZA05] ".str_replace("#FILE#", $archiveFileName, GetMessage("SUPP_UGA_CANT_OPEN")).". ";
		}

		if (strlen($strError_tmp) <= 0)
		{
			if ($bCompressionUsed)
				$flabel = gzread($zp, strlen("BITRIX"));
			else
				$flabel = fread($zp, strlen("BITRIX"));

			if ($flabel != "BITRIX")
			{
				$strError_tmp .= "[UUGZA06] ".str_replace("#FILE#", $archiveFileName, GetMessage("SUPP_UGA_BAD_FORMAT")).". ";

				if ($bCompressionUsed)
					gzclose($zp);
				else
					fclose($zp);
			}
		}

		if (strlen($strError_tmp) <= 0)
		{
			$strongUpdateCheck = COption::GetOptionString("main", "strong_update_check", "Y");

			while (true)
			{
				if ($bCompressionUsed)
					$add_info_size = gzread($zp, 5);
				else
					$add_info_size = fread($zp, 5);

				$add_info_size = Trim($add_info_size);
				if (IntVal($add_info_size) > 0 && IntVal($add_info_size)."!"==$add_info_size."!")
				{
					$add_info_size = IntVal($add_info_size);
				}
				else
				{
					if ($add_info_size != "RTIBE")
						$strError_tmp .= "[UUGZA071] ".str_replace("#FILE#", $archiveFileName, GetMessage("SUPP_UGA_BAD_FORMAT")).". ";

					break;
				}

				if ($bCompressionUsed)
					$add_info = gzread($zp, $add_info_size);
				else
					$add_info = fread($zp, $add_info_size);

				$add_info_arr = explode("|", $add_info);
				if (count($add_info_arr) != 3)
				{
					$strError_tmp .= "[UUGZA072] ".str_replace("#FILE#", $archiveFileName, GetMessage("SUPP_UGA_BAD_FORMAT")).". ";
					break;
				}

				$size = $add_info_arr[0];
				$curpath = $add_info_arr[1];
				$crc32 = $add_info_arr[2];

				$contents = "";
				if (IntVal($size) > 0)
				{
					if ($bCompressionUsed)
						$contents = gzread($zp, $size);
					else
						$contents = fread($zp, $size);
				}

				$crc32_new = dechex(crc32($contents));

				if ($crc32_new != $crc32)
				{
					$strError_tmp .= "[UUGZA073] ".str_replace("#FILE#", $curpath, GetMessage("SUPP_UGA_FILE_CRUSH")).". ";
					break;
				}
				else
				{
					CUpdateClientPartner::__CheckDirPath($updatesDirFull.$curpath, true);

					if (!($fp1 = fopen($updatesDirFull.$curpath, "wb")))
					{
						$strError_tmp .= "[UUGZA074] ".str_replace("#FILE#", $updatesDirFull.$curpath, GetMessage("SUPP_UGA_CANT_OPEN_WR")).". ";
						break;
					}

					if (strlen($contents) > 0 && !fwrite($fp1, $contents))
					{
						$strError_tmp .= "[UUGZA075] ".str_replace("#FILE#", $updatesDirFull.$curpath, GetMessage("SUPP_UGA_CANT_WRITE_F")).". ";
						@fclose($fp1);
						break;
					}
					fclose($fp1);

					if ($strongUpdateCheck == "Y")
					{
						$crc32_new = dechex(crc32(file_get_contents($updatesDirFull.$curpath)));
						if ($crc32_new != $crc32)
						{
							$strError_tmp .= "[UUGZA0761] ".str_replace("#FILE#", $curpath, GetMessage("SUPP_UGA_FILE_CRUSH")).". ";
							break;
						}
					}
				}
			}

			if ($bCompressionUsed)
				gzclose($zp);
			else
				fclose($zp);
		}

		if (strlen($strError_tmp) <= 0)
		{
			if ($bDelArch)
				@unlink($archiveFileName);
		}

		CUpdateClientPartner::AddMessage2Log("TIME UnGzipArchive ".Round(CUpdateClientPartner::__GetMicroTime()-$stime, 3)." sec");

		if (strlen($strError_tmp) > 0)
		{
			CUpdateClientPartner::AddMessage2Log($strError_tmp, "CUUGZA");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}

	// Возвращает информацию по загруженным в папку $updatesDir обновлениям модулей
	public static function CheckUpdatability($updatesDir, &$strError)
	{
		$strError_tmp = "";

		$updatesDirFull = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$updatesDir;
		if (!file_exists($updatesDirFull) || !is_dir($updatesDirFull))
			$strError_tmp .= "[UCU01] ".str_replace("#FILE#", $updatesDirFull, GetMessage("SUPP_CU_NO_TMP_CAT")).". ";

		if (strlen($strError_tmp) <= 0)
			if (!is_readable($updatesDirFull))
				$strError_tmp .= "[UCU02] ".str_replace("#FILE#", $updatesDirFull, GetMessage("SUPP_CU_RD_TMP_CAT")).". ";

		if ($handle = @opendir($updatesDirFull))
		{
			while (($file = readdir($handle)) !== false)
			{
				if ($file == "." || $file == "..")
					continue;

				if (is_dir($updatesDirFull."/".$file))
				{
					CUpdateClientPartner::CheckUpdatability($updatesDir."/".$file, $strError_tmp);
				}
				elseif (is_file($updatesDirFull."/".$file))
				{
					$strRealPath = $_SERVER["DOCUMENT_ROOT"].US_SHARED_KERNEL_PATH."/modules/".substr($updatesDir."/".$file, strpos($updatesDir."/".$file, "/"));
					if (file_exists($strRealPath))
					{
						if (!is_writeable($strRealPath))
							$strError_tmp .= "[UCU03] ".str_replace("#FILE#", $strRealPath, GetMessage("SUPP_CU_MAIN_ERR_FILE")).". ";
					}
					else
					{
						$p = CUpdateClientPartner::__bxstrrpos($strRealPath, "/");
						$strRealPath = substr($strRealPath, 0, $p);

						if (strlen($strRealPath) > 1)
							$strRealPath = rtrim($strRealPath, "/");

						$p = CUpdateClientPartner::__bxstrrpos($strRealPath, "/");
						while ($p > 0)
						{
							if (file_exists($strRealPath) && is_dir($strRealPath))
							{
								if (!is_writable($strRealPath))
									$strError_tmp .= "[UCU04] ".str_replace("#FILE#", $strRealPath, GetMessage("SUPP_CU_MAIN_ERR_CAT")).". ";

								break;
							}
							$strRealPath = substr($strRealPath, 0, $p);
							$p = CUpdateClientPartner::__bxstrrpos($strRealPath, "/");
						}
					}
				}
			}
			@closedir($handle);
		}

		if (strlen($strError_tmp) > 0)
		{
			CUpdateClientPartner::AddMessage2Log($strError_tmp, "CUCU");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}

	// Возвращает информацию по загруженным в папку $updatesDir обновлениям модулей
	public static function GetStepUpdateInfo($updatesDir, &$strError)
	{
		$arResult = array();
		$strError_tmp = "";

		CUpdateClientPartner::AddMessage2Log("exec CUpdateClientPartner::GetStepUpdateInfo");

		$updatesDirFull = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$updatesDir;
		if (!file_exists($updatesDirFull) || !is_dir($updatesDirFull))
			$strError_tmp .= "[UGLMU01] ".str_replace("#FILE#", $updatesDirFull, GetMessage("SUPP_CU_NO_TMP_CAT")).". ";

		if (strlen($strError_tmp) <= 0)
			if (!is_readable($updatesDirFull))
				$strError_tmp .= "[UGLMU02] ".str_replace("#FILE#", $updatesDirFull, GetMessage("SUPP_CU_RD_TMP_CAT")).". ";

		if (strlen($strError_tmp) <= 0)
			if (!file_exists($updatesDirFull."/update_info.xml") || !is_file($updatesDirFull."/update_info.xml"))
				$strError_tmp .= "[UGLMU03] ".str_replace("#FILE#", $updatesDirFull."/update_info.xml", GetMessage("SUPP_RV_ER_DESCR_FILE")).". ";

		if (strlen($strError_tmp) <= 0)
			if (!is_readable($updatesDirFull."/update_info.xml"))
				$strError_tmp .= "[UGLMU04] ".str_replace("#FILE#", $updatesDirFull."/update_info.xml", GetMessage("SUPP_RV_READ_DESCR_FILE")).". ";

		if (strlen($strError_tmp) <= 0)
			$content = file_get_contents($updatesDirFull."/update_info.xml");

		if (strlen($strError_tmp) <= 0)
		{
			$arResult = Array();
			CUpdateClientPartner::__ParseServerData($content, $arResult, $strError_tmp);
		}

		if (strlen($strError_tmp) <= 0)
		{
			if (!isset($arResult["DATA"]) || !is_array($arResult["DATA"]))
				$strError_tmp .= "[UGSMU01] ".GetMessage("SUPP_GAUT_SYSERR").". ";
		}

		if (strlen($strError_tmp) > 0)
		{
			CUpdateClientPartner::AddMessage2Log($strError_tmp, "CUGLMU");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return $arResult;
	}

	public static function __CollectRequestData(&$strError, $lang = false, $stableVersionsOnly = "Y", $arRequestedModules = array(), $arAdditionalData = array(), $bStrongList = false)
	{
		$strResult = "";
		$strError_tmp = "";

		if ($lang === false)
			$lang = LANGUAGE_ID;

		$stableVersionsOnly = (($stableVersionsOnly == "N") ? "N" : "Y");

		CUpdateClientPartner::AddMessage2Log("exec CUpdateClientPartner::__CollectRequestData");

		CUpdateClientPartner::__CheckDirPath($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/", true);

		$arClientModules = CUpdateClientPartner::GetCurrentModules($strError_tmp);

		if (strlen($strError_tmp) <= 0)
		{
			$dbv = $GLOBALS["DB"]->GetVersion();

			$strResult = "utf=".urlencode(defined('BX_UTF') ? "Y" : "N").
				"&lang=".urlencode($lang).
				"&stable=".urlencode($stableVersionsOnly).
				"&CANGZIP=".urlencode((CUpdateClientPartner::__IsGzipInstalled()) ? "Y" : "N").
				"&SUPD_DBS=".urlencode($GLOBALS["DB"]->type).
				"&XE=".urlencode(($GLOBALS["DB"]->XE) ? "Y" : "N").
				"&CLIENT_SITE=".urlencode($_SERVER["SERVER_NAME"]).
				"&LICENSE_KEY=".urlencode(md5(CUpdateClientPartner::GetLicenseKey())).
				"&SUPD_STS=".urlencode(CUpdateClientPartner::__GetFooPath()).
				"&SUPD_URS=".urlencode(CUpdateClientPartner::__GetFooPath1(0)).
				"&SUPD_URSA=".urlencode(CUpdateClientPartner::__GetFooPath1(1)).
				"&TYPENC=".((defined("DEMO") && DEMO=="Y") ? "D" : ((defined("ENCODE") && ENCODE=="Y") ? "E" : "F" )).
				"&CLIENT_PHPVER=".urlencode(phpversion()).
				"&NGINX=".urlencode(COption::GetOptionString("main", "update_use_nginx", "Y")).
				"&dbv=".urlencode($dbv != false ? $dbv : "");

			$strResultTmp = "";
			if (count($arClientModules) > 0)
			{
				foreach ($arClientModules as $key => $value)
				{
					if (StrLen($strResultTmp) > 0)
						$strResultTmp .= ";";
					$strResultTmp .= $key.",".$value["VERSION"].",".$value["IS_DEMO"];
				}
			}
			if (StrLen($strResultTmp) > 0)
				$strResult .= "&instm=".urlencode($strResultTmp);

//			foreach ($arClientModules as $key => $value)
//				$strResult .= "&m_".$key."=".urlencode($value);

			$strResultTmp = "";
			if (count($arRequestedModules) > 0)
			{
				for ($i = 0, $cnt = count($arRequestedModules); $i < $cnt; $i++)
				{
					if (StrLen($strResultTmp) > 0)
						$strResultTmp .= ",";
					$strResultTmp .= $arRequestedModules[$i];
				}
			}
			if (StrLen($strResultTmp) > 0)
				$strResult .= "&reqm=".urlencode($strResultTmp);
			
			if ($bStrongList)
				$strResult .= "&lim=Y";

			$strResultTmp = "";
			if (count($arAdditionalData) > 0)
			{
				foreach ($arAdditionalData as $key => $value)
				{
					if (StrLen($strResultTmp) > 0)
						$strResultTmp .= "&";
					$strResultTmp .= $key."=".urlencode($value);
				}
			}
			if (StrLen($strResultTmp) > 0)
				$strResult .= "&".$strResultTmp;

			if (CModule::IncludeModule("cluster") && class_exists("CCluster"))
				$strResult .= "&SUPD_SRS=".urlencode(CCluster::getServersCount());
			else
				$strResult .= "&SUPD_SRS=".urlencode("RU");

			if (method_exists("CHTMLPagesCache", "IsOn") && method_exists("CHTMLPagesCache", "IsCompositeEnabled") && CHTMLPagesCache::IsOn() && CHTMLPagesCache::IsCompositeEnabled())
				$strResult .= "&SUPD_CMP=".urlencode("Y");
			else
				$strResult .= "&SUPD_CMP=".urlencode("N");

			return $strResult;
		}

		CUpdateClientPartner::AddMessage2Log($strError_tmp, "NCRD01");
		$strError .= $strError_tmp;
		return False;
	}

	/** Собирает клиентские модули с версиями **/
	public static function GetCurrentModules(&$strError)
	{
		$arClientModules = array();

		if (file_exists($_SERVER["DOCUMENT_ROOT"].US_SHARED_KERNEL_PATH."/modules/main/classes/general/version.php")
			&& is_file($_SERVER["DOCUMENT_ROOT"].US_SHARED_KERNEL_PATH."/modules/main/classes/general/version.php"))
		{
			$p = file_get_contents($_SERVER["DOCUMENT_ROOT"].US_SHARED_KERNEL_PATH."/modules/main/classes/general/version.php");

			preg_match("/define\s*\(\s*\"SM_VERSION\"\s*,\s*\"(\d+\.\d+\.\d+)\"\s*\)\s*/im", $p, $arVers);
			$arClientModules["main"] = array("VERSION" => $arVers[1], "IS_DEMO" => ((defined("DEMO") && DEMO == "Y") ? "Y" : "N"));
		}

		if (!array_key_exists("main", $arClientModules) || strlen($arClientModules["main"]["VERSION"]) <= 0)
		{
			CUpdateClientPartner::AddMessage2Log(GetMessage("SUPP_GM_ERR_DMAIN"), "Ux09");
			$strError .= "[Ux09] ".GetMessage("SUPP_GM_ERR_DMAIN").". ";
			return array();
		}

		if ($handle = @opendir($_SERVER["DOCUMENT_ROOT"].US_SHARED_KERNEL_PATH."/modules"))
		{
			while (false !== ($dir = readdir($handle)))
			{
				if (is_dir($_SERVER["DOCUMENT_ROOT"].US_SHARED_KERNEL_PATH."/modules/".$dir)
					&& $dir != "." && $dir != ".." && $dir != "main")
				{
					$module_dir = $_SERVER["DOCUMENT_ROOT"].US_SHARED_KERNEL_PATH."/modules/".$dir;
					if (file_exists($module_dir."/install/index.php"))
					{
						$arInfo = CUpdateClientPartner::__GetModuleInfo($module_dir);
						if (!isset($arInfo["VERSION"]) || strlen($arInfo["VERSION"]) <= 0)
						{
							CUpdateClientPartner::AddMessage2Log(str_replace("#MODULE#", $dir, GetMessage("SUPP_GM_ERR_DMOD")), "Ux11");
							$strError .= "[Ux11] ".str_replace("#MODULE#", $dir, GetMessage("SUPP_GM_ERR_DMOD")).". ";

							return array();
						}
						else
						{
							if($arInfo["ACTIVE"] == "Y")
								$arClientModules[$dir] = array("VERSION" => $arInfo["VERSION"], "IS_DEMO" => $arInfo["IS_DEMO"]);
						}
					}
					else
					{
						CUpdateClientPartner::AddMessage2Log(str_replace("#MODULE#", $dir, GetMessage("SUPP_GM_ERR_DMOD")), "Ux12");
					}
				}
			}
			closedir($handle);
		}
		else
		{
			CUpdateClientPartner::AddMessage2Log(GetMessage("SUPP_GM_NO_KERNEL"), "Ux15");
			$strError .= "[Ux15] ".GetMessage("SUPP_GM_NO_KERNEL").". ";
			
			return array();
		}

		return $arClientModules;
	}

	/* Получить список доступных обновлений */
	public static function GetUpdatesList(&$strError, $lang = false, $stableVersionsOnly = "Y", $arRequestedModules = array(), $aditData = Array())
	{
		$strError_tmp = "";
		$arResult = array();

		CUpdateClientPartner::AddMessage2Log("exec CUpdateClientPartner::GetUpdatesList");

		$strQuery = CUpdateClientPartner::__CollectRequestData($strError_tmp, $lang, $stableVersionsOnly, $arRequestedModules, $aditData);
		if ($strQuery === False || StrLen($strQuery) <= 0 || StrLen($strError_tmp) > 0)
		{
			$strError .= $strError_tmp;
			CUpdateClientPartner::AddMessage2Log("Empty query list", "GUL01");
			return False;
		}

		CUpdateClientPartner::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strQuery));

		$stime = CUpdateClientPartner::__GetMicroTime();
		$content = CUpdateClientPartner::__GetHTTPPage("LIST", $strQuery, $strError_tmp);

		CUpdateClientPartner::AddMessage2Log("TIME GetUpdatesList(request) ".Round(CUpdateClientPartner::__GetMicroTime() - $stime, 3)." sec");

		$arResult = Array();
		if (strlen($strError_tmp) <= 0)
			CUpdateClientPartner::__ParseServerData($content, $arResult, $strError_tmp);

		if (strlen($strError_tmp) <= 0)
		{
			if (!isset($arResult["DATA"]) || !is_array($arResult["DATA"]))
				$strError_tmp .= "[UGAUT01] ".GetMessage("SUPP_GAUT_SYSERR").". ";
		}

		if (strlen($strError_tmp) <= 0)
		{
			$arResult = $arResult["DATA"]["#"];
			if (!is_array($arResult["CLIENT"]) && (!isset($arResult["ERROR"]) || !is_array($arResult["ERROR"])))
				$strError_tmp .= "[UGAUT01] ".GetMessage("SUPP_GAUT_SYSERR").". ";
		}

		if (strlen($strError_tmp) > 0)
		{
			CUpdateClientPartner::AddMessage2Log($strError_tmp, "GUL02");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return $arResult;
	}

	public static function ClearUpdateFolder($updatesDirFull)
	{
		CUpdateClientPartner::__DeleteDirFilesEx($updatesDirFull);
		bx_accelerator_reset();
	}

	public static function LoadModulesUpdates(&$errorMessage, &$arUpdateDescription, $lang = false, $stableVersionsOnly = "Y", $arRequestedModules = array(), $bStrongList = false)
	{
		$arUpdateDescription = array();
		$updateServerQueryString = "";

		$filename = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz";
		$timeout = COption::GetOptionString("main", "update_load_timeout", "30");
		if ($timeout < 5)
			$timeout = 5;

		CUpdateClientPartner::AddMessage2Log("exec CUpdateClientPartner::LoadModulesUpdates");

		if (file_exists($filename.".log"))
		{
			$content = file_get_contents($filename.".log");
			CUpdateClientPartner::__ParseServerData($content, $arUpdateDescription, $strError_tmp);
		}

		if (count($arUpdateDescription) <= 0 || strlen($errorMessage) > 0)
		{
			$arUpdateDescription = array();
			if (file_exists($filename.".tmp"))
				@unlink($filename.".tmp");
			if (file_exists($filename.".log"))
				@unlink($filename.".log");

			if (strlen($errorMessage) > 0)
			{
				CUpdateClientPartner::AddMessage2Log($errorMessage, "LMU001");

				return "E";
			}
		}

		if (count($arUpdateDescription) <= 0)
		{
			$updateServerQueryString = CUpdateClientPartner::__CollectRequestData(
				$errorMessage, $lang, $stableVersionsOnly, $arRequestedModules, array(), $bStrongList
			);
			if (empty($updateServerQueryString) || strlen($errorMessage) > 0)
			{
				if (StrLen($errorMessage) <= 0)
					$errorMessage = "[GNSU01] ".GetMessage("SUPZ_NO_QSTRING").". ";
				CUpdateClientPartner::AddMessage2Log($errorMessage, "LMU002");

				return "E";
			}

			CUpdateClientPartner::AddMessage2Log(
				preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $updateServerQueryString)
			);

			$stime = CUpdateClientPartner::__GetMicroTime();

			$content = CUpdateClientPartner::__GetHTTPPage("STEPM", $updateServerQueryString, $errorMessage);
			if (strlen($content) <= 0)
			{
				if (strlen($errorMessage) <= 0)
					$errorMessage = "[GNSU02] ".GetMessage("SUPZ_EMPTY_ANSWER").". ";
				CUpdateClientPartner::AddMessage2Log($errorMessage, "LMU003");

				return "E";
			}

			CUpdateClientPartner::AddMessage2Log(
				"TIME LoadModulesUpdates(request) ".Round(CUpdateClientPartner::__GetMicroTime() - $stime, 3)." sec"
			);

			CUpdateClientPartner::__ParseServerData($content, $arUpdateDescription, $errorMessage);
			if (strlen($errorMessage) > 0)
			{
				CUpdateClientPartner::AddMessage2Log($errorMessage, "LMU004");

				return "E";
			}

			if (isset($arUpdateDescription["DATA"]["#"]["ERROR"]))
			{
				for ($i = 0, $cnt = count($arUpdateDescription["DATA"]["#"]["ERROR"]); $i < $cnt; $i++)
					$errorMessage .= "[".$arUpdateDescription["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"]."] ".$arUpdateDescription["DATA"]["#"]["ERROR"][$i]["#"];
			}

			if (strlen($errorMessage) > 0)
			{
				CUpdateClientPartner::AddMessage2Log($errorMessage, "LMU005");

				return "E";
			}

			if (isset($arUpdateDescription["DATA"]["#"]["NOUPDATES"]))
			{
				CUpdateClientPartner::AddMessage2Log("Finish - NOUPDATES", "STEP");

				return "F";
			}

			if (!($fp1 = fopen($filename.".log", "wb")))
			{
				$errorMessage = "[GNSU03] ".str_replace(
						"#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates", GetMessage("SUPP_RV_ER_TEMP_FILE")
					).". ";
				CUpdateClientPartner::AddMessage2Log($errorMessage, "LMU006");

				return "E";
			}

			fwrite($fp1, $content);
			fclose($fp1);

			CUpdateClientPartner::AddMessage2Log("STEP", "S");

			return "S";
		}

		if (isset($arUpdateDescription["DATA"]["#"]["FILE"][0]["@"]["NAME"]))
		{
			if ($updateServerQueryString == "")
			{
				$updateServerQueryString = CUpdateClientPartner::__CollectRequestData(
					$errorMessage, $lang, $stableVersionsOnly, $arRequestedModules, array(), $bStrongList
				);
				if (empty($updateServerQueryString) || strlen($errorMessage) > 0)
				{
					if (StrLen($errorMessage) <= 0)
						$errorMessage = "[GNSU01] ".GetMessage("SUPZ_NO_QSTRING").". ";
					CUpdateClientPartner::AddMessage2Log($errorMessage, "LMU002");

					return "E";
				}
			}

			CUpdateClientPartner::AddMessage2Log("loadFileBx");
			$r = static::loadFileBx(
				$arUpdateDescription["DATA"]["#"]["FILE"][0]["@"]["NAME"],
				$arUpdateDescription["DATA"]["#"]["FILE"][0]["@"]["SIZE"],
				$filename,
				$timeout,
				$updateServerQueryString,
				$errorMessage
			);
		}
		elseif ($arUpdateDescription["DATA"]["#"]["FILE"][0]["@"]["URL"])
		{
			CUpdateClientPartner::AddMessage2Log("loadFile");
			$r = static::loadFile(
				$arUpdateDescription["DATA"]["#"]["FILE"][0]["@"]["URL"],
				$arUpdateDescription["DATA"]["#"]["FILE"][0]["@"]["SIZE"],
				$filename,
				$timeout,
				$errorMessage
			);
		}
		else
		{
			$r = "E";
			$errorMessage .= GetMessage("SUPP_PSD_BAD_RESPONSE");
		}

		if ($r == "E")
		{
			CUpdateClientPartner::AddMessage2Log($errorMessage, "GNSU001");
			$errorMessage .= $errorMessage;
		}
		elseif ($r == "U")
		{
			@unlink($filename.".log");
		}

		CUpdateClientPartner::AddMessage2Log("RETURN", $r);

		return $r;
	}

	private static function getAddr($serverIp = null, $serverPort = null)
	{
		if (!$serverIp)
			$serverIp = COption::GetOptionString("main", "update_site", DEFAULT_UPDATE_SERVER);
		if (!$serverPort)
			$serverPort = 80;

		$proxyAddr = COption::GetOptionString("main", "update_site_proxy_addr", "");
		$proxyPort = COption::GetOptionString("main", "update_site_proxy_port", "");
		$proxyUserName = COption::GetOptionString("main", "update_site_proxy_user", "");
		$proxyPassword = COption::GetOptionString("main", "update_site_proxy_pass", "");

		$useProxy = (strlen($proxyAddr) > 0 && strlen($proxyPort) > 0);

		$result = array(
			"USE_PROXY" => $useProxy,
			"IP" => $serverIp,
			"PORT" => $serverPort,
			"SOCKET_IP" => $serverIp,
			"SOCKET_PORT" => $serverPort,
		);

		if ($useProxy)
		{
			$proxyPort = intval($proxyPort);
			if ($proxyPort <= 0)
				$proxyPort = 80;

			$result["SOCKET_IP"] = $proxyAddr;
			$result["SOCKET_PORT"] = $proxyPort;
			$result["PROXY_USERNAME"] = $proxyUserName;
			$result["PROXY_PASSWORD"] = $proxyPassword;
		}

		return $result;
	}

	private static function getSocketError($errstr, $errno, $addrParams)
	{
		if (class_exists('CUtil') && method_exists('CUtil', 'ConvertToLangCharset'))
			$errstr = CUtil::ConvertToLangCharset($errstr);
		$error = GetMessage("SUPP_GHTTP_ER").": [".$errno."] ".$errstr.". ";
		if (intval($errno) <= 0)
			$error .= GetMessage("SUPP_GHTTP_ER_DEF")." ";

		CUpdateClientPartner::AddMessage2Log(
			"Error connecting 2 ".$addrParams["SOCKET_IP"].": [".$errno."] ".$errstr."", "ERRCONN1"
		);

		return $error;
	}

	private static function loadFileBx($requestedUrl, $realSize, $outputFilename, $timeout, $requestQueryString, &$errorMessage)
	{
		$timeout = intval($timeout);
		$startTime = 0;
		if ($timeout > 0)
			$startTime = getmicrotime();

		$addrParams = static::getAddr();

		$socketHandler = fsockopen($addrParams["SOCKET_IP"], $addrParams["SOCKET_PORT"], $errorNum, $errorMsg, 30);
		if (!$socketHandler)
		{
			$errorMessage .= static::getSocketError($errorMsg, $errorNum, $addrParams);
			return "E";
		}

		$request = "";
		if ($addrParams["USE_PROXY"])
		{
			$request .= "POST http://".$addrParams["IP"]."/bitrix/updates/smp_updater_modules.php HTTP/1.0\r\n";
			if ($addrParams["PROXY_USERNAME"])
				$request .= "Proxy-Authorization: Basic ".base64_encode(
						$addrParams["PROXY_USERNAME"].":".$addrParams["PROXY_PASSWORD"]
					)."\r\n";
		}
		else
		{
			$request .= "POST /bitrix/updates/smp_updater_modules.php HTTP/1.0\r\n";
		}

		$requestQueryString .= "&UFILE=".$requestedUrl;
		$startSize = (file_exists($outputFilename.".tmp") ? filesize($outputFilename.".tmp") : 0);
		$requestQueryString .= "&USTART=".$startSize."&verfix=2";

		$request .= "User-Agent: BitrixSMUpdater\r\n";
		$request .= "Accept: */*\r\n";
		$request .= "Host: ".$addrParams["IP"]."\r\n";
		$request .= "Accept-Language: en\r\n";
		$request .= "Content-type: application/x-www-form-urlencoded\r\n";
		$request .= "Content-length: ".strlen($requestQueryString)."\r\n\r\n";
		$request .= $requestQueryString;
		$request .= "\r\n";

		fputs($socketHandler, $request);

		$replyHeader = "";
		while (($result = fgets($socketHandler, 4096)) && $result != "\r\n")
			$replyHeader .= $result;

		$replyHeaderArray = preg_split("#\r\n#", $replyHeader);

		$contentLength = 0;
		for ($i = 0, $cnt = count($replyHeaderArray); $i < $cnt; $i++)
		{
			if (strpos($replyHeaderArray[$i], "Content-Length") !== false)
			{
				$pos = strpos($replyHeaderArray[$i], ":");
				$contentLength = intval(trim(substr($replyHeaderArray[$i], $pos + 1, strlen($replyHeaderArray[$i]) - $pos + 1)));
			}
		}

		if (($contentLength + $startSize) != $realSize)
		{
			$errorMessage .= "[ELVL001] ".GetMessage("ELVL001_SIZE_ERROR").". ";
			return "E";
		}

		@unlink($outputFilename.".tmp1");

		if (file_exists($outputFilename.".tmp"))
		{
			if (@rename($outputFilename.".tmp", $outputFilename.".tmp1"))
			{
				$fileHandler = fopen($outputFilename.".tmp", "wb");
				if ($fileHandler)
				{
					$fh1 = fopen($outputFilename.".tmp1", "rb");
					do
					{
						$data = fread($fh1, 8192);
						if (strlen($data) == 0)
							break;
						fwrite($fileHandler, $data);
					}
					while (true);

					fclose($fh1);
					@unlink($outputFilename.".tmp1");
				}
				else
				{
					$errorMessage .= "[JUHYT002] ".GetMessage("JUHYT002_ERROR_FILE").". ";
					return "E";
				}
			}
			else
			{
				$errorMessage .= "[JUHYT003] ".GetMessage("JUHYT003_ERROR_FILE").". ";
				return "E";
			}
		}
		else
		{
			$fileHandler = fopen($outputFilename.".tmp", "wb");
			if (!$fileHandler)
			{
				$errorMessage .= "[JUHYT004] ".GetMessage("JUHYT004_ERROR_FILE").". ";
				return "E";
			}
		}

		$isFinished = true;
		while (true)
		{
			if ($timeout > 0 && (CUpdateClientPartner::__GetMicroTime() - $startTime) > $timeout)
			{
				$isFinished = false;
				break;
			}

			$result = fread($socketHandler, 40960);
			if ($result == "")
				break;

			fwrite($fileHandler, $result);
		}

		fclose($fileHandler);
		fclose($socketHandler);

		CUpdateClientPartner::AddMessage2Log("Time - ".(CUpdateClientPartner::__GetMicroTime() - $startTime)." sec", "DOWNLOAD");

		$sizeTmp = (file_exists($outputFilename.".tmp") ? filesize($outputFilename.".tmp") : 0);
		if ($sizeTmp == $realSize)
		{
			$isFinished = true;
		}

		if ($isFinished)
		{
			@unlink($outputFilename);
			if (!@rename($outputFilename.".tmp", $outputFilename))
			{
				$errorMessage .= "[JUHYT005] ".GetMessage("JUHYT005_ERROR_FILE").". ";
				return "E";
			}
			@unlink($outputFilename.".tmp");
		}
		else
		{
			return "S";
		}

		return "U";
	}

	private function loadFile($requestedUrl, $realSize, $outputFilename, $timeout, &$errorMessage)
	{
		$timeout = intval($timeout);
		$startTime = 0;
		if ($timeout > 0)
			$startTime = getmicrotime();

		$startSize = file_exists($outputFilename.".tmp") ? filesize($outputFilename.".tmp") : 0;

		//$realRequestedUrlParsed = parse_url($requestedUrl);

		$addrParams = static::getAddr(/*$realRequestedUrlParsed["host"], $realRequestedUrlParsed["port"]*/);

		$socketHandler = fsockopen($addrParams["SOCKET_IP"], $addrParams["SOCKET_PORT"], $errorNum, $errorMsg, 30);
		if (!$socketHandler)
		{
			$errorMessage .= static::getSocketError($errorMsg, $errorNum, $addrParams);
			return "E";
		}

		if (!$requestedUrl)
			$requestedUrl = "/";

		$request = "";
		if (!$addrParams["USE_PROXY"])
		{
			$request .= "GET ".$requestedUrl." HTTP/1.0\r\n";
			$request .= "Host: ".$addrParams["IP"]."\r\n";
		}
		else
		{
			$request .= "GET http://".$addrParams["IP"].$requestedUrl." HTTP/1.0\r\n";
			$request .= "Host: ".$addrParams["IP"]."\r\n";
			if ($addrParams["PROXY_USERNAME"])
				$request .= "Proxy-Authorization: Basic ".base64_encode($addrParams["PROXY_USERNAME"].":".$addrParams["PROXY_PASSWORD"])."\r\n";
		}

		$request .= "User-Agent: BitrixSMUpdater\r\n";
		if ($startSize > 0)
			$request .= "Range: bytes=".$startSize."-\r\n";

		$request .= "\r\n";

		fwrite($socketHandler, $request);

		$replyHeader = "";
		while (($result = fgets($socketHandler, 4096)) && $result!="\r\n")
			$replyHeader .= $result;

		$replyHeaderArray = preg_split("#\r\n#", $replyHeader);

		$replycode = 0;
		$replymsg = "";
		if (preg_match("#([A-Z]{4})/([0-9.]{3}) ([0-9]{3})#", $replyHeaderArray[0], $regs))
		{
			$replycode = intval($regs[3]);
			$replymsg = substr($replyHeaderArray[0], strpos($replyHeaderArray[0], $replycode) + strlen($replycode) + 1, strlen($replyHeaderArray[0]) - strpos($replyHeaderArray[0], $replycode) + 1);
		}

		if ($replycode != 200 && $replycode != 204 && $replycode != 302 && $replycode != 206)
		{
			$errorMessage .= GetMessage("SUPP_PSD_BAD_RESPONSE")." (".$replycode." - ".$replymsg.")";
			return "E";
		}

		$replyContentRange = "";
		$replyContentLength = 0;
		for ($i = 1; $i < count($replyHeaderArray); $i++)
		{
			if (strpos($replyHeaderArray[$i], "Content-Range") !== false)
				$replyContentRange = trim(substr($replyHeaderArray[$i], strpos($replyHeaderArray[$i], ":") + 1, strlen($replyHeaderArray[$i]) - strpos($replyHeaderArray[$i], ":") + 1));
			elseif (strpos($replyHeaderArray[$i], "Content-Length") !== false)
				$replyContentLength = doubleval(Trim(substr($replyHeaderArray[$i], strpos($replyHeaderArray[$i], ":") + 1, strlen($replyHeaderArray[$i]) - strpos($replyHeaderArray[$i], ":") + 1)));
		}

		$shouldReloadFile = true;
		if (strlen($replyContentRange) > 0)
		{
			if (preg_match("# *bytes +([0-9]*) *- *([0-9]*) */ *([0-9]*)#i", $replyContentRange, $regs))
			{
				$startBytesTmp = doubleval($regs[1]);
				$endBytesTmp = doubleval($regs[2]);
				$sizeBytesTmp = doubleval($regs[3]);
				if (($startBytesTmp == $startSize) && ($endBytesTmp == ($realSize - 1)) && ($sizeBytesTmp == $realSize))
				{
					$shouldReloadFile = false;
				}
			}
		}

		if ($shouldReloadFile)
		{
			@unlink($outputFilename.".tmp");
			$startSize = 0;
		}

		if (($replyContentLength + $startSize) != $realSize)
		{
			$errorMessage .= "[ELVL010] ".GetMessage("ELVL001_SIZE_ERROR").". ";
			return "E";
		}

		$fileHandler = fopen($outputFilename.".tmp", "ab");
		if (!$fileHandler)
		{
			$errorMessage .= "[JUHYT010] ".GetMessage("JUHYT002_ERROR_FILE").". ";
			return "E";
		}

		$isFinished = true;
		$downloadSize = (double) $startSize;
		while (true)
		{
			if ($timeout > 0 && (getmicrotime() - $startTime) > $timeout)
			{
				$isFinished = false;
				break;
			}

			$result = fread($socketHandler, 256 * 1024);
			$downloadSize += strlen($result);
			if ($result == "")
				break;

			fwrite($fileHandler, $result);
		}

		fclose($fileHandler);
		fclose($socketHandler);

		$sizeTmp = (file_exists($outputFilename.".tmp") ? filesize($outputFilename.".tmp") : 0);
		if ($sizeTmp == $realSize)
		{
			$isFinished = true;
		}

		if ($isFinished)
		{
			@unlink($outputFilename);
			if (!@rename($outputFilename.".tmp", $outputFilename))
			{
				$errorMessage .= "[JUHYT010] ".GetMessage("JUHYT005_ERROR_FILE").". ";
				return "E";
			}
			@unlink($outputFilename.".tmp");
		}
		else
		{
			return "S";
		}

		return "U";
	}

	public static function UpdateStepModules($updatesDir, &$strError, $bSaveUpdaters = False)
	{
		global $DB;
		$strError_tmp = "";

		if (!defined("US_SAVE_UPDATERS_DIR") || StrLen(US_SAVE_UPDATERS_DIR) <= 0)
			$bSaveUpdaters = False;

		$stime = CUpdateClientPartner::__GetMicroTime();

		$updatesDirFull = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$updatesDir;

		if (!file_exists($updatesDirFull) || !is_dir($updatesDirFull))
			$strError_tmp .= "[UUK01] ".str_replace("#FILE#", $updatesDirFull, GetMessage("SUPP_CU_NO_TMP_CAT")).". ";

		if (strlen($strError_tmp) <= 0)
			if (!is_readable($updatesDirFull))
				$strError_tmp .= "[UUK03] ".str_replace("#FILE#", $updatesDirFull, GetMessage("SUPP_CU_RD_TMP_CAT")).". ";

		$arModules = array();
		if (StrLen($strError_tmp) <= 0)
		{
			$handle = @opendir($updatesDirFull);
			if ($handle)
			{
				while (false !== ($dir = readdir($handle)))
				{
					if ($dir == "." || $dir == "..")
						continue;
					if (is_dir($updatesDirFull."/".$dir))
						$arModules[] = $dir;
				}
				closedir($handle);
			}
		}

		if (!is_array($arModules) || count($arModules) <= 0)
			$strError_tmp .= "[UUK02] ".GetMessage("SUPP_UK_NO_MODS").". ";

		if (strlen($strError_tmp) <= 0)
		{
			for ($i = 0, $cnt = count($arModules); $i < $cnt; $i++)
			{
				$strError_tmp1 = "";

				$updateDirFrom = $updatesDirFull."/".$arModules[$i];
				$updateDirTo = $_SERVER["DOCUMENT_ROOT"].US_SHARED_KERNEL_PATH."/modules/".$arModules[$i];

				CUpdateClientPartner::__CheckDirPath($updateDirTo."/", true);

				if (!file_exists($updateDirTo) || !is_dir($updateDirTo))
					$strError_tmp1 .= "[UUK04] ".str_replace("#MODULE_DIR#", $updateDirTo, GetMessage("SUPP_UK_NO_MODIR")).". ";

				if (strlen($strError_tmp1) <= 0)
					if (!is_writable($updateDirTo))
						$strError_tmp1 .= "[UUK05] ".str_replace("#MODULE_DIR#", $updateDirTo, GetMessage("SUPP_UK_WR_MODIR")).". ";

				if (strlen($strError_tmp1) <= 0)
					if (!file_exists($updateDirFrom) || !is_dir($updateDirFrom))
						$strError_tmp1 .= "[UUK06] ".str_replace("#DIR#", $updateDirFrom, GetMessage("SUPP_UK_NO_FDIR")).". ";

				if (strlen($strError_tmp1) <= 0)
					if (!is_readable($updateDirFrom))
						$strError_tmp1 .= "[UUK07] ".str_replace("#DIR#", $updateDirFrom, GetMessage("SUPP_UK_READ_FDIR")).". ";

				if (strlen($strError_tmp1) <= 0)
				{
					$handle = @opendir($updateDirFrom);
					$arUpdaters = array();
					if ($handle)
					{
						while (false !== ($dir = readdir($handle)))
						{
							if (substr($dir, 0, 7) == "updater")
							{
								$bPostUpdater = "N";
								if (is_file($updateDirFrom."/".$dir))
								{
									$num = substr($dir, 7, strlen($dir) - 11);
									if (substr($dir, strlen($dir) - 9) == "_post.php")
									{
										$bPostUpdater = "Y";
										$num = substr($dir, 7, strlen($dir) - 16);
									}
									$arUpdaters[] = array("/".$dir, Trim($num), $bPostUpdater);
								}
								elseif (file_exists($updateDirFrom."/".$dir."/index.php"))
								{
									$num = substr($dir, 7);
									if (substr($dir, strlen($dir) - 5) == "_post")
									{
										$bPostUpdater = "Y";
										$num = substr($dir, 7, strlen($dir) - 12);
									}
									$arUpdaters[] = array("/".$dir."/index.php", Trim($num), $bPostUpdater);
								}

								if ($bSaveUpdaters)
									CUpdateClientPartner::__CopyDirFiles($updateDirFrom."/".$dir, $_SERVER["DOCUMENT_ROOT"].US_SAVE_UPDATERS_DIR."/".$arModules[$i]."/".$dir, $strError_tmp1, False);
							}
						}
						closedir($handle);
					}

					$n = count($arUpdaters);
					for ($i1 = 0; $i1 < $n - 1; $i1++)
					{
						for ($j1 = $i1 + 1; $j1 < $n; $j1++)
						{
							if (CUpdateClientPartner::__CompareVersions($arUpdaters[$i1][1], $arUpdaters[$j1][1]) > 0)
							{
								$tmp1 = $arUpdaters[$i1];
								$arUpdaters[$i1] = $arUpdaters[$j1];
								$arUpdaters[$j1] = $tmp1;
							}
						}
					}
				}

				if (strlen($strError_tmp1) <= 0)
				{
					if (strtolower($DB->type) == "mysql" && defined("MYSQL_TABLE_TYPE") && strlen(MYSQL_TABLE_TYPE) > 0)
					{
						$DB->Query("SET storage_engine = '".MYSQL_TABLE_TYPE."'", True);
					}
				}

				if (strlen($strError_tmp1) <= 0)
				{
					for ($i1 = 0, $n = count($arUpdaters); $i1 < $n; $i1++)
					{
						if ($arUpdaters[$i1][2] == "N")
						{
							$strError_tmp2 = "";
							CUpdateClientPartner::__RunUpdaterScript($updateDirFrom.$arUpdaters[$i1][0], $strError_tmp2, "/bitrix/updates/".$updatesDir."/".$arModules[$i], $arModules[$i]);
							if (strlen($strError_tmp2) > 0)
							{
								$strError_tmp1 .= 
										str_replace("#MODULE#", $arModules[$i], str_replace("#VER#", $arUpdaters[$i1][1], GetMessage("SUPP_UK_UPDN_ERR"))).": ".
										$strError_tmp2.". ";
								$strError_tmp1 .= str_replace("#MODULE#", $arModules[$i], GetMessage("SUPP_UK_UPDN_ERR_BREAK"))." ";
								break;
							}
						}
					}
				}

				if (strlen($strError_tmp1) <= 0)
					CUpdateClientPartner::__CopyDirFiles($updateDirFrom, $updateDirTo, $strError_tmp1, True);

				if (strlen($strError_tmp1) <= 0)
				{
					for ($i1 = 0, $n = count($arUpdaters); $i1 < $n; $i1++)
					{
						if ($arUpdaters[$i1][2]=="Y")
						{
							$strError_tmp2 = "";
							CUpdateClientPartner::__RunUpdaterScript($updateDirFrom.$arUpdaters[$i1][0], $strError_tmp2, "/bitrix/updates/".$updatesDir."/".$arModules[$i], $arModules[$i]);
							if (strlen($strError_tmp2) > 0)
							{
								$strError_tmp1 .= 
										str_replace("#MODULE#", $arModules[$i], str_replace("#VER#", $arUpdaters[$i1][1], GetMessage("SUPP_UK_UPDY_ERR"))).": ".
										$strError_tmp2.". ";
								$strError_tmp1 .= str_replace("#MODULE#", $arModules[$i], GetMessage("SUPP_UK_UPDN_ERR_BREAK"))." ";
								break;
							}
						}
					}
				}

				if (strlen($strError_tmp1) > 0)
					$strError_tmp .= $strError_tmp1;
			}
			CUpdateClientPartner::ClearUpdateFolder($updatesDirFull);
		}

		CUpdateClientPartner::AddMessage2Log("TIME UpdateStepModules ".Round(CUpdateClientPartner::__GetMicroTime()-$stime, 3)." sec");

		if (strlen($strError_tmp) > 0)
		{
			CUpdateClientPartner::AddMessage2Log($strError_tmp, "USM");
			$strError .= $strError_tmp;
			return False;
		}
		else
		{
			foreach(GetModuleEvents("main", "OnModuleUpdate", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($arModules));

			return True;
		}
	}

	public static function ActivateCoupon($coupon, &$strError, $lang = false, $stableVersionsOnly = "Y")
	{
		$strError_tmp = "";

		CUpdateClientPartner::AddMessage2Log("exec CUpdateClientPartner::ActivateCoupon");

		$strQuery = CUpdateClientPartner::__CollectRequestData($strError_tmp, $lang, $stableVersionsOnly, array(), array());
		if ($strQuery === False || StrLen($strQuery) <= 0 || StrLen($strError_tmp) > 0)
		{
			if (StrLen($strError_tmp) <= 0)
				$strError_tmp = "[RV01] ".GetMessage("SUPZ_NO_QSTRING").". ";
		}

		if (StrLen($strError_tmp) <= 0)
		{
			$strQuery .= "&coupon=".UrlEncode($coupon)."&query_type=coupon";
			CUpdateClientPartner::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strQuery));

			/*
			foreach ($arFields as $key => $value)
				$strQuery .= "&".$key."=".urlencode($value);
			*/

			$stime = CUpdateClientPartner::__GetMicroTime();
			$content = CUpdateClientPartner::__GetHTTPPage("ACTIV", $strQuery, $strError_tmp);
			if (strlen($content) <= 0)
			{
				if (StrLen($strError_tmp) <= 0)
					$strError_tmp = "[GNSU02] ".GetMessage("SUPZ_EMPTY_ANSWER").". ";
			}

			CUpdateClientPartner::AddMessage2Log("TIME ActivateCoupon(request) ".Round(CUpdateClientPartner::__GetMicroTime() - $stime, 3)." sec");
		}

		if (strlen($strError_tmp) <= 0)
		{
			$arRes = Array();
			CUpdateClientPartner::__ParseServerData($content, $arRes, $strError_tmp);
		}

		if (strlen($strError_tmp) <= 0)
		{
			if (isset($arRes["DATA"]["#"]["ERROR"]) && is_array($arRes["DATA"]["#"]["ERROR"]) && count($arRes["DATA"]["#"]["ERROR"]) > 0)
			{
				for ($i = 0, $n = count($arRes["DATA"]["#"]["ERROR"]); $i < $n; $i++)
				{
					if (strlen($arRes["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"]) > 0)
						$strError_tmp .= "[".$arRes["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"]."] ";

					$strError_tmp .= $arRes["DATA"]["#"]["ERROR"][$i]["#"].". ";
				}
			}
		}

		if (strlen($strError_tmp) > 0)
		{
			CUpdateClientPartner::AddMessage2Log($strError_tmp, "AC");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}

	/** Запускает updater модуля **/
	public static function __RunUpdaterScript($path, &$strError, $updateDirFrom, $moduleID)
	{
		global $DBType, $DB, $APPLICATION, $USER;

		if (!isset($GLOBALS["UPDATE_STRONG_UPDATE_CHECK"])
			|| ($GLOBALS["UPDATE_STRONG_UPDATE_CHECK"] != "Y" && $GLOBALS["UPDATE_STRONG_UPDATE_CHECK"] != "N"))
		{
			$GLOBALS["UPDATE_STRONG_UPDATE_CHECK"] = ((US_CALL_TYPE != "DB") ? COption::GetOptionString("main", "strong_update_check", "Y") : "Y");
		}
		$strongUpdateCheck = $GLOBALS["UPDATE_STRONG_UPDATE_CHECK"];

		$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

		$path = str_replace("\\", "/", $path);
		$updaterPath = dirname($path);
		$updaterPath = substr($updaterPath, strlen($_SERVER["DOCUMENT_ROOT"]));
		$updaterPath = Trim($updaterPath, " \t\n\r\0\x0B/\\");
		if (strlen($updaterPath) > 0)
			$updaterPath = "/".$updaterPath;

		$updaterName = substr($path, strlen($_SERVER["DOCUMENT_ROOT"]));

		CUpdateClientPartner::AddMessage2Log("Run updater '".$updaterName."'", "CSURUS1");

		$updater = new CUpdater();
		$updater->Init($updaterPath, $DBType, $updaterName, $updateDirFrom, $moduleID, US_CALL_TYPE);

		$errorMessage = "";

		include($path);

		if (strlen($errorMessage) > 0)
			$strError .= $errorMessage;
		if (is_array($updater->errorMessage) && count($updater->errorMessage) > 0)
			$strError .= implode("\n", $updater->errorMessage);

		unset($updater);
	}

	/** Сравнение двух версий в формате XX.XX.XX  **/
	/** Возвращает 1, если $strVers1 > $strVers2  **/
	/** Возвращает -1, если $strVers1 < $strVers2 **/
	/** Возвращает 0, если $strVers1 == $strVers2 **/
	public static function __CompareVersions($strVers1, $strVers2)
	{
		$strVers1 = Trim($strVers1);
		$strVers2 = Trim($strVers2);

		if ($strVers1 == $strVers2)
			return 0;

		$arVers1 = explode(".", $strVers1);
		$arVers2 = explode(".", $strVers2);

		if (IntVal($arVers1[0]) > IntVal($arVers2[0])
			|| IntVal($arVers1[0]) == IntVal($arVers2[0]) && IntVal($arVers1[1]) > IntVal($arVers2[1])
			|| IntVal($arVers1[0]) == IntVal($arVers2[0]) && IntVal($arVers1[1]) == IntVal($arVers2[1]) && IntVal($arVers1[2]) > IntVal($arVers2[2]))
		{
			return 1;
		}

		if (IntVal($arVers1[0]) == IntVal($arVers2[0]) && IntVal($arVers1[1]) == IntVal($arVers2[1]) && IntVal($arVers1[2]) == IntVal($arVers2[2]))
		{
			return 0;
		}

		return -1;
	}

	/** Запрашивает методом POST страницу $page со списком параметров **/
	/** $strVars и возвращает тело ответа. В параметре $strError      **/
	/** возвращается текст ошибки, если таковая была.                 **/
	public static function __GetHTTPPage($page, $strVars, &$strError)
	{
		global $SERVER_NAME, $DB;

		CUpdateClientPartner::AddMessage2Log("exec CUpdateClientPartner::GetHTTPPage");

		$ServerIP = COption::GetOptionString("main", "update_site", DEFAULT_UPDATE_SERVER);
		$ServerPort = 80;

		$proxyAddr = COption::GetOptionString("main", "update_site_proxy_addr", "");
		$proxyPort = COption::GetOptionString("main", "update_site_proxy_port", "");
		$proxyUserName = COption::GetOptionString("main", "update_site_proxy_user", "");
		$proxyPassword = COption::GetOptionString("main", "update_site_proxy_pass", "");

		$bUseProxy = (strlen($proxyAddr) > 0 && strlen($proxyPort) > 0);

		if ($page == "LIST")
			$page = "smp_updater_list.php";
		elseif ($page == "STEPM")
			$page = "smp_updater_modules.php";
		elseif ($page == "SEARCH")
			$page = "smp_updater_search.php";
		elseif ($page == "MODULE")
			$page = "smp_updater_actions.php";
		elseif ($page == "REG")
			$page = "smp_updater_register.php";
		elseif ($page == "ACTIV")
			$page = "us_updater_actions.php";

		$strVars .= "&product=".(IsModuleInstalled("intranet") ? "CORPORTAL" : "BSM")."&verfix=2";

		if ($bUseProxy)
		{
			$proxyPort = IntVal($proxyPort);
			if ($proxyPort <= 0)
				$proxyPort = 80;

			$requestIP = $proxyAddr;
			$requestPort = $proxyPort;
		}
		else
		{
			$requestIP = $ServerIP;
			$requestPort = $ServerPort;
		}

		$FP = fsockopen($requestIP, $requestPort, $errno, $errstr, 120);

		if ($FP)
		{
			$strRequest = "";

			if ($bUseProxy)
			{
				$strRequest .= "POST http://".$ServerIP."/bitrix/updates/".$page." HTTP/1.0\r\n";
				if (strlen($proxyUserName) > 0)
					$strRequest .= "Proxy-Authorization: Basic ".base64_encode($proxyUserName.":".$proxyPassword)."\r\n";
			}
			else
				$strRequest .= "POST /bitrix/updates/".$page." HTTP/1.0\r\n";

			$strRequest .= "User-Agent: BitrixSMUpdater\r\n";
			$strRequest .= "Accept: */*\r\n";
			$strRequest .= "Host: ".$ServerIP."\r\n";
			$strRequest .= "Accept-Language: en\r\n";
			$strRequest .= "Content-type: application/x-www-form-urlencoded\r\n";
			$strRequest .= "Content-length: ".strlen($strVars)."\r\n\r\n";
			$strRequest .= "$strVars";
			$strRequest .= "\r\n";
//CUpdateClientPartner::AddMessage2Log($strRequest, "!!!!!");
			fputs($FP, $strRequest);

			$bChunked = False;
			while (!feof($FP))
			{
				$line = fgets($FP, 4096);
				if ($line != "\r\n")
				{
					if (preg_match("/Transfer-Encoding: +chunked/i", $line))
						$bChunked = True;
				}
				else
				{
					break;
				}
			}

			$content = "";
			if ($bChunked)
			{
				$maxReadSize = 4096;

				$length = 0;
				$line = FGets($FP, $maxReadSize);
				$line = StrToLower($line);

				$strChunkSize = "";
				$i = 0;
				while ($i < StrLen($line) && in_array($line[$i], array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f")))
				{
					$strChunkSize .= $line[$i];
					$i++;
				}

				$chunkSize = hexdec($strChunkSize);

				while ($chunkSize > 0)
				{
					$processedSize = 0;
					$readSize = (($chunkSize > $maxReadSize) ? $maxReadSize : $chunkSize);

					while ($readSize > 0 && $line = fread($FP, $readSize))
					{
						$content .= $line;
						$processedSize += StrLen($line);
						$newSize = $chunkSize - $processedSize;
						$readSize = (($newSize > $maxReadSize) ? $maxReadSize : $newSize);
					}
					$length += $chunkSize;

					$line = FGets($FP, $maxReadSize);

					$line = FGets($FP, $maxReadSize);
					$line = StrToLower($line);

					$strChunkSize = "";
					$i = 0;
					while ($i < StrLen($line) && in_array($line[$i], array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f")))
					{
						$strChunkSize .= $line[$i];
						$i++;
					}

					$chunkSize = hexdec($strChunkSize);
				}
			}
			else
			{
				while ($line = fread($FP, 4096))
					$content .= $line;
			}

			fclose($FP);
		}
		else
		{
			$content = "";
			$strError .= GetMessage("SUPP_GHTTP_ER").": [".$errno."] ".$errstr.". ";
			if (IntVal($errno) <= 0)
				$strError .= GetMessage("SUPP_GHTTP_ER_DEF")." ";

			CUpdateClientPartner::AddMessage2Log("Error connecting 2 ".$ServerIP.": [".$errno."] ".$errstr."", "ERRCONN");
		}
		//CUpdateClientPartner::AddMessage2Log($content, "!1!");

		//echo "content:<br>".$content."<br><br>";
		return $content;
	}


	/** Проверяет на ошибки ответ сервера $strServerOutput **/
	/** и парсит в массив $arRes                           **/
	public static function __ParseServerData(&$strServerOutput, &$arRes, &$strError)
	{
		$strError_tmp = "";
		$arRes = array();

		CUpdateClientPartner::AddMessage2Log("exec CUpdateClientPartner::ParseServerData");

		//CUpdateClientPartner::AddMessage2Log($strServerOutput, "!2!");
		//echo "strServerOutput:<br>".htmlspecialcharsbx($strServerOutput)."<br><br>";

		if (strlen($strServerOutput) <= 0)
			$strError_tmp .= "[UPSD01] ".GetMessage("SUPP_AS_EMPTY_RESP").". ";

		if (strlen($strError_tmp) <= 0)
		{
			if (SubStr($strServerOutput, 0, StrLen("<DATA>")) != "<DATA>" && CUpdateClientPartner::__IsGzipInstalled())
				$strServerOutput = @gzuncompress($strServerOutput);
			if (SubStr($strServerOutput, 0, StrLen("<DATA>")) != "<DATA>")
			{
				CUpdateClientPartner::AddMessage2Log(substr($strServerOutput, 0, 100), "UPSD02");
				$strError_tmp .= "[UPSD02] ".GetMessage("SUPP_PSD_BAD_RESPONSE").". ";
			}
		}
		//CUpdateClientPartner::AddMessage2Log($strServerOutput, "!3!");

		//echo "strServerOutput:<br>".htmlspecialcharsbx($strServerOutput)."<br><br>";

		if (strlen($strError_tmp) <= 0)
		{
//			$arRes = CUpdateClientPartner::xmlize($strServerOutput);

			$objXML = new CUpdatesXML();
			$objXML->LoadString($strServerOutput);
			$arRes = $objXML->GetArray();

			if (!is_array($arRes) || !isset($arRes["DATA"]) || !is_array($arRes["DATA"]))
				$strError_tmp .= "[UPSD03] ".GetMessage("SUPP_PSD_BAD_TRANS").". ";
		}

		if (strlen($strError_tmp) <= 0)
		{
			if (isset($arRes["DATA"]["#"]["RESPONSE"]))
			{
				$CRCCode = $arRes["DATA"]["#"]["RESPONSE"][0]["@"]["CRC_CODE"];
				if (StrLen($CRCCode) > 0)
					COption::SetOptionString(US_BASE_MODULE, "crc_code", $CRCCode);
			}
			if (isset($arRes["DATA"]["#"]["CLIENT"]) && isset($arRes["DATA"]["#"]["CLIENT"][0]["@"]["DATE_TO_SOURCE"]))
				COption::SetOptionString(US_BASE_MODULE, "~support_finish_date", $arRes["DATA"]["#"]["CLIENT"][0]["@"]["DATE_TO_SOURCE"]);
		}

		if (strlen($strError_tmp) > 0)
		{
			CUpdateClientPartner::AddMessage2Log($strError_tmp, "CUPSD");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}

	/** Проверка на установку GZip компрессии **/
	public static function __IsGzipInstalled()
	{
		if (function_exists("gzcompress"))
			return (COption::GetOptionString("main", "update_is_gzip_installed", "Y") == "Y" ? true : false);
		
		return False;
	}

	public static function __GetFooPath()
	{
		$db = CLang::GetList($by="", $order="", array("ACTIVE" => "Y"));
		$cnt = 0;
		while ($ar = $db->Fetch())
			$cnt++;
		return $cnt;
	}

	public static function __GetFooPath1($v = 0)
	{
		$q = "SELECT COUNT(ID) as C FROM b_user WHERE ACTIVE = 'Y' AND LAST_LOGIN IS NOT NULL";
		if ($v == 0)
			$q = "SELECT COUNT(U.ID) as C FROM b_user U WHERE U.ACTIVE = 'Y' AND U.LAST_LOGIN IS NOT NULL AND EXISTS(SELECT 'x' FROM b_utm_user UF, b_user_field F WHERE F.ENTITY_ID = 'USER' AND F.FIELD_NAME = 'UF_DEPARTMENT' AND UF.FIELD_ID = F.ID AND UF.VALUE_ID = U.ID AND UF.VALUE_INT IS NOT NULL AND UF.VALUE_INT <> 0)";
		$dbRes = $GLOBALS["DB"]->Query($q, true);
		if ($dbRes && ($arRes = $dbRes->Fetch()))
			return $arRes["C"];
		else
			return 0;
	}

	/** Создание путя, если его нет, и установка прав писать **/
	public static function __CheckDirPath($path, $bPermission = true)
	{
		$badDirs = Array();
		$path = str_replace("\\", "/", $path);
		$path = str_replace("//", "/", $path);

		if ($path[strlen($path)-1] != "/") //отрежем имя файла
		{
			$p = CUpdateClientPartner::__bxstrrpos($path, "/");
			$path = substr($path, 0, $p);
		}

		while (strlen($path)>1 && $path[strlen($path)-1]=="/") //отрежем / в конце, если есть
			$path = substr($path, 0, strlen($path)-1);

		$p = CUpdateClientPartner::__bxstrrpos($path, "/");
		while ($p > 0)
		{
			if (file_exists($path) && is_dir($path))
			{
				if ($bPermission)
				{
					if (!is_writable($path))
						@chmod($path, BX_DIR_PERMISSIONS);
				}
				break;
			}
			$badDirs[] = substr($path, $p+1);
			$path = substr($path, 0, $p);
			$p = CUpdateClientPartner::__bxstrrpos($path, "/");
		}

		for ($i = count($badDirs)-1; $i>=0; $i--)
		{
			$path = $path."/".$badDirs[$i];
			@mkdir($path, BX_DIR_PERMISSIONS);
		}
	}

	/** Рекурсивное копирование из $path_from в $path_to **/
	public static function __CopyDirFiles($path_from, $path_to, &$strError, $bSkipUpdater = True)
	{
		$strError_tmp = "";

		while (strlen($path_from) > 1 && $path_from[strlen($path_from)-1] == "/")
			$path_from = substr($path_from, 0, strlen($path_from)-1);

		while (strlen($path_to) > 1 && $path_to[strlen($path_to)-1] == "/")
			$path_to = substr($path_to, 0, strlen($path_to)-1);

		if (strpos($path_to."/", $path_from."/") === 0)
			$strError_tmp .= "[UCDF01] ".GetMessage("SUPP_CDF_SELF_COPY").". ";

		if (strlen($strError_tmp) <= 0)
		{
			if (!file_exists($path_from))
				$strError_tmp .= "[UCDF02] ".str_replace("#FILE#", $path_from, GetMessage("SUPP_CDF_NO_PATH")).". ";
		}

		if (strlen($strError_tmp) <= 0)
		{
			$strongUpdateCheck = COption::GetOptionString("main", "strong_update_check", "Y");

			if (is_dir($path_from))
			{
				CUpdateClientPartner::__CheckDirPath($path_to."/");

				if (!file_exists($path_to) || !is_dir($path_to))
					$strError_tmp .= "[UCDF03] ".str_replace("#FILE#", $path_to, GetMessage("SUPP_CDF_CANT_CREATE")).". ";
				elseif (!is_writable($path_to))
					$strError_tmp .= "[UCDF04] ".str_replace("#FILE#", $path_to, GetMessage("SUPP_CDF_CANT_WRITE")).". ";

				if (strlen($strError_tmp) <= 0)
				{
					if ($handle = @opendir($path_from))
					{
						while (($file = readdir($handle)) !== false)
						{
							if ($file == "." || $file == "..")
								continue;

							if ($bSkipUpdater && substr($file, 0, strlen("updater")) == "updater")
								continue;

							if (is_dir($path_from."/".$file))
							{
								CUpdateClientPartner::__CopyDirFiles($path_from."/".$file, $path_to."/".$file, $strError_tmp);
							}
							elseif (is_file($path_from."/".$file))
							{
								if (file_exists($path_to."/".$file) && !is_writable($path_to."/".$file))
								{
									$strError_tmp .= "[UCDF05] ".str_replace("#FILE#", $path_to."/".$file, GetMessage("SUPP_CDF_CANT_FILE")).". ";
								}
								else
								{
									if ($strongUpdateCheck == "Y")
										$crc32_old = dechex(crc32(file_get_contents($path_from."/".$file)));

									@copy($path_from."/".$file, $path_to."/".$file);
									@chmod($path_to."/".$file, BX_FILE_PERMISSIONS);

									if ($strongUpdateCheck == "Y")
									{
										$crc32_new = dechex(crc32(file_get_contents($path_to."/".$file)));
										if ($crc32_new != $crc32_old)
										{
											$strError_tmp .= "[UCDF061] ".str_replace("#FILE#", $path_to."/".$file, GetMessage("SUPP_UGA_FILE_CRUSH")).". ";
										}
									}
								}
							}
						}
						@closedir($handle);
					}
				}
			}
			else
			{
				$p = CUpdateClientPartner::__bxstrrpos($path_to, "/");
				$path_to_dir = substr($path_to, 0, $p);
				CUpdateClientPartner::__CheckDirPath($path_to_dir."/");

				if (!file_exists($path_to_dir) || !is_dir($path_to_dir))
					$strError_tmp .= "[UCDF06] ".str_replace("#FILE#", $path_to_dir, GetMessage("SUPP_CDF_CANT_FOLDER")).". ";
				elseif (!is_writable($path_to_dir))
					$strError_tmp .= "[UCDF07] ".str_replace("#FILE#", $path_to_dir, GetMessage("SUPP_CDF_CANT_FOLDER_WR")).". ";

				if (strlen($strError_tmp) <= 0)
				{
					if ($strongUpdateCheck == "Y")
						$crc32_old = dechex(crc32(file_get_contents($path_from)));

					@copy($path_from, $path_to);
					@chmod($path_to, BX_FILE_PERMISSIONS);

					if ($strongUpdateCheck == "Y")
					{
						$crc32_new = dechex(crc32(file_get_contents($path_to)));
						if ($crc32_new != $crc32_old)
						{
							$strError_tmp .= "[UCDF0611] ".str_replace("#FILE#", $path_to, GetMessage("SUPP_UGA_FILE_CRUSH")).". ";
						}
					}
				}
			}
		}

		if (strlen($strError_tmp) > 0)
		{
			CUpdateClientPartner::AddMessage2Log($strError_tmp, "CUCDF");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}

	/** Рекурсивное удаление $path **/
	public static function __DeleteDirFilesEx($path)
	{
		if (!file_exists($path))
			return False;

		if (is_file($path))
		{
			@unlink($path);
			return True;
		}

		if ($handle = @opendir($path))
		{
			while (($file = readdir($handle)) !== false)
			{
				if ($file == "." || $file == "..") continue;

				if (is_dir($path."/".$file))
				{
					CUpdateClientPartner::__DeleteDirFilesEx($path."/".$file);
				}
				else
				{
					@unlink($path."/".$file);
				}
			}
		}
		@closedir($handle);
		@rmdir($path);
		return True;
	}

	public static function __bxstrrpos($haystack, $needle)
	{
		$index = strpos(strrev($haystack), strrev($needle));
		if($index === false)
			return false;
		$index = strlen($haystack) - strlen($needle) - $index;
		return $index;
	}

	/** Возвращает экземпляр класса-инсталятора модуля по абсолютному пути $path **/
	public static function __GetModuleInfo($path)
	{
		$arModuleVersion = array();
//		include($path."/install/version.php");
//		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
//			return $arModuleVersion;

		include_once($path."/install/index.php");

		$arr = explode("/", $path);
		$i = array_search("modules", $arr);
		$module_code = $arr[$i+1];

		$class_name = str_replace(".", "_", $module_code);
		if (!class_exists($class_name))
			return array();

		$cls = new $class_name;
		$result = array(
			"VERSION" => $cls->MODULE_VERSION,
			"VERSION_DATE" => $cls->MODULE_VERSION_DATE,
			"IS_DEMO" => ((defined($class_name."_DEMO") && constant($class_name."_DEMO")) ? "Y" : "N"),
			"ACTIVE" => "Y",
		);

		if($result["IS_DEMO"] == "Y" && IsModuleInstalled($module_code) && CModule::IncludeModuleEx($module_code) == MODULE_DEMO_EXPIRED)
			$result["ACTIVE"] = "N";

		return $result;
	}

	public static function __GetMicroTime()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
}
