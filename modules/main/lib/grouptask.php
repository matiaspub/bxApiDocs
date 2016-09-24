<?php
namespace Bitrix\Main;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class GroupTaskTable
 * 
 * Fields:
 * <ul>
 * <li> GROUP_ID int mandatory
 * <li> TASK_ID int mandatory
 * <li> EXTERNAL_ID string(50) optional
 * <li> GROUP reference to {@link \Bitrix\Main\GroupTable}
 * <li> TASK reference to {@link \Bitrix\Main\TaskTable}
 * </ul>
 *
 * @package Bitrix\Main
 **/

class GroupTaskTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	
	/**
	* <p>Статический метод возвращает название таблицы БД для сущности.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/grouptasktable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_group_task';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	
	/**
	* <p>Статический метод возвращает список полей для таблицы.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/grouptasktable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		return array(
			'GROUP_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'TASK_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'EXTERNAL_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateExternalId'),
			),
			'GROUP' => array(
				'data_type' => 'Bitrix\Main\GroupTable',
				'reference' => array('=this.GROUP_ID' => 'ref.ID'),
			),
			'TASK' => array(
				'data_type' => 'Bitrix\Main\TaskTable',
				'reference' => array('=this.TASK_ID' => 'ref.ID'),
			),
		);
	}
	/**
	 * Returns validators for EXTERNAL_ID field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Статический метод возвращает валидаторы для поля EXTERNAL_ID.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/grouptasktable/validateexternalid.php
	* @author Bitrix
	*/
	public static function validateExternalId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
}