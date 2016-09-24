<?php
namespace Bitrix\Im\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class MessageParamTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> MESSAGE_ID int mandatory
 * <li> PARAM_NAME string(100) mandatory
 * <li> PARAM_VALUE string(100) mandatory
 * </ul>
 *
 * @package Bitrix\Im
 **/

class MessageParamTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_im_message_param';
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
				'title' => Loc::getMessage('MESSAGE_PARAM_ENTITY_ID_FIELD'),
			),
			'MESSAGE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('MESSAGE_PARAM_ENTITY_MESSAGE_ID_FIELD'),
			),
			'PARAM_NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateParamName'),
				'title' => Loc::getMessage('MESSAGE_PARAM_ENTITY_PARAM_NAME_FIELD'),
			),
			'PARAM_VALUE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateParamValue'),
				'title' => Loc::getMessage('MESSAGE_PARAM_ENTITY_PARAM_VALUE_FIELD'),
			),
			'PARAM_JSON' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('MESSAGE_PARAM_ENTITY_PARAM_JSON_FIELD'),
			),
		);
	}
	/**
	 * Returns validators for PARAM_NAME field.
	 *
	 * @return array
	 */
	public static function validateParamName()
	{
		return array(
			new Entity\Validator\Length(null, 100),
		);
	}
	/**
	 * Returns validators for PARAM_VALUE field.
	 *
	 * @return array
	 */
	public static function validateParamValue()
	{
		return array(
			new Entity\Validator\Length(null, 100),
		);
	}
}

class_alias("Bitrix\\Im\\Model\\MessageParamTable", "Bitrix\\Im\\MessageParamTable", false);