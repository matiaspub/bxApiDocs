<?
IncludeModuleLangFile(__FILE__);

/**
 * Bizproc API Helper for external usage.
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
	const PARAM_TAGRET_USER = 'TargetUser';
	const PARAM_MODIFIED_DOCUMENT_FIELDS = 'ModifiedDocumentField';

	public static function MigrateDocumentType($oldType, $newType)
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
	 * Method returns array of workflow templates and states for specified document.
	 * If document id is set method returns array of running and terminated workflow states and also templates which started on document edit action.
	 * If document id is not set method returns array of templates which started on document add.
	 * Return array example: array(
	 *		workflow_id_or_template_id => array(
	 *			"ID" => workflow_id,
	 *			"TEMPLATE_ID" => template_id,
	 *			"TEMPLATE_NAME" => template_name,
	 *			"TEMPLATE_DESCRIPTION" => template_description,
	 *			"TEMPLATE_PARAMETERS" => template_parameters,
	 *			"STATE_NAME" => current_state_name,
	 *			"STATE_TITLE" => current_state_title,
	 *			"STATE_MODIFIED" => state_modified_datetime,
	 *			"STATE_PARAMETERS" => state_parameters,
	 *			"STATE_PERMISSIONS" => state_permissions,
	 *			"WORKFLOW_STATUS" => workflow_status,
	 *		),
	 * 		. . .
	 *	)
	 * TEMPLATE_PARAMETERS example:
	 *	array(
	 *		"param1" => array(
	 *			"Name" => "Parameter 1",
	 *			"Description" => "",
	 *			"Type" => "int",
	 *			"Required" => true,
	 *			"Multiple" => false,
	 *			"Default" => 8,
	 *			"Options" => null,
	 *		),
	 *		"param2" => array(
	 *			"Name" => "Parameter 2",
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
	 * STATE_PARAMETERS example:
	 *	array(
	 *		array(
	 *			"NAME" => event_name,
	 *			"TITLE" => event_title,
	 *			"PERMISSION" => array('user_1')
	 *		),
	 *		. . .
	 *	)
	 * STATE_PERMISSIONS example:
	 *	array(
	 *		operation => users_array,
	 *		. . .
	 *	)
	 *
	 * @param array $documentType - Document type array(MODULE_ID, ENTITY, DOCUMENT_TYPE)
	 * @param null|array $documentId - Document id array(MODULE_ID, ENTITY, DOCUMENT_ID).
	 * @return array - Workflow states and templates.
	 */
	
	/**
	* <p>Метод возвращает массив всех рабочих потоков и их состояний для данного документа. Если задан код документа, то метод возвращает массив всех запущенных для данного документа рабочих потоков (в том числе и завершенные), а так же шаблонов рабочих потоков, настроенных на автозапуск при изменении документа. Если код документа не задан, то метод возвращает массив шаблонов рабочих потоков, настроенных на автозапуск при создании документа.</p>  <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статический.</div>
	*
	*
	* @param array $documentType  Тип документа в виде массива <i>array(модуль, класс_документа,
	* тип_документа_в_модуле)</i>
	*
	* @param array $documentId = null Код документа в виде массива <i>array(модуль, класс_документа,
	* код_документа_в_модуле)</i>. Если новый документ, то null
	*
	* @return array <p>Массив имеет вид: </p><pre bgcolor="#323232" style="padding:5px;">array(<br>   код_рабочего_потока_или_шаблона
	* =&gt; array(<br>      "ID" =&gt; код_рабочего_потока,<br>      "TEMPLATE_ID" =&gt;
	* код_шаблона_рабочего_потока,<br>      "TEMPLATE_NAME" =&gt;
	* название_шаблона_рабочего_потока,<br>      "TEMPLATE_DESCRIPTION" =&gt;
	* описание_шаблона_рабочего_потока,<br>      "TEMPLATE_PARAMETERS" =&gt;
	* массив_параметров_запуска_рабочего_потока_из_шаблона,<br>     
	* "STATE_NAME" =&gt; текущее_состояние_рабочего_потока,<br>      "STATE_TITLE" =&gt;
	* название_текущего_состояния_рабочего_потока,<br>      "STATE_MODIFIED" =&gt;
	* дата_изменения_статуса_рабочего_потока,<br>      "STATE_PARAMETERS" =&gt;
	* массив_событий_принимаемых_потоком_в_данном_состоянии,<br>     
	* "STATE_PERMISSIONS" =&gt;
	* права_на_операции_над_документом_в_данном_состоянии,<br>     
	* "WORKFLOW_STATUS" =&gt; статус_рабочего_потока,<br>   ),<br>   . .
	* .<br>)<br></pre><br><pre bgcolor="#323232" style="padding:5px;">array(<br>   "param1" =&gt; array(<br>      "Name" =&gt; "Параметр 1",<br>     
	* "Description" =&gt; "",<br>      "Type" =&gt; "int",<br>      "Required" =&gt; true,<br>      "Multiple" =&gt;
	* false,<br>      "Default" =&gt; 8,<br>      "Options" =&gt; null,<br>   ),<br>   "param2" =&gt; array(<br>      "Name"
	* =&gt; "Параметр 2",<br>      "Description" =&gt; "",<br>      "Type" =&gt; "select",<br>      "Required" =&gt;
	* false,<br>      "Multiple" =&gt; true,<br>      "Default" =&gt; "v2",<br>      "Options" =&gt; array(<br>         "v1"
	* =&gt; "V 1",<br>         "v2" =&gt; "V 2",<br>         "v3" =&gt; "V 3",<br>         . . .<br>      ),<br>   ),<br>   .
	* . .<br>)<br></pre><br><pre bgcolor="#323232" style="padding:5px;">array(<br>   array(<br>      "NAME" =&gt; принимаемое_событие,<br>     
	* "TITLE" =&gt; название_принимаемого_события,<br>      "PERMISSION" =&gt;
	* массив_групп_пользователей_могущих_отправить_событие<br>   ),<br>   . .
	* .<br>)<br></pre><pre bgcolor="#323232" style="padding:5px;">array(<br>   операция =&gt;
	* массив_групп_пользователей_могущих_осуществлять_операцию,<br>   . .
	* .<br>)<br></pre><p></p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$documentType = array("bizproc", "CBPVirtualDocument", "type_".$blockId);<br>$documentId = array("bizproc", "CBPVirtualDocument", $id);
	* 
	* $arDocumentStates = CBPDocument::GetDocumentStates($documentType, $documentId);
	* 
	* foreach ($arDocumentStates as $arDocumentState)<br>{<br>   $arDocumentStateTasks = CBPDocument::GetUserTasksForWorkflow($GLOBALS["USER"]-&gt;GetID(), $arDocumentState["ID"]);<br>   print_r($arDocumentStateTasks);<br>}<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/GetDocumentStates.php
	* @author Bitrix
	*/
	public static function GetDocumentStates($documentType, $documentId = null)
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
	 * Method returns workflow state for specified document.
	 *
	 * @param array $documentId - Document id array(MODULE_ID, ENTITY, DOCUMENT_ID).
	 * @param string $workflowId - Workflow id.
	 * @return array - Workflow state array.
	 */
	
	/**
	* <p>Метод для данного документа возвращает состояние указанного рабочего потока.</p>  <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статический.</div>
	*
	*
	* @param array $documentId  Идентификатор документа в виде массива <i>array(модуль,
	* класс_документа, код_документа_в_модуле)</i>
	*
	* @param string $workflowId  Идентификатор рабочего потока
	*
	* @return array <p>Результирующий массив аналогичен массиву метода <a
	* href="http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/GetDocumentStates.php">CBPDocument::GetDocumentStates</a>.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/GetDocumentStates.php">CBPDocument::GetDocumentStates</a>
	* </li>  </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/GetDocumentState.php
	* @author Bitrix
	*/
	public static function GetDocumentState($documentId, $workflowId)
	{
		$arDocumentState = CBPStateService::GetDocumentStates($documentId, $workflowId);
		return $arDocumentState;
	}

	public static function MergeDocuments($firstDocumentId, $secondDocumentId)
	{
		CBPStateService::MergeStates($firstDocumentId, $secondDocumentId);
		CBPHistoryService::MergeHistory($firstDocumentId, $secondDocumentId);
	}

	/**
	 * Method returns array of events available for specified user and specified state
	 *
	 * @param int $userId - User id.
	 * @param array $arGroups - User groups.
	 * @param array $arState - Workflow state.
	 * @param bool $appendExtendedGroups - Append extended groups.
	 * @return array - Events array array(array("NAME" => event_name, "TITLE" => event_title), ...).
	 * @throws Exception
	 */
	
	/**
	* <p>Метод возвращает массив событий, которые указанный пользователь может отправить рабочему потоку в указанном состоянии.</p>  <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статический.</div>
	*
	*
	* @param int $userId  Код пользователя
	*
	* @param array $arGroups  Массив групп пользователя
	*
	* @param array $arState  Состояние рабочего потока
	*
	* @return array <p>Возвращается массив событий вида </p><pre bgcolor="#323232" style="padding:5px;">array(<br>   array(<br>      "NAME" =&gt;
	* событие,<br>      "TITLE" =&gt; название_события<br>   ),<br>   ...<br>)<br></pre><p></p><a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$documentType = array("bizproc", "CBPVirtualDocument", "type_".$blockId);<br>$documentId = array("bizproc", "CBPVirtualDocument", $id);<br><br>$arCurrentUserGroups = $GLOBALS["USER"]-&gt;GetUserGroupArray();<br>if ($GLOBALS["USER"]-&gt;GetID() == $authorId)<br>   $arCurrentUserGroups[] = "Author";<br><br>$arDocumentStates = CBPDocument::GetDocumentStates($documentType, $documentId);<br><br>foreach ($arDocumentStates as $arDocumentState)<br>{<br>   $arDocumentStateEvents = CBPDocument::GetAllowableEvents($GLOBALS["USER"]-&gt;GetID(), $arCurrentUserGroups, $arDocumentState);<br>   print_r($arDocumentStateEvents);<br>}<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/GetAllowableEvents.php
	* @author Bitrix
	*/
	public static function GetAllowableEvents($userId, $arGroups, $arState, $appendExtendedGroups = false)
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

	
	/**
	* <p>Метод добавляет текущую версию документа в историю.</p>   <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статический.</div>
	*
	*
	* @param array $documentId  Идентификатор документа, заданный в виде массива <i>array(код_модуля,
	* класс_документа, идентификатор_документа_в_модуле)</i>
	*
	* @param string $name  Название записи в истории
	*
	* @param integer $userId  Идентификатор пользователя, от имени которого заносится запись
	*
	* @return integer <p>Возвращается идентификатор записи в истории или false в случае
	* ошибки.</p><br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/AddDocumentToHistory.php
	* @author Bitrix
	*/
	public static function AddDocumentToHistory($parameterDocumentId, $name, $userId)
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
	 * Method returns allowable operations for specified user in specified states.
	 * If specified states are not relevant to state machine returns null.
	 * If user has no access returns array().
	 * Else returns operations array(operation, ...).
	 *
	 * @param int $userId - User id.
	 * @param array $arGroups - User groups.
	 * @param array $arStates - Workflow states.
	 * @param bool $appendExtendedGroups - Append extended groups.
	 * @return array|null - Allowable operations.
	 * @throws Exception
	 */
	
	/**
	* <p>Метод возвращает массив операций, которые указанный пользователь может совершить, если документ находится в указанных состояниях.</p>  <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статический.</div>
	*
	*
	* @param integer $userId  Идентификатор пользователя
	*
	* @param array $arGroups  Массив групп пользователя
	*
	* @param array $arStates  Массив состояний рабочих потоков документа
	*
	* @return array <p>Если среди состояний нет ни одного рабочего потока типа
	* конечных автоматов, то возвращается null. Если пользователь не
	* может выполнить ни одной операции, то возвращается array(). Иначе
	* возвращается массив доступных для пользователя операций в виде
	* </p><pre bgcolor="#323232" style="padding:5px;">array(<br>   операция,<br>   ...<br>)<br></pre><br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/GetAllowableOperations.php
	* @author Bitrix
	*/
	public static function GetAllowableOperations($userId, $arGroups, $arStates, $appendExtendedGroups = false)
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
				}
			}
		}

		return $result;
	}

	/**
	 * Method check can operate user specified operation in specified state.
	 * If specified states are not relevant to state machine returns true.
	 * If user can`t do operation return false.
	 * Else returns true.
	 *
	 * @param string $operation - Operation.
	 * @param int $userId - User id.
	 * @param array $arGroups - User groups.
	 * @param array $arStates - Workflows states.
	 * @return bool
	 * @throws Exception
	 */
	
	/**
	* <p>Метод проверяет, может ли указанный пользователь совершить указанную операцию, если документ находится в указанных состояниях.</p>  <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статический.</div>
	*
	*
	* @param string $operation  Код операции
	*
	* @param integer $userId  Идентификатор пользователя
	*
	* @param array $arGroups  Массив групп пользователя
	*
	* @param array $arStates  Массив состояний рабочих потоков документа
	*
	* @return boolean <p>Если среди состояний нет ни одного рабочего потока типа
	* конечных автоматов, то возвращается true. Если пользователь не
	* может выполнить операцию, то возвращается false. Иначе возвращается
	* true.</p><br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/CanOperate.php
	* @author Bitrix
	*/
	public static function CanOperate($operation, $userId, $arGroups, $arStates)
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
	 * Method starts workflow.
	 *
	 * @param int $workflowTemplateId - Template id.
	 * @param array $documentId - Document id array(MODULE_ID, ENTITY, DOCUMENT_ID).
	 * @param array $arParameters - Workflow parameters.
	 * @param array $arErrors - Errors array(array("code" => error_code, "message" => message, "file" => file_path), ...).
	 * @param array|null $parentWorkflow - Parent workflow information.
	 * @return string - Workflow id.
	 */
	
	/**
	* <p>Метод запускает рабочий поток по коду его шаблона. Это рекомендуемый метод для запуска бизнес-процессов.</p>  <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статический.</div>
	*
	*
	* @param integer $workflowTemplateId  Код шаблона рабочего потока.
	*
	* @param array $documentId  Код документа в виде массива array(модуль, класс_документа,
	* код_документа_в_модуле).
	*
	* @param array $arParameters  Массив параметров запуска рабочего потока. <p></p> <div class="note">
	* <b>Примечание</b>: если процесс запускается из другого процесса
	* через API, и при этом передается значение множественного
	* параметра, то оно должно передаваться в виде массива.</div>
	*
	* @param array &$arErrors  Массив ошибок, которые произошли при запуске рабочего потока в
	* виде          <pre bgcolor="#323232" style="padding:5px;">array(<br>   array(<br>      "code" =&gt; код_ошибки,<br>      "message" =&gt;
	* сообщение,<br>      "file" =&gt; путь_к_файлу<br>   ),<br>   ...<br>).<br></pre>
	*
	* @return string <p>Возвращается идентификатор запущенного бизнес-процесса. В
	* случае ошибки заполняется массив ошибок.</p>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br><br>// Запустим бизнес-процесс по шаблону $workflowTemplateId с входящими параметрами $arWorkflowParameters<br>// для документа array("bizproc", "CBPVirtualDocument", $documentId)<br><br>$arErrorsTmp = array();<br><br>$wfId = CBPDocument::StartWorkflow(<br>   $workflowTemplateId,<br>   array("bizproc", "CBPVirtualDocument", $documentId),<br>   array_merge($arWorkflowParameters, array("TargetUser" =&gt; "user_".intval($GLOBALS["USER"]-&gt;GetID()))),<br>   $arErrorsTmp<br>);<br><br>if (count($arErrorsTmp) &gt; 0)<br>{<br>   foreach ($arErrorsTmp as $e)<br>      $errorMessage .= "[".$e["code"]."] ".$e["message"]."<br>";<br>}<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPRuntime/CreateWorkflow.php">CBPRuntime::CreateWorkflow</a>
	* </li>  </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/StartWorkflow.php
	* @author Bitrix
	*/
	public static function StartWorkflow($workflowTemplateId, $documentId, $arParameters, &$arErrors, $parentWorkflow = null)
	{
		$arErrors = array();

		$runtime = CBPRuntime::GetRuntime();

		if (!is_array($arParameters))
			$arParameters = array($arParameters);
		if (!isset($arParameters[static::PARAM_TAGRET_USER]))
			$arParameters[static::PARAM_TAGRET_USER] = is_object($GLOBALS["USER"]) ? "user_".intval($GLOBALS["USER"]->GetID()) : null;

		if (!isset($arParameters[static::PARAM_MODIFIED_DOCUMENT_FIELDS]))
			$arParameters[static::PARAM_MODIFIED_DOCUMENT_FIELDS] = false;

		try
		{
			$wi = $runtime->CreateWorkflow($workflowTemplateId, $documentId, $arParameters, $parentWorkflow);
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
	* Method auto starts workflow.
	*
	* @param array $documentType -  Document type array(MODULE_ID, ENTITY, DOCUMENT_TYPE).
	* @param int $autoExecute - CBPDocumentEventType (1 = CBPDocumentEventType::Create, 2 = CBPDocumentEventType::Edit).
	* @param array $documentId - Document id array(MODULE_ID, ENTITY, DOCUMENT_ID).
	* @param array $arParameters - Workflow parameters.
	* @param array $arErrors - Errors array(array("code" => error_code, "message" => message, "file" => file_path), ...).
	*/
	
	/**
	* <p>Метод запускает рабочие потоки, настроенные на автозапуск.</p>  <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статический.</div>
	*
	*
	* @param array $documentType  Код типа документа в виде массива <i>array(модуль, класс_документа,
	* код_типа_документа_в_модуле)</i>
	*
	* @param integer $autoExecute  Флаг <b>CBPDocumentEventType</b> типа автозапуска (1 = CBPDocumentEventType::Create, 2 =
	* CBPDocumentEventType::Edit)
	*
	* @param array $documentId  Код документа в виде массива <i>array(модуль, класс_документа,
	* код_документа_в_модуле)</i>
	*
	* @param array $arParameters  Массив параметров запуска рабочего потока
	*
	* @param array &$arErrors  Массив ошибок, которые произошли при запуске рабочего потока в
	* виде          <pre bgcolor="#323232" style="padding:5px;">array(<br>   array(<br>      "code" =&gt; код_ошибки,<br>      "message" =&gt;
	* сообщение,<br>      "file" =&gt; путь_к_файлу<br>   ),<br>   ...<br>)<br></pre>
	*
	* @return void 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/StartWorkflow.php">CBPDocument::StartWorkflow</a></li>
	*  </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/AutoStartWorkflows.php
	* @author Bitrix
	*/
	public static function AutoStartWorkflows($documentType, $autoExecute, $documentId, $arParameters, &$arErrors)
	{
		$arErrors = array();

		$runtime = CBPRuntime::GetRuntime();

		if (!is_array($arParameters))
			$arParameters = array($arParameters);

		if (!isset($arParameters[static::PARAM_TAGRET_USER]))
			$arParameters[static::PARAM_TAGRET_USER] = is_object($GLOBALS["USER"]) ? "user_".intval($GLOBALS["USER"]->GetID()) : null;

		if (!isset($arParameters[static::PARAM_MODIFIED_DOCUMENT_FIELDS]))
			$arParameters[static::PARAM_MODIFIED_DOCUMENT_FIELDS] = false;

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
	* Method sends external event to workflow.
	*
	* @param string $workflowId - Workflow id.
	* @param string $workflowEvent - Event name.
	* @param array $arParameters - Event parameters.
	* @param array $arErrors - Errors array(array("code" => error_code, "message" => message, "file" => file_path), ...).
	*/
	
	/**
	* <p>Метод отправляет внешнее событие рабочему потоку.</p>  <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статический.</div>
	*
	*
	* @param string $workflowId  Код рабочего потока
	*
	* @param string $workflowEvent  Название события
	*
	* @param array $arParameters  Параметры события
	*
	* @param array &$arErrors  Массив ошибок, которые произошли при отправке события в виде         
	* <pre bgcolor="#323232" style="padding:5px;">array(<br>   array(<br>      "code" =&gt; код_ошибки,<br>      "message" =&gt; сообщение,<br>    
	*  "file" =&gt; путь_к_файлу<br>   ),<br>   ...<br>)<br></pre>
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$arCurrentUserGroups = $GLOBALS["USER"]-&gt;GetUserGroupArray();<br>if ($GLOBALS["USER"]-&gt;GetID() == $createdBy)<br>   $arCurrentUserGroups[] = "Author";<br><br>$arErrorTmp = array();<br><br>CBPDocument::SendExternalEvent(<br>   $bizprocId,<br>   $bizprocEvent,<br>   array("Groups" =&gt; $arCurrentUserGroups, "User" =&gt; $GLOBALS["USER"]-&gt;GetID()),<br>   $arErrorTmp<br>);<br><br>if (count($arErrorsTmp) &gt; 0)<br>{<br>   foreach ($arErrorsTmp as $e)<br>      $fatalErrorMessage .= $e["message"].". ";<br>}<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPRuntime/SendExternalEvent.php">CBPRuntime::SendExternalEvent</a></li>
	*  </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/SendExternalEvent.php
	* @author Bitrix
	*/
	public static function SendExternalEvent($workflowId, $workflowEvent, $arParameters, &$arErrors)
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
	* Method terminates workflow.
	*
	* @param string $workflowId -  Workflow id.
	* @param array $documentId - Document type array(MODULE_ID, ENTITY, DOCUMENT_TYPE).
	* @param array $arErrors - Errors array(array("code" => error_code, "message" => message, "file" => file_path), ...).
	* @param string $stateTitle - State title (workflow status).
	*/
	
	/**
	* <p>Метод останавливает выполнение рабочего потока.</p>  <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статический.</div>
	*
	*
	* @param string $workflowId  Код рабочего потока
	*
	* @param array $documentId  Код документа в виде массива <i>array(модуль, класс_документа,
	* код_документа_в_модуле)</i>
	*
	* @param array &$arErrors  Массив ошибок, которые произошли при остановке рабочего потока в
	* виде          <pre bgcolor="#323232" style="padding:5px;">array(<br>   array(<br>      "code" =&gt; код_ошибки,<br>      "message" =&gt;
	* сообщение,<br>      "file" =&gt; путь_к_файлу<br>   ),<br>   ...<br>)<br></pre>
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$arState = CBPStateService::GetWorkflowState($stopWorkflowId);<br>if (count($arState) &gt; 0)<br>{<br>   CBPDocument::TerminateWorkflow(<br>      $stopWorkflowId,<br>      $arState["DOCUMENT_ID"],<br>      $arErrorsTmp<br>   );<br><br>   if (count($arErrorsTmp) &gt; 0)<br>   {<br>      foreach ($arErrorsTmp as $e)<br>         $errorMessage .= $e["message"].". ";<br>   }<br>}<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/TerminateWorkflow.php
	* @author Bitrix
	*/
	public static function TerminateWorkflow($workflowId, $documentId, &$arErrors, $stateTitle = '')
	{
		$arErrors = array();

		$runtime = CBPRuntime::GetRuntime();

		try
		{
			$workflow = $runtime->GetWorkflow($workflowId, true);
			if ($documentId)
			{
				$d = $workflow->GetDocumentId();
				if ($d[0] != $documentId[0] || $d[1] != $documentId[1] || $d[2] != $documentId[2])
					throw new Exception(GetMessage("BPCGDOC_INVALID_WF"));
			}
			$workflow->Terminate(null, $stateTitle);
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

	public static function killWorkflow($workflowId, $terminate = true, $documentId = null)
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
	 * Method removes all related document data.
	 * @param array $documentId - Document id array(MODULE_ID, ENTITY, DOCUMENT_ID).
	 * @param array $arErrors - Errors array(array("code" => error_code, "message" => message, "file" => file_path), ...).
	 */
	
	/**
	* <p>Метод удаляет все связанные с документом записи модуля бизнес-процессов.</p>
	*
	*
	* @param array $documentId  Код документа в виде массива <i>array(модуль, класс_документа,
	* код_документа_в_модуле)</i>
	*
	* @param array &$arErrors  Массив ошибок, которые произошли при удалении в виде         
	* <pre bgcolor="#323232" style="padding:5px;">array(<br>   array(<br>      "code" =&gt; код_ошибки,<br>      "message" =&gt; сообщение,<br>    
	*  "file" =&gt; путь_к_файлу<br>   ),<br>   ...<br>)</pre>
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$arState = CBPStateService::GetWorkflowState($deleteWorkflowId);<br>if (count($arState) &gt; 0)<br>{<br>   $arErrorsTmp = array();<br>   CBPDocument::OnDocumentDelete($arState["DOCUMENT_ID"], $arErrorsTmp);<br>   if (count($arErrorsTmp) &gt; 0)<br>   {<br>      foreach ($arErrorsTmp as $e)<br>         $errorMessage .= $e["message"].". ";<br>   }<br>}<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/OnDocumentDelete.php
	* @author Bitrix
	*/
	public static function OnDocumentDelete($documentId, &$arErrors)
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

	public static function PostTaskForm($arTask, $userId, $arRequest, &$arErrors, $userName = "")
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

	public static function ShowTaskForm($arTask, $userId, $userName = "", $arRequest = null)
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
	public static function setTasksUserStatus($userId, $status, $ids = array(), &$errors = array())
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
	public static function delegateTasks($fromUserId, $toUserId, $ids = array(), &$errors = array())
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

		$iterator = CBPTaskService::GetList(array('ID'=>'ASC'), $filter, false, false, array('ID', 'NAME', 'WORKFLOW_ID', 'ACTIVITY_NAME'));
		$found = false;
		$trackingService = null;
		while ($task = $iterator->fetch())
		{
			if (!$found)
			{
				$runtime = CBPRuntime::GetRuntime();
				$runtime->StartRuntime();
				/** @var CBPTrackingService $trackingService */
				$trackingService = $runtime->GetService('TrackingService');
			}
			$found = true;
			if (!CBPTaskService::delegateTask($task['ID'], $fromUserId, $toUserId))
			{
				$errors[] = GetMessage('BPCGDOC_ERROR_DELEGATE', array('#NAME#' => $task['NAME']));
			}
			else
			{
				$trackingService->Write(
					$task['WORKFLOW_ID'],
					CBPTrackingType::Custom,
					$task['ACTIVITY_NAME'],
					CBPActivityExecutionStatus::Executing,
					CBPActivityExecutionResult::None,
					GetMessage('BPCGDOC_DELEGATE_LOG_TITLE'),
					GetMessage('BPCGDOC_DELEGATE_LOG', array(
						'#NAME#' => $task['NAME'],
						'#FROM#' => '{=user:user_'.$fromUserId.'}',
						'#TO#' => '{=user:user_'.$toUserId.'}'
					))
				);
			}
		}
		return $found;
	}

	public static function getTaskControls($arTask)
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
	 * Method validates parameters values from StartWorkflowParametersShow.
	 *
	 * @param int $templateId - Template id.
	 * @param array $arWorkflowParameters - Workflow parameters.
	 * @param $documentType - Document type array(MODULE_ID, ENTITY, DOCUMENT_TYPE).
	 * @param array $arErrors - Errors array(array("code" => error_code, "message" => message, "file" => file_path), ...).
	 * @return array - Valid Parameters values.
	 */
	public static function StartWorkflowParametersValidate($templateId, $arWorkflowParameters, $documentType, &$arErrors)
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
	 * Method shows parameters form. Validates in StartWorkflowParametersValidate.
	 *
	 * @param int $templateId - Template id.
	 * @param array $arWorkflowParameters - Workflow parameters.
	 * @param string $formName - Form name.
	 * @param bool $bVarsFromForm - false on first form open, else - true.
	 * @param null|array $documentType Document type array(MODULE_ID, ENTITY, DOCUMENT_TYPE).
	 */
	public static function StartWorkflowParametersShow($templateId, $arWorkflowParameters, $formName, $bVarsFromForm, $documentType = null)
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

	public static function AddShowParameterInit($module, $type, $document_type, $entity = "", $document_id = '')
	{
		$GLOBALS["BP_AddShowParameterInit_".$module."_".$entity."_".$document_type] = 1;
		CUtil::InitJSCore(array("window", "ajax"));
?>
<script src="/bitrix/js/bizproc/bizproc.js"></script>
<script>
	function BPAShowSelector(id, type, mode, arCurValues, arDocumentType)
	{
		<?if($type=="only_users"):?>
		var def_mode = "only_users";
		<?else:?>
		var def_mode = "";
		<?endif?>

		if (!mode)
			mode = def_mode;
		var module = '<?=CUtil::JSEscape($module)?>';
		var entity = '<?=CUtil::JSEscape($entity)?>';
		var documentType = '<?=CUtil::JSEscape($document_type)?>';
		var documentId = '<?=CUtil::JSEscape($document_id)?>';

		/*if (arDocumentType && arDocumentType.length == 3)
		{
			module = arDocumentType[0];
			entity = arDocumentType[1];
			documentType = arDocumentType[2];
		}*/

		var loadAccessLib = (typeof BX.Access === 'undefined');

		if (mode == "only_users")
		{
			BX.WindowManager.setStartZIndex(1150);
			(new BX.CDialog({
				'content_url': '/bitrix/admin/'+module
					+'_bizproc_selector.php?mode=public&bxpublic=Y&lang=<?=LANGUAGE_ID?>&entity='
					+entity
					+(loadAccessLib? '&load_access_lib=Y':''),
				'content_post': {
					'document_type': documentType,
					'document_id': documentId,
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
				'document_type': documentType,
				'document_id': documentId,
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
				'content_url': '/bitrix/admin/'
					+module+'_bizproc_selector.php?mode=public&bxpublic=Y&lang=<?=LANGUAGE_ID?>&entity='
					+entity
					+(loadAccessLib? '&load_access_lib=Y':''),
				'content_post': p,
				'height': 425,
				'width': 485
			})).Show();
		}
	}
</script>
<?
	}

	public static function ShowParameterField($type, $name, $values, $arParams = Array())
	{
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
			$s .= '<td valign="top" style="padding-left:4px">';
			$s .= CBPHelper::renderControlSelectorButton($id, $type);
			$s .= '</td></tr></table>';
		}
		elseif($type == "user")
		{
			$s = '<table cellpadding="0" cellspacing="0" border="0"><tr><td valign="top"><textarea onkeydown="if(event.keyCode==45)BPAShowSelector(\''.Cutil::JSEscape(htmlspecialcharsbx($id)).'\', \''.Cutil::JSEscape($type).'\');" ';
			$s .= 'rows="'.($arParams['rows']>0?intval($arParams['rows']):3).'" ';
			$s .= 'cols="'.($arParams['cols']>0?intval($arParams['cols']):45).'" ';
			$s .= 'name="'.htmlspecialcharsbx($name).'" ';
			$s .= 'id="'.htmlspecialcharsbx($id).'">'.htmlspecialcharsbx($values).'</textarea>';
			$s .= '</td><td valign="top" style="padding-left:4px">';
			$s .= CBPHelper::renderControlSelectorButton($id, $type, array('title' => GetMessage("BIZPROC_AS_SEL_FIELD_BUTTON").' (Insert)'));
			$s .= '</td></tr></table>';
		}
		elseif($type == "bool")
		{
			$s = '<select name="'.htmlspecialcharsbx($name).'"><option value=""></option><option value="Y"'.($values=='Y'?' selected':'').'>'.GetMessage('MAIN_YES').'</option><option value="N"'.($values=='N'?' selected':'').'>'.GetMessage('MAIN_NO').'</option>';
			$s .= '<input type="text" ';
			$s .= 'size="20" ';
			$s .= 'name="'.htmlspecialcharsbx($name).'_X" ';
			$s .= 'id="'.htmlspecialcharsbx($id).'" ';
			$s .= 'value="'.($values=="Y" || $values=="N"?"":htmlspecialcharsbx($values)).'"> ';
			$s .= CBPHelper::renderControlSelectorButton($id, $type);
		}
		elseif ($type == 'datetime')
		{
			$s = '<span style="white-space:nowrap;"><input type="text" ';
			$s .= 'size="'.($arParams['size']>0?intval($arParams['size']):30).'" ';
			$s .= 'name="'.htmlspecialcharsbx($name).'" ';
			$s .= 'id="'.htmlspecialcharsbx($id).'" ';
			$s .= 'value="'.htmlspecialcharsbx($values).'">'.CAdminCalendar::Calendar(htmlspecialcharsbx($name), "", "", true).'</span> ';
			$s .= CBPHelper::renderControlSelectorButton($id, $type);
		}
		else
		{
			$s = '<input type="text" ';
			$s .= 'size="'.($arParams['size']>0?intval($arParams['size']):70).'" ';
			$s .= 'name="'.htmlspecialcharsbx($name).'" ';
			$s .= 'id="'.htmlspecialcharsbx($id).'" ';
			$s .= 'value="'.htmlspecialcharsbx($values).'"> ';
			$s .= CBPHelper::renderControlSelectorButton($id, $type);
		}

		return $s;
	}

	public static function _ReplaceTaskURL($str, $documentType)
	{
		$chttp = new CHTTP();
		$baseHref = $chttp->URN2URI('');

		return str_replace(
			Array('#HTTP_HOST#', '#TASK_URL#', '#BASE_HREF#'),
			Array($_SERVER['HTTP_HOST'], ($documentType[0]=="iblock"?"/bitrix/admin/bizproc_task.php?workflow_id={=Workflow:id}":"/company/personal/bizproc/{=Workflow:id}/"), $baseHref),
			$str
			);
	}

	public static function AddDefaultWorkflowTemplates($documentType, $additionalModuleId = null)
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
	 * Method returns array of workflow templates for specified document type.
	 * Return array example:
	 *	array(
	 *		array(
	 *			"ID" => workflow_id,
	 *			"NAME" => template_name,
	 *			"DESCRIPTION" => template_description,
	 *			"MODIFIED" => modified datetime,
	 *			"USER_ID" => modified by user id,
	 *			"USER_NAME" => modified by user name,
	 *			"AUTO_EXECUTE" => flag CBPDocumentEventType,
	 *			"AUTO_EXECUTE_TEXT" => auto_execute_text,
	 *		),
	 *		. . .
	 *	)
	 *
	 * @param array $documentType - Document type array(MODULE_ID, ENTITY, DOCUMENT_TYPE).
	 * @return array - Templates array.
	 */
	public static function GetWorkflowTemplatesForDocumentType($documentType)
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
			$arWorkflowTemplate["USER"] = "(".$arWorkflowTemplate["USER_LOGIN"].")".((strlen($arWorkflowTemplate["USER_NAME"]) > 0 || strlen($arWorkflowTemplate["USER_LAST_NAME"]) > 0) ? " " : "").CUser::FormatName(COption::GetOptionString("bizproc", "name_template", CSite::GetNameFormat(false), SITE_ID), array("NAME" => $arWorkflowTemplate["USER_NAME"], "LAST_NAME" => $arWorkflowTemplate["USER_LAST_NAME"], "SECOND_NAME" => $arWorkflowTemplate["USER_SECOND_NAME"]), false, false);

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

	public static function GetNumberOfWorkflowTemplatesForDocumentType($documentType)
	{
		$n = CBPWorkflowTemplateLoader::GetList(
			array(),
			array("DOCUMENT_TYPE" => $documentType, "ACTIVE"=>"Y"),
			array()
		);
		return $n;
	}

	/**
	 * Method deletes workflow template.
	 *
	 * @param int $id - Template id.
	 * @param array $documentType - Document type array(MODULE_ID, ENTITY, DOCUMENT_TYPE).
	 * @param array $arErrors - Errors array(array("code" => error_code, "message" => message, "file" => file_path), ...).
	 */
	
	/**
	* <p>Метод удаляет шаблон бизнес-процесса.</p>  <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статический.</div>
	*
	*
	* @param int $intid  Код шаблона бизнес-процесса
	*
	* @param array $documentType  Код типа документа в виде массива <i>array(модуль, класс_документа,
	* код_типа_документа_в_модуле)</i>
	*
	* @param array &$arErrors  массив ошибок, которые произошли при выполнении в виде          <pre
	* class="syntax" id="xmpE7D24E70">array(<br>  array(<br>    "code" =&gt; код_ошибки,<br>    "message" =&gt;
	* сообщение,<br>    "file" =&gt; путь_к_файлу<br>  ),<br>  ...<br>)</pre>
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>// Удалим шаблон бизнес-процесса с кодом 132 для инфоблока 18<br>CBPDocument::DeleteWorkflowTemplate(<br>  132,<br>  array("iblock", "CIBlockDocument", "iblock_18"),<br>  $arErrorTmp<br>);<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/DeleteWorkflowTemplate.php
	* @author Bitrix
	*/
	public static function DeleteWorkflowTemplate($id, $documentType, &$arErrors)
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
	 * Method updates workflow template.
	 *
	 * @param int $id - Template id.
	 * @param array $documentType - Document type array(MODULE_ID, ENTITY, DOCUMENT_TYPE).
	 * @param array $arFields - Data for update.
	 * @param array $arErrors - Errors array(array("code" => error_code, "message" => message, "file" => file_path), ...).
	 */
	
	/**
	* <p>Метод изменяет параметры шаблона бизнес-процесса.</p>  <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статичный.</div>
	*
	*
	* @param int $intid  Код шаблона бизнес-процесса
	*
	* @param array $documentType  Код типа документа в виде массива <i>array(модуль, класс_документа,
	* код_типа_документа_в_модуле)</i>
	*
	* @param array $arFields  Массив новых значений параметров шаблона бизнес-процесса
	*
	* @param array& $arErrors  массив ошибок, которые произошли при выполнении в виде          <pre
	* class="syntax" id="xmpE7D24E70">array(<br>  array(<br>    "code" =&gt; код_ошибки,<br>    "message" =&gt;
	* сообщение,<br>    "file" =&gt; путь_к_файлу<br>  ),<br>  ...<br>)</pre>
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
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
	 * Method checks can user operate specified document with specified operation.
	 *
	 * @param int $operation - operation CBPCanUserOperateOperation.
	 * @param int $userId - User id.
	 * @param array $parameterDocumentId - Document id array(MODULE_ID, ENTITY, DOCUMENT_ID).
	 * @param array $arParameters - Additional parameters.
	 * @return bool
	 */
	
	/**
	* <p>Метод проверяет путем обращения к сущности документа, может ли пользователь совершать указанную операцию с документом.</p>  <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статический.</div>
	*
	*
	* @param int $operation  Операция из <b>CBPCanUserOperateOperation</b>
	*
	* @param int $userId  Код пользователя
	*
	* @param array $documentId  Код документа в виде массива <i>array(модуль, класс_документа,
	* код_документа_в_модуле)</i>
	*
	* @param array $arParameters = array() Ассоциативный массив вспомогательных параметров. Используется
	* для того, чтобы не рассчитывать заново те вычисляемые значения,
	* которые уже известны на момент вызова метода. Стандартными
	* являются ключи массива <b>DocumentStates</b> - массив состояний рабочих
	* потоков данного документа, <b>UserGroups</b> - группы пользователя,
	* <b>WorkflowId</b> - код рабочего потока (если требуется проверить
	* операцию на одном рабочем потоке). Массив может быть дополнен
	* другими произвольными ключами
	*
	* @return bool <p>Возвращается true, если пользователь имеет право на выполнение
	* указанной операции. Иначе возвращается false.</p>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$bCanAccess = CBPDocument::CanUserOperateDocument(<br>	CBPCanUserOperateOperation::CreateWorkflow,<br>	$GLOBALS["USER"]-&gt;GetID(),<br>	$documentId,<br>	array("UserGroups" =&gt; $arUserGroups)<br>);<br>if (!$bCanAccess)<br>   die("Access denied");<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/CanUserOperateDocumentType.php">CBPDocument::CanUserOperateDocumentType</a></li>
	*  </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/CanUserOperateDocument.php
	* @author Bitrix
	*/
	public static function CanUserOperateDocument($operation, $userId, $parameterDocumentId, $arParameters = array())
	{
		list($moduleId, $entity, $documentId) = CBPHelper::ParseDocumentId($parameterDocumentId);

		if (strlen($moduleId) > 0)
			CModule::IncludeModule($moduleId);

		if (class_exists($entity))
			return call_user_func_array(array($entity, "CanUserOperateDocument"), array($operation, $userId, $documentId, $arParameters));

		return false;
	}

	/**
	 * Method checks can user operate specified document type with specified operation.
	 *
	 * @param int $operation - operation CBPCanUserOperateOperation.
	 * @param int $userId - User id.
	 * @param array $parameterDocumentType - Document type array(MODULE_ID, ENTITY, DOCUMENT_TYPE).
	 * @param array $arParameters - Additional parameters.
	 * @return bool
	 */
	
	/**
	* <p>Метод проверяет путем обращения к сущности типа документа, может ли пользователь совершать указанную операцию с документами данного типа.</p>  <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статический.</div>
	*
	*
	* @param int $operation  Операция из <b>CBPCanUserOperateOperation</b>
	*
	* @param int $userId  Код пользователя
	*
	* @param array $documentType  Код типа документа в виде массива <i>array(модуль, класс_документа,
	* код_типа_документа_в_модуле)</i>
	*
	* @param array $arParameters = array() Ассоциативный массив вспомогательных параметров. Используется
	* для того, чтобы не рассчитывать заново те вычисляемые значения,
	* которые уже известны на момент вызова метода. Стандартными
	* являются ключи массива <b>DocumentStates</b> - массив состояний рабочих
	* потоков данного документа, <b>WorkflowId</b> - код рабочего потока (если
	* требуется проверить операцию на одном рабочем потоке). Массив
	* может быть дополнен другими произвольными ключами.
	*
	* @return bool <p>Возвращается true, если пользователь имеет право на выполнение
	* указанной операции. Иначе возвращается false.</p>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$bCanAccess = CBPDocument::CanUserOperateDocumentType(<br>	CBPCanUserOperateOperation::CreateWorkflow,<br>	$GLOBALS["USER"]-&gt;GetID(),<br>	$documentType,<br>	array("UserGroups" =&gt; $arUserGroups)<br>);<br>if (!$bCanAccess)<br>   die("Access denied");<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/CanUserOperateDocument.php">CBPDocument::CanUserOperateDocument</a></li>
	*  </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/CanUserOperateDocumentType.php
	* @author Bitrix
	*/
	public static function CanUserOperateDocumentType($operation, $userId, $parameterDocumentType, $arParameters = array())
	{
		list($moduleId, $entity, $documentType) = CBPHelper::ParseDocumentId($parameterDocumentType);

		if (strlen($moduleId) > 0)
			CModule::IncludeModule($moduleId);

		if (class_exists($entity))
			return call_user_func_array(array($entity, "CanUserOperateDocumentType"), array($operation, $userId, $documentType, $arParameters));

		return false;
	}

	/**
	 * Get document admin page URL.
	 *
	 * @param array $parameterDocumentId - Document id array(MODULE_ID, ENTITY, DOCUMENT_ID).
	 * @return string - URL.
	 */
	public static function GetDocumentAdminPage($parameterDocumentId)
	{
		list($moduleId, $entity, $documentId) = CBPHelper::ParseDocumentId($parameterDocumentId);

		if (strlen($moduleId) > 0)
			CModule::IncludeModule($moduleId);

		if (class_exists($entity))
			return call_user_func_array(array($entity, "GetDocumentAdminPage"), array($documentId));

		return "";
	}

	/**
	 * @param array $parameterDocumentId Document Id.
	 * @return mixed|string
	 * @throws CBPArgumentNullException
	 */
	public static function getDocumentName($parameterDocumentId)
	{
		list($moduleId, $entity, $documentId) = CBPHelper::ParseDocumentId($parameterDocumentId);

		if (strlen($moduleId) > 0)
			CModule::IncludeModule($moduleId);

		if (class_exists($entity) && method_exists($entity, 'getDocumentName'))
			return call_user_func_array(array($entity, "getDocumentName"), array($documentId));

		return "";
	}

	/**
	 * Method returns task array for specified user and specified workflow state.
	 * Return array example:
	 *	array(
	 *		array(
	 *			"ID" => task_id,
	 *			"NAME" => task_name,
	 *			"DESCRIPTION" => task_description,
	 *		),
	 *		. . .
	 *	)
	 *
	 * @param int $userId - User id.
	 * @param string $workflowId - Workflow id.
	 * @return array - Tasks.
	 */
	
	/**
	* <p>Метод возвращает массив заданий для данного пользователя в данном рабочем потоке.</p>  <p></p> <div class="note"> <b>Примечание:</b> Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Метод статический.</div>
	*
	*
	* @param integer $userId  Код пользователя
	*
	* @param string $workflowId  Код бизнес-процесса
	*
	* @return array <p>Возвращаемый массив имеет вид: </p><pre bgcolor="#323232" style="padding:5px;">array(<br>   array(<br>      "ID" =&gt;
	* код_задания,<br>      "NAME" =&gt; название_задания,<br>      "DESCRIPTION" =&gt;
	* описание_задания,<br>   ),<br>   . . .<br>)<br></pre><p></p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$documentType = array("bizproc", "CBPVirtualDocument", "type_".$iblockId);<br>$documentId = array("bizproc", "CBPVirtualDocument", $id);<br><br>$arDocumentStates = CBPDocument::GetDocumentStates($documentType, $documentId);<br><br>foreach ($arDocumentStates as $arDocumentState)<br>{<br>   $ar = CBPDocument::GetUserTasksForWorkflow($GLOBALS["USER"]-&gt;GetID(), $arDocumentState["ID"]);<br>   print_r($ar);<br>}<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPDocument/GetUserTasksForWorkflow.php
	* @author Bitrix
	*/
	public static function GetUserTasksForWorkflow($userId, $workflowId)
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

	public static function PrepareFileForHistory($documentId, $fileId, $historyIndex)
	{
		return CBPHistoryService::PrepareFileForHistory($documentId, $fileId, $historyIndex);
	}

	public static function IsAdmin()
	{
		global $APPLICATION;
		return ($APPLICATION->GetGroupRight("bizproc") >= "W");
	}

	public static function GetDocumentFromHistory($historyId, &$arErrors)
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
		return null;
	}

	public static function GetAllowableUserGroups($parameterDocumentType)
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

	public static function onAfterTMDayStart($data)
	{
		global $DB;

		if (!CModule::IncludeModule("im"))
			return;

		$userId = (int) $data['USER_ID'];

		$iterator = \Bitrix\Bizproc\WorkflowInstanceTable::getList(
			array(
				'select' => array(new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(\'x\')')),
				'filter' => array(
					'=STATE.STARTED_BY' => $userId,
					'<OWNED_UNTIL' => date($DB->DateFormatToPHP(FORMAT_DATETIME),
						time() - \Bitrix\Bizproc\WorkflowInstanceTable::LOCKED_TIME_INTERVAL)
				),
			)
		);
		$row = $iterator->fetch();
		if (!empty($row['CNT']))
		{
			CIMNotify::Add(array(
				'FROM_USER_ID' => 0,
				'TO_USER_ID' => $userId,
				"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
				"NOTIFY_MODULE" => "bizproc",
				"NOTIFY_EVENT" => "wi_locked",
				'TITLE' => GetMessage('BPCGDOC_WI_LOCKED_NOTICE_TITLE'),
				'MESSAGE' => 	GetMessage('BPCGDOC_WI_LOCKED_NOTICE_MESSAGE', array(
					'#PATH#' => \Bitrix\Main\Config\Option::get("bizproc", "locked_wi_path", "/services/bp/instances.php?type=is_locked"),
					'#CNT#' => $row['CNT']
				))
			));
		}
	}

	/**
	 * Method returns map of document fields aliases.
	 * @param array $fields Document fields.
	 * @return array Aliases.
	 */
	public static function getDocumentFieldsAliasesMap($fields)
	{
		if (empty($fields) || !is_array($fields))
			return array();

		$aliases = array();
		foreach ($fields as $key => $property)
		{
			if (isset($property['Alias']))
			{
				$aliases[$property['Alias']] = $key;
			}
		}
		return $aliases;
	}

	/**
	 * Bizproc expression checker. Required for usage from external modules!
	 * Examples: {=Document:IBLOCK_ID}, {=Document:CREATED_BY>printable}, {=SequentialWorkflowActivity1:DocumentApprovers>user,printable}
	 * @param $value
	 * @return bool
	 */
	public static function IsExpression($value)
	{
		//go to internal alias
		return CBPActivity::isExpression($value);
	}
}
?>
