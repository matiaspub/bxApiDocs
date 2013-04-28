<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/delivery.php");


/**
 * 
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledelivery/index.php
 * @author Bitrix
 */
class CSaleDelivery extends CAllSaleDelivery
{
	public static function PrepareCurrency4Where($val, $key, $operation, $negative, $field, &$arField, &$arFilter)
	{
		$val = DoubleVal($val);

		$baseSiteCurrency = "";
		if (isset($arFilter["LID"]) && strlen($arFilter["LID"]) > 0)
			$baseSiteCurrency = CSaleLang::GetLangCurrency($arFilter["LID"]);
		elseif (isset($arFilter["CURRENCY"]) && strlen($arFilter["CURRENCY"]) > 0)
			$baseSiteCurrency = $arFilter["CURRENCY"];

		if (strlen($baseSiteCurrency) <= 0)
			return False;

		$strSqlSearch = "";

		$dbCurrency = CCurrency::GetList(($by = "sort"), ($order = "asc"));
		while ($arCurrency = $dbCurrency->Fetch())
		{
			$val1 = roundEx(CCurrencyRates::ConvertCurrency($val, $baseSiteCurrency, $arCurrency["CURRENCY"]), SALE_VALUE_PRECISION);
			if (strlen($strSqlSearch) > 0)
				$strSqlSearch .= " OR ";

			$strSqlSearch .= "(D.ORDER_CURRENCY = '".$arCurrency["CURRENCY"]."' AND ";
			if ($negative == "Y")
				$strSqlSearch .= "NOT";
			$strSqlSearch .= "(".$field." ".$operation." ".$val1." OR ".$field." IS NULL OR ".$field." = 0)";
			$strSqlSearch .= ")";
		}

		return "(".$strSqlSearch.")";
	}

	public static function PrepareLocation4Where($val, $key, $operation, $negative, $field, &$arField, &$arFilter)
	{
		return "(D2L.LOCATION_ID = ".IntVal($val)." AND D2L.LOCATION_TYPE = 'L' ".
			" OR L2LG.LOCATION_ID = ".IntVal($val)." AND D2L.LOCATION_TYPE = 'G') ";
	}

	// If the money is given by the filter, then the filter is mandatory LID!
	
	/**
	 * <p>Функция возвращает результат выборки записей из служб доставки в соответствии со своими параметрами. </p>
	 *
	 *
	 *
	 *
	 * @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	 * записи. Массив имеет вид: <pre class="syntax">array(<br>"название_поля1" =&gt;
	 * "направление_сортировки1",<br>"название_поля2" =&gt;
	 * "направление_сортировки2",<br>. . .<br>)</pre> В качестве
	 * "название_поля<i>N</i>" может стоять любое поле корзины, а в качестве
	 * "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	 * возрастанию) и "<i>DESC</i>" (по убыванию). <br><br> Если массив сортировки
	 * имеет несколько элементов, то результирующий набор сортируется
	 * последовательно по каждому элементу (т.е. сначала сортируется по
	 * первому элементу, потом результат сортируется по второму и т.д.). 
	 * <br><br> Значение по умолчанию - пустой массив array() - означает, что
	 * результат отсортирован не будет.
	 *
	 *
	 *
	 * @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи службы
	 * доставки. Массив имеет вид: <pre
	 * class="syntax">array(<br>"[модификатор1][оператор1]название_поля1" =&gt;
	 * "значение1",<br>"[модификатор2][оператор2]название_поля2" =&gt;
	 * "значение2",<br>. . .<br>)</pre> Удовлетворяющие фильтру записи
	 * возвращаются в результате, а записи, которые не удовлетворяют
	 * условиям фильтра, отбрасываются. <br><br> Допустимыми являются
	 * следующие модификаторы: <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> -
	 * значения null, 0 и пустая строка так же удовлетворяют условиям
	 * фильтра.</li> </ul> Допустимыми являются следующие операторы: <ul> <li>
	 * <b>&gt;= </b> - значение поля больше или равно передаваемой в фильтр
	 * величины;</li> <li> <b>&gt;</b> - значение поля строго больше передаваемой
	 * в фильтр величины;</li> <li> <b>&lt;=</b> - значение поля меньше или равно
	 * передаваемой в фильтр величины;</li> <li> <b>&lt;</b> - значение поля
	 * строго меньше передаваемой в фильтр величины;</li> <li> <b>@</b> -
	 * значение поля находится в передаваемом в фильтр разделенном
	 * запятой списке значений;</li> <li> <b>~</b> - значение поля проверяется на
	 * соответствие передаваемому в фильтр шаблону;</li> <li> <b>%</b> -
	 * значение поля проверяется на соответствие передаваемой в фильтр
	 * строке в соответствии с языком запросов.</li> </ul> В качестве
	 * "название_поляX" может стоять любое поле корзины. <br><br> Пример
	 * фильтра: <pre class="syntax">array("+&lt;=WEIGHT_FROM" =&gt; 1000)</pre> Этот фильтр означает
	 * "выбрать все записи, в которых значение в поле WEIGHT_FROM (вес от)
	 * меньше либо равно 1000 или значение не установлено (null или ноль)".
	 * <br><br> Значение по умолчанию - пустой массив array() - означает, что
	 * результат отфильтрован не будет.
	 *
	 *
	 *
	 * @param array $arGroupBy = false Массив полей, по которым группируются записи служб доставки.
	 * Массив имеет вид: <pre class="syntax">array("название_поля1",<br>
	 * "группирующая_функция2" =&gt; "название_поля2", ...)</pre> В качестве
	 * "название_поля<i>N</i>" может стоять любое поле служб доставки. В
	 * качестве группирующей функции могут стоять: <ul> <li> <b> COUNT</b> -
	 * подсчет количества;</li> <li> <b>AVG</b> - вычисление среднего значения;</li>
	 * <li> <b>MIN</b> - вычисление минимального значения;</li> <li> <b> MAX</b> -
	 * вычисление максимального значения;</li> <li> <b>SUM</b> - вычисление
	 * суммы.</li> </ul> Если массив пустой, то функция вернет число записей,
	 * удовлетворяющих фильтру. <br><br> Значение по умолчанию - <i>false</i> -
	 * означает, что результат группироваться не будет.
	 *
	 *
	 *
	 * @param array $arNavStartParams = false Массив параметров выборки. Может содержать следующие ключи: <ul>
	 * <li>"<b>nTopCount</b>" - количество возвращаемых функцией записей будет
	 * ограничено сверху значением этого ключа;</li> <li> любой ключ,
	 * принимаемый методом <b> CDBResult::NavQuery</b> в качестве третьего
	 * параметра.</li> </ul> Значение по умолчанию - <i>false</i> - означает, что
	 * параметров выборки нет.
	 *
	 *
	 *
	 * @param array $arSelectFields = array() Массив полей записей, которые будут возвращены функцией. Можно
	 * указать только те поля, которые необходимы. Если в массиве
	 * присутствует значение "*", то будут возвращены все доступные поля.
	 * <br><br> Значение по умолчанию - пустой массив array() - означает, что
	 * будут возвращены все поля основной таблицы запроса.
	 *
	 *
	 *
	 * @return CDBResult <p>Возвращается объект класса CDBResult, содержащий набор
	 * ассоциативных массивов параметров доставки с ключами:</p><table
	 * width="100%" class="tnormal"><tbody> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td>
	 * <td>Код службы доставки.</td> </tr> <tr> <td>NAME</td> <td>Название доставки.</td>
	 * </tr> <tr> <td>LID</td> <td>Код сайта, к которому привязана эта доставка.</td>
	 * </tr> <tr> <td>PERIOD_FROM</td> <td>Минимальный срок доставки.</td> </tr> <tr> <td>PERIOD_TO</td>
	 * <td>Максимальный срок доставки.</td> </tr> <tr> <td>PERIOD_TYPE</td> <td>Единица
	 * измерения срока: D - дни, H - часы, M - месяцы.</td> </tr> <tr> <td>WEIGHT_FROM</td>
	 * <td>Минимальный вес заказа, для которого возможна эта доставка
	 * (единица измерения едина на сайте).</td> </tr> <tr> <td>WEIGHT_TO</td>
	 * <td>Максимальный вес заказа, для которого возможна эта доставка
	 * (единица измерения едина на сайте).</td> </tr> <tr> <td>ORDER_PRICE_FROM</td>
	 * <td>Минимальная стоимость заказа, для которой возможна эта
	 * доставка.</td> </tr> <tr> <td>ORDER_PRICE_TO</td> <td>Максимальная стоимость заказа,
	 * для которой возможна эта доставка.</td> </tr> <tr> <td>ORDER_CURRENCY</td> <td>Валюта
	 * ограничений по стоимости.</td> </tr> <tr> <td>ACTIVE</td> <td>Флаг (Y/N) активности
	 * доставки.</td> </tr> <tr> <td>PRICE</td> <td>Стоимость доставки.</td> </tr> <tr>
	 * <td>CURRENCY</td> <td>Валюта стоимости доставки.</td> </tr> <tr> <td>SORT</td> <td>Индекс
	 * сортировки.</td> </tr> <tr> <td>DESCRIPTION</td> <td>Описание доставки.</td> </tr>
	 * </tbody></table><p>Если в качестве параметра <b> arGroupBy</b> передается пустой
	 * массив, то функция вернет число записей, удовлетворяющих
	 * фильтру.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>// Выберем отсортированные по индексу сортировки, а потом (при равных индексах) по имени<br>// активные службы доставки, доступные для текущего сайта, заказа с весом $ORDER_WEIGHT и <br>// стоимостью $ORDER_PRICE (в базовой валюте текущего сайта), доставки в <br>// местоположение $DELIVERY_LOCATION<br>$db_dtype = CSaleDelivery::GetList(<br>    array(<br>            "SORT" =&gt; "ASC",<br>            "NAME" =&gt; "ASC"<br>        ),<br>    array(<br>            "LID" =&gt; SITE_ID,<br>            "+&lt;=WEIGHT_FROM" =&gt; $ORDER_WEIGHT,<br>            "+&gt;=WEIGHT_TO" =&gt; $ORDER_WEIGHT,<br>            "+&lt;=ORDER_PRICE_FROM" =&gt; $ORDER_PRICE,<br>            "+&gt;=ORDER_PRICE_TO" =&gt; $ORDER_PRICE,<br>            "ACTIVE" =&gt; "Y",<br>            "LOCATION" =&gt; $DELIVERY_LOCATION<br>        ),<br>    false,<br>    false,<br>    array()<br>);<br>if ($ar_dtype = $db_dtype-&gt;Fetch())<br>{<br>   echo "Вам доступны следующие способы доставки:&lt;br&gt;";<br>   do<br>   {<br>      echo $ar_dtype["NAME"]." - стоимость ".CurrencyFormat($ar_dtype["PRICE"], $ar_dtype["CURRENCY"])."&lt;br&gt;";<br>   }<br>   while ($ar_dtype = $db_dtype-&gt;Fetch());<br>}<br>else<br>{<br>   echo "Доступных способов доставки не найдено&lt;br&gt;";<br>}<br>?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledelivery/csaledelivery__getlist.28cc1782.php
	 * @author Bitrix
	 */
	public static function GetList($arOrder = array("SORT" => "ASC", "NAME" => "ASC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (isset($arFilter["WEIGHT"]) && DoubleVal($arFilter["WEIGHT"]) > 0)
		{
			// changed by Sigurd, 2007-08-16
			if (!isset($arFilter["WEIGHT_FROM"]) || DoubleVal($arFilter["WEIGHT"]) > DoubleVal($arFilter["WEIGHT_FROM"]))
				$arFilter["+<=WEIGHT_FROM"] = $arFilter["WEIGHT"];
			if (!isset($arFilter["WEIGHT_TO"]) || DoubleVal($arFilter["WEIGHT"]) < DoubleVal($arFilter["WEIGHT_TO"]))
				$arFilter["+>=WEIGHT_TO"] = $arFilter["WEIGHT"];
		}

		if (isset($arFilter["ORDER_PRICE"]) && IntVal($arFilter["ORDER_PRICE"]) > 0)
		{
			if (!isset($arFilter["ORDER_PRICE_FROM"]) || IntVal($arFilter["ORDER_PRICE"]) > IntVal($arFilter["ORDER_PRICE_FROM"]))
				$arFilter["+<=ORDER_PRICE_FROM"] = $arFilter["ORDER_PRICE"];
			if (!isset($arFilter["ORDER_PRICE_TO"]) || IntVal($arFilter["ORDER_PRICE"]) < IntVal($arFilter["ORDER_PRICE_TO"]))
				$arFilter["+>=ORDER_PRICE_TO"] = $arFilter["ORDER_PRICE"];
		}

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "NAME", "LID", "PERIOD_FROM", "PERIOD_TO", "PERIOD_TYPE", "WEIGHT_FROM", "WEIGHT_TO", "ORDER_PRICE_FROM", "ORDER_PRICE_TO", "ORDER_CURRENCY", "ACTIVE", "PRICE", "CURRENCY", "SORT", "DESCRIPTION", "LOGOTIP", "STORE");

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "D.ID", "TYPE" => "int"),
				"NAME" => array("FIELD" => "D.NAME", "TYPE" => "string"),
				"LID" => array("FIELD" => "D.LID", "TYPE" => "string"),
				"PERIOD_FROM" => array("FIELD" => "D.PERIOD_FROM", "TYPE" => "int"),
				"PERIOD_TO" => array("FIELD" => "D.PERIOD_TO", "TYPE" => "int"),
				"PERIOD_TYPE" => array("FIELD" => "D.PERIOD_TYPE", "TYPE" => "char"),
				"WEIGHT_FROM" => array("FIELD" => "D.WEIGHT_FROM", "TYPE" => "double"),
				"WEIGHT_TO" => array("FIELD" => "D.WEIGHT_TO", "TYPE" => "double"),
				"ORDER_PRICE_FROM" => array("FIELD" => "D.ORDER_PRICE_FROM", "TYPE" => "double", "WHERE" => array("CSaleDelivery", "PrepareCurrency4Where")),
				"ORDER_PRICE_TO" => array("FIELD" => "D.ORDER_PRICE_TO", "TYPE" => "double", "WHERE" => array("CSaleDelivery", "PrepareCurrency4Where")),
				"ORDER_CURRENCY" => array("FIELD" => "D.ORDER_CURRENCY", "TYPE" => "string"),
				"ACTIVE" => array("FIELD" => "D.ACTIVE", "TYPE" => "char"),
				"PRICE" => array("FIELD" => "D.PRICE", "TYPE" => "double"),
				"CURRENCY" => array("FIELD" => "D.CURRENCY", "TYPE" => "string"),
				"SORT" => array("FIELD" => "D.SORT", "TYPE" => "int"),
				"DESCRIPTION" => array("FIELD" => "D.DESCRIPTION", "TYPE" => "string"),
				"LOCATION" => array("FIELD" => "D.DESCRIPTION", "WHERE_ONLY" => "Y", "TYPE" => "int", "FROM" => "INNER JOIN b_sale_delivery2location D2L ON (D.ID = D2L.DELIVERY_ID) LEFT JOIN b_sale_location2location_group L2LG ON (D2L.LOCATION_TYPE = 'G' AND D2L.LOCATION_ID = L2LG.LOCATION_GROUP_ID)", "WHERE" => array("CSaleDelivery", "PrepareLocation4Where")),
				"LOGOTIP" => array("FIELD" => "D.LOGOTIP", "TYPE" => "int"),
				"STORE" => array("FIELD" => "D.STORE", "TYPE" => "string"),
			);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_delivery D ".
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
			"FROM b_sale_delivery D ".
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
				"FROM b_sale_delivery D ".
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
	 * <p>Функция добавляет новый способ (службу) доставки с параметрами из массива arFields </p>
	 *
	 *
	 *
	 *
	 * @param array $arFields  Ассоциативный массив параметров доставки, ключами в котором
	 * являются названия параметров доставки, а значениями - значения
	 * параметров. <br><br> Допустимые ключи: <br><ul> <li> <b>NAME</b> - название
	 * доставки (обязательное, задается на языке сайта, к которому
	 * привязана эта доставка);</li> <li> <b>LID</b> - код сайта, к которому
	 * привязана эта доставка;</li> <li> <b>PERIOD_FROM</b> - минимальный срок
	 * доставки;</li> <li> <b>PERIOD_TO</b> - максимальный срок доставки;</li> <li>
	 * <b>PERIOD_TYPE</b> - единица измерения срока: D - дни, H - часы, M - месяцы;</li> <li>
	 * <b>WEIGHT_FROM</b> - минимальный вес заказа, для которого возможна эта
	 * доставка (единица измерения должна быть едина на сайте);</li> <li>
	 * <b>WEIGHT_TO</b> - максимальный вес заказа, для которого возможна эта
	 * доставка (единица измерения должна быть едина на сайте);</li> <li>
	 * <b>ORDER_PRICE_FROM</b> - минимальная стоимость заказа, для которой возможна
	 * эта доставка;</li> <li> <b>ORDER_PRICE_TO</b> - максимальная стоимость заказа,
	 * для которой возможна эта доставка;</li> <li> <b>ORDER_CURRENCY</b> - валюта
	 * ограничений по стоимости;</li> <li> <b>ACTIVE</b> - флаг (Y/N) активности
	 * доставки;</li> <li> <b>PRICE</b> - стоимость доставки;</li> <li> <b>CURRENCY</b> - валюта
	 * стоимости доставки;</li> <li> <b>SORT</b> - индекс сортировки;</li> <li>
	 * <b>DESCRIPTION</b> - описание доставки;</li> <li> <b>LOCATIONS</b> - массив массивов
	 * вида: <pre class="syntax">array("LOCATION_ID" =&gt; "код местоположения или <br> группы
	 * местоположений",<br> "LOCATION_TYPE"=&gt;"L - для местоположения, <br> G - для
	 * группы")</pre> содержащий местоположения и группы местоположений,
	 * для которых работает эта доставка</li> </ul>
	 *
	 *
	 *
	 * @return int <p>Возвращает код добавленной записи или <i>false</i> в случае
	 * ошибки.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$arFields = array(<br>   "NAME" =&gt; "Доставка курьером",<br>   "LID" =&gt; "ru",<br>   "PERIOD_FROM" =&gt; 1,<br>   "PERIOD_TO" =&gt; 3,<br>   "PERIOD_TYPE" =&gt; "D",<br>   "WEIGHT_FROM" =&gt; 0,<br>   "WEIGHT_TO" =&gt; 2500,<br>   "ORDER_PRICE_FROM" =&gt; 0,<br>   "ORDER_PRICE_TO" =&gt; 10000,<br>   "ORDER_CURRENCY" =&gt; "RUB",<br>   "ACTIVE" =&gt; "Y",<br>   "PRICE" =&gt; 58,<br>   "CURRENCY" =&gt; "RUB",<br>   "SORT" =&gt; 100,<br>   "DESCRIPTION" =&gt; "Заказ будет доставлен Вам в течение 3 - 10 рабочих дней после передачи его в курьерскую службу.",<br>   "LOCATIONS" =&gt; array(<br>      array("LOCATION_ID"=&gt;1, "LOCATION_TYPE"=&gt;"L"),<br>      array("LOCATION_ID"=&gt;3, "LOCATION_TYPE"=&gt;"G")<br>      )<br>);<br><br>$ID = CSaleDelivery::Add($arFields);<br>if ($ID&lt;=0)<br>   echo "Ошибка добавления доставки";<br>?&gt;<br>
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledelivery/csaledelivery__add.564001a4.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB;

		if (!CSaleDelivery::CheckFields("ADD", $arFields))
			return false;

		if (array_key_exists("LOGOTIP", $arFields) && is_array($arFields["LOGOTIP"]))
			$arFields["LOGOTIP"]["MODULE_ID"] = "sale";

		CFile::SaveForDB($arFields, "LOGOTIP", "sale/delivery/logotip");

		$arInsert = $DB->PrepareInsert("b_sale_delivery", $arFields);

		$strSql =
			"INSERT INTO b_sale_delivery(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		foreach($arFields["LOCATIONS"] as $location)
		{
			$arInsert = $DB->PrepareInsert("b_sale_delivery2location", $location);

			$strSql =
				"INSERT INTO b_sale_delivery2location(DELIVERY_ID, ".$arInsert[0].") ".
				"VALUES(".$ID.", ".$arInsert[1].")";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		if (is_set($arFields, "PAY_SYSTEM"))
		{
			CSaleDelivery::UpdateDeliveryPay($ID, $arFields["PAY_SYSTEM"]);
		}
		
		return $ID;
	}
}
?>