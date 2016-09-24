<?php
class CAllCrmProductRow
{
	const CACHE_NAME = 'CRM_PRODUCT_ROW_CACHE';
	const TABLE_ALIAS = 'PR';
	const TAX_MODE = 1;
	const LD_TAX_MODE = 1;
	protected static $LAST_ERROR = '';
	protected static $FIELD_INFOS = null;

	public static function CalculateInclusivePrice($exclusivePrice, $taxRate)
	{
		return doubleval($exclusivePrice) * (1 + (doubleval($taxRate) / 100));
	}
	public static function CalculateExclusivePrice($inclusivePrice, $taxRate)
	{
		return doubleval($inclusivePrice) / (1 + (doubleval($taxRate) / 100));
	}
	// CRUD -->
	public static function Add($arFields, $checkPerms = true, $regEvent = true)
	{
		global $DB;

		if (!self::CheckFields('ADD', $arFields, 0))
		{
			return false;
		}

		$ownerType = isset($arFields['OWNER_TYPE']) ? strval($arFields['OWNER_TYPE']) : '';
		$ownerID = isset($arFields['OWNER_ID']) ? intval($arFields['OWNER_ID']) : 0;

		if($ownerType !== '' && $ownerID > 0)
		{
			$accContext = self::PrepareAccountingContext($ownerType, $ownerID);
			if(isset($accContext['CURRENCY_ID']))
			{
				$arFields['CURRENCY_ID'] = $accContext['CURRENCY_ID'];
			}

			if(isset($accContext['EXCH_RATE']))
			{
				$arFields['EXCH_RATE'] = $accContext['EXCH_RATE'];
			}
		}

		// Calculation of Account Data
		if(isset($arFields['CURRENCY_ID']))
		{
			$accData = CCrmAccountingHelper::PrepareAccountingData(
				array(
					'CURRENCY_ID' => $arFields['CURRENCY_ID'],
					'SUM' => isset($arFields['PRICE']) ? $arFields['PRICE'] : null,
					'EXCH_RATE' => isset($arFields['EXCH_RATE']) ? $arFields['EXCH_RATE'] : null
				)
			);

			if(is_array($accData))
			{
				$arFields['PRICE_ACCOUNT'] = $accData['ACCOUNT_SUM'];
			}
		}

		$productID = $arFields['PRODUCT_ID'] = isset($arFields['PRODUCT_ID']) ? intval($arFields['PRODUCT_ID']) : 0;
		$arFields['PRODUCT_NAME'] = isset($arFields['PRODUCT_NAME']) ? $arFields['PRODUCT_NAME'] : '';
		if ($productID > 0
			&& $arFields['PRODUCT_NAME'] !== ''
			&& $arFields['PRODUCT_NAME'] === CCrmProduct::GetProductName($productID))
		{
			$arFields['PRODUCT_NAME'] = '';
		}

		$arFields['DISCOUNT_TYPE_ID'] = isset($arFields['DISCOUNT_TYPE_ID'])
			? intval($arFields['DISCOUNT_TYPE_ID']) : \Bitrix\Crm\Discount::UNDEFINED;

		if(!\Bitrix\Crm\Discount::isDefined($arFields['DISCOUNT_TYPE_ID']))
		{
			$arFields['DISCOUNT_TYPE_ID'] = \Bitrix\Crm\Discount::PERCENTAGE;
		}

		if($arFields['DISCOUNT_TYPE_ID'] === \Bitrix\Crm\Discount::MONETARY)
		{
			$arFields['DISCOUNT_SUM'] = round(doubleval($arFields['DISCOUNT_SUM']), 2);
			$arFields['DISCOUNT_RATE'] = \Bitrix\Crm\Discount::calculateDiscountRate(
				($arFields['PRICE'] + $arFields['DISCOUNT_SUM']),
				$arFields['PRICE']
			);
		}
		else if($arFields['DISCOUNT_TYPE_ID'] === \Bitrix\Crm\Discount::PERCENTAGE)
		{
			$arFields['DISCOUNT_RATE'] = round(doubleval($arFields['DISCOUNT_RATE']), 2);
			$arFields['DISCOUNT_SUM'] = round(\Bitrix\Crm\Discount::calculateDiscountSum(
				$arFields['PRICE'],
				$arFields['DISCOUNT_RATE']
			), 2);
		}
		else
		{
			$arFields['DISCOUNT_SUM'] = $arFields['DISCOUNT_RATE'] = 0.0;
		}

		$arFields['MEASURE_CODE'] = isset($arFields['MEASURE_CODE']) ? intval($arFields['MEASURE_CODE']) : 0;
		$arFields['MEASURE_NAME'] = isset($arFields['MEASURE_NAME']) ? $arFields['MEASURE_NAME'] : '';

		$defaultMeasureInfo = \Bitrix\Crm\Measure::getDefaultMeasure();

		if($arFields['MEASURE_CODE'] <= 0)
		{
			if($productID > 0)
			{
				$measureInfos = \Bitrix\Crm\Measure::getProductMeasures($productID);
				if(isset($measureInfos[$productID]) && !empty($measureInfos[$productID]))
				{
					$measureInfo = $measureInfos[$productID][0];
					$arFields['MEASURE_CODE'] = $measureInfo['CODE'];
					$arFields['MEASURE_NAME'] = $measureInfo['SYMBOL'];
				}

			}
			if($arFields['MEASURE_CODE'] <= 0 && $defaultMeasureInfo !== null)
			{
				$arFields['MEASURE_CODE'] = $defaultMeasureInfo['CODE'];
				$arFields['MEASURE_NAME'] = $defaultMeasureInfo['SYMBOL'];
			}
		}
		elseif($arFields['MEASURE_NAME'] === '')
		{
			$measureInfo = \Bitrix\Crm\Measure::getMeasureByCode($arFields['MEASURE_CODE']);
			if(is_array($measureInfo))
			{
				$arFields['MEASURE_NAME'] = $measureInfo['SYMBOL'];
			}
		}

		$arFields['TAX_RATE'] = isset($arFields['TAX_RATE']) ? round(doubleval($arFields['TAX_RATE']), 2) : 0;
		$arFields['TAX_INCLUDED'] = isset($arFields['TAX_INCLUDED']) && strtoupper($arFields['TAX_INCLUDED']) === 'Y' ? 'Y' : 'N';
		$arFields['CUSTOMIZED'] = isset($arFields['CUSTOMIZED']) && strtoupper($arFields['CUSTOMIZED']) === 'Y' ? 'Y' : 'N';

		$ID = $DB->Add(CCrmProductRow::TABLE_NAME, $arFields);
		if($ID === false)
		{
			self::RegisterError('DB connection was lost');
		}
		else
		{
			$arFields['ID'] = $ID;

			// Update list of taxes
			self::UpdateTotalInfo($ownerType, $ownerID);

			self::SynchronizeOwner($ownerType, $ownerID);

			if($regEvent)
			{
				self::RegisterAddEvent($ownerType, $ownerID, $arFields, $checkPerms);
			}
		}

		return $ID;
	}

	public static function Update($ID, $arFields, $checkPerms = true, $regEvent = true)
	{
		global $DB;

		if (!self::CheckFields('UPDATE', $arFields, $ID))
		{
			return false;
		}

		$arParams = self::GetByID($ID);
		if(!is_array($arParams))
		{
			self::RegisterError("Could not find CrmProductRow '$ID'!");
			return false;
		}

		$ownerType = isset($arFields['OWNER_TYPE']) ? strval($arFields['OWNER_TYPE']) : isset($arParams['OWNER_TYPE']) ? strval($arParams['OWNER_TYPE']) : '';
		$ownerID = isset($arFields['OWNER_ID']) ? intval($arFields['OWNER_ID']) : isset($arParams['OWNER_ID']) ? intval($arParams['OWNER_ID']) : 0;

		if($ownerType !== '' && $ownerID > 0)
		{
			$accContext = self::PrepareAccountingContext($ownerType, $ownerID);
			if(isset($accContext['CURRENCY_ID']))
			{
				$arFields['CURRENCY_ID'] = $accContext['CURRENCY_ID'];
			}

			if(isset($accContext['EXCH_RATE']))
			{
				$arFields['EXCH_RATE'] = $accContext['EXCH_RATE'];
			}
		}

		// Calculation of Account Data
		if(isset($arFields['CURRENCY_ID']))
		{
			$accData = CCrmAccountingHelper::PrepareAccountingData(
				array(
					'CURRENCY_ID' => $arFields['CURRENCY_ID'],
					'SUM' => isset($arFields['PRICE']) ? $arFields['PRICE'] : null,
					'EXCH_RATE' => isset($arFields['EXCH_RATE']) ? $arFields['EXCH_RATE'] : null
				)
			);

			if(is_array($accData))
			{
				$arFields['PRICE_ACCOUNT'] = $accData['ACCOUNT_SUM'];
			}
		}

		if(isset($arFields['PRODUCT_ID']) && isset($arFields['PRODUCT_NAME']))
		{
			$arFields['PRODUCT_ID'] = intval($arFields['PRODUCT_ID']);
			if($arFields['PRODUCT_ID'] > 0
				&& $arFields['PRODUCT_NAME'] !== ''
				&& $arFields['PRODUCT_NAME'] === CCrmProduct::GetProductName($arFields['PRODUCT_ID']))
			{
				$arFields['PRODUCT_NAME'] = '';
			}
		}

		if(!isset($arFields['PRICE']))
		{
			unset($arFields['DISCOUNT_TYPE_ID']);
			unset($arFields['DISCOUNT_SUM']);
			unset($arFields['DISCOUNT_RATE']);
		}
		else
		{
			$arFields['PRICE'] = round(doubleval($arFields['PRICE']), 2);

			$discountTypeID = isset($arFields['DISCOUNT_TYPE_ID'])
				? intval($arFields['DISCOUNT_TYPE_ID']) : \Bitrix\Crm\Discount::UNDEFINED;

			if(!\Bitrix\Crm\Discount::isDefined($discountTypeID))
			{
				$discountTypeID = isset($arParams['DISCOUNT_TYPE_ID'])
					? intval($arParams['DISCOUNT_TYPE_ID']) : \Bitrix\Crm\Discount::UNDEFINED;
			}

			$arFields['DISCOUNT_TYPE_ID'] = $discountTypeID;

			if($arFields['DISCOUNT_TYPE_ID'] === \Bitrix\Crm\Discount::MONETARY)
			{
				$arFields['DISCOUNT_SUM'] = isset($arFields['DISCOUNT_SUM'])
					? round(doubleval($arFields['DISCOUNT_SUM']), 2)
					: (isset($arParams['DISCOUNT_SUM']) ? round(doubleval($arParams['DISCOUNT_SUM']), 2) : 0.0);

				$arFields['DISCOUNT_RATE'] = \Bitrix\Crm\Discount::calculateDiscountRate(
					($arFields['PRICE'] + $arFields['DISCOUNT_SUM']),
					$arFields['PRICE']
				);
			}
			elseif($arFields['DISCOUNT_TYPE_ID'] === \Bitrix\Crm\Discount::PERCENTAGE)
			{
				$arFields['DISCOUNT_RATE'] = isset($arFields['DISCOUNT_RATE'])
					? round(doubleval($arFields['DISCOUNT_RATE']), 2)
					: (isset($arParams['DISCOUNT_RATE']) ? round(doubleval($arParams['DISCOUNT_RATE']), 2) : 0.0);

				$arFields['DISCOUNT_SUM'] = round(\Bitrix\Crm\Discount::calculateDiscountSum(
					$arFields['PRICE'],
					$arFields['DISCOUNT_RATE']
				), 2);
			}
			else
			{
				$arFields['DISCOUNT_SUM'] = $arFields['DISCOUNT_RATE'] = 0.0;
			}
		}

		if(isset($arFields['MEASURE_CODE']))
		{
			$arFields['MEASURE_CODE'] = isset($arFields['MEASURE_CODE']) ? intval($arFields['MEASURE_CODE']) : 0;
			$arFields['MEASURE_NAME'] = isset($arFields['MEASURE_NAME']) ? $arFields['MEASURE_NAME'] : '';

			if($arFields['MEASURE_CODE'] <= 0)
			{
				unset($arFields['MEASURE_CODE']);
				unset($arFields['MEASURE_NAME']);
			}
			elseif($arFields['MEASURE_NAME'] === '')
			{
				$measureInfo = \Bitrix\Crm\Measure::getMeasureByCode($arFields['MEASURE_CODE']);
				if(is_array($measureInfo))
				{
					$arFields['MEASURE_NAME'] = $measureInfo['SYMBOL'];
				}
			}
		}
		else
		{
			unset($arFields['MEASURE_NAME']);
		}

		if(isset($arFields['TAX_RATE']))
		{
			$arFields['TAX_RATE'] = round(doubleval($arFields['TAX_RATE']), 2);
		}

		if($arFields['TAX_INCLUDED'])
		{
			$arFields['TAX_INCLUDED'] = strtoupper($arFields['TAX_INCLUDED']) === 'Y' ? 'Y' : 'N';
		}

		if($arFields['CUSTOMIZED'])
		{
			$arFields['CUSTOMIZED'] = strtoupper($arFields['CUSTOMIZED']) === 'Y' ? 'Y' : 'N';
		}

		$sUpdate = trim($DB->PrepareUpdate(CCrmProductRow::TABLE_NAME, $arFields));
		if (!empty($sUpdate))
		{
			$sQuery = 'UPDATE '.CCrmProductRow::TABLE_NAME.' SET '.$sUpdate.' WHERE ID = '.$ID;
			$DB->Query($sQuery, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);

			CCrmEntityHelper::RemoveCached(self::CACHE_NAME, $ID);
		}

		if(isset($ownerType[0]) && $ownerID > 0)
		{
			// Update list of taxes
			self::UpdateTotalInfo($ownerType, $ownerID);

			self::SynchronizeOwner($ownerType,$ownerID);

			if($regEvent)
			{
				self::RegisterUpdateEvent($ownerType, $ownerID, $arFields, $arParams, $checkPerms);
			}
		}
		return true;
	}

	public static function Delete($ID, $checkPerms = true, $regEvent = true)
	{
		global $DB;

		$ID = intval($ID);
		$arParams = self::GetByID($ID);
		if(!is_array($arParams))
		{
			self::RegisterError("Could not find CrmProductRow($ID).");
			return false;
		}

		if(!$DB->Query('DELETE FROM '.CCrmProductRow::TABLE_NAME.' WHERE ID = '.$ID, true))
		{
			self::RegisterError("Could not delete CrmProductRow($ID).");
			return false;
		}

		CCrmEntityHelper::RemoveCached(self::CACHE_NAME, $ID);
		if(isset($arParams['OWNER_TYPE']) && isset($arParams['OWNER_ID']))
		{
			// Update list of taxes
			self::UpdateTotalInfo($arParams['OWNER_TYPE'], $arParams['OWNER_ID']);

			self::SynchronizeOwner($arParams['OWNER_TYPE'], $arParams['OWNER_ID']);

			if($regEvent)
			{
				self::RegisterRemoveEvent($arParams['OWNER_TYPE'], $arParams['OWNER_ID'], $arParams, $checkPerms);
			}
		}

		return true;
	}
	// <-- CRUD

	// Service -->
	public static function GetFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'OWNER_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'OWNER_TYPE' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'PRODUCT_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'PRODUCT_NAME' => array('TYPE' => 'string'),
				'PRICE' => array('TYPE' => 'double'),
				'QUANTITY' => array('TYPE' => 'double'),
				'DISCOUNT_TYPE_ID' => array('TYPE' => 'integer'),
				'DISCOUNT_RATE' => array('TYPE' => 'double'),
				'DISCOUNT_SUM' => array('TYPE' => 'double'),
				'TAX_RATE' => array('TYPE' => 'double'),
				'TAX_INCLUDED' => array('TYPE' => 'char'),
				'CUSTOMIZED' => array('TYPE' => 'char'),
				'MEASURE_CODE' => array('TYPE' => 'integer'),
				'MEASURE_NAME' => array('TYPE' => 'string'),
				'SORT' => array('TYPE' => 'integer')
			);
		}

		return self::$FIELD_INFOS;
	}

	protected static function GetFields()
	{
		return array(
			'ID' => array('FIELD' => 'PR.ID', 'TYPE' => 'int'),
			'OWNER_ID' => array('FIELD' => 'PR.OWNER_ID', 'TYPE' => 'int'),
			'OWNER_TYPE' => array('FIELD' => 'PR.OWNER_TYPE', 'TYPE' => 'string'),
			'PRODUCT_ID' => array('FIELD' => 'PR.PRODUCT_ID', 'TYPE' => 'int'),
			'PRODUCT_NAME' => array('FIELD' => 'PR.PRODUCT_NAME', 'TYPE' => 'string'),
			'ORIGINAL_PRODUCT_NAME' => array('FIELD' => 'E.NAME', 'TYPE' => 'string', 'FROM' => 'LEFT OUTER JOIN b_iblock_element E ON PR.PRODUCT_ID = E.ID'),
			'PRODUCT_DESCRIPTION' => array('FIELD' => 'E.DETAIL_TEXT', 'TYPE' => 'string', 'FROM' => 'LEFT OUTER JOIN b_iblock_element E ON PR.PRODUCT_ID = E.ID'),
			'PRICE' => array('FIELD' => 'PR.PRICE', 'TYPE' => 'double'),
			'PRICE_ACCOUNT' => array('FIELD' => 'PR.PRICE_ACCOUNT', 'TYPE' => 'double'),
			'QUANTITY' => array('FIELD' => 'PR.QUANTITY', 'TYPE' => 'double'),
			'DISCOUNT_TYPE_ID' => array('FIELD' => 'PR.DISCOUNT_TYPE_ID', 'TYPE' => 'int'),
			'DISCOUNT_RATE' => array('FIELD' => 'PR.DISCOUNT_RATE', 'TYPE' => 'double'),
			'DISCOUNT_SUM' => array('FIELD' => 'PR.DISCOUNT_SUM', 'TYPE' => 'double'),
			'TAX_RATE' => array('FIELD' => 'PR.TAX_RATE', 'TYPE' => 'double'),
			'TAX_INCLUDED' => array('FIELD' => 'PR.TAX_INCLUDED', 'TYPE' => 'char'),
			'CUSTOMIZED' => array('FIELD' => 'PR.CUSTOMIZED', 'TYPE' => 'char'),
			'MEASURE_CODE' => array('FIELD' => 'PR.MEASURE_CODE', 'TYPE' => 'int'),
			'MEASURE_NAME' => array('FIELD' => 'PR.MEASURE_NAME', 'TYPE' => 'string'),
			'SORT' => array('FIELD' => 'PR.SORT', 'TYPE' => 'int')
		);
	}

	//Check fields before ADD and UPDATE.
	private static function CheckFields($sAction, &$arFields, $ID)
	{
		if($sAction == 'ADD')
		{
			if (!isset($arFields['OWNER_ID']))
			{
				self::RegisterError('Could not find Owner ID.');
				return false;
			}

			if (!isset($arFields['OWNER_TYPE']))
			{
				self::RegisterError('Could not find Owner Type.');
				return false;
			}

			if (!isset($arFields['PRODUCT_ID']))
			{
				self::RegisterError('Could not find Product ID.');
				return false;
			}

			if (!isset($arFields['PRICE']))
			{
				self::RegisterError('Could not find Price.');
				return false;
			}

			if (!isset($arFields['QUANTITY']))
			{
				self::RegisterError('Could not find Quantity.');
				return false;
			}

			$discountTypeID = isset($arFields['DISCOUNT_TYPE_ID'])
				&& \Bitrix\Crm\Discount::isDefined($arFields['DISCOUNT_TYPE_ID'])
				? intval($arFields['DISCOUNT_TYPE_ID']) : \Bitrix\Crm\Discount::UNDEFINED;

			if($discountTypeID !== \Bitrix\Crm\Discount::UNDEFINED
				&& !\Bitrix\Crm\Discount::isDefined($discountTypeID))
			{
				self::RegisterError("Discount type ID (DISCOUNT_TYPE_ID) '{$discountTypeID}' is not supported in current context.");
			}
			else if($discountTypeID === \Bitrix\Crm\Discount::MONETARY && !isset($arFields['DISCOUNT_SUM']))
			{
				self::RegisterError("Discount Sum (DISCOUNT_SUM) is required if Monetary Discount Type (DISCOUNT_TYPE_ID) is defined.");
			}
			else if($discountTypeID === \Bitrix\Crm\Discount::PERCENTAGE && !isset($arFields['DISCOUNT_RATE']))
			{
				self::RegisterError("Discount Rate (DISCOUNT_RATE) is required if Percentage Discount Type (DISCOUNT_TYPE_ID) is defined.");
			}
		}
		else//if($sAction == 'UPDATE')
		{
			if(!self::Exists($ID))
			{
				self::RegisterError("Could not find Product Row($ID).");
				return false;
			}
		}

		return true;
	}


	public static function ResolveOwnerTypeName($ownerType)
	{
		$ownerType = strtoupper(strval($ownerType));

		if($ownerType === 'D')
		{
			return CCrmOwnerType::DealName;
		}
		elseif($ownerType === 'L')
		{
			return CCrmOwnerType::LeadName;
		}
		return '';
	}

	protected static function SynchronizeOwner($ownerType, $ownerID, $checkPerms = true, $totalInfo = array())
	{
		$ownerType = strtoupper(strval($ownerType));
		$ownerID = intval($ownerID);
		if (!is_array($totalInfo))
			$totalInfo = array();

		if($ownerType === 'D')
		{
			CCrmDeal::SynchronizeProductRows($ownerID, $checkPerms);
		}
		elseif($ownerType === CCrmQuote::OWNER_TYPE)
		{
			CCrmQuote::SynchronizeProductRows($ownerID, $checkPerms, $totalInfo);
		}
		elseif($ownerType === 'L')
		{
			CCrmLead::SynchronizeProductRows($ownerID);
		}
	}

	protected static function RegisterError($msg)
	{
		global $APPLICATION;
		$APPLICATION->ThrowException(new CAdminException(array(array('text' => $msg))));
		self::$LAST_ERROR = $msg;
	}
	// <-- Service

	// Contract -->
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		$lb = new CCrmEntityListBuilder(
			CCrmProductRow::DB_TYPE,
			CCrmProductRow::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields(),
			'',
			'',
			array()
		);

		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}

	public static function GetRowQuantity($ownerType, $ownerID)
	{
		$ownerType = strval($ownerType);
		$ownerID = intval($ownerID);

		return $ownerType !== '' && $ownerID > 0
			? self::GetList(array(), array('OWNER_TYPE' => $ownerType, 'OWNER_ID' => $ownerID), array())
			: 0;
	}

	public static function LoadRows($ownerType, $ownerID, $assoc = false)
	{
		$ownerType = strval($ownerType);
		$filter = array();

		if(isset($ownerType[0]))
		{
			$filter['OWNER_TYPE'] = $ownerType;
		}

		if(is_array($ownerID))
		{
			if(count($ownerID) > 0)
			{
				$filter['@OWNER_ID'] = $ownerID;
			}
		}
		else
		{
			$ownerID = intval($ownerID);
			if($ownerID > 0)
			{
				$filter['OWNER_ID'] = $ownerID;
			}
		}

		$measurelessProductIDs = array();
		$dbRes = self::GetList(array('SORT' => 'ASC', 'ID'=>'ASC'), $filter);
		$results = array();
		while($ary = $dbRes->Fetch())
		{
			$productID = $ary['PRODUCT_ID'] = isset($ary['PRODUCT_ID']) ? intval($ary['PRODUCT_ID']) : 0;

			$ary['QUANTITY'] = isset($ary['QUANTITY']) ? round(doubleval($ary['QUANTITY']), 4) : 0.0;
			$ary['PRICE'] = isset($ary['PRICE']) ? round(doubleval($ary['PRICE']), 2) : 0.0;
			$ary['DISCOUNT_TYPE_ID'] = isset($ary['DISCOUNT_TYPE_ID'])
				? intval($ary['DISCOUNT_TYPE_ID']) : \Bitrix\Crm\Discount::UNDEFINED;
			$ary['DISCOUNT_RATE'] = isset($ary['DISCOUNT_RATE']) ? round(doubleval($ary['DISCOUNT_RATE']), 2) : 0.0;
			$ary['DISCOUNT_SUM'] = isset($ary['DISCOUNT_SUM']) ? round(doubleval($ary['DISCOUNT_SUM']), 2) : 0.0;

			$ary['TAX_RATE'] = isset($ary['TAX_RATE']) ? round(doubleval($ary['TAX_RATE']), 2) : 0.0;
			$ary['TAX_INCLUDED'] = isset($ary['TAX_INCLUDED']) ? $ary['TAX_INCLUDED'] : 'N';
			$ary['CUSTOMIZED'] = isset($ary['CUSTOMIZED']) ? $ary['CUSTOMIZED'] : 'N';

			$ary['SORT'] = isset($ary['SORT']) ? intval($ary['SORT']) : 0;

			$ary['MEASURE_CODE'] = isset($ary['MEASURE_CODE']) ? intval($ary['MEASURE_CODE']) : 0;
			$ary['MEASURE_NAME'] = isset($ary['MEASURE_NAME']) ? $ary['MEASURE_NAME'] : '';

			if($productID > 0 && $ary['MEASURE_CODE'] <= 0)
			{
				if(!in_array($productID, $measurelessProductIDs, true))
				{
					$measurelessProductIDs[] = $productID;
				}
			}

			if(!isset($ary['PRODUCT_NAME']) || $ary['PRODUCT_NAME'] === '')
			{
				if($ary['PRODUCT_ID'] > 0 && isset($ary['ORIGINAL_PRODUCT_NAME']))
				{
					$ary['PRODUCT_NAME'] = $ary['ORIGINAL_PRODUCT_NAME'];
				}
				elseif(!isset($ary['PRODUCT_NAME']))
				{
					$ary['PRODUCT_NAME'] = '';
				}
			}

			if($assoc)
			{
				$results[intval($ary['ID'])] = $ary;
			}
			else
			{
				$results[] = $ary;
			}
		}

		if(!empty($measurelessProductIDs))
		{
			$defaultMeasureInfo = \Bitrix\Crm\Measure::getDefaultMeasure();
			$measureInfos = \Bitrix\Crm\Measure::getProductMeasures($measurelessProductIDs);
			foreach($results as &$result)
			{
				if($result['MEASURE_CODE'] > 0)
				{
					continue;
				}

				$productID = $result['PRODUCT_ID'];
				if(isset($measureInfos[$productID]) && !empty($measureInfos[$productID]))
				{
					$measureInfo = $measureInfos[$productID][0];
					$result['MEASURE_CODE'] = $measureInfo['CODE'];
					$result['MEASURE_NAME'] = $measureInfo['SYMBOL'];
				}
				elseif($defaultMeasureInfo !== null)
				{
					$result['MEASURE_CODE'] = $defaultMeasureInfo['CODE'];
					$result['MEASURE_NAME'] = $defaultMeasureInfo['SYMBOL'];
				}
			}
			unset($result);
		}

		return $results;
	}

	public static function SaveRows($ownerType, $ownerID, $arRows, $accountContext = null, $checkPerms = true, $regEvent = true, $syncOwner = true, $totalInfo = array())
	{
		$ownerType = strval($ownerType);
		$ownerID = intval($ownerID);

		if(!isset($ownerType[0]) || $ownerID <= 0 || !is_array($arRows))
		{
			self::RegisterError('Invalid arguments are supplied.');
			return false;
		}

		if (!is_array($totalInfo))
			$totalInfo = array();

		$owner = null;
		if (!is_array($accountContext))
		{
			if($ownerType === 'D')
			{
				$owner = CCrmDeal::GetByID($ownerID, $checkPerms);
			}
			elseif($ownerType === CCrmQuote::OWNER_TYPE)
			{
				$owner = CCrmQuote::GetByID($ownerID);
			}
			elseif($ownerType === 'L')
			{
				$owner = CCrmLead::GetByID($ownerID);
			}
		}

		// Preparing accounting context -->
		if(!is_array($accountContext))
		{
			$accountContext = array();

			if(is_array($owner))
			{
				if(isset($owner['CURRENCY_ID']))
				{
					$accountContext['CURRENCY_ID'] = $owner['CURRENCY_ID'];
				}

				if(isset($owner['EXCH_RATE']))
				{
					$accountContext['EXCH_RATE'] = $owner['EXCH_RATE'];
				}
			}
		}

		$currencyID = isset($accountContext['CURRENCY_ID'])
			? $accountContext['CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();

		$exchRate = isset($accountContext['EXCH_RATE'])
			? $accountContext['EXCH_RATE'] : null;
		// <-- Preparing accounting context

		$productIDs = array();
		$products = array();
		foreach($arRows as &$arRow)
		{
			$productID = isset($arRow['PRODUCT_ID']) ? intval($arRow['PRODUCT_ID']) : 0;
			if($productID > 0 && !in_array($productID, $productIDs, true))
			{
				$productIDs[] = $productID;
			}
		}
		unset($arRow);

		if(!empty($productIDs))
		{
			$dbProduct = CCrmProduct::GetList(
				array(),
				array('ID' => $productIDs),
				array('ID', 'NAME')
			);
			if(is_object($dbProduct))
			{
				while($product = $dbProduct->Fetch())
				{
					$products[intval($product['ID'])] = $product;
				}
			}
		}

		$measurelessProductIDs = array();
		$arSafeRows = array();
		foreach($arRows as &$arRow)
		{
			//$rowID = isset($arRow['ID']) ? intval($arRow['ID']) : 0;
			$productID = $arRow['PRODUCT_ID'] = isset($arRow['PRODUCT_ID']) ? intval($arRow['PRODUCT_ID']) : 0;
			$productName = $arRow['PRODUCT_NAME'] = isset($arRow['PRODUCT_NAME']) ? $arRow['PRODUCT_NAME'] : '';
			$arRow['PRICE'] = isset($arRow['PRICE']) ? round(doubleval($arRow['PRICE']), 2) : 0.0;
			$arRow['QUANTITY'] = isset($arRow['QUANTITY']) ? round(doubleval($arRow['QUANTITY']), 4) : 1;
			$arRow['TAX_RATE'] = isset($arRow['TAX_RATE']) ? round(doubleval($arRow['TAX_RATE']), 2) : 0.0;
			$arRow['TAX_INCLUDED'] = isset($arRow['TAX_INCLUDED']) ? ($arRow['TAX_INCLUDED'] === 'Y' ? 'Y' : 'N') : 'N';
			$arRow['MEASURE_CODE'] = isset($arRow['MEASURE_CODE']) ? intval($arRow['MEASURE_CODE']) : 0;
			$arRow['MEASURE_NAME'] = isset($arRow['MEASURE_NAME']) ? $arRow['MEASURE_NAME'] : '';
			$arRow['CUSTOMIZED'] = isset($arRow['CUSTOMIZED']) && strtoupper($arRow['CUSTOMIZED']) === 'Y' ? 'Y' : 'N';
			$arRow['SORT'] = isset($arRow['SORT']) ? intval($arRow['SORT']) : 0;

			if($productID > 0 && $productName !== '' && isset($products[$productID]))
			{
				//Skip save product name if it equal to original
				$product = $products[$productID];
				$originalProductName = isset($product['NAME']) ? trim($product['NAME']) : '';
				if($productName === $originalProductName)
				{
					$productName = '';
				}
			}

			$price = $arRow['PRICE'];

			$arRow['DISCOUNT_TYPE_ID'] = isset($arRow['DISCOUNT_TYPE_ID']) ? intval($arRow['DISCOUNT_TYPE_ID']) : 0;
			if(!\Bitrix\Crm\Discount::isDefined($arRow['DISCOUNT_TYPE_ID']))
			{
				$arRow['DISCOUNT_TYPE_ID'] = \Bitrix\Crm\Discount::PERCENTAGE;
				$arRow['DISCOUNT_RATE'] = 0.0;
			}
			$discountTypeID = $arRow['DISCOUNT_TYPE_ID'];

			if($discountTypeID === \Bitrix\Crm\Discount::PERCENTAGE)
			{
				if(!isset($arRow['DISCOUNT_RATE']))
				{
					self::RegisterError("Discount Rate (DISCOUNT_RATE) is required if Percentage Discount Type (DISCOUNT_TYPE_ID) is defined.");
					return false;
				}
				$discountRate = round(doubleval($arRow['DISCOUNT_RATE']), 2);

				if(isset($arRow['DISCOUNT_SUM']))
				{
					$discountSum = round(doubleval($arRow['DISCOUNT_SUM']), 2);
				}
				else
				{
					$exclusivePrice = self::CalculateExclusivePrice($price, $arRow['TAX_RATE']);
					$discountSum = round(\Bitrix\Crm\Discount::calculateDiscountSum($exclusivePrice, $discountRate), 2);
				}
			}
			else//if($discountTypeID === \Bitrix\Crm\Discount::MONETARY)
			{
				if(!isset($arRow['DISCOUNT_SUM']))
				{
					self::RegisterError("Discount Sum (DISCOUNT_SUM) is required if Monetary Discount Type (DISCOUNT_TYPE_ID) is defined.");
					return false;
				}
				$discountSum = round(doubleval($arRow['DISCOUNT_SUM']), 2);

				if(isset($arRow['DISCOUNT_RATE']))
				{
					$discountRate = round(doubleval($arRow['DISCOUNT_RATE']), 2);
				}
				else
				{
					$exclusivePrice = self::CalculateExclusivePrice($price, $arRow['TAX_RATE']);
					$discountRate = \Bitrix\Crm\Discount::calculateDiscountRate(($exclusivePrice + $discountSum), $exclusivePrice);
				}
			}

			$measureCode = $arRow['MEASURE_CODE'];
			if($productID > 0 && $measureCode <= 0)
			{
				if(!in_array($productID, $measurelessProductIDs, true))
				{
					$measurelessProductIDs[] = $productID;
				}
			}

			$safeRow = array(
				//'ID' => $rowID,
				'OWNER_TYPE' => $ownerType,
				'OWNER_ID' => $ownerID,
				'PRODUCT_ID' => $productID,
				'PRODUCT_NAME' => $productName,
				'PRICE' => $price,
				'QUANTITY'=> $arRow['QUANTITY'],
				'DISCOUNT_TYPE_ID' => $discountTypeID,
				'DISCOUNT_SUM' => $discountSum,
				'DISCOUNT_RATE' => $discountRate,
				'TAX_RATE' => $arRow['TAX_RATE'],
				'TAX_INCLUDED' => isset($arRow['TAX_INCLUDED']) ? ($arRow['TAX_INCLUDED'] === 'Y' ? 'Y' : 'N') : 'N',
				'MEASURE_CODE' => $measureCode,
				'MEASURE_NAME' => $arRow['MEASURE_NAME'],
				'CUSTOMIZED' => 'Y', //Is always enabled for disable requests to product catalog
				'SORT' => $arRow['SORT']
			);

			$accData = CCrmAccountingHelper::PrepareAccountingData(
				array(
					'CURRENCY_ID' => $currencyID,
					'SUM' => $safeRow['PRICE'],
					'EXCH_RATE' => $exchRate
				)
			);

			if(is_array($accData))
			{
				$safeRow['PRICE_ACCOUNT'] = $accData['ACCOUNT_SUM'];
			}

			$arSafeRows[] = &$safeRow;
			unset($safeRow);
		}
		unset($arRow);

		if(!empty($measurelessProductIDs))
		{
			$defaultMeasureInfo = \Bitrix\Crm\Measure::getDefaultMeasure();
			$measureInfos = \Bitrix\Crm\Measure::getProductMeasures($measurelessProductIDs);
			foreach($arSafeRows as &$safeRow)
			{
				if($safeRow['MEASURE_CODE'] > 0)
				{
					continue;
				}

				$productID = $safeRow['PRODUCT_ID'];
				if(isset($measureInfos[$productID]) && !empty($measureInfos[$productID]))
				{
					$measureInfo = $measureInfos[$productID][0];
					$safeRow['MEASURE_CODE'] = $measureInfo['CODE'];
					$safeRow['MEASURE_NAME'] = $measureInfo['SYMBOL'];
				}
				elseif($defaultMeasureInfo !== null)
				{
					$safeRow['MEASURE_CODE'] = $defaultMeasureInfo['CODE'];
					$safeRow['MEASURE_NAME'] = $defaultMeasureInfo['SYMBOL'];
				}
			}
			unset($safeRow);
		}

		$arPresentRows = self::LoadRows($ownerType, $ownerID, true);

		// Registering events -->
		if($regEvent)
		{
			$arRowIDs = array();
			foreach($arRows as &$arRow)
			{
				if(isset($arRow['ID']))
				{
					$arRowIDs[] = intval($arRow['ID']);
				}

				$rowID = isset($arRow['ID']) ? intval($arRow['ID']) : 0;
				if($rowID <= 0)
				{
					// Row was added
					self::RegisterAddEvent($ownerType, $ownerID, $arRow, $checkPerms);
					continue;
				}

				$arPresentRow = isset($arPresentRows[$rowID]) ? $arPresentRows[$rowID] : null;
				if($arPresentRow)
				{
					// Row was modified
					self::RegisterUpdateEvent($ownerType, $ownerID, $arRow, $arPresentRow, $checkPerms);
				}
			}
			unset($arRow);

			foreach($arPresentRows as $rowID => &$arPresentRow)
			{
				if(!in_array($rowID, $arRowIDs, true))
				{
					// Product  was removed
					self::RegisterRemoveEvent($ownerType, $ownerID, $arPresentRow, $checkPerms);
				}
			}
		}
		// <-- Registering events

		$result = CCrmProductRow::DoSaveRows($ownerType, $ownerID, $arSafeRows);

		// Update list of taxes
		if (!isset($totalInfo['CURRENCY_ID']))
			$totalInfo['CURRENCY_ID'] = $currencyID;
		self::UpdateTotalInfo($ownerType, $ownerID, $totalInfo);

		// Disable sum synchronization if product rows are empty
		if($result && $syncOwner && (count($arPresentRows) > 0 || count($arSafeRows) > 0))
		{
			self::SynchronizeOwner($ownerType, $ownerID, $checkPerms, $totalInfo);
		}
		return $result;
	}

	protected static function UpdateTotalInfo($ownerType, $ownerID, $totalInfo = array())
	{
		$result = array();
		
		if (!is_array($totalInfo))
			$totalInfo = array();

		$taxMode = isset($totalInfo['TAX_MODE']) ? intval($totalInfo['TAX_MODE']) : 0;
		if ($taxMode !== self::TAX_MODE && $taxMode !== self::LD_TAX_MODE)
			$taxMode = CCrmTax::isVatMode() ? self::TAX_MODE : self::LD_TAX_MODE;

		$taxList = null;
		if (is_array($totalInfo['TAX_LIST']))
			$taxList = $totalInfo['TAX_LIST'];
		else
		{
			$owner = null;
			if (!isset($totalInfo['CURRENCY']) || !isset($totalInfo['PERSON_TYPE_ID']))
			{
				if($ownerType === 'D')
				{
					$owner = CCrmDeal::GetByID($ownerID, false);
				}
				elseif($ownerType === CCrmQuote::OWNER_TYPE)
				{
					$owner = CCrmQuote::GetByID($ownerID, false);
				}
				elseif($ownerType === 'L')
				{
					$owner = CCrmLead::GetByID($ownerID, false);
				}
			}

			// Determine person type
			$personTypeID = 0;
			$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
			if (isset($arPersonTypes['COMPANY']) && isset($arPersonTypes['CONTACT']))
			{
				if (!isset($totalInfo['PERSON_TYPE_ID']))
				{
					if (intval($owner['COMPANY_ID']) > 0)
						$personTypeID = intval($arPersonTypes['COMPANY']);
					elseif (intval($owner['CONTACT_ID']) > 0)
						$personTypeID = intval($arPersonTypes['CONTACT']);
				}
				else
					$personTypeID = intval($totalInfo['PERSON_TYPE_ID']);

				if ($personTypeID !== intval($arPersonTypes['COMPANY'])
					&& $personTypeID !== intval($arPersonTypes['CONTACT']))
				{
					$personTypeID = 0;
				}
			}

			$currencyID = '';
			if (isset($totalInfo['CURRENCY_ID']))
				$currencyID = $totalInfo['CURRENCY_ID'];
			if (empty($currencyID) && !empty($owner['CURRENCY_ID']))
				$currencyID = $owner['CURRENCY_ID'];
			if (empty($currencyID))
				$currencyID = CCrmCurrency::GetBaseCurrencyID();

			$locationID = 0;
			if (isset($totalInfo['LOCATION_ID']))
				$locationID = intval($totalInfo['LOCATION_ID']);
			else if (isset($owner['LOCATION_ID']))
				$locationID = intval($owner['LOCATION_ID']);

			$enableSaleDiscount = false;
			$siteID = '';
			if (!defined("SITE_ID"))
			{
				$obSite = CSite::GetList($by = "def", $order = "desc", array("ACTIVE" => "Y"));
				if ($obSite && $arSite = $obSite->Fetch())
					$siteID= $arSite["LID"];
				unset($obSite, $arSite);
			}
			else
			{
				$siteID = SITE_ID;
			}

			$arRows = self::LoadRows($ownerType, $ownerID, true);

			$calculateOptions = array();
			if ($taxMode === self::LD_TAX_MODE)
				$calculateOptions['LOCATION_ID'] = $locationID;
			$arResult = CCrmSaleHelper::Calculate($arRows, $currencyID, $personTypeID, $enableSaleDiscount, $siteID, $calculateOptions);

			if (is_array($arResult['TAX_LIST']))
				$taxList = $arResult['TAX_LIST'];
		}

		$settings = CCrmProductRow::LoadSettings($ownerType, $ownerID);
		$settings["TAX_MODE"] = $taxMode;
		if (is_array($taxList))
			$settings['TAX_LIST'] = $taxList;
		CCrmProductRow::SaveSettings($ownerType, $ownerID, $settings);

		return $result;
	}

	public static function LoadTotalInfo($ownerType, $ownerID)
	{
		$result = array();

		$settings = CCrmProductRow::LoadSettings($ownerType, $ownerID);

		$taxMode = isset($settings['TAX_MODE']) ? intval($settings['TAX_MODE']) : 0;
		if ($taxMode !== self::TAX_MODE && $taxMode !== self::LD_TAX_MODE)
			$taxMode = CCrmTax::isVatMode() ? self::TAX_MODE : self::LD_TAX_MODE;

		$result['TAX_MODE'] = $taxMode;

		if (is_array($settings['TAX_LIST']))
			$result['TAX_LIST'] = $settings['TAX_LIST'];

		return $result;
	}

	protected static function PrepareAccountingContext($ownerType, $ownerID)
	{
		$result = array();
		$owner = null;
		if($ownerType === 'D')
		{
			$owner = CCrmDeal::GetByID($ownerID, false);
		}
		elseif($ownerType === CCrmQuote::OWNER_TYPE)
		{
			$owner = CCrmQuote::GetByID($ownerID, false);
		}
		elseif($ownerType === 'L')
		{
			$owner = CCrmLead::GetByID($ownerID, false);
		}

		if(is_array($owner))
		{
			if(isset($owner['CURRENCY_ID']))
			{
				$result['CURRENCY_ID'] = $owner['CURRENCY_ID'];
			}

			if(isset($owner['EXCH_RATE']))
			{
				$result['EXCH_RATE'] = $owner['EXCH_RATE'];
			}
		}

		return $result;
	}

	public static function NormalizeProductName($productID, $productName)
	{
		$result = $productName;

		if($productID > 0 && empty($productName))
		{
			$result = CCrmProduct::GetProductName($productID);
			if (empty($result))
				$result = '['.$productID.']';
		}

		return $result;
	}

	private static function RegisterAddEvent($ownerType, $ownerID, $arRow, $checkPerms)
	{
		IncludeModuleLangFile(__FILE__);

		$productID = isset($arRow['PRODUCT_ID']) ? intval($arRow['PRODUCT_ID']) : 0;
		$productName = isset($arRow['PRODUCT_NAME']) ? $arRow['PRODUCT_NAME'] : '';
		$productName = self::NormalizeProductName($productID, $productName);

		$arFields = array(
			'EVENT_NAME' => GetMessage('CRM_EVENT_PROD_ROW_ADD'),
			'EVENT_TEXT_1' => $productName,
			'EVENT_TEXT_2' => ''
		);

		return self::RegisterEvents($ownerType, $ownerID, array($arFields), $checkPerms);
	}

	private static function RegisterUpdateEvent($ownerType, $ownerID, $arRow, $arPresentRow, $checkPerms)
	{
		IncludeModuleLangFile(__FILE__);

		$productID = isset($arRow['PRODUCT_ID']) ? intval($arRow['PRODUCT_ID']) : 0;
		$productName = isset($arRow['PRODUCT_NAME']) ? $arRow['PRODUCT_NAME'] : '';
		$productName = self::NormalizeProductName($productID, $productName);
		$presentProductID = isset($arPresentRow['PRODUCT_ID']) ? intval($arPresentRow['PRODUCT_ID']) : 0;
		$presentproductName = isset($arPresentRow['PRODUCT_NAME']) ? $arPresentRow['PRODUCT_NAME'] : '';
		$presentproductName = self::NormalizeProductName($presentProductID, $presentproductName);

		$arEvents = array();
		if($arPresentRow['PRODUCT_ID'] !== $arRow['PRODUCT_ID'])
		{
			// Product was changed
			$arEvents[] = array(
				'EVENT_NAME' => GetMessage('CRM_EVENT_PROD_ROW_UPD'),
				'EVENT_TEXT_1' => $presentproductName,
				'EVENT_TEXT_2' => $productName
			);
		}
		else
		{
			if($arRow['PRODUCT_ID'] === 0)
			{
				$nameChanged = $arRow['PRODUCT_NAME'] !== $arPresentRow['PRODUCT_NAME'];
			}
			else
			{
				//If PRODUCT_NAME is not emty - user set custom name
				$nameChanged = ($arRow['PRODUCT_NAME'] !== '' && $arRow['PRODUCT_NAME'] !== $arPresentRow['PRODUCT_NAME'])
					|| ($arRow['PRODUCT_NAME'] === '' && $arPresentRow['PRODUCT_NAME'] !== $arPresentRow['ORIGINAL_PRODUCT_NAME']);
			}

			if($nameChanged)
			{
				// Product name was changed
				$arEvents[] = array(
					'EVENT_NAME' => GetMessage('CRM_EVENT_PROD_ROW_NAME_UPD'),
					'EVENT_TEXT_1' => $arPresentRow['PRODUCT_NAME'],
					'EVENT_TEXT_2' => $arRow['PRODUCT_NAME'] !== '' ? $arRow['PRODUCT_NAME'] : $arPresentRow['ORIGINAL_PRODUCT_NAME']
				);
			}

			$productName = $arRow['PRODUCT_NAME'];
			if($productName === '' && $arRow['PRODUCT_ID'] > 0)
			{
				$productName = $arPresentRow['ORIGINAL_PRODUCT_NAME'];
			}

			if($arPresentRow['PRICE'] !== $arRow['PRICE'])
			{
				// Product price was changed
				$arEvents[] = array(
					'EVENT_NAME' => GetMessage('CRM_EVENT_PROD_ROW_PRICE_UPD', array('#NAME#' => $productName)),
					'EVENT_TEXT_1' => $arPresentRow['PRICE'],
					'EVENT_TEXT_2' => $arRow['PRICE']
				);
			}

			if($arPresentRow['QUANTITY'] !== $arRow['QUANTITY'])
			{
				// Product  quantity was changed
				$arEvents[] = array(
					'EVENT_NAME' => GetMessage('CRM_EVENT_PROD_ROW_QTY_UPD', array('#NAME#' => $productName)),
					'EVENT_TEXT_1' => $arPresentRow['QUANTITY'],
					'EVENT_TEXT_2' => $arRow['QUANTITY']
				);
			}

			$discountSum = round(doubleval($arRow['DISCOUNT_SUM']), 2);
			$presentDiscountSum = round(doubleval($arPresentRow['DISCOUNT_SUM']), 2);
			if($discountSum !== $presentDiscountSum)
			{
				// Product  discount was changed
				$arEvents[] = array(
					'EVENT_NAME' => GetMessage('CRM_EVENT_PROD_ROW_DISCOUNT_UPD', array('#NAME#' => $productName)),
					'EVENT_TEXT_1' => $presentDiscountSum,
					'EVENT_TEXT_2' => $discountSum
				);
			}
			unset($discountSum, $presentDiscountSum);

			if($arPresentRow['TAX_RATE'] !== $arRow['TAX_RATE'])
			{
				// Product  tax was changed
				$arEvents[] = array(
					'EVENT_NAME' => GetMessage('CRM_EVENT_PROD_ROW_TAX_UPD', array('#NAME#' => $productName)),
					'EVENT_TEXT_1' => "{$arPresentRow['TAX_RATE']}%",
					'EVENT_TEXT_2' => "{$arRow['TAX_RATE']}%"
				);
			}

			if($arPresentRow['MEASURE_NAME'] !== $arRow['MEASURE_NAME'])
			{
				// Product  measure was changed
				$arEvents[] = array(
					'EVENT_NAME' => GetMessage('CRM_EVENT_PROD_ROW_MEASURE_UPD', array('#NAME#' => $productName)),
					'EVENT_TEXT_1' => $arPresentRow['MEASURE_NAME'],
					'EVENT_TEXT_2' => $arRow['MEASURE_NAME']
				);
			}
		}

		return count($arEvents) > 0 ? self::RegisterEvents($ownerType, $ownerID, $arEvents, $checkPerms) : false;
	}

	private static function RegisterRemoveEvent($ownerType, $ownerID, $arPresentRow, $checkPerms)
	{
		IncludeModuleLangFile(__FILE__);

		$productID = isset($arPresentRow['PRODUCT_ID']) ? intval($arPresentRow['PRODUCT_ID']) : 0;
		$productName = isset($arPresentRow['PRODUCT_NAME']) ? $arPresentRow['PRODUCT_NAME'] : '';
		$productName = self::NormalizeProductName($productID, $productName);

		$arFields = array(
			'EVENT_NAME' => GetMessage('CRM_EVENT_PROD_ROW_REM'),
			'EVENT_TEXT_1' => $productName,
			'EVENT_TEXT_2' => ''
		);

		return self::RegisterEvents($ownerType, $ownerID, array($arFields), $checkPerms);
	}

	private static function RegisterEvents($ownerType, $ownerID, $arEvents, $checkPerms)
	{
		global $USER;
		$userID = isset($USER) && ($USER instanceof CUser) && ('CUser' === get_class($USER)) ? $USER->GetId() : 0;

		$CCrmEvent = new CCrmEvent();
		foreach($arEvents as $arEvent)
		{
			$arEvent['EVENT_TYPE'] = 1;
			$arEvent['ENTITY_TYPE'] = CCrmOwnerTypeAbbr::ResolveName($ownerType);
			$arEvent['ENTITY_ID'] = $ownerID;
			$arEvent['ENTITY_FIELD'] = 'PRODUCT_ROWS';

			if($userID > 0)
			{
				$arEvent['USER_ID']  = $userID;
			}

			$CCrmEvent->Add($arEvent, $checkPerms);
		}

		return true;
	}

	public static function GetByID($ID)
	{
		$ID = intval($ID);

		$arResult = CCrmEntityHelper::GetCached(self::CACHE_NAME, $ID);
		if (is_array($arResult))
		{
			return $arResult;
		}

		$dbRes = CCrmProductRow::GetList(array(), array('ID' => $ID));
		$arResult = $dbRes->Fetch();

		if(is_array($arResult))
		{
			CCrmEntityHelper::SetCached(self::CACHE_NAME, $ID, $arResult);

			if(isset($arResult['OWNER_TYPE']))
			{
				// Remove space padding of CHAR column
				$arResult['OWNER_TYPE'] = trim($arResult['OWNER_TYPE']);
			}

			$productID = $arResult['PRODUCT_ID'] = intval($arResult['PRODUCT_ID']);
			$arResult['PRICE'] = round(doubleval($arResult['PRICE']), 2);
			$arResult['QUANTITY'] = round(doubleval($arResult['QUANTITY']), 4);

			$arResult['DISCOUNT_TYPE_ID'] = isset($arResult['DISCOUNT_TYPE_ID'])
				? intval($arResult['DISCOUNT_TYPE_ID']) : \Bitrix\Crm\Discount::UNDEFINED;
			$arResult['DISCOUNT_RATE'] = isset($arResult['DISCOUNT_RATE']) ? round(doubleval($arResult['DISCOUNT_RATE']), 2) : 0.0;
			$arResult['DISCOUNT_SUM'] = isset($arResult['DISCOUNT_SUM']) ? round(doubleval($arResult['DISCOUNT_SUM']), 2) : 0.0;

			$arResult['TAX_RATE'] = isset($arResult['TAX_RATE']) ? round(doubleval($arResult['TAX_RATE']), 2) : 0.0;
			$arResult['TAX_INCLUDED'] = isset($arResult['TAX_INCLUDED']) ? $arResult['DISCOUNT_SUM'] : 'N';
			$arResult['CUSTOMIZED'] = isset($arResult['CUSTOMIZED']) ? $arResult['CUSTOMIZED'] : 'N';

			$arResult['MEASURE_CODE'] = isset($arResult['MEASURE_CODE']) ? intval($arResult['MEASURE_CODE']) : 0;
			$arResult['MEASURE_NAME'] = isset($arResult['MEASURE_NAME']) ? $arResult['MEASURE_NAME'] : '';

			if($productID > 0 && $arResult['MEASURE_CODE'] <= 0)
			{
				$defaultMeasureInfo = \Bitrix\Crm\Measure::getDefaultMeasure();
				$measureInfos = \Bitrix\Crm\Measure::getProductMeasures($productID);

				if(isset($measureInfos[$productID]) && !empty($measureInfos[$productID]))
				{
					$measureInfo = $measureInfos[$productID][0];
					$result['MEASURE_CODE'] = $measureInfo['CODE'];
					$result['MEASURE_NAME'] = $measureInfo['SYMBOL'];
				}
				elseif($defaultMeasureInfo !== null)
				{
					$result['MEASURE_CODE'] = $defaultMeasureInfo['CODE'];
					$result['MEASURE_NAME'] = $defaultMeasureInfo['SYMBOL'];
				}
			}
		}

		return $arResult;
	}

	public static function Exists($ID)
	{
		$dbRes = CCrmProductRow::GetList(array(), array('ID'=> $ID), false, false, array('ID'));
		return $dbRes->Fetch() ? true : false;
	}

	public static function CalculateTotalInfo($ownerType, $ownerID, $checkPerms = true, $params = null, $rows = null, $totalInfo = array())
	{
		if (!is_array($totalInfo))
		{
			$totalInfo = array();
		}

		$result = false;
		if (isset($totalInfo['OPPORTUNITY']) && isset($totalInfo['TAX_VALUE']))
		{
			$result = array(
				'OPPORTUNITY' => round(doubleval($totalInfo['OPPORTUNITY']), 2),
				'TAX_VALUE' => round(doubleval($totalInfo['TAX_VALUE']), 2)
			);
		}
		else
		{
			$arParams = null;
			if ($ownerID <= 0)
			{
				$arParams = $params;
			}
			else
			{
				if ($ownerType === 'L')
				{
					$arParams = CCrmLead::GetByID($ownerID, $checkPerms);
				}
				elseif ($ownerType === 'D')
				{
					$arParams = CCrmDeal::GetByID($ownerID, $checkPerms);
				}
				elseif ($ownerType === CCrmQuote::OWNER_TYPE)
				{
					$arParams = CCrmQuote::GetByID($ownerID, $checkPerms);
				}
			}

			if(!is_array($arParams))
			{
				return $result;
			}

			$arRows = null;
			if (is_array($rows))
			{
				$arRows = $rows;
			}
			elseif($ownerID > 0)
			{
				$arRows = CCrmProductRow::LoadRows($ownerType, $ownerID);
			}

			if (!is_array($arRows))
			{
				return $result;
			}

			$currencyID = isset($params['CURRENCY_ID']) ? $params['CURRENCY_ID'] : '';
			if($currencyID === '')
			{
				$currencyID = CCrmCurrency::GetBaseCurrencyID();
			}

			$companyID = isset($params['COMPANY_ID']) ? intval($params['COMPANY_ID']) : 0;
			$contactID = isset($params['CONTACT_ID']) ? intval($params['CONTACT_ID']) : 0;

			// Determine person type
			$personTypeId = 0;
			$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
			if ($companyID > 0 && isset($arPersonTypes['COMPANY']))
			{
				$personTypeId = $arPersonTypes['COMPANY'];
			}
			elseif ($contactID > 0 && isset($arPersonTypes['CONTACT']))
			{
				$personTypeId = $arPersonTypes['CONTACT'];
			}

			$enableSaleDiscount = false;
			$siteID = '';
			if (defined('SITE_ID'))
			{
				$siteID = SITE_ID;
			}
			else
			{
				$obSite = CSite::GetList($by = 'def', $order = 'desc', array('ACTIVE' => 'Y'));
				if ($obSite && $arSite = $obSite->Fetch())
					$siteID= $arSite["LID"];
				unset($obSite, $arSite);
			}

			$calculateOptions = array();
			if (CCrmTax::isTaxMode())
			{
				$calculateOptions['LOCATION_ID'] = isset($arParams['LOCATION_ID']) ? intval($arParams['LOCATION_ID']) : 0;
			}

			$calculated = CCrmSaleHelper::Calculate($arRows, $currencyID, $personTypeId, $enableSaleDiscount, $siteID, $calculateOptions);
			$result = array(
				'OPPORTUNITY' => isset($calculated['PRICE']) ? round(doubleval($calculated['PRICE']), 2) : 1.0,
				'TAX_VALUE' => isset($calculated['TAX_VALUE']) ? round(doubleval($calculated['TAX_VALUE']), 2) : 0.0
			);
		}

		return $result;
	}

	private static function GetProductNameByID($ID)
	{
		$prod = CCrmProduct::GetByID($ID);
		return is_array($prod) && isset($prod['NAME']) ? $prod['NAME'] : '['.$ID.']';
	}

	public static function GetProductName($arRow)
	{
		if(isset($arRow['PRODUCT_NAME']) && $arRow['PRODUCT_NAME'] !== '')
		{
			return $arRow['PRODUCT_NAME'];
		}

		$productID = isset($arRow['PRODUCT_ID']) ? (int)$arRow['PRODUCT_ID'] : 0;
		if($productID > 0)
		{
			$rs = CCrmProduct::GetList(array(), array('ID' => $productID), array('NAME'));
			return ($ary = $rs->Fetch()) ? $ary['NAME'] : $productID;
		}
		return "[{$productID}]";
	}

	public static function GetPrice($arRow, $default = 0.0)
	{
		return isset($arRow['PRICE']) ? round(doubleval($arRow['PRICE']), 2) : $default;
	}

	public static function GetQuantity($arRow, $default = 0)
	{
		return isset($arRow['QUANTITY']) ? round(doubleval($arRow['QUANTITY']), 4) : $default;
	}

	public static function RowsToString($arRows, $formatInfo = array('FORMAT' => '#NAME#', 'DELIMITER' => ', '))
	{
		if(!is_array($arRows) || count($arRows) == 0)
		{
			return '';
		}

		// Validation -->
		if(!is_array($formatInfo))
		{
			$formatInfo = array('FORMAT' => '#NAME#', 'DELIMITER' => ', ');
		}
		else
		{
			if(!isset($formatInfo['FORMAT']))
			{
				$formatInfo['FORMAT'] = '#NAME#';
			}

			if(!isset($formatInfo['DELIMITER']))
			{
				$formatInfo['DELIMITER'] = ', ';
			}
		}
		// <-- Validation

		$result = array();
		foreach($arRows as $row)
		{
			$result[] = str_replace(
				array(
					'#NAME#',
					'#PRICE#',
					'#QUANTITY#'
				),
				array(
					self::GetProductName($row),
					self::GetPrice($row),
					self::GetQuantity($row)
				),
				$formatInfo['FORMAT']
			);
		}

		return implode($formatInfo['DELIMITER'], $result);
	}

	public static function GetLastError()
	{
		return self::$LAST_ERROR;
	}
	public static function DeleteSettings($ownerType, $ownerID)
	{
		$ownerType = strval($ownerType);
		$ownerID = intval($ownerID);

		global $DB;
		$ownerType = $DB->ForSql($ownerType);

		$configTableName = CCrmProductRow::CONFIG_TABLE_NAME;
		$DB->Query(
			"DELETE FROM {$configTableName} WHERE OWNER_TYPE = '{$ownerType}' AND OWNER_ID = {$ownerID}", false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
	}
	// <-- Contract
}
