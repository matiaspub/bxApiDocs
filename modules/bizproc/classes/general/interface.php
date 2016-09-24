<?
interface IBPEventActivity
{
	static public function Subscribe(IBPActivityExternalEventListener $eventHandler);
	static public function Unsubscribe(IBPActivityExternalEventListener $eventHandler);
}

interface IBPEventDrivenActivity
{

}

interface IBPActivityEventListener
{
	static public function OnEvent(CBPActivity $sender, $arEventParameters = array());
}

interface IBPActivityExternalEventListener
{
	static public function OnExternalEvent($arEventParameters = array());
}

interface IBPRootActivity
{
	public function GetDocumentId();
	static public function SetDocumentId($documentId);

	public function GetWorkflowStatus();
	static public function SetWorkflowStatus($status);

	static public function SetProperties($arProperties = array());

	static public function SetVariables($arVariables = array());
	static public function SetVariable($name, $value);
	static public function GetVariable($name);
	public function IsVariableExists($name);

	static public function SetCustomStatusMode();
}


/**
 * Класс документа должен реализовывать методы интерфейса <b>IBPWorkflowDocument</b>. Этот интерфейс содержит методы, которые необходимы бизнес-процессу для работы с документом.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/index.php
 * @author Bitrix
 */
interface IBPWorkflowDocument
{
	/**
	 * Method returns document fields values as array (field_code => value, ...). Must be compatible with GetDocumentFields.
	 *
	 * @param string $documentId - Document id.
	 * @return array - Fields values.
	 */
	
	/**
	* <p>Метод возвращает свойства (поля) документа в виде ассоциативного массива вида </p>   <pre class="syntax">array(<br>   код_свойства =&gt; значение,<br>   ...<br>)<br></pre>  Определены все свойства, которые возвращает метод <a href="http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetDocumentFields.php">GetDocumentFields</a>.  <p></p>
	*
	*
	* @param mixed $documentId  Идентификатор документа
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>public function GetDocument($documentId)<br>{<br>	$documentId = intval($documentId);<br>	if ($documentId &lt;= 0)<br>		throw new CBPArgumentNullException("documentId");<br><br>	$arResult = null;<br><br>	$dbDocumentList = CIBlockElement::GetList(<br>		array(),<br>		array("ID" =&gt; $documentId, "SHOW_NEW"=&gt;"Y", "SHOW_HISTORY" =&gt; "Y")<br>	);<br>	if ($objDocument = $dbDocumentList-&gt;GetNextElement())<br>	{<br>		$arDocumentFields = $objDocument-&gt;GetFields();<br>		$arDocumentProperties = $objDocument-&gt;GetProperties();<br><br>		foreach ($arDocumentFields as $fieldKey =&gt; $fieldValue)<br>		{<br>			if (substr($fieldKey, 0, 1) != "~")<br>				$arResult[$fieldKey] = $fieldValue;<br>		}<br><br>		foreach ($arDocumentProperties as $propertyKey =&gt; $propertyValue)<br>			$arResult["PROPERTY_".$propertyKey] = $propertyValue["VALUE"];<br>	}<br><br>	return $arResult;<br>}<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetDocument.php
	* @author Bitrix
	*/
	static public function GetDocument($documentId);

	/**
	 * Method returns document type fields list.
	 *
	 * @param string $documentType - Document type.
	 * @return array - Fields array(field_code => array("NAME" => field_name, "TYPE" => field_type), ...).
	 */
	
	/**
	* <p>Метод возвращает массив свойств (полей), которые имеет документ данного типа. Метод <a href="http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetDocument.php">GetDocument</a> возвращает значения свойств для заданного документа. Возвращаемый массив имеет вид </p>   <pre class="syntax">array(<br>   код_свойства =&gt; array(<br>      "NAME" =&gt; название_свойства,<br>      "TYPE" =&gt; тип_свойства<br>   ), <br>   ...<br>)<br></pre>   <p></p>
	*
	*
	* @param mixed $documentType  Идентификатор типа документа
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>public function GetDocumentFields($documentType)<br>{<br>	$iblockId = intval(substr($documentType, strlen("iblock_")));<br>	if ($iblockId &lt;= 0)<br>		throw new CBPArgumentOutOfRangeException("documentType", $documentType);<br><br>	$arResult = array(<br>		"ID" =&gt; array(<br>			"Name" =&gt; GetMessage("IBD_FIELD_ID"),<br>			"Type" =&gt; "int",<br>			"Filterable" =&gt; true,<br>			"Editable" =&gt; false,<br>			"Required" =&gt; false,<br>			"Multiple" =&gt; false,<br>		),<br>		"TIMESTAMP_X" =&gt; array(<br>			"Name" =&gt; GetMessage("IBD_FIELD_TIMESTAMP_X"),<br>			"Type" =&gt; "datetime",<br>			"Filterable" =&gt; true,<br>			"Editable" =&gt; true,<br>			"Required" =&gt; false,<br>			"Multiple" =&gt; false,<br>		),<br>		...<br>	);<br>	return $arResult;<br>}<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetDocumentFields.php
	* @author Bitrix
	*/
	static public function GetDocumentFields($documentType);

	/**
	 * Method creates new document with specified fields.
	 *
	 * @param $parentDocumentId - Parent document id.
	 * @param array $arFields - Fields values array(field_code => value, ...). Fields codes must be compatible with codes from GetDocumentFields.
	 * @return int - New document id.
	 */
	
	/**
	* <p>Метод создает новый документ с указанными свойствами (полями) и возвращает его код.</p>
	*
	*
	* @param mixed $pid  Не используется
	*
	* @param array $arFields  Массив значений свойств документа в виде          <pre class="syntax">array(<br>  
	* код_свойства =&gt; значение,<br>   ...<br>)<br></pre>        Коды свойств
	* соответствуют кодам свойств, возвращаемым методом <a
	* href="http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetDocumentFields.php">GetDocumentFields</a>.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>public function CreateDocument($pid, $arFields)<br>{<br>	$iblockElement = new CIBlockElement();<br>	$id = $iblockElement-&gt;Add($arFields);<br>	if (!$id || $id &lt;= 0)<br>		throw new Exception($iblockElement-&gt;LAST_ERROR);<br>	return $id;<br>}<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/CreateDocument.php
	* @author Bitrix
	*/
	static public function CreateDocument($parentDocumentId, $arFields);

	/**
	 * Method updates document fields.
	 *
	 * @param string $documentId - Document id.
	 * @param array $arFields - New fields values array(field_code => value, ...). Fields codes must be compatible with codes from GetDocumentFields.
	 */
	
	/**
	* <p>Метод изменяет свойства (поля) указанного документа на указанные значения.</p>
	*
	*
	* @param mixed $documentId  Код документа
	*
	* @param array $arFields  Массив новых значений свойств документа в виде          <pre
	* class="syntax">array(<br>   код_свойства =&gt; значение,<br>   ...<br>)<br></pre>        Коды
	* свойств соответствуют кодам свойств, возвращаемым методом
	* GetDocumentFields.
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>public function UpdateDocument($documentId, $arFields)<br>{<br>	$documentId = intval($documentId);<br>	if ($documentId &lt;= 0)<br>		throw new CBPArgumentNullException("documentId");<br><br>	$iblockElement = new CIBlockElement();<br>	$res = $iblockElement-&gt;Update($documentId, $arFields);<br>	if (!$res)<br>		throw new Exception($iblockElement-&gt;LAST_ERROR);<br>}<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/UpdateDocument.php
	* @author Bitrix
	*/
	static public function UpdateDocument($documentId, $arFields);

	/**
	 * Method deletes specified document.
	 *
	 * @param string $documentId - Document id.
	 */
	
	/**
	* <p>Метод удаляет указанный документ.</p>
	*
	*
	* @param mixed $documentId  Код документа
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>public function DeleteDocument($documentId)<br>{<br>	$documentId = intval($documentId);<br>	if ($documentId &lt;= 0)<br>		throw new CBPArgumentNullException("documentId");<br><br>	CIBlockElement::Delete($documentId);<br>}<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/DeleteDocument.php
	* @author Bitrix
	*/
	static public function DeleteDocument($documentId);

	/**
	 * Method publishes document.
	 *
	 * @param string $documentId - Document id.
	 */
	
	/**
	* <p>Метод публикует документ, то есть делает его доступным в публичной части сайта.</p>
	*
	*
	* @param mixed $documentId  Код документа
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/PublishDocument.php
	* @author Bitrix
	*/
	static public function PublishDocument($documentId);

	/**
	 * Method unpublishes document.
	 *
	 * @param string $documentId - Document id.
	 */
	
	/**
	* <p>Метод снимает документ с публикации, то есть делает его недоступным в публичной части сайта.</p>
	*
	*
	* @param mixed $documentId  Код документа
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/UnpublishDocument.php
	* @author Bitrix
	*/
	static public function UnpublishDocument($documentId);

	/**
	 * Method locks specified document for specified workflow state. A locked document can be changed only by the specified workflow.
	 *
	 * @param string $documentId - Document id.
	 * @param string $workflowId - Workflow id.
	 * @return bool - True on success, false on failure.
	 */
	
	/**
	* <p>Метод блокирует указанный документ для указанного бизнес-процесса. Заблокированный документ может изменяться только указанным бизнес-процессом. Если удалось заблокировать документ, то возвращается true, иначе – false.</p>
	*
	*
	* @param mixed $documentId  Код документа
	*
	* @param string $workflowId  Код бизнес-процесса
	*
	* @return bool 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/LockDocument.php
	* @author Bitrix
	*/
	static public function LockDocument($documentId, $workflowId);

	/**
	 * Method unlocks specified document. On unlock fires events like "Entity_OnUnlockDocument" with document id as first parameter.
	 *
	 * @param string $documentId - Document id.
	 * @param string $workflowId - Workflow id.
	 * @return bool - True on success, false on failure.
	 */
	
	/**
	* <p>Метод разблокирует указанный документ. При разблокировке вызываются обработчики события вида "Сущность_OnUnlockDocument", которым входящим параметром передается код документа. Если удалось разблокировать документ, то возвращается true, иначе - false.</p>
	*
	*
	* @param mixed $documentId  Код документа
	*
	* @param string $workflowId  Код бизнес-процесса
	*
	* @return bool 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/UnlockDocument.php
	* @author Bitrix
	*/
	static public function UnlockDocument($documentId, $workflowId);

	/**
	 * Method checks lock status.
	 *
	 * @param string $documentId - Document id.
	 * @param string $workflowId - Workflow id.
	 * @return bool True if document locked.
	 */
	
	/**
	* <p>Метод проверяет, заблокирован ли указанный документ для указанного бизнес-процесса. Т.е. если для данного бизнес-процесса документ не доступен для записи из-за того, что он заблокирован другим бизнес-процессом, то метод должен вернуть true, иначе - false.</p>
	*
	*
	* @param mixed $documentId  Код документа
	*
	* @param string $workflowId  Код бизнес-процесса
	*
	* @return bool 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/IsDocumentLocked.php
	* @author Bitrix
	*/
	static public function IsDocumentLocked($documentId, $workflowId);

	/**
	 * Method checks can user operate specified document with specified operation.
	 *
	 * @param int $operation - Operation.
	 * @param int $userId - User id.
	 * @param string|int $documentId - Document id.
	 * @param array $arParameters - Additional parameters.
	 * @return bool
	 */
	
	/**
	* <p>Метод проверяет права на выполнение операций над заданным документом. Проверяются операции: </p>   <ul> <li> <b>0</b> - просмотр данных бизнес-процесса, </li>     <li> <b>1</b> - запуск бизнес-процесса, </li>     <li> <b>2</b> - право изменять документ, </li>     <li> <b>3</b> - право смотреть документ. </li>  </ul>  Если права есть, то возвращается true, иначе – false.  <p></p>
	*
	*
	* @param int $operation  Операция
	*
	* @param int $userId  Код пользователя, для которого проверяется право на выполнение
	* операции
	*
	* @param mixed $documentId  Код документа, к которому применяется операция
	*
	* @param array $arParameters = array() Ассоциативный массив вспомогательных параметров. Используется
	* для того, чтобы не рассчитывать заново те вычисляемые значения,
	* которые уже известны на момент вызова метода. Стандартными
	* являются ключи массива <b>DocumentStates</b> - массив состояний
	* бизнес-процессов данного документа, <b>WorkflowId</b> - код
	* бизнес-процесса (если требуется проверить операцию на одном
	* бизнес-процессе). Массив может быть дополнен другими
	* произвольными ключами.
	*
	* @return bool 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/CanUserOperateDocument.php
	* @author Bitrix
	*/
	static public function CanUserOperateDocument($operation, $userId, $documentId, $arParameters = array());

	/**
	 * Method checks can user operate specified document type with specified operation.
	 *
	 * @param int $operation - Operation.
	 * @param int $userId - User id.
	 * @param string $documentType - Document type.
	 * @param array $arParameters - Additional parameters.
	 * @return bool
	 */
	
	/**
	* <p>Метод проверяет права на выполнение операций над документами заданного типа. Проверяются операции: </p>   <ul> <li> <b>2</b> - право изменять документ, </li>     <li> <b>4</b> - право изменять шаблоны бизнес-процессов для данного типа документа. </li>  </ul>  Если права есть, то возвращается true, иначе – false.  <p></p>
	*
	*
	* @param int $operation  Операция
	*
	* @param int $userId  Код пользователя, для которого проверяется право на выполнение
	* операции
	*
	* @param mixed $documentId  Код документа, к которому применяется операция
	*
	* @param array $arParameters = array() Ассоциативный массив вспомогательных параметров. Используется
	* для того, чтобы не рассчитывать заново те вычисляемые значения,
	* которые уже известны на момент вызова метода. Стандартными
	* являются ключи массива <b>DocumentStates</b> - массив состояний
	* бизнес-процессов данного документа, <b>WorkflowId</b> - код
	* бизнес-процесса (если требуется проверить операцию на одном
	* бизнес-процессе). Массив может быть дополнен другими
	* произвольными ключами.
	*
	* @return bool 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/CanUserOperateDocumentType.php
	* @author Bitrix
	*/
	static public function CanUserOperateDocumentType($operation, $userId, $documentType, $arParameters = array());

	/**
	 * Get document admin page URL.
	 *
	 * @param string|int $documentId - Document id.
	 * @return string - URL.
	 */
	
	/**
	* <p>Метод по коду документа возвращает ссылку на страницу документа в административной части.</p>
	*
	*
	* @param mixed $documentId  Код документа
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetDocumentAdminPage.php
	* @author Bitrix
	*/
	static public function GetDocumentAdminPage($documentId);

	/**
	 * Method returns document information. This information uses in method RecoverDocumentFromHistory.
	 *
	 * @param string $documentId - Document id.
	 * @param $historyIndex - History index.
	 * @return array - Document data.
	 */
	
	/**
	* <p>Метод возвращает массив произвольной структуры, содержащий всю информацию о документе. По этому массиву документ восстанавливается методом <a href="http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/RecoverDocumentFromHistory.php">RecoverDocumentFromHistory</a>.</p>
	*
	*
	* @param mixed $documentId  Код документа
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetDocumentForHistory.php
	* @author Bitrix
	*/
	static public function GetDocumentForHistory($documentId, $historyIndex);

	/**
	 * Method recovers specified document from information, provided by method RecoverDocumentFromHistory.
	 *
	 * @param string $documentId - Document id.
	 * @param array $arDocument - Document data.
	 */
	
	/**
	* <p>Метод восстанавливает указанный документ из массива. Массив создается методом <a href="http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetDocumentForHistory.php">GetDocumentForHistory</a>.</p>
	*
	*
	* @param mixed $documentId  Код документа
	*
	* @param array $arDocument  Массив документа
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/RecoverDocumentFromHistory.php
	* @author Bitrix
	*/
	static public function RecoverDocumentFromHistory($documentId, $arDocument);

	
	/**
	* <p>Метод для типа документа возвращает массив доступных операций в виде </p> <pre class="syntax">array(<br>   "код_операции" =&gt; "название_операции_на_текущем_языке",<br>   . . .<br>)</pre>   <p></p>
	*
	*
	* @param mixed $documentType  Код типа документа
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetAllowableOperations.php
	* @author Bitrix
	*/
	static public function GetAllowableOperations($documentType);
	
	/**
	* <p>Метод для типа документа возвращает массив возможных групп пользователей в виде </p>   <pre class="syntax">array(<br>   "код_группы" =&gt; "название_группы_на_текущем_языке",<br>   . . .<br>)</pre>   <p></p>
	*
	*
	* @param mixed $documentType  Код типа документа
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetAllowableUserGroups.php
	* @author Bitrix
	*/
	static public function GetAllowableUserGroups($documentType);
	
	/**
	* <p>Метод возвращает пользователей указанной группы для указанного документа в виде массива кодов пользователей.</p>
	*
	*
	* @param mixed $group  Код группы пользователей
	*
	* @param mixed $documentId  Код документа
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetUsersFromUserGroup.php
	* @author Bitrix
	*/
	static public function GetUsersFromUserGroup($group, $documentId);
}
?>