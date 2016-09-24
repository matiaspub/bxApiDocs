<?php
namespace Bitrix\Catalog;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class PriceTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> PRODUCT_ID int mandatory
 * <li> EXTRA_ID int optional
 * <li> CATALOG_GROUP_ID int mandatory
 * <li> PRICE double mandatory
 * <li> CURRENCY string(3) mandatory
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> QUANTITY_FROM int optional
 * <li> QUANTITY_TO int optional
 * <li> TMP_ID string(40) optional
 * <li> PRICE_SCALE double optional
 * </ul>
 *
 * @package Bitrix\Catalog
 **/

class PriceTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает название таблицы ценовых предложений товаров. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/pricetable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_catalog_price';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает список полей для таблицы ценовых предложений товаров. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/pricetable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('PRICE_ENTITY_ID_FIELD')
			)),
			'PRODUCT_ID' => new Main\Entity\IntegerField('PRODUCT_ID', array(
				'required' => true,
				'title' => Loc::getMessage('PRICE_ENTITY_PRODUCT_ID_FIELD')
			)),
			'EXTRA_ID' => new Main\Entity\IntegerField('EXTRA_ID', array(
				'title' => Loc::getMessage('PRICE_ENTITY_EXTRA_ID_FIELD')
			)),
			'CATALOG_GROUP_ID' => new Main\Entity\IntegerField('CATALOG_GROUP_ID', array(
				'required' => true,
				'title' => Loc::getMessage('PRICE_ENTITY_CATALOG_GROUP_ID_FIELD')
			)),
			'PRICE' => new Main\Entity\FloatField('PRICE', array(
				'required' => true,
				'title' => Loc::getMessage('PRICE_ENTITY_PRICE_FIELD')
			)),
			'CURRENCY' => new Main\Entity\StringField('CURRENCY', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateCurrency'),
				'title' => Loc::getMessage('PRICE_ENTITY_CURRENCY_FIELD')
			)),
			'TIMESTAMP_X' => new Main\Entity\DatetimeField('TIMESTAMP_X', array(
				'default_value' => new Main\Type\DateTime(),
				'title' => Loc::getMessage('PRICE_ENTITY_TIMESTAMP_X_FIELD')
			)),
			'QUANTITY_FROM' => new Main\Entity\IntegerField('QUANTITY_FROM', array(
				'title' => Loc::getMessage('PRICE_ENTITY_QUANTITY_FROM_FIELD')
			)),
			'QUANTITY_TO' => new Main\Entity\IntegerField('QUANTITY_TO', array(
				'title' => Loc::getMessage('PRICE_ENTITY_QUANTITY_TO_FIELD')
			)),
			'TMP_ID' => new Main\Entity\StringField('TMP_ID', array(
				'validation' => array(__CLASS__, 'validateTmpId'),
				'title' => Loc::getMessage('PRICE_ENTITY_TMP_ID_FIELD')
			)),
			'PRICE_SCALE' => new Main\Entity\FloatField('PRICE_SCALE', array(
				'required' => true,
				'title' => Loc::getMessage('PRICE_ENTITY_PRICE_SCALE_FIELD')
			)),
			'CATALOG_GROUP' => new Main\Entity\ReferenceField(
				'CATALOG_GROUP',
				'Bitrix\Catalog\Group',
				array('=this.CATALOG_GROUP_ID' => 'ref.ID')
			),
			'ELEMENT' => new Main\Entity\ReferenceField(
				'ELEMENT',
				'Bitrix\Iblock\ElementTable',
				array('=this.PRODUCT_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
			'PRODUCT' => new Main\Entity\ReferenceField(
				'PRODUCT',
				'Bitrix\Catalog\ProductTable',
				array('=this.PRODUCT_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/pricetable/validatecurrency.php
	* @author Bitrix
	*/
	public static function validateCurrency()
	{
		return array(
			new Main\Entity\Validator\Length(null, 3),
		);
	}
	/**
	 * Returns validators for TMP_ID field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>TMP_ID</code> (временный символьный идентификатор, используемый для служебных целей). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/pricetable/validatetmpid.php
	* @author Bitrix
	*/
	public static function validateTmpId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 40),
		);
	}
}