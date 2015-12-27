<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/order_tax.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleordertax/index.php
 * @author Bitrix
 */
class CSaleOrderTax extends CAllSaleOrderTax
{
	
	/**
	* <p>Метод возвращает результат выборки записей из налогов заказа в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array("TAX_NAME" Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле налогов заказа, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и т.д.).
	*
	* @param AS $C  Массив, в соответствии с которым фильтруются записи налогов
	* заказа. Массив имеет вид: <pre class="syntax">array(
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
	* заказов.<br><br> Пример фильтра: <pre class="syntax">array("ORDER_ID" =&gt; 125)</pre> Этот
	* фильтр означает "выбрать все записи, в которых значение в поле
	* ORDER_ID (код налога) равно 125".<br><br> Значение по умолчанию - пустой
	* массив array() - означает, что результат отфильтрован не будет.
	*
	* @param  $array  Массив полей, по которым группируются записи вариантов налогов
	* заказа. Массив имеет вид: <pre class="syntax">array("название_поля1",
	* "группирующая_функция2" =&gt; "название_поля2", ...)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле налогов заказа. В
	* качестве группирующей функции могут стоять: <ul> <li> <b> COUNT</b> -
	* подсчет количества;</li> <li> <b>AVG</b> - вычисление среднего значения;</li>
	* <li> <b>MIN</b> - вычисление минимального значения;</li> <li> <b> MAX</b> -
	* вычисление максимального значения;</li> <li> <b>SUM</b> - вычисление
	* суммы.</li> </ul> Если массив пустой, то метод вернет число записей,
	* удовлетворяющих фильтру.<br><br> Значение по умолчанию - <i>false</i> -
	* означает, что результат группироваться не будет.
	*
	* @param arFilte $r = array() Массив параметров выборки. Может содержать следующие ключи: <ul>
	* <li>"<b>nTopCount</b>" - количество возвращаемых методом записей будет
	* ограничено сверху значением этого ключа;</li> <li> любой ключ,
	* принимаемый методом <b> CDBResult::NavQuery</b> в качестве третьего
	* параметра.</li> </ul> Значение по умолчанию - <i>false</i> - означает, что
	* параметров выборки нет.
	*
	* @param array $arGroupBy = false Массив полей записей, которые будут возвращены методом. Можно
	* указать только те поля, которые необходимы. Если в массиве
	* присутствует значение "*", то будут возвращены все доступные
	* поля.<br><br> Значение по умолчанию - пустой массив array() - означает,
	* что будут возвращены все поля основной таблицы запроса.
	*
	* @param array $arNavStartParams = false 
	*
	* @param array $arSelectFields = array() Код суммы налогов. </ht
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий ассоциативные
	* массивы с ключами</p> <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th>
	* <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код суммы налогов.</td> </tr> <tr> <td>ORDER_ID</td>
	* <td>Код заказа.</td> </tr> <tr> <td>TAX_NAME</td> <td>Название налога.</td> </tr> <tr>
	* <td>VALUE</td> <td>Ставка налога.</td> </tr> <tr> <td>VALUE_MONEY</td> <td>Сумма налога.</td>
	* </tr> <tr> <td>APPLY_ORDER</td> <td>Порядок применения.</td> </tr> <tr> <td>CODE</td>
	* <td>Символьный код налога.</td> </tr> <tr> <td>IS_IN_PRICE</td> <td>Флаг (Y/N) включен
	* ли налог в цену товара.</td> </tr> <tr> <td>IS_PERCENT</td> <td>Y</td> </tr> </table> <p>Если в
	* качестве параметра arGroupBy передается пустой массив, то метод
	* вернет число записей, удовлетворяющих фильтру.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arTaxList = array();
	* // Выберем все суммы налогов для заказа $ORDER_ID
	* $db_tax_list = CSaleOrderTax::GetList(array("APPLY_ORDER"=&gt;"ASC"), Array("ORDER_ID"=&gt;$ORDER_ID));
	* 
	* $iNds = -1;
	* $i = 0;
	* while ($ar_tax_list = $db_tax_list-&gt;Fetch())
	* {
	*    $arTaxList[$i] = $ar_tax_list;
	*    // определяем, какой из налогов - НДС, предполагая, что его символьный код - NDS
	*    if ($arTaxList[$i]["CODE"] == "NDS")
	*       $iNds = $i;
	* 
	*    $i++;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleordertax/csaleordertax__getlist.1c2a6c7d.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array("TAX_NAME" => "ASC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "T.ID", "TYPE" => "int"),
				"ORDER_ID" => array("FIELD" => "T.ORDER_ID", "TYPE" => "int"),
				"TAX_NAME" => array("FIELD" => "T.TAX_NAME", "TYPE" => "string"),
				"VALUE" => array("FIELD" => "T.VALUE", "TYPE" => "double"),
				"VALUE_MONEY" => array("FIELD" => "T.VALUE_MONEY", "TYPE" => "double"),
				"APPLY_ORDER" => array("FIELD" => "T.APPLY_ORDER", "TYPE" => "int"),
				"CODE" => array("FIELD" => "T.CODE", "TYPE" => "string"),
				"IS_PERCENT" => array("FIELD" => "T.IS_PERCENT", "TYPE" => "char"),
				"IS_IN_PRICE" => array("FIELD" => "T.IS_IN_PRICE", "TYPE" => "char")
			);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_order_tax T ".
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
			"FROM b_sale_order_tax T ".
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
				"FROM b_sale_order_tax T ".
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
	* <p>Метод добавляет новую сумму налога к заказу. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров новой записи, ключами в котором
	* являются названия параметров, а значениями - соответствующие
	* значения.<br> Допустимые ключи:<ul> <li> <b>ORDER_ID</b> - код заказа
	* (обязательный);</li> <li> <b>TAX_NAME</b> - название налога;</li> <li> <b>VALUE</b> -
	* величина налога (в процентах);</li> <li> <b>VALUE_MONEY</b> - общая сумма этого
	* налога;</li> <li> <b>APPLY_ORDER</b> - порядок применения;</li> <li> <b>CODE</b> -
	* символьный код налога;</li> <li> <b>IS_PERCENT</b> - должно быть значение "Y";</li>
	* <li> <b>IS_IN_PRICE</b> - флаг (Y/N) входит ли налог уже в цену товара.</li> </ul>
	*
	* @return int <p>Возвращается код добавленной суммы налога или <i>false</i> в случае
	* ошибки. </p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arFields = array(
	*    "ORDER_ID" =&gt; 12789,
	*    "TAX_NAME" =&gt; "НДС",
	*    "IS_PERCENT" =&gt; "Y",
	*    "VALUE" =&gt; 3.5,
	*    "VALUE_MONEY" =&gt; 6948.55,
	*    "APPLY_ORDER" =&gt; 300,
	*    "IS_IN_PRICE" =&gt; "N",
	*    "CODE" =&gt; "NDS"
	* );
	* 
	* CSaleOrderTax::Add($arFields);
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleordertax/csaleordertax__add.91944147.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		if (!CSaleOrderTax::CheckFields("ADD", $arFields))
			return false;

		$dbResult = CSaleOrderTax::GetList(
			array(),
			array(
				"ORDER_ID" => $arFields['ORDER_ID'],
				"TAX_NAME" => $arFields['TAX_NAME'],
				"CODE" => $arFields['CODE'],
			),
			false,
			false,
			array("ID")
		);
		if ($dbResult->Fetch())
		{
			return false;
		}

		$arInsert = $DB->PrepareInsert("b_sale_order_tax", $arFields);
		$strSql =
			"INSERT INTO b_sale_order_tax(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		return $ID;
	}
}
?>