<?php
if (!CModule::IncludeModule('iblock') || !CModule::IncludeModule('catalog'))
{
	return false;
}

IncludeModuleLangFile(__FILE__);

/*
 * CRM Product.
 * It is based on IBlock module.
 * */
class CCrmProduct
{
	const CACHE_NAME = 'CRM_CATALOG_PRODUCT_CACHE';
	const TABLE_ALIAS = 'P';
	protected static $LAST_ERROR = '';
	protected static $FIELD_INFOS = null;
	private static $defaultCatalogId = null;
	private static $selectedPriceTypeId = null;
	private static $bVatMode = null;
	private static $arVatRates = array();

	public static function getDefaultCatalogId()
	{
		if (is_null(CCrmProduct::$defaultCatalogId))
			self::$defaultCatalogId = CCrmCatalog::EnsureDefaultExists();
		return self::$defaultCatalogId;
	}

	public static function getSelectedPriceTypeId()
	{
		if (is_null(self::$selectedPriceTypeId))
		{
			$priceTypeId = intval(COption::GetOptionInt('crm', 'selected_catalog_group_id', 0));
			if ($priceTypeId < 1)
			{
				$arBaseCatalogGroup = CCatalogGroup::GetBaseGroup();
				$priceTypeId = intval($arBaseCatalogGroup['ID']);
			}
			self::$selectedPriceTypeId = $priceTypeId;
		}
		return self::$selectedPriceTypeId;
	}

	public static function getPrice($productID, $priceTypeId = false)
	{
		$productID = intval($productID);
		if (0 >= $productID)
			return false;

		if ($priceTypeId === false)
			$priceTypeId = self::getSelectedPriceTypeId();
		if (intval($priceTypeId) < 1)
			return false;

		$arFilter = array(
			'PRODUCT_ID' => $productID,
			'CATALOG_GROUP_ID' => $priceTypeId
		);

		$arSelect = array('ID', 'PRODUCT_ID', 'EXTRA_ID', 'CATALOG_GROUP_ID', 'PRICE', 'CURRENCY', 'TIMESTAMP_X',
			'QUANTITY_FROM', 'QUANTITY_TO', 'TMP_ID'
		);

		$db_res = CPrice::GetListEx(
			array('QUANTITY_FROM' => 'ASC', 'QUANTITY_TO' => 'ASC'),
			$arFilter,
			false,
			array('nTopCount' => 1),
			$arSelect
		);
		if ($res = $db_res->Fetch())
			return $res;

		return false;
	}

	public static function setPrice($productID, $priceValue = 0.0, $currency = false, $priceTypeId = false, $priceTypeId = false)
	{
		$productID = intval($productID);

		if ($currency === false)
			$currency = CCrmCurrency::GetBaseCurrencyID();
		if (strlen($currency) < 3)
			return false;

		if ($priceTypeId === false)
			$priceTypeId = self::getSelectedPriceTypeId();
		if (intval($priceTypeId) < 1)
			return false;

		$ID = false;
		$arFields = false;
		if ($arFields = self::getPrice($productID, $priceTypeId))
		{
			$ID = $arFields["ID"];
			$arFields = array(
				"PRICE" => doubleval($priceValue),
				"CURRENCY" => $currency
			);
			$ID = CPrice::Update($ID, $arFields);
		}
		else
		{
			$arFields = array(
				"PRICE" => doubleval($priceValue),
				"CURRENCY" => $currency,
				"QUANTITY_FROM" => 0,
				"QUANTITY_TO" => 0,
				"EXTRA_ID" => false,
				"CATALOG_GROUP_ID" => $priceTypeId,
				"PRODUCT_ID" => $productID
			);

			$ID = CPrice::Add($arFields);
		}

		return ($ID) ? $ID : false;
	}

	// CRUD -->
	public static function Add($arFields)
	{
		if (!CModule::IncludeModule('catalog'))
		{
			return false;
		}

		global $DB;

		$element =  new CIBlockElement();
		$ID = isset($arFields['ID']) ? $arFields['ID'] : null;
		if ($ID === null)
		{
			//Try to create a CIBlockElement
			$arElement = array();

			if(isset($arFields['NAME']))
			{
				$arElement['NAME'] = $arFields['NAME'];
			}

			if(isset($arFields['SORT']))
			{
				$arElement['SORT'] = $arFields['SORT'];
			}

			if(isset($arFields['ACTIVE']))
			{
				$arElement['ACTIVE'] = $arFields['ACTIVE'];
			}

			if(isset($arFields['DETAIL_PICTURE']))
			{
				$arElement['DETAIL_PICTURE'] = $arFields['DETAIL_PICTURE'];
			}

			if(isset($arFields['DESCRIPTION']))
			{
				$arElement['DETAIL_TEXT'] = $arFields['DESCRIPTION'];
				$arElement['DETAIL_TEXT_TYPE'] = 'text';
			}

			if(isset($arFields['DESCRIPTION_TYPE']))
			{
				$arElement['DETAIL_TEXT_TYPE'] = $arFields['DESCRIPTION_TYPE'];
			}

			if(isset($arFields['PREVIEW_PICTURE']))
			{
				$arElement['PREVIEW_PICTURE'] = $arFields['PREVIEW_PICTURE'];
			}

			if(isset($arFields['PREVIEW_TEXT']))
			{
				$arElement['PREVIEW_TEXT'] = $arFields['PREVIEW_TEXT'];
				$arElement['PREVIEW_TEXT_TYPE'] = 'text';
			}

			if(isset($arFields['PREVIEW_TEXT_TYPE']))
			{
				$arElement['PREVIEW_TEXT_TYPE'] = $arFields['PREVIEW_TEXT_TYPE'];
			}

			if(isset($arFields['CATALOG_ID']))
			{
				$arElement['IBLOCK_ID'] = intval($arFields['CATALOG_ID']);
			}
			else
			{
				$arElement['IBLOCK_ID'] = $arFields['CATALOG_ID'] = CCrmCatalog::EnsureDefaultExists();
			}

			if(isset($arFields['SECTION_ID']))
			{
				$arElement['IBLOCK_SECTION_ID'] = $arFields['SECTION_ID'];
			}

			if(isset($arFields['XML_ID']))
			{
				$arElement['XML_ID'] = $arFields['XML_ID'];
			}
			else
			{
				if(isset($arFields['ORIGINATOR_ID']) || isset($arFields['ORIGIN_ID']))
				{
					if (isset($arFields['ORIGINATOR_ID']) && isset($arFields['ORIGIN_ID']))
					{
						$arElement['XML_ID'] = $arFields['ORIGINATOR_ID'].'#'.$arFields['ORIGIN_ID'];
					}
					else
					{
						if (isset($arFields['ORIGINATOR_ID'])) $arElement['XML_ID'] = $arFields['ORIGINATOR_ID'].'#';
						else $arElement['XML_ID'] = '#'.$arFields['ORIGIN_ID'];
					}
				}
				else
				{
					if ($arElement['IBLOCK_ID'] != self::getDefaultCatalogId())
						$arElement['XML_ID'] = '#';
				}
			}

			if(!$element->CheckFields($arElement))
			{
				self::RegisterError($element->LAST_ERROR);
				return false;
			}

			if(isset($arFields['PROPERTY_VALUES']))
			{
				$arElement['PROPERTY_VALUES'] = $arFields['PROPERTY_VALUES'];
			}

			$ID = intval($element->Add($arElement));
			if($ID <= 0)
			{
				self::$LAST_ERROR = $element->LAST_ERROR;
				return false;
			}
			$arFields['ID'] = $ID;
		}

		if (!self::CheckFields('ADD', $arFields, 0))
		{
			$element->Delete($ID);
			return false;
		}

		$CCatalogProduct = new CCatalogProduct();
		$arCatalogProductFields = array('ID' => $ID, 'QUANTITY' => 0);
		if (isset($arFields['VAT_INCLUDED']))
			$arCatalogProductFields['VAT_INCLUDED'] = $arFields['VAT_INCLUDED'];
		if (isset($arFields['VAT_ID']) && !empty($arFields['VAT_ID']))
			$arCatalogProductFields['VAT_ID'] = $arFields['VAT_ID'];
		if (isset($arFields['MEASURE']) && !empty($arFields['MEASURE']))
			$arCatalogProductFields['MEASURE'] = $arFields['MEASURE'];
		if ($CCatalogProduct->Add($arCatalogProductFields))
		{
			if (isset($arFields['PRICE']) && isset($arFields['CURRENCY_ID']))
			{
				self::setPrice($ID, $arFields['PRICE'], $arFields['CURRENCY_ID']);
			}
		}
		else
		{
			$element->Delete($ID);
			return false;
		}

//		$arInsert = $DB->PrepareInsert(CCrmProduct::TABLE_NAME, $arFields);
//		$sQuery =
//			'INSERT INTO '.CCrmProduct::TABLE_NAME.'('.$arInsert[0].') VALUES('.$arInsert[1].')';
//		$DB->Query($sQuery, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		if (!CModule::IncludeModule('catalog'))
		{
			return false;
		}

		global $DB;

		if (!self::CheckFields('UPDATE', $arFields, $ID))
		{
			return false;
		}

		if(isset($arFields['NAME'])
			|| isset($arFields['SECTION_ID'])
			|| isset($arFields['SORT'])
			|| isset($arFields['ACTIVE'])
			|| isset($arFields['DETAIL_PICTURE'])
			|| isset($arFields['DESCRIPTION'])
			|| isset($arFields['DESCRIPTION_TYPE'])
			|| isset($arFields['PREVIEW_PICTURE'])
			|| isset($arFields['PREVIEW_TEXT'])
			|| isset($arFields['PREVIEW_TEXT_TYPE'])
			|| isset($arFields['ORIGINATOR_ID'])
			|| isset($arFields['ORIGIN_ID'])
			|| isset($arFields['XML_ID'])
			|| isset($arFields['PROPERTY_VALUES']))
		{
			$element =  new CIBlockElement();
			$obResult = $element->GetById($ID);
			if($arElement = $obResult->Fetch())
			{
				if(isset($arFields['NAME']))
				{
					$arElement['NAME'] = $arFields['NAME'];
				}

				if(isset($arFields['SECTION_ID']))
				{
					$arElement['IBLOCK_SECTION_ID'] = $arFields['SECTION_ID'];
				}

				if(isset($arFields['SORT']))
				{
					$arElement['SORT'] = $arFields['SORT'];
				}

				if(isset($arFields['ACTIVE']))
				{
					$arElement['ACTIVE'] = $arFields['ACTIVE'];
				}

				if(isset($arFields['DETAIL_PICTURE']))
				{
					$arElement['DETAIL_PICTURE'] = $arFields['DETAIL_PICTURE'];
				}

				if(isset($arFields['DESCRIPTION']))
				{
					$arElement['DETAIL_TEXT'] = $arFields['DESCRIPTION'];
				}

				if(isset($arFields['DESCRIPTION_TYPE']))
				{
					$arElement['DETAIL_TEXT_TYPE'] = $arFields['DESCRIPTION_TYPE'];
				}

				if(isset($arFields['PREVIEW_PICTURE']))
				{
					$arElement['PREVIEW_PICTURE'] = $arFields['PREVIEW_PICTURE'];
				}

				if(isset($arFields['PREVIEW_TEXT']))
				{
					$arElement['PREVIEW_TEXT'] = $arFields['PREVIEW_TEXT'];
					$arElement['PREVIEW_TEXT_TYPE'] = 'text';
				}

				if(isset($arFields['PREVIEW_TEXT_TYPE']))
				{
					$arElement['PREVIEW_TEXT_TYPE'] = $arFields['PREVIEW_TEXT_TYPE'];
				}

				if(isset($arFields['XML_ID']))
				{
					$arElement['XML_ID'] = $arElement['EXTERNAL_ID'] = $arFields['XML_ID'];
				}
				else
				{
					if (isset($arFields['ORIGINATOR_ID']) || isset($arFields['ORIGIN_ID']))
					{
						if (strlen($arFields['ORIGINATOR_ID']) > 0 && strlen($arFields['ORIGIN_ID']) > 0)
						{
							$arElement['XML_ID'] = $arFields['ORIGINATOR_ID'].'#'.$arFields['ORIGIN_ID'];
						}
						else
						{
							$delimiterPos = strpos($arElement['XML_ID'], '#');
							if (strlen($arFields['ORIGINATOR_ID']) > 0)
							{
								if ($delimiterPos !== false)
								{
									$arElement['XML_ID'] = $arFields['ORIGINATOR_ID'].substr($arElement['XML_ID'], $delimiterPos);
								}
								else $arElement['XML_ID'] = $arFields['ORIGINATOR_ID'];
							}
							else
							{
								if ($delimiterPos !== false)
								{
									$arElement['XML_ID'] = substr($arElement['XML_ID'], 0, $delimiterPos).$arFields['ORIGIN_ID'];
								}
								else $arElement['XML_ID'] = '#'.$arFields['ORIGINATOR_ID'];
							}
						}
					}
				}

				if(isset($arFields['PROPERTY_VALUES']))
				{
					$arElement['PROPERTY_VALUES'] = $arFields['PROPERTY_VALUES'];
				}

				if(!$element->Update($ID, $arElement))
				{
					self::$LAST_ERROR = $element->LAST_ERROR;
					return false;
				}
			}
		}

		// update VAT
		$CCatalogProduct = new CCatalogProduct();
		$arCatalogProductFields = array();
		if (isset($arFields['VAT_INCLUDED']))
			$arCatalogProductFields['VAT_INCLUDED'] = $arFields['VAT_INCLUDED'];
		if (isset($arFields['VAT_ID']) && !empty($arFields['VAT_ID']))
			$arCatalogProductFields['VAT_ID'] = $arFields['VAT_ID'];
		if (isset($arFields['MEASURE']) && !empty($arFields['MEASURE']))
			$arCatalogProductFields['MEASURE'] = $arFields['MEASURE'];
		if (count($arCatalogProductFields) > 0)
			$CCatalogProduct->Update($ID, $arCatalogProductFields);

		if (isset($arFields['PRICE']) && isset($arFields['CURRENCY_ID']))
		{
			self::setPrice($ID, $arFields['PRICE'], $arFields['CURRENCY_ID']);
		}
		else
		{
			if (isset($arFields['PRICE']) || isset($arFields['CURRENCY_ID']))
			{
				$CPrice = new CPrice();
				$price = $currency = false;
				if (!isset($arFields['PRICE']))
				{
					$basePriceInfo = self::getPrice($ID);
					if ($basePriceInfo !== false && is_array($basePriceInfo) && isset($basePriceInfo['PRICE']))
					{
						$price = $basePriceInfo['PRICE'];
						$currency = $arFields['CURRENCY_ID'];
					}
				}
				elseif (!isset($arFields['CURRENCY_ID']))
				{
					$basePriceInfo = self::getPrice($ID);
					if ($basePriceInfo !== false && is_array($basePriceInfo) && isset($basePriceInfo['PRICE']))
					{
						$price = $arFields['PRICE'];
						$currency = $basePriceInfo['CURRENCY'];
					}
				}
				else
				{
					$price = $arFields['PRICE'];
					$currency = $arFields['CURRENCY_ID'];
				}
				if ($price !== false && $currency !== false) CCrmProduct::setPrice($ID, $price, $currency);
			}
		}

//		$sUpdate = trim($DB->PrepareUpdate(CCrmProduct::TABLE_NAME, $arFields));
//		if (!empty($sUpdate))
//		{
//			$sQuery = 'UPDATE '.CCrmProduct::TABLE_NAME.' SET '.$sUpdate.' WHERE ID = '.$ID;
//			$DB->Query($sQuery, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
//
//			CCrmEntityHelper::RemoveCached(self::CACHE_NAME, $ID);
//		}

		CCrmEntityHelper::RemoveCached(self::CACHE_NAME, $ID);

		return true;
	}

	public static function Delete($ID)
	{
		global $DB, $APPLICATION;

		$ID = intval($ID);

		$arProduct = self::GetByID($ID);
		if(!is_array($arProduct))
		{
			// Is no exists
			return true;
		}

		$rowsCount = CCrmProductRow::GetList(array(), array('PRODUCT_ID' => $ID), array(), false, array());
		if($rowsCount > 0 || CCrmInvoice::HasProductRows($ID))
		{
			self::RegisterError(GetMessage('CRM_COULD_NOT_DELETE_PRODUCT_ROWS_EXIST', array('#NAME#' => $arProduct['~NAME'])));
			return false;
		}

		foreach (GetModuleEvents('crm', 'OnBeforeCrmProductDelete', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ID)) === false)
			{
				return false;
			}
		}

		//$DB->StartTransaction();
		//$APPLICATION->ResetException();

//		$sql = 'DELETE FROM '.CCrmProduct::TABLE_NAME.' WHERE ID = '.$ID;
//		if(!$DB->Query($sql, true))
//		{
//			//$DB->Rollback();
//			return false;
//		}

		CCrmEntityHelper::RemoveCached(self::CACHE_NAME, $ID);

		if(self::IsIBlockElementExists($ID))
		{
			$element = new CIBlockElement();
			if(!$element->Delete($ID))
			{
				//$DB->Rollback();
				if ($ex = $APPLICATION->GetException())
				{
					self::RegisterError($ex->GetString());
				}
				return false;
			}
		}

		//$DB->Commit();
		foreach (GetModuleEvents('crm', 'OnCrmProductDelete', true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ID));
		}
		return true;
	}
	//<-- CRUD

	// Contract -->
	public static function GetList($arOrder = array(), $arFilter = array(), $arSelectFields = array(), $arNavStartParams = false, $arGroupBy = false)
	{
		$arProductFields = self::GetFields();

		// Rewrite order
		// <editor-fold defaultstate="collapsed" desc="Rewrite order ...">
		$arOrderRewrited = array();
		foreach ($arOrder as $k => $v)
		{
			$uk = strtoupper($k);
			if ((isset($arProductFields[$uk]) && $arProductFields[$uk] !== false)
				|| preg_match('/^PROPERTY_\d+$/', $uk))
				$arOrderRewrited[$uk] = $v;
		}
		if (strlen($arOrder['ORIGINATOR_ID'].$arOrder['ORIGIN_ID']) > 0)
		{
			if (strlen($arOrder['ORIGINATOR_ID']) > 0) $arOrderRewrited['XML_ID'] = $arOrder['ORIGINATOR_ID'];
			else $arOrderRewrited['XML_ID'] = $arOrder['ORIGIN_ID'];
		}
		// </editor-fold>

		// Rewrite filter
		// <editor-fold defaultstate="collapsed" desc="Rewrite filter ...">
		$arAdditionalFilter = $arFilterRewrited = array();

		$arOptions = array();
		if (isset($arFilter['~REAL_PRICE']))
		{
			$arOptions['REAL_PRICE'] = true;
			unset($arFilter['~REAL_PRICE']);
		}

		foreach ($arProductFields as $fieldProduct => $fieldIblock)
		{
			foreach($arFilter as $k => $v)
			{
				$matches = array();
				if (preg_match('/^([!><=%?][><=%]?[<]?|)'.$fieldProduct.'$/', $k, $matches))
				{
					if ($fieldIblock)
					{
						if($fieldIblock === 'IBLOCK_SECTION_ID')
						{
							//HACK: IBLOCK_SECTION_ID is not supported in filter
							$fieldIblock = 'SECTION_ID';
						}

						$arFilterRewrited[$matches[1].$fieldIblock] = $v;
					}
					else
					{
						$arAdditionalFilter[$k] = $v;
					}
				}
				else if (preg_match('/^([!><=%?][><=%]?[<]?|)(PROPERTY_\d+)$/', $k, $matches))
				{
					$arFilterRewrited[$matches[1].$matches[2]] = $v;
				}
			}
		}
		if (strlen($arFilter['ORIGINATOR_ID'].$arFilter['ORIGIN_ID']) > 0)
		{
			if (strlen($arFilter['ORIGINATOR_ID']) > 0 && strlen($arFilter['ORIGIN_ID']) > 0)
			{
				$arFilterRewrited['XML_ID'] = $arFilter['ORIGINATOR_ID'].'#'.$arFilter['ORIGIN_ID'];
			}
			else
			{
				if (strlen($arFilter['ORIGINATOR_ID']) > 0)
				{
					$arFilterRewrited['%XML_ID'] = $arFilter['ORIGINATOR_ID'].'#';
				}
				else
				{
					$arFilterRewrited['%XML_ID'] = '#'.$arFilter['ORIGIN_ID'];
				}
			}
		}

		if(!isset($arFilter['ID']))
		{
			$catalogID = isset($arFilter['CATALOG_ID']) ? intval($arFilter['CATALOG_ID']) : 0;
			if($catalogID > 0 && !CCrmCatalog::Exists($catalogID))
			{
				$catalogID = 0;
			}

			if($catalogID <= 0)
			{
				$catalogID = CCrmCatalog::EnsureDefaultExists();
			}

			$arFilterRewrited['IBLOCK_ID'] = $catalogID;
		}

		// </editor-fold>

		// Rewrite select
		// <editor-fold defaultstate="collapsed" desc="Rewrite select ...">
		$arSelect = $arSelectFields;
		if (!is_array($arSelect))
		{
			$arSelect = array();
		}

		if (empty($arSelect))
		{
			$arSelect = array();
			foreach (array_keys($arProductFields) as $fieldName)
			{
				if (!in_array($fieldName, array('PRICE', 'CURRENCY_ID', 'VAT_ID', 'VAT_INCLUDED', 'MEASURE'), true))
					$arSelect[] = $fieldName;
			}
		}
		else if (in_array('*', $arSelect, true))
		{
			$arSelect = array_keys($arProductFields);
		}

		$arAdditionalSelect = $arSelectRewrited = array();
		foreach ($arProductFields as $fieldProduct => $fieldIblock)
		{
			if (in_array($fieldProduct, $arSelect, true))
			{
				if ($fieldIblock) $arSelectRewrited[] = $fieldIblock;
				else $arAdditionalSelect[] = $fieldProduct;
			}
		}
		foreach ($arSelect as $v)
		{
			if ((isset($arProductFields[$v]) && $arProductFields[$v] !== false) || preg_match('/^PROPERTY_\d+$/', $v))
				$arSelectRewrited[] = $arProductFields[$v];
			else if (isset($arProductFields[$v]))
				$arAdditionalSelect[] = $v;
		}
		if (!in_array('ID', $arSelectRewrited, true))
			$arSelectRewrited[] = 'ID';

		if (!in_array('XML_ID', $arSelectRewrited, true))
		{
			$bSelectXmlId = false;
			foreach ($arSelect as $k => $v)
			{
				if ($v === 'ORIGINATOR_ID' || $v === 'ORIGIN_ID')
				{
					$bSelectXmlId = true;
					break;
				}
			}
			if ($bSelectXmlId) $arAdditionalSelect[] = $arSelectRewrited[] = 'XML_ID';
		}
		// </editor-fold>

		$arNavStartParamsRewrited = false;
		if (is_array($arNavStartParams))
			$arNavStartParamsRewrited = $arNavStartParams;
		else
		{
			if (is_numeric($arNavStartParams))
			{
				$nTopCount = intval($arNavStartParams);
				if ($nTopCount > 0)
					$arNavStartParamsRewrited = array('nTopCount' => $nTopCount);
			}
		}

		$dbRes = CIBlockElement::GetList($arOrderRewrited, $arFilterRewrited, ($arGroupBy === false) ? false : array(), $arNavStartParamsRewrited, $arSelectRewrited);
		if ($arGroupBy === false)
			$dbRes = new CCrmProductResult($dbRes, $arProductFields, $arAdditionalFilter, $arAdditionalSelect, $arOptions);

		return $dbRes;
	}

	public static function GetPrices($arProductID = array(), $priceTypeId = false)
	{
		$dbRes = false;

		if (is_array($arProductID) && !empty($arProductID))
		{
			if ($priceTypeId === false)
				$priceTypeId = self::getSelectedPriceTypeId();
			if (intval($priceTypeId) > 0)
			{
				$dbRes = CPrice::GetListEx(
					array('QUANTITY_FROM' => 'ASC', 'QUANTITY_TO' => 'ASC'),
					array('PRODUCT_ID' => $arProductID, 'CATALOG_GROUP_ID' => $priceTypeId),
					false,
					false,
					array('ID', 'PRODUCT_ID', 'PRICE', 'CURRENCY')
				);
			}
		}

		return $dbRes;
	}

	public static function GetCatalogProductFields($arProductID = array())
	{
		if (!CModule::IncludeModule('catalog'))
		{
			return false;
		}

		$dbRes = false;
		if (is_array($arProductID) && !empty($arProductID))
		{
			$dbRes = CCatalogProduct::GetList(
				array(),
				array('ID' => $arProductID),
				false,
				false,
				array('ID', 'VAT_ID', 'VAT_INCLUDED', 'MEASURE')
			);
		}
		return $dbRes;
	}

	public static function PrepareCatalogProductFields(array $arProductID)
	{
		if (!CModule::IncludeModule('catalog'))
		{
			return array();
		}

		if (!(is_array($arProductID) && !empty($arProductID)))
		{
			return array();
		}

		$result = array();
		$dbResult = CCatalogProduct::GetList(
			array(),
			array('ID' => $arProductID),
			false,
			false,
			array('ID', 'VAT_ID', 'VAT_INCLUDED', 'MEASURE')
		);

		if(is_object($dbResult))
		{
			while($fields = $dbResult->Fetch())
			{
				$productID = intval($fields['ID']);
				$result[$productID] = array(
					'PRODUCT_ID' => $productID,
					'TAX_ID' => isset($fields['VAT_ID']) ? intval($fields['VAT_ID']) : 0,
					'TAX_INCLUDED' => isset($fields['VAT_INCLUDED']) && strtoupper($fields['VAT_INCLUDED']) === 'Y',
					'MEASURE' => isset($fields['MEASURE']) ? intval($fields['MEASURE']) : 0
				);
			}
		}
		return $result;
	}

	public static function RecalculatePriceVat($price, $bVatIncluded, $vatId)
	{
		$result = $price;

		if (self::$bVatMode === null)
		{
			self::$bVatMode = CCrmTax::isVatMode();
			if (self::$bVatMode)
				self::$arVatRates = CCrmVat::GetAll();
		}

		if (self::$bVatMode)
		{
			if($bVatIncluded !== 'Y')
			{
				if (isset(self::$arVatRates[$vatId]))
				{
					$vatRate = self::$arVatRates[$vatId]['RATE'];
					$result = (doubleval($vatRate)/100 + 1) * doubleval($price);
				}
			}
		}

		return $result;
	}

	public static function DistributeProductSelect($arSelect, &$arPricesSelect, &$arCatalogProductSelect)
	{
		$tmpSelect = array();
		foreach ($arSelect as $fieldName)
		{
			switch ($fieldName)
			{
				case 'PRICE':
				case 'CURRENCY_ID':
					$arPricesSelect[] = $fieldName;
					break;
				case 'VAT_ID':
				case 'VAT_INCLUDED':
				case 'MEASURE':
					$arCatalogProductSelect[] = $fieldName;
					break;
				default:
					$tmpSelect[] = $fieldName;
			}
		}
		return $tmpSelect;
	}
	
	public static function ObtainPricesVats(&$arProducts, &$arProductId, &$arPricesSelect, &$arCatalogProductSelect, $bRealPrice = false)
	{
		if (is_array($arProducts) && is_array($arProductId) && is_array($arPricesSelect) && is_array($arCatalogProductSelect)
			&& count($arProductId) > 0 && count($arProducts) > 0 && (count($arPricesSelect) + count($arCatalogProductSelect)) > 0)
		{
			$arEntitiesFieldsets = array();
			if (count($arPricesSelect) > 0)
				$arEntitiesFieldsets[] = array(
					'name' => 'price',
					'class' => 'CCrmProduct',
					'method' => 'GetPrices',
					'fieldset' => &$arPricesSelect,
					'idField' => 'PRODUCT_ID',
					'fieldMap' => array(
						'PRICE' => 'PRICE',
						'CURRENCY_ID' => 'CURRENCY'
					)
				);
			if (count($arCatalogProductSelect) > 0 || (in_array('PRICE', $arPricesSelect, true) && !$bRealPrice))
				$arEntitiesFieldsets[] = array(
					'name' => 'vat',
					'class' => 'CCrmProduct',
					'method' => 'GetCatalogProductFields',
					'fieldset' => &$arCatalogProductSelect,
					'idField' => 'ID',
					'fieldMap' => array(
						'VAT_INCLUDED' => 'VAT_INCLUDED',
						'VAT_ID' => 'VAT_ID',
						'MEASURE' => 'MEASURE'
					)
				);
			$nProducts = count($arProductId);
			$nStepSize = 500;
			$nSteps = intval(floor($nProducts / $nStepSize)) + 1;
			$nOffset = $nRange = 0;
			$arStepProductId = $fieldset = $arRow = array();
			$fieldName = '';
			while ($nSteps > 0)
			{
				$nRange = ($nSteps > 1) ? $nStepSize : $nProducts - $nOffset;
				if ($nRange > 0)
				{
					$arStepProductId = array_slice($arProductId, $nOffset, $nRange);
					foreach ($arEntitiesFieldsets as $fieldset)
					{
						$dbStep = call_user_func(array($fieldset['class'], $fieldset['method']), $arStepProductId);
						if ($dbStep)
						{
							while ($arRow = $dbStep->Fetch())
							{
								foreach ($fieldset['fieldset'] as $fieldName)
								{
									if (isset($arProducts[$arRow[$fieldset['idField']]]))
									{
										$arProduct = &$arProducts[$arRow[$fieldset['idField']]];
										if (array_key_exists($fieldName, $arProduct) && array_key_exists($fieldset['fieldMap'][$fieldName], $arRow))
										{
											$prefix = array_key_exists('~'.$fieldName, $arProduct) ? '~' : '';
											$arProduct[$prefix.$fieldName] = $arRow[$fieldset['fieldMap'][$fieldName]];
											if (!empty($prefix))
												$arProduct[$fieldName] = htmlspecialcharsbx($arProduct[$prefix.$fieldName]);
										}
									}
								}
								if ($fieldset['name'] === 'vat'
									&& (!isset($bRealPrice) || $bRealPrice !== true))
								{
									if (isset($arProducts[$arRow[$fieldset['idField']]]))
									{
										$arProduct = &$arProducts[$arRow[$fieldset['idField']]];
										$prefix = isset($arProduct['~PRICE']) ? '~' : '';
										if (isset($arProduct[$prefix.'PRICE'])
											&& doubleval($arProduct[$prefix.'PRICE']) != 0.0
											&& $arRow['VAT_INCLUDED'] !== 'Y'
											&& intval($arRow['VAT_ID']) > 0)
										{
											$arProduct[$prefix.'PRICE'] = self::RecalculatePriceVat(
												$arProduct[$prefix.'PRICE'], $arRow['VAT_INCLUDED'], $arRow['VAT_ID']
											);
											if (!empty($prefix))
												$arProduct['PRICE'] = htmlspecialcharsbx($arProduct[$prefix.'PRICE']);
										}
									}
								}
							}
						}
					}
				}
				$nOffset += $nStepSize;
				$nSteps--;
			}
		}
	}

	public static function Exists($ID)
	{
		$dbRes = CCrmProduct::GetList(array(), array('ID'=> $ID), array('ID'));
		return $dbRes->Fetch() ? true : false;
	}

	public static function GetByID($ID, $bRealPrice = false)
	{
		$arResult = CCrmEntityHelper::GetCached(self::CACHE_NAME.($bRealPrice !== false ? '_RP' : ''), $ID);
		if (is_array($arResult))
		{
			return $arResult;
		}

		$arFilter = array('=ID' => intval($ID));
		if ($bRealPrice !== false) $arFilter['~REAL_PRICE'] = true;

		$dbRes = CCrmProduct::GetList(array(), $arFilter, array('*'), array('nTopCount' => 1));
		$arResult = $dbRes->GetNext();

		if(is_array($arResult))
		{
			CCrmEntityHelper::SetCached(self::CACHE_NAME.($bRealPrice !== false ? '_RP' : ''), $ID, $arResult);
		}
		return $arResult;
	}

	public static function GetByName($name)
	{
		$dbRes = CCrmProduct::GetList(array(), array('NAME' => strval($name)), array('*'), array('nTopCount' => 1));
		return $dbRes->GetNext();
	}

	public static function GetByOriginID($originID, $catalogID = 0)
	{
		$catalogID = intval($catalogID);
		if($catalogID <= 0)
		{
			$catalogID = CCrmCatalog::GetDefaultID();
		}

		if($catalogID <= 0)
		{
			return false;
		}

		$dbRes = CCrmProduct::GetList(array(), array('CATALOG_ID' => $catalogID, 'ORIGIN_ID' => $originID),
			array('*'), array('nTopCount' => 1));
		return ($dbRes->GetNext());
	}

	public static function FormatPrice($arProduct)
	{
		$price = isset($arProduct['PRICE']) ? round(doubleval($arProduct['PRICE']), 2) : 0.00;
		/*if($price == 0.00)
		{
			return '';
		}*/

		$currencyID = isset($arProduct['CURRENCY_ID']) ? strval($arProduct['CURRENCY_ID']) : '';
		return CCrmCurrency::MoneyToString($price, $currencyID);
	}

	public static function GetProductName($productID)
	{
		$productID = intval($productID);
		if($productID <=0)
		{
			return '';
		}

		$dbResult = self::GetList(array(), array('=ID' => $productID), array('NAME'));
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		return is_array($fields) && isset($fields['NAME']) ? $fields['NAME'] : '';
	}

	public static function GetLastError()
	{
		return self::$LAST_ERROR;
	}
	//<-- Contract

	//Service -->
	protected static function GetFields()
	{
		return array(
//			'ID' => array('FIELD' => 'P.ID', 'TYPE' => 'int'),
//			'CATALOG_ID' => array('FIELD' => 'P.CATALOG_ID', 'TYPE' => 'int'),
//			'PRICE' => array('FIELD' => 'P.PRICE', 'TYPE' => 'double'),
//			'CURRENCY_ID' => array('FIELD' => 'P.CURRENCY_ID', 'TYPE' => 'string'),
//			'ORIGINATOR_ID' => array('FIELD' => 'P.ORIGINATOR_ID', 'TYPE' => 'string'),
//			'ORIGIN_ID' => array('FIELD' => 'P.ORIGIN_ID', 'TYPE' => 'string'),
//			'NAME' => array('FIELD' => 'E.NAME', 'TYPE' => 'string', 'FROM' => 'INNER JOIN b_iblock_element E ON P.ID = E.ID'),
//			'ACTIVE' => array('FIELD' => 'E.ACTIVE', 'TYPE' => 'char', 'FROM' => 'INNER JOIN b_iblock_element E ON P.ID = E.ID'),
//			'SECTION_ID' => array('FIELD' => 'E.IBLOCK_SECTION_ID', 'TYPE' => 'int', 'FROM' => 'INNER JOIN b_iblock_element E ON P.ID = E.ID'),
//			'DESCRIPTION' => array('FIELD' => 'E.DETAIL_TEXT', 'TYPE' => 'string', 'FROM' => 'INNER JOIN b_iblock_element E ON P.ID = E.ID'),
//			'SORT' => array('FIELD' => 'E.SORT', 'TYPE' => 'int', 'FROM' => 'INNER JOIN b_iblock_element E ON P.ID = E.ID')

			// Value of an element contains the corresponding field of iblock element.
			// If value is false, it means that the field is stored in the catalog module.
			// The ORIGINATOR_ID and ORIGIN_ID fields stick together and stored in the XML_ID field of iblock element
			// in the format of 'ORIGINATOR_ID#ORIGIN_ID'.
			'ID' => 'ID',
			'CATALOG_ID' => 'IBLOCK_ID',
			'PRICE' => false,
			'CURRENCY_ID' => false,
			'ORIGINATOR_ID' => false,
			'ORIGIN_ID' => false,
			'NAME' => 'NAME',
			'ACTIVE' => 'ACTIVE',
			'SECTION_ID' => 'IBLOCK_SECTION_ID',
			'PREVIEW_PICTURE' => 'PREVIEW_PICTURE',
			'PREVIEW_TEXT' => 'PREVIEW_TEXT',
			'PREVIEW_TEXT_TYPE' => 'PREVIEW_TEXT_TYPE',
			'DETAIL_PICTURE' => 'DETAIL_PICTURE',
			'DESCRIPTION' => 'DETAIL_TEXT',
			'DESCRIPTION_TYPE' => 'DETAIL_TEXT_TYPE',
			'SORT' => 'SORT',
			'VAT_ID' => false,
			'VAT_INCLUDED' => false,
			'MEASURE' => false,
			'XML_ID' => 'XML_ID'
		);
	}

	// Get Fields Metadata
	public static function GetFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'CATALOG_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'PRICE' => array('TYPE' => 'double'),
				'CURRENCY_ID' => array('TYPE' => 'string'),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'DESCRIPTION' => array('TYPE' => 'string'),
				'ACTIVE' => array('TYPE' => 'char'),
				'SECTION_ID' => array('TYPE' => 'integer'),
				'SORT' => array('TYPE' => 'integer'),
				'VAT_ID' => array('TYPE' => 'integer'),
				'VAT_INCLUDED' => array('TYPE' => 'char'),
				'MEASURE' => array('TYPE' => 'integer'),
				'XML_ID' => array('TYPE' => 'string')
			);
		}
		return self::$FIELD_INFOS;
	}

	//Check fields before ADD and UPDATE.
	private static function CheckFields($sAction, &$arFields, $ID)
	{
		if($sAction == 'ADD')
		{
			if (!is_set($arFields, 'ID'))
			{
				self::RegisterError('Could not find ID. ID that is treated as a IBLOCK_ELEMENT_ID.');
				return false;
			}

			$elementID = intval($arFields['ID']);
			if($elementID <= 0)
			{
				self::RegisterError('ID that is treated as a IBLOCK_ELEMENT_ID is invalid.');
				return false;
			}

			if (!self::IsIBlockElementExists($elementID))
			{
				self::RegisterError("Could not find IBlockElement(ID = $elementID).");
				return false;
			}

			if (!is_set($arFields, 'CATALOG_ID'))
			{
				self::RegisterError('Could not find CATALOG_ID. CATALOG_ID that is treated as a IBLOCK_ID.');
				return false;
			}

			$blockID = intval($arFields['CATALOG_ID']);
			if($blockID <= 0)
			{
				self::RegisterError('CATALOG_ID that is treated as a IBLOCK_ID is invalid.');
				return false;
			}

			$blocks = CIBlock::GetList(array(), array('ID' => $blockID), false, false, array('ID'));
			if (!($blocks = $blocks->Fetch()))
			{
				self::RegisterError("Could not find IBlock(ID = $blockID).");
				return false;
			}
		}
		else//if($sAction == 'UPDATE')
		{
			if(!self::Exists($ID))
			{
				self::RegisterError("Could not find CrmProduct(ID = $ID).");
				return false;
			}
		}

		return true;
	}

	private static function RegisterError($msg)
	{
		global $APPLICATION;
		$APPLICATION->ThrowException(new CAdminException(array(array('text' => $msg))));
		self::$LAST_ERROR = $msg;
	}

	private static function IsIBlockElementExists($ID)
	{
		$rsElements = CIBlockElement::GetList(array(), array('ID' => $ID), false, array('nTopCount' => 1), array('ID'));
		return $rsElements->Fetch() ? true : false;
	}

	// <-- Service

	// Event handlers -->
	public static function OnIBlockElementDelete($ID)
	{
		return CCrmProduct::Delete($ID);
	}
	// <-- Event handlers
	// Checking User Permissions -->
	public static function CheckCreatePermission()
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}
	public static function CheckUpdatePermission($ID)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}
	public static function CheckDeletePermission($ID)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}
	public static function CheckReadPermission($ID = 0)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ');
	}
	// <-- Checking User Permissions
}
