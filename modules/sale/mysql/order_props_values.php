<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/order_props_values.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvalue/index.php
 * @author Bitrix
 * @deprecated
 */
class CSaleOrderPropsValue extends CAllSaleOrderPropsValue
{
	
	/**
	* <p>Метод возвращает результат выборки записей из заказов в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле местоположения, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и
	* т.д.). <br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи значений
	* свойств. Массив имеет вид: <pre class="syntax">array(
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
	* фильтр массиве со списком значений;</li> <li> <b>~</b> - значение поля
	* проверяется на соответствие передаваемому в фильтр шаблону;</li>
	* <li> <b>%</b> - значение поля проверяется на соответствие передаваемой
	* в фильтр строке в соответствии с языком запросов.</li> </ul> В
	* качестве "название_поляX" может стоять любое поле заказов.<br><br>
	* Пример фильтра: <pre class="syntax">array("~CODE" =&gt; "SH*")</pre> Этот фильтр означает
	* "выбрать все записи, в которых значение в поле CODE (символьный код
	* свойства) начинается с SH".<br><br> Значение по умолчанию - пустой
	* массив array() - означает, что результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи значений свойств.
	* Массив имеет вид: <pre class="syntax"> array("название_поля1",
	* "группирующая_функция2" =&gt; "название_поля2", . . .)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле значений свойств. В
	* качестве группирующей функции могут стоять: <ul> <li> <b> COUNT</b> -
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
	* ассоциативных массивов параметров значений свойств с ключами:</p>
	* <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td>
	* <td>Код значения свойства заказа.</td> </tr> <tr> <td>ORDER_ID</td> <td>Код
	* заказа.</td> </tr> <tr> <td>ORDER_PROPS_ID</td> <td>Код свойства.</td> </tr> <tr> <td>NAME</td>
	* <td>Название свойства.</td> </tr> <tr> <td>VALUE</td> <td>Значение свойства.</td> </tr>
	* <tr> <td>CODE</td> <td>Символьный код свойства.</td> </tr> </table> <p>Если в качестве
	* параметра arGroupBy передается пустой массив, то метод вернет число
	* записей, удовлетворяющих фильтру.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Узнаем имя заказчика (т.е. значение, которое было введено в поле свойства 
	* // заказа $ORDER_ID с установленным флагом IS_PAYER)
	* $PAYER_NAME = "";
	* $db_order = CSaleOrder::GetList(
	*         array("DATE_UPDATE" =&gt; "DESC"),
	*         array("ID" =&gt; $ORDER_ID)
	*     );
	* if ($arOrder = $db_order-&gt;Fetch())
	* {
	*    $db_props = CSaleOrderProps::GetList(
	*         array("SORT" =&gt; "ASC"),
	*         array(
	*                 "PERSON_TYPE_ID" =&gt; $arOrder["PERSON_TYPE_ID"],
	*                 "IS_PAYER" =&gt; "Y"
	*             )
	*     );
	*    if ($arProps = $db_props-&gt;Fetch())
	*    {
	*       $db_vals = CSaleOrderPropsValue::GetList(
	*             array("SORT" =&gt; "ASC"),
	*             array(
	*                     "ORDER_ID" =&gt; $ORDER_ID,
	*                     "ORDER_PROPS_ID" =&gt; $arProps["ID"]
	*                 )
	*         );
	*       if ($arVals = $db_vals-&gt;Fetch())
	*         $PAYER_NAME = $arVals["VALUE"];
	*    }
	* }
	* echo $PAYER_NAME;
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvalue/csaleorderpropsvalue__getlist.52da0d54.php
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

			$arSelectFields = array("ID", "ORDER_ID", "ORDER_PROPS_ID", "NAME", "VALUE", "CODE");
		}

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "ORDER_ID", "ORDER_PROPS_ID", "NAME", "VALUE", "CODE");

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "V.ID", "TYPE" => "int"),
				"ORDER_ID" => array("FIELD" => "V.ORDER_ID", "TYPE" => "int"),
				"ORDER_PROPS_ID" => array("FIELD" => "V.ORDER_PROPS_ID", "TYPE" => "int"),
				"NAME" => array("FIELD" => "V.NAME", "TYPE" => "string"),
				"CODE" => array("FIELD" => "V.CODE", "TYPE" => "string"),
				"PROP_ID" => array("FIELD" => "P.ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_PERSON_TYPE_ID" => array("FIELD" => "P.PERSON_TYPE_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_NAME" => array("FIELD" => "P.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_TYPE" => array("FIELD" => "P.TYPE", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_REQUIED" => array("FIELD" => "P.REQUIED", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_DEFAULT_VALUE" => array("FIELD" => "P.DEFAULT_VALUE", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_SORT" => array("FIELD" => "P.SORT", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_USER_PROPS" => array("FIELD" => "P.USER_PROPS", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_IS_LOCATION" => array("FIELD" => "P.IS_LOCATION", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_PROPS_GROUP_ID" => array("FIELD" => "P.PROPS_GROUP_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_SIZE1" => array("FIELD" => "P.SIZE1", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_SIZE2" => array("FIELD" => "P.SIZE2", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_DESCRIPTION" => array("FIELD" => "P.DESCRIPTION", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_IS_EMAIL" => array("FIELD" => "P.IS_EMAIL", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_IS_PROFILE_NAME" => array("FIELD" => "P.IS_PROFILE_NAME", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_IS_PAYER" => array("FIELD" => "P.IS_PAYER", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_IS_LOCATION4TAX" => array("FIELD" => "P.IS_LOCATION4TAX", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_IS_ZIP" => array("FIELD" => "P.IS_ZIP", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_CODE" => array("FIELD" => "P.CODE", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_ACTIVE" => array("FIELD" => "P.ACTIVE", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
				"PROP_UTIL" => array("FIELD" => "P.UTIL", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID)"),
			);
		// <-- FIELDS

		CSaleOrderPropsValue::addPropertyValueField('V', $arFields, $arSelectFields);

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_order_props_value V ".
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
			"FROM b_sale_order_props_value V ".
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
				"FROM b_sale_order_props_value V ".
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
	* <p>Метод добавляет новое значение свойства к заказу на основании параметров arFields. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров значения свойства, ключами в
	* котором являются названия параметров значения свойства, а
	* значениями - соответствующие значения.<br><br> Допустимые ключи:<ul>
	* <li> <b>ORDER_ID</b> - код заказа (обязательное);</li> <li> <b>ORDER_PROPS_ID</b> - код
	* свойства (обязательное);</li> <li> <b>NAME</b> - название свойства
	* (обязательное);</li> <li> <b>VALUE</b> - значение свойства;</li> <li> <b>CODE</b> -
	* символьный код свойства.</li> </ul>
	*
	* @return int <p>Метод возвращает код добавленного значения свойства или <i>false</i>
	* в случае ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arFields = array(
	*    "ORDER_ID" =&gt; 124859,
	*    "ORDER_PROPS_ID" =&gt; 15,
	*    "NAME" =&gt; "Адрес доставки",
	*    "CODE" =&gt; "ADDRESS",
	*    "VALUE" =&gt; "ул. Строителей, дом 88, кв. 15"
	* );
	* 
	* CSaleOrderPropsValue::Add($arFields);
	* ?&gt;
	* 
	* 
	* //метод класса, который добавляет свойство (код/значение) к заказу, динамически узнавая идентификатор свойства: 
	*  public static function AddOrderProperty($code, $value, $order) {
	*       if (!strlen($code)) {
	*          return false;
	*       }
	*       if (CModule::IncludeModule('sale')) {
	*          if ($arProp = CSaleOrderProps::GetList(array(), array('CODE' =&gt; $code))-&gt;Fetch()) {
	*             return CSaleOrderPropsValue::Add(array(
	*                'NAME' =&gt; $arProp['NAME'],
	*                'CODE' =&gt; $arProp['CODE'],
	*                'ORDER_PROPS_ID' =&gt; $arProp['ID'],
	*                'ORDER_ID' =&gt; $order,
	*                'VALUE' =&gt; $value,
	*             ));
	*          }
	*       }
	*    }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvalue/csaleorderpropsvalue__add.af505780.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		if (!CSaleOrderPropsValue::CheckFields("ADD", $arFields, 0))
			return false;

		// translate here
		$arFields['VALUE'] = self::translateLocationIDToCode($arFields['VALUE'], $arFields['ORDER_PROPS_ID']);

		$arInsert = $DB->PrepareInsert("b_sale_order_props_value", $arFields);

		$strSql =
			"INSERT INTO b_sale_order_props_value(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		return $ID;
	}


	
	/**
	* <p>Метод возвращает набор значений свойств для заказа с кодом ORDER_ID. Кроме параметров значений свойств возвращаются также некоторые связанные значения. Метод динамичный.</p>
	*
	*
	* @param int $ORDER_ID  Код заказа.
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий ассоциативные
	* массивы параметров значений свойств заказа (и сопутствующие
	* параметры других объектов) с ключами:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код значения свойства
	* заказа.</td> </tr> <tr> <td>ORDER_ID</td> <td>Код заказа.</td> </tr> <tr> <td>ORDER_PROPS_ID</td>
	* <td>Код свойства заказа.</td> </tr> <tr> <td>NAME</td> <td>Название свойства
	* заказа (привязанное к значению) </td> </tr> <tr> <td>VALUE</td> <td>Значение
	* свойства заказа.</td> </tr> <tr> <td>CODE</td> <td>Символьный код свойства
	* заказа.</td> </tr> <tr> <td>PROPERTY_NAME</td> <td>Название свойства заказа.</td> </tr> <tr>
	* <td>TYPE</td> <td>Тип свойства заказа.</td> </tr> <tr> <td>PROPS_GROUP_ID</td> <td>Код группы
	* свойств заказа.</td> </tr> <tr> <td>GROUP_NAME</td> <td>Название группы свойств
	* заказа.</td> </tr> <tr> <td>IS_LOCATION</td> <td>Флаг (Y/N) использовать ли это
	* значение в качестве кода местоположения для получения стоимости
	* доставки.</td> </tr> <tr> <td>IS_EMAIL</td> <td>Флаг (Y/N) использовать ли это
	* значение в качестве email адреса покупателя.</td> </tr> <tr> <td>IS_PROFILE_NAME</td>
	* <td>Флаг (Y/N) использовать ли это значение в качестве названия
	* профиля покупателя.</td> </tr> <tr> <td>IS_PAYER</td> <td>Флаг (Y/N) использовать ли
	* это значение в качестве имени покупателя.</td> </tr> </table>
	* <p>Результирующий набор отсортирован последовательно по индексу
	* сортировки группы свойств заказа, названию группы свойств
	* заказа, индексу сортировки свойства заказа, названию свойства
	* заказа.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выведем все свойства заказа с кодом $ID, сгруппированые по группам свойств
	* $db_props = CSaleOrderPropsValue::GetOrderProps($ID);
	* $iGroup = -1;
	* while ($arProps = $db_props-&gt;Fetch())
	* {
	*    if ($iGroup!=IntVal($arProps["PROPS_GROUP_ID"]))
	*    {
	*       echo "&lt;b&gt;".$arProps["GROUP_NAME"]."&lt;/b&gt;&lt;br&gt;";
	*       $iGroup = IntVal($arProps["PROPS_GROUP_ID"]);
	*    }
	* 
	*    echo $arProps["NAME"].": ";
	* 
	*    if ($arProps["TYPE"]=="CHECKBOX")
	*    {
	*       if ($arProps["VALUE"]=="Y")
	*          echo "Да";
	*       else
	*          echo "Нет";
	*    }
	*    elseif ($arProps["TYPE"]=="TEXT" || $arProps["TYPE"]=="TEXTAREA")
	*    {
	*       echo htmlspecialchars($arProps["VALUE"]);
	*    }
	*    elseif ($arProps["TYPE"]=="SELECT" || $arProps["TYPE"]=="RADIO")
	*    {
	*       $arVal = CSaleOrderPropsVariant::GetByValue($arProps["ORDER_PROPS_ID"], $arProps["VALUE"]);
	*       echo htmlspecialchars($arVal["NAME"]);
	*    }
	*    elseif ($arProps["TYPE"]=="MULTISELECT")
	*    {
	*       $curVal = split(",", $arProps["VALUE"]);
	*       for ($i = 0; $i&lt;count($curVal); $i++)
	*       {
	*          $arVal = CSaleOrderPropsVariant::GetByValue($arProps["ORDER_PROPS_ID"], $curVal[$i]);
	*          if ($i&gt;0) echo ", ";
	*          echo htmlspecialchars($arVal["NAME"]);
	*       }
	*    }
	*    elseif ($arProps["TYPE"]=="LOCATION")
	*    {
	*       $arVal = CSaleLocation::GetByID($arProps["VALUE"], LANGUAGE_ID);
	*       echo htmlspecialchars($arVal["COUNTRY_NAME"]." - ".$arVal["CITY_NAME"]);
	*    }
	* 
	*    echo "&lt;br&gt;";
	* }
	* ?&gt;
	* 
	* 
	* //выборка по нескольким свойствам (например, по LOCATION и ADDRESS):
	* $dbOrderProps = CSaleOrderPropsValue::GetList(
	*         array("SORT" =&gt; "ASC"),
	*         array("ORDER_ID" =&gt; $intOrderID, "CODE"=&gt;array("LOCATION", "ADDRESS"))
	*     );
	*     while ($arOrderProps = $dbOrderProps-&gt;GetNext()):
	*             echo "&lt;pre&gt;"; print_r($arOrderProps); echo "&lt;/pre&gt;";
	*     endwhile;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvalue/csaleorderpropsvalue__getorderprops.af7c248d.php
	* @author Bitrix
	*/
	public static function GetOrderProps($ORDER_ID)
	{
		global $DB;
		$ORDER_ID = IntVal($ORDER_ID);

		$strSql =
			"SELECT PV.ID, PV.ORDER_ID, PV.ORDER_PROPS_ID, PV.NAME, ".self::getPropertyValueFieldSelectSql().", PV.CODE, ".
			"	P.NAME as PROPERTY_NAME, P.TYPE, P.PROPS_GROUP_ID, P.INPUT_FIELD_LOCATION, PG.NAME as GROUP_NAME, ".
			"	P.IS_LOCATION, P.IS_EMAIL, P.IS_PROFILE_NAME, P.IS_PAYER, PG.SORT as GROUP_SORT, P.ACTIVE, P.UTIL ".
			"FROM b_sale_order_props_value PV ".
			"	LEFT JOIN b_sale_order_props P ON (PV.ORDER_PROPS_ID = P.ID) ".
			"	LEFT JOIN b_sale_order_props_group PG ON (P.PROPS_GROUP_ID = PG.ID) ".
			self::getLocationTableJoinSql().
			"WHERE PV.ORDER_ID = ".$ORDER_ID." ".
			"ORDER BY PG.SORT, PG.NAME, P.SORT, P.NAME, P.ID ";

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $db_res;
	}

	public static function GetOrderRelatedProps($ORDER_ID, $arFilter = array())
	{
		global $DB;
		$ORDER_ID = IntVal($ORDER_ID);

		$strJoin = "";
		$strWhere = "";

		if (isset($arFilter["PAYSYSTEM_ID"]) && intval($arFilter["PAYSYSTEM_ID"]) > 0)
		{
			$strJoin = "	LEFT JOIN b_sale_order_props_relation SOP ON P.ID = SOP.PROPERTY_ID ";
			$strWhere = " (SOP.ENTITY_TYPE = 'P' AND SOP.ENTITY_ID = ".$DB->ForSql($arFilter["PAYSYSTEM_ID"]).")";
		}

		if (isset($arFilter["DELIVERY_ID"]) && strlen($arFilter["DELIVERY_ID"]) > 0)
		{
			$strJoin .= "	LEFT JOIN b_sale_order_props_relation SOD ON P.ID = SOD.PROPERTY_ID ";
			if (strlen($strWhere) > 0)
				$strWhere .= " OR";

			$strWhere .= " (SOD.ENTITY_TYPE = 'D' AND SOD.ENTITY_ID = '".$DB->ForSql($arFilter["DELIVERY_ID"])."')";
		}

		if (strlen($strWhere) > 0)
			$strWhere = " AND (".$strWhere.") ";

		// locations kept in CODEs, but must be shown as IDs
		$lMig = CSaleLocation::isLocationProMigrated();

		$strSql =
			"SELECT DISTINCT PV.ID, PV.ORDER_ID, PV.ORDER_PROPS_ID, PV.NAME, ".self::getPropertyValueFieldSelectSql().", PV.CODE, ".
			"	P.NAME as PROPERTY_NAME, P.TYPE, P.PROPS_GROUP_ID, P.INPUT_FIELD_LOCATION, PG.NAME as GROUP_NAME, ".
			"	P.IS_LOCATION, P.IS_EMAIL, P.IS_PROFILE_NAME, P.IS_PAYER, PG.SORT as GROUP_SORT, P.ACTIVE, P.UTIL ".
			"FROM b_sale_order_props_value PV ".
			"	LEFT JOIN b_sale_order_props P ON (PV.ORDER_PROPS_ID = P.ID) ".
			"	LEFT JOIN b_sale_order_props_group PG ON (P.PROPS_GROUP_ID = PG.ID) ".
			self::getLocationTableJoinSql().
			$strJoin.
			"WHERE PV.ORDER_ID = ".$ORDER_ID." ".
			$strWhere.
			"ORDER BY PG.SORT, PG.NAME, P.SORT, P.NAME, P.ID ";

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $db_res;
	}
}

?>