<?php
class CCrmActivityConverter
{
	public static function IsCalEventConvertigRequired()
	{
		if(!(IsModuleInstalled('calendar') && CModule::IncludeModule('calendar')))
		{
			return false;
		}

		//TODO: Waiting for implementation of COUNT in CCalendarEvent::GetList
		$arEvents = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					'!UF_CRM_CAL_EVENT' => null,
					'DELETED' => 'N'
				),
				'getUserfields' => true
			)
		);

		foreach($arEvents as $arEvent)
		{
			$count = CCrmActivity::GetCount(
				array(
					'@TYPE_ID' =>  array(CCrmActivityType::Call, CCrmActivityType::Meeting),
					'=ASSOCIATED_ENTITY_ID' => $arEvent['ID']
				)
			);

			if($count === 0)
			{
				return true;
			}
		}
		return false;
	}
	public static function ConvertCalEvents($checkPerms = true, $regEvent = true)
	{
		if(!(IsModuleInstalled('calendar') && CModule::IncludeModule('calendar')))
		{
			return 0;
		}

		$arEvents = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					'!UF_CRM_CAL_EVENT' => null,
					'DELETED' => 'N'
				),
				'getUserfields' => true
			)
		);

		$total = 0;
		foreach($arEvents as $arEvent)
		{
			$eventID = $arEvent['ID'];
			$count = CCrmActivity::GetCount(
				array(
					'@TYPE_ID' =>  array(CCrmActivityType::Call, CCrmActivityType::Meeting),
					'=ASSOCIATED_ENTITY_ID' => $eventID
				)
			);

			if($count === 0
				&& CCrmActivity::CreateFromCalendarEvent($eventID, $arEvent, $checkPerms, $regEvent) > 0)
			{
				$total++;
			}
		}
		return $total;
	}
	public static function IsTaskConvertigRequired()
	{
		if(!(IsModuleInstalled('tasks') && CModule::IncludeModule('tasks')))
		{
			return false;
		}

		$dbTask = CTasks::getCount(
			array('!UF_CRM_TASK' => null),
			array('bIgnoreDbErrors' => true, 'bSkipExtraTables' => true)
		);

		$task = $dbTask ? $dbTask->Fetch() : null;
		$taskCount = is_array($task) && isset($task['CNT']) ? intval($task['CNT']) : 0;
		$activityCount = CCrmActivity::GetCount(
			array(
				'=TYPE_ID' =>  CCrmActivityType::Task,
				'>ASSOCIATED_ENTITY_ID' => 0
			)
		);

		return $taskCount !== $activityCount;
	}
	public static function ConvertTasks($checkPerms = true, $regEvent = true)
	{
		if(!(IsModuleInstalled('tasks') && CModule::IncludeModule('tasks')))
		{
			return 0;
		}

		$taskEntity = new CTasks();
		$dbRes = $taskEntity->GetList(
			array(),
			array('!UF_CRM_TASK' => null),
			array(
				'ID',
				'TITLE',
				'DESCRIPTION',
				'RESPONSIBLE_ID',
				'PRIORITY',
				'STATUS',
				'CREATED_DATE',
				'DATE_START',
				'CLOSED_DATE',
				'START_DATE_PLAN',
				'END_DATE_PLAN',
				'DEADLINE',
				'UF_CRM_TASK'
			),
			false
		);

		$total = 0;
		while($arTask = $dbRes->GetNext())
		{
			$taskID = intval($arTask['ID']);
			$count = CCrmActivity::GetCount(
				array(
					'=TYPE_ID' =>  CCrmActivityType::Task,
					'=ASSOCIATED_ENTITY_ID' => $taskID
				)
			);

			if($count === 0
				&& CCrmActivity::CreateFromTask($taskID, $arTask, $checkPerms, $regEvent) > 0)
			{
				$total++;
			}
		}
		return $total;
	}
}
