<?
use Bitrix\Catalog;
use Bitrix\Sale\DiscountCouponsManager;
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/discount_coupon.php");


/**
 * <b>CCatalogDiscountCoupon</b> - класс для работы с купонами скидок. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/index.php
 * @author Bitrix
 */
class CCatalogDiscountCoupon extends CAllCatalogDiscountCoupon
{
	
	/**
	* <p>Метод добавляет купон для выбранной скидки. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров нового купона, ключами в котором
	* являются названия параметров, а значениями - соответствующие
	* значения: <ul> <li> <b>DISCOUNT_ID</b> - код (ID) скидки (обязательный)</li> <li>
	* <b>ACTIVE</b> - активность купона (Y|N) (необязательный), по умолчанию - Y</li>
	* <li> <b>ONE_TIME</b> - флаг одноразовости купона (необязательный). Может
	* принимать одно из трёх значений: <b>Y</b> - на одну позицию заказа,
	* <b>O</b> - на весь заказ, <b>N</b> - многоразовый, по умолчанию - <b>Y</b>.</li> <li>
	* <b>COUPON</b> - код купона (обязательный)</li> <li> <b>DATE_APPLY</b> - дата
	* применения купона (необязательный), если указать - одноразовый
	* купон будет считаться использованным</li> <li> <b>DESCRIPTION</b> -
	* комментарий (необязательный)</li> </ul> Необязательные ключи,
	* отсутствующие в массиве, получат значения по умолчанию.
	*
	* @param boolean $bAffectDataFile = True Необязательный параметр, указывающий на необходимость
	* перегенерировать файл скидок и купонов. Эти действия
	* осуществляет метод CCatalogDiscount::GenerateDataFile(). <br><br><div class="note">
	* <b>Примечание:</b> начиная с версии 12.0 параметр не требуется, т.к. с
	* этой версии больше не используется файловый кеш скидок.</div>
	*
	* @return mixed <p>Метод возвращает код (ID) купона в случае успешного создания и
	* <i>false</i>, если произошла ошибка. Для получения детальной
	* информации об ошибке следует вызвать $APPLICATION-&gt;GetException().</p> <p>Перед
	* добавлением записи в таблицу осуществляется проверка параметров
	* привязки методом CCatalogDiscountCoupon::CheckFields. Если проверка прошла
	* успешно, производится запись в базу.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* if (CModule::IncludeModule("catalog"))
	* {
	* 	$COUPON = CatalogGenerateCoupon();
	* 
	* 	$arCouponFields = array(
	* 		"DISCOUNT_ID" =&gt; "4",
	* 		"ACTIVE" =&gt; "Y",
	* 		"ONE_TIME" =&gt; "Y",
	* 		"COUPON" =&gt; $COUPON,
	* 		"DATE_APPLY" =&gt; false
	* 	);
	* 
	* 	$CID = CCatalogDiscountCoupon::Add($arCouponFields);
	* 	$CID = IntVal($CID);
	* 	if ($CID &lt;= 0)
	* 	{
	* 		$ex = $APPLICATION-&gt;GetException();
	* 		$errorMessage = $ex-&gt;GetString();
	* 		echo $errorMessage;
	* 	}
	* }
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/fields.php">Структура таблицы</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/checkfields.php">CCatalogDiscountCoupon::CheckFields</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/update.php">CCatalogDiscountCoupon::Update</a></li>
	* </ul> </ht<p>Перед использованием метода необходимо сгенерировать код
	* купона функцией <b>CatalogGenerateCoupon()</b>.</p> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/add.php
	* @author Bitrix
	*/
	static public function Add($arFields, $bAffectDataFile = true)
	{
		static $eventOnBeforeAddExists = null;
		static $eventOnAddExists = null;
		global $DB;

		if ($eventOnBeforeAddExists === true || $eventOnBeforeAddExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnBeforeCouponAdd', true) as $arEvent)
			{
				$eventOnBeforeAddExists = true;
				if (ExecuteModuleEventEx($arEvent, array(&$arFields, &$bAffectDataFile)) === false)
					return false;
			}
			if ($eventOnBeforeAddExists === null)
				$eventOnBeforeAddExists = false;
		}

		$bAffectDataFile = false;

		if (!CCatalogDiscountCoupon::CheckFields("ADD", $arFields, 0))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_discount_coupon", $arFields);

		$strSql = "INSERT INTO b_catalog_discount_coupon(".$arInsert[0].") VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = (int)$DB->LastID();

		if ($eventOnAddExists === true || $eventOnAddExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnCouponAdd', true) as $arEvent)
			{
				$eventOnAddExists = true;
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));
			}
			if ($eventOnAddExists === null)
				$eventOnAddExists = false;
		}

		return $ID;
	}

	
	/**
	* <p>Метод обновляет информацию о купоне. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код (ID) купона.
	*
	* @param array $arFields  Ассоциативный массив параметров купона. Может содержать
	* следующие ключи: <ul> <li> <b>DISCOUNT_ID</b> - код (ID) скидки;</li> <li> <b>ACTIVE</b> -
	* активность купона (Y|N);</li> <li> <b>ONE_TIME</b> - флаг одноразовости купона
	* (Y|N);</li> <li> <b>COUPON</b> - код купона;</li> <li> <b>DATE_APPLY</b> - дата применения
	* купона;</li> <li> <b>DESCRIPTION</b> - комментарий.</li> </ul> Ключи, не указанные в
	* массиве, изменяться не будут.<br> Если массив пустой, обращения к
	* базе не будет.
	*
	* @return mixed <p>Метод возвращает код (ID) купона, если запись существует, была
	* успешно изменена либо не изменялась (пустой массив) и <i>false</i> -
	* если произошла ошибка. Для получения детальной информации об
	* ошибке следует вызвать $APPLICATION-&gt;GetException().</p> <p>Перед изменением
	* записи в таблице осуществляется проверка параметров привязки
	* методом <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/checkfields.php">CCatalogDiscountCoupon::CheckFields</a>.
	* Если проверка прошла успешно и массив не пуст, запись
	* изменяется.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/fields.php">Структура таблицы</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/checkfields.php">CCatalogDiscountCoupon::CheckFields</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/add.php">CCatalogDiscountCoupon::Add</a></li>
	* </ul> </ht<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/update.php
	* @author Bitrix
	*/
	static public function Update($ID, $arFields)
	{
		static $eventOnBeforeUpdateExists = null;
		static $eventOnUpdateExists = null;
		global $DB;

		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		if ($eventOnBeforeUpdateExists === true || $eventOnBeforeUpdateExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnBeforeCouponUpdate', true) as $arEvent)
			{
				$eventOnBeforeUpdateExists = true;
				if (ExecuteModuleEventEx($arEvent, array($ID, &$arFields)) === false)
					return false;
			}
			if ($eventOnBeforeUpdateExists === null)
				$eventOnBeforeUpdateExists = false;
		}

		if (!CCatalogDiscountCoupon::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_catalog_discount_coupon", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "UPDATE b_catalog_discount_coupon SET ".$strUpdate." WHERE ID = ".$ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		if ($eventOnUpdateExists === true || $eventOnUpdateExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnCouponUpdate', true) as $arEvent)
			{
				$eventOnUpdateExists = true;
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));
			}
			if ($eventOnUpdateExists === null)
				$eventOnUpdateExists = false;
		}

		return $ID;
	}

	
	/**
	* <p>Метод удаляет купон. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код (ID) купона.
	*
	* @param boolean $bAffectDataFile = True Необязательный параметр, указывающий на необходимость
	* перегенерировать файл скидок и купонов. Эти действия
	* осуществляет метод CCatalogDiscount::GenerateDataFile(). <br><br> Начиная с версии 12.0
	* параметр не требуется, т.к. с этой версии больше не используется
	* файловый кеш скидок.
	*
	* @return mixed <p>Метод возвращает true в случае успешного удаления и false, если
	* произошла ошибка.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/delete.php
	* @author Bitrix
	*/
	static public function Delete($ID, $bAffectDataFile = true)
	{
		static $eventOnBeforeDeleteExists = null;
		static $eventOnDeleteExists = null;
		global $DB;

		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		if ($eventOnBeforeDeleteExists === true || $eventOnBeforeDeleteExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnBeforeCouponDelete', true) as $arEvent)
			{
				$eventOnBeforeDeleteExists = true;
				if (ExecuteModuleEventEx($arEvent, array($ID, &$bAffectDataFile)) === false)
					return false;
			}
			if ($eventOnBeforeDeleteExists === null)
				$eventOnBeforeDeleteExists = false;
		}

		$bAffectDataFile = false;

		$DB->Query("DELETE FROM b_catalog_discount_coupon WHERE ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($eventOnDeleteExists === true || $eventOnDeleteExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnCouponDelete', true) as $arEvent)
			{
				$eventOnDeleteExists = true;
				ExecuteModuleEventEx($arEvent, array($ID));
			}
			if ($eventOnDeleteExists === null)
				$eventOnDeleteExists = false;
		}

		return true;
	}

	
	/**
	* <p>Метод удаляет все купоны для выбранной скидки и перегенерирует файл скидок и купонов. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код (ID) скидки.
	*
	* @param boolean $bAffectDataFile = true Необязательный параметр, указывающий на необходимость
	* перегенерировать файл скидок и купонов. Эти действия
	* осуществляются методами CCatalogDiscount::ClearFile() и CCatalogDiscount::GenerateDataFile().
	* <br><br><div class="note"> <b>Примечание:</b> начиная с версии 12.0 параметр не
	* требуется, т.к. с этой версии больше не используется файловый кеш
	* скидок.</div>
	*
	* @return boolean <p>Возвращает <i>true</i> в случае успеха и <i>false</i>, если произошла
	* ошибка.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/deletebydiscountid.php
	* @author Bitrix
	*/
	static public function DeleteByDiscountID($ID, $bAffectDataFile = true)
	{
		global $DB;

		$bAffectDataFile = false;
		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		$DB->Query("DELETE FROM b_catalog_discount_coupon WHERE DISCOUNT_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	
	/**
	* <p>Метод возвращает информацию о купоне с заданным ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код (ID) купона.
	*
	* @return mixed <p>Метод возвращает массив параметров купона либо <i>false</i>, если
	* купон с таким ID не найден.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* <b>Выбор купона</b>
	* $ID = 40;
	* $arCoupon = CCatalogDiscountCoupon::GetByID($ID);
	* if (empty($arCoupon))
	* {
	* 	ShowError('Купон не найден');
	* }
	* else
	* {
	* 	echo 'Код купона: '.$arCoupon['COUPON'];
	* }
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/fields.php">Структура таблицы</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/getlist.php">CCatalogDiscountCoupon::GetList</a></li>
	* </ul> </ht
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/getbyid.php
	* @author Bitrix
	*/
	static public function GetByID($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID <= 0)
			return false;

		$strSql =
			"SELECT CD.ID, CD.DISCOUNT_ID, CD.ACTIVE, CD.COUPON, CD.ONE_TIME, ".
			$DB->DateToCharFunction("CD.DATE_APPLY", "FULL")." as DATE_APPLY, ".
			$DB->DateToCharFunction("CD.TIMESTAMP_X", "FULL")." as TIMESTAMP_X, ".
			"CD.CREATED_BY, CD.MODIFIED_BY, ".$DB->DateToCharFunction('CD.DATE_CREATE', 'FULL').' as DATE_CREATE, '.
			"CD.DESCRIPTION FROM b_catalog_discount_coupon CD WHERE CD.ID = ".$ID;

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
	* <p>Метод выбирает купоны, соответствующие условиям. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив вида array(by1=&gt;order1[, by2=&gt;order2 [, ..]]), где by - поле для сортировки,
	* может принимать значения: <ul> <li> <b>ID</b> - код (ID) купона;</li> <li>
	* <b>DISCOUNT_ID</b> - код (ID) скидки;</li> <li> <b>ACTIVE</b> - активность купона;</li> <li>
	* <b>ONE_TIME</b> - флаг однократного использования купона;</li> <li> <b>COUPON</b> -
	* код купона;</li> <li> <b>DATE_APPLY</b> - дата применения купона;</li> </ul> поле order
	* - направление сортировки, может принимать значения: <ul> <li> <b>asc</b> -
	* по возрастанию;</li> <li> <b>desc</b> - по убыванию.</li> </ul> Необязательный.
	* По умолчанию купоны не сортируются.
	*
	* @param array $arFilter = array() Массив параметров, по которым строится фильтр выборки. Имеет вид:
	* <pre class="syntax">array( "[модификатор1][оператор1]название_поля1" =&gt;
	* "значение1", "[модификатор2][оператор2]название_поля2" =&gt; "значение2",
	* . . . )</pre> Удовлетворяющие фильтру записи возвращаются в
	* результате, а записи, которые не удовлетворяют условиям фильтра,
	* отбрасываются.<br> Допустимыми являются следующие модификаторы:
	* <ul> <li> <b>!</b> - отрицание;</li> <li> <b>+</b> - значения null, 0 и пустая строка
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
	* соответствии с языком запросов.</li> </ul> "название поля" может
	* принимать значения: <ul> <li> <b>ID</b> - код (ID) купона (число);</li> <li>
	* <b>DISCOUNT_ID</b> - код (ID) скидки (число);</li> <li> <b>ACTIVE</b> - фильтр по
	* активности (Y|N); передача пустого значения ("ACTIVE"=&gt;"") выводит
	* купоны без учета их состояния (строка);</li> <li> <b>ONE_TIME</b> - флаг
	* однократного использования купона (Y|N); передача пустого значения
	* ("ONE_TIME"=&gt;"") выводит купоны без учета их типа (строка);</li> <li> <b>COUPON</b>
	* - код купона (маска);</li> <li> <b>DATE_APPLY</b> - дата применения купона
	* (дата);</li> <li> <b>DESCRIPTION</b> - комментарий (маска);</li> </ul> Значения
	* фильтра - одиночное значение или массив значений.<br>
	* Необязательное. По умолчанию купоны не фильтруются.
	*
	* @param mixed $arGroupBy = false Массив полей для группировки купонов. имеет вид: <pre
	* class="syntax">array("название_поля1", "название_поля2", . . .)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле каталога. <br><br> Если
	* массив пустой, то метод вернет число записей, удовлетворяющих
	* фильтру. <br> Значение по умолчанию - <i>false</i> - означает, что
	* результат группироваться не будет.
	*
	* @param mixed $arNavStartParams = false Массив параметров выборки. Может содержать следующие ключи: <ul>
	* <li>"<b>nTopCount</b>" - количество возвращаемых методом записей будет
	* ограничено сверху значением этого ключа;</li> <li>любой ключ,
	* принимаемый методом <b> CDBResult::NavQuery</b> в качестве третьего
	* параметра.</li> </ul> Необязательный. По умолчанию false - купоны не
	* ограничиваются.
	*
	* @param array $arSelectFields = array() Массив полей записей, которые будут возвращены методом. Можно
	* указать следующие поля <i>ID</i>, <i>DISCOUNT_ID</i>, <i>ACTIVE</i>, <i>ONE_TIME</i>, <i>COUPON</i>,
	* <i>DATE_APPLY</i>, <i>DISCOUNT_NAME</i> и <i>DESCRIPTION</i>.<br> Если в массиве присутствует
	* значение "*", то будут возвращены все доступные поля.<br>
	* Необязательный. По умолчанию выводятся все поля.
	*
	* @return CDBResult <p>Метод возвращает объект класса CDBResult.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* if (CModule::IncludeModule("catalog"))
	* { 
	*    $arFilter = array('COUPON' =&gt; 'CP-R8RFW-5YGGPZJ'); 
	*    $dbCoupon = CCatalogDiscountCoupon::GetList (array(), $arFilter); 
	*    if($arCoupon = $dbCoupon-&gt;Fetch()) 
	*    { 
	*        echo "&lt;pre&gt;"; 
	*        print_r($arCoupon); 
	*        echo "&lt;/pre&gt;"; 
	*    } 
	*    else
	*    { 
	*        echo "купона нет"; 
	*    } 
	* }
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/fields.php">Структура таблицы</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/getbyid.php">CCatalogDiscountCoupon::GetByID</a></li>
	* </ul> </ht
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/getlist.php
	* @author Bitrix
	*/
	static public function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		$arFields = array(
			"ID" => array("FIELD" => "CD.ID", "TYPE" => "int"),
			"DISCOUNT_ID" => array("FIELD" => "CD.DISCOUNT_ID", "TYPE" => "string"),
			"ACTIVE" => array("FIELD" => "CD.ACTIVE", "TYPE" => "char"),
			"ONE_TIME" => array("FIELD" => "CD.ONE_TIME", "TYPE" => "char"),
			"COUPON" => array("FIELD" => "CD.COUPON", "TYPE" => "string"),
			"DATE_APPLY" => array("FIELD" => "CD.DATE_APPLY", "TYPE" => "datetime"),
			"DISCOUNT_NAME" => array("FIELD" => "CDD.NAME", "TYPE" => "string", "FROM" => "LEFT JOIN b_catalog_discount CDD ON (CD.DISCOUNT_ID = CDD.ID)"),
			"DESCRIPTION" => array("FIELD" => "CD.DESCRIPTION","TYPE" => "string"),
			"TIMESTAMP_X" => array("FIELD" => "CD.TIMESTAMP_X", "TYPE" => "datetime"),
			"MODIFIED_BY" => array("FIELD" => "CD.MODIFIED_BY", "TYPE" => "int"),
			"DATE_CREATE" => array("FIELD" => "CD.DATE_CREATE", "TYPE" => "datetime"),
			"CREATED_BY" => array("FIELD" => "CD.CREATED_BY", "TYPE" => "int"),
		);

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_discount_coupon CD ".$arSqls["FROM"];
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

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_discount_coupon CD ".$arSqls["FROM"];
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
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_discount_coupon CD ".$arSqls["FROM"];
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
	* @deprecated deprecated since catalog 15.0.4
	* @see \Bitrix\Sale\DiscountCouponsManager
	*/
	static public function CouponApply($intUserID, $strCoupon)
	{
		if (self::$existCouponsManager === null)
			self::initCouponManager();
		if (self::$existCouponsManager)
		{
			$couponList = (is_array($strCoupon) ? $strCoupon : array($strCoupon));
			return DiscountCouponsManager::setApplyByProduct(array('MODULE' => 'catalog'), $couponList, true);
		}
		else
		{
			global $DB;

			$mxResult = false;

			$intUserID = (int)$intUserID;
			if ($intUserID < 0)
				$intUserID = 0;

			$arCouponList = array();
			$arCheck = (is_array($strCoupon) ? $strCoupon : array($strCoupon));
			foreach ($arCheck as &$strOneCheck)
			{
				$strOneCheck = (string)$strOneCheck;
				if ('' != $strOneCheck)
					$arCouponList[] = $strOneCheck;
			}
			if (isset($strOneCheck))
				unset($strOneCheck);

			if (empty($arCouponList))
				return $mxResult;

			$strDateFunction = $DB->GetNowFunction();
			$boolFlag = false;
			$couponIterator = Catalog\DiscountCouponTable::getList(array(
				'select' => array('ID', 'TYPE', 'COUPON'),
				'filter' => array('=COUPON' => $arCouponList, '=ACTIVE' => 'Y')
			));
			while ($arCoupon = $couponIterator->fetch())
			{
				$arCoupon['ID'] = (int)$arCoupon['ID'];
				$arFields = array(
					"~DATE_APPLY" => $strDateFunction
				);

				if ($arCoupon['TYPE'] == Catalog\DiscountCouponTable::TYPE_ONE_ROW)
				{
					$arFields["ACTIVE"] = "N";
					if (0 < $intUserID)
					{
						CCatalogDiscountCoupon::EraseCouponByManage($intUserID, $arCoupon['COUPON']);
					}
					else
					{
						CCatalogDiscountCoupon::EraseCoupon($arCoupon['COUPON']);
					}
				}
				elseif ($arCoupon['TYPE'] == Catalog\DiscountCouponTable::TYPE_ONE_ORDER)
				{
					$boolFlag = true;
					if (!isset(self::$arOneOrderCoupons[$arCoupon['ID']]))
						self::$arOneOrderCoupons[$arCoupon['ID']] = array(
							'COUPON' => $arCoupon['COUPON'],
							'USER_ID' => $intUserID,
						);
				}

				$strUpdate = $DB->PrepareUpdate("b_catalog_discount_coupon", $arFields);
				if (!empty($strUpdate))
				{
					$strSql = "UPDATE b_catalog_discount_coupon SET ".$strUpdate." WHERE ID = ".$arCoupon['ID'];
					$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					$mxResult = true;
				}
			}
			unset($arCoupon, $couponIterator);
			if ($boolFlag)
			{
				AddEventHandler('sale', 'OnBasketOrder', array('CCatalogDiscountCoupon', 'CouponOneOrderDisable'));
				AddEventHandler('sale', 'OnDoBasketOrder', array('CCatalogDiscountCoupon', 'CouponOneOrderDisable'));
			}
			return $mxResult;
		}
	}

	/**
	* @deprecated deprecated since catalog 12.5.6
	* @see CCatalogDiscountCoupon::CouponOneOrderDisable()
	*/
	static public function __CouponOneOrderDisable($arCoupons)
	{
		global $DB;
		if (!is_array($arCoupons))
			$arCoupons = array(intval($arCoupons));
		CatalogClearArray($arCoupons, false);
		if (empty($arCoupons))
			return;
		$strSql = "UPDATE b_catalog_discount_coupon SET ACTIVE='N' WHERE ID IN (".implode(', ', $arCoupons).") AND ONE_TIME='".self::TYPE_ONE_ORDER."'";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	/**
	* @deprecated deprecated since catalog 15.0.4
	* @see \Bitrix\Sale\DiscountCouponsManager::saveApplied
	*/
	static public function CouponOneOrderDisable($intOrderID = 0)
	{
		if (self::$existCouponsManager === null)
			self::initCouponManager();
		if (self::$existCouponsManager)
			return;

		global $DB;
		if (!empty(self::$arOneOrderCoupons))
		{
			$arCouponID = array_keys(self::$arOneOrderCoupons);
			foreach (self::$arOneOrderCoupons as &$arCoupon)
			{
				$arCoupon['USER_ID'] = intval($arCoupon['USER_ID']);
				if (0 < $arCoupon['USER_ID'])
				{
					CCatalogDiscountCoupon::EraseCouponByManage($arCoupon['USER_ID'], $arCoupon['COUPON']);
				}
				else
				{
					CCatalogDiscountCoupon::EraseCoupon($arCoupon['COUPON']);
				}
			}
			if (isset($arCoupon))
				unset($arCoupon);
			CatalogClearArray($arCouponID, false);
			if (!empty($arCouponID))
			{
				$strSql = "UPDATE b_catalog_discount_coupon SET ACTIVE='N' WHERE ID IN (".implode(', ', $arCouponID).") AND ONE_TIME='".self::TYPE_ONE_ORDER."' AND ACTIVE='Y'";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			self::$arOneOrderCoupons = array();
		}
	}

	/**
	* @deprecated deprecated since catalog 15.0.4
	* @see \Bitrix\Sale\DiscountCouponsManager::isExist
	*/
	
	/**
	* <p>Метод проверяет существование купона. Метод динамичный.</p>
	*
	*
	* @param string $strCoupon  Код купона.
	*
	* @return bool <p> В случае наличия купона возвращает true, иначе - false.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/isexistcoupon.php
	* @author Bitrix
	* @deprecated deprecated since catalog 15.0.4  ->  \Bitrix\Sale\DiscountCouponsManager::isExist
	*/
	static public function IsExistCoupon($strCoupon)
	{
		if (self::$existCouponsManager === null)
			self::initCouponManager();
		if (self::$existCouponsManager)
		{
			$result = DiscountCouponsManager::isExist($strCoupon);
			if (!empty($result))
				return true;
			return false;
		}
		else
		{
			global $DB;

			if ($strCoupon == '')
				return false;

			$strSql = "select ID, COUPON from b_catalog_discount_coupon where COUPON='".$DB->ForSql($strCoupon)."' limit 1";
			$rsCoupons = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arCoupon = $rsCoupons->Fetch())
				return true;
		}
		return false;
	}
}