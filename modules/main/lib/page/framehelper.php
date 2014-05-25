<?php
namespace Bitrix\Main\Page;

/**
 * Class FrameHelper
 *
 * Helps to organize work with the frame in an convenient way.
 *
 * Case 1. Show to user marked content and then replace it with new one.
 * <code>
 * $frame = new Bitrix\Main\Page\FrameHelper(mt_rand(0, 10000));
 * $frame->begin();
 * echo "1@".(time()+5);
 * $frame->end();
 * </code>
 *
 * Case 2. Show to user empty space and then replace it with new one.
 * <code>
 * $frame = new Bitrix\Main\Page\FrameHelper(mt_rand(0, 10000));
 * $frame->begin("");
 * echo "empty@".(time()+6);
 * $frame->end();
 * </code>
 *
 * Case 3. Show to user $stub and then replace it with new one.
 * <code>
 * $frame = new Bitrix\Main\Page\FrameHelper(mt_rand(0, 10000));
 * $frame->begin("loading...");
 * echo "2@".(time()+7);
 * $frame->end();
 * </code>
 *
 * Case 4. Show to user content after beginStub() and then replace it with the one before this method is called.
 * <code>
 * $frame = new Bitrix\Main\Page\FrameHelper(mt_rand(0, 10000));
 * $frame->begin();
 * echo "&lt;div&gt;3@".(time()+7)."&lt;/div&gt;";
 * $frame->beginStub();
 * echo "&lt;div&gt;waiting&lt;/div&gt;";
 * $frame->end();
 * </code>
 *
 * @package Bitrix\Main\Page
 */
final class FrameHelper
{
	private $id = "";
	private $containerId = null;
	private $useBrowserStorage = false;
	private $useAnimation = false;
	private $autoUpdate = true;
	private $staticPart = null;
	private $dynamicPart = null;
	private $started = false;
	private $ended = false;

	/**
	 * @param $id
	 * @param bool $autoContainer
	 */
	public function __construct($id, $autoContainer = true)
	{
		$this->id = $id;
		if (!$autoContainer)
		{
			$this->containerId = $id;
		}
	}

	/**
	 * Starts an dynamic frame.
	 * Returns self object instance.
	 *
	 * @see Bitrix\Main\Page\FrameHelper
	 * @see Bitrix\Main\Page\FrameHelper::end
	 *
	 * @param null|string $stub
	 * @return \Bitrix\Main\Page\FrameHelper
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	public function begin($stub = null)
	{
		if ($this->started)
			throw new \Bitrix\Main\NotSupportedException("begin() has been called. Frame id: ".$this->id.".");

		Frame::getInstance()->startDynamicWithID($this->id);
		$this->started = true;
		$this->staticPart = $stub;
		ob_start();
		return $this;
	}

	/**
	 * Starts static part of dynamic frame which will be shown to user.
	 * Method begin() must be called before.
	 * Returns self object instance.
	 *
	 * @see Bitrix\Main\Page\FrameHelper
	 * @see Bitrix\Main\Page\FrameHelper::begin
	 *
	 * @return \Bitrix\Main\Page\FrameHelper
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	public function beginStub()
	{
		if (!$this->started)
			throw new \Bitrix\Main\NotSupportedException("begin() has not been called. Frame id: ".$this->id.".");

		$this->dynamicPart = ob_get_contents();
		ob_end_clean();
		ob_start();
		return $this;
	}

	/**
	 * Ends dynamic part of the content.
	 * Method begin() must be called before.
	 * Returns self object instance.
	 *
	 * @see Bitrix\Main\Page\FrameHelper
	 * @see Bitrix\Main\Page\FrameHelper::begin
	 *
	 * @return \Bitrix\Main\Page\FrameHelper
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	public function end()
	{
		if (!$this->started)
			throw new \Bitrix\Main\NotSupportedException("begin() has not been called. Frame id: ".$this->id.".");

		if ($this->ended)
			throw new \Bitrix\Main\NotSupportedException("begin() has been called. Frame id: ".$this->id.".");

		//if beginStub() was called
		if ($this->dynamicPart !== null)
		{
			if ($this->staticPart !== null)
			{
				throw new \Bitrix\Main\NotSupportedException("begin() was called with a stub. Frame id: ".$this->id.".");
			}

			$this->staticPart = ob_get_contents();
			ob_end_clean();
			echo $this->dynamicPart;
		}
		else
		{
			$this->dynamicPart = ob_get_contents();
			ob_end_flush();
		}

		Frame::getInstance()->finishDynamicWithID(
			$this->id,
			$this->staticPart === null? $this->dynamicPart: $this->staticPart,
			$this->containerId,
			$this->useBrowserStorage,
			$this->autoUpdate,
			$this->useAnimation
		);
		$this->ended = true;
		return $this;
	}

	/**
	 * Returns true if Frame was started.
	 *
	 * @return bool
	 */
	public function isStarted()
	{
		return $this->started;
	}

	/**
	 * Returns true if Frame was ended.
	 *
	 * @return bool
	 */
	public function isEnded()
	{
		return $this->ended;
	}

	/**
	 * Sets container id, so container will not be automatically generated.
	 *
	 * @param string $containerId
	 * @return \Bitrix\Main\Page\FrameHelper
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	public function setContainerId($containerId)
	{
		if ($this->ended)
			throw new \Bitrix\Main\NotSupportedException("end() has been called. Frame id: ".$this->id.".");

		$this->containerId = $containerId;
		return $this;
	}

	/**
	 * Enables usage of browser local database for storing dynamic part of content.
	 *
	 * @param bool $useBrowserStorage
	 * @return \Bitrix\Main\Page\FrameHelper
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	public function setBrowserStorage($useBrowserStorage)
	{
		if ($this->ended)
			throw new \Bitrix\Main\NotSupportedException("end() has been called. Frame id: ".$this->id.".");

		$this->useBrowserStorage = $useBrowserStorage;
		return $this;
	}

	/**
	 * Updates the dynamic part with animation.
	 *
	 * @param bool $useAnimation
	 * @return \Bitrix\Main\Page\FrameHelper
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	public function setAnimation($useAnimation)
	{
		if ($this->ended)
			throw new \Bitrix\Main\NotSupportedException("end() has been called. Frame id: ".$this->id.".");

		$this->useAnimation = $useAnimation;
		return $this;
	}

	/**
	 * Disables automatic update of the dynamic part.
	 *
	 * @param bool $autoUpdate
	 * @return \Bitrix\Main\Page\FrameHelper
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	public function setAutoUpdate($autoUpdate)
	{
		if ($this->ended)
			throw new \Bitrix\Main\NotSupportedException("end() has been called. Frame id: ".$this->id.".");

		$this->autoUpdate = $autoUpdate;
		return $this;
	}

	/**
	 * Returns internal state of the object for storing in cache.
	 *
	 * @return array
	 */
	public function getCachedData()
	{
		return array(
			"id" => $this->id,
			"containerId" => $this->containerId,
			"staticPart" => $this->staticPart,
			"dynamicPart" => $this->dynamicPart,
			"useBrowserStorage" => $this->useBrowserStorage,
			"autoUpdate" => $this->autoUpdate,
			"useAnimation" => $this->useAnimation,
		);
	}

	/**
	 * Apply previously saved state.
	 *
	 * @param $cachedData
	 */
	public static function applyCachedData($cachedData)
	{
		$frame = Frame::getInstance();
		$frame->addDynamicData(
			$cachedData["id"],
			$cachedData["dynamicPart"],
			$cachedData["staticPart"],
			$cachedData["containerId"],
			$cachedData["useBrowserStorage"],
			$cachedData["autoUpdate"],
			$cachedData["useAnimation"]
		);
	}
}