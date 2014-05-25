<?
if (!CModule::IncludeModule('catalog'))
{
	return;
}

IncludeModuleLangFile(__FILE__);

class CSaleBasketFilter
{
	public static function ClearBasket($row)
	{
		return (
			!isset($row['SET_PARENT_ID']) || 0 >= intval($row['SET_PARENT_ID'])
		);
	}

	public static function AmountFilter(&$arOrder, $func)
	{
		$dblSumm = 0.0;
		if (isset($arOrder['BASKET_ITEMS']) && !empty($arOrder['BASKET_ITEMS']) && is_array($arOrder['BASKET_ITEMS']))
		{
			$arRes = (is_callable($func) ? array_filter($arOrder['BASKET_ITEMS'], $func) : $arOrder['BASKET_ITEMS']);
			if (!empty($arRes))
			{
				$arClear = array_filter($arRes, 'CSaleBasketFilter::ClearBasket');
				if (!empty($arClear))
				{
					foreach ($arClear as &$arRow)
					{
						$dblSumm += doubleval($arRow['PRICE'])*doubleval($arRow['QUANTITY']);
					}
					unset($arRow);
				}
			}
		}
		return $dblSumm;
	}

	public static function CountFilter(&$arOrder, $func)
	{
		$dblQuantity = 0.0;
		if (isset($arOrder['BASKET_ITEMS']) && !empty($arOrder['BASKET_ITEMS']) && is_array($arOrder['BASKET_ITEMS']))
		{
			$arRes = (is_callable($func) ? array_filter($arOrder['BASKET_ITEMS'], $func) : $arOrder['BASKET_ITEMS']);
			if (!empty($arRes))
			{
				$arClear = array_filter($arRes, 'CSaleBasketFilter::ClearBasket');
				if (!empty($arClear))
				{
					foreach ($arClear as &$arRow)
					{
						$dblQuantity += doubleval($arRow['QUANTITY']);
					}
					unset($arRow);
				}
			}
		}
		return $dblQuantity;
	}

	public static function RowFilter(&$arOrder, $func)
	{
		$intCount = 0;
		if (isset($arOrder['BASKET_ITEMS']) && !empty($arOrder['BASKET_ITEMS']) && is_array($arOrder['BASKET_ITEMS']))
		{
			$arRes = (is_callable($func) ? array_filter($arOrder['BASKET_ITEMS'], $func) : $arOrder['BASKET_ITEMS']);
			if (!empty($arRes))
			{
				$arClear = array_filter($arRes, 'CSaleBasketFilter::ClearBasket');
				$intCount = count($arClear);
			}
		}
		return $intCount;
	}

	public static function ProductFilter(&$arOrder, $func)
	{
		$boolFound = false;
		if (isset($arOrder['BASKET_ITEMS']) && !empty($arOrder['BASKET_ITEMS']) && is_array($arOrder['BASKET_ITEMS']))
		{
			$arRes = (is_callable($func) ? array_filter($arOrder['BASKET_ITEMS'], $func) : $arOrder['BASKET_ITEMS']);
			if (!empty($arRes))
			{
				$arClear = array_filter($arRes, 'CSaleBasketFilter::ClearBasket');
				if (!empty($arClear))
					$boolFound = true;
			}
		}
		return $boolFound;
	}
}

class CSaleCondCtrl extends CGlobalCondCtrl
{
	public static function GetClassName()
	{
		return __CLASS__;
	}
}

class CSaleCondCtrlComplex extends CGlobalCondCtrlComplex
{
	public static function GetClassName()
	{
		return __CLASS__;
	}
}

class CSaleCondCtrlGroup extends CGlobalCondCtrlGroup
{
	public static function GetClassName()
	{
		return __CLASS__;
	}

	public static function GetShowIn($arControls)
	{
		return array(static::GetControlID());
	}
}

class CSaleCondCtrlBasketGroup extends CSaleCondCtrlGroup
{
	public static function GetClassName()
	{
		return __CLASS__;
	}

	public static function GetControlID()
	{
		return array(
			'CondBsktCntGroup',
			'CondBsktAmtGroup',
			'CondBsktProductGroup',
			'CondBsktRowGroup',
			'CondBsktSubGroup'
		);
	}

	public static function GetControlDescr()
	{
		$arResult = array();

		$strClassName = static::GetClassName();
		$arControls = static::GetControls();
		foreach ($arControls as &$arOneControl)
		{
			$arResult[] = array(
				'ID' => $arOneControl['ID'],
				'GROUP' => 'Y',
				"GetControlShow" => array($strClassName, "GetControlShow"),
				"GetConditionShow" => array($strClassName, "GetConditionShow"),
				"IsGroup" => array($strClassName, "IsGroup"),
				"Parse" => array($strClassName, "Parse"),
				"Generate" => array($strClassName, "Generate"),
				"ApplyValues" => array($strClassName, "ApplyValues"),
				"InitParams" => array($strClassName, "InitParams")
			);
		}
		if (isset($arOneControl))
			unset($arOneControl);
		return $arResult;
	}

	public static function GetControlShow($arParams)
	{
		$arResult = array();

		$arControls = static::GetControls();
		foreach ($arControls as &$arOneControl)
		{
			$arOne = array(
				'controlId' => $arOneControl['ID'],
				'group' => ('Y' == $arOneControl['GROUP']),
				'label' => $arOneControl['LABEL'],
				//'defaultText' => '',
				'showIn' => $arOneControl['SHOW_IN'],
				'visual' => $arOneControl['VISUAL'],
				'control' => array()
			);
			switch ($arOneControl['ID'])
			{
				case 'CondBsktCntGroup':
					$arOne['control'] = array(
						GetMessage('BT_SALE_COND_GROUP_BASKET_NUMBER_PREFIX'),
						$arOneControl['ATOMS']['All'],
						$arOneControl['ATOMS']['Logic'],
						$arOneControl['ATOMS']['Value']
					);
					break;
				case 'CondBsktAmtGroup':
					$arOne['control'] = array(
						GetMessage('BT_SALE_COND_GROUP_BASKET_AMOUNT_PREFIX'),
						$arOneControl['ATOMS']['All'],
						$arOneControl['ATOMS']['Logic'],
						$arOneControl['ATOMS']['Value']
					);
					if (static::$boolInit)
					{
						if (isset(static::$arInitParams['CURRENCY']))
						{
							$arOne['control'][] = static::$arInitParams['CURRENCY'];
						}
						elseif (isset(static::$arInitParams['SITE_ID']))
						{
							$strCurrency = CSaleLang::GetLangCurrency(static::$arInitParams['SITE_ID']);
							if (!empty($strCurrency))
							{
								$arOne['control'][] = $strCurrency;
							}
						}
					}
					break;
				case 'CondBsktProductGroup':
					$arOne['control'] = array(
						GetMessage('BT_SALE_COND_GROUP_PRODUCT_PREFIX'),
						$arOneControl['ATOMS']['Found'],
						GetMessage('BT_SALE_COND_GROUP_PRODUCT_DESCR'),
						$arOneControl['ATOMS']['All']
					);
					break;
				case 'CondBsktRowGroup':
					$arOne['control'] = array(
						GetMessage('BT_SALE_COND_GROUP_BASKET_ROW_PREFIX'),
						$arOneControl['ATOMS']['All'],
						$arOneControl['ATOMS']['Logic'],
						$arOneControl['ATOMS']['Value']
					);
					break;
				default:
					$arOne['control'] = array_values($arOneControl['ATOMS']);
					break;
			}
			if (!empty($arOne['control']))
				$arResult[] = $arOne;
		}
		if (isset($arOneControl))
			unset($arOneControl);

		return $arResult;
	}

	public static function GetConditionShow($arParams)
	{
		if (!isset($arParams['ID']))
			return false;
		$arControl = static::GetControls($arParams['ID']);
		if (false === $arControl)
			return false;
		$arControl['ATOMS'] = static::GetAtomsEx($arControl['ID'], true);

		return static::CheckAtoms($arParams['DATA'], $arParams, $arControl, true);
	}

	public static function Parse($arOneCondition)
	{
		if (!isset($arOneCondition['controlId']))
			return false;
		$arControl = static::GetControls($arOneCondition['controlId']);
		if (false === $arControl)
			return false;
		$arControl['ATOMS'] = static::GetAtomsEx($arControl['ID'], true);

		return static::CheckAtoms($arOneCondition, $arOneCondition, $arControl, false);
	}

	public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
	{
		$mxResult = '';

		if (is_string($arControl))
		{
			$arControl = static::GetControls($arControl);
		}
		$boolError = !is_array($arControl);

		if (!isset($arSubs) || !is_array($arSubs))
		{
			$boolError = true;
		}

		if (!$boolError)
		{
			$arControl['ATOMS'] = static::GetAtomsEx($arControl['ID'], true);
			$arParams['COND_NUM'] = $arParams['FUNC_ID'];
			$arValues = static::CheckAtoms($arOneCondition, $arOneCondition, $arControl, true);
			if (false === $arValues)
			{
				$boolError = true;
			}
		}

		if (!$boolError)
		{
			switch($arControl['ID'])
			{
				case 'CondBsktCntGroup':
					$mxResult = self::__GetCntGroupCond($arOneCondition, $arValues['values'], $arParams, $arControl, $arSubs);
					break;
				case 'CondBsktAmtGroup':
					$mxResult = self::__GetAmtGroupCond($arOneCondition, $arValues['values'], $arParams, $arControl, $arSubs);
					break;
				case 'CondBsktProductGroup':
					$mxResult = self::__GetProductGroupCond($arOneCondition, $arValues['values'], $arParams, $arControl, $arSubs);
					break;
				case 'CondBsktRowGroup':
					$mxResult = self::__GetRowGroupCond($arOneCondition, $arValues['values'], $arParams, $arControl, $arSubs);
					break;
				case 'CondBsktSubGroup':
					$mxResult = self::__GetSubGroupCond($arOneCondition, $arValues['values'], $arParams, $arControl, $arSubs);
					break;
			}
		}

		return (!$boolError ? $mxResult : false);
	}

	public static function GetAtomsEx($strControlID = false, $boolEx = false)
	{
		$boolEx = (true === $boolEx ? true : false);
		$arAmtLabels = array(
			BT_COND_LOGIC_EQ => GetMessage('BT_SALE_AMOUNT_LOGIC_EQ_LABEL'),
			BT_COND_LOGIC_NOT_EQ => GetMessage('BT_SALE_AMOUNT_LOGIC_NOT_EQ_LABEL'),
			BT_COND_LOGIC_GR => GetMessage('BT_SALE_AMOUNT_LOGIC_GR_LABEL'),
			BT_COND_LOGIC_LS => GetMessage('BT_SALE_AMOUNT_LOGIC_LS_LABEL'),
			BT_COND_LOGIC_EGR => GetMessage('BT_SALE_AMOUNT_LOGIC_EGR_LABEL'),
			BT_COND_LOGIC_ELS => GetMessage('BT_SALE_AMOUNT_LOGIC_ELS_LABEL')
		);

		$arAtomList = array(
			'CondBsktCntGroup' => array(
				'Logic' => array(
					'JS' => static::GetLogicAtom(
						static::GetLogic(
							array(
								BT_COND_LOGIC_EQ,
								BT_COND_LOGIC_NOT_EQ,
								BT_COND_LOGIC_GR,
								BT_COND_LOGIC_LS,
								BT_COND_LOGIC_EGR,
								BT_COND_LOGIC_ELS
							)
						)
					),
					'ATOM' => array(
						'ID' => 'logic',
						'FIELD_TYPE' => 'string',
						'FIELD_LENGTH' => 255,
						'MULTIPLE' => 'N',
						'VALIDATE' => 'list'
					)
				),
				'Value' => array(
					'JS' => array(
						'id' => 'Value',
						'name' => 'value',
						'type' => 'input'
					),
					'ATOM' => array(
						'ID' => 'Value',
						'FIELD_TYPE' => 'double',
						'MULTIPLE' => 'N',
						'VALIDATE' => ''
					)
				),
				'All' => array(
					'JS' => array(
						'id' => 'All',
						'name' => 'aggregator',
						'type' => 'select',
						'values' => array(
							'AND' => GetMessage('BT_SALE_COND_GROUP_SELECT_ALL'),
							'OR' => GetMessage('BT_SALE_COND_GROUP_SELECT_ANY')
						),
						'defaultText' => GetMessage('BT_SALE_COND_GROUP_BASKET_NUMBER_GROUP_SELECT_DEF'),
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
			),
			'CondBsktAmtGroup' => array(
				'Logic' => array(
					'JS' => static::GetLogicAtom(
						static::GetLogicEx(
							array_keys($arAmtLabels), $arAmtLabels
						)
					),
					'ATOM' => array(
						'ID' => 'logic',
						'FIELD_TYPE' => 'string',
						'FIELD_LENGTH' => 255,
						'MULTIPLE' => 'N',
						'VALIDATE' => 'list'
					)
				),
				'Value' => array(
					'JS' => array(
						'id' => 'Value',
						'name' => 'value',
						'type' => 'input'
					),
					'ATOM' => array(
						'ID' => 'Value',
						'FIELD_TYPE' => 'double',
						'MULTIPLE' => 'N',
						'VALIDATE' => ''
					)
				),
				'All' => array(
					'JS' => array(
						'id' => 'All',
						'name' => 'aggregator',
						'type' => 'select',
						'values' => array(
							'AND' => GetMessage('BT_SALE_COND_GROUP_SELECT_ALL'),
							'OR' => GetMessage('BT_SALE_COND_GROUP_SELECT_ANY')
						),
						'defaultText' => GetMessage('BT_SALE_COND_BASKET_AMOUNT_GROUP_SELECT_DEF'),
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
			),
			'CondBsktProductGroup' => array(
				'Found' => array(
					'JS' => array(
						'id' => 'Found',
						'name' => 'search',
						'type' => 'select',
						'values' => array(
							'Found' => GetMessage('BT_SALE_COND_PRODUCT_GROUP_SELECT_FOUND'),
							'NoFound' => GetMessage('BT_SALE_COND_PRODUCT_GROUP_SELECT_NO_FOUND')
						),
						'defaultText' => GetMessage('BT_SALE_COND_PRODUCT_GROUP_SELECT_DEF'),
						'defaultValue' => 'Found',
						'first_option' => '...'
					),
					'ATOM' => array(
						'ID' => 'Found',
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
							'AND' => GetMessage('BT_SALE_COND_GROUP_SELECT_ALL'),
							'OR' => GetMessage('BT_SALE_COND_GROUP_SELECT_ANY')
						),
						'defaultText' => GetMessage('BT_SALE_COND_PRODUCT_GROUP_SELECT_DEF'),
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
			),
			'CondBsktRowGroup' => array(
				'Logic' => array(
					'JS' => static::GetLogicAtom(
						static::GetLogic(
							array(
								BT_COND_LOGIC_EQ,
								BT_COND_LOGIC_NOT_EQ,
								BT_COND_LOGIC_GR,
								BT_COND_LOGIC_LS,
								BT_COND_LOGIC_EGR,
								BT_COND_LOGIC_ELS
							)
						)
					),
					'ATOM' => array(
						'ID' => 'logic',
						'FIELD_TYPE' => 'string',
						'FIELD_LENGTH' => 255,
						'MULTIPLE' => 'N',
						'VALIDATE' => 'list'
					)
				),
				'Value' => array(
					'JS' => array(
						'id' => 'Value',
						'name' => 'value',
						'type' => 'input'
					),
					'ATOM' => array(
						'ID' => 'Value',
						'FIELD_TYPE' => 'int',
						'MULTIPLE' => 'N',
						'VALIDATE' => ''
					)
				),
				'All' => array(
					'JS' => array(
						'id' => 'All',
						'name' => 'aggregator',
						'type' => 'select',
						'values' => array(
							'AND' => GetMessage('BT_SALE_COND_GROUP_SELECT_ALL'),
							'OR' => GetMessage('BT_SALE_COND_GROUP_SELECT_ANY')
						),
						'defaultText' => GetMessage('BT_SALE_COND_GROUP_BASKET_ROW_GROUP_SELECT_DEF'),
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
			),
			'CondBsktSubGroup' => array(
				'All' => array(
					'JS' => array(
						'id' => 'All',
						'name' => 'aggregator',
						'type' => 'select',
						'values' => array(
							'AND' => GetMessage('BT_CLOBAL_COND_GROUP_SELECT_ALL'),
							'OR' => GetMessage('BT_CLOBAL_COND_GROUP_SELECT_ANY')
						),
						'defaultText' => GetMessage('BT_CLOBAL_COND_GROUP_SELECT_DEF'),
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
				),
				'True' => array(
					'JS' => array(
						'id' => 'True',
						'name' => 'value',
						'type' => 'select',
						'values' => array(
							'True' => GetMessage('BT_CLOBAL_COND_GROUP_SELECT_TRUE'),
							'False' => GetMessage('BT_CLOBAL_COND_GROUP_SELECT_FALSE')
						),
						'defaultText' => GetMessage('BT_CLOBAL_COND_GROUP_SELECT_DEF'),
						'defaultValue' => 'True',
						'first_option' => '...'
					),
					'ATOM' => array(
						'ID' => 'True',
						'FIELD_TYPE' => 'string',
						'FIELD_LENGTH' => 255,
						'MULTIPLE' => 'N',
						'VALIDATE' => 'list'
					)
				)
			)
		);

		if (!$boolEx)
		{
			foreach ($arAtomList as &$arOneControl)
			{
				foreach ($arOneControl as &$arOneAtom)
				{
					$arOneAtom = $arOneAtom['JS'];
				}
				if (isset($arOneAtom))
					unset($arOneAtom);
			}
			if (isset($arOneControl))
				unset($arOneControl);
		}

		if (false === $strControlID)
		{
			return $arAtomList;
		}
		elseif (isset($arAtomList[$strControlID]))
		{
			return $arAtomList[$strControlID];
		}
		else
		{
			return false;
		}
	}

	public static function GetControls($strControlID = false)
	{
		$arAtoms = static::GetAtomsEx();
		$arControlList = array(
			'CondBsktCntGroup' => array(
				'ID' => 'CondBsktCntGroup',
				'GROUP' => 'Y',
				'LABEL' => GetMessage('BT_SALE_COND_GROUP_BASKET_NUMBER_LABEL'),
				'SHOW_IN' => array(parent::GetControlID()),
				'VISUAL' => self::__GetVisual(),
				'ATOMS' => $arAtoms['CondBsktCntGroup']
			),
			'CondBsktAmtGroup' => array(
				'ID' => 'CondBsktAmtGroup',
				'GROUP' => 'Y',
				'LABEL' => GetMessage('BT_SALE_COND_GROUP_BASKET_AMOUNT_LABEL'),
				'SHOW_IN' => array(parent::GetControlID()),
				'VISUAL' => self::__GetVisual(),
				'ATOMS' => $arAtoms['CondBsktAmtGroup']
			),
			'CondBsktProductGroup' => array(
				'ID' => 'CondBsktProductGroup',
				'GROUP' => 'Y',
				'LABEL' => GetMessage('BT_SALE_COND_GROUP_BASKET_PRODUCT_LABEL'),
				'SHOW_IN' => array(parent::GetControlID()),
				'VISUAL' => self::__GetVisual(),
				'ATOMS' => $arAtoms['CondBsktProductGroup']
			),
			'CondBsktRowGroup' => array(
				'ID' => 'CondBsktRowGroup',
				'GROUP' => 'Y',
				'LABEL' => GetMessage('BT_SALE_COND_GROUP_BASKET_ROW_LABEL'),
				'SHOW_IN' => array(parent::GetControlID()),
				'VISUAL' => self::__GetVisual(),
				'ATOMS' => $arAtoms['CondBsktRowGroup']
			),
			'CondBsktSubGroup' => array(
				'ID' => 'CondBsktSubGroup',
				'GROUP' => 'Y',
				'LABEL' => GetMessage('BT_SALE_COND_GROUP_BASKET_SUB_LABEL'),
				'SHOW_IN' => self::GetControlID(),
				'VISUAL' => self::__GetVisual(true),
				'ATOMS' => $arAtoms['CondBsktSubGroup']
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

	private function __GetVisual($boolExt = false)
	{
		$boolExt = (true === $boolExt);
		$arResult = array();
		if ($boolExt)
		{
			$arResult = array(
				'controls' => array(
					'All',
					'True'
				),
				'values' => array(
					array(
						'All' => 'AND',
						'True' => 'True'
					),
					array(
						'All' => 'AND',
						'True' => 'False'
					),
					array(
						'All' => 'OR',
						'True' => 'True'
					),
					array(
						'All' => 'OR',
						'True' => 'False'
					)
				),
				'logic' => array(
					array(
						'style' => 'condition-logic-and',
						'message' => GetMessage('BT_SALE_COND_GROUP_LOGIC_AND')
					),
					array(
						'style' => 'condition-logic-and',
						'message' => GetMessage('BT_SALE_COND_GROUP_LOGIC_NOT_AND')
					),
					array(
						'style' => 'condition-logic-or',
						'message' => GetMessage('BT_SALE_COND_GROUP_LOGIC_OR')
					),
					array(
						'style' => 'condition-logic-or',
						'message' => GetMessage('BT_CLOBAL_COND_GROUP_LOGIC_NOT_OR')
					)
				)
			);
		}
		else
		{
			$arResult = array(
				'controls' => array(
					'All'
				),
				'values' => array(
					array(
						'All' => 'AND'
					),
					array(
						'All' => 'OR'
					),
				),
				'logic' => array(
					array(
						'style' => 'condition-logic-and',
						'message' => GetMessage('BT_SALE_COND_GROUP_LOGIC_AND')
					),
					array(
						'style' => 'condition-logic-or',
						'message' => GetMessage('BT_SALE_COND_GROUP_LOGIC_OR')
					)
				)
			);
		}
		return $arResult;
	}

	private function __GetSubGroupCond($arOneCondition, $arValues, $arParams, $arControl, $arSubs)
	{
		$mxResult = '';
		$boolError = false;

		if (empty($arSubs))
		{
			return '(1 == 1)';
		}

		if (!$boolError)
		{
			$strPrefix = '';
			$strLogic = '';
			$strItemPrefix = '';

			if ('AND' == $arOneCondition['All'])
			{
				$strPrefix = '';
				$strLogic = ' && ';
				$strItemPrefix = ('True' == $arOneCondition['True'] ? '' : '!');
			}
			else
			{
				$strItemPrefix = '';
				if ('True' == $arOneCondition['True'])
				{
					$strPrefix = '';
					$strLogic = ' || ';
				}
				else
				{
					$strPrefix = '!';
					$strLogic = ' && ';
				}
			}

			$strEval = $strItemPrefix.implode($strLogic.$strItemPrefix, $arSubs);
			if ('' != $strPrefix)
				$strEval = $strPrefix.'('.$strEval.')';
			$mxResult = $strEval;
		}

		return $mxResult;
	}

	private function __GetRowGroupCond($arOneCondition, $arValues, $arParams, $arControl, $arSubs)
	{
		$boolError = false;
		$strFunc = '';
		$strCond = '';

		$arLogic = static::SearchLogic(
			$arValues['logic'],
			static::GetLogic(
				array(
					BT_COND_LOGIC_EQ,
					BT_COND_LOGIC_NOT_EQ,
					BT_COND_LOGIC_GR,
					BT_COND_LOGIC_LS,
					BT_COND_LOGIC_EGR,
					BT_COND_LOGIC_ELS
				)
			)
		);

		if (!isset($arLogic['OP']['N']) || empty($arLogic['OP']['N']))
		{
			$boolError = true;
		}
		else
		{
			if (!empty($arSubs))
			{
				$strFuncName = '$salecond'.$arParams['FUNC_ID'];

				$strLogic = ('AND' == $arValues['All'] ? '&&' : '||');

				$strFunc = $strFuncName.'=function($row){';
				$strFunc .= 'return ('.implode(') '.$strLogic.' (', $arSubs).');';
				$strFunc .= '};';

				$strCond = str_replace(
					array('#FIELD#', '#VALUE#'),
					array('CSaleBasketFilter::RowFilter('.$arParams['ORDER'].', '.$strFuncName.')', $arValues['Value']),
					$arLogic['OP']['N']
				);
			}
			else
			{
				$strCond = str_replace(
					array('#FIELD#', '#VALUE#'),
					array('CSaleBasketFilter::RowFilter('.$arParams['ORDER'].', "")', $arValues['Value']),
					$arLogic['OP']['N']
				);
			}
		}

		if (!$boolError)
		{
			if (!empty($strFunc))
			{
				return array(
					'FUNC' => $strFunc,
					'COND' => $strCond,
				);
			}
			else
			{
				return $strCond;
			}
		}
		else
		{
			return '';
		}
	}

	private function __GetProductGroupCond($arOneCondition, $arValues, $arParams, $arControl, $arSubs)
	{
		$strFunc = '';
		$strCond = '';

		if (!empty($arSubs))
		{
			$strFuncName = '$salecond'.$arParams['FUNC_ID'];

			$strLogic = ('AND' == $arValues['All'] ? '&&' : '||');

			$strFunc = $strFuncName.'=function($row){';
			$strFunc .= 'return ('.implode(') '.$strLogic.' (', $arSubs).');';
			$strFunc .= '};';

			$strCond = ('Found' == $arValues['Found'] ? '' : '!').'CSaleBasketFilter::ProductFilter('.$arParams['ORDER'].', '.$strFuncName.')';
		}
		else
		{
			$strCond = ('Found' == $arValues['Found'] ? '' : '!').'CSaleBasketFilter::ProductFilter('.$arParams['ORDER'].', "")';
		}

		if (!empty($strFunc))
		{
			return array(
				'FUNC' => $strFunc,
				'COND' => $strCond,
			);
		}
		else
		{
			return $strCond;
		}
	}

	private function __GetAmtGroupCond($arOneCondition, $arValues, $arParams, $arControl, $arSubs)
	{
		$mxResult = '';
		$boolError = false;

		$strFunc = '';
		$strCond = '';

		$arLogic = static::SearchLogic(
			$arValues['logic'],
			static::GetLogic(
				array(
					BT_COND_LOGIC_EQ,
					BT_COND_LOGIC_NOT_EQ,
					BT_COND_LOGIC_GR,
					BT_COND_LOGIC_LS,
					BT_COND_LOGIC_EGR,
					BT_COND_LOGIC_ELS
				)
			)
		);

		if (!isset($arLogic['OP']['N']) || empty($arLogic['OP']['N']))
		{
			$boolError = true;
		}
		else
		{
			if (!empty($arSubs))
			{
				$strFuncName = '$salecond'.$arParams['FUNC_ID'];

				$strLogic = ('AND' == $arValues['All'] ? '&&' : '||');

				$strFunc = $strFuncName.'=function($row){';
				$strFunc .= 'return ('.implode(') '.$strLogic.' (', $arSubs).');';
				$strFunc .= '};';

				$strCond = str_replace(
					array('#FIELD#', '#VALUE#'),
					array('CSaleBasketFilter::AmountFilter('.$arParams['ORDER'].', '.$strFuncName.')',
					$arValues['Value']),
					$arLogic['OP']['N']
				);
			}
			else
			{
				$strCond = str_replace(
					array('#FIELD#', '#VALUE#'),
					array('CSaleBasketFilter::AmountFilter('.$arParams['ORDER'].', "")',
					$arValues['Value']),
					$arLogic['OP']['N']
				);
			}
		}

		if (!$boolError)
		{
			if (!empty($strFunc))
			{
				return array(
					'FUNC' => $strFunc,
					'COND' => $strCond,
				);
			}
			else
			{
				return $strCond;
			}
		}
		else
		{
			return '';
		}
	}

	private function __GetCntGroupCond($arOneCondition, $arValues, $arParams, $arControl, $arSubs)
	{
		$mxResult = '';
		$boolError = false;

		$strFunc = '';
		$strCond = '';

		$arLogic = static::SearchLogic(
			$arValues['logic'],
			static::GetLogic(
				array(
					BT_COND_LOGIC_EQ,
					BT_COND_LOGIC_NOT_EQ,
					BT_COND_LOGIC_GR,
					BT_COND_LOGIC_LS,
					BT_COND_LOGIC_EGR,
					BT_COND_LOGIC_ELS
				)
			)
		);

		if (!isset($arLogic['OP']['N']) || empty($arLogic['OP']['N']))
		{
			$boolError = true;
		}
		else
		{
			if (!empty($arSubs))
			{
				$strFuncName = '$salecond'.$arParams['FUNC_ID'];

				$strLogic = ('AND' == $arValues['All'] ? '&&' : '||');

				$strFunc = $strFuncName.'=function($row){';
				$strFunc .= 'return ('.implode(') '.$strLogic.' (', $arSubs).');';
				$strFunc .= '};';

				$strCond = str_replace(
					array('#FIELD#', '#VALUE#'),
					array('CSaleBasketFilter::CountFilter('.$arParams['ORDER'].', '.$strFuncName.')',
					$arValues['Value']),
					$arLogic['OP']['N']
				);
			}
			else
			{
				$strCond = str_replace(
					array('#FIELD#', '#VALUE#'),
					array('CSaleBasketFilter::CountFilter('.$arParams['ORDER'].', "")',
					$arValues['Value']),
					$arLogic['OP']['N']
				);
			}
		}

		if (!$boolError)
		{
			if (!empty($strFunc))
			{
				return array(
					'FUNC' => $strFunc,
					'COND' => $strCond,
				);
			}
			else
			{
				return $strCond;
			}
		}
		else
		{
			return '';
		}
	}
}

class CSaleCondCtrlBasketFields extends CSaleCondCtrlComplex
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
			'label' => GetMessage('BT_MOD_SALE_COND_GROUP_BASKET_FIELDS_LABEL'),
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
				$arOne['control'][] = GetMessage('BT_MOD_SALE_COND_MESS_WEIGHT_UNIT');
			}
			$arResult['children'][] = $arOne;
		}
		if (isset($arOneControl))
			unset($arOneControl);

		return $arResult;
	}

	public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
	{
		$strResult = '';

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
				),
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
				'LABEL' => GetMessage('BT_MOD_SALE_COND_BASKET_ROW_PRICE_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_SALE_COND_BASKET_ROW_PRICE_PREFIX'),
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
				'LABEL' => GetMessage('BT_MOD_SALE_COND_BASKET_ROW_QUANTITY_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_SALE_COND_BASKET_ROW_QUANTITY_PREFIX'),
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
				'LABEL' => GetMessage('BT_MOD_SALE_COND_BASKET_ROW_WEIGHT_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_SALE_COND_BASKET_ROW_WEIGHT_PREFIX'),
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

	public static function GetShowIn($arControls)
	{
		$arControls = CSaleCondCtrlBasketGroup::GetControlID();
		return $arControls;
	}
}

class CSaleCondCtrlOrderFields extends CSaleCondCtrlComplex
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
			'label' => GetMessage('BT_MOD_SALE_COND_CMP_ORDER_CONTROLGROUP_LABEL'),
			'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
			'children' => array()
		);
		foreach ($arControls as &$arOneControl)
		{
			$arOne = array();
			if ('CondSaleOrderSumm' == $arOneControl['ID'])
			{
				$arJSControl = array(
					array(
						'id' => 'prefix',
						'type' => 'prefix',
						'text' => $arOneControl['PREFIX']
					),
					static::GetLogicAtom($arOneControl['LOGIC']),
					static::GetValueAtom($arOneControl['JS_VALUE'])
				);
				if (static::$boolInit)
				{
					if (isset(static::$arInitParams['CURRENCY']))
					{
						$arJSControl[] = static::$arInitParams['CURRENCY'];
					}
					elseif (isset(static::$arInitParams['SITE_ID']))
					{
						$strCurrency = CSaleLang::GetLangCurrency(static::$arInitParams['SITE_ID']);
						if (!empty($strCurrency))
						{
							$arJSControl[] = $strCurrency;
						}
					}
				}
				$arOne = array(
					'controlId' => $arOneControl['ID'],
					'group' => ('Y' == $arOneControl['GROUP']),
					'label' => $arOneControl['LABEL'],
					'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
					'control' => $arJSControl
				);
			}
			else
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
			}
			if ('CondSaleOrderWeight' == $arOneControl['ID'])
			{
				$arOne['control'][] = GetMessage('BT_MOD_SALE_COND_MESS_WEIGHT_UNIT');
			}
			$arResult['children'][] = $arOne;
		}
		if (isset($arOneControl))
			unset($arOneControl);

		return $arResult;
	}

	public static function Parse($arOneCondition)
	{
		if (!isset($arOneCondition['controlId']))
			return false;
		$arControl = static::GetControls($arOneCondition['controlId']);
		if (false === $arControl)
			return false;
		return static::Check($arOneCondition, $arOneCondition, $arControl, false);
	}

	public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
	{
		$strResult = '';

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
				$boolMulti = false;
				if (isset($arControl['JS_VALUE']['multiple']) && 'Y' == $arControl['JS_VALUE']['multiple'])
				{
					$boolMulti = true;
					$strJoinOperator = (BT_COND_LOGIC_NOT_EQ == $arLogic['ID'] ? '&&' : '||');
				}
				$strField = $arParams['ORDER'].'[\''.$arControl['FIELD'].'\']';
				switch ($arControl['FIELD_TYPE'])
				{
					case 'int':
					case 'double':
						if (!$boolMulti)
						{
							$strResult = str_replace(array('#FIELD#', '#VALUE#'), array($strField, $arValues['value']), $arLogic['OP'][$arControl['MULTIPLE']]);
						}
						else
						{
							$arResult = array();
							foreach ($arValues['value'] as &$mxValue)
							{
								$arResult[] = str_replace(array('#FIELD#', '#VALUE#'), array($strField, $mxValue), $arLogic['OP'][$arControl['MULTIPLE']]);
							}
							if (isset($mxValue))
								unset($mxValue);
							$strResult = '(('.implode(') '.$strJoinOperator.' (', $arResult).'))';
						}
						break;
					case 'char':
					case 'string':
					case 'text':
						if (!$boolMulti)
						{
							$strResult = str_replace(array('#FIELD#', '#VALUE#'), array($strField, '"'.EscapePHPString($arValues['value']).'"'), $arLogic['OP'][$arControl['MULTIPLE']]);
						}
						else
						{
							$arResult = array();
							foreach ($arValues['value'] as &$mxValue)
							{
								$arResult[] = str_replace(array('#FIELD#', '#VALUE#'), array($strField, '"'.EscapePHPString($mxValue).'"'), $arLogic['OP'][$arControl['MULTIPLE']]);
							}
							if (isset($mxValue))
								unset($mxValue);
							$strResult = '(('.implode(') '.$strJoinOperator.' (', $arResult).'))';
						}
						break;
					case 'date':
					case 'datetime':
						if (!$boolMulti)
						{
							$strResult = str_replace(array('#FIELD#', '#VALUE#'), array($strField, $arValues['value']), $arLogic['OP'][$arControl['MULTIPLE']]);
						}
						else
						{
							$arResult = array();
							foreach ($arValues['value'] as &$mxValue)
							{
								$arResult[] = str_replace(array('#FIELD#', '#VALUE#'), array($strField, $mxValue), $arLogic['OP'][$arControl['MULTIPLE']]);
							}
							if (isset($mxValue))
								unset($mxValue);
							$strResult = '(('.implode(') '.$strJoinOperator.' (', $arResult).'))';
						}
						break;
				}
				$strResult = 'isset('.$strField.') && '.$strResult;
			}
		}

		return (!$boolError ? $strResult : false);
	}

	public static function GetControls($strControlID = false)
	{
		$arSalePersonTypes = array();
		$arFilter = array();
		if (static::$boolInit)
		{
			if (isset(static::$arInitParams['SITE_ID']))
				$arFilter['LID'] = static::$arInitParams['SITE_ID'];
		}
		$rsPersonTypes = CSalePersonType::GetList(array(), $arFilter, false, false, array('ID', 'NAME', 'LIDS'));
		while ($arPersonType = $rsPersonTypes->Fetch())
		{
			$arPersonType['ID'] = intval($arPersonType['ID']);
			$arSalePersonTypes[$arPersonType['ID']] = $arPersonType['NAME'].'('.implode(' ', $arPersonType['LIDS']).')';
		}

		$arSalePaySystemList = array();
		$arFilter = array();
		$rsPaySystems = CSalePaySystem::GetList(array(), $arFilter, false, false, array('ID', 'NAME'));
		while ($arPaySystem = $rsPaySystems->Fetch())
		{
			$arSalePaySystemList[$arPaySystem['ID']] = $arPaySystem['NAME'];
		}

		$arSaleDeliveryList = array();
		$arFilter = array();
		if (static::$boolInit)
		{
			if (isset(static::$arInitParams['SITE_ID']))
				$arFilter['LID'] = static::$arInitParams['SITE_ID'];
		}

		$rsDeliverySystems = CSaleDelivery::GetList(array(), $arFilter, false, false, array('ID', 'LID', 'NAME'));
		while ($arDelivery = $rsDeliverySystems->Fetch())
		{
			$arSaleDeliveryList[$arDelivery['ID']] = $arDelivery['NAME'].' ('.$arDelivery['LID'].')';
		}

		$arFilter = array();
			if (static::$boolInit)
		{
			if (isset(static::$arInitParams['SITE_ID']))
				$arFilter['LID'] = static::$arInitParams['SITE_ID'];
		}
		$rsDeliveryHandlers = CSaleDeliveryHandler::GetList(array(),$arFilter);
		while ($arDeliveryHandler = $rsDeliveryHandlers->Fetch())
		{
			$boolSep = true;
			if (!empty($arDeliveryHandler['PROFILES']) && is_array($arDeliveryHandler['PROFILES']))
			{
				foreach ($arDeliveryHandler['PROFILES'] as $key => $arProfile)
				{
					$arSaleDeliveryList[$arDeliveryHandler['SID'].':'.$key] = $arDeliveryHandler['NAME'];
				}
			}
		}

		$arLabels = array(
			BT_COND_LOGIC_EQ => GetMessage('BT_SALE_AMOUNT_LOGIC_EQ_LABEL'),
			BT_COND_LOGIC_NOT_EQ => GetMessage('BT_SALE_AMOUNT_LOGIC_NOT_EQ_LABEL'),
			BT_COND_LOGIC_GR => GetMessage('BT_SALE_AMOUNT_LOGIC_GR_LABEL'),
			BT_COND_LOGIC_LS => GetMessage('BT_SALE_AMOUNT_LOGIC_LS_LABEL'),
			BT_COND_LOGIC_EGR => GetMessage('BT_SALE_AMOUNT_LOGIC_EGR_LABEL'),
			BT_COND_LOGIC_ELS => GetMessage('BT_SALE_AMOUNT_LOGIC_ELS_LABEL')
		);
		$arLabelsWeight = array(
			BT_COND_LOGIC_EQ => GetMessage('BT_SALE_WEIGHT_LOGIC_EQ_LABEL'),
			BT_COND_LOGIC_NOT_EQ => GetMessage('BT_SALE_WEIGHT_LOGIC_NOT_EQ_LABEL'),
			BT_COND_LOGIC_GR => GetMessage('BT_SALE_WEIGHT_LOGIC_GR_LABEL'),
			BT_COND_LOGIC_LS => GetMessage('BT_SALE_WEIGHT_LOGIC_LS_LABEL'),
			BT_COND_LOGIC_EGR => GetMessage('BT_SALE_WEIGHT_LOGIC_EGR_LABEL'),
			BT_COND_LOGIC_ELS => GetMessage('BT_SALE_WEIGHT_LOGIC_ELS_LABEL')
		);

		$arControlList = array(
			'CondSaleOrderSumm' => array(
				'ID' => 'CondSaleOrderSumm',
				'FIELD' => 'ORDER_PRICE',
				'FIELD_TYPE' => 'double',
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_SALE_COND_CMP_SALE_ORDER_SUMM_LABEL_EXT'),
				'PREFIX' => GetMessage('BT_MOD_SALE_COND_CMP_SALE_ORDER_SUMM_PREFIX_EXT'),
				'LOGIC' => static::GetLogicEx(array_keys($arLabels), $arLabels),
				'JS_VALUE' => array(
					'type' => 'input'
				)
			),
			'CondSalePersonType' => array(
				'ID' => 'CondSalePersonType',
				'FIELD' => 'PERSON_TYPE_ID',
				'FIELD_TYPE' => 'int',
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_SALE_COND_CMP_SALE_PERSON_TYPE_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_SALE_COND_CMP_SALE_PERSON_TYPE_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ)),
				'JS_VALUE' => array(
					'type' => 'select',
					'multiple' => 'Y',
					'values' => $arSalePersonTypes,
					'show_value' => 'Y'
				),
				'PHP_VALUE' => array(
					'VALIDATE' => 'list'
				)
			),
			'CondSalePaySystem' => array(
				'ID' => 'CondSalePaySystem',
				'FIELD' => 'PAY_SYSTEM_ID',
				'FIELD_TYPE' => 'int',
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_SALE_COND_CMP_SALE_PAY_SYSTEM_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_SALE_COND_CMP_SALE_PAY_SYSTEM_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ)),
				'JS_VALUE' => array(
					'type' => 'select',
					'multiple' => 'Y',
					'values' => $arSalePaySystemList,
					'show_value' => 'Y'
				),
				'PHP_VALUE' => array(
					'VALIDATE' => 'list'
				)
			),
			'CondSaleDelivery' => array(
				'ID' => 'CondSaleDelivery',
				'FIELD' => 'DELIVERY_ID',
				'FIELD_TYPE' => 'string',
				'FIELD_LENGTH' => 50,
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_SALE_COND_CMP_SALE_DELIVERY_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_SALE_COND_CMP_SALE_DELIVERY_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ)),
				'JS_VALUE' => array(
					'type' => 'select',
					'multiple' => 'Y',
					'values' => $arSaleDeliveryList,
					'show_value' => 'Y'
				),
				'PHP_VALUE' => array(
					'VALIDATE' => 'list'
				)
			),
			'CondSaleOrderWeight' => array(
				'ID' => 'CondSaleOrderWeight',
				'FIELD' => 'ORDER_WEIGHT',
				'FIELD_TYPE' => 'double',
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_SALE_COND_SALE_ORDER_WEIGHT_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_SALE_COND_SALE_ORDER_WEIGHT_PREFIX'),
				'LOGIC' => static::GetLogicEx(array_keys($arLabelsWeight), $arLabelsWeight),
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

	public static function GetShowIn($arControls)
	{
		$arControls = array(CSaleCondCtrlGroup::GetControlID());
		return $arControls;
	}

	public static function GetJSControl($arControl, $arParams = array())
	{
		return array();
	}
}

class CSaleCondCtrlCommon extends CSaleCondCtrlComplex
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
			'label' => GetMessage('BT_MOD_SALE_COND_CMP_COMMON_CONTROLGROUP_LABEL'),
			'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
			'children' => array()
		);
		foreach ($arControls as &$arOneControl)
		{
			$arLogic = static::GetLogicAtom($arOneControl['LOGIC']);
			$arValue = static::GetValueAtom($arOneControl['JS_VALUE']);
			$arResult['children'][] = array(
				'controlId' => $arOneControl['ID'],
				'group' => false,
				'label' => $arOneControl['LABEL'],
				'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
				'control' => array(
					$arOneControl['PREFIX'],
					$arLogic,
					$arValue
				)
			);
		}
		if (isset($arOneControl))
			unset($arOneControl);

		return $arResult;
	}

	public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
	{
		$strResult = '';

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
				$boolMulti = false;
				if (isset($arControl['JS_VALUE']['multiple']) && 'Y' == $arControl['JS_VALUE']['multiple'])
				{
					$boolMulti = true;
				}
				$intDayOfWeek = "intval(date('N'))";
				if (!$boolMulti)
				{
					$strResult = str_replace(array('#FIELD#', '#VALUE#'), array($intDayOfWeek, $arValues['value']), $arLogic['OP'][$arControl['MULTIPLE']]);
				}
				else
				{
					$arResult = array();
					foreach ($arValues['value'] as &$mxValue)
					{
						$arResult[] = str_replace(array('#FIELD#', '#VALUE#'), array($intDayOfWeek, $mxValue), $arLogic['OP'][$arControl['MULTIPLE']]);
					}
					if (isset($mxValue))
						unset($mxValue);
					$strResult = '(('.implode(') || (', $arResult).'))';
				}
			}
		}

		return (!$boolError ? $strResult : false);
	}

	public static function GetControls($strControlID = false)
	{
		$arDayOfWeek = array(
			1 => GetMessage('BT_MOD_SALE_COND_DAY_OF_WEEK_1'),
			2 => GetMessage('BT_MOD_SALE_COND_DAY_OF_WEEK_2'),
			3 => GetMessage('BT_MOD_SALE_COND_DAY_OF_WEEK_3'),
			4 => GetMessage('BT_MOD_SALE_COND_DAY_OF_WEEK_4'),
			5 => GetMessage('BT_MOD_SALE_COND_DAY_OF_WEEK_5'),
			6 => GetMessage('BT_MOD_SALE_COND_DAY_OF_WEEK_6'),
			7 => GetMessage('BT_MOD_SALE_COND_DAY_OF_WEEK_7')
		);
		$arControlList = array(
			'CondSaleCmnDayOfWeek' => array(
				'ID' => 'CondSaleCmnDayOfWeek',
				'FIELD' => 'DAY_OF_WEEK',
				'FIELD_TYPE' => 'int',
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_SALE_COND_CMP_CMN_DAYOFWEEK_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_SALE_COND_CMP_CMN_DAYOFWEEK_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ)),
				'JS_VALUE' => array(
					'type' => 'select',
					'multiple' => 'Y',
					'values' => $arDayOfWeek
				),
				'PHP_VALUE' => array(
					'VALIDATE' => 'list'
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

	public static function GetShowIn($arControls)
	{
		$arControls = array(CSaleCondCtrlGroup::GetControlID());
		return $arControls;
	}
}

class CSaleCondTree extends CGlobalCondTree
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
				$strFinal = 'function('.$arParams['ORDER'].'){';
				if (!empty($this->arExecuteFunc))
				{
					$strFinal .= implode('; ', $this->arExecuteFunc).'; ';
				}
				$strFinal .= 'return '.$strResult.'; };';
				$strFinal = preg_replace("#;{2,}#",";", $strFinal);
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
					$arResult[] = '('.$strEval.')';
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
						$arResult[] = '('.$strEval.')';
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