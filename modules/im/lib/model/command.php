<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage bitrix24
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Im\Model;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class CommandTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> BOT_ID int optional
 * <li> COMMAND string(255) mandatory
 * <li> COMMON bool optional default 'N'
 * <li> HIDDEN bool optional default 'N'
 * <li> SONET_SUPPORT bool optional default 'N'
 * <li> EXTRANET_SUPPORT bool optional default 'N'
 * <li> CLASS string(255) optional
 * <li> METHOD_COMMAND_ADD string(255) optional
 * <li> METHOD_LANG_GET string(255) optional
 * <li> MODULE_ID string(50) mandatory
 * </ul>
 *
 * @package Bitrix\Im
 **/

class CommandTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_im_command';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('COMMAND_ENTITY_ID_FIELD'),
			),
			'BOT_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('COMMAND_ENTITY_BOT_ID_FIELD'),
			),
			'APP_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateAppId'),
				'title' => Loc::getMessage('COMMAND_ENTITY_APP_ID_FIELD'),
				'default_value' => '',
			),
			'COMMAND' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateCommand'),
				'title' => Loc::getMessage('COMMAND_ENTITY_COMMAND_FIELD'),
			),
			'COMMON' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('COMMAND_ENTITY_COMMON_FIELD'),
				'default_value' => 'N',
			),
			'HIDDEN' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('COMMAND_ENTITY_HIDDEN_FIELD'),
				'default_value' => 'N',
			),
			'EXTRANET_SUPPORT' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('COMMAND_ENTITY_EXTRANET_SUPPORT_FIELD'),
				'default_value' => 'N',
			),
			'SONET_SUPPORT' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('COMMAND_ENTITY_SONET_SUPPORT_FIELD'),
				'default_value' => 'N',
			),
			'CLASS' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateClass'),
				'title' => Loc::getMessage('COMMAND_ENTITY_CLASS_FIELD'),
			),
			'METHOD_COMMAND_ADD' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateMethodCommandAdd'),
				'title' => Loc::getMessage('COMMAND_ENTITY_METHOD_COMMAND_ADD_FIELD'),
			),
			'METHOD_LANG_GET' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateMethodLangGet'),
				'title' => Loc::getMessage('COMMAND_ENTITY_METHOD_LANG_GET_FIELD'),
			),
			'MODULE_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateModuleId'),
				'title' => Loc::getMessage('COMMAND_ENTITY_MODULE_ID_FIELD'),
			),
		);
	}
	/**
	 * Returns validators for APP_ID field.
	 *
	 * @return array
	 */
	public static function validateAppId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for COMMAND field.
	 *
	 * @return array
	 */
	public static function validateCommand()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for CLASS field.
	 *
	 * @return array
	 */
	public static function validateClass()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for METHOD_COMMAND_ADD field.
	 *
	 * @return array
	 */
	public static function validateMethodCommandAdd()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for METHOD_LANG_GET field.
	 *
	 * @return array
	 */
	public static function validateMethodLangGet()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for MODULE_ID field.
	 *
	 * @return array
	 */
	public static function validateModuleId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
}

class_alias("Bitrix\\Im\\Model\\CommandTable", "Bitrix\\Im\\CommandTable", false);