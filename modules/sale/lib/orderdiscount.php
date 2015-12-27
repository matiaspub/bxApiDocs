<?php
namespace Bitrix\Sale;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals;
use Bitrix\Catalog;

Loc::loadMessages(__FILE__);

class OrderDiscountManager
{
	const EVENT_ON_BUILD_DISCOUNT_PROVIDERS = 'onBuildDiscountProviders';

	const ERROR_ID = 'BX_SALE_ORDERDISCOUNT';

	const DESCR_TYPE_SIMPLE = 0x0001;
	const DESCR_TYPE_VALUE = 0x0002;
	const DESCR_TYPE_LIMIT_VALUE = 0x0004;
	const DESCR_TYPE_FIXED = 0x0008;
	const DESCR_TYPE_MAX_BOUND = 0x0010;

	const DESCR_VALUE_TYPE_PERCENT = 'P';
	const DESCR_VALUE_TYPE_CURRENCY = 'C';
	const DESCR_VALUE_TYPE_SUMM = 'S';
	const DESCR_VALUE_TYPE_SUMM_BASKET = 'B';

	const DESCR_VALUE_ACTION_DISCOUNT = 'D';
	const DESCR_VALUE_ACTION_EXTRA = 'E';
	const DESCR_VALUE_ACTION_ACCUMULATE = 'A';

	const DESCR_LIMIT_MAX = 'MAX';
	const DESCR_LIMIT_MIN = 'MIN';

	protected static $init = false;
	protected static $errors = array();
	protected static $discountProviders = array();
	protected static $managerConfig = array();

	protected static $discountCache = array();

	protected static $migrateDiscountsCache = array();
	protected static $migrateCouponsCache = array();
	protected static $catalogIncluded = null;
	protected static $catalogDiscountsCache = array();

	/**
	 * Initial discount manager.
	 *
	 * @return void
	 */
	public static function init()
	{
		if (self::$init === false)
			self::initDiscountProviders();
		self::$init = true;
	}

	/**
	 * Set manager params.
	 *
	 * @param array $config			Manager params (site, currency, etc).
	 * @return bool
	 */
	public static function setManagerConfig($config)
	{
		if (empty($config) || empty($config['SITE_ID']))
			return false;
		if (empty($config['CURRENCY']))
			$config['CURRENCY'] = Internals\SiteCurrencyTable::getSiteCurrency($config['SITE_ID']);
		if (!isset($config['USE_BASE_PRICE']) || ($config['USE_BASE_PRICE'] != 'Y' && $config['USE_BASE_PRICE'] != 'N'))
			$config['USE_BASE_PRICE'] = ((string)Main\Config\Option::get('sale', 'get_discount_percent_from_base_price') == 'Y' ? 'Y' : 'N');
		if (empty($config['BASKET_ITEM']))
			$config['BASKET_ITEM'] = '$basketItem';
		self::$managerConfig = $config;
		return true;
	}

	/**
	 * Resturn current manager params.
	 *
	 * @return array
	 */
	public static function getManagerConfig()
	{
		return self::$managerConfig;
	}

	/**
	 * Convert and save discount.
	 *
	 * @param array $discount			Discount data.
	 * @param bool $extResult			Result extended result data.
	 * @return Result
	 */
	public static function saveDiscount($discount, $extResult = false)
	{
		if (self::$init === false)
			self::init();
		$result = new Result();

		$extResult = ($extResult === true);

		$process = true;

		$internal = null;
		$discountData = false;
		$fields = false;
		$hash = false;
		$emptyData = array(
			'ID' => 0,
			'NAME' => '',
			'ORDER_DISCOUNT_ID' => 0,
			'ORDER_COUPON_ID' => 0,
			'USE_COUPONS' => '',
			'LAST_DISCOUNT' => '',
			'MODULE_ID' => '',
			'EDIT_PAGE_URL' => '',
			'ACTIONS_DESCR' => array()
		);
		if ($extResult)
		{
			$emptyData['RAW_DATA'] = array();
			$emptyData['PREPARED_DATA'] = array();
		}
		$resultData = $emptyData;

		if (empty(self::$managerConfig))
		{
			$process = false;
			$result->addError(new Main\Entity\EntityError(
				Loc::getMessage('SALE_ORDER_DISCOUNT_ERR_EMPTY_MANAGER_PARAMS'),
				self::ERROR_ID
			));
		}

		if (empty($discount) || empty($discount['MODULE_ID']))
		{
			$process = false;
			$result->addError(new Main\Entity\EntityError(
				Loc::getMessage('SALE_ORDER_DISCOUNT_ERR_EMPTY_DISCOUNT'),
				self::ERROR_ID
			));
		}

		if ($process)
		{
			$internal = $discount['MODULE_ID'] == 'sale';
			if (!$internal)
			{
				if (!isset(self::$discountProviders[$discount['MODULE_ID']]))
				{
					$process = false;
					$result->addError(new Main\Entity\EntityError(
						Loc::getMessage('SALE_ORDER_DISCOUNT_ERR_BAD_DISCOUNT_MODULE'),
						self::ERROR_ID
					));
				}
				else
				{
					$discountData = self::executeDiscountProvider($discount['MODULE_ID'], $discount);
				}
			}
			else
			{
				$discountData = self::prepareData($discount);
			}
			if (empty($discountData) || !is_array($discountData))
			{
				$process = false;
				$result->addError(new Main\Entity\EntityError(
					Loc::getMessage('SALE_ORDER_DISCOUNT_ERR_BAD_PREPARE_DISCOUNT'),
					self::ERROR_ID
				));
			}
		}

		if ($process)
		{
			$fields = Internals\OrderDiscountTable::prepareDiscountData($discountData);
			if (empty($fields) || !is_array($fields))
			{
				$process = false;
				$result->addError(new Main\Entity\EntityError(
					Loc::getMessage('SALE_ORDER_DISCOUNT_ERR_BAD_PREPARE_DISCOUNT'),
					self::ERROR_ID
				));
			}
		}

		if ($process)
		{
			$hash = Internals\OrderDiscountTable::calculateHash($fields);
			if ($hash === false)
			{
				$process = false;
				$result->addError(new Main\Entity\EntityError(
					Loc::getMessage('SALE_ORDER_DISCOUNT_ERR_BAD_DISCOUNT_HASH'),
					self::ERROR_ID
				));
			}
		}

		if ($process)
		{
			if (!isset(self::$discountCache[$hash]))
			{
				$orderDiscountIterator = Internals\OrderDiscountTable::getList(array(
					'select' => array('*'),
					'filter' => array('=DISCOUNT_HASH' => $hash)
				));
				if ($orderDiscount = $orderDiscountIterator->fetch())
					self::$discountCache[$hash] = $orderDiscount;
				unset($orderDiscount, $orderDiscountIterator);
			}
			if (!empty(self::$discountCache[$hash]))
			{
				$resultData = self::$discountCache[$hash];
				$resultData['ID'] = (int)$resultData['ID'];
				$resultData['NAME'] = (string)$resultData['NAME'];
				$resultData['ORDER_DISCOUNT_ID'] = $resultData['ID'];
				$result->setId($resultData['ID']);
			}
			else
			{
				$fields['DISCOUNT_HASH'] = $hash;
				$fields['ACTIONS_DESCR'] = array();
				if (isset($discountData['ACTIONS_DESCR']))
					$fields['ACTIONS_DESCR'] = $discountData['ACTIONS_DESCR'];
				$tableResult = Internals\OrderDiscountTable::add($fields);
				if ($tableResult->isSuccess())
				{
					$resultData = $fields;
					$resultData['ID'] = (int)$tableResult->getId();
					$resultData['NAME'] = (string)$resultData['NAME'];
					$resultData['ORDER_DISCOUNT_ID'] = $resultData['ID'];
					$result->setId($resultData['ID']);
				}
				else
				{
					$process = false;
					$result->addErrors($tableResult->getErrors());
				}
				unset($tableResult, $fields);

				if ($process)
				{
					$moduleList = Internals\OrderDiscountTable::getDiscountModules($discountData);
					if (!empty($moduleList))
					{
						$resultModule = Internals\OrderModulesTable::saveOrderDiscountModules($resultData['ORDER_DISCOUNT_ID'], $moduleList);
						if (!$resultModule)
						{
							Internals\OrderDiscountTable::clearList($resultData['ORDER_DISCOUNT_ID']);
							$resultData = $emptyData;
							$process = false;
							$result->addError(new Main\Entity\EntityError(
								Loc::getMessage('SALE_ORDER_DISCOUNT_ERR_SAVE_DISCOUNT_MODULES'),
								self::ERROR_ID
							));
						}
						unset($resultModule);
					}
					unset($needDiscountModules, $moduleList);
				}
			}
		}

		if ($process)
		{
			$resultData['EDIT_PAGE_URL'] = $discountData['EDIT_PAGE_URL'];
			if ($extResult)
			{
				$resultData['RAW_DATA'] = $discount;
				$resultData['PREPARED_DATA'] = $discountData;
			}
			$result->setData($resultData);
		}
		unset($resultData, $process);

		return $result;
	}

	/**
	 * Save coupon.
	 *
	 * @param array $coupon		Coupon data.
	 * @return Result
	 */
	public static function saveCoupon($coupon)
	{
		if (self::$init === false)
			self::init();
		$result = new Result();

		$process = true;

		$resultData = array(
			'ID' => 0,
			'ORDER_ID' => 0,
			'ORDER_DISCOUNT_ID' => 0,
			'COUPON' => '',
			'TYPE' => Internals\DiscountCouponTable::TYPE_UNKNOWN,
			'COUPON_ID' => 0,
			'DATA' => array()
		);

		if (empty($coupon) || !is_array($coupon))
		{
			$process = false;
			$result->addError(new Main\Entity\EntityError(
				Loc::getMessage('SALE_ORDER_DISCOUNT_ERR_EMPTY_COUPON'),
				self::ERROR_ID
			));
		}
		if ($process)
		{
			if (empty($coupon['ORDER_DISCOUNT_ID']) || (int)$coupon['ORDER_DISCOUNT_ID'] <= 0)
			{
				$process = false;
				$result->addError(new Main\Entity\EntityError(
					Loc::getMessage('SALE_ORDER_DISCOUNT_ERR_EMPTY_COUPON'),
					self::ERROR_ID
				));
			}
			if (empty($coupon['COUPON']))
			{
				$process = false;
				$result->addError(new Main\Entity\EntityError(
					Loc::getMessage('SALE_ORDER_DISCOUNT_ERR_COUPON_CODE_ABSENT'),
					self::ERROR_ID
				));
			}
			if (!isset($coupon['TYPE']))
			{
				$process = false;
				$result->addError(new Main\Entity\EntityError(
					Loc::getMessage(
						'SALE_ORDER_DISCOUNT_ERR_COUPON_TYPE_ABSENT',
						array('#COUPON#' => $coupon['COUPON'])
					),
					self::ERROR_ID
				));
			}
			elseif (
				!Internals\DiscountCouponTable::isValidCouponType($coupon['TYPE'])
				&& $coupon['TYPE'] != Internals\DiscountCouponTable::TYPE_ARCHIVED
			)
			{
				$process = false;
				$result->addError(new Main\Entity\EntityError(
					Loc::getMessage(
						'SALE_ORDER_DISCOUNT_ERR_COUPON_TYPE_BAD',
						array('#COUPON#' => $coupon['COUPON'])
					),
					self::ERROR_ID
				));
			}
		}
		if ($process)
		{
			if ($coupon['TYPE'] != Internals\DiscountCouponTable::TYPE_ARCHIVED)
			{
				if (empty($coupon['COUPON_ID']) || (int)$coupon['COUPON_ID'] <= 0)
				{
					$process = false;
					$result->addError(new Main\Entity\EntityError(
						Loc::getMessage(
							'SALE_ORDER_DISCOUNT_ERR_COUPON_ID_BAD',
							array('#COUPON#' => $coupon['COUPON'])
						),
						self::ERROR_ID
					));
				}
			}
		}

		if ($process)
		{
			$orderCouponIterator = Internals\OrderCouponsTable::getList(array(
				'select' => array('*'),
				'filter' => array('=COUPON' => $coupon['COUPON'], '=ORDER_ID' => $coupon['ORDER_ID'])
			));
			if ($orderCoupon = $orderCouponIterator->fetch())
			{
				$resultData = $orderCoupon;
			}
			else
			{
				$fields = $coupon;
				if (array_key_exists('ID', $fields))
					unset($fields['ID']);
				$couponResult = Internals\OrderCouponsTable::add($fields);
				if ($couponResult->isSuccess())
				{
					$resultData = $fields;
					$resultData['ID'] = $couponResult->getId();
				}
				else
				{
					$process = false;
					$result->addErrors($couponResult->getErrors());
				}
				unset($couponResult, $fields);
			}
			unset($orderCoupon, $orderCouponIterator);
		}

		if ($process)
		{
			$result->setId($resultData['ID']);
			$result->setData($resultData);
		}
		unset($process, $resultData);

		return $result;
	}

	/**
	 * Return url for edit sale discount.
	 *
	 * @param array $discount			Discount data.
	 * @return string
	 */
	public static function getEditUrl($discount)
	{
		$result = '';
		if (!empty($discount['ID']))
			$result = '/bitrix/admin/sale_discount_edit.php?lang='.LANGUAGE_ID.'&ID='.$discount['ID'];
		return $result;
	}

	/**
	 * Load applied discount list
	 *
	 * @param int $order				Order id.
	 * @param bool $extendedMode		Get full information by discount.
	 * @param array $basketList			Correspondence between basket ids and basket codes.
	 * @param array $basketData			Basket data.
	 * @return Result
	 */
	public static function loadResultFromDatabase($order, $extendedMode = false, $basketList = array(), $basketData = array())
	{
		if (self::$init === false)
			self::init();
		$result = new Result;

		$translate = (!empty($basketList) && is_array($basketList));

		$extendedMode = ($extendedMode === true);
		$order = (int)$order;
		if ($order <= 0)
		{
			$result->addError(new Main\Entity\EntityError(
				Loc::getMessage('SALE_ORDER_DISCOUNT_BAD_ORDER_ID'),
				self::ERROR_ID
			));
			return $result;
		}
		$resultData = array(
			'BASKET' => array(),
			'ORDER' => array(),
			'DISCOUNT_LIST' => array(),
			'DISCOUNT_MODULES' => array(),
			'COUPON_LIST' => array(),
			'DATA' => array()
		);

		$orderDiscountIndex = 0;
		$orderDiscountLink = array();

		$discountList = array();
		$discountSort = array();
		$couponList = array();
		$couponIterator = Internals\OrderCouponsTable::getList(array(
			'select' => array('*'),
			'filter' => array('=ORDER_ID' => $order),
			'order' => array('ID' => 'ASC')
		));
		while ($coupon = $couponIterator->fetch())
		{
			$coupon['ID'] = (int)$coupon['ID'];
			$coupon['ORDER_ID'] = (int)$coupon['ORDER_ID'];
			$coupon['ORDER_DISCOUNT_ID'] = (int)$coupon['ORDER_DISCOUNT_ID'];
			$resultData['COUPON_LIST'][$coupon['COUPON']] = $coupon;
			$couponList[$coupon['ID']] = $coupon['COUPON'];
		}
		unset($coupon, $couponIterator);

		$ruleIterator = Internals\OrderRulesTable::getList(array(
			'select' => array('*', 'RULE_DESCR' => 'DESCR.DESCR', 'RULE_DESCR_ID' => 'DESCR.ID'),
			'filter' => array('=ORDER_ID' => $order),
			'order' => array('ID' => 'ASC')
		));
		while ($rule = $ruleIterator->fetch())
		{
			$rule['ID'] = (int)$rule['ID'];
			$rule['ORDER_DISCOUNT_ID'] = (int)$rule['ORDER_DISCOUNT_ID'];
			$rule['ORDER_COUPON_ID'] = (int)$rule['COUPON_ID'];
			if ($rule['ORDER_COUPON_ID'] > 0)
			{
				if (!isset($couponList[$rule['COUPON_ID']]))
				{
					$result->addError(new Main\Entity\EntityError(
						Loc::getMessage(
							'SALE_ORDER_DISCOUNT_ERR_RULE_COUPON_NOT_FOUND',
							array('#ID#' => $rule['ID'], '#COUPON_ID#' => $rule['COUPON_ID'])
						)
					));
				}
				else
				{
					$rule['COUPON_ID'] = $couponList[$rule['ORDER_COUPON_ID']];
				}
			}
			$rule['RULE_DESCR_ID'] = (int)$rule['RULE_DESCR_ID'];

			if ($rule['MODULE_ID'] == 'sale')
			{
				$discountId = (int)$rule['ORDER_DISCOUNT_ID'];
				if (!isset($orderDiscountLink[$discountId]))
				{
					$resultData['ORDER'][$orderDiscountIndex] = array(
						'ORDER_ID' => $rule['ORDER_ID'],
						'DISCOUNT_ID' => $rule['ORDER_DISCOUNT_ID'],
						'ORDER_COUPON_ID' => $rule['ORDER_COUPON_ID'],
						'COUPON_ID' => '',
						'RESULT' => array()
					);
					if ($rule['ORDER_COUPON_ID'] > 0)
						$resultData['ORDER'][$orderDiscountIndex]['COUPON_ID'] = $rule['COUPON_ID'];
					$orderDiscountLink[$discountId] = &$resultData['ORDER'][$orderDiscountIndex];
					$orderDiscountIndex++;
				}

				$ruleItem = array(
					'RULE_ID' => $rule['ID'],
					'APPLY' => $rule['APPLY'],
					'RULE_DESCR_ID' => $rule['RULE_DESCR_ID']
				);
				if (!empty($rule['RULE_DESCR']) && is_array($rule['RULE_DESCR']))
				{
					$ruleItem['DESCR_DATA'] = $rule['RULE_DESCR'];
					$ruleItem['DESCR'] = self::formatArrayDescription($rule['RULE_DESCR']);
					$ruleItem['DESCR_ID'] = $rule['RULE_DESCR_ID'];
				}

				switch ($rule['ENTITY_TYPE'])
				{
					case Internals\OrderRulesTable::ENTITY_TYPE_BASKET:
						if (!isset($orderDiscountLink[$discountId]['RESULT']['BASKET']))
							$orderDiscountLink[$discountId]['RESULT']['BASKET'] = array();
						$rule['ENTITY_ID'] = (int)$rule['ENTITY_ID'];
						$ruleItem['BASKET_ID'] = $rule['ENTITY_ID'];
						$index = ($translate ? $basketList[$rule['ENTITY_ID']] : $rule['ENTITY_ID']);
						if (!empty($basketData[$index]))
						{
							$ruleItem['MODULE'] = $basketData[$index]['MODULE'];
							$ruleItem['PRODUCT_ID'] = $basketData[$index]['PRODUCT_ID'];
						}
						$orderDiscountLink[$discountId]['RESULT']['BASKET'][$index] = $ruleItem;
						break;
					case Internals\OrderRulesTable::ENTITY_TYPE_DELIVERY:
						if (!isset($orderDiscountLink[$discountId]['RESULT']['DELIVERY']))
							$orderDiscountLink[$discountId]['RESULT']['DELIVERY'] = array();
						$rule['ENTITY_ID'] = (int)$rule['ENTITY_ID'];
						$ruleItem['DELIVERY_ID'] = ($rule['ENTITY_ID'] > 0 ? $rule['ENTITY_ID'] : (string)$rule['ENTITY_VALUE']);
						$orderDiscountLink[$discountId]['RESULT']['DELIVERY'] = $ruleItem;
						break;
				}
				unset($ruleItem, $discountId);
			}
			else
			{
				if ($rule['ENTITY_TYPE'] != Internals\OrderRulesTable::ENTITY_TYPE_BASKET)
					continue;

				$rule['ENTITY_ID'] = (int)$rule['ENTITY_ID'];
				if ($rule['ENTITY_ID'] <= 0)
					continue;
				$index = ($translate ? $basketList[$rule['ENTITY_ID']] : $rule['ENTITY_ID']);
				if (!isset($resultData['BASKET'][$index]))
					$resultData['BASKET'][$index] = array();
				$ruleResult = array(
					'BASKET_ID' => $rule['ENTITY_ID'],
					'RULE_ID' => $rule['ID'],
					'ORDER_ID' => $rule['ORDER_ID'],
					'DISCOUNT_ID' => $rule['ORDER_DISCOUNT_ID'],
					'ORDER_COUPON_ID' => $rule['ORDER_COUPON_ID'],
					'COUPON_ID' => '',
					'RESULT' => array(
						'APPLY' => $rule['APPLY']
					),
					'RULE_DESCR_ID' => $rule['RULE_DESCR_ID']
				);
				if ($rule['ORDER_COUPON_ID'] > 0)
					$ruleResult['COUPON_ID'] = $rule['COUPON_ID'];
				if (!empty($basketData[$index]))
				{
					$ruleResult['MODULE'] = $basketData[$index]['MODULE'];
					$ruleResult['PRODUCT_ID'] = $basketData[$index]['PRODUCT_ID'];
				}
				if (!empty($rule['RULE_DESCR']) && is_array($rule['RULE_DESCR']))
				{
					$ruleResult['RESULT']['DESCR_DATA'] = $rule['RULE_DESCR'];
					$ruleResult['RESULT']['DESCR'] = self::formatArrayDescription($rule['RULE_DESCR']);
					$ruleResult['DESCR_ID'] = $rule['RULE_DESCR_ID'];
				}
				$resultData['BASKET'][$index][] = $ruleResult;
				unset($ruleResult);
			}

			$discountList[$rule['ORDER_DISCOUNT_ID']] = true;
			if (!isset($discountSort[$rule['ORDER_DISCOUNT_ID']]))
				$discountSort[$rule['ORDER_DISCOUNT_ID']] = $rule['ID'];
		}
		unset($rule, $ruleIterator);
		unset($couponList);

		if (!empty($discountList))
		{
			$discountSelect = (
				$extendedMode
				? array('*')
				: array('ID', 'NAME', 'MODULE_ID', 'DISCOUNT_ID', 'USE_COUPONS')
			);
			$discountIterator = Internals\OrderDiscountTable::getList(array(
				'select' => $discountSelect,
				'filter' => array('@ID' => array_keys($discountList)),
			));
			while ($discount = $discountIterator->fetch())
			{
				$discount['ID'] = (int)$discount['ID'];
				$discount['ORDER_DISCOUNT_ID'] = $discount['ID'];
				$discount['RULE_SORT'] = $discountSort[$discount['ID']];
				if ($discount['MODULE_ID'] == 'sale')
				{
					$discount['EDIT_PAGE_URL'] = self::getEditUrl(array('ID' => $discount['DISCOUNT_ID']));
				}
				else
				{
					$discount['EDIT_PAGE_URL'] = '';
					if (!empty(self::$discountProviders[$discount['MODULE_ID']]['getEditUrl']))
					{
						$discount['EDIT_PAGE_URL'] = call_user_func_array(
							self::$discountProviders[$discount['MODULE_ID']]['getEditUrl'],
							array(
								array('ID' => $discount['DISCOUNT_ID'], 'MODULE_ID' => $discount['MODULE_ID'])
							)
						);
					}
				}
				$resultData['DISCOUNT_LIST'][$discount['ID']] = $discount;
			}
			unset($discount, $discountIterator, $discountSelect);
			if (!empty($resultData['DISCOUNT_LIST']))
			{
				Main\Type\Collection::sortByColumn($resultData['DISCOUNT_LIST'], 'RULE_SORT', '', null, true);
				foreach ($resultData['DISCOUNT_LIST'] as &$discount)
					unset($discount['RULE_SORT']);
				unset($discount);
			}

			$modulesIterator = Internals\OrderModulesTable::getList(array(
				'select' => array('MODULE_ID', 'ORDER_DISCOUNT_ID'),
				'filter' => array('@ORDER_DISCOUNT_ID' => array_keys($discountList))
			));
			while ($module = $modulesIterator->fetch())
			{
				$orderDiscountId = (int)$module['ORDER_DISCOUNT_ID'];
				if (!isset($resultData['DISCOUNT_MODULES'][$orderDiscountId]))
					$resultData['DISCOUNT_MODULES'][$orderDiscountId] = array();
				$resultData['DISCOUNT_MODULES'][$orderDiscountId][] = $module['MODULE_ID'];
			}
			unset($module, $modulesIterator);
		}
		unset($discountList);

		$dataIterator = Internals\OrderDiscountDataTable::getList(array(
			'select' => array('*'),
			'filter' => array(
				'=ORDER_ID' => $order,
				'@ENTITY_TYPE' => array(
					Internals\OrderDiscountDataTable::ENTITY_TYPE_BASKET,
					Internals\OrderDiscountDataTable::ENTITY_TYPE_DELIVERY
				)
			)
		));
		while ($data = $dataIterator->fetch())
		{
			if ($data['ENTITY_TYPE'] == Internals\OrderDiscountDataTable::ENTITY_TYPE_BASKET)
			{
				if (!isset($resultData['DATA']['BASKET']))
					$resultData['DATA']['BASKET'] = array();
				$data['ENTITY_ID'] = (int)$data['ENTITY_ID'];
				$index = ($translate ? $basketList[$data['ENTITY_ID']] : $data['ENTITY_ID']);
				$resultData['DATA']['BASKET'][$index] = $data['ENTITY_DATA'];
			}
			else
			{

			}
		}
		unset($data, $dataIterator);
		$result->addData($resultData);
		unset($resultData);
		return $result;
	}

	/**
	 * Delete all data by order.
	 *
	 * @param int $order			Order id.
	 * @return void
	 */
	public static function deleteByOrder($order)
	{
		$order = (int)$order;
		if ($order <= 0)
			return;
		Internals\OrderRulesTable::clearByOrder($order);
		Internals\OrderDiscountDataTable::clearByOrder($order);
	}

	/**
	 * Prepare discount description.
	 *
	 * @param int $type					Description type.
	 * @param array|string $data		Description data.
	 * @return Result
	 */
	public static function prepareDiscountDescription($type, $data)
	{
		$result = new Result();
		$process = true;
		$type = (int)$type;
		$resultData = array();
		if ($type != self::DESCR_TYPE_SIMPLE)
		{
			if (empty($data) || !is_array($data))
			{
				$process = false;
				$result->addError(new Main\Entity\EntityError(
					Loc::getMessage('SALE_ORDER_DISCOUNT_DESCR_ERR_FORMAT_DESCR_BAD'),
					self::ERROR_ID
				));
			}
		}
		switch ($type)
		{
			case self::DESCR_TYPE_SIMPLE:
				if (empty($data) || !is_string($data))
				{
					$process = false;
					$result->addError(new Main\Entity\EntityError(
						Loc::getMessage('SALE_ORDER_DISCOUNT_DESCR_ERR_FORMAT_DESCR_BAD'),
						self::ERROR_ID
					));
				}
				if ($process)
				{
					$resultData = array(
						'TYPE' => self::DESCR_TYPE_SIMPLE,
						'DESCR' => $data
					);
				}
				break;
			case self::DESCR_TYPE_LIMIT_VALUE:
			case self::DESCR_TYPE_VALUE:
				if ($type == self::DESCR_TYPE_LIMIT_VALUE)
				{
					if ($process)
					{
						if (!isset($data['LIMIT_UNIT']) && isset(self::$managerConfig['CURRENCY']))
							$data['LIMIT_UNIT'] = self::$managerConfig['CURRENCY'];
						if (!isset($data['LIMIT_TYPE']) || !isset($data['LIMIT_VALUE']) || !isset($data['LIMIT_UNIT']))
						{
							$process = false;
							$result->addError(new Main\Entity\EntityError(
								Loc::getMessage('SALE_ORDER_DISCOUNT_DESCR_ERR_FORMAT_DESCR_BAD'),
								self::ERROR_ID
							));
						}
						elseif ($data['LIMIT_TYPE'] != self::DESCR_LIMIT_MAX && $data['LIMIT_TYPE'] != self::DESCR_LIMIT_MIN)
						{
							$process = false;
							$result->addError(new Main\Entity\EntityError(
								Loc::getMessage('SALE_ORDER_DISCOUNT_DESCR_ERR_FORMAT_DESCR_BAD'),
								self::ERROR_ID
							));
						}
					}
					if ($process)
					{
						if ($data['VALUE_TYPE'] != self::DESCR_VALUE_TYPE_PERCENT)
						{
							$process = false;
							$result->addError(new Main\Entity\EntityError(
								Loc::getMessage('SALE_ORDER_DISCOUNT_DESCR_ERR_FORMAT_DESCR_BAD'),
								self::ERROR_ID
							));
						}
					}
					if ($process)
					{
						$resultData['LIMIT_TYPE'] = $data['LIMIT_TYPE'];
						$resultData['LIMIT_VALUE'] = $data['LIMIT_VALUE'];
						$resultData['LIMIT_UNIT'] = $data['LIMIT_UNIT'];
					}
				}
				if ($process)
				{
					if (!isset($data['VALUE']) || !isset($data['VALUE_TYPE']))
					{
						$process = false;
						$result->addError(new Main\Entity\EntityError(
							Loc::getMessage('SALE_ORDER_DISCOUNT_DESCR_ERR_FORMAT_DESCR_BAD'),
							self::ERROR_ID
						));
					}
				}
				if ($process)
				{
					if (
						$data['VALUE_TYPE'] != self::DESCR_VALUE_TYPE_PERCENT
						&& $data['VALUE_TYPE'] != self::DESCR_VALUE_TYPE_CURRENCY
						&& $data['VALUE_TYPE'] != self::DESCR_VALUE_TYPE_SUMM
						&& $data['VALUE_TYPE'] != self::DESCR_VALUE_TYPE_SUMM_BASKET
					)
					{
						$process = false;
						$result->addError(new Main\Entity\EntityError(
							Loc::getMessage('SALE_ORDER_DISCOUNT_DESCR_ERR_FORMAT_DESCR_BAD'),
							self::ERROR_ID
						));
					}
					elseif (
						$data['VALUE_TYPE'] == self::DESCR_VALUE_TYPE_CURRENCY
						|| $data['VALUE_TYPE'] == self::DESCR_VALUE_TYPE_SUMM
						|| $data['VALUE_TYPE'] == self::DESCR_VALUE_TYPE_SUMM_BASKET
					)
					{
						if (!isset($data['VALUE_UNIT']) && isset(self::$managerConfig['CURRENCY']))
							$data['VALUE_UNIT'] = self::$managerConfig['CURRENCY'];
						if (!isset($data['VALUE_UNIT']))
						{
							$process = false;
							$result->addError(new Main\Entity\EntityError(
								Loc::getMessage('SALE_ORDER_DISCOUNT_DESCR_ERR_FORMAT_DESCR_BAD'),
								self::ERROR_ID
							));
						}
					}
				}
				if ($process)
				{
					if (!isset($data['VALUE_ACTION']))
						$data['VALUE_ACTION'] = self::DESCR_VALUE_ACTION_DISCOUNT;
					if (
						$data['VALUE_ACTION'] != self::DESCR_VALUE_ACTION_DISCOUNT
						&& $data['VALUE_ACTION'] != self::DESCR_VALUE_ACTION_EXTRA
						&& $data['VALUE_ACTION'] != self::DESCR_VALUE_ACTION_ACCUMULATE
					)
					{
						$process = false;
						$result->addError(new Main\Entity\EntityError(
							Loc::getMessage('SALE_ORDER_DISCOUNT_DESCR_ERR_FORMAT_DESCR_BAD'),
							self::ERROR_ID
						));
					}
				}
				if ($process)
				{
					$resultData['TYPE'] = $type;
					$resultData['VALUE'] = $data['VALUE'];
					$resultData['VALUE_TYPE'] = $data['VALUE_TYPE'];
					$resultData['VALUE_ACTION'] = $data['VALUE_ACTION'];

					if (
						$data['VALUE_TYPE'] == self::DESCR_VALUE_TYPE_CURRENCY
						|| $data['VALUE_TYPE'] == self::DESCR_VALUE_TYPE_SUMM
						|| $data['VALUE_TYPE'] == self::DESCR_VALUE_TYPE_SUMM_BASKET
					)
						$resultData['VALUE_UNIT'] = $data['VALUE_UNIT'];
					if (isset($data['RESULT_VALUE']) && isset($data['RESULT_UNIT']))
					{
						$resultData['RESULT_VALUE'] = (string)$data['RESULT_VALUE'];
						$resultData['RESULT_UNIT'] = $data['RESULT_UNIT'];
					}
				}
				break;
			case self::DESCR_TYPE_FIXED:
				if ($process)
				{
					if (!isset($data['VALUE_UNIT']) && isset(self::$managerConfig['CURRENCY']))
						$data['VALUE_UNIT'] = self::$managerConfig['CURRENCY'];
					if (!isset($data['VALUE']) || !isset($data['VALUE_UNIT']))
					{
						$process = false;
						$result->addError(new Main\Entity\EntityError(
							Loc::getMessage('SALE_ORDER_DISCOUNT_DESCR_ERR_FORMAT_DESCR_BAD'),
							self::ERROR_ID
						));
					}
				}
				if ($process)
				{
					$resultData = array(
						'TYPE' => $type,
						'VALUE' => $data['VALUE'],
						'VALUE_UNIT' => $data['VALUE_UNIT']
					);
				}
				break;
			case self::DESCR_TYPE_MAX_BOUND:
				if ($process)
				{
					if (!isset($data['VALUE_UNIT']) && isset(self::$managerConfig['CURRENCY']))
						$data['VALUE_UNIT'] = self::$managerConfig['CURRENCY'];
					if (!isset($data['VALUE']) || !isset($data['VALUE_UNIT']))
					{
						$process = false;
						$result->addError(new Main\Entity\EntityError(
							Loc::getMessage('SALE_ORDER_DISCOUNT_DESCR_ERR_FORMAT_DESCR_BAD'),
							self::ERROR_ID
						));
					}
				}
				if ($process)
				{
					$resultData = array(
						'TYPE' => $type,
						'VALUE' => $data['VALUE'],
						'VALUE_UNIT' => $data['VALUE_UNIT']
					);
					if (isset($data['RESULT_VALUE']) && isset($data['RESULT_UNIT']))
					{
						$resultData['RESULT_VALUE'] = (string)$data['RESULT_VALUE'];
						$resultData['RESULT_UNIT'] = $data['RESULT_UNIT'];
					}
				}
				break;
			default:
				$process = false;
				$result->addError(new Main\Entity\EntityError(
					Loc::getMessage('SALE_ORDER_DISCOUNT_DESCR_ERR_FORMAT_TYPE_BAD'),
					self::ERROR_ID
				));
				break;
		}

		if ($process)
			$result->setData($resultData);
		return $result;
	}

	/**
	 * Format discount description.
	 *
	 * @param array $data		Discount description.
	 * @return Result
	 */
	public static function formatDiscountDescription($data)
	{
		$result = new Result();

		$process = true;
		if (empty($data) || !is_array($data) || empty($data['TYPE']))
		{
			$process = true;
			$result->addError(new Main\Entity\EntityError(
				Loc::getMessage('SALE_ORDER_DISCOUNT_DESCR_ERR_FORMAT_DESCR_BAD'),
				self::ERROR_ID
			));
		}

		$resultData = array(
			'DESCRIPTION' => ''
		);
		if ($process)
		{
			switch ($data['TYPE'])
			{
				case self::DESCR_TYPE_SIMPLE:
					$resultData['DESCRIPTION'] = $data['DESCR'];
					break;
				case self::DESCR_TYPE_VALUE:
					if ($data['VALUE_TYPE'] == self::DESCR_VALUE_TYPE_PERCENT)
					{
						$value = $data['VALUE'].'%';
						if (isset($data['RESULT_VALUE']) && isset($data['RESULT_UNIT']))
							$value .= ' ('.\CCurrencyLang::currencyFormat($data['RESULT_VALUE'], $data['RESULT_UNIT'], true).')';
					}
					else
					{
						if ($data['VALUE_TYPE'] == self::DESCR_VALUE_TYPE_CURRENCY)
						{
							$value = \CCurrencyLang::currencyFormat($data['VALUE'], $data['VALUE_UNIT'], true);
						}
						else
						{
							$subMessageID = (
								$data['VALUE_TYPE'] == self::DESCR_VALUE_TYPE_SUMM
								? 'SALE_ORDER_DISCOUNT_DESCR_MESS_SUMM_FORMAT'
								: 'SALE_ORDER_DISCOUNT_DESCR_MESS_SUMM_BASKET_FORMAT'
							);
							$value = Loc::getMessage(
								$subMessageID,
								array('#VALUE#' => \CCurrencyLang::currencyFormat($data['VALUE'], $data['VALUE_UNIT'], true))
							);
							unset($subMessageID);
						}
						if (isset($data['RESULT_VALUE']) && isset($data['RESULT_UNIT']) && $data['VALUE_UNIT'] != $data['RESULT_UNIT'])
							$value .= ' ('.\CCurrencyLang::currencyFormat($data['RESULT_VALUE'], $data['RESULT_UNIT'], true).')';
					}
					$messageId = 'SALE_ORDER_DISCOUNT_DESCR_MESS_TYPE_DISCOUNT';
					if (isset($data['VALUE_ACTION']))
					{
						switch ($data['VALUE_ACTION'])
						{
							case self::DESCR_VALUE_ACTION_EXTRA:
								$messageId = 'SALE_ORDER_DISCOUNT_DESCR_MESS_TYPE_EXTRA';
								break;
							case self::DESCR_VALUE_ACTION_ACCUMULATE:
								$messageId = 'SALE_ORDER_DISCOUNT_DESCR_MESS_TYPE_ACCUMULATE';
								break;
						}
					}
					$resultData['DESCRIPTION'] = Loc::getMessage($messageId, array('#VALUE#' => $value));
					unset($value, $messageId);
					break;
				case self::DESCR_TYPE_LIMIT_VALUE:
					$messageId = (
						isset($data['LIMIT_TYPE']) && $data['LIMIT_TYPE'] == self::DESCR_LIMIT_MIN
						? 'SALE_ORDER_DISCOUNT_DESCR_MESS_LIMIT_MIN_FORMAT'
						: 'SALE_ORDER_DISCOUNT_DESCR_MESS_LIMIT_MAX_FORMAT'
					);
					$value = Loc::getMessage(
						$messageId,
						array(
							'#PERCENT#' => $data['VALUE'].'%',
							'#LIMIT#' => \CCurrencyLang::currencyFormat($data['LIMIT_VALUE'], $data['LIMIT_UNIT'], true)
						)
					);
					if (isset($data['RESULT_VALUE']) && isset($data['RESULT_UNIT']))
						$value .= ' ('.\CCurrencyLang::currencyFormat($data['RESULT_VALUE'], $data['RESULT_UNIT'], true).')';
					$messageId = (
						isset($data['VALUE_ACTION']) && $data['VALUE_ACTION'] == self::DESCR_VALUE_ACTION_EXTRA
						? 'SALE_ORDER_DISCOUNT_DESCR_MESS_TYPE_EXTRA'
						: 'SALE_ORDER_DISCOUNT_DESCR_MESS_TYPE_DISCOUNT'
					);
					$resultData['DESCRIPTION'] = Loc::getMessage($messageId, array('#VALUE#' => $value));
					unset($value, $messageId);
					break;
				case self::DESCR_TYPE_FIXED:
					$resultData['DESCRIPTION'] = Loc::getMessage(
						'SALE_ORDER_DISCOUNT_DESCR_MESS_FIXED_FORMAT',
						array('#VALUE#' => \CCurrencyLang::currencyFormat($data['VALUE'], $data['VALUE_UNIT'], true))
					);
					break;
				case self::DESCR_TYPE_MAX_BOUND:
					$value = \CCurrencyLang::currencyFormat($data['VALUE'], $data['VALUE_UNIT'], true);
					if (isset($data['RESULT_VALUE']) && isset($data['RESULT_UNIT']))
						$value .= ' ('.\CCurrencyLang::currencyFormat($data['RESULT_VALUE'], $data['RESULT_UNIT'], true).')';
					else
						$value .= ' ('.$value.')';
					$resultData['DESCRIPTION'] = Loc::getMessage(
						'SALE_ORDER_DISCOUNT_DESCR_MESS_MAX_BOUND_FORMAT',
						array('#VALUE#' => $value)
					);
					unset($value);
					break;
				default:
					break;
			}
		}

		if ($process)
			$result->setData($resultData);
		return $result;
	}

	/**
	 * Return string discount description.
	 *
	 * @param array $data			Description.
	 * @return bool|string
	 */
	public static function formatDescription($data)
	{
		$result = false;
		$descr = self::formatDiscountDescription($data);
		if ($descr->isSuccess())
		{
			$data = $descr->getData();
			if (!empty($data['DESCRIPTION']))
				$result = (string)$data['DESCRIPTION'];
		}
		return $result;
	}

	/**
	 * Format discount result.
	 *
	 * @param array $descr			Description data.
	 * @return array|bool
	 */
	public static function formatArrayDescription($descr)
	{
		$result = array();
		if (!empty($descr) && is_array($descr))
		{
			foreach ($descr as &$descrRow)
			{
				$descrValue = self::formatDescription($descrRow);
				if ($descrValue !== false)
					$result[] = $descrValue;
			}
			unset($descrValue, $descrRow);
		}
		return (empty($result) ? false: $result);
	}

	/**
	 * Create simple description for unknown discount.
	 *
	 * @param float $newPrice			New price.
	 * @param float $oldPrice			Old price.
	 * @param string $currency			Currency.
	 * @return array|bool
	 */
	public static function createSimpleDescription($newPrice, $oldPrice, $currency)
	{
		$result = false;
		$descr = array(
			'VALUE_TYPE' => self::DESCR_VALUE_TYPE_CURRENCY,
			'VALUE' => abs($oldPrice - $newPrice),
			'VALUE_UNIT' => $currency,
			'VALUE_ACTION' => ($oldPrice > $newPrice ? self::DESCR_VALUE_ACTION_DISCOUNT : self::DESCR_VALUE_ACTION_EXTRA)
		);
		$resultDescr = self::prepareDiscountDescription(self::DESCR_TYPE_VALUE, $descr);
		if ($resultDescr->isSuccess())
		{
			$result = array(
				0 => $resultDescr->getData()
			);
		}
		unset($resultDescr, $descr);
		return $result;
	}

	/**
	 * Check existing discount provider for module.
	 *
	 * @param string $module			Module id.
	 * @return bool
	 */
	public static function checkDiscountProvider($module)
	{
		if (self::$init === false)
			self::init();
		$module = (string)$module;
		if ($module == 'sale')
			return true;
		return ($module != '' && isset(self::$discountProviders[$module]));
	}

	/**
	 * Migrate discount data from b_sale_basket into new entity.
	 *
	 * @param array $order				Order data.
	 * @return Result
	 */
	public static function migrateOrderDiscounts($order)
	{
		if (self::$init === false)
			self::init();

		static $useBasePrice = null;
		if ($useBasePrice === null)
			$useBasePrice = (string)Main\Config\Option::get('sale', 'get_discount_percent_from_base_price');

		$process = true;
		$result = new Result();

		if (empty($order['ID']) || (int)$order['ID'] <= 0)
		{
			$process = false;
			$result->addError(new Main\Entity\EntityError(
				Loc::getMessage('SALE_ORDER_DISCOUNT_ERR_EMPTY_ORDER_ID'),
				self::ERROR_ID
			));
		}

		$catalogOrder = false;
		$basketData = array();
		if ($process)
		{
			$order['ID'] = (int)$order['ID'];
			$basePrices = array();

			$basketIterator = Internals\BasketTable::getList(array(
				'select' => array('ID', 'DISCOUNT_COUPON', 'DISCOUNT_NAME', 'DISCOUNT_VALUE', 'MODULE', 'PRICE', 'DISCOUNT_PRICE', 'CURRENCY', 'SET_PARENT_ID', 'TYPE'),
				'filter' => array('=ORDER_ID' => $order['ID'])
			));
			while ($basket = $basketIterator->fetch())
			{
				$basket['ID'] = (int)$basket['ID'];
				$basket['MODULE'] = (string)$basket['MODULE'];
				$basket['DISCOUNT_COUPON'] = trim((string)$basket['DISCOUNT_COUPON']);
				$basket['DISCOUNT_NAME'] = trim((string)$basket['DISCOUNT_NAME']);
				$basket['SET_PARENT_ID'] = (int)$basket['SET_PARENT_ID'];
				$basket['TYPE'] = (int)$basket['TYPE'];
				if ($basket['MODULE'] == 'catalog')
				{
					$basePrices[$basket['ID']] = array(
						'BASE_PRICE' => $basket['PRICE'] + $basket['DISCOUNT_PRICE'],
						'BASE_PRICE_CURRENCY' => $basket['CURRENCY']
					);
				}

				if ($basket['MODULE'] != 'catalog' || ($basket['DISCOUNT_NAME'] == '' && $basket['DISCOUNT_COUPON'] == ''))
					continue;
				if ($basket['SET_PARENT_ID'] > 0 && $basket['TYPE'] <= 0)
					continue;

				$catalogOrder = true;
				$hash = md5($basket['DISCOUNT_NAME'].'|'.$basket['DISCOUNT_COUPON']);
				if (!isset($basketData[$hash]))
					$basketData[$hash] = array(
						'DISCOUNT_NAME' => $basket['DISCOUNT_NAME'],
						'DISCOUNT_COUPON' => $basket['DISCOUNT_COUPON'],
						'ITEMS' => array()
					);
				$basketData[$hash]['ITEMS'][$basket['ID']] = $basket;
			}
			unset($basket, $basketIterator);
		}

		if ($process && $catalogOrder)
		{
			self::setManagerConfig(array(
				'CURRENCY' => $order['CURRENCY'],
				'SITE_ID' => $order['LID'],
				'USE_BASE_PRICE' => $useBasePrice
			));
			foreach ($basketData as &$row)
			{
				if (!self::migrateDiscount($order['ID'], $row))
				{
					$process = false;
					$result->addError(new Main\Entity\EntityError(
						Loc::getMessage('SALE_ORDER_DISCOUNT_ERR_SAVE_MIGRATE_DISCOUNT'),
						self::ERROR_ID
					));
					break;
				}
			}
			unset($row);
		}
		unset($basketData);

		Internals\OrderDiscountDataTable::clearByOrder($order['ID']);
		if ($process)
		{
			if (!empty($basePrices))
			{
				foreach ($basePrices as $basketId => $price)
				{
					$fields = array(
						'ORDER_ID' => $order['ID'],
						'ENTITY_TYPE' => Internals\OrderDiscountDataTable::ENTITY_TYPE_BASKET,
						'ENTITY_ID' => $basketId,
						'ENTITY_VALUE' => $basketId,
						'ENTITY_DATA' => $price,
					);
					$operationResult = Internals\OrderDiscountDataTable::add($fields);
					if (!$operationResult->isSuccess())
					{
						$process = false;
						$result->addErrors($operationResult->getErrors());
					}
					unset($operationResult);
				}
				unset($basketId, $price);
			}
		}

		if ($process)
		{
			$fields = array(
				'ORDER_ID' => $order['ID'],
				'ENTITY_TYPE' => Internals\OrderDiscountDataTable::ENTITY_TYPE_ORDER,
				'ENTITY_ID' => $order['ID'],
				'ENTITY_VALUE' => $order['ID'],
				'ENTITY_DATA' => array(
					'OLD_ORDER' => 'Y'
				)
			);
			$operationResult = Internals\OrderDiscountDataTable::add($fields);
			if (!$operationResult->isSuccess())
			{
				$process = false;
				$result->addErrors($operationResult->getErrors());
			}
			unset($operationResult);
		}
		unset($process);

		return $result;
	}

	/**
	 * Initialization discount providers.
	 *
	 * @return void
	 */
	protected static function initDiscountProviders()
	{
		/** @var Main\EventResult $eventResult */

		self::$discountProviders = array();
		$event = new Main\Event('sale', self::EVENT_ON_BUILD_DISCOUNT_PROVIDERS, array());
		$event->send();
		$resultList = $event->getResults();
		if (empty($resultList) || !is_array($resultList))
			return;
		foreach ($resultList as &$eventResult)
		{
			if ($eventResult->getType() != Main\EventResult::SUCCESS)
				continue;
			$module = (string)$eventResult->getModuleId();
			$provider = $eventResult->getParameters();
			if (empty($provider) || !is_array($provider))
				continue;
			if (!isset($provider['prepareData']))
				continue;
			self::$discountProviders[$module] = array(
				'module' => $module,
				'prepareData' => $provider['prepareData'],
			);
			if (isset($provider['getEditUrl']))
				self::$discountProviders[$module]['getEditUrl'] = $provider['getEditUrl'];
		}
		unset($provider, $module, $eventResult, $resultList, $event);
	}

	/**
	 * Prepare sale discount before saving.
	 *
	 * @param array $discount				Discount data.
	 * @return array|bool
	 */
	protected static function prepareData($discount)
	{
		if (empty($discount) || empty($discount['ID']))
			return false;

		$discountId = (int)$discount['ID'];
		if ($discountId <= 0)
			return false;

		if (!isset($discount['NAME']) || (string)$discount['NAME'] == '')
			$discount['NAME'] = Loc::getMessage('SALE_ORDER_DISCOUNT_NAME_TEMPLATE', array('#ID#' => $discountId));
		$discount['DISCOUNT_ID'] = $discountId;
		$discount['EDIT_PAGE_URL'] = self::getEditUrl(array('ID' => $discountId));
		unset($discount['ID']);

		return $discount;
	}

	/**
	 * Execute prepare data from provider.
	 *
	 * @param string $module			Module id.
	 * @param array $discount			Discount data.
	 * @return mixed
	 */
	protected static function executeDiscountProvider($module, $discount)
	{
		return $discountData = call_user_func_array(
			self::$discountProviders[$module]['prepareData'],
			array(
				$discount,
				self::$managerConfig
			)
		);
	}

	/**
	 * Convert discount for old order.
	 *
	 * @param int $orderId				Order id.
	 * @param array &$data				Discount data.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	private static function migrateDiscount($orderId, &$data)
	{
		if (self::$catalogIncluded === null)
			self::$catalogIncluded = Main\Loader::includeModule('catalog');
		if (!self::$catalogIncluded)
			return false;

		$discountData = array(
			'COUPON' => '',
			'NAME' => '',
			'DISCOUNT_ID' => 0
		);
		if ($data['DISCOUNT_NAME'] != '')
		{
			$discountName = array();
			if (preg_match('/^\[(\d+)\][ ](.+)$/', $data['DISCOUNT_NAME'], $discountName) == 1)
			{
				$discountData['NAME'] = $discountName[2];
				$discountData['DISCOUNT_ID'] = $discountName[1];
			}
			unset($discountName);
		}
		if ($data['DISCOUNT_COUPON'] != '')
		{
			$discountData['COUPON'] = $data['DISCOUNT_COUPON'];
			if (!self::checkMigrateCoupon($discountData['COUPON']))
				return false;

			if ($discountData['DISCOUNT_ID'] == 0)
			{
				$discountData['NAME'] = self::$migrateCouponsCache[$discountData['COUPON']]['DISCOUNT_NAME'];
				$discountData['DISCOUNT_ID'] = self::$migrateCouponsCache[$discountData['COUPON']]['DISCOUNT_ID'];
			}
			else
			{
				if (
					self::$migrateCouponsCache[$discountData['COUPON']]['TYPE'] != Internals\DiscountCouponTable::TYPE_ARCHIVED
					&& self::$migrateCouponsCache[$discountData['COUPON']]['DISCOUNT_ID'] >= 0
					&& $discountData['DISCOUNT_ID'] != self::$migrateCouponsCache[$discountData['COUPON']]['DISCOUNT_ID']
				)
					$discountData['DISCOUNT_ID'] = 0;
			}
		}
		if ($discountData['DISCOUNT_ID'] == 0)
		{
			if ($discountData['COUPON'] == '')
				return false;
			self::createEmptyDiscount($discountData);
		}
		else
		{
			self::checkMigrateDiscount($discountData);
		}
		$saveResult = self::saveMigrateDiscount($discountData);
		if (!$saveResult->isSuccess())
			return false;

		$migrateDiscountData = $saveResult->getData();
		unset($saveResult);
		$orderDiscountId = $migrateDiscountData['ORDER_DISCOUNT_ID'];
		$orderCouponId = 0;
		$discountDescr = current($migrateDiscountData['ACTIONS_DESCR']['BASKET']);
		if ($discountData['COUPON'] != '')
		{
			$couponData = self::$migrateCouponsCache[$discountData['COUPON']];
			$couponData['ORDER_ID'] = $orderId;
			$couponData['ORDER_DISCOUNT_ID'] = $migrateDiscountData['ORDER_DISCOUNT_ID'];
			$couponData['DATA']['DISCOUNT_ID'] = $migrateDiscountData['DISCOUNT_ID'];
			if (array_key_exists('DISCOUNT_ID', $couponData))
				unset($couponData['DISCOUNT_ID']);
			if (array_key_exists('DISCOUNT_NAME', $couponData))
				unset($couponData['DISCOUNT_NAME']);

			$saveResult = self::saveCoupon($couponData);
			if (!$saveResult->isSuccess())
				return false;
			$migrateCoupon = $saveResult->getData();
			$orderCouponId = $migrateCoupon['ID'];
		}

		foreach ($data['ITEMS'] as $basketItem)
		{
			$applyDescr = $discountDescr;
			if ($basketItem['DISCOUNT_VALUE'] != '')
			{
				if ($applyDescr['TYPE'] == self::DESCR_TYPE_SIMPLE)
				{
					$applyDescr['DESCR'] .= ' ('.$basketItem['DISCOUNT_VALUE'].')';
				}
				else
				{
					$valueData = array();
					if (preg_match('/^(|\+|-)(\d+|[.,]\d+|\d+[.,]\d+)\s?%$/', $basketItem['DISCOUNT_VALUE'], $valueData) == 1)
					{
						$applyDescr['RESULT_VALUE'] = (float)$basketItem['DISCOUNT_VALUE'];
						$applyDescr['RESULT_UNIT'] = self::DESCR_VALUE_TYPE_PERCENT;
					}
					unset($valueData);
				}
			}
			$ruleRow = array(
				'MODULE_ID' => 'catalog',
				'ORDER_DISCOUNT_ID' => $orderDiscountId,
				'ORDER_ID' => $orderId,
				'ENTITY_TYPE' => Internals\OrderRulesTable::ENTITY_TYPE_BASKET,
				'ENTITY_ID' => $basketItem['ID'],
				'ENTITY_VALUE' => $basketItem['ID'],
				'COUPON_ID' => $orderCouponId,
				'APPLY' => 'Y'
			);
			$ruleDescr = array(
				'MODULE_ID' => 'catalog',
				'ORDER_DISCOUNT_ID' => $orderDiscountId,
				'ORDER_ID' => $orderId,
				'DESCR' => array($applyDescr)
			);
			$ruleResult = Internals\OrderRulesTable::add($ruleRow);
			if ($ruleResult->isSuccess())
			{
				$ruleDescr['RULE_ID'] = $ruleResult->getId();
				$descrResult = Internals\OrderRulesDescrTable::add($ruleDescr);
				if (!$descrResult->isSuccess())
					return false;
			}
			else
			{
				return false;
			}
			unset($ruleResult);
		}
		unset($basketItem);

		return true;
	}

	/**
	 * Check coupon for convert.
	 *
	 * @param string $coupon				Coupon.
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 */
	private static function checkMigrateCoupon($coupon)
	{
		if (self::$catalogIncluded === null)
			self::$catalogIncluded = Main\Loader::includeModule('catalog');
		if (!self::$catalogIncluded)
			return false;

		static $catalogCouponTypes = null;
		if ($catalogCouponTypes === null)
			$catalogCouponTypes = array(
				Catalog\DiscountCouponTable::TYPE_ONE_ROW => Internals\DiscountCouponTable::TYPE_BASKET_ROW,
				Catalog\DiscountCouponTable::TYPE_ONE_ORDER => Internals\DiscountCouponTable::TYPE_ONE_ORDER,
				Catalog\DiscountCouponTable::TYPE_NO_LIMIT => Internals\DiscountCouponTable::TYPE_MULTI_ORDER
			);

		if (!isset(self::$migrateCouponsCache[$coupon]))
		{
			self::$migrateCouponsCache[$coupon] = false;
			$couponIterator = Catalog\DiscountCouponTable::getList(array(
				'select' => array('COUPON_ID' => 'ID', 'COUPON', 'TYPE', 'DISCOUNT_ID', 'DISCOUNT_NAME' => 'DISCOUNT.NAME'),
				'filter' => array('=COUPON' => $coupon)
			));
			$existCoupon = $couponIterator->fetch();
			unset($couponIterator);
			if (!empty($existCoupon))
			{
				$existCoupon['TYPE'] = (
					isset($catalogCouponTypes[$existCoupon['TYPE']])
					? $catalogCouponTypes[$existCoupon['TYPE']]
					: Internals\DiscountCouponTable::TYPE_ARCHIVED
				);
				$existCoupon['DATA'] = array(
					'MODE' => DiscountCouponsManager::COUPON_MODE_SIMPLE,
					'MODULE' => 'catalog',
					'DISCOUNT_ID' => 0,
					'TYPE' => Internals\DiscountCouponTable::TYPE_ARCHIVED,
					'USER_INFO' => array(),
				);
				self::$migrateCouponsCache[$coupon] = $existCoupon;
			}
			else
			{
				self::$migrateCouponsCache[$coupon] = self::createEmptyCoupon($coupon);
			}
			unset($existCoupon);
		}
		return true;
	}

	/**
	 * Create fake coupon.
	 *
	 * @param string $coupon			Coupon.
	 * @return array
	 */
	private static function createEmptyCoupon($coupon)
	{
		return array(
			'COUPON' => $coupon,
			'TYPE' => Internals\DiscountCouponTable::TYPE_ARCHIVED,
			'COUPON_ID' => 0,
			'DATA' => array(
				'COUPON' => $coupon,
				'MODE' => DiscountCouponsManager::COUPON_MODE_SIMPLE,
				'MODULE' => 'catalog',
				'DISCOUNT_ID' => 0,
				'TYPE' => Internals\DiscountCouponTable::TYPE_ARCHIVED,
				'USER_INFO' => array(),
			)
		);
	}

	/**
	 * Create fake discount.
	 *
	 * @param array &$discountData					Discount data.
	 * @param bool $accumulate				Accumulate discount.
	 * @return void
	 */
	private static function createEmptyDiscount(&$discountData, $accumulate = false)
	{
		$accumulate = ($accumulate === true);
		static $emptyFields = null;
		if ($emptyFields === null)
		{
			$emptyFields = array(
				'DISCOUNT_ID' => 0,
				'NAME' => Loc::getMessage('SALE_ORDER_DISCOUNT_MESS_CATALOG_DISCOUNT_NAME'),
				'SORT' => 100,
				'PRIORITY' => 1,
				'LAST_DISCOUNT' => 'Y',
				'USE_COUPONS' => 'N'
			);
		}

		static $replaceFields = null;
		static $replaceKeys = null;
		if ($replaceFields === null)
		{
			$replaceFields = array(
				'MODULE_ID' => 'catalog',
				'CONDITIONS' => array(
					'CLASS_ID' => 'CondGroup',
					'DATA' => array('All' => 'AND', 'True' => 'True'),
					'CHILDREN' => array()
				),
				'UNPACK' => '((1 == 1))',
				'ACTIONS' => array(),
				'APPLICATION' => '0'
			);
			$replaceKeys = array(
				'MODULE_ID',
				'CONDITIONS',
				'UNPACK',
				'ACTIONS',
				'APPLICATION'
			);
		}
		static $discountDescr = null;
		if ($discountDescr === null)
		{
			$discountDescr = $actionsDescr = self::prepareDiscountDescription(
				self::DESCR_TYPE_SIMPLE,
				Loc::getMessage('SALE_ORDER_DISCOUNT_MESS_CATALOG_DISCOUNT_SIMPLE_MESS')
			)->getData();
		}
		static $accumulateDescr = null;
		if ($accumulateDescr === null)
		{
			$accumulateDescr = $actionsDescr = self::prepareDiscountDescription(
				self::DESCR_TYPE_SIMPLE,
				Loc::getMessage('SALE_ORDER_DISCOUNT_DESCR_MESS_TYPE_ACCUMULATE_EMPTY')
			)->getData();
		}
		foreach ($replaceKeys as &$key)
		{
			if (array_key_exists($key, $discountData))
				unset($discountData[$key]);
		}
		unset($key);
		$discountData = array_merge($emptyFields, $discountData);
		foreach ($replaceFields as $key => $value)
		{
			$discountData[$key] = $value;
		}
		unset($key, $value);
		if (empty($discountData['ACTIONS_DESCR']))
			$discountData['ACTIONS_DESCR'] = array(
				'BASKET' => array(
					0 => ($accumulate ? $accumulateDescr : $discountDescr)
				)
			);
		if (!$accumulate)
			$discountData['USE_COUPONS'] = ($discountData['COUPON'] != '' ? 'Y' : 'N');
	}

	/**
	 * Check discount for convert.
	 *
	 * @param array &$discountData			Discount data.
	 * @return void
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 */
	private static function checkMigrateDiscount(&$discountData)
	{
		if (self::$catalogIncluded === null)
			self::$catalogIncluded = Main\Loader::includeModule('catalog');
		if (!self::$catalogIncluded)
			return;

		$coupon = $discountData['COUPON'];
		$hash = md5($discountData['DISCOUNT_ID'].'|'.$discountData['NAME']);
		if (!isset(self::$catalogDiscountsCache[$hash]))
		{
			$discountIterator = Catalog\DiscountTable::getList(array(
				'select' => array('*'),
				'filter' => array('=ID' => $discountData['DISCOUNT_ID'], '=NAME' => $discountData['NAME'])
			));
			$existDiscount = $discountIterator->fetch();
			unset($discountIterator);
			if (!empty($existDiscount))
			{
				if ($existDiscount['NAME'] != $discountData['NAME'])
				{
					self::createEmptyDiscount($discountData);
				}
				else
				{
					if ($existDiscount['TYPE'] == Catalog\DiscountTable::TYPE_DISCOUNT_SAVE)
					{
						self::createEmptyDiscount($discountData, true);
					}
					else
					{
						$existDiscount['COUPON'] = $discountData['COUPON'];
						$discountData = self::executeDiscountProvider('catalog', $existDiscount);
					}
				}
			}
			else
			{
				self::createEmptyDiscount($discountData);
			}
			unset($existDiscount);
			self::$catalogDiscountsCache[$hash] = $discountData;
		}
		else
		{
			$discountData = self::$catalogDiscountsCache[$hash];
		}
		$discountData['COUPON'] = $coupon;
	}

	/**
	 * Save converted discount.
	 *
	 * @param array $discountData				Discount data.
	 * @return Result
	 * @throws Main\ArgumentException
	 * @throws \Exception
	 */
	private static function saveMigrateDiscount($discountData)
	{
		$result = new Result();
		$process = true;
		$hash = false;
		$resultData = array();
		$fields = Internals\OrderDiscountTable::prepareDiscountData($discountData);
		if (empty($fields) || !is_array($fields))
		{
			$process = false;
			$result->addError(new Main\Entity\EntityError(
				Loc::getMessage('SALE_ORDER_DISCOUNT_ERR_BAD_PREPARE_DISCOUNT'),
				self::ERROR_ID
			));
		}

		if ($process)
		{
			$hash = Internals\OrderDiscountTable::calculateHash($fields);
			if ($hash === false)
			{
				$process = false;
				$result->addError(new Main\Entity\EntityError(
					Loc::getMessage('SALE_ORDER_DISCOUNT_ERR_BAD_DISCOUNT_HASH'),
					self::ERROR_ID
				));
			}
		}

		if ($process)
		{
			if (!isset(self::$migrateDiscountsCache[$hash]))
			{
				$orderDiscountIterator = Internals\OrderDiscountTable::getList(array(
					'select' => array('*'),
					'filter' => array('=DISCOUNT_HASH' => $hash)
				));
				if ($orderDiscount = $orderDiscountIterator->fetch())
					self::$migrateDiscountsCache[$hash] = $orderDiscount;
				unset($orderDiscount, $orderDiscountIterator);
			}
			if (!empty(self::$migrateDiscountsCache[$hash]))
			{
				$resultData = self::$migrateDiscountsCache[$hash];
				$resultData['ID'] = (int)$resultData['ID'];
				$resultData['NAME'] = (string)$resultData['NAME'];
				$resultData['ORDER_DISCOUNT_ID'] = $resultData['ID'];
				$result->setId($resultData['ID']);
			}
			else
			{
				$fields['DISCOUNT_HASH'] = $hash;
				$fields['ACTIONS_DESCR'] = array();
				if (isset($discountData['ACTIONS_DESCR']))
					$fields['ACTIONS_DESCR'] = $discountData['ACTIONS_DESCR'];
				$tableResult = Internals\OrderDiscountTable::add($fields);
				if ($tableResult->isSuccess())
				{
					$resultData = $fields;
					$resultData['ID'] = (int)$tableResult->getId();
					$resultData['NAME'] = (string)$resultData['NAME'];
					$resultData['ORDER_DISCOUNT_ID'] = $resultData['ID'];
					$result->setId($resultData['ID']);
				}
				else
				{
					$process = false;
					$result->addErrors($tableResult->getErrors());
				}
				unset($tableResult, $fields);

				if ($process)
				{
					$moduleList = Internals\OrderDiscountTable::getDiscountModules($discountData);
					if (!empty($moduleList))
					{
						$resultModule = Internals\OrderModulesTable::saveOrderDiscountModules($resultData['ORDER_DISCOUNT_ID'], $moduleList);
						if (!$resultModule)
						{
							Internals\OrderDiscountTable::clearList($resultData['ORDER_DISCOUNT_ID']);
							$resultData = array();
							$process = false;
							$result->addError(new Main\Entity\EntityError(
								Loc::getMessage('SALE_ORDER_DISCOUNT_ERR_SAVE_DISCOUNT_MODULES'),
								self::ERROR_ID
							));
						}
						unset($resultModule);
					}
					unset($needDiscountModules, $moduleList);
				}
			}
		}

		if ($process)
			$result->setData($resultData);
		unset($resultData, $process);

		return $result;
	}
}