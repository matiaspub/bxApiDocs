<?php
namespace Bitrix\Im\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class ChatTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TITLE string(255) optional
 * <li> DESCRIPTION text optional
 * <li> TYPE string(2) optional
 * <li> AUTHOR_ID int mandatory
 * <li> AVATAR int optional
 * <li> COLOR string optional
 * <li> CALL_TYPE int optional
 * <li> CALL_NUMBER string(20) optional
 * <li> EXTRANET bool optional default 'N'
 * <li> ENTITY_TYPE string(50) optional
 * <li> ENTITY_ID string(255) optional
 * <li> ENTITY_DATA_1 string(255 optional
 * <li> ENTITY_DATA_2 string(255) optional
 * <li> ENTITY_DATA_3 string(255) optional
 * <li> DISK_FOLDER_ID int optional
 * <li> AUTHOR reference to {@link \Bitrix\User\UserTable}
 * </ul>
 *
 * @package Bitrix\Im
 **/

class ChatTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_im_chat';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('CHAT_ENTITY_ID_FIELD'),
			),
			'TITLE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateTitle'),
				'title' => Loc::getMessage('CHAT_ENTITY_TITLE_FIELD'),
			),
			'DESCRIPTION' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('CHAT_ENTITY_DESCRIPTION_FIELD'),
			),
			'COLOR' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateColor'),
				'title' => Loc::getMessage('CHAT_ENTITY_COLOR_FIELD'),
			),
			'TYPE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateType'),
				'title' => Loc::getMessage('CHAT_ENTITY_TYPE_FIELD'),
				'default_value' => 'P',
			),
			'EXTRANET' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('CHAT_ENTITY_EXTRANET_FIELD'),
				'default_value' => 'N',
			),
			'AUTHOR_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('CHAT_ENTITY_AUTHOR_ID_FIELD'),
			),
			'AVATAR' => array(
				'data_type' => 'integer'
			),
			'CALL_TYPE' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CHAT_ENTITY_CALL_TYPE_FIELD'),
			),
			'CALL_NUMBER' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCallNumber'),
				'title' => Loc::getMessage('CHAT_ENTITY_CALL_NUMBER_FIELD'),
			),
			'ENTITY_TYPE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateEntityType'),
				'title' => Loc::getMessage('CHAT_ENTITY_ENTITY_TYPE_FIELD'),
			),
			'ENTITY_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateEntityId'),
				'title' => Loc::getMessage('CHAT_ENTITY_ENTITY_ID_FIELD'),
			),
			'ENTITY_DATA_1' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateEntityData'),
				'title' => Loc::getMessage('CHAT_ENTITY_ENTITY_DATA_1_FIELD'),
			),
			'ENTITY_DATA_2' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateEntityData'),
				'title' => Loc::getMessage('CHAT_ENTITY_ENTITY_DATA_2_FIELD'),
			),
			'ENTITY_DATA_3' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateEntityData'),
				'title' => Loc::getMessage('CHAT_ENTITY_ENTITY_DATA_3_FIELD'),
			),
			'AUTHOR' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.AUTHOR_ID' => 'ref.ID'),
			),
			'DISK_FOLDER_ID' => array(
				'data_type' => 'integer'
			),
			'LAST_MESSAGE_ID' => array(
				'data_type' => 'integer',
				'default_value' => 0
			),
		);
	}
	public static function validateTitle()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateType()
	{
		return array(
			new Entity\Validator\Length(null, 2),
		);
	}
	public static function validateColor()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateEntityType()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
	public static function validateEntityId()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateCallNumber()
	{
		return array(
			new Entity\Validator\Length(null, 20),
		);
	}
	public static function validateEntityData()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
}

class_alias("Bitrix\\Im\\Model\\ChatTable", "Bitrix\\Im\\ChatTable", false);