<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/order_props.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderprops/index.php
 * @author Bitrix
 * @deprecated
 */
class CSaleOrderProps extends CAllSaleOrderProps
{
	
	/**
	* <p>Метод возвращает результат выборки из свойств заказов в соответствии со своими параметрами. Метод динамичный.</p>
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
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи свойств
	* заказа. Массив имеет вид: <pre class="syntax">array(
	* "[модификатор1][оператор1]название_поля1" =&gt; "значение1",
	* "[модификатор2][оператор2]название_поля2" =&gt; "значение2", . . . )</pre>
	* Удовлетворяющие фильтру записи возвращаются в результате, а
	* записи, которые не удовлетворяют условиям фильтра,
	* отбрасываются.<br><br> Допустимыми являются следующие модификаторы:
	* <ul> <li> <b>!</b> - отрицание;</li> <li> <b>+</b> - значения null, 0 и пустая строка
	* так же удовлетворяют условиям фильтра.</li> </ul> Допустимыми
	* являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	* или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	* поля строго больше передаваемой в фильтр величины;</li> <li> <b>&lt;=</b> -
	* значение поля меньше или равно передаваемой в фильтр величины;</li>
	* <li> <b>&lt;</b> - значение поля строго меньше передаваемой в фильтр
	* величины;</li> <li> <b>@</b> - значение поля находится в передаваемом в
	* фильтр разделенном запятой списке значений. Можно передавать и
	* фильтр. Для ключа <b>CODE</b> - корректно формирует фильтр только для
	* массива, а не для перечисление через запятые.;</li> <li> <b>~</b> -
	* значение поля проверяется на соответствие передаваемому в
	* фильтр шаблону;</li> <li> <b>%</b> - значение поля проверяется на
	* соответствие передаваемой в фильтр строке в соответствии с
	* языком запросов.</li> </ul> В качестве "название_поляX" может стоять
	* любое поле заказов.<br><br> Пример фильтра: <pre class="syntax">array("REQUIED" =&gt;
	* "Y")</pre> Этот фильтр означает "выбрать все записи, в которых
	* значение в поле REQUIED (обязательно для заполнения) равно Y".<br><br>
	* Значение по умолчанию - пустой массив array() - означает, что
	* результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи свойств заказа.
	* Массив имеет вид: <pre class="syntax"> array("название_поля1",
	* "группирующая_функция2" =&gt; "название_поля2", . . .)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле свойств заказа. В
	* качестве группирующей функции могут стоять: <ul> <li> <b>COUNT</b> -
	* подсчет количества;</li> <li> <b>AVG</b> - вычисление среднего значения;</li>
	* <li> <b>MIN</b> - вычисление минимального значения;</li> <li> <b>MAX</b> -
	* вычисление максимального значения;</li> <li> <b>UTIL</b> - флаг Y/N,
	* служебное;</li> <li> <b>SUM</b> - вычисление суммы.</li> </ul> Если массив
	* пустой, то метод вернет число записей, удовлетворяющих
	* фильтру.<br><br> Значение по умолчанию - <i>false</i> - означает, что
	* результат группироваться не будет.
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
	* ассоциативных массивов параметров свойств с ключами:</p> <table
	* class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td>
	* <td>Код свойства заказа.</td> </tr> <tr> <td>PERSON_TYPE_ID</td> <td>Тип
	* плательщика.</td> </tr> <tr> <td>NAME</td> <td>Название свойства.</td> </tr> <tr>
	* <td>TYPE</td> <td>Тип свойства. Допустимые значения: <ul> <li>CHECKBOX - флаг,</li>
	* <li>TEXT - строка текста,</li> <li>SELECT - выпадающий список значений, </li>
	* <li>MULTISELECT - список со множественным выбором,</li> <li>TEXTAREA -
	* многострочный текст,</li> <li>LOCATION - местоположение,</li> <li>RADIO -
	* переключатель.</li> </ul> </td> </tr> <tr> <td>REQUIED</td> <td>Флаг (Y/N) обязательное
	* ли поле.</td> </tr> <tr> <td>DEFAULT_VALUE</td> <td>Значение по умолчанию.</td> </tr> <tr>
	* <td>SORT</td> <td>Индекс сортировки.</td> </tr> <tr> <td>USER_PROPS</td> <td>Флаг (Y/N) входит
	* ли это свойство в профиль покупателя.</td> </tr> <tr> <td>IS_LOCATION</td> <td>Флаг
	* (Y/N) использовать ли значение свойства как местоположение
	* покупателя для расчёта стоимости доставки (только для свойств
	* типа LOCATION)</td> </tr> <tr> <td>PROPS_GROUP_ID</td> <td>Код группы свойств.</td> </tr> <tr>
	* <td>SIZE1</td> <td>Ширина поля (размер по горизонтали).</td> </tr> <tr> <td>SIZE2</td>
	* <td>Высота поля (размер по вертикали).</td> </tr> <tr> <td>DESCRIPTION</td>
	* <td>Описание свойства.</td> </tr> <tr> <td>IS_EMAIL</td> <td>Флаг (Y/N) использовать
	* ли значение свойства как E-Mail покупателя.</td> </tr> <tr> <td>IS_PROFILE_NAME</td>
	* <td>Флаг (Y/N) использовать ли значение свойства как название
	* профиля покупателя.</td> </tr> <tr> <td>IS_PAYER</td> <td>Флаг (Y/N) использовать ли
	* значение свойства как имя плательщика.</td> </tr> <tr> <td>IS_LOCATION4TAX</td>
	* <td>Флаг (Y/N) использовать ли значение свойства как местоположение
	* покупателя для расчёта налогов (только для свойств типа LOCATION)</td>
	* </tr> <tr> <td>CODE</td> <td>Символьный код свойства.</td> </tr> </table> <p>Если в
	* качестве параметра arGroupBy передается пустой массив, то метод
	* вернет число записей, удовлетворяющих фильтру.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выведем форму для ввода свойств заказа для группы свойств с кодом 5, которые входят в профиль покупателя, для типа плательщика с кодом 2
	* $db_props = CSaleOrderProps::GetList(
	*         array("SORT" =&gt; "ASC"),
	*         array(
	*                 "PERSON_TYPE_ID" =&gt; 2,
	*                 "PROPS_GROUP_ID" =&gt; 5,
	*                 "USER_PROPS" =&gt; "Y"
	*             ),
	*         false,
	*         false,
	*         array()
	*     );
	* 
	* if ($props = $db_props-&gt;Fetch())
	* {
	*    echo "Заполните параметры заказа:&lt;br&gt;";
	*    do
	*    {
	*       echo $props["NAME"];
	*       if ($props["REQUIED"]=="Y" || 
	*           $props["IS_EMAIL"]=="Y" || 
	*           $props["IS_PROFILE_NAME"]=="Y" || 
	*           $props["IS_LOCATION"]=="Y" || 
	*           $props["IS_LOCATION4TAX"]=="Y" || 
	*           $props["IS_PAYER"]=="Y")
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
	*          echo '&lt;input type="text" class="inputtext" size="'.((IntVal($props["SIZE1"])&gt;0)?$props["SIZE1"]:30).'" maxlength="250" value="'.htmlspecialchars($props["DEFAULT_VALUE"]).'" name="ORDER_PROP_'.$props["ID"].'"&gt;';
	*       }
	*       elseif ($props["TYPE"]=="SELECT")
	*       {
	*          echo '&lt;select name="ORDER_PROP_'.$props["ID"].'" size="'.((IntVal($props["SIZE1"])&gt;0)?$props["SIZE1"]:1).'"&gt;';
	*          $db_vars = CSaleOrderPropsVariant::GetList(($by="SORT"), ($order="ASC"), Array("ORDER_PROPS_ID"=&gt;$props["ID"]));
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
	*          $db_vars = CSaleOrderPropsVariant::GetList(($by="SORT"), ($order="ASC"), Array("ORDER_PROPS_ID"=&gt;$props["ID"]));
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
	*          $db_vars = CSaleLocation::GetList(Array("SORT"=&gt;"ASC", "COUNTRY_NAME_LANG"=&gt;"ASC", "CITY_NAME_LANG"=&gt;"ASC"), array(), LANGUAGE_ID);
	*          while ($vars = $db_vars-&gt;Fetch())
	*          {
	*             echo '&lt;option value="'.$vars["ID"].'"'.((IntVal($vars["ID"])==IntVal($props["DEFAULT_VALUE"]))?" selected":"").'&gt;'.htmlspecialchars($vars["COUNTRY_NAME"]." - ".$vars["CITY_NAME"]).'&lt;/option&gt;';
	*          }
	*          echo '&lt;/select&gt;';
	*       }
	*       elseif ($props["TYPE"]=="RADIO")
	*       {
	*          $db_vars = CSaleOrderPropsVariant::GetList(($by="SORT"), ($order="ASC"), Array("ORDER_PROPS_ID"=&gt;$props["ID"]));
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
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderprops/csaleorderprops__getlist.d76e30a4.php
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

			$arSelectFields = array("ID", "PERSON_TYPE_ID", "NAME", "TYPE", "REQUIED", "DEFAULT_VALUE", "SORT", "USER_PROPS", "IS_LOCATION", "PROPS_GROUP_ID", "SIZE1", "SIZE2", "DESCRIPTION", "IS_EMAIL", "IS_PROFILE_NAME", "IS_PAYER", "IS_LOCATION4TAX", "IS_ZIP", "CODE", "IS_FILTERED", "ACTIVE", "UTIL", "INPUT_FIELD_LOCATION", "MULTIPLE", "PAYSYSTEM_ID", "DELIVERY_ID");
		}

		if (empty($arSelectFields))
			$arSelectFields = array("ID", "PERSON_TYPE_ID", "NAME", "TYPE", "REQUIED", "DEFAULT_VALUE", "SORT", "USER_PROPS", "IS_LOCATION", "PROPS_GROUP_ID", "SIZE1", "SIZE2", "DESCRIPTION", "IS_EMAIL", "IS_PROFILE_NAME", "IS_PAYER", "IS_LOCATION4TAX", "IS_ZIP",	"CODE", "IS_FILTERED", "ACTIVE", "UTIL", "INPUT_FIELD_LOCATION", "MULTIPLE", "PAYSYSTEM_ID", "DELIVERY_ID");

		// filter by relation to delivery and payment systems
		if (isset($arFilter["RELATED"]) && !is_array($arFilter["RELATED"]) && intval($arFilter["RELATED"]) == 0) // filter all not related to anything
		{
			if (($key = array_search("PAYSYSTEM_ID", $arSelectFields)) !== false)
				unset($arSelectFields[$key]);

			if (($key = array_search("DELIVERY_ID", $arSelectFields)) !== false)
				unset($arSelectFields[$key]);
		}
		else if (isset($arFilter["RELATED"]) && is_array($arFilter["RELATED"]))
		{
			if (isset($arFilter["RELATED"]["PAYSYSTEM_ID"]))
			{
				$arFilter["PAYSYSTEM_ID"] = $arFilter["RELATED"]["PAYSYSTEM_ID"];
				unset($arFilter["RELATED"]["PAYSYSTEM_ID"]);
			}

			if (isset($arFilter["RELATED"]["DELIVERY_ID"]))
			{
				$arFilter["DELIVERY_ID"] = $arFilter["RELATED"]["DELIVERY_ID"];
				unset($arFilter["RELATED"]["DELIVERY_ID"]);
			}
		}

		// FIELDS -->
		$arFields = array(
			"ID" => array("FIELD" => "P.ID", "TYPE" => "int"),
			"PERSON_TYPE_ID" => array("FIELD" => "P.PERSON_TYPE_ID", "TYPE" => "int"),
			"NAME" => array("FIELD" => "P.NAME", "TYPE" => "string"),
			"TYPE" => array("FIELD" => "P.TYPE", "TYPE" => "string"),
			"REQUIED" => array("FIELD" => "P.REQUIED", "TYPE" => "char"),
			"REQUIRED" => array("FIELD" => "P.REQUIED", "TYPE" => "char"),
			//"DEFAULT_VALUE" => array("FIELD" => "P.DEFAULT_VALUE", "TYPE" => "string"),
			"SORT" => array("FIELD" => "P.SORT", "TYPE" => "int"),
			"USER_PROPS" => array("FIELD" => "P.USER_PROPS", "TYPE" => "char"),
			"IS_LOCATION" => array("FIELD" => "P.IS_LOCATION", "TYPE" => "char"),
			"PROPS_GROUP_ID" => array("FIELD" => "P.PROPS_GROUP_ID", "TYPE" => "int"),
			"SIZE1" => array("FIELD" => "P.SIZE1", "TYPE" => "int"),
			"SIZE2" => array("FIELD" => "P.SIZE2", "TYPE" => "int"),
			"DESCRIPTION" => array("FIELD" => "P.DESCRIPTION", "TYPE" => "string"),
			"IS_EMAIL" => array("FIELD" => "P.IS_EMAIL", "TYPE" => "char"),
			"IS_PROFILE_NAME" => array("FIELD" => "P.IS_PROFILE_NAME", "TYPE" => "char"),
			"IS_PAYER" => array("FIELD" => "P.IS_PAYER", "TYPE" => "char"),
			"IS_LOCATION4TAX" => array("FIELD" => "P.IS_LOCATION4TAX", "TYPE" => "char"),
			"IS_FILTERED" => array("FIELD" => "P.IS_FILTERED", "TYPE" => "char"),
			"IS_ZIP" => array("FIELD" => "P.IS_ZIP", "TYPE" => "char"),
			"CODE" => array("FIELD" => "P.CODE", "TYPE" => "string"),
			"ACTIVE" => array("FIELD" => "P.ACTIVE", "TYPE" => "char"),
			"UTIL" => array("FIELD" => "P.UTIL", "TYPE" => "char"),
			"INPUT_FIELD_LOCATION" => array("FIELD" => "P.INPUT_FIELD_LOCATION", "TYPE" => "int"),
			"MULTIPLE" => array("FIELD" => "P.MULTIPLE", "TYPE" => "char"),

			"GROUP_ID" => array("FIELD" => "PG.ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_order_props_group PG ON (P.PROPS_GROUP_ID = PG.ID)"),
			"GROUP_PERSON_TYPE_ID" => array("FIELD" => "PG.PERSON_TYPE_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_order_props_group PG ON (P.PROPS_GROUP_ID = PG.ID)"),
			"GROUP_NAME" => array("FIELD" => "PG.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order_props_group PG ON (P.PROPS_GROUP_ID = PG.ID)"),
			"GROUP_SORT" => array("FIELD" => "PG.SORT", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_order_props_group PG ON (P.PROPS_GROUP_ID = PG.ID)"),

			"PERSON_TYPE_LID" => array("FIELD" => "SPT.LID", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_person_type SPT ON (P.PERSON_TYPE_ID = SPT.ID)"),
			"PERSON_TYPE_NAME" => array("FIELD" => "SPT.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_person_type SPT ON (P.PERSON_TYPE_ID = SPT.ID)"),
			"PERSON_TYPE_SORT" => array("FIELD" => "SPT.SORT", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_person_type SPT ON (P.PERSON_TYPE_ID = SPT.ID)"),
			"PERSON_TYPE_ACTIVE" => array("FIELD" => "SPT.ACTIVE", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_person_type SPT ON (P.PERSON_TYPE_ID = SPT.ID)"),

			"PAYSYSTEM_ID" => array("FIELD" => "SOP.PROPERTY_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_order_props_relation SOP ON P.ID = SOP.PROPERTY_ID", "WHERE" => array("CSaleOrderProps", "PrepareRelation4Where")),
			"DELIVERY_ID" => array("FIELD" => "SOD.PROPERTY_ID", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order_props_relation SOD ON P.ID = SOD.PROPERTY_ID", "WHERE" => array("CSaleOrderProps", "PrepareRelation4Where"))
		);
		// <-- FIELDS

		self::addPropertyDefaultValueField('P', $arFields);

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		//filter order properties by relation to delivery and payment systems
		if (isset($arFilter["RELATED"]))
		{
			if (!is_array($arFilter["RELATED"]) && intval($arFilter["RELATED"]) == 0)
			{
				if (strlen($arSqls["WHERE"]) > 0)
					$arSqls["WHERE"] .= " AND ";
				$arSqls["WHERE"] .= "(P.ID NOT IN (SELECT DISTINCT SOR.PROPERTY_ID FROM b_sale_order_props_relation SOR))";
			}
			elseif (is_array($arFilter["RELATED"]))
			{
				$strSqlRelatedWhere = "";

				// payment
				if (isset($arFilter["PAYSYSTEM_ID"]) && intval($arFilter["PAYSYSTEM_ID"]) > 0)
					$strSqlRelatedWhere .= "(SOP.ENTITY_TYPE = 'P' AND SOP.ENTITY_ID = ".$DB->ForSql($arFilter["PAYSYSTEM_ID"]).")";

				// delivery
				if (isset($arFilter["DELIVERY_ID"]) && strlen($arFilter["DELIVERY_ID"]) > 0)
				{
					if (strlen($strSqlRelatedWhere) > 0)
					{
						$logic = "OR";
						if (isset($arFilter["RELATED"]["LOGIC"]) && $arFilter["RELATED"]["LOGIC"] == "AND")
							$logic = "AND";

						$strSqlRelatedWhere .= " ".$logic." ";
					}

					$strSqlRelatedWhere .= "(SOD.ENTITY_TYPE = 'D' AND SOD.ENTITY_ID = '".$DB->ForSql($arFilter["DELIVERY_ID"])."')";
				}

				// all other
				if (isset($arFilter["RELATED"]["TYPE"]))
				{
					if ($arFilter["RELATED"]["TYPE"] == "WITH_NOT_RELATED")
					{
						if (strlen($strSqlRelatedWhere) > 0)
							$strSqlRelatedWhere .= " OR (P.ID NOT IN (SELECT DISTINCT SOR.PROPERTY_ID FROM b_sale_order_props_relation SOR))";
					}
				}

				if (strlen($strSqlRelatedWhere) > 0)
				{
					if (strlen($arSqls["WHERE"]) > 0)
						$arSqls["WHERE"] .= " AND ";

					$arSqls["WHERE"] .= "(".$strSqlRelatedWhere.")";
				}
			}
		}

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_order_props P ".
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
			"FROM b_sale_order_props P ".
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
				"FROM b_sale_order_props P ".
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
	* <p>Метод добавляет новое свойство заказа с параметрами из массива arFields. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив, в котором ключами являются названия
	* параметров свойства, а значениями - значения этих параметров.<br><br>
	* Допустимые ключи: <ul> <li> <b>PERSON_TYPE_ID</b> - тип плательщика;</li> <li> <b>NAME</b>
	* - название свойства (тип плательщика зависит от сайта, а сайт - от
	* языка; название должно быть на соответствующем языке);</li> <li>
	* <b>TYPE</b> - тип свойства. Допустимые значения: <ul> <li> <b>CHECKBOX</b> - флаг;</li>
	* <li> <b>TEXT</b> - строка текста;</li> <li> <b>SELECT</b> - выпадающий список
	* значений;</li> <li> <b>MULTISELECT</b> - список со множественным выбором;</li> <li>
	* <b>TEXTAREA</b> - многострочный текст;</li> <li> <b>LOCATION</b> - местоположение;</li>
	* <li> <b>RADIO</b> - переключатель.</li> </ul> </li> <li> <b>REQUIED</b> - флаг (Y/N)
	* обязательное ли поле;</li> <li> <b>DEFAULT_VALUE</b> - значение по умолчанию;</li>
	* <li> <b>SORT</b> - индекс сортировки;</li> <li> <b>USER_PROPS</b> - флаг (Y/N) входит ли
	* это свойство в профиль покупателя;</li> <li> <b>IS_LOCATION</b> - флаг (Y/N)
	* использовать ли значение свойства как местоположение покупателя
	* для расчёта стоимости доставки (только для свойств типа LOCATION); </li>
	* <li> <b>PROPS_GROUP_ID</b> - код группы свойств;</li> <li> <b>SIZE1</b> - ширина поля
	* (размер по горизонтали);</li> <li> <b>SIZE2</b> - высота поля (размер по
	* вертикали);</li> <li> <b>DESCRIPTION</b> - описание свойства;</li> <li> <b>IS_EMAIL</b> -
	* флаг (Y/N) использовать ли значение свойства как E-Mail покупателя;</li>
	* <li> <b>IS_PROFILE_NAME</b> - флаг (Y/N) использовать ли значение свойства как
	* название профиля покупателя; </li> <li> <b>IS_PAYER</b> - флаг (Y/N)
	* использовать ли значение свойства как имя плательщика;</li> <li>
	* <b>IS_LOCATION4TAX</b> - флаг (Y/N) использовать ли значение свойства как
	* местоположение покупателя для расчёта налогов (только для
	* свойств типа <b>LOCATION</b>);</li> <li> <b>CODE</b> - символьный код свойства.</li>
	* <li> <b>IS_FILTERED</b> - свойство доступно в фильтре по заказам. С версии
	* 10.0.</li> <li> <b>IS_ZIP</b> - использовать как почтовый индекс. С версии
	* 10.0.</li> <li> <b>UTIL</b> - позволяет использовать свойство только в
	* административной части. С версии 11.0.</li> </ul>
	*
	* @return int <p>Возвращается код добавленного свойства заказа.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arFields = array(
	*    "PERSON_TYPE_ID" =&gt; 2,
	*    "NAME" =&gt; "Комплектация",
	*    "TYPE" =&gt; "RADIO",
	*    "REQUIED" =&gt; "Y",
	*    "DEFAULT_VALUE" =&gt; "F",
	*    "SORT" =&gt; 100,
	*    "CODE" =&gt; "COMPLECT",
	*    "USER_PROPS" =&gt; "N",
	*    "IS_LOCATION" =&gt; "N",
	*    "IS_LOCATION4TAX" =&gt; "N",
	*    "PROPS_GROUP_ID" =&gt; 1,
	*    "SIZE1" =&gt; 0,
	*    "SIZE2" =&gt; 0,
	*    "DESCRIPTION" =&gt; "",
	*    "IS_EMAIL" =&gt; "N",
	*    "IS_PROFILE_NAME" =&gt; "N",
	*    "IS_PAYER" =&gt; "N"
	* );
	* 
	* // Если установлен код свойства, то изменяем свойство с этим кодом,
	* // иначе добавляем новой свойство
	* if ($ID&gt;0)
	* {
	*    if (!CSaleOrderProps::Update($ID, $arFields))
	*    {
	*       echo "Ошибка изменения параметров свойства";
	*    }
	*    else
	*    {
	*       // Обновим символьный код у значений свойства
	*       // (хранение избыточных данных для оптимизации работы)
	*       $db_order_props_tmp =
	*           CSaleOrderPropsValue::GetList(($b="NAME"),
	*                                         ($o="ASC"),
	*                                         Array("ORDER_PROPS_ID"=&gt;$ID));
	*       while ($ar_order_props_tmp = $db_order_props_tmp-&gt;Fetch())
	*       {
	*          CSaleOrderPropsValue::Update($ar_order_props_tmp["ID"],
	*                                       array("CODE" =&gt; "COMPLECT"));
	*       }
	*    }
	* }
	* else
	* {
	*    $ID = CSaleOrderProps::Add($arFields);
	*    if ($ID&lt;=0)
	*       echo "Ошибка добавления свойства";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderprops/csaleorderprops__add.b64a5ac9.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		foreach(GetModuleEvents("sale", "OnBeforeOrderPropsAdd", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields))===false)
				return false;
		}

		if (!CSaleOrderProps::CheckFields("ADD", $arFields))
			return false;

		// translate here
		$arFields['DEFAULT_VALUE'] = self::translateLocationIDToCode($arFields);

		$arInsert = $DB->PrepareInsert("b_sale_order_props", $arFields);

		$strSql = "INSERT INTO b_sale_order_props(".$arInsert[0].") VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = intval($DB->LastID());

		foreach(GetModuleEvents("sale", "OnOrderPropsAdd", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
		}

		return $ID;
	}
}
?>