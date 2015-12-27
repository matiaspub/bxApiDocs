<?
interface IBPEventActivity
{
	static public function Subscribe(IBPActivityExternalEventListener $eventHandler);
	static public function Unsubscribe(IBPActivityExternalEventListener $eventHandler);
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
	public function GetVariable($name);
	static public function IsVariableExists($name);

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
	* Метод возвращает свойства (поля) документа в виде ассоциативного массива вида array(код_свойства => значение, ...). Определены все свойства, которые возвращает метод GetDocumentFields.
	*
	* @param string $documentId - код документа.
	* @return array - массив свойств документа.
	*/

	/**
	* <p>Метод возвращает свойства (поля) документа в виде ассоциативного массива вида </p> <pre class="syntax">array(<br> код_свойства =&gt; значение,<br> ...<br>)<br></pre> Определены все свойства, которые возвращает метод <a href="http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetDocumentFields.php">GetDocumentFields</a>. <p></p>
	*
	*
	* @param mixed $documentId  Идентификатор документа
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>public function GetDocument($documentId)<br>{<br>	$documentId = intval($documentId);<br>	if ($documentId &lt;= 0)<br>		throw new CBPArgumentNullException("documentId");<br><br>	$arResult = null;<br><br>	$dbDocumentList = CIBlockElement::GetList(<br>		array(),<br>		array("ID" =&gt; $documentId, "SHOW_NEW"=&gt;"Y", "SHOW_HISTORY" =&gt; "Y")<br>	);<br>	if ($objDocument = $dbDocumentList-&gt;GetNextElement())<br>	{<br>		$arDocumentFields = $objDocument-&gt;GetFields();<br>		$arDocumentProperties = $objDocument-&gt;GetProperties();<br><br>		foreach ($arDocumentFields as $fieldKey =&gt; $fieldValue)<br>		{<br>			if (substr($fieldKey, 0, 1) != "~")<br>				$arResult[$fieldKey] = $fieldValue;<br>		}<br><br>		foreach ($arDocumentProperties as $propertyKey =&gt; $propertyValue)<br>			$arResult["PROPERTY_".$propertyKey] = $propertyValue["VALUE"];<br>	}<br><br>	return $arResult;<br>}<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetDocument.php
	* @author Bitrix
	*/
	public static 	static public function GetDocument($documentId);

	/**
	* Метод возвращает массив свойств (полей), которые имеет документ данного типа. Метод GetDocument возвращает значения свойств для заданного документа.
	*
	* @param string $documentType - тип документа.
	* @return array - массив свойств вида array(код_свойства => array("NAME" => название_свойства, "TYPE" => тип_свойства), ...).
	*/
	
	/**
	* <p>Метод возвращает массив свойств (полей), которые имеет документ данного типа. Метод <a href="http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetDocument.php">GetDocument</a> возвращает значения свойств для заданного документа. Возвращаемый массив имеет вид </p> <pre class="syntax">array(<br> код_свойства =&gt; array(<br> "NAME" =&gt; название_свойства,<br> "TYPE" =&gt; тип_свойства<br> ), <br> ...<br>)<br></pre> <p></p>
	*
	*
	* @param mixed $documentType  Идентификатор типа документа
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>public function GetDocumentFields($documentType)<br>{<br>	$iblockId = intval(substr($documentType, strlen("iblock_")));<br>	if ($iblockId &lt;= 0)<br>		throw new CBPArgumentOutOfRangeException("documentType", $documentType);<br><br>	$arResult = array(<br>		"ID" =&gt; array(<br>			"Name" =&gt; GetMessage("IBD_FIELD_ID"),<br>			"Type" =&gt; "int",<br>			"Filterable" =&gt; true,<br>			"Editable" =&gt; false,<br>			"Required" =&gt; false,<br>			"Multiple" =&gt; false,<br>		),<br>		"TIMESTAMP_X" =&gt; array(<br>			"Name" =&gt; GetMessage("IBD_FIELD_TIMESTAMP_X"),<br>			"Type" =&gt; "datetime",<br>			"Filterable" =&gt; true,<br>			"Editable" =&gt; true,<br>			"Required" =&gt; false,<br>			"Multiple" =&gt; false,<br>		),<br>		...<br>	);<br>	return $arResult;<br>}<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetDocumentFields.php
	* @author Bitrix
	*/
	public static static public function GetDocumentFields($documentType);

	/**
	* Метод создает новый документ с указанными свойствами (полями).
	*
	* @param array $arFields - массив значений свойств документа в виде array(код_свойства => значение, ...). Коды свойств соответствуют кодам свойств, возвращаемым методом GetDocumentFields.
	* @return int - код созданного документа.
	*/

	/**
	* <p>Метод создает новый документ с указанными свойствами (полями) и возвращает его код.</p>
	*
	*
	* @param mixed $pid  Не используется
	*
	* @param array $arFields  Массив значений свойств документа в виде <pre class="syntax">array(<br>
	* код_свойства =&gt; значение,<br> ...<br>)<br></pre> Коды свойств
	* соответствуют кодам свойств, возвращаемым методом <a
	* href="http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetDocumentFields.php">GetDocumentFields</a>.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>public function CreateDocument($pid, $arFields)<br>{<br>	$iblockElement = new CIBlockElement();<br>	$id = $iblockElement-&gt;Add($arFields);<br>	if (!$id || $id &lt;= 0)<br>		throw new Exception($iblockElement-&gt;LAST_ERROR);<br>	return $id;<br>}<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/CreateDocument.php
	* @author Bitrix
	*/
	public static 	static public function CreateDocument($parentDocumentId, $arFields);

	/**
	* Метод изменяет свойства (поля) указанного документа на указанные значения.
	*
	* @param string $documentId - код документа.
	* @param array $arFields - массив новых значений свойств документа в виде array(код_свойства => значение, ...). Коды свойств соответствуют кодам свойств, возвращаемым методом GetDocumentFields.
	*/

	/**
	* <p>Метод изменяет свойства (поля) указанного документа на указанные значения.</p>
	*
	*
	* @param mixed $documentId  Код документа
	*
	* @param array $arFields  Массив новых значений свойств документа в виде <pre class="syntax">array(<br>
	* код_свойства =&gt; значение,<br> ...<br>)<br></pre> Коды свойств
	* соответствуют кодам свойств, возвращаемым методом GetDocumentFields.
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre>
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
	* Метод удаляет указанный документ.
	*
	* @param string $documentId - код документа.
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
	* <pre>
	* &lt;?<br>public function DeleteDocument($documentId)<br>{<br>	$documentId = intval($documentId);<br>	if ($documentId &lt;= 0)<br>		throw new CBPArgumentNullException("documentId");<br><br>	CIBlockElement::Delete($documentId);<br>}<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/DeleteDocument.php
	* @author Bitrix
	*/
	public static 	static public function DeleteDocument($documentId);

	/**
	* Метод публикует документ. То есть делает его доступным в публичной части сайта.
	*
	* @param string $documentId - код документа.
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
	public static static public function PublishDocument($documentId);

	/**
	* Метод снимает документ с публикации. То есть делает его недоступным в публичной части сайта.
	*
	* @param string $documentId - код документа.
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
	static 	static public function UnpublishDocument($documentId);

	/**
	* Метод блокирует указанный документ для указанного рабочего потока. Заблокированый документ может изменяться только указанным рабочим потоком.
	*
	* @param string $documentId - код документа
	* @param string $workflowId - код рабочего потока
	* @return bool - если удалось заблокировать документ, то возвращается true, иначе - false.
	*/
	
	/**
	* <p>Метод блокирует указанный документ для указанного бизнес-процесса. Заблокированный документ может изменяться только указанным бизнес-процессом. Если удалось заблокировать документ, то возвращается true, иначе – false.</p>
	*
	*
	* @param mixed $documentId  Код документа
	*
	* @param string $workflowId  Код бизнес-процесса </htm
	*
	* @return bool 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/LockDocument.php
	* @author Bitrix
	*/
	public static static public function LockDocument($documentId, $workflowId);

	/**
	* Метод разблокирует указанный документ. При разблокировке вызываются обработчики события вида "Сущность_OnUnlockDocument", которым входящим параметром передается код документа.
	*
	* @param string $documentId - код документа
	* @param string $workflowId - код рабочего потока
	* @return bool - если удалось разблокировать документ, то возвращается true, иначе - false.
	*/

	/**
	* <p>Метод разблокирует указанный документ. При разблокировке вызываются обработчики события вида "Сущность_OnUnlockDocument", которым входящим параметром передается код документа. Если удалось разблокировать документ, то возвращается true, иначе - false.</p>
	*
	*
	* @param mixed $documentId  Код документа
	*
	* @param string $workflowId  Код бизнес-процесса </htm
	*
	* @return bool 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/UnlockDocument.php
	* @author Bitrix
	*/
	public static 	static public function UnlockDocument($documentId, $workflowId);

	/**
	* Метод проверяет, заблокирован ли указанный документ для указанного рабочего потока. Т.е. если для данного рабочего потока документ не доступен для записи из-за того, что он заблокирован другим рабочим потоком, то метод должен вернуть true, иначе - false.
	*
	* @param string $documentId - код документа
	* @param string $workflowId - код рабочего потока
	* @return bool
	*/

	/**
	* <p>Метод проверяет, заблокирован ли указанный документ для указанного бизнес-процесса. Т.е. если для данного бизнес-процесса документ не доступен для записи из-за того, что он заблокирован другим бизнес-процессом, то метод должен вернуть true, иначе - false.</p>
	*
	*
	* @param mixed $documentId  Код документа
	*
	* @param string $workflowId  Код бизнес-процесса </htm
	*
	* @return bool 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/IsDocumentLocked.php
	* @author Bitrix
	*/
	public static 	static public function IsDocumentLocked($documentId, $workflowId);

	/**
	* Метод проверяет права на выполнение операций над заданным документом. Проверяются операции 0 - просмотр данных рабочего потока, 1 - запуск рабочего потока, 2 - право изменять документ, 3 - право смотреть документ.
	*
	* @param int $operation - операция.
	* @param int $userId - код пользователя, для которого проверяется право на выполнение операции.
	* @param string $documentId - код документа, к которому применяется операция.
	* @param array $arParameters - ассициативный массив вспомогательных параметров. Используется для того, чтобы не рассчитывать заново те вычисляемые значения, которые уже известны на момент вызова метода. Стандартными являются ключи массива DocumentStates - массив состояний рабочих потоков данного документа, WorkflowId - код рабочего потока (если требуется проверить операцию на одном рабочем потоке). Массив может быть дополнен другими произвольными ключами.
	* @return bool
	*/

	/**
	* <p>Метод проверяет права на выполнение операций над заданным документом. Проверяются операции: </p> <ul> <li> <b>0</b> - просмотр данных бизнес-процесса, </li> <li> <b>1</b> - запуск бизнес-процесса, </li> <li> <b>2</b> - право изменять документ, </li> <li> <b>3</b> - право смотреть документ. </li> </ul> Если права есть, то возвращается true, иначе – false. <p></p>
	*
	*
	* @param int $operation  Операция</bod
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
	* Метод проверяет права на выполнение операций над документами заданного типа. Проверяются операции 4 - право изменять шаблоны рабочий потоков для данного типа документа.
	*
	* @param int $operation - операция.
	* @param int $userId - код пользователя, для которого проверяется право на выполнение операции.
	* @param string $documentId - код типа документа, к которому применяется операция.
	* @param array $arParameters - ассициативный массив вспомогательных параметров. Используется для того, чтобы не рассчитывать заново те вычисляемые значения, которые уже известны на момент вызова метода. Стандартными являются ключи массива DocumentStates - массив состояний рабочих потоков данного документа, WorkflowId - код рабочего потока (если требуется проверить операцию на одном рабочем потоке). Массив может быть дополнен другими произвольными ключами.
	* @return bool
	*/

	/**
	* <p>Метод проверяет права на выполнение операций над документами заданного типа. Проверяются операции: </p> <ul> <li> <b>2</b> - право изменять документ, </li> <li> <b>4</b> - право изменять шаблоны бизнес-процессов для данного типа документа. </li> </ul> Если права есть, то возвращается true, иначе – false. <p></p>
	*
	*
	* @param int $operation  Операция</bod
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
	public static 	static public function CanUserOperateDocumentType($operation, $userId, $documentType, $arParameters = array());

	/**
	* Метод по коду документа возвращает ссылку на страницу документа в административной части.
	*
	* @param string $documentId - код документа.
	* @return string - ссылка на страницу документа в административной части.
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
	public static 	static public function GetDocumentAdminPage($documentId);

	/**
	* Метод возвращает массив произвольной структуры, содержащий всю информацию о документе. По этому массиву документ восстановливается методом RecoverDocumentFromHistory.
	*
	* @param string $documentId - код документа.
	* @return array - массив документа.
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
	public static 	static public function GetDocumentForHistory($documentId, $historyIndex);

	/**
	* Метод восстанавливает указанный документ из массива. Массив создается методом RecoverDocumentFromHistory.
	*
	* @param string $documentId - код документа.
	* @param array $arDocument - массив.
	*/

	/**
	* <p>Метод восстанавливает указанный документ из массива. Массив создается методом <a href="http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetDocumentForHistory.php">GetDocumentForHistory</a>.</p>
	*
	*
	* @param mixed $documentId  Код документа
	*
	* @param array $arDocument  Массив документа </ht
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/RecoverDocumentFromHistory.php
	* @author Bitrix
	*/
	public static 	static public function RecoverDocumentFromHistory($documentId, $arDocument);

	// array("read" => "Ета чтение", "write" => "Ета запысь")

	/**
	* <p>Метод для типа документа возвращает массив доступных операций в виде </p> <pre class="syntax">array(<br> "код_операции" =&gt; "название_операции_на_текущем_языке",<br> . . .<br>)</pre> <p></p>
	*
	*
	* @param mixed $documentType  Код типа документа </htm
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetAllowableOperations.php
	* @author Bitrix
	*/
	public static 	static public function GetAllowableOperations($documentType);
	// array("1" => "Админы", 2 => "Гости", 3 => ..., "Author" => "Афтар")

	/**
	* <p>Метод для типа документа возвращает массив возможных групп пользователей в виде </p> <pre class="syntax">array(<br> "код_группы" =&gt; "название_группы_на_текущем_языке",<br> . . .<br>)</pre> <p></p>
	*
	*
	* @param mixed $documentType  Код типа документа </htm
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/bizproc/interface/IBPWorkflowDocument/GetAllowableUserGroups.php
	* @author Bitrix
	*/
	public static 	static public function GetAllowableUserGroups($documentType);

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
	public static 	static public function GetUsersFromUserGroup($group, $documentId);
}
?>