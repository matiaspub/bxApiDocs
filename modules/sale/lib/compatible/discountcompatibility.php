<?php
namespace Bitrix\Sale\Compatible;

use Bitrix\Main,
	Bitrix\Sale,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DiscountCompatibility
{
	const MODE_CLIENT = 0x0001;
	const MODE_MANAGER = 0x0002;
	const MODE_ORDER = 0x0004;
	const MODE_SYSTEM = 0x0008;
	const MODE_EXTERNAL = 0x0010;
	const MODE_DISABLED = 0x0020;

	const ERROR_ID = 'BX_SALE_DISCOUNT_COMPATIBILITY';

	protected static $init = false;
	protected static $useMode = self::MODE_CLIENT;
	protected static $errors = array();
	protected static $config = array();
	protected static $order = null;
	protected static $discountUseMode = null;
	protected static $basketBasePrice = array();
	protected static $basketDiscountList = array();
	protected static $discountResult = array();
	protected static $discountsCache = array();
	protected static $couponsCache = array();
	protected static $previousOrderData = array();
	protected static $currentOrderData = array();
	protected static $compatibleSaleDiscountResult = array();
	protected static $useCompatible = false;
	protected static $shipmentOrder = null;
	protected static $shipmentId = null;
	protected static $basketCodes = array();
	protected static $saved = false;
	protected static $repeatSave = false;

	/**
	 * Handler for use old api.
	 *
	 * @param Main\Event $event		Event data.
	 * @return Main\EventResult
	 */
	public static function OnSaleBasketItemRefreshData(Main\Event $event)
	{
		if (static::isInited() && static::usedByClient())
		{
			$parameters = $event->getParameters();
			/** @var \Bitrix\Sale\BasketItem $basketItem */
			$basketItem = $parameters['ENTITY'];
			/** @var array $values */
			$values = $parameters['VALUES'];
			unset($parameters);

			if (
				$basketItem instanceof Sale\BasketItem
				&& $basketItem->getField('DELAY') == 'N'
				&& !empty($values)
				&& is_array($values)
			)
			{
				static::setBasketItemData($basketItem->getBasketCode(), $values);
			}
			unset($values, $basketItem);
		}
		return new Main\EventResult(Main\EventResult::SUCCESS, null, 'sale');
	}

	/**
	 * Init use mode.
	 *
	 * @param int $mode				Save discount information mode.
	 * @param array $config			Initial params (site, currency).
	 * 		keys are case sensitive:
	 * 			<ul>
	 * 			<li>string SITE_ID		Current site
	 * 			<li>string CURRENCY		Site currency
	 * 			<li>string ORDER_ID		Order id
	 * 			</ul>.
	 * @return void
	 */
	public static function initUseMode($mode = self::MODE_CLIENT, $config = array())
	{
		$adminSection = (defined('ADMIN_SECTION') && ADMIN_SECTION === true);
		$mode = (int)$mode;
		if (!is_array($config))
			$config = array();

		if ($adminSection)
		{
			self::$useMode = self::MODE_SYSTEM;
			switch ($mode)
			{
				case self::MODE_MANAGER:
					if (empty($config['SITE_ID']))
						self::$errors[] = Loc::getMessage('BX_SALE_DCL_ERR_SITE_ABSENT');
					elseif (empty($config['CURRENCY']))
						$config['CURRENCY'] = Sale\Internals\SiteCurrencyTable::getCurrency($config['SITE_ID']);
					if (empty($config['CURRENCY']))
						self::$errors[] = Loc::getMessage('BX_SALE_DCL_ERR_CURRENCY_ABSENT');
					if (empty(self::$errors))
					{
						self::$useMode = self::MODE_MANAGER;
						$config['SALE_DISCOUNT_ONLY'] = (string)Main\Config\Option::get('sale', 'use_sale_discount_only');
						self::$config = $config;
						self::$discountUseMode = Sale\Discount::USE_MODE_FULL;
					}
					break;
				case self::MODE_ORDER:
					if (empty($config['ORDER_ID']))
						self::$errors[] = Loc::getMessage('BX_SALE_DCL_ERR_ORDER_ID_ABSENT');
					if (empty($config['SITE_ID']))
						self::$errors[] = Loc::getMessage('BX_SALE_DCL_ERR_SITE_ABSENT');
					if (empty($config['CURRENCY']))
						self::$errors[] = Loc::getMessage('BX_SALE_DCL_ERR_CURRENCY_ABSENT');
					if (empty(self::$errors))
					{
						self::$useMode = self::MODE_ORDER;
						self::$order = $config['ORDER_ID'];
						unset($config['ORDER_ID']);
						self::$config = $config;
					}
					break;
				case self::MODE_SYSTEM:
					break;
				default:
					self::$errors[] = Loc::getMessage('BX_SALE_DCL_ERR_BAD_MODE');
			}
		}
		else
		{
			self::$useMode = self::MODE_SYSTEM;
			switch ($mode)
			{
				case self::MODE_CLIENT:
					self::$useMode = self::MODE_CLIENT;
					if (empty($config['SITE_ID']))
						$config['SITE_ID'] = SITE_ID;
					if (empty($config['CURRENCY']))
						$config['CURRENCY'] = Sale\Internals\SiteCurrencyTable::getSiteCurrency($config['SITE_ID']);
					$config['SALE_DISCOUNT_ONLY'] = (string)Main\Config\Option::get('sale', 'use_sale_discount_only');
					self::$config = $config;
					self::$discountUseMode = Sale\Discount::USE_MODE_FULL;
					break;
				case self::MODE_EXTERNAL:
					self::$useMode = self::MODE_EXTERNAL;
					if (empty($config['SITE_ID']))
						$config['SITE_ID'] = SITE_ID;
					if (empty($config['CURRENCY']))
						$config['CURRENCY'] = Sale\Internals\SiteCurrencyTable::getSiteCurrency($config['SITE_ID']);
					$config['SALE_DISCOUNT_ONLY'] = (string)Main\Config\Option::get('sale', 'use_sale_discount_only');
					self::$config = $config;
					self::$discountUseMode = Sale\Discount::USE_MODE_FULL;
					break;
				case self::MODE_SYSTEM:
					break;
				case self::MODE_DISABLED:
					self::$useMode = self::MODE_DISABLED;
					break;
				default:
					self::$errors[] = Loc::getMessage('BX_SALE_DCL_ERR_BAD_MODE');
					break;
			}
		}
	}

	/**
	 * Return use mode.
	 *
	 * @return int
	 */
	public static function getUseMode()
	{
		return self::$useMode;
	}

	/**
	 * Check client use mode.
	 *
	 * @return bool
	 */
	public static function usedByClient()
	{
		return (self::$useMode == self::MODE_CLIENT);
	}

	/**
	 * Check manager use mode.
	 *
	 * @return bool
	 */
	public static function usedByManager()
	{
		return (self::$useMode == self::MODE_MANAGER || self::$useMode == self::MODE_ORDER);
	}

	/**
	 * Return saved flag.
	 *
	 * @return bool
	 */
	public static function isSaved()
	{
		return self::$saved;
	}

	/**
	 * Set save flag.
	 *
	 * @param bool $save		Save flag.
	 * @return void
	 */
	public static function setSaved($save)
	{
		if ($save !== true && $save !== false)
			return;
		self::$saved = $save;
	}

	/**
	 * Return repeat save flag (for old components only).
	 *
	 * @return bool
	 */
	public static function isRepeatSave()
	{
		return self::$repeatSave;
	}

	/**
	 * Set repeat flag.
	 *
	 * @param bool $repeat		Repeat flag.
	 * @return void
	 */
	public static function setRepeatSave($repeat)
	{
		if ($repeat !== true && $repeat !== false)
			return;
		self::$repeatSave = $repeat;
	}

	/**
	 * Return result operation.
	 *
	 * @return bool
	 */
	public static function isSuccess()
	{
		return empty(self::$errors);
	}

	/**
	 * Return error list.
	 *
	 * @return array
	 */
	public static function getErrors()
	{
		return self::$errors;
	}

	/**
	 * Clear errors list.
	 *
	 * @return void
	 */
	public static function clearErrors()
	{
		self::$errors = array();
	}

	/**
	 * Returns configuration parameters.
	 *
	 * @return array
	 */
	public static function getConfig()
	{
		return self::$config;
	}


	/**
	 * Set shipment.
	 *
	 * @param int $order					Order id.
	 * @param int|array $shipment			Shipment id.
	 * @return void
	 */
	public static function setShipment($order, $shipment)
	{
		self::$shipmentOrder = $order;
		self::$shipmentId = $shipment;
	}

	/**
	 * Initialization discount save information.
	 *
	 * @param int $mode				Discount manager mode.
	 * @param array $config			Initial params (site, currency, order).
	 * @return void
	 */
	public static function init($mode = self::MODE_CLIENT, $config = array())
	{
		if (self::$init)
			return;
		self::clearErrors();
		self::initUseMode($mode, $config);
		if (!self::isSuccess())
			return;
		self::$basketBasePrice = array();
		self::$basketDiscountList = array();
		self::$useCompatible = true;
		Sale\OrderDiscountManager::setManagerConfig(self::$config);
		self::$saved = false;
		self::$repeatSave = false;
		self::$init = true;
	}

	/**
	 * Reinitialization discount save information.
	 *
	 * @param int $mode				Discount manager mode.
	 * @param array $config			Initial params (site, currency, order).
	 * @return void
	 */
	public static function reInit($mode = self::MODE_CLIENT, $config = array())
	{
		self::$init = false;
		self::init($mode, $config);
	}

	/**
	 * Check initialization.
	 *
	 * @return bool
	 */
	public static function isInited()
	{
		return self::$init;
	}

	/**
	 * Check used compatible calculate.
	 *
	 * @return bool
	 */
	public static function isUsed()
	{
		return self::$useCompatible;
	}

	/**
	 * Stops usage compatible mode. It's important for situation with new API and old API.
	 * Be careful! Don't forget revert this action.
	 *
	 * @internal
	 * @return void
	 */
	public static function stopUsageCompatible()
	{
		if(self::$useCompatible)
		{
			self::$useCompatible = false;
		}
	}

	/**
	 * Reverts usage compatible mode. It's important for situation with new API and old API.
	 *
	 * @internal
	 * @return void
	 */
	public static function revertUsageCompatible()
	{
		if(self::$init && !self::$useCompatible)
		{
			self::$useCompatible = true;
		}
	}

	/**
	 * Set base price for basket item.
	 *
	 * @param string|int $code				Basket code.
	 * @param float $price			Price.
	 * @param string $currency		Currency.
	 * @return void
	 */
	public static function setBasketItemBasePrice($code, $price, $currency)
	{
		if (!self::$init)
			self::init();
		if (!self::isSuccess() || self::$useMode == self::MODE_SYSTEM || self::$useMode == self::MODE_DISABLED)
			return;
		/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
		self::$basketBasePrice[$code] = (
			$currency == self::$config['CURRENCY']
			? $price
			: \CCurrencyRates::convertCurrency($price, $currency, self::$config['CURRENCY'])
		);
	}

	/**
	 * Set base price for all basket items.
	 *
	 * @param array $basket					Basket.
	 * @return void
	 * @throws Main\ArgumentNullException
	 */
	public static function setBasketBasePrice($basket)
	{
		if (!self::$init)
			self::init();
		if (!self::isSuccess() || self::$useMode == self::MODE_SYSTEM || self::$useMode == self::MODE_DISABLED)
			return;
		self::$basketBasePrice = array();
		if (empty($basket) || !is_array($basket))
			return;
		foreach ($basket as $code => $basketItem)
		{
			/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
			self::$basketBasePrice[$code] = (
				$basketItem['CURRENCY'] == self::$config['CURRENCY']
				? $basketItem['PRICE']
				: \CCurrencyRates::convertCurrency($basketItem['PRICE'], $basketItem['CURRENCY'], self::$config['CURRENCY'])
			);
		}
		unset($code, $basketItem);
	}

	/**
	 * Get base price for basket item.
	 *
	 * @param string|int $code				Basket code.
	 * @return float|null
	 */
	public static function getBasketItemBasePrice($code)
	{
		if (!self::$init)
			self::init();
		return (isset(self::$basketBasePrice[$code]) ? self::$basketBasePrice[$code] : null);
	}

	/**
	 * Set product discounts for basket item.
	 *
	 * @param string|int $code				Basket code.
	 * @param array $discountList			Discount list.
	 * @return void
	 */
	public static function setBasketItemDiscounts($code, $discountList)
	{
		if (!self::$init)
			self::init();
		if (!self::isSuccess() || self::$useMode == self::MODE_SYSTEM || self::$useMode == self::MODE_DISABLED)
			return;
		if (self::$config['SALE_DISCOUNT_ONLY'] == 'Y')
			return;
		if (!is_array($discountList))
			return;
		self::$basketDiscountList[$code] = $discountList;
	}

	/**
	 * Get product discounts for basket item.
	 *
	 * @param string|int $code				Basket code.
	 * @return null|array
	 */
	public static function getBasketItemDiscounts($code)
	{
		if (!self::$init)
			self::init();
		if (self::$config['SALE_DISCOUNT_ONLY'] == 'Y')
			return null;
		return (isset(self::$basketDiscountList[$code]) ? self::$basketDiscountList[$code] : null);
	}

	/**
	 * @param int|string $code				Basket code.
	 * @param array $providerData			Product data from provider.
	 * @throws Main\ArgumentNullException
	 * @return void
	 */
	public static function setBasketItemData($code, $providerData)
	{
		if ($code == '' || empty($providerData) || !is_array($providerData))
			return;
		if (isset($providerData['CUSTOM_PRICE']) && $providerData['CUSTOM_PRICE'] == 'Y')
		{
			static::clearBasketItemData($code);
			return;
		}
		if (isset($providerData['BASE_PRICE']) && isset($providerData['CURRENCY']))
			static::setBasketItemBasePrice($code, $providerData['BASE_PRICE'], $providerData['CURRENCY']);
		if (isset($providerData['DISCOUNT_LIST']))
		{
			if (!empty($providerData['DISCOUNT_LIST']) || isset(self::$basketDiscountList[$code]))
				static::setBasketItemDiscounts($code, $providerData['DISCOUNT_LIST']);
		}

		$fields['PRICE'] = static::getBasketItemBasePrice($code);
		if (empty($fields['PRICE']))
			return;

		$fields['DISCOUNT_PRICE'] = 0;
		$fields['CURRENCY'] = self::$config['CURRENCY'];

		if (self::$config['SALE_DISCOUNT_ONLY'] == 'Y')
			return;
		static::calculateBasketItemDiscount($code, $fields);
	}

	/**
	 * Clear basket item data.
	 *
	 * @param int $code				Basket code.
	 * @return void
	 */
	public static function clearBasketItemData($code)
	{
		if (isset(self::$basketBasePrice[$code]))
			unset(self::$basketBasePrice[$code]);
		if (isset(self::$basketDiscountList[$code]))
			unset(self::$basketDiscountList[$code]);
		if (isset(self::$discountResult['BASKET'][$code]))
			unset(self::$discountResult['BASKET'][$code]);
	}

	/**
	 * Clear results before calculate.
	 *
	 * @return void
	 */
	public static function clearDiscountResult()
	{
		self::$discountResult = array(
			'BASKET' => array(),
			'BASKET_ROUND' => array(),
			'ORDER' => array()
		);
		self::$compatibleSaleDiscountResult = array();
	}

	/**
	 * Fill base prices.
	 *
	 * @param array &$basket				Basket data.
	 * @return void
	 */
	public static function fillBasketData(&$basket)
	{
		if (!self::$init)
			self::init();
		if (!self::isSuccess() || self::$useMode == self::MODE_SYSTEM || self::$useMode == self::MODE_DISABLED)
			return;
		if (empty($basket) || !is_array($basket))
			return;
		$publicMode = self::usedByClient();

		foreach ($basket as $basketCode => $basketItem)
		{
			$code = ($publicMode ? $basketItem['ID'] : $basketCode);
			if (!isset($basketItem['DISCOUNT_PRICE']))
				$basketItem['DISCOUNT_PRICE'] = 0;
			$basketItem['BASE_PRICE'] = (isset(self::$basketBasePrice[$code])
				? self::$basketBasePrice[$code]
				: $basketItem['PRICE'] + $basketItem['DISCOUNT_PRICE']
			);
			if (self::isCustomPrice($basketItem))
			{
				if (array_key_exists($code, self::$basketDiscountList))
					unset(self::$basketDiscountList[$code]);
			}
			if (\CSaleBasketHelper::isSetItem($basketItem))
			{
				$basketItem['PRICE'] = $basketItem['BASE_PRICE'];
				$basketItem['DISCOUNT_PRICE'] = 0;
			}
			$basket[$basketCode] = $basketItem;
		}
		unset($basketCode, $basketItem);
	}

	/**
	 * Calculate basket discounts for save.
	 *
	 * @param array &$basket				Basket items.
	 * @return bool
	 */
	public static function calculateBasketDiscounts(&$basket)
	{
		if (!self::$init)
			return false;
		if (!self::isSuccess() || self::$useMode == self::MODE_SYSTEM || self::$useMode == self::MODE_DISABLED)
			return false;
		if (empty($basket) || !is_array($basket))
			return false;
		Sale\DiscountCouponsManager::clearApply();
		if (self::$config['SALE_DISCOUNT_ONLY'] == 'Y' || empty(self::$basketDiscountList))
			return true;
		$publicMode = self::usedByClient();

		foreach ($basket as $basketCode => $basketItem)
		{
			$code = ($publicMode ? $basketItem['ID'] : $basketCode);
			if (!static::calculateBasketItemDiscount($code, $basketItem))
				return false;
		}
		unset($basketCode, $basketItem);

		return true;
	}

	/**
	 * Save apply mode information.
	 *
	 * @param array &$basket				Basket items.
	 * @return void
	 */
	public static function setApplyMode(&$basket)
	{
		if (!self::$init)
			return;
		if (!self::isSuccess() || self::$useMode == self::MODE_SYSTEM || self::$useMode == self::MODE_DISABLED)
			return;
		if (empty($basket) || !is_array($basket))
			return;
		$publicMode = self::usedByClient();

		switch (Sale\Discount::getApplyMode())
		{
			case Sale\Discount::APPLY_MODE_DISABLE:
			case Sale\Discount::APPLY_MODE_FULL_DISABLE:
				foreach ($basket as $basketCode => $basketItem)
				{
					$code = ($publicMode ? $basketItem['ID'] : $basketCode);
					if (isset(self::$basketDiscountList[$code]) && !empty(self::$basketDiscountList[$code]))
						$basket[$basketCode]['LAST_DISCOUNT'] = 'Y';
					unset($code);
				}
				unset($basketCode, $basketItem);
				break;
			case Sale\Discount::APPLY_MODE_LAST:
			case Sale\Discount::APPLY_MODE_FULL_LAST:
				foreach ($basket as $basketCode => $basketItem)
				{
					$code = ($publicMode ? $basketItem['ID'] : $basketCode);
					if (!isset(self::$basketDiscountList[$code]) || empty(self::$basketDiscountList[$code]))
						continue;
					$lastDiscount = end(self::$basketDiscountList[$code]);
					if (!empty($lastDiscount['LAST_DISCOUNT']) && $lastDiscount['LAST_DISCOUNT'] == 'Y')
						$basket[$basketCode]['LAST_DISCOUNT'] = 'Y';
				}
				unset($basketCode, $basketItem);
				break;
			case Sale\Discount::APPLY_MODE_ADD:
				break;
		}
	}

	/**
	 * Push to stack current order data.
	 *
	 * @param array $order				Current order data.
	 * @return void
	 */
	public static function setOrderData($order)
	{
		if (array_key_exists('DISCOUNT_DESCR', $order))
			unset($order['DISCOUNT_DESCR']);
		if (array_key_exists('DISCOUNT_RESULT', $order))
			unset($order['DISCOUNT_RESULT']);
		self::$previousOrderData = $order;
	}

	/**
	 * Save result discount list from CSaleDiscount::DoProcessOrder.
	 *
	 * @param array $discountList		Result from CSaleDiscount::DoProcessOrder.
	 * @return void
	 */
	public static function setOldDiscountResult($discountList)
	{
		self::$compatibleSaleDiscountResult = $discountList;
	}

	/**
	 * Return result discount list in old format. Compatibility only.
	 *
	 * @return array
	 */
	public static function getOldDiscountResult()
	{
		return self::$compatibleSaleDiscountResult;
	}

	/**
	 * Save sale discount.
	 *
	 * @param array &$order				Current order data.
	 * @param array $discount			Discount data.
	 * @return bool
	 */
	public static function calculateSaleDiscount(&$order, $discount)
	{
		if (!self::$init)
			self::init();
		if (!self::isSuccess())
			return false;

		$stepResult = Sale\Discount\Actions::getActionResult();
		if (!empty($stepResult) && is_array($stepResult))
		{
			$order['DISCOUNT_RESULT'] = $stepResult;
			$order['DISCOUNT_DESCR'] = Sale\Discount\Actions::getActionDescription();
			$stepResult = self::getStepResult($order);
		}
		else
		{
			$stepResult = self::getStepResultOld($order);
		}
		Sale\Discount\Actions::fillCompatibleFields($order);
		$applied = !empty($stepResult);

		$orderDiscountId = 0;
		$orderCouponId = '';

		if ($applied)
		{
			self::correctStepResult($order, $stepResult, $discount);

			if (!empty($order['DISCOUNT_DESCR']) && is_array($order['DISCOUNT_DESCR']))
				$discount['ACTIONS_DESCR'] = $order['DISCOUNT_DESCR'];
			$discountResult = self::convertDiscount($discount);
			if (!$discountResult->isSuccess())
				return false;

			$orderDiscountId = $discountResult->getId();
			$discountData = $discountResult->getData();
			$discount['ORDER_DISCOUNT_ID'] = $orderDiscountId;

			if ($discountData['USE_COUPONS'] == 'Y')
			{
				if (empty($discount['COUPON']))
					return false;

				$couponResult = self::convertCoupon($discount['COUPON']['COUPON'], $orderDiscountId);
				if (!$couponResult->isSuccess())
					return false;
				$orderCouponId = $couponResult->getId();
				Sale\DiscountCouponsManager::setApply($orderCouponId, $stepResult);
				unset($couponResult);
			}
		}

		if (array_key_exists('DISCOUNT_DESCR', $order))
			unset($order['DISCOUNT_DESCR']);
		if (array_key_exists('DISCOUNT_RESULT', $order))
			unset($order['DISCOUNT_RESULT']);
		self::$currentOrderData = $order;
		if ($applied && $orderCouponId != '')
		{
			$couponApply = Sale\DiscountCouponsManager::setApply(self::$couponsCache[$orderCouponId]['COUPON'], $stepResult);
			unset($couponApply);
		}

		if ($applied)
		{
			self::$discountResult['ORDER'][] = array(
				'DISCOUNT_ID' => $orderDiscountId,
				'COUPON_ID' => $orderCouponId,
				'RESULT' => $stepResult
			);
			return true;
		}

		return false;
	}

	/**
	 * Return discount list description.
	 *
	 * @param bool $extMode			Extended mode.
	 * @return array
	 */
	public static function getApplyResult($extMode = false)
	{
		$extMode = ($extMode === true);

		self::getApplyDiscounts();
		if ($extMode)
			self::remakingDiscountResult();

		$result = self::$discountResult;
		if ($extMode)
			unset($result['BASKET'], $result['ORDER']);
		//$result['CONVERTED_ORDER'] = 'Y';
		return $result;
	}

	/**
	 * Set basket code.
	 *
	 * @param string|int $index				Item index.
	 * @param string|int $code				Basket code.
	 * @return void
	 */
	public static function setBasketCode($index, $code)
	{
		self::$basketCodes[$index] = $code;
	}

	/**
	 * Return discount result for old api.
	 *
	 * @return array|bool
	 */
	public static function getResult()
	{
		if (!self::$init || self::$useMode == self::MODE_SYSTEM)
			return false;
		if (self::$useMode == self::MODE_ORDER)
			return array();
		if (self::$useMode == self::MODE_DISABLED)
			return array();
		$result = array(
			'CALCULATE' => array(
				'USE_MODE' => self::$discountUseMode,
				'NEW_ORDER' => self::$order === null,
			),
			'BASE_PRICE' => array(),
			'DISCOUNT_LIST' => array(),
			'COUPONS_LIST' => array(),
			'DISCOUNT_RESULT' => array(),
			'FORWARD_BASKET_TABLE' => array(),
			'REVERSE_BASKET_TABLE' => array()
		);

		if (!empty(self::$basketBasePrice))
		{
			foreach (self::$basketBasePrice as $index => $price)
			{
				if (!isset(self::$basketCodes[$index]))
					continue;
				$result['BASE_PRICE'][self::$basketCodes[$index]] = $price;
			}
			unset($index, $price);
		}

		if (!empty(self::$discountsCache))
			$result['DISCOUNT_LIST'] = self::$discountsCache;

		if (!empty(self::$couponsCache))
			$result['COUPONS_LIST'] = self::$couponsCache;

		if (
			!empty(self::$discountResult['BASKET'])
			|| !empty(self::$discountResult['ORDER'])
			|| !empty(self::$discountResult['BASKET_ROUND'])
		)
		{
			$result['DISCOUNT_RESULT']['APPLY_BLOCKS'] = array(
				0 => array(
					'BASKET' => array(),
					'BASKET_ROUND' => array(),
					'ORDER' => array()
				)
			);
		}

		if (!empty(self::$discountResult['BASKET']))
		{
			foreach (self::$discountResult['BASKET'] as $index => $discountList)
			{
				if (!isset(self::$basketCodes[$index]))
					continue;
				$result['DISCOUNT_RESULT']['APPLY_BLOCKS'][0]['BASKET'][self::$basketCodes[$index]] = $discountList;
			}
			unset($index, $discountList);
		}

		if (!empty(self::$discountResult['BASKET_ROUND']))
		{
			foreach (self::$discountResult['BASKET_ROUND'] as $index => $roundData)
			{
				if (!isset(self::$basketCodes[$index]))
					continue;
				$result['DISCOUNT_RESULT']['APPLY_BLOCKS'][0]['BASKET_ROUND'][self::$basketCodes[$index]] = $roundData;
			}
			unset($index, $roundData);
		}

		if (!empty(self::$discountResult['ORDER']))
		{
			foreach (self::$discountResult['ORDER'] as $discountIndex => $discount)
			{
				if (!empty($discount['RESULT']['BASKET']))
				{
					$newBasket = array();
					foreach ($discount['RESULT']['BASKET'] as $index => $basketItem)
					{
						if (!isset(self::$basketCodes[$index]))
							continue;
						$basketItem['BASKET_ID'] = self::$basketCodes[$index];
						$newBasket[self::$basketCodes[$index]] = $basketItem;
					}
					unset($index, $basketItem);
					$discount['RESULT']['BASKET'] = $newBasket;
				}
				$result['DISCOUNT_RESULT']['APPLY_BLOCKS'][0]['ORDER'][$discountIndex] = $discount;
			}
			unset($discountIndex, $discount);
		}

		if (!empty(self::$basketCodes))
		{
			foreach (self::$basketCodes as $code => $id)
			{
				$result['FORWARD_BASKET_TABLE'][$code] = $id;
				$result['REVERSE_BASKET_TABLE'][$id] = $code;
			}
			unset($code, $id);
		}

		return $result;
	}

	/**
	 * Round prices.
	 *
	 * @param array &$basket	Basket items.
	 * @return void
	 */
	public static function roundPrices(array &$basket)
	{
		if (empty($basket))
			return;

		$publicMode = self::usedByClient();
		foreach ($basket as $basketCode => $basketItem)
		{
			if (\CSaleBasketHelper::isSetItem($basketItem))
				continue;
			$code = ($publicMode ? $basketItem['ID'] : $basketCode);
			$basketItem['MODULE_ID'] = $basketItem['MODULE'];
			$roundResult = Sale\OrderDiscountManager::roundPrice(
				$basketItem,
				array()
			);
			if (empty($roundResult) || !is_array($roundResult))
				continue;

			$basket[$basketCode]['PRICE'] = $roundResult['PRICE'];
			$basket[$basketCode]['DISCOUNT_PRICE'] = $roundResult['DISCOUNT_PRICE'];

			if (!isset(self::$discountResult['BASKET_ROUND']))
				self::$discountResult['BASKET_ROUND'] = array();
			self::$discountResult['BASKET_ROUND'][$code] = array(
				'APPLY' => 'Y',
				'ROUND_RULE' => $roundResult['ROUND_RULE']
			);
			unset($roundResult);
		}
		unset($basketCode, $basketItem);
	}

	/**
	 * Returns existing custom price.
	 *
	 * @param array $basketItem			Basket item.
	 * @return bool
	 */
	protected static function isCustomPrice($basketItem)
	{
		return (isset($basketItem['CUSTOM_PRICE']) && $basketItem['CUSTOM_PRICE'] == 'Y');
	}

	/**
	 * Calculate basket discounts for item.
	 *
	 * @param string|int $code						Basket code.
	 * @param array $fields							Basket data.
	 * @return bool|void
	 */
	protected static function calculateBasketItemDiscount($code, $fields)
	{
		if (\CSaleBasketHelper::isSetItem($fields))
			return true;
		if (empty(self::$basketDiscountList[$code]))
			return true;

		$itemData = array(
			'MODULE_ID' => $fields['MODULE'],
			'PRODUCT_ID' => $fields['PRODUCT_ID'],
			'BASKET_ID' => $code
		);
		foreach (self::$basketDiscountList[$code] as $index => $discount)
		{
			$discountResult = self::convertDiscount($discount);
			if (!$discountResult->isSuccess())
				return false;

			$orderDiscountId = $discountResult->getId();
			$discountData = $discountResult->getData();
			$orderCouponId = '';
			self::$basketDiscountList[$code][$index]['ORDER_DISCOUNT_ID'] = $orderDiscountId;
			if ($discountData['USE_COUPONS'] == 'Y')
			{
				if (empty($discount['COUPON']))
					return false;

				$couponResult = self::convertCoupon($discount['COUPON'], $orderDiscountId);
				if (!$couponResult->isSuccess())
					return false;

				$orderCouponId = $couponResult->getId();
				Sale\DiscountCouponsManager::setApplyByProduct($itemData, array($orderCouponId));
				unset($couponResult);
			}
			unset($discountData, $discountResult);
			if (!isset(self::$discountResult['BASKET'][$code]))
				self::$discountResult['BASKET'][$code] = array();
			self::$discountResult['BASKET'][$code][$index] = array(
				'DISCOUNT_ID' => $orderDiscountId,
				'COUPON_ID' => $orderCouponId,
				'RESULT' => array(
					'APPLY' => 'Y',
					'DESCR' => false,
					'DESCR_DATA' => false
				)
			);

			$orderApplication = (
			!empty(self::$discountsCache[$orderDiscountId]['APPLICATION'])
				? self::$discountsCache[$orderDiscountId]['APPLICATION']
				: null
			);
			if (!empty($orderApplication))
			{
				$fields['DISCOUNT_RESULT'] = (
					!empty(self::$discountsCache[$orderDiscountId]['ACTIONS_DESCR_DATA'])
					? self::$discountsCache[$orderDiscountId]['ACTIONS_DESCR_DATA']
					: false
				);

				$applyProduct = null;
				eval('$applyProduct='.$orderApplication.';');
				if (is_callable($applyProduct))
					$applyProduct($fields);
				unset($applyProduct);

				if (!empty($fields['DISCOUNT_RESULT']))
				{
					self::$discountResult['BASKET'][$code][$index]['RESULT']['DESCR_DATA'] = $fields['DISCOUNT_RESULT']['BASKET'];
					self::$discountResult['BASKET'][$code][$index]['RESULT']['DESCR'] = self::formatDescription($fields['DISCOUNT_RESULT']);
				}
				unset($fields['DISCOUNT_RESULT']);
			}
			unset($orderApplication);
		}
		unset($discount, $index);

		return true;
	}

	/**
	 * Convert discount for saving in order.
	 *
	 * @param array $discount			Raw discount data.
	 * @return Sale\Result
	 */
	protected static function convertDiscount($discount)
	{
		$result = new Sale\Result();

		$discountResult = Sale\OrderDiscountManager::saveDiscount($discount, false);
		if (!$discountResult->isSuccess())
		{
			$result->addErrors($discountResult->getErrors());
			unset($discountResult);
			return $result;
		}
		$orderDiscountId = $discountResult->getId();
		$discountData = $discountResult->getData();
		$resultData = array(
			'ORDER_DISCOUNT_ID' => $orderDiscountId,
			'USE_COUPONS' => $discountData['USE_COUPONS'],
			'MODULE_ID' => $discountData['MODULE_ID'],
		);
		if (!isset(self::$discountsCache[$orderDiscountId]))
		{
			$discountData['ACTIONS_DESCR_DATA'] = false;
			if (!empty($discountData['ACTIONS_DESCR']) && is_array($discountData['ACTIONS_DESCR']))
			{
				$discountData['ACTIONS_DESCR_DATA'] = $discountData['ACTIONS_DESCR'];
				$discountData['ACTIONS_DESCR'] = self::formatDescription($discountData['ACTIONS_DESCR']);
			}
			else
			{
				$discountData['ACTIONS_DESCR'] = false;
			}
			if (empty($discountData['ACTIONS_DESCR']))
			{
				$discountData['ACTIONS_DESCR'] = false;
				$discountData['ACTIONS_DESCR_DATA'] = false;
			}
			self::$discountsCache[$orderDiscountId] = $discountData;
		}

		$result->setId($orderDiscountId);
		$result->setData($resultData);
		unset($discountData, $resultData, $orderDiscountId);

		return $result;
	}

	/**
	 * Convert coupon for saving in order.
	 *
	 * @param string|array $coupon			Coupon.
	 * @param int $discount					Order discount id.
	 * @return Sale\Result
	 */
	protected static function convertCoupon($coupon, $discount)
	{
		$result = new Sale\Result();

		if (!is_array($coupon))
		{
			$couponData = Sale\DiscountCouponsManager::getEnteredCoupon($coupon, true);
			if (empty($couponData))
			{
				$result->addError(new Main\Entity\EntityError(
					Loc::getMessage('BX_SALE_DISCOUNT_ERR_COUPON_NOT_FOUND'),
					self::ERROR_ID
				));
				return $result;
			}
			$coupon = array(
				'COUPON' => $couponData['COUPON'],
				'TYPE' => $couponData['TYPE'],
				'COUPON_ID' => $couponData['ID'],
				'DATA' => $couponData
			);
			unset($couponData);
		}
		$coupon['ORDER_DISCOUNT_ID'] = $discount;
		$coupon['ID'] = 0;

		$orderCouponId = $coupon['COUPON'];
		if (!isset(self::$couponsCache[$orderCouponId]))
			self::$couponsCache[$orderCouponId] = $coupon;
		$result->setId($orderCouponId);
		$result->setData($coupon);
		unset($coupon, $orderCouponId);
		return $result;
	}

	/**
	 * Return formatted discount description.
	 *
	 * @param array|bool $descr				Description.
	 * @return array
	 */
	protected static function formatDescription($descr)
	{
		$result = array();
		if (empty($descr) || !is_array($descr))
			return $result;
		if (isset($descr['DELIVERY']))
		{
			$result['DELIVERY'] = array();
			foreach ($descr['DELIVERY'] as $index => $value)
			{
				$result['DELIVERY'][$index] = Sale\OrderDiscountManager::formatDescription($value);
				if ($result['DELIVERY'][$index] == false)
					unset($result['DELIVERY'][$index]);
			}
			unset($value, $index);
			if (!empty($result['DELIVERY']))
				$result['DELIVERY'] = implode(', ', $result['DELIVERY']);
		}
		if (isset($descr['BASKET']))
		{
			$result['BASKET'] = array();
			foreach ($descr['BASKET'] as $index => $value)
			{
				$result['BASKET'][$index] = Sale\OrderDiscountManager::formatDescription($value);
				if ($result['BASKET'][$index] == false)
					unset($result['BASKET'][$index]);
			}
			unset($value, $index);
			if (!empty($result['BASKET']))
				$result['BASKET'] = implode(', ', $result['BASKET']);
		}
		return $result;
	}

	/**
	 * Returns result after one discount.
	 *
	 * @param array $order			Order current data.
	 * @return array
	 */
	protected static function getStepResult($order)
	{
		$publicMode = self::usedByClient();
		$result = array();
		$stepResult = &$order['DISCOUNT_RESULT'];
		if (!empty($stepResult['DELIVERY']) && is_array($stepResult['DELIVERY']))
		{
			$result['DELIVERY'] = array(
				'APPLY' => 'Y',
				'DELIVERY_ID' => (isset($order['DELIVERY_ID']) ? $order['DELIVERY_ID'] : false),
				'SHIPMENT_CODE' => (isset($order['SHIPMENT_CODE']) ? $order['SHIPMENT_CODE'] : false),
				'DESCR' => Sale\OrderDiscountManager::formatArrayDescription($stepResult['DELIVERY']),
				'DESCR_DATA' => $stepResult['DELIVERY'],
				'ACTION_BLOCK_LIST' => array_keys($stepResult['DELIVERY'])
			);
			if (is_array($result['DELIVERY']['DESCR']))
				$result['DELIVERY']['DESCR'] = implode(', ', $result['DELIVERY']['DESCR']);
		}
		if (!empty($stepResult['BASKET']) && is_array($stepResult['BASKET']))
		{
			if (!isset($result['BASKET']))
				$result['BASKET'] = array();
			foreach ($stepResult['BASKET'] as $basketCode => $basketResult)
			{
				$code = ($publicMode ? $order['BASKET_ITEMS'][$basketCode]['ID'] : $basketCode);
				$result['BASKET'][$code] = array(
					'APPLY' => 'Y',
					'DESCR' => Sale\OrderDiscountManager::formatArrayDescription($basketResult),
					'DESCR_DATA' => $basketResult,
					'MODULE' => $order['BASKET_ITEMS'][$basketCode]['MODULE'],
					'PRODUCT_ID' => $order['BASKET_ITEMS'][$basketCode]['PRODUCT_ID'],
					'BASKET_ID' => $code,
					'ACTION_BLOCK_LIST' => array_keys($basketResult)
				);
				if (is_array($result['BASKET'][$basketCode]['DESCR']))
					$result['BASKET'][$basketCode]['DESCR'] = implode(', ', $result['BASKET'][$basketCode]['DESCR']);
			}
			unset($basketCode, $basketResult);
		}
		unset($stepResult);

		return $result;
	}

	/**
	 * Returns result after one discount in old format.
	 *
	 * @param array $currentOrder			Current order data.
	 * @return array
	 */
	protected static function getStepResultOld($currentOrder)
	{
		$publicMode = self::usedByClient();
		$result = array();
		if (isset(self::$previousOrderData['PRICE_DELIVERY']) && isset($currentOrder['PRICE_DELIVERY']))
		{
			if (self::$previousOrderData['PRICE_DELIVERY'] != $currentOrder['PRICE_DELIVERY'])
			{
				$descr = Sale\OrderDiscountManager::createSimpleDescription($currentOrder['PRICE_DELIVERY'], self::$previousOrderData['PRICE_DELIVERY'], self::$previousOrderData['CURRENCY']);
				$result['DELIVERY'] = array(
					'APPLY' => 'Y',
					'DELIVERY_ID' => (isset($currentOrder['DELIVERY_ID']) ? $currentOrder['DELIVERY_ID'] : false),
					'SHIPMENT_CODE' => (isset($order['SHIPMENT_CODE']) ? $order['SHIPMENT_CODE'] : false),
					'DESCR' => Sale\OrderDiscountManager::formatArrayDescription($descr),
					'DESCR_DATA' => $descr
				);
				unset($descr);
				if (is_array($result['DELIVERY']['DESCR']))
					$result['DELIVERY']['DESCR'] = implode(', ', $result['DELIVERY']['DESCR']);
			}
		}
		if (!empty(self::$previousOrderData['BASKET_ITEMS']) && !empty($currentOrder['BASKET_ITEMS']))
		{
			foreach (self::$previousOrderData['BASKET_ITEMS'] as $basketCode => $item)
			{
				if (!isset($currentOrder['BASKET_ITEMS'][$basketCode]))
					continue;
				$code = ($publicMode ? $currentOrder['BASKET_ITEMS'][$basketCode]['ID'] : $basketCode);
				if ($item['PRICE'] != $currentOrder['BASKET_ITEMS'][$basketCode]['PRICE'])
				{
					if (!isset($result['BASKET']))
						$result['BASKET'] = array();
					$descr = Sale\OrderDiscountManager::createSimpleDescription($currentOrder['BASKET_ITEMS'][$basketCode]['PRICE'], $item['PRICE'], self::$previousOrderData['CURRENCY']);
					$result['BASKET'][$code] = array(
						'APPLY' => 'Y',
						'DESCR' => Sale\OrderDiscountManager::formatArrayDescription($descr),
						'DESCR_DATA' => $descr,
						'MODULE' => $currentOrder['BASKET_ITEMS'][$basketCode]['MODULE'],
						'PRODUCT_ID' => $currentOrder['BASKET_ITEMS'][$basketCode]['PRODUCT_ID'],
						'BASKET_ID' => $code
					);
					unset($descr);
					if (is_array($result['BASKET'][$basketCode]['DESCR']))
						$result['BASKET'][$basketCode]['DESCR'] = implode(', ', $result['BASKET'][$basketCode]['DESCR']);
				}
			}
		}
		return $result;
	}

	/**
	 * Correct data for exotic coupon.
	 *
	 * @param array &$order					Current order data.
	 * @param array &$stepResult			Currenct discount result.
	 * @param array $discount				Discount data.
	 * @return void
	 */
	protected static function correctStepResult(&$order, &$stepResult, $discount)
	{
		if ($discount['USE_COUPONS'] == 'Y' && !empty($discount['COUPON']))
		{
			if (
				$discount['COUPON']['TYPE'] == Sale\Internals\DiscountCouponTable::TYPE_BASKET_ROW &&
				(!empty($stepResult['BASKET']) && count($stepResult['BASKET']) > 1)
			)
			{
				$publicMode = self::usedByClient();
				$maxPrice = 0;
				$maxKey = -1;
				$basketId = -1;
				foreach (self::$previousOrderData['BASKET_ITEMS'] as $key => $item)
				{
					if ($maxPrice < $item['PRICE'])
					{
						$maxPrice = $item['PRICE'];
						$maxKey = $key;
						$basketId = ($publicMode ? $item['ID'] : $key);
					}
				}
				unset($key, $item);
				$basketKeys = array_keys($order['BASKET_ITEMS']);
				foreach ($basketKeys as &$key)
				{
					if ($key == $maxKey)
						continue;
					$order['BASKET_ITEMS'][$key] = self::$previousOrderData['BASKET_ITEMS'][$key];
				}
				unset($key);
				$basketKeys = array_keys($stepResult['BASKET']);
				foreach ($basketKeys as &$key)
				{
					if ($key == $basketId)
						continue;
					unset($stepResult['BASKET'][$key]);
				}
				unset($key);
				unset($basketKeys);
				unset($basketId, $maxKey, $maxPrice, $publicMode);
			}
		}
	}

	/**
	 * Returns discount and coupon list.
	 *
	 * @return void
	 */
	protected static function getApplyDiscounts()
	{
		$discountApply = array();
		$couponApply = array();

		self::$discountResult['DISCOUNT_LIST'] = array();
		if (!empty(self::$discountsCache))
		{
			foreach (self::$discountsCache as $id => $discount)
			{
				self::$discountResult['DISCOUNT_LIST'][$id] = array(
					'ID' => $id,
					'NAME' => $discount['NAME'],
					'MODULE_ID' => $discount['MODULE_ID'],
					'DISCOUNT_ID' => $discount['ID'],
					'USE_COUPONS' => $discount['USE_COUPONS'],
					'ACTIONS_DESCR' => $discount['ACTIONS_DESCR'],
					'ACTIONS_DESCR_DATA' => $discount['ACTIONS_DESCR_DATA'],
					'APPLY' => 'N',
					'EDIT_PAGE_URL' => $discount['EDIT_PAGE_URL']
				);
				$discountApply[$id] = &self::$discountResult['DISCOUNT_LIST'][$id];
			}
			unset($id, $discount);
		}

		self::$discountResult['COUPON_LIST'] = array();
		if (!empty(self::$couponsCache))
		{
			foreach (self::$couponsCache as $id => $coupon)
			{
				self::$discountResult['COUPON_LIST'][$id] = $coupon;
				self::$discountResult['COUPON_LIST'][$id]['APPLY'] = 'N';
				$couponApply[$id] = &self::$discountResult['COUPON_LIST'][$id];
			}
			unset($id, $coupon);
		}

		if (!empty(self::$discountResult['BASKET']))
		{
			foreach (self::$discountResult['BASKET'] as $basketCode => $discountList)
			{
				foreach ($discountList as $discount)
				{
					if ($discount['RESULT']['APPLY'] == 'Y')
					{
						if (isset($discountApply[$discount['DISCOUNT_ID']]))
							$discountApply[$discount['DISCOUNT_ID']]['APPLY'] = 'Y';
						if (isset($couponApply[$discount['COUPON_ID']]))
							$couponApply[$discount['COUPON_ID']]['APPLY'] = 'Y';
					}
				}
				unset($discount);
			}
			unset($basketCode, $discountList);
		}

		if (!empty(self::$discountResult['ORDER']))
		{
			foreach (self::$discountResult['ORDER'] as $discount)
			{
				if (!empty($discount['RESULT']['BASKET']))
				{
					foreach ($discount['RESULT']['BASKET'] as $basketCode => $applyList)
					{
						if ($applyList['APPLY'] == 'Y')
						{
							if (isset($discountApply[$discount['DISCOUNT_ID']]))
								$discountApply[$discount['DISCOUNT_ID']]['APPLY'] = 'Y';
							if (isset($couponApply[$discount['COUPON_ID']]))
								$couponApply[$discount['COUPON_ID']]['APPLY'] = 'Y';
						}
					}
					unset($basketCode, $applyList);
				}
				if (!empty($discount['RESULT']['DELIVERY']) && $discount['RESULT']['DELIVERY']['APPLY'] == 'Y')
				{
					if (isset($discountApply[$discount['DISCOUNT_ID']]))
						$discountApply[$discount['DISCOUNT_ID']]['APPLY'] = 'Y';
					if (isset($couponApply[$discount['COUPON_ID']]))
						$couponApply[$discount['COUPON_ID']]['APPLY'] = 'Y';
				}
			}
			unset($discount);
		}
		unset($discountApply, $couponApply);
	}

	/**
	 * Change result format.
	 *
	 * @return void
	 */
	protected static function remakingDiscountResult()
	{
		$basket = array();
		$delivery = array();

		if (!empty(self::$discountResult['BASKET']))
		{
			foreach (self::$discountResult['BASKET'] as $basketCode => $discountList)
			{
				if (!isset($basket[$basketCode]))
					$basket[$basketCode] = array();
				foreach ($discountList as $discount)
				{
					$basket[$basketCode][] = array(
						'DISCOUNT_ID' => $discount['DISCOUNT_ID'],
						'COUPON_ID' => $discount['COUPON_ID'],
						'APPLY' => $discount['RESULT']['APPLY'],
						'DESCR' => $discount['RESULT']['DESCR']
					);
				}
				unset($discount);
			}
			unset($basketCode, $discountList);
		}

		if (!empty(self::$discountResult['ORDER']))
		{
			foreach (self::$discountResult['ORDER'] as $discount)
			{
				if (!empty($discount['RESULT']['BASKET']))
				{
					foreach ($discount['RESULT']['BASKET'] as $basketCode => $applyList)
					{
						if (!isset($basket[$basketCode]))
							$basket[$basketCode] = array();
						$basket[$basketCode][] = array(
							'DISCOUNT_ID' => $discount['DISCOUNT_ID'],
							'COUPON_ID' => $discount['COUPON_ID'],
							'APPLY' => $applyList['APPLY'],
							'DESCR' => $applyList['DESCR']
						);
					}
					unset($basketCode, $applyList);
				}
				if (!empty($discount['RESULT']['DELIVERY']))
				{
					$delivery[] = array(
						'DISCOUNT_ID' => $discount['DISCOUNT_ID'],
						'COUPON_ID' => $discount['COUPON_ID'],
						'DELIVERY_ID' => $discount['RESULT']['DELIVERY']['DELIVERY_ID'],
						'APPLY' => $discount['RESULT']['DELIVERY']['APPLY'],
						'DESCR' => $discount['RESULT']['DELIVERY']['DESCR']
					);
				}
			}
			unset($discount);
		}

		unset($couponApply, $discountApply);
		self::$discountResult['RESULT'] = array(
			'BASKET' => $basket,
			'DELIVERY' => $delivery
		);
	}
}