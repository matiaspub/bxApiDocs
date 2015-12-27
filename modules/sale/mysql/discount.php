<?
use Bitrix\Sale\Internals;

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/sale/general/discount.php');


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalediscount/index.php
 * @author Bitrix
 */
class CSaleDiscount extends CAllSaleDiscount
{
	
	/**
	* <p>Метод добавляет новую скидку на сумму заказа с параметрами из массива <i> arFields</i>. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров скидки, ключами в котором
	* являются названия параметров скидки, а значениями - значения
	* параметров.<br><br> Допустимые ключи: <ul> <li> <b>LID</b> - код сайта, к
	* которому привязана эта скидка;</li> <li> <b>PRICE_FROM</b> - общая стоимость
	* заказа, начиная с которой предоставляется эта скидка;</li> <li>
	* <b>PRICE_TO</b> - общая стоимость заказа, до достижения которой
	* предоставляется эта скидка;</li> <li> <b>CURRENCY</b> - валюта денежных полей
	* в записи;</li> <li> <b>DISCOUNT_VALUE</b> - величина скидки;</li> <li> <b>DISCOUNT_TYPE</b> -
	* тип величины скидки (P - величина задана в процентах, V - величина
	* задана в абсолютной сумме);</li> <li> <b>ACTIVE</b> - флаг (Y/N) активности
	* скидки;</li> <li> <b>SORT</b> - индекс сортировки (если по сумме заказа
	* доступно несколько скидок, то берется первая по сортировке)</li> </ul>
	*
	* @return int <p>Возвращается код добавленной скидки или <i>false</i> в случае ошибки.
	* </p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalediscount/csalediscount__add.php
	* @author Bitrix
	*/
	static public function Add($arFields)
	{
		global $DB;

		$boolNewVersion = true;
		if (!array_key_exists('CONDITIONS', $arFields) && !array_key_exists('ACTIONS', $arFields))
		{
			$boolConvert = CSaleDiscount::__ConvertOldFormat('ADD', $arFields);
			if (!$boolConvert)
				return false;
			$boolNewVersion = false;
		}

		if (!CSaleDiscount::CheckFields("ADD", $arFields))
			return false;

		if ($boolNewVersion)
		{
			$boolConvert = CSaleDiscount::__SetOldFields('ADD', $arFields);
			if (!$boolConvert)
				return false;
		}

		$arInsert = $DB->PrepareInsert("b_sale_discount", $arFields);

		$strSql = "insert into b_sale_discount(".$arInsert[0].") values(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = (int)$DB->LastID();

		if ($ID > 0)
		{
			Internals\DiscountGroupTable::updateByDiscount($ID, $arFields['USER_GROUPS'], $arFields['ACTIVE'], true);
			if (isset($arFields['HANDLERS']))
				self::updateDiscountHandlers($ID, $arFields['HANDLERS'], false);
			if (isset($arFields['ENTITIES']))
				Internals\DiscountEntitiesTable::updateByDiscount($ID, $arFields['ENTITIES'], false);
		}

		return $ID;
	}

	
	/**
	* <p>Метод обновляет параметры скидки с кодом ID на параметры из массива arFields. Метод динамичный. </p>
	*
	*
	* @param int $ID  Код скидки.
	*
	* @param array $arFields  Ассоциативный массив новых параметров скидки, ключами в котором
	* являются названия параметров, а значениями - новые значения.
	* Допустимо указание не всех ключей, а только тех, значения которых
	* необходимо изменить.<br> Допустимые ключи:<br><ul> <li> <b>LID</b> - код сайта,
	* к которому привязана эта скидка;</li> <li> <b>PRICE_FROM</b> - общая стоимость
	* заказа, начиная с которой предоставляется эта скидка;</li> <li>
	* <b>PRICE_TO</b> - общая стоимость заказа, до достижения которой
	* предоставляется эта скидка;</li> <li> <b>CURRENCY</b> - валюта денежных полей
	* в записи;</li> <li> <b>DISCOUNT_VALUE</b> - величина скидки;</li> <li> <b>DISCOUNT_TYPE</b> -
	* тип величины скидки (P - величина задана в процентах, V - величина
	* задана в абсолютной сумме);</li> <li> <b>ACTIVE</b> - флаг (Y/N) активности
	* скидки;</li> <li> <b>SORT</b> - индекс сортировки (если по сумме заказа
	* доступно несколько скидок, то берется первая по сортировке)</li> </ul>
	*
	* @return int <p>Возвращается код измененной скидки или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalediscount/csalediscount__update.700e1b34.php
	* @author Bitrix
	*/
	static public function Update($ID, $arFields)
	{
		global $DB;

		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		$boolNewVersion = true;
		$arFields['ID'] = $ID;
		if (!array_key_exists('CONDITIONS', $arFields) && !array_key_exists('ACTIONS', $arFields))
		{
			$boolConvert = CSaleDiscount::__ConvertOldFormat('UPDATE', $arFields);
			if (!$boolConvert)
				return false;
			$boolNewVersion = false;
		}

		if (!CSaleDiscount::CheckFields("UPDATE", $arFields))
			return false;

		if ($boolNewVersion)
		{
			$boolConvert = CSaleDiscount::__SetOldFields('UPDATE', $arFields);
			if (!$boolConvert)
				return false;
		}

		$strUpdate = $DB->PrepareUpdate("b_sale_discount", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "update b_sale_discount set ".$strUpdate." where ID = ".$ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		if (isset($arFields['USER_GROUPS']))
		{
			Internals\DiscountGroupTable::updateByDiscount($ID, $arFields['USER_GROUPS'], (isset($arFields['ACTIVE']) ? $arFields['ACTIVE'] : ''), true);
		}
		elseif (isset($arFields['ACTIVE']))
		{
			Internals\DiscountGroupTable::changeActiveByDiscount($ID, $arFields['ACTIVE']);
		}
		if (isset($arFields['HANDLERS']))
			self::updateDiscountHandlers($ID, $arFields['HANDLERS'], true);
		if (isset($arFields['ENTITIES']))
			Internals\DiscountEntitiesTable::updateByDiscount($ID, $arFields['ENTITIES'], true);

		return $ID;
	}

	
	/**
	* <p>Метод возвращает результат выборки записей из скидок на заказ в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле корзины, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и
	* т.д.). <br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи скидки.
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
	* поля строго больше передаваемой в фильтр величины;</li> <li><b> -
	* значение поля меньше или равно передаваемой в фильтр
	* величины;</b></li> <li><b> - значение поля строго меньше передаваемой в
	* фильтр величины;</b></li> <li> <b>@</b> - значение поля находится в
	* передаваемом в фильтр разделенном запятой списке значений;</li> <li>
	* <b>~</b> - значение поля проверяется на соответствие передаваемому в
	* фильтр шаблону;</li> <li> <b>%</b> - значение поля проверяется на
	* соответствие передаваемой в фильтр строке в соответствии с
	* языком запросов.</li> </ul> В качестве "название_поляX" может стоять
	* любое поле корзины.<br><br> Пример фильтра: <pre class="syntax">array("!CURRENCY" =&gt;
	* "USD")</pre> Этот фильтр означает "выбрать все записи, в которых
	* значение в поле CURRENCY (валюта) не равно USD".<br><br> Значение по
	* умолчанию - пустой массив array() - означает, что результат
	* отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи скидок. Массив имеет
	* вид: <pre class="syntax">array("название_поля1", "группирующая_функция2" =&gt;
	* "название_поля2", ...)</pre> В качестве "название_поля<i>N</i>" может стоять
	* любое поле служб доставки. В качестве группирующей функции могут
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
	* ассоциативных массивов с ключами:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код скидки.</td> </tr> <tr>
	* <td>LID</td> <td>Код сайта, к которому привязана эта скидка.</td> </tr> <tr>
	* <td>PRICE_FROM</td> <td>Общая стоимость заказа, начиная с которой
	* предоставляется эта скидка.</td> </tr> <tr> <td>PRICE_TO</td> <td>Общая стоимость
	* заказа, до достижения которой предоставляется эта скидка.</td> </tr>
	* <tr> <td>CURRENCY</td> <td>Валюта денежных полей в записи.</td> </tr> <tr>
	* <td>DISCOUNT_VALUE</td> <td>Величина скидки.</td> </tr> <tr> <td>DISCOUNT_TYPE</td> <td>Тип
	* величины скидки (P - величина задана в процентах, V - величина
	* задана в абсолютной сумме).</td> </tr> <tr> <td>ACTIVE</td> <td>Флаг (Y/N)
	* активности скидки.</td> </tr> <tr> <td>SORT</td> <td>Индекс сортировки (если по
	* сумме заказа доступно несколько скидок, то берется первая по
	* сортировке).</td> </tr> <tr> <td>USER_GROUPS</td> <td>Перечень групп пользователей,
	* на которые должна действовать скидка.</td> </tr> </table> <p>Если в
	* качестве параметра arGroupBy передается пустой массив, то метод
	* вернет число записей, удовлетворяющих фильтру.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выберем величину активной скидки для текущего сайта и стоимости 
	* // заказа $ORDER_PRICE (в базовой валюте этого сайта)
	* $db_res = CSaleDiscount::GetList(
	*         array("SORT" =&gt; "ASC"),
	*         array(
	*               "LID" =&gt; SITE_ID, 
	*               "ACTIVE" =&gt; "Y", 
	*               "&gt;=PRICE_TO" =&gt; $ORDER_PRICE, 
	*               "&lt;=PRICE_FROM" =&gt; $ORDER_PRICE
	*             ),
	*         false,
	*         false,
	*         array()
	*     );
	* if ($ar_res = $db_res-&gt;Fetch())
	* {
	*    echo "Наша скидка - ";
	*    if ($ar_res["DISCOUNT_TYPE"] == "P")
	*    {
	*       echo $ar_res["DISCOUNT_VALUE"]."%";
	*    }
	*    else
	*    {
	*       echo CurrencyFormat($ar_res["DISCOUNT_VALUE"], $ar_res["CURRENCY"]);
	*    }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalediscount/csalediscount__getlist.7e987f7e.php
	* @author Bitrix
	*/
	static public function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (!is_array($arOrder) && !is_array($arFilter))
		{
			$arOrder = (string)($arOrder);
			$arFilter = (string)($arFilter);
			if ($arOrder !== '' && $arFilter !== '')
				$arOrder = array($arOrder => $arFilter);
			else
				$arOrder = array();
			if (is_array($arGroupBy))
				$arFilter = $arGroupBy;
			else
				$arFilter = array();
			if (isset($arFilter["PRICE"]))
			{
				$valTmp = $arFilter["PRICE"];
				unset($arFilter["PRICE"]);
				$arFilter["<=PRICE_FROM"] = $valTmp;
				$arFilter[">=PRICE_TO"] = $valTmp;
			}
			$arGroupBy = false;
		}

		$arFields = array(
			"ID" => array("FIELD" => "D.ID", "TYPE" => "int"),
			"XML_ID" => array("FIELD" => "D.XML_ID", "TYPE" => "string"),
			"LID" => array("FIELD" => "D.LID", "TYPE" => "string"),
			"SITE_ID" => array("FIELD" => "D.LID", "TYPE" => "string"),
			"NAME" => array("FIELD" => "D.NAME", "TYPE" => "string"),
			"PRICE_FROM" => array("FIELD" => "D.PRICE_FROM", "TYPE" => "double", "WHERE" => array("CSaleDiscount", "PrepareCurrency4Where")),
			"PRICE_TO" => array("FIELD" => "D.PRICE_TO", "TYPE" => "double", "WHERE" => array("CSaleDiscount", "PrepareCurrency4Where")),
			"CURRENCY" => array("FIELD" => "D.CURRENCY", "TYPE" => "string"),
			"DISCOUNT_VALUE" => array("FIELD" => "D.DISCOUNT_VALUE", "TYPE" => "double"),
			"DISCOUNT_TYPE" => array("FIELD" => "D.DISCOUNT_TYPE", "TYPE" => "char"),
			"ACTIVE" => array("FIELD" => "D.ACTIVE", "TYPE" => "char"),
			"SORT" => array("FIELD" => "D.SORT", "TYPE" => "int"),
			"ACTIVE_FROM" => array("FIELD" => "D.ACTIVE_FROM", "TYPE" => "datetime"),
			"ACTIVE_TO" => array("FIELD" => "D.ACTIVE_TO", "TYPE" => "datetime"),
			"TIMESTAMP_X" => array("FIELD" => "D.TIMESTAMP_X", "TYPE" => "datetime"),
			"MODIFIED_BY" => array("FIELD" => "D.MODIFIED_BY", "TYPE" => "int"),
			"DATE_CREATE" => array("FIELD" => "D.DATE_CREATE", "TYPE" => "datetime"),
			"CREATED_BY" => array("FIELD" => "D.CREATED_BY", "TYPE" => "int"),
			"PRIORITY" => array("FIELD" => "D.PRIORITY", "TYPE" => "int"),
			"LAST_DISCOUNT" => array("FIELD" => "D.LAST_DISCOUNT", "TYPE" => "char"),
			"VERSION" => array("FIELD" => "D.VERSION", "TYPE" => "int"),
			"CONDITIONS" => array("FIELD" => "D.CONDITIONS", "TYPE" => "string"),
			"UNPACK" => array("FIELD" => "D.UNPACK", "TYPE" => "string"),
			"APPLICATION" => array("FIELD" => "D.APPLICATION", "TYPE" => "string"),
			"ACTIONS" => array("FIELD" => "D.ACTIONS", "TYPE" => "string"),
			"USE_COUPONS" => array("FIELD" => "D.USE_COUPONS", "TYPE" => "char"),
			"USER_GROUPS" => array("FIELD" => "DG.GROUP_ID", "TYPE" => "int","FROM" => "LEFT JOIN b_sale_discount_group DG ON (D.ID = DG.DISCOUNT_ID)")
		);

		if (empty($arSelectFields))
			$arSelectFields = array('ID','LID','SITE_ID','PRICE_FROM','PRICE_TO','CURRENCY','DISCOUNT_VALUE','DISCOUNT_TYPE','ACTIVE','SORT','ACTIVE_FROM','ACTIVE_TO','PRIORITY','LAST_DISCOUNT','VERSION','NAME');
		elseif (is_array($arSelectFields) && in_array('*',$arSelectFields))
			$arSelectFields = array('ID','LID','SITE_ID','PRICE_FROM','PRICE_TO','CURRENCY','DISCOUNT_VALUE','DISCOUNT_TYPE','ACTIVE','SORT','ACTIVE_FROM','ACTIVE_TO','PRIORITY','LAST_DISCOUNT','VERSION','NAME');

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", '', $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "select ".$arSqls["SELECT"]." from b_sale_discount D ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql .= " where ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql .= " group by ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		$strSql = "select ".$arSqls["SELECT"]." from b_sale_discount D ".$arSqls["FROM"];
		if (!empty($arSqls["WHERE"]))
			$strSql .= " where ".$arSqls["WHERE"];
		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " group by ".$arSqls["GROUPBY"];
		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " order by ".$arSqls["ORDERBY"];

		$intTopCount = 0;
		$boolNavStartParams = (!empty($arNavStartParams) && is_array($arNavStartParams));
		if ($boolNavStartParams && isset($arNavStartParams['nTopCount']))
		{
			$intTopCount = (int)$arNavStartParams["nTopCount"];
		}
		if ($boolNavStartParams && $intTopCount <= 0)
		{
			$strSql_tmp = "select COUNT('x') as CNT from b_sale_discount D ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql_tmp .= " where ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql_tmp .= " group by ".$arSqls["GROUPBY"];

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
			if ($boolNavStartParams && $intTopCount > 0)
			{
				$strSql .= " limit ".$intTopCount;
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

	static public function GetDiscountGroupList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		$arFields = array(
			"ID" => array("FIELD" => "DG.ID", "TYPE" => "int"),
			"DISCOUNT_ID" => array("FIELD" => "DG.DISCOUNT_ID", "TYPE" => "int"),
			"GROUP_ID" => array("FIELD" => "DG.GROUP_ID", "TYPE" => "int"),
		);

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "select ".$arSqls["SELECT"]." from b_sale_discount_group DG ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql .= " where ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql .= " group by ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		$strSql = "select ".$arSqls["SELECT"]." from b_sale_discount_group DG ".$arSqls["FROM"];
		if (!empty($arSqls["WHERE"]))
			$strSql .= " where ".$arSqls["WHERE"];
		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " group by ".$arSqls["GROUPBY"];
		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " order by ".$arSqls["ORDERBY"];

		$intTopCount = 0;
		$boolNavStartParams = (!empty($arNavStartParams) && is_array($arNavStartParams));
		if ($boolNavStartParams && array_key_exists('nTopCount', $arNavStartParams))
		{
			$intTopCount = intval($arNavStartParams["nTopCount"]);
		}
		if ($boolNavStartParams && 0 >= $intTopCount)
		{
			$strSql_tmp = "select COUNT('x') as CNT from b_sale_discount_group DG ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql_tmp .= " where ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql_tmp .= " group by ".$arSqls["GROUPBY"];

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
				$strSql .= " limit ".$intTopCount;
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}
}
?>