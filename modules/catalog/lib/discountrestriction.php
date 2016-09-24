<?php
namespace Bitrix\Catalog;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class DiscountRestrictionTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DISCOUNT_ID int mandatory
 * <li> ACTIVE bool optional
 * <li> USER_GROUP_ID int mandatory default -1
 * <li> PRICE_TYPE_ID int mandatory default -1
 * </ul>
 *
 * @package Bitrix\Catalog
 **/

class DiscountRestrictionTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает название таблицы ограничений на применение скидок. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/discountrestrictiontable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_catalog_discount_cond';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает список полей для таблицы ограничений на применение скидок. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/discountrestrictiontable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('DISCOUNT_RESTRICTIONS_ENTITY_ID_FIELD')
			)),
			'DISCOUNT_ID' => new Main\Entity\IntegerField('DISCOUNT_ID', array(
				'required' => true,
				'title' => Loc::getMessage('DISCOUNT_RESTRICTIONS_ENTITY_DISCOUNT_ID_FIELD')
			)),
			'ACTIVE' => new Main\Entity\BooleanField('ACTIVE', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('DISCOUNT_RESTRICTIONS_ENTITY_ACTIVE_FIELD')
			)),
			'USER_GROUP_ID' => new Main\Entity\IntegerField('USER_GROUP_ID', array(
				'required' => true,
				'title' => Loc::getMessage('DISCOUNT_RESTRICTIONS_ENTITY_USER_GROUP_ID_FIELD')
			)),
			'PRICE_TYPE_ID' => new Main\Entity\IntegerField('PRICE_TYPE_ID', array(
				'required' => true,
				'title' => Loc::getMessage('DISCOUNT_RESTRICTIONS_ENTITY_PRICE_TYPE_ID_FIELD')
			)),
			'DISCOUNT' => new Main\Entity\ReferenceField(
				'DISCOUNT',
				'Bitrix\Catalog\Discount',
				array('=this.DISCOUNT_ID' => 'ref.ID')
			)
		);
	}
}