<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/basket.php");


use \Bitrix\Main\Localization;


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalebasket/index.php
 * @author Bitrix
 */
class CSaleBasket extends CAllSaleBasket
{
	/**
	* The function remove old subscribe product
	*
	* @param string $LID - site for cleaning
	* @return true false
	*/
	public static function _ClearProductSubscribe($LID)
	{
		global $DB;

		$subProp = COption::GetOptionString("sale", "subscribe_prod", "");
		$arSubProp = unserialize($subProp);

		$dayDelete = IntVal($arSubProp[$LID]["del_after"]);

		$strSql =
			"DELETE ".
			"FROM b_sale_basket ".
			"WHERE ((ORDER_ID IS NULL) OR (ORDER_ID = 0)) AND CAN_BUY = 'N' AND SUBSCRIBE = 'Y' AND TO_DAYS(DATE_INSERT) < (TO_DAYS(NOW()) - ".$dayDelete.") LIMIT 500";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	
	/**
	* <p>Метод возвращает результат выборки записей из корзины в соответствии со своими параметрами. Метод динамичный.</p>
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
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи корзины.
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
	* передаваемом в фильтр массиве значений;</li> <li> <b>~</b> - значение поля
	* проверяется на соответствие передаваемому в фильтр шаблону;</li>
	* <li> <b>%</b> - значение поля проверяется на соответствие передаваемой
	* в фильтр строке в соответствии с языком запросов.</li> </ul> В
	* качестве "название_поляX" может стоять любое поле корзины.<br><br>
	* Пример фильтра: <pre class="syntax">array("+&gt;=ORDER_ID" =&gt; 20)</pre> Этот фильтр
	* означает "выбрать все записи, в которых значение в поле ORDER_ID
	* больше или равно 20 либо не установлено (т.е. null или ноль)".<br>
	* Значение по умолчанию - пустой массив array() - означает, что
	* результат отфильтрован не будет. <br><br> В фильтре также можно
	* использовать параметр USER_ID (не путать с FUSER_ID), передавая в него
	* реальный идентификатор пользователя. Например вот так: <pre
	* class="syntax">'!USER_ID' =&gt; false</pre>
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи корзины. Массив
	* имеет вид: <pre class="syntax">array("название_поля1", "группирующая_функция2"
	* =&gt; "название_поля2", ...)</pre> В качестве "название_поля<i>N</i>" может
	* стоять любое поле корзины. В качестве группирующей функции могут
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
	* ограничено сверху значением этого ключа<br> любой ключ,
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
	* @return CDBResult <p>Возвращается объект класса <b>CDBResult</b>, содержащий набор
	* ассоциативных массивов с ключами:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код записи.</td> </tr> <tr>
	* <td>PRODUCT_ID</td> <td>Уникальный в рамках модуля код товара.</td> </tr> <tr>
	* <td>PRODUCT_PRICE_ID</td> <td>Дополнительный код товара.</td> </tr> <tr> <td>PRICE</td>
	* <td>Стоимость единицы товара.</td> </tr> <tr> <td>CURRENCY</td> <td>Валюта
	* стоимости единицы товара.</td> </tr> <tr> <td>WEIGHT</td> <td>Вес единицы
	* товара.</td> </tr> <tr> <td>QUANTITY</td> <td>Количество единиц товара.</td> </tr> <tr>
	* <td>LID</td> <td>Сайт, на котором сделана покупка.</td> </tr> <tr> <td>DELAY</td>
	* <td>Флаг "товар отложен" (Y/N)</td> </tr> <tr> <td>CAN_BUY</td> <td>Флаг "товар можно
	* купить" (Y/N)</td> </tr> <tr> <td>NAME</td> <td>Название товара.</td> </tr> <tr>
	* <td>CALLBACK_FUNC<font color="#FF0000">*</font> </td> <td>Название функции обратного
	* вызова для поддержки актуальности корзины.</td> </tr> <tr> <td>MODULE</td>
	* <td>Модуль, добавляющий товар в корзину.</td> </tr> <tr> <td>NOTES</td> <td>Особые
	* заметки, например, тип цены.</td> </tr> <tr> <td>ORDER_CALLBACK_FUNC<font color="#FF0000">*</font>
	* </td> <td>Название функции обратного вызова для оформления заказа.</td>
	* </tr> <tr> <td>ORDER_ALLOW_DELIVERY</td> <td>Доставка заказа корзины разрешена. (Для
	* корзин, уже привязанных к заказу.)</td> </tr> <tr> <td>ORDER_PAYED</td> <td>Заказ
	* корзины оплачен. (Для корзин, уже привязанных к заказу.)</td> </tr> <tr>
	* <td>ORDER_PRICE</td> <td>Стоимость заказа корзины. (Для корзин, уже
	* привязанных к заказу.)</td> </tr> <tr> <td>ORDER_CANCELED</td> <td>Флаг отмены
	* заказа.</td> </tr> <tr> <td>DETAIL_PAGE_URL</td> <td>Ссылка на страницу детального
	* просмотра товара.</td> </tr> <tr> <td>FUSER_ID</td> <td>Внутренний код владельца
	* корзины (не совпадает с кодом пользователя) </td> </tr> <tr> <td>USER_ID</td>
	* <td>Реальный идентификатор пользователя. (не путать с FUSER_ID)</td> </tr>
	* <tr> <td>ORDER_ID</td> <td>Код заказа, в который вошла эта запись (товар). Для
	* товаров, которые помещены в корзину, но ещё не заказаны, это поле
	* равно NULL. </td> </tr> <tr> <td>DATE_INSERT</td> <td>Дата добавления товара в
	* корзину.</td> </tr> <tr> <td>DATE_UPDATE</td> <td>Дата последнего изменения
	* записи.</td> </tr> <tr> <td>CANCEL_CALLBACK_FUNC<font color="#FF0000">*</font> </td> <td>Название
	* функции обратного вызова для отмены заказа. </td> </tr> <tr>
	* <td>PAY_CALLBACK_FUNC<font color="#FF0000">*</font> </td> <td>Название функции обратного
	* вызова, которая вызывается при установке флага заказа "Доставка
	* разрешена". </td> </tr> <tr> <td>PRODUCT_PROVIDER_CLASS<font color="#0000FF">**</font> </td> <td>Имя
	* класса, реализующего интерфейс <b> IBXSaleProductProvider</b>. Торговый
	* каталог записывает в это поле имя класса <b>CCatalogProductProvider</b>.</td> </tr>
	* <tr> <td>DISCOUNT_PRICE</td> <td>Скидка на товар. Значение устанавливается
	* только после оформления заказа. </td> </tr> </table> <p>Если в качестве
	* параметра arGroupBy передается пустой массив, то метод вернет число
	* записей, удовлетворяющих фильтру.</p> <p><b><font color="#FF0000">*</font></b> - ключи
	* считаются устаревшими, начиная с версии 12.5.<br><b><font
	* color="#0000FF">**</font></b> - ключ доступен, начиная с версии 12.5.</p> <p></p><div
	* class="note"> <b>Обратите внимание, что</b> в корзине могут быть не только
	* товары из инфоблока (вполне может какой-то иной модуль добавлять
	* в корзину товары со своим PRODUCT_ID), поэтому надо смотреть еще поле
	* MODULE (для товаров из инфоблоков оно равно catalog).</div> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выведем актуальную корзину для текущего пользователя
	* 
	* $arBasketItems = array();
	* 
	* $dbBasketItems = CSaleBasket::GetList(
	*         array(
	*                 "NAME" =&gt; "ASC",
	*                 "ID" =&gt; "ASC"
	*             ),
	*         array(
	*                 "FUSER_ID" =&gt; CSaleBasket::GetBasketUserID(),
	*                 "LID" =&gt; SITE_ID,
	*                 "ORDER_ID" =&gt; "NULL"
	*             ),
	*         false,
	*         false,
	*         array("ID", "CALLBACK_FUNC", "MODULE", 
	*               "PRODUCT_ID", "QUANTITY", "DELAY", 
	*               "CAN_BUY", "PRICE", "WEIGHT")
	*     );
	* while ($arItems = $dbBasketItems-&gt;Fetch())
	* {
	*     if (strlen($arItems["CALLBACK_FUNC"]) &gt; 0)
	*     {
	*         CSaleBasket::UpdatePrice($arItems["ID"], 
	*                                  $arItems["CALLBACK_FUNC"], 
	*                                  $arItems["MODULE"], 
	*                                  $arItems["PRODUCT_ID"], 
	*                                  $arItems["QUANTITY"]);
	*         $arItems = CSaleBasket::GetByID($arItems["ID"]);
	*     }
	* 
	*     $arBasketItems[] = $arItems;
	* }
	* 
	* // Печатаем массив, содержащий актуальную на текущий момент корзину
	* echo "&lt;pre&gt;";
	* print_r($arBasketItems);
	* echo "&lt;/pre&gt;";
	* ?&gt;
	* 
	* &lt;?
	* $arID = array();
	* 
	* $arBasketItems = array();
	* 
	* $dbBasketItems = CSaleBasket::GetList(
	*      array(
	*                 "NAME" =&gt; "ASC",
	*                 "ID" =&gt; "ASC"
	*              ),
	*      array(
	*                 "FUSER_ID" =&gt; CSaleBasket::GetBasketUserID(),
	*                 "LID" =&gt; SITE_ID,
	*                 "ORDER_ID" =&gt; "NULL"
	*              ),
	*      false,
	*      false,
	*      array("ID", "CALLBACK_FUNC", "MODULE", "PRODUCT_ID", "QUANTITY", "PRODUCT_PROVIDER_CLASS")
	*              );
	* while ($arItems = $dbBasketItems-&gt;Fetch())
	* {
	*      if ('' != $arItems['PRODUCT_PROVIDER_CLASS'] || '' != $arItems["CALLBACK_FUNC"])
	*      {
	*           CSaleBasket::UpdatePrice($arItems["ID"],
	*                                  $arItems["CALLBACK_FUNC"],
	*                                  $arItems["MODULE"],
	*                                  $arItems["PRODUCT_ID"],
	*                                  $arItems["QUANTITY"],
	*                                  "N",
	*                                  $arItems["PRODUCT_PROVIDER_CLASS"]
	*                                  );
	*           $arID[] = $arItems["ID"];
	*      }
	* }
	* if (!empty($arID))
	*      {
	*      $dbBasketItems = CSaleBasket::GetList(
	*      array(
	*           "NAME" =&gt; "ASC",
	*           "ID" =&gt; "ASC"
	*           ),
	*      array(
	*           "ID" =&gt; $arID,
	*         "ORDER_ID" =&gt; "NULL"
	*           ),
	*         false,
	*         false,
	*         array("ID", "CALLBACK_FUNC", "MODULE", "PRODUCT_ID", "QUANTITY", "DELAY", "CAN_BUY", "PRICE", "WEIGHT", "PRODUCT_PROVIDER_CLASS", "NAME")
	*                 );
	* while ($arItems = $dbBasketItems-&gt;Fetch())
	* {
	*     $arBasketItems[] = $arItems;
	* }
	* }
	* // Печатаем массив, содержащий актуальную на текущий момент корзину
	* echo "&lt;pre&gt;";
	* print_r($arBasketItems);
	* echo "&lt;/pre&gt;";
	* ?&gt;
	* 
	* 
	* 
	* //количество товаров в корзине (без цикла) 
	* $cntBasketItems = CSaleBasket::GetList(
	*    array(),
	*    array( 
	*       "FUSER_ID" =&gt; CSaleBasket::GetBasketUserID(),
	*       "LID" =&gt; SITE_ID,
	*       "ORDER_ID" =&gt; "NULL"
	*    ), 
	*    array()
	* );
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalebasket/csalebasket__getlist.4d82547a.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB, $USER;

		$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N');

		if (!is_array($arOrder) && !is_array($arFilter))
		{
			$arOrder = strval($arOrder);
			$arFilter = strval($arFilter);
			if (strlen($arOrder) > 0 && strlen($arFilter) > 0)
				$arOrder = array($arOrder => $arFilter);
			else
				$arOrder = array();
			if (is_array($arGroupBy))
				$arFilter = $arGroupBy;
			else
				$arFilter = array();
			$arGroupBy = false;

			if (ToUpper($arFilter["ORDER_ID"]) == "NULL")
			{
				$arFilter["ORDER_ID"] = 0;
			}
		}

		if ($isOrderConverted == "Y")
		{
			$result = \Bitrix\Sale\Compatible\BasketCompatibility::getList($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields);
			$result->addFetchAdapter(new \Bitrix\Sale\Compatible\BasketFetchAdapter());
			return $result;
		}



		if (count($arSelectFields) <= 0)
		{
			$arSelectFields = array(
				"ID",
				"FUSER_ID",
				"ORDER_ID",
				"PRODUCT_ID",
				"PRODUCT_PRICE_ID",
				"PRICE", "CURRENCY",
				"DATE_INSERT",
				"DATE_UPDATE",
				"WEIGHT",
				"QUANTITY",
				"LID",
				"DELAY",
				"NAME",
				"CAN_BUY",
				"MODULE",
				"CALLBACK_FUNC",
				"NOTES",
				"ORDER_CALLBACK_FUNC",
				"PAY_CALLBACK_FUNC",
				"CANCEL_CALLBACK_FUNC",
				"PRODUCT_PROVIDER_CLASS",
				"DETAIL_PAGE_URL",
				"DISCOUNT_PRICE",
				"CATALOG_XML_ID",
				"PRODUCT_XML_ID",
				"DISCOUNT_NAME",
				"DISCOUNT_VALUE",
				"DISCOUNT_COUPON",
				"VAT_RATE",
				"USER_ID",
				"SUBSCRIBE",
				"BARCODE_MULTI",
				"RESERVED",
				"DEDUCTED",
				"RESERVE_QUANTITY",
				"CUSTOM_PRICE",
				"DIMENSIONS",
				"TYPE",
				"SET_PARENT_ID",
				"RECOMMENDATION"
			);
		}
		elseif (in_array("*", $arSelectFields))
		{
			$arSelectFields = array(
				"ID",
				"FUSER_ID",
				"ORDER_ID",
				"PRODUCT_ID",
				"PRODUCT_PRICE_ID",
				"PRICE",
				"CURRENCY",
				"DATE_INSERT",
				"DATE_UPDATE",
				"WEIGHT",
				"QUANTITY",
				"LID",
				"DELAY",
				"NAME",
				"CAN_BUY",
				"MODULE",
				"CALLBACK_FUNC",
				"NOTES",
				"ORDER_CALLBACK_FUNC",
				"PAY_CALLBACK_FUNC",
				"CANCEL_CALLBACK_FUNC",
				"PRODUCT_PROVIDER_CLASS",
				"DETAIL_PAGE_URL",
				"DISCOUNT_PRICE",
				"CATALOG_XML_ID",
				"PRODUCT_XML_ID",
				"DISCOUNT_NAME",
				"DISCOUNT_VALUE",
				"DISCOUNT_COUPON",
				"VAT_RATE",
				"ORDER_ALLOW_DELIVERY",
				"ORDER_DATE_ALLOW_DELIVERY",
				"ORDER_STATUS",
				"ORDER_CANCELED",
				"ORDER_PAYED",
				"ORDER_PRICE",
				"ORDER_DATE",
				"ORDER_DATE_PAYED",
				"USER_ID",
				"SUBSCRIBE",
				"BARCODE_MULTI",
				"RESERVED",
				"DEDUCTED",
				"RESERVE_QUANTITY",
				"CUSTOM_PRICE",
				"DIMENSIONS",
				"TYPE",
				"SET_PARENT_ID",
				"MEASURE_CODE",
				"MEASURE_NAME",
				"RECOMMENDATION"
			);
		}

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "B.ID", "TYPE" => "int"),
				"FUSER_ID" => array("FIELD" => "B.FUSER_ID", "TYPE" => "int"),
				"ORDER_ID" => array("FIELD" => "B.ORDER_ID", "TYPE" => "int"),
				"PRODUCT_ID" => array("FIELD" => "B.PRODUCT_ID", "TYPE" => "int"),
				"PRODUCT_PRICE_ID" => array("FIELD" => "B.PRODUCT_PRICE_ID", "TYPE" => "int"),
				"PRICE" => array("FIELD" => "B.PRICE", "TYPE" => "double"),
				"CURRENCY" => array("FIELD" => "B.CURRENCY", "TYPE" => "string"),
				"DATE_INSERT" => array("FIELD" => "B.DATE_INSERT", "TYPE" => "datetime"),
				"DATE_UPDATE" => array("FIELD" => "B.DATE_UPDATE", "TYPE" => "datetime"),
				"WEIGHT" => array("FIELD" => "B.WEIGHT", "TYPE" => "double"),
				"QUANTITY" => array("FIELD" => "B.QUANTITY", "TYPE" => "double"),
				"LID" => array("FIELD" => "B.LID", "TYPE" => "string"),
				"DELAY" => array("FIELD" => "B.DELAY", "TYPE" => "char"),
				"NAME" => array("FIELD" => "B.NAME", "TYPE" => "string"),
				"CAN_BUY" => array("FIELD" => "B.CAN_BUY", "TYPE" => "char"),
				"MODULE" => array("FIELD" => "B.MODULE", "TYPE" => "string"),
				"CALLBACK_FUNC" => array("FIELD" => "B.CALLBACK_FUNC", "TYPE" => "string"),
				"NOTES" => array("FIELD" => "B.NOTES", "TYPE" => "string"),
				"ORDER_CALLBACK_FUNC" => array("FIELD" => "B.ORDER_CALLBACK_FUNC", "TYPE" => "string"),
				"PAY_CALLBACK_FUNC" => array("FIELD" => "B.PAY_CALLBACK_FUNC", "TYPE" => "string"),
				"CANCEL_CALLBACK_FUNC" => array("FIELD" => "B.CANCEL_CALLBACK_FUNC", "TYPE" => "string"),
				"PRODUCT_PROVIDER_CLASS" => array("FIELD" => "B.PRODUCT_PROVIDER_CLASS", "TYPE" => "string"),
				"DETAIL_PAGE_URL" => array("FIELD" => "B.DETAIL_PAGE_URL", "TYPE" => "string"),
				"DISCOUNT_PRICE" => array("FIELD" => "B.DISCOUNT_PRICE", "TYPE" => "double"),
				"CATALOG_XML_ID" => array("FIELD" => "B.CATALOG_XML_ID", "TYPE" => "string"),
				"PRODUCT_XML_ID" => array("FIELD" => "B.PRODUCT_XML_ID", "TYPE" => "string"),
				"DISCOUNT_NAME" => array("FIELD" => "B.DISCOUNT_NAME", "TYPE" => "string"),
				"DISCOUNT_VALUE" => array("FIELD" => "B.DISCOUNT_VALUE", "TYPE" => "string"),
				"DISCOUNT_COUPON" => array("FIELD" => "B.DISCOUNT_COUPON", "TYPE" => "string"),
				"VAT_RATE" => array("FIELD" => "B.VAT_RATE", "TYPE" => "double"),
				"SUBSCRIBE" => array("FIELD" => "B.SUBSCRIBE", "TYPE" => "char"),
				"BARCODE_MULTI" => array("FIELD" => "B.BARCODE_MULTI", "TYPE" => "char"),
				"RESERVED" => array("FIELD" => "B.RESERVED", "TYPE" => "char"),
				"DEDUCTED" => array("FIELD" => "B.DEDUCTED", "TYPE" => "char"),
				"RESERVE_QUANTITY" => array("FIELD" => "B.RESERVE_QUANTITY", "TYPE" => "double"),
				"CUSTOM_PRICE" => array("FIELD" => "B.CUSTOM_PRICE", "TYPE" => "char"),
				"DIMENSIONS" => array("FIELD" => "B.DIMENSIONS", "TYPE" => "string"),
				"TYPE" => array("FIELD" => "B.TYPE", "TYPE" => "int"),
				"SET_PARENT_ID" => array("FIELD" => "B.SET_PARENT_ID", "TYPE" => "int"),
				"MEASURE_CODE" => array("FIELD" => "B.MEASURE_CODE", "TYPE" => "int"),
				"MEASURE_NAME" => array("FIELD" => "B.MEASURE_NAME", "TYPE" => "string"),
				"RECOMMENDATION" => array("FIELD" => "B.RECOMMENDATION", "TYPE" => "string"),

				"ORDER_ALLOW_DELIVERY" => array("FIELD" => "O.ALLOW_DELIVERY", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order O ON (O.ID = B.ORDER_ID)"),
				"ORDER_DATE_ALLOW_DELIVERY" => array("FIELD" => "O.DATE_ALLOW_DELIVERY", "TYPE" => "datetime", "FROM" => "LEFT JOIN b_sale_order O ON (O.ID = B.ORDER_ID)"),

				"ORDER_STATUS" => array("FIELD" => "O.STATUS_ID", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order O ON (O.ID = B.ORDER_ID)"),
				"ORDER_CANCELED" => array("FIELD" => "O.CANCELED", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order O ON (O.ID = B.ORDER_ID)"),
				"ORDER_DATE" => array("FIELD" => "O.DATE_INSERT", "TYPE" => "datetime", "FROM" => "LEFT JOIN b_sale_order O ON (O.ID = B.ORDER_ID)"),
				"ORDER_PAYED" => array("FIELD" => "O.PAYED", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order O ON (O.ID = B.ORDER_ID)"),
				"ORDER_DATE_PAYED" => array("FIELD" => "O.DATE_PAYED", "TYPE" => "datetime", "FROM" => "LEFT JOIN b_sale_order O ON (O.ID = B.ORDER_ID)"),
				"ORDER_PRICE" => array("FIELD" => "O.PRICE", "TYPE" => "double", "FROM" => "LEFT JOIN b_sale_order O ON (O.ID = B.ORDER_ID)"),

				"USER_ID" => array("FIELD" => "F.USER_ID", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_fuser F ON (F.ID = B.FUSER_ID)"),

				"ALL_PRICE" => array("FIELD" => "(B.PRICE+B.DISCOUNT_PRICE)", "TYPE" => "double"),
				"SUM_PRICE" => array("FIELD" => "(B.PRICE*B.QUANTITY)", "TYPE" => "double"),
			);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_basket B ".
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
			"FROM b_sale_basket B ".
			"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";
		// echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])<=0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_sale_basket B ".
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
	* <p>Метод возвращает результат выборки записей из свойств корзины в соответствии со своими параметрами. Метод динамичный.</p>
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
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи корзины.
	* Массив имеет вид: <pre class="syntax">array(
	* "[модификатор1][оператор1]название_поля1" =&gt; "значение1",
	* "[модификатор2][оператор2]название_поля2" =&gt; "значение2", . . . )</pre>
	* Удовлетворяющие фильтру записи возвращаются в результате, а
	* записи, которые не удовлетворяют условиям фильтра,
	* отбрасываются.<br><br> Допустимыми являются следующие модификаторы:
	* <ul> <li> <b> !</b> - отрицание,</li> <li> <b> +</b> - значения null, 0 и пустая строка
	* так же удовлетворяют условиям фильтра,</li> </ul> Допустимыми
	* являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	* или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	* поля строго больше передаваемой в фильтр величины;</li> <li> <b>&gt;=</b> -
	* значение поля меньше или равно передаваемой в фильтр величины;</li>
	* <li> <b>&gt;=</b> - значение поля строго меньше передаваемой в фильтр
	* величины;</li> <li> <b>@</b> - модификатор может использоваться для
	* целочисленных и вещественных данных при передаче набора
	* значений (массива). В этом случае при генерации sql-запроса будет
	* использован sql-оператор IN, дающий компактную форму записи.</li> <li>
	* <b>~</b> - значение поля проверяется на соответствие передаваемому в
	* фильтр шаблону;</li> <li> <b>%</b> - значение поля проверяется на
	* соответствие передаваемой в фильтр строке в соответствии с
	* языком запросов.</li> </ul> В качестве "название_поляX" может стоять
	* любое поле корзины.<br><br> Пример фильтра: <pre class="syntax">array("!~VALUE" =&gt;
	* "JOHN*")</pre> Этот фильтр означает "выбрать все записи, в которых
	* значение в поле VALUE не начинается с символов JOHN".<br><br> Значение по
	* умолчанию - пустой массив array() - означает, что результат
	* отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи корзины. Массив
	* имеет вид: <pre class="syntax">array("название_поля1", "группирующая_функция2"
	* =&gt; "название_поля2", ...)</pre> В качестве "название_поля<i>N</i>" может
	* стоять любое поле корзины. В качестве группирующей функции могут
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
	* ограничено сверху значением этого ключа<br> любой ключ,
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
	* @return CDBResult <p>Возвращается объект класса <b>CDBResult</b>, содержащий набор
	* ассоциативных массивов с ключами: </p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код записи. </td> </tr> <tr>
	* <td>BASKET_ID</td> <td>Код элемента корзины, к которому привязано данное
	* свойство.</td> </tr> <tr> <td>NAME</td> <td>Название свойства.</td> </tr> <tr> <td>VALUE</td>
	* <td>Значение свойства. </td> </tr> <tr> <td>CODE</td> <td>Символьный код свойства.
	* </td> </tr> <tr> <td>SORT</td> <td>Индекс сортировки свойства. </td> </tr> </table> <p>Если
	* в качестве параметра arGroupBy передается пустой массив, то метод
	* вернет число записей, удовлетворяющих фильтру.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выведем все свойства элемента корзины с кодом $basketID
	* $db_res = CSaleBasket::GetPropsList(
	*         array(
	*                 "SORT" =&gt; "ASC",
	*                 "NAME" =&gt; "ASC"
	*             ),
	*         array("BASKET_ID" =&gt; $basketID)
	*     );
	* while ($ar_res = $db_res-&gt;Fetch())
	* {
	*    echo $ar_res["NAME"]."=".$ar_res["VALUE"]."&lt;br&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalebasket/csalebasket__getpropslist.e03206e8.php
	* @author Bitrix
	*/
	public static function GetPropsList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
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
			if (is_array($arGroupBy))
				$arFilter = $arGroupBy;
			else
				$arFilter = array();
			$arGroupBy = false;
		}

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "BP.ID", "TYPE" => "int"),
				"BASKET_ID" => array("FIELD" => "BP.BASKET_ID", "TYPE" => "int"),
				"NAME" => array("FIELD" => "BP.NAME", "TYPE" => "string"),
				"VALUE" => array("FIELD" => "BP.VALUE", "TYPE" => "string"),
				"CODE" => array("FIELD" => "BP.CODE", "TYPE" => "string"),
				"SORT" => array("FIELD" => "BP.SORT", "TYPE" => "int")
			);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_basket_props BP ".
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
			"FROM b_sale_basket_props BP ".
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
				"FROM b_sale_basket_props BP ".
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

	//************** ADD, UPDATE, DELETE ********************//

	/**
	* Adds item to the basket.
	* Automatically adds Set items to the basket if Set parents is added
	*
	* @param $arFields
	* @return mixed - int ID or false
	*/
	
	/**
	* <p>Метод добавляет товар в корзину, если его ещё нет, и обновляет параметры товара с увеличением количества, если он уже находится в корзине. В массиве <b>arFields</b> перечисляются все параметры товара, которые нужны для работы модуля Интернет-магазина (т.е. этот модуль не зависит от других модулей и работает полностью самостоятельно). Метод динамичный.</p> <p>Интернет-магазин не зависит от других модулей, поэтому товары в корзину модуля продаж могут добавляться из любого места (например, из торгового каталога или со статической страницы). Для некоторых модулей существуют функции - оболочки, облегчающие добавление товара в корзину (например, для модуля <b>catalog</b> существуют функции <b>Add2Basket</b> и <b>Add2BasketByProductID</b>). </p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров элемента корзины, содержащий
	* следующие ключи: <ul> <li> <b>PRODUCT_ID</b> - уникальный в рамках модуля код
	* товара (обязательное поле);</li> <li> <b>PRODUCT_PRICE_ID</b> - ID (идентификатор)
	* конкретного ценового предложения товара, пришедшего в корзину.
	* Может быть использован в классе <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/index.php">CPrice</a> модуля Catalog для
	* получения детальной информации о цене.</li> <li> <b> PRICE</b> - стоимость
	* единицы товара (обязательное поле);</li> <li> <b>CURRENCY</b> - валюта
	* стоимости единицы товара (обязательное поле), если валюта
	* отличается от базовой валюты для данного сайта, то стоимость
	* будет автоматически сконвертирована по текущему курсу;</li> <li>
	* <b>WEIGHT</b> - вес единицы товара;</li> <li> <b>QUANTITY</b> - количество единиц
	* товара;</li> <li> <b>LID</b> - сайт, на котором сделана покупка
	* (обязательное поле);</li> <li> <b>DELAY</b> - флаг "товар отложен" (Y/N);</li> <li>
	* <b>CAN_BUY</b> - флаг "товар можно купить" (Y/N) - может устанавливаться
	* автоматически про наличии функции обратного вызова для
	* поддержки актуальности корзины;</li> <li> <b>NAME</b> - название товара
	* (обязательное поле);</li> <li> <b>PRODUCT_XML_ID</b> - внешний код товара
	* (необходим при обмене заказами с 1С);</li> <li> <b>CATALOG_XML_ID</b> - внешний
	* код каталога (необходим при обмене заказами с 1С);</li> <li> <b>ORDER_ID</b> -
	* идентификатор заказа. Ключ будет пустым, если товар еще не
	* добавлен в заказ;</li> <li> <b>CALLBACK_FUNC<font color="#FF0000">*</font></b> - название
	* функции обратного вызова для поддержки актуальности корзины
	* (описание ниже);</li> <li> <b>MODULE</b> - модуль, добавляющий товар в
	* корзину;</li> <li> <b>NOTES</b> - особые заметки, например, тип цены;</li> <li>
	* <b>ORDER_CALLBACK_FUNC<font color="#FF0000">*</font></b> - название функции обратного
	* вызова для оформления заказа (описание ниже);</li> <li> <b>DETAIL_PAGE_URL</b> -
	* ссылка на страницу детального просмотра товара;</li> <li>
	* <b>CANCEL_CALLBACK_FUNC<font color="#FF0000">*</font></b> - название функции обратного
	* вызова для отмены заказа (описание ниже);</li> <li> <b>PAY_CALLBACK_FUNC<font
	* color="#FF0000">*</font></b> - название функции обратного вызова, которая
	* вызывается при установке флага "Доставка разрешена" заказа;</li> <li>
	* <b>FUSER_ID</b> - идентификатор пользователя интернет-магазина,
	* необязательный параметр, по умолчанию CSaleBasket::GetBasketUserID() (текущий
	* пользователь);</li> <li> <b>DISCOUNT_PRICE</b> - величина скидки;</li> <li>
	* <b>DISCOUNT_NAME</b> - название скидки;</li> <li> <b>DISCOUNT_VALUE</b> - размер скидки (в
	* процентах);</li> <li> <b>DISCOUNT_COUPON</b> - купон скидки;</li> <li> <b>PROPS</b> - массив
	* свойств товара, который сохраняется в корзине. Каждый элемент
	* этого массива является массивом следующего формата: <pre
	* class="syntax"><code>array("NAME" =&gt; "Название свойства", "CODE" =&gt; "Код свойства",
	* "VALUE" =&gt; "Значение свойства", "SORT" =&gt; "Индекс сортировки")</code></pre> </li>
	* <li> <b>PRODUCT_PROVIDER_CLASS<font color="#0000FF">**</font></b> - содержит имя класса,
	* реализующего интерфейс <b> IBXSaleProductProvider</b>. Торговый каталог
	* записывает в это поле имя класса <b>CCatalogProductProvider</b>. Если поле
	* пусто, то возникает попытка использовать старые поля:<b>CALLBACK_FUNC</b>,
	* <b>ORDER_CALLBACK_FUNC</b>, <b>CANCEL_CALLBACK_FUNC</b> и <b>PAY_CALLBACK_FUNC</b>.</li> </ul>
	*
	* @return int <p>Метод возвращает код элемента корзины, в который попал данный
	* товар.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>if (CModule::IncludeModule("sale"))<br>{<br>  $arFields = array(<br>    "PRODUCT_ID" =&gt; 51,<br>    "PRODUCT_PRICE_ID" =&gt; 0,<br>    "PRICE" =&gt; 138.54,<br>    "CURRENCY" =&gt; "RUB",<br>    "WEIGHT" =&gt; 530,<br>    "QUANTITY" =&gt; 1,<br>    "LID" =&gt; LANG,<br>    "DELAY" =&gt; "N",<br>    "CAN_BUY" =&gt; "Y",<br>    "NAME" =&gt; "Чемодан кожаный",<br>    "CALLBACK_FUNC" =&gt; "MyBasketCallback",<br>    "MODULE" =&gt; "my_module",<br>    "NOTES" =&gt; "",<br>    "ORDER_CALLBACK_FUNC" =&gt; "MyBasketOrderCallback",<br>    "DETAIL_PAGE_URL" =&gt; "/".LANG."/detail.php?ID=51"<br>  );<br><br>  $arProps = array();<br><br>  $arProps[] = array(<br>    "NAME" =&gt; "Цвет",<br>    "CODE" =&gt; "color",<br>    "VALUE" =&gt; "черный"<br>  );<br><br>  $arProps[] = array(<br>    "NAME" =&gt; "Размер",<br>    "VALUE" =&gt; "1.5 x 2.5"<br>  );<br><br>  $arFields["PROPS"] = $arProps;<br><br>  CSaleBasket::Add($arFields);<br>}<br>?&gt;<br>
	* 
	* 
	* 
	* //пример на проверку создания покупателя
	* 
	* $userId=XXX;//id пользователя
	* //получаем FUSER_ID, если покупатель для данного пользователя существует
	* $FUSER_ID=CSaleUser::GetList(array('USER_ID' =&gt; $userId));
	* //если покупателя нет - создаем его
	* if(!$FUSER_ID['ID'])                  
	*        $FUSER_ID['ID']=CSaleUser::_Add(array("USER_ID" =&gt; $userId)); //обратите внимание на нижнее подчеркивание перед Add
	* //если не получается создать покупателя - то тут уж ничего не поделаешь
	* if(!$FUSER_ID['ID']){         
	*  echo "Error while creating SaleUser";      
	*  die();      
	* }
	* $FUSER_ID=$FUSER_ID['ID'];
	* //теперь переменную $FUSER_ID можно использовать для добавления товаров в корзину пользователя с $userId.
	* 
	* 
	* Смотрите также:
	* 
	* <li><a href="/api_help/catalog/interface.php">Взаимодействие торгового каталога и магазина</a></li>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalebasket/csalebasket__add.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB, $APPLICATION;

		if (isset($arFields["ID"]))
			unset($arFields["ID"]);

		$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N');

		CSaleBasket::Init();
		if (!CSaleBasket::CheckFields("ADD", $arFields))
			return false;

		if (!array_key_exists('IGNORE_CALLBACK_FUNC', $arFields) || 'Y' != $arFields['IGNORE_CALLBACK_FUNC'])
		{
			if ((array_key_exists("CALLBACK_FUNC", $arFields) && !empty($arFields["CALLBACK_FUNC"]))
				|| (array_key_exists("PRODUCT_PROVIDER_CLASS", $arFields) && !empty($arFields["PRODUCT_PROVIDER_CLASS"]))
			)
			{
				/** @var $productProvider IBXSaleProductProvider */
				if ($productProvider = CSaleBasket::GetProductProvider(array("MODULE" => $arFields["MODULE"], "PRODUCT_PROVIDER_CLASS" => $arFields["PRODUCT_PROVIDER_CLASS"])))
				{
					$providerParams = array(
						"PRODUCT_ID" => $arFields["PRODUCT_ID"],
						"QUANTITY" => $arFields["QUANTITY"],
						"RENEWAL" => $arFields["RENEWAL"],
						"USER_ID" => (isset($arFields["USER_ID"]) ? $arFields["USER_ID"] : 0),
						"SITE_ID" => (isset($arFields["LID"]) ? $arFields["LID"] : false),
					);
					if (isset($arFields['NOTES']))
						$providerParams['NOTES'] = $arFields['NOTES'];

					if (!($productProvider::GetProductData($providerParams)))
					{
						return false;
					}
				}
				else
				{
					if (!(CSaleBasket::ExecuteCallbackFunction(
						$arFields["CALLBACK_FUNC"],
						$arFields["MODULE"],
						$arFields["PRODUCT_ID"],
						$arFields["QUANTITY"],
						$arFields["RENEWAL"],
						$arFields["USER_ID"],
						$arFields["LID"]
					)))
					{
						return false;
					}
				}
			}
		}

		if ($isOrderConverted != "Y")
		{
			foreach(GetModuleEvents("sale", "OnBeforeBasketAdd", true) as $arEvent)
				if (ExecuteModuleEventEx($arEvent, Array(&$arFields))===false)
					return false;
		}

		$bFound = false;
		$bEqAr = false;

		//TODO: is order converted?
		if ($isOrderConverted == "Y")
		{
			/** @var \Bitrix\Sale\BasketItem $basketItem */
			if (!($basketItem = \Bitrix\Sale\Compatible\BasketCompatibility::add($arFields)))
			{
				$APPLICATION->ThrowException(Localization\Loc::getMessage('BT_MOD_SALE_BASKET_ERR_ID_ABSENT'), "BASKET_ITEM");
				return false;
			}

			$ID = $basketItem->getId();
			$arFields['QUANTITY'] = $basketItem->getQuantity();
		}
		else
		{
			$boolProps = (!empty($arFields["PROPS"]) && is_array($arFields["PROPS"]));

			// check if this item is already in the basket
			$arDuplicateFilter = array(
				"FUSER_ID" => $arFields["FUSER_ID"],
				"PRODUCT_ID" => $arFields["PRODUCT_ID"],
				"LID" => $arFields["LID"],
				"ORDER_ID" => "NULL"
			);

			if (!(isset($arFields["TYPE"]) && $arFields["TYPE"] == CSaleBasket::TYPE_SET))
			{
				if (isset($arFields["SET_PARENT_ID"]))
					$arDuplicateFilter["SET_PARENT_ID"] = $arFields["SET_PARENT_ID"];
				else
					$arDuplicateFilter["SET_PARENT_ID"] = "NULL";
			}

			$db_res = CSaleBasket::GetList(
				array(),
				$arDuplicateFilter,
				false,
				false,
				array("ID", "QUANTITY")
			);
			while($res = $db_res->Fetch())
			{
				if(!$bEqAr)
				{
					$arPropsCur = array();
					$arPropsOld = array();

					if ($boolProps)
					{
						foreach($arFields["PROPS"] as &$arProp)
						{
							if (array_key_exists('VALUE', $arProp)&& '' != $arProp["VALUE"])
							{
								$propID = '';
								if (array_key_exists('CODE', $arProp) && '' != $arProp["CODE"])
								{
									$propID = $arProp["CODE"];
								}
								elseif (array_key_exists('NAME', $arProp) && '' != $arProp["NAME"])
								{
									$propID = $arProp["NAME"];
								}
								if ('' == $propID)
									continue;
								$arPropsCur[$propID] = $arProp["VALUE"];
							}
						}
						if (isset($arProp))
							unset($arProp);
					}

					$dbProp = CSaleBasket::GetPropsList(
						array(),
						array("BASKET_ID" => $res["ID"]),
						false,
						false,
						array('NAME', 'VALUE', 'CODE')
					);
					while ($arProp = $dbProp->Fetch())
					{
						if ('' != $arProp["VALUE"])
						{
							$propID = '';
							if ('' != $arProp["CODE"])
							{
								$propID = $arProp["CODE"];
							}
							elseif ('' != $arProp["NAME"])
							{
								$propID = $arProp["NAME"];
							}
							if ('' == $propID)
								continue;
							$arPropsOld[$propID] = $arProp["VALUE"];
						}
					}

					$bEqAr = false;
					if (count($arPropsCur) == count($arPropsOld))
					{
						$bEqAr = true;
						foreach($arPropsCur as $key => $val)
						{
							if (!array_key_exists($key, $arPropsOld) || $arPropsOld[$key] != $val)
							{
								$bEqAr = false;
								break;
							}
						}
					}

					if ($bEqAr)
					{
						$ID = $res["ID"];
						$arFields["QUANTITY"] += $res["QUANTITY"];
						CSaleBasket::Update($ID, $arFields);
						$bFound = true;
						continue;
					}
				}
			}
		}

		if (!$bFound)
		{
			//TODO: is order converted?
			if ($isOrderConverted != "Y")
			{
				$arInsert = $DB->PrepareInsert("b_sale_basket", $arFields);

				$strSql = "INSERT INTO b_sale_basket(".$arInsert[0].", DATE_INSERT, DATE_UPDATE) VALUES(".$arInsert[1].", ".$DB->GetNowFunction().", ".$DB->GetNowFunction().")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

				$ID = intval($DB->LastID());

				$boolOrder = false;
				if (isset($arFields['ORDER_ID']))
				{
					$boolOrder = (0 < (int)$arFields['ORDER_ID']);
				}

				if (!$boolOrder && !CSaleBasketHelper::isSetItem($arFields))
				{
					$siteID = (isset($arFields["LID"])) ? $arFields["LID"] : SITE_ID;
					$_SESSION["SALE_BASKET_NUM_PRODUCTS"][$siteID]++;
				}

				if ($boolProps)
				{
					foreach ($arFields["PROPS"] as &$prop)
					{
						if ('' != $prop["NAME"])
						{
							$arInsert = $DB->PrepareInsert("b_sale_basket_props", $prop);

							$strSql = "INSERT INTO b_sale_basket_props(BASKET_ID, ".$arInsert[0].") VALUES(".$ID.", ".$arInsert[1].")";
							$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
						}
					}
					if (isset($prop))
						unset($prop);
				}

				// if item is set parent
				if (isset($arFields["TYPE"]) && $arFields["TYPE"] == CSaleBasket::TYPE_SET)
				{
					CSaleBasket::Update($ID, array("SET_PARENT_ID" => $ID));

					if (!isset($arFields["MANUAL_SET_ITEMS_INSERTION"])) // set items will be added separately (from admin form data)
					{
						/** @var $productProvider IBXSaleProductProvider */
						if ($productProvider = CSaleBasket::GetProductProvider($arFields))
						{
							if (method_exists($productProvider, "GetSetItems"))
							{
								$arSets = $productProvider::GetSetItems($arFields["PRODUCT_ID"], CSaleBasket::TYPE_SET, array('BASKET_ID' => $ID));

								if (is_array($arSets))
								{
									foreach ($arSets as $arSetData)
									{
										foreach ($arSetData["ITEMS"] as $setItem)
										{
											$setItem["SET_PARENT_ID"] = $ID;
											$setItem["LID"] = $arFields["LID"];
											$setItem["QUANTITY"] = $setItem["QUANTITY"] * $arFields["QUANTITY"];
											$setItem['FUSER_ID'] = $arFields['FUSER_ID'];
											CSaleBasket::Add($setItem);
										}
									}
								}
							}
						}
					}
				}
			}

			if ($boolOrder)
			{
				CSaleOrderChange::AddRecord(
					$arFields["ORDER_ID"],
					"BASKET_ADDED",
					array(
						"PRODUCT_ID" => $arFields["PRODUCT_ID"],
						"NAME" => $arFields["NAME"],
						"QUANTITY" => $arFields["QUANTITY"]
					)
				);
			}
		}

		if ($isOrderConverted != "Y")
		{
			foreach(GetModuleEvents("sale", "OnBasketAdd", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, Array($ID, $arFields));
		}

		return $ID;
	}

	
	/**
	* <p>Метод удаляет запись корзины с кодом ID. Метод динамичный.</p> <a name="examples"></a>
	*
	*
	* @param int $ID  
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CSaleBasket::Delete(22))
	*     echo "Запись успешно удалена";
	* ?&gt;
	* </htmМетод может использоваться и для удаления записей корзин, которые уже превращены в заказы. То есть, мы можем удалить запись из состава заказа. Но следует помнить, что после использования метода необходимо обновлять итоговую сумму заказа, например, так: 
	* 
	* 
	*  $contents = array();
	*  $dbBasketItems = CSaleBasket::GetList(
	*             array(
	*                "NAME" =&gt; "ASC",
	*                "ID" =&gt; "ASC"
	*             ),
	*             array(
	*               "LID" =&gt; SITE_ID,
	*               "ORDER_ID" =&gt; $order_id,
	*             )
	*          );
	*   while ($arItems = $dbBasketItems-&gt;Fetch()){
	*             $contents[] = $arItems;
	*          }
	*   $sum = 0;
	*   foreach($contents as $basket_item){
	*             if($basket_item['DISCOUNT_PRICE']&gt;0){
	*                $sum += $basket_item['DISCOUNT_PRICE']*$basket_item['QUANTITY'];
	*             }else{
	*                $sum += $basket_item['PRICE']*$basket_item['QUANTITY'];
	*             }
	*          }
	*    $arFields = array(
	*             "PRICE" =&gt; $sum,
	*          );
	*    CSaleOrder::Update($order_id, $arFields);
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalebasket/csalebasket__delete.e0d06223.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB, $APPLICATION;

		$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N');

		$ID = intval($ID);
		if (0 >= $ID)
			return false;

		if ($isOrderConverted == "Y")
		{
			/** @var \Bitrix\Sale\Result $r */
			$r = \Bitrix\Sale\Compatible\BasketCompatibility::delete($ID);
			if (!$r->isSuccess(true))
			{
				foreach($r->getErrorMessages() as $error)
				{
					$APPLICATION->ThrowException($error);
				}

				return false;
			}

			return true;
		}

		$rsBaskets = CSaleBasket::GetList(
			array(),
			array('ID' => $ID),
			false,
			false,
			array(
				'ID',
				'ORDER_ID',
				'PRODUCT_ID',
				'NAME',
				'SUBSCRIBE',
				'FUSER_ID',
				'TYPE',
				'SET_PARENT_ID'
			)
		);
		if (!($arBasket = $rsBaskets->Fetch()))
			return false;

		foreach(GetModuleEvents("sale", "OnBeforeBasketDelete", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array($ID))===false)
				return false;

		if (CSaleBasketHelper::isSetParent($arBasket))
		{
			$rsSetItems = CSaleBasket::GetList(
				array(),
				array("SET_PARENT_ID" => $ID, "TYPE" => ""),
				false,
				false,
				array(
					'ID',
					'ORDER_ID',
					'PRODUCT_ID',
					'NAME',
					'SUBSCRIBE',
					'FUSER_ID',
					'TYPE',
					'SET_PARENT_ID'
				)
			);
			while ($arSetItem = $rsSetItems->GetNext())
			{
				CSaleBasket::Delete($arSetItem["ID"]);
			}
		}

		if (0 < intval($arBasket["ORDER_ID"]))
			CSaleOrderChange::AddRecord($arBasket["ORDER_ID"], "BASKET_REMOVED", array("PRODUCT_ID" => $arBasket["PRODUCT_ID"], "NAME" => $arBasket["NAME"]));

		$DB->Query("DELETE FROM b_sale_basket_props WHERE BASKET_ID = ".$ID, true);

		if(intval($_SESSION["SALE_BASKET_NUM_PRODUCTS"][SITE_ID]) > 0 && !CSaleBasketHelper::isSetItem($arBasket))
			$_SESSION["SALE_BASKET_NUM_PRODUCTS"][SITE_ID]--;

		$DB->Query("DELETE FROM b_sale_store_barcode WHERE BASKET_ID = ".$ID, true);

		$DB->Query("DELETE FROM b_sale_basket WHERE ID = ".$ID, true);

		if ('Y' == $arBasket['SUBSCRIBE'] && array_key_exists('NOTIFY_PRODUCT', $_SESSION))
		{
			$intUserID = CSaleUser::GetUserID($arBasket['FUSER_ID']);
			if ($intUserID && array_key_exists($intUserID, $_SESSION['NOTIFY_PRODUCT']))
			{
				if (array_key_exists($arBasket['PRODUCT_ID'], $_SESSION['NOTIFY_PRODUCT'][$intUserID]))
				{
					unset($_SESSION['NOTIFY_PRODUCT'][$intUserID][$arBasket['PRODUCT_ID']]);
				}
			}
		}

		foreach(GetModuleEvents("sale", "OnBasketDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID));

		return true;
	}

	
	/**
	* <p>Метод удаляет из корзины все записи с внутренним кодом владельца fUserID. Метод динамичный.</p>
	*
	*
	* @param int $fUserID  Внутренний код владельца.
	*
	* @param  $bool  Если флаг равен false (по-умолчанию), то удаляются только записи из
	* корзины. Если флаг равен true, то удаляются и те записи, которые
	* относятся к уже сделанным заказам.
	*
	* @param bIncOrdere $d = false] 
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CSaleBasket::DeleteAll(3, False))
	*     echo "Корзина пользователя с внутренним кодом 3 успешно удалена";
	* ?&gt;
	* 
	* 
	* //пример вызова с автоопределением fUserID
	* &lt;?
	* CSaleBasket::DeleteAll(CSaleBasket::GetBasketUserID());
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalebasket/csalebasket__deleteall.e0d06223.php
	* @author Bitrix
	*/
	public static function DeleteAll($FUSER_ID = 0, $bIncOrdered = false)
	{
		global $DB, $APPLICATION;

		$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N');

		$bIncOrdered = ($bIncOrdered ? True : False);
		$FUSER_ID = intval($FUSER_ID);
		if ($FUSER_ID <= 0)
			return false;

		$arFilter = array("FUSER_ID" => $FUSER_ID);
		if (!$bIncOrdered)
			$arFilter["ORDER_ID"] = "NULL";

		$dbBasket = CSaleBasket::GetList(
			array(),
			$arFilter,
			false,
			false,
			array(
				'ID',
				'ORDER_ID',
				'PRODUCT_ID',
				'NAME',
			)
		);
		while ($arBasket = $dbBasket->Fetch())
		{
			if ($isOrderConverted == "Y")
			{
				/** @var \Bitrix\Sale\Result $r */
				$r = \Bitrix\Sale\Compatible\BasketCompatibility::delete($arBasket["ID"]);
				if (!$r->isSuccess(true))
				{
					foreach($r->getErrorMessages() as $error)
					{
						$APPLICATION->ThrowException($error);
					}

					return false;
				}
			}
			else
			{
				if (0 < intval($arBasket["ORDER_ID"]))
					CSaleOrderChange::AddRecord($arBasket["ORDER_ID"], "BASKET_REMOVED", array("PRODUCT_ID" => $arBasket["PRODUCT_ID"], "NAME" => $arBasket["NAME"]));

				$DB->Query("DELETE FROM b_sale_basket_props WHERE BASKET_ID = ".$arBasket["ID"], true);
				$DB->Query("DELETE FROM b_sale_store_barcode WHERE BASKET_ID = ".$arBasket["ID"], true);
				$DB->Query("DELETE FROM b_sale_basket WHERE ID = ".$arBasket["ID"], true);
			}
		}


		$_SESSION["SALE_BASKET_NUM_PRODUCTS"][SITE_ID] = 0;

		return true;
	}

	public static function GetLeave($arOrder = Array(), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = Array())
	{
		global $DB;
		if(empty($arSelectFields) || in_array("*", $arSelectFields))
			$arSelectFields = Array("FUSER_ID", "USER_ID", "QUANTITY_ALL", "PRICE_ALL", "PR_COUNT", "CURRENCY", "DATE_INSERT_MIN", "DATE_UPDATE_MAX", "LID", "USER_NAME", "USER_LAST_NAME", "USER_LOGIN", "USER_EMAIL");

		$arFields = array(
				"ID" => array("FIELD" => "B.ID", "TYPE" => "int"),
				"FUSER_ID" => array("FIELD" => "B.FUSER_ID", "TYPE" => "int"),
				"ORDER_ID" => array("FIELD" => "B.ORDER_ID", "TYPE" => "int"),
				"PRODUCT_ID" => array("FIELD" => "B.PRODUCT_ID", "TYPE" => "int"),
				"PRICE" => array("FIELD" => "B.PRICE", "TYPE" => "double"),
				"CURRENCY" => array("FIELD" => "B.CURRENCY", "TYPE" => "string"),
				"DATE_INSERT" => array("FIELD" => "B.DATE_INSERT", "TYPE" => "datetime"),
				"DATE_UPDATE" => array("FIELD" => "B.DATE_UPDATE", "TYPE" => "datetime"),
				"WEIGHT" => array("FIELD" => "B.WEIGHT", "TYPE" => "double"),
				"QUANTITY" => array("FIELD" => "B.QUANTITY", "TYPE" => "double"),
				"LID" => array("FIELD" => "B.LID", "TYPE" => "string"),
				"DELAY" => array("FIELD" => "B.DELAY", "TYPE" => "char"),
				"NAME" => array("FIELD" => "B.NAME", "TYPE" => "string"),
				"CAN_BUY" => array("FIELD" => "B.CAN_BUY", "TYPE" => "char"),
				"MODULE" => array("FIELD" => "B.MODULE", "TYPE" => "string"),
				"CALLBACK_FUNC" => array("FIELD" => "B.CALLBACK_FUNC", "TYPE" => "string"),
				"NOTES" => array("FIELD" => "B.NOTES", "TYPE" => "string"),
				"ORDER_CALLBACK_FUNC" => array("FIELD" => "B.ORDER_CALLBACK_FUNC", "TYPE" => "string"),
				"PAY_CALLBACK_FUNC" => array("FIELD" => "B.PAY_CALLBACK_FUNC", "TYPE" => "string"),
				"CANCEL_CALLBACK_FUNC" => array("FIELD" => "B.CANCEL_CALLBACK_FUNC", "TYPE" => "string"),
				"DETAIL_PAGE_URL" => array("FIELD" => "B.DETAIL_PAGE_URL", "TYPE" => "string"),
				"DISCOUNT_PRICE" => array("FIELD" => "B.DISCOUNT_PRICE", "TYPE" => "double"),
				"CATALOG_XML_ID" => array("FIELD" => "B.CATALOG_XML_ID", "TYPE" => "string"),
				"PRODUCT_XML_ID" => array("FIELD" => "B.PRODUCT_XML_ID", "TYPE" => "string"),
				"DISCOUNT_NAME" => array("FIELD" => "B.DISCOUNT_NAME", "TYPE" => "string"),
				"DISCOUNT_VALUE" => array("FIELD" => "B.DISCOUNT_VALUE", "TYPE" => "string"),
				"DISCOUNT_COUPON" => array("FIELD" => "B.DISCOUNT_COUPON", "TYPE" => "string"),
				"VAT_RATE" => array("FIELD" => "B.VAT_RATE", "TYPE" => "double"),
				"SUBSCRIBE" => array("FIELD" => "B.SUBSCRIBE", "TYPE" => "char"),
				"DIMENSIONS" => array("FIELD" => "B.DIMENSIONS", "TYPE" => "string"),
				"USER_ID" => array("FIELD" => "F.USER_ID", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_fuser F ON (F.ID = B.FUSER_ID)"),
				"QUANTITY_ALL" => array("FIELD" => "SUM(B.QUANTITY)", "TYPE" => "double"),
				"PRICE_ALL" => array("FIELD" => "SUM(B.QUANTITY*B.PRICE)", "TYPE" => "double"),
				"PR_COUNT" => array("FIELD" => "COUNT(B.ID)", "TYPE" => "int"),
				"DATE_INSERT_MIN" => array("FIELD" => "MIN(B.DATE_INSERT)", "TYPE" => "datetime"),
				"DATE_UPDATE_MAX" => array("FIELD" => "MAX(B.DATE_UPDATE)", "TYPE" => "datetime"),
				"NAME_SEARCH" => array("FIELD" => "U.NAME, U.LAST_NAME, U.SECOND_NAME, U.EMAIL, U.LOGIN, U.ID", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U ON (U.ID = F.USER_ID)"),
				"USER_NAME" => array("FIELD" => "U.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U ON (U.ID = F.USER_ID)"),
				"USER_LAST_NAME" => array("FIELD" => "U.LAST_NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U ON (U.ID = F.USER_ID)"),
				"USER_LOGIN" => array("FIELD" => "U.LOGIN", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U ON (U.ID = F.USER_ID)"),
				"USER_EMAIL" => array("FIELD" => "U.EMAIL", "TYPE" => "string", "FROM" => "LEFT JOIN b_user U ON (U.ID = F.USER_ID)"),
				"USER_GROUP_ID" => array("FIELD" => "UG.GROUP_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_user_group UG ON (UG.USER_ID = F.USER_ID)"),
			);

		$arFilter["ORDER_ID"] = false;
		if(!in_array("FUSER_ID", $arSelectFields))
			$arSelectFields[] = "FUSER_ID";
		if(!in_array("USER_ID", $arSelectFields))
			$arSelectFields[] = "USER_ID";
		if(!in_array("LID", $arSelectFields))
			$arSelectFields[] = "LID";

		$arFilterH = Array();
		if(!empty($arFilter))
		{
			foreach($arFilter as $k => $v)
			{
				if(strpos($k, "QUANTITY_ALL") !== false || strpos($k, "PRICE_ALL") !== false || strpos($k, "PR_COUNT") !== false)
				{
					$arFilterH[$k] = $v;
					unset($arFilter[$k]);
				}
			}
		}

		if(!empty($arFilterH))
			$arSqlsH = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilterH, false, $arSelectFields);
		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_sale_basket B ".
			"	".$arSqls["FROM"]." ";
		$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		$strSql .= "GROUP BY B.FUSER_ID, F.USER_ID, B.LID ";
		if (strlen($arSqlsH["WHERE"]) > 0)
			$strSql .= "HAVING ".$arSqlsH["WHERE"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";
		// echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])<=0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_sale_basket B ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";
			$strSql_tmp .= "GROUP BY B.FUSER_ID, F.USER_ID, B.LID ";
			if (strlen($arSqlsH["WHERE"]) > 0)
				$strSql_tmp .= "HAVING ".$arSqlsH["WHERE"]." ";

			// echo "!2.1!=".htmlspecialcharsbx($strSql_tmp)."<br>";

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = $dbRes->SelectedRowsCount();

			$dbRes = new CDBResult();

			// echo "!2.2!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])>0)
				$strSql .= "LIMIT ".IntVal($arNavStartParams["nTopCount"]);

			// echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}
}



/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleuser/index.php
 * @author Bitrix
 */
class CSaleUser extends CAllSaleUser
{
	public static function Add()
	{
		global $DB, $USER;

		$arFields = array(
				"=DATE_INSERT" => $DB->GetNowFunction(),
				"=DATE_UPDATE" => $DB->GetNowFunction(),
				"USER_ID" => (is_object($USER) && $USER->IsAuthorized() ? IntVal($USER->GetID()) : False),
				"CODE" => md5(time().randString(10)),
			);

		$ID = CSaleUser::_Add($arFields);
		$ID = IntVal($ID);

		$secure = false;
		if(COption::GetOptionString("sale", "use_secure_cookies", "N") == "Y" && CMain::IsHTTPS())
			$secure=1;

		if(COption::GetOptionString("sale", "encode_fuser_id", "N") == "Y")
		{
			$arRes = CSaleUser::GetList(array("ID" => $ID));
			if(!empty($arRes))
				$GLOBALS["APPLICATION"]->set_cookie("SALE_UID", $arRes["CODE"], false, "/", false, $secure, "Y", false);
		}
		else
		{
				$GLOBALS["APPLICATION"]->set_cookie("SALE_UID", $ID, false, "/", false, $secure, "Y", false);
		}

		return $ID;
	}

	public static function _Add($arFields)
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

		if (!CSaleUser::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_sale_fuser", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($arInsert[0])>0) $arInsert[0] .= ", ";
			$arInsert[0] .= $key;
			if (strlen($arInsert[1])>0) $arInsert[1] .= ", ";
			$arInsert[1] .= $value;
		}

		$strSql =
			"INSERT INTO b_sale_fuser(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		return $ID;
	}

	public static function DeleteOld($nDays)
	{
		global $DB;

		$nDays = IntVal($nDays);
		$strSql =
			"SELECT f.ID ".
			"FROM b_sale_fuser f ".
			"LEFT JOIN b_sale_order o ON (o.USER_ID = f.USER_ID) ".
			"WHERE ".
			"	TO_DAYS(f.DATE_UPDATE)<(TO_DAYS(NOW())-".$nDays.") ".
			"	AND o.ID is null ".
			"	AND f.USER_ID is null ".
			"LIMIT 300";

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($ar_res = $db_res->Fetch())
		{
			CSaleBasket::DeleteAll($ar_res["ID"], false);
			CSaleUser::Delete($ar_res["ID"]);
		}

		return true;
	}

	public static function GetBuyersList($arOrder = Array(), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = Array())
	{
		global $DB;
		if(empty($arSelectFields) || in_array("*", $arSelectFields))
			$arSelectFields = Array("ID", "ACTIVE", "LID", "DATE_REGISTER", "LOGIN", "EMAIL", "NAME", "LAST_NAME", "SECOND_NAME", "PERSONAL_PHONE", "USER_ID", "LAST_LOGIN", "TIMESTAMP_X", "PERSONAL_BIRTHDAY", "ORDER_COUNT", "ORDER_SUM", "CURRENCY", "LAST_ORDER_DATE");

		$arFields_m = array("ACTIVE", "LOGIN", "EMAIL", "NAME", "LAST_NAME", "SECOND_NAME", "PERSONAL_PHONE");
		$arFields_md = array("LAST_LOGIN", "DATE_REGISTER", "TIMESTAMP_X", "PERSONAL_BIRTHDAY");

		$CURRENCY = "";
		if(strlen($arFilter["CURRENCY"]) > 0)
		{
			$CURRENCY = $arFilter["CURRENCY"];
			unset($arFilter["CURRENCY"]);
		}
		else
		{
			CModule::IncludeModule("currency");
			$CURRENCY = CCurrency::GetBaseCurrency();
		}

		$LID = "";
		if(strlen($arFilter["LID"]) > 0)
		{
			$LID = $arFilter["LID"];
			unset($arFilter["LID"]);
		}
		else
		{
			$rsSites = CSite::GetList($by="id", $order="asc", array("ACTIVE" => "Y"));
			$arSite = $rsSites->Fetch();
			$LID = $arSite["ID"];
		}

		$arFields = array(
				"ID" => array("FIELD" => "F.ID", "TYPE" => "int"),
				"LID" => array("FIELD" => "O1.LID", "TYPE" => "string"),
				"ORDER_COUNT" => array("FIELD" => "(SELECT COUNT(O3.PRICE) FROM b_sale_order O3 WHERE O3.USER_ID=F.USER_ID AND O3.CURRENCY = '".$DB->ForSQL($CURRENCY)."' AND O3.PAYED = 'Y' AND O3.LID = '".$DB->ForSQL($LID)."' )", "TYPE" => "double"),
				"ORDER_SUM" => array("FIELD" => "(SELECT SUM(O3.PRICE) FROM b_sale_order O3 WHERE O3.USER_ID=F.USER_ID AND O3.CURRENCY = '".$DB->ForSQL($CURRENCY)."' AND O3.PAYED = 'Y' AND O3.LID = '".$DB->ForSQL($LID)."' )", "TYPE" => "double"),
				"CURRENCY" => array("FIELD" => "O1.CURRENCY", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_order O1 ON (O1.USER_ID=U.ID AND O1.CURRENCY = '".$DB->ForSQL($CURRENCY)."' AND O1.LID = '".$DB->ForSQL($LID)."' AND O1.PAYED = 'Y')"),
				"LAST_ORDER_DATE" => array("FIELD" => "(SELECT MAX(O2.DATE_INSERT) FROM b_sale_order O2 WHERE (O2.USER_ID=F.USER_ID))", "TYPE" => "datetime"),
				"NAME_SEARCH" => array("FIELD" => "U.NAME, U.LAST_NAME, U.SECOND_NAME, U.EMAIL, U.LOGIN, U.ID", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (U.ID = F.USER_ID)"),
				"USER_ID" => array("FIELD" => "F.USER_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_user U ON (U.ID = F.USER_ID)"),
				"GROUPS_ID" => array("FIELD" => "UG.GROUP_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_user_group UG ON (UG.USER_ID = F.USER_ID)"),
			);

		foreach($arFields_m as $val)
		{
			$arFields[$val] = array("FIELD" => "U.".$val, "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (U.ID = F.USER_ID)");
		}
		foreach($arFields_md as $val)
		{
			$arFields[$val] = array("FIELD" => "U.".$val, "TYPE" => "datetime", "FROM" => "INNER JOIN b_user U ON (U.ID = F.USER_ID)");
		}

		if(!in_array("USER_ID", $arSelectFields))
			$arSelectFields[] = "USER_ID";

		$arFilterH = Array();
		if(!empty($arFilter))
		{
			foreach($arFilter as $k => $v)
			{
				if(strpos($k, "ORDER_SUM") !== false || strpos($k, "ORDER_COUNT") !== false || strpos($k, "LAST_ORDER_DATE") !== false)
				{
					$arFilterH[$k] = $v;
					unset($arFilter[$k]);
				}
			}
		}

		if(!empty($arFilterH))
			$arSqlsH = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilterH, false, $arSelectFields);
		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_sale_fuser F ".
			"	".$arSqls["FROM"]." ";
		if(strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		$strSql .= "GROUP BY F.USER_ID ";
		if (strlen($arSqlsH["WHERE"]) > 0)
			$strSql .= "HAVING ".$arSqlsH["WHERE"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";
		// echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])<=0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_sale_fuser F ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";
			$strSql_tmp .= "GROUP BY F.USER_ID ";
			if (strlen($arSqlsH["WHERE"]) > 0)
				$strSql_tmp .= "HAVING ".$arSqlsH["WHERE"]." ";

			// echo "!2.1!=".htmlspecialcharsbx($strSql_tmp)."<br>";

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = $dbRes->SelectedRowsCount();

			$dbRes = new CDBResult();

			// echo "!2.2!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])>0)
				$strSql .= "LIMIT ".IntVal($arNavStartParams["nTopCount"]);

			// echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

	public static function GetUserID ($intFUserID)
	{
		global $DB;
		$intFUserID = intval($intFUserID);
		if (0 >= $intFUserID)
			return false;
		$strSql = "SELECT USER_ID FROM b_sale_fuser WHERE ID = ".$intFUserID;
		$rsUsers = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arUser = $rsUsers->Fetch())
		{
			return intval($arUser['USER_ID']);
		}
		return false;
	}
}


?>