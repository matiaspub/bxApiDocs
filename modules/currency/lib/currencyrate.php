<?php
namespace Bitrix\Currency;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

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

class CurrencyRateTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает название таблицы в базе данных для курсов валют. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/currency/currencyratetable/gettablename.php
	* @author Bitrix
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
	
	/**
	* <p>Метод возвращает список полей для таблицы курсов валют. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/currency/currencyratetable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_ID_FIELD')
			)),
			'CURRENCY' => new Main\Entity\StringField('CURRENCY', array(
				'primary' => true,
				'validation' => array(__CLASS__, 'validateCurrency'),
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_CURRENCY_FIELD')
			)),
			'BASE_CURRENCY' => new Main\Entity\StringField('BASE_CURRENCY', array(
				'primary' => true,
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_BASE_CURRENCY_FIELD')
			)),
			'DATE_RATE' => new Main\Entity\DateField('DATE_RATE', array(
				'primary' => true,
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_DATE_RATE_FIELD')
			)),
			'RATE_CNT' => new Main\Entity\IntegerField('RATE_CNT', array(
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_RATE_CNT_FIELD')
			)),
			'RATE' => new Main\Entity\FloatField('RATE', array(
				'required' => true,
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_RATE_FIELD')
			)),
			'CREATED_BY' => new Main\Entity\IntegerField('CREATED_BY', array(
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_CREATED_BY_FIELD')
			)),
			'DATE_CREATE' => new Main\Entity\DatetimeField('DATE_CREATE', array(
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_DATE_CREATE_FIELD')
			)),
			'MODIFIED_BY' => new Main\Entity\IntegerField('MODIFIED_BY', array(
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_MODIFIED_BY_FIELD')
			)),
			'TIMESTAMP_X' => new Main\Entity\DatetimeField('TIMESTAMP_X', array(
				'required' => true,
				'title' => Loc::getMessage('CURRENCY_RATE_ENTITY_TIMESTAMP_X_FIELD')
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
	 * Returns validators for CURRENCY field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>CURRENCY</code> (код валюты). Метод статический и используется для валидации новых значений полей при добавлении курса валюты или изменении параметров уже существующего.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/currency/currencyratetable/validatecurrency.php
	* @author Bitrix
	*/
	public static function validateCurrency()
	{
		return array(
			new Main\Entity\Validator\Length(null, 3),
		);
	}
}