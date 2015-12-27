<?
use Bitrix\Main\Config\Option;
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/price.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/index.php
 * @author Bitrix
 */
class CPrice extends CAllPrice
{
	
	/**
	* <p>Метод добавляет новое ценовое предложение (новую цену) для товара. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров ценового предложения.
	* Допустимые параметры: <ul> <li> <b>PRODUCT_ID </b> - код товара или торгового
	* предложения (ID элемента инфоблока).;</li> <li> <b>EXTRA_ID</b> - код
	* наценки;</li> <li> <b>CATALOG_GROUP_ID</b> - код типа цены;</li> <li> <b>PRICE</b> - цена;</li>
	* <li> <b>CURRENCY</b> - валюта цены (обязательный параметр);</li> <li> <b>QUANTITY_FROM</b>
	* - количество товара, начиная с приобретения которого действует
	* эта цена;</li> <li> <b>QUANTITY_TO</b> - количество товара, при приобретении
	* которого заканчивает действие эта цена. <p></p> <div class="note">
	* <b>Примечание:</b> если необходимо, чтобы значения параметров
	* <b>QUANTITY_FROM</b> и <b>QUANTITY_TO</b> не были заданы, необходимо указать у них в
	* качестве значения false либо не задавать поля <b>QUANTITY_FROM</b> и
	* <b>QUANTITY_TO</b> в Update вообще.</div> </li> </ul> Если установлен код наценки, то
	* появляется возможность автоматически пересчитывать эту цену при
	* изменении базовой цены или процента наценки.
	*
	* @param boolean $boolRecalc = false Пересчитать цены. Если передать true, то включается механизм
	* пересчета цен. <br> Если добавляется базовая цена (в <b>CATALOG_GROUP_ID</b>
	* задан тип цен, являющийся базовым), будут пересчитаны все
	* остальные типы цен для товара, если у них задан код наценки. <br>
	* Если добавляется иная цена (не базовая), для нее задан код наценки
	* и уже существует базовая - значения <b>PRICE</b> и <b>CURRENCY</b> буду
	* пересчитаны. <br> Необязательный параметр. По умолчанию - <i>false</i>.
	*
	* @return mixed <p>Возвращает идентификатор добавленной цены в случае успешного
	* сохранения и <i>false</i> - в противном случае. Для получения детальной
	* информации об ошибке следует вызвать <b>$APPLICATION-&gt;GetException()</b>.</p>
	* <h4>События</h4></bod<p>Метод работает с событиями <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforepriceadd.php">OnBeforePriceAdd</a> и <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/events/onpriceadd.php">OnPriceAdd</a>.</p> <p></p><div class="note">
	* <b>Примечания:</b> <ul> <li>Если параметр <b>$boolRecalc = true</b>, все равно
	* необходимо указывать цену и валюту (в том случае, когда тип цены -
	* не базовый). Если существует базовая цена, значения цены и валюты
	* будут изменены, если нет - код наценки будет изменен на ноль.</li>
	* <li>В обработчиках события <b>OnBeforePriceAdd</b> можно запретить или,
	* наоборот, включить пересчет цены. За это отвечает ключ <b>RECALC</b>
	* массива данных, передаваемых в обработчик.</li> </ul> </div>
	*
	* <h4>Example</h4> 
	* <pre>
	* <b>Добавление цены</b>
	* &lt;?
	* // Установим для товара с кодом 15 цену типа 2 в значение 29.95 USD
	* $PRODUCT_ID = 15;
	* $PRICE_TYPE_ID = 2;
	* 
	* $arFields = Array(
	*     "PRODUCT_ID" =&gt; $PRODUCT_ID,
	*     "CATALOG_GROUP_ID" =&gt; $PRICE_TYPE_ID,
	*     "PRICE" =&gt; 29.95,
	*     "CURRENCY" =&gt; "USD",
	*     "QUANTITY_FROM" =&gt; 1,
	*     "QUANTITY_TO" =&gt; 10
	* );
	* 
	* $res = CPrice::GetList(
	*         array(),
	*         array(
	*                 "PRODUCT_ID" =&gt; $PRODUCT_ID,
	*                 "CATALOG_GROUP_ID" =&gt; $PRICE_TYPE_ID
	*             )
	*     );
	* 
	* if ($arr = $res-&gt;Fetch())
	* {
	*     CPrice::Update($arr["ID"], $arFields);
	* }
	* else
	* {
	*     CPrice::Add($arFields);
	* }
	* ?&gt;
	* 
	* <b>Добавление цены с пересчетом (базовая существует)</b>
	* 
	* $PRODUCT_ID = 15;
	* $PRICE_TYPE_ID = 2;
	* $arFields = array(
	* 	"PRODUCT_ID" =&gt; $PRODUCT_ID,
	* 	"CATALOG_GROUP_ID" =&gt; $PRICE_TYPE_ID,
	*     "PRICE" =&gt; 0,
	*     "CURRENCY" =&gt; "RUB",
	*     "EXTRA_ID" =&gt; 4,
	*     "QUANTITY_FROM" =&gt; 1,
	*     "QUANTITY_TO" =&gt; 10
	* );
	* 
	* 
	* $obPrice = new CPrice();
	* $obPrice-&gt;Add($arFields,true);
	* Величина и валюта цены будет расчитана исходя из наценки и базовой цены.
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/fields.php">Структура таблицы</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/checkfields.php">CPrice::CheckFields</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/update.php">CPrice::Update</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/events/onbeforepriceadd.php">Событие OnBeforePriceAdd</a></li>
	* <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/events/onpriceadd.php">Событие OnPriceAdd</a></li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/add.php
	* @author Bitrix
	*/
	public static function Add($arFields, $boolRecalc = false)
	{
		global $DB;

		$boolBase = false;
		$arFields['RECALC'] = ($boolRecalc === true);

		foreach (GetModuleEvents("catalog", "OnBeforePriceAdd", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array(&$arFields));
		}

		if (!CPrice::CheckFields("ADD", $arFields, 0))
			return false;

		if (isset($arFields['RECALC']) && $arFields['RECALC'] === true)
		{
			CPrice::ReCountFromBase($arFields, $boolBase);
			if (!$boolBase && $arFields['EXTRA_ID'] <= 0)
			{
				return false;
			}
		}

		$arInsert = $DB->PrepareInsert("b_catalog_price", $arFields);

		$strSql = "INSERT INTO b_catalog_price(".$arInsert[0].") VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = (int)$DB->LastID();

		if ($ID > 0 && $boolBase)
		{
			CPrice::ReCountForBase($arFields);
		}

		foreach (GetModuleEvents("catalog", "OnPriceAdd", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
		}

		// strange copy-paste bug
		foreach (GetModuleEvents("sale", "OnPriceAdd", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
		}

		return $ID;
	}

	
	/**
	* <p>Метод возвращает ценовое предложение по его коду ID. Метод динамичный. </p>
	*
	*
	* @param int $ID  Код ценового предложения.
	*
	* @return array <p>Возвращается ассоциативный массив с ключами</p> <table class="tnormal"
	* width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код
	* ценового предложения.</td> </tr> <tr> <td>PRODUCT_ID</td> <td>Код товара или
	* торгового предложения (ID элемента инфоблока)</td> </tr> <tr> <td>EXTRA_ID</td>
	* <td>Код наценки.</td> </tr> <tr> <td>CATALOG_GROUP_ID</td> <td>Код типа цены. </td> </tr> <tr>
	* <td>PRICE</td> <td>Цена.</td> </tr> <tr> <td>CURRENCY</td> <td>Валюта.</td> </tr> <tr> <td>CAN_ACCESS</td>
	* <td>Флаг (Y/N), может ли текущий пользователь видеть эту цену. </td> </tr>
	* <tr> <td>CAN_BUY</td> <td>Флаг (Y/N), может ли текущий пользователь покупать по
	* этой цене.</td> </tr> <tr> <td>CATALOG_GROUP_NAME</td> <td>Название группы цен на
	* текущем языке. </td> </tr> <tr> <td>TIMESTAMP_X</td> <td> Дата последнего изменения
	* записи. </td> </tr> <tr> <td>QUANTITY_FROM </td> <td>Количество товара, начиная с
	* приобретения которого действует эта цена. </td> </tr> <tr> <td>QUANTITY_TO </td>
	* <td>Количество товара, при приобретении которого заканчивает
	* действие эта цена. </td> </tr> </table> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $ID = 11;
	* $arPrice = CPrice::GetByID($ID);
	* echo "Цена типа ".$arPrice["CATALOG_GROUP_NAME"].
	*      " на товар с кодом ".$ID.": ";
	* echo CurrencyFormat($arPrice["PRICE"], 
	*                     $arPrice["CURRENCY"])."&lt;br&gt;";
	* echo "Вы ".(($arPrice["CAN_ACCESS"]=="Y") ? 
	*             "можете" : 
	*             "не можете")." видеть эту цену";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__getbyid.872661b0.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB, $USER;
		$ID = intval($ID);
		if (0 >= $ID)
			return false;
		$strUserGroups = (CCatalog::IsUserExists() ? $USER->GetGroups() : '2');
		$strSql =
			"SELECT CP.ID, CP.PRODUCT_ID, CP.EXTRA_ID, CP.CATALOG_GROUP_ID, CP.PRICE, ".
			"	CP.CURRENCY, CP.QUANTITY_FROM, CP.QUANTITY_TO, IF(CGG.ID IS NULL, 'N', 'Y') as CAN_ACCESS, CP.TMP_ID, ".
			"	CGL.NAME as CATALOG_GROUP_NAME, IF(CGG1.ID IS NULL, 'N', 'Y') as CAN_BUY, ".
			"	".$DB->DateToCharFunction("CP.TIMESTAMP_X", "FULL")." as TIMESTAMP_X ".
			"FROM b_catalog_price CP, b_catalog_group CG ".
			"	LEFT JOIN b_catalog_group2group CGG ON (CG.ID = CGG.CATALOG_GROUP_ID AND CGG.GROUP_ID IN (".$strUserGroups.") AND CGG.BUY <> 'Y') ".
			"	LEFT JOIN b_catalog_group2group CGG1 ON (CG.ID = CGG1.CATALOG_GROUP_ID AND CGG1.GROUP_ID IN (".$strUserGroups.") AND CGG1.BUY = 'Y') ".
			"	LEFT JOIN b_catalog_group_lang CGL ON (CG.ID = CGL.CATALOG_GROUP_ID AND CGL.LANG = '".LANGUAGE_ID."') ".
			"WHERE CP.ID = ".$ID." ".
			"	AND CP.CATALOG_GROUP_ID = CG.ID ".
			"GROUP BY CP.ID, CP.PRODUCT_ID, CP.EXTRA_ID, CP.CATALOG_GROUP_ID, CP.PRICE, CP.CURRENCY, CP.QUANTITY_FROM, CP.QUANTITY_TO, CP.TIMESTAMP_X ";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($res = $db_res->Fetch())
			return $res;

		return false;
	}

	/**
	 * @param array $arOrder
	 * @param array $arFilter
	 * @param bool|array $arGroupBy
	 * @param bool|array $arNavStartParams
	 * @param array $arSelectFields
	 * @return bool|CDBResult
	 */
	
	/**
	* <p>Метод возвращает результат выборки записей цен в соответствии со своими параметрами. Метод динамичный.</p> <p></p> <div class="note"> <b>Примечание</b>: Если выборку нужно произвести без учёта прав доступа, то лучше использовать метод <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/90252.php">CPrice::GetListEx</a>.</div>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле цены, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и
	* т.д.). <br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи типов цены.
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
	* величины;</li> <li> <b>@</b> - оператор может использоваться для
	* целочисленных и вещественных данных при передаче набора
	* значений (массива). В этом случае при генерации sql-запроса будет
	* использован sql-оператор <b>IN</b>, дающий компактную форму записи;</li>
	* <li> <b>~</b> - значение поля проверяется на соответствие
	* передаваемому в фильтр шаблону;</li> <li> <b>%</b> - значение поля
	* проверяется на соответствие передаваемой в фильтр строке в
	* соответствии с языком запросов.</li> </ul> В качестве "название_поляX"
	* может стоять любое поле типов цены.<br><br> Пример фильтра: <pre
	* class="syntax">array("PRODUCT_ID" =&gt; 150)</pre> Этот фильтр означает "выбрать все
	* записи, в которых значение в поле PRODUCT_ID (код товара) равно 150".<br><br>
	* Значение по умолчанию - пустой массив array() - означает, что
	* результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи типов цены. Массив
	* имеет вид: <pre class="syntax">array("название_поля1", "название_поля2", . . .)</pre> В
	* качестве "название_поля<i>N</i>" может стоять любое поле типов цены.
	* <br><br> Если массив пустой, то метод вернет число записей,
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
	* ассоциативных массивов с ключами:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код ценового
	* предложения.</td> </tr> <tr> <td>PRODUCT_ID</td> <td>Код товара или торгового
	* предложения (ID элемента инфоблока).</td> </tr> <tr> <td>EXTRA_ID</td> <td>Код
	* наценки.</td> </tr> <tr> <td>CATALOG_GROUP_ID</td> <td>Код типа цены.</td> </tr> <tr> <td>PRICE</td>
	* <td>Цена.</td> </tr> <tr> <td>CURRENCY</td> <td>Валюта.</td> </tr> <tr> <td>CAN_ACCESS</td> <td>Флаг
	* (Y/N), может ли текущий пользователь видеть эту цену.</td> </tr> <tr>
	* <td>CAN_BUY</td> <td>Флаг (Y/N), может ли текущий пользователь покупать по
	* этой цене.</td> </tr> <tr> <td>CATALOG_GROUP_NAME</td> <td>Название группы цен на
	* текущем языке.</td> </tr> <tr> <td>TIMESTAMP_X</td> <td> Дата последнего изменения
	* записи. </td> </tr> <tr> <td>QUANTITY_FROM </td> <td>Количество товара, начиная с
	* приобретения которого действует эта цена. </td> </tr> <tr> <td>QUANTITY_TO </td>
	* <td>Количество товара, при приобретении которого заканчивает
	* действие эта цена.</td> </tr> </table> <p> Если в качестве параметра arGroupBy
	* передается пустой массив, то метод вернет число записей,
	* удовлетворяющих фильтру. </p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выведем цену типа $PRICE_TYPE_ID товара с кодом $PRODUCT_ID
	* 
	* $db_res = CPrice::GetList(
	*         array(),
	*         array(
	*                 "PRODUCT_ID" =&gt; $PRODUCT_ID,
	*                 "CATALOG_GROUP_ID" =&gt; $PRICE_TYPE_ID
	*             )
	*     );
	* if ($ar_res = $db_res-&gt;Fetch())
	* {
	*     echo CurrencyFormat($ar_res["PRICE"], $ar_res["CURRENCY"]);
	* }
	* else
	* {
	*     echo "Цена не найдена!";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__getlist.8f7c2a3e.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB, $USER;

		// for old execution style
		if (!is_array($arOrder) && !is_array($arFilter))
		{
			$arOrder = (string)$arOrder;
			$arFilter = (string)$arFilter;
			$arOrder = ($arOrder != '' && $arFilter != '' ? array($arOrder => $arFilter) : array());
			$arFilter = (is_array($arGroupBy) ? $arGroupBy : array());
			$arGroupBy = false;
		}

		$strUserGroups = (CCatalog::IsUserExists() ? $USER->GetGroups() : '2');

		if (empty($arSelectFields))
			$arSelectFields = array("ID", "PRODUCT_ID", "EXTRA_ID", "CATALOG_GROUP_ID", "PRICE", "CURRENCY", "TIMESTAMP_X", "QUANTITY_FROM", "QUANTITY_TO", "BASE", "SORT", "CATALOG_GROUP_NAME", "CAN_ACCESS", "CAN_BUY");

		$arFields = array(
			"ID" => array("FIELD" => "P.ID", "TYPE" => "int"),
			"PRODUCT_ID" => array("FIELD" => "P.PRODUCT_ID", "TYPE" => "int"),
			"EXTRA_ID" => array("FIELD" => "P.EXTRA_ID", "TYPE" => "int"),
			"CATALOG_GROUP_ID" => array("FIELD" => "P.CATALOG_GROUP_ID", "TYPE" => "int"),
			"PRICE" => array("FIELD" => "P.PRICE", "TYPE" => "double"),
			"CURRENCY" => array("FIELD" => "P.CURRENCY", "TYPE" => "string"),
			"TIMESTAMP_X" => array("FIELD" => "P.TIMESTAMP_X", "TYPE" => "datetime"),
			"QUANTITY_FROM" => array("FIELD" => "P.QUANTITY_FROM", "TYPE" => "int"),
			"QUANTITY_TO" => array("FIELD" => "P.QUANTITY_TO", "TYPE" => "int"),
			"TMP_ID" => array("FIELD" => "P.TMP_ID", "TYPE" => "string"),
			"PRICE_BASE_RATE" => array("FIELD" => "P.PRICE*CC.CURRENT_BASE_RATE", "TYPE" => "double", "FROM" => "LEFT JOIN b_catalog_currency CC ON (P.CURRENCY = CC.CURRENCY)"),
			"BASE" => array("FIELD" => "CG.BASE", "TYPE" => "char", "FROM" => "INNER JOIN b_catalog_group CG ON (P.CATALOG_GROUP_ID = CG.ID)"),
			"SORT" => array("FIELD" => "CG.SORT", "TYPE" => "int", "FROM" => "INNER JOIN b_catalog_group CG ON (P.CATALOG_GROUP_ID = CG.ID)"),
			"PRODUCT_QUANTITY" => array("FIELD" => "CP.QUANTITY", "TYPE" => "int", "FROM" => "INNER JOIN b_catalog_product CP ON (P.PRODUCT_ID = CP.ID)"),
			"PRODUCT_QUANTITY_TRACE" => array("FIELD" => "IF (CP.QUANTITY_TRACE = 'D', '".$DB->ForSql((string)Option::get('catalog','default_quantity_trace','N'))."', CP.QUANTITY_TRACE)", "TYPE" => "char", "FROM" => "INNER JOIN b_catalog_product CP ON (P.PRODUCT_ID = CP.ID)"),
			"PRODUCT_CAN_BUY_ZERO" => array("FIELD" => "IF (CP.CAN_BUY_ZERO = 'D', '".$DB->ForSql((string)Option::get('catalog','default_can_buy_zero','N'))."', CP.CAN_BUY_ZERO)", "TYPE" => "char", "FROM" => "INNER JOIN b_catalog_product CP ON (P.PRODUCT_ID = CP.ID)"),
			"PRODUCT_NEGATIVE_AMOUNT_TRACE" => array("FIELD" => "IF (CP.NEGATIVE_AMOUNT_TRACE = 'D', '".$DB->ForSql((string)Option::get('catalog','allow_negative_amount','N'))."', CP.NEGATIVE_AMOUNT_TRACE)", "TYPE" => "char", "FROM" => "INNER JOIN b_catalog_product CP ON (P.PRODUCT_ID = CP.ID)"),
			"PRODUCT_WEIGHT" => array("FIELD" => "CP.WEIGHT", "TYPE" => "int", "FROM" => "INNER JOIN b_catalog_product CP ON (P.PRODUCT_ID = CP.ID)"),
			"ELEMENT_IBLOCK_ID" => array("FIELD" => "IE.IBLOCK_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_iblock_element IE ON (P.PRODUCT_ID = IE.ID)"),
			"CATALOG_GROUP_NAME" => array("FIELD" => "CGL.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_catalog_group_lang CGL ON (P.CATALOG_GROUP_ID = CGL.CATALOG_GROUP_ID AND CGL.LANG = '".LANGUAGE_ID."')"),
		);

		$arFields["CAN_ACCESS"] = array(
			"FIELD" => "IF(CGG.ID IS NULL, 'N', 'Y')",
			"TYPE" => "char",
			"FROM" => "LEFT JOIN b_catalog_group2group CGG ON (CG.ID = CGG.CATALOG_GROUP_ID AND CGG.GROUP_ID IN (".$strUserGroups.") AND CGG.BUY <> 'Y')"
		);
		$arFields["CAN_BUY"] = array(
			"FIELD" => "IF(CGG1.ID IS NULL, 'N', 'Y')",
			"TYPE" => "char",
			"FROM" => "LEFT JOIN b_catalog_group2group CGG1 ON (CG.ID = CGG1.CATALOG_GROUP_ID AND CGG1.GROUP_ID IN (".$strUserGroups.") AND CGG1.BUY = 'Y')"
		);

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		if (array_key_exists("CAN_ACCESS", $arFields) || array_key_exists("CAN_BUY", $arFields))
			$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);
		else
			$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_price P ".$arSqls["FROM"];
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

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_price P ".$arSqls["FROM"];
		if (!empty($arSqls["WHERE"]))
			$strSql .= " WHERE ".$arSqls["WHERE"];
		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " GROUP BY ".$arSqls["GROUPBY"];
		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " ORDER BY ".$arSqls["ORDERBY"];

		$intTopCount = 0;
		$boolNavStartParams = (!empty($arNavStartParams) && is_array($arNavStartParams));
		if ($boolNavStartParams && isset($arNavStartParams['nTopCount']))
		{
			$intTopCount = (int)$arNavStartParams["nTopCount"];
		}
		if ($boolNavStartParams && $intTopCount <= 0)
		{
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_price P ".$arSqls["FROM"];
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
			if ($boolNavStartParams && $intTopCount > 0)
			{
				$strSql .= " LIMIT ".$intTopCount;
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

	/**
	 * @param array $arOrder
	 * @param array $arFilter
	 * @param bool|array $arGroupBy
	 * @param bool|array $arNavStartParams
	 * @param array $arSelectFields
	 * @return bool|CDBResult
	 */
	
	/**
	* <p>Метод возвращает результат выборки записей цен в соответствии со своими параметрами. Отличается от обычного <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__getlist.8f7c2a3e.php">GetList</a> отсутствием безусловной проверкой прав на типы цен для групп текущего пользователя. Метод необходимо использовать везде, где эта проверка не требуется (будет гораздо производительнее). Метод динамичный. </p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле цены, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и
	* т.д.). <br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи типов цены.
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
	* величины;</li> <li> <b>@</b> - оператор может использоваться для
	* целочисленных и вещественных данных при передаче набора
	* значений (массива). В этом случае при генерации sql-запроса будет
	* использован sql-оператор <b>IN</b>, дающий компактную форму записи;</li>
	* <li> <b>~</b> - значение поля проверяется на соответствие
	* передаваемому в фильтр шаблону;</li> <li> <b>%</b> - значение поля
	* проверяется на соответствие передаваемой в фильтр строке в
	* соответствии с языком запросов.</li> </ul> В качестве "название_поляX"
	* может стоять любое поле типов цены.<br><br> Пример фильтра: <pre
	* class="syntax">array("PRODUCT_ID" =&gt; 150)</pre> Этот фильтр означает "выбрать все
	* записи, в которых значение в поле PRODUCT_ID (код товара) равно 150".<br><br>
	* Значение по умолчанию - пустой массив array() - означает, что
	* результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи типов цены. Массив
	* имеет вид: <pre class="syntax">array("название_поля1", "название_поля2", . . .)</pre> В
	* качестве "название_поля<i>N</i>" может стоять любое поле типов цены.
	* <br><br> Если массив пустой, то метод вернет число записей,
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
	* ассоциативных массивов с ключами:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код ценового
	* предложения.</td> </tr> <tr> <td>PRODUCT_ID</td> <td>код товара или торгового
	* предложения (ID элемента инфоблока)</td> </tr> <tr> <td>EXTRA_ID</td> <td>Код
	* наценки.</td> </tr> <tr> <td>CATALOG_GROUP_ID</td> <td>Код типа цены.</td> </tr> <tr> <td>PRICE</td>
	* <td>Цена.</td> </tr> <tr> <td>CURRENCY</td> <td>Валюта.</td> </tr> <tr> <td>TIMESTAMP_X</td> <td> Дата
	* последнего изменения записи. </td> </tr> <tr> <td>QUANTITY_FROM </td> <td>Количество
	* товара, начиная с приобретения которого действует эта цена. </td>
	* </tr> <tr> <td>QUANTITY_TO </td> <td>Количество товара, при приобретении
	* которого заканчивает действие эта цена.</td> </tr> <tr> <td>CATALOG_GROUP_BASE</td>
	* <td>Флаг "Базовая" типа цены.</td> </tr> <tr> <td>CATALOG_GROUP_NAME</td> <td>Название
	* группы цен на текущем языке.</td> </tr> <tr> <td>CATALOG_GROUP_SORT</td> <td>Индекс
	* сортировки типа цены.</td> </tr> <tr> <td>GROUP_BUY</td> <td>Флаг "Разрешена
	* покупка по этой цене"</td> </tr> </table> <p> Если в качестве параметра
	* arGroupBy передается пустой массив, то метод вернет число записей,
	* удовлетворяющих фильтру. </p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $dbProductPrice = CPrice::GetListEx(
	*         array(),
	*         array("PRODUCT_ID" =&gt; $ID),
	*         false,
	*         false,
	*         array("ID", "CATALOG_GROUP_ID", "PRICE", "CURRENCY", "QUANTITY_FROM", "QUANTITY_TO")
	*     );
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__getlistex.8f7c2a3d.php
	* @author Bitrix
	*/
	public static function GetListEx($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (empty($arSelectFields))
			$arSelectFields = array("ID", "PRODUCT_ID", "EXTRA_ID", "CATALOG_GROUP_ID", "PRICE", "CURRENCY", "TIMESTAMP_X", "QUANTITY_FROM", "QUANTITY_TO", "TMP_ID");

		$arFields = array(
			"ID" => array("FIELD" => "P.ID", "TYPE" => "int"),
			"PRODUCT_ID" => array("FIELD" => "P.PRODUCT_ID", "TYPE" => "int"),
			"EXTRA_ID" => array("FIELD" => "P.EXTRA_ID", "TYPE" => "int"),
			"CATALOG_GROUP_ID" => array("FIELD" => "P.CATALOG_GROUP_ID", "TYPE" => "int"),
			"PRICE" => array("FIELD" => "P.PRICE", "TYPE" => "double"),
			"CURRENCY" => array("FIELD" => "P.CURRENCY", "TYPE" => "string"),
			"TIMESTAMP_X" => array("FIELD" => "P.TIMESTAMP_X", "TYPE" => "datetime"),
			"QUANTITY_FROM" => array("FIELD" => "P.QUANTITY_FROM", "TYPE" => "int"),
			"QUANTITY_TO" => array("FIELD" => "P.QUANTITY_TO", "TYPE" => "int"),
			"TMP_ID" => array("FIELD" => "P.TMP_ID", "TYPE" => "string"),
			"PRICE_BASE_RATE" => array("FIELD" => "P.PRICE*CC.CURRENT_BASE_RATE", "TYPE" => "double", "FROM" => "LEFT JOIN b_catalog_currency CC ON (P.CURRENCY = CC.CURRENCY)"),
			"PRODUCT_QUANTITY" => array("FIELD" => "CP.QUANTITY", "TYPE" => "int", "FROM" => "INNER JOIN b_catalog_product CP ON (P.PRODUCT_ID = CP.ID)"),
			"PRODUCT_QUANTITY_TRACE" => array("FIELD" => "IF (CP.QUANTITY_TRACE = 'D', '".$DB->ForSql((string)Option::get('catalog','default_quantity_trace','N'))."', CP.QUANTITY_TRACE)", "TYPE" => "char", "FROM" => "INNER JOIN b_catalog_product CP ON (P.PRODUCT_ID = CP.ID)"),
			"PRODUCT_CAN_BUY_ZERO" => array("FIELD" => "IF (CP.CAN_BUY_ZERO = 'D', '".$DB->ForSql((string)Option::get('catalog','default_can_buy_zero','N'))."', CP.CAN_BUY_ZERO)", "TYPE" => "char", "FROM" => "INNER JOIN b_catalog_product CP ON (P.PRODUCT_ID = CP.ID)"),
			"PRODUCT_NEGATIVE_AMOUNT_TRACE" => array("FIELD" => "IF (CP.NEGATIVE_AMOUNT_TRACE = 'D', '".$DB->ForSql((string)Option::get('catalog','allow_negative_amount','N'))."', CP.NEGATIVE_AMOUNT_TRACE)", "TYPE" => "char", "FROM" => "INNER JOIN b_catalog_product CP ON (P.PRODUCT_ID = CP.ID)"),
			"PRODUCT_WEIGHT" => array("FIELD" => "CP.WEIGHT", "TYPE" => "int", "FROM" => "INNER JOIN b_catalog_product CP ON (P.PRODUCT_ID = CP.ID)"),
			"ELEMENT_IBLOCK_ID" => array("FIELD" => "IE.IBLOCK_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_iblock_element IE ON (P.PRODUCT_ID = IE.ID)"),
			"ELEMENT_NAME" => array("FIELD" => "IE.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_iblock_element IE ON (P.PRODUCT_ID = IE.ID)"),
			"CATALOG_GROUP_CODE" => array("FIELD" => "CG.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_catalog_group CG ON (P.CATALOG_GROUP_ID = CG.ID)"),
			"CATALOG_GROUP_BASE" => array("FIELD" => "CG.BASE", "TYPE" => "char", "FROM" => "INNER JOIN b_catalog_group CG ON (P.CATALOG_GROUP_ID = CG.ID)"),
			"CATALOG_GROUP_SORT" => array("FIELD" => "CG.SORT", "TYPE" => "int", "FROM" => "INNER JOIN b_catalog_group CG ON (P.CATALOG_GROUP_ID = CG.ID)"),
			"CATALOG_GROUP_NAME" => array("FIELD" => "CGL.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_catalog_group_lang CGL ON (P.CATALOG_GROUP_ID = CGL.CATALOG_GROUP_ID AND CGL.LANG = '".LANGUAGE_ID."')"),
			"GROUP_GROUP_ID" => array("FIELD" => "CGG.GROUP_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_catalog_group2group CGG ON (P.CATALOG_GROUP_ID = CGG.CATALOG_GROUP_ID)"),
			"GROUP_BUY" => array("FIELD" => "CGG.BUY", "TYPE" => "char", "FROM" => "INNER JOIN b_catalog_group2group CGG ON (P.CATALOG_GROUP_ID = CGG.CATALOG_GROUP_ID)")
		);

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_price P ".$arSqls["FROM"];
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

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_price P ".$arSqls["FROM"];
		if (!empty($arSqls["WHERE"]))
			$strSql .= " WHERE ".$arSqls["WHERE"];
		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " GROUP BY ".$arSqls["GROUPBY"];
		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " ORDER BY ".$arSqls["ORDERBY"];

		$intTopCount = 0;
		$boolNavStartParams = (!empty($arNavStartParams) && is_array($arNavStartParams));
		if ($boolNavStartParams && isset($arNavStartParams['nTopCount']))
		{
			$intTopCount = (int)$arNavStartParams["nTopCount"];
		}
		if ($boolNavStartParams && $intTopCount <= 0)
		{
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_price P ".$arSqls["FROM"];
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
			if ($boolNavStartParams && $intTopCount > 0)
			{
				$strSql .= " LIMIT ".$intTopCount;
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}
}
?>