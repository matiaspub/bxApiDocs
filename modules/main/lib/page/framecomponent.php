<?
namespace Bitrix\Main\Page;

class FrameComponent
{
	/** @var \CBitrixComponent */
	private $component = null;
	private $started = false;

	public function __construct($component)
	{
		$this->component = $component;
	}

	public function start()
	{
		if (!Frame::getUseHTMLCache() || !$this->isFirstLevelComponent())
		{
			return false;
		}

		if (FrameStatic::getCurrentDynamicId() !== false)
		{
			return false;
		}

		if ($this->component->getDefaultFrameMode() === false || $this->getFrameType() === "STATIC")
		{
			return false;
		}
		
		if (in_array($this->component->getName(), array("bitrix:breadcrumb")))
		{
			return false;
		}

		$this->started = true;

		ob_start();

		return true;
	}

	public function end()
	{
		if (!$this->started)
		{
			return false;
		}

		$isComponentAdapted =
			$this->component->getRealFrameMode() !== null ||
			($this->component->__template !== null && $this->component->__template->getRealFrameMode() !== null);

		if ($isComponentAdapted)
		{
			ob_end_flush();
		}
		else
		{
			$stub = ob_get_contents();
			ob_end_clean();

			$frame = new FrameStatic($this->component->randString());

			if ($this->getFrameType() === "DYNAMIC_WITH_STUB")
			{
				$frame->setStub($stub);
			}
			elseif ($this->getFrameType() === "DYNAMIC_WITH_STUB_LOADING")
			{
				$frame->setStub('<div class="bx-composite-loading"></div>');
			}

			$frame->startDynamicArea();
			echo $stub;
			$frame->finishDynamicArea();
		}

		return true;
	}

	public function getFrameType()
	{
		$componentParams = $this->component->arParams;
		if (isset($componentParams["COMPOSITE_FRAME_TYPE"]) && is_string($componentParams["COMPOSITE_FRAME_TYPE"]))
		{
			$type = strtoupper($componentParams["COMPOSITE_FRAME_TYPE"]);
			if (in_array($type, static::getFrameTypes()))
			{
				return $type;
			}
		}

		$compositeOptions = \CHTMLPagesCache::getOptions();
		if (isset($compositeOptions["FRAME_TYPE"]) && is_string($compositeOptions["FRAME_TYPE"]))
		{
			$type = strtoupper($compositeOptions["FRAME_TYPE"]);
			if (in_array($type, static::getFrameTypes()))
			{
				return $type;
			}
		}

		return "STATIC";
	}
	
	public static function getFrameTypes()
	{
		return array(
			"STATIC",
			"DYNAMIC_WITH_STUB",
			"DYNAMIC_WITH_STUB_LOADING",
			"DYNAMIC_WITHOUT_STUB",
		);
	}

	private function isFirstLevelComponent()
	{
		return count($GLOBALS["APPLICATION"]->getComponentStack()) <= 1;
	}
}