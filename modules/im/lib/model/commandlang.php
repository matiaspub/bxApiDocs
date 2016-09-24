<?php
namespace Bitrix\Im\Model;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class CommandLangTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> COMMAND_ID int mandatory
 * <li> LANGUAGE_ID string(2) mandatory
 * <li> TITLE string(255) optional
 * <li> PARAMS string(255) optional
 * </ul>
 *
 * @package Bitrix\Im
 **/

class CommandLangTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_im_command_lang';
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
				'title' => Loc::getMessage('COMMAND_LANG_ENTITY_ID_FIELD'),
			),
			'COMMAND_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('COMMAND_LANG_ENTITY_COMMAND_ID_FIELD'),
			),
			'LANGUAGE_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateLanguageId'),
				'title' => Loc::getMessage('COMMAND_LANG_ENTITY_LANGUAGE_ID_FIELD'),
			),
			'TITLE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateTitle'),
				'title' => Loc::getMessage('COMMAND_LANG_ENTITY_TITLE_FIELD'),
			),
			'PARAMS' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateParams'),
				'title' => Loc::getMessage('COMMAND_LANG_ENTITY_PARAMS_FIELD'),
			),
		);
	}
	/**
	 * Returns validators for LANGUAGE_ID field.
	 *
	 * @return array
	 */
	public static function validateLanguageId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 2),
		);
	}
	/**
	 * Returns validators for TITLE field.
	 *
	 * @return array
	 */
	public static function validateTitle()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for PARAMS field.
	 *
	 * @return array
	 */
	public static function validateParams()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
}

class_alias("Bitrix\\Im\\Model\\CommandLangTable", "Bitrix\\Im\\CommandLangTable", false);