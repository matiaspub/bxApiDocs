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
		if ($_COOKIE["IS_WEBRTC_SUPPORTED"] && $_COOKIE["IS_WEBRTC_SUPPORTED"] == "Y")
		{
			$this->setWebRtcSupport(true);
		}
		if ($_COOKIE["IS_BXSCRIPT_SUPPORTED"] && $_COOKIE["IS_BXSCRIPT_SUPPORTED"] == "Y")
		{
			$this->setBXScriptSupported(true);
		}

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

		if (array_key_exists("emulate_platform", $_REQUEST))
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
		elseif (array_key_exists("api_version", $_REQUEST))
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
	 * Returns true if mobile application made this request in background
	 * @return bool
	 */
	
	/**
	* <p>Возвращает <code>true</code>, если запрос был сделан мобильным приложением в фоновом режиме. Метод статический. </p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/isappbackground.php
	* @author Bitrix
	*/
	public static function isAppBackground()
	{
		$isBackground = Context::getCurrent()->getServer()->get("HTTP_BX_MOBILE_BACKGROUND");
		return ($isBackground === "true");
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
		$this->userScalable = ($userScalable === false ? "no" : "yes");
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
		if (self::$platform == "android")
		{
			/**
			 * This is workaround for android
			 * We use console.log() to tell the application about successful loading of this page
			 */
			$APPLICATION->AddHeadString("<script type=\"text/javascript\">console.log(\"bxdata://success\")</script>", false, true);
		}
		if (self::getInstance()->getBXScriptSupported())
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
	
	/**
	* <p>Конвертирует строку в UTF-8 и возвращает ее. Метод статический.</p>
	*
	*
	* @param mixed $strings = '' Возвращаемая строка в UTF-8 кодировке.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/preparestrtojson.php
	* @author Bitrix
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


	public static function onMobileInit()
	{
		if (!defined("MOBILE_INIT_EVENT_SKIP"))
		{
			$db_events = getModuleEvents("mobileapp", "OnMobileInit");
			while ($arEvent = $db_events->Fetch())
			{
				ExecuteModuleEventEx($arEvent);
			}
		}
	}

	/**
	 * Gets target dpi for a viewport meta data
	 *
	 * @return string
	 */
	
	/**
	* <p>Получает значение dpi для метатега <code>viewport</code>. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/gettargetdpi.php
	* @author Bitrix
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
	
	/**
	* <p>Используется для получения текущего значения <code>content=""</code> метатега <code>viewport</code>. Метод нестатический.</p>
	*
	*
	* @param string $width = "" Ширина viewport.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/getviewport.php
	* @author Bitrix
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


		if ($this->getWidth())
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
			{
				$contentAttributes[] = "width=device-width";
			}
			$contentAttributes[] = "target-densitydpi=" . $this->getTargetDpi();
		}


		$contentAttributes[] = "user-scalable=" . $this->getUserScalable();

		return str_replace("#content_value#", implode(", ", $contentAttributes), $viewPortMeta);
	}

	/**
	 * Use it to get value of viewport-metadata for large screen of android based device.
	 */
	
	/**
	* <p>Возвращает значение метатега <code>viewport</code> для планшетов на базе Android. Метод нестатический. </p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/getlargescreenviewport.php
	* @author Bitrix
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
	
	/**
	* <p>Получает значение <code>viewport-metadata</code> для Apple iPad. Метод нестатический.</p>
	*
	*
	* @param integer $width = 320 Ширина экрана устройства.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/getipadviewport.php
	* @author Bitrix
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
	
	/**
	* <p>Используется для получения  значения метатега <code>viewport</code> для portrait режима. Метод нестатичный.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/getviewportportrait.php
	* @author Bitrix
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
	
	/**
	* <p>Используется для получения  значения метатега <code>viewport</code> для landscape режима. Метод нестатичный.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/getviewportlandscape.php
	* @author Bitrix
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
	
	/**
	* <p>Устанавливает значение <code>minScale</code> (параметр <code>minimum-scale</code> в метатеге <code>viewport</code>). Метод нестатический.</p>
	*
	*
	* @param mixed $minScale  Минимальный масштаб viewport. Число с точкой (от 0.1 до 10), 1.0 - не
	* масштабировать. По-умолчанию 0.25 в мобильном Safari.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/setminscale.php
	* @author Bitrix
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
	
	/**
	* <p>Устанавливает значение <code>iniScale</code> (параметр <code>initial-scale</code> метатега <code>viewport</code> - начальный масштаб страницы). Метод нестатический.</p>
	*
	*
	* @param mixed $iniScale  Начальный масштаб страницы. Чем больше число, тем выше масштаб.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/setiniscale.php
	* @author Bitrix
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
	
	/**
	* <p>Устанавливает значение <code>maxScale</code> (параметр <code>maximum-scale</code> в метатеге <code>viewport</code>). Метод нестатический.</p>
	*
	*
	* @param mixed $maxScale  Максимальный масштаб viewport. Число с точкой (от 0.1 до 10), 1.0 - не
	* масштабировать. По-умолчанию 1.6 в мобильном Safari.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/setmaxscale.php
	* @author Bitrix
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
	
	/**
	* <p>Устанавливает значение <code>deviceWidth</code>. Метод нестатический.</p>
	*
	*
	* @param mixed $deviceWidth  Значение <code>deviceWidth</code> - ширина viewport.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/setdevicewidth.php
	* @author Bitrix
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
	
	/**
	* <p>Устанавливает значение <code>deviceHeight</code>. Метод нестатический.</p>
	*
	*
	* @param mixed $deviceHeight  Значение <code>deviceHeight</code> - высота viewport.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/setdeviceheight.php
	* @author Bitrix
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
	
	/**
	* <p>Получает значение <code>pixelRatio</code>. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/getpixelratio.php
	* @author Bitrix
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
	
	/**
	* <p>Возвращает значение <code>minScale</code>. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/getminscale.php
	* @author Bitrix
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
	
	/**
	* <p>Получает значение <code>iniScale</code> (параметр <code>initial-scale</code> метатега <code>viewport</code> - начальный масштаб страницы). Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/getiniscale.php
	* @author Bitrix
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
	
	/**
	* <p>Возвращает значение <code>maxScale</code>. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/getmaxscale.php
	* @author Bitrix
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
	
	/**
	* <p>Получает значение <code>deviceWidth</code> - ширина экрана устройства. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/getdevicewidth.php
	* @author Bitrix
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
	
	/**
	* <p>Получает значение <code>deviceHeight</code> - высоту экрана устройства. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/getdeviceheight.php
	* @author Bitrix
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
	
	/**
	* <p>Получает значение <code>deviceDpi</code>. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/getscreencategory.php
	* @author Bitrix
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
	
	/**
	* <p>Устанавливает значение <code>largeScreenSupport</code>. Метод нестатический.</p>
	*
	*
	* @param mixed $largeScreenSupport  the $largeScreenSupport
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/setlargescreensupport.php
	* @author Bitrix
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
	
	/**
	* <p>Получает значение <code>largeScreenSupport</code>. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/getlargescreensupport.php
	* @author Bitrix
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
	
	/**
	* <p>Получает значение <code>scale</code>. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/getscale.php
	* @author Bitrix
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

	
	/**
	* <p>Устанавливает значение <code>scale</code>. Метод нестатический.</p>
	*
	*
	* @param mixed $scale  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/setscale.php
	* @author Bitrix
	*/
	public function setScale($scale)
	{
		$this->scale = $scale;
	}

	/**
	 * gets the value of  self::platform
	 * @return string
	 */
	
	/**
	* <p>Получает значение <code>self::platform</code>. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/getplatform.php
	* @author Bitrix
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
	
	/**
	* <p>Метод устанавливает значение <code>self::$apiVersion</code> - версия API. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/getapiversion.php
	* @author Bitrix
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
	
	/**
	* <p>Возвращает версию <b>PhoneGap</b>. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/getpgversion.php
	* @author Bitrix
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
	
	/**
	* <p>Возвращает <code>true</code>, если устройство имеет большой экран (<code>LARGE</code> или <code>XLARGE</code>). Метод нестатичный.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mobileapp/mobile/islarge.php
	* @author Bitrix
	*/
	public function isLarge()
	{
		return ($this->getScreenCategory() == "LARGE" || $this->getScreenCategory() == "XLARGE");
	}
}

?>