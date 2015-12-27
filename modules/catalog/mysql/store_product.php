<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/store_product.php");


/**
 * <b>CCatalogStoreProduct</b> - класс для работы со остатками товара на складах. 
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
	* <p>Метод добавляет остаток товара, в соответствии с данными из массива arFields. Метод статический.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров, ключами в котором являются
	* названия параметров, а значениями - соответствующие значения.
	* Допустимые ключи: <br><ul> <li>PRODUCT_ID - ID товара;</li> <li>STORE_ID - ID склада;</li>
	* <li>AMOUNT - количество товара;</li> </ul>
	*
	* @return mixed <p>Возвращает <i>ID</i> записи, если добавление совершено, в противном
	* случае - <i>false</i>.</p> <a name="examples"></a>
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

		foreach(GetModuleEvents("catalog", "OnBeforeStoreProductAdd", true) as $arEvent)
			if(ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				return false;

		if (!self::CheckFields('ADD',$arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_store_product", $arFields);
		$strSql = "INSERT INTO b_catalog_store_product (".$arInsert[0].") VALUES(".$arInsert[1].")";

		$res = $DB->Query($strSql, true, "File: ".__FILE__."<br>Line: ".__LINE__);
		if(!$res)
			return false;

		$lastId = intval($DB->LastID());

		foreach(GetModuleEvents("catalog", "OnStoreProductAdd", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($lastId, $arFields));

		return $lastId;
	}

	
	/**
	* <p>Метод возвращает результат выборки записей из таблицы остатков товара в соответствии со своими параметрами. Метод статический.</p>
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
	* в фильтр величины;</li> <li> <b>@</b> - оператор может использоваться для
	* целочисленных и вещественных данных при передаче набора
	* значений (массива). В этом случае при генерации sql-запроса будет
	* использован sql-оператор <b>IN</b>, дающий компактную форму записи;</li>
	* <li> <b>~</b> - значение поля проверяется на соответствие
	* передаваемому в фильтр шаблону;</li> <li> <b>%</b> - значение поля
	* проверяется на соответствие передаваемой в фильтр строке в
	* соответствии с языком запросов.</li> </ul> В качестве "название_поляX"
	* может стоять любое поле. <br><br> Пример фильтра: <pre class="syntax">array("ACTIVE"
	* =&gt; "Y")</pre> Этот фильтр означает "выбрать все записи, в которых
	* значение в поле ACTIVE (флаг "Активность склада") равно Y". <br><br>
	* Значение по умолчанию - пустой массив array() - означает, что
	* результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи. Массив имеет вид: <pre
	* class="syntax">array("название_поля1", "название_поля2", . . .)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле. <br><br> Если массив
	* пустой, то метод вернет число записей, удовлетворяющих фильтру.
	* <br><br> Значение по умолчанию - <i>false</i> - означает, что результат
	* группироваться не будет.
	*
	* @param array $arNavStartParams = false Массив параметров выборки. Может содержать следующие ключи: <ul>
	* <li>"<b>nTopCount</b>" - количество возвращаемых методом записей будет
	* ограничено сверху значением этого ключа;</li> <li>любой ключ,
	* принимаемый методом <b> CDBResult::NavQuery</b> в качестве третьего
	* параметра.</li> </ul> Значение по умолчанию - <i>false</i> - означает, что
	* параметров выборки нет.
	*
	* @param array $arSelectFields = array() Массив полей записей, которые будут возвращены методом. Можно
	* указать только те поля, которые необходимы. Если в массиве
	* присутствует значение "*", то будут возвращены все доступные поля.
	* <br><br> Значение по умолчанию - пустой массив array() - означает, что
	* будут возвращены все поля основной таблицы запроса.
	*
	* @return mixed <p>Возвращает объект класса <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>, содержащий
	* коллекцию ассоциативных массивов с ключами.</p> <br><br>
	*
	* <h4>Example</h4> 
	* <pre>
	* Список возможных полей
	* </htm<tbody>
	* <tr>
	* <th width="15%">Поля</th>
	* <th width="404">Описание</th> </tr>
	* <tr>
	* <td><b>ID</b></td> 	<td> ID записи</td> </tr>
	* <tr>
	* <td><b>PRODUCT_ID</b></td> 	<td>ID  элемента</td> </tr>
	* <tr>
	* <td><b>AMOUNT</b></td> 	<td>количество</td> </tr>
	* <tr>
	* <td><b>STORE_ID</b></td> 	<td>ID склада</td> </tr>
	* <tr>
	* <td><b>STORE_NAME</b></td> 	<td>название склада</td> </tr>
	* <tr>
	* <td><b>STORE_ADDR</b></td> 	<td>адрес склада</td> </tr>
	* <tr>
	* <td><b>STORE_DESCR</b></td> 	<td>описание склада</td> </tr>
	* <tr>
	* <td><b>STORE_GPS_N</b></td> 	<td>широта</td> </tr>
	* <tr>
	* <td><b>STORE_GPS_S</b></td> 	<td>долгота</td> </tr>
	* <tr>
	* <td><b>STORE_IMAGE</b></td> 	<td>картинка склада</td> </tr>
	* </tbody>
	* </pre>
	*
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
			"STORE_LOCATION" => array("FIELD" => "CS.LOCATION_ID", "TYPE" => "int", "FROM" => "RIGHT JOIN b_catalog_store CS ON (CS.ID = CP.STORE_ID)"),
			"STORE_PHONE" => array("FIELD" => "CS.PHONE", "TYPE" => "string", "FROM" => "RIGHT JOIN b_catalog_store CS ON (CS.ID = CP.STORE_ID)")
		);
		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);
		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_store_product CP ".$arSqls["FROM"];
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
		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_store_product CP ".$arSqls["FROM"];
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
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_store_product CP ".$arSqls["FROM"];
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