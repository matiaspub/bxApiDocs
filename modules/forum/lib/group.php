<?php
namespace Bitrix\Forum;

use Bitrix\Main\Entity;
use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class ForumTable
 *
 * Fields:
 * <ul>
 * <li> ID int not null auto_increment,
 * <li> SORT int not null default '150',
 * <li> PARENT_ID int null,
 * <li> LEFT_MARGIN int null,
 * <li> RIGHT_MARGIN int null,
 * <li> DEPTH_LEVEL int null,
 * <li> XML_ID varchar(255)
 * </ul>
 *
 * @package Bitrix\Forum
 */
class GroupTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_forum_group';
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
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ID'),
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_NAME'),
				'validation' => array(__CLASS__, 'validateName'),
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ACTIVE'),
			),
			'SORT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_SORT'),
			),
			'LIST_PAGE_URL' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_LIST_PAGE_URL'),
				'validation' => array(__CLASS__, 'validateListPageUrl'),
			),
			'DETAIL_PAGE_URL' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_DETAIL_PAGE_URL'),
				'validation' => array(__CLASS__, 'validateDetailPageUrl'),
			),
			'SECTION_PAGE_URL' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_SECTION_PAGE_URL'),
				'validation' => array(__CLASS__, 'validateSectionPageUrl'),
			),
			'CANONICAL_PAGE_URL' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_CANONICAL_PAGE_URL'),
				'validation' => array(__CLASS__, 'validateCanonicalPageUrl'),
			),
			'PICTURE' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_PICTURE'),
			),
			'DESCRIPTION' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_DESCRIPTION'),
			),
			'DESCRIPTION_TYPE' => array(
				'data_type' => 'enum',
				'values' => array('text', 'html'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_DESCRIPTION_TYPE'),
			),
			'XML_ID' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_XML_ID'),
				'validation' => array(__CLASS__, 'validateXmlId'),
			),
			'TMP_ID' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_TMP_ID'),
				'validation' => array(__CLASS__, 'validateTmpId'),
			),
			'INDEX_ELEMENT' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_INDEX_ELEMENT'),
			),
			'INDEX_SECTION' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_INDEX_SECTION'),
			),
			'WORKFLOW' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_WORKFLOW'),
			),
			'BIZPROC' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_BIZPROC'),
			),
			'SECTION_CHOOSER' => array(
				'data_type' => 'enum',
				'values' => array(self::SELECT, self::DROPDOWNS, self::PATH),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_SECTION_CHOOSER'),
			),
			'LIST_MODE' => array(
				'data_type' => 'enum',
				'values' => array(self::SEPARATE, self::COMBINED),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_LIST_MODE'),
			),
			'RIGHTS_MODE' => array(
				'data_type' => 'enum',
				'values' => array(self::SIMPLE, self::EXTENDED),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_RIGHTS_MODE'),
			),
			'SECTION_PROPERTY' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_SECTION_PROPERTY'),
			),
			'PROPERTY_INDEX' => array(
				'data_type' => 'enum',
				'values' => array('N', 'Y', self::INVALID),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_SECTION_PROPERTY'),
			),
			'VERSION' => array(
				'data_type' => 'enum',
				'values' => array(1, 2),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_VERSION'),
			),
			'LAST_CONV_ELEMENT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_LAST_CONV_ELEMENT'),
			),
			'SOCNET_GROUP_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_SOCNET_GROUP_ID'),
			),
			'EDIT_FILE_BEFORE' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_EDIT_FILE_BEFORE'),
				'validation' => array(__CLASS__, 'validateEditFileBefore'),
			),
			'EDIT_FILE_AFTER' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_EDIT_FILE_AFTER'),
				'validation' => array(__CLASS__, 'validateEditFileAfter'),
			),
			'TYPE' => array(
				'data_type' => 'Bitrix\Iblock\Type',
				'reference' => array('=this.IBLOCK_TYPE_ID' => 'ref.ID'),
			),
		);
	}

	/**
	 * Returns validators for IBLOCK_TYPE_ID field.
	 *
	 * @return array
	 */
	public static function validateIblockTypeId()
	{
		return array(
			new Entity\Validator\Length(null, 50),
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
			new Entity\Validator\Length(null, 50),
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
			new Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for LIST_PAGE_URL field.
	 *
	 * @return array
	 */
	public static function validateListPageUrl()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for DETAIL_PAGE_URL field.
	 *
	 * @return array
	 */
	public static function validateDetailPageUrl()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for SECTION_PAGE_URL field.
	 *
	 * @return array
	 */
	public static function validateSectionPageUrl()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for CANONICAL_PAGE_URL field.
	 *
	 * @return array
	 */
	public static function validateCanonicalPageUrl()
	{
		return array(
			new Entity\Validator\Length(null, 255),
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
			new Entity\Validator\Length(null, 255),
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
			new Entity\Validator\Length(null, 40),
		);
	}

	/**
	 * Returns validators for EDIT_FILE_BEFORE field.
	 *
	 * @return array
	 */
	public static function validateEditFileBefore()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for EDIT_FILE_AFTER field.
	 *
	 * @return array
	 */
	public static function validateEditFileAfter()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
}
