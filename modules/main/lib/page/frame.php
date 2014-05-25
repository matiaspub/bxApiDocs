<?php
namespace Bitrix\Main\Page;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

final class Frame
{
	private static $instance;
	private static $isEnabled = false;
	private static $isAjaxRequest = null;
	private static $useHTMLCache = false;
	private static $onBeforeHandleKey = false;
	private static $onHandleKey = false;
	private static $onRestartBufferHandleKey = false;
	private static $onPrologHandleKey = false;
	private $dynamicIDs = array();
	private $dynamicData = array();
	private $containers = array();
	private $curDynamicId = false;
	private $injectedJS = false;

	public $arDynamicData = array();

	private function __construct()
	{
		//use self::getInstance()
	}

	private function __clone()
	{
		//you can't clone it
	}

	/**
	 * @return Frame
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
	 * Gets ids of the dynamic blocks
	 * @return array
	 */
	public function getDynamicIDs()
	{
		return array_keys($this->dynamicIDs);
	}

	/**
	 * Adds dynamic data to be sent to the client.
	 *
	 * @param string $ID
	 * @param string $content
	 * @param string $stub
	 * @param string $containerId
	 * @param bool $useBrowserStorage
	 * @param bool $autoUpdate
	 * @param bool $useAnimation
	 */
	public function addDynamicData($ID, $content, $stub = "", $containerId = null, $useBrowserStorage = false, $autoUpdate = true, $useAnimation = false)
	{
		$this->dynamicIDs[$ID] = array(
			"stub" => $stub,
			"use_browser_storage" => $useBrowserStorage,
			"auto_update" => $autoUpdate,
			"use_animation" => $useAnimation,
		);
		$this->dynamicData[$ID] = $content;
		if ($containerId !== null)
			$this->containers[$ID] = $containerId;
	}

	/**
	 * Sets isEnable property value and attaches needed handlers
	 *
	 * @param bool $isEnabled
	 */
	public static function setEnable($isEnabled = true)
	{
		if ($isEnabled && !self::$isEnabled)
		{
			self::$onBeforeHandleKey = AddEventHandler("main", "OnBeforeEndBufferContent", array(self::getInstance(), "OnBeforeEndBufferContent"));
			self::$onHandleKey = AddEventHandler("main", "OnEndBufferContent", array(self::getInstance(), "OnEndBufferContent"));
			self::$onRestartBufferHandleKey = AddEventHandler("main", "OnBeforeRestartBuffer", array(self::getInstance(), "OnBeforeRestartBuffer"));
			self::$isEnabled = true;
			\CJSCore::init(array("fc"), false);
		}
		elseif (!$isEnabled && self::$isEnabled)
		{
			if (self::$onBeforeHandleKey >= 0)
			{
				RemoveEventHandler("main", "OnBeforeEndBufferContent", self::$onBeforeHandleKey);
			}

			if (self::$onBeforeHandleKey >= 0)
			{
				RemoveEventHandler("main", "OnEndBufferContent", self::$onHandleKey);
			}

			if (self::$onRestartBufferHandleKey >= 0)
			{
				RemoveEventHandler("main", "OnBeforeRestartBuffer", self::$onRestartBufferHandleKey);
			}

			self::$isEnabled = false;
		}
	}

	/**
	 * Marks start of a dynamic block
	 *
	 * @param $ID
	 *
	 * @return bool
	 */
	public function startDynamicWithID($ID)
	{
		if (!self::$isEnabled
			|| isset($this->dynamicIDs[$ID])
			|| $ID == $this->curDynamicId
			|| ($this->curDynamicId && !isset($this->dynamicIDs[$this->curDynamicId]))
		)
		{
			return false;
		}

		echo '<!--\'start_frame_cache_'.$ID.'\'-->';

		$this->curDynamicId = $ID;

		return true;
	}

	/**
	 * Marks end of the dynamic block if it's the current dynamic block
	 * and its start was being marked early.
	 *
	 * @param string $ID
	 * @param string $stub
	 * @param string $containerId
	 * @param bool $useBrowserStorage
	 * @param bool $autoUpdate
	 * @param bool $useAnimation
	 *
	 * @return bool
	 */
	public function finishDynamicWithID($ID, $stub = "", $containerId = null, $useBrowserStorage = false, $autoUpdate = true, $useAnimation = false)
	{
		if (!self::$isEnabled || $this->curDynamicId !== $ID)
		{
			return false;
		}

		echo '<!--\'end_frame_cache_'.$ID.'\'-->';

		$this->curDynamicId = false;
		$this->dynamicIDs[$ID] = array(
			"stub" => $stub,
			"use_browser_storage" => $useBrowserStorage,
			"auto_update" => $autoUpdate,
			"use_animation" => $useAnimation,
		);
		if ($containerId !== null)
			$this->containers[$ID] = $containerId;

		return true;
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
	 * @param $content
	 *
	 * @return array
	 */
	public function getDividedPageData($content)
	{
		$data = array(
			"dynamic" => array(),
			"static"  => $content,
			"md5"     => "",
		);

		if ($this->dynamicIDs) //Do we have any dynamic blocks?
		{
			$dynamicKeys = implode('|', array_keys($this->dynamicIDs));
			$match = array();
			$regexp = '/<!--\'start_frame_cache_('.$dynamicKeys.')\'-->(.+?)<!--\'end_frame_cache_(?:'.$dynamicKeys.')\'-->/is';
			if (preg_match_all($regexp, $content, $match))
			{
				/*
					Notes:
					$match[0] -	an array of dynamic blocks with macros'
					$match[1] - ids of dynamic blocks
					$match[2] - array of dynamic blocks
				*/
				$replacedArray = array();
				$replacedEmpty = array();
				foreach ($match[1] as $i => $id)
				{
					$data["dynamic"][] = $this->arDynamicData[] = array(
						"ID" => isset($this->containers[$id]) ? $this->containers[$id] : "bxdynamic_".$id,
						"CONTENT" => isset($this->dynamicData[$id]) ? $this->dynamicData[$id] : $match[2][$i],
						"HASH" => md5(isset($this->dynamicData[$id]) ? $this->dynamicData[$id] : $match[2][$i]),
						"PROPS"=> array(
							"USE_BROWSER_STORAGE" => $this->dynamicIDs[$id]["use_browser_storage"],
							"AUTO_UPDATE" => $this->dynamicIDs[$id]["auto_update"],
							"USE_ANIMATION" => $this->dynamicIDs[$id]["use_animation"]
						)
					);

					if (isset($this->containers[$id]))
					{
						$replacedArray[] = $this->dynamicIDs[$id]["stub"];
						$replacedEmpty[] = '';
					}
					else
					{
						$replacedArray[] = '<div id="bxdynamic_'.$id.'">'.$this->dynamicIDs[$id]["stub"].'</div>';
						$replacedEmpty[] = '<div id="bxdynamic_'.$id.'"></div>';
					}
				}

				$data["static"] = str_replace($match[0], $replacedArray, $content);
				$pureContent = str_replace($match[0], $replacedEmpty, $content);
			}
			else
			{
				$pureContent = $content;
			}
		}
		else
		{
			$pureContent = $content;
		}

		$data["md5"] = md5($pureContent);

		return $data;
	}

	/**
	 * This is a handler of "BeforeProlog" event
	 * Use it to switch on feature of static caching.
	 */
	public static function onBeforeProlog()
	{
		self::getInstance()->setEnable();
	}

	/**
	 * OnBeforeEndBufferContent handler
	 */
	public function onBeforeEndBufferContent()
	{
		global $APPLICATION;
		$frame = self::getInstance();
		$params = array();

		if ($frame->getUseAppCache())
		{
			$manifest = \Bitrix\Main\Data\AppCacheManifest::getInstance();
			$params = $manifest->OnBeforeEndBufferContent();
			$params["CACHE_MODE"] = "APPCACHE";
			$params["PAGE_URL"] = \Bitrix\Main\Context::getCurrent()->getServer()->getRequestUri();
		}
		elseif ($frame->getUseHTMLCache())
		{
			$staticHTMLCache = \Bitrix\Main\Data\StaticHtmlCache::getInstance();

			if ($staticHTMLCache->isCacheable())
			{
				$params["CACHE_MODE"] = "HTMLCACHE";

				if (\Bitrix\Main\Config\Option::get("main", "~show_composite_banner", "Y") == "Y")
				{
					$options = \CHTMLPagesCache::GetOptions();
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
		foreach ($frame->dynamicIDs as $id => $dynamicData)
		{
			if ($dynamicData["use_browser_storage"])
			{
				$realId = isset($this->containers[$id]) ? $this->containers[$id] : "bxdynamic_".$id;
				$params["storageBlocks"][] = $realId;
			}
		}

		$frame->injectedJS = $frame->getInjectedJs($params);
		$APPLICATION->AddHeadString($this->injectedJS["start"], false, "BEFORE_CSS");

		//When dynamic hit we'll throw spread cookies away
		if ($frame->getUseHTMLCache() && $staticHTMLCache->isCacheable())
		{
			$APPLICATION->GetSpreadCookieHTML();
			\CJSCore::GetCoreMessagesScript();
		}
	}

	/**
	 * OnEndBufferContent handler
	 * There are two variants of content's modification in this method.
	 * The first one:
	 * If it's ajax-hit the content will be replaced by json data with dynamic blocks,
	 * javascript files and etc. - dynamic part
	 *
	 * The second one:
	 * If it's simple hit the content will be modified also,
	 * all dynamic blocks will be cutted out of the content - static part.
	 *
	 * @param $content
	 */
	static public function onEndBufferContent(&$content)
	{
		global $APPLICATION;
		global $USER;

		$dividedData = self::getInstance()->getDividedPageData($content);
		$htmlCacheChanged = false;

		if (self::getUseHTMLCache())
		{
			$staticHTMLCache = \Bitrix\Main\Data\StaticHtmlCache::getInstance();
			if ($staticHTMLCache->isCacheable())
			{
				if (
					!$staticHTMLCache->isExists()
					|| $staticHTMLCache->getSubstring(-35, 32) !== $dividedData["md5"]
				)
				{
					$staticHTMLCache->delete();
					$staticHTMLCache->write($dividedData["static"]."<!--".$dividedData["md5"]."-->");
				}

				$frame = self::getInstance();

				$ids = $frame->getDynamicIDs();
				foreach ($ids as $i => $id)
				{
					if (isset($frame->containers[$id]))
						unset($ids[$i]);
				}

				$dividedData["static"] = preg_replace(
					array(
						'/<!--\'start_frame_cache_('.implode("|", $ids).')\'-->/',
						'/<!--\'end_frame_cache_('.implode("|", $ids).')\'-->/',
					),
					array(
						'<div id="bxdynamic_\1">',
						'</div>',
					),
					$content
				);

				if ($frame->injectedJS)
				{
					if (isset($frame->injectedJS["start"]))
						$dividedData["static"] = str_replace($frame->injectedJS["start"], "", $dividedData["static"]);
				}

			}
			elseif (!$staticHTMLCache->isCacheable())
			{
				$staticHTMLCache->delete();
				return;
			}
		}

		if (self::getUseAppCache() == true) //Do we use html5 application cache?
		{
			\Bitrix\Main\Data\AppCacheManifest::getInstance()->generate($dividedData["static"]);
		}
		else
		{
			\Bitrix\Main\Data\AppCacheManifest::checkObsoleteManifest();
		}

<<<<<<< HEAD
		if (self::isAjaxRequest()) //Is it a check request?
		{
			header("Content-Type: application/x-javascript; charset=".SITE_CHARSET);
			$content = array(
				"js"                => $APPLICATION->arHeadScripts,
				"additional_js"     => $APPLICATION->arAdditionalJS,
				"lang"              => \CJSCore::GetCoreMessages(),
				"css"               => $APPLICATION->GetCSSArray(),
				"htmlCacheChanged"  => $htmlCacheChanged,
=======
			header("Content-Type: application/x-javascript");
			$autoTimeZone = "N";
			if (is_object($GLOBALS["USER"]))
				$autoTimeZone = trim($USER->GetParam("AUTO_TIME_ZONE"));
			$content = array(
				"js"=> $APPLICATION->arHeadScripts,
				"additional_js"=> $APPLICATION->arAdditionalJS,
				"lang"=>  array(
						'LANGUAGE_ID' => LANGUAGE_ID,
						'FORMAT_DATE' => FORMAT_DATE,
						'FORMAT_DATETIME' => FORMAT_DATETIME,
						'COOKIE_PREFIX' => \COption::GetOptionString("main", "cookie_name", "BITRIX_SM"),
						'USER_ID' => $USER->GetID(),
						'SERVER_TIME' => time(),
						'SERVER_TZ_OFFSET' => date("Z"),
						'USER_TZ_OFFSET' => \CTimeZone::GetOffset(),
						'USER_TZ_AUTO' => $autoTimeZone == 'N' ? 'N' : 'Y',
						'bitrix_sessid' => bitrix_sessid(),
					),
				"css"=> $APPLICATION->GetCSSArray(),
>>>>>>> FETCH_HEAD
				"isManifestUpdated" => \Bitrix\Main\Data\AppCacheManifest::getInstance()->getIsModified(),
				"dynamicBlocks"     => $dividedData["dynamic"],
				"spread"            => array_map(array("CUtil", "JSEscape"), $APPLICATION->GetSpreadCookieUrls()),
			);

			$content = \CUtil::PhpToJSObject($content);
		}
		else
		{
			$content = $dividedData["static"];
		}
	}

	/**
	 * OnBeforeRestartBuffer handler
	 */
	public function OnBeforeRestartBuffer()
	{
		$this->setEnable(false);
		if (defined("BX_COMPOSITE_DEBUG"))
		{
			AddMessage2Log(
				"RestartBuffer method was invoked\n".
				"Request URI: ".$_SERVER["REQUEST_URI"]."\n".
				"Script: ".(isset($_SERVER["REAL_FILE_PATH"]) ? $_SERVER["REAL_FILE_PATH"] : $_SERVER["SCRIPT_NAME"]),
				"composite"
			);
		}
	}
	/**
	 * Sets useAppCache property
	 *
	 * @param bool $useAppCache
	 */
	static public function setUseAppCache($useAppCache = true)
	{
		if (self::getUseAppCache())
			self::getInstance()->setUseHTMLCache(false);
		$appCache = \Bitrix\Main\Data\AppCacheManifest::getInstance();
		$appCache->setEnabled($useAppCache);
	}

	/**
	 * Gets useAppCache property
	 * @return bool
	 */
	static public function getUseAppCache()
	{
		$appCache = \Bitrix\Main\Data\AppCacheManifest::getInstance();
		return $appCache->isEnabled();
	}

	/**
	 * @return boolean
	 */
	public static function getUseHTMLCache()
	{
		return self::$useHTMLCache;
	}

	/**
	 * @param boolean $useHTMLCache
	 */
	public static function setUseHTMLCache($useHTMLCache = true)
	{
		self::$useHTMLCache = $useHTMLCache;
		self::$onPrologHandleKey = AddEventHandler("main", "onBeforeProlog", array(__CLASS__, "onBeforeProlog"));
	}

	public static function isAjaxRequest()
	{
		if (self::$isAjaxRequest == null)
		{
			$actionType = \Bitrix\Main\Context::getCurrent()->getServer()->get("HTTP_BX_ACTION_TYPE");
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

	protected function getInjectedJs($params = array())
	{
		$vars = \CUtil::PhpToJSObject($params);
		$inlineJS = <<<JS
			(function(w) {

			var v = w.frameCacheVars = $vars;
			var r = w.XMLHttpRequest ? new XMLHttpRequest() : (w.ActiveXObject ? new w.ActiveXObject("Microsoft.XMLHTTP") : null);
			if (!r) { return; }

			w.frameRequestStart = true;
			var m = v.CACHE_MODE; var p = v.PAGE_URL ? v.PAGE_URL : w.location.href;
			var i = p.indexOf("#"); if (i > 0) { p = p.substring(0, i); }
			var u = p + (p.indexOf('?') >= 0 ? '&' : '?') + 'bxrand=' + new Date().getTime();
			r.open("GET", u, true);
			r.setRequestHeader("BX-ACTION-TYPE", "get_dynamic");
			r.setRequestHeader("BX-REF", document.referrer);

			if (p && p.length > 0)
			{
				if (window.JSON) { r.setRequestHeader("BX-APPCACHE-PARAMS", JSON.stringify(v.PARAMS)); }
				r.setRequestHeader("BX-APPCACHE-URL", p);
				r.setRequestHeader("BX-CACHE-MODE", m);
			}

			r.onreadystatechange = function() {
				if (r.readyState != 4) { return; }
				var b = w.BX && w.BX.frameCache ? w.BX.frameCache : false;
				if (!((r.status >= 200 && r.status < 300) || r.status === 304 || r.status === 1223 || r.status === 0))
				{
					if (w.BX)
					{
						BX.ready(function() { BX.onCustomEvent("onFrameDataRequestFail"); });
					}
					else
					{
						w.frameRequestFail = false;
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
					w.frameUpdateInvoked  = true;
				}
				else
				{
					w.frameDataString = r.responseText;
				}
			};
			r.send();

			})(window);
JS;

		return array(
			"start" => "<style>".str_replace(array("\n", "\t"), "", self::getInjectedCSS())."</style>\n".
						"<script>".str_replace(array("\n", "\t"), "", $inlineJS)."</script>"
		);
	}

	public static function getInjectedCSS()
	{
		return <<<CSS

			.bx-composite-btn {
				background: url(/bitrix/images/main/composite/bx-white-logo.png) no-repeat right 5px #e94524;
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

			.bx-composite-btn-fixed {
				position: absolute;
				top: -45px;
				right: 15px;
				z-index: 10;
			}

			.bx-btn-white {
				background-image: url(/bitrix/images/main/composite/bx-white-logo.png);
				color: #fff !important;
			}

			.bx-btn-black {
				background-image: url(/bitrix/images/main/composite/bx-black-logo.png);
				color: #000 !important;
			}

			.bx-btn-grey {
				background-image: url(/bitrix/images/main/composite/bx-grey-logo.png);
				color: #657b89 !important;
			}

			.bx-btn-red {
				background-image: url(/bitrix/images/main/composite/bx-red-logo.png);
				color: #555 !important;
			}

			.bx-btn-border {
				border: 1px solid #d4d4d4;
				background-position: right 5px;
				height: 29px !important;
				line-height: 29px !important;
			}
CSS;
	}

}
