<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/order_user_props_value.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderuserpropsvalue/index.php
 * @author Bitrix
 */
class CSaleOrderUserPropsValue extends CAllSaleOrderUserPropsValue
{
	
	/**
	* <p>Метод возвращает результат выборки записей из значений свойств профилей покупателя в соответствии со своими параметрами. Метод динамичный.</p>
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
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи значений
	* свойств профилей покупателя. Массив имеет вид: <pre class="syntax">array(
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
	* фильтр разделенном запятой списке значений. Можно просто
	* передавать массив значений.</li> <li> <b>~</b> - значение поля
	* проверяется на соответствие передаваемому в фильтр шаблону;</li>
	* <li> <b>%</b> - значение поля проверяется на соответствие передаваемой
	* в фильтр строке в соответствии с языком запросов.</li> </ul> В
	* качестве "название_поляX" может стоять любое поле заказов.<br><br>
	* Пример фильтра: <pre class="syntax">array("USER_PROPS_ID" =&gt; 120)</pre> Этот фильтр
	* означает "выбрать все записи, в которых значение в поле USER_PROPS_ID
	* (код профиля покупателя) равно 120".<br><br> Значение по умолчанию -
	* пустой массив array() - означает, что результат отфильтрован не
	* будет. <br><br><div class="note"> <b>Важно!</b> Если результат фильтровать не
	* нужно, то пустой массив фильтра надо передать обязательно. Это
	* требуется из-за наследия старого API.</div>
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи значений свойств
	* профилей покупателя. Массив имеет вид: <pre
	* class="syntax">array("название_поля1", "группирующая_функция2" =&gt;
	* "название_поля2", ...)</pre> В качестве "название_поля<i>N</i>" может стоять
	* любое поле значений свойств профилей покупателя. В качестве
	* группирующей функции могут стоять: <ul> <li> <b> COUNT</b> - подсчет
	* количества;</li> <li> <b>AVG</b> - вычисление среднего значения;</li> <li>
	* <b>MIN</b> - вычисление минимального значения;</li> <li> <b> MAX</b> -
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
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий ассоциативные
	* массивы параметров свойств с ключами:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код свойства профиля
	* покупателя.</td> </tr> <tr> <td>USER_PROPS_ID</td> <td>Код профиля покупателя.</td> </tr>
	* <tr> <td>ORDER_PROPS_ID</td> <td>Код свойства заказа.</td> </tr> <tr> <td>USER_VALUE_NAME</td>
	* <td>Название свойства заказа, сохраненное в профиле покупателя.</td>
	* </tr> <tr> <td>VALUE</td> <td>Значение свойства заказа, сохраненное в профиле
	* покупателя.</td> </tr> <tr> <td>TYPE</td> <td>Тип свойства заказа.</td> </tr> <tr>
	* <td>SORT</td> <td>Индекс сортировки свойства заказа.</td> </tr> <tr> <td>VARIANT_NAME</td>
	* <td>Название варианта значения свойства заказа со значением из
	* профиля покупателя.</td> </tr> <tr> <td>CODE</td> <td>Символьный код свойства
	* заказа.</td> </tr> </table> <p>Если в качестве параметра arGroupBy передается
	* пустой массив, то метод вернет число записей, удовлетворяющих
	* фильтру.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* // Выведем все свойства профиля покупателя с кодом $ID
	* $db_propVals = CSaleOrderUserPropsValue::GetList(array("ID" =&gt; "ASC"), Array("USER_PROPS_ID"=&gt;$ID));
	* while ($arPropVals = $db_propVals-&gt;Fetch())
	* {
	* echo $arPropVals["USER_VALUE_NAME"]."=".$arPropVals["VALUE"]."&lt;br&gt;";
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderuserpropsvalue/csaleorderuserpropsvalue__getlist.19b444a8.php
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

			if (count($arSelectFields) <= 0)
				$arSelectFields = array("ID", "USER_PROPS_ID", "ORDER_PROPS_ID", "USER_VALUE_NAME", "VALUE", "TYPE", "SORT", "VARIANT_NAME", "CODE");
		}

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "USER_PROPS_ID", "ORDER_PROPS_ID", "NAME", "VALUE", "PROP_ID", "PROP_PERSON_TYPE_ID", "PROP_NAME", "PROP_TYPE", "PROP_REQUIED", "PROP_DEFAULT_VALUE", "PROP_SORT", "PROP_USER_PROPS", "PROP_IS_LOCATION", "PROP_PROPS_GROUP_ID", "PROP_SIZE1", "PROP_SIZE2", "PROP_DESCRIPTION", "PROP_IS_EMAIL", "PROP_IS_PROFILE_NAME", "PROP_IS_PAYER", "PROP_IS_LOCATION4TAX", "PROP_IS_ZIP", "PROP_CODE", "VARIANT_ID", "VARIANT_ORDER_PROPS_ID", "VARIANT_NAME", "VARIANT_VALUE", "VARIANT_SORT", "VARIANT_DESCRIPTION");

		// TODO proper compatibility CAllSaleOrderUserPropsValue::getList15
		$sale15converted = \Bitrix\Main\Config\Option::get('main', '~sale_converted_15', 'N') == 'Y';
		if ($sale15converted && is_array($arSelectFields) && $arSelectFields)
		{
			if (($i = array_search('PROP_SIZE1', $arSelectFields)) !== false)
				unset($arSelectFields[$i]);
			if (($i = array_search('PROP_SIZE2', $arSelectFields)) !== false)
				unset($arSelectFields[$i]);
		}

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "UP.ID", "TYPE" => "int"),
				"USER_PROPS_ID" => array("FIELD" => "UP.USER_PROPS_ID", "TYPE" => "int"),
				"ORDER_PROPS_ID" => array("FIELD" => "UP.ORDER_PROPS_ID", "TYPE" => "int"),
				"NAME" => array("FIELD" => "UP.NAME", "TYPE" => "string"),

				"PROP_ID" => array("FIELD" => "P.ID", "TYPE" => "int", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_PERSON_TYPE_ID" => array("FIELD" => "P.PERSON_TYPE_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_NAME" => array("FIELD" => "P.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_TYPE" => array("FIELD" => "P.TYPE", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_REQUIED" => array("FIELD" => "P.REQUI".($sale15converted ? 'R' : '')."ED", "TYPE" => "char", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_DEFAULT_VALUE" => array("FIELD" => "P.DEFAULT_VALUE", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_SORT" => array("FIELD" => "P.SORT", "TYPE" => "int", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_USER_PROPS" => array("FIELD" => "P.USER_PROPS", "TYPE" => "char", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_IS_LOCATION" => array("FIELD" => "P.IS_LOCATION", "TYPE" => "char", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_PROPS_GROUP_ID" => array("FIELD" => "P.PROPS_GROUP_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_SIZE1" => array("FIELD" => "P.SIZE1", "TYPE" => "int", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_SIZE2" => array("FIELD" => "P.SIZE2", "TYPE" => "int", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_DESCRIPTION" => array("FIELD" => "P.DESCRIPTION", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_IS_EMAIL" => array("FIELD" => "P.IS_EMAIL", "TYPE" => "char", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_IS_PROFILE_NAME" => array("FIELD" => "P.IS_PROFILE_NAME", "TYPE" => "char", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_IS_PAYER" => array("FIELD" => "P.IS_PAYER", "TYPE" => "char", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_IS_LOCATION4TAX" => array("FIELD" => "P.IS_LOCATION4TAX", "TYPE" => "char", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_IS_ZIP" => array("FIELD" => "P.IS_ZIP", "TYPE" => "char", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_CODE" => array("FIELD" => "P.CODE", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_ACTIVE" => array("FIELD" => "P.ACTIVE", "TYPE" => "char", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"PROP_UTIL" => array("FIELD" => "P.UTIL", "TYPE" => "char", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),

				"VARIANT_ID" => array("FIELD" => "PV.ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_order_props_variant PV ON (UP.ORDER_PROPS_ID = PV.ORDER_PROPS_ID AND UP.VALUE = PV.VALUE)"),
				"VARIANT_ORDER_PROPS_ID" => array("FIELD" => "PV.ORDER_PROPS_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_order_props_variant PV ON (UP.ORDER_PROPS_ID = PV.ORDER_PROPS_ID AND UP.VALUE = PV.VALUE)"),
				"VARIANT_NAME" => array("FIELD" => "PV.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order_props_variant PV ON (UP.ORDER_PROPS_ID = PV.ORDER_PROPS_ID AND UP.VALUE = PV.VALUE)"),
				"VARIANT_VALUE" => array("FIELD" => "PV.VALUE", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order_props_variant PV ON (UP.ORDER_PROPS_ID = PV.ORDER_PROPS_ID AND UP.VALUE = PV.VALUE)"),
				"VARIANT_SORT" => array("FIELD" => "PV.SORT", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_order_props_variant PV ON (UP.ORDER_PROPS_ID = PV.ORDER_PROPS_ID AND UP.VALUE = PV.VALUE)"),
				"VARIANT_DESCRIPTION" => array("FIELD" => "PV.DESCRIPTION", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order_props_variant PV ON (UP.ORDER_PROPS_ID = PV.ORDER_PROPS_ID AND UP.VALUE = PV.VALUE)"),

				"USER_VALUE_NAME" => array("FIELD" => "PV.NAME", "TYPE" => "string"),
				"TYPE" => array("FIELD" => "P.TYPE", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"SORT" => array("FIELD" => "P.SORT", "TYPE" => "int", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)"),
				"CODE" => array("FIELD" => "P.CODE", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_order_props P ON (UP.ORDER_PROPS_ID = P.ID)")
			);
		// <-- FIELDS

		self::addPropertyValueField('UP', $arFields, $arSelectFields);

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_user_props_value UP ".
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
			"FROM b_sale_user_props_value UP ".
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
				"FROM b_sale_user_props_value UP ".
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
				// ТОЛЬКО ДЛЯ MYSQL!!! ДЛЯ ORACLE ДРУГОЙ КОД
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
	* <p>Метод добавляет новое свойство профиля покупателя в соответствии с массивом параметров arFields. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров нового свойства профиля
	* покупателя. Ключами в массиве являются названия параметров
	* свойства, а значениями - соответствующие значения.<br><br>
	* Допустимые ключи:<ul> <li> <b>USER_PROPS_ID</b> - код профиля покупателя;</li> <li>
	* <b>ORDER_PROPS_ID</b> - код свойства заказа;</li> <li> <b>NAME</b> - название свойства
	* заказа;</li> <li> <b>VALUE</b> - значение свойства заказа в профиле.</li> </ul>
	*
	* @return int <p>Возвращается код добавленного свойства профиля покупателя или
	* <i>false</i> в случае ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arFields = array(
	*    "USER_PROPS_ID" =&gt; 1258,
	*    "ORDER_PROPS_ID" =&gt; 12,
	*    "NAME" =&gt; "Почтовый индекс",
	*    "VALUE" =&gt; "236015"
	* );
	* CSaleOrderUserPropsValue::Add($arFields);
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderuserpropsvalue/csaleorderuserpropsvalue__add.266618d0.php
	* @author Bitrix
	*/
	public static 	function Add($arFields)
	{
		global $DB;

		// translate here
		$arFields['VALUE'] = self::translateLocationIDToCode($arFields['VALUE'], $arFields['ORDER_PROPS_ID']);

		$arInsert = $DB->PrepareInsert("b_sale_user_props_value", $arFields);

		$strSql = 
			"INSERT INTO b_sale_user_props_value(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		return $ID;
	}
}
?>