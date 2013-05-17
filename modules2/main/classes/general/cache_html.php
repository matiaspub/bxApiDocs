<?
/*. require_module 'standard'; .*/
/*. require_module 'session'; .*/
/*. require_module 'zlib'; .*/
/*. require_module 'pcre'; .*/

class CHTMLPagesCache
{
/*. void .*/
	public static function startCaching()
	{
		$HTML_PAGES_ROOT = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages";
		if(
			$_SERVER["REQUEST_METHOD"] === "POST"
			|| array_key_exists(session_name(), $_COOKIE)
			|| array_key_exists(session_name(), $_REQUEST)
			|| (strncmp($_SERVER["REQUEST_URI"], BX_ROOT, strlen(BX_ROOT)) == 0)
			|| (strncmp($_SERVER["REQUEST_URI"], BX_PERSONAL_ROOT, strlen(BX_ROOT)) == 0)
			|| (preg_match("#^/index_controller\\.php#", $_SERVER["REQUEST_URI"]) > 0)
		)
		{
			return;
		}

		$arHTMLPagesOptions = array();
		if(file_exists($HTML_PAGES_ROOT."/.config.php"))
			include($HTML_PAGES_ROOT."/.config.php");

		//Check for stored authorization
		if(
			isset($arHTMLPagesOptions["STORE_PASSWORD"]) && $arHTMLPagesOptions["STORE_PASSWORD"] == "Y"
			&& isset($_COOKIE[$arHTMLPagesOptions["COOKIE_LOGIN"]]) && $_COOKIE[$arHTMLPagesOptions["COOKIE_LOGIN"]] <> ''
			&& isset($_COOKIE[$arHTMLPagesOptions["COOKIE_PASS"]]) && $_COOKIE[$arHTMLPagesOptions["COOKIE_PASS"]] <> ''
		)
		{
			return;
		}

		//Check for masks
		$p = strpos($_SERVER["REQUEST_URI"], "?");
		if($p === false)
			$PAGES_FILE = $_SERVER["REQUEST_URI"];
		else
			$PAGES_FILE = substr($_SERVER["REQUEST_URI"], 0, $p);
		if(is_array($arHTMLPagesOptions["~EXCLUDE_MASK"]))
		{
			foreach($arHTMLPagesOptions["~EXCLUDE_MASK"] as $mask)
			{
				if(preg_match($mask, $PAGES_FILE) > 0)
				{
					return;
				}
			}
		}
		if(is_array($arHTMLPagesOptions["~INCLUDE_MASK"]))
		{
			foreach($arHTMLPagesOptions["~INCLUDE_MASK"] as $mask)
			{
				if(preg_match($mask, $PAGES_FILE) > 0)
				{
					$PAGES_FILE = "*";
					break;
				}
			}
		}
		if($PAGES_FILE !== "*")
			return;

		$arMatch = array();
		if(preg_match("#^(/.+?)\\.php\\?(.*)#", $_SERVER["REQUEST_URI"], $arMatch) > 0)
		{
			if(strpos($arMatch[2], "\\")!==false || strpos($arMatch[2], "/")!==false)
				return;
			$PAGES_FILE = $HTML_PAGES_ROOT.$arMatch[1]."@".$arMatch[2].".html";
		}
		elseif(preg_match("#^(/.+)\\.php$#", $_SERVER["REQUEST_URI"], $arMatch) > 0)
		{
			$PAGES_FILE = $HTML_PAGES_ROOT.$arMatch[1]."@.html";
		}
		if(preg_match("#^(.*?)/\\?(.*)#", $_SERVER["REQUEST_URI"], $arMatch) > 0)
		{
			if(strpos($arMatch[2], "\\")!==false || strpos($arMatch[2], "/")!==false)
				return;
			if(strlen($arMatch[1]) && substr($arMatch[1], 0, 1)!=="/")
				return;
			$PAGES_FILE = $HTML_PAGES_ROOT.$arMatch[1]."/index@".$arMatch[2].".html";
		}
		elseif(preg_match("#^(.*)/$#", $_SERVER["REQUEST_URI"], $arMatch) > 0)
		{
			if(strlen($arMatch[1]) && substr($arMatch[1], 0, 1)!=="/")
				return;
			$PAGES_FILE = $HTML_PAGES_ROOT.$arMatch[1]."/index@.html";
		}
		//This checks for invalid symbols
		//TODO: make it Windows compatible
		if(preg_match("/(\\?|\\*|\\.\\.)/", $PAGES_FILE) > 0)
			return;

		if(file_exists($PAGES_FILE))
		{
			//Update statistic
			CHTMLPagesCache::writeStatistic(1);

			$mtime = filemtime($PAGES_FILE);
			$fsize = filesize($PAGES_FILE);

			//Handle ETag
			$ETag = md5($PAGES_FILE.$fsize.$mtime);
			if(array_key_exists("HTTP_IF_NONE_MATCH", $_SERVER) && ($_SERVER['HTTP_IF_NONE_MATCH'] === $ETag))
			{
				CHTMLPagesCache::SetStatus("304 Not Modified");
				die();
			}
			header("ETag: ".$ETag);

			//Handle Last Modified
			$lastModified = gmdate('D, d M Y H:i:s', $mtime).' GMT';
			if(array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER) && ($_SERVER['HTTP_IF_MODIFIED_SINCE'] === $lastModified))
			{
				CHTMLPagesCache::SetStatus("304 Not Modified");
				die();
			}
			header("Expires: Fri, 7 Jun 1974 04:00:00 GMT");
			header('Last-Modified: '.$lastModified);

			$fp = fopen($PAGES_FILE, "rb");
			if($fp !== false)
			{
				if(version_compare(phpversion(), "5.3.0", "<") && (($v = ini_get("magic_quotes_runtime")) == 1 || strtolower($v) == 'on'))
					set_magic_quotes_runtime(0);

				$contents = fread($fp, $fsize);
				fclose($fp);

				//Try to parse charset encoding
				$head_end = strpos($contents, "</head>");
				if($head_end !== false)
				{
					if(preg_match("#<meta\\s+http-equiv\\s*=\\s*(['\"])Content-Type(\\1)\\s+content\\s*=\\s*(['\"])(.*?)(\\3)#im", substr($contents, 0, $head_end), $arMatch))
					{
						header("Content-type: ".$arMatch[4]);
					}
				}

				//compression support
				$compress = "";
				if($arHTMLPagesOptions["COMPRESS"])
				{
					if(isset($_SERVER["HTTP_ACCEPT_ENCODING"]))
					{
						if(strpos($_SERVER["HTTP_ACCEPT_ENCODING"],'x-gzip') !== false)
							$compress = "x-gzip";
						elseif(strpos($_SERVER["HTTP_ACCEPT_ENCODING"],'gzip') !== false)
							$compress = "gzip";
					}
				}

				if($compress)
				{
					if(isset($_SERVER["HTTP_USER_AGENT"]))
					{
						$USER_AGENT = $_SERVER["HTTP_USER_AGENT"];
						if((strpos($USER_AGENT, "MSIE 5")>0 || strpos($USER_AGENT, "MSIE 6.0")>0) && strpos($USER_AGENT, "Opera")===false)
							$contents = str_repeat(" ", 2048)."\r\n".$contents;
					}
					$Size = function_exists("mb_strlen")? mb_strlen($contents, 'latin1'): strlen($contents);
					$Crc = crc32($contents);
					$contents = gzcompress($contents, 4);
					$contents = function_exists("mb_substr")? mb_substr($contents, 0, -4, 'latin1'): substr($contents, 0, -4);

					header("Content-Encoding: $compress");
					echo "\x1f\x8b\x08\x00\x00\x00\x00\x00",$contents,pack('V',$Crc),pack('V',$Size);
				}
				else
				{
					header("Content-Length: ".filesize($PAGES_FILE));
					echo $contents;
				}
				die();
			}
		}
		else//if(file_exists($PAGES_FILE))
		{
			// define('HTML_PAGES_FILE', $PAGES_FILE);
		}
	}

	//Deletes all above html_pages
/*. float .*/
	public static function deleteRecursive(/*. string .*/$path = "")
	{
		$base_bir = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages";
		//Estimate for freed space
		$bytes = 0.0;
		//Check if someone is trieng to escape from $base_dir
		if(strpos($path, "..")!==false)
			return 0;

		$dh = false;
		if(is_dir($base_bir.$path))
			$dh = opendir($base_bir.$path);
		if($dh !== false)
		{
			while(($file = readdir($dh)) !== false)
			{
				if(
					$file === "."
					|| $file === ".."
					|| $file === "404.php"
					|| $file === ".htaccess"
					|| $file === ".config.php"
					|| $file === ".enabled"
				)
					continue;

				$file_path = $base_bir.$path."/".$file;
				if(is_dir($file_path))
				{
					$bytes += CHTMLPagesCache::deleteRecursive($path.$file."/");
					@rmdir($file_path);
				}
				elseif(is_file($file_path))
				{
					$bytes += filesize($file_path);
					unlink($file_path);
				}
			}
			closedir($dh);
		}
		return doubleval($bytes);
	}

	public static function OnEpilog()
	{
		global $USER;

		$bAutorized = is_object($USER) && $USER->IsAuthorized();
		if(!$bAutorized && defined("HTML_PAGES_FILE"))
		{
			@setcookie(session_name(), "", time()-360000, "/");
		}

		$bExcludeByFile = $_SERVER["SCRIPT_NAME"] == "/bitrix/admin/get_start_menu.php";

		$posts = 0;
		$bytes = 0.0;
		$all_clean = false;

		//Check if modifyng action happend
		if(($_SERVER["REQUEST_METHOD"] === "POST") || ($bAutorized && check_bitrix_sessid() && !$bExcludeByFile))
		{
			//if it was admin post
			if(strncmp($_SERVER["REQUEST_URI"], "/bitrix/", 8) === 0)
			{
				//Then will clean all the cache
				$bytes = CHTMLPagesCache::deleteRecursive("/");
				$all_clean = true;
			}
			//check if it was SEF post
			elseif(array_key_exists("SEF_APPLICATION_CUR_PAGE_URL", $_REQUEST) && file_exists($_SERVER['DOCUMENT_ROOT']."/urlrewrite.php"))
			{
				$arUrlRewrite = array();
				include($_SERVER['DOCUMENT_ROOT']."/urlrewrite.php");
				foreach($arUrlRewrite as $val)
				{
					if(preg_match($val["CONDITION"], $_SERVER["REQUEST_URI"]) > 0)
					{
						if (strlen($val["RULE"]) > 0)
							$url = preg_replace($val["CONDITION"], (StrLen($val["PATH"]) > 0 ? $val["PATH"]."?" : "").$val["RULE"], $_SERVER["REQUEST_URI"]);
						else
							$url = $val["PATH"];

						$pos=strpos($url, "?");
						if($pos !== false)
						{
							$url = substr($url, 0, $pos);
						}
						$url = substr($url, 0, strrpos($url, "/")+1);
						$bytes = CHTMLPagesCache::deleteRecursive($url);
						break;
					}
				}
			}
			//public page post
			else
			{
				$folder = substr($_SERVER["REQUEST_URI"], 0, strrpos($_SERVER["REQUEST_URI"], "/"));
				$bytes = CHTMLPagesCache::deleteRecursive($folder);
			}
			$posts++;
		}

		if($bytes > 0.0 && class_exists("cdiskquota"))
		{
			CDiskQuota::updateDiskQuota("file", $bytes, "delete");
		}

		if($posts || $bytes)
		{
			CHTMLPagesCache::writeStatistic(
				0, //hit
				0, //miss
				0, //quota
				$posts, //posts
				($all_clean? false: -$bytes) //files
			);
		}
	}

	public static function CleanAll()
	{
		$bytes = CHTMLPagesCache::deleteRecursive("/");
		if($bytes > 0.0 && class_exists("cdiskquota"))
		{
			CDiskQuota::updateDiskQuota("file", $bytes, "delete");
		}
	}

	public static function writeFile($file_name, $content)
	{
		global $USER;
		if(is_object($USER) && $USER->IsAuthorized())
			return;

		$content_len = function_exists('mb_strlen')? mb_strlen($content, 'latin1'): strlen($content);
		if($content_len <= 0)
			return;

		$arHTMLPagesOptions = CHTMLPagesCache::GetOptions(true);
		if(!is_array($arHTMLPagesOptions))
			return;

		//Let's be pessimists
		$bQuota = false;

		if(class_exists("cdiskquota"))
		{
			$quota = new CDiskQuota();
			if($quota->checkDiskQuota(array("FILE_SIZE" => $content_len)))
				$bQuota = true;
		}
		else
		{
			$bQuota = true;
		}

		$arStat = CHTMLPagesCache::readStatistic();
		if($arStat)
			$cached_size = $arStat["FILE_SIZE"];
		else
			$cached_size = 0.0;

		$cache_quota = doubleval($arHTMLPagesOptions["~FILE_QUOTA"]);
		if($bQuota && ($cache_quota > 0.0))
		{
			if($cache_quota  < ($cached_size + $content_len))
				$bQuota = false;
		}

		if($bQuota)
		{
			CheckDirPath($file_name);
			$written = 0;
			$tmp_filename = $file_name.md5(mt_rand()).".tmp";
			$file = @fopen($tmp_filename, "wb");
			if($file !== false)
			{
				$written = fwrite($file, $content);
				if($written == $content_len)
				{
					fclose($file);
					if(file_exists($file_name))
						unlink($file_name);
					rename($tmp_filename, $file_name);
					@chmod($file_name, defined("BX_FILE_PERMISSIONS")? BX_FILE_PERMISSIONS: 0664);
					if(class_exists("cdiskquota"))
					{
						CDiskQuota::updateDiskQuota("file", $content_len, "copy");
					}
				}
				else
				{
					$written = 0;
					fclose($file);
					if(file_exists($file_name))
						unlink($file_name);
					if(file_exists($tmp_filename))
						unlink($tmp_filename);
				}
			}
			$arStat = CHTMLPagesCache::writeStatistic(
				0, //hit
				1, //miss
				0, //quota
				0, //posts
				$written //files
			);
		}
		else
		{
			//Fire cleanup
			CHTMLPagesCache::CleanAll();
			CHTMLPagesCache::writeStatistic(0, 0, 1, 0, false);
		}
	}

	public static function IsOn()
	{
		return file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled");
	}

	public static function SetEnabled($status)
	{
		$file_name  = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled";
		if($status)
		{
			RegisterModuleDependences("main", "OnEpilog", "main", "CHTMLPagesCache", "OnEpilog");
			RegisterModuleDependences("main", "OnLocalRedirect", "main", "CHTMLPagesCache", "OnEpilog");

			//For very first run we have to fall into defaults
			CHTMLPagesCache::SetOptions(CHTMLPagesCache::GetOptions());

			if(!file_exists($file_name))
			{
				$f = fopen($file_name, "w");
				fwrite($f, "0,0,0,0,0");
				fclose($f);
				@chmod($file_name, defined("BX_FILE_PERMISSIONS")? BX_FILE_PERMISSIONS: 0664);
			}
		}
		else
		{
			UnRegisterModuleDependences("main", "OnEpilog", "main", "CHTMLPagesCache", "OnEpilog");
			UnRegisterModuleDependences("main", "OnLocalRedirect", "main", "CHTMLPagesCache", "OnEpilog");

			if(file_exists($file_name))
				unlink($file_name);
		}
	}

	public static function SetOptions($arOptions = array(), $bCompile = true)
	{
		if($bCompile)
			CHTMLPagesCache::CompileOptions($arOptions);

		$file_name = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.config.php";
		$tmp_filename = $file_name.md5(mt_rand()).".tmp";
		CheckDirPath($file_name);

		$fh = fopen($tmp_filename, "wb");
		if($fh !== false)
		{
			$content = "<?\n\$arHTMLPagesOptions = array(\n";
			foreach($arOptions as $key => $value)
			{
				if(is_array($value))
				{
					$content .= "\t\"".EscapePHPString($key)."\" => array(\n";
					foreach($value as $key2 => $val)
					{
						$content .= "\t\t\"".EscapePHPString($key2)."\" => \"".EscapePHPString($val)."\",\n";
					}
					$content .= "\t),\n";
				}
				else
				{
					$content .= "\t\"".EscapePHPString($key)."\" => \"".EscapePHPString($value)."\",\n";
				}
			}
			$content .= ");\n?>\n";
			$written = fwrite($fh, $content);
			$len = function_exists('mb_strlen')? mb_strlen($content, 'latin1'): strlen($content);
			if($written === $len)
			{
				fclose($fh);
				if(file_exists($file_name))
					unlink($file_name);
				rename($tmp_filename, $file_name);
				@chmod($file_name, defined("BX_FILE_PERMISSIONS")? BX_FILE_PERMISSIONS: 0664);
			}
			else
			{
				fclose($fh);
				if(file_exists($tmp_filename))
					unlink($tmp_filename);
			}
		}
	}

	public static function GetOptions($bCheck = false)
	{
		$arHTMLPagesOptions = array();

		$file_name = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.config.php";
		if(file_exists($file_name))
		{
			include($file_name);
		}

		if(!is_array($arHTMLPagesOptions))
		{
			if($bCheck)
				return false;
			else
				$arHTMLPagesOptions = array();
		}

		$bCompile = false;
		if(!array_key_exists("INCLUDE_MASK", $arHTMLPagesOptions))
		{
			$arHTMLPagesOptions["INCLUDE_MASK"] = "*.php;*/";
			$bCompile = true;
		}
		if(!array_key_exists("EXCLUDE_MASK", $arHTMLPagesOptions))
		{
			$arHTMLPagesOptions["EXCLUDE_MASK"] = "/bitrix/*;/404.php";
			$bCompile = true;
		}
		if(!array_key_exists("FILE_QUOTA", $arHTMLPagesOptions))
		{
			$arHTMLPagesOptions["FILE_QUOTA"] = 100;
			$bCompile = true;
		}
		if($bCompile)
		{
			CHTMLPagesCache::CompileOptions($arHTMLPagesOptions);
		}

		return $arHTMLPagesOptions;
	}

	public static function CompileOptions(&$arOptions)
	{
		$arOptions["~INCLUDE_MASK"] = array();
		$inc = str_replace(
			array("\\", ".",  "?", "*",   "'"),
			array("/",  "\.", ".", ".*?", "\'"),
			$arOptions["INCLUDE_MASK"]
		);
		$arIncTmp = explode(";", $inc);
		foreach($arIncTmp as $mask)
		{
			$mask = trim($mask);
			if(strlen($mask) > 0)
				$arOptions["~INCLUDE_MASK"][] = "'^".$mask."$'";
		}

		$arOptions["~EXCLUDE_MASK"] = array();
		$inc = str_replace(
			array("\\", ".",  "?", "*",   "'"),
			array("/",  "\.", ".", ".*?", "\'"),
			$arOptions["EXCLUDE_MASK"]
		);
		$arIncTmp = explode(";", $inc);
		foreach($arIncTmp as $mask)
		{
			$mask = trim($mask);
			if(strlen($mask) > 0)
				$arOptions["~EXCLUDE_MASK"][] = "'^".$mask."$'";
		}

		if(intval($arOptions["FILE_QUOTA"]) > 0)
			$arOptions["~FILE_QUOTA"] = doubleval($arOptions["FILE_QUOTA"]) * 1024.0 * 1024.0;
		else
			$arOptions["~FILE_QUOTA"] = 0.0;
		$arOptions["COMPRESS"] = IsModuleInstalled('compression');
		$arOptions["STORE_PASSWORD"] = COption::GetOptionString("main", "store_password", "Y");
		$cookie_prefix = COption::GetOptionString('main', 'cookie_name', 'BITRIX_SM');
		$arOptions["COOKIE_LOGIN"] = $cookie_prefix.'_LOGIN';
		$arOptions["COOKIE_PASS"]  = $cookie_prefix.'_UIDH';
	}

	public static function readStatistic()
	{
		$arResult = false;
		$file_name = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled";
		if(file_exists($file_name))
		{
			$fp = fopen($file_name, "r");
			if($fp !== false)
			{
				$file_values = explode(",", fgets($fp));
				fclose($fp);
				$arResult = array(
					"HITS" => intval($file_values[0]),
					"MISSES" => intval($file_values[1]),
					"QUOTA" => intval($file_values[2]),
					"POSTS" => intval($file_values[3]),
					"FILE_SIZE" => doubleval($file_values[4]),
				);
			}
		}
		return $arResult;
	}

	public static function writeStatistic($hit = 0, $miss = 0, $quota = 0, $posts = 0, $files = 0.0)
	{
		$file_name = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled";

		$fp = @fopen($file_name, "r+");
		if($fp)
			$file_values = explode(",", fgets($fp));
		else
			$file_values = array();

		if(count($file_values) > 1)
		{
			$new_file_values = array(
				intval($file_values[0]) + $hit,
				intval($file_values[1]) + $miss,
				intval($file_values[2]) + $quota,
				intval($file_values[3]) + $posts,
				$files === false? 0: doubleval($file_values[4]) + doubleval($files),
			);

			fseek($fp, 0);
			ftruncate($fp, 0);
			fwrite($fp, implode(",", $new_file_values));
		}

		if($fp)
			fclose($fp);
	}

	public static function SetStatus($status)
	{
		$bCgi = (stristr(php_sapi_name(), "cgi") !== false);
		$bFastCgi = ($bCgi && (array_key_exists('FCGI_ROLE', $_SERVER) || array_key_exists('FCGI_ROLE', $_ENV)));
		if($bCgi && !$bFastCgi)
			header("Status: ".$status);
		else
			header($_SERVER["SERVER_PROTOCOL"]." ".$status);
	}

}
?>
