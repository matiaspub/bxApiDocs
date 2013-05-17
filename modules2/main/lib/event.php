<?php
namespace Bitrix\Main;

class Event
{
	protected $moduleId;
	protected $type;
	protected $parameters = array();
	protected $filter = null;
	protected $sender = null;

	protected $debugMode = false;
	protected $debugInfo = array();

	/**
	 * @var EventResult[]
	 */
	protected $results = null;

	/**
	 * @var \Exception[]
	 */
	protected $exceptions;

	/**
	 * @param $moduleId
	 * @param $type
	 * @param array $parameters
	 * @param null|string|string[] $filter Filter of module names, mail event names and component names of the event handlers
	 */
	static public function __construct($moduleId, $type, $parameters = array(), $filter = null)
	{
		$this->moduleId = $moduleId;
		$this->type = $type;
		$this->setParameters($parameters);
		$this->setFilter($filter);

		$this->debugMode = false;
		$this->results = null;
	}

	static public function getModuleId()
	{
		return $this->moduleId;
	}

	static public function getEventType()
	{
		return $this->type;
	}

	static public function setParameters($parameters)
	{
		if (!is_array($parameters))
			throw new ArgumentTypeException("parameter", "array");

		$this->parameters = $parameters;
	}

	static public function getParameters()
	{
		return $this->parameters;
	}

	static public function setParameter($key, $value)
	{
		if (!is_array($this->parameters))
			$this->parameters = array();

		$this->parameters[$key] = $value;
	}

	static public function getParameter($key)
	{
		if (isset($this->parameters[$key]))
			return $this->parameters[$key];

		return null;
	}

	static public function setFilter($filter)
	{
		if (!is_array($filter))
		{
			if (empty($filter))
				$filter = null;
			else
				$filter = array($filter);
		}

		$this->filter = $filter;
	}

	static public function getFilter()
	{
		return $this->filter;
	}

	static public function getResults()
	{
		return $this->results;
	}

	static public function addResult(EventResult $result)
	{
		if (!is_array($this->results))
			$this->results = array();

		$this->results[] = $result;
	}

	static public function getSender()
	{
		return $this->sender;
	}

	static public function send($sender = null)
	{
		$this->sender = $sender;
		EventManager::getInstance()->send($this);
	}

	static public function addException(\Exception $exception)
	{
		if (!is_array($this->exceptions))
			$this->exceptions = array();

		$this->exceptions[] = $exception;
	}

	static public function getExceptions()
	{
		return $this->exceptions;
	}

	static public function turnDebugOn()
	{
		$this->debugMode = true;
	}

	static public function isDebugOn()
	{
		return $this->debugMode;
	}

	static public function addDebugInfo($ar)
	{
		if (!$this->debugMode)
			return;

		$this->debugInfo[] = $ar;
	}

	static public function getDebugInfo()
	{
		return $this->debugInfo;
	}
}
