<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/pay_system.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystem/index.php
 * @author Bitrix
 */
class CSalePaySystem extends CAllSalePaySystem
{
	
	/**
	* <p>Метод возвращает результат выборки записей из платежных систем в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array(("SORT"=>"ASC" Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array(<br>"название_поля1" =&gt;
	* "направление_сортировки1",<br>"название_поля2" =&gt;
	* "направление_сортировки2",<br>. . .<br>)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле платежных систем, а в
	* качестве "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>"
	* (по возрастанию) и "<i>DESC</i>" (по убыванию). <br><br> Если массив
	* сортировки имеет несколько элементов, то результирующий набор
	* сортируется последовательно по каждому элементу (т.е. сначала
	* сортируется по первому элементу, потом результат сортируется по
	* второму и т.д.). 
	*
	* @param NAM $E  Массив, в соответствии с которым фильтруются записи платежных
	* систем. Массив имеет вид: <pre
	* class="syntax">array(<br>"[модификатор1][оператор1]название_поля1" =&gt;
	* "значение1",<br>"[модификатор2][оператор2]название_поля2" =&gt;
	* "значение2",<br>. . .<br>)</pre> Удовлетворяющие фильтру записи
	* возвращаются в результате, а записи, которые не удовлетворяют
	* условиям фильтра, отбрасываются. <br><br> Допустимыми являются
	* следующие модификаторы: <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> -
	* значения null, 0 и пустая строка так же удовлетворяют условиям
	* фильтра.</li> </ul> Допустимыми являются следующие операторы: <ul> <li>
	* <b>&gt;=</b> - значение поля больше или равно передаваемой в фильтр
	* величины;</li> <li> <b>&gt;</b> - значение поля строго больше передаваемой
	* в фильтр величины;</li> <li> <b>&lt;=</b> - значение поля меньше или равно
	* передаваемой в фильтр величины;</li> <li> <b>&lt;</b> - значение поля
	* строго меньше передаваемой в фильтр величины;</li> <li> <b>@</b> -
	* значение поля находится в передаваемом в фильтр разделенном
	* запятой списке значений;</li> <li> <b>~</b> - значение поля проверяется на
	* соответствие передаваемому в фильтр шаблону;</li> <li> <b>%</b> -
	* значение поля проверяется на соответствие передаваемой в фильтр
	* строке в соответствии с языком запросов.</li> </ul> В качестве
	* "название_поляX" может стоять любое поле заказов. <br><br> Значение по
	* умолчанию - пустой массив array() - означает, что результат
	* отфильтрован не будет.
	*
	* @param AS $C  Массив полей, по которым группируются записи платежных систем.
	* Массив имеет вид: <pre class="syntax">array("название_поля1",<br>
	* "группирующая_функция2" =&gt; "название_поля2", ...)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле платежных систем. В
	* качестве группирующей функции могут стоять: <ul> <li> <b> COUNT</b> -
	* подсчет количества;</li> <li> <b>AVG</b> - вычисление среднего значения;</li>
	* <li> <b>MIN</b> - вычисление минимального значения;</li> <li> <b> MAX</b> -
	* вычисление максимального значения;</li> <li> <b>SUM</b> - вычисление
	* суммы.</li> </ul> <br> Значение по умолчанию - <i>false</i> - означает, что
	* результат группироваться не будет.
	*
	* @param  $array  Массив параметров выборки. Может содержать следующие ключи: <ul>
	* <li>"<b>nTopCount</b>" - количество возвращаемых методом записей будет
	* ограничено сверху значением этого ключа;</li> <li> любой ключ,
	* принимаемый методом <b> CDBResult::NavQuery</b> в качестве третьего
	* параметра.</li> </ul> Значение по умолчанию - <i>false</i> - означает, что
	* параметров выборки нет.
	*
	* @param arFilte $r = array() Массив полей записей, которые будут возвращены методом. Можно
	* указать только те поля, которые необходимы. Если в массиве
	* присутствует значение "*", то будут возвращены все доступные поля.
	* <br><br> Значение по умолчанию - пустой массив array() - означает, что
	* будут возвращены все поля основной таблицы запроса.
	*
	* @param array $arGroupBy = false 
	*
	* @param array $arNavStartParams = false Код платежной системы.
	*
	* @param array $arSelectFields = array() Название платежной системы.
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий набор
	* ассоциативных массивов параметров платежных систем с ключами:</p>
	* <table width="100%" class="tnormal"><tbody> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr>
	* <td>ID</td> <td>Код платежной системы.</td> </tr> <tr> <td>NAME</td> <td>Название
	* платежной системы.</td> </tr> <tr> <td>ACTIVE</td> <td>Флаг (Y/N) активности
	* системы.</td> </tr> <tr> <td>SORT</td> <td>Индекс сортировки.</td> </tr> <tr>
	* <td>DESCRIPTION</td> <td>Описание платежной системы.</td> </tr> <tr> <td>PSA_ID</td> <td>Код
	* обработчика платежной системы (возвращается, если в метод
	* передается тип плательщика) </td> </tr> <tr> <td>PSA_NAME</td> <td>Название
	* обработчика (возвращается, если в метод передается тип
	* плательщика)</td> </tr> <tr> <td>PSA_ACTION_FILE</td> <td>Скрипт обработчика
	* (возвращается, если в метод передается тип плательщика)</td> </tr> <tr>
	* <td>PSA_RESULT_FILE</td> <td>Скрипт запроса результатов (возвращается, если в
	* метод передается тип плательщика)</td> </tr> <tr> <td>PSA_NEW_WINDOW</td> <td>Флаг
	* (Y/N) открывать ли скрипт обработчика в новом окне (возвращается,
	* если в метод передается тип плательщика)</td> </tr> <tr> <td>PSA_PERSON_TYPE_ID</td>
	* <td>Код типа плательщика.</td> </tr> <tr> <td>PSA_PARAMS</td> <td>Параметры вызова
	* обработчика.</td> </tr> <tr> <td>PSA_HAVE_PAYMENT</td> <td>Есть вариант обработчика
	* для работы после оформления заказа.</td> </tr> <tr> <td>PSA_HAVE_ACTION</td> <td>Есть
	* вариант обработчика для мгновенного списания денег.</td> </tr> <tr>
	* <td>PSA_HAVE_RESULT</td> <td>Есть скрипт запроса результатов.</td> </tr> <tr>
	* <td>PSA_HAVE_PREPAY</td> <td>Есть вариант обработчика для работы во время
	* оформления заказа.</td> </tr> </tbody></table> <p>Если в качестве параметра
	* arGroupBy передается пустой массив, то метод вернет число записей,
	* удовлетворяющих фильтру.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>// Выведем все активные платежные системы для текущего сайта, для типа плательщика с кодом 2, работающие с валютой RUR<br>$db_ptype = CSalePaySystem::GetList($arOrder = Array("SORT"=&gt;"ASC", "PSA_NAME"=&gt;"ASC"), Array("LID"=&gt;SITE_ID, "CURRENCY"=&gt;"RUB", "ACTIVE"=&gt;"Y", "PERSON_TYPE_ID"=&gt;2));<br>$bFirst = True;<br>while ($ptype = $db_ptype-&gt;Fetch())<br>{<br>   ?&gt;&lt;input type="radio" name="PAY_SYSTEM_ID" value="&lt;?echo $ptype["ID"] ?&gt;"&lt;?if ($bFirst) echo " checked";?&gt;&gt;&lt;b&gt;&lt;?echo $ptype["PSA_NAME"] ?&gt;&lt;/b&gt;&lt;br&gt;&lt;?<br>   $bFirst = <i>false</i>;<br>   if (strlen($ptype["DESCRIPTION"])&gt;0)<br>      echo $ptype["DESCRIPTION"]."&lt;br&gt;";<br>   ?&gt;&lt;hr size="1" width="90%"&gt;&lt;?<br>}<br>?&gt;<br>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystem/csalepaysystem__getlist.b3a25180.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array("SORT"=>"ASC", "NAME"=>"ASC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (isset($arFilter["PERSON_TYPE_ID"]))
		{
			$arFilter["PSA_PERSON_TYPE_ID"] = $arFilter["PERSON_TYPE_ID"];
			unset($arFilter["PERSON_TYPE_ID"]);
			if (count($arSelectFields) <= 0)
				$arSelectFields = array("*");
		}

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "LID", "CURRENCY", "NAME", "ACTIVE", "SORT", "DESCRIPTION");

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "P.ID", "TYPE" => "int"),
				"LID" => array("FIELD" => "P.LID", "TYPE" => "string"),
				"CURRENCY" => array("FIELD" => "P.CURRENCY", "TYPE" => "string"),
				"NAME" => array("FIELD" => "P.NAME", "TYPE" => "string"),
				"ACTIVE" => array("FIELD" => "P.ACTIVE", "TYPE" => "char"),
				"SORT" => array("FIELD" => "P.SORT", "TYPE" => "int"),
				"DESCRIPTION" => array("FIELD" => "P.DESCRIPTION", "TYPE" => "string"),
				"PSA_ID" => array("FIELD" => "PA.ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_pay_system_action PA ON (P.ID = PA.PAY_SYSTEM_ID)"),
				"PSA_NAME" => array("FIELD" => "PA.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_pay_system_action PA ON (P.ID = PA.PAY_SYSTEM_ID)"),
				"PSA_ACTION_FILE" => array("FIELD" => "PA.ACTION_FILE", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_pay_system_action PA ON (P.ID = PA.PAY_SYSTEM_ID)"),
				"PSA_RESULT_FILE" => array("FIELD" => "PA.RESULT_FILE", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_pay_system_action PA ON (P.ID = PA.PAY_SYSTEM_ID)"),
				"PSA_NEW_WINDOW" => array("FIELD" => "PA.NEW_WINDOW", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_pay_system_action PA ON (P.ID = PA.PAY_SYSTEM_ID)"),
				"PSA_PERSON_TYPE_ID" => array("FIELD" => "PA.PERSON_TYPE_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_pay_system_action PA ON (P.ID = PA.PAY_SYSTEM_ID)"),
				"PSA_PARAMS" => array("FIELD" => "PA.PARAMS", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_pay_system_action PA ON (P.ID = PA.PAY_SYSTEM_ID)"),
				"PSA_TARIF" => array("FIELD" => "PA.TARIF", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_pay_system_action PA ON (P.ID = PA.PAY_SYSTEM_ID)"),
				"PSA_HAVE_PAYMENT" => array("FIELD" => "PA.HAVE_PAYMENT", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_pay_system_action PA ON (P.ID = PA.PAY_SYSTEM_ID)"),
				"PSA_HAVE_ACTION" => array("FIELD" => "PA.HAVE_ACTION", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_pay_system_action PA ON (P.ID = PA.PAY_SYSTEM_ID)"),
				"PSA_HAVE_RESULT" => array("FIELD" => "PA.HAVE_RESULT", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_pay_system_action PA ON (P.ID = PA.PAY_SYSTEM_ID)"),
				"PSA_HAVE_PREPAY" => array("FIELD" => "PA.HAVE_PREPAY", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_pay_system_action PA ON (P.ID = PA.PAY_SYSTEM_ID)"),
				"PSA_HAVE_RESULT_RECEIVE" => array("FIELD" => "PA.HAVE_RESULT_RECEIVE", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_pay_system_action PA ON (P.ID = PA.PAY_SYSTEM_ID)"),
				"PSA_ENCODING" => array("FIELD" => "PA.ENCODING", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_pay_system_action PA ON (P.ID = PA.PAY_SYSTEM_ID)"),
				"PSA_LOGOTIP" => array("FIELD" => "PA.LOGOTIP", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_pay_system_action PA ON (P.ID = PA.PAY_SYSTEM_ID)"),
			);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_pay_system P ".
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
			"FROM b_sale_pay_system P ".
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
				"FROM b_sale_pay_system P ".
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
				// for MYSQL only!!! another code for ORACLE
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
	* <p>Метод добавляет новую платежную систему на основании параметров из массива arFields. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров новой платежной системы, в
	* котором ключами являются названия параметров, а значениями -
	* соответствующие значения.<br> Допустимые ключи: <ul> <li> <b> CURRENCY</b> -
	* валюта платежной системы;</li> <li> <b> NAME</b> - название платежной
	* системы;</li> <li> <b>ACTIVE</b> - флаг (Y/N) активности платежной системы;</li>
	* <li> <b>SORT</b> - индекс сортировки;</li> <li> <b>DESCRIPTION</b> - описание.</li> </ul>
	*
	* @return int <p>Возвращается код измененной записи или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystem/csalepaysystem__add.eba446b8.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		if (!CSalePaySystem::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_sale_pay_system", $arFields);

		$strSql =
			"INSERT INTO b_sale_pay_system(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		return $ID;
	}
}
?>