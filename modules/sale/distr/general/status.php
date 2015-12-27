<?

use	Bitrix\Sale\Internals\StatusTable,
	Bitrix\Sale\Internals\StatusLangTable,
	Bitrix\Sale\Internals\StatusGroupTaskTable,
	Bitrix\Sale\Internals\OrderTable,
	Bitrix\Sale\Compatible,
	Bitrix\Main\TaskTable,
	Bitrix\Main\OperationTable,
	Bitrix\Main\TaskOperationTable,
	Bitrix\Main\Application,
	Bitrix\Main\SystemException,
	Bitrix\Main\Entity\Result,
	Bitrix\Main\Localization\LanguageTable,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/** @deprecated */

/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalestatus/index.php
 * @author Bitrix
 * @deprecated
 */
class CSaleStatus
{
	
	/**
	* <p>Метод возвращает параметры статуса с кодом ID, включая языкозависимые параметры для языка strLang. Метод динамичный.</p>
	*
	*
	* @param string $ID  Код статуса заказа. </htm
	*
	* @param string $strLang = LANGUAGE_ID Язык, для которого возвращаются языкозависимые параметры. По
	* умолчанию используется текущий язык.
	*
	* @return array <p>Возвращается ассоциативный массив параметров статуса с
	* ключами:</p> <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th>
	* </tr> <tr> <td>ID</td> <td>Код статуса заказа.</td> </tr> <tr> <td>SORT</td> <td>Индекс
	* сортировки.</td> </tr> <tr> <td>LID</td> <td>Язык.</td> </tr> <tr> <td>NAME</td> <td>Название
	* статуса.</td> </tr> <tr> <td>DESCRIPTION</td> <td>Описание статуса.</td> </tr> </table> <p> 
	* </p<a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if ($arStatus = CSaleStatus::GetByID($STATUS_ID))
	* {
	*    echo "&lt;pre&gt;";
	*    print_r($arStatus);
	*    echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalestatus/csalestatus__getbyid.bfbe15e3.php
	* @author Bitrix
	*/
	public static function GetByID($statusId, $languageId = LANGUAGE_ID)
	{
		return StatusTable::getList(array(
			'select' => array(
				'ID',
				'SORT',
				'LID' => 'Bitrix\Sale\Internals\StatusLangTable:STATUS.LID',
				'NAME' => 'Bitrix\Sale\Internals\StatusLangTable:STATUS.NAME',
				'DESCRIPTION' => 'Bitrix\Sale\Internals\StatusLangTable:STATUS.DESCRIPTION'
			),
			'filter' => array(
				'=ID' => $statusId,
				'=Bitrix\Sale\Internals\StatusLangTable:STATUS.LID' => $languageId,
				'=TYPE' => 'O'
			),
			'limit'  => 1,
		))->fetch();
	}

	public static function GetLangByID($statusId, $languageId = LANGUAGE_ID)
	{
		return StatusLangTable::getList(array(
			'select' => array('*'),
			'filter' => array('=STATUS_ID' => $statusId, '=LID' => $languageId),
			'limit'  => 1,
		))->fetch();
	}

	/**
	 * @param array $arOrder
	 * @param array $arFilter
	 * @param bool|array $arGroupBy
	 * @param bool|array $arNavStartParams
	 * @param array $arSelectFields
	 * @return CDBResult|int
	 */
	
	/**
	* <p>Метод возвращает результат выборки записей из статусов в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле статусов, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и
	* т.д.). <br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи статусов.
	* Массив имеет вид: <pre class="syntax">array(
	* "[модификатор1][оператор1]название_поля1" =&gt; "значение1",
	* "[модификатор2][оператор2]название_поля2" =&gt; "значение2", . . . )</pre>
	* Удовлетворяющие фильтру записи возвращаются в результате, а
	* записи, которые не удовлетворяют условиям фильтра,
	* отбрасываются.<br><br> Допустимыми являются следующие модификаторы:
	* <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> - значения null, 0 и пустая строка
	* так же удовлетворяют условиям фильтра.</li> </ul> Допустимыми
	* являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	* или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	* поля строго больше передаваемой в фильтр величины;</li> <li> <b>&lt;=</b> -
	* значение поля меньше или равно передаваемой в фильтр величины;</li>
	* <li> <b>&lt;</b> - значение поля строго меньше передаваемой в фильтр
	* величины;</li> <li> <b>@</b> - значение поля находится в передаваемом в
	* фильтр разделенном запятой списке значений;</li> <li> <b>~</b> - значение
	* поля проверяется на соответствие передаваемому в фильтр
	* шаблону;</li> <li> <b>%</b> - значение поля проверяется на соответствие
	* передаваемой в фильтр строке в соответствии с языком запросов.</li>
	* </ul> В качестве "название_поляX" может стоять любое поле типов
	* плательщика.<br><br> Пример фильтра: <pre class="syntax">array("LID" =&gt; "en")</pre> Этот
	* фильтр означает "выбрать все записи, в которых значение в поле LID
	* (код сайта) равно en".<br><br> Значение по умолчанию - пустой массив array()
	* - означает, что результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи статусов. Массив
	* имеет вид: <pre class="syntax">array("название_поля1", "группирующая_функция2"
	* =&gt; "название_поля2", ...)</pre> В качестве "название_поля<i>N</i>" может
	* стоять любое поле статусов. В качестве группирующей функции
	* могут стоять: <ul> <li> <b> COUNT</b> - подсчет количества;</li> <li> <b>AVG</b> -
	* вычисление среднего значения;</li> <li> <b>MIN</b> - вычисление
	* минимального значения;</li> <li> <b> MAX</b> - вычисление максимального
	* значения;</li> <li> <b>SUM</b> - вычисление суммы.</li> </ul> Этот фильтр
	* означает "выбрать все записи, в которых значение в поле LID (сайт
	* системы) не равно en".<br><br> Значение по умолчанию - <i>false</i> - означает,
	* что результат группироваться не будет.
	*
	* @param array $arNavStartParams = false Массив параметров выборки. Может содержать следующие ключи: <ul>
	* <li>"<b>nTopCount</b>" - количество возвращаемых методом записей будет
	* ограничено сверху значением этого ключа;</li> <li> любой ключ,
	* принимаемый методом <b> CDBResult::NavQuery</b> в качестве третьего
	* параметра.</li> </ul> Значение по умолчанию - <i>false</i> - означает, что
	* параметров выборки нет.
	*
	* @param array $arSelectFields = array() Массив полей записей, которые будут возвращены методом. Можно
	* указать только те поля, которые необходимы. Если в массиве
	* присутствует значение "*", то будут возвращены все доступные
	* поля.<br><br> Значение по умолчанию - пустой массив array() - означает,
	* что будут возвращены все поля основной таблицы запроса.
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий ассоциативные
	* массивы параметров статусов с ключами:</p> <table class="tnormal" width="100%"> <tr>
	* <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код статуса
	* заказа.</td> </tr> <tr> <td>SORT</td> <td>Индекс сортировки.</td> </tr> <tr> <td>LID</td>
	* <td>Язык.</td> </tr> <tr> <td>NAME</td> <td>Название статуса.</td> </tr> <tr> <td>DESCRIPTION</td>
	* <td>Описание статуса.</td> </tr> </table> <p> Если в качестве параметра arGroupBy
	* передается пустой массив, то метод вернет число записей,
	* удовлетворяющих фильтру.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalestatus/csalestatus__getlist.bbf47ed5.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		if (!is_array($arOrder) && !is_array($arFilter))
		{
			$arOrder = strval($arOrder);
			$arFilter = strval($arFilter);
			if ('' != $arOrder && '' != $arFilter)
				$arOrder = array($arOrder => $arFilter);
			else
				$arOrder = array();

			$arFilter = array();
			$arFilter["LID"] = LANGUAGE_ID;
			if ($arGroupBy)
			{
				$arGroupBy = strval($arGroupBy);
				if ('' != $arGroupBy)
					$arFilter["LID"] = $arGroupBy;
			}
			$arGroupBy = false;

			$arSelectFields = array("ID", "SORT", "LID", "NAME", "DESCRIPTION");
		}

		if (! in_array('TYPE', $arSelectFields))
			$arFilter['TYPE'] = 'O';

		$query = new Compatible\OrderQuery(StatusTable::getEntity());
		$query->addAliases(array(
			'LID'         => 'Bitrix\Sale\Internals\StatusLangTable:STATUS.LID',
			'NAME'        => 'Bitrix\Sale\Internals\StatusLangTable:STATUS.NAME',
			'DESCRIPTION' => 'Bitrix\Sale\Internals\StatusLangTable:STATUS.DESCRIPTION',
			'GROUP_ID'    => 'Bitrix\Sale\Internals\StatusGroupTaskTable:STATUS.GROUP_ID',
		));

		$taskIdName = 'Bitrix\Sale\Internals\StatusGroupTaskTable:STATUS.TASK_ID';
		CSaleStatusAdapter::addAliasesTo($query, $taskIdName);

		$query->prepare($arOrder, $arFilter, $arGroupBy, $arSelectFields);

		if ($query->counted())
		{
			return $query->exec()->getSelectedRowsCount();
		}
		else
		{
			$result = new Compatible\CDBResult();
			CSaleStatusAdapter::adaptResult($result, $query, $taskIdName);
			return $query->compatibleExec($result, $arNavStartParams);
		}
	}

	/*
	 * For modern api see: Bitrix\Sale\OrderStatus and Bitrix\Sale\DeliveryStatus
	 */
	public static function GetPermissionsList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		$query = new Compatible\OrderQuery(StatusGroupTaskTable::getEntity());

		$taskIdName = 'TASK_ID';
		CSaleStatusAdapter::addAliasesTo($query, $taskIdName);

		$query->prepare($arOrder, $arFilter, $arGroupBy, $arSelectFields);

		if ($query->counted())
		{
			return $query->exec()->getSelectedRowsCount();
		}
		else
		{
			$result = new Compatible\CDBResult();
			CSaleStatusAdapter::adaptResult($result, $query, $taskIdName);
			return $query->compatibleExec($result, $arNavStartParams);
		}
	}

	private static $statusFields, $langFields, $taskFields;

	public static function CheckFields($ACTION, &$arFields, $statusId = '')
	{
		if ((is_set($arFields, "SORT") || $ACTION=="ADD") && IntVal($arFields["SORT"])<= 0)
			$arFields["SORT"] = 100;

		if ((is_set($arFields, "ID") || $ACTION=="ADD") && strlen($arFields["ID"])<=0)
			return false;

		if (is_set($arFields, "ID") && strlen($statusId)>0 && $statusId!=$arFields["ID"])
			return false;

		if((is_set($arFields, "ID") && !preg_match("#[A-Za-z]#i", $arFields["ID"])) || (strlen($statusId)>0 && !preg_match("#[A-Za-z]#i", $statusId)))
		{
			$GLOBALS["APPLICATION"]->ThrowException(Loc::getMessage("SKGS_ID_NOT_SYMBOL"), "ERROR_ID_NOT_SYMBOL");
			return false;
		}

		$result = new Result;

		if (! self::$statusFields)
		{
			self::$statusFields = StatusTable::getEntity()->getScalarFields();
			self::$langFields   = StatusLangTable::getEntity()->getScalarFields();
			self::$taskFields   = StatusGroupTaskTable::getEntity()->getScalarFields();
		}

		switch ($ACTION)
		{
			case 'ADD':

				$statusId = $arFields['ID'];
				StatusTable::checkFields($result, null, array_intersect_key($arFields, self::$statusFields));

				break;

			case 'UPDATE':

				StatusTable::checkFields($result, $statusId, array_intersect_key(array_diff_key($arFields, array('ID'=>1)), self::$statusFields));

				break;

			default: throw new SystemException('Invalid action: '.$ACTION, 0, __FILE__, __LINE__);
		}

		if (isset($arFields['LANG']) && is_array($arFields['LANG']) && ! empty($arFields['LANG']))
		{
			$availableLanguages = array_map('current', LanguageTable::getList(array(
				'select' => array('LID'),
				'filter' => array('=ACTIVE' => 'Y')
			))->fetchAll());

			foreach ($arFields['LANG'] as $data)
			{
				if ($data['NAME'] && in_array($data['LID'], $availableLanguages))
					StatusLangTable::checkFields($result, null, array('STATUS_ID' => $statusId) + array_intersect_key($data, self::$langFields));
				else
					return false;
			}
		}

		return $result->isSuccess();
	}

	private static function addLanguagesBy($statusId, array $rows)
	{
		foreach ($rows as $row)
			StatusLangTable::add(array('STATUS_ID' => $statusId) + array_intersect_key($row, self::$langFields));
	}

	private static function addTasksBy($statusId, array $rows)
	{
		foreach ($rows as $row)
			StatusGroupTaskTable::add(array(
					'STATUS_ID' => $statusId,
					'TASK_ID' => CSaleStatusAdapter::getTaskId($row, CSaleStatusAdapter::permissions(),
						CSaleStatusAdapter::getTasksOperations()),
				) + array_intersect_key($row, self::$taskFields));
	}

	
	/**
	* <p>Метод добавляет новый статус заказа с параметрами из массива arFields. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров нового статуса. Ключами в
	* массиве являются названия параметров статуса, а значениями -
	* соответствующие значения.<br> Допустимые ключи: <ul> <li> <b>ID</b> - код
	* статуса (обязательный), состоит из одной буквы;</li> <li> <b>SORT</b> -
	* индекс сортировки;</li> <li> <b>LANG</b> - массив ассоциативных массивов
	* языкозависимых параметров статуса с ключами: <ul> <li> <b>LID</b> -
	* язык;</li> <li> <b>NAME</b> - название статуса на этом языке;</li> <li> <b>DESCRIPTION</b>
	* - описание статуса;</li> </ul> </li> <li> <b>PERMS</b> - массив ассоциативных
	* массивов прав на доступ к изменению заказа в данном статусе с
	* ключами: <ul> <li> <b>GROUP_ID</b> - группа пользователей;</li> <li> <b>PERM_TYPE</b> - тип
	* доступа (S - разрешен перевод заказа в данный статус, M - разрешено
	* изменение заказа в данном статусе).</li> </ul> </li> </ul>
	*
	* @return string <p>Возвращается код добавленного статуса или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalestatus/csalestatus__add.c7ce74b1.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		if (! self::CheckFields('ADD', $arFields))
			return false;

		$statusId = $arFields['ID'];

		foreach (GetModuleEvents("sale", "OnBeforeStatusAdd", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array($statusId, &$arFields))===false)
				return false;

		StatusTable::add(array_intersect_key($arFields, self::$statusFields));

		if (isset($arFields['LANG']) && is_array($arFields['LANG']) && ! empty($arFields['LANG']))
			self::addLanguagesBy($statusId, $arFields['LANG']);

		if (isset($arFields['PERMS']) && is_array($arFields['PERMS']) && ! empty($arFields['PERMS']))
			self::addTasksBy($statusId, $arFields['PERMS']);

		foreach (GetModuleEvents("sale", "OnStatusAdd", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($statusId, $arFields));

		return $statusId;
	}

	
	/**
	* <p>Метод изменяет параметры статуса заказа с кодом ID. Метод динамичный.</p>
	*
	*
	* @param string $ID  Код статуса.
	*
	* @param array $arFields  Ассоциативный массив новых параметров статуса. Ключами в массиве
	* являются названия параметров статуса, а значениями -
	* соответствующие значения.<br> Допустимые ключи: <ul> <li> <b>ID</b> - код
	* статуса (обязательный);</li> <li> <b>SORT</b> - индекс сортировки;</li> <li>
	* <b>LANG</b> - массив ассоциативных массивов языкозависимых параметров
	* статуса с ключами: <ul> <li> <b>LID</b> - язык;</li> <li> <b>NAME</b> - название
	* статуса на этом языке;</li> <li> <b>DESCRIPTION</b> - описание статуса;</li> </ul>
	* </li> <li> <b>PERMS</b> - массив ассоциативных массивов прав на доступ к
	* изменению заказа в данном статусе с ключами: <ul> <li> <b>GROUP_ID</b> -
	* группа пользователей;</li> <li> <b>PERM_TYPE</b> - тип доступа (S - разрешен
	* перевод заказа в данный статус, M - разрешено изменение заказа в
	* данном статусе).</li> </ul> </li> </ul>
	*
	* @return string <p>Возвращается код добавленного статуса или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalestatus/csalestatus__update.145077bd.php
	* @author Bitrix
	*/
	public static function Update($statusId, $arFields)
	{
		if (! self::CheckFields('UPDATE', $arFields, $statusId))
			return false;

		foreach (GetModuleEvents("sale", "OnBeforeStatusUpdate", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array($statusId, &$arFields))===false)
				return false;

		StatusTable::update($statusId, array_intersect_key($arFields, self::$statusFields));

		if (isset($arFields['LANG']) && is_array($arFields['LANG']) && ! empty($arFields['LANG']))
		{
			StatusLangTable::deleteByStatus($statusId);
			self::addLanguagesBy($statusId, $arFields['LANG']);
		}

		if (isset($arFields['PERMS']) && is_array($arFields['PERMS']) && ! empty($arFields['PERMS']))
		{
			StatusGroupTaskTable::deleteByStatus($statusId);
			self::addTasksBy($statusId, $arFields['PERMS']);
		}

		foreach (GetModuleEvents("sale", "OnStatusUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($statusId, $arFields));

		return $statusId;
	}

	
	/**
	* <p>Метод удаляет статус заказа с кодом ID. Если в базе есть заказы, находящиеся в этом статусе, то этот статус удалить нельзя. Метод динамичный.</p>
	*
	*
	* @param string $ID  Код статуса заказа. </htm
	*
	* @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	* противном случае.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalestatus/csalestatus__delete.11104aab.php
	* @author Bitrix
	*/
	public static function Delete($statusId)
	{
		if (! $statusId)
			return false;

		global $DB, $APPLICATION;
		$statusId = $DB->ForSql($statusId, 2);

		if (OrderTable::getList(array(
			'filter' => array('=STATUS_ID' => $statusId),
			'limit' => 1
		))->fetch())
		{
			$APPLICATION->ThrowException(Loc::getMessage("SKGS_ERROR_DELETE"), "ERROR_DELETE_STATUS_TO_ORDER");
			return false;
		}

		foreach (GetModuleEvents("sale", "OnBeforeStatusDelete", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array($statusId))===false)
				return false;

		foreach (GetModuleEvents("sale", "OnStatusDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($statusId));

		StatusLangTable::deleteByStatus($statusId);
		StatusGroupTaskTable::deleteByStatus($statusId);
		return StatusTable::delete($statusId)->isSuccess();
	}

	public static function CreateMailTemplate($ID)
	{
		$ID = trim($ID);

		if ($ID == '')
			return false;

		if (! self::GetByID($ID, LANGUAGE_ID))
			return false;

		$eventType = new CEventType();
		$eventMessage = new CEventMessage();

		$eventType->Delete("SALE_STATUS_CHANGED_".$ID);

		$dbSiteList = CSite::GetList(($b = ""), ($o = ""));
		while ($arSiteList = $dbSiteList->Fetch())
		{
			IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/status.php", $arSiteList["LANGUAGE_ID"]);
			$arStatusLang = self::GetLangByID($ID, $arSiteList["LANGUAGE_ID"]);

			$dbEventType = $eventType->GetList(
				array(
					"EVENT_NAME" => "SALE_STATUS_CHANGED_".$ID,
					"LID" => $arSiteList["LANGUAGE_ID"]
				)
			);
			if (!($arEventType = $dbEventType->Fetch()))
			{
				$str  = "";
				$str .= "#ORDER_ID# - ".Loc::getMessage("SKGS_ORDER_ID")."\n";
				$str .= "#ORDER_DATE# - ".Loc::getMessage("SKGS_ORDER_DATE")."\n";
				$str .= "#ORDER_STATUS# - ".Loc::getMessage("SKGS_ORDER_STATUS")."\n";
				$str .= "#EMAIL# - ".Loc::getMessage("SKGS_ORDER_EMAIL")."\n";
				$str .= "#ORDER_DESCRIPTION# - ".Loc::getMessage("SKGS_STATUS_DESCR")."\n";
				$str .= "#TEXT# - ".Loc::getMessage("SKGS_STATUS_TEXT")."\n";
				$str .= "#SALE_EMAIL# - ".Loc::getMessage("SKGS_SALE_EMAIL")."\n";

				$eventTypeID = $eventType->Add(
					array(
						"LID" => $arSiteList["LANGUAGE_ID"],
						"EVENT_NAME" => "SALE_STATUS_CHANGED_".$ID,
						"NAME" => Loc::getMessage("SKGS_CHANGING_STATUS_TO")." \"".$arStatusLang["NAME"]."\"",
						"DESCRIPTION" => $str
					)
				);
			}

			$dbEventMessage = $eventMessage->GetList(
				($b = ""),
				($o = ""),
				array(
					"EVENT_NAME" => "SALE_STATUS_CHANGED_".$ID,
					"SITE_ID" => $arSiteList["LID"]
				)
			);
			if (!($arEventMessage = $dbEventMessage->Fetch()))
			{
				$subject = Loc::getMessage("SKGS_STATUS_MAIL_SUBJ");

				$message  = Loc::getMessage("SKGS_STATUS_MAIL_BODY1");
				$message .= "------------------------------------------\n\n";
				$message .= Loc::getMessage("SKGS_STATUS_MAIL_BODY2");
				$message .= Loc::getMessage("SKGS_STATUS_MAIL_BODY3");
				$message .= "#ORDER_STATUS#\n";
				$message .= "#ORDER_DESCRIPTION#\n";
				$message .= "#TEXT#\n\n";
				$message .= "#SITE_NAME#\n";

				$arFields = Array(
					"ACTIVE" => "Y",
					"EVENT_NAME" => "SALE_STATUS_CHANGED_".$ID,
					"LID" => $arSiteList["LID"],
					"EMAIL_FROM" => "#SALE_EMAIL#",
					"EMAIL_TO" => "#EMAIL#",
					"SUBJECT" => $subject,
					"MESSAGE" => $message,
					"BODY_TYPE" => "text"
				);
				$eventMessageID = $eventMessage->Add($arFields);
			}
		}

		return true;
	}
}

/** @deprecated */
final class CSaleStatusAdapter implements Compatible\FetchAdapter
{
	// M I G R A T I O N ///////////////////////////////////////////////////////////////////////////////////////////////

	public static function perms2opers()
	{
		static $lazy = array(
			'PERM_VIEW'        => 'sale_status_view',
			'PERM_CANCEL'      => 'sale_status_cancel',
			'PERM_MARK'        => 'sale_status_mark',
			'PERM_DELIVERY'    => 'sale_status_delivery',
			'PERM_DEDUCTION'   => 'sale_status_deduction',
			'PERM_PAYMENT'     => 'sale_status_payment',
			'PERM_STATUS'      => 'sale_status_to',
			'PERM_UPDATE'      => 'sale_status_update',
			'PERM_DELETE'      => 'sale_status_delete',
			'PERM_STATUS_FROM' => 'sale_status_from',
		);
		return $lazy;
	}

	private static function field($name, array $field)
	{
		$field['NAME'] = $name;
		$field['MODULE_ID'] = 'sale';
		$field['BINDING'] = 'status';
		return $field;
	}

	public static function migrate()
	{
		$errors = '';

		// install all permissions as operations
		$permissions = array(/* permission name => operation id */);
		foreach (self::perms2opers() as $perm => $oper)
		{
			$result = OperationTable::add(self::field($oper, array()));
			if ($result->isSuccess())
				$permissions[$perm] = $result->getId();
			else
				$errors .= 'cannot add operation: '.$oper."\n".implode("\n", $result->getErrorMessages())."\n\n";
		}
		asort($permissions);

		// install system tasks
		$tasks = array(/* task id => array of operations ids */);
		try
		{
			$tasks[self::addTask(self::field('sale_status_none', array('SYS' => 'Y', 'LETTER' => 'D')), array())] = array();
		}
		catch (SystemException $e)
		{
			$errors .= $e->getMessage();
		}
		try
		{
			$tasks[self::addTask(self::field('sale_status_all', array('SYS' => 'Y', 'LETTER' => 'X')), $permissions)] = array_values($permissions);
		}
		catch (SystemException $e)
		{
			$errors .= $e->getMessage();
		}

		// migrate permissions to tasks
		$result = Application::getConnection()->query('SELECT * FROM b_sale_status2group');
		while ($row = $result->fetch())
		{
			try
			{
				$taskId = self::getTaskId($row, $permissions, $tasks);
				$res = StatusGroupTaskTable::add(array(
					'STATUS_ID' => $row['STATUS_ID'],
					'GROUP_ID'  => $row['GROUP_ID'],
					'TASK_ID'   => $taskId,
				));
				if (! $res->isSuccess())
					$errors .= 'cannot add status: '.$row['STATUS_ID'].', group: '.$row['GROUP_ID'].', task: '.$taskId."\n".implode("\n", $res->getErrorMessages())."\n\n";
			}
			catch (SystemException $e)
			{
				$errors .= $e->getMessage();
			}
		}

		if ($errors)
			throw new SystemException($errors, 0, __FILE__, __LINE__);
	}

	public static function getTaskId(array $data, array $permissions, array &$tasks)
	{
		$permissions = array_values(array_intersect_key($permissions, array_intersect($data, array('Y'))));
		if (! $taskId = array_search($permissions, $tasks))
		{
			$taskId = self::addTask(self::field('sale_status_custom'.(count($tasks) + 1), array('SYS' => 'N')), $permissions);
			$tasks[$taskId] = $permissions;
		}
		return $taskId;
	}

	public static function addTask(array $field, array $permissions)
	{
		// add task
		$result = TaskTable::add($field);
		if (! $result->isSuccess())
			throw new SystemException('cannot add task: '.$field['NAME']."\n".implode("\n", $result->getErrorMessages())."\n\n", 0, __FILE__, __LINE__);

		// add task-operations
		$errors = '';
		$taskId = $result->getId();
		foreach ($permissions as $operId)
		{
			$result = TaskOperationTable::add(array(
				'TASK_ID'      => $taskId,
				'OPERATION_ID' => $operId,
			));
			if (! $result->isSuccess())
				$errors .= 'cannot add task: '.$taskId.', operation: '.$operId."\n".implode("\n", $result->getErrorMessages())."\n\n";
		}
		if ($errors)
			throw new SystemException($errors, 0, __FILE__, __LINE__);

		return $taskId;
	}

	// A D A P T E R ///////////////////////////////////////////////////////////////////////////////////////////////////

	public static function permissions()
	{
		static $lazy = array();
		if (! $lazy)
		{
			$result = OperationTable::getList(array(
				'select' => array('ID', 'NAME'),
				'filter' => array('=MODULE_ID' => 'sale', '=BINDING' => 'status'),
			));

			$operations = array();
			while ($row = $result->fetch())
				$operations[$row['NAME']] = $row['ID'];

			foreach (self::perms2opers() as $perm => $oper)
				$lazy[$perm] = $operations[$oper];

			asort($lazy);
		}
		return $lazy; // permission name => operation id
	}

	public static function addFieldsTo(array &$fields, $statusIdName, $prefix)
	{
		$fields[$prefix.'GROUP_ID'] = array(
			'TYPE'  => 'int',
			'FIELD' => 'SSGT.GROUP_ID',
			'FROM'  => 'INNER JOIN b_sale_status_group_task SSGT ON (SSGT.STATUS_ID = '.$statusIdName.')',
		);
		foreach (self::permissions() as $name => $id)
			$fields[$prefix.$name] = array(
				'TYPE'  => 'char',
				'FIELD' => self::permExpression('SSGT.TASK_ID', $id),
				'FROM'  => 'INNER JOIN b_sale_status_group_task SSGT ON (SSGT.STATUS_ID = '.$statusIdName.')'
			);
	}

	public static function permExpression($taskIdName, $operationId)
	{
		return 'CASE WHEN EXISTS('.
			'SELECT 1 FROM b_task_operation SSTO WHERE SSTO.TASK_ID = '.$taskIdName.' AND SSTO.OPERATION_ID = '.$operationId.
		') THEN "Y" ELSE "N" END';
	}

	public static function addAliasesTo(Compatible\AliasedQuery $query, $taskIdName)
	{
		foreach (self::permissions() as $name => $id)
			$query->addAlias($name, array(
				'expression' => array(self::permExpression('%s', $id), $taskIdName),
			));
	}

	public static function adaptResult(Compatible\CDBResult $result, Compatible\OrderQuery $query, $taskIdName)
	{
		if (! ($query->grouped() || $query->aggregated()))
		{
			$select = $query->allSelected()
				? self::permissions()
				: array_intersect_key(self::permissions(), $query->getSelect());

			if ($select)
			{
				$query->setSelect(array_diff_key($query->getSelect(), $select));
				$query->addAlias('TASK_ID', $taskIdName);
				$query->addAliasSelect('TASK_ID');
				$result->addFetchAdapter(new self($select));
			}
		}
	}

	/** Get map of: task id => array of operations ids
	 */
	public static function getTasksOperations()
	{
		$result = TaskTable::getList(array(
			'select' => array(
				'TASK' => 'ID',
				'OPERATION' => 'Bitrix\Main\TaskOperationTable:TASK.OPERATION_ID',
			),
			'filter' => array(
				'=MODULE_ID' => 'sale',
				'=BINDING' => 'status',
			),
			'order'  => array(
				'Bitrix\Main\TaskOperationTable:TASK.OPERATION_ID' => 'ASC',
			),
		));

		$tasks = array();

		while ($row = $result->fetch())
		{
			if (! $tasks[$row['TASK']])
				$tasks[$row['TASK']] = array();
			if ($row['OPERATION'])
				$tasks[$row['TASK']][] = $row['OPERATION'];
		}

		return $tasks;
	}

	// I N S T A N C E /////////////////////////////////////////////////////////////////////////////////////////////////

	private $select, $tasks;

	private function __construct(array $select)
	{
		$this->select = $select;
		$this->tasks = self::getTasksOperations();
	}

	public function adapt(array $row)
	{
		$tasks = $this->tasks;
		$taskId = $row['TASK_ID'];

		foreach ($this->select as $perm => $operId)
		{
			$row[$perm] = $taskId
				? (in_array($operId, $tasks[$taskId]) ? 'Y' : 'N')
				: '';
		}

		unset($row['TASK_ID']);

		return $row;
	}
}
