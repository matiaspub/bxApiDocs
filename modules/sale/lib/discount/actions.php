<?php
namespace Bitrix\Sale\Discount;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc,
	Bitrix\Sale;

Loc::loadMessages(__FILE__);

class Actions
{
	const VALUE_TYPE_FIX = 'F';
	const VALUE_TYPE_PERCENT = 'P';
	const VALUE_TYPE_SUMM = 'S';

	const GIFT_SELECT_TYPE_ONE = 'one';
	const GIFT_SELECT_TYPE_ALL = 'all';

	const BASKET_APPLIED_FIELD = 'DISCOUNT_APPLIED';

	const VALUE_EPS = 1E-5;

	const MODE_CALCULATE = 0x0001;
	const MODE_MANUAL = 0x0002;
	const MODE_MIXED = 0x0004;

	const APPLY_COUNTER_START = -1;

	const PERCENT_FROM_CURRENT_PRICE = 0x0001;
	const PERCENT_FROM_BASE_PRICE = 0x0002;

	const RESULT_ENTITY_BASKET = 0x0001;
	const RESULT_ENTITY_DELIVERY = 0x0002;

	const APPLY_RESULT_MODE_COUNTER = 0x0001;
	const APPLY_RESULT_MODE_DESCR = 0x0002;
	const APPLY_RESULT_MODE_SIMPLE = 0x0004;

	protected static $useMode = self::MODE_CALCULATE;
	protected static $applyCounter = self::APPLY_COUNTER_START;
	protected static $actionResult = array();
	protected static $actionDescription = array();
	protected static $applyResult = array();
	protected static $applyResultMode = self::APPLY_RESULT_MODE_COUNTER;

	protected static $useBasketFilter = true;

	protected static $percentValueMode = self::PERCENT_FROM_CURRENT_PRICE;
	protected static $currencyId = '';
	protected static $siteId = '';

	private static $compatibleBasketFields = array('DISCOUNT_PRICE', 'PRICE', 'VAT_VALUE', 'PRICE_DEFAULT');

	/**
	 * Check for zero value.
	 *
	 * @param float|int $value Price or discount value.
	 * @return float|int
	 */
	public static function roundZeroValue($value)
	{
		return (abs($value) <= self::VALUE_EPS ? 0 : $value);
	}

	/**
	 * Rounded value with sale rules.
	 *
	 * @param float|int $value Value.
	 * @param string $currency Currency.
	 * @return float
	 */
	public static function roundValue($value, /** @noinspection PhpUnusedParameterInspection */ $currency)
	{
		/** @noinspection PhpInternalEntityUsedInspection */
		return Sale\PriceMaths::roundPrecision($value);
	}

	/**
	 * Set use actions mode.
	 *
	 * @param int $mode Use mode.
	 * @param array $config Config.
	 * @return void
	 */
	public static function setUseMode($mode, array $config = array())
	{
		$mode = (int)$mode;
		if ($mode !== self::MODE_CALCULATE && $mode !== self::MODE_MANUAL && $mode !== self::MODE_MIXED)
			return;
		self::$useMode = $mode;
		switch (self::$useMode)
		{
			case self::MODE_CALCULATE:
				$percentOption = (string)Main\Config\Option::get('sale', 'get_discount_percent_from_base_price');
				self::$percentValueMode = ($percentOption == 'Y' ? self::PERCENT_FROM_BASE_PRICE : self::PERCENT_FROM_CURRENT_PRICE);
				unset($percentOption);
				if (isset($config['CURRENCY']))
					self::$currencyId = $config['CURRENCY'];
				if (isset($config['SITE_ID']))
				{
					self::$siteId = $config['SITE_ID'];
					if (self::$currencyId == '')
						self::$currencyId = Sale\Internals\SiteCurrencyTable::getSiteCurrency(self::$siteId);
				}
				break;
			case self::MODE_MANUAL:
			case self::MODE_MIXED:
				$percentOption = '';
				if (isset($config['USE_BASE_PRICE']))
					$percentOption = $config['USE_BASE_PRICE'];
				if ($percentOption == '')
					$percentOption = (string)Main\Config\Option::get('sale', 'get_discount_percent_from_base_price');
				self::$percentValueMode = ($percentOption == 'Y' ? self::PERCENT_FROM_BASE_PRICE : self::PERCENT_FROM_CURRENT_PRICE);
				unset($percentOption);
				if (isset($config['CURRENCY']))
					self::$currencyId = $config['CURRENCY'];
				if (isset($config['SITE_ID']))
				{
					self::$siteId = $config['SITE_ID'];
					if (self::$currencyId == '')
						self::$currencyId = Sale\Internals\SiteCurrencyTable::getSiteCurrency(self::$siteId);
				}
				break;
		}
		static::clearApplyCounter();
		static::enableBasketFilter();
	}

	/**
	 * Return current use actions mode.
	 *
	 * @return int
	 */
	public static function getUseMode()
	{
		return self::$useMode;
	}

	/**
	 * Check calculate mode.
	 *
	 * @return bool
	 */
	public static function isCalculateMode()
	{
		return self::$useMode === self::MODE_CALCULATE;
	}

	/**
	 * Check manual mode.
	 *
	 * @return bool
	 */
	public static function isManualMode()
	{
		return self::$useMode === self::MODE_MANUAL;
	}

	/**
	 * Check mixed mode.
	 *
	 * @return bool
	 */
	public static function isMixedMode()
	{
		return self::$useMode === self::MODE_MIXED;
	}

	/**
	 * Return calculate mode for percent discount.
	 *
	 * @return int
	 */
	public static function getPercentMode()
	{
		return self::$percentValueMode;
	}

	/**
	 * Return calculate currency.
	 *
	 * @return string
	 */
	public static function getCurrency()
	{
		return self::$currencyId;
	}

	/**
	 * Clear apply counter.
	 *
	 * @return void
	 */
	public static function clearApplyCounter()
	{
		self::$applyCounter = self::APPLY_COUNTER_START;
	}

	/**
	 * Return current apply counter.
	 *
	 * @return int
	 */
	public static function getApplyCounter()
	{
		return self::$applyCounter;
	}

	/**
	 * Increment current apply counter. Use BEFORE discount apply.
	 *
	 * @return void
	 */
	public static function increaseApplyCounter()
	{
		self::$applyCounter++;
	}

	/**
	 * Disable basket filter for mixed apply mode.
	 *
	 * @return void
	 */
	public static function disableBasketFilter()
	{
		if (!static::isMixedMode())
			return;
		self::$useBasketFilter = false;
	}

	/**
	 * Enable basket filter for mixed apply mode.
	 *
	 * @return void
	 */
	public static function enableBasketFilter()
	{
		if (!static::isMixedMode())
			return;
		self::$useBasketFilter = true;
	}

	/**
	 * Return is enabled basket filter mixed apply mode.
	 *
	 * @return bool
	 */
	public static function usedBasketFilter()
	{
		return self::$useBasketFilter;
	}

	/**
	 * Fill compatible fields for old public api.
	 *
	 * @param array &$order Order data.
	 * @return void
	 */
	public static function fillCompatibleFields(array &$order)
	{
		if (Main\Context::getCurrent()->getRequest()->isAdminSection())
			return;
		if (empty($order) || !is_array($order))
			return;
		if (!empty($order['BASKET_ITEMS']) && is_array($order['BASKET_ITEMS']))
		{
			foreach ($order['BASKET_ITEMS'] as &$item)
			{
				foreach (self::$compatibleBasketFields as &$fieldName)
				{
					if (array_key_exists($fieldName, $item) && !is_array($item[$fieldName]))
						$item['~'.$fieldName] = $item[$fieldName];
				}
				unset($fieldName);
			}
			unset($item);
		}
	}

	/**
	 * Basket filter.
	 *
	 * @param array $item Basket item.
	 * @return bool
	 */
	public static function filterBasketForAction(array $item)
	{
		return (
			(!isset($item['CUSTOM_PRICE']) || $item['CUSTOM_PRICE'] != 'Y') &&
			(
				(isset($item['TYPE']) && (int)$item['TYPE'] == Sale\BasketItem::TYPE_SET) ||
				(!isset($item['SET_PARENT_ID']) || (int)$item['SET_PARENT_ID'] <= 0)
			) &&
			(!isset($item['ITEM_FIX']) || $item['ITEM_FIX'] != 'Y') &&
			(!isset($item['LAST_DISCOUNT']) || $item['LAST_DISCOUNT'] != 'Y') &&
			(!isset($item['IN_SET']) || $item['IN_SET'] != 'Y')
		);
	}

	/**
	 * Return all actions description.
	 *
	 * @return array
	 */
	public static function getActionDescription()
	{
		return self::$actionDescription;
	}

	/**
	 * Return all actions results.
	 *
	 * @return array
	 */
	public static function getActionResult()
	{
		return self::$actionResult;
	}

	/**
	 * Set apply result format mode.
	 *
	 * @param int $mode			Apply result mode.
	 * @return void
	 */
	public static function setApplyResultMode($mode)
	{
		$mode = (int)$mode;
		if ($mode != self::APPLY_RESULT_MODE_COUNTER && $mode != self::APPLY_RESULT_MODE_DESCR && $mode != self::APPLY_RESULT_MODE_SIMPLE)
			return;
		self::$applyResultMode = $mode;
		self::$applyResult = array();
	}

	/**
	 * Return apply result format mode.
	 *
	 * @return int
	 */
	public static function getApplyResultMode()
	{
		return self::$applyResultMode;
	}

	/**
	 * Set apply result list.
	 *
	 * @param array $applyResult Apply data.
	 * @return void
	 */
	public static function setApplyResult(array $applyResult)
	{
		self::$applyResult = $applyResult;
	}

	/**
	 * Clear actions description and result.
	 *
	 * @return void
	 */
	public static function clearAction()
	{
		self::clearApplyCounter();
		self::$applyResult = array();
		self::$actionResult = array();
		self::$actionDescription = array();
	}

	/**
	 * Basket action.
	 *
	 * @param array &$order Order data.
	 * @param array $action Action detail
	 *    keys are case sensitive:
	 *        <ul>
	 *        <li>float|int VALUE                Discount value.
	 *        <li>char UNIT                    Discount type.
	 *        <li>string CURRENCY                Currency discount (optional).
	 *        <li>char MAX_BOUND                Max bound.
	 *        </ul>.
	 * @param callable $filter Filter for basket items.
	 * @return void
	 */
	public static function applyToBasket(array &$order, array $action, $filter)
	{
		static::increaseApplyCounter();

		if (!isset($action['VALUE']) || !isset($action['UNIT']))
			return;

		$orderCurrency = static::getCurrency();
		$value = (float)$action['VALUE'];
		$unit = (string)$action['UNIT'];
		$currency = (isset($action['CURRENCY']) ? $action['CURRENCY'] : $orderCurrency);
		$maxBound = false;
		if ($unit == self::VALUE_TYPE_FIX && $value < 0)
			$maxBound = (isset($action['MAX_BOUND']) && $action['MAX_BOUND'] == 'Y');
		$valueAction = (
			$value < 0
			? Sale\OrderDiscountManager::DESCR_VALUE_ACTION_DISCOUNT
			: Sale\OrderDiscountManager::DESCR_VALUE_ACTION_EXTRA
		);

		$actionDescription = array(
			'ACTION_TYPE' => Sale\OrderDiscountManager::DESCR_TYPE_VALUE,
			'VALUE' => abs($value),
			'VALUE_ACTION' => $valueAction
		);
		switch ($unit)
		{
			case self::VALUE_TYPE_SUMM:
				$actionDescription['VALUE_TYPE'] = Sale\OrderDiscountManager::DESCR_VALUE_TYPE_SUMM;
				$actionDescription['VALUE_UNIT'] = $currency;
				break;
			case self::VALUE_TYPE_PERCENT:
				$actionDescription['VALUE_TYPE'] = Sale\OrderDiscountManager::DESCR_VALUE_TYPE_PERCENT;
				break;
			case self::VALUE_TYPE_FIX:
				$actionDescription['VALUE_TYPE'] = Sale\OrderDiscountManager::DESCR_VALUE_TYPE_CURRENCY;
				$actionDescription['VALUE_UNIT'] = $currency;
				if ($maxBound)
					$actionDescription['ACTION_TYPE'] = Sale\OrderDiscountManager::DESCR_TYPE_MAX_BOUND;
				break;
			default:
				return;
				break;
		}
		static::setActionDescription(self::RESULT_ENTITY_BASKET, $actionDescription);

		if (empty($order['BASKET_ITEMS']) || !is_array($order['BASKET_ITEMS']))
			return;

		static::enableBasketFilter();
		$filteredBasket = static::getBasketForApply($order['BASKET_ITEMS'], $filter, $action);
		if (empty($filteredBasket))
			return;

		$applyBasket = array_filter($filteredBasket, '\Bitrix\Sale\Discount\Actions::filterBasketForAction');
		unset($filteredBasket);
		if (empty($applyBasket))
			return;

		if ($unit == self::VALUE_TYPE_SUMM || $unit == self::VALUE_TYPE_FIX)
		{
			if ($currency != $orderCurrency)
				/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
				$value = \CCurrencyRates::convertCurrency($value, $currency, $orderCurrency);
			if ($unit == self::VALUE_TYPE_SUMM)
			{
				$value = static::getPercentByValue($applyBasket, $value);
				if (
					($valueAction == Sale\OrderDiscountManager::DESCR_VALUE_ACTION_DISCOUNT && ($value >= 0 || $value < -100))
					||
					($valueAction == Sale\OrderDiscountManager::DESCR_VALUE_ACTION_EXTRA && $value <= 0)
				)
					return;
				$unit = self::VALUE_TYPE_PERCENT;
			}
		}
		$value = static::roundZeroValue($value);
		if ($value == 0)
			return;

		foreach ($applyBasket as $basketCode => $basketRow)
		{
			$calculateValue = $value;
			if ($unit == self::VALUE_TYPE_PERCENT)
				$calculateValue = static::percentToValue($basketRow, $calculateValue);
			$calculateValue = static::roundValue($calculateValue, $basketRow['CURRENCY']);

			$result = static::roundZeroValue($basketRow['PRICE'] + $calculateValue);
			if ($maxBound && $result < 0)
			{
				$result = 0;
				$calculateValue = -$basketRow['PRICE'];
			}
			if ($result >= 0)
			{
				if (!isset($basketRow['DISCOUNT_PRICE']))
					$basketRow['DISCOUNT_PRICE'] = 0;
				$basketRow['PRICE'] = $result;
				if (isset($basketRow['PRICE_DEFAULT']))
					$basketRow['PRICE_DEFAULT'] = $result;
				$basketRow['DISCOUNT_PRICE'] -= $calculateValue;

				$order['BASKET_ITEMS'][$basketCode] = $basketRow;

				$rowActionDescription = $actionDescription;
				$rowActionDescription['BASKET_CODE'] = $basketCode;
				$rowActionDescription['RESULT_VALUE'] = abs($calculateValue);
				$rowActionDescription['RESULT_UNIT'] = $orderCurrency;
				static::setActionResult(self::RESULT_ENTITY_BASKET, $rowActionDescription);
				unset($rowActionDescription);
			}
			unset($result);
		}
		unset($basketCode, $basketRow);
	}

	/**
	 * Delivery action.
	 *
	 * @param array &$order Order data.
	 * @param array $action Action detail
	 *    keys are case sensitive:
	 *        <ul>
	 *        <li>float|int VALUE                Discount value.
	 *        <li>char UNIT                    Discount type.
	 *        <li>string CURRENCY                Currency discount (optional).
	 *        <li>char MAX_BOUND                Max bound.
	 *        </ul>.
	 * @return void
	 */
	public static function applyToDelivery(array &$order, array $action)
	{
		static::increaseApplyCounter();

		if (!isset($action['VALUE']) || !isset($action['UNIT']))
			return;
		if ($action['UNIT'] != self::VALUE_TYPE_PERCENT && $action['UNIT'] != self::VALUE_TYPE_FIX)
			return;

		$orderCurrency = static::getCurrency();
		$unit = (string)$action['UNIT'];
		$value = (float)$action['VALUE'];
		$currency = (isset($action['CURRENCY']) ? $action['CURRENCY'] : $orderCurrency);
		$maxBound = false;
		if ($unit == self::VALUE_TYPE_FIX && $value < 0)
			$maxBound = (isset($action['MAX_BOUND']) && $action['MAX_BOUND'] == 'Y');

		$actionDescription = array(
			'ACTION_TYPE' => Sale\OrderDiscountManager::DESCR_TYPE_VALUE,
			'VALUE' => abs($value),
			'VALUE_ACTION' => (
				$value < 0
				? Sale\OrderDiscountManager::DESCR_VALUE_ACTION_DISCOUNT
				: Sale\OrderDiscountManager::DESCR_VALUE_ACTION_EXTRA
			)
		);
		if ($maxBound)
			$actionDescription['ACTION_TYPE'] = Sale\OrderDiscountManager::DESCR_TYPE_MAX_BOUND;

		switch ($unit)
		{
			case self::VALUE_TYPE_PERCENT:
				$actionDescription['VALUE_TYPE'] = Sale\OrderDiscountManager::DESCR_VALUE_TYPE_PERCENT;
				$value = ($order['PRICE_DELIVERY'] * $value) / 100;
				break;
			case self::VALUE_TYPE_FIX:
				$actionDescription['VALUE_TYPE'] = Sale\OrderDiscountManager::DESCR_VALUE_TYPE_CURRENCY;
				$actionDescription['VALUE_UNIT'] = $currency;
				if ($currency != $orderCurrency)
					/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
					$value = \CCurrencyRates::convertCurrency($value, $currency, $orderCurrency);
				break;
		}
		static::setActionDescription(self::RESULT_ENTITY_DELIVERY, $actionDescription);

		if (isset($order['CUSTOM_PRICE_DELIVERY']) && $order['CUSTOM_PRICE_DELIVERY'] == 'Y')
			return;
		if (
			!isset($order['PRICE_DELIVERY'])
			|| (
				static::roundZeroValue($order['PRICE_DELIVERY']) == 0
				&& $actionDescription['VALUE_ACTION'] == Sale\OrderDiscountManager::DESCR_VALUE_ACTION_DISCOUNT
			)
		)
			return;

		$value = static::roundValue($value, $order['CURRENCY']);
		$value = static::roundZeroValue($value);
		if ($value == 0)
			return;

		$resultValue = static::roundZeroValue($order['PRICE_DELIVERY'] + $value);
		if ($maxBound && $resultValue < 0)
		{
			$resultValue = 0;
			$value = $order['PRICE_DELIVERY'];
		}

		if ($resultValue < 0)
			return;

		if (!isset($order['PRICE_DELIVERY_DIFF']))
			$order['PRICE_DELIVERY_DIFF'] = 0;
		$order['PRICE_DELIVERY_DIFF'] -= $value;
		$order['PRICE_DELIVERY'] = $resultValue;

		$actionDescription['RESULT_VALUE'] = abs($value);
		$actionDescription['RESULT_UNIT'] = $orderCurrency;

		static::setActionResult(self::RESULT_ENTITY_DELIVERY, $actionDescription);
		unset($actionDescription);
	}

	/**
	 * Simple gift action.
	 *
	 * @param array &$order			Order data.
	 * @param callable $filter		Filter.
	 * @throws Main\ArgumentOutOfRangeException
	 * @return void
	 */
	public static function applySimpleGift(array &$order, $filter)
	{
		static::increaseApplyCounter();

		$actionDescription = array(
			'ACTION_TYPE' => Sale\OrderDiscountManager::DESCR_TYPE_SIMPLE,
			'ACTION_DESCRIPTION' => Loc::getMessage('BX_SALE_DISCOUNT_ACTIONS_SIMPLE_GIFT_DESCR')
		);
		static::setActionDescription(self::RESULT_ENTITY_BASKET, $actionDescription);

		if (!is_callable($filter))
			return;

		if (empty($order['BASKET_ITEMS']) || !is_array($order['BASKET_ITEMS']))
			return;

		static::disableBasketFilter();

		$itemsCopy = $order['BASKET_ITEMS'];
		Main\Type\Collection::sortByColumn($itemsCopy, 'PRICE', null, null, true);
		$filteredBasket = static::getBasketForApply(
			$itemsCopy,
			$filter,
			array(
				'GIFT_TITLE' => Loc::getMessage('BX_SALE_DISCOUNT_ACTIONS_SIMPLE_GIFT_DESCR')
			)
		);
		unset($itemsCopy);

		static::enableBasketFilter();

		if (empty($filteredBasket))
			return;

		$applyBasket = array_filter($filteredBasket, '\Bitrix\Sale\Discount\Actions::filterBasketForAction');
		unset($filteredBasket);
		if (empty($applyBasket))
			return;

		foreach ($applyBasket as $basketCode => $basketRow)
		{
			$basketRow['DISCOUNT_PRICE'] = $basketRow['BASE_PRICE'];
			$basketRow['PRICE'] = 0;

			$order['BASKET_ITEMS'][$basketCode] = $basketRow;

			$rowActionDescription = $actionDescription;
			$rowActionDescription['BASKET_CODE'] = $basketCode;
			static::setActionResult(self::RESULT_ENTITY_BASKET, $rowActionDescription);
			unset($rowActionDescription);
		}
		unset($basketCode, $basketRow);
	}

	/**
	 * Return basket item for action apply.
	 *
	 * @param array $basket Basket.
	 * @param mixed $filter Filter.
	 * @param array $action Prepare data.
	 * @return mixed
	 */
	public static function getBasketForApply(array $basket, $filter, $action = array())
	{
		$result = array();
		switch (static::getUseMode())
		{
			case self::MODE_CALCULATE:
				$result = (is_callable($filter) ? array_filter($basket, $filter) : $basket);
				break;
			case self::MODE_MANUAL:
			case self::MODE_MIXED:
				switch (static::getApplyResultMode())
				{
					case self::APPLY_RESULT_MODE_COUNTER:
						$currentCounter = static::getApplyCounter();
						$basketCodeList = array_keys($basket);
						foreach ($basketCodeList as &$code)
						{
							if (empty(self::$applyResult['BASKET'][$code]) || !is_array(self::$applyResult['BASKET'][$code]))
								continue;
							if (!in_array($currentCounter, self::$applyResult['BASKET'][$code]))
								continue;
							$result[$code] = $basket[$code];
						}
						unset($code, $basketCodeList, $currentCounter);
						break;
					case self::APPLY_RESULT_MODE_DESCR:
						$basketCodeList = array_keys($basket);
						foreach ($basketCodeList as &$code)
						{
							if (empty(self::$applyResult['BASKET'][$code]) || !is_array(self::$applyResult['BASKET'][$code]))
								continue;
							foreach (self::$applyResult['BASKET'][$code] as $descr)
							{
								if (static::compareBasketResultDescr($action, $descr))
								{
									$result[$code] = $basket[$code];
									break;
								}
							}
							unset($descr);
							// only for old format simple gifts
							if (!isset($result[$code]))
							{
								if (isset($action['GIFT_TITLE']))
								{
									end(self::$applyResult['BASKET'][$code]);
									$descr = current(self::$applyResult['BASKET'][$code]);
									if (
										$descr['TYPE'] == Sale\OrderDiscountManager::DESCR_TYPE_SIMPLE
										&& $descr['DESCR'] == $action['GIFT_TITLE']
									)
										$result[$code] = $basket[$code];
									unset($descr);
								}
							}
						}
						unset($code, $basketCodeList);
						break;
					case self::APPLY_RESULT_MODE_SIMPLE:
						$basketCodeList = array_keys($basket);
						foreach ($basketCodeList as &$code)
						{
							if (isset(self::$applyResult['BASKET'][$code]))
								$result[$code] = $basket[$code];
						}
						unset($code, $basketCodeList);
						break;
				}
				break;
		}

		return $result;
	}

	/**
	 * Save action description.
	 *
	 * @param int $type Action object type.
	 * @param array $description Description.
	 * @return void
	 */
	public static function setActionDescription($type, $description)
	{
		if (!static::isCalculateMode())
			return;
		if (empty($description) || !is_array($description) || !isset($description['ACTION_TYPE']))
			return;
		$actionType = $description['ACTION_TYPE'];
		if ($actionType == Sale\OrderDiscountManager::DESCR_TYPE_SIMPLE)
			$description = (isset($description['ACTION_DESCRIPTION']) ? $description['ACTION_DESCRIPTION'] : '');

		$prepareResult = Sale\OrderDiscountManager::prepareDiscountDescription($actionType, $description);
		unset($actionType);

		if ($prepareResult->isSuccess())
		{
			switch ($type)
			{
				case self::RESULT_ENTITY_BASKET:
					if (!isset(self::$actionDescription['BASKET']))
						self::$actionDescription['BASKET'] = array();
					self::$actionDescription['BASKET'][static::getApplyCounter()] = $prepareResult->getData();
					break;
				case self::RESULT_ENTITY_DELIVERY:
					if (!isset(self::$actionDescription['DELIVERY']))
						self::$actionDescription['DELIVERY'] = array();
					self::$actionDescription['DELIVERY'][static::getApplyCounter()] = $prepareResult->getData();
					break;
			}
		}
		unset($prepareResult);
	}

	/**
	 * Save result.
	 *
	 * @param int $entity			Action object type.
	 * @param array $actionResult	Result description.
	 * @return void
	 */
	public static function setActionResult($entity, array $actionResult)
	{
		if (empty($actionResult) || !isset($actionResult['ACTION_TYPE']))
			return;

		$actionType = $actionResult['ACTION_TYPE'];
		if ($actionType == Sale\OrderDiscountManager::DESCR_TYPE_SIMPLE)
			$actionDescription = (isset($actionResult['ACTION_DESCRIPTION']) ? $actionResult['ACTION_DESCRIPTION'] : '');
		else
			$actionDescription = $actionResult;
		$prepareResult = Sale\OrderDiscountManager::prepareDiscountDescription($actionType, $actionDescription);
		unset($actionDescription, $actionType);

		if ($prepareResult->isSuccess())
		{
			switch ($entity)
			{
				case self::RESULT_ENTITY_BASKET:
					if (!isset(self::$actionResult['BASKET']))
						self::$actionResult['BASKET'] = array();
					$basketCode = $actionResult['BASKET_CODE'];
					if (!isset(self::$actionResult['BASKET'][$basketCode]))
						self::$actionResult['BASKET'][$basketCode] = array();
					self::$actionResult['BASKET'][$basketCode][static::getApplyCounter()] = $prepareResult->getData();
					unset($basketCode);
					break;
				case self::RESULT_ENTITY_DELIVERY:
					if (!isset(self::$actionResult['DELIVERY']))
						self::$actionResult['DELIVERY'] = array();
					self::$actionResult['DELIVERY'][static::getApplyCounter()] = $prepareResult->getData();
					break;
			}
		}
		unset($prepareResult);
	}

	/**
	 * @param int $entity			Entity id.
	 * @param array $entityParams	Entity params (optional).
	 * @return void
	 */
	public static function clearEntityActionResult($entity, array $entityParams = array())
	{
		switch ($entity)
		{
			case self::RESULT_ENTITY_BASKET:
				if (empty($entityParams))
				{
					if (array_key_exists('BASKET', self::$actionResult))
						unset(self::$actionResult['BASKET']);
				}
				else
				{
					if (isset($entityParams['BASKET_CODE']) && array_key_exists($entityParams['BASKET_CODE'], self::$actionResult['BASKET']))
						unset(self::$actionResult['BASKET'][$entityParams['BASKET_CODE']]);
				}
				break;
			case self::RESULT_ENTITY_DELIVERY:
				if (array_key_exists('DELIVERY', self::$actionResult))
					unset(self::$actionResult['DELIVERY']);
				break;
		}
	}

	/**
	 * Return percent value.
	 *
	 * @param array $basket Basket.
	 * @param int|float $value Value.
	 * @return float
	 */
	public static function getPercentByValue($basket, $value)
	{
		$summ = 0;
		switch (static::getPercentMode())
		{
			case self::PERCENT_FROM_BASE_PRICE:
				foreach ($basket as $basketRow)
					$summ += (float)$basketRow['BASE_PRICE'] * (float)$basketRow['QUANTITY'];
				unset($basketRow);
				break;
			case self::PERCENT_FROM_CURRENT_PRICE:
				foreach ($basket as $basketRow)
					$summ += (float)$basketRow['PRICE'] * (float)$basketRow['QUANTITY'];
				unset($basketRow);
				break;
		}

		return static::roundZeroValue($summ > 0 ? ($value * 100) / $summ : 0);
	}

	/**
	 * Calculate percent price.
	 *
	 * @param array $basketRow Basket item.
	 * @param float $percent Percent value.
	 * @return float
	 */
	public static function percentToValue($basketRow, $percent)
	{
		$value = 0.0;
		switch (static::getPercentMode())
		{
			case self::PERCENT_FROM_BASE_PRICE:
				$value = ((float)$basketRow['BASE_PRICE'] * $percent) / 100;
				break;
			case self::PERCENT_FROM_CURRENT_PRICE:
				$value = ((float)$basketRow['PRICE'] * $percent) / 100;
				break;
		}

		return $value;
	}

	/**
	 * Return check result for error mode.
	 *
	 * @internal
	 * @param array $action			Action description.
	 * @param array $resultDescr	Result description.
	 * @return bool
	 */
	protected static function compareBasketResultDescr(array $action, $resultDescr)
	{
		$result = false;

		if (empty($action))
			return $result;
		if (!is_array($resultDescr) || !isset($resultDescr['TYPE']))
			return $result;

		$currency = (isset($action['CURRENCY']) ? $action['CURRENCY'] : static::getCurrency());
		$value = abs($action['VALUE']);
		$valueAction = (
			$action['VALUE'] < 0
			? Sale\OrderDiscountManager::DESCR_VALUE_ACTION_DISCOUNT
			: Sale\OrderDiscountManager::DESCR_VALUE_ACTION_EXTRA
		);

		switch ($resultDescr['TYPE'])
		{
			case Sale\OrderDiscountManager::DESCR_TYPE_VALUE:
				if (
					$resultDescr['VALUE'] == $value
					&& $resultDescr['VALUE_ACTION'] = $valueAction
				)
				{
					switch($action['UNIT'])
					{
						case self::VALUE_TYPE_SUMM:
							$result = (
								(
									$resultDescr['VALUE_TYPE'] == Sale\OrderDiscountManager::DESCR_VALUE_TYPE_SUMM_BASKET
									|| $resultDescr['VALUE_TYPE'] == Sale\OrderDiscountManager::DESCR_VALUE_TYPE_SUMM
								)
								&& $resultDescr['VALUE_UNIT'] == $currency
							);
							break;
						case self::VALUE_TYPE_PERCENT:
							$result = ($resultDescr['VALUE_TYPE'] == Sale\OrderDiscountManager::DESCR_VALUE_TYPE_PERCENT);
							break;
						case self::VALUE_TYPE_FIX:
							$result = (
								$resultDescr['VALUE_TYPE'] == Sale\OrderDiscountManager::DESCR_VALUE_TYPE_CURRENCY
								&& $resultDescr['VALUE_UNIT'] == $currency
							);
							break;
					}
				}
				break;
			case Sale\OrderDiscountManager::DESCR_TYPE_MAX_BOUND:
				$result = (
					$resultDescr['VALUE'] == $value
					&& $resultDescr['VALUE_ACTION'] == $valueAction
					&& $resultDescr['VALUE_TYPE'] == Sale\OrderDiscountManager::DESCR_VALUE_TYPE_CURRENCY
					&& $resultDescr['VALUE_UNIT'] == $currency
				);
				break;
		}

		unset($valueAction, $value, $currency);

		return $result;
	}
}