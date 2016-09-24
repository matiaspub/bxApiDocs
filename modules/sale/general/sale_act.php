<?
use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\Sale;

if (!Loader::includeModule('catalog'))
	return;

Loc::loadMessages(__FILE__);

/**
 * @deprecated deprecated since sale 16.0.10
 * @see \Bitrix\Sale\Discount\Actions
 */
class CSaleDiscountActionApply
{
	const VALUE_TYPE_FIX = Sale\Discount\Actions::VALUE_TYPE_FIX;
	const VALUE_TYPE_PERCENT = Sale\Discount\Actions::VALUE_TYPE_PERCENT;
	const VALUE_TYPE_SUMM = Sale\Discount\Actions::VALUE_TYPE_SUMM;

	const GIFT_SELECT_TYPE_ONE = Sale\Discount\Actions::GIFT_SELECT_TYPE_ONE;
	const GIFT_SELECT_TYPE_ALL = Sale\Discount\Actions::GIFT_SELECT_TYPE_ALL;

	const ORDER_MANUAL_MODE_FIELD = 'ORDER_MANUAL_MODE';
	const BASKET_APPLIED_FIELD = Sale\Discount\Actions::BASKET_APPLIED_FIELD;

	const EPS = Sale\Discount\Actions::VALUE_EPS;

	protected static $getPercentFromBasePrice = null;

	/**
	 * Check discount calculate mode field for order.
	 *
	 * @deprecated deprecated since sale 16.0.10
	 * @see \Bitrix\Sale\Discount\Actions::isManualMode
	 *
	 * @param array $order			Order data.
	 * @return bool
	 */
	public static function isManualMode(/** @noinspection PhpUnusedParameterInspection */$order)
	{
		return Sale\Discount\Actions::isManualMode();
	}

	/**
	 * Set discount calculate mode field for order.
	 *
	 * @deprecated deprecated since sale 16.0.10
	 * @see \Bitrix\Sale\Discount\Actions::setUseMode
	 *
	 * @param array &$order			Order data.
	 * @return void
	 */
	public static function setManualMode(&$order)
	{
		if (empty($order) || empty($order['ID']))
			return;
		Sale\Discount\Actions::setUseMode(
			Sale\Discount\Actions::MODE_MANUAL,
			array(
				'USE_BASE_PRICE' => $order['USE_BASE_PRICE'],
				'SITE_ID' => $order['SITE_ID'],
				'CURRENCY' => $order['CURRENCY']
			)
		);
	}

	/**
	 * Erase discount calculate mode field for order.
	 *
	 * @deprecated deprecated since sale 16.0.10
	 * @see \Bitrix\Sale\Discount\Actions::setUseMode
	 *
	 * @param array &$order			Order data.
	 * @return void
	 */
	public static function clearManualMode(&$order)
	{
		if (empty($order) || !is_array($order))
			return;
		Sale\Discount\Actions::setUseMode(Sale\Discount\Actions::MODE_CALCULATE);
	}

	/**
	 * Return true, if discount already applied by basket item.
	 *
	 * @deprecated deprecated since sale 16.0.10
	 *
	 * @param array $row			Basket row.
	 * @return bool
	 */
	public static function filterApplied($row)
	{
		/* @noinspection PhpDeprecationInspection */
		return (isset($row[self::BASKET_APPLIED_FIELD]));
	}

	/**
	 * Fill basket applied information.
	 *
	 * @deprecated deprecated since sale 16.0.10
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
					/* @noinspection PhpDeprecationInspection */
					$basketRow[self::BASKET_APPLIED_FIELD] = $value;
					break;
				}
			}
			unset($basketRow);
		}
		unset($value, $itemId);
		if ($founded)
			/* @noinspection PhpDeprecationInspection */
			self::setManualMode($order);
	}

	/**
	 * Clear basket applied information.
	 *
	 * @deprecated deprecated since sale 16.0.10
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
			/* @noinspection PhpDeprecationInspection */
			if (array_key_exists(self::BASKET_APPLIED_FIELD, $basketRow))
				/* @noinspection PhpDeprecationInspection */
				unset($basketRow[self::BASKET_APPLIED_FIELD]);
		}
		unset($basketRow);
	}

	/**
	 * Filter for undiscount basket items.
	 *
	 * @deprecated deprecated since sale 16.0.10
	 * @see \Bitrix\Sale\Discount\Actions::filterBasketForAction
	 *
	 * @param array $row		Basket item.
	 * @return bool
	 */
	public static function ClearBasket($row)
	{
		return Sale\Discount\Actions::filterBasketForAction($row);
	}

	/**
	 * Apply discount to delivery price.
	 *
	 * @deprecated deprecated since sale 16.0.10
	 * @see \Bitrix\Sale\Discount\Actions::applyToDelivery
	 *
	 * @param array &$order				Order data.
	 * @param float $value				Discount value.
	 * @param string $unit				Value unit.
	 * @param bool $extMode				Apply mode percent discount.
	 * @return void
	 */
	public static function ApplyDelivery(&$order, $value, $unit, $extMode = false)
	{
		$extMode = ($extMode === true);
		$params = array(
			'VALUE' => $value,
			'UNIT' => $unit,
		);
		if ($extMode)
			$params['MAX_BOUND'] = 'Y';
		Sale\Discount\Actions::applyToDelivery(
			$order,
			$params
		);
		unset($params);
	}

	/**
	 * Apply discount to basket.
	 *
	 * @deprecated deprecated since sale 16.0.10
	 * @see \Bitrix\Sale\Discount\Actions::applyToBasket
	 *
	 * @param array &$order			Order data.
	 * @param callable $func		Filter function.
	 * @param float $value			Discount value.
	 * @param string $unit			Value unit.
	 * @return void
	 */
	public static function ApplyBasketDiscount(&$order, $func, $value, $unit)
	{
		Sale\Discount\Actions::applyToBasket(
			$order,
			array(
				'VALUE' => $value,
				'UNIT' => $unit
			),
			$func
		);
	}

	/**
	 * Apply simple gift discount.
	 *
	 * @deprecated deprecated since sale 16.0.10
	 * @see \Bitrix\Sale\Discount\Actions::applySimpleGift
	 *
	 * @param array &$order				Order data.
	 * @param callable $callableFilter	Filter function.
	 * @return void
	 */
	public static function ApplyGiftDiscount(&$order, $callableFilter)
	{
		Sale\Discount\Actions::applySimpleGift($order, $callableFilter);
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

class CSaleActionGiftCtrlGroup extends CSaleActionCtrlGroup
{
	public static function GetClassName()
	{
		return __CLASS__;
	}

	public static function GetShowIn($arControls)
	{
		$arControls = array(
			'CondGroup'
		);
		return $arControls;
	}

	public static function GetControlID()
	{
		return 'GiftCondGroup';
	}

	public static function GetAtoms()
	{
		return static::GetAtomsEx(false, false);
	}

	public static function GetAtomsEx($strControlID = false, $boolEx = false)
	{
		$boolEx = (true === $boolEx ? true : false);
		$arAtomList = array();

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

	public static function GetControlDescr()
	{
		$controlDescr = parent::GetControlDescr();
		$controlDescr['FORCED_SHOW_LIST'] = array(
			'GifterCondIBElement',
			'GifterCondIBSection',
		);

		return $controlDescr;
	}

	public static function GetControlShow($arParams)
	{
		return array(
			'controlId' => static::GetControlID(),
			'group' => true,
			'containsOneAction' => true,
			'label' => Loc::getMessage('BT_SALE_ACT_GIFT_LABEL'),
			'defaultText' => '',
			'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
			'control' => array(
				Loc::getMessage('BT_SALE_ACT_GIFT_GROUP_PRODUCT_PREFIX'),
			)
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
		//I have to notice current method can work only with Gifter's. For example, it is CCatalogGifterProduct.
		//Probably in future we'll add another gifter's and create interface or class, which will tell about attitude to CSaleActionGiftCtrlGroup.
		$mxResult = '';
		$boolError = false;

		if (!isset($arSubs) || !is_array($arSubs) || empty($arSubs))
		{
			$boolError = true;
		}
		else
		{
			$mxResult = '\Bitrix\Sale\Discount\Actions::applySimpleGift(' . $arParams['ORDER'] . ', ' . implode('; ',$arSubs) . ');';
		}
		return $mxResult;
	}

	public static function ProvideGiftProductData(array $fields)
	{
		if(isset($fields['ACTIONS']) && is_array($fields['ACTIONS']))
		{
			$fields['ACTIONS_LIST'] = $fields['ACTIONS'];
		}

		if (
				(empty($fields['ACTIONS_LIST']) || !is_array($fields['ACTIONS_LIST']))
				&& CheckSerializedData($fields['ACTIONS']))
		{
			$actions = unserialize($fields['ACTIONS']);
		}
		else
		{
			$actions = $fields['ACTIONS_LIST'];
		}

		if (!is_array($actions) || empty($actions) || empty($actions['CHILDREN']))
		{
			return array();
		}

		$giftCondGroups = array();
		foreach($actions['CHILDREN'] as $child)
		{
			if(isset($child['CLASS_ID']) && isset($child['DATA']) && $child['CLASS_ID'] === static::GetControlID())
			{
				//we know that in GiftCondGroup may be only once child. See 'containsOneAction' option in method GetControlShow().
				$giftCondGroups[] = reset($child['CHILDREN']);
			}
		}
		unset($child);

		$giftsData = array();
		foreach($giftCondGroups as $child)
		{
			//todo so hard, but we can't made abstraction every time.
			if(isset($child['CLASS_ID']) && isset($child['DATA']))
			{
				$gifter = static::getGifter($child);
				if(!$gifter)
				{
					continue;
				}
				$giftsData[] = $gifter->ProvideGiftData($child);
			}
		}
		unset($child);

		return $giftsData;
	}

	protected static function getGifter(array $data)
	{
		if(in_array($data['CLASS_ID'], array('GifterCondIBElement', 'GifterCondIBSection')))
		{
			return new CCatalogGifterProduct;
		}
		return null;
	}

	/**
	 * Extends list of products by base product, if we have SKU in list.
	 *
	 * @param array $productIds
	 * @return array
	 */
	public static function ExtendProductIds(array $productIds)
	{
		return CCatalogGifterProduct::ExtendProductIds($productIds);
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
			$arOneCondition['Value'] = (float)$arOneCondition['Value'];
			$actionParams = array(
				'VALUE' => ($arOneCondition['Type'] == 'Extra' ? $arOneCondition['Value'] : -$arOneCondition['Value']),
				'UNIT' => ($arOneCondition['Unit'] == 'Cur' ? Sale\Discount\Actions::VALUE_TYPE_FIX : Sale\Discount\Actions::VALUE_TYPE_PERCENT)
			);
			if ($arOneCondition['Type'] == 'DiscountZero' && $arOneCondition['Unit'] == 'Cur')
				$actionParams['MAX_BOUND'] = 'Y';

			$mxResult = '\Bitrix\Sale\Discount\Actions::applyToDelivery('.$arParams['ORDER'].', '.var_export($actionParams, true).')';
			unset($actionParams);
		}

		return $mxResult;
	}
}

class CSaleActionGift extends CSaleActionCtrl
{
	public static function GetControlDescr()
	{
		$controlDescr = parent::GetControlDescr();

		$controlDescr['PARENT'] = true;
		$controlDescr['EXIST_HANDLER'] = 'Y';
		$controlDescr['MODULE_ID'] = 'catalog';
		$controlDescr['MODULE_ENTITY'] = 'iblock';
		$controlDescr['ENTITY'] = 'ELEMENT';
		$controlDescr['FIELD'] = 'ID';

		return $controlDescr;
	}

	public static function GetClassName()
	{
		return __CLASS__;
	}

	public static function GetControlID()
	{
		return 'ActSaleGift';
	}

	public static function GetControlShow($arParams)
	{
		$arAtoms = static::GetAtomsEx(false, false);
		if (static::$boolInit)
		{
			//here initialize
		}
		$arResult = array(
			'controlId' => static::GetControlID(),
			'group' => false,
			'label' => Loc::getMessage('BT_SALE_ACT_GIFT_LABEL'),
			'defaultText' => Loc::getMessage('BT_SALE_ACT_GIFT_DEF_TEXT'),
			'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
			'control' => array(
				Loc::getMessage('BT_SALE_ACT_GIFT_GROUP_PRODUCT_PREFIX'),
				$arAtoms['Type'],
				$arAtoms['GiftValue'],
			),
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
						Sale\Discount\Actions::GIFT_SELECT_TYPE_ONE => Loc::getMessage('BT_SALE_ACT_GIFT_SELECT_TYPE_SELECT_ONE'),
						Sale\Discount\Actions::GIFT_SELECT_TYPE_ALL => Loc::getMessage('BT_SALE_ACT_GIFT_SELECT_TYPE_SELECT_ALL'),
					),
					'defaultText' => Loc::getMessage('BT_SALE_ACT_GIFT_SELECT_TYPE_SELECT_DEF'),
					'defaultValue' => 'one',
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
			'GiftValue' => array(
				'JS' => array(
					'id' => 'GiftValue',
					'name' => 'gifts_value',
					'type' => 'multiDialog',
					'popup_url' =>  '/bitrix/admin/cat_product_search_dialog.php',
					'popup_params' => array(
						'lang' => LANGUAGE_ID,
						'caller' => 'discount'
					),
					'param_id' => 'n',
					'show_value' => 'Y'
				),
				'ATOM' => array(
					'ID' => 'GiftValue',

					'PARENT' => true,
					'EXIST_HANDLER' => 'Y',
					'MODULE_ID' => 'catalog',
					'MODULE_ENTITY' => 'iblock',
					'ENTITY' => 'ELEMENT',
					'FIELD' => 'ID',

					'FIELD_TYPE' => 'int',
					'VALIDATE' => 'element',
					'PHP_VALUE' => array(
						'VALIDATE' => 'element'
					)
				)
			),
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
		if (is_string($arControl) && $arControl == static::GetControlID())
		{
			$arControl = array(
				'ID' => static::GetControlID(),
				'ATOMS' => static::GetAtoms()
			);
		}
		$boolError = !is_array($arControl);

		if (!$boolError)
		{
			$arControl['ATOMS'] = static::GetAtomsEx($arControl['ID'], true);
			$arValues = static::CheckAtoms($arOneCondition, $arOneCondition, $arControl, true);
			$boolError = ($arValues === false);
		}

		if (!$boolError)
		{
			$stringArray = 'array(' . implode(',', array_map('intval', $arOneCondition['GiftValue'])) . ')';
			$type = $arOneCondition['Type'];

			$mxResult = "CSaleDiscountActionApply::ApplyGiftDiscount({$arParams['ORDER']}, $stringArray, '{$type}');";
		}

		return $mxResult;
	}

	public static function getGiftDataByDiscount($fields)
	{
		if (
				(empty($fields['ACTIONS_LIST']) || !is_array($fields['ACTIONS_LIST']))
				&& CheckSerializedData($fields['ACTIONS']))
		{
			$actions = unserialize($fields['ACTIONS']);
		}
		else
		{
			$actions = $fields['ACTIONS_LIST'];
		}

		if (!is_array($actions) || empty($actions) || empty($actions['CHILDREN']))
		{
			return null;
		}

		$result = null;
		foreach($actions['CHILDREN'] as $child)
		{
			if(isset($child['CLASS_ID']) && isset($child['DATA']) && $child['CLASS_ID'] === CSaleActionGift::GetControlID())
			{
				$result[] = $child['DATA'];
			}
		}
		unset($child);

		return $result;
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
			switch ($arOneCondition['Unit'])
			{
				case 'CurEach':
					$unit = Sale\Discount\Actions::VALUE_TYPE_FIX;
					break;
				case 'CurAll':
					$unit = Sale\Discount\Actions::VALUE_TYPE_SUMM;
					break;
				default:
					$unit = Sale\Discount\Actions::VALUE_TYPE_PERCENT;
					break;
			}
			$discountParams = array(
				'VALUE' => ($arOneCondition['Type'] == 'Extra' ? $arOneCondition['Value'] : -$arOneCondition['Value']),
				'UNIT' => $unit
			);

			if (!empty($arSubs))
			{
				$filter = '$saleact'.$arParams['FUNC_ID'];

				$mxResult = $filter.'=function($row){';
				$mxResult .= 'return ('.implode(') '.($arOneCondition['All'] == 'AND' ? '&&' : '||').' (', $arSubs).');';
				$mxResult .= '};';
				$mxResult .= '\Bitrix\Sale\Discount\Actions::applyToBasket('.$arParams['ORDER'].', '.var_export($discountParams, true).', '.$filter.');';
				unset($filter);
			}
			else
			{
				$mxResult = '\Bitrix\Sale\Discount\Actions::applyToBasket('.$arParams['ORDER'].', '.var_export($discountParams, true).', "");';
			}
			unset($discountParams, $unit);
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