<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/user_transact.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleusertransact/index.php
 * @author Bitrix
 */
class CSaleUserTransact extends CAllSaleUserTransact
{
	
	/**
	* <p>Метод возвращает параметры транзакции с кодом ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Номер транзакции.
	*
	* @return array <p>Метод возвращает ассоциативный массив параметров транзакции с
	* ключами:</p> <ul> <li> <b>ID</b> - код транзакции;</li> <li> <b>USER_ID</b> - код
	* пользователя;</li> <li> <b>AMOUNT</b> - сумма;</li> <li> <b>CURRENCY</b> - валюта
	* суммы;</li> <li> <b>DEBIT</b> - "Y", если занесение денег на счет, и "N", если
	* списание денег со счета;</li> <li> <b>DESCRIPTION</b> - описание;</li> <li> <b>ORDER_ID</b>
	* - код заказа, если транзакция относится к заказу;</li> <li> <b>EMPLOYEE_ID</b> -
	* код пользователя, осуществившего транзакцию;</li> <li> <b>TIMESTAMP_X</b> -
	* дата последнего изменения записи;</li> <li> <b>TRANSACT_DATE</b> - дата
	* транзакции.</li> </ul> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleusertransact/csaleusertransact.getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
			return false;

		$strSql = 
			"SELECT UT.ID, UT.USER_ID, UT.AMOUNT, UT.CURRENCY, UT.DEBIT, UT.DESCRIPTION, ".
			"	UT.ORDER_ID, UT.NOTES, UT.EMPLOYEE_ID, ".
			"	".$DB->DateToCharFunction("UT.TIMESTAMP_X", "FULL")." as TIMESTAMP_X, ".
			"	".$DB->DateToCharFunction("UT.TRANSACT_DATE", "FULL")." as TRANSACT_DATE ".
			"FROM b_sale_user_transact UT ".
			"WHERE UT.ID = ".$ID." ";

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($res = $db_res->Fetch())
			return $res;

		return false;
	}

	
	/**
	* <p>Метод возвращает результат выборки записей транзакций в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле транзакций, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и
	* т.д.). <br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи транзакций.
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
	* транзакций.<br><br> Пример фильтра: <pre class="syntax">array("USER_ID" =&gt; 150)</pre> Этот
	* фильтр означает "выбрать все записи, в которых значение в поле
	* USER_ID (код пользователя) равно 150".<br><br> Значение по умолчанию -
	* пустой массив array() - означает, что результат отфильтрован не
	* будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи транзакций. Массив
	* имеет вид: <pre class="syntax">array("название_поля1", "группирующая_функция2"
	* =&gt; "название_поля2", . . .)</pre> В качестве "название_поля<i>N</i>" может
	* стоять любое поле транзакций. В качестве группирующей функции
	* могут стоять: <ul> <li> <b> COUNT</b> - подсчет количества;</li> <li> <b>AVG</b> -
	* вычисление среднего значения;</li> <li> <b>MIN</b> - вычисление
	* минимального значения;</li> <li> <b> MAX</b> - вычисление максимального
	* значения;</li> <li> <b>SUM</b> - вычисление суммы.</li> </ul> Если массив пустой,
	* то метод вернет число записей, удовлетворяющих фильтру.<br><br>
	* Значение по умолчанию - <i>false</i> - означает, что результат
	* группироваться не будет.
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
	* ассоциативных массивов параметров транзакций:</p> <ul> <li> <b>ID</b> - код
	* транзакции;</li> <li> <b>USER_ID</b> - код пользователя;</li> <li> <b>AMOUNT</b> -
	* сумма;</li> <li> <b>CURRENCY</b> - валюта суммы;</li> <li> <b>DEBIT</b> - "Y", если
	* занесение денег на счет, и "N", если списание денег со счета;</li> <li>
	* <b>DESCRIPTION</b> - описание;</li> <li> <b>ORDER_ID</b> - код заказа, если транзакция
	* относится к заказу;</li> <li> <b>EMPLOYEE_ID</b> - код пользователя,
	* осуществившего транзакцию;</li> <li> <b>TIMESTAMP_X</b> - дата последнего
	* изменения записи;</li> <li> <b>TRANSACT_DATE</b> - дата транзакции;</li> <li>
	* <b>USER_LOGIN</b> - логин пользователя;</li> <li> <b>USER_ACTIVE</b> - флаг активности
	* пользователя;</li> <li> <b>USER_NAME</b> - имя пользователя;</li> <li> <b>USER_LAST_NAME</b>
	* - фамилия пользователя;</li> <li> <b>USER_EMAIL</b> - E-Mail пользователя.</li> </ul>
	* <p>Если в качестве параметра arGroupBy передается пустой массив, то
	* метод вернет число записей, удовлетворяющих фильтру.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* //пример формирования отчета по транзакциям личного счета клиента 
	* &lt;table cellpadding="0" cellspacing="0" border="0" class="data-table"&gt;
	*     &lt;thead&gt;
	*         &lt;tr&gt;
	*             &lt;td&gt;№&lt;/td&gt;
	*             &lt;td&gt;Дата транзакции&lt;/td&gt;
	*             &lt;td&gt;Сумма&lt;/td&gt;
	*             &lt;td&gt;Описание&lt;/td&gt;
	*         &lt;/tr&gt;
	*     &lt;/thead&gt;
	*     &lt;tbody&gt;
	*     &lt;?
	*     CModule::IncludeModule("sale");
	*     $res = CSaleUserTransact::GetList(Array("ID" =&gt; "DESC"), array("USER_ID" =&gt; $USER-&gt;GetID()));
	*     while ($arFields = $res-&gt;Fetch())
	*     {?&gt;
	*         &lt;tr&gt;
	*             &lt;td&gt;&lt;?=$arFields["ID"]?&gt;&lt;/td&gt;
	*             &lt;td&gt;&lt;?=$arFields["TRANSACT_DATE"]?&gt;&lt;/td&gt;
	*             &lt;td&gt;&lt;?=($arFields["DEBIT"]=="Y")?"+":"-"?&gt;&lt;?=SaleFormatCurrency($arFields["AMOUNT"], $arFields["CURRENCY"])?&gt;&lt;br /&gt;&lt;small&gt;(&lt;?=($arFields["DEBIT"]=="Y")?"на счет":"со счета"?&gt;)&lt;/small&gt;&lt;/td&gt;
	*             &lt;td&gt;&lt;?=$arFields["NOTES"]?&gt;&lt;/td&gt;
	*         &lt;/tr&gt;
	*     &lt;?}?&gt;
	*     &lt;tbody&gt;
	* &lt;/table&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleusertransact/csaleusertransact.getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "USER_ID", "TIMESTAMP_X", "TRANSACT_DATE", "AMOUNT", "CURRENCY", "DEBIT", "ORDER_ID", "DESCRIPTION", "NOTES");

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "UT.ID", "TYPE" => "int"),
				"USER_ID" => array("FIELD" => "UT.USER_ID", "TYPE" => "int"),
				"AMOUNT" => array("FIELD" => "UT.AMOUNT", "TYPE" => "double"),
				"CURRENCY" => array("FIELD" => "UT.CURRENCY", "TYPE" => "string"),
				"DEBIT" => array("FIELD" => "UT.DEBIT", "TYPE" => "char"),
				"ORDER_ID" => array("FIELD" => "UT.ORDER_ID", "TYPE" => "int"),
				"DESCRIPTION" => array("FIELD" => "UT.DESCRIPTION", "TYPE" => "string"),
				"NOTES" => array("FIELD" => "UT.NOTES", "TYPE" => "string"),
				"TIMESTAMP_X" => array("FIELD" => "UT.TIMESTAMP_X", "TYPE" => "datetime"),
				"TRANSACT_DATE" => array("FIELD" => "UT.TRANSACT_DATE", "TYPE" => "datetime"),
				"EMPLOYEE_ID" => array("FIELD" => "UT.EMPLOYEE_ID", "TYPE" => "int"),
				"USER_LOGIN" => array("FIELD" => "U.LOGIN", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UT.USER_ID = U.ID)"),
				"USER_ACTIVE" => array("FIELD" => "U.ACTIVE", "TYPE" => "char", "FROM" => "INNER JOIN b_user U ON (UT.USER_ID = U.ID)"),
				"USER_NAME" => array("FIELD" => "U.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UT.USER_ID = U.ID)"),
				"USER_LAST_NAME" => array("FIELD" => "U.LAST_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UT.USER_ID = U.ID)"),
				"USER_EMAIL" => array("FIELD" => "U.EMAIL", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UT.USER_ID = U.ID)"),
				"USER_USER" => array("FIELD" => "U.LOGIN,U.NAME,U.LAST_NAME,U.EMAIL,U.ID", "WHERE_ONLY" => "Y", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UT.USER_ID = U.ID)")
			);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_user_transact UT ".
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
			"FROM b_sale_user_transact UT ".
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
				"FROM b_sale_user_transact UT ".
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
	* <p>Метод добавляет новую транзакцию с параметрами из массива arFields. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров новой транзакции с ключами: <ul>
	* <li> <b>USER_ID</b> - код пользователя;</li> <li> <b>AMOUNT</b> - сумма;</li> <li> <b>CURRENCY</b> -
	* валюта суммы;</li> <li> <b>DEBIT</b> - "Y", если занесение денег на счет, и "N",
	* если списание денег со счета;</li> <li> <b>DESCRIPTION</b> - описание;</li> <li>
	* <b>ORDER_ID</b> - код заказа, если транзакция относится к заказу;</li> <li>
	* <b>EMPLOYEE_ID</b> - код пользователя, осуществившего транзакцию;</li> <li>
	* <b>TRANSACT_DATE</b> - дата транзакции.</li> </ul>
	*
	* @return int <p>Метод возвращает код вставленной транзакции или <i>false</i> в
	* случае ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleusertransact/csaleusertransact.add.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1)=="=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CSaleUserTransact::CheckFields("ADD", $arFields, 0))
			return false;

		$arInsert = $DB->PrepareInsert("b_sale_user_transact", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($arInsert[0])>0) $arInsert[0] .= ", ";
			$arInsert[0] .= $key;
			if (strlen($arInsert[1])>0) $arInsert[1] .= ", ";
			$arInsert[1] .= $value;
		}

		$strSql =
			"INSERT INTO b_sale_user_transact(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		return $ID;
	}

	
	/**
	* <p>Метод изменяет параметры транзакции в соответствии с параметрами из массива arFields. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код изменяемой транзакции.
	*
	* @param array $arFields  Ассоциативный массив новых параметров новой транзакции с
	* ключами: <ul> <li> <b> USER_ID</b> - код пользователя;</li> <li> <b> AMOUNT</b> - сумма;</li>
	* <li> <b> CURRENCY</b> - валюта суммы;</li> <li> <b> DEBIT</b> - "Y", если занесение денег
	* на счет, и "N", если списание денег со счета;</li> <li> <b> DESCRIPTION</b> -
	* описание;</li> <li> <b> ORDER_ID</b> - код заказа, если транзакция относится к
	* заказу;</li> <li> <b> EMPLOYEE_ID</b> - код пользователя, осуществившего
	* транзакцию;</li> <li> <b> TRANSACT_DATE</b> - дата транзакции.</li> </ul>
	*
	* @return int <p>Метод возвращает код измененной транзакции или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleusertransact/csaleusertransact.update.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
			return False;

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1)=="=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CSaleUserTransact::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_user_transact", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate)>0) $strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		$strSql = "UPDATE b_sale_user_transact SET ".$strUpdate." WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}
}
?>