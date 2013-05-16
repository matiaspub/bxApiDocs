<?
if(!defined("CACHED_b_iblock_type")) // define("CACHED_b_iblock_type", 36000);
if(!defined("CACHED_b_iblock")) // define("CACHED_b_iblock", 36000);
if(!defined("CACHED_b_iblock_count")) // define("CACHED_b_iblock_count", 300);
if(!defined("CACHED_b_iblock_bucket_size")) // define("CACHED_b_iblock_bucket_size", 20);
if(!defined("CACHED_b_iblock_property_enum")) // define("CACHED_b_iblock_property_enum", 36000);
if(!defined("CACHED_b_iblock_property_enum_bucket_size")) // define("CACHED_b_iblock_property_enum_bucket_size", 100);

global $DBType;
$arClasses = array(
	"iblock" => "install/index.php",
	"CIBlockPropertyResult" => "classes/general/iblockpropresult.php",
	"CIBlockResult" => "classes/general/iblockresult.php",
	"_CIBElement" => "classes/general/iblock_element.php",
	"CIBlockType" => "classes/general/iblocktype.php",
	"CAllIBlock" => "classes/general/iblock.php",
	"CIBlock" => "classes/".$DBType."/iblock.php",
	"CAllIBlockSection" => "classes/general/iblocksection.php",
	"CIBlockSection" => "classes/".$DBType."/iblocksection.php",
	"CAllIBlockProperty" => "classes/general/iblockproperty.php",
	"CIBlockPropertyEnum" => "classes/general/iblockpropertyenum.php",
	"CIBlockProperty" => "classes/".$DBType."/iblockproperty.php",
	"CAllIBlockElement" => "classes/general/iblockelement.php",
	"CIBlockElement" => "classes/".$DBType."/iblockelement.php",
	"CAllIBlockRSS" => "classes/general/iblockrss.php",
	"CIBlockRSS" => "classes/".$DBType."/iblockrss.php",
	"CIBlockPropertyDateTime" => "classes/general/prop_datetime.php",
	"CIBlockPropertyXmlID" => "classes/general/prop_xmlid.php",
	"CIBlockPropertyFileMan" => "classes/general/prop_fileman.php",
	"CIBlockPropertyHTML" => "classes/general/prop_html.php",
	"CIBlockPropertyElementList" => "classes/general/prop_element_list.php",
	"CIBlockXMLFile" => "classes/".$DBType."/cml2.php",
	"CIBlockCMLImport" => "classes/general/cml2.php",
	"CIBlockCMLExport" => "classes/general/cml2.php",
	"CIBlockFindTools" => "classes/general/comp_findtools.php",
	"CIBlockPriceTools" => "classes/general/comp_pricetools.php",
	"CIBlockParameters" => "classes/general/comp_parameters.php",
	"CIBlockFormatProperties" => "classes/general/comp_formatprops.php",
	"CIBlockSequence" => "classes/".$DBType."/iblocksequence.php",
	"CIBlockPropertySequence" => "classes/general/prop_seq.php",
	"CIBlockPropertyElementAutoComplete" => "classes/general/prop_element_auto.php",
	"CIBlockPropertySKU" => "classes/general/prop_element_sku.php",
	"CAllIBlockOffersTmp" => "classes/general/iblockoffers.php",
	"CIBlockOffersTmp" => "classes/".$DBType."/iblockoffers.php",
	"CEventIblock" => "classes/general/iblock_event_list.php",
	"CRatingsComponentsIBlock" => "classes/general/ratings_components.php",
	"CIBlockRights" => "classes/general/iblock_rights.php",
	"CIBlockSectionRights" => "classes/general/iblock_rights.php",
	"CIBlockElementRights" => "classes/general/iblock_rights.php",
	"CIBlockRightsStorage" => "classes/general/iblock_rights.php",
	"Bitrix\\Iblock\\IblockTable" => "lib/iblock.php",
	"Bitrix\\Iblock\\ElementTable" => "lib/element.php",
	"Bitrix\\Iblock\\SectionElementTable" => "lib/sectionelement.php",
	"Bitrix\\Iblock\\SectionTable" => "lib/section.php",
	"Bitrix\\Iblock\\SiteTable" => "lib/site.php",
	"CIBlockSectionPropertyLink" => "classes/general/section_property.php",
);

if(IsModuleInstalled('bizproc'))
{
	$arClasses["CIBlockDocument"] = "classes/general/iblockdocument.php";
}

CModule::AddAutoloadClasses("iblock", $arClasses);

IncludeModuleLangFile(__FILE__);

/*********************************************
Public helper functions
*********************************************/
function GetIBlockListWithCnt($type, $arTypesInc = array(), $arTypesExc = array(), $arOrder = array(
	"SORT" => "ASC",
), $cnt = 0)
{
	if (!is_array($arTypesInc))
	{
		$arTypesInc = array(
			$arTypesInc,
		);
	}

	$arIDsInc = array();
	$arCODEsInc = array();
	foreach ($arTypesInc as $i)
	{
		if (intval($i) > 0)
			$arIDsInc[] = $i;
		else
			$arCODEsInc[] = $i;
	}

	if (!is_array($arTypesExc))
	{
		$arTypesExc = array(
			$arTypesExc,
		);
	}

	$arIDsExc = array();
	$arCODEsExc = array();
	foreach ($arTypesExc as $i)
	{
		if (intval($i) > 0)
			$arIDsExc[] = $i;
		else
			$arCODEsExc[] = $i;
	}

	$res = CIBlock::GetList($arOrder, array(
		"type" => $type,
		"LID" => LANG,
		"ACTIVE" => "Y",
		"ID" => $arIDsInc,
		"CNT_ACTIVE" => "Y",
		"CODE" => $arCODEsInc,
		"!ID" => $arIDsExc,
		"!CODE" => $arCODEsExc,
	), true);

	$dbr = new CIBlockResult($res);
	if ($cnt > 0)
		$dbr->NavStart($cnt);

	return $dbr;
}

function GetIBlockList($type, $arTypesInc = Array(), $arTypesExc = Array(), $arOrder=Array("SORT"=>"ASC"), $cnt=0)
{
	return GetIBlockListLang(LANG, $type, $arTypesInc, $arTypesExc, $arOrder, $cnt);
}

function GetIBlockListLang($lang, $type, $arTypesInc = array(), $arTypesExc = array(), $arOrder = array(
	"SORT" => "ASC",
), $cnt = 0)
{
	if (!is_array($arTypesInc))
	{
		$arTypesInc = array(
			$arTypesInc,
		);
	}

	$arIDsInc = array();
	$arCODEsInc = array();
	foreach ($arTypesInc as $i)
	{
		if (IntVal($i) > 0)
			$arIDsInc[] = $i;
		else
			$arCODEsInc[] = $i;
	}

	if (!is_array($arTypesExc))
	{
		$arTypesExc = array(
			$arTypesExc,
		);
	}

	$arIDsExc = array();
	$arCODEsExc = array();
	foreach ($arTypesExc as $i)
	{
		if (intval($arTypesExc[$i]) > 0)
			$arIDsExc[] = $i;
		else
			$arCODEsExc[] = $i;
	}

	$res = CIBlock::GetList($arOrder, array(
		"type" => $type,
		"LID" => $lang,
		"ACTIVE" => "Y",
		"ID" => $arIDsInc,
		"CODE" => $arCODEsInc,
		"!ID" => $arIDsExc,
		"!CODE" => $arCODEsExc,
	));

	$dbr = new CIBlockResult($res);
	if ($cnt > 0)
		$dbr->NavStart($cnt);

	return $dbr;
}


/**
 * <p>Функция возвращает информационный блок по коду <i>ID</i>, но только если он активен (ACTIVE равно Y) и имеет привязку к текущему сайту. <br></p>
 *
 *
 *
 *
 * @param string $ID  ID информационного блока.
 *
 *
 *
 * @param typ $e = "" Тип информационного блока. Устанавливается в настройках модуля.
 * Если задан, то при выборке проверяется чтобы информационный блок
 * соответствовал этому типу. <br> Необязательный. По умолчанию на
 * информационный блок не накладываются ограничения по типу.
 *
 *
 *
 * @return array <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblock">полей информационного
 * блока</a><code>#SITE_DIR#</code><code>#IBLOCK_ID#</code><br><h4>Примечание</h4>
 *
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?<br>require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");<br><br>$APPLICATION-&gt;SetTitle("Продукты");<br><br>// проверим установлен ли модуль и получим блок с кодом $BID и типом catalog<br>if(CModule::IncludeModule("iblock") &amp;&amp; ($arIBlock = GetIBlock($_GET["BID"], "catalog")))<br>{<br>   // сделаем заголовок страницы таким же как название инф. блока<br>   $APPLICATION-&gt;SetTitle($arIBlock["NAME"]);<br>   //добавим название в навигационную цепочку<br>   $APPLICATION-&gt;AddChainItem($arIBlock["NAME"], $arIBlock["LIST_PAGE_URL"]);<br><br>   //работаем дальше с информационным блоком<br>   // ....<br>}<br>else<br>   ShowError("Информационный блок не найден.");<br><br>require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");<br>?&gt;<br>
 * </pre>
 *
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fiblock">Поля информационных
 * блоков</a></li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/functions/getiblock.php
 * @author Bitrix
 */
function GetIBlock($ID, $type="")
{
	return GetIBlockLang(LANG, $ID, $type);
}

function GetIBlockLang($lang, $ID, $type="")
{
	$res = CIBlock::GetList(Array("sort"=>"asc"), Array("ID"=>IntVal($ID), "TYPE"=>$type, "LID"=>$lang, "ACTIVE"=>"Y"));
	$res = new CIBlockResult($res);
	return $arRes = $res->GetNext();
}

/**************************
Elements helper functions
**************************/
function GetIBlockElementListEx($type, $arTypesInc=Array(), $arTypesExc=Array(), $arOrder=Array("sort"=>"asc"), $cnt=0, $arFilter = Array(), $arSelect=Array(), $arGroupBy=false)
{
	return GetIBlockElementListExLang(LANG, $type, $arTypesInc, $arTypesExc, $arOrder, $cnt, $arFilter, $arSelect, $arGroupBy);
}

function GetIBlockElementCountEx($type, $arTypesInc=Array(), $arTypesExc=Array(), $arOrder=Array("sort"=>"asc"), $cnt=0, $arFilter = Array())
{
	return GetIBlockElementCountExLang(LANG, $type, $arTypesInc, $arTypesExc, $arOrder, $cnt, $arFilter);
}

function GetIBlockElementListExLang($lang, $type, $arTypesInc=Array(), $arTypesExc=Array(), $arOrder=Array("sort"=>"asc"), $cnt=0, $arFilter = Array(), $arSelect=Array(), $arGroupBy=false)
{
	$filter = _GetIBlockElementListExLang_tmp($lang, $type, $arTypesInc, $arTypesExc, $arOrder, $cnt, $arFilter);
	if(is_array($cnt))
		$arNavParams = $cnt; //Array("nPageSize"=>$cnt, "bShowAll"=>false);
	elseif($cnt>0)
		$arNavParams = Array("nPageSize"=>$cnt);
	else
		$arNavParams = false;

	$dbr = CIBlockElement::GetList($arOrder, $filter, $arGroupBy, $arNavParams, $arSelect);
	if(!is_array($cnt) && $cnt>0)
		$dbr->NavStart($cnt);

	return $dbr;
}

function GetIBlockElementCountExLang($lang, $type, $arTypesInc=Array(), $arTypesExc=Array(), $arOrder=Array("sort"=>"asc"), $cnt=0, $arFilter = Array())
{
	$filter = _GetIBlockElementListExLang_tmp($lang, $type, $arTypesInc, $arTypesExc, $arOrder, $cnt, $arFilter);
	return CIBlockElement::GetList($arOrder, $filter, true);
}

function _GetIBlockElementListExLang_tmp($lang, $type, $arTypesInc = array(), $arTypesExc = array(), $arOrder = array(
	"sort" => "asc",
), $cnt = 0, $arFilter = array(), $arSelect = array())
{
	if (!is_array($arTypesInc))
	{
		if ($arTypesInc !== false)
			$arTypesInc = array(
				$arTypesInc,
			);
		else
			$arTypesInc = array();
	}
	$arIDsInc = array();
	$arCODEsInc = array();
	foreach ($arTypesInc as $i)
	{
		if (intval($i) > 0)
			$arIDsInc[] = $i;
		else
			$arCODEsInc[] = $i;
	}

	if (!is_array($arTypesExc))
	{
		if ($arTypesExc !== false)
			$arTypesExc = array(
				$arTypesExc,
			);
		else
			$arTypesExc = array();
	}
	$arIDsExc = array();
	$arCODEsExc = array();
	foreach ($arTypesExc as $i)
	{
		if (intval($i) > 0)
			$arIDsExc[] = $i;
		else
			$arCODEsExc[] = $i;
	}

	$filter = array(
		"IBLOCK_ID" => $arIDsInc,
		"IBLOCK_LID" => $lang,
		"IBLOCK_ACTIVE" => "Y",
		"IBLOCK_CODE" => $arCODEsInc,
		"!IBLOCK_ID" => $arIDsExc,
		"!IBLOCK_CODE" => $arCODEsExc,
		"ACTIVE_DATE" => "Y",
		"ACTIVE" => "Y",
		"CHECK_PERMISSIONS" => "Y",
	);
	if ($type != false && strlen($type) > 0)
		$filter["IBLOCK_TYPE"] = $type;

	if (is_array($arFilter) && count($arFilter) > 0)
		$filter = array_merge($filter, $arFilter);

	return $filter;
}

function GetIBlockElementCount($IBLOCK, $SECT_ID=false, $arOrder=Array("sort"=>"asc"), $cnt=0)
{
	$filter = Array("IBLOCK_ID"=>IntVal($IBLOCK), "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y", "CHECK_PERMISSIONS"=>"Y");
	if($SECT_ID!==false)
		$filter["SECTION_ID"]=IntVal($SECT_ID);

	return CIBlockElement::GetList($arOrder, $filter, true);
}


/**
 * <p>Функция возвращает активные элементы из информационного блока <i>iblock_id</i>. </p>
 *
 *
 *
 *
 * @param int $iblock_id  ID информационного блока из которого будут выбраны элементы.
 *
 *
 *
 * @param int $section_id = false ID раздела, из которой требуется получить элементы. Для получения
 * элементов из корня информационного блока (не привязанные ни к
 * одному разделу) установите параметр <i>section_id</i> =
 * 0.<br><br>Необязательный. По умолчанию (false) выбираются все записи без
 * ограничения по папкам.
 *
 *
 *
 * @param array $order = Array("SORT"=>"ASC") Порядок сортировки - массив вида Array(<i>by1</i>=&gt;<i>order1</i>[,
 * <i>by2</i>=&gt;<i>order2</i> [, ..]]), где <i>by</i> - поле для сортировки, может
 * принимать значения: <ul> <li> <b>sort</b> - индекс сортировки; </li> <li>
 * <b>timestamp_x</b> - дата изменения; </li> <li> <b>name</b> - название; </li> <li> <b>id</b> - ID
 * элемента; </li> <li> <b>active_from</b> - начало периода действия элемента; </li>
 * <li> <b>active_to</b> - окончание периода действия элемента; </li> <li> <b>order</b> -
 * порядок сортировки сортировки, может принимать значения: <ul> <li>
 * <b>asc</b> - по возрастанию; </li> <li> <b>desc</b> - по убыванию. </li>
 * </ul>Необязательный. По умолчанию равен <i>Array("sort"=&gt;"asc")</i> </li>
 * </ul>Полный список полей сортировки и дополнительную информацию <a
 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/getlist.php">смотрите</a> в <a
 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/index.php">CIBlockElement</a>::<a
 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/getlist.php">GetList()</a>
 *
 *
 *
 * @param int $cnt = 0 Максимальное количество записей, которые вернет функция.
 * <br>Необязательный. По умолчанию выбираются все записи.
 *
 *
 *
 * @param array $arFilter = Array() Дополнительный фильтр по произвольным полям вида
 * Array("Фильтруемое поле"=&gt;"Значение", ...). <br><i>Фильтруемое поле</i>
 * может принимать значения: <ul> <li> <b>ID</b> - по коду; </li> <li> <b>ACTIVE</b> -
 * фильтр по активности (Y|N); передача пустого значения (<i>"ACTIVE"=&gt;""</i>)
 * выводит все элементы без учета их состояния; </li> <li> <b>NAME</b> - по
 * имени и фамилии (можно искать по шаблону [%_]); </li> <li> <b>PREVIEW_TEXT</b> - по
 * имени и фамилии (можно искать по шаблону [%_]); </li> <li> <b>DETAIL_TEXT</b> - по
 * детальному описанию (можно искать по шаблону [%_]); </li> <li>
 * <b>SEARCHABLE_CONTENT</b> - по содержимому для поиска. Включает в себя
 * название, описание для анонса и детальное описание (можно искать
 * по шаблону [%_]); </li> <li> <b>CODE</b> - по мнемоническому идентификатору
 * (можно искать по шаблону [%_]); </li> <li> <b>SORT</b> - по сортировке; </li> <li>
 * <b>EXTERNAL_ID</b> - по внешнему коду (можно искать по шаблону [%_]); </li> <li>
 * <b>TIMESTAMP_X</b> - по времени изменения; </li> <li> <b>DATE_CREATE</b> - по времени
 * создания; </li> <li> <b>DATE_ACTIVE_FROM</b> - по дате начала активности; </li> <li>
 * <b>DATE_ACTIVE_TO</b> - по дате окончанию активности; </li> <li> <b>ACTIVE_DATE</b> -
 * непустое значение задействует фильтр по датам активности
 * (<i>DATE_ACTIVE_FROM</i> и <i>DATE_ACTIVE_TO</i>). Если значение не установлено (<i>""</i>),
 * фильтрация по датам активности не производится; </li> <li> <b>IBLOCK_ID</b> -
 * по коду информационного блока; </li> <li> <b>IBLOCK_CODE</b> - по
 * мнемоническому коду информационного блока (можно искать по
 * шаблону [%_]); </li> <li> <b>IBLOCK_LID</b> - по языку (можно искать по шаблону [%_]);
 * </li> <li> <b>IBLOCK_TYPE</b> - по типу блока (можно искать по шаблону [%_]); </li> <li>
 * <b>IBLOCK_ACTIVE</b> - по активности блока (можно искать по шаблону [%_]); </li>
 * <li> <b>SECTION_ID</b> - по родительскому разделу; </li> <li> <b>PROPERTY_&lt;код
 * свойства&gt;</b> - фильтр по значениям свойств (можно искать по
 * шаблону [%_]), для свойств типа "список", поиск будет осуществляться
 * не по значению перечисления, а по его идентификатору; </li> <li>
 * <b>PROPERTY_&lt;код свойства&gt;_VALUE</b> - фильтр по значениям списка для
 * свойств типа "список" (можно искать по шаблону [%_]), поиск будет
 * осуществляться по строковому значению списка, а не по
 * идентификатору; </li> <li> <b>CATALOG_&lt;CATALOG_FIELD&gt;_&lt;PRICE_TYPE&gt;</b> - по полю
 * <i>CATALOG_FIELD </i>из цены типа <i>PRICE_TYPE </i>(ID типа цены), где <i>CATALOG_FIELD
 * </i>может быть: <i>PRICE </i>- цена, <i>CURRENCY </i>- валюта. </li> </ul>Все
 * фильтруемые поля (кроме <i>SECTION_ID </i>и <i>ACTIVE_DATE</i>)<i> </i>могут
 * содержать перед названием <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2683" >тип проверки
 * фильтра</a>, а поля <i>SECTION_ID </i>и <i>ACTIVE_DATE </i>могут содержать перед
 * названием тип проверки фильтра "!" - не равно.<br><br><i>Значения
 * фильтра</i> - одиночное значение или массив.<br><br>Необязательный. По
 * умолчанию - пустой массив.<br>Полный список полей фильтра и
 * дополнительную информацию <a
 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/getlist.php">смотрите</a> в <b><a
 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/index.php">CIBlockElement</a>::<a
 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/getlist.php">GetList()</a>.</b>
 *
 *
 *
 * @return CIBlockResult <p>Функция возвращает объект класса <a
 * href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockresult/index.php">CIBlockResult</a> с активными
 * элементами (у которых установлен флаг "Активен", выполняется
 * условие периода активности и находящиеся в активных
 * информационных блоках для текущего сайта).</p><h4>Примечание</h4><p>При
 * работе с результатом рекомендуется применять метод класса <a
 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>::<a
 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/getnext.php">GetNext()</a>, результатом
 * которого будет массив с полями элемента информационного блока.
 * Все поля при этом будут преобразованы в "HTML безопасный" вид, а в
 * полях с шаблонами URL-ов к страницам (LIST_PAGE_URL - списка элементов и
 * DETAIL_PAGE_URL - детального просмотра) будут заменены параметры
 * <code>#SITE_DIR#</code>, <code>#IBLOCK_ID#</code>, <code>#EXTERNAL_ID#</code> и <code>#ID#</code>. Если
 * результат пуст или достигнут конец выборки <a
 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>::<a
 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/getnext.php">GetNext()</a> вернет false. Также
 * можно воспользоваться любыми другими методами класса <a
 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>, но при этом в
 * полях LIST_PAGE_URL и DETAIL_PAGE_URL будут оригинальные (как они введены в
 * форме редактирования информационного блока) шаблоны URL-ов, а не с
 * замененными параметрами <code>#SITE_DIR#</code>, <code>#IBLOCK_ID#</code>,
 * <code>#EXTERNAL_ID#</code> и <code>#ID#</code>. </p>
 *
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?
 * if(CModule::IncludeModule("iblock"))
 * {
 *    // выберем 10 элементов из папки $ID информационного блока $BID
 *    $items = GetIBlockElementList($_GET['BID'], $_GET['ID'], Array("SORT"=&gt;"ASC"), 10);
 *    $items-&gt;NavPrint("Товары");
 *    while($arItem = $items-&gt;GetNext())
 *    {
 *       echo $arItem["NAME"]."&lt;br&gt;";
 *       echo $arItem["PREVIEW_TEXT"]."&lt;br&gt;";
 *    }
 *    $items-&gt;NavPrint("Товары");
 * }
 * ?&gt;
 * </pre>
 *
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#felement">Поля элементов
 * информационного блока</a> </li> <li> <a
 * href="http://dev.1c-bitrix.ruapi_help/iblock/functions/getiblockelementlistex.php">Функция
 * GetIBlockElementListEx</a> </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/functions/getiblockelementlist.php
 * @author Bitrix
 */
function GetIBlockElementList($IBLOCK, $SECT_ID=false, $arOrder=Array("sort"=>"asc"), $cnt=0, $arFilter=array(), $arSelect=array())
{
	$filter = Array("IBLOCK_ID"=>IntVal($IBLOCK), "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y", "CHECK_PERMISSIONS"=>"Y");
	if($SECT_ID!==false)
		$filter["SECTION_ID"]=IntVal($SECT_ID);

	if (is_array($arFilter) && count($arFilter)>0)
		$filter = array_merge($filter, $arFilter);

	$dbr = CIBlockElement::GetList($arOrder, $filter, false, false, $arSelect);
	if($cnt>0)
		$dbr->NavStart($cnt);

	return $dbr;
}


/**
 * <p>Функция возвращает информационный элемент с кодом <i>ID</i>. Функция-помошник, которая выбирает все базовые поля элемента, его свойства и информацию об инфоблоке. Использует <a href="http://dev.1c-bitrix.ruapi_help/iblock/classes/ciblockelement/getlist.php">GetList</a>. </p>
 *
 *
 *
 *
 * @param int $ID  ID элемента.
 *
 *
 *
 * @param string $type = "" Тип информационного блока. Устанавливается в настройках модуля.
 * Если задан, то при выборке проверяется чтобы элемент
 * соответствовал этому типу. <br> Необязательный. По умолчанию на
 * элемент не накладываются ограничения по типу.
 *
 *
 *
 * @return array <p>Функция возвращает массив <a
 * href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#felement">полей информационного
 * элемента</a> и дополнительно следующие поля: </p><table width="100%"
 * class="tnormal"><tbody> <tr> <th width="15%">Поле</th> <th>Значение</th> </tr> <tr> <td>IBLOCK_NAME</td>
 * <td>Название информационного блока.</td> </tr> <tr> <td>PROPERTIES</td> <td>Массив
 * значений свойств, имеющий в качестве индексов "Символьный код
 * свойства" (задается в настройках информационного блока) или, если
 * код не задан, то уникальное ID свойства. Значением каждого
 * свойства будет массив вида: <pre class="syntax">Array( "NAME"=&gt;"название
 * свойства", "DEFAULT_VALUE"=&gt;"значение свойства по умолчанию",
 * "VALUE"=&gt;"значение свойства или массив значений свойств, если
 * свойство множественное", "VALUE_ENUM_ID"=&gt;"Код значения свойства типа
 * &lt;Список&gt;" )</pre> </td> </tr> </tbody></table><p> Если заданным параметрам не
 * найден элемент, функция вернет <i>false</i>. <br> Выборка элемента
 * происходит только из активных элементов инфоблока, неактивный
 * элемент выбран быть не может. </p><h4>Примечание</h4><p> Все
 * возвращаемые поля преобразованы в "HTML безопасный" вид, а в полях
 * (LIST_PAGE_URL - списка элементов и DETAIL_PAGE_URL - детального просмотра) с
 * шаблонами URL-ов к страницам будут заменены параметры
 * <code>#SITE_DIR#</code>, <code>#IBLOCK_ID#</code>, <code>#EXTERNAL_ID#</code> и <code>#ID#</code>.</p>
 *
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?<br>require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');<br><br>$APPLICATION-&gt;SetTitle('Карточка товара');<br><br>// подключим модуль и выберем элемент ID типа product<br>$arIBlockElement = false;<br>if(CModule::IncludeModule('iblock') &amp;&amp; ($arIBlockElement = GetIBlockElement($ID, 'product')))<br>{<br>   // В заголовок страницы вставим название элемента<br>   $APPLICATION-&gt;SetTitle($arIBlockElement['NAME']);<br>   // В навигационную цепочку вставим название и ссылку на текущий информационный блок<br>   $APPLICATION-&gt;AddChainItem($arIBlockElement['IBLOCK_NAME'], 'products.php?ID='.$arIBlockElement['IBLOCK_ID']);<br><br>   // выведем детальную картинку<br>   echo ShowImage($arIBlockElement['DETAIL_PICTURE'], 150, 150, 'border="0"', '', true);<br>   // выведем детальное описание<br>   echo $arIBlockElement['DETAIL_TEXT'].'&lt;br&gt;';<br>   // выведем значение свойства с кодом PRICE<br>   echo $arIBlockElement['PROPERTIES']['PRICE']['VALUE'].'&lt;br&gt;';<br><br>   // вывeдем оставшиеся свойсва<br>   $arProps = $arIBlockElement['PROPERTIES'];<br>   foreach($arProps as $property_code=&gt;$arValue)<br>   {<br>      // если это свойство с кодом PRICE или значение свойства не введено - пропустим<br>      if($property_code=='PRICE' <br>			|| (!is_array($arValue['VALUE']) &amp;&amp; strlen($arValue['VALUE'])&lt;=0) <br>			|| (is_array($arValue['VALUE']) &amp;&amp; count($arValue['VALUE'])&lt;=0)<br>			)<br>         continue;<br><br>      // выведем пару "Название: значение"<br>      if(!is_array($arValue['VALUE']))<br>         echo $arValue['NAME'].": ".$arValue['VALUE'];<br>      else<br>      {<br>         echo $arValue['NAME'].': ';<br>         foreach($arValue['VALUE'] as $val)<br>         {<br>            echo $val.'&lt;br&gt;';<br>         }<br>      }<br>   }<br>}<br>else<br>   echo ShowError('Новость не найдена');<br><br>require($_SERVER["DOCUMENT_ROOT"].'/bitrix/footer.php");<br>?&gt;<br>
 * </pre>
 *
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#felement">Поля информационного
 * элемента</a></li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/functions/getiblockelement.php
 * @author Bitrix
 */
function GetIBlockElement($ID, $TYPE="")
{
	$filter = Array("ID"=>IntVal($ID), "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y", "CHECK_PERMISSIONS"=>"Y");
	if($TYPE!="")
		$filter["IBLOCK_TYPE"]=$TYPE;

	$iblockelement = CIBlockElement::GetList(Array(), $filter);
	if($obIBlockElement = $iblockelement->GetNextElement())
	{
		$arIBlockElement = $obIBlockElement->GetFields();
		if($arIBlock = GetIBlock($arIBlockElement["IBLOCK_ID"], $TYPE))
		{
			$arIBlockElement["IBLOCK_ID"] = $arIBlock["ID"];
			$arIBlockElement["IBLOCK_NAME"] = $arIBlock["NAME"];
			$arIBlockElement["~IBLOCK_NAME"] = $arIBlock["~NAME"];
			$arIBlockElement["PROPERTIES"] = $obIBlockElement->GetProperties();
			return $arIBlockElement;
		}
	}

	return false;
}

/******************************
Sections functions
******************************/
function GetIBlockSectionListWithCnt($IBLOCK, $SECT_ID=false, $arOrder = Array("left_margin"=>"asc"), $cnt=0, $arFilter=Array())
{
	$filter = Array("IBLOCK_ID"=>IntVal($IBLOCK), "ACTIVE"=>"Y", "CNT_ACTIVE"=>"Y");
	if($SECT_ID!==false)
		$filter["SECTION_ID"]=IntVal($SECT_ID);

	if(is_array($arFilter) && count($arFilter)>0)
		$filter = array_merge($filter, $arFilter);

	$dbr = CIBlockSection::GetList($arOrder, $filter, true);
	if($cnt>0)
		$dbr->NavStart($cnt);

	return $dbr;
}

function GetIBlockSectionList($IBLOCK, $SECT_ID=false, $arOrder = Array("left_margin"=>"asc"), $cnt=0, $arFilter=Array())
{
	$filter = Array("IBLOCK_ID"=>IntVal($IBLOCK), "ACTIVE"=>"Y", "IBLOCK_ACTIVE"=>"Y");
	if($SECT_ID!==false)
		$filter["SECTION_ID"]=IntVal($SECT_ID);

	if(is_array($arFilter) && count($arFilter)>0)
		$filter = array_merge($filter, $arFilter);

	$dbr = CIBlockSection::GetList($arOrder, $filter);
	if($cnt>0)
		$dbr->NavStart($cnt);

	return $dbr;
}


/**
 * <p>Функция возвращает раздел информационного блока. </p>
 *
 *
 *
 *
 * @param int $ID  ID раздела.
 *
 *
 *
 * @param string $type = "" Тип информационного блока, в котором размещён раздел.
 * Устанавливается в настройках модуля. Если задан, то при выборке
 * проверяется чтобы раздел соответствовал этому типу.
 * <br>Необязательный. По умолчанию на раздел не накладываются
 * ограничения по типу.
 *
 *
 *
 * @return array <a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fsection">полей
 * раздела</a><i>IBLOCK_NAME</i><br><h4>Примечание</h4>
 *
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?
 * require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
 * 
 * $APPLICATION-&gt;SetTitle("Просмотр раздела");
 * 
 * // подключим модуль и выберем раздел ID типа product
 * if(CModule::IncludeModule("iblock") &amp;&amp; ($arIBlockSection = GetIBlockSection($_GET['ID'], 'product')))
 * {
 *    // В заголовок страницы вставим название раздела
 *    $APPLICATION-&gt;SetTitle($arIBlockSection["NAME"]);
 *    // В навигационную цепочку вставим название и ссылку на текущий информационный блок
 *    $APPLICATION-&gt;AddChainItem($arIBlockSection["IBLOCK_NAME"], "products.php?ID=".$arIBlockElement["IBLOCK_ID"]);
 *    // выведем картинку
 *    echo ShowImage($arIBlockSection["PICTURE"], 150, 150, "border='0'", "", true);
 *    // выведем детальное описание
 *    echo $arIBlockSection["DESCRIPTION"]."&lt;br&gt;";
 * }
 * else
 *    echo ShowError("Раздел не найден");
 * 
 * require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
 * ?&gt;
 * </pre>
 *
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ruapi_help/iblock/fields.php#fsection">Поля раздела
 * информационного блока</a></li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/functions/getiblocksection.php
 * @author Bitrix
 */
function GetIBlockSection($ID, $TYPE="")
{
	$ID = intval($ID);
	if($ID>0)
	{
		$iblocksection = CIBlockSection::GetList(Array(), Array("ID"=>$ID, "ACTIVE"=>"Y"));
		if($arIBlockSection = $iblocksection->GetNext())
		{
			if($arIBlock = GetIBlock($arIBlockSection["IBLOCK_ID"], $TYPE))
			{
				$arIBlockSection["IBLOCK_ID"] = $arIBlock["ID"];
				$arIBlockSection["IBLOCK_NAME"] = $arIBlock["NAME"];
				return $arIBlockSection;
			}
		}
	}
	return false;
}

function GetIBlockSectionPath($IBLOCK, $SECT_ID)
{
	return CIBlockSection::GetNavChain(IntVal($IBLOCK), IntVal($SECT_ID));
}

/***************************************************************
* RSS
***************************************************************/
function xmlize_rss($data)
{
	$data = trim($data);
	$vals = $index = $array = array();
	$parser = xml_parser_create();
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($parser, $data, $vals, $index);
	xml_parser_free($parser);

	$i = 0;

	$tagname = $vals[$i]['tag'];
	if (isset($vals[$i]['attributes']))
		$array[$tagname]['@'] = $vals[$i]['attributes'];
	else
		$array[$tagname]['@'] = array();

	$array[$tagname]["#"] = xml_depth_rss($vals, $i);

	return $array;
}

function xml_depth_rss($vals, &$i)
{
	$children = array();

	if (isset($vals[$i]['value']))
		array_push($children, $vals[$i]['value']);

	while (++$i < count($vals))
	{
		switch ($vals[$i]['type'])
		{
			case 'open':
				if (isset($vals[$i]['tag']))
					$tagname = $vals[$i]['tag'];
				else
					$tagname = '';

				if (isset($children[$tagname]))
					$size = sizeof($children[$tagname]);
				else
					$size = 0;

				if (isset($vals[$i]['attributes']))
					$children[$tagname][$size]['@'] = $vals[$i]["attributes"];

				$children[$tagname][$size]['#'] = xml_depth_rss($vals, $i);
			break;

			case 'cdata':
				array_push($children, $vals[$i]['value']);
			break;

			case 'complete':
				$tagname = $vals[$i]['tag'];

				if(isset($children[$tagname]))
					$size = sizeof($children[$tagname]);
				else
					$size = 0;

				if(isset($vals[$i]['value']))
					$children[$tagname][$size]["#"] = $vals[$i]['value'];
				else
					$children[$tagname][$size]["#"] = '';

				if (isset($vals[$i]['attributes']))
					$children[$tagname][$size]['@'] = $vals[$i]['attributes'];
			break;

			case 'close':
				return $children;
			break;
		}

	}

	return $children;
}

function GetIBlockDropDownList($IBLOCK_ID, $strTypeName, $strIBlockName, $arFilter = false, $strAddType = '', $strAddIBlock = '')
{
	$html = '';

	static $arTypes = false;
	static $arIBlocks = false;

	if(!$arTypes)
	{
		$arTypes = array(''=>GetMessage("IBLOCK_CHOOSE_IBLOCK_TYPE"));
		$arIBlocks = array(''=>array(''=>GetMessage("IBLOCK_CHOOSE_IBLOCK")));

		if(!is_array($arFilter))
			$arFilter = array();
		$arFilter["MIN_PERMISSION"] = "W";

		$rsIBlocks = CIBlock::GetList(array("IBLOCK_TYPE" => "ASC", "NAME" => "ASC"), $arFilter);
		while($arIBlock = $rsIBlocks->Fetch())
		{
			if(!array_key_exists($arIBlock["IBLOCK_TYPE_ID"], $arTypes))
			{
				$arType = CIBlockType::GetByIDLang($arIBlock["IBLOCK_TYPE_ID"], LANG);
				$arTypes[$arType["~ID"]] = $arType["~NAME"]." [".$arType["~ID"]."]";
				$arIBlocks[$arType["~ID"]] = array(''=>GetMessage("IBLOCK_CHOOSE_IBLOCK"));
			}
			$arIBlocks[$arIBlock["IBLOCK_TYPE_ID"]][$arIBlock["ID"]] = $arIBlock["NAME"]." [".$arIBlock["ID"]."]";
		}

		$html .= '
		<script language="JavaScript">
		function OnTypeChanged(typeSelect, iblockSelectID)
		{
			var arIBlocks = '.CUtil::PhpToJSObject($arIBlocks).';
			var iblockSelect = document.getElementById(iblockSelectID);
			if(iblockSelect)
			{
				for(var i=iblockSelect.length-1; i >= 0; i--)
					iblockSelect.remove(i);
				var n = 0;
				for(var j in arIBlocks[typeSelect.value])
				{
					var newoption = new Option(arIBlocks[typeSelect.value][j], j, false, false);
					iblockSelect.options[n]=newoption;
					n++;
				}
			}
		}
		</script>
		';
	}

	$IBLOCK_TYPE = false;
	if($IBLOCK_ID > 0)
	{
		foreach($arIBlocks as $iblock_type_id => $iblocks)
		{
			if(array_key_exists($IBLOCK_ID, $iblocks))
			{
				$IBLOCK_TYPE = $iblock_type_id;
				break;
			}
		}
	}

	$strAddType = trim($strAddType);
	$strAddIBlock = trim($strAddIBlock);

	$html .= '<select name="'.htmlspecialcharsbx($strTypeName).'" id="'.htmlspecialcharsbx($strTypeName).'" OnChange="'.htmlspecialcharsbx('OnTypeChanged(this, \''.CUtil::JSEscape($strIBlockName).'\')').'"'.($strAddType != '' ? ' '.$strAddType : '').'>'."\n";
	foreach($arTypes as $key => $value)
	{
		if($IBLOCK_TYPE === false)
			$IBLOCK_TYPE = $key;
		$html .= '<option value="'.htmlspecialcharsbx($key).'"'.($IBLOCK_TYPE===$key? ' selected': '').'>'.htmlspecialcharsbx($value).'</option>'."\n";
	}
	$html .= "</select>\n";

	$html .= "&nbsp;\n";

	$html .= '<select name="'.htmlspecialcharsbx($strIBlockName).'" id="'.htmlspecialcharsbx($strIBlockName).'"'.($strAddIBlock != '' ? ' '.$strAddIBlock : '').'>'."\n";
	foreach($arIBlocks[$IBLOCK_TYPE] as $key => $value)
	{
		$html .= '<option value="'.htmlspecialcharsbx($key).'"'.($IBLOCK_ID==$key? ' selected': '').'>'.htmlspecialcharsbx($value).'</option>'."\n";
	}
	$html .= "</select>\n";

	return $html;
}

function GetIBlockDropDownListEx($IBLOCK_ID, $strTypeName, $strIBlockName, $arFilter = false, $onChangeType = '', $onChangeIBlock = '', $strAddType = '', $strAddIBlock = '')
{
	$html = '';

	static $arTypes = false;
	static $arIBlocks = false;

	if(!$arTypes)
	{
		$arTypes = array(0 => GetMessage("IBLOCK_CHOOSE_IBLOCK_TYPE"));
		$arIBlocks = array(0 => array(''=>GetMessage("IBLOCK_CHOOSE_IBLOCK")));

		if(!is_array($arFilter))
			$arFilter = array();
		if (!array_key_exists('MIN_PERMISSION',$arFilter) || trim($arFilter['MIN_PERMISSION']) == '')
			$arFilter["MIN_PERMISSION"] = "W";

		$rsIBlocks = CIBlock::GetList(array("IBLOCK_TYPE" => "ASC", "NAME" => "ASC"), $arFilter);
		while($arIBlock = $rsIBlocks->Fetch())
		{
			if(!array_key_exists($arIBlock["IBLOCK_TYPE_ID"], $arTypes))
			{
				$arType = CIBlockType::GetByIDLang($arIBlock["IBLOCK_TYPE_ID"], LANG);
				$arTypes[$arType["~ID"]] = $arType["~NAME"]." [".$arType["~ID"]."]";
				$arIBlocks[$arType["~ID"]] = array(0 => GetMessage("IBLOCK_CHOOSE_IBLOCK"));
			}
			$arIBlocks[$arIBlock["IBLOCK_TYPE_ID"]][$arIBlock["ID"]] = $arIBlock["NAME"]." [".$arIBlock["ID"]."]";
		}

		$html .= '
		<script type="text/javascript">
		function OnTypeExtChanged(typeSelect, iblockSelectID)
		{
			var arIBlocks = '.CUtil::PhpToJSObject($arIBlocks).';
			var iblockSelect = BX(iblockSelectID);
			if(iblockSelect)
			{
				for(var i=iblockSelect.length-1; i >= 0; i--)
					iblockSelect.remove(i);
				var n = 0;
				for(var j in arIBlocks[typeSelect.value])
				{
					var newoption = new Option(arIBlocks[typeSelect.value][j], j, false, false);
					iblockSelect.options.add(newoption);
					n++;
				}
			}
		}
		</script>
		';
	}

	$IBLOCK_TYPE = false;
	if($IBLOCK_ID > 0)
	{
		foreach($arIBlocks as $iblock_type_id => $iblocks)
		{
			if(array_key_exists($IBLOCK_ID, $iblocks))
			{
				$IBLOCK_TYPE = $iblock_type_id;
				break;
			}
		}
	}

	$onChangeType = trim($onChangeType);
	if ($onChangeType != '')
	{
		if (substr($onChangeType,-1) != ';')
			$onChangeType .= ';';
		$onChangeType = 'OnTypeExtChanged(this, \''.CUtil::JSEscape($strIBlockName).'\'); '.$onChangeType;
	}
	else
	{
		$onChangeType = 'OnTypeExtChanged(this, \''.CUtil::JSEscape($strIBlockName).'\');';
	}
	$onChangeIBlock = trim($onChangeIBlock);
	$strAddType = trim($strAddType);
	$strAddIBlock = trim($strAddIBlock);

	$html .= '<select name="'.htmlspecialcharsbx($strTypeName).'" id="'.htmlspecialcharsbx($strTypeName).'" onchange="'.htmlspecialcharsbx($onChangeType).'"'.($strAddType != '' ? ' '.$strAddType : '').'>'."\n";
	foreach($arTypes as $key => $value)
	{
		if($IBLOCK_TYPE === false)
			$IBLOCK_TYPE = $key;
		$html .= '<option value="'.htmlspecialcharsbx($key).'"'.($IBLOCK_TYPE===$key? ' selected': '').'>'.htmlspecialcharsbx($value).'</option>'."\n";
	}
	$html .= "</select>\n";

	$html .= "&nbsp;\n";

	$html .= '<select name="'.htmlspecialcharsbx($strIBlockName).'" id="'.htmlspecialcharsbx($strIBlockName).'"'.($onChangeIBlock != '' ? ' onchange="'.$onChangeIBlock.'"' : '').($strAddIBlock != '' ? ' '.$strAddIBlock : '').'>'."\n";
	foreach($arIBlocks[$IBLOCK_TYPE] as $key => $value)
	{
		$html .= '<option value="'.htmlspecialcharsbx($key).'"'.($IBLOCK_ID==$key? ' selected': '').'>'.htmlspecialcharsbx($value).'</option>'."\n";
	}
	$html .= "</select>\n";

	return $html;
}

function ImportXMLFile($file_name, $iblock_type="-", $site_id=false, $section_action="D", $element_action="D", $use_crc=false, $preview=false, $sync=false, $return_last_error=false)
{
	global $APPLICATION;

	$ABS_FILE_NAME = false;
	$WORK_DIR_NAME = false;
	if(strlen($file_name)>0)
	{
		if(
			file_exists($file_name)
			&& is_file($file_name)
			&& (
				substr($file_name, -4) === ".xml"
				|| substr($file_name, -7) === ".tar.gz"
			)
		)
		{
			$ABS_FILE_NAME = $file_name;

		}
		else
		{
			$filename = trim(str_replace("\\", "/", trim($file_name)), "/");
			$FILE_NAME = rel2abs($_SERVER["DOCUMENT_ROOT"], "/".$filename);
			if((strlen($FILE_NAME) > 1) && ($FILE_NAME === "/".$filename) && ($APPLICATION->GetFileAccessPermission($FILE_NAME) >= "W"))
			{
				$ABS_FILE_NAME = $_SERVER["DOCUMENT_ROOT"].$FILE_NAME;
			}
		}
	}

	if(!$ABS_FILE_NAME)
		return GetMessage("IBLOCK_XML2_FILE_ERROR");

	$WORK_DIR_NAME = substr($ABS_FILE_NAME, 0, strrpos($ABS_FILE_NAME, "/")+1);

	if(substr($ABS_FILE_NAME, -7) == ".tar.gz")
	{
		include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/tar_gz.php");
		$obArchiver = new CArchiver($ABS_FILE_NAME);
		if(!$obArchiver->ExtractFiles($WORK_DIR_NAME))
		{
			$strError = "";
			if(is_object($APPLICATION))
			{
				$arErrors = $obArchiver->GetErrors();
				if(count($arErrors))
				{
					foreach($arErrors as $error)
						$strError .= $error[1]."<br>";
				}
			}
			if($strError != "")
				return $strError;
			else
				return GetMessage("IBLOCK_XML2_FILE_ERROR");
		}
		$IMP_FILE_NAME = substr($ABS_FILE_NAME, 0, -7).".xml";
	}
	else
	{
		$IMP_FILE_NAME = $ABS_FILE_NAME;
	}

	$fp = fopen($IMP_FILE_NAME, "rb");
	if(!$fp)
		return GetMessage("IBLOCK_XML2_FILE_ERROR");

	if($sync)
		$table_name = "b_xml_tree_sync";
	else
		$table_name = "b_xml_tree";

	$NS = array("STEP"=>0);

	$obCatalog = new CIBlockCMLImport;
	$obCatalog->Init($NS, $WORK_DIR_NAME, $use_crc, $preview, false, false, false, $table_name);

	if($sync)
	{
		if(!$obCatalog->StartSession(bitrix_sessid()))
			return GetMessage("IBLOCK_XML2_TABLE_CREATE_ERROR");

		$obCatalog->ReadXMLToDatabase($fp, $NS, 0, 1024);

		$xml_root = $obCatalog->GetSessionRoot();
		$bUpdateIBlock = false;
	}
	else
	{
		$obCatalog->DropTemporaryTables();

		if(!$obCatalog->CreateTemporaryTables())
			return GetMessage("IBLOCK_XML2_TABLE_CREATE_ERROR");

		$obCatalog->ReadXMLToDatabase($fp, $NS, 0, 1024);

		if(!$obCatalog->IndexTemporaryTables())
			return GetMessage("IBLOCK_XML2_INDEX_ERROR");

		$xml_root = 1;
		$bUpdateIBlock = true;
	}

	fclose($fp);

	$result = $obCatalog->ImportMetaData($xml_root, $iblock_type, $site_id, $bUpdateIBlock);
	if($result !== true)
		return GetMessage("IBLOCK_XML2_METADATA_ERROR").implode("\n", $result);

	$obCatalog->ImportSections();
	$obCatalog->DeactivateSections($section_action);
	$obCatalog->SectionsResort();

	$obCatalog = new CIBlockCMLImport;
	$obCatalog->Init($NS, $WORK_DIR_NAME, $use_crc, $preview, false, false, false, $table_name);
	if($sync)
	{
		if(!$obCatalog->StartSession(bitrix_sessid()))
			return GetMessage("IBLOCK_XML2_TABLE_CREATE_ERROR");
	}
	$SECTION_MAP = false;
	$PRICES_MAP = false;
	$obCatalog->ReadCatalogData($SECTION_MAP, $PRICES_MAP);
	$result = $obCatalog->ImportElements(time(), 0);

	$obCatalog->DeactivateElement($element_action, time(), 0);
	if($sync)
		$obCatalog->EndSession();

	if($return_last_error)
	{
		if(strlen($obCatalog->LAST_ERROR))
			return $obCatalog->LAST_ERROR;
	}

	return true;
}

?>
