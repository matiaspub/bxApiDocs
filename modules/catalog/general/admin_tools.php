<?
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class CCatalogAdminToolsAll
{
	const TAB_CATALOG = 'P';
	const TAB_SKU = 'O';
	const TAB_SET = 'S';
	const TAB_GROUP = 'G';

	protected static $strMainPrefix = '';
	protected static $arErrors = array();
	protected static $arCheckResult = array();

	public static function getTabList($boolFull = false)
	{
		$boolFull = ($boolFull === true);
		if ($boolFull)
		{
			return array(
				self::TAB_CATALOG => Loc::getMessage('BT_CAT_ADM_TOOLS_TAB_CATALOG'),
				self::TAB_SKU => Loc::getMessage('BT_CAT_ADM_TOOLS_TAB_SKU'),
				self::TAB_SET => Loc::getMessage('BT_CAT_ADM_TOOLS_TAB_SET'),
				self::TAB_GROUP => Loc::getMessage('BT_CAT_ADM_TOOLS_TAB_GROUP')
			);
		}
		return array(
			self::TAB_CATALOG,
			self::TAB_SKU,
			self::TAB_SET,
			self::TAB_GROUP
		);
	}

	public static function getIBlockElementMenu($intIBlockID, &$arCatalog, $arParams)
	{
		$arResult = false;
		$intIBlockID = (int)$intIBlockID;
		if ($intIBlockID <= 0)
			return false;

		if (empty($arCatalog))
			$arCatalog = CCatalogSKU::GetInfoByIBlock($intIBlockID);
		if (empty($arCatalog))
			return false;

		if (empty($arParams) || !is_array($arParams))
			return false;

		$arItems = array();
		$arSubItems = array();

		if ($arCatalog['CATALOG'] == 'Y')
		{
			$arParams[self::$strMainPrefix.'PRODUCT_TYPE'] = self::TAB_CATALOG;
			$arItems[] = array(
				'ICON' => 'btn_new',
				'TEXT' => Loc::getMessage('BT_CAT_ADM_TOOLS_ADD_PROD'),
				'TITLE' => Loc::getMessage('BT_CAT_ADM_TOOLS_ADD_PROD_TITLE'),
				'LINK' => CIBlock::GetAdminElementEditLink($intIBlockID, 0, $arParams),
				'SHOW_TITLE' => true
			);
			if (CCatalogSKU::TYPE_FULL == $arCatalog['CATALOG_TYPE'])
			{
				$arParams[self::$strMainPrefix.'PRODUCT_TYPE'] = self::TAB_SKU;
				$arSubItems[] = array(
					'TEXT' => Loc::getMessage('BT_CAT_ADM_TOOLS_ADD_SKU'),
					'TITLE' => Loc::getMessage('BT_CAT_ADM_TOOLS_ADD_SKU_TITLE'),
					'LINK' => CIBlock::GetAdminElementEditLink($intIBlockID, 0, $arParams),
					'SHOW_TITLE' => true
				);
			}
			if (CBXFeatures::IsFeatureEnabled('CatCompleteSet'))
			{
				if (CCatalogSKU::TYPE_OFFERS != $arCatalog['CATALOG_TYPE'])
				{
					$arParams[self::$strMainPrefix.'PRODUCT_TYPE'] = self::TAB_SET;
					$arSubItems[] = array(
						'TEXT' => Loc::getMessage('BT_CAT_ADM_TOOLS_ADD_SET'),
						'TITLE' => Loc::getMessage('BT_CAT_ADM_TOOLS_ADD_SET_TITLE'),
						'LINK' => CIBlock::GetAdminElementEditLink($intIBlockID, 0, $arParams),
						'SHOW_TITLE' => true
					);
				}
				$arParams[self::$strMainPrefix.'PRODUCT_TYPE'] = self::TAB_GROUP;
				$arSubItems[] = array(
					'TEXT' => Loc::getMessage('BT_CAT_ADM_TOOLS_ADD_GROUP'),
					'TITLE' => Loc::getMessage('BT_CAT_ADM_TOOLS_ADD_GROUP_TITLE'),
					'LINK' => CIBlock::GetAdminElementEditLink($intIBlockID, 0, $arParams),
					'SHOW_TITLE' => true
				);
			}
		}
		else
		{
			$arParams[self::$strMainPrefix.'PRODUCT_TYPE'] = self::TAB_SKU;
			$arItems[] = array(
				'ICON' => 'btn_new',
				'TEXT' => Loc::getMessage('BT_CAT_ADM_TOOLS_ADD_SKU'),
				'TITLE' => Loc::getMessage('BT_CAT_ADM_TOOLS_ADD_SKU_TITLE'),
				'LINK' => CIBlock::GetAdminElementEditLink($intIBlockID, 0, $arParams),
				'SHOW_TITLE' => true
			);
		}
		if (!empty($arSubItems))
		{
			$arItems[] = array(
				'TEXT' => Loc::getMessage('BT_CAT_ADM_TOOLS_ADD_EXT'),
				'TITLE' => Loc::getMessage('BT_CAT_ADM_TOOLS_ADD_EXT_TITLE'),
				'MENU' => $arSubItems
			);
		}
		if (!empty($arItems))
		{
			$arResult = $arItems;
		}

		return $arResult;
	}

	public static function getIBlockElementContentMenu($intIBlockID, $intID, &$arCatalog, $arParams)
	{
		$arResult = false;

		$intIBlockID = (int)$intIBlockID;
		$intID = (int)$intID;
		if ($intIBlockID <= 0 || $intID <= 0)
			return false;
		if (empty($arCatalog))
			$arCatalog = CCatalogSKU::GetInfoByIBlock($intIBlockID);
		if (empty($arCatalog))
			return false;
		if ($arCatalog['CATALOG'] != 'Y')
			return false;

		if (empty($arParams) || !is_array($arParams))
			$arParams = array();

		$boolFeatureSet = CBXFeatures::IsFeatureEnabled('CatCompleteSet');

		$intProductID = CIBlockElement::GetRealElement($intID);

		$strProductType = '';
		if (isset($_REQUEST[self::$strMainPrefix.'PRODUCT_TYPE']))
		{
			$strProductType = (string)$_REQUEST[self::$strMainPrefix.'PRODUCT_TYPE'];
			if ('' != $strProductType && !in_array($strProductType, self::getTabList()))
				$strProductType = '';
		}
		if ('' != $strProductType && !$boolFeatureSet)
		{
			if (self::TAB_SET == $strProductType || self::TAB_GROUP == $strProductType)
				$strProductType = '';
		}

		$boolExistOffers = false;
		if (CCatalogSKU::TYPE_FULL == $arCatalog['CATALOG_TYPE'])
			$boolExistOffers = CCatalogSKU::IsExistOffers($intID, $intIBlockID);
		$boolExistSet = CCatalogProductSet::isProductHaveSet($intProductID, CCatalogProductSet::TYPE_SET);
		$boolExistGroup = CCatalogProductSet::isProductHaveSet($intProductID, CCatalogProductSet::TYPE_GROUP);

		$arItems = array();
		if (CCatalogSKU::TYPE_OFFERS != $arCatalog['CATALOG_TYPE'])
		{
			if (!$boolExistOffers && !$boolExistSet)
			{
				//product
				$arItems[] = array(
					'ICON' => '',
					'TEXT' => Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_CATALOG'),
					'CHECKED' => true
				);
				if (CCatalogSKU::TYPE_FULL == $arCatalog['CATALOG_TYPE'])
				{
					$arNewParams = $arParams;
					$arNewParams[self::$strMainPrefix.'PRODUCT_TYPE'] = self::TAB_SKU;
					$arItems[] = array(
						'ICON' => '',
						'TEXT' => Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_SKU'),
						'LINK' => CIBlock::GetAdminElementEditLink($intIBlockID, $intID, $arNewParams)
					);
				}
				if ($boolFeatureSet)
				{
					$arNewParams = $arParams;
					$arNewParams[self::$strMainPrefix.'PRODUCT_TYPE'] = self::TAB_SET;
					$arItems[] = array(
						'ICON' => '',
						'TEXT' => Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_SET'),
						'LINK' => CIBlock::GetAdminElementEditLink($intIBlockID, $intID, $arNewParams)
					);
				}
			}
			elseif ($boolExistOffers)
			{
				//offers
				$arItems[] = array(
					'ICON' => '',
					'TEXT' => Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_CATALOG'),
					'TITLE' => Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_CATALOG_FROM_SKU'),
					'SHOW_TITLE' => true,
					'DISABLED' => true
				);
				$arItems[] = array(
					'ICON' => '',
					'TEXT' => Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_SKU'),
					'CHECKED' => true
				);
				if ($boolFeatureSet)
				{
					$arItems[] = array(
						'ICON' => '',
						'TEXT' => Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_SET'),
						'TITLE' => Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_SET_FROM_SKU'),
						'SHOW_TITLE' => true,
						'DISABLED' => true,
					);
				}
			}
			elseif ($boolExistSet)
			{
				if ($boolFeatureSet)
				{
					//set
					$arNewParams = $arParams;
					$arNewParams['setdel'] = 'Y';
					$arNewParams['sessid'] = bitrix_sessid();

					$arItems[] = array(
						'ICON' => '',
						'TEXT' => Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_CATALOG'),
						'ACTION' => "if(confirm('".CUtil::JSEscape(Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_SET_DELETE_CONFIRM'))."'))window.location='".CIBlock::GetAdminElementEditLink($intIBlockID, $intID, $arNewParams)."';"
					);
					if (CCatalogSKU::TYPE_FULL == $arCatalog['CATALOG_TYPE'])
					{
						$arNewParams[self::$strMainPrefix.'PRODUCT_TYPE'] = self::TAB_SKU;
						$arItems[] = array(
							'ICON' => '',
							'TEXT' => Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_SKU'),
							'ACTION' => "if(confirm('".CUtil::JSEscape(Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_SET_DELETE_CONFIRM'))."'))window.location='".CIBlock::GetAdminElementEditLink($intIBlockID, $intID, $arNewParams)."';"
						);
					}

					$arItems[] = array(
						'ICON' => '',
						'TEXT' => Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_SET'),
						'CHECKED' => true
					);
				}
			}
		}
		if (!$boolFeatureSet && CCatalogSKU::TYPE_FULL != $arCatalog['CATALOG_TYPE'])
		{
			$arItems = array();
		}
		//group
		if ($boolFeatureSet && self::TAB_GROUP != $strProductType)
		{
			$arItems[] = array('SEPARATOR' => 'Y');
			if (!$boolExistGroup)
			{
				$arNewParams = $arParams;
				$arNewParams[self::$strMainPrefix.'PRODUCT_TYPE'] = self::TAB_GROUP;
				$arItems[] = array(
					'ICON' => '',
					'TEXT' => Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_GROUP_ADD'),
					'LINK' => CIBlock::GetAdminElementEditLink($intIBlockID, $intID, $arNewParams)
				);
			}
			else
			{
				$arNewParams = $arParams;
				$arNewParams['groupdel'] = 'Y';
				$arNewParams['sessid'] = bitrix_sessid();
				$arItems[] = array(
					'ICON' => 'delete',
					'TEXT' => Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_GROUP_DELETE'),
					'ACTION' => "if(confirm('".CUtil::JSEscape(Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_GROUP_DELETE_CONFIRM'))."'))window.location='".CIBlock::GetAdminElementEditLink($intIBlockID, $intID, $arNewParams)."';"
				);
			}
		}

		if (!empty($arItems))
		{
			$arResult = array(
				'TEXT' => Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_SELECTOR'),
				'TITLE' => Loc::getMessage('BT_CAT_SET_PRODUCT_TYPE_SELECTOR_TITLE'),
				'MENU' => $arItems
			);
		}
		return $arResult;
	}

	public static function getShowTabs($intIBlockID, $intID, &$arCatalog)
	{
		$intIBlockID = (int)$intIBlockID;
		if ($intIBlockID <= 0)
			return false;

		if (empty($arCatalog))
			$arCatalog = CCatalogSKU::GetInfoByIBlock($intIBlockID);
		if (empty($arCatalog))
			return false;

		$boolFeatureSet = CBXFeatures::IsFeatureEnabled('CatCompleteSet');

		$arResult = array_fill_keys(self::getTabList(false), false);
		$strProductType = '';
		if (isset($_REQUEST[self::$strMainPrefix.'PRODUCT_TYPE']))
		{
			$strProductType = (string)$_REQUEST[self::$strMainPrefix.'PRODUCT_TYPE'];
			if ('' != $strProductType && !isset($arResult[$strProductType]))
				$strProductType = '';
		}
		if ('' != $strProductType && !$boolFeatureSet)
		{
			if (self::TAB_SET == $strProductType || self::TAB_GROUP == $strProductType)
				$strProductType = '';
		}

		if (0 < $intID)
		{
			$intProductID = CIBlockElement::GetRealElement($intID);
			$arResult[self::TAB_CATALOG] = (
				CCatalogSKU::TYPE_CATALOG == $arCatalog['CATALOG_TYPE']
				|| CCatalogSKU::TYPE_FULL == $arCatalog['CATALOG_TYPE']
				|| CCatalogSKU::TYPE_OFFERS == $arCatalog['CATALOG_TYPE']
			);
			$arResult[self::TAB_SKU] = (
				CCatalogSKU::TYPE_PRODUCT == $arCatalog['CATALOG_TYPE']
				|| CCatalogSKU::TYPE_FULL == $arCatalog['CATALOG_TYPE']
			);
			if (CCatalogSKU::TYPE_FULL == $arCatalog['CATALOG_TYPE'])
			{
				if (CCatalogSKU::IsExistOffers($intID, $intIBlockID)
					|| (CCatalogSKU::TYPE_FULL == $arCatalog['CATALOG_TYPE'] && self::TAB_SKU == $strProductType)
				)
				{
					if ('Y' != COption::GetOptionString('catalog', 'show_catalog_tab_with_offers'))
						$arResult[self::TAB_CATALOG] = false;
				}
				else
				{
					$arResult[self::TAB_SKU] = false;
				}
			}
			if (CCatalogSKU::TYPE_PRODUCT != $arCatalog['CATALOG_TYPE'])
			{
				if ($boolFeatureSet)
				{
					if (CCatalogSKU::TYPE_OFFERS != $arCatalog['CATALOG_TYPE'])
					{
						$arResult[self::TAB_SET] = (
							CCatalogProductSet::isProductHaveSet($intProductID, CCatalogProductSet::TYPE_SET)
							|| self::TAB_SET == $strProductType
						);
					}
					$arResult[self::TAB_GROUP] = (
						CCatalogProductSet::isProductHaveSet($intProductID, CCatalogProductSet::TYPE_GROUP)
						|| self::TAB_GROUP == $strProductType
					);
					if ($arResult[self::TAB_SET])
					{
						$arResult[self::TAB_CATALOG] = true;
						$arResult[self::TAB_SKU] = false;
					}
				}
			}
		}
		else
		{
			if ('' != $strProductType)
			{
				if (CCatalogSKU::TYPE_OFFERS == $arCatalog['CATALOG_TYPE'])
				{
					if (self::TAB_SET == $strProductType || self::TAB_SKU == $strProductType)
						$strProductType = '';
				}
			}
			if ('' != $strProductType)
			{
				$arResult[$strProductType] = true;
				if (self::TAB_GROUP == $strProductType || self::TAB_SET == $strProductType)
					$arResult[self::TAB_CATALOG] = true;
				if (
					self::TAB_SKU == $strProductType
					&& 'Y' == $arCatalog['CATALOG']
					&& 'Y' == COption::GetOptionString('catalog', 'show_catalog_tab_with_offers')
				)
					$arResult[self::TAB_CATALOG] = true;
			}
			else
			{
				$arResult[self::TAB_CATALOG] = (
					CCatalogSKU::TYPE_CATALOG == $arCatalog['CATALOG_TYPE']
					|| CCatalogSKU::TYPE_FULL == $arCatalog['CATALOG_TYPE']
					|| CCatalogSKU::TYPE_OFFERS == $arCatalog['CATALOG_TYPE']
				);
				$arResult[self::TAB_SKU] = (
					CCatalogSKU::TYPE_PRODUCT == $arCatalog['CATALOG_TYPE']
					|| CCatalogSKU::TYPE_FULL == $arCatalog['CATALOG_TYPE']
				);
			}
		}
		return $arResult;
	}

	public static function showFormParams()
	{
		$boolFeatureSet = CBXFeatures::IsFeatureEnabled('CatCompleteSet');
		if (isset($_REQUEST[self::$strMainPrefix.'PRODUCT_TYPE']))
		{
			$strProductType = (string)$_REQUEST[self::$strMainPrefix.'PRODUCT_TYPE'];
			if ('' != $strProductType && !in_array($strProductType, self::getTabList(false)))
				$strProductType = '';
		}
		if ('' != $strProductType && !$boolFeatureSet)
		{
			if (self::TAB_SET == $strProductType || self::TAB_GROUP == $strProductType)
				$strProductType = '';
		}
		if ('' != $strProductType)
		{
			?><input type="hidden" name="<? echo self::$strMainPrefix; ?>PRODUCT_TYPE" value="<? echo $strProductType; ?>"><?
		}
	}

	public static function setMainPrefix($strPrefix)
	{
		self::$strMainPrefix = (string)$strPrefix;
	}

	public static function getMainPrefix()
	{
		return self::$strMainPrefix;
	}

	public static function setProductFormParams()
	{
		self::setMainPrefix('');
	}

	public static function setSkuFormParams()
	{
		self::setMainPrefix('SUB');
	}

	public static function getErrors()
	{
		return self::$arErrors;
	}

	public static function changeTabs($intIBlockID, $intID, &$arCatalog)
	{
		$result = false;
		$intIBlockID = (int)$intIBlockID;
		$intID = (int)$intID;
		if ($intIBlockID <= 0 || $intID <= 0)
			return $result;

		if (empty($arCatalog))
			$arCatalog = CCatalogSKU::GetInfoByIBlock($intIBlockID);
		if (empty($arCatalog))
			return $result;
		if ($arCatalog['CATALOG'] != 'Y')
			return $result;

		$intProductID = CIBlockElement::GetRealElement($intID);

		$boolFeatureSet = CBXFeatures::IsFeatureEnabled('CatCompleteSet');

		if ($boolFeatureSet)
		{
			if (isset($_REQUEST['groupdel']) && 'Y' == $_REQUEST['groupdel'])
			{
				$result = CCatalogProductSet::deleteAllSetsByProduct($intProductID, CCatalogProductSet::TYPE_GROUP);
			}
			elseif (isset($_REQUEST['setdel']) && 'Y' == $_REQUEST['setdel'])
			{
				$result = CCatalogProductSet::deleteAllSetsByProduct($intProductID, CCatalogProductSet::TYPE_SET);
			}
		}
		return $result;
	}

	public static function addTabParams(&$arParams)
	{
		if (!is_array($arParams))
			return;
		$boolFeatureSet = CBXFeatures::IsFeatureEnabled('CatCompleteSet');
		if (isset($_REQUEST[self::$strMainPrefix.'PRODUCT_TYPE']))
		{
			$strProductType = (string)$_REQUEST[self::$strMainPrefix.'PRODUCT_TYPE'];
			if ('' != $strProductType && !in_array($strProductType, self::getTabList(false)))
				$strProductType = '';
		}
		if ('' != $strProductType && !$boolFeatureSet)
		{
			if (self::TAB_SET == $strProductType || self::TAB_GROUP == $strProductType)
				$strProductType = '';
		}
		if ('' != $strProductType)
		{
			$arParams[self::$strMainPrefix.'PRODUCT_TYPE'] = $strProductType;
		}
	}

	public static function clearTabParams()
	{
		if (array_key_exists(self::$strMainPrefix.'PRODUCT_TYPE', $_REQUEST))
			unset($_REQUEST[self::$strMainPrefix.'PRODUCT_TYPE']);
	}
}

class CCatalogAdminProductSetEdit
{
	const NEW_ITEM_COUNT = 3;
	const PREFIX_SET = 'PRODUCT_SET';
	const PREFIX_GROUP = 'PRODUCT_GROUP';

	protected static $strMainPrefix = '';
	protected static $intTypeID = 0;
	protected static $arErrors = array();
	protected static $arSrcValues = array();
	protected static $arCheckValues = array();

	public static function setMainPrefix($strPrefix)
	{
		self::$strMainPrefix = (string)$strPrefix;
	}

	public static function getMainPrefix()
	{
		return self::$strMainPrefix;
	}

	public static function setTypeID($intTypeID)
	{
		$intTypeID = (int)$intTypeID;
		if (CCatalogProductSet::TYPE_SET != $intTypeID && CCatalogProductSet::TYPE_GROUP != $intTypeID)
			return;
		self::$intTypeID = $intTypeID;
	}

	public static function getTypeID()
	{
		return self::$intTypeID;
	}

	public static function setProductFormParams($arParams)
	{
		if (empty($arParams) || !is_array($arParams))
			return;
		if (!isset($arParams['TYPE']))
			return;
		$intTypeID = (int)$arParams['TYPE'];
		if (CCatalogProductSet::TYPE_SET != $intTypeID && CCatalogProductSet::TYPE_GROUP != $intTypeID)
			return;
		self::$intTypeID = $intTypeID;
		$strPrefix = (CCatalogProductSet::TYPE_SET == $intTypeID ? self::PREFIX_SET : self::PREFIX_GROUP);

		self::setMainPrefix($strPrefix);
	}

	public static function setSkuFormParams($arParams)
	{
		if (empty($arParams) || !is_array($arParams))
			return;
		if (!isset($arParams['TYPE']))
			return;
		$intTypeID = (int)$arParams['TYPE'];
		if (CCatalogProductSet::TYPE_SET != $intTypeID && CCatalogProductSet::TYPE_GROUP != $intTypeID)
			return;
		self::$intTypeID = $intTypeID;
		$strPrefix = 'SUB'.(CCatalogProductSet::TYPE_SET == $intTypeID ? self::PREFIX_SET : self::PREFIX_GROUP);

		self::setMainPrefix($strPrefix);
	}

	public static function getEmptySet($intProductID)
	{
		$arResult = false;
		if (CCatalogProductSet::TYPE_SET == self::$intTypeID || CCatalogProductSet::TYPE_GROUP == self::$intTypeID)
		{
			$arResult = array(
				'n0' => array(
					'ITEM_ID' => $intProductID,
					'ACTIVE' => 'Y',
					'SORT' => '100',
					'ITEMS' => self::getEmptyItem(0),
					'NEW_ITEM_COUNT' => self::NEW_ITEM_COUNT
				)
			);
		}
		return $arResult;
	}

	public static function getEmptyItem($arParams)
	{
		$arResult = array();
		if (CCatalogProductSet::TYPE_SET != self::$intTypeID && CCatalogProductSet::TYPE_GROUP != self::$intTypeID)
			return $arResult;
		if (!is_array($arParams))
			$arParams = array('nStart' => $arParams);
		if (!isset($arParams['nStart']))
			$arParams['nStart'] = 0;
		$arParams['nStart'] = (int)$arParams['nStart'];
		switch(self::$intTypeID)
		{
			case CCatalogProductSet::TYPE_SET:
				for ($i = $arParams['nStart']; $i < ($arParams['nStart'] + self::NEW_ITEM_COUNT); $i++)
				{
					$arResult['n'.$i] = array(
						'ITEM_ID' => '',
						'QUANTITY' => '',
						'DISCOUNT_PERCENT' => '',
						'SORT' => 100
					);
				}
				break;
			case CCatalogProductSet::TYPE_GROUP:
				for ($i = $arParams['nStart']; $i < ($arParams['nStart'] + self::NEW_ITEM_COUNT); $i++)
				{
					$arResult['n'.$i] = array(
						'ITEM_ID' => '',
						'QUANTITY' => '',
						'SORT' => 100
					);
				}
				break;
			default:
				break;
		}
		return $arResult;
	}

	public static function getFormValues(&$arSets)
	{
		if (CCatalogProductSet::TYPE_SET != self::$intTypeID && CCatalogProductSet::TYPE_GROUP != self::$intTypeID)
			return;
		if (empty($arSets) || !is_array($arSets))
			return;

		$boolFeatureSet = CBXFeatures::IsFeatureEnabled('CatCompleteSet');
		if (!$boolFeatureSet)
			return;

		if (!isset(self::$arSrcValues[self::$strMainPrefix]) || empty(self::$arSrcValues[self::$strMainPrefix]))
			return;

		$arSets = self::$arSrcValues[self::$strMainPrefix];
	}

	public static function addEmptyValues(&$arSets)
	{

	}

	public static function getItemsInfo(&$arSets)
	{
		$arItemList = array();
		if (empty($arSets) || !is_array($arSets))
			return;
		foreach ($arSets as $key => $arOneSet)
		{
			foreach ($arOneSet['ITEMS'] as $keyItem => $arItem)
			{
				if ('' == $arItem['ITEM_ID'])
					continue;
				$intItemID = (int)$arItem['ITEM_ID'];
				if (0 >= $intItemID)
					continue;
				if (!isset($arItemList[$intItemID]))
					$arItemList[$intItemID] = array();
				$arItemList[$intItemID][] = array(
					'SET' => $key,
					'ITEM' => $keyItem
				);
			}
		}
		if (!empty($arItemList))
		{
			$arFilter = array(
				'ID' => array_keys($arItemList)
			);
			$rsProducts = CIBlockElement::GetList(
				array(),
				$arFilter,
				false,
				false,
				array('ID', 'NAME')
			);
			while ($arProduct = $rsProducts->Fetch())
			{
				$arProduct['ID'] = (int)$arProduct['ID'];
				if (isset($arItemList[$arProduct['ID']]))
				{
					foreach ($arItemList[$arProduct['ID']] as $arOneKey)
					{
						$arSets[$arOneKey['SET']]['ITEMS'][$arOneKey['ITEM']]['ITEM_NAME'] = $arProduct['NAME'];
					}
				}
			}
		}
	}

	public static function clearOwnerSet(&$arSets)
	{
		if (empty($arSets) || !is_array($arSets))
			return;
		$index = 0;
		$result = array();
		foreach ($arSets as $oneSet)
		{
			$itemIndex = 0;
			$items = array();
			foreach ($oneSet['ITEMS'] as $oneItem)
			{
				$items['n'.$itemIndex] = $oneItem;
				$itemIndex++;
			}
			$result['n'.$index] = array(
				'ITEM_ID' => '',
				'ACTIVE' => $oneSet['ACTIVE'],
				'SORT' => $oneSet['SORT'],
				'ITEMS' => $items,
				'NEW_ITEM_COUNT' => $itemIndex
			);
			$index++;
		}
		unset($oneSet);
		$arSets = $result;
	}

	public static function showEditForm($arSets)
	{
		global $APPLICATION;

		if (CCatalogProductSet::TYPE_SET != self::$intTypeID && CCatalogProductSet::TYPE_GROUP != self::$intTypeID)
			return;
		if (empty($arSets) || !is_array($arSets))
			return;

		$boolFeatureSet = CBXFeatures::IsFeatureEnabled('CatCompleteSet');
		if (!$boolFeatureSet)
			return;

		$APPLICATION->AddHeadScript('/bitrix/js/catalog/tbl_edit.js');

		self::getItemsInfo($arSets);

		foreach ($arSets as $key => $arOneSet)
		{
			$strNamePrefix = self::$strMainPrefix.'['.$key.']';
			$strIDPrefix = self::$strMainPrefix.'_'.$key;
			?><table id="<? echo $strIDPrefix; ?>_TBL" class="internal" style="margin: 0 auto;">
			<tr class="heading">
			<td class="align-left"><? echo Loc::getMessage('BT_CAT_SET_ITEM_NAME'); ?></td>
			<td class="align-right"><? echo Loc::getMessage('BT_CAT_SET_ITEM_QUANTITY'); ?></td><?
			if (CCatalogProductSet::TYPE_SET == self::$intTypeID)
			{
				?><td class="align-right"><? echo Loc::getMessage('BT_CAT_SET_ITEM_DISCOUNT_PERCENT_EXT'); ?></td><?
			}
			?>
			<td class="align-right"><? echo Loc::getMessage('BT_CAT_SET_ITEM_SORT'); ?></td><?
			if (0 < (int)$key)
			{
				?><td><? echo (
				CCatalogProductSet::TYPE_SET == self::$intTypeID
				? Loc::getMessage('BT_CAT_SET_ITEM_DEL_FROM_SET')
				: Loc::getMessage('BT_CAT_SET_ITEM_DEL_FROM_GROUP')); ?></td><?
			}
			?></tr><?
			foreach ($arOneSet['ITEMS'] as $keyItem => $arOneItem)
			{
				$arItemParams = array(
					'SET_KEY' => $key,
					'KEY' => $keyItem,
					'NAME_PREFIX' => $strNamePrefix.'[ITEMS]['.$keyItem.']',
					'ID_PREFIX' => $strIDPrefix.'_ITEMS_'.$keyItem
				);
				self::showItemRow($arOneItem, $arItemParams);
			}
			?>
			</table>
			<div style="margin: 10px auto; text-align: center;">
			<input class="adm-btn-big" type="button" id="<? echo $strIDPrefix; ?>_ITEMS_ADD" value="<? echo Loc::getMessage('BT_CAT_SET_ITEM_ADD'); ?>" title="<? echo Loc::getMessage('BT_CAT_SET_ITEM_ADD_TITLE'); ?>">
			</div>
			<input type="hidden" id="<? echo $strIDPrefix; ?>_ITEMS_CNT" value="<? echo (int)$arOneSet['NEW_ITEM_COUNT']; ?>"><?
			$arNewParams = array(
				'SET_KEY' => $key,
				'KEY' => 'tmp_xxx',
				'NAME_PREFIX' => $strNamePrefix.'[ITEMS][ntmp_xxx]',
				'ID_PREFIX' => $strIDPrefix.'_ITEMS_ntmp_xxx'
			);

			$arCellInfo = self::getJSRow($arNewParams);

			$arJSParams = array(
				'PREFIX' => $strIDPrefix.'_ITEMS_',
				'PREFIX_NAME' => $strNamePrefix.'[ITEMS]',
				'TABLE_PROP_ID' =>  $strIDPrefix.'_TBL',
				'PROP_COUNT_ID' =>  $strIDPrefix.'_ITEMS_CNT',
				'BTN_ID' => $strIDPrefix.'_ITEMS_ADD',
				'CELLS' => $arCellInfo['CELLS'],
				'CELL_PARAMS' => $arCellInfo['CELL_PARAMS']
			)
			?>
<script type="text/javascript">
var ob<? echo self::$strMainPrefix; ?> = new JCCatTblEditExt(<? echo CUtil::PhpToJSObject($arJSParams); ?>);
</script>
			<?
			break;
		}
	}

	public static function showItemRow($arRow, $arParams)
	{
		if (CCatalogProductSet::TYPE_SET != self::$intTypeID && CCatalogProductSet::TYPE_GROUP != self::$intTypeID)
			return;
		$strNamePrefix = $arParams['NAME_PREFIX'];
		$strIDPrefix = $arParams['ID_PREFIX'];
		$strKey = $arParams['KEY'];
		?><tr>
		<td class="align-left">
			<input name="<? echo $strNamePrefix; ?>[ITEM_ID]" id="<? echo $strIDPrefix; ?>_ITEM_ID" value="<? echo htmlspecialcharsbx($arRow['ITEM_ID']); ?>" size="5" type="text">
			<input type="button" value="..." onclick="jsUtils.OpenWindow('/bitrix/admin/iblock_element_search.php?lang=<? echo LANGUAGE_ID; ?>&n=<? echo $strIDPrefix; ?>_ITEM_ID&discount=Y', 900, 600);">
			&nbsp;<span id="<? echo $strIDPrefix; ?>_ITEM_ID_link"><? echo htmlspecialcharsex($arRow['ITEM_NAME']); ?></span>
		</td>
		<td class="align-right">
			<input type="text" size="5" name="<? echo $strNamePrefix; ?>[QUANTITY]" id="<? echo $strIDPrefix; ?>_QUANTITY" value="<? echo htmlspecialcharsbx($arRow['QUANTITY']) ?>">
		</td><?
		if (CCatalogProductSet::TYPE_SET == self::$intTypeID)
		{
		?><td class="align-right">
			<input type="text" size="3" name="<? echo $strNamePrefix; ?>[DISCOUNT_PERCENT]" id="<? echo $strIDPrefix; ?>_DISCOUNT_PERCENT" value="<? echo htmlspecialcharsbx($arRow['DISCOUNT_PERCENT']) ?>">
		</td><?
		}
		?>
		<td class="align-right">
			<input type="text" size="3" name="<? echo $strNamePrefix; ?>[SORT]" id="<? echo $strIDPrefix; ?>_SORT" value="<? echo htmlspecialcharsbx($arRow['SORT']) ?>">
		</td>
		<?
		if (0 < (int)$arParams['SET_KEY'])
		{
		?><td>
			<input type="hidden" name="<? echo $strNamePrefix; ?>[DEL]" id="<? echo $strIDPrefix; ?>_DEL_N" value="N">
			<?
			if (0 < (int)$strKey)
			{
				?><input type="checkbox" name="<? echo $strNamePrefix; ?>[DEL]" id="<? echo $strIDPrefix; ?>_DEL" value="Y"><?
			}
			else
			{
				?>&nbsp;<?
			}
		?></td><?
		}
		?>
		</tr><?
	}

	protected static function getJSRow($arParams)
	{
		if (CCatalogProductSet::TYPE_SET != self::$intTypeID && CCatalogProductSet::TYPE_GROUP != self::$intTypeID)
			return '';
		$strNamePrefix = $arParams['NAME_PREFIX'];
		$strIDPrefix = $arParams['ID_PREFIX'];
		$strKey = $arParams['KEY'];

		$arCells = array();
		$arCellParams = array();
		$arCells[] = '<input name="'.$strNamePrefix.'[ITEM_ID]" id="'.$strIDPrefix.'_ITEM_ID" value="" size="5" type="text">'.
			' <input type="button" value="..." onclick="jsUtils.OpenWindow(\'/bitrix/admin/iblock_element_search.php?lang='.LANGUAGE_ID.'&n='.$strIDPrefix.'_ITEM_ID&discount=Y\', 900, 600);">'.
			'&nbsp;<span id="'.$strIDPrefix.'_ITEM_ID_link"></span>';
		$arCellParams[] = array(
			'attrs' => array(
				'className' => 'align-left'
			)
		);
		$arCells[] = '<input type="text" size="5" name="'.$strNamePrefix.'[QUANTITY]" id="'.$strIDPrefix.'_QUANTITY" value="">';
		$arCellParams[] = array(
			'attrs' => array(
				'className' => 'align-right'
			)
		);
		if (CCatalogProductSet::TYPE_SET == self::$intTypeID)
		{
			$arCells[] = '<input type="text" size="3" name="'.$strNamePrefix.'[DISCOUNT_PERCENT]" id="'.$strIDPrefix.'_DISCOUNT_PERCENT" value="">';
			$arCellParams[] = array(
				'attrs' => array(
					'className' => 'align-right'
				)
			);
		}
		$arCells[] = '<input type="text" size="3" name="'.$strNamePrefix.'[SORT]" id="'.$strIDPrefix.'_SORT" value="100">';
		$arCellParams[] = array(
				'attrs' => array(
					'className' => 'align-right'
				)
			);
		if (0 < (int)$arParams['SET_KEY'])
		{
			$arCells[] = '<input type="hidden" name="'.$strNamePrefix.'[DEL]" id="'.$strIDPrefix.'_DEL_N" value="N">'.
			(0 < (int)$strKey
				? '<input type="checkbox" name="'.$strNamePrefix.'[DEL]" id="'.$strIDPrefix.'_DEL" value="Y">'
				: '&nbsp;'
			);
			$arCellParams[] = '';
		}

		return array(
			'CELLS' => $arCells,
			'CELL_PARAMS' => $arCellParams
		);
	}

	public static function checkFormValues($arItem)
	{
		self::$arErrors = array();

		$boolFeatureSet = CBXFeatures::IsFeatureEnabled('CatCompleteSet');
		if (!$boolFeatureSet)
			return true;

		self::$arSrcValues[self::$strMainPrefix] = array();
		self::$arCheckValues[self::$strMainPrefix] = array();

		if (isset($_POST[self::$strMainPrefix]) && is_array($_POST[self::$strMainPrefix]))
		{
			CCatalogProductSet::disableShowErrors();
			self::$arSrcValues[self::$strMainPrefix] = $_POST[self::$strMainPrefix];

			foreach (self::$arSrcValues[self::$strMainPrefix] as $key => $arOneSet)
			{
				$boolNew = (0 >= (int)$key);
				$arSaveSet = array(
					'TYPE' => self::$intTypeID,
					'ITEM_ID' => $arItem['PRODUCT_ID'],
					'ACTIVE' => 'Y',
					'ITEMS' => array()
				);

				if (CCatalogProductSet::TYPE_SET == self::$intTypeID)
				{
					foreach ($arOneSet['ITEMS'] as $keyItem => $arOneItem)
					{
						if ('Y' == $arOneItem['DEL'])
							continue;
						$arOneItem['DISCOUNT_PERCENT'] = trim($arOneItem['DISCOUNT_PERCENT']);
						$arSaveItem = array(
							'ITEM_ID' => $arOneItem['ITEM_ID'],
							'QUANTITY' => $arOneItem['QUANTITY'],
							'DISCOUNT_PERCENT' => ('' == $arOneItem['DISCOUNT_PERCENT'] ? false : $arOneItem['DISCOUNT_PERCENT']),
							'SORT' => $arOneItem['SORT']
						);
						$arSaveSet['ITEMS'][] = $arSaveItem;
					}
				}
				else
				{
					foreach ($arOneSet['ITEMS'] as $keyItem => $arOneItem)
					{
						if ('Y' == $arOneItem['DEL'])
							continue;
						$arSaveItem = array(
							'ITEM_ID' => $arOneItem['ITEM_ID'],
							'QUANTITY' => $arOneItem['QUANTITY'],
							'SORT' => $arOneItem['SORT']
						);
						if ($arSaveItem['QUANTITY'] == '')
							$arSaveItem['QUANTITY'] = 1;
						$arSaveSet['ITEMS'][] = $arSaveItem;
					}
				}
				$arTestSet = $arSaveSet;
				$boolCheck = (
					$boolNew
					? CCatalogProductSet::checkFields('TEST', $arTestSet, 0)
					: CCatalogProductSet::checkFields('UPDATE', $arTestSet, $key)
				);
				if (!$boolCheck)
				{
					$ex = new CAdminException(CCatalogProductSet::getErrors());
					self::$arErrors[$key] = $ex->GetString();
				}
				else
				{
					self::$arCheckValues[self::$strMainPrefix][$key] = $arSaveSet;
				}
				break;
			}

			CCatalogProductSet::enableShowErrors();
			return (empty(self::$arErrors));
		}
		return true;
	}

	public static function saveFormValues($arItem)
	{
		$boolFeatureSet = CBXFeatures::IsFeatureEnabled('CatCompleteSet');
		if (!$boolFeatureSet)
			return;

		if (0 >= $arItem['PRODUCT_ID'])
			return;

		if (!empty(self::$arCheckValues[self::$strMainPrefix]))
		{
			foreach (self::$arCheckValues[self::$strMainPrefix] as $key => $arSaveSet)
			{
				if (0 >= $arSaveSet['ITEM_ID'])
					$arSaveSet['ITEM_ID'] = $arItem['PRODUCT_ID'];
				$boolNew = (0 >= (int)$key);
				if ($boolNew)
				{
					CCatalogProductSet::add($arSaveSet);
				}
				else
				{
					CCatalogProductSet::update($key, $arSaveSet);
				}
			}
		}
	}

	public static function getErrors()
	{
		return self::$arErrors;
	}
}
?>