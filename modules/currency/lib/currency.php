<?php
namespace Bitrix\Currency;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class CurrencyTable
 *
 * Fields:
 * <ul>
 * <li> CURRENCY string(3) mandatory
 * <li> AMOUNT_CNT int optional default 1
 * <li> AMOUNT float optional
 * <li> SORT int optional default 100
 * <li> DATE_UPDATE datetime mandatory
 * <li> BASE string(1) mandatory
 * <li> CREATED_BY int optional
 * <li> DATE_CREATE datetime optional
 * <li> MODIFIED_BY int optional
 * <li> CURRENT_BASE_RATE float optional
 * <li> CREATED_BY_USER reference to {@link \Bitrix\Main\UserTable}
 * <li> MODIFIED_BY_USER reference to {@link \Bitrix\Main\UserTable}
 * </ul>
 *
 * @package Bitrix\Currency
 **/
class CurrencyTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_catalog_currency';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'CURRENCY' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateCurrency'),
				'required' => true,
				'title' => Loc::getMessage('CURRENCY_ENTITY_CURRENCY_FIELD'),
			),
			'AMOUNT_CNT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CURRENCY_ENTITY_AMOUNT_CNT_FIELD'),
			),
			'AMOUNT' => array(
				'data_type' => 'float',
				'title' => Loc::getMessage('CURRENCY_ENTITY_AMOUNT_FIELD'),
			),
			'SORT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CURRENCY_ENTITY_SORT_FIELD'),
			),
			'DATE_UPDATE' => array(
				'data_type' => 'datetime',
				'required' => true,
				'title' => Loc::getMessage('CURRENCY_ENTITY_DATE_UPDATE_FIELD'),
			),
			'BASE' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
				'title' => Loc::getMessage('CURRENCY_ENTITY_BASE_FIELD'),
			),
			'CREATED_BY' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CURRENCY_ENTITY_CREATED_BY_FIELD'),
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('CURRENCY_ENTITY_DATE_CREATE_FIELD'),
			),
			'MODIFIED_BY' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CURRENCY_ENTITY_MODIFIED_BY_FIELD'),
			),
			'CURRENT_BASE_RATE' => array(
				'data_type' => 'float',
				'title' => Loc::getMessage('CURRENCY_ENTITY_CURRENT_BASE_RATE_FIELD'),
			),
			'CREATED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.CREATED_BY' => 'ref.ID'),
			),
			'MODIFIED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.MODIFIED_BY' => 'ref.ID'),
			),
			'LANG_FORMAT' => array(
				'data_type' => 'Bitrix\Currency\CurrencyLang',
				'reference' => array('=this.CURRENCY' => 'ref.CURRENCY'),
			)
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

	public static function currencyBaseRateAgent()
	{
		\CCurrency::updateAllCurrencyBaseRate();
		return '\Bitrix\Currency\CurrencyTable::currencyBaseRateAgent();';
	}
}