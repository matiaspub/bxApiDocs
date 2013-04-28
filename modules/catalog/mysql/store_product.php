<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/store_product.php");


/**
 * <b>CCatalogStoreProduct</b> - класс для работы со остатками товара на складах.
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstoreproduct/index.php
 * @author Bitrix
 */
class CCatalogStoreProduct
	extends CCatalogStoreProductAll
{
	
	/**
	 * <p>Метод добавляет остаток товара, в соответствии с данными из массива arFields.</p>
	 *
	 *
	 *
	 *
	 * @param array $arFields  Ассоциативный массив параметров, ключами в котором являются
	 * названия параметров, а значениями - соответствующие значения.
	 * Допустимые ключи: <br><ul> <li>PRODUCT_ID - ID товара;</li> <li>STORE_ID - ID склада;</li>
	 * <li>AMOUNT - количество товара;</li> </ul>
	 *
	 *
	 *
	 * @return mixed <p>Возвращает <i>ID</i> записи, если добавление совершено, в противном
	 * случае - <i>false</i>.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * $arFields = Array(
	 * 		"PRODUCT_ID" =&gt; 71,
	 * 		"STORE_ID" =&gt; 1,
	 * 		"AMOUNT" =&gt; 50,
	 * 	);
	 * 	
	 * 	$ID = CCatalogStoreProduct::Add($arFields);
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstoreproduct/add.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB;
		if (!self::CheckFields('ADD',$arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_store_product", $arFields);
		$strSql =
			"INSERT INTO b_catalog_store_product (".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";

		$res=$DB->Query($strSql, true, "File: ".__FILE__."<br>Line: ".__LINE__);
		if(!$res)
			return false;
		$lastId = intval($DB->LastID());
		return $lastId;
	}

	
	/**
	 * <p>Функция возвращает результат выборки записей из таблицы остатков товара в соответствии со своими параметрами.</p>
	 *
	 *
	 *
	 *
	 * @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	 * записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	 * "направление_сортировки1", "название_поля2" =&gt;
	 * "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	 * может стоять любое поле, а в качестве
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
	 * @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи. Массив имеет
	 * вид: <pre class="syntax">array( "[модификатор1][оператор1]название_поля1" =&gt;
	 * "значение1", "[модификатор2][оператор2]название_поля2" =&gt; "значение2",
	 * . . . )</pre> Удовлетворяющие фильтру записи возвращаются в
	 * результате, а записи, которые не удовлетворяют условиям фильтра,
	 * отбрасываются. <br><br> Допустимыми являются следующие
	 * модификаторы: <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> - значения null, 0 и
	 * пустая строка так же удовлетворяют условиям фильтра.</li> </ul>
	 * Допустимыми являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение
	 * поля больше или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b>
	 * - значение поля строго больше передаваемой в фильтр величины;</li>
	 * <li> <b>&lt;=</b> - значение поля меньше или равно передаваемой в фильтр
	 * величины;</li> <li> <b>&lt;</b> - значение поля строго меньше передаваемой
	 * в фильтр величины;</li> <li> <b>@</b> - значение поля находится в
	 * передаваемом в фильтр разделенном запятой списке значений;</li> <li>
	 * <b>~</b> - значение поля проверяется на соответствие передаваемому в
	 * фильтр шаблону;</li> <li> <b>%</b> - значение поля проверяется на
	 * соответствие передаваемой в фильтр строке в соответствии с
	 * языком запросов.</li> </ul> В качестве "название_поляX" может стоять
	 * любое поле. <br><br> Пример фильтра: <pre class="syntax">array("ACTIVE" =&gt; "Y")</pre> Этот
	 * фильтр означает "выбрать все записи, в которых значение в поле ACTIVE
	 * (флаг "Активность склада") равно Y". <br><br> Значение по умолчанию -
	 * пустой массив array() - означает, что результат отфильтрован не
	 * будет.
	 *
	 *
	 *
	 * @param array $arGroupBy = false Массив полей, по которым группируются записи. Массив имеет вид: <pre
	 * class="syntax">array("название_поля1", "группирующая_функция2" =&gt;
	 * "название_поля2", . . .)</pre> В качестве "название_поля<i>N</i>" может
	 * стоять любое поле. В качестве группирующей функции могут стоять:
	 * <ul> <li> <b>COUNT</b> - подсчет количества;</li> <li> <b>AVG</b> - вычисление
	 * среднего значения;</li> <li> <b>MIN</b> - вычисление минимального
	 * значения;</li> <li> <b>MAX</b> - вычисление максимального значения;</li> <li>
	 * <b>SUM</b> - вычисление суммы.</li> </ul> Если массив пустой, то функция
	 * вернет число записей, удовлетворяющих фильтру. <br><br> Значение по
	 * умолчанию - <i>false</i> - означает, что результат группироваться не
	 * будет.
	 *
	 *
	 *
	 * @param array $arNavStartParams = false Массив параметров выборки. Может содержать следующие ключи: <ul>
	 * <li>"<b>nTopCount</b>" - количество возвращаемых функцией записей будет
	 * ограничено сверху значением этого ключа;</li> <li>любой ключ,
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
	 * @return mixed <p>Возвращает объект класса <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>, содержащий
	 * коллекцию ассоциативных массивов с ключами.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstoreproduct/getlist.php
	 * @author Bitrix
	 */
	static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;
		$arFields = array(
			"ID" => array("FIELD" => "CP.ID", "TYPE" => "int"),
			"PRODUCT_ID" => array("FIELD" => "CP.PRODUCT_ID", "TYPE" => "int"),
			"STORE_ID" => array("FIELD" => "CP.STORE_ID", "TYPE" => "int"),
			"AMOUNT" => array("FIELD" => "CP.AMOUNT", "TYPE" => "double"),
			"STORE_NAME" => array("FIELD" => "CS.TITLE", "TYPE" => "string", "FROM" => "RIGHT JOIN b_catalog_store CS ON (CS.ID = CP.STORE_ID)"),
			"STORE_ADDR" => array("FIELD" => "CS.ADDRESS", "TYPE" => "string", "FROM" => "RIGHT JOIN b_catalog_store CS ON (CS.ID = CP.STORE_ID)"),
			"STORE_DESCR" => array("FIELD" => "CS.DESCRIPTION", "TYPE" => "string", "FROM" => "RIGHT JOIN b_catalog_store CS ON (CS.ID = CP.STORE_ID)"),
			"STORE_GPS_N" => array("FIELD" => "CS.GPS_N", "TYPE" => "string", "FROM" => "RIGHT JOIN b_catalog_store CS ON (CS.ID = CP.STORE_ID)"),
			"STORE_GPS_S" => array("FIELD" => "CS.GPS_S", "TYPE" => "string", "FROM" => "RIGHT JOIN b_catalog_store CS ON (CS.ID = CP.STORE_ID)"),
			"STORE_IMAGE" => array("FIELD" => "CS.IMAGE_ID", "TYPE" => "int", "FROM" => "RIGHT JOIN b_catalog_store CS ON (CS.ID = CP.STORE_ID)"),
			"STORE_LOCATION" => array("FIELD" => "CS.LOCATION_ID", "TYPE" => "int", "FROM" => "RIGHT JOIN b_catalog_store CS ON (CS.ID = CP.STORE_ID)")

		);
		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);
		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
					"FROM b_catalog_store_product CP ".
					"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}
		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_catalog_store_product CP ".
				"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";


		if (is_array($arNavStartParams) && intval($arNavStartParams["nTopCount"])<=0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
					"FROM b_catalog_store_product CP ".
					"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";
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
			if (is_array($arNavStartParams) && intval($arNavStartParams["nTopCount"])>0)
				$strSql .= "LIMIT ".intval($arNavStartParams["nTopCount"]);

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return $dbRes;
	}
}