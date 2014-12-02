<?php
namespace Bitrix\Currency;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class CurrencyRateTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CURRENCY string(3) mandatory
 * <li> DATE_RATE date mandatory
 * <li> RATE_CNT int optional default 1
 * <li> RATE float mandatory default 0.0000
 * <li> CREATED_BY int optional
 * <li> DATE_CREATE datetime optional
 * <li> MODIFIED_BY int optional
 * <li> TIMESTAMP_X datetime optional
 * <li> CREATED_BY_USER reference to {@link \Bitrix\Main\UserTable}
 * <li> MODIFIED_BY_USER reference to {@link \Bitrix\Main\UserTable}
 * </ul>
 *
 * @package Bitrix\Currency
 **/

class CurrencyRateTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_catalog_currency_rate';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_ID_FIELD'),
			),
			'CURRENCY' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateCurrency'),
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_CURRENCY_FIELD'),
			),
			'DATE_RATE' => array(
				'data_type' => 'date',
				'primary' => true,
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_DATE_RATE_FIELD'),
			),
			'RATE_CNT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_RATE_CNT_FIELD'),
			),
			'RATE' => array(
				'data_type' => 'float',
				'required' => true,
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_RATE_FIELD'),
			),
			'CREATED_BY' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_CREATED_BY_FIELD'),
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_DATE_CREATE_FIELD'),
			),
			'MODIFIED_BY' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_MODIFIED_BY_FIELD'),
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
				'required' => true,
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_TIMESTAMP_X_FIELD'),
			),
			'CREATED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.CREATED_BY' => 'ref.ID'),
			),
			'MODIFIED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.MODIFIED_BY' => 'ref.ID'),
			),
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
			new Entity\Validator\Length(null, 3),
		);
	}
}