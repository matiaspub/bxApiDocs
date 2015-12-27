<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/pay_system_action.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystemaction/index.php
 * @author Bitrix
 */
class CSalePaySystemAction extends CAllSalePaySystemAction
{
	
	/**
	* <p>Метод возвращает результат выборки записей из обработчиков платежных систем в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле обработчиков платежных систем, а в
	* качестве "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>"
	* (по возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив
	* сортировки имеет несколько элементов, то результирующий набор
	* сортируется последовательно по каждому элементу (т.е. сначала
	* сортируется по первому элементу, потом результат сортируется по
	* второму и т.д.). <br><br> Значение по умолчанию - пустой массив array() -
	* означает, что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи обработчиков
	* платежных систем. Массив имеет вид: <pre class="syntax">array(
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
	* </ul> В качестве "название_поляX" может стоять любое поле
	* заказов.<br><br> Пример фильтра: <pre class="syntax">array("!PERSON_TYPE_ID" =&gt; 5)</pre> Этот
	* фильтр означает "выбрать все записи, в которых значение в поле
	* PERSON_TYPE_ID (код типа плательщика) не равно 5".<br><br> Значение по
	* умолчанию - пустой массив array() - означает, что результат
	* отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи обработчиков
	* платежных систем. Массив имеет вид: <pre class="syntax">array("название_поля1",
	* "группирующая_функция2" =&gt; "название_поля2", ...)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле обработчиков
	* платежных систем. В качестве группирующей функции могут стоять:
	* <ul> <li> <b> COUNT</b> - подсчет количества;</li> <li> <b>AVG</b> - вычисление
	* среднего значения;</li> <li> <b>MIN</b> - вычисление минимального
	* значения;</li> <li> <b> MAX</b> - вычисление максимального значения;</li> <li>
	* <b>SUM</b> - вычисление суммы.</li> </ul> Этот фильтр означает "выбрать все
	* записи, в которых значение в поле LID (сайт системы) не равно en".<br><br>
	* Значение по умолчанию - <i>false</i> - означает, что результат
	* группироваться не будет.
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
	* массивы параметров обработчиков платежных систем с ключами:</p>
	* <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td>
	* <td>Код обработчика платежной системы.</td> </tr> <tr> <td>PAY_SYSTEM_ID</td> <td>Код
	* платежной системы.</td> </tr> <tr> <td>PERSON_TYPE_ID</td> <td>Код типа
	* плательщика.</td> </tr> <tr> <td>NAME</td> <td>Название платежной системы.</td> </tr>
	* <tr> <td>ACTION_FILE</td> <td>Скрипт платежной системы.</td> </tr> <tr> <td>RESULT_FILE</td>
	* <td>Скрипт получения результатов.</td> </tr> <tr> <td>NEW_WINDOW</td> <td>Флаг (Y/N)
	* открывать ли скрипт платежной системы в новом окне.</td> </tr> <tr>
	* <td>PARAMS</td> <td>Параметры вызова обработчика.</td> </tr> <tr> <td>HAVE_PAYMENT</td>
	* <td>Есть вариант обработчика для работы после оформления
	* заказа.</td> </tr> <tr> <td>HAVE_ACTION</td> <td>Есть вариант обработчика для
	* мгновенного списания денег.</td> </tr> <tr> <td>HAVE_RESULT</td> <td>Есть скрипт
	* запроса результатов.</td> </tr> <tr> <td>HAVE_PREPAY</td> <td>Есть вариант
	* обработчика для работы во время оформления заказа.</td> </tr> <tr>
	* <td>PS_LID</td> <td>Сайт платежной системы.</td> </tr> <tr> <td>PS_CURRENCY</td> <td>Валюта
	* платежной системы.</td> </tr> <tr> <td>PS_NAME</td> <td>Название платежной
	* системы.</td> </tr> <tr> <td>PS_ACTIVE</td> <td>Активность платежной системы.</td>
	* </tr> <tr> <td>PS_SORT</td> <td>Индекс сортировки платежной системы.</td> </tr> <tr>
	* <td>PS_DESCRIPTION</td> <td>Описание платежной системы.</td> </tr> <tr> <td>PT_LID</td>
	* <td>Сайт типа плательщика.</td> </tr> <tr> <td>PT_NAME</td> <td>Название типа
	* плательщика.</td> </tr> <tr> <td>PT_SORT</td> <td>Индекс сортировки типа
	* плательщика.</td> </tr> </table> <p>Если в качестве параметра arGroupBy
	* передается пустой массив, то метод вернет число записей,
	* удовлетворяющих фильтру.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystemaction/csalepaysystemaction__getlist.324e3583.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (!is_array($arOrder) && !is_array($arFilter))
		{
			$arOrder = strval($arOrder);
			$arFilter = strval($arFilter);
			if (strlen($arOrder) > 0 && strlen($arFilter) > 0)
				$arOrder = array($arOrder => $arFilter);
			else
				$arOrder = array();
			if (is_array($arGroupBy))
				$arFilter = $arGroupBy;
			else
				$arFilter = array();
			$arGroupBy = false;
		}

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "PAY_SYSTEM_ID", "PERSON_TYPE_ID", "NAME", "ACTION_FILE", "RESULT_FILE", "NEW_WINDOW", "PARAMS", "TARIF", "ENCODING", "LOGOTIP");

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "PSA.ID", "TYPE" => "int"),
				"PAY_SYSTEM_ID" => array("FIELD" => "PSA.PAY_SYSTEM_ID", "TYPE" => "int"),
				"PERSON_TYPE_ID" => array("FIELD" => "PSA.PERSON_TYPE_ID", "TYPE" => "int"),
				"NAME" => array("FIELD" => "PSA.NAME", "TYPE" => "string"),
				"ACTION_FILE" => array("FIELD" => "PSA.ACTION_FILE", "TYPE" => "string"),
				"RESULT_FILE" => array("FIELD" => "PSA.RESULT_FILE", "TYPE" => "string"),
				"NEW_WINDOW" => array("FIELD" => "PSA.NEW_WINDOW", "TYPE" => "char"),
				"PARAMS" => array("FIELD" => "PSA.PARAMS", "TYPE" => "string"),
				"TARIF" => array("FIELD" => "PSA.TARIF", "TYPE" => "string"),
				"HAVE_PAYMENT" => array("FIELD" => "PSA.HAVE_PAYMENT", "TYPE" => "char"),
				"HAVE_ACTION" => array("FIELD" => "PSA.HAVE_ACTION", "TYPE" => "char"),
				"HAVE_RESULT" => array("FIELD" => "PSA.HAVE_RESULT", "TYPE" => "char"),
				"HAVE_PREPAY" => array("FIELD" => "PSA.HAVE_PREPAY", "TYPE" => "char"),
				"HAVE_RESULT_RECEIVE" => array("FIELD" => "PSA.HAVE_RESULT_RECEIVE", "TYPE" => "char"),
				"ENCODING" => array("FIELD" => "PSA.ENCODING", "TYPE" => "string"),
				"LOGOTIP" => array("FIELD" => "PSA.LOGOTIP", "TYPE" => "int"),
				"PS_LID" => array("FIELD" => "PS.LID", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_pay_system PS ON (PSA.PAY_SYSTEM_ID = PS.ID)"),
				"PS_CURRENCY" => array("FIELD" => "PS.CURRENCY", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_pay_system PS ON (PSA.PAY_SYSTEM_ID = PS.ID)"),
				"PS_NAME" => array("FIELD" => "PS.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_pay_system PS ON (PSA.PAY_SYSTEM_ID = PS.ID)"),
				"PS_ACTIVE" => array("FIELD" => "PS.ACTIVE", "TYPE" => "char", "FROM" => "INNER JOIN b_sale_pay_system PS ON (PSA.PAY_SYSTEM_ID = PS.ID)"),
				"PS_SORT" => array("FIELD" => "PS.SORT", "TYPE" => "int", "FROM" => "INNER JOIN b_sale_pay_system PS ON (PSA.PAY_SYSTEM_ID = PS.ID)"),
				"PS_DESCRIPTION" => array("FIELD" => "PS.DESCRIPTION", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_pay_system PS ON (PSA.PAY_SYSTEM_ID = PS.ID)"),
				"PT_LID" => array("FIELD" => "PT.LID", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_person_type PT ON (PSA.PERSON_TYPE_ID = PT.ID)"),
				"PT_NAME" => array("FIELD" => "PT.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_person_type PT ON (PSA.PERSON_TYPE_ID = PT.ID)"),
				"PT_SORT" => array("FIELD" => "PT.SORT", "TYPE" => "int", "FROM" => "INNER JOIN b_sale_person_type PT ON (PSA.PERSON_TYPE_ID = PT.ID)"),
				"PT_ACTIVE" => array("FIELD" => "PT.ACTIVE", "TYPE" => "char", "FROM" => "INNER JOIN b_sale_person_type PT ON (PSA.PERSON_TYPE_ID = PT.ID)"),
			);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_pay_system_action PSA ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			//echo "!1!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return False;
		}

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_sale_pay_system_action PSA ".
			"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])<=0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_sale_pay_system_action PSA ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			//echo "!2.1!=".htmlspecialcharsbx($strSql_tmp)."<br>";

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (strlen($arSqls["GROUPBY"]) <= 0)
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				// FOR MYSQL!!! ANOTHER CODE FOR ORACLE
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			//echo "!2.2!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])>0)
				$strSql .= "LIMIT ".IntVal($arNavStartParams["nTopCount"]);

			//echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

	
	/**
	* <p>Метод добавляет новый обработчик платежной системы на основании параметров из массива arFields. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров нового обработчика платежной
	* системы, ключами в котором являются названия параметров, а
	* значениями - соответствующие значения.<br> Допустимые ключи:<ul> <li>
	* <b>PAY_SYSTEM_ID</b> - код платежной системы;</li> <li> <b>PERSON_TYPE_ID</b> - код типа
	* плательщика;</li> <li> <b>NAME</b> - название платежной системы;</li> <li>
	* <b>ACTION_FILE</b> - скрипт платежной системы;</li> <li> <b>RESULT_FILE</b> - скрипт
	* получения результатов;</li> <li> <b>NEW_WINDOW</b> - флаг (Y/N) открывать ли
	* скрипт платежной системы в новом окне</li> <li> <b>PARAMS</b> - параметры
	* вызова обработчика</li> <li> <b>HAVE_PAYMENT</b> - есть вариант обработчика
	* для работы после оформления заказа</li> <li> <b>HAVE_ACTION</b> - есть вариант
	* обработчика для мгновенного списания денег</li> <li> <b>HAVE_RESULT</b> - есть
	* скрипт запроса результатов</li> <li> <b>HAVE_PREPAY</b> - есть вариант
	* обработчика для работы во время оформления заказа.</li> </ul>
	*
	* @return int <p>Возвращается код обновленной записи или <i>false</i> - в случае
	* ошибки.  </p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystemaction/csalepaysystemaction__add.d76269ee.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		if (!CSalePaySystemAction::CheckFields("ADD", $arFields))
			return false;

		if (array_key_exists("LOGOTIP", $arFields) && is_array($arFields["LOGOTIP"]))
			$arFields["LOGOTIP"]["MODULE_ID"] = "sale";

		CFile::SaveForDB($arFields, "LOGOTIP", "sale/paysystem/logotip");

		$arInsert = $DB->PrepareInsert("b_sale_pay_system_action", $arFields);

		$strSql =
			"INSERT INTO b_sale_pay_system_action(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		return $ID;
	}

	
	/**
	* <p>Метод обновляет параметры обработчика с кодом ID платежной системы в соответствии с массивом arFields. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код обработчика платежной системы.
	*
	* @param array $arFields  Ассоциативный массив новых параметров платежной системы,
	* ключами в котором являются названия параметров, а значениями -
	* соответствующие значения.<br> Допустимые ключи:<ul> <li> <b>PAY_SYSTEM_ID</b> -
	* код платежной системы;</li> <li> <b>PERSON_TYPE_ID</b> - код типа плательщика;</li>
	* <li> <b>NAME</b> - название платежной системы;</li> <li> <b>ACTION_FILE</b> - скрипт
	* платежной системы;</li> <li> <b>RESULT_FILE</b> - скрипт получения
	* результатов;</li> <li> <b>NEW_WINDOW</b> - флаг (Y/N) открывать ли скрипт
	* платежной системы в новом окне</li> <li> <b>PARAMS</b> - параметры вызова
	* обработчика</li> <li> <b>HAVE_PAYMENT</b> - есть вариант обработчика для
	* работы после оформления заказа</li> <li> <b>HAVE_ACTION</b> - есть вариант
	* обработчика для мгновенного списания денег</li> <li> <b>HAVE_RESULT</b> - есть
	* скрипт запроса результатов</li> <li> <b>HAVE_PREPAY</b> - есть вариант
	* обработчика для работы во время оформления заказа.</li> </ul>
	*
	* @return int <p>Возвращается код обновленной записи или <i>false</i> - в случае
	* ошибки.  </p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystemaction/csalepaysystemaction__update.f76269ee.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = IntVal($ID);
		if (!CSalePaySystemAction::CheckFields("UPDATE", $arFields))
			return false;

		if (array_key_exists("LOGOTIP", $arFields) && is_array($arFields["LOGOTIP"]))
			$arFields["LOGOTIP"]["MODULE_ID"] = "sale";

		CFile::SaveForDB($arFields, "LOGOTIP", "sale/paysystem/logotip");

		$strUpdate = $DB->PrepareUpdate("b_sale_pay_system_action", $arFields);
		$strSql = "UPDATE b_sale_pay_system_action SET ".$strUpdate." WHERE ID = ".$ID."";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}
}
?>