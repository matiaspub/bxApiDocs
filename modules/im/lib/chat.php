<?php
namespace Bitrix\Im;

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
 * <li> AUTHOR_ID int mandatory
 * <li> AVATAR int optional
 * <li> CALL_TYPE int optional
 * <li> CALL_NUMBER string(20) optional
 * <li> ENTITY_TYPE string(50) optional
 * <li> ENTITY_ID string(50) optional
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
			'AUTHOR' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.AUTHOR_ID' => 'ref.ID'),
			),
		);
	}
	public static function validateTitle()
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
			new Entity\Validator\Length(null, 50),
		);
	}
	public static function validateCallNumber()
	{
		return array(
			new Entity\Validator\Length(null, 20),
		);
	}
}