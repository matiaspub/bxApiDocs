<?php
class CMobile
{
	protected static $instance;
	protected static $isAlreadyInit = false;
	private $pixelRatio = 1.0;
	private $minScale = false;
	private $iniScale = false;
	private $maxScale = false;
	private $scale = 1.2;
	private $deviceWidth = 320;
	private $deviceHeight = 480;
	private $screenCategory = "NORMAL";
	private $device = "";
	private $largeScreenSupport = true;
	public static $platform = "ios";
	public static $apiVersion = 1;
	public static $pgVersion = "2.0.0";
	private static $remoteScriptPath = "http://dev.1c-bitrix.ru/mobile_scripts/";

	private function __construct()
	{
		global $APPLICATION;

		$this->setDevicewidth($_COOKIE["MOBILE_RESOLUTION_WIDTH"]);
		$this->setDeviceheight($_COOKIE["MOBILE_RESOLUTION_HEIGHT"]);
		$this->setScreenCategory($_COOKIE["MOBILE_SCREEN_CATEGORY"]);
		$this->setPixelratio($_COOKIE["MOBILE_SCALE"]);
		$this->setPgVersion($_COOKIE["PG_VERSION"]);

		$this->setDevice($_COOKIE["MOBILE_DEVICE"]);

		if ($this->getDevice() == "iPad")
		{
			$this->setScreenCategory("LARGE");
			if (intval($this->getPixelratio()) == 2) //retina hack
			{
				$this->setDevicewidth($_COOKIE["MOBILE_RESOLUTION_WIDTH"] / 2);
				$this->setDeviceheight($_COOKIE["MOBILE_RESOLUTION_HEIGHT"] / 2);
			}
		}


		//detecting OS
		if (array_key_exists("MOBILE_DEVICE", $_COOKIE))
		{
			$deviceDetectSource = $_COOKIE["MOBILE_DEVICE"];
		}
		else
		{
			$deviceDetectSource = strtolower($_SERVER['HTTP_USER_AGENT']);
		}

		if (strrpos(ToUpper($deviceDetectSource), "IPHONE") > 0 || strrpos(ToUpper($deviceDetectSource), "IPAD") > 0)
		{
			self::$platform = "ios";
		}
		else if (strrpos(ToUpper($deviceDetectSource), "ANDROID") > 0 || strrpos(ToUpper($deviceDetectSource), "ANDROID") === 0)
		{
			self::$platform = "android";
		}

		if (array_key_exists("MOBILE_API_VERSION", $_COOKIE))
		{
			self::$apiVersion = intval($_COOKIE["MOBILE_API_VERSION"]);
		}
		elseif ($APPLICATION->get_cookie("MOBILE_APP_VERSION"))
		{
			self::$apiVersion = $APPLICATION->get_cookie("MOBILE_APP_VERSION");
		}

	}

	public static function getPgVersion()
	{
		return self::$pgVersion;
	}

	public static function setPgVersion($pgVersion)
	{
		if($pgVersion)
			self::$pgVersion = $pgVersion;
	}

	private function __clone()
	{
		//you can't clone it
	}

	/**
	 * @return CMobile
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
			self::$instance = new CMobile();

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

		$pgJsFile = "/bitrix/js/mobileapp/" . self::$platform . "-cordova-" . self::$pgVersion . ".js";
		if(!file_exists($_SERVER["DOCUMENT_ROOT"]. $pgJsFile))
			$pgJsFile = self::$remoteScriptPath . self::$platform . "-cordova-" . self::$pgVersion . ".js";
		$APPLICATION->AddHeadString("<script type=\"text/javascript\"> var appVersion = " . self::$apiVersion . ";var platform = \"" . self::$platform . "\";</script>", false, true);
		$APPLICATION->AddHeadString("<script type=\"text/javascript\" src=\"" . CUtil::GetAdditionalFileURL($pgJsFile) . "\"></script>", false, true);

		if ($APPLICATION->IsJSOptimized())
		{
			$APPLICATION->AddHeadScript("/bitrix/js/mobileapp/bitrix_mobile.js");
		}
		else
		{
			$APPLICATION->AddHeadString("<script type=\"text/javascript\" src=\"" . CUtil::GetAdditionalFileURL("/bitrix/js/mobileapp/bitrix_mobile.js") . "\"></script>", false, true);
		}

		if (self::$platform == "android")
		{
			$APPLICATION->AddHeadString("<script type=\"text/javascript\">app.bindloadPageBlank();</script>", false, false);
		}
		$APPLICATION->AddHeadString(CMobile::getInstance()->getViewPort());
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
		return (defined("BX_UTF") ? $s : $GLOBALS['APPLICATION']->ConvertCharset($s, SITE_CHARSET, 'UTF-8'));
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
		return (defined("BX_UTF") ? $s : $GLOBALS['APPLICATION']->ConvertCharset($s, 'UTF-8', SITE_CHARSET));
	}

	/**
	 *  detects mobile platform and attaches all needed javascript files
	 */
	protected function _Init()
	{
		if (self::$isAlreadyInit)
			return;

		$GLOBALS["BITRIX_PLATFORM"] = self::$platform;
		$GLOBALS["BITRIX_API_VERSION"] = self::$apiVersion;

		AddEventHandler("main", "OnBeforeEndBufferContent", Array("CMobile", "initScripts"));
		self::$isAlreadyInit = true;
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
			return $targetDpi;
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
			$contentAttributes[] = "initial-scale=" . $this->getIniscale();
		if ($this->getMaxscale())
			$contentAttributes[] = "maximum-scale=" . $this->getMaxscale();
		if ($this->getMinscale())
			$contentAttributes[] = "minimum-scale=" . $this->getMinscale();
		if ($this->getIniscale())
			$contentAttributes[] = "width=" . ($width / $this->getIniscale());
		$contentAttributes[] = "target-densitydpi=" . $this->getTargetDpi();
		$contentAttributes[] = "user-scalable=0";
		$content = implode(", ", $contentAttributes);

		return str_replace("#content_value#", $content, $viewPortMeta);
	}

	/**
	 * Use it to get value of viewport-metadata for large screen of android based device.
	 */
	public function getLargeScreenViewPort()
	{
		return "<meta id=\"bx_mobile_viewport\" name=\"viewport\" content=\"user-scalable=0 width=device-width target-densitydpi=" . $this->getTargetDpi() . "\">";
	}

	/**
	 * Use it to get value of viewport-metadata for iPad.
	 */
	public function getIPadViewPort($width = 320)
	{
		if ($width == false)
			$width = $this->getDevicewidth();
		$viewPortMeta = "<meta id=\"bx_mobile_viewport\" name=\"viewport\" content=\"#content_value#\">";
		$contentAttributes = Array(
			"initial-scale=" . $this->scale,
			"maximum-scale=" . $this->scale,
			"minimum-scale=" . $this->scale,
			"width=" . ($width / $this->scale),
			"user-scalable=0"
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
	public function setPixelratio($pixelRatio)
	{
		$this->pixelRatio = $pixelRatio;
	}

	/**
	 * Sets the value of minScale.
	 *
	 * @param mixed $minScale the minScale
	 */
	public function setMinscale($minScale)
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
	public function setIniscale($iniScale)
	{
		$this->iniScale = $iniScale;
	}

	/**
	 * Sets the value of maxScale.
	 *
	 * @param mixed $maxScale the maxScale
	 */
	public function setMaxscale($maxScale)
	{
		$this->maxScale = $maxScale;
	}

	/**
	 * Sets the value of deviceWidth.
	 *
	 * @param mixed $deviceWidth the deviceWidth
	 */
	public function setDevicewidth($deviceWidth)
	{
		$this->deviceWidth = $deviceWidth;
	}

	/**
	 * Sets the value of deviceHeight.
	 *
	 * @param mixed $deviceHeight the deviceHeight
	 */
	public function setDeviceheight($deviceHeight)
	{
		$this->deviceHeight = $deviceHeight;
	}

	/**
	 * Gets the value of pixelRatio.
	 *
	 * @return mixed
	 */
	public function getPixelratio()
	{
		return $this->pixelRatio;
	}

	/**
	 * Gets the value of minScale.
	 *
	 * @return mixed
	 */
	public function getMinscale()
	{
		return $this->minScale;
	}

	/**
	 * Gets the value of iniScale.
	 *
	 * @return mixed
	 */
	public function getIniscale()
	{
		return $this->iniScale;
	}

	/**
	 * Gets the value of maxScale.
	 *
	 * @return mixed
	 */
	public function getMaxscale()
	{
		return $this->maxScale;
	}

	/**
	 * Gets the value of deviceWidth.
	 *
	 * @return mixed
	 */
	public function getDevicewidth()
	{
		return $this->deviceWidth;
	}

	/**
	 * Gets the value of deviceHeight.
	 *
	 * @return mixed
	 */
	public function getDeviceheight()
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
	 *  Returns true if device has a large screen
	 * @return bool
	 */
	public function isLarge()
	{
		return ($this->getScreenCategory() == "LARGE" || $this->getScreenCategory() == "XLARGE");
	}
}

?>