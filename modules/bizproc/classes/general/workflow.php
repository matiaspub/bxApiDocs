<?
IncludeModuleLangFile(__FILE__);

/**
* Workflow instance.
*/

/**
 * <p>В коде действия объект-оболочка для бизнес-процесса, в который входит это действие, доступна через переменную-член workflow:</p> <pre class="syntax">$this-&gt;workflow-&gt;ExecuteActivity($activity);</pre>
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPWorkflow/index.php
 * @author Bitrix
 */
class CBPWorkflow
{
	private $instanceId = "";
	private $workflowTemplateId = 0;
	private $runtime = null;

	private $rootActivity = null;

	private $activitiesQueue = array();
	private $eventsQueue = array();

	private $activitiesNamesMap = array();

	/************************  PROPERTIES  *******************************/

	public function GetInstanceId()
	{
		return $this->instanceId;
	}

	
	/**
	* <p>Метод возвращает экземпляр исполняющей среды, в которой запущен бизнес-процесс.</p>
	*
	*
	* @return CBPRuntime <p>Возвращается объект типа CBPRuntime, представляющий собой экземпляр
	* исполняющей среды, в которой запущен бизнес-процесс.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPRuntime/index.php">CBPRuntime</a>  </li>
	* </ul<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPWorkflow/GetRuntime.php
	* @author Bitrix
	*/
	public function GetRuntime()
	{
		return $this->runtime;
	}

	
	/**
	* <p>Метод возвращает текущий статус выполнения бизнес-процесса.</p>
	*
	*
	* @return int <ul> <li> <b>CBPActivityExecutionStatus::Initialized</b> - бизнес-процесс создан, </li> <li>
	* <b>CBPActivityExecutionStatus::Executing</b> - бизнес-процесс выполняется, </li> <li>
	* <b>CBPActivityExecutionStatus::Canceling</b> - бизнес-процесс отменен, </li> <li>
	* <b>CBPActivityExecutionStatus::Closed</b> - бизнес-процесс завершен, </li> <li>
	* <b>CBPActivityExecutionStatus::Faulting</b> - бизнес-процесс остановлен по ошибке. </li>
	* </ul> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPWorkflow/GetExecutionStatus.php
	* @author Bitrix
	*/
	private function GetExecutionStatus()
	{
		return $this->rootActivity->executionStatus;
	}

	
	/**
	* <p>Метод возвращает результат выполнения бизнес-процесса.</p>
	*
	*
	* @return int <ul> <li> <b>CBPActivityExecutionResult::None</b> - результат выполнения
	* бизнес-процесса не установлен, </li> <li> <b>CBPActivityExecutionResult::Succeeded</b> -
	* бизнес-процесс завершен успешно, </li> <li> <b>CBPActivityExecutionResult::Canceled</b> -
	* бизнес-процесс отменен, </li> <li> <b>CBPActivityExecutionResult::Faulted</b> -
	* бизнес-процесс остановлен по ошибке, </li> <li>
	* <b>CBPActivityExecutionResult::Uninitialized</b> - бизнес-процесс не инициализирован.
	* </li> </ul> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>$runtime = CBPRuntime::GetRuntime();<br>try<br>{<br>   $workflow = $runtime-&gt;GetWorkflow($workflowId);<br>}<br>catch (Exception $e)<br>{<br>   //<br>}<br><br>$executionResult = $workflow-&gt;GetExecutionResult();<br>switch ($executionResult)<br>{<br>   case CBPActivityExecutionResult::None:<br>      echo "Нет";<br>      break;<br>   case CBPActivityExecutionResult::Succeeded:<br>      echo "Успешно";<br>      break;<br>   case CBPActivityExecutionResult::Canceled:<br>      echo "Отменено";<br>      break;<br>   case CBPActivityExecutionResult::Faulted:<br>      echo "Ошибка";<br>      break;<br>   case CBPActivityExecutionResult::Uninitialized:<br>      echo "Не инициализировано";<br>      break;<br>   default:<br>      echo "Не определено";<br>}<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPWorkflow/GetExecutionResult.php
	* @author Bitrix
	*/
	private function GetExecutionResult()
	{
		return $this->rootActivity->executionResult;
	}

	private function GetWorkflowStatus()
	{
		return $this->rootActivity->GetWorkflowStatus();
	}

	private function SetWorkflowStatus($newStatus)
	{
		$this->rootActivity->SetWorkflowStatus($newStatus);
	}

	public function GetService($name)
	{
		return $this->runtime->GetService($name);
	}

	public function GetDocumentId()
	{
		return $this->rootActivity->GetDocumentId();
	}

	/************************  CONSTRUCTORS  ****************************************************/

	/**
	* Public constructor initializes a new workflow instance with the specified ID.
	* 
	* @param mixed $instanceId - ID of the new workflow instance.
	* @param mixed $runtime - Runtime object.
	* @return CBPWorkflow
	*/
	
	/**
	* <p>Конструктор создает новый экземпляр класса <a href="http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPWorkflow/index.php">CBPWorkflow</a>.</p>
	*
	*
	* @param string $instanceId  Идентификатор бизнес-процесса
	*
	* @param CBPRuntime $runtime  Исполняющая среда </ht
	*
	* @return public 
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPRuntime/index.php">CBPRuntime</a> </li>
	* </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPWorkflow/constructor.php
	* @author Bitrix
	*/
	public function __construct($instanceId, CBPRuntime $runtime)
	{
		if (strlen($instanceId) <= 0)
			throw new Exception("instanceId");
		if (!$runtime)
			throw new Exception("runtime");

		$this->instanceId = $instanceId;
		$this->runtime = $runtime;
	}

	/**
	 * Remove workflow object from serialized data
	 * @return array
	 */
	static public function __sleep()
	{
		return array();
	}

	/************************  CREATE / LOAD WORKFLOW  ****************************************/

	public function Initialize(CBPActivity $rootActivity, $documentId, $workflowParameters = array(), $workflowVariablesTypes = array(), $workflowParametersTypes = array(), $workflowTemplateId = 0)
	{
		$this->rootActivity = $rootActivity;
		$rootActivity->SetWorkflow($this);
		if (method_exists($rootActivity, 'SetWorkflowTemplateId'))
			$rootActivity->SetWorkflowTemplateId($workflowTemplateId);

		$arDocumentId = CBPHelper::ParseDocumentId($documentId);

		$rootActivity->SetDocumentId($arDocumentId);

		$documentService = $this->GetService("DocumentService");
		$documentType = $documentService->GetDocumentType($arDocumentId);

		if ($documentType !== null)
		{
			$rootActivity->SetDocumentType($documentType);
			$rootActivity->SetFieldTypes($documentService->GetDocumentFieldTypes($documentType));
		}

		$rootActivity->SetProperties($workflowParameters);


		$rootActivity->SetVariablesTypes($workflowVariablesTypes);
		if (is_array($workflowVariablesTypes))
		{
			foreach ($workflowVariablesTypes as $k => $v)
				$rootActivity->SetVariable($k, $v["Default"]);
		}

		$rootActivity->SetPropertiesTypes($workflowParametersTypes);
	}

	public function Reload(CBPActivity $rootActivity)
	{
		$this->rootActivity = $rootActivity;
		$rootActivity->SetWorkflow($this);

		switch ($this->GetWorkflowStatus())
		{
			case CBPWorkflowStatus::Completed:
			case CBPWorkflowStatus::Terminated:
				throw new Exception("InvalidAttemptToLoad");
		}
	}

	public function OnRuntimeStopped()
	{
		$workflowStatus = $this->GetWorkflowStatus();

		if ($workflowStatus == CBPWorkflowStatus::Suspended)
			return;

		if ($this->rootActivity->executionStatus == CBPActivityExecutionStatus::Closed)
		{
			$this->SetWorkflowStatus(CBPWorkflowStatus::Completed);
		}
		else
		{
			$workflowStatus = $this->GetWorkflowStatus();
			if ($workflowStatus == CBPWorkflowStatus::Running)
				$this->SetWorkflowStatus(CBPWorkflowStatus::Suspended);
		}

		$persister = CBPWorkflowPersister::GetPersister();
		$persister->SaveWorkflow($this->rootActivity, true);
	}

	/************************  EXECUTE WORKFLOW  ************************************************/

	/**
	* Starts new workflow instance.
	* 
	*/
	public function Start()
	{
		if ($this->GetWorkflowStatus() != CBPWorkflowStatus::Created)
			throw new Exception("CanNotStartInstanceTwice");

		$this->SetWorkflowStatus(CBPWorkflowStatus::Running);

		try
		{
			$this->InitializeActivity($this->rootActivity);
			$this->ExecuteActivity($this->rootActivity);
			$this->RunQueue();
		}
		catch (Exception $e)
		{
			$this->Terminate($e);
			throw $e;
		}

		if ($this->rootActivity->executionStatus == CBPActivityExecutionStatus::Closed)
		{
			$this->SetWorkflowStatus(CBPWorkflowStatus::Completed);
		}
		else
		{
			$workflowStatus = $this->GetWorkflowStatus();
			if ($workflowStatus == CBPWorkflowStatus::Running)
				$this->SetWorkflowStatus(CBPWorkflowStatus::Suspended);
		}

		$persister = CBPWorkflowPersister::GetPersister();
		$persister->SaveWorkflow($this->rootActivity, true);
	}

	/**
	* Resume existing workflow.
	* 
	*/
	public function Resume()
	{
		if ($this->GetWorkflowStatus() != CBPWorkflowStatus::Suspended)
			throw new Exception("CanNotResumeInstance");

		$this->SetWorkflowStatus(CBPWorkflowStatus::Running);

		try
		{
			$this->RunQueue();
		}
		catch (Exception $e)
		{
			$this->Terminate($e);
			throw $e;
		}

		if ($this->rootActivity->executionStatus == CBPActivityExecutionStatus::Closed)
		{
			$this->SetWorkflowStatus(CBPWorkflowStatus::Completed);
		}
		else
		{
			$workflowStatus = $this->GetWorkflowStatus();
			if ($workflowStatus == CBPWorkflowStatus::Running)
				$this->SetWorkflowStatus(CBPWorkflowStatus::Suspended);
		}

		$persister = CBPWorkflowPersister::GetPersister();
		$persister->SaveWorkflow($this->rootActivity, true);
	}

//	public static function DeleteWorkflow($workflowId)
//	{
//		$workflowId = trim($workflowId);
//		if (strlen($workflowId) <= 0)
//			throw new Exception("workflowId");
//		
//	}

	/**********************  EXTERNAL EVENTS  **************************************************************/

	/**
	* Resume the workflow instance and transfer the specified event to it.
	* 
	* @param mixed $eventName - Event name.
	* @param mixed $arEventParameters - Event parameters.
	*/
	public function SendExternalEvent($eventName, $arEventParameters = array())
	{
		$this->AddEventToQueue($eventName, $arEventParameters);
		$this->Resume();
	}

	/***********************  SEARCH ACTIVITY BY NAME  ****************************************************/

	private function FillNameActivityMapInternal(CBPActivity $activity)
	{
		$this->activitiesNamesMap[$activity->GetName()] = $activity;

		if (is_a($activity, "CBPCompositeActivity"))
		{
			$arSubActivities = $activity->CollectNestedActivities();
			foreach ($arSubActivities as $subActivity)
				$this->FillNameActivityMapInternal($subActivity);
		}
	}

	private function FillNameActivityMap()
	{
		if (!is_array($this->activitiesNamesMap))
			$this->activitiesNamesMap = array();

		if (count($this->activitiesNamesMap) > 0)
			return;

		$this->FillNameActivityMapInternal($this->rootActivity);
	}

	/**
	* Returns activity by its name.
	* 
	* @param mixed $activityName - Activity name.
	* @return CBPActivity - Returns activity object or null if activity is not found.
	*/
	public function GetActivityByName($activityName)
	{
		if (strlen($activityName) <= 0)
			throw new Exception("activityName");

		$activity = null;

		$this->FillNameActivityMap();

		if (array_key_exists($activityName, $this->activitiesNamesMap))
			$activity = $this->activitiesNamesMap[$activityName];

		return $activity;
	}

	/************************  ACTIVITY EXECUTION  *************************************************/

	/**
	* Initializes the specified activity by calling its method Initialize.
	* 
	* @param CBPActivity $activity
	*/
	static public function InitializeActivity(CBPActivity $activity)
	{
		if ($activity == null)
			throw new CBPArgumentNullException("activity");

		if ($activity->executionStatus != CBPActivityExecutionStatus::Initialized)
			throw new Exception("InvalidInitializingState");

		$activity->Initialize();
	}

	/**
	* Plans specified activity for execution.
	* 
	* @param CBPActivity $activity - Activity object.
	* @param mixed $arEventParameters - Optional parameters.
	*/
	public function ExecuteActivity(CBPActivity $activity, $arEventParameters = array())
	{
		if ($activity == null)
			throw new Exception("activity");

		if ($activity->executionStatus != CBPActivityExecutionStatus::Initialized)
			throw new Exception("InvalidExecutionState");

		$activity->SetStatus(CBPActivityExecutionStatus::Executing, $arEventParameters);
		$this->AddItemToQueue(array($activity, CBPActivityExecutorOperationType::Execute));
	}

	/**
	* Close specified activity.
	* 
	* @param CBPActivity $activity - Activity object.
	* @param mixed $arEventParameters - Optional parameters.
	*/
	static public function CloseActivity(CBPActivity $activity, $arEventParameters = array())
	{
		switch ($activity->executionStatus)
		{
			case CBPActivityExecutionStatus::Executing:
				$activity->MarkCompleted($arEventParameters);
				return;

			case CBPActivityExecutionStatus::Canceling:
				$activity->MarkCanceled($arEventParameters);
				return;

			case CBPActivityExecutionStatus::Closed:
				return;

			case CBPActivityExecutionStatus::Faulting:
				$activity->MarkFaulted($arEventParameters);
				return;
		}

		throw new Exception("InvalidClosingState");
	}

	/**
	* Cancel specified activity.
	* 
	* @param CBPActivity $activity - Activity object.
	* @param mixed $arEventParameters - Optional parameters.
	*/
	public function CancelActivity(CBPActivity $activity, $arEventParameters = array())
	{
		if ($activity == null)
			throw new Exception("activity");

		if ($activity->executionStatus != CBPActivityExecutionStatus::Executing)
			throw new Exception("InvalidCancelingState");

		$activity->SetStatus(CBPActivityExecutionStatus::Canceling, $arEventParameters);
		$this->AddItemToQueue(array($activity, CBPActivityExecutorOperationType::Cancel));
	}

	public function FaultActivity(CBPActivity $activity, Exception $e, $arEventParameters = array())
	{
		if ($activity == null)
			throw new Exception("activity");

		if ($activity->executionStatus == CBPActivityExecutionStatus::Closed)
		{
			if ($activity->parent == null)
				$this->Terminate($e);
			else
				$this->FaultActivity($activity->parent, $e, $arEventParameters);
		}
		else
		{
			$activity->SetStatus(CBPActivityExecutionStatus::Faulting);
			$this->AddItemToQueue(array($activity, CBPActivityExecutorOperationType::HandleFault, $e));
		}
	}

	/************************  ACTIVITIES QUEUE  ***********************************************/

	private function AddItemToQueue($item)
	{
		array_push($this->activitiesQueue, $item);
	}

	private function RunQueue()
	{
		while (true)
		{
			$this->ProcessQueuedEvents();

			$item = array_shift($this->activitiesQueue);
			if ($item == null)
				return;

			try
			{
				$this->RunQueuedItem($item[0], $item[1], (count($item) > 2 ? $item[2] : null));
			}
			catch (Exception $e)
			{
				$this->FaultActivity($item[0], $e);

				if ($this->GetWorkflowStatus() == CBPWorkflowStatus::Terminated)
					return;
			}
		}
	}

	private function RunQueuedItem(CBPActivity $activity, $activityOperation, Exception $exception = null)
	{
		if ($activityOperation == CBPActivityExecutorOperationType::Execute)
		{
			if ($activity->executionStatus == CBPActivityExecutionStatus::Executing)
			{
				try
				{
					$trackingService = $this->GetService("TrackingService");
					$trackingService->Write($this->GetInstanceId(), CBPTrackingType::ExecuteActivity, $activity->GetName(), $activity->executionStatus, $activity->executionResult, ($activity->IsPropertyExists("Title") ? $activity->Title : ""), "");

					$newStatus = $activity->Execute();

					if ($newStatus == CBPActivityExecutionStatus::Closed)
						$this->CloseActivity($activity);
					elseif ($newStatus != CBPActivityExecutionStatus::Executing)
						throw new Exception("InvalidExecutionStatus");
				}
				catch (Exception $e)
				{
					throw $e;
				}
			}
		}
		elseif ($activityOperation == CBPActivityExecutorOperationType::Cancel)
		{
			if ($activity->executionStatus == CBPActivityExecutionStatus::Canceling)
			{
				try
				{
					$trackingService = $this->GetService("TrackingService");
					$trackingService->Write($this->GetInstanceId(), CBPTrackingType::CancelActivity, $activity->GetName(), $activity->executionStatus, $activity->executionResult, ($activity->IsPropertyExists("Title") ? $activity->Title : ""), "");

					$newStatus = $activity->Cancel();

					if ($newStatus == CBPActivityExecutionStatus::Closed)
						$this->CloseActivity($activity);
					elseif ($newStatus != CBPActivityExecutionStatus::Canceling)
						throw new Exception("InvalidExecutionStatus");
				}
				catch (Exception $e)
				{
					throw $e;
				}
			}
		}
		elseif ($activityOperation == CBPActivityExecutorOperationType::HandleFault)
		{
			if ($activity->executionStatus == CBPActivityExecutionStatus::Faulting)
			{
				try
				{
					$trackingService = $this->GetService("TrackingService");
					$trackingService->Write($this->GetInstanceId(), CBPTrackingType::FaultActivity, $activity->GetName(), $activity->executionStatus, $activity->executionResult, ($activity->IsPropertyExists("Title") ? $activity->Title : ""), ($exception != null ? "[".$exception->getCode()."] ".$exception->getMessage() : ""));

					$newStatus = $activity->HandleFault($exception);

					if ($newStatus == CBPActivityExecutionStatus::Closed)
						$this->CloseActivity($activity);
					elseif ($newStatus != CBPActivityExecutionStatus::Faulting)
						throw new Exception("InvalidExecutionStatus");
				}
				catch (Exception $e)
				{
					throw $e;
				}
			}
		}
	}

	public function Terminate(Exception $e = null)
	{
		$taskService = $this->GetService("TaskService");
		$taskService->DeleteAllWorkflowTasks($this->GetInstanceId());

		$this->SetWorkflowStatus(CBPWorkflowStatus::Terminated);

		$persister = CBPWorkflowPersister::GetPersister();
		$persister->SaveWorkflow($this->rootActivity, true);

		$stateService = $this->GetService("StateService");
		$stateService->SetState(
			$this->instanceId,
			array(
				"STATE" => "Terminated",
				"TITLE" => GetMessage("BPCGWF_TERMINATED"),
				"PARAMETERS" => array()
			),
			false//array()
		);

		if ($e != null)
		{
			$trackingService = $this->GetService("TrackingService");
			$trackingService->Write($this->instanceId, CBPTrackingType::FaultActivity, "none", CBPActivityExecutionStatus::Faulting, CBPActivityExecutionResult::Faulted, "Exception", "[".$e->getCode()."] ".$e->getMessage());
		}
	}

	/************************  EVENTS QUEUE  ********************************************************/

	private function AddEventToQueue($eventName, $arEventParameters = array())
	{
		array_push($this->eventsQueue, array($eventName, $arEventParameters));
	}

	private function ProcessQueuedEvents()
	{
		while (true)
		{
			$arEvent = array_shift($this->eventsQueue);
			if ($arEvent == null)
				return;

			$eventName = $arEvent[0];
			$arEventParameters = $arEvent[1];

			$this->ProcessQueuedEvent($eventName, $arEventParameters);
		}
	}

	private function ProcessQueuedEvent($eventName, $arEventParameters = array())
	{
		if (!array_key_exists($eventName, $this->rootActivity->arEventsMap))
			return;

		foreach ($this->rootActivity->arEventsMap[$eventName] as $eventHandler)
		{
			if (is_a($eventHandler, "IBPActivityExternalEventListener"))
				$eventHandler->OnExternalEvent($arEventParameters);
		}
	}

	/**
	* Add new event handler to the specified event.
	* 
	* @param mixed $eventName - Event name.
	* @param IBPActivityExternalEventListener $eventHandler - Event handler.
	*/
	public function AddEventHandler($eventName, IBPActivityExternalEventListener $eventHandler)
	{
		if (!is_array($this->rootActivity->arEventsMap))
			$this->rootActivity->arEventsMap = array();

		if (!array_key_exists($eventName, $this->rootActivity->arEventsMap))
			$this->rootActivity->arEventsMap[$eventName] = array();

		$this->rootActivity->arEventsMap[$eventName][] = $eventHandler;
	}

	/**
	* Remove the event handler from the specified event.
	* 
	* @param mixed $eventName - Event name.
	* @param IBPActivityExternalEventListener $eventHandler - Event handler.
	*/
	public function RemoveEventHandler($eventName, IBPActivityExternalEventListener $eventHandler)
	{
		if (!is_array($this->rootActivity->arEventsMap))
			$this->rootActivity->arEventsMap = array();

		if (!array_key_exists($eventName, $this->rootActivity->arEventsMap))
			$this->rootActivity->arEventsMap[$eventName] = array();

		$idx = array_search($eventHandler, $this->rootActivity->arEventsMap[$eventName], true);
		if ($idx !== false)
			unset($this->rootActivity->arEventsMap[$eventName][$idx]);

		if (count($this->rootActivity->arEventsMap[$eventName]) <= 0)
			unset($this->rootActivity->arEventsMap[$eventName]);
	}

	/*******************  UTILITIES  ***************************************************************/

	/**
	* Returns available events for current state of state machine workflow activity.
	* 
	*/
	public function GetAvailableStateEvents()
	{
		if (!is_a($this->rootActivity, "CBPStateMachineWorkflowActivity"))
			throw new Exception("NotAStateMachineWorkflow");

		return $this->rootActivity->GetAvailableStateEvents();
	}

}
?>