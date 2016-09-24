<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class PersonTypeTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> LID string(2) mandatory
 * <li> NAME string(255) mandatory
 * <li> SORT int optional default 150
 * <li> ACTIVE bool optional default 'Y'
 * </ul>
 *
 * @package Bitrix\Sale
 **/

class PersonTypeTable extends Main\Entity\DataManager
{
	/**
	 * Returns path to the file which contains definition of the class.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает путь к файлу, содержащему определение класса. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/persontypetable/getfilepath.php
	* @author Bitrix
	*/
	public static function getFilePath()
	{
		return __FILE__;
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает название таблицы типов плательщиков в базе данных. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/persontypetable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_sale_person_type';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает список полей для таблицы типов плательщиков. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/persontypetable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'LID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateLid'),
			),
			new Main\Entity\ReferenceField(
				'PERSON_TYPE_SITE',
				'\Bitrix\Sale\Internals\PersonTypeSiteTable',
				array('=this.ID' => 'ref.PERSON_TYPE_ID'),
				array('join_type' => 'LEFT')
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateName'),
			),
			'SORT' => array(
				'data_type' => 'integer'
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			)
		);
	}

	/**
	 * Returns validators for LID field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>LID</code> (идентификатор сайта). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/persontypetable/validatelid.php
	* @author Bitrix
	*/
	public static function validateLid()
	{
		return array(
			new Entity\Validator\Length(null, 2),
		);
	}
	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>NAME</code> (название типа плательщика). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/persontypetable/validatename.php
	* @author Bitrix
	*/
	public static function validateName()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
}
