<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/recurring.php");

/***********************************************************************/
/***********  CSaleRecurring  ******************************************/
/***********************************************************************/

/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalerecurring/index.php
 * @author Bitrix
 */
class CSaleRecurring extends CAllSaleRecurring
{
	
	/**
	* <p>Метод возвращает параметры информации о продлении с кодом ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код записи с информацией о продлении.
	*
	* @return array <p>Метод возвращает ассоциативный массив параметров информации о
	* продлении с ключами:</p> <ul> <li> <b>ID</b> - код записи;</li> <li> <b>USER_ID</b> - код
	* пользователя;</li> <li> <b>MODULE</b> - модуль, товар которого
	* продлевается;</li> <li> <b>PRODUCT_ID</b> - код продлеваемого товара;</li> <li>
	* <b>PRODUCT_NAME</b> - название продлеваемого товара;</li> <li> <b>PRODUCT_URL</b> -
	* ссылка на продлеваемый товар;</li> <li> <b>RECUR_SCHEME_TYPE</b> - тип периода
	* оплаты;</li> <li> <b>RECUR_SCHEME_LENGTH</b> - длина периода оплаты;</li> <li>
	* <b>WITHOUT_ORDER</b> - флаг "Без оформления заказа";</li> <li> <b>ORDER_ID</b> - код
	* базового заказа для продления;</li> <li> <b>CANCELED</b> - флаг отмены
	* продления;</li> <li> <b>DESCRIPTION</b> - описание;</li> <li> <b>CALLBACK_FUNC</b> - функция
	* обратного вызова для обновления параметров продления;</li> <li>
	* <b>REMAINING_ATTEMPTS</b> - количество оставшихся попыток осуществления
	* продления;</li> <li> <b>SUCCESS_PAYMENT</b> - успешное осуществление
	* продления;</li> <li> <b>CANCELED_REASON</b> - причина отмены;</li> <li> <b>DATE_CANCELED</b> -
	* дата отмены;</li> <li> <b>PRIOR_DATE</b> - дата последнего продления;</li> <li>
	* <b>NEXT_DATE</b> - дата очередного продления;</li> <li> <b>TIMESTAMP_X</b> - дата
	* последнего изменения записи.</li> </ul> <p></p><div class="note"> <b>Примечание:</b>
	* результат выполнения метода кешируется в рамках одной страницы.
	* Поэтому повторные вызовы метода не влекут за собой
	* дополнительных запросов к базе данных.</div> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalerecurring/csalerecurring.getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		if (isset($GLOBALS["SALE_RECURRING"]["SALE_RECURRING_CACHE_".$ID]) && is_array($GLOBALS["SALE_RECURRING"]["SALE_RECURRING_CACHE_".$ID]) && is_set($GLOBALS["SALE_RECURRING"]["SALE_RECURRING_CACHE_".$ID], "ID"))
		{
			return $GLOBALS["SALE_RECURRING"]["SALE_RECURRING_CACHE_".$ID];
		}
		else
		{
			$strSql =
				"SELECT SR.ID, SR.USER_ID, SR.MODULE, SR.PRODUCT_ID, SR.PRODUCT_NAME, ".
				"	SR.PRODUCT_URL, SR.PRODUCT_PRICE_ID, SR.RECUR_SCHEME_TYPE, ".
				"	SR.RECUR_SCHEME_LENGTH, SR.WITHOUT_ORDER, SR.PRICE, SR.CURRENCY, SR.ORDER_ID, ".
				"	SR.CANCELED, SR.DESCRIPTION, SR.CALLBACK_FUNC, SR.PRODUCT_PROVIDER_CLASS, ".
				"	SR.REMAINING_ATTEMPTS, SR.SUCCESS_PAYMENT, SR.CANCELED_REASON, ".
				"	".$DB->DateToCharFunction("SR.TIMESTAMP_X", "FULL")." as TIMESTAMP_X, ".
				"	".$DB->DateToCharFunction("SR.DATE_CANCELED", "FULL")." as DATE_CANCELED, ".
				"	".$DB->DateToCharFunction("SR.PRIOR_DATE", "FULL")." as PRIOR_DATE, ".
				"	".$DB->DateToCharFunction("SR.NEXT_DATE", "FULL")." as NEXT_DATE ".
				"FROM b_sale_recurring SR WHERE SR.ID = ".$ID;

			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($res = $db_res->Fetch())
			{
				$GLOBALS["SALE_RECURRING"]["SALE_RECURRING_CACHE_".$ID] = $res;
				return $res;
			}
		}

		return false;
	}

	
	/**
	* <p>Метод возвращает результат выборки записей информации и продлении подписки в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле информации о продлении подписки, а в
	* качестве "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>"
	* (по возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив
	* сортировки имеет несколько элементов, то результирующий набор
	* сортируется последовательно по каждому элементу (т.е. сначала
	* сортируется по первому элементу, потом результат сортируется по
	* второму и т.д.). <br><br> Значение по умолчанию - пустой массив array() -
	* означает, что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи информации о
	* продлении подписки. Массив имеет вид: <pre class="syntax">array(
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
	* фильтр разделенном запятой списке значений. Можно передавать и
	* массив обычный, стандартно.</li> <li> <b>~</b> - значение поля проверяется
	* на соответствие передаваемому в фильтр шаблону;</li> <li> <b>%</b> -
	* значение поля проверяется на соответствие передаваемой в фильтр
	* строке в соответствии с языком запросов.</li> </ul> В качестве
	* "название_поляX" может стоять любое поле информации о продлении
	* подписки.<br><br> Пример фильтра: <pre class="syntax">array("USER_ID" =&gt; 150)</pre> Этот
	* фильтр означает "выбрать все записи, в которых значение в поле
	* USER_ID (код пользователя) равно 150".<br><br> Значение по умолчанию -
	* пустой массив array() - означает, что результат отфильтрован не
	* будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи информации о
	* продлении подписки. Массив имеет вид: <pre
	* class="syntax">array("название_поля1", "группирующая_функция2" =&gt;
	* "название_поля2", . . .)</pre> В качестве "название_поля<i>N</i>" может
	* стоять любое поле информации о продлении подписки. В качестве
	* группирующей функции могут стоять: <ul> <li> <b> COUNT</b> - подсчет
	* количества;</li> <li> <b>AVG</b> - вычисление среднего значения;</li> <li>
	* <b>MIN</b> - вычисление минимального значения;</li> <li> <b> MAX</b> -
	* вычисление максимального значения;</li> <li> <b>SUM</b> - вычисление
	* суммы.</li> </ul> Если массив пустой, то метод вернет число записей,
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
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий набор
	* ассоциативных массивов параметров информации и продлении
	* подписки.</p> <ul> <li> <b>ID</b> - код записи;</li> <li> <b>USER_ID</b> - код
	* пользователя;</li> <li> <b>MODULE</b> - модуль, товар которого
	* продлевается;</li> <li> <b>PRODUCT_ID</b> - код продлеваемого товара;</li> <li>
	* <b>PRODUCT_NAME</b> - название продлеваемого товара;</li> <li> <b>PRODUCT_URL</b> -
	* ссылка на продлеваемый товар;</li> <li> <b>RECUR_SCHEME_TYPE</b> - тип периода
	* оплаты;</li> <li> <b>RECUR_SCHEME_LENGTH</b> - длина периода оплаты;</li> <li>
	* <b>WITHOUT_ORDER</b> - флаг "Без оформления заказа";</li> <li> <b>ORDER_ID</b> - код
	* базового заказа для продления;</li> <li> <b>CANCELED</b> - флаг отмены
	* продления;</li> <li> <b>DESCRIPTION</b> - описание;</li> <li> <b>CALLBACK_FUNC</b> - функция
	* обратного вызова для обновления параметров продления;</li> <li>
	* <b>REMAINING_ATTEMPTS</b> - количество оставшихся попыток осуществления
	* продления;</li> <li> <b>SUCCESS_PAYMENT</b> - успешное осуществление
	* продления;</li> <li> <b>CANCELED_REASON</b> - причина отмены;</li> <li> <b>DATE_CANCELED</b> -
	* дата отмены;</li> <li> <b>PRIOR_DATE</b> - дата последнего продления;</li> <li>
	* <b>NEXT_DATE</b> - дата очередного продления;</li> <li> <b>TIMESTAMP_X</b> - дата
	* последнего изменения записи;</li> <li> <b>USER_LOGIN</b> - логин
	* пользователя;</li> <li> <b>USER_ACTIVE</b> - флаг активности пользователя;</li>
	* <li> <b>USER_NAME</b> - имя пользователя;</li> <li> <b>USER_LAST_NAME</b> - фамилия
	* пользователя;</li> <li> <b>USER_EMAIL</b> - E-Mail пользователя.</li> </ul> <p>Если в
	* качестве параметра <i> arGroupBy</i> передается пустой массив, то метод
	* вернет число записей, удовлетворяющих фильтру.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalerecurring/csalerecurring.getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (empty($arSelectFields))
			$arSelectFields = array("ID", "USER_ID", "MODULE", "PRODUCT_ID", "PRODUCT_NAME", "PRODUCT_URL", "PRODUCT_PRICE_ID", "RECUR_SCHEME_TYPE", "RECUR_SCHEME_LENGTH", "WITHOUT_ORDER", "PRICE", "CURRENCY", "ORDER_ID", "CANCELED", "DATE_CANCELED", "CANCELED_REASON", "CALLBACK_FUNC", "PRODUCT_PROVIDER_CLASS", "DESCRIPTION", "TIMESTAMP_X", "PRIOR_DATE", "NEXT_DATE", "REMAINING_ATTEMPTS", "SUCCESS_PAYMENT");

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "SR.ID", "TYPE" => "int"),
				"USER_ID" => array("FIELD" => "SR.USER_ID", "TYPE" => "int"),
				"MODULE" => array("FIELD" => "SR.MODULE", "TYPE" => "string"),
				"PRODUCT_ID" => array("FIELD" => "SR.PRODUCT_ID", "TYPE" => "int"),
				"PRODUCT_NAME" => array("FIELD" => "SR.PRODUCT_NAME", "TYPE" => "string"),
				"PRODUCT_URL" => array("FIELD" => "SR.PRODUCT_URL", "TYPE" => "string"),
				"PRODUCT_PRICE_ID" => array("FIELD" => "SR.PRODUCT_PRICE_ID", "TYPE" => "int"),
				"RECUR_SCHEME_TYPE" => array("FIELD" => "SR.RECUR_SCHEME_TYPE", "TYPE" => "char"),
				"RECUR_SCHEME_LENGTH" => array("FIELD" => "SR.RECUR_SCHEME_LENGTH", "TYPE" => "int"),
				"WITHOUT_ORDER" => array("FIELD" => "SR.WITHOUT_ORDER", "TYPE" => "char"),
				"PRICE" => array("FIELD" => "SR.PRICE", "TYPE" => "double"),
				"CURRENCY" => array("FIELD" => "SR.CURRENCY", "TYPE" => "string"),
				"ORDER_ID" => array("FIELD" => "SR.ORDER_ID", "TYPE" => "int"),
				"CANCELED" => array("FIELD" => "SR.CANCELED", "TYPE" => "char"),
				"DATE_CANCELED" => array("FIELD" => "SR.DATE_CANCELED", "TYPE" => "datetime"),
				"CANCELED_REASON" => array("FIELD" => "SR.CANCELED_REASON", "TYPE" => "string"),
				"CALLBACK_FUNC" => array("FIELD" => "SR.CALLBACK_FUNC", "TYPE" => "string"),
				"PRODUCT_PROVIDER_CLASS" => array("FIELD" => "SR.PRODUCT_PROVIDER_CLASS", "TYPE" => "string"),
				"DESCRIPTION" => array("FIELD" => "SR.DESCRIPTION", "TYPE" => "string"),
				"TIMESTAMP_X" => array("FIELD" => "SR.TIMESTAMP_X", "TYPE" => "datetime"),
				"PRIOR_DATE" => array("FIELD" => "SR.PRIOR_DATE", "TYPE" => "datetime"),
				"NEXT_DATE" => array("FIELD" => "SR.NEXT_DATE", "TYPE" => "datetime"),
				"REMAINING_ATTEMPTS" => array("FIELD" => "SR.REMAINING_ATTEMPTS", "TYPE" => "int"),
				"SUCCESS_PAYMENT" => array("FIELD" => "SR.SUCCESS_PAYMENT", "TYPE" => "char"),
				"USER_LOGIN" => array("FIELD" => "U.LOGIN", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (SR.USER_ID = U.ID)"),
				"USER_ACTIVE" => array("FIELD" => "U.ACTIVE", "TYPE" => "char", "FROM" => "INNER JOIN b_user U ON (SR.USER_ID = U.ID)"),
				"USER_NAME" => array("FIELD" => "U.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (SR.USER_ID = U.ID)"),
				"USER_LAST_NAME" => array("FIELD" => "U.LAST_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (SR.USER_ID = U.ID)"),
				"USER_EMAIL" => array("FIELD" => "U.EMAIL", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (SR.USER_ID = U.ID)"),
				"USER_USER" => array("FIELD" => "U.LOGIN,U.NAME,U.LAST_NAME,U.EMAIL,U.ID", "WHERE_ONLY" => "Y", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (SR.USER_ID = U.ID)")
			);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_recurring SR ".
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
			"FROM b_sale_recurring SR ".
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
				"FROM b_sale_recurring SR ".
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
	* <p>Метод добавляет новую запись на продление подписки в соответствии с параметрами из массива arFields. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров новой записи продления подписки
	* с ключами: <ul> <li> <b>USER_ID</b> - код пользователя;</li> <li> <b>MODULE</b> - модуль,
	* товар которого продлевается;</li> <li> <b>PRODUCT_ID</b> - код продлеваемого
	* товара;</li> <li> <b>PRODUCT_NAME</b> - название продлеваемого товара;</li> <li>
	* <b>PRODUCT_URL</b> - ссылка на продлеваемый товар;</li> <li> <b>RECUR_SCHEME_TYPE</b> - тип
	* периода оплаты;</li> <li> <b>RECUR_SCHEME_LENGTH</b> - длина периода оплаты;</li> <li>
	* <b>WITHOUT_ORDER</b> - флаг "Без оформления заказа";</li> <li> <b>ORDER_ID</b> - код
	* базового заказа для продления;</li> <li> <b>CANCELED</b> - флаг отмены
	* продления;</li> <li> <b>DESCRIPTION</b> - описание;</li> <li> <b>CALLBACK_FUNC</b> - функция
	* обратного вызова для обновления параметров продления;</li> <li>
	* <b>REMAINING_ATTEMPTS</b> - количество оставшихся попыток осуществления
	* продления;</li> <li> <b>SUCCESS_PAYMENT</b> - успешное осуществление
	* продления;</li> <li> <b>CANCELED_REASON</b> - причина отмены;</li> <li> <b>DATE_CANCELED</b> -
	* дата отмены;</li> <li> <b>PRIOR_DATE</b> - дата последнего продления;</li> <li>
	* <b>NEXT_DATE</b> - дата очередного продления.</li> </ul>
	*
	* @return int <p>Метод возвращает код добавленной записи или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalerecurring/csalerecurring.add.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		if (!CSaleRecurring::CheckFields("ADD", $arFields, 0))
			return false;

		$arInsert = $DB->PrepareInsert("b_sale_recurring", $arFields);

		$strSql =
			"INSERT INTO b_sale_recurring(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		return $ID;
	}
}
?>