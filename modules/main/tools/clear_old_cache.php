<?
// define("STOP_STATISTICS", true);
// define("NOT_CHECK_PERMISSIONS", true);
set_time_limit(1800);
// define("LANG", "ru");

$strLOG_FILE = $_SERVER["DOCUMENT_ROOT"]."/upload/clear_cache_files.log";

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$dLog = fopen($strLOG_FILE, "wb");
fwrite($dLog, date("r")."\n");
fwrite($dLog, mktime()."\n");

$iGoodNum = 0;
$iOldNum = 0;
$iEmptyDirNum = 0;

function CheckCacheFiles_Rec($strDir)
{
	global $iGoodNum, $iOldNum, $iEmptyDirNum, $dLog;

	if ($handle = @opendir($strDir))
	{
		while (($file = readdir($handle)) !== false)
		{
			if ($file == "." || $file == "..") continue;

			if (is_dir($strDir."/".$file))
			{
				CheckCacheFiles_Rec($strDir."/".$file);
			}
			elseif (is_file($strDir."/".$file))
			{
				$ext = "";
				$ext_pos = bxstrrpos($file, ".");
				if ($ext_pos!==false)
					$ext = substr($file, $ext_pos + 1);

				$bCacheExp = False;
				if ($ext=="html")
					$bCacheExp = CPageCache::IsCacheExpired($strDir."/".$file);
				elseif ($ext=="php")
					$bCacheExp = CPHPCache::IsCacheExpired($strDir."/".$file);

				if ($bCacheExp)
				{
					$iOldNum++;
					@unlink($strDir."/".$file);
				}
				else
				{
					$iGoodNum++;
				}
			}
		}
		@closedir($handle);
	}

	clearstatcache();

	$bEmptyFolder = True;
	if ($handle = @opendir($strDir))
	{
		while (($file = readdir($handle)) !== false)
		{
			if ($file == "." || $file == "..") continue;
			$bEmptyFolder = False;
			break;
		}
	}

	if ($bEmptyFolder)
	{
		$iEmptyDirNum++;
		@rmdir($strDir);
	}
}

list($usec, $sec) = explode(" ", microtime());
$start_time = ((float)$usec + (float)$sec);

CheckCacheFiles_Rec($_SERVER["DOCUMENT_ROOT"]."/bitrix/cache");

list($usec, $sec) = explode(" ", microtime());
$end_time = ((float)$usec + (float)$sec);

fwrite($dLog, "\nTime - ".round($end_time-$start_time, 3)." sec\n");

fwrite($dLog, "\nFiles deleted - ".$iOldNum."\n");
fwrite($dLog, "Empty folders removed - ".$iEmptyDirNum."\n");
fwrite($dLog, "\nFiles up to date - ".$iGoodNum."\n");

fwrite($dLog, "\nDone\n");
fclose($dLog);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>