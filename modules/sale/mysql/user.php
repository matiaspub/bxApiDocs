<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/user.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleuseraccount/index.php
 * @author Bitrix
 */
class CSaleUserAccount extends CAllSaleUserAccount
{
	//********** SELECT **************//
	
	/**
	* <p>Метод возвращает ассоциативный массив параметров счета с кодом ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код счета.</bod
	*
	* @return array <p>Метод возвращает ассоциативный массив параметров счета с
	* ключами:</p> <ul> <li> <b>ID</b> - код счета;</li> <li> <b>USER_ID</b> - код
	* пользователя-владельца;</li> <li> <b>CURRENT_BUDGET</b> - текущая сумма на
	* счете;</li> <li> <b>CURRENCY</b> - валюта;</li> <li> <b>NOTES</b> - текстовое описание;</li>
	* <li> <b>LOCKED</b> - флаг заблокированности счета;</li> <li> <b>TIMESTAMP_X</b> - дата
	* последнего изменения;</li> <li> <b>DATE_LOCKED</b> - дата блокировки счета.</li>
	* </ul> <p></p><div class="note"> <b>Примечание:</b> результат выполнения метода
	* кешируется в рамках страницы, поэтому повторный вызов метода на
	* одной странице не влечет за собой дополнительных обращений к
	* базе данных.</div> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;? if ($ar = CSaleUserAccount::GetByID(5)) 
	* { echo "На счете ".SaleFormatCurrency($ar["CURRENT_BUDGET"], 
	*                                       $ar["CURRENCY"]); } ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleuseraccount/csaleuseraccount.getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
			return false;

		if (isset($GLOBALS["SALE_USER_ACCOUNT"]["SALE_USER_ACCOUNT_CACHE_".$ID]) && is_array($GLOBALS["SALE_USER_ACCOUNT"]["SALE_USER_ACCOUNT_CACHE_".$ID]) && is_set($GLOBALS["SALE_USER_ACCOUNT"]["SALE_USER_ACCOUNT_CACHE_".$ID], "ID"))
		{
			return $GLOBALS["SALE_USER_ACCOUNT"]["SALE_USER_ACCOUNT_CACHE_".$ID];
		}
		else
		{
			$strSql = 
				"SELECT UA.ID, UA.USER_ID, UA.CURRENT_BUDGET, UA.CURRENCY, UA.NOTES, UA.LOCKED, ".
				"	".$DB->DateToCharFunction("UA.TIMESTAMP_X", "FULL")." as TIMESTAMP_X, ".
				"	".$DB->DateToCharFunction("UA.DATE_LOCKED", "FULL")." as DATE_LOCKED ".
				"FROM b_sale_user_account UA ".
				"WHERE UA.ID = ".$ID." ";

			$dbUserAccount = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arUserAccount = $dbUserAccount->Fetch())
			{
				$GLOBALS["SALE_USER_ACCOUNT"]["SALE_USER_ACCOUNT_CACHE_".$ID] = $arUserAccount;
				return $arUserAccount;
			}
		}

		return false;
	}

	
	/**
	* <p>Метод возвращает ассоциативный массив параметров счета с валютой currency для пользователя с кодом userID. Метод динамичный.</p>
	*
	*
	* @param int $userID  Код пользователя. </h
	*
	* @param string $currency  Валюта счета.
	*
	* @return array <p>Метод возвращает ассоциативный массив параметров счета с
	* ключами:</p> <ul> <li> <b> ID</b> - код счета;</li> <li> <b> USER_ID</b> - код
	* пользователя-владельца;</li> <li> <b> CURRENT_BUDGET</b> - текущая сумма на
	* счете;</li> <li> <b> CURRENCY</b> - валюта;</li> <li> <b> NOTES</b> - текстовое
	* описание;</li> <li> <b> LOCKED</b> - флаг заблокированности счета;</li> <li> <b>
	* TIMESTAMP_X</b> - дата последнего изменения;</li> <li> <b> DATE_LOCKED</b> - дата
	* блокировки счета.</li> </ul> <p></p><div class="note"> <b>Примечание:</b> результат
	* выполнения метода кешируется в рамках страницы, поэтому
	* повторный вызов метода на одной странице не влечет за собой
	* дополнительных обращений к базе данных.</div> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;? if ($ar = CSaleUserAccount::GetByUserID(172, "USD")) 
	*  { echo "На счете ".SaleFormatCurrency($ar["CURRENT_BUDGET"], $ar["CURRENCY"]); } ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleuseraccount/csaleuseraccount.getbyuserid.php
	* @author Bitrix
	*/
	public static function GetByUserID($userID, $currency)
	{
		global $DB;

		$userID = IntVal($userID);
		if ($userID <= 0)
			return false;

		$currency = Trim($currency);
		$currency = preg_replace("#[\W]+#", "", $currency);
		if (strlen($currency) <= 0)
			return false;

		if (isset($GLOBALS["SALE_USER_ACCOUNT"]["SALE_USER_ACCOUNT_CACHE_".$userID."_".$currency]) && is_array($GLOBALS["SALE_USER_ACCOUNT"]["SALE_USER_ACCOUNT_CACHE_".$userID."_".$currency]) && is_set($GLOBALS["SALE_USER_ACCOUNT"]["SALE_USER_ACCOUNT_CACHE_".$userID."_".$currency], "ID"))
		{
			return $GLOBALS["SALE_USER_ACCOUNT"]["SALE_USER_ACCOUNT_CACHE_".$userID."_".$currency];
		}
		else
		{
			$strSql = 
				"SELECT UA.ID, UA.USER_ID, UA.CURRENT_BUDGET, UA.CURRENCY, UA.NOTES, UA.LOCKED, ".
				"	".$DB->DateToCharFunction("UA.TIMESTAMP_X", "FULL")." as TIMESTAMP_X, ".
				"	".$DB->DateToCharFunction("UA.DATE_LOCKED", "FULL")." as DATE_LOCKED ".
				"FROM b_sale_user_account UA ".
				"WHERE UA.USER_ID = ".$userID." ".
				"	AND UA.CURRENCY = '".$DB->ForSql($currency)."' ";

			$dbUserAccount = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arUserAccount = $dbUserAccount->Fetch())
			{
				$GLOBALS["SALE_USER_ACCOUNT"]["SALE_USER_ACCOUNT_CACHE_".$userID."_".$currency] = $arUserAccount;
				return $arUserAccount;
			}
		}

		return false;
	}

	
	/**
	* <p>Метод возвращает результат выборки записей счетов в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле счетов, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и
	* т.д.). <br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи счетов.
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
	* заказов.<br><br> Пример фильтра: <pre class="syntax">array("USER_ID" =&gt; 150)</pre> Этот
	* фильтр означает "выбрать все записи, в которых значение в поле
	* USER_ID (код пользователя) равно 150".<br><br> Значение по умолчанию -
	* пустой массив array() - означает, что результат отфильтрован не
	* будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи счетов. Массив имеет
	* вид: <pre class="syntax">array("название_поля1", "группирующая_функция2" =&gt;
	* "название_поля2", ...)</pre> В качестве "название_поля<i>N</i>" может стоять
	* любое поле счетов. В качестве группирующей функции могут стоять:
	* <ul> <li> <b> COUNT</b> - подсчет количества;</li> <li> <b>AVG</b> - вычисление
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
	* ассоциативных массивов параметров счетов:</p> <ul> <li> <b>ID</b> - код
	* счета;</li> <li> <b>USER_ID</b> - код пользователя-владельца;</li> <li>
	* <b>CURRENT_BUDGET</b> - текущая сумма на счете;</li> <li> <b>CURRENCY</b> - валюта;</li> <li>
	* <b>NOTES</b> - текстовое описание;</li> <li> <b>LOCKED</b> - флаг
	* заблокированности счета;</li> <li> <b>TIMESTAMP_X</b> - дата последнего
	* изменения;</li> <li> <b>DATE_LOCKED</b> - дата блокировки счета Если в качестве
	* параметра arGroupBy передается пустой массив, то метод вернет число
	* записей, удовлетворяющих фильтру.</li> </ul> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выберем все счета (в разных валютах) пользователя с кодом 21
	* $dbAccountCurrency = CSaleUserAccount::GetList(
	*         array(),
	*         array("USER_ID" =&gt; "21"),
	*         false,
	*         false,
	*         array("CURRENT_BUDGET", "CURRENCY")
	*     );
	* while ($arAccountCurrency = $dbAccountCurrency-&gt;Fetch())
	* {
	*     echo "На счете ".$arAccountCurrency["CURRENCY"].": ";
	*     echo SaleFormatCurrency($arAccountCurrency["CURRENT_BUDGET"],
	*                             $arAccountCurrency["CURRENCY"])."&lt;br&gt;";
	* }
	* 
	* // Выберем, сумму счетов покупателей (сколько должен магазин покупателям)
	* $dbAccountCurrency = CSaleUserAccount::GetList(
	*         array("CURRENCY" =&gt; "ASC"),
	*         array(),
	*         array("CURRENCY", "SUM" =&gt; "CURRENT_BUDGET"),
	*         false,
	*         array("CURRENCY", "SUM" =&gt; "CURRENT_BUDGET")
	*     );
	* while ($arAccountCurrency = $dbAccountCurrency-&gt;Fetch())
	* {
	*     echo "В валюте ".$arAccountCurrency["CURRENCY"].": ";
	*     echo SaleFormatCurrency($arAccountCurrency["CURRENT_BUDGET"],
	*                             $arAccountCurrency["CURRENCY"])."&lt;br&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleuseraccount/csaleuseraccount.getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "USER_ID", "CURRENT_BUDGET", "CURRENCY", "LOCKED", "NOTES", "TIMESTAMP_X", "DATE_LOCKED");

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "UA.ID", "TYPE" => "int"),
				"USER_ID" => array("FIELD" => "UA.USER_ID", "TYPE" => "int"),
				"CURRENT_BUDGET" => array("FIELD" => "UA.CURRENT_BUDGET", "TYPE" => "double"),
				"CURRENCY" => array("FIELD" => "UA.CURRENCY", "TYPE" => "string"),
				"LOCKED" => array("FIELD" => "UA.LOCKED", "TYPE" => "char"),
				"NOTES" => array("FIELD" => "UA.NOTES", "TYPE" => "string"),
				"TIMESTAMP_X" => array("FIELD" => "UA.TIMESTAMP_X", "TYPE" => "datetime"),
				"DATE_LOCKED" => array("FIELD" => "UA.DATE_LOCKED", "TYPE" => "datetime"),
				"USER_LOGIN" => array("FIELD" => "U.LOGIN", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UA.USER_ID = U.ID)"),
				"USER_ACTIVE" => array("FIELD" => "U.ACTIVE", "TYPE" => "char", "FROM" => "INNER JOIN b_user U ON (UA.USER_ID = U.ID)"),
				"USER_NAME" => array("FIELD" => "U.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UA.USER_ID = U.ID)"),
				"USER_LAST_NAME" => array("FIELD" => "U.LAST_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UA.USER_ID = U.ID)"),
				"USER_EMAIL" => array("FIELD" => "U.EMAIL", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UA.USER_ID = U.ID)"),
				"USER_USER" => array("FIELD" => "U.LOGIN,U.NAME,U.LAST_NAME,U.EMAIL,U.ID", "WHERE_ONLY" => "Y", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (UA.USER_ID = U.ID)")
			);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_user_account UA ".
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
			"FROM b_sale_user_account UA ".
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
				"FROM b_sale_user_account UA ".
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
	* <p>Метод добавляет новый счет пользователя в соответствии с параметрами из массива arFields. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров нового счета. Может содержать
	* следующие ключи:  <ul> <li> <b>USER_ID</b> - код пользователя-владельца </li>
	* <li> <b>CURRENT_BUDGET</b> - текущая сумма на счете </li> <li> <b>CURRENCY</b> - валюта </li>
	* <li> <b>NOTES</b> - текстовое описание </li> <li> <b>LOCKED</b> - флаг
	* заблокированности счета </li> <li> <b>DATE_LOCKED</b> - дата блокировки
	* счета</li> </ul>
	*
	* @return int <p>Метод возвращает код добавленного счета или <i>false</i> в случае
	* ошибки.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* if($USER-&gt;IsAuthorized())
	* {
	* $user_id = $USER-&gt;GetID();
	* $arFields = Array("USER_ID" =&gt; $user_id, "CURRENCY" =&gt; "USD", "CURRENT_BUDGET" =&gt; 0);
	* $accountID = CSaleUserAccount::Add($arFields);
	* }
	* Создание счета для текущего пользователя
	* 
	* 
	* if(!CSaleUserAccount::GetByUserID($USER-&gt;GetID(), "RUB")){
	*    $arFields = Array("USER_ID" =&gt; $USER-&gt;GetID(), "CURRENCY" =&gt; "RUB", "CURRENT_BUDGET" =&gt; 0);
	*    CSaleUserAccount::Add($arFields);  
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleuseraccount/csaleuseraccount.add.php
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

		if (!CSaleUserAccount::CheckFields("ADD", $arFields, 0))
			return false;

		$dbEvents = GetModuleEvents("sale", "OnBeforeUserAccountAdd");
		while ($arEvent = $dbEvents->Fetch())
		{
			if (ExecuteModuleEventEx($arEvent, Array(&$arFields))===false)
			{
				return false;
			}
		}

		$arInsert = $DB->PrepareInsert("b_sale_user_account", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($arInsert[0])>0) $arInsert[0] .= ", ";
			$arInsert[0] .= $key;
			if (strlen($arInsert[1])>0) $arInsert[1] .= ", ";
			$arInsert[1] .= $value;
		}

		$strSql =
			"INSERT INTO b_sale_user_account(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());
		$_SESSION["SALE_BASKET_NUM_PRODUCTS"][SITE_ID] = 0;

		$dbEvents = GetModuleEvents("sale", "OnAfterUserAccountAdd");
		while ($arEvent = $dbEvents->Fetch())
		{
			ExecuteModuleEventEx($arEvent, Array($ID, $arFields));
		}

		return $ID;
	}

	
	/**
	* <p>Метод изменяет параметры счета пользователя в соответствии с параметрами из массива arFields. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код изменяемого счета. </htm
	*
	* @param array $arFields  Ассоциативный массив новых параметров счета. Может содержать
	* следующие ключи: <ul> <li> <b>USER_ID</b> - код пользователя-владельца;</li> <li>
	* <b>CURRENT_BUDGET</b> - текущая сумма на счете;</li> <li> <b>CURRENCY</b> - валюта;</li> <li>
	* <b>NOTES</b> - текстовое описание;</li> <li> <b>LOCKED</b> - флаг
	* заблокированности счета;</li> <li> <b>DATE_LOCKED</b> - дата блокировки
	* счета.</li> </ul>
	*
	* @return int <p>Метод возвращает код измененного счета или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleuseraccount/csaleuseraccount.update.php
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

		if (!CSaleUserAccount::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$dbEvents = GetModuleEvents("sale", "OnBeforeUserAccountUpdate");
		while ($arEvent = $dbEvents->Fetch())
		{
			if (ExecuteModuleEventEx($arEvent, Array($ID, &$arFields))===false)
			{
				return false;
			}
		}

		$arOldUserAccount = CSaleUserAccount::GetByID($ID);

		$strUpdate = $DB->PrepareUpdate("b_sale_user_account", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate)>0) $strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		$strSql = "UPDATE b_sale_user_account SET ".$strUpdate." WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		unset($GLOBALS["SALE_USER_ACCOUNT"]["SALE_USER_ACCOUNT_CACHE_".$ID]);
		unset($GLOBALS["SALE_USER_ACCOUNT"]["SALE_USER_ACCOUNT_CACHE1_".$arOldUserAccount["USER_ID"]."_".$arOldUserAccount["CURRENCY"]]);

		$dbEvents = GetModuleEvents("sale", "OnAfterUserAccountUpdate");
		while ($arEvent = $dbEvents->Fetch())
		{
			ExecuteModuleEventEx($arEvent, Array($ID, $arFields));
		}

		return $ID;
	}
}
?>