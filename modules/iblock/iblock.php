<?php
IncludeModuleLangFile(__FILE__);

if(!defined("CACHED_b_iblock_type")) // define("CACHED_b_iblock_type", 36000);
if(!defined("CACHED_b_iblock")) // define("CACHED_b_iblock", 36000);
if(!defined("CACHED_b_iblock_bucket_size")) // define("CACHED_b_iblock_bucket_size", 20);
if(!defined("CACHED_b_iblock_property_enum")) // define("CACHED_b_iblock_property_enum", 36000);
if(!defined("CACHED_b_iblock_property_enum_bucket_size")) // define("CACHED_b_iblock_property_enum_bucket_size", 100);

/** @global string $DBType */
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
	"CIBlockPropertyDate" => "classes/general/prop_date.php",
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
	"CIBlockPropertySectionAutoComplete" => "classes/general/prop_section_auto.php",
	"CAllIBlockOffersTmp" => "classes/general/iblockoffers.php",
	"CIBlockOffersTmp" => "classes/".$DBType."/iblockoffers.php",
	"CEventIblock" => "classes/general/iblock_event_list.php",
	"CRatingsComponentsIBlock" => "classes/general/ratings_components.php",
	"CIBlockRights" => "classes/general/iblock_rights.php",
	"CIBlockSectionRights" => "classes/general/iblock_rights.php",
	"CIBlockElementRights" => "classes/general/iblock_rights.php",
	"CIBlockRightsStorage" => "classes/general/iblock_rights.php",
	"CIBlockPropertyTools" => "classes/general/iblockproptools.php",
	"CIBlockSectionPropertyLink" => "classes/general/section_property.php",
	'\Bitrix\Iblock\ElementTable' => "lib/element.php",
	'\Bitrix\Iblock\IblockTable' => "lib/iblock.php",
	'\Bitrix\Iblock\IblockFieldTable' => "lib/iblockfield.php",
	'\Bitrix\Iblock\IblockGroupTable' => "lib/iblockgroup.php",
	'\Bitrix\Iblock\IblockMessageTable' => "lib/iblockmessage.php",
	'\Bitrix\Iblock\IblockRssTable' => "lib/iblockrss.php",
	'\Bitrix\Iblock\IblockSiteTable' => "lib/iblocksite.php",
	'\Bitrix\Iblock\InheritedPropertyTable' => "lib/inheritedproperty.php",
	'\Bitrix\Iblock\PropertyTable' => "lib/property.php",
	'\Bitrix\Iblock\PropertyEnumerationTable' => "lib/propertyenumeration.php",
	'\Bitrix\Iblock\SectionTable' => "lib/section.php",
	'\Bitrix\Iblock\SectionElementTable' => "lib/sectionelement.php",
	'\Bitrix\Iblock\SectionPropertyTable' => "lib/sectionproperty.php",
	'\Bitrix\Iblock\SequenceTable' => "lib/sequence.php",
	'\Bitrix\Iblock\SiteTable' => "lib/site.php",
	'\Bitrix\Iblock\TypeTable' => "lib/type.php",
	'\Bitrix\Iblock\TypeLanguageTable' => "lib/typelanguage.php",
	'\Bitrix\Iblock\BizprocType\UserTypeProperty' => "lib/bizproctype/usertypeproperty.php",
	'\Bitrix\Iblock\BizprocType\UserTypePropertyDiskFile' => "lib/bizproctype/usertypepropertydiskfile.php",
	'\Bitrix\Iblock\BizprocType\UserTypePropertyElist' => "lib/bizproctype/usertypepropertyelist.php",
	'\Bitrix\Iblock\BizprocType\UserTypePropertyEmployee' => "lib/bizproctype/usertypepropertyemployee.php",
	'\Bitrix\Iblock\BizprocType\UserTypePropertyHtml' => "lib/bizproctype/usertypepropertyhtml.php",
	'\Bitrix\Iblock\Component\Filters' => "lib/component/filters.php",
	'\Bitrix\Iblock\Component\Tools' => "lib/component/tools.php",
	'\Bitrix\Iblock\Helpers\Admin\Property' => "lib/helpers/admin/property.php",
	'\Bitrix\Iblock\InheritedProperty\BaseTemplate' => "lib/inheritedproperty/basetemplate.php",
	'\Bitrix\Iblock\InheritedProperty\BaseValues' => "lib/inheritedproperty/basevalues.php",
	'\Bitrix\Iblock\InheritedProperty\ElementTemplates' => "lib/inheritedproperty/elementtemplates.php",
	'\Bitrix\Iblock\InheritedProperty\ElementValues' => "lib/inheritedproperty/elementvalues.php",
	'\Bitrix\Iblock\InheritedProperty\IblockTemplates' => "lib/inheritedproperty/iblocktemplates.php",
	'\Bitrix\Iblock\InheritedProperty\IblockValues' => "lib/inheritedproperty/iblockvalues.php",
	'\Bitrix\Iblock\InheritedProperty\SectionTemplates' => "lib/inheritedproperty/sectiontemplates.php",
	'\Bitrix\Iblock\InheritedProperty\SectionValues' => "lib/inheritedproperty/sectionvalues.php",
	'\Bitrix\Iblock\Model\Section' => "lib/model/section.php",
	'\Bitrix\Iblock\PropertyIndex\Dictionary' => "lib/propertyindex/dictionary.php",
	'\Bitrix\Iblock\PropertyIndex\Element' => "lib/propertyindex/element.php",
	'\Bitrix\Iblock\PropertyIndex\Facet' => "lib/propertyindex/facet.php",
	'\Bitrix\Iblock\PropertyIndex\Indexer' => "lib/propertyindex/indexer.php",
	'\Bitrix\Iblock\PropertyIndex\Manager' => "lib/propertyindex/manager.php",
	'\Bitrix\Iblock\PropertyIndex\QueryBuilder' => "lib/propertyindex/querybuilder.php",
	'\Bitrix\Iblock\PropertyIndex\Storage' => "lib/propertyindex/storage.php",
	'\Bitrix\Iblock\Template\Engine' => "lib/template/engine.php",
	'\Bitrix\Iblock\Template\NodeRoot' => "lib/template/engine.php",
	'\Bitrix\Iblock\Template\NodeText' => "lib/template/engine.php",
	'\Bitrix\Iblock\Template\NodeEntityField' => "lib/template/engine.php",
	'\Bitrix\Iblock\Template\NodeFunction' => "lib/template/engine.php",
	'\Bitrix\Iblock\Template\Helper' => "lib/template/helper.php",
	'\Bitrix\Iblock\Template\Entity\Base' => "lib/template/entity/base.php",
	'\Bitrix\Iblock\Template\Entity\LazyValueLoader' => "lib/template/entity/base.php",
	'\Bitrix\Iblock\Template\Entity\CatalogStore' => "lib/template/entity/catalogstore.php",
	'\Bitrix\Iblock\Template\Entity\Element' => "lib/template/entity/element.php",
	'\Bitrix\Iblock\Template\Entity\ElementCatalog' => "lib/template/entity/elementcatalog.php",
	'\Bitrix\Iblock\Template\Entity\ElementCatalogMeasure' => "lib/template/entity/elementcatalog.php",
	'\Bitrix\Iblock\Template\Entity\ElementCatalogStoreList' => "lib/template/entity/elementcatalog.php",
	'\Bitrix\Iblock\Template\Entity\ElementPrice' => "lib/template/entity/elementprice.php",
	'\Bitrix\Iblock\Template\Entity\ElementProperty' => "lib/template/entity/elementproperty.php",
	'\Bitrix\Iblock\Template\Entity\ElementPropertyUserField' => "lib/template/entity/elementproperty.php",
	'\Bitrix\Iblock\Template\Entity\ElementPropertyEnum' => "lib/template/entity/elementproperty.php",
	'\Bitrix\Iblock\Template\Entity\ElementPropertyElement' => "lib/template/entity/elementproperty.php",
	'\Bitrix\Iblock\Template\Entity\ElementPropertySection' => "lib/template/entity/elementproperty.php",
	'\Bitrix\Iblock\Template\Entity\ElementSku' => "lib/template/entity/elementsku.php",
	'\Bitrix\Iblock\Template\Entity\ElementSkuPrice' => "lib/template/entity/elementskuprice.php",
	'\Bitrix\Iblock\Template\Entity\ElementSkuProperty' => "lib/template/entity/elementskuproperty.php",
	'\Bitrix\Iblock\Template\Entity\Iblock' => "lib/template/entity/iblock.php",
	'\Bitrix\Iblock\Template\Entity\Section' => "lib/template/entity/section.php",
	'\Bitrix\Iblock\Template\Entity\SectionPath' => "lib/template/entity/sectionpath.php",
	'\Bitrix\Iblock\Template\Entity\SectionProperty' => "lib/template/entity/sectionproperty.php",
	'\Bitrix\Iblock\Template\Functions\Fabric' => "lib/template/functions/fabric.php",
	'\Bitrix\Iblock\Template\Functions\FunctionBase' => "lib/template/functions/fabric.php",
	'\Bitrix\Iblock\Template\Functions\FunctionUpper' => "lib/template/functions/fabric.php",
	'\Bitrix\Iblock\Template\Functions\FunctionLower' => "lib/template/functions/fabric.php",
	'\Bitrix\Iblock\Template\Functions\FunctionTranslit' => "lib/template/functions/fabric.php",
	'\Bitrix\Iblock\Template\Functions\FunctionConcat' => "lib/template/functions/fabric.php",
	'\Bitrix\Iblock\Template\Functions\FunctionLimit' => "lib/template/functions/fabric.php",
	'\Bitrix\Iblock\Template\Functions\FunctionContrast' => "lib/template/functions/fabric.php",
	'\Bitrix\Iblock\Template\Functions\FunctionMin' => "lib/template/functions/fabric.php",
	'\Bitrix\Iblock\Template\Functions\FunctionMax' => "lib/template/functions/fabric.php",
	'\Bitrix\Iblock\Template\Functions\FunctionDistinct' => "lib/template/functions/fabric.php",
	'\Bitrix\Iblock\SenderEventHandler' => "lib/senderconnector.php",
	'\Bitrix\Iblock\SenderConnectorIblock' => "lib/senderconnector.php",
);
if (\Bitrix\Main\ModuleManager::isModuleInstalled('bizproc'))
	$arClasses["CIBlockDocument"] = "classes/general/iblockdocument.php";

\Bitrix\Main\Loader::registerAutoLoadClasses("iblock", $arClasses);

/**
 * Returns list of the information blocks of specified $type linked to the current site
 * including ELEMENT_CNT column which presents currently active elements.
 *
 * @param string $type Information blocks type to get blocks from.
 * @param array|string|int $arTypesInc Information block ID or CODE or array of IDs or CODEs to get.
 * @param array|string|int $arTypesExc Information block ID or CODE or array of IDs or CODEs to exclude.
 * @param array $arOrder Order in which blocks will be returned.
 * @param int $cnt Maximum count of iblocks to be returned.
 *
 * @return CIBlockResult
 * @deprecated No longer used by internal code and not recommended.
 */
function GetIBlockListWithCnt($type, $arTypesInc = array(), $arTypesExc = array(), $arOrder = array("sort" => "asc"), $cnt = 0)
{
	return GetIBlockListLang(SITE_ID, $type, $arTypesInc, $arTypesExc, $arOrder, $cnt, true);
}
/**
 * Returns list of the information blocks of specified $type linked to the current site
 *
 * @param string $type Information blocks type to get blocks from.
 * @param array|string|int $arTypesInc Information block ID or CODE or array of IDs or CODEs to get.
 * @param array|string|int $arTypesExc Information block ID or CODE or array of IDs or CODEs to exclude.
 * @param array $arOrder Order in which blocks will be returned.
 * @param int $cnt Maximum count of iblocks to be returned.
 *
 * @return CIBlockResult
 * @deprecated No longer used by internal code and not recommended.
 */
function GetIBlockList($type, $arTypesInc = array(), $arTypesExc = array(), $arOrder = array("sort" => "asc"), $cnt = 0)
{
	return GetIBlockListLang(SITE_ID, $type, $arTypesInc, $arTypesExc, $arOrder, $cnt);
}
/**
 * Returns list of the information blocks of specified $type linked to the specified site
 *
 * @param string $lang Site identifier blocks linked to.
 * @param string $type Information blocks type to get blocks from.
 * @param array|string|int $arTypesInc Information block ID or CODE or array of IDs or CODEs to get.
 * @param array|string|int $arTypesExc Information block ID or CODE or array of IDs or CODEs to exclude.
 * @param array $arOrder Order in which blocks will be returned.
 * @param int $cnt Maximum count of iblocks to be returned.
 * @param bool $bCountActive
 *
 * @return CIBlockResult
 * @deprecated No longer used by internal code and not recommended.
 */
function GetIBlockListLang($lang, $type, $arTypesInc = array(), $arTypesExc = array(), $arOrder = array("SORT" => "ASC"), $cnt = 0, $bCountActive = false)
{
	$arIDsInc = array();
	$arCODEsInc = array();
	if (is_array($arTypesInc))
	{
		foreach ($arTypesInc as $i)
		{
			if (intval($i) > 0)
				$arIDsInc[] = $i;
			else
				$arCODEsInc[] = $i;
		}
	}
	elseif (intval($arTypesInc) > 0)
	{
		$arIDsInc[] = $arTypesInc;
	}
	else
	{
		$arCODEsInc[] = $arTypesInc;
	}

	$arIDsExc = array();
	$arCODEsExc = array();
	if (is_array($arTypesExc))
	{
		foreach ($arTypesExc as $i)
		{
			if (intval($i) > 0)
				$arIDsExc[] = $i;
			else
				$arCODEsExc[] = $i;
		}
	}
	elseif (intval($arTypesExc) > 0)
	{
		$arIDsExc[] = $arTypesExc;
	}
	else
	{
		$arCODEsExc[] = $arTypesExc;
	}

	$res = CIBlock::GetList($arOrder, array(
		"type" => $type,
		"LID" => $lang,
		"ACTIVE" => "Y",
		"CNT_ACTIVE" => $bCountActive? "Y": "N",
		"ID" => $arIDsInc,
		"CODE" => $arCODEsInc,
		"!ID" => $arIDsExc,
		"!CODE" => $arCODEsExc,
	), $bCountActive);

	$dbr = new CIBlockResult($res);
	if ($cnt > 0)
		$dbr->NavStart($cnt);

	return $dbr;
}
/**
 * Returns an array with Information block fields or false if none found.
 * iblock have to be linked to the current site.
 *
 * @param int $ID Numeric identifier of the iblock
 * @param string $type Type of iblock restrict search to.
 *
 * @return array
 */

/**
 * <p>Функция возвращает информационный блок по коду <i>ID</i>, но только если он активен (ACTIVE равно Y) и имеет привязку к текущему сайту.    <br></p>
 *
 *
 * @param string $stringID  ID информационного блока.
 *
 * @param  $type = "" Тип информационного блока. Устанавливается в настройках модуля.
 * Если задан, то при выборке проверяется чтобы информационный блок
 * соответствовал этому типу.          <br>        Необязательный. По
 * умолчанию на информационный блок не накладываются ограничения
 * по типу.
 *
 * @return array <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblock">полей информационного
 * блока</a><code>#SITE_DIR#</code><code>#IBLOCK_ID#</code><br><p></p><div class="note"> <b>Примечание:</b>
 * функция вернет поля информационного блока только в том случае,
 * если информационный блок активен и привязан к текущему сайту.</div>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?<br>require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");<br><br>$APPLICATION-&gt;SetTitle("Продукты");<br><br>// проверим установлен ли модуль и получим блок с кодом $BID и типом catalog<br>if(CModule::IncludeModule("iblock") &amp;&amp; ($arIBlock = GetIBlock($_GET["BID"], "catalog")))<br>{<br>   // сделаем заголовок страницы таким же как название инф. блока<br>   $APPLICATION-&gt;SetTitle($arIBlock["NAME"]);<br>   //добавим название в навигационную цепочку<br>   $APPLICATION-&gt;AddChainItem($arIBlock["NAME"], $arIBlock["LIST_PAGE_URL"]);<br><br>   //работаем дальше с информационным блоком<br>   // ....<br>}<br>else<br>   ShowError("Информационный блок не найден.");<br><br>require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");<br>?&gt;<br>
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblock">Поля информационных
 * блоков</a></li>  </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/functions/getiblock.php
 * @author Bitrix
 */
function GetIBlock($ID, $type = "")
{
	return GetIBlockLang(SITE_ID, $ID, $type);
}
/**
 * Returns an array with Information block fields or false if none found.
 * iblock have to be linked to the current site.
 *
 * @param string $lang Site identifier block linked to.
 * @param int $ID Numeric identifier of the iblock
 * @param string $type Type of iblock restrict search to.
 *
 * @return array
 */
function GetIBlockLang($lang, $ID, $type="")
{
	$res = CIBlock::GetList(array(), array(
		"ID" => intval($ID),
		"TYPE" => $type,
		"LID" => $lang,
		"ACTIVE" => "Y",
	));
	if ($res)
	{
		$res = new CIBlockResult($res);
		return $res->GetNext();
	}
	else
	{
		return false;
	}
}
/**
 * Returns a list of the currently active elements of specified information blocks.
 * Checks permissions by default.
 *
 * @param string $type Information blocks type to get blocks from.
 * @param array|string|int $arTypesInc Information block ID or CODE or array of IDs or CODEs to get.
 * @param array|string|int $arTypesExc Information block ID or CODE or array of IDs or CODEs to exclude.
 * @param array $arOrder Order in which elements will be returned.
 * @param int $cnt Maximum count of elements to be returned.
 * @param array $arFilter Filter to be applied
 * @param array $arSelect Fields to return (all if empty or not supplied)
 * @param bool $arGroupBy Fields to group by (none grouping by default), overwrites $arSelect
 *
 * @return CIBlockResult
 * @deprecated No longer used by internal code and not recommended.
 */
function GetIBlockElementListEx($type, $arTypesInc = array(), $arTypesExc = array(), $arOrder = array("sort"=>"asc"), $cnt = 0, $arFilter = array(), $arSelect = array(), $arGroupBy = false)
{
	return GetIBlockElementListExLang(SITE_ID, $type, $arTypesInc, $arTypesExc, $arOrder, $cnt, $arFilter, $arSelect, $arGroupBy);
}
/**
 * Returns count of the currently active elements of specified information blocks.
 * Checks permissions by default.
 *
 * @param string $type Information blocks type to get blocks from.
 * @param array|string|int $arTypesInc Information block ID or CODE or array of IDs or CODEs to get.
 * @param array|string|int $arTypesExc Information block ID or CODE or array of IDs or CODEs to exclude.
 * @param array $arOrder Order in which elements will be returned.
 * @param int $cnt Maximum count of elements to be returned.
 * @param array $arFilter Filter to be applied
 *
 * @return int
 * @deprecated No longer used by internal code and not recommended.
 */
function GetIBlockElementCountEx($type, $arTypesInc = array(), $arTypesExc = array(), $arOrder = array("sort"=>"asc"), $cnt = 0, $arFilter = array())
{
	return GetIBlockElementListExLang(SITE_ID, $type, $arTypesInc, $arTypesExc, $arOrder, 0, $arFilter, false, array());
}
/**
 * Returns count of the currently active elements of specified information blocks.
 * Checks permissions by default.
 *
 * @param string $lang Site identifier blocks linked to.
 * @param string $type Information blocks type to get blocks from.
 * @param array|string|int $arTypesInc Information block ID or CODE or array of IDs or CODEs to get.
 * @param array|string|int $arTypesExc Information block ID or CODE or array of IDs or CODEs to exclude.
 * @param array $arOrder Order in which elements will be returned.
 * @param int $cnt Maximum count of elements to be returned.
 * @param array $arFilter Filter to be applied
 *
 * @return int
 * @deprecated No longer used by internal code and not recommended.
 */
function GetIBlockElementCountExLang($lang, $type, $arTypesInc = array(), $arTypesExc = array(), $arOrder = array("sort"=>"asc"), $cnt = 0, $arFilter = array())
{
	return GetIBlockElementListExLang($lang, $type, $arTypesInc, $arTypesExc, $arOrder, 0, $arFilter, false, array());
}
/**
 * Returns a list of the currently active elements of specified information blocks.
 * Checks permissions by default.
 *
 * @param string $lang Site identifier blocks linked to.
 * @param string $type Information blocks type to get blocks from.
 * @param array|string|int $arTypesInc Information block ID or CODE or array of IDs or CODEs to get.
 * @param array|string|int $arTypesExc Information block ID or CODE or array of IDs or CODEs to exclude.
 * @param array $arOrder Order in which elements will be returned.
 * @param int $cnt Maximum count of elements to be returned.
 * @param array $arFilter Filter to be applied
 * @param array $arSelect Fields to return (all if empty or not supplied)
 * @param bool $arGroupBy Fields to group by (none grouping by default), overwrites $arSelect
 *
 * @return CIBlockResult|int
 * @deprecated No longer used by internal code and not recommended.
 */
function GetIBlockElementListExLang($lang, $type, $arTypesInc = array(), $arTypesExc = array(), $arOrder = array("sort"=>"asc"), $cnt = 0, $arFilter = array(), $arSelect = array(), $arGroupBy = false)
{
	$filter = _GetIBlockElementListExLang_tmp($lang, $type, $arTypesInc, $arTypesExc, $arOrder, $cnt, $arFilter);

	if(is_array($cnt))
		$arNavParams = $cnt; //array("nPageSize"=>$cnt, "bShowAll"=>false);
	elseif($cnt > 0)
		$arNavParams = array("nTopCount"=>$cnt);
	else
		$arNavParams = false;

	return CIBlockElement::GetList($arOrder, $filter, $arGroupBy, $arNavParams, $arSelect);
}
/**
 * Makes filter for CIBlockElement::GetList. Internal function
 *
 * @param string $lang Site identifier blocks linked to.
 * @param string $type Information blocks type to get blocks from.
 * @param array|string|int $arTypesInc Information block ID or CODE or array of IDs or CODEs to get.
 * @param array|string|int $arTypesExc Information block ID or CODE or array of IDs or CODEs to exclude.
 * @param array $arOrder Order in which elements will be returned.
 * @param int $cnt Maximum count of elements to be returned.
 * @param array $arFilter Filter to be applied
 * @param array $arSelect Fields to return (all if empty or not supplied)
 *
 * @return array
 * @deprecated No longer used by internal code and not recommended.
 */
function _GetIBlockElementListExLang_tmp($lang, $type, $arTypesInc = array(), $arTypesExc = array(), $arOrder = array("sort" => "asc"), $cnt = 0, $arFilter = array(), $arSelect = array())
{
	$arIDsInc = array();
	$arCODEsInc = array();
	if (is_array($arTypesInc))
	{
		foreach ($arTypesInc as $i)
		{
			if (intval($i) > 0)
				$arIDsInc[] = $i;
			else
				$arCODEsInc[] = $i;
		}
	}
	elseif (intval($arTypesInc) > 0)
	{
		$arIDsInc[] = $arTypesInc;
	}
	elseif ($arTypesInc !== false)
	{
		$arCODEsInc[] = $arTypesInc;
	}

	$arIDsExc = array();
	$arCODEsExc = array();
	if (is_array($arTypesExc))
	{
		foreach ($arTypesExc as $i)
		{
			if (intval($i) > 0)
				$arIDsExc[] = $i;
			else
				$arCODEsExc[] = $i;
		}
	}
	elseif (intval($arTypesExc) > 0)
	{
		$arIDsExc[] = $arTypesExc;
	}
	elseif ($arTypesInc !== false)
	{
		$arCODEsExc[] = $arTypesExc;
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
/**
 * Returns number of active elements for given iblock.
 *
 * @param int $IBLOCK Information block ID.
 * @param bool $SECTION_ID Section ID.
 * @param array $arOrder Has no meaning here.
 * @param int $cnt Not used.
 *
 * @return int
 * @deprecated No longer used by internal code and not recommended.
 */
function GetIBlockElementCount($IBLOCK, $SECTION_ID = false, $arOrder = array("sort"=>"asc"), $cnt = 0)
{
	$filter = array(
		"IBLOCK_ID" => intval($IBLOCK),
		"ACTIVE_DATE" => "Y",
		"ACTIVE" => "Y",
		"CHECK_PERMISSIONS" => "Y",
	);
	if ($SECTION_ID !== false)
		$filter["SECTION_ID"] = intval($SECTION_ID);
	return CIBlockElement::GetList($arOrder, $filter, true);
}
/**
 * Return the list of the elements.
 *
 * @param int $IBLOCK Information block ID.
 * @param bool $SECTION_ID Section ID.
 * @param array $arOrder Has no meaning here.
 * @param int $cnt
 * @param array $arFilter
 * @param array $arSelect
 *
 * @return CIBlockResult
 * @deprecated No longer used by internal code and not recommended.
 */

/**
 * <p>Функция возвращает активные элементы из информационного блока <i>iblock_id</i>. </p> <p></p> <div class="note"> <b>Примечание:</b> функция является устаревшей, оставлена для обратной совместимости. Рекомендуется использоваться метод <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php">GetList</a>.</div>
 *
 *
 * @param int $iblock_id  ID информационного блока из которого будут выбраны элементы.
 *
 * @param int $section_id = false ID раздела, из которой требуется получить элементы. Для получения  
 *      элементов из корня информационного блока (не привязанные ни к
 * одному        разделу) установите параметр <i>section_id</i> =
 * 0.<br><br>Необязательный.        По умолчанию (false) выбираются все записи
 * без ограничения по  папкам.
 *
 * @param array $order = Array("SORT"=>"ASC") Порядок сортировки - массив вида Array(<i>by1</i>=&gt;<i>order1</i>[, 
 * 			<i>by2</i>=&gt;<i>order2</i> [, ..]]), где <i>by</i> - поле для        сортировки, может
 * принимать значения:  			<ul> <li> <b>sort</b> - индекс сортировки;  				</li> <li>
 * <b>timestamp_x</b> - дата изменения;  				</li> <li> <b>name</b> - название;  				</li> <li>
 * <b>id</b> - ID элемента;  				</li> <li> <b>active_from</b> - начало периода действия
 * элемента;  				</li> <li> <b>active_to</b> - окончание периода действия
 * элемента;  				</li> <li> <b>order</b> - порядок сортировки сортировки, может
 * принимать          значения:  				<ul> <li> <b>asc</b> - по возрастанию;  					</li> <li>
 * <b>desc</b> - по убыванию. </li> </ul>Необязательный. По умолчанию         
 * равен <i>Array("sort"=&gt;"asc")</i> </li> </ul>Полный список полей        сортировки и
 * дополнительную информацию <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php">смотрите</a> в <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/index.php">CIBlockElement</a>::<a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php">GetList()</a>
 *
 * @param int $cnt = 0 Максимальное количество записей, которые вернет функция. 
 * 			<br>Необязательный. По умолчанию выбираются все записи.
 *
 * @param array $arFilter = Array() Дополнительный фильтр по произвольным полям вида
 * Array("Фильтруемое        поле"=&gt;"Значение", ...). <br><i>Фильтруемое поле</i>
 * может принимать        значения:  			<ul> <li> <b>ID</b> - по коду;  				</li> <li>
 * <b>ACTIVE</b> - фильтр по активности (Y|N); передача пустого          значения
 * (<i>"ACTIVE"=&gt;""</i>) выводит все элементы без учета их          состояния; 
 * 				</li> <li> <b>NAME</b> - по имени и фамилии (можно искать по шаблону [%_]); 
 * 				</li> <li> <b>PREVIEW_TEXT</b> - по имени и фамилии (можно искать по шаблону     
 *     [%_]);  				</li> <li> <b>DETAIL_TEXT</b> - по детальному описанию (можно искать по
 * шаблону          [%_]);  				</li> <li> <b>SEARCHABLE_CONTENT</b> - по содержимому для
 * поиска. Включает в          себя название, описание для анонса и
 * детальное описание (можно искать по          шаблону [%_]);  				</li> <li>
 * <b>CODE</b> - по символьному идентификатору (можно искать по         
 * шаблону [%_]);  				</li> <li> <b>SORT</b> - по сортировке;  				</li> <li> <b>EXTERNAL_ID</b> - по
 * внешнему коду (можно искать по шаблону          [%_]);  				</li> <li>
 * <b>TIMESTAMP_X</b> - по времени изменения;  				</li> <li> <b>DATE_CREATE</b> - по времени
 * создания;  				</li> <li> <b>DATE_ACTIVE_FROM</b> - по дате начала активности;  				</li>
 * <li> <b>DATE_ACTIVE_TO</b> - по дате окончанию активности;  				</li> <li> <b>ACTIVE_DATE</b> -
 * непустое значение задействует фильтр по датам          активности
 * (<i>DATE_ACTIVE_FROM</i> и <i>DATE_ACTIVE_TO</i>). Если          значение не установлено
 * (<i>""</i>), фильтрация по датам активности не          производится; 
 * 				</li> <li> <b>IBLOCK_ID</b> - по коду информационного блока;  				</li> <li>
 * <b>IBLOCK_CODE</b> - по символьному коду информационного блока          (можно
 * искать по шаблону [%_]);  				</li> <li> <b>IBLOCK_LID</b> - по языку (можно искать
 * по шаблону [%_]);  				</li> <li> <b>IBLOCK_TYPE</b> - по типу блока (можно искать по
 * шаблону [%_]);  				</li> <li> <b>IBLOCK_ACTIVE</b> - по активности блока (можно
 * искать по шаблону          [%_]);  				</li> <li> <b>SECTION_ID</b> - по родительскому
 * разделу;  				</li> <li> <b>PROPERTY_&lt;код свойства&gt;</b> - фильтр по значениям
 * свойств          (можно искать по шаблону [%_]), для свойств типа
 * "список", поиск будет          осуществляться не по значению
 * перечисления, а по его идентификатору;  				</li> <li> <b>PROPERTY_&lt;код
 * свойства&gt;_VALUE</b> - фильтр по значениям          списка для свойств
 * типа "список" (можно искать по шаблону [%_]), поиск          будет
 * осуществляться по строковому значению списка, а не по         
 * идентификатору;  				</li> <li> <b>CATALOG_&lt;CATALOG_FIELD&gt;_&lt;PRICE_TYPE&gt;</b> - по полю 
 * 				<i>CATALOG_FIELD </i>из цены типа <i>PRICE_TYPE </i>(ID типа цены), где  				<i>CATALOG_FIELD
 * </i>может быть: <i>PRICE </i>- цена, <i>CURRENCY </i>-          валюта. </li> </ul>Все
 * фильтруемые поля (кроме <i>SECTION_ID </i>и  			<i>ACTIVE_DATE</i>)<i> </i>могут
 * содержать перед названием <a
 * href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2683" >тип проверки
 * фильтра</a>, а поля <i>SECTION_ID  			</i>и <i>ACTIVE_DATE </i>могут содержать перед
 * названием тип проверки        фильтра "!" - не равно.<br><br><i>Значения
 * фильтра</i> - одиночное        значение или
 * массив.<br><br>Необязательный. По умолчанию - пустой       
 * массив.<br>Полный список полей фильтра и дополнительную
 * информацию <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php">смотрите</a> в <b><a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/index.php">CIBlockElement</a>::<a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php">GetList()</a>.</b>
 *
 * @return CIBlockResult <p>Функция возвращает объект класса <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/index.php">CIBlockResult</a> с активными 
 * элементами (у которых установлен флаг "Активен", выполняется
 * условие периода  активности и находящиеся в активных
 * информационных блоках для текущего  сайта).</p><p></p><div class="note">
 * <b>Примечание:</b> при работе с результатом рекомендуется применять
 * метод класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>::<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/getnext.php">GetNext()</a>,  результатом
 * которого будет массив с полями элемента информационного блока.
 * Все  поля при этом будут преобразованы в "HTML безопасный" вид, а в
 * полях с шаблонами  URL-ов к страницам (LIST_PAGE_URL - списка элементов и
 * DETAIL_PAGE_URL -  детального просмотра) будут заменены параметры
 * <code>#SITE_DIR#</code>,  <code>#IBLOCK_ID#</code>, <code>#EXTERNAL_ID#</code> и <code>#ID#</code>. Если 
 * результат пуст или достигнут конец выборки <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>::<a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/getnext.php">GetNext()</a>  вернет false.
 * Также можно воспользоваться любыми другими методами класса <a
 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>,  но при этом в
 * полях LIST_PAGE_URL и DETAIL_PAGE_URL будут оригинальные (как они  введены в
 * форме редактирования информационного блока) шаблоны URL-ов, а не с 
 * замененными параметрами <code>#SITE_DIR#</code>, <code>#IBLOCK_ID#</code>, 
 * <code>#EXTERNAL_ID#</code> и <code>#ID#</code>. </div>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#felement">Поля элементов
 * информационного  блока</a>  </li> <li> <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/functions/getiblockelementlistex.php">Функция
 * GetIBlockElementListEx</a> </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/functions/getiblockelementlist.php
 * @author Bitrix
 * @deprecated No longer used by internal code and not recommended.
 */
function GetIBlockElementList($IBLOCK, $SECTION_ID = false, $arOrder = array("sort"=>"asc"), $cnt = 0, $arFilter = array(), $arSelect = array())
{
	$filter = array(
		"IBLOCK_ID" => intval($IBLOCK),
		"ACTIVE_DATE" => "Y",
		"ACTIVE" => "Y",
		"CHECK_PERMISSIONS" => "Y",
	);
	if ($SECTION_ID !== false)
		$filter["SECTION_ID"]=intval($SECTION_ID);

	if (is_array($arFilter) && !empty($arFilter))
		$filter = array_merge($filter, $arFilter);

	$dbr = CIBlockElement::GetList($arOrder, $filter, false, false, $arSelect);
	if ($cnt > 0)
		$dbr->NavStart($cnt);

	return $dbr;
}
/**
 * Returns an array with element fields and PROPERTIES key containing element property values.
 * false when element not active or not exists.
 *
 * @param int $ID Identifier of the elements to be returned.
 * @param string $TYPE Information block type identifier to filter elements with.
 *
 * @return array|bool
 * @deprecated No longer used by internal code and not recommended.
 */

/**
 * <p>Функция возвращает информационный элемент с кодом <i>ID</i>. Функция-помошник, которая выбирает все базовые поля элемента, его свойства и информацию об инфоблоке. Использует <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php">GetList</a>. </p> <p></p> <div class="note"> <b>Примечание:</b> функция является устаревшей, оставлена для обратной совместимости. Рекомендуется использоваться метод <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php">GetList</a>.</div>
 *
 *
 * @param mixed $intID  ID элемента.
 *
 * @param string $type = "" Тип информационного блока. Устанавливается в настройках модуля.
 * Если задан, то при выборке проверяется чтобы элемент
 * соответствовал этому типу.          <br>        Необязательный. По
 * умолчанию на элемент не накладываются ограничения по типу.
 *
 * @return array <p>Функция возвращает массив <a
 * href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#felement">полей информационного
 * элемента</a> и дополнительно следующие поля: </p><table width="100%"
 * class="tnormal"><tbody> <tr> <th width="15%">Поле</th> <th>Значение</th> </tr> <tr> <td>IBLOCK_NAME</td>
 * 	<td>Название информационного блока.</td> </tr> <tr> <td>PROPERTIES</td> <td>Массив
 * значений свойств, имеющий в качестве индексов "Символьный код
 * свойства" (задается в настройках информационного блока) или, если
 * код не задан, то уникальное ID свойства. Значением каждого
 * свойства будет массив вида: <pre class="syntax">Array(   	"NAME"=&gt;"название
 * свойства",   	"DEFAULT_VALUE"=&gt;"значение свойства по умолчанию",  
 * 	"VALUE"=&gt;"значение свойства или массив значений свойств, если
 * свойство множественное",   	"VALUE_ENUM_ID"=&gt;"Код значения свойства типа
 * &lt;Список&gt;"   	)</pre> </td> </tr> </tbody></table><p> Если заданным параметрам не
 * найден элемент, функция вернет <i>false</i>.    <br>  Выборка элемента
 * происходит только из активных элементов инфоблока, неактивный
 * элемент выбран быть не может. </p><p></p><div class="note"> <b>Примечание:</b> все
 * возвращаемые поля преобразованы в "HTML безопасный" вид, а в полях
 * (LIST_PAGE_URL - списка элементов и DETAIL_PAGE_URL - детального просмотра) с
 * шаблонами URL-ов к страницам будут заменены параметры
 * <code>#SITE_DIR#</code>, <code>#IBLOCK_ID#</code>, <code>#EXTERNAL_ID#</code> и <code>#ID#</code>.</div>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?<br>require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');<br><br>$APPLICATION-&gt;SetTitle('Карточка товара');<br><br>// подключим модуль и выберем элемент ID типа product<br>$arIBlockElement = false;<br>if(CModule::IncludeModule('iblock') &amp;&amp; ($arIBlockElement = GetIBlockElement($ID, 'product')))<br>{<br>   // В заголовок страницы вставим название элемента<br>   $APPLICATION-&gt;SetTitle($arIBlockElement['NAME']);<br>   // В навигационную цепочку вставим название и ссылку на текущий информационный блок<br>   $APPLICATION-&gt;AddChainItem($arIBlockElement['IBLOCK_NAME'], 'products.php?ID='.$arIBlockElement['IBLOCK_ID']);<br><br>   // выведем детальную картинку<br>   echo ShowImage($arIBlockElement['DETAIL_PICTURE'], 150, 150, 'border="0"', '', true);<br>   // выведем детальное описание<br>   echo $arIBlockElement['DETAIL_TEXT'].'&lt;br&gt;';<br>   // выведем значение свойства с кодом PRICE<br>   echo $arIBlockElement['PROPERTIES']['PRICE']['VALUE'].'&lt;br&gt;';<br><br>   // вывeдем оставшиеся свойсва<br>   $arProps = $arIBlockElement['PROPERTIES'];<br>   foreach($arProps as $property_code=&gt;$arValue)<br>   {<br>      // если это свойство с кодом PRICE или значение свойства не введено - пропустим<br>      if($property_code=='PRICE' <br>			|| (!is_array($arValue['VALUE']) &amp;&amp; strlen($arValue['VALUE'])&lt;=0) <br>			|| (is_array($arValue['VALUE']) &amp;&amp; count($arValue['VALUE'])&lt;=0)<br>			)<br>         continue;<br><br>      // выведем пару "Название: значение"<br>      if(!is_array($arValue['VALUE']))<br>         echo $arValue['NAME'].": ".$arValue['VALUE'];<br>      else<br>      {<br>         echo $arValue['NAME'].': ';<br>         foreach($arValue['VALUE'] as $val)<br>         {<br>            echo $val.'&lt;br&gt;';<br>         }<br>      }<br>   }<br>}<br>else<br>   echo ShowError('Новость не найдена');<br><br>require($_SERVER["DOCUMENT_ROOT"].'/bitrix/footer.php");<br>?&gt;<br>
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#felement">Поля информационного
 * элемента</a></li>  </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/functions/getiblockelement.php
 * @author Bitrix
 * @deprecated No longer used by internal code and not recommended.
 */
function GetIBlockElement($ID, $TYPE = "")
{
	$filter = array(
		"ID" => intval($ID),
		"ACTIVE_DATE" => "Y",
		"ACTIVE" => "Y",
		"CHECK_PERMISSIONS" => "Y",
	);
	if ($TYPE != "")
		$filter["IBLOCK_TYPE"] = $TYPE;

	$iblockElement = CIBlockElement::GetList(array(), $filter);
	if($obIBlockElement = $iblockElement->GetNextElement())
	{
		$arIBlockElement = $obIBlockElement->GetFields();
		if ($arIBlock = GetIBlock($arIBlockElement["IBLOCK_ID"], $TYPE))
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
/**
 * Returns list of sections of specified iblock including ELEMENT_CNT column.
 *
 * @param int $IBLOCK
 * @param bool|int $SECT_ID
 * @param array $arOrder
 * @param int $cnt
 * @param array $arFilter
 *
 * @return CIBlockResult
 * @deprecated No longer used by internal code and not recommended.
 */
function GetIBlockSectionListWithCnt($IBLOCK, $SECT_ID = false, $arOrder = array("left_margin"=>"asc"), $cnt = 0, $arFilter = array())
{
	$filter = array(
		"IBLOCK_ID" => intval($IBLOCK),
		"ACTIVE" => "Y",
		"CNT_ACTIVE" => "Y",
	);
	if ($SECT_ID !== false)
		$filter["SECTION_ID"] = intval($SECT_ID);

	if (is_array($arFilter) && !empty($arFilter))
		$filter = array_merge($filter, $arFilter);

	$dbr = CIBlockSection::GetList($arOrder, $filter, true);
	if($cnt > 0)
		$dbr->NavStart($cnt);

	return $dbr;
}
/**
 * Returns list of sections of specified iblock.
 *
 * @param int $IBLOCK
 * @param bool|int $SECT_ID
 * @param array $arOrder
 * @param int $cnt
 * @param array $arFilter
 *
 * @return CIBlockResult
 * @deprecated No longer used by internal code and not recommended.
 */
function GetIBlockSectionList($IBLOCK, $SECT_ID = false, $arOrder = array("left_margin"=>"asc"), $cnt = 0, $arFilter = array())
{
	$filter = array(
		"IBLOCK_ID" => intval($IBLOCK),
		"ACTIVE" => "Y",
		"IBLOCK_ACTIVE" => "Y",
	);
	if ($SECT_ID !== false)
		$filter["SECTION_ID"] = intval($SECT_ID);

	if(is_array($arFilter) && !empty($arFilter))
		$filter = array_merge($filter, $arFilter);

	$dbr = CIBlockSection::GetList($arOrder, $filter);
	if ($cnt > 0)
		$dbr->NavStart($cnt);

	return $dbr;
}
/**
 * Returns an array with section fields if found. Else returns false.
 *
 * @param int $ID
 * @param string $TYPE
 *
 * @return array|bool
 * @deprecated No longer used by internal code and not recommended.
 */

/**
 * <p>Функция возвращает раздел информационного блока. </p>
 *
 *
 * @param int $intID  ID раздела.
 *
 * @param string $type = "" Тип информационного блока, в котором размещён раздел.
 * Устанавливается в настройках модуля. Если задан, то при выборке
 * проверяется чтобы раздел соответствовал этому типу.
 * <br>Необязательный. По умолчанию на раздел не накладываются
 * ограничения по типу.
 *
 * @return array <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fsection">полей
 * раздела</a><i>IBLOCK_NAME</i><br><p></p><div class="note"> <b>Примечание:</b> функция
 * вернет поля раздела информационного блока только в том случае,
 * если    раздел "активен" и находится в информационный блоке,
 * который также    "активен" и привязан к текущему сайту. </div>
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
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
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fsection">Поля раздела
 * информационного блока</a></li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/functions/getiblocksection.php
 * @author Bitrix
 * @deprecated No longer used by internal code and not recommended.
 */
function GetIBlockSection($ID, $TYPE = "")
{
	$ID = intval($ID);
	if($ID > 0)
	{
		$iblockSection = CIBlockSection::GetList(array(), array(
			"ID" => $ID,
			"ACTIVE" => "Y",
		));
		if($arIBlockSection = $iblockSection->GetNext())
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

/**
 * Returns path to the section.
 *
 * @param int $IBLOCK_ID
 * @param int $SECTION_ID
 * @return CIBlockResult
 * @deprecated No longer used by internal code and not recommended.
 */
function GetIBlockSectionPath($IBLOCK_ID, $SECTION_ID)
{
	return CIBlockSection::GetNavChain($IBLOCK_ID, $SECTION_ID);
}
/**
 * Converts xml string into recursive array
 *
 * @param string $data
 * @return array
 * @deprecated No longer used by internal code and not recommended.
 */
function xmlize_rss($data)
{
	$data = trim($data);
	$values = $index = $array = array();
	$parser = xml_parser_create();
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($parser, $data, $values, $index);
	xml_parser_free($parser);

	$i = 0;

	$tagName = $values[$i]['tag'];
	if (isset($values[$i]['attributes']))
		$array[$tagName]['@'] = $values[$i]['attributes'];
	else
		$array[$tagName]['@'] = array();

	$array[$tagName]["#"] = xml_depth_rss($values, $i);

	return $array;
}
/**
 * Helper function for xmlize_rss
 *
 * @param array $values
 * @param int $i
 * @return array
 * @deprecated No longer used by internal code and not recommended.
 */
function xml_depth_rss($values, &$i)
{
	$children = array();

	if (isset($values[$i]['value']))
		array_push($children, $values[$i]['value']);

	while (++$i < count($values))
	{
		switch ($values[$i]['type'])
		{
			case 'open':
				if (isset($values[$i]['tag']))
					$tagName = $values[$i]['tag'];
				else
					$tagName = '';

				if (isset($children[$tagName]))
					$size = sizeof($children[$tagName]);
				else
					$size = 0;

				if (isset($values[$i]['attributes']))
					$children[$tagName][$size]['@'] = $values[$i]["attributes"];

				$children[$tagName][$size]['#'] = xml_depth_rss($values, $i);
			break;

			case 'cdata':
				array_push($children, $values[$i]['value']);
			break;

			case 'complete':
				$tagName = $values[$i]['tag'];

				if(isset($children[$tagName]))
					$size = sizeof($children[$tagName]);
				else
					$size = 0;

				if(isset($values[$i]['value']))
					$children[$tagName][$size]["#"] = $values[$i]['value'];
				else
					$children[$tagName][$size]["#"] = '';

				if (isset($values[$i]['attributes']))
					$children[$tagName][$size]['@'] = $values[$i]['attributes'];
			break;

			case 'close':
				return $children;
			break;
		}

	}

	return $children;
}
/**
 * Returns html presenting a control of two drop boxes to choose iblock iblock from.
 *
 * @param int $IBLOCK_ID Selected iblock
 * @param string $strTypeName Name of the iblock type select
 * @param string $strIBlockName Name of the iblock name select
 * @param bool|array $arFilter Additional filter for iblock list
 * @param string $onChangeType Additional JS handler for type select
 * @param string $onChangeIBlock Additional JS handler for iblock select
 * @param string $strAddType Additional html inserted into type select
 * @param string $strAddIBlock Additional html inserted into iblock select
 * @return string
 */
function GetIBlockDropDownListEx($IBLOCK_ID, $strTypeName, $strIBlockName, $arFilter = false, $onChangeType = '', $onChangeIBlock = '', $strAddType = '', $strAddIBlock = '')
{
	$html = '';

	static $arTypesAll = array();
	static $arTypes = array();
	static $arIBlocks = array();

	if(!is_array($arFilter))
		$arFilter = array();
	if (!array_key_exists('MIN_PERMISSION',$arFilter) || trim($arFilter['MIN_PERMISSION']) == '')
		$arFilter["MIN_PERMISSION"] = "W";
	$filterId = md5(serialize($arFilter));

	if(!isset($arTypes[$filterId]))
	{
		$arTypes[$filterId] = array(0 => GetMessage("IBLOCK_CHOOSE_IBLOCK_TYPE"));
		$arIBlocks[$filterId] = array(0 => array(''=>GetMessage("IBLOCK_CHOOSE_IBLOCK")));

		$rsIBlocks = CIBlock::GetList(array("IBLOCK_TYPE" => "ASC", "NAME" => "ASC"), $arFilter);
		while($arIBlock = $rsIBlocks->Fetch())
		{
			$tmpIBLOCK_TYPE_ID = $arIBlock["IBLOCK_TYPE_ID"];
			if(!array_key_exists($tmpIBLOCK_TYPE_ID, $arTypesAll))
			{
				$arType = CIBlockType::GetByIDLang($tmpIBLOCK_TYPE_ID, LANG);
				$arTypesAll[$arType["~ID"]] = $arType["~NAME"]." [".$arType["~ID"]."]";
			}
			if(!array_key_exists($tmpIBLOCK_TYPE_ID, $arTypes[$filterId]))
			{
				$arTypes[$filterId][$tmpIBLOCK_TYPE_ID] = $arTypesAll[$tmpIBLOCK_TYPE_ID];
				$arIBlocks[$filterId][$tmpIBLOCK_TYPE_ID] = array(0 => GetMessage("IBLOCK_CHOOSE_IBLOCK"));
			}
			$arIBlocks[$filterId][$tmpIBLOCK_TYPE_ID][$arIBlock["ID"]] = $arIBlock["NAME"]." [".$arIBlock["ID"]."]";
		}

		$html .= '
		<script type="text/javascript">
		function OnType_'.$filterId.'_Changed(typeSelect, iblockSelectID)
		{
			var arIBlocks = '.CUtil::PhpToJSObject($arIBlocks[$filterId]).';
			var iblockSelect = BX(iblockSelectID);
			if(!!iblockSelect)
			{
				for(var i=iblockSelect.length-1; i >= 0; i--)
					iblockSelect.remove(i);
				for(var j in arIBlocks[typeSelect.value])
				{
					var newOption = new Option(arIBlocks[typeSelect.value][j], j, false, false);
					iblockSelect.options.add(newOption);
				}
			}
		}
		</script>
		';
	}

	$IBLOCK_TYPE = false;
	if($IBLOCK_ID > 0)
	{
		foreach($arIBlocks[$filterId] as $iblock_type_id => $iblocks)
		{
			if(array_key_exists($IBLOCK_ID, $iblocks))
			{
				$IBLOCK_TYPE = $iblock_type_id;
				break;
			}
		}
	}

	$htmlTypeName = htmlspecialcharsbx($strTypeName);
	$htmlIBlockName = htmlspecialcharsbx($strIBlockName);
	$onChangeType = 'OnType_'.$filterId.'_Changed(this, \''.CUtil::JSEscape($strIBlockName).'\');'.$onChangeType.';';
	$onChangeIBlock = trim($onChangeIBlock);

	$html .= '<select name="'.$htmlTypeName.'" id="'.$htmlTypeName.'" onchange="'.htmlspecialcharsbx($onChangeType).'" '.$strAddType.'>'."\n";
	foreach($arTypes[$filterId] as $key => $value)
	{
		if($IBLOCK_TYPE === false)
			$IBLOCK_TYPE = $key;
		$html .= '<option value="'.htmlspecialcharsbx($key).'"'.($IBLOCK_TYPE===$key? ' selected': '').'>'.htmlspecialcharsEx($value).'</option>'."\n";
	}
	$html .= "</select>\n";
	$html .= "&nbsp;\n";
	$html .= '<select name="'.$htmlIBlockName.'" id="'.$htmlIBlockName.'"'.($onChangeIBlock != ''? ' onchange="'.htmlspecialcharsbx($onChangeIBlock).'"': '').' '.$strAddIBlock.'>'."\n";
	foreach($arIBlocks[$filterId][$IBLOCK_TYPE] as $key => $value)
	{
		$html .= '<option value="'.htmlspecialcharsbx($key).'"'.($IBLOCK_ID==$key? ' selected': '').'>'.htmlspecialcharsEx($value).'</option>'."\n";
	}
	$html .= "</select>\n";

	return $html;
}
/**
 * Returns html presenting a control of two drop boxes to choose iblock iblock from.
 * All iblock permission check to be at least W
 *
 * @param int $IBLOCK_ID Selected iblock
 * @param string $strTypeName Name of the iblock type select
 * @param string $strIBlockName Name of the iblock name select
 * @param bool|array $arFilter Additional filter for iblock list
 * @param string $strAddType Additional html inserted into type select
 * @param string $strAddIBlock Additional html inserted into iblock select
 * @return string
 */
function GetIBlockDropDownList($IBLOCK_ID, $strTypeName, $strIBlockName, $arFilter = false, $strAddType = '', $strAddIBlock = '')
{
	if(!is_array($arFilter))
		$arFilter = array();
	$arFilter["MIN_PERMISSION"] = "W";

	return GetIBlockDropDownListEx($IBLOCK_ID, $strTypeName, $strIBlockName, $arFilter, '', '', $strAddType, $strAddIBlock);
}
/**
 * Imports an xml file into iblock. File may be an .tar.gz archive.
 *
 * @param string $file_name Name of the file to import
 * @param string $iblock_type IBlock type ID to import iblock to
 * @param string|array $site_id ID of the site or array of IDs to bind iblock to
 * @param string $section_action What to do with sections missed in the file. D - delete or A - deactivate.
 * @param string $element_action What to do with elements missed in the file. D - delete or A - deactivate.
 * @param bool $use_crc Whenever to use CRC check for optimizi=ation or force an update
 * @param bool $preview If true when use iblock settings to generate preview pictures from detail.
 * @param bool $sync If true uses alternative set of tables in order not to interfere with other import processes
 * @param bool $return_last_error If true will return string with error description in case of failure
 * @param bool $return_iblock_id If true will return iblock identifier (int) in case of success
 * @return bool|int|string
 */

/**
 * <p>Функция выполняет импорт xml-файла в инфоблок.</p>
 *
 *
 * @param string $file_name  Путь к xml-файлу.
 *
 * @param string $iblock_type = "-" Тип инфоблока, в который импортировать.
 *
 * @param array $site_id = '' Сайт, к которому будет привязан инфоблок.
 *
 * @param string $section_action = "D" Действие, которое осуществляется с секциями, отсутствующими в
 * файле импорта ("N" - ничего; "A" - деактивировать; "D" - удалить,
 * используется по умолчанию).
 *
 * @param string $element_action = "D" Действие, которое осуществляется с элементами, отсутствующими в
 * файле импорта ("N" - ничего; "A" - деактивировать; "D" - удалить,
 * используется по умолчанию).
 *
 * @param bool $use_crc = false Использование контрольных сумм при импорте для увеличения
 * производительности и избежания лишних обновлений элементов.
 *
 * @param bool $preview = false Если <i>true</i>, то формировать картинку анонса из детальной
 * картинки.
 *
 * @param bool $sync = false Позволяет разнести процесс синхронизации с 1С и обмен новостями
 * через контроллер.
 *
 * @param bool $return_last_error = false Если <i>true</i>, то функция в случае ошибки вернёт строку, иначе null.
 *
 * @param bool $return_iblock_id = false Если <i>true</i>, то функция в случае успеха вернёт идентификатор
 * инфоблока (нового или обновлённого).
 *
 * @return mixed <p>Возвращает <i>true</i> в случае успешного импорта или строку с
 * сообщением об ошибке.</p><br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/functions/importxmlfile.php
 * @author Bitrix
 */
function ImportXMLFile($file_name, $iblock_type="-", $site_id='', $section_action="D", $element_action="D", $use_crc=false, $preview=false, $sync=false, $return_last_error=false, $return_iblock_id=false)
{
	/** @global CMain $APPLICATION */
	global $APPLICATION;
	$ABS_FILE_NAME = false;

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
	$obCatalog->ImportElements(time(), 0);
	$obCatalog->ImportProductSets();

	$obCatalog->DeactivateElement($element_action, time(), 0);
	if($sync)
		$obCatalog->EndSession();

	if($return_last_error)
	{
		if(strlen($obCatalog->LAST_ERROR))
			return $obCatalog->LAST_ERROR;
	}

	if ($return_iblock_id)
		return intval($NS["IBLOCK_ID"]);
	else
		return true;
}
