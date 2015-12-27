<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class DiscountTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> XML_ID string(255) optional
 * <li> LID string(2) mandatory
 * <li> NAME string(255) optional
 * <li> PRICE_FROM float optional
 * <li> PRICE_TO float optional
 * <li> CURRENCY string(3) optional
 * <li> DISCOUNT_VALUE float mandatory
 * <li> DISCOUNT_TYPE string(1) mandatory default 'P'
 * <li> ACTIVE bool optional default 'Y'
 * <li> SORT int optional default 100
 * <li> ACTIVE_FROM datetime optional
 * <li> ACTIVE_TO datetime optional
 * <li> TIMESTAMP_X datetime optional
 * <li> MODIFIED_BY int optional
 * <li> DATE_CREATE datetime optional
 * <li> CREATED_BY int optional
 * <li> PRIORITY int optional default 1
 * <li> LAST_DISCOUNT bool optional default 'Y'
 * <li> VERSION int optional default 1
 * <li> CONDITIONS text optional
 * <li> CONDITIONS_LIST text optional
 * <li> UNPACK text optional
 * <li> ACTIONS text optional
 * <li> ACTIONS_LIST text optional
 * <li> APPLICATION text optional
 * <li> USE_COUPONS bool optional default 'N'
 * <li> EXECUTE_MODULE string(50) mandatory default 'all'
 * <li> CREATED_BY_USER reference to {@link \Bitrix\Main\UserTable}
 * <li> MODIFIED_BY_USER reference to {@link \Bitrix\Main\UserTable}
 * </ul>
 *
 * @package Bitrix\Sale\Internals
 **/
class DiscountTable extends Main\Entity\DataManager
{
	const VERSION_OLD = 0x0001;
	const VERSION_NEW = 0x0002;

	protected static $deleteCoupons = false;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sale_discount';
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
				'title' => Loc::getMessage('DISCOUNT_ENTITY_ID_FIELD')
			)),
			'XML_ID' => new Main\Entity\StringField('XML_ID', array(
				'validation' => array(__CLASS__, 'validateXmlId'),
				'title' => Loc::getMessage('DISCOUNT_ENTITY_XML_ID_FIELD')
			)),
			'LID' => new Main\Entity\StringField('LID', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateLid'),
				'title' => Loc::getMessage('DISCOUNT_ENTITY_LID_FIELD')
			)),
			'NAME' => new Main\Entity\StringField('NAME', array(
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('DISCOUNT_ENTITY_NAME_FIELD')
			)),
			'PRICE_FROM' => new Main\Entity\FloatField('PRICE_FROM', array()),
			'PRICE_TO' => new Main\Entity\FloatField('PRICE_TO', array()),
			'CURRENCY' => new Main\Entity\StringField('CURRENCY', array(
				'validation' => array(__CLASS__, 'validateCurrency'),
				'title' => Loc::getMessage('DISCOUNT_ENTITY_CURRENCY_FIELD')
			)),
			'DISCOUNT_VALUE' => new Main\Entity\FloatField('DISCOUNT_VALUE', array()),
			'DISCOUNT_TYPE' => new Main\Entity\StringField('DISCOUNT_TYPE', array(
				'default_value' => 'P',
				'validation' => array(__CLASS__, 'validateDiscountType')
			)),
			'ACTIVE' => new Main\Entity\BooleanField('ACTIVE', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
				'title' => Loc::getMessage('DISCOUNT_ENTITY_ACTIVE_FIELD')
			)),
			'SORT' => new Main\Entity\IntegerField('SORT', array(
				'title' => Loc::getMessage('DISCOUNT_ENTITY_SORT_FIELD')
			)),
			'ACTIVE_FROM' => new Main\Entity\DatetimeField('ACTIVE_FROM', array(
				'default_value' => null,
				'title' => Loc::getMessage('DISCOUNT_ENTITY_ACTIVE_FROM_FIELD')
			)),
			'ACTIVE_TO' => new Main\Entity\DatetimeField('ACTIVE_TO', array(
				'default_value' => null,
				'title' => Loc::getMessage('DISCOUNT_ENTITY_ACTIVE_TO_FIELD')
			)),
			'TIMESTAMP_X' => new Main\Entity\DatetimeField('TIMESTAMP_X', array(
				'default_value' => new Main\Type\DateTime(),
				'title' => Loc::getMessage('DISCOUNT_ENTITY_TIMESTAMP_X_FIELD')
			)),
			'MODIFIED_BY' => new Main\Entity\IntegerField('MODIFIED_BY', array(
				'default_value' => null,
				'title' => Loc::getMessage('DISCOUNT_ENTITY_MODIFIED_BY_FIELD')
			)),
			'DATE_CREATE' => new Main\Entity\DatetimeField('DATE_CREATE', array(
				'default_value' => new Main\Type\DateTime(),
				'title' => Loc::getMessage('DISCOUNT_ENTITY_DATE_CREATE_FIELD')
			)),
			'CREATED_BY' => new Main\Entity\IntegerField('CREATED_BY', array(
				'default_value' => null,
				'title' => Loc::getMessage('DISCOUNT_ENTITY_CREATED_BY_FIELD')
			)),
			'PRIORITY' => new Main\Entity\IntegerField('PRIORITY', array(
				'default_value' => 1,
				'title' => Loc::getMessage('DISCOUNT_ENTITY_PRIORITY_FIELD')
			)),
			'LAST_DISCOUNT' => new Main\Entity\BooleanField('LAST_DISCOUNT', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
				'title' => Loc::getMessage('DISCOUNT_ENTITY_LAST_DISCOUNT_FIELD')
			)),
			'VERSION' => new Main\Entity\EnumField('VERSION', array(
				'values' => array(self::VERSION_OLD, self::VERSION_NEW),
				'default_value' => self::VERSION_NEW,
				'title' => Loc::getMessage('DISCOUNT_ENTITY_VERSION_FIELD')
			)),
			'CONDITIONS_LIST' => new Main\Entity\TextField('CONDITIONS_LIST', array(
				'serialized' => true,
				'column_name' => 'CONDITIONS',
				'title' => Loc::getMessage('DISCOUNT_ENTITY_CONDITIONS_LIST_FIELD')
			)),
			'CONDITIONS' => new Main\Entity\ExpressionField('CONDITIONS', '%s', 'CONDITIONS_LIST'),
			'UNPACK' => new Main\Entity\TextField('UNPACK', array()),
			'ACTIONS_LIST' => new Main\Entity\TextField('ACTIONS_LIST', array(
				'serialized' => true,
				'column_name' => 'ACTIONS',
				'title' => Loc::getMessage('DISCOUNT_ENTITY_ACTIONS_LIST_FIELD')
			)),
			'ACTIONS' => new Main\Entity\ExpressionField('ACTIONS', '%s', 'ACTIONS_LIST'),
			'APPLICATION' => new Main\Entity\TextField('APPLICATION', array()),
			'USE_COUPONS' => new Main\Entity\BooleanField('USE_COUPONS', array(
				'values' => array('N', 'Y'),
				'default_value' => 'N',
				'title' => Loc::getMessage('DISCOUNT_ENTITY_USE_COUPONS_FIELD')
			)),
			'EXECUTE_MODULE' => new Main\Entity\StringField('EXECUTE_MODULE', array(
				'validation' => array(__CLASS__, 'validateExecuteModule'),
				'title' => Loc::getMessage('DISCOUNT_ENTITY_EXECUTE_MODULE_FIELD')
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
			'COUPON' => new Main\Entity\ReferenceField(
				'COUPON',
				'Bitrix\Sale\Internals\DiscountCoupon',
				array('=this.ID' => 'ref.DISCOUNT_ID'),
				array('join_type' => 'LEFT')
			)
		);
	}
	/**
	 * Returns validators for XML_ID field.
	 *
	 * @return array
	 */
	public static function validateXmlId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for LID field.
	 *
	 * @return array
	 */
	public static function validateLid()
	{
		return array(
			new Main\Entity\Validator\Length(null, 2),
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
	 * Returns validators for CURRENCY field.
	 *
	 * @return array
	 */
	public static function validateCurrency()
	{
		return array(
			new Main\Entity\Validator\Length(null, 3),
		);
	}
	/**
	 * Returns validators for DISCOUNT_TYPE field.
	 *
	 * @return array
	 */
	public static function validateDiscountType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}

	/**
	 * Returns validators for EXECUTE_MODULE field.
	 *
	 * @return array
	 */
	public static function validateExecuteModule()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * Default onBeforeAdd handler. Absolutely necessary.
	 *
	 * @param Main\Entity\Event $event		Event object.
	 * @return Main\Entity\EventResult
	 */
	public static function onBeforeAdd(Main\Entity\Event $event)
	{
		$result = new Main\Entity\EventResult;
		$data = $event->getParameter('fields');

		$modifyFieldList = array(
			'DISCOUNT_VALUE' => 0,
			'DISCOUNT_TYPE' => 'P',
		);
		if (isset($data['LID']))
			$modifyFieldList['CURRENCY'] = SiteCurrencyTable::getSiteCurrency($data['LID']);
		self::setUserID($modifyFieldList, $data, array('CREATED_BY', 'MODIFIED_BY'));
		self::setTimestamp($modifyFieldList, $data, array('DATE_CREATE', 'TIMESTAMP_X'));

		self::copyOldFields($modifyFieldList, $data);
		$result->unsetField('CONDITIONS');
		$result->unsetField('ACTIONS');

		if (!empty($modifyFieldList))
			$result->modifyFields($modifyFieldList);
		unset($modifyFieldList);

		return $result;
	}

	/**
	 * Default onBeforeUpdate handler. Absolutely necessary.
	 *
	 * @param Main\Entity\Event $event		Event object.
	 * @return Main\Entity\EventResult
	 */
	public static function onBeforeUpdate(Main\Entity\Event $event)
	{
		$result = new Main\Entity\EventResult;
		$data = $event->getParameter('fields');

		$modifyFieldList = array();
		self::setUserID($modifyFieldList, $data, array('MODIFIED_BY'));
		self::setTimestamp($modifyFieldList, $data, array('TIMESTAMP_X'));

		self::copyOldFields($modifyFieldList, $data);
		$result->unsetField('CONDITIONS');
		$result->unsetField('ACTIONS');

		if (!empty($modifyFieldList))
			$result->modifyFields($modifyFieldList);
		unset($modifyFieldList);

		return $result;
	}

	/**
	 * Default onAfterUpdate handler. Absolutely necessary.
	 *
	 * @param Main\Entity\Event $event		Event object.
	 * @return void
	 */
	public static function onAfterUpdate(Main\Entity\Event $event)
	{
		$id = $event->getParameter('id');
		$id = end($id);
		$data = $event->getParameter('fields');
		if (isset($data['ACTIVE']))
			DiscountGroupTable::changeActiveByDiscount($id, $data['ACTIVE']);
		unset($data, $id);
	}

	/**
	 * Default onDelete handler. Absolutely necessary.
	 *
	 * @param Main\Entity\Event $event		Event object.
	 * @return void
	 */
	public static function onDelete(Main\Entity\Event $event)
	{
		$id = $event->getParameter('id');
		$discountIterator = self::getList(array(
			'select' => array('ID', 'USE_COUPONS'),
			'filter' => array('=ID' => $id)
		));
		if ($discount = $discountIterator->fetch())
		{
			if ((string)$discount['USE_COUPONS'] === 'Y')
				self::$deleteCoupons = $discount['ID'];
		}
		unset($discount, $discountIterator, $id);
	}

	/**
	 * Default onAfterDelete handler. Absolutely necessary.
	 *
	 * @param Main\Entity\Event $event		Event object.
	 * @return void
	 */
	public static function onAfterDelete(Main\Entity\Event $event)
	{
		$id = $event->getParameter('id');
		$id = end($id);
		DiscountEntitiesTable::deleteByDiscount($id);
		DiscountModuleTable::deleteByDiscount($id);
		DiscountGroupTable::deleteByDiscount($id);
		if (self::$deleteCoupons !== false)
		{
			DiscountCouponTable::deleteByDiscount(self::$deleteCoupons);
			self::$deleteCoupons = false;
		}
		unset($id);
	}

	/**
	 * Set exist coupons flag for discount list.
	 *
	 * @param array $discountList			Discount ids for update.
	 * @param string $use				Value for update use coupons.
	 * @return void
	 */
	public static function setUseCoupons($discountList, $use)
	{
		if (!is_array($discountList))
			$discountList = array($discountList);
		$use = (string)$use;
		if ($use !== 'Y' && $use !== 'N')
			return;
		Main\Type\Collection::normalizeArrayValuesByInt($discountList);
		if (empty($discountList))
			return;
		$conn = Application::getConnection();
		$helper = $conn->getSqlHelper();
		$conn->queryExecute(
			'update '.$helper->quote(self::getTableName()).
			' set '.$helper->quote('USE_COUPONS').' = \''.$use.'\' where '.
			$helper->quote('ID').' in ('.implode(',', $discountList).')'
		);
	}

	/**
	 * Set exist coupons flag for all discounts.
	 *
	 * @param string $use				Value for update use coupons for all discount.
	 * @return void
	 */
	public static function setAllUseCoupons($use)
	{
		$use = (string)$use;
		if ($use !== 'Y' && $use !== 'N')
			return;
		$conn = Application::getConnection();
		$helper = $conn->getSqlHelper();
		$conn->queryExecute(
			'update '.$helper->quote(self::getTableName()).' set '.$helper->quote('USE_COUPONS').' = \''.$use.'\''
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

	/**
	 * Remove values from old fields conditions and actions (for compatibility with old api).
	 *
	 * @param array &$result			Modified data for add/update discount.
	 * @param array $data				Current data for add/update discount.
	 * @return void
	 */
	protected static function copyOldFields(&$result, $data)
	{
		if (!isset($data['CONDITIONS_LIST']) && isset($data['CONDITIONS']))
			$result['CONDITIONS_LIST'] = (is_array($data['CONDITIONS']) ? $data['CONDITIONS'] : unserialize($data['CONDITIONS']));

		if (!isset($data['ACTIONS_LIST']) && isset($data['ACTIONS']))
			$result['ACTIONS_LIST'] = (is_array($data['ACTIONS']) ? $data['ACTIONS'] : unserialize($data['ACTIONS']));
	}
}