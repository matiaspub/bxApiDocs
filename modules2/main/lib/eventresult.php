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

	static public function __construct($type, $parameters = null, $moduleId = null, $handler = null)
	{
		$this->type = $type;
		$this->moduleId = $moduleId;
		$this->handler = $handler;
		$this->parameters = $parameters;
	}

	static public function getResultType()
	{
		return $this->type;
	}

	static public function getModuleId()
	{
		return $this->moduleId;
	}

	static public function getHandler()
	{
		return $this->handler;
	}

	static public function getParameters()
	{
		return $this->parameters;
	}

	//public function copyParametersToArray($ar)
}
