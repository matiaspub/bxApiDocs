<?
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Sale;

if (!Loader::includeModule('catalog'))
	return;

Loc::loadMessages(__FILE__);

class CSaleDiscountActionApply
{
	const VALUE_TYPE_FIX = 'F';
	const VALUE_TYPE_PERCENT = 'P';
	const VALUE_TYPE_SUMM = 'S';

	const ORDER_MANUAL_MODE_FIELD = 'ORDER_MANUAL_MODE';
	const BASKET_APPLIED_FIELD = 'DISCOUNT_APPLIED';

	const EPS = 1E-12;

	private static $resetFields = array('DISCOUNT_PRICE', 'PRICE', 'VAT_VALUE', 'PRICE_DEFAULT');
	protected static $getPercentFromBasePrice = null;

	/**
	 * Check discount calculate mode field for order.
	 *
	 * @param array $order			Order data.
	 * @return bool
	 */
	public static function isManualMode($order)
	{
		return (isset($order[self::ORDER_MANUAL_MODE_FIELD]) && $order[self::ORDER_MANUAL_MODE_FIELD] === true);
	}

	/**
	 * Set discount calculate mode field for order.
	 *
	 * @param array &$order			Order data.
	 * @return void
	 */
	public static function setManualMode(&$order)
	{
		if (empty($order) || empty($order['ID']))
			return;
		$order[self::ORDER_MANUAL_MODE_FIELD] = true;
	}

	/**
	 * Erase discount calculate mode field for order.
	 *
	 * @param array &$order			Order data.
	 * @return void
	 */
	public static function clearManualMode(&$order)
	{
		if (empty($order) || !is_array($order))
			return;
		if (array_key_exists(self::ORDER_MANUAL_MODE_FIELD, $order))
			unset($order[self::ORDER_MANUAL_MODE_FIELD]);
	}

	/**
	 * Return true, if discount already applied by basket item.
	 *
	 * @param array $row			Basket row.
	 * @return bool
	 */
	public static function filterApplied($row)
	{
		return (isset($row[self::BASKET_APPLIED_FIELD]));
	}

	/**
	 * Fill basket applied information.
	 *
	 * @param array &$order			Order data.
	 * @param array $basket			Applied information (key - BASKET_ID, value - Y/N).
	 * @return void
	 */
	public static function fillBasketApplied(&$order, $basket)
	{
		if (empty($order) || empty($order['ID']) || empty($order['BASKET_ITEMS']) || !is_array($order['BASKET_ITEMS']))
			return;
		if (empty($basket) || !is_array($basket))
			return;
		$founded = false;
		foreach ($basket as $itemId => $value)
		{
			foreach ($order['BASKET_ITEMS'] as &$basketRow)
			{
				if (isset($basketRow['ID']) && $basketRow['ID'] == $itemId)
				{
					$founded = true;
					$basketRow[self::BASKET_APPLIED_FIELD] = $value;
					break;
				}
			}
			unset($basketRow);
		}
		unset($value, $itemId);
		if ($founded)
			self::setManualMode($order);
	}

	/**
	 * Clear basket applied information.
	 *
	 * @param array &$order				Order data.
	 * @return void
	 */
	public static function clearBasketApplied(&$order)
	{
		if (empty($order) || empty($order['ID']) || empty($order['BASKET_ITEMS']) || !is_array($order['BASKET_ITEMS']))
			return;
		foreach ($order['BASKET_ITEMS'] as &$basketRow)
		{
			if (array_key_exists(self::BASKET_APPLIED_FIELD, $basketRow))
				unset($basketRow[self::BASKET_APPLIED_FIELD]);
		}
		unset($basketRow);
	}

	/**
	 * Filter for undiscount basket items.
	 *
	 * @param array $row		Basket item.
	 * @return bool
	 */
	public static function ClearBasket($row)
	{
		return (
			(!isset($row['CUSTOM_PRICE']) || $row['CUSTOM_PRICE'] != 'Y') &&
			(
				(isset($row['TYPE']) && (int)$row['TYPE'] == CSaleBasket::TYPE_SET) ||
				(!isset($row['SET_PARENT_ID']) || (int)$row['SET_PARENT_ID'] <= 0)
			) &&
			(!isset($row['ITEM_FIX']) || $row['ITEM_FIX'] != 'Y') &&
			(!isset($row['LAST_DISCOUNT']) || $row['LAST_DISCOUNT'] != 'Y') &&
			(!isset($row['IN_SET']) || $row['IN_SET'] != 'Y')
		);
	}

	/**
	 * Apply discount to delivery price.
	 *
	 * @param array &$order				Order data.
	 * @param float $value				Discount value.
	 * @param string $unit				Value unit.
	 * @param bool $extMode				Apply mode percent discount.
	 * @return void
	 */
	public static function ApplyDelivery(&$order, $value, $unit, $extMode = false)
	{
		$unit = (string)$unit;
		if ($unit != self::VALUE_TYPE_PERCENT && $unit != self::VALUE_TYPE_FIX)
			return;
		if (isset($order['CUSTOM_PRICE_DELIVERY']) && $order['CUSTOM_PRICE_DELIVERY'] == 'Y')
			return;
		if (isset($order['PRICE_DELIVERY']))
		{
			$extMode = ($extMode === true);
			$type = ($extMode ? Sale\OrderDiscountManager::DESCR_TYPE_MAX_BOUND : Sale\OrderDiscountManager::DESCR_TYPE_VALUE);
			$value = (float)$value;
			$discountDescr = array(
				'VALUE' => abs($value),
				'VALUE_ACTION' => (
					$value < 0
					? Sale\OrderDiscountManager::DESCR_VALUE_ACTION_DISCOUNT
					: Sale\OrderDiscountManager::DESCR_VALUE_ACTION_EXTRA
				)
			);

			if ($unit == self::VALUE_TYPE_PERCENT)
			{
				$value = roundEx(($order['PRICE_DELIVERY']*$value)/100, SALE_VALUE_PRECISION);
				$type = Sale\OrderDiscountManager::DESCR_TYPE_VALUE;
				$discountDescr['VALUE_TYPE'] = Sale\OrderDiscountManager::DESCR_VALUE_TYPE_PERCENT;
			}
			else
			{
				$discountDescr['VALUE_TYPE'] = Sale\OrderDiscountManager::DESCR_VALUE_TYPE_CURRENCY;
				if (isset($order['CURRENCY']))
					$discountDescr['VALUE_UNIT'] = $order['CURRENCY'];
			}
			if ($value == 0)
				return;

			$resultValue = $order['PRICE_DELIVERY'] + $value;
			if ($extMode && $resultValue < 0)
			{
				$resultValue = 0;
				$value = $order['PRICE_DELIVERY'];
			}
			if (abs($resultValue) < self::EPS)
				$resultValue = 0;
			if ($resultValue >= 0)
			{
				if (!isset($order['PRICE_DELIVERY_DIFF']))
					$order['PRICE_DELIVERY_DIFF'] = 0;
				$order['PRICE_DELIVERY_DIFF'] -= $value;
				$order['PRICE_DELIVERY'] = $resultValue;

				if (!self::isManualMode($order))
				{
					$prepareResult = Sale\OrderDiscountManager::prepareDiscountDescription($type, $discountDescr);
					if ($prepareResult->isSuccess())
					{
						if (!isset($order['DISCOUNT_DESCR']))
							$order['DISCOUNT_DESCR'] = array();
						if (!isset($order['DISCOUNT_DESCR']['DELIVERY']))
							$order['DISCOUNT_DESCR']['DELIVERY'] = array();
						$order['DISCOUNT_DESCR']['DELIVERY'][] = $prepareResult->getData();
					}
					unset($prepareResult);
				}
				$discountDescr['RESULT_VALUE'] = abs($value);
				if (isset($order['CURRENCY']))
					$discountDescr['RESULT_UNIT'] = $order['CURRENCY'];

				$prepareResult = Sale\OrderDiscountManager::prepareDiscountDescription($type, $discountDescr);
				if ($prepareResult->isSuccess(true))
				{
					if (!isset($order['DISCOUNT_RESULT']))
						$order['DISCOUNT_RESULT'] = array();
					if (!isset($order['DISCOUNT_RESULT']['DELIVERY']))
						$order['DISCOUNT_RESULT']['DELIVERY'] = array();
					$order['DISCOUNT_RESULT']['DELIVERY'][] = $prepareResult->getData();
				}
				unset($prepareResult);
			}
			unset($discountDescr);
		}
	}

	/**
	 * Apply discount to basket.
	 *
	 * @param array &$order			Order data.
	 * @param callable $func		Filter function.
	 * @param float $value			Discount value.
	 * @param string $unit			Value unit.
	 * @return void
	 */
	public static function ApplyBasketDiscount(&$order, $func, $value, $unit)
	{
		if (empty($order['BASKET_ITEMS']) || !is_array($order['BASKET_ITEMS']))
			return;

		$manualMode = self::isManualMode($order);
		if (self::$getPercentFromBasePrice === null)
		{
			if ($manualMode)
				self::$getPercentFromBasePrice = (isset($order['USE_BASE_PRICE']) && $order['USE_BASE_PRICE'] == 'Y');
			else
				self::$getPercentFromBasePrice = (string)Option::get('sale', 'get_discount_percent_from_base_price') == 'Y';
		}

		if ($manualMode)
			$discountBasket = array_filter($order['BASKET_ITEMS'], 'CSaleDiscountActionApply::filterApplied');
		else
			$discountBasket = (is_callable($func) ? array_filter($order['BASKET_ITEMS'], $func) : $order['BASKET_ITEMS']);
		if (empty($discountBasket))
			return;

		$allBasket = (count($order['BASKET_ITEMS']) == count($discountBasket));

		$clearBasket = array_filter($discountBasket, 'CSaleDiscountActionApply::ClearBasket');
		if (empty($clearBasket))
			return;
		unset($discountBasket);

		$unit = (string)$unit;
		$value = (float)$value;
		$type = Sale\OrderDiscountManager::DESCR_TYPE_VALUE;
		$discountDescr = array(
			'VALUE' => abs($value),
			'VALUE_ACTION' => (
				$value < 0
				? Sale\OrderDiscountManager::DESCR_VALUE_ACTION_DISCOUNT
				: Sale\OrderDiscountManager::DESCR_VALUE_ACTION_EXTRA
			),
		);
		switch ($unit)
		{
			case self::VALUE_TYPE_SUMM:
				$discountDescr['VALUE_TYPE'] = (
					$allBasket
					? Sale\OrderDiscountManager::DESCR_VALUE_TYPE_SUMM_BASKET
					: Sale\OrderDiscountManager::DESCR_VALUE_TYPE_SUMM
				);
				if (isset($order['CURRENCY']))
					$discountDescr['VALUE_UNIT'] = $order['CURRENCY'];
				break;
			case self::VALUE_TYPE_PERCENT:
				$discountDescr['VALUE_TYPE'] = Sale\OrderDiscountManager::DESCR_VALUE_TYPE_PERCENT;
				break;
			case self::VALUE_TYPE_FIX:
			default:
				$discountDescr['VALUE_TYPE'] = Sale\OrderDiscountManager::DESCR_VALUE_TYPE_CURRENCY;
				if (isset($order['CURRENCY']))
					$discountDescr['VALUE_UNIT'] = $order['CURRENCY'];
				break;
		}
		unset($allBasket);
		if ($unit == self::VALUE_TYPE_SUMM)
		{
			$dblSumm = 0.0;
			if (self::$getPercentFromBasePrice)
			{
				foreach ($clearBasket as &$arOneRow)
				{
					if (!isset($arOneRow['DISCOUNT_PRICE']))
						$arOneRow['DISCOUNT_PRICE'] = 0;
					$dblSumm += (float)(isset($arOneRow['BASE_PRICE'])
							? $arOneRow['BASE_PRICE']
							: $arOneRow['PRICE'] + $arOneRow['DISCOUNT_PRICE']
						) * (float)$arOneRow['QUANTITY'];
				}
				unset($arOneRow);
			}
			else
			{
				foreach ($clearBasket as &$arOneRow)
					$dblSumm += (float)$arOneRow['PRICE'] * (float)$arOneRow['QUANTITY'];
				unset($arOneRow);
			}

			$value = ($dblSumm > 0 ? ($value*100)/$dblSumm : 0.0);
			$unit = self::VALUE_TYPE_PERCENT;
		}
		if ($value != 0)
		{
			if (!$manualMode)
			{
				$prepareResult = Sale\OrderDiscountManager::prepareDiscountDescription($type, $discountDescr);
				if ($prepareResult->isSuccess())
				{
					if (!isset($order['DISCOUNT_DESCR']))
						$order['DISCOUNT_DESCR'] = array();
					if (!isset($order['DISCOUNT_DESCR']['BASKET']))
						$order['DISCOUNT_DESCR']['BASKET'] = array();
					$order['DISCOUNT_DESCR']['BASKET'][] = $prepareResult->getData();
				}
				unset($prepareResult);
			}
			$applyResultList = array();
			foreach ($clearBasket as $basketCode => $arOneRow)
			{
				$calculateValue = $value;
				if ($unit == self::VALUE_TYPE_PERCENT)
				{
					if (self::$getPercentFromBasePrice)
					{
						if (!isset($arOneRow['DISCOUNT_PRICE']))
							$arOneRow['DISCOUNT_PRICE'] = 0;
						$calculateValue = ((isset($arOneRow['BASE_PRICE'])
							? $arOneRow['BASE_PRICE']
							: $arOneRow['PRICE'] + $arOneRow['DISCOUNT_PRICE']
						)*$value)/100;
					}
					else
					{
						$calculateValue = ($arOneRow['PRICE']*$value)/100;
					}
					$calculateValue = roundEx($calculateValue, SALE_VALUE_PRECISION);
				}

				$dblResult = $arOneRow['PRICE'] + $calculateValue;
				if (abs($dblResult) < self::EPS)
					$dblResult = 0;
				if ($dblResult >= 0 && (!$manualMode || isset($arOneRow[self::BASKET_APPLIED_FIELD])))
				{
					$arOneRow['PRICE'] = $dblResult;
					if (isset($arOneRow['PRICE_DEFAULT']))
						$arOneRow['PRICE_DEFAULT'] = $dblResult;
					if (isset($arOneRow['DISCOUNT_PRICE']))
					{
						$arOneRow['DISCOUNT_PRICE'] = (float)$arOneRow['DISCOUNT_PRICE'];
						$arOneRow['DISCOUNT_PRICE'] -= $calculateValue;
					}
					else
					{
						$arOneRow['DISCOUNT_PRICE'] = -$calculateValue;
					}
					if ($arOneRow['DISCOUNT_PRICE'] < 0)
						$arOneRow['DISCOUNT_PRICE'] = 0;
					if (isset($arOneRow['VAT_RATE']))
					{
						$dblVatRate = (float)$arOneRow['VAT_RATE'];
						if ($dblVatRate > 0)
							$arOneRow['VAT_VALUE'] = (($arOneRow['PRICE'] / ($dblVatRate + 1)) * $dblVatRate);
					}

					foreach (self::$resetFields as &$fieldName)
					{
						if (isset($arOneRow[$fieldName]) && !is_array($arOneRow[$fieldName]))
							$arOneRow['~'.$fieldName] = $arOneRow[$fieldName];
					}
					unset($fieldName);

					$order['BASKET_ITEMS'][$basketCode] = $arOneRow;

					$applyResultList[$basketCode] = $discountDescr;
					$applyResultList[$basketCode]['RESULT_VALUE'] = abs($calculateValue);
					if (isset($arOneRow['CURRENCY']))
						$applyResultList[$basketCode]['RESULT_UNIT'] = $arOneRow['CURRENCY'];
				}
			}
			unset($basketCode);
			if (!isset($order['DISCOUNT_RESULT']))
				$order['DISCOUNT_RESULT'] = array();
			if (!isset($order['DISCOUNT_RESULT']['BASKET']))
				$order['DISCOUNT_RESULT']['BASKET'] = array();
			foreach ($applyResultList as $basketCode => $applyResult)
			{
				$prepareResult = Sale\OrderDiscountManager::prepareDiscountDescription($type, $applyResult);
				if ($prepareResult->isSuccess())
				{
					if (!isset($order['DISCOUNT_RESULT']['BASKET'][$basketCode]))
						$order['DISCOUNT_RESULT']['BASKET'][$basketCode] = array();
					$order['DISCOUNT_RESULT']['BASKET'][$basketCode][] = $prepareResult->getData();
				}
				unset($prepareResult);
			}
			unset($basketCode, $applyResult, $applyResultList);
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
				Loc::getMessage('BT_SALE_ACT_GROUP_GLOBAL_PREFIX')
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
					'message' => Loc::getMessage('BT_SALE_ACT_GROUP_LOGIC_AND')
				),
				array(
					'style' => 'condition-logic-or',
					'message' => Loc::getMessage('BT_SALE_ACT_GROUP_LOGIC_OR')
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
			'label' => Loc::getMessage('BT_SALE_ACT_DELIVERY_LABEL'),
			'defaultText' => Loc::getMessage('BT_SALE_ACT_DELIVERY_DEF_TEXT'),
			'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
			'control' => array(
				Loc::getMessage('BT_SALE_ACT_DELIVERY_GROUP_PRODUCT_PREFIX'),
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
						'Discount' => Loc::getMessage('BT_SALE_ACT_DELIVERY_SELECT_TYPE_DISCOUNT'),
						'DiscountZero' => Loc::getMessage('BT_SALE_ACT_DELIVERY_SELECT_TYPE_DISCOUNT_ZERO'),
						'Extra' => Loc::getMessage('BT_SALE_ACT_DELIVERY_SELECT_TYPE_EXTRA'),
					),
					'defaultText' => Loc::getMessage('BT_SALE_ACT_DELIVERY_SELECT_TYPE_DEF'),
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
						'Perc' => Loc::getMessage('BT_SALE_ACT_DELIVERY_SELECT_PERCENT'),
						'Cur' => Loc::getMessage('BT_SALE_ACT_DELIVERY_SELECT_CUR')
					),
					'defaultText' => Loc::getMessage('BT_SALE_ACT_DELIVERY_SELECT_UNIT_DEF'),
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
			$strUnit = ('Cur' == $arOneCondition['Unit'] ? CSaleDiscountActionApply::VALUE_TYPE_FIX : CSaleDiscountActionApply::VALUE_TYPE_PERCENT);
			$extMode = ('DiscountZero' == $arOneCondition['Type'] && $arOneCondition['Unit'] == 'Cur' ? 'true' : 'false');
			$mxResult = 'CSaleDiscountActionApply::ApplyDelivery('.$arParams['ORDER'].', '.$dblVal.', "'.$strUnit.'", '.$extMode.');';
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
			'label' => Loc::getMessage('BT_SALE_ACT_GROUP_BASKET_LABEL'),
			'defaultText' => Loc::getMessage('BT_SALE_ACT_GROUP_BASKET_DEF_TEXT'),
			'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
			'visual' => static::GetVisual(),
			'control' => array(
				Loc::getMessage('BT_SALE_ACT_GROUP_BASKET_PREFIX'),
				$arAtoms['Type'],
				$arAtoms['Value'],
				$arAtoms['Unit'],
				Loc::getMessage('BT_SALE_ACT_GROUP_BASKET_DESCR'),
				$arAtoms['All']
			),
			'mess' => array(
				'ADD_CONTROL' => Loc::getMessage('BT_SALE_SUBACT_ADD_CONTROL'),
				'SELECT_CONTROL' => Loc::getMessage('BT_SALE_SUBACT_SELECT_CONTROL')
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
						'Discount' => Loc::getMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_TYPE_DISCOUNT'),
						'Extra' => Loc::getMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_TYPE_EXTRA')
					),
					'defaultText' => Loc::getMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_TYPE_DEF'),
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
						'Perc' => Loc::getMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_PERCENT'),
						'CurEach' => Loc::getMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_CUR_EACH'),
						'CurAll' => Loc::getMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_CUR_ALL')
					),
					'defaultText' => Loc::getMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_UNIT_DEF'),
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
						'AND' => Loc::getMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_ALL'),
						'OR' => Loc::getMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_ANY')
					),
					'defaultText' => Loc::getMessage('BT_SALE_ACT_GROUP_BASKET_SELECT_DEF'),
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
			$strUnit = CSaleDiscountActionApply::VALUE_TYPE_PERCENT;
			if ('CurEach' == $arOneCondition['Unit'])
			{
				$strUnit = CSaleDiscountActionApply::VALUE_TYPE_FIX;
			}
			elseif ('CurAll' == $arOneCondition['Unit'])
			{
				$strUnit = CSaleDiscountActionApply::VALUE_TYPE_SUMM;
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
			'label' => Loc::getMessage('BT_MOD_SALE_ACT_GROUP_BASKET_FIELDS_LABEL'),
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
			if ($arOneControl['ID'] == 'CondBsktFldPrice' || $arOneControl['ID'] == 'CondBsktFldSumm')
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
				if (!$boolCurrency)
					$arOne = array();
			}
			elseif ('CondBsktFldWeight' == $arOneControl['ID'])
			{
				$arOne['control'][] = Loc::getMessage('BT_MOD_SALE_ACT_MESS_WEIGHT_UNIT');
			}
			if (!empty($arOne))
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
				'LABEL' => Loc::getMessage('BT_MOD_SALE_ACT_BASKET_PRODUCT_ID_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_SALE_ACT_BASKET_PRODUCT_ID_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ)),
				'JS_VALUE' => array(
					'type' => 'dialog',
					'popup_url' =>  '/bitrix/admin/cat_product_search_dialog.php',
					'popup_params' => array(
						'lang' => LANGUAGE_ID,
						'caller' => 'discount_rules'
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
				'LABEL' => Loc::getMessage('BT_MOD_SALE_ACT_BASKET_PRODUCT_NAME_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_SALE_ACT_BASKET_PRODUCT_NAME_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT)),
				'JS_VALUE' => array(
					'type' => 'input'
				),
				'PHP_VALUE' => ''
			),
			'CondBsktFldSumm' => array(
				'ID' => 'CondBsktFldSumm',
				'FIELD' => array(
					'PRICE',
					'QUANTITY'
				),
				'FIELD_TYPE' => 'double',
				'LABEL' => Loc::getMessage('BT_MOD_SALE_ACT_BASKET_ROW_SUMM_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_SALE_ACT_BASKET_ROW_SUMM_EXT_PREFIX'),
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
			'CondBsktFldPrice' => array(
				'ID' => 'CondBsktFldPrice',
				'FIELD' => 'PRICE',
				'FIELD_TYPE' => 'double',
				'LABEL' => Loc::getMessage('BT_MOD_SALE_ACT_BASKET_ROW_PRICE_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_SALE_ACT_BASKET_ROW_PRICE_EXT_PREFIX'),
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
				'LABEL' => Loc::getMessage('BT_MOD_SALE_ACT_BASKET_ROW_QUANTITY_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_SALE_ACT_BASKET_ROW_QUANTITY_EXT_PREFIX'),
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
				'LABEL' => Loc::getMessage('BT_MOD_SALE_ACT_BASKET_ROW_WEIGHT_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_SALE_ACT_BASKET_ROW_WEIGHT_EXT_PREFIX'),
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
		foreach ($arControlList as &$control)
		{
			$control['MODULE_ID'] = 'sale';
			$control['MODULE_ENTITY'] = 'sale';
			$control['ENTITY'] = 'BASKET';
			$control['MULTIPLE'] = 'N';
			$control['GROUP'] = 'N';
		}
		unset($control);

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

		if (is_string($arControl))
		{
			$arControl = static::GetControls($arControl);
		}
		$boolError = !is_array($arControl);

		if (!$boolError)
		{
			$arValues = static::Check($arOneCondition, $arOneCondition, $arControl, false);
			$boolError = (false === $arValues);
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
				$multyField = is_array($arControl['FIELD']);
				$issetField = '';
				$valueField = '';
				if ($multyField)
				{
					$fieldsList = array();
					foreach ($arControl['FIELD'] as &$oneField)
					{
						$fieldsList[] = $arParams['BASKET_ROW'].'[\''.$oneField.'\']';
					}
					unset($oneField);
					$issetField = implode(') && isset (', $fieldsList);
					$valueField = implode('*',$fieldsList);
					unset($fieldsList);
				}
				else
				{
					$issetField = $arParams['BASKET_ROW'].'[\''.$arControl['FIELD'].'\']';
					$valueField = $issetField;
				}
				switch ($arControl['FIELD_TYPE'])
				{
					case 'int':
					case 'double':
						$strResult = str_replace(array('#FIELD#', '#VALUE#'), array($valueField, $arValues['value']), $arLogic['OP'][$arControl['MULTIPLE']]);
						break;
					case 'char':
					case 'string':
					case 'text':
						$strResult = str_replace(array('#FIELD#', '#VALUE#'), array($valueField, '"'.EscapePHPString($arValues['value']).'"'), $arLogic['OP'][$arControl['MULTIPLE']]);
						break;
					case 'date':
					case 'datetime':
						$strResult = str_replace(array('#FIELD#', '#VALUE#'), array($valueField, $arValues['value']), $arLogic['OP'][$arControl['MULTIPLE']]);
						break;
				}
				$strResult = 'isset('.$issetField.') && '.$strResult;
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
	protected $executeModule = array();

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
		$this->usedModules = array();
		$this->usedExtFiles = array();
		$this->usedEntity = array();
		$this->executeModule = array();
		if (!$this->boolError)
		{
			$strResult = '';
			if (!empty($arConditions) && is_array($arConditions))
			{
				$arParams['FUNC_ID'] = '';
				$arResult = $this->GenerateLevel($arConditions, $arParams, true);
				if (empty($arResult))
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
		$boolFirst = ($boolFirst === true);
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
			if (!empty($arLevel['CLASS_ID']))
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
							$strEval = (isset($mxEval['COND']) ? $mxEval['COND'] : false);
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
					$this->fillUsedData($arOneControl);
				}
			}
			$intRowNum++;
		}
		else
		{
			foreach ($arLevel as &$arOneCondition)
			{
				$arParams['ROW_NUM'] = $intRowNum;
				if (!empty($arOneCondition['CLASS_ID']))
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
								$strEval = (isset($mxEval['COND']) ? $mxEval['COND'] : false);
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
						$this->fillUsedData($arOneControl);
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

	public function GetExecuteModule()
	{
		return (!empty($this->executeModule) ? array_keys($this->executeModule) : array());
	}

	protected function fillUsedData(&$control)
	{
		parent::fillUsedData($control);
		if (!empty($control['EXECUTE_MODULE']))
			$this->executeModule[$control['EXECUTE_MODULE']] = true;
	}
}
?>