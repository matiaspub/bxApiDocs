<?
$US_HOST_PROCESS_MAIN = ($US_HOST_PROCESS_MAIN ? True : False);

// define("US_CALL_TYPE", "DB");
// define("US_SAVE_UPDATERS_DIR", "/bitrix/updaters");
// define("US_DB_VERSIONS_FILE", $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/versions.php");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");

if (!function_exists("DBUpdaterCheckUpdates"))
{
	function DBUpdaterLock()
	{
		global $DB, $APPLICATION;

		$uniq = $APPLICATION->GetServerUniqID();

		if ($DB->type == "MYSQL")
		{
			$dbLock = $DB->Query("SELECT GET_LOCK('".$uniq."_DBUpdater', 0) as L", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$arLock = $dbLock->Fetch();
			if ($arLock["L"] == "1")
				return true;
			else
				return false;
		}
		elseif ($DB->type == "ORACLE")
		{
			$dbLock = $DB->Query("
				declare
					my_lock_id number;
					my_result number;
					lock_failed exception;
					pragma exception_init(lock_failed, -54);
				begin
					my_lock_id:=dbms_utility.get_hash_value(to_char('".$uniq."_DBUpdater'), 0, 1024);
					my_result:=dbms_lock.request(my_lock_id, dbms_lock.x_mode, 0, true);
					--  Return value:
					--    0 - success
					--    1 - timeout
					--    2 - deadlock
					--    3 - parameter error
					--    4 - already own lock specified by 'id' or 'lockhandle'
					--    5 - illegal lockhandle
					if(my_result<>0 and my_result<>4)then
						raise lock_failed;
					end if;
				end;
			", true);
			return ($dbLock !== false);
		}
		else
		{
			$i = 60;
			$DB->Query("DELETE FROM B_OPTION WHERE MODULE_ID = 'main' AND NAME = '".$uniq."_DBUpdater' AND SITE_ID IS NULL AND DATEDIFF(SECOND, CONVERT(DATETIME, DESCRIPTION), GETDATE()) > ".$i, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("SET LOCK_TIMEOUT 1", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$dbLock = $DB->Query("INSERT INTO B_OPTION(MODULE_ID, NAME, SITE_ID, VALUE, DESCRIPTION) VALUES ('main', '".$uniq."_DBUpdater', NULL,  NULL, CONVERT(VARCHAR(128), GETDATE()))", true);
			$DB->Query("SET LOCK_TIMEOUT -1", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			return ($dbLock !== false);
		}
	}

	function DBUpdaterUnLock()
	{
		global $DB, $APPLICATION;

		$uniq = $APPLICATION->GetServerUniqID();

		if ($DB->type == "MYSQL")
		{
			$dbLock = $DB->Query("SELECT RELEASE_LOCK('".$uniq."_DBUpdater') as L", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$arLock = $dbLock->Fetch();
			if($arLock["L"] == "0")
				return false;
			else
				return true;
		}
		elseif ($DB->type == "ORACLE")
		{
			return true;
		}
		else
		{
			$DB->Query("DELETE FROM B_OPTION WHERE MODULE_ID = 'main' AND NAME = '".$uniq."_DBUpdater' AND SITE_ID IS NULL", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			return true;
		}
	}

	function DBUpdaterCheckUpdates($US_HOST_PROCESS_MAIN)
	{
		if (!file_exists(US_DB_VERSIONS_FILE))
			DBUpdaterCollectDBVersionsNew("A", "", "");

		$arDBVersions = array();
		include(US_DB_VERSIONS_FILE);

		if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/versions.php"))
			return array();
		$arVersions = array();
		include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/versions.php");

		$arResult = array();
		foreach ($arDBVersions as $moduleID => $dbVersion)
		{
			if ($US_HOST_PROCESS_MAIN && $moduleID != "main" || !$US_HOST_PROCESS_MAIN && $moduleID == "main")
				continue;

			if (array_key_exists($moduleID, $arVersions))
			{
				if (CUpdateClient::CompareVersions($arVersions[$moduleID], $dbVersion) > 0)
					$arResult[$moduleID] = $dbVersion;
			}
		}

		return $arResult;
	}


	function DBUpdaterCollectDBVersionsNew($collectTypeParam, $moduleIdParam, $versionIdParam)
	{
		$arDBVersions = array();
		include(US_DB_VERSIONS_FILE);

		@unlink(US_DB_VERSIONS_FILE);

		$errorMessage = "";
		$arDBVersionsNew = CUpdateClient::GetCurrentModules($errorMessage, false);

		if (StrLen($errorMessage) <= 0)
		{
			$f = fopen(US_DB_VERSIONS_FILE, "w");
			fwrite($f, "<"."?\n");
			fwrite($f, "\$arDBVersions = array(\n");
			foreach ($arDBVersionsNew as $moduleID => $version)
			{
				if (!array_key_exists($moduleID, $arDBVersions))
					$arDBVersions[$moduleID] = $version;

				if ($collectTypeParam == "A")
				{
					fwrite($f, "\t\"".htmlspecialcharsbx($moduleID)."\" => \"".htmlspecialcharsbx($version)."\",\n");
				}
				elseif ($collectTypeParam == "M")
				{
					if ($moduleID == "main")
						fwrite($f, "\t\"".htmlspecialcharsbx($moduleID)."\" => \"".htmlspecialcharsbx($version)."\",\n");
					else
						fwrite($f, "\t\"".htmlspecialcharsbx($moduleID)."\" => \"".htmlspecialcharsbx($arDBVersions[$moduleID])."\",\n");
				}
				elseif ($collectTypeParam == "O")
				{
					if ($moduleID != "main")
						fwrite($f, "\t\"".htmlspecialcharsbx($moduleID)."\" => \"".htmlspecialcharsbx($version)."\",\n");
					else
						fwrite($f, "\t\"".htmlspecialcharsbx($moduleID)."\" => \"".htmlspecialcharsbx($arDBVersions[$moduleID])."\",\n");
				}
				elseif ($collectTypeParam == "N")
				{
					if ($moduleID == $moduleIdParam)
						fwrite($f, "\t\"".htmlspecialcharsbx($moduleID)."\" => \"".htmlspecialcharsbx($versionIdParam)."\",\n");
					else
						fwrite($f, "\t\"".htmlspecialcharsbx($moduleID)."\" => \"".htmlspecialcharsbx($arDBVersions[$moduleID])."\",\n");
				}
			}
			fwrite($f, ");\n");
			fwrite($f, "?".">");
			fclose($f);
		}
		else
		{
			CControllerClient::SendMessage("SITE_UPDATE_KERNEL_DB", "N", $errorMessage);
		}
	}

	function DBUpdaterUpdateFromVersion($moduleID, $dbVersion)
	{
		if (StrLen($moduleID) <= 0)
			return;
		if (StrLen($dbVersion) <= 0)
			return;

		$errorMessage = "";

		if (file_exists($_SERVER["DOCUMENT_ROOT"].US_SAVE_UPDATERS_DIR."/".$moduleID) && is_dir($_SERVER["DOCUMENT_ROOT"].US_SAVE_UPDATERS_DIR."/".$moduleID))
		{
			$arUpdaters = array();

			if ($handle = @opendir($_SERVER["DOCUMENT_ROOT"].US_SAVE_UPDATERS_DIR."/".$moduleID))
			{
				while (false !== ($dir = readdir($handle)))
				{
					if ($dir == "." || $dir == "..")
						continue;

					if (substr($dir, 0, 7) == "updater")
					{
						if (is_file($_SERVER["DOCUMENT_ROOT"].US_SAVE_UPDATERS_DIR."/".$moduleID."/".$dir))
						{
							$num = substr($dir, 7, strlen($dir) - 11);
							if (substr($dir, strlen($dir) - 9) == "_post.php")
								$num = substr($dir, 7, strlen($dir) - 16);

							$arUpdaters[] = array("/".$dir, Trim($num));
						}
						elseif (file_exists($_SERVER["DOCUMENT_ROOT"].US_SAVE_UPDATERS_DIR."/".$moduleID."/".$dir."/index.php"))
						{
							$num = substr($dir, 7);
							if (substr($dir, strlen($dir) - 5) == "_post")
								$num = substr($dir, 7, strlen($dir) - 12);

							$arUpdaters[] = array("/".$dir."/index.php", Trim($num));
						}
					}
				}
				closedir($handle);
			}

			for ($i1 = 0; $i1 < count($arUpdaters) - 1; $i1++)
			{
				for ($j1 = $i1 + 1; $j1 < count($arUpdaters); $j1++)
				{
					if (CUpdateClient::CompareVersions($arUpdaters[$i1][1], $arUpdaters[$j1][1]) > 0)
					{
						$tmp1 = $arUpdaters[$i1];
						$arUpdaters[$i1] = $arUpdaters[$j1];
						$arUpdaters[$j1] = $tmp1;
					}
				}
			}

			for ($i1 = 0; $i1 < count($arUpdaters); $i1++)
			{
				if (CUpdateClient::CompareVersions($arUpdaters[$i1][1], $dbVersion) <= 0)
					continue;

				$errorMessageTmp = "";

				CUpdateClient::RunUpdaterScript($_SERVER["DOCUMENT_ROOT"].US_SAVE_UPDATERS_DIR."/".$moduleID.$arUpdaters[$i1][0], $errorMessageTmp, "", $moduleID);
				if (strlen($errorMessageTmp) > 0)
					$errorMessage .= str_replace("#MODULE#", $moduleID, str_replace("#VER#", $arUpdaters[$i1][1], GetMessage("SUPP_UK_UPDN_ERR"))).": ".$errorMessageTmp.".<br>";

				DBUpdaterCollectDBVersionsNew("N", $moduleID, $arUpdaters[$i1][1]);
			}
		}

		if (StrLen($errorMessage) > 0)
			CControllerClient::SendMessage("SITE_UPDATE_KERNEL_DB", "N", $errorMessage);
	}
}

$arDBVersions = DBUpdaterCheckUpdates($US_HOST_PROCESS_MAIN);

if (count($arDBVersions) > 0)
{
	@set_time_limit(0);
	ini_set("track_errors", "1");
	ignore_user_abort(true);

	if (DBUpdaterLock())
	{
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/controller_member.php");

		foreach ($arDBVersions as $moduleID => $dbVersion)
			DBUpdaterUpdateFromVersion($moduleID, $dbVersion);

		DBUpdaterCollectDBVersionsNew($US_HOST_PROCESS_MAIN ? "M" : "O", "", "");

		CControllerClient::SendMessage("SITE_UPDATE_KERNEL_DB", "Y", "");

		DBUpdaterUnLock();

		LocalRedirect($_SERVER["REQUEST_URI"]);
	}
	else
	{
		echo "Web site is now updating. Please wait for about one minute.";
		die();
	}
}
?>