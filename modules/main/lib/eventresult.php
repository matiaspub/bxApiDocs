<?php
namespace Bitrix\Main;

class EventResult
{
	const UNDEFINED = 0;
	const SUCCESS = 1;
	const ERROR = 2;

	protected $moduleId;
	protected $handler;
	protected $type;
	protected $parameters;

	public function __construct($type, $parameters = null, $moduleId = null, $handler = null)
	{
		$this->type = $type;
		$this->moduleId = $moduleId;
		$this->handler = $handler;
		$this->parameters = $parameters;
	}

	public function getResultType()
	{
		return $this->type;
	}

	public function getModuleId()
	{
		return $this->moduleId;
	}

	public function getHandler()
	{
		return $this->handler;
	}

	public function getParameters()
	{
		return $this->parameters;
	}

	//public function copyParametersToArray($ar)
}
