<?php
namespace Bitrix\Main;

class EventManager
{
	/**
	 * @var EventManager
	 */
	private static $instance;

	private $handlers = array();
	private $isHandlersLoaded = false;

	private $mailHandlers = array();
	private $componentHandlers = array();

	static $cacheKey = "b_module_to_module";

	private function __construct()
	{
		$this->isHandlersLoaded = false;
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

	private function addEventHandlerInternal($fromModuleId, $eventType, $callback, $includeFile, $sort, $version)
	{
		$arEvent = array(
			"CALLBACK" => $callback,
			"SORT" => $sort,
			"INCLUDE_FILE" => $includeFile,
			"NAME" => $this->formatEventName(
				array(
					"CALLBACK" => $callback,
				)
			),
			"VERSION" => $version
		);

		$fromModuleId = strtoupper($fromModuleId);
		$eventType = strtoupper($eventType);

		if (!isset($this->handlers[$fromModuleId]) || !is_array($this->handlers[$fromModuleId]))
			$this->handlers[$fromModuleId] = array();

		if (!isset($this->handlers[$fromModuleId][$eventType]) || !is_array($this->handlers[$fromModuleId][$eventType]))
			$this->handlers[$fromModuleId][$eventType] = array();

		$iEventHandlerKey = count($this->handlers[$fromModuleId][$eventType]);

		$this->handlers[$fromModuleId][$eventType][$iEventHandlerKey] = $arEvent;

		uasort($this->handlers[$fromModuleId][$eventType], create_function('$a, $b', 'if ($a["SORT"] == $b["SORT"]) return 0; return ($a["SORT"] < $b["SORT"]) ? -1 : 1;'));

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
	 *
	 * @deprecated Deprecated for new kernel
	 */
	public function addEventHandlerOld($fromModuleId, $eventType, $callback, $includeFile = false, $sort = 100)
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

	public static function unRegisterEventHandler($fromModuleId, $eventType, $toModuleId, $toClass = "", $toMethod = "", $toPath = "", $toMethodArg = array())
	{
		$toMethodArg = ((!is_array($toMethodArg) || is_array($toMethodArg) && empty($toMethodArg)) ? "" : serialize($toMethodArg));

		$con = Application::getDbConnection();
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

		$managedCache = Application::getInstance()->getManagedCache();
		$managedCache->clean(self::$cacheKey);
	}

	public static function registerEventHandler($fromModuleId, $eventType, $toModuleId, $toClass = "", $toMethod = "", $sort = 100, $toPath = "", $toMethodArg = array())
	{
		$toMethodArg = ((!is_array($toMethodArg) || is_array($toMethodArg) && empty($toMethodArg)) ? "" : serialize($toMethodArg));

		$con = Application::getDbConnection();
		$sqlHelper = $con->getSqlHelper();

		$sort = intval($sort);
		$fromModuleId = $sqlHelper->forSql($fromModuleId);
		$eventType = $sqlHelper->forSql($eventType);
		$toModuleId = $sqlHelper->forSql($toModuleId);
		$toClass = $sqlHelper->forSql($toClass);
		$toMethod = $sqlHelper->forSql($toMethod);
		$toPath = $sqlHelper->forSql($toPath);
		$toMethodArg = $sqlHelper->forSql($toMethodArg);

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
				"   '".$toClass."', '".$toMethod."', '".$toPath."', '".$toMethodArg."', 2)"
			);

			$managedCache = Application::getInstance()->getManagedCache();
			$managedCache->clean(self::$cacheKey);
		}
	}

	private function formatEventName($arEvent)
	{
		$strName = '';
		if (array_key_exists("CALLBACK", $arEvent))
		{
			if (is_array($arEvent["CALLBACK"]))
				$strName .= (is_object($arEvent["CALLBACK"][0]) ? get_class($arEvent["CALLBACK"][0]) : $arEvent["CALLBACK"][0]).'::'.$arEvent["CALLBACK"][1];
			elseif (is_callable($arEvent["CALLBACK"]))
				$strName .= "callable";
			else
				$strName .= $arEvent["CALLBACK"];
		}
		else
		{
			$strName .= $arEvent["CLASS"].'::'.$arEvent["METHOD"];
		}
		if (isset($arEvent['MODULE_ID']) && !empty($arEvent['MODULE_ID']))
			$strName .= ' ('.$arEvent['MODULE_ID'].')';
		return $strName;
	}

	private function loadEventHandlers()
	{
		$cache = Application::getInstance()->getManagedCache();
		if ($cache->read(3600, self::$cacheKey))
		{
			$arEvents = $cache->get(self::$cacheKey);
		}
		else
		{
			$arEvents = array();

			$con = Application::getDbConnection();
			$rs = $con->query("
				SELECT *
				FROM b_module_to_module m2m
					INNER JOIN b_module m ON (m2m.TO_MODULE_ID = m.ID)
				ORDER BY SORT
			");
			while ($ar = $rs->fetch())
			{
				$ar['TO_NAME'] = $this->formatEventName(
					array(
						"MODULE_ID" => $ar["TO_MODULE_ID"],
						"CLASS" => $ar["TO_CLASS"],
						"METHOD" => $ar["TO_METHOD"]
					)
				);
				$ar["~FROM_MODULE_ID"] = strtoupper($ar["FROM_MODULE_ID"]);
				$ar["~MESSAGE_ID"] = strtoupper($ar["MESSAGE_ID"]);
				if (strlen($ar["TO_METHOD_ARG"]) > 0)
					$ar["TO_METHOD_ARG"] = unserialize($ar["TO_METHOD_ARG"]);
				else
					$ar["TO_METHOD_ARG"] = array();

				$arEvents[] = $ar;
			}

			$cache->set(self::$cacheKey, $arEvents);
		}

		if (!is_array($arEvents))
			$arEvents = array();

		$handlers = $this->handlers;

		foreach ($arEvents as $ar)
			$this->handlers[$ar["~FROM_MODULE_ID"]][$ar["~MESSAGE_ID"]][] = array(
				"SORT" => $ar["SORT"],
				"MODULE_ID" => $ar["TO_MODULE_ID"],
				"PATH" => $ar["TO_PATH"],
				"CLASS" => $ar["TO_CLASS"],
				"METHOD" => $ar["TO_METHOD"],
				"METHOD_ARG" => $ar["TO_METHOD_ARG"],
				"VERSION" => $ar["VERSION"],
				"NAME" => $ar["TO_NAME"],
			);

		// need to re-sort because of AddEventHandler() calls
		$funcSort = create_function('$a, $b', 'if ($a["SORT"] == $b["SORT"]) return 0; return ($a["SORT"] < $b["SORT"]) ? -1 : 1;');
		foreach (array_keys($handlers) as $moduleId)
			foreach (array_keys($handlers[$moduleId]) as $event)
				uasort($this->handlers[$moduleId][$event], $funcSort);

		$this->isHandlersLoaded = true;
	}

	private function loadMailEventHandlers()
	{
		$this->mailHandlers = array(
			/*"OnEvent" => array(
				"MODULE" => "main",
				"EVENT" => "SomeMailEvent",
			),*/
		);
	}

	private function loadComponentEventHandlers()
	{
		$this->componentHandlers = array(
			/*"OnEvent" => array(
				array(
					"COMPONENT" => "bitrix:lists.edit",
					"METHOD" => "SomeHandler",
				),
			),*/
		);
	}

	private function findEventHandlers($eventModuleId, $eventType, array $filter = null)
	{
		if (!$this->isHandlersLoaded)
			$this->loadEventHandlers();

		$eventModuleId = strtoupper($eventModuleId);
		$eventType = strtoupper($eventType);

		if (!isset($this->handlers[$eventModuleId]) || !isset($this->handlers[$eventModuleId][$eventType]))
			return array();

		$handlers = $this->handlers[$eventModuleId][$eventType];
		if (!is_array($handlers))
			return array();

		if (is_array($filter) && !empty($filter))
		{
			$handlersTmp = $handlers;
			$handlers = array();
			foreach ($handlersTmp as $handler)
			{
				if (in_array($handler["MODULE_ID"], $filter))
					$handlers[] = $handler;
			}
		}

		return $handlers;
	}

	private function findMailEventHandlers($eventModuleId, $eventType, array $filter = null)
	{
		if (empty($this->mailHandlers))
			$this->loadMailEventHandlers();

		$eventModuleId = strtoupper($eventModuleId);
		$eventType = strtoupper($eventType);

		if (!isset($this->mailHandlers[$eventModuleId]) || !isset($this->mailHandlers[$eventModuleId][$eventType]))
			return array();

		$handlers = $this->mailHandlers[$eventModuleId][$eventType];
		if (!is_array($handlers))
			return array();

		if (is_array($filter) && !empty($filter))
		{
			$handlersTmp = $handlers;
			$handlers = array();
			foreach ($handlersTmp as $handler)
			{
				if (in_array($handler, $filter))
					$handlers[] = $handler;
			}
		}

		return $handlers;
	}

	private function findComponentEventHandlers($eventModuleId, $eventType, array $filter = null)
	{
		if (empty($this->componentHandlers))
			$this->loadComponentEventHandlers();

		$eventModuleId = strtoupper($eventModuleId);
		$eventType = strtoupper($eventType);

		if (!isset($this->componentHandlers[$eventModuleId]) || !isset($this->componentHandlers[$eventModuleId][$eventType]))
			return array();

		$handlers = $this->componentHandlers[$eventModuleId][$eventType];
		if (!is_array($handlers))
			return array();

		if (is_array($filter) && !empty($filter))
		{
			$handlersTmp = $handlers;
			$handlers = array();
			foreach ($handlersTmp as $handler)
			{
				if (in_array($handler["COMPONENT"], $filter))
					$handlers[] = $handler;
			}
		}

		return $handlers;
	}

	public function send(Event $event)
	{
		$handlers = $this->findEventHandlers($event->getModuleId(), $event->getEventType(), $event->getFilter());
		foreach ($handlers as $handler)
			$this->sendToEventHandler($handler, $event);

		$handlers = $this->findMailEventHandlers($event->getModuleId(), $event->getEventType(), $event->getFilter());
		foreach ($handlers as $handler)
			$this->sendToMailEventHandler($handler, $event);

		$handlers = $this->findComponentEventHandlers($event->getModuleId(), $event->getEventType(), $event->getFilter());
		foreach ($handlers as $handler)
			$this->sendToComponentEventHandler($handler, $event);
	}

	private function sendToEventHandler(array $handler, Event $event)
	{
		try
		{
			$result = true;
			$event->addDebugInfo($handler);

			if (isset($handler["MODULE_ID"]) && !empty($handler["MODULE_ID"]))
			{
				$result = Loader::includeModule($handler["MODULE_ID"]);
			}
			elseif (isset($handler["PATH"]) && !empty($handler["PATH"]))
			{
				$path = ltrim($handler["PATH"], "/");
				if (($path = Loader::getLocal($path)) !== false)
					$result = include_once($path);
			}
			elseif (isset($handler["INCLUDE_FILE"]) && !empty($handler["INCLUDE_FILE"]) && \Bitrix\Main\IO\File::isFileExists($handler["INCLUDE_FILE"]))
			{
				$result = include_once($handler["INCLUDE_FILE"]);
			}

			$event->addDebugInfo($result);

			if (isset($handler["METHOD_ARG"]) && is_array($handler["METHOD_ARG"]) && count($handler["METHOD_ARG"]))
				$args = $handler["METHOD_ARG"];
			else
				$args = array();

			if ($handler["VERSION"] > 1)
				$args[] = $event;
			else
				$args = array_merge($args, array_values($event->getParameters()));

			$callback = null;
			if (array_key_exists("CALLBACK", $handler))
				$callback = $handler["CALLBACK"];
			elseif (!empty($handler["CLASS"]) && !empty($handler["METHOD"]) && class_exists($handler["CLASS"]))
				$callback = array($handler["CLASS"], $handler["METHOD"]);

			if ($callback != null)
				$result = call_user_func_array($callback, $args);

			if (($result != null) && !($result instanceof EventResult))
				$result = new EventResult(EventResult::UNDEFINED, $result, $handler["MODULE_ID"]);

			$event->addDebugInfo($result);

			if ($result != null)
				$event->addResult($result);
		}
		catch (\Exception $ex)
		{
			if ($event->isDebugOn())
				$event->addException($ex);
			else
				throw $ex;
		}
	}

	private function sendToMailEventHandler($handler, Event $event)
	{
		//$result = call_user_func_array(array($handler["CLASS"], $handler["METHOD"]), array($event));
		$event->addResult(new EventResult(EventResult::SUCCESS));
	}

	private function sendToComponentEventHandler($handler, Event $event)
	{
		//$result = call_user_func_array(array($handler["CLASS"], $handler["METHOD"]), array($event));
		$event->addResult(new EventResult(EventResult::SUCCESS));
	}
}
