<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class OrderRoundTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ORDER_ID int mandatory
 * <li> APPLY_BLOCK_COUNTER int mandatory
 * <li> ORDER_ROUND string(1) mandatory
 * <li> ENTITY_TYPE int mandatory
 * <li> ENTITY_ID int mandatory
 * <li> ENTITY_VALUE string(255) optional
 * <li> APPLY string(1) mandatory
 * <li> ROUND_RULE string mandatory
 * </ul>
 *
 * @package Bitrix\Sale
 **/

class OrderRoundTable extends Main\Entity\DataManager
{
	const ENTITY_TYPE_BASKET = 0x0001;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sale_order_round';
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
				'title' => Loc::getMessage('ORDER_ROUND_ENTITY_ID_FIELD')
			)),
			'ORDER_ID' => new Main\Entity\IntegerField('ORDER_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ORDER_ROUND_ENTITY_ORDER_ID_FIELD')
			)),
			'APPLY_BLOCK_COUNTER' => new Main\Entity\IntegerField('APPLY_BLOCK_COUNTER', array(
				'required' => true,
			)),
			'ORDER_ROUND' => new Main\Entity\BooleanField('ORDER_ROUND', array(
				'required' => true,
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
				'title' => Loc::getMessage('ORDER_ROUND_ENTITY_ORDER_ROUND_FIELD')
			)),
			'ENTITY_TYPE' => new Main\Entity\EnumField('ENTITY_TYPE', array(
				'required' => true,
				'values' => array(self::ENTITY_TYPE_BASKET),
				'title' => Loc::getMessage('ORDER_ROUND_ENTITY_ENTITY_TYPE_FIELD')
			)),
			'ENTITY_ID' => new Main\Entity\IntegerField('ENTITY_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ORDER_ROUND_ENTITY_ENTITY_ID_FIELD')
			)),
			'ENTITY_VALUE' => new Main\Entity\StringField('ENTITY_VALUE', array(
				'validation' => array(__CLASS__, 'validateEntityValue'),
				'title' => Loc::getMessage('ORDER_ROUND_ENTITY_ENTITY_VALUE_FIELD')
			)),
			'APPLY' => new Main\Entity\BooleanField('APPLY', array(
				'required' => true,
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('ORDER_ROUND_ENTITY_APPLY_FIELD')
			)),
			'ROUND_RULE' => new Main\Entity\TextField('ROUND_RULE', array(
				'required' => true,
				'serialized' => true
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
}