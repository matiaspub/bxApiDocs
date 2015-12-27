<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;

Loc::loadMessages(__FILE__);

/**
 * Class DiscountCouponTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DISCOUNT_ID int mandatory
 * <li> ACTIVE bool optional default 'Y'
 * <li> ACTIVE_FROM datetime optional
 * <li> ACTIVE_TO datetime optional
 * <li> COUPON string(32) mandatory
 * <li> TYPE int mandatory
 * <li> MAX_USE int mandatory
 * <li> USE_COUNT int mandatory
 * <li> USER_ID int mandatory
 * <li> DATE_APPLY datetime optional
 * <li> TIMESTAMP_X datetime optional
 * <li> MODIFIED_BY int optional
 * <li> DATE_CREATE datetime optional
 * <li> CREATED_BY int optional
 * <li> DESCRIPTION text optional
 * <li> CREATED_BY_USER reference to {@link \Bitrix\Main\UserTable}
 * <li> MODIFIED_BY_USER reference to {@link \Bitrix\Main\UserTable}
 * <li> DISCOUNT reference to {@link \Bitrix\Sale\Internals\DiscountTable}
 * </ul>
 *
 * @package Bitrix\Sale\Internals
 **/

class DiscountCouponTable extends Main\Entity\DataManager
{
	const TYPE_UNKNOWN = 0x0000;
	const TYPE_BASKET_ROW = 0x0001;
	const TYPE_ONE_ORDER = 0x0002;
	const TYPE_MULTI_ORDER = 0x0004;
	const TYPE_ARCHIVED = 0x0008;

	const EVENT_ON_GENERATE_COUPON = 'onGenerateCoupon';
	const EVENT_ON_AFTER_DELETE_DISCOUNT = 'onAfterDeleteDiscountCoupons';

	protected static $discountCheckList = array();
	protected static $checkDiscountCouponsUse = 0;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sale_discount_coupon';
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
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_ID_FIELD')
			)),
			'DISCOUNT_ID' => new Main\Entity\IntegerField('DISCOUNT_ID', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateDiscountId'),
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_DISCOUNT_ID_FIELD')
			)),
			'ACTIVE' => new Main\Entity\BooleanField('ACTIVE', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_ACTIVE_FIELD')
			)),
			'ACTIVE_FROM' => new Main\Entity\DatetimeField('ACTIVE_FROM', array(
				'default_value' => null,
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_ACTIVE_FROM_FIELD')
			)),
			'ACTIVE_TO' => new Main\Entity\DatetimeField('ACTIVE_TO', array(
				'default_value' => null,
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_ACTIVE_TO_FIELD')
			)),
			'COUPON' => new Main\Entity\StringField('COUPON', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateCoupon'),
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_COUPON_FIELD')
			)),
			'TYPE' => new Main\Entity\IntegerField('TYPE', array(
				'validation' => array(__CLASS__, 'validateType'),
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_TYPE_FIELD')
			)),
			'MAX_USE' => new Main\Entity\IntegerField('MAX_USE', array(
				'default_value' => 0,
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_MAX_USE_FIELD')
			)),
			'USE_COUNT' => new Main\Entity\IntegerField('USE_COUNT', array(
				'default_value' => 0,
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_USE_COUNT_FIELD')
			)),
			'USER_ID' => new Main\Entity\IntegerField('USER_ID', array(
				'default_value' => 0,
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_USER_ID_FIELD')
			)),
			'DATE_APPLY' => new Main\Entity\DatetimeField('DATE_APPLY', array(
				'default_value' => null,
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_DATE_APPLY_FIELD')
			)),
			'TIMESTAMP_X' => new Main\Entity\DatetimeField('TIMESTAMP_X', array(
				'default_value' => new Main\Type\DateTime(),
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_TIMESTAMP_X_FIELD')
			)),
			'MODIFIED_BY' => new Main\Entity\IntegerField('MODIFIED_BY', array(
				'default_value' => null,
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_MODIFIED_BY_FIELD')
			)),
			'DATE_CREATE' => new Main\Entity\DatetimeField('DATE_CREATE', array(
				'default_value' => new Main\Type\DateTime(),
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_DATE_CREATE_FIELD')
			)),
			'CREATED_BY' => new Main\Entity\IntegerField('CREATED_BY', array(
				'default_value' => null,
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_CREATED_BY_FIELD')
			)),
			'DESCRIPTION' => new Main\Entity\TextField('DESCRIPTION', array(
				'default_value' => null,
				'title' => Loc::getMessage('DISCOUNT_COUPON_ENTITY_DESCRIPTION_FIELD')
			)),
			'CREATED_BY_USER' => new Main\Entity\ReferenceField(
				'CREATED_BY_USER',
				'Bitrix\Main\User',
				array('=this.CREATED_BY' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
			'MODIFIED_BY_USER' => new Main\Entity\ReferenceField(
				'MODIFIED_BY_USER',
				'Bitrix\Main\User',
				array('=this.MODIFIED_BY' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
			'DISCOUNT' => new Main\Entity\ReferenceField(
				'DISCOUNT',
				'Bitrix\Sale\Internals\Discount',
				array('=this.DISCOUNT_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			)
		);
	}

	/**
	 * Returns validators for DISCOUNT_ID field.
	 *
	 * @return array
	 */
	public static function validateDiscountId()
	{
		return array(
			array(__CLASS__, 'checkDiscountId')
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
			array(__CLASS__, 'checkCoupon')
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
	 * Check discount id.
	 *
	 * @param int $value					Discount id.
	 * @param array|int $primary			Primary key.
	 * @param array $row					Current data.
	 * @param Main\Entity\Field $field		Field object.
	 * @return bool|string
	 */
	public static function checkDiscountId($value, $primary, array $row, Main\Entity\Field $field)
	{
		if ((int)$value <= 0)
			return Loc::getMessage('DISCOUNT_COUPON_VALIDATOR_DISCOUNT_ID');

		return true;
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
		if (
			$value == self::TYPE_BASKET_ROW
			|| $value == self::TYPE_ONE_ORDER
			|| $value == self::TYPE_MULTI_ORDER
		)
			return true;

		return Loc::getMessage('DISCOUNT_COUPON_VALIDATOR_TYPE');
	}

	/**
	 * Check coupon - unique and exist.
	 *
	 * @param int $value					Coupon.
	 * @param array|int $primary			Primary key.
	 * @param array $row					Current data.
	 * @param Main\Entity\Field $field		Field object.
	 * @return bool|string
	 */
	public static function checkCoupon($value, $primary, array $row, Main\Entity\Field $field)
	{
		$value = trim((string)$value);
		if ($value == '')
			return Loc::getMessage('DISCOUNT_COUPON_VALIDATOR_COUPON_EMPTY');

		$existCoupon = Sale\DiscountCouponsManager::isExist($value);
		if (!empty($existCoupon))
		{
			$currentId = (int)(is_array($primary) ? $primary['ID'] : $primary);
			if ($existCoupon['MODULE'] != 'sale' || $currentId != $existCoupon['ID'])
				return Loc::getMessage('DISCOUNT_COUPON_VALIDATOR_COUPON_EXIST');
		}
		return true;
	}

	/**
	 * Default onBeforeAdd handler. Absolutely necessary.
	 *
	 * @param Main\Entity\Event $event		Current data for add.
	 * @return Main\Entity\EventResult
	 */
	public static function onBeforeAdd(Main\Entity\Event $event)
	{
		$result = new Main\Entity\EventResult;
		$data = $event->getParameter('fields');

		$modifyFieldList = array();
		self::setUserID($modifyFieldList, $data, array('CREATED_BY', 'MODIFIED_BY'));
		self::setTimestamp($modifyFieldList, $data, array('DATE_CREATE', 'TIMESTAMP_X'));

		if (!empty($modifyFieldList))
			$result->modifyFields($modifyFieldList);
		unset($modifyFieldList);

		return $result;
	}

	/**
	 * Default onAfterAdd handler. Absolutely necessary.
	 *
	 * @param Main\Entity\Event $event		Current data for add.
	 * @return void
	 */
	public static function onAfterAdd(Main\Entity\Event $event)
	{
		if (!self::isCheckedCouponsUse())
			return;
		$data = $event->getParameter('fields');
		$data['DISCOUNT_ID'] = (int)$data['DISCOUNT_ID'];
		self::$discountCheckList[$data['DISCOUNT_ID']] = $data['DISCOUNT_ID'];
		self::updateUseCoupons();
	}

	/**
	 * Default onBeforeUpdate handler. Absolutely necessary.
	 *
	 * @param Main\Entity\Event $event		Current data for update.
	 * @return Main\Entity\EventResult
	 */
	public static function onBeforeUpdate(Main\Entity\Event $event)
	{
		$result = new Main\Entity\EventResult;
		$data = $event->getParameter('fields');

		$modifyFieldList = array();
		self::setUserID($modifyFieldList, $data, array('MODIFIED_BY'));
		self::setTimestamp($modifyFieldList, $data, array('TIMESTAMP_X'));

		if (!empty($modifyFieldList))
			$result->modifyFields($modifyFieldList);
		unset($modifyFieldList);

		return $result;
	}

	/**
	 * Default onUpdate handler. Absolutely necessary.
	 *
	 * @param Main\Entity\Event $event		Current data for update.
	 * @return void
	 */
	public static function onUpdate(Main\Entity\Event $event)
	{
		if (!self::isCheckedCouponsUse())
			return;
		$data = $event->getParameter('fields');
		if (isset($data['DISCOUNT_ID']))
		{
			$data['DISCOUNT_ID'] = (int)$data['DISCOUNT_ID'];
			$id = $event->getParameter('id');
			$couponIterator = self::getList(array(
				'select' => array('ID', 'DISCOUNT_ID'),
				'filter' => array('=ID' => $id)
			));
			if ($coupon = $couponIterator->fetch())
			{
				$coupon['DISCOUNT_ID'] = (int)$coupon['DISCOUNT_ID'];
				if ($coupon['DISCOUNT_ID'] !== $data['DISCOUNT_ID'])
				{
					self::$discountCheckList[$data['DISCOUNT_ID']] = $data['DISCOUNT_ID'];
					self::$discountCheckList[$coupon['DISCOUNT_ID']] = $coupon['DISCOUNT_ID'];
				}
			}
			unset($coupon, $couponIterator);
		}
	}

	/**
	 * Default onAfterUpdate handler. Absolutely necessary.
	 *
	 * @param Main\Entity\Event $event		Current data for update.
	 * @return void
	 */
	public static function onAfterUpdate(Main\Entity\Event $event)
	{
		self::updateUseCoupons();
	}

	/**
	 * Default onDelete handler. Absolutely necessary.
	 *
	 * @param Main\Entity\Event $event		Current data for delete.
	 * @return void
	 */
	public static function onDelete(Main\Entity\Event $event)
	{
		if (!self::isCheckedCouponsUse())
			return;
		$id = $event->getParameter('id');
		$couponIterator = self::getList(array(
			'select' => array('ID', 'DISCOUNT_ID'),
			'filter' => array('=ID' => $id)
		));
		if ($coupon = $couponIterator->fetch())
		{
			$coupon['DISCOUNT_ID'] = (int)$coupon['DISCOUNT_ID'];
			self::$discountCheckList[$coupon['DISCOUNT_ID']] = $coupon['DISCOUNT_ID'];
		}
		unset($coupon, $couponIterator);
	}

	/**
	 * Default onAfterDelete handler. Absolutely necessary.
	 *
	 * @param Main\Entity\Event $event		Current data for delete.
	 * @return void
	 */
	public static function onAfterDelete(Main\Entity\Event $event)
	{
		self::updateUseCoupons();
	}

	/**
	 * Returns coupon types list.
	 *
	 * @param bool $extendedMode			Get type ids or ids with title.
	 * @return array
	 */
	public static function getCouponTypes($extendedMode = false)
	{
		$extendedMode = ($extendedMode === true);
		if ($extendedMode)
		{
			return array(
				self::TYPE_BASKET_ROW => Loc::getMessage('DISCOUNT_COUPON_TABLE_TYPE_BASKET_ROW'),
				self::TYPE_ONE_ORDER => Loc::getMessage('DISCOUNT_COUPON_TABLE_TYPE_ONE_ORDER'),
				self::TYPE_MULTI_ORDER => Loc::getMessage('DISCOUNT_COUPON_TABLE_TYPE_MULTI_ORDER')
			);
		}
		return array(self::TYPE_BASKET_ROW, self::TYPE_ONE_ORDER, self::TYPE_MULTI_ORDER);
	}

	/**
	 * Disable checking use coupons for discount before multiuse add/update/delete.
	 *
	 * @return void
	 */
	public static function disableCheckCouponsUse()
	{
		self::$checkDiscountCouponsUse--;
	}

	/**
	 * Enable checking use coupons for discount after multiuse add/update/delete.
	 *
	 * @return void
	 */
	public static function enableCheckCouponsUse()
	{
		self::$checkDiscountCouponsUse++;
	}

	/**
	 * Returns current checking use coupons mode.
	 *
	 * @return bool
	 */
	public static function isCheckedCouponsUse()
	{
		return (self::$checkDiscountCouponsUse >= 0);
	}

	/**
	 * Clear discount list for update use coupons flag.
	 *
	 * @return void
	 */
	public static function clearDiscountCheckList()
	{
		self::$discountCheckList = array();
	}

	/**
	 * Fill discount list for update use coupons flag.
	 *
	 * @param array|int $discountList			Discount ids for check.
	 * @return void
	 */
	public static function setDiscountCheckList($discountList)
	{
		if (!is_array($discountList))
			$discountList = array($discountList);
		if (!empty($discountList))
			self::$discountCheckList = (empty(self::$discountCheckList) ? $discountList : array_merge(self::$discountCheckList, $discountList));
	}

	/**
	 * Update use coupon flag for discount list.
	 *
	 * @return void
	 */
	public static function updateUseCoupons()
	{
		if (!self::isCheckedCouponsUse() || empty(self::$discountCheckList))
			return;

		Main\Type\Collection::normalizeArrayValuesByInt(self::$discountCheckList);
		if (empty(self::$discountCheckList))
			return;

		$withoutCoupons = array_fill_keys(self::$discountCheckList, true);
		$withCoupons = array();
		$couponIterator = DiscountCouponTable::getList(array(
			'select' => array('DISCOUNT_ID', new Main\Entity\ExpressionField('CNT', 'COUNT(*)')),
			'filter' => array('@DISCOUNT_ID' => self::$discountCheckList),
			'group' => array('DISCOUNT_ID')
		));
		while ($coupon = $couponIterator->fetch())
		{
			$coupon['CNT'] = (int)$coupon['CNT'];
			if ($coupon['CNT'] > 0)
			{
				$coupon['DISCOUNT_ID'] = (int)$coupon['DISCOUNT_ID'];
				unset($withoutCoupons[$coupon['DISCOUNT_ID']]);
				$withCoupons[$coupon['DISCOUNT_ID']] = true;
			}
		}
		unset($coupon, $couponIterator);
		if (!empty($withoutCoupons))
		{
			$withoutCoupons = array_keys($withoutCoupons);
			DiscountTable::setUseCoupons($withoutCoupons, 'N');
		}
		if (!empty($withCoupons))
		{
			$withCoupons = array_keys($withCoupons);
			DiscountTable::setUseCoupons($withCoupons, 'Y');
		}
		unset($withCoupons, $withoutCoupons);

		self::$discountCheckList = array();
	}

	/**
	 * Delete all coupons for discount.
	 *
	 * @param int $discount			Discount id.
	 * @return void
	 */
	public static function deleteByDiscount($discount)
	{
		$discount = (int)$discount;
		if ($discount <= 0)
			return;

		$couponsList = array();
		$couponIterator = self::getList(array(
			'select' => array('ID'),
			'filter' => array('=DISCOUNT_ID' => $discount)
		));
		while ($coupon = $couponIterator->fetch())
			$couponsList[] = $coupon['ID'];
		unset($coupon, $couponIterator);
		if (!empty($couponsList))
		{
			$conn = Application::getConnection();
			$helper = $conn->getSqlHelper();
			$conn->queryExecute(
				'delete from '.$helper->quote(self::getTableName()).' where '.$helper->quote('DISCOUNT_ID').' = '.$discount
			);
			$event = new Main\Event('sale', self::EVENT_ON_AFTER_DELETE_DISCOUNT, array($discount, $couponsList));
			$event->send();
		}
	}

	/**
	 * Save coupons applyed info.
	 *
	 * @param array $coupons				Coupons list.
	 * @param int $userId					User id.
	 * @param Main\Type\DateTime $currentTime	Current datetime.
	 * @return array|bool
	 */
	public static function saveApplied($coupons, $userId, Main\Type\DateTime $currentTime)
	{
		$currentTimestamp = $currentTime->getTimestamp();
		if ($userId === null || (int)$userId == 0)
			return false;
		$userId = (int)$userId;
		if (!is_array($coupons))
			$coupons = array($coupons);
		if (empty($coupons))
			return false;
		Main\Type\Collection::normalizeArrayValuesByInt($coupons);
		if (empty($coupons))
			return false;

		$deactivateCoupons = array();
		$incrementalCoupons = array();
		$limitedCoupons = array();
		$couponIterator = self::getList(array(
			'select' => array(
				'ID', 'COUPON', 'DISCOUNT_ID', 'TYPE', 'ACTIVE', 'MAX_USE', 'USE_COUNT', 'USER_ID', 'ACTIVE_TO', 'ACTIVE_FROM',
				'DISCOUNT_ACTIVE' => 'DISCOUNT.ACTIVE',
				'DISCOUNT_ACTIVE_FROM' => 'DISCOUNT.ACTIVE_FROM', 'DISCOUNT_ACTIVE_TO' => 'DISCOUNT.ACTIVE_TO'
			),
			'filter' => array('@ID' => $coupons, '=ACTIVE' => 'Y'),
			'order' => array('ID' => 'ASC')
		));
		while ($existCoupon = $couponIterator->fetch())
		{
			if ($existCoupon['DISCOUNT_ACTIVE'] != 'Y')
				continue;
			if (
				($existCoupon['DISCOUNT_ACTIVE_FROM'] instanceof Main\Type\DateTime && $existCoupon['DISCOUNT_ACTIVE_FROM']->getTimestamp() > $currentTimestamp)
				||
				($existCoupon['DISCOUNT_ACTIVE_TO'] instanceof Main\Type\DateTime && $existCoupon['DISCOUNT_ACTIVE_TO']->getTimestamp() < $currentTimestamp)
			)
				continue;

			$existCoupon['USER_ID'] = (int)$existCoupon['USER_ID'];
			if ($existCoupon['USER_ID'] > 0 && $existCoupon['USER_ID'] != $userId)
				continue;
			if (
				($existCoupon['ACTIVE_FROM'] instanceof Main\Type\DateTime && $existCoupon['ACTIVE_FROM']->getTimestamp() > $currentTimestamp)
				||
				($existCoupon['ACTIVE_TO'] instanceof Main\Type\DateTime && $existCoupon['ACTIVE_TO']->getTimestamp() < $currentTimestamp)
			)
				continue;
			if (
				$existCoupon['TYPE'] == self::TYPE_BASKET_ROW
				|| $existCoupon['TYPE'] == self::TYPE_ONE_ORDER
			)
			{
				$deactivateCoupons[$existCoupon['COUPON']] = $existCoupon['ID'];
			}
			elseif ($existCoupon['TYPE'] == self::TYPE_MULTI_ORDER)
			{
				$existCoupon['MAX_USE'] = (int)$existCoupon['MAX_USE'];
				$existCoupon['USE_COUNT'] = (int)$existCoupon['USE_COUNT'];

				if ($existCoupon['MAX_USE'] > 0 && $existCoupon['USE_COUNT'] >= $existCoupon['MAX_USE'])
					continue;
				if ($existCoupon['MAX_USE'] > 0 && $existCoupon['USE_COUNT'] >= ($existCoupon['MAX_USE'] - 1))
				{
					$limitedCoupons[$existCoupon['COUPON']] = $existCoupon['ID'];
				}
				else
				{
					$incrementalCoupons[$existCoupon['COUPON']] = $existCoupon['ID'];
				}
			}

		}
		unset($existCoupon, $couponIterator, $coupons);
		if (!empty($deactivateCoupons) || !empty($limitedCoupons) || !empty($incrementalCoupons))
		{
			$conn = Application::getConnection();
			$helper = $conn->getSqlHelper();
			$tableName = $helper->quote(self::getTableName());
			if (!empty($deactivateCoupons))
			{
				$conn->queryExecute(
					'update '.$tableName.' set '.$helper->quote('ACTIVE').' = \'N\', '.$helper->quote('DATE_APPLY').' = '.$helper->getCurrentDateTimeFunction().
					' where '.$helper->quote('ID').' in ('.implode(',', $deactivateCoupons).')'
				);
			}
			if (!empty($incrementalCoupons))
			{
				$conn->queryExecute(
					'update '.$tableName.' set '.$helper->quote('DATE_APPLY').' = '.$helper->getCurrentDateTimeFunction().', '.
					$helper->quote('USE_COUNT').' = '.$helper->quote('USE_COUNT').' + 1'.
					' where '.$helper->quote('ID').' in ('.implode(',', $incrementalCoupons).')'
				);
			}
			if (!empty($limitedCoupons))
			{
				$conn->queryExecute(
					'update '.$tableName.' set '.$helper->quote('DATE_APPLY').' = '.$helper->getCurrentDateTimeFunction().', '.
					$helper->quote('ACTIVE').' = \'N\', '.$helper->quote('USE_COUNT').' = '.$helper->quote('USE_COUNT').' + 1'.
					' where '.$helper->quote('ID').' in ('.implode(',', $limitedCoupons).')'
				);
			}
			unset($tableName, $helper);
		}
		return array(
			'DEACTIVATE' => $deactivateCoupons,
			'LIMITED' => $limitedCoupons,
			'INCREMENT' => $incrementalCoupons
		);
	}

	/**
	 * Create coupon code.
	 *
	 * @param bool $check		Check new coupon or no.
	 * @return string
	 */
	public static function generateCoupon($check = false)
	{
		static $eventExists = null;

		$check = ($check === true);
		if ($eventExists === true || $eventExists === null)
		{
			$event = new Main\Event('sale', self::EVENT_ON_GENERATE_COUPON, array('CHECK' => $check));
			$event->send();
			$resultList = $event->getResults();
			if (!empty($resultList) && is_array($resultList))
			{
				/** @var Main\EventResult $eventResult */
				foreach ($resultList as &$eventResult)
				{
					if ($eventResult->getType() != Main\EventResult::SUCCESS)
						continue;
					$eventExists = true;
					$result = $eventResult->getParameters();
					if (!empty($result) && is_string($result))
						return $result;
				}
				unset($eventResult);
			}
			if ($eventExists === null)
				$eventExists = false;
		}

		$allchars = 'ABCDEFGHIJKLNMOPQRSTUVWXYZ0123456789';
		$charsLen = strlen($allchars)-1;

		do
		{
			$resultCorrect = true;
			$partOne = '';
			$partTwo = '';
			for ($i = 0; $i < 5; $i++)
				$partOne .= substr($allchars, rand(0, $charsLen), 1);

			for ($i = 0; $i < 7; $i++)
				$partTwo .= substr($allchars, rand(0, $charsLen), 1);

			$result = 'SL-'.$partOne.'-'.$partTwo;
			if ($check)
			{
				$existCoupon = Sale\DiscountCouponsManager::isExist($result);
				$resultCorrect = empty($existCoupon);
			}
		} while (!$resultCorrect);
		return $result;
	}

	/**
	 * Create one and more coupons for discount.
	 *
	 * @param array $data				Coupon data.
	 * @param int $count				Coupos count.
	 * @param int $limit				Maximum number of attempts.
	 * @return Main\Entity\Result
	 */
	public static function addPacket(array $data, $count, $limit = 0)
	{
		$result = new Main\Entity\Result();
		$result->setData(array(
			'result' => 0,
			'count' => $count,
			'limit' => $limit,
			'all' => 0
		));
		$count = (int)$count;
		if ($count <= 0)
		{
			$result->addError(new Main\Entity\EntityError(
				Loc::getMessage('DISCOUNT_COUPON_PACKET_COUNT_ERR'),
				'COUPON_PACKET'
			));
		}
		foreach (static::getEntity()->getFields() as $field)
		{
			if ($field instanceof Main\Entity\ScalarField &&  !array_key_exists($field->getName(), $data))
			{
				$defaultValue =  $field->getDefaultValue();

				if ($defaultValue !== null)
					$data[$field->getName()] = $field->getDefaultValue();
			}
		}
		$checkResult = static::checkPacket($data, false);
		if (!$checkResult->isSuccess())
		{
			foreach ($checkResult->getErrors() as $checkError)
			{
				$result->addError($checkError);
			}
			unset($checkError);
		}
		unset($checkResult);
		$useCoupons = false;
		$discountIterator = DiscountTable::getList(array(
			'select' => array('ID', 'USE_COUPONS'),
			'filter' => array('=ID' => $data['DISCOUNT_ID'])
		));
		if ($discount = $discountIterator->fetch())
		{
			$useCoupons = ($discount['USE_COUPONS'] == 'Y');
		}
		else
		{
			$result->addError(new Main\Entity\EntityError(
				Loc::getMessage('DISCOUNT_COUPON_PACKET_DISCOUNT_ERR'),
				'COUPON_PACKET'
			));
		}
		if (!$result->isSuccess(true))
			return $result;

		self::setDiscountCheckList($data['DISCOUNT_ID']);
		self::disableCheckCouponsUse();
		$limit = (int)$limit;
		if ($limit < $count)
			$limit = $count*2;
		$resultCount = 0;
		$all = 0;
		do
		{
			$data['COUPON'] = self::generateCoupon(true);
			$couponResult = self::add($data);
			if ($couponResult->isSuccess())
				$resultCount++;
			$all++;
		} while ($resultCount < $count && $all < $limit);
		$result->setData(array(
			'result' => $resultCount,
			'count' => $count,
			'limit' => $limit,
			'all' => $all
		));
		if ($resultCount == 0)
		{
			$result->addError(new Main\Entity\EntityError(
				($useCoupons
					? Loc::getMessage('DISCOUNT_COUPON_PACKET_GENERATE_COUPON_ZERO_ERR')
					: Loc::getMessage('DISCOUNT_COUPON_PACKET_NEW_GENERATE_COUPON_ZERO_ERR')
				),
				'COUPON_PACKET'
			));
			self::clearDiscountCheckList();
		}
		elseif ($resultCount < $count)
		{
			$result->addError(new Main\Entity\EntityError(
				Loc::getMessage(
					'DISCOUNT_COUPON_PACKET_GENERATE_COUPON_COUNT_ERR',
					array(
						'#RESULT#' => $resultCount,
						'#COUNT#' => $count,
						'#ALL#' => $all
					)
				),
				'COUPON_PACKET'
			));
		}
		self::enableCheckCouponsUse();
		self::updateUseCoupons();

		return $result;
	}

	/**
	 * Check data for create one or more coupons.
	 *
	 * @param array $data				Coupon data.
	 * @param bool $newDiscount			New discount flag.
	 * @return Main\Entity\Result
	 */
	public static function checkPacket(array $data, $newDiscount = false)
	{
		$result = new Main\Entity\Result();

		$newDiscount = ($newDiscount === true);
		if (empty($data) || !is_array($data))
		{
			$result->addError(new Main\Entity\EntityError(
				Loc::getMessage('DISCOUNT_COUPON_PACKET_EMPTY'),
				'COUPON_PACKET'
			));
		}
		else
		{
			if (empty($data['TYPE']) || !in_array((int)$data['TYPE'], self::getCouponTypes(false)))
			{
				$result->addError(new Main\Entity\EntityError(
					Loc::getMessage('DISCOUNT_COUPON_VALIDATOR_TYPE'),
					'COUPON_PACKET'
				));
			}
			if (!$newDiscount && empty($data['DISCOUNT_ID']))
			{
				$result->addError(new Main\Entity\EntityError(
					Loc::getMessage('DISCOUNT_COUPON_VALIDATOR_DISCOUNT_ID'),
					'COUPON_PACKET'
				));
			}
			if (
				(isset($data['ACTIVE_FROM']) && !($data['ACTIVE_FROM'] instanceof Main\Type\DateTime))
				||
				(isset($data['ACTIVE_TO']) && !($data['ACTIVE_TO'] instanceof Main\Type\DateTime))
			)
			{
				$result->addError(new Main\Entity\EntityError(
					Loc::getMessage('DISCOUNT_COUPON_VALIDATOR_PERIOD'),
					'COUPON_PACKET'
				));
			}
		}
		return $result;
	}

	/**
	 * Prepare coupon data. Only for admin list pages.
	 *
	 * @param array &$fields				Coupon data.
	 * @return Main\Entity\Result
	 */
	public static function prepareCouponData(&$fields)
	{
		$result = new Main\Entity\Result();
		if (!empty($fields) && is_array($fields))
		{
			if (isset($fields['ACTIVE_FROM']) && is_string($fields['ACTIVE_FROM']))
			{
				try
				{
					$fields['ACTIVE_FROM'] = trim($fields['ACTIVE_FROM']);
					$fields['ACTIVE_FROM'] = ($fields['ACTIVE_FROM'] !== '' ? new Main\Type\DateTime($fields['ACTIVE_FROM']) : null);
				}
				catch (Main\ObjectException $e)
				{
					$fields['ACTIVE_FROM'] = new Main\Type\Date($fields['ACTIVE_FROM']);
				}
			}
			if (isset($fields['ACTIVE_TO']) && is_string($fields['ACTIVE_TO']))
			{
				try
				{
					$fields['ACTIVE_TO'] = trim($fields['ACTIVE_TO']);
					$fields['ACTIVE_TO'] = ($fields['ACTIVE_TO'] !== '' ? new Main\Type\DateTime($fields['ACTIVE_TO']) : null);
				}
				catch(Main\ObjectException $e)
				{
					$fields['ACTIVE_TO'] = new Main\Type\Date($fields['ACTIVE_TO']);
				}
			}
		}
		return $result;
	}

	/**
	 * Check valid coupon type.
	 *
	 * @param int $couponType			Coupon type.
	 * @return bool
	 */
	public static function isValidCouponType($couponType)
	{
		$couponType = (int)$couponType;
		return (
			$couponType == self::TYPE_BASKET_ROW
			|| $couponType == self::TYPE_ONE_ORDER
			|| $couponType == self::TYPE_MULTI_ORDER
		);
	}

	/**
	 * Fill user id fields.
	 *
	 * @param array &$result			Modified data for add/update discount.
	 * @param array $data				Current data for add/update discount.
	 * @param array $keys				List with checked keys (userId info).
	 * @return void
	 */
	protected static function setUserID(&$result, $data, $keys)
	{
		static $currentUserID = false;
		if ($currentUserID === false)
		{
			global $USER;
			$currentUserID = (isset($USER) && $USER instanceof \CUser ? (int)$USER->getID() : null);
		}
		foreach ($keys as &$oneKey)
		{
			$setField = true;
			if (array_key_exists($oneKey, $data))
				$setField = ($data[$oneKey] !== null && (int)$data[$oneKey] <= 0);

			if ($setField)
				$result[$oneKey] = $currentUserID;
		}
		unset($oneKey);
	}

	/**
	 * Fill datetime fields.
	 *
	 * @param array &$result			Modified data for add/update discount.
	 * @param array $data				Current data for add/update discount.
	 * @param array $keys				List with checked keys (datetime info).
	 * @return void
	 */
	protected static function setTimestamp(&$result, $data, $keys)
	{
		foreach ($keys as &$oneKey)
		{
			$setField = true;
			if (array_key_exists($oneKey, $data))
				$setField = ($data[$oneKey] !== null && !is_object($data[$oneKey]));

			if ($setField)
				$result[$oneKey] = new Main\Type\DateTime();
		}
		unset($oneKey);
	}
}