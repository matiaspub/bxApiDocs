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
	
	/**
	* <p>Нестатический метод запускает создание динамической зоны. Возвращает собственный экземпляр объекта.</p>
	*
	*
	* @param mixed $null  
	*
	* @param string $stub = null 
	*
	* @return \Bitrix\Main\Page\FrameHelper 
	*
	* <h4>See Also</h4> 
	* <ul> <li><code>\Bitrix\Main\Page\FrameHelper</code></li> <li><code>\Bitrix\Main\Page\FrameHelper::end</code></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/framebuffered/begin.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод запускает статическую часть динамической зоны, которая будет показана пользователю. Метод <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/framebuffered/begin.php">begin</a> должен быть вызван перед использованием данного метода. Возвращает собственный экземпляр объекта.</p> <p>Без параметров</p>
	*
	*
	* @return \Bitrix\Main\Page\FrameHelper 
	*
	* <h4>See Also</h4> 
	* <ul> <li><code>\Bitrix\Main\Page\FrameHelper</code></li> <li><code>\Bitrix\Main\Page\FrameHelper::begin</code></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/framebuffered/beginstub.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод завершает динамическую часть контента. Возвращает собственный экземпляр объекта.</p> <p>Перед вызовом данного метода должен быть вызван метод <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/framebuffered/begin.php">begin</a>.</p> <p>Без параметров</p>
	*
	*
	* @return \Bitrix\Main\Page\FrameHelper 
	*
	* <h4>See Also</h4> 
	* <ul> <li><code>\Bitrix\Main\Page\FrameHelper</code></li> <li><code>\Bitrix\Main\Page\FrameHelper::begin</code></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/framebuffered/end.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает <i>true</i> если динамическая область была запущена.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/framebuffered/isstarted.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает <i>true</i> если динамическая область была завершена.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/page/framebuffered/isended.php
	* @author Bitrix
	*/
	public function isEnded()
	{
		return $this->ended;
	}
}