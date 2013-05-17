<?
if (!CModule::IncludeModule('catalog'))
{
	return;
}

IncludeModuleLangFile(__FILE__);

class CSaleDiscountActionApply
{
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
				$arOrder['PRICE_DELIVERY'] = $dblResult;
		}
	}

	public static function ApplyBasketDiscount(&$arOrder, $func, $dblValue, $strUnit)
	{
		if (array_key_exists('BASKET_ITEMS', $arOrder) && !empty($arOrder['BASKET_ITEMS']) && is_array($arOrder['BASKET_ITEMS']))
		{
			$arDiscountBasket = (is_callable($func) ? array_filter($arOrder['BASKET_ITEMS'], $func) : $arOrder['BASKET_ITEMS']);
			if (!empty($arDiscountBasket))
			{
				$dblValue = doubleval($dblValue);
				$arResultBasket = array();
				if ('S' == $strUnit)
				{
					$dblSumm = 0.0;
					foreach ($arDiscountBasket as &$arOneRow)
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
						$dblValue = 0;
					}
					$strUnit = 'P';
				}
				if (0 != $dblValue)
				{
					foreach ($arDiscountBasket as $key => $arOneRow)
					{
						if (array_key_exists('CUSTOM_PRICE', $arOneRow) && 'Y' == $arOneRow['CUSTOM_PRICE'])
							continue;
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
							if (array_key_exists('DISCOUNT_PRICE', $arOneRow))
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
							if (array_key_exists('VAT_RATE', $arOneRow))
							{
								$dblVatRate = doubleval($arOneRow["VAT_RATE"]);
								if (0 < $dblVatRate)
									$arOneRow["VAT_VALUE"] = (($arOneRow["PRICE"] / ($dblVatRate + 1)) * $dblVatRate);
							}
							$arResultBasket[$key] = $arOneRow;
						}
					}
					if (!empty($arResultBasket))
					{
						foreach ($arResultBasket as $key => $arOneRow)
						{
							$arOrder['BASKET_ITEMS'][$key] = $arOneRow;
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
			//'visual' => static::GetVisual(),
			'control' => array(
				GetMessage('BT_SALE_ACT_GROUP_GLOBAL_PREFIX'),
			),
		);

		return $arResult;
	}

	public static function GetConditionShow($arParams)
	{
		return array(
			'id' => $arParams['COND_NUM'],
			'controlId' => static::GetControlID(),
			'values' => array(),
		);
	}

	public static function Parse($arOneCondition)
	{
		return array(
			'All' => 'AND',
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
		$arAtoms = static::GetAtoms();
		$boolCurrency = false;
		if (static::$boolInit)
		{
			if (array_key_exists('CURRENCY', static::$arInitParams))
			{
				$arAtoms['Unit']['values']['Cur'] = static::$arInitParams['CURRENCY'];
				$boolCurrency = true;
			}
			elseif (array_key_exists('SITE_ID', static::$arInitParams))
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
				$arAtoms['Unit'],
			),
		);

		return $arResult;
	}

	public static function GetAtoms()
	{
		return array(
			'Type' => array(
				'id' => 'Type',
				'name' => 'extra',
				'type' => 'select',
				'values' => array(
					'Discount' => GetMessage('BT_SALE_ACT_DELIVERY_SELECT_TYPE_DISCOUNT'),
					'Extra' => GetMessage('BT_SALE_ACT_DELIVERY_SELECT_TYPE_EXTRA'),
				),
				'defaultText' => GetMessage('BT_SALE_ACT_DELIVERY_SELECT_TYPE_DEF'),
				'defaultValue' => 'Discount',
				'first_option' => '...',
			),
			'Value' => array(
				'id' => 'Value',
				'name' => 'extra_size',
				'type' => 'input',
				'value_type' => 'double',
			),
			'Unit' => array(
				'id' => 'Unit',
				'name' => 'extra_unit',
				'type' => 'select',
				'values' => array(
					'Perc' => GetMessage('BT_SALE_ACT_DELIVERY_SELECT_PERCENT'),
					'Cur' => GetMessage('BT_SALE_ACT_DELIVERY_SELECT_CUR'),
				),
				'defaultText' => GetMessage('BT_SALE_ACT_DELIVERY_SELECT_UNIT_DEF'),
				'defaultValue' => 'Perc',
				'first_option' => '...',
			),
		);
	}

	public static function GetShowIn($arControls)
	{
		return array(CSaleActionCtrlGroup::GetControlID());
	}

	public static function GetConditionShow($arParams)
	{
		if (!isset($arParams['ID']))
			return false;
		if ($arParams['ID'] != static::GetControlID())
			return false;
		$arControl = array(
			'ID' => $arParams['ID'],
			'ATOMS' => static::GetAtoms(),
		);

		return static::Check($arParams['DATA'], $arParams, $arControl, true);
	}

	public static function Parse($arOneCondition)
	{
		if (!isset($arOneCondition['controlId']))
			return false;
		if ($arOneCondition['controlId'] != static::GetControlID())
			return false;
		$arControl = array(
			'ID' => $arOneCondition['controlId'],
			'ATOMS' => static::GetAtoms(),
		);

		return static::Check($arOneCondition, $arOneCondition, $arControl, false);
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
					'ATOMS' => static::GetAtoms(),
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

	public static function Check($arOneCondition, $arParams, $arControl, $boolShow)
	{
		$arResult = array();

		$boolShow = (true === $boolShow);
		$boolError = false;
		$boolFatalError = false;
		$arMsg = array();

		$arValues = array(
		);

		if (!isset($arControl['ATOMS']) || !is_array($arControl['ATOMS']) || empty($arControl['ATOMS']))
		{
			$boolFatalError = true;
			$boolError = true;
			$arMsg[] = GetMessage('BT_SALE_ACT_ERR_ATOMS_ABSENT');
		}
		if (!$boolError)
		{
			if ($boolShow)
			{
				foreach ($arControl['ATOMS'] as &$arOneAtom)
				{
					$boolAtomError = false;
					if (!isset($arOneCondition[$arOneAtom['id']]))
					{
						$boolAtomError = true;
					}
					elseif (!is_string($arOneCondition[$arOneAtom['id']]))
					{
						$boolAtomError = true;
					}
					if (!$boolAtomError)
					{
						switch ($arOneAtom['type'])
						{
							case 'select':
								if (!array_key_exists($arOneCondition[$arOneAtom['id']], $arOneAtom['values']))
								{
									$boolAtomError = true;
								}
								break;
							default:
								if (array_key_exists('value_type', $arOneAtom) && !empty($arOneAtom['value_type']))
								{
									switch($arOneAtom['value_type'])
									{
										case 'int':
											$arOneCondition[$arOneAtom['id']] = intval($arOneCondition[$arOneAtom['id']]);
											break;
										case 'double':
											$arOneCondition[$arOneAtom['id']] = doubleval($arOneCondition[$arOneAtom['id']]);
											break;
									}
								}
								break;
						}
					}
					if (!$boolAtomError)
					{
						$arValues[$arOneAtom['id']] = (string)$arOneCondition[$arOneAtom['id']];
					}
					else
					{
						$arValues[$arOneAtom['id']] = '';
					}
					if ($boolAtomError)
						$boolError = true;
				}
				if (isset($arOneAtom))
					unset($arOneAtom);
			}
			else
			{
				foreach ($arControl['ATOMS'] as &$arOneAtom)
				{
					$boolAtomError = false;
					if (!isset($arOneCondition[$arOneAtom['name']]))
					{
						$boolAtomError = true;
					}
					elseif (!is_string($arOneCondition[$arOneAtom['name']]) && !is_int($arOneCondition[$arOneAtom['name']]) && !is_float($arOneCondition[$arOneAtom['name']]))
					{
						$boolAtomError = true;
					}
					if (!$boolAtomError)
					{
						switch ($arOneAtom['type'])
						{
							case 'select':
								if (!array_key_exists($arOneCondition[$arOneAtom['name']], $arOneAtom['values']))
								{
									$boolAtomError = true;
								}
								break;
							default:
								if (array_key_exists('value_type', $arOneAtom) && !empty($arOneAtom['value_type']))
								{
									switch($arOneAtom['value_type'])
									{
										case 'int':
											$arOneCondition[$arOneAtom['name']] = intval($arOneCondition[$arOneAtom['name']]);
											break;
										case 'double':
											$arOneCondition[$arOneAtom['name']] = doubleval($arOneCondition[$arOneAtom['name']]);
											break;
									}
								}
								break;
						}
						if (!$boolAtomError)
						{
							$arValues[$arOneAtom['id']] = (string)$arOneCondition[$arOneAtom['name']];
						}
					}
					if ($boolAtomError)
						$boolError = true;
				}
				if (isset($arOneAtom))
					unset($arOneAtom);
			}
		}

		if ($boolShow)
		{
			$arResult = array(
				'id' => $arParams['COND_NUM'],
				'controlId' => $arControl['ID'],
				'values' => $arValues,
			);
			if ($boolError)
			{
				$arResult['err_cond'] = 'Y';
				if ($boolFatalError)
					$arResult['fatal_err_cond'] = 'Y';
				if (!empty($arMsg))
					$arResult['err_cond_mess'] = implode('. ', $arMsg);
			}

			return $arResult;
		}
		else
		{
			$arResult = $arValues;
			return (!$boolError ? $arResult : false);
		}
	}
}

class CSaleActionCtrlBasketGroup extends CGlobalCondCtrlGroup
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
		$arAtoms = static::GetAtoms();
		$boolCurrency = false;
		if (static::$boolInit)
		{
			if (array_key_exists('CURRENCY', static::$arInitParams))
			{
				$arAtoms['Unit']['values']['CurEach'] = str_replace('#CUR#', static::$arInitParams['CURRENCY'], $arAtoms['Unit']['values']['CurEach']);
				$arAtoms['Unit']['values']['CurAll'] = str_replace('#CUR#', static::$arInitParams['CURRENCY'], $arAtoms['Unit']['values']['CurAll']);
				$boolCurrency = true;
			}
			elseif (array_key_exists('SITE_ID', static::$arInitParams))
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
				$arAtoms['All'],
			),
			'mess' => array(
				'ADD_CONTROL' => GetMessage('BT_SALE_SUBACT_ADD_CONTROL'),
				'SELECT_CONTROL' => GetMessage('BT_SALE_SUBACT_SELECT_CONTROL'),
			),
		);
	}

	public static function GetShowIn($arControls)
	{
		return array(CSaleActionCtrlGroup::GetControlID());
	}

	public static function GetAtoms()
	{
		return array(
			'Type' => array(
				'id' => 'Type',
				'name' => 'extra',
				'type' => 'select',
				'values' => array(
					'Discount' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_TYPE_DISCOUNT'),
					'Extra' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_TYPE_EXTRA'),
				),
				'defaultText' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_TYPE_DEF'),
				'defaultValue' => 'Discount',
				'first_option' => '...',
			),
			'Value' => array(
				'id' => 'Value',
				'name' => 'extra_size',
				'type' => 'input',
			),
			'Unit' => array(
				'id' => 'Unit',
				'name' => 'extra_unit',
				'type' => 'select',
				'values' => array(
					'Perc' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_PERCENT'),
					'CurEach' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_CUR_EACH'),
					'CurAll' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_CUR_ALL'),
				),
				'defaultText' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_UNIT_DEF'),
				'defaultValue' => 'Perc',
				'first_option' => '...',
			),
			'All' => array(
				'id' => 'All',
				'name' => 'aggregator',
				'type' => 'select',
				'values' => array(
					'AND' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_ALL'),
					'OR' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_ANY'),
				),
				'defaultText' => GetMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_DEF'),
				'defaultValue' => 'AND',
				'first_option' => '...',
			),
		);
	}

	public static function GetConditionShow($arParams)
	{
		if (!isset($arParams['ID']))
			return false;
		if ($arParams['ID'] != static::GetControlID())
			return false;
		$arControl = array(
			'ID' => $arParams['ID'],
			'ATOMS' => static::GetAtoms(),
		);

		return static::Check($arParams['DATA'], $arParams, $arControl, true);
	}

	public static function Parse($arOneCondition)
	{
		if (!isset($arOneCondition['controlId']))
			return false;
		if ($arOneCondition['controlId'] != static::GetControlID())
			return false;
		$arControl = array(
			'ID' => $arOneCondition['controlId'],
			'ATOMS' => static::GetAtoms(),
		);

		return static::Check($arOneCondition, $arOneCondition, $arControl, false);
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

	public static function Check($arOneCondition, $arParams, $arControl, $boolShow)
	{
		$arResult = array();

		$boolShow = (true === $boolShow);
		$boolError = false;
		$boolFatalError = false;
		$arMsg = array();

		$arValues = array(
		);

		if (!isset($arControl['ATOMS']) || !is_array($arControl['ATOMS']) || empty($arControl['ATOMS']))
		{
			$boolFatalError = true;
			$boolError = true;
			$arMsg[] = GetMessage('BT_SALE_ACT_ERR_ATOMS_ABSENT');
		}
		if (!$boolError)
		{
			if ($boolShow)
			{
				foreach ($arControl['ATOMS'] as &$arOneAtom)
				{
					$boolAtomError = false;
					if (!isset($arOneCondition[$arOneAtom['id']]))
					{
						$boolAtomError = true;
					}
					elseif (!is_string($arOneCondition[$arOneAtom['id']]))
					{
						$boolAtomError = true;
					}
					if (!$boolAtomError)
					{
						switch ($arOneAtom['type'])
						{
							case 'select':
								if (!array_key_exists($arOneCondition[$arOneAtom['id']], $arOneAtom['values']))
								{
									$boolAtomError = true;
								}
								break;
							default:
								if (array_key_exists('value_type', $arOneAtom) && !empty($arOneAtom['value_type']))
								{
									switch($arOneAtom['value_type'])
									{
										case 'int':
											$arOneCondition[$arOneAtom['id']] = intval($arOneCondition[$arOneAtom['id']]);
											break;
										case 'double':
											$arOneCondition[$arOneAtom['id']] = doubleval($arOneCondition[$arOneAtom['id']]);
											break;
									}
								}
								break;
						}
					}
					if (!$boolAtomError)
					{
						$arValues[$arOneAtom['id']] = (string)$arOneCondition[$arOneAtom['id']];
					}
					else
					{
						$arValues[$arOneAtom['id']] = '';
					}
					if ($boolAtomError)
						$boolError = true;
				}
				if (isset($arOneAtom))
					unset($arOneAtom);
			}
			else
			{
				foreach ($arControl['ATOMS'] as &$arOneAtom)
				{
					$boolAtomError = false;
					if (!isset($arOneCondition[$arOneAtom['name']]))
					{
						$boolAtomError = true;
					}
					elseif (!is_string($arOneCondition[$arOneAtom['name']]) && !is_int($arOneCondition[$arOneAtom['name']]) && !is_float($arOneCondition[$arOneAtom['name']]))
					{
						$boolAtomError = true;
					}
					if (!$boolAtomError)
					{
						switch ($arOneAtom['type'])
						{
							case 'select':
								if (!array_key_exists($arOneCondition[$arOneAtom['name']], $arOneAtom['values']))
								{
									$boolAtomError = true;
								}
								break;
							default:
								if (array_key_exists('value_type', $arOneAtom) && !empty($arOneAtom['value_type']))
								{
									switch($arOneAtom['value_type'])
									{
										case 'int':
											$arOneCondition[$arOneAtom['name']] = intval($arOneCondition[$arOneAtom['name']]);
											break;
										case 'double':
											$arOneCondition[$arOneAtom['name']] = doubleval($arOneCondition[$arOneAtom['name']]);
											break;
									}
								}
								break;
						}
						if (!$boolAtomError)
						{
							$arValues[$arOneAtom['id']] = (string)$arOneCondition[$arOneAtom['name']];
						}
					}
					if ($boolAtomError)
						$boolError = true;
				}
				if (isset($arOneAtom))
					unset($arOneAtom);
			}
		}

		if ($boolShow)
		{
			$arResult = array(
				'id' => $arParams['COND_NUM'],
				'controlId' => $arControl['ID'],
				'values' => $arValues,
			);
			if ($boolError)
			{
				$arResult['err_cond'] = 'Y';
				if ($boolFatalError)
					$arResult['fatal_err_cond'] = 'Y';
				if (!empty($arMsg))
					$arResult['err_cond_mess'] = implode('. ', $arMsg);
			}

			return $arResult;
		}
		else
		{
			$arResult = $arValues;
			return (!$boolError ? $arResult : false);
		}
	}

	public static function GetVisual()
	{
		return array(
			'controls' => array(
				'All',
			),
			'values' => array(
				array(
					'All' => 'AND',
				),
				array(
					'All' => 'OR',
				),
			),
			'logic' => array(
				array(
					'style' => 'condition-logic-and',
					'message' => GetMessage('BT_SALE_ACT_GROUP_LOGIC_AND'),
				),
				array(
					'style' => 'condition-logic-or',
					'message' => GetMessage('BT_SALE_ACT_GROUP_LOGIC_OR'),
				),
			)
		);
	}
}

class CSaleActionCtrlGiftsGroup extends CGlobalCondCtrlGroup
{
	public static function GetClassName()
	{
		return __CLASS__;
	}

	public static function GetControlID()
	{
		return 'ActSaleGiftsGrp';
	}

	public static function GetControlShow($arParams)
	{
		//$arAtoms = static::GetAtoms();
		return array(
			'controlId' => static::GetControlID(),
			'group' => true,
			'label' => GetMessage('BT_SALE_ACT_GROUP_GIFTS_LABEL'),
			'defaultText' => GetMessage('BT_SALE_ACT_GROUP_GIFTS_DEF_TEXT'),
			'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
//			'visual' => static::GetVisual(),
			'control' => array(
				GetMessage('BT_SALE_ACT_GROUP_GIFTS_PREFIX'),
			),
			'mess' => array(
				'ADD_CONTROL' => GetMessage('BT_SALE_SUBACT_ADD_CONTROL'),
				'SELECT_CONTROL' => GetMessage('BT_SALE_SUBACT_SELECT_CONTROL'),
			),
		);
	}

	public static function GetShowIn($arControls)
	{
		return array(CSaleActionCtrlGroup::GetControlID());
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
						'text' => $arOneControl['PREFIX'],
					),
					static::GetLogicAtom($arOneControl['LOGIC']),
					static::GetValueAtom($arOneControl['JS_VALUE']),
				),
			);
			if ('CondBsktFldPrice' == $arOneControl['ID'])
			{
				$boolCurrency = false;
				if (static::$boolInit)
				{
					if (array_key_exists('CURRENCY', static::$arInitParams))
					{
						$arOne['control'][] = static::$arInitParams['CURRENCY'];
						$boolCurrency = true;
					}
					elseif (array_key_exists('SITE_ID', static::$arInitParams))
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
					),
					'param_id' => 'n',
					'show_value' => 'Y',
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
					'type' => 'input',
				),
				'PHP_VALUE' => '',
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
				),
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
				),
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
				),
			),
		);

		if (false === $strControlID)
		{
			return $arControlList;
		}
		elseif (array_key_exists($strControlID, $arControlList))
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

class CSaleActionCtrlBasketProductFields extends CSaleActionCtrlComplex
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
			'label' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_CONTROLGROUP_LABEL'),
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
					array(
						'id' => 'prefix',
						'type' => 'prefix',
						'text' => $arOneControl['PREFIX'],
					),
					$arLogic,
					$arValue,
				),
			);
		}
		if (isset($arOneControl))
			unset($arOneControl);

		return $arResult;
	}

	public static function GetShowIn($arControls)
	{
		$arControls = array(CSaleActionCtrlBasketGroup::GetControlID(), CSaleActionCtrlSubGroup::GetControlID());
		return $arControls;
	}

	public static function GetControls($strControlID = false)
	{
		$arControlList = array(
/*			'CondIBElement' => array(
				'ID' => 'CondIBElement',
				'FIELD' => 'ID',
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
					),
					'param_id' => 'n',
					'show_value' => 'Y',
				),
				'PHP_VALUE' => array(
					'VALIDATE' => 'element'
				),
			), */
			'CondIBIBlock' => array(
				'ID' => 'CondIBIBlock',
				'FIELD' => 'IBLOCK_ID',
				'FIELD_TYPE' => 'int',
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_IBLOCK_ID_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_IBLOCK_ID_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ)),
				'JS_VALUE' => array(
					'type' => 'popup',
					'popup_url' =>  '/bitrix/admin/cat_iblock_search.php',
					'popup_params' => array(
						'lang' => LANGUAGE_ID,
					),
					'param_id' => 'n',
					'show_value' => 'Y',
				),
				'PHP_VALUE' => array(
					'VALIDATE' => 'iblock'
				),
			),
			'CondIBSection' => array(
				'ID' => 'CondIBSection',
				'FIELD' => 'SECTION_ID',
				'FIELD_TYPE' => 'int',
				'MULTIPLE' => 'Y',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_SECTION_ID_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_SECTION_ID_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ)),
				'JS_VALUE' => array(
					'type' => 'popup',
					'popup_url' =>  '/bitrix/admin/cat_section_search.php',
					'popup_params' => array(
						'lang' => LANGUAGE_ID,
					),
					'param_id' => 'n',
					'show_value' => 'Y',
				),
				'PHP_VALUE' => array(
					'VALIDATE' => 'section'
				),
			),
			'CondIBCode' => array(
				'ID' => 'CondIBCode',
				'FIELD' => 'CODE',
				'FIELD_TYPE' => 'string',
				'FIELD_LENGTH' => 255,
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_CODE_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_CODE_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT)),
				'JS_VALUE' => array(
					'type' => 'input',
				),
				'PHP_VALUE' => '',
			),
			'CondIBXmlID' => array(
				'ID' => 'CondIBXmlID',
				'FIELD' => 'XML_ID',
				'FIELD_TYPE' => 'string',
				'FIELD_LENGTH' => 255,
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_XML_ID_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_XML_ID_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT)),
				'JS_VALUE' => array(
					'type' => 'input',
				),
				'PHP_VALUE' => '',
			),
/*			'CondIBName' => array(
				'ID' => 'CondIBName',
				'FIELD' => 'NAME',
				'FIELD_TYPE' => 'string',
				'FIELD_LENGTH' => 255,
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_NAME_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_NAME_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT)),
				'JS_VALUE' => array(
					'type' => 'input',
				),
				'PHP_VALUE' => '',
			), */
			'CondIBPreviewText' => array(
				'ID' => 'CondIBPreviewText',
				'FIELD' => 'PREVIEW_TEXT',
				'FIELD_TYPE' => 'text',
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_PREVIEW_TEXT_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_PREVIEW_TEXT_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT)),
				'JS_VALUE' => array(
					'type' => 'input',
				),
				'PHP_VALUE' => '',
			),
			'CondIBDetailText' => array(
				'ID' => 'CondIBDetailText',
				'FIELD' => 'DETAIL_TEXT',
				'FIELD_TYPE' => 'text',
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_DETAIL_TEXT_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_DETAIL_TEXT_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT)),
				'JS_VALUE' => array(
					'type' => 'input',
				),
				'PHP_VALUE' => '',
			),
			'CondIBTags' => array(
				'ID' => 'CondIBTags',
				'FIELD' => 'TAGS',
				'FIELD_TYPE' => 'string',
				'FIELD_LENGTH' => 255,
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_TAGS_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_TAGS_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT)),
				'JS_VALUE' => array(
					'type' => 'input',
				),
				'PHP_VALUE' => '',
			),
			'CondCatQuantity' => array(
				'ID' => 'CondCatQuantity',
				'FIELD' => 'CATALOG_QUANTITY',
				'FIELD_TYPE' => 'double',
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => GetMessage('BT_MOD_CATALOG_COND_CMP_CATALOG_QUANTITY_LABEL'),
				'PREFIX' => GetMessage('BT_MOD_CATALOG_COND_CMP_CATALOG_QUANTITY_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_GR, BT_COND_LOGIC_LS, BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS)),
				'JS_VALUE' => array(
					'type' => 'input',
				),
			),
		);

		if (false === $strControlID)
		{
			return $arControlList;
		}
		elseif (array_key_exists($strControlID, $arControlList))
		{
			return $arControlList[$strControlID];
		}
		else
		{
			return false;
		}
	}
}

class CSaleActionCtrlBasketProductProps extends CSaleActionCtrlComplex
{
	public static function GetClassName()
	{
		return __CLASS__;
	}

	public static function GetControlShow($arParams)
	{
		$arControls = static::GetControls();
		$arResult = array();
		$intCount = -1;
		foreach ($arControls as &$arOneControl)
		{
			if (isset($arOneControl['SEP']) && 'Y' == $arOneControl['SEP'])
			{
				$intCount++;
				$arResult[$intCount] = array(
					'controlgroup' => true,
					'group' =>  false,
					'label' => $arOneControl['SEP_LABEL'],
					'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
					'children' => array()
				);
			}
			$arLogic = static::GetLogicAtom($arOneControl['LOGIC']);
			$arValue = static::GetValueAtom($arOneControl['JS_VALUE']);

			$arResult[$intCount]['children'][] = array(
				'controlId' => $arOneControl['ID'],
				'group' => false,
				'label' => $arOneControl['LABEL'],
				'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
				'control' => array(
					array(
						'id' => 'prefix',
						'type' => 'prefix',
						'text' => $arOneControl['PREFIX'],
					),
					$arLogic,
					$arValue,
				),
			);
		}
		if (isset($arOneControl))
			unset($arOneControl);

		return $arResult;
	}

	public static function GetShowIn($arControls)
	{
		$arControls = array(CSaleActionCtrlBasketGroup::GetControlID(), CSaleActionCtrlSubGroup::GetControlID());
		return $arControls;
	}

	public static function GetControls($strControlID = false)
	{
		$arControlList = array();
		$arIBlockList = array();
		$rsIBlocks = CCatalog::GetList(array(), array(), false, false, array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID'));
		while ($arIBlock = $rsIBlocks->Fetch())
		{
			$arIBlock['IBLOCK_ID'] = intval($arIBlock['IBLOCK_ID']);
			$arIBlock['PRODUCT_IBLOCK_ID'] = intval($arIBlock['PRODUCT_IBLOCK_ID']);
			if (0 < $arIBlock['IBLOCK_ID'])
				$arIBlockList[] = $arIBlock['IBLOCK_ID'];
			if (0 < $arIBlock['PRODUCT_IBLOCK_ID'])
				$arIBlockList[] = $arIBlock['PRODUCT_IBLOCK_ID'];
		}
		if (!empty($arIBlockList))
		{
			$arIBlockList = array_values(array_unique($arIBlockList));
			foreach ($arIBlockList as &$intIBlockID)
			{
				$strName = CIBlock::GetArrayByID($intIBlockID, 'NAME');
				if (false !== $strName)
				{
					$boolSep = true;
					$rsProps = CIBlockProperty::GetList(array('SORT' => 'ASC', 'NAME' => 'ASC'), array('IBLOCK_ID' => $intIBlockID));
					while ($arProp = $rsProps->Fetch())
					{
						if ('CML2_LINK' == $arProp['XML_ID'])
							continue;
						if ('F' == $arProp['PROPERTY_TYPE'])
							continue;
						if ('L' == $arProp['PROPERTY_TYPE'])
						{
							$arProp['VALUES'] = array();
							$rsPropEnums = CIBlockPropertyEnum::GetList(array('DEF' => 'DESC', 'SORT' => 'ASC'), array('PROPERTY_ID' => $arProp['ID']));
							while ($arPropEnum = $rsPropEnums->Fetch())
							{
								$arProp['VALUES'][] = $arPropEnum;
							}
							if (empty($arProp['VALUES']))
								continue;
						}

						$strFieldType = '';
						$arLogic = array();
						$arValue = array();
						$arPhpValue = '';

						$boolUserType = false;
						if (isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE']))
						{
							switch ($arProp['USER_TYPE'])
							{
								case 'DateTime':
									$strFieldType = 'datetime';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_GR, BT_COND_LOGIC_LS, BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS));
									$arValue = array('type' => 'datetime');
									$boolUserType = true;
									break;
								default:
									$boolUserType = false;
									break;
							}
						}

						if (!$boolUserType)
						{
							switch ($arProp['PROPERTY_TYPE'])
							{
								case 'N':
									$strFieldType = 'double';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_GR, BT_COND_LOGIC_LS, BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS));
									$arValue = array('type' => 'input');
									break;
								case 'S':
									$strFieldType = 'text';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT));
									$arValue = array('type' => 'input');
									break;
								case 'L':
									$strFieldType = 'int';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ));
									$arValue = array(
										'type' => 'select',
										'values' => array(),
									);
									foreach ($arProp['VALUES'] as &$arOnePropValue)
									{
										$arValue['values'][$arOnePropValue['ID']] = $arOnePropValue['VALUE'];
									}
									if (isset($arOnePropValue))
										unset($arOnePropValue);
									break;
									$arPhpValue = array('VALIDATE' => 'list');
								case 'E':
									$strFieldType = 'int';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ));
									$arValue = array(
										'type' => 'popup',
										'popup_url' =>  '/bitrix/admin/iblock_element_search.php',
										'popup_params' => array(
											'lang' => LANGUAGE_ID,
											'IBLOCK_ID' => $arProp['LINK_IBLOCK_ID']
										),
										'param_id' => 'n',
									);
									$arPhpValue = array('VALIDATE' => 'element');
									break;
								case 'G':
									$strFieldType = 'int';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ));
									$arValue = array(
										'type' => 'popup',
										'popup_url' =>  '/bitrix/admin/cat_section_search.php',
										'popup_params' => array(
											'lang' => LANGUAGE_ID,
											'IBLOCK_ID' => $arProp['LINK_IBLOCK_ID']
										),
										'param_id' => 'n',
									);
									$arPhpValue = array('VALIDATE' => 'section');
									break;
							}
						}
						$arControlList["CondIBProp:".$intIBlockID.':'.$arProp['ID']] = array(
							"ID" => "CondIBProp:".$intIBlockID.':'.$arProp['ID'],
							"IBLOCK_ID" => $intIBlockID,
							"FIELD" => "PROPERTY_".$arProp['ID']."_VALUE",
							"FIELD_TYPE" => $strFieldType,
							'MULTIPLE' => 'Y',
							'GROUP' => 'N',
							'SEP' => ($boolSep ? 'Y' : 'N'),
							'SEP_LABEL' => ($boolSep ? str_replace(array('#ID#', '#NAME#'), array($intIBlockID, $strName), GetMessage('BT_MOD_CATALOG_COND_CMP_CATALOG_PROP_LABEL')) : ''),
							'LABEL' => $arProp['NAME'],
							'PREFIX' => str_replace(array('#NAME#', '#IBLOCK_ID#', '#IBLOCK_NAME#'), array($arProp['NAME'], $intIBlockID, $strName), GetMessage('BT_MOD_CATALOG_COND_CMP_CATALOG_ONE_PROP_PREFIX')),
							'LOGIC' => $arLogic,
							'JS_VALUE' => $arValue,
							'PHP_VALUE' => $arPhpValue,
						);

						$boolSep = false;
					}
				}
			}
			if (isset($intIBlockID))
				unset($intIBlockID);
		}

		if (false === $strControlID)
		{
			return $arControlList;
		}
		elseif (array_key_exists($strControlID, $arControlList))
		{
			return $arControlList[$strControlID];
		}
		else
		{
			return false;
		}
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

	static public function Generate($arConditions, $arParams)
	{
		$strFinal = '';
		$this->arExecuteFunc = array();
		if (!$this->boolError)
		{
			$strResult = '';
			if (is_array($arConditions) && !empty($arConditions))
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

	static public function GenerateLevel(&$arLevel, $arParams, $boolFirst = false)
	{
		$arResult = array();
		$boolError = false;
		$boolFirst = (true === $boolFirst);
		if (!is_array($arLevel) || empty($arLevel))
		{
			return $arResult;
		}
		if (!array_key_exists('FUNC_ID', $arParams))
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
					if (array_key_exists('Generate', $arOneControl))
					{
						$strEval = false;
						if (isset($arOneControl['GROUP']) && 'Y' == $arOneControl['GROUP'])
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
								if (array_key_exists('FUNC', $mxEval))
								{
									$this->arExecuteFunc[] = $mxEval['FUNC'];
								}
								if (array_key_exists('COND', $mxEval))
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
						if (array_key_exists('Generate', $arOneControl))
						{
							$strEval = false;
							if (isset($arOneControl['GROUP']) && 'Y' == $arOneControl['GROUP'])
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
									if (array_key_exists('FUNC', $mxEval))
									{
										$this->arExecuteFunc[] = $mxEval['FUNC'];
									}
									if (array_key_exists('COND', $mxEval))
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
				if (0 >= strlen ($value) || '()' == $value)
					unset($arResult[$key]);
			}
		}
		if (!empty($arResult))
			$arResult = array_values($arResult);

		return $arResult;
	}
}
?>