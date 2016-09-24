<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

class Event extends \Bitrix\Main\Event
{
	protected $entity;
	protected $entityEventType;

	/**
	 * @param Base   $entity
	 * @param string $type
	 * @param array  $parameters
	 * @param bool   $withNamespace
	 */
	public function __construct(Base $entity, $type, array $parameters = array(), $withNamespace = false)
	{
		if ($withNamespace)
		{
			$eventName = $entity->getNamespace() . $entity->getName() . '::' . $type;
			$this->entityEventType = $type;
		}
		else
		{
			$eventName = $entity->getName().$type;
		}

		parent::__construct($entity->getModule(), $eventName, $parameters);

		$this->entity = $entity;
	}

	/**
	 * Returns entity
	 */
	
	/**
	* <p>Нестатический метод возвращает объект.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/event/getentity.php
	* @author Bitrix
	*/
	public function getEntity()
	{
		return $this->entity;
	}

	/**
	 * Checks the result of the event for errors, fills the Result object.
	 * Returns true on errors, false on no errors.
	 *
	 * @param Result $result
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод проверяет результат событий на ошибки, наполняет объект <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/result/index.php">\Bitrix\Main\Result</a>. Возвращает <i>true</i> в случае ошибки и <i>false</i> в противном случае.</p>
	*
	*
	* @param mixed $Bitrix  
	*
	* @param Bitri $Main  
	*
	* @param Mai $Entity  
	*
	* @param Result $result  
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/event/geterrors.php
	* @author Bitrix
	*/
	public function getErrors(Result $result)
	{
		$hasErrors = false;

		/** @var $evenResult EventResult */
		foreach($this->getResults() as $evenResult)
		{
			if($evenResult->getType() === EventResult::ERROR)
			{
				$hasErrors = true;
				$result->addErrors($evenResult->getErrors());
			}
		}
		return $hasErrors;
	}

	/**
	 * Merges the data fields set in the event handlers with the source fields.
	 * Returns a merged array of the data fields from the all event handlers.
	 *
	 * @param array $data
	 * @return array
	 */
	
	/**
	* <p>Нестатический метод объединяет поля данных, установленные в обработчиках событий с полями источника. Возвращает объединённый массив полей данных из всех обработчиков событий.</p>
	*
	*
	* @param array $data  
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/event/mergefields.php
	* @author Bitrix
	*/
	public function mergeFields(array $data)
	{
		if ($this->getResults() != null)
		{
			/** @var $evenResult EventResult */
			foreach($this->getResults() as $evenResult)
			{
				$removed = $evenResult->getUnset();
				foreach($removed as $val)
				{
					unset($data[$val]);
				}

				$modified = $evenResult->getModified();
				if(!empty($modified))
				{
					$data = array_merge($data, $modified);
				}
			}
		}
		return $data;
	}

	public function send($sender = null)
	{
		static $events = array(
			DataManager::EVENT_ON_BEFORE_ADD => true,
			DataManager::EVENT_ON_ADD => true,
			DataManager::EVENT_ON_AFTER_ADD => true,
			DataManager::EVENT_ON_BEFORE_UPDATE => true,
			DataManager::EVENT_ON_UPDATE => true,
			DataManager::EVENT_ON_AFTER_UPDATE => true,
			DataManager::EVENT_ON_BEFORE_DELETE => true,
			DataManager::EVENT_ON_DELETE => true,
			DataManager::EVENT_ON_AFTER_DELETE => true,
		);

		if(isset($events[$this->entityEventType]))
		{
			//The event handler function name magically equals to the event type (e.g. "OnBeforeAdd").
			//There are emtpy handlers in the DataManager class.
			$result = call_user_func_array(array($this->entity->getDataClass(), $this->entityEventType), array($this));
			if (($result !== null) && !($result instanceof EventResult))
			{
				$result = new EventResult();
			}
			if($result !== null)
			{
				$this->addResult($result);
			}
		}

		parent::send($sender);
	}
}
