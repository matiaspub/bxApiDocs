<?
class CCompress
{
	public static function OnPageStart()
	{
		ob_start();
		ob_start(); // second buffering envelope for PHP URL rewrite, see http://bugs.php.net/bug.php?id=35933
		ob_implicit_flush(0);
	}

	public static function OnAfterEpilog()
	{
		global $USER;

		$canEditPHP = $USER->CanDoOperation('edit_php');
		$bShowTime = isset($_SESSION["SESS_SHOW_TIME_EXEC"]) && ($_SESSION["SESS_SHOW_TIME_EXEC"] == 'Y');
		$bShowStat = ($GLOBALS["DB"]->ShowSqlStat && ($canEditPHP || $_SESSION["SHOW_SQL_STAT"]=="Y"));
		$bShowCacheStat = (\Bitrix\Main\Data\Cache::getShowCacheStat() && ($canEditPHP || $_SESSION["SHOW_CACHE_STAT"]=="Y"));
		$bExcel = isset($_REQUEST["mode"]) && $_REQUEST["mode"] === 'excel';
		$ENCODING = CCompress::CheckCanGzip();
		if($ENCODING !== 0)
		{
			$level = 4;

			if (isset($_GET["compress"]))
			{
				if ($_GET["compress"] === "Y" || $_GET["compress"] === "y")
					$_SESSION["SESS_COMPRESS"] = "Y";
				elseif ($_GET["compress"] === "N" || $_GET["compress"] === "n")
					unset($_SESSION["SESS_COMPRESS"]);
			}

			if(!defined("ADMIN_AJAX_MODE") && !defined('PUBLIC_AJAX_MODE') && !$bExcel)
			{
				if($bShowTime || $bShowStat || $bShowCacheStat)
				{
					$main_exec_time = round((getmicrotime()-START_EXEC_TIME), 4);
					include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/interface/debug_info.php");
				}

				if(isset($_SESSION["SESS_COMPRESS"]) && $_SESSION["SESS_COMPRESS"] == "Y")
					include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/compression/table.php");
			}

			ob_end_flush();
			$Contents = ob_get_contents();
			ob_end_clean();

			if(!defined("BX_SPACES_DISABLED") || BX_SPACES_DISABLED!==true)
				if((strpos($GLOBALS["HTTP_USER_AGENT"], "MSIE 5")>0 || strpos($GLOBALS["HTTP_USER_AGENT"], "MSIE 6.0")>0) && strpos($GLOBALS["HTTP_USER_AGENT"], "Opera")===false)
					$Contents = str_repeat(" ", 2048)."\r\n".$Contents;

			$Size = function_exists("mb_strlen")? mb_strlen($Contents, 'latin1'): strlen($Contents);
			$Crc = crc32($Contents);
			$Contents = gzcompress($Contents, $level);
			$Contents = function_exists("mb_substr")? mb_substr($Contents, 0, -4, 'latin1'): substr($Contents, 0, -4);

			header("Content-Encoding: $ENCODING");
			print "\x1f\x8b\x08\x00\x00\x00\x00\x00";
			print $Contents;
			print pack('V',$Crc);
			print pack('V',$Size);
		}
		else
		{
			ob_end_flush();
			ob_end_flush();
			if(($bShowTime || $bShowStat || $bShowCacheStat) && !defined("ADMIN_AJAX_MODE") && !defined('PUBLIC_AJAX_MODE'))
			{
				$main_exec_time = round((getmicrotime()-START_EXEC_TIME), 4);
				include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/interface/debug_info.php");
			}
		}
	}

	public static function DisableCompression()
	{
		// define("BX_COMPRESSION_DISABLED", true);
	}

	public static function Disable2048Spaces()
	{
		// define("BX_SPACES_DISABLED", true);
	}

	public static function CheckCanGzip()
	{
		if(!function_exists("gzcompress")) return 0;
		if(defined("BX_COMPRESSION_DISABLED") && BX_COMPRESSION_DISABLED===true) return 0;
		if(headers_sent() || connection_aborted()) return 0;
		if(ini_get('zlib.output_compression') == 1) return 0;
		if($GLOBALS["HTTP_ACCEPT_ENCODING"] == '') return 0;
		if(strpos($_SERVER["HTTP_ACCEPT_ENCODING"],'x-gzip') !== false) return "x-gzip";
		if(strpos($_SERVER["HTTP_ACCEPT_ENCODING"],'gzip') !== false) return "gzip";
		return 0;
	}
}
?>