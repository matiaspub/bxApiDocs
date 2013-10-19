<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/discount.php");


/**
 * 
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/index.php
 * @author Bitrix
 */
class CCatalogDiscount extends CAllCatalogDiscount
{
	static public function _Add(&$arFields)
	{
		global $DB;
		global $USER;
		global $stackCacheManager;

		$arFields1 = array();
		if (isset($USER) && $USER instanceof CUser && 'CUser' == get_class($USER))
		{
			if (!array_key_exists('CREATED_BY', $arFields) || intval($arFields["CREATED_BY"]) <= 0)
				$arFields["CREATED_BY"] = intval($USER->GetID());
			if (!array_key_exists('MODIFIED_BY', $arFields) || intval($arFields["MODIFIED_BY"]) <= 0)
				$arFields["MODIFIED_BY"] = intval($USER->GetID());
		}
		if (array_key_exists('TIMESTAMP_X', $arFields))
			unset($arFields['TIMESTAMP_X']);
		if (array_key_exists('DATE_CREATE', $arFields))
			unset($arFields['DATE_CREATE']);

		$arFields1['TIMESTAMP_X'] = $DB->GetNowFunction();
		$arFields1['DATE_CREATE'] = $DB->GetNowFunction();

		if (!CCatalogDiscount::CheckFields("ADD", $arFields, 0))
			return false;

		$stackCacheManager->Clear("catalog_discount");

		$arInsert = $DB->PrepareInsert("b_catalog_discount", $arFields);

		if (!empty($arFields1))
		{
			$arInsert[0] .= ', '.implode(', ',array_keys($arFields1));
			$arInsert[1] .= ', '.implode(', ',array_values($arFields1));
		}

		$strSql = "INSERT INTO b_catalog_discount(".$arInsert[0].") VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = intval($DB->LastID());

		return $ID;
	}

	static public function _Update($ID, &$arFields)
	{
		global $DB;
		global $stackCacheManager;
		global $USER;
		global $APPLICATION;

		$ID = intval($ID);
		if ($ID <= 0)
			return false;

		$arFields1 = array();

		if (array_key_exists('CREATED_BY',$arFields))
			unset($arFields['CREATED_BY']);
		if (array_key_exists('DATE_CREATE',$arFields))
			unset($arFields['DATE_CREATE']);
		if (array_key_exists('TIMESTAMP_X', $arFields))
			unset($arFields['TIMESTAMP_X']);
		if (isset($USER) && $USER instanceof CUser && 'CUser' == get_class($USER))
		{
			if (!array_key_exists('MODIFIED_BY', $arFields) || intval($arFields["MODIFIED_BY"]) <= 0)
				$arFields["MODIFIED_BY"] = intval($USER->GetID());
		}
		$arFields1['TIMESTAMP_X'] = $DB->GetNowFunction();

		if (!CCatalogDiscount::CheckFields("UPDATE", $arFields, $ID))
			return false;

		if (isset($arFields['VALUE']) != isset($arFields['VALUE_TYPE']))
		{
			$rsDiscounts = CCatalogDiscount::GetList(array(),array('ID' => $ID), false, array('nTopCount' => 1), array('ID', 'VALUE', 'VALUE_TYPE'));
			if ($arDiscount = $rsDiscounts->Fetch())
			{
				if (!isset($arFields['VALUE']))
					$arFields['VALUE'] = doubleval($arDiscount['VALUE']);
				if (!isset($arFields['VALUE_TYPE']))
					$arFields['VALUE_TYPE'] = $arDiscount['VALUE_TYPE'];
				if ('P' == $arFields['VALUE_TYPE'] && 100 < $arFields['VALUE'])
				{
					$APPLICATION->ThrowException(GetMessage("BT_MOD_CATALOG_DISC_ERR_BAD_VALUE"), "VALUE");
					return false;
				}
			}
			else
			{
				$APPLICATION->ThrowException(str_replace('#ID#', $ID, GetMessage("BT_MOD_CATALOG_DISC_ERR_BAD_ID")), "ID");
				return false;
			}
		}

		$stackCacheManager->Clear("catalog_discount");

		$strUpdate = $DB->PrepareUpdate("b_catalog_discount", $arFields);
		if (!empty($strUpdate))
		{
			$arAdd = array();
			if (!empty($arFields1))
			{
				foreach ($arFields1 as $key => $value)
				{
					$arAdd[] = $key."=".$value;
				}
				$strUpdate .= ', '.implode(', ', $arAdd);
			}

			$strSql = "UPDATE b_catalog_discount SET ".$strUpdate." WHERE ID = ".$ID." AND TYPE = ".DISCOUNT_TYPE_STANDART;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $ID;
	}

	
	/**
	 * <p>Метод удаляет скидку с кодом ID.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код удаляемой записи.
	 *
	 *
	 *
	 * @return bool <p>Метод возвращает <i>true</i> в случае успешного удаления и <i>false</i> в
	 * случае ошибки.</p><br><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.delete.php
	 * @author Bitrix
	 */
	static public function Delete($ID)
	{
		global $DB;
		global $stackCacheManager;

		$ID = intval($ID);

		foreach (GetModuleEvents("catalog", "OnBeforeDiscountDelete", true) as $arEvent)
		{
			if (false === ExecuteModuleEventEx($arEvent, array($ID)))
				return false;
		}

		$stackCacheManager->Clear("catalog_discount");

		$DB->Query("DELETE FROM b_catalog_discount_cond WHERE DISCOUNT_ID = ".$ID);
		$DB->Query("DELETE FROM b_catalog_discount_coupon WHERE DISCOUNT_ID = ".$ID);
		$DB->Query("DELETE FROM b_catalog_discount2iblock WHERE DISCOUNT_ID = ".$ID);
		$DB->Query("DELETE FROM b_catalog_discount2section WHERE DISCOUNT_ID = ".$ID);
		$DB->Query("DELETE FROM b_catalog_discount2product WHERE DISCOUNT_ID = ".$ID);


		$DB->Query("DELETE FROM b_catalog_discount WHERE ID = ".$ID." AND TYPE = ".DISCOUNT_TYPE_STANDART);

		CCatalogDiscount::SaveFilterOptions();

		foreach (GetModuleEvents("catalog", "OnDiscountDelete", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ID));
		}

		return true;
	}

	
	/**
	 * <p>Метод выбирает параметры скидки по ее коду ID.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код записи.
	 *
	 *
	 *
	 * @return array <p>Метод возвращает ассоциативный массив параметров скидки с
	 * ключами:</p><ul> <li> <b>ID</b> - код записи;</li> <li> <b>SITE_ID</b> - сайт;</li> <li>
	 * <b>ACTIVE</b> - флаг активности;</li> <li> <b>NAME</b> - название скидки;</li> <li>
	 * <b>COUPON</b> - код купона;</li> <li> <b>SORT</b> - индекс сортировки;</li> <li>
	 * <b>MAX_DISCOUNT</b> - максимальная величина скидки;</li> <li> <b>TIMESTAMP_X</b> - дата
	 * последнего изменения записи;</li> <li> <b>VALUE_TYPE</b> - тип скидки (P - в
	 * процентах, F - фиксированная величина);</li> <li> <b>VALUE</b> - величина
	 * скидки;</li> <li> <b>CURRENCY</b> - валюта;</li> <li> <b>RENEWAL</b> - флаг "Скидка на
	 * продление";</li> <li> <b>ACTIVE_FROM</b> - дата начала действия скидки;</li> <li>
	 * <b>ACTIVE_TO</b> - дата окончания действия скидки.</li> </ul><br><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getbyid.php
	 * @author Bitrix
	 */
	static public function GetByID($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID <= 0)
			return false;

		$strSql =
			"SELECT CD.ID, CD.SITE_ID, CD.ACTIVE, CD.NAME, CD.MAX_USES, ".
			"CD.COUNT_USES, CD.COUPON, CD.SORT, CD.MAX_DISCOUNT, CD.VALUE_TYPE, ".
			"CD.VALUE, CD.CURRENCY, CD.MIN_ORDER_SUM, CD.NOTES, CD.RENEWAL, ".
			$DB->DateToCharFunction("CD.TIMESTAMP_X", "FULL")." as TIMESTAMP_X, ".
			$DB->DateToCharFunction("CD.ACTIVE_FROM", "FULL")." as ACTIVE_FROM, ".
			$DB->DateToCharFunction("CD.ACTIVE_TO", "FULL")." as ACTIVE_TO, ".
			"CD.CREATED_BY, CD.MODIFIED_BY, ".$DB->DateToCharFunction('CD.DATE_CREATE', 'FULL').' as DATE_CREATE, '.
			"CD.PRIORITY, CD.LAST_DISCOUNT, CD.VERSION, CD.CONDITIONS, CD.UNPACK ".
			"FROM b_catalog_discount CD WHERE CD.ID = ".$ID." AND CD.TYPE = ".DISCOUNT_TYPE_STANDART;

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($res = $db_res->Fetch())
			return $res;

		return false;
	}

	static public function PrepareSection4Where($val, $key, $operation, $negative, $field, &$arField, &$arFilter)
	{
		$val = intval($val);
		if ($val <= 0)
			return false;

		$dbSection = CIBlockSection::GetByID($val);
		if ($arSection = $dbSection->Fetch())
		{
			$arIDs = array(0);
			$dbSectionTree = CIBlockSection::GetList(
				array("LEFT_MARGIN" => "DESC"),
				array(
					"IBLOCK_ID" => $arSection["IBLOCK_ID"],
					"ACTIVE" => "Y",
					"GLOBAL_ACTIVE" => "Y",
					"IBLOCK_ACTIVE" => "Y",
					">=LEFT_BORDER" => $arSection["LEFT_MARGIN"],
					"<=RIGHT_BORDER" => $arSection["RIGHT_MARGIN"]
				)
			);
			while ($arSectionTree = $dbSectionTree->Fetch())
			{
				$arIDs[] = intval($arSectionTree["ID"]);
			}

			return "(CDS.SECTION_ID ".(($negative == "Y") ? "NOT " : "")."IN (".implode(',',$arIDs)."))";
		}

		return false;
	}

	
	/**
	 * <p>Функция возвращает результат выборки записей скидок в соответствии со своими параметрами.</p>
	 *
	 *
	 *
	 *
	 * @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	 * записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	 * "направление_сортировки1", "название_поля2" =&gt;
	 * "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	 * может стоять любое поле скидки, а в качестве
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
	 * @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи. Массив имеет
	 * вид: <pre class="syntax">array( "[модификатор1][оператор1]название_поля1" =&gt;
	 * "значение1", "[модификатор2][оператор2]название_поля2" =&gt; "значение2",
	 * . . . )</pre> Удовлетворяющие фильтру записи возвращаются в
	 * результате, а записи, которые не удовлетворяют условиям фильтра,
	 * отбрасываются.<br><br> Допустимыми являются следующие модификаторы:
	 * <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> - значения null, 0 и пустая строка
	 * так же удовлетворяют условиям фильтра.</li> </ul> Допустимыми
	 * являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	 * или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	 * поля строго больше передаваемой в фильтр величины;</li> <li><b> -
	 * значение поля меньше или равно передаваемой в фильтр
	 * величины;</b></li> <li><b> - значение поля строго меньше передаваемой в
	 * фильтр величины;</b></li> <li> <b>@</b> - оператор может использоваться для
	 * целочисленных и вещественных данных при передаче набора
	 * значений (массива). В этом случае при генерации sql-запроса будет
	 * использован sql-оператор <b>IN</b>, дающий компактную форму записи;</li>
	 * <li> <b>~</b> - значение поля проверяется на соответствие
	 * передаваемому в фильтр шаблону;</li> <li> <b>%</b> - значение поля
	 * проверяется на соответствие передаваемой в фильтр строке в
	 * соответствии с языком запросов.</li> </ul> В качестве "название_поляX"
	 * может стоять любое поле скидки.<br><br> Пример фильтра: <pre
	 * class="syntax">array("!DISCOUNT_ID" =&gt; 15)</pre> Этот фильтр означает "выбрать все
	 * записи, в которых значение в поле DISCOUNT_ID (код скидки) не равно
	 * 15".<br><br> Значение по умолчанию - пустой массив array() - означает, что
	 * результат отфильтрован не будет.
	 *
	 *
	 *
	 * @param array $arGroupBy = false Массив полей, по которым группируются скидки. Массив имеет вид: <pre
	 * class="syntax">array("название_поля1", "название_поля2", . . .)</pre> В качестве
	 * "название_поля<i>N</i>" может стоять любое поле скидки.<br><br> Если
	 * массив пустой, то функция вернет число записей, удовлетворяющих
	 * фильтру.<br><br> Значение по умолчанию - <i>false</i> - означает, что
	 * результат группироваться не будет.
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
	 * @return CDBResult <p>Возвращается объект класса CDBResult, содержащий набор
	 * ассоциативных массивов с ключами:</p><ul> <li> <b>ID</b> - код записи;</li> <li>
	 * <b>SITE_ID</b> - сайт;</li> <li> <b>ACTIVE</b> - флаг активности;</li> <li> <b>NAME</b> -
	 * название скидки;</li> <li> <b>COUPON</b> - код купона;</li> <li> <b>SORT</b> - индекс
	 * сортировки;</li> <li> <b>MAX_DISCOUNT</b> - максимальная величина скидки;</li> <li>
	 * <b>TIMESTAMP_X</b> - дата последнего изменения записи;</li> <li> <b>VALUE_TYPE</b> - тип
	 * скидки (P - в процентах, F - фиксированая величина);</li> <li> <b>VALUE</b> -
	 * величина скидки;</li> <li> <b>CURRENCY</b> - валюта;</li> <li> <b>RENEWAL</b> - флаг
	 * "Скидка на продление";</li> <li> <b>ACTIVE_FROM</b> - дата начала действия
	 * скидки;</li> <li> <b>ACTIVE_TO</b> - дата окончания действия скидки;</li> <li>
	 * <b>PRODUCT_ID</b> - код товара, на который дается скидка;</li> <li> <b>SECTION_ID</b> -
	 * код группы товаров, на которую дается скидка;</li> <li> <b>GROUP_ID</b> - код
	 * группы пользователей, на которую дается скидка;</li> <li>
	 * <b>CATALOG_GROUP_ID</b> - код типа цен, на который дается скидка.</li> </ul><p>Если
	 * в качестве параметра arGroupBy передается пустой массив, то функция
	 * вернет число записей, удовлетворяющих фильтру.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // Выберем все скидки для данного товара
	 * 
	 * $dbProductDiscounts = CCatalogDiscount::GetList(
	 *     array("SORT" =&gt; "ASC"),
	 *     array(
	 *             "+PRODUCT_ID" =&gt; $PRODUCT_ID,
	 *             "ACTIVE" =&gt; "Y",
	 *             "!&gt;ACTIVE_FROM" =&gt; $DB-&gt;FormatDate(date("Y-m-d H:i:s"), 
	 *                                                "YYYY-MM-DD HH:MI:SS",
	 *                                                CSite::GetDateFormat("FULL")),
	 *             "!&lt;ACTIVE_TO" =&gt; $DB-&gt;FormatDate(date("Y-m-d H:i:s"), 
	 *                                              "YYYY-MM-DD HH:MI:SS", 
	 *                                              CSite::GetDateFormat("FULL")),
	 *             "COUPON" =&gt; ""
	 *         ),
	 *     false,
	 *     false,
	 *     array(
	 *             "ID", "SITE_ID", "ACTIVE", "ACTIVE_FROM", "ACTIVE_TO", 
	 *             "RENEWAL", "NAME", "SORT", "MAX_DISCOUNT", "VALUE_TYPE", 
	 *     "VALUE", "CURRENCY", "PRODUCT_ID"
	 *         )
	 *     );
	 * while ($arProductDiscounts = $dbProductDiscounts-&gt;Fetch())
	 * {
	 *     * * *
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getlist.php
	 * @author Bitrix
	 */
	static public function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		$arFields = array(
			"ID" => array("FIELD" => "CD.ID", "TYPE" => "int"),
			"XML_ID" => array("FIELD" => "CD.XML_ID", "TYPE" => "string"),
			"SITE_ID" => array("FIELD" => "CD.SITE_ID", "TYPE" => "string"),
			"TYPE" => array("FIELD" => "CD.TYPE", "TYPE" => "int"),
			"ACTIVE" => array("FIELD" => "CD.ACTIVE", "TYPE" => "char"),
			"ACTIVE_FROM" => array("FIELD" => "CD.ACTIVE_FROM", "TYPE" => "datetime"),
			"ACTIVE_TO" => array("FIELD" => "CD.ACTIVE_TO", "TYPE" => "datetime"),
			"RENEWAL" => array("FIELD" => "CD.RENEWAL", "TYPE" => "char"),
			"NAME" => array("FIELD" => "CD.NAME", "TYPE" => "string"),
			"MAX_USES" => array("FIELD" => "CD.MAX_USES", "TYPE" => "int"),
			"COUNT_USES" => array("FIELD" => "CD.COUNT_USES", "TYPE" => "int"),
			"SORT" => array("FIELD" => "CD.SORT", "TYPE" => "int"),
			"MAX_DISCOUNT" => array("FIELD" => "CD.MAX_DISCOUNT", "TYPE" => "double"),
			"VALUE_TYPE" => array("FIELD" => "CD.VALUE_TYPE", "TYPE" => "char"),
			"VALUE" => array("FIELD" => "CD.VALUE", "TYPE" => "double"),
			"CURRENCY" => array("FIELD" => "CD.CURRENCY", "TYPE" => "string"),
			"MIN_ORDER_SUM" => array("FIELD" => "CD.MIN_ORDER_SUM", "TYPE" => "double"),
			"TIMESTAMP_X" => array("FIELD" => "CD.TIMESTAMP_X", "TYPE" => "datetime"),
			"MODIFIED_BY" => array("FIELD" => "CD.MODIFIED_BY", "TYPE" => "int"),
			"DATE_CREATE" => array("FIELD" => "CD.DATE_CREATE", "TYPE" => "datetime"),
			"CREATED_BY" => array("FIELD" => "CD.CREATED_BY", "TYPE" => "int"),
			"NOTES" => array("FIELD" => "CD.NOTES", "TYPE" => "string"),
			"PRIORITY" => array("FIELD" => "CD.PRIORITY", "TYPE" => "int"),
			"LAST_DISCOUNT" => array("FIELD" => "CD.LAST_DISCOUNT", "TYPE" => "char"),
			"VERSION" => array("FIELD" => "CD.VERSION", "TYPE" => "int"),
			"CONDITIONS" => array("FIELD" => "CD.CONDITIONS", "TYPE" => "string"),
			"UNPACK" => array("FIELD" => "CD.UNPACK", "TYPE" => "string"),

			"PRODUCT_ID" => array("FIELD" => "CDP.PRODUCT_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_catalog_discount2product CDP ON (CD.ID = CDP.DISCOUNT_ID)"),
			"SECTION_ID" => array("FIELD" => "CDS.SECTION_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_catalog_discount2section CDS ON (CD.ID = CDS.DISCOUNT_ID)", "WHERE" => array("CCatalogDiscount", "PrepareSection4Where")),
			"SECTION_LIST" => array("FIELD" => "CDSL.SECTION_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_catalog_discount2section CDSL ON (CD.ID = CDSL.DISCOUNT_ID)"),
			"IBLOCK_ID" => array("FIELD" => "CDI.IBLOCK_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_catalog_discount2iblock CDI ON (CD.ID = CDI.DISCOUNT_ID)"),
			"GROUP_ID" => array("FIELD" => "CDC.USER_GROUP_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_catalog_discount_cond CDC ON (CD.ID = CDC.DISCOUNT_ID)"),
			"USER_GROUP_ID" => array("FIELD" => "CDC.USER_GROUP_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_catalog_discount_cond CDC ON (CD.ID = CDC.DISCOUNT_ID)"),
			"CATALOG_GROUP_ID" => array("FIELD" => "CDC.PRICE_TYPE_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_catalog_discount_cond CDC ON (CD.ID = CDC.DISCOUNT_ID)"),
			"PRICE_TYPE_ID" => array("FIELD" => "CDC.PRICE_TYPE_ID", "TYPE" => "int", "FROM" => "LEFT JOIN b_catalog_discount_cond CDC ON (CD.ID = CDC.DISCOUNT_ID)"),

			"COUPON" => array("FIELD" => "CDCP.COUPON", "TYPE" => "string", "FROM" => "LEFT JOIN b_catalog_discount_coupon CDCP ON (CD.ID = CDCP.DISCOUNT_ID)"),
			"COUPON_ACTIVE" => array("FIELD" => "CDCP.ACTIVE", "TYPE" => "char", "FROM" => "LEFT JOIN b_catalog_discount_coupon CDCP ON (CD.ID = CDCP.DISCOUNT_ID)"),
			"COUPON_ONE_TIME" => array("FIELD" => "CDCP.ONE_TIME", "TYPE" => "char", "FROM" => "LEFT JOIN b_catalog_discount_coupon CDCP ON (CD.ID = CDCP.DISCOUNT_ID)"),
		);

		if (!is_array($arFilter))
			$arFilter = array();
		$arFilter['TYPE'] = DISCOUNT_TYPE_STANDART;

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_discount CD ".$arSqls["FROM"];
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

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_discount CD ".$arSqls["FROM"];
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
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_discount CD ".$arSqls["FROM"];
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

	
	/**
	 * <p>Функция возвращает результат выборки записей с информацией о привязке скидок к группам пользователей в соответствии со своими параметрами.</p>
	 *
	 *
	 *
	 *
	 * @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	 * записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	 * "направление_сортировки1", "название_поля2" =&gt;
	 * "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	 * может стоять любое поле записи, а в качестве
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
	 * @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи. Массив имеет
	 * вид: <pre class="syntax">array( "[модификатор1][оператор1]название_поля1" =&gt;
	 * "значение1", "[модификатор2][оператор2]название_поля2" =&gt; "значение2",
	 * . . . )</pre> Удовлетворяющие фильтру записи возвращаются в
	 * результате, а записи, которые не удовлетворяют условиям фильтра,
	 * отбрасываются.<br><br> Допустимыми являются следующие модификаторы:
	 * <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> - значения null, 0 и пустая строка
	 * так же удовлетворяют условиям фильтра.</li> </ul> Допустимыми
	 * являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	 * или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	 * поля строго больше передаваемой в фильтр величины;</li> <li> <b>&gt;=</b> -
	 * значение поля меньше или равно передаваемой в фильтр величины;</li>
	 * <li> <b>&gt;=</b> - значение поля строго меньше передаваемой в фильтр
	 * величины;</li> <li> <b>@</b> - оператор может использоваться для
	 * целочисленных и вещественных данных при передаче набора
	 * значений (массива). В этом случае при генерации sql-запроса будет
	 * использован sql-оператор <b>IN</b>, дающий компактную форму записи;</li>
	 * <li> <b>~</b> - значение поля проверяется на соответствие
	 * передаваемому в фильтр шаблону;</li> <li> <b>%</b> - значение поля
	 * проверяется на соответствие передаваемой в фильтр строке в
	 * соответствии с языком запросов.</li> </ul> В качестве "название_поляX"
	 * может стоять любое поле записи.<br><br> Пример фильтра: <pre
	 * class="syntax">array("!GROUP_ID" =&gt; 5)</pre> Этот фильтр означает "выбрать все
	 * записи, в которых значение в поле GROUP_ID (код группы пользователей)
	 * не равно 5".<br><br> Значение по умолчанию - пустой массив array() -
	 * означает, что результат отфильтрован не будет.
	 *
	 *
	 *
	 * @param array $arGroupBy = false Массив полей, по которым группируются записи. Массив имеет вид: <pre
	 * class="syntax">array("название_поля1", "название_поля2", . . .)</pre> В качестве
	 * "название_поля<i>N</i>" может стоять любое поле записи. <br><br> Если
	 * массив пустой, то функция вернет число записей, удовлетворяющих
	 * фильтру.<br><br> Значение по умолчанию - <i>false</i> - означает, что
	 * результат группироваться не будет.
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
	 * @return CDBResult <p>Возвращается объект класса CDBResult, содержащий набор
	 * ассоциативных массивов с ключами:</p><ul> <li> <b>ID</b> - код записи;</li> <li>
	 * <b>DISCOUNT_ID</b> - код скидки;</li> <li> <b>GROUP_ID</b> - код группы
	 * пользователей.</li> </ul><p>Если в качестве параметра arGroupBy передается
	 * пустой массив, то функция вернет число записей, удовлетворяющих
	 * фильтру.</p><br><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getdiscountgroupslist.php
	 * @author Bitrix
	 */
	static public function GetDiscountGroupsList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		return self::__GetDiscountEntityList($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields);
	}

	
	/**
	 * <p>Функция возвращает результат выборки записей с информацией о привязке скидок к типам цен в соответствии со своими параметрами.</p>
	 *
	 *
	 *
	 *
	 * @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	 * записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	 * "направление_сортировки1", "название_поля2" =&gt;
	 * "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	 * может стоять любое поле записи, а в качестве
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
	 * @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи. Массив имеет
	 * вид: <pre class="syntax">array( "[модификатор1][оператор1]название_поля1" =&gt;
	 * "значение1", "[модификатор2][оператор2]название_поля2" =&gt; "значение2",
	 * . . . )</pre> Удовлетворяющие фильтру записи возвращаются в
	 * результате, а записи, которые не удовлетворяют условиям фильтра,
	 * отбрасываются.<br><br> Допустимыми являются следующие модификаторы:
	 * <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> - значения null, 0 и пустая строка
	 * так же удовлетворяют условиям фильтра.</li> </ul> Допустимыми
	 * являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	 * или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	 * поля строго больше передаваемой в фильтр величины;</li> <li> <b>&gt;=</b> -
	 * значение поля меньше или равно передаваемой в фильтр величины;</li>
	 * <li> <b>&gt;=</b> - значение поля строго меньше передаваемой в фильтр
	 * величины;</li> <li> <b>@</b> - оператор может использоваться для
	 * целочисленных и вещественных данных при передаче набора
	 * значений (массива). В этом случае при генерации sql-запроса будет
	 * использован sql-оператор <b>IN</b>, дающий компактную форму записи;</li>
	 * <li> <b>~</b> - значение поля проверяется на соответствие
	 * передаваемому в фильтр шаблону;</li> <li> <b>%</b> - значение поля
	 * проверяется на соответствие передаваемой в фильтр строке в
	 * соответствии с языком запросов.</li> </ul> В качестве "название_поляX"
	 * может стоять любое поле записи.<br><br> Пример фильтра: <pre
	 * class="syntax">array("!DISCOUNT_ID" =&gt; 15)</pre> Этот фильтр означает "выбрать все
	 * записи, в которых значение в поле DISCOUNT_ID (код скидки) не равно
	 * 15".<br><br> Значение по умолчанию - пустой массив array() - означает, что
	 * результат отфильтрован не будет.
	 *
	 *
	 *
	 * @param array $arGroupBy = false Массив полей, по которым группируются записи. Массив имеет вид: <pre
	 * class="syntax">array("название_поля1", "название_поля2", . . .)</pre> В качестве
	 * "название_поля<i>N</i>" может стоять любое поле записи. <br><br> Если
	 * массив пустой, то функция вернет число записей, удовлетворяющих
	 * фильтру.<br><br> Значение по умолчанию - <i>false</i> - означает, что
	 * результат группироваться не будет.
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
	 * @return CDBResult <p>Возвращается объект класса CDBResult, содержащий набор
	 * ассоциативных массивов с ключами:</p><ul> <li>ID - код записи;</li>
	 * <li>DISCOUNT_ID - код скидки;</li> <li>CATALOG_GROUP_ID - код типа цены.</li> </ul><p>Если в
	 * качестве параметра arGroupBy передается пустой массив, то функция
	 * вернет число записей, удовлетворяющих фильтру.</p><br><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getdiscountcatslist.php
	 * @author Bitrix
	 */
	static public function GetDiscountCatsList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		return self::__GetDiscountEntityList($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields);
	}

/*
* @deprecated deprecated since catalog 12.0.0
*/
	
	/**
	 * <p>Функция возвращает результат выборки записей с информацией о привязке скидок к товарам в соответствии со своими параметрами.</p>
	 *
	 *
	 *
	 *
	 * @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	 * записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	 * "направление_сортировки1", "название_поля2" =&gt;
	 * "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	 * может стоять любое поле записи, а в качестве
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
	 * @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи. Массив имеет
	 * вид: <pre class="syntax">array( "[модификатор1][оператор1]название_поля1" =&gt;
	 * "значение1", "[модификатор2][оператор2]название_поля2" =&gt; "значение2",
	 * . . . )</pre> Удовлетворяющие фильтру записи возвращаются в
	 * результате, а записи, которые не удовлетворяют условиям фильтра,
	 * отбрасываются.<br><br> Допустимыми являются следующие модификаторы:
	 * <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> - значения null, 0 и пустая строка
	 * так же удовлетворяют условиям фильтра.</li> </ul> Допустимыми
	 * являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	 * или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	 * поля строго больше передаваемой в фильтр величины;</li> <li> <b>&gt;=</b> -
	 * значение поля меньше или равно передаваемой в фильтр величины;</li>
	 * <li> <b>&gt;=</b> - значение поля строго меньше передаваемой в фильтр
	 * величины;</li> <li> <b>@</b> - оператор может использоваться для
	 * целочисленных и вещественных данных при передаче набора
	 * значений (массива). В этом случае при генерации sql-запроса будет
	 * использован sql-оператор <b>IN</b>, дающий компактную форму записи;</li>
	 * <li> <b>~</b> - значение поля проверяется на соответствие
	 * передаваемому в фильтр шаблону;</li> <li> <b>%</b> - значение поля
	 * проверяется на соответствие передаваемой в фильтр строке в
	 * соответствии с языком запросов.</li> </ul> В качестве "название_поляX"
	 * может стоять любое поле записи.<br><br> Пример фильтра: <pre
	 * class="syntax">array("!DISCOUNT_ID" =&gt; 15)</pre> Этот фильтр означает "выбрать все
	 * записи, в которых значение в поле DISCOUNT_ID (код скидки) не равно
	 * 15".<br><br> Значение по умолчанию - пустой массив array() - означает, что
	 * результат отфильтрован не будет.
	 *
	 *
	 *
	 * @param array $arGroupBy = false Массив полей, по которым группируются записи. Массив имеет вид: <pre
	 * class="syntax">array("название_поля1", "название_поля2", . . .)</pre> В качестве
	 * "название_поля<i>N</i>" может стоять любое поле записи. <br><br> Если
	 * массив пустой, то функция вернет число записей, удовлетворяющих
	 * фильтру.<br><br> Значение по умолчанию - <i>false</i> - означает, что
	 * результат группироваться не будет.
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
	 * @return CDBResult <p>Возвращается объект класса CDBResult, содержащий набор
	 * ассоциативных массивов с ключами:</p><ul> <li> <b>ID</b> - код записи;</li> <li>
	 * <b>DISCOUNT_ID</b> - код скидки;</li> <li> <b>PRODUCT_ID</b> - код товара.</li> </ul><p>Если в
	 * качестве параметра arGroupBy передается пустой массив, то функция
	 * вернет число записей, удовлетворяющих фильтру.</p><br><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getdiscountproductslist.php
	 * @author Bitrix
	 */
	static public function GetDiscountProductsList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		$arFields = array(
			"ID" => array("FIELD" => "DG.ID", "TYPE" => "int"),
			"DISCOUNT_ID" => array("FIELD" => "DG.DISCOUNT_ID", "TYPE" => "int"),
			"PRODUCT_ID" => array("FIELD" => "DG.PRODUCT_ID", "TYPE" => "int")
		);

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_discount2product DG ".$arSqls["FROM"];
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

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_discount2product DG ".$arSqls["FROM"];
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
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_discount2product DG ".$arSqls["FROM"];
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

/*
* @deprecated deprecated since catalog 12.0.0
*/
	
	/**
	 * <p>Функция возвращает результат выборки записей с информацией о привязке скидок к группам товаров в соответствии со своими параметрами.</p>
	 *
	 *
	 *
	 *
	 * @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	 * записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	 * "направление_сортировки1", "название_поля2" =&gt;
	 * "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	 * может стоять любое поле записи, а в качестве
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
	 * @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи. Массив имеет
	 * вид: <pre class="syntax">array( "[модификатор1][оператор1]название_поля1" =&gt;
	 * "значение1", "[модификатор2][оператор2]название_поля2" =&gt; "значение2",
	 * . . . )</pre> Удовлетворяющие фильтру записи возвращаются в
	 * результате, а записи, которые не удовлетворяют условиям фильтра,
	 * отбрасываются.<br><br> Допустимыми являются следующие модификаторы:
	 * <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> - значения null, 0 и пустая строка
	 * так же удовлетворяют условиям фильтра.</li> </ul> Допустимыми
	 * являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	 * или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	 * поля строго больше передаваемой в фильтр величины;</li> <li> <b>&gt;=</b> -
	 * значение поля меньше или равно передаваемой в фильтр величины;</li>
	 * <li> <b>&gt;=</b> - значение поля строго меньше передаваемой в фильтр
	 * величины;</li> <li> <b>@</b> - оператор может использоваться для
	 * целочисленных и вещественных данных при передаче набора
	 * значений (массива). В этом случае при генерации sql-запроса будет
	 * использован sql-оператор <b>IN</b>, дающий компактную форму записи;</li>
	 * <li> <b>~</b> - значение поля проверяется на соответствие
	 * передаваемому в фильтр шаблону;</li> <li> <b>%</b> - значение поля
	 * проверяется на соответствие передаваемой в фильтр строке в
	 * соответствии с языком запросов.</li> </ul> В качестве "название_поляX"
	 * может стоять любое поле записи.<br><br> Пример фильтра: <pre
	 * class="syntax">array("!DISCOUNT_ID" =&gt; 15)</pre> Этот фильтр означает "выбрать все
	 * записи, в которых значение в поле DISCOUNT_ID (код скидки) не равно
	 * 15".<br><br> Значение по умолчанию - пустой массив array() - означает, что
	 * результат отфильтрован не будет.
	 *
	 *
	 *
	 * @param array $arGroupBy = false Массив полей, по которым группируются записи. Массив имеет вид: <pre
	 * class="syntax">array("название_поля1", "название_поля2", . . .)</pre> В качестве
	 * "название_поля<i>N</i>" может стоять любое поле записи. <br><br> Если
	 * массив пустой, то функция вернет число записей, удовлетворяющих
	 * фильтру.<br><br> Значение по умолчанию - <i>false</i> - означает, что
	 * результат группироваться не будет.
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
	 * @return CDBResult <p>Возвращается объект класса CDBResult, содержащий набор
	 * ассоциативных массивов с ключами:</p><ul> <li> <b>ID</b> - код записи;</li> <li>
	 * <b>DISCOUNT_ID</b> - код скидки;</li> <li> <b>PRODUCT_ID</b> - код товара.</li> </ul><p>Если в
	 * качестве параметра arGroupBy передается пустой массив, то функция
	 * вернет число записей, удовлетворяющих фильтру.</p><br><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getdiscountsectionslist.php
	 * @author Bitrix
	 */
	static public function GetDiscountSectionsList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		$arFields = array(
			"ID" => array("FIELD" => "DG.ID", "TYPE" => "int"),
			"DISCOUNT_ID" => array("FIELD" => "DG.DISCOUNT_ID", "TYPE" => "int"),
			"SECTION_ID" => array("FIELD" => "DG.SECTION_ID", "TYPE" => "int")
		);

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_discount2section DG ".$arSqls["FROM"];
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

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_discount2section DG ".$arSqls["FROM"];
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
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_discount2section DG ".$arSqls["FROM"];
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

/*
* @deprecated deprecated since catalog 12.0.0
*/
	static public function GetDiscountIBlocksList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		$arFields = array(
			"ID" => array("FIELD" => "DG.ID", "TYPE" => "int"),
			"DISCOUNT_ID" => array("FIELD" => "DG.DISCOUNT_ID", "TYPE" => "int"),
			"IBLOCK_ID" => array("FIELD" => "DG.IBLOCK_ID", "TYPE" => "int")
		);

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_discount2iblock DG ".$arSqls["FROM"];
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

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_discount2iblock DG ".$arSqls["FROM"];
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
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_discount2iblock DG ".$arSqls["FROM"];
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

	protected function __GetDiscountEntityList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		$arFields = array(
			"ID" => array("FIELD" => "DC.ID", "TYPE" => "int"),
			"DISCOUNT_ID" => array("FIELD" => "DC.DISCOUNT_ID", "TYPE" => "int"),
			"CATALOG_GROUP_ID" => array("FIELD" => "DC.PRICE_TYPE_ID", "TYPE" => "int"),
			"PRICE_TYPE_ID" => array("FIELD" => "DC.PRICE_TYPE_ID", "TYPE" => "int"),
			"USER_GROUP_ID" => array("FIELD" => "DC.USER_GROUP_ID", "TYPE" => "int"),
			"GROUP_ID" => array("FIELD" => "DC.USER_GROUP_ID", "TYPE" => "int"),
		);

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_discount_cond DC ".$arSqls["FROM"];
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

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_discount_cond DC ".$arSqls["FROM"];
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
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_discount_cond DC ".$arSqls["FROM"];
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

/*
* @deprecated deprecated since catalog 12.0.0
*/
	static public function SaveFilterOptions()
	{
		COption::SetOptionString("catalog", "do_use_discount_product", 'Y');

		COption::SetOptionString("catalog", "do_use_discount_section", 'Y');

		COption::SetOptionString("catalog", "do_use_discount_iblock", 'Y');

		self::__SaveFilterForEntity(array('ENTITY_ID' => 'PRICE_TYPE_ID', 'OPTION_ID' => 'do_use_discount_cat_group'));
		self::__SaveFilterForEntity(array('ENTITY_ID' => 'USER_GROUP_ID', 'OPTION_ID' => 'do_use_discount_group'));
	}

	protected function __SaveFilterForEntity($arParams)
	{
		global $DB;

		if (!is_array($arParams) || empty($arParams))
			return;
		$strFilter = 'N';
		$arDiscList = array();
		$strQuery = str_replace('#ENTITY_ID#', $arParams['ENTITY_ID'], "SELECT DISCOUNT_ID FROM b_catalog_discount_cond WHERE #ENTITY_ID# != -1");
		$rsDiscounts = $DB->Query($strQuery, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arDiscount = $rsDiscounts->Fetch())
		{
			$arDiscList[] = intval($arDiscount['DISCOUNT_ID']);
		}
		if (!empty($arDiscList))
		{
			$arDiscList = array_unique($arDiscList);
			$strQuery = "SELECT 'x' FROM b_catalog_discount D WHERE ID IN (".implode(',', $arDiscList).") AND D.ACTIVE = 'Y' AND (D.ACTIVE_TO > ".$DB->CurrentTimeFunction()." OR D.ACTIVE_TO IS NULL) LIMIT 0, 1";
			$rsDiscounts = $DB->Query($strQuery, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arDiscount= $rsDiscounts->Fetch())
				$strFilter = 'Y';
		}
		COption::SetOptionString("catalog", $arParams['OPTION_ID'], $strFilter);
	}

	protected function __UpdateSubdiscount($intDiscountID, &$arConditions)
	{
		global $APPLICATION;
		global $DB;

		$arMsg = array();
		$boolResult = true;

		$intDiscountID = intval($intDiscountID);
		if (0 >= $intDiscountID)
		{
			$arMsg[] = array('id' => 'ID', "text" => GetMessage('BT_MOD_CATALOG_DISC_ERR_DISCOUNT_ID_ABSENT'));
			$boolResult = false;
		}
		if (!is_array($arConditions) || empty($arConditions))
		{
			$arMsg[] = array('id' => 'SUBDISCOUNT', "text" => GetMessage('BT_MOD_CATALOG_DISC_ERR_SUBDISCOUNT_ROWS_ABSENT'));
			$boolResult = false;
		}

		$arEmptyRow = array(
			'DISCOUNT_ID' => $intDiscountID,
			'USER_GROUP_ID' => -1,
			'PRICE_TYPE_ID' => -1,
		);

		if ($boolResult)
		{
			$strQuery = 'DELETE from b_catalog_discount_cond where DISCOUNT_ID = '.$intDiscountID;
			$DB->Query($strQuery,  false, "File: ".__FILE__."<br>Line: ".__LINE__);

			foreach ($arConditions as $arOneCondition)
			{
				$arRow = $arEmptyRow;
				if (!empty($arOneCondition['EQUAL']) && is_array($arOneCondition['EQUAL']))
				{
					foreach ($arOneCondition['EQUAL'] as $strKey => $intOneEntity)
					{
						$arRow[$strKey] = $intOneEntity;
					}
				}
				$arInsert = $DB->PrepareInsert("b_catalog_discount_cond", $arRow);
				$strInserCond = "INSERT INTO b_catalog_discount_cond(".$arInsert[0].") VALUES(".$arInsert[1].")";
				$DB->Query($strInserCond, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			if (isset($arOneCondition))
				unset($arOneCondition);
		}

		return $boolResult;
	}

	protected function __GetDiscountID($arFilter)
	{
		global $DB;

		$arResult = array();
		$boolRest = array_key_exists('RESTRICTIONS', $arFilter);

		$arFields = array(
			"DISCOUNT_ID" => array("FIELD" => "DC.DISCOUNT_ID", "TYPE" => "int"),
			"USER_GROUP_ID" => array("FIELD" => "DC.USER_GROUP_ID", "TYPE" => "int"),
			"PRICE_TYPE_ID" => array("FIELD" => "DC.PRICE_TYPE_ID", "TYPE" => "int"),
		);

		if (!isset($arFilter['USER_GROUP_ID']))
			$arFilter['USER_GROUP_ID'] = array();
		elseif (!is_array($arFilter['USER_GROUP_ID']))
			$arFilter['USER_GROUP_ID'] = array($arFilter['USER_GROUP_ID']);
		if (!empty($arFilter['USER_GROUP_ID']))
			$arFilter['USER_GROUP_ID'][] = -1;
		else
			unset($arFilter['USER_GROUP_ID']);

		if (!isset($arFilter['PRICE_TYPE_ID']))
			$arFilter['PRICE_TYPE_ID'] = array();
		elseif (!is_array($arFilter['PRICE_TYPE_ID']))
			$arFilter['PRICE_TYPE_ID'] = array($arFilter['PRICE_TYPE_ID']);
		if (!empty($arFilter['PRICE_TYPE_ID']))
			$arFilter['PRICE_TYPE_ID'][] = -1;
		else
			unset($arFilter['PRICE_TYPE_ID']);

		if (array_key_exists('DISCOUNT_ID', $arFilter))
			unset($arFilter['DISCOUNT_ID']);

		$arSelectFields = array('DISCOUNT_ID');
		if ($boolRest)
		{
			$arSelectFields[] = 'USER_GROUP_ID';
			$arSelectFields[] = 'PRICE_TYPE_ID';
			unset($arFilter['RESTRICTIONS']);
		}

		$arSqls = CCatalog::PrepareSql($arFields, array(), $arFilter, false, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);
		if (empty($arSqls["WHERE"]))
			$arSqls["WHERE"] = "1=1";

		$strQuery = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_discount_cond DC WHERE ".$arSqls["WHERE"];

		$arDiscountID = array();
		$rsDiscounts = $DB->Query($strQuery, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($boolRest)
		{
			$arRestrictions = array();
			while ($arDiscount = $rsDiscounts->Fetch())
			{
				$arDiscount['DISCOUNT_ID'] = intval($arDiscount['DISCOUNT_ID']);
				$arDiscountID[$arDiscount['DISCOUNT_ID']] = true;
				if (!array_key_exists($arDiscount['DISCOUNT_ID'], $arRestrictions))
				{
					$arRestrictions[$arDiscount['DISCOUNT_ID']] = array(
						'USER_GROUP' => array(),
						'PRICE_TYPE' => array(),
					);
				}
				$arDiscount['USER_GROUP_ID'] = intval($arDiscount['USER_GROUP_ID']);
				$arDiscount['PRICE_TYPE_ID'] = intval($arDiscount['PRICE_TYPE_ID']);
				$arRestrictions[$arDiscount['DISCOUNT_ID']]['USER_GROUP'][$arDiscount['USER_GROUP_ID']] = true;
				$arRestrictions[$arDiscount['DISCOUNT_ID']]['PRICE_TYPE'][$arDiscount['PRICE_TYPE_ID']] = true;
			}
			if (!empty($arDiscountID))
			{
				$arDiscountID = array_keys($arDiscountID);
				foreach ($arRestrictions as $intKey => $arOneRestrictions)
				{
					if (array_key_exists(-1, $arOneRestrictions['USER_GROUP']))
						$arOneRestrictions['USER_GROUP'] = array();
					if (array_key_exists(-1, $arOneRestrictions['PRICE_TYPE']))
						$arOneRestrictions['PRICE_TYPE'] = array();
					$arRestrictions[$intKey] = $arOneRestrictions;
				}
			}
			$arResult = array(
				'DISCOUNTS' => $arDiscountID,
				'RESTRICTIONS' => $arRestrictions,
			);
		}
		else
		{
			while ($arDiscount = $rsDiscounts->Fetch())
			{
				$arDiscount['DISCOUNT_ID'] = intval($arDiscount['DISCOUNT_ID']);
				$arDiscountID[$arDiscount['DISCOUNT_ID']] = true;
			}
			if (!empty($arDiscountID))
				$arResult = array_keys($arDiscountID);
		}
		return $arResult;
	}

	protected function __UpdateOldEntities($ID, &$arFields, $boolUpdate)
	{
		$ID = intval($ID);
		if (0 >= $ID)
			return;
		CCatalogDiscount::__UpdateOldOneEntity($ID, $arFields,
			array(
				'ENTITY_ID' => 'IBLOCK_IDS',
				'TABLE_ID' => 'b_catalog_discount2iblock',
				'FIELD_ID' => 'IBLOCK_ID',
			),
			$boolUpdate
		);
		CCatalogDiscount::__UpdateOldOneEntity($ID, $arFields,
			array(
				'ENTITY_ID' => 'SECTION_IDS',
				'TABLE_ID' => 'b_catalog_discount2section',
				'FIELD_ID' => 'SECTION_ID',
			),
			$boolUpdate
		);
		CCatalogDiscount::__UpdateOldOneEntity($ID, $arFields,
			array(
				'ENTITY_ID' => 'PRODUCT_IDS',
				'TABLE_ID' => 'b_catalog_discount2product',
				'FIELD_ID' => 'PRODUCT_ID',
			),
			$boolUpdate
		);
	}

	protected function __FillArrays($intDiscountID, &$arFields, $strEntityID)
	{
		$boolResult = false;
		$intDiscountID = intval($intDiscountID);
		if (0 >= $intDiscountID)
			return $boolResult;
		$strEntityID = trim(strval($strEntityID));
		if (!empty($strEntityID) && ('GROUP_IDS' == $strEntityID || 'CATALOG_GROUP_IDS' == $strEntityID))
		{
			$boolCheck = false;
			$strEntityResult = ('GROUP_IDS' == $strEntityID ? 'USER_GROUP_ID' : 'PRICE_TYPE_ID');
			$arValues = array();
			$rsDiscounts = self::__GetDiscountEntityList(
				array(),
				array('DISCOUNT_ID' => $intDiscountID),
				false,
				false,
				array('ID', 'DISCOUNT_ID', $strEntityResult)
			);
			while ($arDiscount = $rsDiscounts->Fetch())
			{
				$boolCheck = true;
				$intValue = intval($arDiscount[$strEntityResult]);
				if (0 < $intValue)
					$arValues[$intValue] = true;
			}
			if ($boolCheck)
			{
				$arFields[$strEntityID] = (!empty($arValues) ? array_keys($arValues) : array());
				$boolResult = true;
			}
		}
		return $boolResult;
	}
}
?>