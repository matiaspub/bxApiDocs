<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/order_user_props.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderuserprops/index.php
 * @author Bitrix
 */
class CSaleOrderUserProps extends CAllSaleOrderUserProps
{
	
	/**
	* <p>Метод возвращает результат выборки записей из профилей покупателя в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле налогов заказа, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и
	* т.д.). <br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи профилей
	* покупателя. Массив имеет вид: <pre class="syntax">array(
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
	* заказов.<br><br> Пример фильтра: <pre class="syntax">array("~NAME" =&gt; "John*")</pre> Этот
	* фильтр означает "выбрать все записи, в которых значение в поле NAME
	* (название профиля) начинается с John".<br><br> Значение по умолчанию -
	* пустой массив array() - означает, что результат отфильтрован не
	* будет.<br><br><div class="note"> <b>Важно!</b> Если результат фильтровать не
	* нужно, то пустой массив фильтра надо передать обязательно. Это
	* требуется из-за наследия старого API. </div>
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи вариантов профилей
	* покупателя. Массив имеет вид: <pre class="syntax">array("название_поля1",
	* "группирующая_функция2" =&gt; "название_поля2", ...)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле профилей покупателя.
	* В качестве группирующей функции могут стоять: <ul> <li> <b> COUNT</b> -
	* подсчет количества;</li> <li> <b>AVG</b> - вычисление среднего значения;</li>
	* <li> <b>MIN</b> - вычисление минимального значения;</li> <li> <b> MAX</b> -
	* вычисление максимального значения;</li> <li> <b>SUM</b> - вычисление
	* суммы.</li> </ul> Если массив пустой, то метод вернет число записей,
	* удовлетворяющих фильтру.<br><br> Значение по умолчанию - <i>false</i> -
	* означает, что результат группироваться не будет.
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
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий набор
	* ассоциативных массивов параметров профиля покупателя с
	* ключами:</p> <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th>
	* </tr> <tr> <td>ID</td> <td>Код профиля покупателя.</td> </tr> <tr> <td>NAME</td>
	* <td>Название профиля.</td> </tr> <tr> <td>USER_ID</td> <td>Код пользователя,
	* которому принадлежит профиль.</td> </tr> <tr> <td>PERSON_TYPE_ID</td> <td>Тип
	* плательщика.</td> </tr> <tr> <td>DATE_UPDATE</td> <td>Дата последнего изменения
	* профиля.</td> </tr> </table> <p>Если в качестве параметра arGroupBy передается
	* пустой массив, то метод вернет число записей, удовлетворяющих
	* фильтру.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выберем все профили покупателя для текущего пользователя, 
	* // упорядочив результат по дате последнего изменения
	* $db_sales = CSaleOrderUserProps::GetList(
	*         array("DATE_UPDATE" =&gt; "DESC"),
	*         array("USER_ID" =&gt; $USER-&gt;GetID())
	*     );
	* 
	* while ($ar_sales = $db_sales-&gt;Fetch())
	* {
	*    echo $ar_sales["NAME"]."&lt;br&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderuserprops/csaleorderuserprops__getlist.244c2c9f.php
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
			$arSelectFields = array("ID", "NAME", "USER_ID", "PERSON_TYPE_ID", "DATE_UPDATE");

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "P.ID", "TYPE" => "int"),
				"NAME" => array("FIELD" => "P.NAME", "TYPE" => "string"),
				"USER_ID" => array("FIELD" => "P.USER_ID", "TYPE" => "int"),
				"PERSON_TYPE_ID" => array("FIELD" => "P.PERSON_TYPE_ID", "TYPE" => "int"),
				"DATE_UPDATE" => array("FIELD" => "P.DATE_UPDATE", "TYPE" => "datetime"),
				"FORMAT_DATE_UPDATE" => array("FIELD" => "P.DATE_UPDATE", "TYPE" => "datetime"),
				"DATE_UPDATE_FORMAT" => array("FIELD" => "P.DATE_UPDATE", "TYPE" => "datetime"),
				"XML_ID" => array("FIELD" => "P.XML_ID", "TYPE" => "string"),
				"VERSION_1C" => array("FIELD" => "P.VERSION_1C", "TYPE" => "string"),
			);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_user_props P ".
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
			"FROM b_sale_user_props P ".
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
				"FROM b_sale_user_props P ".
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
	* <p>Метод сохраняет новый профиль покупателя с параметрами из массива arFields. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров нового профиля. Ключами
	* являются названия параметров, а значениями - соответствующие
	* значения.<br><br> Допустимые ключи:<ul> <li> <b>NAME</b> - название профиля
	* покупателя;</li> <li> <b>USER_ID</b> - код пользователя, которому
	* принадлежит профиль;</li> <li> <b>PERSON_TYPE_ID</b> - тип плательщика;</li> <li>
	* <b>DATE_UPDATE</b> - дата последнего изменения.</li> </ul>
	*
	* @return int <p>Возвращается код добавленного профиля или <i>false</i> в случае
	* ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arFields = array(
	*    "NAME" =&gt; "Профиль 1",
	*    "USER_ID" =&gt; $USER-&gt;GetID(),
	*    "PERSON_TYPE_ID" =&gt; 2
	* );
	* $USER_PROPS_ID = CSaleOrderUserProps::Add($arFields);
	* ?&gt;
	* </bod
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderuserprops/csaleorderuserprops__add.110a5a48.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		if (!CSaleOrderUserProps::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_sale_user_props", $arFields);

		$strSql =
			"INSERT INTO b_sale_user_props(".$arInsert[0].", DATE_UPDATE) ".
			"VALUES(".$arInsert[1].", ".$DB->GetNowFunction().")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		return $ID;
	}
}
?>