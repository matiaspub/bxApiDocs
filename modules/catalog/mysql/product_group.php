<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/product_group.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductgroups/index.php
 * @author Bitrix
 */
class CCatalogProductGroups extends CAllCatalogProductGroups
{
	
	/**
	* <p>Метод добавляет новую запись с информацией о связи товара и группы пользователей, к которой пользователь привязывается при покупке товара, в соответствии с данными из массива arFields. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров новой информации о связи
	* товаров и групп пользователей, ключами в котором являются
	* названия параметров, а значениями - соответствующие значения.
	* Допустимые ключи: <ul> <li> <b> PRODUCT_ID</b> - код товара;</li> <li> <b> GROUP_ID</b> - код
	* группы пользователей;</li> <li> <b> ACCESS_LENGTH</b> - длина периода, на
	* который пользователь привязывается к группе пользователей при
	* покупке товара (0 - навсегда);</li> <li> <b> ACCESS_LENGTH_TYPE</b> - тип периода, на
	* который пользователь привязывается к группе пользователей при
	* покупке товара ("H" - час, "D" - сутки, "W" - неделя, "M" - месяц, "Q" - квартал,
	* "S" - полугодие, "Y" - год).</li> </ul>
	*
	* @return bool <p>Метод возвращает код вставленной записи или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductgroups/ccatalogproductgroups.add.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		if (!CCatalogProductGroups::CheckFields("ADD", $arFields, 0))
			return False;

		$arInsert = $DB->PrepareInsert("b_catalog_product2group", $arFields);

		$strSql = "INSERT INTO b_catalog_product2group(".$arInsert[0].") VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = intval($DB->LastID());

		return $ID;
	}

	
	/**
	* <p>Метод возвращает результат выборки записей информации о связи товаров и групп пользователей, к которым пользователь привязывается при покупке товара, в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле записи, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и
	* т.д.). <br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи. Массив имеет
	* вид: <pre class="syntax">array( "[модификатор1][оператор1]название_поля1" =&gt;
	* "значение1", "[модификатор2][оператор2]название_поля2" =&gt; "значение2",
	* . . . )</pre> Удовлетворяющие фильтру записи возвращаются в
	* результате, а записи, которые не удовлетворяют условиям фильтра,
	* отбрасываются.<br><br> Допустимыми являются следующие модификаторы:
	* <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> - значения null, 0 и пустая строка
	* так же удовлетворяют условиям фильтра.</li> </ul> Допустимыми
	* являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	* или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	* поля строго больше передаваемой в фильтр величины;</li> <li><b> -
	* значение поля меньше или равно передаваемой в фильтр
	* величины;</b></li> <li><b> - значение поля строго меньше передаваемой в
	* фильтр величины;</b></li> <li> <b>@</b> - оператор может использоваться для
	* целочисленных и вещественных данных при передаче набора
	* значений (массива). В этом случае при генерации sql-запроса будет
	* использован sql-оператор <b>IN</b>, дающий компактную форму записи;</li>
	* <li> <b>~</b> - значение поля проверяется на соответствие
	* передаваемому в фильтр шаблону;</li> <li> <b>%</b> - значение поля
	* проверяется на соответствие передаваемой в фильтр строке в
	* соответствии с языком запросов.</li> </ul> В качестве "название_поляX"
	* может стоять любое поле заказов.<br><br> Пример фильтра: <pre
	* class="syntax">array("PRODUCT_ID" =&gt; 150)</pre> Этот фильтр означает "выбрать все
	* записи, в которых значение в поле PRODUCT_ID (код товара) равно 150".<br><br>
	* Значение по умолчанию - пустой массив array() - означает, что
	* результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи. Массив имеет вид: <pre
	* class="syntax">array("название_поля1", "название_поля2", . . .)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле записи.<br><br> Если
	* массив пустой, то метод вернет число записей, удовлетворяющих
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
	* ассоциативных массивов с ключами:</p> <ul> <li> <b>ID</b> - код записи;</li> <li>
	* <b>PRODUCT_ID</b> - код товара;</li> <li> <b>GROUP_ID</b> - код группы
	* пользователей;</li> <li> <b>ACCESS_LENGTH</b> - длина периода, на который
	* пользователь привязывается к группе пользователей при покупке
	* товара (0 - навсегда);</li> <li> <b>ACCESS_LENGTH_TYPE</b> - тип периода, на который
	* пользователь привязывается к группе пользователей при покупке
	* товара ("H" - час, "D" - сутки, "W" - неделя, "M" - месяц, "Q" - квартал, "S" -
	* полугодие, "Y" - год);</li> <li> <b>GROUP_ACTIVE</b> - флаг активности группы
	* пользователей;</li> <li> <b>GROUP_NAME</b> - название группы пользователей.</li>
	* </ul> <p>Если в качестве параметра arGroupBy передается пустой массив, то
	* метод вернет число записей, удовлетворяющих фильтру.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductgroups/ccatalogproductgroups.getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		$arFields = array(
			"ID" => array("FIELD" => "CPG.ID", "TYPE" => "int"),
			"PRODUCT_ID" => array("FIELD" => "CPG.PRODUCT_ID", "TYPE" => "int"),
			"GROUP_ID" => array("FIELD" => "CPG.GROUP_ID", "TYPE" => "int"),
			"ACCESS_LENGTH" => array("FIELD" => "CPG.ACCESS_LENGTH", "TYPE" => "int"),
			"ACCESS_LENGTH_TYPE" => array("FIELD" => "CPG.ACCESS_LENGTH_TYPE", "TYPE" => "char"),
			"GROUP_ACTIVE" => array("FIELD" => "G.ACTIVE", "TYPE" => "char", "FROM" => "INNER JOIN b_group G ON (CPG.GROUP_ID = G.ID)"),
			"GROUP_NAME" => array("FIELD" => "G.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_group G ON (CPG.GROUP_ID = G.ID)")
		);

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_product2group CPG ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_product2group CPG ".$arSqls["FROM"];
		if (!empty($arSqls["WHERE"]))
			$strSql .= " WHERE ".$arSqls["WHERE"];
		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " GROUP BY ".$arSqls["GROUPBY"];
		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " ORDER BY ".$arSqls["ORDERBY"];

		$intTopCount = 0;
		$boolNavStartParams = (!empty($arNavStartParams) && is_array($arNavStartParams));
		if ($boolNavStartParams && array_key_exists('nTopCount', $arNavStartParams))
		{
			$intTopCount = intval($arNavStartParams["nTopCount"]);
		}
		if ($boolNavStartParams && 0 >= $intTopCount)
		{
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_product2group CPG ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql_tmp .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql_tmp .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (empty($arSqls["GROUPBY"]))
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if ($boolNavStartParams && 0 < $intTopCount)
			{
				$strSql .= " LIMIT ".$intTopCount;
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}
}
?>