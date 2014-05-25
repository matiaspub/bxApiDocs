<?
/*. require_module 'standard'; .*/
/*. require_module 'session'; .*/
/*. require_module 'zlib'; .*/
/*. require_module 'pcre'; .*/
use Bitrix\Main\IO;
use Bitrix\Main\Application;
use Bitrix\Main;

class CHTMLPagesCache
{
	public static $options = array();

	/**
	 * @return void
	 */
	public static function startCaching()
	{
		$HTML_PAGES_ROOT = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages";

		if(
			isset($_SERVER["HTTP_BX_AJAX"])
			|| (isset($_SERVER["HTTP_FORWARDED"]) && $_SERVER["HTTP_FORWARDED"]=="SSL")
			|| (strncmp($_SERVER["REQUEST_URI"], BX_ROOT, strlen(BX_ROOT)) == 0)
			|| (strncmp($_SERVER["REQUEST_URI"], BX_PERSONAL_ROOT, strlen(BX_ROOT)) == 0)
			|| (preg_match("#^/index_controller\\.php#", $_SERVER["REQUEST_URI"]) > 0)
		)
		{
			return;
		}

		$arHTMLPagesOptions = array();
		if (file_exists($HTML_PAGES_ROOT."/.config.php"))
			include($HTML_PAGES_ROOT."/.config.php");

		$useCompositeCache = isset($arHTMLPagesOptions["COMPOSITE"]) && $arHTMLPagesOptions["COMPOSITE"] === "Y";
		if ($useCompositeCache)
		{
			//to warm up localStorage
			// define("ENABLE_HTML_STATIC_CACHE_JS", true);
		}

		if(
			$_SERVER["REQUEST_METHOD"] !== "GET"
			|| isset($_GET["sessid"])
		)
		{
			return;
		}

		if ($useCompositeCache)
		{
			if (isset($_SERVER["HTTP_BX_REF"]))
			{
				$_SERVER["HTTP_REFERER"] = $_SERVER["HTTP_BX_REF"];
			}

			if (array_key_exists("bxrand", $_GET))
			{
				unset($_GET["bxrand"]);
				unset($_REQUEST["bxrand"]);
				if (isset($_SERVER["REQUEST_URI"]))
				{
					$_SERVER["REQUEST_URI"] = preg_replace("/([?&]bxrand=[0-9]+)\$/", "", $_SERVER["REQUEST_URI"]);
				}
				if (isset($_SERVER["QUERY_STRING"]))
				{
					$_SERVER["QUERY_STRING"] = preg_replace("/([?&]bxrand=[0-9]+)\$/", "", $_SERVER["QUERY_STRING"]);
				}
			}
		}

		if(
			$useCompositeCache
			&& (
				isset($arHTMLPagesOptions["COOKIE_NCC"])
				&& array_key_exists($arHTMLPagesOptions["COOKIE_NCC"], $_COOKIE)
				&& $_COOKIE[$arHTMLPagesOptions["COOKIE_NCC"]] === "Y"
			)
		)
		{
			return;
		}

		if(
			!$useCompositeCache
			&& (
				array_key_exists(session_name(), $_COOKIE)
				|| array_key_exists(session_name(), $_REQUEST)
			)
		)
		{
			return;
		}

		//Check for stored authorization
		if(
			isset($arHTMLPagesOptions["STORE_PASSWORD"]) && $arHTMLPagesOptions["STORE_PASSWORD"] == "Y"
			&& isset($_COOKIE[$arHTMLPagesOptions["COOKIE_LOGIN"]]) && $_COOKIE[$arHTMLPagesOptions["COOKIE_LOGIN"]] <> ''
			&& isset($_COOKIE[$arHTMLPagesOptions["COOKIE_PASS"]]) && $_COOKIE[$arHTMLPagesOptions["COOKIE_PASS"]] <> ''
		)
		{
			if (
				!$useCompositeCache
				|| !isset($arHTMLPagesOptions["COOKIE_CC"])
				|| !array_key_exists($arHTMLPagesOptions["COOKIE_CC"], $_COOKIE)
				|| $_COOKIE[$arHTMLPagesOptions["COOKIE_CC"]] !== "Y"
			)
			{
				return;
			}
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

		if ($useCompositeCache)
		{
			if(
				isset($arHTMLPagesOptions["DOMAINS"])
				&& !in_array($_SERVER["HTTP_HOST"], $arHTMLPagesOptions["DOMAINS"])
			)
			{
				return;
			}

			if(
				isset($arHTMLPagesOptions["INDEX_ONLY"])
				&& $arHTMLPagesOptions["INDEX_ONLY"]
				&& !preg_match("#^(/[^?]+)\\.php$#", $_SERVER["REQUEST_URI"])
				&& !preg_match("#^([^?]*)/$#", $_SERVER["REQUEST_URI"])
				&& ! (
					isset($arHTMLPagesOptions["~GET"])
					&& !empty($arHTMLPagesOptions["~GET"])
					&& isset($_GET)
					&& !empty($_GET)
					&& count(array_diff(array_keys($_GET), $arHTMLPagesOptions["~GET"])) === 0
				)
			)
			{
				return;
			}

			$HTML_PAGES_ROOT .= "/".preg_replace("/:(\\d+)\$/", "-\\1", $_SERVER["HTTP_HOST"]);
		}

		$arMatch = array();
		if(preg_match("#^(/.+?)\\.php\\?(.*)#", $_SERVER["REQUEST_URI"], $arMatch) > 0)
		{
			if(strpos($arMatch[2], "\\")!==false || strpos($arMatch[2], "/")!==false)
				return;
			$PAGES_FILE = $arMatch[1]."@".$arMatch[2];
		}
		elseif(preg_match("#^(/.+)\\.php$#", $_SERVER["REQUEST_URI"], $arMatch) > 0)
		{
			$PAGES_FILE = $arMatch[1]."@";
		}
		if(preg_match("#^(.*?)/\\?(.*)#", $_SERVER["REQUEST_URI"], $arMatch) > 0)
		{
			if(strpos($arMatch[2], "\\")!==false || strpos($arMatch[2], "/")!==false)
				return;
			if(strlen($arMatch[1]) && substr($arMatch[1], 0, 1)!=="/")
				return;
			$PAGES_FILE = $arMatch[1]."/index@".$arMatch[2];
		}
		elseif(preg_match("#^(.*)/$#", $_SERVER["REQUEST_URI"], $arMatch) > 0)
		{
			if(strlen($arMatch[1]) && substr($arMatch[1], 0, 1)!=="/")
				return;
			$PAGES_FILE = $arMatch[1]."/index@";
		}

		$PAGES_FILE = $HTML_PAGES_ROOT.str_replace(".", "_", $PAGES_FILE).".html";

		//This checks for invalid symbols
		//TODO: make it Windows compatible
		if(preg_match("/(\\?|\\*|\\.\\.)/", $PAGES_FILE) > 0)
			return;

		if(
			(isset($_SERVER["HTTP_BX_CACHE_MODE"]) && $_SERVER["HTTP_BX_CACHE_MODE"] === "HTMLCACHE")
			|| (defined("CACHE_MODE") && constant("CACHE_MODE") === "HTMLCACHE")
		)
		{
			define("USE_HTML_STATIC_CACHE", true);
			return;
		}

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
			if ($useCompositeCache)
			{
				if (isset($arHTMLPagesOptions["~FILE_QUOTA"]))
				{
					$cache_quota = doubleval($arHTMLPagesOptions["~FILE_QUOTA"]);
					$arStat = CHTMLPagesCache::readStatistic();
					if($arStat)
						$cached_size = $arStat["FILE_SIZE"];
					else
						$cached_size = 0.0;
					if ($cached_size > $cache_quota)
					{
						CHTMLPagesCache::writeStatistic(0, 0, 1);
						return;
					}
				}

				if (!defined('USE_HTML_STATIC_CACHE'))
					// define('USE_HTML_STATIC_CACHE', true);
			}
			else
			{
				// define('HTML_PAGES_FILE', $PAGES_FILE);
			}
		}
	}

	/**
	 * Deletes all above html_pages
	 * @param string $relativePath [optional]
	 * @param int $validTime [optional] unix timestamp
	 * @return float
	 */
	public static function deleteRecursive($relativePath = "", $validTime = 0)
	{
		$bytes = 0.0;
		if (strpos($relativePath, "..") !== false)
		{
			return $bytes;
		}

		$relativePath = rtrim($relativePath, "/");
		$baseDir = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages";
		$absPath = $baseDir.$relativePath;

		if (is_file($absPath))
		{
			if (
				($validTime && filemtime($absPath) > $validTime) ||
				in_array($relativePath, array("/.enabled", "/.config.php", "/.htaccess", "/404.php")))
			{
				return $bytes;
			}

			$bytes = filesize($absPath);
			@unlink($absPath);
			return doubleval($bytes);
		}
		elseif (is_dir($absPath) && ($handle = opendir($absPath)) !== false)
		{
			while (($file = readdir($handle)) !== false)
			{
				if ($file === "." || $file === "..")
				{
					continue;
				}

				$bytes += CHTMLPagesCache::deleteRecursive($relativePath."/".$file, $validTime);
			}
			closedir($handle);
			@rmdir($absPath);
		}

		return doubleval($bytes);
	}

	public static function OnEpilog()
	{
		global $USER;

		$arOptions = CHTMLPagesCache::GetOptions();
		if ($arOptions["COMPOSITE"] === "Y")
		{
			if (Main\Data\Cache::shouldClearCache())
			{
				$server = Main\Context::getCurrent()->getServer();

				$queryString = DeleteParam(array("clear_cache", "clear_cache_session"));
				$uri = new Bitrix\Main\Web\Uri($server->getRequestUri());
				$refinedUri = $queryString != "" ? $uri->getPath()."?".$queryString : $uri->getPath();

				$cachedFile = Main\Data\StaticHtmlCache::convertUriToPath($refinedUri, $server->getHttpHost());
				$bytes = self::deleteRecursive($cachedFile);
				self::updateQuota(-$bytes);
			}
			return;
		}

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
		self::updateQuota(-$bytes);
	}

	public static function writeFile($file_name, $content)
	{
		global $USER;
		if(is_object($USER) && $USER->IsAuthorized())
			return;

		$content_len = function_exists('mb_strlen')? mb_strlen($content, 'latin1'): strlen($content);
		if($content_len <= 0)
			return;

		$arHTMLPagesOptions = CHTMLPagesCache::GetOptions();

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

	public static function IsCompositeEnabled()
	{
		$options = self::GetOptions();
		return isset($options["COMPOSITE"]) && $options["COMPOSITE"] === "Y";
	}

	public static function SetEnabled($status)
	{
		$file_name  = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled";
		if($status)
		{
			RegisterModuleDependences("main", "OnEpilog", "main", "CHTMLPagesCache", "OnEpilog");
			RegisterModuleDependences("main", "OnLocalRedirect", "main", "CHTMLPagesCache", "OnEpilog");
			RegisterModuleDependences("main", "OnChangeFile", "main", "CHTMLPagesCache", "OnChangeFile");

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
			UnRegisterModuleDependences("main", "OnChangeFile", "main", "CHTMLPagesCache", "OnChangeFile");

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
				if (is_integer($key))
					$phpKey = $key;
				else
					$phpKey = "\"".EscapePHPString($key)."\"";

				if(is_array($value))
				{
					$content .= "\t".$phpKey." => array(\n";
					foreach($value as $key2 => $val)
					{
						if (is_integer($key2))
							$phpKey2 = $key2;
						else
							$phpKey2 = "\"".EscapePHPString($key2)."\"";

						$content .= "\t\t".$phpKey2." => \"".EscapePHPString($val)."\",\n";
					}
					$content .= "\t),\n";
				}
				else
				{
					$content .= "\t".$phpKey." => \"".EscapePHPString($value)."\",\n";
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

			self::$options = array();
		}
	}

	public static function GetOptions()
	{
		if (!empty(self::$options))
		{
			return self::$options;
		}

		$arHTMLPagesOptions = array();
		$file_name = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.config.php";
		if(file_exists($file_name))
		{
			include($file_name);
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

		if(!array_key_exists("COMPOSITE", $arHTMLPagesOptions))
		{
			$arHTMLPagesOptions["COMPOSITE"] = "N";
			$bCompile = true;
		}

		if(!array_key_exists("BANNER_BGCOLOR", $arHTMLPagesOptions))
		{
			$arHTMLPagesOptions["BANNER_BGCOLOR"] = "#E94524";
		}

		if(!array_key_exists("BANNER_STYLE", $arHTMLPagesOptions))
		{
			$arHTMLPagesOptions["BANNER_STYLE"] = "white";
		}

		if(!array_key_exists("ONLY_PARAMETERS", $arHTMLPagesOptions))
		{
			$arHTMLPagesOptions["ONLY_PARAMETERS"] = "referrer1;r1;referrer2;r2;referrer3;r3;utm_source;utm_medium;utm_campaign;utm_content";
			$bCompile = true;
		}

		if($bCompile)
		{
			CHTMLPagesCache::CompileOptions($arHTMLPagesOptions);
		}

		self::$options = $arHTMLPagesOptions;
		return self::$options;
	}

	public static function CompileOptions(&$arOptions)
	{
		$arOptions["~INCLUDE_MASK"] = array();
		$inc = str_replace(
			array("\\", ".",  "?", "*",   "'"),
			array("/",  "\\.", ".", ".*?", "\\'"),
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
		$exc = str_replace(
			array("\\", ".",  "?", "*",   "'"),
			array("/",  "\\.", ".", ".*?", "\\'"),
			$arOptions["EXCLUDE_MASK"]
		);
		$arExcTmp = explode(";", $exc);
		foreach($arExcTmp as $mask)
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
		$arOptions["COOKIE_NCC"]  = $cookie_prefix.'_NCC';
		$arOptions["COOKIE_CC"]  = $cookie_prefix.'_CC';

		$arOptions["INDEX_ONLY"] = ($arOptions["NO_PARAMETERS"] === "Y");
		$arOptions["~GET"] = array();
		$arTmp = explode(";", $arOptions["ONLY_PARAMETERS"]);
		foreach($arTmp as $str)
		{
			$str = trim($str);
			if(strlen($str) > 0)
				$arOptions["~GET"][] = $str;
		}
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

	/**
	 * Update disk quota and cache statistic
	 * @param float $bytes positive or negative value
	 */
	public static function updateQuota($bytes)
	{
		if ($bytes == 0.0)
		{
			return;
		}

		if (class_exists("cdiskquota"))
		{
			CDiskQuota::updateDiskQuota("file", abs($bytes), $bytes > 0.0 ? "copy" : "delete");
		}

		CHTMLPagesCache::writeStatistic(0, 0, 0, 0, $bytes);
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

	public static function OnUserLogin()
	{
		global $APPLICATION, $USER;
		if (self::IsOn())
		{
			$arHTMLCacheOptions = CHTMLPagesCache::GetOptions();
			$arHTMLCacheOptions["GROUPS"][] = "2";
			$diff = array_diff($USER->GetUserGroupArray(), $arHTMLCacheOptions["GROUPS"]);

			if ($diff)
			{
				$APPLICATION->set_cookie("NCC", "Y");
				$APPLICATION->set_cookie("CC", "", 0);
			}
			else
			{
				$APPLICATION->set_cookie("CC", "Y");
				$APPLICATION->set_cookie("NCC", "", 0);
			}
		}
	}

	public static function OnUserLogout()
	{
		global $APPLICATION;
		if (self::IsOn())
		{
			$APPLICATION->set_cookie("NCC", "", 0);
			$APPLICATION->set_cookie("CC", "", 0);
		}
	}

	public static function OnChangeFile($path, $site)
	{
		$pagesDir = new IO\Directory(IO\Path::convertRelativeToAbsolute(Application::getPersonalRoot()."/html_pages"));
		if (!$pagesDir->isExists())
		{
			return;
		}

		$bytes = 0.0;
		$domainDirs = $pagesDir->getChildren();
		$cachedFile = \Bitrix\Main\Data\StaticHtmlCache::convertUriToPath($path);
		foreach ($domainDirs as $domainDir)
		{
			if ($domainDir->isDirectory())
			{
				$bytes += self::deleteRecursive("/".$domainDir->getName().$cachedFile);
			}
		}

		self::updateQuota(-$bytes);
	}


}
