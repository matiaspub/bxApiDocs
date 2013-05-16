<?
IncludeModuleLangFile(__FILE__);


/**
 * <p>Непосредственно от абстрактного класса CBPActivity наследуются действия, которые не могут содержать внутри себя другие действия. Этот класс определяет набор базовых методов, которые необходимы любому действию. Некоторые методы, определенные в классе CBPActivity могут или должны быть переопределены в классе-наследнике.</p> <p>Составные действия наследуются от абстрактного класса <b>CBPCompositeActivity</b>, который в свою очередь наследуется от класса CBPActivity. Класс <b>CBPCompositeActivity</b> обеспечивает поддержку возможности включать внутрь действия дочерние действия. Например, составным действием является стандартное действие <b>CBPParallelActivity</b> (параллельное выполнение), которое содержит в себе дочерние действия, соответствующие веткам параллельного выполнения.</p> <p>Класс <b>CBPCompositeActivity</b> содержит член <b>arActivities</b>, с помощью которого можно обращаться к дочерним действиям.</p> <p>Класс <b>CBPActivity</b> содержит следующие члены, которые можно применять в действиях-наследниках:</p> <ul> <li> <b>workflow</b> – содержит объект-оболочку типа CBPWorkflow для данного бизнес-процесса,</li> <li> <b>parent</b> – содержит родительское действие,</li> <li> <b>executionStatus</b> – статус выполнения действия,</li> <li> <b>executionResult</b> – результат выполнения действия.</li> </ul>
 *
 *
 *
 *
 * @return mixed 
 *
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?<br>// Код класса действия, которое создаст файл с указанным в свойствах действия именем<br>class CBPMyActivity<br>	extends CBPActivity<br>{<br>	public function __construct($name)<br>	{<br>		parent::__construct($name);<br>		// Определим свойство FileName, в котором будет содержаться имя файла<br>		$this-&gt;arProperties = array("Title" =&gt; "", "FileName" =&gt; "");<br>	}<br><br>	// Исполняемый метод действия<br>	public function Execute()<br>	{<br>		// Если свойство с именем файла задано, осуществим в него запись<br>		// Обратите внимание, что для упрощения кода здесь не добавлены<br>		// необходимые проверки безопасности <br>		if (strlen($this-&gt;FileName) &gt; 0)<br>		{<br>			$f = fopen($this-&gt;FileName, "w");<br>			fwrite($f, "Какой-то текст");<br>			fclose($f);<br>		}<br><br>		// Вернем указание исполняющей среде, что действие завершено<br>		return CBPActivityExecutionStatus::Closed;<br>	}<br>}<br>?&gt;
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPActivity/index.php
 * @author Bitrix
 */
abstract class CBPActivity
{
	public $parent = null;

	public $executionStatus = CBPActivityExecutionStatus::Initialized;
	public $executionResult = CBPActivityExecutionResult::None;

	private $arStatusChangeHandlers = array();

	const StatusChangedEvent = 0;
	const ExecutingEvent = 1;
	const CancelingEvent = 2;
	const ClosedEvent = 3;
	const FaultingEvent = 4;

	protected $arProperties = array();
	protected $arPropertiesTypes = array();

	protected $name = "";
	public $workflow = null;

	public $arEventsMap = array();

	/************************  PROPERTIES  ************************************************/

	static public function GetDocumentId()
	{
		$rootActivity = $this->GetRootActivity();
		return $rootActivity->GetDocumentId();
	}

	static public function SetDocumentId($documentId)
	{
		$rootActivity = $this->GetRootActivity();
		$rootActivity->SetDocumentId($documentId);
	}

	static public function GetDocumentType()
	{
		$rootActivity = $this->GetRootActivity();
		if (!is_array($rootActivity->documentType) || count($rootActivity->documentType) <= 0)
		{
			$documentService = $this->workflow->GetService("DocumentService");
			$rootActivity->documentType = $documentService->GetDocumentType($rootActivity->documentId);
		}
		return $rootActivity->documentType;
	}

	static public function SetDocumentType($documentType)
	{
		$rootActivity = $this->GetRootActivity();
		$rootActivity->documentType = $documentType;
	}

	static public function GetWorkflowStatus()
	{
		$rootActivity = $this->GetRootActivity();
		return $rootActivity->GetWorkflowStatus();
	}

	static public function SetWorkflowStatus($status)
	{
		$rootActivity = $this->GetRootActivity();
		$rootActivity->SetWorkflowStatus($status);
	}

	static public function SetFieldTypes($arFieldTypes = array())
	{
		if (count($arFieldTypes) > 0)
		{
			$rootActivity = $this->GetRootActivity();
			foreach ($arFieldTypes as $key => $value)
				$rootActivity->arFieldTypes[$key] = $value;
		}
	}

	/**********************************************************/
	protected function ClearProperties()
	{
		$rootActivity = $this->GetRootActivity();
		if (is_array($rootActivity->arPropertiesTypes) && count($rootActivity->arPropertiesTypes) > 0
			&& is_array($rootActivity->arFieldTypes) && count($rootActivity->arFieldTypes) > 0)
		{
			foreach ($rootActivity->arPropertiesTypes as $key => $value)
			{
				if ($rootActivity->arFieldTypes[$value["Type"]]["BaseType"] == "file")
				{
					if (is_array($rootActivity->arProperties[$key]))
					{
						foreach ($rootActivity->arProperties[$key] as $v)
							CFile::Delete($v);
					}
					else
					{
						CFile::Delete($rootActivity->arProperties[$key]);
					}
				}
			}
		}
	}

	static public function GetPropertyBaseType($propertyName)
	{
		$rootActivity = $this->GetRootActivity();
		return $rootActivity->arFieldTypes[$rootActivity->arPropertiesTypes[$propertyName]["Type"]]["BaseType"];
	}

	static public function SetProperties($arProperties = array())
	{
		if (count($arProperties) > 0)
		{
			foreach ($arProperties as $key => $value)
				$this->arProperties[$key] = $value;
		}
	}

	static public function SetPropertiesTypes($arPropertiesTypes = array())
	{
		if (count($arPropertiesTypes) > 0)
		{
			foreach ($arPropertiesTypes as $key => $value)
				$this->arPropertiesTypes[$key] = $value;
		}
	}

	/**********************************************************/
	protected function ClearVariables()
	{
		$rootActivity = $this->GetRootActivity();
		if (is_array($rootActivity->arVariablesTypes) && count($rootActivity->arVariablesTypes) > 0
			&& is_array($rootActivity->arFieldTypes) && count($rootActivity->arFieldTypes) > 0)
		{
			foreach ($rootActivity->arVariablesTypes as $key => $value)
			{
				if ($rootActivity->arFieldTypes[$value["Type"]]["BaseType"] == "file")
				{
					if (is_array($rootActivity->arVariables[$key]))
					{
						foreach ($rootActivity->arVariables[$key] as $v)
							CFile::Delete($v);
					}
					else
					{
						CFile::Delete($rootActivity->arVariables[$key]);
					}
				}
			}
		}
	}

	static public function GetVariableBaseType($variableName)
	{
		$rootActivity = $this->GetRootActivity();
		return $rootActivity->arFieldTypes[$rootActivity->arVariablesTypes[$variableName]["Type"]]["BaseType"];
	}

	static public function SetVariables($arVariables = array())
	{
		if (count($arVariables) > 0)
		{
			$rootActivity = $this->GetRootActivity();
			foreach ($arVariables as $key => $value)
				$rootActivity->arVariables[$key] = $value;
		}
	}

	static public function SetVariablesTypes($arVariablesTypes = array())
	{
		if (count($arVariablesTypes) > 0)
		{
			$rootActivity = $this->GetRootActivity();
			foreach ($arVariablesTypes as $key => $value)
				$rootActivity->arVariablesTypes[$key] = $value;
		}
	}

	static public function SetVariable($name, $value)
	{
		$rootActivity = $this->GetRootActivity();
		$rootActivity->arVariables[$name] = $value;
	}

	static public function GetVariable($name)
	{
		$rootActivity = $this->GetRootActivity();

		if (array_key_exists($name, $rootActivity->arVariables))
			return $rootActivity->arVariables[$name];

		return null;
		//else
		//	throw new Exception(str_replace("#NAME#", htmlspecialcharsbx($name), GetMessage("BPSWA_EMPTY_NAME")));
	}

	static public function IsVariableExists($name)
	{
		$rootActivity = $this->GetRootActivity();
		return array_key_exists($name, $rootActivity->arVariables);
	}

	/************************************************/
	
	/**
	 * <p>Метод возвращает имя действия. Имя действия уникально в рамках бизнес-процесса.</p>
	 *
	 *
	 *
	 *
	 * @return string <p>Строка, содержащая имя действия.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPActivity/GetName.php
	 * @author Bitrix
	 */
	static public function GetName()
	{
		return $this->name;
	}

	
	/**
	 * <p>Метод возвращает корневое действие бизнес-процесса. Корневое действие реализует интерфейс <b>IBPRootActivity</b>.</p>
	 *
	 *
	 *
	 *
	 * @return CBPActivity <p>Объект типа <i>CBPActivity</i>, представляющий корневое действие
	 * бизнес-процесса.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$rootActivity = $this-&gt;GetRootActivity();<br>$documentId = $rootActivity-&gt;GetDocumentId();<br>?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPActivity/GetRootActivity.php
	 * @author Bitrix
	 */
	static public function GetRootActivity()
	{
		$p = $this;
		while ($p->parent != null)
			$p = $p->parent;
		return $p;
	}

	static public function SetWorkflow(CBPWorkflow $workflow)
	{
		$this->workflow = $workflow;
	}

	
	/**
	 * <p>Метод возвращает код бизнес-процесса.</p>
	 *
	 *
	 *
	 *
	 * @return string <p>Строка, содержащая идентификатор экземпляра бизнес-процесса.</p>
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/bizproc/bizproc_classes/CBPWorkflow/GetInstanceId.php">CBPWorkflow::GetInstanceId</a>
	 * </li> </ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPActivity/GetWorkflowInstanceId.php
	 * @author Bitrix
	 */
	static public function GetWorkflowInstanceId()
	{
		return $this->workflow->GetInstanceId();
	}

	static public function SetStatusTitle($title = '')
	{
		$rootActivity = $this->GetRootActivity();
		$stateService = $this->workflow->GetService("StateService");
		if ($rootActivity instanceof CBPStateMachineWorkflowActivity)
		{
			$arState = $stateService->GetWorkflowState($this->GetWorkflowInstanceId());

			$arActivities = $rootActivity->CollectNestedActivities();
			foreach ($arActivities as $activity)
				if ($activity->GetName() == $arState["STATE_NAME"])
					break;

			$stateService->SetStateTitle(
				$this->GetWorkflowInstanceId(),
				$activity->Title.($title != '' ? ": ".$title : '')
			);
		}
		else
		{
			if ($title != '')
			{
				$stateService->SetStateTitle(
					$this->GetWorkflowInstanceId(),
					$title
				);
			}
		}
	}

	static public function AddStatusTitle($title = '')
	{
		if ($title == '')
			return;

		$stateService = $this->workflow->GetService("StateService");

		$mainTitle = $stateService->GetStateTitle($this->GetWorkflowInstanceId());
		$mainTitle .= ((strpos($mainTitle, ": ") !== false) ? ", " : ": ").$title;

		$stateService->SetStateTitle($this->GetWorkflowInstanceId(), $mainTitle);
	}

	static public function DeleteStatusTitle($title = '')
	{
		if ($title == '')
			return;

		$stateService = $this->workflow->GetService("StateService");
		$mainTitle = $stateService->GetStateTitle($this->GetWorkflowInstanceId());

		$ar1 = explode(":", $mainTitle);
		if (count($ar1) <= 1)
			return;

		$newTitle = "";

		$ar2 = explode(",", $ar1[1]);
		foreach ($ar2 as $a)
		{
			$a = trim($a);
			if ($a != $title)
			{
				if (strlen($newTitle) > 0)
					$newTitle .= ", ";
				$newTitle .= $a;
			}
		}

		$result = $ar1[0].(strlen($newTitle) > 0 ? ": " : "").$newTitle;

		$stateService->SetStateTitle($this->GetWorkflowInstanceId(), $result);
	}

	private function GetPropertyValueRecursive($val)
	{
		// array(2, 5, array("SequentialWorkflowActivity1", "DocumentApprovers"))
		// array("Document", "IBLOCK_ID")
		// array("Workflow", "id")
		// "Hello, {=SequentialWorkflowActivity1:DocumentApprovers}, {=Document:IBLOCK_ID}!"

		if (is_string($val) && preg_match("/^\{=([A-Za-z0-9_]+)\:([A-Za-z0-9_]+)\}$/i", $val, $arMatches))
			$val = array($arMatches[1], $arMatches[2]);

		if (is_array($val))
		{
			$b = true;
			$r = array();

			$keys = array_keys($val);

			$i = 0;
			foreach ($keys as $key)
			{
				if ($key."!" != $i."!")
				{
					$b = false;
					break;
				}
				$i++;
			}

			foreach ($keys as $key)
			{
				list($t, $a) = $this->GetPropertyValueRecursive($val[$key]);
				if ($b)
				{
					if ($t == 1 && is_array($a))
						$r = array_merge($r, $a);
					else
						$r[] = $a;
				}
				else
				{
					$r[$key] = $a;
				}
			}

			if (count($r) == 2)
			{
				$keys = array_keys($r);
				if ($keys[0] == 0 && $keys[1] == 1 && is_string($r[0]) && is_string($r[1]))
				{
					$result = null;
					if ($this->GetRealParameterValue($r[0], $r[1], $result, false))
						return array(1, $result);
				}
			}
			return array(2, $r);
		}
		else
		{
			if (is_string($val))
			{
				if (substr($val, 0, 1) === "=")
				{
					$calc = new CBPCalc($this);
					$r = $calc->Calculate($val);
					if ($r != null)
						return array(2, $r);
				}

				$val = preg_replace_callback(
					"/\{=([A-Za-z0-9_]+)\:([A-Za-z0-9_]+)\}/i",
					array($this, "ParseStringParameter"),
					$val
				);
			}

			return array(2, $val);
		}
	}

	private function GetRealParameterValue($objectName, $fieldName, &$result)
	{
		$return = false;

		if ($objectName == "Document")
		{
			$rootActivity = $this->GetRootActivity();
			$documentId = $rootActivity->GetDocumentId();

			$documentService = $this->workflow->GetService("DocumentService");
			$document = $documentService->GetDocument($documentId);

			if (array_key_exists($fieldName, $document))
			{
				$result = $document[$fieldName];
				$return = true;
			}
		}
		elseif ($objectName == "Template")
		{
			$rootActivity = $this->GetRootActivity();
			if (substr($fieldName, -strlen("_printable")) == "_printable")
			{
				$fieldNameTmp = substr($fieldName, 0, strlen($fieldName) - strlen("_printable"));
				$result = $rootActivity->{$fieldNameTmp};

				$rootActivity = $this->GetRootActivity();
				$documentId = $rootActivity->GetDocumentId();

				$documentService = $this->workflow->GetService("DocumentService");
				$result = $documentService->GetFieldValuePrintable($documentId, $fieldNameTmp, $rootActivity->arPropertiesTypes[$fieldNameTmp]["Type"], $result, $rootActivity->arPropertiesTypes[$fieldNameTmp]);

				if (is_array($result))
					$result = implode(", ", $result);
			}
			else
			{
				$result = $rootActivity->{$fieldName};
			}

			$return = true;
		}
		elseif ($objectName == "Variable")
		{
			$rootActivity = $this->GetRootActivity();

			if (substr($fieldName, -strlen("_printable")) == "_printable")
			{
				$fieldNameTmp = substr($fieldName, 0, strlen($fieldName) - strlen("_printable"));
				$result = $rootActivity->GetVariable($fieldNameTmp);

				$rootActivity = $this->GetRootActivity();
				$documentId = $rootActivity->GetDocumentId();

				$documentService = $this->workflow->GetService("DocumentService");
				$result = $documentService->GetFieldValuePrintable($documentId, $fieldNameTmp, $rootActivity->arVariablesTypes[$fieldNameTmp]["Type"], $result, $rootActivity->arVariablesTypes[$fieldNameTmp]);

				if (is_array($result))
					$result = implode(", ", $result);
			}
			else
			{
				$result = $rootActivity->GetVariable($fieldName);
			}

			$return = true;
		}
		elseif ($objectName == "Workflow")
		{
			$result = $this->GetWorkflowInstanceId();
			$return = true;
		}
		elseif ($objectName == "User")
		{
			$result = 0;
			if ($GLOBALS["USER"]->IsAuthorized())
				$result = "user_".$GLOBALS["USER"]->GetID();

			$return = true;
		}
		elseif ($objectName == "System")
		{
			global $DB;

			$result = null;
			if ($fieldName == "Now")
				$result = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")));
			elseif ($fieldName == "Date")
				$result = date($DB->DateFormatToPHP(CSite::GetDateFormat("SHORT")));
			if ($result !== null)
				$return = true;
		}
		else
		{
			$activity = $this->workflow->GetActivityByName($objectName);
			if ($activity)
			{
				// _printable is not supported because mapping between activity property types
				// and document property types is not supported
				$result = $activity->{$fieldName};
				$return = true;
			}
		}

		return $return;
	}

	private function ParseStringParameter($matches)
	{
		$objectName = $matches[1];
		$fieldName = $matches[2];

		$result = "";

		if ($this->GetRealParameterValue($objectName, $fieldName, $result))
		{
			if (is_array($result))
				$result = implode(", ", $result);
		}
		else
		{
			$result = "{=".$objectName.":".$fieldName."}";
		}

		return $result;
	}

	static public function ParseValue($value)
	{
		list($t, $r) = $this->GetPropertyValueRecursive($value);
		return $r;
	}

	function __get($name)
	{
		if (array_key_exists($name, $this->arProperties))
		{
			list($t, $r) = $this->GetPropertyValueRecursive($this->arProperties[$name]);
			return $r;
		}
		else
		{
			return null;
			//throw new Exception(str_replace("#NAME#", htmlspecialcharsbx($name), GetMessage("BPCGACT_NO_PROPERTY")));
		}
	}

	function __set($name, $val)
	{
		if (array_key_exists($name, $this->arProperties))
			$this->arProperties[$name] = $val;
		//else
			//throw new Exception(str_replace("#NAME#", htmlspecialcharsbx($name), GetMessage("BPCGACT_NO_PROPERTY")));
	}

	static public function IsPropertyExists($name)
	{
		return array_key_exists($name, $this->arProperties);
	}

	static public function CollectNestedActivities()
	{
		return null;
	}

	/************************  CONSTRUCTORS  *****************************************************/

	static public function __construct($name)
	{
		$this->name = $name;
	}

	/************************  DEBUG  ***********************************************************/

	static public function ToString()
	{
		return $this->name.
			" [".get_class($this)."] (status=".
			CBPActivityExecutionStatus::Out($this->executionStatus).
			", result=".
			CBPActivityExecutionResult::Out($this->executionResult).
			", count(ClosedEvent)=".
			count($this->arStatusChangeHandlers[self::ClosedEvent]).
			")";
	}

	static public function Dump($level = 3)
	{
		$result = str_repeat("	", $level).$this->ToString()."\n";

		if (is_subclass_of($this, "CBPCompositeActivity"))
		{
			foreach ($this->arActivities as $activity)
				$result .= $activity->Dump($level + 1);
		}

		return $result;
	}

	/************************  PROCESS  ***********************************************************/

	public function Initialize()
	{
	}

	static public function Execute()
	{
		return CBPActivityExecutionStatus::Closed;
	}

	protected function ReInitialize()
	{
		$this->executionStatus = CBPActivityExecutionStatus::Initialized;
		$this->executionResult = CBPActivityExecutionResult::None;
	}

	static public function Cancel()
	{
		return CBPActivityExecutionStatus::Closed;
	}

	static public function HandleFault(Exception $exception)
	{
		return CBPActivityExecutionStatus::Closed;
	}

	/************************  LOAD / SAVE  *******************************************************/

	static public function FixUpParentChildRelationship(CBPActivity $nestedActivity)
	{
		$nestedActivity->parent = $this;
	}

	public static function Load($stream)
	{
		if (strlen($stream) <= 0)
			throw new Exception("stream");

		$pos = strpos($stream, ";");
		$strUsedActivities = substr($stream, 0, $pos);
		$stream = substr($stream, $pos + 1);

		$runtime = CBPRuntime::GetRuntime();
		$arUsedActivities = explode(",", $strUsedActivities);

		foreach ($arUsedActivities as $activityCode)
			$runtime->IncludeActivityFile($activityCode);

		return unserialize($stream);
	}

	protected function GetACNames()
	{
		return array(substr(get_class($this), 3));
	}

	private static function SearchUsedActivities(CBPActivity $activity, &$arUsedActivities)
	{
		$arT = $activity->GetACNames();
		foreach ($arT as $t)
		{
			if (!in_array($t, $arUsedActivities))
				$arUsedActivities[] = $t;
		}

		if ($arNestedActivities = $activity->CollectNestedActivities())
		{
			foreach ($arNestedActivities as $nestedActivity)
				self::SearchUsedActivities($nestedActivity, $arUsedActivities);
		}
	}

	static public function Save()
	{
		$arUsedActivities = array();
		self::SearchUsedActivities($this, $arUsedActivities);
		$strUsedActivities = implode(",", $arUsedActivities);
		return $strUsedActivities.";".serialize($this);
	}

	/************************  STATUS CHANGE HANDLERS  **********************************************/

	
	/**
	 * <p>Метод добавляет новый обработчик события изменения статуса действия.</p>
	 *
	 *
	 *
	 *
	 * @param int $event  Одна из констант <b>CBPActivity::ExecutingEvent</b>, <b>CBPActivity::ClosedEvent</b>,
	 * <b>CBPActivity::FaultingEvent</b>, определяющая, на какое изменение статуса
	 * будет вызываться обработчик.
	 *
	 *
	 *
	 * @param IBPActivityEventListener $eventHandler  Обработчик события, который реализует интерфейс
	 * <b>IBPActivityEventListener</b>.
	 *
	 *
	 *
	 * @return void 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>class CBPMyActivity<br>	extends CBPCompositeActivity    // наследуем, так как составное действие<br>	implements IBPEventActivity	// обработка события завершения дочернего действия<br>{<br>	// Исполняемый метод действия<br>	public function Execute()<br>	{<br>		// Возьмем первое дочернее действие<br>		$activity = $this-&gt;arActivities[0];<br>		// Подпишемся на событие изменения статуса дочернего действия (завершение)<br>		$activity-&gt;AddStatusChangeHandler(self::ClosedEvent, $this);<br>		// Отправим дочернее действие исполняющей среде на выполнение<br>		$this-&gt;workflow-&gt;ExecuteActivity($activity);<br><br>		// Вернем указание исполняющей среде, что действие еще выполняется<br>		return CBPActivityExecutionStatus::Executing;<br>	}<br><br>	// Обработчик события изменения статуса интерфейса IBPEventActivity<br>	// Параметром передается действие, изменившее статус<br>	protected function OnEvent(CBPActivity $sender)<br>	{<br>		// Отпишемся от события изменения статуса дочернего действия (завершения)<br>		$sender-&gt;RemoveStatusChangeHandler(self::ClosedEvent, $this);<br>		// Дочернее действие завершено, выполняем другой необходимый нам код<br>		// Например завершаем действие<br>		$this-&gt;workflow-&gt;CloseActivity($this);<br>	}<br>}<br>?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/bizproc/bizproc_classes/CBPActivity/RemoveStatusChangeHandler.php">RemoveStatusChangeHandler</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPActivity/AddStatusChangeHandler.php
	 * @author Bitrix
	 */
	static public function AddStatusChangeHandler($event, $eventHandler)
	{
		if (!is_array($this->arStatusChangeHandlers))
			$this->arStatusChangeHandlers = array();

		if (!array_key_exists($event, $this->arStatusChangeHandlers))
			$this->arStatusChangeHandlers[$event] = array();

		$this->arStatusChangeHandlers[$event][] = $eventHandler;
	}

	
	/**
	 * <p>Метод удаляет обработчик события изменения статуса действия.</p>
	 *
	 *
	 *
	 *
	 * @param int $event  Одна из констант <b>CBPActivity::ExecutingEvent</b>, <b>CBPActivity::ClosedEvent</b>,
	 * <b>CBPActivity::FaultingEvent</b>, определяющая, на какое изменение статуса
	 * будет вызываться обработчик.
	 *
	 *
	 *
	 * @param IBPActivityEventListener $eventHandler  Обработчик события, который реализует интерфейс
	 * <b>IBPActivityEventListener</b>.
	 *
	 *
	 *
	 * @return void 
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/bizproc/bizproc_classes/CBPActivity/AddStatusChangeHandler.php">AddStatusChangeHandler</a></li>
	 * </ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/bizproc/bizproc_classes/CBPActivity/RemoveStatusChangeHandler.php
	 * @author Bitrix
	 */
	static public function RemoveStatusChangeHandler($event, $eventHandler)
	{
		if (!is_array($this->arStatusChangeHandlers))
			$this->arStatusChangeHandlers = array();

		if (!array_key_exists($event, $this->arStatusChangeHandlers))
			$this->arStatusChangeHandlers[$event] = array();

		$index = array_search($eventHandler, $this->arStatusChangeHandlers[$event], true);

		if ($index !== false)
			unset($this->arStatusChangeHandlers[$event][$index]);
	}

	/************************  EVENTS  **********************************************************************/

	private function FireStatusChangedEvents($event, $arEventParameters = array())
	{
		if (array_key_exists($event, $this->arStatusChangeHandlers) && is_array($this->arStatusChangeHandlers[$event]))
		{
			foreach ($this->arStatusChangeHandlers[$event] as $eventHandler)
				call_user_func_array(array($eventHandler, "OnEvent"), array($this, $arEventParameters));
		}
	}

	static public function SetStatus($newStatus, $arEventParameters = array())
	{
		$this->executionStatus = $newStatus;
		$this->FireStatusChangedEvents(self::StatusChangedEvent, $arEventParameters);

		switch ($newStatus)
		{
			case CBPActivityExecutionStatus::Executing:
				$this->FireStatusChangedEvents(self::ExecutingEvent, $arEventParameters);
				break;

			case CBPActivityExecutionStatus::Canceling:
				$this->FireStatusChangedEvents(self::CancelingEvent, $arEventParameters);
				break;

			case CBPActivityExecutionStatus::Closed:
				$this->FireStatusChangedEvents(self::ClosedEvent, $arEventParameters);
				break;

			case CBPActivityExecutionStatus::Faulting:
				$this->FireStatusChangedEvents(self::FaultingEvent, $arEventParameters);
				break;

			default:
				return;
		}
	}

	/************************  CREATE  *****************************************************************/

	public static function IncludeActivityFile($code)
	{
		$runtime = CBPRuntime::GetRuntime();
		return $runtime->IncludeActivityFile($code);
	}

	public static function CreateInstance($code, $data)
	{
		$code = preg_replace("[^a-zA-Z0-9]", "", $code);
		$classname = 'CBP'.$code;
		if (class_exists($classname))
			return new $classname($data);
		else
			return null;
	}

	public static function CallStaticMethod($code, $method, $arParameters = array())
	{
		$runtime = CBPRuntime::GetRuntime();
		if (!$runtime->IncludeActivityFile($code))
			return array(array("code" => "ActivityNotFound", "parameter" => $code, "message" => GetMessage("BPGA_ACTIVITY_NOT_FOUND")));

		$code = preg_replace("[^a-zA-Z0-9]", "", $code);
		$classname = 'CBP'.$code;

		return call_user_func_array(array($classname, $method), $arParameters);
	}

	static public function InitializeFromArray($arParams)
	{
		if (is_array($arParams))
		{
			foreach ($arParams as $key => $value)
			{
				if (array_key_exists($key, $this->arProperties))
					$this->arProperties[$key] = $value;
			}
		}
	}

	/************************  MARK  ****************************************************************/

	static public function MarkCanceled($arEventParameters = array())
	{
		if ($this->executionStatus != CBPActivityExecutionStatus::Closed)
		{
			if ($this->executionStatus != CBPActivityExecutionStatus::Canceling)
				throw new Exception("InvalidCancelActivityState");

			$this->executionResult = CBPActivityExecutionResult::Canceled;
			$this->MarkClosed($arEventParameters);
		}
	}

	static public function MarkCompleted($arEventParameters = array())
	{
		$this->executionResult = CBPActivityExecutionResult::Succeeded;
		$this->MarkClosed($arEventParameters);
	}

	static public function MarkFaulted($arEventParameters = array())
	{
		$this->executionResult = CBPActivityExecutionResult::Faulted;
		$this->MarkClosed($arEventParameters);
	}

	private function MarkClosed($arEventParameters = array())
	{
		switch ($this->executionStatus)
		{
			case CBPActivityExecutionStatus::Executing:
			case CBPActivityExecutionStatus::Canceling:
			case CBPActivityExecutionStatus::Faulting:
			{
				if (is_subclass_of($this, "CBPCompositeActivity"))
				{
					foreach ($this->arActivities as $activity)
					{
						if (($activity->executionStatus != CBPActivityExecutionStatus::Initialized) 
							&& ($activity->executionStatus != CBPActivityExecutionStatus::Closed))
						{
							throw new Exception("ActiveChildExist");
						}
					}
				}

				$trackingService = $this->workflow->GetService("TrackingService");
				$trackingService->Write($this->GetWorkflowInstanceId(), CBPTrackingType::CloseActivity, $this->name, $this->executionStatus, $this->executionResult, ($this->IsPropertyExists("Title") ? $this->Title : ""));

				$this->SetStatus(CBPActivityExecutionStatus::Closed, $arEventParameters);

				//if ($this->parent)
				//	$this->workflow->SetCurrentActivity($this->parent);

				return;
			}
		}

		throw new Exception("InvalidCloseActivityState");
	}

	protected function WriteToTrackingService($message = "", $modifiedBy = 0, $trackingType = -1)
	{
		$trackingService = $this->workflow->GetService("TrackingService");
		if ($trackingType < 0)
			$trackingType = CBPTrackingType::Custom;
		$trackingService->Write($this->GetWorkflowInstanceId(), $trackingType, $this->name, $this->executionStatus, $this->executionResult, ($this->IsPropertyExists("Title") ? $this->Title : ""), $message, $modifiedBy);
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		return array();
	}

	public static function ValidateChild($childActivity, $bFirstChild = false)
	{
		return array();
	}

	public static function &FindActivityInTemplate(&$arWorkflowTemplate, $activityName)
	{
		return CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
	}
}
?>