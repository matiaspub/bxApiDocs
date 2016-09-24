<?php
namespace Bitrix\Main\Page;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Data\AppCacheManifest;
use Bitrix\Main\Data\StaticHtmlCache;
use Bitrix\Main\Loader;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

final class Frame
{
	private static $instance;
	private static $isEnabled = false;
	private static $isAjaxRequest = null;
	private static $useHTMLCache = false;
	private static $onBeforeHandleKey = false;
	private static $onRestartBufferHandleKey = false;
	private static $onBeforeLocalRedirect = false;
	private static $autoUpdate = true;
	private static $autoUpdateTTL = 0;
	private $isCompositeInjected = false;
	private $isRedirect = false;
	private $isBufferRestarted = false;

	/**
	 * use self::getInstance()
	 */
	private function __construct()
	{

	}

	/**
	 * you can't clone it
	 */
	private function __clone()
	{

	}

	/**
	 * Singleton instance.
	 *
	 * @return Frame
	 */
	
	/**
	* <p>Статический метод возвращает экземпляр метода.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Page\Frame 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/frame/getinstance.php
	* @author Bitrix
	*/
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new Frame();
		}

		return self::$instance;
	}

	/**
	 * This method returns the divided content.
	 * The content is divided by two parts - static and dynamic.
	 * Example of returned value:
	 * <code>
	 * array(
	 *    "static"=>"Hello World!"
	 *    "dynamic"=>array(
	 *        array("ID"=>"someID","CONTENT"=>"someDynamicContent", "HASH"=>"md5ofDynamicContent")),
	 *        array("ID"=>"someID2","CONTENT"=>"someDynamicContent2", "HASH"=>"md5ofDynamicContent2"))
	 * );
	 * </code>
	 *
	 * @param string $content Html page content.
	 *
	 * @return array
	 */
	public function getDividedPageData($content)
	{
		$data = array(
			"dynamic" => array(),
			"static"  => "",
			"md5"     => "",
		);

		$dynamicAreas = FrameStatic::getDynamicAreas();
		if (count($dynamicAreas) > 0 && ($areas = self::getFrameIndexes($content)) !== false)
		{
			$offset = 0;
			$pageBlocks = $this->getPageBlocks();
			foreach ($areas as $area)
			{
				$dynamicArea = FrameStatic::getDynamicArea($area->id);
				if ($dynamicArea === null)
				{
					continue;
				}

				$realId = $dynamicArea->getContainerId() !== null ? $dynamicArea->getContainerId() : "bxdynamic_".$area->id;
				$assets =  Asset::getInstance()->getAssetInfo($dynamicArea->getAssetId(), $dynamicArea->getAssetMode());
				$areaContent = \CUtil::BinSubstr($content, $area->openTagEnd, $area->closingTagStart - $area->openTagEnd);
				$areaContentMd5 = md5($areaContent);

				$blockId = $dynamicArea->getId();
				$hasSameContent =
					isset($pageBlocks[$blockId]) &&
					isset($pageBlocks[$blockId]["hash"]) &&
					$pageBlocks[$blockId]["hash"] === $areaContentMd5;

				if (!$hasSameContent)
				{
					$data["dynamic"][] = array(
						"ID" => $realId,
						"CONTENT" => $areaContent,
						"HASH" => $areaContentMd5,
						"PROPS"=> array(
							"CONTAINER_ID" => $dynamicArea->getContainerId(),
							"USE_BROWSER_STORAGE" => $dynamicArea->getBrowserStorage(),
							"AUTO_UPDATE" => $dynamicArea->getAutoUpdate(),
							"USE_ANIMATION" => $dynamicArea->getAnimation(),
							"CSS" => $assets["CSS"],
							"JS" => $assets["JS"],
							"STRINGS" => $assets["STRINGS"],
						),
					);
				}

				$data["static"] .= \CUtil::BinSubstr($content, $offset, $area->openTagStart - $offset);
				
				if ($dynamicArea->getContainerId() === null)
				{
					$data["static"] .= 
						'<div id="bxdynamic_'.$area->id.'_start" style="display:none"></div>'.
						$dynamicArea->getStub().
						'<div id="bxdynamic_'.$area->id.'_end" style="display:none"></div>';
				}
				else
				{
					$data["static"] .= $dynamicArea->getStub();
				}

				$offset = $area->closingTagEnd;
			}

			$data["static"] .= \CUtil::BinSubstr($content, $offset);
		}
		else
		{
			$data["static"] = $content;
		}

		self::replaceSessid($data["static"]);
		Asset::getInstance()->moveJsToBody($data["static"]);

		$data["md5"] = md5($data["static"]);

		return $data;
	}

	private function getPageBlocks()
	{
		$blocks = array();
		$json = Context::getCurrent()->getServer()->get("HTTP_BX_CACHE_BLOCKS");
		if ($json !== null && strlen($json) > 0)
		{
			$blocks = json_decode($json, true);
			if ($blocks === null)
			{
				$blocks = array();
			}
		}

		return $blocks;
	}

	/**
	 * Replaces bitrix sessid in the $content
	 * @param string $content
	 */
	private static function replaceSessid(&$content)
	{
		$methodInvocations = bitrix_sessid_post("sessid", true);
		if ($methodInvocations > 0)
		{
			$content = str_replace("value=\"".bitrix_sessid()."\"", "value=\"\"", $content);
		}
	}

	/**
	 * @param string $content
	 * @return array|bool
	 */
	private static function getFrameIndexes($content)
	{
		$openTag = "<!--'start_frame_cache_";
		$closingTag = "<!--'end_frame_cache_";
		$ending = "'-->";

		$areas = array();
		$offset = 0;
		while (($openTagStart = \CUtil::BinStrpos($content, $openTag, $offset)) !== false)
		{
			$endingPos = \CUtil::BinStrpos($content, $ending, $openTagStart);
			if ($endingPos === false)
			{
				break;
			}

			$idStart = $openTagStart + strlen($openTag);
			$idLength = $endingPos - $idStart;
			$areaId = \CUtil::BinSubstr($content, $idStart, $idLength);
			$openTagEnd = $endingPos + strlen($ending);

			$realClosingTag = $closingTag.$areaId.$ending;
			$closingTagStart = \CUtil::BinStrpos($content, $realClosingTag, $openTagEnd);
			if ($closingTagStart === false)
			{
				$offset = $openTagEnd;
				continue;
			}

			$closingTagEnd = $closingTagStart + strlen($realClosingTag);

			$area = new \stdClass();
			$area->id = $areaId;
			$area->openTagStart = $openTagStart;
			$area->openTagEnd = $openTagEnd;
			$area->closingTagStart = $closingTagStart;
			$area->closingTagEnd = $closingTagEnd;
			$areas[] = $area;

			$offset = $closingTagEnd;
		}

		return count($areas) > 0 ? $areas : false;
	}

	/**
	 * OnBeforeEndBufferContent handler.
	 * Prepares the stage for composite mode handler.
	 *
	 * @return void
	 */
	public function onBeforeEndBufferContent()
	{
		$params = array();
		if (self::getUseAppCache())
		{
			$manifest = AppCacheManifest::getInstance();
			$params = $manifest->OnBeforeEndBufferContent();
			$params["CACHE_MODE"] = "APPCACHE";
			$params["PAGE_URL"] = Context::getCurrent()->getServer()->getRequestUri();
		}
		elseif (self::getUseHTMLCache())
		{
			$staticHTMLCache = StaticHtmlCache::getInstance();
			$staticHTMLCache->onBeforeEndBufferContent();

			if ($staticHTMLCache->isCacheable())
			{
				$params["CACHE_MODE"] = "HTMLCACHE";

				if (self::isBannerEnabled())
				{
					$options = \CHTMLPagesCache::getOptions();
					$params["banner"] = array(
						"url" => GetMessage("COMPOSITE_BANNER_URL"),
						"text" => GetMessage("COMPOSITE_BANNER_TEXT"),
						"bgcolor" => isset($options["BANNER_BGCOLOR"]) ? $options["BANNER_BGCOLOR"] : "",
						"style" => isset($options["BANNER_STYLE"]) ? $options["BANNER_STYLE"] : ""
					);
				}
			}
			else
			{
				return;
			}
		}

		$params["storageBlocks"] = array();
		$params["dynamicBlocks"] = array();
		$dynamicAreas = FrameStatic::getDynamicAreas();
		foreach ($dynamicAreas as $id => $dynamicArea)
		{
			$params["dynamicBlocks"][$dynamicArea->getId()] = array(
				"hash" => md5($dynamicArea->getStub())
			);

			if ($dynamicArea->getBrowserStorage())
			{
				$realId = $dynamicArea->getContainerId() !== null ? $dynamicArea->getContainerId() : "bxdynamic_".$id;
				$params["storageBlocks"][] = $realId;
			}
		}

		$params["AUTO_UPDATE"] = self::getAutoUpdate();
		$params["AUTO_UPDATE_TTL"] = self::getAutoUpdateTTL();

		Asset::getInstance()->addString(
			$this->getInjectedJs($params),
			false,
			AssetLocation::BEFORE_CSS,
			self::getUseHTMLCache() ? AssetMode::COMPOSITE : AssetMode::ALL
		);

		$this->isCompositeInjected = true;
	}

	/**
	 * @param $content
	 * @return null|string
	 */
	public function startBuffering($content)
	{
		if (!$this->isEnabled() ||
			!$this->isCompositeInjected ||
			!is_object($GLOBALS["APPLICATION"]) ||
			defined("BX_BUFFER_SHUTDOWN"))
		{
			return null;
		}

		$newBuffer = $GLOBALS["APPLICATION"]->buffer_content;
		$cnt = count($GLOBALS["APPLICATION"]->buffer_content_type);

		Asset::getInstance()->setMode(AssetMode::COMPOSITE);

		$this->isCompositeInjected = false; //double-check
		for ($i = 0; $i < $cnt; $i++)
		{
			$method = $GLOBALS["APPLICATION"]->buffer_content_type[$i]["F"];
			if (!is_array($method) || count($method) !== 2 || $method[0] !== $GLOBALS["APPLICATION"])
			{
				continue;
			}

			if (in_array($method[1], array("GetCSS", "GetHeadScripts", "GetHeadStrings")))
			{
				$newBuffer[$i*2+1] = call_user_func_array($method, $GLOBALS["APPLICATION"]->buffer_content_type[$i]["P"]);
				if ($this->isCompositeInjected !== true && $method[1] === "GetHeadStrings")
				{
					$this->isCompositeInjected = \CUtil::BinStrpos($newBuffer[$i*2+1], "w.frameRequestStart") !== false;
				}
			}
		}

		Asset::getInstance()->setMode(AssetMode::STANDARD);

		return $this->isCompositeInjected === true ? implode("", $newBuffer).$content : null;
	}

	/**
	 *
	 * Returns true if $originalContent was modified
	 * @param $originalContent
	 * @param $compositeContent
	 *
	 * @return bool
	 * @internal
	 */
	public function endBuffering(&$originalContent, $compositeContent)
	{
		if (!$this->isEnabled() || $compositeContent === null || defined("BX_BUFFER_SHUTDOWN"))
		{
			if (self::isAjaxRequest() && $this->isRedirect === false)
			{
				$originalContent = $this->getAjaxError();
				StaticHtmlCache::getInstance()->delete();
				return true;
			}

			return false;
		}

		if (function_exists("getmoduleevents"))
		{
			foreach(GetModuleEvents("main", "OnEndBufferContent", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array(&$compositeContent));
			}
		}

		$compositeContent = $this->processPageContent($compositeContent);
		if (self::isAjaxRequest())
		{
			$originalContent = $compositeContent;
			return true;
		}
		elseif (self::getUseAppCache())
		{
			$originalContent = $compositeContent;
			return true;
		}

		return false;
	}

	/**
	 * * There are two variants of content's modification in this method.
	 * The first one:
	 * If it's ajax-hit the content will be replaced by json data with dynamic blocks,
	 * javascript files and etc. - dynamic part
	 *
	 * The second one:
	 * If it's simple hit the content will be modified also,
	 * all dynamic blocks will be cut out of the content - static part.
	 *
	 * @param string $content Html page content.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод создаёт контент страницы. Существует два варианта модификации контента динамической зоны этим методом:</p> <ol> <li>Если это ajax хит, то контент будет передан как json данные с динамической областью, файлами javascript и так далее.</li> <li>Если это простой хит, то контент будет модифицирован так же как все динамические блоки: вырезан из контента.</li>   </ol>
	*
	*
	* @param string $content  Контент html страницы.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/frame/processpagecontent.php
	* @author Bitrix
	*/
	public function processPageContent($content)
	{
		global $APPLICATION, $USER;

		$dividedData = $this->getDividedPageData($content);
		$htmlCacheChanged = false;

		if (self::getUseHTMLCache())
		{
			$staticHTMLCache = StaticHtmlCache::getInstance();
			if ($staticHTMLCache->isCacheable())
			{
				$cacheExists = $staticHTMLCache->exists();
				$rewriteCache = $staticHTMLCache->getMd5() !== $dividedData["md5"];
				if (self::getAutoUpdate() && self::getAutoUpdateTTL() > 0 && $cacheExists)
				{
					$mtime = $staticHTMLCache->getLastModified();
					if ($mtime !== false && ($mtime + self::getAutoUpdateTTL()) > time())
					{
						$rewriteCache = false;
					}
				}

				$invalidateCache = self::getAutoUpdate() === false && self::isInvalidationRequest();

				if (!$cacheExists || $rewriteCache || $invalidateCache)
				{
					if ($invalidateCache || FrameLocker::lock($staticHTMLCache->getCacheKey()))
					{
						$success = $staticHTMLCache->write($dividedData["static"], $dividedData["md5"]);

						if ($success)
						{
							$htmlCacheChanged = true;
							$staticHTMLCache->setUserPrivateKey();
						}

						FrameLocker::unlock($staticHTMLCache->getCacheKey());
					}
				}
			}
			else
			{
				$staticHTMLCache->delete();
				return $this->getAjaxError();
			}
		}

		if (self::getUseAppCache() == true) //Do we use html5 application cache?
		{
			AppCacheManifest::getInstance()->generate($dividedData["static"]);
		}
		else
		{
			AppCacheManifest::checkObsoleteManifest();
		}

		if (self::isAjaxRequest())
		{
			self::sendRandHeader();

			header("Content-Type: application/x-javascript; charset=".SITE_CHARSET);
			header("X-Bitrix-Composite: Ajax ".($htmlCacheChanged ? "(changed)" : "(stable)"));

			$content = array(
				"js"                => $APPLICATION->arHeadScripts,
				"additional_js"     => $APPLICATION->arAdditionalJS,
				"lang"              => \CJSCore::GetCoreMessages(),
				"css"               => $APPLICATION->GetCSSArray(),
				"htmlCacheChanged"  => $htmlCacheChanged,
				"isManifestUpdated" => AppCacheManifest::getInstance()->getIsModified(),
				"dynamicBlocks"     => $dividedData["dynamic"],
				"spread"            => array_map(array("CUtil", "JSEscape"), $APPLICATION->GetSpreadCookieUrls()),
			);

			if($USER->IsAuthorized() && $this->getUseAppCache())
			{
				if(Loader::includeModule("pull") && \CPullOptions::CheckNeedRun())
				{
					$content["pull"] = \CPullChannel::GetConfig($USER->GetID());
				}
			}

			$content = \CUtil::PhpToJSObject($content);
		}
		else
		{
			$content = $dividedData["static"];
		}

		return $content;
	}

	private function getAjaxError($errorMsg = null)
	{
		$error = "unknown";
		if ($errorMsg !== null)
		{
			$error = $errorMsg;
		}
		elseif ($this->isBufferRestarted)
		{
			$error = "buffer_restarted";
		}
		elseif (!$this->isEnabled())
		{
			$error = "not_enabled";
		}
		elseif (defined("BX_BUFFER_SHUTDOWN"))
		{
			$error = "php_shutdown";
		}
		elseif (!StaticHtmlCache::getInstance()->isCacheable())
		{
			$error = "not_cacheable";
		}
		elseif (!$this->isCompositeInjected)
		{
			$error = "not_injected";
		}

		header("X-Bitrix-Composite: Ajax (error:".$error.")");
		self::sendRandHeader();

		$response = array(
			"error" => true,
			"reason" => $error,
		);

		return \CUtil::PhpToJSObject($response);
	}

	/**
	 * OnBeforeRestartBuffer event handler.
	 * Disables composite mode when called.
	 *
	 * @return void
	 */
	public static function onBeforeRestartBuffer()
	{
		self::getInstance()->isBufferRestarted = true;
		self::setEnable(false);

		if (defined("BX_COMPOSITE_DEBUG") && BX_COMPOSITE_DEBUG === true)
		{
			AddMessage2Log(
				"RestartBuffer method was invoked\n".
				"Request URI: ".$_SERVER["REQUEST_URI"]."\n".
				"Script: ".(isset($_SERVER["REAL_FILE_PATH"]) ? $_SERVER["REAL_FILE_PATH"] : $_SERVER["SCRIPT_NAME"]),
				"composite"
			);
		}
	}

	public static function onBeforeLocalRedirect(&$url, $skip_security_check, $isExternal)
	{
		global $APPLICATION;
		if (!self::isAjaxRequest() || ($isExternal && $skip_security_check !== true))
		{
			return;
		}

		$response = array(
			"error" => true,
			"reason" => "redirect",
			"redirect_url" => $url,
		);

		self::setEnable(false);
		if ($APPLICATION->buffered)
		{
			$APPLICATION->RestartBuffer();
		}

		self::getInstance()->isRedirect = true;
		StaticHtmlCache::getInstance()->delete();

		header("X-Bitrix-Composite: Ajax (error:redirect)");
		self::sendRandHeader();
		echo \CUtil::PhpToJSObject($response);

		die();
	}

	/**
	 * Sets isEnable property value and attaches needed handlers.
	 *
	 * @param bool $isEnabled Mode control flag.
	 *
	 * @return void
	 */
	
	/**
	* <p>Статический метод устанавливает значения свойств <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/frame/isenabled.php">isEnabled</a> и добавляет необходимые обработчики. Метод включает или выключает композитный режим для страницы в зависимости от флажка в аргументе.</p>
	*
	*
	* @param boolean $isEnabled = true Флаг управления режимом.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/frame/setenable.php
	* @author Bitrix
	*/
	public static function setEnable($isEnabled = true)
	{
		if ($isEnabled && !self::$isEnabled)
		{
			self::$onBeforeHandleKey = AddEventHandler("main", "OnBeforeEndBufferContent", array(self::getInstance(), "onBeforeEndBufferContent"));
			self::$onRestartBufferHandleKey = AddEventHandler("main", "OnBeforeRestartBuffer", array(__CLASS__, "onBeforeRestartBuffer"));
			self::$onBeforeLocalRedirect = AddEventHandler("main", "OnBeforeLocalRedirect", array(__CLASS__, "onBeforeLocalRedirect"), 2);
			self::$isEnabled = true;
			\CJSCore::init(array("fc"), false);
		}
		elseif (!$isEnabled && self::$isEnabled)
		{
			if (self::$onBeforeHandleKey >= 0)
			{
				RemoveEventHandler("main", "OnBeforeEndBufferContent", self::$onBeforeHandleKey);
			}

			if (self::$onRestartBufferHandleKey >= 0)
			{
				RemoveEventHandler("main", "OnBeforeRestartBuffer", self::$onRestartBufferHandleKey);
			}

			if (self::$onBeforeLocalRedirect >= 0)
			{
				RemoveEventHandler("main", "OnBeforeLocalRedirect", self::$onBeforeLocalRedirect);
			}

			self::$isEnabled = false;
		}
	}

	/**
	 * Gets isEnabled property.
	 *
	 * @return boolean
	 */
	
	/**
	* <p>Статический метод получает параметры включения композитного режима.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/frame/isenabled.php
	* @author Bitrix
	*/
	public static function isEnabled()
	{
		return self::$isEnabled;
	}

	/**
	 * Sets useAppCache property.
	 *
	 * @param boolean $useAppCache AppCache mode control flag.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает свойства используемого AppCache.</p>
	*
	*
	* @param boolean $useAppCache = true Флаг включения режима AppCache.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/frame/setuseappcache.php
	* @author Bitrix
	*/
	static public function setUseAppCache($useAppCache = true)
	{
		if (self::getUseAppCache())
			self::getInstance()->setUseHTMLCache(false);
		$appCache = AppCacheManifest::getInstance();
		$appCache->setEnabled($useAppCache);
	}

	/**
	 * Gets useAppCache property.
	 *
	 * @return boolean
	 */
	
	/**
	* <p>Статический метод получает параметры включения AppCache.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/frame/getuseappcache.php
	* @author Bitrix
	*/
	public static function getUseAppCache()
	{
		$appCache = AppCacheManifest::getInstance();
		return $appCache->isEnabled();
	}

	/**
	 * Sets useHTMLCache property.
	 *
	 * @param boolean $useHTMLCache Composite mode control flag.
	 *
	 * @return void
	 */
	
	/**
	* <p>Статический метод устанавливает свойства используемого HTML кеша.</p>
	*
	*
	* @param boolean $useHTMLCache = true Флаг режима Композита.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/frame/setusehtmlcache.php
	* @author Bitrix
	*/
	public static function setUseHTMLCache($useHTMLCache = true)
	{
		self::$useHTMLCache = $useHTMLCache;
		self::setEnable();
	}

	/**
	 * Gets useHTMLCache property.
	 *
	 * @return boolean
	 */
	
	/**
	* <p>Статический метод получает параметры включения HTMLCache.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/frame/getusehtmlcache.php
	* @author Bitrix
	*/
	public static function getUseHTMLCache()
	{
		return self::$useHTMLCache;
	}

	/**
	 * Sets autoUpdate property
	 * @param bool $flag
	 * @return void
	 */
	
	/**
	* <p>Статический метод устанавливает параметры автообновления кеша.</p>
	*
	*
	* @param boolean $flag  
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/frame/setautoupdate.php
	* @author Bitrix
	*/
	public static function setAutoUpdate($flag)
	{
		self::$autoUpdate = $flag === false ? false : true;
	}

	/**
	 * Gets autoUpdate property
	 * @return bool
	 */
	
	/**
	* <p>Статический метод получает параметры автообновления кеша.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/frame/getautoupdate.php
	* @author Bitrix
	*/
	public static  function getAutoUpdate()
	{
		return self::$autoUpdate;
	}
	/**
	 * Sets auto update ttl
	 * @param int $ttl - number of seconds
	 * @return void
	 */
	
	/**
	* <p>Статический метод устанавливает TTL автообновления кеша</p>
	*
	*
	* @param integer $ttl  Число секунд
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/frame/setautoupdatettl.php
	* @author Bitrix
	*/
	public static function setAutoUpdateTTL($ttl)
	{
		self::$autoUpdateTTL = intval($ttl);
	}

	/**
	 * Gets auto update ttl
	 * @return int
	 */
	
	/**
	* <p>Статический метод получает TTL автообновления кеша.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/frame/getautoupdatettl.php
	* @author Bitrix
	*/
	public static function getAutoUpdateTTL()
	{
		return self::$autoUpdateTTL;
	}

	/**
	 * Sets useHTMLCache property.
	 *
	 * @param boolean $preventAutoUpdate property.
	 *
	 * @deprecated use setAutoUpdate
	 * @return void
	 */
	public static function setPreventAutoUpdate($preventAutoUpdate = true)
	{
		self::$autoUpdate = !$preventAutoUpdate;
	}

	/**
	 * Gets preventAutoUpdate property.
	 *
	 * @return boolean
	 * @deprecated use getAutoUpdate
	 */
	public static function getPreventAutoUpdate()
	{
		return !self::$autoUpdate;
	}

	/**
	 * Returns true if current request was initiated by Ajax.
	 *
	 * @return boolean
	 */
	
	/**
	* <p>Статический метод возвращает <i>true</i> если текущий запрос инициализирован Ajax.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/frame/isajaxrequest.php
	* @author Bitrix
	*/
	public static function isAjaxRequest()
	{
		if (self::$isAjaxRequest == null)
		{
			$actionType = Context::getCurrent()->getServer()->get("HTTP_BX_ACTION_TYPE");
			self::$isAjaxRequest = (
				$actionType == "get_dynamic"
				|| (
					defined("actionType")
					&& constant("actionType") == "get_dynamic"
				)
			);
		}

		return self::$isAjaxRequest;
	}

	public static function isInvalidationRequest()
	{
		return
			self::isAjaxRequest() &&
			Context::getCurrent()->getServer()->get("HTTP_BX_INVALIDATE_CACHE") === "Y";
	}

	public static function sendRandHeader()
	{
		$bxRandom = \CHTMLPagesCache::getAjaxRandom();
		if ($bxRandom !== false)
		{
			header("BX-RAND: ".$bxRandom);
		}
	}

	/**
	 * Returns JS minified code that will do dynamic hit to the server.
	 * The code is returned in the 'start' key of the array.
	 *
	 * @param array $params
	 * @return array[string]string
	 */
	protected function getInjectedJs($params = array())
	{
		$vars = \CUtil::PhpToJSObject($params);

		$inlineJS = <<<JS
			(function(w, d) {

			var v = w.frameCacheVars = $vars;
			var inv = false;
			if (v.AUTO_UPDATE === false)
			{
				if (v.AUTO_UPDATE_TTL && v.AUTO_UPDATE_TTL > 0)
				{
					var lm = Date.parse(d.lastModified);
					if (!isNaN(lm))
					{
						var td = new Date().getTime();
						if ((lm + v.AUTO_UPDATE_TTL * 1000) >= td)
						{
							w.frameRequestStart = false;
							w.preventAutoUpdate = true;
							return;
						}
						inv = true;
					}
				}
				else
				{
					w.frameRequestStart = false;
					w.preventAutoUpdate = true;
					return;
				}
			}

			var r = w.XMLHttpRequest ? new XMLHttpRequest() : (w.ActiveXObject ? new w.ActiveXObject("Microsoft.XMLHTTP") : null);
			if (!r) { return; }

			w.frameRequestStart = true;

			var m = v.CACHE_MODE; var l = w.location; var x = new Date().getTime();
			var q = "?bxrand=" + x + (l.search.length > 0 ? "&" + l.search.substring(1) : "");
			var u = l.protocol + "//" + l.host + l.pathname + q;

			r.open("GET", u, true);
			r.setRequestHeader("BX-ACTION-TYPE", "get_dynamic");
			r.setRequestHeader("BX-CACHE-MODE", m);
			r.setRequestHeader("BX-CACHE-BLOCKS", v.dynamicBlocks ? JSON.stringify(v.dynamicBlocks) : "");
			if (inv)
			{
				r.setRequestHeader("BX-INVALIDATE-CACHE", "Y");
			}
			
			try { r.setRequestHeader("BX-REF", d.referrer || "");} catch(e) {}

			if (m === "APPCACHE")
			{
				r.setRequestHeader("BX-APPCACHE-PARAMS", JSON.stringify(v.PARAMS));
				r.setRequestHeader("BX-APPCACHE-URL", v.PAGE_URL ? v.PAGE_URL : "");
			}

			r.onreadystatechange = function() {
				if (r.readyState != 4) { return; }
				var a = r.getResponseHeader("BX-RAND");
				var b = w.BX && w.BX.frameCache ? w.BX.frameCache : false;
				if (a != x || !((r.status >= 200 && r.status < 300) || r.status === 304 || r.status === 1223 || r.status === 0))
				{
					var f = {error:true, reason:a!=x?"bad_rand":"bad_status", url:u, xhr:r, status:r.status};
					if (w.BX && w.BX.ready)
					{
						BX.ready(function() {
							setTimeout(function(){
								BX.onCustomEvent("onFrameDataRequestFail", [f]);
							}, 0);
						});
					}
					else
					{
						w.frameRequestFail = f;
					}
					return;
				}

				if (b)
				{
					b.onFrameDataReceived(r.responseText);
					if (!w.frameUpdateInvoked)
					{
						b.update(false);
					}
					w.frameUpdateInvoked = true;
				}
				else
				{
					w.frameDataString = r.responseText;
				}
			};

			r.send();

			})(window, document);
JS;

		$html = "";
		if (self::isBannerEnabled())
		{
			$html .=
				'<style type="text/css">'.
					str_replace(array("\n", "\t"), "", self::getInjectedCSS()).
				"</style>\n";
		}

		$html .=
			'<script type="text/javascript" data-skip-moving="true">'.
				str_replace(array("\n", "\t"), "", $inlineJS).
			"</script>";

		return $html;
	}

	/**
	 * Returns css string to be injected.
	 *
	 * @return string
	 */
	public static function getInjectedCSS()
	{
		return <<<CSS

			.bx-composite-btn {
				background: url(/bitrix/images/main/composite/sprite-1x.png) no-repeat right 0 #e94524;
				border-radius: 15px;
				color: #ffffff !important;
				display: inline-block;
				line-height: 30px;
				font-family: "Helvetica Neue", Helvetica, Arial, sans-serif !important;
				font-size: 12px !important;
				font-weight: bold !important;
				height: 31px !important;
				padding: 0 42px 0 17px !important;
				vertical-align: middle !important;
				text-decoration: none !important;
			}

			@media screen 
  				and (min-device-width: 1200px) 
  				and (max-device-width: 1600px) 
  				and (-webkit-min-device-pixel-ratio: 2)
  				and (min-resolution: 192dpi) {
					.bx-composite-btn {
						background-image: url(/bitrix/images/main/composite/sprite-2x.png);
						background-size: 42px 124px;
					}
			}

			.bx-composite-btn-fixed {
				position: absolute;
				top: -45px;
				right: 15px;
				z-index: 10;
			}

			.bx-btn-white {
				background-position: right 0;
				color: #fff !important;
			}

			.bx-btn-black {
				background-position: right -31px;
				color: #000 !important;
			}

			.bx-btn-red {
				background-position: right -62px;
				color: #555 !important;
			}

			.bx-btn-grey {
				background-position: right -93px;
				color: #657b89 !important;
			}

			.bx-btn-border {
				border: 1px solid #d4d4d4;
				height: 29px !important;
				line-height: 29px !important;
			}

			.bx-composite-loading {
				display: block;
				width: 40px;
				height: 40px;
				background: url(/bitrix/images/main/composite/loading.gif);
			}
CSS;
	}

	/**
	 * Checks whether HTML Cache should be enabled.
	 *
	 * @return void
	 */
	public static function shouldBeEnabled()
	{
		if (defined("USE_HTML_STATIC_CACHE") && USE_HTML_STATIC_CACHE === true)
		{
			if (
				!defined("BX_SKIP_SESSION_EXPAND") &&
				(!defined("ADMIN_SECTION") || (defined("ADMIN_SECTION") && ADMIN_SECTION != "Y"))
			)
			{
				if (self::isInvalidationRequest())
				{
					$cacheKey = \CHTMLPagesCache::convertUriToPath(
						\CHTMLPagesCache::getRequestUri(),
						\CHTMLPagesCache::getHttpHost(), 
						\CHTMLPagesCache::getRealPrivateKey(StaticHtmlCache::getPrivateKey())
					);

					if (!FrameLocker::lock($cacheKey))
					{
						die(Frame::getInstance()->getAjaxError("invalidation_request_locked"));
					}
				}

				self::setUseHTMLCache();

				$options = \CHTMLPagesCache::getOptions();
				if (isset($options["AUTO_UPDATE"]) && $options["AUTO_UPDATE"] === "N")
				{
					self::setAutoUpdate(false);
				}

				if (isset($options["AUTO_UPDATE_TTL"]))
				{
					self::setAutoUpdateTTL($options["AUTO_UPDATE_TTL"]);
				}

				// define("BX_SKIP_SESSION_EXPAND", true);
			}
		}
		elseif (
			(defined("ENABLE_HTML_STATIC_CACHE_JS") && ENABLE_HTML_STATIC_CACHE_JS === true) &&
			(!defined("ADMIN_SECTION") || ADMIN_SECTION !== true))
		{
			\CJSCore::init(array("fc")); //to warm up localStorage
		}
	}

	/**
	 * Checks if admin panel will be shown or not.
	 * Disables itself if panel will be show.
	 *
	 * @return void
	 */
	public static function checkAdminPanel()
	{
		if ($GLOBALS["APPLICATION"]->showPanelWasInvoked === true
			&& self::getUseHTMLCache()
			&& !self::isAjaxRequest()
			&& \CTopPanel::shouldShowPanel()
		)
		{
			self::setEnable(false);
		}
	}

	/**
	 * Returns true if we should inject banner into a page.
	 * @return bool
	 */
	
	/**
	* <p>Статический метод возвращает <i>true</i> если баннер должен быть добавлен на страницу.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/frame/isbannerenabled.php
	* @author Bitrix
	*/
	public static function isBannerEnabled()
	{
		return Option::get("main", "~show_composite_banner", "Y") == "Y";
	}

	/* =========================Deprecated Methods ===============================*/

	/**
	 * Gets ids of the dynamic blocks.
	 *
	 * @deprecated
	 * @return array
	 */
	static public function getDynamicIDs()
	{
		return FrameStatic::getDynamicIDs();
	}

	/**
	 * Returns the identifier of current dynamic area.
	 *
	 * @deprecated
	 * @return string|false
	 */
	static public function getCurrentDynamicId()
	{
		return FrameStatic::getCurrentDynamicId();
	}

	/**
	 * Adds dynamic data to be sent to the client.
	 *
	 * @deprecated
	 * @param string $id Unique identifier of the block.
	 * @param string $content Dynamic part html.
	 * @param string $stub Html to use as stub.
	 * @param string $containerId Identifier of the html container.
	 * @param boolean $useBrowserStorage Use browser storage for caching or not.
	 * @param boolean $autoUpdate Automatically or manually update block contents.
	 * @param boolean $useAnimation Animation flag.
	 *
	 * @return void
	 */
	static public function addDynamicData($id, $content, $stub = "", $containerId = null, $useBrowserStorage = false, $autoUpdate = true, $useAnimation = false)
	{
		$area = new FrameStatic($id);
		$area->setStub($stub);
		$area->setContainerId($containerId);
		$area->setBrowserStorage($useBrowserStorage);
		$area->setAutoUpdate($autoUpdate);
		$area->setAnimation($useAnimation);
		FrameStatic::addDynamicArea($area);
	}

	/**
	 * Marks start of a dynamic block.
	 *
	 * @deprecated
	 * @param integer $id Unique identifier of the block.
	 *
	 * @return boolean
	 */
	static public function startDynamicWithID($id)
	{
		$dynamicArea = new FrameStatic($id);
		return $dynamicArea->startDynamicArea();
	}

	/**
	 * Marks end of the dynamic block if it's the current dynamic block
	 * and its start was being marked early.
	 *
	 * @deprecated
	 * @param string $id Unique identifier of the block.
	 * @param string $stub Html to use as stub.
	 * @param string $containerId Identifier of the html container.
	 * @param boolean $useBrowserStorage Use browser storage for caching or not.
	 * @param boolean $autoUpdate Automatically or manually update block contents.
	 * @param boolean $useAnimation Animation flag.
	 *
	 * @return boolean
	 */
	static public function finishDynamicWithID($id, $stub = "", $containerId = null, $useBrowserStorage = false, $autoUpdate = true, $useAnimation = false)
	{
		$curDynamicArea = FrameStatic::getCurrentDynamicArea();
		if ($curDynamicArea === null || $curDynamicArea->getId() !== $id)
		{
			return false;
		}

		$curDynamicArea->setStub($stub);
		$curDynamicArea->setContainerId($containerId);
		$curDynamicArea->setBrowserStorage($useBrowserStorage);
		$curDynamicArea->setAutoUpdate($autoUpdate);
		$curDynamicArea->setAnimation($useAnimation);

		return $curDynamicArea->finishDynamicArea();
	}
}
