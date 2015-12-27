<?php

namespace Bitrix\MobileApp;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\IO\File;
use Bitrix\Main\Text\Encoding;

class Mobile
{
	protected static $instance;
	protected static $isAlreadyInit = false;
	private $pixelRatio = 1.0;
	private $minScale = false;
	private $iniScale = false;
	private $maxScale = false;
	private $scale = 1.2;
	private $width = false;
	private $userScalable = "no";

	private $deviceWidth = 320;
	private $deviceHeight = 480;
	private $screenCategory = "NORMAL";
	private $device = "";

	private $largeScreenSupport = true;
	private $isWebRtcSupported = false;
	private $isBXScriptSupported = false;
	public static $platform = "ios";
	public static $apiVersion = 1;
	public static $pgVersion = "2.0.0";
	public static $supportedCordovaVersion = "3.6.3";
	public static $isDev = false;
	private static $remoteScriptPath = "http://dev.1c-bitrix.ru/mobile_scripts/";

	private function __construct()
	{
		global $APPLICATION;

		$this->setDeviceWidth($_COOKIE["MOBILE_RESOLUTION_WIDTH"]);
		$this->setDeviceHeight($_COOKIE["MOBILE_RESOLUTION_HEIGHT"]);
		$this->setScreenCategory($_COOKIE["MOBILE_SCREEN_CATEGORY"]);
		$this->setPixelratio($_COOKIE["MOBILE_SCALE"]);
		$this->setPgVersion($_COOKIE["PG_VERSION"]);

		self::$isDev = (isset($_COOKIE["MOBILE_DEV"]) && $_COOKIE["MOBILE_DEV"] == "Y");

		$this->setDevice($_COOKIE["MOBILE_DEVICE"]);
		if($_COOKIE["IS_WEBRTC_SUPPORTED"] && $_COOKIE["IS_WEBRTC_SUPPORTED"] == "Y")
			$this->setWebRtcSupport(true);
		if ($_COOKIE["IS_BXSCRIPT_SUPPORTED"] && $_COOKIE["IS_BXSCRIPT_SUPPORTED"] == "Y")
			$this->setBXScriptSupported(true);

		if ($this->getDevice() == "iPad")
		{
			$this->setScreenCategory("LARGE");
			if (intval($this->getPixelRatio()) == 2) //retina hack
			{
				$this->setDeviceWidth($_COOKIE["MOBILE_RESOLUTION_WIDTH"] / 2);
				$this->setDeviceHeight($_COOKIE["MOBILE_RESOLUTION_HEIGHT"] / 2);
			}
		}

		//detecting OS
		if (array_key_exists("MOBILE_DEVICE", $_COOKIE))
		{
			$deviceDetectSource = $_COOKIE["MOBILE_DEVICE"];
		}
		else
		{
			$deviceDetectSource = strtolower(Context::getCurrent()->getServer()->get("HTTP_USER_AGENT"));
		}

		if (strrpos(ToUpper($deviceDetectSource), "IPHONE") > 0 || strrpos(ToUpper($deviceDetectSource), "IPAD") > 0)
		{
			self::$platform = "ios";
		}
		elseif (strrpos(ToUpper($deviceDetectSource), "ANDROID") > 0 || strrpos(ToUpper($deviceDetectSource), "ANDROID") === 0)
		{
			self::$platform = "android";
		}

		if(array_key_exists("emulate_platform", $_REQUEST))
		{
			self::$platform = $_REQUEST["emulate_platform"];
		}


		if (array_key_exists("MOBILE_API_VERSION", $_COOKIE))
		{
			self::$apiVersion = intval($_COOKIE["MOBILE_API_VERSION"]);
		}
		elseif ($APPLICATION->get_cookie("MOBILE_APP_VERSION"))
		{
			self::$apiVersion = $APPLICATION->get_cookie("MOBILE_APP_VERSION");
		}
		elseif(array_key_exists("api_version", $_REQUEST))
		{
			self::$apiVersion = intval($_REQUEST["api_version"]);
		}

	}

	/**
	 * @return boolean
	 */
	public function isWebRtcSupported()
	{
		return $this->isWebRtcSupported;
	}

	/**
	 * @param boolean $isWebRtcSupported
	 */
	public function setWebRtcSupport($isWebRtcSupported)
	{
		$this->isWebRtcSupported = $isWebRtcSupported;
	}

	/**
	 * @param boolean $isBXScriptSupported
	 */
	public function setBXScriptSupported($isBXScriptSupported)
	{
		$this->isBXScriptSupported = $isBXScriptSupported;
	}

	/**
	 * @return boolean
	 */
	public function getBXScriptSupported()
	{
		return $this->isBXScriptSupported;
	}

	/**
	 * @return integer
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * @param integer $width
	 */
	public function setWidth($width)
	{
		$this->width = $width;
	}

	/**
	 * @return string
	 */
	public function getUserScalable()
	{
		return $this->userScalable;
	}

	/**
	 * @param boolean $userScalable
	 */
	public function setUserScalable($userScalable)
	{
		$this->userScalable = ($userScalable === false?"no":"yes");
	}

	private function __clone()
	{
		//you can't clone it
	}

	/**
	 * @return \Bitrix\MobileApp\Mobile
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new Mobile();
		}

		return self::$instance;
	}

	public static function Init()
	{
		self::getInstance()->_Init();
	}

	/**
	 * Sets viewport-metadata
	 */
	public static function initScripts()
	{
		global $APPLICATION;

		\CJSCore::Init();
		$APPLICATION->AddHeadString("<script type=\"text/javascript\">var mobileSiteDir=\"" . SITE_DIR . "\"; var appVersion = " . self::$apiVersion . ";var platform = \"" . self::$platform . "\";</script>", false, true);
		if(self::$platform == "android")
		{
			/**
			 * This is workaround for android
			 * We use console.log() to tell the application about successful loading of this page
			 */
			$APPLICATION->AddHeadString("<script type=\"text/javascript\">console.log(\"bxdata://success\")</script>", false, true);
		}
		if(self::getInstance()->getBXScriptSupported())
		{
			/**
			 * If the application tells us bxscript-feature is available
			 * it means that device can load cordova-scripts (including plugins) itself.
			 */
			$pgJsFile = "/bitrix/js/mobileapp/__deviceload__/cordova.js";
			$APPLICATION->AddHeadString("<script type=\"text/javascript\" src=\"" . $pgJsFile . "\"></script>", false, true);

		}
		else
		{
			$pgJsFile = "/bitrix/js/mobileapp/" . self::$platform . "-cordova-" . self::$pgVersion . ".js";
			if (!File::isFileExists(Application::getDocumentRoot() . $pgJsFile))
			{
				$pgJsFile = self::$remoteScriptPath . self::$platform . "-cordova-" . self::$pgVersion . ".js";
			}

			$APPLICATION->AddHeadString("<script type=\"text/javascript\" src=\"" . \CUtil::GetAdditionalFileURL($pgJsFile) . "\"></script>", false, true);
		}

		$APPLICATION->AddHeadString("<script type=\"text/javascript\" src=\"" . \CUtil::GetAdditionalFileURL("/bitrix/js/mobileapp/bitrix_mobile.js") . "\"></script>", false, true);
		$APPLICATION->AddHeadString("<script type=\"text/javascript\" src=\"" . \CUtil::GetAdditionalFileURL("/bitrix/js/mobileapp/mobile_lib.js") . "\"></script>", false, true);


		if (self::$platform == "android")
		{
			$APPLICATION->AddHeadString("<script type=\"text/javascript\">app.bindloadPageBlank();</script>", false, false);
		}

		$APPLICATION->AddHeadString(Mobile::getInstance()->getViewPort());
	}

	/**
	 * Converts string from site charset in utf-8 and returns it
	 *
	 * @param string $s
	 *
	 * @return string
	 */
	public static function PrepareStrToJson($s = '')
	{
		return (Application::isUtfMode() ? $s : Encoding::convertEncoding($s, SITE_CHARSET, 'UTF-8'));
	}

	/**
	 * Converts string from utf-8 in site charset and returns it
	 *
	 * @param string $s
	 *
	 * @return string
	 */
	public static function ConvertFromUtf($s = '')
	{
		return (defined("BX_UTF") ? $s : Encoding::convertEncoding($s, 'UTF-8', SITE_CHARSET));
	}

	/**
	 *  detects mobile platform and attaches all needed javascript files
	 */
	protected function _Init()
	{
		if (self::$isAlreadyInit)
		{
			return;
		}

		header("BX-Cordova-Version: " . self::$supportedCordovaVersion);
		$GLOBALS["BITRIX_PLATFORM"] = self::$platform;
		$GLOBALS["BITRIX_API_VERSION"] = self::$apiVersion;

		AddEventHandler("main", "OnBeforeEndBufferContent", Array(__CLASS__, "initScripts"));
		AddEventHandler("main", "OnEpilog", Array($this, "onMobileInit"));
		self::$isAlreadyInit = true;
	}


	static public function onMobileInit()
	{
		if(!defined("MOBILE_INIT_EVENT_SKIP"))
		{
			$db_events = getModuleEvents("mobileapp", "OnMobileInit");
			while ($arEvent = $db_events->Fetch())
				ExecuteModuleEventEx($arEvent);
		}
	}

	/**
	 * Gets target dpi for a viewport meta data
	 *
	 * @return string
	 */
	public function getTargetDpi()
	{
		$targetDpi = "medium-dpi";
		if ($this->getDevice() == "iPad")
		{
			return $targetDpi;
		}
		switch ($this->getScreenCategory())
		{
			case 'NORMAL':
				$targetDpi = "medium-dpi";
				break;
			case 'LARGE':
				$targetDpi = "low-dpi";
				break;
			case 'XLARGE':
				$targetDpi = "low-dpi";
				break;
			case 'SMALL':
				$targetDpi = "medium-dpi";
				break;
			default:
				$targetDpi = "medium-dpi";
				break;
		}

		return $targetDpi;
	}

	/**
	 * Use it to get current value of "viewport"-metadata
	 *
	 * @param string $width
	 *
	 * @return mixed|string
	 */
	public function getViewPort($width = "")
	{

		if ($width == "")
		{
			$width = $this->getDevicewidth();
		}

		if ($this->largeScreenSupport == true)
		{
			//we need to change densitydpi for large screens
			if ($this->getDevice() == "iPad")
			{
				//ipad large screen setting
				return $this->getIPadViewPort();

			}
			elseif (($this->getScreenCategory() == "LARGE" || $this->getScreenCategory() == "XLARGE"))
			{
				//android large screen setting
				return $this->getLargeScreenViewPort();
			}
		}

		$viewPortMeta = "<meta id=\"bx_mobile_viewport\" name=\"viewport\" content=\"#content_value#\">";
		if ($this->getIniscale())
		{
			$contentAttributes[] = "initial-scale=" . $this->getIniscale();
		}
		if ($this->getMaxscale())
		{
			$contentAttributes[] = "maximum-scale=" . $this->getMaxscale();
		}
		if ($this->getMinscale())
		{
			$contentAttributes[] = "minimum-scale=" . $this->getMinscale();
		}



		if($this->getWidth())
		{
			$contentAttributes[] = "width=" . $this->getWidth();
		}
		elseif ($this->getIniscale())
		{
			$contentAttributes[] = "width=" . ($width / $this->getIniscale());
		}


		if (toUpper($this->getPlatform()) == "ANDROID")
		{
			if (!$this->getWidth())
				$contentAttributes[] = "width=device-width";
			$contentAttributes[] = "target-densitydpi=" . $this->getTargetDpi();
		}


		$contentAttributes[] = "user-scalable=".$this->getUserScalable();

		return str_replace("#content_value#", implode(", ", $contentAttributes), $viewPortMeta);
	}

	/**
	 * Use it to get value of viewport-metadata for large screen of android based device.
	 */
	public function getLargeScreenViewPort()
	{
		return "<meta id=\"bx_mobile_viewport\" name=\"viewport\" content=\"user-scalable=no width=device-width target-densitydpi=" . $this->getTargetDpi() . "\">";
	}

	/**
	 * Use it to get value of viewport-metadata for iPad.
	 *
	 * @param int $width
	 *
	 * @return mixed
	 */
	public function getIPadViewPort($width = 320)
	{
		if ($width == false)
		{
			$width = $this->getDevicewidth();
		}
		$viewPortMeta = "<meta id=\"bx_mobile_viewport\" name=\"viewport\" content=\"#content_value#\">";
		$contentAttributes = Array(
			"initial-scale=" . $this->scale,
			"maximum-scale=" . $this->scale,
			"minimum-scale=" . $this->scale,
			"width=" . ($width / $this->scale),
			"user-scalable=no"
		);
		$content = implode(", ", $contentAttributes);

		return str_replace("#content_value#", $content, $viewPortMeta);
	}

	/**
	 * Use it to get value of viewport-metadata for portrait orientation.
	 *
	 * @return string
	 */
	public function getViewPortPortrait()
	{
		return $this->getViewPort($this->deviceWidth);
	}

	/**
	 * Use it to get value of viewport-metadata for landscape orientation.
	 *
	 * @return string
	 */
	public function getViewPortLandscape()
	{
		return $this->getViewPort($this->deviceHeight);
	}

	/**
	 * Sets the value of pixelRatio.
	 *
	 * @param mixed $pixelRatio the pixelRatio
	 */
	public function setPixelRatio($pixelRatio)
	{
		$this->pixelRatio = $pixelRatio;
	}

	/**
	 * Sets the value of minScale.
	 *
	 * @param mixed $minScale the minScale
	 */
	public function setMinScale($minScale)
	{
		$this->minScale = $minScale;
	}


	/**
	 * Sets the value of device.
	 *
	 * @param mixed $device the pixelRatio
	 */
	public function setDevice($device)
	{
		$this->device = $device;
	}

	/**
	 * Sets the value of iniScale.
	 *
	 * @param mixed $iniScale the iniScale
	 */
	public function setIniScale($iniScale)
	{
		$this->iniScale = $iniScale;
	}

	/**
	 * Sets the value of maxScale.
	 *
	 * @param mixed $maxScale the maxScale
	 */
	public function setMaxScale($maxScale)
	{
		$this->maxScale = $maxScale;
	}

	/**
	 * Sets the value of deviceWidth.
	 *
	 * @param mixed $deviceWidth the deviceWidth
	 */
	public function setDeviceWidth($deviceWidth)
	{
		$this->deviceWidth = $deviceWidth;
	}

	/**
	 * Sets the value of deviceHeight.
	 *
	 * @param mixed $deviceHeight the deviceHeight
	 */
	public function setDeviceHeight($deviceHeight)
	{
		$this->deviceHeight = $deviceHeight;
	}

	/**
	 * Gets the value of pixelRatio.
	 *
	 * @return mixed
	 */
	public function getPixelRatio()
	{
		return $this->pixelRatio;
	}

	/**
	 * Gets the value of minScale.
	 *
	 * @return mixed
	 */
	public function getMinScale()
	{
		return $this->minScale;
	}

	/**
	 * Gets the value of iniScale.
	 *
	 * @return mixed
	 */
	public function getIniScale()
	{
		return $this->iniScale;
	}

	/**
	 * Gets the value of maxScale.
	 *
	 * @return mixed
	 */
	public function getMaxScale()
	{
		return $this->maxScale;
	}

	/**
	 * Gets the value of deviceWidth.
	 *
	 * @return mixed
	 */
	public function getDeviceWidth()
	{
		return $this->deviceWidth;
	}

	/**
	 * Gets the value of deviceHeight.
	 *
	 * @return mixed
	 */
	public function getDeviceHeight()
	{
		return $this->deviceHeight;
	}

	public function getDevice()
	{
		return $this->device;
	}

	/**
	 * Gets the value of deviceDpi.
	 *
	 * @return mixed
	 */
	public function getScreenCategory()
	{
		return $this->screenCategory;
	}

	/**
	 * Sets the value of screenCategory.
	 *
	 * @param mixed $screenCategory the screenCategory
	 */
	public function setScreenCategory($screenCategory)
	{
		$this->screenCategory = $screenCategory;
	}

	/**
	 * Sets the value of largeScreenSupport.
	 *
	 * @param mixed $largeScreenSupport the $largeScreenSupport
	 */
	public function setLargeScreenSupport($largeScreenSupport)
	{
		$this->largeScreenSupport = $largeScreenSupport;
	}

	/**
	 * Gets the value of largeScreenSupport.
	 *
	 * @return mixed
	 */
	public function getLargeScreenSupport()
	{
		return $this->largeScreenSupport;
	}

	/**
	 * Gets the value of scale.
	 *
	 * @return mixed
	 */
	public function getScale()
	{
		return $this->scale;
	}

	/**
	 * Sets the value of scale.
	 *
	 * @param $scale
	 */

	public function setScale($scale)
	{
		$this->scale = $scale;
	}

	/**
	 * gets the value of  self::platform
	 * @return string
	 */
	public static function getPlatform()
	{
		return self::$platform;
	}

	/**
	 * sets the value of self::platform
	 *
	 * @param $platform
	 */
	public static function setPlatform($platform)
	{
		self::$platform = $platform;
	}

	/**
	 * Sets the value of self::$apiVersion
	 *
	 * @return int
	 */
	public static function getApiVersion()
	{
		return self::$apiVersion;
	}

	/**
	 * Gets the value of self::$apiVersion
	 *
	 * @param $apiVersion
	 */
	public static function setApiVersion($apiVersion)
	{
		self::$apiVersion = $apiVersion;
	}

	/**
	 * Returns phonegap version
	 * @return string
	 */
	public static function getPgVersion()
	{
		return self::$pgVersion;
	}

	/**
	 * Sets phonegap version
	 * @param $pgVersion
	 */
	public static function setPgVersion($pgVersion)
	{
		if ($pgVersion)
		{
			self::$pgVersion = $pgVersion;
		}
	}

	/**
	 *  Returns true if device has a large screen
	 * @return bool
	 */
	public function isLarge()
	{
		return ($this->getScreenCategory() == "LARGE" || $this->getScreenCategory() == "XLARGE");
	}
}

?>