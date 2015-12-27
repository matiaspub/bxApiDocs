<?php
namespace Bitrix\Currency;

use Bitrix\Main;
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
 * <li> NUMCODE string(3) optional
 * <li> BASE string(1) mandatory
 * <li> CREATED_BY int optional
 * <li> DATE_CREATE datetime optional
 * <li> MODIFIED_BY int optional
 * <li> CURRENT_BASE_RATE float optional
 * <li> CREATED_BY_USER reference to {@link \Bitrix\Main\UserTable}
 * <li> MODIFIED_BY_USER reference to {@link \Bitrix\Main\UserTable}
 * <li> LANG_FORMAT reference to {@link \Bitrix\Currency\CurrencyLangTable}
 * <li> CURRENT_LANG_FORMAT reference to {@link \Bitrix\Currency\CurrencyLangTable} with current language (LANGUAGE_ID)
 * </ul>
 *
 * @package Bitrix\Currency
 **/
class CurrencyTable extends Main\Entity\DataManager
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
			'CURRENCY' => new Main\Entity\StringField('CURRENCY', array(
				'primary' => true,
				'validation' => array(__CLASS__, 'validateCurrency'),
				'required' => true,
				'title' => Loc::getMessage('CURRENCY_ENTITY_CURRENCY_FIELD')
			)),
			'AMOUNT_CNT' => new Main\Entity\IntegerField('AMOUNT_CNT', array(
				'required' => true,
				'title' => Loc::getMessage('CURRENCY_ENTITY_AMOUNT_CNT_FIELD'),
			)),
			'AMOUNT' => new Main\Entity\FloatField('AMOUNT', array(
				'required' => true,
				'title' => Loc::getMessage('CURRENCY_ENTITY_AMOUNT_FIELD')
			)),
			'SORT' => new Main\Entity\IntegerField('SORT', array(
				'title' => Loc::getMessage('CURRENCY_ENTITY_SORT_FIELD')
			)),
			'DATE_UPDATE' => new Main\Entity\DatetimeField('DATE_UPDATE', array(
				'required' => true,
				'default_value' => new Main\Type\DateTime(),
				'title' => Loc::getMessage('CURRENCY_ENTITY_DATE_UPDATE_FIELD')
			)),
			'NUMCODE' => new Main\Entity\StringField('NUMCODE', array(
				'validation' => array(__CLASS__, 'validateNumcode'),
				'title' => Loc::getMessage('CURRENCY_ENTITY_NUMCODE_FIELD')
			)),
			'BASE' => new Main\Entity\BooleanField('BASE', array(
				'values' => array('N','Y'),
				'default_value' => 'N',
				'title' => Loc::getMessage('CURRENCY_ENTITY_BASE_FIELD')
			)),
			'CREATED_BY' => new Main\Entity\IntegerField('CREATED_BY', array(
				'title' => Loc::getMessage('CURRENCY_ENTITY_CREATED_BY_FIELD')
			)),
			'DATE_CREATE' => new Main\Entity\DatetimeField('DATE_CREATE', array(
				'title' => Loc::getMessage('CURRENCY_ENTITY_DATE_CREATE_FIELD')
			)),
			'MODIFIED_BY' => new Main\Entity\IntegerField('MODIFIED_BY', array(
				'title' => Loc::getMessage('CURRENCY_ENTITY_MODIFIED_BY_FIELD')
			)),
			'CURRENT_BASE_RATE' => new Main\Entity\FloatField('CURRENT_BASE_RATE', array(
				'title' => Loc::getMessage('CURRENCY_ENTITY_CURRENT_BASE_RATE_FIELD')
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
			'LANG_FORMAT' => new Main\Entity\ReferenceField(
				'LANG_FORMAT',
				'Bitrix\Currency\CurrencyLang',
				array('=this.CURRENCY' => 'ref.CURRENCY'),
				array('join_type' => 'LEFT')
			),
			'CURRENT_LANG_FORMAT' => new Main\Entity\ReferenceField(
				'CURRENT_LANG_FORMAT',
				'Bitrix\Currency\CurrencyLang',
				array(
					'=this.CURRENCY' => 'ref.CURRENCY',
					'=ref.LID' => new Main\DB\SqlExpression('?', LANGUAGE_ID)
				),
				array('join_type' => 'LEFT')
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
			new Main\Entity\Validator\Length(null, 3),
		);
	}

	/**
	 * Returns validators for NUMCODE field.
	 *
	 * @return array
	 */
	public static function validateNumcode()
	{
		return array(
			new Main\Entity\Validator\Length(null, 3),
		);
	}

	/**
	 * @return string
	 */
	public static function currencyBaseRateAgent()
	{
		\CCurrency::updateAllCurrencyBaseRate();
		return '\Bitrix\Currency\CurrencyTable::currencyBaseRateAgent();';
	}
}