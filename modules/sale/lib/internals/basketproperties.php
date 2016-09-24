<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class BasketPropsTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> BASKET_ID int mandatory
 * <li> NAME string(255) mandatory
 * <li> VALUE string(255) optional
 * <li> CODE string(255) optional
 * <li> SORT int optional default 100
 * </ul>
 *
 * @package Bitrix\Sale
 **/

class BasketPropertyTable
	extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает название таблицы свойств корзины в базе данных. Статический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/basketpropertytable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_sale_basket_props';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает список полей для таблицы свойств корзины. Статический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/basketpropertytable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		return array(
			new Main\Entity\IntegerField(
				'ID',
				 array(
					 'autocomplete' => true,
					 'primary' => true,
				 )
			),
			new Main\Entity\IntegerField(
				'BASKET_ID',
				 array(
					 'required' => true,
				 )
			),
			new Main\Entity\StringField(
				'NAME',
				array(
					'size' => 255,
					'validation' => array(__CLASS__, 'validateName'),
				)
			),
			new Main\Entity\StringField(
				'VALUE',
				array(
					'size' => 255,
					'validation' => array(__CLASS__, 'validateValue'),
				)
			),
			new Main\Entity\StringField(
				'CODE',
				array(
					'size' => 255,
					'validation' => array(__CLASS__, 'validateCode'),
				)
			),

			new Main\Entity\IntegerField(
				'SORT'
			),
			new Main\Entity\ReferenceField(
				'BASKET',
				'Bitrix\Sale\Internals\Basket',
				array(
					'=this.BASKET_ID' => 'ref.ID'
				)
			),
		);
	}
	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>NAME</code> (название свойства). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/basketpropertytable/validatename.php
	* @author Bitrix
	*/
	public static function validateName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for VALUE field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>VALUE</code> (значение свойства). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/basketpropertytable/validatevalue.php
	* @author Bitrix
	*/
	public static function validateValue()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for CODE field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>CODE</code> (код свойства). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/basketpropertytable/validatecode.php
	* @author Bitrix
	*/
	public static function validateCode()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
}