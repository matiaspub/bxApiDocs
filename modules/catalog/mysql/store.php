<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/store.php");


/**
 * <b>CCatalogStore</b> - класс для работы со складами. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/index.php
 * @author Bitrix
 */
class CCatalogStore
	extends CAllCatalogStore
{
	/** Add new store in table b_catalog_store,
	 * @static
	 * @param $arFields
	 * @return bool|int
	 */
	
	/**
	* <p>Метод добавляет новый склад, в соответствии с данными из массива arFields. Метод статический.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров нового склада, ключами в котором
	* являются названия параметров, а значениями - соответствующие
	* значения. Допустимые ключи: <br><ul> <li>TITLE - название склада;</li> <li>ACTIVE -
	* активность склада('Y' - активен, 'N' - не активен);</li> <li>ADDRESS - адрес
	* склада;</li> <li>DESCRIPTION - описание склада;</li> <li>GPS_N - GPS
	* координата(широта);</li> <li>GPS_S - GPS координата(долгота);</li> <li>IMAGE_ID - ID
	* картинки склада;</li> <li>PHONE - телефон;</li> <li>SCHEDULE - расписание работы
	* склада (максимальный размер поля 255 символов);</li> <li>XML_ID - XML_ID
	* склада для экспорта\импорта из 1С;</li> </ul>
	*
	* @return int <p>Возвращает <i>ID</i> вновь созданного склада, если добавление
	* совершено, в противном случае - <i>false</i>.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* $arFields = Array(
	* 		"TITLE" =&gt; $TITLE,
	* 		"ACTIVE" =&gt; $ACTIVE,
	* 		"ADDRESS" =&gt; $ADDRESS,
	* 		"DESCRIPTION" =&gt; $DESCRIPTION,
	* 		"IMAGE_ID" =&gt; $fid,
	* 		"GPS_N" =&gt; $GPS_N,
	* 		"GPS_S" =&gt; $GPS_S,
	* 		"PHONE" =&gt; $PHONE,
	* 		"SCHEDULE" =&gt; $SCHEDULE,
	* 		"XML_ID" =&gt; $XML_ID,
	* 	);
	* 	
	* 	$ID = CCatalogStore::Add($arFields);
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/add.php
	* @author Bitrix
	*/
	static function Add($arFields)
	{
		/** @global CDataBase $DB */

		global $DB;

		if(!CBXFeatures::IsFeatureEnabled('CatMultiStore'))
		{
			$dbResultList = CCatalogStore::GetList(array(), array(), false, array('NAV_PARAMS' => array("nTopCount" => "1")), array("ID"));
			if($arResult = $dbResultList->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CS_ALREADY_HAVE_STORE"));
				return false;
			}
		}

		foreach (GetModuleEvents("catalog", "OnBeforeCatalogStoreAdd", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				return false;
		}

		if(array_key_exists('DATE_CREATE', $arFields))
			unset($arFields['DATE_CREATE']);
		if(array_key_exists('DATE_MODIFY', $arFields))
			unset($arFields['DATE_MODIFY']);

		$arFields['~DATE_MODIFY'] = $DB->GetNowFunction();
		$arFields['~DATE_CREATE'] = $DB->GetNowFunction();

		if(!self::CheckFields('ADD',$arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_store", $arFields);

		$strSql = "INSERT INTO b_catalog_store (".$arInsert[0].") VALUES(".$arInsert[1].")";

		$res = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
		if(!$res)
			return false;
		$lastId = intval($DB->LastID());

		foreach(GetModuleEvents("catalog", "OnCatalogStoreAdd", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($lastId, $arFields));

		return $lastId;
	}

	
	/**
	* <p>Метод возвращает результат выборки записей из таблицы складов в соответствии со своими параметрами. Метод статический.</p>
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
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogstore/getlist.php
	* @author Bitrix
	*/
	static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;
		
		if (empty($arSelectFields))
			$arSelectFields = array(
				"ID",
				"ACTIVE",
				"TITLE",
				"PHONE",
				"SCHEDULE",
				"ADDRESS",
				"DESCRIPTION",
				"GPS_N",
				"GPS_S",
				"IMAGE_ID",
				"DATE_CREATE",
				"DATE_MODIFY",
				"USER_ID",
				"XML_ID",
				"SORT",
				"EMAIL",
				"ISSUING_CENTER",
				"SHIPPING_CENTER",
				"SITE_ID"
				/*, "BASE"*/
			);

		$keyForDelete = array_search("PRODUCT_AMOUNT", $arSelectFields);

		if (!isset($arFilter["PRODUCT_ID"]) && $keyForDelete !== false)
			unset($arSelectFields[$keyForDelete]);

		if ($keyForDelete == false)
		{
			$keyForDelete = array_search("ELEMENT_ID", $arSelectFields);
			if($keyForDelete !== false)
				unset($arSelectFields[$keyForDelete]);
		}
		$productID = '(';

		if (is_array($arFilter["PRODUCT_ID"]))
		{
			foreach($arFilter["PRODUCT_ID"] as $id)
				$productID .= intval($id).',';
			$productID = rtrim($productID, ',').')';
		}
		else
		{
			$productID .= intval($arFilter["PRODUCT_ID"]) . ')';
		}

		$arFields = array(
			"ID" => array("FIELD" => "CS.ID", "TYPE" => "int"),
			"ACTIVE" => array("FIELD" => "CS.ACTIVE", "TYPE" => "string"),
			"TITLE" => array("FIELD" => "CS.TITLE", "TYPE" => "string"),
			"PHONE" => array("FIELD" => "CS.PHONE", "TYPE" => "string"),
			"SCHEDULE" => array("FIELD" => "CS.SCHEDULE", "TYPE" => "string"),
			"ADDRESS" => array("FIELD" => "CS.ADDRESS", "TYPE" => "string"),
			"DESCRIPTION" => array("FIELD" => "CS.DESCRIPTION", "TYPE" => "string"),
			"GPS_N" => array("FIELD" => "CS.GPS_N", "TYPE" => "string"),
			"GPS_S" => array("FIELD" => "CS.GPS_S", "TYPE" => "string"),
			"IMAGE_ID" => array("FIELD" => "CS.IMAGE_ID", "TYPE" => "int"),
			"LOCATION_ID" => array("FIELD" => "CS.LOCATION_ID", "TYPE" => "int"),
			"DATE_CREATE" => array("FIELD" => "CS.DATE_CREATE", "TYPE" => "datetime"),
			"DATE_MODIFY" => array("FIELD" => "CS.DATE_MODIFY", "TYPE" => "datetime"),
			"USER_ID" => array("FIELD" => "CS.USER_ID", "TYPE" => "int"),
			"MODIFIED_BY" => array("FIELD" => "CS.MODIFIED_BY", "TYPE" => "int"),
			"XML_ID" => array("FIELD" => "CS.XML_ID", "TYPE" => "string"),
			"SORT" => array("FIELD" => "CS.SORT", "TYPE" => "int"),
			"EMAIL" => array("FIELD" => "CS.EMAIL", "TYPE" => "string"),
			"ISSUING_CENTER" => array("FIELD" => "CS.ISSUING_CENTER", "TYPE" => "char"),
			"SHIPPING_CENTER" => array("FIELD" => "CS.SHIPPING_CENTER", "TYPE" => "char"),
			"SITE_ID" => array("FIELD" => "CS.SITE_ID", "TYPE" => "string"),
			"PRODUCT_AMOUNT" => array("FIELD" => "CP.AMOUNT", "TYPE" => "double", "FROM" => "LEFT JOIN b_catalog_store_product CP ON (CS.ID = CP.STORE_ID AND CP.PRODUCT_ID IN ".$productID.")"),
			"ELEMENT_ID" => array("FIELD" => "CP.PRODUCT_ID", "TYPE" => "int")
		);

		$userField = new CUserTypeSQL();
		$userField->SetEntity("CAT_STORE", "CS.ID");
		$userField->SetSelect($arSelectFields);
		$userField->SetFilter($arFilter);
		$userField->SetOrder($arOrder);

		$strUfFilter = $userField->GetFilter();
		$strSqlUfFilter = (strlen($strUfFilter) > 0) ? " (".$strUfFilter.") " : "";


		$strSqlUfOrder = "";
		foreach ($arOrder as $field => $by)
		{
			$field = $userField->GetOrder($field);
			if (empty($field))
				continue;

			if (strlen($strSqlUfOrder) > 0)
				$strSqlUfOrder .= ', ';
			$strSqlUfOrder .= $field." ".$by;
		}


		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);
		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." ".$userField->GetSelect()." FROM b_catalog_store CS ".$arSqls["FROM"]. " ".$userField->GetJoin("CS.ID");
			if (!empty($arSqls["WHERE"]))
				$strSql .= " WHERE ".$arSqls["WHERE"]." ";

			if (strlen($arSqls["WHERE"]) > 0 && strlen($strSqlUfFilter) > 0)
				$strSql .= " AND ".$strSqlUfFilter." ";
			elseif (strlen($arSqls["WHERE"]) == 0 && strlen($strSqlUfFilter) > 0)
				$strSql .= " WHERE ".$strSqlUfFilter." ";


			if (!empty($arSqls["GROUPBY"]))
				$strSql .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}
		$strSql = "SELECT ".$arSqls["SELECT"]." ".$userField->GetSelect()." FROM b_catalog_store CS ".$arSqls["FROM"]." ".$userField->GetJoin("CS.ID");
		if (!empty($arSqls["WHERE"]))
			$strSql .= " WHERE ".$arSqls["WHERE"]." ";

		if (strlen($arSqls["WHERE"]) > 0 && strlen($strSqlUfFilter) > 0)
			$strSql .= " AND ".$strSqlUfFilter." ";
		elseif (strlen($arSqls["WHERE"]) <= 0 && strlen($strSqlUfFilter) > 0)
			$strSql .= " WHERE ".$strSqlUfFilter." ";

		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " GROUP BY ".$arSqls["GROUPBY"];

		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " ORDER BY ".$arSqls["ORDERBY"];
		elseif (strlen($arSqls["ORDERBY"]) <= 0 && strlen($strSqlUfOrder) > 0)
			$strSql .= " ORDER BY ".$strSqlUfOrder;


		$intTopCount = 0;
		$boolNavStartParams = (!empty($arNavStartParams) && is_array($arNavStartParams));
		if ($boolNavStartParams && array_key_exists('nTopCount', $arNavStartParams))
			$intTopCount = intval($arNavStartParams["nTopCount"]);

		if ($boolNavStartParams && 0 >= $intTopCount)
		{
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_store CS ".$arSqls["FROM"]. " ".$userField->GetJoin("CS.ID");
			if (!empty($arSqls["WHERE"]))
				$strSql_tmp .= " WHERE ".$arSqls["WHERE"];

			if (strlen($arSqls["WHERE"]) > 0 && strlen($strSqlUfFilter) > 0)
				$strSql_tmp .= " AND ".$strSqlUfFilter." ";
			elseif (strlen($arSqls["WHERE"]) <= 0 && strlen($strSqlUfFilter) > 0)
				$strSql_tmp .= " WHERE ".$strSqlUfFilter." ";

			if (!empty($arSqls["GROUPBY"]))
				$strSql_tmp .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$cnt = 0;
			if (empty($arSqls["GROUPBY"]))
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			else
				$cnt = $dbRes->SelectedRowsCount();

			$dbRes = new CDBResult();

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if($boolNavStartParams && 0 < $intTopCount)
				$strSql .= " LIMIT ".$intTopCount;

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}
}