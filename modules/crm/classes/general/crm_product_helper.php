<?php
if (!CModule::IncludeModule('iblock'))
{
	return false;
}

class CCrmProductHelper
{
	public static function PreparePopupItems($currencyID = '', $count = 50, $enableRawPrices = false)
	{
		$currencyID = strval($currencyID);
		if(!isset($currencyID[0]))
		{
			$currencyID = CCrmCurrency::GetBaseCurrencyID();
		}

		$count = intval($count);
		if($count <= 0)
		{
			$count = 50;
		}

		$arSelect = array('ID', 'NAME', 'PRICE', 'CURRENCY_ID');
		$arPricesSelect = $arVatsSelect = array();
		$arSelect = CCrmProduct::DistributeProductSelect($arSelect, $arPricesSelect, $arVatsSelect);
		$rs = CCrmProduct::GetList(
			array('ID' => 'DESC'),
			array(
				'ACTIVE' => 'Y',
				'CATALOG_ID' => CCrmCatalog::EnsureDefaultExists()
			),
			$arSelect,
			$count
		);

		$arProducts = array();
		$arProductId = array();
		while ($product = $rs->Fetch())
		{
			foreach ($arPricesSelect as $fieldName)
			{
				$product[$fieldName] = null;
			}
			foreach ($arVatsSelect as $fieldName)
			{
				$product[$fieldName] = null;
			}
			$arProductId[] = $product['ID'];
			$arProducts[$product['ID']] = $product;
		}
		CCrmProduct::ObtainPricesVats($arProducts, $arProductId, $arPricesSelect, $arVatsSelect, $enableRawPrices);
		$measureInfos = \Bitrix\Crm\Measure::getProductMeasures($arProductId);
		$productVatInfos = CCrmProduct::PrepareCatalogProductFields($arProductId);
		unset($arProductId, $arPricesSelect, $arVatsSelect);
		$defaultMeasureInfo = \Bitrix\Crm\Measure::getDefaultMeasure();

		$result = array();
		foreach ($arProducts as $productID => &$product)
		{
			if($currencyID != $product['CURRENCY_ID'])
			{
				$product['PRICE'] = CCrmCurrency::ConvertMoney($product['PRICE'], $product['CURRENCY_ID'], $currencyID);
				$product['CURRENCY_ID'] = $currencyID;
			}

			$customData = array('price' => $product['PRICE']);
			if(isset($measureInfos[$productID]) && !empty($measureInfos[$productID]))
			{
				$measureIfo = $measureInfos[$productID][0];
				$customData['measure'] = array(
					'code' => $measureIfo['CODE'],
					'name' => $measureIfo['SYMBOL']
				);
			}
			elseif($defaultMeasureInfo !== null)
			{
				$customData['measure'] = array(
					'code' => $defaultMeasureInfo['CODE'],
					'name' => $defaultMeasureInfo['SYMBOL']
				);
			}

			if(isset($productVatInfos[$productID]))
			{
				$productVatInfo = $productVatInfos[$productID];
				$customData['tax'] = array(
					'id' => $productVatInfo['TAX_ID'],
					'included' => $enableRawPrices && $productVatInfo['TAX_INCLUDED']
				);
			}

			$result[] = array(
				'title' => $product['NAME'],
				'desc' => CCrmProduct::FormatPrice($product),
				'id' => $product['ID'],
				'url' => '',
				'type'  => 'product',
				'selected' => false,
				'customData' => &$customData
			);
			unset($customData);
		}
		unset($product, $arProducts);

		return $result;
	}

	public static function PrepareCatalogListItems($addNotSelected = true)
	{
		IncludeModuleLangFile(__FILE__);

		$result = array();
		if($addNotSelected)
		{
			$result['0'] = GetMessage('CRM_PRODUCT_CATALOG_NOT_SELECTED');
		}

		$rs = CCrmCatalog::GetList(
			array('NAME' => 'ASC'),
			array(),
			array('ID', 'NAME')
		);

		while ($ar = $rs->Fetch())
		{
			$result[$ar['ID']] = $ar['NAME'];
		}

		return $result;
	}

	public static function PrepareListItems($catalogID = 0)
	{
		$catalogID = intval($catalogID);
		$result = array();
		$filter = array('ACTIVE' => 'Y');
		if($catalogID > 0)
		{
			$filter['CATALOG_ID'] = $catalogID;
		}

		$rs = CCrmProduct::GetList(
			array('SORT' => 'ASC', 'NAME' => 'ASC'),
			$filter,
			array('ID', 'NAME')
		);

		while ($ar = $rs->Fetch())
		{
			$result[$ar['ID']] = $ar['NAME'];
		}

		return $result;
	}

	public static function PrepareSectionListItems($catalogID, $addNotSelected = true)
	{
		IncludeModuleLangFile(__FILE__);

		$result = array();

		if($addNotSelected)
		{
			$result['0'] = GetMessage('CRM_PRODUCT_SECTION_NOT_SELECTED');
		}

		$rs = CIBlockSection::GetList(
			array('left_margin' => 'asc'),
			array(
				'IBLOCK_ID' => $catalogID,
				'GLOBAL_ACTIVE' => 'Y',
				'CHECK_PERMISSIONS' => 'N'
			),
			false,
			array(
				'ID',
				'NAME',
				'DEPTH_LEVEL'
			)
		);

		while($ary = $rs->GetNext())
		{
			$result[$ary['ID']] = str_repeat(' . ', $ary['DEPTH_LEVEL']).$ary['~NAME'];
		}

		return $result;
	}

	/**
	 * @param string $fieldName
	 * @param array $visibleFields
	 * @return bool
	 */
	public static function IsFieldVisible ($fieldName, $visibleFields)
	{
		if (!is_string($fieldName) || empty($fieldName))
			return false;

		if (!is_array($visibleFields) || empty($visibleFields))
			return true;

		if (in_array($fieldName, $visibleFields, true))
			return true;

		return false;
	}
}
