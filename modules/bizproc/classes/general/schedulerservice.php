<?
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/classes/general/runtimeservice.php");

class CBPSchedulerService
	extends CBPRuntimeService
{
	static public function SubscribeOnTime($workflowId, $eventName, $expiresAt)
	{
		CTimeZone::Disable();

		$workflowId = preg_replace('#[^a-z0-9.]#i', '', $workflowId);
		$eventName = preg_replace('#[^a-z0-9._-]#i', '', $eventName);

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

		if ($arEventParameters["EntityId"] != null && $arEventParameters["EntityId"] != $arEventParameters[0])
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