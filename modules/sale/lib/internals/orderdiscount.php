<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals;

Loc::loadMessages(__FILE__);

/**
 * Class OrderDiscountTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> MODULE_ID string(50) mandatory
 * <li> DISCOUNT_ID int mandatory
 * <li> NAME string(255) mandatory
 * <li> DISCOUNT_HASH string(32) mandatory
 * <li> CONDITIONS string optional
 * <li> UNPACK string optional
 * <li> ACTIONS string optional
 * <li> APPLICATION string optional
 * <li> USE_COUPONS bool mandatory
 * <li> SORT int mandatory
 * <li> PRIORITY int mandatory
 * <li> LAST_DISCOUNT bool mandatory
 * </ul>
 *
 * @package Bitrix\Sale\Internals
 **/

class OrderDiscountTable extends Main\Entity\DataManager
{
	protected static $requiredFields = array(
		'MODULE_ID',
		'DISCOUNT_ID',
		'NAME',
		'CONDITIONS',
		'UNPACK',
		'ACTIONS',
		'APPLICATION',
		'USE_COUPONS',
		'SORT',
		'PRIORITY',
		'LAST_DISCOUNT'
	);
	protected static $replaceFields = array(
		'DISCOUNT_ID' => 'ID',
		'CONDITIONS' => 'CONDITIONS_LIST',
		'ACTIONS' => 'ACTIONS_LIST',
		'MODULE_ID' => 'MODULE'
	);

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sale_order_discount';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ORDER_DISCOUNT_ENTITY_ID_FIELD')
			)),
			'MODULE_ID' => new Main\Entity\StringField('MODULE_ID', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateModuleId'),
				'title' => Loc::getMessage('ORDER_DISCOUNT_ENTITY_MODULE_ID_FIELD')
			)),
			'DISCOUNT_ID' => new Main\Entity\IntegerField('DISCOUNT_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ORDER_DISCOUNT_ENTITY_DISCOUNT_ID_FIELD')
			)),
			'NAME' => new Main\Entity\StringField('NAME', array(
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('ORDER_DISCOUNT_ENTITY_NAME_FIELD')
			)),
			'DISCOUNT_HASH' => new Main\Entity\StringField('DISCOUNT_HASH', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateDiscountHash'),
				'title' => Loc::getMessage('ORDER_DISCOUNT_ENTITY_DISCOUNT_HASH_FIELD')
			)),
			'CONDITIONS' => new Main\Entity\TextField('CONDITIONS', array(
				'serialized' => true,
				'title' => Loc::getMessage('ORDER_DISCOUNT_ENTITY_CONDITIONS_FIELD')
			)),
			'UNPACK' => new Main\Entity\TextField('UNPACK', array(
				'title' => Loc::getMessage('ORDER_DISCOUNT_ENTITY_UNPACK_FIELD')
			)),
			'ACTIONS' => new Main\Entity\TextField('ACTIONS', array(
				'serialized' => true,
				'title' => Loc::getMessage('ORDER_DISCOUNT_ENTITY_ACTIONS_FIELD')
			)),
			'APPLICATION' => new Main\Entity\TextField('APPLICATION', array(
				'title' => Loc::getMessage('ORDER_DISCOUNT_ENTITY_APPLICATION_FIELD')
			)),
			'USE_COUPONS' => new Main\Entity\BooleanField('USE_COUPONS', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('ORDER_DISCOUNT_ENTITY_USE_COUPONS_FIELD')
			)),
			'SORT' => new Main\Entity\IntegerField('SORT', array(
				'title' => Loc::getMessage('ORDER_DISCOUNT_ENTITY_SORT_FIELD')
			)),
			'PRIORITY' => new Main\Entity\IntegerField('PRIORITY', array(
				'title' => Loc::getMessage('ORDER_DISCOUNT_ENTITY_PRIORITY_FIELD')
			)),
			'LAST_DISCOUNT' => new Main\Entity\BooleanField('LAST_DISCOUNT', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('ORDER_DISCOUNT_ENTITY_LAST_DISCOUNT_FIELD')
			)),
			'ACTIONS_DESCR' => new Main\Entity\TextField('ACTIONS_DESCR',array(
				'serialized' => true,
				'title' => Loc::getMessage('ORDER_DISCOUNT_ENTITY_ACTIONS_DESCR_FIELD')
			))
		);
	}
	/**
	 * Returns validators for MODULE_ID field.
	 *
	 * @return array
	 */
	public static function validateModuleId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for DISCOUNT_HASH field.
	 *
	 * @return array
	 */
	public static function validateDiscountHash()
	{
		return array(
			new Main\Entity\Validator\Length(32, 32),
		);
	}

	/**
	 * Return discount id by hash.
	 *
	 * @param string $hash				Discount hash.
	 * @return int|bool
	 */
	public static function getDiscountByHash($hash)
	{
		$hash = (string)$hash;
		if ($hash == '')
			return false;
		$result = 0;
		$discountIterator = self::getList(array(
			'select' => array('ID', 'DISCOUNT_HASH'),
			'filter' => array('=DISCOUNT_HASH' => $hash)
		));
		if ($discount = $discountIterator->fetch())
			$result = (int)$discount['ID'];
		unset($discount, $discountIterator);
		return $result;
	}

	/**
	 * Calculate discount hash.
	 *
	 * @param array $discount			Discount data.
	 * @return bool|string
	 */
	public static function calculateHash($discount)
	{
		$hash = false;
		if (!empty($discount) && is_array($discount))
		{
			$fields = array();
			foreach (self::$requiredFields as $fieldName)
			{
				if (!isset($discount[$fieldName]))
					return $hash;
				$fields[$fieldName] = (
					is_array($discount[$fieldName])
					? $discount[$fieldName]
					: trim((string)$discount[$fieldName])
				);
			}
			if (!empty($fields))
				$hash = md5(serialize($fields));
		}
		return $hash;
	}

	/**
	 * Calculate hash for fields CONDITIONS and ACTIONS.
	 *
	 * @param array $discount			Discount data.
	 * @return bool|string
	 */
	public static function calculateRuleHash($discount)
	{
		$hash = false;
		if (!empty($discount) && is_array($discount))
		{
			if (!isset($discount['CONDITIONS']) || !isset($discount['ACTIONS']))
				return $hash;
			$fields = array(
				'CONDITIONS' => $discount['CONDITIONS'],
				'ACTIONS' => $discount['ACTIONS']
			);
			$hash = md5(serialize($fields));
		}
		return $hash;
	}

	/**
	 * Prepare discount data for save.
	 *
	 * @param array $discount			Discount data.
	 * @return array|bool
	 */
	public static function prepareDiscountData($discount)
	{
		$fields = false;
		if (!empty($discount) && is_array($discount))
		{
			foreach (self::$replaceFields as $dest => $src)
			{
				if (!isset($discount[$dest]) && isset($discount[$src]))
					$discount[$dest] = $discount[$src];
			}

			$fields = array();
			foreach (self::$requiredFields as $fieldName)
			{
				if (!isset($discount[$fieldName]))
					return false;
				$fields[$fieldName] = $discount[$fieldName];
			}
			unset($fieldName);
		}
		return $fields;
	}

	/**
	 * Return discount modules list.
	 *
	 * @param array $discount			Discount data.
	 * @return array
	 */
	public static function getDiscountModules($discount)
	{
		$result = array();
		$needDiscountModules = array();
		if (!empty($discount['MODULES']))
		{
			$needDiscountModules = (
				!is_array($discount['MODULES'])
				? array($discount['MODULES'])
				: $discount['MODULES']
			);
		}
		elseif (!empty($discount['HANDLERS']))
		{
			if (!empty($discount['HANDLERS']['MODULES']))
			{
				$needDiscountModules = (
				!is_array($discount['HANDLERS']['MODULES'])
					? array($discount['HANDLERS']['MODULES'])
					: $discount['HANDLERS']['MODULES']
				);
			}
		}
		if (!empty($needDiscountModules))
		{
			foreach ($needDiscountModules as &$module)
			{
				$module = trim((string)$module);
				if (!empty($module))
					$result[] = $module;
			}
			unset($module);
		}
		return $result;
	}

	/**
	 * Remove discount list.
	 *
	 * @param array|int $discount			Order discount list.
	 * @return void
	 */
	public static function clearList($discount)
	{
		if (!is_array($discount))
			$discount = array($discount);
		Main\Type\Collection::normalizeArrayValuesByInt($discount, true);
		if (empty($discount))
			return;
		$conn = Main\Application::getConnection();
		$helper = $conn->getSqlHelper();
		$discountRows = array_chunk($discount, 500);
		$query = 'delete from '.$helper->quote(self::getTableName()).' where '.$helper->quote('ID').' in (';
		foreach ($discountRows as &$row)
		{
			$conn->queryExecute($query.implode(', ', $row).')');
			OrderModulesTable::clearByDiscount($row);
		}
		unset($row, $query, $discountRows, $helper, $conn);
	}
}

/**
 * Class OrderCouponsTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ORDER_ID int mandatory
 * <li> BASKET_ID int mandatory
 * <li> ORDER_DISCOUNT_ID int mandatory
 * <li> COUPON string(32) mandatory
 * <li> TYPE int mandatory
 * </ul>
 *
 * @package Bitrix\Sale\Internals
 **/

class OrderCouponsTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sale_order_coupons';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ORDER_COUPONS_ENTITY_ID_FIELD')
			)),
			'ORDER_ID' => new Main\Entity\IntegerField('ORDER_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ORDER_COUPONS_ENTITY_ORDER_ID_FIELD')
			)),
			'ORDER_DISCOUNT_ID' => new Main\Entity\IntegerField('ORDER_DISCOUNT_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ORDER_COUPONS_ENTITY_ORDER_DISCOUNT_ID_FIELD')
			)),
			'COUPON' => new Main\Entity\StringField('COUPON', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateCoupon'),
				'title' => Loc::getMessage('ORDER_COUPONS_ENTITY_COUPON_FIELD')
			)),
			'TYPE' => new Main\Entity\IntegerField('TYPE', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateType'),
				'title' => Loc::getMessage('ORDER_COUPONS_ENTITY_TYPE_FIELD')
			)),
			'DATA' => new Main\Entity\TextField('DATA', array(
				'required' => true,
				'serialized' => true,
				'title' => Loc::getMessage('ORDER_COUPONS_ENTITY_DATA_FIELD')
			)),
			'COUPON_ID' => new Main\Entity\IntegerField('COUPON_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ORDER_COUPONS_ENTITY_COUPON_ID_FIELD')
			)),
			'ORDER_DISCOUNT' => new Main\Entity\ReferenceField(
				'ORDER_DISCOUNT',
				'Bitrix\Sale\Internals\OrderDiscount',
				array('=this.ORDER_DISCOUNT_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			)
		);
	}

	/**
	 * Returns validators for COUPON field.
	 *
	 * @return array
	 */
	public static function validateCoupon()
	{
		return array(
			new Main\Entity\Validator\Length(null, 32),
		);
	}

	/**
	 * Returns validators for TYPE field.
	 *
	 * @return array
	 */
	public static function validateType()
	{
		return array(
			array(__CLASS__, 'checkType')
		);
	}

	/**
	 * Check coupon type.
	 *
	 * @param int $value					Coupon type.
	 * @param array|int $primary			Primary key.
	 * @param array $row					Current data.
	 * @param Main\Entity\Field $field		Field object.
	 * @return bool|string
	 */
	public static function checkType($value, $primary, array $row, Main\Entity\Field $field)
	{
		if (Internals\DiscountCouponTable::isValidCouponType($value) || $value == Internals\DiscountCouponTable::TYPE_ARCHIVED)
			return true;

		return Loc::getMessage('ORDER_COUPONS_VALIDATOR_TYPE');
	}

	/**
	 * Remove order coupons.
	 *
	 * @param int $order			Order id.
	 * @return void
	 */
	public static function clearByOrder($order)
	{
		$order = (int)$order;
		if ($order <= 0)
			return;

		$conn = Main\Application::getConnection();
		$helper = $conn->getSqlHelper();
		$conn->queryExecute('delete from '.$helper->quote(self::getTableName()).' where '.$helper->quote('ORDER_ID').' = '.$order);
		unset($helper, $conn);
	}

	/**
	 * Remove coupon list.
	 *
	 * @param array|int $coupon			Order coupon list.
	 * @return void
	 */
	public static function clearList($coupon)
	{
		if (!is_array($coupon))
			$coupon = array($coupon);
		Main\Type\Collection::normalizeArrayValuesByInt($coupon, true);
		if (empty($coupon))
			return;
		$conn = Main\Application::getConnection();
		$helper = $conn->getSqlHelper();
		$couponRows = array_chunk($coupon, 500);
		$query = 'delete from '.$helper->quote(self::getTableName()).' where '.$helper->quote('ID').' in (';
		foreach ($couponRows as &$row)
		{
			$conn->queryExecute($query.implode(', ', $row).')');
		}
		unset($row, $query, $couponRows, $helper, $conn);
	}

	/**
	 * Save order in order coupons.
	 *
	 * @param array $coupons			Coupons id list.
	 * @param int $order				Order id.
	 * @return void
	 */
	public static function applyOrder($coupons, $order)
	{
		$order = (int)$order;
		if ($order <= 0 || empty($coupons))
			return;
		if (!is_array($coupons))
			$coupons = array($coupons);
		Main\Type\Collection::normalizeArrayValuesByInt($coupons, true);
		if (empty($coupons))
			return;
		$conn = Main\Application::getConnection();
		$helper = $conn->getSqlHelper();
		$conn->queryExecute(
			'update '.$helper->quote(self::getTableName()).
			' set '.$helper->quote('ORDER_ID').' = '.$order.' where '.
			$helper->quote('ID').' in ('.implode(',', $coupons).') and '.
			$helper->quote('ORDER_ID').' = 0'
		);
	}
}

/**
 * Class OrderModulesTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ORDER_DISCOUNT_ID int mandatory
 * <li> MODULE_ID string(50) mandatory
 * </ul>
 *
 * @package Bitrix\Sale\Internals
 **/

class OrderModulesTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sale_order_modules';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ORDER_MODULES_ENTITY_ID_FIELD')
			)),
			'ORDER_DISCOUNT_ID' => new Main\Entity\IntegerField('ORDER_DISCOUNT_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ORDER_MODULES_ENTITY_ORDER_DISCOUNT_ID_FIELD')
			)),
			'MODULE_ID' => new Main\Entity\StringField('MODULE_ID', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateModuleId'),
				'title' => Loc::getMessage('ORDER_MODULES_ENTITY_MODULE_ID_FIELD')
			))
		);
	}
	/**
	 * Returns validators for MODULE_ID field.
	 *
	 * @return array
	 */
	public static function validateModuleId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * Save order discount modules.
	 *
	 * @param int $discountId			Order discount id.
	 * @param array $moduleList			Module list.
	 * @return bool
	 */
	public static function saveOrderDiscountModules($discountId, $moduleList)
	{
		$discountId = (int)$discountId;
		if ($discountId <= 0)
			return false;
		if (!is_array($moduleList))
			$moduleList = array($moduleList);

		$error = false;

		$conn = Main\Application::getConnection();
		$helper = $conn->getSqlHelper();
		$query = 'delete from '.$helper->quote(self::getTableName()).' where '.$helper->quote('ORDER_DISCOUNT_ID').' = '.$discountId;
		$conn->queryExecute($query);
		foreach ($moduleList as &$module)
		{
			$module = (string)$module;
			if (empty($module))
				continue;
			$fields = array(
				'ORDER_DISCOUNT_ID' => $discountId,
				'MODULE_ID' => $module
			);
			$result = self::add($fields);
			if (!$result->isSuccess())
			{
				$error = true;
				break;
			}
		}
		unset($result, $module);
		if ($error)
			$conn->queryExecute($query);

		unset($query, $helper, $conn);
		return !$error;
	}

	/**
	 * Remove module discount list.
	 *
	 * @param array|int $discount			Discount list.
	 * @return void
	 */
	public static function clearByDiscount($discount)
	{
		if (!is_array($discount))
			$discount = array($discount);
		Main\Type\Collection::normalizeArrayValuesByInt($discount, true);
		if (empty($discount))
			return;
		$conn = Main\Application::getConnection();
		$helper = $conn->getSqlHelper();
		$discountRows = array_chunk($discount, 500);
		$query = 'delete from '.$helper->quote(self::getTableName()).' where '.$helper->quote('ORDER_DISCOUNT_ID').' in (';
		foreach ($discountRows as &$row)
		{
			$conn->queryExecute($query.implode(', ', $row).')');
		}
		unset($row, $query, $discountRows, $helper, $conn);
	}
}

/**
 * Class OrderRulesTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ORDER_DISCOUNT_ID int mandatory
 * <li> ORDER_ID int mandatory
 * <li> ENTITY_TYPE int mandatory
 * <li> ENTITY_ID int mandatory
 * <li> ENTITY_VALUE string(255) optional
 * <li> COUPON_ID int mandatory
 * <li> APPLY bool mandatory
 * </ul>
 *
 * @package Bitrix\Sale\Internals
 **/

class OrderRulesTable extends Main\Entity\DataManager
{
	const ENTITY_TYPE_BASKET = 0x0001;
	const ENTITY_TYPE_DELIVERY = 0x0002;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sale_order_rules';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ORDER_RULES_ENTITY_ID_FIELD')
			)),
			'MODULE_ID' => new Main\Entity\StringField('MODULE_ID', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateModuleId'),
				'title' => Loc::getMessage('ORDER_RULES_ENTITY_MODULE_ID_FIELD')
			)),
			'ORDER_DISCOUNT_ID' => new Main\Entity\IntegerField('ORDER_DISCOUNT_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ORDER_RULES_ENTITY_ORDER_DISCOUNT_ID_FIELD')
			)),
			'ORDER_ID' => new Main\Entity\IntegerField('ORDER_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ORDER_RULES_ENTITY_ORDER_ID_FIELD')
			)),
			'ENTITY_TYPE' => new Main\Entity\EnumField('ENTITY_TYPE', array(
				'required' => true,
				'values' => array(self::ENTITY_TYPE_BASKET, self::ENTITY_TYPE_DELIVERY),
				'title' => Loc::getMessage('ORDER_RULES_ENTITY_ENTITY_TYPE_FIELD')
			)),
			'ENTITY_ID' => new Main\Entity\IntegerField('ENTITY_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ORDER_RULES_ENTITY_ENTITY_ID_FIELD')
			)),
			'ENTITY_VALUE' => new Main\Entity\StringField('ENTITY_VALUE', array(
				'validation' => array(__CLASS__, 'validateEntityValue'),
				'title' => Loc::getMessage('ORDER_RULES_ENTITY_ENTITY_VALUE_FIELD')
			)),
			'COUPON_ID' => new Main\Entity\IntegerField('COUPON_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ORDER_RULES_ENTITY_COUPON_ID_FIELD')
			)),
			'APPLY' => new Main\Entity\BooleanField('APPLY', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('ORDER_RULES_ENTITY_APPLY_FIELD')
			)),
			'ORDER_DISCOUNT' => new Main\Entity\ReferenceField(
				'ORDER_DISCOUNT',
				'Bitrix\Sale\Internals\OrderDiscount',
				array('=this.ORDER_DISCOUNT_ID' => 'ref.ID'),
				array('join_type' => 'INNER')
			),
			'DESCR' => new Main\Entity\ReferenceField(
				'DESCR',
				'Bitrix\Sale\Internals\OrderRulesDescr',
				array('=this.ID' => 'ref.RULE_ID'),
				array('join_type' => 'LEFT')
			)
		);
	}

	/**
	 * Returns validators for MODULE_ID field.
	 *
	 * @return array
	 */
	public static function validateModuleId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for ENTITY_VALUE field.
	 *
	 * @return array
	 */
	public static function validateEntityValue()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Clear apply list by basket item.
	 *
	 * @param int $basket			Basket id.
	 * @return void
	 */
	public static function clearByBasketItem($basket)
	{
		$basket = (int)$basket;
		if ($basket <= 0)
			return;

		self::clear(array('=ENTITY_TYPE' => self::ENTITY_TYPE_BASKET, '=ENTITY_ID' => $basket, '=ORDER_ID' => 0));
	}

	/**
	 * Clear sale discount rules.
	 *
	 * @param array $basketList				Basket id.
	 * @return void
	 */
	public static function clearBasketSaleDiscount($basketList)
	{
		if (empty($basketList) || !is_array($basketList))
			return;
		Main\Type\Collection::normalizeArrayValuesByInt($basketList, true);
		if (empty($basketList))
			return;

		self::clear(array('=MODULE_ID' => 'sale', '=ENTITY_TYPE' => self::ENTITY_TYPE_BASKET, '@ENTITY_ID' => $basketList, '=ORDER_ID' => 0));
	}

	/**
	 * Clear rules by order.
	 *
	 * @param int $order				Order id.
	 * @return void
	 */
	public static function clearByOrder($order)
	{
		$order = (int)$order;
		if ($order <= 0)
			return;

		self::clear(array('=ORDER_ID' => $order));
	}

	/**
	 * Check use discount list for other basket items. Return list of unused order discount id.
	 *
	 * @param array &$orderDiscountList				Order discount list.
	 * @param array &$ruleList						Rule id list.
	 * @return void
	 */
	protected static function checkUseOrderDiscounts(&$orderDiscountList, &$ruleList)
	{
		if (empty($orderDiscountList) || empty($ruleList))
			return;

		$discountIterator = self::getList(array(
			'select' => array('ORDER_DISCOUNT_ID', new Main\Entity\ExpressionField('CNT', 'COUNT(*)')),
			'filter' => array('!@ID' => $ruleList, '@ORDER_DISCOUNT_ID' => $orderDiscountList),
			'group' => array('DISCOUNT_ID')
		));
		while ($discount = $discountIterator->fetch())
		{
			$discount['CNT'] = (int)$discount['CNT'];
			if ($discount['CNT'] > 0)
			{
				$discount['ORDER_DISCOUNT_ID'] = (int)$discount['ORDER_DISCOUNT_ID'];
				unset($orderDiscountList[$discount['ORDER_DISCOUNT_ID']]);
			}
		}
		unset($discount, $discountIterator);
	}

	/**
	 * Check use coupon discount list for other basket items. Return list of unused order discount.
	 *
	 * @param array &$orderCouponList			Order coupon id list.
	 * @param array &$ruleList					Rule id list.
	 * @return void
	 */
	protected static function checkUseOrderCoupons(&$orderCouponList, &$ruleList)
	{
		if (empty($orderCouponList) || empty($ruleList))
			return;

		$couponIterator = self::getList(array(
			'select' => array('COUPON_ID', new Main\Entity\ExpressionField('CNT', 'COUNT(*)')),
			'filter' => array('!@ID' => $ruleList, '@COUPON_ID' => $orderCouponList),
			'group' => array('COUPON_ID')
		));
		while ($coupon = $couponIterator->fetch())
		{
			$coupon['CNT'] = (int)$coupon['CNT'];
			if ($coupon['CNT'] > 0)
			{
				$coupon['COUPON_ID'] = (int)$coupon['COUPON_ID'];
				unset($orderCouponList[$coupon['COUPON_ID']]);
			}
		}
		unset($coupon, $couponIterator);
	}

	/**
	 * Clear rule list.
	 *
	 * @param array $filter				Filter for clear rules.
	 * @return void
	 */
	protected static function clear($filter)
	{
		if (empty($filter) || !is_array($filter))
			return;

		$ruleList = array();
		$orderDiscountList = array();
		$orderCouponList = array();
		$ruleIterator = self::getList(array(
			'select' => array('ID', 'ORDER_DISCOUNT_ID', 'COUPON_ID'),
			'filter' => $filter
		));
		while ($rule = $ruleIterator->fetch())
		{
			$rule['ID'] = (int)$rule['ID'];
			$rule['ORDER_DISCOUNT_ID'] = (int)$rule['ORDER_DISCOUNT_ID'];
			$rule['COUPON_ID'] = (int)$rule['COUPON_ID'];
			$ruleList[] = $rule['ID'];
		}
		unset($rule, $ruleIterator);
		if (empty($ruleList))
			return;

		$conn = Main\Application::getConnection();
		$helper = $conn->getSqlHelper();
		$ruleRows = array_chunk($ruleList, 500);
		$mainQuery = 'delete from '.$helper->quote(self::getTableName()).' where '.$helper->quote('ID').' in (';
		$descrQuery = 'delete from '.$helper->quote(OrderRulesDescrTable::getTableName()).' where '.$helper->quote('RULE_ID').' in (';
		foreach ($ruleRows as &$row)
		{
			$conn->queryExecute($mainQuery.implode(', ', $row).')');
			$conn->queryExecute($descrQuery.implode(', ', $row).')');
		}
		unset($row, $descrQuery, $mainQuery, $ruleRows, $ruleList);
		unset($helper, $conn);

		if (!empty($orderDiscountList))
			OrderDiscountTable::clearList($orderDiscountList);
		unset($orderDiscountList);
		if (!empty($orderCouponList))
			OrderCouponsTable::clearList($orderCouponList);
		unset($orderCouponList);
	}
}

/**
 * Class OrderDiscountDataTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ORDER_ID int mandatory
 * <li> ENTITY_TYPE int mandatory
 * <li> ENTITY_ID int mandatory
 * <li> ENTITY_VALUE string(255) optional
 * <li> ENTITY_DATA string mandatory
 * </ul>
 *
 * @package Bitrix\Sale\Internals
 **/

class OrderDiscountDataTable extends Main\Entity\DataManager
{
	const ENTITY_TYPE_BASKET = 0x0001;
	const ENTITY_TYPE_DELIVERY = 0x0002;
	const ENTITY_TYPE_SHIPMENT = 0x0004;
	const ENTITY_TYPE_DISCOUNT = 0x0008;
	const ENTITY_TYPE_ORDER = 0x0010;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sale_order_discount_data';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ORDER_DISCOUNT_DATA_ENTITY_ID_FIELD')
			)),
			'ORDER_ID' => new Main\Entity\IntegerField('ORDER_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ORDER_DISCOUNT_DATA_ENTITY_ORDER_ID_FIELD')
			)),
			'ENTITY_TYPE' => new Main\Entity\EnumField('ENTITY_TYPE', array(
				'required' => true,
				'values' => array(
					self::ENTITY_TYPE_BASKET,
					self::ENTITY_TYPE_DELIVERY,
					self::ENTITY_TYPE_SHIPMENT,
					self::ENTITY_TYPE_DISCOUNT,
					self::ENTITY_TYPE_ORDER
				),
				'title' => Loc::getMessage('ORDER_DISCOUNT_DATA_ENTITY_ENTITY_TYPE_FIELD')
			)),
			'ENTITY_ID' => new Main\Entity\IntegerField('ENTITY_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ORDER_DISCOUNT_DATA_ENTITY_ENTITY_ID_FIELD')
			)),
			'ENTITY_VALUE' => new Main\Entity\StringField('ENTITY_VALUE', array(
				'validation' => array(__CLASS__, 'validateEntityValue'),
				'title' => Loc::getMessage('ORDER_DISCOUNT_DATA_ENTITY_ENTITY_VALUE_FIELD')
			)),
			'ENTITY_DATA' => new Main\Entity\TextField('ENTITY_DATA', array(
				'required' => true,
				'serialized' => true,
				'title' => Loc::getMessage('ORDER_DISCOUNT_DATA_ENTITY_ENTITY_DATA_FIELD')
			))
		);
	}
	/**
	 * Returns validators for ENTITY_VALUE field.
	 *
	 * @return array
	 */
	public static function validateEntityValue()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Upsert basket item data.
	 *
	 * @param int $order				Order id.
	 * @param int $basket				Basket id.
	 * @param array $data				Data list.
	 * @param bool $clear				Clear old values or update.
	 * @return bool
	 */
	public static function saveBasketItemData($order, $basket, $data, $clear = false)
	{
		$order = (int)$order;
		$basket = (int)$basket;
		if ($order < 0 || $basket <= 0 || empty($data) || !is_array($data))
			return false;
		$clear = ($clear === true);
		$id = 0;
		$fields = array(
			'ENTITY_DATA' => $data
		);
		$dataIterator = self::getList(array(
			'select' => array('ID', 'ENTITY_DATA'),
			'filter' => array('=ORDER_ID' => $order, '=ENTITY_TYPE' => self::ENTITY_TYPE_BASKET, '=ENTITY_ID' => $basket)
		));
		if ($oldData = $dataIterator->fetch())
		{
			if (!$clear && !empty($oldData['ENTITY_DATA']))
				$fields['ENTITY_DATA'] = array_merge($oldData['ENTITY_DATA'], $fields['ENTITY_DATA']);
			$id = (int)$oldData['ID'];
		}
		unset($oldData, $dataIterator);
		if ($id > 0)
		{
			$result = self::update($id, $fields);
		}
		else
		{
			$fields['ORDER_ID'] = $order;
			$fields['ENTITY_TYPE'] = self::ENTITY_TYPE_BASKET;
			$fields['ENTITY_ID'] = $basket;
			$fields['ENTITY_VALUE'] = $basket;
			$result = self::add($fields);
			if ($result->isSuccess())
				$id = (int)$result->getId();
		}
		unset($fields, $id);
		return $result->isSuccess();
	}

	/**
	 * Clear data for basket item.
	 *
	 * @param int $basket			Basket id.
	 * @return bool
	 */
	public static function clearByBasketItem($basket)
	{
		$basket = (int)$basket;
		if ($basket <= 0)
			return false;

		$conn = Main\Application::getConnection();
		$helper = $conn->getSqlHelper();
		$conn->queryExecute(
			'delete from '.$helper->quote(self::getTableName()).
			' where '.$helper->quote('ENTITY_TYPE').' = '.self::ENTITY_TYPE_BASKET.
			' and '.$helper->quote('ENTITY_ID').' = '.$basket
		);
		unset($helper, $conn);
		return true;
	}

	/**
	 * Delete data by order.
	 *
	 * @param int $order		Order id.
	 * @return bool
	 */
	public static function clearByOrder($order)
	{
		$order = (int)$order;
		if ($order <= 0)
			return false;

		$conn = Main\Application::getConnection();
		$helper = $conn->getSqlHelper();
		$conn->queryExecute('delete from '.$helper->quote(self::getTableName()).' where '.$helper->quote('ORDER_ID').' = '.$order);
		unset($helper, $conn);

		return true;
	}

	/**
	 * Clear data by discount list.
	 *
	 * @param array|int $discountList			Discount ids list.
	 * @return bool
	 */
	public static function clearByDiscount($discountList)
	{
		if (!is_array($discountList))
			$discountList = array($discountList);
		if (empty($discountList))
			return false;
		Main\Type\Collection::normalizeArrayValuesByInt($discountList, true);
		if (empty($discountList))
			return false;

		$conn = Main\Application::getConnection();
		$helper = $conn->getSqlHelper();
		$conn->queryExecute(
			'delete from '.$helper->quote(self::getTableName()).
			' where '.$helper->quote('ENTITY_TYPE').' = '.self::ENTITY_TYPE_DISCOUNT.
			' and '.$helper->quote('ENTITY_ID').' in ('.implode(',', $discountList).')'
		);
		unset($helper, $conn);

		return true;
	}
}

/**
 * Class OrderRulesDescrTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> MODULE_ID string(50) mandatory
 * <li> ORDER_DISCOUNT_ID int mandatory
 * <li> ORDER_ID int mandatory
 * <li> RULE_ID int mandatory
 * <li> DESCR string mandatory
 * </ul>
 *
 * @package Bitrix\Sale\Internals
 **/

class OrderRulesDescrTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sale_order_rules_descr';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ORDER_RULES_DESCR_ENTITY_ID_FIELD')
			)),
			'MODULE_ID' => new Main\Entity\StringField('MODULE_ID', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateModuleId'),
				'title' => Loc::getMessage('ORDER_RULES_DESCR_ENTITY_MODULE_ID_FIELD')
			)),
			'ORDER_DISCOUNT_ID' => new Main\Entity\IntegerField('ORDER_DISCOUNT_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ORDER_RULES_DESCR_ENTITY_ORDER_DISCOUNT_ID_FIELD')
			)),
			'ORDER_ID' => new Main\Entity\IntegerField('ORDER_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ORDER_RULES_DESCR_ENTITY_ORDER_ID_FIELD')
			)),
			'RULE_ID' => new Main\Entity\IntegerField('RULE_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ORDER_RULES_DESCR_ENTITY_RULE_ID_FIELD')
			)),
			'DESCR' => new Main\Entity\TextField('DESCR', array(
				'required' => true,
				'serialized' => true,
				'title' => Loc::getMessage('ORDER_RULES_DESCR_ENTITY_DESCR_FIELD')
			))
		);
	}

	/**
	 * Returns validators for MODULE_ID field.
	 *
	 * @return array
	 */
	public static function validateModuleId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
}