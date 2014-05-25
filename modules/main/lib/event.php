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
	public function __construct($moduleId, $type, $parameters = array(), $filter = null)
	{
		$this->moduleId = $moduleId;
		$this->type = $type;
		$this->setParameters($parameters);
		$this->setFilter($filter);

		$this->debugMode = false;
		$this->results = null;
	}

	public function getModuleId()
	{
		return $this->moduleId;
	}

	public function getEventType()
	{
		return $this->type;
	}

	public function setParameters($parameters)
	{
		if (!is_array($parameters))
			throw new ArgumentTypeException("parameter", "array");

		$this->parameters = $parameters;
	}

	public function getParameters()
	{
		return $this->parameters;
	}

	public function setParameter($key, $value)
	{
		if (!is_array($this->parameters))
			$this->parameters = array();

		$this->parameters[$key] = $value;
	}

	public function getParameter($key)
	{
		if (isset($this->parameters[$key]))
			return $this->parameters[$key];

		return null;
	}

	public function setFilter($filter)
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

	public function getFilter()
	{
		return $this->filter;
	}

	public function getResults()
	{
		return $this->results;
	}

	public function addResult(EventResult $result)
	{
		if (!is_array($this->results))
			$this->results = array();

		$this->results[] = $result;
	}

	public function getSender()
	{
		return $this->sender;
	}

	public function send($sender = null)
	{
		$this->sender = $sender;
		EventManager::getInstance()->send($this);
	}

	public function addException(\Exception $exception)
	{
		if (!is_array($this->exceptions))
			$this->exceptions = array();

		$this->exceptions[] = $exception;
	}

	public function getExceptions()
	{
		return $this->exceptions;
	}

	public function turnDebugOn()
	{
		$this->debugMode = true;
	}

	public function isDebugOn()
	{
		return $this->debugMode;
	}

	public function addDebugInfo($ar)
	{
		if (!$this->debugMode)
			return;

		$this->debugInfo[] = $ar;
	}

	public function getDebugInfo()
	{
		return $this->debugInfo;
	}
}
