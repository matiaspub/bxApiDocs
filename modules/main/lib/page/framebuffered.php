<?
namespace Bitrix\Main\Page;

use Bitrix\Main\NotSupportedException;

class FrameBuffered extends FrameStatic
{
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
		parent::__construct($id);
		if (!$autoContainer)
		{
			$this->setContainerId($id);
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
	 * @throws NotSupportedException
	 */
	public function begin($stub = null)
	{
		if ($this->started)
		{
			throw new NotSupportedException("begin() has been called. Frame id: ".$this->getId().".");
		}

		$this->startDynamicArea();

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
	 * @throws NotSupportedException
	 */
	public function beginStub()
	{
		if (!$this->started)
		{
			throw new NotSupportedException("begin() has not been called. Frame id: ".$this->getId().".");
		}

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
	 * @throws NotSupportedException
	 */
	public function end()
	{
		if (!$this->started)
		{
			throw new NotSupportedException("begin() has not been called. Frame id: ".$this->getId().".");
		}

		if ($this->ended)
		{
			throw new NotSupportedException("begin() has been called. Frame id: ".$this->getId().".");
		}

		//if beginStub() was called
		if ($this->dynamicPart !== null)
		{
			if ($this->staticPart !== null)
			{
				throw new NotSupportedException("begin() was called with a stub. Frame id: ".$this->getId().".");
			}

			$this->staticPart = ob_get_contents();
			ob_end_clean();
			echo $this->dynamicPart;
		}
		else
		{
			$this->dynamicPart = ob_get_contents();
			if ($this->staticPart === null)
			{
				$this->staticPart = $this->dynamicPart;
			}
			ob_end_flush();
		}

		$this->setStub($this->staticPart);
		$this->finishDynamicArea();

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
}