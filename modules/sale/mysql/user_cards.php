<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/user_cards.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleusercards/index.php
 * @author Bitrix
 */
class CSaleUserCards extends CAllSaleUserCards
{
	
	/**
	* <p>Метод возвращает параметры пластиковой карты с кодом ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код пластиковой карты.
	*
	* @return array <p>Метод возвращает ассоциативный массив параметров пластиковой
	* карты с ключами:</p> <ul> <li> <b>ID</b> - код пластиковой карты;</li> <li>
	* <b>USER_ID</b> - код пользователя;</li> <li> <b>SORT</b> - индекс сортировки;</li> <li>
	* <b>PAY_SYSTEM_ACTION_ID</b> - код обработчика платежной системы;</li> <li> <b>CURRENCY</b>
	* - валюта, которую можно снимать с карты;</li> <li> <b>CARD_CODE</b> - CVC2;</li> <li>
	* <b>CARD_TYPE</b> - тип карты;</li> <li> <b>CARD_NUM</b> - номер карты;</li> <li> <b>CARD_EXP_MONTH</b>
	* - месяц окончания действия карты;</li> <li> <b>CARD_EXP_YEAR</b> - год окончания
	* действия карты;</li> <li> <b>DESCRIPTION</b> - краткое описание;</li> <li> <b>SUM_MIN</b> -
	* минимальная сумма, которую можно снять с карты за раз;</li> <li>
	* <b>SUM_MAX</b> - максимальная сумма, которую можно снять с карты за
	* раз;</li> <li> <b>SUM_CURRENCY</b> - валюта минимальной и максимальной сумм;</li>
	* <li> <b>LAST_STATUS</b> - статус последнего использования карты;</li> <li>
	* <b>LAST_STATUS_CODE</b> - код статуса последнего использования карты;</li> <li>
	* <b>LAST_STATUS_DESCRIPTION</b> - описание статуса последнего использования
	* карты;</li> <li> <b>LAST_STATUS_MESSAGE</b> - сообщение платежной системы;</li> <li>
	* <b>LAST_SUM</b> - последняя снятая с карты сумма;</li> <li> <b>LAST_CURRENCY</b> -
	* валюта последней снятой с карты суммы;</li> <li> <b>ACTIVE</b> - флаг
	* активности;</li> <li> <b>TIMESTAMP_X</b> - дата изменения;</li> <li> <b>LAST_DATE</b> - дата
	* последнего использования карты.</li> </ul> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleusercards/csaleusercards.getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
			return false;

		$strSql = 
			"SELECT UC.ID, UC.USER_ID, UC.SORT, UC.PAY_SYSTEM_ACTION_ID, UC.CURRENCY, UC.CARD_CODE, ".
			"	UC.CARD_TYPE, UC.CARD_NUM, UC.CARD_EXP_MONTH, UC.CARD_EXP_YEAR, UC.DESCRIPTION, ".
			"	UC.SUM_MIN, UC.SUM_MAX, UC.SUM_CURRENCY, UC.LAST_STATUS, UC.LAST_STATUS_CODE, ".
			"	UC.LAST_STATUS_DESCRIPTION, UC.LAST_STATUS_MESSAGE, UC.LAST_SUM, ".
			"	UC.LAST_CURRENCY, UC.ACTIVE, ".
			"	".$DB->DateToCharFunction("UC.TIMESTAMP_X", "FULL")." as TIMESTAMP_X, ".
			"	".$DB->DateToCharFunction("UC.LAST_DATE", "FULL")." as LAST_DATE ".
			"FROM b_sale_user_cards UC ".
			"WHERE UC.ID = ".$ID." ";

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($res = $db_res->Fetch())
			return $res;

		return false;
	}

	
	/**
	* <p>Метод возвращает результат выборки записей пластиковых карт в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле карт, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и
	* т.д.). <br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи карт. Массив
	* имеет вид: <pre class="syntax">array( "[модификатор1][оператор1]название_поля1"
	* =&gt; "значение1", "[модификатор2][оператор2]название_поля2" =&gt;
	* "значение2", . . . )</pre> Удовлетворяющие фильтру записи возвращаются
	* в результате, а записи, которые не удовлетворяют условиям
	* фильтра, отбрасываются.<br><br> Допустимыми являются следующие
	* модификаторы: <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> - значения null, 0 и
	* пустая строка так же удовлетворяют условиям фильтра.</li> </ul>
	* Допустимыми являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение
	* поля больше или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b>
	* - значение поля строго больше передаваемой в фильтр величины;</li>
	* <li> <b>&gt;=</b> - значение поля меньше или равно передаваемой в фильтр
	* величины;</li> <li> <b>&gt;=</b> - значение поля строго меньше передаваемой
	* в фильтр величины;</li> <li> <b>@</b> - значение поля находится в
	* передаваемом в фильтр разделенном запятой списке значений;</li> <li>
	* <b>~</b> - значение поля проверяется на соответствие передаваемому в
	* фильтр шаблону;</li> <li> <b>%</b> - значение поля проверяется на
	* соответствие передаваемой в фильтр строке в соответствии с
	* языком запросов.</li> </ul> В качестве "название_поляX" может стоять
	* любое поле карт.<br><br> Пример фильтра: <pre class="syntax">array("USER_ID" =&gt; 150)</pre>
	* Этот фильтр означает "выбрать все записи, в которых значение в
	* поле USER_ID (код пользователя) равно 150".<br><br> Значение по умолчанию -
	* пустой массив array() - означает, что результат отфильтрован не
	* будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи карт. Массив имеет
	* вид: <pre class="syntax">array("название_поля1", "группирующая_функция2" =&gt;
	* "название_поля2", . . .)</pre> В качестве "название_поля<i>N</i>" может
	* стоять любое поле карт. В качестве группирующей функции могут
	* стоять: <ul> <li> <b> COUNT</b> - подсчет количества;</li> <li> <b>AVG</b> - вычисление
	* среднего значения;</li> <li> <b>MIN</b> - вычисление минимального
	* значения;</li> <li> <b> MAX</b> - вычисление максимального значения;</li> <li>
	* <b>SUM</b> - вычисление суммы.</li> </ul> Если массив пустой, то метод
	* вернет число записей, удовлетворяющих фильтру.<br><br> Значение по
	* умолчанию - <i>false</i> - означает, что результат группироваться не
	* будет.
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
	* ассоциативных массивов параметров карт.</p> <ul> <li> <b>ID</b> - код
	* пластиковой карты;</li> <li> <b>USER_ID</b> - код пользователя;</li> <li> <b>SORT</b> -
	* индекс сортировки;</li> <li> <b>PAY_SYSTEM_ACTION_ID</b> - код обработчика
	* платежной системы;</li> <li> <b>CURRENCY</b> - валюта, которую можно снимать с
	* карты;</li> <li> <b>CARD_CODE</b> - CVC2;</li> <li> <b>CARD_TYPE</b> - тип карты;</li> <li> <b>CARD_NUM</b>
	* - номер карты;</li> <li> <b>CARD_EXP_MONTH</b> - месяц окончания действия
	* карты;</li> <li> <b>CARD_EXP_YEAR</b> - год окончания действия карты;</li> <li>
	* <b>DESCRIPTION</b> - краткое описание;</li> <li> <b>SUM_MIN</b> - минимальная сумма,
	* которую можно снять с карты за раз;</li> <li> <b>SUM_MAX</b> - максимальная
	* сумма, которую можно снять с карты за раз;</li> <li> <b>SUM_CURRENCY</b> - валюта
	* минимальной и максимальной сумм;</li> <li> <b>LAST_STATUS</b> - статус
	* последнего использования карты;</li> <li> <b>LAST_STATUS_CODE</b> - код статуса
	* последнего использования карты;</li> <li> <b>LAST_STATUS_DESCRIPTION</b> - описание
	* статуса последнего использования карты;</li> <li> <b>LAST_STATUS_MESSAGE</b> -
	* сообщение платежной системы;</li> <li> <b>LAST_SUM</b> - последняя снятая с
	* карты сумма;</li> <li> <b>LAST_CURRENCY</b> - валюта последней снятой с карты
	* суммы;</li> <li> <b>ACTIVE</b> - флаг активности;</li> <li> <b>TIMESTAMP_X</b> - дата
	* изменения;</li> <li> <b>LAST_DATE</b> - дата последнего использования
	* карты.</li> </ul> <p>Если в качестве параметра arGroupBy передается пустой
	* массив, то метод вернет число записей, удовлетворяющих фильтру.</p>
	* <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleusercards/csaleusercards.getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "USER_ID", "ACTIVE", "SORT", "PAY_SYSTEM_ACTION_ID", "CURRENCY", "CARD_TYPE", "CARD_NUM", "CARD_CODE", "CARD_EXP_MONTH", "CARD_EXP_YEAR", "DESCRIPTION", "SUM_MIN", "SUM_MAX", "SUM_CURRENCY", "TIMESTAMP_X", "LAST_STATUS", "LAST_STATUS_CODE", "LAST_STATUS_DESCRIPTION", "LAST_STATUS_MESSAGE", "LAST_SUM", "LAST_CURRENCY", "LAST_DATE");

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "UC.ID", "TYPE" => "int"),
				"USER_ID" => array("FIELD" => "UC.USER_ID", "TYPE" => "int"),
				"ACTIVE" => array("FIELD" => "UC.ACTIVE", "TYPE" => "char"),
				"SORT" => array("FIELD" => "UC.SORT", "TYPE" => "int"),
				"PAY_SYSTEM_ACTION_ID" => array("FIELD" => "UC.PAY_SYSTEM_ACTION_ID", "TYPE" => "int"),
				"CURRENCY" => array("FIELD" => "UC.CURRENCY", "TYPE" => "string"),
				"CARD_TYPE" => array("FIELD" => "UC.CARD_TYPE", "TYPE" => "string"),
				"CARD_NUM" => array("FIELD" => "UC.CARD_NUM", "TYPE" => "string"),
				"CARD_CODE" => array("FIELD" => "UC.CARD_CODE", "TYPE" => "string"),
				"CARD_EXP_MONTH" => array("FIELD" => "UC.CARD_EXP_MONTH", "TYPE" => "int"),
				"CARD_EXP_YEAR" => array("FIELD" => "UC.CARD_EXP_YEAR", "TYPE" => "int"),
				"DESCRIPTION" => array("FIELD" => "UC.DESCRIPTION", "TYPE" => "string"),
				"SUM_MIN" => array("FIELD" => "UC.SUM_MIN", "TYPE" => "double"),
				"SUM_MAX" => array("FIELD" => "UC.SUM_MAX", "TYPE" => "double"),
				"SUM_CURRENCY" => array("FIELD" => "UC.SUM_CURRENCY", "TYPE" => "string"),
				"TIMESTAMP_X" => array("FIELD" => "UC.TIMESTAMP_X", "TYPE" => "datetime"),
				"LAST_STATUS" => array("FIELD" => "UC.LAST_STATUS", "TYPE" => "char"),
				"LAST_STATUS_CODE" => array("FIELD" => "UC.LAST_STATUS_CODE", "TYPE" => "string"),
				"LAST_STATUS_DESCRIPTION" => array("FIELD" => "UC.LAST_STATUS_DESCRIPTION", "TYPE" => "string"),
				"LAST_STATUS_MESSAGE" => array("FIELD" => "UC.LAST_STATUS_MESSAGE", "TYPE" => "string"),
				"LAST_SUM" => array("FIELD" => "UC.LAST_SUM", "TYPE" => "double"),
				"LAST_CURRENCY" => array("FIELD" => "UC.LAST_CURRENCY", "TYPE" => "string"),
				"LAST_DATE" => array("FIELD" => "UC.LAST_DATE", "TYPE" => "datetime"),
				"USER_LOGIN" => array("FIELD" => "U.LOGIN", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UC.USER_ID = U.ID)"),
				"USER_ACTIVE" => array("FIELD" => "U.ACTIVE", "TYPE" => "char", "FROM" => "INNER JOIN b_user U ON (UC.USER_ID = U.ID)"),
				"USER_NAME" => array("FIELD" => "U.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UC.USER_ID = U.ID)"),
				"USER_LAST_NAME" => array("FIELD" => "U.LAST_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UC.USER_ID = U.ID)"),
				"USER_EMAIL" => array("FIELD" => "U.EMAIL", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UC.USER_ID = U.ID)"),
				"USER_USER" => array("FIELD" => "U.LOGIN,U.NAME,U.LAST_NAME,U.EMAIL,U.ID", "WHERE_ONLY" => "Y", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UC.USER_ID = U.ID)")
			);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_user_cards UC ".
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
			"FROM b_sale_user_cards UC ".
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
				"FROM b_sale_user_cards UC ".
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
	* <p>Метод сохраняет информацию о новой пластиковой карте пользователя. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров пластиковой карты с ключами: <ul>
	* <li> <b>USER_ID</b> - код пользователя;</li> <li> <b>SORT</b> - индекс сортировки;</li>
	* <li> <b>PAY_SYSTEM_ACTION_ID</b> - код обработчика платежной системы;</li> <li>
	* <b>CURRENCY</b> - валюта, которую можно снимать с карты;</li> <li> <b>CARD_CODE</b> -
	* CVC2;</li> <li> <b>CARD_TYPE</b> - тип карты;</li> <li> <b>CARD_NUM</b> - номер карты;</li> <li>
	* <b>CARD_EXP_MONTH</b> - месяц окончания действия карты;</li> <li> <b>CARD_EXP_YEAR</b> -
	* год окончания действия карты;</li> <li> <b>DESCRIPTION</b> - краткое
	* описание;</li> <li> <b>SUM_MIN</b> - минимальная сумма, которую можно снять с
	* карты за раз;</li> <li> <b>SUM_MAX</b> - максимальная сумма, которую можно
	* снять с карты за раз;</li> <li> <b>SUM_CURRENCY</b> - валюта минимальной и
	* максимальной сумм;</li> <li> <b>LAST_STATUS</b> - статус последнего
	* использования карты;</li> <li> <b>LAST_STATUS_CODE</b> - код статуса последнего
	* использования карты;</li> <li> <b>LAST_STATUS_DESCRIPTION</b> - описание статуса
	* последнего использования карты;</li> <li> <b>LAST_STATUS_MESSAGE</b> - сообщение
	* платежной системы;</li> <li> <b>LAST_SUM</b> - последняя снятая с карты
	* сумма;</li> <li> <b>LAST_CURRENCY</b> - валюта последней снятой с карты суммы;</li>
	* <li> <b>ACTIVE</b> - флаг активности;</li> <li> <b>LAST_DATE</b> - дата последнего
	* использования карты.</li> </ul> <p></p> <div class="note"> <b>Замечание:</b> перед
	* добавлением записи номер карты должен быть зашифрован методом <a
	* href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleusercards/csaleusercards.cryptdata.php">CSaleUserCards::CryptData</a>.</div>
	*
	* @return int <p>Метод возвращает код добавленной записи или <i>false</i> в случае
	* ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Сохраним новую карту текущего пользователя
	* 
	* if (CSaleUserCards::CheckPassword())
	* {
	*     $arFields = array(
	*             "USER_ID" =&gt; $USER-&gt;GetID(),
	*             "ACTIVE" =&gt; "Y",
	*             "SORT" =&gt; "100",
	*             "PAY_SYSTEM_ACTION_ID" =&gt; 11,
	*             "CURRENCY" =&gt; "USD",
	*             "CARD_TYPE" =&gt; 
	*                 CSaleUserCards::IdentifyCardType("4111111111111"),
	*             "CARD_NUM" =&gt; 
	*                 CSaleUserCards::CryptData("4111111111111", "E"),
	*             "CARD_EXP_MONTH" =&gt; 11,
	*             "CARD_EXP_YEAR" =&gt; 2007,
	*             "DESCRIPTION" =&gt; <i>false</i>,
	*             "CARD_CODE" =&gt; "123",
	*             "SUM_MIN" =&gt; False,
	*             "SUM_MAX" =&gt; False,
	*             "SUM_CURRENCY" =&gt; False
	*         );
	* 
	*     $UserCardID = CSaleUserCards::Add($arFields);
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleusercards/csaleusercards.add.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		if (!CSaleUserCards::CheckFields("ADD", $arFields, 0))
			return false;

		$arInsert = $DB->PrepareInsert("b_sale_user_cards", $arFields);

		$strSql =
			"INSERT INTO b_sale_user_cards(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		return $ID;
	}

	
	/**
	* <p>Метод изменяет информацию о новой пластиковой карте пользователя. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код изменяемой записи. </htm
	*
	* @param array $arFields  Ассоциативный массив новых параметров пластиковой карты с
	* ключами: <ul> <li> <b>USER_ID</b> - код пользователя;</li> <li> <b>SORT</b> - индекс
	* сортировки;</li> <li> <b>PAY_SYSTEM_ACTION_ID</b> - код обработчика платежной
	* системы;</li> <li> <b>CURRENCY</b> - валюта, которую можно снимать с карты;</li>
	* <li> <b>CARD_CODE</b> - CVC2;</li> <li> <b>CARD_TYPE</b> - тип карты;</li> <li> <b>CARD_NUM</b> - номер
	* карты;</li> <li> <b>CARD_EXP_MONTH</b> - месяц окончания действия карты;</li> <li>
	* <b>CARD_EXP_YEAR</b> - год окончания действия карты;</li> <li> <b>DESCRIPTION</b> -
	* краткое описание;</li> <li> <b>SUM_MIN</b> - минимальная сумма, которую можно
	* снять с карты за раз;</li> <li> <b>SUM_MAX</b> - максимальная сумма, которую
	* можно снять с карты за раз;</li> <li> <b>SUM_CURRENCY</b> - валюта минимальной и
	* максимальной сумм;</li> <li> <b>LAST_STATUS</b> - статус последнего
	* использования карты;</li> <li> <b>LAST_STATUS_CODE</b> - код статуса последнего
	* использования карты;</li> <li> <b>LAST_STATUS_DESCRIPTION</b> - описание статуса
	* последнего использования карты;</li> <li> <b>LAST_STATUS_MESSAGE</b> - сообщение
	* платежной системы;</li> <li> <b>LAST_SUM</b> - последняя снятая с карты
	* сумма;</li> <li> <b>LAST_CURRENCY</b> - валюта последней снятой с карты суммы;</li>
	* <li> <b>ACTIVE</b> - флаг активности;</li> <li> <b>LAST_DATE</b> - дата последнего
	* использования карты.</li> </ul> <p></p> <div class="note"> <b>Замечание:</b> если
	* меняется номер карты, то перед добавлением записи этот номер
	* должен быть зашифрован методом <a
	* href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleusercards/csaleusercards.cryptdata.php">CSaleUserCards::CryptData</a>.</div>
	*
	* @return int <p>Метод возвращает код обновленной записи или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleusercards/csaleusercards.update.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
			return False;

		if (!CSaleUserCards::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_user_cards", $arFields);
		$strSql = "UPDATE b_sale_user_cards SET ".$strUpdate." WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}
}
?>