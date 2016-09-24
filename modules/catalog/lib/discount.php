<?php
namespace Bitrix\Catalog;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc,
	Bitrix\Currency;

Loc::loadMessages(__FILE__);

/**
 * Class DiscountTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> XML_ID string(255) optional
 * <li> SITE_ID string(2) mandatory
 * <li> TYPE int mandatory
 * <li> ACTIVE bool optional default 'Y'
 * <li> ACTIVE_FROM datetime optional
 * <li> ACTIVE_TO datetime optional
 * <li> RENEWAL bool optional default 'N'
 * <li> NAME string(255) optional
 * <li> SORT int optional default 100
 * <li> MAX_DISCOUNT double optional
 * <li> VALUE_TYPE string(1) mandatory default 'P'
 * <li> VALUE double mandatory default 0.0000
 * <li> CURRENCY string(3) mandatory
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> COUNT_PERIOD string(1) mandatory default 'U'
 * <li> COUNT_SIZE int mandatory
 * <li> COUNT_TYPE bool optional default 'Y'
 * <li> COUNT_FROM datetime optional
 * <li> COUNT_TO datetime optional
 * <li> ACTION_SIZE int mandatory
 * <li> ACTION_TYPE bool optional default 'Y'
 * <li> MODIFIED_BY int optional
 * <li> DATE_CREATE datetime optional
 * <li> CREATED_BY int optional
 * <li> PRIORITY int optional default 1
 * <li> LAST_DISCOUNT bool optional default 'Y'
 * <li> VERSION int optional default 1
 * <li> NOTES string(255) optional
 * <li> CONDITIONS string optional
 * <li> UNPACK string optional
 * <li> COUPON reference to {@link \Bitrix\Catalog\DiscountCoupon}
 * <li> CREATED_BY_USER reference to {@link \Bitrix\Main\UserTable}
 * <li> MODIFIED_BY_USER reference to {@link \Bitrix\Main\UserTable}
 * <li> RESTRICTION reference to {@link \Bitrix\Catalog\DiscountRestriction}
 * </ul>
 *
 * @package Bitrix\Catalog
 **/

class DiscountTable extends Main\Entity\DataManager
{
	const TYPE_DISCOUNT = 0;
	const TYPE_DISCOUNT_SAVE = 1;

	const VALUE_TYPE_PERCENT = 'P';
	const VALUE_TYPE_FIX = 'F';
	const VALUE_TYPE_SALE = 'S';

	const COUNT_PERIOD_TYPE_ALL = 'U';
	const COUNT_PERIOD_TYPE_INTERVAL = 'D';
	const COUNT_PERIOD_TYPE_PERIOD = 'P';

	const COUNT_TYPE_SIZE_DAY = 'D';
	const COUNT_TYPE_SIZE_MONTH ='M';
	const COUNT_TYPE_SIZE_YEAR = 'Y';

	const ACTION_PERIOD_TYPE_ALL = 'U';
	const ACTION_PERIOD_TYPE_INTERVAL = 'D';
	const ACTION_PERIOD_TYPE_PERIOD = 'P';

	const ACTION_TYPE_SIZE_DAY = 'D';
	const ACTION_TYPE_SIZE_MONTH ='M';
	const ACTION_TYPE_SIZE_YEAR = 'Y';

	const ACTUAL_VERSION = 2;
	const OLD_VERSION = 1;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает название таблицы скидок на товары. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/discounttable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_catalog_discount';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает список полей для таблицы скидок на товары. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/discounttable/getmap.php
	* @author Bitrix
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
			'SITE_ID' => new Main\Entity\StringField('SITE_ID', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateSiteId'),
				'title' => Loc::getMessage('DISCOUNT_ENTITY_SITE_ID_FIELD')
			)),
			'TYPE' => new Main\Entity\IntegerField('TYPE', array(
				'required' => true,
				'default_value' => self::TYPE_DISCOUNT,
				'validation' => array(__CLASS__, 'validateType'),
				'title' => Loc::getMessage('DISCOUNT_ENTITY_TYPE_FIELD')
			)),
			'ACTIVE' => new Main\Entity\BooleanField('ACTIVE', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
				'title' => Loc::getMessage('DISCOUNT_ENTITY_ACTIVE_FIELD')
			)),
			'ACTIVE_FROM' => new Main\Entity\DatetimeField('ACTIVE_FROM', array(
				'default_value' => null,
				'title' => Loc::getMessage('DISCOUNT_ENTITY_ACTIVE_FROM_FIELD')
			)),
			'ACTIVE_TO' => new Main\Entity\DatetimeField('ACTIVE_TO', array(
				'default_value' => null,
				'title' => Loc::getMessage('DISCOUNT_ENTITY_ACTIVE_TO_FIELD')
			)),
			'RENEWAL' => new Main\Entity\BooleanField('RENEWAL', array(
				'values' => array('N', 'Y'),
				'default_value' => 'N',
				'title' => Loc::getMessage('DISCOUNT_ENTITY_RENEWAL_FIELD')
			)),
			'NAME' => new Main\Entity\StringField('NAME', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('DISCOUNT_ENTITY_NAME_FIELD')
			)),
			'SORT' => new Main\Entity\IntegerField('SORT', array(
				'title' => Loc::getMessage('DISCOUNT_ENTITY_SORT_FIELD')
			)),
			'MAX_DISCOUNT' => new Main\Entity\FloatField('MAX_DISCOUNT', array(
				'title' => Loc::getMessage('DISCOUNT_ENTITY_MAX_DISCOUNT_FIELD')
			)),
			'VALUE_TYPE' => new Main\Entity\EnumField('VALUE_TYPE', array(
				'required' => true,
				'values' => array(self::VALUE_TYPE_PERCENT, self::VALUE_TYPE_FIX, self::VALUE_TYPE_SALE),
				'default_value' => self::VALUE_TYPE_PERCENT,
				'title' => Loc::getMessage('DISCOUNT_ENTITY_VALUE_TYPE_FIELD')
			)),
			'VALUE' => new Main\Entity\FloatField('VALUE', array(
				'required' => true,
				'title' => Loc::getMessage('DISCOUNT_ENTITY_VALUE_FIELD')
			)),
			'CURRENCY' => new Main\Entity\StringField('CURRENCY', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateCurrency'),
				'title' => Loc::getMessage('DISCOUNT_ENTITY_CURRENCY_FIELD')
			)),
			'TIMESTAMP_X' => new Main\Entity\DatetimeField('TIMESTAMP_X', array(
				'required' => true,
				'default_value' => new Main\Type\DateTime(),
				'title' => Loc::getMessage('DISCOUNT_ENTITY_TIMESTAMP_X_FIELD')
			)),
			'COUNT_PERIOD' => new Main\Entity\EnumField('COUNT_PERIOD', array(
				'values' => array(self::COUNT_PERIOD_TYPE_ALL, self::COUNT_PERIOD_TYPE_INTERVAL, self::COUNT_PERIOD_TYPE_PERIOD),
				'default_value' => self::COUNT_PERIOD_TYPE_ALL
			)),
			'COUNT_SIZE' => new Main\Entity\IntegerField('COUNT_SIZE', array(
				'default_value' => 0
			)),
			'COUNT_TYPE' => new Main\Entity\EnumField('COUNT_TYPE', array(
				'values' => array(self::COUNT_TYPE_SIZE_DAY, self::COUNT_TYPE_SIZE_MONTH, self::COUNT_TYPE_SIZE_YEAR),
				'default_value' => self::COUNT_TYPE_SIZE_YEAR
			)),
			'COUNT_FROM' => new Main\Entity\DatetimeField('COUNT_FROM', array(
				'default_value' => null
			)),
			'COUNT_TO' => new Main\Entity\DatetimeField('COUNT_TO', array(
				'default_value' => null
			)),
			'ACTION_SIZE' => new Main\Entity\IntegerField('ACTION_SIZE', array(
				'default_value' => 0
			)),
			'ACTION_TYPE' => new Main\Entity\EnumField('ACTION_TYPE', array(
				'values' => array(self::ACTION_TYPE_SIZE_DAY, self::ACTION_TYPE_SIZE_MONTH, self::ACTION_TYPE_SIZE_YEAR),
				'default_value' => self::ACTION_TYPE_SIZE_YEAR
			)),
			'MODIFIED_BY' => new Main\Entity\IntegerField('MODIFIED_BY', array(
				'title' => Loc::getMessage('DISCOUNT_ENTITY_MODIFIED_BY_FIELD')
			)),
			'DATE_CREATE' => new Main\Entity\DatetimeField('DATE_CREATE', array(
				'default_value' => null,
				'title' => Loc::getMessage('DISCOUNT_ENTITY_DATE_CREATE_FIELD')
			)),
			'CREATED_BY' => new Main\Entity\IntegerField('CREATED_BY', array(
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
				'values' => array(self::OLD_VERSION, self::ACTUAL_VERSION),
				'default_value' => self::ACTUAL_VERSION
			)),
			'NOTES' => new Main\Entity\StringField('NOTES', array(
				'validation' => array(__CLASS__, 'validateNotes'),
				'title' => Loc::getMessage('DISCOUNT_ENTITY_NOTES_FIELD')
			)),
			'CONDITIONS' => new Main\Entity\TextField('CONDITIONS', array()),
			'CONDITIONS_LIST' => new Main\Entity\TextField('CONDITIONS_LIST', array(
				'serialized' => true,
				'column_name' => 'CONDITIONS',
				'title' => Loc::getMessage('DISCOUNT_ENTITY_CONDITIONS_LIST_FIELD')
			)),
			'UNPACK' => new Main\Entity\TextField('UNPACK', array()),
			'COUPON' => new Main\Entity\ReferenceField(
				'COUPON',
				'Bitrix\Catalog\DiscountCoupon',
				array('=this.ID' => 'ref.DISCOUNT_ID'),
				array('join_type' => 'LEFT')
			),
			'CREATED_BY_USER' => new Main\Entity\ReferenceField(
				'CREATED_BY_USER',
				'Bitrix\Main\User',
				array('=this.CREATED_BY' => 'ref.ID')
			),
			'MODIFIED_BY_USER' => new Main\Entity\ReferenceField(
				'MODIFIED_BY_USER',
				'Bitrix\Main\User',
				array('=this.MODIFIED_BY' => 'ref.ID')
			),
			'RESTRICTION' => new Main\Entity\ReferenceField(
				'RESTRICTIONS',
				'Bitrix\Catalog\DiscountRestriction',
				array('=this.ID' => 'ref.DISCOUNT_ID')
			)
		);
	}
	/**
	 * Returns validators for XML_ID field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>XML_ID</code> (внешний код). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/discounttable/validatexmlid.php
	* @author Bitrix
	*/
	public static function validateXmlId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for SITE_ID field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>SITE_ID</code> (идентификатор сайта). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/discounttable/validatesiteid.php
	* @author Bitrix
	*/
	public static function validateSiteId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 2),
		);
	}
	/**
	 * Returns validators for TYPE field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>TYPE</code> (тип скидки). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/discounttable/validatetype.php
	* @author Bitrix
	*/
	public static function validateType()
	{
		return array(
			array(__CLASS__, 'checkType')
		);
	}
	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>NAME</code> (название скидки). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/discounttable/validatename.php
	* @author Bitrix
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
	
	/**
	* <p>Метод возвращает валидатор для поля <code>CURRENCY</code> (код валюты). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/discounttable/validatecurrency.php
	* @author Bitrix
	*/
	public static function validateCurrency()
	{
		return array(
			new Main\Entity\Validator\Length(null, 3),
		);
	}
	/**
	 * Returns validators for NOTES field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>NOTES</code> (краткое описание скидки). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/discounttable/validatenotes.php
	* @author Bitrix
	*/
	public static function validateNotes()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Check TYPE field.
	 *
	 * @param int $value					Current field value.
	 * @param int|array $primary			Primary key.
	 * @param array $row					Current data.
	 * @param Main\Entity\Field $field		Field object.
	 * @return bool|string
	 */
	
	/**
	* <p>Метод проверяет поле <code>TYPE</code> (вид скидки: обычная или накопительная). Метод статический.</p>
	*
	*
	* @param integer $value  Текущее значение поля.
	*
	* @param integer $integer  Первичный ключ записи.
	*
	* @param array $primary  Массив обновляемых значений.
	*
	* @param array $row  Поле объекта.
	*
	* @param array $Bitrix  
	*
	* @param Bitri $Main  
	*
	* @param Mai $Entity  
	*
	* @param Field $field  
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/discounttable/checktype.php
	* @author Bitrix
	*/
	public static function checkType($value, $primary, array $row, Main\Entity\Field $field)
	{
		if (
			$value == self::TYPE_DISCOUNT
			|| $value == self::TYPE_DISCOUNT_SAVE
		)
		{
			return true;
		}
		return Loc::getMessage('DISCOUNT_ENTITY_VALIDATOR_TYPE');
	}

	/**
	 * Add discount.
	 *
	 * @param array $data			Discount data.
	 * @return Main\Entity\AddResult
	 */
	
	/**
	* <p>Метод добавляет новую скидку в соответствии с данными из массива <i>$data</i>. Метод статический и является методом-заглушкой, порождающим исключения для добавления скидок. В настоящий момент необходимо использовать <a href="http://dev.1c-bitrix.ru/api_help/index.php" >АПИ старого ядра</a>.</p>
	*
	*
	* @param array $data  Массив параметров новой скидки.
	*
	* @return \Bitrix\Main\Entity\AddResult 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/discounttable/add.php
	* @author Bitrix
	*/
	public static function add(array $data)
	{
		$result = new Main\Entity\AddResult();
		$result->addError(new Main\Entity\EntityError(
			Loc::getMessage('CATALOG_DISCOUNT_ENTITY_MESS_ADD_BLOCKED')
		));
		return $result;
	}

	/**
	 * Updates discount by primary key.
	 *
	 * @param mixed $primary		Discount primary key.
	 * @param array $data			Discount data.
	 * @return Main\Entity\UpdateResult
	 */
	
	/**
	* <p>Метод изменяет параметры скидки с ключом <code>$primary</code> в соответствии с данными из массива <code>$data</code>.  Метод статический и является методом-заглушкой, порождающим исключения для изменения скидок. В настоящий момент необходимо использовать <a href="http://dev.1c-bitrix.ru/api_help/index.php" >АПИ старого ядра</a>.</p>
	*
	*
	* @param mixed $primary  Первичный ключ скидки.
	*
	* @param array $data  Массив параметров скидки.
	*
	* @return \Bitrix\Main\Entity\UpdateResult 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/discounttable/update.php
	* @author Bitrix
	*/
	public static function update($primary, array $data)
	{
		$result = new Main\Entity\UpdateResult();
		$result->addError(new Main\Entity\EntityError(
			Loc::getMessage('CATALOG_DISCOUNT_ENTITY_MESS_UPDATE_BLOCKED')
		));
		return $result;
	}

	/**
	 * Deletes discount by primary key.
	 *
	 * @param mixed $primary		Discount primary key.
	 * @return Main\Entity\DeleteResult
	 */
	
	/**
	* <p>Метод удаляет скидку с первичным ключом <code>$primary</code>.  Метод статический и является методом-заглушкой, порождающим исключения для удаления скидок. В настоящий момент необходимо использовать <a href="http://dev.1c-bitrix.ru/api_help/index.php" >АПИ старого ядра</a>.</p>
	*
	*
	* @param mixed $primary  Первичный ключ скидки.
	*
	* @return \Bitrix\Main\Entity\DeleteResult 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/discounttable/delete.php
	* @author Bitrix
	*/
	public static function delete($primary)
	{
		$result = new Main\Entity\DeleteResult();
		$result->addError(new Main\Entity\EntityError(
			Loc::getMessage('CATALOG_DISCOUNT_ENTITY_MESS_DELETE_BLOCKED')
		));
		return $result;
	}

	/**
	 * Convert discount data to other currency (sale currency).
	 *
	 * @param array &$discount				Discout data.
	 * @param string $currency				New currency.
	 * @return void
	 */
	
	/**
	* <p>Метод пересчитывает параметры скидки в другой валюте (валюте магазина). Метод статический.</p>
	*
	*
	* @param array &$discount  Массив с параметрами скидки.
	*
	* @param string $currency  Новая валюта.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/discounttable/convertcurrency.php
	* @author Bitrix
	*/
	public static function convertCurrency(&$discount, $currency)
	{
		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		if ($currency === false || empty($discount) || !is_array($discount))
			return;
		if (!isset($discount['VALUE_TYPE']) || !isset($discount['CURRENCY']) || $discount['CURRENCY'] == $currency)
			return;

		switch ($discount['VALUE_TYPE'])
		{
			case self::VALUE_TYPE_FIX:
			case self::VALUE_TYPE_SALE:
				$discount['VALUE'] = roundEx(
					\CCurrencyRates::convertCurrency($discount['VALUE'], $discount['CURRENCY'], $currency),
					CATALOG_VALUE_PRECISION
				);
				$discount['CURRENCY'] = $currency;
				break;
			case self::VALUE_TYPE_PERCENT:
				if ($discount['MAX_DISCOUNT'] > 0)
					$discount['MAX_DISCOUNT'] = roundEx(
						\CCurrencyRates::convertCurrency($discount['MAX_DISCOUNT'], $discount['CURRENCY'], $currency),
						CATALOG_VALUE_PRECISION
					);
				$discount['CURRENCY'] = $currency;
				break;
		}
	}
}