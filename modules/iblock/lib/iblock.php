<?php
namespace Bitrix\Iblock;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class IblockTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime
 * <li> IBLOCK_TYPE_ID string(50) mandatory
 * <li> CODE string(50) optional
 * <li> NAME string(255) mandatory
 * <li> ACTIVE bool optional default 'Y'
 * <li> SORT int optional default 500
 * <li> LIST_PAGE_URL string(255) optional
 * <li> DETAIL_PAGE_URL string(255) optional
 * <li> SECTION_PAGE_URL string(255) optional
 * <li> CANONICAL_PAGE_URL string(255) optional
 * <li> PICTURE int optional
 * <li> DESCRIPTION text optional
 * <li> DESCRIPTION_TYPE enum ('text', 'html') optional default 'text'
 * <li> XML_ID string(255) optional
 * <li> TMP_ID string(40) optional <b>internal use only</b>
 * <li> INDEX_ELEMENT bool optional default 'Y'
 * <li> INDEX_SECTION bool optional default 'N'
 * <li> WORKFLOW bool optional default 'Y'
 * <li> BIZPROC bool optional default 'N'
 * <li> SECTION_CHOOSER enum ('L', 'D' or 'P') optional default 'L'
 * <li> LIST_MODE enum ('S' or 'C') optional default ''
 * <li> RIGHTS_MODE enum ('S' or 'E') optional default 'S'
 * <li> SECTION_PROPERTY bool optional default 'N'
 * <li> PROPERTY_INDEX enum ('N', 'Y', 'I') optional default 'N'
 * <li> VERSION enum (1 or 2) optional default 1
 * <li> LAST_CONV_ELEMENT int optional default 0 <b>internal use only</b>
 * <li> SOCNET_GROUP_ID int optional <b>internal use only</b>
 * <li> EDIT_FILE_BEFORE string(255) optional
 * <li> EDIT_FILE_AFTER string(255) optional
 * <li> TYPE reference to {@link \Bitrix\Iblock\TypeTable}
 * </ul>
 *
 * @package Bitrix\Iblock
 */
class IblockTable extends Entity\DataManager
{
	const SELECT = 'L';
	const DROPDOWNS = 'D';
	const PATH = 'P';
	const SEPARATE = 'S';
	const COMBINED = 'C';
	const SIMPLE = 'S';
	const EXTENDED = 'E';
	const INVALID = 'I';

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
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_iblock';
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
				'title' => Loc::getMessage('IBLOCK_ENTITY_ID_FIELD'),
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('IBLOCK_ENTITY_TIMESTAMP_X_FIELD'),
			),
			'IBLOCK_TYPE_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('IBLOCK_ENTITY_IBLOCK_TYPE_ID_FIELD'),
				'validation' => array(__CLASS__, 'validateIblockTypeId'),
			),
			'CODE' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('IBLOCK_ENTITY_CODE_FIELD'),
				'validation' => array(__CLASS__, 'validateCode'),
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('IBLOCK_ENTITY_NAME_FIELD'),
				'validation' => array(__CLASS__, 'validateName'),
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
				'title' => Loc::getMessage('IBLOCK_ENTITY_ACTIVE_FIELD'),
			),
			'SORT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('IBLOCK_ENTITY_SORT_FIELD'),
			),
			'LIST_PAGE_URL' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('IBLOCK_ENTITY_LIST_PAGE_URL_FIELD'),
				'validation' => array(__CLASS__, 'validateListPageUrl'),
			),
			'DETAIL_PAGE_URL' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('IBLOCK_ENTITY_DETAIL_PAGE_URL_FIELD'),
				'validation' => array(__CLASS__, 'validateDetailPageUrl'),
			),
			'SECTION_PAGE_URL' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('IBLOCK_ENTITY_SECTION_PAGE_URL_FIELD'),
				'validation' => array(__CLASS__, 'validateSectionPageUrl'),
			),
			'CANONICAL_PAGE_URL' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('IBLOCK_ENTITY_CANONICAL_PAGE_URL_FIELD'),
				'validation' => array(__CLASS__, 'validateCanonicalPageUrl'),
			),
			'PICTURE' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('IBLOCK_ENTITY_PICTURE_FIELD'),
			),
			'DESCRIPTION' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('IBLOCK_ENTITY_DESCRIPTION_FIELD'),
			),
			'DESCRIPTION_TYPE' => array(
				'data_type' => 'enum',
				'values' => array('text', 'html'),
				'title' => Loc::getMessage('IBLOCK_ENTITY_DESCRIPTION_TYPE_FIELD'),
			),
			'XML_ID' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('IBLOCK_ENTITY_XML_ID_FIELD'),
				'validation' => array(__CLASS__, 'validateXmlId'),
			),
			'TMP_ID' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('IBLOCK_ENTITY_TMP_ID_FIELD'),
				'validation' => array(__CLASS__, 'validateTmpId'),
			),
			'INDEX_ELEMENT' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
				'title' => Loc::getMessage('IBLOCK_ENTITY_INDEX_ELEMENT_FIELD'),
			),
			'INDEX_SECTION' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
				'title' => Loc::getMessage('IBLOCK_ENTITY_INDEX_SECTION_FIELD'),
			),
			'WORKFLOW' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
				'title' => Loc::getMessage('IBLOCK_ENTITY_WORKFLOW_FIELD'),
			),
			'BIZPROC' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
				'title' => Loc::getMessage('IBLOCK_ENTITY_BIZPROC_FIELD'),
			),
			'SECTION_CHOOSER' => array(
				'data_type' => 'enum',
				'values' => array(self::SELECT, self::DROPDOWNS, self::PATH),
				'title' => Loc::getMessage('IBLOCK_ENTITY_SECTION_CHOOSER_FIELD'),
			),
			'LIST_MODE' => array(
				'data_type' => 'enum',
				'values' => array(self::SEPARATE, self::COMBINED),
				'title' => Loc::getMessage('IBLOCK_ENTITY_LIST_MODE_FIELD'),
			),
			'RIGHTS_MODE' => array(
				'data_type' => 'enum',
				'values' => array(self::SIMPLE, self::EXTENDED),
				'title' => Loc::getMessage('IBLOCK_ENTITY_RIGHTS_MODE_FIELD'),
			),
			'SECTION_PROPERTY' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
				'title' => Loc::getMessage('IBLOCK_ENTITY_SECTION_PROPERTY_FIELD'),
			),
			'PROPERTY_INDEX' => array(
				'data_type' => 'enum',
				'values' => array('N', 'Y', self::INVALID),
				'title' => Loc::getMessage('IBLOCK_ENTITY_SECTION_PROPERTY_FIELD'),
			),
			'VERSION' => array(
				'data_type' => 'enum',
				'values' => array(1, 2),
				'title' => Loc::getMessage('IBLOCK_ENTITY_VERSION_FIELD'),
			),
			'LAST_CONV_ELEMENT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('IBLOCK_ENTITY_LAST_CONV_ELEMENT_FIELD'),
			),
			'SOCNET_GROUP_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('IBLOCK_ENTITY_SOCNET_GROUP_ID_FIELD'),
			),
			'EDIT_FILE_BEFORE' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('IBLOCK_ENTITY_EDIT_FILE_BEFORE_FIELD'),
				'validation' => array(__CLASS__, 'validateEditFileBefore'),
			),
			'EDIT_FILE_AFTER' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('IBLOCK_ENTITY_EDIT_FILE_AFTER_FIELD'),
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
