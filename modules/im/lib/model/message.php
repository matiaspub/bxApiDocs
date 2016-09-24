<?php
namespace Bitrix\Im\Model;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class MessageTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CHAT_ID int mandatory
 * <li> AUTHOR_ID int mandatory
 * <li> MESSAGE string optional
 * <li> MESSAGE_OUT string optional
 * <li> DATE_CREATE datetime mandatory
 * <li> EMAIL_TEMPLATE string(255) optional
 * <li> NOTIFY_TYPE int optional
 * <li> NOTIFY_MODULE string(255) optional
 * <li> NOTIFY_EVENT string(255) optional
 * <li> NOTIFY_TAG string(255) optional
 * <li> NOTIFY_SUB_TAG string(255) optional
 * <li> NOTIFY_TITLE string(255) optional
 * <li> NOTIFY_BUTTONS string optional
 * <li> NOTIFY_READ bool optional default 'N'
 * <li> IMPORT_ID int optional
 * <li> CHAT reference to {@link \Bitrix\Im\ImRelationTable}
 * <li> NOTIFY_MODULE reference to {@link \Bitrix\Module\ModuleTable}
 * <li> AUTHOR reference to {@link \Bitrix\User\UserTable}
 * </ul>
 *
 * @package Bitrix\Im
 **/

class MessageTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_im_message';
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
				'title' => Loc::getMessage('MESSAGE_ENTITY_ID_FIELD'),
			),
			'CHAT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('MESSAGE_ENTITY_CHAT_ID_FIELD'),
			),
			'AUTHOR_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('MESSAGE_ENTITY_AUTHOR_ID_FIELD'),
			),
			'MESSAGE' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('MESSAGE_ENTITY_MESSAGE_FIELD'),
			),
			'MESSAGE_OUT' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('MESSAGE_ENTITY_MESSAGE_OUT_FIELD'),
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'required' => true,
				'title' => Loc::getMessage('MESSAGE_ENTITY_DATE_CREATE_FIELD'),
				'default_value' => array(__CLASS__, 'getCurrentDate'),
			),
			'EMAIL_TEMPLATE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateEmailTemplate'),
				'title' => Loc::getMessage('MESSAGE_ENTITY_EMAIL_TEMPLATE_FIELD'),
			),
			'NOTIFY_TYPE' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('MESSAGE_ENTITY_NOTIFY_TYPE_FIELD'),
			),
			'NOTIFY_MODULE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateNotifyModule'),
				'title' => Loc::getMessage('MESSAGE_ENTITY_NOTIFY_MODULE_FIELD'),
			),
			'NOTIFY_EVENT' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateNotifyEvent'),
				'title' => Loc::getMessage('MESSAGE_ENTITY_NOTIFY_EVENT_FIELD'),
			),
			'NOTIFY_TAG' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateNotifyTag'),
				'title' => Loc::getMessage('MESSAGE_ENTITY_NOTIFY_TAG_FIELD'),
			),
			'NOTIFY_SUB_TAG' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateNotifySubTag'),
				'title' => Loc::getMessage('MESSAGE_ENTITY_NOTIFY_SUB_TAG_FIELD'),
			),
			'NOTIFY_TITLE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateNotifyTitle'),
				'title' => Loc::getMessage('MESSAGE_ENTITY_NOTIFY_TITLE_FIELD'),
			),
			'NOTIFY_BUTTONS' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('MESSAGE_ENTITY_NOTIFY_BUTTONS_FIELD'),
			),
			'NOTIFY_READ' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('MESSAGE_ENTITY_NOTIFY_READ_FIELD'),
			),
			'IMPORT_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('MESSAGE_ENTITY_IMPORT_ID_FIELD'),
			),
			'CHAT' => array(
				'data_type' => 'Bitrix\Im\ImRelation',
				'reference' => array('=this.CHAT_ID' => 'ref.CHAT_ID'),
			),
			'AUTHOR' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.AUTHOR_ID' => 'ref.ID'),
			),
		);
	}
	/**
	 * Returns validators for EMAIL_TEMPLATE field.
	 *
	 * @return array
	 */
	public static function validateEmailTemplate()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for NOTIFY_MODULE field.
	 *
	 * @return array
	 */
	public static function validateNotifyModule()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for NOTIFY_EVENT field.
	 *
	 * @return array
	 */
	public static function validateNotifyEvent()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for NOTIFY_TAG field.
	 *
	 * @return array
	 */
	public static function validateNotifyTag()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for NOTIFY_SUB_TAG field.
	 *
	 * @return array
	 */
	public static function validateNotifySubTag()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for NOTIFY_TITLE field.
	 *
	 * @return array
	 */
	public static function validateNotifyTitle()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Return current date for DATE_CREATE field.
	 *
	 * @return array
	 */
	public static function getCurrentDate()
	{
		return new \Bitrix\Main\Type\DateTime();
	}
}

class_alias("Bitrix\\Im\\Model\\MessageTable", "Bitrix\\Im\\MessageTable", false);