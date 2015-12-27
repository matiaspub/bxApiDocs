<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Sale\OrderHistory;
use Bitrix\Sale\Result;
use Bitrix\Sale\ResultError;

abstract class Entity
{
	/** @var Fields */
	protected $fields;

	protected function __construct(array $fields = array())
	{
		$this->fields = new Fields($fields);
	}

	/**
	 * @return array
	 *
	 * @throws Main\NotImplementedException
	 */
	public static function getAvailableFields()
	{
		throw new Main\NotImplementedException();
	}

	/**
	 * @return array
	 *
	 * @throws Main\NotImplementedException
	 */
	public static function getAllFields()
	{
		throw new Main\NotImplementedException();
	}

	/**
	 * @return array
	 *
	 * @throws Main\NotImplementedException
	 */
	public static function getMeaningfulFields()
	{
		throw new Main\NotImplementedException();
	}

	/**
	 * @param $name
	 * @return null|string
	 */
	public function getField($name)
	{
		return $this->fields->get($name);
	}

	/**
	 * @param $name
	 * @param $value
	 * @return Result
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotImplementedException
	 * @throws \Exception
	 */
	public function setField($name, $value)
	{
		if ($eventName = static::getEntityEventName())
		{
			/** @var Main\Entity\Event $event */
			$event = new Main\Event('sale', 'OnBefore'.$eventName.'SetField', array(
				'ENTITY' => $this,
				'NAME' => $name,
				'VALUE' => $value,
			));
			$event->send();

			if ($event->getResults())
			{
				$result = new Result();
				/** @var Main\EventResult $eventResult */
				foreach($event->getResults() as $eventResult)
				{
					if($eventResult->getType() == Main\EventResult::SUCCESS)
					{
						if ($eventResultData = $eventResult->getParameters())
						{
							if (isset($eventResultData['VALUE']) && $eventResultData['VALUE'] != $value)
							{
								$value = $eventResultData['VALUE'];
							}
						}
					}
					elseif($eventResult->getType() == Main\EventResult::ERROR)
					{

						$errorMsg = new ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_ON_BEFORE_'.strtoupper($eventName).'_SET_FIELD_ERROR'), 'SALE_EVENT_ON_BEFORE_'.strtoupper($eventName).'_SET_FIELD_ERROR');

						if ($eventResultData = $eventResult->getParameters())
						{
							if (isset($eventResultData['ERROR']) && $eventResultData['ERROR'] instanceof ResultError)
							{
								$errorMsg = $eventResultData['ERROR'];
							}
						}

						$result->addError($errorMsg);

					}
				}

				if (!$result->isSuccess())
				{
					return $result;
				}
			}
		}



		if (!in_array($name, static::getAvailableFields()))
			throw new Main\ArgumentOutOfRangeException("name=$name");

		$oldValue = $this->fields->get($name);
		if ($oldValue != $value)
		{
			if ($eventName = static::getEntityEventName())
			{
				$event = new Main\Event('sale', 'On'.$eventName.'SetField', array(
					'ENTITY' => $this,
					'NAME' => $name,
					'VALUE' => $value,
					'OLD_VALUE' => $oldValue,
				));
				$event->send();

				if ($event->getResults())
				{
					/** @var Main\EventResult $evenResult */
					foreach($event->getResults() as $eventResult)
					{
						if($eventResult->getType() == Main\EventResult::SUCCESS)
						{
							if ($eventResultData = $eventResult->getParameters())
							{
								if (isset($eventResultData['VALUE']) && $eventResultData['VALUE'] != $value)
								{
									$value = $eventResultData['VALUE'];
								}
							}
						}
					}
				}
			}

			$isStartField = $this->isStartField(in_array($name, static::getMeaningfulFields()));

			$this->fields->set($name, $value);
			try
			{
				$result = $this->onFieldModify($name, $oldValue, $value);
				if ($result->isSuccess())
				{
					static::addChangesToHistory($name, $oldValue, $value);
				}
			}
			catch (\Exception $e)
			{
				$this->fields->set($name, $oldValue);
				throw $e;
			}

			if (!$result->isSuccess())
			{
				$this->fields->set($name, $oldValue);
			}
			else
			{
				if ($isStartField)
				{
					$hasMeaningfulFields = $this->hasMeaningfulField();

					/** @var Result $r */
					$r = $this->doFinalAction($hasMeaningfulFields);
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
					}
					else
					{
						if (($data = $r->getData())
							&& !empty($data) && is_array($data))
						{
							$result->setData($result->getData() + $data);
						}
					}
				}
			}

			return $result;
		}

		return new Result();
	}

	/**
	 * @param bool $isMeaningfulField
	 * @return bool
	 */
	abstract public function isStartField($isMeaningfulField = false);

	/**
	 *
	 */
	abstract public function clearStartField();

	/**
	 * @return bool
	 */
	abstract public function hasMeaningfulField();

	/**
	 * @param bool $hasMeaningfulField
	 * @return Result
	 */
	abstract public function doFinalAction($hasMeaningfulField = false);

	/**
	 * @param bool|false $value
	 * @return mixed
	 */
	abstract public function setMathActionOnly($value = false);

	/**
	 * @return bool
	 */
	abstract public function isMathActionOnly();
	/**
	 * @internal
	 *
	 * @param $name
	 * @param $value
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public function setFieldNoDemand($name, $value)
	{
		if (!in_array($name, static::getAllFields()))
		{
			throw new Main\ArgumentOutOfRangeException($name);
		}

		$oldValue = $this->fields->get($name);

		if ($oldValue != $value)
		{
			$this->fields->set($name, $value);
			static::addChangesToHistory($name, $oldValue, $value);
		}
	}


	/**
	 *
	 * @param array $values
	 * @return Result
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 * @throws \Exception
	 */
	public function setFields(array $values)
	{
		$resultData = array();
		$result = new Result();
		$oldValues = null;

		foreach ($values as $key => $value)
		{
			$oldValues[$key] = $this->fields->get($key);
		}

		if ($eventName = static::getEntityEventName())
		{
			$event = new Main\Event('sale', 'OnBefore'.$eventName.'SetFields', array(
				'ENTITY' => $this,
				'VALUES' => $values,
				'OLD_VALUES' => $oldValues
			));
			$event->send();

			if ($event->getResults())
			{
				/** @var Main\EventResult $eventResult */
				foreach($event->getResults() as $eventResult)
				{
					if($eventResult->getType() == Main\EventResult::SUCCESS)
					{
						if ($eventResultData = $eventResult->getParameters())
						{
							if (isset($eventResultData['VALUES']))
							{
								$values = $eventResultData['VALUES'];
							}
						}
					}
					elseif($eventResult->getType() == Main\EventResult::ERROR)
					{
						$errorMsg = new ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_ON_BEFORE_'.strtoupper($eventName).'_SET_FIELDS_ERROR'), 'SALE_EVENT_ON_BEFORE_'.strtoupper($eventName).'_SET_FIELDS_ERROR');

						if ($eventResultData = $eventResult->getParameters())
						{
							if (isset($eventResultData['ERROR']) && $eventResultData['ERROR'] instanceof ResultError)
							{
								$errorMsg = $eventResultData['ERROR'];
							}
						}

						$result->addError($errorMsg);
					}
				}
			}
		}

		if (!$result->isSuccess())
		{
			return $result;
		}

		$isStartField = $this->isStartField();

		foreach ($values as $key => $value)
		{
			$r = $this->setField($key, $value);
			if (!$r->isSuccess())
			{
				$data = $r->getData();
				if (!empty($data) && is_array($data))
				{
					$resultData = array_merge($resultData, $data);
				}
				$result->addErrors($r->getErrors());
			}
		}

		if (!empty($resultData))
		{
			$result->setData($resultData);
		}

		if ($isStartField)
		{
			$hasMeaningfulFields = $this->hasMeaningfulField();

			/** @var Result $r */
			$r = $this->doFinalAction($hasMeaningfulFields);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
			else
			{
				if (($data = $r->getData())
					&& !empty($data) && is_array($data))
				{
					$result->setData($result->getData() + $data);
				}
			}
		}

		return $result;
	}

	/**
	 * @internal
	 *
	 * @param array $values
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public function setFieldsNoDemand(array $values)
	{
		foreach ($values as $key => $value)
		{
			$this->setFieldNoDemand($key, $value);
		}
	}

	/**
	 * @internal
	 *
	 * @param $name
	 * @param $value
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public function initField($name, $value)
	{
		if (!in_array($name, static::getAllFields()))
		{
			throw new Main\ArgumentOutOfRangeException($name);
		}

		$this->fields->init($name, $value);
	}

	/**
	 * @internal
	 *
	 * @param array $values
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public function initFields(array $values)
	{
		foreach ($values as $key => $value)
			$this->initField($key, $value);
	}

	/**
	 * @return array
	 */
	public function getFieldValues()
	{
		return $this->fields->getValues();
	}

	/**
	 * @internal
	 * @return Fields
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * @param string $name
	 * @param mixed $oldValue
	 * @param mixed $value
	 * @return Result
	 */
	protected function onFieldModify($name, $oldValue, $value)
	{
		return new Result();
	}

	public function getId()
	{
		return $this->getField("ID");
	}

	/**
	 * @param string $name
	 * @param null|string $oldValue
	 * @param null|string $value
	 */
	protected function addChangesToHistory($name, $oldValue = null, $value = null)
	{

	}

	protected function getEntityParent()
	{
		$parent = null;
		if ($this instanceof CollectableEntity)
		{
			$parent = $this->getCollection();
		}
		return $parent;
	}

	/**
	 * @return null|string
	 */
	protected static function getEntityEventName()
	{
		$eventName = null;
		$className = static::getClassName();
		$parts = explode("\\", $className);

		$first = true;
		foreach ($parts as $part)
		{
			if (strval(trim($part)) == '')
				continue;

			if ($first === true && $part == "Bitrix")
			{
				$first = false;
				continue;
			}

			$eventName .= $part;
		}

		return $eventName;
	}

	public static function getClassName()
	{
		return get_called_class();
	}

	/**
	 * @return bool
	 */
	public function isChanged()
	{
		return (($changed = $this->fields->getChangedValues()) && !empty($changed));
	}

}