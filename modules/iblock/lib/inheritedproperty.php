<?php
namespace Bitrix\Iblock;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class InheritedPropertyTable extends Entity\DataManager
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedpropertytable/getfilepath.php
	* @author Bitrix
	*/
	public static function getFilePath()
	{
		return __FILE__;
	}

	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает название таблицы шаблонов вычисляемых наследуемых свойств. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedpropertytable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_iblock_iproperty';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает список полей для таблицы шаблонов вычисляемых наследуемых свойств. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedpropertytable/getmap.php
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
			'IBLOCK_ID' => array(
				'data_type' => 'integer',
			),
			'CODE' => array(
				'data_type' => 'string',
			),
			'ENTITY_TYPE' => array(
				'data_type' => 'string',
			),
			'ENTITY_ID' => array(
				'data_type' => 'string',
			),
			'TEMPLATE' => array(
				'data_type' => 'string',
			)
		);
	}
}
