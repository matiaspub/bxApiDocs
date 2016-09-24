<?php
namespace Bitrix\Iblock;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class IblockRssTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> IBLOCK_ID int mandatory
 * <li> NODE string(50) mandatory
 * <li> NODE_VALUE string(250) optional
 * <li> IBLOCK reference to {@link \Bitrix\Iblock\IblockTable}
 * </ul>
 *
 * @package Bitrix\Iblock
 **/

class IblockRssTable extends Entity\DataManager
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/iblockrsstable/getfilepath.php
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
	* <p>Метод возвращает название таблицы привязок полей для выгрузки в RSS. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/iblockrsstable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_iblock_rss';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает список полей для таблицы привязок полей для выгрузки в RSS. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/iblockrsstable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('IBLOCK_RSS_ENTITY_ID_FIELD'),
			),
			'IBLOCK_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('IBLOCK_RSS_ENTITY_IBLOCK_ID_FIELD'),
			),
			'NODE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateNode'),
				'title' => Loc::getMessage('IBLOCK_RSS_ENTITY_NODE_FIELD'),
			),
			'NODE_VALUE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateNodeValue'),
				'title' => Loc::getMessage('IBLOCK_ENTITY_NODE_VALUE_FIELD'),
			),
			'IBLOCK' => array(
				'data_type' => 'Bitrix\Iblock\Iblock',
				'reference' => array('=this.IBLOCK_ID' => 'ref.ID'),
			),
		);
	}

	/**
	 * Returns validators for NODE field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>NODE</code> (поле RSS). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/iblockrsstable/validatenode.php
	* @author Bitrix
	*/
	public static function validateNode()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * Returns validators for NODE_VALUE field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>NODE_VALUE</code> (шаблон поля). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/iblockrsstable/validatenodevalue.php
	* @author Bitrix
	*/
	public static function validateNodeValue()
	{
		return array(
			new Entity\Validator\Length(null, 250),
		);
	}
}