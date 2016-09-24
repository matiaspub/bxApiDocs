<?php
namespace Bitrix\Im\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class StatusTable
 *
 * Fields:
 * <ul>
 * <li> USER_ID int mandatory
 * <li> STATUS string(50) optional default 'online'
 * <li> STATUS_TEXT string(255) optional
 * <li> IDLE datetime optional default 0
 * <li> DESKTOP_LAST_DATE datetime optional default 0
 * <li> MOBILE_LAST_DATE datetime optional default 0
 * <li> EVENT_ID int optional default 0
 * <li> EVENT_UNTIL_DATE datetime optional default 0
 * </ul>
 *
 * @package Bitrix\Im
 **/

class StatusTable extends Entity\DataManager
{
	/**
	 * Returns path to the file which contains definition of the class.
	 *
	 * @return string
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
	public static function getTableName()
	{
		return 'b_im_status';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'title' => Loc::getMessage('STATUS_ENTITY_USER_ID_FIELD'),
			),
			'COLOR' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateColor'),
				'title' => Loc::getMessage('STATUS_ENTITY_COLOR_FIELD'),
			),
			'STATUS' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateStatus'),
				'title' => Loc::getMessage('STATUS_ENTITY_STATUS_FIELD'),
				'default_value' => 'online',
			),
			'STATUS_TEXT' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateStatusText'),
			),
			'IDLE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('STATUS_ENTITY_IDLE_FIELD'),
			),
			'DESKTOP_LAST_DATE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('STATUS_ENTITY_DESKTOP_LAST_DATE_FIELD'),
			),
			'MOBILE_LAST_DATE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('STATUS_ENTITY_MOBILE_LAST_DATE_FIELD'),
			),
			'EVENT_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('STATUS_ENTITY_EVENT_ID_FIELD'),
			),
			'EVENT_UNTIL_DATE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('STATUS_ENTITY_EVENT_UNTIL_DATE_FIELD'),
			),
		);
	}
	/**
	 * Returns validators for STATUS field.
	 *
	 * @return array
	 */
	public static function validateStatus()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for STATUS_TEXT field.
	 *
	 * @return array
	 */
	public static function validateStatusText()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	public static function validateColor()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
}

class_alias("Bitrix\\Im\\Model\\StatusTable", "Bitrix\\Im\\StatusTable", false);