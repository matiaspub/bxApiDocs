<?
/**********************************************************************/
/**    DO NOT MODIFY THIS FILE                                       **/
/**    MODIFICATION OF THIS FILE WILL ENTAIL SITE FAILURE            **/
/**********************************************************************/

if (!defined("BX_DIR_PERMISSIONS"))
	// define("BX_DIR_PERMISSIONS", 0755);

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

class CUpdateSystem
{
	public static function IsInCommonKernel()
	{
		return (!defined("US_BITRIX24_MODE") || !US_BITRIX24_MODE) && file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_db_updater.php");
	}

	/** Подписка на информацию об обновлениях **/
	public static function SubscribeUpdates($strEmails, &$strError, $lang = false)
	{
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::SubscribeUpdates");

		if ($lang===false)
			$lang = LANGUAGE_ID;

		if (strlen($strError_tmp)<=0)
		{
			$GLOBALS["DB"]->GetVersion();

			$strVars = "LICENSE_KEY=".urlencode(md5(CUpdateSystem::GetLicenseKey())).
				"&CLIENT_SITE=".urlencode($_SERVER["SERVER_NAME"]).
				"&CANGZIP=".urlencode((CUpdateSystem::IsGzipInstalled()) ? "Y" : "N").
				"&SUPD_VER=".urlencode(UPDATE_SYSTEM_VERSION).
				"&SUBSCR_EMAIL=".urlencode($strEmails).
				"&SUPD_STS=".urlencode(CUpdateSystem::GetFooPath("GetList")).
				"&SUPD_DBS=".urlencode($GLOBALS["DB"]->type).
				"&XE=".urlencode(($GLOBALS["DB"]->XE) ? "Y" : "N").
				"&CLIENT_PHPVER=".urlencode(phpversion()).
				"&lang=".urlencode($lang);

			$content = CUpdateSystem::getHTTPPage("bit_syssubscr.php", $strVars, $strError_tmp);

			if (strlen($content)<=0)
				$strError_tmp .= "[USU058] Empty server response.<br>";
		}
	}

	/** Активирует лицензионный ключ **/
	public static function AddSites($strCheck, &$strError, $lang = false, $stableVersionsOnly = "Y")
	{
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::AddSites");

		if ($lang===false)
			$lang = LANGUAGE_ID;

		$stableVersionsOnly = (($stableVersionsOnly == "N") ? "N" : "Y");

		if (strlen($strCheck)<=0)
		{
			$strError_tmp .= "[UAS01] ".GetMessage("SUPP_AS_NO_CHECK").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$GLOBALS["DB"]->GetVersion();

			$strVars = "LICENSE_KEY=".urlencode(md5(CUpdateSystem::GetLicenseKey())).
				"&CLIENT_SITE=".urlencode($_SERVER["SERVER_NAME"]).
				"&CANGZIP=".urlencode((CUpdateSystem::IsGzipInstalled()) ? "Y" : "N").
				"&UTYPES=".urlencode("W").
				"&COUNT_ONLY=".urlencode("Y").
				"&SUPD_STS=".urlencode(CUpdateSystem::GetFooPath("GetList")).
				"&SUPD_DBS=".urlencode($GLOBALS["DB"]->type).
				"&XE=".urlencode(($GLOBALS["DB"]->XE) ? "Y" : "N").
				"&SUPD_VER=".urlencode(UPDATE_SYSTEM_VERSION).
				"&CHECK=".urlencode($strCheck).
				"&CLIENT_PHPVER=".urlencode(phpversion()).
				"&stable=".urlencode($stableVersionsOnly).
				"&lang=".urlencode($lang);

			CUpdateSystem::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strVars));

			$stime = CUpdateSystem::getmicrotime();
			$content = CUpdateSystem::getHTTPPage("bit_sysserver.php", $strVars, $strError_tmp);
			CUpdateSystem::AddMessage2Log("TIME AddSites.getHTTPPage ".Round(CUpdateSystem::getmicrotime()-$stime, 3)." sec");

			if (strlen($content)<=0)
				$strError_tmp .= "[UAS02] ".GetMessage("SUPP_AS_EMPTY_RESP").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$arRes = Array();
			CUpdateSystem::ParseServerData($content, $arRes, $strError_tmp);
		}

		if (strlen($strError_tmp)<=0)
		{
			if (isset($arRes["DATA"]["#"]["ERROR"])
				&& is_array($arRes["DATA"]["#"]["ERROR"])
				&& count($arRes["DATA"]["#"]["ERROR"])>0)
			{
				for ($i = 0, $n = count($arRes["DATA"]["#"]["ERROR"]); $i < $n; $i++)
				{
					if (strlen($arRes["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"])>0)
						$strError_tmp .= "[".$arRes["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"]."] ";

					$strError_tmp .= $arRes["DATA"]["#"]["ERROR"][$i]["#"].".<br>";
				}
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			CUpdateSystem::AddMessage2Log("Check applied successfully!", "CUAS");
		}

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUAS");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}

	/** Активирует лицензионный ключ **/
	public static function ActivateLicenseKey($arFields, &$strError, $lang = false, $stableVersionsOnly = "Y")
	{
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::ActivateLicenseKey");

		$stableVersionsOnly = (($stableVersionsOnly == "N") ? "N" : "Y");

		if ($lang===false)
			$lang = LANGUAGE_ID;

		if (strlen($strError_tmp)<=0)
		{
			$GLOBALS["DB"]->GetVersion();

			$strVars = "LICENSE_KEY=".urlencode(md5(CUpdateSystem::GetLicenseKey())).
				"&CLIENT_SITE=".urlencode($_SERVER["SERVER_NAME"]).
				"&CANGZIP=".urlencode((CUpdateSystem::IsGzipInstalled()) ? "Y" : "N").
				"&UTYPES=".urlencode("A").
				"&COUNT_ONLY=".urlencode("N").
				"&SUPD_STS=".urlencode(CUpdateSystem::GetFooPath("GetList")).
				"&SUPD_DBS=".urlencode($GLOBALS["DB"]->type).
				"&XE=".urlencode(($GLOBALS["DB"]->XE) ? "Y" : "N").
				"&SUPD_VER=".urlencode(UPDATE_SYSTEM_VERSION).
				"&CLIENT_PHPVER=".urlencode(phpversion()).
				"&stable=".urlencode($stableVersionsOnly).
				"&lang=".urlencode($lang);

			foreach ($arFields as $key => $value)
			{
				$strVars .= "&".$key."=".urlencode($value);
			}

			CUpdateSystem::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strVars));

			$stime = CUpdateSystem::getmicrotime();
			$content = CUpdateSystem::getHTTPPage("bit_sysserver.php", $strVars, $strError_tmp);
			CUpdateSystem::AddMessage2Log("TIME ActivateLicenseKey.getHTTPPage ".Round(CUpdateSystem::getmicrotime()-$stime, 3)." sec");

			if (strlen($content)<=0)
				$strError_tmp .= "[UALK01] ".GetMessage("SUPP_AS_EMPTY_RESP").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$arRes = Array();
			CUpdateSystem::ParseServerData($content, $arRes, $strError_tmp);
		}

		if (strlen($strError_tmp)<=0)
		{
			if (isset($arRes["DATA"]["#"]["ERROR"])
				&& is_array($arRes["DATA"]["#"]["ERROR"])
				&& count($arRes["DATA"]["#"]["ERROR"])>0)
			{
				for ($i = 0, $n = count($arRes["DATA"]["#"]["ERROR"]); $i < $n; $i++)
				{
					if (strlen($arRes["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"])>0)
						$strError_tmp .= "[".$arRes["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"]."] ";

					$strError_tmp .= $arRes["DATA"]["#"]["ERROR"][$i]["#"].".<br>";
				}
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			CUpdateSystem::AddMessage2Log("License key activated successfully!", "CUALK");
		}

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUALK");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}

	// Регистрирует копию продукта, если можно
	public static function RegisterVersion(&$strError, $lang = false, $stableVersionsOnly = "Y")
	{
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::RegisterVersion");

		$stableVersionsOnly = (($stableVersionsOnly == "N") ? "N" : "Y");

		if ($lang===false)
			$lang = LANGUAGE_ID;

		if (strlen($strError_tmp)<=0)
		{
			$GLOBALS["DB"]->GetVersion();

			$strVars = "LICENSE_KEY=".urlencode(md5(CUpdateSystem::GetLicenseKey())).
				"&CLIENT_SITE=".urlencode($_SERVER["SERVER_NAME"]).
				"&CANGZIP=".urlencode((CUpdateSystem::IsGzipInstalled()) ? "Y" : "N").
				"&UTYPES=".urlencode("R").
				"&COUNT_ONLY=".urlencode("F").
				"&VERSION=".urlencode(SM_VERSION).
				"&SUPD_STS=".urlencode(CUpdateSystem::GetFooPath("GetList")).
				"&SUPD_DBS=".urlencode($GLOBALS["DB"]->type).
				"&XE=".urlencode(($GLOBALS["DB"]->XE) ? "Y" : "N").
				"&SUPD_VER=".urlencode(UPDATE_SYSTEM_VERSION).
				"&CLIENT_PHPVER=".urlencode(phpversion()).
				"&stable=".urlencode($stableVersionsOnly).
				"&lang=".urlencode($lang);

			CUpdateSystem::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strVars));

			$stime = CUpdateSystem::getmicrotime();
			$fcontent = CUpdateSystem::getHTTPPage("bit_sysserver.php", $strVars, $strError_tmp);
			CUpdateSystem::AddMessage2Log("TIME RegisterVersion.getHTTPPage ".Round(CUpdateSystem::getmicrotime()-$stime, 3)." sec");

			if (strlen($fcontent)<=0)
				$strError_tmp .= "[URV01] ".GetMessage("SUPP_AS_EMPTY_RESP").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!($fp1 = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz", "wb")))
				$strError_tmp .= "[URV02] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates", GetMessage("SUPP_RV_ER_TEMP_FILE")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!fwrite($fp1, $fcontent))
				$strError_tmp .= "[URV03] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz", GetMessage("SUPP_RV_WRT_TEMP_FILE")).".<br>";

			@fclose($fp1);
		}

		if (strlen($strError_tmp)<=0)
		{
			$temporary_updates_dir = "";
			if (!CUpdateSystem::UnGzipArchive($temporary_updates_dir, $strError_tmp, "Y"))
				$strError_tmp .= "[URV04] ".GetMessage("SUPP_RV_BREAK").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$updates_dir_full = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$temporary_updates_dir;
			if (!file_exists($updates_dir_full."/update_info.xml") || !is_file($updates_dir_full."/update_info.xml"))
			{
				$strError_tmp .= "[URV05] ".str_replace("#FILE#", $updates_dir_full."/update_info.xml", GetMessage("SUPP_RV_ER_DESCR_FILE")).".<br>";
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!is_readable($updates_dir_full."/update_info.xml"))
				$strError_tmp .= "[URV06] ".str_replace("#FILE#", $updates_dir_full."/update_info.xml", GetMessage("SUPP_RV_READ_DESCR_FILE")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$content = file_get_contents($updates_dir_full."/update_info.xml");
		}

		if (strlen($strError_tmp)<=0)
		{
			$arRes = Array();
			CUpdateSystem::ParseServerData($content, $arRes, $strError_tmp);
		}

		if (strlen($strError_tmp)<=0)
		{
			if (isset($arRes["DATA"]["#"]["ERROR"])
				&& is_array($arRes["DATA"]["#"]["ERROR"])
				&& count($arRes["DATA"]["#"]["ERROR"])>0)
			{
				for ($i = 0, $n = count($arRes["DATA"]["#"]["ERROR"]); $i < $n; $i++)
				{
					if (strlen($arRes["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"])>0)
						$strError_tmp .= "[".$arRes["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"]."] ";

					$strError_tmp .= $arRes["DATA"]["#"]["ERROR"][$i]["#"].".<br>";
				}
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!file_exists($updates_dir_full."/include.php") || !is_file($updates_dir_full."/include.php"))
			{
				$strError_tmp .= "[URV07] ".GetMessage("SUPP_RV_NO_FILE").".<br>";
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			$newfilesize = @filesize($updates_dir_full."/include.php");
			if (IntVal($newfilesize)!=IntVal($arRes["DATA"]["#"]["FILE"][0]["@"]["SIZE"]))
			{
				$strError_tmp .= "[URV08] ".GetMessage("SUPP_RV_ER_SIZE").".<br>";
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!is_writeable($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include.php"))
			{
				$strError_tmp .= "[URV09] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include.php", GetMessage("SUPP_RV_NO_WRITE")).".<br>";
			}
		}

		if (strlen($strError_tmp) <= 0)
		{
			if (!copy($updates_dir_full."/include.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include.php"))
				$strError_tmp .= "[URV10] ".GetMessage("SUPP_RV_ERR_COPY").".<br>";
			@chmod($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include.php", BX_FILE_PERMISSIONS);
		}

		if (strlen($strError_tmp) <= 0)
		{
			$strongUpdateCheck = COption::GetOptionString("main", "strong_update_check", "Y");

			if ($strongUpdateCheck == "Y")
			{
				$crc32_old = dechex(crc32(file_get_contents($updates_dir_full."/include.php")));
				$crc32_new = dechex(crc32(file_get_contents($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include.php")));
				if ($crc32_new != $crc32_old)
					$strError_tmp .= "[URV1011] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include.php", GetMessage("SUPP_UGA_FILE_CRUSH")).".<br>";
			}
		}

		if (strlen($strError_tmp) <= 0)
		{
			CUpdateSystem::AddMessage2Log("Product registered successfully!", "CURV");
			CUpdateSystem::DeleteDirFilesEx($updates_dir_full);
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


	// Обновляет систему обновлений
	public static function UpdateUpdate(&$strError, $lang = false, $stableVersionsOnly = "Y")
	{
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::UpdateUpdate");

		if ($lang===false)
			$lang = LANGUAGE_ID;

		$stableVersionsOnly = (($stableVersionsOnly == "N") ? "N" : "Y");

		if (strlen($strError_tmp)<=0)
		{
			$GLOBALS["DB"]->GetVersion();

			$strVars = "LICENSE_KEY=".urlencode(md5(CUpdateSystem::GetLicenseKey())).
				"&CLIENT_SITE=".urlencode($_SERVER["SERVER_NAME"]).
				"&CANGZIP=".urlencode((CUpdateSystem::IsGzipInstalled()) ? "Y" : "N").
				"&UTYPES=".urlencode("U").
				"&SUPD_STS=".urlencode(CUpdateSystem::GetFooPath("GetList")).
				"&COUNT_ONLY=".urlencode("F").
				"&SUPD_VER=".urlencode(UPDATE_SYSTEM_VERSION).
				"&SUPD_DBS=".urlencode($GLOBALS["DB"]->type).
				"&XE=".urlencode(($GLOBALS["DB"]->XE) ? "Y" : "N").
				"&CLIENT_PHPVER=".urlencode(phpversion()).
				"&stable=".urlencode($stableVersionsOnly).
				"&lang=".urlencode($lang);

			CUpdateSystem::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strVars));

			$fcontent = CUpdateSystem::getHTTPPage("bit_sysserver.php", $strVars, $strError_tmp);

			if (strlen($fcontent)<=0)
				$strError_tmp .= "[UUU01] ".GetMessage("SUPP_AS_EMPTY_RESP").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!($fp1 = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz", "wb")))
				$strError_tmp .= "[UUU02] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates", GetMessage("SUPP_RV_ER_TEMP_FILE")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!fwrite($fp1, $fcontent))
				$strError_tmp .= "[UUU03] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz", GetMessage("SUPP_RV_WRT_TEMP_FILE")).".<br>";

			@fclose($fp1);
		}

		if (strlen($strError_tmp)<=0)
		{
			$temporary_updates_dir = "";
			if (!CUpdateSystem::UnGzipArchive($temporary_updates_dir, $strError_tmp, "Y"))
				$strError_tmp .= "[UUU04] ".GetMessage("SUPP_UU_BREAK").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$updates_dir_full = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$temporary_updates_dir;
			if (!file_exists($updates_dir_full."/update_info.xml") || !is_file($updates_dir_full."/update_info.xml"))
				$strError_tmp .= "[UUU05] ".str_replace("#FILE#", $updates_dir_full."/update_info.xml", GetMessage("SUPP_RV_ER_DESCR_FILE")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!is_readable($updates_dir_full."/update_info.xml"))
				$strError_tmp .= "[URV06] ".str_replace("#FILE#", $updates_dir_full."/update_info.xml", GetMessage("SUPP_RV_READ_DESCR_FILE")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$content = file_get_contents($updates_dir_full."/update_info.xml");
		}

		if (strlen($strError_tmp)<=0)
		{
			$arRes = Array();
			CUpdateSystem::ParseServerData($content, $arRes, $strError_tmp);
		}

		if (strlen($strError_tmp)<=0)
		{
			if (isset($arRes["DATA"]["#"]["ERROR"])
				&& is_array($arRes["DATA"]["#"]["ERROR"])
				&& count($arRes["DATA"]["#"]["ERROR"])>0)
			{
				for ($i = 0, $n = count($arRes["DATA"]["#"]["ERROR"]); $i < $n; $i++)
				{
					if (strlen($arRes["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"])>0)
						$strError_tmp .= "[".$arRes["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"]."] ";

					$strError_tmp .= $arRes["DATA"]["#"]["ERROR"][$i]["#"].".<br>";
				}
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			$aFiles = array(
				//"UUU071"=>"update_update.php",
				"UUU072"=>"update_class.php",
				"UUU073"=>"update_list.php",
				//"UUU074"=>"update_update5.php",
				"UUU075"=>"update_log.php",
			);
			foreach($aFiles as $err=>$file)
				if(!file_exists($updates_dir_full."/main/classes/general/".$file) || !is_file($updates_dir_full."/main/classes/general/".$file))
					$strError_tmp .= "[".$err."] ".str_replace("#FILE#", $file, GetMessage("SUPP_UU_NO_UFILE")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!CUpdateSystem::CheckUpdatability($temporary_updates_dir, $strError_tmp))
			{
				$strError_tmp .= "[UUU08] ".GetMessage("SUPP_UU_LOAD_BREAK").".<br>";
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			$new_updatesys_version = "";
			if (isset($arRes["DATA"]["#"]["UPDATE_SYSTEM"])
				&& is_array($arRes["DATA"]["#"]["UPDATE_SYSTEM"])
				&& count($arRes["DATA"]["#"]["UPDATE_SYSTEM"])>0
				&& isset($arRes["DATA"]["#"]["UPDATE_SYSTEM"][0]["@"]["VERSION"]))
			{
				$new_updatesys_version = Trim($arRes["DATA"]["#"]["UPDATE_SYSTEM"][0]["@"]["VERSION"]);
			}

			if (strlen($new_updatesys_version)<=0)
				$strError_tmp .= "[UUU080] ".GetMessage("SUPP_UU_NO_NEW_VER").".<br>";
		}

		/*
		if (strlen($strError_tmp)<=0)
		{
			$strUpdateContent = file_get_contents($updates_dir_full."/main/classes/general/update_update.php");
			$strUpdateContent = "<"."?define(\"UPDATE_SYSTEM_VERSION\", \"".$new_updatesys_version."\");?".">".$strUpdateContent;

			if (!($fp1 = fopen($updates_dir_full."/main/classes/general/update_update.php", "wb")))
				$strError_tmp .= "[UUU082] "."Не могу открыть на запись временный файл \"".$updates_dir_full."/main/classes/general/update_update.php\".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!fwrite($fp1, $strUpdateContent))
				$strError_tmp .= "[UUU083] "."Не могу писать во временный файл \"".$updates_dir_full."/main/classes/general/update_update.php\".<br>";

			@fclose($fp1);
		}
		*/

		if (strlen($strError_tmp)<=0)
		{
			CUpdateSystem::CopyDirFiles($updates_dir_full."/main", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main", $strError_tmp);
		}

		if (strlen($strError_tmp)<=0)
		{
			CUpdateSystem::AddMessage2Log("Update updated successfully!", "CUUU");
			CUpdateSystem::DeleteDirFilesEx($updates_dir_full);
		}

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUUU");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}

	// Закачивает исходники продукта, если можно
	public static function LoadSources(&$strError, $lang = false, $stableVersionsOnly = "Y")
	{
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::LoadSources");

		if ($lang===false)
			$lang = LANGUAGE_ID;

		$stableVersionsOnly = (($stableVersionsOnly == "N") ? "N" : "Y");

		$arClientModules = CUpdateSystem::GetModules($strError_tmp);

		if (strlen($strError_tmp)<=0)
		{
			$GLOBALS["DB"]->GetVersion();

			$strVars = "LICENSE_KEY=".urlencode(md5(CUpdateSystem::GetLicenseKey())).
				"&lang=".urlencode($lang).
				"&UTYPES=".urlencode("S").
				"&SUPD_VER=".urlencode(UPDATE_SYSTEM_VERSION).
				"&COUNT_ONLY=".urlencode("F").
				"&SUPD_STS=".urlencode(CUpdateSystem::GetFooPath("GetList")).
				"&CLIENT_SITE=".urlencode($_SERVER["SERVER_NAME"]).
				"&SUPD_DBS=".urlencode($GLOBALS["DB"]->type).
				"&XE=".urlencode(($GLOBALS["DB"]->XE) ? "Y" : "N").
				"&CANGZIP=".urlencode((CUpdateSystem::IsGzipInstalled()) ? "Y" : "N").
				"&CLIENT_PHPVER=".urlencode(phpversion()).
				"&stable=".urlencode($stableVersionsOnly).
				"&".CUpdateSystem::ModulesArray2Query($arClientModules, "bitm_");

			CUpdateSystem::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strVars));

			$stime = CUpdateSystem::getmicrotime();
			$fcontent = CUpdateSystem::getHTTPPage("bit_sysserver.php", $strVars, $strError_tmp);
			CUpdateSystem::AddMessage2Log("TIME LoadSources.getHTTPPage ".Round(CUpdateSystem::getmicrotime()-$stime, 3)." sec");

			if (strlen($fcontent)<=0)
				$strError_tmp .= "[ULS01] ".GetMessage("SUPP_AS_EMPTY_RESP").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!($fp1 = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz", "wb")))
				$strError_tmp .= "[ULS02] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates", GetMessage("SUPP_RV_ER_TEMP_FILE")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!fwrite($fp1, $fcontent))
				$strError_tmp .= "[ULS03] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz", GetMessage("SUPP_RV_WRT_TEMP_FILE")).".<br>";

			@fclose($fp1);
		}

		if (strlen($strError_tmp)<=0)
		{
			$temporary_updates_dir = "";
			if (!CUpdateSystem::UnGzipArchive($temporary_updates_dir, $strError_tmp, "Y"))
				$strError_tmp .= "[ULS04] ".GetMessage("SUPP_LS_LOAD_BREAK").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$updates_dir_full = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$temporary_updates_dir;
			if (!file_exists($updates_dir_full."/update_info.xml") || !is_file($updates_dir_full."/update_info.xml"))
				$strError_tmp .= "[ULS05] ".str_replace("#FILE#", $updates_dir_full."/update_info.xml", GetMessage("SUPP_RV_ER_DESCR_FILE")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!is_readable($updates_dir_full."/update_info.xml"))
				$strError_tmp .= "[ULS06] ".str_replace("#FILE#", $updates_dir_full."/update_info.xml", GetMessage("SUPP_RV_READ_DESCR_FILE")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$content = file_get_contents($updates_dir_full."/update_info.xml");
		}

		if (strlen($strError_tmp)<=0)
		{
			$arRes = Array();
			CUpdateSystem::ParseServerData($content, $arRes, $strError_tmp);
		}

		if (strlen($strError_tmp)<=0)
		{
			if (isset($arRes["DATA"]["#"]["ERROR"])
				&& is_array($arRes["DATA"]["#"]["ERROR"])
				&& count($arRes["DATA"]["#"]["ERROR"])>0)
			{
				for ($i = 0, $n = count($arRes["DATA"]["#"]["ERROR"]); $i < $n; $i++)
				{
					if (strlen($arRes["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"])>0)
						$strError_tmp .= "[".$arRes["DATA"]["#"]["ERROR"][$i]["@"]["TYPE"]."] ";

					$strError_tmp .= $arRes["DATA"]["#"]["ERROR"][$i]["#"].".<br>";
				}
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!CUpdateSystem::CheckUpdatability($temporary_updates_dir, $strError_tmp))
				$strError_tmp .= "[ULS07] ".GetMessage("SUPP_LS_LOAD_BREAK").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			foreach ($arClientModules as $module_id => $version)
			{
				$strError_tmp1 = "";

				$from_dir = $updates_dir_full."/".$module_id;
				$to_dir = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id;

				if (!file_exists($to_dir) || !is_dir($to_dir))
				{
					$strError_tmp1 .= "[ULS08] ".str_replace("#MODULE#", $module_id, GetMessage("SUPP_LS_NO_MOD_CAT")).".<br>";
				}
				elseif (!is_writable($to_dir))
				{
					$strError_tmp1 .= "[ULS09] ".str_replace("#MODULE#", $module_id, GetMessage("SUPP_LS_WR_MOD_CAT")).".<br>";
				}

				if (!file_exists($from_dir) || !is_dir($from_dir))
				{
					$strError_tmp1 .= "[ULS10] ".str_replace("#MODULE#", $module_id, GetMessage("SUPP_LS_NO_SRC_MOD")).".<br>";
				}
				elseif (!is_readable($from_dir))
				{
					$strError_tmp1 .= "[ULS11] ".str_replace("#MODULE#", $module_id, GetMessage("SUPP_LS_RD_SRC_MOD")).".<br>";
				}

				if (strlen($strError_tmp1)<=0)
				{
					CUpdateSystem::CopyDirFiles($from_dir, $to_dir, $strError_tmp1);
				}

				if (strlen($strError_tmp1) > 0)
				{
					CUpdateSystem::AddMessage2Log("Error loading sources for ".$module_id."!", "CULS");
					$strError_tmp .= $strError_tmp1;
				}
				else
				{
					CUpdateSystem::AddMessage2Log("Sources loaded for ".$module_id."!", "CULS");
				}
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			CUpdateSystem::DeleteDirFilesEx($updates_dir_full);
		}

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CULS");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}


	// Возвращает, что обновилось
	public static function GetAvailableUpdateTypes(&$strError, $lang = false, $stableVersionsOnly = "Y")
	{
		$arResult = array();
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::GetAvailableUpdateTypes");

		if ($lang===false)
			$lang = LANGUAGE_ID;

		$stableVersionsOnly = (($stableVersionsOnly == "N") ? "N" : "Y");

		CUpdateSystem::CheckDirPath($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/", true);

		$arClientModules = CUpdateSystem::GetModules($strError_tmp);
		$arClientLanguages = CUpdateSystem::GetLanguages($strError_tmp);
		$arClientHelps = CUpdateSystem::GetHelps($strError_tmp);

		if (strlen($strError_tmp)<=0)
		{
			$GLOBALS["DB"]->GetVersion();

			$strVars = "LICENSE_KEY=".urlencode(md5(CUpdateSystem::GetLicenseKey())).
				"&lang=".urlencode($lang).
				"&UTYPES=".urlencode("MLH").
				"&SUPD_VER=".urlencode(UPDATE_SYSTEM_VERSION).
				"&ALL_MODULES=".urlencode("Y").
				"&TYPENC=".((defined("DEMO") && DEMO=="Y") ? "D" : ((defined("ENCODE") && ENCODE=="Y") ? "E" : "F" )).
				"&COUNT_ONLY=".urlencode("Y").
				"&SUPD_STS=".urlencode(CUpdateSystem::GetFooPath("GetList")).
				"&SUPD_DBS=".urlencode($GLOBALS["DB"]->type).
				"&XE=".urlencode(($GLOBALS["DB"]->XE) ? "Y" : "N").
				"&CLIENT_SITE=".urlencode($_SERVER["SERVER_NAME"]).
				"&CANGZIP=".urlencode((CUpdateSystem::IsGzipInstalled()) ? "Y" : "N").
				"&CLIENT_PHPVER=".urlencode(phpversion()).
				"&stable=".urlencode($stableVersionsOnly).
				"&".CUpdateSystem::ModulesArray2Query($arClientModules, "bitm_").
				"&".CUpdateSystem::ModulesArray2Query($arClientLanguages, "bitl_").
				"&".CUpdateSystem::ModulesArray2Query($arClientHelps, "bith_");

			CUpdateSystem::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strVars));

			$stime = CUpdateSystem::getmicrotime();
			$content = CUpdateSystem::getHTTPPage("bit_sysserver.php", $strVars, $strError_tmp);
			CUpdateSystem::AddMessage2Log("TIME GetAvailableUpdateTypes.getHTTPPage ".Round(CUpdateSystem::getmicrotime()-$stime, 3)." sec");
		}

		if (strlen($strError_tmp)<=0)
		{
			$arRes = Array();
			CUpdateSystem::ParseServerData($content, $arRes, $strError_tmp);
		}

		if (strlen($strError_tmp)<=0)
		{
			if (isset($arRes["DATA"]["#"]["UPDATE_SYSTEM"])
				&& is_array($arRes["DATA"]["#"]["UPDATE_SYSTEM"])
				&& count($arRes["DATA"]["#"]["UPDATE_SYSTEM"])>0)
			{
				$arResult["UPDATE_SYSTEM"] = $arRes["DATA"]["#"]["UPDATE_SYSTEM"][0];
			}

			if (isset($arRes["DATA"]["#"]["MODULES"])
				&& is_array($arRes["DATA"]["#"]["MODULES"])
				&& count($arRes["DATA"]["#"]["MODULES"])>0)
			{
				$arResult["MODULES"] = $arRes["DATA"]["#"]["MODULES"][0];
			}

			if (isset($arRes["DATA"]["#"]["LANGS"])
				&& is_array($arRes["DATA"]["#"]["LANGS"])
				&& count($arRes["DATA"]["#"]["LANGS"])>0)
			{
				$arResult["LANGS"] = $arRes["DATA"]["#"]["LANGS"][0];
			}

			if (isset($arRes["DATA"]["#"]["HELPS"])
				&& is_array($arRes["DATA"]["#"]["HELPS"])
				&& count($arRes["DATA"]["#"]["HELPS"])>0)
			{
				$arResult["HELPS"] = $arRes["DATA"]["#"]["HELPS"][0];
			}

			if (isset($arRes["DATA"]["#"]["ERROR"])
				&& is_array($arRes["DATA"]["#"]["ERROR"])
				&& count($arRes["DATA"]["#"]["ERROR"])>0)
			{
				$arResult["ERROR"] = $arRes["DATA"]["#"]["ERROR"];
			}

			if (isset($arRes["DATA"]["#"]["CLIENT"])
				&& is_array($arRes["DATA"]["#"]["CLIENT"])
				&& count($arRes["DATA"]["#"]["CLIENT"])>0)
			{
				$arResult["CLIENT"] = $arRes["DATA"]["#"]["CLIENT"][0];
			}

			if (isset($arRes["DATA"]["#"]["SYSTEM"])
				&& is_array($arRes["DATA"]["#"]["SYSTEM"])
				&& count($arRes["DATA"]["#"]["SYSTEM"])>0)
			{
				$arResult["SYSTEM"] = $arRes["DATA"]["#"]["SYSTEM"][0];
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			if ((!isset($arResult["CLIENT"]) || !is_array($arResult["CLIENT"]))
				&& (!isset($arResult["ERROR"]) || !is_array($arResult["ERROR"])))
			{
				$strError_tmp .= "[UGAUT01] ".GetMessage("SUPP_GAUT_SYSERR").".<br>";
			}
		}

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUGAUT");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return $arResult;
	}

	// Возвращает информацию по доступным обновлениям модулей на сервере
	public static function GetServerModuleUpdates(&$strError, $lang = false, $stableVersionsOnly = "Y")
	{
		$arResult = array();
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::GetServerModuleUpdates");

		if ($lang===false)
			$lang = LANGUAGE_ID;

		$stableVersionsOnly = (($stableVersionsOnly == "N") ? "N" : "Y");

		$arClientModules = CUpdateSystem::GetModules($strError_tmp);

		if (strlen($strError_tmp)<=0)
		{
			$GLOBALS["DB"]->GetVersion();

			$strVars = "LICENSE_KEY=".urlencode(md5(CUpdateSystem::GetLicenseKey())).
				"&lang=".urlencode($lang).
				"&UTYPES=".urlencode("M").
				"&SUPD_VER=".urlencode(UPDATE_SYSTEM_VERSION).
				"&ALL_MODULES=".urlencode("Y").
				"&TYPENC=".((defined("DEMO") && DEMO=="Y") ? "D" : ((defined("ENCODE") && ENCODE=="Y") ? "E" : "F" )).
				"&COUNT_ONLY=".urlencode("N").
				"&SUPD_STS=".urlencode(CUpdateSystem::GetFooPath("GetList")).
				"&CLIENT_SITE=".urlencode($_SERVER["SERVER_NAME"]).
				"&SUPD_DBS=".urlencode($GLOBALS["DB"]->type).
				"&XE=".urlencode(($GLOBALS["DB"]->XE) ? "Y" : "N").
				"&CANGZIP=".urlencode((CUpdateSystem::IsGzipInstalled()) ? "Y" : "N").
				"&CLIENT_PHPVER=".urlencode(phpversion()).
				"&stable=".urlencode($stableVersionsOnly).
				"&".CUpdateSystem::ModulesArray2Query($arClientModules, "bitm_");

			CUpdateSystem::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strVars));

			$stime = CUpdateSystem::getmicrotime();
			$content = CUpdateSystem::getHTTPPage("bit_sysserver.php", $strVars, $strError_tmp);
			CUpdateSystem::AddMessage2Log("TIME GetServerModuleUpdates.getHTTPPage ".Round(CUpdateSystem::getmicrotime()-$stime, 3)." sec");
		}

		if (strlen($strError_tmp)<=0)
		{
			$arRes = Array();
			CUpdateSystem::ParseServerData($content, $arRes, $strError_tmp);
		}

		if (strlen($strError_tmp)<=0)
		{
			if (isset($arRes["DATA"]["#"]["UPDATE_SYSTEM"])
				&& is_array($arRes["DATA"]["#"]["UPDATE_SYSTEM"])
				&& count($arRes["DATA"]["#"]["UPDATE_SYSTEM"])>0)
			{
				$arResult["UPDATE_SYSTEM"] = $arRes["DATA"]["#"]["UPDATE_SYSTEM"][0];
			}

			if (isset($arRes["DATA"]["#"]["MODULES"])
				&& is_array($arRes["DATA"]["#"]["MODULES"])
				&& count($arRes["DATA"]["#"]["MODULES"])>0)
			{
				$arResult["MODULES"] = $arRes["DATA"]["#"]["MODULES"][0];
			}

			if (isset($arRes["DATA"]["#"]["ERROR"])
				&& is_array($arRes["DATA"]["#"]["ERROR"])
				&& count($arRes["DATA"]["#"]["ERROR"])>0)
			{
				$arResult["ERROR"] = $arRes["DATA"]["#"]["ERROR"];
			}

			if (isset($arRes["DATA"]["#"]["CLIENT"])
				&& is_array($arRes["DATA"]["#"]["CLIENT"])
				&& count($arRes["DATA"]["#"]["CLIENT"])>0)
			{
				$arResult["CLIENT"] = $arRes["DATA"]["#"]["CLIENT"][0];
			}

			if (isset($arRes["DATA"]["#"]["SYSTEM"])
				&& is_array($arRes["DATA"]["#"]["SYSTEM"])
				&& count($arRes["DATA"]["#"]["SYSTEM"])>0)
			{
				$arResult["SYSTEM"] = $arRes["DATA"]["#"]["SYSTEM"][0];
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			if ((!isset($arResult["CLIENT"]) || !is_array($arResult["CLIENT"]))
				&& (!isset($arResult["ERROR"]) || !is_array($arResult["ERROR"])))
			{
				$strError_tmp .= "[UGSMU01] ".GetMessage("SUPP_GAUT_SYSERR").".<br>";
			}
		}

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUGSMU");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return $arResult;
	}


	// Возвращает информацию по доступным языкам на сервере
	public static function GetServerLangsUpdates(&$strError, $lang = false, $stableVersionsOnly = "Y")
	{
		$arResult = array();
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::GetServerLangsUpdates");

		$stableVersionsOnly = (($stableVersionsOnly == "N") ? "N" : "Y");

		if ($lang===false)
			$lang = LANGUAGE_ID;

		$arClientLanguages = CUpdateSystem::GetLanguages($strError_tmp);

		if (strlen($strError_tmp)<=0)
		{
			$GLOBALS["DB"]->GetVersion();

			$strVars = "LICENSE_KEY=".urlencode(md5(CUpdateSystem::GetLicenseKey())).
				"&lang=".urlencode($lang).
				"&UTYPES=".urlencode("L").
				"&SUPD_VER=".urlencode(UPDATE_SYSTEM_VERSION).
				"&ALL_MODULES=".urlencode("Y").
				"&COUNT_ONLY=".urlencode("N").
				"&CLIENT_SITE=".urlencode($_SERVER["SERVER_NAME"]).
				"&SUPD_STS=".urlencode(CUpdateSystem::GetFooPath("GetList")).
				"&CANGZIP=".urlencode((CUpdateSystem::IsGzipInstalled()) ? "Y" : "N").
				"&SUPD_DBS=".urlencode($GLOBALS["DB"]->type).
				"&XE=".urlencode(($GLOBALS["DB"]->XE) ? "Y" : "N").
				"&CLIENT_PHPVER=".urlencode(phpversion()).
				"&stable=".urlencode($stableVersionsOnly).
				"&".CUpdateSystem::ModulesArray2Query($arClientLanguages, "bitl_");

			CUpdateSystem::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strVars));

			$stime = CUpdateSystem::getmicrotime();
			$content = CUpdateSystem::getHTTPPage("bit_sysserver.php", $strVars, $strError_tmp);
			CUpdateSystem::AddMessage2Log("TIME GetServerLangsUpdates.getHTTPPage ".Round(CUpdateSystem::getmicrotime()-$stime, 3)." sec");
		}

		if (strlen($strError_tmp)<=0)
		{
			$arRes = Array();
			CUpdateSystem::ParseServerData($content, $arRes, $strError_tmp);
		}

		if (strlen($strError_tmp)<=0)
		{
			if (isset($arRes["DATA"]["#"]["UPDATE_SYSTEM"])
				&& is_array($arRes["DATA"]["#"]["UPDATE_SYSTEM"])
				&& count($arRes["DATA"]["#"]["UPDATE_SYSTEM"])>0)
			{
				$arResult["UPDATE_SYSTEM"] = $arRes["DATA"]["#"]["UPDATE_SYSTEM"][0];
			}

			if (isset($arRes["DATA"]["#"]["LANGS"])
				&& is_array($arRes["DATA"]["#"]["LANGS"])
				&& count($arRes["DATA"]["#"]["LANGS"])>0)
			{
				$arResult["LANGS"] = $arRes["DATA"]["#"]["LANGS"][0];
			}

			if (isset($arRes["DATA"]["#"]["ERROR"])
				&& is_array($arRes["DATA"]["#"]["ERROR"])
				&& count($arRes["DATA"]["#"]["ERROR"])>0)
			{
				$arResult["ERROR"] = $arRes["DATA"]["#"]["ERROR"];
			}

			if (isset($arRes["DATA"]["#"]["CLIENT"])
				&& is_array($arRes["DATA"]["#"]["CLIENT"])
				&& count($arRes["DATA"]["#"]["CLIENT"])>0)
			{
				$arResult["CLIENT"] = $arRes["DATA"]["#"]["CLIENT"][0];
			}

			if (isset($arRes["DATA"]["#"]["SYSTEM"])
				&& is_array($arRes["DATA"]["#"]["SYSTEM"])
				&& count($arRes["DATA"]["#"]["SYSTEM"])>0)
			{
				$arResult["SYSTEM"] = $arRes["DATA"]["#"]["SYSTEM"][0];
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			if ((!isset($arResult["CLIENT"]) || !is_array($arResult["CLIENT"]))
				&& (!isset($arResult["ERROR"]) || !is_array($arResult["ERROR"])))
			{
				$strError_tmp .= "[UGSLU01] ".GetMessage("SUPP_GAUT_SYSERR").".<br>";
			}
		}

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUGSLU");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return $arResult;
	}


	// Возвращает информацию по доступным языкам на сервере
	public static function GetServerHelpUpdates(&$strError, $lang = false, $stableVersionsOnly = "Y")
	{
		$arResult = array();
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::GetServerHelpUpdates");

		$stableVersionsOnly = (($stableVersionsOnly == "N") ? "N" : "Y");

		if ($lang===false)
			$lang = LANGUAGE_ID;

		$arClientHelps = CUpdateSystem::GetHelps($strError_tmp);

		if (strlen($strError_tmp)<=0)
		{
			$GLOBALS["DB"]->GetVersion();

			$strVars = "LICENSE_KEY=".urlencode(md5(CUpdateSystem::GetLicenseKey())).
				"&lang=".urlencode($lang).
				"&UTYPES=".urlencode("H").
				"&SUPD_VER=".urlencode(UPDATE_SYSTEM_VERSION).
				"&ALL_MODULES=".urlencode("Y").
				"&COUNT_ONLY=".urlencode("N").
				"&CLIENT_SITE=".urlencode($_SERVER["SERVER_NAME"]).
				"&SUPD_STS=".urlencode(CUpdateSystem::GetFooPath("GetList")).
				"&CANGZIP=".urlencode((CUpdateSystem::IsGzipInstalled()) ? "Y" : "N").
				"&SUPD_DBS=".urlencode($GLOBALS["DB"]->type).
				"&XE=".urlencode(($GLOBALS["DB"]->XE) ? "Y" : "N").
				"&CLIENT_PHPVER=".urlencode(phpversion()).
				"&stable=".urlencode($stableVersionsOnly).
				"&".CUpdateSystem::ModulesArray2Query($arClientHelps, "bith_");

			CUpdateSystem::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strVars));

			$stime = CUpdateSystem::getmicrotime();
			$content = CUpdateSystem::getHTTPPage("bit_sysserver.php", $strVars, $strError_tmp);
			CUpdateSystem::AddMessage2Log("TIME GetServerHelpUpdates.getHTTPPage ".Round(CUpdateSystem::getmicrotime()-$stime, 3)." sec");
		}

		if (strlen($strError_tmp)<=0)
		{
			$arRes = Array();
			CUpdateSystem::ParseServerData($content, $arRes, $strError_tmp);
		}

		if (strlen($strError_tmp)<=0)
		{
			if (isset($arRes["DATA"]["#"]["UPDATE_SYSTEM"])
				&& is_array($arRes["DATA"]["#"]["UPDATE_SYSTEM"])
				&& count($arRes["DATA"]["#"]["UPDATE_SYSTEM"])>0)
			{
				$arResult["UPDATE_SYSTEM"] = $arRes["DATA"]["#"]["UPDATE_SYSTEM"][0];
			}

			if (isset($arRes["DATA"]["#"]["HELPS"])
				&& is_array($arRes["DATA"]["#"]["HELPS"])
				&& count($arRes["DATA"]["#"]["HELPS"])>0)
			{
				$arResult["HELPS"] = $arRes["DATA"]["#"]["HELPS"][0];
			}

			if (isset($arRes["DATA"]["#"]["ERROR"])
				&& is_array($arRes["DATA"]["#"]["ERROR"])
				&& count($arRes["DATA"]["#"]["ERROR"])>0)
			{
				$arResult["ERROR"] = $arRes["DATA"]["#"]["ERROR"];
			}

			if (isset($arRes["DATA"]["#"]["CLIENT"])
				&& is_array($arRes["DATA"]["#"]["CLIENT"])
				&& count($arRes["DATA"]["#"]["CLIENT"])>0)
			{
				$arResult["CLIENT"] = $arRes["DATA"]["#"]["CLIENT"][0];
			}

			if (isset($arRes["DATA"]["#"]["SYSTEM"])
				&& is_array($arRes["DATA"]["#"]["SYSTEM"])
				&& count($arRes["DATA"]["#"]["SYSTEM"])>0)
			{
				$arResult["SYSTEM"] = $arRes["DATA"]["#"]["SYSTEM"][0];
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			if ((!isset($arResult["CLIENT"]) || !is_array($arResult["CLIENT"]))
				&& (!isset($arResult["ERROR"]) || !is_array($arResult["ERROR"])))
			{
				$strError_tmp .= "[UGSHU01] ".GetMessage("SUPP_GAUT_SYSERR").".<br>";
			}
		}

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUGSHU");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return $arResult;
	}


	// Загружает обновление модулей $arModules в файл update_archive.gz
	public static function LoadModuleUpdates($arModules, &$strError, $lang = false, $stableVersionsOnly = "Y")
	{
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::LoadModuleUpdates");

		if ($lang===false)
			$lang = LANGUAGE_ID;

		$stableVersionsOnly = (($stableVersionsOnly == "N") ? "N" : "Y");

		if (!is_array($arModules))
		{
			$strError_tmp .= "[ULMU01] ".GetMessage("SUPP_LMU_NO_MODS").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$arClientModules = CUpdateSystem::GetModules($strError_tmp, $arModules);
			for ($i = 0, $n = count($arModules); $i < $n; $i++)
			{
				if (!array_key_exists($arModules[$i], $arClientModules))
					$arClientModules[$arModules[$i]] = "";
			}
			if (!is_array($arClientModules) || count($arClientModules)<=0)
			{
				$strError_tmp .= "[ULMU02] ".GetMessage("SUPP_LMU_NO_MODS").".<br>";
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			$GLOBALS["DB"]->GetVersion();

			$strVars = "LICENSE_KEY=".urlencode(md5(CUpdateSystem::GetLicenseKey())).
				"&lang=".urlencode($lang).
				"&UTYPES=".urlencode("M").
				"&ALL_MODULES=".urlencode("N").
				"&TYPENC=".((defined("DEMO") && DEMO=="Y") ? "D" : ((defined("ENCODE") && ENCODE=="Y") ? "E" : "F" )).
				"&SUPD_VER=".urlencode(UPDATE_SYSTEM_VERSION).
				"&COUNT_ONLY=".urlencode("F").
				"&CLIENT_SITE=".urlencode($_SERVER["SERVER_NAME"]).
				"&SUPD_STS=".urlencode(CUpdateSystem::GetFooPath("GetList")).
				"&CANGZIP=".urlencode((CUpdateSystem::IsGzipInstalled()) ? "Y" : "N").
				"&CLIENT_PHPVER=".urlencode(phpversion()).
				"&SUPD_DBS=".urlencode($GLOBALS["DB"]->type).
				"&XE=".urlencode(($GLOBALS["DB"]->XE) ? "Y" : "N").
				"&stable=".urlencode($stableVersionsOnly).
				"&".CUpdateSystem::ModulesArray2Query($arClientModules, "bitm_");

			CUpdateSystem::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strVars));

			$stime = CUpdateSystem::getmicrotime();
			$content = CUpdateSystem::getHTTPPage("bit_sysserver.php", $strVars, $strError_tmp);
			CUpdateSystem::AddMessage2Log("TIME LoadModuleUpdates.getHTTPPage ".Round(CUpdateSystem::getmicrotime()-$stime, 3)." sec");

			if (strlen($content)<=0)
				$strError_tmp .= "[ULMU03] ".GetMessage("SUPP_AS_EMPTY_RESP").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!($fp1 = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz", "wb")))
			{
				$strError_tmp .= "[ULMU04] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates", GetMessage("SUPP_RV_ER_TEMP_FILE")).".<br>";
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			fwrite($fp1, $content);
			fclose($fp1);

			if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz")
				|| !is_file($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz"))
			{
				$strError_tmp .= "[ULMU05] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz", GetMessage("SUPP_LMU_NO_TMP_FILE")).".<br>";
			}
		}

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CULMU");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}


	// Загружает обновления языков $arLangs в файл update_archive.gz
	public static function LoadLangsUpdates($arLangs, &$strError, $lang = false, $stableVersionsOnly = "Y")
	{
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::LoadLangsUpdates");

		$stableVersionsOnly = (($stableVersionsOnly == "N") ? "N" : "Y");

		if ($lang===false)
			$lang = LANGUAGE_ID;

		if (!is_array($arLangs) || count($arLangs)<=0)
		{
			$strError_tmp .= "[ULLU01] ".GetMessage("SUPP_LLU_NO_LANGS").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$arClientLangs = CUpdateSystem::GetLanguages($strError_tmp, $arLangs);
			for ($i = 0, $n = count($arLangs); $i < $n; $i++)
			{
				if (!array_key_exists($arLangs[$i], $arClientLangs))
					$arClientLangs[$arLangs[$i]] = "";
			}
			if (!is_array($arClientLangs) || count($arClientLangs)<=0)
			{
				$strError_tmp .= "[ULLU02] ".GetMessage("SUPP_LLU_NO_LANGS").".<br>";
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			$GLOBALS["DB"]->GetVersion();

			$strVars = "LICENSE_KEY=".urlencode(md5(CUpdateSystem::GetLicenseKey())).
				"&lang=".urlencode($lang).
				"&UTYPES=".urlencode("L").
				"&ALL_MODULES=".urlencode("N").
				"&SUPD_VER=".urlencode(UPDATE_SYSTEM_VERSION).
				"&SUPD_STS=".urlencode(CUpdateSystem::GetFooPath("GetList")).
				"&COUNT_ONLY=".urlencode("F").
				"&CLIENT_SITE=".urlencode($_SERVER["SERVER_NAME"]).
				"&CANGZIP=".urlencode((CUpdateSystem::IsGzipInstalled()) ? "Y" : "N").
				"&CLIENT_PHPVER=".urlencode(phpversion()).
				"&SUPD_DBS=".urlencode($GLOBALS["DB"]->type).
				"&XE=".urlencode(($GLOBALS["DB"]->XE) ? "Y" : "N").
				"&stable=".urlencode($stableVersionsOnly).
				"&".CUpdateSystem::ModulesArray2Query($arClientLangs, "bitl_");

			CUpdateSystem::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strVars));

			$stime = CUpdateSystem::getmicrotime();
			$content = CUpdateSystem::getHTTPPage("bit_sysserver.php", $strVars, $strError_tmp);
			CUpdateSystem::AddMessage2Log("TIME LoadLangsUpdates.getHTTPPage ".Round(CUpdateSystem::getmicrotime()-$stime, 3)." sec");

			if (strlen($content)<=0)
				$strError_tmp .= "[ULLU03] ".GetMessage("SUPP_AS_EMPTY_RESP").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!($fp1 = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz", "wb")))
			{
				$strError_tmp .= "[ULLU04] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates", GetMessage("SUPP_RV_ER_TEMP_FILE")).".<br>";
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			fwrite($fp1, $content);
			fclose($fp1);

			if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz")
				|| !is_file($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz"))
			{
				$strError_tmp .= "[ULLU05] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz", GetMessage("SUPP_LMU_NO_TMP_FILE")).".<br>";
			}
		}

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CULLU");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}


	// Загружает обновление помощи $load_help в файл update_archive.gz
	public static function LoadHelpUpdates($arHelp, &$strError, $lang = false, $stableVersionsOnly = "Y")
	{
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::LoadHelpUpdates");

		if ($lang===false)
			$lang = LANGUAGE_ID;

		$stableVersionsOnly = (($stableVersionsOnly == "N") ? "N" : "Y");

		if (!is_array($arHelp) || count($arHelp)<=0)
		{
			$strError_tmp .= "[ULHU01] ".GetMessage("SUPP_LHU_NO_HELP").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$arClientHelps = CUpdateSystem::GetHelps($strError_tmp, $arHelp);
			for ($i = 0, $n = count($arHelp); $i < $n; $i++)
			{
				if (!array_key_exists($arHelp[$i], $arClientHelps))
					$arClientHelps[$arHelp[$i]] = "";
			}

			if (!is_array($arClientHelps) || count($arClientHelps)<=0)
			{
				$strError_tmp .= "[ULHU02] ".GetMessage("SUPP_LHU_NO_HELP").".<br>";
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			$GLOBALS["DB"]->GetVersion();

			$strVars = "LICENSE_KEY=".urlencode(md5(CUpdateSystem::GetLicenseKey())).
				"&lang=".urlencode($lang).
				"&UTYPES=".urlencode("H").
				"&ALL_MODULES=".urlencode("N").
				"&SUPD_VER=".urlencode(UPDATE_SYSTEM_VERSION).
				"&SUPD_STS=".urlencode(CUpdateSystem::GetFooPath("GetList")).
				"&COUNT_ONLY=".urlencode("F").
				"&CLIENT_SITE=".urlencode($_SERVER["SERVER_NAME"]).
				"&CANGZIP=".urlencode((CUpdateSystem::IsGzipInstalled()) ? "Y" : "N").
				"&CLIENT_PHPVER=".urlencode(phpversion()).
				"&SUPD_DBS=".urlencode($GLOBALS["DB"]->type).
				"&XE=".urlencode(($GLOBALS["DB"]->XE) ? "Y" : "N").
				"&stable=".urlencode($stableVersionsOnly).
				"&".CUpdateSystem::ModulesArray2Query($arClientHelps, "bith_");

			CUpdateSystem::AddMessage2Log(preg_replace("/LICENSE_KEY=[^&]*/i", "LICENSE_KEY=X", $strVars));

			$stime = CUpdateSystem::getmicrotime();
			$content = CUpdateSystem::getHTTPPage("bit_sysserver.php", $strVars, $strError_tmp);
			CUpdateSystem::AddMessage2Log("TIME LoadHelpUpdates.getHTTPPage ".Round(CUpdateSystem::getmicrotime()-$stime, 3)." sec");

			if (strlen($content)<=0)
				$strError_tmp .= "[ULHU03] ".GetMessage("SUPP_AS_EMPTY_RESP").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!($fp1 = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz", "wb")))
				$strError_tmp .= "[ULHU04] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates", GetMessage("SUPP_RV_ER_TEMP_FILE")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			fwrite($fp1, $content);
			fclose($fp1);

			if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz")
				|| !is_file($_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz"))
			{
				$strError_tmp .= "[ULHU05] ".str_replace("#FILE#", $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz", GetMessage("SUPP_LMU_NO_TMP_FILE")).".<br>";
			}
		}

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CULHU");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}

	// Распаковывает архив файлов update_archive.gz в папкy $updates_dir
	public static function UnGzipArchive(&$updates_dir, &$strError, $DelArch = "Y")
	{
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::UnGzipArchive");
		$stime = CUpdateSystem::getmicrotime();

		if ($DelArch!="Y")
			$DelArch = "N";

		$archiveFileName = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/update_archive.gz";

		if (!file_exists($archiveFileName) || !is_file($archiveFileName))
		{
			$strError_tmp .= "[UUGZA01] ".str_replace("#FILE#", $archiveFileName, GetMessage("SUPP_UGA_NO_TMP_FILE")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!is_readable($archiveFileName))
				$strError_tmp .= "[UUGZA02] ".str_replace("#FILE#", $archiveFileName, GetMessage("SUPP_UGA_NO_READ_FILE")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$updates_dir = "update_m".time();
			$updates_dir_full = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$updates_dir;
			CUpdateSystem::CheckDirPath($updates_dir_full."/", true);

			if (!file_exists($updates_dir_full) || !is_dir($updates_dir_full))
			{
				$strError_tmp .= "[UUGZA03] ".str_replace("#FILE#", $updates_dir_full, GetMessage("SUPP_UGA_NO_TMP_CAT")).".<br>";
			}
			elseif (!is_writable($updates_dir_full))
			{
				$strError_tmp .= "[UUGZA04] ".str_replace("#FILE#", $updates_dir_full, GetMessage("SUPP_UGA_WRT_TMP_CAT")).".<br>";
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			$bCompressionUsed = True;

			$fd = fopen($archiveFileName, "rb");
			$flabel = fread($fd, strlen("BITRIX"));
			fclose($fd);

			if ($flabel=="BITRIX")
				$bCompressionUsed = False;
		}

		if (strlen($strError_tmp)<=0)
		{
			if ($bCompressionUsed)
				$zp = gzopen($archiveFileName, "rb9f");
			else
				$zp = fopen($archiveFileName, "rb");

			if (!$zp)
			{
				$strError_tmp .= "[UUGZA05] ".str_replace("#FILE#", $archiveFileName, GetMessage("SUPP_UGA_CANT_OPEN")).".<br>";
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			if ($bCompressionUsed)
				$flabel = gzread($zp, strlen("BITRIX"));
			else
				$flabel = fread($zp, strlen("BITRIX"));

			if ($flabel!="BITRIX")
			{
				$strError_tmp .= "[UUGZA06] ".str_replace("#FILE#", $archiveFileName, GetMessage("SUPP_UGA_BAD_FORMAT")).".<br>";

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
				if (IntVal($add_info_size)>0
					&& IntVal($add_info_size)."!"==$add_info_size."!")
				{
					$add_info_size = IntVal($add_info_size);
				}
				else
				{
					if ($add_info_size!="RTIBE")
						$strError_tmp .= "[UUGZA071] ".str_replace("#FILE#", $archiveFileName, GetMessage("SUPP_UGA_BAD_FORMAT")).".<br>";

					break;
				}

				if ($bCompressionUsed)
					$add_info = gzread($zp, $add_info_size);
				else
					$add_info = fread($zp, $add_info_size);

				$add_info_arr = explode("|", $add_info);
				if (count($add_info_arr) != 3)
				{
					$strError_tmp .= "[UUGZA072] ".str_replace("#FILE#", $archiveFileName, GetMessage("SUPP_UGA_BAD_FORMAT")).".<br>";
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

				if ($crc32_new !== $crc32)
				{
					$strError_tmp .= "[UUGZA073] ".str_replace("#FILE#", $curpath, GetMessage("SUPP_UGA_FILE_CRUSH")).".<br>";
					break;
				}
				else
				{
					CUpdateSystem::CheckDirPath($updates_dir_full.$curpath, true);

					if (!($fp1 = fopen($updates_dir_full.$curpath, "wb")))
					{
						$strError_tmp .= "[UUGZA074] ".str_replace("#FILE#", $updates_dir_full.$curpath, GetMessage("SUPP_UGA_CANT_OPEN_WR")).".<br>";
						break;
					}

					if (strlen($contents)>0 && !fwrite($fp1, $contents))
					{
						$strError_tmp .= "[UUGZA075] ".str_replace("#FILE#", $updates_dir_full.$curpath, GetMessage("SUPP_UGA_CANT_WRITE_F")).".<br>";
						@fclose($fp1);
						break;
					}
					fclose($fp1);

					if ($strongUpdateCheck == "Y")
					{
						$crc32_new = dechex(crc32(file_get_contents($updates_dir_full.$curpath)));
						if ($crc32_new !== $crc32)
						{
							$strError_tmp .= "[UUGZA0761] ".str_replace("#FILE#", $curpath, GetMessage("SUPP_UGA_FILE_CRUSH")).".<br>";
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
			if ($DelArch=="Y")
				@unlink($archiveFileName);
		}

		CUpdateSystem::AddMessage2Log("TIME UnGzipArchive ".Round(CUpdateSystem::getmicrotime()-$stime, 3)." sec");

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUUGZA");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}


	// Проверяет возможность обновления модулей $arModules
	// на основании контроля версий VERSION_CONTROL
	public static function CheckVersions(&$arRes, &$strError, $arSelectedModules = false)
	{
		$strError_tmp = "";

		$stime = CUpdateSystem::getmicrotime();

		if (!is_array($arRes)
			|| !isset($arRes["MODULES"])
			|| !is_array($arRes["MODULES"])
			)
		{
			$strError_tmp .= "[UCV01] ".GetMessage("SUPP_CV_ERR_ARR").".<br>";
		}

		if ($arSelectedModules!==false && (!is_array($arSelectedModules) || count($arSelectedModules)<=0))
		{
			$strError_tmp .= "[UCV011] ".GetMessage("SUPP_CV_NO_SELECTED").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$arModulesList = $arRes["MODULES"]["#"]["MODULE"];

			if (!is_array($arModulesList) || count($arModulesList)<=0)
			{
				$strError_tmp .= "[UCV02] ".GetMessage("SUPP_CV_EMPTY_MODS").".<br>";
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			for ($i = 0, $ni = count($arModulesList); $i < $ni; $i++)
			{
				if ($arSelectedModules===false || in_array($arModulesList[$i]["@"]["ID"], $arSelectedModules))
				{
					if (isset($arModulesList[$i]["#"]["VERSION_CONTROL"])
						&& count($arModulesList[$i]["#"]["VERSION_CONTROL"])>0)
					{
						for ($j = 0, $nj = count($arModulesList[$i]["#"]["VERSION_CONTROL"]); $j < $nj; $j++)
						{
							$strModule_tmp = $arModulesList[$i]["#"]["VERSION_CONTROL"][$j]["@"]["MODUL"];
							$bNeedInstall = True;

							if ($bNeedInstall)
							{
								for ($k = 0, $nk = count($arModulesList); $k < $nk; $k++)
								{
									if ($arModulesList[$k]["@"]["ID"]==$strModule_tmp
										&& ($arSelectedModules===false
											|| in_array($arModulesList[$k]["@"]["ID"], $arSelectedModules)))
									{
										if (CUpdateSystem::CompareVersions(
												$arModulesList[$k]["@"]["VERSION"],
												$arModulesList[$i]["#"]["VERSION_CONTROL"][$j]["@"]["VERSION"]
											) >= 0)
										{
											$bNeedInstall = False;
											break;
										}
									}
								}
							}

							if ($bNeedInstall)
							{
								$strModule_tmp_dir = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$strModule_tmp;
								if (file_exists($strModule_tmp_dir) && is_dir($strModule_tmp_dir))
								{
									if ($strModule_tmp!="main")
									{
										if (file_exists($strModule_tmp_dir."/install/index.php"))
										{
											$arModule_tmp_info = CUpdateSystem::GetModuleInfo($strModule_tmp_dir);
											if (CUpdateSystem::CompareVersions(
													$arModule_tmp_info["VERSION"],
													$arModulesList[$i]["#"]["VERSION_CONTROL"][$j]["@"]["VERSION"]
												) >= 0)
											{
												$bNeedInstall = False;
											}
										}
									}
									else
									{
										if (CUpdateSystem::CompareVersions(
												SM_VERSION,
												$arModulesList[$i]["#"]["VERSION_CONTROL"][$j]["@"]["VERSION"]
											) >= 0)
										{
											$bNeedInstall = False;
										}
									}
								}
								else
								{
									$bNeedInstall = False;
								}
							}

							if ($bNeedInstall)
							{
								$strError_tmp .= "[UCV_MER01] ".str_replace("#VERS#", $arModulesList[$i]["#"]["VERSION_CONTROL"][$j]["@"]["VERSION"], str_replace("#MODULE2#", $strModule_tmp, str_replace("#MODULE1#", $arModulesList[$i]["@"]["ID"], GetMessage("SUPP_CV_RES_ERR")))).".<br>";
							}
						}
					}
				}
			}
		}

		CUpdateSystem::AddMessage2Log("TIME CheckVersions ".Round(CUpdateSystem::getmicrotime()-$stime, 3)." sec");

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUCV");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}


	// Возвращает информацию по загруженным в папку $updates_dir обновлениям модулей
	public static function CheckUpdatability($updates_dir, &$strError)
	{
		$strError_tmp = "";

		$updates_dir_full = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$updates_dir;
		if (!file_exists($updates_dir_full) || !is_dir($updates_dir_full))
		{
			$strError_tmp .= "[UCU01] ".str_replace("#FILE#", $updates_dir_full, GetMessage("SUPP_CU_NO_TMP_CAT")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!is_readable($updates_dir_full))
			{
				$strError_tmp .= "[UCU02] ".str_replace("#FILE#", $updates_dir_full, GetMessage("SUPP_CU_RD_TMP_CAT")).".<br>";
			}
		}

		if ($handle = @opendir($updates_dir_full))
		{
			while (($file = readdir($handle)) !== false)
			{
				if ($file == "." || $file == "..") continue;

				if (is_dir($updates_dir_full."/".$file))
				{
					CUpdateSystem::CheckUpdatability($updates_dir."/".$file, $strError_tmp);
				}
				elseif (is_file($updates_dir_full."/".$file))
				{
					$strRealPath = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".substr($updates_dir."/".$file, strpos($updates_dir."/".$file, "/"));
					if (file_exists($strRealPath))
					{
						if (!is_writeable($strRealPath))
						{
							$strError_tmp .= "[UCU03] ".str_replace("#FILE#", $strRealPath, GetMessage("SUPP_CU_MAIN_ERR_FILE")).".<br>";
						}
					}
					else
					{
						$p = CUpdateSystem::bxstrrpos($strRealPath, "/");
						$strRealPath = substr($strRealPath, 0, $p);

						if (strlen($strRealPath)>1)
							$strRealPath = rtrim($strRealPath, "/");

						$p = CUpdateSystem::bxstrrpos($strRealPath, "/");
						while ($p > 0)
						{
							if (file_exists($strRealPath) && is_dir($strRealPath))
							{
								if (!is_writable($strRealPath))
									$strError_tmp .= "[UCU04] ".str_replace("#FILE#", $strRealPath, GetMessage("SUPP_CU_MAIN_ERR_CAT")).".<br>";

								break;
							}
							$strRealPath = substr($strRealPath, 0, $p);
							$p = CUpdateSystem::bxstrrpos($strRealPath, "/");
						}
					}
				}
			}
			@closedir($handle);
		}

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUCU");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}

	public static function CheckFolderUpdatability($destFolder, $srcFolder, &$strError)
	{
		$strError_tmp = "";

		$destFolder = str_replace("\\", "/", $destFolder);
		$destFolder = Trim($destFolder, " \t\n\r\0\x0B/\\");
		if (strlen($destFolder) > 0)
			$destFolder = "/".$destFolder;

		if ($srcFolder && strlen($srcFolder) > 0)
		{
			$srcFolder = str_replace("\\", "/", $srcFolder);
			$srcFolder = Trim($srcFolder, " \t\n\r\0\x0B/\\");
			if (strlen($srcFolder) > 0)
				$srcFolder = "/".$srcFolder;

			$srcFolderFull = $_SERVER["DOCUMENT_ROOT"].$srcFolder;
			if (!file_exists($srcFolderFull) || !is_dir($srcFolderFull))
				$strError_tmp .= "[UCFU01] ".str_replace("#FILE#", $srcFolderFull, GetMessage("SUPN_NO_FOLDER_EX")).".<br>";

			if (strlen($strError_tmp) <= 0)
			{
				if (!is_readable($srcFolderFull))
					$strError_tmp .= "[UCFU02] ".str_replace("#FILE#", $srcFolderFull, GetMessage("SUPN_CU_RD_TMP_CAT")).".<br>";
			}

			if ($handle = @opendir($srcFolderFull))
			{
				while (($file = readdir($handle)) !== false)
				{
					if ($file == "." || $file == "..")
						continue;

					if (is_dir($srcFolderFull."/".$file))
					{
						CUpdateSystem::CheckFolderUpdatability($destFolder."/".$file, $srcFolder."/".$file, $strError_tmp);
					}
					elseif (is_file($srcFolderFull."/".$file))
					{
						$destFileFull = $_SERVER["DOCUMENT_ROOT"].$destFolder."/".$file;
						if (file_exists($destFileFull))
						{
							if (!is_writeable($destFileFull))
								$strError_tmp .= "[UCFU03] ".str_replace("#FILE#", $destFileFull, GetMessage("SUPP_CU_MAIN_ERR_FILE")).".<br>";
						}
						else
						{
							$p = CUpdateSystem::bxstrrpos($destFileFull, "/");
							$destFileFull = substr($destFileFull, 0, $p);

							while (strlen($destFileFull) > 1 && substr($destFileFull, strlen($destFileFull) - 1, 1) == "/")
								$destFileFull = substr($destFileFull, 0, strlen($destFileFull) - 1);

							$p = CUpdateSystem::bxstrrpos($destFileFull, "/");
							while ($p > 0)
							{
								if (file_exists($destFileFull) && is_dir($destFileFull))
								{
									if (!is_writable($destFileFull))
										$strError_tmp .= "[UCFU04] ".str_replace("#FILE#", $destFileFull, GetMessage("SUPP_CU_MAIN_ERR_CAT")).".<br>";

									break;
								}
								$destFileFull = substr($destFileFull, 0, $p);
								$p = CUpdateSystem::bxstrrpos($destFileFull, "/");
							}
						}
					}
				}
				@closedir($handle);
			}
		}
		else
		{
			$destFolderFull = $_SERVER["DOCUMENT_ROOT"].$destFolder;
			if (!file_exists($destFolderFull) || !is_dir($destFolderFull))
				$strError_tmp .= "[UCFU05] ".str_replace("#FILE#", $destFolderFull, GetMessage("SUPN_NO_FOLDER_EX")).".<br>";

			if (strlen($strError_tmp) <= 0)
			{
				if (!is_writeable($destFolderFull))
					$strError_tmp .= "[UCFU06] ".str_replace("#FILE#", $destFolderFull, GetMessage("SUPP_CU_MAIN_ERR_CAT")).".<br>";
			}

			if ($handle = @opendir($destFolderFull))
			{
				while (($file = readdir($handle)) !== false)
				{
					if ($file == "." || $file == "..")
						continue;

					if (is_dir($destFolderFull."/".$file))
					{
						CUpdateSystem::CheckFolderUpdatability($destFolder."/".$file, "", $strError_tmp);
					}
					elseif (is_file($destFolderFull."/".$file))
					{
						if (!is_writeable($destFolderFull."/".$file))
							$strError_tmp .= "[UCFU07] ".str_replace("#FILE#", $destFolderFull."/".$file, GetMessage("SUPP_CU_MAIN_ERR_FILE")).".<br>";
					}
				}
				@closedir($handle);
			}
		}

		if (strlen($strError_tmp) > 0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUCFU");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}


	// Возвращает информацию по загруженным в папку $updates_dir обновлениям модулей
	public static function GetLoadedModuleUpdates($updates_dir, &$strError)
	{
		$arResult = array();
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::GetLoadedModuleUpdates");

		$updates_dir_full = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$updates_dir;
		if (!file_exists($updates_dir_full) || !is_dir($updates_dir_full))
		{
			$strError_tmp .= "[UGLMU01] ".str_replace("#FILE#", $updates_dir_full, GetMessage("SUPP_CU_NO_TMP_CAT")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!is_readable($updates_dir_full))
				$strError_tmp .= "[UGLMU02] ".str_replace("#FILE#", $updates_dir_full, GetMessage("SUPP_CU_RD_TMP_CAT")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!file_exists($updates_dir_full."/update_info.xml") || !is_file($updates_dir_full."/update_info.xml"))
			{
				$strError_tmp .= "[UGLMU03] ".str_replace("#FILE#", $updates_dir_full."/update_info.xml", GetMessage("SUPP_RV_ER_DESCR_FILE")).".<br>";
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!is_readable($updates_dir_full."/update_info.xml"))
				$strError_tmp .= "[UGLMU04] ".str_replace("#FILE#", $updates_dir_full."/update_info.xml", GetMessage("SUPP_RV_READ_DESCR_FILE")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$content = file_get_contents($updates_dir_full."/update_info.xml");
		}

		if (strlen($strError_tmp)<=0)
		{
			$arRes = Array();
			CUpdateSystem::ParseServerData($content, $arRes, $strError_tmp);
		}

		if (strlen($strError_tmp)<=0)
		{
			if (isset($arRes["DATA"]["#"]["MODULES"])
				&& is_array($arRes["DATA"]["#"]["MODULES"])
				&& count($arRes["DATA"]["#"]["MODULES"])>0)
			{
				$arResult["MODULES"] = $arRes["DATA"]["#"]["MODULES"][0];
			}

			if (isset($arRes["DATA"]["#"]["ERROR"])
				&& is_array($arRes["DATA"]["#"]["ERROR"])
				&& count($arRes["DATA"]["#"]["ERROR"])>0)
			{
				$arResult["ERROR"] = $arRes["DATA"]["#"]["ERROR"];
			}

			if (isset($arRes["DATA"]["#"]["CLIENT"])
				&& is_array($arRes["DATA"]["#"]["CLIENT"])
				&& count($arRes["DATA"]["#"]["CLIENT"])>0)
			{
				$arResult["CLIENT"] = $arRes["DATA"]["#"]["CLIENT"][0];
			}

			if (isset($arRes["DATA"]["#"]["SYSTEM"])
				&& is_array($arRes["DATA"]["#"]["SYSTEM"])
				&& count($arRes["DATA"]["#"]["SYSTEM"])>0)
			{
				$arResult["SYSTEM"] = $arRes["DATA"]["#"]["SYSTEM"][0];
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			if ((!isset($arResult["CLIENT"]) || !is_array($arResult["CLIENT"]))
				&& (!isset($arResult["ERROR"]) || !is_array($arResult["ERROR"])))
			{
				$strError_tmp .= "[UGSMU01] ".GetMessage("SUPP_GAUT_SYSERR").".<br>";
			}
		}

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUGLMU");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return $arResult;
	}


	// Возвращает информацию по загруженным в папку $updates_dir обновлениям языков
	public static function GetLoadedLangsUpdates($updates_dir, &$strError)
	{
		$arResult = array();
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::GetLoadedLangsUpdates");

		$updates_dir_full = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$updates_dir;
		if (!file_exists($updates_dir_full) || !is_dir($updates_dir_full))
		{
			$strError_tmp .= "[UGLLU01] ".str_replace("#FILE#", $updates_dir_full, GetMessage("SUPP_CU_NO_TMP_CAT")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!is_readable($updates_dir_full))
				$strError_tmp .= "[UGLLU02] ".str_replace("#FILE#", $updates_dir_full, GetMessage("SUPP_CU_RD_TMP_CAT")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!file_exists($updates_dir_full."/update_info.xml") || !is_file($updates_dir_full."/update_info.xml"))
			{
				$strError_tmp .= "[UGLLU03] ".str_replace("#FILE#", $updates_dir_full."/update_info.xml", GetMessage("SUPP_RV_ER_DESCR_FILE")).".<br>";
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!is_readable($updates_dir_full."/update_info.xml"))
				$strError_tmp .= "[UGLLU04] ".str_replace("#FILE#", $updates_dir_full."/update_info.xml", GetMessage("SUPP_RV_READ_DESCR_FILE")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$content = file_get_contents($updates_dir_full."/update_info.xml");
		}

		if (strlen($strError_tmp)<=0)
		{
			$arRes = Array();
			CUpdateSystem::ParseServerData($content, $arRes, $strError_tmp);
		}

		if (strlen($strError_tmp)<=0)
		{
			if (isset($arRes["DATA"]["#"]["LANGS"])
				&& is_array($arRes["DATA"]["#"]["LANGS"])
				&& count($arRes["DATA"]["#"]["LANGS"])>0)
			{
				$arResult["LANGS"] = $arRes["DATA"]["#"]["LANGS"][0];
			}

			if (isset($arRes["DATA"]["#"]["ERROR"])
				&& is_array($arRes["DATA"]["#"]["ERROR"])
				&& count($arRes["DATA"]["#"]["ERROR"])>0)
			{
				$arResult["ERROR"] = $arRes["DATA"]["#"]["ERROR"];
			}

			if (isset($arRes["DATA"]["#"]["CLIENT"])
				&& is_array($arRes["DATA"]["#"]["CLIENT"])
				&& count($arRes["DATA"]["#"]["CLIENT"])>0)
			{
				$arResult["CLIENT"] = $arRes["DATA"]["#"]["CLIENT"][0];
			}

			if (isset($arRes["DATA"]["#"]["SYSTEM"])
				&& is_array($arRes["DATA"]["#"]["SYSTEM"])
				&& count($arRes["DATA"]["#"]["SYSTEM"])>0)
			{
				$arResult["SYSTEM"] = $arRes["DATA"]["#"]["SYSTEM"][0];
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			if ((!isset($arResult["CLIENT"]) || !is_array($arResult["CLIENT"]))
				&& (!isset($arResult["ERROR"]) || !is_array($arResult["ERROR"])))
			{
				$strError_tmp .= "[UGSLU01] ".GetMessage("SUPP_GAUT_SYSERR").".<br>";
			}
		}

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUGLLU");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return $arResult;
	}

	// Возвращает информацию по загруженным в папку $updates_dir обновлениям помощи
	public static function GetLoadedHelpUpdates($updates_dir, &$strError)
	{
		$arResult = array();
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::GetLoadedHelpUpdates");

		$updates_dir_full = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$updates_dir;
		if (!file_exists($updates_dir_full) || !is_dir($updates_dir_full))
		{
			$strError_tmp .= "[UGLHU01] ".str_replace("#FILE#", $updates_dir_full, GetMessage("SUPP_CU_NO_TMP_CAT")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!is_readable($updates_dir_full))
				$strError_tmp .= "[UGLHU02] ".str_replace("#FILE#", $updates_dir_full, GetMessage("SUPP_CU_RD_TMP_CAT")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!file_exists($updates_dir_full."/update_info.xml") || !is_file($updates_dir_full."/update_info.xml"))
			{
				$strError_tmp .= "[UGLHU03] ".str_replace("#FILE#", $updates_dir_full."/update_info.xml", GetMessage("SUPP_RV_ER_DESCR_FILE")).".<br>";
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!is_readable($updates_dir_full."/update_info.xml"))
				$strError_tmp .= "[UGLHU04] ".str_replace("#FILE#", $updates_dir_full."/update_info.xml", GetMessage("SUPP_RV_READ_DESCR_FILE")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$content = file_get_contents($updates_dir_full."/update_info.xml");
		}

		if (strlen($strError_tmp)<=0)
		{
			$arRes = Array();
			CUpdateSystem::ParseServerData($content, $arRes, $strError_tmp);
		}

		if (strlen($strError_tmp)<=0)
		{
			if (isset($arRes["DATA"]["#"]["HELPS"])
				&& is_array($arRes["DATA"]["#"]["HELPS"])
				&& count($arRes["DATA"]["#"]["HELPS"])>0)
			{
				$arResult["HELPS"] = $arRes["DATA"]["#"]["HELPS"][0];
			}

			if (isset($arRes["DATA"]["#"]["ERROR"])
				&& is_array($arRes["DATA"]["#"]["ERROR"])
				&& count($arRes["DATA"]["#"]["ERROR"])>0)
			{
				$arResult["ERROR"] = $arRes["DATA"]["#"]["ERROR"];
			}

			if (isset($arRes["DATA"]["#"]["CLIENT"])
				&& is_array($arRes["DATA"]["#"]["CLIENT"])
				&& count($arRes["DATA"]["#"]["CLIENT"])>0)
			{
				$arResult["CLIENT"] = $arRes["DATA"]["#"]["CLIENT"][0];
			}

			if (isset($arRes["DATA"]["#"]["SYSTEM"])
				&& is_array($arRes["DATA"]["#"]["SYSTEM"])
				&& count($arRes["DATA"]["#"]["SYSTEM"])>0)
			{
				$arResult["SYSTEM"] = $arRes["DATA"]["#"]["SYSTEM"][0];
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			if ((!isset($arResult["CLIENT"]) || !is_array($arResult["CLIENT"]))
				&& (!isset($arResult["ERROR"]) || !is_array($arResult["ERROR"])))
			{
				$strError_tmp .= "[UGSHU01] ".GetMessage("SUPP_GAUT_SYSERR").".<br>";
			}
		}

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUGLHU");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return $arResult;
	}


	// Обновляет модули $arModules продукта из папки $updates_dir
	public static function UpdateKernel($updates_dir, $arModules, &$strError, &$arErrorModules, &$arSuccessModules)
	{
		global $DB;
		$strError_tmp = "";

		$stime = CUpdateSystem::getmicrotime();

		$updates_dir_full = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$updates_dir;

		if (!file_exists($updates_dir_full) || !is_dir($updates_dir_full))
		{
			$strError_tmp .= "[UUK01] ".str_replace("#FILE#", $updates_dir_full, GetMessage("SUPP_CU_NO_TMP_CAT")).".<br>";
		}

		if (!is_array($arModules) || count($arModules)<=0)
		{
			$strError_tmp .= "[UUK02] ".GetMessage("SUPP_UK_NO_MODS").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!is_readable($updates_dir_full))
				$strError_tmp .= "[UUK03] ".str_replace("#FILE#", $updates_dir_full, GetMessage("SUPP_CU_RD_TMP_CAT")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			for ($i = 0, $ni = count($arModules); $i < $ni; $i++)
			{
				$strError_tmp1 = "";

				$from_dir = $updates_dir_full."/".$arModules[$i];
				$to_dir = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$arModules[$i];

				CUpdateSystem::CheckDirPath($to_dir."/", true);

				if (!file_exists($to_dir) || !is_dir($to_dir))
				{
					$strError_tmp1 .= "[UUK04] ".str_replace("#MODULE_DIR#", $to_dir, GetMessage("SUPP_UK_NO_MODIR")).".<br>";
					$arErrorModules[$arModules[$i]] = str_replace("#MODULE_DIR#", $to_dir, GetMessage("SUPP_UK_NO_MODIR"))." [UUK04]. ";
				}

				if (strlen($strError_tmp1)<=0)
				{
					if (!is_writable($to_dir))
					{
						$strError_tmp1 .= "[UUK05] ".str_replace("#MODULE_DIR#", $to_dir, GetMessage("SUPP_UK_WR_MODIR")).".<br>";
						$arErrorModules[$arModules[$i]] = str_replace("#MODULE_DIR#", $to_dir, GetMessage("SUPP_UK_WR_MODIR"))." [UUK05]. ";
					}
				}

				if (strlen($strError_tmp1)<=0)
				{
					if (!file_exists($from_dir) || !is_dir($from_dir))
					{
						$strError_tmp1 .= "[UUK06] ".str_replace("#DIR#", $from_dir, GetMessage("SUPP_UK_NO_FDIR")).".<br>";
						$arErrorModules[$arModules[$i]] = str_replace("#DIR#", $from_dir, GetMessage("SUPP_UK_NO_FDIR"))." [UUK06]. ";
					}
				}

				if (strlen($strError_tmp1)<=0)
				{
					if (!is_readable($from_dir))
					{
						$strError_tmp1 .= "[UUK07] ".str_replace("#DIR#", $from_dir, GetMessage("SUPP_UK_READ_FDIR")).".<br>";
						$arErrorModules[$arModules[$i]] = str_replace("#DIR#", $from_dir, GetMessage("SUPP_UK_READ_FDIR"))." [UUK07]. ";
					}
				}

				if (strlen($strError_tmp1)<=0)
				{
					$handle = @opendir($from_dir);
					$arUpdaters = array();
					if ($handle)
					{
						while (false !== ($dir = readdir($handle)))
						{
							if (substr($dir, 0, 7)=="updater")
							{
								$bPostUpdater = "N";
								if (is_file($from_dir."/".$dir))
								{
									$num = substr($dir, 7, strlen($dir)-11);
									if (substr($dir, strlen($dir) - 9) == "_post.php")
									{
										$bPostUpdater = "Y";
										$num = substr($dir, 7, strlen($dir)-16);
									}
									$arUpdaters[] = array("/".$dir, Trim($num), $bPostUpdater);
								}
								elseif (file_exists($from_dir."/".$dir."/index.php"))
								{
									$num = substr($dir, 7);
									if (substr($dir, strlen($dir) - 5) == "_post")
									{
										$bPostUpdater = "Y";
										$num = substr($dir, 7, strlen($dir)-12);
									}
									$arUpdaters[] = array("/".$dir."/index.php", Trim($num), $bPostUpdater);
								}
							}
						}
						closedir($handle);
					}

					for ($i1 = 0, $nij = count($arUpdaters); $i1 < $nij-1; $i1++)
					{
						for ($j1 = $i1 + 1; $j1 < $nij; $j1++)
						{
							if (CUpdateSystem::CompareVersions($arUpdaters[$i1][1], $arUpdaters[$j1][1])>0)
							{
								$tmp1 = $arUpdaters[$i1];
								$arUpdaters[$i1] = $arUpdaters[$j1];
								$arUpdaters[$j1] = $tmp1;
							}
						}
					}
				}

				if (strlen($strError_tmp1)<=0)
				{
					if (strtolower($DB->type)=="mysql" && defined("MYSQL_TABLE_TYPE") && strlen(MYSQL_TABLE_TYPE)>0)
					{
						$DB->Query("SET storage_engine = '".MYSQL_TABLE_TYPE."'", True);
					}
				}

				if (strlen($strError_tmp1) <= 0)
				{
					for ($i1 = 0, $ni1 = count($arUpdaters); $i1 < $ni1; $i1++)
					{
						if ($arUpdaters[$i1][2]=="N")
						{
							$strError_tmp2 = "";
							CUpdateSystem::RunUpdaterScript($from_dir.$arUpdaters[$i1][0], $strError_tmp2, "/bitrix/updates/".$updates_dir."/".$arModules[$i], $arModules[$i]);
							if (strlen($strError_tmp2) > 0)
							{
								$strError_tmp1 .=
										str_replace("#MODULE#", $arModules[$i], str_replace("#VER#", $arUpdaters[$i1][1], GetMessage("SUPP_UK_UPDN_ERR"))).": ".
										$strError_tmp2.".<br>";
								$arErrorModules[$arModules[$i]] =
										str_replace("#MODULE#", $arModules[$i], str_replace("#VER#", $arUpdaters[$i1][1], GetMessage("SUPP_UK_UPDN_ERR"))).": ".
										$strError_tmp2.".<br>";
								$strError_tmp1 .= str_replace("#MODULE#", $arModules[$i], GetMessage("SUPP_UK_UPDN_ERR_BREAK"))." ";
								break;
							}
						}
					}
				}

				if (strlen($strError_tmp1) <= 0)
				{
					CUpdateSystem::CopyDirFiles($from_dir, $to_dir, $strError_tmp1);
				}

				if (strlen($strError_tmp1) <= 0)
				{
					for ($i1 = 0, $ni1 = count($arUpdaters); $i1 < $ni1; $i1++)
					{
						if ($arUpdaters[$i1][2]=="Y")
						{
							$strError_tmp2 = "";
							CUpdateSystem::RunUpdaterScript($from_dir.$arUpdaters[$i1][0], $strError_tmp2, "/bitrix/updates/".$updates_dir."/".$arModules[$i], $arModules[$i]);
							if (strlen($strError_tmp2) > 0)
							{
								$strError_tmp1 .=
										str_replace("#MODULE#", $arModules[$i], str_replace("#VER#", $arUpdaters[$i1][1], GetMessage("SUPP_UK_UPDY_ERR"))).": ".
										$strError_tmp2.".<br>";
								$arErrorModules[$arModules[$i]] =
										str_replace("#MODULE#", $arModules[$i], str_replace("#VER#", $arUpdaters[$i1][1], GetMessage("SUPP_UK_UPDY_ERR"))).": ".
										$strError_tmp2.".<br>";
								$strError_tmp1 .= str_replace("#MODULE#", $arModules[$i], GetMessage("SUPP_UK_UPDN_ERR_BREAK"))." ";
								break;
							}
						}
					}
				}

				if (strlen($strError_tmp1)>0)
				{
					$strError_tmp .= $strError_tmp1;
					$arErrorModules[$arModules[$i]] .= str_replace("#MODULE#", $arModules[$i], GetMessage("SUPP_UK_UPDN_ERR_BREAK1"))." ";
				}
				else
				{
					$arSuccessModules[$arModules[$i]] = "Y";
				}
			}
			CUpdateSystem::DeleteDirFilesEx($updates_dir_full);
		}

		CUpdateSystem::AddMessage2Log("TIME UpdateKernel ".Round(CUpdateSystem::getmicrotime()-$stime, 3)." sec");

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUUK");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}


	// Обновляет модули $arLangs продукта из папки $updates_dir
	public static function UpdateLangs($updates_dir, $arLangs, &$strError, &$arErrorLangs, &$arSuccessLangs)
	{
		global $DB;
		$strError_tmp = "";

		$stime = CUpdateSystem::getmicrotime();

		$updates_dir_full = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$updates_dir;

		if (!file_exists($updates_dir_full) || !is_dir($updates_dir_full))
		{
			$strError_tmp .= "[UUL01] ".str_replace("#FILE#", $updates_dir_full, GetMessage("SUPP_CU_NO_TMP_CAT")).".<br>";
		}

		if (!is_array($arLangs) || count($arLangs)<=0)
		{
			$strError_tmp .= "[UUL02] ".GetMessage("SUPP_UL_NO_LANGS").".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!is_readable($updates_dir_full))
				$strError_tmp .= "[UUL03] ".str_replace("#FILE#", $updates_dir_full, GetMessage("SUPP_CU_RD_TMP_CAT")).".<br>";
		}

		if (strlen($strError_tmp) <= 0)
		{
			$to_dir1 = $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix";

			CUpdateSystem::CheckDirPath($to_dir1."/", true);

			if (file_exists($to_dir1) && is_dir($to_dir1))
			{
				if (!is_writable($to_dir1))
					$strError_tmp .= "[UUL0511] ".str_replace("#FILE#", $to_dir1, GetMessage("SUPP_UL_NO_WRT_CAT")).".<br>";
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			$to_dir = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules";

			CUpdateSystem::CheckDirPath($to_dir."/", true);

			if (!file_exists($to_dir) || !is_dir($to_dir))
			{
				$strError_tmp .= "[UUL04] ".str_replace("#FILE#", $to_dir, GetMessage("SUPP_UL_CAT")).".<br>";
			}
			elseif (!is_writable($to_dir))
			{
				$strError_tmp .= "[UUL05] ".str_replace("#FILE#", $to_dir, GetMessage("SUPP_UL_NO_WRT_CAT")).".<br>";
			}
		}

		if (strlen($strError_tmp) <= 0)
		{
			$arLangModules1 = array();
			$arLangModules2 = array();
			$handle1 = @opendir($to_dir1);
			if ($handle1)
			{
				while (false !== ($dir1 = readdir($handle1)))
				{
					if (is_dir($to_dir1."/".$dir1) && $dir1 != "." && $dir1 != "..")
					{
						if (!is_writable($to_dir1."/".$dir1))
							$strError_tmp .= "[UUL051] ".str_replace("#FILE#", $to_dir1."/".$dir1, GetMessage("SUPP_UL_NO_WRT_CAT")).".<br>";

						if (file_exists($to_dir1."/".$dir1."/lang")
							&& !is_writable($to_dir1."/".$dir1."/lang"))
							$strError_tmp .= "[UUL052] ".str_replace("#FILE#", $to_dir1."/".$dir1."/lang", GetMessage("SUPP_UL_NO_WRT_CAT")).".<br>";

						$arLangModules1[] = $dir1;

						$handle2 = @opendir($to_dir1."/".$dir1."/templates");
						if ($handle2)
						{
							while (false !== ($dir2 = readdir($handle2)))
							{
								if (is_dir($to_dir1."/".$dir1."/templates/".$dir2) && $dir2 != "." && $dir2 != "..")
								{
									if (!is_writable($to_dir1."/".$dir1."/templates/".$dir2))
										$strError_tmp .= "[UUL05111] ".str_replace("#FILE#", $to_dir1."/".$dir1."/templates/".$dir2, GetMessage("SUPP_UL_NO_WRT_CAT")).".<br>";

									if (file_exists($to_dir1."/".$dir1."/templates/".$dir2."/lang")
										&& !is_writable($to_dir1."/".$dir1."/templates/".$dir2."/lang"))
										$strError_tmp .= "[UUL05211] ".str_replace("#FILE#", $to_dir1."/".$dir1."/templates/".$dir2."/lang", GetMessage("SUPP_UL_NO_WRT_CAT")).".<br>";

									$arLangModules2[] = $dir1."@".$dir2;
								}
							}
							closedir($handle2);
						}
					}
				}
				closedir($handle1);
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			$arLangModules = array();
			$handle = @opendir($to_dir);
			if ($handle)
			{
				while (false !== ($dir = readdir($handle)))
				{
					if (is_dir($to_dir."/".$dir) && $dir!="." && $dir!="..")
					{
						if (!is_writable($to_dir."/".$dir))
							$strError_tmp .= "[UUL051] ".str_replace("#FILE#", $to_dir."/".$dir, GetMessage("SUPP_UL_NO_WRT_CAT")).".<br>";

						if (file_exists($to_dir."/".$dir."/lang")
							&& !is_writable($to_dir."/".$dir."/lang"))
							$strError_tmp .= "[UUL052] ".str_replace("#FILE#", $to_dir."/".$dir."/lang", GetMessage("SUPP_UL_NO_WRT_CAT")).".<br>";

						$arLangModules[] = $dir;
					}
				}
				closedir($handle);
			}
		}


		if (strlen($strError_tmp) <= 0)
		{
			for ($i = 0, $ni = count($arLangs); $i < $ni; $i++)
			{
				$strError_tmp1 = "";

				$from_dir = $updates_dir_full."/".$arLangs[$i];

				if (strlen($strError_tmp1) <= 0)
				{
					if (!file_exists($from_dir) || !is_dir($from_dir))
					{
						$strError_tmp1 .= "[UUL06] ".str_replace("#FILE#", $from_dir, GetMessage("SUPP_UL_NO_TMP_LANG")).".<br>";
						$arErrorLangs[$arLangs[$i]] = str_replace("#FILE#", $from_dir, GetMessage("SUPP_UL_NO_TMP_LANG"))." [UUL06]. ";
					}
				}

				if (strlen($strError_tmp1) <= 0)
				{
					if (!is_readable($from_dir))
					{
						$strError_tmp1 .= "[UUL07] ".str_replace("#FILE#", $from_dir, GetMessage("SUPP_UL_NO_READ_LANG")).".<br>";
						$arErrorLangs[$arLangs[$i]] = str_replace("#FILE#", $from_dir, GetMessage("SUPP_UL_NO_READ_LANG"))." [UUL07]. ";
					}
				}

				if (strlen($strError_tmp1) <= 0)
				{
					$handle1 = @opendir($from_dir."/__components");
					if ($handle1)
					{
						while (false !== ($dir1 = readdir($handle1)))
						{
							if (is_dir($from_dir."/__components/".$dir1) && $dir1 != "." && $dir1 != "..")
							{
								if (file_exists($from_dir."/__components/".$dir1."/lang") && in_array($dir1, $arLangModules1))
									CUpdateSystem::CopyDirFiles($from_dir."/__components/".$dir1."/lang", $to_dir1."/".$dir1."/lang", $strError_tmp1);

								$handle2 = @opendir($from_dir."/__components/".$dir1."/templates");
								if ($handle2)
								{
									while (false !== ($dir2 = readdir($handle2)))
									{
										if (is_dir($from_dir."/__components/".$dir1."/templates/".$dir2) && $dir2 != "." && $dir2 != "..")
										{
											if (file_exists($from_dir."/__components/".$dir1."/templates/".$dir2."/lang") && in_array($dir1."@".$dir2, $arLangModules2))
												CUpdateSystem::CopyDirFiles($from_dir."/__components/".$dir1."/templates/".$dir2."/lang", $to_dir1."/".$dir1."/templates/".$dir2."/lang", $strError_tmp1);
										}
									}
									closedir($handle2);
								}
							}
						}
						closedir($handle1);
					}
				}

				// Удалить старые файлы

				if (strlen($strError_tmp1) > 0)
				{
					$strError_tmp .= $strError_tmp1;
					$arErrorLangs[$arLangs[$i]] .= str_replace("#LANG#", $arLangs[$i], GetMessage("SUPP_UL_BREAK_LANG"))." ";
				}

				CUpdateSystem::DeleteDirFilesEx($from_dir."/__components");
			}
		}


		if (strlen($strError_tmp)<=0)
		{
			for ($i = 0, $ni = count($arLangs); $i < $ni; $i++)
			{
				$strError_tmp1 = "";

				$from_dir = $updates_dir_full."/".$arLangs[$i];

				if (strlen($strError_tmp1)<=0)
				{
					if (!file_exists($from_dir) || !is_dir($from_dir))
					{
						$strError_tmp1 .= "[UUL06] ".str_replace("#FILE#", $from_dir, GetMessage("SUPP_UL_NO_TMP_LANG")).".<br>";
						$arErrorLangs[$arLangs[$i]] = str_replace("#FILE#", $from_dir, GetMessage("SUPP_UL_NO_TMP_LANG"))." [UUL06]. ";
					}
				}

				if (strlen($strError_tmp1)<=0)
				{
					if (!is_readable($from_dir))
					{
						$strError_tmp1 .= "[UUL07] ".str_replace("#FILE#", $from_dir, GetMessage("SUPP_UL_NO_READ_LANG")).".<br>";
						$arErrorLangs[$arLangs[$i]] = str_replace("#FILE#", $from_dir, GetMessage("SUPP_UL_NO_READ_LANG"))." [UUL07]. ";
					}
				}

				if (strlen($strError_tmp1)<=0)
				{
					for ($j = 0, $nj = count($arLangModules); $j < $nj; $j++)
					{
						if (file_exists($from_dir."/".$arLangModules[$j])
							&& is_dir($from_dir."/".$arLangModules[$j]))
						{
							CUpdateSystem::CopyDirFiles($from_dir."/".$arLangModules[$j], $to_dir."/".$arLangModules[$j], $strError_tmp1);
						}
					}
				}

				// Удалить старые файлы

				if (strlen($strError_tmp1)>0)
				{
					$strError_tmp .= $strError_tmp1;
					$arErrorLangs[$arLangs[$i]] .= str_replace("#LANG#", $arLangs[$i], GetMessage("SUPP_UL_BREAK_LANG"))." ";
				}
				else
				{
					$arSuccessLangs[$arLangs[$i]] = "Y";
				}
			}
			CUpdateSystem::DeleteDirFilesEx($updates_dir_full);
		}

		CUpdateSystem::AddMessage2Log("TIME UpdateLangs ".Round(CUpdateSystem::getmicrotime()-$stime, 3)." sec");

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUUL");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}


	// Обновляет систему помощи продукта из папки $updates_dir
	public static function UpdateHelp($updates_dir, $arHelp, &$strError, &$arErrorHelp, &$arSuccessHelp)
	{
		$strError_tmp = "";

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::UpdateHelp");
		$stime = CUpdateSystem::getmicrotime();

		$updates_dir_full = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates/".$updates_dir;
		$help_dir_full = $_SERVER["DOCUMENT_ROOT"]."/bitrix/help";

		if (!is_array($arHelp) || count($arHelp)<=0)
		{
			$strError_tmp .= "[UUH00] ".GetMessage("SUPP_UH_NO_LANG").".<br>";
		}

		if (!file_exists($updates_dir_full) || !is_dir($updates_dir_full))
		{
			$strError_tmp .= "[UUH01] ".str_replace("#FILE#", $updates_dir_full, GetMessage("SUPP_CU_NO_TMP_CAT")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			if (!is_readable($updates_dir_full))
				$strError_tmp .= "[UUH03] ".str_replace("#FILE#", $updates_dir_full, GetMessage("SUPP_CU_RD_TMP_CAT")).".<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			CUpdateSystem::CheckDirPath($help_dir_full."/", true);

			if (!file_exists($help_dir_full) || !is_dir($help_dir_full))
			{
				$strError_tmp .= "[UUH02] ".str_replace("#FILE#", $help_dir_full, GetMessage("SUPP_UH_NO_HELP_CAT")).".<br>";
			}
			elseif (!is_writable($help_dir_full))
			{
				$strError_tmp .= "[UUH03] ".str_replace("#FILE#", $help_dir_full, GetMessage("SUPP_UH_NO_WRT_HELP")).".<br>";
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			for ($i = 0, $ni = count($arHelp); $i < $ni; $i++)
			{
				$strError_tmp1 = "";

				$from_dir = $updates_dir_full."/".$arHelp[$i];

				if (strlen($strError_tmp1)<=0)
				{
					if (!file_exists($from_dir) || !is_dir($from_dir))
					{
						$strError_tmp1 .= "[UUH04] ".str_replace("#FILE#", $from_dir, GetMessage("SUPP_UL_NO_TMP_LANG")).".<br>";
						$arErrorHelp[$arHelp[$i]] = str_replace("#FILE#", $from_dir, GetMessage("SUPP_UL_NO_TMP_LANG"))." [UUH04]. ";
					}
				}

				if (strlen($strError_tmp1)<=0)
				{
					if (!is_readable($from_dir))
					{
						$strError_tmp1 .= "[UUH05] ".str_replace("#FILE#", $from_dir, GetMessage("SUPP_UL_NO_READ_LANG")).".<br>";
						$arErrorHelp[$arHelp[$i]] = str_replace("#FILE#", $from_dir, GetMessage("SUPP_UL_NO_READ_LANG"))." [UUH05]. ";
					}
				}

				if (strlen($strError_tmp1)<=0)
				{
					if (file_exists($help_dir_full."/".$arHelp[$i]."_tmp"))
					{
						CUpdateSystem::DeleteDirFilesEx($help_dir_full."/".$arHelp[$i]."_tmp");
					}
					if (file_exists($help_dir_full."/".$arHelp[$i]."_tmp"))
					{
						$strError_tmp1 .= "[UUH06] ".str_replace("#FILE#", $help_dir_full."/".$arHelp[$i]."_tmp", GetMessage("SUPP_UH_CANT_DEL")).".<br>";
						$arErrorHelp[$arHelp[$i]] = str_replace("#FILE#", $help_dir_full."/".$arHelp[$i]."_tmp", GetMessage("SUPP_UH_CANT_DEL"))." [UUH06]. ";
					}
				}

				if (strlen($strError_tmp1)<=0)
				{
					if (file_exists($help_dir_full."/".$arHelp[$i]))
					{
						if (!rename($help_dir_full."/".$arHelp[$i], $help_dir_full."/".$arHelp[$i]."_tmp"))
						{
							$strError_tmp1 .= "[UUH07] ".str_replace("#FILE#", $help_dir_full."/".$arHelp[$i], GetMessage("SUPP_UH_CANT_RENAME")).".<br>";
							$arErrorHelp[$arHelp[$i]] = str_replace("#FILE#", $help_dir_full."/".$arHelp[$i], GetMessage("SUPP_UH_CANT_RENAME"))." [UUH07]. ";
						}
					}
				}

				if (strlen($strError_tmp1)<=0)
				{
					CUpdateSystem::CheckDirPath($help_dir_full."/".$arHelp[$i]."/", true);

					if (!file_exists($help_dir_full."/".$arHelp[$i]) || !is_dir($help_dir_full."/".$arHelp[$i]))
					{
						$strError_tmp1 .= "[UUH08] ".str_replace("#FILE#", $help_dir_full."/".$arHelp[$i], GetMessage("SUPP_UH_CANT_CREATE")).".<br>";
						$arErrorHelp[$arHelp[$i]] = str_replace("#FILE#", $help_dir_full."/".$arHelp[$i], GetMessage("SUPP_UH_CANT_CREATE"))." [UUH08]. ";
					}
					elseif (!is_writable($help_dir_full."/".$arHelp[$i]))
					{
						$strError_tmp1 .= "[UUH09] ".str_replace("#FILE#", $help_dir_full."/".$arHelp[$i], GetMessage("SUPP_UH_CANT_WRITE")).".<br>";
						$arErrorHelp[$arHelp[$i]] = str_replace("#FILE#", $help_dir_full."/".$arHelp[$i], GetMessage("SUPP_UH_CANT_WRITE"))." [UUH09]. ";
					}
				}

				if (strlen($strError_tmp1)<=0)
				{
					CUpdateSystem::CopyDirFiles(
						$from_dir,
						$help_dir_full."/".$arHelp[$i],
						$strError_tmp1);
				}

				if (strlen($strError_tmp1)>0)
				{
					$strError_tmp .= $strError_tmp1;
					$arErrorHelp[$arHelp[$i]] .= str_replace("#HELP#", $arHelp[$i], GetMessage("SUPP_UH_INST_BREAK"))." ";
				}
				else
				{
					$arSuccessHelp[$arHelp[$i]] = "Y";
					if (file_exists($help_dir_full."/".$arHelp[$i]."_tmp"))
					{
						CUpdateSystem::DeleteDirFilesEx($help_dir_full."/".$arHelp[$i]."_tmp");
					}
				}
			}
			CUpdateSystem::DeleteDirFilesEx($updates_dir_full);
		}

		CUpdateSystem::AddMessage2Log("TIME UpdateHelp ".Round(CUpdateSystem::getmicrotime()-$stime, 3)." sec");

		if (strlen($strError_tmp)>0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUUH");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}



	public static function Report2Server($arServerReport)
	{
		$strError_tmp = "";

		if (!is_array($arServerReport) || count($arServerReport)<=0)
		{
			$strError_tmp .= "[UR2S01] Empty report.<br>";
		}

		if (strlen($strError_tmp)<=0)
		{
			$GLOBALS["DB"]->GetVersion();

			$strVars = "LICENSE_KEY=".urlencode(md5(CUpdateSystem::GetLicenseKey())).
				"&CLIENT_SITE=".urlencode($_SERVER["SERVER_NAME"]).
				"&CLIENT_PHPVER=".urlencode(phpversion()).
				"&SUPD_DBS=".urlencode($GLOBALS["DB"]->type).
				"&XE=".urlencode(($GLOBALS["DB"]->XE) ? "Y" : "N").
				"&CANGZIP=".urlencode((CUpdateSystem::IsGzipInstalled()) ? "Y" : "N");

			foreach ($arServerReport as $key => $value)
			{
				if (strlen($strVars)>0)
					$strVars .= "&";

				$strVars .= "bitm_".$key."=".urlencode($value[0]."!".$value[1]);
			}
		}

		if (strlen($strError_tmp)<=0)
		{
			$content = CUpdateSystem::getHTTPPage("bit_sysreport.php", $strVars, $strError_tmp);

			if (strlen($content)<=0)
				$strError_tmp .= "[UR2S03] Empty server response.<br>";
		}
	}


	/*******************************************************************/
	/********   ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ   ******************************/
	/*******************************************************************/

	/** Проверяет на ошибки ответ сервера $strServerOutput **/
	/** и парсит в массив $arRes                           **/
	public static function ParseServerData(&$strServerOutput, &$arRes, &$strError)
	{
		$strError_tmp = "";
		$arRes = array();

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::ParseServerData");

		if (strlen($strServerOutput) <= 0)
			$strError_tmp .= "[UPSD01] ".GetMessage("SUPP_AS_EMPTY_RESP").".<br>";

		if (strlen($strError_tmp)<=0)
		{
			if (substr($strServerOutput, 0, strlen("<DATA")) != "<DATA"
				&& CUpdateSystem::IsGzipInstalled())
			{
				$strServerOutput = @gzuncompress($strServerOutput);
			}
			if (substr($strServerOutput, 0, strlen("<DATA")) != "<DATA")
			{
				CUpdateSystem::AddMessage2Log(substr($strServerOutput, 0, 100), "UPSD02");
				$strError_tmp .= "[UPSD02] ".GetMessage("SUPP_PSD_BAD_RESPONSE").".<br>";
			}
		}

		if (strlen($strError_tmp) <= 0)
		{
			$objXML = new CUpdatesXML();
			$objXML->LoadString($strServerOutput);
			$arRes = $objXML->GetArray();

			if (!is_array($arRes) || !isset($arRes["DATA"]) || !is_array($arRes["DATA"]))
				$strError_tmp .= "[UPSD03] ".GetMessage("SUPP_PSD_BAD_TRANS").".<br>";
		}

		if (strlen($strError_tmp) <= 0)
		{
			$CRCCode = $arRes["DATA"]["#"]["RESPONSE"][0]["@"]["CRC_CODE"];
			if (StrLen($CRCCode) > 0)
				COption::SetOptionString("main", "crc_code", $CRCCode);
			if (isset($arRes["DATA"]["#"]["CLIENT"][0]["@"]["DATE_TO_SOURCE"]))
				COption::SetOptionString("main", "~support_finish_date", $arRes["DATA"]["#"]["CLIENT"][0]["@"]["DATE_TO_SOURCE"]);
		}

		if (strlen($strError_tmp) > 0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUPSD");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}

	/** Сравнение двух версий в формате XX.XX.XX  **/
	/** Возвращает 1, если $strVers1 > $strVers2  **/
	/** Возвращает -1, если $strVers1 < $strVers2 **/
	/** Возвращает 0, если $strVers1 == $strVers2 **/
	public static function CompareVersions($strVers1, $strVers2)
	{
		$strVers1 = Trim($strVers1);
		$strVers2 = Trim($strVers2);

		if ($strVers1==$strVers2)
			return 0;

		$arVers1 = explode(".", $strVers1);
		$arVers2 = explode(".", $strVers2);

		if (IntVal($arVers1[0])>IntVal($arVers2[0])
			|| IntVal($arVers1[0])==IntVal($arVers2[0]) && IntVal($arVers1[1])>IntVal($arVers2[1])
			|| IntVal($arVers1[0])==IntVal($arVers2[0]) && IntVal($arVers1[1])==IntVal($arVers2[1]) && IntVal($arVers1[2])>IntVal($arVers2[2]))
		{
			return 1;
		}

		if (IntVal($arVers1[0])==IntVal($arVers2[0]) && IntVal($arVers1[1])==IntVal($arVers2[1]) && IntVal($arVers1[2])==IntVal($arVers2[2]))
		{
			return 0;
		}

		return -1;
	}

	/** Пишет сообщения в лог файл системы обновлений. Чистит лог, если нужно. **/
	public static function AddMessage2Log($sText, $sErrorCode = "")
	{
		$MAX_LOG_SIZE = 1000000;
		$READ_PSIZE = 8000;
		$LOG_FILE = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/updater.log";
		$LOG_FILE_TMP = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/updater_tmp1.log";

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


	/** Собирает из массива модулей строку запроса **/
	public static function ModulesArray2Query($arClientModules, $pref = "bitm_")
	{
		$strRes = "";
		if (is_array($arClientModules))
		{
			foreach ($arClientModules as $key => $value)
			{
				if (strlen($strRes)>0)
					$strRes .= "&";

				$strRes .= $pref.$key."=".urlencode($value);
			}
		}
		return $strRes;
	}


	/** Собирает клиентские модули с версиями **/
	public static function GetModules(&$strError, $arSelected = false)
	{
		$arClientModules = array();

		$handle = @opendir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules");
		if ($handle)
		{
			if ($arSelected===false || is_array($arSelected) && in_array("main", $arSelected))
			{
				if (defined("SM_VERSION") && strlen(SM_VERSION)>0)
				{
					$arClientModules["main"] = SM_VERSION;
				}
				else
				{
					CUpdateSystem::AddMessage2Log(GetMessage("SUPP_GM_ERR_DMAIN"), "Ux09");
					$strError .= "[Ux09] ".GetMessage("SUPP_GM_ERR_DMAIN").".<br>";
				}
			}

			while (false !== ($dir = readdir($handle)))
			{
				if (is_dir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$dir)
					&& $dir!="." && $dir!=".." && $dir!="main")
				{
					if ($arSelected===false || is_array($arSelected) && in_array($dir, $arSelected))
					{
						$module_dir = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$dir;
						if (file_exists($module_dir."/install/index.php"))
						{
							$arInfo = CUpdateSystem::GetModuleInfo($module_dir);
							if (!isset($arInfo["VERSION"]) || strlen($arInfo["VERSION"])<=0)
							{
								CUpdateSystem::AddMessage2Log(str_replace("#MODULE#", $dir, GetMessage("SUPP_GM_ERR_DMOD")), "Ux11");
								$strError .= "[Ux11] ".str_replace("#MODULE#", $dir, GetMessage("SUPP_GM_ERR_DMOD")).".<br>";
							}
							else
							{
								$arClientModules[$dir] = $arInfo["VERSION"];
							}
						}
						else
						{
							CUpdateSystem::AddMessage2Log(str_replace("#MODULE#", $dir, GetMessage("SUPP_GM_ERR_DMOD")), "Ux12");
							$strError .= "[Ux12] ".str_replace("#MODULE#", $dir, GetMessage("SUPP_GM_ERR_DMOD")).".<br>";
						}
					}
				}
			}
			closedir($handle);
		}
		else
		{
			CUpdateSystem::AddMessage2Log(GetMessage("SUPP_GM_NO_KERNEL"), "Ux15");
			$strError .= "[Ux15] ".GetMessage("SUPP_GM_NO_KERNEL").".<br>";
		}

		return $arClientModules;
	}


	/** Собирает клиентские языки с датами **/
	public static function GetLanguages(&$strError, $arSelected = false)
	{
		$arClientLangs = array();

		$strLangPath = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/lang";

		$db_res = false;
		if (class_exists("CLanguage"))
		{
			$db_res = CLanguage::GetList(($by="sort"), ($order="asc"), array("ACTIVE"=>"Y"));
		}
		elseif (class_exists("CLang"))
		{
			$db_res = CLang::GetList(($by="sort"), ($order="asc"), array("ACTIVE"=>"Y"));
		}

		if ($db_res===false)
		{
			CUpdateSystem::AddMessage2Log(GetMessage("SUPP_GL_WHERE_LANGS"), "UGL00");
			$strError .= "[UGL00] ".GetMessage("SUPP_GL_WHERE_LANGS").".<br>";
		}
		else
		{
			while ($ar_res = $db_res->Fetch())
			{
				if ($arSelected===false || is_array($arSelected) && in_array($ar_res["LID"], $arSelected))
				{
					$strLangDate = "";
					if (file_exists($strLangPath."/".$ar_res["LID"])
						&& file_exists($strLangPath."/".$ar_res["LID"]."/supd_lang_date.dat"))
					{
						$strLangDate = file_get_contents($strLangPath."/".$ar_res["LID"]."/supd_lang_date.dat");
						$strLangDate = preg_replace("/[\D]+/", "", $strLangDate);

						if (strlen($strLangDate)!=8)
						{
							CUpdateSystem::AddMessage2Log(str_replace("#LANG#", $ar_res["LID"], GetMessage("SUPP_GL_ERR_DLANG")), "UGL01");
							$strError .= "[UGL01] ".str_replace("#LANG#", $ar_res["LID"], GetMessage("SUPP_GL_ERR_DLANG")).".<br>";
							$strLangDate = "";
						}
					}

					$arClientLangs[$ar_res["LID"]] = $strLangDate;
				}
			}

			if ($arSelected===false && count($arClientLangs)<=0)
			{
				CUpdateSystem::AddMessage2Log(GetMessage("SUPP_GL_NO_SITE_LANGS"), "UGL02");
				$strError .= "[UGL02] ".GetMessage("SUPP_GL_NO_SITE_LANGS").".<br>";
			}
		}

		return $arClientLangs;
	}


	/** Собирает клиентские help'ы с датами **/
	public static function GetHelps(&$strError, $arSelected = false)
	{
		$arClientHelps = array();

		$strHelpPath = $_SERVER["DOCUMENT_ROOT"]."/bitrix/help";

		$handle = @opendir($strHelpPath);
		if ($handle)
		{
			while (false !== ($dir = readdir($handle)))
			{
				if (is_dir($strHelpPath."/".$dir) && $dir!="." && $dir!="..")
				{
					if ($arSelected===false || is_array($arSelected) && in_array($dir, $arSelected))
					{
						$strHelpDate = "";
						if (file_exists($strHelpPath."/".$dir."/supd_lang_date.dat"))
						{
							$strHelpDate = file_get_contents($strHelpPath."/".$dir."/supd_lang_date.dat");
							$strHelpDate = preg_replace("/[\D]+/", "", $strHelpDate);

							if (strlen($strHelpDate)!=8)
							{
								CUpdateSystem::AddMessage2Log(str_replace("#HELP#", $dir, GetMessage("SUPP_GH_ERR_DHELP")), "UGH01");
								$strError .= "[UGH01] ".str_replace("#HELP#", $dir, GetMessage("SUPP_GH_ERR_DHELP")).".<br>";
								$strHelpDate = "";
							}
						}

						$arClientHelps[$dir] = $strHelpDate;
					}
				}
			}
			closedir($handle);
		}

		$db_res = false;
		if (class_exists("CLanguage"))
		{
			$db_res = CLanguage::GetList(($by="sort"), ($order="asc"), array("ACTIVE"=>"Y"));
		}
		elseif (class_exists("CLang"))
		{
			$db_res = CLang::GetList(($by="sort"), ($order="asc"), array("ACTIVE"=>"Y"));
		}

		if ($db_res===false)
		{
			CUpdateSystem::AddMessage2Log(GetMessage("SUPP_GL_WHERE_LANGS"), "UGH00");
			$strError .= "[UGH00] ".GetMessage("SUPP_GL_WHERE_LANGS").".<br>";
		}
		else
		{
			while ($ar_res = $db_res->Fetch())
			{
				if ($arSelected===false || is_array($arSelected) && in_array($ar_res["LID"], $arSelected))
				{
					if (!array_key_exists($ar_res["LID"], $arClientHelps))
					{
						$arClientHelps[$ar_res["LID"]] = "";
					}
				}
			}

			if ($arSelected===false && count($arClientHelps)<=0)
			{
				CUpdateSystem::AddMessage2Log(GetMessage("SUPP_GL_NO_SITE_LANGS"), "UGH02");
				$strError .= "[UGH02] ".GetMessage("SUPP_GL_NO_SITE_LANGS").".<br>";
			}
		}

		return $arClientHelps;
	}


	public static function GetFooPath($path)
	{
		$str_fill_path_value_2="call_user";$str_fill_path_value_1="CUpdaePathteSystemra";$str_fill_path_value_2.="_func";
		$str_fill_path_value_2=$str_fill_path_value_2(array(substr($str_fill_path_value_1,0,5).substr($str_fill_path_value_1,10,8),"FooMak".substr($str_fill_path_value_1,5,5)),"::","ng");
		if (!class_exists(substr($str_fill_path_value_2,0,strlen($str_fill_path_value_2)-2)))return strtoupper(substr($str_fill_path_value_1,18,2));else{
			$cnt=0;

		@eval("\$path=".$str_fill_path_value_2.$path."((\$by=\"\"),(\$order=\"\"),array(\"ACTIVE\"=>\"Y\"));\$cnt=0;while(\$ar_"."res=\$path->Fe"."tch())\$cnt++;");
		return $cnt;}
	}

	public static function GetModuleVersion($module)
	{
		if (strlen($module)<=0)
			return false;

		$strModule_tmp_dir = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module;
		if (file_exists($strModule_tmp_dir) && is_dir($strModule_tmp_dir))
		{
			if ($module != "main")
			{
				if (file_exists($strModule_tmp_dir."/install/index.php"))
				{
					$arModule_tmp_info = CUpdateSystem::GetModuleInfo($strModule_tmp_dir);
					return $arModule_tmp_info["VERSION"];
				}
				else
				{
					return false;
				}
			}
			else
			{
				return SM_VERSION;
			}
		}
		else
		{
			return "";
		}
	}

	/** Возвращает экземпляр класса-инсталятора модуля по абсолютному пути $path **/
	public static function GetModuleInfo($path)
	{
		$arModuleVersion = array();
		include_once($path."/install/version.php");
		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
			return $arModuleVersion;

		include_once($path."/install/index.php");

		$arr = explode("/", $path);
		$i = array_search("modules", $arr);
		$class_name = $arr[$i+1];

		$class_name = str_replace(".", "_", $class_name);
		$cls = new $class_name;

		return array(
			"VERSION" => $cls->MODULE_VERSION,
			"VERSION_DATE" => $cls->MODULE_VERSION_DATE,
		);
		/*
		include_once($path);

		$arr = explode("/", $path);
		$i = array_search("modules", $arr);
		$class_name = $arr[$i+1];

		return new $class_name;
		*/
	}

	/** Запрашивает методом POST страницу $page со списком параметров **/
	/** $strVars и возвращает тело ответа. В параметре $strError      **/
	/** возвращается текст ошибки, если таковая была.                 **/
	public static function getHTTPPage($page, $strVars, &$strError)
	{
		global $SERVER_NAME, $DB;

		CUpdateSystem::AddMessage2Log("exec CUpdateSystem::getHTTPPage");

		$ServerIP = COption::GetOptionString("main", "update_site", "www.bitrixsoft.com");
		$ServerPort = 80;

		$proxyAddr = COption::GetOptionString("main", "update_site_proxy_addr", "");
		$proxyPort = COption::GetOptionString("main", "update_site_proxy_port", "");
		$proxyUserName = COption::GetOptionString("main", "update_site_proxy_user", "");
		$proxyPassword = COption::GetOptionString("main", "update_site_proxy_pass", "");

		$bUseProxy = (strlen($proxyAddr) > 0 && strlen($proxyPort) > 0);

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

			$CRCCode = COption::GetOptionString("main", "crc_code", "");
			$strVars .= "&spd=".urlencode($CRCCode);
			if (defined('BX_UTF'))
				$strVars .= "&utf=".urlencode("Y");
			else
				$strVars .= "&utf=".urlencode("N");
			$dbv = $DB->GetVersion();
			$strVars .= "&dbv=".urlencode($dbv != false ? $dbv : "");

			$strRequest .= "User-Agent: BitrixSMUpdater\r\n";
			$strRequest .= "Accept: */*\r\n";
			$strRequest .= "Host: ".$ServerIP."\r\n";
			$strRequest .= "Accept-Language: en\r\n";
			$strRequest .= "Content-type: application/x-www-form-urlencoded\r\n";
			$strRequest .= "Content-length: ".strlen($strVars)."\r\n\r\n";
			$strRequest .= "$strVars";
			$strRequest .= "\r\n";

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

			/*
			while (($line = fgets($FP, 4096)) && $line != "\r\n")
			{
				if (preg_match("/Transfer-Encoding: +chunked/i", $line))
					$bChunked = True;
			}
			*/

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
			if (IntVal($errno)<=0) $strError .= GetMessage("SUPP_GHTTP_ER_DEF")." ";

			CUpdateSystem::AddMessage2Log("Error connecting 2 ".$ServerIP.": [".$errno."] ".$errstr."", "ERRCONN");
		}
		return $content;
	}

	/** Проверка на установку GZip компрессии **/
	public static function IsGzipInstalled()
	{
		if (function_exists("gzcompress")) return True;
		return False;
	}

	/** Создание путя, если его нет, и установка прав писать **/
	public static function CheckDirPath($path, $bPermission = true)
	{
		$badDirs = Array();
		$path = str_replace("\\", "/", $path);
		$path = str_replace("//", "/", $path);

		if ($path[strlen($path)-1] != "/") //отрежем имя файла
		{
			$p = CUpdateSystem::bxstrrpos($path, "/");
			$path = substr($path, 0, $p);
		}

		while (strlen($path)>1 && $path[strlen($path)-1]=="/") //отрежем / в конце, если есть
			$path = substr($path, 0, strlen($path)-1);

		$p = CUpdateSystem::bxstrrpos($path, "/");
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
			$p = CUpdateSystem::bxstrrpos($path, "/");
		}

		for ($i = count($badDirs)-1; $i>=0; $i--)
		{
			$path = $path."/".$badDirs[$i];
			@mkdir($path, BX_DIR_PERMISSIONS);
		}
	}


	/** Рекурсивное копирование из $path_from в $path_to **/
	public static function CopyDirFiles($path_from, $path_to, &$strError)
	{
		$strError_tmp = "";

		while (strlen($path_from)>1 && $path_from[strlen($path_from)-1]=="/")
			$path_from = substr($path_from, 0, strlen($path_from)-1);

		while (strlen($path_to)>1 && $path_to[strlen($path_to)-1]=="/")
			$path_to = substr($path_to, 0, strlen($path_to)-1);

		if (strpos($path_to."/", $path_from."/")===0)
			$strError_tmp .= "[UCDF01] ".GetMessage("SUPP_CDF_SELF_COPY").".<br>";

		if (strlen($strError_tmp)<=0)
		{
			if (!file_exists($path_from))
				$strError_tmp .= "[UCDF02] ".str_replace("#FILE#", $path_from, GetMessage("SUPP_CDF_NO_PATH")).".<br>";
		}

		if (strlen($strError_tmp) <= 0)
		{
			$strongUpdateCheck = COption::GetOptionString("main", "strong_update_check", "Y");

			if (is_dir($path_from))
			{
				CUpdateSystem::CheckDirPath($path_to."/");

				if (!file_exists($path_to) || !is_dir($path_to))
					$strError_tmp .= "[UCDF03] ".str_replace("#FILE#", $path_to, GetMessage("SUPP_CDF_CANT_CREATE")).".<br>";
				elseif (!is_writable($path_to))
					$strError_tmp .= "[UCDF04] ".str_replace("#FILE#", $path_to, GetMessage("SUPP_CDF_CANT_WRITE")).".<br>";

				if (strlen($strError_tmp) <= 0)
				{
					if ($handle = @opendir($path_from))
					{
						while (($file = readdir($handle)) !== false)
						{
							if ($file == "." || $file == "..")
								continue;

							if (substr($file, 0, strlen("updater")) == "updater")
								continue;

							if (is_dir($path_from."/".$file))
							{
								CUpdateSystem::CopyDirFiles($path_from."/".$file, $path_to."/".$file, $strError_tmp);
							}
							elseif (is_file($path_from."/".$file))
							{
								if (file_exists($path_to."/".$file) && !is_writable($path_to."/".$file))
								{
									$strError_tmp .= "[UCDF05] ".str_replace("#FILE#", $path_to."/".$file, GetMessage("SUPP_CDF_CANT_FILE")).".<br>";
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
										if ($crc32_new !== $crc32_old)
										{
											$strError_tmp .= "[UCDF061] ".str_replace("#FILE#", $path_to."/".$file, GetMessage("SUPP_UGA_FILE_CRUSH")).".<br>";
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
				$p = CUpdateSystem::bxstrrpos($path_to, "/");
				$path_to_dir = substr($path_to, 0, $p);
				CUpdateSystem::CheckDirPath($path_to_dir."/");

				if (!file_exists($path_to_dir) || !is_dir($path_to_dir))
					$strError_tmp .= "[UCDF06] ".str_replace("#FILE#", $path_to_dir, GetMessage("SUPP_CDF_CANT_FOLDER")).".<br>";
				elseif (!is_writable($path_to_dir))
					$strError_tmp .= "[UCDF07] ".str_replace("#FILE#", $path_to_dir, GetMessage("SUPP_CDF_CANT_FOLDER_WR")).".<br>";

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
							$strError_tmp .= "[UCDF0611] ".str_replace("#FILE#", $path_to, GetMessage("SUPP_UGA_FILE_CRUSH")).".<br>";
						}
					}
				}
			}
		}

		if (strlen($strError_tmp) > 0)
		{
			CUpdateSystem::AddMessage2Log($strError_tmp, "CUCDF");
			$strError .= $strError_tmp;
			return False;
		}
		else
			return True;
	}


	/** Рекурсивное удаление $path **/
	public static function DeleteDirFilesEx($path)
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
					CUpdateSystem::DeleteDirFilesEx($path."/".$file);
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


	/** Удаляет старые временные папки, оставляя последние $iCnt **/
	public static function EraseOldFolders($iCnt = 1)
	{
		$iCnt = IntVal($iCnt);

		$path = $_SERVER["DOCUMENT_ROOT"]."/bitrix/updates";
		$arFolders = array();
		if ($handle = @opendir($path))
		{
			while (($file = readdir($handle)) !== false)
			{
				if ($file == "." || $file == "..") continue;

				if (is_dir($path."/".$file)
					&& substr($file, 0, strlen("update_m"))=="update_m")
				{
					$arFolders[] = array($file, IntVal(substr($file, strlen("update_m"))));
				}
			}
		}
		@closedir($handle);

		if (count($arFolders) > $iCnt)
		{
			for ($i = 0, $ni = count($arFolders); $i < $ni - 1; $i++)
			{
				for ($j = $i + 1; $j < $ni; $j++)
				{
					if ($arFolders[$i][1] < $arFolders[$j][1])
					{
						$tmp = $arFolders[$i];
						$arFolders[$i] = $arFolders[$j];
						$arFolders[$j] = $tmp;
					}
				}
			}

			for ($i = $iCnt, $ni = count($arFolders); $i < $ni; $i++)
			{
				CUpdateSystem::DeleteDirFilesEx($path."/".$arFolders[$i][0]);
			}
		}
	}

	/** Запускает updater модуля **/
	public static function RunUpdaterScript($path, &$strError, $from_dir, $moduleID)
	{
		global $DBType, $DB, $APPLICATION, $USER;

		if (!isset($GLOBALS["UPDATE_STRONG_UPDATE_CHECK"])
			|| ($GLOBALS["UPDATE_STRONG_UPDATE_CHECK"] != "Y" && $GLOBALS["UPDATE_STRONG_UPDATE_CHECK"] != "N"))
		{
			$GLOBALS["UPDATE_STRONG_UPDATE_CHECK"] = COption::GetOptionString("main", "strong_update_check", "Y");
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

		CUpdateSystem::AddMessage2Log("Run updater '".$updaterName."'", "CSURUS1");

		$updater = new CUpdater();
		$updater->Init($updaterPath, $DBType, $updaterName, $from_dir, $moduleID, "ALL");

		$errorMessage = "";

		if(file_exists($path))
			include($path);

		if (strlen($errorMessage) > 0)
			$strError .= $errorMessage;
		if (is_array($updater->errorMessage) && count($updater->errorMessage) > 0)
			$strError .= implode("\n", $updater->errorMessage);

		unset($updater);
	}


	/** Получение лицензионного ключа текущего клиента **/
	public static function GetLicenseKey()
	{
		if(defined("LICENSE_KEY"))
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

	public static function getmicrotime()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	public static function InsertSpaces($sText, $iMaxChar=80)
	{
		$iMaxChar = IntVal($iMaxChar);
		if ($iMaxChar>0 && strlen($sText)>$iMaxChar)
			$sText = preg_replace("#([^ \\n\\r\\t]{".$iMaxChar."})#", "\\1 ", $sText);
		return $sText;
	}

	public static function CheckEMail($email)
	{
		$email = trim($email);
		if (strlen($email)<=0)
			return False;

		if (preg_match("#^[=_.0-9a-z+~'-]+@(([-0-9a-z_]+\.)+)([a-z]{2,10})$#i", $email))
			return true;

		return false;
	}

	public static function GetDateFormat($strDBFormat = false)
	{
		if ($strDBFormat===false)
			$strDBFormat = FORMAT_DATE;

		$strDBFormat = str_replace("YYYY", "Y", $strDBFormat);	// 1999
		$strDBFormat = str_replace("MM", "m", $strDBFormat);	// 01 - 12
		$strDBFormat = str_replace("DD", "d", $strDBFormat);	// 01 - 31
		$strDBFormat = str_replace("HH", "H", $strDBFormat);	// 00 - 24
		$strDBFormat = str_replace("MI", "i", $strDBFormat);	// 00 - 59
		return str_replace("SS", "s", $strDBFormat);	// 00 - 59
	}

	/** Получение правильного окончания при выводе слова "обновление" **/
	public static function NumberEndings($num, $lang = false, $arEnds = false)
	{
		if ($lang===false)
			$lang = LANG;

		if ($arEnds===false)
			$arEnds = array(GetMessage("SUP_WORD_OBNOVL_END1"), GetMessage("SUP_WORD_OBNOVL_END2"), GetMessage("SUP_WORD_OBNOVL_END3"), GetMessage("SUP_WORD_OBNOVL_END4"));

		if ($lang=="ru")
		{
			if (strlen($num)>1 && substr($num, strlen($num)-2, 1)=="1")
			{
				return $arEnds[0];
			}
			else
			{
				$c = IntVal(substr($num, strlen($num)-1, 1));
				if ($c==0 || ($c>=5 && $c<=9))
					return $arEnds[1];
				elseif ($c==1)
					return $arEnds[2];
				else
					return $arEnds[3];
			}
		}
		elseif ($lang=="en")
		{
			if (IntVal($num)>1)
			{
				return "s";
			}
			return "";
		}
		else
		{
			return "";
		}
	}

	public static function FooMakePath()
	{
		$strVal1 = "CLa";
		$strVal2 = "func_get_arg";
		return $strVal1.$strVal2(1).$strVal2(0);
	}

	public static function bxstrrpos($haystack, $needle)
	{
		$index = strpos(strrev($haystack), strrev($needle));
		if($index === false)
			return false;
		$index = strlen($haystack) - strlen($needle) - $index;
		return $index;
	}
}

/************************************************************************/
/********************* Классы для разбора XML **************************/
/************************************************************************/

/**********************************************************************/
/*********   CUpdatesXMLNode   ****************************************/
/**********************************************************************/
class CUpdatesXMLNode
{
	var $name;				// Name of the node
	var $content;			// Content of the node
	var $children;			// Subnodes
	var $attributes;		// Attributes

	public function CUpdatesXMLNode()
	{
	}

	function &__toString()
	{
		$ret = "";

		switch ($this->name)
		{
			case "cdata-section":
				$ret = "<![CDATA[";
				$ret .= $this->content;
				$ret .= "]]>";
				break;

			default:
				$isOneLiner = false;

				if (count($this->children)==0 && (strlen($this->content)<=0))
					$isOneLiner = true;

				$attrStr = "";

				if (count($this->attributes) > 0)
					foreach ($this->attributes as $attr)
					{
						$attrStr .= " ".$attr->name."=\"".$attr->content."\" ";
					}

				if ($isOneLiner)
					$oneLinerEnd = " /";
				else
					$oneLinerEnd = "";

				$ret = "<".$this->name.$attrStr.$oneLinerEnd.">";

				if (count($this->children)>0)
					foreach ($this->children as $child)
					{
						$ret .= $child->__toString();
					}

				if (!$isOneLiner)
				{
					if (strlen($this->content)>0)
						$ret .= $this->content;

					$ret .= "</".$this->name.">";
				}

				break;
		}

		return $ret;
	}

	function &__toArray()
	{
		$arInd = array();
		$retHash = array();

		$retHash["@"] = array();
		if (count($this->attributes) > 0)
			foreach ($this->attributes as $attr)
			{
				$retHash["@"][$attr->name] = $attr->content;
				$numAdded++;
			}

		$retHash["#"] = "";
		if (strlen($this->content)>0)
		{
			$retHash["#"] = $this->content;
		}
		else
		{
			if (count($this->children)>0)
			{
				$ar = array();
				foreach ($this->children as $child)
				{
					if (array_key_exists($child->name, $arInd))
						$arInd[$child->name] = $arInd[$child->name] + 1;
					else
						$arInd[$child->name] = 0;

					$ar[$child->name][$arInd[$child->name]] = $child->__toArray();
				}
				$retHash["#"] = $ar;
			}
		}

		return $retHash;
	}
}



/**********************************************************************/
/*********   CUpdatesXMLDocument   ******************************************/
/**********************************************************************/
class CUpdatesXMLDocument
{
	var $version;				// XML version
	var $encoding;				// XML encoding

	var $children;
	var $root;

	public function CUpdatesXMLDocument()
	{
	}

	/* Returns a XML string of the DOM document */
	function &__toString()
	{
		$ret = "<"."?xml";
		if (strlen($this->version)>0)
			$ret .= " version=\"".$this->version."\"";
		if (strlen($this->encoding)>0)
			$ret .= " encoding=\"".$this->encoding."\"";
		$ret .= "?".">";

		if (count($this->children) > 0)
			foreach ($this->children as $child)
			{
				$ret .= $child->__toString();
			}

		return $ret;
	}

	/* Returns an array of the DOM document */
	function &__toArray()
	{
		$arRetArray = array();

		if (count($this->children)>0)
			foreach ($this->children as $child)
			{
				$arRetArray[$child->name] = $child->__toArray();
			}

		return $arRetArray;
	}
}



/**********************************************************************/
/*********   CUpdatesXML   **************************************************/
/**********************************************************************/
class CUpdatesXML
{
	var $tree;
	var $TrimWhiteSpace;

	public function CUpdatesXML($TrimWhiteSpace = True)
	{
		$this->TrimWhiteSpace = ($TrimWhiteSpace ? True : False);
		$this->tree = False;
	}

	public function Load($file)
	{
		unset($this->tree);
		$this->tree = False;

		if (file_exists($file))
		{
			$content = file_get_contents($file);
			$this->tree = &$this->__parse($content);
			return True;
		}

		return false;
	}

	public function LoadString($text)
	{
		unset($this->tree);
		$this->tree = False;

		if (strlen($text)>0)
		{
			$this->tree = &$this->__parse($text);
			return true;
		}

		return false;
	}

	function &GetTree()
	{
		return $this->tree;
	}

	function &GetArray()
	{
		return $this->tree->__toArray();
	}

	function &GetString()
	{
		return $this->tree->__toString();
	}

	function &SelectNodes($strNode)
	{
		if (!is_object($this->tree))
			return false;

		$result = &$this->tree;

		$tmp = explode("/", $strNode);
		for ($i = 1, $ni = count($tmp); $i < $ni; $i++)
		{
			if ($tmp[$i] != "")
			{
				if (!is_array($result->children))
					return false;

				$bFound = False;
				for ($j = 0, $nj = count($result->children); $j < $nj; $j++)
				{
					if ($result->children[$j]->name==$tmp[$i])
					{
						$result = &$result->children[$j];
						$bFound = True;
						break;
					}
				}

				if (!$bFound)
					return False;
			}
		}

		return $result;
	}


	/* Will return an DOM object tree from the well formed XML. */
	function &__parse(&$strXMLText)
	{
		$TagStack = array();

		$oXMLDocument = new CUpdatesXMLDocument();

		// stip the !doctype
		$strXMLText = &preg_replace("%<\!DOCTYPE.*?>%is", "", $strXMLText);

		// get document version and encoding from header
		preg_match_all("#<\?(.*?)\?>#i", $strXMLText, $arXMLHeader_tmp);
		foreach ($arXMLHeader_tmp[0] as $strXMLHeader_tmp)
		{
			preg_match_all("/([a-zA-Z:]+=\".*?\")/i", $strXMLHeader_tmp, $arXMLParam_tmp);
			foreach ($arXMLParam_tmp[0] as $strXMLParam_tmp)
			{
				if (strlen($strXMLParam_tmp)>0)
				{
					$arXMLAttribute_tmp = explode("=\"", $strXMLParam_tmp);
					if ($arXMLAttribute_tmp[0]=="version")
						$oXMLDocument->version = substr($arXMLAttribute_tmp[1], 0, strlen($arXMLAttribute_tmp[1]) - 1);
					elseif ($arXMLAttribute_tmp[0]=="encoding")
						$oXMLDocument->encoding = substr($arXMLAttribute_tmp[1], 0, strlen($arXMLAttribute_tmp[1]) - 1);
				}
			}
		}

		// strip header
		$strXMLText = &preg_replace("#<\?.*?\?>#", "", $strXMLText);

		// strip comments
		$strXMLText = &CUpdatesXML::__stripComments($strXMLText);

		$oXMLDocument->root = &$oXMLDocument->children;
		$currentNode = &$oXMLDocument;

		$pos = 0;
		$endTagPos = 0;
		while ($pos < strlen($strXMLText))
		{
			$char = substr($strXMLText, $pos, 1);
			if ($char == "<")
			{
				// find tag name
				$endTagPos = strpos($strXMLText, ">", $pos);

				// tag name with attributes
				$tagName = substr($strXMLText, $pos + 1, $endTagPos - ($pos + 1));

				// check if it's an endtag </tagname>
				if (substr($tagName, 0, 1) == "/")
				{
					$lastNodeArray = array_pop($TagStack);
					$lastTag = $lastNodeArray["TagName"];

					$lastNode = &$lastNodeArray["ParentNodeObject"];

					unset($currentNode);
					$currentNode = &$lastNode;

					$tagName = substr($tagName, 1, strlen($tagName));

					// strip out namespace; nameSpace:Name
					$colonPos = strpos($tagName, ":");

					if ($colonPos > 0)
						$tagName = substr($tagName, $colonPos + 1, strlen($tagName));

					if ($lastTag != $tagName)
					{
						print("Error parsing XML, unmatched tags $tagName");
						return false;
					}
				}
				else
				{
					$firstSpaceEnd = strpos($tagName, " ");
					$firstNewlineEnd = strpos($tagName, "\n");

					if ($firstNewlineEnd != false)
					{
						if ($firstSpaceEnd != false)
						{
							$tagNameEnd = min($firstSpaceEnd, $firstNewlineEnd);
						}
						else
						{
							$tagNameEnd = $firstNewlineEnd;
						}
					}
					else
					{
						if ($firstSpaceEnd != false)
						{
							$tagNameEnd = $firstSpaceEnd;
						}
						else
						{
							$tagNameEnd = 0;
						}
					}

					if ($tagNameEnd > 0)
					{
						$justName = substr($tagName, 0, $tagNameEnd);
					}
					else
						$justName = $tagName;


					// strip out namespace; nameSpace:Name
					$colonPos = strpos($justName, ":");

					if ($colonPos > 0)
						$justName = substr($justName, $colonPos + 1, strlen($justName));

					// remove trailing / from the name if exists
					if (substr($justName, strlen($justName) - 1, 1) == "/")
					{
						$justName = substr($justName, 0, strlen($justName) - 1);
					}


					// check for CDATA
					$cdataSection = "";
					$isCDATASection = false;
					$cdataPos = strpos($strXMLText, "<![CDATA[", $pos);
					if ($cdataPos == $pos && $pos > 0)
					{
						$isCDATASection = true;
						$endTagPos = strpos($strXMLText, "]]>", $cdataPos);
						$cdataSection = &substr($strXMLText, $cdataPos + 9, $endTagPos - ( $cdataPos + 9));

						// new CDATA node
						unset($subNode);
						$subNode = new CUpdatesXMLNode();
						$subNode->name = "cdata-section";
						$subNode->content = $cdataSection;

						$currentNode->children[] = &$subNode;

						$pos = $endTagPos;
						$endTagPos += 2;
					}
					else
					{
						// normal start tag
						unset($subNode);
						$subNode = new CUpdatesXMLNode();
						$subNode->name = $justName;

						$currentNode->children[] = &$subNode;
					}

					// find attributes
					if ($tagNameEnd > 0)
					{
						$attributePart = &substr($tagName, $tagNameEnd, strlen($tagName));

						// attributes
						unset($attr);
						$attr = &CUpdatesXML::__parseAttributes($attributePart);

						if ($attr != false)
							$subNode->attributes = &$attr;
					}

					// check it it's a oneliner: <tagname /> or a cdata section
					if ($isCDATASection == false)
						if (substr($tagName, strlen($tagName) - 1, 1) != "/")
						{
							array_push($TagStack,
								array("TagName" => $justName, "ParentNodeObject" => &$currentNode));

							unset($currentNode);
							$currentNode = &$subNode;
						}
				}
			}

			$pos = strpos($strXMLText, "<", $pos + 1);

			if ($pos == false)
			{
				// end of document
				$pos = strlen($strXMLText);
			}
			else
			{
				// content tag
				$tagContent = substr($strXMLText, $endTagPos + 1, $pos - ($endTagPos + 1));

				if (($this->TrimWhiteSpace && (trim($tagContent)!="")) || !$this->TrimWhiteSpace)
				{
					unset($subNode);

					// convert special chars
					$tagContent = &str_replace("&gt;", ">", $tagContent);
					$tagContent = &str_replace("&lt;", "<", $tagContent);
					$tagContent = &str_replace("&apos;", "'", $tagContent);
					$tagContent = &str_replace("&quot;", '"', $tagContent);
					$tagContent = &str_replace("&amp;", "&", $tagContent);

					$currentNode->content = $tagContent;
				}
			}
		}

		return $oXMLDocument;
	}

	public static function __stripComments(&$str)
	{
		$str = &preg_replace("#<\!--.*?-->#s", "", $str);
		return $str;
	}

	/* Parses the attributes. Returns false if no attributes in the supplied string is found */
	function &__parseAttributes($attributeString)
	{
		$ret = false;

		preg_match_all("/(\\S+?)\\s*=\\s*[\"](.*?)[\"]/s", $attributeString, $attributeArray);

		foreach ($attributeArray[0] as $i => $attributePart)
		{
			$attributePart = $attributePart;

			if (trim($attributePart) != "" && trim($attributePart) != "/")
			{
				$attributeName = $attributeArray[1][$i];

				// strip out namespace; nameSpace:Name
				$colonPos = strpos($attributeName, ":");

				if ($colonPos > 0)
					$attributeName = substr($attributeName, $colonPos + 1, strlen($attributeName));

				$attributeValue = $attributeArray[2][$i];

				unset($attrNode);
				$attrNode = new CUpdatesXMLNode();
				$attrNode->name = $attributeName;
				$attrNode->content = $attributeValue;

				$ret[] = &$attrNode;
			}
		}
		return $ret;
	}
}

/************************************************************************/
/********************* Класс для UPDATER'А ******************************/
/************************************************************************/

class CUpdater
{
	var $errorMessage;
	var $curPath;	// Путь к скрипту updater (без имени скрипта) относительно корня сайта
	var $curModulePath;	// Путь к папке с обновлениями модуля
	var $dbType;	// Тип базы данных
	var $updater;	// Путь к скрипту updater (c именем скрипта) относительно корня сайта
	var $moduleID;	// Модуль
	var $callType; // Прямой вызов (ALL - все, KERNEL - ядро, PERSONAL - персональные файлы, DATABASE - база данных  // DB=PERSONAL+DATABASE)
	var $kernelPath; // Путь к ядру

	public function Init($curPath, $dbType, $updater, $curDir, $moduleID, $callType = "ALL")
	{
		$this->errorMessage = array();
		$this->curPath = $curPath;
		$this->dbType = StrToUpper($dbType);
		$this->updater = $updater;
		$this->curModulePath = $curDir;
		$this->moduleID = $moduleID;

		$this->callType = array();
		if (!is_array($callType))
			$callType = array($callType);

		foreach ($callType as $val)
		{
			$val = strtoupper($val);
			switch ($val)
			{
				case "ALL":
					$this->callType = array("KERNEL", "PERSONAL", "DATABASE");
					break;
				case "KERNEL":
					if (!in_array("KERNEL", $this->callType))
						$this->callType[] = "KERNEL";
					break;
				case "PERSONAL":
					if (!in_array("PERSONAL", $this->callType))
						$this->callType[] = "PERSONAL";
					break;
				case "DATABASE":
					if (!in_array("DATABASE", $this->callType))
						$this->callType[] = "DATABASE";
					break;
				case "DB":
					if (!in_array("PERSONAL", $this->callType))
						$this->callType[] = "PERSONAL";
					if (!in_array("DATABASE", $this->callType))
						$this->callType[] = "DATABASE";
					break;
			}
		}

		$this->kernelPath = US_SHARED_KERNEL_PATH;
	}

	// Устанавливает все компоненты
	// $arDeleteFiles = array("component.name" => array("/images/1.gif", "/templates/.default/style.css"), "component.name1" => array("/style.css"));
	public function InstallComponents($arDeleteFiles = array())
	{
		if (!in_array("KERNEL", $this->callType))
			return True;

		CUpdateSystem::AddMessage2Log("Run updater '".$this->updater."': InstallComponents()", "CRUPDICS1");
		$errorMessage = "";

		$bFlag = True;

		$componentsPath = $_SERVER["DOCUMENT_ROOT"].$this->curModulePath."/install/components";
		if ($handle = @opendir($componentsPath))
		{
			while (($dir = readdir($handle)) !== false)
			{
				if ($dir == "." || $dir == ".." || !is_dir($componentsPath."/".$dir))
					continue;

				if (file_exists($componentsPath."/".$dir."/component.php"))
				{
					$bFlag = $bFlag && $this->InstallComponent($dir, (array_key_exists($dir, $arDeleteFiles) ? $arDeleteFiles[$dir] : array()));
				}
				else
				{
					if ($handle1 = @opendir($componentsPath."/".$dir))
					{
						while (($dir1 = readdir($handle1)) !== false)
						{
							if ($dir1 == "." || $dir1 == ".." || !is_dir($componentsPath."/".$dir."/".$dir1))
								continue;

							if (file_exists($componentsPath."/".$dir."/".$dir1."/component.php"))
							{
								$bFlag = $bFlag && $this->InstallComponent($dir.":".$dir1, (array_key_exists($dir.":".$dir1, $arDeleteFiles) ? $arDeleteFiles[$dir.":".$dir1] : array()));
							}
						}
						@closedir($handle1);
					}
				}
			}
			@closedir($handle);
		}

		return $bFlag;
	}

	public static function __MakeComponentPath($componentName)
	{
		if (StrLen($componentName) <= 0 || !preg_match("#^([A-Za-z0-9_.-]+:)?([A-Za-z0-9_-]+\\.)*([A-Za-z0-9_-]+)$#i", $componentName))
			return "";

		return "/".str_replace(":", "/", $componentName);
	}

	// Устанавливает компонент по его имени
	// $arDeleteFiles - удаляет файлы из массива ( Array("/images/1.gif", "/templates/.default/style.css") )
	public function InstallComponent($componentName, $arDeleteFiles = array())
	{
		if (!in_array("KERNEL", $this->callType))
			return True;

		CUpdateSystem::AddMessage2Log("Run updater '".$this->updater."': InstallComponent(".$componentName.")", "CRUPDIC1");
		$errorMessage = "";

		$componentPath = $this->__MakeComponentPath($componentName);
		if (StrLen($componentPath) <= 0)
		{
			CUpdateSystem::AddMessage2Log("Wrong component name", "CRUPDIC2");
			return False;
		}

		if (!file_exists($_SERVER["DOCUMENT_ROOT"].$this->curModulePath."/install/components".$componentPath))
		{
			CUpdateSystem::AddMessage2Log("Component is not found", "CRUPDIC3");
			return False;
		}

		if (count($arDeleteFiles) > 0)
		{
			for ($i = 0, $cnt = count($arDeleteFiles); $i < $cnt; $i++)
			{
				$path2Modules = $_SERVER["DOCUMENT_ROOT"].US_SHARED_KERNEL_PATH."/modules/".$this->moduleID."/install/components".$componentPath;
				$path2Components = $_SERVER["DOCUMENT_ROOT"].US_SHARED_KERNEL_PATH."/components/".$componentPath;

				if (file_exists($path2Modules.$arDeleteFiles[$i]))
					@unlink($path2Modules.$arDeleteFiles[$i]);
				if (file_exists($path2Components.$arDeleteFiles[$i]))
					@unlink($path2Components.$arDeleteFiles[$i]);
			}
		}

		return $this->CopyDirFiles($this->curModulePath."/install/components".$componentPath, US_SHARED_KERNEL_PATH."/components".$componentPath);
	}


	// Устанавливает все мастера
	// $arDeleteFiles = array("component.name" => array("/images/1.gif", "/templates/.default/style.css"), "component.name1" => array("/style.css"));
	public function InstallWizards($arDeleteFiles = array())
	{
		if (!in_array("KERNEL", $this->callType))
			return True;

		CUpdateSystem::AddMessage2Log("Run updater '".$this->updater."': InstallWizards()", "CRUPDICS1");
		$errorMessage = "";

		$bFlag = True;

		$wizardsPath = $_SERVER["DOCUMENT_ROOT"].$this->curModulePath."/install/wizards";
		if ($handle = @opendir($wizardsPath))
		{
			while (($dir = readdir($handle)) !== false)
			{
				if ($dir == "." || $dir == ".." || !is_dir($wizardsPath."/".$dir))
					continue;

				if (file_exists($wizardsPath."/".$dir."/wizard.php"))
				{
					$bFlag = $bFlag && $this->InstallWizard($dir, (array_key_exists($dir, $arDeleteFiles) ? $arDeleteFiles[$dir] : array()));
				}
				else
				{
					if ($handle1 = @opendir($wizardsPath."/".$dir))
					{
						while (($dir1 = readdir($handle1)) !== false)
						{
							if ($dir1 == "." || $dir1 == ".." || !is_dir($wizardsPath."/".$dir."/".$dir1))
								continue;

							if (file_exists($wizardsPath."/".$dir."/".$dir1."/wizard.php"))
							{
								$bFlag = $bFlag && $this->InstallWizard($dir.":".$dir1, (array_key_exists($dir.":".$dir1, $arDeleteFiles) ? $arDeleteFiles[$dir.":".$dir1] : array()));
							}
						}
						@closedir($handle1);
					}
				}
			}
			@closedir($handle);
		}

		return $bFlag;
	}

	// Устанавливает мастер по его имени
	// $arDeleteFiles - удаляет файлы из массива ( Array("/images/1.gif", "/templates/.default/style.css") )
	public function InstallWizard($wizardName, $arDeleteFiles = array())
	{
		if (!in_array("KERNEL", $this->callType))
			return True;

		CUpdateSystem::AddMessage2Log("Run updater '".$this->updater."': InstallWizard(".$wizardName.")", "CRUPDIC1");
		$errorMessage = "";

		$wizardPath = $this->__MakeComponentPath($wizardName);
		if (StrLen($wizardPath) <= 0)
		{
			CUpdateSystem::AddMessage2Log("Wrong wizard name", "CRUPDIC2");
			return False;
		}

		if (!file_exists($_SERVER["DOCUMENT_ROOT"].$this->curModulePath."/install/wizards".$wizardPath))
		{
			CUpdateSystem::AddMessage2Log("Wizard is not found", "CRUPDIC3");
			return False;
		}

		if (count($arDeleteFiles) > 0)
		{
			for ($i = 0, $cnt = count($arDeleteFiles); $i < $cnt; $i++)
			{
				$path2Modules = $_SERVER["DOCUMENT_ROOT"].US_SHARED_KERNEL_PATH."/modules/".$this->moduleID."/install/wizards".$wizardPath;
				$path2Wizards = $_SERVER["DOCUMENT_ROOT"].US_SHARED_KERNEL_PATH."/wizards/".$wizardPath;

				if (file_exists($path2Modules.$arDeleteFiles[$i]))
					@unlink($path2Modules.$arDeleteFiles[$i]);
				if (file_exists($path2Wizards.$arDeleteFiles[$i]))
					@unlink($path2Wizards.$arDeleteFiles[$i]);
			}
		}

		return $this->CopyDirFiles($this->curModulePath."/install/wizards".$wizardPath, US_SHARED_KERNEL_PATH."/wizards".$wizardPath);
	}

	public function CopyFiles($fromDir, $toDir)
	{
		if (!in_array("KERNEL", $this->callType))
			return True;

		CUpdateSystem::AddMessage2Log("Run updater '".$this->updater."': CopyFiles(".$fromDir.", ".$toDir.")", "CRUPDCF1");

		$errorMessage = "";

		if (substr($fromDir, 0, 1) != "/")
			$fromDir = $this->curModulePath."/".$fromDir;
		if (substr($toDir, 0, 1) != "/")
			$toDir = $this->kernelPath."/".$toDir;

		$fromDirFull = $_SERVER["DOCUMENT_ROOT"].$fromDir;
		$toDirFull = $_SERVER["DOCUMENT_ROOT"].$toDir;

		$result = CUpdateSystem::CopyDirFiles($fromDirFull, $toDirFull, $errorMessage);

		if (!$result)
			$this->errorMessage[] = $errorMessage;

		return $result;
	}

	public function CopyDirFiles($fromDir, $toDir)
	{
		if (!in_array("KERNEL", $this->callType))
			return True;

		CUpdateSystem::AddMessage2Log("Run updater '".$this->updater."': CopyDirFiles(".$fromDir.", ".$toDir.")", "CRUPDCDF1");
		$errorMessage = "";

		if (substr($fromDir, 0, 1) != "/")
			$fromDir = $this->curPath."/".$fromDir;

		$fromDirFull = $_SERVER["DOCUMENT_ROOT"].$fromDir;
		$toDirFull = $_SERVER["DOCUMENT_ROOT"].$toDir;

		$result = CUpdateSystem::CopyDirFiles($fromDirFull, $toDirFull, $errorMessage);

		if (!$result)
			$this->errorMessage[] = $errorMessage;

		return $result;
	}

	public function Query($query, $tableName = "")
	{
		if (!in_array("DATABASE", $this->callType))
			return False;

		$bCanUpdate = True;
		if (strlen($tableName) > 0)
			if (!$this->TableExists($tableName))
				$bCanUpdate = False;

		$result = False;

		if ($bCanUpdate)
		{
			$strQuery = "";

			if (is_array($query))
			{
				foreach ($query as $key => $value)
				{
					if ($this->dbType == StrToUpper($key))
					{
						$strQuery = $value;
						break;
					}
				}
			}
			else
			{
				$strQuery = $query;
			}

			if (strlen($strQuery) <= 0)
				$bCanUpdate = False;
		}

		CUpdateSystem::AddMessage2Log("Run updater '".$this->updater."': Query(".$strQuery.", ".$tableName.")", "CRUPDCDF2");

		if ($bCanUpdate)
		{
			$result = $GLOBALS["DB"]->Query($strQuery, True);

			if (!$result)
				$this->errorMessage[] = $GLOBALS["DB"]->db_Error;
		}

		return $result;
	}

	public function QueryBatch($queryPath, $tableName = "")
	{
		if (!in_array("DATABASE", $this->callType))
			return False;

		CUpdateSystem::AddMessage2Log("Run updater '".$this->updater."': QueryBatch(".$queryPath.", ".$tableName.")", "CRUPDCDF3");

		$bCanUpdate = True;
		if (strlen($tableName) > 0)
			if (!$this->TableExists($tableName))
				$bCanUpdate = False;

		$result = False;

		if ($bCanUpdate)
		{
			$strQueryPath = "";

			if (is_array($queryPath))
			{
				foreach ($queryPath as $key => $value)
				{
					if ($this->dbType == StrToUpper($key))
					{
						$strQueryPath = $value;
						break;
					}
				}
			}
			else
			{
				$strQueryPath = $queryPath;
			}

			if (strlen($strQueryPath) <= 0)
				$bCanUpdate = False;
		}

		if ($bCanUpdate)
		{
			if (substr($strQueryPath, 0, 1) != "/")
				$strQueryPath = $this->curPath."/".$strQueryPath;

			$queryPathFull = $_SERVER["DOCUMENT_ROOT"].$strQueryPath;

			$arError = False;
			if (file_exists($queryPathFull))
				$arError = $GLOBALS["DB"]->RunSQLBatch($queryPathFull);

			if ($arError)
			{
				foreach ($arError as $key => $value)
					$this->errorMessage[] = $value;
			}
		}

		return ($arError ? False : True);
	}

	public function TableExists($tableName)
	{
		if (!in_array("DATABASE", $this->callType))
			return False;

		$tableName = preg_replace("/[^A-Za-z0-9%_]+/i", "", $tableName);
		$tableName = Trim($tableName);

		if (strlen($tableName) <= 0)
			return False;

		$strSql = "";
		if ($this->dbType == "MYSQL")
			$strSql = "SHOW TABLES LIKE '".strtolower($GLOBALS["DB"]->ForSql($tableName))."'";
		elseif ($this->dbType == "ORACLE")
			$strSql = "SELECT TABLE_NAME FROM USER_TABLES WHERE TABLE_NAME LIKE UPPER('".strtoupper($GLOBALS["DB"]->ForSql($tableName))."')";
		elseif ($this->dbType == "MSSQL")
			$strSql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '".strtoupper($GLOBALS["DB"]->ForSql($tableName))."'";

		$dbResult = $GLOBALS["DB"]->Query($strSql);
		if ($arResult = $dbResult->Fetch())
			return True;
		else
			return False;
	}

	public function CanUpdateDatabase()
	{
		return (in_array("DATABASE", $this->callType));
	}

	public function CanUpdateKernel()
	{
		return (in_array("KERNEL", $this->callType));
	}

	public function CanUpdatePersonalFiles()
	{
		return (in_array("PERSONAL", $this->callType));
	}
}
?>