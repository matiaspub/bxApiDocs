<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/status.php");


/**
 * 
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalestatus/index.php
 * @author Bitrix
 */
class CSaleStatus extends CAllSaleStatus
{
	
	/**
	 * <p>Функция возвращает результат выборки записей из статусов в соответствии со своими параметрами.</p>
	 *
	 *
	 *
	 *
	 * @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	 * записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	 * "направление_сортировки1", "название_поля2" =&gt;
	 * "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	 * может стоять любое поле статусов, а в качестве
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
	 * @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи статусов.
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
	 * </ul> В качестве "название_поляX" может стоять любое поле типов
	 * плательщика.<br><br> Пример фильтра: <pre class="syntax">array("LID" =&gt; "en")</pre> Этот
	 * фильтр означает "выбрать все записи, в которых значение в поле LID
	 * (код сайта) равно en".<br><br> Значение по умолчанию - пустой массив array()
	 * - означает, что результат отфильтрован не будет.
	 *
	 *
	 *
	 * @param array $arGroupBy = false Массив полей, по которым группируются записи статусов. Массив
	 * имеет вид: <pre class="syntax">array("название_поля1", "группирующая_функция2"
	 * =&gt; "название_поля2", ...)</pre> В качестве "название_поля<i>N</i>" может
	 * стоять любое поле статусов. В качестве группирующей функции
	 * могут стоять: <ul> <li> <b> COUNT</b> - подсчет количества;</li> <li> <b>AVG</b> -
	 * вычисление среднего значения;</li> <li> <b>MIN</b> - вычисление
	 * минимального значения;</li> <li> <b> MAX</b> - вычисление максимального
	 * значения;</li> <li> <b>SUM</b> - вычисление суммы.</li> </ul> Этот фильтр
	 * означает "выбрать все записи, в которых значение в поле LID (сайт
	 * системы) не равно en".<br><br> Значение по умолчанию - <i>false</i> - означает,
	 * что результат группироваться не будет.
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
	 * @return CDBResult <p>Возвращается объект класса CDBResult, содержащий ассоциативные
	 * массивы параметров статусов с ключами:</p><table class="tnormal" width="100%"> <tr> <th
	 * width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код статуса заказа.</td>
	 * </tr> <tr> <td>SORT</td> <td>Индекс сортировки.</td> </tr> <tr> <td>LID</td> <td>Язык.</td> </tr>
	 * <tr> <td>NAME</td> <td>Название статуса.</td> </tr> <tr> <td>DESCRIPTION</td> <td>Описание
	 * статуса.</td> </tr> </table><p> Если в качестве параметра arGroupBy передается
	 * пустой массив, то функция вернет число записей, удовлетворяющих
	 * фильтру.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalestatus/csalestatus__getlist.bbf47ed5.php
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

			$arFilter = array();
			$arFilter["LID"] = LANGUAGE_ID;
			if ($arGroupBy)
			{
				$arGroupBy = strval($arGroupBy);
				if (strlen($arGroupBy) > 0)
					$arFilter["LID"] = $arGroupBy;
			}
			$arGroupBy = false;

			$arSelectFields = array("ID", "SORT", "LID", "NAME", "DESCRIPTION");
		}

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "S.ID", "TYPE" => "char"),
				"SORT" => array("FIELD" => "S.SORT", "TYPE" => "int"),
				"GROUP_ID" => array("FIELD" => "SSG.GROUP_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_sale_status2group SSG ON (S.ID = SSG.STATUS_ID)"),
				"PERM_VIEW" => array("FIELD" => "SSG.PERM_VIEW", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_status2group SSG ON (S.ID = SSG.STATUS_ID)"),
				"PERM_CANCEL" => array("FIELD" => "SSG.PERM_CANCEL", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_status2group SSG ON (S.ID = SSG.STATUS_ID)"),
				"PERM_DELIVERY" => array("FIELD" => "SSG.PERM_DELIVERY", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_status2group SSG ON (S.ID = SSG.STATUS_ID)"),
				"PERM_MARK" => array("FIELD" => "SSG.PERM_MARK", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_status2group SSG ON (S.ID = SSG.STATUS_ID)"),
				"PERM_DEDUCTION" => array("FIELD" => "SSG.PERM_DEDUCTION", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_status2group SSG ON (S.ID = SSG.STATUS_ID)"),
				"PERM_PAYMENT" => array("FIELD" => "SSG.PERM_PAYMENT", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_status2group SSG ON (S.ID = SSG.STATUS_ID)"),
				"PERM_STATUS" => array("FIELD" => "SSG.PERM_STATUS", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_status2group SSG ON (S.ID = SSG.STATUS_ID)"),
				"PERM_STATUS_FROM" => array("FIELD" => "SSG.PERM_STATUS_FROM", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_status2group SSG ON (S.ID = SSG.STATUS_ID)"),
				"PERM_UPDATE" => array("FIELD" => "SSG.PERM_UPDATE", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_status2group SSG ON (S.ID = SSG.STATUS_ID)"),
				"PERM_DELETE" => array("FIELD" => "SSG.PERM_DELETE", "TYPE" => "char", "FROM" => "LEFT JOIN b_sale_status2group SSG ON (S.ID = SSG.STATUS_ID)"),
				"LID" => array("FIELD" => "SL.LID", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_status_lang SL ON (S.ID = SL.STATUS_ID)"),
				"NAME" => array("FIELD" => "SL.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_status_lang SL ON (S.ID = SL.STATUS_ID)"),
				"DESCRIPTION" => array("FIELD" => "SL.DESCRIPTION", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_status_lang SL ON (S.ID = SL.STATUS_ID)")
			);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_status S ".
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
			"FROM b_sale_status S ".
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
				"FROM b_sale_status S ".
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
	 * <p>Функция возвращает параметры статуса с кодом ID, включая языкозависимые параметры для языка strLang </p>
	 *
	 *
	 *
	 *
	 * @param string $ID  Код статуса заказа.
	 *
	 *
	 *
	 * @param string $strLang = LANGUAGE_ID Язык, для которого возвращаются языкозависимые параметры. По
	 * умолчанию используется текущий язык.
	 *
	 *
	 *
	 * @return array <p>Возвращается ассоциативный массив параметров статуса с
	 * ключами:</p><table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th>
	 * </tr> <tr> <td>ID</td> <td>Код статуса заказа.</td> </tr> <tr> <td>SORT</td> <td>Индекс
	 * сортировки.</td> </tr> <tr> <td>LID</td> <td>Язык.</td> </tr> <tr> <td>NAME</td> <td>Название
	 * статуса.</td> </tr> <tr> <td>DESCRIPTION</td> <td>Описание статуса.</td> </tr> </table><p> 
	 * </p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if ($arStatus = CSaleStatus::GetByID($STATUS_ID))
	 * {
	 *    echo "&lt;pre&gt;";
	 *    print_r($arStatus);
	 *    echo "&lt;/pre&gt;";
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalestatus/csalestatus__getbyid.bfbe15e3.php
	 * @author Bitrix
	 */
	public static function GetByID($ID, $strLang = LANGUAGE_ID)
	{
		global $DB;

		$ID = $DB->ForSql($ID, 1);
		$strLang = $DB->ForSql($strLang, 2);
		if (isset($GLOBALS["SALE_STATUS"]["SALE_STATUS_CACHE_".$ID."_".$strLang]) && is_array($GLOBALS["SALE_STATUS"]["SALE_STATUS_CACHE_".$ID."_".$strLang]) && is_set($GLOBALS["SALE_STATUS"]["SALE_ORDER_CACHE_".$ID."_".$strLang], "ID"))
		{
			return $GLOBALS["SALE_STATUS"]["SALE_STATUS_CACHE_".$ID."_".$strLang];
		}
		else
		{

			$strSql =
				"SELECT S.ID, S.SORT, SL.LID, SL.NAME, SL.DESCRIPTION ".
				"FROM b_sale_status S ".
				"	LEFT JOIN b_sale_status_lang SL ON (S.ID = SL.STATUS_ID AND SL.LID = '".$strLang."') ".
				"WHERE ID = '".$ID."' ";
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			if ($res = $db_res->Fetch())
			{
				$GLOBALS["SALE_STATUS"]["SALE_STATUS_CACHE_".$ID."_".$strLang] = $res;
				return $res;
			}
		}
		return False;
	}


	public static function GetPermissionsList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "S.ID", "TYPE" => "int"),
				"GROUP_ID" => array("FIELD" => "S.GROUP_ID", "TYPE" => "int"),
				"STATUS_ID" => array("FIELD" => "S.STATUS_ID", "TYPE" => "char"),
				"PERM_VIEW" => array("FIELD" => "S.PERM_VIEW", "TYPE" => "char"),
				"PERM_CANCEL" => array("FIELD" => "S.PERM_CANCEL", "TYPE" => "char"),
				"PERM_MARK" => array("FIELD" => "S.PERM_MARK", "TYPE" => "char"),
				"PERM_DELIVERY" => array("FIELD" => "S.PERM_DELIVERY", "TYPE" => "char"),
				"PERM_DEDUCTION" => array("FIELD" => "S.PERM_DEDUCTION", "TYPE" => "char"),
				"PERM_PAYMENT" => array("FIELD" => "S.PERM_PAYMENT", "TYPE" => "char"),
				"PERM_STATUS" => array("FIELD" => "S.PERM_STATUS", "TYPE" => "char"),
				"PERM_STATUS_FROM" => array("FIELD" => "S.PERM_STATUS_FROM", "TYPE" => "char"),
				"PERM_UPDATE" => array("FIELD" => "S.PERM_UPDATE", "TYPE" => "char"),
				"PERM_DELETE" => array("FIELD" => "S.PERM_DELETE", "TYPE" => "char"),
			);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_status2group S ".
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
			"FROM b_sale_status2group S ".
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
				"FROM b_sale_status2group S ".
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
}
?>