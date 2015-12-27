<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/order_props_variant.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvariant/index.php
 * @author Bitrix
 */
class CSaleOrderPropsVariant extends CAllSaleOrderPropsVariant
{
	
	/**
	* <p>Метод возвращает набор вариантов значений свойств заказа, удовлетворяющих фильтру arFilter. Результирующий набор отсортирован по полю by в направлении order. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле вариантов значений свойств, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и
	* т.д.). <br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи вариантов
	* значений свойств. Массив имеет вид: <pre class="syntax">array(
	* "[модификатор1][оператор1]название_поля1" =&gt; "значение1",
	* "[модификатор2][оператор2]название_поля2" =&gt; "значение2", . . . )</pre>
	* Удовлетворяющие фильтру записи возвращаются в результате, а
	* записи, которые не удовлетворяют условиям фильтра,
	* отбрасываются.<br><br> Допустимыми являются следующие модификаторы:
	* <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> - значения null, 0 и пустая строка
	* так же удовлетворяют условиям фильтра.</li> </ul> Допустимыми
	* являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	* или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	* поля строго больше передаваемой в фильтр величины;</li> <li> <b>&gt;=</b> -
	* значение поля меньше или равно передаваемой в фильтр величины;</li>
	* <li> <b>&gt;=</b> - значение поля строго меньше передаваемой в фильтр
	* величины;</li> <li> <b>@</b> - значение поля находится в передаваемом в
	* фильтр разделенном запятой списке значений;</li> <li> <b>~</b> - значение
	* поля проверяется на соответствие передаваемому в фильтр
	* шаблону;</li> <li> <b>%</b> - значение поля проверяется на соответствие
	* передаваемой в фильтр строке в соответствии с языком запросов.</li>
	* </ul> В качестве "название_поляX" может стоять любое поле
	* заказов.<br><br> Пример фильтра: <pre class="syntax">array("~VALUE" =&gt; "SH*")</pre> Этот
	* фильтр означает "выбрать все записи, в которых значение в поле VALUE
	* (значение) начинается с SH".<br><br> Значение по умолчанию - пустой
	* массив array() - означает, что результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи вариантов значений
	* свойств. Массив имеет вид: <pre class="syntax"> array("название_поля1",
	* "группирующая_функция2" =&gt; "название_поля2", ...)</pre> В качестве
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
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий ассоциативные
	* массивы параметров с ключами:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Код</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код варианта значения
	* свойства заказа.</td> </tr> <tr> <td>ORDER_PROPS_ID</td> <td>Код свойства заказа.</td>
	* </tr> <tr> <td>NAME</td> <td>Название варианта.</td> </tr> <tr> <td>VALUE</td> <td>Значение
	* варианта.</td> </tr> <tr> <td>SORT</td> <td>Индекс сортировки.</td> </tr> <tr>
	* <td>DESCRIPTION</td> <td>Описание варианта значения свойства заказа.</td> </tr>
	* </table> <p>Если в качестве параметра arGroupBy передается пустой массив,
	* то метод вернет число записей, удовлетворяющих фильтру.</p> <a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выведем форму для ввода свойств заказа для группы свойств с кодом 5, которые 
	* // входят в профиль покупателя, для типа плательщика с кодом 2
	* $db_props = CSaleOrderProps::GetList(
	*         array("SORT" =&gt; "ASC"),
	*         array(
	*                 "PERSON_TYPE_ID" =&gt; 2,
	*                 "PROPS_GROUP_ID" =&gt; 5,
	*                 "USER_PROPS" =&gt; "Y"
	*             )
	*     );
	* if ($props = $db_props-&gt;Fetch())
	* {
	*    echo "Заполните параметры заказа:&lt;br&gt;";
	*    do
	*    {
	*       echo $props["NAME"];
	*       if ($props["REQUIED"]=="Y" || $props["IS_EMAIL"]=="Y" || 
	*           $props["IS_PROFILE_NAME"]=="Y" || $props["IS_LOCATION"]=="Y" || 
	*           $props["IS_LOCATION4TAX"]=="Y" || $props["IS_PAYER"]=="Y")
	*       {
	*          echo "*";
	*       }
	*       echo ": ";
	* 
	*       if ($props["TYPE"]=="CHECKBOX")
	*       {
	*          echo '&lt;input type="checkbox" class="inputcheckbox" name="ORDER_PROP_'.$props["ID"].'" value="Y"'.(($props["DEFAULT_VALUE"]=="Y")?" checked":"").'&gt;';
	*       }
	*       elseif ($props["TYPE"]=="TEXT")
	*       {
	*          echo '&lt;input type="text" class="inputtext" size="'.((IntVal($props["SIZE1"])&gt;0)?$props["SIZE1"]:30).'" maxlength="250" value="'.htmlspecialchars($props["DEFAULT_VALUE"]).'" name="ORDER_PROP_'.$props["ID"].'"&gt;";
	*       }
	*       elseif ($props["TYPE"]=="SELECT")
	*       {
	*          echo '&lt;select name="ORDER_PROP_'.$props["ID"].'" size="'.((IntVal($props["SIZE1"])&gt;0)?$props["SIZE1"]:1).'"&gt;';
	*          $db_vars = CSaleOrderPropsVariant::GetList(
	*                 array("SORT" =&gt; "ASC"),
	*                 array("ORDER_PROPS_ID" =&gt; $props["ID"])
	*             );
	*          while ($vars = $db_vars-&gt;Fetch())
	*          {
	*             echo '&lt;option value="'.$vars["VALUE"].'"'.(($vars["VALUE"]==$props["DEFAULT_VALUE"])?" selected":"").'&gt;'.htmlspecialchars($vars["NAME"]).'&lt;/option&gt;';
	*          }
	*          echo '&lt;/select&gt;';
	*       }
	*       elseif ($props["TYPE"]=="MULTISELECT")
	*       {
	*          echo '&lt;select multiple name="ORDER_PROP_'.$props["ID"].'[]" size="'.((IntVal($props["SIZE1"])&gt;0)?$props["SIZE1"]:5).'"&gt;';
	*          $arDefVal = Split(",", $props["DEFAULT_VALUE"]);
	*          for ($i = 0; $i&lt;count($arDefVal); $i++)
	*             $arDefVal[$i] = Trim($arDefVal[$i]);
	* 
	*          $db_vars = CSaleOrderPropsVariant::GetList(
	*                 array("SORT" =&gt; "ASC"),
	*                 array("ORDER_PROPS_ID" =&gt; $props["ID"])
	*             );
	*          while ($vars = $db_vars-&gt;Fetch())
	*          {
	*             echo '&lt;option value="'.$vars["VALUE"].'"'.(in_array($vars["VALUE"], $arDefVal)?" selected":"").'&gt;'.htmlspecialchars($vars["NAME"]).'&lt;/option&gt;';
	*          }
	*          echo '&lt;/select&gt;';
	*       }
	*       elseif ($props["TYPE"]=="TEXTAREA")
	*       {
	*          echo '&lt;textarea rows="'.((IntVal($props["SIZE2"])&gt;0)?$props["SIZE2"]:4).'" cols="'.((IntVal($props["SIZE1"])&gt;0)?$props["SIZE1"]:40).'" name="ORDER_PROP_'.$props["ID"].'"&gt;'.htmlspecialchars($props["DEFAULT_VALUE"]).'&lt;/textarea&gt;';
	*       }
	*       elseif ($props["TYPE"]=="LOCATION")
	*       {
	*          echo '&lt;select name="ORDER_PROP_'.$props["ID"].'" size="'.((IntVal($props["SIZE1"])&gt;0)?$props["SIZE1"]:1).'"&gt;';
	*          $db_vars = CSaleLocation::GetList(Array("SORT"=&gt;"ASC", "COUNTRY_NAME_LANG"=&gt;"ASC", "CITY_NAME_LANG"=&gt;"ASC"), array("LID" =&gt; LANGUAGE_ID));
	*          while ($vars = $db_vars-&gt;Fetch())
	*          {
	*             echo '&lt;option value="'.$vars["ID"].'"".((IntVal($vars["ID"])==IntVal($props["DEFAULT_VALUE"]))?" selected":"").'&gt;'.htmlspecialchars($vars["COUNTRY_NAME"]." - ".$vars["CITY_NAME"]).'&lt;/option&gt;';
	*          }
	*          echo '&lt;/select&gt;';
	*       }
	*       elseif ($props["TYPE"]=="RADIO")
	*       {
	*          $db_vars = CSaleOrderPropsVariant::GetList(
	*                 array("SORT" =&gt; "ASC"),
	*                 array("ORDER_PROPS_ID" =&gt; $props["ID"])
	*             );
	*          while ($vars = $db_vars-&gt;Fetch())
	*          {
	*             echo '&lt;input type="radio" name="ORDER_PROP_'.$props["ID"].'" value="'.$vars["VALUE"].'"'.(($vars["VALUE"]==$props["DEFAULT_VALUE"])?" checked":"").'&gt;'.htmlspecialchars($vars["NAME"]).'&lt;br&gt;';
	*          }
	*       }
	* 
	*       if (strlen($props["DESCRIPTION"])&gt;0)
	*       {
	*          echo "&lt;br&gt;&lt;small&gt;".$props["DESCRIPTION"]."&lt;/small&gt;";
	*       }
	* 
	*       echo "&lt;br&gt;";
	*    }
	*    while ($props = $db_props-&gt;Fetch());
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvariant/csaleorderpropsvariant__getlist.d436238c.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		// To call the old form
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

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "PV.ID", "TYPE" => "int"),
				"ORDER_PROPS_ID" => array("FIELD" => "PV.ORDER_PROPS_ID", "TYPE" => "int"),
				"NAME" => array("FIELD" => "PV.NAME", "TYPE" => "string"),
				"VALUE" => array("FIELD" => "PV.VALUE", "TYPE" => "string"),
				"SORT" => array("FIELD" => "PV.SORT", "TYPE" => "int"),
				"DESCRIPTION" => array("FIELD" => "PV.DESCRIPTION", "TYPE" => "string")
			);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_order_props_variant PV ".
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
			"FROM b_sale_order_props_variant PV ".
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
				"FROM b_sale_order_props_variant PV ".
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
	* <p>Метод добавляет новый вариант для выбора значения в свойствах типа переключатель (RADIO) и списки (SELECT, MULTISELECT). Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров нового варианта значения
	* свойства заказа, ключами в котором являются названия
	* параметров.<br> Допустимые ключи:<ul> <li> <b>ORDER_PROPS_ID</b> - код свойства
	* заказа;</li> <li> <b>NAME</b> - название варианта;</li> <li> <b>VALUE</b> - значение
	* варианта;</li> <li> <b>SORT</b> - индекс сортировки;</li> <li> <b>DESCRIPTION</b> -
	* описание варианта.</li> </ul>
	*
	* @return int <p>Возвращается код добавленного варианта значения или <i>false</i> в
	* случае ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arFieldsV = array(
	*    "ORDER_PROPS_ID" =&gt; 12,
	*    "VALUE" =&gt; "F",
	*    "NAME" =&gt; "В полной комплектации",
	*    "SORT" =&gt; 100,
	*    "DESCRIPTION" =&gt; "Доставка начинается после полного формирования заказа"
	* );
	* 
	* if (!CSaleOrderPropsVariant::Add($arFieldsV))
	*    echo "Ошибка добавления варианта значения";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvariant/csaleorderpropsvariant__add.0b68743c.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		if (!CSaleOrderPropsVariant::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_sale_order_props_variant", $arFields);

		$strSql =
			"INSERT INTO b_sale_order_props_variant(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		return $ID;
	}
}
?>