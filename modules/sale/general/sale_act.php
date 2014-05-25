<?
if (!CModule::IncludeModule('catalog'))
{
	return;
}

IncludeModuleLangFile(__FILE__);

class CSaleDiscountActionApply
{
	public static function ClearBasket($row)
	{
		return (
			(!isset($row['CUSTOM_PRICE']) || 'Y' != $row['CUSTOM_PRICE']) &&
			(!isset($row['SET_PARENT_ID']) || 0 >= intval($row['SET_PARENT_ID'])) &&
			(!isset($row['ITEM_FIX']) || 'Y' != $row['ITEM_FIX'])
		);
	}

	public static function ApplyDelivery(&$arOrder, $dblValue, $strUnit)
	{
		if (isset($arOrder['PRICE_DELIVERY']))
		{
			$dblValue = doubleval($dblValue);
			if ('P' == $strUnit)
			{
				$dblValue = $arOrder['PRICE_DELIVERY']*($dblValue/100);
			}
			$dblResult = $arOrder['PRICE_DELIVERY'] + $dblValue;
			if (0 <= $dblResult)
			{
				if (!isset($arOrder['PRICE_DELIVERY_DIFF']))
					$arOrder['PRICE_DELIVERY_DIFF'] = 0;
				$arOrder['PRICE_DELIVERY_DIFF'] -= $dblValue;
				$arOrder['PRICE_DELIVERY'] = $dblResult;
			}
		}
	}

	public static function ApplyBasketDiscount(&$arOrder, $func, $dblValue, $strUnit)
	{
		if (isset($arOrder['BASKET_ITEMS']) && !empty($arOrder['BASKET_ITEMS']) && is_array($arOrder['BASKET_ITEMS']))
		{
			$arDiscountBasket = (is_callable($func) ? array_filter($arOrder['BASKET_ITEMS'], $func) : $arOrder['BASKET_ITEMS']);
			if (!empty($arDiscountBasket))
			{
				$arClearBasket = array_filter($arDiscountBasket, 'CSaleDiscountActionApply::ClearBasket');
				if (!empty($arClearBasket))
				{
					$dblValue = doubleval($dblValue);
					if ('S' == $strUnit)
					{
						$dblSumm = 0.0;
						foreach ($arClearBasket as &$arOneRow)
						{
							$dblSumm += doubleval($arOneRow['PRICE'])*doubleval($arOneRow['QUANTITY']);
						}
						if (isset($arOneRow))
							unset($arOneRow);
						if ($dblSumm > 0)
						{
							$dblValue = ($dblValue*100)/$dblSumm;
						}
						else
						{
							$dblValue = 0.0;
						}
						$strUnit = 'P';
					}
					if (0 != $dblValue)
					{
						foreach ($arClearBasket as $key => $arOneRow)
						{
							$dblCurValue = $dblValue;
							if ('P' == $strUnit)
							{
								$dblCurValue = $arOneRow['PRICE']*($dblValue/100);
							}
							$dblResult = $arOneRow['PRICE'] + $dblCurValue;
							if (0 <= $dblResult)
							{
								$arOneRow['PRICE'] = $dblResult;
								if (array_key_exists('PRICE_DEFAULT', $arOneRow))
									$arOneRow['PRICE_DEFAULT'] = $dblResult;
								if (isset($arOneRow['DISCOUNT_PRICE']))
								{
									$arOneRow['DISCOUNT_PRICE'] = doubleval($arOneRow['DISCOUNT_PRICE']);
									$arOneRow['DISCOUNT_PRICE'] -= $dblCurValue;
								}
								else
								{
									$arOneRow['DISCOUNT_PRICE'] = -$dblCurValue;
								}
								if (0 > $arOneRow['DISCOUNT_PRICE'])
									$arOneRow['DISCOUNT_PRICE'] = 0;
								if (isset($arOneRow['VAT_RATE']))
								{
									$dblVatRate = doubleval($arOneRow["VAT_RATE"]);
									if (0 < $dblVatRate)
										$arOneRow["VAT_VALUE"] = (($arOneRow["PRICE"] / ($dblVatRate + 1)) * $dblVatRate);
								}
								$arOrder['BASKET_ITEMS'][$key] = $arOneRow;
							}
						}
					}
				}
			}
		}
	}
}

class CSaleActionCtrl extends CGlobalCondCtrl
{
	public static function GetClassName()
	{
		return __CLASS__;
	}

	public static function GetConditionShow($arParams)
	{
		if (!isset($arParams['ID']))
			return false;
		if ($arParams['ID'] != static::GetControlID())
			return false;
		$arControl = array(
			'ID' => $arParams['ID'],
			'ATOMS' => static::GetAtomsEx(false, true),
		);

		return static::CheckAtoms($arParams['DATA'], $arParams, $arControl, true);
	}

	public static function Parse($arOneCondition)
	{
		if (!isset($arOneCondition['controlId']))
			return false;
		if ($arOneCondition['controlId'] != static::GetControlID())
			return false;
		$arControl = array(
			'ID' => $arOneCondition['controlId'],
			'ATOMS' => static::GetAtomsEx(false, true),
		);

		return static::CheckAtoms($arOneCondition, $arOneCondition, $arControl, false);
	}
}

class CSaleActionCtrlComplex extends CGlobalCondCtrlComplex
{
	public static function GetClassName()
	{
		return __CLASS__;
	}
}

class CSaleActionCtrlGroup extends CGlobalCondCtrlGroup
{
	public static function GetClassName()
	{
		return __CLASS__;
	}

	public static function GetShowIn($arControls)
	{
		$arControls = array();
		return $arControls;
	}

	public static function GetControlShow($arParams)
	{
		$arResult = array(
			'controlId' => static::GetControlID(),
			'group' => true,
			'label' => '',
			'defaultText' => '',
			'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
			'control' => array(
				GetMessage('BT_SALE_ACT_GROUP_GLOBAL_PREFIX')
			)
		);

		return $arResult;
	}

	public static function GetConditionShow($arParams)
	{
		return array(
			'id' => $arParams['COND_NUM'],
			'controlId' => static::GetControlID(),
			'values' => array()
		);
	}

	public static function Parse($arOneCondition)
	{
		return array(
			'All' => 'AND'
		);
	}

	public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
	{
		$mxResult = '';
		$boolError = false;

		if (!isset($arSubs) || !is_array($arSubs) || empty($arSubs))
		{
			$boolError = true;
		}
		else
		{
			$mxResult = 'function (&'.$arParams['ORDER'].'){'.implode('; ',$arSubs).';};';
		}
		return $mxResult;
	}
}

class CSaleActionCtrlAction extends CGlobalCondCtrlGroup
{
	public static function GetClassName()
	{
		return __CLASS__;
	}

	public static function GetConditionShow($arParams)
	{
		if (!isset($arParams['ID']))
			return false;
		if ($arParams['ID'] != static::GetControlID())
			return false;
		$arControl = array(
			'ID' => $arParams['ID'],
			'ATOMS' => static::GetAtomsEx(false, true)
		);

		return static::CheckAtoms($arParams['DATA'], $arParams, $arControl, true);
	}

	public static function Parse($arOneCondition)
	{
		if (!isset($arOneCondition['controlId']))
			return false;
		if ($arOneCondition['controlId'] != static::GetControlID())
			return false;
		$arControl = array(
			'ID' => $arOneCondition['controlId'],
			'ATOMS' => static::GetAtomsEx(false, true)
		);

		return static::CheckAtoms($arOneCondition, $arOneCondition, $arControl, false);
	}

	public static function GetVisual()
	{
		return array(
			'controls' => array(
				'All'
			),
			'values' => array(
				array(
					'All' => 'AND'
				),
				array(
					'All' => 'OR'
				)
			),
			'logic' => array(
				array(
					'style' => 'condition-logic-and',
					'message' => GetMessage('BT_SALE_ACT_GROUP_LOGIC_AND')
				),
				array(
					'style' => 'condition-logic-or',
					'message' => GetMessage('BT_SALE_ACT_GROUP_LOGIC_OR')
				)
			)
		);
	}
}

class CSaleActionCtrlDelivery extends CSaleActionCtrl
{
	public static function GetClassName()
	{
		return __CLASS__;
	}

	public static function GetControlID()
	{
		return 'ActSaleDelivery';
	}

	public static function GetControlShow($arParams)
	{
		$arAtoms = static::GetAtomsEx(false, false);
		$boolCurrency = false;
		if (static::$boolInit)
		{
			if (isset(static::$arInitParams['CURRENCY']))
			{
				$arAtoms['Unit']['values']['Cur'] = static::$arInitParams['CURRENCY'];
				$boolCurrency = true;
			}
			elseif (isset(static::$arInitParams['SITE_ID']))
			{
				$strCurrency = CSaleLang::GetLangCurrency(static::$arInitParams['SITE_ID']);
				if (!empty($strCurrency))
				{
					$arAtoms['Unit']['values']['Cur'] = $strCurrency;
					$boolCurrency = true;
				}
			}
		}
		if (!$boolCurrency)
		{
			unset($arAtoms['Unit']['values']['Cur']);
		}
		$arResult = array(
			'controlId' => static::GetControlID(),
			'group' => false,
			'label' => GetMessage('BT_SALE_ACT_DELIVERY_LABEL'),
			'defaultText' => GetMessage('BT_SALE_ACT_DELIVERY_DEF_TEXT'),
			'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
			'control' => array(
				GetMessage('BT_SALE_ACT_DELIVERY_GROUP_PRODUCT_PREFIX'),
				$arAtoms['Type'],
				$arAtoms['Value'],
				$arAtoms['Unit']
			)
		);

		return $arResult;
	}

	public static function GetAtoms()
	{
		return static::GetAtomsEx(false, false);
	}

	public static function GetAtomsEx($strControlID = false, $boolEx = false)
	{
		$boolEx = (true === $boolEx ? true : false);
		$arAtomList = array(
			'Type' => array(
				'JS' => array(
					'id' => 'Type',
					'name' => 'extra',
					'type' => 'select',
					'values' => array(
						'Discount' => GetMessage('BT_SALE_ACT_DELIVERY_SELECT_TYPE_DISCOUNT'),
						'Extra' => GetMessage('BT_SALE_ACT_DELIVERY_SELECT_TYPE_EXTRA')
					),
					'defaultText' => GetMessage('BT_SALE_ACT_DELIVERY_SELECT_TYPE_DEF'),
					'defaultValue' => 'Discount',
					'first_option' => '...'
				),
				'ATOM' => array(
					'ID' => 'Type',
					'FIELD_TYPE' => 'string',
					'FIELD_LENGTH' => 255,
					'MULTIPLE' => 'N',
					'VALIDATE' => 'list'
				)
			),
			'Value' => array(
				'JS' => array(
					'id' => 'Value',
					'name' => 'extra_size',
					'type' => 'input'
				),
				'ATOM' => array(
					'ID' => 'Value',
					'FIELD_TYPE' => 'double',
					'MULTIPLE' => 'N',
					'VALIDATE' => ''
				)
			),
			'Unit' => array(
				'JS' => array(
					'id' => 'Unit',
					'name' => 'extra_unit',
					'type' => 'select',
					'values' => array(
						'Perc' => GetMessage('BT_SALE_ACT_DELIVERY_SELECT_PERCENT'),
						'Cur' => GetMessage('BT_SALE_ACT_DELIVERY_SELECT_CUR')
					),
					'defaultText' => GetMessage('BT_SALE_ACT_DELIVERY_SELECT_UNIT_DEF'),
					'defaultValue' => 'Perc',
					'first_option' => '...'
				),
				'ATOM' => array(
					'ID' => 'Unit',
					'FIELD_TYPE' => 'string',
					'FIELD_LENGTH' => 255,
					'MULTIPLE' => 'N',
					'VALIDATE' => ''
				)
			)
		);

		if (!$boolEx)
		{
			foreach ($arAtomList as &$arOneAtom)
			{
				$arOneAtom = $arOneAtom['JS'];
			}
				if (isset($arOneAtom))
					unset($arOneAtom);
		}

		return $arAtomList;
	}

	public static function GetShowIn($arControls)
	{
		return array(CSaleActionCtrlGroup::GetControlID());
	}

	public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
	{
		$mxResult = '';
		$boolError = false;

		if (is_string($arControl))
		{
			if ($arControl == static::GetControlID())
			{
				$arControl = array(
					'ID' => static::GetControlID(),
					'ATOMS' => static::GetAtoms()
				);
			}
		}
		$boolError = !is_array($arControl);

		if (!$boolError)
		{
			$arOneCondition['Value'] = doubleval($arOneCondition['Value']);
			$dblVal = ('Extra' == $arOneCondition['Type'] ? $arOneCondition['Value'] : -$arOneCondition['Value']);
			$strUnit = ('Cur' == $arOneCondition['Unit'] ? 'F' : 'P');
			$mxResult = 'CSaleDiscountActionApply::ApplyDelivery('.$arParams['ORDER'].', '.$dblVal.', "'.$strUnit.'");';
		}

		return $mxResult;
	}
}

class CSaleActionCtrlBasketGroup extends CSaleActionCtrlAction
{
	public static function GetClassName()
	{
		return __CLASS__;
	}

	public static function GetControlID()
	{
		return 'ActSaleBsktGrp';
	}

	public static function GetControlShow($arParams)
	{
		$arAtoms = static::GetAtomsEx(false, false);
		$boolCurrency = false;
		if (static::$boolInit)
		{
			if (isset(static::$arInitParams['CURRENCY']))
			{
				$arAtoms['Unit']['values']['CurEach'] = str_replace('#CUR#', static::$arInitParams['CURRENCY'], $arAtoms['Unit']['values']['CurEach']);
				$arAtoms['Unit']['values']['CurAll'] = str_replace('#CUR#', static::$arInitParams['CURRENCY'], $arAtoms['Unit']['values']['CurAll']);
				$boolCurrency = true;
			}
			elseif (isset(static::$arInitParams['SITE_ID']))
			{
				$strCurrency = CSaleLang::GetLangCurrency(static::$arInitParams['SITE_ID']);
				if (!empty($strCurrency))
				{
					$arAtoms['Unit']['values']['CurEach'] = str_replace('#CUR#', $strCurrency, $arAtoms['Unit']['values']['CurEach']);
					$arAtoms['Unit']['values']['CurAll'] = str_replace('#CUR#', $strCurrency, $arAtoms['Unit']['values']['CurAll']);
					$boolCurrency = true;
				}
			}
		}
		if (!$boolCurrency)
		{
			unset($arAtoms['Unit']['values']['CurEach']);
			unset($arAtoms['Unit']['values']['CurAll']);
		}
		return array(
			'controlId' => static::GetControlID(),
			'group' => true,
			'label' => GetMessage('BT_SALE_ACT_GROUP_BASKET_LABEL'),
			'defaultText' => GetMessage('BT_SALE_ACT_GROUP_BASKET_DEF_TEXT'),
			'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
			'visual' => static::GetVisual(),
			'control' => array(
				GetMessage('BT_SALE_ACT_GROUP_BASKET_PREFIX'),
				$arAtoms['Type'],
				$arAtoms['Value'],
				$arAtoms['Unit'],
				GetMessage('BT_SALE_ACT_GROUP_BASKET_DESCR'),
				$arAtoms['All']
			),
			'mess' => array(
				'ADD_CONTROL' => GetMessage('BT_SALE_SUBACT_ADD_CONTROL'),
				'SELECT_CONTROL' => GetMessage('BT_SALE_SUBACT_SELECT_CONTROL')
			)
		);
	}

	public static function GetShowIn($arControls)
	{
		return array(CSaleActionCtrlGroup::GetControlID());
	}

	public static function GetAtoms()
	{
		return static::GetAtomsEx(false, false);
	}

	public static function GetAtomsEx($strControlID = false, $boolEx = false)
	{
		$boolEx = (true === $boolEx ? true : false);
		$arAtomList = array(
			'Type' => array(
				'JS' => array(
					'id' => 'Type',
					'name' => 'extra',
					'type' => 'select',
					'values' => array(
						'Discount' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_TYPE_DISCOUNT'),
						'Extra' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_TYPE_EXTRA')
					),
					'defaultText' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_TYPE_DEF'),
					'defaultValue' => 'Discount',
					'first_option' => '...'
				),
				'ATOM' => array(
					'ID' => 'Type',
					'FIELD_TYPE' => 'string',
					'FIELD_LENGTH' => 255,
					'MULTIPLE' => 'N',
					'VALIDATE' => 'list'
				)
			),
			'Value' => array(
				'JS' => array(
					'id' => 'Value',
					'name' => 'extra_size',
					'type' => 'input'
				),
				'ATOM' => array(
					'ID' => 'Value',
					'FIELD_TYPE' => 'double',
					'MULTIPLE' => 'N',
					'VALIDATE' => ''
				)
			),
			'Unit' => array(
				'JS' => array(
					'id' => 'Unit',
					'name' => 'extra_unit',
					'type' => 'select',
					'values' => array(
						'Perc' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_PERCENT'),
						'CurEach' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_CUR_EACH'),
						'CurAll' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_CUR_ALL')
					),
					'defaultText' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_UNIT_DEF'),
					'defaultValue' => 'Perc',
					'first_option' => '...'
				),
				'ATOM' => array(
					'ID' => 'Unit',
					'FIELD_TYPE' => 'string',
					'FIELD_LENGTH' => 255,
					'MULTIPLE' => 'N',
					'VALIDATE' => 'list'
				)
			),
			'All' => array(
				'JS' => array(
					'id' => 'All',
					'name' => 'aggregator',
					'type' => 'select',
					'values' => array(
						'AND' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_ALL'),
						'OR' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_ANY')
					),
					'defaultText' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_DEF'),
					'defaultValue' => 'AND',
					'first_option' => '...'
				),
				'ATOM' => array(
					'ID' => 'All',
					'FIELD_TYPE' => 'string',
					'FIELD_LENGTH' => 255,
					'MULTIPLE' => 'N',
					'VALIDATE' => 'list'
				)
			)
		);

		if (!$boolEx)
		{
			foreach ($arAtomList as &$arOneAtom)
			{
				$arOneAtom = $arOneAtom['JS'];
			}
				if (isset($arOneAtom))
					unset($arOneAtom);
		}

		return $arAtomList;
	}

	public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
	{
		$mxResult = '';
		$boolError = false;

		if (!isset($arSubs) || !is_array($arSubs))
		{
			$boolError = true;
		}

		if (!$boolError)
		{
			$arOneCondition['Value'] = doubleval($arOneCondition['Value']);
			$dblVal = ('Extra' == $arOneCondition['Type'] ? $arOneCondition['Value'] : -$arOneCondition['Value']);
			$strUnit = 'P';
			if ('CurEach' == $arOneCondition['Unit'])
			{
				$strUnit = 'F';
			}
			elseif ('CurAll' == $arOneCondition['Unit'])
			{
				$strUnit = 'S';
			}

			if (!empty($arSubs))
			{
				$strFuncName = '$saleact'.$arParams['FUNC_ID'];
				$strLogic = ('AND' == $arOneCondition['All'] ? '&&' : '||');

				$mxResult = $strFuncName.'=function($row){';
				$mxResult .= 'return ('.implode(') '.$strLogic.' (', $arSubs).');';
				$mxResult .= '};';
				$mxResult .= 'CSaleDiscountActionApply::ApplyBasketDiscount('.$arParams['ORDER'].', '.$strFuncName.', '.$dblVal.', "'.$strUnit.'");';
			}
			else
			{
				$mxResult = 'CSaleDiscountActionApply::ApplyBasketDiscount('.$arParams['ORDER'].', "", '.$dblVal.', "'.$strUnit.'");';
			}
		}
		return (!$boolError ? $mxResult : false);
	}
}

class CSaleActionCtrlSubGroup extends CGlobalCondCtrlGroup
{
	public static function GetClassName()
	{
		return __CLASS__;
	}

	public static function GetControlID()
	{
		return 'ActSaleSubGrp';
	}

	public static function GetShowIn($arControls)
	{
		$arControls = array(CSaleActionCtrlBasketGroup::GetControlID());
		return $arControls;
	}
}

class CSaleActionCondCtrlBasketFields extends CSaleActionCtrlComplex
{
	public static function GetClassName()
	{
		return __CLASS__;
	}

	public static function GetControlShow($arParams)
	{
		$arControls = static::GetControls();
		$arResult = array(
			'controlgroup' => true,
			'group' =>  false,
			'label' => GetMessage('BT_MOD_SALE_ACT_GROUP_BASKET_FIELDS_LABEL'),
			'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
			'children' => array()
		);
		foreach ($arControls as &$arOneControl)
		{
			$arOne = array(
				'controlId' => $arOneControl['ID'],
				'group' => ('Y' == $arOneControl['GROUP']),
				'label' => $arOneControl['LABEL'],
				'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
				'control' => array(
					array(
						'id' => 'prefix',
						'type' => 'prefix',
						'text' => $arOneControl['PREFIX']
					),
					static::GetLogicAtom($arOneControl['LOGIC']),
					static::GetValueAtom($arOneControl['JS_VALUE'])
				)
			);
			if ('CondBsktFldPrice' == $arOneControl['ID'])
			{
				$boolCurrency = false;
				if (static::$boolInit)
				{
					if (isset(static::$arInitParams['CURRENCY']))
					{
						$arOne['control'][] = static::$arInitParams['CURRENCY'];
						$boolCurrency = true;
					}
					elseif (isset(static::$arInitParams['SITE_ID']))
					{
						$strCurrency = CSaleLang::GetLangCurrency(static::$arInitParams['SITE_ID']);
						if (!empty($strCurrency))
						{
							$arOne['control'][] = $strCurrency;
							$boolCurrency = true;
						}
					}
				}
			}
			elseif ('CondBsktFldWeight' == $arOneControl['ID'])
			{
				$arOne['control'][] = GetMessage('BT_MOD_SALE_ACT_MESS_WEIGHT_UNIT');
			}
			$arResult['children'][] = $arOne;
		}
		if (isset($arOneControl))
			unset($arOneControl);

		return $arResult;
	}

	public static function GetControls($strControlID = false)
	{
		$arControlList = array(
			'CondBsktFldProduct' => array(
				'ID' => 'CondBsktFldProduct',
				'FIELD' => 'PRODUCT_ID',
				'FIELD_TYPE' => 'int',
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_ELEMENT_ID_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_ELEMENT_ID_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ)),
				'JS_VALUE' => array(
					'type' => 'popup',
					'popup_url' =>  '/bitrix/admin/iblock_element_search.php',
					'popup_params' => array(
						'lang' => LANGUAGE_ID,
						'discount' => 'Y'
					),
					'param_id' => 'n',
					'show_value' => 'Y'
				),
				'PHP_VALUE' => array(
					'VALIDATE' => 'element'
				)
			),
			'CondBsktFldName' => array(
				'ID' => 'CondBsktFldName',
				'FIELD' => 'NAME',
				'FIELD_TYPE' => 'string',
				'FIELD_LENGTH' => 255,
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_NAME_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_NAME_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT)),
				'JS_VALUE' => array(
					'type' => 'input'
				),
				'PHP_VALUE' => ''
			),
			'CondBsktFldPrice' => array(
				'ID' => 'CondBsktFldPrice',
				'FIELD' => 'PRICE',
				'FIELD_TYPE' => 'double',
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_SALE_ACT_BASKET_ROW_PRICE_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_SALE_ACT_BASKET_ROW_PRICE_PREFIX'),
				'LOGIC' => static::GetLogic(
					array(
						BT_COND_LOGIC_EQ,
						BT_COND_LOGIC_NOT_EQ,
						BT_COND_LOGIC_GR,
						BT_COND_LOGIC_LS,
						BT_COND_LOGIC_EGR,
						BT_COND_LOGIC_ELS
					)
				),
				'JS_VALUE' => array(
					'type' => 'input'
				)
			),
			'CondBsktFldQuantity' => array(
				'ID' => 'CondBsktFldQuantity',
				'FIELD' => 'QUANTITY',
				'FIELD_TYPE' => 'double',
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_SALE_ACT_BASKET_ROW_QUANTITY_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_SALE_ACT_BASKET_ROW_QUANTITY_PREFIX'),
				'LOGIC' => static::GetLogic(
					array(
						BT_COND_LOGIC_EQ,
						BT_COND_LOGIC_NOT_EQ,
						BT_COND_LOGIC_GR,
						BT_COND_LOGIC_LS,
						BT_COND_LOGIC_EGR,
						BT_COND_LOGIC_ELS
					)
				),
				'JS_VALUE' => array(
					'type' => 'input'
				)
			),
			'CondBsktFldWeight' => array(
				'ID' => 'CondBsktFldWeight',
				'FIELD' => 'WEIGHT',
				'FIELD_TYPE' => 'double',
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_SALE_ACT_BASKET_ROW_WEIGHT_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_SALE_ACT_BASKET_ROW_WEIGHT_PREFIX'),
				'LOGIC' => static::GetLogic(
					array(
						BT_COND_LOGIC_EQ,
						BT_COND_LOGIC_NOT_EQ,
						BT_COND_LOGIC_GR,
						BT_COND_LOGIC_LS,
						BT_COND_LOGIC_EGR,
						BT_COND_LOGIC_ELS
					)
				),
				'JS_VALUE' => array(
					'type' => 'input'
				)
			)
		);

		if (false === $strControlID)
		{
			return $arControlList;
		}
		elseif (isset($arControlList[$strControlID]))
		{
			return $arControlList[$strControlID];
		}
		else
		{
			return false;
		}
	}

	public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
	{
		$strResult = '';
		$boolError = false;

		if (is_string($arControl))
		{
			$arControl = static::GetControls($arControl);
		}
		$boolError = !is_array($arControl);

		if (!$boolError)
		{
			$arValues = static::Check($arOneCondition, $arOneCondition, $arControl, false);
			if (false === $arValues)
			{
				$boolError = true;
			}
		}

		if (!$boolError)
		{
			$arLogic = static::SearchLogic($arValues['logic'], $arControl['LOGIC']);
			if (!isset($arLogic['OP'][$arControl['MULTIPLE']]) || empty($arLogic['OP'][$arControl['MULTIPLE']]))
			{
				$boolError = true;
			}
			else
			{
				$strField = $arParams['BASKET_ROW'].'[\''.$arControl['FIELD'].'\']';
				switch ($arControl['FIELD_TYPE'])
				{
					case 'int':
					case 'double':
						$strResult = str_replace(array('#FIELD#', '#VALUE#'), array($strField, $arValues['value']), $arLogic['OP'][$arControl['MULTIPLE']]);
						break;
					case 'char':
					case 'string':
					case 'text':
						$strResult = str_replace(array('#FIELD#', '#VALUE#'), array($strField, '"'.EscapePHPString($arValues['value']).'"'), $arLogic['OP'][$arControl['MULTIPLE']]);
						break;
					case 'date':
					case 'datetime':
						$strResult = str_replace(array('#FIELD#', '#VALUE#'), array($strField, $arValues['value']), $arLogic['OP'][$arControl['MULTIPLE']]);
						break;
				}
				$strResult = 'isset('.$strField.') && '.$strResult;
			}
		}

		return (!$boolError ? $strResult : false);
	}

	public static function GetShowIn($arControls)
	{
		$arControls = array(CSaleActionCtrlBasketGroup::GetControlID(), CSaleActionCtrlSubGroup::GetControlID());
		return $arControls;
	}
}

class CSaleActionTree extends CGlobalCondTree
{
	protected $arExecuteFunc = array();

	static public function __construct()
	{
		parent::__construct();
	}

	static public function __destruct()
	{
		parent::__destruct();
	}

	public function Generate($arConditions, $arParams)
	{
		$strFinal = '';
		$this->arExecuteFunc = array();
		if (!$this->boolError)
		{
			$strResult = '';
			if (!empty($arConditions) && is_array($arConditions))
			{
				$arParams['FUNC_ID'] = '';
				$arResult = $this->GenerateLevel($arConditions, $arParams, true);
				if (false === $arResult || empty($arResult))
				{
					$strResult = '';
					$this->boolError = true;
				}
				else
				{
					$strResult = current($arResult);
				}
			}
			else
			{
				$this->boolError = true;
			}
			if (!$this->boolError)
			{
				$strFinal = preg_replace("#;{2,}#",";", $strResult);
			}
			return $strFinal;
		}
		else
		{
			return '';
		}
	}

	public function GenerateLevel(&$arLevel, $arParams, $boolFirst = false)
	{
		$arResult = array();
		$boolError = false;
		$boolFirst = (true === $boolFirst);
		if (empty($arLevel) || !is_array($arLevel))
		{
			return $arResult;
		}
		if (!isset($arParams['FUNC_ID']))
		{
			$arParams['FUNC_ID'] = '';
		}
		$intRowNum = 0;
		if ($boolFirst)
		{
			$arParams['ROW_NUM'] = $intRowNum;
			if (isset($arLevel['CLASS_ID']) && !empty($arLevel['CLASS_ID']))
			{
				if (isset($this->arControlList[$arLevel['CLASS_ID']]))
				{
					$arOneControl = $this->arControlList[$arLevel['CLASS_ID']];
					$strEval = false;
					if ('Y' == $arOneControl['GROUP'])
					{
						$arSubParams = $arParams;
						$arSubParams['FUNC_ID'] .= '_'.$intRowNum;
						$arSubEval = $this->GenerateLevel($arLevel['CHILDREN'], $arSubParams);
						if (false === $arSubEval || !is_array($arSubEval))
							return false;
						$arGroupParams = $arParams;
						$arGroupParams['FUNC_ID'] .= '_'.$intRowNum;
						$mxEval = call_user_func_array($arOneControl['Generate'],
							array($arLevel['DATA'], $arGroupParams, $arLevel['CLASS_ID'], $arSubEval)
						);
						if (is_array($mxEval))
						{
							if (isset($mxEval['FUNC']))
							{
								$this->arExecuteFunc[] = $mxEval['FUNC'];
							}
							if (isset($mxEval['COND']))
							{
								$strEval = $mxEval['COND'];
							}
							else
							{
								$strEval = false;
							}
						}
						else
						{
							$strEval = $mxEval;
						}
					}
					else
					{
						$strEval = call_user_func_array($arOneControl['Generate'],
							array($arLevel['DATA'], $arParams, $arLevel['CLASS_ID'])
						);
					}
					if (false === $strEval || !is_string($strEval) || 'false' === $strEval)
					{
						return false;
					}
					$arResult[] = $strEval;
				}
			}
			$intRowNum++;
		}
		else
		{
			foreach ($arLevel as &$arOneCondition)
			{
				$arParams['ROW_NUM'] = $intRowNum;
				if (isset($arOneCondition['CLASS_ID']) && !empty($arOneCondition['CLASS_ID']))
				{
					if (isset($this->arControlList[$arOneCondition['CLASS_ID']]))
					{
						$arOneControl = $this->arControlList[$arOneCondition['CLASS_ID']];
						$strEval = false;
						if ('Y' == $arOneControl['GROUP'])
						{
							$arSubParams = $arParams;
							$arSubParams['FUNC_ID'] .= '_'.$intRowNum;
							$arSubEval = $this->GenerateLevel($arOneCondition['CHILDREN'], $arSubParams);
							if (false === $arSubEval || !is_array($arSubEval))
								return false;
							$arGroupParams = $arParams;
							$arGroupParams['FUNC_ID'] .= '_'.$intRowNum;
							$mxEval = call_user_func_array($arOneControl['Generate'],
								array($arOneCondition['DATA'], $arGroupParams, $arOneCondition['CLASS_ID'], $arSubEval)
							);
							if (is_array($mxEval))
							{
								if (isset($mxEval['FUNC']))
								{
									$this->arExecuteFunc[] = $mxEval['FUNC'];
								}
								if (isset($mxEval['COND']))
								{
									$strEval = $mxEval['COND'];
								}
								else
								{
									$strEval = false;
								}
							}
							else
							{
								$strEval = $mxEval;
							}
						}
						else
						{
							$strEval = call_user_func_array($arOneControl['Generate'],
								array($arOneCondition['DATA'], $arParams, $arOneCondition['CLASS_ID'])
							);
						}
						if (false === $strEval || !is_string($strEval) || 'false' === $strEval)
						{
							return false;
						}
						$arResult[] = $strEval;
					}
				}
				$intRowNum++;
			}
			if (isset($arOneCondition))
				unset($arOneCondition);
		}

		if (!empty($arResult))
		{
			foreach ($arResult as $key => $value)
			{
				if ('' == $value || '()' == $value)
					unset($arResult[$key]);
			}
		}
		if (!empty($arResult))
			$arResult = array_values($arResult);

		return $arResult;
	}
}
?>