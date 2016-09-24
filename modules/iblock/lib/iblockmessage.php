<?php
namespace Bitrix\Iblock;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class IblockMessageTable
 *
 * Fields:
 * <ul>
 * <li> IBLOCK_ID int mandatory
 * <li> MESSAGE_ID string(50) mandatory
 * <li> MESSAGE_TEXT string(255) optional
 * <li> IBLOCK reference to {@link \Bitrix\Iblock\IblockTable}
 * </ul>
 *
 * @package Bitrix\Iblock
 */
class IblockMessageTable extends Entity\DataManager
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/iblockmessagetable/getfilepath.php
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
	* <p>Метод возвращает название таблицы подписей и заголовков объектов инфоблоков в базе данных. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/iblockmessagetable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_iblock_messages';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает список полей для таблицы подписей и заголовков объектов инфоблоков. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/iblockmessagetable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		return array(
			'IBLOCK_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'title' => Loc::getMessage('IBLOCK_MESSAGE_ENTITY_IBLOCK_ID_FIELD'),
			),
			'MESSAGE_ID' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateMessageId'),
				'title' => Loc::getMessage('IBLOCK_MESSAGE_ENTITY_MESSAGE_ID_FIELD'),
			),
			'MESSAGE_TEXT' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateMessageText'),
				'title' => Loc::getMessage('IBLOCK_MESSAGE_ENTITY_MESSAGE_TEXT_FIELD'),
			),
			'IBLOCK' => array(
				'data_type' => 'Bitrix\Iblock\Iblock',
				'reference' => array('=this.IBLOCK_ID' => 'ref.ID')
			),
		);
	}

	/**
	 * Returns validators for MESSAGE_ID field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>MESSAGE_ID</code> (идентификатор подписи). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/iblockmessagetable/validatemessageid.php
	* @author Bitrix
	*/
	public static function validateMessageId()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * Returns validators for MESSAGE_TEXT field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>MESSAGE_TEXT</code> (текст подписи). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/iblockmessagetable/validatemessagetext.php
	* @author Bitrix
	*/
	public static function validateMessageText()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
}
