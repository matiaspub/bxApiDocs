<?
IncludeModuleLangFile(__FILE__);

/**
* Класс содержит статические методы-обертки для удобного использования API модуля бизнес-процессов вне этого модуля.
*/

/**
 * <p>Или можно использовать метод:</p> <pre class="syntax">string CBPDocument::StartWorkflow($workflowTemplateId, $documentId, $arParameters, &amp;$arErrors)</pre> который кроме того обработает исключения, собрав их в массив <b>$arErrors</b>, и вернет идентификатор бизнес-процесса.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/index.php
 * @author Bitrix
 */
class CBPDocument
{
public static 	public static function MigrateDocumentType($oldType, $newType)
	{
		$templateIds = array();
		$db = CBPWorkflowTemplateLoader::GetList(array(), array("DOCUMENT_TYPE" => $oldType), false, false, array("ID"));
		while ($ar = $db->Fetch())
			$templateIds[] = $ar["ID"];

		foreach ($templateIds as $id)
			CBPWorkflowTemplateLoader::Update($id, array("DOCUMENT_TYPE" => $newType));

		if (count($templateIds) > 0)
		{
			CBPHistoryService::MigrateDocumentType($oldType, $newType, $templateIds);
			CBPStateService::MigrateDocumentType($oldType, $newType, $templateIds);
		}
	}

	/**
	* Метод возвращает массив всех рабочих потоков и их состояний для данного документа.
	* Если задан код документа, то метод возвращает массив всех запущенных для данного документа рабочих потоков (в том числе и завершенные), а так же шаблонов рабочих потоков, настроенных на автозапуск при изменении документа.
	* Если код документа не задан, то метод возвращает массив шаблонов рабочих потоков, настроенных на автозапуск при создании документа.
	* Массив имеет вид:
	*	array(
	*		код_рабочего_потока_или_шаблона => array(
	*			"ID" => код_рабочего_потока,
	*			"TEMPLATE_ID" => код_шаблона_рабочего_потока,
	*			"TEMPLATE_NAME" => название_шаблона_рабочего_потока,
	*			"TEMPLATE_DESCRIPTION" => описание_шаблона_рабочего_потока,
	*			"TEMPLATE_PARAMETERS" => массив_параметров_запуска_рабочего_потока_из_шаблона,
	*			"STATE_NAME" => текущее_состояние_рабочего_потока,
	*			"STATE_TITLE" => название_текущего_состояния_рабочего_потока,
	*			"STATE_MODIFIED" => дата_изменения_статуса_рабочего_потока,
	*			"STATE_PARAMETERS" => массив_событий_принимаемых_потоком_в_данном_состоянии,
	*			"STATE_PERMISSIONS" => права_на_операции_над_документом_в_данном_состоянии,
	*			"WORKFLOW_STATUS" => статус_рабочего_потока,
	*		),
	* 		. . .
	*	)
	* В зависимости от того, рабочий поток это или шаблон, часть полей может быть не установлена. Для шаблона рабочего потока типа конечных автоматов состоянием является его начальное состояние.
	* Массив параметров запуска рабочего потока из шаблона (TEMPLATE_PARAMETERS) имеет вид:
	*	array(
	*		"param1" => array(
	*			"Name" => "Параметр 1",
	*			"Description" => "",
	*			"Type" => "int",
	*			"Required" => true,
	*			"Multiple" => false,
	*			"Default" => 8,
	*			"Options" => null,
	*		),
	*		"param2" => array(
	*			"Name" => "Параметр 2",
	*			"Description" => "",
	*			"Type" => "select",
	*			"Required" => false,
	*			"Multiple" => true,
	*			"Default" => "v2",
	*			"Options" => array(
	*				"v1" => "V 1",
	*				"v2" => "V 2",
	*				"v3" => "V 3",
	*				. . .
	*			),
	*		),
	*		. . .
	*	)
	* Допустимые типы параметров: int, double, string, text, select, bool, date, datetime, user.
	* Массив событий, принимаемых потоком в данном состоянии (STATE_PARAMETERS) имеет вид:
	*	array(
	*		array(
	*			"NAME" => принимаемое_событие,
	*			"TITLE" => название_принимаемого_события,
	*			"PERMISSION" => массив_групп_пользователей_могущих_отправить_событие
	*		),
	*		. . .
	*	)
	* Права на операции над документом в данном состоянии (STATE_PERMISSIONS) имеют вид:
	*	array(
	*		операция => массив_групп_пользователей_могущих_осуществлять_операцию,
	*		. . .
	*	)
	*
	* @param array $documentType - тип документа в виде массива array(модуль, сущность, тип_документа_в_модуле)
	* @param mixed $documentId - код документа в виде массива array(модуль, сущность, код_документа_в_модуле). Если новый документ, то null.
	* @return array - массив рабочих потоков и шаблонов.
	*/
public static 	public static function GetDocumentStates($documentType, $documentId = null)
	{
		$arDocumentStates = array();

		if ($documentId != null)
			$arDocumentStates = CBPStateService::GetDocumentStates($documentId);

		$arTemplateStates = CBPWorkflowTemplateLoader::GetDocumentTypeStates(
			$documentType,
			(($documentId != null) ? CBPDocumentEventType::Edit : CBPDocumentEventType::Create)
		);

		return ($arDocumentStates + $arTemplateStates);
	}

	/**
	* Метод для данного документа возвращает состояние указанного рабочего потока. Результирующий массив аналогичен массиву метода GetDocumentStates.
	*
	* @param array $documentId - код документа в виде массива array(модуль, сущность, код_документа_в_модуле).
	* @param string $workflowId - код рабочего потока.
	* @return array - массив рабочего потока.
	*/
public static 	public static function GetDocumentState($documentId, $workflowId)
	{
		$arDocumentState = CBPStateService::GetDocumentStates($documentId, $workflowId);
		return $arDocumentState;
	}

public static 	public static function MergeDocuments($firstDocumentId, $secondDocumentId)
	{
		CBPStateService::MergeStates($firstDocumentId, $secondDocumentId);
		CBPHistoryService::MergeHistory($firstDocumentId, $secondDocumentId);
	}

	/**
	* Метод возвращает массив событий, которые указанный пользователь может отправить рабочему потоку в указанном состоянии.
	*
	* @param int $userId - код пользователя.
	* @param array $arGroups - массив групп пользователя.
	* @param array $arState - состояние рабочего потока.
	* @param bool $appendExtendedGroups.
	* @return array - массив событий вида array(array("NAME" => событие, "TITLE" => название_события), ...).
	*/
public static 	public static function GetAllowableEvents($userId, $arGroups, $arState, $appendExtendedGroups = false)
	{
		if (!is_array($arState))
			throw new Exception("arState");
		if (!is_array($arGroups))
			throw new Exception("arGroups");

		$arGroups = CBPHelper::convertToExtendedGroups($arGroups);
		if ($appendExtendedGroups)
		{
			$arGroups = array_merge($arGroups, CBPHelper::getUserExtendedGroups($userId));
		}
		if (!in_array("group_u".$userId, $arGroups))
			$arGroups[] = "group_u".$userId;

		$arResult = array();

		if (is_array($arState["STATE_PARAMETERS"]) && count($arState["STATE_PARAMETERS"]) > 0)
		{
			foreach ($arState["STATE_PARAMETERS"] as $arStateParameter)
			{
				$arStateParameter["PERMISSION"] = CBPHelper::convertToExtendedGroups($arStateParameter["PERMISSION"]);

				if (count($arStateParameter["PERMISSION"]) <= 0
					|| count(array_intersect($arGroups, $arStateParameter["PERMISSION"])) > 0)
				{
					$arResult[] = array(
						"NAME" => $arStateParameter["NAME"],
						"TITLE" => ((strlen($arStateParameter["TITLE"]) > 0) ? $arStateParameter["TITLE"] : $arStateParameter["NAME"]),
					);
				}
			}
		}

		return $arResult;
	}

public static 	public static function AddDocumentToHistory($parameterDocumentId, $name, $userId)
	{
		list($moduleId, $entity, $documentType) = CBPHelper::ParseDocumentId($parameterDocumentId);

		if (strlen($moduleId) > 0)
			CModule::IncludeModule($moduleId);

		if (!class_exists($entity))
			return false;

		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();

		$historyService = $runtime->GetService("HistoryService");
		$documentService = $runtime->GetService("DocumentService");

		$userId = intval($userId);

		$historyIndex = $historyService->AddHistory(
			array(
				"DOCUMENT_ID" => $parameterDocumentId,
				"NAME" => "New",
				"DOCUMENT" => null,
				"USER_ID" => $userId,
			)
		);

		$arDocument = $documentService->GetDocumentForHistory($parameterDocumentId, $historyIndex);
		if (!is_array($arDocument))
			return false;

		$historyService->UpdateHistory(
			$historyIndex,
			array(
				"NAME" => $name,
				"DOCUMENT" => $arDocument,
			)
		);

		return $historyIndex;
	}

	/**
	* Метод возвращает массив операций, которые указанный пользователь может совершить, если документ находится в указанных состояниях.
	* Если среди состояний нет ни одного рабочего потока типа конечных автоматов, то возвращается null.
	* Если пользователь не может выполнить ни одной операции, то возвращается array().
	* Иначе возвращается массив доступных для пользователя операций в виде array(операция, ...).
	*
	* @param int $userId - код пользователя.
	* @param array $arGroups - массив групп пользователя.
	* @param array $arStates - массив состояний рабочих потоков документа.
	* @param bool $appendExtendedGroups - use extended groups mode
	* @return mixed - массив доступных операций или null.
	*/
public static 	public static function GetAllowableOperations($userId, $arGroups, $arStates, $appendExtendedGroups = false)
	{
		if (!is_array($arStates))
			throw new Exception("arStates");
		if (!is_array($arGroups))
			throw new Exception("arGroups");

		$arGroups = CBPHelper::convertToExtendedGroups($arGroups);
		if ($appendExtendedGroups)
		{
			$arGroups = array_merge($arGroups, CBPHelper::getUserExtendedGroups($userId));
		}
		if (!in_array("group_u".$userId, $arGroups))
			$arGroups[] = "group_u".$userId;

		$result = null;

		foreach ($arStates as $arState)
		{
			if (is_array($arState["STATE_PERMISSIONS"]) && count($arState["STATE_PERMISSIONS"]) > 0)
			{
				if ($result == null)
					$result = array();

				foreach ($arState["STATE_PERMISSIONS"] as $operation => $arOperationGroups)
				{
					$arOperationGroups = CBPHelper::convertToExtendedGroups($arOperationGroups);

					if (count(array_intersect($arGroups, $arOperationGroups)) > 0)
						$result[] = strtolower($operation);


//					foreach ($arOperationGroups as $operationGroup)
//					{
//						if (is_array($operationGroup) && count($operationGroup) == 2
//							|| !is_array($operationGroup) && in_array($operationGroup, $arGroups))
//						{
//							$result[] = strtolower($operation);
//							break;
//						}
//					}
				}
			}
		}

		return $result;
	}

	/**
	* Метод проверяет, может ли указанный пользователь совершить указанную операцию, если документ находится в указанных состояниях.
	* Если среди состояний нет ни одного рабочего потока типа конечных автоматов, то возвращается true.
	* Если пользователь не может выполнить операцию, то возвращается false.
	* Иначе возвращается true.
	*
	* @param string $operation - операция.
	* @param int $userId - код пользователя.
	* @param array $arGroups - массив групп пользователя.
	* @param array $arStates - массив состояний рабочих потоков документа.
	* @return bool
	*/
public static 	public static function CanOperate($operation, $userId, $arGroups, $arStates)
	{
		$operation = trim($operation);
		if (strlen($operation) <= 0)
			throw new Exception("operation");

		$operations = self::GetAllowableOperations($userId, $arGroups, $arStates);
		if ($operations === null)
			return true;

		return in_array($operation, $operations);
	}

	/**
	* Метод запускает рабочий поток по коду его шаблона.
	*
	* @param int $workflowTemplateId - код шаблона рабочего потока.
	* @param array $documentId - код документа в виде массива array(модуль, сущность, код_документа_в_модуле).
	* @param array $arParameters - массив параметров запуска рабочего потока.
	* @param array $arErrors - массив ошибок, которые произошли при запуске рабочего потока в виде array(array("code" => код_ошибки, "message" => сообщение, "file" => путь_к_файлу), ...).
	* @return string - код запущенного рабочего потока.
	*/
	public static public static function StartWorkflow($workflowTemplateId, $documentId, $arParameters, &$arErrors)
	{
		$arErrors = array();

		$runtime = CBPRuntime::GetRuntime();

		if (!is_array($arParameters))
			$arParameters = array($arParameters);
		if (!array_key_exists("TargetUser", $arParameters))
			$arParameters["TargetUser"] = "user_".intval($GLOBALS["USER"]->GetID());

		try
		{
			$wi = $runtime->CreateWorkflow($workflowTemplateId, $documentId, $arParameters);
			$wi->Start();
			return $wi->GetInstanceId();
		}
		catch (Exception $e)
		{
			$arErrors[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]"
			);
		}

		return null;
	}

	/**
	* Метод запускает рабочие потоки, настроенные на автозапуск.
	*
	* @param array $documentType - код типа документа в виде массива array(модуль, сущность, код_типа_документа_в_модуле).
	* @param int $autoExecute - флаг CBPDocumentEventType типа автозапуска (1 = CBPDocumentEventType::Create, 2 = CBPDocumentEventType::Edit).
	* @param array $documentId - код документа в виде массива array(модуль, сущность, код_документа_в_модуле).
	* @param array $arParameters - массив параметров запуска рабочего потока.
	* @param array $arErrors - массив ошибок, которые произошли при запуске рабочего потока в виде array(array("code" => код_ошибки, "message" => сообщение, "file" => путь_к_файлу), ...).
	*/
public static 	public static function AutoStartWorkflows($documentType, $autoExecute, $documentId, $arParameters, &$arErrors)
	{
		$arErrors = array();

		$runtime = CBPRuntime::GetRuntime();

		if (!is_array($arParameters))
			$arParameters = array($arParameters);
		if (!array_key_exists("TargetUser", $arParameters))
			$arParameters["TargetUser"] =  "user_".intval($GLOBALS["USER"]->GetID());

		$arWT = CBPWorkflowTemplateLoader::SearchTemplatesByDocumentType($documentType, $autoExecute);
		foreach ($arWT as $wt)
		{
			try
			{
				$wi = $runtime->CreateWorkflow($wt["ID"], $documentId, $arParameters);
				$wi->Start();
			}
			catch (Exception $e)
			{
				$arErrors[] = array(
					"code" => $e->getCode(),
					"message" => $e->getMessage(),
					"file" => $e->getFile()." [".$e->getLine()."]"
				);
			}
		}
	}

	/**
	* Метод отправляет событие рабочему потоку.
	*
	* @param string $workflowId - код рабочего потока.
	* @param string $workflowEvent - событие.
	* @param array $arParameters - параметры события.
	* @param array $arErrors - массив ошибок, которые произошли при отправке события в виде array(array("code" => код_ошибки, "message" => сообщение, "file" => путь_к_файлу), ...).
	*/

	/**
	* <p>Метод отправляет внешнее событие рабочему потоку.</p> <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статичный.</div>
	*
	*
	* @param string $workflowId  Код рабочего потока </h
	*
	* @param string $workflowEvent  Название события </h
	*
	* @param array $arParameters  Параметры события </htm
	*
	* @param array &$arErrors  Массив ошибок, которые произошли при отправке события в виде
	* <pre>array(<br> array(<br> "code" =&gt; код_ошибки,<br> "message" =&gt; сообщение,<br> "file" =&gt;
	* путь_к_файлу<br> ),<br> ...<br>)<br></pre>
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>$arCurrentUserGroups = $GLOBALS["USER"]-&gt;GetUserGroupArray();<br>if ($GLOBALS["USER"]-&gt;GetID() == $createdBy)<br>   $arCurrentUserGroups[] = "Author";<br><br>$arErrorTmp = array();<br><br>CBPDocument::SendExternalEvent(<br>   $bizprocId,<br>   $bizprocEvent,<br>   array("Groups" =&gt; $arCurrentUserGroups, "User" =&gt; $GLOBALS["USER"]-&gt;GetID()),<br>   $arErrorTmp<br>);<br><br>if (count($arErrorsTmp) &gt; 0)<br>{<br>   foreach ($arErrorsTmp as $e)<br>      $fatalErrorMessage .= $e["message"].". ";<br>}<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPRuntime/SendExternalEvent.php">CBPRuntime::SendExternalEvent</a></li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/SendExternalEvent.php
	* @author Bitrix
	*/
	public static 	public static function SendExternalEvent($workflowId, $workflowEvent, $arParameters, &$arErrors)
	{
		$arErrors = array();

		try
		{
			CBPRuntime::SendExternalEvent($workflowId, $workflowEvent, $arParameters);
		}
		catch(Exception $e)
		{
			$arErrors[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]"
			);
		}
	}

	/**
	* Метод останавливает выполнение рабочего потока.
	*
	* @param string $workflowId - код рабочего потока.
	* @param array $documentId - код документа в виде массива array(модуль, сущность, код_документа_в_модуле).
	* @param array $arErrors - массив ошибок, которые произошли при остановке рабочего потока в виде array(array("code" => код_ошибки, "message" => сообщение, "file" => путь_к_файлу), ...).
	*/
public static 	public static function TerminateWorkflow($workflowId, $documentId, &$arErrors)
	{
		$arErrors = array();

		$runtime = CBPRuntime::GetRuntime();

		try
		{
			$workflow = $runtime->GetWorkflow($workflowId);
			if ($documentId)
			{
				$d = $workflow->GetDocumentId();
				if ($d[0] != $documentId[0] || $d[1] != $documentId[1] || $d[2] != $documentId[2])
					throw new Exception(GetMessage("BPCGDOC_INVALID_WF"));
			}
			$workflow->Terminate(null);
		}
		catch(Exception $e)
		{
			$arErrors[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]"
			);
		}
	}

public static 	public static function killWorkflow($workflowId, $terminate = true, $documentId = null)
	{
		$errors = array();
		if ($terminate)
			static::TerminateWorkflow($workflowId, $documentId, $errors);
		\Bitrix\Bizproc\WorkflowInstanceTable::delete($workflowId);
		CBPTaskService::DeleteByWorkflow($workflowId);
		CBPTrackingService::DeleteByWorkflow($workflowId);
		CBPStateService::DeleteWorkflow($workflowId);
		return $errors;
	}

	/**
	* Метод удаляет все связанные с документом записи.
	*
	* @param array $documentId - код документа в виде массива array(модуль, сущность, код_документа_в_модуле).
	* @param array $arErrors - массив ошибок, которые произошли при удалении в виде array(array("code" => код_ошибки, "message" => сообщение, "file" => путь_к_файлу), ...).
	*/
public static 	public static function OnDocumentDelete($documentId, &$arErrors)
	{
		$arErrors = array();

		$arStates = CBPStateService::GetDocumentStates($documentId);
		foreach ($arStates as $workflowId => $arState)
		{
			$terminate = strlen($arState["ID"]) > 0 && strlen($arState["WORKFLOW_STATUS"]) > 0;
			$errors = static::killWorkflow($workflowId, $terminate, $documentId);
			if ($errors)
				foreach ($errors as $e)
					$arErrors[] = $e;
		}

		CBPStateService::DeleteByDocument($documentId);
		CBPHistoryService::DeleteByDocument($documentId);
	}

	public static public static function PostTaskForm($arTask, $userId, $arRequest, &$arErrors, $userName = "")
	{
		$originalUserId = CBPTaskService::getOriginalTaskUserId($arTask['ID'], $userId);

		return CBPActivity::CallStaticMethod(
			$arTask["ACTIVITY"],
			"PostTaskForm",
			array(
				$arTask,
				$originalUserId,
				$arRequest,
				&$arErrors,
				$userName,
				$userId
			)
		);
	}

public static 	public static function ShowTaskForm($arTask, $userId, $userName = "", $arRequest = null)
	{
		return CBPActivity::CallStaticMethod(
			$arTask["ACTIVITY"],
			"ShowTaskForm",
			array(
				$arTask,
				$userId,
				$userName,
				$arRequest
			)
		);
	}

	/**
	 * @param int $userId Task User Id.
	 * @param int $status Task user status.
	 * @param int|array $ids Task ids.
	 * @param array $errors Error collection.
	 * @return bool
	 */
public static 	public static function setTasksUserStatus($userId, $status, $ids = array(), &$errors = array())
	{
		$filter = array(
			'USER_ID' => $userId,
			'STATUS' => CBPTaskStatus::Running,
			'USER_STATUS' => CBPTaskUserStatus::Waiting,
		);
		if ($ids)
		{
			$ids = array_filter(array_map('intval', (array)$ids));
			if ($ids)
				$filter['ID'] = $ids;
		}

		$iterator = CBPTaskService::GetList(array('ID'=>'ASC'),
			$filter,
			false,
			false,
			array('ID', 'NAME', 'WORKFLOW_ID', 'ACTIVITY', 'ACTIVITY_NAME', 'IS_INLINE'));
		while ($task = $iterator->fetch())
		{
			if ($task['IS_INLINE'] == 'Y')
			{
				$taskErrors = array();
				self::PostTaskForm($task, $userId, array('INLINE_USER_STATUS' => $status), $taskErrors);
				if (!empty($taskErrors))
					foreach ($taskErrors as $error)
						$errors[] = GetMessage('BPCGDOC_ERROR_ACTION', array('#NAME#' => $task['NAME'], '#ERROR#' => $error['message']));
			}
			else
				$errors[] = GetMessage('BPCGDOC_ERROR_TASK_IS_NOT_INLINE', array('#NAME#' => $task['NAME']));

		}
		return true;
	}

	/**
	 * @param int $fromUserId Task current user.
	 * @param int $toUserId Task target user.
	 * @param array|int $ids Task ids.
	 * @param array $errors Error collection.
	 * @return bool
	 */
public static 	public static function delegateTasks($fromUserId, $toUserId, $ids = array(), &$errors = array())
	{
		$filter = array(
			'USER_ID' => $fromUserId,
			'STATUS' => CBPTaskStatus::Running,
			'USER_STATUS' => CBPTaskUserStatus::Waiting
		);

		if ($ids)
		{
			$ids = array_filter(array_map('intval', (array)$ids));
			if ($ids)
				$filter['ID'] = $ids;
		}

		$iterator = CBPTaskService::GetList(array('ID'=>'ASC'), $filter, false, false, array('ID', 'NAME'));
		$found = false;
		while ($task = $iterator->fetch())
		{
			$found = true;
			if (!CBPTaskService::delegateTask($task['ID'], $fromUserId, $toUserId))
			{
				$errors[] = GetMessage('BPCGDOC_ERROR_DELEGATE', array('#NAME#' => $task['NAME']));
			}
		}
		return $found;
	}

public static 	public static function getTaskControls($arTask)
	{
		return CBPActivity::CallStaticMethod(
			$arTask["ACTIVITY"],
			"getTaskControls",
			array(
				$arTask
			)
		);
	}

	/**
	* Метод собирает и проверяет значения параметров запуска рабочего потока, заданных в форме метода StartWorkflowParametersShow.
	*
	* @param int $templateId - код шаблона кабочего потока.
	* @param array $arWorkflowParameters - массив параметров запуска рабочего потока.
	* @param array $arErrors - массив ошибок, которые произошли при выполнении в виде array(array("code" => код_ошибки, "message" => сообщение, "parameter" => название_параметра, "file" => путь_к_файлу), ...).
	* @return array - массив корректных значений параметров запуска рабочего потока в виде array(код_параметра => значение, ...)
	*/
	public static public static function StartWorkflowParametersValidate($templateId, $arWorkflowParameters, $documentType, &$arErrors)
	{
		$arErrors = array();

		$templateId = intval($templateId);
		if ($templateId <= 0)
		{
			$arErrors[] = array(
				"code" => "",
				"message" => GetMessage("BPCGDOC_EMPTY_WD_ID"),
			);
			return array();
		}

		if (!isset($arWorkflowParameters) || !is_array($arWorkflowParameters))
			$arWorkflowParameters = array();

		$arWorkflowParametersValues = array();

		$arRequest = $_REQUEST;
		foreach ($_FILES as $k => $v)
		{
			if (array_key_exists("name", $v))
			{
				if (is_array($v["name"]))
				{
					$ks = array_keys($v["name"]);
					for ($i = 0, $cnt = count($ks); $i < $cnt; $i++)
					{
						$ar = array();
						foreach ($v as $k1 => $v1)
							$ar[$k1] = $v1[$ks[$i]];

						$arRequest[$k][] = $ar;
					}
				}
				else
				{
					$arRequest[$k] = $v;
				}
			}
		}

		if (count($arWorkflowParameters) > 0)
		{
			$arErrorsTmp = array();
			$ar = array();

			foreach ($arWorkflowParameters as $parameterKey => $arParameter)
				$ar[$parameterKey] = $arRequest["bizproc".$templateId."_".$parameterKey];

			$arWorkflowParametersValues = CBPWorkflowTemplateLoader::CheckWorkflowParameters(
				$arWorkflowParameters,
				$ar,
				$documentType,
				$arErrors
			);
		}

		return $arWorkflowParametersValues;
	}

	/**
	* Метод выводит форму сбора значений параметров запуска рабочего потока. Проверяются и собираются значения методом StartWorkflowParametersValidate.
	*
	* @param int $templateId - код шаблона кабочего потока.
	* @param array $arWorkflowParameters - массив параметров запуска рабочего потока.
	* @param string $formName - название формы, в которой выводится форма сбора значений.
	* @param bool $bVarsFromForm - равно false в случае первого открытия формы, иначе - true.
	*/
public static 	public static function StartWorkflowParametersShow($templateId, $arWorkflowParameters, $formName, $bVarsFromForm, $documentType = null)
	{
		$templateId = intval($templateId);
		if ($templateId <= 0)
			return;

		if (!isset($arWorkflowParameters) || !is_array($arWorkflowParameters))
			$arWorkflowParameters = array();

		if (strlen($formName) <= 0)
			$formName = "start_workflow_form1";

		if ($documentType == null)
		{
			$dbResult = CBPWorkflowTemplateLoader::GetList(array(), array("ID" => $templateId), false, false, array("ID", "MODULE_ID", "ENTITY", "DOCUMENT_TYPE"));
			if ($arResult = $dbResult->Fetch())
				$documentType = $arResult["DOCUMENT_TYPE"];
		}

		$arParametersValues = array();
		$keys = array_keys($arWorkflowParameters);
		foreach ($keys as $key)
		{
			$v = ($bVarsFromForm ? $_REQUEST["bizproc".$templateId."_".$key] : $arWorkflowParameters[$key]["Default"]);
			if (!is_array($v))
			{
				$arParametersValues[$key] = $v;
			}
			else
			{
				$keys1 = array_keys($v);
				foreach ($keys1 as $key1)
					$arParametersValues[$key][$key1] = $v[$key1];
			}
		}

		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();
		$documentService = $runtime->GetService("DocumentService");

		foreach ($arWorkflowParameters as $parameterKey => $arParameter)
		{
			$parameterKeyExt = "bizproc".$templateId."_".$parameterKey;
			?><tr>
				<td align="right" width="40%" valign="top" class="field-name"><?= $arParameter["Required"] ? "<span class=\"required\">*</span> " : ""?><?= htmlspecialcharsbx($arParameter["Name"]) ?>:<?if (strlen($arParameter["Description"]) > 0) echo "<br /><small>".htmlspecialcharsbx($arParameter["Description"])."</small><br />";?></td>
				<td width="60%" valign="top"><?
			echo $documentService->GetFieldInputControl(
				$documentType,
				$arParameter,
				array("Form" => $formName, "Field" => $parameterKeyExt),
				$arParametersValues[$parameterKey],
				false,
				true
			);
			?></td></tr><?
		}
	}

public static 	public static function AddShowParameterInit($module, $type, $document_type, $entity = "")
	{
		CUtil::InitJSCore(array("window", "ajax"));
?>
<script src="/bitrix/js/bizproc/bizproc.js"></script>
<script>
	function BPAShowSelector(id, type, mode, arCurValues)
	{
		<?if($type=="only_users"):?>
		var def_mode = "only_users";
		<?else:?>
		var def_mode = "";
		<?endif?>

		if (!mode)
			mode = def_mode;

		<?/* if (type == 'xuser')
		{
			BX.Access.Init({other:{disabled:true}});
			BX.Access.ShowForm({
				callback: function (obSelected)
				{
					var result = [];
					for (var provider in obSelected)
					{
						if (obSelected.hasOwnProperty(provider))
						{
							for (var varId in obSelected[provider])
							{
								if (obSelected[provider].hasOwnProperty(varId))
								{
									result.push(BX.Access.GetProviderName(provider) + ' ' + obSelected[provider][varId].name + ' [' + varId + ']');
								}
							}
						}
					}
					if (result)
					{
						var el = BX(id), v = el.value;
						if (v)
							v += '; ';
						el.value = v + result.join('; ');
					}
				}
			});
		}
		else */?>
		if (mode == "only_users")
		{
			BX.WindowManager.setStartZIndex(1150);
			(new BX.CDialog({
				'content_url': '/bitrix/admin/<?=htmlspecialcharsbx($module)?>_bizproc_selector.php?mode=public&bxpublic=Y&lang=<?=LANGUAGE_ID?>&entity=<?=htmlspecialcharsbx($entity)?>',
				'content_post': {
					'document_type': '<?=CUtil::JSEscape($document_type)?>',
					'fieldName': id,
					'fieldType': type,
					'only_users': 'Y',
					'sessid': '<?= bitrix_sessid() ?>'
				},
				'height': 400,
				'width': 485
			})).Show();
		}
		else
		{
			if (typeof arWorkflowConstants === 'undefined')
				arWorkflowConstants = {};

			var workflowTemplateNameCur = workflowTemplateName;
			var workflowTemplateDescriptionCur = workflowTemplateDescription;
			var workflowTemplateAutostartCur = workflowTemplateAutostart;
			var arWorkflowParametersCur = arWorkflowParameters;
			var arWorkflowVariablesCur = arWorkflowVariables;
			var arWorkflowConstantsCur = arWorkflowConstants;
			var arWorkflowTemplateCur = Array(rootActivity.Serialize());

			if (arCurValues)
			{
				if (arCurValues['workflowTemplateName'])
					workflowTemplateNameCur = arCurValues['workflowTemplateName'];
				if (arCurValues['workflowTemplateDescription'])
					workflowTemplateDescriptionCur = arCurValues['workflowTemplateDescription'];
				if (arCurValues['workflowTemplateAutostart'])
					workflowTemplateAutostartCur = arCurValues['workflowTemplateAutostart'];
				if (arCurValues['arWorkflowParameters'])
					arWorkflowParametersCur = arCurValues['arWorkflowParameters'];
				if (arCurValues['arWorkflowVariables'])
					arWorkflowVariablesCur = arCurValues['arWorkflowVariables'];
				if (arCurValues['arWorkflowConstants'])
					arWorkflowConstantsCur = arCurValues['arWorkflowConstants'];
				if (arCurValues['arWorkflowTemplate'])
					arWorkflowTemplateCur = arCurValues['arWorkflowTemplate'];
			}

			var p = {
				'document_type': '<?=CUtil::JSEscape($document_type)?>',
				'fieldName': id,
				'fieldType': type,
				'selectorMode': mode,
				'workflowTemplateName': workflowTemplateNameCur,
				'workflowTemplateDescription': workflowTemplateDescriptionCur,
				'workflowTemplateAutostart': workflowTemplateAutostartCur,
				'sessid': '<?= bitrix_sessid() ?>'
			};

			JSToPHPHidd(p, arWorkflowParametersCur, 'arWorkflowParameters');
			JSToPHPHidd(p, arWorkflowVariablesCur, 'arWorkflowVariables');
			JSToPHPHidd(p, arWorkflowConstantsCur, 'arWorkflowConstants');
			JSToPHPHidd(p, arWorkflowTemplateCur, 'arWorkflowTemplate');

			(new BX.CDialog({
				'content_url': '/bitrix/admin/<?=htmlspecialcharsbx($module)?>_bizproc_selector.php?mode=public&bxpublic=Y&lang=<?=LANGUAGE_ID?>&entity=<?=htmlspecialcharsbx($entity)?>',
				'content_post': p,
				'height': 425,
				'width': 485
			})).Show();
		}
	}
</script>
<?
	}

public static 	public static function ShowParameterField($type, $name, $values, $arParams = Array())
	{
		/*
		"string" => "Строка",
		"text" => "Многострочный текст",
		"int" => "Целое число",
		"double" => "Число",
		"select" => "Список",
		"bool" => "Да/Нет",
		"date" => "Дата",
		"datetime" => "Дата/Время",
		"user" => "Пользователь",
		*/
		if(strlen($arParams['id'])>0)
			$id = $arParams['id'];
		else
			$id = md5(uniqid());

		if($type == "text")
		{
			$s = '<table cellpadding="0" cellspacing="0" border="0"><tr><td valign="top"><textarea ';
			$s .= 'rows="'.($arParams['rows']>0?intval($arParams['rows']):5).'" ';
			$s .= 'cols="'.($arParams['cols']>0?intval($arParams['cols']):50).'" ';
			$s .= 'name="'.htmlspecialcharsbx($name).'" ';
			$s .= 'id="'.htmlspecialcharsbx($id).'" ';
			$s .= '>'.htmlspecialcharsbx($values);
			$s .= '</textarea></td>';
			$s .= '<td valign="top" style="padding-left:4px"><input type="button" value="..." onclick="BPAShowSelector(\''.Cutil::JSEscape(htmlspecialcharsbx($id)).'\', \''.Cutil::JSEscape($type).'\');"></td></tr></table>';
		}
		elseif($type == "user")
		{
			$s = '<table cellpadding="0" cellspacing="0" border="0"><tr><td valign="top"><textarea onkeydown="if(event.keyCode==45)BPAShowSelector(\''.Cutil::JSEscape(htmlspecialcharsbx($id)).'\', \''.Cutil::JSEscape($type).'\');" ';
			$s .= 'rows="'.($arParams['rows']>0?intval($arParams['rows']):3).'" ';
			$s .= 'cols="'.($arParams['cols']>0?intval($arParams['cols']):45).'" ';
			$s .= 'name="'.htmlspecialcharsbx($name).'" ';
			$s .= 'id="'.htmlspecialcharsbx($id).'">'.htmlspecialcharsbx($values).'</textarea>';
			$s .= '</td><td valign="top" style="padding-left:4px"><input type="button" value="..." title="'.GetMessage("BIZPROC_AS_SEL_FIELD_BUTTON").' (Insert)'.'" onclick="BPAShowSelector(\''.Cutil::JSEscape(htmlspecialcharsbx($id)).'\', \''.Cutil::JSEscape($type).'\');"></td></tr></table>';
		}
		/* elseif($type == "xuser")
		{
			$s = '<table cellpadding="0" cellspacing="0" border="0"><tr><td valign="top"><textarea onkeydown="if(event.keyCode==45)BPAShowSelector(\''.Cutil::JSEscape(htmlspecialcharsbx($id)).'\', \''.Cutil::JSEscape($type).'\');" ';
			$s .= 'rows="'.($arParams['rows']>0?intval($arParams['rows']):3).'" ';
			$s .= 'cols="'.($arParams['cols']>0?intval($arParams['cols']):45).'" ';
			$s .= 'name="'.htmlspecialcharsbx($name).'" ';
			$s .= 'id="'.htmlspecialcharsbx($id).'">'.htmlspecialcharsbx($values).'</textarea>';
			$s .= '</td><td valign="top" style="padding-left:4px"><input type="button" value="..." title="'.GetMessage("BIZPROC_AS_SEL_FIELD_BUTTON").' (Insert)'.'" onclick="BPAShowSelector(\''.Cutil::JSEscape(htmlspecialcharsbx($id)).'\', \'user\');">
			<input type="button" value="[X]" title="'.GetMessage("BIZPROC_AS_SEL_FIELD_BUTTON").' (Insert)'.'" onclick="BPAShowSelector(\''.Cutil::JSEscape(htmlspecialcharsbx($id)).'\', \''.Cutil::JSEscape($type).'\');"></td></tr></table>';
		} */
		elseif($type == "bool")
		{
			$s = '<select name="'.htmlspecialcharsbx($name).'"><option value=""></option><option value="Y"'.($values=='Y'?' selected':'').'>'.GetMessage('MAIN_YES').'</option><option value="N"'.($values=='N'?' selected':'').'>'.GetMessage('MAIN_NO').'</option>';
			$s .= '<input type="text" ';
			$s .= 'size="20" ';
			$s .= 'name="'.htmlspecialcharsbx($name).'_X" ';
			$s .= 'id="'.htmlspecialcharsbx($id).'" ';
			$s .= 'value="'.($values=="Y" || $values=="N"?"":htmlspecialcharsbx($values)).'"> ';
			$s .= '<input type="button" value="..." onclick="BPAShowSelector(\''.Cutil::JSEscape(htmlspecialcharsbx($id)).'\', \''.Cutil::JSEscape($type).'\');">';
		}
		else
		{
			$s = '<input type="text" ';
			$s .= 'size="'.($arParams['size']>0?intval($arParams['size']):70).'" ';
			$s .= 'name="'.htmlspecialcharsbx($name).'" ';
			$s .= 'id="'.htmlspecialcharsbx($id).'" ';
			$s .= 'value="'.htmlspecialcharsbx($values).'"> ';
			$s .= '<input type="button" value="..." onclick="BPAShowSelector(\''.Cutil::JSEscape(htmlspecialcharsbx($id)).'\', \''.Cutil::JSEscape($type).'\');">';
		}

		return $s;
	}

public static 	public static function _ReplaceTaskURL($str, $documentType)
	{
		$chttp = new CHTTP();
		$baseHref = $chttp->URN2URI('');

		return str_replace(
			Array('#HTTP_HOST#', '#TASK_URL#', '#BASE_HREF#'),
			Array($_SERVER['HTTP_HOST'], ($documentType[0]=="iblock"?"/bitrix/admin/bizproc_task.php?workflow_id={=Workflow:id}":"/company/personal/bizproc/{=Workflow:id}/"), $baseHref),
			$str
			);
	}

public static 	public static function AddDefaultWorkflowTemplates($documentType, $additionalModuleId = null)
	{
		if (!empty($additionalModuleId))
		{
			$additionalModuleId = preg_replace("/[^a-z0-9_.]/i", "", $additionalModuleId);
			$arModule = array($additionalModuleId, $documentType[0], 'bizproc');
		}
		else
		{
			$arModule = array($documentType[0], 'bizproc');
		}

		$bIn = false;
		foreach ($arModule as $sModule)
		{
			if (file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$sModule.'/templates'))
			{
				if($handle = opendir($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$sModule.'/templates'))
				{
					$bIn = true;
					while(false !== ($file = readdir($handle)))
					{
						if(!is_file($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$sModule.'/templates/'.$file))
							continue;
						$arFields = false;
						include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$sModule.'/templates/'.$file);
						if(is_array($arFields))
						{
							/*
							 * If DOCUMENT_TYPE not defined, use current documentType
							 * Overwise check if DOCUMENT_TYPE equals to current documentType
							 */
							if (!array_key_exists("DOCUMENT_TYPE", $arFields))
								$arFields["DOCUMENT_TYPE"] = $documentType;
							elseif($arFields["DOCUMENT_TYPE"] != $documentType)
								continue;

							$arFields["SYSTEM_CODE"] = $file;
							if(is_object($GLOBALS['USER']))
								$arFields["USER_ID"] = $GLOBALS['USER']->GetID();
							$arFields["MODIFIER_USER"] = new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser);
							try
							{
								CBPWorkflowTemplateLoader::Add($arFields);
							}
							catch (Exception $e)
							{
							}
						}
					}
					closedir($handle);
				}
			}
			if ($bIn)
				break;
		}
	}

	/**
	* Метод возвращает массив шаблонов рабочих потоков для данного типа документа.
	* Возвращаемый массив имеет вид:
	*	array(
	*		array(
	*			"ID" => код_шаблона,
	*			"NAME" => название_шаблона,
	*			"DESCRIPTION" => описание_шаблона,
	*			"MODIFIED" => дата_изменения_шаблона,
	*			"USER_ID" => код_пользователя_изменившего_шаблон,
	*			"USER_NAME" => имя_пользователя_изменившего_шаблон,
	*			"AUTO_EXECUTE" => флаг_автовыполнения_CBPDocumentEventType,
	*			"AUTO_EXECUTE_TEXT" => текст_автовыполнения,
	*		),
	*		. . .
	*	)
	*
	* @param array $documentType - код типа документа в виде массива array(модуль, сущность, код_типа_документа_в_модуле).
	* @return array - массив шаблонов рабочих потоков.
	*/
public static 	public static function GetWorkflowTemplatesForDocumentType($documentType)
	{
		$arResult = array();

		$dbWorkflowTemplate = CBPWorkflowTemplateLoader::GetList(
			array(),
			array("DOCUMENT_TYPE" => $documentType, "ACTIVE"=>"Y"),
			false,
			false,
			array("ID", "NAME", "DESCRIPTION", "MODIFIED", "USER_ID", "AUTO_EXECUTE", "USER_NAME", "USER_LAST_NAME", "USER_LOGIN", "USER_SECOND_NAME")
		);
		while ($arWorkflowTemplate = $dbWorkflowTemplate->GetNext())
		{
			$arWorkflowTemplate["USER"] = "(".$arWorkflowTemplate["USER_LOGIN"].")".((strlen($arWorkflowTemplate["USER_NAME"]) > 0 || strlen($arWorkflowTemplate["USER_LAST_NAME"]) > 0) ? " " : "").CUser::FormatName(COption::GetOptionString("bizproc", "name_template", CSite::GetNameFormat(false), SITE_ID), array("NAME" => $arWorkflowTemplate["USER_NAME"], "LAST_NAME" => $arWorkflowTemplate["USER_LAST_NAME"], "SECOND_NAME" => $arWorkflowTemplate["USER_SECOND_NAME"]));

			$arWorkflowTemplate["AUTO_EXECUTE_TEXT"] = "";

			if ($arWorkflowTemplate["AUTO_EXECUTE"] == CBPDocumentEventType::None)
				$arWorkflowTemplate["AUTO_EXECUTE_TEXT"] .= GetMessage("BPCGDOC_AUTO_EXECUTE_NONE");

			if (($arWorkflowTemplate["AUTO_EXECUTE"] & CBPDocumentEventType::Create) != 0)
			{
				if (strlen($arWorkflowTemplate["AUTO_EXECUTE_TEXT"]) > 0)
					$arWorkflowTemplate["AUTO_EXECUTE_TEXT"] .= ", ";
				$arWorkflowTemplate["AUTO_EXECUTE_TEXT"] .= GetMessage("BPCGDOC_AUTO_EXECUTE_CREATE");
			}

			if (($arWorkflowTemplate["AUTO_EXECUTE"] & CBPDocumentEventType::Edit) != 0)
			{
				if (strlen($arWorkflowTemplate["AUTO_EXECUTE_TEXT"]) > 0)
					$arWorkflowTemplate["AUTO_EXECUTE_TEXT"] .= ", ";
				$arWorkflowTemplate["AUTO_EXECUTE_TEXT"] .= GetMessage("BPCGDOC_AUTO_EXECUTE_EDIT");
			}

			if (($arWorkflowTemplate["AUTO_EXECUTE"] & CBPDocumentEventType::Delete) != 0)
			{
				if (strlen($arWorkflowTemplate["AUTO_EXECUTE_TEXT"]) > 0)
					$arWorkflowTemplate["AUTO_EXECUTE_TEXT"] .= ", ";
				$arWorkflowTemplate["AUTO_EXECUTE_TEXT"] .= GetMessage("BPCGDOC_AUTO_EXECUTE_DELETE");
			}

			$arResult[] = $arWorkflowTemplate;
		}

		return $arResult;
	}

public static 	public static function GetNumberOfWorkflowTemplatesForDocumentType($documentType)
	{
		$n = CBPWorkflowTemplateLoader::GetList(
			array(),
			array("DOCUMENT_TYPE" => $documentType, "ACTIVE"=>"Y"),
			array()
		);
		return $n;
	}

	/**
	* Метод удаляет шаблон рабочего потока.
	*
	* @param int $id - код шаблона рабочего потока.
	* @param array $documentType - код типа документа в виде массива array(модуль, сущность, код_типа_документа_в_модуле).
	* @param array $arErrors - массив ошибок, которые произошли при выполнении в виде array(array("code" => код_ошибки, "message" => сообщение, "file" => путь_к_файлу), ...).
	*/

	/**
	* <p>Метод удаляет шаблон бизнес-процесса.</p> <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статичный.</div>
	*
	*
	* @param int $id  Код шаблона бизнес-процесса
	*
	* @param array $documentType  Код типа документа в виде массива <i>array(модуль, класс_документа,
	* код_типа_документа_в_модуле)</i>
	*
	* @param array &$arErrors  массив ошибок, которые произошли при выполнении в виде <pre class="syntax"
	* id="xmpE7D24E70">array(<br> array(<br> "code" =&gt; код_ошибки,<br> "message" =&gt; сообщение,<br>
	* "file" =&gt; путь_к_файлу<br> ),<br> ...<br>)</pre>
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>// Удалим шаблон бизнес-процесса с кодом 132 для инфоблока 18<br>CBPDocument::DeleteWorkflowTemplate(<br>  132,<br>  array("iblock", "CIBlockDocument", "iblock_18"),<br>  $arErrorTmp<br>);<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/DeleteWorkflowTemplate.php
	* @author Bitrix
	*/
	public static 	public static function DeleteWorkflowTemplate($id, $documentType, &$arErrors)
	{
		$arErrors = array();

		$dbTemplates = CBPWorkflowTemplateLoader::GetList(
			array(),
			array("ID" => $id, "DOCUMENT_TYPE" => $documentType),
			false,
			false,
			array("ID")
		);
		$arTemplate = $dbTemplates->Fetch();
		if (!$arTemplate)
		{
			$arErrors[] = array(
				"code" => 0,
				"message" => str_replace("#ID#", $id, GetMessage("BPCGDOC_INVALID_WF_ID")),
				"file" => ""
			);
			return;
		}

		try
		{
			CBPWorkflowTemplateLoader::Delete($id);
		}
		catch (Exception $e)
		{
			$arErrors[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]"
			);
		}
	}

	/**
	* Метод изменяет параметры шаблона рабочего потока.
	*
	* @param int $id - код шаблона рабочего потока.
	* @param array $documentType - код типа документа в виде массива array(модуль, сущность, код_типа_документа_в_модуле).
	* @param array $arFields - массив новых значений параметров шаблона рабочего потока.
	* @param array $arErrors - массив ошибок, которые произошли при выполнении в виде array(array("code" => код_ошибки, "message" => сообщение, "file" => путь_к_файлу), ...).
	*/

	/**
	* <p>Метод изменяет параметры шаблона бизнес-процесса.</p> <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статичный.</div>
	*
	*
	* @param int $id  Код шаблона бизнес-процесса
	*
	* @param array $documentType  Код типа документа в виде массива <i>array(модуль, класс_документа,
	* код_типа_документа_в_модуле)</i>
	*
	* @param array $arFields  Массив новых значений параметров шаблона бизнес-процесса
	*
	* @param array& $arErrors  массив ошибок, которые произошли при выполнении в виде <pre class="syntax"
	* id="xmpE7D24E70">array(<br> array(<br> "code" =&gt; код_ошибки,<br> "message" =&gt; сообщение,<br>
	* "file" =&gt; путь_к_файлу<br> ),<br> ...<br>)</pre>
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>// изменим флаг автозапуска шаблона бизнес-процесса с кодом 132 для инфоблока с кодом 32<br>CBPDocument::UpdateWorkflowTemplate(<br>	132,<br>	array("iblock", "CIBlockDocument", "iblock_32"),<br>	array(<br>		"AUTO_EXECUTE" =&gt; CBPDocumentEventType::Create<br>	),<br>	$arErrorsTmp<br>);<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/UpdateWorkflowTemplate.php
	* @author Bitrix
	*/
		public static function UpdateWorkflowTemplate($id, $documentType, $arFields, &$arErrors)
	{
		$arErrors = array();

		$dbTemplates = CBPWorkflowTemplateLoader::GetList(
			array(),
			array("ID" => $id, "DOCUMENT_TYPE" => $documentType),
			false,
			false,
			array("ID")
		);
		$arTemplate = $dbTemplates->Fetch();
		if (!$arTemplate)
		{
			$arErrors[] = array(
				"code" => 0,
				"message" => str_replace("#ID#", $id, GetMessage("BPCGDOC_INVALID_WF_ID")),
				"file" => ""
			);
			return;
		}

		try
		{
			CBPWorkflowTemplateLoader::Update($id, $arFields);
		}
		catch (Exception $e)
		{
			$arErrors[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]"
			);
		}
	}

	/**
	* Метод проверяет путем обращения к сущности документа, может ли пользователь совершать указанную операцию с документом.
	*
	* @param int $operation - операция из CBPCanUserOperateOperation
	* @param int $userId - код пользователя
	* @param array $parameterDocumentId - код документа в виде массива array(модуль, сущность, код_документа_в_модуле).
	* @param array $arParameters - ассициативный массив вспомогательных параметров. Используется для того, чтобы не рассчитывать заново те вычисляемые значения, которые уже известны на момент вызова метода. Стандартными являются ключи массива DocumentStates - массив состояний рабочих потоков данного документа, WorkflowId - код рабочего потока (если требуется проверить операцию на одном рабочем потоке). Массив может быть дополнен другими произвольными ключами.
	* @return bool
	*/
public static 	public static function CanUserOperateDocument($operation, $userId, $parameterDocumentId, $arParameters = array())
	{
		list($moduleId, $entity, $documentId) = CBPHelper::ParseDocumentId($parameterDocumentId);

		if (strlen($moduleId) > 0)
			CModule::IncludeModule($moduleId);

		if (class_exists($entity))
			return call_user_func_array(array($entity, "CanUserOperateDocument"), array($operation, $userId, $documentId, $arParameters));

		return false;
	}

	/**
	* Метод проверяет путем обращения к сущности типа документа, может ли пользователь совершать указанную операцию с документами данного типа.
	*
	* @param int $operation - операция из CBPCanUserOperateOperation
	* @param int $userId - код пользователя
	* @param array $parameterDocumentType - код типа документа в виде массива array(модуль, сущность, код_типа_документа_в_модуле).
	* @param array $arParameters - ассициативный массив вспомогательных параметров. Используется для того, чтобы не рассчитывать заново те вычисляемые значения, которые уже известны на момент вызова метода. Стандартными являются ключи массива DocumentStates - массив состояний рабочих потоков данного документа, WorkflowId - код рабочего потока (если требуется проверить операцию на одном рабочем потоке). Массив может быть дополнен другими произвольными ключами.
	* @return bool
	*/
public static 	public static function CanUserOperateDocumentType($operation, $userId, $parameterDocumentType, $arParameters = array())
	{
		list($moduleId, $entity, $documentType) = CBPHelper::ParseDocumentId($parameterDocumentType);

		if (strlen($moduleId) > 0)
			CModule::IncludeModule($moduleId);

		if (class_exists($entity))
			return call_user_func_array(array($entity, "CanUserOperateDocumentType"), array($operation, $userId, $documentType, $arParameters));

		return false;
	}

	/**
	* Метод по коду документа возвращает ссылку на страницу документа в административной части.
	*
	* @param array $parameterDocumentId - код документа в виде массива array(модуль, сущность, код_документа_в_модуле).
	* @return string - ссылка на страницу документа в административной части.
	*/
public static 	public static function GetDocumentAdminPage($parameterDocumentId)
	{
		list($moduleId, $entity, $documentId) = CBPHelper::ParseDocumentId($parameterDocumentId);

		if (strlen($moduleId) > 0)
			CModule::IncludeModule($moduleId);

		if (class_exists($entity))
			return call_user_func_array(array($entity, "GetDocumentAdminPage"), array($documentId));

		return "";
	}

	/**
	* Метод возвращает массив заданий для данного пользователя в данном рабочем потоке.
	* Возвращаемый массив имеет вид:
	*	array(
	*		array(
	*			"ID" => код_задания,
	*			"NAME" => название_задания,
	*			"DESCRIPTION" => описание_задания,
	*		),
	*		. . .
	*	)
	*
	* @param int $userId - код пользователя.
	* @param string $workflowId - код рабочего потока.
	* @return array - массив заданий.
	*/
public static 	public static function GetUserTasksForWorkflow($userId, $workflowId)
	{
		$userId = intval($userId);
		if ($userId <= 0)
			return array();

		$workflowId = trim($workflowId);
		if (strlen($workflowId) <= 0)
			return array();

		$arResult = array();

		$dbTask = CBPTaskService::GetList(
			array(),
			array("WORKFLOW_ID" => $workflowId, "USER_ID" => $userId, 'STATUS' => CBPTaskStatus::Running),
			false,
			false,
			array("ID", "WORKFLOW_ID", "NAME", "DESCRIPTION")
		);
		while ($arTask = $dbTask->GetNext())
			$arResult[] = $arTask;

		return $arResult;
	}

public static 	public static function PrepareFileForHistory($documentId, $fileId, $historyIndex)
	{
		return CBPHistoryService::PrepareFileForHistory($documentId, $fileId, $historyIndex);
	}

public static 	public static function IsAdmin()
	{
		global $APPLICATION;
		return ($APPLICATION->GetGroupRight("bizproc") >= "W");
	}

public static 	public static function GetDocumentFromHistory($historyId, &$arErrors)
	{
		$arErrors = array();

		try
		{
			$historyId = intval($historyId);
			if ($historyId <= 0)
				throw new CBPArgumentNullException("historyId");

			return CBPHistoryService::GetById($historyId);
		}
		catch (Exception $e)
		{
			$arErrors[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]"
			);
		}
	}

public static 	public static function GetAllowableUserGroups($parameterDocumentType)
	{
		list($moduleId, $entity, $documentType) = CBPHelper::ParseDocumentId($parameterDocumentType);

		if (strlen($moduleId) > 0)
			CModule::IncludeModule($moduleId);

		if (class_exists($entity))
		{
			$result = call_user_func_array(array($entity, "GetAllowableUserGroups"), array($documentType));
			$result1 = array();
			foreach ($result as $key => $value)
				$result1[strtolower($key)] = $value;
			return $result1;
		}

		return array();
	}

	/**
	 * Bizproc expression checker. Required for usage from external modules!
	 * Examples: {=Document:IBLOCK_ID}, {=Document:CREATED_BY>printable}, {=SequentialWorkflowActivity1:DocumentApprovers>user,printable}
	 * @param $value
	 * @return bool
	 */
public static 	public static function IsExpression($value)
	{
		//go to internal alias
		return CBPActivity::isExpression($value);
	}
}
?>
