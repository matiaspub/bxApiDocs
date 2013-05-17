<?

/**
 * <b>CIBlockElement</b> - класс для работы с элементами информационных блоков.
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/index.php
 * @author Bitrix
 */
class CIBlockElement extends CAllIBlockElement
{
	///////////////////////////////////////////////////////////////////
	// Function returns lock status of element (red, yellow, green)
	///////////////////////////////////////////////////////////////////
	public static function WF_GetLockStatus($ID, &$locked_by, &$date_lock)
	{
		global $DB, $USER;
		$err_mess = "FILE: ".__FILE__."<br> LINE:";
		$ID = intval($ID);
		$MAX_LOCK = intval(COption::GetOptionString("workflow","MAX_LOCK_TIME","60"));
		$uid = is_object($USER)? intval($USER->GetID()): 0;

		$strSql = "
			SELECT WF_LOCKED_BY,
				".$DB->DateToCharFunction("WF_DATE_LOCK")." WF_DATE_LOCK,
				if (WF_DATE_LOCK is null, 'green',
					if(DATE_ADD(WF_DATE_LOCK, interval $MAX_LOCK MINUTE)<now(), 'green',
						if(WF_LOCKED_BY=$uid, 'yellow', 'red'))) LOCK_STATUS
			FROM b_iblock_element
			WHERE ID = ".$ID."
		";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		$locked_by = $zr["WF_LOCKED_BY"];
		$date_lock = $zr["WF_DATE_LOCK"];
		return $zr["LOCK_STATUS"];
	}

	///////////////////////////////////////////////////////////////////
	// Locking element
	///////////////////////////////////////////////////////////////////
	public static function WF_Lock($LAST_ID, $bWorkFlow=true)
	{
		global $DB, $USER;
		$LAST_ID = intval($LAST_ID);
		$USER_ID = is_object($USER)? intval($USER->GetID()): 0;

		if ($bWorkFlow === true)
		{
			$strSql = "
				SELECT
					WF_PARENT_ELEMENT_ID
				FROM
					b_iblock_element
				WHERE
					ID = ".$LAST_ID."
			";
			$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
			$zr = $z->Fetch();
			if($zr)
			{
				$PARENT_ID = intval($zr["WF_PARENT_ELEMENT_ID"]);
				$DB->Query("
					UPDATE b_iblock_element
					SET
						WF_DATE_LOCK = ".$DB->GetNowFunction().",
						WF_LOCKED_BY = ".$USER_ID."
					WHERE
						ID in (".$LAST_ID.", ".$PARENT_ID.")
				", false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
			}
		}
		else
		{
			$DB->Query("
				UPDATE b_iblock_element
				SET
					WF_DATE_LOCK = ".$DB->GetNowFunction().",
					WF_LOCKED_BY = ".$USER_ID."
				WHERE
					ID = ".$LAST_ID,
			false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
		}
	}

	///////////////////////////////////////////////////////////////////
	// Unlock element
	///////////////////////////////////////////////////////////////////
	public static function WF_UnLock($LAST_ID, $bWorkFlow=true)
	{
		global $DB, $USER;
		$LAST_ID = intval($LAST_ID);
		$USER_ID = is_object($USER)? intval($USER->GetID()): 0;

		if ($bWorkFlow === true)
		{
			$strSql = "
				SELECT
					WF_PARENT_ELEMENT_ID,
					WF_LOCKED_BY
				FROM
					b_iblock_element
				WHERE
					ID = ".$LAST_ID."
			";
			$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
			$zr = $z->Fetch();
			if(
				$zr
				&& (
					$zr["WF_LOCKED_BY"]==$USER_ID
					|| (CModule::IncludeModule('workflow') && CWorkflow::IsAdmin())
				)
			)
			{
				$PARENT_ID = intval($zr["WF_PARENT_ELEMENT_ID"]);
				$DB->Query("
					UPDATE b_iblock_element
					SET
						WF_DATE_LOCK = null,
						WF_LOCKED_BY = null
					WHERE
						ID in (".$LAST_ID.", ".$PARENT_ID.")
						OR WF_PARENT_ELEMENT_ID = ".$PARENT_ID."
				", false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
			}
		}
		else
		{
			$DB->Query("
				UPDATE b_iblock_element
				SET
					WF_DATE_LOCK = null,
					WF_LOCKED_BY = null
				WHERE
					ID = ".$LAST_ID,
				false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
		}
	}

	///////////////////////////////////////////////////////////////////
	// List the history items
	///////////////////////////////////////////////////////////////////
	public static function WF_GetHistoryList($ELEMENT_ID, &$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = "FILE: ".__FILE__."<br>LINE: ";
		global $DB;
		$ELEMENT_ID = intval($ELEMENT_ID);
		$strSqlSearch = "";
		if(is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				if (strlen($val) <= 0 || $val == "NOT_REF")
					continue;
				$val = $DB->ForSql($val);
				$key = strtoupper($key);
				switch($key)
				{
				case "ID":
					$arr = explode(",",$val); $str = "";
					foreach ($arr as $a)
						$str .= intval($a).",";
					$strSqlSearch .= " and E.ID in (".$DB->ForSQL($str)."0)";
					break;
				case "TIMESTAMP_FROM":
					$strSqlSearch .= " and E.TIMESTAMP_X>=FROM_UNIXTIME('".MkDateTime(FmtDate($val,"D.M.Y"),"d.m.Y")."')";
					break;
				case "TIMESTAMP_TO":
					$strSqlSearch .= " and E.TIMESTAMP_X<=FROM_UNIXTIME('".MkDateTime(FmtDate($val,"D.M.Y")." 23:59:59","d.m.Y H:i:s")."')";
					break;
				case "MODIFIED_BY":
				case "MODIFIED_USER_ID":
					$strSqlSearch .= " and E.MODIFIED_BY='".intval($val)."'";
					break;
				case "IBLOCK_ID":
					$strSqlSearch .= " and E.IBLOCK_ID='".intval($val)."'";
					break;
				case "NAME":
					if($val!="%%")
						$strSqlSearch .= " and upper(E.NAME) like upper('".$DB->ForSQL($val,255)."')";
					break;
				case "STATUS":
				case "STATUS_ID":
					$strSqlSearch .= " and E.WF_STATUS_ID='".intval($val)."'";
					break;
				}
			}
		}

		if($by == "s_id")
			$strSqlOrder = "ORDER BY E.ID";
		elseif($by == "s_timestamp")
			$strSqlOrder = "ORDER BY E.TIMESTAMP_X";
		elseif($by == "s_modified_by")
			$strSqlOrder = "ORDER BY E.MODIFIED_BY";
		elseif($by == "s_name")
			$strSqlOrder = "ORDER BY E.NAME";
		elseif($by == "s_status")
			$strSqlOrder = "ORDER BY E.WF_STATUS_ID";
		else
		{
			$by = "s_id";
			$strSqlOrder = "ORDER BY E.ID";
		}

		if($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}

		$strSql = "
			SELECT
				E.*,
				".$DB->DateToCharFunction("E.TIMESTAMP_X")." TIMESTAMP_X,
				concat('(', U.LOGIN, ') ', ifnull(U.NAME,''), ' ', ifnull(U.LAST_NAME,'')) USER_NAME,
				S.TITLE STATUS_TITLE
			FROM
				b_iblock_element E
				INNER JOIN b_workflow_status S on S.ID = E.WF_STATUS_ID
				LEFT JOIN b_user U ON U.ID = E.MODIFIED_BY
			WHERE
				E.WF_PARENT_ELEMENT_ID = ".$ELEMENT_ID."
				".$strSqlSearch."
			".$strSqlOrder."
		";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (strlen($strSqlSearch)>0);
		return $res;
	}

	///////////////////////////////////////////////////////////////////
	// List of elements
	///////////////////////////////////////////////////////////////////
	
	/**
	 * <p>Возвращает список элементов по фильтру <i>arFilter</i>.</p> <p><b>Примечания</b>:</p> <ol> <li>Внутренние ограничения Oracle и MSSQL не позволяют использовать DISTINCT при фильтрации по полям типа blob, поэтому фильтрация по нескольким значениям множественного свойства может дать дублирование.   </li> <li>Поля перечисленные для сортировки будут автоматически добавлены в параметр arSelectFields или в arGroupBy, если указана группировка записей. <br> </li> </ol>
	 *
	 *
	 *
	 *
	 * @param array $arOrder = Array("SORT"=>"ASC") Массив вида Array(<i>by1</i>=&gt;<i>order1</i>[, <i>by2</i>=&gt;<i>order2</i> [, ..]]), где <i>by</i> -
	 * поле для сортировки, может принимать значения: <ul> <li> <b>id</b> - ID
	 * элемента; </li> <li> <b>sort</b> - индекс сортировки; </li> <li> <b>timestamp_x</b> - дата
	 * изменения; </li> <li> <b>name</b> - название; </li> <li> <b>active_from</b> или <span
	 * style="font-weight: bold;">date_active_from</span> - начало периода действия элемента;
	 * </li> <li> <b>active_to</b> или <span style="font-weight: bold;">date_active_to</span> - окончание
	 * периода действия элемента; </li> <li> <b>status</b> - код статуса элемента в
	 * документообороте; </li> <li> <b>code</b> - мнемонический код элемента; </li>
	 * <li> <b>iblock_id</b> - числовой код информационного блока; </li> <li> <b>modified_by</b>
	 * - код последнего изменившего пользователя; </li> <li> <b>active</b> - признак
	 * активности элемента; </li> <li> <i>show_counter </i>- количество показов
	 * элемента (учитывается функцией <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/index.php">CIBlockElement</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/counterinc.php">CounterInc</a>); </li> <li>
	 * <b>show_counter_start</b> - время первого показа элемента (учитывается
	 * функцией <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/index.php">CIBlockElement</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/counterinc.php">CounterInc</a>); </li> <li>
	 * <b>shows</b> - усредненное количество показов (количество показов /
	 * продолжительность показа); </li> <li> <b>rand</b> - случайный порядок;</li> <li>
	 * <span style="font-weight: bold;">xml_id</span> или <span style="font-weight: bold;">external_id</span> -
	 * внешний код;</li> <li> <span style="font-weight: bold;">tags</span> - теги;</li> <li> <span
	 * style="font-weight: bold;">created</span> - время создания;</li> <li> <span style="font-weight:
	 * bold;">created_date</span> - дата создания без учета времени;</li> <li> <span
	 * style="font-weight: bold;">cnt</span> - количество элементов (только при заданной
	 * группировке); <br> </li> <li> <b>property_&lt;PROPERTY_CODE&gt;</b> - по значению свойства
	 * с числовым или мнемоническим кодом <i>PROPERTY_CODE</i> (например, PROPERTY_123
	 * или PROPERTY_NEWS_SOURCE); </li> <li> <b>propertysort_&lt;PROPERTY_CODE&gt;</b> - по индексу
	 * сортировки варианта значения свойства. Только для свойств типа
	 * "Список" ; </li> <li> <b>catalog_&lt;CATALOG_FIELD&gt;_&lt;PRICE_TYPE&gt;</b> - по полю CATALOG_FIELD
	 * (может быть PRICE - цена или CURRENCY - валюта) из цены с типом <i>PRICE_TYPE</i>
	 * (например, catalog_PRICE_1 или CATALOG_CURRENCY_3);</li> <li> <span style="font-weight:
	 * bold;">PROPERTY_&lt;PROPERTY_CODE&gt;.&lt;FIELD&gt;</span> - по значению поля элемента
	 * указанного в качестве привязки. PROPERTY_CODE - мнемонический или
	 * символьный код свойства типа привязка к элементам. FIELD может
	 * принимать значения:</li> <ul> <li>ID <br> </li> <li>TIMESTAMP_X <br> </li> <li>MODIFIED_BY <br> </li>
	 * <li>CREATED <br> </li> <li>CREATED_DATE <br> </li> <li>CREATED_BY <br> </li> <li>IBLOCK_ID <br> </li> <li>ACTIVE <br>
	 * </li> <li>ACTIVE_FROM <br> </li> <li>ACTIVE_TO <br> </li> <li>SORT <br> </li> <li>NAME <br> </li> <li>SHOW_COUNTER <br>
	 * </li> <li>SHOW_COUNTER_START <br> </li> <li>CODE <br> </li> <li>TAGS <br> </li> <li>XML_ID <br> </li> <li>STATUS </li>
	 * </ul> <li> <span style="font-weight: bold;">PROPERTY_&lt;PROPERTY_CODE&gt;.PROPERTY_&lt;</span><span style="font-weight:
	 * bold;">PROPERTY_CODE2</span><span style="font-weight: bold;">&gt;</span> - по значению свойства
	 * элемента указанного в качестве привязки. PROPERTY_CODE - мнемонический
	 * или символьный код свойства типа привязки к элементам. PROPERTY_CODE2-
	 * код свойства связанных элементов. </li> <li> <b>HAS_PREVIEW_PICTURE</b> и
	 * <b>HAS_DETAIL_PICTURE</b> - сортировка по наличию и отсутствию картинок.</li> <li>
	 * <b>order</b> - порядок сортировки, может принимать значения: <ul> <li> <b>asc</b>
	 * - по возрастанию;</li> <li> <span style="font-weight: bold;">nulls,asc</span> - по возрастанию
	 * с пустыми значениями в начале выборки;</li> <li> <span style="font-weight:
	 * bold;">asc,nulls</span> - по возрастанию с пустыми значениями в конце
	 * выборки;</li> <li> <b>desc</b> - по убыванию;</li> <li> <span style="font-weight:
	 * bold;">nulls,desc</span> - по убыванию с пустыми значениями в начале
	 * выборки;</li> <li> <span style="font-weight: bold;">desc,nulls</span> - по убыванию с пустыми
	 * значениями в конце выборки; <br> </li> </ul> Необязательный. По
	 * умолчанию равен <i>Array("sort"=&gt;"asc")</i> </li> </ul>
	 *
	 *
	 *
	 * @param array $arFilter = Array() Массив вида array("фильтруемое поле"=&gt;"значения фильтра" [, ...]).
	 * "фильтруемое поле" может принимать значения: <ul> <li> <b>ID</b> - по
	 * числовому коду (<a href="http://dev.1c-bitrix.ruapi_help/iblock/filters/number.php">Число</a>); </li>
	 * <li> <b>ACTIVE</b> - фильтр по активности (Y|N); передача пустого значения
	 * (<i>"ACTIVE"=&gt;""</i>) выводит все элементы без учета их состояния (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/string_equal.php">Строка</a>); </li> <li> <b>NAME</b> - по
	 * названию (<a href="http://dev.1c-bitrix.ruapi_help/iblock/filters/string.php">Маска</a>); </li> <li>
	 * <b>CODE</b> - по мнемоническому идентификатору (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/string.php">Маска</a>); </li> <li> <b>TAGS</b> - по
	 * тегам (<a href="http://dev.1c-bitrix.ruapi_help/iblock/filters/string.php">Маска</a>); </li> <li>
	 * <b>XML_ID</b> или<b> EXTERNAL_ID</b> - по внешнему коду (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/string.php">Маска</a>); </li> <li> <b>PREVIEW_TEXT</b> - по
	 * анонсу (<a href="http://dev.1c-bitrix.ruapi_help/iblock/filters/string.php">Маска</a>); </li> <li>
	 * <b>PREVIEW_TEXT_TYPE</b> - по типу анонса (html|text, <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/string_equal.php">Строка</a>); </li> <li>
	 * <b>PREVIEW_PICTURE</b> - коду картинки для анонса (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/number.php">Число</a>); </li> <li> <b>DETAIL_TEXT</b> - по
	 * детальному описанию (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/string.php">Маска</a>); </li> <li> <b>DETAIL_TEXT_TYPE</b> -
	 * по типу детальному описания (html|text, <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/string_equal.php">Строка</a>); </li> <li>
	 * <b>DETAIL_PICTURE</b> - по коду детальной картинки (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/number.php">Число</a>); </li> <li> <b>CHECK_PERMISSIONS</b> -
	 * если установлен в "Y", то в выборке будет осуществляться проверка
	 * прав доступа к информационным блокам. По умолчанию права доступа
	 * не проверяются. </li> <li> <b>MIN_PERMISSION</b> - минимальный уровень доступа,
	 * будет обработан только если <b>CHECK_PERMISSIONS</b> установлен в "Y". По
	 * умолчанию "R". Список прав доступа см. в <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblock/index.php">CIBlock</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblock/setpermission.php">SetPermission</a>(). </li> <li>
	 * <b>SEARCHABLE_CONTENT</b> - по содержимому для поиска. Включает в себя
	 * название, описание для анонса и детальное описание (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/string.php">Маска</a>); </li> <li> <b>SORT</b> - по
	 * сортировке (<a href="http://dev.1c-bitrix.ruapi_help/iblock/filters/number.php">Число</a>); </li> <li>
	 * <b>TIMESTAMP_X</b> - по времени изменения (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/date.php">Дата</a>);</li> <li> <b>DATE_MODIFY_FROM</b> - по
	 * времени изменения. Будут выбраны элементы измененные после
	 * времени указанного в фильтре. Время указывается в формате сайта.
	 * Возможно использовать операцию отрицания "!DATE_MODIFY_FROM"; </li> <li>
	 * <b>DATE_MODIFY_TO</b> - по времени изменения. Будут выбраны элементы
	 * измененные ранее времени указанного в фильтре. Время указывается
	 * в формате сайта. Возможно использовать операцию отрицания
	 * "!DATE_MODIFY_TO";</li> <li> <b>MODIFIED_USER_ID </b>или<b> MODIFIED_BY</b> - по коду пользователя,
	 * изменившего элемент (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/number.php">Число</a>); </li> <li> <b>DATE_CREATE</b> - по
	 * времени создания (<a href="http://dev.1c-bitrix.ruapi_help/iblock/filters/date.php">Дата</a>); </li>
	 * <li> <b>CREATED_USER_ID </b>или<b> CREATED_BY</b> - по коду пользователя, добавившего
	 * элемент (<a href="http://dev.1c-bitrix.ruapi_help/iblock/filters/number.php">Число</a>); </li> <li>
	 * <b>DATE_ACTIVE_FROM</b> - по дате начала активности (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/date.php">Дата</a>) Формат даты должен
	 * соответствовать <a
	 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=28&amp;LESSON_ID=1499">формату даты</a>,
	 * установленному на сайте.; </li> <li> <b>DATE_ACTIVE_TO</b> - по дате окончания
	 * активности (<a href="http://dev.1c-bitrix.ruapi_help/iblock/filters/date.php">Дата</a>)Формат
	 * даты должен соответствовать <a
	 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=28&amp;LESSON_ID=1499">формату даты</a>,
	 * установленному на сайте.; </li> <li> <b>ACTIVE_DATE</b> - непустое значение
	 * задействует фильтр по датам активности. Будут выбраны активные
	 * по датам элементы.Если значение не установлено (<i>""</i>), фильтрация
	 * по датам активности не производится; </li> <li> <b>ACTIVE_FROM</b> -
	 * устаревший;</li> <li> <b>ACTIVE_TO</b> - устаревший;</li> <li> <b>IBLOCK_ID</b> - по коду
	 * информационного блока (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/number.php">Число</a>); </li> <li> <b>IBLOCK_CODE</b> - по
	 * мнемоническому коду информационного блока (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/string.php">Маска</a>); </li> <li> <b>IBLOCK_SITE_ID</b>
	 * или <span style="font-weight: bold;">IBLOCK_LID</span> или <span style="font-weight: bold;">SITE_ID</span> или
	 * <span style="font-weight: bold;">LID</span> - по сайту (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/string_equal.php">Строка</a>); </li> <li> <b>IBLOCK_TYPE</b>
	 * - по типу информационного блока (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/string.php">Маска</a>); </li> <li> <b>IBLOCK_ACTIVE</b> - по
	 * активности информационного блока (Y|N, <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/string_equal.php">Строка</a>); </li> <li> <b>SECTION_ID</b> -
	 * по родительской группе. Если значение фильтра false, "" или 0, то будут
	 * выбраны элементы не привязанные ни к каким разделам. Иначе будут
	 * выбраны элементы привязанные к заданному разделу. Значением
	 * фильтра может быть и массив. В этом случае будут выбраны элементы
	 * привязанные хотя бы к одному из разделов указанных в фильтре.
	 * Возможно указание отрицания "!". В этом случае условие будет
	 * инвертировано;</li> <li> <span style="font-weight: bold;">SECTION_CODE</span> - по
	 * мнемоническому коду родительской группы. Аналогично SECTION_ID; <br> </li>
	 * <li> <b>INCLUDE_SUBSECTIONS</b> - если задан фильтр по родительским группам
	 * <b>SECTION_ID</b>, то будут также выбраны элементы находящиеся в
	 * подгруппах этих групп;</li> <li> <span style="font-weight: bold;">SUBSECTION</span>  - по
	 * принадлежности к подразделам раздела. Значением фильтра может
	 * быть массив из двух элементов задающих левую и правую границу
	 * дерева разделов. Операция отрицания поддерживается. <br> </li> <li> <span
	 * style="font-weight: bold;">SECTION_ACTIVE</span> - если установлен в значение "Y", то
	 * проверяется активность групп к которым привязан элемент. Если
	 * естановлен "N", то будут выбраны элементы неактивных групп. <br> </li>
	 * <li> <span style="font-weight: bold;">SECTION_GLOBAL_ACTIVE</span> - аналогично предыдущему, но
	 * учитывается также активность родительских групп.</li> <li>
	 * <b>SHOW_COUNTER</b> - по количеству показов (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/number.php">Число</a>); </li> <li> <b>SHOW_COUNTER_START</b> -
	 * по времени первого показа (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/date.php">Дата</a>); </li> <li> <b>WF_COMMENTS</b> - по
	 * комментарию документооборота (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/string.php">Маска</a>); </li> <li> <b>WF_STATUS_ID</b> или
	 * <span style="font-weight: bold;">WF_STATUS</span> - по коду статуса документооборота (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/number.php">Число</a>); </li> <li> <b>SHOW_HISTORY</b> -
	 * если установлен в значение "Y", то вместе с элементами будут
	 * выводится и их архив (история), по умолчанию выводятся только
	 * опубликованные элементы; </li> <li> <b>SHOW_NEW</b> - если <b>SHOW_HISTORY</b> не
	 * установлен или не равен Y и <b>SHOW_NEW</b>=Y, то будут показываться ещё
	 * неопубликованные элементы вместе с опубликованными; </li> <li>
	 * <b>WF_PARENT_ELEMENT_ID</b> - по коду элемента-родителя в документообороте для
	 * выборки истории изменений (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/number.php">Число</a>); </li> <li> <b>WF_NEW</b> - флаг
	 * что элемент ещё ни разу не был опубликован (Y|N); </li> <li> <b>WF_LOCK_STATUS</b> -
	 * статус заблокированности элемента в документооборте (red|green|yellow);
	 * </li> <li> <b>PROPERTY_&lt;PROPERTY_CODE</b><b>&gt;</b> - фильтр по значениям свойств, где
	 * PROPERTY_CODE - код свойства или мнемонический код. Для свойств типа
	 * "Список", "Число", "Привязка к элементам" и "Привязка к разделам"  - <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/number.php">Число</a>. Для прочих - <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/string.php">Маска</a>; </li> <li> <b style="font-weight:
	 * bold;">PROPERTY_&lt;</b><b>PROPERTY_CODE<span style="font-weight: bold;">&gt;_VALUE</span></b> - фильтр по
	 * значениям списка для свойств типа "список" (<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/filters/string.php">Маска</a>), поиск будет
	 * осуществляться по строковому значению списка, а не по
	 * идентификатору; </li> <li> <b>CATALOG_&lt;CATALOG_FIELD&gt;_&lt;PRICE_TYPE&gt;</b> - по полю
	 * <i>CATALOG_FIELD</i> из цены типа <i>PRICE_TYPE</i> (ID типа цены), где <i>CATALOG_FIELD</i>
	 * может быть: PRICE - цена, CURRENCY - валюта.</li> <li> <span style="font-weight:
	 * bold;">PROPERTY_&lt;PROPERTY_CODE&gt;.&lt;FIELD&gt;</span> - фильтр по значениям полей
	 * связанных элементов. , где PROPERTY_CODE - ID или мнемонический код
	 * свойства привязки, а FIELD - поле указанного в привязке элемента. FIELD
	 * может принимать следующие значения: ACTIVE, DETAIL_TEXT_TYPE, PREVIEW_TEXT_TYPE,
	 * EXTERNAL_ID, NAME, XML_ID, TMP_ID, DETAIL_TEXT, SEARCHABLE_CONTENT, PREVIEW_TEXT, CODE, TAGS, WF_COMMENTS, ID,
	 * SHOW_COUNTER, WF_PARENT_ELEMENT_ID, WF_STATUS_ID, SORT, CREATED_BY, PREVIEW_PICTURE, DETAIL_PICTURE, IBLOCK_ID,
	 * TIMESTAMP_X, DATE_CREATE, SHOW_COUNTER_START, DATE_ACTIVE_FROM, DATE_ACTIVE_TO, ACTIVE_FROM, ACTIVE_TO, ACTIVE_DATE,
	 * DATE_MODIFY_FROM, DATE_MODIFY_TO, MODIFIED_USER_ID, MODIFIED_BY, CREATED_USER_ID, CREATED_BY. Правила
	 * фильтров идентичны тем, которые описаны выше.</li> </ul> Перед
	 * названием фильтруемого поля можно указать тип проверки фильтра:
	 * <ul> <li>"!" - не равно </li> <li>"&lt;" - меньше </li> <li>"&lt;=" - меньше либо равно
	 * </li> <li>"&gt;" - больше </li> <li>"&gt;=" - больше либо равно</li> <li>"&gt;&lt;" -
	 * между</li> <li>и т.д. <br> </li> </ul> <i>Значения фильтра</i> - одиночное
	 * значение или массив значений. Для исключения пустых значений
	 * необходимо использовать <i>false</i>. <br><br> Необязательное. По
	 * умолчанию записи не фильтруются. 
	 *
	 *
	 *
	 * @param mixed $arGroupBy = false Массив полей для группировки элемента. Если поля указаны, то
	 * выборка по ним группируется (при этом параметр arSelectFields будет
	 * проигнорирован), а в результат добавляется поле CNT - количество
	 * сгруппированных элементов. Если указать в качестве arGroupBy пустой
	 * массив, то функция вернет количество элементов CNT по фильтру.
	 * Группировать можно по полям элемента, а также по значениям его
	 * свойств. Для этого в качестве одного из полей группировки
	 * необходимо указать <i>PROPERTY_&lt;PROPERTY_CODE&gt;</i>, где PROPERTY_CODE - код
	 * свойства или мнемонический код. <br> Необязательное. По умолчанию
	 * false - записи не группируются.
	 *
	 *
	 *
	 * @param mixed $arNavStartParams = false Параметры для постраничной навигации и ограничения количества
	 * выводимых элементов. массив вида "Название
	 * параметра"=&gt;"Значение", где название параметра <br> "nTopCount" -
	 * ограничить количество сверху <br> "bShowAll" - разрешить вывести все
	 * элементы при постраничной навигации <br> "iNumPage" - номер страницы при
	 * постраничной навигации <br> "nPageSize" - количество элементов на
	 * странице при постраничной навигации <br> "nElementID" - ID элемента
	 * который будет выбран вместе со своими соседями. Количество
	 * соседей определяется параметром nPageSize. Например: если nPageSize равно
	 * 2-м, то будут выбраны максимум 5-ть элементов.  Соседи определяются
	 * порядком сортировки заданным в параметре arOrder (см. выше) . <br> При
	 * этом действуют следующие ограничения: <br><ul> <li>Если элемент с
	 * таким ID отсутствует в выборке, то результат будет не определен.</li>
	 * <li>nElementID не работает, если задана группировка (см. параметр arGroupBy
	 * выше).</li> <li>в параметре arSelect обязательно должено присутствовать
	 * поле "ID".</li> <li>обязательно должна быть задана сортировка arOrder.</li>
	 * <li>поля в сортировке catalog_* не учитываются и результат выборки
	 * становится не определенным.</li> <li>в выборку добавляется поле RANK -
	 * порядковый номер элемента в "полной" выборке. <br> </li> </ul>
	 * Необязательное. По умолчанию <i>false</i> - не ограничивать выводимые
	 * элементы. <br> Если передать в параметр <i>arNavStartParams</i> пустой массив,
	 * то ставится ограничение на 10 выводимых элементов. <br>
	 *
	 *
	 *
	 * @param array $arSelectFields = Array() Массив возвращаемых полей элемента. Список полей элемента, а
	 * также можно сразу выводить значения его свойств. Обязательно
	 * должно быть использовано поле IBLOCK_ID или ID, иначе не будет работать
	 * корректно. Кроме того, также  в качестве одного из полей
	 * необходимо указать <i>PROPERTY_&lt;PROPERTY_CODE&gt;</i>, где PROPERTY_CODE - ID или
	 * мнемонический код. В результате будет выведены значения свойств
	 * элемента в виде полей <i>PROPERTY_&lt;PROPERTY_CODE&gt;_VALUE</i> - значение;
	 * <i>PROPERTY_&lt;PROPERTY_CODE&gt;_ID</i> - код значения у элемента;
	 * <i>PROPERTY_&lt;PROPERTY_CODE&gt;_ENUM_ID</i> - код значения (для свойств типа список).
	 * <br> При установленном модуле торгового каталога можно выводить и
	 * цены элемента. Для этого в качестве одного из полей необходимо
	 * указать <i>CATALOG_GROUP_&lt;PRICE_CODE&gt;</i>, где PRICE_CODE - ID типа цены. <br> Так же
	 * есть возможность выбрать поля элементов по значениям свойства
	 * типа "Привязка к элементам". Для этого необходимо указать 
	 * <i>PROPERTY_&lt;PROPERTY_CODE&gt;.&lt;FIELD&gt;</i>, где PROPERTY_CODE - ID или мнемонический код
	 * свойства привязки, а FIELD - поле указанного в привязке элемента. См.
	 * ниже "Поля связанных элементов для сортировки". <br> Можно выбрать и
	 * значения свойств элементов по значениям свойства типа "Привязка
	 * к элементам". Для этого необходимо указать 
	 * <i>PROPERTY_&lt;PROPERTY_CODE&gt;.</i><i>PROPERTY_&lt;PROPERTY_CODE2&gt;</i>, где PROPERTY_CODE - ID или
	 * мнемонический код свойства привязки, а PROPERTY_CODE2 - свойство
	 * указанного в привязке элемента. <br><br> По умолчанию выводить все
	 * поля. <br><br><b>Примечание 1</b>: если в массиве используются свойство,
	 * являющееся множественным, то для элементов, где используются
	 * несколько значений этого свойства, будет возвращено несколько
	 * записей вместо одной. Для решения этой проблемы инфоблоки нужно
	 * перевести в <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2723"
	 * >Режим хранения свойств в отдельных таблицах</a>, в этом случае для
	 * свойства будет отдаваться массив значений. Либо можно не
	 * указывать свойства в параметрах выборки, а получать их значения
	 * на каждом шаге перебора выборки с помощью _CIBElement::GetProperties().
	 * <br><br><b>Примечание 2</b>: Если в массиве указаны поля DETAIL_PAGE_URL,
	 * SECTION_PAGE_URL или LIST_PAGE_URL, то поля необходимые для правильной
	 * подстановки шаблонов URL'ов будут выбраны автоматически. Но только
	 * если не была задана группировка. <p><b>Примечание 3</b>: если
	 * необходимо выбрать данные о рейтингах для выбранных элементов,
	 * то для этого в массиве необходимо указать следующие <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/general/ratings/rating_vote.php">поля</a>: RATING_TOTAL_VALUE,
	 * RATING_TOTAL_VOTES, RATING_TOTAL_POSITIVE_VOTES, RATING_TOTAL_NEGATIVE_VOTES, RATING_USER_VOTE_VALUE.</p>
	 *
	 *
	 *
	 * @return CIBlockResult <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockresult/index.php">CIBlockResult</a>
	 *
	 *
	 * <h4>See Also</h4> 
	 * <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a> </li>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php
	 * @author Bitrix
	 */
	public static function GetList($arOrder=Array("SORT"=>"ASC"), $arFilter=Array(), $arGroupBy=false, $arNavStartParams=false, $arSelectFields=Array())
	{
		/*
		Filter combinations:
		CHECK_PERMISSIONS="N" - check permissions of the current user to the infoblock
			MIN_PERMISSION="R" - when permissions check, then minimal access level
		SHOW_HISTORY="N" - add history items to list
			SHOW_NEW="N" - if not add history items, then add new, but not published elements
		*/
		global $DB, $USER, $APPLICATION;
		$MAX_LOCK = intval(COption::GetOptionString("workflow","MAX_LOCK_TIME","60"));
		$uid = is_object($USER)? intval($USER->GetID()): 0;

		$arIblockElementFields = Array(
				"ID"=>"BE.ID",
				"TIMESTAMP_X"=>$DB->DateToCharFunction("BE.TIMESTAMP_X"),
				"TIMESTAMP_X_UNIX"=>'UNIX_TIMESTAMP(BE.TIMESTAMP_X)',
				"MODIFIED_BY"=>"BE.MODIFIED_BY",
				"DATE_CREATE"=>$DB->DateToCharFunction("BE.DATE_CREATE"),
				"DATE_CREATE_UNIX"=>'UNIX_TIMESTAMP(BE.DATE_CREATE)',
				"CREATED_BY"=>"BE.CREATED_BY",
				"IBLOCK_ID"=>"BE.IBLOCK_ID",
				"IBLOCK_SECTION_ID"=>"BE.IBLOCK_SECTION_ID",
				"ACTIVE"=>"BE.ACTIVE",
				"ACTIVE_FROM"=>(
						CPageOption::GetOptionString("iblock", "FORMAT_ACTIVE_DATES", "-")!="-"
						?
							$DB->DateToCharFunction("BE.ACTIVE_FROM", CPageOption::GetOptionString("iblock", "FORMAT_ACTIVE_DATES", "SHORT"))
						:
							"IF(EXTRACT(HOUR_SECOND FROM BE.ACTIVE_FROM)>0, ".$DB->DateToCharFunction("BE.ACTIVE_FROM", "FULL").", ".$DB->DateToCharFunction("BE.ACTIVE_FROM", "SHORT").")"
						),
				"ACTIVE_TO"=>(
						CPageOption::GetOptionString("iblock", "FORMAT_ACTIVE_DATES", "-")!="-"
						?
							$DB->DateToCharFunction("BE.ACTIVE_TO", CPageOption::GetOptionString("iblock", "FORMAT_ACTIVE_DATES", "SHORT"))
						:
							"IF(EXTRACT(HOUR_SECOND FROM BE.ACTIVE_TO)>0, ".$DB->DateToCharFunction("BE.ACTIVE_TO", "FULL").", ".$DB->DateToCharFunction("BE.ACTIVE_TO", "SHORT").")"
						),
				"DATE_ACTIVE_FROM"=>(
						CPageOption::GetOptionString("iblock", "FORMAT_ACTIVE_DATES", "-")!="-"
						?
							$DB->DateToCharFunction("BE.ACTIVE_FROM", CPageOption::GetOptionString("iblock", "FORMAT_ACTIVE_DATES", "SHORT"))
						:
							"IF(EXTRACT(HOUR_SECOND FROM BE.ACTIVE_FROM)>0, ".$DB->DateToCharFunction("BE.ACTIVE_FROM", "FULL").", ".$DB->DateToCharFunction("BE.ACTIVE_FROM", "SHORT").")"
						),
				"DATE_ACTIVE_TO"=>(
						CPageOption::GetOptionString("iblock", "FORMAT_ACTIVE_DATES", "-")!="-"
						?
							$DB->DateToCharFunction("BE.ACTIVE_TO", CPageOption::GetOptionString("iblock", "FORMAT_ACTIVE_DATES", "SHORT"))
						:
							"IF(EXTRACT(HOUR_SECOND FROM BE.ACTIVE_TO)>0, ".$DB->DateToCharFunction("BE.ACTIVE_TO", "FULL").", ".$DB->DateToCharFunction("BE.ACTIVE_TO", "SHORT").")"
						),
				"SORT"=>"BE.SORT",
				"NAME"=>"BE.NAME",
				"PREVIEW_PICTURE"=>"BE.PREVIEW_PICTURE",
				"PREVIEW_TEXT"=>"BE.PREVIEW_TEXT",
				"PREVIEW_TEXT_TYPE"=>"BE.PREVIEW_TEXT_TYPE",
				"DETAIL_PICTURE"=>"BE.DETAIL_PICTURE",
				"DETAIL_TEXT"=>"BE.DETAIL_TEXT",
				"DETAIL_TEXT_TYPE"=>"BE.DETAIL_TEXT_TYPE",
				"SEARCHABLE_CONTENT"=>"BE.SEARCHABLE_CONTENT",
				"WF_STATUS_ID"=>"BE.WF_STATUS_ID",
				"WF_PARENT_ELEMENT_ID"=>"BE.WF_PARENT_ELEMENT_ID",
				"WF_LAST_HISTORY_ID"=>"BE.WF_LAST_HISTORY_ID",
				"WF_NEW"=>"BE.WF_NEW",
				"LOCK_STATUS"=>"if (BE.WF_DATE_LOCK is null, 'green', if(DATE_ADD(BE.WF_DATE_LOCK, interval ".$MAX_LOCK." MINUTE)<now(), 'green', if(BE.WF_LOCKED_BY=".$uid.", 'yellow', 'red')))",
				"WF_LOCKED_BY"=>"BE.WF_LOCKED_BY",
				"WF_DATE_LOCK"=>$DB->DateToCharFunction("BE.WF_DATE_LOCK"),
				"WF_COMMENTS"=>"BE.WF_COMMENTS",
				"IN_SECTIONS"=>"BE.IN_SECTIONS",
				"SHOW_COUNTER"=>"BE.SHOW_COUNTER",
				"SHOW_COUNTER_START"=>"BE.SHOW_COUNTER_START",
				"CODE"=>"BE.CODE",
				"TAGS"=>"BE.TAGS",
				"XML_ID"=>"BE.XML_ID",
				"EXTERNAL_ID"=>"BE.XML_ID",
				"TMP_ID"=>"BE.TMP_ID",
				"USER_NAME"=>"concat('(',U.LOGIN,') ',ifnull(U.NAME,''),' ',ifnull(U.LAST_NAME,''))",
				"LOCKED_USER_NAME"=>"concat('(',UL.LOGIN,') ',ifnull(UL.NAME,''),' ',ifnull(UL.LAST_NAME,''))",
				"CREATED_USER_NAME"=>"concat('(',UC.LOGIN,') ',ifnull(UC.NAME,''),' ',ifnull(UC.LAST_NAME,''))",
				"LANG_DIR"=>"L.DIR",
				"LID"=>"B.LID",
				"IBLOCK_TYPE_ID"=>"B.IBLOCK_TYPE_ID",
				"IBLOCK_CODE"=>"B.CODE",
				"IBLOCK_NAME"=>"B.NAME",
				"IBLOCK_EXTERNAL_ID"=>"B.XML_ID",
				"DETAIL_PAGE_URL"=>"B.DETAIL_PAGE_URL",
				"LIST_PAGE_URL"=>"B.LIST_PAGE_URL",
				"CREATED_DATE"=>$DB->DateFormatToDB("YYYY.MM.DD", "BE.DATE_CREATE"),
				"BP_PUBLISHED"=>"if(BE.WF_STATUS_ID = 1, 'Y', 'N')",
			);

		$bDistinct = false;

		CIBlockElement::PrepareGetList(
				$arIblockElementFields,
				$arJoinProps,
				$bOnlyCount,
				$bDistinct,

				$arSelectFields,
				$sSelect,
				$arAddSelectFields,

				$arFilter,
				$sWhere,
				$sSectionWhere,
				$arAddWhereFields,

				$arGroupBy,
				$sGroupBy,

				$arOrder,
				$arSqlOrder,
				$arAddOrderByFields,

				$arIBlockFilter,
				$arIBlockMultProps,
				$arIBlockConvProps,
				$arIBlockAllProps,
				$arIBlockNumProps,
				$arIBlockLongProps
			);

		$arFilterIBlocks = isset($arFilter["IBLOCK_ID"])? array($arFilter["IBLOCK_ID"]): array();
		//******************FROM PART********************************************
		$sFrom = "";
		foreach($arJoinProps["FPS"] as $iblock_id => $iPropCnt)
		{
			$sFrom .= "\t\t\tINNER JOIN b_iblock_element_prop_s".$iblock_id." FPS".$iPropCnt." ON FPS".$iPropCnt.".IBLOCK_ELEMENT_ID = BE.ID\n";
			$arFilterIBlocks[$iblock_id] = $iblock_id;
		}

		foreach($arJoinProps["FP"] as $propID => $db_prop)
		{
			$i = $db_prop["CNT"];

			if($db_prop["bFullJoin"])
				$sFrom .= "\t\t\tINNER JOIN b_iblock_property FP".$i." ON FP".$i.".IBLOCK_ID = B.ID AND ".
					(
						IntVal($propID)>0?
						" FP".$i.".ID=".IntVal($propID)."\n":
						" FP".$i.".CODE='".$DB->ForSQL($propID, 200)."'\n"
					);
			else
				$sFrom .= "\t\t\tLEFT JOIN b_iblock_property FP".$i." ON FP".$i.".IBLOCK_ID = B.ID AND ".
					(
						IntVal($propID)>0?
						" FP".$i.".ID=".IntVal($propID)."\n":
						" FP".$i.".CODE='".$DB->ForSQL($propID, 200)."'\n"
					);

			if($db_prop["IBLOCK_ID"])
				$arFilterIBlocks[$db_prop["IBLOCK_ID"]] = $db_prop["IBLOCK_ID"];
		}

		foreach($arJoinProps["FPV"] as $propID => $db_prop)
		{
			$i = $db_prop["CNT"];

			if($db_prop["MULTIPLE"]=="Y")
				$bDistinct = true;

			if($db_prop["VERSION"]==2)
				$strTable = "b_iblock_element_prop_m".$db_prop["IBLOCK_ID"];
			else
				$strTable = "b_iblock_element_property";

			if($db_prop["bFullJoin"])
				$sFrom .= "\t\t\tINNER JOIN ".$strTable." FPV".$i." ON FPV".$i.".IBLOCK_PROPERTY_ID = FP".$db_prop["JOIN"].".ID AND FPV".$i.".IBLOCK_ELEMENT_ID = BE.ID\n";
			else
				$sFrom .= "\t\t\tLEFT JOIN ".$strTable." FPV".$i." ON FPV".$i.".IBLOCK_PROPERTY_ID = FP".$db_prop["JOIN"].".ID AND FPV".$i.".IBLOCK_ELEMENT_ID = BE.ID\n";

			if($db_prop["IBLOCK_ID"])
				$arFilterIBlocks[$db_prop["IBLOCK_ID"]] = $db_prop["IBLOCK_ID"];
		}

		foreach($arJoinProps["FPEN"] as $propID => $db_prop)
		{
			$i = $db_prop["CNT"];

			if($db_prop["VERSION"] == 2 && $db_prop["MULTIPLE"] == "N")
			{
				if($db_prop["bFullJoin"])
					$sFrom .= "\t\t\tINNER JOIN b_iblock_property_enum FPEN".$i." ON FPEN".$i.".PROPERTY_ID = ".$db_prop["ORIG_ID"]." AND FPS".$db_prop["JOIN"].".PROPERTY_".$db_prop["ORIG_ID"]." = FPEN".$i.".ID\n";
				else
					$sFrom .= "\t\t\tLEFT JOIN b_iblock_property_enum FPEN".$i." ON FPEN".$i.".PROPERTY_ID = ".$db_prop["ORIG_ID"]." AND FPS".$db_prop["JOIN"].".PROPERTY_".$db_prop["ORIG_ID"]." = FPEN".$i.".ID\n";
			}
			else
			{
				if($db_prop["bFullJoin"])
					$sFrom .= "\t\t\tINNER JOIN b_iblock_property_enum FPEN".$i." ON FPEN".$i.".PROPERTY_ID = FPV".$db_prop["JOIN"].".IBLOCK_PROPERTY_ID AND FPV".$db_prop["JOIN"].".VALUE_ENUM = FPEN".$i.".ID\n";
				else
					$sFrom .= "\t\t\tLEFT JOIN b_iblock_property_enum FPEN".$i." ON FPEN".$i.".PROPERTY_ID = FPV".$db_prop["JOIN"].".IBLOCK_PROPERTY_ID AND FPV".$db_prop["JOIN"].".VALUE_ENUM = FPEN".$i.".ID\n";
			}

			if($db_prop["IBLOCK_ID"])
				$arFilterIBlocks[$db_prop["IBLOCK_ID"]] = $db_prop["IBLOCK_ID"];
		}

		foreach($arJoinProps["BE"] as $propID => $db_prop)
		{
			$i = $db_prop["CNT"];

			$sFrom .= "\t\t\tLEFT JOIN b_iblock_element BE".$i." ON BE".$i.".ID = ".
				(
					$db_prop["VERSION"]==2 && $db_prop["MULTIPLE"]=="N"?
					"FPS".$db_prop["JOIN"].".PROPERTY_".$db_prop["ORIG_ID"]
					:"FPV".$db_prop["JOIN"].".VALUE_NUM"
				).
				(
					$arFilter["SHOW_HISTORY"] != "Y"?
					" AND ((BE.WF_STATUS_ID=1 AND BE.WF_PARENT_ELEMENT_ID IS NULL)".($arFilter["SHOW_NEW"]=="Y"? " OR BE.WF_NEW='Y'": "").")":
					""
				)."\n";

			if($db_prop["bJoinIBlock"])
				$sFrom .= "\t\t\tLEFT JOIN b_iblock B".$i." ON B".$i.".ID = BE".$i.".IBLOCK_ID\n";

			if($db_prop["bJoinSection"])
				$sFrom .= "\t\t\tLEFT JOIN b_iblock_section BS".$i." ON BS".$i.".ID = BE".$i.".IBLOCK_SECTION_ID\n";

			if($db_prop["IBLOCK_ID"])
				$arFilterIBlocks[$db_prop["IBLOCK_ID"]] = $db_prop["IBLOCK_ID"];
		}

		foreach($arJoinProps["BE_FPS"] as $iblock_id => $db_prop)
		{
			$sFrom .= "\t\t\tLEFT JOIN b_iblock_element_prop_s".$iblock_id." JFPS".$db_prop["CNT"]." ON JFPS".$db_prop["CNT"].".IBLOCK_ELEMENT_ID = BE".$db_prop["JOIN"].".ID\n";

			if($db_prop["IBLOCK_ID"])
				$arFilterIBlocks[$db_prop["IBLOCK_ID"]] = $db_prop["IBLOCK_ID"];
		}

		foreach($arJoinProps["BE_FP"] as $propID => $db_prop)
		{
			$i = $db_prop["CNT"];
			list($propID, $link) = explode("~", $propID, 2);

			if($db_prop["bFullJoin"])
				$sFrom .= "\t\t\tINNER JOIN b_iblock_property JFP".$i." ON JFP".$i.".IBLOCK_ID = BE".$db_prop["JOIN"].".IBLOCK_ID AND ".
					(
						IntVal($propID)>0?
						" JFP".$i.".ID=".IntVal($propID)."\n":
						" JFP".$i.".CODE='".$DB->ForSQL($propID, 200)."'\n"
					);
			else
				$sFrom .= "\t\t\tLEFT JOIN b_iblock_property JFP".$i." ON JFP".$i.".IBLOCK_ID = BE".$db_prop["JOIN"].".IBLOCK_ID AND ".
					(
						IntVal($propID)>0?
						" JFP".$i.".ID=".IntVal($propID)."\n":
						" JFP".$i.".CODE='".$DB->ForSQL($propID, 200)."'\n"
					);

			if($db_prop["IBLOCK_ID"])
				$arFilterIBlocks[$db_prop["IBLOCK_ID"]] = $db_prop["IBLOCK_ID"];
		}

		foreach($arJoinProps["BE_FPV"] as $propID => $db_prop)
		{
			$i = $db_prop["CNT"];
			list($propID, $link) = explode("~", $propID, 2);

			if($db_prop["MULTIPLE"]=="Y")
				$bDistinct = true;

			if($db_prop["VERSION"]==2)
				$strTable = "b_iblock_element_prop_m".$db_prop["IBLOCK_ID"];
			else
				$strTable = "b_iblock_element_property";

			if($db_prop["bFullJoin"])
				$sFrom .= "\t\t\tINNER JOIN ".$strTable." JFPV".$i." ON JFPV".$i.".IBLOCK_PROPERTY_ID = JFP".$db_prop["JOIN"].".ID AND JFPV".$i.".IBLOCK_ELEMENT_ID = BE".$db_prop["BE_JOIN"].".ID\n";
			else
				$sFrom .= "\t\t\tLEFT JOIN ".$strTable." JFPV".$i." ON JFPV".$i.".IBLOCK_PROPERTY_ID = JFP".$db_prop["JOIN"].".ID AND JFPV".$i.".IBLOCK_ELEMENT_ID = BE".$db_prop["BE_JOIN"].".ID\n";

			if($db_prop["IBLOCK_ID"])
				$arFilterIBlocks[$db_prop["IBLOCK_ID"]] = $db_prop["IBLOCK_ID"];
		}

		foreach($arJoinProps["BE_FPEN"] as $propID => $db_prop)
		{
			$i = $db_prop["CNT"];
			list($propID, $link) = explode("~", $propID, 2);

			if($db_prop["VERSION"] == 2 && $db_prop["MULTIPLE"] == "N")
			{
				if($db_prop["bFullJoin"])
					$sFrom .= "\t\t\tINNER JOIN b_iblock_property_enum JFPEN".$i." ON JFPEN".$i.".PROPERTY_ID = ".$db_prop["ORIG_ID"]." AND JFPS".$db_prop["JOIN"].".PROPERTY_".$db_prop["ORIG_ID"]." = JFPEN".$i.".ID\n";
				else
					$sFrom .= "\t\t\tLEFT JOIN b_iblock_property_enum JFPEN".$i." ON JFPEN".$i.".PROPERTY_ID = ".$db_prop["ORIG_ID"]." AND JFPS".$db_prop["JOIN"].".PROPERTY_".$db_prop["ORIG_ID"]." = JFPEN".$i.".ID\n";
			}
			else
			{
				if($db_prop["bFullJoin"])
					$sFrom .= "\t\t\tINNER JOIN b_iblock_property_enum JFPEN".$i." ON JFPEN".$i.".PROPERTY_ID = JFPV".$db_prop["JOIN"].".IBLOCK_PROPERTY_ID AND JFPV".$db_prop["JOIN"].".VALUE_ENUM = JFPEN".$i.".ID\n";
				else
					$sFrom .= "\t\t\tLEFT JOIN b_iblock_property_enum JFPEN".$i." ON JFPEN".$i.".PROPERTY_ID = JFPV".$db_prop["JOIN"].".IBLOCK_PROPERTY_ID AND JFPV".$db_prop["JOIN"].".VALUE_ENUM = JFPEN".$i.".ID\n";
			}

			if($db_prop["IBLOCK_ID"])
				$arFilterIBlocks[$db_prop["IBLOCK_ID"]] = $db_prop["IBLOCK_ID"];
		}

		if(strlen($arJoinProps["BES"]))
		{
			$sFrom .= "\t\t\t".$arJoinProps["BES"]."\n";
		}

		if($arJoinProps["RV"])
			$sFrom .= "\t\t\tLEFT JOIN b_rating_voting RV ON RV.ENTITY_TYPE_ID = 'IBLOCK_ELEMENT' AND RV.ENTITY_ID = BE.ID\n";
		if($arJoinProps["RVU"])
			$sFrom .= "\t\t\tLEFT JOIN b_rating_vote RVU ON RVU.ENTITY_TYPE_ID = 'IBLOCK_ELEMENT' AND RVU.ENTITY_ID = BE.ID AND RVU.USER_ID = ".$uid."\n";
		if($arJoinProps["RVV"])
			$sFrom .= "\t\t\t".($arJoinProps["RVV"]["bFullJoin"]? "INNER": "LEFT")." JOIN b_rating_vote RVV ON RVV.ENTITY_TYPE_ID = 'IBLOCK_ELEMENT' AND RVV.ENTITY_ID = BE.ID\n";

		//******************END OF FROM PART********************************************

		$bCatalogSort = false;
		if(count($arAddSelectFields)>0 || count($arAddWhereFields)>0 || count($arAddOrderByFields)>0)
		{
			if(CModule::IncludeModule("catalog"))
			{
				$res_catalog = CCatalogProduct::GetQueryBuildArrays($arAddOrderByFields, $arAddWhereFields, $arAddSelectFields);
				if(
					$sGroupBy==""
					&& !$bOnlyCount
					&& !(is_object($this) && isset($this->strField))
				)
					$sSelect .= $res_catalog["SELECT"]." ";
				$sFrom .= str_replace("LEFT JOIN", "\n\t\t\tLEFT JOIN", $res_catalog["FROM"])."\n";
				//$sWhere .= $res_catalog["WHERE"]." "; moved to MkFilter
				if(is_array($res_catalog["ORDER"]) && count($res_catalog["ORDER"]))
				{
					$bCatalogSort = true;
					foreach($res_catalog["ORDER"] as $i=>$val)
						$arSqlOrder[$i] = $val;
				}
			}
		}

		$sOrderBy = "";
		foreach($arSqlOrder as $i=>$val)
		{
			if(strlen($val))
			{
				if($sOrderBy=="")
					$sOrderBy = " ORDER BY ";
				else
					$sOrderBy .= ",";

				$sOrderBy .= $val." ";
			}
		}

		if(strlen(trim($sSelect))<=0)
			$sSelect = "0 as NOP ";

		$bDistinct = $bDistinct || (isset($arFilter["INCLUDE_SUBSECTIONS"]) && $arFilter["INCLUDE_SUBSECTIONS"] == "Y");

		if($bDistinct)
			$sSelect = str_replace("%%_DISTINCT_%%", "DISTINCT", $sSelect);
		else
			$sSelect = str_replace("%%_DISTINCT_%%", "", $sSelect);

		$sFrom = "
			b_iblock B
			INNER JOIN b_lang L ON B.LID=L.LID
			INNER JOIN b_iblock_element BE ON BE.IBLOCK_ID = B.ID
			".ltrim($sFrom, "\t\n")
			.(in_array("USER_NAME", $arSelectFields)? "\t\t\tLEFT JOIN b_user U ON U.ID=BE.MODIFIED_BY\n": "")
			.(in_array("LOCKED_USER_NAME", $arSelectFields)? "\t\t\tLEFT JOIN b_user UL ON UL.ID=BE.WF_LOCKED_BY\n": "")
			.(in_array("CREATED_USER_NAME", $arSelectFields)? "\t\t\tLEFT JOIN b_user UC ON UC.ID=BE.CREATED_BY\n": "")."
		";

		$strSql = "
			FROM ".$sFrom."
			WHERE 1=1 "
			.$sWhere."
			".$sGroupBy."
		";

		if(isset($this) && is_object($this) && isset($this->strField))
		{
			$this->sFrom = $sFrom;
			$this->sWhere = $sWhere;
			return "SELECT ".$sSelect.$strSql;
		}

		if($bOnlyCount)
		{
			$res = $DB->Query("SELECT ".$sSelect.$strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			$res = $res->Fetch();
			return $res["CNT"];
		}

		if(is_array($arNavStartParams))
		{
			$nTopCount = intval($arNavStartParams["nTopCount"]);
			$nElementID = intval($arNavStartParams["nElementID"]);

			if($nTopCount > 0)
			{
				$strSql = "SELECT ".$sSelect.$strSql.$sOrderBy." LIMIT ".$nTopCount;
				$res = $DB->Query($strSql);
			}
			elseif(
				$nElementID > 0
				&& $sGroupBy == ""
				&& $sOrderBy != ""
				&& strpos($sSelect, "BE.ID") !== false
				&& !$bCatalogSort
			)
			{
				$nPageSize = intval($arNavStartParams["nPageSize"]);

				if($nPageSize > 0)
				{
					$DB->Query("SET @rank=0");
					$DB->Query("
						SELECT @rank:=el1.rank
						FROM (
							SELECT @rank:=@rank+1 AS rank, el0.*
							FROM (
								SELECT ".$sSelect.$strSql.$sOrderBy."
							) el0
						) el1
						WHERE el1.ID = ".$nElementID."
					");
					$DB->Query("SET @rank2=0");

					$res = $DB->Query("
						SELECT *
						FROM (
							SELECT @rank2:=@rank2+1 AS RANK, el0.*
							FROM (
								SELECT ".$sSelect.$strSql.$sOrderBy."
							) el0
						) el1
						WHERE el1.RANK between @rank-$nPageSize and @rank+$nPageSize
					");
				}
				else
				{
					$DB->Query("SET @rank=0");
					$res = $DB->Query("
						SELECT el1.*
						FROM (
							SELECT @rank:=@rank+1 AS RANK, el0.*
							FROM (
								SELECT ".$sSelect.$strSql.$sOrderBy."
							) el0
						) el1
						WHERE el1.ID = ".$nElementID."
					");
				}
			}
			else
			{
				if($sGroupBy == "")
				{
					$res_cnt = $DB->Query("SELECT COUNT(".($bDistinct? "DISTINCT BE.ID": "'x'").") as C ".$strSql);
					$res_cnt = $res_cnt->Fetch();
					$cnt = $res_cnt["C"];
				}
				else
				{
					$res_cnt = $DB->Query("SELECT 'x' ".$strSql);
					$cnt = $res_cnt->SelectedRowsCount();
				}

				$strSql = "SELECT ".$sSelect.$strSql.$sOrderBy;
				$res = new CDBResult();
				$res->NavQuery($strSql, $cnt, $arNavStartParams);
			}
		}
		else//if(is_array($arNavStartParams))
		{
			$strSql = "SELECT ".$sSelect.$strSql.$sOrderBy;
			$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}

		$res = new CIBlockResult($res);
		$res->SetIBlockTag($arFilterIBlocks);
		$res->arIBlockMultProps = $arIBlockMultProps;
		$res->arIBlockConvProps = $arIBlockConvProps;
		$res->arIBlockAllProps  = $arIBlockAllProps;
		$res->arIBlockNumProps = $arIBlockNumProps;
		$res->arIBlockLongProps = $arIBlockLongProps;

		return $res;
	}

	///////////////////////////////////////////////////////////////////
	// Update element function
	///////////////////////////////////////////////////////////////////
	
	/**
	 * <p>Метод изменяет параметры элемента с кодом <i>ID</i>. Перед изменением элемента вызываются обработчики события  <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/OnStartIBlockElementUpdate.php">OnStartIBlockElementUpdate</a> из которых можно изменить значения полей или отменить изменение элемента вернув сообщение об ошибке. После изменения элемента вызывается само событие <a href="http://dev.1c-bitrix.ruapi_help/iblock/events/onafteriblockelementupdate.php">OnAfterIBlockElementUpdate</a>. </p> <p>Если изменяется свойство типа <b>файл</b>, то необходимо сформировать <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cfile/makefilearray.php">массив</a>. </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  ID изменяемой записи.
	 *
	 *
	 *
	 * @param array $arFields  Массив вида Array("поле"=&gt;"значение", ...), содержащий значения <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#felement">полей элемента</a> инфоблоков
	 * и дополнительно может содержать поле "PROPERTY_VALUES" - массив со всеми
	 * значениями свойств элемента в виде массива Array("код
	 * свойства"=&gt;"значение свойства"). Где <br> "код свойства" - числовой
	 * или мнемонический код свойства, <br> "значение свойства" - одиночное
	 * значение, либо массив значений (если свойство множественное). <br>
	 * Если массив<i> PROPERTY_VALUES</i> задан, то он должен содержать полный
	 * набор значений свойств для данного элемента, т.е. если в нем будет
	 * отсутствовать одно из свойств, то все его значения для данного
	 * элемента будут удалены. <br> Это справедливо для всех типов свойств
	 * кроме типа <b>файл</b>. Файлы надо удалять через массив с параметром
	 * "del"=&gt;"Y". <br> Дополнительно для сохранения значения свойств см: <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/index.php">CIBlockElement</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/setpropertyvalues.php">SetPropertyValues()</a>, <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/index.php">CIBlockElement</a>::<a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/setpropertyvaluecode.php">SetPropertyValueCode().</a>
	 * <br><br><b>Примечание:</b> Нельзя изменить изначения полей ID и IBLOCK_ID. <br>
	 *
	 *
	 *
	 * @param bool $bWorkFlow = false Изменение в режиме документооборота. Если true и модуль
	 * документооборота установлен, то данное изменение будет учтено в
	 * журнале изменений элемента. Не обязательный параметр, по
	 * умолчанию изменение в режиме документооборота отключено.
	 * <br><br><b>Примечание</b>: в режиме документооборота можно передавать
	 * значения не всех свойств в PROPERTY_VALUES, а только необходимых.
	 *
	 *
	 *
	 * @param bool $bUpdateSearch = true Индексировать элемент для поиска. Для повышения
	 * производительности можно отключать этот параметр во время серии
	 * изменений элементов, а после их окончания переиндексировать
	 * поиск. Не обязательный параметр, по умолчанию элемент после
	 * изменения будет автоматически проиндексирован в поиске.
	 *
	 *
	 *
	 * @param bool $bResizePictures = false Использовать настройки инфоблока для обработки изображений. По
	 * умоляанию настройки не применяются. Если этот параметр имеет
	 * значение true, то к полям PREVIEW_PICTURE и DETAIL_PICTURE будут применены правила
	 * генерации и масштабирования в соответствии с настройками
	 * информационного блока.
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$el = new CIBlockElement;<br><br>$PROP = array();<br>$PROP[12] = "Белый";  // свойству с кодом 12 присваиваем значение "Белый"<br>$PROP[3] = 38;        // свойству с кодом 3 присваиваем значение 38<br><br>$arLoadProductArray = Array(<br>  "MODIFIED_BY"    =&gt; $USER-&gt;GetID(), // элемент изменен текущим пользователем<br>  "IBLOCK_SECTION" =&gt; false,          // элемент лежит в корне раздела<br>  "PROPERTY_VALUES"=&gt; $PROP,<br>  "NAME"           =&gt; "Элемент",<br>  "ACTIVE"         =&gt; "Y",            // активен<br>  "PREVIEW_TEXT"   =&gt; "текст для списка элементов",<br>  "DETAIL_TEXT"    =&gt; "текст для детального просмотра",<br>  "DETAIL_PICTURE" =&gt; CFile::MakeFileArray($_SERVER["DOCUMENT_ROOT"]."/image.gif")<br>  );<br><br>$PRODUCT_ID = 2;  // изменяем элемент с кодом (ID) 2<br>$res = $el-&gt;Update($PRODUCT_ID, $arLoadProductArray);<br>?&gt;Менять параметр IBLOCK ID нельзя.
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/add.php">CIBlockElement::Add</a></li>
	 * <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/events/onbeforeiblockelementupdate.php">OnBeforeIBlockElementUpdate</a></li>
	 * <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/events/onafteriblockelementupdate.php">OnAfterIBlockElementUpdate</a></li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/update.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields, $bWorkFlow=false, $bUpdateSearch=true, $bResizePictures=false, $bCheckDiskQuota=true)
	{
		global $DB, $USER;
		$ID = intval($ID);

		$db_element = CIBlockElement::GetList(array(), array("ID"=>$ID, "SHOW_HISTORY"=>"Y"), false, false,
			array(
				"ID",
				"TIMESTAMP_X",
				"MODIFIED_BY",
				"DATE_CREATE",
				"CREATED_BY",
				"IBLOCK_ID",
				"ACTIVE",
				"ACTIVE_FROM",
				"ACTIVE_TO",
				"SORT",
				"NAME",
				"PREVIEW_PICTURE",
				"PREVIEW_TEXT",
				"PREVIEW_TEXT_TYPE",
				"DETAIL_PICTURE",
				"DETAIL_TEXT",
				"DETAIL_TEXT_TYPE",
				"WF_STATUS_ID",
				"WF_PARENT_ELEMENT_ID",
				"WF_NEW",
				"WF_COMMENTS",
				"IN_SECTIONS",
				"CODE",
				"TAGS",
				"XML_ID",
				"TMP_ID",
			)
		);
		if(!($ar_element = $db_element->Fetch()))
			return false;

		$arIBlock = CIBlock::GetArrayByID($ar_element["IBLOCK_ID"]);
		$bWorkFlow = $bWorkFlow && is_array($arIBlock) && ($arIBlock["WORKFLOW"] != "N") && CModule::IncludeModule("workflow");

		$ar_wf_element = $ar_element;

		$LAST_ID = 0;
		if($bWorkFlow)
		{
			$LAST_ID = CIBlockElement::WF_GetLast($ID);
			if($LAST_ID!=$ID)
			{
				$db_element = CIBlockElement::GetByID($LAST_ID);
				if(!($ar_wf_element = $db_element->Fetch()))
					return false;
			}

			$arFields["WF_PARENT_ELEMENT_ID"] = $ID;

			if(!array_key_exists("PROPERTY_VALUES", $arFields) || !is_array($arFields["PROPERTY_VALUES"]))
				$arFields["PROPERTY_VALUES"] = array();

			$bFieldProps = array();
			foreach($arFields["PROPERTY_VALUES"] as $k=>$v)
				$bFieldProps[$k]=true;

			$arFieldProps = &$arFields['PROPERTY_VALUES'];
			$props = CIBlockElement::GetProperty($ar_element["IBLOCK_ID"], $ar_wf_element["ID"]);
			while($arProp = $props->Fetch())
			{
				$pr_val_id = $arProp['PROPERTY_VALUE_ID'];
				if($arProp['PROPERTY_TYPE']=='F' && strlen($pr_val_id)>0)
				{
					if(strlen($arProp["CODE"]) > 0 && is_set($arFieldProps, $arProp["CODE"]))
						$pr_id = $arProp["CODE"];
					else
						$pr_id = $arProp['ID'];

					if(
						array_key_exists($pr_id, $arFieldProps)
						&& array_key_exists($pr_val_id, $arFieldProps[$pr_id])
						&& is_array($arFieldProps[$pr_id][$pr_val_id])
					)
					{
						$new_value = $arFieldProps[$pr_id][$pr_val_id];
						if(
							strlen($new_value['name']) <= 0
							&& $new_value['del'] != "Y"
							&& strlen($new_value['VALUE']['name']) <= 0
							&& $new_value['VALUE']['del'] != "Y"
						)
						{
							if(
								array_key_exists('DESCRIPTION', $new_value)
								&& ($new_value['DESCRIPTION'] != $arProp['DESCRIPTION'])
							)
							{
								$p = Array("VALUE"=>CFile::MakeFileArray($arProp['VALUE']));
								$p["DESCRIPTION"] = $new_value["DESCRIPTION"];
								$p["MODULE_ID"] = "iblock";
								$arFieldProps[$pr_id][$pr_val_id] = $p;
							}
							elseif($arProp['VALUE'] > 0)
							{
								$arFieldProps[$pr_id][$pr_val_id] = array("VALUE"=>$arProp['VALUE'],"DESCRIPTION"=>$arProp["DESCRIPTION"]);
							}
						}
					}
					else
					{
						$arFieldProps[$pr_id][$pr_val_id] = array("VALUE"=>$arProp['VALUE'],"DESCRIPTION"=>$arProp["DESCRIPTION"]);
					}

					continue;
				}

				if (
					strlen($pr_val_id)<=0
					|| array_key_exists($arProp["ID"], $bFieldProps)
					|| (
						strlen($arProp["CODE"])>0
						&& array_key_exists($arProp["CODE"], $bFieldProps)
					)
				)
					continue;

				$arFieldProps[$arProp["ID"]][$pr_val_id] = array("VALUE"=>$arProp['VALUE'],"DESCRIPTION"=>$arProp["DESCRIPTION"]);
			}

			if($ar_wf_element["IN_SECTIONS"] == "Y")
			{
				$ar_wf_element["IBLOCK_SECTION"] = array();
				$rsSections = CIBlockElement::GetElementGroups($ar_element["ID"], true);
				while($arSection = $rsSections->Fetch())
					$ar_wf_element["IBLOCK_SECTION"][] = $arSection["ID"];
			}

			unset($ar_wf_element["DATE_ACTIVE_FROM"]);
			unset($ar_wf_element["DATE_ACTIVE_TO"]);
			unset($ar_wf_element["EXTERNAL_ID"]);
			unset($ar_wf_element["TIMESTAMP_X"]);
			unset($ar_wf_element["ID"]);

			$arFields = $arFields + $ar_wf_element;
		}

		$arFields["WF"] = ($bWorkFlow?"Y":"N");

		$bBizProc = is_array($arIBlock) && ($arIBlock["BIZPROC"] == "Y") && IsModuleInstalled("bizproc");
		if(array_key_exists("BP_PUBLISHED", $arFields))
		{
			if($bBizProc)
			{
				if($arFields["BP_PUBLISHED"] == "Y")
				{
					$arFields["WF_STATUS_ID"] = 1;
					$arFields["WF_NEW"] = false;
				}
				else
				{
					$arFields["WF_STATUS_ID"] = 2;
					$arFields["WF_NEW"] = "Y";
					$arFields["BP_PUBLISHED"] = "N";
				}
			}
			else
			{
				$arFields["WF_NEW"] = false;
				unset($arFields["BP_PUBLISHED"]);
			}
		}
		else
		{
			$arFields["WF_NEW"] = false;
		}

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";

		if(is_set($arFields, "PREVIEW_TEXT_TYPE") && $arFields["PREVIEW_TEXT_TYPE"]!="html")
			$arFields["PREVIEW_TEXT_TYPE"]="text";

		if(is_set($arFields, "DETAIL_TEXT_TYPE") && $arFields["DETAIL_TEXT_TYPE"]!="html")
			$arFields["DETAIL_TEXT_TYPE"]="text";

		$strWarning = "";
		if($bResizePictures)
		{
			$arDef = $arIBlock["FIELDS"]["PREVIEW_PICTURE"]["DEFAULT_VALUE"];

			if(
				$arDef["DELETE_WITH_DETAIL"] === "Y"
				&& $arFields["DETAIL_PICTURE"]["del"] === "Y"
			)
			{
				$arFields["PREVIEW_PICTURE"]["del"] = "Y";
			}

			if(
				$arDef["FROM_DETAIL"] === "Y"
				&& (
					$arFields["PREVIEW_PICTURE"]["size"] <= 0
					|| $arDef["UPDATE_WITH_DETAIL"] === "Y"
				)
				&& is_array($arFields["DETAIL_PICTURE"])
				&& $arFields["DETAIL_PICTURE"]["size"] > 0
			)
			{
				if(
					$arFields["PREVIEW_PICTURE"]["del"] !== "Y"
					&& $arDef["UPDATE_WITH_DETAIL"] !== "Y"
				)
				{
					$rsElement = CIBlockElement::GetList(Array("ID" => "DESC"), Array("ID" => $ar_wf_element["ID"], "IBLOCK_ID" => $ar_wf_element["IBLOCK_ID"], "SHOW_HISTORY"=>"Y"), false, false, Array("ID", "PREVIEW_PICTURE"));
					$arOldElement = $rsElement->Fetch();
				}
				else
				{
					$arOldElement = false;
				}

				if(!$arOldElement || !$arOldElement["PREVIEW_PICTURE"])
				{
					$arNewPreview = $arFields["DETAIL_PICTURE"];
					$arNewPreview["COPY_FILE"] = "Y";
					$arNewPreview["description"] = $arFields["PREVIEW_PICTURE"]["description"];
					$arFields["PREVIEW_PICTURE"] = $arNewPreview;
				}
			}

			if(
				array_key_exists("PREVIEW_PICTURE", $arFields)
				&& is_array($arFields["PREVIEW_PICTURE"])
				&& $arFields["PREVIEW_PICTURE"]["size"] > 0
				&& $arDef["SCALE"] === "Y"
			)
			{
				$arNewPicture = CIBlock::ResizePicture($arFields["PREVIEW_PICTURE"], $arDef);

				if(is_array($arNewPicture))
				{
					$arNewPicture["description"] = $arFields["PREVIEW_PICTURE"]["description"];
					$arFields["PREVIEW_PICTURE"] = $arNewPicture;
				}
				elseif($arDef["IGNORE_ERRORS"] !== "Y")
				{
					unset($arFields["PREVIEW_PICTURE"]);
					$strWarning .= GetMessage("IBLOCK_FIELD_PREVIEW_PICTURE").": ".$arNewPicture."<br>";
				}
			}

			if(
				array_key_exists("PREVIEW_PICTURE", $arFields)
				&& is_array($arFields["PREVIEW_PICTURE"])
				&& $arDef["USE_WATERMARK_FILE"] === "Y"
			)
			{
				if(
					strlen($arFields["PREVIEW_PICTURE"]["tmp_name"]) > 0
					&& (
						$arFields["PREVIEW_PICTURE"]["tmp_name"] === $arFields["DETAIL_PICTURE"]["tmp_name"]
						|| ($arFields["PREVIEW_PICTURE"]["COPY_FILE"] == "Y" && !$arFields["PREVIEW_PICTURE"]["copy"])
					)
				)
				{
					$tmp_name = CTempFile::GetFileName(basename($arFields["PREVIEW_PICTURE"]["tmp_name"]));
					CheckDirPath($tmp_name);
					copy($arFields["PREVIEW_PICTURE"]["tmp_name"], $tmp_name);
					$arFields["PREVIEW_PICTURE"]["copy"] = true;
					$arFields["PREVIEW_PICTURE"]["tmp_name"] = $tmp_name;
				}

				CIBLock::FilterPicture($arFields["PREVIEW_PICTURE"]["tmp_name"], array(
					"name" => "watermark",
					"position" => $arDef["WATERMARK_FILE_POSITION"],
					"type" => "file",
					"size" => "real",
					"alpha_level" => 100 - min(max($arDef["WATERMARK_FILE_ALPHA"], 0), 100),
					"file" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_FILE"]),
				));
			}

			if(
				array_key_exists("PREVIEW_PICTURE", $arFields)
				&& is_array($arFields["PREVIEW_PICTURE"])
				&& $arDef["USE_WATERMARK_TEXT"] === "Y"
			)
			{
				if(
					strlen($arFields["PREVIEW_PICTURE"]["tmp_name"]) > 0
					&& (
						$arFields["PREVIEW_PICTURE"]["tmp_name"] === $arFields["DETAIL_PICTURE"]["tmp_name"]
						|| ($arFields["PREVIEW_PICTURE"]["COPY_FILE"] == "Y" && !$arFields["PREVIEW_PICTURE"]["copy"])
					)
				)
				{
					$tmp_name = CTempFile::GetFileName(basename($arFields["PREVIEW_PICTURE"]["tmp_name"]));
					CheckDirPath($tmp_name);
					copy($arFields["PREVIEW_PICTURE"]["tmp_name"], $tmp_name);
					$arFields["PREVIEW_PICTURE"]["copy"] = true;
					$arFields["PREVIEW_PICTURE"]["tmp_name"] = $tmp_name;
				}

				CIBLock::FilterPicture($arFields["PREVIEW_PICTURE"]["tmp_name"], array(
					"name" => "watermark",
					"position" => $arDef["WATERMARK_TEXT_POSITION"],
					"type" => "text",
					"coefficient" => $arDef["WATERMARK_TEXT_SIZE"],
					"text" => $arDef["WATERMARK_TEXT"],
					"font" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_TEXT_FONT"]),
					"color" => $arDef["WATERMARK_TEXT_COLOR"],
				));
			}

			$arDef = $arIBlock["FIELDS"]["DETAIL_PICTURE"]["DEFAULT_VALUE"];

			if(
				array_key_exists("DETAIL_PICTURE", $arFields)
				&& is_array($arFields["DETAIL_PICTURE"])
				&& $arDef["SCALE"] === "Y"
			)
			{
				$arNewPicture = CIBlock::ResizePicture($arFields["DETAIL_PICTURE"], $arDef);
				if(is_array($arNewPicture))
				{
					$arNewPicture["description"] = $arFields["DETAIL_PICTURE"]["description"];
					$arFields["DETAIL_PICTURE"] = $arNewPicture;
				}
				elseif($arDef["IGNORE_ERRORS"] !== "Y")
				{
					unset($arFields["DETAIL_PICTURE"]);
					$strWarning .= GetMessage("IBLOCK_FIELD_DETAIL_PICTURE").": ".$arNewPicture."<br>";
				}
			}

			if(
				array_key_exists("DETAIL_PICTURE", $arFields)
				&& is_array($arFields["DETAIL_PICTURE"])
				&& $arDef["USE_WATERMARK_FILE"] === "Y"
			)
			{
				if(
					strlen($arFields["DETAIL_PICTURE"]["tmp_name"]) > 0
					&& (
						$arFields["DETAIL_PICTURE"]["tmp_name"] === $arFields["PREVIEW_PICTURE"]["tmp_name"]
						|| ($arFields["DETAIL_PICTURE"]["COPY_FILE"] == "Y" && !$arFields["DETAIL_PICTURE"]["copy"])
					)
				)
				{
					$tmp_name = CTempFile::GetFileName(basename($arFields["DETAIL_PICTURE"]["tmp_name"]));
					CheckDirPath($tmp_name);
					copy($arFields["DETAIL_PICTURE"]["tmp_name"], $tmp_name);
					$arFields["DETAIL_PICTURE"]["copy"] = true;
					$arFields["DETAIL_PICTURE"]["tmp_name"] = $tmp_name;
				}

				CIBLock::FilterPicture($arFields["DETAIL_PICTURE"]["tmp_name"], array(
					"name" => "watermark",
					"position" => $arDef["WATERMARK_FILE_POSITION"],
					"type" => "file",
					"size" => "real",
					"alpha_level" => 100 - min(max($arDef["WATERMARK_FILE_ALPHA"], 0), 100),
					"file" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_FILE"]),
				));
			}

			if(
				array_key_exists("DETAIL_PICTURE", $arFields)
				&& is_array($arFields["DETAIL_PICTURE"])
				&& $arDef["USE_WATERMARK_TEXT"] === "Y"
			)
			{
				if(
					strlen($arFields["DETAIL_PICTURE"]["tmp_name"]) > 0
					&& (
						$arFields["DETAIL_PICTURE"]["tmp_name"] === $arFields["PREVIEW_PICTURE"]["tmp_name"]
						|| ($arFields["DETAIL_PICTURE"]["COPY_FILE"] == "Y" && !$arFields["DETAIL_PICTURE"]["copy"])
					)
				)
				{
					$tmp_name = CTempFile::GetFileName(basename($arFields["DETAIL_PICTURE"]["tmp_name"]));
					CheckDirPath($tmp_name);
					copy($arFields["DETAIL_PICTURE"]["tmp_name"], $tmp_name);
					$arFields["DETAIL_PICTURE"]["copy"] = true;
					$arFields["DETAIL_PICTURE"]["tmp_name"] = $tmp_name;
				}

				CIBLock::FilterPicture($arFields["DETAIL_PICTURE"]["tmp_name"], array(
					"name" => "watermark",
					"position" => $arDef["WATERMARK_TEXT_POSITION"],
					"type" => "text",
					"coefficient" => $arDef["WATERMARK_TEXT_SIZE"],
					"text" => $arDef["WATERMARK_TEXT"],
					"font" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_TEXT_FONT"]),
					"color" => $arDef["WATERMARK_TEXT_COLOR"],
				));
			}
		}

		if(array_key_exists("PREVIEW_PICTURE", $arFields) && is_array($arFields["PREVIEW_PICTURE"]))
		{
			if(
				strlen($arFields["PREVIEW_PICTURE"]["name"])<=0
				&& strlen($arFields["PREVIEW_PICTURE"]["del"])<=0
				&& !is_set($arFields["PREVIEW_PICTURE"], "description")
			)
			{
				unset($arFields["PREVIEW_PICTURE"]);
			}
			else
			{
				$arFields["PREVIEW_PICTURE"]["MODULE_ID"] = "iblock";
				$arFields["PREVIEW_PICTURE"]["old_file"] = $ar_wf_element["PREVIEW_PICTURE"];
			}
		}

		if(array_key_exists("DETAIL_PICTURE", $arFields) && is_array($arFields["DETAIL_PICTURE"]))
		{
			if(
				strlen($arFields["DETAIL_PICTURE"]["name"])<=0
				&& strlen($arFields["DETAIL_PICTURE"]["del"])<=0
				&& !is_set($arFields["DETAIL_PICTURE"], "description")
			)
			{
				unset($arFields["DETAIL_PICTURE"]);
			}
			else
			{
				$arFields["DETAIL_PICTURE"]["MODULE_ID"] = "iblock";
				$arFields["DETAIL_PICTURE"]["old_file"] = $ar_wf_element["DETAIL_PICTURE"];
			}
		}

		if(is_set($arFields, "DATE_ACTIVE_FROM"))
			$arFields["ACTIVE_FROM"] = $arFields["DATE_ACTIVE_FROM"];
		if(is_set($arFields, "DATE_ACTIVE_TO"))
			$arFields["ACTIVE_TO"] = $arFields["DATE_ACTIVE_TO"];
		if(is_set($arFields, "EXTERNAL_ID"))
			$arFields["XML_ID"] = $arFields["EXTERNAL_ID"];

		$PREVIEW_tmp = is_set($arFields, "PREVIEW_TEXT")? $arFields["PREVIEW_TEXT"]: $ar_wf_element["PREVIEW_TEXT"];
		$PREVIEW_TYPE_tmp = is_set($arFields, "PREVIEW_TEXT_TYPE")? $arFields["PREVIEW_TEXT_TYPE"]: $ar_wf_element["PREVIEW_TEXT_TYPE"];
		$DETAIL_tmp = is_set($arFields, "DETAIL_TEXT")? $arFields["DETAIL_TEXT"]: $ar_wf_element["DETAIL_TEXT"];
		$DETAIL_TYPE_tmp = is_set($arFields, "DETAIL_TEXT_TYPE")? $arFields["DETAIL_TEXT_TYPE"]: $ar_wf_element["DETAIL_TEXT_TYPE"];

		$arFields["SEARCHABLE_CONTENT"] = ToUpper(
			(is_set($arFields, "NAME")? $arFields["NAME"]: $ar_wf_element["NAME"])."\r\n".
			($PREVIEW_TYPE_tmp=="html"? HTMLToTxt($PREVIEW_tmp): $PREVIEW_tmp)."\r\n".
			($DETAIL_TYPE_tmp=="html"? HTMLToTxt($DETAIL_tmp): $DETAIL_tmp)
		);

		if(is_set($arFields["IBLOCK_SECTION_ID"]) && !is_set($arFields, "IBLOCK_SECTION"))
			$arFields["IBLOCK_SECTION"] = Array($arFields["IBLOCK_SECTION_ID"]);

		$arFields["IBLOCK_ID"] = $ar_element["IBLOCK_ID"];

		if(!$this->CheckFields($arFields, $ID, $bCheckDiskQuota) || strlen($strWarning))
		{
			$this->LAST_ERROR .= $strWarning;
			$Result = false;
			$arFields["RESULT_MESSAGE"] = &$this->LAST_ERROR;
		}
		else
		{
			unset($arFields["ID"]);

			if(array_key_exists("PREVIEW_PICTURE", $arFields))
			{
				$SAVED_PREVIEW_PICTURE = $arFields["PREVIEW_PICTURE"];
			}
			else
			{
				$SAVED_PREVIEW_PICTURE = false;
			}

			if(array_key_exists("DETAIL_PICTURE", $arFields))
			{
				$SAVED_DETAIL_PICTURE = $arFields["DETAIL_PICTURE"];
			}
			else
			{
				$SAVED_DETAIL_PICTURE = false;
			}

			// edit was done in workflow mode
			if($bWorkFlow)
			{
				$arFields["WF_PARENT_ELEMENT_ID"] = $ID;

				if(array_key_exists("PREVIEW_PICTURE", $arFields))
				{
					if(is_array($arFields["PREVIEW_PICTURE"]))
					{
						if(
							strlen($arFields["PREVIEW_PICTURE"]["name"])<=0
							&& strlen($arFields["PREVIEW_PICTURE"]["del"])<=0
						)
						{
							if(array_key_exists("description", $arFields["PREVIEW_PICTURE"]))
							{
								$arFile = CFile::GetFileArray($ar_wf_element["PREVIEW_PICTURE"]);
								if($arFields["PREVIEW_PICTURE"]["description"] != $arFile["DESCRIPTION"])
								{//Description updated, so it's new file
									$arNewFile = CFile::MakeFileArray($ar_wf_element["PREVIEW_PICTURE"]);
									$arNewFile["description"] = $arFields["PREVIEW_PICTURE"]["description"];
									$arNewFile["MODULE_ID"] = "iblock";
									$arFields["PREVIEW_PICTURE"] = $arNewFile;
								}
								else
								{
									$arFields["PREVIEW_PICTURE"] = $ar_wf_element["PREVIEW_PICTURE"];
								}
							}
							else
							{
								//File was not changed at all
								$arFields["PREVIEW_PICTURE"] = $ar_wf_element["PREVIEW_PICTURE"];
							}
						}
						else
						{
							unset($arFields["PREVIEW_PICTURE"]["old_file"]);
						}
					}
				}
				else
				{
					$arFields["PREVIEW_PICTURE"] = $ar_wf_element["PREVIEW_PICTURE"];
				}

				if(array_key_exists("DETAIL_PICTURE", $arFields))
				{
					if(is_array($arFields["DETAIL_PICTURE"]))
					{
						if(
							strlen($arFields["DETAIL_PICTURE"]["name"])<=0
							&& strlen($arFields["DETAIL_PICTURE"]["del"])<=0
						)
						{
							if(array_key_exists("description", $arFields["DETAIL_PICTURE"]))
							{
								$arFile = CFile::GetFileArray($ar_wf_element["DETAIL_PICTURE"]);
								if($arFields["DETAIL_PICTURE"]["description"] != $arFile["DESCRIPTION"])
								{//Description updated, so it's new file
									$arNewFile = CFile::MakeFileArray($ar_wf_element["DETAIL_PICTURE"]);
									$arNewFile["description"] = $arFields["DETAIL_PICTURE"]["description"];
									$arNewFile["MODULE_ID"] = "iblock";
									$arFields["DETAIL_PICTURE"] = $arNewFile;
								}
								else
								{
									$arFields["DETAIL_PICTURE"] = $ar_wf_element["DETAIL_PICTURE"];
								}
							}
							else
							{
								//File was not changed at all
								$arFields["DETAIL_PICTURE"] = $ar_wf_element["DETAIL_PICTURE"];
							}
						}
						else
						{
							unset($arFields["DETAIL_PICTURE"]["old_file"]);
						}
					}
				}
				else
				{
					$arFields["DETAIL_PICTURE"] = $ar_wf_element["DETAIL_PICTURE"];
				}

				$NID = $this->Add($arFields);
				if($NID>0)
				{
					if($arFields["WF_STATUS_ID"]==1)
					{
						$DB->Query("UPDATE b_iblock_element SET TIMESTAMP_X=TIMESTAMP_X, WF_NEW=null WHERE ID=".$ID);
						$DB->Query("UPDATE b_iblock_element SET TIMESTAMP_X=TIMESTAMP_X, WF_NEW=null WHERE WF_PARENT_ELEMENT_ID=".$ID);
						$ar_wf_element["WF_NEW"] = false;
					}

					if($this->bWF_SetMove)
						CIBlockElement::WF_SetMove($NID, $LAST_ID);

					if($ar_element["WF_STATUS_ID"] != 1
						&& $ar_wf_element["WF_STATUS_ID"] != $arFields["WF_STATUS_ID"]
						&& $arFields["WF_STATUS_ID"] != 1
						)
					{
						$DB->Query("UPDATE b_iblock_element SET TIMESTAMP_X=TIMESTAMP_X, WF_STATUS_ID=".$arFields["WF_STATUS_ID"]." WHERE ID=".$ID);
					}
				}

				//element was not published, so keep original
				if(
					(is_set($arFields, "WF_STATUS_ID") && $arFields["WF_STATUS_ID"]!=1 && $ar_element["WF_STATUS_ID"]==1)
					|| (!is_set($arFields, "WF_STATUS_ID") && $ar_wf_element["WF_STATUS_ID"]!=1)
				)
				{
					CIBlockElement::WF_CleanUpHistoryCopies($ID);
					return true;
				}

				$arFields['WF_PARENT_ELEMENT_ID'] = false;

				$rs = $DB->Query("SELECT PREVIEW_PICTURE, DETAIL_PICTURE from b_iblock_element WHERE ID = ".$NID);
				$ar_new_element = $rs->Fetch();
			}
			else
			{
				$ar_new_element = false;
			}

			if($ar_new_element)
			{
				if(!intval($ar_new_element["PREVIEW_PICTURE"]))
					$arFields["PREVIEW_PICTURE"] = false;
				else
					$arFields["PREVIEW_PICTURE"] = $ar_new_element["PREVIEW_PICTURE"];

				if(!intval($ar_new_element["DETAIL_PICTURE"]))
					$arFields["DETAIL_PICTURE"] = false;
				else
					$arFields["DETAIL_PICTURE"] = $ar_new_element["DETAIL_PICTURE"];

				if(is_array($arFields["PROPERTY_VALUES"]) && count($arFields["PROPERTY_VALUES"]) > 0)
				{
					$i = 0;
					$db_prop = CIBlockProperty::GetList(array(), array(
						"IBLOCK_ID" => $arFields["IBLOCK_ID"],
						"CHECK_PERMISSIONS" => "N",
						"PROPERTY_TYPE" => "F",
					));
					while($arProp = $db_prop->Fetch())
					{
						$i++;
						unset($arFields["PROPERTY_VALUES"][$arProp["CODE"]]);
						unset($arFields["PROPERTY_VALUES"][$arProp["ID"]]);
						$arFields["PROPERTY_VALUES"][$arProp["ID"]] = array();
					}

					if($i > 0)
					{
						//Delete previous files
						$props = CIBlockElement::GetProperty($arFields["IBLOCK_ID"], $ID, "sort", "asc", array("PROPERTY_TYPE" => "F", "EMPTY" => "N"));
						while($arProp = $props->Fetch())
						{
							$arFields["PROPERTY_VALUES"][$arProp["ID"]][$arProp['PROPERTY_VALUE_ID']] = array(
								"VALUE" => array(
									"del" => "Y",
								),
								"DESCRIPTION" => false,
							);
						}
						//Add copy from history
						$arDup = array();//This is cure for files duplication bug (just save element one more time)
						$props = CIBlockElement::GetProperty($arFields["IBLOCK_ID"], $NID, "sort", "asc", array("PROPERTY_TYPE" => "F", "EMPTY" => "N"));
						while($arProp = $props->Fetch())
						{
							if(!array_key_exists($arProp["VALUE"], $arDup))//This is cure for files duplication bug
							{
								$arFields["PROPERTY_VALUES"][$arProp["ID"]][$arProp['PROPERTY_VALUE_ID']] = array(
									"VALUE" => $arProp["VALUE"],
									"DESCRIPTION" => $arProp["DESCRIPTION"],
								);
								$arDup[$arProp["VALUE"]] = true;//This is cure for files duplication bug
							}
						}
					}
				}
			}
			else
			{
				if(array_key_exists("PREVIEW_PICTURE", $arFields))
					CFile::SaveForDB($arFields, "PREVIEW_PICTURE", "iblock");
				if(array_key_exists("DETAIL_PICTURE", $arFields))
					CFile::SaveForDB($arFields, "DETAIL_PICTURE", "iblock");
			}

			UnSet($arFields["IBLOCK_ID"]);
			UnSet($arFields["WF_NEW"]);
			UnSet($arFields["IBLOCK_SECTION_ID"]);

			$bTimeStampNA = false;
			if(is_set($arFields, "TIMESTAMP_X") && ($arFields["TIMESTAMP_X"] === NULL || $arFields["TIMESTAMP_X"]===false))
			{
				$bTimeStampNA = true;
				UnSet($arFields["TIMESTAMP_X"]);
			}

			$strUpdate = $DB->PrepareUpdate("b_iblock_element", $arFields, "iblock");

			if(strlen($strUpdate)>0)
				$strUpdate .= ", ";

			$strSql = "UPDATE b_iblock_element SET ".$strUpdate.($bTimeStampNA?"TIMESTAMP_X=TIMESTAMP_X":"TIMESTAMP_X=now()")." WHERE ID=".$ID;
			$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

			if(
				array_key_exists("PROPERTY_VALUES", $arFields)
				&& is_array($arFields["PROPERTY_VALUES"])
				&& count($arFields["PROPERTY_VALUES"]) > 0
			)
				CIBlockElement::SetPropertyValues($ID, $ar_element["IBLOCK_ID"], $arFields["PROPERTY_VALUES"]);

			if(is_set($arFields, "IBLOCK_SECTION"))
				CIBlockElement::SetElementSection($ID, $arFields["IBLOCK_SECTION"], false, $arIBlock["RIGHTS_MODE"] === "E"? $arIBlock["ID"]: 0);

			if($arIBlock["RIGHTS_MODE"] === "E")
			{
				$obElementRights = new CIBlockElementRights($arIBlock["ID"], $ID);
				if(array_key_exists("RIGHTS", $arFields) && is_array($arFields["RIGHTS"]))
					$obElementRights->SetRights($arFields["RIGHTS"]);
			}

			if($bUpdateSearch)
				CIBlockElement::UpdateSearch($ID, true);

			if($bWorkFlow)
				CIBlockElement::WF_CleanUpHistoryCopies($ID);

			//Restore saved values
			if($SAVED_PREVIEW_PICTURE!==false)
				$arFields["PREVIEW_PICTURE"] = $SAVED_PREVIEW_PICTURE;
			else
				unset($arFields["PREVIEW_PICTURE"]);

			if($SAVED_DETAIL_PICTURE!==false)
				$arFields["DETAIL_PICTURE"] = $SAVED_DETAIL_PICTURE;
			else
				unset($arFields["DETAIL_PICTURE"]);

			if($arIBlock["FIELDS"]["LOG_ELEMENT_EDIT"]["IS_REQUIRED"] == "Y")
			{
				$USER_ID = is_object($USER)? intval($USER->GetID()) : 0;
				$arEvents = GetModuleEvents("main", "OnBeforeEventLog", true);
				if(empty($arEvents) || ExecuteModuleEventEx($arEvents[0], array($USER_ID))===false)
				{
					$rsElement = CIBlockElement::GetList(
						array(),
						array("=ID" => $ID, "CHECK_PERMISSIONS" => "N", "SHOW_NEW" => "Y"),
						false, false,
						array("ID", "NAME", "LIST_PAGE_URL", "CODE")
					);
					$arElement = $rsElement->GetNext();
					$res = array(
						"ID" => $ID,
						"CODE" => $arElement["CODE"],
						"NAME" => $arElement["NAME"],
						"ELEMENT_NAME" => $arIBlock["ELEMENT_NAME"],
						"USER_ID" => $USER_ID,
						"IBLOCK_PAGE_URL" => $arElement["LIST_PAGE_URL"],
					);
					CEventLog::Log(
						"IBLOCK",
						"IBLOCK_ELEMENT_EDIT",
						"iblock",
						$arIBlock["ID"],
						serialize($res)
					);
				}
			}
			$Result = true;

			/************* QUOTA *************/
			$_SESSION["SESS_RECOUNT_DB"] = "Y";
			/************* QUOTA *************/
		}

		$arFields["ID"] = $ID;
		$arFields["IBLOCK_ID"] = $ar_element["IBLOCK_ID"];
		$arFields["RESULT"] = &$Result;

		if(
			isset($arFields["PREVIEW_PICTURE"])
			&& $arFields["PREVIEW_PICTURE"]["COPY_FILE"] == "Y"
			&& $arFields["PREVIEW_PICTURE"]["copy"]
		)
		{
			@unlink($arFields["PREVIEW_PICTURE"]["tmp_name"]);
			@rmdir(dirname($arFields["PREVIEW_PICTURE"]["tmp_name"]));
		}

		if(
			isset($arFields["DETAIL_PICTURE"])
			&& $arFields["DETAIL_PICTURE"]["COPY_FILE"] == "Y"
			&& $arFields["DETAIL_PICTURE"]["copy"]
		)
		{
			@unlink($arFields["DETAIL_PICTURE"]["tmp_name"]);
			@rmdir(dirname($arFields["DETAIL_PICTURE"]["tmp_name"]));
		}

		foreach (GetModuleEvents("iblock", "OnAfterIBlockElementUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arFields));

		if(defined("BX_COMP_MANAGED_CACHE"))
			$GLOBALS["CACHE_MANAGER"]->ClearByTag("iblock_id_".$arIBlock["ID"]);

		return $Result;
	}

	
	/**
	 * <p>Функция сохраняет значения всех свойств элемента информационного блока.</p>
	 *
	 *
	 *
	 *
	 * @param int $ELEMENT_ID  Код элемента, значения свойств которого необходимо установить.
	 *
	 *
	 *
	 * @param int $IBLOCK_ID  Код информационного блока.
	 *
	 *
	 *
	 * @param array $PROPERTY_VALUES  Массив значений свойств, в котором коду свойства ставится в
	 * соответствие значение свойства. <br> Если <i>PROPERTY_CODE</i> установлен,
	 * то должен содержать одно или массив всех значений свойства
	 * (множественное) для заданного элемента. <br> Если <i>PROPERTY_CODE</i> равен
	 * <i>false</i>, то <i>PROPERTY_VALUES</i> должен быть вида Array("код
	 * свойства1"=&gt;"значения свойства1", ....), где "код свойства" - числовой
	 * или мнемонический код свойства, "значения свойства" - одно или
	 * массив всех значений свойства (множественное). При этом массив
	 * <i>PROPERTY_VALUES</i> должен содержать полный набор значений свойств для
	 * данного элемента, т.е. если в нем будет остутствовать одно из
	 * свойств, то все его значения для данного элемента будут удалены.
	 * <br> Это справедливо для всех типов свойств кроме типа <b>файл</b>.
	 * Файлы надо удалять через массив с параметром "del"=&gt;"Y".
	 *
	 *
	 *
	 * @param string $PROPERTY_CODE = false Код изменяемого свойства. Если этот параметр отличен от false, то
	 * изменяется только свойство с таким кодом. Не обязательный
	 * параметр, по умолчанию равен false.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$ELEMENT_ID = 18;  // код элемента<br>$PROPERTY_CODE = "PROP1";  // код свойства<br>$PROPERTY_VALUE = "Синий";  // значение свойства<br><br>// Установим новое значение для данного свойства данного элемента<br>$dbr = CIBlockElement::GetList(array(), array("=ID"=&gt;$ELEMENT_ID), false, false, array("ID", "IBLOCK_ID"));<br>if ($dbr_arr = $dbr-&gt;Fetch())<br>{<br>  $IBLOCK_ID = $dbr_arr["IBLOCK_ID"];<br>  CIBlockElement::SetPropertyValues($ELEMENT_ID, $IBLOCK_ID, $PROPERTY_VALUE, $PROPERTY_CODE);<br>}<br>?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/update.php">CIBlockElement::Update</a>
	 * </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/setpropertyvaluecode.php">CIBlockElement::SetPropertyValueCode</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/setpropertyvalues.php
	 * @author Bitrix
	 */
	public static function SetPropertyValues($ELEMENT_ID, $IBLOCK_ID, $PROPERTY_VALUES, $PROPERTY_CODE = false)
	{
		global $DB;
		$ELEMENT_ID = intVal($ELEMENT_ID);
		if(!is_array($PROPERTY_VALUES))
			$PROPERTY_VALUES = Array($PROPERTY_VALUES);

		$arFilter = Array(
			"IBLOCK_ID"=>$IBLOCK_ID,
			"CHECK_PERMISSIONS"=>"N",
		);
		if($PROPERTY_CODE!==false)
		{
			if(IntVal($PROPERTY_CODE)>0)
				$arFilter["ID"] = IntVal($PROPERTY_CODE);
			else
				$arFilter["CODE"] = $PROPERTY_CODE;
		}
		else
		{
			$arFilter["ACTIVE"] = "Y";
		}

		$uniq_flt = md5(serialize($arFilter));
		global $BX_IBLOCK_PROP_CACHE;
		if(!is_set($BX_IBLOCK_PROP_CACHE, $IBLOCK_ID))
			$BX_IBLOCK_PROP_CACHE[$IBLOCK_ID] = Array();
		if(is_set($BX_IBLOCK_PROP_CACHE[$IBLOCK_ID], $uniq_flt))
			$ar_prop = &$BX_IBLOCK_PROP_CACHE[$IBLOCK_ID][$uniq_flt];
		else
		{
			$db_prop = CIBlockProperty::GetList(array(), $arFilter);
			$ar_prop = Array();
			while($prop = $db_prop->Fetch())
				$ar_prop[] = $prop;

			$BX_IBLOCK_PROP_CACHE[$IBLOCK_ID][$uniq_flt] = &$ar_prop;
		}
		Reset($ar_prop);

		$bRecalcSections = false;
		$arPROP_ID = array_keys($PROPERTY_VALUES);

		$cacheValues=false;
		if(count($ar_prop)>1)
		{
			$cacheValues = Array();
			$strSql =
				"SELECT ep.ID, ep.VALUE, ep.IBLOCK_PROPERTY_ID ".
				"FROM b_iblock_element_property ep, b_iblock_property p ".
				"WHERE ep.IBLOCK_ELEMENT_ID=".$ELEMENT_ID.
				"	AND ep.IBLOCK_PROPERTY_ID = p.ID ".
				"	AND p.PROPERTY_TYPE <> 'L' ".
				"	AND p.PROPERTY_TYPE <> 'G' ";

			$db_res = $DB->Query($strSql);
			while($res = $db_res->Fetch())
			{
				if(!isset($cacheValues[$res["IBLOCK_PROPERTY_ID"]]))
					$cacheValues[$res["IBLOCK_PROPERTY_ID"]] = Array();
				$cacheValues[$res["IBLOCK_PROPERTY_ID"]][] = $res;
			}
		}

		$ids = "0";
		foreach($ar_prop as $prop)
		{
			if($PROPERTY_CODE)
				$PROP = $PROPERTY_VALUES;
			else
			{
				if(strlen($prop["CODE"])>0 && in_array($prop["CODE"], $arPROP_ID, TRUE))
					$PROP = $PROPERTY_VALUES[$prop["CODE"]];
				else
					$PROP = $PROPERTY_VALUES[$prop["ID"]];
			}
			if($prop["PROPERTY_TYPE"]=="F")
			{
				if(
					!is_array($PROP)
					|| (is_array($PROP) && (is_set($PROP, "tmp_name") || is_set($PROP, "del")))
					|| (count($PROP)==2 && is_set($PROP, "VALUE") && is_set($PROP, "DESCRIPTION"))
				)
					$PROP = Array($PROP);
			}
			elseif(!is_array($PROP) || (count($PROP)==2 && is_set($PROP, "VALUE") && is_set($PROP, "DESCRIPTION")))
				$PROP = Array($PROP);

			if($prop["USER_TYPE"]!="")
			{
				$arUserType = CIBlockProperty::GetUserType($prop["USER_TYPE"]);

				if(array_key_exists("ConvertToDB", $arUserType))
				{
					foreach($PROP as $key=>$value)
					{
						if(!is_array($value))
							$value=array("VALUE"=>$value);
						elseif(!array_key_exists("VALUE", $value))
							$value=array("VALUE"=>$value);
						$PROP[$key] = call_user_func_array($arUserType["ConvertToDB"], array($prop, $value));
					}
				}
			}

			if($prop["VERSION"]==2)
			{
				if($prop["MULTIPLE"]=="Y")
					$strTable = "b_iblock_element_prop_m".$prop["IBLOCK_ID"];
				else
					$strTable = "b_iblock_element_prop_s".$prop["IBLOCK_ID"];
			}
			else
				$strTable = "b_iblock_element_property";

			if($prop["PROPERTY_TYPE"]=="L")
			{
				$DB->Query(CIBLockElement::DeletePropertySQL($prop, $ELEMENT_ID), false, "File: ".__FILE__."<br>Line: ".__LINE__);
				if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="Y")
				{
					$strSql = "
						UPDATE	b_iblock_element_prop_s".$prop["IBLOCK_ID"]."
						SET	PROPERTY_".$prop["ID"]."=NULL, DESCRIPTION_".$prop["ID"]."=NULL
						WHERE	IBLOCK_ELEMENT_ID=".$ELEMENT_ID."
					";
					$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				}
				$ids = "0";
				foreach($PROP as $key=>$value)
				{
					if(is_array($value))
						$value = $value["VALUE"];

					if(IntVal($value)<=0) continue;
					$ids .= ",".IntVal($value);
					if($prop["MULTIPLE"]!="Y") break;
				}

				if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="N")
				{
					$strSql = "
						UPDATE
							b_iblock_element_prop_s".$prop["IBLOCK_ID"]." E
							,b_iblock_property P
							,b_iblock_property_enum PEN
						SET
							E.PROPERTY_".$prop["ID"]."=PEN.ID
						WHERE
							E.IBLOCK_ELEMENT_ID=".$ELEMENT_ID."
							AND P.ID=".$prop["ID"]."
							AND P.ID=PEN.PROPERTY_ID
							AND PEN.ID IN (".$ids.")
					";
				}
				else
				{
					$strSql = "
						INSERT INTO ".$strTable."
						(IBLOCK_ELEMENT_ID, IBLOCK_PROPERTY_ID, VALUE, VALUE_ENUM)
						SELECT ".$ELEMENT_ID.", P.ID, PEN.ID, PEN.ID
						FROM
							b_iblock_property P
							,b_iblock_property_enum PEN
						WHERE
							P.ID=".$prop["ID"]."
							AND P.ID=PEN.PROPERTY_ID
							AND PEN.ID IN (".$ids.")
					";
				}
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			elseif($prop["PROPERTY_TYPE"]=="G")
			{
				$bRecalcSections = true;
				$DB->Query(CIBLockElement::DeletePropertySQL($prop, $ELEMENT_ID), false, "File: ".__FILE__."<br>Line: ".__LINE__);
				if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="Y")
				{
					$strSql = "
						UPDATE	b_iblock_element_prop_s".$prop["IBLOCK_ID"]."
						SET	PROPERTY_".$prop["ID"]."=NULL, DESCRIPTION_".$prop["ID"]."=NULL
						WHERE	IBLOCK_ELEMENT_ID=".$ELEMENT_ID."
					";
					$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				}
				$DB->Query("DELETE FROM b_iblock_section_element WHERE ADDITIONAL_PROPERTY_ID=".$prop["ID"]." AND IBLOCK_ELEMENT_ID=".$ELEMENT_ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$ids = "0";
				foreach($PROP as $key=>$value)
				{
					if(is_array($value))
						$value = $value["VALUE"];

					if(IntVal($value)<=0) continue;
					$ids .= ",".IntVal($value);
					if($prop["MULTIPLE"]!="Y") break;
				}

				if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="N")
				{
					$strSql = "
						UPDATE
							b_iblock_element_prop_s".$prop["IBLOCK_ID"]." E
							,b_iblock_property P
							,b_iblock_section S
						SET
							E.PROPERTY_".$prop["ID"]."=S.ID
							,DESCRIPTION_".$prop["ID"]."=null
						WHERE
							E.IBLOCK_ELEMENT_ID=".$ELEMENT_ID."
							AND P.ID=".$prop["ID"]."
							AND S.IBLOCK_ID = P.LINK_IBLOCK_ID
							AND S.ID IN (".$ids.")
					";
				}
				else
				{
					$strSql = "
						INSERT INTO ".$strTable."
						(IBLOCK_ELEMENT_ID, IBLOCK_PROPERTY_ID, VALUE, VALUE_NUM)
						SELECT ".$ELEMENT_ID.", P.ID, S.ID, S.ID
						FROM
							b_iblock_property P
							,b_iblock_section S
						WHERE
							P.ID=".$prop["ID"]."
							AND S.IBLOCK_ID = P.LINK_IBLOCK_ID
							AND S.ID IN (".$ids.")
					";
				}
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$DB->Query(
					"INSERT INTO b_iblock_section_element(IBLOCK_ELEMENT_ID, IBLOCK_SECTION_ID, ADDITIONAL_PROPERTY_ID) ".
					"SELECT ".$ELEMENT_ID.", S.ID, P.ID ".
					"FROM b_iblock_property P, b_iblock_section S ".
					"WHERE P.ID=".$prop["ID"]." ".
					"	AND S.IBLOCK_ID = P.LINK_IBLOCK_ID ".
					"	AND S.ID IN (".$ids.") "
					, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			else
			{
				$ids = "0";
				$arV = Array();
				if($cacheValues===false || $prop["VERSION"]==2)
				{
					if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="N")
					{
						$strSql = "
							SELECT	concat(IBLOCK_ELEMENT_ID,':','".$prop["ID"]."') ID, PROPERTY_".$prop["ID"]." VALUE
							FROM	b_iblock_element_prop_s".$prop["IBLOCK_ID"]."
							WHERE	IBLOCK_ELEMENT_ID=".$ELEMENT_ID;
					}
					else
					{
						$strSql = "
							SELECT	ID, VALUE
							FROM	".$strTable."
							WHERE	IBLOCK_ELEMENT_ID=".$ELEMENT_ID."
								AND IBLOCK_PROPERTY_ID=".$prop["ID"];
					}
					$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					while($res = $db_res->Fetch())
						$arV[] = $res;
				}
				elseif(is_set($cacheValues, $prop["ID"]))
					$arV = $cacheValues[$prop["ID"]];

				$arWas = Array();
				foreach($arV as $res)
				{
					$val = $PROP[$res["ID"]];
					if(is_array($val) && !is_set($val, "tmp_name")&& !is_set($val, "del"))
					{
						$val_desc = $val["DESCRIPTION"];
						$val = $val["VALUE"];
					}
					else
					{
						$val_desc = false;
					}

					if($prop["PROPERTY_TYPE"]=="E")
					{
						if(in_array($val, $arWas))
							$val = "";
						else
							$arWas[] = $val;
					}

					if($prop["PROPERTY_TYPE"]=="S" || $prop["PROPERTY_TYPE"]=="N" || $prop["PROPERTY_TYPE"]=="E")
					{
						if(strlen($val)<=0)
						{
							if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="N")
							{
								$strSql = "
									UPDATE	b_iblock_element_prop_s".$prop["IBLOCK_ID"]."
									SET
										PROPERTY_".$prop["ID"]."=null
										,DESCRIPTION_".$prop["ID"]."=null
									WHERE	IBLOCK_ELEMENT_ID=".$ELEMENT_ID;
							}
							else
							{
								$strSql = "DELETE FROM ".$strTable." WHERE ID=".$res["ID"];
							}
							$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
							if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="Y")
							{
								$strSql = "
									UPDATE	b_iblock_element_prop_s".$prop["IBLOCK_ID"]."
									SET	PROPERTY_".$prop["ID"]."=NULL, DESCRIPTION_".$prop["ID"]."=NULL
									WHERE	IBLOCK_ELEMENT_ID=".$ELEMENT_ID."
								";
								$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
							}
						}
						else
						{
							if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="N")
							{
								if($prop["PROPERTY_TYPE"]=="N")
									$val = CIBlock::roundDB($val);
								$strSql = "
									UPDATE b_iblock_element_prop_s".$prop["IBLOCK_ID"]."
									SET PROPERTY_".$prop["ID"]."='".$DB->ForSql($val)."'
									,DESCRIPTION_".$prop["ID"]."=".($val_desc!==false?"'".$DB->ForSQL($val_desc, 255)."'":"null")."
									WHERE IBLOCK_ELEMENT_ID=".$ELEMENT_ID;
							}
							else
							{
								$strSql = "
									UPDATE ".$strTable."
									SET 	VALUE='".$DB->ForSql($val)."'
										,VALUE_NUM=".CIBlock::roundDB($val)."
										".($val_desc!==false ? ",DESCRIPTION='".$DB->ForSql($val_desc, 255)."'" : "")."
									WHERE ID=".$res["ID"];
							}
							$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
							if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="Y")
							{
								$strSql = "
									UPDATE	b_iblock_element_prop_s".$prop["IBLOCK_ID"]."
									SET	PROPERTY_".$prop["ID"]."=NULL, DESCRIPTION_".$prop["ID"]."=NULL
									WHERE	IBLOCK_ELEMENT_ID=".$ELEMENT_ID."
								";
								$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
							}
						}
					}
					elseif($prop["PROPERTY_TYPE"]=="F")
					{
						if(is_array($val))
						{
							if(strlen($val["del"]) > 0)
							{
								$val = "NULL";
							}
							else
							{
								$val["MODULE_ID"] = "iblock";
								if($val_desc!==false)
									$val["description"] = $val_desc;

								if(
									$val_desc !== false
									&& (strlen($val["name"]) <= 0)
								)
								{//update description only
									if($res["VALUE"] > 0)
										CFile::UpdateDesc($res["VALUE"], $val_desc);
									$val = false;
								}
								else
								{//register new file
									$val = CFile::SaveFile($val, "iblock");
								}
							}
						}

						if($val=="NULL")
						{
							CIBLockElement::DeleteFile($res["VALUE"], $ELEMENT_ID, "PROPERTY", -1, $prop["IBLOCK_ID"]);

							if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="N")
							{
								$strSql = "
									UPDATE b_iblock_element_prop_s".$prop["IBLOCK_ID"]."
									SET PROPERTY_".$prop["ID"]."=null
									,DESCRIPTION_".$prop["ID"]."=null
									WHERE IBLOCK_ELEMENT_ID=".$ELEMENT_ID;
							}
							else
							{
								$strSql = "DELETE FROM ".$strTable." WHERE ID=".$res["ID"];
							}
							$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

							if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="Y")
							{
								$strSql = "
									UPDATE	b_iblock_element_prop_s".$prop["IBLOCK_ID"]."
									SET	PROPERTY_".$prop["ID"]."=NULL, DESCRIPTION_".$prop["ID"]."=NULL
									WHERE	IBLOCK_ELEMENT_ID=".$ELEMENT_ID."
								";
								$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
							}
						}
						elseif(IntVal($val)>0)
						{
							if(intval($val) != $res["VALUE"])
								CIBLockElement::DeleteFile($res["VALUE"], $ELEMENT_ID, "PROPERTY", -1, $prop["IBLOCK_ID"]);

							if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="N")
							{
								$strSql = "
									UPDATE b_iblock_element_prop_s".$prop["IBLOCK_ID"]."
									SET PROPERTY_".$prop["ID"]."='".intval($val)."'
									".($val_desc!==false ? ",DESCRIPTION_".$prop["ID"]."='".$DB->ForSql($val_desc, 255)."'" : "")."
									WHERE IBLOCK_ELEMENT_ID=".$ELEMENT_ID;
							}
							else
							{
								$strSql = "
									UPDATE ".$strTable."
									SET 	VALUE='".intval($val)."'
										,VALUE_NUM='".intval($val)."'
										".($val_desc!==false ? ",DESCRIPTION='".$DB->ForSql($val_desc, 255)."'" : "")."
									WHERE ID=".$res["ID"];
							}
							$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

							if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="Y")
							{
								$strSql = "
									UPDATE	b_iblock_element_prop_s".$prop["IBLOCK_ID"]."
									SET	PROPERTY_".$prop["ID"]."=NULL, DESCRIPTION_".$prop["ID"]."=NULL
									WHERE	IBLOCK_ELEMENT_ID=".$ELEMENT_ID."
								";
								$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
							}
						}
						elseif($val_desc!==false)
						{
							if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="N")
							{
								$strSql = "
									UPDATE b_iblock_element_prop_s".$prop["IBLOCK_ID"]."
									SET DESCRIPTION_".$prop["ID"]."='".$DB->ForSql($val_desc, 255)."'
									WHERE IBLOCK_ELEMENT_ID=".$ELEMENT_ID."
								";
							}
							else
							{
								$strSql = "
									UPDATE ".$strTable."
									SET DESCRIPTION='".$DB->ForSql($val_desc, 255)."'
									WHERE ID=".$res["ID"]."
								";
							}
							$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
							if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="Y")
							{
								$strSql = "
									UPDATE	b_iblock_element_prop_s".$prop["IBLOCK_ID"]."
									SET	PROPERTY_".$prop["ID"]."=NULL, DESCRIPTION_".$prop["ID"]."=NULL
									WHERE	IBLOCK_ELEMENT_ID=".$ELEMENT_ID."
								";
								$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
							}
						}
					}
					$ids .= ",".$res["ID"];
					unset($PROP[$res["ID"]]);
				} //foreach($arV as $res)

				foreach($PROP as $key=>$val)
				{
					if(is_array($val) && !is_set($val, "tmp_name"))
					{
						$val_desc = $val["DESCRIPTION"];
						$val = $val["VALUE"];
					}
					else
						$val_desc = false;

					if($prop["PROPERTY_TYPE"]=="F")
					{
						if(is_array($val))
						{
							$val["MODULE_ID"] = "iblock";
							if($val_desc!==false)
								$val["description"] = $val_desc;

							$val = CFile::SaveFile($val, "iblock");
						}

						if(intval($val)<=0)
						{
							$val = false;
						}
						elseif($prop["MULTIPLE"]!="Y" && strlen($val)>0)
						{
							$strSql = "
								SELECT VALUE
								FROM b_iblock_element_property
								WHERE
									IBLOCK_ELEMENT_ID=".$ELEMENT_ID."
									AND IBLOCK_PROPERTY_ID=".IntVal($prop["ID"])."
							";
							if($prop["VERSION"]==2)
							{
								if($prop["MULTIPLE"]=="Y")
									$strSql = "
										SELECT PROPERTY_".$prop["ID"]." VALUE
										FROM b_iblock_element_prop_m".$prop["IBLOCK_ID"]."
										WHERE
										IBLOCK_ELEMENT_ID=".$ELEMENT_ID."
										AND IBLOCK_PROPERTY_ID=".IntVal($prop["ID"])."
									";
								else
									$strSql = "
										SELECT PROPERTY_".$prop["ID"]." VALUE
										FROM b_iblock_element_prop_s".$prop["IBLOCK_ID"]."
										WHERE IBLOCK_ELEMENT_ID=".$ELEMENT_ID."
									";
							}
							$pfres = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
							while($pfar = $pfres->Fetch())
								CIBLockElement::DeleteFile($pfar["VALUE"], $ELEMENT_ID, "PROPERTY", -1, $prop["IBLOCK_ID"]);

							$DB->Query(CIBLockElement::DeletePropertySQL($prop, $ELEMENT_ID), false, "File: ".__FILE__."<br>Line: ".__LINE__);
							if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="Y")
							{
								$strSql = "
									UPDATE	b_iblock_element_prop_s".$prop["IBLOCK_ID"]."
									SET	PROPERTY_".$prop["ID"]."=NULL, DESCRIPTION_".$prop["ID"]."=NULL
									WHERE	IBLOCK_ELEMENT_ID=".$ELEMENT_ID."
								";
								$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
							}
						}
					}
					elseif($prop["PROPERTY_TYPE"]=="E")
					{
						if(in_array($val, $arWas))
							$val = "";
						else
							$arWas[] = $val;
					}

					if(strlen($val)>0)
					{
						if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="N")
						{
							$strSql = "
								UPDATE	b_iblock_element_prop_s".$prop["IBLOCK_ID"]."
								SET
									PROPERTY_".$prop["ID"]." = '".$DB->ForSql($val)."'
									,DESCRIPTION_".$prop["ID"]."=".($val_desc!==false?"'".$DB->ForSQL($val_desc, 255)."'":"null")."
								WHERE	IBLOCK_ELEMENT_ID=".$ELEMENT_ID;
						}
						else
						{
							$strSql = "
								INSERT INTO ".$strTable."
								(IBLOCK_ELEMENT_ID, IBLOCK_PROPERTY_ID, VALUE, VALUE_NUM".($val_desc!==false?", DESCRIPTION":"").")
								SELECT
									".$ELEMENT_ID."
									,P.ID
									,'".$DB->ForSql($val)."'
									,".CIBlock::roundDB($val)."
									".($val_desc!==false?", '".$DB->ForSQL($val_desc, 255)."'":"")."
								FROM	b_iblock_property P
								WHERE	ID=".IntVal($prop["ID"]);
						}
						$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
						if($prop["VERSION"]==2 && $prop["MULTIPLE"]=="Y")
						{
							$strSql = "
								UPDATE	b_iblock_element_prop_s".$prop["IBLOCK_ID"]."
								SET	PROPERTY_".$prop["ID"]."=NULL, DESCRIPTION_".$prop["ID"]."=NULL
								WHERE	IBLOCK_ELEMENT_ID=".$ELEMENT_ID."
							";
							$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
						}

						if($prop["MULTIPLE"]!="Y") break;
					}
					$ids .= ",".$prop["ID"];
				} //foreach($PROP as $key=>$value)
			} //if($prop["PROPERTY_TYPE"]=="L")
		}

		if($bRecalcSections)
			CIBlockElement::RecalcSections($ELEMENT_ID);

		/****************************** QUOTA ******************************/
			$_SESSION["SESS_RECOUNT_DB"] = "Y";
		/****************************** QUOTA ******************************/
	}

	public static function GetRandFunction()
	{
		return " RAND(".rand(0, 1000000).") ";
	}

	public static function GetShowedFunction()
	{
		return " IfNULL(BE.SHOW_COUNTER/((UNIX_TIMESTAMP(now())-UNIX_TIMESTAMP(BE.SHOW_COUNTER_START)+0.1)/60/60),0) ";
	}

	///////////////////////////////////////////////////////////////////
	// Update list of elements w/o any events
	///////////////////////////////////////////////////////////////////
	protected function UpdateList($arFields, $arFilter = array())
	{
		global $DB, $USER, $USER_FIELD_MANAGER;

		$strUpdate = $DB->PrepareUpdate("b_iblock_element", $arFields, "iblock", false, "BE");
		if ($strUpdate == "")
			return false;

		$element = new CIBlockElement;
		$element->strField = "ID";
		$element->GetList(array(), $arFilter, false, false, array("ID"));

		$strSql = "
			UPDATE ".$element->sFrom." SET ".$strUpdate."
			WHERE 1=1 ".$element->sWhere."
		";

		return $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
	}
}
?>
