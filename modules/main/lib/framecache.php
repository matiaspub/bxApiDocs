<?php
namespace Bitrix\Main;

class FrameCache
{
	private static $instance;
	private static $isEnable = false;
	private static $isBackgroundRequest = false;
	private static $useAppCache = false;
	private static $onBeforeHandleKey = false;
	private static $onHandleKey = false;
	private $arDynamicIDs = array();
	private $curDynamicId = false;

	public $arDynamicData = Array();

	private function __construct()
	{
		//use self::getInstance()
	}
	private function __clone()
	{
		//you can't clone it
	}
	public static function getInstance()
	{
		if (is_null(self::$instance))
			self::$instance = new  FrameCache();

		return self::$instance;
	}

	/**
	 * Gets ids of the dynamic blocks
	 * @return array
	 */
	public function getDynamicIDs()
	{
		return $this->arDynamicIDs;
	}

	/**
	 * Sets isEnable property value
	 * @param bool $isEnable
	 */
	public static function setEnable($isEnable = true)
	{
		if ($isEnable && !self::$isEnable)
		{
			self::$onBeforeHandleKey = AddEventHandler("main", "OnBeforeEndBufferContent", Array(__CLASS__, "OnBeforeEndBufferContent"));
			self::$onHandleKey = AddEventHandler("main", "OnEndBufferContent", Array(__CLASS__, "OnEndBufferContent"));
			self::$isEnable = true;

			\CJSCore::init(Array("fc"), false);

			$actionType = \Bitrix\Main\Context::getCurrent()->getServer()->get("HTTP_BX_ACTION_TYPE");
			if ($actionType == "get_dynamic")//Is it the background request?
				self::$isBackgroundRequest = true;
		}
		elseif (!$isEnable && self::$isEnable)
		{
			if (self::$onBeforeHandleKey >= 0)
				RemoveEventHandler("main", "OnBeforeEndBufferContent", self::$onBeforeHandleKey);
			if (self::$onBeforeHandleKey >= 0)
				RemoveEventHandler("main", "OnEndBufferContent", self::$onHandleKey);

			self::$isEnable = false;
		}
	}

	/**
	 * Marks start of a dynamic block
	 * @param $ID
	 * @return bool
	 */
	public function startDynamicWithID($ID)
	{
		if (in_array($ID, $this->arDynamicIDs) && $ID == $this->curDynamicId)
			return false;

		self::setEnable();
		$this->arDynamicIDs[] = $ID;
		$this->curDynamicId = $ID;
		echo "##start_frame_cache_" . $ID . "##";

		return true;
	}

	/**
	 * Marks end of the dynamic block if it's the current dynamic block
	 * and its start was being marked early.
	 * @param $ID
	 */
	public function finishDynamicWithID($ID)
	{
		if ($this->curDynamicId !== $ID)
			return;
		$this->curDynamicId = false;
		echo "##end_frame_cache_" . $ID . "##";
	}

	/**
	 * OnBeforeEndBufferContent handler
	 */
	public static function onBeforeEndBufferContent()
	{
		global $APPLICATION;

		$fcache = FrameCache::getInstance();

		$manifest = \Bitrix\Main\Data\AppCacheManifest::getInstance();
		if (self::getUseAppCache() == true)
			$appCacheParams = $manifest->OnBeforeEndBufferContent();
		else
			$appCacheParams = Array();

		if (!self::$isBackgroundRequest)
		{
			$checkParams = array_merge(Array("dynamic" => $fcache->getDynamicIDs()), $appCacheParams);
			$checkScript = "<script>BX.ready(function(){ BX.frameCache.vars = " . json_encode($checkParams) . ";BX.frameCache.update();})</script>";
			$APPLICATION->AddHeadString($checkScript);
		}
	}

	/**
	 * OnEndBufferContent handler
	 * @param $content
	 */
	public static function onEndBufferContent(&$content)
	{

		global $APPLICATION;

		if (self::getUseAppCache() == true) //Do we use html5 application cache?
			\Bitrix\Main\Data\AppCacheManifest::onEndBufferContent($content);
		else
			\Bitrix\Main\Data\AppCacheManifest::checkObsoleteManifest();//it checks if the manifest is still alive.

		$selfObject = self::getInstance();
		$ids = $selfObject->getDynamicIDs();


		if (count($ids) > 0) //Do we have any dynamic blocks?
		{
			$match = array();
			$regexp = "/##start_frame_cache_(" . implode("|", $ids) . ")##(.+?)##end_frame_cache_(?:" . implode("|", $ids) . ")##/is";
			preg_match_all($regexp, $content, $match);

			/*
				Notes:
				$match[0] -	array of dynamic blocks with macros'
				$match[1] - ids of the dynamic blocks
				$match[2] - array of dynamic blocks
			*/

			$count = count($match[1]);
			if (self::$isBackgroundRequest)
			{
				//$fcache->arDynamicData = array_combine($match[1], $match[2]);
				for ($i = 0; $i < $count; $i++)
				{
					$selfObject->arDynamicData[] = Array("ID" => $match[1][$i], "CONTENT" => $match[2][$i], "HASH" => md5($match[2][$i]));
				}
			}
			else
			{
				$replacedArray = array();
				for ($i = 0; $i < $count; $i++)
				{
					$replacedArray[] = '<div id="bxdynamic_' . $match[1][$i] . '"></div>';
				}
				$content = str_replace($match[0], $replacedArray, $content);

			}
		}

		if (self::$isBackgroundRequest) //Is it a check request?
		{
			header("Content-Type: application/x-javascript");
			$content = Array(
				"isManifestUpdated" => \Bitrix\Main\Data\AppCacheManifest::getInstance()->getIsModified(),
				"dynamicBlocks" => $selfObject->arDynamicData,
			);
			if (!Application::getInstance()->isUtfMode())
				//TODO I use it because there is no similar method in the new Bitrix Framework yet
				$content = $APPLICATION->convertCharsetArray($content, SITE_CHARSET, "UTF-8");
			$content = json_encode($content);
		}
	}

	/**
	 * Sets useAppCache property
	 * @param bool $useAppCache
	 */
	static public function setUseAppCache($useAppCache = true)
	{
		self::$useAppCache = $useAppCache;
	}

	/**
	 * Gets useAppCache property
	 * @return bool
	 */
	static public function getUseAppCache()
	{
		return self::$useAppCache;
	}

}