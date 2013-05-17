<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

class DataManagerEvent extends \Bitrix\Main\Event
{
	/**
	 * @param Base $entity
	 * @param string $type
	 * @param array $parameters
	 */
	static public function __construct($entity, $type, array $parameters = array())
	{
		parent::__construct($entity->getModule(), $entity->getName().$type, $parameters);
	}

	/**
	 * @param Result $result
	 * @return bool
	 */
	static public function getErrors(Result $result = null)
	{
		$hasErrors = false;
		if ($this->getResults() != null)
		{
			foreach($this->getResults() as $evenResult)
			{
				if($evenResult->getResultType() == \Bitrix\Main\EventResult::ERROR)
				{
					$hasErrors = true;
					if($result !== null)
					{
						$result->addErrors($evenResult->getParameters());
					}
					else
					{
						break;
					}
				}
			}
		}
		return $hasErrors;
	}
}
