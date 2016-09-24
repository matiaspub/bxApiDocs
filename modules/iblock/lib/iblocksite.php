<?php
namespace Bitrix\Iblock;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class IblockSiteTable
 *
 * Fields:
 * <ul>
 * <li> IBLOCK_ID int mandatory
 * <li> SITE_ID char(2) mandatory
 * <li> IBLOCK reference to {@link \Bitrix\Iblock\IblockTable}
 * <li> SITE reference to {@link \Bitrix\Main\SiteTable}
 * </ul>
 *
 * @package Bitrix\Iblock
 */
class IblockSiteTable extends Entity\DataManager
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/iblocksitetable/getfilepath.php
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
	* <p>Метод возвращает название таблицы привязки инфоблоков к сайтам в базе данных. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/iblocksitetable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_iblock_site';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает список полей для таблицы привязки инфоблоков к сайтам. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/iblocksitetable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		return array(
			'IBLOCK_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'title' => Loc::getMessage('IBLOCK_SITE_ENTITY_IBLOCK_ID_FIELD'),
			),
			'SITE_ID' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateSiteId'),
				'title' => Loc::getMessage('IBLOCK_SITE_ENTITY_SITE_ID_FIELD'),
			),
			'IBLOCK' => array(
				'data_type' => 'Bitrix\Iblock\Iblock',
				'reference' => array('=this.IBLOCK_ID' => 'ref.ID')
			),
			'SITE' => array(
				'data_type' => 'Bitrix\Main\Site',
				'reference' => array('=this.SITE_ID' => 'ref.LID'),
			),
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/iblocksitetable/validatesiteid.php
	* @author Bitrix
	*/
	public static function validateSiteId()
	{
		return array(
			new Entity\Validator\Length(null, 2),
		);
	}
}
