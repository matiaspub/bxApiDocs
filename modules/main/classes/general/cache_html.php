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
	private static $options = array();
	private static $isAjaxRequest = null;
	private static $ajaxRandom = null;

	/**
	 * Checks many conditions to enable HTML Cache
	 *
	 * @return void
	 */
	public static function startCaching()
	{
		self::$ajaxRandom = self::removeRandParam();

		if (
			isset($_SERVER["HTTP_BX_AJAX"]) ||
			isset($_GET["bxajaxid"]) ||
			isset($_GET["ncc"]) ||
			self::isBitrixFolder() ||
			(preg_match("#^/index_controller\\.php#", $_SERVER["REQUEST_URI"]) > 0)
		)
		{
			return;
		}

		//to warm up localStorage
		// define("ENABLE_HTML_STATIC_CACHE_JS", true);

		if ($_SERVER["REQUEST_METHOD"] !== "GET" || isset($_GET["sessid"]))
		{
			return;
		}

		if (isset($_SERVER["HTTP_BX_REF"]))
		{
			$_SERVER["HTTP_REFERER"] = $_SERVER["HTTP_BX_REF"];
		}

		$compositeOptions = self::getOptions();

		//NCC cookie exists
		if (
			isset($compositeOptions["COOKIE_NCC"]) &&
			array_key_exists($compositeOptions["COOKIE_NCC"], $_COOKIE) &&
			$_COOKIE[$compositeOptions["COOKIE_NCC"]] === "Y"
		)
		{
			return;
		}

		//A stored authorization exists, but CC cookie doesn't exist
		if (
			isset($compositeOptions["STORE_PASSWORD"]) && $compositeOptions["STORE_PASSWORD"] == "Y" &&
			isset($_COOKIE[$compositeOptions["COOKIE_LOGIN"]]) && $_COOKIE[$compositeOptions["COOKIE_LOGIN"]] !== "" &&
			isset($_COOKIE[$compositeOptions["COOKIE_PASS"]]) && $_COOKIE[$compositeOptions["COOKIE_PASS"]] !== ""
		)
		{
			if (
				!isset($compositeOptions["COOKIE_CC"]) ||
				!array_key_exists($compositeOptions["COOKIE_CC"], $_COOKIE) ||
				$_COOKIE[$compositeOptions["COOKIE_CC"]] !== "Y"
			)
			{
				return;
			}
		}

		$queryPos = strpos($_SERVER["REQUEST_URI"], "?");
		$requestUri = $queryPos === false ? $_SERVER["REQUEST_URI"] : substr($_SERVER["REQUEST_URI"], 0, $queryPos);

		//Checks excluded masks
		if (isset($compositeOptions["~EXCLUDE_MASK"]) && is_array($compositeOptions["~EXCLUDE_MASK"]))
		{
			foreach ($compositeOptions["~EXCLUDE_MASK"] as $mask)
			{
				if (preg_match($mask, $requestUri) > 0)
				{
					return;
				}
			}
		}

		//Checks excluded GET params
		if (isset($compositeOptions["~EXCLUDE_PARAMS"]) && is_array($compositeOptions["~EXCLUDE_PARAMS"]))
		{
			foreach ($compositeOptions["~EXCLUDE_PARAMS"] as $param)
			{
				if (array_key_exists($param, $_GET))
				{
					return;
				}
			}
		}

		//Checks included masks
		$isRequestInMask = false;
		if (isset($compositeOptions["~INCLUDE_MASK"]) && is_array($compositeOptions["~INCLUDE_MASK"]))
		{
			foreach ($compositeOptions["~INCLUDE_MASK"] as $mask)
			{
				if (preg_match($mask, $requestUri) > 0)
				{
					$isRequestInMask = true;
					break;
				}
			}
		}

		if (!$isRequestInMask)
		{
			return;
		}

		//Checks hosts
		$host = self::getHttpHost();
		if (!in_array($host, self::getDomains()))
		{
			return;
		}

		if (!self::isValidQueryString($compositeOptions))
		{
			return;
		}

		if (self::isAjaxRequest())
		{
			// define("USE_HTML_STATIC_CACHE", true);
		}
		else
		{
			self::setErrorHandler();

			$cacheKey = self::getCacheKey($host);
			$cache = self::getHtmlCacheResponse($cacheKey, $compositeOptions);
			self::trySendResponse($cache, $compositeOptions);

			if ($cache !== null && $cache->shouldCountQuota() && !self::checkQuota())
			{
				self::writeStatistic(0, 0, 1);
			}
			else
			{
				define("USE_HTML_STATIC_CACHE", true);
			}

			self::restoreErrorHandler();
		}
	}

	/**
	 * Gets a cache key with a hostname given by $host
	 * @param string $host
	 * @return string
	 */
	private static function getCacheKey($host)
	{
		$userPrivateKey = self::getUserPrivateKey();
		return self::convertUriToPath(self::getRequestUri(), $host, self::getRealPrivateKey($userPrivateKey));
	}

	/**
	 *
	 * Tries to send a response if cache exists
	 * @param StaticHtmlFileResponse $cache
	 * @param array $compositeOptions
	 */
	private static function trySendResponse($cache, $compositeOptions)
	{
		if ($cache !== null && $cache->exists())
		{
			//Update statistic
			self::writeStatistic(1);

			$etag = $cache->getEtag();
			$lastModified = $cache->getLastModified();
			if ($etag !== false)
			{
				if (array_key_exists("HTTP_IF_NONE_MATCH", $_SERVER) && $_SERVER["HTTP_IF_NONE_MATCH"] === $etag)
				{
					self::setStatus("304 Not Modified");
					self::setHeaders($etag, false, "304");
					die();
				}
			}

			if ($lastModified !== false)
			{
				$sinceModified = isset($_SERVER["HTTP_IF_MODIFIED_SINCE"]) ? strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]) : false;
				if ($sinceModified && $sinceModified >= $lastModified)
				{
					self::setStatus("304 Not Modified");
					self::setHeaders($etag, false, "304");
					die();
				}
			}

			$contents = $cache->getContents();
			if ($contents !== false)
			{
				self::setHeaders($etag, $lastModified, "200", $cache->getContentType());

				//compression support
				$compress = "";
				if ($compositeOptions["COMPRESS"] && isset($_SERVER["HTTP_ACCEPT_ENCODING"]))
				{
					if (strpos($_SERVER["HTTP_ACCEPT_ENCODING"], "x-gzip") !== false)
					{
						$compress = "x-gzip";
					}
					elseif (strpos($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") !== false)
					{
						$compress = "gzip";
					}
				}

				if ($compress)
				{
					header("Content-Encoding: ".$compress);
					echo $cache->isGzipped() ? $contents : gzencode($contents, 4);
				}
				else
				{
					if ($cache->isGzipped())
					{
						$contents = self::gzdecode($contents);
					}

					header("Content-Length: ".self::getBinaryLength($contents));
					echo $contents;
				}

				die();
			}
		}
	}

	/**
	 * Returns Request URI
	 * @return string
	 */
	public static function getRequestUri()
	{
		if (self::isSpaMode())
		{
			return isset($options["SPA_REQUEST_URI"]) ? $options["SPA_REQUEST_URI"] : "/";
		}
		else
		{
			return $_SERVER["REQUEST_URI"];
		}
	}

	/**
	 * Returns HTTP hostname
	 * @param string $host
	 * @return string
	 */
	public static function getHttpHost($host = null)
	{
		return preg_replace("/:(80|443)$/", "", $host === null ? $_SERVER["HTTP_HOST"] : $host);
	}

	/**
	 * Returns valid domains from the composite options
	 * @return array
	 */
	public static function getDomains()
	{
		$options = self::getOptions();
		$domains = array();
		if (isset($options["DOMAINS"]) && is_array($options["DOMAINS"]))
		{
			$domains = array_values($options["DOMAINS"]);
		}

		return array_map(array(__CLASS__, "getHttpHost"), $domains);
	}

	public static function getSpaPostfixByUri($requestUri)
	{
		$options = self::getOptions();
		$requestUri = ($p = strpos($requestUri, "?")) === false ? $requestUri : substr($requestUri, 0, $p);

		if (isset($options["SPA_MAP"]) && is_array($options["SPA_MAP"]))
		{
			foreach ($options["SPA_MAP"] as $mask => $postfix)
			{
				if (preg_match($mask, $requestUri))
				{
					return $postfix;
				}
			}
		}

		return null;
	}

	public static function getSpaPostfix()
	{
		$options = self::getOptions();
		if (isset($options["SPA_MAP"]) && is_array($options["SPA_MAP"]))
		{
			return array_values($options["SPA_MAP"]);
		}

		return array();
	}

	public static function getRealPrivateKey($privateKey = null, $postfix = null)
	{
		if (self::isSpaMode())
		{
			$postfix = $postfix === null ? self::getSpaPostfixByUri($_SERVER["REQUEST_URI"]) : $postfix;
			if ($postfix !== null)
			{
				$privateKey .= $postfix;
			}
		}

		return $privateKey;
	}

	public static function getUserPrivateKey()
	{
		$options = self::getOptions();
		if (isset($options["COOKIE_PK"]) && array_key_exists($options["COOKIE_PK"], $_COOKIE))
		{
			return $_COOKIE[$options["COOKIE_PK"]];
		}

		return null;
	}

	public static function setUserPrivateKey($prefix, $expire = 0)
	{
		$options = self::getOptions();
		if (isset($options["COOKIE_PK"]) && strlen($options["COOKIE_PK"]) > 0)
		{
			setcookie($options["COOKIE_PK"], $prefix, $expire, "/", false, false, true);
		}
	}

	public static function deleteUserPrivateKey()
	{
		$options = self::getOptions();
		if (isset($options["COOKIE_PK"]) && strlen($options["COOKIE_PK"]) > 0)
		{
			setcookie($options["COOKIE_PK"], "", 0, "/");
		}
	}

	/**
	 * Returns true if the current request was initiated by Ajax.
	 *
	 * @return bool
	 */
	public static function isAjaxRequest()
	{
		if (self::$isAjaxRequest === null)
		{
			self::$isAjaxRequest = (
				(isset($_SERVER["HTTP_BX_CACHE_MODE"]) && $_SERVER["HTTP_BX_CACHE_MODE"] === "HTMLCACHE")
				||
				(defined("CACHE_MODE") && constant("CACHE_MODE") === "HTMLCACHE")
			);
		}

		return self::$isAjaxRequest;
	}

	/**
	 * Returns true if the current request URI has bitrix folder
	 *
	 * @return bool
	 */
	public static function isBitrixFolder()
	{
		$folders = array(BX_ROOT, BX_PERSONAL_ROOT);
		$requestUri = "/".ltrim($_SERVER["REQUEST_URI"], "/");
		foreach ($folders as $folder)
		{
			$folder = rtrim($folder, "/")."/";
			if (strncmp($requestUri, $folder, strlen($folder)) == 0)
			{
				return true;
			}
		}

		return false;
	}

	public static function isSpaMode()
	{
		$options = self::getOptions();
		return isset($options["SPA_MODE"]) || $options["SPA_MODE"] === "Y";
	}

	/**
	 * Removes bxrand parameter from the current request and returns its value
	 *
	 * @return string|false
	 */
	public static function removeRandParam()
	{
		if (!array_key_exists("bxrand", $_GET) || !preg_match("/^[0-9]+$/", $_GET["bxrand"]))
		{
			return false;
		}

		$randValue = $_GET["bxrand"];

		unset($_GET["bxrand"]);
		unset($_REQUEST["bxrand"]);

		if (isset($_SERVER["REQUEST_URI"]))
		{
			$_SERVER["REQUEST_URI"] = preg_replace("/((?<=\\?)bxrand=\\d+&?|&bxrand=\\d+\$)/", "", $_SERVER["REQUEST_URI"]);
			$_SERVER["REQUEST_URI"] = rtrim($_SERVER["REQUEST_URI"], "?&");
		}

		if (isset($_SERVER["QUERY_STRING"]))
		{
			$_SERVER["QUERY_STRING"] = preg_replace("/[?&]?bxrand=[0-9]+/", "", $_SERVER["QUERY_STRING"]);
			$_SERVER["QUERY_STRING"] = trim($_SERVER["QUERY_STRING"], "&");
			if (isset($GLOBALS["QUERY_STRING"]))
			{
				$GLOBALS["QUERY_STRING"] = $_SERVER["QUERY_STRING"];
			}
		}

		return $randValue;
	}

	/**
	 *
	 * Decodes a gzip compressed string
	 *
	 * @param $data
	 * @return string
	 */
	public static function gzdecode($data)
	{
		if (function_exists("gzdecode"))
		{
			return gzdecode($data);
		}

		$data = self::getBinarySubstring($data, 10, -8);
		if ($data !== "")
		{
			$data = gzinflate($data);
		}

		return $data;
	}

	/**
	 *
	 * Binary version of substr
	 * @param $str
	 * @param $start
	 * @return string
	 */
	private static function getBinarySubstring($str, $start)
	{
		if (function_exists("mb_substr"))
		{
			$length = (func_num_args() > 2 ? func_get_arg(2) : self::getBinaryLength($str));
			return mb_substr($str, $start, $length, "latin1");
		}

		if (func_num_args() > 2)
		{
			return substr($str, $start, func_get_arg(2));
		}

		return substr($str, $start);
	}

	/**
	 * Binary version of strlen
	 * @param $str
	 * @return int
	 */
	public static function getBinaryLength($str)
	{
		return function_exists("mb_strlen") ? mb_strlen($str, "latin1") : strlen($str);
	}

	private static function isValidQueryString($arHTMLPagesOptions)
	{
		if (!isset($arHTMLPagesOptions["INDEX_ONLY"]) || !$arHTMLPagesOptions["INDEX_ONLY"])
		{
			return true;
		}

		$queryString = "";
		if (isset($_SERVER["REQUEST_URI"]) && ($position = strpos($_SERVER["REQUEST_URI"], "?")) !== false)
		{
			$queryString = substr($_SERVER["REQUEST_URI"], $position + 1);
			$queryString = self::removeIgnoredParams($queryString);
		}

		if ($queryString === "")
		{
			return true;
		}

		$queryParams = array();
		parse_str($queryString, $queryParams);
		if (isset($arHTMLPagesOptions["~GET"]) &&
			!empty($arHTMLPagesOptions["~GET"]) &&
			count(array_diff(array_keys($queryParams), $arHTMLPagesOptions["~GET"])) === 0
		)
		{
			return true;
		}

		return false;
	}

	/**
	 * Returns bxrand value
	 *
	 * @return string|false
	 */
	public static function getAjaxRandom()
	{
		if (self::$ajaxRandom === null)
		{
			self::$ajaxRandom = self::removeRandParam();
		}

		return self::$ajaxRandom;
	}

	/**
	 * Returns the instance of the StaticHtmlFileResponse
	 * @param string $cacheKey unique cache identifier
	 * @param array $htmlCacheOptions html cache options
	 * @return StaticHtmlFileResponse|null
	 */
	private static function getHtmlCacheResponse($cacheKey, array $htmlCacheOptions)
	{
		$configuration = array();
		$storage = isset($htmlCacheOptions["STORAGE"]) ? $htmlCacheOptions["STORAGE"] : false;
		if (in_array($storage, array("memcached", "memcached_cluster")))
		{
			if (extension_loaded("memcache"))
			{
				return new StaticHtmlMemcachedResponse($cacheKey, $configuration, $htmlCacheOptions);
			}
			else
			{
				return null;
			}
		}
		else
		{
			return new StaticHtmlFileResponse($cacheKey, $configuration, $htmlCacheOptions);
		}
	}

	/**
	 *
	 * Sets HTTP headers
	 * @param string $etag
	 * @param int $lastModified
	 * @param bool $compositeHeader
	 * @param bool $contentType
	 */
	private static function setHeaders($etag, $lastModified, $compositeHeader = false, $contentType = false)
	{
		if ($etag !== false)
		{
			header("ETag: ".$etag);
		}

		header("Expires: Fri, 07 Jun 1974 04:00:00 GMT");

		if ($lastModified !== false)
		{
			$utc = gmdate("D, d M Y H:i:s", $lastModified)." GMT";
			header("Last-Modified: ".$utc);
		}

		if ($contentType !== false)
		{
			header("Content-type: ".$contentType);
		}

		if ($compositeHeader !== false)
		{
			header("X-Bitrix-Composite: Cache (".$compositeHeader.")");
		}
	}

	/**
	 * Sets HTTP status
	 * @param string $status
	 */
	private static function setStatus($status)
	{
		$bCgi = (stristr(php_sapi_name(), "cgi") !== false);
		$bFastCgi = ($bCgi && (array_key_exists("FCGI_ROLE", $_SERVER) || array_key_exists("FCGI_ROLE", $_ENV)));
		if ($bCgi && !$bFastCgi)
		{
			header("Status: ".$status);
		}
		else
		{
			header($_SERVER["SERVER_PROTOCOL"]." ".$status);
		}
	}

	/**
	 * Converts URI to a cache key (file path)
	 * / => /index.html
	 * /index.php => /index.html
	 * /aa/bb/ => /aa/bb/index.html
	 * /aa/bb/index.php => /aa/bb/index.html
	 * /?a=b&b=c => /index@a=b&b=c.html
	 * @param string $uri
	 * @param string $host
	 * @param string $privateKey
	 * @return string
	 */
	public static function convertUriToPath($uri, $host = null, $privateKey = null)
	{
		$uri = "/".trim($uri, "/");
		$parts = explode("?", $uri, 2);

		$uriPath = $parts[0];
		$uriPath = preg_replace("~/index\\.(php|html)$~i", "", $uriPath);
		$uriPath = rtrim(str_replace("..", "__", $uriPath), "/");
		$uriPath .= "/index";

		$queryString = isset($parts[1]) ? self::removeIgnoredParams($parts[1]) : "";
		$queryString = str_replace(".", "_", $queryString);

		$host = self::getHttpHost($host);
		if (strlen($host) > 0)
		{
			$host = "/".$host;
			$host = preg_replace("/:(\\d+)\$/", "-\\1", $host);
		}

		$privateKey = preg_replace("~[^a-z0-9/_]~i", "", $privateKey);
		if (strlen($privateKey) > 0)
		{
			$privateKey = "/".trim($privateKey, "/");
		}

		$cacheKey = $host.$uriPath."@".$queryString.$privateKey.".html";
		return str_replace(array("?", "*"), "_", $cacheKey);
	}

	private static function removeIgnoredParams($queryString)
	{
		if (!is_string($queryString) || $queryString === "")
		{
			return "";
		}

		$params = array();
		parse_str($queryString, $params);

		$options = self::getOptions();
		$ignoredParams =
			isset($options["~IGNORED_PARAMETERS"]) && is_array($options["~IGNORED_PARAMETERS"]) ?
			$options["~IGNORED_PARAMETERS"] :
			array();

		if (empty($ignoredParams) || empty($params))
		{
			return $queryString;
		}

		foreach ($params as $key => $value)
		{
			foreach ($ignoredParams as $ignoredParam)
			{
				if (strcasecmp($ignoredParam, $key) == 0)
				{
					unset($params[$key]);
					break;
				}
			}
		}

		return http_build_query($params, "", "&");
	}

	/**
	 * @deprecated
	 * use 
	 * $staticHtmlCache = \Bitrix\Main\Data\StaticHtmlCache::getInstance();
	 * $staticHtmlCache->deleteAll();
	 */
	public static function cleanAll()
	{
		$bytes = \Bitrix\Main\Data\StaticHtmlFileStorage::deleteRecursive("/");

		if (class_exists("cdiskquota"))
		{
			CDiskQuota::updateDiskQuota("file", $bytes, "delete");
		}

		self::updateQuota(-$bytes);
	}

	/**
	 * @deprecated
	 *
	 * Creates cache file
	 * Old Html Cache
	 * @param string $file_name
	 * @param string $content
	 */
	public static function writeFile($file_name, $content)
	{
		return;
	}

	/**
	 * Return true if html cache is on
	 * @return bool
	 */
	public static function isOn()
	{
		return file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled");
	}

	/**
	 * Return true if composite mode is enabled
	 * @return bool
	 */
	public static function isCompositeEnabled()
	{
		return self::isOn();
	}

	public static function setEnabled($status, $setDefaults = true)
	{
		$fileName  = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled";
		if ($status)
		{
			RegisterModuleDependences("main", "OnEpilog", "main", "CHTMLPagesCache", "OnEpilog");
			RegisterModuleDependences("main", "OnLocalRedirect", "main", "CHTMLPagesCache", "OnEpilog");
			RegisterModuleDependences("main", "OnChangeFile", "main", "CHTMLPagesCache", "OnChangeFile");

			//For very first run we have to fall into defaults
			if ($setDefaults === true)
			{
				self::setOptions();
			}

			if (!file_exists($fileName))
			{
				$f = fopen($fileName, "w");
				fwrite($f, "0,0,0,0,0");
				fclose($f);
				@chmod($fileName, defined("BX_FILE_PERMISSIONS")? BX_FILE_PERMISSIONS: 0664);
			}
		}
		else
		{
			UnRegisterModuleDependences("main", "OnEpilog", "main", "CHTMLPagesCache", "OnEpilog");
			UnRegisterModuleDependences("main", "OnLocalRedirect", "main", "CHTMLPagesCache", "OnEpilog");
			UnRegisterModuleDependences("main", "OnChangeFile", "main", "CHTMLPagesCache", "OnChangeFile");

			if (file_exists($fileName))
			{
				unlink($fileName);
			}
		}
	}

	/**
	 * Saves cache options
	 * @param array $arOptions
	 * @return void
	 */
	public static function setOptions($arOptions = array())
	{
		$arOptions = array_merge(self::getOptions(), $arOptions);
		self::compileOptions($arOptions);

		$file_name = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.config.php";
		$tmp_filename = $file_name.md5(mt_rand()).".tmp";
		CheckDirPath($file_name);

		$fh = fopen($tmp_filename, "wb");
		if ($fh !== false)
		{
			$content = "<?\n\$arHTMLPagesOptions = array(\n";
			foreach ($arOptions as $key => $value)
			{
				if (is_integer($key))
				{
					$phpKey = $key;
				}
				else
				{
					$phpKey = "\"".EscapePHPString($key)."\"";
				}

				if (is_array($value))
				{
					$content .= "\t".$phpKey." => array(\n";
					foreach ($value as $key2 => $val)
					{
						if (is_integer($key2))
						{
							$phpKey2 = $key2;
						}
						else
						{
							$phpKey2 = "\"".EscapePHPString($key2)."\"";
						}

						$content .= "\t\t".$phpKey2." => \"".EscapePHPString($val)."\",\n";
					}
					$content .= "\t),\n";
				}
				else
				{
					$content .= "\t".$phpKey." => \"".EscapePHPString($value)."\",\n";
				}
			}
			
			$content .= ");\n?>";
			$written = fwrite($fh, $content);
			$len = function_exists('mb_strlen')? mb_strlen($content, 'latin1'): strlen($content);
			if ($written === $len)
			{
				fclose($fh);
				if (file_exists($file_name))
				{
					unlink($file_name);
				}
				rename($tmp_filename, $file_name);
				@chmod($file_name, defined("BX_FILE_PERMISSIONS")? BX_FILE_PERMISSIONS: 0664);
			}
			else
			{
				fclose($fh);
				if (file_exists($tmp_filename))
				{
					unlink($tmp_filename);
				}
			}

			self::$options = array();
		}
	}

	/**
	 * Returns an array with cache options.
	 * @return array
	 */
	public static function getOptions()
	{
		if (!empty(self::$options))
		{
			return self::$options;
		}

		$arHTMLPagesOptions = array();
		$file_name = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.config.php";
		if (file_exists($file_name))
		{
			include($file_name);
		}

		$compile = count(array_diff(self::getCompiledOptions(), array_keys($arHTMLPagesOptions))) > 0;
		$arHTMLPagesOptions = $arHTMLPagesOptions + self::getDefaultOptions();
		if ($compile)
		{
			self::compileOptions($arHTMLPagesOptions);
		}

		if (isset($arHTMLPagesOptions["AUTO_COMPOSITE"]) && $arHTMLPagesOptions["AUTO_COMPOSITE"] === "Y")
		{
			$arHTMLPagesOptions["FRAME_MODE"] = "Y";
			$arHTMLPagesOptions["FRAME_TYPE"] = "DYNAMIC_WITH_STUB";
			$arHTMLPagesOptions["AUTO_UPDATE"] = "Y";
		}

		self::$options = $arHTMLPagesOptions;
		return self::$options;
	}

	public static function resetOptions()
	{
		self::setOptions(self::getDefaultOptions());
	}

	private static function getDefaultOptions()
	{
		return array(
			"INCLUDE_MASK" => "/*",
			"EXCLUDE_MASK" => "/bitrix/*; /404.php; ",
			"FILE_QUOTA" => 100,
			"BANNER_BGCOLOR" => "#E94524",
			"BANNER_STYLE" => "white",
			"STORAGE" => "files",
			"ONLY_PARAMETERS" => "id; ELEMENT_ID; SECTION_ID; PAGEN_1; ",
			"IGNORED_PARAMETERS" => "utm_source; utm_medium; utm_campaign; utm_content; fb_action_ids; ".
									"utm_term; yclid; gclid; _openstat; from; ".
									"referrer1; r1; referrer2; r2; referrer3; r3; ",
			"WRITE_STATISTIC" => "Y",
			"EXCLUDE_PARAMS" => "ncc; ",
			"COMPOSITE" => "Y"
		);
	}

	private static function getCompiledOptions()
	{
		return array(
			"INCLUDE_MASK",
			"~INCLUDE_MASK",
			"EXCLUDE_MASK",
			"~EXCLUDE_MASK",
			"FILE_QUOTA",
			"~FILE_QUOTA",
			"~GET",
			"ONLY_PARAMETERS",
			"IGNORED_PARAMETERS",
			"~IGNORED_PARAMETERS",
			"INDEX_ONLY",
			"EXCLUDE_PARAMS",
			"~EXCLUDE_PARAMS",
		);
	}

	public static function compileOptions(&$arOptions)
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
			if (strlen($mask) > 0)
			{
				$arOptions["~INCLUDE_MASK"][] = "'^".$mask."$'";
			}
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
			if (strlen($mask) > 0)
			{
				$arOptions["~EXCLUDE_MASK"][] = "'^".$mask."$'";
			}
		}

		if (intval($arOptions["FILE_QUOTA"]) > 0)
		{
			$arOptions["~FILE_QUOTA"] = doubleval($arOptions["FILE_QUOTA"]) * 1024.0 * 1024.0;
		}
		else
		{
			$arOptions["~FILE_QUOTA"] = 0.0;
		}

		$arOptions["INDEX_ONLY"] = isset($arOptions["NO_PARAMETERS"]) && ($arOptions["NO_PARAMETERS"] === "Y");
		$arOptions["~GET"] = array();
		$onlyParams = explode(";", $arOptions["ONLY_PARAMETERS"]);
		foreach ($onlyParams as $str)
		{
			$str = trim($str);
			if (strlen($str) > 0)
			{
				$arOptions["~GET"][] = $str;
			}
		}

		$arOptions["~IGNORED_PARAMETERS"] = array();
		$ignoredParams = explode(";", $arOptions["IGNORED_PARAMETERS"]);
		foreach($ignoredParams as $str)
		{
			$str = trim($str);
			if (strlen($str) > 0)
			{
				$arOptions["~IGNORED_PARAMETERS"][] = $str;
			}
		}

		$arOptions["~EXCLUDE_PARAMS"] = array();
		$excludeParams = explode(";", $arOptions["EXCLUDE_PARAMS"]);
		foreach($excludeParams as $str)
		{
			$str = trim($str);
			if (strlen($str) > 0)
			{
				$arOptions["~EXCLUDE_PARAMS"][] = $str;
			}
		}

		if (function_exists("IsModuleInstalled"))
		{
			$arOptions["COMPRESS"] = IsModuleInstalled('compression');
			$arOptions["STORE_PASSWORD"] = COption::GetOptionString("main", "store_password", "Y");
			$cookie_prefix = COption::GetOptionString('main', 'cookie_name', 'BITRIX_SM');
			$arOptions["COOKIE_LOGIN"] = $cookie_prefix.'_LOGIN';
			$arOptions["COOKIE_PASS"]  = $cookie_prefix.'_UIDH';
			$arOptions["COOKIE_NCC"]  = $cookie_prefix.'_NCC';
			$arOptions["COOKIE_CC"]  = $cookie_prefix.'_CC';
			$arOptions["COOKIE_PK"]  = $cookie_prefix.'_PK';
		}
	}

	/**
	 * Returns array with cache statistics data.
	 * Returns an empty array in case of disabled html cache.
	 *
	 * @return array
	 */
	public static function readStatistic()
	{
		$result = false;
		$fileName = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled";
		if (file_exists($fileName) && ($contents = file_get_contents($fileName)) !== false)
		{
			$fileValues = explode(",", $contents);
			$result = array(
				"HITS" => intval($fileValues[0]),
				"MISSES" => intval($fileValues[1]),
				"QUOTA" => intval($fileValues[2]),
				"POSTS" => intval($fileValues[3]),
				"FILE_SIZE" => doubleval($fileValues[4]),
			);
		}

		return $result;
	}

	/**
	 * Updates cache usage statistics.
	 * Each of parameters is added to appropriate existing stats.
	 *
	 * @param integer|false $hits Number of cache hits.
	 * @param integer|false $writings Number of cache writing.
	 * @param integer|false $quota Quota change in bytes.
	 * @param integer|false $posts Number of POST requests.
	 * @param float|false $files File size in bytes.
	 *
	 * @return void
	 */
	public static function writeStatistic($hits = 0, $writings = 0, $quota = 0, $posts = 0, $files = 0.0)
	{
		$options = self::getOptions();
		if ($options["WRITE_STATISTIC"] !== "Y")
		{
			return;
		}

		$fileName = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled";
		if (!file_exists($fileName) || ($fp = @fopen($fileName, "r+")) === false)
		{
			return;
		}

		if (@flock($fp, LOCK_EX))
		{
			$fileValues = explode(",", fgets($fp));
			$cacheSize = (isset($fileValues[4]) ? doubleval($fileValues[4]) + doubleval($files) : doubleval($files));
			$newFileValues = array(
				$hits      === false ? 0 : (isset($fileValues[0]) ? intval($fileValues[0]) + $hits     : $hits),
				$writings  === false ? 0 : (isset($fileValues[1]) ? intval($fileValues[1]) + $writings : $writings),
				$quota     === false ? 0 : (isset($fileValues[2]) ? intval($fileValues[2]) + $quota    : $quota),
				$posts     === false ? 0 : (isset($fileValues[3]) ? intval($fileValues[3]) + $posts    : $posts),
				$files     === false ? 0 : $cacheSize > 0 ? $cacheSize : 0,
			);

			fseek($fp, 0);
			ftruncate($fp, 0);
			fwrite($fp, implode(",", $newFileValues));
			flock($fp, LOCK_UN);
		}

		fclose($fp);
	}

	/**
	 * Checks disk quota.
	 * Returns true if quota is not exceeded.
	 *
	 * @return bool
	 */
	public static function checkQuota()
	{
		$arHTMLPagesOptions = self::getOptions();
		$cacheQuota = doubleval($arHTMLPagesOptions["~FILE_QUOTA"]);
		$statistic = self::readStatistic();
		if (count($statistic) > 0)
		{
			$cachedSize = $statistic["FILE_SIZE"];
		}
		else
		{
			$cachedSize = 0.0;
		}

		return ($cachedSize < $cacheQuota);
	}

	/**
	 * Updates disk quota and cache statistic
	 * @param float $bytes positive or negative value
	 */
	public static function updateQuota($bytes)
	{
		if ($bytes == 0.0)
		{
			return;
		}

		self::writeStatistic(0, 0, 0, 0, $bytes);
	}

	/**
	 * Sets NCC cookie
	 */
	public static function setNCC()
	{
		global $APPLICATION;
		$APPLICATION->set_cookie("NCC", "Y");
		$APPLICATION->set_cookie("CC", "", 0);
		self::deleteUserPrivateKey();
	}

	/**
	 * Sets CC cookie
	 */
	public static function setCC()
	{
		global $APPLICATION;
		$APPLICATION->set_cookie("CC", "Y");
		$APPLICATION->set_cookie("NCC", "", 0);

		$staticHTMLCache = \Bitrix\Main\Data\StaticHtmlCache::getInstance();
		$staticHTMLCache->setUserPrivateKey();
	}

	/**
	 * Removes all composite cookies
	 */
	public static function deleteCompositeCookies()
	{
		global $APPLICATION;
		$APPLICATION->set_cookie("NCC", "", 0);
		$APPLICATION->set_cookie("CC", "", 0);
		self::deleteUserPrivateKey();
	}

	/**
	 * OnUserLogin Event Handler
	 */
	public static function OnUserLogin()
	{
		if (!self::isOn())
		{
			return;
		}

		if (self::isCurrentUserCC())
		{
			self::setCC();
		}
		else
		{
			self::setNCC();
		}
	}

	public static function isCurrentUserCC()
	{
		global $USER;
		$options = self::getOptions();

		$groups = isset($options["GROUPS"]) && is_array($options["GROUPS"]) ? $options["GROUPS"] : array();
		$groups[] = "2";

		$diff = array_diff($USER->GetUserGroupArray(), $groups);
		return count($diff) === 0;
	}

	/**
	 * OnUserLogout Event Handler
	 */
	public static function OnUserLogout()
	{
		if (self::isOn())
		{
			self::deleteCompositeCookies();
		}
	}

	/**
	 * OnEpilog Event Handler
	 * @return void
	 */
	public static function OnEpilog()
	{
		if (self::isOn())
		{
			self::onEpilogComposite();
		}
	}

	private static function onEpilogComposite()
	{
		global $USER, $APPLICATION;

		if (is_object($USER) && $USER->IsAuthorized())
		{
			if (self::isCurrentUserCC())
			{
				if ($APPLICATION->get_cookie("CC") !== "Y" || $APPLICATION->get_cookie("NCC") === "Y")
				{
					self::setCC();
				}
			}
			else
			{
				if ($APPLICATION->get_cookie("NCC") !== "Y" || $APPLICATION->get_cookie("CC") === "Y")
				{
					self::setNCC();
				}
			}
		}
		else
		{
			if ($APPLICATION->get_cookie("NCC") === "Y" || $APPLICATION->get_cookie("CC") === "Y")
			{
				self::deleteCompositeCookies();
			}
		}

		if (Main\Data\Cache::shouldClearCache())
		{
			$server = Main\Context::getCurrent()->getServer();

			$queryString = DeleteParam(array(
				"clear_cache", "clear_cache_session", "bitrix_include_areas", "back_url_admin",
				"show_page_exec_time", "show_include_exec_time", "show_sql_stat", "bitrix_show_mode",
				"show_link_stat", "login"
			));

			$uri = new Bitrix\Main\Web\Uri($server->getRequestUri());
			$refinedUri = $queryString != "" ? $uri->getPath()."?".$queryString : $uri->getPath();

			$cachedFile = self::convertUriToPath($refinedUri, self::getHttpHost());

			$cacheStorage = Bitrix\Main\Data\StaticHtmlCache::getStaticHtmlStorage($cachedFile);
			if ($cacheStorage !== null)
			{
				$bytes = $cacheStorage->delete();
				if ($bytes !== false && $cacheStorage->shouldCountQuota())
				{
					self::updateQuota(-$bytes);
				}
			}
		}
	}

	/**
	 * OnChangeFile Event Handler
	 * @param $path
	 * @param $site
	 */
	public static function OnChangeFile($path, $site)
	{
		$domains = self::getDomains();
		$bytes = 0.0;
		foreach ($domains as $domain)
		{
			$cachedFile = self::convertUriToPath($path, $domain);
			$cacheStorage = Bitrix\Main\Data\StaticHtmlCache::getStaticHtmlStorage($cachedFile);
			if ($cacheStorage !== null)
			{
				$result = $cacheStorage->delete();
				if ($result !== false && $cacheStorage->shouldCountQuota())
				{
					$bytes += $result;
				}
			}
		}

		self::updateQuota(-$bytes);
	}

	private static function setErrorHandler()
	{
		set_error_handler(array(__CLASS__, "handleError"));
	}

	private static function restoreErrorHandler()
	{
		restore_error_handler();
	}

	public static function handleError($code, $message, $file, $line)
	{
		return true;
	}
}

/**
 * Represents interface for the html cache response
 * Class StaticHtmlCacheResponse
 */
abstract class StaticHtmlCacheResponse
{
	protected $cacheKey = null;
	protected $configuration = array();
	protected $htmlCacheOptions = array();

	/**
	 * @param string $cacheKey unique cache identifier
	 * @param array $configuration storage configuration
	 * @param array $htmlCacheOptions html cache options
	 */
	public function __construct($cacheKey, array $configuration, array $htmlCacheOptions)
	{
		$this->cacheKey = $cacheKey;
		$this->configuration = $configuration;
		$this->htmlCacheOptions = $htmlCacheOptions;
	}

	/**
	 * Returns the cache contents
	 * @return string|false
	 */
	abstract public function getContents();

	/**
	 * Returns true if content is gzipped
	 * @return bool
	 */
	abstract public function isGzipped();

	/**
	 * Returns the time the cache was last modified
	 * @return int|false
	 */
	abstract public function getLastModified();

	/**
	 * Returns the Entity Tag of the cache
	 * @return string|int
	 */
	abstract public function getEtag();

	/**
	 * Returns the content type of the cache
	 * @return string|false
	 */
	abstract public function getContentType();

	/**
	 * Checks whether the cache exists
	 *
	 * @return bool
	 */
	abstract public function exists();

	/**
	 * Should we count a quota limit
	 * @return bool
	 */
	abstract public function shouldCountQuota();
}

final class StaticHtmlMemcachedResponse extends StaticHtmlCacheResponse
{
	/**
	 * @var stdClass
	 */
	private $props = null;

	/**
	 * @var \Memcache
	 */
	private static $memcached = null;
	private static $connected = null;
	private $contents = null;
	private $flags = 0;

	const MEMCACHED_GZIP_FLAG = 65536;

	static public function __construct($cacheKey, array $configuration, array $htmlCacheOptions)
	{
		parent::__construct($cacheKey, $configuration, $htmlCacheOptions);
		self::getConnection($configuration, $htmlCacheOptions);
	}

	public function getContents()
	{
		if (self::$memcached === null)
		{
			return false;
		}

		if ($this->contents === null)
		{
			$this->contents = self::$memcached->get($this->cacheKey, $this->flags);
		}

		return $this->contents;
	}

	public function getLastModified()
	{
		return $this->getProp("mtime");
	}

	public function getEtag()
	{
		return $this->getProp("etag");
	}

	public function getContentType()
	{
		return $this->getProp("type");
	}

	public function exists()
	{
		return $this->getProps() !== false;
	}

	/**
	 * Returns true if content is gzipped
	 * @return bool
	 */
	public function isGzipped()
	{
		$this->getContents();
		return ($this->flags & self::MEMCACHED_GZIP_FLAG) === self::MEMCACHED_GZIP_FLAG;
	}

	/**
	 * Should we count a quota limit
	 * @return bool
	 */
	static public function shouldCountQuota()
	{
		return false;
	}

	/**
	 * @param array $htmlCacheOptions html cache options
	 * @return array
	 */
	private static function getServers(array $htmlCacheOptions)
	{
		$arServers = array();
		if ($htmlCacheOptions["STORAGE"] === "memcached_cluster")
		{
			$groupId = isset($htmlCacheOptions["MEMCACHED_CLUSTER_GROUP"]) ? $htmlCacheOptions["MEMCACHED_CLUSTER_GROUP"] : 1;
			$arServers = self::getClusterServers($groupId);
		}
		elseif (isset($htmlCacheOptions["MEMCACHED_HOST"]) && isset($htmlCacheOptions["MEMCACHED_PORT"]))
		{
			$arServers[] = array(
				"HOST" => $htmlCacheOptions["MEMCACHED_HOST"],
				"PORT" => $htmlCacheOptions["MEMCACHED_PORT"]
			);
		}

		return $arServers;
	}

	/**
	 * Gets clusters settings
	 * @param int $groupId
	 * @return array
	 */
	private static function getClusterServers($groupId)
	{
		$arServers = array();

		$arList = false;
		if (file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/cluster/memcache.php"))
		{
			include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/cluster/memcache.php");
		}

		if (defined("BX_MEMCACHE_CLUSTER") && is_array($arList))
		{
			foreach ($arList as $arServer)
			{
				if ($arServer["STATUS"] === "ONLINE" && $arServer["GROUP_ID"] == $groupId)
				{
					$arServers[] = $arServer;
				}
			}
		}

		return $arServers;
	}

	/**
	 * Returns the object that represents the connection to the memcached server
	 * @param array $configuration memcached configuration
	 * @param array $htmlCacheOptions html cache options
	 * @return Memcache|false
	 */
	public static function getConnection(array $configuration, array $htmlCacheOptions)
	{
		if (self::$memcached === null && self::$connected === null)
		{
			$arServers = self::getServers($htmlCacheOptions);
			$memcached = new \Memcache;
			if (count($arServers) === 1)
			{
				if ($memcached->connect($arServers[0]["HOST"], $arServers[0]["PORT"]))
				{
					self::$connected = true;
					self::$memcached = $memcached;
					register_shutdown_function(array(__CLASS__, "close"));
				}
				else
				{
					self::$connected = false;
				}
			}
			elseif (count($arServers) > 1)
			{
				self::$memcached = $memcached;
				foreach ($arServers as $arServer)
				{
					self::$memcached->addServer(
						$arServer["HOST"],
						$arServer["PORT"],
						true, //persistent
						($arServer["WEIGHT"] > 0? $arServer["WEIGHT"]: 1),
						1 //timeout
					);
				}
			}
			else
			{
				self::$connected = false;
			}
		}

		return self::$memcached;
	}

	/**
	 * Closes connection to the memcached server
	 */
	public static function close()
	{
		if (self::$memcached !== null)
		{
			self::$memcached->close();
			self::$memcached = null;
		}
	}

	/**
	 * Returns an array of the cache properties
	 *
	 * @return \stdClass|false
	 */
	public function getProps()
	{
		if ($this->props === null)
		{
			if (self::$memcached !== null)
			{
				$props = self::$memcached->get("~".$this->cacheKey);
				$this->props = is_object($props) ? $props : false;
			}
			else
			{
				$this->props = false;
			}
		}

		return $this->props;
	}

	/**
	 * Returns the $property value
	 * @param string $property the property name
	 *
	 * @return string|false
	 */
	public function getProp($property)
	{
		$props = $this->getProps();
		if ($props !== false && isset($props->{$property}))
		{
			return $props->{$property};
		}
		return false;
	}
}

final class StaticHtmlFileResponse extends StaticHtmlCacheResponse
{
	private $cacheFile = null;
	private $lastModified = null;
	private $contents = null;

	public function __construct($cacheKey, array $configuration, array $htmlCacheOptions)
	{
		parent::__construct($cacheKey, $configuration, $htmlCacheOptions);
		$pagesPath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages";

		if (file_exists($pagesPath.$this->cacheKey))
		{
			$this->cacheFile = $pagesPath.$this->cacheKey;
		}
	}

	public function getContents()
	{
		if ($this->cacheFile === null)
		{
			return false;
		}

		if ($this->contents === null)
		{
			$this->contents = file_get_contents($this->cacheFile);
			if (
				$this->contents !== false &&
				(
					strlen($this->contents) < 2500 ||
					!preg_match("/^[a-f0-9]{32}$/", substr($this->contents, -35, 32))
				)
			)
			{
				$this->contents = false;
			}
		}

		return $this->contents;
	}

	public function getLastModified()
	{
		if ($this->cacheFile === null)
		{
			return false;
		}

		if ($this->lastModified === null)
		{
			$this->lastModified = filemtime($this->cacheFile);
		}

		return $this->lastModified;

	}

	public function getEtag()
	{
		if ($this->cacheFile === null)
		{
			return false;
		}

		return md5(
			$this->cacheFile.
			filesize($this->cacheFile).
			$this->getLastModified()
		);
	}

	public function getContentType()
	{
		$contents = $this->getContents();
		$head = strpos($contents, "</head>");
		$meta = "#<meta\\s+http-equiv\\s*=\\s*(['\"])Content-Type(\\1)\\s+content\\s*=\\s*(['\"])(.*?)(\\3)#im";
		if ($head !== false && preg_match($meta, substr($contents, 0, $head), $match))
		{
			return $match[4];
		}

		return false;
	}

	public function exists()
	{
		return $this->cacheFile !== null;
	}

	/**
	 * Should we count a quota limit
	 * @return bool
	 */
	static public function shouldCountQuota()
	{
		return true;
	}

	/**
	 * Returns true if content is gzipped
	 * @return bool
	 */
	static public function isGzipped()
	{
		return false;
	}
}
