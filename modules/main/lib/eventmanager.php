<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\Diag;
use Bitrix\Main\IO;

class EventManager
{
	/**
	 * @var EventManager
	 */
	protected static $instance;

	protected $handlers = array();
	protected $isHandlersLoaded = false;

	protected static $cacheKey = "b_module_to_module";

	protected function __construct()
	{
	}

	/**
	 * @static
	 * @return EventManager
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	protected function addEventHandlerInternal($fromModuleId, $eventType, $callback, $includeFile, $sort, $version)
	{
		$arEvent = array(
			"FROM_MODULE_ID" => $fromModuleId,
			"MESSAGE_ID" => $eventType,
			"CALLBACK" => $callback,
			"SORT" => $sort,
			"FULL_PATH" => $includeFile,
			"VERSION" => $version,
			"TO_NAME" => $this->formatEventName(array("CALLBACK" => $callback)),
		);

		$fromModuleId = strtoupper($fromModuleId);
		$eventType = strtoupper($eventType);

		if (!isset($this->handlers[$fromModuleId]) || !is_array($this->handlers[$fromModuleId]))
		{
			$this->handlers[$fromModuleId] = array();
		}

		$arEvents = &$this->handlers[$fromModuleId];

		if (!isset($arEvents[$eventType]) || !is_array($arEvents[$eventType]) || empty($arEvents[$eventType]))
		{
			$arEvents[$eventType] = array($arEvent);
			$iEventHandlerKey = 0;
		}
		else
		{
			$newEvents = array();
			$iEventHandlerKey = max(array_keys($arEvents[$eventType])) + 1;
			//$iEventHandlerKey = count($arEvents[$eventType]);
			foreach ($arEvents[$eventType] as $key => $value)
			{
				if ($value["SORT"] > $arEvent["SORT"])
				{
					$newEvents[$iEventHandlerKey] = $arEvent;
				}

				$newEvents[$key] = $value;
			}
			$newEvents[$iEventHandlerKey] = $arEvent;
			$arEvents[$eventType] = $newEvents;
		}

		return $iEventHandlerKey;
	}

	public function addEventHandler($fromModuleId, $eventType, $callback, $includeFile = false, $sort = 100)
	{
		return $this->addEventHandlerInternal($fromModuleId, $eventType, $callback, $includeFile, $sort, 2);
	}

	/**
	 * @param $fromModuleId
	 * @param $eventType
	 * @param $callback
	 * @param bool $includeFile
	 * @param int $sort
	 * @return int
	 */
	public function addEventHandlerCompatible($fromModuleId, $eventType, $callback, $includeFile = false, $sort = 100)
	{
		return $this->addEventHandlerInternal($fromModuleId, $eventType, $callback, $includeFile, $sort, 1);
	}

	public function removeEventHandler($fromModuleId, $eventType, $iEventHandlerKey)
	{
		$fromModuleId = strtoupper($fromModuleId);
		$eventType = strtoupper($eventType);

		if (is_array($this->handlers[$fromModuleId][$eventType]))
		{
			if (isset($this->handlers[$fromModuleId][$eventType][$iEventHandlerKey]))
			{
				unset($this->handlers[$fromModuleId][$eventType][$iEventHandlerKey]);
				return true;
			}
		}

		return false;
	}

	public function unRegisterEventHandler($fromModuleId, $eventType, $toModuleId, $toClass = "", $toMethod = "", $toPath = "", $toMethodArg = array())
	{
		$toMethodArg = ((!is_array($toMethodArg) || is_array($toMethodArg) && empty($toMethodArg)) ? "" : serialize($toMethodArg));

		$con = Application::getConnection();
		$sqlHelper = $con->getSqlHelper();

		$strSql =
			"DELETE FROM b_module_to_module ".
			"WHERE FROM_MODULE_ID='".$sqlHelper->forSql($fromModuleId)."'".
			"	AND MESSAGE_ID='".$sqlHelper->forSql($eventType)."' ".
			"	AND TO_MODULE_ID='".$sqlHelper->forSql($toModuleId)."' ".
			(($toClass != "") ? " AND TO_CLASS='".$sqlHelper->forSql($toClass)."' " : " AND (TO_CLASS='' OR TO_CLASS IS NULL) ").
			(($toMethod != "") ? " AND TO_METHOD='".$sqlHelper->forSql($toMethod)."'": " AND (TO_METHOD='' OR TO_METHOD IS NULL) ").
			(($toPath != "" && $toPath !== 1/*controller disconnect correction*/) ? " AND TO_PATH='".$sqlHelper->forSql($toPath)."'" : " AND (TO_PATH='' OR TO_PATH IS NULL) ").
			(($toMethodArg != "") ? " AND TO_METHOD_ARG='".$sqlHelper->forSql($toMethodArg)."'" : " AND (TO_METHOD_ARG='' OR TO_METHOD_ARG IS NULL) ");

		$con->queryExecute($strSql);

		$this->clearLoadedHandlers();
	}

	public function registerEventHandler($fromModuleId, $eventType, $toModuleId, $toClass = "", $toMethod = "", $sort = 100, $toPath = "", $toMethodArg = array())
	{
		$this->registerEventHandlerInternal($fromModuleId, $eventType, $toModuleId, $toClass, $toMethod, $sort, $toPath, $toMethodArg, 2);
	}

	public function registerEventHandlerCompatible($fromModuleId, $eventType, $toModuleId, $toClass = "", $toMethod = "", $sort = 100, $toPath = "", $toMethodArg = array())
	{
		$this->registerEventHandlerInternal($fromModuleId, $eventType, $toModuleId, $toClass, $toMethod, $sort, $toPath, $toMethodArg, 1);
	}

	protected function registerEventHandlerInternal($fromModuleId, $eventType, $toModuleId, $toClass, $toMethod, $sort, $toPath, $toMethodArg, $version)
	{
		$toMethodArg = ((!is_array($toMethodArg) || is_array($toMethodArg) && empty($toMethodArg)) ? "" : serialize($toMethodArg));

		$con = Application::getConnection();
		$sqlHelper = $con->getSqlHelper();

		$sort = intval($sort);
		$fromModuleId = $sqlHelper->forSql($fromModuleId);
		$eventType = $sqlHelper->forSql($eventType);
		$toModuleId = $sqlHelper->forSql($toModuleId);
		$toClass = $sqlHelper->forSql($toClass);
		$toMethod = $sqlHelper->forSql($toMethod);
		$toPath = $sqlHelper->forSql($toPath);
		$toMethodArg = $sqlHelper->forSql($toMethodArg);
		$version = intval($version);

		$res = $con->query(
			"SELECT 'x' ".
			"FROM b_module_to_module ".
			"WHERE FROM_MODULE_ID='".$fromModuleId."'".
			"	AND MESSAGE_ID='".$eventType."' ".
			"	AND TO_MODULE_ID='".$toModuleId."' ".
			"	AND TO_CLASS='".$toClass."' ".
			"	AND TO_METHOD='".$toMethod."'".
			(($toPath == "") ? " AND (TO_PATH='' OR TO_PATH IS NULL)" : " AND TO_PATH='".$toPath."'").
			(($toMethodArg == "") ? " AND (TO_METHOD_ARG='' OR TO_METHOD_ARG IS NULL)" : " AND TO_METHOD_ARG='".$toMethodArg."'")
		);

		if (!$res->fetch())
		{
			$con->queryExecute(
				"INSERT INTO b_module_to_module (SORT, FROM_MODULE_ID, MESSAGE_ID, TO_MODULE_ID, ".
				"	TO_CLASS, TO_METHOD, TO_PATH, TO_METHOD_ARG, VERSION) ".
				"VALUES (".$sort.", '".$fromModuleId."', '".$eventType."', '".$toModuleId."', ".
				"   '".$toClass."', '".$toMethod."', '".$toPath."', '".$toMethodArg."', ".$version.")"
			);

			$this->clearLoadedHandlers();
		}
	}

	protected function formatEventName($arEvent)
	{
		$strName = '';
		if (isset($arEvent["CALLBACK"]))
		{
			if (is_array($arEvent["CALLBACK"]))
			{
				$strName .= (is_object($arEvent["CALLBACK"][0]) ? get_class($arEvent["CALLBACK"][0]) : $arEvent["CALLBACK"][0]).'::'.$arEvent["CALLBACK"][1];
			}
			elseif (is_callable($arEvent["CALLBACK"]))
			{
				$strName .= "callable";
			}
			else
			{
				$strName .= $arEvent["CALLBACK"];
			}
		}
		else
		{
			$strName .= $arEvent["TO_CLASS"].'::'.$arEvent["TO_METHOD"];
		}
		if (isset($arEvent['TO_MODULE_ID']) && !empty($arEvent['TO_MODULE_ID']))
		{
			$strName .= ' ('.$arEvent['TO_MODULE_ID'].')';
		}
		return $strName;
	}

	protected function loadEventHandlers()
	{
		$cache = Application::getInstance()->getManagedCache();
		if ($cache->read(3600, self::$cacheKey))
		{
			$arEvents = $cache->get(self::$cacheKey);
		}
		else
		{
			$arEvents = array();

			$con = Application::getConnection();
			$rs = $con->query("
				SELECT FROM_MODULE_ID, MESSAGE_ID, SORT, TO_MODULE_ID, TO_PATH,
					TO_CLASS, TO_METHOD, TO_METHOD_ARG, VERSION
				FROM b_module_to_module m2m
					INNER JOIN b_module m ON (m2m.TO_MODULE_ID = m.ID)
				ORDER BY SORT
			");
			while ($ar = $rs->fetch())
			{
				$ar['TO_NAME'] = $this->formatEventName(
					array(
						"TO_MODULE_ID" => $ar["TO_MODULE_ID"],
						"TO_CLASS" => $ar["TO_CLASS"],
						"TO_METHOD" => $ar["TO_METHOD"]
					)
				);
				$ar["~FROM_MODULE_ID"] = strtoupper($ar["FROM_MODULE_ID"]);
				$ar["~MESSAGE_ID"] = strtoupper($ar["MESSAGE_ID"]);
				if (strlen($ar["TO_METHOD_ARG"]) > 0)
				{
					$ar["TO_METHOD_ARG"] = unserialize($ar["TO_METHOD_ARG"]);
				}
				else
				{
					$ar["TO_METHOD_ARG"] = array();
				}

				$arEvents[] = $ar;
			}

			$cache->set(self::$cacheKey, $arEvents);
		}

		if (!is_array($arEvents))
		{
			$arEvents = array();
		}

		$handlers = $this->handlers;
		$hasHandlers = !empty($this->handlers);

		// compatibility with former event manager
		foreach ($arEvents as $ar)
		{
			$this->handlers[$ar["~FROM_MODULE_ID"]][$ar["~MESSAGE_ID"]][] = array(
				"SORT" => $ar["SORT"],
				"TO_MODULE_ID" => $ar["TO_MODULE_ID"],
				"TO_PATH" => $ar["TO_PATH"],
				"TO_CLASS" => $ar["TO_CLASS"],
				"TO_METHOD" => $ar["TO_METHOD"],
				"TO_METHOD_ARG" => $ar["TO_METHOD_ARG"],
				"VERSION" => $ar["VERSION"],
				"TO_NAME" => $ar["TO_NAME"],
				"FROM_DB" => true,
			);
		}

		if ($hasHandlers)
		{
			// need to re-sort because of AddEventHandler() calls (before loadEventHandlers)
			$funcSort = create_function('$a, $b', 'if ($a["SORT"] == $b["SORT"]) return 0; return ($a["SORT"] < $b["SORT"]) ? -1 : 1;');
			foreach (array_keys($handlers) as $moduleId)
			{
				foreach (array_keys($handlers[$moduleId]) as $event)
				{
					uasort($this->handlers[$moduleId][$event], $funcSort);
				}
			}
		}

		$this->isHandlersLoaded = true;
	}

	protected function clearLoadedHandlers()
	{
		$managedCache = Application::getInstance()->getManagedCache();
		$managedCache->clean(self::$cacheKey);

		foreach($this->handlers as $module=>$types)
		{
			foreach($types as $type=>$events)
			{
				foreach($events as $i => $event)
				{
					if($event["FROM_DB"] == true)
					{
						unset($this->handlers[$module][$type][$i]);
					}
				}
			}
		}
		$this->isHandlersLoaded = false;
	}

	public function findEventHandlers($eventModuleId, $eventType, array $filter = null)
	{
		if (!$this->isHandlersLoaded)
		{
			$this->loadEventHandlers();
		}

		$eventModuleId = strtoupper($eventModuleId);
		$eventType = strtoupper($eventType);

		if (!isset($this->handlers[$eventModuleId]) || !isset($this->handlers[$eventModuleId][$eventType]))
		{
			return array();
		}

		$handlers = $this->handlers[$eventModuleId][$eventType];
		if (!is_array($handlers))
		{
			return array();
		}

		if (is_array($filter) && !empty($filter))
		{
			$handlersTmp = $handlers;
			$handlers = array();
			foreach ($handlersTmp as $handler)
			{
				if (in_array($handler["TO_MODULE_ID"], $filter))
				{
					$handlers[] = $handler;
				}
			}
		}

		return $handlers;
	}

	public function send(Event $event)
	{
		$handlers = $this->findEventHandlers($event->getModuleId(), $event->getEventType(), $event->getFilter());
		foreach ($handlers as $handler)
		{
			$this->sendToEventHandler($handler, $event);
		}
	}

	protected function sendToEventHandler(array $handler, Event $event)
	{
		try
		{
			$result = true;
			$includeResult = true;

			$event->addDebugInfo($handler);

			if (isset($handler["TO_MODULE_ID"]) && !empty($handler["TO_MODULE_ID"]) && ($handler["TO_MODULE_ID"] != 'main'))
			{
				$result = Loader::includeModule($handler["TO_MODULE_ID"]);
			}
			elseif (isset($handler["TO_PATH"]) && !empty($handler["TO_PATH"]))
			{
				$path = ltrim($handler["TO_PATH"], "/");
				if (($path = Loader::getLocal($path)) !== false)
				{
					$includeResult = include_once($path);
				}
			}
			elseif (isset($handler["FULL_PATH"]) && !empty($handler["FULL_PATH"]) && IO\File::isFileExists($handler["FULL_PATH"]))
			{
				$includeResult = include_once($handler["FULL_PATH"]);
			}

			$event->addDebugInfo($result);

			if ($result)
			{
				if (isset($handler["TO_METHOD_ARG"]) && is_array($handler["TO_METHOD_ARG"]) && !empty($handler["TO_METHOD_ARG"]))
				{
					$args = $handler["TO_METHOD_ARG"];
				}
				else
				{
					$args = array();
				}

				if ($handler["VERSION"] > 1)
				{
					$args[] = $event;
				}
				else
				{
					$args = array_merge($args, array_values($event->getParameters()));
				}

				$callback = null;
				if (isset($handler["CALLBACK"]))
				{
					$callback = $handler["CALLBACK"];
				}
				elseif (!empty($handler["TO_CLASS"]) && !empty($handler["TO_METHOD"]) && class_exists($handler["TO_CLASS"]))
				{
					$callback = array($handler["TO_CLASS"], $handler["TO_METHOD"]);
				}

				if ($callback != null)
				{
					$result = call_user_func_array($callback, $args);
				}
				else
				{
					$result = $includeResult;
				}

				if (($result != null) && !($result instanceof EventResult))
				{
					$result = new EventResult(EventResult::UNDEFINED, $result, $handler["TO_MODULE_ID"]);
				}

				$event->addDebugInfo($result);

				if ($result != null)
				{
					$event->addResult($result);
				}
			}
		}
		catch (\Exception $ex)
		{
			if ($event->isDebugOn())
			{
				$event->addException($ex);
			}
			else
			{
				throw $ex;
			}
		}
	}
}
