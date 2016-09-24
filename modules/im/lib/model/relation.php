<?php
namespace Bitrix\Im\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class RelationTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CHAT_ID int mandatory
 * <li> MESSAGE_TYPE string(2) optional default 'P'
 * <li> USER_ID int mandatory
 * <li> START_ID int optional
 * <li> LAST_ID int optional
 * <li> LAST_SEND_ID int optional
 * <li> LAST_FILE_ID int optional
 * <li> LAST_READ datetime optional
 * <li> STATUS int optional
 * <li> CALL_STATUS int optional
 * <li> NOTIFY_BLOCK bool optional default 'N'
 * <li> CHAT reference to {@link \Bitrix\Im\Model\MessageTable}
 * <li> START reference to {@link \Bitrix\Im\Model\MessageTable}
 * <li> LAST_SEND reference to {@link \Bitrix\Im\Model\MessageTable}
 * <li> LAST reference to {@link \Bitrix\Im\Model\MessageTable}
 * <li> USER reference to {@link \Bitrix\Main\UserTable}
 * </ul>
 *
 * @package Bitrix\Im
 **/

class RelationTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_im_relation';
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
				'title' => Loc::getMessage('RELATION_ENTITY_ID_FIELD'),
			),
			'CHAT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('RELATION_ENTITY_CHAT_ID_FIELD'),
			),
			'MESSAGE_TYPE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateMessageType'),
				'title' => Loc::getMessage('RELATION_ENTITY_MESSAGE_TYPE_FIELD'),
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('RELATION_ENTITY_USER_ID_FIELD'),
			),
			'START_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('RELATION_ENTITY_START_ID_FIELD'),
			),
			'LAST_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('RELATION_ENTITY_LAST_ID_FIELD'),
			),
			'LAST_SEND_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('RELATION_ENTITY_LAST_SEND_ID_FIELD'),
			),
			'LAST_FILE_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('RELATION_ENTITY_LAST_FILE_ID_FIELD'),
			),
			'LAST_READ' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('RELATION_ENTITY_LAST_READ_FIELD'),
			),
			'STATUS' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('RELATION_ENTITY_STATUS_FIELD'),
			),
			'CALL_STATUS' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('RELATION_ENTITY_CALL_STATUS_FIELD'),
			),
			'NOTIFY_BLOCK' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('RELATION_ENTITY_NOTIFY_BLOCK_FIELD'),
			),
			'CHAT' => array(
				'data_type' => 'Bitrix\Im\Chat',
				'reference' => array('=this.CHAT_ID' => 'ref.ID'),
			),
			'START' => array(
				'data_type' => 'Bitrix\Im\Message',
				'reference' => array('=this.START_ID' => 'ref.ID'),
			),
			'LAST_SEND' => array(
				'data_type' => 'Bitrix\Im\Message',
				'reference' => array('=this.LAST_SEND_ID' => 'ref.ID'),
			),
			'LAST' => array(
				'data_type' => 'Bitrix\Im\Message',
				'reference' => array('=this.LAST_ID' => 'ref.ID'),
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.USER_ID' => 'ref.ID'),
			),
		);
	}
	/**
	 * Returns validators for MESSAGE_TYPE field.
	 *
	 * @return array
	 */
	public static function validateMessageType()
	{
		return array(
			new Entity\Validator\Length(null, 1),
		);
	}
}

class_alias("Bitrix\\Im\\Model\\RelationTable", "Bitrix\\Im\\RelationTable", false);