<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Sender;


class TriggerSettings
{
	protected $endpoint = array('MODULE_ID' => '', 'CODE' => '');
	protected $fields = array();
	protected $eventOccur = true;
	protected $preventEmail = false;
	protected $intervalValue = 0;
	protected $intervalType = 'M';
	protected $typeStart = true;
	protected $isClosedTrigger = false;
	protected $runForOldData = false;
	protected $wasRunForOldData = false;
	protected $closedTriggerTime = '';
	protected $closedTriggerInterval = 0;
	protected $eventModuleId = '';
	protected $eventType = '';

	/*
		array(
			'MODULE_ID' => '',
			'CODE' => 'order_cancel',
			'FIELDS' => array(
				'BASKET_PRODUCT_ID' => 99
			),
			'IS_TYPE_START' => true,
			'IS_PREVENT_EMAIL' => true,
			'IS_EVENT_OCCUR' => true,
			'SEND_INTERVAL_UNIT' => 'M',
			'SEND_INTERVAL' => 0,
			'IS_CLOSED_TRIGGER' => false,
			'CLOSED_TRIGGER_INTERVAL' => 0,
			'CLOSED_TRIGGER_TIME' => '',
			'RUN_FOR_OLD_DATA' => '',
			'WAS_RUN_FOR_OLD_DATA' => '',
		)
	 */

	public function __construct(array $settings = null)
	{
		if(empty($settings))
			return;

		$this->setEndpoint($settings['CODE'], $settings['MODULE_ID']);
		$this->setFields($settings['FIELDS']);

		$this->setTypeStart($settings['IS_TYPE_START']);
		$this->setPreventEmail($settings['IS_PREVENT_EMAIL']);
		$this->setEventOccur($settings['IS_EVENT_OCCUR']);
		$this->setInterval($settings['SEND_INTERVAL'], $settings['SEND_INTERVAL_UNIT']);

		$this->setClosedTrigger($settings['IS_CLOSED_TRIGGER']);
		$this->setClosedTriggerInterval($settings['CLOSED_TRIGGER_INTERVAL']);
		$this->setClosedTriggerTime($settings['CLOSED_TRIGGER_TIME']);

		$this->setEventModuleId($settings['EVENT_MODULE_ID']);
		$this->setEventType($settings['EVENT_TYPE']);

		$this->setRunForOldData($settings['RUN_FOR_OLD_DATA']);
		$this->setWasRunForOldData($settings['WAS_RUN_FOR_OLD_DATA']);
	}

	public static function getArrayFromTrigger(Trigger $trigger)
	{
		return array(
			'MODULE_ID' => $trigger->getModuleId(),
			'CODE' => $trigger->getCode(),
			'NAME' => $trigger->getName(),
			'IS_CLOSED_TRIGGER' => ($trigger->isClosed() ? 'Y' : 'N'),
			'CAN_RUN_FOR_OLD_DATA' => ($trigger->canRunForOldData() ? 'Y' : 'N'),
			'CLOSED_TRIGGER_INTERVAL' => '1440',
			'CLOSED_TRIGGER_TIME' => '00:00',
			'EVENT_MODULE_ID' => $trigger->getEventModuleId(),
			'EVENT_TYPE' => $trigger->getEventType(),
		);
	}

	public function getArray()
	{
		return array(
			'MODULE_ID' => $this->getEndpoint('MODULE_ID'),
			'CODE' => $this->getEndpoint('CODE'),
			'FIELDS' => $this->getFields(),
			'IS_TYPE_START' => $this->isTypeStart(),
			'IS_PREVENT_EMAIL' => $this->isPreventEmail(),
			'IS_EVENT_OCCUR' => $this->isEventOccur(),
			'SEND_INTERVAL_UNIT' => $this->getIntervalType(),
			'SEND_INTERVAL' => $this->getIntervalValue(),
			'IS_CLOSED_TRIGGER' => $this->isClosedTrigger(),
			'CLOSED_TRIGGER_INTERVAL' => $this->getClosedTriggerInterval(),
			'CLOSED_TRIGGER_TIME' => $this->getClosedTriggerTime(),
			'RUN_FOR_OLD_DATA' => $this->canRunForOldData(),
			'WAS_RUN_FOR_OLD_DATA' => $this->wasRunForOldData(),
			'EVENT_MODULE_ID' => $this->getEventModuleId(),
			'EVENT_TYPE' => $this->getEventType(),
		);
	}

	/**
	 * @return string
	 */
	public function getTriggerId()
	{
		$endpoint = $this->getEndpoint();
		if(!empty($endpoint['CODE']))
			$triggerId = $endpoint['MODULE_ID'] . '_' . $endpoint['CODE'];
		else
			$triggerId = '';

		return $triggerId;
	}

	/**
	 * @param mixed $key
	 * @return mixed
	 */
	public function getEndpoint($key = null)
	{
		if($key)
		{
			return (isset($this->endpoint[$key]) ? $this->endpoint[$key] : '');
		}
		else
			return $this->endpoint;
	}

	/**
	 * @param mixed $endpoint
	 */
	public function setEndpoint($code, $moduleId)
	{
		if(!empty($code))
		{
			$this->endpoint['CODE'] = $code;
			if(!empty($moduleId))
				$this->endpoint['MODULE_ID'] = $moduleId;
		}
	}

	/**
	 * @return array
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * @param array $fields
	 */
	public function setFields($fields)
	{
		$this->fields = $fields;
	}

	/**
	 * @param bool $state
	 * @return void
	 */
	public function setTypeStart($state)
	{
		if(is_string($state))
		{
			$state = ($state == 'Y' ? true : false);
		}

		$this->typeStart = (bool) $state;
	}

	/**
	 * @return bool
	 */
	public function isTypeStart()
	{
		return $this->typeStart;
	}

	/**
	 * @param bool $state
	 * @return void
	 */
	public function setEventOccur($state)
	{
		if(is_string($state))
		{
			$state = ($state == 'Y' ? true : false);
		}

		$this->eventOccur = (bool) $state;
	}

	/**
	 * @return bool
	 */
	public function isEventOccur()
	{
		return $this->eventOccur;
	}

	/**
	 * @return bool
	 */
	public function isPreventEmail()
	{
		return $this->preventEmail;
	}

	/**
	 * @param boolean $state
	 * @return void
	 */
	public function setPreventEmail($state)
	{
		if(is_string($state))
		{
			$state = ($state == 'Y' ? true : false);
		}


		$this->preventEmail = (bool) $state;
	}

	/**
	 * @return bool
	 */
	public function isClosedTrigger()
	{
		return $this->isClosedTrigger;
	}

	/**
	 * @param boolean $state
	 * @return void
	 */
	public function setClosedTrigger($state)
	{
		if(is_string($state))
		{
			$state = ($state == 'Y' ? true : false);
		}

		$this->isClosedTrigger = (bool) $state;
	}

	/**
	 * @return bool
	 */
	public function getClosedTriggerTime()
	{
		if($this->isClosedTrigger())
			return $this->closedTriggerTime;
		else
			return '';
	}

	/**
	 * @return bool
	 */
	public function setClosedTriggerTime($time)
	{
		$this->closedTriggerTime = (string) $time;
	}

	/**
	 * @return bool
	 */
	public function getClosedTriggerInterval()
	{
		if($this->isClosedTrigger())
			return (int) $this->closedTriggerInterval;
		else
			return 0;
	}

	/**
	 * @return bool
	 */
	public function setClosedTriggerInterval($interval)
	{
		$this->closedTriggerInterval = (int) $interval;
	}

	/**
	 * @param bool $state
	 * @return void
	 */
	public function setRunForOldData($state)
	{
		if(is_string($state))
		{
			$state = ($state == 'Y' ? true : false);
		}

		$this->runForOldData = (bool) $state;
	}

	/**
	 * @return bool
	 */
	public function canRunForOldData()
	{
		return $this->runForOldData;
	}

	/**
	 * @param bool $state
	 * @return void
	 */
	public function setWasRunForOldData($state)
	{
		if(is_string($state))
		{
			$state = ($state == 'Y' ? true : false);
		}

		$this->wasRunForOldData = (bool) $state;
	}

	/**
	 * @return bool
	 */
	public function wasRunForOldData()
	{
		return $this->wasRunForOldData;
	}

	/**
	 * @return void
	 */
	public function setEventModuleId($moduleId)
	{
		$this->eventModuleId = $moduleId;
	}

	/**
	 * @return string
	 */
	public function getEventModuleId()
	{
		return $this->eventModuleId;
	}

	/**
	 * @return void
	 */
	public function setEventType($eventType)
	{
		$this->eventType = $eventType;
	}

	/**
	 * @return string
	 */
	public function getEventType()
	{
		return $this->eventType;
	}

	/**
	 * @return string
	 */
	public function getFullEventType()
	{
		if(!empty($this->eventModuleId) && !empty($this->eventType))
			return $this->eventModuleId . '_' . $this->eventType;
		else
			return '';
	}

	/**
	 * @param int $value
	 * @param string $type
	 */
	public function setInterval($value = 0, $type = 'H')
	{
		$this->setIntervalValue($value);
		$this->setIntervalType($type);
	}

	/**
	 * @return int
	 */
	public function getInterval()
	{
		$value = $this->getIntervalValue();
		if($value <= 0) return 0;

		$type = $this->getIntervalType();
		switch($type)
		{
			case 'H': // hours
				$koeff = 60;
				break;
			case 'D': // days
				$koeff = 60*24;
				break;
			case 'M': // minutes
			default:
				$koeff = 1;
		}

		return $value * $koeff;
	}

	/**
	 * @return mixed
	 */
	public function getIntervalValue()
	{
		return $this->intervalValue;
	}

	/**
	 * @return mixed
	 */
	public function getIntervalType()
	{
		return $this->intervalType;
	}

	/**
	 * @param int $intervalValue
	 */
	public function setIntervalValue($intervalValue)
	{
		$this->intervalValue = (int) $intervalValue;
	}

	/**
	 * @param string $intervalType
	 */
	public function setIntervalType($intervalType)
	{
		$this->intervalType = $intervalType;
	}
}