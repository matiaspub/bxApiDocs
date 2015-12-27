<?php
namespace Bitrix\Iblock;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class ElementTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime optional
 * <li> MODIFIED_BY int optional
 * <li> DATE_CREATE datetime optional
 * <li> CREATED_BY int optional
 * <li> IBLOCK_ID int mandatory
 * <li> IBLOCK_SECTION_ID int optional
 * <li> ACTIVE bool optional default 'Y'
 * <li> ACTIVE_FROM datetime optional
 * <li> ACTIVE_TO datetime optional
 * <li> SORT int optional default 500
 * <li> NAME string(255) mandatory
 * <li> PREVIEW_PICTURE int optional
 * <li> PREVIEW_TEXT string optional
 * <li> PREVIEW_TEXT_TYPE enum ('text', 'html') optional default 'text'
 * <li> DETAIL_PICTURE int optional
 * <li> DETAIL_TEXT string optional
 * <li> DETAIL_TEXT_TYPE enum ('text', 'html') optional default 'text'
 * <li> SEARCHABLE_CONTENT string optional
 * <li> WF_STATUS_ID int optional default 1
 * <li> WF_PARENT_ELEMENT_ID int optional
 * <li> WF_NEW enum ('N', 'Y') optional
 * <li> WF_LOCKED_BY int optional
 * <li> WF_DATE_LOCK datetime optional
 * <li> WF_COMMENTS string optional
 * <li> IN_SECTIONS bool optional default 'N'
 * <li> XML_ID string(255) optional
 * <li> CODE string(255) optional
 * <li> TAGS string(255) optional
 * <li> TMP_ID string(40) optional
 * <li> WF_LAST_HISTORY_ID int optional
 * <li> SHOW_COUNTER int optional
 * <li> SHOW_COUNTER_START datetime optional
 * <li> PREVIEW_PICTURE_FILE reference to {@link \Bitrix\File\FileTable}
 * <li> DETAIL_PICTURE_FILE reference to {@link \Bitrix\File\FileTable}
 * <li> IBLOCK reference to {@link \Bitrix\Iblock\IblockTable}
 * <li> WF_PARENT_ELEMENT reference to {@link \Bitrix\Iblock\IblockElementTable}
 * <li> IBLOCK_SECTION reference to {@link \Bitrix\Iblock\IblockSectionTable}
 * <li> MODIFIED_BY_USER reference to {@link \Bitrix\User\UserTable}
 * <li> CREATED_BY_USER reference to {@link \Bitrix\User\UserTable}
 * <li> WF_LOCKED_BY_USER reference to {@link \Bitrix\User\UserTable}
 * </ul>
 *
 * @package Bitrix\Iblock
 **/

class ElementTable extends Main\Entity\DataManager
{
	const TYPE_TEXT = 'text';
	const TYPE_HTML = 'html';

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_iblock_element';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ELEMENT_ENTITY_ID_FIELD'),
			)),
			'TIMESTAMP_X' => new Main\Entity\DatetimeField('TIMESTAMP_X', array(
				'default_value' => new Main\Type\DateTime(),
				'title' => Loc::getMessage('ELEMENT_ENTITY_TIMESTAMP_X_FIELD'),
			)),
			'MODIFIED_BY' => new Main\Entity\IntegerField('MODIFIED_BY', array(
				'title' => Loc::getMessage('ELEMENT_ENTITY_MODIFIED_BY_FIELD'),
			)),
			'DATE_CREATE' => new Main\Entity\DatetimeField('DATE_CREATE', array(
				'default_value' => new Main\Type\DateTime(),
				'title' => Loc::getMessage('ELEMENT_ENTITY_DATE_CREATE_FIELD'),
			)),
			'CREATED_BY' => new Main\Entity\IntegerField('CREATED_BY', array(
				'title' => Loc::getMessage('ELEMENT_ENTITY_CREATED_BY_FIELD'),
			)),
			'IBLOCK_ID' => new Main\Entity\IntegerField('IBLOCK_ID', array(
				'required' => true,
				'title' => Loc::getMessage('ELEMENT_ENTITY_IBLOCK_ID_FIELD'),
			)),
			'IBLOCK_SECTION_ID' => new Main\Entity\IntegerField('IBLOCK_SECTION_ID', array(
				'title' => Loc::getMessage('ELEMENT_ENTITY_IBLOCK_SECTION_ID_FIELD'),
			)),
			'ACTIVE' => new Main\Entity\BooleanField('ACTIVE', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
				'title' => Loc::getMessage('ELEMENT_ENTITY_ACTIVE_FIELD'),
			)),
			'ACTIVE_FROM' => new Main\Entity\DatetimeField('ACTIVE_FROM', array(
				'title' => Loc::getMessage('ELEMENT_ENTITY_ACTIVE_FROM_FIELD'),
			)),
			'ACTIVE_TO' => new Main\Entity\DatetimeField('ACTIVE_TO', array(
				'title' => Loc::getMessage('ELEMENT_ENTITY_ACTIVE_TO_FIELD'),
			)),
			'SORT' => new Main\Entity\IntegerField('SORT', array(
				'default_value' => 500,
				'title' => Loc::getMessage('ELEMENT_ENTITY_SORT_FIELD'),
			)),
			'NAME' => new Main\Entity\StringField('NAME', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('ELEMENT_ENTITY_NAME_FIELD'),
			)),
			'PREVIEW_PICTURE' => new Main\Entity\IntegerField('PREVIEW_PICTURE', array(
				'title' => Loc::getMessage('ELEMENT_ENTITY_PREVIEW_PICTURE_FIELD'),
			)),
			'PREVIEW_TEXT' => new Main\Entity\TextField('PREVIEW_TEXT', array(
				'title' => Loc::getMessage('ELEMENT_ENTITY_PREVIEW_TEXT_FIELD'),
			)),
			'PREVIEW_TEXT_TYPE' => new Main\Entity\EnumField('PREVIEW_TEXT_TYPE', array(
				'values' => array(self::TYPE_TEXT, self::TYPE_HTML),
				'default_value' => self::TYPE_TEXT,
				'title' => Loc::getMessage('ELEMENT_ENTITY_PREVIEW_TEXT_TYPE_FIELD'),
			)),
			'DETAIL_PICTURE' => new Main\Entity\IntegerField('DETAIL_PICTURE', array(
				'title' => Loc::getMessage('ELEMENT_ENTITY_DETAIL_PICTURE_FIELD'),
			)),
			'DETAIL_TEXT' => new Main\Entity\TextField('DETAIL_TEXT', array(
				'title' => Loc::getMessage('ELEMENT_ENTITY_DETAIL_TEXT_FIELD'),
			)),
			'DETAIL_TEXT_TYPE' => new Main\Entity\EnumField('DETAIL_TEXT_TYPE', array(
				'values' => array(self::TYPE_TEXT, self::TYPE_HTML),
				'default_value' => self::TYPE_TEXT,
				'title' => Loc::getMessage('ELEMENT_ENTITY_DETAIL_TEXT_TYPE_FIELD'),
			)),
			'SEARCHABLE_CONTENT' => new Main\Entity\TextField('SEARCHABLE_CONTENT', array(
				'title' => Loc::getMessage('ELEMENT_ENTITY_SEARCHABLE_CONTENT_FIELD'),
			)),
			'WF_STATUS_ID' => new Main\Entity\IntegerField('WF_STATUS_ID', array(
				'title' => Loc::getMessage('ELEMENT_ENTITY_WF_STATUS_ID_FIELD'),
			)),
			'WF_PARENT_ELEMENT_ID' => new Main\Entity\IntegerField('WF_PARENT_ELEMENT_ID', array(
				'title' => Loc::getMessage('ELEMENT_ENTITY_WF_PARENT_ELEMENT_ID_FIELD'),
			)),
			'WF_NEW' => new Main\Entity\EnumField('WF_NEW', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('ELEMENT_ENTITY_WF_NEW_FIELD'),
			)),
			'WF_LOCKED_BY' => new Main\Entity\IntegerField('WF_LOCKED_BY', array(
				'title' => Loc::getMessage('ELEMENT_ENTITY_WF_LOCKED_BY_FIELD'),
			)),
			'WF_DATE_LOCK' => new Main\Entity\DatetimeField('WF_DATE_LOCK', array(
				'title' => Loc::getMessage('ELEMENT_ENTITY_WF_DATE_LOCK_FIELD'),
			)),
			'WF_COMMENTS' => new Main\Entity\TextField('WF_COMMENTS', array(
				'title' => Loc::getMessage('ELEMENT_ENTITY_WF_COMMENTS_FIELD'),
			)),
			'IN_SECTIONS' => new Main\Entity\BooleanField('IN_SECTIONS', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('ELEMENT_ENTITY_IN_SECTIONS_FIELD'),
			)),
			'XML_ID' => new Main\Entity\StringField('XML_ID', array(
				'validation' => array(__CLASS__, 'validateXmlId'),
				'title' => Loc::getMessage('ELEMENT_ENTITY_XML_ID_FIELD'),
			)),
			'CODE' => new Main\Entity\StringField('CODE', array(
				'validation' => array(__CLASS__, 'validateCode'),
				'title' => Loc::getMessage('ELEMENT_ENTITY_CODE_FIELD'),
			)),
			'TAGS' => new Main\Entity\StringField('TAGS', array(
				'validation' => array(__CLASS__, 'validateTags'),
				'title' => Loc::getMessage('ELEMENT_ENTITY_TAGS_FIELD'),
			)),
			'TMP_ID' => new Main\Entity\StringField('TMP_ID', array(
				'validation' => array(__CLASS__, 'validateTmpId'),
				'title' => Loc::getMessage('ELEMENT_ENTITY_TMP_ID_FIELD'),
			)),
			'SHOW_COUNTER' => new Main\Entity\IntegerField('SHOW_COUNTER', array(
				'default_value' => 0,
				'title' => Loc::getMessage('ELEMENT_ENTITY_SHOW_COUNTER_FIELD'),
			)),
			'SHOW_COUNTER_START' => new Main\Entity\DatetimeField('SHOW_COUNTER_START', array(
				'title' => Loc::getMessage('ELEMENT_ENTITY_SHOW_COUNTER_START_FIELD'),
			)),
			'IBLOCK' => new Main\Entity\ReferenceField(
				'IBLOCK',
				'Bitrix\Iblock\Iblock',
				array('=this.IBLOCK_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
			'WF_PARENT_ELEMENT' => new Main\Entity\ReferenceField(
				'WF_PARENT_ELEMENT',
				'Bitrix\Iblock\Element',
				array('=this.WF_PARENT_ELEMENT_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
			'IBLOCK_SECTION' => new Main\Entity\ReferenceField(
				'IBLOCK_SECTION',
				'Bitrix\Iblock\Section',
				array('=this.IBLOCK_SECTION_ID' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
			'MODIFIED_BY_USER' => new Main\Entity\ReferenceField(
				'MODIFIED_BY_USER',
				'Bitrix\Main\User',
				array('=this.MODIFIED_BY' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
			'CREATED_BY_USER' => new Main\Entity\ReferenceField(
				'CREATED_BY_USER',
				'Bitrix\Main\User',
				array('=this.CREATED_BY' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
			'WF_LOCKED_BY_USER' => new Main\Entity\ReferenceField(
				'WF_LOCKED_BY_USER',
				'Bitrix\Main\User',
				array('=this.WF_LOCKED_BY' => 'ref.ID'),
				array('join_type' => 'LEFT')
			),
		);
	}
	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for XML_ID field.
	 *
	 * @return array
	 */
	public static function validateXmlId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for CODE field.
	 *
	 * @return array
	 */
	public static function validateCode()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for TAGS field.
	 *
	 * @return array
	 */
	public static function validateTags()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for TMP_ID field.
	 *
	 * @return array
	 */
	public static function validateTmpId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 40),
		);
	}

	/**
	 * Add iblock element.
	 *
	 * @param array $data			Element data.
	 * @return Main\Entity\AddResult
	 */
	public static function add(array $data)
	{
		$result = new Main\Entity\AddResult();
		$result->addError(new Main\Entity\EntityError(
			Loc::getMessage('ELEMENT_ENTITY_MESS_ADD_BLOCKED')
		));
		return $result;
	}

	/**
	 * Updates iblock element by primary key.
	 *
	 * @param mixed $primary		Element primary key.
	 * @param array $data			Element data.
	 * @return Main\Entity\UpdateResult
	 */
	public static function update($primary, array $data)
	{
		$result = new Main\Entity\UpdateResult();
		$result->addError(new Main\Entity\EntityError(
			Loc::getMessage('ELEMENT_ENTITY_MESS_UPDATE_BLOCKED')
		));
		return $result;
	}

	/**
	 * Deletes iblock element by primary key.
	 *
	 * @param mixed $primary		Element primary key.
	 * @return Main\Entity\DeleteResult
	 */
	public static function delete($primary)
	{
		$result = new Main\Entity\DeleteResult();
		$result->addError(new Main\Entity\EntityError(
			Loc::getMessage('ELEMENT_ENTITY_MESS_DELETE_BLOCKED')
		));
		return $result;
	}
}