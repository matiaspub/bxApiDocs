<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/catalog.php");


/**
 * 
 *
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
	 * <p>Функция возвращает результат выборки записей из каталога в соответствии со своими параметрами.</p>
	 *
	 *
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
	 *
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
	 * величины;</li> <li> <b>@</b> - значение поля находится в передаваемом в
	 * фильтр разделенном запятой списке значений;</li> <li> <b>~</b> - значение
	 * поля проверяется на соответствие передаваемому в фильтр
	 * шаблону;</li> <li> <b>%</b> - значение поля проверяется на соответствие
	 * передаваемой в фильтр строке в соответствии с языком запросов.</li>
	 * </ul> В качестве "название_поляX" может стоять любое поле
	 * каталога.<br><br> Пример фильтра: <pre class="syntax">array("SUBSCRIPTION" =&gt; "Y")</pre>
	 * Этот фильтр означает "выбрать все записи, в которых значение в
	 * поле SUBSCRIPTION (флаг "Продажа контента") равно Y".<br><br> Значение по
	 * умолчанию - пустой массив array() - означает, что результат
	 * отфильтрован не будет.
	 *
	 *
	 *
	 * @param array $arGroupBy = false Массив полей, по которым группируются записи каталога. Массив
	 * имеет вид: <pre class="syntax">array("название_поля1", "группирующая_функция2"
	 * =&gt; "название_поля2", . . .)</pre> В качестве "название_поля<i>N</i>" может
	 * стоять любое поле каталога. В качестве группирующей функции
	 * могут стоять: <ul> <li> <b> COUNT</b> - подсчет количества;</li> <li> <b>AVG</b> -
	 * вычисление среднего значения;</li> <li> <b>MIN</b> - вычисление
	 * минимального значения;</li> <li> <b> MAX</b> - вычисление максимального
	 * значения;</li> <li> <b>SUM</b> - вычисление суммы.</li> </ul> Если массив пустой,
	 * то функция вернет число записей, удовлетворяющих фильтру.<br><br>
	 * Значение по умолчанию - <i>false</i> - означает, что результат
	 * группироваться не будет.
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
	 * присутствует значение "*", то будут возвращены все доступные
	 * поля.<br><br> Значение по умолчанию - пустой массив array() - означает,
	 * что будут возвращены все поля основной таблицы запроса.
	 *
	 *
	 *
	 * @return CDBResult <p>Возвращает объект класса CDBResult, содержащий коллекцию
	 * ассоциативных массивов с ключами:</p><table class="tnormal" width="100%"> <tr> <th
	 * width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код каталога.
	 * Совпадает с кодом информационного блока.</td> </tr> <tr> <td>IBLOCK_ID</td>
	 * <td>Код информационного блока.</td> </tr> <tr> <td>IBLOCK_TYPE_ID</td> <td>Тип
	 * информационного блока.</td> </tr> <tr> <td>SUBSCRIPTION</td> <td>Флаг "Продажа
	 * контента".</td> </tr> <tr> <td>NAME</td> <td>Название информационного блока.</td>
	 * </tr> <tr> <td>YANDEX_EXPORT</td> <td>флаг "экспортировать в Яндекс.Товары"</td> </tr>
	 * </table><p>Если в качестве параметра arGroupBy передается пустой массив,
	 * то функция вернет число записей, удовлетворяющих фильтру.</p>
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
				"ID" => array("FIELD" => "I.ID", "TYPE" => "int"),
				"IBLOCK_ID" => array("FIELD" => "CI.IBLOCK_ID", "TYPE" => "int"),
				"YANDEX_EXPORT" => array("FIELD" => "CI.YANDEX_EXPORT", "TYPE" => "char"),
				"SUBSCRIPTION" => array("FIELD" => "CI.SUBSCRIPTION", "TYPE" => "char"),
				"PRODUCT_IBLOCK_ID" => array("FIELD" => "CI.PRODUCT_IBLOCK_ID", "TYPE" => "int"),
				"SKU_PROPERTY_ID" => array("FIELD" => "CI.SKU_PROPERTY_ID", "TYPE" => "int"),
				"OFFERS_PROPERTY_ID" => array("FIELD" => "OFFERS.SKU_PROPERTY_ID", "TYPE" => "int"),
				"OFFERS_IBLOCK_ID" => array("FIELD" => "OFFERS.IBLOCK_ID", "TYPE" => "int"),
				"IBLOCK_TYPE_ID" => array("FIELD" => "I.IBLOCK_TYPE_ID", "TYPE" => "string"),
				"IBLOCK_ACTIVE" => array("FIELD" => "I.ACTIVE", "TYPE" => "char"),
				"LID" => array("FIELD" => "I.LID", "TYPE" => "string"),
				"NAME" => array("FIELD" => "I.NAME", "TYPE" => "string")
			);
		// <-- FIELDS

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_catalog_iblock CI
				INNER JOIN b_iblock I ON CI.IBLOCK_ID = I.ID
				LEFT JOIN b_catalog_iblock OFFERS ON CI.IBLOCK_ID = OFFERS.PRODUCT_IBLOCK_ID".
				" ".$arSqls["FROM"]." ".
				"WHERE 1=1 ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "AND ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return False;
		}

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_catalog_iblock CI
			INNER JOIN b_iblock I ON CI.IBLOCK_ID = I.ID
			LEFT JOIN b_catalog_iblock OFFERS ON CI.IBLOCK_ID = OFFERS.PRODUCT_IBLOCK_ID".
			" ".$arSqls["FROM"]." ".
			"WHERE 1=1 ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "AND ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])<=0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_catalog_iblock CI
				INNER JOIN b_iblock I ON CI.IBLOCK_ID = I.ID
				LEFT JOIN b_catalog_iblock OFFERS ON CI.IBLOCK_ID = OFFERS.PRODUCT_IBLOCK_ID".
				" ".$arSqls["FROM"]." ".
				"WHERE 1=1 ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "AND ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

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

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])>0)
				$strSql .= "LIMIT ".intval($arNavStartParams["nTopCount"]);

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}
}
?>