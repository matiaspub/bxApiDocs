<?php
namespace Bitrix\Currency;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class CurrencyLangTable
 *
 * Fields:
 * <ul>
 * <li> CURRENCY string(3) mandatory primary
 * <li> LID string(2) mandatory primary
 * <li> FORMAT_STRING string(50) mandatory
 * <li> FULL_NAME string(50) optional
 * <li> DEC_POINT string(5) optional default '.'
 * <li> THOUSANDS_SEP string(5) optional default ' '
 * <li> DECIMALS int optional default 2
 * <li> THOUSANDS_VARIANT string(1) optional
 * <li> HIDE_ZERO bool optional default 'N'
 * <li> CREATED_BY int optional
 * <li> DATE_CREATE datetime optional
 * <li> MODIFIED_BY int optional
 * <li> TIMESTAMP_X datetime optional
 * <li> CREATED_BY_USER reference to {@link \Bitrix\Main\UserTable}
 * <li> MODIFIED_BY_USER reference to {@link \Bitrix\Main\UserTable}
 * <li> LANGUAGE reference to {@link \Bitrix\Main\Localization\LanguageTable}
 * </ul>
 *
 * @package Bitrix\Currency
 **/

class CurrencyLangTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_catalog_currency_lang';
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
				'title' => Loc::getMessage('CURRENCY_LANG_ENTITY_CURRENCY_FIELD')
			)),
			'LID' => new Main\Entity\StringField('LID', array(
				'primary' => true,
				'validation' => array(__CLASS__, 'validateLid'),
				'title' => Loc::getMessage('CURRENCY_LANG_ENTITY_LID_FIELD'),
			)),
			'FORMAT_STRING' => new Main\Entity\StringField('FORMAT_STRING', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateFormatString'),
				'title' => Loc::getMessage('CURRENCY_LANG_ENTITY_FORMAT_STRING_FIELD')
			)),
			'FULL_NAME' => new Main\Entity\StringField('FULL_NAME', array(
				'validation' => array(__CLASS__, 'validateFullName'),
				'title' => Loc::getMessage('CURRENCY_LANG_ENTITY_FULL_NAME_FIELD')
			)),
			'DEC_POINT' => new Main\Entity\StringField('DEC_POINT', array(
				'default_value' => '.',
				'validation' => array(__CLASS__, 'validateDecPoint'),
				'title' => Loc::getMessage('CURRENCY_LANG_ENTITY_DEC_POINT_FIELD')
			)),
			'THOUSANDS_SEP' => new Main\Entity\StringField('THOUSANDS_SEP', array(
				'default_value' => ' ',
				'validation' => array(__CLASS__, 'validateThousandsSep'),
				'title' => Loc::getMessage('CURRENCY_LANG_ENTITY_THOUSANDS_SEP_FIELD')
			)),
			'DECIMALS' => new Main\Entity\IntegerField('DECIMALS', array(
				'default_value' => 2,
				'title' => Loc::getMessage('CURRENCY_LANG_ENTITY_DECIMALS_FIELD')
			)),
			'THOUSANDS_VARIANT' => new Main\Entity\StringField('THOUSANDS_VARIANT', array(
				'validation' => array(__CLASS__, 'validateThousandsVariant'),
				'title' => Loc::getMessage('CURRENCY_LANG_ENTITY_THOUSANDS_VARIANT_FIELD')
			)),
			'HIDE_ZERO' => new Main\Entity\BooleanField('HIDE_ZERO', array(
				'values' => array('N', 'Y'),
				'default_value' => 'N',
				'title' => Loc::getMessage('CURRENCY_LANG_ENTITY_HIDE_ZERO_FIELD')
			)),
			'CREATED_BY' => new Main\Entity\IntegerField('CREATED_BY', array(
				'title' => Loc::getMessage('CURRENCY_LANG_ENTITY_CREATED_BY_FIELD')
			)),
			'DATE_CREATE' => new Main\Entity\DatetimeField('DATE_CREATE', array(
				'title' => Loc::getMessage('CURRENCY_LANG_ENTITY_DATE_CREATE_FIELD')
			)),
			'MODIFIED_BY' => new Main\Entity\IntegerField('MODIFIED_BY', array(
				'title' => Loc::getMessage('CURRENCY_LANG_ENTITY_MODIFIED_BY_FIELD')
			)),
			'TIMESTAMP_X' => new Main\Entity\DatetimeField('TIMESTAMP_X', array(
				'required' => true,
				'default_value' => new Main\Type\DateTime(),
				'title' => Loc::getMessage('CURRENCY_LANG_ENTITY_TIMESTAMP_X_FIELD')
			)),
			'CREATED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.CREATED_BY' => 'ref.ID'),
			),
			'MODIFIED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.MODIFIED_BY' => 'ref.ID'),
			),
			'LANGUAGE' => array(
				'data_type' => 'Bitrix\Main\Localization\Language',
				'reference' => array('=this.LID' => 'ref.LID'),
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
			new Main\Entity\Validator\Length(null, 3),
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
			new Main\Entity\Validator\Length(2, 2),
		);
	}

	/**
	 * Returns validators for FORMAT_STRING field.
	 *
	 * @return array
	 */
	public static function validateFormatString()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * Returns validators for FULL_NAME field.
	 *
	 * @return array
	 */
	public static function validateFullName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * Returns validators for DEC_POINT field.
	 *
	 * @return array
	 */
	public static function validateDecPoint()
	{
		return array(
			new Main\Entity\Validator\Length(null, 5),
		);
	}

	/**
	 * Returns validators for THOUSANDS_SEP field.
	 *
	 * @return array
	 */
	public static function validateThousandsSep()
	{
		return array(
			new Main\Entity\Validator\Length(null, 5),
		);
	}

	/**
	 * Returns validators for THOUSANDS_VARIANT field.
	 *
	 * @return array
	 */
	public static function validateThousandsVariant()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}
}