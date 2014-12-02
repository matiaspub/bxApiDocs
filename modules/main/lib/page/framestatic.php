<?
namespace Bitrix\Main\Page;

class FrameStatic
{
	private $id = null;
	private $stub = "";
	private $containerId = null;
	private $useBrowserStorage = false;
	private $useAnimation = false;
	private $autoUpdate = true;
	private $assetMode = AssetMode::ALL;

	/**
	 * @var FrameStatic[]
	 */
	private static $dynamicAreas = array();
	private static $curDynamicId = false;
	private static $containers = array();

	public function __construct($id)
	{
		$this->id = $id;
	}

	public function startDynamicArea()
	{
		if (isset(self::$dynamicAreas[$this->id])
			|| $this->id == self::$curDynamicId
			|| self::$curDynamicId !== false
		)
		{
			return false;
		}

		echo '<!--\'start_frame_cache_'.$this->id.'\'-->';

		self::$curDynamicId = $this->id;
		self::addDynamicArea($this);

		Asset::getInstance()->startTarget($this->getAssetId(), $this->assetMode);

		return true;
	}

	public function finishDynamicArea()
	{
		if (self::$curDynamicId !== $this->id)
		{
			return false;
		}

		echo '<!--\'end_frame_cache_'.$this->id.'\'-->';

		self::$curDynamicId = false;

		Asset::getInstance()->stopTarget($this->getAssetId());

		return true;
	}

	public static function addDynamicArea(FrameStatic $area)
	{
		self::$dynamicAreas[$area->getId()] = $area;
	}

	/**
	 * @return array[]
	 */
	public static function getDynamicIDs()
	{
		return array_keys(self::$dynamicAreas);
	}

	/**
	 * @return FrameStatic[]
	 */
	public static function getDynamicAreas()
	{
		return self::$dynamicAreas;
	}

	/**
	 * @param string $id Dynamic Area Id
	 * @return FrameStatic
	 */
	public static function getDynamicArea($id)
	{
		return isset(self::$dynamicAreas[$id]) ? self::$dynamicAreas[$id] : null;
	}

	/**
	 * @return FrameStatic
	 */
	public static function getCurrentDynamicArea()
	{
		if (self::$curDynamicId !== false && isset(self::$dynamicAreas[self::$curDynamicId]))
		{
			return self::$dynamicAreas[self::$curDynamicId];
		}

		return null;
	}

	public static function getCurrentDynamicId()
	{
		return self::$curDynamicId;
	}

	public static function getContainers()
	{
		return self::$containers;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getAssetId()
	{
		return "frame_".$this->id;
	}

	public function setStub($stub)
	{
		$this->stub = $stub;
	}

	public function getStub()
	{
		return $this->stub;
	}

	public function setContainerId($containerId)
	{
		$this->containerId = $containerId;
		if ($this->containerId !== null)
			self::$containers[$this->id] = $containerId;
	}

	public function getContainerId()
	{
		return $this->containerId;
	}

	public function setBrowserStorage($useBrowserStorage)
	{
		$this->useBrowserStorage = $useBrowserStorage;
	}

	public function getBrowserStorage()
	{
		return $this->useBrowserStorage;
	}

	public function setAnimation($useAnimation)
	{
		$this->useAnimation = $useAnimation;
	}

	public function getAnimation()
	{
		return $this->useAnimation;
	}

	public function setAutoUpdate($autoUpdate)
	{
		$this->autoUpdate = $autoUpdate;
	}

	public function getAutoUpdate()
	{
		return $this->autoUpdate;
	}

	/**
	 * @param AssetMode $mode
	 */
	public function setAssetMode($mode)
	{
		// startDynamicArea wasn't invoked
		if (self::getDynamicArea($this->id) === null)
		{
			$this->assetMode = $mode;
		}
	}

	public function getAssetMode()
	{
		return $this->assetMode;
	}

	/**
	 * Returns internal state of the object for storing in cache.
	 *
	 * @return array
	 */
	public function getCachedData()
	{
		return array(
			"id" => $this->getId(),
			"containerId" => $this->getContainerId(),
			"staticPart" => $this->getStub(),
			"useBrowserStorage" => $this->getBrowserStorage(),
			"autoUpdate" => $this->getAutoUpdate(),
			"useAnimation" => $this->getAnimation(),
		);
	}

	/**
	 * Apply previously saved state.
	 *
	 * @param $cachedData
	 */
	public static function applyCachedData($cachedData)
	{
		$area = new static($cachedData["id"]);
		$area->setStub($cachedData["staticPart"]);
		$area->setContainerId($cachedData["containerId"]);
		$area->setBrowserStorage($cachedData["useBrowserStorage"]);
		$area->setAutoUpdate($cachedData["autoUpdate"]);
		$area->setAnimation($cachedData["useAnimation"]);

		self::addDynamicArea($area);
	}
}