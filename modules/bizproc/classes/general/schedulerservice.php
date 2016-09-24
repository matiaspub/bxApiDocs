<?
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/classes/general/runtimeservice.php");

class CBPSchedulerService
	extends CBPRuntimeService
{
	/**
	 * @param bool $withType Return as array [value, type].
	 * @return int|array
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function getDelayMinLimit($withType = false)
	{
		$result = (int) \Bitrix\Main\Config\Option::get('bizproc', 'delay_min_limit', 0);
		if (!$withType)
			return $result;
		$type = 's';
		if ($result > 0)
		{
			if ($result % (3600 * 24) == 0)
			{
				$result = $result / (3600 * 24);
				$type = 'd';
			}
			elseif ($result % 3600 == 0)
			{
				$result = $result / 3600;
				$type = 'h';
			}
			elseif ($result % 60 == 0)
			{
				$result = $result / 60;
				$type = 'm';
			}
		}
		return array($result, $type);
	}

	public static function setDelayMinLimit($limit, $type = 's')
	{
		$limit = (int)$limit;
		switch ($type)
		{
			case 'd':
				$limit *= 3600 * 24;
				break;
			case 'h':
				$limit *= 3600;
				break;
			case 'm':
				$limit *= 60;
				break;
			default:
				break;
		}
		\Bitrix\Main\Config\Option::set('bizproc', 'delay_min_limit', $limit);
	}

	static public function SubscribeOnTime($workflowId, $eventName, $expiresAt)
	{
		CTimeZone::Disable();

		$workflowId = preg_replace('#[^a-z0-9.]#i', '', $workflowId);
		$eventName = preg_replace('#[^a-z0-9._-]#i', '', $eventName);

		$minLimit = static::getDelayMinLimit(false);
		if ($minLimit > 0)
		{
			$minExpiresAt = time() + $minLimit;
			if ($minExpiresAt > $expiresAt)
				$expiresAt = $minExpiresAt;
		}

		$result = CAgent::AddAgent(
			"CBPSchedulerService::OnAgent('".$workflowId."', '".$eventName."', array('SchedulerService' => 'OnAgent'));",
			"bizproc",
			"N",
			10,
			"",
			"Y",
			date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $expiresAt)
		);
		CTimeZone::Enable();
		return $result;
	}

	static public function UnSubscribeOnTime($id)
	{
		CAgent::Delete($id);
	}

	public static function OnAgent($workflowId, $eventName, $arEventParameters = array())
	{
		try
		{
			CBPRuntime::SendExternalEvent($workflowId, $eventName, $arEventParameters);
		}
		catch (Exception $e)
		{
			
		}
	}

	static public function SubscribeOnEvent($workflowId, $eventHandlerName, $eventModule, $eventName, $entityId = null)
	{
		RegisterModuleDependences(
			$eventModule,
			$eventName,
			"bizproc",
			"CBPSchedulerService",
			"OnEvent",
			100,
			"",
			array($workflowId, $eventHandlerName, array('SchedulerService' => 'OnEvent', 'EntityId' => $entityId))
		);
	}

	static public function UnSubscribeOnEvent($workflowId, $eventHandlerName, $eventModule, $eventName, $entityId = null)
	{
		UnRegisterModuleDependences(
			$eventModule,
			$eventName,
			"bizproc",
			"CBPSchedulerService",
			"OnEvent",
			"",
			array($workflowId, $eventHandlerName, array('SchedulerService' => 'OnEvent', 'EntityId' => $entityId))
		);
	}

	public static function OnEvent($workflowId, $eventName, $arEventParameters = array())
	{
		$num = func_num_args();
		if ($num > 3)
		{
			for ($i = 3; $i < $num; $i++)
				$arEventParameters[] = func_get_arg($i);
		}

		if (is_array($arEventParameters["EntityId"]))
		{
			foreach ($arEventParameters["EntityId"] as $key => $value)
			{
				if (!isset($arEventParameters[0][$key]) || $arEventParameters[0][$key] != $value)
					return;
			}
		}
		elseif ($arEventParameters["EntityId"] != null && $arEventParameters["EntityId"] != $arEventParameters[0])
			return;

		try
		{
			CBPRuntime::SendExternalEvent($workflowId, $eventName, $arEventParameters);
		}
		catch (Exception $e)
		{

		}
	}
}
?>