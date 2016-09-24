<?php
namespace Bitrix\Catalog;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class RoundingTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CATALOG_GROUP_ID int mandatory
 * <li> PRICE double mandatory
 * <li> ROUND_TYPE int mandatory
 * <li> ROUND_PRECISION double mandatory
 * <li> CREATED_BY int optional
 * <li> DATE_CREATE datetime optional
 * <li> MODIFIED_BY int optional
 * <li> TIMESTAMP_X datetime optional
 * <li> CREATED_BY_USER reference to {@link \Bitrix\Main\UserTable}
 * <li> MODIFIED_BY_USER reference to {@link \Bitrix\Main\UserTable}
 * </ul>
 *
 * @package Bitrix\Catalog
 **/

class RoundingTable extends Main\Entity\DataManager
{
	const ROUND_MATH = 0x0001;
	const ROUND_UP = 0x0002;
	const ROUND_DOWN = 0x0004;

	/** @var int clear rounding cache flag */
	protected static $clearCache = 0;
	/** @var array price type list for clear */
	protected static $priceTypeIds = array();

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_catalog_rounding';
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
				'title' => Loc::getMessage('ROUNDING_ENTITY_ID_FIELD')
			)),
			'CATALOG_GROUP_ID' => new Main\Entity\IntegerField('CATALOG_GROUP_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ROUNDING_ENTITY_CATALOG_GROUP_ID_FIELD')
			)),
			'PRICE' => new Main\Entity\FloatField('PRICE', array(
				'required' => true,
				'title' => Loc::getMessage('ROUNDING_ENTITY_PRICE_FIELD')
			)),
			'ROUND_TYPE' => new Main\Entity\EnumField('ROUND_TYPE', array(
				'required' => true,
				'values' => array(self::ROUND_MATH, self::ROUND_UP, self::ROUND_DOWN),
				'title' => Loc::getMessage('ROUNDING_ENTITY_ROUND_TYPE_FIELD')
			)),
			'ROUND_PRECISION' => new Main\Entity\FloatField('ROUND_PRECISION', array(
				'required' => true,
				'title' => Loc::getMessage('ROUNDING_ENTITY_ROUND_PRECISION_FIELD')
			)),
			'CREATED_BY' => new Main\Entity\IntegerField('CREATED_BY', array(
				'title' => Loc::getMessage('ROUNDING_ENTITY_CREATED_BY_FIELD')
			)),
			'DATE_CREATE' => new Main\Entity\DatetimeField('DATE_CREATE', array(
				'title' => Loc::getMessage('ROUNDING_ENTITY_DATE_CREATE_FIELD')
			)),
			'MODIFIED_BY' => new Main\Entity\IntegerField('MODIFIED_BY', array(
				'title' => Loc::getMessage('ROUNDING_ENTITY_MODIFIED_BY_FIELD')
			)),
			'DATE_MODIFY' => new Main\Entity\DatetimeField('DATE_MODIFY', array(
				'title' => Loc::getMessage('ROUNDING_ENTITY_TIMESTAMP_X_FIELD')
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
			)
		);
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
		static::setUserId($modifyFieldList, $data, array('CREATED_BY', 'MODIFIED_BY'));
		static::setTimestamp($modifyFieldList, $data, array('DATE_CREATE', 'DATE_MODIFY'));

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
		if (!static::isAllowedClearCache())
			return;
		$data = $event->getParameter('fields');
		self::$priceTypeIds[$data['CATALOG_GROUP_ID']] = $data['CATALOG_GROUP_ID'];
		unset($data);
		static::clearCache();
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
		static::setUserId($modifyFieldList, $data, array('MODIFIED_BY'));
		static::setTimestamp($modifyFieldList, $data, array('DATE_MODIFY'));

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
		if (!static::isAllowedClearCache())
			return;
		$data = $event->getParameter('fields');
		$rule = static::getList(array(
			'select' => array('ID', 'CATALOG_GROUP_ID'),
			'filter' => array('=ID' => $event->getParameter('id'))
		))->fetch();
		if (!empty($rule))
		{
			self::$priceTypeIds[$rule['CATALOG_GROUP_ID']] = $rule['CATALOG_GROUP_ID'];
			if (isset($data['CATALOG_GROUP_ID']))
				self::$priceTypeIds[$data['CATALOG_GROUP_ID']] = $data['CATALOG_GROUP_ID'];
		}
		unset($rule, $data);
	}

	/**
	 * Default onAfterUpdate handler. Absolutely necessary.
	 *
	 * @param Main\Entity\Event $event		Current data for update.
	 * @return void
	 */
	public static function onAfterUpdate(Main\Entity\Event $event)
	{
		static::clearCache();
	}

	/**
	 * Default onDelete handler. Absolutely necessary.
	 *
	 * @param Main\Entity\Event $event		Current data for delete.
	 * @return void
	 */
	public static function onDelete(Main\Entity\Event $event)
	{
		if (!static::isAllowedClearCache())
			return;
		$rule = static::getList(array(
			'select' => array('ID', 'CATALOG_GROUP_ID'),
			'filter' => array('=ID' => $event->getParameter('id'))
		))->fetch();
		if (!empty($rule))
			self::$priceTypeIds[$rule['CATALOG_GROUP_ID']] = $rule['CATALOG_GROUP_ID'];
		unset($rule);
	}

	/**
	 * Default onAfterDelete handler. Absolutely necessary.
	 *
	 * @param Main\Entity\Event $event		Current data for delete.
	 * @return void
	 */
	public static function onAfterDelete(Main\Entity\Event $event)
	{
		static::clearCache();
	}

	/**
	 * Returns current allow mode for cache clearing.
	 *
	 * @return bool
	 */
	public static function isAllowedClearCache()
	{
		return (self::$clearCache >= 0);
	}

	/**
	 * Allow clear cache after multiuse add/update/delete.
	 *
	 * @return void
	 */
	public static function allowClearCache()
	{
		self::$clearCache++;
	}

	/**
	 * Disallow clear cache before multiuse add/update/delete.
	 *
	 * @return void
	 */
	public static function disallowClearCache()
	{
		self::$clearCache--;
	}

	/**
	 * Clear price type ids.
	 *
	 * @return void
	 */
	public static function clearPriceTypeIds()
	{
		self::$priceTypeIds = array();
	}

	/**
	 * Set price type list for cache clearing.
	 *
	 * @param string|int|array $priceTypes		Price types for cache clearing.
	 * @return void
	 */
	public static function setPriceTypeIds($priceTypes)
	{
		if (!is_array($priceTypes))
			$priceTypes = array($priceTypes => $priceTypes);

		if (!empty($priceTypes) && is_array($priceTypes))
			self::$priceTypeIds = (empty(self::$priceTypeIds) ? $priceTypes : array_merge(self::$priceTypeIds, $priceTypes));
	}

	/**
	 * Clear managed cache.
	 *
	 * @return void
	 */
	public static function clearCache()
	{
		if (!static::isAllowedClearCache() || empty(self::$priceTypeIds))
			return;
		foreach (self::$priceTypeIds as $priceType)
			Product\Price::clearRoundRulesCache($priceType);
		unset($priceType);
		static::clearPriceTypeIds();
	}

	/**
	 * Delete rules by currency.
	 *
	 * @param string|int $priceType		Price type id.
	 * @return void
	 */
	public static function deleteByPriceType($priceType)
	{
		$priceType = (int)$priceType;
		if ($priceType <= 0)
			return;
		$conn = Main\Application::getConnection();
		$helper = $conn->getSqlHelper();
		$conn->queryExecute(
			'delete from '.$helper->quote(self::getTableName()).' where '.$helper->quote('CATALOG_CGROU_ID').' = '.$priceType
		);
		unset($helper, $conn);
		Product\Price::clearRoundRulesCache($priceType);
	}

	/**
	 * Return round types.
	 *
	 * @param bool $full		Get types with description.
	 * @return array
	 */
	public static function getRoundTypes($full = false)
	{
		$full = ($full === true);
		if ($full)
		{
			return array(
				self::ROUND_MATH => Loc::getMessage('ROUNDING_TYPE_ROUND_MATH'),
				self::ROUND_UP => Loc::getMessage('ROUNDING_TYPE_ROUND_UP'),
				self::ROUND_DOWN => Loc::getMessage('ROUNDING_TYPE_ROUND_DOWN')
			);
		}
		return array(
			self::ROUND_MATH,
			self::ROUND_UP,
			self::ROUND_DOWN
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
	protected static function setUserId(array &$result, array $data, array $keys)
	{
		static $currentUserID = false;
		if ($currentUserID === false)
		{
			global $USER;
			/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
			$currentUserID = (isset($USER) && $USER instanceof \CUser ? (int)$USER->getID() : null);
		}
		foreach ($keys as $index)
		{
			$setField = true;
			if (array_key_exists($index, $data))
				$setField = ($data[$index] !== null && (int)$data[$index] <= 0);

			if ($setField)
				$result[$index] = $currentUserID;
		}
		unset($index);
	}

	/**
	 * Fill datetime fields.
	 *
	 * @param array &$result			Modified data for add/update discount.
	 * @param array $data				Current data for add/update discount.
	 * @param array $keys				List with checked keys (datetime info).
	 * @return void
	 */
	protected static function setTimestamp(array &$result, array $data, array $keys)
	{
		foreach ($keys as $index)
		{
			$setField = true;
			if (array_key_exists($index, $data))
				$setField = ($data[$index] !== null && !is_object($data[$index]));

			if ($setField)
				$result[$index] = new Main\Type\DateTime();
		}
		unset($index);
	}
}