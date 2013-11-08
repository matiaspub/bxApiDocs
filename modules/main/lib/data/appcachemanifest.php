<?php
namespace Bitrix\Main\Data;

use Bitrix\Main\Application;

class AppCacheManifest
{

	CONST HASH_FILE_PATH = "/bitrix/cache/appcache/params/";
	CONST MANIFEST_DIR = "/bitrix/cache/appcache/manifests/";

	private static $instance;
	private $isEnable = false;
	private $pageURI = "";
	private $files = Array();
	private $network = Array();
	private $fallbackPages = Array();
	private $params = Array();
	private $isSided = false;

	private $isModified = false;
	private $receivedManifest = "";
	private $receivedCacheParams = Array();

	private function __construct()
	{
		//use CAppCacheManifest::getInstance();
	}


	private function __clone()
	{
		//you can't clone it

	}

	public static function getInstance()
	{
		if (is_null(self::$instance))
			self::$instance = new  AppCacheManifest();

		return self::$instance;
	}

	/**
	 * Creates or updates the manifest file for the page with usage its content.
	 *
	 * @param $content
	 */
	public static function setEnable($isEnable = true)
	{
		$selfObject = self::getInstance();
		if ($isEnable && !$selfObject->isEnable)
		{
			AddEventHandler("main", "OnBeforeEndBufferContent", Array(__CLASS__, "onBeforeEndBufferContent"));
			AddEventHandler("main", "OnEndBufferContent", Array(__CLASS__, "onEndBufferContent"));
			$selfObject->isEnable = true;
		}

	}

	public static function generate(&$content)
	{
		$manifest = AppCacheManifest::getInstance();
		$files = $manifest->getFilesFromContent($content);
		$manifest->setFiles($files);
		$manifest->setNetworkFiles(Array("*"));
		$manifest->create();

		return $manifest->getIsModified();
	}

	/**
	 * OnBeforeEndBufferContent handler
	 * @return array|mixed
	 */
	public static function onBeforeEndBufferContent()
	{
		global $APPLICATION;
		$selfObject = self::getInstance();
		$server = \Bitrix\Main\Context::getCurrent()->getServer();
		$params = Array();
		$appCacheUrl = $server->get("HTTP_BX_APPCACHE_URL");
		$appCacheParams = $server->get("HTTP_BX_APPCACHE_PARAMS");
		if (strlen($appCacheUrl) > 0)
		{
			//TODO compare $_SERVER["REQUEST_URI"] and $_SERVER["HTTP_BX_APPCACHE_URL"]
			$selfObject->setIsSided(true);
			$selfObject->setPageURI($appCacheUrl);
			if ($appCacheParams)
			{
				$params = json_decode($appCacheParams);
				$selfObject->setReceivedCacheParams($params);
			}
		}
		else
		{
			$selfObject->setPageURI($server->get("REQUEST_URI"));
			$APPLICATION->SetPageProperty("manifest", " manifest=\"/bitrix/tools/check_appcache.php?manifest_id=" . $selfObject->getCurrentManifestID() . "\"");
			$params = Array(
				"PAGE_URL" => $selfObject->getPageURI(),
				"PARAMS" => $selfObject->getAdditionalParams()
			);
		}

		return $params;
	}

	/*
	 * OnEndBufferContent handler
	 */
	public static function onEndBufferContent(&$content)
	{
		AppCacheManifest::generate($content);
	}

	/**
	 * Creates, rewrites the manifest file
	 * @return bool|string
	 */
	public function create()
	{
		$this->isModified = false;
		$manifestId = $this->getCurrentManifestID();

		if ($this->isSided)
		{
			$curManifestId = $this->getManifestID($this->pageURI, $this->receivedCacheParams);
			if ($curManifestId != $manifestId)
			{
				self::removeManifestById($curManifestId);
			}
		}

		$currentHashSum = md5(serialize($this->files) . serialize($this->fallbackPages) . serialize($this->network));
		$manifestCache = $this->readManifestCache($manifestId);
		if (!$manifestCache || $manifestCache["FILE_HASH"] != $currentHashSum)
		{
			$this->isModified = true;
		}
		else
			return $manifestId;

		$manifestText = "CACHE MANIFEST\n\n";
		$manifestText .= $this->getManifestDescription();
		$manifestText .= "#files" . "\n\n";
		$manifestText .= implode("\n", $this->files["files"]) . "\n\n";
		$manifestText .= "NETWORK:\n";
		$manifestText .= implode("\n", $this->network) . "\n\n";
		$manifestText .= "FALLBACK:\n\n";
		$countFallback = count($this->fallbackPages);
		for ($i = 0; $i < $countFallback; $i++)
			$manifestText .= $this->fallbackPages[$i]["online"] . " " . $this->fallbackPages[$i]["offline"] . "\n";

		$arFields = array(
			"ID"=> $manifestId,
			"TEXT"=> $manifestText,
			"FILE_HASH"=> $currentHashSum,
		);

		$success = $this->writeManifestCache($arFields);

		return ($success) ? $manifestId : false;
	}

	/**
	 * Parses the passed content to find css, js and images. Returns the array of files.
	 *
	 * @param $content
	 *
	 * @return array
	 */
	static function getFilesFromContent($content)
	{
		$arFileData = Array("files" => Array());
		$arFilesByType = Array();
		$arExtensions = Array("js", "css");
		$extension_regex = "(?:" . implode("|", $arExtensions) . ")";
		$regex = "/
				((?i:
					href=
					|src=
					|BX\\.loadCSS\\(
					|BX\\.loadScript\\(
					|jsUtils\\.loadJSFile\\(
					|background\\s*:\\s*url\\(
				))                                                   #attribute
				(\"|')                                               #open_quote
				([^?'\"]+\\.)                                        #href body
				(" . $extension_regex . ")                           #extentions
				(|\\?\\d+|\\?v=\\d+)                                 #params
				(\\2)                                                #close_quote
			/x";
		$match = Array();
		preg_match_all($regex, $content, $match);
		$link = $match[3];
		$extension = $match[4];
		$params = $match[5];
		$linkCount = count($link);
		for ($i = 0; $i < $linkCount; $i++)
		{
			$arFileData["files"][] = $arFilesByType[$extension[$i]][] = $link[$i] . $extension[$i] . $params[$i];
			$arFileData["mdate"][$link[$i] . $extension[$i]] = str_replace("?", "", $params[$i]);
			$arFilesByType[$extension[$i]][] = $link[$i] . $extension[$i];

		}

		if (array_key_exists("css", $arFilesByType))
		{
			$cssCount = count($arFilesByType["css"]);
			for ($j = 0; $j < $cssCount; $j++)
			{
				$fileContent = file_get_contents(Application::getDocumentRoot() . $arFilesByType["css"][$j]);
				$regex = '#([;\s:]*(?:url|@import)\s*\(\s*)(\'|"|)(.+?)(\2)\s*\)#si';
				$cssPath = dirname($arFilesByType["css"][$j]);
				preg_match_all($regex, $fileContent, $match);
				$matchCount = count($match[3]);
				for ($k = 0; $k < $matchCount; $k++)
				{
					$file = self::replaceUrlCSS($match[3][$k], addslashes($cssPath));
					if (!in_array($file, $arFileData["files"]))
						$arFileData["files"][] = $arFilesByType["img"][] = $file;
				}
			}

		}

		return $arFileData;
	}

	/**
	 * Replaces url to css-file with absolute path.
	 *
	 * @param $url
	 * @param $cssPath
	 *
	 * @return string
	 */
	private static function replaceUrlCSS($url, $cssPath)
	{
		if (strpos($url, "://") !== false || strpos($url, "data:") !== false)
			return $url;
		$url = trim(stripslashes($url), "'\" \r\n\t");
		if (substr($url, 0, 1) == "/")
			return $url;

		return $cssPath . '/' . $url;
	}

	/**
	 * Sets received cache params
	 *
	 * @param $receivedCacheParams
	 */
	public function setReceivedCacheParams($receivedCacheParams)
	{
		$this->receivedCacheParams = $receivedCacheParams;
	}

	/**
	 * Gets received cache parameters
	 * @return array
	 */
	public function getReceivedCacheParams()
	{
		return $this->receivedCacheParams;
	}

	/**
	 * Sets received path to manifest
	 *
	 * @param $receivedManifest
	 */
	public function setReceivedManifest($receivedManifest)
	{
		$this->receivedManifest = $receivedManifest;
	}

	public function getReceivedManifest()
	{
		return $this->receivedManifest;
	}

	public function setIsSided($isSided)
	{
		$this->isSided = $isSided;
	}

	public function getIsSided()
	{
		return $this->isSided;
	}

	public function setPageURI($pageURI = "")
	{
		$this->pageURI = $pageURI;
	}

	public function getPageURI()
	{
		return $this->pageURI;
	}

	public function setFiles($arFiles)
	{
		$this->files = $arFiles;
	}

	public function addFile($filePath)
	{
		$this->files[] = $filePath;
	}

	public function addAdditionalParam($name, $value)
	{
		$this->params[$name] = $value;
	}

	public function getAdditionalParams()
	{
		return $this->params;
	}

	public function setNetworkFiles($network)
	{
		$this->network = $network;
	}

	public function getNetworkFiles()
	{
		return $this->network;
	}

	public function addFallbackPage($onlinePage, $offlinePage)
	{
		$this->fallbackPages[] = Array(
			"online" => $onlinePage,
			"offline" => $offlinePage
		);
	}

	public function getFallbackPages()
	{
		return $this->fallbackPages;
	}

	public function getCurrentManifestID()
	{
		return $this->getManifestID($this->pageURI, $this->params);

	}

	public function getIsModified()
	{
		return $this->isModified;
	}

	private function getManifestDescription()
	{

		$manifestParams = "";
		$arCacheParams = $this->params;
		if (count($arCacheParams) > 0)
		{
			foreach ($arCacheParams as $key => $value)
				$manifestParams .= "#" . $key . "=" . $value . "\n";
		}

		$desc = "#Date: " . date("r") . "\n";
		$desc .= "#Page: " . $this->pageURI . "\n";
		$desc .= "#Params: \n" . $manifestParams . "\n\n";

		return $desc;
	}

	private function writeManifestCache($arFields)
	{
		$cache = new \CPHPCache();
		$manifestId = $arFields["ID"];
		$this->removeManifestById($manifestId);
		$cachePath = self::getCachePath($manifestId);
		$cache->StartDataCache(3600 * 24 * 365, $manifestId, $cachePath);
		$cache->EndDataCache($arFields);

		return true;
	}

	static public function readManifestCache($manifestId)
	{
		$cache = new \CPHPCache();
		$cachePath = self::getCachePath($manifestId);
		if($cache->InitCache(3600 * 24 * 365, $manifestId, $cachePath) )
		{
			return $cache->getVars();
		}

		return false;
	}

	private static function removeManifestById($manifestId)
	{
		$cache = new \CPHPCache();
		$cachePath = self::getCachePath($manifestId);
		return $cache->CleanDir($cachePath);
	}

	/**
	 * @param $manifestId
	 *
	 * @return string
	 */
	public static function getCachePath($manifestId)
	{
		$cachePath = "/appcache/" . substr($manifestId, 0, 2) . "/" . substr($manifestId, 2, 4) . "/";

		return $cachePath;
	}


	private function getManifestID($pageURI, $arParams)
	{
		$id = $pageURI;
		if (count($arParams) > 0)
		{
			$strCacheParams = "";
			foreach ($arParams as $key => $value)
				$strCacheParams .= $key . "=" . $value;

			$id .= $strCacheParams;
		}

		return md5($id);
	}

	public static function checkObsoleteManifest()
	{
		$server = \Bitrix\Main\Context::getCurrent()->getServer();
		$appCacheUrl = $server->get("HTTP_BX_APPCACHE_URL");
		$appCacheParams = $server->get("HTTP_BX_APPCACHE_PARAMS");
		if ($appCacheUrl)
		{
			$params = json_decode($appCacheParams);
			\Bitrix\Main\Data\AppCacheManifest::clear($appCacheUrl, $params);
		}
	}

	private static function clear($url, $params)
	{
		$manifestId = self::getManifestID($url, $params);
		if(self::readManifestCache($manifestId))
		{
			self::removeManifestById($manifestId);
			self::getInstance()->isModified = true;
		}

	}


}