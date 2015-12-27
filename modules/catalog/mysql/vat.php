<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/vat.php");


/**
 * <b>CCatalogVat</b> - класс для работы со ставками НДС. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/index.php
 * @author Bitrix
 */
class CCatalogVat extends CAllCatalogVat
{
	
	/**
	* <p>Метод добавляет новую ставку НДС в соответствии с данными из массива <i>arFields</i>. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров новой ставки НДС. Допустимые
	* ключи: <ul> <li>ACTIVE - активность ставки НДС ('Y' - активна, 'N' -
	* неактивна);</li> <li>SORT - индекс сортировки (до версии 12.5.6
	* использовалось поле C_SORT);</li> <li>NAME - название ставки НДС
	* (обязательное поле);</li> <li>RATE - величина ставки НДС (обязательное
	* поле).</li> </ul>
	*
	* @return int <p>Возвращает <i>ID</i> созданной ставки НДС или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/add.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		if (!CCatalogVat::CheckFields('ADD', $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_vat", $arFields);

		$strSql = "INSERT INTO b_catalog_vat(".$arInsert[0].") VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ID = intval($DB->LastID());

		return $ID;
	}

	
	/**
	* <p>Метод изменяет параметры ставки НДС с кодом <i>ID</i> в соответствии с данными из массива <i>arFields</i>. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код ставки НДС.
	*
	* @param array $arFields  Ассоциативный массив параметров ставки НДС. Допустимые ключи: <ul>
	* <li>ACTIVE - активность ставки НДС ('Y' - активна, 'N' - неактивна);</li> <li>SORT -
	* индекс сортировки (до версии 12.5.6 использовалось поле C_SORT);</li> <li>NAME
	* - название ставки НДС;</li> <li>RATE - величина ставки НДС.</li> </ul>
	*
	* @return mixed <p>Метод возвращает код измененной записи или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/update.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);
		if (0 >= $ID)
			return false;

		if (!CCatalogVat::CheckFields('UPDATE', $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_catalog_vat", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "UPDATE b_catalog_vat SET ".$strUpdate." WHERE ID = ".$ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $ID;
	}

	
	/**
	* <p>Метод удаляет ставку НДС с кодом <i>ID</i>. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код ставки НДС.
	*
	* @return bool <p>Возвращает <i>true</i> в случае успешного удаления и <i>false</i> - в
	* противном случае.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;
		$ID = intval($ID);
		if (0 >= $ID)
			return false;
		$DB->Query("DELETE FROM b_catalog_vat WHERE ID=".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return true;
	}

	
	/**
	* <p>Метод возвращает результат выборки записей из таблицы ставок НДС в соответствии со своими параметрами. Метод динамичный.</p>
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
	* может стоять любое поле. <br><br> Значение по умолчанию - пустой
	* массив array() - означает, что результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи. Массив имеет вид: <pre
	* class="syntax">array("название_поля1", "название_поля2", . . .)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле. Если массив пустой,
	* то метод вернет число записей, удовлетворяющих фильтру. <br><br>
	* Значение по умолчанию - <i>false</i> - означает, что результат
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
	* @return CDBResult <p>Возвращает объект класса <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>, содержащий
	* коллекцию ассоциативных массивов с ключами.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogvat/getlistex.php
	* @author Bitrix
	*/
	public static function GetListEx($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (empty($arSelectFields))
			$arSelectFields = array('ID', 'TIMESTAMP_X', 'ACTIVE', 'C_SORT', 'NAME', 'RATE');

		$arFields = array(
			'ID' => array("FIELD" => "CV.ID", "TYPE" => "int"),
			'TIMESTAMP_X' => array("FIELD" => "CV.TIMESTAMP_X", "TYPE" => "datetime"),
			'ACTIVE' => array("FIELD" => "CV.ACTIVE", "TYPE" => "char"),
			'C_SORT' => array("FIELD" => "CV.C_SORT", "TYPE" => "int"),
			'SORT' => array("FIELD" => "CV.C_SORT", "TYPE" => "int"),
			'NAME' => array("FIELD" => "CV.NAME", "TYPE" => "string"),
			'RATE' => array("FIELD" => "CV.RATE", "TYPE" => "double"),
		);

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_vat CV ".$arSqls["FROM"];
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

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_vat CV ".$arSqls["FROM"];
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
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_vat CV ".$arSqls["FROM"];
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