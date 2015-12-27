<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/catalog.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/index.php
 * @author Bitrix
 */
class CCatalog extends CAllCatalog
{
	
	/**
	* <p>Метод возвращает результат выборки записей из каталога в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле каталога, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и
	* т.д.). <br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи каталога.
	* Массив имеет вид: <pre class="syntax">array(
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
	* величины;</li> <li> <b>@</b> - оператор может использоваться для
	* целочисленных и вещественных данных при передаче набора
	* значений (массива). В этом случае при генерации sql-запроса будет
	* использован sql-оператор <b>IN</b>, дающий компактную форму записи;</li>
	* <li> <b>~</b> - значение поля проверяется на соответствие
	* передаваемому в фильтр шаблону;</li> <li> <b>%</b> - значение поля
	* проверяется на соответствие передаваемой в фильтр строке в
	* соответствии с языком запросов.</li> </ul> В качестве "название_поляX"
	* может стоять любое поле каталога.<br><br> Пример фильтра: <pre
	* class="syntax">array("SUBSCRIPTION" =&gt; "Y")</pre> Этот фильтр означает "выбрать все
	* записи, в которых значение в поле SUBSCRIPTION (флаг "Продажа контента")
	* равно Y".<br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи каталога. Массив
	* имеет вид: <pre class="syntax">array("название_поля1", "название_поля2", . . .)</pre> В
	* качестве "название_поля<i>N</i>" может стоять любое поле каталога. <br>
	* Если массив пустой, то метод вернет число записей,
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
	* @return CDBResult <p>Возвращает объект класса CDBResult, содержащий коллекцию
	* ассоциативных массивов с ключами:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код каталога.
	* Совпадает с кодом информационного блока.</td> </tr> <tr> <td>IBLOCK_ID</td>
	* <td>Код информационного блока.</td> </tr> <tr> <td>IBLOCK_TYPE_ID</td> <td>Тип
	* информационного блока.</td> </tr> <tr> <td>SUBSCRIPTION</td> <td>Флаг "Продажа
	* контента".</td> </tr> <tr> <td>NAME</td> <td>Название информационного блока.</td>
	* </tr> <tr> <td>YANDEX_EXPORT</td> <td>флаг "экспортировать в Яндекс.Товары"</td> </tr>
	* </table> <p>Если в качестве параметра arGroupBy передается пустой массив,
	* то метод вернет число записей, удовлетворяющих фильтру.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__getlist.d4805440.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		// For old-style execution
		if (!is_array($arOrder) && !is_array($arFilter))
		{
			$arOrder = strval($arOrder);
			$arFilter = strval($arFilter);
			if ('' != $arOrder && '' != $arFilter)
				$arOrder = array($arOrder => $arFilter);
			else
				$arOrder = array();
			if (is_array($arGroupBy))
				$arFilter = $arGroupBy;
			else
				$arFilter = array();
			$arGroupBy = false;
		}

		$arFields = array(
			"IBLOCK_ID" => array("FIELD" => "CI.IBLOCK_ID", "TYPE" => "int"),
			"YANDEX_EXPORT" => array("FIELD" => "CI.YANDEX_EXPORT", "TYPE" => "char"),
			"SUBSCRIPTION" => array("FIELD" => "CI.SUBSCRIPTION", "TYPE" => "char"),
			"VAT_ID" => array("FIELD" => "CI.VAT_ID", "TYPE" => "int"),
			"PRODUCT_IBLOCK_ID" => array("FIELD" => "CI.PRODUCT_IBLOCK_ID", "TYPE" => "int"),
			"SKU_PROPERTY_ID" => array("FIELD" => "CI.SKU_PROPERTY_ID", "TYPE" => "int"),
			"OFFERS_PROPERTY_ID" => array("FIELD" => "OFFERS.SKU_PROPERTY_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_catalog_iblock OFFERS ON (CI.IBLOCK_ID = OFFERS.PRODUCT_IBLOCK_ID)"),
			"OFFERS_IBLOCK_ID" => array("FIELD" => "OFFERS.IBLOCK_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_catalog_iblock OFFERS ON (CI.IBLOCK_ID = OFFERS.PRODUCT_IBLOCK_ID)"),
			"ID" => array("FIELD" => "I.ID", "TYPE" => "int", "FROM" => "INNER JOIN b_iblock I ON (CI.IBLOCK_ID = I.ID)"),
			"IBLOCK_TYPE_ID" => array("FIELD" => "I.IBLOCK_TYPE_ID", "TYPE" => "string", "FROM" => "INNER JOIN b_iblock I ON (CI.IBLOCK_ID = I.ID)"),
			"IBLOCK_ACTIVE" => array("FIELD" => "I.ACTIVE", "TYPE" => "char", "FROM" => "INNER JOIN b_iblock I ON (CI.IBLOCK_ID = I.ID)"),
			"LID" => array("FIELD" => "I.LID", "TYPE" => "string", "FROM" => "INNER JOIN b_iblock I ON (CI.IBLOCK_ID = I.ID)"),
			"NAME" => array("FIELD" => "I.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_iblock I ON (CI.IBLOCK_ID = I.ID)")
		);

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_iblock CI ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return False;
		}

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_iblock CI ".$arSqls["FROM"];
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
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_iblock CI ".$arSqls["FROM"];
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