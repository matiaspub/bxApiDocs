<?
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
$module_id = 'catalog';

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main;
use Bitrix\Currency;
use Bitrix\Catalog;
use Bitrix\Sale;

// define('CATALOG_NEW_OFFERS_IBLOCK_NEED','-1');

$bReadOnly = !$USER->CanDoOperation('catalog_settings');
if ($USER->CanDoOperation('catalog_read') || !$bReadOnly)
{
	Loader::includeModule('catalog');
	Loc::loadMessages(__FILE__);

	$useSaleDiscountOnly = false;
	$saleIsInstalled = ModuleManager::isModuleInstalled('sale');
	if ($saleIsInstalled)
		$useSaleDiscountOnly = (string)Option::get('sale', 'use_sale_discount_only') == 'Y';

	$applyDiscSaveModeList = CCatalogDiscountSave::GetApplyModeList(true);

	$saleSettingsUrl = 'settings.php?lang='.LANGUAGE_ID.'&mid=sale&mid_menu=1';

	if ($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_REQUEST['RestoreDefaults']) && !$bReadOnly && check_bitrix_sessid())
	{
		$strValTmp = '';
		if (!$USER->IsAdmin())
			$strValTmp = Option::get('catalog', 'avail_content_groups');

		Option::delete('catalog', array());
		$v1 = 'id';
		$v2 = 'asc';
		$z = CGroup::GetList($v1, $v2, array("ACTIVE" => "Y", "ADMIN" => "N"));
		while($zr = $z->Fetch())
			$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));

		if (!$USER->IsAdmin())
			Option::set('catalog', 'avail_content_groups', $strValTmp, '');
	}

	$arAllOptions = array(
		array("export_default_path", Loc::getMessage("CAT_EXPORT_DEFAULT_PATH"), "/bitrix/catalog_export/", array("text", 30)),
		array("default_catalog_1c", Loc::getMessage("CAT_DEF_IBLOCK"), "", array("text", 30)),
		array("deactivate_1c_no_price", Loc::getMessage("CAT_DEACT_NOPRICE"), "N", array("checkbox")),
		array("yandex_xml_period", Loc::getMessage("CAT_YANDEX_XML_PERIOD"), "24", array("text", 5)),
	);

	$strWarning = "";
	$strOK = "";
	if ($_SERVER['REQUEST_METHOD'] == "POST" && !empty($_POST['Update']) && !$bReadOnly && check_bitrix_sessid())
	{
		for ($i = 0, $cnt = count($arAllOptions); $i < $cnt; $i++)
		{
			$name = $arAllOptions[$i][0];
			$val = (isset($_POST[$name]) ? trim($_POST[$name]) : '');
			if ($arAllOptions[$i][3][0]=="checkbox" && $val!="Y")
				$val = "N";
			if ($val == '')
				$val = $arAllOptions[$i][2];
			if ($name == 'export_default_path')
			{
				$boolExpPath = true;
				if (empty($val))
				{
					$boolExpPath = false;
				}
				if ($boolExpPath)
				{
					$val = str_replace('//','/',Rel2Abs('/', $val.'/'));
					if (preg_match(BX_CATALOG_FILENAME_REG, $val))
						$boolExpPath = false;
				}
				if ($boolExpPath)
				{
					if (empty($val) || '/' == $val)
						$boolExpPath = false;
				}
				if ($boolExpPath)
				{
					if (!file_exists($_SERVER['DOCUMENT_ROOT'].$val) || !is_dir($_SERVER['DOCUMENT_ROOT'].$val))
						$boolExpPath = false;
				}
				if ($boolExpPath)
				{
					if ($APPLICATION->GetFileAccessPermission($val) < 'W')
						$boolExpPath = false;
				}

				if ($boolExpPath)
				{
					Option::set('catalog', $name, $val, '');
				}
				else
				{
					$strWarning .= Loc::getMessage('CAT_PATH_ERR_EXPORT_FOLDER_BAD').'<br />';
				}
			}
			else
			{
				Option::set('catalog', $name, $val, '');
			}
		}

		$default_outfile_action = (isset($_REQUEST['default_outfile_action']) ? (string)$_REQUEST['default_outfile_action'] : '');
		if ($default_outfile_action!="D" && $default_outfile_action!="H" && $default_outfile_action!="F")
		{
			$default_outfile_action = "D";
		}
		Option::set('catalog', 'default_outfile_action', $default_outfile_action, '');

		$strYandexAgent = '';
		$strYandexAgent = trim($_POST['yandex_agent_file']);
		if (!empty($strYandexAgent))
		{
			$strYandexAgent = Rel2Abs('/', $strYandexAgent);
			if (preg_match(BX_CATALOG_FILENAME_REG, $strYandexAgent) || (!file_exists($_SERVER['DOCUMENT_ROOT'].$strYandexAgent) || !is_file($_SERVER['DOCUMENT_ROOT'].$strYandexAgent)))
			{
				$strWarning .= Loc::getMessage('CAT_PATH_ERR_YANDEX_AGENT').'<br />';
				$strYandexAgent = '';
			}
		}
		Option::set('catalog', 'yandex_agent_file', $strYandexAgent, '');

		$num_catalog_levels = (isset($_POST['num_catalog_levels']) ? (int)$_POST['num_catalog_levels'] : 3);
		if ($num_catalog_levels <= 0)
			$num_catalog_levels = 3;
		Option::set('catalog', 'num_catalog_levels', $num_catalog_levels, '');

		$serialSelectFields = array(
			'allowed_product_fields',
			'allowed_price_fields',
			'allowed_group_fields',
			'allowed_currencies'
		);
		foreach ($serialSelectFields as &$oneSelect)
		{
			$fieldsClear = array();
			$fieldsRaw = (isset($_POST[$oneSelect]) ? $_POST[$oneSelect] : array());
			if (!is_array($fieldsRaw))
			{
				$fieldsRaw = array($fieldsRaw);
			}
			if (!empty($fieldsRaw))
			{
				foreach ($fieldsRaw as &$oneValue)
				{
					$oneValue = trim($oneValue);
					if ('' !== $oneValue)
					{
						$fieldsClear[] = $oneValue;
					}
				}
				unset($oneValue);
			}
			Option::set('catalog', $oneSelect, implode(',', $fieldsClear), '');
		}
		unset($oneSelect);

		$viewedPeriodChange = false;
		$viewedTimeChange = false;
		if (isset($_POST['viewed_period']))
		{
			$viewedPeriod = (int)$_POST['viewed_period'];
			if ($viewedPeriod > 0)
			{
				$oldViewedPeriod = (int)Option::get('catalog', 'viewed_period');
				$viewedPeriodChange = ($viewedPeriod !== $oldViewedPeriod);
				Option::set('catalog', 'viewed_period', $viewedPeriod, '');
			}
		}

		if (isset($_POST['viewed_time']))
		{
			$viewedTime = (int)$_POST['viewed_time'];
			if ($viewedTime > 0)
			{
				$oldViewedTime = (int)Option::get('catalog', 'viewed_time');
				$viewedTimeChange = ($viewedTime !== $oldViewedTime);
				Option::set('catalog', 'viewed_time', $viewedTime, '');
			}
		}

		if ($viewedPeriodChange || $viewedTimeChange)
		{
			CAgent::RemoveAgent('\Bitrix\Catalog\CatalogViewedProductTable::clearAgent();', 'catalog');
			CAgent::AddAgent('\Bitrix\Catalog\CatalogViewedProductTable::clearAgent();', 'catalog', 'N', (int)Option::get('catalog', 'viewed_period') * 24 * 3600);
		}

		if (isset($_POST['viewed_count']))
		{
			$viewedCount = (int)$_POST['viewed_count'];
			if ($viewedCount > 0)
				Option::set('catalog', 'viewed_count', $viewedCount, '');
		}

		if ($USER->IsAdmin() && CBXFeatures::IsFeatureEnabled('SaleRecurring'))
		{
			$arOldAvailContentGroups = array();
			$oldAvailContentGroups = (string)Option::get('catalog', 'avail_content_groups');
			if ($oldAvailContentGroups != '')
				$arOldAvailContentGroups = explode(",", $oldAvailContentGroups);
			if (!empty($arOldAvailContentGroups))
				$arOldAvailContentGroups = array_fill_keys($arOldAvailContentGroups, true);

			$fieldsClear = array();
			if (isset($_POST['AVAIL_CONTENT_GROUPS']) && is_array($_POST['AVAIL_CONTENT_GROUPS']))
			{
				$fieldsClear = $_POST['AVAIL_CONTENT_GROUPS'];
				CatalogClearArray($fieldsClear);
				foreach ($fieldsClear as &$oneValue)
				{
					if (isset($arOldAvailContentGroups[$oneValue]))
						unset($arOldAvailContentGroups[$oneValue]);
				}
				if (isset($oneValue))
					unset($oneValue);

			}
			Option::set('catalog', 'avail_content_groups', implode(',', $fieldsClear), '');
			if (!empty($arOldAvailContentGroups))
			{
				$arOldAvailContentGroups = array_keys($arOldAvailContentGroups);
				foreach ($arOldAvailContentGroups as &$oneValue)
				{
					CCatalogProductGroups::DeleteByGroup($oneValue);
				}
				unset($oneValue);
			}
		}

		$oldSimpleSearch = Option::get('catalog', 'product_form_simple_search');
		$newSimpleSearch = $oldSimpleSearch;
		$checkboxFields = array(
			'save_product_without_price',
			'save_product_with_empty_price_range',
			'show_catalog_tab_with_offers',
			'default_quantity_trace',
			'default_can_buy_zero',
			'default_subscribe',
			'product_form_show_offers_iblock',
			'product_form_simple_search'
		);

		$setNegativeAmount = false;
		foreach ($checkboxFields as &$oneCheckbox)
		{
			if (empty($_POST[$oneCheckbox]) || !is_string($_POST[$oneCheckbox]))
				continue;
			$value = (string)$_POST[$oneCheckbox];
			if ($value !== 'Y' && $value !== 'N')
				continue;
			Option::set('catalog', $oneCheckbox, $value, '');
			if ($oneCheckbox === 'default_can_buy_zero')
				$setNegativeAmount = ($value === 'Y');
			if ($oneCheckbox === 'product_form_simple_search')
				$newSimpleSearch = $value;
		}
		unset($value, $oneCheckbox);

		if ($setNegativeAmount)
		{
			Option::set('catalog', 'allow_negative_amount', 'Y', '');
		}
		else
		{
			if (!empty($_POST['allow_negative_amount']) && is_string($_POST['allow_negative_amount']))
			{
				$value = (string)$_POST['allow_negative_amount'];
				if ($value === 'Y' || $value === 'N')
					Option::set('catalog', 'allow_negative_amount', $value, '');
				unset($value);
			}
		}

		if ($oldSimpleSearch != $newSimpleSearch)
		{
			if ($newSimpleSearch == 'Y')
				UnRegisterModuleDependences('search', 'BeforeIndex', 'catalog', '\Bitrix\Catalog\SearchHandlers', 'onBeforeIndex');
			else
				RegisterModuleDependences('search', 'BeforeIndex', 'catalog', '\Bitrix\Catalog\SearchHandlers', 'onBeforeIndex');
		}
		unset($oldSimpleSearch, $newSimpleSearch);

		$strUseStoreControlBeforeSubmit = (string)Option::get('catalog', 'default_use_store_control');
		$strUseStoreControl = (isset($_POST['use_store_control']) && (string)$_POST['use_store_control'] === 'Y' ? 'Y' : 'N');

		if ($strUseStoreControlBeforeSubmit != $strUseStoreControl)
		{
			if ($strUseStoreControl == 'Y')
			{
				$countStores = (int)CCatalogStore::GetList(array(), array("ACTIVE" => 'Y'), array());
				if ($countStores <= 0)
				{
					$arStoreFields = array("TITLE" => Loc::getMessage("CAT_STORE_NAME"), "ADDRESS" => " ");
					$newStoreId = CCatalogStore::Add($arStoreFields);
					if ($newStoreId)
					{
						CCatalogDocs::synchronizeStockQuantity($newStoreId);
					}
					else
					{
						$strWarning .= Loc::getMessage("CAT_STORE_ACTIVE_ERROR");
						$strUseStoreControl = 'N';
					}
				}
				else
				{
					$strWarning .= Loc::getMessage("CAT_STORE_SYNCHRONIZE_WARNING");
				}
			}
			elseif($strUseStoreControl == 'N')
			{
				$strWarning .= Loc::getMessage("CAT_STORE_DEACTIVATE_NOTICE");
			}
		}

		Option::set('catalog', 'default_use_store_control', $strUseStoreControl, '');

		if ($strUseStoreControl == 'Y')
			$strEnableReservation = 'Y';
		else
			$strEnableReservation = (isset($_POST['enable_reservation']) && (string)$_POST['enable_reservation'] === 'Y' ? 'Y' : 'N');
		Option::set('catalog', 'enable_reservation', $strEnableReservation, '');

		if (!$useSaleDiscountOnly)
		{
			if (CBXFeatures::IsFeatureEnabled('CatDiscountSave'))
			{
				$strDiscSaveApply = '';
				if (isset($_REQUEST['discsave_apply']))
					$strDiscSaveApply = (string)$_REQUEST['discsave_apply'];
				if ($strDiscSaveApply != '' && isset($applyDiscSaveModeList[$strDiscSaveApply]))
				{
					Option::set('catalog', 'discsave_apply', $strDiscSaveApply, '');
				}
			}
			if (!$saleIsInstalled)
			{
				$discountPercent = '';
				if (isset($_REQUEST['get_discount_percent_from_base_price']))
					$discountPercent = (string)$_REQUEST['get_discount_percent_from_base_price'];
				if ($discountPercent == 'Y' || $discountPercent == 'N')
					Option::set('catalog', 'get_discount_percent_from_base_price', $discountPercent, '');
				unset($discountPercent);
			}
/*			$strDiscountVat = (!empty($_REQUEST['discount_vat']) && $_REQUEST['discount_vat'] == 'N' ? 'N' : 'Y');
			Option::set('catalog', 'discount_vat', $strDiscountVat, ''); */
		}

		$bNeedAgent = false;

		$boolFlag = true;
		$arCurrentIBlocks = array();
		$arNewIBlocksList = array();
		$rsIBlocks = CIBlock::GetList(array());
		while ($arOneIBlock = $rsIBlocks->Fetch())
		{
			// Current info
			$arOneIBlock['ID'] = (int)$arOneIBlock['ID'];
			$arIBlockItem = array();
			$arIBlockSitesList = array();
			$rsIBlockSites = CIBlock::GetSite($arOneIBlock['ID']);
			while ($arIBlockSite = $rsIBlockSites->Fetch())
			{
				$arIBlockSitesList[] = htmlspecialcharsbx($arIBlockSite['SITE_ID']);
			}

			$strInfo = '['.$arOneIBlock['IBLOCK_TYPE_ID'].'] '.htmlspecialcharsbx($arOneIBlock['NAME']).' ('.implode(' ',$arIBlockSitesList).')';

			$arIBlockItem = array(
				'INFO' => $strInfo,
				'ID' => $arOneIBlock['ID'],
				'NAME' => $arOneIBlock['NAME'],
				'SITE_ID' => $arIBlockSitesList,
				'IBLOCK_TYPE_ID' => $arOneIBlock['IBLOCK_TYPE_ID'],
				'CATALOG' => 'N',
				'PRODUCT_IBLOCK_ID' => 0,
				'SKU_PROPERTY_ID' => 0,
				'OFFERS_IBLOCK_ID' => 0,
				'OFFERS_PROPERTY_ID' => 0,
			);
			$arCurrentIBlocks[$arOneIBlock['ID']] = $arIBlockItem;
		}
		$arCatalogList = array();
		$catalogIterator = Catalog\CatalogIblockTable::getList(array(
			'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'SUBSCRIPTION', 'YANDEX_EXPORT', 'VAT_ID')
		));
		while ($arCatalog = $catalogIterator->fetch())
		{
			$arCatalog['IBLOCK_ID'] = (int)$arCatalog['IBLOCK_ID'];
			$arCatalog['PRODUCT_IBLOCK_ID'] = (int)$arCatalog['PRODUCT_IBLOCK_ID'];
			$arCatalog['SKU_PROPERTY_ID'] = (int)$arCatalog['SKU_PROPERTY_ID'];
			$arCatalog['VAT_ID'] = (int)$arCatalog['VAT_ID'];

			$arCatalogList[$arCatalog['IBLOCK_ID']] = $arCatalog;

			$arCurrentIBlocks[$arCatalog['IBLOCK_ID']]['CATALOG'] = 'Y';
			$arCurrentIBlocks[$arCatalog['IBLOCK_ID']]['PRODUCT_IBLOCK_ID'] = $arCatalog['PRODUCT_IBLOCK_ID'];
			$arCurrentIBlocks[$arCatalog['IBLOCK_ID']]['SKU_PROPERTY_ID'] = $arCatalog['SKU_PROPERTY_ID'];
			if (0 < $arCatalog['PRODUCT_IBLOCK_ID'])
			{
				$arCurrentIBlocks[$arCatalog['PRODUCT_IBLOCK_ID']]['OFFERS_IBLOCK_ID'] = $arCatalog['IBLOCK_ID'];
				$arCurrentIBlocks[$arCatalog['PRODUCT_IBLOCK_ID']]['OFFERS_PROPERTY_ID'] = $arCatalog['SKU_PROPERTY_ID'];
			}
		}
		unset($arCatalog, $catalogIterator);

		foreach ($arCurrentIBlocks as &$arOneIBlock)
		{
			// From form
			$is_cat = ((${"IS_CATALOG_".$arOneIBlock["ID"]}=="Y") ? "Y" : "N" );
			$is_cont = ((${"IS_CONTENT_".$arOneIBlock["ID"]}!="Y") ? "N" : "Y" );
			$yan_exp = ((${"YANDEX_EXPORT_".$arOneIBlock["ID"]}!="Y") ? "N" : "Y" );
			$cat_vat = (int)${"VAT_ID_".$arOneIBlock["ID"]};

			$offer_name = trim(${"OFFERS_NAME_".$arOneIBlock["ID"]});
			$offer_type = trim(${"OFFERS_TYPE_".$arOneIBlock["ID"]});
			$offer_new_type = '';
			$offer_new_type = trim(${"OFFERS_NEWTYPE_".$arOneIBlock["ID"]});
			$flag_new_type = ('Y' == ${'CREATE_OFFERS_TYPE_'.$arOneIBlock["ID"]} ? 'Y' : 'N');

			$offers_iblock_id = intval(${"OFFERS_IBLOCK_ID_".$arOneIBlock["ID"]});

			$arNewIBlockItem = array(
				'ID' => $arOneIBlock['ID'],
				'CATALOG' => $is_cat,
				'SUBSCRIPTION' => $is_cont,
				'YANDEX_EXPORT' => $yan_exp,
				'VAT_ID' => $cat_vat,
				'OFFERS_IBLOCK_ID' => $offers_iblock_id,
				'OFFERS_NAME' => $offer_name,
				'OFFERS_TYPE' => $offer_type,
				'OFFERS_NEW_TYPE' => $offer_new_type,
				'CREATE_OFFERS_NEW_TYPE' => $flag_new_type,
				'NEED_IS_REQUIRED' => 'N',
				'NEED_UPDATE' => 'N',
				'NEED_LINK' => 'N',
				'OFFERS_PROP' => 0,
			);
			$arNewIBlocksList[$arOneIBlock['ID']] = $arNewIBlockItem;
		}
		if (isset($arOneIBlock))
			unset($arOneIBlock);

		// check for offers is catalog
		foreach ($arCurrentIBlocks as $intIBlockID => $arIBlockInfo)
		{
			if ((0 < $arIBlockInfo['PRODUCT_IBLOCK_ID']) && ('Y' != $arNewIBlocksList[$intIBlockID]['CATALOG']))
				$arNewIBlocksList[$intIBlockID]['CATALOG'] = 'Y';
		}
		// check for double using iblock and selfmade
		$arOffersIBlocks = array();
		foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
		{
			if (0 < $arIBlockInfo['OFFERS_IBLOCK_ID'])
			{
				// double
				if (isset($arOffersIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]))
				{
					$boolFlag = false;
					$strWarning .= Loc::getMessage(
						'CAT_IBLOCK_OFFERS_ERR_TOO_MANY_PRODUCT_IBLOCK',
						array('#OFFER#' => $arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO'])
					).'<br />';
				}
				else
				{
					$arOffersIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']] = true;
				}
				// selfmade
				if ($arIBlockInfo['OFFERS_IBLOCK_ID'] == $intIBlockID)
				{
					$boolFlag = false;
					$strWarning .= Loc::getMessage(
						'CAT_IBLOCK_OFFERS_ERR_SELF_MADE',
						array('#PRODUCT#' => $arCurrentIBlocks[$intIBlockID]['INFO'])
					).'<br />';
				}
			}
		}
		unset($arOffersIBlocks);
		// check for rights
		if ($boolFlag)
		{
			if (!$USER->IsAdmin())
			{
				foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
				{
					if (CATALOG_NEW_OFFERS_IBLOCK_NEED == $arIBlockInfo['OFFERS_IBLOCK_ID'])
					{
						$boolFlag = false;
						$strWarning .= Loc::getMessage(
							'CAT_IBLOCK_OFFERS_ERR_CANNOT_CREATE_IBLOCK',
							array('#PRODUCT#' => $arCurrentIBlocks[$intIBlockID]['INFO'])
						).'<br />';
					}
				}
			}
		}
		// check for offers next offers
		if ($boolFlag)
		{
			foreach ($arCurrentIBlocks as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['PRODUCT_IBLOCK_ID'] && 0 != $arNewIBlocksList[$intIBlockID]['OFFERS_IBLOCK_ID'])
				{
					$boolFlag = false;
					$strWarning .= Loc::getMessage(
						'CAT_IBLOCK_OFFERS_ERR_PRODUCT_AND_OFFERS',
						array('#PRODUCT#' => $arIBlockInfo['INFO'])
					).'<br />';
				}
			}
		}
		// check for product as offer
		if ($boolFlag)
		{
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['OFFERS_IBLOCK_ID'] && 0 < $arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['OFFERS_IBLOCK_ID'])
				{
					$boolFlag = false;
					$strWarning .= Loc::getMessage(
						'CAT_IBLOCK_OFFERS_ERR_PRODUCT_AND_OFFERS',
						array('#PRODUCT#' => $arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO'])
					).'<br />';
				}
			}
		}
		if ($boolFlag)
		{
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['OFFERS_IBLOCK_ID'] && 0 < $arNewIBlocksList[$arIBlockInfo['OFFERS_IBLOCK_ID']]['OFFERS_IBLOCK_ID'])
				{
					$boolFlag = false;
					$strWarning .= Loc::getMessage(
						'CAT_IBLOCK_OFFERS_ERR_PRODUCT_AND_OFFERS',
						array('#PRODUCT#' => $arNewIBlocksList[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO'])
					).'<br />';
				}
			}
		}
		if ($boolFlag)
		{
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['OFFERS_IBLOCK_ID'] && CATALOG_NEW_OFFERS_IBLOCK_NEED == $arNewIBlocksList[$arIBlockInfo['OFFERS_IBLOCK_ID']]['OFFERS_IBLOCK_ID'])
				{
					$boolFlag = false;
					$strWarning .= Loc::getMessage(
						'CAT_IBLOCK_OFFERS_ERR_PRODUCT_AND_OFFERS',
						array('#PRODUCT#' => $arNewIBlocksList[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO'])
					).'<br />';
				}
			}
		}

		// check name and new iblock_type
		if ($boolFlag)
		{
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (CATALOG_NEW_OFFERS_IBLOCK_NEED == $arIBlockInfo['OFFERS_IBLOCK_ID'])
				{
					if ('' == trim($arIBlockInfo['OFFERS_NAME']))
					{
						$arNewIBlocksList[$intIBlockID]['OFFERS_NAME'] = Loc::getMessage(
							'CAT_IBLOCK_OFFERS_NAME_TEPLATE',
							array('#PRODUCT#' => $arCurrentIBlocks[$intIBlockID]['NAME'])
						);
					}
					if ('Y' == $arIBlockInfo['CREATE_OFFERS_NEW_TYPE'] && '' == trim($arIBlockInfo['OFFERS_NEW_TYPE']))
					{
						$arNewIBlocksList[$intIBlockID]['CREATE_OFFERS_NEW_TYPE'] = 'N';
						$arNewIBlocksList[$intIBlockID]['OFFERS_TYPE'] = $arCurrentIBlocks[$intIBlockID]['IBLOCK_TYPE_ID'];
					}
					if ('N' == $arIBlockInfo['CREATE_OFFERS_NEW_TYPE'] && '' == trim($arIBlockInfo['OFFERS_TYPE']))
					{
						$arNewIBlocksList[$intIBlockID]['CREATE_OFFERS_NEW_TYPE'] = 'N';
						$arNewIBlocksList[$intIBlockID]['OFFERS_TYPE'] = $arCurrentIBlocks[$intIBlockID]['IBLOCK_TYPE_ID'];
					}
				}
			}
		}
		// check for sites
		if ($boolFlag)
		{
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['OFFERS_IBLOCK_ID'])
				{
					$arDiffParent = array();
					$arDiffParent = array_diff($arCurrentIBlocks[$intIBlockID]['SITE_ID'],$arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['SITE_ID']);
					$arDiffOffer = array();
					$arDiffOffer = array_diff($arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['SITE_ID'],$arCurrentIBlocks[$intIBlockID]['SITE_ID']);
					if (!empty($arDiffParent) || !empty($arDiffOffer))
					{
						$boolFlag = false;
						$strWarning .= Loc::getMessage(
							'CAT_IBLOCK_OFFERS_ERR_SITELIST_DEFFERENT',
							array(
								'#PRODUCT#' => $arCurrentIBlocks[$intIBlockID]['INFO'],
								'#OFFERS#' => $arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO']
							)
						).'<br />';
					}
				}
			}
		}
		// check properties
		if ($boolFlag)
		{
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['OFFERS_IBLOCK_ID'])
				{
					// search properties
					$intCountProp = 0;
					$arLastProp = false;
					$rsProps = CIBlockProperty::GetList(array(),array('IBLOCK_ID' => $arIBlockInfo['OFFERS_IBLOCK_ID'],'PROPERTY_TYPE' => 'E','LINK_IBLOCK_ID' => $intIBlockID,'ACTIVE' => 'Y','USER_TYPE' => 'SKU'));
					if ($arProp = $rsProps->Fetch())
					{
						$intCountProp++;
						$arLastProp = $arProp;
						while ($arProp = $rsProps->Fetch())
						{
							if (false !== $arProp)
							{
								$arLastProp = $arProp;
								$intCountProp++;
							}
						}
					}
					if (1 < $intCountProp)
					{
						// too many links for catalog
						$boolFlag = false;
						$strWarning .= Loc::getMessage(
							'CAT_IBLOCK_OFFERS_ERR_TOO_MANY_LINKS',
							array(
								'#OFFER#' => $arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO'],
								'#PRODUCT#' => $arCurrentIBlocks[$intIBlockID]['INFO']
							)
						).'<br />';
					}
					elseif (1 == $intCountProp)
					{
						if ('Y' == $arLastProp['MULTIPLE'])
						{
							// link must single property
							$boolFlag = false;
							$strWarning .= Loc::getMessage(
								'CAT_IBLOCK_OFFERS_ERR_LINKS_MULTIPLE',
								array(
									'#OFFER#' => $arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO'],
									'#PRODUCT#' => $arCurrentIBlocks[$intIBlockID]['INFO']
								)
							).'<br />';
						}
						elseif (('SKU' != $arLastProp['USER_TYPE']) || ('CML2_LINK' != $arLastProp['XML_ID']))
						{
							// link must is updated
							$arNewIBlocksList[$intIBlockID]['NEED_UPDATE'] = 'Y';
							$arNewIBlocksList[$intIBlockID]['OFFERS_PROP'] = $arLastProp['ID'];
						}
						else
						{
							$arNewIBlocksList[$intIBlockID]['OFFERS_PROP'] = $arLastProp['ID'];
						}
					}
					elseif (0 == $intCountProp)
					{
						// create offers iblock
						$arNewIBlocksList[$intIBlockID]['NEED_IS_REQUIRED'] = 'N';
						$arNewIBlocksList[$intIBlockID]['NEED_UPDATE'] = 'Y';
						$arNewIBlocksList[$intIBlockID]['NEED_LINK'] = 'Y';
					}
				}
			}
		}
		// create iblock
		$arNewOffers = array();
		if ($boolFlag)
		{
			$DB->StartTransaction();
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (CATALOG_NEW_OFFERS_IBLOCK_NEED == $arIBlockInfo['OFFERS_IBLOCK_ID'])
				{
					// need new offers
					$arResultNewCatalogItem = array();
					if ('Y' == $arIBlockInfo['CREATE_OFFERS_NEW_TYPE'])
					{
						$rsIBlockTypes = CIBlockType::GetByID($arIBlockInfo['OFFERS_NEW_TYPE']);
						if ($arIBlockType = $rsIBlockTypes->Fetch())
						{
							$arIBlockInfo['OFFERS_TYPE'] = $arIBlockInfo['OFFERS_NEW_TYPE'];
						}
						else
						{
							$arFields = array(
								'ID' => $arIBlockInfo['OFFERS_NEW_TYPE'],
								'SECTIONS' => 'N',
								'IN_RSS' => 'N',
								'SORT' => 500,
							);

							$languageIterator = Main\Localization\LanguageTable::getList(array(
								'select' => array('ID'),
								'filter' => array('=ACTIVE' => 'Y'),
								'order' => array('SORT' => 'ASC')
							));
							while ($language = $languageIterator->fetch())
							{
								$arFields['LANG'][$language['ID']]['NAME'] = $arIBlockInfo['OFFERS_NEW_TYPE'];
							}
							unset($language, $languageIterator);

							$obIBlockType = new CIBlockType();
							$mxOffersType = $obIBlockType->Add($arFields);
							if (!$mxOffersType)
							{
								$boolFlag = false;
								$strWarning .= Loc::getMessage(
									'CAT_IBLOCK_OFFERS_ERR_NEW_IBLOCK_TYPE_NOT_ADD',
									array(
										'#PRODUCT#' => $arCurrentIBlocks[$intIBlockID]['INFO'],
										'#ERROR#' => $obIBlockType->LAST_ERROR
									)
								).'<br />';
							}
							else
							{
								$arIBlockInfo['OFFERS_TYPE'] = $arIBlockInfo['OFFERS_NEW_TYPE'];
							}
						}
					}
					if ($boolFlag)
					{
						$arParentRights = CIBlock::GetGroupPermissions($intIBlockID);
						foreach ($arParentRights as $keyRight => $valueRight)
						{
							if ('U' == $valueRight)
							{
								$arParentRights[$keyRight] = 'W';
							}
						}
						$arFields = array(
							'SITE_ID' => $arCurrentIBlocks[$intIBlockID]['SITE_ID'],
							'IBLOCK_TYPE_ID' => $arIBlockInfo['OFFERS_TYPE'],
							'NAME' => $arIBlockInfo['OFFERS_NAME'],
							'ACTIVE' => 'Y',
							'GROUP_ID' => $arParentRights,
							'WORKFLOW' => 'N',
							'BIZPROC' => 'N',
							"LIST_PAGE_URL" => '',
							"SECTION_PAGE_URL" => '',
							"DETAIL_PAGE_URL" => '#PRODUCT_URL#',
							"INDEX_SECTION" => "N",
						);
						$obIBlock = new CIBlock();
						$mxOffersID = $obIBlock->Add($arFields);
						if ($mxOffersID === false)
						{
							$boolFlag = false;
							$strWarning .= Loc::getMessage(
								'CAT_IBLOCK_OFFERS_ERR_IBLOCK_ADD',
								array(
									'#PRODUCT#' => $arCurrentIBlocks[$intIBlockID]['INFO'],
									'#ERR#' => $obIBlock->LAST_ERROR
								)
							).'<br />';
						}
						else
						{
							$arResultNewCatalogItem = array(
								'INFO' => '['.$arFields['IBLOCK_TYPE_ID'].'] '.htmlspecialcharsbx($arFields['NAME']).' ('.implode(' ',$arCurrentIBlocks[$intIBlockID]['SITE_ID']).')',
								'SITE_ID' => $arCurrentIBlocks[$intIBlockID]['SITE_ID'],
								'IBLOCK_TYPE_ID' => $arFields['IBLOCK_TYPE_ID'],
								'ID' => $mxOffersID,
								'NAME' => $arFields['NAME'],
								'CATALOG' => 'Y',
								'IS_CONTENT' => 'N',
								'YANDEX_EXPORT' => 'N',
								'VAT_ID' => 0,
								'PRODUCT_IBLOCK_ID' => $intIBlockID,
								'SKU_PROPERTY_ID' => 0,
								'NEED_IS_REQUIRED' => 'N',
								'NEED_UPDATE' => 'Y',
								'LINK_PROP' => false,
								'NEED_LINK' => 'Y',
							);
							$arFields = array(
								'IBLOCK_ID' => $mxOffersID,
								'NAME' => Loc::getMessage('CAT_IBLOCK_OFFERS_TITLE_LINK_NAME'),
								'ACTIVE' => 'Y',
								'PROPERTY_TYPE' => 'E',
								'MULTIPLE' => 'N',
								'LINK_IBLOCK_ID' => $intIBlockID,
								'CODE' => 'CML2_LINK',
								'XML_ID' => 'CML2_LINK',
								"FILTRABLE" => "Y",
								'USER_TYPE' => 'SKU',
							);
							$obProp = new CIBlockProperty();
							$mxPropID = $obProp->Add($arFields);
							if (!$mxPropID)
							{
								$boolFlag = false;
								$strWarning .= Loc::getMessage(
									'CAT_IBLOCK_OFFERS_ERR_CANNOT_CREATE_LINK',
									array(
										'#OFFERS#' => $arResultNewCatalogItem['INFO'],
										'#ERR#' => $obProp->LAST_ERROR
									)
								).'<br />';
							}
							else
							{
								$arResultNewCatalogItem['SKU_PROPERTY_ID'] = $mxPropID;
								$arResultNewCatalogItem['NEED_IS_REQUIRED'] = 'N';
								$arResultNewCatalogItem['NEED_UPDATE'] = 'N';
								$arResultNewCatalogItem['NEED_LINK'] = 'N';
							}
						}
					}
					if ($boolFlag)
					{
						$arNewOffers[$mxOffersID] = $arResultNewCatalogItem;
					}
					else
					{
						break;
					}
				}
			}
			if (!$boolFlag)
			{
				$DB->Rollback();
			}
			else
			{
				$DB->Commit();
			}
		}
		// create properties
		if ($boolFlag)
		{
			$DB->StartTransaction();
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['OFFERS_IBLOCK_ID'])
				{
					if ('Y' == $arIBlockInfo['NEED_LINK'])
					{
						$arFields = array(
							'IBLOCK_ID' => $arIBlockInfo['OFFERS_IBLOCK_ID'],
							'NAME' => Loc::getMessage('CAT_IBLOCK_OFFERS_TITLE_LINK_NAME'),
							'ACTIVE' => 'Y',
							'PROPERTY_TYPE' => 'E',
							'MULTIPLE' => 'N',
							'LINK_IBLOCK_ID' => $intIBlockID,
							'CODE' => 'CML2_LINK',
							'XML_ID' => 'CML2_LINK',
							"FILTRABLE" => "Y",
							'USER_TYPE' => 'SKU',
						);
						$obProp = new CIBlockProperty();
						$mxPropID = $obProp->Add($arFields);
						if (!$mxPropID)
						{
							$boolFlag = false;
							$strWarning .= Loc::getMessage(
								'CAT_IBLOCK_OFFERS_ERR_CANNOT_CREATE_LINK',
								array(
									'#OFFERS#' => $arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO'],
									'#ERR#' => $obProp->LAST_ERROR
								)
							).'<br />';
						}
						else
						{
							$arNewIBlocksList[$intIBlockID]['OFFERS_PROP'] = $mxPropID;
							$arNewIBlocksList[$intIBlockID]['NEED_IS_REQUIRED'] = 'N';
							$arNewIBlocksList[$intIBlockID]['NEED_UPDATE'] = 'N';
							$arNewIBlocksList[$intIBlockID]['NEED_LINK'] = 'N';
						}
					}
					elseif (0 < $arIBlockInfo['OFFERS_PROP'])
					{
						if ('Y' == $arIBlockInfo['NEED_UPDATE'])
						{
							$arPropFields = array(
								'USER_TYPE' => 'SKU',
								'XML_ID' => 'CML2_LINK',
							);
							$obProp = new CIBlockProperty();
							$mxPropID = $obProp->Update($arIBlockInfo['OFFERS_PROP'],$arPropFields);
							if (!$mxPropID)
							{
								$boolFlag = false;
								$strWarning .= Loc::getMessage(
									'CAT_IBLOCK_OFFERS_ERR_MODIFY_PROP_IS_REQ',
									array(
										'#OFFERS#' => $arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['INFO'],
										'#ERR#' => $obProp->LAST_ERROR
									)
								).'<br />';
								break;
							}
						}
					}
				}
			}
			if (!$boolFlag)
			{
				$DB->Rollback();
			}
			else
			{
				$DB->Commit();
			}
		}
		// reverse array
		if ($boolFlag)
		{
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				$arCurrentIBlocks[$intIBlockID]['CATALOG'] = $arIBlockInfo['CATALOG'];
				$arCurrentIBlocks[$intIBlockID]['SUBSCRIPTION'] = $arIBlockInfo['SUBSCRIPTION'];
				$arCurrentIBlocks[$intIBlockID]['YANDEX_EXPORT'] = $arIBlockInfo['YANDEX_EXPORT'];
				$arCurrentIBlocks[$intIBlockID]['VAT_ID'] = $arIBlockInfo['VAT_ID'];
			}
			foreach ($arNewIBlocksList as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['OFFERS_IBLOCK_ID'])
				{
					$arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['CATALOG'] = 'Y';
					$arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['PRODUCT_IBLOCK_ID'] = $intIBlockID;
					$arCurrentIBlocks[$arIBlockInfo['OFFERS_IBLOCK_ID']]['SKU_PROPERTY_ID'] = $arIBlockInfo['OFFERS_PROP'];
				}
			}
		}
		// check old offers
		if ($boolFlag)
		{
			foreach ($arCurrentIBlocks as $intIBlockID => $arIBlockInfo)
			{
				if (0 < $arIBlockInfo['PRODUCT_IBLOCK_ID'])
				{
					if ($intIBlockID != $arNewIBlocksList[$arIBlockInfo['PRODUCT_IBLOCK_ID']]['OFFERS_IBLOCK_ID'])
					{
						$arCurrentIBlocks[$intIBlockID]['UNLINK'] = 'Y';
					}
				}
			}
		}
		// go exist iblock
		$boolCatalogUpdate = false;
		if ($boolFlag)
		{
			$DB->StartTransaction();
			$obCatalog = new CCatalog();
			foreach ($arCurrentIBlocks as $intIBlockID => $arIBlockInfo)
			{
				$boolAttr = true;
				if (isset($arIBlockInfo['UNLINK']) && 'Y' == $arIBlockInfo['UNLINK'])
				{
					$boolFlag = $obCatalog->UnLinkSKUIBlock($arIBlockInfo['PRODUCT_IBLOCK_ID']);
					if ($boolFlag)
					{
						$arIBlockInfo['PRODUCT_IBLOCK_ID'] = 0;
						$arIBlockInfo['SKU_PROPERTY_ID'] = 0;
						$boolCatalogUpdate = true;
					}
					else
					{
						$boolFlag = false;
						$ex = $APPLICATION->GetException();
						$strError = $ex->GetString();
						$strWarning .= Loc::getMessage(
							'CAT_IBLOCK_OFFERS_ERR_UNLINK_SKU',
							array(
								'#PRODUCT#' => $arIBlockInfo['INFO'],
								'#ERROR#' => $strError
							)
						).'<br />';
					}
				}
				if ($boolFlag)
				{
					$boolExists = isset($arCatalogList[$intIBlockID]);
					$arCurValues = ($boolExists ? $arCatalogList[$intIBlockID] : array());

					if ($boolExists && ('Y' == $arIBlockInfo['CATALOG'] || 'Y' == $arIBlockInfo['SUBSCRIPTION'] || 0 < $arIBlockInfo['PRODUCT_IBLOCK_ID']))
					{
						$boolAttr = $obCatalog->Update(
							$intIBlockID,
							array(
								'IBLOCK_ID' => $arIBlockInfo['ID'],
								'YANDEX_EXPORT' => $arIBlockInfo['YANDEX_EXPORT'],
								'SUBSCRIPTION' => $arIBlockInfo['SUBSCRIPTION'],
								'VAT_ID' => $arIBlockInfo['VAT_ID'],
								'PRODUCT_IBLOCK_ID' => $arIBlockInfo['PRODUCT_IBLOCK_ID'],
								'SKU_PROPERTY_ID' => $arIBlockInfo['SKU_PROPERTY_ID']
							)
						);
						if (!$boolAttr)
						{
							$ex = $APPLICATION->GetException();
							$strError = $ex->GetString();
							$strWarning .= Loc::getMessage(
								'CAT_IBLOCK_OFFERS_ERR_CAT_UPDATE',
								array(
									'#PRODUCT#' => $arIBlockInfo['INFO'],
									'#ERROR#' => $strError
								)
							).'<br />';
							$boolFlag = false;
						}
						else
						{
							if (
								$arCurValues['SUBSCRIPTION'] != $arIBlockInfo['SUBSCRIPTION']
								|| $arCurValues['PRODUCT_IBLOCK_ID'] != $arIBlockInfo['PRODUCT_IBLOCK_ID']
								|| $arCurValues['YANDEX_EXPORT'] != $arIBlockInfo['YANDEX_EXPORT']
								|| $arCurValues['VAT_ID'] != $arIBlockInfo['VAT_ID']
							)
							{
								$boolCatalogUpdate = true;
							}
							if ($arIBlockInfo['YANDEX_EXPORT']=="Y")
								$bNeedAgent = true;
						}
					}
					elseif ($boolExists && $arIBlockInfo['CATALOG']!="Y" && $arIBlockInfo['SUBSCRIPTION']!="Y" && 0 == $arIBlockInfo['PRODUCT_IBLOCK_ID'])
					{
						if (!CCatalog::Delete($arIBlockInfo['ID']))
						{
							$boolFlag = false;
							$strWarning .= Loc::getMessage("CAT_DEL_CATALOG1").' '.$arIBlockInfo['INFO'].' '.Loc::getMessage("CAT_DEL_CATALOG2").".<br />";
						}
						else
						{
							$boolCatalogUpdate = true;
						}
					}
					elseif ($arIBlockInfo['CATALOG']=="Y" || $arIBlockInfo['SUBSCRIPTION']=="Y" || 0 < $arIBlockInfo['PRODUCT_IBLOCK_ID'])
					{
						$boolAttr = $obCatalog->Add(array(
							'IBLOCK_ID' => $arIBlockInfo['ID'],
							'YANDEX_EXPORT' => $arIBlockInfo['YANDEX_EXPORT'],
							'SUBSCRIPTION' => $arIBlockInfo['SUBSCRIPTION'],
							'VAT_ID' => $arIBlockInfo['VAT_ID'],
							'PRODUCT_IBLOCK_ID' => $arIBlockInfo['PRODUCT_IBLOCK_ID'],
							'SKU_PROPERTY_ID' => $arIBlockInfo['SKU_PROPERTY_ID']
						));
						if (!$boolAttr)
						{
							$ex = $APPLICATION->GetException();
							$strError = $ex->GetString();
							$strWarning .= str_replace(
								array('#PRODUCT#', '#ERROR#'),
								array($arIBlockInfo['INFO'], $strError),
								Loc::getMessage('CAT_IBLOCK_OFFERS_ERR_CAT_ADD')
							).'<br />';
							$strWarning .= Loc::getMessage(
								'CAT_IBLOCK_OFFERS_ERR_CAT_ADD',
								array(
									'#PRODUCT#' => $arIBlockInfo['INFO'],
									'#ERROR#' => $strError
									)
							).'<br />';
							$boolFlag = false;
						}
						else
						{
							if ($arIBlockInfo['YANDEX_EXPORT']=="Y") $bNeedAgent = true;
							$boolCatalogUpdate = true;
						}
					}
				}
				if (!$boolFlag)
					break;
			}
			if (!$boolFlag)
			{
				$DB->Rollback();
			}
			else
			{
				$DB->Commit();
			}
		}
		if ($boolFlag)
		{
			if (!empty($arNewOffers))
			{
				$DB->StartTransaction();
				foreach ($arNewOffers as $IntIBlockID => $arIBlockInfo)
				{
					$boolAttr = $obCatalog->Add(array('IBLOCK_ID' => $arIBlockInfo['ID'], "YANDEX_EXPORT" => $arIBlockInfo['YANDEX_EXPORT'], "SUBSCRIPTION" => $arIBlockInfo['SUBSCRIPTION'], "VAT_ID" => $arIBlockInfo['VAT_ID'], "PRODUCT_IBLOCK_ID" => $arIBlockInfo['PRODUCT_IBLOCK_ID'], 'SKU_PROPERTY_ID' => $arIBlockInfo['SKU_PROPERTY_ID']));
					if (!$boolAttr)
					{
						$ex = $APPLICATION->GetException();
						$strError = $ex->GetString();
						$strWarning .= Loc::getMessage(
							'CAT_IBLOCK_OFFERS_ERR_CAT_ADD',
							array(
								'#PRODUCT#' => $arIBlockInfo['INFO'],
								'#ERROR#' => $strError
							)
						).'<br />';
						$boolFlag = false;
						break;
					}
					else
					{
						if ($arIBlockInfo['YANDEX_EXPORT']=="Y") $bNeedAgent = true;
						$boolCatalogUpdate = true;
					}
				}
				if (!$boolFlag)
				{
					$DB->Rollback();
				}
				else
				{
					$DB->Commit();
				}
			}
		}

		if ($boolFlag && $boolCatalogUpdate)
		{
			$strOK .= Loc::getMessage('CAT_IBLOCK_CATALOG_SUCCESSFULLY_UPDATE').'<br />';
		}

		CAgent::RemoveAgent('CCatalog::PreGenerateXML("yandex");', 'catalog');
		if ($bNeedAgent)
		{
			CAgent::AddAgent('CCatalog::PreGenerateXML("yandex");', 'catalog', 'N', (int)Option::get('catalog', 'yandex_xml_period')*3600);
		}
	}

	if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['agent_start']) && !$bReadOnly && check_bitrix_sessid())
	{
		CAgent::RemoveAgent('CCatalog::PreGenerateXML("yandex");', 'catalog');
		$intCount = (int)CCatalog::GetList(array(), array('YANDEX_EXPORT' => 'Y'), array());
		if ($intCount > 0)
		{
			CAgent::AddAgent('CCatalog::PreGenerateXML("yandex");', 'catalog', 'N', (int)Option::get('catalog', 'yandex_xml_period') * 3600);
			$strOK .= Loc::getMessage('CAT_AGENT_ADD_SUCCESS').'. ';
		}
		else
		{
			$strWarning .= Loc::getMessage('CAT_AGENT_ADD_NO_EXPORT').'. ';
		}
	}

	if (!empty($strWarning))
		CAdminMessage::ShowMessage($strWarning);

	if (!empty($strOK))
		CAdminMessage::ShowNote($strOK);

	$aTabs = array(
		array("DIV" => "edit5", "TAB" => Loc::getMessage("CO_TAB_5"), "ICON" => "catalog_settings", "TITLE" => Loc::getMessage("CO_TAB_5_TITLE")),
		array("DIV" => "edit1", "TAB" => Loc::getMessage("CO_TAB_1"), "ICON" => "catalog_settings", "TITLE" => Loc::getMessage("CO_TAB_1_TITLE")),
		array("DIV" => "edit2", "TAB" => Loc::getMessage("CO_TAB_2"), "ICON" => "catalog_settings", "TITLE" => Loc::getMessage("CO_TAB_2_TITLE"))
	);

	if ($USER->IsAdmin())
	{
		if (CBXFeatures::IsFeatureEnabled('SaleRecurring'))
			$aTabs[] = array("DIV" => "edit3", "TAB" => Loc::getMessage("CO_TAB_3"), "ICON" => "catalog_settings", "TITLE" => Loc::getMessage("CO_SALE_GROUPS"));
		$aTabs[] = array("DIV" => "edit4", "TAB" => Loc::getMessage("CO_TAB_RIGHTS"), "ICON" => "catalog_settings", "TITLE" => Loc::getMessage("CO_TAB_RIGHTS_TITLE"));
	}

	$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);

	$currentSettings = array();
	$currentSettings['discsave_apply'] = Option::get('catalog', 'discsave_apply');
	$currentSettings['get_discount_percent_from_base_price'] = Option::get(($saleIsInstalled ? 'sale' : 'catalog'), 'get_discount_percent_from_base_price');
	$currentSettings['save_product_with_empty_price_range'] = Option::get('catalog', 'save_product_with_empty_price_range');

	$strShowCatalogTab = Option::get('catalog', 'show_catalog_tab_with_offers');
	$strSaveProductWithoutPrice = Option::get('catalog', 'save_product_without_price');

	$strQuantityTrace = Option::get('catalog', 'default_quantity_trace');
	$strAllowCanBuyZero = Option::get('catalog', 'default_can_buy_zero');
	$strAllowNegativeAmount = Option::get('catalog', 'allow_negative_amount');
	$strSubscribe = Option::get('catalog', 'default_subscribe');

	$strEnableReservation = Option::get('catalog', 'enable_reservation');
	$strUseStoreControl = Option::get('catalog', 'default_use_store_control');

	$strShowOffersIBlock = Option::get('catalog', 'product_form_show_offers_iblock');
	$strSimpleSearch = Option::get('catalog', 'product_form_simple_search');

	$tabControl->Begin();
	?>
<script type="text/javascript">
function onClickCanBuyZero(el)
{
	var obNegativeAmount = BX('allow_negative_amount_y'),
		oldValue = '';

	if (!obNegativeAmount)
	{
		return;
	}
	if (el.checked)
	{
		obNegativeAmount.checked = true;
	}
	else
	{
		if (obNegativeAmount.hasAttribute('data-oldvalue'))
		{
			oldValue = obNegativeAmount.getAttribute('data-oldvalue');
			obNegativeAmount.checked = (oldValue === 'Y');
		}
	}
	obNegativeAmount.disabled = el.checked;
}

function showReservation(show)
{
	var obRowReservationPeriod = BX('tr_reservation_period'),
		obReservationType = BX('td_reservation_type'),
		titleQuantityDecrease = '<? echo CUtil::JSEscape(Loc::getMessage("CAT_PRODUCT_QUANTITY_DECREASE")); ?>',
		titleProductReserved = '<? echo CUtil::JSEscape(Loc::getMessage("CAT_PRODUCT_RESERVED")); ?>';

	show = !!show;
	if (!!obRowReservationPeriod)
		BX.style(obRowReservationPeriod, 'display', (show ? 'table-row' : 'none'));
	obRowReservationPeriod = null;
	if (!!obReservationType)
		obReservationType.innerHTML = (show ? titleProductReserved : titleQuantityDecrease);
	obReservationType = null;
}

function onClickReservation(el)
{
	showReservation(el.checked);
}

function onClickStoreControl(el)
{
	var obEnableReservation = BX('enable_reservation_y'),
		oldValue = '';

	if (!obEnableReservation)
	{
		return;
	}

	if (el.checked)
	{
		obEnableReservation.checked = true;
	}
	else
	{
		if (obEnableReservation.hasAttribute('data-oldvalue'))
		{
			oldValue = obEnableReservation.getAttribute('data-oldvalue');
			obEnableReservation.checked = (oldValue === 'Y');
		}
	}
	showReservation(obEnableReservation.checked);
	obEnableReservation.disabled = el.checked;
}

function RestoreDefaults()
{
	if (confirm('<?echo CUtil::JSEscape(Loc::getMessage("CAT_OPTIONS_BTN_HINT_RESTORE_DEFAULT_WARNING"));?>'))
		window.location = "<? echo $APPLICATION->GetCurPage(); ?>?RestoreDefaults=Y&lang=<? echo LANGUAGE_ID; ?>&mid=<? echo urlencode($mid); ?>&<? echo bitrix_sessid_get(); ?>";
}
</script>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?=LANGUAGE_ID; ?>&mid=<?=htmlspecialcharsbx($mid); ?>&mid_menu=1" name="ara">
<?echo bitrix_sessid_post()?><?
	$tabControl->BeginNextTab();
	?>
<tr class="heading">
	<td colspan="2"><? echo Loc::getMessage("CAT_PRODUCT_CARD") ?></td>
</tr>
<tr>
	<td width="40%"><label for="save_product_without_price_y"><? echo Loc::getMessage("CAT_SAVE_PRODUCTS_WITHOUT_PRICE"); ?></label></td>
	<td width="60%">
		<input type="hidden" name="save_product_without_price" id="save_product_without_price_n" value="N">
		<input type="checkbox" name="save_product_without_price" id="save_product_without_price_y" value="Y"<?if ('Y' == $strSaveProductWithoutPrice) echo " checked";?>>
	</td>
</tr>
<tr>
	<td width="40%"><label for="save_product_with_empty_price_range_y"><? echo Loc::getMessage("CAT_SAVE_PRODUCT_WITH_EMPTY_PRICE_RANGE"); ?></label></td>
	<td width="60%">
		<input type="hidden" name="save_product_with_empty_price_range" id="save_product_with_empty_price_range_n" value="N">
		<input type="checkbox" name="save_product_with_empty_price_range" id="save_product_with_empty_price_range_y" value="Y"<?if ($currentSettings['save_product_with_empty_price_range'] == 'Y') echo ' checked';?>>
	</td>
</tr>
<tr>
	<td width="40%"><label for="show_catalog_tab_with_offers"><? echo Loc::getMessage("CAT_SHOW_CATALOG_TAB"); ?></label></td>
	<td width="60%">
		<input type="hidden" name="show_catalog_tab_with_offers" id="show_catalog_tab_with_offers_n" value="N">
		<input type="checkbox" name="show_catalog_tab_with_offers" id="show_catalog_tab_with_offers_y" value="Y"<?if ('Y' == $strShowCatalogTab) echo " checked";?>>
	</td>
</tr>
<tr class="heading">
	<td colspan="2"><? echo Loc::getMessage('CAT_PRODUCT_CARD_DEFAULT_VALUES'); ?></td>
</tr>
<tr>
	<td width="40%"><label for="default_quantity_trace_y"><? echo Loc::getMessage("CAT_ENABLE_QUANTITY_TRACE"); ?></label></td>
	<td width="60%">
		<input type="hidden" name="default_quantity_trace" id="default_quantity_trace_n" value="N">
		<input type="checkbox" name="default_quantity_trace" id="default_quantity_trace_y" value="Y"<? echo ($strQuantityTrace === 'Y' ? ' checked' : ''); ?>>
	</td>
</tr>
<tr>
	<td width="40%"><label for="default_can_buy_zero_y"><? echo Loc::getMessage("CAT_ALLOW_CAN_BUY_ZERO"); ?></label></td>
	<td width="60%">
		<input type="hidden" name="default_can_buy_zero" id="default_can_buy_zero_n" value="N">
		<input type="checkbox" name="default_can_buy_zero" id="default_can_buy_zero_y" value="Y"<? echo ($strAllowCanBuyZero === 'Y' ? ' checked' : ''); ?> onclick="onClickCanBuyZero(this);">
	</td>
</tr>
<tr>
	<td width="40%"><label for="allow_negative_amount_y"><? echo Loc::getMessage("CAT_ALLOW_NEGATIVE_AMOUNT"); ?></label></td>
	<td width="60%">
		<input type="hidden" name="allow_negative_amount" id="allow_negative_amount_n" value="N">
		<input type="checkbox" name="allow_negative_amount" id="allow_negative_amount_y" value="Y"<?if($strAllowCanBuyZero == "Y")echo " disabled";?><?if($strAllowNegativeAmount == "Y")echo " checked";?> data-oldvalue="<? echo $strAllowNegativeAmount; ?>">
	</td>
</tr>
<tr>
	<td width="40%"><label for="default_subscribe"><? echo Loc::getMessage("CAT_PRODUCT_SUBSCRIBE"); ?></label></td>
	<td width="60%">
		<input type="hidden" name="default_subscribe" id="default_subscribe_n" value="N">
		<input type="checkbox" name="default_subscribe" id="default_subscribe_y" value="Y"<?if ('Y' == $strSubscribe) echo " checked";?>>
	</td>
</tr>
<tr class="heading">
	<td colspan="2" valign="top" align="center"><? echo Loc::getMessage("CAT_STORE") ?></td>
</tr>
<tr id='cat_store_tr'>
	<td width="40%"><label for="use_store_control_y"><? echo Loc::getMessage("CAT_USE_STORE_CONTROL"); ?></label></td>
	<td width="60%">
		<input type="hidden" name="use_store_control" id="use_store_control_n" value="N">
		<input type="checkbox" onclick="onClickStoreControl(this);" name="use_store_control" id="use_store_control_y" value="Y"<?if($strUseStoreControl == "Y")echo " checked";?>>
	</td>
</tr>
<tr>
	<td width="40%">
		<span id="hint_reservation"></span>&nbsp;<label for="enable_reservation"><? echo Loc::getMessage("CAT_ENABLE_RESERVATION"); ?></label></td>
	<td width="60%">
		<input type="hidden" name="enable_reservation" id="enable_reservation_n" value="N">
		<input type="checkbox" onclick="onClickReservation(this);" name="enable_reservation" id="enable_reservation_y" value="Y" data-oldvalue="<? echo $strEnableReservation; ?>" <?if($strEnableReservation == "Y" || $strUseStoreControl == "Y")echo " checked";?> <?if($strUseStoreControl == "Y")echo " disabled";?>>
	</td>
</tr>

<?
if ($saleIsInstalled && Loader::includeModule('sale'))
{
	?>
	<tr>
		<td id="td_reservation_type"><?
			echo Loc::getMessage(($strUseStoreControl == 'Y' || $strEnableReservation == 'Y'  ? 'CAT_PRODUCT_RESERVED' : 'CAT_PRODUCT_QUANTITY_DECREASE'));
		?></td>
		<td>
			<?
			$currentReserveCondition = Sale\Configuration::getProductReservationCondition();
			$reserveConditions = Sale\Configuration::getReservationConditionList(true);
			if (isset($reserveConditions[$currentReserveCondition]))
				echo htmlspecialcharsex($reserveConditions[$currentReserveCondition]);
			else
				echo Loc::getMessage('BX_CAT_RESERVE_CONDITION_EMPTY');
			unset($reserveConditions, $currentReserveCondition);
			?>&nbsp;<a href="<? echo $saleSettingsUrl; ?>#section_reservation"><? echo Loc::getMessage('CAT_DISCOUNT_PERCENT_FROM_BASE_SALE') ?></a>
		</td>
	</tr>
	<tr id="tr_reservation_period" style="display: <? echo ($strUseStoreControl == 'Y' || $strEnableReservation == 'Y' ? 'table-row' : 'none'); ?>;">
		<td>
			<?echo Loc::getMessage("CAT_RESERVATION_CLEAR_PERIOD")?>
		</td>
		<td>
			<? echo Sale\Configuration::getProductReserveClearPeriod(); ?>
		</td>
	</tr>
	<?
}
	if (!$useSaleDiscountOnly)
	{
		if (CBXFeatures::IsFeatureEnabled('CatDiscountSave'))
		{
		?>
	<tr class="heading">
		<td colspan="2"><? echo Loc::getMessage("CAT_DISCOUNT"); ?></td>
	</tr>
	<tr>
		<td width="40%"><label for="discsave_apply"><? echo Loc::getMessage("CAT_DISCSAVE_APPLY"); ?></label></td>
		<td width="60%">
			<select name="discsave_apply" id="discsave_apply"><?
			foreach ($applyDiscSaveModeList as $applyMode => $applyTitle)
			{
				?><option value="<? echo $applyMode; ?>" <? echo ($applyMode == $currentSettings['discsave_apply'] ? 'selected' : ''); ?>><? echo $applyTitle; ?></option><?
			}
			?>
			</select>
		</td>
	</tr>
	<?
		}
?>
	<tr>
		<td width="40%"><? echo Loc::getMessage('CAT_DISCOUNT_PERCENT_FROM_BASE_PRICE'); ?></td>
		<td width="60%"><?
		if ($saleIsInstalled)
		{
			echo (
				$currentSettings['get_discount_percent_from_base_price'] == 'Y'
				? Loc::getMessage('CAT_DISCOUNT_PERCENT_FROM_BASE_PRICE_YES')
				: Loc::getMessage('CAT_DISCOUNT_PERCENT_FROM_BASE_PRICE_NO')
			);?>&nbsp;<a href="<? echo $saleSettingsUrl; ?>#section_discount"><? echo Loc::getMessage('CAT_DISCOUNT_PERCENT_FROM_BASE_SALE') ?></a><?
		}
		else
		{
			?>
			<input type="hidden" name="get_discount_percent_from_base_price" id="get_discount_percent_from_base_price_N" value="N">
			<input type="checkbox" name="get_discount_percent_from_base_price" id="get_discount_percent_from_base_price_Y" value="Y"<? echo ($currentSettings['get_discount_percent_from_base_price'] == 'Y' ? ' checked' : ''); ?>>
			<?
		}
		?></td>

	</tr>
<?
/*
$strDiscountVat = Option::get('catalog', 'discount_vat');
?>
<tr>
	<td width="40%"><label for="discount_vat_y"><? echo Loc::getMessage("CAT_DISCOUNT_VAT"); ?></label></td>
	<td width="60%"><input type="hidden" name="discount_vat" id="discount_vat_n" value="N"><input type="checkbox" name="discount_vat" id="discount_vat_y" value="Y"<?if ('Y' == $strDiscountVat) echo " checked";?>></td>
</tr>
<?
*/
	}
$viewedTime = (int)Option::get('catalog', 'viewed_time');
$viewedCount = (int)Option::get('catalog', 'viewed_count');
$viewedPeriod = (int)Option::get('catalog', 'viewed_period');
?>
<tr class="heading">
	<td colspan="2"><? echo Loc::getMessage("CAT_VIEWED_PRODUCTS_TITLE") ?></td>
</tr>
<tr>
	<td width="40%"><label for="viewed_time"><? echo Loc::getMessage("CAT_VIEWED_TIME"); ?></label></td>
	<td width="60%">
		<input type="text" name="viewed_time" id="viewed_time" value="<? echo $viewedTime; ?>" size="10">
	</td>
</tr>
<tr>
	<td width="40%"><label for="viewed_count"><? echo Loc::getMessage("CAT_VIEWED_COUNT"); ?></label></td>
	<td width="60%">
		<input type="text" name="viewed_count" id="viewed_count" value="<? echo $viewedCount; ?>" size="10">
	</td>
</tr>
<tr>
	<td width="40%"><label for="viewed_period"><? echo Loc::getMessage("CAT_VIEWED_PERIOD"); ?></label></td>
	<td width="60%">
		<input type="text" name="viewed_period" id="viewed_period" value="<? echo $viewedPeriod; ?>" size="10">
	</td>
</tr>
<tr class="heading">
	<td colspan="2"><? echo Loc::getMessage("CAT_PRODUCT_FORM_SETTINGS"); ?></td>
</tr>
<tr>
	<td width="40%"><? echo Loc::getMessage('CAT_SHOW_OFFERS_IBLOCK'); ?></td>
	<td width="60%">
		<input type="hidden" name="product_form_show_offers_iblock" id="product_form_show_offers_iblock_n" value="N">
		<input type="checkbox" name="product_form_show_offers_iblock" id="product_form_show_offers_iblock_y" value="Y" <?if ($strShowOffersIBlock == "Y") echo " checked";?>>
	</td>
</tr>
<tr>
	<td width="40%"><? echo Loc::getMessage('CAT_SIMPLE_SEARCH'); ?></td>
	<td width="60%">
		<input type="hidden" name="product_form_simple_search" id="product_form_simple_search_n" value="N">
		<input type="checkbox" name="product_form_simple_search" id="product_form_simple_search_y" value="Y" <?if ($strSimpleSearch == "Y") echo " checked";?>>
	</td>
</tr>
<?
	$tabControl->BeginNextTab();
?>
<tr class="heading">
	<td colspan="2"><? echo Loc::getMessage("CAT_COMMON_EXPIMP_SETTINGS"); ?></td>
</tr><?
	for ($i = 0, $intCount = count($arAllOptions); $i < $intCount; $i++)
	{
		$Option = $arAllOptions[$i];
		$val = Option::get('catalog', $Option[0], $Option[2]);
		$type = $Option[3];
		?>
		<tr>
			<td width="40%"><? echo ($type[0]=="checkbox" ? '<label for="'.htmlspecialcharsbx($Option[0]).'">'.$Option[1].'</label>' : $Option[1]); ?></td>
			<td width="60%">
				<?
				if ($Option[0] == 'export_default_path')
				{
					CAdminFileDialog::ShowScript
					(
						array(
							"event" => "BtnClickExpPath",
							"arResultDest" => array("FORM_NAME" => "ara", "FORM_ELEMENT_NAME" => $Option[0]),
							"arPath" => array("PATH" => GetDirPath($val)),
							"select" => 'D',// F - file only, D - folder only
							"operation" => 'O',// O - open, S - save
							"showUploadTab" => false,
							"showAddToMenuTab" => false,
							"fileFilter" => '',
							"allowAllFiles" => true,
							"SaveConfig" => true,
						)
					);
					?><input type="text" name="<? echo htmlspecialcharsbx($Option[0]); ?>" size="50" maxlength="255" value="<?echo htmlspecialcharsbx($val); ?>">&nbsp;<input type="button" name="browseExpPath" value="..." onClick="BtnClickExpPath()"><?
				}
				else
				{
					if($type[0]=="checkbox"):?>
						<input type="checkbox" name="<?echo htmlspecialcharsbx($Option[0])?>" id="<?echo htmlspecialcharsbx($Option[0])?>" value="Y"<?if($val=="Y")echo" checked";?>>
					<?elseif($type[0]=="text"):?>
						<input type="text" size="<?echo $type[1]?>" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($Option[0])?>">
					<?elseif($type[0]=="textarea"):?>
						<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($Option[0])?>"><?echo htmlspecialcharsbx($val)?></textarea>
					<?endif;
				}
				?>
			</td>
		</tr>
	<?
	}
	?>
	<tr>
		<td width="40%"><?=Loc::getMessage("CAT_DEF_OUTFILE")?></td>
		<td width="60%">
			<?$default_outfile_action = Option::get('catalog', 'default_outfile_action');?>
			<select name="default_outfile_action">
				<option value="D" <?if ($default_outfile_action=="D" || strlen($default_outfile_action)<=0) echo "selected" ?>><?echo Loc::getMessage("CAT_DEF_OUTFILE_D") ?></option>
				<option value="H" <?if ($default_outfile_action=="H") echo "selected" ?>><?=Loc::getMessage("CAT_DEF_OUTFILE_H")?></option>
				<option value="F" <?if ($default_outfile_action=="F") echo "selected" ?>><?=Loc::getMessage("CAT_DEF_OUTFILE_F")?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td width="40%">
		<?
		$yandex_agent_file = Option::get('catalog', 'yandex_agent_file');
		CAdminFileDialog::ShowScript
		(
			Array(
				"event" => "BtnClick",
				"arResultDest" => array("FORM_NAME" => "ara", "FORM_ELEMENT_NAME" => "yandex_agent_file"),
				"arPath" => array("PATH" => GetDirPath($yandex_agent_file)),
				"select" => 'F',// F - file only, D - folder only
				"operation" => 'O',// O - open, S - save
				"showUploadTab" => true,
				"showAddToMenuTab" => false,
				"fileFilter" => 'php',
				"allowAllFiles" => true,
				"SaveConfig" => true,
			)
		);
		?>
		<?echo Loc::getMessage("CAT_AGENT_FILE")?></td>
		<td width="60%"><input type="text" name="yandex_agent_file" size="50" maxlength="255" value="<?echo $yandex_agent_file?>">&nbsp;<input type="button" name="browse" value="..." onClick="BtnClick()"></td>
	</tr>
	<tr class="heading">
		<td colspan="2"><?echo Loc::getMessage("CO_PAR_IE_CSV") ?></td>
	</tr>
	<tr>
		<td width="40%" valign="top"><?echo Loc::getMessage("CO_PAR_DPP_CSV") ?></td>
		<td width="60%" valign="top">
	<?
	$arVal = array();
	$strVal = (string)Option::get('catalog', 'allowed_product_fields');
	if ($strVal != '')
	{
		$arVal = array_fill_keys(explode(',', $strVal), true);
	}
	$productFields = array_merge(
		CCatalogCSVSettings::getSettingsFields(CCatalogCSVSettings::FIELDS_ELEMENT),
		CCatalogCSVSettings::getSettingsFields(CCatalogCSVSettings::FIELDS_CATALOG)
	);
	?><select name="allowed_product_fields[]" multiple size="8"><?
	foreach ($productFields as &$oneField)
	{
		?><option value="<? echo htmlspecialcharsbx($oneField['value']); ?>"<? echo (isset($arVal[$oneField['value']]) ? ' selected' : ''); ?>><? echo htmlspecialcharsex($oneField['name']); ?></option><?
	}
	if (isset($oneField))
		unset($oneField);
	unset($productFields);
	?></select>
		</td>
	</tr>
	<tr>
		<td width="40%" valign="top"><? echo Loc::getMessage("CO_AVAIL_PRICE_FIELDS"); ?></td>
		<td width="60%" valign="top">
	<?
	$arVal = array();
	$strVal = (string)Option::get('catalog', 'allowed_price_fields');
	if ($strVal != '')
	{
		$arVal = array_fill_keys(explode(',', $strVal), true);
	}
	$priceFields = CCatalogCSVSettings::getSettingsFields(CCatalogCSVSettings::FIELDS_PRICE);
	?><select name="allowed_price_fields[]" multiple size="3"><?
	foreach ($priceFields as &$oneField)
	{
		?><option value="<? echo htmlspecialcharsbx($oneField['value']); ?>"<? echo (isset($arVal[$oneField['value']]) ? ' selected' : ''); ?>><? echo htmlspecialcharsex($oneField['name']); ?></option><?
	}
	if (isset($oneField))
		unset($oneField);
	unset($priceFields);
	?></select>
		</td>
	</tr>
	<tr>
		<td width="40%"><?echo Loc::getMessage("CAT_NUM_CATALOG_LEVELS");?></td>
		<td width="60%"><?
			$strVal = (int)Option::get('catalog', 'num_catalog_levels');
			?><input type="text" size="5" maxlength="5" value="<? echo $strVal; ?>" name="num_catalog_levels">
		</td>
	</tr>
	<tr>
		<td width="40%" valign="top"><?echo Loc::getMessage("CO_PAR_DPG_CSV") ?></td>
		<td width="60%">
	<?
	$arVal = array();
	$strVal = (string)Option::get('catalog', 'allowed_group_fields');
	if ($strVal != '')
	{
		$arVal = array_fill_keys(explode(',', $strVal), true);
	}
	$sectionFields = CCatalogCSVSettings::getSettingsFields(CCatalogCSVSettings::FIELDS_SECTION);
	?><select name="allowed_group_fields[]" multiple size="9"><?
	foreach ($sectionFields as &$oneField)
	{
		?><option value="<? echo htmlspecialcharsbx($oneField['value']); ?>"<? echo (isset($arVal[$oneField['value']]) ? ' selected' : ''); ?>><? echo htmlspecialcharsex($oneField['name']); ?></option><?
	}
	if (isset($oneField))
		unset($oneField);
	unset($sectionFields);
	?></select>
		</td>
	</tr>
	<tr>
		<td width="40%" valign="top"><?echo Loc::getMessage("CO_PAR_DV1_CSV")?></td>
		<td width="60%" valign="top">
	<?
	$arVal = array();
	$strVal = (string)Option::get('catalog', 'allowed_currencies');
	if ($strVal != '')
	{
		$arVal = array_fill_keys(explode(',', $strVal), true);
	}
	?><select name="allowed_currencies[]" multiple size="5"><?
	$currencyIterator = Currency\CurrencyTable::getList(array(
		'select' => array('CURRENCY', 'FULL_NAME' => 'RT_LANG.FULL_NAME'),
		'order' => array('SORT' => 'ASC', 'CURRENCY' => 'ASC'),
		'runtime' => array(
			'RT_LANG' => array(
				'data_type' => 'Bitrix\Currency\CurrencyLang',
				'reference' => array(
					'=this.CURRENCY' => 'ref.CURRENCY',
					'=ref.LID' => new Main\DB\SqlExpression('?', LANGUAGE_ID)
				)
			)
		)
	));
	while ($currency = $currencyIterator->fetch())
	{
		$currency['FULL_NAME'] = (string)$currency['FULL_NAME'];
		?><option value="<? echo $currency["CURRENCY"]; ?>"<? echo (isset($arVal[$currency["CURRENCY"]]) ? ' selected' : ''); ?>><?
		echo $currency['CURRENCY'];
		if ($currency['FULL_NAME'] != '')
			echo ' ('.htmlspecialcharsex($currency['FULL_NAME']).')'; ?></option><?
	}
	unset($currency, $currencyIterator);
	?></select>
		</td>
	</tr>
<?
	$tabControl->BeginNextTab();
	$arVATRef = CatalogGetVATArray(array(), true);

	$arCatalogList = array();
	$arIBlockSitesList = array();

	$arIBlockFullInfo = array();

	$arRecurring = array();
	$arRecurringKey = array();

	$rsIBlocks = CIBlock::GetList(array('IBLOCK_TYPE' => 'ASC','ID' => 'ASC'));
	while ($arIBlock = $rsIBlocks->Fetch())
	{
		$arIBlock['ID'] = (int)$arIBlock['ID'];
		if (!isset($arIBlockSitesList[$arIBlock['ID']]))
		{
			$arLIDList = array();
			$arWithLinks = array();
			$arWithoutLinks = array();
			$rsIBlockSites = CIBlock::GetSite($arIBlock['ID']);
			while ($arIBlockSite = $rsIBlockSites->Fetch())
			{
				$arLIDList[] = $arIBlockSite['LID'];
				$arWithLinks[] = '<a href="/bitrix/admin/site_edit.php?LID='.urlencode($arIBlockSite['LID']).'&lang='.LANGUAGE_ID.'" title="'.Loc::getMessage("CO_SITE_ALT").'">'.htmlspecialcharsbx($arIBlockSite["LID"]).'</a>';
				$arWithoutLinks[] = htmlspecialcharsbx($arIBlockSite['LID']);
			}
			$arIBlockSitesList[$arIBlock['ID']] = array(
				'SITE_ID' => $arLIDList,
				'WITH_LINKS' => implode('&nbsp;',$arWithLinks),
				'WITHOUT_LINKS' => implode(' ',$arWithoutLinks),
			);
		}
		$arIBlockItem = array(
			'ID' => $arIBlock['ID'],
			'IBLOCK_TYPE_ID' => $arIBlock['IBLOCK_TYPE_ID'],
			'SITE_ID' => $arIBlockSitesList[$arIBlock['ID']]['SITE_ID'],
			'NAME' => htmlspecialcharsbx($arIBlock['NAME']),
			'ACTIVE' => $arIBlock['ACTIVE'],
			'FULL_NAME' => '['.$arIBlock['IBLOCK_TYPE_ID'].'] '.htmlspecialcharsbx($arIBlock['NAME']).' ('.$arIBlockSitesList[$arIBlock['ID']]['WITHOUT_LINKS'].')',
			'IS_CATALOG' => 'N',
			'IS_CONTENT' => 'N',
			'YANDEX_EXPORT' => 'N',
			'VAT_ID' => 0,
			'PRODUCT_IBLOCK_ID' => 0,
			'SKU_PROPERTY_ID' => 0,
			'OFFERS_IBLOCK_ID' => 0,
			'IS_OFFERS' => 'N',
			'OFFERS_PROPERTY_ID' => 0
		);
		$arIBlockFullInfo[$arIBlock['ID']] = $arIBlockItem;
	}

	$catalogIterator = Catalog\CatalogIblockTable::getList(array(
		'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'SUBSCRIPTION', 'YANDEX_EXPORT', 'VAT_ID')
	));
	while ($arOneCatalog = $catalogIterator->fetch())
	{
		$arOneCatalog['IBLOCK_ID'] = (int)$arOneCatalog['IBLOCK_ID'];
		$arOneCatalog['VAT_ID'] = (int)$arOneCatalog['VAT_ID'];
		$arOneCatalog['PRODUCT_IBLOCK_ID'] = (int)$arOneCatalog['PRODUCT_IBLOCK_ID'];
		$arOneCatalog['SKU_PROPERTY_ID'] = (int)$arOneCatalog['SKU_PROPERTY_ID'];

		if (!CBXFeatures::IsFeatureEnabled('SaleRecurring') && 'Y' == $arOneCatalog['SUBSCRIPTION'])
		{
			$arRecurring[] = '['.$arIBlockItem['ID'].'] '.$arIBlockItem['NAME'];
			$arRecurringKey[$arIBlockItem['ID']] = true;
		}

		$arIBlock = $arIBlockFullInfo[$arOneCatalog['IBLOCK_ID']];
		$arIBlock['IS_CATALOG'] = 'Y';
		$arIBlock['IS_CONTENT'] = (CBXFeatures::IsFeatureEnabled('SaleRecurring') ? $arOneCatalog['SUBSCRIPTION'] : 'N');
		$arIBlock['YANDEX_EXPORT'] = $arOneCatalog['YANDEX_EXPORT'];
		$arIBlock['VAT_ID'] = $arOneCatalog['VAT_ID'];
		$arIBlock['PRODUCT_IBLOCK_ID'] = $arOneCatalog['PRODUCT_IBLOCK_ID'];
		$arIBlock['SKU_PROPERTY_ID'] = $arOneCatalog['SKU_PROPERTY_ID'];
		if (0 < $arOneCatalog['PRODUCT_IBLOCK_ID'])
		{
			$arIBlock['IS_OFFERS'] = 'Y';
			$arOwnBlock = $arIBlockFullInfo[$arOneCatalog['PRODUCT_IBLOCK_ID']];
			$arOwnBlock['OFFERS_IBLOCK_ID'] = $arOneCatalog['IBLOCK_ID'];
			$arOwnBlock['OFFERS_PROPERTY_ID'] = $arOneCatalog['SKU_PROPERTY_ID'];
			$arIBlockFullInfo[$arOneCatalog['PRODUCT_IBLOCK_ID']] = $arOwnBlock;
			unset($arOwnBlock);
		}
		$arIBlockFullInfo[$arOneCatalog['IBLOCK_ID']] = $arIBlock;
		if ('Y' == $arIBlock['IS_CATALOG'])
			$arCatalogList[$arOneCatalog['IBLOCK_ID']] = $arIBlock;
		unset($arIBlock);
	}
	unset($arCatalog, $catalogIterator);

	$arIBlockTypeIDList = array();
	$arIBlockTypeNameList = array();
	$rsIBlockTypes = CIBlockType::GetList(array("sort"=>"asc"), array("ACTIVE"=>"Y"));
	while ($arIBlockType = $rsIBlockTypes->Fetch())
	{
		if ($ar = CIBlockType::GetByIDLang($arIBlockType["ID"], LANGUAGE_ID, true))
		{
			$arIBlockTypeIDList[] = htmlspecialcharsbx($arIBlockType["ID"]);
			$arIBlockTypeNameList[] = htmlspecialcharsbx('['.$arIBlockType["ID"].'] '.$ar["~NAME"]);
		}
	}

	$arDoubleIBlockFullInfo = $arIBlockFullInfo;

?>
<tr><td><?
	if (!empty($arRecurring))
	{
		$strRecurring = Loc::getMessage('SMALL_BUSINESS_RECURRING_ERR_LIST').'<ul><li>'.implode('</li><li>', $arRecurring).'</li></ul>'.Loc::getMessage('SMALL_BUSINESS_RECURRING_ERR_LIST_CLEAR');
		CAdminMessage::ShowMessage(array(
			"MESSAGE" => Loc::getMessage("SMALL_BUSINESS_RECURRING_ERR"),
			"DETAILS" => $strRecurring,
			"HTML" => true,
			"TYPE" => "ERROR",
		));
	}
?>
<script type="text/javascript">
function ib_checkFldActivity(id, flag)
{
	var Cat = BX('IS_CATALOG_' + id + '_Y');
	var Cont = BX('IS_CONTENT_' + id + '_Y');
	var Yand = BX('YANDEX_EXPORT_' + id + '_Y');
	var Vat = BX('VAT_ID_' + id);

	if (flag == 0)
	{
		if (!!Cat && !!Cont)
		{
			if (!Cat.checked)
				Cont.checked = false;
		}
	}

	if (flag == 1)
	{
		if (!!Cat && !!Cont)
		{
			if (Cont.checked)
				Cat.checked = true;
		}
	}

	var bActive = Cat.checked;
	if (!!Yand)
		Yand.disabled = !bActive;
	if (!!Vat)
		Vat.disabled = !bActive;
}

function show_add_offers(id, obj)
{
	var value = obj.options[obj.selectedIndex].value;
	var add_form = document.getElementById('offers_add_info_'+id);
	if (undefined !== add_form)
	{
		if (<? echo CATALOG_NEW_OFFERS_IBLOCK_NEED; ?> == value)
		{
			add_form.style.display = 'block';
		}
		else
		{
			add_form.style.display = 'none';
		}
	}
}
function change_offers_ibtype(obj,ID)
{
	var value = obj.value;
	if ('Y' == value)
	{
		document.forms.ara['OFFERS_TYPE_' + ID].disabled = true;
		document.forms.ara['OFFERS_NEWTYPE_' + ID].disabled = false;
	}
	else if ('N' == value)
	{
		document.forms.ara['OFFERS_TYPE_' + ID].disabled = false;
		document.forms.ara['OFFERS_NEWTYPE_' + ID].disabled = true;
	}
}
</script>
<table width="100%" cellspacing="0" cellpadding="0" border="0" class="internal">
	<tr class="heading">
		<td><?=Loc::getMessage("CAT_IBLOCK_SELECT_NAME")?></td>
		<td><?=Loc::getMessage("CAT_IBLOCK_SELECT_CAT")?></td>
		<td><?=Loc::getMessage("CAT_IBLOCK_SELECT_OFFERS")?></td><?
		if (CBXFeatures::IsFeatureEnabled('SaleRecurring'))
		{
			?><td><?=Loc::getMessage("CO_SALE_CONTENT") ?></td><?
		}
		?><td><?=Loc::getMessage("CAT_IBLOCK_SELECT_YAND")?></td>
		<td><?=Loc::getMessage("CAT_IBLOCK_SELECT_VAT")?></td>
	</tr>
	<?
	foreach ($arIBlockFullInfo as &$res)
	{
		?>
		<tr>
			<td>[<a title="<? echo Loc::getMessage("CO_IB_TYPE_ALT"); ?>" href="/bitrix/admin/iblock_admin.php?type=<? echo urlencode($res["IBLOCK_TYPE_ID"]); ?>&lang=<? echo LANGUAGE_ID; ?>&admin=Y"><? echo $res["IBLOCK_TYPE_ID"]; ?></a>]
				&nbsp;[<? echo $res['ID']; ?>] <a title="<? echo Loc::getMessage("CO_IB_ELEM_ALT"); ?>" href="<? echo CIBlock::GetAdminElementListLink($res["ID"], array('find_section_section' => '0', 'admin' => 'Y')); ?>"><? echo $res["NAME"]; ?></a> (<? echo $arIBlockSitesList[$res['ID']]['WITH_LINKS']; ?>)
				<input type="hidden" name="IS_OFFERS_<? echo $res["ID"]; ?>" value="<? echo $res['IS_OFFERS']; ?>" />
			</td>
			<td align="center" style="text-align: center;"><input type="hidden" name="IS_CATALOG_<?echo $res["ID"] ?>" id="IS_CATALOG_<?echo $res["ID"] ?>_N" value="N"><input type="checkbox" name="IS_CATALOG_<?echo $res["ID"] ?>" id="IS_CATALOG_<?echo $res["ID"] ?>_Y" onclick="ib_checkFldActivity('<?=$res['ID']?>', 0)" <?if ('Y' == $res['IS_CATALOG']) echo 'checked="checked"'?> <? if ('Y' == $res['IS_OFFERS']) echo 'disabled="disabled"'; ?>value="Y" /></td>
			<td align="center"><select id="OFFERS_IBLOCK_ID_<? echo $res["ID"]; ?>" name="OFFERS_IBLOCK_ID_<? echo $res["ID"]; ?>" class="typeselect" <? echo ('Y' == $res['IS_OFFERS'] ? 'disabled="disabled"' : 'onchange="show_add_offers('.$res["ID"].',this);"'); ?> style="width: 100%;">
			<option value="0" <? echo (0 == $res['OFFERS_IBLOCK_ID'] ? 'selected' : '');?>><? echo Loc::getMessage('CAT_IBLOCK_OFFERS_EMPTY')?></option>
			<?
			if ('Y' != $res['IS_OFFERS'])
			{
				if ($USER->IsAdmin())
				{
					?><option value="<? echo CATALOG_NEW_OFFERS_IBLOCK_NEED; ?>"><? echo Loc::getMessage('CAT_IBLOCK_OFFERS_NEW')?></option><?
				}
				foreach ($arDoubleIBlockFullInfo as &$value)
				{
					if ($value['ID'] != $res['OFFERS_IBLOCK_ID'])
					{
						if (
							('Y' != $value['IS_CATALOG'])
							|| ('N' == $value['ACTIVE'])
							|| ('Y' == $value['IS_OFFERS'])
							|| (0 < $value['OFFERS_IBLOCK_ID'])
							|| ($res['ID'] == $value['ID'])
							|| (0 < $value['PRODUCT_IBLOCK_ID'])
						)
						{
							continue;
						}
						else
						{
							$arDiffParent = array();
							$arDiffParent = array_diff($value['SITE_ID'],$res['SITE_ID']);
							$arDiffOffer = array();
							$arDiffOffer = array_diff($res['SITE_ID'],$value['SITE_ID']);
							if (!empty($arDiffParent) || !empty($arDiffOffer))
							{
								continue;
							}
						}
					}
					?><option value="<? echo (int)$value['ID']; ?>"<? echo ($value['ID'] == $res['OFFERS_IBLOCK_ID'] ? ' selected' : ''); ?>><? echo $value['FULL_NAME']; ?></option><?
				}
				if (isset($value))
					unset($value);
			}
			?>
			</select>
			<div id="offers_add_info_<? echo $res["ID"]; ?>" style="display: none; width: 98%; maring: 0 1%;"><table class="internal" style="width: 100%;"><tbody>
				<tr><td style="text-align: right; width: 25%;"><? echo Loc::getMessage('CAT_IBLOCK_OFFERS_TITLE'); ?>:</td><td style="text-align: left; width: 75%;"><input type="text" name="OFFERS_NAME_<? echo $res["ID"]; ?>" value="" style="width: 98%; margin: 0 1%;" /></td></tr>
				<tr><td style="text-align: left; width: 100%;" colspan="2"><input type="radio" value="N" id="CREATE_OFFERS_TYPE_N_<? echo $res['ID']; ?>" name="CREATE_OFFERS_TYPE_<? echo $res['ID']; ?>" checked="checked" onclick="change_offers_ibtype(this,<? echo $res['ID']?>);"><label for="CREATE_OFFERS_TYPE_N_<? echo $res['ID']; ?>"><? echo Loc::getMessage('CAT_IBLOCK_OFFERS_OLD_IBTYPE');?></label></td></tr>
				<tr><td style="text-align: right; width: 25%;"><? echo Loc::getMessage('CAT_IBLOCK_OFFERS_TYPE'); ?>:</td><td style="text-align: left; width: 75%;"><? echo SelectBoxFromArray('OFFERS_TYPE_'.$res["ID"],array('REFERENCE' => $arIBlockTypeNameList,'REFERENCE_ID' => $arIBlockTypeIDList),'','','style="width: 98%;  margin: 0 1%;"'); ?></td></tr>
				<tr><td style="text-align: left; width: 100%;" colspan="2"><input type="radio" value="Y" id="CREATE_OFFERS_TYPE_Y_<? echo $res['ID']; ?>" name="CREATE_OFFERS_TYPE_<? echo $res['ID']; ?>" onclick="change_offers_ibtype(this,<? echo $res['ID']?>);"><label for="CREATE_OFFERS_TYPE_Y_<? echo $res['ID']; ?>"><? echo Loc::getMessage('CAT_IBLOCK_OFFERS_NEW_IBTYPE');?></label></td></tr>
				<tr><td style="text-align: right; width: 25%;"><? echo Loc::getMessage('CAT_IBLOCK_OFFERS_NEWTYPE'); ?>:</td><td style="text-align: left; width: 75%;"><input type="text" name="OFFERS_NEWTYPE_<? echo $res["ID"]; ?>" value="" style="width: 98%; margin: 0 1%;" disabled="disabled" /></td></tr>
			</tbody></table></div></td><?
			if (CBXFeatures::IsFeatureEnabled('SaleRecurring'))
			{
				?><td align="center" style="text-align: center;"><input type="hidden" name="IS_CONTENT_<?echo $res["ID"] ?>" id="IS_CONTENT_<?echo $res["ID"] ?>_N" value="N"><input type="checkbox" name="IS_CONTENT_<?echo $res["ID"] ?>" id="IS_CONTENT_<?echo $res["ID"] ?>_Y" onclick="ib_checkFldActivity('<?=$res['ID']?>', 1)" <?if ('Y' == $res["IS_CONTENT"]) echo "checked"?> value="Y" /></td><?
			}
			else
			{
				?><input type="hidden" name="IS_CONTENT_<?echo $res["ID"] ?>" value="N" id="IS_CONTENT_<?echo $res["ID"] ?>_N"><?
			}
			?><td align="center" style="text-align: center;"><input type="hidden" name="YANDEX_EXPORT_<?echo $res["ID"] ?>" id="YANDEX_EXPORT_<?echo $res["ID"] ?>_N"><input type="checkbox" name="YANDEX_EXPORT_<?echo $res["ID"] ?>" id="YANDEX_EXPORT_<?echo $res["ID"] ?>_Y" <?if ('N' == $res['IS_CATALOG']) echo 'disabled="disabled"';?> <?if ('Y' == $res["YANDEX_EXPORT"]) echo "checked"?> value="Y" /></td>
			<td align="center"><?=SelectBoxFromArray('VAT_ID_'.$res['ID'], $arVATRef, $res['VAT_ID'], '', ('N' == $res['IS_CATALOG'] ? 'disabled="disabled"' : ''))?></td>
		</tr>
		<?
	}
	if (isset($res))
		unset($res);
	?>
</table>
</td></tr>
<?
	if ($USER->IsAdmin())
	{
		if (CBXFeatures::IsFeatureEnabled('SaleRecurring'))
		{
			$tabControl->BeginNextTab();

			$arVal = array();
			$strVal = (string)Option::get('catalog', 'avail_content_groups');
			if ($strVal != '')
				$arVal = explode(',', $strVal);

			$dbUserGroups = CGroup::GetList(($b="c_sort"), ($o="asc"), array("ANONYMOUS" => "N"));
			while ($arUserGroups = $dbUserGroups->Fetch())
			{
				$arUserGroups["ID"] = (int)$arUserGroups["ID"];
				if ($arUserGroups["ID"] == 2)
					continue;
			?>
			<tr>
				<td width="40%"><label for="user_group_<?=$arUserGroups["ID"]?>"><?= htmlspecialcharsEx($arUserGroups["NAME"])?></label> [<a href="group_edit.php?ID=<?=$arUserGroups["ID"]?>&lang=<?=LANGUAGE_ID?>" title="<?=Loc::getMessage("CO_USER_GROUP_ALT")?>"><?=$arUserGroups["ID"]?></a>]:</td>
				<td width="60%"><input type="checkbox" id="user_group_<?=$arUserGroups["ID"]?>" name="AVAIL_CONTENT_GROUPS[]"<?if (in_array($arUserGroups["ID"], $arVal)) echo " checked"?> value="<?= $arUserGroups["ID"] ?>"></td>
			</tr>
			<?
			}
		}

		$tabControl->BeginNextTab();

		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights2.php");

	}

	$tabControl->Buttons();
	?>
<input type="submit" class="adm-btn-save" <? if ($bReadOnly) echo "disabled" ?> name="Update" value="<? echo Loc::getMessage("CAT_OPTIONS_BTN_SAVE"); ?>">
<input type="hidden" name="Update" value="Y">
<input type="reset" name="reset" value="<?echo Loc::getMessage("CAT_OPTIONS_BTN_RESET")?>">
<input type="button" <?if ($bReadOnly) echo "disabled" ?> title="<?echo Loc::getMessage("CAT_OPTIONS_BTN_HINT_RESTORE_DEFAULT")?>" onclick="RestoreDefaults();" value="<?echo Loc::getMessage("CAT_OPTIONS_BTN_RESTORE_DEFAULT")?>">
</form>
<script type="text/javascript">
BX.hint_replace(BX('hint_reservation'), '<? echo Loc::getMessage('CAT_ENABLE_RESERVATION_HINT'); ?>');
</script>
<?
	$tabControl->End();
?><h2><?= Loc::getMessage("COP_SYS_ROU") ?></h2>
<?
	$aTabs = array(
		array("DIV" => "fedit2", "TAB" => Loc::getMessage("COP_TAB2_AGENT"), "ICON" => "catalog_settings", "TITLE" => Loc::getMessage("COP_TAB2_AGENT_TITLE")),
		array("DIV" => "fedit4", "TAB" => Loc::getMessage("COP_TAB_RECALC"), "ICON" => "catalog_settings", "TITLE" => Loc::getMessage("COP_TAB_RECALC_TITLE")),
	);
	if ($strUseStoreControl === 'N' && !empty($arCatalogList))
	{
		$aTabs[] = array("DIV" => "fedit3", "TAB" => Loc::getMessage("CAT_QUANTITY_CONTROL_TAB"), "ICON" => "catalog_settings", "TITLE" => Loc::getMessage("CAT_QUANTITY_CONTROL"));

?>
<script type="text/javascript">
	function catClearQuantity(el, action)
	{
		var waiter_parent = BX.findParent(el, BX.is_relative),
			pos = BX.pos(el, !!waiter_parent);
		var iblockId = BX("catalogs_id").value;
		if(action == 'clearStore')
		{
			iblockId = BX("catalogs_store_id").value;
		}
		var dateURL = {
			sessid: BX.bitrix_sessid(),
			iblockId: iblockId,
			action: action,
			elId: el.id
		};
		if (action === 'clearStore')
		{
			var obStore = BX('stores_id');
			if (!!obStore)
			{
				dateURL.storeId = obStore.value;
			}
			else
			{
				return;
			}
		}
		el.disabled = true;
		el.bxwaiter = (waiter_parent || document.body).appendChild(BX.create('DIV', {
			props: {className: 'adm-btn-load-img'},
			style: {
				top: parseInt((pos.bottom + pos.top)/2 - 5, 10) + 'px',
				left: parseInt((pos.right + pos.left)/2 - 9, 10) + 'px'
			}
		}));
		BX.addClass(el, 'adm-btn-load');
		BX.ajax.post(
			'/bitrix/admin/cat_quantity_control.php?lang=<? echo LANGUAGE_ID; ?>',
			dateURL,
			catClearQuantityResult
		);
	}

	function catClearQuantityResult(result)
	{
		if (result.length > 0)
		{
			var res = eval( '('+result+')' );
			var el = BX(res);
			BX(res).setAttribute('class', 'adm-btn');
			if (el.bxwaiter && el.bxwaiter.parentNode)
			{
				el.bxwaiter.parentNode.removeChild(el.bxwaiter);
				el.bxwaiter = null;
			}
			el.disabled = false;
		}
	}
</script>
<?
	}

	$systemTabControl = new CAdminTabControl("tabControl2", $aTabs, true, true);

	$systemTabControl->Begin();
	$systemTabControl->BeginNextTab();
?><tr><td align="left"><?
	$arAgentInfo = false;
	$rsAgents = CAgent::GetList(array(),array('MODULE_ID' => 'catalog','NAME' => 'CCatalog::PreGenerateXML("yandex");'));
	if ($arAgent = $rsAgents->Fetch())
	{
		$arAgentInfo = $arAgent;
	}
	if (!is_array($arAgentInfo) || empty($arAgentInfo))
	{
		?><form name="agent_form" method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?=LANGUAGE_ID?>">
		<?echo bitrix_sessid_post()?>
		<input type="submit" class="adm-btn-save" name="agent_start" value="<? echo Loc::getMessage('CAT_AGENT_START') ?>" <?if ($bReadOnly) echo "disabled" ?>>
		</form><?
	}
	else
	{
		echo Loc::getMessage('CAT_AGENT_ACTIVE').':&nbsp;'.($arAgentInfo['ACTIVE'] == 'Y' ? Loc::getMessage("MAIN_YES") : Loc::getMessage("MAIN_NO")).'<br>';
		if ($arAgentInfo['LAST_EXEC'])
		{
			echo Loc::getMessage('CAT_AGENT_LAST_EXEC').':&nbsp;'.($arAgentInfo['LAST_EXEC'] ? $arAgentInfo['LAST_EXEC'] : '').'<br>';
			echo Loc::getMessage('CAT_AGENT_NEXT_EXEC').':&nbsp;'.($arAgentInfo['NEXT_EXEC'] ? $arAgentInfo['NEXT_EXEC'] : '').'<br>';
		}
		else
		{
			echo Loc::getMessage('CAT_AGENT_WAIT_START').'<br>';
		}
	}
?><br><?
	$strYandexFile = str_replace('//', '/', Option::get('catalog', 'export_default_path').'/yandex.php');
	if (file_exists($_SERVER['DOCUMENT_ROOT'].$strYandexFile))
	{
		echo Loc::getMessage(
			'CAT_AGENT_FILEPATH',
			array(
				'#FILE#' => '<a href="'.$strYandexFile.'">'.$strYandexFile.'</a>'
			)
		).'<br>';
	}
	else
	{
		echo Loc::getMessage('CAT_AGENT_FILE_ABSENT').'<br>';
	}
?><br><?
	echo Loc::getMessage('CAT_AGENT_EVENT_LOG').':&nbsp;';

?><a href="/bitrix/admin/event_log.php?lang=<? echo LANGUAGE_ID; ?>&set_filter=Y<? echo CCatalogEvent::GetYandexAgentFilter(); ?>"><? echo Loc::getMessage('CAT_AGENT_EVENT_LOG_SHOW_ERROR')?></a>
</td></tr>
<?
	$systemTabControl->BeginNextTab();
?><tr><td align="left"><?
	$firstTop = ' style="margin-top: 0;"';
	if (!$useSaleDiscountOnly)
	{
	?>
	<h4<? echo $firstTop; ?>><? echo Loc::getMessage('CAT_PROC_REINDEX_DISCOUNT'); ?></h4>
	<input class="adm-btn-save" type="button" id="discount_reindex" value="<? echo Loc::getMessage('CAT_PROC_REINDEX_DISCOUNT_BTN'); ?>">
	<p><? echo Loc::getMessage('CAT_PROC_REINDEX_DISCOUNT_ALERT'); ?></p><?
		$firstTop = '';
	}
	if (CBXFeatures::IsFeatureEnabled('CatCompleteSet') && CCatalogProductSetAvailable::getAllCounter() > 0)
	{
	?><h4<? echo $firstTop; ?>><? echo Loc::getMessage('CAT_PROC_REINDEX_SETS_AVAILABLE'); ?></h4>
	<input class="adm-btn-save" type="button" id="sets_reindex" value="<? echo Loc::getMessage('CAT_PROC_REINDEX_SETS_AVAILABLE_BTN'); ?>">
	<p><? echo Loc::getMessage('CAT_PROC_REINDEX_SETS_AVAILABLE_ALERT'); ?></p><?
		$firstTop = '';
	}
	?>
</td></tr><?
	if ($strUseStoreControl === 'N' && !empty($arCatalogList))
	{
		$userListID = array();
		$strQuantityUser = '';
		$strQuantityReservedUser = '';
		$strStoreUser = '';
		$strClearQuantityDate = '';
		$strClearQuantityReservedDate = '';
		$strClearStoreDate = '';

		$clearQuantityUser = (int)Option::get('catalog', 'clear_quantity_user');
		if ($clearQuantityUser < 0)
			$clearQuantityUser = 0;
		$userListID[$clearQuantityUser] = true;

		$clearQuantityReservedUser = (int)Option::get('catalog', 'clear_reserved_quantity_user');
		if ($clearQuantityReservedUser < 0)
			$clearQuantityReservedUser = 0;
		$userListID[$clearQuantityReservedUser] = true;

		$clearStoreUser = (int)Option::get('catalog', 'clear_store_user');
		if ($clearStoreUser < 0)
			$clearStoreUser = 0;
		$userListID[$clearStoreUser] = true;

		if (isset($userListID[0]))
			unset($userListID[0]);
		if (!empty($userListID))
		{
			$strClearQuantityDate = Option::get('catalog', 'clear_quantity_date');
			$strClearQuantityReservedDate = Option::get('catalog', 'clear_reserved_quantity_date');
			$strClearStoreDate = Option::get('catalog', 'clear_store_date');

			$arUserList = array();
			$strNameFormat = CSite::GetNameFormat(true);

			$canViewUserList = (
				$USER->CanDoOperation('view_subordinate_users')
				|| $USER->CanDoOperation('view_all_users')
				|| $USER->CanDoOperation('edit_all_users')
				|| $USER->CanDoOperation('edit_subordinate_users')
			);
			$userIterator = Main\UserTable::getList(array(
				'select' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME'),
				'filter' => array('ID' => array_keys($userListID))
			));
			while ($arOneUser = $userIterator->fetch())
			{
				$arOneUser['ID'] = (int)$arOneUser['ID'];
				if ($canViewUserList)
					$arUserList[$arOneUser['ID']] = '<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='.$arOneUser['ID'].'">'.CUser::FormatName($strNameFormat, $arOneUser).'</a>';
				else
					$arUserList[$arOneUser['ID']] = CUser::FormatName($strNameFormat, $arOneUser);
			}
			unset($arOneUser, $userIterator, $canViewUserList);
			if (isset($arUserList[$clearQuantityUser]))
				$strQuantityUser = $arUserList[$clearQuantityUser];
			if (isset($arUserList[$clearQuantityReservedUser]))
				$strQuantityReservedUser = $arUserList[$clearQuantityReservedUser];
			if (isset($arUserList[$clearStoreUser]))
				$strStoreUser = $arUserList[$clearStoreUser];
		}
		$boolStoreExists = false;
		$arStores = array();
		$arStores[] = array("ID" => -1, "ADDRESS" => Loc::getMessage("CAT_ALL_STORES"));
		$rsStores = CCatalogStore::GetList(
			array('SORT' => 'ASC', 'ID' => 'ASC'),
			array('ACTIVE' => 'Y'),
			false,
			false,
			array('ID', 'TITLE', 'ADDRESS')
		);
		while ($arStore = $rsStores->GetNext())
		{
			$boolStoreExists = true;
			$arStores[] = $arStore;
		}

		$systemTabControl->BeginNextTab();
	?>
	<tr>
		<td><?= Loc::getMessage("CAT_SELECT_CATALOG") ?>:</td>
		<td>
			<select style="max-width: 300px" id="catalogs_id" name="catalogs_id" <?=($bReadOnly ? ' disabled' : ''); ?>>
				<?
				foreach($arCatalogList as &$arOneCatalog)
				{
					echo '<option value="'.$arOneCatalog['ID'].'">'.htmlspecialcharsex($arOneCatalog["NAME"]).' ('.$arIBlockSitesList[$arOneCatalog['ID']]['WITHOUT_LINKS'].')</option>';
				}
				unset($arOneCatalog);
				?>
			</select>
		</td>
	</tr>

	<tr>
		<td width="40%"><? echo Loc::getMessage("CAT_CLEAR_QUANTITY"); ?>:</td>
		<td width="60%">
			<input type="button" value="<? echo Loc::getMessage("CAT_CLEAR_ACTION"); ?>" id="cat_clear_quantity_btn" onclick="catClearQuantity(this, 'clearQuantity')">
			<?
			if (0 < $clearQuantityUser)
			{
				?><span style="font-size: smaller;"><?=$strQuantityUser;?>&nbsp;<?=$strClearQuantityDate;?></span><?
			}
			?>
		</td>
	</tr>
	<tr>
		<td width="40%"><? echo Loc::getMessage("CAT_CLEAR_RESERVED_QUANTITY"); ?></td>
		<td>
			<input type="button" value="<? echo Loc::getMessage("CAT_CLEAR_ACTION"); ?>" id="cat_clear_reserved_quantity_btn" onclick="catClearQuantity(this, 'clearReservedQuantity')">
			<?
			if (0 < $clearQuantityUser)
			{
				?><span style="font-size: smaller;"><?=$strQuantityReservedUser;?>&nbsp;<?=$strClearQuantityReservedDate;?></span><?
			}
			?>
		</td>
	</tr>
	<tr class="heading">
		<td colspan="2"><? echo Loc::getMessage("CAT_CLEAR_STORE") ?></td>
	</tr>
<?
		if ($boolStoreExists)
		{
?>
	<tr>
		<td><?= Loc::getMessage("CAT_SELECT_CATALOG") ?>:</td>
		<td>
			<select style="max-width: 300px" id="catalogs_store_id" name="catalogs_store_id" <?=($bReadOnly ? ' disabled' : ''); ?>>
				<?foreach($arCatalogList as &$arOneCatalog)
				{
					echo '<option value="'.$arOneCatalog['ID'].'">'.htmlspecialcharsex($arOneCatalog["NAME"]).' ('.$arIBlockSitesList[$arOneCatalog['ID']]['WITHOUT_LINKS'].')</option>';
				}
				unset($arOneCatalog);
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td><?= Loc::getMessage("CAT_SELECT_STORE") ?>:</td>
		<td>
			<select style="max-width: 300px;" id="stores_id" name="stores_id" <?=($bReadOnly ? ' disabled' : ''); ?>>
				<?
				foreach($arStores as $key => $val)
				{
					$store = ($val["TITLE"] != '') ? $val["TITLE"]." (".$val["ADDRESS"].")" : $val["ADDRESS"];
					echo '<option value="'.$val['ID'].'">'.$store.'</option>';
				}
				?>
			</select>

		</td>
	</tr>
	<tr>
		<td><?= Loc::getMessage("CAT_CLEAR_STORE") ?>:</td>
		<td>
			<input type="button" value="<? echo Loc::getMessage("CAT_CLEAR_ACTION"); ?>" id="cat_clear_store_btn" onclick="catClearQuantity(this, 'clearStore')">
			<?
			if (0 < $clearStoreUser)
			{
				?><span style="font-size: smaller;"><?=$strStoreUser;?>&nbsp;<?=$strClearStoreDate;?></span><?
			}
			?>
		</td>
	</tr>
<?
		}
		else
		{
?>
	<tr>
		<td colspan="2"><?= Loc::getMessage("CAT_STORE_LIST_IS_EMPTY"); ?></td>
	</tr>
<?
		}
	}
	$systemTabControl->End();
?>
<script type="text/javascript">
function showDiscountReindex()
{
	var obDiscount, params;

	params = {
		bxpublic: 'Y',
		sessid: BX.bitrix_sessid()
	};

	var obBtn = {
		title: '<? echo CUtil::JSEscape(Loc::getMessage('CAT_POPUP_WINDOW_CLOSE_BTN')) ?>',
		id: 'close',
		name: 'close',
		action: function () {
			this.parentWindow.Close();
		}
	};

	obDiscount = new BX.CAdminDialog({
		'content_url': '/bitrix/admin/cat_discount_convert.php?lang=<? echo LANGUAGE_ID; ?>&format=Y',
		'content_post': params,
		'draggable': true,
		'resizable': true,
		'buttons': [obBtn]
	});
	obDiscount.Show();
	return false;
}
function showSetsAvailableReindex()
{
	var obWindow, params;

	params = {
		bxpublic: 'Y',
		sessid: BX.bitrix_sessid()
	};

	var obBtn = {
		title: '<? echo CUtil::JSEscape(Loc::getMessage('CAT_POPUP_WINDOW_CLOSE_BTN')) ?>',
		id: 'close',
		name: 'close',
		action: function () {
			this.parentWindow.Close();
		}
	};

	obWindow = new BX.CAdminDialog({
		'content_url': '/bitrix/tools/catalog/sets_available.php?lang=<? echo LANGUAGE_ID; ?>',
		'content_post': params,
		'draggable': true,
		'resizable': true,
		'buttons': [obBtn]
	});
	obWindow.Show();
	return false;
}
BX.ready(function(){
	var discountReindex = BX('discount_reindex'),
		setsReindex = BX('sets_reindex');

	if (!!discountReindex)
		BX.bind(discountReindex, 'click', showDiscountReindex);
	if (!!setsReindex)
		BX.bind(setsReindex, 'click', showSetsAvailableReindex);
});
</script>
<?
}