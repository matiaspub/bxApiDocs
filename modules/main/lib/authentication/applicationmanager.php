<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Main\Authentication;

use Bitrix\Main;

class ApplicationManager
{
	/** @var ApplicationManager */
	protected static $instance;

	protected $applications = array();

	protected function __construct()
	{
		$event = new Main\Event("main", "OnApplicationsBuildList");
		$event->send();

		foreach($event->getResults() as $eventResult)
		{
			$result = $eventResult->getParameters();
			if(is_array($result))
			{
				if(!is_array($result[0]))
				{
					$result = array($result);
				}
				foreach($result as $app)
				{
					$this->applications[$app["ID"]] = $app;
				}
			}
		}
		Main\Type\Collection::sortByColumn($this->applications, "SORT");
	}

	public static function getInstance()
	{
		if (!isset(static::$instance))
		{
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Returns sorted array which describes available applications.
	 *
	 * @return array Array of arrays:
	 * 		array("ID" => array(
	 *	 		"ID" => application id,
	 * 			"NAME" => application name,
	 * 			"SORT" => application sort index,
	 * 			"CLASS" => application class name
	 * 		))
	 */
	public function getApplications()
	{
		return $this->applications;
	}

	/**
	 * Checks the valid scope for the applicaton.
	 *
	 * @param string $applicationId
	 * @return bool
	 */
	public function checkScope($applicationId)
	{
		if(isset($this->applications[$applicationId]))
		{
			$className = $this->applications[$applicationId]["CLASS"];
			$class = new $className;
			if(is_callable(array($class, "checkScope")))
			{
				return call_user_func_array(array($class, "checkScope"), array());
			}
		}
		return false;
	}
}
